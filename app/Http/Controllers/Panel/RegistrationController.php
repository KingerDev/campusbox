<?php

namespace App\Http\Controllers\Panel;

use App\Enums\BusinessRole;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\MenuSection;
use App\Models\User;
use App\Services\BusinessReview;
use App\Support\Phone;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        // Prihlásený člen tímu už podnik má.
        if ($request->user()?->memberships()->exists() && ! $request->boolean('hotovo')) {
            return redirect()->route('panel.overview');
        }

        return Inertia::render('Panel/Registration', [
            'types' => Business::TYPES,
            'loggedIn' => (bool) $request->user(),
            'done' => $request->boolean('hotovo') && $request->user()?->memberships()->exists(),
        ]);
    }

    /** Overí jeden krok sprievodcu (0–3), aby sa chyby ukázali hneď pri ňom. */
    public function validateStep(Request $request): RedirectResponse
    {
        $step = (int) $request->input('step');
        abort_unless($step >= 0 && $step <= 3, 422);
        $this->normalizeAccount($request);
        $request->validate($this->rules($request, $step), $this->messages());

        return back();
    }

    public function store(Request $request, BusinessReview $review): RedirectResponse
    {
        $this->normalizeAccount($request);
        $rules = array_merge(...array_map(fn ($s) => $this->rules($request, $s), [0, 1, 2, 3]));
        $data = $request->validate($rules, $this->messages());

        $business = DB::transaction(function () use ($request, $data) {
            $user = $request->user() ?? User::create([
                'name' => $data['meno'],
                'email' => $data['email'],
                'phone' => $data['tel'],
                'password' => $data['heslo'],
            ]);

            $business = Business::create([
                'name' => $data['nazov'],
                'ico' => $data['ico'],
                'type' => $data['typ'],
                'address' => $data['adresa'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'pickup_instructions' => $data['pokyny'] ?? null,
                'opening_hours' => Business::normalizeHours($data['opening_hours']),
                'delivery_enabled' => (bool) ($data['donaska'] ?? false),
                'delivery_fee' => $this->money($data['poplatok'] ?? '0'),
                'iban' => strtoupper(preg_replace('/\s+/', ' ', trim($data['iban']))),
                'billing_email' => $data['fakt'],
                'phone' => $data['tel'] ?? null,
                'terms_accepted_at' => now(),
                'status' => 'pending',
            ]);

            $business->members()->create([
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => BusinessRole::Owner,
                'joined_at' => now(),
            ]);

            foreach (['Denné menu', 'Polievky', 'Sladké'] as $i => $name) {
                MenuSection::create(['business_id' => $business->id, 'name' => $name, 'position' => $i]);
            }

            if (! $request->user()) {
                event(new Registered($user));
                Auth::login($user);
            }

            $request->session()->put('business_id', $business->id);

            return $business;
        });

        // Správcovia sa o žiadosti dozvedia hneď – majiteľovi sľubujeme overenie do 24 hodín.
        $review->registered($business);

        return redirect()->route('panel.registration', ['hotovo' => 1]);
    }

    /** E‑mail malými písmenami a telefón v jednotnom tvare, nech na zápise nezáleží. */
    private function normalizeAccount(Request $request): void
    {
        $request->merge(array_filter([
            'email' => $request->filled('email') ? Str::lower(trim((string) $request->input('email'))) : null,
            'tel' => $request->filled('tel') ? Phone::normalize((string) $request->input('tel')) : null,
        ]));
    }

    private function rules(Request $request, int $step): array
    {
        $guest = ! $request->user();

        return match ($step) {
            0 => $guest ? [
                'meno' => ['required', 'string', 'max:80'],
                'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email')],
                'tel' => ['required', 'string', 'max:40', Phone::RULE],
                'heslo' => ['required', 'string', Password::min(8)],
            ] : [],
            1 => [
                'nazov' => ['required', 'string', 'max:120'],
                'ico' => ['required', 'string', 'regex:/^[0-9 ]{6,12}$/'],
                'typ' => ['required', 'string', Rule::in(Business::TYPES)],
            ],
            2 => Business::locationRules() + Business::hoursRules() + [
                'adresa' => ['required', 'string', 'max:200'],
                'pokyny' => ['nullable', 'string', 'max:300'],
                'donaska' => ['boolean'],
                'poplatok' => ['nullable', 'regex:/^\d{1,2}([.,]\d{1,2})?$/'],
            ],
            3 => [
                'iban' => ['required', 'string', 'regex:/^[A-Z]{2}\d{2}[A-Z0-9 ]{10,30}$/i'],
                'fakt' => ['required', 'email', 'max:120'],
                'suhlas' => ['accepted'],
            ],
        };
    }

    private function messages(): array
    {
        return Business::locationMessages() + [
            'meno.required' => 'Vyplňte pole „Meno a priezvisko“.',
            'email.required' => 'Vyplňte pole „Pracovný e‑mail“.',
            'email.email' => 'Skontrolujte e‑mail, napr. meno@podnik.sk.',
            'email.unique' => 'Tento e‑mail už má účet. Prihláste sa.',
            'tel.required' => 'Vyplňte pole „Telefón“.',
            'tel.regex' => 'Telefón zadajte s predvoľbou, napr. +421 903 123 456.',
            'heslo.required' => 'Vyplňte pole „Heslo“.',
            'heslo.min' => 'Heslo musí mať aspoň 8 znakov.',
            'nazov.required' => 'Vyplňte pole „Názov podniku“.',
            'ico.required' => 'Vyplňte pole „IČO“.',
            'ico.regex' => 'IČO má 6 až 8 číslic.',
            'typ.required' => 'Vyberte typ podniku.',
            'typ.in' => 'Vyberte typ podniku zo zoznamu.',
            'adresa.required' => 'Vyplňte pole „Adresa podniku“.',
            'poplatok.regex' => 'Cenu doručenia zadajte ako číslo, napr. 1,49.',
            'iban.required' => 'Vyplňte pole „IBAN pre výplaty“.',
            'iban.regex' => 'Skontrolujte IBAN, napr. SK31 1200 0000 1987 4263 7541.',
            'fakt.required' => 'Vyplňte pole „E‑mail pre faktúry“.',
            'fakt.email' => 'Skontrolujte e‑mail pre faktúry.',
            'suhlas.accepted' => 'Pre pokračovanie potrebujeme váš súhlas s podmienkami.',
        ];
    }

    private function money(string $value): float
    {
        return round((float) str_replace(',', '.', $value), 2);
    }
}
