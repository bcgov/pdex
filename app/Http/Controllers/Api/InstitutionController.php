<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\HandlesApiResourcePermissions;
use App\Models\Institution;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class InstitutionController extends Controller
{
    use HandlesApiResourcePermissions;

    /**
     * Get all institutions with permission filtering
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $tokenData = $request->input('token_data');
            $registeredApp = $this->getRegisteredApplication($this->resolveClientIdentifier($tokenData));

            if (!$registeredApp) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'Application not registered for API access'
                ], 403);
            }

            // Check if this app has permission to read institutions data
            if (!$this->hasPermission($registeredApp->id, 'institutions', 'read')) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'Insufficient permissions to access institutions data'
                ], 403);
            }

            $allowedFields = $this->getAllowedFields($registeredApp->id, 'institutions');
            
            $institutions = Institution::select($allowedFields)->get();

            return response()->json([
                'data' => $institutions,
                'meta' => [
                    'count' => $institutions->count(),
                    'registered_app' => $registeredApp->name,
                    'allowed_fields' => $allowedFields,
                    'permissions' => $this->getAppPermissions($registeredApp->id, 'institutions')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('API Institutions index error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Internal Server Error',
                'message' => 'An error occurred while fetching institutions'
            ], 500);
        }
    }

    /**
     * Get a specific institution by ID
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $tokenData = $request->input('token_data');
            $registeredApp = $this->getRegisteredApplication($this->resolveClientIdentifier($tokenData));

            if (!$registeredApp) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'Application not registered for API access'
                ], 403);
            }

            if (!$this->hasPermission($registeredApp->id, 'institutions', 'read')) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'Insufficient permissions to access institution data'
                ], 403);
            }

            $allowedFields = $this->getAllowedFields($registeredApp->id, 'institutions');
            
            $institution = Institution::select($allowedFields)
                ->where('guid', $id)
                ->first();

            if (!$institution) {
                return response()->json([
                    'error' => 'Not Found',
                    'message' => 'Institution not found'
                ], 404);
            }

            return response()->json([
                'data' => $institution,
                'meta' => [
                    'registered_app' => $registeredApp->name,
                    'allowed_fields' => $allowedFields,
                    'permissions' => $this->getAppPermissions($registeredApp->id, 'institutions')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('API Institution show error', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Internal Server Error',
                'message' => 'An error occurred while fetching the institution'
            ], 500);
        }
    }
}