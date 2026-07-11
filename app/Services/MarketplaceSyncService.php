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
            ? ['UNPAID', 'READY_TO_SHIP', 'PROCESSED', 'SHIPPED', 'COMPLETED']
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
     * Sync settlement / escrow data per order dari marketplace.
     * Tarik order-order yang belum punya settlement (atau dalam rentang waktu),
     * fetch escrow detail satu per satu, simpan ke marketplace_order_settlements.
     *
     * @return array{synced: int, skipped: int, errors: int, message: string}
     */
    public function syncSettlements(Store $store, ?int $timeFrom = null, ?int $timeTo = null): array
    {
        $driver = $this->manager->driver($store);

        // Ambil order yang perlu di-settle: COMPLETED atau SHIPPED, belum ada settlement
        $query = MarketplaceOrder::where('store_id', $store->id)
            ->whereIn('order_status', ['COMPLETED', 'SHIPPED', 'TO_CONFIRM_RECEIVE'])
            ->whereDoesntHave('settlement');

        if ($timeFrom) {
            $query->where('ordered_at', '>=', now()->setTimestamp($timeFrom));
        }
        if ($timeTo) {
            $query->where('ordered_at', '<=', now()->setTimestamp($timeTo));
        }

        $orders  = $query->limit(200)->get();
        $synced  = 0;
        $skipped = 0;
        $errors  = 0;

        foreach ($orders as $order) {
            try {
                $response = $driver->getEscrowDetail($store, $order->channel_order_id);

                if (! empty($response['error']) || empty($response['response'])) {
                    $errMsg = $response['message'] ?? ($response['error'] ?? 'Unknown error');
                    // Tidak fatal — skip dan catat
                    Log::warning("Escrow detail error order {$order->channel_order_id}: {$errMsg}");
                    $errors++;
                    continue;
                }

                $income = $response['response']['order_income'] ?? $response['response'] ?? [];

                if (empty($income)) {
                    $skipped++;
                    continue;
                }

                $settlementTime = ! empty($income['escrow_release_time'])
                    ? now()->setTimestamp((int) $income['escrow_release_time'])
                    : (! empty($income['settlement_time']) ? now()->setTimestamp((int) $income['settlement_time']) : null);

                MarketplaceOrderSettlement::updateOrCreate(
                    ['channel_order_id' => $order->channel_order_id],
                    [
                        'store_id'   => $store->id,
                        'order_id'   => $order->id,

                        // Pembayaran customer
                        'buyer_payment_amount'  => (float) ($income['buyer_payment_amount'] ?? $income['buyer_paid_amount'] ?? 0),

                        // Fee marketplace
                        'commission_fee'        => (float) ($income['commission_fee'] ?? 0),
                        'service_fee'           => (float) ($income['service_fee'] ?? $income['credit_card_promotion'] ?? 0),
                        'transaction_fee'       => (float) ($income['transaction_fee'] ?? 0),

                        // Voucher & diskon
                        'seller_voucher'        => (float) ($income['seller_voucher_rebate'] ?? $income['seller_voucher'] ?? 0),
                        'seller_coin_cash_back' => (float) ($income['seller_absorbed_coin_discount'] ?? $income['seller_coin_cash_back'] ?? 0),

                        // Ongkir
                        'actual_shipping_fee'   => (float) ($income['actual_shipping_fee'] ?? $income['estimated_shipping_fee'] ?? 0),
                        'shipping_fee_subsidy'  => (float) ($income['shopee_shipping_rebate'] ?? $income['shipping_fee_rebate'] ?? 0),
                        'reverse_shipping_fee'  => (float) ($income['reverse_shipping_fee'] ?? 0),

                        // Campaign & lainnya
                        'activity_fee'          => (float) ($income['activity_fee'] ?? $income['ams_commission_fee'] ?? 0),
                        'drc_adjustable_refund' => (float) ($income['drc_adjustable_refund'] ?? $income['seller_return_refund_amount'] ?? 0),
                        'escrow_tax'            => (float) ($income['escrow_tax'] ?? 0),

                        // Dana cair
                        'final_income'          => (float) ($income['final_income'] ?? $income['escrow_amount'] ?? 0),
                        'settlement_time'       => $settlementTime,

                        'synced_at' => now(),
                        'raw_json'  => $income,
                    ]
                );

                $synced++;

            } catch (\Throwable $e) {
                Log::error("Gagal sync settlement order {$order->channel_order_id}: " . $e->getMessage());
                $errors++;
            }
        }

        $message = "Settlement synced: {$synced}, skipped: {$skipped}, errors: {$errors}.";
        $this->log($store, 'sync_settlements', $errors > 0 && $synced === 0 ? 'failed' : 'success', $message, [
            'synced'  => $synced,
            'skipped' => $skipped,
            'errors'  => $errors,
        ]);

        return compact('synced', 'skipped', 'errors', 'message');
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

        DB::transaction(function () use ($store, $details, &$outerStats, $dryRun) {

            foreach ($details as $detail) {
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
                $trackingNo      = $firstPkg['tracking_no'] ?? $firstPkg['tracking_number'] ?? null; // Added tracking_number for V2 fallback
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

                    MarketplaceOrderItem::updateOrCreate(
                        [
                            'marketplace_order_id' => $order->id,
                            'external_item_id'     => $itemExtId,
                            'external_model_id'    => $modelExtId,
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
                    
                    $existingItems->forget($itemKey);
                }

                // Track order-level ready / incomplete
                if ($orderHasIncomplete) {
                    $outerStats['incomplete']++;
                } elseif (in_array($orderStatus, ['READY_TO_SHIP', 'PROCESSED'])) {
                    $outerStats['ready']++;
                }
            }

            if (!$dryRun) {
                $store->update(['last_synced_at' => now()]);
            }
        });

        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=ON');
        }

        return $outerStats;
    }

    private function autoCreateFulfillments(Store $store): void
    {
        // Hanya buat fulfillment draft untuk order yang SEMUA item-nya data_status = valid
        $orders = MarketplaceOrder::where('store_id', $store->id)
            ->where('order_status', 'READY_TO_SHIP')
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
