<?php

namespace Database\Seeders;

use App\Models\ProfileFormField;
use App\Models\ProfileFormFieldOption;
use Illuminate\Database\Seeder;

class ProfileFormFieldSeeder extends Seeder
{
    private const PREFER_NOT_TO_ANSWER_LABEL = 'Prefer not to answer';
    private const INDIGENOUS_IDENTITY_SECTION = 'Indigenous Identity';

    private array $profileAnswerOptions = [
        ['value' => 'yes', 'label' => 'Yes'],
        ['value' => 'no', 'label' => 'No'],
        ['value' => 'unknown', 'label' => self::PREFER_NOT_TO_ANSWER_LABEL],
    ];

    /**
     * Seed the student profile form field definitions.
     *
     * These mirror the fields currently hard-coded in the Student profile
     * multi-step Vue components so they can be managed from the DB.
     */
    public function run(): void
    {
        // Clear existing student definitions to avoid duplicates on re-seed.
        $existingIds = ProfileFormField::where('profile_type', 'student')->pluck('id');
        ProfileFormFieldOption::whereIn('profile_form_field_id', $existingIds)->delete();
        ProfileFormField::where('profile_type', 'student')->delete();

        foreach ($this->fields() as $sortOrder => $field) {
            $options = $field['options'] ?? [];
            unset($field['options']);

            $field['profile_type'] = 'student';
            $field['sort_order'] = $sortOrder;
            $field['is_active'] = true;

            $record = ProfileFormField::create($field);

            foreach ($options as $optionOrder => $option) {
                ProfileFormFieldOption::create([
                    'profile_form_field_id' => $record->id,
                    'value' => $option['value'],
                    'label' => $option['label'],
                    'is_default' => $option['is_default'] ?? false,
                    'sort_order' => $optionOrder,
                ]);
            }
        }
    }

    /**
     * The full catalog of student profile fields grouped by tab and section.
     */
    private function fields(): array
    {
        return array_merge(
            $this->generalFields(),
            $this->addressFields(),
            $this->employmentFields(),
            $this->identityFields(),
        );
    }

