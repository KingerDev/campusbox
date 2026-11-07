<?php

namespace App\Http\Middleware;

use App\Support\CurrentBusiness;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePanelAbility
{
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        abort_unless(app(CurrentBusiness::class)->can($ability), 403, 'Na túto časť panela nemáte oprávnenie.');

        return $next($request);
    }
}
