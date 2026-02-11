<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Services\KeycloakService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private const LOGIN_VIEW = 'Auth/Login';
    private const OPENID_SCOPES = 'openid profile email';

    public function __construct(
        private KeycloakService $keycloakService
    ) {}

    /**
     * Display the login view.
     */
    public function login(Request $request): Response|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectToDashboard();
        }

        return Inertia::render(self::LOGIN_VIEW, [
            'loginAttempt' => false,
            'hasAccess' => false,
            'status' => session('status'),
        ]);
    }

    
    /**
     * IDIB/BCSC/BCeID login.
     */
    public function portalLogin(Request $request)
    {
        $provider = new Keycloak([
            'authServerUrl' => config('auth.keycloak.auth_server_url'),
            'realm' => config('auth.keycloak.realm'),
            'clientId' => config('auth.keycloak.client_id'),
            'clientSecret' => config('auth.keycloak.client_secret'),
            'redirectUri' => config('auth.keycloak.redirect_uri'),
            'scopes' => self::OPENID_SCOPES,
        ]);

        return $this->loginUser($request, $provider);
    }

    /**
     * IDIR/Government staff login.
     */
    public function idirLogin(Request $request)
    {
        $provider = new Keycloak([
            'authServerUrl' => config('auth.keycloak.auth_server_url'),
            'realm' => config('auth.keycloak.realm'),
            'clientId' => config('auth.keycloak.client_id'),
            'clientSecret' => config('auth.keycloak.client_secret'),
            'redirectUri' => config('auth.keycloak.redirect_uri'),
            'scopes' => self::OPENID_SCOPES,
        ]);

        return $this->loginUser($request, $provider, 'idir');
    }

    /**
     * BC Services Card login.
     */
    public function bcscLogin(Request $request)
    {
        $provider = new Keycloak([
            'authServerUrl' => config('auth.keycloak.auth_server_url'),
            'realm' => config('auth.keycloak.realm'),
            'clientId' => config('auth.keycloak.client_id'),
            'clientSecret' => config('auth.keycloak.client_secret'),
            'redirectUri' => config('auth.keycloak.redirect_uri'),
            'scopes' => self::OPENID_SCOPES,
        ]);

        return $this->loginUser($request, $provider, 'bcsc');
    }

    /**
     * BCeID login.
     */
    public function bceidLogin(Request $request)
    {
        $provider = new Keycloak([
            'authServerUrl' => config('auth.keycloak.auth_server_url'),
            'realm' => config('auth.keycloak.realm'),
            'clientId' => config('auth.keycloak.client_id'),
            'clientSecret' => config('auth.keycloak.client_secret'),
            'redirectUri' => config('auth.keycloak.redirect_uri'),
            'scopes' => self::OPENID_SCOPES,
        ]);

        return $this->loginUser($request, $provider, 'bceid');
    }

    /**
     * Handle OAuth login flow.
     */
    private function loginUser(Request $request, $provider, string $idpType = null)
    {
        if (!$request->has('code')) {
            // If we don't have an authorization code then get one
            $authUrl = $provider->getAuthorizationUrl([
                'scope' => self::OPENID_SCOPES,
            ]);

            $request->session()->put('oauth2state', $provider->getState());
            // $request->session()->put($provider->getState(), $idpType);
            
            Log::info('Redirecting to Keycloak for authentication', [
                'idp_type' => $idpType,
                'auth_url' => $authUrl,
            ]);

            // Add IDP hints based on login type
            if ($idpType === 'bcsc') {
                return redirect($authUrl . '&kc_idp_hint=' . env('KEYCLOAK_CLIENT_ID'));
            } elseif ($idpType === 'bceid') {
                return redirect($authUrl . '&kc_idp_hint=bceidbusiness');
            } elseif ($idpType === 'idir') {
                return redirect($authUrl . '&kc_idp_hint=azureidir');
            }

            // Default redirect without hint
            return redirect($authUrl);

        } elseif (!$request->has('state') || ($request->state !== $request->session()->get('oauth2state'))) {
            // Invalid state - security check failed
            Log::warning('OAuth state mismatch', [
                'request_state' => $request->state,
                'session_state' => $request->session()->get('oauth2state'),
            ]);
            
            $request->session()->forget('oauth2state');
            // $request->session()->forget($provider->getState());

            return Inertia::render(self::LOGIN_VIEW, [
                'loginAttempt' => true,
                'hasAccess' => false,
                'status' => 'Authentication failed. Please try again.',
            ]);
        } else {
            // We have a valid code, exchange it for a token

            // Get IDP type from session using the state as key
            $state = $request->session()->get('oauth2state');
            // $idpType = $request->session()->get($state);
            
            try {
                $token = $provider->getAccessToken('authorization_code', [
                    'code' => $request->code,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to get access token', [
                    'error' => $e->getMessage(),
                    'state' => $state,
                ]);
                
                return Inertia::render(self::LOGIN_VIEW, [
                    'loginAttempt' => true,
                    'hasAccess' => false,
                    'status' => 'Failed to get access token',
                ]);
            }

            // Get user profile from Keycloak
            try {
                $providerUser = $provider->getResourceOwner($token);
                $providerUser = $providerUser->toArray();

                Log::info('User authenticated with Keycloak', [
                    // 'idp_type' => $idpType,
                    'user_email' => $providerUser['email'] ?? 'unknown',
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to get resource owner', [
                    'error' => $e->getMessage(),
                    // 'idp_type' => $idpType,
                ]);
                
                return Inertia::render(self::LOGIN_VIEW, [
                    'loginAttempt' => true,
                    'hasAccess' => false,
                    'status' => 'Failed to get user information: ' . $e->getMessage(),
                ]);
            }

            // Find or create user based on IDP type
            [$user, $idpType] = $this->findOrCreateUser($providerUser, $token, $request);
            
            if (!$user) {
                return Inertia::render(self::LOGIN_VIEW, [
                    'loginAttempt' => true,
                    'hasAccess' => false,
                    'status' => 'Access denied. Please contact administrator.',
                ]);
            }

            // Log the user in
            Auth::login($user);
            
            Log::info('User logged in successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'idp_type' => $idpType,
            ]);

            // Redirect based on user role
            return $this->redirectToDashboard();
        }
    }

    /**
     * Find or create user based on identity provider.
     * @return array|null [User, idpType] or null
     */
    private function findOrCreateUser(array $providerUser, $token = null, Request $request = null): ?array
    {
        Log::info('findOrCreateUser', [
            'providerUser' => $providerUser,
            'token' => $token,
            // 'idp_type' => $idpType,
        ]);

        $user = User::where('keycloak_id', $providerUser['sub'])->first();

        /* 
        if $providerUser['bceid_user_guid'] is set, $idpType = bceid;
        if $providerUser['idir_user_guid'] is set, $idpType = idir;
        else it is bcsc 
        */
        $idpType = 'bcsc';
        if (isset($providerUser['bceid_user_guid'])) {
            $idpType = 'bceid';
        } elseif (isset($providerUser['idir_user_guid'])) {
            $idpType = 'idir';
        }


        // If user doesn't exist, create them
        // if (!$user && $this->shouldCreateUser($providerUser)) {
        if (!$user) {
            \Log::info('Creating new user', [
                'email' => $providerUser['email'] ?? 'unknown',
                'idp_type' => $idpType,
            ]);
            $user = $this->createNewUser($providerUser, $idpType, $token);
        }

        // if ($idpType === 'bcsc') {
        //     $this->createStudentProfile($user, $providerUser);
        // }


        // Update user information and tokens for existing users
        else {
            \Log::info('Updating existing user', [
                'user_id' => $user->id,
                'email' => $user->email,
                'idp_type' => $idpType,
            ]);
            if (isset($providerUser['name'])) {
                $user->name = $providerUser['name'];
            }
            
            // Update Keycloak ID if available
            // if (isset($providerUser['sub'])) {
            //     $user->keycloak_id = $providerUser['sub'];
            // }
            
            // Update tokens if provided
            if ($token) {
                $user->kc_token = $token->getToken();
                if ($token->getRefreshToken()) {
                    $user->kc_refresh_token = $token->getRefreshToken();
                }
            }
            
            $user->save();
        }

        //this is needed for BCSC
        $tokenValues = $token->getValues();
        if (isset($tokenValues['id_token'])) {
            $idToken = $tokenValues['id_token'];
            
            //$request is undefined here
            $request->session()->put('bcsc_logout_uri_' . $user->id, env('KEYCLOAK_BCSC_LOGOUT_URL').'?state='.
                $request->state.'&scope=profile%20email&response_type=code&approval_prompt=auto&client_id=pdex&id_token_hint='.
                $idToken.'&post_logout_redirect_uri='.env('KEYCLOAK_BCSC_REDIRECT_LOGOUT_URI'));

            $returnUrl = env('KEYCLOAK_LOGOUT_URL1') . '?retnow=1&returl=' . urlencode(env('KEYCLOAK_LOGOUT_URL2').'?id_token_hint=' . $idToken . '&post_logout_redirect_uri=' . env('KEYCLOAK_LOGOUT_URL3'));
            $request->session()->put('kc_logout_uri_' . $user->id, $returnUrl);
        }
        return array($user, $idpType);
    }

    /**
     * Create new user.
     */
    private function createNewUser(array $providerUser, string $idpType, $token = null): User
    {
        $user = new User();
        $user->guid = Str::orderedUuid()->getHex();
        $user->name = Str::upper($providerUser['name'] ?? '');
        $user->first_name = Str::upper($providerUser['given_names'] ?? '');
        $user->last_name = Str::upper($providerUser['family_name'] ?? '');
        $user->email = Str::lower($providerUser['email'] ?? '');
        $user->display_name = Str::upper($providerUser['display_name'] ?? '');
        $user->family_name = Str::upper($providerUser['family_name'] ?? '');
        $user->given_name = Str::upper($providerUser['given_names'] ?? '');

        $user->identity_provider = $idpType;
        $user->keycloak_id = $providerUser['sub'];
        
        // Store tokens if provided
        if ($token) {
            $user->kc_token = $token->getToken();
            if ($token->getRefreshToken()) {
                $user->kc_refresh_token = $token->getRefreshToken();
            }
        }
        
        // Set IDP-specific fields
        switch ($idpType) {
            case 'bcsc':
                // default bcsc user to active state
                $user->is_active = true;

                break;
                
            case 'idir':
                $user->idir_user_guid = $providerUser['idir_user_guid'] ?? null;
                $user->idir_username = $providerUser['idir_username'] ?? null;
                break;
                
            case 'bceid':
                $user->bceid_user_guid = $providerUser['bceid_user_guid'] ?? null;
                $user->bceid_username = $providerUser['bceid_username'] ?? null;
                $user->bceid_business_guid = $providerUser['bceid_business_guid'] ?? null;
                $user->organization = Str::upper($providerUser['bceid_business_name'] ?? '');
                break;
                
            default:
                Log::warning('Unknown identity provider type', ['idp_type' => $idpType]);
                break;
        }
        
        $user->save();

        // Assign default role based on IDP type
        $this->assignDefaultRole($user, $idpType);

        // Create student profile for BCSC users
        if ($idpType === 'bcsc') {
            $this->createStudentProfile($user, $providerUser);
        }

        Log::info('New user created', [
            'user_id' => $user->id,
            'email' => $user->email,
            'idp_type' => $idpType,
            'keycloak_id' => $user->keycloak_id,
        ]);

        return $user;
    }

    /**
     * Check if we should create a new user.
     */
    // private function shouldCreateUser(array $providerUser): bool
    // {
    //     // Add validation logic here
    //     return isset($providerUser['email']) && !empty($providerUser['email']);
    // }

    /**
     * Assign default role based on identity provider.
     */
    private function assignDefaultRole(User $user, string $idpType): void
    {
        // Check the current route to determine if this is admin login
        $isAdminLogin = request()->is('admin/*') || session('admin_login_requested');
        
        $roleMap = [
            'bcsc' => Role::STUDENT,
            'idir' => $isAdminLogin ? Role::ADMIN_GUEST : Role::MINISTRY_USER,  // Admin vs Ministry based on login route
            'bceid' => Role::INSTITUTION_USER,
        ];

        if (isset($roleMap[$idpType])) {
            $role = Role::where('name', $roleMap[$idpType])->first();
            if ($role) {
                $user->roles()->attach($role);
                
                // Set is_active to false only for new admin IDIR users (Admin Guest)
                if ($idpType === 'idir' && $isAdminLogin) {
                    $user->is_active = false;
                    $user->save();
                }
            }
        }
    }

    /**
     * Log the user out (handles both GET and POST).
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();
        
        if ($user) {
            Log::info('User logging out', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        }

        Auth::logout($user);
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->flush(); // Clear all session data

        return redirect('/login')->with('message', 'You have been logged out successfully.');
    }

    /**
     * Force logout route - public access for SSO logout.
     * This route performs the same logout action as clicking the logout button
     * and redirects users to SSO logout URL.
     */
    public function forceLogout(Request $request): RedirectResponse
    {
        $user = Auth::user();
        
        if ($user) {
            Log::info('Force logout initiated', [
                'user_id' => $user->id,
                'email' => $user->email,
                'identity_provider' => $user->identity_provider,
            ]);

            // Get logout URLs from session (same as logout button)
            $logoutUrl = $request->session()->get('kc_logout_uri_' . $user->id);
            $logoutBcscUrl = $request->session()->get('bcsc_logout_uri_' . $user->id);
            
            // Logout the user
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Redirect to appropriate SSO logout URL
            if ($logoutBcscUrl) {
                Log::info('Redirecting to BCSC SSO logout', ['logout_url' => $logoutBcscUrl]);
                return redirect($logoutBcscUrl);
            }
            
            if ($logoutUrl) {
                Log::info('Redirecting to Keycloak SSO logout', ['logout_url' => $logoutUrl]);
                return redirect($logoutUrl);
            }
            
            Log::info('No SSO logout URL found in session, redirecting to home');
        } else {
            Log::info('Force logout called but no user was authenticated');
        }

        // Fallback to home page if no user or logout URL
        return redirect('/');
    }

    /**
     * Redirect to appropriate dashboard based on user roles and IDP type.
     */
    private function redirectToDashboard(): RedirectResponse
    {
        $user = Auth::user();

        // Check if user is inactive (Admin Guest status)
        if (!$user->is_active && $user->hasRole(Role::ADMIN_GUEST)) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
            
            return redirect()->route('admin.login')
                ->withErrors(['error' => 'Your account is pending approval. Please contact an administrator.']);
        }

        // Check if admin user is trying to access ministry dashboard
        if (session('admin_accessing_ministry') && $user->hasAnyRole([Role::SUPER_ADMIN, Role::ADMIN_MANAGER, Role::APPLICATION_MANAGER, Role::SECURITY_OFFICER, Role::PRIVACY_OFFICER])) {
            session()->forget('admin_accessing_ministry');
            return redirect()->route('ministry.dashboard');
        }

        // Admin roles - only accessible via /admin/login
        $adminRoles = [Role::SUPER_ADMIN, Role::ADMIN_MANAGER, Role::APPLICATION_MANAGER, Role::SECURITY_OFFICER, Role::PRIVACY_OFFICER];
        if ($user->hasAnyRole($adminRoles)) {
            return redirect()->route('admin.dashboard');
        }

        // IDP-based routing for general login
        // IDIR users (ministry) - check by role or IDP type
        if ($user->hasAnyRole([Role::MINISTRY_USER, Role::MINISTRY_ADMIN]) || 
            (!empty($user->idir_user_guid) && !$user->hasRole(Role::ADMIN_GUEST))) {
            return redirect()->route('ministry.dashboard');
        }

        // BCeID users (institutions)
        if ($user->hasAnyRole([Role::INSTITUTION_ADMIN, Role::INSTITUTION_USER]) || !empty($user->bceid_user_guid)) {
            return redirect()->route('institution.dashboard');
        }

        // BCSC users (students)
        if ($user->hasRole(Role::STUDENT) || !empty($user->bcsc_user_guid)) {
            return redirect()->route('student.dashboard');
        }

        // Default dashboard for other cases
        return redirect()->route('login')
            ->withErrors(['error' => 'Could not access dashboard. Please contact an administrator. Error #0082940']);
    }

    /**
     * Create student profile for newly registered BCSC users
     */
    private function createStudentProfile(User $user, array $providerUser): void
    {
        // Check if profile already exists to avoid duplicates
        $existingProfile = \App\Models\Individual::where('user_guid', $user->guid)->first();
        if ($existingProfile) {
            Log::info('Student profile already exists for user, skipping creation', [
                'user_guid' => $user->guid,
            ]);
            return;
        }

        try {
            $individual = \App\Models\Individual::create([
                'user_guid' => $user->guid,
                'first_name' => Str::upper($providerUser['given_names'] ?? ''),
                'last_name' => Str::upper($providerUser['family_name'] ?? ''),
                'email_address' => Str::lower($providerUser['email'] ?? ''),
                'date_of_birth' => $providerUser['birthdate'] ?? null,
                'sex' => $providerUser['gender'] ?? null,
            ]);

            Log::info('Student profile created during user registration', [
                'individual_guid' => $individual->guid,
                'user_guid' => $user->guid,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create student profile during registration', [
                'user_guid' => $user->guid,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
