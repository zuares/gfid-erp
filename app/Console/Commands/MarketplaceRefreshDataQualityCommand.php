<?php

namespace App\Console\Commands;

use App\Models\MarketplaceOrder;
use App\Services\Marketplace\MarketplaceFinancialDataQualityService;
use Illuminate\Console\Command;

class MarketplaceRefreshDataQualityCommand extends Command
{
    protected $signature = 'marketplace:refresh-data-quality
        {--store_id=all : Store ID atau all}
        {--dry-run : Audit tanpa menyimpan status}';

    protected $description = 'Refresh status kelengkapan settlement, mapping, HPP, dan kesiapan finansial marketplace.';

    public function handle(MarketplaceFinancialDataQualityService $quality): int
    {
        $storeId = (string) $this->option('store_id');
        $dryRun = (bool) $this->option('dry-run');

        $query = MarketplaceOrder::query()
            ->with(['settlement', 'items'])
            ->when($storeId !== 'all', fn ($q) => $q->where('store_id', (int) $storeId))
            ->orderBy('id');

        $counts = [
            'orders' => 0,
            'ready' => 0,
            'incomplete' => 0,
            'not_applicable' => 0,
            'settlement_complete' => 0,
            'settlement_incomplete' => 0,
            'settlement_unknown' => 0,
        ];

        $query->chunkById(200, function ($orders) use ($quality, $dryRun, &$counts) {
            foreach ($orders as $order) {
                $counts['orders']++;

                if ($order->settlement) {
                    $settlement = $quality->assessSettlement($order->settlement->raw_json);
                    $counts['settlement_' . $settlement['status']]++;
                } else {
                    $counts['settlement_incomplete']++;
                }

                $assessment = $dryRun
                    ? $quality->assessOrder($order)
                    : $quality->refreshOrder($order);

                $counts[$assessment['status']]++;
            }
        });

        $this->info(($dryRun ? 'Audit selesai (DRY-RUN).' : 'Status kualitas data berhasil diperbarui.')
            . " Orders: {$counts['orders']}.");
        $this->line("Ready: {$counts['ready']}");
        $this->line("Incomplete: {$counts['incomplete']}");
        $this->line("Not applicable: {$counts['not_applicable']}");
        $this->line("Settlement complete: {$counts['settlement_complete']}");
        $this->line("Settlement incomplete: {$counts['settlement_incomplete']}");

        return self::SUCCESS;
    }
}
