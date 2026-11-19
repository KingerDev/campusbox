<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nevydané objednávky sa po konci okna výdaja automaticky zrušia (na serveri treba cron so `schedule:run`).
Schedule::command('campusbox:expire-orders')->everyFiveMinutes()->withoutOverlapping();

// Ukážkové podniky návštevníkov sa po vypršaní zmažú aj s dočasnými účtami.
Schedule::command('campusbox:prune-demos')->hourly()->withoutOverlapping();
