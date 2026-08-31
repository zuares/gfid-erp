<?php

namespace App\Console\Commands;

use App\Services\Marketplace\Finance\MarketplaceFinanceBackfillService;
use Illuminate\Console\Command;

class MarketplaceFinanceBackfillCommand extends Command
{
    protected $signature = 'marketplace:finance-backfill
                            {--apply : Tulis hasil backfill ke tabel finance baru (default: dry-run)}
                            {--source=* : Batasi sumber: order_settlements, income_estimates, mp_incomes, payouts}
                            {--store_id= : Batasi satu store_id}';

    protected $description = 'Backfill Marketplace Finance dari sumber legacy secara idempotent (default dry-run)';

    public function handle(MarketplaceFinanceBackfillService $backfill): int
    {
        $sources = $this->option('source') ?: null;
        $storeId = $this->option('store_id');
        if ($storeId !== null && (! ctype_digit((string) $storeId) || (int) $storeId < 1)) {
            $this->error('Opsi --store_id harus berupa angka positif.');

            return self::INVALID;
        }

        $result = $backfill->run([
            'dry_run' => ! $this->option('apply'),
            'sources' => $sources,
            'store_id' => $storeId !== null ? (int) $storeId : null,
        ]);

        $this->info($result['mode'] === 'dry-run'
            ? 'MODE DRY-RUN: tidak ada data yang ditulis.'
            : 'MODE APPLY: hasil backfill ditulis ke tabel finance baru.');
        $this->table(
            ['Sumber', 'Scan', 'Created', 'Updated', 'Unchanged', 'Unmatched', 'Duplicate', 'Error'],
            collect($result['sources'])->map(fn (array $stats, string $source): array => [
                $source,
                $stats['scanned'],
                $stats['created'],
                $stats['updated'],
                $stats['unchanged'],
                $stats['unmatched'],
                $stats['duplicates'],
                $stats['errors'],
            ])->values()->all(),
        );
        $this->line('SUMMARY '.json_encode($result['summary'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        foreach (array_slice($result['unmatched_rows'], 0, 20) as $row) {
            $this->warn("UNMATCHED {$row['source']}#{$row['source_id']}: {$row['reason']}");
        }
        foreach (array_slice($result['errors_rows'], 0, 20) as $row) {
            $this->error("ERROR {$row['source']}#{$row['source_id']}: {$row['message']}");
        }

        return $result['summary']['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
