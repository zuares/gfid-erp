<?php

namespace App\Console\Commands\Marketplace;

use App\Models\Store;
use App\Services\MarketplaceSyncService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncReleasedSettlementTimesCommand extends Command
{
    protected $signature = 'marketplace:sync-released-settlements
        {--store= : ID toko spesifik}
        {--from= : Awal rentang release time (Y-m-d)}
        {--to= : Akhir rentang release time (Y-m-d)}
        {--limit=100 : Jumlah record per halaman (1-100)}';

    protected $description = 'Mengisi tanggal dana cair dari endpoint Shopee GetEscrowReleasedOrders.';

    public function handle(MarketplaceSyncService $syncService): int
    {
        $limit = (int) $this->option('limit');
        if ($limit < 1 || $limit > 100) {
            $this->error('--limit harus berupa angka antara 1 dan 100.');
            return self::FAILURE;
        }

        [$from, $to, $dateError] = $this->resolveDateRange();
        if ($dateError !== null) {
            $this->error($dateError);
            return self::FAILURE;
        }

        $storeOption = $this->option('store');
        $stores = Store::query()
            ->where('status', 'active')
            ->where('is_active', true)
            ->whereHas('channel', fn ($q) => $q->whereIn('code', ['shopee', 'shp']))
            ->when($storeOption !== null, fn ($q) => $q->where('id', (int) $storeOption))
            ->get();

        if ($stores->isEmpty()) {
            $this->warn('Tidak ada toko Shopee aktif yang perlu diproses.');
            return $storeOption !== null ? self::FAILURE : self::SUCCESS;
        }

        $this->info(sprintf(
            'Sync release time %s sampai %s untuk %d toko.',
            Carbon::createFromTimestamp($from, config('app.timezone'))->toDateString(),
            Carbon::createFromTimestamp($to, config('app.timezone'))->toDateString(),
            $stores->count()
        ));

        $totalFound = 0;
        $totalUpdated = 0;
        $totalUnmatched = 0;
        $totalUnsupported = 0;
        $totalErrors = 0;

        foreach ($stores as $store) {
            $lock = Cache::lock(
                "sync_settlements_store_{$store->id}",
                (int) config('marketplace.settlement_lock_ttl', 3600)
            );

            if (! $lock->get()) {
                $this->warn("{$store->name}: dilewati karena settlement sedang berjalan.");
                continue;
            }

            try {
                try {
                    // Preflight dekripsi supaya satu toko rusak tidak menghentikan
                    // toko lain dan error yang tampil tetap actionable.
                    $store->credentials;
                } catch (DecryptException $e) {
                    $totalErrors++;
                    $this->warn("{$store->name}: kredensial tidak dapat didekripsi, dilewati.");
                    Log::warning('[sync-released-settlements] credential decrypt failed', [
                        'store_id' => $store->id,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }

                $result = $syncService->syncReleasedSettlementTimes($store, $from, $to, $limit);
                $totalFound += (int) ($result['found'] ?? 0);
                $totalUpdated += (int) ($result['updated'] ?? 0);
                $totalUnmatched += (int) ($result['unmatched'] ?? 0);
                $totalUnsupported += (int) ($result['unsupported'] ?? 0);
                $totalErrors += (int) ($result['errors'] ?? 0);

                $this->line(sprintf(
                    '%s: ditemukan=%d updated=%d tidak_terhubung=%d unsupported=%d errors=%d%s',
                    $store->name,
                    $result['found'] ?? 0,
                    $result['updated'] ?? 0,
                    $result['unmatched'] ?? 0,
                    $result['unsupported'] ?? 0,
                    $result['errors'] ?? 0,
                    ($result['message'] ?? '') !== '' ? ' · ' . $result['message'] : '',
                ));
            } catch (\Throwable $e) {
                // Enrichment tidak boleh menghentikan toko berikutnya maupun
                // membuat settlement nominal yang sudah sukses menjadi rollback.
                $totalErrors++;
                $this->warn("{$store->name}: enrichment gagal sementara, proses lanjut.");
                Log::warning('[sync-released-settlements] store failed', [
                    'store_id' => $store->id,
                    'error' => $e->getMessage(),
                ]);
            } finally {
                $lock->release();
            }
        }

        $this->line("Ditemukan : {$totalFound}");
        $this->line("Updated   : {$totalUpdated}");
        $this->line("Unmatched : {$totalUnmatched}");
        $this->line("Unsupported: {$totalUnsupported}");
        $this->line("Errors    : {$totalErrors}");
        $this->info('Selesai. Error enrichment dicatat dan tidak menghentikan sync settlement utama.');

        // Command enrichment selalu menyelesaikan seluruh toko yang bisa diakses;
        // error dicatat untuk observability, tetapi tidak membuat scheduler
        // menghentikan rangkaian sync marketplace berikutnya.
        return self::SUCCESS;
    }

    /** @return array{0:int,1:int,2:string|null} */
    private function resolveDateRange(): array
    {
        $fromOption = $this->option('from');
        $toOption = $this->option('to');

        if (($fromOption === null) !== ($toOption === null)) {
            return [0, 0, '--from dan --to harus diisi berpasangan.'];
        }

        if ($fromOption === null && $toOption === null) {
            return [
                now()->subDays((int) config('shopee.release_time_lookback_days', 45))->startOfDay()->timestamp,
                now()->timestamp,
                null,
            ];
        }

        try {
            $timezone = config('app.timezone');
            $from = Carbon::createFromFormat('!Y-m-d', (string) $fromOption, $timezone);
            $to = Carbon::createFromFormat('!Y-m-d', (string) $toOption, $timezone);
        } catch (\Throwable) {
            return [0, 0, 'Format tanggal harus Y-m-d.'];
        }

        if (! $from || ! $to
            || $from->format('Y-m-d') !== (string) $fromOption
            || $to->format('Y-m-d') !== (string) $toOption) {
            return [0, 0, 'Format tanggal harus Y-m-d.'];
        }

        if ($from->greaterThan($to)) {
            return [0, 0, '--from tidak boleh lebih besar dari --to.'];
        }

        $toTimestamp = $to->isToday() ? now()->timestamp : $to->endOfDay()->timestamp;

        return [$from->startOfDay()->timestamp, $toTimestamp, null];
    }
}
