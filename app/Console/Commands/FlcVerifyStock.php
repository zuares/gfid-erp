<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FlcVerifyStock extends Command
{
    protected $signature = 'flc:verify-stock';

    protected $description = 'Verifikasi post-repair: cek stok FLC, lot x1000 tersisa, dan lot avg_cost=0.';

    public function handle(): int
    {
        $this->info('========================================');
        $this->info('  VERIFIKASI STOK FLC — ' . now()->format('Y-m-d H:i:s'));
        $this->info('========================================');

        $allOk = true;

        // ─── 1. Cek item unit ────────────────────────────────────────────
        $this->newLine();
        $this->info('【1】 CEK items.unit FLC');

        $itemsStillPcs = DB::table('items')
            ->where('code', 'like', 'FLC%')
            ->where('unit', 'pcs')
            ->count();

        if ($itemsStillPcs > 0) {
            $this->error("  [FAIL] Masih ada {$itemsStillPcs} item FLC dengan unit=pcs!");
            $allOk = false;
        } else {
            $this->info('  [OK] Semua item FLC sudah unit=kg.');
        }

        $itemsAll = DB::table('items')
            ->where('code', 'like', 'FLC%')
            ->orderBy('code')
            ->get(['code', 'unit']);
        $this->table(['Code', 'Unit'], $itemsAll->map(fn($r) => [$r->code, $r->unit]));

        // ─── 2. Cek lot x1000 tersisa ────────────────────────────────────
        $this->newLine();
        $this->info('【2】 CEK LOT x1000 TERSISA (qty_onhand > initial_qty × 100)');

        $x1000Remaining = DB::table('lots as l')
            ->join('items as i', 'i.id', '=', 'l.item_id')
            ->where('i.code', 'like', 'FLC%')
            ->whereRaw('l.qty_onhand > l.initial_qty * 100')
            ->get(['l.id', 'l.code', 'i.code as item_code', 'l.initial_qty', 'l.qty_onhand']);

        if ($x1000Remaining->isNotEmpty()) {
            $this->error("  [FAIL] Masih ada {$x1000Remaining->count()} lot x1000 yang belum direpair!");
            $this->table(
                ['ID', 'Lot', 'Item', 'initial_qty', 'qty_onhand'],
                $x1000Remaining->map(fn($r) => [
                    $r->id, $r->code, $r->item_code,
                    number_format($r->initial_qty, 4),
                    number_format($r->qty_onhand, 3),
                ])
            );
            $allOk = false;
        } else {
            $this->info('  [OK] Tidak ada lot FLC x1000 tersisa.');
        }

        // ─── 3. Lot avg_cost = 0 ─────────────────────────────────────────
        $this->newLine();
        $this->info('【3】 LOT FLC avg_cost = 0 (informasi — belum tentu error)');

        $zeroCost = DB::table('lots as l')
            ->join('items as i', 'i.id', '=', 'l.item_id')
            ->where('i.code', 'like', 'FLC%')
            ->where(DB::raw('ROUND(l.avg_cost, 4)'), '=', 0)
            ->count();

        $this->comment("  Total lot avg_cost=0: {$zeroCost}");
        if ($zeroCost > 0) {
            $this->comment('  → Isi avg_cost secara manual jika harga diketahui.');
            $this->comment('    Jalankan: php artisan flc:audit untuk daftar lengkap.');
        }

        // ─── 4. Total stok FLC setelah repair ───────────────────────────
        $this->newLine();
        $this->info('【4】 TOTAL STOK FLC per ITEM (setelah repair)');

        $stockSummary = DB::table('lots as l')
            ->join('items as i', 'i.id', '=', 'l.item_id')
            ->where('i.code', 'like', 'FLC%')
            ->groupBy('i.code', 'i.unit')
            ->orderBy('i.code')
            ->selectRaw("
                i.code as item_code,
                i.unit,
                COUNT(l.id) as total_lot,
                SUM(CASE WHEN l.qty_onhand > l.initial_qty * 100 THEN 1 ELSE 0 END) as lot_x1000_sisa,
                ROUND(SUM(l.qty_onhand), 3) as total_onhand,
                ROUND(SUM(CASE WHEN ROUND(l.avg_cost,4) > 0 THEN l.total_cost ELSE 0 END), 0) as total_cost
            ")
            ->get();

        $this->table(
            ['Item', 'Unit', 'Total Lot', 'x1000 Sisa', 'Total Stok (kg)', 'Total Cost'],
            $stockSummary->map(fn($r) => [
                $r->item_code,
                $r->unit,
                $r->total_lot,
                $r->lot_x1000_sisa > 0 ? "⚠ {$r->lot_x1000_sisa}" : '✓ 0',
                number_format($r->total_onhand, 3),
                number_format($r->total_cost, 0),
            ])
        );

        // ─── 5. Lot normal (yg tidak disentuh) ───────────────────────────
        $this->newLine();
        $this->info('【5】 LOT NORMAL (verifikasi tidak berubah)');

        $normalLots = DB::table('lots as l')
            ->join('items as i', 'i.id', '=', 'l.item_id')
            ->where('i.code', 'like', 'FLC%')
            ->whereRaw('l.qty_onhand <= l.initial_qty * 100')
            ->orderBy('l.code')
            ->get(['l.id', 'l.code', 'i.code as item_code', 'l.initial_qty',
                   'l.qty_onhand', 'l.avg_cost', 'l.total_cost']);

        if ($normalLots->isEmpty()) {
            $this->comment('  (tidak ada lot normal)');
        } else {
            $this->table(
                ['ID', 'Lot', 'Item', 'initial_qty', 'qty_onhand', 'avg_cost', 'total_cost'],
                $normalLots->map(fn($r) => [
                    $r->id, $r->code, $r->item_code,
                    number_format($r->initial_qty, 4),
                    number_format($r->qty_onhand, 4),
                    number_format($r->avg_cost, 0),
                    number_format($r->total_cost, 0),
                ])
            );
        }

        // ─── 6. Kesimpulan ───────────────────────────────────────────────
        $this->newLine();
        if ($allOk) {
            $this->info('✅ VERIFIKASI LULUS — data stok FLC sudah bersih.');
        } else {
            $this->error('❌ VERIFIKASI GAGAL — ada masalah yang perlu ditindaklanjuti (lihat di atas).');
        }

        return $allOk ? self::SUCCESS : self::FAILURE;
    }
}
