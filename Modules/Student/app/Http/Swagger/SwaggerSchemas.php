<?php

namespace Modules\Student\Http\Swagger;

/**
 * @OA\Schema(
 *     schema="IndividualCreateRequest",
 *     type="object",
 *     title="Individual Create Request",
 *     description="Request body for creating a new individual profile",
 *     required={"social_insurance_number", "first_name", "last_name", "email_address"},
 *     @OA\Property(property="social_insurance_number", type="string", description="Social Insurance Number", example="123-456-789"),
 *     @OA\Property(property="government_issued_id", type="string", description="Government issued ID", example="DL123456789"),
 *     @OA\Property(property="first_name", type="string", description="First name", example="John"),
 *     @OA\Property(property="middle_name", type="string", description="Middle name", example="Michael"),
 *     @OA\Property(property="last_name", type="string", description="Last name", example="Doe"),
 *     @OA\Property(property="preferred_name", type="string", description="Preferred name", example="Johnny"),
 *     @OA\Property(property="email_address", type="string", format="email", description="Email address", example="john.doe@example.com"),
 *     @OA\Property(property="phone_number", type="string", description="Phone number", example="+1-555-123-4567"),
 *     @OA\Property(property="alternate_phone_number", type="string", description="Alternate phone number", example="+1-555-987-6543"),
 *     @OA\Property(property="date_of_birth", type="string", format="date", description="Date of birth", example="1995-05-15"),
 *     @OA\Property(property="gender", type="string", description="Gender", example="male", enum={"male", "female", "other", "prefer_not_to_say"}),
 *     @OA\Property(property="preferred_pronouns", type="string", description="Preferred pronouns", example="he/him"),
 *     @OA\Property(property="disability_status", type="string", description="Disability status", example="yes", enum={"yes", "no", "unknown"}),
 *     @OA\Property(property="accommodation_needs", type="string", description="Accommodation needs", example="Wheelchair access required"),
 *     @OA\Property(
 *         property="emergency_contact",
 *         type="object",
 *         description="Emergency contact information",
 *         @OA\Property(property="name", type="string", example="Jane Doe"),
 *         @OA\Property(property="relationship", type="string", example="Spouse"),
 *         @OA\Property(property="phone", type="string", example="+1-555-234-5678"),
 *         @OA\Property(property="email", type="string", format="email", example="jane.doe@example.com")
 *     ),
 *     @OA\Property(
 *         property="current_address",
 *         type="object",
 *         description="Current address",
 *         required={"address_line1", "city", "province", "postal_code", "country"},
 *         @OA\Property(property="address_line1", type="string", example="123 Main Street"),
 *         @OA\Property(property="address_line2", type="string", example="Apt 4B"),
 *         @OA\Property(property="city", type="string", example="Vancouver"),
 *         @OA\Property(property="province", type="string", example="BC"),
 *         @OA\Property(property="postal_code", type="string", example="V6B 1A1"),
 *         @OA\Property(property="country", type="string", example="Canada")
 *     ),
 *     @OA\Property(property="use_different_mailing_address", type="boolean", description="Use different mailing address", example=false),
 *     @OA\Property(
 *         property="mailing_address",
 *         type="object",
 *         description="Mailing address (if different from current)",
 *         @OA\Property(property="address_line1", type="string", example="456 Oak Avenue"),
 *         @OA\Property(property="address_line2", type="string", example="Suite 200"),
 *         @OA\Property(property="city", type="string", example="Victoria"),
 *         @OA\Property(property="province", type="string", example="BC"),
 *         @OA\Property(property="postal_code", type="string", example="V8W 1P6"),
 *         @OA\Property(property="country", type="string", example="Canada")
 *     ),
 *     @OA\Property(
 *         property="current_employment",
 *         type="object",
 *         description="Current employment information",
 *         @OA\Property(property="employment_status", type="string", example="employed"),
 *         @OA\Property(property="is_looking_for_work", type="boolean", example=false),
 *         @OA\Property(property="job_title", type="string", example="Software Developer"),
 *         @OA\Property(property="employer_name", type="string", example="Tech Corp Inc"),
 *         @OA\Property(property="employer_industry", type="string", example="Technology"),
 *         @OA\Property(property="employment_start_date", type="string", format="date", example="2023-01-15"),
 *         @OA\Property(property="work_hours_per_week", type="integer", example=40),
 *         @OA\Property(property="monthly_income", type="number", format="float", example=5000.00)
 *     ),
 *     @OA\Property(
 *         property="identity",
 *         type="object",
 *         description="Identity information",
 *         @OA\Property(property="citizenship_status", type="string", example="canadian_citizen"),
 *         @OA\Property(property="country_of_birth", type="string", example="Canada"),
 *         @OA\Property(property="language_spoken_at_home", type="string", example="English"),
 *         @OA\Property(property="is_visible_minority", type="string", example="no", enum={"yes", "no", "unknown"}),
 *         @OA\Property(property="indigenous_status", type="string", example="no", enum={"yes", "no", "unknown"}),
 *         @OA\Property(property="refugee_status", type="boolean", example=false)
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="IndividualUpdateRequest",
 *     type="object",
 *     title="Individual Update Request",
 *     description="Request body for updating an individual profile",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/IndividualCreateRequest")
 *     }
 * )
 */
class SwaggerSchemas
{
    // This class is only used for Swagger documentation
    // The actual request validation is handled by the existing request classes
}
