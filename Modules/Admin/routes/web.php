<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\AdminController;
use Modules\Admin\Http\Controllers\AdminUserController;
use Modules\Admin\Http\Controllers\ApplicationController;
use Modules\Admin\Http\Controllers\AdminLogoutController;

use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\InstitutionSiteController;
use App\Http\Controllers\InstitutionRelationshipController;

Route::prefix('admin')->group(function () {
    Route::group([
        'middleware' => ['auth', 'admin'],
        'as' => 'admin.',
    ], function () {
        // Logout route
        Route::post('logout', [AdminLogoutController::class, 'logout'])->name('logout');
        Route::get('/', [AdminController::class, 'index'])->name('dashboard');
        // Route::get('intake', [AdminController::class, 'intake'])->name('intake.index');
        
        // Ministry Dashboard Access for Admin Users
        Route::get('ministry-access', [AdminController::class, 'ministryAccess'])->name('ministry-access');
        
        // Admin User Management
        Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
        Route::patch('users/update-roles/{user}', [AdminUserController::class, 'updateRoles'])->name('users.update-roles');
        Route::patch('users/toggle-status/{user}', [AdminUserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::delete('users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
        Route::patch('users/restore/{id}', [AdminUserController::class, 'restore'])->name('users.restore');
        Route::delete('users/force-delete/{id}', [AdminUserController::class, 'forceDelete'])->name('users.force-delete');

        // Applications CRUD
        Route::get('applications', [ApplicationController::class, 'index'])->name('applications.index');
        Route::get('applications/create', [ApplicationController::class, 'create'])->name('applications.create');
        Route::post('applications', [ApplicationController::class, 'store'])->name('applications.store');
        Route::get('applications/edit/{application}', [ApplicationController::class, 'edit'])->name('applications.edit');
        Route::put('applications/{application}', [ApplicationController::class, 'update'])->name('applications.update');
        Route::put('applications/{application}/approver-update', [ApplicationController::class, 'approverUpdate'])->name('applications.approver-update');
        Route::delete('applications/{application}', [ApplicationController::class, 'destroy'])->name('applications.destroy');
        Route::patch('applications/{application}/security-approval', [ApplicationController::class, 'securityApproval'])->name('applications.security-approval');
        Route::patch('applications/{application}/privacy-approval', [ApplicationController::class, 'privacyApproval'])->name('applications.privacy-approval');
        // Route::patch('applications/{application}/manager-update', [ApplicationController::class, 'managerUpdate'])->name('applications.manager-update');
        Route::patch('applications/toggle-status/{application}', [ApplicationController::class, 'toggleStatus'])->name('applications.toggle-status');
        Route::patch('applications/restore/{id}', [ApplicationController::class, 'restore'])->name('applications.restore');
        Route::delete('applications/force-delete/{id}', [ApplicationController::class, 'forceDelete'])->name('applications.force-delete');

        // Institution Management
        Route::resource('institutions', InstitutionController::class);
        Route::get('institutions/{institution}/stats', [InstitutionController::class, 'stats'])->name('institutions.stats');
        Route::patch('institutions/{institution}/toggle-status', [InstitutionController::class, 'toggleStatus'])->name('institutions.toggle-status');
        Route::patch('institutions/{institution}/users/{user}/toggle-status', [InstitutionController::class, 'toggleUserStatus'])->name('institutions.users.toggle-status');
        Route::patch('institutions/{institution}/users/{user}/toggle-role', [InstitutionController::class, 'toggleUserRole'])->name('institutions.users.toggle-role');

        // Institution Sites Management
        Route::resource('institutions.sites', InstitutionSiteController::class)->except(['index']);
        Route::get('institutions/{institution}/sites', [InstitutionSiteController::class, 'index'])->name('institutions.sites.index');
        Route::patch('institutions/{institution}/sites/{site}/toggle-status', [InstitutionSiteController::class, 'toggleStatus'])->name('institutions.sites.toggle-status');

        // Institution Relationships Management
        Route::resource('institutions.relationships', InstitutionRelationshipController::class)->except(['index']);
        Route::get('institutions/{institution}/relationships', [InstitutionRelationshipController::class, 'index'])->name('institutions.relationships.index');
        Route::patch('institutions/{institution}/relationships/{relationship}/toggle-status', [InstitutionRelationshipController::class, 'toggleStatus'])->name('institutions.relationships.toggle-status');
    });
});
