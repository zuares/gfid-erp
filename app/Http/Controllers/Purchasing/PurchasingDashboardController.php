<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\PurchasePayment;
use App\Models\SupplierInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PurchasingDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user   = $request->user();
        $role   = strtolower((string) ($user->role ?? ''));
        $isOwner = $user?->isOwner() ?? false;
        $isAdmin = $role === 'admin';

        // Akses: owner, admin, accounting, developer
        abort_unless(
            $isOwner || in_array($role, ['admin', 'accounting', 'developer'], true),
            403,
            'Akses ditolak.'
        );

        $canSeeMoney = $isOwner || in_array($role, ['admin', 'accounting', 'developer'], true);

        $today        = now()->toDateString();
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth   = now()->endOfMonth()->toDateString();

        $hasInvoiceTable = Schema::hasTable('supplier_invoices');
        $hasPrTable      = Schema::hasTable('purchase_requests');

        // ══════════════════════════════════════════════════════════════
        // KPI 1 — PR Draft / Menunggu Submit
        // ══════════════════════════════════════════════════════════════
        $prDraftCount = $hasPrTable
            ? PurchaseRequest::where('status', 'draft')->count()
            : 0;

        // ══════════════════════════════════════════════════════════════
        // KPI 2 — PR Approved belum convert ke PO
        // ══════════════════════════════════════════════════════════════
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

        // ══════════════════════════════════════════════════════════════
        // KPI 3 — PO Approved belum diterima
        // ══════════════════════════════════════════════════════════════
        $poApprovedNotReceivedCount = PurchaseOrder::where('status', 'approved')
            ->where(fn($q) => $q->whereNull('received_status')->orWhere('received_status', 'not_received'))
            ->count();

        // ══════════════════════════════════════════════════════════════
        // KPI 4 — PO Partial Received
        // ══════════════════════════════════════════════════════════════
        $poPartialCount = PurchaseOrder::where('status', 'approved')
            ->where('received_status', 'partial')
            ->count();

        // Section B: PO Butuh Penerimaan (not_received + partial, merged)
        $poBelumTerimaList = PurchaseOrder::where('status', 'approved')
            ->where(fn($q) => $q
                ->whereNull('received_status')
                ->orWhereIn('received_status', ['not_received', 'partial'])
            )
            ->with(['supplier', 'lines'])
            ->orderByDesc('date')
            ->limit(10)
            ->get();

        // ══════════════════════════════════════════════════════════════
        // KPI 5 — PO Fully Received tapi belum ada Supplier Invoice
        // ══════════════════════════════════════════════════════════════
        if ($hasInvoiceTable) {
            $poFullyNoInvoiceCount = PurchaseOrder::where('status', 'approved')
                ->where('received_status', 'fully_received')
                ->whereNull('closed_at')
                ->whereDoesntHave('supplierInvoices', fn($q) => $q->whereNotIn('status', ['void']))
                ->count();

            $poFullyNoInvoiceList = PurchaseOrder::where('status', 'approved')
                ->where('received_status', 'fully_received')
                ->whereNull('closed_at')
                ->whereDoesntHave('supplierInvoices', fn($q) => $q->whereNotIn('status', ['void']))
                ->with('supplier')
                ->orderByDesc('date')
                ->limit(10)
                ->get();
        } else {
            $poFullyNoInvoiceCount = 0;
            $poFullyNoInvoiceList  = collect();
        }

        // ══════════════════════════════════════════════════════════════
        // KPI 6 — Supplier Invoice Outstanding (posted/partial_paid)
        // ══════════════════════════════════════════════════════════════
        if ($hasInvoiceTable) {
            $invOutstandingCount = SupplierInvoice::whereIn('status', ['posted', 'partial_paid'])->count();
            $invOutstandingTotal = (float) SupplierInvoice::whereIn('status', ['posted', 'partial_paid'])
                ->selectRaw('COALESCE(SUM(total_amount - paid_amount), 0) as outstanding')
                ->value('outstanding');

            $invOutstandingList = SupplierInvoice::whereIn('status', ['posted', 'partial_paid'])
                ->with(['supplier', 'purchaseOrder:id,code'])
                ->orderBy('due_date')
                ->orderByDesc('invoice_date')
                ->limit(10)
                ->get();
        } else {
            $invOutstandingCount = 0;
            $invOutstandingTotal = 0.0;
            $invOutstandingList  = collect();
        }

        // ══════════════════════════════════════════════════════════════
        // KPI 7 — Invoice jatuh tempo / overdue
        // ══════════════════════════════════════════════════════════════
        if ($hasInvoiceTable) {
            $invOverdueCount = SupplierInvoice::whereIn('status', ['posted', 'partial_paid'])
                ->whereNotNull('due_date')
                ->where('due_date', '<', $today)
                ->count();
        } else {
            $invOverdueCount = 0;
        }

        // ══════════════════════════════════════════════════════════════
        // KPI 8 — PO Siap Close
        // ══════════════════════════════════════════════════════════════
        $poReadyCloseQuery = PurchaseOrder::where('status', 'approved')
            ->where('received_status', 'fully_received')
            ->where('payment_status', 'paid')
            ->whereNull('closed_at');

        if ($hasInvoiceTable) {
            $poReadyCloseQuery->whereDoesntHave('supplierInvoices', fn($q) =>
                $q->whereIn('status', ['draft', 'posted', 'partial_paid'])
            );
        }

        $poReadyCloseCount = (clone $poReadyCloseQuery)->count();
        $poReadyCloseList  = (clone $poReadyCloseQuery)
            ->with(['supplier', 'supplierInvoices'])
            ->orderByDesc('date')
            ->limit(10)
            ->get();

        // ══════════════════════════════════════════════════════════════
        // KPI 9 — PO Belum Bisa Close (ada blocker)
        // ══════════════════════════════════════════════════════════════
        $poNotReadyCloseCount = PurchaseOrder::where('status', 'approved')
            ->whereNull('closed_at')
            ->where(fn($q) => $q
                ->where('received_status', '!=', 'fully_received')
                ->orWhere('payment_status', '!=', 'paid')
            )
            ->count();

        $poNotReadyCloseList = PurchaseOrder::where('status', 'approved')
            ->whereNull('closed_at')
            ->where(fn($q) => $q
                ->where('received_status', '!=', 'fully_received')
                ->orWhere('payment_status', '!=', 'paid')
            )
            ->with(['supplier', 'supplierInvoices'])
            ->orderByDesc('date')
            ->limit(15)
            ->get()
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
                    $outstanding = $po->supplierInvoices
                        ->whereIn('status', ['draft', 'posted', 'partial_paid'])
                        ->count();
                    if ($outstanding > 0) {
                        $blockers[] = "{$outstanding} invoice belum lunas";
                    }
                }
                $po->_blockers = $blockers;
                return $po;
            });

        // ══════════════════════════════════════════════════════════════
        // KPI 10 — PO Closed bulan ini
        // ══════════════════════════════════════════════════════════════
        $poClosedMonthCount = PurchaseOrder::whereNotNull('closed_at')
            ->whereBetween('closed_at', [$startOfMonth . ' 00:00:00', $endOfMonth . ' 23:59:59'])
            ->count();

        // ══════════════════════════════════════════════════════════════
        // SECTION E — Recent Activity
        // ══════════════════════════════════════════════════════════════
        $recentPr = $hasPrTable
            ? PurchaseRequest::with(['supplier', 'requestedBy'])
                ->orderByDesc('created_at')
                ->limit(5)
                ->get()
            : collect();

        $recentPo = PurchaseOrder::with('supplier')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $recentInvoices = $hasInvoiceTable
            ? SupplierInvoice::with('supplier')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get()
            : collect();

        $hasPaymentTable = Schema::hasTable('purchase_payments');
        $recentPayments = $hasPaymentTable
            ? PurchasePayment::with(['purchaseOrder.supplier'])
                ->where('voided_at', null)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get()
            : collect();

        return view('purchasing.dashboard', compact(
            'canSeeMoney',
            'isOwner',
            'isAdmin',
            'today',
            'hasInvoiceTable',
            'hasPrTable',
            // KPI counts
            'prDraftCount',
            'prApprovedNotConvertedCount',
            'poApprovedNotReceivedCount',
            'poPartialCount',
            'poFullyNoInvoiceCount',
            'invOutstandingCount',
            'invOutstandingTotal',
            'invOverdueCount',
            'poReadyCloseCount',
            'poNotReadyCloseCount',
            'poClosedMonthCount',
            // Section lists
            'prApprovedNotConvertedList',
            'poBelumTerimaList',
            'poFullyNoInvoiceList',
            'invOutstandingList',
            'poReadyCloseList',
            'poNotReadyCloseList',
            // Recent activity
            'recentPr',
            'recentPo',
            'recentInvoices',
            'recentPayments',
            'hasPaymentTable'
        ));
    }
}
