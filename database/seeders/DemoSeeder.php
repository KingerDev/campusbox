<?php

namespace Database\Seeders;

use App\Enums\BusinessRole;
use App\Enums\OfferType;
use App\Enums\OrderStatus;
use App\Models\Business;
use App\Models\Dish;
use App\Models\OfferItem;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Ukážkový podnik Bistro Pod Urpínom s tímom, menu, dnešnou ponukou a históriou objednávok.
 * Prihlásenie: peter@podurpinom.sk (majiteľ), jana@podurpinom.sk (manažér), samo@podurpinom.sk (pracovník), heslo „password“.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(42);

        $business = Business::create([
            'name' => 'Bistro Pod Urpínom',
            'ico' => '54 123 987',
            'dic' => '2121987654',
            'types' => ['Bistro', 'Pizzeria'],
            'categories' => ['Teplé jedlá', 'Polievky'],
            'address' => 'Tajovského 40, 974 01 Banská Bystrica',
            'pickup_instructions' => 'Pri pulte povedzte, že idete po balíček z CampusBox.',
            'description' => 'Domáce denné menu pár krokov od Ekonomickej fakulty. Varíme z lokálnych surovín a nechceme nič vyhadzovať.',
            'phone' => '+421 48 123 45 67',
            'public_email' => 'ahoj@podurpinom.sk',
            'web' => 'podurpinom.sk',
            'instagram' => '@bistro.podurpinom',
            'facebook' => 'facebook.com/bistropodurpinom',
            'opening_hours' => Business::defaultHours(),
            'delivery_enabled' => true,
            'delivery_fee' => 1.49,
            'iban' => 'SK31 1200 0000 1987 4263 7541',
            'billing_email' => 'faktury@podurpinom.sk',
            'status' => 'approved',
            'approved_at' => now(),
            'terms_accepted_at' => now(),
            'issue_tried_at' => now(),
            'onboarding_dismissed_at' => now(),
        ]);

        $people = [
            ['Peter Urban', 'peter@podurpinom.sk', BusinessRole::Owner],
            ['Jana Kmeťová', 'jana@podurpinom.sk', BusinessRole::Manager],
            ['Samo Lukáč', 'samo@podurpinom.sk', BusinessRole::Worker],
        ];
        foreach ($people as [$name, $email, $role]) {
            $user = User::firstOrCreate(['email' => $email], ['name' => $name, 'password' => 'password', 'email_verified_at' => now()]);
            $business->members()->create(['user_id' => $user->id, 'email' => $email, 'role' => $role, 'joined_at' => now()]);
        }
        $business->members()->create(['email' => 'ivana.simkova@gmail.com', 'role' => BusinessRole::Worker, 'invite_token' => Str::random(48)]);

        $sections = collect(['Denné menu', 'Polievky', 'Šaláty', 'Sladké'])
            ->mapWithKeys(fn ($name, $i) => [$name => $business->sections()->create(['name' => $name, 'position' => $i])]);

        $dish = fn (string $section, array $attrs) => $business->dishes()->create($attrs + [
            'menu_section_id' => $sections[$section]->id,
            'option_groups' => [],
            'position' => $sections[$section]->dishes()->count(),
        ]);

        $rizoto = $dish('Denné menu', ['name' => 'Kurací rizoto', 'category' => 'Teplé jedlo', 'description' => 'Krémové rizoto s kuracím mäsom a parmezánom, 350 g', 'price' => 7.9, 'allergens' => ['mlieko'], 'popular' => true,
            'option_groups' => [['q' => 'Veľkosť porcie', 'multi' => false, 'req' => true, 'max' => null, 'opts' => [['n' => 'Normálna', 'p' => ''], ['n' => 'Veľká', 'p' => '1,50']]]]]);
        $gulas = $dish('Denné menu', ['name' => 'Hovädzí guláš', 'category' => 'Teplé jedlo', 'description' => 'S chlebom alebo knedľou', 'price' => 8.5, 'allergens' => ['lepok', 'zeler'],
            'option_groups' => [
                ['q' => 'Akú prílohu si želáte?', 'multi' => false, 'req' => true, 'max' => null, 'opts' => [['n' => 'Chlieb', 'p' => ''], ['n' => 'Knedľa', 'p' => '0,50']]],
                ['q' => 'Niečo navyše?', 'multi' => true, 'req' => false, 'max' => 2, 'opts' => [['n' => 'Cibuľa', 'p' => '0,30'], ['n' => 'Feferóny', 'p' => '0,30'], ['n' => 'Kofola 0,33 l', 'p' => '1,20']]],
            ]]);
        $polievka = $dish('Polievky', ['name' => 'Šošovicová polievka', 'category' => 'Polievka', 'description' => 'Hustá, s údenou paprikou, 0,5 l', 'price' => 3.2, 'allergens' => ['zeler'], 'vegetarian' => true]);
        $dish('Šaláty', ['name' => 'Caesar šalát', 'category' => 'Šalát', 'description' => 'Rímsky šalát, kuracie mäso, krutóny', 'price' => 6.9, 'allergens' => ['vajcia', 'mlieko', 'ryby']]);
        $palacinky = $dish('Sladké', ['name' => 'Palacinky s džemom', 'category' => 'Dezert', 'description' => '2 ks, domáci marhuľový džem', 'price' => 4.5, 'allergens' => ['lepok', 'vajcia', 'mlieko'], 'vegetarian' => true, 'popular' => true]);

        // Dnešná ponuka.
        $offer = fn (OfferType $type, ?Dish $d, int $qty, float $price) => OfferItem::create([
            'business_id' => $business->id, 'date' => today(), 'type' => $type,
            'item_key' => $d ? 'dish:'.$d->id : 'surprise', 'dish_id' => $d?->id,
            'name' => $d?->name ?? 'Balíček prekvapenie', 'regular_price' => $d?->price ?? 9,
            'price' => $price, 'quantity' => $qty, 'active' => true,
        ]);
        $surprise = $offer(OfferType::Rescue, null, 4, 3.5);
        $rizotoZ = $offer(OfferType::Rescue, $rizoto, 3, 3.2);
        $polievkaZ = $offer(OfferType::Rescue, $polievka, 5, 1.2);
        $offer(OfferType::Menu, $rizoto, 15, 5.9);
        $gulasM = $offer(OfferType::Menu, $gulas, 10, 6.5);
        $offer(OfferType::Menu, $polievka, 20, 2.4);

        // Dnešné objednávky v rôznych stavoch (ako v dizajne).
        $today = [
            ['KV-8840', 'Michal T.', [[$rizotoZ, 1]], '17:38', OrderStatus::New, 'Internát Tajovského, blok C'],
            ['KV-4821', 'Ema K.', [[$surprise, 1]], '17:31', OrderStatus::New, null],
            ['KV-3307', 'Matej H.', [[$rizotoZ, 2]], '16:12', OrderStatus::Preparing, 'Ul. 29. augusta 12', '19:35'],
            ['KV-7020', 'Simona K.', [[$gulasM, 1]], '12:05', OrderStatus::OnTheWay, 'Internát 1. mája, blok A', '13:10'],
            ['KV-6012', 'Dominika R.', [[$polievkaZ, 1], [$surprise, 1]], '16:40', OrderStatus::Ready, null, '19:30'],
            ['KV-1408', 'Peter G.', [[$surprise, 1]], '15:02', OrderStatus::Cancelled, null, null, 'Zrušil študent 16:10 · vrátené'],
            ['KV-0937', 'Zuzana D.', [[$rizotoZ, 1]], '14:48', OrderStatus::Reported, null, null, 'Balíček nebol pripravený · vrátené'],
            ['KV-5190', 'Lucia P.', [[$surprise, 1]], '15:58', OrderStatus::Issued],
            ['KV-2264', 'Tomáš B.', [[$rizotoZ, 1]], '15:20', OrderStatus::Issued],
            ['KV-7743', 'Natália S.', [[$polievkaZ, 2]], '14:05', OrderStatus::Issued],
        ];
        foreach ($today as $row) {
            [$code, $name, $lines, $at, $status] = $row;
            $this->order($business, $code, today()->toImmutable(), $name, $lines, $at, $status, $row[5] ?? null, $row[6] ?? null, $row[7] ?? null);
        }

        // História za posledných ~75 dní pre štatistiky a výplaty.
        $pool = [[$surprise, 3.5], [$rizotoZ, 3.2], [$polievkaZ, 1.2], [$gulasM, 6.5]];
        for ($d = 75; $d >= 1; $d--) {
            $date = CarbonImmutable::today()->subDays($d);
            $count = $date->isWeekend() ? mt_rand(0, 2) : mt_rand(2, 6);
            for ($n = 0; $n < $count; $n++) {
                [$item] = $pool[mt_rand(0, count($pool) - 1)];
                $status = mt_rand(1, 150) === 1 ? OrderStatus::Reported : OrderStatus::Issued;
                $this->order($business, Order::generateCode($business->id, $date->toDateString()), $date, collect(['Adam', 'Barbora', 'Kristína', 'Martin', 'Nina', 'Ondrej', 'Veronika'])->random().' '.chr(mt_rand(65, 90)).'.', [[$item, mt_rand(1, 2)]], sprintf('%02d:%02d', mt_rand(11, 19), mt_rand(0, 59)), $status, null, null, $status === OrderStatus::Reported ? 'Balíček nebol pripravený · vrátené' : null);
            }
        }

        // Vyplatené obdobia (všetky ukončené polmesiace okrem posledného).
        $start = CarbonImmutable::today()->subDays(75)->startOfMonth();
        $currentStart = now()->day <= 15 ? now()->startOfMonth() : now()->startOfMonth()->setDay(16);
        $periods = [];
        while ($start->lt($currentStart)) {
            $end = $start->day === 1 ? $start->setDay(15) : $start->endOfMonth();
            $periods[] = [$start, $end];
            $start = $start->day === 1 ? $start->setDay(16) : $start->addMonth()->startOfMonth();
        }
        array_pop($periods); // posledné ukončené obdobie ešte čaká na výplatu
        foreach ($periods as [$from, $to]) {
            $net = Order::where('business_id', $business->id)->where('status', OrderStatus::Issued)
                ->whereBetween('pickup_date', [$from->toDateString(), $to->toDateString()])->with('items')->get()
                ->flatMap->items->sum(fn ($i) => $i->quantity * $i->unit_price * (1 - $i->commission_rate));
            $business->payouts()->create(['period_start' => $from, 'period_end' => $to, 'amount' => round($net, 2), 'paid_at' => $to->addDays(2)]);
        }
    }

    /** @param list<array{0:OfferItem,1:int}> $lines */
    private function order(Business $business, string $code, CarbonImmutable $date, string $name, array $lines, string $at, OrderStatus $status, ?string $address = null, ?string $eta = null, ?string $reason = null): void
    {
        $orderedAt = $date->setTimeFromTimeString($at);
        $total = 0;
        $commission = 0;
        foreach ($lines as [$item, $qty]) {
            $total += $qty * (float) $item->price;
            $commission += $qty * (float) $item->price * $item->type->commissionRate();
        }

        $order = $business->orders()->create([
            'code' => $code,
            'pickup_date' => $date,
            'customer_name' => $name,
            'fulfilment' => $address ? 'delivery' : 'pickup',
            'delivery_address' => $address,
            'status' => $status,
            'eta' => $eta,
            'reason' => $reason,
            'total' => $total + ($address ? (float) $business->delivery_fee : 0),
            'commission' => round($commission, 2),
            'ordered_at' => $orderedAt,
            'confirmed_at' => $status !== OrderStatus::New ? $orderedAt->addMinutes(3) : null,
            'issued_at' => $status === OrderStatus::Issued ? $orderedAt->addMinutes(40) : null,
            'closed_at' => in_array($status, [OrderStatus::Issued, OrderStatus::Cancelled, OrderStatus::Reported], true) ? $orderedAt->addMinutes(40) : null,
        ]);

        foreach ($lines as [$item, $qty]) {
            $order->items()->create([
                'offer_item_id' => $item->date->isToday() && $date->isToday() ? $item->id : null,
                'type' => $item->type->value,
                'name' => $item->name,
                'quantity' => $qty,
                'unit_price' => $item->price,
                'commission_rate' => $item->type->commissionRate(),
            ]);
        }
    }
}
