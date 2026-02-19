<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Application;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index(): Response
    {
        $user = auth()->user();
        
        // Get admin statistics with fallbacks
        $stats = [
            'totalUsers' => $this->getSafeUserCount(),
            'activeUsers' => $this->getSafeActiveUserCount(),
            'totalInstitutions' => $this->getSafeInstitutionCount(),
            'activeInstitutions' => $this->getSafeActiveInstitutionCount(),
            'inactiveInstitutions' => $this->getSafeInactiveInstitutionCount(),
            'institutionsWithDli' => $this->getInstitutionsWithDliCount(),
            'institutionTypeBreakdown' => $this->getInstitutionTypeBreakdown(),
            'totalApplications' => $this->getSafeApplicationCount(),
            'applicationStatusBreakdown' => $this->getApplicationStatusBreakdown(),
            'pendingApprovals' => $this->getPendingApprovalsCount(),
            'pendingSecurityApprovals' => $this->getPendingSecurityApprovalsCount(),
            'pendingPrivacyApprovals' => $this->getPendingPrivacyApprovalsCount(),
            'offlineApplications' => $this->getOfflineApplicationsCount(),
            'alertingApplications' => $this->getAlertingApplicationsCount(),
            'failedJobs' => $this->getFailedJobsCount(),
            'systemAlerts' => $this->getSystemAlertsCount(),
        ];

        // Get recent activities
        $recentActivities = $this->getRecentActivities();

        return Inertia::render('Admin::Dashboard', [
            'user' => $user,
            'stats' => $stats,
            'recentActivities' => $recentActivities,
            'users' => (object)[],
            'roles' => (object)[],
            'page' => 'dashboard',
        ]);
    }

    /**
     * Display intake management page.
     */
    public function intake(): Response
    {
        $user = auth()->user();
        
        // Get intake data (placeholder for now)
        $intakes = [
            [
                'id' => 1,
                'institution' => 'University of British Columbia',
                'program' => 'Computer Science',
                'status' => 'pending',
                'submitted_date' => now()->subDays(3)->toDateString(),
                'reviewer' => null,
            ],
            [
                'id' => 2,
                'institution' => 'Simon Fraser University',
                'program' => 'Business Administration',
                'status' => 'approved',
                'submitted_date' => now()->subDays(7)->toDateString(),
                'reviewer' => 'Ministry Admin',
            ],
        ];

        return Inertia::render('Admin::ApplicationEdit', [
            'user' => $user,
            'intakes' => $intakes,
        ]);
    }

    /**
     * Allow admin users to access ministry dashboard.
     * This method sets a session flag and redirects to ministry dashboard.
     */
    public function ministryAccess(): \Illuminate\Http\RedirectResponse
    {
        $user = auth()->user();
        
        // Verify user has admin privileges
        if (!$user->isAdmin()) {
            return redirect()->route('admin.dashboard')
                ->withErrors(['error' => 'Access denied. Admin privileges required.']);
        }
        
        // Set session flag to allow access to ministry dashboard
        session(['admin_accessing_ministry' => true]);
        
        return redirect()->route('ministry.dashboard');
    }

    /**
     * Get count of applications with pending approvals.
     */
    private function getPendingApprovalsCount(): int
    {
        try {
            return Application::where(function ($query) {
                $query->where('security_approval_status', 'pending')
                      ->orWhere('privacy_approval_status', 'pending');
            })->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get count of applications with pending security approvals.
     */
    private function getPendingSecurityApprovalsCount(): int
    {
        try {
            return Application::where('security_approval_status', 'pending')->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get count of applications with pending privacy approvals.
     */
    private function getPendingPrivacyApprovalsCount(): int
    {
        try {
            return Application::where('privacy_approval_status', 'pending')->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get application counts by status.
     */
    private function getApplicationStatusBreakdown(): array
    {
        try {
            return Application::selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get count of applications currently offline.
     */
    private function getOfflineApplicationsCount(): int
    {
        try {
            return Application::where('status', 'offline')->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get count of applications with active alert messages.
     */
    private function getAlertingApplicationsCount(): int
    {
        try {
            return Application::whereNotNull('active_alert_message')
                ->where('active_alert_message', '!=', '')
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get total applications count with a safe fallback.
     */
    private function getSafeApplicationCount(): int
    {
        try {
            return Application::count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get total institutions count with a safe fallback.
     */
    private function getSafeInstitutionCount(): int
    {
        try {
            return Institution::count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get active institutions count with a safe fallback.
     */
    private function getSafeActiveInstitutionCount(): int
    {
        try {
            return Institution::where('active_status', true)->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get inactive institutions count with a safe fallback.
     */
    private function getSafeInactiveInstitutionCount(): int
    {
        try {
            return Institution::where('active_status', false)->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get count of institutions with DLI values.
     */
    private function getInstitutionsWithDliCount(): int
    {
        try {
            return Institution::whereNotNull('dli')->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get institution counts by type.
     */
    private function getInstitutionTypeBreakdown(): array
    {
        try {
            return Institution::selectRaw('institution_type, count(*) as count')
                ->whereNotNull('institution_type')
                ->groupBy('institution_type')
                ->pluck('count', 'institution_type')
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get failed jobs count if table exists.
     */
    private function getFailedJobsCount(): int
    {
        try {
            if (DB::getSchemaBuilder()->hasTable('failed_jobs')) {
                return DB::table('failed_jobs')->count();
            }
        } catch (\Exception $e) {
            return 0;
        }

        return 0;
    }

    /**
     * Get count of system alerts (failed jobs, inactive applications, etc.).
     */
    private function getSystemAlertsCount(): int
    {
        $alerts = 0;
        
        // Count failed jobs if the jobs table exists
        try {
            if (DB::getSchemaBuilder()->hasTable('failed_jobs')) {
                $failedJobs = DB::table('failed_jobs')->count();
                $alerts += $failedJobs;
            }
        } catch (\Exception $e) {
            // Ignore database errors for failed_jobs table
        }
        
        try {
            // Count applications that are offline
            $offlineApps = Application::where('status', 'offline')->count();
            $alerts += $offlineApps;
            
            // Count applications with active alert messages
            $alertApps = Application::whereNotNull('active_alert_message')
                                    ->where('active_alert_message', '!=', '')
                                    ->count();
            $alerts += $alertApps;
        } catch (\Exception $e) {
            // Ignore database errors for applications table
        }
        
        return $alerts;
    }

    /**
     * Get recent activities from various system events.
     */
    private function getRecentActivities(): array
    {
        try {
            $activities = collect();
            
            // Recent user registrations (last 7 days)
            $recentUsers = User::where('created_at', '>=', now()->subDays(7))
                              ->orderBy('created_at', 'desc')
                              ->limit(5)
                              ->get();
            
            foreach ($recentUsers as $user) {
                $activities->push([
                    'id' => 'user_' . $user->id,
                    'title' => 'New User Registration',
                    'description' => 'User "' . ($user->name ?: $user->user_id) . '" registered',
                    'created_at' => $user->created_at,
                    'type' => 'user_registration'
                ]);
            }
            
            // Recent application updates (last 7 days)
            $recentApps = Application::where('updated_at', '>=', now()->subDays(7))
                                    ->orderBy('updated_at', 'desc')
                                    ->limit(5)
                                    ->get();
            
            foreach ($recentApps as $app) {
                $title = 'Application Updated';
                $description = 'Application "' . $app->name . '" was updated';
                
                // More specific descriptions based on approval status
                if ($app->security_approval_status === 'approved' && $app->security_approved_at >= now()->subDays(7)) {
                    $title = 'Security Approval Granted';
                    $description = 'Security approval granted for "' . $app->name . '"';
                } elseif ($app->privacy_approval_status === 'approved' && $app->privacy_approved_at >= now()->subDays(7)) {
                    $title = 'Privacy Approval Granted';
                    $description = 'Privacy approval granted for "' . $app->name . '"';
                }
                
                $activities->push([
                    'id' => 'app_' . $app->id,
                    'title' => $title,
                    'description' => $description,
                    'created_at' => $app->updated_at,
                    'type' => 'application_update'
                ]);
            }
            
            // Sort all activities by date and return the most recent 10
            return $activities->sortByDesc('created_at')
                             ->take(10)
                             ->values()
                             ->toArray();
        } catch (\Exception $e) {
            // Return empty array if database queries fail
            return [];
        }
    }

    /**
     * Safely get total user count with fallback.
     */
    private function getSafeUserCount(): int
    {
        try {
            return User::count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Safely get active user count with fallback.
     */
    private function getSafeActiveUserCount(): int
    {
        try {
            return User::where('is_active', true)->count();
        } catch (\Exception $e) {
            return 0;
        }
    }
}
