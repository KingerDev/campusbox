<?php

namespace App\Http\Middleware;

use App\Support\CurrentBusiness;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * S PANEL_2FA_REQUIRED=true pustí panel majiteľa a manažéra ďalej až so zapnutým dvojfaktorovým overením
 * (ukážkový podnik je výnimka). Predvolene je overenie dobrovoľné a panel ho len odporúča pásom hore.
 */
class RequireTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $current = app(CurrentBusiness::class);

        if (
            config('panel.two_factor_required')
            && ! $current->business()->is_demo
            && $current->role()->requiresTwoFactor()
            && ! $request->user()->hasTwoFactor()
        ) {
            return redirect()->route('two-factor.edit');
        }

        return $next($request);
    }
}
