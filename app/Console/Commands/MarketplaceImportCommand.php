<?php

namespace App\Console\Commands;

use App\Services\Marketplace\Import\MpImportService;
use Illuminate\Console\Command;

class MarketplaceImportCommand extends Command
{
    protected $signature = 'marketplace:import
        {channel : shopee|tiktok}
        {file : path file xlsx/csv}
        {--store_id= : store id}
        {--dry-run : parse only, no DB write}';

    protected $description = 'Import marketplace shipment file into mp_shipments & mp_shipment_items.';

    public function handle(MpImportService $svc): int
    {
        $channel = (string) $this->argument('channel');
        $file = (string) $this->argument('file');
        $storeId = (int) ($this->option('store_id') ?? 0);
        $dry = (bool) $this->option('dry-run');

        if ($storeId <= 0) {
            $this->error('--store_id wajib diisi');
            return self::FAILURE;
        }

        $sourceFile = basename($file);

        $res = $svc->import($channel, $file, $storeId, $sourceFile, $dry);

        $stats = $res['stats'];
        $this->info("Import channel={$stats['channel']} store_id={$stats['store_id']} file={$stats['source_file']}");
        $this->line("Parsed shipments={$stats['shipments_parsed']} items={$stats['items_parsed']}");
        $this->line("Inserted shipments={$stats['inserted_shipments']} updated shipments={$stats['updated_shipments']}");
        $this->line("Inserted items={$stats['inserted_items']}");
        $this->line("Batch={$stats['import_batch_id']} dry_run=" . ($stats['dry_run'] ? 'YES' : 'NO'));

        return self::SUCCESS;
    }
}
