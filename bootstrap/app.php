<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use App\Models\Role;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trust CloudFront and ALB proxies for proper URL generation
        $middleware->trustProxies(at: '*');
        
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);
        $middleware->validateCsrfTokens(except: ['/login']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Configure authentication redirection
        $middleware->redirectGuestsTo('/login');
        // Redirect authenticated users to the dashboard allowed by their role: /ministry, /institution, /student

        // $middleware->redirectUsersTo('/');
        // Redirect authenticated users based on their role
        $middleware->redirectUsersTo(function ($request) {
            $user = $request->user();
            if ($user && $user->role) {
                // Use the Role model to determine the redirect path
                return Role::redirectPath($user->role);
            }
            return '/';
        });


        // Register custom middleware aliases
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'ministry_active' => \App\Http\Middleware\Ministry\IsActive::class,
            'ministry_admin' => \App\Http\Middleware\Ministry\IsAdmin::class,
            'institution_active' => \App\Http\Middleware\Institution\IsActive::class,
            'institution_admin' => \App\Http\Middleware\Institution\IsAdmin::class,
            'student_active' => \App\Http\Middleware\Student\IsActive::class,
            'student_profile' => \App\Http\Middleware\Student\CheckProfile::class,
            'super_admin' => \App\Http\Middleware\SuperAdmin::class,
            'prevent_cross_idp' => \App\Http\Middleware\PreventCrossIdpAccess::class,
            'oauth.token' => \App\Http\Middleware\ValidateOAuthToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
