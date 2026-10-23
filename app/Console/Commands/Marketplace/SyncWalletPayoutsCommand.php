<?php

namespace App\Console\Commands\Marketplace;

use App\Models\Store;
use App\Models\MarketplaceFinanceSyncRun;
use App\Services\Marketplace\Finance\MarketplaceSettlementSyncService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SyncWalletPayoutsCommand extends Command
{
    protected $signature = 'marketplace:sync-wallet-payouts
        {--store= : ID toko Shopee spesifik}
        {--from= : Tanggal mulai (Y-m-d), default 14 hari terakhir}
        {--to= : Tanggal akhir (Y-m-d), default hari ini}
        {--page-size=100 : Jumlah data per halaman (1-100)}
        {--trigger=manual : Sumber eksekusi: manual atau schedule}';

    protected $description = 'Autosync payout withdrawal wallet Shopee ke Finance settlement tanpa membuat jurnal.';

    public function handle(MarketplaceSettlementSyncService $syncService): int
    {
        $pageSize = (int) $this->option('page-size');
        if ($pageSize < 1 || $pageSize > 100) {
            $this->error('Opsi --page-size harus di antara 1 dan 100.');

            return self::FAILURE;
        }

        try {
            $from = Carbon::createFromFormat('Y-m-d', (string) ($this->option('from') ?: now()->subDays(14)->toDateString()))->startOfDay();
            $to = Carbon::createFromFormat('Y-m-d', (string) ($this->option('to') ?: now()->toDateString()))->endOfDay();
        } catch (\Throwable) {
            $this->error('Format tanggal harus Y-m-d.');

            return self::FAILURE;
        }

        if ($to->lt($from)) {
            $this->error('Tanggal --to tidak boleh lebih kecil dari --from.');

            return self::FAILURE;
        }

        if ($from->diffInDays($to) > 14) {
            $this->error('Rentang payout maksimal 15 hari.');

            return self::FAILURE;
        }

        $trigger = strtolower(trim((string) $this->option('trigger')));
        if (! in_array($trigger, ['manual', 'schedule'], true)) {
            $this->error('Opsi --trigger harus manual atau schedule.');

            return self::FAILURE;
        }

        $stores = Store::query()
            ->where('status', 'active')
            ->where('is_active', true)
            ->whereHas('channel', fn ($query) => $query->whereIn('code', ['shopee', 'shp', 'SHOPEE']))
            ->when($this->option('store'), fn ($query, $storeId) => $query->whereKey((int) $storeId))
            ->orderBy('id')
            ->get();

        if ($stores->isEmpty()) {
            $this->info('Tidak ada toko Shopee aktif yang cocok.');

            return self::SUCCESS;
        }

        $run = MarketplaceFinanceSyncRun::create([
            'trigger' => 'wallet_payout_'.$trigger,
            'status' => 'processing',
            'started_at' => now(),
        ]);
        $audit = [
            'scope' => 'wallet_payout',
            'trigger' => $trigger,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'page_size' => $pageSize,
            'stores' => [],
        ];
        $failed = 0;
        foreach ($stores as $store) {
            if ($store->connection_status !== 'CONNECTED') {
                $this->warn("{$store->name}: dilewati karena koneksi Shopee tidak aktif.");
                $audit['stores'][] = ['store_id' => $store->id, 'status' => 'skipped', 'reason' => 'not_connected'];
                continue;
            }

            $lock = Cache::lock("marketplace:wallet_payout_sync:{$store->id}", 1800);
            if (! $lock->get()) {
                $this->warn("{$store->name}: dilewati karena sync sedang berjalan.");
                $audit['stores'][] = ['store_id' => $store->id, 'status' => 'skipped', 'reason' => 'locked'];
                continue;
            }

            try {
                $result = $syncService->syncWalletPayouts($store, $from, $to, $pageSize);
                $this->line(sprintf(
                    '%s: status=%s found=%d created=%d updated=%d invalid=%d pages=%d',
                    $store->name,
                    $result['status'] ?? 'unknown',
                    $result['found'] ?? 0,
                    $result['created'] ?? 0,
                    $result['updated'] ?? 0,
                    $result['invalid'] ?? 0,
                    $result['pages'] ?? 0,
                ));
                $audit['stores'][] = [
                    'store_id' => $store->id,
                    'status' => $result['status'] ?? 'unknown',
                    'found' => $result['found'] ?? 0,
                    'created' => $result['created'] ?? 0,
                    'updated' => $result['updated'] ?? 0,
                    'invalid' => $result['invalid'] ?? 0,
                    'pages' => $result['pages'] ?? 0,
                ];

                if (($result['status'] ?? null) !== 'synced') {
                    $failed++;
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->error("{$store->name}: {$e->getMessage()}");
                $audit['stores'][] = ['store_id' => $store->id, 'status' => 'error', 'error' => mb_substr($e->getMessage(), 0, 500)];
            } finally {
                $lock->release();
            }
        }

        $run->update([
            'status' => $failed > 0 ? 'error' : 'success',
            'error_message' => $failed > 0 ? 'Satu atau lebih toko gagal atau mengembalikan status partial.' : null,
            'output' => json_encode($audit, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'finished_at' => now(),
        ]);

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
