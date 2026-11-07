<?php

namespace App\Http\Controllers\Panel;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderWorkflow;
use App\Support\CurrentBusiness;
use App\Support\OrderPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(private OrderWorkflow $workflow) {}

    public function index(CurrentBusiness $current): Response
    {
        $money = $current->can('money');
        $orders = $current->business()->orders()->today()->with('items')->latest('ordered_at')->get()
            ->map(fn (Order $o) => OrderPresenter::toArray($o, $money));

        return Inertia::render('Panel/Orders', ['orders' => $orders]);
    }

    /** Ľahký endpoint pre polling: nové objednávky a počty do odznakov. */
    public function poll(CurrentBusiness $current): JsonResponse
    {
        $money = $current->can('money');
        $today = $current->business()->orders()->today()->with('items');

        return response()->json([
            'new' => (clone $today)->where('status', OrderStatus::New)->latest('ordered_at')->get()
                ->map(fn (Order $o) => OrderPresenter::toArray($o, $money)),
            'ready' => (clone $today)->where('status', OrderStatus::Ready)->count(),
            'active' => (clone $today)->whereIn('status', [OrderStatus::Preparing, OrderStatus::Ready, OrderStatus::OnTheWay])->count(),
        ]);
    }

    public function confirm(Request $request, CurrentBusiness $current, Order $order): RedirectResponse
    {
        $this->own($current, $order);
        $data = $request->validate(['eta' => ['nullable', 'date_format:H:i']]);
        $this->workflow->confirm($order, $data['eta'] ?? null);

        return back()->with('toast', ! empty($data['eta'])
            ? 'Potvrdené · študent vidí čas '.$data['eta']
            : 'Potvrdené · čas upresníte tlačidlom Pripravené');
    }

    public function reject(Request $request, CurrentBusiness $current, Order $order): RedirectResponse
    {
        $this->own($current, $order);
        $data = $request->validate(['reason' => ['required', Rule::in(['Vypredané', 'Zatvárame skôr', 'Iný dôvod'])]]);
        $this->workflow->reject($order, $data['reason']);

        return back()->with('toast', 'Odmietnuté · peniaze sa študentovi vrátia');
    }

    public function ready(CurrentBusiness $current, Order $order): RedirectResponse
    {
        $this->own($current, $order);
        $this->workflow->ready($order);

        return back()->with('toast', $order->isDelivery()
            ? 'Kuriér dostal upozornenie, že môže prísť'
            : 'Študent dostal upozornenie: pripravené na odovzdanie');
    }

    public function handover(CurrentBusiness $current, Order $order): RedirectResponse
    {
        $this->own($current, $order);
        $this->workflow->handover($order);

        return back()->with('toast', 'Odovzdané kuriérovi · študent ho sleduje v appke');
    }

    public function issue(CurrentBusiness $current, Order $order): RedirectResponse
    {
        $this->own($current, $order);
        $delivered = $order->status === OrderStatus::OnTheWay;
        $this->workflow->issue($order);

        return back()->with('toast', ($delivered ? 'Doručené · ' : 'Vydané · ').$order->code);
    }

    private function own(CurrentBusiness $current, Order $order): void
    {
        abort_unless($order->business_id === $current->business()->id, 404);
    }
}
