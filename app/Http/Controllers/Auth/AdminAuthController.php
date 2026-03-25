<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\KeycloakService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Role;

class AdminAuthController extends Controller
{
    private const ADMIN_LOGIN_VIEW = 'Auth/AdminLogin';

    public function __construct(
        private KeycloakService $keycloakService
    ) {}

    /**
     * Show the admin login page.
     */
    public function login(): Response
    {
        // If user is already authenticated and has admin role, redirect to admin dashboard
        if (Auth::check()) {
            $user = Auth::user();
            $adminRoles = ['Super Admin', 'Application Manager', 'Security Officer', 'Privacy Officer', 'Admin Guest'];
            if ($user->hasAnyRole($adminRoles)) {
                return redirect('/admin');
            }
            
            // If authenticated but not admin, logout and show admin login
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }

        return Inertia::render(self::ADMIN_LOGIN_VIEW, [
            'loginAttempt' => false,
            'hasAccess' => false,
            'status' => session('status'),
        ]);
    }

    /**
     * Redirect to Keycloak for admin authentication.
     */
    public function redirectToKeycloak(Request $request): RedirectResponse|Response
    {
        $provider = new Keycloak([
            'authServerUrl' => config('auth.keycloak.auth_server_url'),
            'realm' => config('auth.keycloak.realm'),
            'clientId' => config('auth.keycloak.client_id'),
            'clientSecret' => config('auth.keycloak.client_secret'),
            'redirectUri' => config('auth.keycloak.admin_redirect_uri'),
            'scopes' => 'openid profile email',
        ]);

        if (!$request->has('code')) {
            // If we don't have an authorization code then get one
            $authUrl = $provider->getAuthorizationUrl([
                'scope' => 'openid profile email',
            ]);

            $request->session()->put('oauth2state', $provider->getState());
            
            Log::info('Redirecting to Keycloak for admin authentication', [
                'auth_url' => $authUrl,
            ]);

            // Add IDP hint for IDIR (government staff)
            return redirect($authUrl . '&kc_idp_hint=idir');

        } elseif (!$request->has('state') || ($request->state !== $request->session()->get('oauth2state'))) {
            // Invalid state - security check failed
            Log::warning('Admin OAuth state mismatch', [
                'request_state' => $request->state,
                'session_state' => $request->session()->get('oauth2state'),
            ]);
            
            $request->session()->forget('oauth2state');
            
            return Inertia::render(self::ADMIN_LOGIN_VIEW, [
                'loginAttempt' => true,
                'hasAccess' => false,
                'status' => 'Authentication failed. Please try again.',
            ]);
                
        } else {
            // We have a valid code, exchange it for a token
            try {
                $token = $provider->getAccessToken('authorization_code', [
                    'code' => $request->code,
                ]);
            } catch (\Exception $e) {
                Log::error('Admin failed to get access token', [
                    'error' => $e->getMessage(),
                ]);
                
                return Inertia::render(self::ADMIN_LOGIN_VIEW, [
                    'loginAttempt' => true,
                    'hasAccess' => false,
                    'status' => 'Failed to authenticate. Please try again.',
                ]);
            }

            // Get user profile from Keycloak
            try {
                $providerUser = $provider->getResourceOwner($token);
                $providerUser = $providerUser->toArray();

                Log::info('Admin user authenticated with Keycloak', [
                    'user_email' => $providerUser['email'] ?? 'unknown',
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to get admin resource owner', [
                    'error' => $e->getMessage(),
                ]);
                
                return Inertia::render(self::ADMIN_LOGIN_VIEW, [
                    'loginAttempt' => true,
                    'hasAccess' => false,
                    'status' => 'Failed to get user information. Please try again.',
                ]);
            }

            // Find user (admin users should already exist, but create if needed)
            $user = null;
            if (isset($providerUser['idir_user_guid'])) {
                $user = User::where('idir_user_guid', $providerUser['idir_user_guid'])->first();

                if($token)
                {
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
                }
            }

            // If user doesn't exist, create them with Admin Guest role
            if (!$user) {
                $user = $this->createAdminUser($providerUser);
                
                Log::info('New admin user created', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'idir_user_guid' => $providerUser['idir_user_guid'] ?? 'unknown',
                ]);
            }

            // Check if user has admin roles (including Admin Guest)
            $adminRoles = [Role::SUPER_ADMIN, Role::ADMIN_MANAGER, Role::APPLICATION_MANAGER, Role::SECURITY_OFFICER, Role::PRIVACY_OFFICER];
            if (!$user->hasAnyRole($adminRoles)) {
                Log::warning('Non-admin user attempted admin login', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name')->toArray(),
                ]);

                return Inertia::render(self::ADMIN_LOGIN_VIEW, [
                    'loginAttempt' => true,
                    'hasAccess' => false,
                    'status' => 'Access denied. You do not have administrative privileges.',
                ]);
            }

            // Log the admin user in
            Auth::login($user);
            
            $request->session()->forget('oauth2state');
            
            Log::info('Admin user logged in successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->toArray(),
            ]);

            return redirect()->route('admin.dashboard');
        }
    }

    /**
     * Log the admin user out.
     */
    public function adminLogout(Request $request): RedirectResponse
    {
        $user = Auth::user();
        
        if ($user) {
            Log::info('Admin user logging out', [
                'user_id' => $user->id,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->toArray(),
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->flush(); // Clear all session data

        return redirect('/login')->with('message', 'Admin logout successful. Please log in again.');
    }

    /**
     * Create new admin user with Admin Guest role.
     */
    private function createAdminUser(array $providerUser): User
    {
        $user = new User();
        $user->guid = Str::orderedUuid()->getHex();
        $user->name = Str::upper($providerUser['name'] ?? '');
        $user->first_name = Str::upper($providerUser['given_name'] ?? '');
        $user->last_name = Str::upper($providerUser['family_name'] ?? '');
        $user->email = Str::lower($providerUser['email'] ?? '');
        $user->identity_provider = 'idir';
        
        // Store Keycloak information
        if (isset($providerUser['sub'])) {
            $user->keycloak_id = $providerUser['sub'];
        }
        
        // Set IDIR-specific fields
        $user->idir_user_guid = $providerUser['idir_user_guid'] ?? null;
        $user->idir_username = $providerUser['idir_username'] ?? null;
        
        // Set as inactive initially for admin users
        $user->is_active = false;
        
        $user->save();

        // Assign Admin Guest role
        $adminGuestRole = Role::where('name', Role::ADMIN_GUEST)->first();
        if ($adminGuestRole) {
            $user->roles()->attach($adminGuestRole);
            
            Log::info('Admin Guest role assigned to new user', [
                'user_id' => $user->id,
                'role' => Role::ADMIN_GUEST,
            ]);
        }

        return $user;
    }
}
