<?php

namespace App\Console\Commands\Marketplace;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderSettlement;
use App\Models\Store;
use App\Services\MarketplaceSyncService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Fase 1 — sinkronisasi settlement/escrow Shopee.
 *
 * TIDAK dijadwalkan otomatis di paket ini (lihat AUDIT_FASE1_RANCANGAN_FINAL.md
 * Koreksi 1) — hanya dijalankan manual sampai backfill + UAT selesai. TIDAK membuat
 * jurnal accounting apa pun.
 *
 * CATATAN OPERASIONAL — "The MAC is invalid" saat kredensial toko gagal didekripsi
 * (ditemukan saat UAT 23 Juli 2026): biasanya disebabkan config cache BASI (mis.
 * APP_KEY ter-cache dari nilai .env lama). Sebelum menjalankan UAT lokal yang memakai
 * encrypted credentials, jalankan dulu:
 *   php artisan config:clear
 * JANGAN menjalankan config:clear otomatis dari command ini (berisiko di production,
 * bisa mengubah state konfigurasi yang sedang dipakai proses lain).
 */
class SyncSettlementsCommand extends Command
{
    /**
     * Lock per toko dipegang selama SELURUH proses --all untuk toko itu (bukan per
     * batch), supaya proses lain (mis. --resync manual untuk toko sama) tidak bisa
     * menyelinap di antara dua batch yang sedang berjalan.
     */
    private const LOCK_TTL_SECONDS = 900; // 15 menit — lihat audit pola lock TTL command lain

    /**
     * Pengaman mode --all: jangan biarkan berjalan tanpa batas.
     */
    private const ALL_MAX_BATCHES = 20;
    private const ALL_MAX_RUNTIME_SECONDS = 12 * 60; // 12 menit

    protected $signature = 'marketplace:sync-settlements
        {--store= : ID toko spesifik (stores.id)}
        {--order= : channel_order_id spesifik — sinkronkan hanya order ini}
        {--from= : Tanggal mulai (Y-m-d) — memfilter kolom ordered_at, awal hari di timezone aplikasi}
        {--to= : Tanggal akhir (Y-m-d) — memfilter kolom ordered_at, akhir hari di timezone aplikasi}
        {--limit=200 : Maks order per batch (1-500)}
        {--resync : Ambil ulang meski sudah ada settlement tersimpan (tanpa membuat histori)}
        {--all : Ulangi batch berbasis cursor id sampai tidak ada order tersisa, tidak ada kemajuan, atau batas pengaman tercapai}
        {--inspect : Tampilkan struktur field raw response (masked) — hanya valid bersama --order}';

    protected $description = 'Sinkronisasi settlement/escrow Shopee (get_escrow_detail). Tidak membuat jurnal accounting. Dijalankan manual — belum dijadwalkan.';

