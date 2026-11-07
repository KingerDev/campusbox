<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function (Request $request) {
    return Inertia::render('Landing', [
        'audience' => $request->query('pre') === 'podniky' ? 'podniky' : 'studenti',
    ]);
})->name('home');

// Starter kit presmerúva po prihlásení na „dashboard“ – to je panel podniku.
Route::redirect('dashboard', '/panel')->middleware('auth')->name('dashboard');

require __DIR__.'/panel.php';

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
