<?php

namespace App\Services\Marketplace;

use App\Models\Store;

class AmsPerformanceReportService
{
    public function __construct(
        protected MarketplaceApiGateway $gateway
    ) {}

    public function marker(Store $store): array
    {
        return $this->gateway->getAmsPerformanceDataUpdateTime($store, 'AmsMarker');
    }

    /**
     * Fetch one of the read-only AMS Performance & Report endpoints.
     */
    public function fetch(Store $store, string $report, array $params = []): array
    {
        return match ($report) {
            'shop' => $this->gateway->getAmsShopPerformance($store, $params),
            'product' => $this->gateway->getAmsProductPerformance($store, $params),
            'affiliate' => $this->gateway->getAmsAffiliatePerformance($store, $params),
            'content' => $this->gateway->getAmsContentPerformance($store, $params),
            'campaign_metrics' => $this->gateway->getAmsCampaignKeyMetricsPerformance($store, $params),
            'open_campaign' => $this->gateway->getAmsOpenCampaignPerformance($store, $params),
            'targeted_campaign' => $this->gateway->getAmsTargetedCampaignPerformance($store, $params),
            'conversion' => $this->gateway->getAmsConversionReport($store, $params),
            'validation_list' => $this->gateway->getAmsValidationList($store),
            'validation_report' => $this->gateway->getAmsValidationReport($store, $params),
            default => throw new \InvalidArgumentException("AMS report [{$report}] tidak didukung."),
        };
    }

    public function overview(Store $store, array $params): array
    {
        return [
            'marker' => $this->marker($store),
            'shop' => $this->fetch($store, 'shop', $params),
            'campaign_metrics' => $this->fetch($store, 'campaign_metrics', $params),
        ];
    }
}
