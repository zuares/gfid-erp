<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ProductionOrder;
use App\Models\ProductionReceipt;
use App\Models\ProductionReceiptLine;
use App\Models\Warehouse;
use App\Services\Production\ProductionPostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionReceiptController extends Controller
{
    public function create(ProductionOrder $order)
    {
        $wip = Warehouse::where('code', 'WIP-PROD')->firstOrFail();
        $fg = Warehouse::where('code', 'WH-FG')->firstOrFail();

        // FG items: ambil dari target order (paling aman)
        $fgItemIds = $order->lines()->pluck('item_id')->unique()->values();
        $items = Item::query()
            ->whereIn('id', $fgItemIds)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return view('production.receipts.create', compact('order', 'wip', 'fg', 'items'));
    }

    public function store(Request $request, ProductionOrder $order)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.qty_good' => ['required', 'numeric', 'gt:0'],
            'lines.*.lot_id' => ['nullable', 'integer'], // optional
        ]);

        $wip = Warehouse::where('code', 'WIP-PROD')->firstOrFail();
        $fg = Warehouse::where('code', 'WH-FG')->firstOrFail();

        return DB::transaction(function () use ($data, $order, $wip, $fg) {

            $code = 'RCV-' . now()->format('Ymd-His');

            $receipt = ProductionReceipt::create([
                'code' => $code,
                'date' => $data['date'],
                'production_order_id' => $order->id,
                'from_warehouse_id' => $wip->id,
                'to_warehouse_id' => $fg->id,
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['lines'] as $ln) {
                ProductionReceiptLine::create([
                    'production_receipt_id' => $receipt->id,
                    'item_id' => $ln['item_id'],
                    'qty_good' => $ln['qty_good'],
                    'lot_id' => $ln['lot_id'] ?? null,
                    'unit_cost' => null,
                ]);
            }

            return redirect()
                ->route('production.orders.show', $order)
                ->with('success', 'FG Receipt dibuat (draft). Klik POST untuk update stok.');
        });
    }

    public function post(ProductionReceipt $receipt, ProductionPostingService $service)
    {
        $service->postReceipt($receipt);

        return redirect()
            ->back()
            ->with('success', 'FG Receipt berhasil di-post (stok terupdate).');
    }
}
