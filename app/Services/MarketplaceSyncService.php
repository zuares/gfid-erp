<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\Item;
use App\Models\MarketplaceAdCampaign;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceOrderSettlement;
use App\Models\MarketplaceSyncLog;
use App\Models\SkuMapping;
use App\Models\Store;
use App\Services\Channels\ChannelManager;
use App\Services\Channels\Contracts\MarketplaceChannel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarketplaceSyncService
{
    public function __construct(
        protected ChannelManager $manager,
        protected OrderFulfillmentService $fulfillment,
        protected MarketplaceIssueService $issueService = new MarketplaceIssueService(),
    ) {}

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
     * Sync orders dari marketplace ke DB lokal.
     * Mengembalikan array: found, synced, order_sn_list, message.
     * Melempar \RuntimeException jika API error.
     */
    public function syncOrders(Store $store, int $timeFrom, int $timeTo, int $pageSize = 50, bool $dryRun = false): array
    {
        $driver = $this->manager->driver($store);

        $orderSnList = [];
        
        // Untuk Shopee, kita tarik spesifik per status agar tidak ada order (termasuk kilat) yang terlewat
        // Catatan: TO_CONFIRM_RECEIVE bukan parameter valid untuk filter di get_order_list (akan memicu error API)
        $statuses = $store->channel?->code === 'shopee'
            ? ['UNPAID', 'READY_TO_SHIP', 'PROCESSED', 'SHIPPED', 'COMPLETED', 'IN_CANCEL', 'CANCELLED']
            : ['']; // Channel lain panggil tanpa filter status

        foreach ($statuses as $status) {
            $cursor = '';
            $hasMore = true;

            // 1. Ambil daftar order per status
            while ($hasMore) {
                $listResponse = $driver->getOrders($store, $timeFrom, $timeTo, $pageSize, $cursor, $status);

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

        $orderSnList = array_unique($orderSnList);

        if (empty($orderSnList)) {
            $this->log($store, 'sync_orders', 'success', 'Tidak ada order ditemukan.', ['time_from' => $timeFrom, 'time_to' => $timeTo]);
            return ['found' => 0, 'synced' => 0, 'order_sn_list' => [], 'message' => 'Tidak ada order ditemukan di rentang tanggal ini.'];
        }

        // 2. Ambil detail order per chunk
        $details = [];
        $failedChunks = 0;
        foreach (array_chunk($orderSnList, 50) as $chunk) {
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
    public function syncOrdersBySn(Store $store, array $orderSnList): array
    {
        $orderSnList = array_values(array_unique(array_filter($orderSnList)));
        if (empty($orderSnList)) {
            return ['found' => 0, 'new' => 0, 'updated' => 0];
        }

        $driver  = $this->manager->driver($store);
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
     * Maksimal percobaan panggilan get_escrow_detail (1 percobaan awal + 2 retry).
     */
    private const ESCROW_MAX_ATTEMPTS = 3;

    /**
     * Maksimal detik menunggu antar-retry, dibatasi supaya Retry-After yang besar
     * dari Shopee tidak menahan proses batch terlalu lama.
     */
    private const ESCROW_MAX_RETRY_SLEEP_SECONDS = 10;

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
        'commission_fee', 'service_fee', 'transaction_fee', 'seller_voucher',
        'shipping_fee_subsidy', 'escrow_tax', 'final_income', 'settlement_time',
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
    ): array {
        $driver = $this->manager->driver($store);

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

        foreach ($orders as $order) {
            $processed++;
            $lastProcessedId = $order->id;

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
                $retryResult = $this->getEscrowDetailWithRetry($driver, $store, $order->channel_order_id);
                $response = $retryResult['payload'];
                $lastCallMeta = $retryResult['meta'];

                // 2) Validasi response SEBELUM mapping — tidak langsung anggap sukses
                //    hanya karena tidak ada exception.
                $validation = $this->validateEscrowIncome($response);

                if ($validation['status'] === 'skipped') {
                    // Kondisi bisnis (mis. response kosong / belum eligible di sisi Shopee)
                    // — bukan error, dan TIDAK membuat settlement kosong/nol.
                    $skipped++;
                    Log::info("[sync-settlements] Order {$order->channel_order_id} dilewati: {$validation['message']}");
                    continue;
                }

                if ($validation['status'] === 'error') {
                    $errors++;
                    $failedOrderIds[] = $order->channel_order_id;
                    Log::warning("[sync-settlements] Order {$order->channel_order_id} gagal validasi ({$validation['reason']}): {$validation['message']}");
                    
                    if ($validation['reason'] === 'order_not_found') {
                        $order->update([
                            'settlement_sync_error_code' => 'order_not_found',
                            'settlement_sync_failed_at' => now(),
                        ]);
                    }
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
    private function getEscrowDetailWithRetry(MarketplaceChannel $driver, Store $store, string $orderSn): array
    {
        $attempt = 0;
        $response = [];
        $startedAt = hrtime(true);

        while ($attempt < self::ESCROW_MAX_ATTEMPTS) {
            $attempt++;

            try {
                $response = $driver->getEscrowDetail($store, $orderSn);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $response = [
                    'error'   => 'connection_exception',
                    'message' => $e->getMessage(),
                    '_meta'   => ['http_status' => null, 'retry_after' => null],
                ];
            }

            $httpStatus = $response['_meta']['http_status'] ?? null;
            $isConnectionIssue = ($response['error'] ?? null) === 'connection_exception';
            $isTransient = $isConnectionIssue || $httpStatus === 429 || ($httpStatus !== null && $httpStatus >= 500);

            if (! $isTransient || $attempt >= self::ESCROW_MAX_ATTEMPTS) {
                break;
            }

            $retryAfter = $response['_meta']['retry_after'] ?? null;
            $sleepSeconds = is_numeric($retryAfter) ? (int) $retryAfter : (2 * $attempt);
            $sleepSeconds = min($sleepSeconds, self::ESCROW_MAX_RETRY_SLEEP_SECONDS);

            if ($sleepSeconds > 0) {
                sleep($sleepSeconds);
            }
        }

        $durationMs = (hrtime(true) - $startedAt) / 1_000_000;

        return [
            // Payload API asli (ShopeeChannel::withHttpMeta() masih menyisipkan _meta di
            // sini) — TIDAK diubah, supaya validateEscrowIncome()/mapEscrowSettlement()
            // yang sudah ada tidak perlu berubah. _meta di payload ini TIDAK PERNAH ikut
            // ke raw_json (mapEscrowSettlement() hanya mengambil node order_income).
            'payload' => $response,
            'meta'    => [
                'attempts'    => $attempt,
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
     *  - buyer_payment_amount: `buyer_total_amount` TERVERIFIKASI ada (81912 di UAT).
     *    `buyer_payment_amount`/`buyer_paid_amount` tidak pernah muncul di response.
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
                $income['cost_of_goods_sold'] ?? $income['order_selling_price'] ?? $income['buyer_total_amount'] ?? $income['buyer_payment_amount'] ?? null
            )),

            // Fee marketplace
            'commission_fee'        => $val('commission_fee', $this->decimalValue($income['commission_fee'] ?? null)),
            'service_fee'           => $val('service_fee', $this->decimalValue($income['service_fee'] ?? null)),
            'transaction_fee'       => $val('transaction_fee', $this->decimalValue(
                ($income['seller_transaction_fee'] ?? 0) + ($income['seller_order_processing_fee'] ?? 0) ?: ($income['transaction_fee'] ?? null)
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
     * "Not-null guard" — SEMUA 13 kolom fee/nominal di marketplace_order_settlements
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
     * benar-benar dibutuhkan untuk rekonsiliasi, ke-13 kolom ini perlu migration
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
        $driver = $this->manager->driver($store);
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
        $perfMap    = []; // campaign_id => {metrics + meta}
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
                }
            }
        }

        // ── 4. Upsert ke DB ───────────────────────────────────────────────────
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

                MarketplaceAdCampaign::updateOrCreate(
                    [
                        'store_id'            => $store->id,
                        'channel_campaign_id' => $cid,
                        'report_date_from'    => Carbon::parse($dateFrom)->toDateString(),
                        'report_date_to'      => Carbon::parse($dateTo)->toDateString(),
                    ],
                    [
                        'campaign_name' => $metrics['ad_name'] ?? null,
                        'campaign_type' => $placement,
                        'status'        => 'ongoing', // aktif karena muncul di API

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

                        'raw_json'  => $metrics,
                        'synced_at' => now(),
                    ]
                );

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

                if (in_array($orderStatus, ['READY_TO_SHIP', 'PROCESSED'])) {
                    \App\Jobs\DownloadMarketplaceShippingDocumentJob::dispatch($store->id, $detail['order_sn']);
                }

                $existingItems = $order->items->keyBy(function ($item) {
                    return ($item->external_item_id ?: 'null') . '_' . ($item->external_model_id ?: 'null') . '_' . ($item->marketplace_sku ?: 'null');
                });

                // Channel code untuk SKU Mapping lookup
                $channelCode = $store->channel?->code;
                $orderStatus = $detail['order_status'] ?? '';
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
