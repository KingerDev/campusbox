<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Validation\ValidationException;

/**
 * Prechody stavov objednávky. Každá akcia overí, že je z aktuálneho stavu povolená.
 */
class OrderWorkflow
{
    public function confirm(Order $order, ?string $eta): void
    {
        $this->expect($order, [OrderStatus::New]);
        $order->update(['status' => OrderStatus::Preparing, 'eta' => $eta, 'confirmed_at' => now()]);
    }

    public function reject(Order $order, string $reason): void
    {
        $this->expect($order, [OrderStatus::New]);
        $order->update(['status' => OrderStatus::Rejected, 'reason' => $reason.' · vrátené', 'closed_at' => now()]);
    }

    public function ready(Order $order): void
    {
        $this->expect($order, [OrderStatus::Preparing]);
        $order->update(['status' => OrderStatus::Ready, 'ready_at' => now()]);
    }

    public function handover(Order $order): void
    {
        abort_unless($order->isDelivery(), 422);
        $this->expect($order, [OrderStatus::Ready]);
        $order->update(['status' => OrderStatus::OnTheWay]);
    }

    /** Vydanie pri pulte alebo potvrdenie doručenia. */
    public function issue(Order $order): void
    {
        $this->expect($order, [OrderStatus::Preparing, OrderStatus::Ready, OrderStatus::OnTheWay]);
        $order->update(['status' => OrderStatus::Issued, 'issued_at' => now(), 'closed_at' => now()]);
    }

    /** @param list<OrderStatus> $allowed */
    private function expect(Order $order, array $allowed): void
    {
        if (! in_array($order->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'order' => 'Objednávka '.$order->code.' je už v stave „'.$order->status->label().'“.',
            ]);
        }
    }
}
