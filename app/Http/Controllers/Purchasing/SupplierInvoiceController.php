<?php

namespace App\Http\Controllers\Purchasing;

use App\Helpers\CodeGenerator;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\PaymentMethod;
use App\Models\PurchaseOrder;
use App\Models\PurchasePayment;
use App\Models\SupplierInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
                    ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$s}%")
                        ->orWhere('code', 'like', "%{$s}%"));
            });
        }

        $invoices = $query->paginate(20)->withQueryString();

        $tolerance = PurchaseOrder::paymentRoundingTolerance();
        $summary = (object) [
            'total' => SupplierInvoice::count(),
            'draft_count' => SupplierInvoice::where('status', 'draft')->count(),
            'posted_count' => SupplierInvoice::whereIn('status', ['posted', 'partial_paid', 'paid'])->count(),
            'unpaid_total' => SupplierInvoice::whereIn('status', ['posted', 'partial_paid'])
                ->whereRaw('ROUND(total_amount - paid_amount, 2) > ?', [$tolerance])
                ->selectRaw('COALESCE(SUM(total_amount - paid_amount), 0) as total')->value('total') ?? 0,
        ];

        $suppliers = \App\Models\Supplier::orderBy('name')->get(['id', 'name', 'code']);
        $hasReceivableOrders = PurchaseOrder::query()
            ->whereHas('purchaseReceipts', fn ($query) => $this->scopeNonReplacementPostedGrn($query))
            ->exists();

        return view('purchasing.supplier_invoices.index', compact(
            'invoices', 'summary', 'suppliers', 'hasReceivableOrders'
        ));
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

            if ($order && $this->hasPostedGrn($order)) {
                $basis = $this->invoiceBasis($order);
                $grnPostedTotal = $basis['grn_posted_total'];
                $returnTotal = $basis['return_total'];
            } elseif ($order) {
                return redirect()
                    ->route('purchasing.supplier_invoices.index')
                    ->with('error', 'Invoice Supplier baru hanya dapat dibuat dari PO yang memiliki GRN Posted.');
            }
        }

        $suppliers = \App\Models\Supplier::orderBy('name')->get(['id', 'name', 'code']);
        $receivableOrders = PurchaseOrder::where(function ($query) {
            $query->where('status', 'approved');
            if (! config('purchasing.po_approval_enabled', false)) {
                $query->orWhere('status', 'draft');
            }
        })
            ->whereHas('purchaseReceipts', fn ($query) => $this->scopeNonReplacementPostedGrn($query))
            ->with('supplier')
            ->orderByDesc('date')
            ->limit(100)
            ->get(['id', 'code', 'supplier_id', 'grand_total', 'date']);

        return view('purchasing.supplier_invoices.create', compact(
            'order', 'grnPostedTotal', 'returnTotal', 'suppliers', 'receivableOrders'
        ));
    }

    // =========================================================
    // STORE
    // =========================================================

    public function store(Request $request)
    {
        $this->ensureCanAccess($request);

        $data = $request->validate([
            'purchase_order_id' => ['required', 'integer', 'exists:purchase_orders,id'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'supplier_invoice_ref' => ['nullable', 'string', 'max:100'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $order = PurchaseOrder::query()->find((int) $data['purchase_order_id']);

        if (! $order || (int) $order->supplier_id !== (int) $data['supplier_id']) {
            throw ValidationException::withMessages([
                'purchase_order_id' => 'Supplier invoice harus memakai PO dan supplier yang sama.',
            ]);
        }

        if ($order->status === 'cancelled') {
            throw ValidationException::withMessages([
                'purchase_order_id' => 'PO yang sudah dibatalkan tidak dapat dibuatkan supplier invoice.',
            ]);
        }

        $basis = $this->invoiceBasis($order);
        if ($basis['grn_posted_total'] <= PurchaseOrder::paymentRoundingTolerance()) {
            throw ValidationException::withMessages([
                'purchase_order_id' => 'Invoice Supplier hanya dapat dibuat setelah ada GRN Posted.',
            ]);
        }

        // Nilai invoice selalu bersumber dari GRN posted, bukan angka dari form.
        $subtotal = round($basis['grn_posted_total'], 2);
        $discountAmount = 0.0;
        $returnDeduction = round(min($basis['return_total'], $subtotal), 2);
        $totalAmount = max(0, round($subtotal - $returnDeduction, 2));

        if ($totalAmount <= PurchaseOrder::paymentRoundingTolerance()) {
            throw ValidationException::withMessages([
                'purchase_order_id' => 'Nilai GRN Posted sudah tertutup retur; invoice tidak dapat dibuat dengan total nol.',
            ]);
        }

        // Generate invoice_no
        $suppCode = DB::table('suppliers')
            ->where('id', (int) $data['supplier_id'])
            ->value('code');
        $prefix = $suppCode ? 'INV-'.strtoupper($suppCode) : 'INV';
        $invoiceNo = CodeGenerator::make($prefix);

        $invoice = SupplierInvoice::create([
            'invoice_no' => $invoiceNo,
            'supplier_invoice_ref' => $data['supplier_invoice_ref'] ?? null,
            'supplier_id' => (int) $data['supplier_id'],
            'purchase_order_id' => (int) $data['purchase_order_id'],
            'invoice_date' => $data['invoice_date'],
            'due_date' => $data['due_date'] ?? null,
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'return_deduction_amount' => $returnDeduction,
            'total_amount' => $totalAmount,
            'paid_amount' => 0,
            'status' => 'draft',
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()?->id,
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

        $paymentRows = PurchasePayment::query()
            ->with(['paymentMethod', 'cashAccount', 'createdBy', 'voidedBy'])
            ->where('supplier_invoice_id', $supplierInvoice->id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $paymentMethods = PaymentMethod::query()
            ->where('is_active', true)
            ->whereIn('mode', ['cash', 'transfer'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $cashAccounts = Account::query()
            ->where('is_active', true)
            ->where('is_cash', true)
            ->whereIn('code', ['1101', '1111', '1112', '1113', '1114'])
            ->orderBy('code')
            ->get();

        $order = $supplierInvoice->purchaseOrder;
        $grnPostedTotal = 0.0;
        $returnTotal = 0.0;
        $grnCount = 0;
        $poApOutstanding = 0.0;

        if ($order) {
            $grnPostedTotal = (float) $order->purchaseReceipts
                ->where('status', 'posted')
                ->where('is_replacement', false)
                ->sum('grand_total');

            $grnCount = $order->purchaseReceipts
                ->where('status', 'posted')
                ->where('is_replacement', false)
                ->count();

            $returnTotal = (float) DB::table('purchase_returns as pr')
                ->join('purchase_receipts as rec', 'rec.id', '=', 'pr.purchase_receipt_id')
                ->where('rec.purchase_order_id', $order->id)
                ->where('pr.status', 'posted')
                ->whereNull('pr.voided_at')
                ->where(function ($q) {
                    $q->whereNull('pr.resolution_type')
                        ->orWhere('pr.resolution_type', '!=', 'replacement');
                })
                ->sum('pr.total');

            $poPaidTotal = (float) PurchasePayment::query()
                ->where('purchase_order_id', $order->id)
                ->whereNull('voided_at')
                ->where('type', 'payment')
                ->sum('amount');
            $poDpAppliedTotal = (float) PurchasePayment::query()
                ->where('purchase_order_id', $order->id)
                ->whereNull('voided_at')
                ->where('type', 'dp_apply')
                ->sum('amount');
            $poApOutstanding = PurchaseOrder::normalizePaymentRemainder(
                $grnPostedTotal - $returnTotal - $poPaidTotal - $poDpAppliedTotal
            );
        }

        $paymentAvailable = min($supplierInvoice->outstanding(), $poApOutstanding);

        return view('purchasing.supplier_invoices.show', compact(
            'supplierInvoice', 'order', 'grnPostedTotal', 'returnTotal', 'grnCount',
            'paymentRows', 'paymentMethods', 'cashAccounts', 'poApOutstanding', 'paymentAvailable'
        ));
    }

    // =========================================================
    // POST
    // =========================================================

    public function post(Request $request, SupplierInvoice $supplierInvoice)
    {
        $this->ensureCanAccess($request);

        if (! $supplierInvoice->isDraft()) {
            return back()->with('error', 'Hanya faktur draft yang bisa di-post.');
        }

        if ((float) $supplierInvoice->total_amount <= 0) {
            return back()->with('error', 'Total faktur harus > 0 sebelum di-post.');
        }

        // 3-way control: PO -> GRN -> Supplier Invoice.
        // Invoice tidak membuat jurnal AP baru; AP sudah berasal dari GRN
        // posted. Karena itu invoice hanya boleh dipost sebagai dokumen
        // verifikasi setelah ada penerimaan yang menjadi sumber hutang.
        if (! $supplierInvoice->purchase_order_id) {
            return back()->with('error', 'Invoice Supplier wajib memiliki PO dengan GRN Posted.');
        }

        $order = PurchaseOrder::query()
            ->with('supplier')
            ->find((int) $supplierInvoice->purchase_order_id);

        if (! $order || (int) $order->supplier_id !== (int) $supplierInvoice->supplier_id) {
            return back()->with('error', 'Supplier invoice tidak cocok dengan supplier pada PO.');
        }

        $basis = $this->invoiceBasis($order);
        if ($basis['grn_posted_total'] <= PurchaseOrder::paymentRoundingTolerance()) {
            return back()->with('error', 'Faktur supplier belum dapat di-post karena belum ada GRN Posted.');
        }

        $invoiceTotal = round((float) $supplierInvoice->total_amount, 2);
        $maxInvoiceTotal = max(0, round($basis['grn_posted_total'] - $basis['return_total'], 2));
        if ($invoiceTotal > $maxInvoiceTotal + PurchaseOrder::paymentRoundingTolerance()) {
            return back()->with(
                'error',
                'Total faktur melebihi nilai GRN Posted setelah retur ('.rupiah($maxInvoiceTotal).').'
            );
        }

        $supplierInvoice->update([
            'status' => 'posted',
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
            'status' => 'void',
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

    // =========================================================
    // SET DEDUCTION — legacy route retained for compatibility.
    // Nilai retur sekarang selalu dihitung dari retur Posted.
    // Route: POST /purchasing/supplier-invoices/{id}/set-deduction
    //        name: purchasing.supplier_invoices.set_deduction
    // Middleware: role:owner,admin (accounting dijaga di view via canSetDeduction)
    // =========================================================

    public function setDeduction(Request $request, SupplierInvoice $supplierInvoice)
    {
        $this->ensureCanAccess($request);

        if ($supplierInvoice->isVoid()) {
            return back()->with('error', 'Invoice sudah void, tidak bisa diubah.');
        }

        if ($supplierInvoice->isPaid()) {
            return back()->with('error', 'Invoice sudah lunas, tidak bisa mengubah potongan.');
        }

        return back()->with('error', 'Potongan retur invoice dihitung otomatis dari retur yang sudah Posted.');
    }

    protected function scopeNonReplacementPostedGrn($query): void
    {
        $query->where('status', 'posted')
            ->where(function ($query) {
                $query->whereNull('is_replacement')
                    ->orWhere('is_replacement', false);
            });
    }

    protected function hasPostedGrn(PurchaseOrder $order): bool
    {
        return $order->purchaseReceipts()
            ->where(fn ($query) => $this->scopeNonReplacementPostedGrn($query))
            ->exists();
    }

    protected function invoiceBasis(PurchaseOrder $order): array
    {
        $grnPostedTotal = (float) $order->purchaseReceipts()
            ->where(fn ($query) => $this->scopeNonReplacementPostedGrn($query))
            ->sum('grand_total');

        $returnTotal = (float) DB::table('purchase_returns as pr')
            ->join('purchase_receipts as rec', 'rec.id', '=', 'pr.purchase_receipt_id')
            ->where('rec.purchase_order_id', $order->id)
            ->where('pr.status', 'posted')
            ->whereNull('pr.voided_at')
            ->where(function ($query) {
                $query->whereNull('pr.resolution_type')
                    ->orWhere('pr.resolution_type', '!=', 'replacement');
            })
            ->sum('pr.total');

        return [
            'grn_posted_total' => round($grnPostedTotal, 2),
            'return_total' => round($returnTotal, 2),
        ];
    }
}
