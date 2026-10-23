<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Institution extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'institutions';

    protected $fillable = [
        'guid',
        'bceid_business_guid',
        'legal_operating_name',
        'institution_type',
        'dli',
        'active_status',
    ];

    protected $casts = [
        'active_status' => 'boolean',
    ];

    protected $attributes = [
        'active_status' => false,
    ];

    // Constants for Institution Types
    const TYPE_UNIVERSITY = 'University';
    const TYPE_TEACHING_UNIVERSITY = 'Teaching University';
    const TYPE_COLLEGE = 'College';
    const TYPE_INSTITUTE = 'Institute';
    const TYPE_PRIVATE_CAREER_COLLEGE = 'Private Career College';
    const TYPE_UNION = 'Union';
    const TYPE_PRIVATE_TRAINER = 'Private Trainer';
    const TYPE_OTHER = 'Other';

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
     * Get the display name (legal operating name)
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->legal_operating_name;
    }

    /**
     * Get the status badge color for UI
     */
    public function getStatusBadgeAttribute(): string
    {
        return $this->active_status ? 'success' : 'secondary';
    }

    /**
     * Scope for active institutions
     */
    public function scopeActive($query)
    {
        return $query->where('active_status', true);
    }

    /**
     * Scope by institution type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('institution_type', $type);
    }

    /**
     * Get all available institution types
     */
    public static function getInstitutionTypes(): array
    {
        return [
            self::TYPE_UNIVERSITY,
            self::TYPE_TEACHING_UNIVERSITY,
            self::TYPE_COLLEGE,
            self::TYPE_INSTITUTE,
            self::TYPE_PRIVATE_CAREER_COLLEGE,
            self::TYPE_UNION,
            self::TYPE_PRIVATE_TRAINER,
            self::TYPE_OTHER,
        ];
    }

    /**
     * Check if institution is operational (active)
     */
    public function isOperational(): bool
    {
        return $this->active_status === true;
    }

    /**
     * Check if institution has a Designated Learning Institution number
     */
    public function hasDli(): bool
    {
        return !empty($this->dli);
    }

    /**
     * Get formatted DLI display
     */
    public function getDliDisplayAttribute(): string
    {
        return $this->dli ? "DLI: {$this->dli}" : 'No DLI';
    }

    /**
     * Relationship with programs (if you have a programs table)
     */
    public function programs(): HasMany
    {
        return $this->hasMany(Program::class, 'institution_id');
    }

    /**
     * Relationship with students (if you have a students table)
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'institution_id');
    }

    /**
     * Relationship with institution staff (if you have an institution_staff table)
     */
    public function staff(): HasMany
    {
        return $this->hasMany(InstitutionStaff::class, 'institution_id');
    }

    /**
     * Relationship with institution sites
     */
    public function sites(): HasMany
    {
        return $this->hasMany(InstitutionSite::class, 'institution_guid', 'guid');
    }

    /**
     * Relationships where this institution is Institution A
     */
    public function relationshipsAsA(): HasMany
    {
        return $this->hasMany(InstitutionRelationship::class, 'institution_a_guid', 'guid');
    }

    /**
     * Relationships where this institution is Institution B
     */
    public function relationshipsAsB(): HasMany
    {
        return $this->hasMany(InstitutionRelationship::class, 'institution_b_guid', 'guid');
    }

    /**
     * Get all relationships for this institution (both directions)
     */
    public function allRelationships()
    {
        return InstitutionRelationship::where('institution_a_guid', $this->guid)
            ->orWhere('institution_b_guid', $this->guid);
    }

    /**
     * Get all related institutions
     */
    public function relatedInstitutions()
    {
        $relationshipIds = $this->allRelationships()->pluck('id');
        
        $relatedGuids = collect();
        
        foreach ($this->allRelationships()->get() as $relationship) {
            if ($relationship->institution_a_guid === $this->guid) {
                $relatedGuids->push($relationship->institution_b_guid);
            } else {
                $relatedGuids->push($relationship->institution_a_guid);
            }
        }
        
        return Institution::whereIn('guid', $relatedGuids->unique());
    }

    /**
     * Get related institutions by relationship type
     */
    public function getRelatedByType(string $type)
    {
        $relationships = $this->allRelationships()
            ->where('relationship_type', $type)
            ->where('is_active', true)
            ->get();
        
        $relatedGuids = collect();
        
        foreach ($relationships as $relationship) {
            if ($relationship->institution_a_guid === $this->guid) {
                $relatedGuids->push($relationship->institution_b_guid);
            } else {
                $relatedGuids->push($relationship->institution_a_guid);
            }
        }
        
        return Institution::whereIn('guid', $relatedGuids->unique())->get();
    }

    /**
     * Check if this institution is related to another
     */
    public function isRelatedTo(Institution $other): bool
    {
        return $this->allRelationships()
            ->where(function($query) use ($other) {
                $query->where('institution_a_guid', $other->guid)
                      ->orWhere('institution_b_guid', $other->guid);
            })
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Add a relationship to another institution
     */
    public function addRelationshipWith(
        Institution $other, 
        string $type, 
        string $reason, 
        array $additionalData = []
    ): InstitutionRelationship {
        return InstitutionRelationship::createBidirectional(
            $this->guid,
            $other->guid,
            $type,
            $reason,
            $additionalData
        );
    }
}
