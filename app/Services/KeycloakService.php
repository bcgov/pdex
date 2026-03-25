<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;

class KeycloakService
{
    private Keycloak $provider;

    public function __construct()
    {
        $this->provider = new Keycloak([
            'authServerUrl' => config('auth.keycloak.auth_server_url'),
            'realm' => config('auth.keycloak.realm'),
            'clientId' => config('auth.keycloak.client_id'),
            'clientSecret' => config('auth.keycloak.client_secret'),
            'redirectUri' => config('auth.keycloak.redirect_uri'),
        ]);
    }

    /**
     * Get the authorization URL for Keycloak login.
     */
    public function getAuthorizationUrl(?string $idp = null): string
    {
        $options = [
            'scope' => 'openid profile email',
        ];

        // Add identity provider hint for authentication
        if ($idp) {
            $options['kc_idp_hint'] = $idp;
        }

        $authUrl = $this->provider->getAuthorizationUrl($options);

        // Store the state in session for verification
        session(['oauth2state' => $this->provider->getState()]);

        return $authUrl;
    }

    /**
     * Handle the callback from Keycloak and authenticate the user.
     */
    public function handleCallback(string $code, string $state): User
    {
        // Verify state
        if (empty($state) || ($state !== session('oauth2state'))) {
            session()->forget('oauth2state');
            throw new \Exception('Invalid state parameter');
        }

        session()->forget('oauth2state');

        // Get access token
        $token = $this->provider->getAccessToken('authorization_code', [
            'code' => $code,
        ]);

        // Get user details from Keycloak
        $keycloakUser = $this->provider->getResourceOwner($token);
        $userData = $keycloakUser->toArray();

        Log::info('Keycloak user data received', $userData);

        // Find or create user
        $user = $this->findOrCreateUser($userData);

        // Assign roles based on Keycloak claims
        $this->assignRoles($user, $userData);

        // Log the user in
        Auth::login($user);

        return $user;
    }

    /**
     * Find or create user based on Keycloak data.
     */
    private function findOrCreateUser(array $userData): User
    {
        $keycloakId = $userData['sub'] ?? null;
        $email = $userData['email'] ?? null;

        if (!$keycloakId || !$email) {
            throw new \Exception('Missing required user data from Keycloak');
        }

        // Try to find existing user by Keycloak ID first
        $user = User::where('keycloak_id', $keycloakId)->first();

        if (!$user) {
            // Try to find by email
            $user = User::where('email', $email)->first();
        }

        if (!$user) {
            // Create new user
            $user = new User();
        }

        // Update user data following
        $user->keycloak_id = $keycloakId;
        $user->email = $email;
        $user->name = $userData['name'] ?? $userData['preferred_username'] ?? $email;
        $user->display_name = $userData['display_name'] ?? $userData['name'] ?? null;
        $user->given_name = $userData['given_name'] ?? null;
        $user->family_name = $userData['family_name'] ?? null;

        // Identity provider detection
        $identityProvider = $this->detectIdentityProvider($userData);
        $user->identity_provider = $identityProvider;

        // Set appropriate GUID based on identity provider
        switch ($identityProvider) {
            case 'idir':
                $user->idir_user_guid = $userData['idir_user_guid'] ?? $keycloakId;
                $user->user_guid = $user->idir_user_guid;
                break;
            case 'bceid':
                $user->bceid_user_guid = $userData['bceid_user_guid'] ?? $keycloakId;
                $user->user_guid = $user->bceid_user_guid;
                break;
            case 'bcsc':
                $user->bcsc_user_guid = $userData['bcsc_user_guid'] ?? $keycloakId;
                $user->user_guid = $user->bcsc_user_guid;
                break;
            default:
                Log::warning('Unknown identity provider', ['identity_provider' => $identityProvider]);
                $user->user_guid = $keycloakId;
                break;
        }

        $user->organization = $userData['organization'] ?? null;
        $user->is_active = true;

        $user->save();

        return $user;
    }

    /**
     * Detect identity provider from Keycloak claims
     */
    private function detectIdentityProvider(array $userData): string
    {
        // Check for identity provider claims in the token
        if (isset($userData['identity_provider'])) {
            return strtolower($userData['identity_provider']);
        }

        if (isset($userData['idp'])) {
            return strtolower($userData['idp']);
        }

        // Fallback: detect from email domain or other indicators
        $email = $userData['email'] ?? '';
        if (str_contains($email, '@gov.bc.ca')) {
            return 'idir';
        }

        // Default to bceid if we can't determine
        return 'bceid';
    }

    /**
     * Assign roles to user based on Keycloak claims
     */
    private function assignRoles(User $user, array $userData): void
    {
        // Remove existing roles
        $user->roles()->detach();

        // Get roles from Keycloak token
        $keycloakRoles = $userData['realm_access']['roles'] ?? [];
        $resourceRoles = $userData['resource_access'][config('auth.keycloak.client_id')]['roles'] ?? [];
        
        $allRoles = array_merge($keycloakRoles, $resourceRoles);

        Log::info('Assigning roles to user', [
            'user_id' => $user->id,
            'keycloak_roles' => $allRoles,
        ]);

        // Map Keycloak roles to our application roles
        $roleMapping = [
            'Ministry_Admin' => Role::MINISTRY_ADMIN,
            'Ministry_User' => Role::MINISTRY_USER,
            'Institution_User' => Role::INSTITUTION_USER,
            'Institution_Admin' => Role::INSTITUTION_ADMIN,
            'Student' => Role::STUDENT,

            'Super_Admin' => Role::SUPER_ADMIN,
            'Application_Manager' => Role::APPLICATION_MANAGER,
            'Security_Officer' => Role::SECURITY_OFFICER,
            'Privacy_Officer' => Role::PRIVACY_OFFICER
        ];

        foreach ($allRoles as $keycloakRole) {
            if (isset($roleMapping[$keycloakRole])) {
                $role = Role::where('name', $roleMapping[$keycloakRole])->first();
                if ($role) {
                    $user->roles()->attach($role);
                    Log::info('Role assigned', [
                        'user_id' => $user->id,
                        'role' => $role->name,
                    ]);
                }
            }
        }

        // If no roles assigned, assign default based on identity provider
        if ($user->roles()->count() === 0) {
            $defaultRole = match ($user->identity_provider) {
                'idir' => Role::MINISTRY_ADMIN,
                'bceid' => Role::INSTITUTION_USER,
                'bcsc' => Role::STUDENT,
                default => Role::STUDENT,
            };

            $role = Role::where('name', $defaultRole)->first();
            if ($role) {
                $user->roles()->attach($role);
                Log::info('Default role assigned', [
                    'user_id' => $user->id,
                    'role' => $role->name,
                    'reason' => 'no_keycloak_roles',
                ]);
            }
        }
    }

    /**
     * Get logout URL.
     */
    public function getLogoutUrl(?string $redirectUrl = null): string
    {
        $logoutUrl = config('auth.keycloak.auth_server_url') . 
                    '/realms/' . config('auth.keycloak.realm') . '/protocol/openid-connect/logout';
        
        return $logoutUrl . '?' . http_build_query([
            'post_logout_redirect_uri' => $redirectUrl ?? url('/'),
            'client_id' => config('auth.keycloak.client_id'),
        ]);
    }
}
