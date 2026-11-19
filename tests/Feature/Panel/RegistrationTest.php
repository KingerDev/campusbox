<?php

namespace Tests\Feature\Panel;

use App\Enums\BusinessRole;
use App\Models\Business;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private array $data = [
        'meno' => 'Peter Urban', 'email' => 'peter@bistro.sk', 'tel' => '+421 905 123 456', 'heslo' => 'kvasok2026',
        'nazov' => 'Bistro Test', 'ico' => '54 123 987', 'typ' => 'Bistro',
        'adresa' => 'Tajovského 40, Banská Bystrica', 'pokyny' => 'Pri pulte', 'donaska' => true, 'poplatok' => '1,49',
        'latitude' => 48.7392, 'longitude' => 19.1196,
        'opening_hours' => [['', '09:00', '17:00', false], ['', '09:00', '17:00', false], ['', '09:00', '17:00', false], ['', '09:00', '17:00', false], ['', '09:00', '17:00', false], ['', '', '', true], ['', '', '', true]],
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
        $this->post('/panel/registracia/overit', ['step' => 1, 'nazov' => 'X', 'ico' => '12', 'typ' => 'Vegetariánska'])->assertSessionHasErrors(['ico', 'typ']);
        $this->post('/panel/registracia/overit', ['step' => 2, 'adresa' => 'Niekde 1'])->assertSessionHasErrors(['latitude' => 'Označte podnik na mape – kliknite na miesto alebo potiahnite špendlík.', 'opening_hours']);
        $this->post('/panel/registracia/overit', ['step' => 2, 'latitude' => 50.08, 'longitude' => 14.42] + $this->data)->assertSessionHasErrors(['latitude' => 'Poloha podniku musí byť na Slovensku.']);
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
        $this->assertSame('Bistro', $business->type);
        $this->assertSame([], $business->categories(), 'Bez jedál v menu podnik ešte žiadnu kategóriu nemá.');
        $this->assertTrue($business->delivery_enabled);
        $this->assertSame('1.49', $business->delivery_fee);
        $this->assertSame(BusinessRole::Owner, $business->members()->first()->role);
        $this->assertSame(3, $business->sections()->count());
        $this->assertSame(48.7392, $business->latitude);
        $this->assertSame(['Pondelok', '09:00', '17:00', false], $business->opening_hours[0]);
        $this->assertTrue($business->opening_hours[6][3]);

        // Nový majiteľ ide rovno do panela; dvojfaktorové overenie mu panel len odporučí.
        $this->get('/panel')->assertOk()->assertInertia(fn (Assert $p) => $p->where('panel.suggestTwoFactor', true));
        $this->get('/panel')->assertOk()->assertInertia(fn (Assert $p) => $p->component('Panel/Overview')->where('panel.business.approved', false));
    }

    public function test_email_must_be_unique()
    {
        User::factory()->create(['email' => 'peter@bistro.sk']);
        $this->post('/panel/registracia', $this->data)->assertSessionHasErrors('email');
    }

    public function test_closed_day_ignores_leftover_times_and_open_day_needs_valid_ones()
    {
        $hours = fn (array $sunday) => ['step' => 2, 'adresa' => 'Niekde 1', 'latitude' => 48.7392, 'longitude' => 19.1196,
            'opening_hours' => [...array_fill(0, 6, ['', '9:00', '17:00', false]), $sunday]];

        // Zatvorený deň môže mať v časoch čokoľvek (zostatok po prepnutí).
        $this->post('/panel/registracia/overit', $hours(['', 'abc', '25', true]))->assertSessionHasNoErrors();

        $this->post('/panel/registracia/overit', $hours(['', 'abc', '17:00', false]))
            ->assertSessionHasErrors(['opening_hours.6.1' => 'Čas otvorenia zadajte vo formáte 10:00.']);
        $this->post('/panel/registracia/overit', $hours(['', '', '', false]))
            ->assertSessionHasErrors(['opening_hours.6.1' => 'Doplňte čas otvorenia, alebo deň označte ako zatvorený.']);

        $this->assertSame(['Nedeľa', '', '', true], Business::normalizeHours($hours(['', 'abc', '25', true])['opening_hours'])[6]);
        $this->assertSame(['Pondelok', '09:00', '17:00', false], Business::normalizeHours($hours(['', '', '', true])['opening_hours'])[0]);
    }

    public function test_registration_steps_and_submit_do_not_share_one_rate_limit()
    {
        // Sprievodca overuje každý krok zvlášť; tie pokusy nesmú minúť limit na odoslanie žiadosti.
        foreach (range(1, 12) as $i) {
            $this->post('/panel/registracia/overit', ['step' => 1])->assertStatus(302);
        }

        $this->post('/panel/registracia', [])->assertStatus(302)->assertSessionHasErrors('nazov');
    }

    public function test_too_many_attempts_show_a_form_error_in_inertia()
    {
        foreach (range(1, 10) as $i) {
            $this->post('/panel/registracia', []);
        }

        $this->from('/panel/registracia')->post('/panel/registracia', [], ['X-Inertia' => 'true'])
            ->assertRedirect('/panel/registracia')->assertSessionHasErrors('throttle');
    }

    public function test_phone_and_email_are_normalized_whatever_way_they_are_typed()
    {
        foreach (['0905 123 456', '905123456', '+421905123456', '00421 905 123456', '+421 905 123 456'] as $tel) {
            $this->assertSame('+421 905 123 456', Phone::normalize($tel), $tel);
        }
        $this->assertSame('+420 777 123 456', Phone::normalize('+420777123456'));

        $this->post('/panel/registracia', ['tel' => '0905 123 456', 'email' => '  Peter@Bistro.SK '] + $this->data)->assertSessionHasNoErrors();
        $user = User::where('email', 'peter@bistro.sk')->firstOrFail();
        $this->assertSame('+421 905 123 456', $user->phone);
    }

    public function test_incomplete_phone_is_rejected()
    {
        foreach (['0905 123', 'abc', '+421 905 123 4567 89'] as $tel) {
            $this->post('/panel/registracia/overit', ['step' => 0, 'tel' => $tel] + $this->data)
                ->assertSessionHasErrors(['tel' => 'Telefón zadajte s predvoľbou, napr. +421 903 123 456.']);
        }
    }
}
