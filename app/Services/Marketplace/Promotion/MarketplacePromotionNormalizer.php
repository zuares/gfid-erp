<?php

namespace App\Services\Marketplace\Promotion;

/**
 * Canonical promotion/fee shape shared by marketplace API and file imports.
 *
 * TikTok exports expose SKU and shipping discounts as separate columns, while
 * API payloads may expose vouchers and bundle deals under different names. The
 * normalizer keeps those concepts separate and uses zero when a source does
 * not provide a value.
 */
class MarketplacePromotionNormalizer
{
    /**
     * @param array<string, mixed> $values
     * @return array{line: array<string, float|string|null>, order: array<string, float>}
     */
    public function normalizeTiktokFileRow(array $values): array
    {
        $linePlatform = $this->number($values, [
            'sku_platform_discount',
            'platform_discount',
        ]);
        $lineSeller = $this->number($values, [
            'sku_seller_discount',
            'seller_discount',
        ]);
        $bundle = $this->number($values, [
            'bundle_deal_discount',
            'bundle_discount',
            'bundle_discount_amount',
        ]);

        $lineProduct = max($linePlatform + $lineSeller - $bundle, 0);

        return [
            'line' => [
                'platform_discount' => $linePlatform,
                'seller_discount' => $lineSeller,
                'product_discount' => $lineProduct,
                'bundle_deal_discount' => $bundle,
            ],
            'order' => [
                'product_discount' => $lineProduct,
                'platform_discount' => $linePlatform,
                'seller_discount' => $lineSeller,
                'voucher_store' => $this->number($values, [
                    'voucher_from_seller',
                    'seller_voucher',
                    'seller_voucher_rebate',
                    'seller_voucher_discount',
                ]),
                'voucher_platform' => $this->number($values, [
                    'voucher_from_tiktok',
                    'voucher_from_platform',
                    'platform_voucher',
                    'platform_voucher_discount',
                ]),
                'bundle_deal_discount' => $bundle,
                'shipping_discount_platform' => $this->number($values, [
                    'shipping_fee_platform_discount',
                    'shipping_discount_platform',
                ]),
                'shipping_discount_seller' => $this->number($values, [
                    'shipping_fee_seller_discount',
                    'shipping_discount_seller',
                ]),
                'payment_platform_discount' => $this->number($values, [
                    'payment_platform_discount',
                ]),
                'other_discount' => $this->number($values, [
                    'other_discount',
                ]),
                'shipping_fee_original' => $this->number($values, [
                    'original_shipping_fee',
                    'shipping_fee_original',
                ]),
                'shipping_fee_after_discount' => $this->number($values, [
                    'shipping_fee_after_discount',
                    'shipping_fee',
                ]),
                'refund_total' => $this->number($values, [
                    'order_refund_amount',
                    'refund_amount',
                ]),
                'buyer_service_fee' => $this->number($values, [
                    'buyer_service_fee',
                ]),
                'handling_fee' => $this->number($values, [
                    'handling_fee',
                ]),
                'shipping_insurance' => $this->number($values, [
                    'shipping_insurance',
                ]),
                'item_insurance' => $this->number($values, [
                    'item_insurance',
                ]),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $order
     * @param array<int, array<string, mixed>> $items
     * @return array<string, float>
     */
    public function normalizeTiktokApi(array $order, array $items): array
    {
        $linePlatform = 0.0;
        $lineSeller = 0.0;
        $bundle = 0.0;

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $itemPromotion = $this->normalizeTiktokApiItem($item);
            $linePlatform += $itemPromotion['platform_discount'];
            $lineSeller += $itemPromotion['seller_discount'];
            $bundle += $itemPromotion['bundle_deal_discount'];
        }

        $explicitProduct = $this->number($order, [
            'product_discount',
            'sku_discount',
            'item_discount',
        ]);
        $platform = $this->number($order, [
            'platform_discount',
            'platform_discount_amount',
        ]);
        $seller = $this->number($order, [
            'seller_discount',
            'seller_discount_amount',
        ]);

        $platform = $platform > 0 ? $platform : $linePlatform;
        $seller = $seller > 0 ? $seller : $lineSeller;
        $product = $explicitProduct > 0 ? $explicitProduct : $platform + $seller;

        return [
            'product_discount' => $product,
            'platform_discount' => $platform,
            'seller_discount' => $seller,
            'voucher_store' => $this->number($order, [
                'voucher_from_seller',
                'seller_voucher',
                'seller_voucher_rebate',
                'seller_voucher_discount',
            ]),
            'voucher_platform' => $this->number($order, [
                'voucher_from_tiktok',
                'voucher_from_platform',
                'platform_voucher',
                'platform_voucher_discount',
            ]),
            'bundle_deal_discount' => $bundle > 0
                ? $bundle
                : $this->number($order, [
                    'bundle_deal_discount',
                    'bundle_discount',
                    'bundle_discount_amount',
                ]),
            'shipping_discount_platform' => $this->number($order, [
                'shipping_fee_platform_discount',
                'shipping_discount_platform',
                'shipping_subsidy',
            ]),
            'shipping_discount_seller' => $this->number($order, [
                'shipping_fee_seller_discount',
                'shipping_discount_seller',
            ]),
            'payment_platform_discount' => $this->number($order, [
                'payment_platform_discount',
            ]),
            'other_discount' => $this->number($order, [
                'other_discount',
            ]),
            'shipping_fee_original' => $this->number($order, [
                'original_shipping_fee',
                'shipping_fee_original',
            ]),
            'shipping_fee_after_discount' => $this->number($order, [
                'shipping_fee_after_discount',
                'shipping_fee',
                'shipping_cost',
            ]),
            'refund_total' => $this->number($order, [
                'order_refund_amount',
                'refund_amount',
            ]),
            'buyer_service_fee' => $this->number($order, [
                'buyer_service_fee',
            ]),
            'handling_fee' => $this->number($order, [
                'handling_fee',
            ]),
            'shipping_insurance' => $this->number($order, [
                'shipping_insurance',
            ]),
            'item_insurance' => $this->number($order, [
                'item_insurance',
            ]),
        ];
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, float>
     */
    public function normalizeTiktokApiItem(array $item): array
    {
        $platform = $this->number($item, [
            'sku_platform_discount',
            'platform_discount',
            'platform_discount_amount',
        ]);
        $seller = $this->number($item, [
            'sku_seller_discount',
            'seller_discount',
            'seller_discount_amount',
        ]);
        $bundle = $this->number($item, [
            'bundle_deal_discount',
            'bundle_discount',
            'bundle_discount_amount',
        ]);

        return [
            'platform_discount' => $platform,
            'seller_discount' => $seller,
            'product_discount' => max($platform + $seller - $bundle, 0),
            'bundle_deal_discount' => $bundle,
        ];
    }

    /**
     * @param array<string, mixed> $source
     * @param array<int, string> $keys
     */
    private function number(array $source, array $keys): float
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $source) && is_numeric($source[$key])) {
                return max((float) $source[$key], 0);
            }
        }

        return 0.0;
    }
}
