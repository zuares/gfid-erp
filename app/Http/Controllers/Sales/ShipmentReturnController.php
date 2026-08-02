<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Shipment;
use App\Models\ShipmentReturn;
use App\Models\ShipmentReturnLine;
use App\Models\ShipmentReturnOrderScan;
use App\Models\ShipmentReturnOrderScanItem;
use App\Models\Store;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShipmentReturnController extends Controller
{
    public function __construct(
        protected InventoryService $inventory
    ) {}

    protected function devOrderCommandAllowed(): bool
    {
        $user = Auth::user();
        $databaseName = strtolower((string) config('database.connections.' . config('database.default') . '.database'));
        $appUrl = strtolower((string) config('app.url'));

        return ($user?->role === 'owner')
            && (
                app()->environment(['local', 'development', 'testing'])
                || (bool) config('app.debug')
                || str_contains($appUrl, 'dev')
                || str_contains($databaseName, 'dev')
            );
    }

    /**
     * List retur pengiriman.
     */
    public function index(Request $request)
    {
        $returns = ShipmentReturn::with(['store', 'shipment'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20);

        return view('sales.shipment_returns.index', compact('returns'));
    }

    /**
     * Form buat retur pengiriman.
     * Bisa optional dari shipment asal (?shipment_id=...).
     */
    public function create(Request $request)
    {
        $stores = Store::query()->orderBy('return_channel')->orderBy('code')->get();
        $shipment = $request->filled('shipment_id')
            ? Shipment::with('store')->find($request->integer('shipment_id'))
            : null;

        return view('sales.shipment_returns.create', compact('stores', 'shipment'));
    }

    public function edit(ShipmentReturn $shipmentReturn)
    {
        $shipmentReturn->load([
            'store',
            'shipment',
            'lines.item',
            'creator',
            'orderScans.items.item',
        ]);

        return view('sales.shipment_returns.edit', compact('shipmentReturn'));
    }

    public function editItemFirst(ShipmentReturn $shipmentReturn)
    {
        $shipmentReturn->load([
            'store',
            'shipment',
            'lines.item',
            'orderScans.items.item',
        ]);

        return view('sales.shipment_returns.edit_item_first', compact('shipmentReturn'));
    }

    public function scanLookup(Request $request, ShipmentReturn $shipmentReturn)
    {
        $code = mb_strtoupper(trim((string) $request->query('code', '')));

        if ($code === '') {
            return response()->json([
                'type' => 'empty',
            ]);
        }

        // Lookup order sengaja tidak mengakses marketplace/shipment. Semua kode
        // yang discan diterima sebagai nomor order dan dicatat apa adanya.
        return response()->json([
            'type' => 'order',
            'order' => [
                'code' => $code,
            ],
        ]);
    }

    /**
     * Catat nomor order hasil scan tanpa mencoba resolve ke marketplace/shipment.
     */
    public function scanOrder(Request $request, ShipmentReturn $shipmentReturn)
    {
        if ($shipmentReturn->status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Pencatatan sudah tidak berstatus draft.',
            ], 409);
        }

        $data = $request->validate([
            'order_number' => ['required', 'string', 'max:100'],
        ]);

        $orderNumber = mb_strtoupper(trim($data['order_number']));
        if ($orderNumber === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'Nomor order belum diisi.',
            ], 422);
        }

        $scan = DB::transaction(function () use ($shipmentReturn, $orderNumber) {
            ShipmentReturn::query()
                ->lockForUpdate()
                ->findOrFail($shipmentReturn->id);

            $scan = ShipmentReturnOrderScan::query()
                ->where('shipment_return_id', $shipmentReturn->id)
                ->where('order_number', $orderNumber)
                ->lockForUpdate()
                ->first();

            if (!$scan) {
                $scan = ShipmentReturnOrderScan::create([
                    'shipment_return_id' => $shipmentReturn->id,
                    'order_number' => $orderNumber,
                    'scanned_at' => now(),
                    'match_status' => 'pending',
                    'source' => 'scanner',
                    'raw_payload' => [
                        'mode' => 'record_only',
                        'label' => 'Pencatatan order',
                    ],
                ]);
            } else {
                $scan->scanned_at = now();
                $scan->save();
            }

            return $scan;
        });

        return response()->json([
            'status' => 'ok',
            'created' => $scan->wasRecentlyCreated,
            'message' => $scan->wasRecentlyCreated
                ? "Order {$orderNumber} dicatat."
                : "Order {$orderNumber} sudah tercatat.",
            'order' => [
                'code' => $orderNumber,
                'label' => 'Pencatatan order',
                'scanned_at' => optional($scan->scanned_at)->toISOString(),
            ],
        ]);
    }

    public function bulkOrders(Request $request, ShipmentReturn $shipmentReturn)
    {
        abort_unless($this->devOrderCommandAllowed(), 403);

        if ($shipmentReturn->status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Retur sudah tidak draft.',
            ], 409);
        }

        $data = $request->validate([
            'orders' => ['nullable', 'string', 'max:10000'],
            'order_numbers' => ['nullable', 'array'],
            'order_numbers.*' => ['nullable', 'string', 'max:100'],
        ]);

        $codes = collect($data['order_numbers'] ?? [])
            ->merge(preg_split('/[\s,;]+/', (string) ($data['orders'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [])
            ->map(fn ($code) => mb_strtoupper(trim((string) $code)))
            ->filter()
            ->unique()
            ->take(100)
            ->values();

        if ($codes->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nomor order belum diisi.',
            ], 422);
        }

        $orders = DB::transaction(function () use ($shipmentReturn, $codes) {
            $shipmentReturn = ShipmentReturn::query()
                ->lockForUpdate()
                ->findOrFail($shipmentReturn->id);

            return $codes->map(function (string $code) use ($shipmentReturn) {
                $scan = ShipmentReturnOrderScan::query()
                    ->where('shipment_return_id', $shipmentReturn->id)
                    ->where('order_number', $code)
                    ->lockForUpdate()
                    ->first();

                if (!$scan) {
                    $scan = ShipmentReturnOrderScan::create([
                        'shipment_return_id' => $shipmentReturn->id,
                        'order_number' => $code,
                        'scanned_at' => now(),
                        'match_status' => 'pending',
                        'source' => 'dev_command',
                        'raw_payload' => [
                            'mode' => 'record_only',
                            'label' => 'Pencatatan order',
                        ],
                    ]);
                } else {
                    $scan->scanned_at = now();
                    $scan->save();
                }

                return [
                    'code' => $code,
                    'label' => 'Pencatatan order',
                    'scanned_at' => optional($scan->scanned_at)->toISOString(),
                    'items' => [],
                ];
            })->values();
        });

        return response()->json([
            'status' => 'ok',
            'orders' => $orders,
            'message' => $orders->count() . ' order ditambahkan.',
        ]);
    }

    public function clearOrders(Request $request, ShipmentReturn $shipmentReturn)
    {
        abort_unless($this->devOrderCommandAllowed(), 403);

        if ($shipmentReturn->status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Retur sudah tidak draft.',
            ], 409);
        }

        $deleted = DB::transaction(function () use ($shipmentReturn) {
            $shipmentReturn = ShipmentReturn::query()
                ->lockForUpdate()
                ->findOrFail($shipmentReturn->id);

            $orderScans = ShipmentReturnOrderScan::query()
                ->with('items')
                ->where('shipment_return_id', $shipmentReturn->id)
                ->lockForUpdate()
                ->get();

            $orderNumbers = $orderScans
                ->map(fn ($scan) => mb_strtoupper(trim((string) $scan->order_number)))
                ->filter()
                ->unique()
                ->values();

            $lineIds = $orderScans
                ->flatMap(fn ($scan) => $scan->items->pluck('shipment_return_line_id'))
                ->filter()
                ->unique()
                ->values();

            ShipmentReturnOrderScanItem::query()
                ->whereIn('shipment_return_order_scan_id', $orderScans->pluck('id'))
                ->delete();

            ShipmentReturnOrderScan::query()
                ->whereIn('id', $orderScans->pluck('id'))
                ->delete();

            if ($lineIds->isNotEmpty()) {
                ShipmentReturnLine::query()
                    ->where('shipment_return_id', $shipmentReturn->id)
                    ->whereIn('id', $lineIds)
                    ->delete();
            }

            if ($orderNumbers->isNotEmpty()) {
                ShipmentReturnLine::query()
                    ->where('shipment_return_id', $shipmentReturn->id)
                    ->whereIn(DB::raw('UPPER(remarks)'), $orderNumbers->all())
                    ->delete();
            }

            return $orderScans->count();
        });

        return response()->json([
            'status' => 'ok',
            'message' => $deleted . ' order dibersihkan.',
        ]);
    }

    /**
     * Simpan header retur pengiriman.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'store_id'      => ['nullable', 'exists:stores,id'],
            'shipment_id'   => ['nullable', 'exists:shipments,id'],
            'order_number'  => ['nullable', 'string', 'max:100'],
            'date'          => ['required', 'date'],
            'reason'        => ['nullable', 'string'],
            'notes'         => ['nullable', 'string'],
            'scan_mode'     => ['nullable', 'in:item_first,order_first'],
            'order_numbers' => ['nullable', 'array'],
            'order_numbers.*' => ['nullable', 'string', 'max:100'],
            'lines'         => ['nullable', 'array'],
            'lines.*.item_id' => ['required_with:lines', 'exists:items,id'],
            'lines.*.qty'     => ['required_with:lines', 'numeric', 'min:1'],
            'lines.*.remarks' => ['nullable', 'string', 'max:255'],
        ]);

        $orderNumbers = collect($data['order_numbers'] ?? [])
            ->map(fn ($code) => strtoupper(trim((string) $code)))
            ->filter()
            ->unique()
            ->values();

        // Lookup shipment by order_number (code) jika shipment_id tidak langsung dikirim
        $shipmentId = $data['shipment_id'] ?? null;
        if (!$shipmentId && !empty($data['order_number'])) {
            $found = Shipment::where('code', trim($data['order_number']))->first();
            $shipmentId = $found?->id;
        }
        if (!$shipmentId && $orderNumbers->isNotEmpty()) {
            $found = Shipment::whereIn('code', $orderNumbers)->orderBy('id')->first();
            $shipmentId = $found?->id;
        }

        $notes = trim((string) ($data['notes'] ?? ''));
        if ($orderNumbers->isNotEmpty()) {
            $orderNote = 'Pesanan retur: ' . $orderNumbers->implode(', ');
            $notes = $notes !== '' ? ($notes . "\n" . $orderNote) : $orderNote;
        }

        $prefix = 'RTP';

        $ret = DB::transaction(function () use ($data, $shipmentId, $prefix, $notes) {
            $ret = ShipmentReturn::create([
                'code'        => ShipmentReturn::generateCode($prefix),
                'store_id'    => $data['store_id'],
                'shipment_id' => $shipmentId,
                'date'        => $data['date'],
                'status'      => 'draft',
                'scan_mode'   => $data['scan_mode'] ?? 'item_first',
                'reason'      => $data['reason'] ?? null,
                'notes'       => $notes !== '' ? $notes : null,
                'created_by'  => Auth::id(),
            ]);

            $totalQty = 0;
            foreach (($data['lines'] ?? []) as $line) {
                $qty = (int) ($line['qty'] ?? 0);
                if ($qty <= 0 || empty($line['item_id'])) continue;

                ShipmentReturnLine::create([
                    'shipment_return_id' => $ret->id,
                    'item_id'            => (int) $line['item_id'],
                    'qty'                => $qty,
                    'remarks'            => $line['remarks'] ?? null,
                ]);
                $totalQty += $qty;
            }

            if ($totalQty > 0) {
                $ret->total_qty = $totalQty;
                $ret->save();
            }

            return $ret;
        }, 5);

        $nextRoute = ($data['scan_mode'] ?? 'item_first') === 'item_first'
            ? 'sales.shipment_returns.scan_items'
            : 'sales.shipment_returns.edit';

        return redirect()
            ->route($nextRoute, $ret)
            ->with('status', 'success')
            ->with('message', ($data['scan_mode'] ?? 'item_first') === 'item_first'
                ? 'Draft retur dibuat. Silakan scan semua item terlebih dahulu.'
                : 'Draft retur dibuat. Silakan scan order dan item retur.');
    }

    /**
     * Detail retur pengiriman.
     */
    public function show(ShipmentReturn $shipmentReturn)
    {
        $shipmentReturn->load([
            'store',
            'shipment',
            'lines.item',
            'lines.shipmentLine',
            'orderScans.items.item',
            'creator',
            'submittedBy',
            'postedBy',
            'cancelledBy',
        ]);

        return view('sales.shipment_returns.show', compact('shipmentReturn'));
    }

    /**
     * Halaman cetak barcode retur.
     * Default jumlah label per item = qty retur, tapi bisa disesuaikan sebelum cetak.
     */
    public function barcode(ShipmentReturn $shipmentReturn)
    {
        $shipmentReturn->load(['store', 'lines.item']);

        $lines = $shipmentReturn->lines
            ->filter(fn ($l) => $l->item && $l->item->code)
            ->map(fn ($l) => [
                'id'   => $l->item->id,
                'code' => $l->item->code,
                'name' => $l->item->name,
                'qty'  => max(1, (int) round((float) ($l->qty ?? 1))),
            ])
            ->values();

        return view('sales.shipment_returns.barcode', compact('shipmentReturn', 'lines'));
    }

    /**
     * Scan item yang dikembalikan → tambah / update line retur.
     * Support AJAX.
     */
    public function scanItem(Request $request, ShipmentReturn $shipmentReturn)
    {
        if ($shipmentReturn->status !== 'draft') {
            $message = 'Retur sudah tidak bisa discan (bukan draft).';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $message,
                ], 409);
            }

            return back()
                ->with('status', 'error')
                ->with('message', $message);
        }

        $data = $request->validate([
            'scan_code' => ['required', 'string', 'max:255'],
            'order_number' => ['nullable', 'string', 'max:100'],
            'qty' => ['nullable', 'integer', 'min:1'],
        ]);

        $scanCode = mb_strtoupper(trim($data['scan_code']));
        $orderNumber = mb_strtoupper(trim((string) ($data['order_number'] ?? '')));
        $qty = (int) ($data['qty'] ?? 1);
        if ($qty <= 0) {
            $qty = 1;
        }

        $item = Item::query()
            ->where('type', 'finished_good')
            ->where(function ($q) use ($scanCode) {
                $q->where('barcode', $scanCode)
                    ->orWhere('code', $scanCode)
                    ->orWhereHas('barcodes', function ($barcodeQuery) use ($scanCode) {
                        $barcodeQuery->where('barcode', $scanCode)
                            ->where('is_active', true);
                    });
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

            return back()
                ->with('status', 'error')
                ->with('message', $message)
                ->withInput();
        }

        $result = DB::transaction(function () use ($shipmentReturn, $item, $qty, $orderNumber) {
            $shipmentReturn = ShipmentReturn::query()
                ->lockForUpdate()
                ->findOrFail($shipmentReturn->id);

            $orderScan = null;
            if ($orderNumber !== '') {
                $orderScan = ShipmentReturnOrderScan::query()
                    ->where('shipment_return_id', $shipmentReturn->id)
                    ->where('order_number', $orderNumber)
                    ->lockForUpdate()
                    ->first();

                if (!$orderScan) {
                    $orderScan = ShipmentReturnOrderScan::create([
                        'shipment_return_id' => $shipmentReturn->id,
                        'order_number' => $orderNumber,
                        'scanned_at' => now(),
                        'match_status' => 'pending',
                        'source' => 'scanner',
                        'raw_payload' => [
                            'mode' => 'record_only',
                            'label' => 'Pencatatan order',
                        ],
                    ]);
                }
                $shipmentReturn->save();
            }

            /** @var \App\Models\ShipmentReturnLine|null $line */
            $line = ShipmentReturnLine::query()
                ->where('shipment_return_id', $shipmentReturn->id)
                ->where('item_id', $item->id)
                ->where(function ($q) use ($orderNumber) {
                    if ($orderNumber === '') {
                        $q->whereNull('remarks')->orWhere('remarks', '');
                    } else {
                        $q->where('remarks', $orderNumber);
                    }
                })
                ->lockForUpdate()
                ->first();

            // Link ke shipment_line sengaja ditunda agar pencatatan tidak
            // bergantung pada modul shipment/marketplace.
            $shipmentLineId = null;

            if ($line) {
                $line->qty = (int) $line->qty + $qty;
                $line->scanned_at = now();
                if ($shipmentLineId && !$line->shipment_line_id) {
                    $line->shipment_line_id = $shipmentLineId;
                }
                $line->save();
            } else {
                $line = ShipmentReturnLine::create([
                    'shipment_return_id' => $shipmentReturn->id,
                    'item_id' => $item->id,
                    'shipment_line_id' => $shipmentLineId,
                    'qty' => $qty,
                    'scanned_at' => now(),
                    'remarks' => $orderNumber !== '' ? $orderNumber : null,
                ]);
            }

            if ($orderScan) {
                ShipmentReturnOrderScanItem::query()
                    ->updateOrCreate(
                        [
                            'shipment_return_order_scan_id' => $orderScan->id,
                            'item_id' => $item->id,
                        ],
                        [
                            'shipment_return_line_id' => $line->id,
                            'qty_scanned' => (int) $line->qty,
                            'match_status' => 'pending',
                        ]
                    );
            }

            $totalQty = (int) ShipmentReturnLine::where('shipment_return_id', $shipmentReturn->id)->sum('qty');
            $totalLines = (int) ShipmentReturnLine::where('shipment_return_id', $shipmentReturn->id)->count();

            session()->put('last_scanned_return_line_id', $line->id);

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
                'message' => 'Berhasil scan retur ' . $item->code . ' (+' . $qty . ')',
                'last_scanned_line_id' => $line->id,
                'line' => [
                    'id' => $line->id,
                    'item_id' => $line->item_id,
                    'item_code' => $item->code,
                    'item_name' => $item->name,
                    'order_number' => $line->remarks ?? null,
                    'remarks' => $line->remarks ?? null,
                    'qty' => (int) $line->qty,
                    'scanned_at' => optional($line->scanned_at)->toISOString(),
                    'update_qty_url' => route('sales.shipment_returns.update_line_qty', $line),
                ],
                'totals' => [
                    'total_qty' => $totalQty,
                    'total_lines' => $totalLines,
                ],
            ]);
        }

        return redirect()
            ->route('sales.shipment_returns.show', $shipmentReturn)
            ->with('last_scanned_return_line_id', $line->id);
    }

    /**
     * Submit retur (lock scan, belum stock in).
     */
    public function submit(Request $request, ShipmentReturn $shipmentReturn)
    {
        if (!in_array($shipmentReturn->status, ['draft', 'submitted'], true)) {
            return back()
                ->with('status', 'error')
                ->with('message', 'Hanya retur draft yang bisa disimpan.');
        }

        if ($shipmentReturn->lines()->count() === 0) {
            return back()
                ->with('status', 'error')
                ->with('message', 'Tidak ada item di retur ini.');
        }

        // Endpoint lama dipertahankan, tetapi Submit tidak lagi membuat status
        // baru. Pencatatan tetap berada di draft sampai diterima ke WH-RTS.
        $shipmentReturn->status = 'draft';
        $shipmentReturn->submitted_at = null;
        $shipmentReturn->submitted_by = null;
        $shipmentReturn->save();

        return redirect()
            ->route('sales.shipment_returns.show', $shipmentReturn)
            ->with('status', 'success')
            ->with('message', 'Draft retur tersimpan.');
    }

    /**
     * Terima retur ke WH-RTS → stock in.
     *
     * Marketplace/store tidak diperlukan di tahap ini. Yang menjadi sumber
     * kebenaran adalah dokumen retur dan item yang sudah direkam di dalamnya.
     */
    public function receive(Request $request, ShipmentReturn $shipmentReturn)
    {
        $warehouse = Warehouse::where('code', 'WH-RTS')->first();

        if (!$warehouse) {
            return back()
                ->with('status', 'error')
                ->with('message', 'Warehouse WH-RTS belum dikonfigurasi.');
        }

        $result = DB::transaction(function () use ($shipmentReturn, $warehouse) {
            $lockedReturn = ShipmentReturn::query()
                ->with(['lines.item'])
                ->lockForUpdate()
                ->findOrFail($shipmentReturn->id);

            if ($lockedReturn->status === 'posted') {
                return [
                    'status' => 'already_posted',
                    'shipment_return' => $lockedReturn,
                ];
            }

            if (!in_array($lockedReturn->status, ['draft', 'submitted'], true)) {
                return [
                    'status' => 'error',
                    'message' => 'Hanya draft retur yang bisa diterima ke WH-RTS.',
                ];
            }

            if ($lockedReturn->lines->isEmpty()) {
                return [
                    'status' => 'error',
                    'message' => 'Tidak ada item di pencatatan retur ini.',
                ];
            }

            $totalQty = 0;

            foreach ($lockedReturn->lines as $line) {
                $qty = (int) $line->qty;

                if ($qty <= 0) {
                    continue;
                }

                $totalQty += $qty;

                // ✅ Ambil HPP dari master item
                $unitCost = optional($line->item)->hpp;

                // Opsional: kalau HPP null atau <= 0, bisa fallback ke null
                if ($unitCost !== null) {
                    $unitCost = (float) $unitCost;
                    if ($unitCost <= 0) {
                        $unitCost = null;
                    }
                }

                $this->inventory->stockIn(
                    warehouseId: $warehouse->id,
                    itemId: $line->item_id,
                    qty: $qty,
                    date: $lockedReturn->date,
                    sourceType: 'shipment_return',
                    sourceId: $lockedReturn->id,
                    sourceLineId: $line->id,
                    notes: 'Penerimaan retur ' . ($lockedReturn->code ?? $lockedReturn->id) . ' ke WH-RTS',
                    lotId: null,
                    unitCost: $unitCost,
                    affectLotCost: false,
                );
            }

            if ($totalQty <= 0) {
                return [
                    'status' => 'error',
                    'message' => 'Total qty retur harus lebih dari 0.',
                ];
            }

            $lockedReturn->status = 'posted';
            $lockedReturn->total_qty = $totalQty;
            $lockedReturn->posted_at = now();
            $lockedReturn->posted_by = auth()->id();
            $lockedReturn->save();

            return [
                'status' => 'ok',
                'shipment_return' => $lockedReturn,
            ];
        });

        if ($result['status'] === 'already_posted') {
            return back()
                ->with('status', 'error')
                ->with('message', 'Retur ini sudah diterima ke WH-RTS sebelumnya.');
        }

        if ($result['status'] !== 'ok') {
            return back()
                ->with('status', 'error')
                ->with('message', $result['message']);
        }

        return redirect()
            ->route('sales.shipment_returns.show', $result['shipment_return'])
            ->with('status', 'success')
            ->with('message', 'Retur diterima ke WH-RTS dan stok berhasil ditambahkan.');
    }

    /**
     * Alias kompatibilitas untuk endpoint lama.
     */
    public function post(Request $request, ShipmentReturn $shipmentReturn)
    {
        return $this->receive($request, $shipmentReturn);
    }

    /**
     * Batalkan draft retur tanpa menghapus histori pencatatan.
     * Retur yang sudah diterima ke WH-RTS harus melalui proses reversal terpisah.
     */
    public function cancel(Request $request, ShipmentReturn $shipmentReturn)
    {
        $result = DB::transaction(function () use ($shipmentReturn) {
            $lockedReturn = ShipmentReturn::query()
                ->lockForUpdate()
                ->findOrFail($shipmentReturn->id);

            if ($lockedReturn->status === 'cancelled') {
                return [
                    'status' => 'already_cancelled',
                    'shipment_return' => $lockedReturn,
                ];
            }

            if ($lockedReturn->status === 'posted') {
                return [
                    'status' => 'error',
                    'message' => 'Retur yang sudah diterima ke WH-RTS tidak bisa dibatalkan dari sini.',
                ];
            }

            if (!in_array($lockedReturn->status, ['draft', 'submitted'], true)) {
                return [
                    'status' => 'error',
                    'message' => 'Status retur tidak dapat dibatalkan.',
                ];
            }

            $lockedReturn->status = 'cancelled';
            $lockedReturn->cancelled_at = now();
            $lockedReturn->cancelled_by = auth()->id();
            $lockedReturn->save();

            return [
                'status' => 'ok',
                'shipment_return' => $lockedReturn,
            ];
        });

        if ($result['status'] === 'already_cancelled') {
            return back()
                ->with('status', 'error')
                ->with('message', 'Retur ini sudah dibatalkan.');
        }

        if ($result['status'] !== 'ok') {
            return back()
                ->with('status', 'error')
                ->with('message', $result['message']);
        }

        return redirect()
            ->route('sales.shipment_returns.show', $result['shipment_return'])
            ->with('status', 'success')
            ->with('message', 'Retur berhasil dibatalkan.');
    }

    /**
     * Inline update qty line retur (support AJAX).
     */
    public function updateLineQty(Request $request, ShipmentReturnLine $line)
    {
        $header = $line->header;

        if (!$header || $header->status !== 'draft') {
            $message = 'Retur sudah tidak draft, qty tidak bisa diubah.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $message,
                ], 409);
            }

            return back()
                ->with('status', 'error')
                ->with('message', $message);
        }

        $data = $request->validate([
            'qty' => ['required', 'integer', 'min:0'],
        ]);

        $qty = (int) $data['qty'];

        DB::transaction(function () use (&$line, $qty) {
            $scanItem = ShipmentReturnOrderScanItem::query()
                ->where('shipment_return_line_id', $line->id)
                ->lockForUpdate()
                ->first();

            if ($qty === 0) {
                $orderScan = $scanItem?->orderScan;
                $scanItem?->delete();
                $line->delete();

                if ($orderScan && !$orderScan->items()->exists()) {
                    $orderScan->delete();
                }
            } else {
                $line->qty = $qty;
                $line->save();

                if ($scanItem) {
                    $scanItem->qty_scanned = $qty;
                    $scanItem->save();
                }
            }
        });

        $totalQty = (int) ShipmentReturnLine::where('shipment_return_id', $header->id)->sum('qty');
        $totalLines = (int) ShipmentReturnLine::where('shipment_return_id', $header->id)->count();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'ok',
                'message' => 'Qty retur berhasil diperbarui.',
                'deleted' => $qty === 0,
                'qty' => $qty,
                'totals' => [
                    'total_qty' => $totalQty,
                    'total_lines' => $totalLines,
                ],
            ]);
        }

        return back()
            ->with('status', 'success')
            ->with('message', 'Qty retur berhasil diperbarui.');
    }

    /**
     * Placeholder kalau nanti mau sync dari perangkat lain.
     */
    public function syncScans(Request $request, ShipmentReturn $shipmentReturn)
    {
        return back()
            ->with('status', 'error')
            ->with('message', 'Fitur sync scans retur belum diimplementasi.');
    }
}
