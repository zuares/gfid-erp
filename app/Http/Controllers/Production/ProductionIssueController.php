<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ProductionIssue;
use App\Models\ProductionIssueLine;
use App\Models\ProductionOrder;
use App\Models\Warehouse;
use App\Services\Production\ProductionPostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @deprecated Modul produksi generasi lama (production_issues). Digantikan alur
 * aktif Cutting → QC → Sewing → Finishing → Packing (lihat routes/web/production.php).
 * Tabel pendukung 0 baris per 2026-06. Dipertahankan untuk backward-compatibility;
 * jangan dihapus tanpa persetujuan. Route: routes/web/production-legacy.php.
 */
class ProductionIssueController extends Controller
{
    public function create(ProductionOrder $order)
    {
        $whRm = Warehouse::where('code', 'RM')->firstOrFail();
        $wip = Warehouse::where('code', 'WIP-PROD')->firstOrFail();

        // Minimal: dropdown semua item raw_material kalau kamu punya kolom type
        // Kalau tidak ada, tampilkan semua items dulu.
        $items = Item::query()
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return view('production.issues.create', compact('order', 'whRm', 'wip', 'items'));
    }

    public function store(Request $request, ProductionOrder $order)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.qty' => ['required', 'numeric', 'gt:0'],
            'lines.*.lot_id' => ['nullable', 'integer'], // kalau mau strict: exists:lots,id
        ]);

        $whRm = Warehouse::where('code', 'RM')->firstOrFail();
        $wip = Warehouse::where('code', 'WIP-PROD')->firstOrFail();

        return DB::transaction(function () use ($data, $order, $whRm, $wip) {

            // Code generator minimal (ganti kalau kamu punya CodeGenerator::generate)
            $code = 'ISS-' . now()->format('Ymd-His');

            $issue = ProductionIssue::create([
                'code' => $code,
                'date' => $data['date'],
                'production_order_id' => $order->id,
                'from_warehouse_id' => $whRm->id,
                'to_warehouse_id' => $wip->id,
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['lines'] as $ln) {
                ProductionIssueLine::create([
                    'production_issue_id' => $issue->id,
                    'item_id' => $ln['item_id'],
                    'qty' => $ln['qty'],
                    'lot_id' => $ln['lot_id'] ?? null,
                    'unit_cost' => null, // optional snapshot
                ]);
            }

            return redirect()
                ->route('production.orders.show', $order)
                ->with('success', 'Issue Material dibuat (draft). Klik POST untuk mengurangi stok.');
        });
    }

    public function post(ProductionIssue $issue, ProductionPostingService $service)
    {
        $service->postIssue($issue);

        return redirect()
            ->back()
            ->with('success', 'Material Issue berhasil di-post (stok terupdate).');
    }
}
