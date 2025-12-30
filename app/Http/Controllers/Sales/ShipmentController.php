<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\SalesInvoice;
use App\Models\Shipment;
use App\Models\ShipmentLine;
use App\Models\Store;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ShipmentController extends Controller
{
    public function __construct(
        protected InventoryService $inventory
    ) {}

    public function index(Request $request)
    {
        $shipments = Shipment::with('store')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20);

        return view('sales.shipments.index', compact('shipments'));
    }

    public function create(Request $request)
    {
        $stores = Store::orderBy('code')->get();
        $whRts = Warehouse::where('code', 'WH-RTS')->first();

        $invoice = null;

        // Terima dari query ?sales_invoice_id=... (misal dari "create shipment from invoice")
        if ($request->filled('sales_invoice_id')) {
            $invoice = SalesInvoice::with('store')
                ->find($request->sales_invoice_id);
        }
        // Backward compatibility: masih bisa pakai ?invoice_id=...
        elseif ($request->filled('invoice_id')) {
            $invoice = SalesInvoice::with('store')
                ->find($request->invoice_id);
        }

        return view('sales.shipments.create', [
            'stores' => $stores,
            'whRts' => $whRts,
            'invoice' => $invoice,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'store_id' => ['required', 'exists:stores,id'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'sales_invoice_id' => ['nullable', 'exists:sales_invoices,id'],
        ]);

        // 🔥 Ambil store untuk tentukan prefix kode
        $store = Store::findOrFail($data['store_id']);

        $storeName = strtoupper(trim($store->name ?? ''));
        $storeCode = strtoupper(trim($store->code ?? ''));
        $storeKey = $storeCode . ' ' . $storeName;

        // Default prefix (kalau tidak ada rule khusus)
        $prefix = 'SHP';

        // Kalau ada kode store, pakai 3 huruf pertama
        if ($storeCode !== '') {
            $cleanCode = preg_replace('/[^A-Z0-9]/', '', $storeCode);
            if ($cleanCode !== '') {
                $prefix = substr($cleanCode, 0, 3);
            }
        }

        // Override khusus Shopee / TikTok
        if (str_contains($storeKey, 'SHP') || str_contains($storeKey, 'SHOPEE')) {
            $prefix = 'SHP';
        } elseif (str_contains($storeKey, 'TTK') || str_contains($storeKey, 'TIKTOK')) {
            $prefix = 'TTK';
        }

        // Generate kode dengan prefix sesuai channel
        $code = Shipment::generateCode($prefix);

        $shipment = Shipment::create([
            'code' => $code,
            'store_id' => $data['store_id'],
            'sales_invoice_id' => $data['sales_invoice_id'] ?? null,
            'date' => $data['date'],
            'status' => 'draft',
            'notes' => $data['notes'] ?? null,
            'created_by' => Auth::id(),
        ]);

        // Setelah dibuat → langsung ke halaman EDIT (scan)
        return redirect()
            ->route('sales.shipments.edit', $shipment)
            ->with('status', 'success')
            ->with('message', 'Shipment dibuat. Silakan scan barang.');
    }

    /**
     * DETAIL READ-ONLY
     * - Untuk semua status: draft / submitted / posted
     * - Tanpa form scan & import.
     */
    public function show(Shipment $shipment)
    {
        $shipment->load(['store', 'lines.item.category', 'creator', 'invoice']);

        // Hitung HPP per line (unit_hpp & total_hpp) – sesuaikan sumber HPP di sini
        $shipment->lines->each(function ($line) {
            // Contoh fallback: pakai atribut di item jika ada
            $unitHpp = 0;

            if (isset($line->item)) {
                // GANTI bagian ini sesuai struktur tabel kamu
                $unitHpp = $line->item->latest_hpp ?? $line->item->hpp ?? $line->item->last_purchase_price ?? 0;
            }

            $line->unit_hpp = $unitHpp;
            $line->total_hpp = $unitHpp * (int) $line->qty_scanned;
        });

        $totalQty = $shipment->lines->sum('qty_scanned');
        $totalLines = $shipment->lines->count();
        $totalHpp = $shipment->lines->sum('total_hpp');

        // Ringkasan per kategori
        $summaryPerCategory = $shipment->lines
            ->groupBy(function ($line) {
                return optional(optional($line->item)->category)->name ?: 'Tanpa Kategori';
            })
            ->map(function ($group, $categoryName) {
                return [
                    'category_name' => $categoryName,
                    'total_lines' => $group->count(),
                    'total_qty' => $group->sum('qty_scanned'),
                    'total_hpp' => $group->sum('total_hpp'),
                ];
            })
            ->values()
            ->sortBy('category_name');

        return view('sales.shipments.show', [
            'shipment' => $shipment,
            'totalQty' => $totalQty,
            'totalLines' => $totalLines,
            'totalHpp' => $totalHpp,
            'summaryPerCategory' => $summaryPerCategory,
        ]);
    }

    /**
     * HALAMAN EDIT / SCAN
     * - Hanya boleh diakses kalau status = draft
     * - Menggunakan layout scan + import (edit.blade.php).
     */
    public function edit(Shipment $shipment)
    {
        if ($shipment->status !== 'draft') {
            return redirect()
                ->route('sales.shipments.show', $shipment)
                ->with('status', 'error')
                ->with('message', 'Shipment bukan draft, tidak bisa di-edit / discan lagi.');
        }

        $shipment->load(['store', 'lines.item', 'creator', 'invoice']);

        // Import preview (jika dipanggil dari importPreview)
        $importPreview = session('shipment_import_preview.' . $shipment->id . '.rows') ?? null;
        $importPreviewSummary = session('shipment_import_preview.' . $shipment->id . '.summary') ?? null;

        return view('sales.shipments.edit', [
            'shipment' => $shipment,
            'importPreview' => $importPreview,
            'importPreviewSummary' => $importPreviewSummary,
        ]);
    }

    /**
     * Scan item → tambah / update line.
     * - Hanya untuk draft
     */
    public function scanItem(Request $request, Shipment $shipment)
    {
        if ($shipment->status !== 'draft') {
            $message = 'Shipment sudah tidak bisa discan (bukan draft).';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $message,
                ], 409);
            }

            return redirect()
                ->route('sales.shipments.show', $shipment)
                ->with('status', 'error')
                ->with('message', $message);
        }

        $data = $request->validate([
            'scan_code' => ['required', 'string', 'max:255'],
            'qty' => ['nullable', 'integer', 'min:1'],
        ]);

        // Paksa uppercase
        $scanCode = mb_strtoupper(trim($data['scan_code']));
        $qty = (int) ($data['qty'] ?? 1);
        if ($qty <= 0) {
            $qty = 1;
        }

        $item = Item::query()
            ->where('type', 'finished_good')
            ->where(function ($q) use ($scanCode) {
                $q->where('barcode', $scanCode)
                    ->orWhere('code', $scanCode);
            })
            ->first();

        if (!$item) {
            $message = "Item dengan kode/barcode {$scanCode} tidak ditemukan atau bukan finished_good.";

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $message,
                ], 422);
            }

            return redirect()
                ->route('sales.shipments.edit', $shipment)
                ->with('status', 'error')
                ->with('message', $message)
                ->withInput();
        }

        $result = DB::transaction(function () use ($shipment, $item, $qty) {
            /** @var \App\Models\ShipmentLine|null $line */
            $line = ShipmentLine::query()
                ->where('shipment_id', $shipment->id)
                ->where('item_id', $item->id)
                ->lockForUpdate()
                ->first();

            if ($line) {
                $line->qty_scanned = (int) $line->qty_scanned + $qty;
                $line->save();
            } else {
                $line = ShipmentLine::create([
                    'shipment_id' => $shipment->id,
                    'item_id' => $item->id,
                    'qty_scanned' => $qty,
                ]);
            }

            $totalQty = (int) ShipmentLine::where('shipment_id', $shipment->id)->sum('qty_scanned');
            $totalLines = (int) ShipmentLine::where('shipment_id', $shipment->id)->count();

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

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'ok',
                'message' => 'Berhasil scan ' . $item->code . ' (+' . $qty . ')',
                'last_scanned_line_id' => $line->id,
                'line' => [
                    'id' => $line->id,
                    'item_code' => $item->code,
                    'item_name' => $item->name,
                    'remarks' => $line->remarks ?? null,
                    'qty_scanned' => (int) $line->qty_scanned,
                    'update_qty_url' => route('sales.shipments.update_line_qty', $line),
                ],
                'totals' => [
                    'total_qty' => $totalQty,
                    'total_lines' => $totalLines,
                ],
            ]);
        }

        return redirect()
            ->route('sales.shipments.edit', $shipment)
            ->with('last_scanned_line_id', $line->id);
    }

    /**
     * Submit shipment (lock scan, belum stock out).
     */
    public function submit(Request $request, Shipment $shipment)
    {
        if ($shipment->status !== 'draft') {
            return redirect()
                ->route('sales.shipments.show', $shipment)
                ->with('status', 'error')
                ->with('message', 'Hanya shipment draft yang bisa di-submit.');
        }

        if ($shipment->lines()->count() === 0) {
            return redirect()
                ->route('sales.shipments.edit', $shipment)
                ->with('status', 'error')
                ->with('message', 'Tidak ada item di shipment ini.');
        }

        $shipment->status = 'submitted';
        $shipment->submitted_at = now();
        $shipment->submitted_by = auth()->id();
        $shipment->save();

        return redirect()
            ->route('sales.shipments.show', $shipment)
            ->with('status', 'success')
            ->with('message', 'Shipment disubmit. Tidak bisa discan lagi, siap untuk posting stok.');
    }

    /**
     * Posting shipment → stock out dari WH-RTS.
     */
    public function post(Request $request, Shipment $shipment)
    {
        if ($shipment->status === 'posted') {
            return redirect()
                ->route('sales.shipments.show', $shipment)
                ->with('status', 'error')
                ->with('message', 'Shipment sudah diposting sebelumnya.');
        }

        if ($shipment->status !== 'submitted') {
            return redirect()
                ->route('sales.shipments.show', $shipment)
                ->with('status', 'error')
                ->with('message', 'Shipment harus berstatus submitted sebelum diposting.');
        }

        $shipment->load(['lines.item', 'store']);

        if ($shipment->lines->isEmpty()) {
            return redirect()
                ->route('sales.shipments.show', $shipment)
                ->with('status', 'error')
                ->with('message', 'Tidak ada item di shipment ini.');
        }

        $warehouse = Warehouse::where('code', 'WH-RTS')->first();

        if (!$warehouse) {
            return redirect()
                ->route('sales.shipments.show', $shipment)
                ->with('status', 'error')
                ->with('message', 'Warehouse WH-RTS belum dikonfigurasi.');
        }

        DB::transaction(function () use ($shipment, $warehouse) {
            $totalQty = 0;

            foreach ($shipment->lines as $line) {
                $qty = (int) $line->qty_scanned;
                if ($qty <= 0) {
                    continue;
                }

                $totalQty += $qty;

                // Kurangi stok FG dari WH-RTS
                $this->inventory->stockOut(
                    warehouseId: $warehouse->id,
                    itemId: $line->item_id,
                    qty: $qty,
                    date: $shipment->date,
                    sourceType: 'shipment',
                    sourceId: $shipment->id,
                    notes: 'Shipment ' . $shipment->code . ' ke store ' . ($shipment->store->code ?? '-'),
                    allowNegative: true, // stok FG boleh minus / sesuai kebijakan
                    lotId: null, // FG tidak pakai LOT
                    unitCostOverride: null,
                    affectLotCost: false,
                );
            }

            $shipment->status = 'posted';
            $shipment->total_qty = $totalQty;
            $shipment->save();
        });

        return redirect()
            ->route('sales.shipments.show', $shipment)
            ->with('status', 'success')
            ->with('message', 'Shipment berhasil diposting & stok berkurang dari WH-RTS.');
    }

    public function exportLines(Shipment $shipment)
    {
        $shipment->load(['store', 'lines.item']);

        if ($shipment->lines->isEmpty()) {
            return redirect()
                ->route('sales.shipments.show', $shipment)
                ->with('status', 'error')
                ->with('message', 'Tidak ada item di shipment ini untuk diekspor.');
        }

        $fileName = 'shipment_' . $shipment->code . '_items_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        $callback = function () use ($shipment) {
            $handle = fopen('php://output', 'w');

            // Supaya Excel Windows baca UTF-8 dengan benar
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header kolom
            fputcsv($handle, [
                'Shipment Code',
                'Tanggal',
                'Store Name',
                'Store Code',
                'Item Code',
                'Item Name',
                'Qty Scanned',
                'Catatan Line',
            ], ';');

            foreach ($shipment->lines as $line) {
                $item = $line->item;
                $store = $shipment->store;

                fputcsv($handle, [
                    $shipment->code,
                    optional($shipment->date)->format('Y-m-d'),
                    $store?->name ?? '',
                    $store?->code ?? '',
                    $item?->code ?? '',
                    $item?->name ?? '',
                    (int) $line->qty_scanned,
                    $line->remarks ?? '',
                ], ';');
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $fileName, $headers);
    }

    /**
     * Bersihkan semua baris (ShipmentLine) dalam 1 shipment draft.
     */
    public function clearLines(Request $request, Shipment $shipment)
    {
        if ($shipment->status !== 'draft') {
            $message = 'Shipment sudah tidak draft, baris tidak bisa dibersihkan.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $message,
                ], 409);
            }

            return redirect()
                ->route('sales.shipments.show', $shipment)
                ->with('status', 'error')
                ->with('message', $message);
        }

        DB::transaction(function () use ($shipment) {
            ShipmentLine::where('shipment_id', $shipment->id)->delete();
        });

        // Bersihkan juga state bantuan di session
        session()->forget('last_scanned_line_id');
        session()->forget('shipment_import_preview.' . $shipment->id);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'ok',
                'message' => 'Semua baris berhasil dibersihkan.',
                'totals' => [
                    'total_qty' => 0,
                    'total_lines' => 0,
                ],
            ]);
        }

        return redirect()
            ->route('sales.shipments.edit', $shipment)
            ->with('status', 'success')
            ->with('message', 'Semua baris shipment berhasil dibersihkan.');
    }

    public function destroyLine(Request $request, ShipmentLine $line)
    {
        $shipment = $line->shipment;

        if (!$shipment || $shipment->status !== 'draft') {
            $message = 'Shipment sudah tidak draft, baris tidak bisa dihapus.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $message,
                ], 409);
            }

            return redirect()
                ->route('sales.shipments.show', $shipment?->id ?? null)
                ->with('status', 'error')
                ->with('message', $message);
        }

        DB::transaction(function () use ($line) {
            $line->delete();
        });

        $totalQty = (int) ShipmentLine::where('shipment_id', $shipment->id)->sum('qty_scanned');
        $totalLines = (int) ShipmentLine::where('shipment_id', $shipment->id)->count();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'ok',
                'message' => 'Baris berhasil dihapus.',
                'totals' => [
                    'total_qty' => $totalQty,
                    'total_lines' => $totalLines,
                ],
            ]);
        }

        return redirect()
            ->route('sales.shipments.edit', $shipment)
            ->with('status', 'success')
            ->with('message', 'Baris berhasil dihapus.');
    }

    /**
     * Inline update qty (support AJAX).
     */
    public function updateLineQty(Request $request, ShipmentLine $line)
    {
        $shipment = $line->shipment;

        if (!$shipment || $shipment->status !== 'draft') {
            $message = 'Shipment sudah tidak draft, qty tidak bisa diubah.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $message,
                ], 409);
            }

            return redirect()
                ->route('sales.shipments.show', $shipment?->id ?? null)
                ->with('status', 'error')
                ->with('message', $message);
        }

        $data = $request->validate([
            'qty' => ['required', 'integer', 'min:0'],
        ]);

        $qty = (int) $data['qty'];

        DB::transaction(function () use (&$line, $qty) {
            if ($qty === 0) {
                $line->delete();
            } else {
                $line->qty_scanned = $qty;
                $line->save();
            }
        });

        $totalQty = (int) ShipmentLine::where('shipment_id', $shipment->id)->sum('qty_scanned');
        $totalLines = (int) ShipmentLine::where('shipment_id', $shipment->id)->count();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'ok',
                'message' => 'Qty berhasil diperbarui.',
                'deleted' => $qty === 0,
                'qty' => $qty,
                'totals' => [
                    'total_qty' => $totalQty,
                    'total_lines' => $totalLines,
                ],
            ]);
        }

        return redirect()
            ->route('sales.shipments.edit', $shipment)
            ->with('status', 'success')
            ->with('message', 'Qty berhasil diperbarui.');
    }

    /**
     * Opsional: placeholder syncScans.
     */
    public function syncScans(Request $request, Shipment $shipment)
    {
        return back()
            ->with('status', 'error')
            ->with('message', 'Fitur sync scans belum diimplementasi.');
    }

    /**
     * Helper: parse quantity dengan format lokal (contoh: "2,00", "1.000,00").
     */
    protected function parseImportedQty(?string $raw): int
    {
        if ($raw === null) {
            return 0;
        }

        // Trim & hapus non-breaking space
        $value = trim(str_replace("\xc2\xa0", ' ', $raw));
        if ($value === '') {
            return 0;
        }

        // Hapus spasi di dalam angka
        $value = str_replace(' ', '', $value);

        // Hapus titik pemisah ribuan
        $value = str_replace('.', '', $value);

        // Ganti koma jadi titik (desimal)
        $value = str_replace(',', '.', $value);

        if (!is_numeric($value)) {
            return 0;
        }

        $float = (float) $value;
        $qty = (int) round($float);

        return max(0, $qty);
    }

    /**
     * Konfirmasi import: tambah / update ShipmentLine dari hasil preview.
     */
    public function importLines(Request $request, Shipment $shipment)
    {
        if ($shipment->status !== 'draft') {
            return redirect()
                ->route('sales.shipments.show', $shipment)
                ->with('status', 'error')
                ->with('message', 'Hanya shipment draft yang bisa di-import.');
        }

        $data = $request->validate([
            'rows' => ['required', 'array'],
            'rows.*.product_code' => ['required', 'string', 'max:255'],
            'rows.*.qty' => ['required', 'integer', 'min:1'],
        ]);

        $rows = $data['rows'];
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        DB::transaction(function () use ($rows, $shipment, &$created, &$updated, &$skipped, &$errors) {
            foreach ($rows as $idx => $row) {
                $productCode = trim($row['product_code'] ?? '');
                $qty = (int) ($row['qty'] ?? 0);

                if ($productCode === '' || $qty <= 0) {
                    $skipped++;
                    $errors[] = "Baris " . ($idx + 1) . " tidak valid.";
                    continue;
                }

                $item = Item::query()
                    ->where('type', 'finished_good')
                    ->where(function ($q) use ($productCode) {
                        $q->where('code', $productCode)
                            ->orWhere('barcode', $productCode);
                    })
                    ->first();

                if (!$item) {
                    $skipped++;
                    $errors[] = "Baris " . ($idx + 1) . " item '{$productCode}' tidak ditemukan.";
                    continue;
                }

                /** @var \App\Models\ShipmentLine|null $line */
                $line = ShipmentLine::query()
                    ->where('shipment_id', $shipment->id)
                    ->where('item_id', $item->id)
                    ->lockForUpdate()
                    ->first();

                if ($line) {
                    // Tambah qty (bukan replace) → selaras dengan scan
                    $line->qty_scanned = (int) $line->qty_scanned + $qty;
                    $line->save();
                    $updated++;
                } else {
                    ShipmentLine::create([
                        'shipment_id' => $shipment->id,
                        'item_id' => $item->id,
                        'qty_scanned' => $qty,
                    ]);
                    $created++;
                }
            }
        });

        $message = "Import selesai. Baris baru: {$created}, diupdate: {$updated}, dilewati: {$skipped}.";
        if (!empty($errors)) {
            $message .= ' Beberapa catatan: ' . implode(' ', array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $message .= ' (dan ' . (count($errors) - 5) . ' error lainnya)';
            }
        }

        // Setelah import tetap di halaman edit (scan)
        return redirect()
            ->route('sales.shipments.edit', $shipment)
            ->with('status', 'success')
            ->with('message', $message);
    }

    /**
     * Preview import: simpan di session, lalu render di halaman edit.
     */
    public function importPreview(Request $request, Shipment $shipment)
    {
        if ($shipment->status !== 'draft') {
            return redirect()
                ->route('sales.shipments.show', $shipment)
                ->with('status', 'error')
                ->with('message', 'Hanya shipment draft yang bisa di-import.');
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls'],
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());

        $rows = [];

        if (in_array($ext, ['xlsx', 'xls'])) {
            // 📄 Baca dari Excel (XLSX / XLS)
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();

            foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);

                $cols = [];
                foreach ($cellIterator as $cell) {
                    $value = $cell->getValue();
                    $cols[] = is_null($value) ? '' : trim((string) $value);
                }

                if (count(array_filter($cols, fn($v) => $v !== '')) === 0) {
                    continue;
                }

                $rows[] = $cols;
            }
        } else {
            // 📄 Baca dari CSV / TXT
            $content = file_get_contents($file->getRealPath());

            if (trim($content) === '') {
                return redirect()
                    ->route('sales.shipments.edit', $shipment)
                    ->with('status', 'error')
                    ->with('message', 'File kosong, tidak ada data untuk dipreview.');
            }

            $lines = preg_split("/\r\n|\n|\r/", trim($content));

            foreach ($lines as $line) {
                $row = trim($line);
                if ($row === '') {
                    continue;
                }

                // Pisah kolom: support TAB, titik koma, koma, atau pipe
                $cols = preg_split('/\s*[\t;,\|]\s*/', $row);
                $rows[] = $cols;
            }
        }

        if (empty($rows)) {
            return redirect()
                ->route('sales.shipments.edit', $shipment)
                ->with('status', 'error')
                ->with('message', 'File kosong, tidak ada data untuk dipreview.');
        }

        $previewRows = [];
        $okCount = 0;
        $skipCount = 0;
        $totalQtyOk = 0;

        foreach ($rows as $index => $cols) {
            // Minimal 2 kolom: Product, Qty
            if (count($cols) < 2) {
                $previewRows[] = [
                    'row_number' => $index + 1,
                    'raw_product' => isset($cols[0]) ? trim($cols[0]) : '',
                    'raw_qty' => isset($cols[1]) ? $cols[1] : '',
                    'parsed_qty' => 0,
                    'item_code' => null,
                    'item_name' => null,
                    'status' => 'skip',
                    'error' => 'Kolom kurang (butuh Product & Quantity).',
                ];
                $skipCount++;
                continue;
            }

            $productCode = trim($cols[0] ?? '');
            $qtyRaw = $cols[1] ?? '';

            // Skip header
            if (strtolower($productCode) === 'product' || strtolower($productCode) === 'kode') {
                continue;
            }

            if ($productCode === '') {
                $previewRows[] = [
                    'row_number' => $index + 1,
                    'raw_product' => $productCode,
                    'raw_qty' => $qtyRaw,
                    'parsed_qty' => 0,
                    'item_code' => null,
                    'item_name' => null,
                    'status' => 'skip',
                    'error' => 'Kode product kosong.',
                ];
                $skipCount++;
                continue;
            }

            // ✅ parse qty → integer
            $parsedQtyRaw = $this->parseImportedQty($qtyRaw);
            $parsedQty = (int) round($parsedQtyRaw);

            if ($parsedQty <= 0) {
                $previewRows[] = [
                    'row_number' => $index + 1,
                    'raw_product' => $productCode,
                    'raw_qty' => $qtyRaw,
                    'parsed_qty' => 0,
                    'item_code' => null,
                    'item_name' => null,
                    'status' => 'skip',
                    'error' => 'Qty tidak valid / <= 0.',
                ];
                $skipCount++;
                continue;
            }

            // Cari item finished_good
            $item = Item::query()
                ->where('type', 'finished_good')
                ->where(function ($q) use ($productCode) {
                    $q->where('code', $productCode)
                        ->orWhere('barcode', $productCode);
                })
                ->first();

            if (!$item) {
                $previewRows[] = [
                    'row_number' => $index + 1,
                    'raw_product' => $productCode,
                    'raw_qty' => $qtyRaw,
                    'parsed_qty' => $parsedQty,
                    'item_code' => null,
                    'item_name' => null,
                    'status' => 'skip',
                    'error' => "Item '{$productCode}' tidak ditemukan / bukan finished_good.",
                ];
                $skipCount++;
                continue;
            }

            // OK
            $previewRows[] = [
                'row_number' => $index + 1,
                'raw_product' => $productCode,
                'raw_qty' => $qtyRaw,
                'parsed_qty' => $parsedQty,
                'item_code' => $item->code,
                'item_name' => $item->name,
                'status' => 'ok',
                'error' => null,
            ];
            $okCount++;
            $totalQtyOk += $parsedQty;
        }

        // Simpan preview di session supaya bisa diambil di edit()
        $previewSummary = [
            'ok_count' => $okCount,
            'skip_count' => $skipCount,
            'total_qty_ok' => $totalQtyOk,
        ];

        session([
            'shipment_import_preview.' . $shipment->id . '.rows' => $previewRows,
            'shipment_import_preview.' . $shipment->id . '.summary' => $previewSummary,
        ]);

        return redirect()
            ->route('sales.shipments.edit', [
                'shipment' => $shipment->id,
                'show_preview' => 1, // <-- ini yang dibaca di Blade
            ]);
    }

    public function report(Request $request)
    {
        // Default: bulan berjalan
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if (!$dateFrom || !$dateTo) {
            $dateFrom = now()->startOfMonth()->toDateString();
            $dateTo = now()->toDateString();
        }

        $storeId = $request->input('store_id');
        $status = $request->input('status');

        $stores = Store::orderBy('code')->get();

        $statusOptions = [
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'posted' => 'Posted',
        ];

        $shipments = Shipment::query()
            ->with(['store', 'lines.item'])
        // ⚠️ pake whereDate supaya kalau kolom "date" ternyata DATETIME,
        // shipment hari terakhir tetap ikut.
            ->when($dateFrom && $dateTo, function ($q) use ($dateFrom, $dateTo) {
                $q->whereDate('date', '>=', $dateFrom)
                    ->whereDate('date', '<=', $dateTo);
            })
            ->when($storeId, function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            ->when($status, function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $rows = $shipments->map(function (Shipment $shipment) {
            $totalQty = 0;
            $totalHpp = 0;
            $totalLines = 0;

            foreach ($shipment->lines as $line) {
                $qty = (int) $line->qty_scanned;
                if ($qty <= 0) {
                    continue;
                }

                $totalLines++;

                $unitHpp = 0;
                if ($line->item) {
                    $unitHpp = $line->item->latest_hpp ?? $line->item->hpp ?? $line->item->last_purchase_price ?? 0;
                }

                $totalQty += $qty;
                $totalHpp += $unitHpp * $qty;
            }

            return (object) [
                'shipment' => $shipment,
                'total_qty' => $totalQty,
                'total_lines' => $totalLines,
                'total_hpp' => $totalHpp,
            ];
        });

        $summary = [
            'total_shipments' => $rows->count(),
            'total_qty' => $rows->sum('total_qty'),
            'total_hpp' => $rows->sum('total_hpp'),
        ];

        return view('sales.shipments.report', [
            'rows' => $rows,
            'summary' => $summary,
            'stores' => $stores,
            'statusOptions' => $statusOptions,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'store_id' => $storeId,
                'status' => $status,
            ],
        ]);
    }

}
