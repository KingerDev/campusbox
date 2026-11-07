<?php

namespace App\Enums;

enum OfferType: string
{
    case Rescue = 'zachrana';
    case Menu = 'menu';

    public function label(): string
    {
        return $this === self::Rescue ? 'Záchrana jedla' : 'Študentské menu';
    }

    /** Provízia CampusBox z vydanej objednávky (pilotná prevádzka). */
    public function commissionRate(): float
    {
        return $this === self::Rescue ? 0.11 : 0.06;
    }

    /** @return list<string> */
    public function slots(): array
    {
        return $this === self::Rescue
            ? ['18:30–19:00', '19:30–20:00', '20:30–21:00']
            : ['11:00–14:00', '11:30–14:30', '12:00–15:00'];
    }
}
