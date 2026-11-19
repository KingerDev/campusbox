<?php

namespace App\Support;

/**
 * Telefónne číslo v jednotnom tvare „+421 903 123 456“ (rovnaké pravidlá ako formatPhone vo fronte).
 */
class Phone
{
    /** Platné číslo: Slovensko +421 a 9 číslic, inak 8–15 číslic s predvoľbou. */
    public const RULE = 'regex:/^(\+421( \d{3}){3}|\+(?!421)\d{1,3}( \d{1,3}){2,4})$/';

    public static function normalize(?string $raw): string
    {
        $raw = trim((string) $raw);
        $digits = preg_replace('/\D/', '', $raw);
        if ($digits === '') {
            return '';
        }

        if (! str_starts_with($raw, '+')) {
            if (str_starts_with($digits, '00')) {
                $digits = substr($digits, 2);
            } elseif (str_starts_with($digits, '0')) {
                $digits = '421'.substr($digits, 1);
            } elseif (! str_starts_with($digits, '421')) {
                $digits = '421'.$digits;
            }
        }

        $digits = substr($digits, 0, 15);

        return '+'.substr($digits, 0, 3).rtrim(' '.implode(' ', str_split(substr($digits, 3), 3)));
    }
}
