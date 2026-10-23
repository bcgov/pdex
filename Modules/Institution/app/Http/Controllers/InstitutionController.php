<?php

namespace Modules\Institution\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Application;
use App\Models\ApplicationDataPermission;
use App\Models\Institution;
use App\Models\InstitutionSite;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Institution\Http\Requests\InstitutionStoreRequest;

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
     * Display the current BCeID user's associated institution profile.
     */
    public function showInstitutionProfile(): Response|RedirectResponse
    {
        $this->authorize('accessPortal', Institution::class);

        $user = auth()->user();

        if (!$user->bceid_business_guid) {
            return redirect()->route('institution.dashboard')
                ->with('error', 'Invalid BCeID business GUID.');
        }

        if (!$user->hasRole(Role::INSTITUTION_ADMIN)) {
            abort(403, 'Only institution admins can view institution information.');
        }

        $institution = $user->institution();

        if (!$institution) {
            return redirect()->route('institution.create');
        }

        return Inertia::render('Institution::Show', [
            'institution' => $institution->load('sites'),
        ]);
    }

    /**
     * Show the form for a BCeID user to submit a new institution.
     */
    public function create(): Response|RedirectResponse
    {
        $this->authorize('accessPortal', Institution::class);

        $user = auth()->user();

        if (!$user->bceid_business_guid) {
            return redirect()->route('institution.dashboard')
                ->with('error', 'Invalid BCeID business GUID.');
        }

        if ($user->institution()) {
            return $this->handleExistingInstitution($user);
        }

        return Inertia::render('Institution::Create', [
            'institutionTypes' => Institution::getInstitutionTypes(),
            'regulatingBodies' => InstitutionSite::getRegulatingBodies(),
            'standingStatuses' => InstitutionSite::getStandingStatuses(),
            'economicRegions' => InstitutionSite::getEconomicRegions(),
            'businessGuid' => $user->bceid_business_guid,
        ]);
    }

    /**
     * Store an institution from the BCeID portal.
     */
    public function store(InstitutionStoreRequest $request): RedirectResponse
    {
        $this->authorize('accessPortal', Institution::class);

        $user = auth()->user();

        if (!$user->bceid_business_guid) {
            return redirect()->route('institution.dashboard')
                ->with('error', 'Invalid BCeID business GUID.');
        }

        if ($user->institution()) {
            return $this->handleExistingInstitution($user);
        }

        $this->createSubmittedInstitutionWithSites($request->validated(), $user);
        $this->assignInstitutionAdminRole($user);

        return redirect()->route('institution.profile')
            ->with('success', 'Institution submitted successfully.');
    }

    /**
     * Get user-friendly label for database table names
     */
    private function getTableLabel(string $tableName): string
    {
        $availableTables = ApplicationDataPermission::getAvailableTables();
        return $availableTables[$tableName]['label'] ?? ucfirst(str_replace('_', ' ', $tableName));
    }

    /**
     * Create the inactive institution (Pending Review) and sites for a BCeID portal submission.
     */
    private function createSubmittedInstitutionWithSites(array $validated, User $user): Institution
    {
        $institution = Institution::create([
            'legal_operating_name' => $validated['legal_operating_name'],
            'institution_type' => $validated['institution_type'],
            'dli' => $validated['dli'] ?? null,
            'bceid_business_guid' => $user->bceid_business_guid,
        ]);

        foreach ($validated['sites'] as $site) {
            $institution->sites()->create($this->siteAttributesForSubmission($site));
        }

        return $institution;
    }

    /**
     * Map validated Portal input to site fields.
     */
    private function siteAttributesForSubmission(array $site): array
    {
        return [
            'operating_name' => $site['operating_name'],
            'primary_phone' => $site['primary_phone'],
            'primary_email' => $site['primary_email'],
            'website' => $site['website'] ?? null,
            'regulating_body' => $site['regulating_body'],
            'other_regulating_body' => $site['other_regulating_body'] ?? null,
            'established_date' => $site['established_date'] ?? null,
            'contact_first_name' => $site['contact_first_name'],
            'contact_last_name' => $site['contact_last_name'],
            'contact_email' => $site['contact_email'],
            'contact_phone' => $site['contact_phone'],
            'address_line_1' => $site['address_line_1'],
            'address_line_2' => $site['address_line_2'] ?? null,
            'city' => $site['city'],
            'province_state' => $site['province_state'],
            'country' => $site['country'],
            'postal_code' => $site['postal_code'],
            'standing_status' => $site['standing_status'] ?? null,
            'economic_region' => $site['economic_region'] ?? null,
        ];
    }

    /**
     * Redirect the user to the institution profile page if they already have an existing institution.
     */
    private function handleExistingInstitution(User $user): RedirectResponse
    {
        if (!$user->hasRole(Role::INSTITUTION_ADMIN)) {
            abort(403, 'Only institution admins can submit or view institution information.');
        }

        return redirect()->route('institution.profile');
    }

    /**
     * Assign the Institution Admin role to the user, removing any existing Institution roles.
     */
    private function assignInstitutionAdminRole(User $user): void
    {
        $adminRole = Role::where('name', Role::INSTITUTION_ADMIN)->firstOrFail();
        $currentInstitutionRoles = $user->roles()
            ->whereIn('name', [Role::INSTITUTION_ADMIN, Role::INSTITUTION_USER])
            ->pluck('roles.id');

        if ($currentInstitutionRoles->isNotEmpty()) {
            $user->roles()->detach($currentInstitutionRoles);
        }

        $user->roles()->syncWithoutDetaching([$adminRole->id]);
        $user->load('roles');
    }

}