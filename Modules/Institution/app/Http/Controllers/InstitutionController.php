<?php

namespace Modules\Institution\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Application;
use App\Models\ApplicationDataPermission;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InstitutionController extends Controller
{
    /**
     * Display the institution dashboard with available applications.
     */
    public function index(): Response
    {
        // Check if user can access institution portal
        $this->authorize('accessPortal', \App\Models\Institution::class);

        // Get applications that are enabled for BCeID (Institution users)
        // and are either active or offline, and have both security and privacy approvals
        $applications = Application::where('bceid_enabled', true)
            ->whereIn('status', ['active', 'offline'])
            ->where('security_approval_status', 'approved')
            ->where('privacy_approval_status', 'approved')
            ->with('dataPermissions')
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('name', 'asc')
            ->select([
                'id',
                'guid',
                'name',
                'description',
                'bceid_redirect_url',
                'status',
                'active_alert_message',
                'offline_alert_message',
                'offline_start_time',
                'offline_end_time',
                'info_label',
                'info_url'
            ])
            ->get()
            ->map(function ($app) {
                // Group data permissions by table for better display
                $permissionGroups = [];
                foreach ($app->dataPermissions as $permission) {
                    $tableName = $permission->table_name;
                    if (!isset($permissionGroups[$tableName])) {
                        $permissionGroups[$tableName] = [
                            'table_name' => $tableName,
                            'table_label' => $this->getTableLabel($tableName),
                            'permissions' => []
                        ];
                    }
                    $permissionGroups[$tableName]['permissions'][] = [
                        'column_name' => $permission->column_name,
                        'display_name' => $permission->display_name,
                        'can_read' => $permission->can_read,
                        'can_write' => $permission->can_write,
                    ];
                }

                return [
                    'id' => $app->id,
                    'guid' => $app->guid,
                    'name' => $app->name,
                    'description' => $app->description,
                    'redirect_url' => $app->bceid_redirect_url,
                    'status' => $app->status,
                    'info_label' => $app->info_label,
                    'info_url' => $app->info_url,
                    'alert_message' => $app->status === 'offline' 
                        ? $app->offline_alert_message 
                        : $app->active_alert_message,
                    'data_permission_groups' => array_values($permissionGroups),
                ];
            });

        return Inertia::render('Institution::Dashboard', [
            'applications' => $applications,
            'user' => auth()->user()->only(['name', 'email', 'organization']),
        ]);
    }

    /**
     * Display institution applications.
     */
    public function applications(): Response
    {
        // Check if user can access institution portal
        $this->authorize('accessPortal', \App\Models\Institution::class);

        $user = auth()->user();
        
        return Inertia::render('Institution::Applications', [
            'user' => $user,
        ]);
    }

    /**
     * Display admin page.
     */
    public function admin(): Response
    {
        // Check if user can access institution admin features
        $this->authorize('accessAdmin', \App\Models\Institution::class);

        $user = auth()->user();
        
        return Inertia::render('Institution::Admin', [
            'user' => $user,
        ]);
    }

    /**
     * Display settings page.
     */
    public function settings(): Response
    {
        // Check if user can view their own institution settings
        $this->authorize('viewOwnSettings', \App\Models\Institution::class);

        $user = auth()->user();

        // Get the institution associated with the current user if it exists
        $institution = $user->institution();

        if (!$institution) {
            abort(403, 'No institution associated with this user.');
        }

        return Inertia::render('Institution::Settings', [
            'user' => $user,
            'institution' => $institution,
            'institutionTypes' => Institution::getInstitutionTypes(),
        ]);
    }

    /**
     * Update the current institution's settings.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $institution = $user->institution();
        
        if (!$institution) {
            abort(403, 'No institution associated with this user.');
        }

        // Check if user can update their own institution
        $this->authorize('updateOwn', $institution);

        $validated = $request->validate([
            'legal_operating_name' => 'sometimes|required|string|max:255',
            'institution_type' => 'sometimes|required|string|in:' . implode(',', Institution::getInstitutionTypes()),
            'dli' => 'nullable|string|max:20',
            'bceid_business_guid' => 'nullable|string|max:255',
            'active_status' => 'boolean',
        ]);

        $institution->update($validated);

        return redirect()->route('institution.settings.index')
            ->with('success', 'Institution settings updated successfully.');
    }

    /**
     * Get user-friendly label for database table names
     */
    private function getTableLabel(string $tableName): string
    {
        $availableTables = ApplicationDataPermission::getAvailableTables();
        return $availableTables[$tableName]['label'] ?? ucfirst(str_replace('_', ' ', $tableName));
    }
}