<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Support\CurrentBusiness;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(CurrentBusiness $current): Response
    {
        $b = $current->business();
        $billing = $current->can('billing');

        return Inertia::render('Panel/Profile', [
            'profile' => [
                'name' => $b->name,
                'description' => $b->description ?? '',
                'types' => $b->types,
                'categories' => $b->categories,
                'phone' => $b->phone ?? '',
                'public_email' => $b->public_email ?? '',
                'web' => $b->web ?? '',
                'instagram' => $b->instagram ?? '',
                'facebook' => $b->facebook ?? '',
                'linkedin' => $b->linkedin ?? '',
                'address' => $b->address,
                'pickup_instructions' => $b->pickup_instructions ?? '',
                'opening_hours' => $b->opening_hours ?: Business::defaultHours(),
            ] + ($billing ? [
                'ico' => $b->ico,
                'dic' => $b->dic ?? '',
                'iban' => $b->iban,
                'billing_email' => $b->billing_email,
            ] : []),
            'cover' => $b->coverUrl(),
            'logo' => $b->logoUrl(),
            'canBill' => $billing,
            'delivery' => ['on' => $b->delivery_enabled, 'fee' => (float) $b->delivery_fee],
            'types' => Business::TYPES,
            'categories' => Business::CATEGORIES,
        ]);
    }

    public function update(Request $request, CurrentBusiness $current): RedirectResponse
    {
        $billing = $current->can('billing');
        $time = ['nullable', 'regex:/^([01]?\d|2[0-3]):[0-5]\d$/'];

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:200'],
            'types' => ['required', 'array', 'min:1'],
            'types.*' => [Rule::in(Business::TYPES)],
            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => [Rule::in(Business::CATEGORIES)],
            'phone' => ['nullable', 'string', 'max:40'],
            'public_email' => ['nullable', 'email', 'max:120'],
            'web' => ['nullable', 'string', 'max:120'],
            'instagram' => ['nullable', 'string', 'max:120'],
            'facebook' => ['nullable', 'string', 'max:160'],
            'linkedin' => ['nullable', 'string', 'max:160'],
            'address' => ['required', 'string', 'max:200'],
            'pickup_instructions' => ['nullable', 'string', 'max:300'],
            'opening_hours' => ['required', 'array', 'size:7'],
            'opening_hours.*.1' => $time,
            'opening_hours.*.2' => $time,
            'opening_hours.*.3' => ['boolean'],
        ] + ($billing ? [
            'ico' => ['required', 'string', 'regex:/^[0-9 ]{6,12}$/'],
            'dic' => ['nullable', 'string', 'max:20'],
            'iban' => ['required', 'string', 'regex:/^[A-Z]{2}\d{2}[A-Z0-9 ]{10,30}$/i'],
            'billing_email' => ['required', 'email', 'max:120'],
        ] : []), [
            'name.required' => 'Vyplňte názov podniku.',
            'address.required' => 'Vyplňte adresu.',
            'types.required' => 'Vyberte aspoň jeden typ podniku.',
            'categories.required' => 'Vyberte aspoň jednu kategóriu.',
            'opening_hours.*.1.regex' => 'Čas zadajte vo formáte 10:00.',
            'opening_hours.*.2.regex' => 'Čas zadajte vo formáte 21:00.',
            'iban.regex' => 'Skontrolujte IBAN.',
            'ico.regex' => 'IČO má 6 až 8 číslic.',
        ]);

        $data['opening_hours'] = array_map(fn ($h, $i) => [Business::DAYS[$i], $h[1] ?? '', $h[2] ?? '', (bool) ($h[3] ?? false)], $data['opening_hours'], array_keys($data['opening_hours']));
        $current->business()->update($data);

        return back()->with('toast', 'Profil uložený');
    }

    public function upload(Request $request, CurrentBusiness $current): RedirectResponse
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in(['cover', 'logo'])],
            'image' => ['required', 'image', 'max:4096'],
        ], ['image.image' => 'Nahrajte obrázok (JPG alebo PNG).', 'image.max' => 'Obrázok môže mať najviac 4 MB.']);

        $business = $current->business();
        $column = $data['kind'].'_path';
        if ($business->$column) {
            Storage::disk('public')->delete($business->$column);
        }
        $business->update([$column => $request->file('image')->store('businesses/'.$business->id, 'public')]);

        return back()->with('toast', $data['kind'] === 'cover' ? 'Titulná fotka zmenená' : 'Logo zmenené');
    }

    public function dismissOnboarding(CurrentBusiness $current): RedirectResponse
    {
        $current->business()->update(['onboarding_dismissed_at' => now()]);

        return back();
    }
}
