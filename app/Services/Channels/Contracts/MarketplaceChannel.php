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

    public function refreshToken(Store $store): array;

    /**
     * Logistics / Fulfillment
     */
    public function getShippingParameter(Store $store, string $orderSn): array;

    public function shipOrder(Store $store, string $orderSn, array $params = []): array;

    public function createShippingDocument(Store $store, array $orderSnList): array;

    public function getShippingDocument(Store $store, array $orderSnList): array;
}
