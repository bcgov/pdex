<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProfileFormField extends Model
{
    use HasFactory;

    protected $fillable = [
        'profile_type',
        'tab',
        'section',
        'field_id',
        'label',
        'type',
        'required',
        'placeholder',
        'multi_select',
        'help_text',
        'sort_order',
        'is_active',
        'api_enabled',
    ];

    protected $casts = [
        'required' => 'boolean',
        'multi_select' => 'boolean',
        'is_active' => 'boolean',
        'api_enabled' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Options for select/radio/checkbox fields.
     */
    public function options(): HasMany
    {
        return $this->hasMany(ProfileFormFieldOption::class)->orderBy('sort_order');
    }
}
