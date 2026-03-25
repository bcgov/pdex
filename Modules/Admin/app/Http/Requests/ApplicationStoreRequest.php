<?php

namespace Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplicationStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Application::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:applications',
            'description' => 'nullable|string|max:1000',
            'info_url' => 'nullable|url|max:500',
            'info_label' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive,offline',
            'bcsc_redirect_url' => 'nullable|url|max:500',
            'idir_redirect_url' => 'nullable|url|max:500',
            'bceid_redirect_url' => 'nullable|url|max:500',
            'contact_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'bcsc_enabled' => 'boolean',
            'idir_enabled' => 'boolean',
            'bceid_enabled' => 'boolean',
            'comments' => 'nullable|string|max:2000',
            'stra_provided' => 'boolean',
            'pia_provided' => 'boolean',
            'profile_integration_ready' => 'boolean',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $this->all();

            // Validate that at least one IDP is enabled
            if (!($data['bcsc_enabled'] ?? false) && 
                !($data['idir_enabled'] ?? false) && 
                !($data['bceid_enabled'] ?? false)) {
                $validator->errors()->add('bcsc_enabled', 'At least one identity provider must be enabled.');
            }

            // Validate that corresponding redirect URL is provided for enabled IDPs
            if (($data['bcsc_enabled'] ?? false) && empty($data['bcsc_redirect_url'])) {
                $validator->errors()->add('bcsc_redirect_url', 'BCSC redirect URL is required when BCSC is enabled.');
            }
            if (($data['idir_enabled'] ?? false) && empty($data['idir_redirect_url'])) {
                $validator->errors()->add('idir_redirect_url', 'IDIR redirect URL is required when IDIR is enabled.');
            }
            if (($data['bceid_enabled'] ?? false) && empty($data['bceid_redirect_url'])) {
                $validator->errors()->add('bceid_redirect_url', 'BCeID redirect URL is required when BCeID is enabled.');
            }

            // Validate info fields - both must be provided together or not at all
            $hasInfoUrl = !empty($data['info_url']);
            $hasInfoLabel = !empty($data['info_label']);
            
            if ($hasInfoUrl && !$hasInfoLabel) {
                $validator->errors()->add('info_label', 'Info label is required when info URL is provided.');
            }
            if ($hasInfoLabel && !$hasInfoUrl) {
                $validator->errors()->add('info_url', 'Info URL is required when info label is provided.');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Application name is required.',
            'name.unique' => 'An application with this name already exists.',
            'contact_name.required' => 'Contact name is required.',
            'contact_email.required' => 'Contact email is required.',
            'contact_email.email' => 'Please provide a valid email address.',
        ];
    }
}
