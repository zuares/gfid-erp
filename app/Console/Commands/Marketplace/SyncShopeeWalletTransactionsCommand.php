<?php

namespace App\Console\Commands\Marketplace;

use App\Jobs\SyncShopeeWalletTransactionsJob;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SyncShopeeWalletTransactionsCommand extends Command
{
    protected $signature = 'marketplace:sync-shopee-wallet-transactions
        {--days=3 : Jumlah hari ke belakang}
        {--from= : Tanggal mulai Y-m-d, mengalahkan --days}
        {--to= : Tanggal akhir Y-m-d, mengalahkan --days}
        {--store_id=all : ID toko Shopee atau all}';

    protected $description = 'Queue sinkronisasi mutasi wallet Shopee ke database lokal.';

    public function handle(): int
    {
        [$from, $to] = $this->dateWindow();
        if (! $from || ! $to || $from->greaterThan($to)) {
            $this->error('Rentang tanggal tidak valid. Gunakan format Y-m-d.');

            return self::INVALID;
        }
        if ($from->diffInDays($to) > 60) {
            $this->error('Rentang maksimal 60 hari per sync.');

            return self::INVALID;
        }

        $storeOption = (string) $this->option('store_id');
        $stores = Store::query()
            ->with('channel')
            ->where('status', 'active')
            ->where('is_active', true)
            ->whereHas('channel', fn ($query) => $query->whereIn('code', ['shopee', 'shp', 'SHOPEE']))
            ->when($storeOption !== 'all', fn ($query) => $query->whereKey((int) $storeOption))
            ->orderBy('id')
            ->get();

        if ($stores->isEmpty()) {
            $this->warn('Tidak ada toko Shopee aktif yang cocok.');

            return self::SUCCESS;
        }

        $queued = 0;
        $skipped = 0;
        foreach ($stores as $store) {
            $activeKey = SyncShopeeWalletTransactionsJob::activeKey((int) $store->id);
            if (! Cache::add($activeKey, [
                'store_id' => $store->id,
                'store_name' => $store->name,
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
                'queued_at' => now()->toISOString(),
            ], 3600)) {
                $skipped++;
                $this->line("Dilewati (sudah berjalan): {$store->name} (#{$store->id})");

                continue;
            }

            Cache::put(SyncShopeeWalletTransactionsJob::progressKey((int) $store->id), [
                'status' => 'queued',
                'phase' => 'queued',
                'percent' => 2,
                'label' => "Sinkronisasi mutasi wallet Shopee {$store->name} masuk antrean…",
                'store_id' => $store->id,
                'store_name' => $store->name,
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
                'updated_at' => now()->toISOString(),
            ], 3600);

            try {
                SyncShopeeWalletTransactionsJob::dispatch(
                    (int) $store->id,
                    $from->toDateString(),
                    $to->toDateString(),
                    'scheduler',
                );
                $queued++;
                $this->info("Diantrekan: {$store->name} (#{$store->id})");
            } catch (\Throwable $e) {
                Cache::forget($activeKey);
                $this->error("Gagal mengantrikan {$store->name}: {$e->getMessage()}");
            }
        }

        $this->line("SUMMARY queued={$queued} skipped={$skipped}");

        return self::SUCCESS;
    }

    /** @return array{0:?Carbon,1:?Carbon} */
    private function dateWindow(): array
    {
        try {
            $fromOption = $this->option('from');
            $toOption = $this->option('to');
            if ($fromOption || $toOption) {
                if (! $fromOption || ! $toOption) {
                    return [null, null];
                }

                return [
                    Carbon::createFromFormat('!Y-m-d', (string) $fromOption, config('app.timezone'))->startOfDay(),
                    Carbon::createFromFormat('!Y-m-d', (string) $toOption, config('app.timezone'))->endOfDay(),
                ];
            }

            $days = (int) $this->option('days');
            if ($days < 1 || $days > 60) {
                return [null, null];
            }

            return [
                now(config('app.timezone'))->subDays($days - 1)->startOfDay(),
                now(config('app.timezone'))->endOfDay(),
            ];
        } catch (\Throwable) {
            return [null, null];
        }
    }
}
