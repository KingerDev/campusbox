<?php

namespace App\Http\Controllers\Panel;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OfferBoard;
use App\Support\CurrentBusiness;
use App\Support\OrderPresenter;
use Inertia\Inertia;
use Inertia\Response;

class OverviewController extends Controller
{
    public function __invoke(CurrentBusiness $current, OfferBoard $board): Response
    {
        $business = $current->business();
        $money = $current->can('money');

        $today = $business->orders()->today()->with('items')->latest('ordered_at')->get();
        $live = $today->filter(fn (Order $o) => $o->status->isLive());
        $reserved = $live->sum(fn (Order $o) => $o->quantity());
        $issued = $live->where('status', OrderStatus::Issued)->sum(fn (Order $o) => $o->quantity());
        $active = $board->activeItems($business);
        $offered = (int) $active->sum('quantity');

        // Spoľahlivosť za 30 dní: nahlásené problémy a automaticky zrušené (nevydané) objednávky.
        $last30 = $business->orders()->where('pickup_date', '>=', today()->subDays(30))->get(['status', 'reason']);
        $reported = $last30->where('status', OrderStatus::Reported)->count();
        $expired = $last30->filter(fn ($o) => $o->status === OrderStatus::Cancelled && str_contains((string) $o->reason, 'automaticky'))->count();
        $problemRate = $last30->count() ? ($reported + $expired) / $last30->count() * 100 : 0;

        return Inertia::render('Panel/Overview', [
            'dateLabel' => ucfirst(now()->locale('sk')->isoFormat('dddd D. M.')),
            'kpi' => [
                'offered' => $offered,
                'reserved' => $reserved,
                'issued' => $issued,
                'waiting' => $reserved - $issued,
                'free' => max(0, $offered - $reserved),
                'sales' => $money ? (float) $live->sum('total') : null,
            ],
            'reliability' => $money ? [
                'rate' => round($problemRate, 1),
                'reported' => $reported,
                'expired' => $expired,
            ] : null,
            'active' => $today->filter(fn (Order $o) => $o->status->isActive())
                ->map(fn (Order $o) => OrderPresenter::toArray($o, $money))->values(),
            'offer' => $active->map(fn ($i) => [
                'name' => ($i->type->value === 'menu' ? 'Menu · ' : '').$i->name,
                'count' => $i->quantity,
                'price' => $money ? (float) $i->price : null,
            ])->values(),
            'onboarding' => $this->onboarding($current),
        ]);
    }

    private function onboarding(CurrentBusiness $current): ?array
    {
        $b = $current->business();
        if (! $current->can('offer') || $b->onboarding_dismissed_at) {
            return null;
        }

        $steps = [
            'dish' => $b->dishes()->exists(),
            'offer' => $b->offerItems()->exists(),
            'issue' => (bool) $b->issue_tried_at,
        ];

        return array_sum($steps) === 3 ? null : $steps;
    }
}
