<?php

namespace Modules\Student\Http\Requests;

use App\Models\Individual;
use App\Rules\ValidSin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIndividualMultiStepRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $individual = Individual::where('user_guid', auth()->user()->guid)->first();
        $individualId = $individual ? $individual->id : null;

        return [
            // General Information Step
            'social_insurance_number' => ['nullable', 'string', 'max:255', 
                new ValidSin(), 
                Rule::unique('individuals', 'social_insurance_number')->ignore($individualId)
            ],
            // 'government_issued_id' => 'nullable|string|max:255',
            'provincial_education_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('individuals', 'provincial_education_number')->ignore($individualId),
            ],
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            // 'preferred_name' => 'nullable|string|max:255',
            'email_address' => [
                'required',
                'email',
            ],
            // 'phone_number' => 'nullable|string|max:255',
            // 'alternate_phone_number' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|string|in:man,woman,non-binary,unknown',
            'sex' => 'nullable|string|in:male,female,indeterminate,unknown',
            // 'preferred_pronouns' => 'nullable|string|max:255',
            // 'disability_status' => 'boolean',
            // 'accommodation_needs' => 'nullable|string|required_if:disability_status,true',

            // Address Information Step
            // 'current_address' => 'nullable|array',
            // 'current_address.address_line1' => 'nullable|string|max:255',
            // 'current_address.address_line2' => 'nullable|string|max:50',
            // 'current_address.city' => 'nullable|string|max:255',
            // 'current_address.province' => 'nullable|string|max:255',
            // 'current_address.postal_code' => 'nullable|string|max:20',
            // 'current_address.country' => 'nullable|string|max:255',
            
            // 'use_different_mailing_address' => 'boolean',
            // 'mailing_address' => 'nullable|array|required_if:use_different_mailing_address,true',
            // 'mailing_address.address_line1' => 'nullable|string|max:255|required_if:use_different_mailing_address,true',
            // 'mailing_address.address_line2' => 'nullable|string|max:50',
            // 'mailing_address.city' => 'nullable|string|max:255|required_if:use_different_mailing_address,true',
            // 'mailing_address.province' => 'nullable|string|max:255|required_if:use_different_mailing_address,true',
            // 'mailing_address.postal_code' => 'nullable|string|max:20|required_if:use_different_mailing_address,true',
            // 'mailing_address.country' => 'nullable|string|max:255|required_if:use_different_mailing_address,true',

            // // Employment Information Step
            // 'current_employment' => 'nullable|array',
            // 'current_employment.employment_status' => 'nullable|string|max:255',
            // 'current_employment.is_looking_for_work' => 'boolean',
            // 'current_employment.job_title' => 'nullable|string|max:255',
            // 'current_employment.employer_name' => 'nullable|string|max:255',
            // 'current_employment.employer_industry' => 'nullable|string|max:255',
            // 'current_employment.employment_start_date' => 'nullable|date',
            // 'current_employment.employment_end_date' => 'nullable|date|after:current_employment.employment_start_date',
            // 'current_employment.work_hours_per_week' => 'nullable|integer|min:0|max:168',
            // 'current_employment.monthly_income' => 'nullable|numeric|min:0',
            // 'current_employment.is_job_related_to_program' => 'boolean',
            // 'current_employment.previous_job_title' => 'nullable|string|max:255',
            // 'current_employment.previous_employer_name' => 'nullable|string|max:255',
            // 'current_employment.previous_employment_start_date' => 'nullable|date',
            // 'current_employment.previous_employment_end_date' => 'nullable|date|after:current_employment.previous_employment_start_date',
            // 'current_employment.reason_for_leaving' => 'nullable|string|max:255',
            // 'current_employment.career_interest_area' => 'nullable|string|max:255',
            // 'current_employment.desired_job_title' => 'nullable|string|max:255',
            // 'current_employment.career_readiness_level' => 'nullable|string|max:255',
            // 'current_employment.has_career_plan' => 'boolean',
            // 'current_employment.is_receiving_employment_insurance' => 'boolean',
            // 'current_employment.is_participating_in_work_study_program' => 'boolean',
            // 'current_employment.barriers_to_employment' => 'nullable|string',

            // // Identity Information Step
            // 'identity' => 'nullable|array',
            // 'identity.citizenship_status' => 'nullable|string|max:255',
            // 'identity.country_of_birth' => 'nullable|string|max:255',
            // 'identity.language_spoken_at_home' => 'nullable|string|max:255',
            // 'identity.years_in_country' => 'nullable|integer|min:0',
            // 'identity.refugee_status' => 'boolean',
            // 'identity.immigration_status' => 'nullable|string|max:255',
            // 'identity.indigenous_status' => 'boolean',
            // 'identity.indigenous_group' => 'nullable|array',
            // 'identity.indigenous_group.*' => 'string|max:255',
            // 'identity.band_affiliation' => 'nullable|string|max:255',
            // 'identity.indigenous_status_card_number' => 'nullable|string|max:255',
            // 'identity.is_registered_with_band' => 'boolean',
            // 'identity.on_reserve_resident' => 'boolean',
            // 'identity.racial_identity' => 'nullable|array',
            // 'identity.racial_identity.*' => 'string|max:255',
            // 'identity.racial_identity_other_text' => 'nullable|string|max:200',
            // 'identity.is_visible_minority' => 'boolean',
            // 'identity.receives_indigenous_support_services' => 'boolean',
            // 'identity.receives_minority_support_services' => 'boolean',
            // 'metadata' => 'nullable|array',

        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'social_insurance_number.required' => 'Social Insurance Number is required.',
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'email_address.required' => 'Email address is required.',
            'email_address.unique' => 'This email address is already registered.',
            'date_of_birth.before' => 'Date of birth must be in the past.',
            
            // Address validation messages
            // 'current_address.address_line1.required' => 'Current street address is required.',
            // 'current_address.city.required' => 'Current city is required.',
            // 'current_address.province.required' => 'Current province/state is required.',
            // 'current_address.postal_code.required' => 'Current postal code is required.',
            // 'current_address.country.required' => 'Current country is required.',
            
            // 'mailing_address.address_line1.required_if' => 'Mailing street address is required when using a different mailing address.',
            // 'mailing_address.city.required_if' => 'Mailing city is required when using a different mailing address.',
            // 'mailing_address.province.required_if' => 'Mailing province/state is required when using a different mailing address.',
            // 'mailing_address.postal_code.required_if' => 'Mailing postal code is required when using a different mailing address.',
            // 'mailing_address.country.required_if' => 'Mailing country is required when using a different mailing address.',
            
            // // Employment validation messages
            // 'current_employment.employment_end_date.after' => 'Employment end date must be after the start date.',
            // 'current_employment.work_hours_per_week.min' => 'Hours per week must be at least 0.',
            // 'current_employment.work_hours_per_week.max' => 'Hours per week cannot exceed 168.',
            // 'current_employment.monthly_income.min' => 'Monthly income must be a positive number.',
            // 'current_employment.previous_employment_end_date.after' => 'Previous employment end date must be after the start date.',
            // 'current_employment.years_in_country.min' => 'Years in country must be a positive number.',
            
            // // Identity validation messages
            // 'identity.indigenous_group.required_if' => 'Indigenous group is required when Indigenous status is selected.',
            // 'identity.years_in_country.min' => 'Years in country must be a positive number.',
            
            // 'accommodation_needs.required_if' => 'Accommodation details are required when requesting accessibility support.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Clean SIN: remove dashes and store only digits
        if ($this->has('social_insurance_number') && $this->social_insurance_number) {
            $this->merge([
                'social_insurance_number' => preg_replace('/[^0-9]/', '', $this->social_insurance_number)
            ]);
        }

        // Ensure boolean fields are properly cast
        if (!$this->has('disability_status')) {
            $this->merge(['disability_status' => false]);
        }

        if (!$this->has('use_different_mailing_address')) {
            $this->merge(['use_different_mailing_address' => false]);
        }

        // Set nested boolean fields for identity while preserving other fields
        $identity = $this->input('identity', []);
        if ($this->has('identity')) {
            
            // Set default boolean values only if not present
            $identity['refugee_status'] = $identity['refugee_status'] ?? false;
            $identity['indigenous_status'] = $identity['indigenous_status'] ?? false;
            $identity['is_registered_with_band'] = $identity['is_registered_with_band'] ?? false;
            $identity['on_reserve_resident'] = $identity['on_reserve_resident'] ?? false;
            $identity['is_visible_minority'] = $identity['is_visible_minority'] ?? false;
            $identity['receives_indigenous_support_services'] = $identity['receives_indigenous_support_services'] ?? false;
            $identity['receives_minority_support_services'] = $identity['receives_minority_support_services'] ?? false;
            
        }

        // Set racial identity for mini profile
        $identity['racial_identity'] = $this->input('racial_identity', []);
        $identity['racial_identity_other_text'] = $this->input('racial_identity_other_text', null);
        $this->merge(['identity' => $identity]);

        // Set employment boolean fields
        $employment = $this->input('current_employment', []);
        if (!isset($employment['is_looking_for_work'])) {
            $employment['is_looking_for_work'] = false;
        }
        if (!isset($employment['is_job_related_to_program'])) {
            $employment['is_job_related_to_program'] = false;
        }
        if (!isset($employment['has_career_plan'])) {
            $employment['has_career_plan'] = false;
        }
        if (!isset($employment['is_receiving_employment_insurance'])) {
            $employment['is_receiving_employment_insurance'] = false;
        }
        if (!isset($employment['is_participating_in_work_study_program'])) {
            $employment['is_participating_in_work_study_program'] = false;
        }
        $this->merge(['current_employment' => $employment]);
    }
}
