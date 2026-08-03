<?php

namespace App\Http\Controllers\Purchasing;

use App\Helpers\CodeGenerator;
use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestLine;
use App\Models\Supplier;
use App\Services\Purchasing\PurchaseOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class PurchaseRequestController extends Controller
{
    // =========================================================
    // INDEX
    // =========================================================

    public function index(Request $request)
    {
        $query = PurchaseRequest::query()
            ->with([
                'supplier:id,code,name',
                'requestedBy:id,name',
                'convertedToPo:id,code',           // PR-E: link PO di tabel
                'purchaseOrders:id,code,purchase_request_id',
                'lines:id,purchase_request_id,item_id,supplier_id,qty,unit_price,notes',
                'lines.item:id,code,name,unit',
                'lines.supplier:id,code,name',
            ])
            ->withCount('lines')
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $allowed = ['draft', 'approved', 'rejected', 'converted', 'cancelled'];
            if (in_array($request->status, $allowed, true)) {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', (int) $request->supplier_id);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('code', 'like', "%{$s}%")
                  ->orWhereHas('supplier', fn($sq) => $sq
                      ->where('name', 'like', "%{$s}%")
                      ->orWhere('code', 'like', "%{$s}%")
                  )
                  ->orWhereHas('lines.item', fn ($itemQuery) => $itemQuery
                      ->where('name', 'like', "%{$s}%")
                      ->orWhere('code', 'like', "%{$s}%"));
            });
        }

        if ($request->filled('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        $prs = $query->paginate(20)->withQueryString();

        // PR-E: summary + converted count
        $summary = [
            'total'     => PurchaseRequest::count(),
            'draft'     => PurchaseRequest::where('status', 'draft')->count(),
            'approved'  => PurchaseRequest::where('status', 'approved')->count(),
            'converted' => PurchaseRequest::where('status', 'converted')->count(),
            'rejected'  => PurchaseRequest::where('status', 'rejected')->count(),
        ];

        $suppliers   = Supplier::orderBy('name')->get(['id', 'name', 'code']);
        $canSeeMoney = $this->canSeeMoney($request); // PR-E: pass ke view

        return view('purchasing.purchase_requests.index', compact('prs', 'summary', 'suppliers', 'canSeeMoney'));
    }

    // =========================================================
    // CREATE
    // =========================================================

    public function create(Request $request)
    {
        $suppliers    = Supplier::orderBy('name')->get(['id', 'name', 'code']);
        $items        = $this->purchaseRequestItems();
        $canSeeMoney  = $this->canSeePurchaseRequestMoney($request);
        $linesData    = [];

        return view('purchasing.purchase_requests.create', compact(
            'suppliers', 'items', 'canSeeMoney', 'linesData'
        ));
    }

    // =========================================================
    // STORE
    // =========================================================

    public function store(Request $request)
    {
        $data = $request->validate([
            'date'               => ['required', 'date'],
            'supplier_id'        => ['nullable', 'integer', 'exists:suppliers,id'],
            'notes'              => ['nullable', 'string', 'max:2000'],
            'lines'              => ['required', 'array', 'min:1'],
            'lines.*.item_id'    => ['nullable', 'integer', 'exists:items,id'],
            'lines.*.qty'        => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'lines.*.notes'      => ['nullable', 'string', 'max:255'],
        ]);

        // Non-owner: hapus unit_price dari payload
        $items = $this->purchaseRequestItems();

        if (!$this->canSeePurchaseRequestMoney($request)) {
            foreach ($data['lines'] as &$line) {
                $line['unit_price'] = null;
            }
            unset($line);
        } else {
            $data['lines'] = $this->applyDefaultPurchaseRequestLinePrices($data['lines'], $items);
        }

        $pr = null;

        DB::transaction(function () use ($data, $request, &$pr) {
            $pr = PurchaseRequest::create([
                'code'         => CodeGenerator::make('PR'),
                'date'         => $data['date'],
                'supplier_id'  => $data['supplier_id'] ?? null,
                'requested_by' => (int) $request->user()->id,
                'status'       => 'draft',
                'notes'        => $data['notes'] ?? null,
            ]);

            foreach ($data['lines'] as $line) {
                PurchaseRequestLine::create([
                    'purchase_request_id' => $pr->id,
                    'item_id'             => $line['item_id'] ?? null,
                    'qty'                 => (float) to_num($line['qty'] ?? 0),
                    'unit_price'          => isset($line['unit_price']) && $line['unit_price'] !== ''
                                                ? (float) to_num($line['unit_price'])
                                                : null,
                    'notes'               => $line['notes'] ?? null,
                ]);
            }
        });

        return redirect()
            ->route('purchasing.purchase_requests.show', $pr->id)
            ->with('success', "PR {$pr->code} berhasil dibuat.");
    }

    // =========================================================
    // SHOW
    // =========================================================

    public function show(Request $request, PurchaseRequest $purchase_request)
    {
        $purchase_request->load([
            'supplier',
            'requestedBy',
            'approvedBy',
            'rejectedBy',
            'lines.item',
            'lines.supplier:id,code,name',
            'lines.purchaseOrder:id,code,status',
            'convertedToPo:id,code', // PR-E: tampilkan link PO di show
            'purchaseOrders:id,code,status,supplier_id,purchase_request_id',
            'purchaseOrders.supplier:id,code,name',
        ]);

        $canSeeMoney = $this->canSeeMoney($request);
        $user        = $request->user();

        $canApproveReject = $purchase_request->isDraft()
            && ($user->isOwner()
                || in_array($user->role, ['admin'], true)
                || $user->isDeveloper());

        return view('purchasing.purchase_requests.show', compact(
            'purchase_request',
            'canSeeMoney',
            'canApproveReject',
            'user'
        ));
    }

    // =========================================================
    // EDIT
    // =========================================================

    public function edit(Request $request, PurchaseRequest $purchase_request)
    {
        if (!$purchase_request->isDraft()) {
            return redirect()
                ->route('purchasing.purchase_requests.show', $purchase_request->id)
                ->with('error', 'Hanya PR berstatus draft yang bisa diedit.');
        }

        $purchase_request->load(['lines.item']);

        $suppliers   = Supplier::orderBy('name')->get(['id', 'name', 'code']);
        $items       = $this->purchaseRequestItems();
        $canSeeMoney = $this->canSeePurchaseRequestMoney($request);
        $linesData   = $purchase_request->lines
            ->sortBy(fn ($line) => (float) ($line->qty ?? 0))
            ->values()
            ->map(function ($line) use ($canSeeMoney, $items) {
                $linePrice = (float) ($line->unit_price ?? 0);

                if ($canSeeMoney && $linePrice <= 0) {
                    $suggestedPrice = $this->suggestPurchaseRequestItemPrice($line->item, $items);
                    if ($suggestedPrice !== null && $suggestedPrice > 0) {
                        $line->unit_price = $suggestedPrice;
                    }
                }

                return $line;
            })
            ->toArray();

        return view('purchasing.purchase_requests.edit', compact(
            'purchase_request',
            'suppliers',
            'items',
            'canSeeMoney',
            'linesData'
        ));
    }

    // =========================================================
    // UPDATE
    // =========================================================

    public function update(Request $request, PurchaseRequest $purchase_request)
    {
        if (!$purchase_request->isDraft()) {
            return redirect()
                ->route('purchasing.purchase_requests.show', $purchase_request->id)
                ->with('error', 'Hanya PR berstatus draft yang bisa diedit.');
        }

        $data = $request->validate([
            'date'               => ['required', 'date'],
            'supplier_id'        => ['nullable', 'integer', 'exists:suppliers,id'],
            'notes'              => ['nullable', 'string', 'max:2000'],
            'lines'              => ['required', 'array', 'min:1'],
            'lines.*.item_id'    => ['nullable', 'integer', 'exists:items,id'],
            'lines.*.qty'        => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'lines.*.notes'      => ['nullable', 'string', 'max:255'],
        ]);

        $items = $this->purchaseRequestItems();

        if (!$this->canSeePurchaseRequestMoney($request)) {
            foreach ($data['lines'] as &$line) {
                $line['unit_price'] = null;
            }
            unset($line);
        } else {
            $data['lines'] = $this->applyDefaultPurchaseRequestLinePrices($data['lines'], $items);
        }

        DB::transaction(function () use ($data, $purchase_request) {
            $purchase_request->update([
                'date'        => $data['date'],
                'supplier_id' => $data['supplier_id'] ?? null,
                'notes'       => $data['notes'] ?? null,
            ]);

            // Replace lines (hapus lama, insert baru)
            $purchase_request->lines()->delete();

            foreach ($data['lines'] as $line) {
                PurchaseRequestLine::create([
                    'purchase_request_id' => $purchase_request->id,
                    'item_id'             => $line['item_id'] ?? null,
                    'qty'                 => (float) to_num($line['qty'] ?? 0),
                    'unit_price'          => isset($line['unit_price']) && $line['unit_price'] !== ''
                                                ? (float) to_num($line['unit_price'])
                                                : null,
                    'notes'               => $line['notes'] ?? null,
                ]);
            }
        });

        if ($request->boolean('after_save_allocate')) {
            return redirect()
                ->route('purchasing.purchase_requests.allocate_suppliers', $purchase_request->id)
                ->with('success', "PR {$purchase_request->code} berhasil disimpan. Lanjut pilih supplier untuk draft PO.");
        }

        return redirect()
            ->route('purchasing.purchase_requests.show', $purchase_request->id)
            ->with('success', "PR {$purchase_request->code} berhasil diperbarui.");
    }

    // =========================================================
    // CONVERT → PO (PR-D)
    // =========================================================

    public function allocateSuppliers(Request $request, PurchaseRequest $purchase_request)
    {
        if (!$this->canAllocateSuppliers($purchase_request)) {
            return redirect()
                ->route('purchasing.purchase_requests.show', $purchase_request->id)
                ->with('error', 'PR ini belum siap diproses atau sudah pernah dibagi ke PO.');
        }

        $purchase_request->load(['lines.item:id,code,name,unit,item_category_id', 'supplier:id,code,name']);
        $suppliers = Supplier::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
        $fallbackSupplier = $suppliers->first();
        $fallbackRecommendation = $fallbackSupplier ? (object) [
            'supplier_id' => (int) $fallbackSupplier->id,
            'is_primary' => 0,
            'source' => 'fallback',
            'category_name' => null,
        ] : null;
        $itemRecommendations = DB::table('supplier_items')
            ->join('suppliers', 'suppliers.id', '=', 'supplier_items.supplier_id')
            ->whereIn('supplier_items.item_id', $purchase_request->lines->pluck('item_id'))
            ->where('supplier_items.active', true)
            ->where('suppliers.active', true)
            ->orderByDesc('supplier_items.is_primary')
            ->orderByDesc('supplier_items.updated_at')
            ->get(['supplier_items.item_id', 'supplier_items.supplier_id', 'supplier_items.is_primary'])
            ->groupBy('item_id')
            ->map(fn ($rows) => $rows->first());
        $categoryRecommendations = DB::table('supplier_category_mappings')
            ->join('suppliers', 'suppliers.id', '=', 'supplier_category_mappings.supplier_id')
            ->join('item_categories', 'item_categories.id', '=', 'supplier_category_mappings.item_category_id')
            ->whereIn('supplier_category_mappings.item_category_id', $purchase_request->lines->pluck('item.item_category_id')->filter())
            ->where('supplier_category_mappings.active', true)
            ->where('suppliers.active', true)
            ->orderByDesc('supplier_category_mappings.is_primary')
            ->orderByDesc('supplier_category_mappings.updated_at')
            ->get([
                'supplier_category_mappings.item_category_id',
                'supplier_category_mappings.supplier_id',
                'supplier_category_mappings.is_primary',
                'item_categories.name as category_name',
            ])
            ->groupBy('item_category_id')
            ->map(fn ($rows) => $rows->first());

        $categoryHistoryRecommendations = DB::table('purchase_order_lines')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_lines.purchase_order_id')
            ->join('items', 'items.id', '=', 'purchase_order_lines.item_id')
            ->join('item_categories', 'item_categories.id', '=', 'items.item_category_id')
            ->join('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
            ->whereIn('items.item_category_id', $purchase_request->lines->pluck('item.item_category_id')->filter())
            ->whereIn('purchase_orders.status', ['approved', 'done', 'open', 'processing', 'closed'])
            ->where('suppliers.active', true)
            ->orderByDesc('purchase_orders.date')
            ->orderByDesc('purchase_orders.id')
            ->get([
                'items.item_category_id',
                'purchase_orders.supplier_id',
                'item_categories.name as category_name',
            ])
            ->groupBy('item_category_id')
            ->map(fn ($rows) => $rows->first());
            
        $lastPurchasedSuppliers = DB::table('purchase_order_lines')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_lines.purchase_order_id')
            ->join('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
            ->whereIn('purchase_order_lines.item_id', $purchase_request->lines->pluck('item_id'))
            ->whereIn('purchase_orders.status', ['approved', 'done', 'open', 'processing', 'closed'])
            ->where('suppliers.active', true)
            ->orderByDesc('purchase_orders.date')
            ->orderByDesc('purchase_orders.id')
            ->get([
                'purchase_order_lines.item_id',
                'purchase_orders.supplier_id',
            ])
            ->groupBy('item_id')
            ->map(fn ($rows) => $rows->first());

        $recommendedSuppliers = $purchase_request->lines->mapWithKeys(function ($line) use (
            $itemRecommendations,
            $categoryRecommendations,
            $categoryHistoryRecommendations,
            $lastPurchasedSuppliers,
            $fallbackRecommendation
        ) {
            if ($itemRecommendation = $itemRecommendations->get($line->item_id)) {
                $itemRecommendation->source = 'item';
                return [$line->item_id => $itemRecommendation];
            }

            if ($categoryRecommendation = $categoryRecommendations->get($line->item?->item_category_id)) {
                $categoryRecommendation->source = 'category';
                return [$line->item_id => $categoryRecommendation];
            }

            if ($categoryHistory = $categoryHistoryRecommendations->get($line->item?->item_category_id)) {
                $categoryHistory->source = 'history_category';
                $categoryHistory->is_primary = 0;
                return [$line->item_id => $categoryHistory];
            }
            
            if ($lastPurchased = $lastPurchasedSuppliers->get($line->item_id)) {
                $lastPurchased->source = 'history';
                $lastPurchased->is_primary = 0;
                return [$line->item_id => $lastPurchased];
            }

            if ($fallbackRecommendation) {
                return [$line->item_id => $fallbackRecommendation];
            }

            return [];
        });

        return view('purchasing.purchase_requests.allocate-suppliers', compact(
            'purchase_request',
            'suppliers',
            'recommendedSuppliers',
        ));
    }

    public function convert(
        Request $request,
        PurchaseRequest $purchase_request,
        PurchaseOrderService $purchaseOrderService
    ) {
        if (!$this->canAllocateSuppliers($purchase_request)) {
            return redirect()
                ->route('purchasing.purchase_requests.show', $purchase_request->id)
                ->with('error', 'PR ini belum siap diproses atau sudah pernah dibuatkan PO.');
        }

        $lineIds = $purchase_request->lines()->pluck('id')->all();
        $data = $request->validate([
            'suppliers' => ['required', 'array', 'size:' . count($lineIds)],
            'suppliers.*' => ['required', 'integer', Rule::exists('suppliers', 'id')->where('active', true)],
        ]);

        $submittedIds = array_map('intval', array_keys($data['suppliers']));
        sort($submittedIds);
        $expectedIds = array_map('intval', $lineIds);
        sort($expectedIds);

        if ($submittedIds !== $expectedIds) {
            return back()->withInput()->withErrors([
                'suppliers' => 'Semua item PR harus memiliki supplier sebelum PO dibuat.',
            ]);
        }

        $previewLines = $purchase_request->lines()
            ->with('item:id,type')
            ->get();

        $previewGroups = $previewLines->groupBy(function ($line) use ($data) {
            $supplierId = (int) ($data['suppliers'][$line->id] ?? 0);
            $itemType = (string) ($line->item->type ?? 'material');

            return $supplierId . '|' . $itemType;
        });

        $supplierIds = $previewGroups->keys()
            ->map(fn ($key) => (int) explode('|', (string) $key, 2)[0])
            ->filter(fn ($value) => $value > 0)
            ->unique()
            ->values()
            ->all();

        $supplierCodes = DB::table('suppliers')
            ->whereIn('id', $supplierIds)
            ->pluck('code', 'id')
            ->all();

        $generatedOrderCodes = [];
        foreach ($previewGroups as $groupKey => $supplierLines) {
            [$supplierId] = explode('|', (string) $groupKey, 2);
            $supplierId = (int) $supplierId;
            $supplierCode = $supplierCodes[$supplierId] ?? null;
            $prefix = $supplierCode ? 'PO-' . strtoupper($supplierCode) : 'PO';

            $generatedOrderCodes[$groupKey] = CodeGenerator::make($prefix);
        }

        $createdOrders = DB::transaction(function () use (
            $data,
            $purchase_request,
            $purchaseOrderService,
            $request,
            $generatedOrderCodes
        ) {
            $lockedRequest = PurchaseRequest::query()
                ->whereKey($purchase_request->id)
                ->lockForUpdate()
                ->firstOrFail();
            $lines = $purchase_request->lines()->with('item')->lockForUpdate()->get();

            if (!in_array($lockedRequest->status, ['draft', 'approved'], true) || $lines->contains(fn ($line) => $line->purchase_order_id !== null)) {
                abort(409, 'PR sudah diproses oleh pengguna lain. Muat ulang halaman.');
            }

            $orders = collect();
            
            $lastPrices = DB::table('purchase_order_lines')
                ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_lines.purchase_order_id')
                ->whereIn('purchase_order_lines.item_id', $lines->pluck('item_id'))
                ->whereIn('purchase_orders.status', ['approved', 'done', 'open', 'processing', 'closed'])
                ->orderByDesc('purchase_orders.date')
                ->orderByDesc('purchase_orders.id')
                ->get(['purchase_order_lines.item_id', 'purchase_order_lines.unit_price'])
                ->groupBy('item_id')
                ->map(fn ($rows) => $rows->first()->unit_price);
            
            $grouped = $lines->groupBy(function ($line) use ($data) {
                $supplierId = (int) $data['suppliers'][$line->id];
                $itemType = $line->item->type;
                return $supplierId . '|' . $itemType;
            });

            foreach ($grouped as $key => $supplierLines) {
                [$supplierId, $orderType] = explode('|', $key);

                if (!isset($generatedOrderCodes[$key])) {
                    abort(409, 'Kode PO belum siap. Silakan muat ulang halaman dan coba lagi.');
                }

                $order = $purchaseOrderService->create([
                    'code' => $generatedOrderCodes[$key],
                    'date' => now()->toDateString(),
                    'supplier_id' => (int) $supplierId,
                    'discount' => 0,
                    'tax_percent' => 0,
                    'shipping_cost' => 0,
                    'notes' => "Dibuat dari {$purchase_request->code}. Harga dilengkapi owner sebelum approval.",
                    'created_by' => (int) $request->user()->id,
                    'status' => 'draft',
                    'order_type' => $orderType,
                    'purchase_request_id' => $purchase_request->id,
                    'lines' => $supplierLines->map(function ($line) use ($lastPrices) {
                        $historyPrice = $lastPrices->get($line->item_id);
                        $itemPrice = $line->item->last_purchase_price;
                        $prPrice = $line->unit_price;
                        
                        $finalPrice = $historyPrice ?: ($itemPrice ?: ($prPrice ?: 0));
                        
                        return [
                            'item_id' => $line->item_id,
                            'qty' => $line->qty,
                            'unit_price' => $finalPrice,
                            'discount' => 0,
                            'notes' => $line->notes,
                        ];
                    })->all(),
                ]);

                $purchase_request->lines()
                    ->whereIn('id', $supplierLines->pluck('id'))
                    ->update([
                        'supplier_id' => (int) $supplierId,
                        'purchase_order_id' => $order->id,
                        'converted_at' => now(),
                    ]);

                $orders->push($order);
            }

            $purchase_request->update([
                'status' => 'converted',
                'converted_to_po_id' => $orders->first()->id,
                'converted_at' => now(),
            ]);

            return $orders;
        }, 3);

        return redirect()
            ->route('purchasing.purchase_requests.show', $purchase_request->id)
            ->with('success', $createdOrders->count() . ' PO draft berhasil dibuat: ' . $createdOrders->pluck('code')->join(', ') . '.');
    }

    // =========================================================
    // APPROVE (PR-C)
    // =========================================================

    public function approve(Request $request, PurchaseRequest $purchase_request)
    {
        abort_unless(
            $request->user()->isOwner()
                || in_array($request->user()->role, ['admin'], true)
                || $request->user()->isDeveloper(),
            403,
            'Hanya owner atau admin yang bisa approve PR.'
        );

        if (!$purchase_request->isDraft()) {
            return redirect()
                ->route('purchasing.purchase_requests.show', $purchase_request->id)
                ->with('error', 'Hanya PR berstatus draft yang bisa di-approve.');
        }

        $purchase_request->update([
            'status'      => 'approved',
            'approved_by' => (int) $request->user()->id,
        ]);

        return redirect()
            ->route('purchasing.purchase_requests.show', $purchase_request->id)
            ->with('success', "PR {$purchase_request->code} berhasil di-approve.");
    }

    // =========================================================
    // REJECT (PR-C)
    // =========================================================

    public function reject(Request $request, PurchaseRequest $purchase_request)
    {
        abort_unless(
            $request->user()->isOwner()
                || in_array($request->user()->role, ['admin'], true)
                || $request->user()->isDeveloper(),
            403,
            'Hanya owner atau admin yang bisa reject PR.'
        );

        if (!$purchase_request->isDraft()) {
            return redirect()
                ->route('purchasing.purchase_requests.show', $purchase_request->id)
                ->with('error', 'Hanya PR berstatus draft yang bisa di-reject.');
        }

        $purchase_request->update([
            'status'      => 'rejected',
            'rejected_by' => (int) $request->user()->id,
        ]);

        return redirect()
            ->route('purchasing.purchase_requests.show', $purchase_request->id)
            ->with('success', "PR {$purchase_request->code} telah ditolak.");
    }

    // =========================================================
    // PRIVATE HELPERS
    // =========================================================

    protected function canSeeMoney(?Request $request = null): bool
    {
        $user = $request?->user() ?: auth()->user();
        return $user && method_exists($user, 'isOwner') && $user->isOwner();
    }

    protected function canSeePurchaseRequestMoney(?Request $request = null): bool
    {
        $user = $request?->user() ?: auth()->user();

        if (!$user) {
            return false;
        }

        $role = strtolower((string) ($user->role ?? ''));

        return $user->isOwner() || in_array($role, ['admin', 'accounting', 'nta'], true);
    }

    protected function purchaseRequestItems(): Collection
    {
        $select = [
            'id',
            'code',
            'name',
            'unit',
            'item_category_id',
            'last_purchase_price',
        ];

        if (Schema::hasColumn('items', 'product_category_id')) {
            $select[] = 'product_category_id';
        }

        return Item::query()
            ->with([
                'category:id,name,kind',
                'productCategory:id,name,kind',
            ])
            ->where('active', true)
            ->orderBy('code')
            ->get($select);
    }

    /**
     * PR draft atau approved yang belum pernah masuk PO masih boleh dipilih supplier.
     */
    protected function canAllocateSuppliers(PurchaseRequest $purchase_request): bool
    {
        if (!in_array($purchase_request->status, ['draft', 'approved'], true)) {
            return false;
        }

        return ! $purchase_request->lines()->whereNotNull('purchase_order_id')->exists();
    }

    /**
     * Isi harga est. dari harga terakhir item jika baris belum punya harga.
     */
    protected function applyDefaultPurchaseRequestLinePrices(array $lines, ?Collection $items = null): array
    {
        $items ??= $this->purchaseRequestItems();

        $itemIds = collect($lines)
            ->pluck('item_id')
            ->filter(fn ($itemId) => (int) $itemId > 0)
            ->map(fn ($itemId) => (int) $itemId)
            ->unique()
            ->values()
            ->all();

        if (empty($itemIds)) {
            return $lines;
        }

        $itemsById = $items->keyBy('id');

        foreach ($lines as &$line) {
            $itemId = (int) ($line['item_id'] ?? 0);
            $currentPrice = (float) ($line['unit_price'] ?? 0);

            if ($itemId <= 0 || $currentPrice > 0) {
                continue;
            }

            $item = $itemsById->get($itemId);
            $fallbackPrice = $this->suggestPurchaseRequestItemPrice($item, $items);

            if ($fallbackPrice !== null && $fallbackPrice > 0) {
                $line['unit_price'] = $fallbackPrice;
            }
        }
        unset($line);

        return $lines;
    }

    protected function suggestPurchaseRequestItemPrice(?Item $targetItem, Collection $items): ?float
    {
        if (!$targetItem) {
            return null;
        }

        $directPrice = (float) ($targetItem->last_purchase_price ?? 0);
        if ($directPrice > 0) {
            return $directPrice;
        }

        $targetSignature = $this->itemVariantSignature($targetItem);

        $bestItem = $items
            ->filter(fn (Item $candidate) => (int) $candidate->id !== (int) $targetItem->id)
            ->filter(fn (Item $candidate) => (float) ($candidate->last_purchase_price ?? 0) > 0)
            ->map(function (Item $candidate) use ($targetItem, $targetSignature) {
                return [
                    'item' => $candidate,
                    'score' => $this->itemPriceMatchScore($targetItem, $candidate, $targetSignature),
                ];
            })
            ->sortByDesc('score')
            ->first();

        if (!$bestItem || (int) ($bestItem['score'] ?? 0) <= 0) {
            return null;
        }

        return (float) ($bestItem['item']->last_purchase_price ?? 0);
    }

    protected function itemPriceMatchScore(Item $targetItem, Item $candidate, ?array $targetSignature = null): int
    {
        $targetSignature ??= $this->itemVariantSignature($targetItem);
        $candidateSignature = $this->itemVariantSignature($candidate);

        $score = 0;

        if ((int) $targetItem->item_category_id > 0 && (int) $targetItem->item_category_id === (int) $candidate->item_category_id) {
            $score += 80;
        }

        if ((int) $targetItem->product_category_id > 0 && (int) $targetItem->product_category_id === (int) $candidate->product_category_id) {
            $score += 60;
        }

        $targetKind = $this->itemCategoryKind($targetItem);
        $candidateKind = $this->itemCategoryKind($candidate);
        if ($targetKind !== null && $targetKind === $candidateKind) {
            $score += 40;
        }

        if (!empty($targetSignature['color']) && !empty($candidateSignature['color']) && $targetSignature['color'] === $candidateSignature['color']) {
            $score += 30;
        }

        if (!empty($targetSignature['size']) && !empty($candidateSignature['size']) && $targetSignature['size'] === $candidateSignature['size']) {
            $score += 30;
        }

        if (!empty($targetSignature['base']) && !empty($candidateSignature['base']) && $targetSignature['base'] === $candidateSignature['base']) {
            $score += 15;
        }

        return $score;
    }

    protected function itemCategoryKind(Item $item): ?string
    {
        if ($item->relationLoaded('category') && $item->category?->kind) {
            return strtolower((string) $item->category->kind);
        }

        if ($item->relationLoaded('productCategory') && $item->productCategory?->kind) {
            return strtolower((string) $item->productCategory->kind);
        }

        return null;
    }

    protected function itemVariantSignature(Item $item): array
    {
        $source = trim(((string) ($item->code ?? '')) . ' ' . ((string) ($item->name ?? '')));
        $normalized = Str::of($source)
            ->lower()
            ->replace(['-', '/', '_'], ' ')
            ->squish()
            ->toString();

        $colorMap = [
            'hitam' => 'hitam',
            'black' => 'hitam',
            'blk' => 'hitam',
            'navy' => 'navy',
            'biru' => 'biru',
            'blue' => 'biru',
            'blu' => 'biru',
            'putih' => 'putih',
            'white' => 'putih',
            'wht' => 'putih',
            'abu' => 'abu',
            'grey' => 'abu',
            'gray' => 'abu',
            'gry' => 'abu',
            'cream' => 'cream',
            'krem' => 'cream',
            'crm' => 'cream',
            'maroon' => 'maroon',
            'merah' => 'merah',
            'red' => 'merah',
            'kuning' => 'kuning',
            'yellow' => 'kuning',
            'yel' => 'kuning',
            'orange' => 'orange',
            'oren' => 'orange',
            'hijau' => 'hijau',
            'green' => 'hijau',
            'grn' => 'hijau',
            'olive' => 'olive',
            'army' => 'army',
            'khaki' => 'khaki',
            'coklat' => 'coklat',
            'brown' => 'coklat',
            'mocca' => 'coklat',
            'pink' => 'pink',
            'ungu' => 'ungu',
            'purple' => 'ungu',
            'lilac' => 'ungu',
            'taro' => 'ungu',
        ];

        $sizePatterns = [
            '/\b(10xl|9xl|8xl|7xl|6xl|5xl|4xl|3xl|xxxl|xxl|xl|xs|s|m|l)\b/i',
            '/\b\d{1,2}l\b/i',
        ];

        $tokens = preg_split('/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $color = null;

        foreach ($tokens as $token) {
            if (isset($colorMap[$token])) {
                $color = $colorMap[$token];
                break;
            }
        }

        if ($color === null) {
            foreach (array_keys($colorMap) as $needle) {
                if (preg_match('/(?:^|\s)' . preg_quote($needle, '/') . '(?:\s|$)/i', $normalized)) {
                    $color = $colorMap[$needle];
                    break;
                }
            }
        }

        $size = null;
        foreach ($sizePatterns as $pattern) {
            if (preg_match($pattern, $normalized, $match)) {
                $size = strtolower((string) $match[1]);
                break;
            }
        }

        $base = $normalized;
        if ($color !== null) {
            foreach (array_keys($colorMap) as $needle) {
                $base = preg_replace('/(?:^|\s)' . preg_quote($needle, '/') . '(?:\s|$)/i', ' ', $base);
            }
        }

        if ($size !== null) {
            $base = preg_replace('/\b(?:10xl|9xl|8xl|7xl|6xl|5xl|4xl|3xl|xxxl|xxl|xl|xs|s|m|l|\d{1,2}l)\b/i', ' ', $base);
        }

        $base = Str::of($base)
            ->replace(['-', '/', '_'], ' ')
            ->squish()
            ->toString();

        return [
            'color' => $color,
            'size' => $size,
            'base' => $base,
        ];
    }
}
