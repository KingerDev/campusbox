<?php

use App\Http\Controllers\Panel\InvitationController;
use App\Http\Controllers\Panel\IssueController;
use App\Http\Controllers\Panel\MenuController;
use App\Http\Controllers\Panel\OfferController;
use App\Http\Controllers\Panel\OrderController;
use App\Http\Controllers\Panel\OverviewController;
use App\Http\Controllers\Panel\PayoutController;
use App\Http\Controllers\Panel\ProfileController;
use App\Http\Controllers\Panel\RegistrationController;
use App\Http\Controllers\Panel\TeamController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::prefix('panel')->name('panel.')->group(function () {
    // Registrácia podniku (pre hostí aj prihlásených bez podniku).
    Route::get('registracia', [RegistrationController::class, 'create'])->name('registration');
    Route::post('registracia/overit', [RegistrationController::class, 'validateStep'])->middleware('throttle:30,1')->name('registration.validate');
    Route::post('registracia', [RegistrationController::class, 'store'])->middleware('throttle:6,1')->name('registration.store');

    Route::get('pozvanka/{token}', [InvitationController::class, 'show'])->name('invitation');
    Route::post('pozvanka/{token}', [InvitationController::class, 'accept'])->middleware('throttle:10,1')->name('invitation.accept');

    Route::middleware(['auth', 'panel'])->group(function () {
        Route::get('/', OverviewController::class)->name('overview');
        Route::get('viac', fn () => Inertia::render('Panel/More'))->name('more');

        Route::get('vydaj', IssueController::class)->name('issue');
        Route::get('objednavky', [OrderController::class, 'index'])->name('orders');
        Route::get('objednavky/nove', [OrderController::class, 'poll'])->name('orders.poll');
        Route::post('objednavky/{order}/potvrdit', [OrderController::class, 'confirm'])->name('orders.confirm');
        Route::post('objednavky/{order}/odmietnut', [OrderController::class, 'reject'])->name('orders.reject');
        Route::post('objednavky/{order}/pripravene', [OrderController::class, 'ready'])->name('orders.ready');
        Route::post('objednavky/{order}/kurier', [OrderController::class, 'handover'])->name('orders.handover');
        Route::post('objednavky/{order}/vydat', [OrderController::class, 'issue'])->name('orders.issue');

        Route::middleware('panel.can:offer')->group(function () {
            Route::get('ponuka', [OfferController::class, 'edit'])->name('offer');
            Route::put('ponuka', [OfferController::class, 'update'])->name('offer.update');
            Route::post('uvod/skryt', [ProfileController::class, 'dismissOnboarding'])->name('onboarding.dismiss');
        });

        Route::middleware('panel.can:menu')->group(function () {
            Route::get('menu', [MenuController::class, 'index'])->name('menu');
            Route::put('menu/poradie', [MenuController::class, 'reorder'])->name('menu.reorder');
            Route::post('menu/sekcie', [MenuController::class, 'storeSection'])->name('sections.store');
            Route::put('menu/sekcie/{section}', [MenuController::class, 'updateSection'])->name('sections.update');
            Route::delete('menu/sekcie/{section}', [MenuController::class, 'destroySection'])->name('sections.destroy');
            Route::post('menu/jedla', [MenuController::class, 'storeDish'])->name('dishes.store');
            Route::put('menu/jedla/{dish}', [MenuController::class, 'updateDish'])->name('dishes.update');
            Route::delete('menu/jedla/{dish}', [MenuController::class, 'destroyDish'])->name('dishes.destroy');
        });

        Route::middleware('panel.can:payouts')->group(function () {
            Route::get('vyplaty', [PayoutController::class, 'index'])->name('payouts');
            Route::get('vyplaty/vypis', [PayoutController::class, 'statement'])->name('payouts.statement');
        });

        Route::middleware('panel.can:team')->group(function () {
            Route::get('tim', [TeamController::class, 'index'])->name('team');
            Route::post('tim', [TeamController::class, 'invite'])->middleware('throttle:20,1')->name('team.invite');
            Route::put('tim/{member}', [TeamController::class, 'update'])->name('team.update');
            Route::delete('tim/{member}', [TeamController::class, 'destroy'])->name('team.destroy');
        });

        Route::middleware('panel.can:profile')->group(function () {
            Route::get('profil', [ProfileController::class, 'edit'])->name('profile');
            Route::put('profil', [ProfileController::class, 'update'])->name('profile.update');
            Route::post('profil/obrazok', [ProfileController::class, 'upload'])->name('profile.upload');
        });
    });
});
