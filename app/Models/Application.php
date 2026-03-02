<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Application extends Model
{
    use HasFactory, SoftDeletes;

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
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'guid';
    }

    protected $fillable = [
        'guid',
        'name',
        'description',
        'info_url',
        'info_label',
        'bcsc_redirect_url',
        'idir_redirect_url',
        'bceid_redirect_url',
        'contact_name',
        'contact_email',
        'contact_phone',
        'status',
        'security_approval_status',
        'security_approval_notes',
        'security_approved_at',
        'security_approved_by',
        'privacy_approval_status',
        'privacy_approval_notes',
        'privacy_approved_at',
        'privacy_approved_by',
        'active_alert_message',
        'offline_alert_message',
        'offline_start_time',
        'offline_end_time',
        'bcsc_enabled',
        'idir_enabled',
        'bceid_enabled',
        'comments',
        'stra_provided',
        'pia_provided',
        'client_id',
        'client_secret',
        'approval_notes',
        'profile_integration_ready',
    ];

    protected $casts = [
        'bcsc_enabled' => 'boolean',
        'idir_enabled' => 'boolean',
        'bceid_enabled' => 'boolean',
        'stra_provided' => 'boolean',
        'pia_provided' => 'boolean',
        'security_approved_at' => 'datetime',
        'privacy_approved_at' => 'datetime',
        'offline_start_time' => 'datetime',
        'offline_end_time' => 'datetime',
    ];

    /**
     * Get the user who approved security for this application.
     */
    public function securityApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'security_approved_by');
    }

    /**
     * Get the user who approved privacy for this application.
     */
    public function privacyApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'privacy_approved_by');
    }

    /**
     * Legacy relationship for backward compatibility.
     * Returns the security approver if available, otherwise privacy approver.
     */
    public function approver(): BelongsTo
    {
        // For backward compatibility, return security approver
        return $this->belongsTo(User::class, 'security_approved_by');
    }

    /**
     * Get the security approval status badge color.
     */
    public function getSecurityApprovalStatusBadgeAttribute(): string
    {
        return match($this->security_approval_status) {
            'approved' => 'success',
            'rejected' => 'danger',
            'pending' => 'warning',
            default => 'secondary'
        };
    }

    /**
     * Get the privacy approval status badge color.
     */
    public function getPrivacyApprovalStatusBadgeAttribute(): string
    {
        return match($this->privacy_approval_status) {
            'approved' => 'success',
            'rejected' => 'danger',
            'pending' => 'warning',
            default => 'secondary'
        };
    }

    /**
     * Get the status badge color.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'active' => 'success',
            'inactive' => 'secondary',
            'offline' => 'danger',
            default => 'secondary'
        };
    }

    /**
     * Check if both security and privacy approvals are complete.
     */
    public function isFinallyApproved(): bool
    {
        return $this->security_approval_status === 'approved' && 
               $this->privacy_approval_status === 'approved';
    }

    /**
     * Check if the application can have its approvals modified.
     */
    public function canModifyApprovals(): bool
    {
        return !$this->isFinallyApproved();
    }

    /**
     * Check if the application is accessible (both approvals are required).
     */
    public function isAccessible(): bool
    {
        return $this->isFinallyApproved() && $this->status === 'active';
    }

    /**
     * Get the OAuth client for this application
     */
    public function oauthClient()
    {
        return $this->hasOne(OAuthClient::class);
    }

    /**
     * Get the individual data permissions for this application
     */
    public function individualPermissions()
    {
        return $this->hasMany(ApplicationIndividualPermission::class);
    }

    /**
     * Get the API access permissions for this application
     */
    public function apiPermissions()
    {
        return $this->hasMany(ApplicationApiPermission::class);
    }

    /**
     * Legacy method - redirects to individual permissions for backward compatibility
     */
    public function dataPermissions()
    {
        return $this->hasMany(ApplicationIndividualPermission::class);
    }

    /**
     * Check if application has access to a specific individual data column
     */
    public function hasIndividualDataAccess(string $tableName, string $columnName, string $accessType = 'read'): bool
    {
        return ApplicationIndividualPermission::where('application_id', $this->id)
            ->where('table_name', $tableName)
            ->where('column_name', $columnName)
            ->where($accessType === 'write' ? 'can_write' : 'can_read', true)
            ->exists();
    }

    /**
     * Check if application has API access to a specific data column
     */
    public function hasApiAccess(string $tableName, string $columnName, string $accessType = 'read'): bool
    {
        return ApplicationApiPermission::where('application_id', $this->id)
            ->where('table_name', $tableName)
            ->where('column_name', $columnName)
            ->where($accessType === 'write' ? 'can_write' : 'can_read', true)
            ->exists();
    }

    /**
     * Legacy method - redirects to individual data access for backward compatibility
     */
    public function hasDataAccess(string $tableName, string $columnName, string $accessType = 'read'): bool
    {
        return $this->hasIndividualDataAccess($tableName, $columnName, $accessType);
    }

    /**
     * Get all accessible individual data columns for a table
     */
    public function getAccessibleIndividualColumns(string $tableName, string $accessType = 'read'): array
    {
        return ApplicationIndividualPermission::where('application_id', $this->id)
            ->where('table_name', $tableName)
            ->where($accessType === 'write' ? 'can_write' : 'can_read', true)
            ->pluck('column_name')
            ->toArray();
    }

    /**
     * Get all accessible API columns for a table
     */
    public function getAccessibleApiColumns(string $tableName, string $accessType = 'read'): array
    {
        return ApplicationApiPermission::where('application_id', $this->id)
            ->where('table_name', $tableName)
            ->where($accessType === 'write' ? 'can_write' : 'can_read', true)
            ->pluck('column_name')
            ->toArray();
    }

    /**
     * Legacy method - redirects to individual columns for backward compatibility
     */
    public function getAccessibleColumns(string $tableName, string $accessType = 'read'): array
    {
        return $this->getAccessibleIndividualColumns($tableName, $accessType);
    }
}
