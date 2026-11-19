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
            // Menu: okno výdaja od–do. Záchrana: len čas „do“, predvolene dnešná záverečná.
            'menuSlot' => $business->menu_slot,
            'rescueUntil' => $business->rescueUntil(),
            // Dnešné otváracie hodiny – karta výdaja podľa nich upozorní na čas mimo otvorenia.
            'today' => (fn (array $h) => ['open' => $h[1], 'close' => $h[2], 'closed' => (bool) $h[3]])($business->todayHours()),
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
            // Menu: okno výdaja „HH:MM–HH:MM“, koniec musí byť po začiatku.
            'slot' => [Rule::requiredIf($type === OfferType::Menu), 'bail', 'nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d–([01]\d|2[0-3]):[0-5]\d$/', function ($attr, $value, $fail) {
                [$from, $to] = explode('–', $value);
                if ($to <= $from) {
                    $fail('Koniec výdaja musí byť neskôr ako začiatok.');
                }
            }],
            // Záchrana: dokedy si študenti jedlo vyzdvihnú. Zverejniť ponuku s časom, ktorý už prešiel, nemá zmysel.
            'until' => [Rule::requiredIf($type === OfferType::Rescue), 'bail', 'nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/', function ($attr, $value, $fail) use ($request) {
                $selling = collect($request->input('rows', []))->contains(fn ($r) => ! empty($r['on']));
                if ($selling && $value <= now()->format('H:i')) {
                    $fail('Tento čas už prešiel. Zadajte, dokedy si študenti jedlo dnes ešte vyzdvihnú.');
                }
            }],
            'rows' => ['array'],
            'rows.*.key' => ['required', 'string'],
            'rows.*.on' => ['boolean'],
            'rows.*.count' => ['required', 'integer', 'min:1', 'max:500'],
            // Bežnú hodnotu si podnik určuje len pri balíčku prekvapenie; jedlá ju majú z menu.
            'rows.*.regular' => ['nullable', 'numeric', 'min:1', 'max:999'],
            'rows.*.price' => ['required', 'numeric', 'min:0.5', 'max:999'],
            'delivery.on' => ['boolean'],
            'delivery.fee' => ['nullable', 'regex:/^\d{1,2}([.,]\d{1,2})?$/'],
            'delivery.radius' => [Rule::in(['1 km', '2 km', '3 km'])],
            'delivery.by' => [Rule::in(['Kuriér CampusBox', 'Vlastný kuriér'])],
        ], ['delivery.fee.regex' => 'Cenu doručenia zadajte ako číslo, napr. 1,49.', 'slot.regex' => 'Zadajte čas výdaja od – do, napr. 11:00 – 14:00.', 'slot.required' => 'Zadajte, od kedy do kedy menu vydávate.', 'until.regex' => 'Zadajte čas v tvare 20:00.', 'until.required' => 'Zadajte, dokedy si študenti jedlo vyzdvihnú.']);

        $known = collect($board->rows($business, $type))->keyBy('key');
        $rows = collect($data['rows'] ?? [])->filter(fn ($r) => $known->has($r['key']));
        $published = 0;

        DB::transaction(function () use ($business, $type, $data, $rows, $known, &$published) {
            foreach ($rows as $row) {
                $base = $known[$row['key']];
                if ($row['key'] === 'surprise' && isset($row['regular'])) {
                    $base['regular'] = round((float) $row['regular'], 2);
                    $business->surprise_value = $base['regular'];
                }
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
                // Čas zhodný so záverečnou neukladáme – výdaj sa potom každý deň riadi otváracími hodinami.
                ...($type === OfferType::Rescue
                    ? ['rescue_until' => $data['until'] === $business->closingTime() ? null : $data['until']]
                    : ['menu_slot' => $data['slot']]),
                'delivery_enabled' => (bool) data_get($data, 'delivery.on', $business->delivery_enabled),
                'delivery_fee' => round((float) str_replace(',', '.', (string) data_get($data, 'delivery.fee', $business->delivery_fee)), 2),
                'delivery_radius' => data_get($data, 'delivery.radius', $business->delivery_radius),
                'delivery_by' => data_get($data, 'delivery.by', $business->delivery_by),
            ]);
        });

        // Neoverený podnik si ponuku môže pripraviť, ale študenti ju ešte nevidia (Business::visibleToStudents) – nesľubujeme zverejnenie.
        return back()->with('toast', $business->isApproved()
            ? 'Ponuka zverejnená · '.$published.' porcií'
            : 'Ponuka uložená · '.$published.' porcií. Študenti ju uvidia až po overení podniku.');
    }
}
