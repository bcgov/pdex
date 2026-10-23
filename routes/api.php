<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OAuthController;

/**
 * @OA\Info(
 *     title="PDEX API Documentation",
 *     version="1.0.0",
 *     description="Post-Secondary Data Exchange (PDEX) API endpoints for managing student profiles, institutional data, and ministry oversight.",
 *     @OA\Contact(
 *         email="admin@pdex.gov.bc.ca",
 *         name="PDEX Support"
 *     )
 * )
 * 
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="PDEX API Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="oauth2",
 *     type="oauth2",
 *     description="OAuth 2.0 Client Credentials flow",
 *     @OA\Flow(
 *         flow="clientCredentials",
 *         tokenUrl="/api/oauth/token",
 *         scopes={
 *             "read": "Read access to resources",
 *             "write": "Write access to resources",
 *             "*": "Full access to all resources"
 *         }
 *     )
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Laravel Sanctum token authentication"
 * )
 */

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API Status endpoint
Route::get('/status', function () {
    return response()->json([
        'status' => 'online',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0'
    ]);
})->name('api.status');

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'database' => 'connected',
        'timestamp' => now()->toISOString()
    ]);
})->name('api.health');

// OAuth 2.0 Client Credentials endpoints
Route::prefix('oauth')->group(function () {
    Route::post('/token', [OAuthController::class, 'token']);
    Route::post('/introspect', [OAuthController::class, 'introspect'])->middleware('auth:sanctum');
    Route::post('/revoke', [OAuthController::class, 'revoke']);
});

// Protected API endpoints using OAuth token validation
Route::middleware(['oauth.token'])->prefix('v1')->group(function () {
    
    // Application management endpoints
    Route::prefix('applications')->group(function () {
        Route::post('/register', [\App\Http\Controllers\Api\ApplicationController::class, 'registerApp'])
            ->name('api.v1.applications.register');
        Route::get('/', [\App\Http\Controllers\Api\ApplicationController::class, 'index'])
            ->name('api.v1.applications.index');
        Route::get('/{id}', [\App\Http\Controllers\Api\ApplicationController::class, 'show'])
            ->name('api.v1.applications.show');
    });

    // Test endpoint to verify OAuth authentication
    Route::get('/test', function (Request $request) {
        return response()->json([
            'message' => 'OAuth authentication successful',
            'timestamp' => now()->toISOString(),
            'token_data' => $request->input('token_data'),
        ]);
    })->name('api.v1.test');

    // Students endpoints
    Route::prefix('students')->group(function () {
        Route::get('/', function (Request $request) {
            return response()->json([
                'message' => 'Students endpoint - OAuth authenticated',
                'data' => [],
                'token_claims' => $request->input('token_data')
            ]);
        })->name('api.v1.students.index');
    });

    // Institutions endpoints  
    Route::prefix('institutions')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\InstitutionController::class, 'index'])
            ->name('api.v1.institutions.index');
        Route::get('/{id}', [\App\Http\Controllers\Api\InstitutionController::class, 'show'])
            ->name('api.v1.institutions.show');
    });

    // Institution sites endpoints
    Route::prefix('institution-sites')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\InstitutionSiteController::class, 'index'])
            ->name('api.v1.institution-sites.index');
        Route::get('/{id}', [\App\Http\Controllers\Api\InstitutionSiteController::class, 'show'])
            ->name('api.v1.institution-sites.show');
    });

    // Institution staff endpoints
    Route::prefix('institution-staff')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\InstitutionStaffController::class, 'index'])
            ->name('api.v1.institution-staff.index');
        Route::get('/{id}', [\App\Http\Controllers\Api\InstitutionStaffController::class, 'show'])
            ->name('api.v1.institution-staff.show');
    });

    // Institution relationships endpoints
    Route::prefix('institution-relationships')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\InstitutionRelationshipController::class, 'index'])
            ->name('api.v1.institution-relationships.index');
        Route::get('/{id}', [\App\Http\Controllers\Api\InstitutionRelationshipController::class, 'show'])
            ->name('api.v1.institution-relationships.show');
    });

    // Countries endpoints
    Route::prefix('countries')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\CountryController::class, 'index'])
            ->name('api.v1.countries.index');
        Route::get('/{id}', [\App\Http\Controllers\Api\CountryController::class, 'show'])
            ->name('api.v1.countries.show');
    });

    // Utility / configuration endpoints
    Route::prefix('utils')->group(function () {
        // Student profile form field catalog (definitions + options)
        Route::get('/student', [\App\Http\Controllers\Api\ProfileFormFieldController::class, 'index'])
            ->name('api.v1.utils.student.index');
        Route::get('/student/{field}', [\App\Http\Controllers\Api\ProfileFormFieldController::class, 'show'])
            ->name('api.v1.utils.student.show');
    });
});
