<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dočasný účet ukážkového podniku nemá nastavenia účtu (heslo, e‑mail, 2FA, zmazanie).
 */
class EnsureRealAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_demo) {
            return redirect()->route('panel.overview')->with('toast', 'V deme sa nastavenia účtu nemenia.');
        }

        return $next($request);
    }
}