    private function generalFields(): array
    {
        return [
            // Name Information
            ['tab' => 'general', 'section' => 'Name Information', 'field_id' => 'first_name', 'label' => 'First Name', 'type' => 'text', 'required' => true, 'placeholder' => null, 'multi_select' => false, 'help_text' => null],
            ['tab' => 'general', 'section' => 'Name Information', 'field_id' => 'middle_name', 'label' => 'Middle Name', 'type' => 'text', 'required' => false, 'placeholder' => null, 'multi_select' => false, 'help_text' => null],
            ['tab' => 'general', 'section' => 'Name Information', 'field_id' => 'last_name', 'label' => 'Last Name', 'type' => 'text', 'required' => true, 'placeholder' => null, 'multi_select' => false, 'help_text' => null],
            ['tab' => 'general', 'section' => 'Name Information', 'field_id' => 'preferred_name', 'label' => 'Preferred Name', 'type' => 'text', 'required' => false, 'placeholder' => null, 'multi_select' => false, 'help_text' => null],

            // Personal Details
            ['tab' => 'general', 'section' => 'Personal Details', 'field_id' => 'date_of_birth', 'label' => 'Date of Birth', 'type' => 'date', 'required' => true, 'placeholder' => null, 'multi_select' => false, 'help_text' => null],
            ['tab' => 'general', 'section' => 'Personal Details', 'field_id' => 'gender', 'label' => 'Gender', 'type' => 'select', 'required' => false, 'placeholder' => 'Select Gender', 'multi_select' => false, 'help_text' => null, 'options' => [
                ['value' => 'man', 'label' => 'Man/Boy'],
                ['value' => 'woman', 'label' => 'Woman/Girl'],
                ['value' => 'non-binary', 'label' => 'Non-binary'],
                ['value' => 'unknown', 'label' => self::PREFER_NOT_TO_ANSWER_LABEL],
            ]],
            ['tab' => 'general', 'section' => 'Personal Details', 'field_id' => 'sex', 'label' => 'Sex', 'type' => 'select', 'required' => false, 'placeholder' => 'Select Sex', 'multi_select' => false, 'help_text' => null, 'options' => [
                ['value' => 'male', 'label' => 'Male'],
                ['value' => 'female', 'label' => 'Female'],
                ['value' => 'indeterminate', 'label' => 'Indeterminate'],
                ['value' => 'unknown', 'label' => self::PREFER_NOT_TO_ANSWER_LABEL],
            ]],
            ['tab' => 'general', 'section' => 'Personal Details', 'field_id' => 'preferred_pronouns', 'label' => 'Preferred Pronouns', 'type' => 'text', 'required' => false, 'placeholder' => 'e.g., he/him, she/her, they/them', 'multi_select' => false, 'help_text' => null],

            // Contact Information
            ['tab' => 'general', 'section' => 'Contact Information', 'field_id' => 'email_address', 'label' => 'Email Address', 'type' => 'email', 'required' => true, 'placeholder' => null, 'multi_select' => false, 'help_text' => null],
            ['tab' => 'general', 'section' => 'Contact Information', 'field_id' => 'phone_number', 'label' => 'Phone Number', 'type' => 'tel', 'required' => false, 'placeholder' => '(000) 000-0000', 'multi_select' => false, 'help_text' => null],
            ['tab' => 'general', 'section' => 'Contact Information', 'field_id' => 'alternate_phone_number', 'label' => 'Alternate Phone Number', 'type' => 'tel', 'required' => false, 'placeholder' => '(000) 000-0000', 'multi_select' => false, 'help_text' => null],

            // Identity Numbers
            ['tab' => 'general', 'section' => 'Identity Numbers', 'field_id' => 'social_insurance_number', 'label' => 'Social Insurance Number (SIN)', 'type' => 'text', 'required' => false, 'placeholder' => '000-000-000', 'multi_select' => false, 'help_text' => null],
            ['tab' => 'general', 'section' => 'Identity Numbers', 'field_id' => 'provincial_education_number', 'label' => 'Provincial Education Number (PEN)', 'type' => 'text', 'required' => false, 'placeholder' => 'e.g., BC Student Number', 'multi_select' => false, 'help_text' => null],
            ['tab' => 'general', 'section' => 'Identity Numbers', 'field_id' => 'government_issued_id', 'label' => 'Government ID Number', 'type' => 'text', 'required' => false, 'placeholder' => "Driver's License, Health Card, etc.", 'multi_select' => false, 'help_text' => null],

            // Accessibility & Accommodation
            ['tab' => 'general', 'section' => 'Accessibility & Accommodation', 'field_id' => 'disability_status', 'label' => 'I have a disability or accessibility needs', 'type' => 'select', 'required' => false, 'placeholder' => null, 'multi_select' => false, 'help_text' => 'Controls visibility of accommodation needs field', 'options' => $this->profileAnswerOptions],
            ['tab' => 'general', 'section' => 'Accessibility & Accommodation', 'field_id' => 'accommodation_needs', 'label' => 'Accommodation Needs', 'type' => 'textarea', 'required' => false, 'placeholder' => 'Please describe any accommodations you require', 'multi_select' => false, 'help_text' => 'Shown only when disability status is Yes'],
        ];
    }

