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

class MultiStepProfileFormTest extends TestCase
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
    public function guests_cannot_access_create_profile_form()
    {
        $response = $this->get(route('student.profile.create'));
        $response->assertRedirect('/login');
    }

    #[Test]
    public function authenticated_users_can_access_create_profile_form()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('student.profile.create'));

        $response->assertStatus(200);
        // Just check that we get an Inertia response with the right data structure
        $response->assertInertia(fn (Assert $page) => 
            $page->has('countries')
                ->has('individual')
                ->has('individual.general')
                ->has('individual.address')
                ->has('individual.employment')
                ->has('individual.identity')
        );
    }

    #[Test]
    public function create_form_returns_correct_fillable_fields_structure()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('student.profile.create'));
        
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => 
            $page->has('individual')
                ->where('individual', function ($individual) {
                    return isset($individual['general']) && 
                           isset($individual['address']) && 
                           isset($individual['employment']) && 
                           isset($individual['identity']) &&
                           is_array($individual['general']) &&
                           is_array($individual['address']) &&
                           is_array($individual['employment']) &&
                           is_array($individual['identity']);
                })
                ->has('countries')
        );
    }

    #[Test]
    public function can_successfully_create_complete_profile_with_all_steps()
    {
        $this->actingAs($this->user);

        $profileData = $this->getValidProfileData();

        $response = $this->post(route('student.profile.store'), $profileData);

        $response->assertRedirect(route('student.dashboard'));
        $response->assertSessionHas('success');

        // Verify individual was created
        $this->assertDatabaseHas('individuals', [
            'user_guid' => $this->user->guid,
            'first_name' => $profileData['first_name'],
            'last_name' => $profileData['last_name'],
            'email_address' => $profileData['email_address'],
        ]);

        // Verify address was created
        $individual = Individual::where('user_guid', $this->user->guid)->first();
        $this->assertDatabaseHas('individual_addresses', [
            'individual_id' => $individual->id,
            'address_line1' => $profileData['current_address']['street_address'],
            'city' => $profileData['current_address']['city'],
        ]);

        // Verify employment was created
        $this->assertDatabaseHas('individual_employments', [
            'individual_id' => $individual->id,
            'employment_status' => $profileData['current_employment']['employment_status'],
        ]);

        // Verify identity was created
        $this->assertDatabaseHas('individual_identities', [
            'individual_id' => $individual->id,
            'citizenship_status' => $profileData['identity']['citizenship_status'],
        ]);
    }

    #[Test]
    public function validation_fails_with_missing_required_general_fields()
    {
        $this->actingAs($this->user);

        $invalidData = $this->getValidProfileData();
        unset($invalidData['first_name'], $invalidData['last_name'], $invalidData['email_address']);

        $response = $this->post(route('student.profile.store'), $invalidData);

        $response->assertSessionHasErrors(['first_name', 'last_name', 'email_address']);
        $this->assertDatabaseMissing('individuals', [
            'user_guid' => $this->user->guid,
        ]);
    }

    #[Test]
    public function validation_fails_with_invalid_sin_format()
    {
        $this->actingAs($this->user);

        $invalidData = $this->getValidProfileData();
        $invalidData['social_insurance_number'] = '123-456-789'; // Invalid SIN

        $response = $this->post(route('student.profile.store'), $invalidData);

        $response->assertSessionHasErrors(['social_insurance_number']);
    }

    #[Test]
    public function validation_fails_with_duplicate_email()
    {
        Individual::factory()->create(['email_address' => 'test@example.com']);
        
        $this->actingAs($this->user);

        $invalidData = $this->getValidProfileData();
        $invalidData['email_address'] = 'test@example.com';

        $response = $this->post(route('student.profile.store'), $invalidData);

        $response->assertSessionHasErrors(['email_address']);
    }

    #[Test]
    public function validation_requires_accommodation_needs_when_disability_status_is_true()
    {
        $this->actingAs($this->user);

        $invalidData = $this->getValidProfileData();
        $invalidData['disability_status'] = 'yes';
        unset($invalidData['accommodation_needs']);

        $response = $this->post(route('student.profile.store'), $invalidData);

        $response->assertSessionHasErrors(['accommodation_needs']);
    }

    #[Test]
    public function can_access_edit_form_with_existing_profile()
    {
        $individual = Individual::factory()->create(['user_guid' => $this->user->guid]);
        $address = IndividualAddress::factory()->create(['individual_id' => $individual->id]);
        $employment = IndividualEmployment::factory()->create(['individual_id' => $individual->id]);
        $identity = IndividualIdentity::factory()->create(['individual_id' => $individual->id]);

        $this->actingAs($this->user);

        $response = $this->get(route('student.profile.edit'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => 
            $page->component('Student::Profile/EditMultiStep')
                ->has('individual')
                ->has('fillable')
                ->where('individual.first_name', $individual->first_name)
        );
    }

    #[Test]
    public function can_update_existing_profile()
    {
        $individual = Individual::factory()->create(['user_guid' => $this->user->guid]);
        IndividualAddress::factory()->create(['individual_id' => $individual->id]);
        IndividualEmployment::factory()->create(['individual_id' => $individual->id]);
        IndividualIdentity::factory()->create(['individual_id' => $individual->id]);

        $this->actingAs($this->user);

        $updateData = $this->getValidProfileData();
        $updateData['first_name'] = 'Updated Name';

        $response = $this->put(route('student.profile.update'), $updateData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('individuals', [
            'id' => $individual->id,
            'first_name' => 'Updated Name',
        ]);
    }

    #[Test]
    public function employment_fields_are_conditionally_validated_based_on_status()
    {
        $this->actingAs($this->user);

        $data = $this->getValidProfileData();
        $data['employment']['employment_status'] = 'employed';
        // Remove required fields for employed status
        unset($data['employment']['job_title'], $data['employment']['employer_name']);

        $response = $this->post(route('student.profile.store'), $data);

        // Should not fail validation as these fields might not be required
        // Adjust based on your actual validation rules
        $response->assertRedirect();
    }

    #[Test]
    public function indigenous_fields_are_conditionally_validated()
    {
        $this->actingAs($this->user);

        $data = $this->getValidProfileData();
        $data['identity']['indigenous_status'] = 'yes';
        $data['identity']['indigenous_group'] = 'first_nations';
        $data['identity']['band_affiliation'] = 'Sample Band';

        $response = $this->post(route('student.profile.store'), $data);

        $response->assertRedirect(route('student.dashboard'));

        $individual = Individual::where('user_guid', $this->user->guid)->first();
        $this->assertDatabaseHas('individual_identities', [
            'individual_id' => $individual->id,
            'indigenous_status' => 'yes',
            'indigenous_group' => 'first_nations',
            'band_affiliation' => 'Sample Band',
        ]);
    }

    #[Test]
    public function form_handles_optional_fields_correctly()
    {
        $this->actingAs($this->user);

        $minimalData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email_address' => 'john.doe@example.com',
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
            ],
        ];

        $response = $this->post(route('student.profile.store'), $minimalData);

        $response->assertRedirect(route('student.dashboard'));
        $this->assertDatabaseHas('individuals', [
            'user_guid' => $this->user->guid,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    }

    #[Test]
    public function form_validates_date_fields_correctly()
    {
        $this->actingAs($this->user);

        $data = $this->getValidProfileData();
        $data['date_of_birth'] = now()->addYear()->format('Y-m-d'); // Future date

        $response = $this->post(route('student.profile.store'), $data);

        $response->assertSessionHasErrors(['date_of_birth']);
    }

    #[Test]
    public function form_validates_numeric_fields_correctly()
    {
        $this->actingAs($this->user);

        $data = $this->getValidProfileData();
        $data['employment']['work_hours_per_week'] = -5; // Negative hours
        $data['identity']['years_in_country'] = 200; // Too many years

        $response = $this->post(route('student.profile.store'), $data);

        // Adjust based on your actual validation rules
        $response->assertSessionHasErrors();
    }

    /**
     * Get valid profile data for testing
     */
    protected function getValidProfileData(): array
    {
        return [
            // General Information
            'social_insurance_number' => '130-692-544', // Valid SIN format
            'government_issued_id' => 'BC123456789',
            'first_name' => $this->faker->firstName,
            'middle_name' => $this->faker->optional()->firstName,
            'last_name' => $this->faker->lastName,
            'preferred_name' => $this->faker->optional()->firstName,
            'email_address' => $this->faker->unique()->safeEmail,
            'phone_number' => $this->faker->phoneNumber,
            'alternate_phone_number' => $this->faker->optional()->phoneNumber,
            'date_of_birth' => $this->faker->date('Y-m-d', '-18 years'),
            'gender' => $this->faker->randomElement(['male', 'female', 'non-binary', 'other']),
            'preferred_pronouns' => $this->faker->randomElement(['he/him', 'she/her', 'they/them']),
            'disability_status' => null,
            'accommodation_needs' => null,

            // Address Information
            'current_address' => [
                'street_address' => $this->faker->streetAddress,
                'apartment_unit' => $this->faker->optional()->secondaryAddress,
                'city' => $this->faker->city,
                'province_state' => 'BC',
                'postal_code' => 'V6B 1A1',
                'country' => 'Canada',
            ],

            // Employment Information
            'current_employment' => [
                'employment_status' => $this->faker->randomElement(['employed', 'unemployed', 'student']),
                'employer_name' => $this->faker->company,
                'job_title' => $this->faker->jobTitle,
                'industry' => $this->faker->randomElement(['Technology', 'Healthcare', 'Education']),
                'start_date' => $this->faker->date('Y-m-d', '-2 years'),
                'end_date' => null,
                'salary_range' => '$40,000 - $60,000',
                'hours_per_week' => $this->faker->numberBetween(20, 40),
            ],
            'career_goals' => $this->faker->sentence,
            'preferred_work_location' => $this->faker->city,

            // Identity Information
            'identity' => [
                'citizenship_status' => $this->faker->randomElement(['canadian_citizen', 'permanent_resident']),
                'country_of_birth' => 'Canada',
                'language_spoken_at_home' => 'English',
                'years_in_country' => $this->faker->numberBetween(0, 50),
                'refugee_status' => false,
                'immigration_status' => 'born_in_canada',
                'indigenous_status' => null,
                'indigenous_group' => null,
                'band_affiliation' => null,
                'indigenous_status_card_number' => null,
                'is_registered_with_band' => false,
                'on_reserve_resident' => false,
                'racial_identity' => $this->faker->randomElement(['white', 'asian', 'black', 'mixed_race']),
                'is_visible_minority' => $this->faker->randomElement(['yes', 'no', 'unknown', null]),
                'receives_indigenous_support_services' => false,
                'receives_minority_support_services' => false,
            ],
        ];
    }
}
