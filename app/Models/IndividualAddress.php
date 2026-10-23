<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasVersionTracking;
use App\Traits\HasDynamicProfileColumns;

class IndividualAddress extends Model
{
    use HasFactory, HasVersionTracking, HasDynamicProfileColumns;

    /** The student profile form tab backed by this model. */
    protected string $profileFieldTab = 'address';

    protected $fillable = [
        'individual_id',
        'user_id',
        'version_number',
        'address_type',
        'address_line1',
        'address_line2',
        'city',
        'province',
        'postal_code',
        'country',
        'is_primary',
        'is_active',
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
