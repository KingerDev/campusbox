<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\TwoFactorController;
use Illuminate\Support\Facades\Route;

// Dočasné účty ukážkového podniku nastavenia účtu nemajú.
Route::middleware(['auth', 'real', 'panel.share'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('settings/two-factor', [TwoFactorController::class, 'edit'])->name('two-factor.edit');
    Route::post('settings/two-factor', [TwoFactorController::class, 'store'])->name('two-factor.store');
    Route::post('settings/two-factor/confirm', [TwoFactorController::class, 'confirm'])->middleware('throttle:10,1,2fa-confirm')->name('two-factor.confirm');
    Route::post('settings/two-factor/recovery-codes', [TwoFactorController::class, 'recoveryCodes'])->middleware('throttle:10,1,2fa-codes')->name('two-factor.recovery-codes');
    Route::delete('settings/two-factor', [TwoFactorController::class, 'destroy'])->middleware('throttle:10,1,2fa-off')->name('two-factor.destroy');

    // Panel má jeden vzhľad, prepínač svetlého a tmavého režimu už nemá čo meniť.
    Route::redirect('settings/appearance', '/settings/profile')->name('appearance');
});
