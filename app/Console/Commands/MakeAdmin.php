<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Správca platformy sa nedá vytvoriť cez web – len týmto príkazom na serveri.
 */
class MakeAdmin extends Command
{
    protected $signature = 'campusbox:make-admin {email : E‑mail účtu} {--revoke : Odobrať práva správcu}';

    protected $description = 'Urobí z účtu správcu platformy (prístup do /admin); ak účet neexistuje, vytvorí ho';

    public function handle(): int
    {
        $email = Str::lower(trim($this->argument('email')));
        $user = User::where('email', $email)->first();

        if ($this->option('revoke')) {
            $user?->forceFill(['is_admin' => false])->save();
            $this->info($user ? "Účet {$email} už nie je správca." : "Účet {$email} neexistuje.");

            return self::SUCCESS;
        }

        if ($user?->is_demo) {
            $this->error('Dočasný účet ukážkového podniku nemôže byť správca.');

            return self::FAILURE;
        }

        if (! $user) {
            $name = $this->ask('Účet neexistuje, vytvoríme ho. Meno a priezvisko');
            $password = $this->secret('Heslo (aspoň 12 znakov)');
            if (! $name || Str::length((string) $password) < 12) {
                $this->error('Meno je povinné a heslo musí mať aspoň 12 znakov.');

                return self::FAILURE;
            }
            $user = User::create(['name' => $name, 'email' => $email, 'password' => $password]);
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $user->forceFill(['is_admin' => true])->save();
        $this->info("Účet {$email} je správca. Administrácia je na /admin; odporúčame hneď zapnúť dvojfaktorové overenie.");

        return self::SUCCESS;
    }
}
