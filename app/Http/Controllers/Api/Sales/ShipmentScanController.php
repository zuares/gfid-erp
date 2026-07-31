<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Shipment;
use App\Models\ShipmentLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShipmentScanController extends Controller
{
    /**
     * Handle scan item untuk Shipment.
     *
     * - Hanya boleh untuk shipment status "draft"
     * - Hanya item dengan type = "finished_good"
     * - Jika line sudah ada -> qty_scanned += 1
     * - Jika belum ada -> buat line baru qty_scanned = 1
     *
     * Response:
     * - JSON (untuk AJAX): { status, message, last_scanned_line_id, line, totals }
     * - Non-JSON: redirect back (fallback)
     */
    public function scan(Request $request, Shipment $shipment)
    {
        // Validasi input
        $validated = $request->validate([
            'scan_code' => ['required', 'string', 'max:255'],
        ]);

        $scanCode = trim($validated['scan_code']);

        // Shipment harus draft
        if ($shipment->status !== 'draft') {
            return $this->errorResponse(
                $request,
                'Shipment sudah tidak bisa discan (bukan draft).',
                409
            );
        }

        // Cari item: hanya finished_good
        // Sesuaikan field pencarian jika perlu (code / barcode / sku, dsb)
        $item = Item::query()
            ->where('type', 'finished_good')
            ->where(function ($q) use ($scanCode) {
                $q->where('code', $scanCode)
                    ->orWhere('barcode', $scanCode)
                    ->orWhereHas('barcodes', function ($barcodeQuery) use ($scanCode) {
                        $barcodeQuery->where('barcode', $scanCode)
                            ->where('is_active', true);
                    });
            })
            ->first();

        if (!$item) {
            return $this->errorResponse(
                $request,
                'Item tidak ditemukan atau bukan finished_good.',
                422
            );
        }

        $result = DB::transaction(function () use ($shipment, $item) {
            // Cari line existing
            /** @var \App\Models\ShipmentLine|null $line */
            $line = ShipmentLine::query()
                ->where('shipment_id', $shipment->id)
                ->where('item_id', $item->id)
                ->lockForUpdate()
                ->first();

            if ($line) {
                $line->qty_scanned = (int) $line->qty_scanned + 1;
                $line->save();
            } else {
                $line = ShipmentLine::create([
                    'shipment_id' => $shipment->id,
                    'item_id' => $item->id,
                    'qty_scanned' => 1,
                    // 'remarks'   => null, // kalau ada kolom ini dan mau diisi
                ]);
            }

            // Hitung total
            $totalQty = (int) ShipmentLine::where('shipment_id', $shipment->id)->sum('qty_scanned');
            $totalLines = (int) ShipmentLine::where('shipment_id', $shipment->id)->count();

            // Simpan last scanned ke session (dipakai saat full reload)
            session()->put('last_scanned_line_id', $line->id);

            return [
                'line' => $line,
                'total_qty' => $totalQty,
                'total_lines' => $totalLines,
            ];
        });

        $line = $result['line'];
        $totalQty = $result['total_qty'];
        $totalLines = $result['total_lines'];

        // Untuk request JSON (AJAX) → balas JSON
        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'ok',
                'message' => 'Berhasil scan ' . $item->code . ' (+1)',
                'last_scanned_line_id' => $line->id,
                'line' => [
                    'id' => $line->id,
                    'item_code' => $item->code,
                    'item_name' => $item->name,
                    'remarks' => $line->remarks ?? null,
                    'qty_scanned' => (int) $line->qty_scanned,
                ],
                'totals' => [
                    'total_qty' => $totalQty,
                    'total_lines' => $totalLines,
                ],
            ]);
        }

        // Fallback: non-JSON → redirect back
        return redirect()
            ->back()
            ->with('status', 'success')
            ->with('message', 'Berhasil scan ' . $item->code)
            ->with('last_scanned_line_id', $line->id);
    }

    /**
     * Helper error response:
     * - Kalau JSON → balas JSON
     * - Kalau non-JSON → redirect back dengan flash error
     */
    protected function errorResponse(Request $request, string $message, int $statusCode = 400)
    {
        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'error',
                'message' => $message,
            ], $statusCode);
        }

        return redirect()
            ->back()
            ->with('status', 'error')
            ->with('message', $message);
    }
}
