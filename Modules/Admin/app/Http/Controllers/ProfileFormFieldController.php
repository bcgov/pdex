<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ProfileFormField;
use App\Models\ProfileFormFieldOption;
use App\Services\ProfileFieldSchemaSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProfileFormFieldController extends Controller
{
    public function __construct(private ProfileFieldSchemaSync $schemaSync)
    {
    }

    /**
     * Ordered list of tabs used by the student profile form.
     */
    private array $tabs = ['general', 'address', 'employment', 'identity'];

    /**
     * Supported field input types.
     */
    private array $types = [
        'text',
        'email',
        'tel',
        'number',
        'date',
        'textarea',
        'select',
        'radio',
        'checkbox',
        'autocomplete',
    ];

    /**
     * Display the student profile form field manager.
     */
    public function index(): Response
    {
        $fields = ProfileFormField::with('options')
            ->where('profile_type', 'student')
            ->orderBy('tab')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Admin::Utils/Student', [
            'fields' => $fields,
            'tabs' => $this->tabs,
            'types' => $this->types,
        ]);
    }

    /**
     * Store a new field (with its options).
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateField($request);

        $field = DB::transaction(function () use ($data) {
            $data['profile_type'] = 'student';

            if (! isset($data['sort_order'])) {
                $data['sort_order'] = (int) ProfileFormField::where('profile_type', 'student')
                    ->where('tab', $data['tab'])
                    ->max('sort_order') + 1;
            }

            $options = $data['options'] ?? [];
            unset($data['options']);

            $field = ProfileFormField::create($data);
            $this->syncOptions($field, $options);

            return $field;
        });

        // Ensure a matching schema column exists (generates + runs a migration
        // when the column is missing). Done outside the transaction because
        // running a migration inside one is unsafe.
        $sync = $this->schemaSync->syncField($field);

        return back()->with('success', $this->syncMessage('Field created successfully.', $sync));
    }

    /**
     * Update an existing field (and its options).
     */
    public function update(Request $request, ProfileFormField $field): RedirectResponse
    {
        $data = $this->validateField($request, $field);

        DB::transaction(function () use ($data, $field) {
            $options = $data['options'] ?? [];
            unset($data['options']);

            $field->update($data);
            $this->syncOptions($field, $options);
        });

        // Keep the schema in sync when a field's id/tab/type changes.
        $sync = $this->schemaSync->syncField($field->refresh());

        return back()->with('success', $this->syncMessage('Field updated successfully.', $sync));

        return back()->with('success', 'Field updated successfully.');
    }

    /**
     * Delete a field and its options.
     */
    public function destroy(ProfileFormField $field): RedirectResponse
    {
        $field->options()->delete();
        $field->delete();

        return back()->with('success', 'Field deleted successfully.');
    }

    /**
     * Persist a new ordering of fields within a tab.
     */
    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fields' => 'required|array',
            'fields.*.id' => 'required|integer|exists:profile_form_fields,id',
            'fields.*.sort_order' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['fields'] as $item) {
                ProfileFormField::where('id', $item['id'])
                    ->update(['sort_order' => $item['sort_order']]);
            }
        });

        return back()->with('success', 'Fields reordered successfully.');
    }

    /**
     * Validate the incoming field payload.
     */
    private function validateField(Request $request, ?ProfileFormField $field = null): array
    {
        $uniqueRule = Rule::unique('profile_form_fields', 'field_id')
            ->where(fn ($query) => $query
                ->where('profile_type', 'student')
                ->where('tab', $request->input('tab')));

        if ($field) {
            $uniqueRule->ignore($field->id);
        }

        return $request->validate([
            'tab' => ['required', Rule::in($this->tabs)],
            'section' => ['nullable', 'string', 'max:255'],
            'field_id' => ['required', 'string', 'max:255', 'regex:/^[a-z][a-z0-9_]*$/', $uniqueRule],
            'label' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in($this->types)],
            'required' => ['boolean'],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'multi_select' => ['boolean'],
            'help_text' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'api_enabled' => ['boolean'],
            'options' => ['array'],
            'options.*.value' => ['required_with:options', 'string', 'max:255'],
            'options.*.label' => ['required_with:options', 'string', 'max:255'],
            'options.*.is_default' => ['boolean'],
        ], [
            'field_id.regex' => 'The field id must start with a letter and contain only lowercase letters, numbers, and underscores.',
        ]);
    }

    /**
     * Replace the options for a field with the provided set.
     */
    private function syncOptions(ProfileFormField $field, array $options): void
    {
        $field->options()->delete();

        foreach (array_values($options) as $index => $option) {
            ProfileFormFieldOption::create([
                'profile_form_field_id' => $field->id,
                'value' => $option['value'],
                'label' => $option['label'],
                'is_default' => (bool) ($option['is_default'] ?? false),
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * Append the schema-sync outcome to a flash message when a column was created.
     *
     * @param  array{status:string, table:?string, column:?string, migration:?string, message:?string}  $sync
     */
    private function syncMessage(string $base, array $sync): string
    {
        if (($sync['status'] ?? null) === 'created') {
            return "{$base} Added column '{$sync['column']}' to '{$sync['table']}'.";
        }

        return $base;
    }
}
