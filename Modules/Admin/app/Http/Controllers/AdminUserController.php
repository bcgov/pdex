<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    /**
     * Display a listing of admin users.
     */
    public function index(Request $request): Response
    {
        // Get filter parameters from request
        $search = $request->get('search');
        $roleFilter = $request->get('role');
        $statusFilter = $request->get('status');

        // Get all admin roles
        $adminRoles = [Role::SUPER_ADMIN, Role::APPLICATION_MANAGER, Role::SECURITY_OFFICER, Role::PRIVACY_OFFICER, Role::ADMIN_GUEST];

        // Start building the query
        // $query = User::where('identity_provider', 'idir')
        //     ->where('name', 'ilike', '%' . env('MINISTRY_SHORT_NAME', 'psfs') . '%')
        $query = User::with(['roles:id,name,display_name'])
            ->withTrashed()
            ->orderBy('name');

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', '%' . $search . '%')
                  ->orWhere('email', 'ilike', '%' . $search . '%')
                  ->orWhere('display_name', 'ilike', '%' . $search . '%');
            });
        }

        // Apply status filter
        if ($statusFilter) {
            switch ($statusFilter) {
                case 'active':
                    $query->where('is_active', true)->whereNull('deleted_at');
                    break;
                case 'inactive':
                    $query->where('is_active', false)->whereNull('deleted_at');
                    break;
                case 'deleted':
                    $query->whereNotNull('deleted_at');
                    break;
                default:
                    // If no status filter or "All Users", don't add any additional where clause
                    break;
            }
        }

        $adminUsers = $query->get()->map(function ($user) {
            $user->admin_roles = $user->roles->pluck('name')->toArray();
            return $user;
        });

        // Apply role filter (done after query to filter by role relationships)
        if ($roleFilter) {
            $adminUsers = $adminUsers->filter(function ($user) use ($roleFilter) {
                return in_array($roleFilter, $user->admin_roles);
            })->values(); // Reset array keys
        }

        // Get available admin roles for assignment
        $availableRoles = Role::whereIn('name', $adminRoles)
            ->get(['id', 'name', 'display_name']);

        return Inertia::render('Admin::Users', [
            'users' => $adminUsers,
            'availableRoles' => $availableRoles,
            'canManageUsers' => Auth::user()->canManageAdminUsers(),
            'filters' => [
                'search' => $search,
                'role' => $roleFilter,
                'status' => $statusFilter,
            ],
        ]);
    }

    /**
     * Update user roles.
     */
    public function updateRoles(Request $request, User $user)
    {

        // Debug: Log current user's roles
        \Log::info('Current user roles', [
            'user_id' => Auth::id(),
            'roles' => Auth::user()->roles->pluck('name')->toArray(),
        ]);

        // Debug: Log result of canManageAdminUsers
        \Log::info('canManageAdminUsers', [
            'result' => Auth::user()->canManageAdminUsers()
        ]);

        $this->authorize('manageUsers', $user);

        $data = $request->validate([
            'admin_roles' => 'required|array',
            'admin_roles.*' => 'exists:roles,name',
        ]);

        // Get the role names to ensure they're admin roles
        $roles = Role::whereIn('name', $data['admin_roles'])->get();
        $adminRoles = Role::getAdminRoles();

        foreach ($roles as $role) {
            if (!in_array($role->name, $adminRoles)) {
                return back()->withErrors(['admin_roles' => 'Invalid role selected. Only admin roles are allowed.']);
            }
        }

        // Get role ids
        $roleIds = $roles->pluck('id')->toArray();

        // Sync the roles
        $user->roles()->sync($roleIds);

        return redirect()->route('admin.users.index')
            ->with('success', 'User roles updated successfully.');
    }

    /**
     * Toggle user active status.
     */
    public function toggleStatus(Request $request, User $user)
    {
        $this->authorize('manageUsers', $user);

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';
        
        return redirect()->route('admin.users.index')
            ->with('success', "User has been {$status}.");
    }

    /**
     * Soft delete a user.
     */
    public function destroy(User $user)
    {
        $this->authorize('manageUsers', $user);

        // Prevent self-deletion
        if ($user->id === Auth::id()) {
            return back()->withErrors(['user' => 'You cannot delete your own account.']);
        }

        $user->delete();
        
        return redirect()->route('admin.users.index')
            ->with('success', 'User has been deleted. They can be restored if needed.');
    }

    /**
     * Restore a soft deleted user.
     */
    public function restore($id)
    {
        $this->authorize('manageUsers', Auth::user());

        $user = User::withTrashed()->findOrFail($id);
        $user->restore();
        
        return redirect()->route('admin.users.index')
            ->with('success', 'User has been restored.');
    }

    /**
     * Permanently delete a user.
     */
    public function forceDelete($id)
    {
        $this->authorize('manageUsers', Auth::user());

        $user = User::withTrashed()->findOrFail($id);
        
        // Prevent self-deletion
        if ($user->id === Auth::id()) {
            return back()->withErrors(['user' => 'You cannot permanently delete your own account.']);
        }

        $user->forceDelete();
        
        return redirect()->route('admin.users.index')
            ->with('success', 'User has been permanently deleted.');
    }
}
