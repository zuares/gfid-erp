<?php

namespace App\Services\Production;

use App\Models\CuttingJobBundle;
use App\Models\InventoryAdjustment;
use App\Models\InventoryAdjustmentLine;
use App\Models\SewingPickupLine;
use App\Models\Warehouse;
use App\Services\Accounting\JournalService;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * WipPickupCloseService — menutup baris ambil-jahit yang menggantung.
 *
 * 3 arti "tutup" (owner/admin, langsung + voidable):
 *   settle    : sisa dianggap disetor OK → stok WIP-SEW → WIP-FIN,
 *               qty_returned_ok += sisa. Tanpa jurnal (nilai tetap 1202).
 *   write_off : sisa hilang → keluar WIP-SEW, jurnal Dr 6120 / Cr 1202.
 *               ditandai di qty_closed (bukan reject, agar RTS tidak salah).
 *   cancel    : batalkan pick → stok WIP-SEW → WIP-CUT, bundle.sewing_picked_qty
 *               dikurangi. Tanpa jurnal (nilai tetap 1202). ditandai qty_closed.
 *
 * settle memakai kolom lama (qty_returned_ok) → aman tanpa migrasi.
 * write_off & cancel butuh kolom qty_closed (migration) — di-guard di sini.
 */
class WipPickupCloseService
{
    public function __construct(
        private InventoryService $inventory,
        private JournalService $journal,
    ) {
    }

