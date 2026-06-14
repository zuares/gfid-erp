<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SetAllowNegativeMaterials extends Command
{
    protected $signature   = 'supply:allow-negative {--dry-run : Lihat daftar tanpa mengubah}';
    protected $description = 'Set allow_negative = true untuk bahan baku dan bahan pendukung produksi (RIB, KRT, FLC, TLK, OPP, LBL, dst.)';

    // Item roles yang boleh minus
    private array $allowedRoles = [
        'production_supply',
        'raw_material',
    ];

    // Prefix kode item yang boleh minus
    private array $codePrefixes = [
        'RIB', 'KRT', 'FLC', 'TLK', 'OPP', 'LBL',
    ];

    public function handle(): int
    {
        if (! Schema::hasColumn('items', 'allow_negative')) {
            $this->error('Kolom allow_negative belum ada di tabel items. Jalankan php artisan migrate terlebih dahulu.');
            return self::FAILURE;
        }

        $isDryRun = $this->option('dry-run');

        // Build query: item_role IN (...) OR code LIKE 'RIB%' OR ...
        $query = DB::table('items')
            ->where(function ($q) {
                $q->whereIn('item_role', $this->allowedRoles);

                foreach ($this->codePrefixes as $prefix) {
                    $q->orWhere('code', 'like', $prefix . '%');
                }
            })
            ->orderBy('item_role')
            ->orderBy('code');

        $items = $query->get(['id', 'code', 'name', 'item_role', 'allow_negative']);

        if ($items->isEmpty()) {
            $this->warn('Tidak ada item yang cocok ditemukan.');
            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Code', 'Name', 'Role', 'Sudah Allow Negative?'],
            $items->map(fn($i) => [
                $i->id,
                $i->code,
                $i->name,
                $i->item_role,
                $i->allow_negative ? '✓ Ya' : '– Tidak',
            ])->toArray()
        );

        $toUpdate = $items->where('allow_negative', false);
        $this->info("Total item: {$items->count()} | Perlu diupdate: {$toUpdate->count()}");

        if ($isDryRun) {
            $this->warn('[DRY RUN] Tidak ada perubahan yang disimpan.');
            return self::SUCCESS;
        }

        if ($toUpdate->isEmpty()) {
            $this->info('Semua item sudah allow_negative = true. Tidak ada yang perlu diubah.');
            return self::SUCCESS;
        }

        if (! $this->confirm("Update {$toUpdate->count()} item menjadi allow_negative = true?", true)) {
            $this->line('Dibatalkan.');
            return self::SUCCESS;
        }

        $updated = DB::table('items')
            ->whereIn('id', $toUpdate->pluck('id'))
            ->update([
                'allow_negative' => true,
                'updated_at'     => now(),
            ]);

        $this->info("✓ {$updated} item berhasil di-set allow_negative = true.");

        return self::SUCCESS;
    }
}
