<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
class TestKpi extends Command {
    protected $signature = 'test:kpi';
    public function handle() {
        $s = \App\Models\Store::find(5);
        $run = \App\Models\MarketplaceAdsSyncRun::create([
            'store_id' => $s->id,
            'status' => 'running',
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-01',
            'sync_type' => 'manual'
        ]);
        $sync = app(\App\Services\Marketplace\Ads\ShopeeAdsSyncService::class);
        $sync->syncShopHourlyPerformance($s, '2026-08-01', $run);
        echo "Hourly Sync Completed. Records count: " . \App\Models\MarketplaceAdsHourlyPerformance::count() . "\n";
    }
}
