<?php

namespace Modules\Student\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Individual;
use App\Models\Application;
use App\Models\ApplicationDataPermission;
use App\Models\ApplicationIndividualPermission;
use App\Models\ApplicationApiPermission;
use App\Models\IndividualApplicationPermissionSelection;
use App\Events\IndividualCreated;
use App\Events\IndividualUpdated;
use App\Models\IndividualAddress;
use App\Models\IndividualEmployment;
use App\Models\IndividualIdentity;
use App\Models\Country;
use App\Models\ProfileFormField;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Modules\Student\Http\Requests\StoreIndividualRequest;
use Modules\Student\Http\Requests\UpdateIndividualRequest;
use Modules\Student\Http\Requests\StoreIndividualMultiStepRequest;
use Modules\Student\Http\Requests\UpdateIndividualMultiStepRequest;
use Firebase\JWT\JWT;

class StudentController extends Controller
{
    /**
     * Display the student dashboard with available applications.
     */
    public function index(): Response
    {
        // Get applications that are enabled for BCSC (Student users)
        // Include both active and offline applications, and have both security and privacy approvals
        $applications = Application::where('bcsc_enabled', true)
            ->whereIn('status', ['active', 'offline'])
            ->where('security_approval_status', 'approved')
            ->where('privacy_approval_status', 'approved')
            ->with(['individualPermissions', 'apiPermissions'])
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('name', 'asc')
            ->select([
                'id',
                'guid',
                'name',
                'description',
                'bcsc_redirect_url',
                'status',
                'security_approval_status',
                'privacy_approval_status',
                'active_alert_message',
                'offline_alert_message',
                'offline_start_time',
                'offline_end_time',
                'info_label', 
                'info_url',
                'profile_integration_ready',
            ])
            ->get()
            ->map(function ($app) {
                // Only use individual permissions for display (NOT API permissions)
                // This matches what we use for token generation
                $individualPermissions = $app->individualPermissions;
                
                // Group data permissions by table for better display
                $permissionGroups = [];
                foreach ($individualPermissions as $permission) {
                    $tableName = $permission->table_name;
                    if (!isset($permissionGroups[$tableName])) {
                        $permissionGroups[$tableName] = [
                            'table_name' => $tableName,
                            'table_label' => $this->getTableLabel($tableName),
                            'permissions' => []
                        ];
                    }
                    $permissionGroups[$tableName]['permissions'][] = [
                        'column_name' => $permission->column_name,
                        'display_name' => $permission->display_name,
                        'can_read' => $permission->can_read,
                        'can_write' => $permission->can_write,
                        'is_required' => $permission->is_required ?? false,
                    ];
                }

                return [
                    'id' => $app->id,
                    'guid' => $app->guid,
                    'name' => $app->name,
                    'description' => $app->description,
                    'redirect_url' => $app->bcsc_redirect_url,
                    'status' => $app->status,
                    'info_label' => $app->info_label,
                    'info_url' => $app->info_url,
                    'alert_message' => $app->status === 'offline' 
                        ? $app->offline_alert_message 
                        : $app->active_alert_message,
                    'profile_integration_ready' => $app->profile_integration_ready,
                    'data_permission_groups' => array_values($permissionGroups),
                    'profile_complete' => $this->checkProfileCompleteness($app),
                    'missing_data_message' => $this->getMissingDataMessage($app),
                    'has_permissions' => $individualPermissions->count() > 0,
                ];
            });

        // Get user's profile data for modal forms
        $individual = Individual::where('user_guid', Auth::user()->guid)->first();
        $profileData = $individual ? ["general" => $individual, "addresses" => $individual->addresses, 
            "employments" => $individual->employments, "identities" => $individual->identities] : null;

        // Add permission selections to each application
        $applications = $applications->map(function ($app) use ($individual) {
            if ($individual && $app['has_permissions']) {
                // Get existing permission selections for this user and application
                $existingSelections = IndividualApplicationPermissionSelection::getSelectionsForUserAndApplication(
                    $individual->id, 
                    $app['id']
                );

                // Update permission groups to include selection status
                $app['data_permission_groups'] = collect($app['data_permission_groups'])->map(function ($group) use ($existingSelections, $app) {
                    // Get the application object to access its permissions
                    $application = Application::with('individualPermissions')->find($app['id']);
                    
                    $group['permissions'] = collect($group['permissions'])->map(function ($permission) use ($existingSelections, $application, $group) {
                        $permissionId = $this->getPermissionIdFromApplication($application, $group['table_name'], $permission['column_name']);
                        $permission['is_selected'] = isset($existingSelections[$permissionId]) 
                            ? $existingSelections[$permissionId]->is_selected 
                            : $permission['is_required']; // Default to required status
                        $permission['permission_id'] = $permissionId;
                        return $permission;
                    })->toArray();
                    return $group;
                })->toArray();
            }
            return $app;
        });

        return Inertia::render('Student::Dashboard', [
            'applications' => $applications,
            'user' => auth()->user()->only(['name', 'email']),
            'profileData' => $profileData,
        ]);
    }

