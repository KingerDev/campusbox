<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Models\Order;
use App\Services\OfferBoard;
use Illuminate\Console\Command;

/**
 * Kým neexistuje študentská appka, simuluje novú objednávku z dnešnej ponuky podniku.
 */
class FakeOrder extends Command
{
    protected $signature = 'campusbox:fake-order {business? : ID podniku (predvolene prvý)} {--delivery : objednávka s donáškou}';

    protected $description = 'Vytvorí testovaciu objednávku „Na potvrdenie“ z dnešnej ponuky';

    public function handle(OfferBoard $board): int
    {
        $business = $this->argument('business') ? Business::findOrFail($this->argument('business')) : Business::firstOrFail();
        $items = $board->activeItems($business);

        if ($items->isEmpty()) {
            $this->error('Podnik nemá na dnes zverejnenú ponuku.');

            return self::FAILURE;
        }

        $lines = $items->shuffle()->take(random_int(1, 2))->map(fn ($item) => [$item, random_int(1, 2)]);
        $delivery = $this->option('delivery') && $business->delivery_enabled;
        $total = $lines->sum(fn ($l) => $l[1] * (float) $l[0]->price) + ($delivery ? (float) $business->delivery_fee : 0);

        $order = $business->orders()->create([
            'code' => Order::generateCode($business->id, today()->toDateString()),
            'pickup_date' => today(),
            'customer_name' => collect(['Lukáš V.', 'Ema K.', 'Matej H.', 'Simona K.', 'Dominika R.'])->random(),
            'fulfilment' => $delivery ? 'delivery' : 'pickup',
            'delivery_address' => $delivery ? 'Internát Tajovského, blok C' : null,
            'status' => 'nova',
            'total' => $total,
            'commission' => round($lines->sum(fn ($l) => $l[1] * (float) $l[0]->price * $l[0]->type->commissionRate()), 2),
            'ordered_at' => now(),
        ]);

        foreach ($lines as [$item, $qty]) {
            $order->items()->create([
                'offer_item_id' => $item->id,
                'type' => $item->type->value,
                'name' => $item->name,
                'quantity' => $qty,
                'unit_price' => $item->price,
                'commission_rate' => $item->type->commissionRate(),
            ]);
        }

        $this->info("Objednávka {$order->code} vytvorená pre {$business->name}.");

        return self::SUCCESS;
    }
}