    public function generate(string $action, int $pickupLineId, float $qty, string $reason, ?int $userId = null): InventoryAdjustment
    {
        return DB::transaction(function () use ($action, $pickupLineId, $qty, $reason, $userId) {

            /** @var SewingPickupLine $line */
            $line = SewingPickupLine::query()->whereKey($pickupLineId)->lockForUpdate()->firstOrFail();

            if ($line->voided_at) {
                throw ValidationException::withMessages(['pickup' => 'Baris ambil jahit sudah void.']);
            }

            $needsClosedCol = in_array($action, ['write_off', 'cancel'], true);
            if ($needsClosedCol && ! Schema::hasColumn('sewing_pickup_lines', 'qty_closed')) {
                throw ValidationException::withMessages([
                    'pickup' => 'Fitur write-off/batalkan butuh kolom qty_closed. Jalankan `php artisan migrate` dulu.',
                ]);
            }

            $outstanding = $this->outstanding($line);
            if ($qty <= 0 || $qty > $outstanding + 1e-6) {
                throw ValidationException::withMessages([
                    'pickup' => "Qty harus 1..{$outstanding} (sisa outstanding).",
                ]);
            }

            $itemId  = (int) $line->finished_item_id;
            $wipSew  = (int) Warehouse::where('code', 'WIP-SEW')->value('id');
            $wipFin  = (int) Warehouse::where('code', 'WIP-FIN')->value('id');
            $wipCut  = (int) Warehouse::where('code', 'WIP-CUT')->value('id');
            $date    = now()->toDateString();

            $toLocationId = match ($action) {
                'settle' => $wipFin,
                'cancel' => $wipCut,
                default  => null, // write_off
            };
            $adjAction = match ($action) {
                'settle'    => InventoryAdjustment::ACTION_PICKUP_SETTLE,
                'cancel'    => InventoryAdjustment::ACTION_PICKUP_CANCEL,
                'write_off' => InventoryAdjustment::ACTION_WRITE_OFF,
                default     => throw ValidationException::withMessages(['pickup' => 'Aksi tidak dikenal.']),
            };

            $adj = InventoryAdjustment::create([
                'code'             => $this->generateCode(),
                'date'             => $date,
                'warehouse_id'     => $wipSew,
                'from_location_id' => $wipSew,
                'to_location_id'   => $toLocationId,
                'purpose'          => 'wip_cleanup',
                'action'           => $adjAction,
                'reason'           => $reason,
                'reference_type'   => SewingPickupLine::class,
                'reference_id'     => $line->id,
                'status'           => InventoryAdjustment::STATUS_PENDING,
                'created_by'       => $userId,
            ]);

            InventoryAdjustmentLine::create([
                'inventory_adjustment_id' => $adj->id,
                'item_id'                 => $itemId,
                'qty_before'              => $outstanding,
                'qty_after'               => $outstanding - $qty,
                'qty_change'              => -$qty,
                'direction'               => 'out',
                'action'                  => $adjAction,
                'sewing_pickup_line_id'   => $line->id,
                'cutting_job_bundle_id'   => $line->cutting_job_bundle_id,
            ]);

            // ── STOK ──
            if ($action === 'settle') {
                $this->inventory->transfer(
                    fromWarehouseId: $wipSew, toWarehouseId: $wipFin, itemId: $itemId, qty: $qty,
                    date: $date, sourceType: JournalService::SRC_WIP_CLEANUP, sourceId: (int) $adj->id,
                    notes: $reason, allowNegative: false, lotId: null,
                    cuttingJobBundleId: $line->cutting_job_bundle_id ? (int) $line->cutting_job_bundle_id : null,
                );
                $line->qty_returned_ok = (float) $line->qty_returned_ok + $qty;
            } elseif ($action === 'cancel') {
                $this->inventory->transfer(
                    fromWarehouseId: $wipSew, toWarehouseId: $wipCut, itemId: $itemId, qty: $qty,
                    date: $date, sourceType: JournalService::SRC_WIP_CLEANUP, sourceId: (int) $adj->id,
                    notes: $reason, allowNegative: false, lotId: null,
                    cuttingJobBundleId: $line->cutting_job_bundle_id ? (int) $line->cutting_job_bundle_id : null,
                );
                $line->qty_closed = (float) ($line->qty_closed ?? 0) + $qty;
                $line->close_action = 'cancel';
                $line->closed_at = now();
                $line->closed_by = $userId;
                // kembalikan kapasitas bundle (pick dibatalkan)
                if ($line->cutting_job_bundle_id) {
                    $bundle = CuttingJobBundle::query()->lockForUpdate()->find($line->cutting_job_bundle_id);
                    if ($bundle) {
                        $bundle->sewing_picked_qty = max(0, (float) $bundle->sewing_picked_qty - $qty);
                        // ✅ Pick dibatalkan → kalau tidak ada lagi yang terpick, status bundle
                        // harus kembali ke 'cut' (siap diambil-jahit lagi). Tanpa ini bundle
                        // tetap berlabel 'in_sewing' padahal stok sudah balik ke WIP-CUT,
                        // sehingga tampil rancu di WIP cleanup (seolah masih di WIP-SEW).
                        if ((float) $bundle->sewing_picked_qty < 0.0000001) {
                            $bundle->status = 'cut';
                        }
                        $bundle->save();
                    }
                }
            } else { // write_off
                $this->inventory->adjustByDifference(
                    warehouseId: $wipSew, itemId: $itemId, qtyChange: -$qty, date: $date,
                    sourceType: JournalService::SRC_WIP_CLEANUP, sourceId: (int) $adj->id,
                    notes: $reason, allowNegative: false, affectLotCost: false,
                    cuttingJobBundleId: $line->cutting_job_bundle_id ? (int) $line->cutting_job_bundle_id : null,
                );
                $line->qty_closed = (float) ($line->qty_closed ?? 0) + $qty;
                $line->close_action = 'write_off';
                $line->closed_at = now();
                $line->closed_by = $userId;
            }
            $line->save();

            // ✅ Sinkronkan status header pickup (draft/partial/completed/closed) setelah
            // line ditutup, supaya list pickup tidak lagi tampil "draft/sisa" padahal tuntas.
            $this->syncPickupStatus((int) $line->sewing_pickup_id);

            $adj->status = InventoryAdjustment::STATUS_APPROVED;
            $adj->approved_by = $userId;
            $adj->approved_at = now();
            $adj->save();

            // Jurnal hanya untuk write_off (postWipCleanup: pickup_settle/cancel → null).
            $this->journal->postWipCleanup($adj);

            return $adj;
        });
    }

