<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class InstitutionSite extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'institution_sites';

    protected $fillable = [
        'guid',
        'institution_guid',
        'operating_name',
        'primary_phone',
        'primary_email',
        'website',
        'regulating_body',
        'other_regulating_body',
        'established_date',
        'info_sharing_agreement',
        'contact_first_name',
        'contact_last_name',
        'contact_email',
        'contact_phone',
        'address_line_1',
        'address_line_2',
        'city',
        'province_state',
        'country',
        'postal_code',
        'public',
        'active_status',
        'standing_status',
        'economic_region',
        'notes',
    ];

    protected $casts = [
        'established_date' => 'date',
        'info_sharing_agreement' => 'boolean',
        'public' => 'boolean',
        'active_status' => 'boolean',
    ];

    protected $attributes = [
        'country' => 'Canada',
        'public' => false,
        'active_status' => false,
        'info_sharing_agreement' => false,
    ];

    // Constants for Standing Status
    const STANDING_GOOD = 'Good Standing';
    const STANDING_PROBATION = 'Probation';
    const STANDING_SUSPENDED = 'Suspended';
    const STANDING_UNDER_REVIEW = 'Under Review';

    // Constants for Economic Regions (BC)
    const REGION_CARIBOO = 'Cariboo';
    const REGION_KOOTENAY = 'Kootenay';
    const REGION_MAINLAND_SOUTHWEST = 'Mainland/Southwest';
    const REGION_NECHAKO = 'Nechako';
    const REGION_NORTH_COAST = 'North Coast';
    const REGION_NORTHEAST = 'Northeast';
    const REGION_THOMPSON_OKANAGAN = 'Thompson-Okanagan';
    const REGION_VANCOUVER_ISLAND_COAST = 'Vancouver Island/Coast';

    // Constants for Regulating Bodies
    const REGULATING_BODY_PTIB = 'Private Training Institutions Branch (PTIB)';
    const REGULATING_BODY_AEST = 'Ministry of Post-Secondary Education and Future Skills';
    const REGULATING_BODY_DQAB = 'Degree Quality Assessment Board (DQAB)';
    const REGULATING_BODY_EQA = 'Educational Quality Assurance (EQA)';
    const REGULATING_BODY_OTHER = 'Other';

    /**
     * Boot method to auto-generate GUID
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
     * Get the display name (operating name or institution legal name)
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->operating_name ?: $this->institution->legal_operating_name;
    }

    /**
     * Get the full contact name
     */
    public function getFullContactNameAttribute(): string
    {
        return trim($this->contact_first_name . ' ' . $this->contact_last_name);
    }

    /**
     * Get the full address
     */
    public function getFullAddressAttribute(): string
    {
        $address = $this->address_line_1;
        if ($this->address_line_2) {
            $address .= ', ' . $this->address_line_2;
        }
        $address .= ', ' . $this->city . ', ' . $this->province_state;
        $address .= ', ' . $this->country . ' ' . $this->postal_code;
        
        return $address;
    }

    /**
     * Get the status badge color for UI
     */
    public function getStatusBadgeAttribute(): string
    {
        if (!$this->active_status) {
            return 'secondary';
        }

        return match($this->standing_status) {
            self::STANDING_GOOD => 'success',
            self::STANDING_PROBATION => 'warning',
            self::STANDING_SUSPENDED => 'danger',
            self::STANDING_UNDER_REVIEW => 'info',
            default => 'primary'
        };
    }

    /**
     * Scope for active sites
     */
    public function scopeActive($query)
    {
        return $query->where('active_status', true);
    }

    /**
     * Scope for public sites
     */
    public function scopePublic($query)
    {
        return $query->where('public', true);
    }

    /**
     * Scope for sites in good standing
     */
    public function scopeInGoodStanding($query)
    {
        return $query->where('standing_status', self::STANDING_GOOD);
    }

    /**
     * Scope by economic region
     */
    public function scopeInRegion($query, string $region)
    {
        return $query->where('economic_region', $region);
    }

    /**
     * Get all available standing statuses
     */
    public static function getStandingStatuses(): array
    {
        return [
            self::STANDING_GOOD,
            self::STANDING_PROBATION,
            self::STANDING_SUSPENDED,
            self::STANDING_UNDER_REVIEW,
        ];
    }

    /**
     * Get all BC economic regions
     */
    public static function getEconomicRegions(): array
    {
        return [
            self::REGION_CARIBOO,
            self::REGION_KOOTENAY,
            self::REGION_MAINLAND_SOUTHWEST,
            self::REGION_NECHAKO,
            self::REGION_NORTH_COAST,
            self::REGION_NORTHEAST,
            self::REGION_THOMPSON_OKANAGAN,
            self::REGION_VANCOUVER_ISLAND_COAST,
        ];
    }

    /**
     * Get all available regulating bodies
     */
    public static function getRegulatingBodies(): array
    {
        return [
            self::REGULATING_BODY_PTIB,
            self::REGULATING_BODY_AEST,
            self::REGULATING_BODY_DQAB,
            self::REGULATING_BODY_EQA,
            self::REGULATING_BODY_OTHER,
        ];
    }

    /**
     * Check if site has signed info sharing agreement
     */
    public function hasInfoSharingAgreement(): bool
    {
        return $this->info_sharing_agreement === true;
    }

    /**
     * Check if site is operational (active and in good standing)
     */
    public function isOperational(): bool
    {
        return $this->active_status && $this->standing_status === self::STANDING_GOOD;
    }

    /**
     * Relationship with parent institution
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_guid', 'guid');
    }
}
