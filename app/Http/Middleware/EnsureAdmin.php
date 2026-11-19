<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Administrácia je len pre správcov platformy; ostatným sa tvári, že neexistuje.
 * Dvojfaktorové overenie vyžaduje len s PANEL_2FA_REQUIRED=true, inak ho stránka odporúča pásom.
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user?->is_admin, 404);

        if (config('panel.two_factor_required') && ! $user->hasTwoFactor()) {
            return redirect()->route('two-factor.edit');
        }

        return $next($request);
    }
}