    private function addressFields(): array
    {
        $provinceOptions = [
            ['value' => 'AB', 'label' => 'Alberta'],
            ['value' => 'BC', 'label' => 'British Columbia'],
            ['value' => 'MB', 'label' => 'Manitoba'],
            ['value' => 'NB', 'label' => 'New Brunswick'],
            ['value' => 'NL', 'label' => 'Newfoundland and Labrador'],
            ['value' => 'NS', 'label' => 'Nova Scotia'],
            ['value' => 'ON', 'label' => 'Ontario'],
            ['value' => 'PE', 'label' => 'Prince Edward Island'],
            ['value' => 'QC', 'label' => 'Quebec'],
            ['value' => 'SK', 'label' => 'Saskatchewan'],
            ['value' => 'NT', 'label' => 'Northwest Territories'],
            ['value' => 'NU', 'label' => 'Nunavut'],
            ['value' => 'YT', 'label' => 'Yukon'],
        ];

        return [
            // Current Address
            ['tab' => 'address', 'section' => 'Current Address', 'field_id' => 'address_line1', 'label' => 'Address Line 1', 'type' => 'text', 'required' => true, 'placeholder' => 'Street number and name', 'multi_select' => false, 'help_text' => null],
            ['tab' => 'address', 'section' => 'Current Address', 'field_id' => 'address_line2', 'label' => 'Address Line 2', 'type' => 'text', 'required' => false, 'placeholder' => 'Apt, Suite, Unit', 'multi_select' => false, 'help_text' => null],
            ['tab' => 'address', 'section' => 'Current Address', 'field_id' => 'city', 'label' => 'City', 'type' => 'text', 'required' => false, 'placeholder' => null, 'multi_select' => false, 'help_text' => null],
            ['tab' => 'address', 'section' => 'Current Address', 'field_id' => 'province', 'label' => 'Province/State', 'type' => 'select', 'required' => false, 'placeholder' => 'Select Province', 'multi_select' => false, 'help_text' => 'Shows a province dropdown for Canada, free text otherwise', 'options' => $provinceOptions],
            ['tab' => 'address', 'section' => 'Current Address', 'field_id' => 'postal_code', 'label' => 'Postal/ZIP Code', 'type' => 'text', 'required' => false, 'placeholder' => 'A1A 1A1', 'multi_select' => false, 'help_text' => null],
            ['tab' => 'address', 'section' => 'Current Address', 'field_id' => 'country', 'label' => 'Country', 'type' => 'autocomplete', 'required' => false, 'placeholder' => 'Type to search countries...', 'multi_select' => false, 'help_text' => null],

            // Mailing Address Toggle
            ['tab' => 'address', 'section' => 'Mailing Address', 'field_id' => 'use_different_mailing_address', 'label' => 'My mailing address is different from my current address', 'type' => 'checkbox', 'required' => false, 'placeholder' => null, 'multi_select' => false, 'help_text' => 'Controls visibility of the mailing address section'],
            ['tab' => 'address', 'section' => 'Mailing Address', 'field_id' => 'mailing_address_line1', 'label' => 'Address Line 1', 'type' => 'text', 'required' => false, 'placeholder' => 'Street number and name', 'multi_select' => false, 'help_text' => 'Shown only when a different mailing address is used'],
            ['tab' => 'address', 'section' => 'Mailing Address', 'field_id' => 'mailing_address_line2', 'label' => 'Address Line 2', 'type' => 'text', 'required' => false, 'placeholder' => 'Apt, Suite, Unit', 'multi_select' => false, 'help_text' => 'Shown only when a different mailing address is used'],
            ['tab' => 'address', 'section' => 'Mailing Address', 'field_id' => 'mailing_city', 'label' => 'City', 'type' => 'text', 'required' => false, 'placeholder' => null, 'multi_select' => false, 'help_text' => 'Shown only when a different mailing address is used'],
            ['tab' => 'address', 'section' => 'Mailing Address', 'field_id' => 'mailing_province', 'label' => 'Province/State', 'type' => 'select', 'required' => false, 'placeholder' => 'Select Province', 'multi_select' => false, 'help_text' => 'Shown only when a different mailing address is used', 'options' => $provinceOptions],
            ['tab' => 'address', 'section' => 'Mailing Address', 'field_id' => 'mailing_postal_code', 'label' => 'Postal/ZIP Code', 'type' => 'text', 'required' => false, 'placeholder' => 'A1A 1A1', 'multi_select' => false, 'help_text' => 'Shown only when a different mailing address is used'],
            ['tab' => 'address', 'section' => 'Mailing Address', 'field_id' => 'mailing_country', 'label' => 'Country', 'type' => 'autocomplete', 'required' => false, 'placeholder' => 'Type to search countries...', 'multi_select' => false, 'help_text' => 'Shown only when a different mailing address is used'],
        ];
    }

