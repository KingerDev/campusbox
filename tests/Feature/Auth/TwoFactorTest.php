<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\TwoFactor;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private TwoFactor $twoFactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->twoFactor = app(TwoFactor::class);
    }

    /** Používateľ so zapnutým 2FA; vráti [user, záložné kódy]. */
    private function userWithTwoFactor(?User $user = null): array
    {
        $user ??= User::factory()->create();
        $this->twoFactor->begin($user);
        $codes = $this->twoFactor->confirm($user, $this->code($user));
        // Potvrdenie „minulo“ aktuálny kód; knižnica ide podľa skutočného času, tak ho pre test uvoľníme.
        $user->forceFill(['two_factor_last_step' => null])->save();

        return [$user->fresh(), $codes];
    }

    private function code(User $user): string
    {
        return $this->twoFactor->currentCode($user->fresh()->two_factor_secret);
    }

    public function test_setup_shows_qr_and_turns_on_only_after_a_valid_code()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/settings/two-factor')->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('settings/TwoFactor')->where('enabled', false)->where('setup', null));

        $this->post('/settings/two-factor')->assertRedirect('/settings/two-factor');
        $this->get('/settings/two-factor')->assertInertia(fn (Assert $p) => $p->where('enabled', false)
            ->where('setup.secret', $user->fresh()->two_factor_secret)->where('setup.svg', fn ($svg) => str_starts_with($svg, '<svg')));
        $this->assertFalse($user->fresh()->hasTwoFactor());

        $this->post('/settings/two-factor/confirm', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertFalse($user->fresh()->hasTwoFactor());

        $this->post('/settings/two-factor/confirm', ['code' => $this->code($user)])
            ->assertRedirect('/settings/two-factor')->assertSessionHas('recovery_codes', fn ($codes) => count($codes) === 8);
        $this->assertTrue($user->fresh()->hasTwoFactor());

        // Záložné kódy sa ukážu len raz.
        $this->get('/settings/two-factor')->assertInertia(fn (Assert $p) => $p->where('enabled', true)->where('setup', null)->has('recoveryCodes', 8));
        $this->get('/settings/two-factor')->assertInertia(fn (Assert $p) => $p->where('recoveryCodes', null)->where('remaining', 8));
    }

    public function test_secret_and_recovery_codes_are_encrypted_and_never_shared_with_the_page()
    {
        [$user, $codes] = $this->userWithTwoFactor();

        $raw = \DB::table('users')->where('id', $user->id)->first();
        $this->assertStringNotContainsString($user->two_factor_secret, $raw->two_factor_secret);
        $this->assertStringNotContainsString($codes[0], $raw->two_factor_recovery_codes);

        $this->actingAs($user)->get('/settings/two-factor')->assertInertia(fn (Assert $p) => $p
            ->missing('auth.user.two_factor_secret')->missing('auth.user.two_factor_recovery_codes')->missing('auth.user.password'));
    }

    public function test_login_without_two_factor_works_as_before()
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_two_factor_needs_the_code()
    {
        [$user] = $this->userWithTwoFactor();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/two-factor-challenge');
        $this->assertGuest();
        $this->get('/panel')->assertRedirect('/login');
        $this->get('/two-factor-challenge')->assertOk();

        $this->post('/two-factor-challenge', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();

        $this->post('/two-factor-challenge', ['code' => $this->code($user)])->assertRedirect('/panel');
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_never_reaches_the_challenge()
    {
        [$user] = $this->userWithTwoFactor();

        $this->post('/login', ['email' => $user->email, 'password' => 'zle-heslo'])->assertSessionHasErrors('email');
        $this->get('/two-factor-challenge')->assertRedirect('/login');
        $this->post('/two-factor-challenge', ['code' => $this->code($user)])->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_the_same_code_cannot_be_used_twice()
    {
        [$user] = $this->userWithTwoFactor();
        $code = $this->code($user);

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->post('/two-factor-challenge', ['code' => $code]);
        $this->assertAuthenticatedAs($user);

        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->post('/two-factor-challenge', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_recovery_code_works_once()
    {
        [$user, $codes] = $this->userWithTwoFactor();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->post('/two-factor-challenge', ['recovery_code' => 'aaaaa-bbbbb'])->assertSessionHasErrors('recovery_code');
        $this->post('/two-factor-challenge', ['recovery_code' => strtoupper($codes[0])])->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
        $this->assertCount(7, $user->fresh()->two_factor_recovery_codes);

        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->post('/two-factor-challenge', ['recovery_code' => $codes[0]])->assertSessionHasErrors('recovery_code');
        $this->assertGuest();
    }

    public function test_challenge_is_rate_limited()
    {
        [$user] = $this->userWithTwoFactor();
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        foreach (range(1, 5) as $i) {
            $this->post('/two-factor-challenge', ['code' => '000000']);
        }
        // Po 5 chybách neprejde ani správny kód.
        $this->post('/two-factor-challenge', ['code' => $this->code($user)])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_challenge_expires()
    {
        [$user] = $this->userWithTwoFactor();
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->travel(11)->minutes();
        $this->post('/two-factor-challenge', ['code' => $this->code($user)])->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_disabling_and_new_recovery_codes_need_the_password()
    {
        [$user, $codes] = $this->userWithTwoFactor();
        $this->actingAs($user);

        $this->post('/settings/two-factor/recovery-codes', ['password' => 'zle'])->assertSessionHasErrors('password');
        $this->assertSame($codes, $user->fresh()->two_factor_recovery_codes);
        $this->post('/settings/two-factor/recovery-codes', ['password' => 'password'])->assertSessionHas('recovery_codes');
        $this->assertNotSame($codes, $user->fresh()->two_factor_recovery_codes);

        $this->delete('/settings/two-factor', ['password' => 'zle'])->assertSessionHasErrors('password');
        $this->assertTrue($user->fresh()->hasTwoFactor());
        $this->delete('/settings/two-factor', ['password' => 'password'])->assertSessionHasNoErrors();
        $this->assertFalse($user->fresh()->hasTwoFactor());
        $this->assertNull($user->fresh()->two_factor_secret);
    }

    public function test_by_default_panel_only_suggests_it_to_owner_and_manager()
    {
        $this->seed(DemoSeeder::class);
        $who = fn (string $name) => User::where('email', $name.'@podurpinom.sk')->firstOrFail();

        foreach (['peter' => true, 'jana' => true, 'samo' => false] as $name => $suggest) {
            $this->actingAs($who($name))->get('/panel')->assertOk()->assertInertia(fn (Assert $p) => $p->where('panel.suggestTwoFactor', $suggest));
            $this->actingAs($who($name))->get('/settings/two-factor')->assertInertia(fn (Assert $p) => $p->where('required', false));
        }

        $this->userWithTwoFactor($who('peter'));
        $this->actingAs($who('peter'))->get('/panel')->assertInertia(fn (Assert $p) => $p->where('panel.suggestTwoFactor', false));
    }

    public function test_when_required_owner_and_manager_must_set_it_up_before_using_the_panel_but_worker_does_not()
    {
        config(['panel.two_factor_required' => true]);
        $this->seed(DemoSeeder::class);
        $who = fn (string $name) => User::where('email', $name.'@podurpinom.sk')->firstOrFail();

        foreach (['peter', 'jana'] as $name) {
            $this->actingAs($who($name))->get('/panel')->assertRedirect('/settings/two-factor');
            $this->actingAs($who($name))->get('/panel/objednavky')->assertRedirect('/settings/two-factor');
            $this->actingAs($who($name))->get('/settings/two-factor')->assertInertia(fn (Assert $p) => $p->where('required', true));
        }
        $this->actingAs($who('peter'))->get('/panel/vyplaty')->assertRedirect('/settings/two-factor');
        $this->actingAs($who('peter'))->put('/panel/profil', [])->assertRedirect('/settings/two-factor');

        $this->actingAs($who('samo'))->get('/panel')->assertOk();
        $this->actingAs($who('samo'))->get('/settings/two-factor')->assertInertia(fn (Assert $p) => $p->where('required', false));

        $this->userWithTwoFactor($who('peter'));
        $this->actingAs($who('peter'))->get('/panel')->assertOk();
        $this->actingAs($who('peter'))->get('/panel/vyplaty')->assertOk();
    }
}
