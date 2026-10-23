<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\HandlesApiResourcePermissions;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CountryController extends Controller
{
    use HandlesApiResourcePermissions;

    private const TABLE = 'countries';

    /**
     * Get all countries with permission filtering
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
                    'message' => 'Insufficient permissions to access countries data'
                ], 403);
            }

            $allowedFields = $this->getAllowedFields($registeredApp->id, self::TABLE);

            $query = Country::select($allowedFields);

            // Optionally filter to active countries only
            if ($request->boolean('active_only')) {
                $query->where('active', true);
            }

            $countries = $query->orderBy('name')->get();

            return response()->json([
                'data' => $countries,
                'meta' => [
                    'count' => $countries->count(),
                    'registered_app' => $registeredApp->name,
                    'allowed_fields' => $allowedFields,
                    'permissions' => $this->getAppPermissions($registeredApp->id, self::TABLE)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('API Countries index error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Internal Server Error',
                'message' => 'An error occurred while fetching countries'
            ], 500);
        }
    }

    /**
     * Get a specific country by ID
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
                    'message' => 'Insufficient permissions to access country data'
                ], 403);
            }

            $allowedFields = $this->getAllowedFields($registeredApp->id, self::TABLE);

            $country = Country::select($allowedFields)
                ->where('id', $id)
                ->first();

            if (!$country) {
                return response()->json([
                    'error' => 'Not Found',
                    'message' => 'Country not found'
                ], 404);
            }

            return response()->json([
                'data' => $country,
                'meta' => [
                    'registered_app' => $registeredApp->name,
                    'allowed_fields' => $allowedFields,
                    'permissions' => $this->getAppPermissions($registeredApp->id, self::TABLE)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('API Country show error', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Internal Server Error',
                'message' => 'An error occurred while fetching the country'
            ], 500);
        }
    }
}
