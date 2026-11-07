<?php

namespace App\Services;

use App\Enums\OfferType;
use App\Models\Business;
use App\Models\Dish;
use App\Models\OfferItem;
use Illuminate\Support\Collection;

/**
 * Riadky dnešnej ponuky: balíček prekvapenie (len záchrana) + každé jedlo z menu.
 */
class OfferBoard
{
    public const SURPRISE_PRICE = 9.0;

    public const PLACEHOLDER = ['Teplé jedlo' => '#EFB08A', 'Polievka' => '#E9C46A', 'Pečivo' => '#F2C48D', 'Dezert' => '#F4A9A0', 'Šalát' => '#C9E2B3', 'Pizza' => '#E88A5B', 'Nápoj' => '#D9B08C'];

    /** @return list<array<string, mixed>> */
    public function rows(Business $business, OfferType $type): array
    {
        $saved = $this->items($business)->where('type', $type)->keyBy('item_key');
        $dishes = $business->dishes()->with('section')->get()->sortBy(fn (Dish $d) => [$d->section?->position, $d->position]);

        $rows = [];
        if ($type === OfferType::Rescue) {
            $rows[] = ['key' => 'surprise', 'dishId' => null, 'name' => 'Balíček prekvapenie', 'sub' => 'Mix toho, čo zostane', 'regular' => self::SURPRISE_PRICE, 'ph' => '#F6B77E'];
        }
        foreach ($dishes as $dish) {
            $rows[] = ['key' => 'dish:'.$dish->id, 'dishId' => $dish->id, 'name' => $dish->name, 'sub' => $dish->category, 'regular' => (float) $dish->price, 'ph' => self::PLACEHOLDER[$dish->category] ?? '#E3C3A0', 'photo' => $dish->photoUrl()];
        }

        return array_map(function (array $row) use ($saved, $type) {
            /** @var OfferItem|null $item */
            $item = $saved->get($row['key']);
            $factor = $type === OfferType::Menu ? 0.8 : 0.4;

            return $row + [
                'on' => $item?->active ?? false,
                'count' => $item?->quantity ?? ($type === OfferType::Menu ? 10 : 2),
                'price' => $item ? (float) $item->price : max(0.5, round($row['regular'] * $factor, 1)),
            ];
        }, $rows);
    }

    /** @return Collection<int, OfferItem> */
    public function items(Business $business): Collection
    {
        return $business->offerItems()->whereDate('date', today())->get();
    }

    /** @return Collection<int, OfferItem> */
    public function activeItems(Business $business): Collection
    {
        // Najprv záchrana jedla, potom študentské menu.
        return $this->items($business)->where('active', true)->sortBy(fn (OfferItem $i) => [$i->type->value === 'menu', $i->id])->values();
    }
}
