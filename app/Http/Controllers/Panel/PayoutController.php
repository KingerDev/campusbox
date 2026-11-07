<?php

namespace App\Http\Controllers\Panel;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\OrderItem;
use App\Support\CurrentBusiness;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class PayoutController extends Controller
{
    public function index(Request $request, CurrentBusiness $current): Response
    {
        $business = $current->business();
        $month = $this->month($request);
        $stats = $this->stats($business, $month, $month->endOfMonth());

        $perDay = $this->issuedItems($business, $month, $month->endOfMonth())
            ->groupBy(fn (OrderItem $i) => $i->order->pickup_date->day)
            ->map->sum('quantity');
        $days = $month->daysInMonth;
        $max = max(1, (int) $perDay->max());
        $bars = collect(range(1, $days))->map(fn (int $d) => [
            'day' => $d,
            'qty' => (int) ($perDay[$d] ?? 0),
            'h' => round(($perDay[$d] ?? 0) / $max * 100),
            'weekend' => $month->setDay($d)->isWeekend(),
        ]);
        $activeDays = $perDay->filter()->count();

        return Inertia::render('Panel/Payouts', [
            'month' => $month->format('Y-m'),
            'monthLabel' => ucfirst($month->locale('sk')->isoFormat('MMMM YYYY')),
            'prev' => $month->subMonth()->format('Y-m'),
            'next' => $month->addMonth()->lte(now()->startOfMonth()) ? $month->addMonth()->format('Y-m') : null,
            'stats' => $stats,
            'bars' => $bars,
            'average' => $activeDays ? round($perDay->sum() / $activeDays, 1) : 0,
            'periods' => $this->periods($business),
        ]);
    }

    /** Výpis za mesiac na tlač / uloženie do PDF. */
    public function statement(Request $request, CurrentBusiness $current): HttpResponse
    {
        $business = $current->business();
        $month = $this->month($request);
        $items = $this->issuedItems($business, $month, $month->endOfMonth());

        return response()->view('panel.statement', [
            'business' => $business,
            'monthLabel' => ucfirst($month->locale('sk')->isoFormat('MMMM YYYY')),
            'stats' => $this->stats($business, $month, $month->endOfMonth()),
            'orders' => $items->groupBy('order_id')->map(fn ($group) => $group->first()->order)->sortBy('issued_at'),
        ]);
    }

    private function month(Request $request): CarbonImmutable
    {
        $value = (string) $request->query('mesiac');
        $month = preg_match('/^\d{4}-\d{2}$/', $value) ? CarbonImmutable::createFromFormat('Y-m-d', $value.'-01') : CarbonImmutable::now();

        return $month->startOfMonth()->min(CarbonImmutable::now()->startOfMonth());
    }

    private function issuedItems(Business $business, CarbonImmutable $from, CarbonImmutable $to)
    {
        return OrderItem::with('order')
            ->whereHas('order', fn ($q) => $q->where('business_id', $business->id)
                ->where('status', OrderStatus::Issued)
                ->whereBetween('pickup_date', [$from->toDateString(), $to->toDateString()]))
            ->get();
    }

    private function stats(Business $business, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $items = $this->issuedItems($business, $from, $to);
        $sales = $items->sum(fn (OrderItem $i) => $i->quantity * (float) $i->unit_price);
        $commission = $items->sum(fn (OrderItem $i) => $i->quantity * (float) $i->unit_price * (float) $i->commission_rate);

        return [
            'sales' => round($sales, 2),
            'commission' => round($commission, 2),
            'net' => round($sales - $commission, 2),
            'rescued' => (int) $items->where('type', 'zachrana')->sum('quantity'),
            'portions' => (int) $items->sum('quantity'),
        ];
    }

    /** Polmesačné obdobia výplat (1.–15. a 16.–koniec), aktuálne a dve predošlé. */
    private function periods(Business $business): array
    {
        $now = CarbonImmutable::now();
        $start = $now->day <= 15 ? $now->startOfMonth() : $now->startOfMonth()->setDay(16);
        $periods = [];

        for ($i = 0; $i < 3; $i++) {
            $end = $start->day === 1 ? $start->setDay(15) : $start->endOfMonth();
            $payout = $business->payouts()->whereDate('period_start', $start)->first();
            $net = $this->stats($business, $start, $end)['net'];
            $label = $start->format('j.').'–'.$end->format('j. n.');

            $periods[] = match (true) {
                $i === 0 => ['label' => $label, 'amount' => $net, 'status' => 'Spracúva sa', 'tone' => 'orange'],
                (bool) $payout?->paid_at => ['label' => $label, 'amount' => (float) $payout->amount, 'status' => 'Vyplatené '.$payout->paid_at->format('j. n.'), 'tone' => 'green'],
                default => ['label' => $label, 'amount' => $net, 'status' => 'Čaká na výplatu', 'tone' => 'orange'],
            };

            $start = $start->day === 16 ? $start->setDay(1) : $start->subMonth()->setDay(16);
        }

        return $periods;
    }
}
