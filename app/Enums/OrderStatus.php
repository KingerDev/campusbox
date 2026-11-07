<?php

namespace App\Enums;

enum OrderStatus: string
{
    case New = 'nova';            // čaká na potvrdenie podnikom
    case Preparing = 'prip';      // potvrdené, pripravuje sa
    case Ready = 'hot';           // pripravené na odovzdanie (pri donáške čaká na kuriéra)
    case OnTheWay = 'cesta';      // odovzdané kuriérovi
    case Issued = 'vyd';          // vydané / doručené
    case Cancelled = 'zrus';      // zrušené študentom alebo automaticky
    case Reported = 'nahl';       // študent nahlásil problém
    case Rejected = 'odm';        // odmietnuté podnikom

    public function label(): string
    {
        return match ($this) {
            self::New => 'Na potvrdenie',
            self::Preparing => 'Pripravuje sa',
            self::Ready => 'Pripravené',
            self::OnTheWay => 'Na ceste',
            self::Issued => 'Vydané',
            self::Cancelled => 'Zrušené',
            self::Reported => 'Nahlásený problém',
            self::Rejected => 'Odmietnuté',
        };
    }

    /** Objednávka, ktorá sa ešte rieši (nie je vydaná ani stornovaná). */
    public function isActive(): bool
    {
        return in_array($this, [self::New, self::Preparing, self::Ready, self::OnTheWay], true);
    }

    /** Objednávka, ktorá sa započítava do rezervovaných porcií. */
    public function isLive(): bool
    {
        return ! in_array($this, [self::Cancelled, self::Reported, self::Rejected], true);
    }

    /** @return list<self> */
    public static function live(): array
    {
        return array_values(array_filter(self::cases(), fn (self $s) => $s->isLive()));
    }
}
