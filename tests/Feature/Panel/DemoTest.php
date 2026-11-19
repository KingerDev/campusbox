<?php

namespace Tests\Feature\Panel;

use App\Enums\BusinessRole;
use App\Models\Business;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

class DemoTest extends PanelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // V deme sa 2FA nevyžaduje, ani keď je inak povinné.
        config(['panel.two_factor_required' => true]);
    }

    public function test_visitor_gets_a_private_copy_and_is_logged_in_as_its_owner()
    {
        $this->post('/panel/demo')->assertRedirect('/panel');

        $user = auth()->user();
        $this->assertTrue($user->is_demo);
        $this->assertStringEndsWith('@demo.campusbox.invalid', $user->email);

        $demo = Business::where('is_demo', true)->sole();
        $this->assertNotSame($this->business->id, $demo->id);
        $this->assertTrue($demo->demo_expires_at->isFuture());
        $this->assertSame(BusinessRole::Owner, $demo->members()->where('user_id', $user->id)->sole()->role);
        $this->assertGreaterThan(50, $demo->orders()->count());

        $this->get('/panel')->assertOk()->assertInertia(fn (Assert $p) => $p->where('panel.business.demo', true)->where('panel.business.id', $demo->id));
        foreach (['/panel/ponuka', '/panel/menu', '/panel/objednavky', '/panel/vyplaty', '/panel/tim', '/panel/profil'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_each_visitor_gets_a_separate_copy()
    {
        $this->post('/panel/demo');
        $first = auth()->user();
        auth()->logout();
        $this->flushSession();

        $this->post('/panel/demo');
        $second = auth()->user();

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(2, Business::where('is_demo', true)->count());
        $this->assertNotSame($first->memberships()->sole()->business_id, $second->memberships()->sole()->business_id);
    }

    public function test_logged_in_user_keeps_their_own_panel()
    {
        $this->as('peter')->post('/panel/demo')->assertRedirect('/panel');
        $this->assertSame(0, Business::where('is_demo', true)->count());
    }

    public function test_demo_does_not_send_invitations_or_store_uploads()
    {
        Mail::fake();
        Storage::fake('public');
        $this->post('/panel/demo');

        $this->post('/panel/tim', ['email' => 'niekto@example.com', 'role' => 'pracovnik'])->assertSessionHasNoErrors();
        Mail::assertNothingSent();
        $this->assertDatabaseHas('business_members', ['email' => 'niekto@example.com']);

        $this->post('/panel/profil/obrazok', ['kind' => 'logo', 'image' => UploadedFile::fake()->image('logo.png')]);
        $this->assertNull(Business::where('is_demo', true)->sole()->logo_path);

        $section = Business::where('is_demo', true)->sole()->sections()->first()->id;
        $this->post('/panel/menu/jedla', ['name' => 'Skúška', 'sectionId' => $section, 'category' => 'Polievka', 'price' => '3,50', 'photo' => UploadedFile::fake()->image('jedlo.jpg')])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('dishes', ['name' => 'Skúška', 'photo_path' => null]);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_demo_invitation_links_do_not_work()
    {
        $this->post('/panel/demo');
        $token = Business::where('is_demo', true)->sole()->members()->whereNotNull('invite_token')->value('invite_token');
        auth()->logout();

        $this->get('/panel/pozvanka/'.$token)->assertNotFound();
    }

    public function test_demo_account_has_no_account_settings()
    {
        $this->post('/panel/demo');

        $this->get('/settings/profile')->assertRedirect('/panel');
        $this->get('/settings/two-factor')->assertRedirect('/panel');
        $this->put('/settings/password', ['current_password' => 'x', 'password' => 'nove-heslo-123', 'password_confirmation' => 'nove-heslo-123'])->assertRedirect('/panel');
        $this->delete('/settings/profile', ['password' => 'x'])->assertRedirect('/panel');
        $this->assertNotNull(auth()->user()->fresh());
    }

    public function test_leaving_demo_logs_out_and_opens_registration()
    {
        $this->post('/panel/demo');
        $this->post('/panel/demo/ukoncit')->assertRedirect('/panel/registracia');
        $this->assertGuest();
    }

    public function test_expired_demo_is_pruned_with_its_accounts_and_real_data_stays()
    {
        $this->post('/panel/demo');
        $demo = Business::where('is_demo', true)->sole();
        $this->assertSame(3, User::where('is_demo', true)->count());

        $this->artisan('campusbox:prune-demos');
        $this->assertNotNull($demo->fresh(), 'Platné demo sa nemaže.');

        $this->travel(25)->hours();
        // Po vypršaní už panel nepustí ďalej…
        $this->get('/panel')->assertRedirect('/panel/registracia');
        $this->assertGuest();

        // …a čistenie zmaže podnik aj dočasné účty, skutočné dáta nechá.
        $this->artisan('campusbox:prune-demos');
        $this->assertNull($demo->fresh());
        $this->assertSame(0, User::where('is_demo', true)->count());
        $this->assertDatabaseMissing('orders', ['business_id' => $demo->id]);
        $this->assertNotNull($this->business->fresh());
        $this->assertSame(3, User::count());
    }

    public function test_demo_is_refused_when_the_limit_is_reached()
    {
        config(['panel.demo_limit' => 1]);
        $this->post('/panel/demo');
        auth()->logout();
        $this->flushSession();

        $this->post('/panel/demo')->assertSessionHasErrors('demo');
        $this->assertGuest();
        $this->assertSame(1, Business::where('is_demo', true)->count());
    }
}
