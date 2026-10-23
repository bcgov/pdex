<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationIndividualPermission;
use App\Models\ProfileFormField;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProfileFormFieldController extends Controller
{
    /**
     * The table name used for API access permissions.
     */
    private const PERMISSION_TABLE = 'profile_form_fields';

    /**
     * Return all student profile form fields (with options) exposed via the API.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $registeredApp = $this->authorizeRequest($request);

            if ($registeredApp instanceof JsonResponse) {
                return $registeredApp;
            }

            $fields = ProfileFormField::with('options')
                ->where('profile_type', 'student')
                ->where('is_active', true)
                ->where('api_enabled', true)
                ->orderBy('tab')
                ->orderBy('sort_order')
                ->get();

            $permissionLabels = ApplicationIndividualPermission::where('application_id', $registeredApp->id)
                ->whereIn('column_name', $fields->pluck('field_id'))
                ->pluck('display_name', 'column_name');

            Log::info('Permission Labels', [
                'permission_labels' => $permissionLabels,
            ]);
            return response()->json([
                'data' => $fields->map(fn (ProfileFormField $field) => $this->transformField($field, $permissionLabels[$field->field_id] ?? null))->values(),
                'meta' => [
                    'count' => $fields->count(),
                    'registered_app' => $registeredApp->name,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('API utils/student index error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Internal Server Error',
                'message' => 'An error occurred while fetching student profile form fields',
            ], 500);
        }
    }

    /**
     * Return a single student profile form field (with options) by its field_id.
     */
    public function show(Request $request, string $field): JsonResponse
    {
        try {
            $registeredApp = $this->authorizeRequest($request);

            if ($registeredApp instanceof JsonResponse) {
                return $registeredApp;
            }

            $profileField = ProfileFormField::with('options')
                ->where('profile_type', 'student')
                ->where('is_active', true)
                ->where('api_enabled', true)
                ->where('field_id', $field)
                ->first();

            if (!$profileField) {
                return response()->json([
                    'error' => 'Not Found',
                    'message' => 'Student profile form field not found',
                ], 404);
            }

            $permissionLabel = ApplicationIndividualPermission::where('application_id', $registeredApp->id)
                ->where('column_name', $profileField->field_id)
                ->value('display_name');

            return response()->json([
                'data' => $this->transformField($profileField, $permissionLabel),
                'meta' => [
                    'registered_app' => $registeredApp->name,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('API utils/student show error', [
                'field' => $field,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Internal Server Error',
                'message' => 'An error occurred while fetching the student profile form field',
            ], 500);
        }
    }

    /**
     * Resolve and authorize the calling application.
     *
     * @return Application|JsonResponse The application when authorized, or an error response.
     */
    private function authorizeRequest(Request $request): Application|JsonResponse
    {
        $tokenData = $request->input('token_data');
        $registeredApp = $this->getRegisteredApplication($this->resolveClientIdentifier($tokenData));

        if (!$registeredApp) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Application not registered for API access',
            ], 403);
        }

        if (!$this->hasPermission($registeredApp->id)) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Insufficient permissions to access student profile form fields',
            ], 403);
        }

        return $registeredApp;
    }

    /**
     * Get the registered application for the current token.
     */
    private function getRegisteredApplication(?string $clientIdentifier): ?Application
    {
        if (!$clientIdentifier) {
            return null;
        }

        return Application::where('client_id', $clientIdentifier)
            ->orWhereHas('oauthClient', function ($query) use ($clientIdentifier) {
                $query->where('client_id', $clientIdentifier);
            })
            ->first();
    }

    /**
     * Resolve the OAuth client identifier from token claims.
     * Client credentials tokens typically identify the client in azp.
     */
    private function resolveClientIdentifier(array $tokenData): ?string
    {
        return $tokenData['azp'] ?? $tokenData['client_id'] ?? $tokenData['sub'] ?? null;
    }

    /**
     * Check if the registered application has read access to the form field catalog.
     */
    private function hasPermission(int $appId): bool
    {
        return \DB::table('application_api_permissions')
            ->where('application_id', $appId)
            ->where('table_name', self::PERMISSION_TABLE)
            ->where('can_read', true)
            ->exists();
    }

    /**
     * Transform a field into the API response shape (definition + options).
     */
    private function transformField(ProfileFormField $field, ?string $permissionLabel = null): array
    {
        return [
            'field_id' => $field->field_id,
            'tab' => $field->tab,
            'section' => $field->section,
            'label' => $field->label,
            'permission_label' => $permissionLabel,
            'type' => $field->type,
            'required' => (bool) $field->required,
            'placeholder' => $field->placeholder,
            'multi_select' => (bool) $field->multi_select,
            'help_text' => $field->help_text,
            'sort_order' => (int) $field->sort_order,
            'options' => $field->options->map(fn ($option) => [
                'value' => $option->value,
                'label' => $option->label,
                'is_default' => (bool) $option->is_default,
            ])->values(),
        ];
    }
}
