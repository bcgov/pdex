<?php

namespace Modules\Student\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="IndividualIdentity",
 *     type="object",
 *     title="Individual Identity",
 *     description="Individual identity and cultural information",
 *     @OA\Property(property="id", type="integer", description="Unique identifier"),
 *     @OA\Property(property="individual_id", type="integer", description="Individual ID"),
 *     @OA\Property(property="citizenship_status", type="string", description="Citizenship status"),
 *     @OA\Property(property="country_of_birth", type="string", description="Country of birth"),
 *     @OA\Property(property="immigration_status", type="string", description="Immigration status"),
 *     @OA\Property(property="years_in_country", type="integer", description="Years in country"),
 *     @OA\Property(property="language_spoken_at_home", type="string", description="Language spoken at home"),
 *     @OA\Property(property="racial_identity", type="string", description="Racial identity"),
 *     @OA\Property(property="is_visible_minority", type="string", description="Is visible minority"),
 *     @OA\Property(property="indigenous_status", type="string", description="Indigenous status"),
 *     @OA\Property(property="indigenous_group", type="string", description="Indigenous group"),
 *     @OA\Property(property="band_affiliation", type="string", description="Band affiliation"),
 *     @OA\Property(property="refugee_status", type="boolean", description="Refugee status"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Last update timestamp")
 * )
 */
class IndividualIdentityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'individual_id' => $this->individual_id,
            'citizenship_status' => $this->citizenship_status,
            'country_of_birth' => $this->country_of_birth,
            'immigration_status' => $this->immigration_status,
            'years_in_country' => $this->years_in_country,
            'language_spoken_at_home' => $this->language_spoken_at_home,
            'racial_identity' => $this->racial_identity,
            'is_visible_minority' => $this->is_visible_minority,
            'indigenous_status' => $this->indigenous_status,
            'indigenous_group' => $this->indigenous_group,
            'band_affiliation' => $this->band_affiliation,
            'indigenous_status_card_number' => $this->indigenous_status_card_number,
            'is_registered_with_band' => (bool) $this->is_registered_with_band,
            'on_reserve_resident' => (bool) $this->on_reserve_resident,
            'receives_indigenous_support_services' => (bool) $this->receives_indigenous_support_services,
            'receives_minority_support_services' => (bool) $this->receives_minority_support_services,
            'refugee_status' => (bool) $this->refugee_status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
