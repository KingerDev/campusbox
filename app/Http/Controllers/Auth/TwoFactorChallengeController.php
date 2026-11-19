<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\TwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Druhý krok prihlásenia: kód z autentifikačnej appky alebo záložný kód.
 * Heslo už bolo overené, používateľ čaká v session (kľúč two_factor) najviac 10 minút.
 */
class TwoFactorChallengeController extends Controller
{
    private const TTL_SECONDS = 600;

    public function create(Request $request): Response|RedirectResponse
    {
        if (! $this->pending($request)) {
            return redirect()->route('login');
        }

        return Inertia::render('auth/TwoFactorChallenge');
    }

    public function store(Request $request, TwoFactor $twoFactor): RedirectResponse
    {
        $user = $this->pending($request);
        if (! $user) {
            return redirect()->route('login')->with('status', 'Prihlásenie vypršalo. Zadajte e‑mail a heslo znova.');
        }

        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:20'],
            'recovery_code' => ['nullable', 'string', 'max:40'],
        ]);
        $recovery = filled($data['recovery_code'] ?? null);
        $field = $recovery ? 'recovery_code' : 'code';

        $key = 'two-factor|'.$user->id.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                $field => 'Priveľa pokusov. Skúste to znova o '.RateLimiter::availableIn($key).' s.',
            ]);
        }

        $ok = $recovery
            ? $twoFactor->useRecoveryCode($user, $data['recovery_code'])
            : $twoFactor->verify($user, (string) ($data['code'] ?? ''));

        if (! $ok) {
            RateLimiter::hit($key);

            throw ValidationException::withMessages([
                $field => $recovery ? 'Záložný kód nesedí alebo už bol použitý.' : 'Kód nesedí. Skontrolujte appku a skúste aktuálny kód.',
            ]);
        }

        RateLimiter::clear($key);
        $remember = (bool) $request->session()->get('two_factor.remember');
        $request->session()->forget('two_factor');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function pending(Request $request): ?User
    {
        $state = $request->session()->get('two_factor');
        if (! is_array($state) || now()->timestamp - (int) ($state['at'] ?? 0) > self::TTL_SECONDS) {
            $request->session()->forget('two_factor');

            return null;
        }

        $user = User::find($state['id'] ?? null);

        return $user?->hasTwoFactor() ? $user : null;
    }
}
