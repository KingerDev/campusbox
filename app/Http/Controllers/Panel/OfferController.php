<?php

namespace App\Http\Controllers\Panel;

use App\Enums\OfferType;
use App\Http\Controllers\Controller;
use App\Models\OfferItem;
use App\Services\OfferBoard;
use App\Support\CurrentBusiness;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OfferController extends Controller
{
    public function edit(Request $request, CurrentBusiness $current, OfferBoard $board): Response
    {
        $business = $current->business();

        return Inertia::render('Panel/Offer', [
            'type' => $request->query('typ') === 'menu' ? 'menu' : 'zachrana',
            'rows' => [
                'zachrana' => $board->rows($business, OfferType::Rescue),
                'menu' => $board->rows($business, OfferType::Menu),
            ],
            'slots' => [
                'zachrana' => ['options' => OfferType::Rescue->slots(), 'value' => $business->rescue_slot],
                'menu' => ['options' => OfferType::Menu->slots(), 'value' => $business->menu_slot],
            ],
            'rates' => ['zachrana' => OfferType::Rescue->commissionRate(), 'menu' => OfferType::Menu->commissionRate()],
            'delivery' => [
                'on' => $business->delivery_enabled,
                'fee' => number_format((float) $business->delivery_fee, 2, ',', ''),
                'radius' => $business->delivery_radius,
                'by' => $business->delivery_by,
            ],
        ]);
    }

    /** Zverejní ponuku jedného typu na dnes (aj s oknom výdaja a nastavením doručenia). */
    public function update(Request $request, CurrentBusiness $current, OfferBoard $board): RedirectResponse
    {
        $business = $current->business();
        $type = OfferType::from($request->input('type'));

        $data = $request->validate([
            'type' => ['required', Rule::enum(OfferType::class)],
            'slot' => ['required', Rule::in($type->slots())],
            'rows' => ['array'],
            'rows.*.key' => ['required', 'string'],
            'rows.*.on' => ['boolean'],
            'rows.*.count' => ['required', 'integer', 'min:1', 'max:500'],
            'rows.*.price' => ['required', 'numeric', 'min:0.5', 'max:999'],
            'delivery.on' => ['boolean'],
            'delivery.fee' => ['nullable', 'regex:/^\d{1,2}([.,]\d{1,2})?$/'],
            'delivery.radius' => [Rule::in(['1 km', '2 km', '3 km'])],
            'delivery.by' => [Rule::in(['Kuriér CampusBox', 'Vlastný kuriér'])],
        ], ['delivery.fee.regex' => 'Cenu doručenia zadajte ako číslo, napr. 1,49.']);

        $known = collect($board->rows($business, $type))->keyBy('key');
        $rows = collect($data['rows'] ?? [])->filter(fn ($r) => $known->has($r['key']));
        $published = 0;

        DB::transaction(function () use ($business, $type, $data, $rows, $known, &$published) {
            foreach ($rows as $row) {
                $base = $known[$row['key']];
                // Cena pre študenta nesmie byť vyššia ako bežná cena.
                $price = min((float) $row['price'], (float) $base['regular']);
                $attrs = ['dish_id' => $base['dishId'], 'name' => $base['name'], 'regular_price' => $base['regular'], 'price' => $price, 'quantity' => $row['count'], 'active' => (bool) $row['on']];
                // whereDate: dátum je v SQLite uložený aj s časom, porovnanie reťazcom by riadok nenašlo.
                $existing = $business->offerItems()->whereDate('date', today())->where('type', $type)->where('item_key', $row['key'])->first();
                $existing
                    ? $existing->update($attrs)
                    : OfferItem::create($attrs + ['business_id' => $business->id, 'date' => today(), 'type' => $type, 'item_key' => $row['key']]);
                if ($row['on']) {
                    $published += $row['count'];
                }
            }

            $business->update([
                $type === OfferType::Rescue ? 'rescue_slot' : 'menu_slot' => $data['slot'],
                'delivery_enabled' => (bool) data_get($data, 'delivery.on', $business->delivery_enabled),
                'delivery_fee' => round((float) str_replace(',', '.', (string) data_get($data, 'delivery.fee', $business->delivery_fee)), 2),
                'delivery_radius' => data_get($data, 'delivery.radius', $business->delivery_radius),
                'delivery_by' => data_get($data, 'delivery.by', $business->delivery_by),
            ]);
        });

        return back()->with('toast', 'Ponuka zverejnená · '.$published.' porcií');
    }
}
