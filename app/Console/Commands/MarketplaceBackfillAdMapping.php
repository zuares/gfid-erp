<?php

namespace App\Console\Commands;

use App\Services\AdItemMapper;
use Illuminate\Console\Command;

/**
 * Recompute mapping campaign iklan → item internal untuk data yang sudah ada.
 * Berguna setelah menambah override, atau setelah order items dipetakan ulang.
 */
class MarketplaceBackfillAdMapping extends Command
{
    protected $signature = 'marketplace:backfill-ad-mapping {--store= : Batasi ke store_id tertentu}';

    protected $description = 'Recompute mapping campaign iklan → item internal (auto resolve).';

    public function handle(AdItemMapper $mapper): int
    {
        $storeId = $this->option('store') ? (int) $this->option('store') : null;

        $this->info('Menjalankan resolusi mapping campaign iklan…');
        $changed = $mapper->backfill($storeId);
        $this->info("Selesai. {$changed} campaign berubah mapping-nya.");

        return self::SUCCESS;
    }
}
