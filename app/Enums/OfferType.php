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
}
