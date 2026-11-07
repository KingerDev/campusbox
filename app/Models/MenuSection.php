<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuSection extends Model
{
    protected $guarded = ['id'];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function dishes(): HasMany
    {
        return $this->hasMany(Dish::class)->orderBy('position');
    }
}
