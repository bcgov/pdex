<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Http\Controllers\RedirectResponse;

class WelcomeController extends Controller
{
    /**
     * Display the public welcome page.
     */
    public function index(): Response | RedirectResponse
    {
        // force redirect to /login page and skip welcome page for now since we don't have public content yet
        return RedirectResponse::route('login');

        return Inertia::render('Welcome', [
            'stats' => [
                'applications' => 12,
                'institutions' => 150,
                'students_served' => 25000,
                'data_exchanges' => 500000,
            ],
            'features' => [
                [
                    'icon' => 'bi bi-shield-shaded',
                    'title' => 'Secure Data Exchange',
                    'description' => 'Advanced security protocols ensure your personal information remains protected during data transfers between institutions.',
                ],
                [
                    'icon' => 'bi bi-building',
                    'title' => 'Institutional Integration',
                    'description' => 'Seamlessly connect post-secondary institutions across BC for efficient student data management.',
                ],
                [
                    'icon' => 'bi bi-mortarboard',
                    'title' => 'Student-Centered',
                    'description' => 'Empowering students with control over their educational data and application processes.',
                ],
                [
                    'icon' => 'bi bi-graph-up-arrow',
                    'title' => 'Real-Time Processing',
                    'description' => 'Lightning-fast data processing and application status updates keep you informed every step of the way.',
                ],
            ],
            'portals' => [
                [
                    'name' => 'Student Portal',
                    'description' => 'Access your educational profile, manage applications, and track your academic journey.',
                    'icon' => 'bi bi-mortarboard',
                    'color' => 'primary',
                ],
                [
                    'name' => 'Institution Portal',
                    'description' => 'Institutional administrators can manage student data, applications, and institutional settings.',
                    'icon' => 'bi bi-building',
                    'color' => 'success',
                ],
                [
                    'name' => 'Ministry Portal',
                    'description' => 'Government oversight and management of the provincial data exchange system.',
                    'icon' => 'bi bi-bank',
                    'color' => 'info',
                ],
            ],
        ]);
    }
}
