@extends('layouts.app')

@section('title', 'Dashboard Purchasing')

@push('head')
<style>
    .dash-wrap { max-width: 1100px; margin-inline: auto; padding-bottom: 3rem; }

    /* KPI Cards */
    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: .75rem; }
    @media (max-width: 576px) { .kpi-grid { grid-template-columns: repeat(2, 1fr); } }

    .kpi-card {
        border-radius: 12px;
        padding: 1rem .9rem;
        background: var(--card-bg, #fff);
        border: 1px solid var(--line, #e5e7eb);
        cursor: pointer;
        transition: box-shadow .15s, transform .1s;
        text-decoration: none;
        color: inherit;
        display: block;
    }
    .kpi-card:hover { box-shadow: 0 4px 18px rgba(0,0,0,.10); transform: translateY(-1px); }
    .kpi-card .kpi-num  { font-size: 2rem; font-weight: 700; line-height: 1.1; }
    .kpi-card .kpi-label { font-size: .75rem; color: var(--muted, #64748b); margin-top: .2rem; line-height: 1.3; }
    .kpi-card .kpi-sub  { font-size: .7rem; color: var(--muted); margin-top: .15rem; }

    .kpi-danger  { border-left: 4px solid #ef4444; }
    .kpi-warn    { border-left: 4px solid #f59e0b; }
    .kpi-info    { border-left: 4px solid #3b82f6; }
    .kpi-success { border-left: 4px solid #22c55e; }
    .kpi-neutral { border-left: 4px solid #94a3b8; }

    /* Section */
    .dash-section { margin-top: 1.5rem; }
    .dash-section-title {
        font-size: .72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: var(--muted, #64748b);
        margin-bottom: .6rem;
    }

    /* Tables */
    .dash-table { width: 100%; font-size: .82rem; border-collapse: collapse; }
    .dash-table th { font-weight: 600; font-size: .72rem; text-transform: uppercase; color: var(--muted); padding: .4rem .5rem; border-bottom: 2px solid var(--line, #e5e7eb); }
    .dash-table td { padding: .45rem .5rem; border-bottom: 1px solid var(--line, #e5e7eb); vertical-align: middle; }
    .dash-table tr:last-child td { border-bottom: none; }
    .dash-table tr:hover td { background: rgba(59,130,246,.04); }

    .mono { font-variant-numeric: tabular-nums; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono"; }

    /* Status badges */
    .pill { border-radius: 999px; font-size: .68rem; padding: .1rem .55rem; border: 1px solid transparent; display: inline-block; white-space: nowrap; }
    .pill-draft    { background: rgba(148,163,184,.12); color: #64748b; border-color: rgba(148,163,184,.4); }
    .pill-approved { background: rgba(59,130,246,.10); color: #1d4ed8; border-color: rgba(59,130,246,.4); }
    .pill-partial  { background: rgba(234,179,8,.12);  color: #a16207; border-color: rgba(234,179,8,.4); }
    .pill-full     { background: rgba(22,163,74,.12);  color: #15803d; border-color: rgba(22,163,74,.4); }
    .pill-danger   { background: rgba(220,38,38,.10);  color: #b91c1c; border-color: rgba(220,38,38,.4); }
    .pill-paid     { background: rgba(22,163,74,.12);  color: #15803d; border-color: rgba(22,163,74,.4); }
    .pill-unpaid   { background: rgba(220,38,38,.10);  color: #b91c1c; border-color: rgba(220,38,38,.4); }

    /* Blocker badges */
    .blocker-list { display: flex; flex-wrap: wrap; gap: .25rem; }
    .blocker-tag  { font-size: .65rem; background: rgba(234,179,8,.15); color: #92400e; border: 1px solid rgba(234,179,8,.4); border-radius: 4px; padding: .1rem .4rem; }

    /* Collapsible table sections */
    .dash-card { background: var(--card-bg,#fff); border: 1px solid var(--line,#e5e7eb); border-radius: 12px; overflow: hidden; margin-bottom: .75rem; }
    .dash-card-header { display: flex; justify-content: space-between; align-items: center; padding: .65rem .9rem; cursor: pointer; font-size: .83rem; font-weight: 600; border-bottom: 1px solid var(--line,#e5e7eb); }
    .dash-card-header:hover { background: rgba(59,130,246,.04); }
    .dash-card-body { padding: 0; overflow-x: auto; }
    .empty-hint { text-align: center; color: var(--muted); font-size: .82rem; padding: 1.2rem; }

    /* Link style in table */
    .tbl-link { color: inherit; text-decoration: none; font-weight: 500; }
    .tbl-link:hover { text-decoration: underline; color: #2563eb; }
</style>
@endpush

@section('content')
<div class="dash-wrap px-3 py-4">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h5 fw-bold mb-0">Dashboard Purchasing</h1>
            <div class="text-muted small">{{ now()->isoFormat('dddd, D MMMM Y') }}</div>
        </div>
        <a href="{{ route('purchasing.purchase_orders.index') }}" class="btn btn-sm btn-outline-secondary">
            ≡ Semua PO
        </a>
    </div>

    {{-- ================================================================
    KPI CARDS
    ================================================================ --}}
    <div class="kpi-grid mb-3">

        {{-- 1. PO Draft --}}
        <a href="{{ route('purchasing.purchase_orders.index', ['status' => 'draft']) }}" class="kpi-card kpi-neutral">
            <div class="kpi-num">{{ $poDraftCount }}</div>
            <div class="kpi-label">PO Draft</div>
        </a>

        {{-- 2. Approved belum terima --}}
        <a href="{{ route('purchasing.purchase_orders.index', ['received_status' => 'not_received']) }}" class="kpi-card kpi-warn">
            <div class="kpi-num">{{ $poApprovedNotReceivedCount }}</div>
            <div class="kpi-label">Belum Diterima</div>
            <div class="kpi-sub">PO approved</div>
        </a>

        {{-- 3. Partial received --}}
        <a href="{{ route('purchasing.purchase_orders.index', ['received_status' => 'partial']) }}" class="kpi-card kpi-warn">
            <div class="kpi-num">{{ $poPartialCount }}</div>
            <div class="kpi-label">Terima Sebagian</div>
        </a>

        {{-- 4. Fully received belum invoice --}}
        @if ($hasInvoiceTable)
        <a href="#sectionFullyNoInv" class="kpi-card kpi-info">
            <div class="kpi-num">{{ $poFullyNoInvoiceCount }}</div>
            <div class="kpi-label">Belum Ada Invoice</div>
            <div class="kpi-sub">sudah fully received</div>
        </a>
        @endif

        {{-- 5. Invoice outstanding --}}
        @if ($hasInvoiceTable)
        <a href="{{ route('purchasing.supplier_invoices.index') }}" class="kpi-card kpi-danger">
            <div class="kpi-num">{{ $invOutstandingCount }}</div>
            <div class="kpi-label">Invoice Outstanding</div>
            @if ($canSeeMoney && $invOutstandingTotal > 0)
                <div class="kpi-sub mono">{{ rupiah($invOutstandingTotal) }}</div>
            @endif
        </a>
        @endif

        {{-- 6. Invoice jatuh tempo --}}
        @if ($hasInvoiceTable && $invOverdueCount > 0)
        <a href="{{ route('purchasing.supplier_invoices.index', ['status' => 'posted']) }}" class="kpi-card kpi-danger">
            <div class="kpi-num text-danger">{{ $invOverdueCount }}</div>
            <div class="kpi-label">Invoice Jatuh Tempo</div>
        </a>
        @endif

        {{-- 7. Siap Close --}}
        <a href="#sectionReadyClose" class="kpi-card kpi-success">
            <div class="kpi-num">{{ $poReadyCloseCount }}</div>
            <div class="kpi-label">Siap Close</div>
        </a>

        {{-- 8. Closed bulan ini --}}
        <div class="kpi-card kpi-neutral" style="cursor:default;">
            <div class="kpi-num">{{ $poClosedMonthCount }}</div>
            <div class="kpi-label">Closed Bulan Ini</div>
        </div>

    </div>

    {{-- ================================================================
    SECTION: PO Draft
    ================================================================ --}}
    @if ($poDraftList->isNotEmpty())
    <div class="dash-section">
        <div class="dash-section-title">PO Draft — perlu approval</div>
        <div class="dash-card">
            <div class="dash-card-body">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>Kode PO</th>
                            <th>Supplier</th>
                            <th>Tanggal</th>
                            @if ($canSeeMoney)<th class="text-end">Total</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($poDraftList as $po)
                        <tr>
                            <td>
                                <a href="{{ route('purchasing.purchase_orders.show', $po->id) }}" class="tbl-link mono">
                                    {{ $po->code }}
                                </a>
                            </td>
                            <td>{{ optional($po->supplier)->name ?? '—' }}</td>
                            <td class="mono text-muted">{{ id_date($po->date) }}</td>
                            @if ($canSeeMoney)
                            <td class="text-end mono">{{ rupiah($po->grand_total) }}</td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- ================================================================
    SECTION: PO Belum Diterima
    ================================================================ --}}
    @if ($poApprovedNotReceivedList->isNotEmpty())
    <div class="dash-section">
        <div class="dash-section-title">PO Approved — belum ada GRN</div>
        <div class="dash-card">
            <div class="dash-card-body">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>Kode PO</th>
                            <th>Supplier</th>
                            <th>Tanggal</th>
                            @if ($canSeeMoney)<th class="text-end">Total</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($poApprovedNotReceivedList as $po)
                        <tr>
                            <td>
                                <a href="{{ route('purchasing.purchase_orders.show', $po->id) }}" class="tbl-link mono">
                                    {{ $po->code }}
                                </a>
                            </td>
                            <td>{{ optional($po->supplier)->name ?? '—' }}</td>
                            <td class="mono text-muted">{{ id_date($po->date) }}</td>
                            @if ($canSeeMoney)
                            <td class="text-end mono">{{ rupiah($po->grand_total) }}</td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- ================================================================
    SECTION: PO Partial Received
    ================================================================ --}}
    @if ($poPartialList->isNotEmpty())
    <div class="dash-section">
        <div class="dash-section-title">PO Terima Sebagian — masih ada sisa</div>
        <div class="dash-card">
            <div class="dash-card-body">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>Kode PO</th>
                            <th>Supplier</th>
                            <th>Tanggal</th>
                            @if ($canSeeMoney)<th class="text-end">Total</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($poPartialList as $po)
                        <tr>
                            <td>
                                <a href="{{ route('purchasing.purchase_orders.show', $po->id) }}" class="tbl-link mono">
                                    {{ $po->code }}
                                </a>
                            </td>
                            <td>{{ optional($po->supplier)->name ?? '—' }}</td>
                            <td class="mono text-muted">{{ id_date($po->date) }}</td>
                            @if ($canSeeMoney)
                            <td class="text-end mono">{{ rupiah($po->grand_total) }}</td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- ================================================================
    SECTION: Fully Received tapi belum invoice
    ================================================================ --}}
    @if ($hasInvoiceTable && $poFullyNoInvoiceList->isNotEmpty())
    <div class="dash-section" id="sectionFullyNoInv">
        <div class="dash-section-title">Sudah Fully Received — belum ada Faktur Supplier</div>
        <div class="dash-card">
            <div class="dash-card-body">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>Kode PO</th>
                            <th>Supplier</th>
                            <th>Tanggal</th>
                            @if ($canSeeMoney)<th class="text-end">Total PO</th>@endif
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($poFullyNoInvoiceList as $po)
                        <tr>
                            <td>
                                <a href="{{ route('purchasing.purchase_orders.show', $po->id) }}" class="tbl-link mono">
                                    {{ $po->code }}
                                </a>
                            </td>
                            <td>{{ optional($po->supplier)->name ?? '—' }}</td>
                            <td class="mono text-muted">{{ id_date($po->date) }}</td>
                            @if ($canSeeMoney)
                            <td class="text-end mono">{{ rupiah($po->grand_total) }}</td>
                            @endif
                            <td>
                                @if ($isOwner || in_array(auth()->user()?->role ?? '', ['accounting','developer']))
                                    @if (\Illuminate\Support\Facades\Route::has('purchasing.supplier_invoices.create'))
                                        <a href="{{ route('purchasing.supplier_invoices.create', ['purchase_order_id' => $po->id]) }}"
                                           class="btn btn-xs btn-outline-success" style="font-size:.72rem;padding:.15rem .5rem;">
                                            + Faktur
                                        </a>
                                    @endif
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- ================================================================
    SECTION: Invoice Outstanding
    ================================================================ --}}
    @if ($hasInvoiceTable && $invOutstandingList->isNotEmpty())
    <div class="dash-section">
        <div class="dash-section-title">
            Faktur Supplier Outstanding
            @if ($canSeeMoney && $invOutstandingTotal > 0)
                <span class="text-danger ms-1 mono" style="font-size:.8rem;">
                    Total: {{ rupiah($invOutstandingTotal) }}
                </span>
            @endif
        </div>
        <div class="dash-card">
            <div class="dash-card-body">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>No. Invoice</th>
                            <th>Supplier</th>
                            <th>Jatuh Tempo</th>
                            <th>Status</th>
                            @if ($canSeeMoney)<th class="text-end">Outstanding</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invOutstandingList as $inv)
                        @php
                            $isOverdue = $inv->due_date && $inv->due_date < now();
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('purchasing.supplier_invoices.show', $inv->id) }}" class="tbl-link mono">
                                    {{ $inv->invoice_no }}
                                </a>
                            </td>
                            <td>{{ optional($inv->supplier)->name ?? '—' }}</td>
                            <td class="mono {{ $isOverdue ? 'text-danger fw-semibold' : 'text-muted' }}">
                                {{ $inv->due_date ? id_date($inv->due_date) : '—' }}
                                @if ($isOverdue) <span class="pill pill-danger">Lewat</span> @endif
                            </td>
                            <td>
                                <span class="pill {{ $inv->status === 'partial_paid' ? 'pill-partial' : 'pill-approved' }}">
                                    {{ strtoupper($inv->status) }}
                                </span>
                            </td>
                            @if ($canSeeMoney)
                            <td class="text-end mono {{ $isOverdue ? 'text-danger' : '' }}">
                                {{ rupiah(max(0, $inv->total_amount - $inv->paid_amount)) }}
                            </td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- ================================================================
    SECTION: PO Siap Close
    ================================================================ --}}
    @if ($poReadyCloseList->isNotEmpty())
    <div class="dash-section" id="sectionReadyClose">
        <div class="dash-section-title">✅ PO Siap Close — semua syarat terpenuhi</div>
        <div class="dash-card">
            <div class="dash-card-body">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>Kode PO</th>
                            <th>Supplier</th>
                            <th>Tanggal</th>
                            @if ($canSeeMoney)<th class="text-end">Total</th>@endif
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($poReadyCloseList as $po)
                        <tr>
                            <td>
                                <a href="{{ route('purchasing.purchase_orders.show', $po->id) }}" class="tbl-link mono">
                                    {{ $po->code }}
                                </a>
                            </td>
                            <td>{{ optional($po->supplier)->name ?? '—' }}</td>
                            <td class="mono text-muted">{{ id_date($po->date) }}</td>
                            @if ($canSeeMoney)
                            <td class="text-end mono">{{ rupiah($po->grand_total) }}</td>
                            @endif
                            <td>
                                @if ($isOwner && \Illuminate\Support\Facades\Route::has('purchasing.purchase_orders.close'))
                                    <form method="POST"
                                          action="{{ route('purchasing.purchase_orders.close', $po->id) }}"
                                          onsubmit="return confirm('Close PO {{ $po->code }}?')">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-dark"
                                                style="font-size:.72rem;padding:.15rem .5rem;">
                                            ✔ Close
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- ================================================================
    SECTION: PO Belum Bisa Close (dengan alasan)
    ================================================================ --}}
    @if ($poNotReadyCloseList->isNotEmpty())
    <div class="dash-section">
        <div class="dash-section-title">⛔ PO Belum Bisa Close — ada yang perlu diselesaikan</div>
        <div class="dash-card">
            <div class="dash-card-body">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>Kode PO</th>
                            <th>Supplier</th>
                            <th>Tanggal</th>
                            <th>Alasan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($poNotReadyCloseList as $po)
                        <tr>
                            <td>
                                <a href="{{ route('purchasing.purchase_orders.show', $po->id) }}" class="tbl-link mono">
                                    {{ $po->code }}
                                </a>
                            </td>
                            <td>{{ optional($po->supplier)->name ?? '—' }}</td>
                            <td class="mono text-muted">{{ id_date($po->date) }}</td>
                            <td>
                                <div class="blocker-list">
                                    @foreach ($po->_blockers as $blk)
                                        <span class="blocker-tag">{{ $blk }}</span>
                                    @endforeach
                                    @if (empty($po->_blockers))
                                        <span class="text-muted small">—</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Empty state --}}
    @if ($poDraftCount + $poApprovedNotReceivedCount + $poPartialCount + $invOutstandingCount === 0)
    <div class="text-center py-5 text-muted">
        <div style="font-size:2.5rem;">✅</div>
        <div class="fw-semibold mt-2">Semua bersih!</div>
        <div class="small">Tidak ada PO atau invoice yang perlu perhatian.</div>
    </div>
    @endif

</div>
@endsection
