<?php

namespace App\Console\Commands;

use App\Models\Business;
use Illuminate\Console\Command;

class ApproveBusiness extends Command
{
    protected $signature = 'campusbox:approve {business : ID podniku}';

    protected $description = 'Schváli podnik po overení (zmizne upozornenie „čaká na overenie“)';

    public function handle(): int
    {
        $business = Business::findOrFail($this->argument('business'));
        $business->update(['status' => 'approved', 'approved_at' => now()]);
        $this->info("Podnik {$business->name} je schválený.");

        return self::SUCCESS;
    }
}
