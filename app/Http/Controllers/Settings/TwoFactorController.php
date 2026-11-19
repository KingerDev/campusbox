<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\BusinessMember;
use App\Models\User;
use App\Support\TwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Nastavenie dvojfaktorového overenia: zapnutie cez QR kód, potvrdenie prvým kódom, záložné kódy, vypnutie.
 */
class TwoFactorController extends Controller
{
    public function edit(Request $request, TwoFactor $twoFactor): Response
    {
        $user = $request->user();
        $pending = $user->two_factor_secret !== null && ! $user->hasTwoFactor();

        return Inertia::render('settings/TwoFactor', [
            'enabled' => $user->hasTwoFactor(),
            'required' => $this->required($user),
            'setup' => $pending ? ['svg' => $twoFactor->qrSvg($user), 'secret' => $user->two_factor_secret] : null,
            // Záložné kódy sa ukážu len raz, hneď po vygenerovaní.
            'recoveryCodes' => $request->session()->get('recovery_codes'),
            'remaining' => $user->hasTwoFactor() ? count($user->two_factor_recovery_codes ?? []) : null,
            // Kam viesť „späť“: správca bez podniku nemá panel, len administráciu.
            'home' => route($user->is_admin && ! $user->memberships()->exists() ? 'admin.businesses' : 'panel.overview'),
            'admin' => $user->is_admin,
        ]);
    }

    public function store(Request $request, TwoFactor $twoFactor): RedirectResponse
    {
        if (! $request->user()->hasTwoFactor()) {
            $twoFactor->begin($request->user());
        }

        return to_route('two-factor.edit');
    }

    public function confirm(Request $request, TwoFactor $twoFactor): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:20']], ['code.required' => 'Zadajte 6‑miestny kód z appky.']);

        $user = $request->user();
        if ($user->hasTwoFactor()) {
            return to_route('two-factor.edit');
        }

        $codes = $twoFactor->confirm($user, $data['code']);
        if ($codes === null) {
            return back()->withErrors(['code' => 'Kód nesedí. Skontrolujte appku a skúste aktuálny kód.']);
        }

        return to_route('two-factor.edit')->with('recovery_codes', $codes);
    }

    public function recoveryCodes(Request $request, TwoFactor $twoFactor): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']], $this->passwordMessages());
        abort_unless($request->user()->hasTwoFactor(), 409);

        return to_route('two-factor.edit')->with('recovery_codes', $twoFactor->regenerateRecoveryCodes($request->user()));
    }

    public function destroy(Request $request, TwoFactor $twoFactor): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']], $this->passwordMessages());
        $twoFactor->disable($request->user());

        return to_route('two-factor.edit');
    }

    /** Je overenie povinné (PANEL_2FA_REQUIRED) a používateľ je správca, majiteľ či manažér skutočného podniku? */
    private function required(User $user): bool
    {
        return config('panel.two_factor_required') && ($user->is_admin || $user->memberships()->with('business')->get()
            ->contains(fn (BusinessMember $m) => ! $m->business->is_demo && $m->role->requiresTwoFactor()));
    }

    private function passwordMessages(): array
    {
        return ['password.required' => 'Zadajte svoje heslo.', 'password.current_password' => 'Heslo nesedí.'];
    }
}
