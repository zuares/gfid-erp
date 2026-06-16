<?php

namespace App\Http\Controllers\Purchasing;

use App\Helpers\CodeGenerator;
use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestLine;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseRequestController extends Controller
{
    // =========================================================
    // INDEX
    // =========================================================

    public function index(Request $request)
    {
        $query = PurchaseRequest::query()
            ->with(['supplier', 'requestedBy'])
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
                  );
            });
        }

        $prs = $query->paginate(20)->withQueryString();

        $summary = [
            'total'    => PurchaseRequest::count(),
            'draft'    => PurchaseRequest::where('status', 'draft')->count(),
            'approved' => PurchaseRequest::where('status', 'approved')->count(),
            'rejected' => PurchaseRequest::where('status', 'rejected')->count(),
        ];

        $suppliers = Supplier::orderBy('name')->get(['id', 'name', 'code']);

        return view('purchasing.purchase_requests.index', compact('prs', 'summary', 'suppliers'));
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
