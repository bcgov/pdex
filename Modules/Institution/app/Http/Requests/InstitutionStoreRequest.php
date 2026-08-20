<?php

namespace Modules\Institution\Http\Requests;

use App\Models\Institution;
use App\Models\InstitutionSite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InstitutionStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('accessPortal', Institution::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $phoneRegex = 'regex:/^(\+?1[-.\s]?)?\(?[0-9]{3}\)?[-.\s]?[0-9]{3}[-.\s]?[0-9]{4}$/';

        return [
            'legal_operating_name' => ['required', 'string', 'max:255', Rule::unique('institutions', 'legal_operating_name')->withoutTrashed()],
            'institution_type' => 'required|string|in:' . implode(',', Institution::getInstitutionTypes()),
            'dli' => 'required|string|max:20',
            'sites' => 'required|array|min:1',
            'sites.*.operating_name' => 'required|string|max:255',
            'sites.*.primary_phone' => ['required', 'string', $phoneRegex, 'max:20'],
            'sites.*.primary_email' => 'required|email|max:255',
            'sites.*.website' => 'nullable|url|max:255',
            'sites.*.regulating_body' => 'required|string|in:' . implode(',', InstitutionSite::getRegulatingBodies()),
            'sites.*.other_regulating_body' => 'nullable|string|max:255',
            'sites.*.contact_first_name' => 'required|string|max:100',
            'sites.*.contact_last_name' => 'required|string|max:100',
            'sites.*.contact_email' => 'required|email|max:255',
            'sites.*.contact_phone' => ['required', 'string', $phoneRegex, 'max:20'],
            'sites.*.address_line_1' => 'required|string|max:255',
            'sites.*.address_line_2' => 'nullable|string|max:255',
            'sites.*.city' => 'required|string|max:100',
            'sites.*.province_state' => 'required|string|max:100',
            'sites.*.country' => 'required|string|max:100',
            'sites.*.postal_code' => [
                'required',
                'string',
                'regex:/^[A-Za-z]\d[A-Za-z][\s\-]?\d[A-Za-z]\d$/',
                'max:10',
            ],
            'sites.*.standing_status' => 'nullable|string|in:' . implode(',', InstitutionSite::getStandingStatuses()),
            'sites.*.economic_region' => 'nullable|string|in:' . implode(',', InstitutionSite::getEconomicRegions()),
            'sites.*.established_date' => 'nullable|date',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'sites.min' => 'At least one institution site is required.',
            'sites.*.primary_phone.regex' => 'Primary phone must be a valid North American phone number (e.g., (555) 123-4567).',
            'sites.*.contact_phone.regex' => 'Contact phone must be a valid North American phone number (e.g., (555) 123-4567).',
            'sites.*.postal_code.regex' => 'Postal code must be a valid Canadian postal code (e.g., A1A 1A1).',

            'sites.*.operating_name.required' => 'The site name field is required.',
            'sites.*.primary_phone.required' => 'The primary phone field is required.',
            'sites.*.primary_email.required' => 'The primary email field is required.',
            'sites.*.regulating_body.required' => 'The regulating body field is required.',
            'sites.*.contact_first_name.required' => 'The contact first name field is required.',
            'sites.*.contact_last_name.required' => 'The contact last name field is required.',
            'sites.*.contact_email.required' => 'The contact email field is required.',
            'sites.*.contact_phone.required' => 'The contact phone field is required.',
            'sites.*.address_line_1.required' => 'The address line 1 field is required.',
            'sites.*.city.required' => 'The city field is required.',
            'sites.*.province_state.required' => 'The province/state field is required.',
            'sites.*.country.required' => 'The country field is required.',
            'sites.*.postal_code.required' => 'The postal code field is required.',

            'sites.*.primary_email.email' => 'The primary email must be a valid email address.',
            'sites.*.contact_email.email' => 'The contact email must be a valid email address.',
            'sites.*.website.url' => 'The website must be a valid URL.',
            'sites.*.regulating_body.in' => 'The selected regulating body is invalid.',
            'sites.*.standing_status.in' => 'The selected standing status is invalid.',
            'sites.*.economic_region.in' => 'The selected economic region is invalid.',
            'sites.*.established_date.date' => 'The established date must be a valid date.',

        ];
    }
}

