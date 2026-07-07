<?php

namespace App\Services\Production;

use App\Models\InventoryAdjustment;
use App\Services\Accounting\JournalService;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * WipNormalizationService — orkestrator normalisasi WIP (opname WIP).
 *
 * Pola aman (docs/AUDIT_WIP_PRODUCTION.md):
 *   - DRAFT: hanya menyimpan InventoryAdjustment + lines (qty_before=sistem,
 *     qty_physical=hasil hitung). TIDAK menyentuh stok.
 *   - GENERATE (saat approve): pindahkan selisih lewat InventoryService
 *     (menulis inventory_mutations) lalu JournalService (Dr/Cr 1202 vs 6115).
 *     TIDAK pernah menulis qty mentah.
 *
 * Idempotent: sekali approved + berjurnal, pemanggilan ulang tidak menduplikasi.
 */
class WipNormalizationService
{
    public function __construct(
        private InventoryService $inventory,
        private JournalService $journal,
    ) {
    }

    /**
     * Generate movement + jurnal dari sebuah draft normalisasi yang disetujui.
     *
     * @throws ValidationException
     */
    public function generate(InventoryAdjustment $adjustment, ?int $approverId = null): InventoryAdjustment
    {
        return DB::transaction(function () use ($adjustment, $approverId) {

            /** @var InventoryAdjustment $adj */
            $adj = InventoryAdjustment::query()
                ->whereKey($adjustment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (($adj->purpose ?? null) !== 'wip_normalization') {
                throw ValidationException::withMessages([
                    'adjustment' => 'Dokumen bukan normalisasi WIP.',
                ]);
            }

            // Idempotent: sudah approved + sudah ada jurnal aktif → no-op.
            $already = \App\Models\Journal::query()
                ->where('source_type', JournalService::SRC_WIP_NORMALIZATION)
                ->where('source_id', (int) $adj->id)
                ->whereNull('voided_at')
                ->exists();
            if ($adj->status === InventoryAdjustment::STATUS_APPROVED && $already) {
                return $adj;
            }

            if (!in_array($adj->status, [InventoryAdjustment::STATUS_DRAFT, InventoryAdjustment::STATUS_PENDING], true)) {
                throw ValidationException::withMessages([
                    'adjustment' => 'Status dokumen tidak bisa di-approve.',
                ]);
            }

            $adj->load('lines');
            $warehouseId = (int) $adj->warehouse_id;
            $date = $adj->date?->toDateString() ?? now()->toDateString();
            $eps = 0.000001;

            foreach ($adj->lines as $line) {
                $system   = (float) ($line->qty_before ?? 0);
                $physical = (float) ($line->qty_physical ?? $line->qty_after ?? 0);
                $diff     = $physical - $system;

                if (abs($diff) < $eps) {
                    // tidak ada selisih → tetap simpan hasil, tanpa mutasi
                    $line->qty_after  = $physical;
                    $line->qty_change = 0;
                    $line->direction  = 'in';
                    $line->save();
                    continue;
                }

                // Tulis mutasi stok (source of truth). allowNegative=false: fisik
                // untuk write-down tidak akan melebihi sistem.
                $this->inventory->adjustByDifference(
                    warehouseId: $warehouseId,
                    itemId: (int) $line->item_id,
                    qtyChange: $diff,
                    date: $date,
                    sourceType: JournalService::SRC_WIP_NORMALIZATION,
                    sourceId: (int) $adj->id,
                    notes: $line->notes ?? $adj->reason,
                    lotId: $line->lot_id ? (int) $line->lot_id : null,
                    allowNegative: false,
                    unitCostOverride: null,
                    affectLotCost: false,
                    cuttingJobBundleId: $line->cutting_job_bundle_id ? (int) $line->cutting_job_bundle_id : null,
                );

                $line->qty_after  = $physical;
                $line->qty_change = $diff;
                $line->direction  = $diff >= 0 ? 'in' : 'out';
                $line->save();
            }

            // Tandai approved sebelum posting jurnal (agar meta approved_* terisi).
            $adj->status      = InventoryAdjustment::STATUS_APPROVED;
            $adj->approved_by = $approverId ?? auth()->id();
            $adj->approved_at = now();
            $adj->save();

            // Jurnal selisih WIP (Dr/Cr 1202 vs 6115). Nilai dari mutasi di atas.
            $this->journal->postWipNormalization($adj);

            return $adj;
        });
    }

    /**
     * Void: reversal jurnal + kembalikan stok (opname dibalik).
     * Dipakai kalau normalisasi ternyata salah.
     */
    public function void(InventoryAdjustment $adjustment, ?string $reason = null): void
    {
        DB::transaction(function () use ($adjustment, $reason) {
            $adj = InventoryAdjustment::query()->whereKey($adjustment->id)->lockForUpdate()->firstOrFail();

            // Balik stok: buat mutasi kebalikan per line
            $adj->load('lines');
            $date = now()->toDateString();
            foreach ($adj->lines as $line) {
                $change = (float) ($line->qty_change ?? 0);
                if (abs($change) < 0.000001) {
                    continue;
                }

                // Ambil unit cost dari mutasi asli agar reversal simetris nilainya
                // (jejak total_cost tidak timpang).
                $orig = DB::table('inventory_mutations')
                    ->where('source_type', JournalService::SRC_WIP_NORMALIZATION)
                    ->where('source_id', (int) $adj->id)
                    ->where('item_id', (int) $line->item_id)
                    ->orderByDesc('id')
                    ->first();
                $unitCost = null;
                if ($orig && abs((float) $orig->qty_change) > 0.000001) {
                    $unitCost = abs((float) ($orig->total_cost ?? 0) / (float) $orig->qty_change);
                    if ($unitCost <= 0.000001) {
                        $unitCost = null;
                    }
                }

                $this->inventory->adjustByDifference(
                    warehouseId: (int) $adj->warehouse_id,
                    itemId: (int) $line->item_id,
                    qtyChange: -$change, // balik arah
                    date: $date,
                    sourceType: JournalService::SRC_WIP_NORMALIZATION . '_void',
                    sourceId: (int) $adj->id,
                    notes: 'VOID ' . ($adj->code ?? ''),
                    allowNegative: true,
                    unitCostOverride: $unitCost,
                    affectLotCost: false,
                    cuttingJobBundleId: $line->cutting_job_bundle_id ? (int) $line->cutting_job_bundle_id : null,
                );
            }

            $this->journal->voidWipNormalization($adj, $reason);

            $adj->status = InventoryAdjustment::STATUS_VOID;
            $adj->save();
        });
    }
}
