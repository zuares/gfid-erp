<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceShopPerformanceSnapshot;
use App\Models\Store;
use App\Services\Channels\Shopee\ShopeeChannel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ShopeeShopPerformanceSyncService
{
    private const GRANULARITIES = ['customize', 'day', 'week', 'month', 'quarter', 'year'];

    public function __construct(private readonly ShopeeChannel $shopee)
    {
    }

    /**
     * Fetch and persist one principal performance period.
     *
     * The API's detail rows are shop-level aggregates. We retain the exact
     * requested period so the application never confuses these metrics with
     * order-based net sales or settlement data.
     */
    public function syncPeriod(
        Store $store,
        Carbon $from,
        Carbon $to,
        string $granularity = 'customize',
        string $timezone = 'GMT+7',
        string $currency = 'LOCAL'
    ): array {
        $this->assertGranularity($granularity);

        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();
        if ($from->greaterThan($to)) {
            throw new RuntimeException('Tanggal mulai tidak boleh lebih besar dari tanggal akhir.');
        }

        $this->assertTimezone($timezone);
        $this->assertCurrency($currency);

        $shopId = (int) $store->credential('shop_id', $store->external_shop_id);
        if ($shopId <= 0) {
            throw new RuntimeException("{$store->name}: shop_id belum dikonfigurasi.");
        }

        $payload = [
            'start_date' => $from->toDateString(),
            'end_date' => $to->toDateString(),
            'timezone' => $timezone,
            'granularity' => $granularity,
            'shop_list' => [[
                'shop_id' => $shopId,
                'currency' => $currency,
            ]],
        ];

        $response = $this->shopee->getShopSalesPerformanceDetail($store, $payload);
        $error = trim((string) ($response['error'] ?? ''));
        if ($error !== '') {
            $message = trim((string) ($response['message'] ?? $error));
            throw new RuntimeException("{$store->name}: Shopee {$error}: {$message}");
        }

        $details = data_get($response, 'response.details', []);
        if (! is_array($details) || $details === []) {
            throw new RuntimeException("{$store->name}: response Shopee tidak memiliki detail toko.");
        }

        $principalId = (int) $store->credential(
            'principal_id',
            data_get($store->meta ?? [], 'principal_id', config('shopee.principal_id'))
        );
        $now = now();
        $saved = 0;

        DB::transaction(function () use (
            $details,
            $store,
            $principalId,
            $granularity,
            $from,
            $to,
            $timezone,
            $currency,
            $response,
            $now,
            &$saved
        ): void {
            foreach ($details as $detail) {
                if (! is_array($detail)) {
                    continue;
                }

                $detailShopId = (int) ($detail['shop_id'] ?? $store->external_shop_id);
                if ($detailShopId <= 0) {
                    continue;
                }

                $detailCurrency = strtoupper((string) ($detail['currency'] ?? $currency));
                $key = [
                    'store_id' => $store->id,
                    'shop_id' => $detailShopId,
                    'granularity' => $granularity,
                    'period_start' => $from->toDateString(),
                    'period_end' => $to->toDateString(),
                    'currency' => $detailCurrency,
                ];

                MarketplaceShopPerformanceSnapshot::query()->updateOrCreate(
                    $key,
                    array_merge($this->mapMetrics($detail), [
                        'principal_id' => $principalId > 0 ? $principalId : null,
                        'timezone' => $timezone,
                        'request_id' => data_get($response, 'request_id'),
                        'metrics_json' => $detail,
                        'raw_response' => $response,
                        'fetched_at' => $now,
                    ])
                );
                $saved++;
            }
        });

        return [
            'request_id' => data_get($response, 'request_id'),
            'saved' => $saved,
            'period_start' => $from->toDateString(),
            'period_end' => $to->toDateString(),
            'granularity' => $granularity,
        ];
    }

    /**
     * Day granularity is intentionally requested one day at a time because
     * Shopee requires start_date === end_date for that granularity.
     */
    public function syncDailyRange(
        Store $store,
        Carbon $from,
        Carbon $to,
        string $timezone = 'GMT+7',
        string $currency = 'LOCAL'
    ): array {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();
        $results = [];

        for ($date = $from->copy(); $date->lessThanOrEqualTo($to); $date->addDay()) {
            $results[] = $this->syncPeriod($store, $date, $date, 'day', $timezone, $currency);
        }

        // Keep a period-level row as well. This is important for metrics such
        // as unique_visitors, which cannot be safely summed across days.
        $aggregate = $this->syncPeriod($store, $from, $to, 'customize', $timezone, $currency);

        return [
            'periods' => count($results) + 1,
            'saved' => array_sum(array_column($results, 'saved')) + ($aggregate['saved'] ?? 0),
            'aggregate' => $aggregate,
            'results' => $results,
        ];
    }

    private function mapMetrics(array $detail): array
    {
        return [
            'sales' => $this->number($detail['sales'] ?? 0),
            'orders' => $this->integer($detail['orders'] ?? 0),
            'units_sold' => $this->integer($detail['units_sold'] ?? 0),
            'average_basket_size' => $this->number($detail['average_basket_size'] ?? 0),
            'items_per_order' => $this->number($detail['items_per_order'] ?? 0),
            'average_selling_price' => $this->number($detail['average_selling_price'] ?? 0),
            'product_clicks' => $this->integer($detail['product_clicks'] ?? 0),
            'product_views' => $this->integer($detail['product_views'] ?? 0),
            'unique_visitors' => $this->integer($detail['unique_visitors'] ?? 0),
            'item_conversion_rate' => $this->number($detail['item_conversion_rate'] ?? 0),
            'order_conversion_rate' => $this->number($detail['order_conversion_rate'] ?? 0),
            'atp_top_skus_l1d' => $this->nullableNumber($detail['atp_top_skus_l1d'] ?? null),
            'atp_top_skus_l30d' => $this->nullableNumber($detail['atp_top_skus_l30d'] ?? null),
            'atp_live_skus_l1d' => $this->nullableNumber($detail['atp_live_skus_l1d'] ?? null),
            'atp_live_skus_l30d' => $this->nullableNumber($detail['atp_live_skus_l30d'] ?? null),
            'flash_sale_sales' => $this->number($detail['flash_sale_sales'] ?? 0),
            'flash_sale_orders' => $this->integer($detail['flash_sale_orders'] ?? 0),
            'flash_sale_units_sold' => $this->integer($detail['flash_sale_units_sold'] ?? 0),
            'voucher_sales' => $this->number($detail['voucher_sales'] ?? 0),
            'voucher_buyers' => $this->integer($detail['voucher_buyers'] ?? 0),
            'voucher_usage_rate' => $this->number($detail['voucher_usage_rate'] ?? 0),
            'voucher_cir' => $this->number($detail['voucher_cir'] ?? 0),
            'voucher_cost' => $this->number($detail['voucher_cost'] ?? 0),
        ];
    }

    private function assertGranularity(string $granularity): void
    {
        if (! in_array($granularity, self::GRANULARITIES, true)) {
            throw new RuntimeException('Granularity harus customize, day, week, month, quarter, atau year.');
        }
    }

    private function assertTimezone(string $timezone): void
    {
        if (! in_array($timezone, ['GMT+7', 'GMT+8', 'GMT-3'], true)) {
            throw new RuntimeException('Timezone Shopee tidak didukung.');
        }
    }

    private function assertCurrency(string $currency): void
    {
        if (! in_array(strtoupper($currency), ['LOCAL', 'USD'], true)) {
            throw new RuntimeException('Currency Shopee hanya boleh LOCAL atau USD.');
        }
    }

    private function number(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function nullableNumber(mixed $value): ?float
    {
        return $value === null || $value === '' ? null : $this->number($value);
    }

    private function integer(mixed $value): int
    {
        return is_numeric($value) ? max(0, (int) $value) : 0;
    }
}
