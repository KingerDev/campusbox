<?php

use App\Http\Controllers\Admin\BusinessController;
use Illuminate\Support\Facades\Route;

// Administrácia platformy: len správcovia (users.is_admin) a vždy s dvojfaktorovým overením.
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [BusinessController::class, 'index'])->name('businesses');
    Route::post('podniky/{business}/schvalit', [BusinessController::class, 'approve'])->name('businesses.approve');
    Route::post('podniky/{business}/zamietnut', [BusinessController::class, 'reject'])->name('businesses.reject');
});
