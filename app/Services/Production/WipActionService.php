<?php

namespace App\Services\Production;

use App\Models\InventoryAdjustment;
use App\Services\Accounting\JournalService;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * WipActionService — eksekusi aksi WIP Cleanup atas satu stok WIP.
 *
 * Reuse pola aman WipNormalizationService: InventoryService untuk stok
 * (menulis inventory_mutations), JournalService untuk jurnal. Idempotent
 * + voidable. Record = InventoryAdjustment (purpose='wip_cleanup', action=X).
 *
 * Action:
 *   keep_open    → tidak melakukan apa-apa (hanya catatan)
 *   move         → transfer WIP → WIP lain (nilai tetap 1202, tanpa jurnal)
 *   finish       → transfer WIP → WH-PRD (Dr 1203 / Cr 1202)
 *   reject       → transfer WIP → REJECT (Dr 1204 / Cr 1202)
 *   write_off    → keluarkan stok (Dr 6120 / Cr 1202)
 *   close_legacy → keluarkan stok + tandai legacy (Dr 6116 / Cr 1202)
 */
class WipActionService
{
    public function __construct(
        private InventoryService $inventory,
        private JournalService $journal,
    ) {
    }

    /** Action yang memindah stok ke gudang lain (butuh to_location_id). */
    private const TRANSFER_ACTIONS = [
        InventoryAdjustment::ACTION_MOVE,
        InventoryAdjustment::ACTION_FINISH,
        InventoryAdjustment::ACTION_REJECT,
    ];

    /** Action yang mengeluarkan stok tanpa tujuan (write-down). */
    private const REMOVAL_ACTIONS = [
        InventoryAdjustment::ACTION_WRITE_OFF,
        InventoryAdjustment::ACTION_CLOSE_LEGACY,
    ];

    public function generate(InventoryAdjustment $adjustment, ?int $approverId = null): InventoryAdjustment
    {
        return DB::transaction(function () use ($adjustment, $approverId) {

            /** @var InventoryAdjustment $adj */
            $adj = InventoryAdjustment::query()->whereKey($adjustment->id)->lockForUpdate()->firstOrFail();

            if (($adj->purpose ?? null) !== 'wip_cleanup') {
                throw ValidationException::withMessages(['adjustment' => 'Dokumen bukan WIP cleanup.']);
            }

            // Idempotent
            $already = \App\Models\Journal::query()
                ->where('source_type', JournalService::SRC_WIP_CLEANUP)
                ->where('source_id', (int) $adj->id)
                ->whereNull('voided_at')
                ->exists();
            if ($adj->status === InventoryAdjustment::STATUS_APPROVED && $already) {
                return $adj;
            }
            if (!in_array($adj->status, [InventoryAdjustment::STATUS_DRAFT, InventoryAdjustment::STATUS_PENDING], true)) {
                throw ValidationException::withMessages(['adjustment' => 'Status dokumen tidak bisa diproses.']);
            }

            $action = $adj->action;
            $adj->load('lines');
            $date = $adj->date?->toDateString() ?? now()->toDateString();

            if ($action !== InventoryAdjustment::ACTION_KEEP_OPEN) {
                foreach ($adj->lines as $line) {
                    $qty = abs((float) ($line->qty_change ?? 0));
                    if ($qty < 0.000001) {
                        continue;
                    }

                    if (in_array($action, self::TRANSFER_ACTIONS, true)) {
                        $target = (int) ($adj->to_location_id ?? 0);
                        if ($target <= 0) {
                            throw ValidationException::withMessages(['adjustment' => 'Gudang tujuan wajib untuk aksi ini.']);
                        }
                        $this->inventory->transfer(
                            fromWarehouseId: (int) $adj->warehouse_id,
                            toWarehouseId: $target,
                            itemId: (int) $line->item_id,
                            qty: $qty,
                            date: $date,
                            sourceType: JournalService::SRC_WIP_CLEANUP,
                            sourceId: (int) $adj->id,
                            notes: $adj->reason,
                            allowNegative: false,
                            lotId: null,
                            cuttingJobBundleId: $line->cutting_job_bundle_id ? (int) $line->cutting_job_bundle_id : null,
                        );
                    } elseif (in_array($action, self::REMOVAL_ACTIONS, true)) {
                        $this->inventory->adjustByDifference(
                            warehouseId: (int) $adj->warehouse_id,
                            itemId: (int) $line->item_id,
                            qtyChange: -$qty,
                            date: $date,
                            sourceType: JournalService::SRC_WIP_CLEANUP,
                            sourceId: (int) $adj->id,
                            notes: $adj->reason,
                            allowNegative: false,
                            affectLotCost: false,
                            cuttingJobBundleId: $line->cutting_job_bundle_id ? (int) $line->cutting_job_bundle_id : null,
                        );
                    }
                }
            }

            if ($action === InventoryAdjustment::ACTION_CLOSE_LEGACY) {
                $adj->is_legacy = true;
            }

            $adj->status = InventoryAdjustment::STATUS_APPROVED;
            $adj->approved_by = $approverId ?? auth()->id();
            $adj->approved_at = now();
            $adj->save();

            $this->journal->postWipCleanup($adj);

            return $adj;
        });
    }

    public function void(InventoryAdjustment $adjustment, ?string $reason = null): void
    {
        DB::transaction(function () use ($adjustment, $reason) {
            $adj = InventoryAdjustment::query()->whereKey($adjustment->id)->lockForUpdate()->firstOrFail();
            $adj->load('lines');
            $date = now()->toDateString();
            $action = $adj->action;

            if ($action !== InventoryAdjustment::ACTION_KEEP_OPEN) {
                foreach ($adj->lines as $line) {
                    $qty = abs((float) ($line->qty_change ?? 0));
                    if ($qty < 0.000001) {
                        continue;
                    }

                    if (in_array($action, self::TRANSFER_ACTIONS, true)) {
                        // balik: tarik dari tujuan kembali ke WIP asal
                        $this->inventory->transfer(
                            fromWarehouseId: (int) $adj->to_location_id,
                            toWarehouseId: (int) $adj->warehouse_id,
                            itemId: (int) $line->item_id,
                            qty: $qty,
                            date: $date,
                            sourceType: JournalService::SRC_WIP_CLEANUP . '_void',
                            sourceId: (int) $adj->id,
                            notes: 'VOID ' . ($adj->code ?? ''),
                            allowNegative: true,
                        );
                    } elseif (in_array($action, self::REMOVAL_ACTIONS, true)) {
                        $orig = DB::table('inventory_mutations')
                            ->where('source_type', JournalService::SRC_WIP_CLEANUP)
                            ->where('source_id', (int) $adj->id)
                            ->where('item_id', (int) $line->item_id)
                            ->orderByDesc('id')->first();
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
                            qtyChange: $qty, // kembalikan
                            date: $date,
                            sourceType: JournalService::SRC_WIP_CLEANUP . '_void',
                            sourceId: (int) $adj->id,
                            notes: 'VOID ' . ($adj->code ?? ''),
                            allowNegative: true,
                            unitCostOverride: $unitCost,
                            affectLotCost: false,
                        );
                    }
                }
            }

            $this->journal->voidWipCleanup($adj, $reason);

            $adj->status = InventoryAdjustment::STATUS_VOID;
            $adj->save();
        });
    }
}
