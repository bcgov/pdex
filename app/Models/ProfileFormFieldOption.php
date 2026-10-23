<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileFormFieldOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'profile_form_field_id',
        'value',
        'label',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * The field this option belongs to.
     */
    public function field(): BelongsTo
    {
        return $this->belongsTo(ProfileFormField::class, 'profile_form_field_id');
    }
}
