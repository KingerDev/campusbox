<?php

namespace Tests\Feature\Panel;

use App\Models\Business;
use Database\Seeders\DemoSeeder;
use Inertia\Testing\AssertableInertia as Assert;

class RolesTest extends PanelTestCase
{
    public function test_owner_can_open_every_screen()
    {
        foreach (['/panel', '/panel/ponuka', '/panel/menu', '/panel/vydaj', '/panel/objednavky', '/panel/vyplaty', '/panel/tim', '/panel/profil', '/panel/viac', '/panel/vyplaty/vypis'] as $url) {
            $this->as('peter')->get($url)->assertOk();
        }
    }

    public function test_manager_cannot_see_payouts_or_team()
    {
        $this->as('jana')->get('/panel/ponuka')->assertOk();
        $this->as('jana')->get('/panel/profil')->assertOk()->assertInertia(fn (Assert $p) => $p->where('canBill', false)->missing('profile.iban'));
        $this->as('jana')->get('/panel/vyplaty')->assertForbidden();
        $this->as('jana')->get('/panel/tim')->assertForbidden();
    }

    public function test_worker_sees_only_issue_and_orders_without_money()
    {
        $this->as('samo')->get('/panel/ponuka')->assertForbidden();
        $this->as('samo')->get('/panel/menu')->assertForbidden();
        $this->as('samo')->get('/panel/profil')->assertForbidden();

        $this->as('samo')->get('/panel/objednavky')->assertOk()
            ->assertInertia(fn (Assert $p) => $p->where('orders.0.sum', null)->where('panel.abilities', []));
        $this->as('samo')->get('/panel')->assertOk()
            ->assertInertia(fn (Assert $p) => $p->where('kpi.sales', null)->where('reliability', null));
        $this->as('samo')->getJson('/panel/objednavky/nove')->assertOk()->assertJsonPath('new.0.sum', null);
    }

    public function test_members_cannot_touch_other_businesses()
    {
        // Druhý podnik, v ktorom Peter nie je členom.
        $this->seed(DemoSeeder::class);
        $other = Business::latest('id')->first();
        $other->members()->delete();
        $order = $other->orders()->where('status', 'nova')->first();
        $dish = $other->dishes()->first();

        $this->as('peter')->post("/panel/objednavky/{$order->id}/potvrdit")->assertNotFound();
        $this->as('peter')->delete("/panel/menu/jedla/{$dish->id}")->assertNotFound();
        $this->assertModelExists($dish);
    }
}
