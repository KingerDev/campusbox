<?php

namespace App\Models;

use App\Enums\BusinessRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Business extends Model
{
    public const TYPES = ['Bistro', 'Reštaurácia', 'Pekáreň', 'Kaviareň', 'Cukráreň', 'Pizzeria', 'Ázijská kuchyňa', 'Vegetariánska', 'Potraviny'];

    public const CATEGORIES = ['Teplé jedlá', 'Polievky', 'Pečivo', 'Dezerty', 'Šaláty', 'Pizza', 'Sendviče', 'Káva'];

    public const DAYS = ['Pondelok', 'Utorok', 'Streda', 'Štvrtok', 'Piatok', 'Sobota', 'Nedeľa'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'types' => 'array',
            'categories' => 'array',
            'opening_hours' => 'array',
            'delivery_enabled' => 'boolean',
            'delivery_fee' => 'decimal:2',
            'latitude' => 'float',
            'longitude' => 'float',
            'approved_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'issue_tried_at' => 'datetime',
            'onboarding_dismissed_at' => 'datetime',
        ];
    }

    /** @return list<array{0:string,1:string,2:string,3:bool}> */
    public static function defaultHours(): array
    {
        return [
            ['Pondelok', '10:00', '21:00', false], ['Utorok', '10:00', '21:00', false], ['Streda', '10:00', '21:00', false],
            ['Štvrtok', '10:00', '21:00', false], ['Piatok', '10:00', '22:00', false], ['Sobota', '11:00', '20:00', false],
            ['Nedeľa', '', '', true],
        ];
    }

    /** Validácia otváracích hodín: 7 riadkov [deň, od, do, zatvorené]. */
    public static function hoursRules(): array
    {
        $time = ['nullable', 'regex:/^([01]?\d|2[0-3]):[0-5]\d$/'];

        return [
            'opening_hours' => ['required', 'array', 'size:7'],
            'opening_hours.*.1' => $time,
            'opening_hours.*.2' => $time,
            'opening_hours.*.3' => ['boolean'],
        ];
    }

    /** Poloha podniku na mape (pilot: Slovensko). */
    public static function locationRules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:47.7,49.7'],
            'longitude' => ['required', 'numeric', 'between:16.8,22.6'],
        ];
    }

    public static function locationMessages(): array
    {
        return [
            'latitude.required' => 'Označte podnik na mape – kliknite na miesto alebo potiahnite špendlík.',
            'longitude.required' => 'Označte podnik na mape – kliknite na miesto alebo potiahnite špendlík.',
            'latitude.between' => 'Poloha podniku musí byť na Slovensku.',
            'longitude.between' => 'Poloha podniku musí byť na Slovensku.',
            'opening_hours.*.1.regex' => 'Čas otvorenia zadajte vo formáte 10:00.',
            'opening_hours.*.2.regex' => 'Čas zatvorenia zadajte vo formáte 21:00.',
        ];
    }

    /** Doplní názvy dní a pretypuje riadky hodín. */
    public static function normalizeHours(array $hours): array
    {
        return array_map(
            fn (array $h, int $i) => [self::DAYS[$i], (string) ($h[1] ?? ''), (string) ($h[2] ?? ''), (bool) ($h[3] ?? false)],
            array_values($hours),
            array_keys(array_values($hours)),
        );
    }

    public function members(): HasMany
    {
        return $this->hasMany(BusinessMember::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(MenuSection::class)->orderBy('position');
    }

    public function dishes(): HasMany
    {
        return $this->hasMany(Dish::class);
    }

    public function offerItems(): HasMany
    {
        return $this->hasMany(OfferItem::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function owner(): ?BusinessMember
    {
        return $this->members()->where('role', BusinessRole::Owner->value)->first();
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    /** Otváracie hodiny pre dnešný deň. */
    public function todayHours(): array
    {
        $hours = $this->opening_hours ?: self::defaultHours();

        return $hours[now()->dayOfWeekIso - 1] ?? ['', '', '', true];
    }
}
