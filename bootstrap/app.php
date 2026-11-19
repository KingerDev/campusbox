<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureBusinessMember;
use App\Http\Middleware\EnsurePanelAbility;
use App\Http\Middleware\EnsureRealAccount;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RequireTwoFactor;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'panel' => EnsureBusinessMember::class,
            'panel.can' => EnsurePanelAbility::class,
            'panel.2fa' => RequireTwoFactor::class,
            'panel.share' => \App\Http\Middleware\SharePanel::class,
            'real' => EnsureRealAccount::class,
            'admin' => EnsureAdmin::class,
        ]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Priveľa pokusov: v Inertia formulároch ukážeme zrozumiteľnú chybu namiesto holej stránky 429.
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if (! $request->header('X-Inertia')) {
                return null;
            }
            $seconds = (int) ($e->getHeaders()['Retry-After'] ?? 60);

            return back()->withErrors(['throttle' => "Priveľa pokusov za krátky čas. Skúste to znova o {$seconds} s."]);
        });
    })->create();
