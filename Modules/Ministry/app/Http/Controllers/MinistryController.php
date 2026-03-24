<?php

namespace Modules\Ministry\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationDataPermission;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MinistryController extends Controller
{
    /**
     * Display the ministry dashboard with available applications.
     */
    public function index()
    {
        // Get applications that are enabled for IDIR (Ministry users)
        // and are either active or offline, and have both security and privacy approvals
        $applications = Application::where('idir_enabled', true)
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
                'idir_redirect_url',
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
                    'redirect_url' => $app->idir_redirect_url,
                    'status' => $app->status,
                    'info_label' => $app->info_label,
                    'info_url' => $app->info_url,
                    'alert_message' => $app->status === 'offline' 
                        ? $app->offline_alert_message 
                        : $app->active_alert_message,
                    'data_permission_groups' => array_values($permissionGroups),
                ];
            });

        return Inertia::render('Ministry::Dashboard', [
            'applications' => $applications,
            'user' => auth()->user()->only(['name', 'email']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('ministry::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('ministry::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('ministry::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}

    /**
     * Get user-friendly label for database table names
     */
    private function getTableLabel(string $tableName): string
    {
        $availableTables = ApplicationDataPermission::getAvailableTables();
        return $availableTables[$tableName]['label'] ?? ucfirst(str_replace('_', ' ', $tableName));
    }
}
