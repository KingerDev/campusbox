<?php

namespace App\Http\Controllers\Panel;

use App\Enums\BusinessRole;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\User;
use App\Support\DemoBusinessBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Ukážkový podnik: návštevník dostane jedným klikom vlastnú kópiu Bistra Pod Urpínom a je v nej prihlásený ako majiteľ.
 * Kópia je súkromná, nič z nej neodchádza (e‑maily, obrázky) a po vypršaní ju zmaže campusbox:prune-demos.
 */
class DemoController extends Controller
{
    public function store(Request $request, DemoBusinessBuilder $builder): RedirectResponse
    {
        // Prihlásený používateľ (skutočný alebo už v deme) ide rovno do svojho panela.
        if ($request->user()) {
            return redirect()->route('panel.overview');
        }

        if (Business::where('is_demo', true)->where('demo_expires_at', '>', now())->count() >= config('panel.demo_limit')) {
            return back()->withErrors(['demo' => 'Demo je práve vyťažené. Skúste to prosím o chvíľu.']);
        }

        [$owner, $business] = DB::transaction(function () use ($builder) {
            $id = Str::lower(Str::random(12));
            // Účty sú len na toto sedenie: heslo nikto nepozná a doména .invalid nikdy nedoručí e‑mail.
            $password = Hash::make(Str::random(40));
            $user = fn (string $name, string $slug) => tap((new User)->forceFill([
                'name' => $name,
                'email' => "{$slug}.{$id}@demo.campusbox.invalid",
                'password' => $password,
                'email_verified_at' => now(),
                'is_demo' => true,
            ]))->save();

            $owner = $user('Peter Urban', 'peter');
            $business = $builder->build(
                [[$owner, BusinessRole::Owner], [$user('Jana Kmeťová', 'jana'), BusinessRole::Manager], [$user('Samo Lukáč', 'samo'), BusinessRole::Worker]],
                "ivana.{$id}@demo.campusbox.invalid",
                ['is_demo' => true, 'demo_expires_at' => now()->addHours(config('panel.demo_hours'))],
            );

            return [$owner, $business];
        });

        Auth::login($owner);
        $request->session()->regenerate();
        $request->session()->put('business_id', $business->id);

        return redirect()->route('panel.overview')->with('toast', 'Vitajte v ukážkovom podniku');
    }

    /** Z dema na registráciu vlastného podniku (dočasný účet sa odhlási). */
    public function leave(Request $request): RedirectResponse
    {
        if ($request->user()->is_demo) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('panel.registration');
    }
}
