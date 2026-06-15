<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FlcAudit extends Command
{
    protected $signature = 'flc:audit';

    protected $description = 'Audit stok item & lot FLC — tampilkan normal, x1000, avg_cost=0, dan total stok.';

    public function handle(): int
    {
        $this->info('========================================');
        $this->info('  AUDIT STOK FLC — ' . now()->format('Y-m-d H:i:s'));
        $this->info('========================================');

        // ─── 1. Daftar item FLC ──────────────────────────────────────────
        $this->newLine();
        $this->info('【1】 ITEM FLC');
        $items = DB::table('items')
            ->where('code', 'like', 'FLC%')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'unit']);

        $this->table(
            ['ID', 'Code', 'Name', 'Unit'],
            $items->map(fn($r) => [
                $r->id,
                $r->code,
                $r->name,
                $r->unit === 'pcs' ? '⚠ pcs (PERLU UBAH)' : $r->unit,
            ])
        );

        $itemPcsCount = $items->where('unit', 'pcs')->count();
        $this->comment("  Items masih unit 'pcs': {$itemPcsCount} / {$items->count()}");

        // ─── 2. Lot normal ───────────────────────────────────────────────
        $this->newLine();
        $this->info('【2】 LOT FLC NORMAL (qty_onhand ≤ initial_qty × 100)');
        $normalLots = DB::table('lots as l')
            ->join('items as i', 'i.id', '=', 'l.item_id')
            ->where('i.code', 'like', 'FLC%')
            ->whereRaw('l.qty_onhand <= l.initial_qty * 100')
            ->orderBy('l.code')
            ->get(['l.id', 'l.code', 'i.code as item_code', 'i.unit',
                   'l.initial_qty', 'l.qty_onhand', 'l.avg_cost', 'l.total_cost', 'l.status']);

        if ($normalLots->isEmpty()) {
            $this->line('  (tidak ada lot normal)');
        } else {
            $this->table(
                ['ID', 'Lot Code', 'Item', 'Unit', 'initial_qty', 'qty_onhand', 'avg_cost', 'total_cost', 'status'],
                $normalLots->map(fn($r) => [
                    $r->id, $r->code, $r->item_code, $r->unit,
                    number_format($r->initial_qty, 4),
                    number_format($r->qty_onhand, 4),
                    number_format($r->avg_cost, 0),
                    number_format($r->total_cost, 0),
                    $r->status,
                ])
            );
        }

        // ─── 3. Lot x1000 ────────────────────────────────────────────────
        $this->newLine();
        $this->info('【3】 LOT FLC x1000 — qty_onhand > initial_qty × 100 (PERLU REPAIR)');
        $x1000Lots = DB::table('lots as l')
            ->join('items as i', 'i.id', '=', 'l.item_id')
            ->where('i.code', 'like', 'FLC%')
            ->whereRaw('l.qty_onhand > l.initial_qty * 100')
            ->orderBy('i.code')
            ->orderBy('l.code')
            ->get(['l.id', 'l.code', 'i.code as item_code', 'i.unit',
                   'l.initial_qty', 'l.qty_onhand', 'l.avg_cost', 'l.status']);

        $this->table(
            ['ID', 'Lot Code', 'Item', 'Unit', 'initial_qty', 'qty_onhand (SALAH)', 'qty_onhand/1000 (BETUL)', 'avg_cost'],
            $x1000Lots->map(fn($r) => [
                $r->id, $r->code, $r->item_code, $r->unit,
                number_format($r->initial_qty, 4),
                number_format($r->qty_onhand, 3),
                number_format($r->qty_onhand / 1000, 4),
                number_format($r->avg_cost, 0),
            ])
        );
        $this->warn("  Total lot x1000: {$x1000Lots->count()}");

        // ─── 4. Lot avg_cost = 0 ─────────────────────────────────────────
        $this->newLine();
        $this->info('【4】 LOT FLC avg_cost = 0');
        $zeroCostLots = DB::table('lots as l')
            ->join('items as i', 'i.id', '=', 'l.item_id')
            ->where('i.code', 'like', 'FLC%')
            ->where(DB::raw('ROUND(l.avg_cost, 4)'), '=', 0)
            ->count();

        // Cek apakah mutation punya unit_cost
        $lotsWithMutationCost = DB::table('lots as l')
            ->join('items as i', 'i.id', '=', 'l.item_id')
            ->join('inventory_mutations as m', function ($j) {
                $j->on('m.lot_id', '=', 'l.id')
                  ->where('m.direction', '=', 'in')
                  ->where('m.unit_cost', '>', 0);
            })
            ->where('i.code', 'like', 'FLC%')
            ->where(DB::raw('ROUND(l.avg_cost, 4)'), '=', 0)
            ->selectRaw('l.id, l.code, i.code as item_code, l.initial_qty, l.qty_onhand, m.unit_cost as mutation_cost')
            ->groupBy('l.id')
            ->limit(10)
            ->get();

        $this->line("  Total lot avg_cost=0: {$zeroCostLots}");
        if ($lotsWithMutationCost->isNotEmpty()) {
            $this->comment("  Contoh: lot avg_cost=0 tapi mutation punya unit_cost:");
            $this->table(
                ['ID', 'Lot', 'Item', 'initial_qty', 'qty_onhand', 'mutation unit_cost'],
                $lotsWithMutationCost->map(fn($r) => [
                    $r->id, $r->code, $r->item_code,
                    number_format($r->initial_qty, 4),
                    number_format($r->qty_onhand, 3),
                    number_format($r->mutation_cost, 0),
                ])
            );
            $this->comment('  → avg_cost TIDAK diisi otomatis. Isi manual jika diperlukan.');
        }

        // ─── 5. Total stok ───────────────────────────────────────────────
        $this->newLine();
        $this->info('【5】 RINGKASAN STOK FLC per ITEM (sebelum repair)');
        $stockSummary = DB::table('lots as l')
            ->join('items as i', 'i.id', '=', 'l.item_id')
            ->where('i.code', 'like', 'FLC%')
            ->groupBy('i.code', 'i.unit')
            ->orderBy('i.code')
            ->selectRaw("
                i.code as item_code,
                i.unit,
                COUNT(l.id) as total_lot,
                SUM(CASE WHEN l.qty_onhand > l.initial_qty * 100 THEN 1 ELSE 0 END) as lot_x1000,
                SUM(CASE WHEN l.qty_onhand <= l.initial_qty * 100 THEN 1 ELSE 0 END) as lot_normal,
                ROUND(SUM(l.qty_onhand), 3) as total_onhand_raw,
                ROUND(SUM(CASE WHEN l.qty_onhand > l.initial_qty * 100 THEN l.qty_onhand / 1000.0 ELSE l.qty_onhand END), 3) as total_onhand_fixed,
                ROUND(SUM(l.total_cost), 0) as total_cost
            ")
            ->get();

        $this->table(
            ['Item', 'Unit', 'Lot Total', 'x1000', 'Normal', 'Stok RAW (salah)', 'Stok FIXED (estimasi)', 'Total Cost'],
            $stockSummary->map(fn($r) => [
                $r->item_code, $r->unit,
                $r->total_lot, $r->lot_x1000, $r->lot_normal,
                number_format($r->total_onhand_raw, 2),
                number_format($r->total_onhand_fixed, 2),
                number_format($r->total_cost, 0),
            ])
        );

        $this->newLine();
        $this->line('Jalankan <info>php artisan flc:repair-stock --dry-run</info> untuk preview repair.');
        $this->line('Jalankan <info>php artisan flc:repair-stock</info> untuk eksekusi.');

        return self::SUCCESS;
    }
}
