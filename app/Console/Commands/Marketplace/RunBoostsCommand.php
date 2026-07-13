<?php

namespace App\Console\Commands\Marketplace;

use App\Services\MarketplaceBoostService;
use Illuminate\Console\Command;

class RunBoostsCommand extends Command
{
    protected $signature = 'marketplace:run-boosts';
    protected $description = 'Jalankan mesin Naikkan Produk: jadwal jam-tetap + rotasi otomatis (maks 5 / 4 jam)';

    public function handle(MarketplaceBoostService $service): int
    {
        $summary = $service->run();

        foreach ($summary as $row) {
            if (! empty($row['error'])) {
                $this->warn("[{$row['store']}] error: {$row['error']}");
            } elseif (! empty($row['skipped'])) {
                $this->line("[{$row['store']}] tidak ada jadwal jatuh tempo.");
            } else {
                $this->info("[{$row['store']}] naik {$row['boosted']} produk" .
                    (isset($row['items']) ? ' (' . implode(', ', $row['items']) . ')' : ''));
            }
        }

        return self::SUCCESS;
    }
}
