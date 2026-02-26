<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Application;
use Illuminate\Support\Str;

class ApplicationSeeder extends Seeder
{
    public function run(): void
    {
        // seed the following only on dev or local environments
        if( app()->environment() === ('production' || 'testing')){
            return;
        }

        // ACTIVE APPLICATIONS (Ready to use)
        
        // Student Active - No Alert
        Application::create([
            'guid' => Str::random(32),
            'name' => 'BC Student Portal',
            'description' => 'Your gateway to provincial student services, financial aid, and academic records.',
            'bcsc_redirect_url' => 'https://student-portal.edu.bc.ca/bcsc-callback',
            'contact_name' => 'Sarah Chen',
            'contact_email' => 'sarah.chen@edu.bc.ca',
            'contact_phone' => '604-555-1234',
            'status' => 'active',
            'security_approval_status' => 'approved',
            'security_approved_at' => now()->subDays(10),
            'security_approved_by' => null,
            'privacy_approval_status' => 'approved',
            'privacy_approved_at' => now()->subDays(8),
            'privacy_approved_by' => null,
            'bcsc_enabled' => true,
            'idir_enabled' => false,
            'bceid_enabled' => false,
            'comments' => 'Fully operational student services portal.',
            'stra_provided' => true,
            'pia_provided' => true,
            'active_alert_message' => null,
            'offline_alert_message' => null,
        ]);

        // Student Active - With Alert
        Application::create([
            'guid' => Str::random(32),
            'name' => 'BC Scholarship Hub',
            'description' => 'Apply for scholarships, grants, and bursaries available to BC students.',
            'bcsc_redirect_url' => 'https://scholarships.edu.bc.ca/bcsc-callback',
            'contact_name' => 'Michael Rodriguez',
            'contact_email' => 'michael.rodriguez@edu.bc.ca',
            'contact_phone' => '778-555-9876',
            'status' => 'active',
            'security_approval_status' => 'approved',
            'security_approved_at' => now()->subDays(15),
            'security_approved_by' => null,
            'privacy_approval_status' => 'approved',
            'privacy_approved_at' => now()->subDays(12),
            'privacy_approved_by' => null,
            'bcsc_enabled' => true,
            'idir_enabled' => false,
            'bceid_enabled' => false,
            'comments' => 'Active with new scholarship opportunities available.',
            'stra_provided' => true,
            'pia_provided' => true,
            'active_alert_message' => 'New scholarship applications are now open for the 2025-2026 academic year!',
            'offline_alert_message' => null,
        ]);

        // Ministry Active - No Alert
        Application::create([
            'guid' => Str::random(32),
            'name' => 'Ministry Operations Center',
            'description' => 'Central hub for ministry staff to access operational tools and resources.',
            'idir_redirect_url' => 'https://operations.gov.bc.ca/idir-callback',
            'contact_name' => 'Jennifer Walsh',
            'contact_email' => 'jennifer.walsh@gov.bc.ca',
            'contact_phone' => '250-555-2468',
            'status' => 'active',
            'security_approval_status' => 'approved',
            'security_approved_at' => now()->subDays(20),
            'security_approved_by' => null,
            'privacy_approval_status' => 'approved',
            'privacy_approved_at' => now()->subDays(18),
            'privacy_approved_by' => null,
            'bcsc_enabled' => false,
            'idir_enabled' => true,
            'bceid_enabled' => false,
            'comments' => 'Primary ministry operations platform.',
            'stra_provided' => true,
            'pia_provided' => true,
            'active_alert_message' => null,
            'offline_alert_message' => null,
        ]);

        // Ministry Active - With Alert
        Application::create([
            'guid' => Str::random(32),
            'name' => 'Policy Management System',
            'description' => 'Comprehensive system for managing government policies and procedures.',
            'idir_redirect_url' => 'https://policy-mgmt.gov.bc.ca/idir-callback',
            'contact_name' => 'David Thompson',
            'contact_email' => 'david.thompson@gov.bc.ca',
            'contact_phone' => '250-555-3579',
            'status' => 'active',
            'security_approval_status' => 'approved',
            'security_approved_at' => now()->subDays(5),
            'security_approved_by' => null,
            'privacy_approval_status' => 'approved',
            'privacy_approved_at' => now()->subDays(3),
            'privacy_approved_by' => null,
            'bcsc_enabled' => false,
            'idir_enabled' => true,
            'bceid_enabled' => false,
            'comments' => 'Recently updated with new policy templates.',
            'stra_provided' => true,
            'pia_provided' => true,
            'active_alert_message' => 'New policy templates are now available in the template library.',
            'offline_alert_message' => null,
        ]);

        // Institution Active - No Alert
        Application::create([
            'guid' => Str::random(32),
            'name' => 'Campus Connect',
            'description' => 'Institutional portal for campus administration and student management.',
            'bceid_redirect_url' => 'https://campus-connect.edu.bc.ca/bceid-callback',
            'contact_name' => 'Dr. Amanda Foster',
            'contact_email' => 'amanda.foster@campus.edu.bc.ca',
            'contact_phone' => '604-555-4680',
            'status' => 'active',
            'security_approval_status' => 'approved',
            'security_approved_at' => now()->subDays(7),
            'security_approved_by' => null,
            'privacy_approval_status' => 'approved',
            'privacy_approved_at' => now()->subDays(6),
            'privacy_approved_by' => null,
            'bcsc_enabled' => false,
            'idir_enabled' => false,
            'bceid_enabled' => true,
            'comments' => 'Institutional administration platform.',
            'stra_provided' => true,
            'pia_provided' => true,
            'active_alert_message' => null,
            'offline_alert_message' => null,
        ]);

        // Institution Active - With Alert
        Application::create([
            'guid' => Str::random(32),
            'name' => 'Enrollment Management Suite',
            'description' => 'Advanced tools for managing student enrollment, registration, and academic planning.',
            'bceid_redirect_url' => 'https://enrollment.edu.bc.ca/bceid-callback',
            'contact_name' => 'Robert Kim',
            'contact_email' => 'robert.kim@enrollment.edu.bc.ca',
            'contact_phone' => '778-555-1357',
            'status' => 'active',
            'security_approval_status' => 'approved',
            'security_approved_at' => now()->subDays(2),
            'security_approved_by' => null,
            'privacy_approval_status' => 'approved',
            'privacy_approved_at' => now()->subDays(1),
            'privacy_approved_by' => null,
            'bcsc_enabled' => false,
            'idir_enabled' => false,
            'bceid_enabled' => true,
            'comments' => 'Recently enhanced with new enrollment features.',
            'stra_provided' => true,
            'pia_provided' => true,
            'active_alert_message' => 'Fall 2025 enrollment period is now open. New dashboard features available!',
            'offline_alert_message' => null,
        ]);

        // OFFLINE APPLICATIONS (Under maintenance)

        // Student Offline - With Alert
        Application::create([
            'guid' => Str::random(32),
            'name' => 'BC Student Financial Aid',
            'description' => 'Apply for and manage your provincial student loans and grants.',
            'bcsc_redirect_url' => 'https://financial-aid.edu.bc.ca/bcsc-callback',
            'contact_name' => 'Lisa Park',
            'contact_email' => 'lisa.park@studentaid.bc.ca',
            'contact_phone' => '604-555-7531',
            'status' => 'offline',
            'security_approval_status' => 'approved',
            'security_approved_at' => now()->subDays(30),
            'security_approved_by' => null,
            'privacy_approval_status' => 'approved',
            'privacy_approved_at' => now()->subDays(28),
            'privacy_approved_by' => null,
            'bcsc_enabled' => true,
            'idir_enabled' => false,
            'bceid_enabled' => false,
            'comments' => 'Scheduled maintenance for system upgrades.',
            'stra_provided' => true,
            'pia_provided' => true,
            'active_alert_message' => null,
            'offline_alert_message' => 'System maintenance in progress. Expected completion: August 5th at 6:00 AM PDT.',
        ]);

        // Ministry Offline - With Alert
        Application::create([
            'guid' => Str::random(32),
            'name' => 'Document Management System',
            'description' => 'Secure document storage and collaboration platform for government staff.',
            'idir_redirect_url' => 'https://docs.gov.bc.ca/idir-callback',
            'contact_name' => 'Kevin O\'Brien',
            'contact_email' => 'kevin.obrien@gov.bc.ca',
            'contact_phone' => '250-555-8642',
            'status' => 'offline',
            'security_approval_status' => 'approved',
            'security_approved_at' => now()->subDays(45),
            'security_approved_by' => null,
            'privacy_approval_status' => 'approved',
            'privacy_approved_at' => now()->subDays(43),
            'privacy_approved_by' => null,
            'bcsc_enabled' => false,
            'idir_enabled' => true,
            'bceid_enabled' => false,
            'comments' => 'Undergoing security updates and performance improvements.',
            'stra_provided' => true,
            'pia_provided' => true,
            'active_alert_message' => null,
            'offline_alert_message' => 'Security updates in progress. Service will resume August 3rd at 9:00 AM PDT.',
        ]);

        // Institution Offline - With Alert
        Application::create([
            'guid' => Str::random(32),
            'name' => 'Academic Records Portal',
            'description' => 'Comprehensive academic records management for institutional administrators.',
            'bceid_redirect_url' => 'https://records.edu.bc.ca/bceid-callback',
            'contact_name' => 'Dr. Elena Vasquez',
            'contact_email' => 'elena.vasquez@records.edu.bc.ca',
            'contact_phone' => '778-555-9753',
            'status' => 'offline',
            'security_approval_status' => 'approved',
            'security_approved_at' => now()->subDays(25),
            'security_approved_by' => null,
            'privacy_approval_status' => 'approved',
            'privacy_approved_at' => now()->subDays(23),
            'privacy_approved_by' => null,
            'bcsc_enabled' => false,
            'idir_enabled' => false,
            'bceid_enabled' => true,
            'comments' => 'Database migration and system optimization.',
            'stra_provided' => true,
            'pia_provided' => true,
            'active_alert_message' => null,
            'offline_alert_message' => 'Database migration in progress. Estimated completion: August 4th at 2:00 PM PDT.',
        ]);

        // INACTIVE APPLICATIONS (Not accessible)

        // Student Inactive - No Alert
        Application::create([
            'guid' => Str::random(32),
            'name' => 'Career Services Network',
            'description' => 'Connect with career counselors and explore job opportunities in BC.',
            'bcsc_redirect_url' => 'https://careers.edu.bc.ca/bcsc-callback',
            'contact_name' => 'Thomas Wright',
            'contact_email' => 'thomas.wright@careers.bc.ca',
            'contact_phone' => '604-555-4826',
            'status' => 'inactive',
            'security_approval_status' => 'pending',
            'privacy_approval_status' => 'approved',
            'privacy_approved_at' => now()->subDays(15),
            'privacy_approved_by' => null,
            'bcsc_enabled' => true,
            'idir_enabled' => false,
            'bceid_enabled' => false,
            'comments' => 'Awaiting final security approval.',
            'stra_provided' => false,
            'pia_provided' => true,
            'active_alert_message' => null,
            'offline_alert_message' => null,
        ]);

        // Ministry Inactive - No Alert
        Application::create([
            'guid' => Str::random(32),
            'name' => 'Resource Planning Hub',
            'description' => 'Strategic planning and resource allocation tools for ministry departments.',
            'idir_redirect_url' => 'https://planning.gov.bc.ca/idir-callback',
            'contact_name' => 'Rachel Green',
            'contact_email' => 'rachel.green@planning.gov.bc.ca',
            'contact_phone' => '250-555-3691',
            'status' => 'inactive',
            'security_approval_status' => 'approved',
            'security_approved_at' => now()->subDays(10),
            'security_approved_by' => null,
            'privacy_approval_status' => 'pending',
            'bcsc_enabled' => false,
            'idir_enabled' => true,
            'bceid_enabled' => false,
            'comments' => 'Privacy review in progress.',
            'stra_provided' => true,
            'pia_provided' => false,
            'active_alert_message' => null,
            'offline_alert_message' => null,
        ]);

        // Institution Inactive - No Alert
        Application::create([
            'guid' => Str::random(32),
            'name' => 'Research Collaboration Platform',
            'description' => 'Facilitate research partnerships and grant management across institutions.',
            'bceid_redirect_url' => 'https://research.edu.bc.ca/bceid-callback',
            'contact_name' => 'Dr. Marcus Johnson',
            'contact_email' => 'marcus.johnson@research.bc.ca',
            'contact_phone' => '778-555-8024',
            'status' => 'inactive',
            'security_approval_status' => 'pending',
            'privacy_approval_status' => 'pending',
            'bcsc_enabled' => false,
            'idir_enabled' => false,
            'bceid_enabled' => true,
            'comments' => 'Initial review phase - both approvals pending.',
            'stra_provided' => false,
            'pia_provided' => false,
            'active_alert_message' => null,
            'offline_alert_message' => null,
        ]);

        // MULTI-AUTH APPLICATIONS (Available to multiple user types)

        // Multi-auth Active with different alerts
        Application::create([
            'guid' => Str::random(32),
            'name' => 'BC Education Gateway',
            'description' => 'Unified access point for educational services across BC institutions.',
            'bcsc_redirect_url' => 'http://127.0.0.1:8232/test-gateway/student',
            'idir_redirect_url' => 'http://127.0.0.1:8232/test-gateway/student',
            'bceid_redirect_url' => 'http://127.0.0.1:8232/test-gateway/student',
            'contact_name' => 'Dr. Patricia Lee',
            'contact_email' => 'patricia.lee@education.bc.ca',
            'contact_phone' => '604-555-1472',
            'status' => 'active',
            'security_approval_status' => 'approved',
            'security_approved_at' => now()->subDays(14),
            'security_approved_by' => null,
            'privacy_approval_status' => 'approved',
            'privacy_approved_at' => now()->subDays(12),
            'privacy_approved_by' => null,
            'bcsc_enabled' => true,
            'idir_enabled' => true,
            'bceid_enabled' => true,
            'comments' => 'Universal education platform serving all user types.',
            'stra_provided' => true,
            'pia_provided' => true,
            'active_alert_message' => 'New collaborative features now available across all authentication methods!',
            'offline_alert_message' => null,
        ]);

        // Multi-auth Offline
        Application::create([
            'guid' => Str::random(32),
            'name' => 'Provincial Data Exchange',
            'description' => 'Secure data sharing platform for cross-institutional collaboration.',
            'bcsc_redirect_url' => 'https://data-exchange.bc.ca/bcsc-callback',
            'idir_redirect_url' => 'https://data-exchange.bc.ca/idir-callback',
            'bceid_redirect_url' => 'https://data-exchange.bc.ca/bceid-callback',
            'contact_name' => 'James Wilson',
            'contact_email' => 'james.wilson@dataexchange.bc.ca',
            'contact_phone' => '250-555-7896',
            'status' => 'offline',
            'security_approval_status' => 'approved',
            'security_approved_at' => now()->subDays(60),
            'security_approved_by' => null,
            'privacy_approval_status' => 'approved',
            'privacy_approved_at' => now()->subDays(58),
            'privacy_approved_by' => null,
            'bcsc_enabled' => true,
            'idir_enabled' => true,
            'bceid_enabled' => true,
            'comments' => 'Major infrastructure upgrade in progress.',
            'stra_provided' => true,
            'pia_provided' => true,
            'active_alert_message' => null,
            'offline_alert_message' => 'Infrastructure upgrade in progress. All services will resume August 6th at 8:00 AM PDT.',
        ]);
    }
}
