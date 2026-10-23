<?php

namespace App\Traits;

use App\Models\ProfileFormField;
use Illuminate\Support\Facades\Schema;

/**
 * Merges the statically declared $fillable of a model with the columns defined
 * by the student profile form fields (profile_form_fields) for the model's tab.
 *
 * This keeps the invariant that every field managed under
 * /admin/utils/student is mass-assignable on its backing model without having
 * to hand-edit the $fillable array each time a field is added.
 *
 * Models using this trait must declare a $profileFieldTab property, e.g.:
 *   protected string $profileFieldTab = 'identity';
 */
trait HasDynamicProfileColumns
{
    /**
     * Per-request cache of dynamic columns keyed by tab.
     *
     * @var array<string, array<int, string>>
     */
    protected static array $dynamicProfileColumnsCache = [];

    /**
     * Get the fillable attributes for the model, including profile form fields.
     *
     * @return array<int, string>
     */
    public function getFillable()
    {
        return array_values(array_unique(array_merge(
            parent::getFillable(),
            $this->dynamicProfileColumns()
        )));
    }

    /**
     * Resolve the profile-form-field columns for this model's tab.
     *
     * @return array<int, string>
     */
    protected function dynamicProfileColumns(): array
    {
        $tab = $this->profileFieldTab ?? null;

        if (! $tab) {
            return [];
        }

        if (array_key_exists($tab, static::$dynamicProfileColumnsCache)) {
            return static::$dynamicProfileColumnsCache[$tab];
        }

        // Avoid touching the DB when the table is not available yet
        // (e.g. during early migration/boot).
        if (! Schema::hasTable('profile_form_fields')) {
            return static::$dynamicProfileColumnsCache[$tab] = [];
        }

        try {
            $columns = ProfileFormField::query()
                ->where('profile_type', 'student')
                ->where('tab', $tab)
                ->pluck('field_id')
                ->all();
        } catch (\Throwable $e) {
            $columns = [];
        }

        return static::$dynamicProfileColumnsCache[$tab] = $columns;
    }
}
