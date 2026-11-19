<?php

namespace Tests\Feature\Panel;

use App\Enums\BusinessRole;
use App\Enums\OfferType;
use App\Mail\TeamInvitation;
use App\Models\BusinessMember;
use App\Models\Dish;
use App\Models\User;
use App\Services\OfferBoard;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

class ManageTest extends PanelTestCase
{
    public function test_offer_can_be_published()
    {
        $this->travelTo(now()->setTime(15, 0));
        $dish = Dish::where('name', 'Caesar šalát')->firstOrFail();
        $this->as('jana')->put('/panel/ponuka', [
            'type' => 'zachrana',
            'until' => '20:30',
            'rows' => [['key' => 'dish:'.$dish->id, 'on' => true, 'count' => 4, 'price' => 99], ['key' => 'surprise', 'on' => false, 'count' => 1, 'price' => 3]],
            'delivery' => ['on' => true, 'fee' => '0,99', 'radius' => '3 km', 'by' => 'Vlastný kuriér'],
        ])->assertSessionHas('toast', 'Ponuka zverejnená · 4 porcií');

        // Neoverený podnik si ponuku uloží, ale nesľubujeme mu, že je zverejnená.
        $this->business->update(['status' => 'pending']);
        $this->as('jana')->put('/panel/ponuka', [
            'type' => 'zachrana',
            'until' => '20:30',
            'rows' => [['key' => 'dish:'.$dish->id, 'on' => true, 'count' => 4, 'price' => 99], ['key' => 'surprise', 'on' => false, 'count' => 1, 'price' => 3]],
            'delivery' => ['on' => true, 'fee' => '0,99', 'radius' => '3 km', 'by' => 'Vlastný kuriér'],
        ])->assertSessionHas('toast', 'Ponuka uložená · 4 porcií. Študenti ju uvidia až po overení podniku.');

        $item = $this->business->offerItems()->whereDate('date', today())->where('item_key', 'dish:'.$dish->id)->firstOrFail();
        $this->assertSame('6.90', $item->price); // cena orezaná na bežnú cenu
        $this->assertFalse($this->business->offerItems()->where('item_key', 'surprise')->where('type', 'zachrana')->first()->active);
        $this->assertSame('20:30', $this->business->fresh()->rescue_until);
        $this->assertSame('3 km', $this->business->fresh()->delivery_radius);
    }

    public function test_rescue_is_picked_up_until_closing_time_by_default()
    {
        $this->travelTo(now()->setTime(15, 0));
        $closing = $this->business->closingTime();
        $this->assertNotNull($closing);
        $this->assertSame($closing, $this->business->rescueUntil());

        $dish = Dish::where('name', 'Caesar šalát')->firstOrFail();
        $base = ['type' => 'zachrana', 'rows' => [['key' => 'dish:'.$dish->id, 'on' => true, 'count' => 2, 'price' => 3]], 'delivery' => ['on' => false]];

        // Vlastný čas sa uloží, záverečná nie – výdaj sa potom riadi otváracími hodinami.
        $this->as('jana')->put('/panel/ponuka', $base + ['until' => '18:15'])->assertSessionHasNoErrors();
        $this->assertSame('18:15', $this->business->fresh()->rescueUntil());
        $this->as('jana')->put('/panel/ponuka', $base + ['until' => $closing])->assertSessionHasNoErrors();
        $this->assertNull($this->business->fresh()->rescue_until);

        // Čas, ktorý už prešiel, pri zverejnení neprejde; bez času ani nezmyslu tiež nie.
        $this->as('jana')->put('/panel/ponuka', $base + ['until' => '14:00'])->assertSessionHasErrors('until');
        $this->as('jana')->put('/panel/ponuka', $base + ['until' => 'večer'])->assertSessionHasErrors('until');
        $this->as('jana')->put('/panel/ponuka', $base)->assertSessionHasErrors('until');
    }

    public function test_surprise_package_value_is_set_by_the_business()
    {
        $this->travelTo(now()->setTime(15, 0));
        $surprise = fn () => collect(app(OfferBoard::class)->rows($this->business->fresh(), OfferType::Rescue))->firstWhere('key', 'surprise');
        $this->assertSame(9.0, $surprise()['regular']);

        $put = fn (array $row) => $this->as('jana')->put('/panel/ponuka', ['type' => 'zachrana', 'until' => '20:30', 'rows' => [['key' => 'surprise', 'on' => true, 'count' => 3] + $row], 'delivery' => ['on' => false]]);

        // Vlastná bežná hodnota sa zapamätá aj na ďalšie dni a cena pre študenta ju nepresiahne.
        $put(['regular' => 6.5, 'price' => 8])->assertSessionHasNoErrors();
        $this->assertSame(6.5, $surprise()['regular']);
        $item = $this->business->offerItems()->whereDate('date', today())->where('item_key', 'surprise')->firstOrFail();
        $this->assertSame('6.50', $item->regular_price);
        $this->assertSame('6.50', $item->price);

        $put(['regular' => 0, 'price' => 2])->assertSessionHasErrors('rows.0.regular');
    }

