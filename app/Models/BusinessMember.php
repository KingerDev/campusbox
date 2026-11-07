<?php

namespace App\Models;

use App\Enums\BusinessRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessMember extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'role' => BusinessRole::class,
            'joined_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->joined_at === null;
    }
}
