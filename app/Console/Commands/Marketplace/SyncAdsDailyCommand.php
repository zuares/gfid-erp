<?php

namespace App\Console\Commands\Marketplace;

use App\Models\MarketplaceAdsBalanceLog;
use App\Models\MarketplaceAdsDaily;
use App\Models\Store;
use App\Services\Channels\ChannelManager;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncAdsDailyCommand extends Command
{
    protected $signature = 'marketplace:sync-ads-daily {--days=3 : Berapa hari ke belakang yang di-refresh}';
    protected $description = 'Simpan performa iklan harian + snapshot saldo semua toko Shopee ke DB (untuk analisa)';

    public function handle(ChannelManager $manager): int
    {
        $days = max(1, (int) $this->option('days'));
        $dateFrom = now()->subDays($days - 1)->format('d-m-Y');
        $dateTo   = now()->format('d-m-Y');

        $stores = Store::whereHas('channel', fn ($q) => $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))
            ->where('status', 'active')->get();

        $saved = 0;
        foreach ($stores as $store) {
            $driver = $manager->driver($store);

            // Snapshot saldo
            try {
                $bal = data_get($driver->getAdsTotalBalance($store), 'response.total_balance');
                if ($bal !== null) {
                    MarketplaceAdsBalanceLog::create(['store_id' => $store->id, 'balance' => $bal]);
                    $this->info("[{$store->name}] saldo: {$bal}");
                }
            } catch (\Throwable $e) {
                $this->warn("[{$store->name}] saldo gagal: " . $e->getMessage());
            }

            // Performa harian
            try {
                $res = $driver->getAdsShopDailyPerformance($store, $dateFrom, $dateTo);
                if (! empty($res['error'])) {
                    $this->warn("[{$store->name}] daily gagal: " . ($res['message'] ?? $res['error']));
                    continue;
                }

                $list = data_get($res, 'response.day_list')
                    ?? data_get($res, 'response.daily_performance')
                    ?? (is_array($res['response'] ?? null) && array_is_list($res['response']) ? $res['response'] : []);

                foreach ($list as $d) {
                    if (empty($d['date'])) continue;
                    $dateObj = Carbon::createFromFormat('d-m-Y', $d['date']);

                    $record = MarketplaceAdsDaily::where('store_id', $store->id)
                        ->whereDate('date', clone $dateObj)
                        ->first();
                        
                    $payload = [
                        'impressions' => $d['impression'] ?? $d['impressions'] ?? 0,
                        'clicks'      => $d['clicks'] ?? $d['click'] ?? 0,
                        'ctr'         => $d['ctr'] ?? null,
                        'spend'       => $d['expense'] ?? $d['spend'] ?? 0,
                        'orders'      => $d['broad_order'] ?? $d['orders'] ?? 0,
                        'gmv'         => $d['broad_gmv'] ?? $d['broad_order_amount'] ?? $d['gmv'] ?? 0,
                        'roas'        => $d['broad_roi'] ?? $d['roas'] ?? null,
                        'raw_json'    => $d,
                    ];
                    
                    if ($record) {
                        $record->update($payload);
                    } else {
                        MarketplaceAdsDaily::create(array_merge([
                            'store_id' => $store->id, 
                            'date' => $dateObj->format('Y-m-d')
                        ], $payload));
                    }
                    $saved++;
                }
            } catch (\Throwable $e) {
                $this->warn("[{$store->name}] daily exception: " . $e->getMessage());
            }
        }

        $this->info("Selesai. {$saved} baris harian tersimpan dari {$stores->count()} toko.");
        return self::SUCCESS;
    }
}