    /**
     * Launch application with profile data
     */
    public function launchApplication(Request $request, $appId)
    {
        try {
            \Log::info("LaunchApplication called for app: $appId", [
                'user_id' => Auth::id(),
                'has_permission_selections' => $request->has('permission_selections')
            ]);
            
            $app = Application::findOrFail($appId);
            $user = Auth::user();
            
            // Get individual profile using the same method as index
            $individual = Individual::where('user_guid', $user->guid)->first();
            
            // If no individual profile exists, we can still launch the application
            // The user will provide data directly to the application
            if (!$individual) {
                \Log::info("No individual profile found for user: " . Auth::id() . ", launching without profile data");
            }
            
            $permissionSelections = $request->input('permission_selections', []);
            
            \Log::info("Processing launch request", [
                'permission_selections_count' => count($permissionSelections),
                'has_individual_profile' => !is_null($individual)
            ]);
            
            // Save permission selections if individual exists
            if ($individual && !empty($permissionSelections)) {
                $this->savePermissionSelections($individual->id, $appId, $permissionSelections);
            }
            
            // Prepare individual data for token based on user's permission selections
            $individualData = null;
            if ($individual) {
                // Include available profile data based on user's permission selections
                $individualData = $this->prepareIndividualDataForTokenWithSelections($app, $individual, $permissionSelections);
                \Log::info("Including profile data in token", ['fields_count' => count($individualData)]);
            } else {
                // Send empty individual parameter
                $individualData = [];
                \Log::info("Sending empty individual parameter");
            }
            
            // Create a token with the individual data
            $token = $this->createApplicationToken($app, $user, $individualData);
            
            // Store token temporarily in session for gateway to retrieve
            session(['app_launch_token_' . $appId => $token]);
            session(['app_launch_user_' . $appId => $user->id]);
            
            // Generate launch URL
            $launchUrl = "/gateway/$appId";
            
            \Log::info("Application launch successful", [
                'app_id' => $appId,
                'launch_url' => $launchUrl,
                'token_created' => !empty($token)
            ]);
            
            // Return success response - frontend will handle redirect
            return back()->with([
                'launch_url' => $launchUrl,
                'success' => 'Application launched successfully!'
            ]);
            
        } catch (\Exception $e) {
            \Log::error("Application launch failed", [
                'app_id' => $appId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->withErrors([
                'message' => 'Failed to launch application: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Prepare individual data for token based on application permissions
     */
    private function prepareIndividualDataForToken($application)
    {
        $tokenData = [];
        $user = Auth::user();
        
        // Get the individual with all relationships loaded
        $individual = Individual::where('user_guid', $user->guid)
            ->with(['addresses', 'employments', 'identities'])
            ->first();
        
        if (!$individual) {
            \Log::warning("No individual found for token preparation", ['user_guid' => $user->guid]);
            return $tokenData;
        }
        
        // Get all individual permissions for this application (NOT API permissions)
        $individualPermissions = $application->individualPermissions;
        
        \Log::info("Preparing token data", [
            'app_id' => $application->id,
            'individual_permissions_count' => $individualPermissions->count(),
            'individual_id' => $individual->id
        ]);
        
        // Process each individual permission and get data from the appropriate table
        foreach ($individualPermissions as $permission) {
            if (!$permission->can_read) {
                continue;
            }
            
            $value = null;
            $tableName = $permission->table_name;
            $columnName = $permission->column_name;
            
            // Get value based on table name
            switch ($tableName) {
                case 'individuals':
                    // Check if the column actually exists on the model
                    if (in_array($columnName, (new Individual())->getFillable())) {
                        $value = $individual->{$columnName} ?? null;
                    } else {
                        \Log::warning("Column does not exist in individuals table", [
                            'column_name' => $columnName,
                            'permission_id' => $permission->id ?? 'unknown'
                        ]);
                        continue 2;
                    }
                    break;
                    
                case 'individual_addresses':
                    // Check if the column exists on the address model
                    if (in_array($columnName, (new IndividualAddress())->getFillable())) {
                        // Get primary address or first address
                        $primaryAddress = $individual->addresses->where('is_primary', true)->first() 
                                       ?? $individual->addresses->first();
                        if ($primaryAddress) {
                            $value = $primaryAddress->{$columnName} ?? null;
                        } else {
                            // No address record exists - set default based on field type
                            $value = $this->getDefaultValueForField($columnName);
                        }
                    } else {
                        \Log::warning("Column does not exist in individual_addresses table", [
                            'column_name' => $columnName,
                            'permission_id' => $permission->id ?? 'unknown'
                        ]);
                        continue 2;
                    }
                    break;
                    
                case 'individual_employments':
                    // Check if the column exists on the employment model
                    if (in_array($columnName, (new IndividualEmployment())->getFillable())) {
                        // Get current employment or first employment
                        $currentEmployment = $individual->employments->where('is_current', true)->first()
                                          ?? $individual->employments->first();
                        if ($currentEmployment) {
                            $value = $currentEmployment->{$columnName} ?? null;
                        } else {
                            // No employment record exists - set default based on field type
                            $value = $this->getDefaultValueForField($columnName);
                        }
                    } else {
                        \Log::warning("Column does not exist in individual_employments table", [
                            'column_name' => $columnName,
                            'permission_id' => $permission->id ?? 'unknown'
                        ]);
                        continue 2;
                    }
                    break;
                    
                case 'individual_identities':
                    // Check if the column exists on the identity model
                    if (in_array($columnName, (new IndividualIdentity())->getFillable())) {
                        // Get first identity
                        $identity = $individual->identities->first();
                        if ($identity) {
                            $value = $identity->{$columnName} ?? null;
                        } else {
                            // No identity record exists - set default based on field type
                            $value = $this->getDefaultValueForField($columnName);
                        }
                    } else {
                        \Log::warning("Column does not exist in individual_identities table", [
                            'column_name' => $columnName,
                            'permission_id' => $permission->id ?? 'unknown'
                        ]);
                        continue 2;
                    }
                    break;
                    
                default:
                    \Log::warning("Unknown table name in permission", [
                        'table_name' => $tableName,
                        'column_name' => $columnName,
                        'permission_id' => $permission->id ?? 'unknown'
                    ]);
                    continue 2;
            }
            
            // Include ALL fields that the application requests, regardless of null values
            // This ensures the application gets complete data structure for all 11 fields
            $tokenData[$columnName] = $this->normalizeDatetimeValue($value);
            
            \Log::debug("Processing permission", [
                'table_name' => $tableName,
                'column_name' => $columnName,
                'can_read' => $permission->can_read,
                'value' => $value,
                'value_type' => gettype($value),
                'has_related_record' => $this->hasRelatedRecord($individual, $tableName)
            ]);
        }
        
        \Log::info("Token data prepared", [
            'app_id' => $application->id,
            'app_name' => $application->name,
            'token_data_keys' => array_keys($tokenData),
            'token_data_count' => count($tokenData),
            'token_data' => $tokenData  // Log actual data for debugging
        ]);
        
        return $tokenData;
    }

    /**
     * Create application launch token
     */
    private function createApplicationToken($application, $user, $individualData)
    {
        $tokenData = [
            'user_guid' => $user->guid,
            'user_email' => $user->email,
            'user_name' => $user->name,
            'individual' => $individualData,
            'issued_at' => time(),
            'expires_at' => time() + (60 * 15), // 15 minutes expiry
        ];
        
        // The JWT is signed with the application's client_secret (HS256, symmetric).
        // The receiving application verifies the token using the same shared secret.
        $secret = $application->client_secret;
        if (! is_string($secret) || $secret === '') {
            // Application has no signing secret yet; generate and persist a stable one
            // so the token can be signed and later verified consistently.
            $secret = \Illuminate\Support\Str::random(64);
            $application->forceFill(['client_secret' => $secret])->save();

            \Log::warning('Application had no client_secret; generated one for JWT signing', [
                'app_id' => $application->id,
                'app_name' => $application->name,
            ]);
        }

        return JWT::encode($tokenData, $secret, 'HS256');
    }

    /**
     * Get required fields for an application based on its permissions
     */
    private function getRequiredFieldsForApplication($application)
    {
        $requiredFields = [];
        
        // Load individual permissions (Required/Optional system)
        $individualPermissions = $application->individualPermissions;
        
        foreach ($individualPermissions as $permission) {
            if ($permission->is_required) {
                $requiredFields[] = $permission->field_name;
            }
        }
        
        return $requiredFields;
    }

    /**
     * Update individual profile from form data
     */
    private function updateIndividualFromFormData($individual, $formData)
    {
        // Update basic individual fields - use actual database field names
        $individualFields = [
            'first_name', 'last_name', 'middle_name', 'preferred_name',
            'date_of_birth', 'gender', 'preferred_pronouns', 'phone_number', 
            'email_address', 'alternate_phone_number', 'disability_status',
            'accommodation_needs', 'social_insurance_number', 'government_issued_id',
            'provincial_education_number', 'status', 'verification_status'
        ];
        
        foreach ($individualFields as $field) {
            if (array_key_exists($field, $formData)) {
                $individual->{$field} = $formData[$field];
            }
        }
        
        $individual->save();
        
        // Update address if provided - use actual database field names
        $addressFields = [
            'address_type', 'address_line1', 'address_line2', 'city', 
            'province', 'postal_code', 'country', 'is_primary', 'is_active'
        ];
        
        $hasAddressData = false;
        $addressData = [];
        foreach ($addressFields as $field) {
            if (array_key_exists($field, $formData)) {
                $addressData[$field] = $formData[$field];
                $hasAddressData = true;
            }
        }
        
        if ($hasAddressData) {
            $individual->currentAddress()->updateOrCreate(
                ['individual_id' => $individual->id, 'is_primary' => true],
                $addressData
            );
        }
        
        // Update employment if provided - use actual database field names
        $employmentFields = [
            'employment_status', 'is_looking_for_work', 'job_title', 'employer_name',
            'employer_industry', 'employment_start_date', 'employment_end_date',
            'work_hours_per_week', 'monthly_income', 'is_job_related_to_program',
            'previous_job_title', 'previous_employer_name', 'previous_employment_start_date',
            'previous_employment_end_date', 'reason_for_leaving', 'career_interest_area',
            'desired_job_title', 'career_readiness_level', 'has_career_plan',
            'is_receiving_employment_insurance', 'is_participating_in_work_study_program',
            'barriers_to_employment', 'is_current'
        ];
        
        $hasEmploymentData = false;
        $employmentData = [];
        foreach ($employmentFields as $field) {
            if (array_key_exists($field, $formData)) {
                $employmentData[$field] = $formData[$field];
                $hasEmploymentData = true;
            }
        }
        
        if ($hasEmploymentData) {
            $individual->currentEmployment()->updateOrCreate(
                ['individual_id' => $individual->id, 'is_current' => true],
                $employmentData
            );
        }
        
        // Update identity if provided - use actual database field names
        $identityFields = [
            'citizenship_status', 'country_of_birth', 'language_spoken_at_home',
            'years_in_country', 'refugee_status', 'immigration_status',
            'indigenous_status', 'indigenous_group', 'band_affiliation',
            'indigenous_status_card_number', 'is_registered_with_band',
            'on_reserve_resident', 'racial_identity', 'racial_identity_other_text',
            'is_visible_minority',
            'receives_indigenous_support_services', 'receives_minority_support_services'
        ];
        
        $hasIdentityData = false;
        $identityData = [];
        foreach ($identityFields as $field) {
            if (array_key_exists($field, $formData)) {
                $identityData[$field] = $formData[$field];
                $hasIdentityData = true;
            }
        }
        
        if ($hasIdentityData) {
            $individual->identity()->updateOrCreate(
                ['individual_id' => $individual->id],
                $identityData
            );
        }
    }

    /**
     * Generate application launch URL with form data token
     */
    private function generateApplicationLaunchUrl($application, $formData)
    {
        // This would typically involve creating a JWT token with the form data
        // and redirecting through the gateway with the token
        
        // For now, we'll use the existing gateway route
        // In a real implementation, you'd want to:
        // 1. Create a JWT token with the form data
        // 2. Store it temporarily (cache/database)
        // 3. Pass the token to the gateway route
        
        return url("/gateway/{$application->id}");
    }

    /**
     * Display the student's profile.
     */
    /**
     * Build the student profile form field configuration keyed by field_id.
     *
     * Consumed by the profile step components to drive labels, options,
     * required flags, placeholders and visibility from the database.
     */
    private function getStudentFormConfig(): array
    {
        return ProfileFormField::with('options')
            ->where('profile_type', 'student')
            ->orderBy('sort_order')
            ->get()
            ->keyBy('field_id')
            ->toArray();
    }

    public function profile()
    {
        // $individual = Individual::where('user_guid', Auth::user()->guid)->first();

        // // If no profile exists, redirect to create one
        // if (!$individual) {
        //     return redirect()->route('student.profile.create')
        //         ->with('info', 'Please create your profile to get started.');
        // }

        // return Inertia::render('Student::Profile/Index', [
        //     'individual' => $individual,
        // ]);
        $individual = Individual::where('user_guid', Auth::user()->guid)
            ->with(['addresses', 'employments', 'identities'])
            ->first();

        $this->authorize('update', $individual);

        // Get all active countries for the form
        $countries = Country::getActiveCountries();

        $profileData = $individual ? [
            "general" => $individual,
            "addresses" => $individual->addresses,
            // Pass employment/identity as single objects so the step forms
            // (which read a flat object) display existing values correctly.
            "employments" => $individual->employments->first() ?? (object) [],
            "identities" => $individual->identities->first() ?? (object) [],
        ] : null;

        return Inertia::render('Student::Profile/EditMultiStep', [
            'individual' => $profileData,
            'countries' => $countries,
            'formConfig' => $this->getStudentFormConfig(),
        ]);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Individual::class);
        
        // Get all active countries for the form
        $countries = Country::getActiveCountries();

        // Create empty form structure for new individual using fillable fields
        $individual = [
            'general' => (new Individual())->getFillable(),
            'address' => (new IndividualAddress())->getFillable(),
            'employment' => (new IndividualEmployment())->getFillable(),
            'identity' => (new IndividualIdentity())->getFillable(),
        ];

        return Inertia::render('Student::Profile/CreateMultiStep', [
            'countries' => $countries,
            'individual' => $individual,
            'formConfig' => $this->getStudentFormConfig(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreIndividualMultiStepRequest $request)
    {
        $this->authorize('create', Individual::class);
        
        $validated = $request->validated();
        
        // Extract nested data
        $currentAddress = $validated['current_address'] ?? null;
        $mailingAddress = $validated['mailing_address'] ?? null;
        $currentEmployment = $validated['current_employment'] ?? null;
        $identity = $validated['identity'] ?? null;
        $useDifferentMailing = (bool) ($validated['use_different_mailing_address'] ?? false);
        
        // Remove nested data from main validated array
        unset($validated['current_address'], $validated['mailing_address'], $validated['current_employment'], $validated['identity']);
        unset($validated['use_different_mailing_address'], $validated['career_goals'], $validated['preferred_work_location']);
        
        // Ensure the user_guid is set to the current authenticated user
        $validated['user_guid'] = auth()->user()->guid;

        try {
            // Create the main Individual record
            $individual = Individual::create($validated);

            // Create current address if provided
            if ($currentAddress) {
                $currentAddress['individual_id'] = $individual->id;
                $currentAddress['user_id'] = auth()->user()->id;
                $currentAddress['is_primary'] = true;
                
                IndividualAddress::create($currentAddress);
            }

            // Create mailing address if provided
            if ($mailingAddress && $useDifferentMailing) {
                $mailingAddress['individual_id'] = $individual->id;
                $mailingAddress['user_id'] = auth()->user()->id;
                $mailingAddress['is_primary'] = false;
                
                IndividualAddress::create($mailingAddress);
            }

            // Create employment record if provided
            if ($currentEmployment) {
                $currentEmployment['individual_id'] = $individual->id;
                $currentEmployment['user_id'] = auth()->user()->id;
                $currentEmployment['is_current'] = true;
                $currentEmployment['is_active'] = true;
                
                IndividualEmployment::create($currentEmployment);
            }

            // Create identity record if provided
            if ($identity) {
                $identity['individual_id'] = $individual->id;
                $identity['user_id'] = auth()->user()->id;
                
                IndividualIdentity::create($identity);
            }

            // Fire the IndividualCreated event
            IndividualCreated::dispatch($individual, auth()->user());

            Log::info('Individual created successfully', [
                'individual_guid' => $individual->guid,
                'created_by' => auth()->user()?->guid,
            ]);

            return redirect()
                ->route('student.dashboard')
                ->with('success', 'Individual profile created successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to create individual', [
                'error' => $e->getMessage(),
                'data' => $validated,
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create individual profile. Please try again.']);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        $individual = Individual::where('user_guid', Auth::user()->guid)->first();

        $this->authorize('view', $individual);

        return Inertia::render('Student::Profile/Index', [
            'individual' => $individual,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request)
    {
        $individual = Individual::where('user_guid', Auth::user()->guid)
            ->with(['currentAddress', 'currentEmployment', 'identity'])
            ->first();

        $this->authorize('update', $individual);

        // Get all active countries for the form
        $countries = Country::getActiveCountries();

        $profileData = $individual ? [
            "general" => $individual,
            "addresses" => $individual->addresses,
            // Pass employment/identity as single objects so the step forms
            // (which read a flat object) display existing values correctly.
            "employments" => $individual->currentEmployment ?? $individual->employments->first() ?? (object) [],
            "identities" => $individual->identity ?? $individual->identities->first() ?? (object) [],
        ] : null;

        return Inertia::render('Student::Profile/EditMultiStep', [
            'individual' => $profileData,
            'countries' => $countries,
            'formConfig' => $this->getStudentFormConfig(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateIndividualMultiStepRequest $request)
    {
        $individual = Individual::with(['addresses', 'employments', 'identities'])
            ->where('user_guid', Auth::user()->guid)
            ->first();

        $this->authorize('update', $individual);

        // Store original data for the event
        $originalData = $individual->toArray();
        $validated = $request->validated();

        try {
            // Create a version with current data before updating
            $individual->createVersionBeforeUpdate(
                'Profile updated by user',
                auth()->user()?->guid
            );

            // Extract nested data
            $currentAddress = $validated['current_address'] ?? null;
            $mailingAddress = $validated['mailing_address'] ?? null;
            $currentEmployment = $validated['current_employment'] ?? null;
            $identity = $validated['identity'] ?? null;
            $useDifferentMailing = $validated['use_different_mailing_address'] ?? false;
            
            // Remove nested data from main validated array
            unset($validated['current_address'], $validated['mailing_address'], $validated['current_employment'], $validated['identity']);
            unset($validated['use_different_mailing_address']);

            // Update the individual record
            $individual->update($validated);

            // Update or create current address
            if ($currentAddress) {
                $existingCurrentAddress = $individual->addresses()->where('is_primary', true)->first();
                if ($existingCurrentAddress) {
                    $existingCurrentAddress->update($currentAddress);
                } else {
                    $currentAddress['individual_id'] = $individual->id;
                    $currentAddress['user_id'] = auth()->user()->id;
                    $currentAddress['is_primary'] = true;
                    IndividualAddress::create($currentAddress);
                }
            }

            // Handle mailing address
            $existingMailingAddress = $individual->addresses()->where('is_primary', false)->first();
            if ($useDifferentMailing && $mailingAddress) {
                // Create or update mailing address
                if ($existingMailingAddress) {
                    $existingMailingAddress->update($mailingAddress);
                } else {
                    $mailingAddress['individual_id'] = $individual->id;
                    $mailingAddress['user_id'] = auth()->user()->id;
                    $mailingAddress['is_primary'] = false;
                    IndividualAddress::create($mailingAddress);
                }
            } else {
                // Remove mailing address if not using different mailing address
                if ($existingMailingAddress) {
                    $existingMailingAddress->delete();
                }
            }

            // Update or create employment record
            if ($currentEmployment && !empty(array_filter($currentEmployment))) {
                $existingEmployment = $individual->employments()->where('is_current', true)->first();
                if ($existingEmployment) {
                    $existingEmployment->update($currentEmployment);
                } else {
                    $currentEmployment['individual_id'] = $individual->id;
                    $currentEmployment['user_id'] = auth()->user()->id;
                    $currentEmployment['is_current'] = true;
                    IndividualEmployment::create($currentEmployment);
                }
            }

            // Update or create identity record
            if ($identity) {
                \Log::info('Updating identity record', [
                    'identity_data' => $identity,
                ]);
                $existingIdentity = $individual->identities()->first();
                if ($existingIdentity) {
                    \Log::info('Updating existing identity record', [
                        'identity_id' => $existingIdentity->id,
                        'racial_identity' => $identity['racial_identity'] ?? null,
                        'racial_identity_other_text' => $identity['racial_identity_other_text'] ?? null,
                    ]);
                    $existingIdentity->update($identity);
                } else {
                    \Log::info('Creating new identity record');
                    $identity['individual_id'] = $individual->id;
                    $identity['user_id'] = auth()->user()->id;
                    IndividualIdentity::create($identity);
                }
            }


            // Fire the IndividualUpdated event
            IndividualUpdated::dispatch($individual, $originalData, auth()->user());

            Log::info('Individual updated successfully with version created', [
                'individual_guid' => $individual->guid,
                'updated_by' => auth()->user()?->guid,
            ]);

            return redirect()
                ->route('student.dashboard')
                ->with('success', 'Individual profile updated successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to update individual', [
                'individual_guid' => $individual->guid,
                'error' => $e->getMessage(),
                'data' => $validated,
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update individual profile. Please try again.']);
        }
    }

    /**
     * Display version history for the user's profile.
     */
    public function versions()
    {
        $individual = Individual::where('user_guid', Auth::user()->guid)->first();

        if (!$individual) {
            return redirect()->route('student.profile.create')
                ->with('info', 'Please create your profile first.');
        }

        $versions = $individual->getVersionHistory();

        return Inertia::render('Student::Profile/Versions', [
            'individual' => $individual,
            'versions' => $versions,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        try {
        $individual = Individual::where('user_guid', Auth::user()->guid)->first();

            $this->authorize('delete', $individual);
            
            Log::info('Individual deleted', [
                'individual_guid' => $individual->guid,
                'deleted_by' => auth()->user()?->guid,
            ]);

            $individual->delete();

            return redirect()
                ->route('student.profile.index')
                ->with('success', 'Individual profile deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to delete individual', [
                'individual_guid' => $guid,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withErrors(['error' => 'Failed to delete individual profile. Please try again.']);
        }
    }

    /**
     * Get user-friendly label for database table names
     */
    private function getTableLabel(string $tableName): string
    {
        $availableTables = ApplicationDataPermission::getAvailableTables();
        return $availableTables[$tableName]['label'] ?? ucfirst(str_replace('_', ' ', $tableName));
    }

    /**
     * Check if user's profile is complete for the application's data permissions
     */
    private function checkProfileCompleteness(Application $application): bool
    {
        $user = auth()->user();
        $individual = Individual::where('user_guid', $user->guid)->first();
        
        // If no individual profile exists, profile is incomplete
        if (!$individual) {
            return false;
        }

        // Check if profile requires updating based on CheckProfile middleware logic
        $lastUpdated = $individual->updated_at ?: $individual->created_at;
        $timeAgo = \Carbon\Carbon::now()->subMonths(env('PROFILE_UPDATE_GRACE_PERIOD', 6));
        
        if ($lastUpdated->lt($timeAgo)) {
            return false;
        }

        // Check if all required data permissions fields are populated
        foreach ($application->dataPermissions as $permission) {
            if ($permission->can_read && $this->isFieldRequired($permission)) {
                $value = $this->getFieldValue($individual, $permission);
                if (empty($value)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get missing data message for incomplete profiles
     */
    private function getMissingDataMessage(Application $application): string
    {
        $user = auth()->user();
        $individual = Individual::where('user_guid', $user->guid)->first();
        
        if (!$individual) {
            return 'Profile required - Please create your profile to access this application.';
        }

        // Check if profile needs updating due to age
        $lastUpdated = $individual->updated_at ?: $individual->created_at;
        $timeAgo = \Carbon\Carbon::now()->subMonths(env('PROFILE_UPDATE_GRACE_PERIOD', 6));
        
        if ($lastUpdated->lt($timeAgo)) {
            $months = env('PROFILE_UPDATE_GRACE_PERIOD', 6);
            return "Profile update required - Your profile is more than {$months} months old and needs to be updated.";
        }

        // Check for missing required fields
        $missingFields = [];
        foreach ($application->dataPermissions as $permission) {
            if ($permission->can_read && $this->isFieldRequired($permission)) {
                $value = $this->getFieldValue($individual, $permission);
                if (empty($value)) {
                    $fieldName = $permission->display_name ?: $this->formatFieldName($permission->column_name);
                    $missingFields[] = $fieldName;
                }
            }
        }

        if (!empty($missingFields)) {
            $fields = implode(', ', $missingFields);
            return "Missing required information - Please complete these fields: {$fields}";
        }

        return 'Profile incomplete - Please update your profile to access this application.';
    }

    /**
     * Check if a data permission field is required (exclude optional fields)
     */
    private function isFieldRequired($permission): bool
    {
        // Define optional fields that are not required for application access
        $optionalFields = [
            'id', 'guid', 'user_guid', 'created_at', 'updated_at', 'deleted_at',
            'preferred_name', 'middle_name', 'suffix', 'nickname'
        ];

        return !in_array($permission->column_name, $optionalFields);
    }

    /**
     * Get the value of a field from the individual's profile data
     */
    private function getFieldValue($individual, $permission)
    {
        $tableName = $permission->table_name;
        $columnName = $permission->column_name;

        switch ($tableName) {
            case 'individuals':
                return $individual->{$columnName} ?? null;
            
            case 'individual_addresses':
                $address = $individual->addresses()->first();
                return $address ? $address->{$columnName} : null;
            
            case 'individual_employments':
                $employment = $individual->employments()->first();
                return $employment ? $employment->{$columnName} : null;
            
            case 'individual_identities':
                $identity = $individual->identities()->first();
                return $identity ? $identity->{$columnName} : null;
            
            default:
                return null;
        }
    }

    /**
     * Format field name for display
     */
    private function formatFieldName(string $fieldName): string
    {
        return ucwords(str_replace('_', ' ', $fieldName));
    }

    /**
     * Get default value for a field when related record doesn't exist
     */
    private function getDefaultValueForField(string $columnName)
    {
        // Boolean fields that should default to false when no record exists
        $booleanFields = [
            'is_receiving_employment_insurance',
            'is_participating_in_work_study_program', 
            'is_looking_for_work',
            'is_job_related_to_program',
            'has_career_plan',
            'is_current',
            'latest_version',
            'is_primary'
        ];
        
        if (in_array($columnName, $booleanFields)) {
            return false;
        }
        
        // For other fields, return null (which means they won't be included unless required)
        return null;
    }

    /**
     * Strip the time portion from ISO 8601 datetime strings (e.g. 1966-07-08T00:00:00.000000Z → 1966-07-08).
     * Non-datetime values are returned unchanged.
     * Also handles cases where time portion is present but not in ISO format (e.g. 1966-07-08T00:00:00 → 1966-07-08).
     * Also handles cases where time portion is present with milliseconds but not in ISO format (e.g. 1966-07-08T00:00:00.000 → 1966-07-08).
     * Also handles cases where time portion is present with milliseconds and timezone but not in ISO format (e.g. 1966-07-08T00:00:00.000Z → 1966-07-08).
     * Also handles cases where time portion is present with milliseconds and timezone in ISO format (e.g. 1966-07-08T00:00:00.000Z → 1966-07-08).
     * Also handles cases where time portion is present after a space instead of T (e.g. 1966-07-08 00:00:00 → 1966-07-08).
     */
    private function normalizeDatetimeValue($value)
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}[T ]/', $value)) {
            return substr($value, 0, 10);
        }
        return $value;
    }

    /**
     * Check if individual has a related record for the given table
     */
    private function hasRelatedRecord($individual, string $tableName): bool
    {
        switch ($tableName) {
            case 'individuals':
                return true; // Individual record always exists if we got this far
            case 'individual_addresses':
                return $individual->addresses->count() > 0;
            case 'individual_employments':
                return $individual->employments->count() > 0;
            case 'individual_identities':
                return $individual->identities->count() > 0;
            default:
                return false;
        }
    }

    /**
     * Get permission ID from application's individual permissions by table name and column name
     */
    private function getPermissionIdFromApplication($application, $tableName, $columnName)
    {
        $permission = $application->individualPermissions
            ->where('table_name', $tableName)
            ->where('column_name', $columnName)
            ->first();
        
        return $permission ? $permission->id : null;
    }

    /**
     * Save permission selections for a user and application
     */
    private function savePermissionSelections($individualId, $applicationId, array $permissionSelections)
    {
        IndividualApplicationPermissionSelection::updateSelections($individualId, $applicationId, $permissionSelections);
        
        \Log::info("Saved permission selections", [
            'individual_id' => $individualId,
            'application_id' => $applicationId,
            'selections_count' => count($permissionSelections)
        ]);
    }

    /**
     * Prepare individual data for token based on user's permission selections
     */
    private function prepareIndividualDataForTokenWithSelections($application, $individual, array $permissionSelections)
    {
        $tokenData = [];
        
        // Get all individual permissions for this application
        $individualPermissions = $application->individualPermissions;
        
        \Log::info("Preparing token data with selections", [
            'app_id' => $application->id,
            'individual_permissions_count' => $individualPermissions->count(),
            'permission_selections_count' => count($permissionSelections),
            'individual_id' => $individual->id
        ]);
        
        // Process each individual permission and get data based on user selections
        foreach ($individualPermissions as $permission) {
            if (!$permission->can_read) {
                continue;
            }
            
            $permissionId = $permission->id;
            $isSelected = isset($permissionSelections[$permissionId]) ? (bool)$permissionSelections[$permissionId] : false;
            
            $destinationKey = $permission->destination_field ?: $permission->column_name;

            // If permission is not selected, set value to null
            if (!$isSelected) {
                $tokenData[$destinationKey] = null;
                \Log::debug("Permission not selected, setting to null", [
                    'permission_id' => $permissionId,
                    'column_name' => $permission->column_name,
                    'destination_key' => $destinationKey,
                ]);
                continue;
            }
            
            $value = null;
            $tableName = $permission->table_name;
            $columnName = $permission->column_name;
            
            // Get value based on table name (same logic as before)
            switch ($tableName) {
                case 'individuals':
                    if (in_array($columnName, (new Individual())->getFillable())) {
                        $value = $individual->{$columnName} ?? null;
                    } else {
                        \Log::warning("Column does not exist in individuals table", [
                            'column_name' => $columnName,
                            'permission_id' => $permission->id
                        ]);
                        continue 2;
                    }
                    break;
                    
                case 'individual_addresses':
                    if (in_array($columnName, (new IndividualAddress())->getFillable())) {
                        $primaryAddress = $individual->addresses->where('is_primary', true)->first() 
                                       ?? $individual->addresses->first();
                        if ($primaryAddress) {
                            $value = $primaryAddress->{$columnName} ?? null;
                        } else {
                            $value = $this->getDefaultValueForField($columnName);
                        }
                    } else {
                        \Log::warning("Column does not exist in individual_addresses table", [
                            'column_name' => $columnName,
                            'permission_id' => $permission->id
                        ]);
                        continue 2;
                    }
                    break;
                    
                case 'individual_employments':
                    if (in_array($columnName, (new IndividualEmployment())->getFillable())) {
                        $currentEmployment = $individual->employments->where('is_current', true)->first()
                                          ?? $individual->employments->first();
                        if ($currentEmployment) {
                            $value = $currentEmployment->{$columnName} ?? null;
                        } else {
                            $value = $this->getDefaultValueForField($columnName);
                        }
                    } else {
                        \Log::warning("Column does not exist in individual_employments table", [
                            'column_name' => $columnName,
                            'permission_id' => $permission->id
                        ]);
                        continue 2;
                    }
                    break;
                    
                case 'individual_identities':
                    if (in_array($columnName, (new IndividualIdentity())->getFillable())) {
                        $identity = $individual->identities->first();
                        if ($identity) {
                            $value = $identity->{$columnName} ?? null;
                        } else {
                            $value = $this->getDefaultValueForField($columnName);
                        }
                    } else {
                        \Log::warning("Column does not exist in individual_identities table", [
                            'column_name' => $columnName,
                            'permission_id' => $permission->id
                        ]);
                        continue 2;
                    }
                    break;
                    
                default:
                    \Log::warning("Unknown table name in permission", [
                        'table_name' => $tableName,
                        'column_name' => $columnName,
                        'permission_id' => $permission->id
                    ]);
                    continue 2;
            }
            
            $tokenData[$destinationKey] = $this->normalizeDatetimeValue($value);
            
            \Log::debug("Processing selected permission", [
                'table_name' => $tableName,
                'column_name' => $columnName,
                'destination_key' => $destinationKey,
                'raw_value' => $value,
                'normalized_value' => $tokenData[$destinationKey],
                'is_selected' => $isSelected
            ]);
        }
        
        \Log::info("Token data prepared with selections", [
            'app_id' => $application->id,
            'app_name' => $application->name,
            'token_data_keys' => array_keys($tokenData),
            'token_data_count' => count($tokenData),
            'token_data' => $tokenData
        ]);
        
        return $tokenData;
    }
}
