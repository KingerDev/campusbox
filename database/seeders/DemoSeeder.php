<?php

namespace Database\Seeders;

use App\Enums\BusinessRole;
use App\Models\User;
use App\Support\DemoBusinessBuilder;
use Illuminate\Database\Seeder;

/**
 * Ukážkový podnik Bistro Pod Urpínom pre lokálny vývoj a testy (dáta stavia DemoBusinessBuilder).
 * Prihlásenie: peter@podurpinom.sk (majiteľ), jana@podurpinom.sk (manažér), samo@podurpinom.sk (pracovník), heslo „password“.
 */
class DemoSeeder extends Seeder
{
    public function run(DemoBusinessBuilder $builder): void
    {
        $people = array_map(
            fn (array $p) => [User::firstOrCreate(['email' => $p[1]], ['name' => $p[0], 'password' => 'password', 'email_verified_at' => now()]), $p[2]],
            [
                ['Peter Urban', 'peter@podurpinom.sk', BusinessRole::Owner],
                ['Jana Kmeťová', 'jana@podurpinom.sk', BusinessRole::Manager],
                ['Samo Lukáč', 'samo@podurpinom.sk', BusinessRole::Worker],
            ],
        );

        $builder->build($people, 'ivana.simkova@gmail.com');
    }
}
