<?php

namespace Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplicationUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('application'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $applicationId = $this->route('application')->id;

        return [
            'name' => 'required|string|max:255|unique:applications,name,' . $applicationId,
            'description' => 'nullable|string|max:1000',
            'info_url' => 'nullable|url|max:500',
            'info_label' => 'nullable|string|max:255',
            'status' => 'required|in:draft,submitted,under_review,active,inactive,suspended,offline',
            'api_key' => 'nullable|string|max:255',
            'client_id' => 'nullable|string|max:255|required_with:api_key', 
            'client_secret' => 'nullable|string|max:255|required_with:api_key', 
            'approval_notes' => 'nullable|string|max:2000',
            'bcsc_redirect_url' => 'nullable|url|max:500',
            'idir_redirect_url' => 'nullable|url|max:500',
            'bceid_redirect_url' => 'nullable|url|max:500',
            'contact_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'bcsc_enabled' => 'boolean',
            'idir_enabled' => 'boolean',
            'bceid_enabled' => 'boolean',
            'active_alert_message' => 'nullable|string|max:1000',
            'offline_alert_message' => 'nullable|string|max:1000',
            'offline_start_time' => 'nullable|date',
            'offline_end_time' => 'nullable|date|after:offline_start_time',
            'comments' => 'nullable|string|max:2000',
            'stra_provided' => 'nullable|boolean',
            'pia_provided' => 'nullable|boolean',
            'data_permissions' => 'nullable|array',
            'data_permissions.*.table_name' => 'required|string|in:individuals,individual_addresses,individual_employments,individual_identities,institutions,institution_staff,institution_sites,programs',
            'data_permissions.*.column_name' => 'required|string',
            'data_permissions.*.display_name' => 'nullable|string|max:255',
            'data_permissions.*.can_read' => 'boolean',
            'data_permissions.*.can_write' => 'boolean',
            'data_permissions.*.is_required' => 'nullable|boolean',
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

            // Validate compliance docs when activating
            if (($data['status'] ?? '') === 'active') {
                if (!($data['stra_provided'] ?? false)) {
                    $validator->errors()->add('stra_provided', 'STRA documentation must be provided before setting status to active.');
                }
                if (!($data['pia_provided'] ?? false)) {
                    $validator->errors()->add('pia_provided', 'PIA documentation must be provided before setting status to active.');
                }
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
            'offline_end_time.after' => 'Offline end time must be after the start time.',
        ];
    }
}
