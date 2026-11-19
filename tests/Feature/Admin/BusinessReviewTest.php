<?php

namespace Tests\Feature\Admin;

use App\Mail\BusinessApproved;
use App\Mail\BusinessRejected;
use App\Mail\NewBusinessRegistered;
use App\Models\Business;
use App\Models\User;
use App\Support\TwoFactor;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Panel\PanelTestCase;

class BusinessReviewTest extends PanelTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->admin();
        $this->business->update(['status' => 'pending', 'approved_at' => null]);
    }

    private function admin(bool $twoFactor = true): User
    {
        $user = User::factory()->create();
        $user->forceFill(['is_admin' => true])->save();
        if ($twoFactor) {
            $tf = app(TwoFactor::class);
            $tf->begin($user);
            $tf->confirm($user, $tf->currentCode($user->two_factor_secret));
        }

        return $user->fresh();
    }

    public function test_admin_area_is_hidden_from_everyone_else()
    {
        $this->get('/admin')->assertRedirect('/login');
        // Ani majiteľ podniku nie je správca.
        $this->as('peter')->get('/admin')->assertNotFound();
        $this->as('peter')->post("/admin/podniky/{$this->business->id}/schvalit")->assertNotFound();
        $this->assertSame('pending', $this->business->fresh()->status);
    }

    public function test_admin_cannot_be_set_through_registration_or_profile()
    {
        $user = User::factory()->create();
        $this->actingAs($user)->patch('/settings/profile', ['name' => 'X', 'email' => $user->email, 'is_admin' => true]);
        $this->assertFalse($user->fresh()->is_admin);
    }

    public function test_admin_without_two_factor_gets_in_and_sees_the_suggestion()
    {
        $fresh = $this->admin(twoFactor: false);

        $this->actingAs($fresh)->get('/admin')->assertOk()->assertInertia(fn (Assert $p) => $p->where('suggestTwoFactor', true));
        $this->actingAs($this->admin)->get('/admin')->assertOk()->assertInertia(fn (Assert $p) => $p->where('suggestTwoFactor', false));
        $this->actingAs($fresh)->get('/settings/two-factor')->assertInertia(fn (Assert $p) => $p->where('required', false)->where('admin', true));
    }

    public function test_when_required_admin_needs_two_factor_first()
    {
        config(['panel.two_factor_required' => true]);
        $fresh = $this->admin(twoFactor: false);

        $this->actingAs($fresh)->get('/admin')->assertRedirect('/settings/two-factor');
        $this->actingAs($fresh)->post("/admin/podniky/{$this->business->id}/schvalit")->assertRedirect('/settings/two-factor');
        $this->assertSame('pending', $this->business->fresh()->status);
        $this->actingAs($fresh)->get('/settings/two-factor')->assertInertia(fn (Assert $p) => $p->where('required', true)->where('admin', true));
    }

    public function test_admin_without_a_business_lands_in_admin_after_login()
    {
        $this->actingAs($this->admin)->get('/dashboard')->assertRedirect('/admin');
        $this->as('peter')->get('/dashboard')->assertRedirect('/panel');
    }

    public function test_admin_sees_pending_businesses_with_owner_details_but_no_demos()
    {
        $this->post('/panel/demo');
        auth()->logout();

        $this->actingAs($this->admin)->get('/admin')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Admin/Businesses')
            ->where('status', 'pending')
            ->where('counts.pending', 1)
            ->has('businesses', 1)
            ->where('businesses.0.name', 'Bistro Pod Urpínom')
            ->where('businesses.0.owner.email', 'peter@podurpinom.sk')
            ->where('businesses.0.ico', '54 123 987')
            ->where('businesses.0.dishes', 5));

        $this->get('/admin?stav=approved')->assertInertia(fn (Assert $p) => $p->where('status', 'approved')->has('businesses', 0));
    }

    public function test_approving_notifies_the_owner_and_clears_the_banner()
    {
        Mail::fake();
        $this->as('peter')->get('/panel')->assertInertia(fn (Assert $p) => $p->where('panel.business.approved', false));

        $this->actingAs($this->admin)->post("/admin/podniky/{$this->business->id}/schvalit")->assertSessionHasNoErrors();

        $this->assertSame('approved', $this->business->fresh()->status);
        $this->assertNotNull($this->business->fresh()->approved_at);
        Mail::assertSent(BusinessApproved::class, fn ($m) => $m->hasTo('peter@podurpinom.sk') && ! $m->hasTo('jana@podurpinom.sk'));
        $this->as('peter')->get('/panel')->assertInertia(fn (Assert $p) => $p->where('panel.business.approved', true)->where('panel.business.rejected', false));
    }

    public function test_rejecting_needs_a_reason_and_shows_it_to_the_owner()
    {
        Mail::fake();
        $this->actingAs($this->admin)->post("/admin/podniky/{$this->business->id}/zamietnut", ['reason' => ''])->assertSessionHasErrors('reason');
        $this->assertSame('pending', $this->business->fresh()->status);

        $this->post("/admin/podniky/{$this->business->id}/zamietnut", ['reason' => 'IČO sa nezhoduje s registrom.'])->assertSessionHasNoErrors();
        $this->assertSame('rejected', $this->business->fresh()->status);
        Mail::assertSent(BusinessRejected::class, fn ($m) => $m->hasTo('peter@podurpinom.sk'));

        $this->as('peter')->get('/panel')->assertInertia(fn (Assert $p) => $p
            ->where('panel.business.rejected', true)->where('panel.business.rejectionReason', 'IČO sa nezhoduje s registrom.'));

        // Zamietnutý podnik sa dá neskôr schváliť.
        $this->actingAs($this->admin)->post("/admin/podniky/{$this->business->id}/schvalit");
        $this->assertSame('approved', $this->business->fresh()->status);
        $this->assertNull($this->business->fresh()->rejection_reason);
    }

    public function test_demo_business_cannot_be_reviewed()
    {
        $this->post('/panel/demo');
        auth()->logout();
        $demo = Business::where('is_demo', true)->sole();

        $this->actingAs($this->admin)->post("/admin/podniky/{$demo->id}/zamietnut", ['reason' => 'Skúška zamietnutia'])->assertNotFound();
    }

    public function test_new_registration_notifies_admins()
    {
        Mail::fake();
        config(['panel.notify_email' => 'ahoj@campusbox.sk']);

        $this->post('/panel/registracia', [
            'meno' => 'Eva Nová', 'email' => 'eva@pekaren.sk', 'tel' => '0905 111 222', 'heslo' => 'cerstve-rozky-2026',
            'nazov' => 'Pekáreň Eva', 'ico' => '12 345 678', 'typ' => 'Pekáreň',
            'adresa' => 'Dolná 1, Banská Bystrica', 'latitude' => 48.7392, 'longitude' => 19.1196,
            'opening_hours' => [...array_fill(0, 6, ['', '06:00', '14:00', false]), ['', '', '', true]],
            'iban' => 'SK31 1200 0000 1987 4263 7541', 'fakt' => 'eva@pekaren.sk', 'suhlas' => true,
        ])->assertSessionHasNoErrors();

        Mail::assertSent(NewBusinessRegistered::class, fn ($m) => $m->hasTo($this->admin->email) && $m->hasTo('ahoj@campusbox.sk')
            && $m->business->name === 'Pekáreň Eva' && str_contains($m->render(), 'eva@pekaren.sk'));
    }

    public function test_only_approved_real_businesses_are_visible_to_students()
    {
        $this->assertSame(0, Business::visibleToStudents()->count());

        $this->business->update(['status' => 'approved']);
        $this->post('/panel/demo'); // demo je „schválené“, ale študentom sa neukáže
        $this->assertSame([$this->business->id], Business::visibleToStudents()->pluck('id')->all());

        $this->business->update(['status' => 'rejected']);
        $this->assertSame(0, Business::visibleToStudents()->count());
    }

    public function test_make_admin_command()
    {
        $user = User::factory()->create(['email' => 'martin@campusbox.sk']);
        $this->artisan('campusbox:make-admin', ['email' => 'Martin@CampusBox.sk'])->assertSuccessful();
        $this->assertTrue($user->fresh()->is_admin);

        $this->artisan('campusbox:make-admin', ['email' => 'martin@campusbox.sk', '--revoke' => true])->assertSuccessful();
        $this->assertFalse($user->fresh()->is_admin);
    }
}
