<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceOrderSettlement extends Model
{
    protected $fillable = [
        'store_id',
        'order_id',
        'channel_order_id',

        // Pembayaran customer
        'buyer_payment_amount',

        // Fee marketplace
        'commission_fee',
        'service_fee',
        'transaction_fee',
        'affiliate_fee',

        // Voucher & diskon seller
        'seller_voucher',
        'seller_coin_cash_back',

        // Ongkir
        'actual_shipping_fee',
        'shipping_cost_amount',
        'shipping_fee_subsidy',
        'reverse_shipping_fee',
        'shipping_insurance_fee',

        // Campaign & lainnya
        'activity_fee',
        'drc_adjustable_refund',
        'escrow_tax',

        // Biaya iklan (manual / import)
        'ad_cost',

        // Dana cair
        'final_income',
        'settlement_time',

        // Meta
        'synced_at',
        'raw_json',
        'data_status',
        'data_quality_flags',
        'data_checked_at',
    ];

    protected $casts = [
        'buyer_payment_amount'   => 'decimal:2',
        'commission_fee'         => 'decimal:2',
        'service_fee'            => 'decimal:2',
        'transaction_fee'        => 'decimal:2',
        'affiliate_fee'          => 'decimal:2',
        'seller_voucher'         => 'decimal:2',
        'seller_coin_cash_back'  => 'decimal:2',
        'actual_shipping_fee'    => 'decimal:2',
        'shipping_cost_amount'   => 'decimal:2',
        'shipping_fee_subsidy'   => 'decimal:2',
        'reverse_shipping_fee'   => 'decimal:2',
        'shipping_insurance_fee' => 'decimal:2',
        'activity_fee'           => 'decimal:2',
        'drc_adjustable_refund'  => 'decimal:2',
        'escrow_tax'             => 'decimal:2',
        'ad_cost'                => 'decimal:2',
        'final_income'           => 'decimal:2',
        'settlement_time'        => 'datetime',
        'synced_at'              => 'datetime',
        'raw_json'               => 'array',
        'data_quality_flags'     => 'array',
        'data_checked_at'        => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrder::class, 'order_id');
    }

    /** Total semua potongan. */
    public function totalDeductions(): float
    {
        $totals = $this->marketplaceFeeCategoryTotals();

        return (float) ($totals['seller'] ?? 0.0);
    }

    /**
     * Read the item-level TikTok Finance refund amount from a normalized
     * statement payload. TikTok exposes the refund as a negative
     * `refund_subtotal_before_discount_amount` inside each revenue breakdown;
     * analytics stores deductions as positive amounts, so the sign is
     * normalized here. Seller discount refunds are intentionally excluded:
     * the gross-sales calculation uses the pre-discount subtotal as the
     * reversed sales value and must use the same basis for the refund.
     */
    public static function tiktokRefundAmountFromPayload(array $raw): float
    {
        $transactions = $raw['sku_transactions'] ?? [];
        $sources = is_array($transactions) && $transactions !== []
            ? array_values(array_filter($transactions, 'is_array'))
            : [$raw];
        $total = 0.0;

        foreach ($sources as $source) {
            $breakdown = is_array($source['revenue_breakdown'] ?? null)
                ? $source['revenue_breakdown']
                : [];
            $value = $breakdown['refund_subtotal_before_discount_amount']
                ?? $source['refund_subtotal_before_discount_amount']
                ?? $source['refund_total']
                ?? $source['refund_amount']
                ?? null;

            if ($value !== null && $value !== '' && is_numeric($value)) {
                $total += abs((float) $value);
            }
        }

        if ($total <= 0) {
            foreach (['seller_return_refund_amount', 'seller_return_refund', 'drc_adjustable_refund'] as $key) {
                $value = $raw[$key] ?? null;
                if ($value !== null && $value !== '' && is_numeric($value)) {
                    $total = abs((float) $value);
                    break;
                }
            }
        }

        return round($total, 2);
    }

    public function tiktokRefundAmount(): float
    {
        $raw = $this->raw_json;

        return is_array($raw) ? self::tiktokRefundAmountFromPayload($raw) : 0.0;
    }

    /**
     * Read TikTok affiliate commission once from a normalized finance payload.
     * TikTok may expose the same value through the before-PIT display field;
     * when both values match, the display field is intentionally ignored.
     */
    public static function tiktokAffiliateFeeFromPayload(array $raw): float
    {
        $fees = data_get($raw, 'tiktok_fee_breakdown.fees');
        if (! is_array($fees)) {
            $fees = [];
            foreach ((array) ($raw['sku_transactions'] ?? []) as $transaction) {
                $transactionFees = data_get($transaction, 'fee_tax_breakdown.fee', []);
                if (! is_array($transactionFees)) {
                    continue;
                }

                foreach ($transactionFees as $key => $value) {
                    if (is_numeric($value)) {
                        $fees[$key] = (float) ($fees[$key] ?? 0) + (float) $value;
                    }
                }
            }
        }

        $affiliate = $fees['affiliate_commission_amount'] ?? null;
        $beforePit = $fees['affiliate_commission_amount_before_pit'] ?? null;
        $skipBeforePit = is_numeric($affiliate) && is_numeric($beforePit)
            && abs((float) $affiliate - (float) $beforePit) < 0.01;
        $total = 0.0;

        foreach ($fees as $key => $value) {
            $key = strtolower((string) $key);
            if (! str_contains($key, 'affiliate') || ($skipBeforePit && $key === 'affiliate_commission_amount_before_pit')) {
                continue;
            }
            if (is_numeric($value)) {
                $total += abs((float) $value);
            }
        }

        return round($total, 2);
    }

    /**
     * Build a categorized breakdown for marketplace settlement fees.
     *
     * @return array<int, array{label: string, amount: float, category: string, source?: string}>
     */
    public function marketplaceFeeBreakdown(): array
    {
        $rawValue = $this->getRawOriginal('raw_json');
        $raw = is_array($rawValue) ? $rawValue : (is_array($this->raw_json) ? $this->raw_json : []);
        $rows = [];
        $seen = [];
        $usedKeys = [];

        // TikTok v202501 already maps fee and tax into the canonical columns
        // below. Do not count the aggregate API fields a second time in the
        // generic fee-field fallback. The source/sku checks also cover rows
        // synced before tiktok_fee_breakdown was normalized.
        $isTikTokFinancePayload = is_array($raw['tiktok_fee_breakdown'] ?? null)
            || is_array($raw['sku_transactions'] ?? null)
            || str_contains(strtolower((string) ($raw['source'] ?? '')), 'tiktok');
        if ($isTikTokFinancePayload) {
            $usedKeys['fee_and_tax_amount'] = true;
            $usedKeys['fee_tax_amount'] = true;
            $usedKeys['tiktok_fee_breakdown'] = true;
        }

        $amountOf = function (mixed $value): float {
            if ($value === null || $value === '') {
                return 0.0;
            }

            if (is_bool($value)) {
                return $value ? 1.0 : 0.0;
            }

            if (is_numeric($value)) {
                return abs((float) $value);
            }

            $normalized = str_replace(['Rp', 'rp', ' '], '', (string) $value);
            $normalized = preg_replace('/[^0-9,\.\-]/', '', $normalized) ?? '';
            if ($normalized === '' || $normalized === '-') {
                return 0.0;
            }

            $normalized = str_replace(',', '', $normalized);

            return abs((float) $normalized);
        };

        $push = function (string $label, mixed $value, string $category = 'seller', ?string $sourceKey = null) use (&$rows, &$seen, &$usedKeys, $amountOf) {
            $amount = $amountOf($value);
            if ($amount <= 0) {
                return;
            }

            $signature = $category . '|' . $label . '|' . number_format($amount, 2, '.', '');
            if (isset($seen[$signature])) {
                return;
            }

            $seen[$signature] = true;
            $row = [
                'label' => $label,
                'amount' => $amount,
                'category' => $category,
            ];

            if ($sourceKey) {
                $row['source'] = $sourceKey;
                $usedKeys[$sourceKey] = true;
            }

            $rows[] = $row;
        };

        $consumeFirst = function (array $keys, mixed $fallback = 0) use ($raw, &$usedKeys): array {
            foreach ($keys as $key) {
                $value = data_get($raw, $key);
                if ($value !== null && $value !== '') {
                    foreach ($keys as $alias) {
                        $usedKeys[$alias] = true;
                    }

                    return [$value, $key];
                }
            }

            return [$fallback, null];
        };

        // Seller-side fees.
        [$commission, $commissionKey] = $consumeFirst(['commission_fee'], $this->commission_fee);
        $push('Biaya Administrasi', $commission, 'seller', $commissionKey);

        [$service, $serviceKey] = $consumeFirst(['service_fee'], $this->service_fee);
        $push('Biaya Layanan', $service, 'seller', $serviceKey);

        $sellerTransaction = data_get($raw, 'seller_transaction_fee');
        $processOrder = data_get($raw, 'seller_order_processing_fee');
        if ($sellerTransaction !== null && $sellerTransaction !== '') {
            $push('Biaya Transaksi', $sellerTransaction, 'seller', 'seller_transaction_fee');
            $usedKeys['transaction_fee'] = true;
            $usedKeys['seller_transaction_fee'] = true;
        } elseif ($this->transaction_fee || data_get($raw, 'transaction_fee') !== null) {
            [$transaction, $transactionKey] = $consumeFirst(['transaction_fee'], $this->transaction_fee);
            $push('Biaya Transaksi', $transaction, 'seller', $transactionKey);
            $usedKeys['seller_transaction_fee'] = true;
        }
        if ($processOrder !== null && $processOrder !== '') {
            $push('Biaya Proses Pesanan', $processOrder, 'seller', 'seller_order_processing_fee');
            $usedKeys['transaction_fee'] = true;
            $usedKeys['seller_order_processing_fee'] = true;
        }

        $ams = data_get($raw, 'order_ams_commission_fee');
        if ($ams === null || $ams === '') {
            $ams = data_get($raw, 'ams_commission_fee');
        }
        if ($ams === null || $ams === '') {
            $ams = $this->activity_fee;
        }
        $push('Biaya Komisi AMS', $ams, 'seller', data_get($raw, 'order_ams_commission_fee') !== null ? 'order_ams_commission_fee' : (data_get($raw, 'ams_commission_fee') !== null ? 'ams_commission_fee' : 'activity_fee'));
        $usedKeys['activity_fee'] = true;
        $usedKeys['order_ams_commission_fee'] = true;
        $usedKeys['ams_commission_fee'] = true;

        [$affiliateFee, $affiliateFeeKey] = $consumeFirst(['affiliate_fee', 'affiliate_commission_fee', 'seller_affiliate_fee'], $this->affiliate_fee);
        $push('Biaya Affiliate', $affiliateFee, 'seller', $affiliateFeeKey);
        $usedKeys['affiliate_fee'] = true;
        $usedKeys['affiliate_commission_fee'] = true;
        $usedKeys['seller_affiliate_fee'] = true;

        $affiliate = data_get($raw, 'affiliate_commission');
        if ($affiliate === null || $affiliate === '') {
            $affiliate = data_get($raw, 'affiliate_commission_amount');
        }
        if ($affiliate !== null && $affiliate !== '') {
            $push('Affiliate', $affiliate, 'seller', data_get($raw, 'affiliate_commission') !== null ? 'affiliate_commission' : 'affiliate_commission_amount');
            $usedKeys['affiliate_commission'] = true;
            $usedKeys['affiliate_commission_amount'] = true;
        }

        [$shippingInsurance, $shippingInsuranceKey] = $consumeFirst(['shipping_insurance_fee', 'shipping_insurance', 'insurance_fee'], $this->shipping_insurance_fee);
        $push('Biaya Asuransi Pengiriman', $shippingInsurance, 'seller', $shippingInsuranceKey);
        $usedKeys['shipping_insurance_fee'] = true;
        $usedKeys['shipping_insurance'] = true;
        $usedKeys['insurance_fee'] = true;

        [$escrowTax, $escrowTaxKey] = $consumeFirst(['escrow_tax'], $this->escrow_tax);
        $push('Pajak (Escrow)', $escrowTax, 'seller', $escrowTaxKey);
        $usedKeys['escrow_tax'] = true;

        // Before the normalized TikTok breakdown was introduced, the service
        // stored the tax aggregate in raw_json.fee_tax_amount while leaving
        // escrow_tax empty. Preserve that tax once without re-adding the full
        // fee_and_tax_amount aggregate.
        if ($isTikTokFinancePayload && $amountOf($escrowTax) <= 0) {
            $legacyTikTokTax = data_get($raw, 'fee_tax_amount');
            if ($legacyTikTokTax !== null && $legacyTikTokTax !== '') {
                $push('Pajak TikTok', $legacyTikTokTax, 'seller', 'fee_tax_amount');
            }
        }

        [$reverseShipping, $reverseShippingKey] = $consumeFirst(['reverse_shipping_fee'], $this->reverse_shipping_fee);
        $push('Ongkir balik', $reverseShipping, 'adjustment', $reverseShippingKey);
        $usedKeys['reverse_shipping_fee'] = true;

        [$adCost, $adCostKey] = $consumeFirst(['ad_cost'], $this->ad_cost);
        $push('Biaya Iklan', $adCost, 'seller', $adCostKey);
        $usedKeys['ad_cost'] = true;

        [$refundAdjust, $refundAdjustKey] = $consumeFirst(['drc_adjustable_refund', 'seller_return_refund_amount', 'seller_return_refund'], $this->drc_adjustable_refund);
        $push('Refund / Adjustment', $refundAdjust, 'adjustment', $refundAdjustKey);
        $usedKeys['drc_adjustable_refund'] = true;
        $usedKeys['seller_return_refund_amount'] = true;
        $usedKeys['seller_return_refund'] = true;

        $premi = data_get($raw, 'premi');
        if ($premi !== null && $premi !== '') {
            $push('Premi', $premi, 'seller', 'premi');
            $usedKeys['premi'] = true;
        }

        // Buyer-side charges.
        [$buyerShipping, $buyerShippingKey] = $consumeFirst(['buyer_paid_shipping_fee', 'customer_paid_shipping_fee_amount'], null);
        if ($buyerShipping !== null && $buyerShipping !== '') {
            $push('Ongkos Kirim Dibayar Pembeli', $buyerShipping, 'buyer', $buyerShippingKey);
        }
        // These are logistics display fields, not seller/buyer fee rows. Keep
        // them out of the generic fee scan so actual and estimated shipping
        // are never counted as buyer burden when buyer-paid shipping is absent.
        foreach ([
            'actual_shipping_fee',
            'estimated_shipping_fee',
            'buyer_paid_shipping_fee',
            'customer_paid_shipping_fee_amount',
        ] as $shippingKey) {
            $usedKeys[$shippingKey] = true;
        }

        [$buyerPaymentFee, $buyerPaymentFeeKey] = $consumeFirst(['credit_card_transaction_fee', 'buyer_transaction_fee'], null);
        if ($buyerPaymentFee !== null && $buyerPaymentFee !== '') {
            $push('Biaya Pembayaran Pembeli', $buyerPaymentFee, 'buyer', $buyerPaymentFeeKey);
        }

        // Platform-side support.
        [$platformSubsidy, $platformSubsidyKey] = $consumeFirst(['shipping_fee_subsidy', 'shopee_shipping_rebate'], $this->shipping_fee_subsidy);
        if ($platformSubsidy !== null && $platformSubsidy !== '') {
            $push('Subsidi Ongkir Platform', $platformSubsidy, 'platform', $platformSubsidyKey);
        }

        [$voucherPlatform, $voucherPlatformKey] = $consumeFirst(['voucher_from_shopee', 'platform_voucher', 'voucher_from_platform'], null);
        if ($voucherPlatform !== null && $voucherPlatform !== '') {
            $push('Voucher Platform', $voucherPlatform, 'voucher', $voucherPlatformKey);
            $usedKeys['voucher_from_shopee'] = true;
            $usedKeys['platform_voucher'] = true;
            $usedKeys['voucher_from_platform'] = true;
        }

        [$voucherToko, $voucherTokoKey] = $consumeFirst(['seller_voucher', 'voucher_from_seller', 'seller_voucher_rebate'], $this->seller_voucher);
        if ($voucherToko !== null && $voucherToko !== '') {
            $push('Voucher Toko', $voucherToko, 'voucher', $voucherTokoKey);
        }

        // Any future fee-like field from platform should still show up.
        foreach ($raw as $key => $value) {
            if (!is_string($key) || isset($usedKeys[$key])) {
                continue;
            }

            if (is_array($value) || is_object($value)) {
                continue;
            }

            if (!$this->looksLikeFeeField($key)) {
                continue;
            }

            $amount = $amountOf($value);
            if ($amount <= 0) {
                continue;
            }

            $category = $this->feeBreakdownCategoryForKey($key);
            $label = 'Biaya Tambahan (' . $key . ')';
            $push($label, $amount, $category, $key);
        }

        return $rows;
    }

    /**
     * Canonical fee mapping used by Marketplace Analytics.
     *
     * The legacy columns intentionally remain untouched because Shopee and
     * TikTok do not expose the same fee contract. This view-level mapping
     * reads the raw settlement snapshot and presents comparable categories
     * without counting aggregate TikTok fields together with their children.
     *
     * @return array<int, array{code:string, label:string, amount:float, category:string, burden:string, source?:string}>
     */
    public function analyticsFeeBreakdown(): array
    {
        $rawValue = $this->getRawOriginal('raw_json');
        $raw = is_array($rawValue) ? $rawValue : (is_array($this->raw_json) ? $this->raw_json : []);
        $rows = [];
        $seen = [];
        $amountOf = static function (mixed $value): float {
            if ($value === null || $value === '' || ! is_numeric($value)) {
                return 0.0;
            }

            return abs((float) $value);
        };
        $push = static function (array &$rows, array &$seen, string $code, string $label, mixed $value, string $category, ?string $source = null) use ($amountOf): void {
            $amount = $amountOf($value);
            if ($amount <= 0) {
                return;
            }

            $signature = $code.'|'.($source ?? '').'|'.number_format($amount, 2, '.', '');
            if (isset($seen[$signature])) {
                return;
            }

            $seen[$signature] = true;
            $row = [
                'code' => $code,
                'label' => $label,
                'amount' => round($amount, 2),
                'category' => $category,
                'burden' => $code === 'shipping_subsidy' || $category === 'platform' ? 'platform' : 'seller',
            ];
            if ($source) {
                $row['source'] = $source;
            }
            $rows[] = $row;
        };

        $isTikTok = is_array($raw['tiktok_fee_breakdown'] ?? null)
            || is_array($raw['sku_transactions'] ?? null)
            || str_contains(strtolower((string) ($raw['source'] ?? '')), 'tiktok');

        if ($isTikTok) {
            $fees = data_get($raw, 'tiktok_fee_breakdown.fees', []);
            $feeRows = is_array($fees) ? $fees : [];

            foreach ($feeRows as $key => $value) {
                $key = strtolower((string) $key);
                if ($key === 'affiliate_commission_amount_before_pit') {
                    continue;
                }

                if (str_contains($key, 'gmv_max') || str_contains($key, 'ad_fee') || str_contains($key, 'coupon_fee')) {
                    $push($rows, $seen, 'ad_fee', 'Biaya Ads / GMV Max', $value, 'fee', 'tiktok_fee_breakdown.fees.'.$key);
                    continue;
                }
                if (str_contains($key, 'affiliate')) {
                    $push($rows, $seen, 'affiliate_fee', 'Komisi Afiliasi', $value, 'affiliate', 'tiktok_fee_breakdown.fees.'.$key);
                    continue;
                }
                if (str_contains($key, 'dynamic')) {
                    $push($rows, $seen, 'dynamic_commission', 'Komisi Dinamis', $value, 'fee', 'tiktok_fee_breakdown.fees.'.$key);
                    continue;
                }
                if ($key === 'vn_fix_infrastructure_fee' || str_contains($key, 'processing') || str_contains($key, 'transaction')) {
                    $push($rows, $seen, 'order_processing_fee', 'Biaya Pemrosesan Pesanan', $value, 'fee', 'tiktok_fee_breakdown.fees.'.$key);
                    continue;
                }
                if (str_contains($key, 'service') || str_contains($key, 'growth') || str_contains($key, 'program')) {
                    $label = str_contains($key, 'voucher_xtra')
                        ? 'Program Voucher XTRA'
                        : (str_contains($key, 'growth') ? 'Program Growth Xtra' : 'Biaya Program / Layanan');
                    $push($rows, $seen, 'program_service_fee', $label, $value, 'fee', 'tiktok_fee_breakdown.fees.'.$key);
                    continue;
                }
                if (str_contains($key, 'platform') || str_contains($key, 'commission')) {
                    $push($rows, $seen, 'platform_commission', 'Komisi Platform', $value, 'fee', 'tiktok_fee_breakdown.fees.'.$key);
                    continue;
                }

                $push($rows, $seen, 'other_fee', 'Biaya Marketplace Lainnya', $value, 'fee', 'tiktok_fee_breakdown.fees.'.$key);
            }

            foreach ((array) data_get($raw, 'tiktok_fee_breakdown.taxes', []) as $key => $value) {
                $push($rows, $seen, 'tax', 'Pajak', $value, 'tax', 'tiktok_fee_breakdown.taxes.'.(string) $key);
            }

            $push($rows, $seen, 'shipping_cost', 'Biaya Layanan Logistik', $raw['shipping_cost_amount'] ?? null, 'shipping', 'shipping_cost_amount');
            $push($rows, $seen, 'shipping_subsidy', 'Subsidi / Potongan Ongkir', data_get($raw, 'tiktok_shipping_breakdown.shipping_fee_discount_amount'), 'shipping', 'tiktok_shipping_breakdown.shipping_fee_discount_amount');

            if ($rows === []) {
                $push($rows, $seen, 'platform_commission', 'Komisi Platform', $this->commission_fee, 'fee', 'commission_fee');
            }

            return $rows;
        }

        $push($rows, $seen, 'platform_commission', 'Biaya Administrasi / Komisi Platform', $this->commission_fee, 'fee', 'commission_fee');

        $processing = $raw['seller_order_processing_fee'] ?? null;
        $sellerTransaction = $raw['seller_transaction_fee'] ?? null;
        if ($processing !== null || $sellerTransaction !== null) {
            $push($rows, $seen, 'order_processing_fee', 'Biaya Proses Pesanan', $processing, 'fee', 'seller_order_processing_fee');
            $push($rows, $seen, 'transaction_fee', 'Biaya Transaksi', $sellerTransaction, 'fee', 'seller_transaction_fee');
        } else {
            $push($rows, $seen, 'order_processing_fee', 'Biaya Proses Pesanan', $this->transaction_fee, 'fee', 'transaction_fee');
        }

        $serviceDetails = [];
        $collectService = static function (mixed $value, string $source) use (&$collectService, &$serviceDetails): void {
            if (! is_array($value)) {
                return;
            }

            $amount = $value['fee_amount'] ?? $value['service_fee_amount'] ?? $value['amount'] ?? $value['value'] ?? null;
            if ($amount !== null && $amount !== '' && is_numeric($amount)) {
                $label = (string) ($value['rule_display_name'] ?? $value['fee_name'] ?? $value['program_name'] ?? $value['name'] ?? 'Biaya Layanan');
                $serviceDetails[] = [$label, $amount, $source];
                return;
            }

            foreach ($value as $nested) {
                $collectService($nested, $source);
            }
        };
        foreach (['net_service_fee_info_list', 'final_seller_service_fee_list', 'seller_service_fee_list', 'service_fee_info_list', 'service_fee_breakdown'] as $key) {
            $collectService($raw[$key] ?? null, $key);
        }
        if ($serviceDetails !== []) {
            foreach ($serviceDetails as [$label, $amount, $source]) {
                $push($rows, $seen, 'program_service_fee', $label, $amount, 'fee', $source);
            }
        } else {
            $push($rows, $seen, 'program_service_fee', 'Biaya Layanan / Program', $this->service_fee, 'fee', 'service_fee');
        }

        $push($rows, $seen, 'affiliate_fee', 'Komisi Afiliasi / AMS', $this->affiliate_fee, 'affiliate', 'affiliate_fee');
        $push($rows, $seen, 'affiliate_fee', 'Komisi Afiliasi / AMS', $this->activity_fee, 'affiliate', 'activity_fee');
        $push($rows, $seen, 'shipping_cost', 'Biaya Kirim ke Jasa Kirim', $this->actual_shipping_fee ?: $this->shipping_cost_amount, 'shipping', 'actual_shipping_fee');
        $push($rows, $seen, 'shipping_subsidy', 'Subsidi / Potongan Ongkir', $this->shipping_fee_subsidy, 'shipping', 'shipping_fee_subsidy');
        $push($rows, $seen, 'insurance_fee', 'Premi / Asuransi', $this->shipping_insurance_fee, 'fee', 'shipping_insurance_fee');
        $push($rows, $seen, 'tax', 'Pajak', $this->escrow_tax, 'tax', 'escrow_tax');
        $push($rows, $seen, 'other_fee', 'Premi', $raw['premi'] ?? null, 'fee', 'premi');

        // Some Shopee settlement snapshots keep seller protection premiums in
        // raw_json while the normalized insurance column is empty. Surface
        // those charges as other fees so the analytics ledger does not omit
        // them or mix them into the primary shipping charge.
        if ((float) $this->shipping_insurance_fee <= 0) {
            $push($rows, $seen, 'other_fee', 'Biaya Perlindungan Pengiriman', $raw['shipping_seller_protection_fee_amount'] ?? null, 'fee', 'shipping_seller_protection_fee_amount');
            $push($rows, $seen, 'other_fee', 'Premi Perlindungan Pengiriman', $raw['delivery_seller_protection_fee_premium_amount'] ?? null, 'fee', 'delivery_seller_protection_fee_premium_amount');
        }

        return $rows;
    }

    /**
     * Return totals per burden category.
     *
     * @param  array<int, array{label: string, amount: float, category: string, source?: string}>|null  $breakdown
     * @return array{seller: float, buyer: float, platform: float, voucher: float, adjustment: float, total: float}
     */
    public function marketplaceFeeCategoryTotals(?array $breakdown = null): array
    {
        $breakdown ??= $this->marketplaceFeeBreakdown();

        $totals = [
            'seller' => 0.0,
            'buyer' => 0.0,
            'platform' => 0.0,
            'voucher' => 0.0,
            'adjustment' => 0.0,
            'total' => 0.0,
        ];

        foreach ($breakdown as $row) {
            $category = $row['category'] ?? 'seller';
            $amount = (float) ($row['amount'] ?? 0);

            if (! isset($totals[$category])) {
                $totals[$category] = 0.0;
            }

            $totals[$category] += $amount;
            $totals['total'] += $amount;
        }

        return $totals;
    }

    private function looksLikeFeeField(string $key): bool
    {
        $key = strtolower($key);

        return (bool) preg_match('/(?:fee|commission|tax|insurance|premi|campaign|promo|refund|adjust|surcharge|charge|deduct|potongan|subsidy|ads?|ad_cost|voucher)/i', $key)
            && ! preg_match('/(?:buyer_|coin|gross|net|payment|amount_paid|buyer_total|order_selling_price|cost_of_goods_sold|escrow_amount|settlement_time|create_time|update_time)/i', $key);
    }

    private function feeBreakdownCategoryForKey(string $key): string
    {
        $key = strtolower($key);

        if (preg_match('/(?:buyer_|buyer_transaction_fee|credit_card_transaction_fee|actual_shipping_fee|estimated_shipping_fee|buyer_paid_shipping_fee)/i', $key)) {
            return 'buyer';
        }

        if (preg_match('/voucher/i', $key)) {
            return 'voucher';
        }

        if (preg_match('/(?:refund|adjust|drc_adjustable_refund|seller_return_refund)/i', $key)) {
            return 'adjustment';
        }

        if (preg_match('/(?:shipping_fee_subsidy|shopee_shipping_rebate|subsidy|rebate|platform)/i', $key)) {
            return 'platform';
        }

        return 'seller';
    }
}
