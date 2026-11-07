<?php

namespace App\Http\Middleware;

use App\Enums\OrderStatus;
use App\Models\BusinessMember;
use App\Support\CurrentBusiness;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pustí do panela len člena tímu podniku a sprístupní CurrentBusiness + zdieľané props.
 */
class EnsureBusinessMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $member = BusinessMember::with('business')
            ->where('user_id', $user->id)
            ->whereNotNull('joined_at')
            ->when($request->session()->get('business_id'), fn ($q, $id) => $q->orderByRaw('business_id = ? desc', [$id]))
            ->first();

        if (! $member) {
            return redirect()->route('panel.registration');
        }

        $request->session()->put('business_id', $member->business_id);
        $current = new CurrentBusiness($member);
        app()->instance(CurrentBusiness::class, $current);

        $business = $member->business;
        $role = $member->role;

        Inertia::share('panel', fn () => [
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'approved' => $business->isApproved(),
                'logo' => $business->logoUrl(),
                'rescueSlot' => $business->rescue_slot,
            ],
            'user' => ['name' => $user->name],
            'role' => ['key' => $role->value, 'label' => $role->label()],
            'abilities' => $role->abilities(),
            'counts' => [
                'new' => $business->orders()->today()->where('status', OrderStatus::New)->count(),
                'ready' => $business->orders()->today()->where('status', OrderStatus::Ready)->count(),
                'active' => $business->orders()->today()->whereIn('status', [OrderStatus::Preparing, OrderStatus::Ready, OrderStatus::OnTheWay])->count(),
            ],
            'flash' => $request->session()->get('toast'),
        ]);

        return $next($request);
    }
}
