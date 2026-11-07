<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'pickup_date' => 'date',
            'status' => OrderStatus::class,
            'total' => 'decimal:2',
            'commission' => 'decimal:2',
            'ordered_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'ready_at' => 'datetime',
            'issued_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeToday(Builder $query): void
    {
        $query->whereDate('pickup_date', today());
    }

    public function isDelivery(): bool
    {
        return $this->fulfilment === 'delivery';
    }

    /** Krátky popis obsahu, napr. „1× Polievka, 2× Balíček prekvapenie“. */
    public function itemsLabel(): string
    {
        return $this->items->map(fn (OrderItem $i) => $i->quantity.'× '.($i->type === 'menu' ? 'Menu · ' : '').$i->name)->join(', ');
    }

    public function quantity(): int
    {
        return (int) $this->items->sum('quantity');
    }

    /** Vygeneruje kód KV‑xxxx, ktorý je v daný deň v podniku unikátny. */
    public static function generateCode(int $businessId, string $date): string
    {
        do {
            $code = 'KV-'.str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (self::where('business_id', $businessId)->whereDate('pickup_date', $date)->where('code', $code)->exists());

        return $code;
    }
}
