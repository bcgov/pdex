<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;
use App\Models\Role;
use Illuminate\Auth\Access\HandlesAuthorization;

class ApplicationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any applications.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            Role::SUPER_ADMIN,
            Role::ADMIN_MANAGER,
            Role::APPLICATION_MANAGER,
            Role::SECURITY_OFFICER,
            Role::PRIVACY_OFFICER
        ]);
    }

    /**
     * Determine whether the user can view the application.
     */
    public function view(User $user, Application $application): bool
    {
        return $user->hasAnyRole([
            Role::SUPER_ADMIN,
            Role::ADMIN_MANAGER,
            Role::APPLICATION_MANAGER,
            Role::SECURITY_OFFICER,
            Role::PRIVACY_OFFICER
        ]);
    }

    /**
     * Determine whether the user can create applications.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            Role::SUPER_ADMIN,
            Role::APPLICATION_MANAGER
        ]);
    }

    /**
     * Determine whether the user can update the application.
     */
    public function update(User $user, Application $application): bool
    {
        return $user->hasAnyRole([
            Role::SUPER_ADMIN,
            Role::APPLICATION_MANAGER,
            Role::SECURITY_OFFICER,
            Role::PRIVACY_OFFICER
        ]);
    }

    /**
     * Determine whether the user can delete the application.
     */
    public function delete(User $user, Application $application): bool
    {
        return $user->hasAnyRole([
            Role::SUPER_ADMIN,
            Role::APPLICATION_MANAGER
        ]);
    }

    /**
     * Determine whether the user can approve/disapprove applications for security.
     */
    public function approveSecurity(User $user, Application $application = null): bool
    {
        // Cannot modify approvals if already finally approved
        if ($application && !$application->canModifyApprovals()) {
            return false;
        }

        return $user->hasAnyRole([
            Role::SUPER_ADMIN,
            Role::SECURITY_OFFICER
        ]);
    }

    /**
     * Determine whether the user can approve/disapprove applications for privacy.
     */
    public function approvePrivacy(User $user, Application $application = null): bool
    {
        // Cannot modify approvals if already finally approved
        if ($application && !$application->canModifyApprovals()) {
            return false;
        }

        return $user->hasAnyRole([
            Role::SUPER_ADMIN,
            Role::PRIVACY_OFFICER
        ]);
    }

    /**
     * Determine whether the user can reject applications (security or privacy).
     */
    public function reject(User $user, Application $application = null): bool
    {
        if ($application && !$application->canModifyApprovals()) {
            return false;
        }

        return $user->hasAnyRole([
            Role::SUPER_ADMIN,
            Role::SECURITY_OFFICER,
            Role::PRIVACY_OFFICER
        ]);
    }

    /**
     * Determine whether the user can activate/deactivate applications.
     */
    public function toggleStatus(User $user, Application $application = null): bool
    {
        return $user->hasAnyRole([
            Role::SUPER_ADMIN,
            Role::APPLICATION_MANAGER,
            Role::SECURITY_OFFICER
        ]);
    }
}
