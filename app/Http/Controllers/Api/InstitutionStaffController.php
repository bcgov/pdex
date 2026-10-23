<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\HandlesApiResourcePermissions;
use App\Models\InstitutionStaff;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class InstitutionStaffController extends Controller
{
    use HandlesApiResourcePermissions;

    private const TABLE = 'institution_staff';

    /**
     * Get all institution staff with permission filtering
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

            if (!$this->hasPermission($registeredApp->id, self::TABLE, 'read')) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'Insufficient permissions to access institution staff data'
                ], 403);
            }

            $allowedFields = $this->getAllowedFields($registeredApp->id, self::TABLE);

            $query = InstitutionStaff::select($allowedFields);

            // Optionally filter by parent institution
            if ($institutionGuid = $request->query('institution_guid')) {
                $query->where('institution_guid', $institutionGuid);
            }

            $staff = $query->get();

            return response()->json([
                'data' => $staff,
                'meta' => [
                    'count' => $staff->count(),
                    'registered_app' => $registeredApp->name,
                    'allowed_fields' => $allowedFields,
                    'permissions' => $this->getAppPermissions($registeredApp->id, self::TABLE)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('API Institution staff index error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Internal Server Error',
                'message' => 'An error occurred while fetching institution staff'
            ], 500);
        }
    }

    /**
     * Get a specific institution staff member by GUID
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

            if (!$this->hasPermission($registeredApp->id, self::TABLE, 'read')) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'Insufficient permissions to access institution staff data'
                ], 403);
            }

            $allowedFields = $this->getAllowedFields($registeredApp->id, self::TABLE);

            $staff = InstitutionStaff::select($allowedFields)
                ->where('institution_guid', $id)
                ->first();

            if (!$staff) {
                return response()->json([
                    'error' => 'Not Found',
                    'message' => 'Institution staff member not found'
                ], 404);
            }

            return response()->json([
                'data' => $staff,
                'meta' => [
                    'registered_app' => $registeredApp->name,
                    'allowed_fields' => $allowedFields,
                    'permissions' => $this->getAppPermissions($registeredApp->id, self::TABLE)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('API Institution staff show error', [
                'institution_guid' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Internal Server Error',
                'message' => 'An error occurred while fetching the institution staff member'
            ], 500);
        }
    }
}
