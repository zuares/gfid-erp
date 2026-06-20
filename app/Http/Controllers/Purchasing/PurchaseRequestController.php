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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
        $suppliers   = Supplier::orderBy('name')->get(['id', 'name', 'code']);
        $items       = Item::where('active', true)->orderBy('code')->get(['id', 'code', 'name', 'unit']);
        $canSeeMoney = $this->canSeeMoney($request);
        $linesData   = [];

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
        if (!$this->canSeeMoney($request)) {
            foreach ($data['lines'] as &$line) {
                $line['unit_price'] = null;
            }
            unset($line);
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
        $items       = Item::where('active', true)->orderBy('code')->get(['id', 'code', 'name', 'unit']);
        $canSeeMoney = $this->canSeeMoney($request);
        $linesData   = $purchase_request->lines->toArray();

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

        if (!$this->canSeeMoney($request)) {
            foreach ($data['lines'] as &$line) {
                $line['unit_price'] = null;
            }
            unset($line);
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

        return redirect()
            ->route('purchasing.purchase_requests.show', $purchase_request->id)
            ->with('success', "PR {$purchase_request->code} berhasil diperbarui.");
    }

    // =========================================================
    // CONVERT → PO (PR-D)
    // =========================================================

    public function allocateSuppliers(Request $request, PurchaseRequest $purchase_request)
    {
        if (!$purchase_request->isConvertible()) {
            return redirect()
                ->route('purchasing.purchase_requests.show', $purchase_request->id)
                ->with('error', 'PR ini tidak dapat dibagi ke PO karena belum disetujui atau sudah pernah diproses.');
        }

        $purchase_request->load(['lines.item:id,code,name,unit,item_category_id', 'supplier:id,code,name']);
        $suppliers = Supplier::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
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
        $recommendedSuppliers = $purchase_request->lines->mapWithKeys(function ($line) use (
            $itemRecommendations,
            $categoryRecommendations
        ) {
            if ($itemRecommendation = $itemRecommendations->get($line->item_id)) {
                $itemRecommendation->source = 'item';
                return [$line->item_id => $itemRecommendation];
            }

            if ($categoryRecommendation = $categoryRecommendations->get($line->item?->item_category_id)) {
                $categoryRecommendation->source = 'category';
                return [$line->item_id => $categoryRecommendation];
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
        if (!$purchase_request->isConvertible()) {
            return redirect()
                ->route('purchasing.purchase_requests.show', $purchase_request->id)
                ->with('error', 'PR ini tidak dapat diproses karena belum disetujui atau sudah pernah dibuatkan PO.');
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

        $createdOrders = DB::transaction(function () use (
            $data,
            $purchase_request,
            $purchaseOrderService,
            $request
        ) {
            $lockedRequest = PurchaseRequest::query()
                ->whereKey($purchase_request->id)
                ->lockForUpdate()
                ->firstOrFail();
            $lines = $purchase_request->lines()->with('item')->lockForUpdate()->get();

            if ($lockedRequest->status !== 'approved' || $lines->contains(fn ($line) => $line->purchase_order_id !== null)) {
                abort(409, 'PR sudah diproses oleh pengguna lain. Muat ulang halaman.');
            }

            $orders = collect();
            $grouped = $lines->groupBy(fn ($line) => (int) $data['suppliers'][$line->id]);

            foreach ($grouped as $supplierId => $supplierLines) {
                $order = $purchaseOrderService->create([
                    'date' => now()->toDateString(),
                    'supplier_id' => (int) $supplierId,
                    'discount' => 0,
                    'tax_percent' => 0,
                    'shipping_cost' => 0,
                    'notes' => "Dibuat dari {$purchase_request->code}. Harga dilengkapi owner sebelum approval.",
                    'created_by' => (int) $request->user()->id,
                    'status' => 'draft',
                    'order_type' => 'material',
                    'purchase_request_id' => $purchase_request->id,
                    'lines' => $supplierLines->map(fn ($line) => [
                        'item_id' => $line->item_id,
                        'qty' => $line->qty,
                        'unit_price' => $line->unit_price ?? 0,
                        'discount' => 0,
                        'notes' => $line->notes,
                    ])->all(),
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
        });

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
}
