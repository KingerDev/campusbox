<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Dish extends Model
{
    public const CATEGORIES = ['Teplé jedlo', 'Polievka', 'Pečivo', 'Dezert', 'Šalát', 'Pizza', 'Nápoj'];

    public const ALLERGENS = ['lepok', 'vajcia', 'mlieko', 'orechy', 'arašidy', 'sója', 'zeler', 'horčica', 'sezam', 'ryby'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'allergens' => 'array',
            'option_groups' => 'array',
            'vegetarian' => 'boolean',
            'popular' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(MenuSection::class, 'menu_section_id');
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }
}
