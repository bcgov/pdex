<?php

namespace Tests\Feature\Student;

use App\Models\User;
use App\Models\Role;
use App\Models\Individual;
use App\Models\IndividualAddress;
use App\Models\IndividualEmployment;
use App\Models\IndividualIdentity;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class ProfileFormUiUxTest extends TestCase
{
    use WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create student role using factory
        $studentRole = Role::factory()->student()->create();
        
        // Create a BC Services Card user for student access
        $this->user = User::factory()->create([
            'identity_provider' => 'bcsc',
            'bcsc_user_guid' => fake()->uuid(),
        ]);
        
        // Assign student role to user
        $this->user->roles()->attach($studentRole);
    }

    #[Test]
    public function create_form_displays_correct_progress_structure()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('student.profile.create'));
        
        $response->assertInertia(fn (Assert $page) => 
            $page->component('Student::Profile/CreateMultiStep')
                ->has('fillable')
                ->where('fillable', function ($fillable) {
                    return isset($fillable['general']) && 
                           isset($fillable['address']) && 
                           isset($fillable['employment']) && 
                           isset($fillable['identity']);
                })
        );
    }

    #[Test]
    public function edit_form_pre_populates_existing_data_correctly()
    {
        $individual = Individual::factory()->create([
            'user_guid' => $this->user->guid,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email_address' => 'john@example.com',
        ]);

        $address = IndividualAddress::factory()->create([
            'individual_id' => $individual->id,
            'address_line1' => '123 Test Street',
            'city' => 'Vancouver',
        ]);

        $employment = IndividualEmployment::factory()->create([
            'individual_id' => $individual->id,
            'employment_status' => 'employed',
            'job_title' => 'Software Developer',
        ]);

        $identity = IndividualIdentity::factory()->create([
            'individual_id' => $individual->id,
            'citizenship_status' => 'canadian_citizen',
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('student.profile.edit'));

        $response->assertInertia(fn (Assert $page) => 
            $page->component('Student::Profile/EditMultiStep')
                ->where('individual.first_name', 'John')
                ->where('individual.last_name', 'Doe')
                ->where('individual.email_address', 'john@example.com')
                ->has('individual.addresses.0')
                ->has('individual.employments.0')
                ->has('individual.identities.0')
        );
    }

    #[Test]
    public function form_validation_messages_are_properly_formatted()
    {
        $this->actingAs($this->user);

        // Submit empty form to trigger validation errors
        $response = $this->post(route('student.profile.store'), []);

        $response->assertSessionHasErrors();
        
        // Check that the response can be handled by the frontend
        $errors = session()->get('errors')->getBag('default');
        $this->assertIsObject($errors);
        
        // Verify specific validation messages exist
        $this->assertTrue($errors->has('first_name'));
        $this->assertTrue($errors->has('last_name'));
        $this->assertTrue($errors->has('email_address'));
    }

    #[Test]
    public function form_handles_conditional_field_validation_employment_status()
    {
        $this->actingAs($this->user);

        // Test unemployed status - should not require job details
        $unemployedData = $this->getMinimalValidData();
        $unemployedData['employment'] = [
            'employment_status' => 'unemployed',
            'is_looking_for_work' => true,
        ];

        $response = $this->post(route('student.profile.store'), $unemployedData);
        $response->assertRedirect(route('student.dashboard'));

        // Test employed status - might require additional fields (adjust based on your validation)
        $employedData = $this->getMinimalValidData();
        $employedData['email_address'] = 'different@example.com'; // Unique email
        $employedData['employment'] = [
            'employment_status' => 'employed',
            'job_title' => 'Developer',
            'employer_name' => 'Tech Company',
        ];

        $response = $this->post(route('student.profile.store'), $employedData);
        $response->assertRedirect(route('student.dashboard'));
    }

    #[Test]
    public function form_handles_conditional_field_validation_indigenous_status()
    {
        $this->actingAs($this->user);

        // Test non-indigenous status
        $nonIndigenousData = $this->getMinimalValidData();
        $nonIndigenousData['identity']['indigenous_status'] = 'no';

        $response = $this->post(route('student.profile.store'), $nonIndigenousData);
        $response->assertRedirect(route('student.dashboard'));

        // Test indigenous status with additional fields
        $indigenousData = $this->getMinimalValidData();
        $indigenousData['email_address'] = 'indigenous@example.com'; // Unique email
        $indigenousData['identity']['indigenous_status'] = 'yes';
        $indigenousData['identity']['indigenous_group'] = 'first_nations';
        $indigenousData['identity']['band_affiliation'] = 'Sample Band';

        $response = $this->post(route('student.profile.store'), $indigenousData);
        $response->assertRedirect(route('student.dashboard'));

        // Verify the indigenous data was saved
        $individual = Individual::where('email_address', 'indigenous@example.com')->first();
        $this->assertDatabaseHas('individual_identities', [
            'individual_id' => $individual->id,
            'indigenous_status' => 'yes',
            'indigenous_group' => 'first_nations',
            'band_affiliation' => 'Sample Band',
        ]);
    }

    #[Test]
    public function form_handles_conditional_field_validation_disability_status()
    {
        $this->actingAs($this->user);

        // Test without disability - should not require accommodation needs
        $noDisabilityData = $this->getMinimalValidData();
        $noDisabilityData['disability_status'] = 'no';

        $response = $this->post(route('student.profile.store'), $noDisabilityData);
        $response->assertRedirect(route('student.dashboard'));

        // Test with disability - should require accommodation needs
        $disabilityData = $this->getMinimalValidData();
        $disabilityData['email_address'] = 'disability@example.com'; // Unique email
        $disabilityData['disability_status'] = 'yes';
        $disabilityData['accommodation_needs'] = 'Need wheelchair access';

        $response = $this->post(route('student.profile.store'), $disabilityData);
        $response->assertRedirect(route('student.dashboard'));

        // Test with disability but no accommodation needs - should fail
        $invalidDisabilityData = $this->getMinimalValidData();
        $invalidDisabilityData['email_address'] = 'invalid@example.com';
        $invalidDisabilityData['disability_status'] = 'yes';
        // Don't include accommodation_needs

        $response = $this->post(route('student.profile.store'), $invalidDisabilityData);
        $response->assertSessionHasErrors(['accommodation_needs']);
    }

    #[Test]
    public function form_properly_structures_nested_data_for_related_models()
    {
        $this->actingAs($this->user);

        $validData = $this->getCompleteValidData();
        
        $response = $this->post(route('student.profile.store'), $validData);
        $response->assertRedirect(route('student.dashboard'));

        $individual = Individual::where('user_guid', $this->user->guid)->first();
        
        // Verify main individual data
        $this->assertEquals($validData['first_name'], $individual->first_name);
        $this->assertEquals($validData['email_address'], $individual->email_address);

        // Verify address relationship and data
        $this->assertCount(1, $individual->addresses);
        $address = $individual->addresses->first();
        $this->assertEquals($validData['address']['address_line1'], $address->address_line1);
        $this->assertEquals($validData['address']['city'], $address->city);

        // Verify employment relationship and data
        $this->assertCount(1, $individual->employments);
        $employment = $individual->employments->first();
        $this->assertEquals($validData['employment']['employment_status'], $employment->employment_status);

        // Verify identity relationship and data
        $this->assertCount(1, $individual->identities);
        $identity = $individual->identities->first();
        $this->assertEquals($validData['identity']['citizenship_status'], $identity->citizenship_status);
    }

    #[Test]
    public function form_handles_boolean_field_conversion_correctly()
    {
        $this->actingAs($this->user);

        $data = $this->getMinimalValidData();
        $data['disability_status'] = 'true'; // String instead of boolean
        $data['employment']['is_looking_for_work'] = '1'; // String instead of boolean
        $data['identity']['indigenous_status'] = 'false'; // String instead of boolean

        $response = $this->post(route('student.profile.store'), $data);
        $response->assertRedirect(route('student.dashboard'));

        $individual = Individual::where('user_guid', $this->user->guid)->first();
        
        // Verify boolean conversion
        $this->assertTrue($individual->disability_status);
        $this->assertTrue($individual->employments->first()->is_looking_for_work);
        $this->assertFalse($individual->identities->first()->indigenous_status);
    }

    #[Test]
    public function form_handles_numeric_field_validation_correctly()
    {
        $this->actingAs($this->user);

        $validData = $this->getMinimalValidData();
        $validData['employment']['work_hours_per_week'] = 40;
        $validData['employment']['monthly_income'] = 5000;
        $validData['identity']['years_in_country'] = 25;

        $response = $this->post(route('student.profile.store'), $validData);
        $response->assertRedirect(route('student.dashboard'));

        $individual = Individual::where('user_guid', $this->user->guid)->first();
        $employment = $individual->employments->first();
        $identity = $individual->identities->first();

        $this->assertEquals(40, $employment->work_hours_per_week);
        $this->assertEquals(5000, $employment->monthly_income);
        $this->assertEquals(25, $identity->years_in_country);
    }

    #[Test]
    public function form_prevents_duplicate_submissions_with_same_email()
    {
        // Create an existing individual
        Individual::factory()->create(['email_address' => 'duplicate@example.com']);

        $this->actingAs($this->user);

        $data = $this->getMinimalValidData();
        $data['email_address'] = 'duplicate@example.com';

        $response = $this->post(route('student.profile.store'), $data);
        $response->assertSessionHasErrors(['email_address']);

        // Verify no new individual was created
        $this->assertEquals(1, Individual::where('email_address', 'duplicate@example.com')->count());
    }

    #[Test]
    public function form_properly_handles_optional_fields_as_null()
    {
        $this->actingAs($this->user);

        $data = $this->getMinimalValidData();
        // Don't include optional fields to test null handling
        
        $response = $this->post(route('student.profile.store'), $data);
        $response->assertRedirect(route('student.dashboard'));

        $individual = Individual::where('user_guid', $this->user->guid)->first();
        
        // Verify optional fields are properly handled as null
        $this->assertNull($individual->middle_name);
        $this->assertNull($individual->preferred_name);
        $this->assertNull($individual->phone_number);
        $this->assertNull($individual->alternate_phone_number);
    }

    /**
     * Get minimal valid data for testing
     */
    protected function getMinimalValidData(): array
    {
        return [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email_address' => 'john.doe@example.com',
            'disability_status' => null,
            'address' => [
                'address_line1' => '123 Main St',
                'city' => 'Vancouver',
                'province' => 'BC',
                'postal_code' => 'V6B 1A1',
                'country' => 'Canada',
                'address_type' => 'current',
                'is_primary' => true,
                'is_active' => true,
            ],
            'employment' => [
                'employment_status' => 'unemployed',
                'is_looking_for_work' => true,
            ],
            'identity' => [
                'citizenship_status' => 'canadian_citizen',
                'country_of_birth' => 'Canada',
                'indigenous_status' => null,
                'is_visible_minority' => null,
            ],
        ];
    }

    /**
     * Get complete valid data for testing
     */
    protected function getCompleteValidData(): array
    {
        return [
            'social_insurance_number' => '130-692-544',
            'government_issued_id' => 'BC123456789',
            'first_name' => 'Jane',
            'middle_name' => 'Marie',
            'last_name' => 'Smith',
            'preferred_name' => 'Janey',
            'email_address' => 'jane.smith@example.com',
            'phone_number' => '604-555-0123',
            'alternate_phone_number' => '778-555-0456',
            'date_of_birth' => '1990-05-15',
            'gender' => 'female',
            'preferred_pronouns' => 'she/her',
            'disability_status' => 'no',
            'address' => [
                'address_line1' => '456 Oak Avenue',
                'address_line2' => 'Suite 123',
                'city' => 'Victoria',
                'province' => 'BC',
                'postal_code' => 'V8W 1A1',
                'country' => 'Canada',
                'address_type' => 'current',
                'is_primary' => true,
                'is_active' => true,
            ],
            'employment' => [
                'employment_status' => 'employed',
                'is_looking_for_work' => false,
                'job_title' => 'Marketing Coordinator',
                'employer_name' => 'ABC Marketing Inc.',
                'employer_industry' => 'Marketing',
                'employment_start_date' => '2022-01-15',
                'work_hours_per_week' => 37,
                'monthly_income' => 4500,
                'is_job_related_to_program' => true,
                'career_interest_area' => 'Digital Marketing',
                'desired_job_title' => 'Marketing Manager',
                'career_readiness_level' => 'proficient',
                'has_career_plan' => true,
                'is_receiving_employment_insurance' => false,
                'is_participating_in_work_study_program' => false,
            ],
            'identity' => [
                'citizenship_status' => 'canadian_citizen',
                'country_of_birth' => 'Canada',
                'language_spoken_at_home' => 'English',
                'years_in_country' => 33,
                'refugee_status' => false,
                'immigration_status' => 'born_in_canada',
                'indigenous_status' => 'no',
                'racial_identity' => 'white',
                'is_visible_minority' => 'no',
                'receives_indigenous_support_services' => false,
                'receives_minority_support_services' => false,
            ],
        ];
    }
}
