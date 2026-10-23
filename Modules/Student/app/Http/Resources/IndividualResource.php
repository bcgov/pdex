<?php

namespace Modules\Student\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="Individual",
 *     type="object",
 *     title="Individual",
 *     description="Student individual profile",
 *     @OA\Property(property="id", type="integer", description="Unique identifier"),
 *     @OA\Property(property="guid", type="string", format="uuid", description="Global unique identifier"),
 *     @OA\Property(property="user_guid", type="string", format="uuid", description="Associated user identifier"),
 *     @OA\Property(property="social_insurance_number", type="string", description="Social Insurance Number"),
 *     @OA\Property(property="government_issued_id", type="string", description="Government issued ID"),
 *     @OA\Property(property="first_name", type="string", description="First name"),
 *     @OA\Property(property="middle_name", type="string", description="Middle name"),
 *     @OA\Property(property="last_name", type="string", description="Last name"),
 *     @OA\Property(property="preferred_name", type="string", description="Preferred name"),
 *     @OA\Property(property="email_address", type="string", format="email", description="Email address"),
 *     @OA\Property(property="phone_number", type="string", description="Phone number"),
 *     @OA\Property(property="alternate_phone_number", type="string", description="Alternate phone number"),
 *     @OA\Property(property="date_of_birth", type="string", format="date", description="Date of birth"),
 *     @OA\Property(property="gender", type="string", description="Gender"),
 *     @OA\Property(property="preferred_pronouns", type="string", description="Preferred pronouns"),
 *     @OA\Property(property="disability_status", type="string", description="Disability status"),
 *     @OA\Property(property="accommodation_needs", type="string", description="Accommodation needs"),
 *     @OA\Property(property="emergency_contact", type="object", description="Emergency contact information"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Last update timestamp"),
 *     @OA\Property(
 *         property="addresses",
 *         type="array",
 *         description="Associated addresses",
 *         @OA\Items(ref="#/components/schemas/IndividualAddress")
 *     ),
 *     @OA\Property(
 *         property="employments",
 *         type="array",
 *         description="Employment history",
 *         @OA\Items(ref="#/components/schemas/IndividualEmployment")
 *     ),
 *     @OA\Property(
 *         property="identities",
 *         type="array",
 *         description="Identity information",
 *         @OA\Items(ref="#/components/schemas/IndividualIdentity")
 *     )
 * )
 */
class IndividualResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'guid' => $this->guid,
            'user_guid' => $this->user_guid,
            'social_insurance_number' => $this->social_insurance_number,
            'government_issued_id' => $this->government_issued_id,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'preferred_name' => $this->preferred_name,
            'email_address' => $this->email_address,
            'phone_number' => $this->phone_number,
            'alternate_phone_number' => $this->alternate_phone_number,
            'date_of_birth' => $this->date_of_birth,
            'gender' => $this->gender,
            'preferred_pronouns' => $this->preferred_pronouns,
            'disability_status' => $this->disability_status,
            'accommodation_needs' => $this->accommodation_needs,
            'emergency_contact' => $this->emergency_contact,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Related data
            'addresses' => IndividualAddressResource::collection($this->whenLoaded('addresses')),
            'employments' => IndividualEmploymentResource::collection($this->whenLoaded('employments')),
            'identities' => IndividualIdentityResource::collection($this->whenLoaded('identities')),
        ];
    }
}
