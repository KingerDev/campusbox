<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Dish;
use App\Models\MenuSection;
use App\Services\OfferBoard;
use App\Support\CurrentBusiness;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MenuController extends Controller
{
    public function index(CurrentBusiness $current, OfferBoard $board): Response
    {
        $business = $current->business();
        $today = $board->activeItems($business)->whereNotNull('dish_id')->groupBy('dish_id')->map->sum('quantity');

        return Inertia::render('Panel/Menu', [
            'sections' => $business->sections()->with('dishes')->get()->map(fn (MenuSection $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'dishes' => $s->dishes->map(fn (Dish $d) => [
                    'id' => $d->id,
                    'sectionId' => $d->menu_section_id,
                    'name' => $d->name,
                    'category' => $d->category,
                    'description' => $d->description,
                    'price' => (float) $d->price,
                    'allergens' => $d->allergens ?? [],
                    'groups' => $d->option_groups ?? [],
                    'vegetarian' => $d->vegetarian,
                    'popular' => $d->popular,
                    'photo' => $d->photoUrl(),
                    'ph' => OfferBoard::PLACEHOLDER[$d->category] ?? '#E3C3A0',
                    'today' => (int) ($today[$d->id] ?? 0),
                ]),
            ]),
            'categories' => Dish::CATEGORIES,
            'allergens' => Dish::ALLERGENS,
            'placeholders' => OfferBoard::PLACEHOLDER,
        ]);
    }

    public function storeSection(Request $request, CurrentBusiness $current): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:60']]);
        $business = $current->business();
        $business->sections()->create(['name' => $data['name'], 'position' => (int) $business->sections()->max('position') + 1]);

        return back()->with('toast', 'Sekcia pridaná');
    }

    public function updateSection(Request $request, CurrentBusiness $current, MenuSection $section): RedirectResponse
    {
        $this->own($current, $section->business_id);
        $section->update($request->validate(['name' => ['required', 'string', 'max:60']]));

        return back();
    }

    public function destroySection(CurrentBusiness $current, MenuSection $section): RedirectResponse
    {
        $this->own($current, $section->business_id);
        abort_if($section->dishes()->exists(), 422, 'Sekcia nie je prázdna.');
        $section->delete();

        return back()->with('toast', 'Sekcia odstránená');
    }

    /** Nové poradie sekcií aj jedál (presun jedla medzi sekciami). */
    public function reorder(Request $request, CurrentBusiness $current): RedirectResponse
    {
        $business = $current->business();
        $data = $request->validate([
            'sections' => ['required', 'array'],
            'sections.*.id' => ['required', 'integer'],
            'sections.*.dishes' => ['array'],
            'sections.*.dishes.*' => ['integer'],
        ]);

        $sectionIds = $business->sections()->pluck('id')->all();
        $dishIds = $business->dishes()->pluck('id')->all();

        DB::transaction(function () use ($data, $sectionIds, $dishIds) {
            foreach ($data['sections'] as $sPos => $s) {
                if (! in_array($s['id'], $sectionIds, true)) {
                    continue;
                }
                MenuSection::whereKey($s['id'])->update(['position' => $sPos]);
                foreach ($s['dishes'] ?? [] as $dPos => $dishId) {
                    if (in_array($dishId, $dishIds, true)) {
                        Dish::whereKey($dishId)->update(['menu_section_id' => $s['id'], 'position' => $dPos]);
                    }
                }
            }
        });

        return back();
    }

    public function storeDish(Request $request, CurrentBusiness $current): RedirectResponse
    {
        $business = $current->business();
        $data = $this->validateDish($request, $current);
        $data['position'] = (int) $business->dishes()->where('menu_section_id', $data['menu_section_id'])->max('position') + 1;
        $dish = $business->dishes()->create($data);
        $this->storePhoto($request, $dish);

        return back()->with('toast', 'Jedlo „'.$dish->name.'“ pridané');
    }

    public function updateDish(Request $request, CurrentBusiness $current, Dish $dish): RedirectResponse
    {
        $this->own($current, $dish->business_id);
        $dish->update($this->validateDish($request, $current));
        $this->storePhoto($request, $dish);

        return back()->with('toast', 'Jedlo uložené');
    }

    public function destroyDish(CurrentBusiness $current, Dish $dish): RedirectResponse
    {
        $this->own($current, $dish->business_id);
        if ($dish->photo_path) {
            Storage::disk('public')->delete($dish->photo_path);
        }
        $dish->delete();

        return back()->with('toast', 'Jedlo odstránené');
    }

    private function validateDish(Request $request, CurrentBusiness $current): array
    {
        $sectionIds = $current->business()->sections()->pluck('id')->all();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'sectionId' => ['required', Rule::in($sectionIds)],
            'category' => ['required', Rule::in(Dish::CATEGORIES)],
            'description' => ['nullable', 'string', 'max:300'],
            'price' => ['required', 'regex:/^\d{1,3}([.,]\d{1,2})?$/'],
            'vegetarian' => ['boolean'],
            'popular' => ['boolean'],
            'allergens' => ['array'],
            'allergens.*' => [Rule::in(Dish::ALLERGENS)],
            'groups' => ['array', 'max:10'],
            'groups.*.q' => ['required', 'string', 'max:120'],
            'groups.*.multi' => ['boolean'],
            'groups.*.req' => ['boolean'],
            'groups.*.max' => ['nullable', 'integer', 'min:1', 'max:20'],
            'groups.*.opts' => ['required', 'array', 'min:1', 'max:20'],
            'groups.*.opts.*.n' => ['required', 'string', 'max:80'],
            'groups.*.opts.*.p' => ['nullable', 'regex:/^\d{1,2}([.,]\d{1,2})?$/'],
            'photo' => ['nullable', 'image', 'max:4096'],
        ], [
            'name.required' => 'Vyplňte názov jedla.',
            'price.required' => 'Zadajte bežnú cenu.',
            'price.regex' => 'Cenu zadajte ako číslo, napr. 7,90.',
            'groups.*.q.required' => 'Každý výber potrebuje otázku.',
            'groups.*.opts.required' => 'Každý výber potrebuje aspoň jednu možnosť.',
            'groups.*.opts.*.n.required' => 'Vyplňte názov každej možnosti.',
            'groups.*.opts.*.p.regex' => 'Príplatok zadajte ako číslo, napr. 0,50.',
        ]);

        return [
            'menu_section_id' => $data['sectionId'],
            'name' => $data['name'],
            'category' => $data['category'],
            'description' => $data['description'] ?? null,
            'price' => round((float) str_replace(',', '.', $data['price']), 2),
            'vegetarian' => (bool) ($data['vegetarian'] ?? false),
            'popular' => (bool) ($data['popular'] ?? false),
            'allergens' => array_values($data['allergens'] ?? []),
            'option_groups' => array_map(fn ($g) => [
                'q' => $g['q'],
                'multi' => (bool) ($g['multi'] ?? false),
                'req' => (bool) ($g['req'] ?? false),
                'max' => ($g['multi'] ?? false) ? ($g['max'] ?? null) : null,
                'opts' => array_map(fn ($o) => ['n' => $o['n'], 'p' => $o['p'] ?? ''], $g['opts']),
            ], $data['groups'] ?? []),
        ];
    }

    private function storePhoto(Request $request, Dish $dish): void
    {
        if (! $request->hasFile('photo')) {
            return;
        }
        if ($dish->photo_path) {
            Storage::disk('public')->delete($dish->photo_path);
        }
        $dish->update(['photo_path' => $request->file('photo')->store('dishes/'.$dish->business_id, 'public')]);
    }

    private function own(CurrentBusiness $current, int $businessId): void
    {
        abort_unless($current->business()->id === $businessId, 404);
    }
}
