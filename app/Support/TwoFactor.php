<?php

namespace App\Support;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Dvojfaktorové overenie: kód z autentifikačnej appky (TOTP) a jednorazové záložné kódy.
 */
class TwoFactor
{
    public function __construct(private readonly Google2FA $engine = new Google2FA) {}

    /** Vygeneruje nový tajný kľúč; overenie platí až po potvrdení prvým kódom. */
    public function begin(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => $this->engine->generateSecretKey(32),
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_step' => null,
        ])->save();
    }

    /**
     * Potvrdí nastavenie prvým kódom z appky a vráti záložné kódy.
     *
     * @return list<string>|null
     */
    public function confirm(User $user, string $code): ?array
    {
        if (! $user->two_factor_secret || ! $this->verify($user, $code)) {
            return null;
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $this->regenerateRecoveryCodes($user);
    }

    /** Overí 6‑miestny kód. Ten istý kód sa nedá použiť dvakrát. */
    public function verify(User $user, string $code): bool
    {
        $code = preg_replace('/\D/', '', $code);
        if (! $user->two_factor_secret || strlen($code) !== 6) {
            return false;
        }

        $step = $this->engine->verifyKeyNewer($user->two_factor_secret, $code, $user->two_factor_last_step ?? 0);
        if ($step === false) {
            return false;
        }

        $user->forceFill(['two_factor_last_step' => $step])->save();

        return true;
    }

    /** Použije záložný kód; každý platí len raz. */
    public function useRecoveryCode(User $user, string $code): bool
    {
        $code = Str::lower(trim($code));
        $codes = $user->two_factor_recovery_codes ?? [];
        $match = null;
        foreach ($codes as $candidate) {
            if (hash_equals($candidate, $code)) {
                $match = $candidate;
            }
        }
        if ($match === null) {
            return false;
        }

        $user->forceFill(['two_factor_recovery_codes' => array_values(array_diff($codes, [$match]))])->save();

        return true;
    }

    /** @return list<string> */
    public function regenerateRecoveryCodes(User $user): array
    {
        $codes = array_map(fn () => Str::lower(Str::random(5).'-'.Str::random(5)), range(1, 8));
        $user->forceFill(['two_factor_recovery_codes' => $codes])->save();

        return $codes;
    }

    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_step' => null,
        ])->save();
    }

    /** QR kód pre autentifikačnú appku ako vložené SVG. */
    public function qrSvg(User $user): string
    {
        $url = $this->engine->getQRCodeUrl(config('app.name'), $user->email, $user->two_factor_secret);
        $svg = (new Writer(new ImageRenderer(new RendererStyle(200, 1), new SvgImageBackEnd)))->writeString($url);

        return trim(substr($svg, strpos($svg, "\n") + 1));
    }

    /** Aktuálny kód pre daný kľúč (na testy). */
    public function currentCode(string $secret): string
    {
        return $this->engine->getCurrentOtp($secret);
    }
}
