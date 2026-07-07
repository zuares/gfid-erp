<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Shipment;
use App\Models\ShipmentLine;
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

    protected function returnStoreChannel(Store $store): ?string
    {
        $key = strtoupper(trim(($store->code ?? '') . ' ' . ($store->name ?? '')));

        return match (true) {
            str_contains($key, 'SHOPEE') || str_starts_with($key, 'SHP') => 'Shopee',
            str_contains($key, 'TIKTOK') || str_contains($key, 'TTK') => 'TikTok',
            str_contains($key, 'OFFLINE') || str_contains($key, 'OFFL') => 'Offline',
            default => null,
        };
    }

    protected function returnStores()
    {
        return Store::orderBy('code')
            ->get()
            ->filter(function (Store $store) {
                return $this->returnStoreChannel($store) !== null;
            })
            ->map(function (Store $store) {
                $store->setAttribute('return_channel', $this->returnStoreChannel($store));

                return $store;
            })
            ->values();
    }

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
        $stores = $this->returnStores();

        $shipment = null;
        if ($request->filled('shipment_id')) {
            $shipment = Shipment::with(['store', 'lines.item'])
                ->find($request->shipment_id);
        }

        return view('sales.shipment_returns.create', [
            'stores' => $stores,
            'shipment' => $shipment,
        ]);
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

    public function scanLookup(Request $request, ShipmentReturn $shipmentReturn)
    {
        $code = mb_strtoupper(trim((string) $request->query('code', '')));

        if ($code === '') {
            return response()->json([
                'type' => 'empty',
            ]);
        }

        $item = Item::query()
            ->where('type', 'finished_good')
            ->where(function ($query) use ($code) {
                $query->where('barcode', $code)
                    ->orWhere('code', $code)
                    ->orWhereHas('barcodes', function ($barcodeQuery) use ($code) {
                        $barcodeQuery->where('barcode', $code)
                            ->where('is_active', true);
                    });
            })
            ->first(['id', 'code', 'name']);

        if ($item) {
            return response()->json([
                'type' => 'item',
                'item' => [
                    'id' => $item->id,
                    'code' => $item->code,
                    'name' => $item->name,
                ],
            ]);
        }

        $shipment = Shipment::with('store:id,code,name')
            ->where('code', $code)
            ->first(['id', 'code', 'store_id', 'date']);

        if ($shipment) {
            return response()->json([
                'type' => 'order',
                'order' => [
                    'id' => $shipment->id,
                    'code' => $shipment->code,
                    'store_code' => $shipment->store?->code,
                    'store_name' => $shipment->store?->name,
                ],
            ]);
        }

        return response()->json([
            'type' => 'unknown',
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
                $shipment = Shipment::with('store:id,code,name')
                    ->where('code', $code)
                    ->first();

                $scan = ShipmentReturnOrderScan::query()
                    ->where('shipment_return_id', $shipmentReturn->id)
                    ->where(function ($query) use ($code) {
                        $query->where('order_number', $code)
                            ->orWhere('order_no', $code);
                    })
                    ->lockForUpdate()
                    ->first();

                if (!$scan) {
                    $scan = ShipmentReturnOrderScan::create([
                        'shipment_return_id' => $shipmentReturn->id,
                        'order_no' => $code,
                        'order_number' => $code,
                        'shipment_id' => $shipment?->id,
                        'status' => 'pending',
                        'match_status' => 'pending',
                        'source_type' => 'dev_command',
                        'source' => 'dev_command',
                        'raw_payload' => $shipment ? [
                            'shipment_id' => $shipment->id,
                            'store_id' => $shipment->store_id,
                            'store_code' => $shipment->store?->code,
                            'store_name' => $shipment->store?->name,
                        ] : null,
                    ]);
                } else {
                    $scan->fill([
                        'order_number' => $scan->order_number ?: $code,
                        'order_no' => $scan->order_no ?: $code,
                        'shipment_id' => $scan->shipment_id ?: $shipment?->id,
                    ])->save();
                }

                if (!$shipmentReturn->shipment_id && $shipment) {
                    $shipmentReturn->shipment_id = $shipment->id;
                    $shipmentReturn->save();
                }

                $label = $shipment
                    ? collect([$shipment->store?->code, $shipment->store?->name])->filter()->implode(' - ')
                    : 'Manual';

                return [
                    'code' => $code,
                    'label' => $label !== '' ? $label : 'Manual',
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
                ->map(fn ($scan) => mb_strtoupper(trim((string) ($scan->order_number ?: $scan->order_no))))
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
            'store_id'      => ['required', 'exists:stores,id'],
            'shipment_id'   => ['nullable', 'exists:shipments,id'],
            'order_number'  => ['nullable', 'string', 'max:100'],
            'date'          => ['required', 'date'],
            'reason'        => ['nullable', 'string'],
            'notes'         => ['nullable', 'string'],
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

        $store = Store::findOrFail($data['store_id']);
        if ($this->returnStoreChannel($store) === null) {
            return back()
                ->withErrors(['store_id' => 'Marketplace retur hanya Shopee, TikTok, atau Offline.'])
                ->withInput();
        }

        $storeCode = strtoupper(trim($store->code ?? ''));
        $storeKey  = $storeCode . ' ' . strtoupper(trim($store->name ?? ''));

        $prefix = 'RTP';
        $cleanCode = preg_replace('/[^A-Z0-9]/', '', $storeCode);
        if ($cleanCode !== '') {
            $prefix = substr($cleanCode, 0, 3) . 'R';
        }
        if (str_contains($storeKey, 'SHP') || str_contains($storeKey, 'SHOPEE')) {
            $prefix = 'SHR';
        } elseif (str_contains($storeKey, 'TTK') || str_contains($storeKey, 'TIKTOK')) {
            $prefix = 'TTR';
        }

        $ret = DB::transaction(function () use ($data, $shipmentId, $prefix, $notes) {
            $ret = ShipmentReturn::create([
                'code'        => ShipmentReturn::generateCode($prefix),
                'store_id'    => $data['store_id'],
                'shipment_id' => $shipmentId,
                'date'        => $data['date'],
                'status'      => 'draft',
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
        });

        return redirect()
            ->route('sales.shipment_returns.edit', $ret)
            ->with('status', 'success')
            ->with('message', 'Draft retur dibuat. Silakan scan order dan item retur.');
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

            $foundShipment = null;
            $orderScan = null;
            if ($orderNumber !== '') {
                $foundShipment = Shipment::with('store:id,code,name')
                    ->where('code', $orderNumber)
                    ->first();

                $orderScan = ShipmentReturnOrderScan::query()
                    ->where('shipment_return_id', $shipmentReturn->id)
                    ->where(function ($query) use ($orderNumber) {
                        $query->where('order_number', $orderNumber)
                            ->orWhere('order_no', $orderNumber);
                    })
                    ->lockForUpdate()
                    ->first();

                if (!$orderScan) {
                    $orderScan = ShipmentReturnOrderScan::create([
                        'shipment_return_id' => $shipmentReturn->id,
                        'order_no' => $orderNumber,
                        'order_number' => $orderNumber,
                        'shipment_id' => $foundShipment?->id,
                        'matched_order_id' => null,
                        'status' => 'pending',
                        'match_status' => 'pending',
                        'source_type' => 'scanner',
                        'source' => 'scanner',
                        'raw_payload' => $foundShipment ? [
                            'shipment_id' => $foundShipment->id,
                            'store_id' => $foundShipment->store_id,
                            'store_code' => $foundShipment->store?->code,
                            'store_name' => $foundShipment->store?->name,
                        ] : null,
                    ]);
                } elseif (!$orderScan->order_number) {
                    $orderScan->order_number = $orderNumber;
                    $orderScan->save();
                }

                if (!$shipmentReturn->shipment_id && $foundShipment) {
                    $shipmentReturn->shipment_id = $foundShipment->id;
                }

                $noteLine = 'Pesanan retur: ' . $orderNumber;
                $notes = (string) ($shipmentReturn->notes ?? '');
                if (!str_contains($notes, $noteLine)) {
                    $shipmentReturn->notes = trim($notes . "\n" . $noteLine);
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

            // Opsional: link ke shipment_line asal kalau ada shipment_id
            $shipmentLineId = null;
            $shipmentForLine = $foundShipment ?: $shipmentReturn->shipment;
            if ($shipmentForLine) {
                $shipmentLine = ShipmentLine::query()
                    ->where('shipment_id', $shipmentForLine->id)
                    ->where('item_id', $item->id)
                    ->first();

                if ($shipmentLine) {
                    $shipmentLineId = $shipmentLine->id;
                }
            }

            if ($line) {
                $line->qty = (int) $line->qty + $qty;
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
                            'qty' => (int) $line->qty,
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
        if ($shipmentReturn->status !== 'draft') {
            return back()
                ->with('status', 'error')
                ->with('message', 'Hanya retur draft yang bisa di-submit.');
        }

        if ($shipmentReturn->lines()->count() === 0) {
            return back()
                ->with('status', 'error')
                ->with('message', 'Tidak ada item di retur ini.');
        }

        $shipmentReturn->status = 'submitted';
        $shipmentReturn->submitted_at = now();
        $shipmentReturn->submitted_by = auth()->id();
        $shipmentReturn->save();

        return redirect()
            ->route('sales.shipment_returns.show', $shipmentReturn)
            ->with('status', 'success')
            ->with('message', 'Retur disubmit. Siap diposting ke WH-RTS.');
    }

    /**
     * Posting retur → stock in ke WH-RTS.
     */
    public function post(Request $request, ShipmentReturn $shipmentReturn)
    {
        if ($shipmentReturn->status === 'posted') {
            return back()
                ->with('status', 'error')
                ->with('message', 'Shipment retur sudah diposting sebelumnya.');
        }

        if ($shipmentReturn->status !== 'submitted') {
            return back()
                ->with('status', 'error')
                ->with('message', 'Shipment retur harus berstatus submitted sebelum diposting.');
        }

        $shipmentReturn->load(['lines.item', 'store']);

        if ($shipmentReturn->lines->isEmpty()) {
            return back()
                ->with('status', 'error')
                ->with('message', 'Tidak ada item di shipment retur ini.');
        }

        // ✅ Ambil WH-RTS (gudang FG untuk channel)
        $warehouse = Warehouse::where('code', 'WH-RTS')->first();

        if (!$warehouse) {
            return back()
                ->with('status', 'error')
                ->with('message', 'Warehouse WH-RTS belum dikonfigurasi.');
        }

        DB::transaction(function () use ($shipmentReturn, $warehouse) {
            $totalQty = 0;

            foreach ($shipmentReturn->lines as $line) {
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

                // ✅ Nambah stok FG ke WH-RTS dengan HPP dari item
                $this->inventory->stockIn(
                    warehouseId: $warehouse->id,
                    itemId: $line->item_id,
                    qty: $qty,
                    date: $shipmentReturn->date,
                    sourceType: 'shipment_return',
                    sourceId: $shipmentReturn->id,
                    notes: 'Retur shipment ' . ($shipmentReturn->code ?? $shipmentReturn->id) .
                    ' dari store ' . ($shipmentReturn->store->code ?? '-'),
                    lotId: null, // FG tidak pakai LOT
                    unitCost: $unitCost, // ⬅️ sekarang pakai kolom items.hpp
                    affectLotCost: false, // tetap jangan sentuh LotCost kain
                );
            }

            $shipmentReturn->status = 'posted';
            $shipmentReturn->total_qty = $totalQty;

            // Kalau nanti ada kolom posted_at / posted_by:
            // $shipmentReturn->posted_at = now();
            // $shipmentReturn->posted_by = auth()->id();

            $shipmentReturn->save();
        });

        return redirect()
            ->route('sales.shipment_returns.show', $shipmentReturn)
            ->with('status', 'success')
            ->with('message', 'Shipment retur berhasil diposting & stok bertambah di WH-RTS.');
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
                    $scanItem->qty = $qty;
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
