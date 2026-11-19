<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Nastavenia účtu sa zobrazujú v ráme panela. Člen podniku preto dostane tie isté zdieľané props
 * ako v paneli; správca bez podniku ich nemá a stránka použije jednoduchý rám administrácie.
 */
class SharePanel
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $member = EnsureBusinessMember::member($request);

        // Kým je overenie povinné a nezapnuté, panel by používateľa aj tak vracal späť – menu mu neukazujeme.
        $locked = config('panel.two_factor_required') && ! $user->hasTwoFactor()
            && ($user->is_admin || $member?->role->requiresTwoFactor());

        if ($member && ! $locked) {
            EnsureBusinessMember::share($request, $member);
        }

        Inertia::share('account', fn () => [
            'admin' => $user->is_admin,
            'home' => $member ? route('panel.overview') : ($user->is_admin ? route('admin.businesses') : route('panel.registration')),
            'homeLabel' => $member ? 'Späť do panela' : ($user->is_admin ? 'Do administrácie' : 'Registrácia podniku'),
        ]);

        return $next($request);
    }
}
