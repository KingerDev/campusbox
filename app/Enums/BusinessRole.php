<?php

namespace App\Enums;

/**
 * Rola člena tímu podniku. Oprávnenia zodpovedajú matici z dizajnu panelu.
 */
enum BusinessRole: string
{
    case Owner = 'majitel';
    case Manager = 'manazer';
    case Worker = 'pracovnik';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Majiteľ',
            self::Manager => 'Manažér',
            self::Worker => 'Pracovník',
        };
    }

    /**
     * Schopnosti: offer (dnešná ponuka), menu (jedlá), money (sumy a tržby dňa),
     * payouts (výplaty a štatistiky), team (tím), profile (verejný profil), billing (IBAN, IČO).
     */
    public function can(string $ability): bool
    {
        return in_array($ability, match ($this) {
            self::Owner => ['offer', 'menu', 'money', 'payouts', 'team', 'profile', 'billing'],
            self::Manager => ['offer', 'menu', 'money', 'profile'],
            self::Worker => [],
        }, true);
    }

    /** @return list<string> */
    public function abilities(): array
    {
        return array_values(array_filter(
            ['offer', 'menu', 'money', 'payouts', 'team', 'profile', 'billing'],
            fn (string $a) => $this->can($a),
        ));
    }
}
