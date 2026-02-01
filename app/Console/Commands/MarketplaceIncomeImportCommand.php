<?php

namespace App\Console\Commands;

use App\Services\Marketplace\Income\MpIncomeImportService;
use Illuminate\Console\Command;

class MarketplaceIncomeImportCommand extends Command
{
    protected $signature = 'marketplace:import-income
        {channel : shopee|tiktok}
        {file : path to income xlsx}
        {--store_id= : Store ID (required)}
        {--dry-run : parse only, no DB write}';

    protected $description = 'Import marketplace income/payout and store per-order payout (mp_incomes) + optionally apply to mp_shipments.';

    public function handle(MpIncomeImportService $svc): int
    {
        $channel = strtolower(trim((string) $this->argument('channel')));
        $file = (string) $this->argument('file');
        $dry = (bool) $this->option('dry-run');

        $storeId = (int) ($this->option('store_id') ?: 0);
        if ($storeId <= 0) {
            $this->error('store_id wajib. contoh: php artisan marketplace:import-income shopee "/path/file.xlsx" --store_id=1 --dry-run');
            return self::FAILURE;
        }

        if (!is_file($file) || !is_readable($file)) {
            $this->error("File tidak ditemukan atau tidak bisa dibaca: {$file}");
            return self::FAILURE;
        }

        $sourceFile = basename($file);

        // NOTE: signature service baru: import($channel, $path, $sourceFile, $storeId, $dryRun)
        $res = $svc->import($channel, $file, $sourceFile, $storeId, $dry);

        $stats = $res['stats'] ?? [];

        // Backward compatible prints (kalau key belum ada, fallback ke 0)
        $batch = $stats['batch'] ?? '-';
        $rowsParsed = (int) ($stats['rows_parsed'] ?? 0);
        $ordersParsed = (int) ($stats['orders_parsed'] ?? 0);
        $incomesUpserted = (int) ($stats['incomes_upserted'] ?? 0);

        $matched = (int) ($stats['orders_matched_shipments'] ?? $stats['orders_matched'] ?? 0);
        $unmatched = (int) ($stats['orders_unmatched_shipments'] ?? $stats['orders_unmatched'] ?? 0);
        $shipUpdated = (int) ($stats['shipments_updated'] ?? 0);

        $this->info("Income import channel={$channel} store_id={$storeId} file={$sourceFile} batch={$batch}");
        $this->line("Rows parsed={$rowsParsed} | Orders parsed={$ordersParsed} | Incomes upserted={$incomesUpserted}");
        $this->line("Matched shipments={$matched} | Unmatched shipments={$unmatched}");
        $this->line("Shipments updated={$shipUpdated} | dry_run=" . ($dry ? 'YES' : 'NO'));

        if ($dry && isset($res['sample'])) {
            $this->line("Sample:");
            $this->line(json_encode($res['sample'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        return self::SUCCESS;
    }
}
