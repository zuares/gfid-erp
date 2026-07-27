<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Store;
use App\Services\MarketplaceSyncService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SyncHistoricalOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopee:sync-historical-orders
        {year=2022 : Tahun target untuk mundur ke belakang (contoh: 2022)}
        {--store=all : ID spesifik dari Store}
        {--from= : (Opsional) tanggal mulai mundur, format Y-m-d. Default: hari ini. Berguna untuk melanjutkan backfill yang terputus.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menarik histori order masa lalu dari Shopee per 14 hari (berdasarkan create_time) sampai tahun target tercapai.';

    /**
     * Execute the console command.
     *
     * Loop berjendela + lock per-jendela TIDAK diimplementasikan di sini —
     * memakai MarketplaceSyncService::syncOrdersWindowed() yang juga dipakai
     * marketplace:sync-orders, supaya tidak ada dua implementasi yang sama.
     */
    public function handle(MarketplaceSyncService $syncService)
    {
        $targetYear = (int) $this->argument('year');
        $storeId = $this->option('store');

        $query = Store::whereHas('channel', function ($q) {
            $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']);
        })->where('status', 'active')
            ->where('is_active', true); // toko nonaktif dilewati

        if ($storeId !== 'all') {
            $query->where('id', $storeId);
        }

        $stores = $query->get();

        if ($stores->isEmpty()) {
            $this->error("Tidak ada toko Shopee yang ditemukan atau aktif.");
            return self::FAILURE;
        }

        $targetDate = Carbon::create($targetYear, 1, 1, 0, 0, 0)->timestamp;

        // Titik mulai mundur: hari ini, atau --from=Y-m-d untuk melanjutkan backfill terputus.
        $startPoint = time();
        if ($this->option('from')) {
            try {
                $startPoint = Carbon::createFromFormat('Y-m-d', $this->option('from'))->endOfDay()->timestamp;
            } catch (\Throwable $e) {
                $this->error('Format --from tidak valid. Gunakan Y-m-d, contoh: --from=2025-06-30');
                return self::FAILURE;
            }
        }

        if ($startPoint <= $targetDate) {
            $this->error('Titik mulai lebih lama dari tahun target — tidak ada yang perlu ditarik.');
            return self::FAILURE;
        }

        foreach ($stores as $store) {
            $this->info("Memulai sinkronisasi histori Order untuk toko: {$store->name} (ID: {$store->id}) menuju tahun {$targetYear}");

            // Refresh token bila kedaluwarsa lewat helper terpusat di service
            // (dulu blok refresh yang sama terduplikasi di command ini).
            if (! $syncService->ensureStoreConnected($store)) {
                $this->warn("Toko {$store->name} perlu login ulang / tidak terhubung. Dilewati.");
                continue;
            }

            $totals = $syncService->syncOrdersWindowed(
                $store,
                $targetDate,
                $startPoint,
                50,
                'create_time', // deterministik per tanggal order dibuat — tepat untuk backfill
                true,          // terbaru duluan: data yang paling relevan masuk lebih awal
                function (int $i, int $total, int $wFrom, int $wTo, ?array $result, ?\Throwable $error) {
                    $range = date('Y-m-d', $wFrom) . ' s/d ' . date('Y-m-d', $wTo);
                    if ($error instanceof \Illuminate\Contracts\Cache\LockTimeoutException) {
                        $this->warn("[{$i}/{$total}] {$range}: dilewati — toko sedang disinkronkan proses lain.");
                    } elseif ($error) {
                        $this->error("[{$i}/{$total}] {$range}: gagal — " . $error->getMessage());
                    } else {
                        $this->info("[{$i}/{$total}] {$range}: " . ($result['new'] ?? 0) . " baru, " . ($result['updated'] ?? 0) . " diperbarui.");
                    }
                }
            );

            $summary = "✅ Selesai histori toko {$store->name} sampai tahun {$targetYear}: {$totals['new']} baru, {$totals['updated']} diperbarui";
            if ($totals['skipped_locked'] > 0) {
                $summary .= ", {$totals['skipped_locked']} jendela dilewati (lock — ulangi dengan --from untuk menambal)";
            }
            if ($totals['failed'] > 0) {
                $summary .= ", {$totals['failed']} jendela gagal";
            }
            $this->info($summary . '.');
        }

        $this->info("Semua toko selesai diproses!");
        return self::SUCCESS;
    }
}