    private function employmentFields(): array
    {
        return [
            // Current Employment Status
            ['tab' => 'employment', 'section' => 'Current Employment Status', 'field_id' => 'employment_status', 'label' => 'Employment Status', 'type' => 'select', 'required' => false, 'placeholder' => 'Select Status', 'multi_select' => false, 'help_text' => null, 'options' => [
                ['value' => 'employed', 'label' => 'Employed'],
                ['value' => 'unemployed', 'label' => 'Unemployed'],
                ['value' => 'student', 'label' => 'Student'],
                ['value' => 'self-employed', 'label' => 'Self-employed'],
                ['value' => 'retired', 'label' => 'Retired'],
                ['value' => 'homemaker', 'label' => 'Homemaker'],
                ['value' => 'other', 'label' => 'Other'],
            ]],
            ['tab' => 'employment', 'section' => 'Current Employment Status', 'field_id' => 'is_looking_for_work', 'label' => 'Currently looking for work', 'type' => 'checkbox', 'required' => false, 'placeholder' => null, 'multi_select' => false, 'help_text' => null],

            // Current Employer
            ['tab' => 'employment', 'section' => 'Current Employer', 'field_id' => 'employer_name', 'label' => 'Employer Name', 'type' => 'text', 'required' => false, 'placeholder' => null, 'multi_select' => false, 'help_text' => 'Shown when employed or self-employed'],
            ['tab' => 'employment', 'section' => 'Current Employer', 'field_id' => 'job_title', 'label' => 'Job Title', 'type' => 'text', 'required' => false, 'placeholder' => null, 'multi_select' => false, 'help_text' => 'Shown when employed or self-employed'],
            ['tab' => 'employment', 'section' => 'Current Employer', 'field_id' => 'employer_industry', 'label' => 'Industry', 'type' => 'text', 'required' => false, 'placeholder' => 'e.g., Technology, Healthcare, Education', 'multi_select' => false, 'help_text' => 'Shown when employed or self-employed'],
            ['tab' => 'employment', 'section' => 'Current Employer', 'field_id' => 'employment_start_date', 'label' => 'Start Date', 'type' => 'date', 'required' => false, 'placeholder' => null, 'multi_select' => false, 'help_text' => 'Shown when employed or self-employed'],
            ['tab' => 'employment', 'section' => 'Current Employer', 'field_id' => 'employment_end_date', 'label' => 'End Date', 'type' => 'date', 'required' => false, 'placeholder' => null, 'multi_select' => false, 'help_text' => 'Leave blank if current position'],
            ['tab' => 'employment', 'section' => 'Current Employer', 'field_id' => 'work_hours_per_week', 'label' => 'Hours per Week', 'type' => 'number', 'required' => false, 'placeholder' => null, 'multi_select' => false, 'help_text' => 'Range 0-168, step 0.5'],
            ['tab' => 'employment', 'section' => 'Current Employer', 'field_id' => 'monthly_income', 'label' => 'Monthly Income ($)', 'type' => 'number', 'required' => false, 'placeholder' => '0.00', 'multi_select' => false, 'help_text' => null],
            ['tab' => 'employment', 'section' => 'Current Employer', 'field_id' => 'is_job_related_to_program', 'label' => 'This job is related to my program of study', 'type' => 'checkbox', 'required' => false, 'placeholder' => null, 'multi_select' => false, 'help_text' => 'Shown when employed or self-employed'],

            // Previous Employment
            ['tab' => 'employment', 'section' => 'Previous Employment', 'field_id' => 'previous_job_title', 'label' => 'Previous Job Title', 'type' => 'text', 'required' => false, 'placeholder' => null, 'multi_select' => false, 'help_text' => null],
            ['tab' => 'employment', 'section' => 'Previous Employment', 'field_id' => 'previous_employer_name', 'label' => 'Previous Employer Name', 'type' => 'text', 'required' => false, 'placeholder' => null, 'multi_select' => false, 'help_text' => null],
            ['tab' => 'employment', 'section' => 'Previous Employment', 'field_id' => 'previous_employment_start_date', 'label' => 'Previous Employment Start Date', 'type' => 'date', 'required' => false, 'placeholder' => null, 'multi_select' => false, 'help_text' => null],
            ['tab' => 'employment', 'section' => 'Previous Employment', 'field_id' => 'previous_employment_end_date', 'label' => 'Previous Employment End Date', 'type' => 'date', 'required' => false, 'placeholder' => null, 'multi_select' => false, 'help_text' => null],
            ['tab' => 'employment', 'section' => 'Previous Employment', 'field_id' => 'reason_for_leaving', 'label' => 'Reason for Leaving', 'type' => 'text', 'required' => false, 'placeholder' => 'e.g., Career advancement, relocation', 'multi_select' => false, 'help_text' => null],

            // Employment Support & Benefits
            ['tab' => 'employment', 'section' => 'Employment Support & Benefits', 'field_id' => 'is_receiving_employment_insurance', 'label' => 'Currently receiving Employment Insurance (EI)', 'type' => 'checkbox', 'required' => false, 'placeholder' => null, 'multi_select' => false, 'help_text' => null],
            ['tab' => 'employment', 'section' => 'Employment Support & Benefits', 'field_id' => 'is_participating_in_work_study_program', 'label' => 'Participating in work-study program', 'type' => 'checkbox', 'required' => false, 'placeholder' => null, 'multi_select' => false, 'help_text' => null],
            ['tab' => 'employment', 'section' => 'Employment Support & Benefits', 'field_id' => 'barriers_to_employment', 'label' => 'Barriers to Employment', 'type' => 'textarea', 'required' => false, 'placeholder' => 'Describe any barriers you face in finding or maintaining employment', 'multi_select' => false, 'help_text' => null],

            // Career Goals
            ['tab' => 'employment', 'section' => 'Career Goals', 'field_id' => 'career_interest_area', 'label' => 'Career Interest Area', 'type' => 'text', 'required' => false, 'placeholder' => 'e.g., Information Technology, Business', 'multi_select' => false, 'help_text' => null],
            ['tab' => 'employment', 'section' => 'Career Goals', 'field_id' => 'desired_job_title', 'label' => 'Desired Job Title', 'type' => 'text', 'required' => false, 'placeholder' => 'Your career goal position', 'multi_select' => false, 'help_text' => null],
            ['tab' => 'employment', 'section' => 'Career Goals', 'field_id' => 'career_readiness_level', 'label' => 'Career Readiness Level', 'type' => 'select', 'required' => false, 'placeholder' => 'Select Level', 'multi_select' => false, 'help_text' => null, 'options' => [
                ['value' => 'beginner', 'label' => 'Beginner'],
                ['value' => 'developing', 'label' => 'Developing'],
                ['value' => 'proficient', 'label' => 'Proficient'],
                ['value' => 'advanced', 'label' => 'Advanced'],
            ]],
            ['tab' => 'employment', 'section' => 'Career Goals', 'field_id' => 'has_career_plan', 'label' => 'I have a clear career plan', 'type' => 'checkbox', 'required' => false, 'placeholder' => null, 'multi_select' => false, 'help_text' => null],
        ];
    }

