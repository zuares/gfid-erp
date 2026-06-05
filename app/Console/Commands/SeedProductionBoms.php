<?php

namespace App\Console\Commands;

use Database\Seeders\ItemBomSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedProductionBoms extends Command
{
    protected $signature = 'production:seed-boms {--force : Jalankan tanpa konfirmasi saat environment production}';

    protected $description = 'Generate BOM finished goods produksi sendiri kecuali kategori SHT.';

    public function handle(): int
    {
        $this->info('Generate BOM produksi sendiri...');

        $code = $this->call('db:seed', [
            '--class' => ItemBomSeeder::class,
            '--force' => (bool) $this->option('force'),
        ]);

        if ($code !== self::SUCCESS) {
            $this->error('Seeder BOM gagal dijalankan.');
            return self::FAILURE;
        }

        $rows = DB::table('items as i')
            ->join('item_categories as c', 'c.id', '=', 'i.item_category_id')
            ->leftJoin('item_boms as b', 'b.item_id', '=', 'i.id')
            ->where('i.type', 'finished_good')
            ->where('c.code', '!=', 'SHT')
            ->groupBy('c.code')
            ->orderBy('c.code')
            ->selectRaw("
                c.code as kategori,
                COUNT(i.id) as total_fg,
                SUM(CASE WHEN b.id IS NOT NULL AND b.active = 1 THEN 1 ELSE 0 END) as total_bom
            ")
            ->get();

        if ($rows->isNotEmpty()) {
            $this->newLine();
            $this->table(
                ['Kategori', 'FG', 'BOM Aktif'],
                $rows->map(fn ($r) => [
                    $r->kategori,
                    (int) $r->total_fg,
                    (int) $r->total_bom,
                ])->all()
            );
        }

        $this->info('Selesai. Jalankan dengan: php artisan production:seed-boms');

        return self::SUCCESS;
    }
}
