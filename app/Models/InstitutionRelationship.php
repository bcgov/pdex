<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InstitutionRelationship extends Model
{
    use HasFactory;

    protected $table = 'institution_relationships';

    protected $fillable = [
        'institution_a_guid',
        'institution_b_guid',
        'relationship_type',
        'relationship_reason',
        'description',
        'is_active',
        'effective_date',
        'expiry_date',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    // Constants for relationship types
    const TYPE_GEOGRAPHIC = 'geographic';
    const TYPE_ACADEMIC = 'academic';
    const TYPE_PARTNERSHIP = 'partnership';
    const TYPE_CONSORTIUM = 'consortium';
    const TYPE_TRANSFER_AGREEMENT = 'transfer_agreement';
    const TYPE_AFFILIATION = 'affiliation';
    const TYPE_FEDERATION = 'federation';
    const TYPE_NETWORK = 'network';

    /**
     * Get all available relationship types
     */
    public static function getRelationshipTypes(): array
    {
        return [
            self::TYPE_GEOGRAPHIC => 'Geographic (Same City/Region)',
            self::TYPE_ACADEMIC => 'Academic Partnership',
            self::TYPE_PARTNERSHIP => 'Strategic Partnership',
            self::TYPE_CONSORTIUM => 'Consortium Member',
            self::TYPE_TRANSFER_AGREEMENT => 'Transfer Agreement',
            self::TYPE_AFFILIATION => 'Institutional Affiliation',
            self::TYPE_FEDERATION => 'Federation Member',
            self::TYPE_NETWORK => 'Network Member',
        ];
    }

    /**
     * Get all available relationship reasons
     */
    public static function getRelationshipReasons(): array
    {
        return [
            'joint_programs' => 'Joint Academic Programs',
            'student_exchange' => 'Student Exchange',
            'credit_transfer' => 'Credit Transfer Agreement',
            'research_collaboration' => 'Research Collaboration',
            'shared_resources' => 'Shared Resources',
            'geographic_proximity' => 'Geographic Proximity',
            'institutional_network' => 'Institutional Network',
            'strategic_alliance' => 'Strategic Alliance',
            'accreditation' => 'Accreditation Partnership',
            'pathway_program' => 'Pathway Program',
            'dual_degree' => 'Dual Degree Program',
            'consortium_membership' => 'Consortium Membership',
            'articulation_agreement' => 'Articulation Agreement',
            'other' => 'Other',
        ];
    }

    /**
     * Relationship with Institution A
     */
    public function institutionA(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_a_guid', 'guid');
    }

    /**
     * Relationship with Institution B
     */
    public function institutionB(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_b_guid', 'guid');
    }

    /**
     * Get the "other" institution in the relationship
     */
    public function getOtherInstitution(string $institutionGuid): ?Institution
    {
        if ($this->institution_a_guid === $institutionGuid) {
            return $this->institutionB;
        } elseif ($this->institution_b_guid === $institutionGuid) {
            return $this->institutionA;
        }
        
        return null;
    }

    /**
     * Check if relationship is currently active
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now()->toDateString();
        
        // Check if effective date has passed
        if ($this->effective_date && $this->effective_date > $now) {
            return false;
        }
        
        // Check if expiry date has passed
        if ($this->expiry_date && $this->expiry_date < $now) {
            return false;
        }
        
        return true;
    }

    /**
     * Scope for active relationships
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for relationships of a specific type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('relationship_type', $type);
    }

    /**
     * Scope for current relationships (active and within date range)
     */
    public function scopeCurrent($query)
    {
        $now = now()->toDateString();
        
        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('effective_date')
                  ->orWhere('effective_date', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>=', $now);
            });
    }

    /**
     * Create a bidirectional relationship helper
     */
    public static function createBidirectional(
        string $institutionAGuid,
        string $institutionBGuid,
        string $relationshipType,
        string $relationshipReason,
        array $additionalData = []
    ): self {
                // Check if relationship of the same type already exists in either direction
        $existingRelationships = self::where(function ($query) use ($institutionAGuid, $institutionBGuid) {
            $query->where('institution_a_guid', $institutionAGuid)
                  ->where('institution_b_guid', $institutionBGuid);
        })->orWhere(function ($query) use ($institutionAGuid, $institutionBGuid) {
            $query->where('institution_a_guid', $institutionBGuid)
                  ->where('institution_b_guid', $institutionAGuid);
                })->where('relationship_type', $relationshipType)
                    ->get();

        if ($existingRelationships->isNotEmpty()) {
            // Update any existing relationship(s) of the same type
            $updates = array_merge([
                'relationship_type' => $relationshipType,
                'relationship_reason' => $relationshipReason,
            ], $additionalData);

            foreach ($existingRelationships as $existingRelationship) {
                $existingRelationship->update($updates);
            }
            
            return $existingRelationships->first();
        }

        // Ensure consistent ordering for new relationships
        if ($institutionAGuid > $institutionBGuid) {
            [$institutionAGuid, $institutionBGuid] = [$institutionBGuid, $institutionAGuid];
        }

        return self::create(array_merge([
            'institution_a_guid' => $institutionAGuid,
            'institution_b_guid' => $institutionBGuid,
            'relationship_type' => $relationshipType,
            'relationship_reason' => $relationshipReason,
        ], $additionalData));
    }
}
