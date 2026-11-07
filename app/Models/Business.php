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
