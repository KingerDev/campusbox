<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Zmaže ukážkové podniky po vypršaní aj s ich dočasnými účtami.
 */
class PruneDemos extends Command
{
    protected $signature = 'campusbox:prune-demos';

    protected $description = 'Zmaže ukážkové podniky návštevníkov, ktorým vypršala platnosť';

    public function handle(): int
    {
        $count = 0;
        Business::where('is_demo', true)->where('demo_expires_at', '<', now())->get()->each(function (Business $business) use (&$count) {
            $userIds = $business->members()->whereNotNull('user_id')->pluck('user_id');
            Storage::disk('public')->deleteDirectory('businesses/'.$business->id);
            $business->delete();
            User::whereIn('id', $userIds)->where('is_demo', true)->delete();
            $count++;
        });

        // Dočasné účty, ktoré zostali bez podniku.
        User::where('is_demo', true)->whereDoesntHave('memberships')->where('created_at', '<', now()->subHours(config('panel.demo_hours')))->delete();

        $this->info("Zmazané ukážkové podniky: {$count}");

        return self::SUCCESS;
    }
}
