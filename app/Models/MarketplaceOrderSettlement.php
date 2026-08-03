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
     * Build a categorized breakdown for marketplace settlement fees.
     *
     * @return array<int, array{label: string, amount: float, category: string, source?: string}>
     */
    public function marketplaceFeeBreakdown(): array
    {
        $raw = is_array($this->raw_json) ? $this->raw_json : [];
        $rows = [];
        $seen = [];
        $usedKeys = [];

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
        [$buyerShipping, $buyerShippingKey] = $consumeFirst(['buyer_paid_shipping_fee', 'actual_shipping_fee', 'estimated_shipping_fee'], null);
        if ($buyerShipping !== null && $buyerShipping !== '') {
            $push('Ongkos Kirim Dibayar Pembeli', $buyerShipping, 'buyer', $buyerShippingKey);
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
