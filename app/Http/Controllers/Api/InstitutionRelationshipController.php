<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\HandlesApiResourcePermissions;
use App\Models\InstitutionRelationship;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class InstitutionRelationshipController extends Controller
{
    use HandlesApiResourcePermissions;

    private const TABLE = 'institution_relationships';

    /**
     * Get all institution relationships with permission filtering
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
                    'message' => 'Insufficient permissions to access institution relationships data'
                ], 403);
            }

            $allowedFields = $this->getAllowedFields($registeredApp->id, self::TABLE);

            $query = InstitutionRelationship::select($allowedFields);

            // Optionally filter to relationships involving a specific institution (either side)
            if ($institutionGuid = $request->query('institution_guid')) {
                $query->where(function ($q) use ($institutionGuid) {
                    $q->where('institution_a_guid', $institutionGuid)
                        ->orWhere('institution_b_guid', $institutionGuid);
                });
            }

            $relationships = $query->get();

            return response()->json([
                'data' => $relationships,
                'meta' => [
                    'count' => $relationships->count(),
                    'registered_app' => $registeredApp->name,
                    'allowed_fields' => $allowedFields,
                    'permissions' => $this->getAppPermissions($registeredApp->id, self::TABLE)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('API Institution relationships index error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Internal Server Error',
                'message' => 'An error occurred while fetching institution relationships'
            ], 500);
        }
    }

    /**
     * Get a specific institution relationship by ID
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
                    'message' => 'Insufficient permissions to access institution relationship data'
                ], 403);
            }

            $allowedFields = $this->getAllowedFields($registeredApp->id, self::TABLE);

            $relationship = InstitutionRelationship::select($allowedFields)
                ->where('institution_a_guid', $id)
                ->orWhere('institution_b_guid', $id)
                ->first();

            if (!$relationship) {
                return response()->json([
                    'error' => 'Not Found',
                    'message' => 'Institution relationship not found'
                ], 404);
            }

            return response()->json([
                'data' => $relationship,
                'meta' => [
                    'registered_app' => $registeredApp->name,
                    'allowed_fields' => $allowedFields,
                    'permissions' => $this->getAppPermissions($registeredApp->id, self::TABLE)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('API Institution relationship show error', [
                'institution_guid' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Internal Server Error',
                'message' => 'An error occurred while fetching the institution relationship'
            ], 500);
        }
    }
}
