<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ApplicationApiPermission extends Model
{
    use HasFactory;

    private const INDIVIDUAL_IDENTIFIER_DESC = 'Associated individual identifier';
    private const INSTITUTION_IDENTIFIER_DESC = 'Associated institution identifier';

    protected $fillable = [
        'application_id',
        'table_name',
        'column_name',
        'display_name',
        'can_read',
        'can_write',
        'access_notes'
    ];

    protected $casts = [
        'can_read' => 'boolean',
        'can_write' => 'boolean'
    ];

    /**
     * Available tables for API access permissions (all tables including individual and institutional data)
     */
    public static function getApiAccessTables(): array
    {
        return [
            // Individual data tables
            'individuals' => [
                'name' => 'individuals',
                'label' => 'Individual Profile',
                'description' => 'Main individual profile data including personal information',
                'columns' => self::getTableColumns('individuals')
            ],
            'individual_addresses' => [
                'name' => 'individual_addresses',
                'label' => 'Individual Addresses',
                'description' => 'Individual address information (home, mailing, etc.)',
                'columns' => self::getTableColumns('individual_addresses')
            ],
            'individual_employments' => [
                'name' => 'individual_employments',
                'label' => 'Individual Employment',
                'description' => 'Employment history and career information',
                'columns' => self::getTableColumns('individual_employments')
            ],
            'individual_identities' => [
                'name' => 'individual_identities',
                'label' => 'Individual Identity & Demographics',
                'description' => 'Cultural identity, citizenship, and demographic information',
                'columns' => self::getTableColumns('individual_identities')
            ],
            // Institutional data tables
            'institutions' => [
                'name' => 'institutions',
                'label' => 'Institutions',
                'description' => 'Educational institution information',
                'columns' => self::getTableColumns('institutions')
            ],
            'institution_staff' => [
                'name' => 'institution_staff',
                'label' => 'Institution Staff',
                'description' => 'Staff members associated with institutions',
                'columns' => self::getTableColumns('institution_staff')
            ],
            'institution_sites' => [
                'name' => 'institution_sites',
                'label' => 'Institution Sites',
                'description' => 'Physical sites/campuses of institutions',
                'columns' => self::getTableColumns('institution_sites')
            ],
            'programs' => [
                'name' => 'programs',
                'label' => 'Programs',
                'description' => 'Educational programs offered by institutions',
                'columns' => self::getTableColumns('programs')
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
            // Individual tables
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
            'individual_addresses' => [
                'individual_id' => self::INDIVIDUAL_IDENTIFIER_DESC,
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
            'individual_employments' => [
                'individual_id' => self::INDIVIDUAL_IDENTIFIER_DESC,
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
            'individual_identities' => [
                'individual_id' => self::INDIVIDUAL_IDENTIFIER_DESC,
                'citizenship' => 'Citizenship status',
                'ethnicity' => 'Ethnic background',
                'first_language' => 'First or native language',
                'religion' => 'Religious affiliation',
                'marital_status' => 'Marital status',
                'emergency_contact_name' => 'Emergency contact full name',
                'emergency_contact_phone' => 'Emergency contact phone number',
                'emergency_contact_relationship' => 'Relationship to emergency contact',
                'status' => 'Identity record status'
            ],
            // Institutions table
            'institutions' => [
                'name' => 'Institution name',
                'type' => 'Type of institution (university, college, etc.)',
                'status' => 'Current operational status',
                'address' => 'Institution address',
                'phone' => 'Contact phone number',
                'email' => 'Contact email address',
                'website' => 'Institution website URL',
                'ministry_id' => 'Associated ministry identifier',
                'dli_number' => 'Designated Learning Institution number',
                'institution_code' => 'Unique institution code'
            ],
            // Institution Staff table
            'institution_staff' => [
                'institution_id' => self::INSTITUTION_IDENTIFIER_DESC,
                'first_name' => 'Staff member first name',
                'last_name' => 'Staff member last name',
                'email' => 'Staff member email address',
                'phone' => 'Staff member phone number',
                'position' => 'Job title or position',
                'department' => 'Department or division',
                'status' => 'Employment status',
                'start_date' => 'Employment start date',
                'end_date' => 'Employment end date'
            ],
            // Institution Sites table
            'institution_sites' => [
                'institution_id' => self::INSTITUTION_IDENTIFIER_DESC,
                'site_name' => 'Name of the site or campus',
                'address' => 'Site physical address',
                'city' => 'Site city',
                'province' => 'Site province',
                'postal_code' => 'Site postal code',
                'phone' => 'Site contact phone',
                'email' => 'Site contact email',
                'site_type' => 'Type of site (main campus, branch, etc.)',
                'status' => 'Site operational status'
            ],
            // Programs table
            'programs' => [
                'institution_id' => self::INSTITUTION_IDENTIFIER_DESC,
                'program_name' => 'Name of the program',
                'program_code' => 'Unique program code',
                'program_type' => 'Type of program (degree, diploma, certificate)',
                'level' => 'Education level (undergraduate, graduate, etc.)',
                'duration' => 'Program duration',
                'description' => 'Program description',
                'status' => 'Program status (active, inactive)',
                'start_date' => 'Program start date',
                'end_date' => 'Program end date',
                'credential_type' => 'Type of credential awarded'
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
            // Individual tables
            'individuals' => ['first_name', 'last_name', 'middle_name', 'preferred_name', 'date_of_birth', 'email', 'phone', 'sin'],
            'individual_addresses' => ['street_address', 'street_address_2', 'city', 'postal_code'],
            'individual_employments' => ['employer_name', 'job_title', 'salary'],
            'individual_identities' => ['emergency_contact_name', 'emergency_contact_phone'],
            // Institutional tables
            'institution_staff' => ['first_name', 'last_name', 'email', 'phone'],
            'institutions' => ['email', 'phone'],
            'institution_sites' => ['email', 'phone', 'address'],
            'programs' => []
        ];

        return in_array($columnName, $piiColumns[$tableName] ?? []);
    }

    /**
     * Determine if a column contains sensitive information
     */
    private static function isSensitiveInformation(string $tableName, string $columnName): bool
    {
        $sensitiveColumns = [
            // Individual tables
            'individuals' => ['sin', 'status', 'notes'],
            'individual_addresses' => ['is_primary'],
            'individual_employments' => ['salary', 'employment_type'],
            'individual_identities' => ['citizenship', 'ethnicity', 'religion', 'emergency_contact_relationship'],
            // Institutional tables
            'institutions' => ['dli_number', 'institution_code'],
            'institution_staff' => ['position', 'department', 'status'],
            'institution_sites' => ['address', 'postal_code'],
            'programs' => ['program_code']
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
