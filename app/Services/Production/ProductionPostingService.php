<?php

namespace App\Services\Production;

use App\Models\InventoryMutation;
use App\Models\InventoryStock;
use App\Models\ProductionIssue;
use App\Models\ProductionReceipt;
use App\Services\Production\ProductionOrderStatusService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductionPostingService
{

    public function __construct(
        protected ProductionOrderStatusService $statusService
    ) {}

    public function postIssue(ProductionIssue $issue): void
    {
        if ($issue->status === 'posted') {
            return;
        }

        $issue->loadMissing('lines');

        DB::transaction(function () use ($issue) {

            // lock header
            $issue->refresh();

            if ($issue->status === 'posted') {
                return;
            }

            // validate stock availability in from warehouse
            foreach ($issue->lines as $line) {
                $available = InventoryStock::query()
                    ->where('warehouse_id', $issue->from_warehouse_id)
                    ->where('item_id', $line->item_id)
                    ->lockForUpdate()
                    ->value('qty');

                $available = (float) ($available ?? 0);
                $need = (float) $line->qty;

                if ($available < $need) {
                    throw ValidationException::withMessages([
                        'stock' => "Stock tidak cukup untuk item_id={$line->item_id} di WH(from). Available={$available}, Need={$need}",
                    ]);
                }

                // OUT mutation (WH-RM)
                InventoryMutation::create([
                    'date' => $issue->date,
                    'warehouse_id' => $issue->from_warehouse_id,
                    'item_id' => $line->item_id,
                    'qty_change' => $line->qty,
                    'direction' => 'out',
                    'source_type' => 'production_issue',
                    'source_id' => $issue->id,
                    'notes' => $issue->notes,
                    'lot_id' => $line->lot_id,
                    'unit_cost' => $line->unit_cost,
                    'total_cost' => $line->unit_cost ? ($line->unit_cost * $line->qty) : null,
                ]);

                // IN mutation (WIP-PROD)
                InventoryMutation::create([
                    'date' => $issue->date,
                    'warehouse_id' => $issue->to_warehouse_id,
                    'item_id' => $line->item_id,
                    'qty_change' => $line->qty,
                    'direction' => 'in',
                    'source_type' => 'production_issue',
                    'source_id' => $issue->id,
                    'notes' => $issue->notes,
                    'lot_id' => $line->lot_id,
                    'unit_cost' => $line->unit_cost,
                    'total_cost' => $line->unit_cost ? ($line->unit_cost * $line->qty) : null,
                ]);

                // update stocks (atomic)
                InventoryStock::query()
                    ->where('warehouse_id', $issue->from_warehouse_id)
                    ->where('item_id', $line->item_id)
                    ->update(['qty' => DB::raw('qty - ' . (float) $line->qty)]);

                InventoryStock::query()
                    ->where('warehouse_id', $issue->to_warehouse_id)
                    ->where('item_id', $line->item_id)
                    ->lockForUpdate()
                    ->firstOrCreate(
                        ['warehouse_id' => $issue->to_warehouse_id, 'item_id' => $line->item_id],
                        ['qty' => 0]
                    );

                InventoryStock::query()
                    ->where('warehouse_id', $issue->to_warehouse_id)
                    ->where('item_id', $line->item_id)
                    ->update(['qty' => DB::raw('qty + ' . (float) $line->qty)]);
            }

            $issue->update([
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => Auth::id(),
            ]);

            $this->statusService->recalc($issue->order);

        });
    }

    public function postReceipt(ProductionReceipt $receipt): void
    {
        if ($receipt->status === 'posted') {
            return;
        }

        $receipt->loadMissing('lines');

        DB::transaction(function () use ($receipt) {

            $receipt->refresh();
            if ($receipt->status === 'posted') {
                return;
            }

            foreach ($receipt->lines as $line) {
                $available = InventoryStock::query()
                    ->where('warehouse_id', $receipt->from_warehouse_id)
                    ->where('item_id', $line->item_id)
                    ->lockForUpdate()
                    ->value('qty');

                $available = (float) ($available ?? 0);
                $need = (float) $line->qty_good;

                if ($available < $need) {
                    throw ValidationException::withMessages([
                        'stock' => "WIP tidak cukup untuk FG item_id={$line->item_id}. Available={$available}, Need={$need}",
                    ]);
                }

                // OUT mutation (WIP-PROD)
                InventoryMutation::create([
                    'date' => $receipt->date,
                    'warehouse_id' => $receipt->from_warehouse_id,
                    'item_id' => $line->item_id,
                    'qty_change' => $line->qty_good,
                    'direction' => 'out',
                    'source_type' => 'production_receipt',
                    'source_id' => $receipt->id,
                    'notes' => $receipt->notes,
                    'lot_id' => $line->lot_id,
                    'unit_cost' => $line->unit_cost,
                    'total_cost' => $line->unit_cost ? ($line->unit_cost * $line->qty_good) : null,
                ]);

                // IN mutation (WH-FG)
                InventoryMutation::create([
                    'date' => $receipt->date,
                    'warehouse_id' => $receipt->to_warehouse_id,
                    'item_id' => $line->item_id,
                    'qty_change' => $line->qty_good,
                    'direction' => 'in',
                    'source_type' => 'production_receipt',
                    'source_id' => $receipt->id,
                    'notes' => $receipt->notes,
                    'lot_id' => $line->lot_id,
                    'unit_cost' => $line->unit_cost,
                    'total_cost' => $line->unit_cost ? ($line->unit_cost * $line->qty_good) : null,
                ]);

                // update stocks
                InventoryStock::query()
                    ->where('warehouse_id', $receipt->from_warehouse_id)
                    ->where('item_id', $line->item_id)
                    ->update(['qty' => DB::raw('qty - ' . (float) $line->qty_good)]);

                InventoryStock::query()
                    ->where('warehouse_id', $receipt->to_warehouse_id)
                    ->where('item_id', $line->item_id)
                    ->lockForUpdate()
                    ->firstOrCreate(
                        ['warehouse_id' => $receipt->to_warehouse_id, 'item_id' => $line->item_id],
                        ['qty' => 0]
                    );

                InventoryStock::query()
                    ->where('warehouse_id', $receipt->to_warehouse_id)
                    ->where('item_id', $line->item_id)
                    ->update(['qty' => DB::raw('qty + ' . (float) $line->qty_good)]);
            }

            $receipt->update([
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => Auth::id(),
            ]);

            $this->statusService->recalc($receipt->order);

        });
    }
}
