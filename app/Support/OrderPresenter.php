<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\Order;

class OrderPresenter
{
    public static function toArray(Order $order, bool $withMoney): array
    {
        $status = $order->status;
        // Pri donáške je „Pripravené“ z pohľadu podniku „Čaká na kuriéra“.
        $statusLabel = $order->isDelivery() && $status === OrderStatus::Ready ? 'Čaká na kuriéra' : $status->label();

        return [
            'id' => $order->id,
            'code' => $order->code,
            'name' => $order->customer_name,
            'items' => $order->itemsLabel(),
            'qty' => $order->quantity(),
            'sum' => $withMoney ? (float) $order->total : null,
            'at' => $order->ordered_at->format('H:i'),
            'status' => $status->value,
            'statusLabel' => $statusLabel,
            'delivery' => $order->isDelivery(),
            'address' => $order->delivery_address,
            'eta' => $order->eta,
            'reason' => $order->reason,
        ];
    }
}
