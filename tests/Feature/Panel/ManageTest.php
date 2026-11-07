<?php

namespace Tests\Feature\Panel;

use App\Mail\TeamInvitation;
use App\Models\BusinessMember;
use App\Models\Dish;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

class ManageTest extends PanelTestCase
{
    public function test_offer_can_be_published()
    {
        $dish = Dish::where('name', 'Caesar šalát')->firstOrFail();
        $this->as('jana')->put('/panel/ponuka', [
            'type' => 'zachrana',
            'slot' => '20:30–21:00',
            'rows' => [['key' => 'dish:'.$dish->id, 'on' => true, 'count' => 4, 'price' => 99], ['key' => 'surprise', 'on' => false, 'count' => 1, 'price' => 3]],
            'delivery' => ['on' => true, 'fee' => '0,99', 'radius' => '3 km', 'by' => 'Vlastný kuriér'],
        ])->assertSessionHas('toast', 'Ponuka zverejnená · 4 porcií');

        $item = $this->business->offerItems()->whereDate('date', today())->where('item_key', 'dish:'.$dish->id)->firstOrFail();
        $this->assertSame('6.90', $item->price); // cena orezaná na bežnú cenu
        $this->assertFalse($this->business->offerItems()->where('item_key', 'surprise')->where('type', 'zachrana')->first()->active);
        $this->assertSame('20:30–21:00', $this->business->fresh()->rescue_slot);
        $this->assertSame('3 km', $this->business->fresh()->delivery_radius);
    }

    public function test_dish_crud_with_options_and_photo()
    {
        Storage::fake('public');
        $section = $this->business->sections()->first();

        $this->as('jana')->post('/panel/menu/jedla', [
            'name' => 'Pizza Margherita', 'sectionId' => $section->id, 'category' => 'Pizza', 'price' => '8,90',
            'allergens' => ['lepok', 'mlieko'], 'vegetarian' => '1',
            'groups' => [['q' => 'Veľkosť', 'multi' => '0', 'req' => '1', 'opts' => [['n' => '32 cm', 'p' => ''], ['n' => '45 cm', 'p' => '3,00']]]],
            'photo' => UploadedFile::fake()->image('pizza.jpg'),
        ])->assertSessionHasNoErrors();

        $dish = Dish::where('name', 'Pizza Margherita')->firstOrFail();
        $this->assertSame('8.90', $dish->price);
        $this->assertTrue($dish->vegetarian);
        $this->assertCount(2, $dish->option_groups[0]['opts']);
        Storage::disk('public')->assertExists($dish->photo_path);

        $this->as('jana')->put("/panel/menu/jedla/{$dish->id}", ['name' => 'Pizza Margherita XL', 'sectionId' => $section->id, 'category' => 'Pizza', 'price' => '9'])->assertSessionHasNoErrors();
        $this->assertSame('Pizza Margherita XL', $dish->fresh()->name);
        $this->assertSame([], $dish->fresh()->option_groups);

        $this->as('jana')->delete("/panel/menu/jedla/{$dish->id}");
        $this->assertModelMissing($dish);
    }

    public function test_dish_validation_messages_are_slovak()
    {
        $section = $this->business->sections()->first();
        $this->as('jana')->post('/panel/menu/jedla', ['name' => '', 'sectionId' => $section->id, 'category' => 'Pizza', 'price' => 'abc'])
            ->assertSessionHasErrors(['name' => 'Vyplňte názov jedla.', 'price' => 'Cenu zadajte ako číslo, napr. 7,90.']);
    }

    public function test_sections_and_order_can_be_changed()
    {
        $this->as('peter')->post('/panel/menu/sekcie', ['name' => 'Nápoje']);
        $sections = $this->business->sections()->with('dishes')->get();
        $last = $sections->last();
        $dish = $sections->first()->dishes->first();

        $payload = $sections->reverse()->values()->map(fn ($s) => ['id' => $s->id, 'dishes' => $s->id === $last->id ? [$dish->id] : $s->dishes->pluck('id')->reject(fn ($id) => $id === $dish->id)->values()->all()])->all();
        $this->as('peter')->put('/panel/menu/poradie', ['sections' => $payload]);

        $this->assertSame(0, $last->fresh()->position);
        $this->assertSame($last->id, $dish->fresh()->menu_section_id);
    }

    public function test_team_invitation_flow()
    {
        Mail::fake();
        $this->as('peter')->post('/panel/tim', ['email' => 'novy@podurpinom.sk', 'role' => 'manazer'])->assertSessionHas('toast');
        $member = BusinessMember::where('email', 'novy@podurpinom.sk')->firstOrFail();
        Mail::assertSent(TeamInvitation::class, fn ($m) => $m->hasTo('novy@podurpinom.sk'));

        auth()->logout(); // pozvaný ešte nemá účet
        $this->get("/panel/pozvanka/{$member->invite_token}")->assertOk()->assertInertia(fn (Assert $p) => $p->where('hasAccount', false));
        $this->post("/panel/pozvanka/{$member->invite_token}", ['name' => 'Nový Manažér', 'password' => 'heslo12345', 'password_confirmation' => 'heslo12345'])
            ->assertRedirect(route('panel.overview'));

        $this->assertNotNull($member->fresh()->joined_at);
        $this->assertAuthenticatedAs(User::where('email', 'novy@podurpinom.sk')->first());
        $this->get('/panel/ponuka')->assertOk();
        $this->get('/panel/tim')->assertForbidden();
    }

    public function test_owner_role_cannot_be_changed()
    {
        $owner = BusinessMember::where('email', 'peter@podurpinom.sk')->first();
        $worker = BusinessMember::where('email', 'samo@podurpinom.sk')->first();

        $this->as('peter')->put("/panel/tim/{$owner->id}", ['role' => 'pracovnik'])->assertForbidden();
        $this->as('peter')->put("/panel/tim/{$worker->id}", ['role' => 'manazer']);
        $this->assertSame('manazer', $worker->fresh()->role->value);
    }

    public function test_profile_update_and_billing_only_for_owner()
    {
        $base = ['name' => 'Bistro Nové', 'types' => ['Bistro'], 'categories' => ['Pečivo'], 'address' => 'Nová 1', 'opening_hours' => array_fill(0, 7, ['X', '09:00', '17:00', false])];

        $this->as('jana')->put('/panel/profil', $base + ['iban' => 'SK00 0000 0000 0000 0000 0000'])->assertSessionHasNoErrors();
        $this->assertSame('Bistro Nové', $this->business->fresh()->name);
        $this->assertSame('SK31 1200 0000 1987 4263 7541', $this->business->fresh()->iban); // manažér IBAN nezmení
        $this->assertSame('Pondelok', $this->business->fresh()->opening_hours[0][0]);

        $this->as('peter')->put('/panel/profil', $base + ['ico' => '12345678', 'iban' => 'SK89 0200 0000 0012 3456 7890', 'billing_email' => 'f@b.sk'])->assertSessionHasNoErrors();
        $this->assertSame('SK89 0200 0000 0012 3456 7890', $this->business->fresh()->iban);
    }

    public function test_payouts_show_stats_and_periods()
    {
        $this->as('peter')->get('/panel/vyplaty')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Panel/Payouts')
            ->has('periods', 3)
            ->where('periods.0.status', 'Spracúva sa')
            ->where('periods.1.status', 'Čaká na výplatu')
            ->has('bars'));

        $this->as('peter')->get('/panel/vyplaty?mesiac='.now()->subMonth()->format('Y-m'))->assertInertia(fn (Assert $p) => $p->where('stats.sales', fn ($v) => $v > 0));
    }
}
