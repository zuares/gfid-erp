<?php

namespace App\Http\Controllers\Purchasing;

use App\Helpers\CodeGenerator;
use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\SupplierInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierInvoiceController extends Controller
{
    // =========================================================
    // INDEX
    // =========================================================

    public function index(Request $request)
    {
        $this->ensureCanAccess($request);

        $query = SupplierInvoice::query()
            ->with(['supplier', 'purchaseOrder', 'createdBy'])
            ->orderByDesc('invoice_date')
            ->orderByDesc('id');

        // Filter: supplier
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', (int) $request->supplier_id);
        }

        // Filter: status
        $status = (string) $request->input('status', '');
        if (in_array($status, ['draft', 'posted', 'partial_paid', 'paid', 'void'], true)) {
            $query->where('status', $status);
        }

        // Filter: search (invoice_no / supplier)
        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('invoice_no', 'like', "%{$s}%")
                  ->orWhere('supplier_invoice_ref', 'like', "%{$s}%")
                  ->orWhereHas('supplier', fn($sq) => $sq->where('name', 'like', "%{$s}%")
                      ->orWhere('code', 'like', "%{$s}%"));
            });
        }

        $invoices = $query->paginate(20)->withQueryString();

        $summary = (object) [
            'total'        => SupplierInvoice::count(),
            'draft_count'  => SupplierInvoice::where('status', 'draft')->count(),
            'posted_count' => SupplierInvoice::whereIn('status', ['posted', 'partial_paid', 'paid'])->count(),
            'unpaid_total' => SupplierInvoice::whereIn('status', ['posted', 'partial_paid'])
                ->selectRaw('SUM(total_amount - paid_amount) as total')->value('total') ?? 0,
        ];

        $suppliers = \App\Models\Supplier::orderBy('name')->get(['id', 'name', 'code']);

        return view('purchasing.supplier_invoices.index', compact('invoices', 'summary', 'suppliers'));
    }

    // =========================================================
    // CREATE (dari PO)
    // =========================================================

    public function create(Request $request)
    {
        $this->ensureCanAccess($request);

        $poId = $request->input('purchase_order_id');
        $order = null;
        $grnPostedTotal = 0.0;
        $returnTotal = 0.0;

        if ($poId) {
            $order = PurchaseOrder::with([
                'supplier',
                'purchaseReceipts.lines',
                'lines',
            ])->find((int) $poId);

            if ($order) {
                // Total GRN posted
                $grnPostedTotal = (float) $order->purchaseReceipts
                    ->where('status', 'posted')
                    ->sum('grand_total');

                // Total return posted dari GRN milik PO ini
                $returnTotal = (float) DB::table('purchase_returns as pr')
                    ->join('purchase_receipts as rec', 'rec.id', '=', 'pr.purchase_receipt_id')
                    ->where('rec.purchase_order_id', $order->id)
                    ->where('pr.status', 'posted')
                    ->whereNull('pr.voided_at')
                    ->sum('pr.total');
            }
        }

        $suppliers = \App\Models\Supplier::orderBy('name')->get(['id', 'name', 'code']);
        $approvedOrders = PurchaseOrder::where('status', 'approved')
            ->with('supplier')
            ->orderByDesc('date')
            ->limit(100)
            ->get(['id', 'code', 'supplier_id', 'grand_total', 'date']);

        return view('purchasing.supplier_invoices.create', compact(
            'order', 'grnPostedTotal', 'returnTotal', 'suppliers', 'approvedOrders'
        ));
    }

    // =========================================================
    // STORE
    // =========================================================

    public function store(Request $request)
    {
        $this->ensureCanAccess($request);

        $data = $request->validate([
            'purchase_order_id'      => ['nullable', 'integer', 'exists:purchase_orders,id'],
            'supplier_id'            => ['required', 'integer', 'exists:suppliers,id'],
            'supplier_invoice_ref'   => ['nullable', 'string', 'max:100'],
            'invoice_date'           => ['required', 'date'],
            'due_date'               => ['nullable', 'date'],
            'subtotal'               => ['nullable', 'string'],
            'discount_amount'        => ['nullable', 'string'],
            'return_deduction_amount'=> ['nullable', 'string'],
            'notes'                  => ['nullable', 'string'],
        ]);

        $subtotal         = $this->num($data['subtotal'] ?? 0);
        $discountAmount   = $this->num($data['discount_amount'] ?? 0);
        $returnDeduction  = $this->num($data['return_deduction_amount'] ?? 0);
        $totalAmount      = max(0, round($subtotal - $discountAmount - $returnDeduction, 2));

        // Generate invoice_no
        $suppCode = DB::table('suppliers')
            ->where('id', (int) $data['supplier_id'])
            ->value('code');
        $prefix = $suppCode ? 'INV-' . strtoupper($suppCode) : 'INV';
        $invoiceNo = CodeGenerator::make($prefix);

        $invoice = SupplierInvoice::create([
            'invoice_no'              => $invoiceNo,
            'supplier_invoice_ref'    => $data['supplier_invoice_ref'] ?? null,
            'supplier_id'             => (int) $data['supplier_id'],
            'purchase_order_id'       => !empty($data['purchase_order_id']) ? (int) $data['purchase_order_id'] : null,
            'invoice_date'            => $data['invoice_date'],
            'due_date'                => $data['due_date'] ?? null,
            'subtotal'                => $subtotal,
            'discount_amount'         => $discountAmount,
            'return_deduction_amount' => $returnDeduction,
            'total_amount'            => $totalAmount,
            'paid_amount'             => 0,
            'status'                  => 'draft',
            'notes'                   => $data['notes'] ?? null,
            'created_by'              => $request->user()?->id,
        ]);

        return redirect()
            ->route('purchasing.supplier_invoices.show', $invoice->id)
            ->with('success', "Faktur Supplier {$invoiceNo} berhasil dibuat.");
    }

    // =========================================================
    // SHOW
    // =========================================================

    public function show(Request $request, SupplierInvoice $supplierInvoice)
    {
        $this->ensureCanAccess($request);

        $supplierInvoice->load([
            'supplier',
            'purchaseOrder.purchaseReceipts',
            'purchaseOrder.lines',
            'createdBy',
            'postedBy',
            'voidedBy',
        ]);

        $order          = $supplierInvoice->purchaseOrder;
        $grnPostedTotal = 0.0;
        $returnTotal    = 0.0;
        $grnCount       = 0;

        if ($order) {
            $grnPostedTotal = (float) $order->purchaseReceipts
                ->where('status', 'posted')
                ->sum('grand_total');

            $grnCount = $order->purchaseReceipts->where('status', 'posted')->count();

            $returnTotal = (float) DB::table('purchase_returns as pr')
                ->join('purchase_receipts as rec', 'rec.id', '=', 'pr.purchase_receipt_id')
                ->where('rec.purchase_order_id', $order->id)
                ->where('pr.status', 'posted')
                ->whereNull('pr.voided_at')
                ->sum('pr.total');
        }

        return view('purchasing.supplier_invoices.show', compact(
            'supplierInvoice', 'order', 'grnPostedTotal', 'returnTotal', 'grnCount'
        ));
    }

    // =========================================================
    // POST
    // =========================================================

    public function post(Request $request, SupplierInvoice $supplierInvoice)
    {
        $this->ensureCanAccess($request);

        if (!$supplierInvoice->isDraft()) {
            return back()->with('error', 'Hanya faktur draft yang bisa di-post.');
        }

        if ((float) $supplierInvoice->total_amount <= 0) {
            return back()->with('error', 'Total faktur harus > 0 sebelum di-post.');
        }

        $supplierInvoice->update([
            'status'    => 'posted',
            'posted_at' => now(),
            'posted_by' => $request->user()?->id,
        ]);

        return back()->with('success', "Faktur {$supplierInvoice->invoice_no} berhasil di-post.");
    }

    // =========================================================
    // VOID
    // =========================================================

    public function void(Request $request, SupplierInvoice $supplierInvoice)
    {
        $this->ensureOwner($request);

        if ($supplierInvoice->isVoid()) {
            return back()->with('error', 'Faktur sudah void.');
        }

        if ($supplierInvoice->isPaid()) {
            return back()->with('error', 'Faktur sudah lunas, tidak bisa di-void. Hubungi owner.');
        }

        $supplierInvoice->update([
            'status'    => 'void',
            'voided_at' => now(),
            'voided_by' => $request->user()?->id,
        ]);

        return redirect()
            ->route('purchasing.supplier_invoices.index')
            ->with('success', "Faktur {$supplierInvoice->invoice_no} di-void.");
    }

    // =========================================================
    // INTERNAL HELPERS
    // =========================================================

    /**
     * Hanya owner + accounting yang boleh akses.
     * Gudang diblokir.
     */
    protected function ensureCanAccess(Request $request): void
    {
        $user = $request->user();
        $allowed = $user && (
            $user->isOwner() ||
            in_array($user->role ?? '', ['accounting', 'developer'], true)
        );
        abort_unless($allowed, 403, 'Hanya owner / accounting yang boleh mengakses Faktur Supplier.');
    }

    protected function ensureOwner(Request $request): void
    {
        abort_unless($request->user()?->isOwner(), 403, 'Hanya owner yang boleh melakukan aksi ini.');
    }

    protected function num($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        $v = trim((string) $value);
        $v = str_replace(' ', '', $v);
        if (strpos($v, ',') !== false) {
            $v = str_replace('.', '', $v);
            $v = str_replace(',', '.', $v);
            return (float) $v;
        }
        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $v)) {
            $v = str_replace('.', '', $v);
            return (float) $v;
        }
        return (float) $v;
    }
}
