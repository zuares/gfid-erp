<?php

namespace App\Services\Marketplace\Income\Adapters;

interface MpIncomeAdapterInterface
{
    public function channel(): string;

    /**
     * Return normalized income rows grouped by order id:
     * [
     *   [
     *     'platform_order_id' => '...',
     *     'released_at' => 'Y-m-d H:i:s'|null,
     *     'platform_fee_total' => 0,   // int rupiah
     *     'refund_total' => 0,         // int rupiah
     *     'net_payout_actual' => 0,    // int rupiah
     *     'raw' => [...],              // raw per-order
     *   ],
     * ]
     */
    public function parse(string $path, string $sourceFile): array;
}
