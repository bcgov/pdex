<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasVersionTracking;
use App\Traits\HasDynamicProfileColumns;

class IndividualEmployment extends Model
{
    use HasFactory, HasVersionTracking, HasDynamicProfileColumns;

    /** The student profile form tab backed by this model. */
    protected string $profileFieldTab = 'employment';

    protected $fillable = [
        'individual_id',
        'user_id',
        'version_number',
        'employment_status',
        'is_looking_for_work',
        'job_title',
        'employer_name',
        'employer_industry',
        'employment_start_date',
        'employment_end_date',
        'work_hours_per_week',
        'monthly_income',
        'is_job_related_to_program',
        'previous_job_title',
        'previous_employer_name',
        'previous_employment_start_date',
        'previous_employment_end_date',
        'reason_for_leaving',
        'career_interest_area',
        'desired_job_title',
        'career_readiness_level',
        'has_career_plan',
        'is_receiving_employment_insurance',
        'is_participating_in_work_study_program',
        'barriers_to_employment',
        'is_current',
        'latest_version',
    ];

    public function individual()
    {
        return $this->belongsTo(Individual::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