    private function identityFields(): array
    {
        return [
            // Citizenship & Immigration Status
            ['tab' => 'identity', 'section' => 'Citizenship & Immigration Status', 'field_id' => 'citizenship_status', 'label' => 'Citizenship Status', 'type' => 'select', 'required' => false, 'placeholder' => 'Select Status', 'multi_select' => false, 'help_text' => null, 'options' => [
                ['value' => 'canadian_citizen', 'label' => 'Canadian Citizen'],
                ['value' => 'permanent_resident', 'label' => 'Permanent Resident'],
                ['value' => 'work_permit', 'label' => 'Work Permit Holder'],
                ['value' => 'study_permit', 'label' => 'Study Permit Holder'],
                ['value' => 'visitor', 'label' => 'Visitor'],
                ['value' => 'refugee', 'label' => 'Refugee'],
                ['value' => 'other', 'label' => 'Other'],
            ]],
            ['tab' => 'identity', 'section' => 'Citizenship & Immigration Status', 'field_id' => 'country_of_birth', 'label' => 'Country of Birth', 'type' => 'text', 'required' => false, 'placeholder' => 'e.g., Canada, Philippines, India', 'multi_select' => false, 'help_text' => null],
            ['tab' => 'identity', 'section' => 'Citizenship & Immigration Status', 'field_id' => 'immigration_status', 'label' => 'Immigration Status', 'type' => 'select', 'required' => false, 'placeholder' => 'Select Status', 'multi_select' => false, 'help_text' => null, 'options' => [
                ['value' => 'born_in_canada', 'label' => 'Born in Canada'],
                ['value' => 'naturalized_citizen', 'label' => 'Naturalized Citizen'],
                ['value' => 'permanent_resident', 'label' => 'Permanent Resident'],
                ['value' => 'temporary_resident', 'label' => 'Temporary Resident'],
                ['value' => 'refugee_protected_person', 'label' => 'Refugee/Protected Person'],
                ['value' => 'other', 'label' => 'Other'],
            ]],
            ['tab' => 'identity', 'section' => 'Citizenship & Immigration Status', 'field_id' => 'years_in_country', 'label' => 'Years in Canada', 'type' => 'number', 'required' => false, 'placeholder' => 'Years living in Canada', 'multi_select' => false, 'help_text' => 'Range 0-100'],

            // Language & Cultural Background
            ['tab' => 'identity', 'section' => 'Language & Cultural Background', 'field_id' => 'language_spoken_at_home', 'label' => 'Language Spoken at Home', 'type' => 'text', 'required' => false, 'placeholder' => 'e.g., English, French, Tagalog, Mandarin', 'multi_select' => false, 'help_text' => null],

            // Racial & Diversity Information
            ['tab' => 'identity', 'section' => 'Racial & Diversity Information', 'field_id' => 'racial_identity', 'label' => 'Racial Identity', 'type' => 'select', 'required' => false, 'placeholder' => 'Select Identity', 'multi_select' => false, 'help_text' => null, 'options' => [
                ['value' => 'black', 'label' => 'Black'],
                ['value' => 'east_asian', 'label' => 'East Asian'],
                ['value' => 'indigenous', 'label' => 'Indigenous'],
                ['value' => 'latino', 'label' => 'Latino'],
                ['value' => 'middle_eastern', 'label' => 'Middle Eastern'],
                ['value' => 'south_asian', 'label' => 'South Asian'],
                ['value' => 'southeast_asian', 'label' => 'Southeast Asian'],
                ['value' => 'white', 'label' => 'White'],
                ['value' => 'mixed_race', 'label' => 'Mixed Race'],
                ['value' => 'other', 'label' => 'Other'],
                ['value' => 'prefer_not_to_say', 'label' => 'Prefer not to say'],
            ]],
            ['tab' => 'identity', 'section' => 'Racial & Diversity Information', 'field_id' => 'is_visible_minority', 'label' => 'I identify as a visible minority', 'type' => 'select', 'required' => false, 'placeholder' => null, 'multi_select' => false, 'help_text' => null, 'options' => $this->profileAnswerOptions],

            // Indigenous Identity
            ['tab' => 'identity', 'section' => self::INDIGENOUS_IDENTITY_SECTION, 'field_id' => 'indigenous_status', 'label' => 'I identify as Indigenous', 'type' => 'select', 'required' => false, 'placeholder' => null, 'multi_select' => false, 'help_text' => 'Controls visibility of Indigenous group and band fields', 'options' => $this->profileAnswerOptions],
            ['tab' => 'identity', 'section' => self::INDIGENOUS_IDENTITY_SECTION, 'field_id' => 'indigenous_group', 'label' => 'Indigenous Group', 'type' => 'select', 'required' => false, 'placeholder' => 'Select Group', 'multi_select' => false, 'help_text' => 'Shown when Indigenous status is checked', 'options' => [
                ['value' => 'first_nations', 'label' => 'First Nations'],
                ['value' => 'metis', 'label' => 'Métis'],
                ['value' => 'inuit', 'label' => 'Inuit'],
                ['value' => 'other', 'label' => 'Other'],
            ]],
            ['tab' => 'identity', 'section' => self::INDIGENOUS_IDENTITY_SECTION, 'field_id' => 'band_affiliation', 'label' => 'Band/Nation Affiliation', 'type' => 'text', 'required' => false, 'placeholder' => 'Enter band or nation name', 'multi_select' => false, 'help_text' => 'Shown when Indigenous status is checked'],
            ['tab' => 'identity', 'section' => self::INDIGENOUS_IDENTITY_SECTION, 'field_id' => 'indigenous_status_card_number', 'label' => 'Status Card Number', 'type' => 'text', 'required' => false, 'placeholder' => 'Status card number (if applicable)', 'multi_select' => false, 'help_text' => null],
            ['tab' => 'identity', 'section' => self::INDIGENOUS_IDENTITY_SECTION, 'field_id' => 'is_registered_with_band', 'label' => 'Registered with band/nation', 'type' => 'checkbox', 'required' => false, 'placeholder' => null, 'multi_select' => false, 'help_text' => null],
            ['tab' => 'identity', 'section' => self::INDIGENOUS_IDENTITY_SECTION, 'field_id' => 'on_reserve_resident', 'label' => 'Currently living on reserve', 'type' => 'checkbox', 'required' => false, 'placeholder' => null, 'multi_select' => false, 'help_text' => null],
            ['tab' => 'identity', 'section' => self::INDIGENOUS_IDENTITY_SECTION, 'field_id' => 'receives_indigenous_support_services', 'label' => 'Receiving Indigenous support services', 'type' => 'checkbox', 'required' => false, 'placeholder' => null, 'multi_select' => false, 'help_text' => null],

            // Support Services
            ['tab' => 'identity', 'section' => 'Support Services', 'field_id' => 'receives_minority_support_services', 'label' => 'Receiving minority support services', 'type' => 'checkbox', 'required' => false, 'placeholder' => null, 'multi_select' => false, 'help_text' => null],
            ['tab' => 'identity', 'section' => 'Support Services', 'field_id' => 'refugee_status', 'label' => 'I am a refugee or protected person', 'type' => 'checkbox', 'required' => false, 'placeholder' => null, 'multi_select' => false, 'help_text' => null],
        ];
    }
}
