<?php

namespace Tests\Feature\Panel;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Facades\Artisan;
use Inertia\Testing\AssertableInertia as Assert;

class OrderFlowTest extends PanelTestCase
{
    private function order(string $code): Order
    {
        return $this->business->orders()->today()->where('code', $code)->firstOrFail();
    }

    public function test_pickup_order_goes_from_new_to_issued()
    {
        $order = $this->order('KV-4821');

        $this->as('samo')->post("/panel/objednavky/{$order->id}/potvrdit", ['eta' => '19:35'])->assertSessionHas('toast');
        $this->assertSame(OrderStatus::Preparing, $order->fresh()->status);
        $this->assertSame('19:35', $order->fresh()->eta);

        $this->as('samo')->post("/panel/objednavky/{$order->id}/pripravene");
        $this->assertSame(OrderStatus::Ready, $order->fresh()->status);

        $this->as('samo')->get('/panel/vydaj?kod=4821')->assertInertia(fn (Assert $p) => $p->where('found.code', 'KV-4821')->where('error', null));
        $this->as('samo')->post("/panel/objednavky/{$order->id}/vydat");
        $this->assertSame(OrderStatus::Issued, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->issued_at);
    }

    public function test_delivery_order_is_handed_to_courier_and_delivered()
    {
        $order = $this->order('KV-3307'); // pripravuje sa, donáška
        $this->as('jana')->post("/panel/objednavky/{$order->id}/pripravene");
        $this->as('jana')->post("/panel/objednavky/{$order->id}/kurier");
        $this->assertSame(OrderStatus::OnTheWay, $order->fresh()->status);
        $this->as('jana')->post("/panel/objednavky/{$order->id}/vydat")->assertSessionHas('toast', 'Doručené · KV-3307');
        $this->assertSame(OrderStatus::Issued, $order->fresh()->status);
    }

    public function test_order_can_be_rejected_with_reason()
    {
        $order = $this->order('KV-8840');
        $this->as('peter')->post("/panel/objednavky/{$order->id}/odmietnut", ['reason' => 'Vypredané']);
        $this->assertSame(OrderStatus::Rejected, $order->fresh()->status);
        $this->assertStringContainsString('Vypredané', $order->fresh()->reason);
    }

    public function test_invalid_transition_is_refused()
    {
        $order = $this->order('KV-5190'); // už vydaná
        $this->as('peter')->post("/panel/objednavky/{$order->id}/pripravene")->assertSessionHasErrors('order');
        $this->assertSame(OrderStatus::Issued, $order->fresh()->status);
    }

    public function test_unknown_code_shows_error()
    {
        $this->as('samo')->get('/panel/vydaj?kod=9999')->assertInertia(fn (Assert $p) => $p->where('found', null)->where('error', 'Kód KV‑9999 medzi dnešnými rezerváciami nie je.'));
    }

    public function test_fake_order_command_creates_new_order_visible_in_poll()
    {
        Artisan::call('campusbox:fake-order');
        $this->as('peter')->getJson('/panel/objednavky/nove')->assertOk()->assertJsonCount(3, 'new');
    }

    public function test_expired_orders_are_cancelled_automatically()
    {
        $this->travelTo(today()->setTime(23, 59));
        Artisan::call('campusbox:expire-orders');
        $this->assertSame(OrderStatus::Cancelled, $this->order('KV-4821')->status);
        $this->assertSame(OrderStatus::Issued, $this->order('KV-5190')->status);
    }
}