    public function test_menu_pickup_window_must_be_valid()
    {
        $base = ['type' => 'menu', 'rows' => [], 'delivery' => ['on' => false]];
        $this->as('jana')->put('/panel/ponuka', $base + ['slot' => '11:15–13:45'])->assertSessionHasNoErrors();
        $this->assertSame('11:15–13:45', $this->business->fresh()->menu_slot);

        $this->as('jana')->put('/panel/ponuka', $base + ['slot' => '14:00–11:00'])->assertSessionHasErrors(['slot' => 'Koniec výdaja musí byť neskôr ako začiatok.']);
        $this->as('jana')->put('/panel/ponuka', $base + ['slot' => 'obed'])->assertSessionHasErrors('slot');
        $this->as('jana')->put('/panel/ponuka', $base)->assertSessionHasErrors('slot');
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
        $this->assertFalse($dish->vegan);

        // Vegánske jedlo sa uloží aj ako vegetariánske.
        $this->as('jana')->post('/panel/menu/jedla', ['name' => 'Tofu miska', 'sectionId' => $section->id, 'category' => 'Teplé jedlo', 'price' => '6,50', 'vegan' => '1'])->assertSessionHasNoErrors();
        $tofu = Dish::where('name', 'Tofu miska')->firstOrFail();
        $this->assertTrue($tofu->vegan);
        $this->assertTrue($tofu->vegetarian);
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
        $base = ['name' => 'Bistro Nové', 'type' => 'Pekáreň', 'address' => 'Nová 1', 'latitude' => 48.73, 'longitude' => 19.15, 'opening_hours' => array_fill(0, 7, ['X', '09:00', '17:00', false])];

        $this->as('jana')->put('/panel/profil', $base + ['iban' => 'SK00 0000 0000 0000 0000 0000'])->assertSessionHasNoErrors();
        $this->assertSame('Bistro Nové', $this->business->fresh()->name);
        $this->assertSame('Pekáreň', $this->business->fresh()->type);
        $this->as('jana')->put('/panel/profil', ['type' => 'Vegetariánska'] + $base)->assertSessionHasErrors('type');
        $this->assertSame('SK31 1200 0000 1987 4263 7541', $this->business->fresh()->iban); // manažér IBAN nezmení
        $this->assertSame('Pondelok', $this->business->fresh()->opening_hours[0][0]);

        $this->as('peter')->put('/panel/profil', $base + ['ico' => '12345678', 'iban' => 'SK89 0200 0000 0012 3456 7890', 'billing_email' => 'f@b.sk'])->assertSessionHasNoErrors();
        $this->assertSame('SK89 0200 0000 0012 3456 7890', $this->business->fresh()->iban);
    }

    public function test_business_categories_follow_the_menu()
    {
        // Ukážkové menu: rizoto a guláš (teplé jedlo), polievka, šalát, palacinky (dezert).
        $this->assertSame(['Teplé jedlo', 'Polievka', 'Šalát', 'Dezert'], $this->business->categories());
        $this->as('peter')->get('/panel/profil')->assertInertia(fn (Assert $p) => $p
            ->where('categories', ['Teplé jedlo', 'Polievka', 'Šalát', 'Dezert'])->where('profile.type', 'Bistro')->missing('profile.categories'));

        // Zmizne posledná polievka → podnik už medzi polievkami nie je; pribudne pizza → je medzi pizzami.
        $this->business->dishes()->where('category', 'Polievka')->delete();
        $this->business->dishes()->first()->replicate()->fill(['name' => 'Margherita', 'category' => 'Pizza'])->save();

        $this->assertSame(['Teplé jedlo', 'Šalát', 'Pizza', 'Dezert'], $this->business->categories());
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

    public function test_owner_cannot_delete_account_but_staff_can(): void
    {
        $owner = $this->business->owner()->user;
        $this->actingAs($owner)->get('/settings/profile')
            ->assertInertia(fn ($p) => $p->where('ownedBusinesses', [$this->business->name]));
        $this->delete('/settings/profile', ['password' => 'password'])->assertSessionHasErrors('password');
        $this->assertAuthenticated();
        $this->assertNotNull($owner->fresh());

        $staff = $this->business->members()->where('role', '!=', BusinessRole::Owner)->whereNotNull('joined_at')->firstOrFail()->user;
        $this->actingAs($staff)->get('/settings/profile')->assertInertia(fn ($p) => $p->where('ownedBusinesses', []));
        $this->delete('/settings/profile', ['password' => 'password'])->assertRedirect('/');
        $this->assertNull($staff->fresh());
    }
}
