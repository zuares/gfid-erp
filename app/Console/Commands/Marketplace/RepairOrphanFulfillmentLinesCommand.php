<?php

namespace App\Console\Commands\Marketplace;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RepairOrphanFulfillmentLinesCommand extends Command
{
    protected $signature = 'marketplace:repair-orphan-fulfillment-lines
                            {--dry-run : Tampilkan orphan tanpa mengubah data}
                            {--force : Izinkan perubahan data saat environment production}';

    protected $description = 'Putuskan referensi item order yang sudah tidak ada pada fulfillment line';

    public function handle(): int
    {
        if (! Schema::hasTable('order_fulfillment_lines')) {
            $this->warn('Tabel order_fulfillment_lines belum tersedia.');

            return self::SUCCESS;
        }

        $isDryRun = (bool) $this->option('dry-run');
        if (app()->environment('production') && ! $isDryRun && ! $this->option('force')) {
            $this->error('Repair di production membutuhkan flag --force. Gunakan --dry-run untuk inspeksi.');

            return self::FAILURE;
        }

        $query = DB::table('order_fulfillment_lines as lines')
            ->leftJoin('marketplace_order_items as items', 'items.id', '=', 'lines.marketplace_order_item_id')
            ->whereNotNull('lines.marketplace_order_item_id')
            ->whereNull('items.id')
            ->select([
                'lines.id as orphan_id',
                'lines.marketplace_order_item_id',
                'lines.marketplace_sku',
                'lines.marketplace_item_name',
            ])
            ->orderBy('lines.id');

        $count = (clone $query)->count('lines.id');
        if ($count === 0) {
            $this->info('Tidak ada orphan fulfillment line.');

            return self::SUCCESS;
        }

        $this->warn("Ditemukan {$count} orphan fulfillment line.");
        $this->table(
            ['ID', 'Missing item ID', 'SKU', 'Nama item'],
            (clone $query)->limit(20)->get()->map(fn ($line) => [
                $line->orphan_id,
                $line->marketplace_order_item_id,
                $line->marketplace_sku,
                str($line->marketplace_item_name)->limit(60),
            ])->all()
        );

        if ($isDryRun) {
            $this->info('Dry-run selesai; tidak ada data yang diubah.');

            return self::SUCCESS;
        }

        $repaired = 0;
        $query->chunkById(500, function ($lines) use (&$repaired): void {
            $ids = $lines->pluck('orphan_id')->all();
            if ($ids === []) {
                return;
            }

            $repaired += DB::table('order_fulfillment_lines')
                ->whereIn('id', $ids)
                ->update([
                    'marketplace_order_item_id' => null,
                    'updated_at' => now(),
                ]);
        }, 'lines.id', 'orphan_id');

        $remaining = DB::table('order_fulfillment_lines as lines')
            ->leftJoin('marketplace_order_items as items', 'items.id', '=', 'lines.marketplace_order_item_id')
            ->whereNotNull('lines.marketplace_order_item_id')
            ->whereNull('items.id')
            ->count('lines.id');

        $this->info("{$repaired} fulfillment line diperbaiki; tersisa {$remaining} orphan.");

        return $remaining === 0 ? self::SUCCESS : self::FAILURE;
    }
}
