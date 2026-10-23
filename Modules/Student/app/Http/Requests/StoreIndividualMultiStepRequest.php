<?php

namespace Modules\Student\Http\Requests;

use App\Models\Individual;
use App\Models\ProfileFormField;
use App\Rules\ValidSin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreIndividualMultiStepRequest extends FormRequest
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
        // Build rules dynamically from the admin-managed profile form fields so
        // every active field (including newly added ones) survives validation
        // and is persisted. Dynamic fields are kept lenient (nullable) so saves
        // are never blocked; critical general fields are overridden below.
        $rules = $this->dynamicRules();

        $rules['social_insurance_number'] = ['nullable', 'string', 'max:255',
            new ValidSin(),
            Rule::unique('individuals', 'social_insurance_number'),
        ];
        $rules['provincial_education_number'] = ['nullable', 'string', 'max:255',
            Rule::unique('individuals', 'provincial_education_number'),
        ];
        $rules['first_name'] = ['required', 'string', 'max:255'];
        $rules['last_name'] = ['required', 'string', 'max:255'];
        $rules['email_address'] = ['required', 'email'];
        $rules['date_of_birth'] = ['nullable', 'date', 'before:today'];
        $rules['gender'] = ['nullable', 'string', 'in:man,woman,non-binary,unknown'];
        $rules['sex'] = ['nullable', 'string', 'in:male,female,indeterminate,unknown'];
        $rules['disability_status'] = ['nullable', 'string', 'in:yes,no,unknown'];
        $rules['identity.indigenous_status'] = ['nullable', 'string', 'in:yes,no,unknown'];
        $rules['identity.is_visible_minority'] = ['nullable', 'string', 'in:yes,no,unknown'];

        return $rules;
    }

    /**
     * Build validation rules from the student profile form field definitions.
     *
     * Each field maps to a key on the payload depending on its tab:
     *   general    -> <field_id>
     *   address    -> current_address.<field_id> (mailing_* -> mailing_address.<...>)
     *   employment -> current_employment.<field_id>
     *   identity   -> identity.<field_id>
     */
    private function dynamicRules(): array
    {
        $rules = [
            'current_address' => ['nullable', 'array'],
            'mailing_address' => ['nullable', 'array'],
            'current_employment' => ['nullable', 'array'],
            'identity' => ['nullable', 'array'],
            'use_different_mailing_address' => ['boolean'],
        ];

        $fields = ProfileFormField::where('profile_type', 'student')->get();

        foreach ($fields as $field) {
            // The mailing toggle is handled as a root boolean above.
            if ($field->field_id === 'use_different_mailing_address') {
                continue;
            }

            $key = match ($field->tab) {
                'general' => $field->field_id,
                'address' => str_starts_with($field->field_id, 'mailing_')
                    ? 'mailing_address.' . Str::after($field->field_id, 'mailing_')
                    : 'current_address.' . $field->field_id,
                'employment' => 'current_employment.' . $field->field_id,
                'identity' => 'identity.' . $field->field_id,
                default => null,
            };

            if ($key === null) {
                continue;
            }

            // Keep dynamic fields lenient so a form save is never blocked. The
            // key still needs a rule to be returned by validated() and persisted.
            $rules[$key] = ['nullable'];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'social_insurance_number.required' => 'Social Insurance Number is required.',
            'social_insurance_number.unique' => 'This Social Insurance Number is already registered.',
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
            // 'identity.racial_identity' => 'nullable|array',
            // 'identity.racial_identity.*' => 'string|max:255',
            // 'identity.racial_identity_other_text' => 'nullable|string|max:200',

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
        // if (!$this->has('disability_status')) {
        //     $this->merge(['disability_status' => false]);
        // }

        if (!$this->has('use_different_mailing_address')) {
            $this->merge(['use_different_mailing_address' => false]);
        }

        // Set nested boolean fields for identity while preserving other fields
        $identity = $this->input('identity', []);
        if ($this->has('identity')) {
            
            // Set default boolean values only if not present
            $identity['refugee_status'] = $identity['refugee_status'] ?? false;
            $identity['is_registered_with_band'] = $identity['is_registered_with_band'] ?? false;
            $identity['on_reserve_resident'] = $identity['on_reserve_resident'] ?? false;
            $identity['receives_indigenous_support_services'] = $identity['receives_indigenous_support_services'] ?? false;
            $identity['receives_minority_support_services'] = $identity['receives_minority_support_services'] ?? false;
            
        }

        // Set racial identity for mini profile
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

        // Coerce every field to its configured type so empty strings become
        // null (or false for checkboxes, [] for multi-selects). Without this,
        // empty inputs would error when written to integer/date/boolean columns.
        $fields = ProfileFormField::where('profile_type', 'student')->get()->groupBy('tab');

        // General fields live at the payload root.
        $root = [];
        foreach ($fields->get('general', collect()) as $field) {
            if ($this->has($field->field_id)) {
                $root[$field->field_id] = $this->coerceValue($this->input($field->field_id), $field);
            }
        }
        if (! empty($root)) {
            $this->merge($root);
        }

        // Nested containers.
        $addressFields = $fields->get('address', collect());
        $this->coerceContainer('current_address', $addressFields->filter(fn ($f) => ! str_starts_with($f->field_id, 'mailing_')), false);
        $this->coerceContainer('mailing_address', $addressFields->filter(fn ($f) => str_starts_with($f->field_id, 'mailing_')), true);
        $this->coerceContainer('current_employment', $fields->get('employment', collect()), false);
        $this->coerceContainer('identity', $fields->get('identity', collect()), false);
    }

    /**
     * Coerce the values inside a nested container (address/employment/identity)
     * according to their configured field types.
     *
     * @param  \Illuminate\Support\Collection<int, ProfileFormField>  $fields
     * @param  bool  $stripMailingPrefix  Whether the container key drops the "mailing_" prefix (mailing address).
     */
    private function coerceContainer(string $containerKey, $fields, bool $stripMailingPrefix): void
    {
        $data = $this->input($containerKey);

        if (! is_array($data)) {
            return;
        }

        foreach ($fields as $field) {
            $subKey = $stripMailingPrefix ? Str::after($field->field_id, 'mailing_') : $field->field_id;

            if (array_key_exists($subKey, $data)) {
                $data[$subKey] = $this->coerceValue($data[$subKey], $field);
            }
        }

        $this->merge([$containerKey => $data]);
    }

    /**
     * Coerce a single value to a persistable form based on its field type.
     */
    private function coerceValue($value, ProfileFormField $field)
    {
        if ($field->multi_select) {
            if ($value === '' || $value === null) {
                return [];
            }

            return is_array($value) ? $value : [$value];
        }

        if ($field->type === 'checkbox') {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        return $value === '' ? null : $value;
    }
}
