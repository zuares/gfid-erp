<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\SupplierInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PurchasingDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user      = $request->user();
        $role      = strtolower((string) ($user->role ?? ''));
        $isOwner   = $user?->isOwner() ?? false;
        $canSeeMoney = $isOwner || in_array($role, ['accounting', 'developer']);

        // abort jika bukan role yang diizinkan
        abort_unless(
            $isOwner || in_array($role, ['admin', 'accounting', 'developer']),
            403,
            'Akses ditolak.'
        );

        $today = now()->toDateString();
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth   = now()->endOfMonth()->toDateString();

        // ======================================================================
        // KPI 1 — PO Draft
        // ======================================================================
        $poDraftCount = PurchaseOrder::where('status', 'draft')->count();
        $poDraftList  = PurchaseOrder::where('status', 'draft')
            ->with('supplier')
            ->orderByDesc('date')
            ->limit(10)
            ->get();

        // ======================================================================
        // KPI 2 — PO Approved belum diterima sama sekali
        // ======================================================================
        $poApprovedNotReceivedCount = PurchaseOrder::where('status', 'approved')
            ->where(fn($q) => $q->whereNull('received_status')->orWhere('received_status', 'not_received'))
            ->count();

        $poApprovedNotReceivedList = PurchaseOrder::where('status', 'approved')
            ->where(fn($q) => $q->whereNull('received_status')->orWhere('received_status', 'not_received'))
            ->with('supplier')
            ->orderByDesc('date')
            ->limit(10)
            ->get();

        // ======================================================================
        // KPI 3 — PO Partial Received
        // ======================================================================
        $poPartialCount = PurchaseOrder::where('status', 'approved')
            ->where('received_status', 'partial')
            ->count();

        $poPartialList = PurchaseOrder::where('status', 'approved')
            ->where('received_status', 'partial')
            ->with('supplier')
            ->orderByDesc('date')
            ->limit(10)
            ->get();

        // ======================================================================
        // KPI 4 — PO Fully Received tapi belum ada Supplier Invoice
        // ======================================================================
        $hasInvoiceTable = Schema::hasTable('supplier_invoices');

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

        // ======================================================================
        // KPI 5 — Supplier Invoice Outstanding (posted/partial_paid)
        // ======================================================================
        if ($hasInvoiceTable) {
            $invOutstandingCount = SupplierInvoice::whereIn('status', ['posted', 'partial_paid'])->count();
            $invOutstandingTotal = (float) SupplierInvoice::whereIn('status', ['posted', 'partial_paid'])
                ->selectRaw('SUM(total_amount - paid_amount) as outstanding')
                ->value('outstanding');

            $invOutstandingList = SupplierInvoice::whereIn('status', ['posted', 'partial_paid'])
                ->with('supplier')
                ->orderBy('due_date')
                ->orderByDesc('invoice_date')
                ->limit(10)
                ->get();
        } else {
            $invOutstandingCount = 0;
            $invOutstandingTotal = 0.0;
            $invOutstandingList  = collect();
        }

        // ======================================================================
        // KPI 6 — Invoice jatuh tempo (due_date <= today, belum lunas)
        // ======================================================================
        if ($hasInvoiceTable) {
            $invOverdueCount = SupplierInvoice::whereIn('status', ['posted', 'partial_paid'])
                ->whereNotNull('due_date')
                ->where('due_date', '<', $today)
                ->count();

            $invOverdueList = SupplierInvoice::whereIn('status', ['posted', 'partial_paid'])
                ->whereNotNull('due_date')
                ->where('due_date', '<', $today)
                ->with('supplier')
                ->orderBy('due_date')
                ->limit(10)
                ->get();
        } else {
            $invOverdueCount = 0;
            $invOverdueList  = collect();
        }

        // ======================================================================
        // KPI 7 — PO siap Close (approved + fully_received + paid + no outstanding inv)
        // ======================================================================
        if ($hasInvoiceTable) {
            $poReadyCloseQuery = PurchaseOrder::where('status', 'approved')
                ->where('received_status', 'fully_received')
                ->where('payment_status', 'paid')
                ->whereNull('closed_at')
                ->whereDoesntHave('supplierInvoices', fn($q) =>
                    $q->whereIn('status', ['draft', 'posted', 'partial_paid'])
                );
        } else {
            $poReadyCloseQuery = PurchaseOrder::where('status', 'approved')
                ->where('received_status', 'fully_received')
                ->where('payment_status', 'paid')
                ->whereNull('closed_at');
        }

        $poReadyCloseCount = (clone $poReadyCloseQuery)->count();
        $poReadyCloseList  = (clone $poReadyCloseQuery)
            ->with('supplier')
            ->orderByDesc('date')
            ->limit(10)
            ->get();

        // ======================================================================
        // KPI 8 — PO Closed bulan ini
        // ======================================================================
        $poClosedMonthCount = PurchaseOrder::whereNotNull('closed_at')
            ->whereBetween('closed_at', [$startOfMonth . ' 00:00:00', $endOfMonth . ' 23:59:59'])
            ->count();

        // ======================================================================
        // KPI 9 — PO belum bisa close (approved, not closed, ada blocker)
        // ======================================================================
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
                    $outstandingInv = $po->supplierInvoices
                        ->whereIn('status', ['draft', 'posted', 'partial_paid'])
                        ->count();
                    if ($outstandingInv > 0) {
                        $blockers[] = "{$outstandingInv} invoice belum lunas";
                    }
                }
                $po->_blockers = $blockers;
                return $po;
            });

        return view('purchasing.dashboard', compact(
            'canSeeMoney',
            'isOwner',
            'today',
            // KPI counts
            'poDraftCount',
            'poApprovedNotReceivedCount',
            'poPartialCount',
            'poFullyNoInvoiceCount',
            'invOutstandingCount',
            'invOutstandingTotal',
            'invOverdueCount',
            'poReadyCloseCount',
            'poClosedMonthCount',
            // Lists
            'poDraftList',
            'poApprovedNotReceivedList',
            'poPartialList',
            'poFullyNoInvoiceList',
            'invOutstandingList',
            'invOverdueList',
            'poReadyCloseList',
            'poNotReadyCloseList',
            'hasInvoiceTable'
        ));
    }
}
