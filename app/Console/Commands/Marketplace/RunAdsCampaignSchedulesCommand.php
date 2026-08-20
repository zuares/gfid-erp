<?php

namespace App\Console\Commands\Marketplace;

use App\Jobs\ExecuteAdsCampaignSchedule;
use App\Models\AdsCampaignSchedule;
use Illuminate\Console\Command;

class RunAdsCampaignSchedulesCommand extends Command
{
    protected $signature = 'marketplace:run-ads-campaign-schedules';

    protected $description = 'Antrekan aksi pause/resume campaign Ads yang sudah jatuh tempo';

    public function handle(): int
    {
        AdsCampaignSchedule::query()
            ->where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->limit(50)
            ->get()
            ->each(function (AdsCampaignSchedule $schedule): void {
                $claimed = AdsCampaignSchedule::query()
                    ->whereKey($schedule->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'queued']);

                if ($claimed === 1) {
                    ExecuteAdsCampaignSchedule::dispatch($schedule->id)->onQueue('ads');
                }
            });

        return self::SUCCESS;
    }
}
