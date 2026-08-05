<?php

namespace App\Services\Channels\Contracts;

use App\Models\Store;

interface MarketplaceChannel
{
    public function getShopInfo(Store $store): array;

    /**
     * @param string $timeRangeField 'update_time' (default, untuk sync rutin agar
     *                               perubahan status ikut tertangkap) atau
     *                               'create_time' (untuk backfill/histori agar
     *                               setiap periode deterministik per tanggal order dibuat).
     */
    public function getOrders(Store $store, int $timeFrom, int $timeTo, int $pageSize = 20, string $cursor = '', string $orderStatus = '', string $timeRangeField = 'update_time'): array;

    public function getOrderDetail(Store $store, array $orderSnList): array;

    public function getEscrowDetail(Store $store, string $orderSn): array;

    /**
     * Ambil order yang sudah menerima pencairan escrow beserta waktunya.
     * Shopee-only; channel lain boleh mengembalikan not_supported.
     */
    public function getEscrowReleasedOrders(
        Store $store,
        int $releaseTimeFrom,
        int $releaseTimeTo,
        int $paginationOffset = 0,
        int $paginationEntriesPerPage = 100
    ): array;

    /**
     * Cek apakah iklan aktif di toko ini.
     * Endpoint: GET /api/v2/ads/get_shop_toggle_info
     */
    public function getShopToggleInfo(Store $store): array;

    /**
     * Ambil daftar ID campaign (dengan nama & status).
     * Endpoint: GET /api/v2/ads/get_product_level_campaign_id_list
     *
     * @param  int  $pageNo    Mulai dari 1
     * @param  int  $pageSize  Max 100
     */
    public function getCampaignIdList(Store $store, int $pageNo = 1, int $pageSize = 100): array;

    /**
     * Ambil info pengaturan campaign (nama, tipe, status, budget).
     * Endpoint: GET /api/v2/ads/get_product_level_campaign_setting_info
     *
     * @param  array  $campaignIds  Array of campaign_id (int)
     */
    public function getCampaignSettingInfo(Store $store, array $campaignIds): array;

    /**
     * Ambil performa harian per campaign untuk rentang tanggal.
     * Endpoint: GET /api/v2/ads/get_product_campaign_daily_performance
     *
     * @param  array   $campaignIds  Array of campaign_id (int)
     * @param  string  $startDate    Format DD-MM-YYYY
     * @param  string  $endDate      Format DD-MM-YYYY
     */
    public function getCampaignDailyPerformance(
        Store  $store,
        array  $campaignIds,
        string $startDate,
        string $endDate
    ): array;

    // ───────────────────────────────────────────────────────────────────────
    // Discount APIs
    // ───────────────────────────────────────────────────────────────────────

    public function getDiscountList(
        Store $store,
        string $status = 'ongoing',
        int $pageNo = 1,
        int $pageSize = 100,
        ?int $updateTimeFrom = null,
        ?int $updateTimeTo = null
    ): array;

    public function addDiscount(Store $store, string $discountName, int $startTime, int $endTime): array;

    public function addDiscountItem(Store $store, int $discountId, array $itemList): array;

    public function updateDiscountItem(Store $store, int $discountId, array $itemList): array;

    public function endDiscount(Store $store, int $discountId): array;

    public function getDiscount(Store $store, int $discountId, int $pageNo = 1, int $pageSize = 50): array;

    public function updateDiscount(
        Store $store,
        int $discountId,
        ?string $discountName = null,
        ?int $startTime = null,
        ?int $endTime = null
    ): array;

    public function deleteDiscount(Store $store, int $discountId): array;

    public function deleteDiscountItem(Store $store, int $discountId, int $itemId, int $modelId = 0): array;

    public function getSipDiscounts(Store $store, ?string $region = null): array;

    public function setSipDiscount(Store $store, string $region, int $sipDiscountRate): array;

    public function deleteSipDiscount(Store $store, string $region): array;

    public function refreshToken(Store $store, bool $force = false): array;

    /**
     * Logistics / Fulfillment
     */
    public function getShippingParameter(Store $store, string $orderSn): array;

    public function shipOrder(Store $store, string $orderSn, array $params = []): array;

    public function createShippingDocument(Store $store, array $orderSnList): array;

    public function getShippingDocument(Store $store, array $orderSnList): array;
}
