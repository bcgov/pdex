<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class InstitutionStaff extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'institution_staff';

    protected $fillable = [
        'guid',
        'user_guid',
        'institution_guid',
        'bceid_business_guid',
        'bceid_user_guid',
        'bceid_user_id',
        'bceid_user_name',
        'bceid_user_email',
        'status',
        'last_touch_by_user_guid',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * Boot method to auto-generate GUID.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->guid)) {
                $model->guid = Str::orderedUuid()->getHex();
            }
        });
    }

    /**
     * Get the route key for implicit model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'guid';
    }

    /**
     * The institution this staff member belongs to.
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_guid', 'guid');
    }
}
