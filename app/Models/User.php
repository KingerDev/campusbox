<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\BusinessRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_last_step',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            'is_demo' => 'boolean',
            'is_admin' => 'boolean',
        ];
    }

    /** Dvojfaktorové overenie je zapnuté až po potvrdení prvým kódom z appky. */
    public function hasTwoFactor(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(BusinessMember::class)->whereNotNull('joined_at');
    }

    /**
     * Názvy skutočných podnikov, ktorých je používateľ majiteľom. Kým nejaký má, účet si zmazať nemôže –
     * podnik by ostal bez majiteľa (objednávky, výplaty a fakturačné údaje by nemal kto spravovať).
     *
     * @return list<string>
     */
    public function ownedBusinessNames(): array
    {
        return $this->memberships()->where('role', BusinessRole::Owner)
            ->whereHas('business', fn ($q) => $q->where('is_demo', false))
            ->with('business')->get()->pluck('business.name')->all();
    }
}
