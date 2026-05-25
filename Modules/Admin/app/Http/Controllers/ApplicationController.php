<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationDataPermission;
use App\Models\ApplicationIndividualPermission;
use App\Models\ApplicationApiPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Modules\Admin\Events\ApplicationCreated;
use Modules\Admin\Events\ApplicationUpdated;
use Modules\Admin\Events\ApplicationSecurityApprovalChanged;
use Modules\Admin\Events\ApplicationPrivacyApprovalChanged;
use Modules\Admin\Events\ApplicationStatusToggled;
use Modules\Admin\Http\Requests\ApplicationStoreRequest;
use Modules\Admin\Http\Requests\ApplicationUpdateRequest;
use Modules\Admin\Http\Requests\ApplicationSecurityApprovalRequest;
use Modules\Admin\Http\Requests\ApplicationPrivacyApprovalRequest;
use Modules\Admin\Http\Requests\ApplicationManagerUpdateRequest;

class ApplicationController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Application::class, 'application');
    }

    public function index(Request $request)
    {
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        
        // Define allowed sort fields to prevent SQL injection
        $allowedSortFields = [
            'name',
            'contact_name',
            'contact_email',
            'security_approval_status', 
            'privacy_approval_status',
            'status',
            'created_at'
        ];
        
        // Validate sort field
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at';
        }
        
        // Validate sort direction
        if (!in_array(strtolower($sortDirection), ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }
        
        $query = Application::with([
                'securityApprover:id,name,email',
                'privacyApprover:id,name,email'
            ])
            ->withTrashed(); // Include soft deleted records
            
        // Handle contact sorting (combine contact_name and contact_email)
        if ($sortField === 'contact_name' || $sortField === 'contact_email') {
            $query->orderBy('contact_name', $sortDirection)
                  ->orderBy('contact_email', $sortDirection);
        } else {
            $query->orderBy($sortField, $sortDirection);
        }
        
        $applications = $query->get();

        $userCanCreate = Auth::user()->can('create', Application::class);

        return Inertia::render('Admin::Applications', [
            'applications' => $applications,
            'userCanCreate' => $userCanCreate,
            'filters' => [
                'sort' => $sortField,
                'direction' => $sortDirection,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin::ApplicationsCreate');
    }

    public function store(ApplicationStoreRequest $request)
    {
        $data = $request->validated();

        // Generate API credentials
        $data['api_key'] = 'pdex_' . Str::random(32);
        $data['api_secret'] = Str::random(64);
        
        // Set default status if not provided
        if (!isset($data['status'])) {
            $data['status'] = 'inactive';
        }
        
        $data['security_approval_status'] = 'pending';
        $data['privacy_approval_status'] = 'pending';

        $application = Application::create($data);

        // Dispatch event
        ApplicationCreated::dispatch($application);

        return redirect()->route('admin.applications.index')
            ->with('success', 'Application created successfully. It requires security and privacy approval before activation.');
    }

    public function edit(Application $application)
    {
        // Load both individual and API permissions
        $individualPermissions = $application->individualPermissions()->get();
        $apiPermissions = $application->apiPermissions()->get();
        
        // Combine permissions for backward compatibility with frontend
        $combinedPermissions = $individualPermissions->merge($apiPermissions);
        
        return Inertia::render('Admin::ApplicationsEdit', [
            'application' => $application->load([
                'securityApprover:id,name',
                'privacyApprover:id,name'
            ])->setRelation('data_permissions', $combinedPermissions),
            'auth' => [
                'user' => auth()->user()->load('roles'),
            ],
            'availableDataTables' => ApplicationIndividualPermission::getIndividualTables(),
            'apiAccessTables' => ApplicationApiPermission::getApiAccessTables(),
        ]);
    }

    public function update(ApplicationUpdateRequest $request, Application $application)
    {
        try {
            $originalData = $application->toArray();
            $data = $request->validated();

            // Extract data permissions from the validated data
            $dataPermissions = $data['data_permissions'] ?? [];
            unset($data['data_permissions']);

            $application->update($data);

            // Handle data permissions - separate individual and API permissions
            if (isset($dataPermissions)) {
                // Delete existing permissions for this application
                $application->individualPermissions()->delete();
                $application->apiPermissions()->delete();

                // Separate permissions by source (Data Access vs API Access)
                $individualPermissions = [];
                $apiPermissions = [];

                foreach ($dataPermissions as $permission) {
                    if ($permission['can_read'] || $permission['can_write']) {
                        // Check if this permission came from the Data Access Permissions section
                        // (these use Required/Optional and have is_required field)
                        if (isset($permission['is_required'])) {
                            $individualPermissions[] = $permission;
                        } else {
                            // This came from API Access Permissions section (Read/Write system)
                            $apiPermissions[] = $permission;
                        }
                    }
                }

                // Create individual permissions (Data Access Permissions)
                foreach ($individualPermissions as $permission) {
                    $application->individualPermissions()->create([
                        'table_name' => $permission['table_name'],
                        'column_name' => $permission['column_name'],
                        'destination_field' => $permission['destination_field'] ?: $permission['column_name'],
                        'display_name' => $permission['display_name'] ?? ucwords(str_replace('_', ' ', $permission['column_name'])),
                        'can_read' => $permission['can_read'] ?? false,
                        'can_write' => $permission['can_write'] ?? false,
                        'is_required' => $permission['is_required'] ?? false,
                    ]);
                }

                // Create API permissions (API Access Permissions)
                foreach ($apiPermissions as $permission) {
                    $application->apiPermissions()->create([
                        'table_name' => $permission['table_name'],
                        'column_name' => $permission['column_name'],
                        'display_name' => $permission['display_name'] ?? ucwords(str_replace('_', ' ', $permission['column_name'])),
                        'can_read' => $permission['can_read'] ?? false,
                        'can_write' => $permission['can_write'] ?? false,
                    ]);
                }
            }

            // Get the changes that were made
            $changes = array_diff_assoc($data, $originalData);

            // Dispatch event
            ApplicationUpdated::dispatch($application, $changes);

            return back()
                ->with('success', 'Application updated successfully.');
                
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Application update failed: ' . $e->getMessage(), [
                'application_id' => $application->id,
                'user_id' => auth()->id(),
                'error' => $e->getTraceAsString()
            ]);

            return back()
                ->with('error', 'Failed to update application. Please try again or contact support if the problem persists.')
                ->withInput();
        }
    }

    public function approverUpdate(ApplicationManagerUpdateRequest $request, Application $application)
    {
        $data = $request->validated();

        $application->update($data);

        return back()->with('success', 'Approver changes saved successfully.');
    }

    public function destroy(Application $application)
    {
        $application->delete(); // This will be a soft delete
        
        return redirect()->route('admin.applications.index')
            ->with('success', 'Application deleted successfully. You can restore it if needed.');
    }

    public function restore($id)
    {
        $application = Application::withTrashed()->findOrFail($id);
        $this->authorize('update', $application);
        
        $application->restore();
        
        return redirect()->route('admin.applications.index')
            ->with('success', 'Application restored successfully.');
    }

    public function forceDelete($id)
    {
        $application = Application::withTrashed()->findOrFail($id);
        $this->authorize('delete', $application);
        
        $application->forceDelete(); // Permanent delete
        
        return redirect()->route('admin.applications.index')
            ->with('success', 'Application permanently deleted.');
    }

    /**
     * Handle security approval for an application.
     */
    public function securityApproval(ApplicationSecurityApprovalRequest $request, Application $application)
    {
        $previousStatus = $application->security_approval_status;
        $data = $request->validated();

        $data['security_approved_at'] = now();
        $data['security_approved_by'] = auth()->id();

        $application->update($data);

        // Dispatch event
        ApplicationSecurityApprovalChanged::dispatch(
            $application,
            auth()->user(),
            $previousStatus,
            $data['security_approval_status']
        );

        return redirect()->back()
            ->with('success', 'Security approval updated successfully.');
    }

    /**
     * Handle privacy approval for an application.
     */
    public function privacyApproval(ApplicationPrivacyApprovalRequest $request, Application $application)
    {
        $previousStatus = $application->privacy_approval_status;
        $data = $request->validated();

        $data['privacy_approved_at'] = now();
        $data['privacy_approved_by'] = auth()->id();

        $application->update($data);

        // Dispatch event
        ApplicationPrivacyApprovalChanged::dispatch(
            $application,
            auth()->user(),
            $previousStatus,
            $data['privacy_approval_status']
        );

        return redirect()->back()
            ->with('success', 'Privacy approval updated successfully.');
    }

    /**
     * Reject both security and privacy approvals for an application.
     */
    public function reject(Request $request, Application $application)
    {
        $this->authorize('reject', $application);

        $data = $request->validate([
            'rejection_notes' => 'nullable|string|max:2000',
        ]);

        $notes = $data['rejection_notes'] ?? null;
        $userId = auth()->id();
        $now = now();

        $updates = [];

        if ($application->security_approval_status !== 'approved' || !$application->isFinallyApproved()) {
            $prevSecurity = $application->security_approval_status;
            $updates['security_approval_status'] = 'rejected';
            $updates['security_approval_notes'] = $notes;
            $updates['security_approved_at'] = $now;
            $updates['security_approved_by'] = $userId;
        }

        if ($application->privacy_approval_status !== 'approved' || !$application->isFinallyApproved()) {
            $prevPrivacy = $application->privacy_approval_status;
            $updates['privacy_approval_status'] = 'rejected';
            $updates['privacy_approval_notes'] = $notes;
            $updates['privacy_approved_at'] = $now;
            $updates['privacy_approved_by'] = $userId;
        }

        $application->update($updates);

        return redirect()->back()
            ->with('success', 'Application rejected successfully.');
    }

    /**
     * Handle manager update for an application.
     */
    public function managerUpdate(Request $request, Application $application)
    {
        $this->authorize('update', $application);

        $data = $request->validate([
            'active_alert_message' => 'nullable|string|max:1000',
            'offline_alert_message' => 'nullable|string|max:1000',
            'offline_start_time' => 'nullable|date',
            'offline_end_time' => 'nullable|date|after:offline_start_time',
        ]);

        $application->update($data);

        return redirect()->route('admin.applications.edit', $application)
            ->with('success', 'Application settings updated successfully.');
    }

    public function toggleStatus(Request $request, Application $application)
    {
        $this->authorize('toggleStatus', $application);

        $previousStatus = $application->status;

        // Cycle through statuses: inactive -> active -> offline -> inactive
        $statusCycle = [
            'inactive' => 'active',
            'active' => 'offline', 
            'offline' => 'inactive'
        ];
        
        $newStatus = $statusCycle[$application->status] ?? 'active';

        // Check if trying to activate without approvals
        if ($newStatus === 'active' && !$application->isFinallyApproved()) {
            return back()->withErrors(['status' => 'Application must have both security and privacy approvals before it can be activated.']);
        }

        $application->update(['status' => $newStatus]);

        // Dispatch event
        ApplicationStatusToggled::dispatch($application, $previousStatus, $newStatus);

        return redirect()->route('admin.applications.index')
            ->with('success', "Application status changed to {$newStatus}.");
    }
}
