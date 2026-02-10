<?php

use Illuminate\Support\Facades\Route;
use Modules\Ministry\Http\Controllers\MinistryController;
use Modules\Ministry\Http\Controllers\MinistryLogoutController;

Route::prefix('ministry')->group(function () {
    Route::group([
        'middleware' => ['auth', 'ministry_active'],
        'as' => 'ministry.',
    ], function () {
        // Logout route
        Route::post('logout', [MinistryLogoutController::class, 'logout'])->name('logout');
        Route::get('/', [MinistryController::class, 'index'])->name('dashboard');
        Route::get('reports', [MinistryController::class, 'reports'])->name('reports.index');
        
        // Admin-only ministry routes
        Route::group([
            'middleware' => ['ministry_admin'],
        ], function () {
            Route::get('admin', [MinistryController::class, 'admin'])->name('admin.index');
            Route::get('users', [MinistryController::class, 'users'])->name('users.index');
            Route::get('settings', [MinistryController::class, 'settings'])->name('settings.index');
        });
    });
});
