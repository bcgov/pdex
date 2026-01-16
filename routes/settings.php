<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

const SETTINGS_PROFILE_PATH = 'settings/profile';

Route::middleware('auth')->group(function () {
    Route::redirect('settings', '/' . SETTINGS_PROFILE_PATH);

    Route::get(SETTINGS_PROFILE_PATH, [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch(SETTINGS_PROFILE_PATH, [ProfileController::class, 'update'])->name('profile.update');
    Route::delete(SETTINGS_PROFILE_PATH, [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance');
});
