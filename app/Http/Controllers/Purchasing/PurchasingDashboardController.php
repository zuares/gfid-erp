<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseRequest;
use App\Models\PurchasePayment;
use App\Models\PurchaseReturn;
use App\Models\SupplierInvoice;
use App\Services\Purchasing\MaterialShortageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PurchasingDashboardController extends Controller
{
    public function __construct(protected MaterialShortageService $shortages) {}

    public function index(Request $request)
    {
        $user    = $request->user();
        $role    = strtolower((string) ($user->role ?? ''));
        $isOwner = $user?->isOwner() ?? false;
        $isAdmin = $role === 'admin';

        abort_unless(
            $isOwner || in_array($role, ['admin', 'accounting', 'developer'], true),
            403, 'Akses ditolak.'
        );

        $canSeeMoney = $isOwner || in_array($role, ['admin', 'accounting', 'developer'], true);

        $today        = now()->toDateString();
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth   = now()->endOfMonth()->toDateString();

        $hasInvoiceTable = Schema::hasTable('supplier_invoices');
        $hasPrTable      = Schema::hasTable('purchase_requests');
        $hasReturnTable  = Schema::hasTable('purchase_returns');

        // ── Material Shortage ─────────────────────────────────────────
        $shortageRows      = $this->shortages->rows();
        $shortageCount     = $shortageRows->where('has_shortage', true)->count();
        $shortageItemCount = $shortageRows->count();

        // ── Purchase Request ──────────────────────────────────────────
        $prDraftCount = $hasPrTable ? PurchaseRequest::where('status', 'draft')->count() : 0;

        $prPendingApproval = $hasPrTable
            ? PurchaseRequest::where('status', 'draft')->count()
            : 0;

        $prApprovedNotConvertedCount = $hasPrTable
            ? PurchaseRequest::where('status', 'approved')
                ->whereNull('converted_to_po_id')
                ->count()
            : 0;

        $prApprovedNotConvertedList = $hasPrTable
            ? PurchaseRequest::where('status', 'approved')
                ->whereNull('converted_to_po_id')
                ->with(['supplier', 'requestedBy', 'lines'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
            : collect();

        // ── Purchase Order ────────────────────────────────────────────
        $poDraftCount = PurchaseOrder::where('status', 'draft')->count();

        $poApprovedNotReceivedCount = PurchaseOrder::where('status', 'approved')
            ->where(fn ($q) => $q->whereNull('received_status')->orWhere('received_status', 'not_received'))
            ->count();

        $poPartialCount = PurchaseOrder::where('status', 'approved')
            ->where('received_status', 'partial')
            ->count();

        $poBelumTerimaList = PurchaseOrder::where('status', 'approved')
            ->where(fn ($q) => $q
                ->whereNull('received_status')
                ->orWhereIn('received_status', ['not_received', 'partial'])
            )
            ->with(['supplier', 'lines'])
            ->orderByDesc('date')
            ->limit(10)
            ->get();

        // ── GRN bulan ini ─────────────────────────────────────────────
        $grnThisMonth = PurchaseReceipt::whereBetween('date', [$startOfMonth, $endOfMonth])->count();

        // ── Purchase Return ───────────────────────────────────────────
        $returnDraftCount = $hasReturnTable
            ? PurchaseReturn::whereNull('posted_at')->whereNull('voided_at')->count()
            : 0;

        // ── Invoice ───────────────────────────────────────────────────
        if ($hasInvoiceTable) {
            $poFullyNoInvoiceCount = PurchaseOrder::where('status', 'approved')
                ->where('received_status', 'fully_received')
                ->whereNull('closed_at')
                ->whereDoesntHave('supplierInvoices', fn ($q) => $q->whereNotIn('status', ['void']))
                ->count();

            $poFullyNoInvoiceList = PurchaseOrder::where('status', 'approved')
                ->where('received_status', 'fully_received')
                ->whereNull('closed_at')
                ->whereDoesntHave('supplierInvoices', fn ($q) => $q->whereNotIn('status', ['void']))
                ->with('supplier')
                ->orderByDesc('date')
                ->limit(10)
                ->get();

            $invOutstandingCount = SupplierInvoice::whereIn('status', ['posted', 'partial_paid'])->count();
            $invOutstandingTotal = (float) SupplierInvoice::whereIn('status', ['posted', 'partial_paid'])
                ->selectRaw('COALESCE(SUM(total_amount - paid_amount), 0) as outstanding')
                ->value('outstanding');

            $invOverdueCount = SupplierInvoice::whereIn('status', ['posted', 'partial_paid'])
                ->whereNotNull('due_date')
                ->where('due_date', '<', $today)
                ->count();

            $invOverdueTotal = (float) SupplierInvoice::whereIn('status', ['posted', 'partial_paid'])
                ->whereNotNull('due_date')
                ->where('due_date', '<', $today)
                ->selectRaw('COALESCE(SUM(total_amount - paid_amount), 0) as outstanding')
                ->value('outstanding');

            $invOutstandingList = SupplierInvoice::whereIn('status', ['posted', 'partial_paid'])
                ->with(['supplier', 'purchaseOrder:id,code'])
                ->orderBy('due_date')
                ->orderByDesc('invoice_date')
                ->limit(10)
                ->get();

            // Upcoming due dates (7 hari ke depan)
            $invUpcoming = SupplierInvoice::whereIn('status', ['posted', 'partial_paid'])
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [$today, now()->addDays(7)->toDateString()])
                ->with('supplier')
                ->orderBy('due_date')
                ->limit(5)
                ->get();
        } else {
            $poFullyNoInvoiceCount = 0;
            $poFullyNoInvoiceList  = collect();
            $invOutstandingCount   = 0;
            $invOutstandingTotal   = 0.0;
            $invOverdueCount       = 0;
            $invOverdueTotal       = 0.0;
            $invOutstandingList    = collect();
            $invUpcoming           = collect();
        }

        // ── Pembayaran ────────────────────────────────────────────────
        $hasPaymentTable = Schema::hasTable('purchase_payments');

        $payThisMonthTotal = $hasPaymentTable
            ? (float) PurchasePayment::whereNull('voided_at')
                ->whereBetween('created_at', [$startOfMonth . ' 00:00:00', $endOfMonth . ' 23:59:59'])
                ->sum('amount')
            : 0.0;

        $recentPayments = $hasPaymentTable
            ? PurchasePayment::with(['purchaseOrder.supplier'])
                ->whereNull('voided_at')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get()
            : collect();

        // ── PO Siap / Belum Bisa Close ────────────────────────────────
        $poReadyCloseQuery = PurchaseOrder::where('status', 'approved')
            ->where('received_status', 'fully_received')
            ->where('payment_status', 'paid')
            ->whereNull('closed_at');
        if ($hasInvoiceTable) {
            $poReadyCloseQuery->whereDoesntHave('supplierInvoices', fn ($q) =>
                $q->whereIn('status', ['draft', 'posted', 'partial_paid'])
            );
        }
        $poReadyCloseCount = (clone $poReadyCloseQuery)->count();
        $poReadyCloseList  = (clone $poReadyCloseQuery)
            ->with(['supplier', 'supplierInvoices'])
            ->orderByDesc('date')->limit(10)->get();

        $poClosedMonthCount = PurchaseOrder::whereNotNull('closed_at')
            ->whereBetween('closed_at', [$startOfMonth . ' 00:00:00', $endOfMonth . ' 23:59:59'])
            ->count();

        $poNotReadyCloseList = PurchaseOrder::where('status', 'approved')
            ->whereNull('closed_at')
            ->where(fn ($q) => $q
                ->where('received_status', '!=', 'fully_received')
                ->orWhere('payment_status', '!=', 'paid')
            )
            ->with(['supplier', 'supplierInvoices'])
            ->orderByDesc('date')->limit(15)->get()
            ->map(function ($po) use ($hasInvoiceTable) {
                $blockers = [];
                if (($po->received_status ?? 'not_received') !== 'fully_received') {
                    $blockers[] = match ($po->received_status ?? 'not_received') {
                        'partial' => 'Terima sebagian',
                        default   => 'Belum ada GRN',
                    };
                }
                if (($po->payment_status ?? 'unpaid') !== 'paid') {
                    $blockers[] = match ($po->payment_status ?? 'unpaid') {
                        'partial' => 'Bayar sebagian',
                        default   => 'Belum bayar',
                    };
                }
                if ($hasInvoiceTable) {
                    $open = $po->supplierInvoices->whereIn('status', ['draft', 'posted', 'partial_paid'])->count();
                    if ($open > 0) $blockers[] = "{$open} invoice belum lunas";
                }
                $po->_blockers = $blockers;
                return $po;
            });
        $poNotReadyCloseCount = $poNotReadyCloseList->count();

        // ── Recent Activity ───────────────────────────────────────────
        $recentPr = $hasPrTable
            ? PurchaseRequest::with(['requestedBy'])->orderByDesc('created_at')->limit(5)->get()
            : collect();

        $recentPo = PurchaseOrder::with('supplier')->orderByDesc('created_at')->limit(5)->get();

        $recentGrn = PurchaseReceipt::with('supplier')->orderByDesc('created_at')->limit(5)->get();

        $recentInvoices = $hasInvoiceTable
            ? SupplierInvoice::with('supplier')->orderByDesc('created_at')->limit(5)->get()
            : collect();

        return view('purchasing.dashboard', compact(
            'canSeeMoney', 'isOwner', 'isAdmin', 'today',
            'hasInvoiceTable', 'hasPrTable', 'hasReturnTable', 'hasPaymentTable',
            // Material shortage
            'shortageCount', 'shortageItemCount',
            // PR
            'prDraftCount', 'prPendingApproval', 'prApprovedNotConvertedCount', 'prApprovedNotConvertedList',
            // PO
            'poDraftCount', 'poApprovedNotReceivedCount', 'poPartialCount', 'poBelumTerimaList',
            // GRN
            'grnThisMonth',
            // Return
            'returnDraftCount',
            // Invoice
            'poFullyNoInvoiceCount', 'poFullyNoInvoiceList',
            'invOutstandingCount', 'invOutstandingTotal',
            'invOverdueCount', 'invOverdueTotal',
            'invOutstandingList', 'invUpcoming',
            // Payment
            'payThisMonthTotal', 'recentPayments',
            // Close
            'poReadyCloseCount', 'poReadyCloseList',
            'poNotReadyCloseCount', 'poNotReadyCloseList',
            'poClosedMonthCount',
            // Activity
            'recentPr', 'recentPo', 'recentGrn', 'recentInvoices',
        ));
    }
}