    public function void(InventoryAdjustment $adj, ?string $reason = null): void
    {
        DB::transaction(function () use ($adj, $reason) {
            $adj = InventoryAdjustment::query()->whereKey($adj->id)->lockForUpdate()->firstOrFail();
            $adj->load('lines');
            $date = now()->toDateString();

            $wipSew = (int) Warehouse::where('code', 'WIP-SEW')->value('id');
            $wipFin = (int) Warehouse::where('code', 'WIP-FIN')->value('id');
            $wipCut = (int) Warehouse::where('code', 'WIP-CUT')->value('id');
            $action = $adj->action;

            foreach ($adj->lines as $l) {
                $qty = abs((float) $l->qty_change);
                if ($qty < 1e-6) {
                    continue;
                }
                $itemId = (int) $l->item_id;

                if ($action === InventoryAdjustment::ACTION_PICKUP_SETTLE) {
                    $this->inventory->transfer(
                        fromWarehouseId: $wipFin, toWarehouseId: $wipSew, itemId: $itemId, qty: $qty,
                        date: $date, sourceType: JournalService::SRC_WIP_CLEANUP . '_void', sourceId: (int) $adj->id,
                        notes: 'VOID ' . $adj->code, allowNegative: true,
                    );
                } elseif ($action === InventoryAdjustment::ACTION_PICKUP_CANCEL) {
                    $this->inventory->transfer(
                        fromWarehouseId: $wipCut, toWarehouseId: $wipSew, itemId: $itemId, qty: $qty,
                        date: $date, sourceType: JournalService::SRC_WIP_CLEANUP . '_void', sourceId: (int) $adj->id,
                        notes: 'VOID ' . $adj->code, allowNegative: true,
                    );
                } else { // write_off
                    $orig = DB::table('inventory_mutations')
                        ->where('source_type', JournalService::SRC_WIP_CLEANUP)
                        ->where('source_id', (int) $adj->id)->where('item_id', $itemId)
                        ->orderByDesc('id')->first();
                    $unitCost = ($orig && abs((float) $orig->qty_change) > 1e-6)
                        ? abs((float) ($orig->total_cost ?? 0) / (float) $orig->qty_change) : null;
                    $this->inventory->adjustByDifference(
                        warehouseId: $wipSew, itemId: $itemId, qtyChange: $qty, date: $date,
                        sourceType: JournalService::SRC_WIP_CLEANUP . '_void', sourceId: (int) $adj->id,
                        notes: 'VOID ' . $adj->code, allowNegative: true,
                        unitCostOverride: $unitCost && $unitCost > 1e-6 ? $unitCost : null, affectLotCost: false,
                    );
                }

                // Kembalikan penanda di pickup line
                if ($l->sewing_pickup_line_id) {
                    $line = SewingPickupLine::query()->lockForUpdate()->find($l->sewing_pickup_line_id);
                    if ($line) {
                        if ($action === InventoryAdjustment::ACTION_PICKUP_SETTLE) {
                            $line->qty_returned_ok = max(0, (float) $line->qty_returned_ok - $qty);
                        } elseif (Schema::hasColumn('sewing_pickup_lines', 'qty_closed')) {
                            $line->qty_closed = max(0, (float) ($line->qty_closed ?? 0) - $qty);
                            if ((float) $line->qty_closed <= 1e-6) {
                                $line->close_action = null;
                                $line->closed_at = null;
                                $line->closed_by = null;
                            }
                        }
                        $line->save();

                        if ($action === InventoryAdjustment::ACTION_PICKUP_CANCEL && $line->cutting_job_bundle_id) {
                            $bundle = CuttingJobBundle::query()->lockForUpdate()->find($line->cutting_job_bundle_id);
                            if ($bundle) {
                                $bundle->sewing_picked_qty = (float) $bundle->sewing_picked_qty + $qty;
                                $bundle->save();
                            }
                        }
                    }
                }
            }

            $this->journal->voidWipCleanup($adj, $reason);
            $adj->status = InventoryAdjustment::STATUS_VOID;
            $adj->save();
        });
    }

    private function outstanding(SewingPickupLine $line): float
    {
        $closed = Schema::hasColumn('sewing_pickup_lines', 'qty_closed') ? (float) ($line->qty_closed ?? 0) : 0.0;
        return max(0, (float) $line->qty_bundle - (float) $line->qty_returned_ok - (float) $line->qty_returned_reject - $closed);
    }

    private function generateCode(): string
    {
        $prefix = 'WIPP-' . now()->format('Ymd');
        $last = InventoryAdjustment::where('code', 'like', $prefix . '%')->orderByDesc('code')->value('code');
        $seq = $last ? ((int) substr($last, -3)) + 1 : 1;

        return $prefix . '-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }
}
