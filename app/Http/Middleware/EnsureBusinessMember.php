<?php

namespace App\Http\Middleware;

use App\Enums\OrderStatus;
use App\Models\BusinessMember;
use App\Support\CurrentBusiness;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pustí do panela len člena tímu podniku a sprístupní CurrentBusiness + zdieľané props.
 */
class EnsureBusinessMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $member = self::member($request);

        if (! $member) {
            return redirect()->route('panel.registration');
        }

        // Ukážkový podnik po vypršaní zmizne aj s účtom.
        if ($member->business->is_demo && $member->business->demo_expires_at?->isPast()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('panel.registration');
        }

        $request->session()->put('business_id', $member->business_id);
        $current = new CurrentBusiness($member);
        app()->instance(CurrentBusiness::class, $current);

        self::share($request, $member);

        return $next($request);
    }

    /** Členstvo prihláseného používateľa (prednostne v podniku, s ktorým naposledy pracoval). */
    public static function member(Request $request): ?BusinessMember
    {
        return BusinessMember::with('business')
            ->where('user_id', $request->user()->id)
            ->whereNotNull('joined_at')
            ->when($request->session()->get('business_id'), fn ($q, $id) => $q->orderByRaw('business_id = ? desc', [$id]))
            ->first();
    }

    /** Zdieľané props, z ktorých sa skladá rám panela (bočné menu, pásy, odznaky). */
    public static function share(Request $request, BusinessMember $member): void
    {
        $user = $request->user();
        $business = $member->business;
        $role = $member->role;

        Inertia::share('panel', fn () => [
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'approved' => $business->isApproved(),
                'rejected' => $business->status === 'rejected',
                'rejectionReason' => $business->status === 'rejected' ? $business->rejection_reason : null,
                'logo' => $business->logoUrl(),
                'rescueUntil' => $business->rescueUntil(),
                'menuSlot' => $business->menu_slot,
                'demo' => $business->is_demo,
            ],
            'user' => ['name' => $user->name, 'admin' => $user->is_admin],
            // Kto vidí tržby a výplaty, tomu panel odporučí zapnúť dvojfaktorové overenie.
            'suggestTwoFactor' => $role->requiresTwoFactor() && ! $business->is_demo && ! $user->hasTwoFactor(),
            'role' => ['key' => $role->value, 'label' => $role->label()],
            'abilities' => $role->abilities(),
            'counts' => [
                'new' => $business->orders()->today()->where('status', OrderStatus::New)->count(),
                'ready' => $business->orders()->today()->where('status', OrderStatus::Ready)->count(),
                'active' => $business->orders()->today()->whereIn('status', [OrderStatus::Preparing, OrderStatus::Ready, OrderStatus::OnTheWay])->count(),
            ],
            'flash' => $request->session()->get('toast'),
        ]);
    }
}
