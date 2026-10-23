<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\HandlesApiResourcePermissions;
use App\Models\InstitutionSite;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class InstitutionSiteController extends Controller
{
    use HandlesApiResourcePermissions;

    private const TABLE = 'institution_sites';

    /**
     * Get all institution sites with permission filtering
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
                    'message' => 'Insufficient permissions to access institution sites data'
                ], 403);
            }

            $allowedFields = $this->getAllowedFields($registeredApp->id, self::TABLE);

            $query = InstitutionSite::select($allowedFields);

            // Optionally filter by parent institution
            if ($institutionGuid = $request->query('institution_guid')) {
                $query->where('institution_guid', $institutionGuid);
            }

            $sites = $query->get();

            return response()->json([
                'data' => $sites,
                'meta' => [
                    'count' => $sites->count(),
                    'registered_app' => $registeredApp->name,
                    'allowed_fields' => $allowedFields,
                    'permissions' => $this->getAppPermissions($registeredApp->id, self::TABLE)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('API Institution sites index error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Internal Server Error',
                'message' => 'An error occurred while fetching institution sites'
            ], 500);
        }
    }

    /**
     * Get a specific institution site by GUID
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
                    'message' => 'Insufficient permissions to access institution site data'
                ], 403);
            }

            $allowedFields = $this->getAllowedFields($registeredApp->id, self::TABLE);

            $site = InstitutionSite::select($allowedFields)
                ->where('institution_guid', $id)
                ->first();

            if (!$site) {
                return response()->json([
                    'error' => 'Not Found',
                    'message' => 'Institution site not found'
                ], 404);
            }

            return response()->json([
                'data' => $site,
                'meta' => [
                    'registered_app' => $registeredApp->name,
                    'allowed_fields' => $allowedFields,
                    'permissions' => $this->getAppPermissions($registeredApp->id, self::TABLE)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('API Institution site show error', [
                'institution_guid' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Internal Server Error',
                'message' => 'An error occurred while fetching the institution site'
            ], 500);
        }
    }
}
