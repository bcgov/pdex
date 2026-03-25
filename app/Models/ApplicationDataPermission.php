<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ApplicationDataPermission extends Model
{
    use HasFactory;

    private const LABEL_USER_GUID = 'User GUID';
    private const LABEL_POSTAL_CODE = 'Postal Code';
    private const LABEL_INDIVIDUAL_ID = 'Individual ID';
    private const DESC_REFERENCE_TO_INDIVIDUAL = 'Reference to individual';
    private const LABEL_USER_ID = 'User ID';
    private const DESC_REFERENCE_TO_USER = 'Reference to user';
    private const LABEL_ACTIVE_STATUS = 'Active Status';
    private const LABEL_INSTITUTION_GUID = 'Institution GUID';

    protected $fillable = [
        'application_id',
        'table_name',
        'column_name',
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
    public static function getAvailableTables(): array
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
     * Get columns for a specific table with metadata
     */
    public static function getTableColumns(string $tableName): array
    {
        $columns = Schema::getColumnListing($tableName);
        $metadata = self::getColumnMetadata($tableName);
        
        $result = [];
        foreach ($columns as $column) {
            // Skip system columns
            if (in_array($column, ['id', 'created_at', 'updated_at', 'deleted_at', 'version_number', 'latest_version'])) {
                continue;
            }
            
            $result[$column] = [
                'name' => $column,
                'label' => $metadata[$column]['label'] ?? ucfirst(str_replace('_', ' ', $column)),
                'description' => $metadata[$column]['description'] ?? '',
                'sensitive' => $metadata[$column]['sensitive'] ?? false,
                'pii' => $metadata[$column]['pii'] ?? false,
                'default_display_name' => self::generateDisplayName($column)
            ];
        }
        
        return $result;
    }

    /**
     * Generate a user-friendly display name from a column name
     */
    public static function generateDisplayName(string $columnName): string
    {
        // Handle special cases
        $specialCases = [
            'sin' => 'Social Insurance Number',
            'email_address' => 'Email Address',
            'phone_number' => 'Phone Number',
            'date_of_birth' => 'Date of Birth',
            'bceid_guid' => 'BCeID GUID',
            'user_guid' => self::LABEL_USER_GUID,
            'guid' => 'GUID',
            'id' => 'ID',
            'url' => 'URL',
            'postal_code' => self::LABEL_POSTAL_CODE,
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'job_title' => 'Job Title',
            'employment_status' => 'Employment Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
        
        $lowerColumnName = strtolower($columnName);
        
        // Check for exact matches first
        if (isset($specialCases[$lowerColumnName])) {
            return $specialCases[$lowerColumnName];
        }
        
        // Convert snake_case to Title Case
        $words = explode('_', $columnName);
        $titleWords = array_map(function($word) {
            // Handle common abbreviations
            $abbreviations = [
                'id' => 'ID',
                'guid' => 'GUID',
                'url' => 'URL',
                'api' => 'API',
                'sin' => 'SIN',
                'bceid' => 'BCeID',
                'idir' => 'IDIR'
            ];
            
            $lowerWord = strtolower($word);
            if (isset($abbreviations[$lowerWord])) {
                return $abbreviations[$lowerWord];
            }
            
            return ucfirst($word);
        }, $words);
        
        return implode(' ', $titleWords);
    }

    /**
     * Column metadata for better UI display
     */
    private static function getColumnMetadata(string $tableName): array
    {
        $metadata = [
            'individuals' => [
                'guid' => ['label' => 'GUID', 'description' => 'Unique identifier', 'pii' => false],
                'user_guid' => ['label' => self::LABEL_USER_GUID, 'description' => 'User system identifier', 'pii' => false],
                'social_insurance_number' => ['label' => 'Social Insurance Number', 'description' => 'SIN', 'sensitive' => true, 'pii' => true],
                'government_issued_id' => ['label' => 'Government ID', 'description' => 'Government issued identification', 'sensitive' => true, 'pii' => true],
                'first_name' => ['label' => 'First Name', 'description' => 'Given name', 'pii' => true],
                'middle_name' => ['label' => 'Middle Name', 'description' => 'Middle name', 'pii' => true],
                'last_name' => ['label' => 'Last Name', 'description' => 'Family name', 'pii' => true],
                'preferred_name' => ['label' => 'Preferred Name', 'description' => 'Preferred first name', 'pii' => true],
                'email_address' => ['label' => 'Email Address', 'description' => 'Primary email', 'pii' => true],
                'phone_number' => ['label' => 'Phone Number', 'description' => 'Primary phone', 'pii' => true],
                'alternate_phone_number' => ['label' => 'Alternate Phone', 'description' => 'Secondary phone', 'pii' => true],
                'date_of_birth' => ['label' => 'Date of Birth', 'description' => 'Birth date', 'sensitive' => true, 'pii' => true],
                'gender' => ['label' => 'Gender', 'description' => 'Gender identity', 'sensitive' => true],
                'sex' => ['label' => 'Sex', 'description' => 'Biological sex', 'sensitive' => true],
                'preferred_pronouns' => ['label' => 'Preferred Pronouns', 'description' => 'Preferred pronouns', 'sensitive' => true],
                'emergency_contact' => ['label' => 'Emergency Contact', 'description' => 'Emergency contact information', 'pii' => true],
                'disability_status' => ['label' => 'Disability Status', 'description' => 'Disability information', 'sensitive' => true],
                'accommodation_needs' => ['label' => 'Accommodation Needs', 'description' => 'Required accommodations', 'sensitive' => true],
                'bceid_guid' => ['label' => 'BCeID GUID', 'description' => 'BC Services Card identifier', 'pii' => false],
                'external_id' => ['label' => 'External ID', 'description' => 'External system identifier', 'pii' => false],
                'status' => ['label' => 'Status', 'description' => 'Account status', 'pii' => false],
                'verification_status' => ['label' => 'Verification Status', 'description' => 'Identity verification status', 'pii' => false],
                'metadata' => ['label' => 'Metadata', 'description' => 'Additional data', 'pii' => false],
                'notes' => ['label' => 'Notes', 'description' => 'Administrative notes', 'pii' => false],
                'email_verified_at' => ['label' => 'Email Verified At', 'description' => 'Email verification timestamp', 'pii' => false],
                'last_login_at' => ['label' => 'Last Login', 'description' => 'Last login timestamp', 'pii' => false],
            ],
            'individual_addresses' => [
                'individual_id' => ['label' => self::LABEL_INDIVIDUAL_ID, 'description' => self::DESC_REFERENCE_TO_INDIVIDUAL, 'pii' => false],
                'user_id' => ['label' => self::LABEL_USER_ID, 'description' => self::DESC_REFERENCE_TO_USER, 'pii' => false],
                'address_type' => ['label' => 'Address Type', 'description' => 'Type of address (home, mailing, etc.)', 'pii' => false],
                'address_line1' => ['label' => 'Address Line 1', 'description' => 'Street address', 'pii' => true],
                'address_line2' => ['label' => 'Address Line 2', 'description' => 'Apartment, suite, etc.', 'pii' => true],
                'city' => ['label' => 'City', 'description' => 'City name', 'pii' => true],
                'province' => ['label' => 'Province', 'description' => 'Province/state', 'pii' => true],
                'postal_code' => ['label' => self::LABEL_POSTAL_CODE, 'description' => 'Postal/ZIP code', 'pii' => true],
                'country' => ['label' => 'Country', 'description' => 'Country name', 'pii' => true],
                'is_primary' => ['label' => 'Is Primary', 'description' => 'Primary address flag', 'pii' => false],
                'is_active' => ['label' => 'Is Active', 'description' => 'Active address flag', 'pii' => false],
            ],
            'individual_employments' => [
                'individual_id' => ['label' => self::LABEL_INDIVIDUAL_ID, 'description' => self::DESC_REFERENCE_TO_INDIVIDUAL, 'pii' => false],
                'user_id' => ['label' => self::LABEL_USER_ID, 'description' => self::DESC_REFERENCE_TO_USER, 'pii' => false],
                'employment_status' => ['label' => 'Employment Status', 'description' => 'Current employment status', 'pii' => false],
                'is_looking_for_work' => ['label' => 'Looking for Work', 'description' => 'Job seeking status', 'pii' => false],
                'job_title' => ['label' => 'Job Title', 'description' => 'Current job title', 'pii' => false],
                'employer_name' => ['label' => 'Employer Name', 'description' => 'Current employer', 'pii' => false],
                'employer_industry' => ['label' => 'Industry', 'description' => 'Employer industry', 'pii' => false],
                'employment_start_date' => ['label' => 'Employment Start Date', 'description' => 'Start date of current job', 'pii' => false],
                'employment_end_date' => ['label' => 'Employment End Date', 'description' => 'End date of current job', 'pii' => false],
                'work_hours_per_week' => ['label' => 'Hours Per Week', 'description' => 'Weekly work hours', 'pii' => false],
                'monthly_income' => ['label' => 'Monthly Income', 'description' => 'Monthly earnings', 'sensitive' => true, 'pii' => true],
                'is_job_related_to_program' => ['label' => 'Job Related to Program', 'description' => 'Whether job relates to education program', 'pii' => false],
                'previous_job_title' => ['label' => 'Previous Job Title', 'description' => 'Last job title', 'pii' => false],
                'previous_employer_name' => ['label' => 'Previous Employer', 'description' => 'Last employer', 'pii' => false],
                'previous_employment_start_date' => ['label' => 'Previous Start Date', 'description' => 'Start date of previous job', 'pii' => false],
                'previous_employment_end_date' => ['label' => 'Previous End Date', 'description' => 'End date of previous job', 'pii' => false],
                'reason_for_leaving' => ['label' => 'Reason for Leaving', 'description' => 'Reason for leaving previous job', 'pii' => false],
                'career_interest_area' => ['label' => 'Career Interest', 'description' => 'Area of career interest', 'pii' => false],
                'desired_job_title' => ['label' => 'Desired Job Title', 'description' => 'Target job position', 'pii' => false],
                'career_readiness_level' => ['label' => 'Career Readiness', 'description' => 'Career preparation level', 'pii' => false],
                'has_career_plan' => ['label' => 'Has Career Plan', 'description' => 'Whether individual has career plan', 'pii' => false],
                'is_receiving_employment_insurance' => ['label' => 'Receiving EI', 'description' => 'Employment insurance status', 'sensitive' => true],
                'is_participating_in_work_study_program' => ['label' => 'Work Study Program', 'description' => 'Work study participation', 'pii' => false],
                'barriers_to_employment' => ['label' => 'Employment Barriers', 'description' => 'Barriers to finding employment', 'sensitive' => true],
                'is_current' => ['label' => 'Is Current', 'description' => 'Current employment record flag', 'pii' => false],
            ],
            'individual_identities' => [
                'individual_id' => ['label' => self::LABEL_INDIVIDUAL_ID, 'description' => self::DESC_REFERENCE_TO_INDIVIDUAL, 'pii' => false],
                'user_id' => ['label' => self::LABEL_USER_ID, 'description' => self::DESC_REFERENCE_TO_USER, 'pii' => false],
                'citizenship_status' => ['label' => 'Citizenship Status', 'description' => 'Citizenship/residency status', 'sensitive' => true],
                'country_of_birth' => ['label' => 'Country of Birth', 'description' => 'Birth country', 'sensitive' => true, 'pii' => true],
                'language_spoken_at_home' => ['label' => 'Home Language', 'description' => 'Primary language at home', 'sensitive' => true],
                'years_in_country' => ['label' => 'Years in Country', 'description' => 'Years residing in Canada', 'sensitive' => true],
                'refugee_status' => ['label' => 'Refugee Status', 'description' => 'Refugee status information', 'sensitive' => true],
                'immigration_status' => ['label' => 'Immigration Status', 'description' => 'Immigration status', 'sensitive' => true],
                'indigenous_status' => ['label' => 'Indigenous Status', 'description' => 'Indigenous identity status', 'sensitive' => true],
                'indigenous_group' => ['label' => 'Indigenous Group', 'description' => 'Specific indigenous group', 'sensitive' => true],
                'band_affiliation' => ['label' => 'Band Affiliation', 'description' => 'First Nations band affiliation', 'sensitive' => true],
                'indigenous_status_card_number' => ['label' => 'Status Card Number', 'description' => 'Indigenous status card number', 'sensitive' => true, 'pii' => true],
                'is_registered_with_band' => ['label' => 'Registered with Band', 'description' => 'Band registration status', 'sensitive' => true],
                'on_reserve_resident' => ['label' => 'On Reserve Resident', 'description' => 'Lives on reserve', 'sensitive' => true],
                'racial_identity' => ['label' => 'Racial Identity', 'description' => 'Racial/ethnic identity', 'sensitive' => true],
                'is_visible_minority' => ['label' => 'Visible Minority', 'description' => 'Visible minority status', 'sensitive' => true],
                'receives_indigenous_support_services' => ['label' => 'Indigenous Support Services', 'description' => 'Receives indigenous services', 'sensitive' => true],
                'receives_minority_support_services' => ['label' => 'Minority Support Services', 'description' => 'Receives minority services', 'sensitive' => true],
            ],
            'institutions' => [
                'guid' => ['label' => 'GUID', 'description' => 'Unique institution identifier', 'pii' => false],
                'bceid_business_guid' => ['label' => 'BCeID Business GUID', 'description' => 'Business BCeID identifier', 'pii' => false],
                'legal_operating_name' => ['label' => 'Legal Operating Name', 'description' => 'Official institution name', 'pii' => false],
                'institution_type' => ['label' => 'Institution Type', 'description' => 'Type of educational institution', 'pii' => false],
                'dli' => ['label' => 'DLI Number', 'description' => 'Designated Learning Institution number', 'pii' => false],
                'active_status' => ['label' => self::LABEL_ACTIVE_STATUS, 'description' => 'Whether institution is active', 'pii' => false],
            ],
            'institution_staff' => [
                'guid' => ['label' => 'GUID', 'description' => 'Unique staff identifier', 'pii' => false],
                'user_guid' => ['label' => self::LABEL_USER_GUID, 'description' => 'User system identifier', 'pii' => false],
                'institution_guid' => ['label' => self::LABEL_INSTITUTION_GUID, 'description' => 'Institution identifier', 'pii' => false],
                'bceid_business_guid' => ['label' => 'BCeID Business GUID', 'description' => 'Business BCeID identifier', 'pii' => false],
                'bceid_user_guid' => ['label' => 'BCeID User GUID', 'description' => 'User BCeID identifier', 'pii' => false],
                'bceid_user_id' => ['label' => 'BCeID User ID', 'description' => 'BCeID user ID', 'pii' => false],
                'bceid_user_name' => ['label' => 'BCeID Username', 'description' => 'BCeID username', 'pii' => true],
                'bceid_user_email' => ['label' => 'BCeID Email', 'description' => 'BCeID email address', 'pii' => true],
                'status' => ['label' => 'Status', 'description' => 'Staff member status', 'pii' => false],
                'last_touch_by_user_guid' => ['label' => 'Last Modified By', 'description' => 'Last modifier user GUID', 'pii' => false],
            ],
            'institution_sites' => [
                'guid' => ['label' => 'GUID', 'description' => 'Unique site identifier', 'pii' => false],
                'institution_guid' => ['label' => self::LABEL_INSTITUTION_GUID, 'description' => 'Parent institution identifier', 'pii' => false],
                'operating_name' => ['label' => 'Operating Name', 'description' => 'Site operating name', 'pii' => false],
                'site_type' => ['label' => 'Site Type', 'description' => 'Type of site or campus', 'pii' => false],
                'contact_first_name' => ['label' => 'Contact First Name', 'description' => 'Site contact first name', 'pii' => true],
                'contact_last_name' => ['label' => 'Contact Last Name', 'description' => 'Site contact last name', 'pii' => true],
                'contact_email' => ['label' => 'Contact Email', 'description' => 'Site contact email', 'pii' => true],
                'contact_phone' => ['label' => 'Contact Phone', 'description' => 'Site contact phone', 'pii' => true],
                'address_line_1' => ['label' => 'Address Line 1', 'description' => 'Street address', 'pii' => true],
                'address_line_2' => ['label' => 'Address Line 2', 'description' => 'Apartment, suite, etc.', 'pii' => true],
                'city' => ['label' => 'City', 'description' => 'City name', 'pii' => true],
                'province_state' => ['label' => 'Province/State', 'description' => 'Province or state', 'pii' => true],
                'country' => ['label' => 'Country', 'description' => 'Country name', 'pii' => true],
                'postal_code' => ['label' => self::LABEL_POSTAL_CODE, 'description' => 'Postal/ZIP code', 'pii' => true],
                'public' => ['label' => 'Public', 'description' => 'Public institution flag', 'pii' => false],
                'active_status' => ['label' => self::LABEL_ACTIVE_STATUS, 'description' => 'Site active status', 'pii' => false],
                'standing_status' => ['label' => 'Standing Status', 'description' => 'Institutional standing', 'pii' => false],
                'economic_region' => ['label' => 'Economic Region', 'description' => 'BC economic region', 'pii' => false],
                'notes' => ['label' => 'Notes', 'description' => 'Administrative notes', 'pii' => false],
            ],
            'programs' => [
                'guid' => ['label' => 'GUID', 'description' => 'Unique program identifier', 'pii' => false],
                'institution_guid' => ['label' => self::LABEL_INSTITUTION_GUID, 'description' => 'Institution identifier', 'pii' => false],
                'program_name' => ['label' => 'Program Name', 'description' => 'Name of the program', 'pii' => false],
                'program_type' => ['label' => 'Program Type', 'description' => 'Type of educational program', 'pii' => false],
                'program_number' => ['label' => 'Program Number', 'description' => 'Historical program number', 'pii' => false],
                'delivery_method' => ['label' => 'Delivery Method', 'description' => 'How program is delivered', 'pii' => false],
                'online_delivery_type' => ['label' => 'Online Delivery Type', 'description' => 'Synchronous or asynchronous', 'pii' => false],
                'credential_type' => ['label' => 'Credential Type', 'description' => 'Type of credential awarded', 'pii' => false],
                'micro_credential_type' => ['label' => 'Micro Credential Type', 'description' => 'Micro credential classification', 'pii' => false],
                'high_priority_industry' => ['label' => 'High Priority Industry', 'description' => 'Industry priority level', 'pii' => false],
                'total_duration_hrs' => ['label' => 'Total Duration (Hours)', 'description' => 'Program duration in hours', 'pii' => false],
                'creditable' => ['label' => 'Creditable', 'description' => 'Whether program offers credits', 'pii' => false],
                'full_time' => ['label' => 'Full Time', 'description' => 'Full-time program flag', 'pii' => false],
                'prov_funded_micro_cred' => ['label' => 'Provincially Funded Micro Credential', 'description' => 'Provincial funding flag', 'pii' => false],
                'indigenous_related_learning' => ['label' => 'Indigenous Related Learning', 'description' => 'Indigenous content flag', 'pii' => false],
                'diversity_inclusion_related_learning' => ['label' => 'Diversity & Inclusion Related', 'description' => 'D&I content flag', 'pii' => false],
                'active_status' => ['label' => self::LABEL_ACTIVE_STATUS, 'description' => 'Program active status', 'pii' => false],
                'last_touch_by_user_guid' => ['label' => 'Last Modified By', 'description' => 'Last modifier user GUID', 'pii' => false],
                'excel_guid' => ['label' => 'Excel GUID', 'description' => 'Excel import identifier', 'pii' => false],
                'start_date' => ['label' => 'Start Date', 'description' => 'Program start date', 'pii' => false],
                'end_date' => ['label' => 'End Date', 'description' => 'Program end date', 'pii' => false],
            ]
        ];

        return $metadata[$tableName] ?? [];
    }

    /**
     * Get the application that owns this permission
     */
    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Check if application has read access to specific table.column
     */
    public static function hasReadAccess(int $applicationId, string $tableName, string $columnName): bool
    {
        return self::where('application_id', $applicationId)
            ->where('table_name', $tableName)
            ->where('column_name', $columnName)
            ->where('can_read', true)
            ->exists();
    }

    /**
     * Check if application has write access to specific table.column
     */
    public static function hasWriteAccess(int $applicationId, string $tableName, string $columnName): bool
    {
        return self::where('application_id', $applicationId)
            ->where('table_name', $tableName)
            ->where('column_name', $columnName)
            ->where('can_write', true)
            ->exists();
    }

    /**
     * Get all accessible columns for an application and table
     */
    public static function getAccessibleColumns(int $applicationId, string $tableName, string $accessType = 'read'): array
    {
        $column = $accessType === 'write' ? 'can_write' : 'can_read';
        
        return self::where('application_id', $applicationId)
            ->where('table_name', $tableName)
            ->where($column, true)
            ->pluck('column_name')
            ->toArray();
    }
}
