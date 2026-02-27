<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Individual extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'guid',
        'user_guid',
        'version_number',
        'social_insurance_number',
        'government_issued_id',
        'provincial_education_number',
        'first_name',
        'middle_name',
        'last_name',
        'preferred_name',
        'email_address',
        'phone_number',
        'alternate_phone_number',
        'date_of_birth',
        'gender',
        'preferred_pronouns',
        'disability_status',
        'accommodation_needs',
        'status',
        'verification_status',
        'metadata',
        'notes',
        'email_verified_at',
        'last_login_at',
        'sex',
    ];

    
    protected $casts = [
    'date_of_birth' => 'date',
    'disability_status' => 'boolean',
    'emergency_contact' => 'array',
    'metadata' => 'array',
    'email_verified_at' => 'timestamp',
    'last_login_at' => 'timestamp',
    ];

    protected $dates = [
        'deleted_at',
        'email_verified_at',
        'last_login_at',
    ];

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_SUSPENDED = 'suspended';

    // Verification status constants
    const VERIFICATION_UNVERIFIED = 'unverified';
    const VERIFICATION_PENDING = 'pending';
    const VERIFICATION_VERIFIED = 'verified';
    const VERIFICATION_REJECTED = 'rejected';

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->guid)) {
                $model->guid = Str::orderedUuid()->getHex();
            }
        });
    }

    // Relationships for addresses, employments, and identities
    public function addresses()
    {
        return $this->hasMany(IndividualAddress::class);
    }

    public function employments()
    {
        return $this->hasMany(IndividualEmployment::class);
    }

    public function identities()
    {
        return $this->hasMany(IndividualIdentity::class);
    }

    // Singular relationships for current/primary records
    public function currentAddress()
    {
        return $this->hasOne(IndividualAddress::class)->where('is_primary', true);
    }

    public function currentEmployment()
    {
        return $this->hasOne(IndividualEmployment::class)->where('is_current', true);
    }

    public function identity()
    {
        return $this->hasOne(IndividualIdentity::class);
    }

    public function permissionSelections()
    {
        return $this->hasMany(IndividualApplicationPermissionSelection::class);
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_guid', 'guid');
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->middle_name . ' ' . $this->last_name);
    }

    public function getDisplayNameAttribute()
    {
        return $this->preferred_name ?: $this->first_name;
    }



    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeVerified($query)
    {
        return $query->where('verification_status', self::VERIFICATION_VERIFIED);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Versioning relationship
    public function versions()
    {
        return $this->hasMany(IndividualVersion::class)->orderBy('version_number', 'desc');
    }

    // Get the latest version number
    public function getLatestVersionNumber()
    {
        return $this->versions()->max('version_number') ?: 0;
    }

    // Create a version before updating
    public function createVersionBeforeUpdate($notes = null, $createdBy = null)
    {
        try {
            // Increment version number
            $this->increment('version_number');
            
            // Mark all related records as not latest version
            $this->markRelatedRecordsAsOldVersion();
            
            return IndividualVersion::createVersion($this, $notes, $createdBy);
        } catch (\Exception $e) {
            // Log the error but don't fail the update
            \Illuminate\Support\Facades\Log::warning('Versioning failed, continuing without version', [
                'error' => $e->getMessage(),
                'individual_id' => $this->id
            ]);
            return null;
        }
    }

    // Mark all related records as old versions
    protected function markRelatedRecordsAsOldVersion()
    {
        try {
            // Mark all addresses as not latest version
            $this->addresses()->update(['latest_version' => false]);
            
            // Mark all employments as not latest version
            $this->employments()->update(['latest_version' => false]);
            
            // Mark all identities as not latest version
            $this->identities()->update(['latest_version' => false]);
        } catch (\Exception $e) {
            \Log::warning('Failed to mark related records as old version', [
                'error' => $e->getMessage(),
                'individual_id' => $this->id
            ]);
        }
    }

    // Get current version of addresses
    public function currentAddresses()
    {
        return $this->addresses()->where('latest_version', true);
    }

    // Get current version of employments
    public function currentEmployments()
    {
        return $this->employments()->where('latest_version', true);
    }

    // Get current version of identities
    public function currentIdentities()
    {
        return $this->identities()->where('latest_version', true);
    }

    // Get version history
    public function getVersionHistory()
    {
        try {
            return $this->versions;
        } catch (\Exception $e) {
            // If versioning table doesn't exist, return empty collection
            \Illuminate\Support\Facades\Log::warning('Could not get version history', [
                'error' => $e->getMessage(),
                'individual_id' => $this->id
            ]);
            return collect([]);
        }
    }
}
