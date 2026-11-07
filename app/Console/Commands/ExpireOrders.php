<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Objednávka nevydaná do 15 min po konci okna výdaja sa zruší a peniaze sa vrátia.
 */
class ExpireOrders extends Command
{
    protected $signature = 'campusbox:expire-orders';

    protected $description = 'Zruší objednávky, ktoré neboli vydané do 15 minút po konci okna výdaja';

    public function handle(): int
    {
        $expired = 0;

        Order::with(['business', 'items'])
            ->whereIn('status', [OrderStatus::New, OrderStatus::Preparing, OrderStatus::Ready])
            ->whereDate('pickup_date', '<=', today())
            ->each(function (Order $order) use (&$expired) {
                $business = $order->business;
                $slot = $order->items->contains('type', 'menu') ? $business->menu_slot : $business->rescue_slot;
                $end = trim(explode('–', $slot)[1] ?? '23:59');
                $deadline = Carbon::parse($order->pickup_date->toDateString().' '.$end)->addMinutes(15);

                if (now()->greaterThan($deadline)) {
                    $order->update(['status' => OrderStatus::Cancelled, 'reason' => 'Nevydané · vrátené automaticky', 'closed_at' => now()]);
                    $expired++;
                }
            });

        $this->info("Zrušených objednávok: {$expired}");

        return self::SUCCESS;
    }
}
