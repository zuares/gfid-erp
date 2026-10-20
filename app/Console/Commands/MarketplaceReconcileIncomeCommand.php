<?php

namespace App\Console\Commands;

use App\Models\MpIncome;
use App\Services\Marketplace\Income\ImportedMarketplaceIncomeSyncService;
use Illuminate\Console\Command;

class MarketplaceReconcileIncomeCommand extends Command
{
    protected $signature = 'marketplace:reconcile-income
        {--store_id= : Batasi ke store tertentu}
        {--channel= : shopee|tiktok}
        {--limit=500 : Maksimal order per run}';

    protected $description = 'Automatically reconcile stored marketplace income with orders and shipments.';

    public function handle(ImportedMarketplaceIncomeSyncService $incomeSync): int
    {
        $storeId = (int) ($this->option('store_id') ?: 0);
        $channel = strtolower(trim((string) ($this->option('channel') ?: '')));
        $limit = max(1, min(5000, (int) ($this->option('limit') ?: 500)));

        $rows = MpIncome::query()
            ->from('mp_incomes as i')
            ->join('marketplace_orders as o', function ($join): void {
                $join->on('o.store_id', '=', 'i.store_id')
                    ->on('o.channel_order_id', '=', 'i.platform_order_id');
            })
            ->leftJoin('marketplace_order_settlements as s', 's.order_id', '=', 'o.id')
            ->where(function ($query): void {
                // No settlement atau masih placeholder dari order sync.
                $query->whereNull('s.id')->orWhere('s.final_income', 0);
            })
            ->when($storeId > 0, fn ($query) => $query->where('i.store_id', $storeId))
            ->when($channel !== '', fn ($query) => $query->where('i.channel', $channel))
            ->whereIn('i.channel', ['shopee', 'tiktok'])
            ->select(['i.store_id', 'i.channel', 'i.platform_order_id'])
            ->distinct()
            ->orderBy('i.store_id')
            ->orderBy('i.platform_order_id')
            ->limit($limit)
            ->get();

        $stats = [
            'scanned' => $rows->count(),
            'found' => 0,
            'settlements_created' => 0,
            'settlements_updated' => 0,
            'shipments_updated' => 0,
            'mismatches' => 0,
        ];

        foreach ($rows as $row) {
            $result = $incomeSync->syncStoredIncomeForOrder(
                (int) $row->store_id,
                (string) $row->channel,
                (string) $row->platform_order_id,
            );

            if (empty($result['found'])) {
                continue;
            }

            $stats['found']++;
            $stats['settlements_created'] += (int) ($result['settlements_created'] ?? 0);
            $stats['settlements_updated'] += (int) ($result['settlements_updated'] ?? 0);
            $stats['shipments_updated'] += (int) ($result['shipments_updated'] ?? 0);
            $stats['mismatches'] += (int) ($result['income_mismatches'] ?? 0);
        }

        $this->line(sprintf(
            'Income reconcile scanned=%d found=%d settlements_created=%d settlements_updated=%d shipments_updated=%d mismatches=%d',
            $stats['scanned'],
            $stats['found'],
            $stats['settlements_created'],
            $stats['settlements_updated'],
            $stats['shipments_updated'],
            $stats['mismatches'],
        ));

        return self::SUCCESS;
    }
}