    public function handle(MarketplaceSyncService $syncService): int
    {
        $commandStartedAt = hrtime(true);

        // ── Validasi opsi (sebelum proses apa pun) ─────────────────────────────
        $validation = $this->validateOptions();
        if ($validation !== null) {
            $this->error($validation);
            return self::FAILURE;
        }

        $limit    = (int) $this->option('limit');
        $resync   = (bool) $this->option('resync');
        $all      = (bool) $this->option('all');
        $inspect  = (bool) $this->option('inspect');
        $orderOpt = $this->option('order');
        $storeOpt = $this->option('store');

        [$timeFrom, $timeTo, $fromTzError] = $this->resolveDateRange();
        if ($fromTzError !== null) {
            $this->error($fromTzError);
            return self::FAILURE;
        }

        // ── Resolusi order (--order) lebih dulu — termasuk deteksi ambigu lintas toko ──
        $resolvedStoreId = $storeOpt ? (int) $storeOpt : null;

        if ($orderOpt) {
            $matches = MarketplaceOrder::where('channel_order_id', $orderOpt)
                ->when($resolvedStoreId, fn ($q) => $q->where('store_id', $resolvedStoreId))
                ->get(['id', 'store_id', 'order_status', 'channel_order_id']);

            if ($matches->isEmpty()) {
                $this->error("Order '{$orderOpt}' tidak ditemukan" . ($resolvedStoreId ? " di toko #{$resolvedStoreId}." : '.'));
                return self::FAILURE;
            }

            $distinctStores = $matches->pluck('store_id')->unique();

            if (! $resolvedStoreId && $distinctStores->count() > 1) {
                $this->error(
                    "Order '{$orderOpt}' ditemukan di lebih dari satu toko (store_id: " .
                    $distinctStores->implode(', ') . "). Order ini AMBIGU — gunakan --store untuk memilih toko yang dimaksud."
                );
                return self::FAILURE;
            }

            // Sudah pasti satu toko (baik dari --store, atau karena hanya cocok di 1 toko)
            $resolvedStoreId = $distinctStores->first();

            $orderModel = $matches->first();
            $eligibleStatuses = ['COMPLETED', 'SHIPPED', 'TO_CONFIRM_RECEIVE'];
            if (! in_array($orderModel->order_status, $eligibleStatuses, true)) {
                $this->warn(
                    "Order '{$orderOpt}': status saat ini '{$orderModel->order_status}', BELUM eligible untuk settlement " .
                    '(butuh salah satu: ' . implode(', ', $eligibleStatuses) . '). Dilewati — API TIDAK dipanggil.'
                );
                return self::SUCCESS;
            }
        }

        // ── Resolusi toko yang akan diproses ────────────────────────────────────
        $storesQuery = Store::where('status', 'active')
            ->where('is_active', true)
            ->whereHas('channel', fn ($q) => $q->where('code', 'shopee'));

        if ($resolvedStoreId) {
            $storesQuery->where('id', $resolvedStoreId);
        }

        $stores = $storesQuery->get();

        if ($stores->isEmpty()) {
            if ($resolvedStoreId) {
                $this->error('Toko tidak ditemukan, tidak aktif, atau bukan Shopee.');
                return self::FAILURE;
            }
            $this->info('Tidak ada toko Shopee aktif.');
            return self::SUCCESS;
        }

        if ($all) {
            $this->warn('Mode --all aktif. Lock TTL per toko: ' . self::LOCK_TTL_SECONDS . ' detik (15 menit). ' .
                'Batas pengaman: maksimal ' . self::ALL_MAX_BATCHES . ' batch atau ' . (self::ALL_MAX_RUNTIME_SECONDS / 60) . ' menit runtime, mana yang tercapai lebih dulu.');
        }

        $totalFound = 0;
        $totalProcessed = 0;
        $totalSynced = 0;
        $totalSkipped = 0;
        $totalErrors = 0;
        $storesFailed = 0;

        foreach ($stores as $index => $store) {
            $this->info(sprintf('[%d/%d] %s', $index + 1, $stores->count(), $store->name));

            $lockKey = "sync_settlements_store_{$store->id}";
            $lock = Cache::lock($lockKey, self::LOCK_TTL_SECONDS);

            if (! $lock->get()) {
                $this->warn('Hasil: Dilewati (toko sedang disinkronkan proses lain)');
                continue;
            }

            try {
                // Preflight: paksa akses credentials SEBELUM memanggil service sama
                // sekali, supaya DecryptException (APP_KEY tidak cocok / config cache
                // basi — lihat CATATAN OPERASIONAL di docblock class ini) terdeteksi
                // di level command tanpa pernah memanggil syncSettlements().
                $store->credentials;
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                $this->reportDecryptException($store, $e);
                Log::error("[marketplace:sync-settlements] Store #{$store->id} DecryptException (preflight): " . $e->getMessage());
                $storesFailed++;
                $lock->release();
                continue;
            }

            try {
                $storeResult = $this->syncOneStore(
                    $syncService,
                    $store,
                    $timeFrom,
                    $timeTo,
                    $orderOpt,
                    $resync,
                    $limit,
                    $all,
                );

                $totalFound     += $storeResult['found'];
                $totalProcessed += $storeResult['processed'];
                $totalSynced    += $storeResult['synced'];
                $totalSkipped   += $storeResult['skipped'];
                $totalErrors    += $storeResult['errors'];

                if ($inspect && $orderOpt) {
                    $this->printInspectOutput($store, $orderOpt, $storeResult['synced'] > 0, $storeResult['last_call_meta'] ?? null);
                }

            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                // Preflight khusus: kredensial toko gagal didekripsi. Biasanya BUKAN
                // masalah kredensial itu sendiri, melainkan config cache basi (APP_KEY
                // ter-cache dari .env lama) — lihat CATATAN OPERASIONAL di docblock
                // class ini. Toko ini dilewati (berhenti di level store), toko lain
                // tetap diproses — dan pesan ke user jelas menunjukkan kemungkinan
                // penyebab & langkah perbaikannya, bukan pesan generik.
                $this->reportDecryptException($store, $e);
                Log::error("[marketplace:sync-settlements] Store #{$store->id} DecryptException: " . $e->getMessage());
                $storesFailed++;
            } catch (\Throwable $e) {
                $this->error("Hasil: Gagal ({$e->getMessage()})");
                Log::error("[marketplace:sync-settlements] Store #{$store->id} gagal: " . $e->getMessage());
                $storesFailed++;
            } finally {
                $lock->release();
            }
        }

        $durationMs = (hrtime(true) - $commandStartedAt) / 1_000_000;

        $this->line('');
        $this->info('Selesai');
        $this->line("Ditemukan : {$totalFound}");
        $this->line("Diproses  : {$totalProcessed}");
        $this->line("Synced    : {$totalSynced}");
        $this->line("Skipped   : {$totalSkipped}");
        $this->line("Errors    : {$totalErrors}");
        if ($storesFailed > 0) {
            $this->line("Toko gagal total : {$storesFailed}");
        }
        $this->line(sprintf('Durasi    : %.2f ms / %.2f detik', $durationMs, $durationMs / 1000));

        // Exit code: FAILURE kalau ada toko yang gagal total (exception), atau kalau
        // seluruh batch yang diproses berakhir error tanpa satu pun sukses.
        $completeFailure = $totalProcessed > 0 && $totalSynced === 0 && $totalErrors > 0;

        return ($storesFailed > 0 || $completeFailure) ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Jalankan sinkronisasi untuk satu toko — satu batch, atau berulang (cursor)
     * kalau --all diberikan.
     *
     * @return array{found:int, processed:int, synced:int, skipped:int, errors:int, last_call_meta:array|null}
     */
    private function syncOneStore(
        MarketplaceSyncService $syncService,
        Store $store,
        ?int $timeFrom,
        ?int $timeTo,
        ?string $orderSn,
        bool $resync,
        int $limit,
        bool $all,
    ): array {
        $totals = ['found' => 0, 'processed' => 0, 'synced' => 0, 'skipped' => 0, 'errors' => 0, 'last_call_meta' => null];

        $afterId = 0;
        $noProgressStreak = 0;
        $startedAt = microtime(true);

        for ($batch = 1; $batch <= self::ALL_MAX_BATCHES; $batch++) {
            $result = $syncService->syncSettlements(
                store: $store,
                timeFrom: $timeFrom,
                timeTo: $timeTo,
                orderSn: $orderSn,
                resync: $resync,
                limit: $limit,
                afterId: $afterId,
            );

            $totals['found']     += $result['found'];
            $totals['processed'] += $result['processed'];
            $totals['synced']    += $result['synced'];
            $totals['skipped']   += $result['skipped'];
            $totals['errors']    += $result['errors'];
            $totals['last_call_meta'] = $result['last_call_meta'] ?? $totals['last_call_meta'];

            $this->line(sprintf(
                '  Batch %d: ditemukan=%d diproses=%d synced=%d skipped=%d errors=%d',
                $batch, $result['found'], $result['processed'], $result['synced'], $result['skipped'], $result['errors']
            ));

            if ($result['processed'] === 0) {
                break; // tidak ada order tersisa sama sekali
            }

            $madeProgress = $result['synced'] > 0 || ($result['last_processed_id'] ?? 0) > $afterId;
            $noProgressStreak = $madeProgress ? 0 : $noProgressStreak + 1;

            $afterId = max($afterId, (int) ($result['last_processed_id'] ?? $afterId));

            if (! $all) {
                break; // satu batch saja
            }

            if ($noProgressStreak >= 2) {
                $this->error(sprintf(
                    'Berhenti: %dx batch berturut-turut tanpa kemajuan (synced=0). Cursor terakhir: id=%d. Contoh order gagal: %s',
                    $noProgressStreak, $afterId, implode(', ', array_slice($result['failed_order_ids'] ?? [], 0, 5))
                ));
                break;
            }

            $elapsed = microtime(true) - $startedAt;
            if ($elapsed >= self::ALL_MAX_RUNTIME_SECONDS) {
                $this->warn(sprintf(
                    'Berhenti: batas runtime %d menit tercapai. Cursor terakhir: id=%d. Jalankan ulang command ini untuk melanjutkan (order sukses otomatis dilewati, order gagal akan dicoba ulang).',
                    self::ALL_MAX_RUNTIME_SECONDS / 60, $afterId
                ));
                break;
            }

            if ($batch === self::ALL_MAX_BATCHES) {
                $this->warn(sprintf(
                    'Berhenti: batas pengaman %d batch tercapai. Cursor terakhir: id=%d. Jalankan ulang command ini untuk melanjutkan.',
                    self::ALL_MAX_BATCHES, $afterId
                ));
            }
        }

        return $totals;
    }

    /**
     * Cetak diagnosa DecryptException secara konsisten di kedua titik tangkapnya
     * (preflight per-toko dan safety-net di sekitar syncOneStore()).
     *
     * SENGAJA TIDAK memakai $this->error() untuk seluruh pesan sekaligus: error()
     * memakai block SymfonyStyle yang melakukan word-wrap PAKSA ke lebar terminal
     * (termasuk memotong satu kata di tengah kalau lebar terminal sempit). Itu
     * pernah membuat substring "APP_KEY" terpotong jadi dua baris terpisah di
     * terminal user sehingga lolos dari mata tapi gagal di assertion test
     * (expectsOutputToContain). $this->line() TIDAK melakukan wrap paksa seperti
     * itu, jadi detail yang harus tetap utuh sebagai satu substring diletakkan di
     * sana, sementara $this->error() hanya dipakai untuk judul singkat.
     */
    private function reportDecryptException(Store $store, \Illuminate\Contracts\Encryption\DecryptException $e): void
    {
        $this->error("Hasil: Gagal dekripsi kredensial toko #{$store->id}.");
        $this->line('  Kemungkinan penyebab: APP_KEY tidak cocok atau config cache basi.');
        $this->line('  Coba: php artisan config:clear lalu ulangi.');
        $this->line('  Detail: ' . $e->getMessage());
    }

    private function validateOptions(): ?string
    {
        $limit = $this->option('limit');
        if (! is_numeric($limit) || (int) $limit < 1 || (int) $limit > 500) {
            return '--limit harus berupa angka antara 1 dan 500.';
        }

        foreach (['from', 'to'] as $opt) {
            $val = $this->option($opt);
            if ($val !== null && ! $this->isValidDateFormat($val)) {
                return "--{$opt} harus berformat Y-m-d (contoh: 2026-07-01).";
            }
        }

        $from = $this->option('from');
        $to = $this->option('to');
        if ($from && $to && $from > $to) {
            return '--from harus lebih kecil atau sama dengan --to.';
        }

        $store = $this->option('store');
        if ($store !== null && (! is_numeric($store) || (int) $store <= 0)) {
            return '--store harus berupa ID numerik yang valid.';
        }

        $order = $this->option('order');
        if ($this->hasOptionBeenPassedEmpty('order')) {
            return '--order tidak boleh string kosong.';
        }

        if ($this->option('inspect') && ! $order) {
            return '--inspect hanya valid bersama --order.';
        }

        if ($order && $this->option('all')) {
            return '--all tidak diperlukan saat --order dipakai (satu order sudah spesifik). Jalankan tanpa --all.';
        }

        return null;
    }

    private function hasOptionBeenPassedEmpty(string $name): bool
    {
        // Laravel: --order (tanpa nilai) atau --order= akan menghasilkan string kosong,
        // sedangkan opsi yang sama sekali tidak disebut menghasilkan null.
        return $this->option($name) === '';
    }

    private function isValidDateFormat(string $value): bool
    {
        $date = \DateTime::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value;
    }

    /**
     * @return array{0: int|null, 1: int|null, 2: string|null} [timeFrom, timeTo, errorMessage]
     */
    private function resolveDateRange(): array
    {
        $timezone = config('app.timezone');
        $from = $this->option('from');
        $to = $this->option('to');

        try {
            $timeFrom = $from ? Carbon::createFromFormat('Y-m-d', $from, $timezone)->startOfDay()->timestamp : null;
            $timeTo   = $to ? Carbon::createFromFormat('Y-m-d', $to, $timezone)->endOfDay()->timestamp : null;
        } catch (\Throwable $e) {
            return [null, null, 'Gagal memproses --from/--to: ' . $e->getMessage()];
        }

        return [$timeFrom, $timeTo, null];
    }

    /**
     * Tampilkan struktur field raw response yang sudah di-masking (Koreksi 15).
     * Data finansial numerik & timestamp boleh tampil apa adanya; string bebas
     * (berpotensi mengandung data pembeli) SELALU di-mask. Raw response lengkap
     * TIDAK pernah dicetak mentah ke console/log — hanya nama field, tipe, null-atau-tidak.
     */
    private function printInspectOutput(Store $store, string $orderSn, bool $wasSynced, ?array $callMeta = null): void
    {
        $this->line('');
        $this->info("--- Inspect: {$orderSn} (toko: {$store->name}) ---");

        // Metadata panggilan API ditampilkan REGARDLESS status sync — termasuk saat
        // gagal/skipped, karena justru di situ metadata ini paling berguna untuk
        // diagnosa (Tahap 8: HTTP status, attempts, retry-after, duration harus
        // tetap tampil saat error). Token TIDAK PERNAH ditampilkan.
        if ($callMeta !== null) {
            $tokenRefreshed = $callMeta['token_refreshed'] ?? null;
            $this->line('  HTTP status    : ' . ($callMeta['http_status'] ?? '(tidak diketahui)'));
            $this->line('  Attempts       : ' . ($callMeta['attempts'] ?? '(tidak diketahui)'));
            $this->line('  Retry-After    : ' . ($callMeta['retry_after'] ?? '(tidak ada)'));
            $this->line('  Duration       : ' . (array_key_exists('duration_ms', $callMeta)
                ? number_format((float) $callMeta['duration_ms'], 2) . ' ms'
                : '(tidak diketahui)'));
            // SENGAJA tidak pernah mengklaim true/false kalau memang tidak diketahui —
            // lihat catatan di MarketplaceSyncService::getEscrowDetailWithRetry().
            $this->line('  Token refreshed: ' . ($tokenRefreshed === null
                ? 'unknown/not instrumented'
                : ($tokenRefreshed ? 'true' : 'false')));
            $this->line('');
        }

        if (! $wasSynced) {
            $this->warn('Tidak ada settlement tersimpan untuk order ini (hasil sync: skipped/errors). ' .
                'Tidak ada raw_json untuk diperiksa. Lihat log aplikasi untuk detail alasan (marketplace_sync_logs / storage/logs).');
            return;
        }

        $settlement = MarketplaceOrderSettlement::where('store_id', $store->id)
            ->where('channel_order_id', $orderSn)
            ->first();

        if (! $settlement || ! is_array($settlement->raw_json)) {
            $this->warn('Settlement ditemukan tapi raw_json kosong/tidak berbentuk array.');
            return;
        }

        $timestampLikeFields = ['escrow_release_time', 'settlement_time', 'create_time', 'update_time'];
        $maxFields = 100; // batas jumlah field yang ditampilkan
        $count = 0;

        foreach ($settlement->raw_json as $field => $value) {
            if ($count >= $maxFields) {
                $this->line('  ... (dipotong, lebih dari ' . $maxFields . ' field)');
                break;
            }
            $count++;

            $type = match (true) {
                is_null($value) => 'null',
                is_numeric($value) => 'numeric',
                is_bool($value) => 'bool',
                is_array($value) => 'array',
                default => 'string',
            };

            $preview = match (true) {
                is_null($value) => 'NULL',
                in_array($field, $timestampLikeFields, true) && is_numeric($value) =>
                    $value . ' (' . date('Y-m-d H:i:s', (int) $value) . ')',
                is_numeric($value) => (string) $value,
                is_bool($value) => $value ? 'true' : 'false',
                is_array($value) => '[array, ' . count($value) . ' item — tidak dicetak mentah]',
                default => '[masked - field string, berpotensi berisi data non-finansial]',
            };

            $this->line(sprintf('  %-35s %-8s null=%-5s %s', $field, $type, is_null($value) ? 'YES' : 'NO', $preview));
        }

        $this->line('');
        $this->comment('Catatan: raw_json lengkap tersimpan di database (marketplace_order_settlements.raw_json), tidak dicetak penuh di sini untuk mencegah kebocoran data non-finansial ke console/log.');
    }
}
