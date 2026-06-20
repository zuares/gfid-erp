<?php

namespace App\Services\Production;

use App\Models\InventoryAdjustment;
use App\Models\SewingPickup;
use App\Models\SewingPickupLineSupplyLine;
use App\Models\SewingPickupSupplyLine;
use App\Models\Warehouse;
use App\Services\Accounting\JournalService;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;

class SewingSupplyFulfillmentService
{
    public function __construct(
        protected InventoryService $inventory,
        protected JournalService $journal,
    ) {}

    public function fulfillApprovedAdjustment(InventoryAdjustment $adjustment): void
    {
        if ($adjustment->reference_type !== SewingPickup::class || !$adjustment->reference_id) {
            return;
        }

        $pickup = SewingPickup::query()
            ->with(['supplyLines', 'lineSupplyLines'])
            ->lockForUpdate()
            ->find($adjustment->reference_id);
        if (!$pickup || $pickup->status === 'void') {
            return;
        }

        $rmId = (int) Warehouse::query()->where('code', 'RM')->value('id');
        if (!$rmId) {
            throw new \RuntimeException('Gudang RM belum dikonfigurasi.');
        }

        $approvedQty = $adjustment->lines()
            ->where('direction', 'in')
            ->get()
            ->groupBy('item_id')
            ->map(fn($lines) => (float) $lines->sum('qty_change'));

        foreach ($pickup->supplyLines as $supply) {
            $itemId = (int) $supply->material_item_id;
            $limit = max((float) ($approvedQty[$itemId] ?? 0), 0);
            $outstanding = max((float) $supply->required_qty - (float) $supply->issued_qty, 0);
            if ($limit <= 0.000001 || $outstanding <= 0.000001) {
                continue;
            }

            $available = max($this->inventory->getOnHandQty($rmId, $itemId), 0);
            $qty = min($limit, $outstanding, $available);
            if ($qty <= 0.000001) {
                continue;
            }

            $unitCost = (float) $this->inventory->getItemIncomingUnitCost($rmId, $itemId);
            if ($unitCost <= 0) {
                throw new \RuntimeException("Harga pokok material #{$itemId} belum tersedia setelah adjustment.");
            }

            $this->inventory->stockOut(
                warehouseId: $rmId,
                itemId: $itemId,
                qty: $qty,
                date: $adjustment->date,
                sourceType: JournalService::SRC_SEWING_PICKUP_SUPPLY_FOLLOWUP,
                sourceId: (int) $adjustment->id,
                notes: "Kelengkapan menyusul {$pickup->code}",
                allowNegative: false,
                lotId: null,
                unitCostOverride: $unitCost,
                affectLotCost: false,
            );

            $ratio = (float) $supply->required_qty > 0
                ? (float) $supply->required_pcs / (float) $supply->required_qty
                : 0;
            $supply->issued_qty = min((float) $supply->issued_qty + $qty, (float) $supply->required_qty);
            $supply->issued_pcs = min(
                (float) $supply->issued_pcs + ($qty * $ratio),
                (float) $supply->required_pcs
            );
            $supply->save();

            $this->allocateToPickupLines($pickup, $itemId, $qty, $unitCost);
        }

        $this->journal->postSewingPickupSupplyFollowup($adjustment, $pickup);
    }

    protected function allocateToPickupLines(SewingPickup $pickup, int $itemId, float $qty, float $unitCost): void
    {
        $remaining = $qty;
        $lines = SewingPickupLineSupplyLine::query()
            ->where('sewing_pickup_id', $pickup->id)
            ->where('material_item_id', $itemId)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($lines as $line) {
            if ($remaining <= 0.000001) break;
            $outstanding = max((float) $line->required_qty - (float) $line->issued_qty, 0);
            $take = min($remaining, $outstanding);
            if ($take <= 0.000001) continue;

            $line->issued_qty = min((float) $line->issued_qty + $take, (float) $line->required_qty);
            $line->save();
            $remaining -= $take;

            $pickupLine = $line->sewingPickupLine()->first();
            if (!$pickupLine || (float) $pickupLine->qty_bundle <= 0) continue;

            $mutation = DB::table('inventory_mutations')
                ->where('source_type', SewingPickup::class)
                ->where('source_id', $pickup->id)
                ->where('cutting_job_bundle_id', $pickupLine->cutting_job_bundle_id)
                ->where('qty_change', '>', 0)
                ->lockForUpdate()
                ->first();
            if (!$mutation) continue;

            $additionalCost = $take * $unitCost;
            $newTotal = (float) ($mutation->total_cost ?? 0) + $additionalCost;
            $newUnit = $newTotal / (float) $mutation->qty_change;
            DB::table('inventory_mutations')->where('id', $mutation->id)->update([
                'unit_cost' => $newUnit,
                'total_cost' => $newTotal,
            ]);
        }
    }
}
