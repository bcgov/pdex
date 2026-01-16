<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ApplicationController extends Controller
{
    private const ERROR_INTERNAL_SERVER = 'Internal Server Error';

    /**
     * Get all applications (with permission filtering)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $tokenData = $request->input('token_data');
            $registeredApp = $this->getRegisteredApplication($tokenData['sub'] ?? null);

            if (!$registeredApp) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'Application not registered for API access'
                ], 403);
            }

            // Check if this app has permission to access applications data
            if (!$this->hasPermission($registeredApp->id, 'applications', 'read')) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'Insufficient permissions to access applications data'
                ], 403);
            }

            $applications = Application::select($this->getAllowedFields($registeredApp->id, 'applications'))
                ->get();

            return response()->json([
                'data' => $applications,
                'meta' => [
                    'count' => $applications->count(),
                    'registered_app' => $registeredApp->name,
                    'permissions' => $this->getAppPermissions($registeredApp->id)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('API Applications index error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => self::ERROR_INTERNAL_SERVER,
                'message' => 'An error occurred while fetching applications'
            ], 500);
        }
    }

    /**
     * Get a specific application by ID
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $tokenData = $request->input('token_data');
            $registeredApp = $this->getRegisteredApplication($tokenData['sub'] ?? null);

            if (!$registeredApp) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'Application not registered for API access'
                ], 403);
            }

            if (!$this->hasPermission($registeredApp->id, 'applications', 'read')) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'Insufficient permissions to access application data'
                ], 403);
            }

            $application = Application::select($this->getAllowedFields($registeredApp->id, 'applications'))
                ->where('id', $id)
                ->first();

            if (!$application) {
                return response()->json([
                    'error' => 'Not Found',
                    'message' => 'Application not found'
                ], 404);
            }

            return response()->json([
                'data' => $application,
                'meta' => [
                    'registered_app' => $registeredApp->name,
                    'permissions' => $this->getAppPermissions($registeredApp->id)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('API Application show error', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => self::ERROR_INTERNAL_SERVER,
                'message' => 'An error occurred while fetching the application'
            ], 500);
        }
    }

    /**
     * Register an application for API access
     */
    public function registerApp(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'application_id' => 'required|integer|exists:applications,id'
            ]);

            $tokenData = $request->input('token_data');
            $sub = $tokenData['sub'] ?? null;
            $applicationId = $request->input('application_id');

            if (!$sub) {
                return response()->json([
                    'error' => 'Bad Request',
                    'message' => 'Invalid token: missing subject claim'
                ], 400);
            }

            $application = Application::find($applicationId);
            
            if (!$application) {
                return response()->json([
                    'error' => 'Not Found',
                    'message' => 'Application not found'
                ], 404);
            }

            // Check if application is already registered
            if (!empty($application->client_id)) {
                return response()->json([
                    'error' => 'Conflict',
                    'message' => 'Application is already registered for API access',
                    'data' => [
                        'application_id' => $application->id,
                        'application_name' => $application->name,
                        'registered_at' => $application->updated_at
                    ]
                ], 409);
            }

            // Register the application
            $application->client_id = $sub;
            $application->save();

            Log::info('Application registered for API access', [
                'application_id' => $application->id,
                'application_name' => $application->name,
                'client_id' => $sub
            ]);

            return response()->json([
                'message' => 'Application successfully registered for API access',
                'data' => [
                    'application_id' => $application->id,
                    'application_name' => $application->name,
                    'client_id' => $sub,
                    'registered_at' => $application->updated_at->toISOString()
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation Error',
                'message' => 'Invalid request data',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('API Application registration error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => self::ERROR_INTERNAL_SERVER,
                'message' => 'An error occurred during application registration'
            ], 500);
        }
    }

    /**
     * Get the registered application for the current token
     */
    private function getRegisteredApplication(?string $sub): ?Application
    {
        if (!$sub) {
            return null;
        }

        return Application::where('client_id', $sub)->first();
    }

    /**
     * Check if the registered application has a specific permission
     */
    private function hasPermission(int $appId, string $tableName, string $action): bool
    {
        $column = $action === 'read' ? 'can_read' : 'can_write';
        
        return \DB::table('application_api_permissions')
            ->where('application_id', $appId)
            ->where('table_name', $tableName)
            ->where($column, true)
            ->exists();
    }

    /**
     * Get allowed fields for a specific table based on application permissions
     */
    private function getAllowedFields(int $appId, string $tableName): array
    {
        $permissions = \DB::table('application_api_permissions')
            ->where('application_id', $appId)
            ->where('table_name', $tableName)
            ->where('can_read', true)
            ->pluck('column_name')
            ->toArray();

        // If no specific field permissions, return basic fields
        if (empty($permissions)) {
            return $this->getBasicFields($tableName);
        }

        // Always include ID field if not already present
        if (!in_array('id', $permissions)) {
            array_unshift($permissions, 'id');
        }

        return $permissions;
    }

    /**
     * Get basic fields for a table when no specific permissions are set
     */
    private function getBasicFields(string $tableName): array
    {
        $basicFields = [
            'applications' => ['id', 'name', 'status', 'created_at'],
            'institutions' => ['id', 'legal_operating_name', 'status', 'created_at'],
            'students' => ['id', 'first_name', 'last_name', 'created_at']
        ];

        return $basicFields[$tableName] ?? ['id', 'created_at'];
    }

    /**
     * Get all permissions for the registered application
     */
    private function getAppPermissions(int $appId): array
    {
        return \DB::table('application_api_permissions')
            ->where('application_id', $appId)
            ->select('table_name', 'column_name', 'can_read', 'can_write', 'display_name')
            ->get()
            ->groupBy('table_name')
            ->map(function ($permissions) {
                return [
                    'readable_fields' => $permissions->where('can_read', true)->pluck('column_name')->values(),
                    'writable_fields' => $permissions->where('can_write', true)->pluck('column_name')->values(),
                    'field_details' => $permissions->map(function ($perm) {
                        return [
                            'column' => $perm->column_name,
                            'display_name' => $perm->display_name,
                            'can_read' => $perm->can_read,
                            'can_write' => $perm->can_write
                        ];
                    })->values()
                ];
            })
            ->toArray();
    }
}