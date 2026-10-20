<?php

namespace App\Services\Marketplace\Income;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderSettlement;
use App\Models\MpIncome;
use App\Services\Marketplace\MarketplaceFinancialDataQualityService;
use Carbon\Carbon;

class ImportedMarketplaceIncomeSyncService
{
    public function __construct(
        protected MarketplaceFinancialDataQualityService $financialQuality,
        protected ApplyIncomeService $applyIncome,
    ) {}

    /**
     * Reconcile income yang sudah tersimpan sebelum order marketplace tersedia.
     *
     * Jalur ini sengaja idempoten dan dipakai oleh import order/API sync maupun
     * safety-net scheduler. Jadi income tidak perlu di-upload ulang.
     */
    public function syncStoredIncomeForOrder(int $storeId, string $channel, string $orderId): array
    {
        $channel = strtolower(trim($channel));
        $orderId = trim($orderId);

        if ($storeId <= 0 || $channel === '' || $orderId === '') {
            return ['found' => false];
        }

        $income = MpIncome::query()
            ->where('store_id', $storeId)
            ->where('channel', $channel)
            ->where('platform_order_id', $orderId)
            ->first();

        if (! $income) {
            return ['found' => false];
        }

        $rawPayload = is_array($income->raw_payload)
            ? $income->raw_payload
            : (json_decode((string) $income->raw_payload, true) ?: []);
        $incomePayload = is_array($rawPayload['income'] ?? null) ? $rawPayload['income'] : [];
        $row = [
            'platform_order_id' => $orderId,
            'platform_fee_total' => (float) $income->platform_fee_total,
            'refund_total' => (float) $income->refund_total,
            'net_payout_actual' => $income->net_payout_actual,
            'released_at' => $income->released_at,
            'raw' => $incomePayload['raw'] ?? null,
        ];

        $batchId = trim((string) ($income->import_batch_id ?: 'stored-income-reconcile-'.$income->id));
        $sourceFile = (string) ($income->source_file ?: 'stored-income');
        $stats = $this->syncFromParsedRows(
            $storeId,
            [$orderId],
            [$orderId => $row],
            $batchId,
            $sourceFile,
        );

        $apply = $this->applyIncome->applyFromParsedRows(
            $storeId,
            $channel,
            [$orderId],
            [$orderId => $row],
            $batchId,
            $sourceFile,
        );

        return [
            'found' => true,
            'matched_order' => (int) ($stats['matched_orders'] ?? 0),
            'unmatched_order' => (int) ($stats['unmatched_orders'] ?? 0),
            'settlements_created' => (int) ($stats['settlements_created'] ?? 0),
            'settlements_updated' => (int) ($stats['settlements_updated'] ?? 0),
            'api_settlements_preserved' => (int) ($stats['api_settlements_preserved'] ?? 0),
            'income_mismatches' => (int) ($stats['income_mismatches'] ?? 0),
            'profit_incomplete' => (int) ($stats['profit_incomplete'] ?? 0),
            'matched_shipment' => (int) ($apply['matched_orders'] ?? 0),
            'shipments_updated' => (int) ($apply['updated_shipments'] ?? 0),
        ];
    }

