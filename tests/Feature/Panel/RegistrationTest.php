<?php

namespace Tests\Feature\Panel;

use App\Enums\BusinessRole;
use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private array $data = [
        'meno' => 'Peter Urban', 'email' => 'peter@bistro.sk', 'tel' => '+421 905 123 456', 'heslo' => 'kvasok2026',
        'nazov' => 'Bistro Test', 'ico' => '54 123 987', 'typ' => ['Bistro'], 'zost' => ['Polievky'],
        'adresa' => 'Tajovského 40, Banská Bystrica', 'pokyny' => 'Pri pulte', 'donaska' => true, 'poplatok' => '1,49',
        'iban' => 'SK31 1200 0000 1987 4263 7541', 'fakt' => 'faktury@bistro.sk', 'suhlas' => true,
    ];

    public function test_registration_page_renders()
    {
        $this->get('/panel/registracia')->assertOk()->assertInertia(fn (Assert $p) => $p->component('Panel/Registration'));
        $this->get('/register')->assertRedirect('/panel/registracia');
    }

    public function test_each_step_is_validated()
    {
        $this->post('/panel/registracia/overit', ['step' => 0, 'meno' => 'A'])->assertSessionHasErrors(['email', 'tel', 'heslo']);
        $this->post('/panel/registracia/overit', ['step' => 1, 'nazov' => 'X', 'ico' => '12', 'typ' => []])->assertSessionHasErrors(['ico', 'typ', 'zost']);
        $this->post('/panel/registracia/overit', ['step' => 3, 'iban' => 'SK31 1200 0000 1987 4263 7541', 'fakt' => 'a@b.sk'])->assertSessionHasErrors('suhlas');
        $this->post('/panel/registracia/overit', ['step' => 1] + $this->data)->assertSessionHasNoErrors();
    }

    public function test_business_owner_can_register()
    {
        $this->post('/panel/registracia', $this->data)->assertRedirect(route('panel.registration', ['hotovo' => 1]));

        $user = User::where('email', 'peter@bistro.sk')->firstOrFail();
        $business = Business::where('name', 'Bistro Test')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('pending', $business->status);
        $this->assertTrue($business->delivery_enabled);
        $this->assertSame('1.49', $business->delivery_fee);
        $this->assertSame(BusinessRole::Owner, $business->members()->first()->role);
        $this->assertSame(3, $business->sections()->count());

        $this->get('/panel')->assertOk()->assertInertia(fn (Assert $p) => $p->component('Panel/Overview')->where('panel.business.approved', false));
    }

    public function test_email_must_be_unique()
    {
        User::factory()->create(['email' => 'peter@bistro.sk']);
        $this->post('/panel/registracia', $this->data)->assertSessionHasErrors('email');
    }
}
