<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Services\BusinessReview;
use Illuminate\Console\Command;

class ApproveBusiness extends Command
{
    protected $signature = 'campusbox:approve {business : ID podniku}';

    protected $description = 'Schváli podnik po overení (zmizne upozornenie „čaká na overenie“)';

    public function handle(BusinessReview $review): int
    {
        $business = Business::findOrFail($this->argument('business'));
        $review->approve($business);
        $this->info("Podnik {$business->name} je schválený, majiteľ dostal e‑mail.");

        return self::SUCCESS;
    }
}
