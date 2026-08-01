<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\Item;
use App\Models\MarketplaceAdCampaign;
use App\Models\MarketplaceAdCampaignDaily;
use App\Models\MarketplaceAdsBalanceLog;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceOrderSettlement;
use App\Models\MarketplaceSyncLog;
use App\Models\SkuMapping;
use App\Models\Store;
use App\Services\Marketplace\MarketplaceApiGateway;
use App\Services\Channels\ChannelManager;
use App\Services\Channels\Contracts\MarketplaceChannel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarketplaceSyncService
{
    public function __construct(
        protected MarketplaceApiGateway $gateway,
        protected OrderFulfillmentService $fulfillment,
        protected MarketplaceIssueService $issueService = new MarketplaceIssueService(),
    ) {}

    /**
     * Kirim update progress ke callback jika tersedia.
     */
    private function reportProgress(?callable $progress, array $payload): void
    {
        if (! $progress) {
            return;
        }

        try {
            $progress($payload);
        } catch (\Throwable) {
            // Observabilitas tidak boleh memutus sinkronisasi inti.
        }
    }

    /**
     * Seed channel default (Shopee, TikTok, dst).
     */
    public function bootstrapChannels(): array
    {
        foreach ([
            ['code' => 'shopee',    'name' => 'Shopee'],
            ['code' => 'tiktok',    'name' => 'TikTok Shop'],
            ['code' => 'tokopedia', 'name' => 'Tokopedia'],
            ['code' => 'lazada',    'name' => 'Lazada'],
            ['code' => 'offline',   'name' => 'Offline'],
        ] as $channel) {
            Channel::firstOrCreate(
                ['code' => $channel['code']],
                ['name' => $channel['name'], 'status' => 'active']
            );
        }

        return Channel::orderBy('name')->get()->toArray();
    }

    /**
     * Pecah rentang waktu menjadi jendela <= 14 hari.
     * Shopee get_order_list membatasi rentang maksimal 15 hari per panggilan API,
     * jadi rentang panjang (30/60 hari) harus dipecah lalu digabung hasilnya.
     *
     * @return array<int, array{0:int,1:int}>
     */
    private function splitTimeWindows(int $timeFrom, int $timeTo, int $maxSpan = 1209600): array
    {
        if ($timeTo <= $timeFrom) {
            return [[$timeFrom, $timeTo]];
        }

        $windows = [];
        $start = $timeFrom;
        while ($start < $timeTo) {
            $end = min($start + $maxSpan, $timeTo);
            $windows[] = [$start, $end];
            $start = $end; // sedikit tumpang-tindih di batas aman — order_sn tetap di-dedup
        }

        return $windows;
    }

    /**
     * Sync orders dari marketplace ke DB lokal.
     * Mengembalikan array: found, synced, order_sn_list, message.
     * Melempar \RuntimeException jika API error.
     */
    public function syncOrders(Store $store, int $timeFrom, int $timeTo, int $pageSize = 50, bool $dryRun = false, ?callable $onProgress = null, string $timeRangeField = 'update_time'): array
    {
        // Pelaporan progres bersifat opsional — dipakai untuk sync latar belakang
        // agar UI bisa menampilkan persentase. Aman jika callback null / melempar.
        $report = function (int $percent, string $label) use ($onProgress) {
            if ($onProgress) {
                try { $onProgress(max(0, min(100, $percent)), $label); } catch (\Throwable $e) { /* abaikan */ }
            }
        };
        $report(3, 'Menyiapkan sinkronisasi…');

        $driver = $this->gateway;

        $orderSnList = [];
        
        // Untuk Shopee, kita tarik spesifik per status agar tidak ada order (termasuk kilat) yang terlewat
        // Catatan: TO_CONFIRM_RECEIVE bukan parameter valid untuk filter di get_order_list (akan memicu error API)
        $statuses = $store->channel?->code === 'shopee'
            ? ['UNPAID', 'READY_TO_SHIP', 'PROCESSED', 'SHIPPED', 'COMPLETED', 'IN_CANCEL', 'CANCELLED']
            : ['']; // Channel lain panggil tanpa filter status

        // Rentang panjang (mis. 30/60 hari) dipecah menjadi jendela <=14 hari
        // agar tidak ditolak oleh batas 15 hari Shopee get_order_list.
        $windows = $this->splitTimeWindows($timeFrom, $timeTo);
        $totalWindows = max(1, count($windows));
        foreach ($windows as $wIdx => [$windowFrom, $windowTo]) {
        foreach ($statuses as $status) {
            $cursor = '';
            $hasMore = true;

            // 1. Ambil daftar order per status
            while ($hasMore) {
                $listResponse = $driver->getOrders($store, $windowFrom, $windowTo, $pageSize, $cursor, $status, $timeRangeField);

                if (! empty($listResponse['error'])) {
                    $this->log($store, 'sync_orders', 'failed', $listResponse['message'] ?? $listResponse['error'], $listResponse);
                    throw new \RuntimeException($listResponse['message'] ?? 'Gagal ambil daftar order dari marketplace.');
                }

                // Extract items based on channel structure
                // Shopee uses response.order_list
                $orders = data_get($listResponse, 'response.order_list');
                if ($orders === null) {
                    // TikTok uses data.orders or similar? We'll extract order_id / order_sn
                    $orders = data_get($listResponse, 'data.orders', []);
                }
                
                $chunkSns = collect($orders)
                    ->map(fn($o) => $o['order_sn'] ?? $o['order_id'] ?? $o['id'] ?? null)
                    ->filter()->values()->all();
                
                $orderSnList = array_merge($orderSnList, $chunkSns);

                $nextCursor = data_get($listResponse, 'response.next_cursor') ?? data_get($listResponse, 'data.next_cursor');
                $hasMoreData = data_get($listResponse, 'response.more') ?? data_get($listResponse, 'data.more') ?? false;

                if ($hasMoreData && $nextCursor) {
                    $cursor = (string) $nextCursor;
                } else {
                    $hasMore = false;
                }
            }
        }
        // Fase 1 (tarik daftar order): 5% → 55% seiring jendela waktu selesai
        $report((int) round(5 + (($wIdx + 1) / $totalWindows) * 50),
            'Menarik daftar pesanan… (' . ($wIdx + 1) . '/' . $totalWindows . ' periode)');
        } // tutup loop jendela waktu

        $orderSnList = array_unique($orderSnList);

        if (empty($orderSnList)) {
            $this->log($store, 'sync_orders', 'success', 'Tidak ada order ditemukan.', ['time_from' => $timeFrom, 'time_to' => $timeTo]);
            return ['found' => 0, 'synced' => 0, 'order_sn_list' => [], 'message' => 'Tidak ada order ditemukan di rentang tanggal ini.'];
        }

        // 2. Ambil detail order per chunk
        $report(57, 'Mengambil detail pesanan…');
        $details = [];
        $failedChunks = 0;
        $detailChunks = array_chunk($orderSnList, 50);
        $totalChunks = max(1, count($detailChunks));
        foreach ($detailChunks as $ci => $chunk) {
            // Fase 2 (detail order): 57% → 90% seiring chunk selesai
            $report((int) round(57 + (($ci + 1) / $totalChunks) * 33),
                'Mengambil detail pesanan… (' . ($ci + 1) . '/' . $totalChunks . ')');
            try {
                $detailResponse = $driver->getOrderDetail($store, $chunk);
                if (! empty($detailResponse['error'])) {
                    $this->log($store, 'sync_orders_detail', 'failed', $detailResponse['message'] ?? $detailResponse['error'], $detailResponse);
                    \Illuminate\Support\Facades\Log::warning("Gagal ambil detail order chunk: " . ($detailResponse['message'] ?? $detailResponse['error']));
                    $failedChunks++;
                    continue;
                }
                $details = array_merge($details, data_get($detailResponse, 'response.order_list', []));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Exception ambil detail order chunk: " . $e->getMessage());
                $failedChunks++;
                continue;
            }
        }
        
        if (empty($details) && $failedChunks > 0) {
            throw new \RuntimeException('Gagal mengambil detail dari seluruh order API.');
        }

        // 3. Simpan ke DB
        $report(92, 'Menyimpan pesanan ke database…');
        $stats  = $this->upsertOrders($store, $details, $dryRun);

        // 4. Auto-buat fulfillment draft untuk READY_TO_SHIP yang belum punya fulfillment
        if (!$dryRun) {
            $this->autoCreateFulfillments($store);
        }

        $found = count($orderSnList);
        $logType = $dryRun ? 'sync_orders_dry_run' : 'sync_orders';
        $this->log($store, $logType, 'success', "Synced {$stats['new']} baru + {$stats['updated']} update dari {$found} order.", [
            'time_from'         => $timeFrom,
            'time_to'           => $timeTo,
            'found'             => $found,
            'new'               => $stats['new'],
            'updated'           => $stats['updated'],
            'sku_empty'         => $stats['sku_empty'],
            'mapping_not_found' => $stats['mapping_not_found'],
            'missing_hpp'       => $stats['missing_hpp'],
            'ready'             => $stats['ready'],
            'incomplete'        => $stats['incomplete'],
            'order_sn_list'     => $orderSnList,
        ]);

        $total_issues = $stats['sku_empty'] + $stats['mapping_not_found'] + $stats['missing_hpp'];

        return [
            'found'             => $found,
            'new'               => $stats['new'],
            'updated'           => $stats['updated'],
            'sku_empty'         => $stats['sku_empty'],
            'mapping_not_found' => $stats['mapping_not_found'],
            'missing_hpp'       => $stats['missing_hpp'],
            'ready'             => $stats['ready'],
            'incomplete'        => $stats['incomplete'],
            'total_issues'      => $total_issues,
            'order_sn_list'     => $orderSnList,
            'message'           => ($dryRun ? "[DRY RUN] " : "") . "Berhasil sync {$found} order ({$stats['new']} baru, {$stats['updated']} update).",
        ];
    }

    /**
     * Sync order berdasarkan daftar order_sn spesifik (tanpa get_order_list).
     * Dipakai backfill Pesanan Kilat: booking yang sudah MATCHED punya order_sn,
     * tapi order-nya bisa saja belum pernah tersinkron ke marketplace_orders
     * (mis. lebih lama dari rentang sync harian) sehingga tidak muncul di halaman Orders.
     *
     * @return array{found: int, new: int, updated: int}
     */
    /**
     * Pastikan toko siap dipanggil API: coba refresh token bila kedaluwarsa.
     * SATU-SATUNYA pre-check koneksi sisi console/service — dipakai
     * marketplace:sync-orders & shopee:sync-historical-orders (sebelumnya blok
     * refresh TOKEN_EXPIRED yang sama persis terduplikasi di kedua command).
     */
    public function ensureStoreConnected(Store $store): bool
    {
        if ($store->connection_status === 'TOKEN_EXPIRED') {
            try {
                $driver = $this->gateway;
                if (method_exists($driver, 'refreshToken')) {
                    $driver->refreshToken($store);
                    $store->refresh();
                }
            } catch (\Throwable $e) {
                Log::warning('ensureStoreConnected: token refresh gagal', [
                    'store_id' => $store->id,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        return $store->connection_status === 'CONNECTED';
    }

    /**
     * Sync order rentang PANJANG secara berjendela (per <=14 hari) dengan lock
     * per-jendela — kunci yang SAMA dengan sync 5-menit (sync_store_{id}) supaya
     * tidak tabrakan, tapi dilepas di tiap jendela agar cron tidak terblokir lama.
     *
     * SATU-SATUNYA implementasi loop berjendela: dipakai marketplace:sync-orders
     * (--days > 14) dan shopee:sync-historical-orders. Sebelumnya logika ini
     * terduplikasi di dua command dengan perilaku lock berbeda (command sync-orders
     * memegang satu lock TTL 240 dtk yang kedaluwarsa di tengah rentang panjang).
     *
     * @param callable|null $onWindow fn(int $index, int $total, int $wFrom, int $wTo, ?array $result, ?\Throwable $error): void
     * @return array{windows:int, new:int, updated:int, failed:int, skipped_locked:int}
     */
    public function syncOrdersWindowed(
        Store $store,
        int $timeFrom,
        int $timeTo,
        int $pageSize = 50,
        string $timeRangeField = 'update_time',
        bool $newestFirst = false,
        ?callable $onWindow = null,
        int $pauseSeconds = 1,
    ): array {
        $windows = $this->splitTimeWindows($timeFrom, $timeTo);
        if ($newestFirst) {
            $windows = array_reverse($windows); // backfill: data terbaru masuk duluan
        }

        $totals = ['windows' => count($windows), 'new' => 0, 'updated' => 0, 'failed' => 0, 'skipped_locked' => 0];

        foreach ($windows as $i => [$wFrom, $wTo]) {
            $lock   = \Illuminate\Support\Facades\Cache::lock("sync_store_{$store->id}", 240);
            $result = null;
            $error  = null;

            try {
                $lock->block(120); // tunggu maks 2 menit bila proses lain sedang sync toko ini
                $result = $this->syncOrders($store, $wFrom, $wTo, $pageSize, false, null, $timeRangeField);
                $totals['new']     += (int) ($result['new'] ?? 0);
                $totals['updated'] += (int) ($result['updated'] ?? 0);
            } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
                $totals['skipped_locked']++;
                $error = $e;
            } catch (\Throwable $e) {
                $totals['failed']++;
                $error = $e;
                Log::warning('syncOrdersWindowed: jendela gagal', [
                    'store_id' => $store->id,
                    'from'     => date('Y-m-d', $wFrom),
                    'to'       => date('Y-m-d', $wTo),
                    'error'    => $e->getMessage(),
                ]);
            } finally {
                optional($lock)->release();
            }

            if ($onWindow) {
                try { $onWindow($i + 1, $totals['windows'], $wFrom, $wTo, $result, $error); } catch (\Throwable $e) { /* abaikan */ }
            }

            if ($pauseSeconds > 0 && $i + 1 < $totals['windows']) {
                sleep($pauseSeconds); // jeda antar jendela, jaring pengaman rate limit
            }
        }

        return $totals;
    }

    /**
     * Promosi booking Pesanan Kilat → order bernomor pesanan.
     *
     * Alur API Shopee: setelah pengiriman diatur (v2.logistics.ship_booking),
     * Shopee mencocokkan booking ke order buyer secara ASINKRON. Begitu MATCHED,
     * v2.order.get_booking_detail mengembalikan order_sn untuk booking_sn tsb.
     * Method ini: lengkapi order_sn dari get_booking_detail bila belum tercatat,
     * tarik order-nya via syncOrdersBySn() bila belum ada di marketplace_orders,
     * lalu tautkan booking_sn ke order lokal.
     *
     * @return string|null order_sn bila sudah MATCHED & tertaut, null bila belum.
     */
    public function promoteBookingToOrder(Store $store, string $bookingSn): ?string
    {
        $booking = \App\Models\MarketplaceBooking::where('store_id', $store->id)
            ->where('booking_sn', $bookingSn)
            ->first();
        if (! $booking) {
            return null;
        }

        // order_sn belum tercatat → ambil dari get_booking_detail (muncul saat MATCHED).
        if (blank($booking->order_sn)) {
            $driver = $this->gateway;
            if (method_exists($driver, 'getBookingDetail')) {
                $det  = $driver->getBookingDetail($store, $bookingSn);
                $list = data_get($det, 'response.booking_list') ?? data_get($det, 'response.order_list', []);
                $d    = collect($list)->firstWhere('booking_sn', $bookingSn) ?? ($list[0] ?? null);
                // Dua bentuk response yang terlihat di production: order_sn langsung,
                // atau bersarang di order_list[0].order_sn.
                $orderSn = $d['order_sn'] ?? data_get($d, 'order_list.0.order_sn');
                if (! empty($orderSn)) {
                    $booking->order_sn = $orderSn;
                    if (! empty($d['booking_status'])) {
                        $booking->booking_status = $d['booking_status'];
                    }
                    $booking->save();
                }
            }
        }

        if (blank($booking->order_sn)) {
            return null; // belum MATCHED
        }

        // Order lokal belum ada → backfill via detail order (idempotent).
        $exists = MarketplaceOrder::where('channel_order_id', $booking->order_sn)
            ->orWhere('external_order_id', $booking->order_sn)
            ->exists();
        if (! $exists) {
            $this->syncOrdersBySn($store, [$booking->order_sn]);
        }

        // Tautkan booking_sn ke order lokal (channel_order_id ATAU external_order_id).
        MarketplaceOrder::where('store_id', $store->id)
            ->where(function ($q) use ($booking) {
                $q->where('channel_order_id', $booking->order_sn)
                  ->orWhere('external_order_id', $booking->order_sn);
            })
            ->whereNull('booking_sn')
            ->update(['booking_sn' => $bookingSn]);

        return $booking->order_sn;
    }

    public function syncOrdersBySn(Store $store, array $orderSnList): array
    {
        $orderSnList = array_values(array_unique(array_filter($orderSnList)));
        if (empty($orderSnList)) {
            return ['found' => 0, 'new' => 0, 'updated' => 0];
        }

        $driver  = $this->gateway;
        $details = [];
        foreach (array_chunk($orderSnList, 50) as $chunk) {
            try {
                $detailResponse = $driver->getOrderDetail($store, $chunk);
                if (! empty($detailResponse['error'])) {
                    \Illuminate\Support\Facades\Log::warning(
                        "syncOrdersBySn [{$store->id}]: " . ($detailResponse['message'] ?? $detailResponse['error'])
                    );
                    continue;
                }
                $details = array_merge($details, data_get($detailResponse, 'response.order_list', []));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("syncOrdersBySn [{$store->id}]: " . $e->getMessage());
            }
        }

        if (empty($details)) {
            return ['found' => count($orderSnList), 'new' => 0, 'updated' => 0];
        }

        $stats = $this->upsertOrders($store, $details);
        $this->autoCreateFulfillments($store);
        $this->log($store, 'sync_orders_by_sn', 'success', "Backfill order kilat: {$stats['new']} baru + {$stats['updated']} update dari " . count($orderSnList) . " order_sn.", [
            'found' => count($orderSnList),
            'new'   => $stats['new'],
            'updated' => $stats['updated'],
        ]);

        return ['found' => count($orderSnList), 'new' => $stats['new'], 'updated' => $stats['updated']];
    }

    /**
     * Cooldown minimum (menit) sebelum settlement yang BELUM final
     * (settlement_time NULL) di-refresh ulang otomatis oleh batch reguler (bukan
     * --resync). Mencegah order yang sama diminta ulang ke API Shopee di setiap
     * batch/scheduler tick (tiap 4 jam) selagi memang belum ada perubahan status di
     * sisi Shopee. TIDAK berlaku untuk settlement yang sudah final
     * (settlement_time terisi) — itu hanya bisa di-refresh lewat --resync eksplisit.
     */
    private const PENDING_SETTLEMENT_REFRESH_COOLDOWN_MINUTES = 60;

    /**
     * Field settlement yang dianggap "material" untuk keperluan logging perubahan
     * saat --resync (BUKAN untuk tabel histori — histori sengaja belum dibuat di Fase 1,
     * lihat AUDIT_FASE1_RANCANGAN_FINAL.md Bagian 10).
     */
    private const MATERIAL_SETTLEMENT_FIELDS = [
        'commission_fee', 'service_fee', 'transaction_fee', 'affiliate_fee',
        'seller_voucher', 'shipping_fee_subsidy', 'shipping_insurance_fee',
        'escrow_tax', 'final_income', 'settlement_time',
    ];

    /**
     * Sync settlement / escrow data per order dari marketplace.
     *
     * Alur: query order eligible → panggil finance API (dengan retry) → validasi
     * response → mapping → updateOrCreate (per order, dalam transaction pendek) →
     * logging → result summary. Detail retry/validasi/mapping/normalisasi nominal
     * & timestamp SENGAJA dipisah ke method privat lain (lihat
     * getEscrowDetailWithRetry(), validateEscrowIncome(), mapEscrowSettlement(),
     * decimalValue(), normalizeShopeeTimestamp()) supaya method ini tetap fokus
     * pada orkestrasi, bukan detail teknis tiap langkah.
     *
     * TIDAK membuat jurnal accounting apa pun (di luar scope Fase 1).
     *
     * @param  Store  $store
     * @param  int|null  $timeFrom  Unix timestamp, filter ordered_at >= ini
     * @param  int|null  $timeTo  Unix timestamp, filter ordered_at <= ini
     * @param  string|null  $orderSn  Kalau diisi, batasi ke satu channel_order_id
     * @param  bool  $resync  Kalau true, SEMUA order (termasuk yang settlement-nya sudah
     *                        final) diproses ulang (updateOrCreate). Kalau false (default),
     *                        order tetap eligible bila: belum ada settlement sama sekali,
     *                        ATAU settlement ada tapi belum final (settlement_time NULL)
     *                        dan sudah lewat cooldown — lihat
     *                        PENDING_SETTLEMENT_REFRESH_COOLDOWN_MINUTES.
     * @param  int  $limit  Maks order yang diambil dalam satu pemanggilan (satu batch)
     * @param  int  $afterId  Cursor — hanya ambil order dengan id > afterId (untuk mode --all di command)
     * @return array{found:int, processed:int, synced:int, new:int, updated:int, skipped:int, errors:int, last_processed_id:int|null, failed_order_ids:array<int,string>, last_call_meta:array|null, message:string}
     */
    public function syncSettlements(
        Store $store,
        ?int $timeFrom = null,
        ?int $timeTo = null,
        ?string $orderSn = null,
        bool $resync = false,
        int $limit = 200,
        int $afterId = 0,
        bool $waitForRateLimit = false,
        ?callable $progress = null,
    ): array {
        $driver = $this->gateway;

        $query = MarketplaceOrder::where('store_id', $store->id)
            ->where('id', '>', $afterId)
            ->where('order_status', 'COMPLETED')
            ->orderBy('id');

        if (! $resync) {
            // Eligible untuk batch REGULER (bukan --resync) kalau salah satu:
            // (a) belum pernah ada settlement sama sekali — perilaku asli, TIDAK diubah; atau
            // (b) SUDAH ada settlement tapi BELUM FINAL (settlement_time NULL) dan sudah
            //     melewati cooldown (lihat PENDING_SETTLEMENT_REFRESH_COOLDOWN_MINUTES) —
            //     supaya order yang duitnya belum cair saat sync pertama kali TIDAK
            //     terlewat selamanya, tanpa membanjiri API Shopee dengan re-check di
            //     setiap batch/scheduler tick untuk order yang sama.
            // Settlement yang SUDAH final (settlement_time terisi) TIDAK PERNAH masuk
            // cabang (b) — hanya bisa diperbarui lagi lewat --resync eksplisit.
            $query->where(function ($eligible) use ($store) {
                $eligible->whereNotExists(function ($q) use ($store) {
                    $q->select(DB::raw(1))
                      ->from('marketplace_order_settlements')
                      ->whereColumn('marketplace_order_settlements.channel_order_id', 'marketplace_orders.channel_order_id')
                      ->where('marketplace_order_settlements.store_id', $store->id);
                })->orWhereExists(function ($q) use ($store) {
                    $q->select(DB::raw(1))
                      ->from('marketplace_order_settlements')
                      ->whereColumn('marketplace_order_settlements.channel_order_id', 'marketplace_orders.channel_order_id')
                      ->where('marketplace_order_settlements.store_id', $store->id)
                      ->whereNull('marketplace_order_settlements.settlement_time')
                      ->where('marketplace_order_settlements.synced_at', '<=', now()->subMinutes(self::PENDING_SETTLEMENT_REFRESH_COOLDOWN_MINUTES));
                });
            });
        }
        if ($orderSn) {
            $query->where('channel_order_id', $orderSn);
        } else {
            // Abaikan order dengan permanent failure saat memproses batch
            $query->whereNull('settlement_sync_error_code');
        }
        if ($timeFrom) {
            $query->where('ordered_at', '>=', now()->setTimestamp($timeFrom));
        }
        if ($timeTo) {
            $query->where('ordered_at', '<=', now()->setTimestamp($timeTo));
        }

        $orders  = $query->limit(max(1, $limit))->get();
        $found   = $orders->count();
        $processed = 0;
        $synced  = 0;
        $new     = 0;
        $updated = 0;
        $skipped = 0;
        $errors  = 0;
        $lastProcessedId = null;
        $failedOrderIds  = [];
        // Metadata panggilan API TERAKHIR yang diproses (attempts/http_status/retry_after/
        // duration) — dipakai command untuk --inspect. Untuk mode satu-order (--order),
        // ini otomatis metadata satu-satunya order yang diproses. Untuk batch, ini hanya
        // metadata order terakhir (bukan agregat) — cukup untuk kebutuhan --inspect yang
        // memang dibatasi hanya valid bersama --order (satu order).
        $lastCallMeta = null;
        $safeFound = max(1, $found);

        $this->reportProgress($progress, [
            'status'            => 'running',
            'phase'             => 'batch_start',
            'percent'           => $found > 0 ? 5 : 100,
            'label'             => $found > 0
                ? "Menyiapkan batch settlement ({$found} order)…"
                : 'Tidak ada settlement eligible untuk batch ini.',
            'store_id'          => $store->id,
            'store_name'        => $store->name,
            'found'             => $found,
            'processed'         => 0,
            'synced'            => 0,
            'new'               => 0,
            'updated'           => 0,
            'skipped'           => 0,
            'errors'            => 0,
            'last_processed_id' => null,
        ]);

        foreach ($orders as $order) {
            $processed++;
            $lastProcessedId = $order->id;

            $this->reportProgress($progress, [
                'status'            => 'running',
                'phase'             => 'fetching',
                'percent'           => $found > 0
                    ? min(95, 5 + (int) floor((max(0, $processed - 1) / $safeFound) * 90))
                    : 100,
                'label'             => "Mengambil detail settlement {$processed}/{$found}…",
                'store_id'          => $store->id,
                'store_name'        => $store->name,
                'found'             => $found,
                'processed'         => $processed - 1,
                'synced'            => $synced,
                'new'               => $new,
                'updated'           => $updated,
                'skipped'           => $skipped,
                'errors'            => $errors,
                'last_processed_id' => $lastProcessedId,
                'current_order'     => $order->channel_order_id,
            ]);

            try {
                // Revalidasi status order tepat sebelum API dipanggil
                $order->refresh();
                if ($order->order_status !== 'COMPLETED') {
                    $skipped++;
                    Log::info("[sync-settlements] Order {$order->channel_order_id} berubah status menjadi {$order->order_status} (bukan COMPLETED), API batal dipanggil.");
                    continue;
                }

                // 1) Panggil API dulu (di luar transaction — request jaringan tidak boleh
                //    menahan transaction database, apalagi di SQLite yang rawan "database
                //    is locked" kalau transaction dibiarkan terbuka lama).
                $retryResult = $this->getEscrowDetailWithRetry(
                    $driver,
                    $store,
                    $order->channel_order_id,
                    $waitForRateLimit,
                    $progress,
                );
                $response = $retryResult['payload'];
                $lastCallMeta = $retryResult['meta'];

                // 2) Validasi response SEBELUM mapping — tidak langsung anggap sukses
                //    hanya karena tidak ada exception.
                $validation = $this->validateEscrowIncome($response);

                if ($validation['status'] === 'skipped') {
                    // Kondisi bisnis (mis. response kosong / belum eligible di sisi Shopee).
                    // Dibuatkan settlement pending agar mendapat cooldown, sehingga
                    // tidak ditarik berulang-ulang dalam batch yang sama setiap kueri.
                    $skipped++;
                    Log::info("[sync-settlements] Order {$order->channel_order_id} dilewati: {$validation['message']}");
                    
                    $dummyMapped = $this->mapEscrowSettlement($store, $order, [], []);
                    DB::transaction(function () use ($order, $dummyMapped) {
                        MarketplaceOrderSettlement::updateOrCreate(
                            ['channel_order_id' => $order->channel_order_id],
                            $dummyMapped['values']
                        );
                    });
                    
                    continue;
                }

                if ($validation['status'] === 'error') {
                    $errors++;
                    $failedOrderIds[] = $order->channel_order_id;
                    Log::warning("[sync-settlements] Order {$order->channel_order_id} gagal validasi ({$validation['reason']}): {$validation['message']}");
                    
                    // Tandai error code untuk semua error yang lolos retry agar order
                    // bermasalah tidak terus-menerus memblokir limit batch.
                    $order->update([
                        'settlement_sync_error_code' => $validation['reason'] ?? 'api_error',
                        'settlement_sync_failed_at' => now(),
                    ]);
                    
                    continue;
                }

                // 3) Mapping — terpusat, tidak menyebar fallback di banyak tempat.
                $mapped = $this->mapEscrowSettlement($store, $order, $validation['node'], $response);

                // 3b) Field finansial yang tidak tersedia dari API dicatat (nama kolom lokal
                //     saja, BUKAN payload mentah) — supaya "field hilang" bisa dibedakan dari
                //     "field dikirim bernilai 0" tanpa perlu buka raw_json satu-satu.
                if (! empty($mapped['missing_financial_fields'])) {
                    Log::info("[sync-settlements] Order {$order->channel_order_id} field API hilang", [
                        'store_id' => $store->id,
                        'missing_financial_fields' => $mapped['missing_financial_fields'],
                    ]);
                }

                // 4) Simpan — transaction PENDEK, hanya membungkus operasi database untuk
                //    SATU order ini (bukan seluruh batch), sesuai prinsip: satu error tidak
                //    boleh rollback order lain, dan transaction panjang berbahaya untuk SQLite.
                $wasNew = true;

                DB::transaction(function () use ($order, $mapped, $resync, &$wasNew) {
                    // Dicek terlepas dari $resync — dipakai untuk breakdown baru vs
                    // diperbarui di response (kebutuhan UI tombol Sync Settlement),
                    // TIDAK mengubah perilaku upsert/histori yang sudah ada.
                    $existing = MarketplaceOrderSettlement::where('channel_order_id', $order->channel_order_id)->first();
                    $wasNew   = $existing === null;

                    MarketplaceOrderSettlement::updateOrCreate(
                        ['channel_order_id' => $order->channel_order_id],
                        $mapped['values']
                    );

                    // Log perubahan material baik saat --resync eksplisit MAUPUN saat
                    // refresh otomatis settlement pending (existing tapi settlement_time
                    // sebelumnya NULL) — keduanya adalah "update terhadap row yang sudah
                    // ada" dan sama-sama layak diaudit datanya berubah dari apa ke apa.
                    if ($existing) {
                        $this->logMaterialChanges($order, $existing, $mapped['values']);
                    }

                    // Reset permanent failure jika akhirnya sukses
                    if ($order->settlement_sync_error_code !== null) {
                        $order->update([
                            'settlement_sync_error_code' => null,
                            'settlement_sync_failed_at' => null,
                        ]);
                    }
                });

                $synced++;
                if ($wasNew) {
                    $new++;
                } else {
                    $updated++;
                }

                $this->reportProgress($progress, [
                    'status'            => 'running',
                    'phase'             => 'processing',
                    'percent'           => $found > 0
                        ? min(95, 5 + (int) floor(($processed / $safeFound) * 90))
                        : 100,
                    'label'             => "Memproses settlement {$processed}/{$found}…",
                    'store_id'          => $store->id,
                    'store_name'        => $store->name,
                    'found'             => $found,
                    'processed'         => $processed,
                    'synced'            => $synced,
                    'new'               => $new,
                    'updated'           => $updated,
                    'skipped'           => $skipped,
                    'errors'            => $errors,
                    'last_processed_id' => $lastProcessedId,
                    'current_order'     => $order->channel_order_id,
                ]);

            } catch (\Throwable $e) {
                Log::error("[sync-settlements] Exception order {$order->channel_order_id}: " . $e->getMessage());
                $errors++;
                $failedOrderIds[] = $order->channel_order_id;
            }
        }

        $message = "Settlement — found: {$found}, processed: {$processed}, synced: {$synced} (new: {$new}, updated: {$updated}), skipped: {$skipped}, errors: {$errors}.";

        $logStatus = 'success';
        if ($errors > 0) {
            $logStatus = $synced > 0 ? 'partial_success' : 'failed';
        }

        $this->reportProgress($progress, [
            'status'            => $logStatus === 'success' ? 'success' : ($logStatus === 'partial_success' ? 'warn' : 'error'),
            'phase'             => 'batch_done',
            'percent'           => 100,
            'label'             => $message,
            'store_id'          => $store->id,
            'store_name'        => $store->name,
            'found'             => $found,
            'processed'         => $processed,
            'synced'            => $synced,
            'new'               => $new,
            'updated'           => $updated,
            'skipped'           => $skipped,
            'errors'            => $errors,
            'last_processed_id' => $lastProcessedId,
            'failed_order_ids'  => array_slice($failedOrderIds, 0, 20),
        ]);

        $this->log($store, 'sync_settlements', $logStatus, $message, [
            'found'              => $found,
            'processed'          => $processed,
            'synced'             => $synced,
            'new'                => $new,
            'updated'            => $updated,
            'skipped'            => $skipped,
            'errors'             => $errors,
            'last_processed_id'  => $lastProcessedId,
            // Dibatasi maksimal 20 supaya log tidak membengkak untuk batch ratusan order.
            'failed_order_ids'   => array_slice($failedOrderIds, 0, 20),
        ]);

        return [
            'found'              => $found,
            'processed'          => $processed,
            'synced'             => $synced,
            'new'                => $new,
            'updated'            => $updated,
            'skipped'            => $skipped,
            'errors'             => $errors,
            'last_processed_id'  => $lastProcessedId,
            'failed_order_ids'   => array_slice($failedOrderIds, 0, 20),
            'last_call_meta'     => $lastCallMeta,
            'message'            => $message,
        ];
    }

    /**
     * Backfill settlement secara synchronous untuk rentang panjang.
     *
     * Dipakai controller UI agar 1-3 bulan ke belakang bisa dijalankan langsung
     * di request biasa, tanpa queue/background, sambil tetap menghormati cooldown
     * rate limit Shopee dan melanjutkan dari cursor batch berikutnya.
     *
     * @return array{
     *   found:int, processed:int, synced:int, new:int, updated:int, skipped:int, errors:int,
     *   batches:int, last_processed_id:int|null, failed_order_ids:array<int,string>,
     *   duration_ms:float, message:string, status:string
     * }
     */
    public function syncSettlementsBackfill(
        Store $store,
        ?int $timeFrom = null,
        ?int $timeTo = null,
        ?string $orderSn = null,
        bool $resync = false,
        int $limit = 200,
        int $maxBatches = 20,
        int $pauseSeconds = 1,
        ?callable $progress = null,
    ): array {
        $totals = [
            'found'           => 0,
            'processed'       => 0,
            'synced'          => 0,
            'new'             => 0,
            'updated'         => 0,
            'skipped'         => 0,
            'errors'          => 0,
            'batches'         => 0,
            'last_processed_id' => null,
            'failed_order_ids'=> [],
        ];

        $afterId = 0;
        $noProgressStreak = 0;
        $startedAt = microtime(true);
        $maxRuntimeSeconds = max(900, (int) config('shopee.settlement_backfill_max_runtime_seconds', 7200));
        $orderMode = $orderSn !== null && $orderSn !== '';

        $this->reportProgress($progress, [
            'status'     => 'running',
            'phase'      => 'backfill_start',
            'percent'    => 3,
            'label'      => 'Menyiapkan backfill settlement…',
            'store_id'   => $store->id,
            'store_name' => $store->name,
            'batches'    => 0,
            'max_batches'=> $maxBatches,
        ]);

        for ($batch = 1; $batch <= $maxBatches; $batch++) {
            $batchProgress = function (array $payload) use ($progress, $batch, $maxBatches): void {
                if (! $progress) {
                    return;
                }

                $batchPercent = (float) ($payload['percent'] ?? 0);
                $overall = (($batch - 1) / max(1, $maxBatches)) * 100 + ($batchPercent / max(1, $maxBatches));
                $payload['percent'] = (int) min(99, round($overall));
                $payload['batch'] = $batch;
                $payload['max_batches'] = $maxBatches;
                $payload['phase'] = $payload['phase'] ?? 'batch_running';
                $progress($payload);
            };

            $result = $this->syncSettlements(
                $store,
                $timeFrom,
                $timeTo,
                $orderSn,
                $resync,
                $limit,
                $afterId,
                true, // backfill harus menunggu cooldown rate limit, bukan gagal cepat
                $batchProgress,
            );

            $totals['batches'] = $batch;
            $totals['found']     += (int) ($result['found'] ?? 0);
            $totals['processed'] += (int) ($result['processed'] ?? 0);
            $totals['synced']    += (int) ($result['synced'] ?? 0);
            $totals['new']       += (int) ($result['new'] ?? 0);
            $totals['updated']   += (int) ($result['updated'] ?? 0);
            $totals['skipped']   += (int) ($result['skipped'] ?? 0);
            $totals['errors']    += (int) ($result['errors'] ?? 0);
            $totals['failed_order_ids'] = array_values(array_unique(array_merge(
                $totals['failed_order_ids'],
                $result['failed_order_ids'] ?? []
            )));
            $totals['last_processed_id'] = $result['last_processed_id'] ?? $totals['last_processed_id'];

            if (($result['processed'] ?? 0) === 0) {
                break;
            }

            if ($orderMode) {
                break;
            }

            $madeProgress = (($result['synced'] ?? 0) > 0) || (($result['last_processed_id'] ?? 0) > $afterId);
            $noProgressStreak = $madeProgress ? 0 : ($noProgressStreak + 1);
            $afterId = max($afterId, (int) ($result['last_processed_id'] ?? $afterId));

            if ($noProgressStreak >= 2) {
                break;
            }

            if ((microtime(true) - $startedAt) >= $maxRuntimeSeconds) {
                break;
            }

            if ($batch < $maxBatches && $pauseSeconds > 0) {
                sleep($pauseSeconds);
            }
        }

        $durationMs = (microtime(true) - $startedAt) * 1000;
        $message = "Settlement backfill — found: {$totals['found']}, processed: {$totals['processed']}, synced: {$totals['synced']} (new: {$totals['new']}, updated: {$totals['updated']}), skipped: {$totals['skipped']}, errors: {$totals['errors']}, batches: {$totals['batches']}.";
        $status = $totals['errors'] > 0 ? ($totals['synced'] > 0 ? 'partial_success' : 'failed') : 'success';

        $this->reportProgress($progress, [
            'status'            => $status === 'success' ? 'success' : ($status === 'partial_success' ? 'warn' : 'error'),
            'phase'             => 'backfill_done',
            'percent'           => 100,
            'label'             => $message,
            'store_id'          => $store->id,
            'store_name'        => $store->name,
            'found'             => $totals['found'],
            'processed'         => $totals['processed'],
            'synced'            => $totals['synced'],
            'new'               => $totals['new'],
            'updated'           => $totals['updated'],
            'skipped'           => $totals['skipped'],
            'errors'            => $totals['errors'],
            'batches'           => $totals['batches'],
            'max_batches'       => $maxBatches,
            'last_processed_id' => $totals['last_processed_id'],
            'failed_order_ids'  => array_slice($totals['failed_order_ids'], 0, 20),
        ]);

        return [
            'found'              => $totals['found'],
            'processed'          => $totals['processed'],
            'synced'             => $totals['synced'],
            'new'                => $totals['new'],
            'updated'            => $totals['updated'],
            'skipped'            => $totals['skipped'],
            'errors'             => $totals['errors'],
            'batches'            => $totals['batches'],
            'last_processed_id'  => $totals['last_processed_id'],
            'failed_order_ids'   => array_slice($totals['failed_order_ids'], 0, 20),
            'duration_ms'        => round($durationMs, 2),
            'status'             => $status,
            'message'            => $message,
        ];
    }

    /**
     * Panggil getEscrowDetail() dengan retry terbatas — HANYA untuk kegagalan
     * transient (ConnectionException, HTTP 429, HTTP 5xx). Tidak retry untuk error
     * 4xx lain (dianggap permanen: validasi/permission/order tidak valid).
     *
     * Membaca status HTTP dari `_meta.http_status` (lihat ShopeeChannel::withHttpMeta()),
     * BUKAN dari pencocokan string bebas terhadap field 'message'/'error' Shopee.
     *
     * Tidak pernah mencetak/mencatat token atau credential — hanya menerima $orderSn
     * (string) dan mengembalikan response API apa adanya (ditambah _meta).
     */
    /**
     * @return array{payload:array, meta:array{attempts:int, http_status:mixed, retry_after:mixed, token_refreshed:null, duration_ms:float}}
     */
    private function getEscrowDetailWithRetry(
        MarketplaceApiGateway $driver,
        Store $store,
        string $orderSn,
        bool $waitForRateLimit = false,
        ?callable $progress = null,
    ): array
    {
        // CATATAN KONSOLIDASI: loop retry transien (429/5xx/koneksi putus) yang dulu
        // ada di sini DIHAPUS — sekarang ditangani SATU kali di lapisan channel
        // (ShopeeChannel::resilientRequest: backoff + cooldown 429 global per toko).
        // Dobel retry di dua lapis = hingga 9 panggilan API untuk satu order gagal.
        // Wrapper ini dipertahankan hanya untuk instrumentasi (durasi, http_status)
        // dengan BENTUK RETURN yang sama persis (payload + meta) supaya
        // validateEscrowIncome()/mapEscrowSettlement()/laporan --inspect tidak berubah.
        $startedAt = hrtime(true);
        $attempts = 0;
        $maxAttempts = $waitForRateLimit ? 13 : 2;
        $maxCooldownWaits = $waitForRateLimit ? 12 : 0;
        $cooldownWaits = 0;
        $response = [];

        do {
            $attempts++;

            try {
                $response = $driver->getEscrowDetail($store, $orderSn);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $response = [
                    'error'   => 'connection_exception',
                    'message' => $e->getMessage(),
                    '_meta'   => ['http_status' => null, 'retry_after' => null],
                ];
            }

            $status = $response['_meta']['http_status'] ?? null;
            $error  = (string) ($response['error'] ?? '');
            $msg    = strtolower((string) ($response['message'] ?? ''));
            $isRateLimited = $status === 429
                || str_contains(strtolower($error), 'rate_limit')
                || str_contains($msg, 'rate limit');
            $isConnectionException = $error === 'connection_exception';
            $isServerError = is_int($status) && $status >= 500;
            $shouldRetry = ($isRateLimited || $isConnectionException || $isServerError) && $attempts < $maxAttempts;

            if (! $shouldRetry) {
                break;
            }

            if ($isRateLimited && $waitForRateLimit && $cooldownWaits < $maxCooldownWaits) {
                $retryAfter = (int) ($response['_meta']['retry_after'] ?? 0);
                if ($retryAfter <= 0) {
                    $retryAfter = (int) config('shopee.rate_limit_cooldown', 30);
                }

                $sleepSeconds = max(1, $retryAfter + random_int(1, 3));
                $cooldownWaits++;
                Log::warning("[sync-settlements] Rate limit cooldown untuk order {$orderSn} di toko {$store->id}; menunggu {$sleepSeconds}s lalu retry (attempt {$attempts}).");
                $this->reportProgress($progress, [
                    'status'        => 'running',
                    'phase'         => 'retry_wait',
                    'percent'       => null,
                    'label'         => "Rate limit, menunggu {$sleepSeconds}s untuk order {$orderSn}…",
                    'store_id'      => $store->id,
                    'store_name'    => $store->name,
                    'current_order' => $orderSn,
                    'retry_after'   => $sleepSeconds,
                    'attempts'      => $attempts,
                ]);
                set_time_limit(max(180, $sleepSeconds + 60));
                sleep($sleepSeconds);
            }
        } while (true);

        $durationMs = (hrtime(true) - $startedAt) / 1_000_000;

        return [
            // Payload API asli (ShopeeChannel::withHttpMeta() masih menyisipkan _meta di
            // sini) — TIDAK diubah, supaya validateEscrowIncome()/mapEscrowSettlement()
            // yang sudah ada tidak perlu berubah. _meta di payload ini TIDAK PERNAH ikut
            // ke raw_json (mapEscrowSettlement() hanya mengambil node order_income).
            'payload' => $response,
            'meta'    => [
                'attempts'    => $attempts,
                'http_status' => $response['_meta']['http_status'] ?? null,
                'retry_after' => $response['_meta']['retry_after'] ?? null,
                // SENGAJA null ("tidak diketahui"), BUKAN false. ShopeeChannel belum
                // menyediakan sinyal aman untuk mendeteksi refresh token benar-benar
                // terjadi (menebak dari perubahan token_expires_at berisiko salah, mis.
                // race condition antar proses). Keputusan UAT 23 Juli 2026: jangan klaim
                // false tanpa instrumentasi pasti — caller (command --inspect) menampilkan
                // "unknown/not instrumented" untuk nilai null ini.
                'token_refreshed' => null,
                'duration_ms' => round($durationMs, 2),
            ],
        ];
    }

    /**
     * Validasi response get_escrow_detail SEBELUM mapping. Tidak menentukan kondisi
     * bisnis berdasarkan pencocokan string bebas — mengecek field 'error' resmi
     * Shopee dan struktur node finansial secara eksplisit.
     *
     * @return array{status:'ok'|'skipped'|'error', node?:array, reason?:string, message?:string}
     */
    private function validateEscrowIncome(array $response): array
    {
        if (! empty($response['error'])) {
            return [
                'status'  => 'error',
                'reason'  => (string) $response['error'],
                'message' => (string) ($response['message'] ?? $response['error']),
            ];
        }

        $node = $response['response']['order_income'] ?? $response['response'] ?? null;

        if (! is_array($node)) {
            return [
                'status'  => 'error',
                'reason'  => 'invalid_structure',
                'message' => 'Struktur response finansial tidak ditemukan (response / response.order_income bukan array).',
            ];
        }

        if (empty($node)) {
            // Response kosong TANPA error eksplisit — bukan berarti settlement bernilai
            // nol. Paling mungkin: order belum eligible di sisi Shopee walau status lokal
            // sudah eligible. Dicatat sebagai skipped, BUKAN disimpan sebagai settlement 0.
            return [
                'status'  => 'skipped',
                'reason'  => 'empty_income',
                'message' => 'Response finansial kosong — order kemungkinan belum eligible di sisi Shopee, atau field order_income tidak dikembalikan.',
            ];
        }

        $hasPrimarySignal = array_key_exists('final_income', $node)
            || array_key_exists('escrow_amount', $node)
            || array_key_exists('order_sn', $node);

        if (! $hasPrimarySignal) {
            return [
                'status'  => 'error',
                'reason'  => 'missing_primary_fields',
                'message' => 'Tidak ada field final_income/escrow_amount/order_sn di response — struktur tidak dikenali (perlu verifikasi ulang ke dokumentasi resmi).',
            ];
        }

        return ['status' => 'ok', 'node' => $node];
    }

    /**
     * Mapping terpusat dari node finansial Shopee (`order_income`) ke kolom
     * `marketplace_order_settlements`. SEMUA fallback field (a ?? b ?? c) ada di SATU
     * tempat ini, bukan menyebar.
     *
     * KOREKSI MAPPING BERBASIS UAT NYATA (23 Juli 2026) — order `2607181DCXQSBW`,
     * toko Greatfit.id (store_id=5), lewat `--inspect`. Fallback lama (asumsi, belum
     * pernah diverifikasi) DIPERTAHANKAN sebagai compatibility guard untuk kemungkinan
     * variasi response order/versi API lain — bukan dihapus, hanya diberi prioritas
     * lebih rendah dari field yang sudah terbukti nyata. Ringkasan keputusan:
     *  - buyer_payment_amount: `buyer_total_amount` TERVERIFIKASI ada (81912 di UAT)
     *    dan harus jadi prioritas utama. Field lama seperti `buyer_payment_amount`
     *    / `buyer_paid_amount` hanya fallback kompatibilitas bila response lama
     *    belum mengirim `buyer_total_amount`.
     *  - seller_voucher: `voucher_from_seller` TERVERIFIKASI ada (200 di UAT).
     *  - transaction_fee: Kombinasi `seller_transaction_fee` (beban seller) dan 
     *    `seller_order_processing_fee` (Biaya Penanganan Pesanan). SENGAJA TIDAK
     *    memakai `buyer_transaction_fee` atau `credit_card_transaction_fee` (beban pembeli).
     *    sebagai fallback — biaya pembeli tidak boleh masuk laporan biaya seller.
     *  - activity_fee: HANYA mewakili AMS commission (`order_ams_commission_fee`).
     *    `campaign_fee` SENGAJA TIDAK dipakai — dianggap konsep berbeda sampai terbukti
     *    sebaliknya; tetap tersimpan apa adanya di raw_json, kandidat kolom terpisah di
     *    fase berikutnya (TIDAK dibuat di koreksi ini).
     *  - final_income: `escrow_amount` TERVERIFIKASI ada (68456 di UAT). `final_income`
     *    tidak pernah muncul di response nyata — fallback ini pada dasarnya jadi primary.
     *  - affiliate_fee & shipping_insurance_fee: disimpan dari field pengganti yang
     *    umum dipakai API/response mentah bila tersedia, supaya kolom baru tetap terisi
     *    tanpa merusak backward compatibility kalau field belum ada.
     *  - seller_coin_cash_back: `seller_coin_cash_back` sendiri TERVERIFIKASI ada di UAT
     *    (field asli persis nama kolom lokal) — diprioritaskan di atas fallback lama.
     *  - commission_fee, service_fee, actual_shipping_fee, shopee_shipping_rebate,
     *    reverse_shipping_fee, drc_adjustable_refund, escrow_tax: TIDAK diubah — field
     *    utamanya sudah terbukti tepat di UAT.
     *  - settlement_time: TIDAK diubah (lihat docblock di bawah, sumbernya belum
     *    terbukti dari UAT ini — order yang diuji belum ada timestamp pencairan).
     *
     * ad_cost (TECHNICAL DEBT): kolom ini ada di skema tapi SENGAJA TIDAK dipetakan dari
     * field mana pun di sini. Belum ada field di get_escrow_detail yang terverifikasi
     * sebagai sumbernya — JANGAN diisi pakai campaign_fee, AMS fee, atau biaya lain
     * tanpa verifikasi konsep dulu. Kolom tetap default sesuai skema.
     *
     * `raw_json` HANYA berisi node finansial asli Shopee (`$income`) — TIDAK
     * mengandung `_meta` (metadata HTTP internal).
     *
     * @return array{values: array, missing_financial_fields: array<int,string>}
     */
    private function mapEscrowSettlement(Store $store, MarketplaceOrder $order, array $income, array $rawResponse): array
    {
        $rootResponse = $rawResponse['response'] ?? [];

        $settlementTime = $this->normalizeShopeeTimestamp($income['escrow_release_time'] ?? null)
            ?? $this->normalizeShopeeTimestamp($rootResponse['escrow_release_time'] ?? null)
            ?? $this->normalizeShopeeTimestamp($income['settlement_time'] ?? null);

        // Fallback: Jika Shopee sama sekali tidak mengirimkan escrow_release_time di API escrow (sering terjadi 
        // di versi API baru), kita gunakan update_time dari order detail saat statusnya sudah COMPLETED.
        if (! $settlementTime && $order->order_status === 'COMPLETED' && !empty($order->raw_json['update_time'])) {
            $settlementTime = $this->normalizeShopeeTimestamp($order->raw_json['update_time']);
        }

        // Melacak kolom mana yang nilainya "field tidak tersedia dari API" (BUKAN
        // "field dikirim bernilai 0") — dipakai untuk observability (missing_financial_fields
        // di sync log), supaya dua kondisi ini tidak tercampur padahal keduanya sama-sama
        // tersimpan sebagai 0.00 di kolom NOT NULL (lihat docblock nn()).
        $missingFields = [];
        $val = function (string $localColumn, ?string $decimal) use (&$missingFields): string {
            if ($decimal === null) {
                $missingFields[] = $localColumn;
            }
            return $this->nn($decimal);
        };

        $values = [
            'store_id'   => $store->id,
            'order_id'   => $order->id,

            // Pembayaran customer (Subtotal Pesanan / Harga Produk yg diakui seller sbg gross)
            'buyer_payment_amount'  => $val('buyer_payment_amount', $this->decimalValue(
                $income['buyer_total_amount'] ?? $income['buyer_paid_amount'] ?? $income['buyer_payment_amount'] ?? $income['order_selling_price'] ?? $income['cost_of_goods_sold'] ?? null
            )),

            // Fee marketplace
            'commission_fee'        => $val('commission_fee', $this->decimalValue($income['commission_fee'] ?? null)),
            'service_fee'           => $val('service_fee', $this->decimalValue($income['service_fee'] ?? null)),
            'transaction_fee'       => $val('transaction_fee', $this->decimalValue(
                ($income['seller_transaction_fee'] ?? 0) + ($income['seller_order_processing_fee'] ?? 0) ?: ($income['transaction_fee'] ?? null)
            )),
            'affiliate_fee'         => $val('affiliate_fee', $this->decimalValue(
                $income['affiliate_fee'] ?? $income['affiliate_commission_fee'] ?? $income['affiliate_commission'] ?? $income['seller_affiliate_fee'] ?? null
            )),

            // Voucher & diskon
            'seller_voucher'        => $val('seller_voucher', $this->decimalValue(
                $income['voucher_from_seller'] ?? $income['seller_voucher_rebate'] ?? $income['seller_voucher'] ?? null
            )),
            'seller_coin_cash_back' => $val('seller_coin_cash_back', $this->decimalValue(
                $income['seller_coin_cash_back'] ?? $income['seller_absorbed_coin_discount'] ?? null
            )),

            // Ongkir
            'actual_shipping_fee'   => $val('actual_shipping_fee', $this->decimalValue($income['actual_shipping_fee'] ?? $income['estimated_shipping_fee'] ?? null)),
            'shipping_fee_subsidy'  => $val('shipping_fee_subsidy', $this->decimalValue($income['shopee_shipping_rebate'] ?? $income['shipping_fee_rebate'] ?? null)),
            'reverse_shipping_fee'  => $val('reverse_shipping_fee', $this->decimalValue($income['reverse_shipping_fee'] ?? null)),
            'shipping_insurance_fee' => $val('shipping_insurance_fee', $this->decimalValue(
                $income['shipping_insurance_fee'] ?? $income['shipping_insurance'] ?? $income['insurance_fee'] ?? null
            )),

            // Campaign & lainnya — activity_fee = AMS commission SAJA (lihat docblock).
            'activity_fee'          => $val('activity_fee', $this->decimalValue(
                $income['order_ams_commission_fee'] ?? $income['ams_commission_fee'] ?? $income['activity_fee'] ?? null
            )),
            'drc_adjustable_refund' => $val('drc_adjustable_refund', $this->decimalValue($income['drc_adjustable_refund'] ?? $income['seller_return_refund_amount'] ?? null)),
            'escrow_tax'            => $val('escrow_tax', $this->decimalValue($income['escrow_tax'] ?? null)),

            // Dana cair
            'final_income'          => $val('final_income', $this->decimalValue($income['final_income'] ?? $income['escrow_amount'] ?? null)),
            'settlement_time'       => $settlementTime, // NULLABLE — null tetap null, TIDAK di-track sebagai missing_financial_fields (memang boleh null)

            'synced_at' => now(),
            'raw_json'  => $income, // murni payload Shopee, TANPA _meta
        ];

        return [
            'values' => $values,
            'missing_financial_fields' => $missingFields,
        ];
    }

    /**
     * "Not-null guard" — SEMUA 15 kolom fee/nominal di marketplace_order_settlements
     * (buyer_payment_amount s/d final_income) adalah NOT NULL di skema saat ini
     * (default '0', TANPA ->nullable() — lihat migration
     * 2026_06_09_013006_create_marketplace_order_settlements_table.php). Insert NULL
     * eksplisit ke kolom ini akan gagal dengan integrity error.
     *
     * decimalValue() SENGAJA tetap mengembalikan null murni untuk "field tidak
     * tersedia dari API" (Koreksi 6) — itu perilaku yang benar & unit-testable
     * secara independen. Guard di sini HANYA memaksa null -> '0.00' di titik
     * PENYIMPANAN, semata-mata karena keterbatasan skema kolom NOT NULL yang
     * sudah ada — BUKAN karena secara semantik "tidak tersedia" == "nol".
     *
     * TECHNICAL DEBT (dicatat, bukan diperbaiki di Fase 1 — di luar scope migration
     * yang disetujui): kalau distingsi "field tidak tersedia" vs "fee memang 0"
     * benar-benar dibutuhkan untuk rekonsiliasi, ke-15 kolom ini perlu migration
     * terpisah untuk dijadikan nullable, disertai keputusan bisnis dulu.
     */
    private function nn(?string $value): string
    {
        return $value ?? '0.00';
    }

    /**
     * Normalisasi nilai nominal finansial dari response API menjadi string decimal
     * aman untuk disimpan (kolom di-cast 'decimal:2' oleh Eloquent).
     *
     * - null / '' → null (BUKAN otomatis 0 — "field tidak tersedia" beda makna
     *   dengan "fee bernilai 0")
     * - int/float/string numerik → string decimal 2 digit
     * - string non-numerik → dianggap invalid, melempar exception (bukan disulap jadi 0)
     *
     * Pakai bcmath (fixed-point, tanpa presisi float) kalau tersedia; fallback ke
     * sprintf kalau bcmath tidak terpasang (bukan dependency wajib project ini).
     */
    private function decimalValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return null;
            }
            if (! is_numeric($trimmed)) {
                throw new \UnexpectedValueException("Nilai nominal bukan angka valid: \"{$value}\"");
            }
            $value = $trimmed;
        } elseif (! is_int($value) && ! is_float($value)) {
            throw new \UnexpectedValueException('Nilai nominal harus null, int, float, atau string numerik.');
        }

        if (function_exists('bcadd')) {
            return bcadd((string) $value, '0', 2);
        }

        // Fallback tanpa bcmath — tetap dijalankan lewat string formatting, bukan
        // dipakai untuk KALKULASI (hanya pembulatan tampilan/penyimpanan akhir).
        return sprintf('%.2f', (float) $value);
    }

    /**
     * Normalisasi timestamp dari Shopee (get_escrow_detail belum diverifikasi resmi
     * apakah selalu epoch detik — lihat AUDIT_FASE1_RANCANGAN_FINAL.md Bagian 10).
     *
     * - null / '' / non-numeric → null
     * - 0 → null (dianggap "tidak ada", bukan epoch 1970-01-01 yang valid secara bisnis)
     * - epoch detik wajar (<=10 digit) → dipakai apa adanya
     * - epoch lebih dari 10 digit → DIASUMSIKAN milidetik, dibagi 1000 (heuristik,
     *   diberi komentar jelas karena belum terverifikasi ke response asli)
     */
    private function normalizeShopeeTimestamp(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $intValue = (int) $value;

        if ($intValue === 0) {
            return null;
        }

        if (abs($intValue) > 9_999_999_999) {
            $intValue = intdiv($intValue, 1000);
        }

        try {
            return Carbon::createFromTimestamp($intValue, config('app.timezone'));
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Log (bukan tabel histori permanen — sengaja ditunda, lihat scope Fase 1)
     * perubahan pada field material saat --resync menimpa settlement yang sudah ada.
     * Hanya field finansial numerik yang dicatat — TIDAK ADA data pembeli/token.
     */
    private function logMaterialChanges(MarketplaceOrder $order, MarketplaceOrderSettlement $existing, array $newValues): void
    {
        $changed = [];

        foreach (self::MATERIAL_SETTLEMENT_FIELDS as $field) {
            $oldVal = (string) $existing->{$field};
            $newVal = (string) ($newValues[$field] ?? '');

            if ($oldVal !== $newVal) {
                $changed[$field] = ['old' => $oldVal, 'new' => $newVal];
            }
        }

        if (! empty($changed)) {
            // Bisa dipicu oleh --resync eksplisit ATAU refresh otomatis settlement yang
            // sebelumnya belum final (settlement_time NULL) — keduanya sama-sama "update
            // terhadap row existing", jadi pesan log tidak mengasumsikan salah satu saja.
            Log::info("[sync-settlements] Nilai settlement order {$order->channel_order_id} berubah (update terhadap row existing)", [
                'store_id' => $order->store_id,
                'changed'  => $changed,
            ]);
        }
    }

    /**
     * Sync campaign ads dari Shopee Ads API ke marketplace_ad_campaigns.
     *
     * Langkah:
     *  1. Cek toggle info (apakah ads aktif)
     *  2. Ambil semua campaign ID via get_product_level_campaign_id_list (auto-paginate)
     *  3. Ambil setting info (nama, status, tipe) via get_product_level_campaign_setting_info
     *  4. Ambil performa harian via get_product_campaign_daily_performance
     *  5. Merge + upsert ke DB
     *
     * @param  string  $dateFrom  Format YYYY-MM-DD
     * @param  string  $dateTo    Format YYYY-MM-DD
     * @return array{synced: int, skipped: int, errors: int, message: string}
     */
    public function syncAdCampaigns(Store $store, string $dateFrom, string $dateTo): array
    {
        $driver = $this->gateway;
        $synced = $skipped = $errors = 0;

        // Shopee Ads API pakai format tanggal DD-MM-YYYY
        $startDDMMYYYY = Carbon::parse($dateFrom)->format('d-m-Y');
        $endDDMMYYYY   = Carbon::parse($dateTo)->format('d-m-Y');

        // ── 1. Cek toggle info ────────────────────────────────────────────────
        $toggleRes = $driver->getShopToggleInfo($store);
        if (! empty($toggleRes['error'])) {
            $errCode = $toggleRes['error'] ?? 'unknown';
            $errMsg  = $toggleRes['message'] ?? $errCode;
            if (str_starts_with((string) $errCode, 'error_auth')) {
                throw new \RuntimeException("Token expired — silakan re-authorize toko ini. ({$errCode})");
            }
            // Jika endpoint tidak tersedia / shop belum aktifkan ads — lanjut saja, bukan fatal
            Log::warning("getShopToggleInfo error store #{$store->id}: {$errMsg}");
        } else {
            $adsEnabled = data_get($toggleRes, 'response.is_ads_on', null);
            if ($adsEnabled === false) {
                return [
                    'synced'  => 0, 'skipped' => 0, 'errors' => 0,
                    'message' => 'Iklan di toko ini sedang dinonaktifkan (toggle off).',
                ];
            }
        }

        // ── 2. Ambil semua campaign ID (auto-paginate) ────────────────────────
        $campaignIds = [];
        $pageNo      = 1;

        do {
            $res = $driver->getCampaignIdList($store, $pageNo, 100);

            if (! empty($res['error'])) {
                $errCode = $res['error'] ?? 'unknown';
                $errMsg  = $res['message'] ?? $errCode;
                $detail  = json_encode($res, JSON_UNESCAPED_UNICODE);
                $this->log($store, 'sync_ad_campaigns', 'failed', "getCampaignIdList error: {$errMsg}", $res);

                if (str_starts_with((string) $errCode, 'error_auth')) {
                    throw new \RuntimeException("Token expired — silakan re-authorize toko ini. ({$errCode})");
                }
                if (in_array($errCode, ['error_not_found', 'error_param'])) {
                    return [
                        'synced'  => 0, 'skipped' => 0, 'errors' => 0,
                        'message' => 'Belum ada campaign iklan di toko ini.',
                    ];
                }
                throw new \RuntimeException("Gagal ambil daftar campaign: {$errMsg} | {$detail}");
            }

            // Response key: campaign_list (array of {campaign_id, ad_type})
            $list        = data_get($res, 'response.campaign_list', []);
            $ids         = array_column($list, 'campaign_id');
            $campaignIds = array_merge($campaignIds, array_map('intval', $ids));

            $hasMore = (bool) data_get($res, 'response.has_next_page', false);
            $pageNo++;
        } while ($hasMore && $pageNo <= 20);

        if (empty($campaignIds)) {
            $this->log($store, 'sync_ad_campaigns', 'success', 'Tidak ada campaign.', []);
            return ['synced' => 0, 'skipped' => 0, 'errors' => 0, 'message' => 'Tidak ada campaign iklan ditemukan.'];
        }

        // ── 3. Ambil performa harian dalam batch 50 ──────────────────────────
        // Response: response.campaign_list[].{campaign_id, ad_name, ad_type,
        //   campaign_placement, metrics_list[].{impression, clicks, expense,
        //   broad_gmv, broad_order, direct_gmv, direct_order, ctr, cr, cpc}}
        $perfMap    = []; // campaign_id => {metrics + meta} (rollup rentang)
        $dailyRows  = []; // baris harian mentah untuk marketplace_ad_campaign_dailies
        $perfErrors = 0;
        foreach (array_chunk($campaignIds, 50) as $chunk) {
            $perfRes = $driver->getCampaignDailyPerformance($store, $chunk, $startDDMMYYYY, $endDDMMYYYY);
            if (! empty($perfRes['error'])) {
                Log::warning("getCampaignDailyPerformance error store #{$store->id}: " .
                    ($perfRes['message'] ?? $perfRes['error']));
                $perfErrors++;
                continue;
            }

            foreach (data_get($perfRes, 'response.campaign_list', []) as $row) {
                $cid = (string) ($row['campaign_id'] ?? '');
                if (! $cid) continue;

                if (! isset($perfMap[$cid])) {
                    $perfMap[$cid] = [
                        'ad_name'            => $row['ad_name'] ?? null,
                        'ad_type'            => $row['ad_type'] ?? null,
                        'campaign_placement' => $row['campaign_placement'] ?? null,
                        'expense'     => 0, 'impression' => 0, 'clicks' => 0,
                        'broad_order' => 0, 'broad_gmv'  => 0, 'direct_gmv' => 0,
                        'direct_order'=> 0, 'cpc_sum'    => 0, 'days'       => 0,
                    ];
                }

                foreach ($row['metrics_list'] ?? [] as $d) {
                    $perfMap[$cid]['expense']      += (float) ($d['expense'] ?? 0);
                    $perfMap[$cid]['impression']   += (int)   ($d['impression'] ?? 0);
                    $perfMap[$cid]['clicks']       += (int)   ($d['clicks'] ?? 0);
                    $perfMap[$cid]['broad_order']  += (int)   ($d['broad_order'] ?? 0);
                    $perfMap[$cid]['broad_gmv']    += (float) ($d['broad_gmv'] ?? 0);
                    $perfMap[$cid]['direct_gmv']   += (float) ($d['direct_gmv'] ?? 0);
                    $perfMap[$cid]['direct_order'] += (int)   ($d['direct_order'] ?? 0);
                    $perfMap[$cid]['cpc_sum']      += (float) ($d['cpc'] ?? 0);
                    $perfMap[$cid]['days']++;

                    // ── Simpan baris harian mentah (grain terkecil) ──────────
                    $rawDate = $d['date'] ?? $d['report_date'] ?? null;
                    $date    = $this->parseAdMetricDate($rawDate);
                    if ($date === null) continue; // tanpa tanggal tak bisa disimpan per hari

                    $key = $cid . '|' . $date;
                    if (! isset($dailyRows[$key])) {
                        $dailyRows[$key] = [
                            'store_id'            => $store->id,
                            'channel_campaign_id' => $cid,
                            'date'                => $date,
                            'ad_type'             => $row['ad_type'] ?? null,
                            'impressions'         => 0, 'clicks' => 0, 'expense' => 0,
                            'broad_order'         => 0, 'broad_gmv' => 0,
                            'direct_order'        => 0, 'direct_gmv' => 0,
                            'cpc'                 => null,
                        ];
                    }
                    $dailyRows[$key]['impressions']  += (int)   ($d['impression'] ?? 0);
                    $dailyRows[$key]['clicks']       += (int)   ($d['clicks'] ?? 0);
                    $dailyRows[$key]['expense']      += (float) ($d['expense'] ?? 0);
                    $dailyRows[$key]['broad_order']  += (int)   ($d['broad_order'] ?? 0);
                    $dailyRows[$key]['broad_gmv']    += (float) ($d['broad_gmv'] ?? 0);
                    $dailyRows[$key]['direct_order'] += (int)   ($d['direct_order'] ?? 0);
                    $dailyRows[$key]['direct_gmv']   += (float) ($d['direct_gmv'] ?? 0);
                    if (isset($d['cpc'])) $dailyRows[$key]['cpc'] = (float) $d['cpc'];
                }
            }
        }

        // ── 3b. Ambil item_id produk per campaign (product ads) ───────────────
        // Endpoint: get_product_level_campaign_setting_info dengan info_type_list=1,2,3,4.
        // Path setting terverifikasi dari API nyata (lihat parseCampaignSetting()).
        // Kegagalan setting TIDAK menggagalkan sync performa (partial-failure aman).
        $settingMap = []; // channel_campaign_id => parsed setting
        foreach (array_chunk($campaignIds, 50) as $chunk) {
            try {
                $setRes = $driver->getCampaignSettingInfo($store, $chunk, [1, 2, 3, 4]);
            } catch (\Throwable $e) {
                Log::warning("getCampaignSettingInfo error store #{$store->id}: " . $e->getMessage());
                continue;
            }
            if (! empty($setRes['error'])) {
                Log::warning("getCampaignSettingInfo error store #{$store->id}: " .
                    ($setRes['message'] ?? $setRes['error']));
                continue;
            }
            foreach (data_get($setRes, 'response.campaign_list', []) as $c) {
                $cid = (string) ($c['campaign_id'] ?? '');
                if (! $cid) continue;
                $settingMap[$cid] = self::parseCampaignSetting($c);
            }
        }

        // ── 3c. Simpan baris harian (idempoten, upsert per store+campaign+date) ─
        if (! empty($dailyRows)) {
            $now = now();
            foreach (array_chunk(array_values($dailyRows), 200) as $chunk) {
                foreach ($chunk as &$r) { $r['created_at'] = $now; $r['updated_at'] = $now; }
                unset($r);
                MarketplaceAdCampaignDaily::upsert(
                    $chunk,
                    ['store_id', 'channel_campaign_id', 'date'],
                    ['ad_type', 'impressions', 'clicks', 'expense', 'broad_order',
                     'broad_gmv', 'direct_order', 'direct_gmv', 'cpc', 'updated_at']
                );
            }
        }

        // ── 3d. Log saldo iklan (burn rate) ───────────────────────────────────
        try {
            $balRes = $driver->getAdsTotalBalance($store);
            $bal    = data_get($balRes, 'response.total_balance', data_get($balRes, 'response.balance'));
            if ($bal !== null) {
                MarketplaceAdsBalanceLog::create(['store_id' => $store->id, 'balance' => (float) $bal]);
            }
        } catch (\Throwable $e) {
            Log::warning("getAdsTotalBalance error store #{$store->id}: " . $e->getMessage());
        }

        $mapper = app(\App\Services\AdItemMapper::class);

        // ── 4. Upsert master campaign (1 baris per campaign) + mapping ────────
        foreach ($campaignIds as $campaignId) {
            try {
                $cid     = (string) $campaignId;
                $metrics = $perfMap[$cid] ?? [];

                $spend       = (float) ($metrics['expense'] ?? 0);
                $gmv         = (float) ($metrics['broad_gmv'] ?? 0);
                $impressions = (int)   ($metrics['impression'] ?? 0);
                $clicks      = (int)   ($metrics['clicks'] ?? 0);
                $orders      = (int)   ($metrics['broad_order'] ?? 0);

                $roas = $spend > 0 ? round($gmv / $spend, 4) : null;
                $ctr  = $impressions > 0 ? round($clicks / $impressions * 100, 4) : null;
                $cvr  = $clicks > 0 ? round($orders / $clicks * 100, 4) : null;
                $cpc  = $clicks > 0 ? round($spend / $clicks, 4) : null;

                // placement: search|discovery → gunakan sebagai campaign_type
                $placement = $metrics['campaign_placement'] ?? $metrics['ad_type'] ?? null;

                $campaign = MarketplaceAdCampaign::firstOrNew([
                    'store_id'            => $store->id,
                    'channel_campaign_id' => $cid,
                ]);

                $campaign->fill([
                    'campaign_name'   => $metrics['ad_name'] ?? $campaign->campaign_name,
                    'campaign_type'   => $placement,
                    'status'          => 'ongoing', // aktif karena muncul di API

                    'spend'       => $spend,
                    'impressions' => $impressions,
                    'clicks'      => $clicks,
                    'ctr'         => $ctr,
                    'orders'      => $orders,
                    'items_sold'  => (int) ($metrics['direct_order'] ?? 0),
                    'gmv'         => $gmv,
                    'direct_gmv'  => (float) ($metrics['direct_gmv'] ?? 0),
                    'roas'        => $roas,
                    'direct_roas' => $spend > 0 && ($metrics['direct_gmv'] ?? 0) > 0
                        ? round((float) $metrics['direct_gmv'] / $spend, 4)
                        : null,
                    'cpc'         => $cpc,
                    'cvr'         => $cvr,

                    'last_synced_range_from' => Carbon::parse($dateFrom)->toDateString(),
                    'last_synced_range_to'   => Carbon::parse($dateTo)->toDateString(),
                    'raw_json'  => $metrics,
                    'synced_at' => now(),
                ]);

                // Setting campaign: HANYA di-set bila setting berhasil dibaca.
                // Bila gagal (tak ada di $settingMap), kolom setting lama TIDAK
                // ditimpa null (partial-failure aman).
                if (isset($settingMap[$cid])) {
                    $s = $settingMap[$cid];
                    $campaign->fill([
                        'channel_item_id'    => $s['channel_item_id'] ?? $campaign->channel_item_id,
                        'ad_type'            => $s['ad_type'] ?? $campaign->ad_type,
                        'bidding_method'     => $s['bidding_method'] ?? $campaign->bidding_method,
                        'campaign_status'    => $s['campaign_status'] ?? $campaign->campaign_status,
                        'campaign_placement' => $s['campaign_placement'] ?? $campaign->campaign_placement,
                        'campaign_budget'    => $s['campaign_budget'] ?? $campaign->campaign_budget,
                        'target_roas'        => $s['target_roas'],   // 0 valid; null bila tak ada auto_bidding_info
                        'started_at'         => $s['started_at'] ?? $campaign->started_at,
                        'ended_at'           => $s['ended_at'],      // null legit (end_time=0)
                        'raw_setting_payload'=> $s['raw_setting_payload'],
                        'setting_synced_at'  => now(),
                    ]);
                    if (! empty($s['ad_name'])) {
                        $campaign->campaign_name = $s['ad_name']; // nama dari setting lebih akurat
                    }
                }

                // Resolusi mapping ke item internal (hormati override manual).
                if ($campaign->mapping_status !== 'manual') {
                    $mapper->applyTo($campaign);
                }

                $campaign->save();

                $synced++;
            } catch (\Throwable $e) {
                Log::error("Gagal simpan campaign #{$campaignId} store #{$store->id}: " . $e->getMessage());
                $errors++;
            }
        }

        $skipped = max(0, count($campaignIds) - $synced - $errors);
        $message = "Ad campaigns synced: {$synced}, skipped: {$skipped}, errors: {$errors}"
            . ($perfErrors > 0 ? " (perf batch errors: {$perfErrors})" : '') . '.';
        $this->log($store, 'sync_ad_campaigns', $errors > 0 && $synced === 0 ? 'failed' : 'success', $message, [
            'date_from' => $dateFrom, 'date_to' => $dateTo,
            'synced' => $synced, 'skipped' => $skipped,
            'errors' => $errors, 'perf_batch_errors' => $perfErrors,
        ]);

        return compact('synced', 'skipped', 'errors', 'message');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Parse satu node campaign dari get_product_level_campaign_setting_info
     * (info_type_list=1,2,3,4) menjadi field ternormalisasi.
     *
     * Path terverifikasi dari API nyata (campaign 477707399):
     *   common_info.item_id_list.0  ← path item_id yang BENAR (prioritas 1)
     *   common_info.{ad_type,ad_name,bidding_method,campaign_status,
     *                campaign_placement,campaign_budget,campaign_duration.*}
     *   auto_bidding_info.roas_target
     *
     * Static + tanpa side-effect agar mudah diuji unit.
     */
    public static function parseCampaignSetting(array $node): array
    {
        $common = data_get($node, 'common_info') ?? [];
        $auto   = data_get($node, 'auto_bidding_info'); // null bila bukan auto bidding

        // Path NYATA diprioritaskan; fallback untuk struktur lain/lama.
        $itemId = data_get($node, 'common_info.item_id_list.0')
            ?? data_get($node, 'item_id')
            ?? data_get($node, 'item_id_list.0');

        $tz    = config('app.timezone', 'Asia/Jakarta');
        $start = (int) data_get($common, 'campaign_duration.start_time', 0);
        $end   = (int) data_get($common, 'campaign_duration.end_time', 0);

        return [
            'channel_item_id'     => $itemId !== null ? (int) $itemId : null,
            'ad_type'             => data_get($common, 'ad_type'),
            'ad_name'             => data_get($common, 'ad_name'),
            'bidding_method'      => data_get($common, 'bidding_method'),
            'campaign_status'     => data_get($common, 'campaign_status'),
            'campaign_placement'  => data_get($common, 'campaign_placement'),
            'campaign_budget'     => data_get($common, 'campaign_budget'),
            'target_roas'         => $auto ? data_get($auto, 'roas_target') : null,
            'started_at'          => $start > 0 ? Carbon::createFromTimestamp($start, $tz) : null,
            'ended_at'            => $end   > 0 ? Carbon::createFromTimestamp($end, $tz)   : null, // end_time=0 → null
            'raw_setting_payload' => self::stripSensitive($node),
        ];
    }

    /**
     * Buang key sensitif (defensif) sebelum menyimpan raw payload.
     * Setting node normalnya tidak memuat credential, tapi ini jaminan aman.
     */
    public static function stripSensitive($data)
    {
        static $blocked = ['access_token', 'refresh_token', 'partner_key', 'partner_key_v2', 'sign', 'credentials'];

        if (! is_array($data)) return $data;

        $out = [];
        foreach ($data as $k => $v) {
            if (is_string($k) && in_array($k, $blocked, true)) continue;
            $out[$k] = self::stripSensitive($v);
        }
        return $out;
    }

    /**
     * Parse tanggal metric harian dari Shopee Ads API ke 'Y-m-d'.
     * Menerima 'DD-MM-YYYY', 'YYYY-MM-DD', atau unix timestamp. Null jika gagal.
     */
    private function parseAdMetricDate($raw): ?string
    {
        if (empty($raw)) return null;

        if (is_numeric($raw)) {
            try { return Carbon::createFromTimestamp((int) $raw)->toDateString(); }
            catch (\Throwable $e) { return null; }
        }

        $raw = trim((string) $raw);
        // DD-MM-YYYY (format request Shopee Ads)
        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $raw, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        try { return Carbon::parse($raw)->toDateString(); }
        catch (\Throwable $e) { return null; }
    }

    private function upsertOrders(Store $store, array $details, bool $dryRun = false): array
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF');
        }

        $outerStats = ['new' => 0, 'updated' => 0, 'sku_empty' => 0, 'mapping_not_found' => 0, 'missing_hpp' => 0, 'ready' => 0, 'incomplete' => 0, 'skipped' => 0];

        try {
        // Proses per-batch kecil; tiap batch transaksi sendiri agar kunci tulis SQLite tidak
        // ditahan lama sepanjang seluruh sync (mengurangi "database is locked" saat bersamaan).
        foreach (array_chunk($details, 50) as $chunk) {
        DB::transaction(function () use ($store, $chunk, &$outerStats, $dryRun) {

            foreach ($chunk as $detail) {
                if (empty($detail['order_sn'])) continue;

                $orderedAt   = ! empty($detail['create_time']) ? now()->setTimestamp((int) $detail['create_time']) : null;
                $orderDate   = $orderedAt ? $orderedAt->toDateTimeString() : now()->toDateTimeString();
                $orderStatus = $detail['order_status'] ?? null;

                // ── Buyer & alamat (dari recipient_address) ──────────────────
                $addr            = $detail['recipient_address'] ?? [];
                $buyerName       = $addr['name']         ?? $detail['buyer_username'] ?? null;
                $buyerPhone      = $addr['phone_number'] ?? null;
                $shippingAddress = $addr['full_address'] ?? (isset($addr['street'], $addr['town'])
                    ? trim(($addr['street'] ?? '') . ', ' . ($addr['town'] ?? '') . ', ' . ($addr['district'] ?? ''))
                    : null);
                $shippingCity     = $addr['city']     ?? null;
                $shippingProvince = $addr['state']    ?? null;
                $shippingPostal   = $addr['zip_code'] ?? null;

                // ── Kurir & resi (dari package_list) ─────────────────────────
                $packages  = $detail['package_list'] ?? [];
                $firstPkg  = $packages[0] ?? [];
                $trackingNo      = $firstPkg['tracking_no'] ?? $firstPkg['tracking_number'] ?? null;
                if (!$trackingNo && !empty($firstPkg['package_number']) && !str_starts_with(strtoupper($firstPkg['package_number']), 'OFG')) {
                    $trackingNo = $firstPkg['package_number'];
                }
                
                if ($trackingNo && str_starts_with(strtoupper($trackingNo), 'OFG')) {
                    $trackingNo = null;
                }
                $shippingCarrier = $firstPkg['shipping_carrier']
                    ?? $detail['shipping_carrier']
                    ?? $detail['checkout_shipping_carrier']
                    ?? null;
                
                $logisticsStatus = $firstPkg['logistics_status'] ?? '';
                
                // Jika status dari Shopee adalah READY_TO_SHIP, tetapi logistiknya sudah diatur (REQUEST_CREATED dsb),
                // maka secara logika aplikasi kita (GFID) statusnya adalah PROCESSED
                // [UPDATE]: User meminta agar pesanan instan (atau pesanan yang otomatis ter-arrange)
                // TETAP dibiarkan sebagai READY_TO_SHIP di GFID agar bisa muncul di tab "Perlu Dikirim"
                // dan mereka bisa secara manual meng-klik "Atur Pengiriman" (Acknowledge) di UI.
                // if ($orderStatus === 'READY_TO_SHIP' && in_array($logisticsStatus, ['LOGISTICS_REQUEST_CREATED', 'LOGISTICS_READY_TO_SHIP'])) {
                //     $orderStatus = 'PROCESSED';
                // }

                // ── Finansial ─────────────────────────────────────────────────
                $totalAmount       = (float) ($detail['total_amount'] ?? 0);
                $shippingFee       = (float) ($detail['actual_shipping_fee']
                    ?? $detail['buyer_paid_shipping_fee']
                    ?? $detail['estimated_shipping_fee']
                    ?? 0);
                $subtotalItems     = collect($detail['item_list'] ?? [])->sum(function ($item) {
                    return (float) ($item['model_original_price'] ?? $item['model_discounted_price'] ?? 0)
                        * (int) ($item['model_quantity_purchased'] ?? $item['active_qty'] ?? 0);
                });

                // ── Status legacy mapping ─────────────────────────────────────
                $statusMap  = [
                    'READY_TO_SHIP' => 'packed',
                    'PROCESSED'     => 'packed',
                    'SHIPPED'       => 'shipped',
                    'COMPLETED'     => 'completed',
                    'CANCELLED'     => 'cancelled',
                    'IN_CANCEL'     => 'cancelled',
                    'UNPAID'        => 'new',
                    'TO_CONFIRM_RECEIVE' => 'shipped',
                ];
                $statusLegacy = $statusMap[$orderStatus] ?? 'new';

                if ($dryRun) {
                    $existingOrder = MarketplaceOrder::where('store_id', $store->id)->where('channel_order_id', $detail['order_sn'])->first();
                    if (!$existingOrder) {
                        $outerStats['new']++;
                    } else {
                        $outerStats['updated']++;
                    }
                    
                    $orderHasIncomplete = false;
                    foreach (($detail['item_list'] ?? []) as $item) {
                        $mappingAttrs = $this->issueService->buildMappingAttributes(
                            modelSku:    $item['model_sku'] ?? null,
                            itemSku:     $item['item_sku']  ?? null,
                            externalSku: null,
                            channelCode: $store->channel?->code,
                            itemName:    $item['item_name'] ?? null,
                            variantName: $item['model_name'] ?? null,
                        );
                        if ($mappingAttrs['mapping_status'] === 'marketplace_sku_empty') $outerStats['sku_empty']++;
                        if ($mappingAttrs['mapping_status'] === 'mapping_not_found')     $outerStats['mapping_not_found']++;
                        if ($mappingAttrs['cost_status']    === 'missing_hpp')           $outerStats['missing_hpp']++;
                        if (($mappingAttrs['data_status']   ?? null) === 'incomplete')   $orderHasIncomplete = true;
                    }
                    if ($orderHasIncomplete) {
                        $outerStats['incomplete']++;
                    } elseif (in_array($orderStatus, ['READY_TO_SHIP', 'PROCESSED'])) {
                        $outerStats['ready']++;
                    }
                    continue;
                }

                $order = MarketplaceOrder::updateOrCreate(
                    ['store_id' => $store->id, 'channel_order_id' => $detail['order_sn']],
                    [
                        // Identifiers
                        'external_order_id' => $detail['order_sn'],
                        'booking_sn'        => $detail['booking_sn'] ?? null,
                        'order_date'        => $orderDate,
                        'ordered_at'        => $orderedAt,

                        // Status
                        'order_status'  => $orderStatus,
                        'status'        => $statusLegacy,

                        // Buyer
                        'buyer_username' => $detail['buyer_username'] ?? null,
                        'buyer_name'     => $buyerName,
                        'buyer_phone'    => $buyerPhone,

                        // Pengiriman
                        'shipping_carrier'      => $shippingCarrier,
                        'shipping_courier_code' => $shippingCarrier,
                        'shipping_awb_no'       => $trackingNo,
                        'shipping_address'      => $shippingAddress,
                        'shipping_city'         => $shippingCity,
                        'shipping_province'     => $shippingProvince,
                        'shipping_postal_code'  => $shippingPostal,

                        // Finansial
                        'payment_method'       => $detail['payment_method'] ?? null,
                        'total_amount'         => $totalAmount,
                        'total_paid_customer'  => $totalAmount,
                        'subtotal_items'       => $subtotalItems,
                        'shipping_fee_customer'=> $shippingFee,
                        'currency'             => $detail['currency'] ?? 'IDR',

                        // Meta
                        'synced_at' => now(),
                        'raw_json'  => $detail,
                    ]
                );

                if ($order->wasRecentlyCreated) {
                    $outerStats['new']++;
                } else {
                    $outerStats['updated']++;
                }

                // ── [FEATURE] Placeholder Settlement ──────────────────────────
                // Supaya order yang belum COMPLETED tetap masuk ke tab "Belum Cair" 
                // dengan estimasi nominal sementara (dan settlement_time = null).
                if (!in_array($orderStatus, ['CANCELLED', 'BATAL', 'RETURNED'])) {
                    $calcSellerDiscount = 0;
                    if (!empty($detail['item_list'])) {
                        foreach ($detail['item_list'] as $itm) {
                            $orig = $itm['model_original_price'] ?? $itm['original_price'] ?? 0;
                            $disc = $itm['model_discounted_price'] ?? $itm['discounted_price'] ?? $orig;
                            $qty = $itm['model_quantity_purchased'] ?? $itm['quantity_purchased'] ?? 1;
                            if ($disc > 0 && $orig > $disc) {
                                $calcSellerDiscount += ($orig - $disc) * $qty;
                            }
                        }
                    }

                    MarketplaceOrderSettlement::updateOrCreate(
                        ['store_id' => $store->id, 'channel_order_id' => $detail['order_sn'], 'settlement_time' => null],
                        [
                            'order_id'             => $order->id,
                            'buyer_payment_amount' => $totalAmount,
                            'raw_json'             => ['seller_discount' => $calcSellerDiscount],
                        ]
                    );
                }

                // Pre-download resi: HANYA order segar (≤7 hari — backfill histori tidak
                // butuh label) dan yang label-nya belum ter-cache. Job-nya sendiri unik
                // per (store, order_sn), jadi sync berulang tidak menumpuk duplikat.
                if (in_array($orderStatus, ['READY_TO_SHIP', 'PROCESSED'])) {
                    $orderedTs = isset($detail['create_time']) ? (int) $detail['create_time'] : null;
                    $isFresh = $orderedTs === null || $orderedTs >= now()->subDays(7)->timestamp;
                    if ($isFresh) {
                        $labelDisk = \Illuminate\Support\Facades\Storage::disk('local');
                        $sn = $detail['order_sn'];
                        if (! $labelDisk->exists("shipping_labels/{$store->id}/{$sn}.pdf.gz")
                            && ! $labelDisk->exists("shipping_labels/{$store->id}/{$sn}_nocard.pdf.gz")) {
                            \App\Jobs\DownloadMarketplaceShippingDocumentJob::dispatch($store->id, $sn);
                        }
                    }
                }

                $existingItems = $order->items->keyBy(function ($item) {
                    return ($item->external_item_id ?: 'null') . '_' . ($item->external_model_id ?: 'null') . '_' . ($item->marketplace_sku ?: 'null');
                });

                // Channel code untuk SKU Mapping lookup
                $channelCode = $store->channel?->code;
                $orderStatus = $detail['order_status'] ?? '';
                
                // --- PATCH: Preserve Webhook "TO_RETURN" Status ---
                // GetOrderDetail API often lags behind the logistics webhook. 
                // If the webhook already promoted this to TO_RETURN, prevent GetOrderDetail from downgrading it to SHIPPED.
                $existingOrder = MarketplaceOrder::where('store_id', $store->id)
                    ->where('channel_order_id', $detail['order_sn'])
                    ->first(['id', 'order_status']);
                    
                if ($existingOrder && $existingOrder->order_status === 'TO_RETURN' && $orderStatus === 'SHIPPED') {
                    $orderStatus = 'TO_RETURN';
                }
                
                // Jika status dari Shopee adalah READY_TO_SHIP, tetapi order lokal sudah PROCESSED
                // (biasanya karena Acknowledge manual di GFID), cegah downgrade
                if ($existingOrder && $existingOrder->order_status === 'PROCESSED' && $orderStatus === 'READY_TO_SHIP') {
                    $orderStatus = 'PROCESSED';
                }
                
                $orderHasIncomplete = false;

                // Penomoran baris per pasangan item+model: bundle/add-on deal bisa
                // punya item_id+model_id yang sama dalam 1 order — tanpa line_no
                // baris kedua akan meng-overwrite baris pertama.
                $lineSeq     = [];
                $keptItemIds = [];

                foreach (($detail['item_list'] ?? []) as $item) {
                    $itemExtId = isset($item['item_id']) ? (string) $item['item_id'] : null;
                    $modelExtId = isset($item['model_id']) ? (string) $item['model_id'] : null;
                    $mpSku = $item['model_sku'] ?? $item['item_sku'] ?? null;
                    
                    $itemKey = ($itemExtId ?: 'null') . '_' . ($modelExtId ?: 'null') . '_' . ($mpSku ?: 'null');
                    $existingItem = $existingItems->get($itemKey);

                    // Resolusi mapping + HPP
                    $mappingAttrs = $this->issueService->buildMappingAttributes(
                        modelSku:    $item['model_sku'] ?? null,
                        itemSku:     $item['item_sku']  ?? null,
                        externalSku: null,
                        channelCode: $channelCode,
                        itemName:    $item['item_name'] ?? null,
                        variantName: $item['model_name'] ?? null,
                    );
                    
                    // Proteksi HPP manual override
                    if ($existingItem && $existingItem->hpp_snapshot > 0) {
                        $mappingAttrs['hpp_snapshot'] = $existingItem->hpp_snapshot;
                        $mappingAttrs['cost_status'] = $existingItem->cost_status;
                    }

                    // Track item-level stats
                    if ($mappingAttrs['mapping_status'] === 'marketplace_sku_empty') $outerStats['sku_empty']++;
                    if ($mappingAttrs['mapping_status'] === 'mapping_not_found')     $outerStats['mapping_not_found']++;
                    if ($mappingAttrs['cost_status']    === 'missing_hpp')           $outerStats['missing_hpp']++;
                    if (($mappingAttrs['data_status']   ?? null) === 'incomplete')   $orderHasIncomplete = true;

                    $pairKey = ($itemExtId ?: 'null') . '_' . ($modelExtId ?: 'null');
                    $lineSeq[$pairKey] = ($lineSeq[$pairKey] ?? 0) + 1;

                    $itemRow = MarketplaceOrderItem::updateOrCreate(
                        [
                            'marketplace_order_id' => $order->id,
                            'external_item_id'     => $itemExtId,
                            'external_model_id'    => $modelExtId,
                            'line_no'              => $lineSeq[$pairKey],
                        ],
                        array_merge([
                            'order_id'             => $order->id,
                            'item_name'            => $item['item_name'] ?? '-',
                            'item_sku'             => $item['item_sku']  ?? null,
                            'model_sku'            => $item['model_sku'] ?? null,
                            'variant_name'         => $item['model_name'] ?? null,
                            'qty'                  => (int) ($item['model_quantity_purchased'] ?? $item['active_qty'] ?? 0),
                            'price'                => $item['model_original_price'] ?? $item['model_discounted_price'] ?? 0,
                            'image_url'            => data_get($item, 'image_info.image_url'),
                            'raw_json'             => $item,
                        ], $mappingAttrs)
                    );

                    $keptItemIds[] = $itemRow->id;
                    $existingItems->forget($itemKey);
                }

                // Bersihkan baris item lama yang tidak ada lagi di payload terbaru
                // (termasuk baris legacy dengan line_no NULL agar tidak dobel)
                if (!empty($keptItemIds)) {
                    $order->items()->whereNotIn('id', $keptItemIds)->delete();
                }

                // Track order-level ready / incomplete
                if ($orderHasIncomplete) {
                    $outerStats['incomplete']++;
                } elseif (in_array($orderStatus, ['READY_TO_SHIP', 'PROCESSED'])) {
                    $outerStats['ready']++;
                }
            }
        });
        } // akhir per-batch (array_chunk)

        if (!$dryRun) {
            $store->update(['last_synced_at' => now()]);
        }
        } finally {
            // WAJIB di-finally: kalau transaction melempar exception,
            // foreign_keys harus tetap dinyalakan kembali di koneksi ini.
            if (DB::connection()->getDriverName() === 'sqlite') {
                DB::statement('PRAGMA foreign_keys=ON');
            }
        }

        return $outerStats;
    }

    private function autoCreateFulfillments(Store $store): void
    {
        // Hanya buat fulfillment draft untuk order yang SEMUA item-nya data_status = valid
        // PROCESSED ikut: order bisa masuk GFID sudah berstatus PROCESSED
        // (resi diproses langsung di Seller Centre sebelum GFID sempat sync)
        $orders = MarketplaceOrder::where('store_id', $store->id)
            ->whereIn('order_status', ['READY_TO_SHIP', 'PROCESSED'])
            ->whereDoesntHave('fulfillment')
            ->whereDoesntHave('items', fn ($q) => $q->where('data_status', 'incomplete'))
            ->get();

        foreach ($orders as $order) {
            try {
                $this->fulfillment->createDraft($order);
            } catch (\Throwable $e) {
                Log::warning("Gagal buat fulfillment draft order #{$order->id}: " . $e->getMessage());
            }
        }
    }

    private function log(Store $store, string $action, string $status, string $message, array $payload = []): void
    {
        MarketplaceSyncLog::create([
            'store_id' => $store->id,
            'action'   => $action,
            'status'   => $status,
            'message'  => $message,
            'payload'  => $payload,
        ]);
    }
}
