{{-- resources/views/purchasing/purchase_orders/show.blade.php --}}
@extends('layouts.app')

@section('title', 'PO ' . $order->code)

@push('head')
    <style>
        .page-wrap {
            max-width: 1080px;
            margin-inline: auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            overflow: hidden;
        }

        th.sticky {
            position: sticky;
            top: 0;
            background: var(--card);
            z-index: 1;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .tag {
            border-radius: 999px;
            padding: .15rem .65rem;
            font-size: .7rem;
            border: 1px solid var(--line);
            background: rgba(148, 163, 184, .12);
            white-space: nowrap;
        }

        .tag-status-draft {
            background: rgba(148, 163, 184, .12);
            color: #64748b;
            border-color: rgba(148, 163, 184, .6);
        }

        .tag-status-approved {
            background: rgba(22, 163, 74, .12);
            color: #15803d;
            border-color: rgba(22, 163, 74, .6);
        }

        .tag-status-cancelled {
            background: rgba(220, 38, 38, .08);
            color: #b91c1c;
            border-color: rgba(220, 38, 38, .6);
        }

        .tag-grn {
            border-radius: 999px;
            padding: .15rem .6rem;
            font-size: .7rem;
            border: 1px solid rgba(59, 130, 246, .5);
            background: rgba(59, 130, 246, .08);
            color: #1d4ed8;
            white-space: nowrap;
        }

        .badge-pill {
            border-radius: 999px;
            font-size: .7rem;
            padding: .1rem .5rem;
            border: 1px solid transparent;
            white-space: nowrap;
        }

        .badge-posted {
            background: rgba(22, 163, 74, .12);
            color: #15803d;
            border-color: rgba(22, 163, 74, .5);
        }

        .badge-draft {
            background: rgba(148, 163, 184, .12);
            color: #64748b;
            border-color: rgba(148, 163, 184, .5);
        }

        /* payment badges */
        .pay-badge {
            border-radius: 999px;
            padding: .14rem .6rem;
            font-size: .72rem;
            border: 1px solid rgba(148, 163, 184, .45);
            background: rgba(148, 163, 184, .12);
            color: #64748b;
            white-space: nowrap;
        }

        .pay-paid {
            border-color: rgba(22, 163, 74, .55);
            background: rgba(22, 163, 74, .12);
            color: #15803d;
        }

        .pay-partial {
            border-color: rgba(234, 179, 8, .55);
            background: rgba(234, 179, 8, .12);
            color: #a16207;
        }

        .pay-unpaid {
            border-color: rgba(148, 163, 184, .45);
            background: rgba(148, 163, 184, .12);
            color: #64748b;
        }

        /* method badges */
        .pm-badge {
            border-radius: 999px;
            padding: .12rem .55rem;
            font-size: .7rem;
            border: 1px solid rgba(148, 163, 184, .5);
            background: rgba(148, 163, 184, .10);
            color: #64748b;
            white-space: nowrap;
        }

        .pm-cash {
            border-color: rgba(22, 163, 74, .5);
            background: rgba(22, 163, 74, .10);
            color: #15803d;
        }

        .pm-credit {
            border-color: rgba(59, 130, 246, .55);
            background: rgba(59, 130, 246, .08);
            color: #1d4ed8;
        }

        .pm-transfer {
            border-color: rgba(234, 179, 8, .55);
            background: rgba(234, 179, 8, .10);
            color: #a16207;
        }

        /* type chips */
        .type-chip {
            border-radius: 999px;
            padding: .12rem .55rem;
            font-size: .72rem;
            border: 1px solid rgba(148, 163, 184, .45);
            background: rgba(148, 163, 184, .10);
            color: #64748b;
            white-space: nowrap;
            text-transform: uppercase;
        }

        .type-dp {
            border-color: rgba(59, 130, 246, .55);
            background: rgba(59, 130, 246, .08);
            color: #1d4ed8;
        }

        .type-payment {
            border-color: rgba(22, 163, 74, .55);
            background: rgba(22, 163, 74, .10);
            color: #15803d;
        }

        .type-dp-apply {
            border-color: rgba(234, 179, 8, .55);
            background: rgba(234, 179, 8, .10);
            color: #a16207;
        }

        .btn-pill {
            border-radius: 999px;
            padding-inline: .95rem;
        }

        .gf-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            overflow: hidden;
        }

        .gf-card-h {
            padding: .65rem .9rem;
            border-bottom: 1px solid var(--line);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .gf-card-title {
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--muted);
            font-weight: 800;
        }

        .summary-col {
            padding: .75rem 1rem;
            border-right: 1px solid rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .summary-col:last-child {
            border-right: 0;
        }

        .summary-label {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #64748b;
            font-weight: 700;
            margin-bottom: .25rem;
        }

        .summary-value {
            font-size: 1.15rem;
            font-weight: 900;
            color: #0f172a;
        }

        .summary-money {
            font-size: clamp(1.1rem, 1.8vw, 1.4rem);
            line-height: 1.1;
            word-break: break-word;
            color: #1e293b;
            letter-spacing: -0.02em;
        }
        
        .dashboard-card {
            background: linear-gradient(to bottom right, #ffffff, #f8fafc);
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 12px;
            box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.02);
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .po-total-cell {
            font-size: .95rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .po-progress {
            display: flex;
            justify-content: flex-end;
            gap: .3rem;
            flex-wrap: wrap;
        }

        .po-progress-pill {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, .28);
            background: rgba(148, 163, 184, .07);
            color: var(--muted);
            padding: .12rem .48rem;
            font-size: .68rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .po-progress-pill strong {
            color: var(--text);
        }

        .po-progress-pill.is-ok {
            color: #15803d;
            border-color: rgba(22, 163, 74, .24);
            background: rgba(22, 163, 74, .07);
        }

        .po-progress-pill.is-return {
            color: #b45309;
            border-color: rgba(245, 158, 11, .28);
            background: rgba(245, 158, 11, .08);
        }

        .po-progress-pill.is-left {
            color: #2563eb;
            border-color: rgba(37, 99, 235, .24);
            background: rgba(37, 99, 235, .07);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .6rem;
        }

        .info-cell {
            min-height: 54px;
            padding: .5rem .75rem;
            border: 1px solid rgba(148, 163, 184, .18);
            border-radius: 10px;
            background: rgba(148, 163, 184, .045);
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .info-label {
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--muted);
            font-weight: 800;
            margin-bottom: .18rem;
        }

        .info-value {
            font-size: .88rem;
            font-weight: 700;
            line-height: 1.25;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .info-sub {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .po-table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }

        .po-table thead th {
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            border-top: 1px solid #e2e8f0;
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #64748b;
            padding: .6rem .75rem;
            white-space: nowrap;
            font-weight: 700;
        }

        .po-table tbody tr {
            transition: background-color 0.15s ease;
        }
        
        .po-table tbody tr:hover {
            background-color: #f8fafc;
        }

        .po-table tbody td {
            padding: .65rem .75rem;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
            vertical-align: middle;
        }

        .po-table tfoot th, .po-table tfoot td {
            padding: .75rem .75rem;
            background: #f8fafc;
            font-size: 1.05rem;
            color: #0f172a;
            border-top: 2px solid #e2e8f0;
        }

        .po-item-name {
            font-weight: 700;
            font-size: .95rem;
            color: #0f172a;
            margin-bottom: .2rem;
            line-height: 1.3;
        }

        .po-item-code {
            font-size: .75rem;
            letter-spacing: .02em;
            color: #64748b;
        }

        .modal-content.gf-modal {
            border: 1px solid var(--line);
            border-radius: 16px;
            overflow: hidden;
        }

        .gf-modal .modal-header,
        .gf-modal .modal-footer {
            padding: .7rem .95rem;
            border-color: var(--line);
        }

        .gf-modal .modal-body {
            padding: .95rem;
        }

        .modal-kpi {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: .15rem .55rem;
            font-size: .72rem;
            color: var(--muted);
            background: rgba(148, 163, 184, .08);
        }

        .modal-kpi strong {
            color: var(--text);
        }

        @media (max-width: 768px) {
            .page-wrap {
                padding-inline: .75rem;
            }

            .po-item-name {
                font-size: .9rem;
                font-weight: 600;
            }

            .po-item-code {
                font-size: .78rem;
            }

            .card .card-body {
                padding: .75rem .85rem;
            }

            .card-header {
                padding: .6rem .85rem;
            }

            .po-mobile-card {
                border-top: 1px solid var(--line);
                padding-top: .5rem;
                margin-top: .5rem;
            }

            .po-mobile-card:first-of-type {
                border-top: none;
                padding-top: 0;
                margin-top: 0;
            }

            .po-actions .btn-action {
                width: 100%;
            }

            .summary-col {
                border-right: 0;
                border-bottom: 1px solid var(--line);
            }

            .summary-col:nth-last-child(-n+2) {
                border-bottom: 0;
            }

            .info-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: .5rem;
            }

            .summary-money {
                font-size: .82rem;
            }

            .po-total-cell {
                font-size: .88rem;
            }

            .po-progress {
                justify-content: flex-start;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $user = auth()->user();
        $isAdmin = strtolower((string)($user->role ?? '')) === 'admin';
        $canSeeMoney = $user?->canSeePurchasePrices() ?? false;
        $poHasPrice  = (float) ($order->grand_total ?? 0) > 0;

        // Status PO
        $status = (string) ($order->status ?? 'draft');
        $statusClass = match ($status) {
            'approved' => 'tag tag-status-approved',
            'cancelled' => 'tag tag-status-cancelled',
            default => 'tag tag-status-draft',
        };

        // GRN list
        $grnList = $order->purchaseReceipts ?? collect();
        $grnCount = $grnList->count();

        // Payment method "preferensi" PO
        $pm = $order->paymentMethod ?? null;

        // Hutang real berbasis GRN posted (dari controller show)
        $grnPostedTotal = (float) ($grnPostedTotal ?? 0);
        $returnPostedTotal = (float) ($returnPostedTotal ?? 0);
        $apDebt = (float) ($apDebt ?? max(0, round($grnPostedTotal - $returnPostedTotal, 2)));
        $paidPaymentTotal = (float) ($paidPaymentTotal ?? 0); // type=payment
        $dpTotal = (float) ($dpTotal ?? 0); // type=dp

        // DP APPLY total (buat UI)
        $dpAppliedTotal =
            (float) ($dpAppliedTotal ?? ($order->activePayments()?->where('type', 'dp_apply')->sum('amount') ?? 0));
        $dpAvailable = max(0, round($dpTotal - $dpAppliedTotal, 2));

        // outstanding hutang (should include dp_apply)
        $apOutstanding =
            (float) ($apOutstanding ?? max(0, round($apDebt - $paidPaymentTotal - $dpAppliedTotal, 2)));

        // status bayar
        $payStatus = (string) ($order->payment_status ?? 'unpaid');
        $payBadgeClass = match ($payStatus) {
            'paid' => 'pay-badge pay-paid',
            'partial' => 'pay-badge pay-partial',
            default => 'pay-badge pay-unpaid',
        };

        $canPay = $status !== 'cancelled';
        $hasPayments = ($order->payments?->count() ?? 0) > 0;

        // strict cash/bank list
        $cashAccountsCol = collect($cashAccounts ?? []);
        $cash1101 = $cashAccountsCol->firstWhere('code', '1101');

        $transferBankCodes = ['1111', '1112', '1113', '1114'];
        $transferBanks = $cashAccountsCol
            ->filter(fn($a) => in_array((string) ($a->code ?? ''), $transferBankCodes, true))
            ->values();

        // guard: payment hanya boleh kalau hutang real sudah ada
        $hasAp = $grnPostedTotal > 0.0001;

        // apply DP guard
        $canApplyDp = $canPay && $hasAp && $dpAvailable > 0.01 && $apOutstanding > 0.01;
        $maxApplyDp = max(0, round(min($dpAvailable, $apOutstanding), 2));
        $totalQty = (float) ($order->lines?->sum('qty') ?? 0);
        $hasDiscount = (float) ($order->lines?->sum('discount') ?? 0) > 0.0001;

        // for JS/open modal routing
        $voidActionTemplate = route('purchasing.purchase_orders.payments.void', [
            'purchase_order' => $order->id,
            'payment' => '___PAYMENT___',
        ]);
    @endphp

    <div class="page-wrap py-4">

        {{-- HEADER --}}
        <div class="d-flex align-items-center justify-content-between gap-3 mb-3 flex-wrap">
            <div style="min-width:0;">
                <h2 class="mb-0 lh-1 mono" style="font-size:1.35rem;">{{ $order->code }}</h2>
                <div class="text-muted mt-1" style="font-size:.82rem;">{{ optional($order->supplier)->name ?? 'Purchase Order' }}</div>
                <div class="mt-1 d-flex align-items-center gap-1 flex-wrap">
                    <span class="badge rounded-pill text-bg-secondary" style="font-size:.7rem;font-weight:500;">
                        {{ $order->receivingStageLabel() }}
                    </span>
                    @if ($order->isLocked())
                        <span class="badge rounded-pill text-bg-warning" style="font-size:.7rem;font-weight:600;"
                              title="{{ $order->lock_reason }} ({{ optional($order->locked_at)->format('d/m/Y H:i') }})">
                            <i class="bi bi-lock-fill"></i> Locked
                        </span>
                    @endif
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-end gap-2 flex-wrap ms-auto w-100 w-md-auto">
                <a href="{{ route('purchasing.purchase_orders.index') }}"
                   class="btn btn-light btn-sm fw-bold border shadow-sm" title="Kembali">
                    <i class="bi bi-arrow-left"></i>
                </a>

                {{-- PRIMARY ACTIONS --}}
                @if ($user && (in_array($user->role, ['owner','admin']) || $user->isDeveloper()) && $status === 'draft')
                    @if ($poHasPrice)
                        <form action="{{ route('purchasing.purchase_orders.approve', $order->id) }}" method="POST" class="d-inline"
                            onsubmit="return confirm('Approve PO ini? Setelah di-approve, PO tidak bisa diedit lagi.');">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm fw-bold shadow-sm px-3" title="Approve PO">
                                <i class="bi bi-check-circle"></i> <span class="ms-1">Approve</span>
                            </button>
                        </form>
                    @else
                        <button type="button" class="btn btn-primary btn-sm px-3 fw-bold disabled shadow-sm"
                            style="opacity:.5;cursor:not-allowed;"
                            title="{{ $canSeeMoney ? 'Harga belum diisi, edit PO terlebih dahulu.' : 'Dokumen belum lengkap, hubungi owner.' }}">
                            <i class="bi bi-check-circle"></i> <span class="ms-1">Approve</span>
                        </button>
                    @endif
                @endif
                
                @if ($canSeeMoney && $canPay && $hasAp)
                     <button type="button" class="btn btn-success btn-sm fw-bold shadow-sm px-3"
                         data-bs-toggle="modal" data-bs-target="#modalAddPayment" title="Bayar PO">
                         <i class="bi bi-cash-coin"></i> <span class="ms-1">Bayar PO</span>
                     </button>
                @endif
                
                {{-- Terima (buat GRN) --}}
                @if ($user && ($user->isOwner() || $isAdmin) && $order->isReceivableForGrn() && $status !== 'cancelled' && ($canCreateGrn ?? true))
                     <a href="{{ route('purchasing.purchase_receipts.create_from_order', $order->id) }}"
                         class="btn btn-info text-white btn-sm fw-bold shadow-sm px-3" title="Terima Barang">
                         <i class="bi bi-box-seam"></i> <span class="ms-1">Terima</span>
                     </a>
                @endif

                {{-- SECONDARY ACTIONS DROPDOWN --}}
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm border shadow-sm px-2" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Opsi Lainnya">
                        <i class="bi bi-three-dots-vertical" style="font-size: 1.1rem; vertical-align: middle;"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="font-size: .85rem; border-radius: 12px; padding: .5rem 0;">
                        @if ($canSeeMoney)
                            <li>
                                <a class="dropdown-item py-2" href="{{ route('purchasing.purchase_orders.print_dot_matrix', $order->id) }}">
                                    <i class="bi bi-printer me-2 text-muted"></i> Cetak (Dot Matrix)
                                </a>
                            </li>
                        @endif
                        @if ($status === 'draft' && (!$order->isLocked() || ($user && $user->canSeePurchasePrices())))
                            <li>
                                <a class="dropdown-item py-2" href="{{ route('purchasing.purchase_orders.edit', $order->id) }}">
                                    <i class="bi bi-pencil me-2 text-muted"></i> Edit PO
                                </a>
                            </li>
                        @endif
                        @if ($canSeeMoney && $canPay && $hasAp)
                            <li>
                                <button type="button" class="dropdown-item py-2" data-bs-toggle="modal" data-bs-target="#modalApplyDp" @if (!$canApplyDp) disabled @endif>
                                    <i class="bi bi-arrow-left-right me-2 text-muted"></i> Offset DP
                                </button>
                            </li>
                        @endif
                        @if ($canSeeMoney && $canPay && $hasPayments)
                            <li>
                                <button type="button" class="dropdown-item py-2" data-bs-toggle="collapse" data-bs-target="#paymentHistoryCollapse">
                                    <i class="bi bi-clock-history me-2 text-muted"></i> Riwayat Pembayaran
                                </button>
                            </li>
                        @endif
                        @if ($status === 'approved' && $user && ($user->isOwner() || in_array($user->role ?? '', ['accounting', 'developer'])))
                            @if (\Illuminate\Support\Facades\Route::has('purchasing.supplier_invoices.create'))
                                <li>
                                    <a class="dropdown-item py-2" href="{{ route('purchasing.supplier_invoices.create', ['purchase_order_id' => $order->id]) }}">
                                        <i class="bi bi-receipt me-2 text-muted"></i> Buat Faktur Supplier
                                    </a>
                                </li>
                            @endif
                        @endif
                        @if ($user && (in_array($user->role, ['owner','admin']) || $user->isDeveloper()) && $status === 'approved' && $grnCount === 0)
                            <li>
                                <form action="{{ route('purchasing.purchase_orders.unapprove', $order->id) }}" method="POST" class="m-0 p-0" onsubmit="return confirm('Kembalikan PO ini ke status Draft?');">
                                    @csrf
                                    <button type="submit" class="dropdown-item py-2 text-warning fw-bold">
                                        <i class="bi bi-arrow-counterclockwise me-2"></i> Unapprove
                                    </button>
                                </form>
                            </li>
                        @endif
                        @if ($user && $user->isOwner() && !($order->isClosed()) && ($canClose ?? false))
                            @if (\Illuminate\Support\Facades\Route::has('purchasing.purchase_orders.close'))
                                <li>
                                    <form action="{{ route('purchasing.purchase_orders.close', $order->id) }}" method="POST" class="m-0 p-0" onsubmit="return confirm('Close PO ini? Pastikan semua sudah lunas dan diterima.');">
                                        @csrf
                                        <button type="submit" class="dropdown-item py-2 text-dark fw-bold">
                                            <i class="bi bi-check2-all me-2"></i> Close PO
                                        </button>
                                    </form>
                                </li>
                            @endif
                        @endif
                        @if ($user && $user->isOwner() && in_array($status, ['draft', 'approved'], true) && $grnCount === 0)
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('purchasing.purchase_orders.cancel', $order->id) }}" method="POST" class="m-0 p-0" onsubmit="return confirm('Batalkan PO ini? Tindakan ini tidak bisa dibatalkan.');">
                                    @csrf
                                    <button type="submit" class="dropdown-item py-2 text-danger fw-bold">
                                        <i class="bi bi-trash me-2"></i> Cancel PO
                                    </button>
                                </form>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>

        {{-- FLASH --}}
        @if (session('success'))
            <div class="alert alert-success py-2 small">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
        @endif

        {{-- ============================================================
        RINGKASAN PO — Status + Nominal (Tahap 4)
        ============================================================ --}}
        @php
            $isClosed    = $order->isClosed();
            $rcvStatus   = $order->received_status ?? 'not_received';
            $closeBlks   = $closeBlockers ?? [];
            $canClosePO  = ($canClose ?? false) && !$isClosed;
            $invTotal    = (float) ($invoiceTotalAmount ?? 0);
            $invPaid     = (float) ($invoiceTotalPaid ?? 0);
            $invOutstand = (float) ($invoiceOutstanding ?? 0);
            $poInvoiceList = $poInvoices ?? collect();
        @endphp
        {{-- SUMMARY CARD (EXECUTIVE DASHBOARD) --}}
        <div class="dashboard-card mb-4">
            <div class="row g-0 align-items-center">
                @if ($canSeeMoney)
                    <div class="col-12 col-md-5 summary-col" style="background: rgba(248, 250, 252, 0.5);">
                        <div class="summary-label"><i class="bi bi-wallet2 me-1"></i> Total Nilai</div>
                        <div class="summary-value mono summary-money mb-2">{{ rupiah($order->grand_total) }}</div>
                        
                        <div class="d-flex align-items-center gap-2 mt-1">
                            @if ($apOutstanding > 0)
                                <span class="badge bg-danger rounded-pill px-3 py-1 fw-bold" style="font-size: .75rem;">
                                    Sisa: {{ rupiah($apOutstanding) }}
                                </span>
                            @else
                                <span class="badge bg-success rounded-pill px-3 py-1 fw-bold" style="font-size: .75rem;">
                                    <i class="bi bi-check-all"></i> Lunas
                                </span>
                            @endif
                        </div>
                    </div>
                @endif
                
                <div class="col-6 {{ $canSeeMoney ? 'col-md-2' : 'col-md-4' }} summary-col">
                    <div class="summary-label">Status</div>
                    <div class="summary-value">
                        <span class="{{ $statusClass }} border rounded-pill px-3 py-1" style="font-size: .75rem;">
                            {{ $isClosed ? 'CLOSED' : strtoupper($status) }}
                        </span>
                    </div>
                </div>
                
                <div class="col-6 {{ $canSeeMoney ? 'col-md-2' : 'col-md-4' }} summary-col">
                    <div class="summary-label">Terima (GRN)</div>
                    <div class="summary-value" style="font-size: .9rem;">
                        {!! received_status_label($rcvStatus) !!}
                    </div>
                </div>
                
                <div class="col-12 {{ $canSeeMoney ? 'col-md-3' : 'col-md-4' }} summary-col">
                    <div class="summary-label">Barang</div>
                    <div class="summary-value mono" style="font-size: 1.05rem; color: #334155;">
                        {{ $order->lines->count() }} <span style="font-size:.75rem; color:#94a3b8; font-weight:normal;">Tipe</span> 
                        <span class="mx-1 text-muted fw-normal" style="opacity: .4;">•</span> 
                        {{ decimal_id($totalQty, 2) }} <span style="font-size:.75rem; color:#94a3b8; font-weight:normal;">Pcs</span>
                    </div>
                </div>
            </div>

            @if (($canSeeMoney && $invTotal > 0) || $isClosed || count($closeBlks) > 0 || $canClosePO)
                <div class="px-4 py-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-2" style="background: #f8fafc;">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        @if ($canSeeMoney && $invTotal > 0)
                            <div class="text-muted" style="font-size: .8rem;">
                                <strong>Invoice:</strong> <span class="mono text-dark fw-bold">{{ rupiah($invTotal) }}</span>
                            </div>
                            <div class="text-muted" style="font-size: .8rem;">
                                <strong>Dibayar:</strong> <span class="mono text-success fw-bold">{{ rupiah($invPaid) }}</span>
                            </div>
                        @endif
                    </div>
                    
                    <div class="d-flex align-items-center gap-2">
                        @if (!$isClosed && count($closeBlks) > 0)
                            @foreach ($closeBlks as $blk)
                                <span class="badge bg-danger rounded-pill" style="font-size: .7rem;"><i class="bi bi-exclamation-triangle"></i> {{ $blk }}</span>
                            @endforeach
                        @elseif ($isClosed)
                            <span class="badge bg-dark rounded-pill px-3 py-1" style="font-size: .75rem;"><i class="bi bi-check2-all"></i> Closed pada {{ $order->closed_at ? id_date($order->closed_at) : '' }}</span>
                        @elseif ($canClosePO)
                            <span class="badge bg-info text-white rounded-pill px-3 py-1" style="font-size: .75rem;"><i class="bi bi-info-circle"></i> PO siap di-close</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- WARNING: harga belum diisi --}}
        @if ($status === 'draft' && !$poHasPrice && ($canSeeMoney || $isAdmin))
            <div class="alert mb-3 py-2 px-3 d-flex align-items-center gap-2"
                 style="background:rgba(234,179,8,.1);border:1px solid rgba(234,179,8,.4);border-radius:10px;font-size:.85rem;">
                @if ($canSeeMoney)
                    <span><strong>Harga belum diisi.</strong> Isi harga sebelum approval.</span>
                @else
                    <span><strong>PO belum bisa di-approve.</strong> Hubungi owner.</span>
                @endif
            </div>
        @endif

        {{-- INFO CARD --}}
        <div class="gf-card mb-3">
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-cell">
                        <div class="info-label">Tanggal</div>
                        <div class="info-value mono">{{ id_date($order->date) }}</div>
                    </div>
                    <div class="info-cell">
                        <div class="info-label">Supplier</div>
                        <div class="info-value mono" title="{{ optional($order->supplier)->name ?? '' }}">
                            {{ optional($order->supplier)->code ?? '—' }}
                        </div>
                    </div>
                    <div class="info-cell">
                        <div class="info-label">Jenis</div>
                        <div class="info-value">{{ po_order_type_label($order->order_type, true) }}</div>
                    </div>
                    @if ($canSeeMoney)
                        @php
                            $pmModeLabel = ['cash' => 'Tunai', 'transfer' => 'Transfer', 'credit' => 'Tempo'];
                            $pmMode = strtolower((string) ($pm->mode ?? ''));
                        @endphp
                        <div class="info-cell">
                            <div class="info-label">Bayar</div>
                            <div class="info-value">{{ $pm ? ($pmModeLabel[$pmMode] ?? $pm->name) : '—' }}</div>
                            @if (!empty($order->due_date))
                                <div class="text-muted mono info-sub" style="font-size:.72rem;">Jatuh tempo {{ id_date($order->due_date) }}</div>
                            @endif
                        </div>
                    @endif
                </div>
                @if ($order->notes || !empty($purchaseRequest))
                    <div class="border-top mt-3 pt-2 d-flex gap-2 flex-wrap" style="font-size:.8rem;">
                        @if ($order->notes)
                            <span class="modal-kpi">Catatan <strong>{{ $order->notes }}</strong></span>
                        @endif
                        @if (!empty($purchaseRequest))
                            @if (\Illuminate\Support\Facades\Route::has('purchasing.purchase_requests.show'))
                                <a href="{{ route('purchasing.purchase_requests.show', $purchaseRequest->id) }}" class="modal-kpi text-decoration-none">
                                    PR <strong class="mono">{{ $purchaseRequest->code }}</strong>
                                </a>
                            @else
                                <span class="modal-kpi">PR <strong class="mono">{{ $purchaseRequest->code }}</strong></span>
                            @endif
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- RIWAYAT PEMBAYARAN (COLLAPSE) --}}
        @if ($canSeeMoney && $hasPayments)
            <div class="collapse mb-4" id="paymentHistoryCollapse">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="fw-semibold">Riwayat Pembayaran</div>
                        <div class="text-muted small mono">
                            Sisa {{ rupiah($apOutstanding) }} • DP Avail {{ rupiah($dpAvailable) }}
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0 mono align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 18%;">Tanggal</th>
                                        <th style="width: 14%;">Tipe</th>
                                        <th>Metode</th>
                                        <th style="width: 18%;" class="text-end">Nominal</th>
                                        <th style="width: 18%;">Ref</th>
                                        <th style="width: 14%;" class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($order->payments as $p)
                                        @php
                                            $pMode = strtolower((string) ($p->paymentMethod?->mode ?? ''));
                                            $pPmClass = match ($pMode) {
                                                'cash' => 'pm-badge pm-cash',
                                                'credit' => 'pm-badge pm-credit',
                                                'transfer' => 'pm-badge pm-transfer',
                                                default => 'pm-badge',
                                            };

                                            $type = (string) ($p->type ?? '');
                                            $typeClass = match ($type) {
                                                'dp' => 'type-chip type-dp',
                                                'dp_apply' => 'type-chip type-dp-apply',
                                                default => 'type-chip type-payment',
                                            };

                                            $typeLabel = match ($type) {
                                                'dp' => 'DP',
                                                'dp_apply' => 'OFFSET',
                                                default => 'PAYMENT',
                                            };
                                        @endphp

                                        <tr @class(['text-muted' => $p->voided_at])>
                                            <td>{{ $p->date ? id_date($p->date) : '-' }}</td>

                                            <td>
                                                <span class="{{ $typeClass }}">{{ $typeLabel }}</span>
                                            </td>

                                            <td>
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <span>{{ $p->paymentMethod?->name ?? '-' }}</span>
                                                    @if ($p->paymentMethod)
                                                        <span class="{{ $pPmClass }} mono">
                                                            {{ strtoupper($p->paymentMethod?->mode ?? '-') }}
                                                        </span>
                                                    @endif
                                                    @if ($p->voided_at)
                                                        <span class="tag tag-status-cancelled mono">VOID</span>
                                                    @endif
                                                </div>

                                                @if ($p->cashAccount)
                                                    <div class="text-muted small mono">
                                                        {{ $p->cashAccount->code }} — {{ $p->cashAccount->name }}
                                                    </div>
                                                @else
                                                    @if (strtolower((string) ($p->paymentMethod?->mode ?? '')) === 'credit')
                                                        <div class="text-muted small">Tanpa kas/bank (tempo/credit)</div>
                                                    @endif
                                                @endif

                                                @if (!empty($p->notes))
                                                    <div class="text-muted small">{{ $p->notes }}</div>
                                                @endif
                                            </td>

                                            <td class="text-end fw-semibold">{{ rupiah($p->amount) }}</td>
                                            <td>{{ $p->ref_no ?? '—' }}</td>

                                            <td class="text-end">
                                                @if (!$p->voided_at && $canPay)
                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                        data-bs-toggle="modal" data-bs-target="#modalVoidPayment"
                                                        data-payment-id="{{ $p->id }}"
                                                        data-payment-type="{{ $typeLabel }}"
                                                        data-payment-amount="{{ rupiah($p->amount) }}"
                                                        data-payment-date="{{ $p->date ? id_date($p->date) : '-' }}">
                                                        VOID
                                                    </button>
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>
            </div>
        @endif


        {{-- DETAIL BARANG --}}
        <div class="gf-card mb-4">
            <div class="gf-card-h">
                <div>
                    <div class="gf-card-title">Barang</div>
                    <div class="mono fw-semibold" style="font-size:.9rem;">{{ $order->lines->count() }} item · {{ decimal_id($totalQty, 2) }}</div>
                </div>
            </div>

            <div class="table-responsive d-none d-md-block">
                <table class="table mb-0 po-table" style="border-bottom: 1px solid #e2e8f0;">
                    <thead style="background: transparent;">
                        <tr>
                            <th class="text-center text-muted" style="width: 50px; border-bottom: 2px solid #cbd5e1;">#</th>
                            <th style="border-bottom: 2px solid #cbd5e1;">Deskripsi Barang</th>
                            <th class="text-center" style="width: 10%; border-bottom: 2px solid #cbd5e1;">Qty</th>
                            @if ($canSeeMoney)
                                <th class="text-end" style="width: 15%; border-bottom: 2px solid #cbd5e1;">Harga</th>
                                @if ($hasDiscount)
                                    <th class="text-end" style="width: 12%; border-bottom: 2px solid #cbd5e1;">Diskon</th>
                                @endif
                                <th class="text-end" style="width: 18%; border-bottom: 2px solid #cbd5e1;">Jumlah</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($order->lines as $line)
                            @php
                                $lineReceived = (float) (($receivedByLine ?? collect())->get($line->id, 0));
                                $lineReturned = (float) (($returnedByLine ?? collect())->get($line->id, 0));
                                $lineLeft = max(0, round((float) $line->qty - $lineReceived, 4));
                            @endphp
                            <tr>
                                <td class="text-center align-top text-muted pt-3">{{ $loop->iteration }}</td>
                                <td class="pt-3 pb-3">
                                    <div class="fw-bold" style="font-size: .95rem; color: #1e293b;">{{ optional($line->item)->name ?? '—' }}</div>
                                    @if ($line->item)
                                        <div class="text-muted" style="font-size: .75rem; margin-bottom: 8px;">Kode: {{ $line->item->code }}</div>
                                    @endif
                                    
                                    <div class="d-flex flex-wrap gap-2 mt-1">
                                        <span class="badge" style="background: rgba(22, 163, 74, .1); color: #15803d; font-size: .7rem; font-weight: 600; border: 1px solid rgba(22, 163, 74, .2);">Terima: {{ decimal_id($lineReceived, 2) }}</span>
                                        @if ($lineReturned > 0.0001)
                                            <span class="badge" style="background: rgba(245, 158, 11, .1); color: #b45309; font-size: .7rem; font-weight: 600; border: 1px solid rgba(245, 158, 11, .2);">Retur: {{ decimal_id($lineReturned, 2) }}</span>
                                        @endif
                                        <span class="badge" style="background: rgba(59, 130, 246, .1); color: #1d4ed8; font-size: .7rem; font-weight: 600; border: 1px solid rgba(59, 130, 246, .2);">Sisa: {{ decimal_id($lineLeft, 2) }}</span>
                                    </div>
                                </td>
                                <td class="text-center align-top pt-3 fw-semibold" style="font-size: 1rem;">{{ decimal_id($line->qty, 2) }}</td>
                                @if ($canSeeMoney)
                                    <td class="text-end align-top pt-3 mono">{{ angka($line->unit_price) }}</td>
                                    @if ($hasDiscount)
                                        <td class="text-end align-top pt-3 mono text-danger">-{{ angka($line->discount) }}</td>
                                    @endif
                                    <td class="text-end align-top pt-3 mono fw-bold" style="color: #0f172a; font-size: 1.05rem;">{{ angka($line->line_total) }}</td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canSeeMoney ? ($hasDiscount ? 6 : 5) : 3 }}" class="text-center text-muted py-4">Tidak ada item dalam PO ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($canSeeMoney)
                        <tfoot style="background: transparent;">
                            <tr>
                                <td colspan="{{ $hasDiscount ? 5 : 4 }}" class="text-end py-3 fw-bold text-uppercase" style="font-size: .8rem; letter-spacing: .05em; color: #64748b; border-bottom: 0;">Grand Total</td>
                                <td class="text-end py-3 mono fw-bolder" style="font-size: 1.25rem; color: #0f172a; border-bottom: 0;">{{ rupiah($order->grand_total) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            <div class="d-md-none">
                <div class="p-3 pt-2">
                    @forelse ($order->lines as $line)
                        @php
                            $item = $line->item;
                            $lineReceived = (float) (($receivedByLine ?? collect())->get($line->id, 0));
                            $lineReturned = (float) (($returnedByLine ?? collect())->get($line->id, 0));
                            $lineLeft = max(0, round((float) $line->qty - $lineReceived, 4));
                        @endphp
                            <div class="po-mobile-card mb-2 p-3" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <div class="fw-bold" style="font-size: .95rem; color: #1e293b; line-height: 1.2;">{{ $item->name ?? '—' }}</div>
                                        @if ($item)
                                            <div class="text-muted mt-1" style="font-size: .75rem;">Kode: {{ $item->code }}</div>
                                        @endif
                                    </div>
                                    @if ($canSeeMoney)
                                        <div class="text-end ms-3">
                                            <div class="fw-bold mono" style="color: #0f172a; font-size: 1.05rem;">{{ angka($line->line_total) }}</div>
                                            <div class="text-muted small mono">Total</div>
                                        </div>
                                    @endif
                                </div>

                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <span class="badge" style="background: rgba(22, 163, 74, .1); color: #15803d; font-size: .65rem; border: 1px solid rgba(22, 163, 74, .2);">Terima: {{ decimal_id($lineReceived, 2) }}</span>
                                    @if ($lineReturned > 0.0001)
                                        <span class="badge" style="background: rgba(245, 158, 11, .1); color: #b45309; font-size: .65rem; border: 1px solid rgba(245, 158, 11, .2);">Retur: {{ decimal_id($lineReturned, 2) }}</span>
                                    @endif
                                    <span class="badge" style="background: rgba(59, 130, 246, .1); color: #1d4ed8; font-size: .65rem; border: 1px solid rgba(59, 130, 246, .2);">Sisa: {{ decimal_id($lineLeft, 2) }}</span>
                                </div>

                                <div class="d-flex justify-content-between align-items-center" style="font-size: .85rem; padding-top: .5rem; border-top: 1px dashed #e2e8f0;">
                                    <div>
                                        <span class="text-muted">Qty:</span> <strong class="mono" style="color: #1e293b;">{{ decimal_id($line->qty, 2) }}</strong>
                                    </div>
                                    @if ($canSeeMoney)
                                        <div class="text-end">
                                            <span class="text-muted">@</span> <strong class="mono" style="color: #1e293b;">{{ angka($line->unit_price) }}</strong>
                                            @if ($hasDiscount)
                                                <div class="text-danger mono" style="font-size: .75rem;">Disc: -{{ angka($line->discount) }}</div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                    @empty
                        <div class="text-center text-muted py-3 small">Tidak ada item</div>
                    @endforelse

                    @if ($canSeeMoney)
                        <div class="mt-3 border-top pt-2 small">
                            <div class="d-flex justify-content-between mt-1 fw-bold">
                                <span>Total PO</span>
                                <span class="mono">{{ rupiah($order->grand_total) }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

        {{-- GOODS RECEIPTS (GRN) — owner & admin --}}
        @if ($user && ($user->isOwner() || $isAdmin))
        <div class="gf-card mb-4">
            <div class="gf-card-h">
                <div>
                    <div class="gf-card-title">Penerimaan</div>
                    <div class="mono fw-semibold" style="font-size:.9rem;">{{ $grnCount }} dokumen</div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @if ($status === 'approved' && !($canCreateGrn ?? true))
                        <span class="modal-kpi">Semua diterima</span>
                    @endif

                    <a href="{{ route('purchasing.purchase_receipts.index', ['po' => $order->id]) }}"
                        class="btn btn-sm btn-outline-secondary">
                        Semua
                    </a>
                </div>
            </div>

            <div class="table-responsive d-none d-md-block">
                @if ($grnCount === 0)
                    <div class="p-3 text-muted small">Belum ada penerimaan.</div>
                @else
                    <table class="table table-sm mb-0 mono align-middle po-table">
                        <thead>
                            <tr>
                                <th style="width: 18%;">Tanggal</th>
                                <th>Dokumen</th>
                                <th style="width: 18%;">Gudang</th>
                                @if ($canSeeMoney)
                                <th style="width: 16%;" class="text-end">Total</th>
                                @endif
                                <th style="width: 12%;" class="text-center">Status</th>
                                <th style="width: 10%;" class="text-end"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($grnList as $grn)
                                @php
                                    $isPosted = ($grn->status ?? 'draft') === 'posted';
                                    $badgeStatusClass = $isPosted
                                        ? 'badge-pill badge-posted'
                                        : 'badge-pill badge-draft';
                                    $statusLabel = $isPosted ? 'POSTED' : 'DRAFT';
                                    $wh = $grn->warehouse ?? null;
                                @endphp
                                <tr>
                                    <td>{{ $grn->date ? id_date($grn->date) : '-' }}</td>
                                    <td>
                                        <a href="{{ route('purchasing.purchase_receipts.show', $grn->id) }}"
                                            class="text-decoration-none">
                                            {{ $grn->code ?? $grn->id }}
                                        </a>
                                    </td>
                                    <td>
                                        @if ($wh)
                                            <div class="fw-semibold">{{ $wh->code }}</div>
                                            <div class="text-muted small">{{ $wh->name }}</div>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    @if ($canSeeMoney)
                                    <td class="text-end">{{ isset($grn->grand_total) ? rupiah($grn->grand_total) : '—' }}</td>
                                    @endif
                                    <td class="text-center">
                                        <span class="{{ $badgeStatusClass }}">{{ $statusLabel }}</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <a href="{{ route('purchasing.purchase_receipts.show', $grn->id) }}"
                                                class="btn btn-xs btn-outline-secondary btn-sm">Detail</a>
                                            @if (!$isPosted)
                                                <form action="{{ route('purchasing.purchase_receipts.post', $grn->id) }}"
                                                    method="POST"
                                                    onsubmit="return confirm('Post GRN ini? Setelah di-post, stok akan ter-update.');">
                                                    @csrf
                                                    <button type="submit"
                                                        class="btn btn-xs btn-success btn-sm">Post</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- Mobile cards --}}
            <div class="d-md-none">
                @if ($grnCount === 0)
                    <div class="p-3 text-muted small">Belum ada penerimaan.</div>
                @else
                    <div class="p-3 pt-2">
                        @foreach ($grnList as $grn)
                            @php
                                $isPosted = ($grn->status ?? 'draft') === 'posted';
                                $badgeStatusClass = $isPosted ? 'badge-pill badge-posted' : 'badge-pill badge-draft';
                                $statusLabel = $isPosted ? 'POSTED' : 'DRAFT';
                                $wh = $grn->warehouse ?? null;
                            @endphp

                            <div class="po-mobile-card">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-semibold mono">
                                            <a href="{{ route('purchasing.purchase_receipts.show', $grn->id) }}"
                                                class="text-decoration-none">
                                                {{ $grn->code ?? $grn->id }}
                                            </a>
                                        </div>
                                        <div class="text-muted small mono">{{ $grn->date ? id_date($grn->date) : '-' }}
                                        </div>
                                        @if ($wh)
                                            <div class="small mt-1">
                                                <span class="fw-semibold mono">{{ $wh->code }}</span>
                                                <span class="text-muted">• {{ $wh->name }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="text-end">
                                        <span class="{{ $badgeStatusClass }}">{{ $statusLabel }}</span>
                                    </div>
                                </div>

                                @if ($canSeeMoney)
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <div class="small text-muted">Total</div>
                                    <div class="mono fw-semibold">
                                        {{ isset($grn->grand_total) ? rupiah($grn->grand_total) : '—' }}</div>
                                </div>
                                @endif

                                <div class="d-flex justify-content-end gap-1 mt-2">
                                    <a href="{{ route('purchasing.purchase_receipts.show', $grn->id) }}"
                                        class="btn btn-outline-secondary btn-sm">Detail</a>

                                    @if (!$isPosted)
                                        <form action="{{ route('purchasing.purchase_receipts.post', $grn->id) }}"
                                            method="POST"
                                            onsubmit="return confirm('Post GRN ini? Setelah di-post, stok akan ter-update.');">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm">Post</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        @endif {{-- end isOwner GRN --}}

        </div>

    </div>

    @if ($canSeeMoney)
    {{-- =========================================================
    MODAL: ADD PAYMENT / DP (punya kamu, tetap, cuma sedikit touch)
========================================================= --}}
    <div class="modal fade" id="modalAddPayment" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content gf-modal">
                <div class="modal-header">
                    <div>
                        <h6 class="modal-title fw-semibold mb-0">Bayar PO</h6>
                        <div class="d-flex gap-1 flex-wrap mt-2">
                            <span class="modal-kpi">GRN <strong class="mono">{{ rupiah($grnPostedTotal) }}</strong></span>
                            @if ($returnPostedTotal > 0.0001)
                                <span class="modal-kpi">Retur <strong class="mono">{{ rupiah($returnPostedTotal) }}</strong></span>
                            @endif
                            <span class="modal-kpi">Sisa <strong class="mono">{{ rupiah($apOutstanding) }}</strong></span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form method="POST" action="{{ route('purchasing.purchase_orders.payments.store', $order->id) }}"
                    id="paymentForm">
                    @csrf

                    <div class="modal-body">
                        <div class="row g-2">
                            <div class="col-6 col-md-3">
                                <label class="form-label small fw-semibold">Tanggal</label>
                                <input type="text" name="date" class="form-control form-control-sm gf-date-input"
                                    value="{{ old('date', now()->toDateString()) }}" data-gf-date
                                    autocomplete="off" required>
                            </div>

                            <div class="col-6 col-md-3">
                                <label class="form-label small fw-semibold">Jenis</label>
                                <select name="type" class="form-select form-select-sm" required id="typeSelectModal">
                                    <option value="payment" @selected(old('type', 'payment') === 'payment')>Pelunasan</option>
                                    <option value="dp" @selected(old('type') === 'dp')>DP (Uang Muka)</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Metode <span class="text-danger">*</span></label>
                                <select name="payment_method_id" class="form-select form-select-sm" required id="pmSelectModal">
                                    @foreach ($paymentMethods ?? [] as $pmOpt)
                                        <option value="{{ $pmOpt->id }}"
                                            data-mode="{{ strtolower($pmOpt->mode ?? '') }}"
                                            data-code="{{ strtoupper($pmOpt->code ?? '') }}"
                                            @selected(old('payment_method_id', $order->payment_method_id) == $pmOpt->id)>
                                            {{ $pmOpt->name }}
                                            @if (!empty($pmOpt->mode))— {{ strtoupper($pmOpt->mode) }}@endif
                                        </option>
                                    @endforeach
                                </select>
                                <div class="text-danger small mt-1 d-none" id="creditPayErrModal">
                                    TEMPO/CREDIT hanya untuk DP, bukan pelunasan.
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Kas/Bank <span class="text-danger">*</span></label>

                                <div id="cashWrap" class="d-none">
                                    <select name="cash_account_id" class="form-select form-select-sm" id="cashSelectCash">
                                        <option value="">— Pilih Kas —</option>
                                        @if ($cash1101)
                                            <option value="{{ $cash1101->id }}" data-code="1101"
                                                @selected(old('cash_account_id') == $cash1101->id)>
                                                1101 — {{ $cash1101->name }}
                                            </option>
                                        @endif
                                    </select>
                                    <div class="text-danger small mt-1 d-none" id="cashErrModal">Wajib pilih akun 1101 (Kas).</div>
                                </div>

                                <div id="bankWrap" class="d-none">
                                    <select name="cash_account_id" class="form-select form-select-sm" id="cashSelectBank">
                                        <option value="">— Pilih Bank/E-Wallet —</option>
                                        @foreach ($transferBanks as $acc)
                                            <option value="{{ $acc->id }}" data-code="{{ $acc->code }}"
                                                @selected(old('cash_account_id') == $acc->id)>
                                                {{ $acc->code }} — {{ $acc->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="text-danger small mt-1 d-none" id="bankErrModal">Wajib pilih akun 1111–1114.</div>
                                </div>

                                <div id="creditWrap" class="d-none">
                                    <input type="text" class="form-control form-control-sm"
                                        value="Tidak perlu kas/bank (TEMPO/CREDIT)" disabled>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Nominal <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" name="amount" class="form-control mono" id="amountInput"
                                        placeholder="0" value="{{ old('amount') }}" required>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnFillRemaining">Sisa</button>
                                </div>
                            </div>

                            <div class="col-6 col-md-3">
                                <label class="form-label small fw-semibold">No. Ref</label>
                                <input type="text" name="ref_no" class="form-control form-control-sm mono" placeholder="Opsional"
                                    value="{{ old('ref_no') }}">
                            </div>

                            <div class="col-6 col-md-3">
                                <label class="form-label small fw-semibold">Catatan</label>
                                <input type="text" name="notes" class="form-control form-control-sm" placeholder="Opsional"
                                    value="{{ old('notes') }}">
                            </div>

                            @php $unpaidInvList = $unpaidInvoices ?? collect(); @endphp
                            @if ($unpaidInvList->isNotEmpty())
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Faktur Supplier <span class="text-muted fw-normal">(opsional)</span></label>
                                <select name="supplier_invoice_id" class="form-select form-select-sm">
                                    <option value="">— Tidak dikaitkan ke faktur —</option>
                                    @foreach ($unpaidInvList as $inv)
                                        <option value="{{ $inv->id }}"
                                            {{ old('supplier_invoice_id') == $inv->id ? 'selected' : '' }}>
                                            {{ $inv->invoice_no }}
                                            @if ($inv->supplier_invoice_ref) [{{ $inv->supplier_invoice_ref }}] @endif
                                            — {{ rupiah($inv->total_amount - $inv->paid_amount) }} outstanding
                                            ({{ strtoupper($inv->status) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                        </div>

                        @if ($errors->any())
                            <div class="alert alert-danger mt-3 mb-0 py-2 small">
                                <div class="fw-semibold mb-1">Gagal:</div>
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $e)
                                        <li>{{ $e }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button class="btn btn-sm btn-primary" id="btnSavePayment">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- =========================================================
    MODAL: APPLY DP (OFFSET)
========================================================= --}}
    <div class="modal fade" id="modalApplyDp" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('purchasing.purchase_orders.payments.apply_dp', $order->id) }}"
                class="modal-content gf-modal" id="applyDpForm">
                @csrf

                <div class="modal-header">
                    <div>
                        <h6 class="modal-title fw-semibold mb-0">Offset DP</h6>
                        <div class="d-flex gap-1 flex-wrap mt-2">
                            <span class="modal-kpi">DP <strong class="mono">{{ rupiah($dpAvailable) }}</strong></span>
                            @if ($returnPostedTotal > 0.0001)
                                <span class="modal-kpi">Retur <strong class="mono">{{ rupiah($returnPostedTotal) }}</strong></span>
                            @endif
                            <span class="modal-kpi">Sisa <strong class="mono">{{ rupiah($apOutstanding) }}</strong></span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Tanggal</label>
                            <input type="text" name="date" class="form-control form-control-sm gf-date-input"
                                value="{{ old('date', now()->toDateString()) }}" data-gf-date
                                autocomplete="off" required>
                            @error('date')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-6">
                            <label class="form-label small fw-semibold">Nominal Offset</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="amount" class="form-control mono" id="applyDpAmount"
                                    placeholder="0" value="{{ old('amount') }}" required>
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                    id="btnFillMaxApplyDp">Max</button>
                            </div>
                            @error('amount')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Catatan <span class="text-muted fw-normal">(opsional)</span></label>
                            <input type="text" name="notes" class="form-control form-control-sm" maxlength="255"
                                value="{{ old('notes') }}">
                            @error('notes')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary"
                        @if (!$canApplyDp) disabled @endif>Proses Offset</button>
                </div>
            </form>
        </div>
    </div>

    {{-- =========================================================
    MODAL: VOID PAYMENT + REASON
========================================================= --}}
    <div class="modal fade" id="modalVoidPayment" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="" class="modal-content gf-modal" id="voidPaymentForm">
                @csrf

                <div class="modal-header">
                    <div>
                        <h6 class="modal-title fw-semibold mb-0">Void Pembayaran</h6>
                        <div class="text-muted small mono mt-1" id="voidInfo">—</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <label class="form-label small fw-semibold">Alasan Void</label>
                    <input type="text" name="reason" class="form-control form-control-sm" maxlength="255"
                        placeholder="contoh: salah nominal / duplikat / salah metode" value="{{ old('reason') }}"
                        required>
                    @error('reason')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-danger">Void</button>
                </div>
            </form>
        </div>
    </div>



    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // =========================================================
                // ADD PAYMENT (existing logic kamu)
                // =========================================================
                const pm = document.getElementById('pmSelectModal');
                const btnSave = document.getElementById('btnSavePayment');
                const grnPostedTotal = {{ (float) $grnPostedTotal }};

                if (grnPostedTotal <= 0.0001 && btnSave) {
                    btnSave.disabled = true;
                }

                const cashWrap = document.getElementById('cashWrap');
                const bankWrap = document.getElementById('bankWrap');
                const creditWrap = document.getElementById('creditWrap');

                const cashSelectCash = document.getElementById('cashSelectCash');
                const cashSelectBank = document.getElementById('cashSelectBank');

                const bankErr = document.getElementById('bankErrModal');
                const cashErr = document.getElementById('cashErrModal');
                const creditPayErr = document.getElementById('creditPayErrModal');

                const amountInput = document.getElementById('amountInput');
                const btnFill = document.getElementById('btnFillRemaining');

                const remaining = {{ (float) $apOutstanding }};
                const typeSelect = document.getElementById('typeSelectModal');

                function fmtId(n) {
                    return new Intl.NumberFormat('id-ID', {
                        maximumFractionDigits: 0
                    }).format(Math.round(n || 0));
                }

                function detectMode() {
                    const opt = pm?.selectedOptions?.[0];
                    const mode = (opt?.dataset?.mode || '').toLowerCase();
                    const code = (opt?.dataset?.code || '').toUpperCase();

                    if (mode) return mode;
                    if (code.includes('CASH')) return 'cash';
                    if (code.includes('TRF') || code.includes('TRANSFER') || code.includes('BANK')) return 'transfer';
                    if (code.includes('TEMPO') || code.includes('CREDIT')) return 'credit';
                    return '';
                }

                function showOnly(which) {
                    cashWrap?.classList.toggle('d-none', which !== 'cash');
                    bankWrap?.classList.toggle('d-none', which !== 'bank');
                    creditWrap?.classList.toggle('d-none', which !== 'credit');
                }

                function setBankError(show) {
                    if (bankErr) bankErr.classList.toggle('d-none', !show);
                }

                function setCashError(show) {
                    if (cashErr) cashErr.classList.toggle('d-none', !show);
                }

                function setCreditPayError(show) {
                    if (creditPayErr) creditPayErr.classList.toggle('d-none', !show);
                }

                function autoPickCash() {
                    if (!cashSelectCash) return;
                    if (!cashSelectCash.value && cashSelectCash.options.length > 1) cashSelectCash.selectedIndex = 1;
                }

                function autoPickBankJago() {
                    if (!cashSelectBank) return;
                    if (cashSelectBank.value) return;

                    const opts = Array.from(cashSelectBank.options);
                    const jago = opts.find(o => (o.dataset.code || '') === '1111' || (o.textContent || '').includes(
                        '1111'));
                    if (jago) {
                        cashSelectBank.value = jago.value;
                        return;
                    }
                    if (cashSelectBank.options.length > 1) cashSelectBank.selectedIndex = 1;
                }

                function validateCashBank() {
                    const mode = detectMode();
                    if (mode === 'credit') {
                        setBankError(false);
                        setCashError(false);
                        return true;
                    }
                    if (mode === 'transfer') {
                        const ok = !!(cashSelectBank && cashSelectBank.value);
                        setBankError(!ok);
                        setCashError(false);
                        return ok;
                    }
                    const ok = !!(cashSelectCash && cashSelectCash.value);
                    setCashError(!ok);
                    setBankError(false);
                    return ok;
                }

                function validateTypeVsMode() {
                    const mode = detectMode();
                    const type = (typeSelect?.value || 'payment');
                    if (type === 'payment' && mode === 'credit') {
                        setCreditPayError(true);
                        if (btnSave) btnSave.disabled = true;
                        return false;
                    }
                    setCreditPayError(false);
                    return true;
                }

                function applyAllValidations() {
                    const okCashBank = validateCashBank();
                    const okTypeMode = validateTypeVsMode();
                    if (btnSave) btnSave.disabled = !(okCashBank && okTypeMode);
                }

                function syncModeUI() {
                    const mode = detectMode();

                    if (mode === 'credit') {
                        showOnly('credit');
                        if (cashSelectCash) cashSelectCash.value = '';
                        if (cashSelectBank) cashSelectBank.value = '';
                        setBankError(false);
                        setCashError(false);
                        applyAllValidations();
                        return;
                    }

                    if (mode === 'transfer') {
                        showOnly('bank');
                        autoPickBankJago();
                        applyAllValidations();
                        return;
                    }

                    showOnly('cash');
                    autoPickCash();
                    applyAllValidations();
                }

                function syncTypeRules() {
  // tidak ada rule khusus lagi
  applyAllValidations();
}


                btnFill?.addEventListener('click', function() {
                    if (!amountInput) return;
                    amountInput.value = fmtId(remaining);
                    amountInput.focus();
                    amountInput.select?.();
                });

                amountInput?.addEventListener('focusout', function() {
                    const raw = (amountInput.value || '').toString().trim();
                    if (raw === '') return;
                    const n = Number(raw.replace(/\./g, '').replace(/,/g, '.'));
                    if (!isNaN(n)) amountInput.value = fmtId(n);
                });

                typeSelect?.addEventListener('change', syncTypeRules);
                pm?.addEventListener('change', syncModeUI);
                cashSelectBank?.addEventListener('change', applyAllValidations);
                cashSelectCash?.addEventListener('change', applyAllValidations);

                // init
                syncTypeRules();
                syncModeUI();

                // =========================================================
                // APPLY DP modal helpers
                // =========================================================
                const applyDpAmount = document.getElementById('applyDpAmount');
                const btnFillMaxApplyDp = document.getElementById('btnFillMaxApplyDp');
                const maxApplyDp = {{ (float) $maxApplyDp }};

                btnFillMaxApplyDp?.addEventListener('click', function() {
                    if (!applyDpAmount) return;
                    applyDpAmount.value = fmtId(maxApplyDp);
                    applyDpAmount.focus();
                    applyDpAmount.select?.();
                });

                applyDpAmount?.addEventListener('focusout', function() {
                    const raw = (applyDpAmount.value || '').toString().trim();
                    if (raw === '') return;
                    const n = Number(raw.replace(/\./g, '').replace(/,/g, '.'));
                    if (!isNaN(n)) applyDpAmount.value = fmtId(n);
                });

                // =========================================================
                // VOID modal wiring
                // =========================================================
                const modalVoidEl = document.getElementById('modalVoidPayment');
                const voidForm = document.getElementById('voidPaymentForm');
                const voidInfo = document.getElementById('voidInfo');
                const actionTpl = @json($voidActionTemplate);

                modalVoidEl?.addEventListener('show.bs.modal', function(event) {
                    const btn = event.relatedTarget;
                    if (!btn) return;

                    const pid = btn.getAttribute('data-payment-id');
                    const ptype = btn.getAttribute('data-payment-type') || '-';
                    const pamt = btn.getAttribute('data-payment-amount') || '-';
                    const pdate = btn.getAttribute('data-payment-date') || '-';

                    if (voidForm) voidForm.action = actionTpl.replace('___PAYMENT___', pid);
                    if (voidInfo) voidInfo.textContent = `${ptype} • ${pamt} • ${pdate}`;

                    const reasonInput = voidForm?.querySelector('input[name="reason"]');
                    if (reasonInput) reasonInput.value = '';
                });

                // =========================================================
                // Auto open modal if validation errors (best-effort)
                // =========================================================
                const hasErrors = {{ $errors->any() ? 'true' : 'false' }};

                if (hasErrors && window.bootstrap) {
                    // Heuristic:
                    // - if reason error exists -> open void modal and expand history
                    // - else if amount/date/notes and request came from apply dp -> open apply dp modal
                    // - else open add payment modal
                    const reasonErr = {!! json_encode($errors->has('reason')) !!};
                    const amountErr = {!! json_encode($errors->has('amount')) !!};
                    const dateErr = {!! json_encode($errors->has('date')) !!};

                    const hist = document.getElementById('paymentHistoryCollapse');
                    if (hist) new bootstrap.Collapse(hist, {
                        toggle: true
                    });

                    if (reasonErr) {
                        const el = document.getElementById('modalVoidPayment');
                        if (el) new bootstrap.Modal(el).show();
                        return;
                    }

                    // apply dp biasanya error amount/date juga, tapi add payment juga.
                    // trigger jika user terakhir tekan tombol offset dp (kita pakai session flash flag kalau ada)
                    const fromApplyDp = {!! json_encode((bool) session('from_apply_dp')) !!};

                    if (fromApplyDp) {
                        const el = document.getElementById('modalApplyDp');
                        if (el) new bootstrap.Modal(el).show();
                        return;
                    }

                    // default: add payment
                    const addModalEl = document.getElementById('modalAddPayment');
                    if (addModalEl) new bootstrap.Modal(addModalEl).show();
                }
            });
        </script>


    @endpush
    @endif
@endsection
