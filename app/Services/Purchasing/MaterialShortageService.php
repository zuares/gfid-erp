<?php

namespace App\Services\Purchasing;

use App\Models\ItemBomLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MaterialShortageService
{
    public function rows(): Collection
    {
        $pipeline = DB::table('inventory_stocks as s')
            ->join('warehouses as w', 'w.id', '=', 's.warehouse_id')
            ->whereIn('w.code', ['WIP-CUT', 'WIP-SEW', 'WIP-FIN'])
            ->where('s.qty', '>', 0.000001)
            ->get(['s.item_id', 's.qty', 'w.code as warehouse_code']);

        $finishedItemIds = $pipeline->pluck('item_id')->unique()->values();
        $bomLines = DB::table('item_bom_lines as bl')
            ->join('item_boms as b', 'b.id', '=', 'bl.item_bom_id')
            ->join('items as m', 'm.id', '=', 'bl.material_item_id')
            ->where('b.active', true)
            ->whereIn('b.item_id', $finishedItemIds)
            ->where('bl.is_optional', false)
            ->whereIn('bl.usage_stage', [
                ItemBomLine::STAGE_SEWING_SUPPLY,
                ItemBomLine::STAGE_PACKING_SUPPLY,
            ])
            ->get([
                'b.item_id as finished_item_id',
                'bl.material_item_id',
                'bl.usage_stage',
                'bl.qty',
                'bl.scrap_pct',
                'bl.uom',
                'm.code',
                'm.name',
                'm.unit',
            ])
            ->groupBy('finished_item_id');

        $requirements = [];
        foreach ($pipeline as $stock) {
            foreach ($bomLines->get((int) $stock->item_id, collect()) as $line) {
                if (!$this->stageApplies((string) $stock->warehouse_code, (string) $line->usage_stage)) {
                    continue;
                }

                $materialId = (int) $line->material_item_id;
                $need = (float) $stock->qty * (float) $line->qty * (1 + ((float) $line->scrap_pct / 100));
                if ($need <= 0) continue;

                $requirements[$materialId] ??= [
                    'item_id' => $materialId,
                    'code' => (string) $line->code,
                    'name' => (string) $line->name,
                    'unit' => (string) ($line->uom ?: $line->unit ?: 'pcs'),
                    'required_qty' => 0.0,
                    'sources' => [],
                ];
                $requirements[$materialId]['required_qty'] += $need;
                $key = $stock->warehouse_code . ':' . $line->usage_stage;
                $requirements[$materialId]['sources'][$key] =
                    ($requirements[$materialId]['sources'][$key] ?? 0) + $need;
            }
        }

        $rmId = (int) DB::table('warehouses')->where('code', 'RM')->value('id');
        $negativeRm = DB::table('inventory_stocks as s')
            ->join('items as i', 'i.id', '=', 's.item_id')
            ->where('s.warehouse_id', $rmId)
            ->where('s.qty', '<', -0.000001)
            ->get(['i.id', 'i.code', 'i.name', 'i.unit', 's.qty']);

        foreach ($negativeRm as $item) {
            $requirements[(int) $item->id] ??= [
                'item_id' => (int) $item->id,
                'code' => (string) $item->code,
                'name' => (string) $item->name,
                'unit' => (string) ($item->unit ?: 'pcs'),
                'required_qty' => 0.0,
                'sources' => [],
            ];
        }

        $materialIds = collect(array_keys($requirements));
        if ($materialIds->isEmpty()) return collect();

        $rmStocks = DB::table('inventory_stocks')
            ->where('warehouse_id', $rmId)
            ->whereIn('item_id', $materialIds)
            ->pluck('qty', 'item_id');
        $openPr = $this->openPurchaseRequestQty($materialIds);
        $openPo = $this->openPurchaseOrderQty($materialIds);

        return collect($requirements)
            ->map(function (array $row) use ($rmStocks, $openPr, $openPo) {
                $itemId = $row['item_id'];
                $stock = (float) ($rmStocks[$itemId] ?? 0);
                $prQty = (float) ($openPr[$itemId] ?? 0);
                $poQty = (float) ($openPo[$itemId] ?? 0);
                $incoming = $prQty + $poQty;
                $shortage = max((float) $row['required_qty'] - $stock - $incoming, 0);

                return (object) array_merge($row, [
                    'required_qty' => round((float) $row['required_qty'], 4),
                    'stock_qty' => round($stock, 4),
                    'open_pr_qty' => round($prQty, 4),
                    'open_po_qty' => round($poQty, 4),
                    'incoming_qty' => round($incoming, 4),
                    'shortage_qty' => round($shortage, 4),
                    'has_shortage' => $shortage > 0.000001,
                ]);
            })
            ->sort(function ($a, $b) {
                if ($a->has_shortage !== $b->has_shortage) {
                    return $a->has_shortage ? -1 : 1;
                }
                if (abs($a->shortage_qty - $b->shortage_qty) > 0.000001) {
                    return $b->shortage_qty <=> $a->shortage_qty;
                }
                return strcmp($a->code, $b->code);
            })
            ->values();
    }

    protected function stageApplies(string $warehouse, string $stage): bool
    {
        if ($warehouse === 'WIP-CUT') {
            return in_array($stage, [ItemBomLine::STAGE_SEWING_SUPPLY, ItemBomLine::STAGE_PACKING_SUPPLY], true);
        }

        return in_array($warehouse, ['WIP-SEW', 'WIP-FIN'], true)
            && $stage === ItemBomLine::STAGE_PACKING_SUPPLY;
    }

    protected function openPurchaseRequestQty(Collection $itemIds): Collection
    {
        return DB::table('purchase_request_lines as l')
            ->join('purchase_requests as r', 'r.id', '=', 'l.purchase_request_id')
            ->whereIn('l.item_id', $itemIds)
            ->whereIn('r.status', ['draft', 'approved'])
            ->groupBy('l.item_id')
            ->selectRaw('l.item_id, SUM(l.qty) qty')
            ->pluck('qty', 'item_id');
    }

    protected function openPurchaseOrderQty(Collection $itemIds): Collection
    {
        $received = DB::table('purchase_receipt_lines as rl')
            ->join('purchase_receipts as r', 'r.id', '=', 'rl.purchase_receipt_id')
            ->where('r.status', 'posted')
            ->groupBy('rl.purchase_order_line_id')
            ->selectRaw('rl.purchase_order_line_id, SUM(rl.qty_received) received_qty');

        return DB::table('purchase_order_lines as l')
            ->join('purchase_orders as o', 'o.id', '=', 'l.purchase_order_id')
            ->leftJoinSub($received, 'received', fn($join) => $join->on('received.purchase_order_line_id', '=', 'l.id'))
            ->whereIn('l.item_id', $itemIds)
            ->whereIn('o.status', ['draft', 'approved'])
            ->whereNull('o.closed_at')
            ->groupBy('l.item_id')
            ->selectRaw('l.item_id, SUM(CASE WHEN l.qty > COALESCE(received.received_qty,0) THEN l.qty - COALESCE(received.received_qty,0) ELSE 0 END) qty')
            ->pluck('qty', 'item_id');
    }
}