    /**
     * Reconcile aggregate Income exports with marketplace order settlements.
     * Detailed API settlements always win; imports only fill an empty/imported
     * settlement and retain an explicit incomplete-finance marker.
     */
    public function syncFromParsedRows(
        int $storeId,
        array $orderIds,
        array $rowByOrder,
        string $batchId,
        string $sourceFile,
        string $tz = 'Asia/Jakarta',
    ): array {
        $stats = [
            'matched_orders' => 0,
            'unmatched_orders' => 0,
            'settlements_created' => 0,
            'settlements_updated' => 0,
            'api_settlements_preserved' => 0,
            'income_mismatches' => 0,
            'profit_incomplete' => 0,
        ];

        if ($storeId <= 0 || $orderIds === []) {
            return $stats;
        }

        $orders = MarketplaceOrder::query()
            ->with(['settlement', 'items'])
            ->where('store_id', $storeId)
            ->whereIn('channel_order_id', $orderIds)
            ->get()
            ->keyBy(fn (MarketplaceOrder $order): string => (string) $order->channel_order_id);

        foreach ($orderIds as $orderId) {
            $order = $orders->get((string) $orderId);
            $row = $rowByOrder[$orderId] ?? null;
            if (! $order || ! is_array($row)) {
                $stats['unmatched_orders']++;
                continue;
            }

            $netRaw = $row['net_payout_actual'] ?? null;
            if ($netRaw === null || $netRaw === '' || ! is_numeric($netRaw)) {
                continue;
            }

            $stats['matched_orders']++;
            $net = (float) $netRaw;
            $refund = $this->refundTotal($row['refund_total'] ?? null, $net);
            $releasedAt = $this->releasedAt($row['released_at'] ?? null, $tz);
            $settlement = $order->settlement;
            $reconciliation = [
                'status' => 'imported',
                'batch' => $batchId,
                'source_file' => $sourceFile,
                'imported_net_payout' => $net,
                'checked_at' => now()->toIso8601String(),
            ];

            if ($settlement && $this->isAuthoritative($settlement)) {
                $existingNet = (float) $settlement->final_income;
                $difference = round($net - $existingNet, 2);
                $reconciliation['existing_net_payout'] = $existingNet;
                $reconciliation['difference'] = $difference;
                $reconciliation['status'] = abs($difference) < 0.01 ? 'matched' : 'mismatch';

                $stats['api_settlements_preserved']++;
                if ($reconciliation['status'] === 'mismatch') {
                    $stats['income_mismatches']++;
                }

                $this->financialQuality->refreshOrder($order->fresh(['settlement', 'items']));
                $settlement->refresh();
                $settlement->forceFill([
                    'data_quality_flags' => array_merge((array) $settlement->data_quality_flags, [
                        'income_import_reconciliation' => $reconciliation,
                    ]),
                ])->saveQuietly();
                continue;
            }

            $raw = [
                'source' => 'marketplace_income_import',
                'buyer_total_amount' => $this->buyerPayment($order),
                'platform_fee_total' => (float) ($row['platform_fee_total'] ?? 0),
                'refund_total' => $refund,
                'final_income' => $net,
                'released_at' => $releasedAt?->toIso8601String(),
                'import_batch_id' => $batchId,
                'source_file' => $sourceFile,
            ];
            $values = [
                'store_id' => $storeId,
                'order_id' => $order->id,
                'channel_order_id' => (string) $order->channel_order_id,
                'buyer_payment_amount' => $this->buyerPayment($order),
                'drc_adjustable_refund' => $refund,
                'final_income' => $net,
                'settlement_time' => $releasedAt,
                'synced_at' => now(),
                'raw_json' => $raw,
                'data_status' => MarketplaceFinancialDataQualityService::SETTLEMENT_INCOMPLETE,
                'data_quality_flags' => [
                    'source' => 'marketplace_income_import',
                    'reason' => 'income_breakdown_incomplete',
                    'income_import_reconciliation' => $reconciliation,
                ],
                'data_checked_at' => now(),
            ];

            if ($settlement) {
                $settlement->forceFill($values)->save();
                $stats['settlements_updated']++;
            } else {
                $settlement = MarketplaceOrderSettlement::create($values);
                $stats['settlements_created']++;
            }

            if ($this->isImportOwned($order)) {
                $order->forceFill([
                    'status' => 'completed',
                    'order_status' => 'COMPLETED',
                    'completed_at' => $order->completed_at ?: $releasedAt,
                ])->saveQuietly();
            }

            $quality = $this->financialQuality->refreshOrder($order->fresh(['settlement', 'items']));
            $settlement->refresh();
            $settlement->forceFill([
                'data_quality_flags' => array_merge((array) $settlement->data_quality_flags, [
                    'source' => 'marketplace_income_import',
                    'reason' => 'income_breakdown_incomplete',
                    'income_import_reconciliation' => $reconciliation,
                ]),
            ])->saveQuietly();

            if (($quality['status'] ?? null) === MarketplaceFinancialDataQualityService::ORDER_INCOMPLETE) {
                $stats['profit_incomplete']++;
            }
        }

        return $stats;
    }

    private function isAuthoritative(MarketplaceOrderSettlement $settlement): bool
    {
        $raw = (array) $settlement->raw_json;
        if (($raw['source'] ?? null) === 'marketplace_income_import') {
            return false;
        }

        return $settlement->data_status === MarketplaceFinancialDataQualityService::SETTLEMENT_COMPLETE
            || data_get($settlement->data_quality_flags, 'source') === 'escrow_detail'
            || array_key_exists('final_income', $raw)
            || array_key_exists('escrow_amount', $raw)
            || array_key_exists('order_income', $raw);
    }

    private function isImportOwned(MarketplaceOrder $order): bool
    {
        return in_array((string) data_get($order->raw_json, 'source'), [
            'marketplace_import',
            'marketplace_crm_import',
            'marketplace_shipment_import',
        ], true);
    }

    private function buyerPayment(MarketplaceOrder $order): float
    {
        foreach ([$order->total_paid_customer, $order->total_amount, $order->subtotal_items] as $value) {
            if ((float) $value > 0) {
                return (float) $value;
            }
        }

        return 0.0;
    }

    private function refundTotal(mixed $value, float $net): float
    {
        if ($value !== null && $value !== '' && is_numeric($value)) {
            return max(0.0, (float) $value);
        }

        return $net < 0 ? abs($net) : 0.0;
    }

    private function releasedAt(mixed $value, string $tz): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value, $tz);
        } catch (\Throwable) {
            return null;
        }
    }
}
