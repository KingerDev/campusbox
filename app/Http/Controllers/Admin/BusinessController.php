<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BusinessRole;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Services\BusinessReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Administrácia: overovanie zaregistrovaných podnikov (ukážkové podniky sem nepatria).
 */
class BusinessController extends Controller
{
    private const STATUSES = ['pending', 'approved', 'rejected'];

    public function index(Request $request): Response
    {
        $status = in_array($request->query('stav'), self::STATUSES, true) ? $request->query('stav') : 'pending';
        $real = fn () => Business::where('is_demo', false);

        $businesses = $real()->where('status', $status)
            ->with(['members' => fn ($q) => $q->where('role', BusinessRole::Owner)->with('user')])
            ->withCount('dishes')
            ->orderBy('created_at', $status === 'pending' ? 'asc' : 'desc')
            ->limit(200)
            ->get();

        return Inertia::render('Admin/Businesses', [
            'status' => $status,
            'counts' => collect(self::STATUSES)->mapWithKeys(fn ($s) => [$s => $real()->where('status', $s)->count()]),
            'businesses' => $businesses->map(function (Business $b) {
                $owner = $b->members->first()?->user;

                return [
                    'id' => $b->id,
                    'name' => $b->name,
                    'type' => $b->type,
                    'ico' => $b->ico,
                    'dic' => $b->dic,
                    'address' => $b->address,
                    'map' => $b->latitude ? "https://www.openstreetmap.org/?mlat={$b->latitude}&mlon={$b->longitude}#map=17/{$b->latitude}/{$b->longitude}" : null,
                    'iban' => $b->iban,
                    'billingEmail' => $b->billing_email,
                    'delivery' => $b->delivery_enabled,
                    'dishes' => $b->dishes_count,
                    'owner' => $owner ? ['name' => $owner->name, 'email' => $owner->email, 'phone' => $owner->phone] : null,
                    'registeredAt' => $b->created_at->format('j. n. Y H:i'),
                    // Sľub „do 24 hodín“: koľko hodín už žiadosť čaká.
                    'waitingHours' => $b->status === 'pending' ? (int) $b->created_at->diffInHours(now()) : null,
                    'reviewedAt' => ($b->approved_at ?? $b->rejected_at)?->format('j. n. Y H:i'),
                    'reason' => $b->rejection_reason,
                ];
            }),
            'flash' => $request->session()->get('toast'),
            'suggestTwoFactor' => ! $request->user()->hasTwoFactor(),
        ]);
    }

    public function approve(Business $business, BusinessReview $review): RedirectResponse
    {
        abort_if($business->is_demo, 404);
        $review->approve($business);

        return back()->with('toast', $business->name.' je schválený. Majiteľovi sme poslali e‑mail.');
    }

    public function reject(Request $request, Business $business, BusinessReview $review): RedirectResponse
    {
        abort_if($business->is_demo, 404);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:300']], [
            'reason.required' => 'Napíšte dôvod – majiteľ ho dostane e‑mailom.',
            'reason.min' => 'Dôvod napíšte zrozumiteľne, aspoň pár slov.',
        ]);
        $review->reject($business, $data['reason']);

        return back()->with('toast', $business->name.' je zamietnutý. Majiteľovi sme poslali dôvod.');
    }
}
