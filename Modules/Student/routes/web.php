<?php

use Illuminate\Support\Facades\Route;
use Modules\Student\Http\Controllers\StudentController;
use Modules\Student\Http\Controllers\StudentLogoutController;

Route::prefix('student')->group(function () {
    Route::group([
        'middleware' => ['auth', 'student_active'],
        'as' => 'student.',
    ], function () {
        // Logout route
        Route::post('logout', [StudentLogoutController::class, 'logout'])->name('logout');
        // Dashboard route with profile check
        Route::get('/', [StudentController::class, 'index'])
            ->middleware('student_profile')
            ->name('dashboard');
        
        // Application launch route
        Route::post('launch-application/{application}', [StudentController::class, 'launchApplication'])
            ->name('launch.application');
        
        // Profile management routes (no profile check to avoid infinite redirects)
        Route::get('profile', [StudentController::class, 'profile'])->name('profile.index');
        Route::get('profile/create', [StudentController::class, 'create'])->name('profile.create');
        Route::post('profile', [StudentController::class, 'store'])->name('profile.store');
        // Route::get('profile/edit', [StudentController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [StudentController::class, 'update'])->name('profile.update');
        Route::delete('profile', [StudentController::class, 'destroy'])->name('profile.destroy');
        Route::get('profile/versions', [StudentController::class, 'versions'])->name('profile.versions');
    });
});
