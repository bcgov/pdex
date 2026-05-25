<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ApplicationIndividualPermission extends Model
{
    use HasFactory;

    private const DESC_ASSOCIATED_INDIVIDUAL_ID = 'Associated individual identifier';

    protected $fillable = [
        'application_id',
        'table_name',
        'column_name',
        'destination_field',
        'display_name',
        'can_read',
        'can_write',
        'is_required',
        'access_notes'
    ];

    protected $casts = [
        'can_read' => 'boolean',
        'can_write' => 'boolean',
        'is_required' => 'boolean'
    ];

    /**
     * Available tables for individual data access permissions (individual_* tables only)
     */
    public static function getIndividualTables(): array
    {
        return [
            'individuals' => [
                'name' => 'individuals',
                'label' => 'Individual Profile',
                'description' => 'Main individual profile data including personal information',
                'columns' => self::getTableColumns('individuals')
            ],
            'individual_addresses' => [
                'name' => 'individual_addresses',
                'label' => 'Addresses',
                'description' => 'Individual address information (home, mailing, etc.)',
                'columns' => self::getTableColumns('individual_addresses')
            ],
            'individual_employments' => [
                'name' => 'individual_employments',
                'label' => 'Employment',
                'description' => 'Employment history and career information',
                'columns' => self::getTableColumns('individual_employments')
            ],
            'individual_identities' => [
                'name' => 'individual_identities',
                'label' => 'Identity & Demographics',
                'description' => 'Cultural identity, citizenship, and demographic information',
                'columns' => self::getTableColumns('individual_identities')
            ]
        ];
    }

    /**
     * Get columns for a table with metadata
     */
    private static function getTableColumns(string $tableName): array
    {
        if (!Schema::hasTable($tableName)) {
            return [];
        }

        $columns = Schema::getColumnListing($tableName);
        $columnData = [];

        foreach ($columns as $column) {
            // Skip system columns
            if (in_array($column, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $columnData[$column] = self::getColumnMetadata($tableName, $column);
        }

        return $columnData;
    }

    /**
     * Get metadata for a specific column
     */
    private static function getColumnMetadata(string $tableName, string $columnName): array
    {
        $metadata = [
            'name' => $columnName,
            'label' => ucwords(str_replace('_', ' ', $columnName)),
            'description' => self::getColumnDescription($tableName, $columnName),
            'default_display_name' => ucwords(str_replace('_', ' ', $columnName)),
            'pii' => self::isPersonallyIdentifiable($tableName, $columnName),
            'sensitive' => self::isSensitiveInformation($tableName, $columnName)
        ];

        return $metadata;
    }

    /**
     * Get human-readable description for a column
     */
    private static function getColumnDescription(string $tableName, string $columnName): string
    {
        $descriptions = [
            // Individuals table
            'individuals' => [
                'first_name' => 'Individual\'s first name',
                'last_name' => 'Individual\'s last name',
                'middle_name' => 'Individual\'s middle name or initial',
                'preferred_name' => 'Individual\'s preferred name',
                'date_of_birth' => 'Individual\'s date of birth',
                'gender' => 'Individual\'s gender identity',
                'email' => 'Primary email address',
                'phone' => 'Primary phone number',
                'sin' => 'Social Insurance Number',
                'status' => 'Individual\'s current status',
                'notes' => 'Additional notes about the individual'
            ],
            // Individual Addresses table
            'individual_addresses' => [
                'individual_id' => self::DESC_ASSOCIATED_INDIVIDUAL_ID,
                'address_type' => 'Type of address (home, mailing, work)',
                'street_address' => 'Street address line 1',
                'street_address_2' => 'Street address line 2',
                'city' => 'City or municipality',
                'province' => 'Province or state',
                'postal_code' => 'Postal or ZIP code',
                'country' => 'Country',
                'is_primary' => 'Whether this is the primary address',
                'status' => 'Address status (active, inactive)'
            ],
            // Individual Employments table
            'individual_employments' => [
                'individual_id' => self::DESC_ASSOCIATED_INDIVIDUAL_ID,
                'employer_name' => 'Name of employer',
                'job_title' => 'Job title or position',
                'industry' => 'Industry sector',
                'employment_type' => 'Type of employment (full-time, part-time, contract)',
                'start_date' => 'Employment start date',
                'end_date' => 'Employment end date',
                'current_employment' => 'Whether this is current employment',
                'salary' => 'Salary or wage information',
                'status' => 'Employment record status'
            ],
            // Individual Identities table
            'individual_identities' => [
                'individual_id' => self::DESC_ASSOCIATED_INDIVIDUAL_ID,
                'citizenship' => 'Citizenship status',
                'ethnicity' => 'Ethnic background',
                'first_language' => 'First or native language',
                'religion' => 'Religious affiliation',
                'marital_status' => 'Marital status',
                'emergency_contact_name' => 'Emergency contact full name',
                'emergency_contact_phone' => 'Emergency contact phone number',
                'emergency_contact_relationship' => 'Relationship to emergency contact',
                'status' => 'Identity record status'
            ]
        ];

        return $descriptions[$tableName][$columnName] ?? ucwords(str_replace('_', ' ', $columnName));
    }

    /**
     * Determine if a column contains personally identifiable information
     */
    private static function isPersonallyIdentifiable(string $tableName, string $columnName): bool
    {
        $piiColumns = [
            'individuals' => ['first_name', 'last_name', 'middle_name', 'preferred_name', 'date_of_birth', 'email', 'phone', 'sin'],
            'individual_addresses' => ['street_address', 'street_address_2', 'city', 'postal_code'],
            'individual_employments' => ['employer_name', 'job_title', 'salary'],
            'individual_identities' => ['emergency_contact_name', 'emergency_contact_phone']
        ];

        return in_array($columnName, $piiColumns[$tableName] ?? []);
    }

    /**
     * Determine if a column contains sensitive information
     */
    private static function isSensitiveInformation(string $tableName, string $columnName): bool
    {
        $sensitiveColumns = [
            'individuals' => ['sin', 'status', 'notes'],
            'individual_addresses' => ['is_primary'],
            'individual_employments' => ['salary', 'employment_type'],
            'individual_identities' => ['citizenship', 'ethnicity', 'religion', 'emergency_contact_relationship']
        ];

        return in_array($columnName, $sensitiveColumns[$tableName] ?? []);
    }

    /**
     * Relationship to application
     */
    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
