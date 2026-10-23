<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Application;
use Illuminate\Support\Facades\DB;

/**
 * Shared helpers for OAuth-token protected API resource controllers.
 *
 * Encapsulates the application resolution and per-table field/permission
 * filtering logic used by the institutional data endpoints.
 */
trait HandlesApiResourcePermissions
{
    /**
     * Get the registered application for the current token.
     */
    protected function getRegisteredApplication(?string $clientIdentifier): ?Application
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
     */
    protected function resolveClientIdentifier(array $tokenData): ?string
    {
        return $tokenData['azp'] ?? $tokenData['client_id'] ?? $tokenData['sub'] ?? null;
    }

    /**
     * Check if the registered application has a specific permission.
     */
    protected function hasPermission(int $appId, string $tableName, string $action): bool
    {
        $column = $action === 'read' ? 'can_read' : 'can_write';

        return DB::table('application_api_permissions')
            ->where('application_id', $appId)
            ->where('table_name', $tableName)
            ->where($column, true)
            ->exists();
    }

    /**
     * Get allowed fields for a specific table based on application permissions.
     */
    protected function getAllowedFields(int $appId, string $tableName): array
    {
        $permissions = DB::table('application_api_permissions')
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
     * Get basic fields when no specific permissions are set.
     */
    protected function getBasicFields(string $tableName): array
    {
        $basicFields = [
            'institutions' => ['id', 'legal_operating_name', 'status', 'created_at'],
            'institution_sites' => ['id', 'guid', 'institution_guid', 'operating_name', 'city', 'active_status', 'created_at'],
            'institution_staff' => ['id', 'guid', 'institution_guid', 'bceid_user_name', 'status', 'created_at'],
            'institution_relationships' => ['id', 'institution_a_guid', 'institution_b_guid', 'relationship_type', 'is_active', 'created_at'],
            'countries' => ['id', 'name', 'active', 'created_at'],
        ];

        return $basicFields[$tableName] ?? ['id', 'created_at'];
    }

    /**
     * Get permissions for a specific table.
     */
    protected function getAppPermissions(int $appId, string $tableName): array
    {
        $permissions = DB::table('application_api_permissions')
            ->where('application_id', $appId)
            ->where('table_name', $tableName)
            ->select('column_name', 'can_read', 'can_write', 'display_name')
            ->get();

        return [
            'readable_fields' => $permissions->where('can_read', true)->pluck('column_name')->values(),
            'writable_fields' => $permissions->where('can_write', true)->pluck('column_name')->values(),
            'field_details' => $permissions->map(function ($perm) {
                return [
                    'column' => $perm->column_name,
                    'display_name' => $perm->display_name,
                    'can_read' => $perm->can_read,
                    'can_write' => $perm->can_write,
                ];
            })->values(),
        ];
    }
}
