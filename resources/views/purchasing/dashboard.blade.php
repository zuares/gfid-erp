@extends('layouts.app')

@section('title', 'Dashboard Purchasing')

@push('head')
<style>
    .dash-wrap { max-width: 1100px; margin-inline: auto; padding-bottom: 3rem; }

    /* KPI Cards */
    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: .65rem; }
    @media (max-width: 576px) { .kpi-grid { grid-template-columns: repeat(2, 1fr); } }

    .kpi-card {
        border-radius: 12px;
        padding: .9rem .85rem;
        background: var(--card-bg, #fff);
        border: 1px solid var(--line, #e5e7eb);
        cursor: pointer;
        transition: box-shadow .15s, transform .1s;
        text-decoration: none;
        color: inherit;
        display: block;
    }
    .kpi-card:hover { box-shadow: 0 4px 18px rgba(0,0,0,.10); transform: translateY(-1px); }
    .kpi-card .kpi-num   { font-size: 1.85rem; font-weight: 700; line-height: 1.1; }
    .kpi-card .kpi-label { font-size: .73rem; color: var(--muted, #64748b); margin-top: .2rem; line-height: 1.3; }
    .kpi-card .kpi-sub   { font-size: .68rem; color: var(--muted); margin-top: .15rem; }

    .kpi-danger  { border-left: 4px solid #ef4444; }
    .kpi-warn    { border-left: 4px solid #f59e0b; }
    .kpi-info    { border-left: 4px solid #3b82f6; }
    .kpi-success { border-left: 4px solid #22c55e; }
    .kpi-neutral { border-left: 4px solid #94a3b8; }
    .kpi-purple  { border-left: 4px solid #8b5cf6; }

    /* Section */
    .dash-section { margin-top: 1.5rem; }
    .dash-section-title {
        font-size: .72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: var(--muted, #64748b);
        margin-bottom: .6rem;
        display: flex;
        align-items: center;
        gap: .4rem;
    }

    /* Tables */
    .dash-card { background: var(--card-bg,#fff); border: 1px solid var(--line,#e5e7eb); border-radius: 12px; overflow: hidden; margin-bottom: .75rem; }
    .dash-card-body { padding: 0; overflow-x: auto; }
    .dash-table { width: 100%; font-size: .82rem; border-collapse: collapse; min-width: 500px; }
    .dash-table th { font-weight: 600; font-size: .70rem; text-transform: uppercase; color: var(--muted); padding: .5rem .6rem; border-bottom: 2px solid var(--line, #e5e7eb); white-space: nowrap; }
    .dash-table td { padding: .45rem .6rem; border-bottom: 1px solid var(--line, #e5e7eb); vertical-align: middle; }
    .dash-table tr:last-child td { border-bottom: none; }
    .dash-table tr:hover td { background: rgba(59,130,246,.04); }

    .mono { font-variant-numeric: tabular-nums; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace; }

    /* Badges */
    .pill { border-radius: 999px; font-size: .67rem; padding: .1rem .5rem; border: 1px solid transparent; display: inline-block; white-space: nowrap; }
    .pill-draft     { background: rgba(148,163,184,.12); color: #64748b; border-color: rgba(148,163,184,.4); }
    .pill-approved  { background: rgba(59,130,246,.10);  color: #1d4ed8; border-color: rgba(59,130,246,.4); }
    .pill-converted { background: rgba(99,102,241,.10);  color: #4338ca; border-color: rgba(99,102,241,.4); }
    .pill-partial   { background: rgba(234,179,8,.12);   color: #a16207; border-color: rgba(234,179,8,.4); }
    .pill-full      { background: rgba(22,163,74,.12);   color: #15803d; border-color: rgba(22,163,74,.4); }
    .pill-danger    { background: rgba(220,38,38,.10);   color: #b91c1c; border-color: rgba(220,38,38,.4); }
    .pill-paid      { background: rgba(22,163,74,.12);   color: #15803d; border-color: rgba(22,163,74,.4); }
    .pill-unpaid    { background: rgba(220,38,38,.10);   color: #b91c1c; border-color: rgba(220,38,38,.4); }

    /* Blocker tags */
    .blocker-list { display: flex; flex-wrap: wrap; gap: .25rem; }
    .blocker-tag  { font-size: .64rem; background: rgba(234,179,8,.15); color: #92400e; border: 1px solid rgba(234,179,8,.4); border-radius: 4px; padding: .1rem .4rem; }

    /* Link */
    .tbl-link { color: inherit; text-decoration: none; font-weight: 500; }
    .tbl-link:hover { text-decoration: underline; color: #2563eb; }

    /* Recent activity */
    .activity-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px,1fr)); gap: .75rem; }
    .activity-card { background: var(--card-bg,#fff); border: 1px solid var(--line,#e5e7eb); border-radius: 12px; overflow: hidden; }
    .activity-card-header { padding: .55rem .8rem; font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); border-bottom: 1px solid var(--line,#e5e7eb); }
    .activity-row { display: flex; justify-content: space-between; align-items: center; padding: .4rem .8rem; border-bottom: 1px solid var(--line,#e5e7eb); font-size: .8rem; gap: .5rem; }
    .activity-row:last-child { border-bottom: none; }
    .activity-code { font-weight: 500; white-space: nowrap; }
    .activity-meta { font-size: .72rem; color: var(--muted); white-space: nowrap; }
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
        <div class="d-flex gap-2 flex-wrap">
            @if (Route::has('purchasing.purchase_requests.index') && $hasPrTable)
            <a href="{{ route('purchasing.purchase_requests.index') }}" class="btn btn-sm btn-outline-secondary">
                📋 PR
            </a>
            @endif
            <a href="{{ route('purchasing.purchase_orders.index') }}" class="btn btn-sm btn-outline-secondary">
                🧾 PO
            </a>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════
    KPI CARDS — 10 kartu
    ════════════════════════════════════════════════════════════ --}}
    <div class="kpi-grid mb-3">

        {{-- KPI 1: PR Draft --}}
        @if ($hasPrTable)
        <a href="{{ route('purchasing.purchase_requests.index', ['status' => 'draft']) }}"
           class="kpi-card kpi-neutral">
            <div class="kpi-num">{{ $prDraftCount }}</div>
            <div class="kpi-label">PR Draft</div>
            <div class="kpi-sub">menunggu submit</div>
        </a>
        @endif

        {{-- KPI 2: PR Approved belum convert --}}
        @if ($hasPrTable)
        <a href="{{ route('purchasing.purchase_requests.index', ['status' => 'approved']) }}"
           class="kpi-card {{ $prApprovedNotConvertedCount > 0 ? 'kpi-purple' : 'kpi-neutral' }}">
            <div class="kpi-num">{{ $prApprovedNotConvertedCount }}</div>
            <div class="kpi-label">PR Belum Convert</div>
            <div class="kpi-sub">approved → PO</div>
        </a>
        @endif

        {{-- KPI 3: PO Approved belum diterima --}}
        <a href="{{ route('purchasing.purchase_orders.index', ['received_status' => 'not_received']) }}"
           class="kpi-card {{ $poApprovedNotReceivedCount > 0 ? 'kpi-warn' : 'kpi-neutral' }}">
            <div class="kpi-num">{{ $poApprovedNotReceivedCount }}</div>
            <div class="kpi-label">PO Belum Diterima</div>
            <div class="kpi-sub">approved, 0 GRN</div>
        </a>

        {{-- KPI 4: PO Partial --}}
        <a href="{{ route('purchasing.purchase_orders.index', ['received_status' => 'partial']) }}"
           class="kpi-card {{ $poPartialCount > 0 ? 'kpi-warn' : 'kpi-neutral' }}">
            <div class="kpi-num">{{ $poPartialCount }}</div>
            <div class="kpi-label">Terima Sebagian</div>
            <div class="kpi-sub">masih ada sisa</div>
        </a>

        {{-- KPI 5: Fully received belum invoice --}}
        @if ($hasInvoiceTable)
        <a href="#sectionFullyNoInv" class="kpi-card {{ $poFullyNoInvoiceCount > 0 ? 'kpi-info' : 'kpi-neutral' }}">
            <div class="kpi-num">{{ $poFullyNoInvoiceCount }}</div>
            <div class="kpi-label">Belum Ada Invoice</div>
            <div class="kpi-sub">sudah fully received</div>
        </a>
        @endif

        {{-- KPI 6: Invoice outstanding --}}
        @if ($hasInvoiceTable)
        <a href="{{ route('purchasing.supplier_invoices.index') }}"
           class="kpi-card {{ $invOutstandingCount > 0 ? 'kpi-danger' : 'kpi-neutral' }}">
            <div class="kpi-num">{{ $invOutstandingCount }}</div>
            <div class="kpi-label">Invoice Outstanding</div>
            @if ($canSeeMoney && $invOutstandingTotal > 0)
                <div class="kpi-sub mono">{{ rupiah($invOutstandingTotal) }}</div>
            @endif
        </a>
        @endif

        {{-- KPI 7: Invoice jatuh tempo --}}
        @if ($hasInvoiceTable)
        <a href="{{ route('purchasing.supplier_invoices.index') }}"
           class="kpi-card {{ $invOverdueCount > 0 ? 'kpi-danger' : 'kpi-neutral' }}">
            <div class="kpi-num {{ $invOverdueCount > 0 ? 'text-danger' : '' }}">{{ $invOverdueCount }}</div>
            <div class="kpi-label">Invoice Jatuh Tempo</div>
            <div class="kpi-sub">overdue</div>
        </a>
        @endif

        {{-- KPI 8: PO Siap Close --}}
        <a href="#sectionReadyClose"
           class="kpi-card {{ $poReadyCloseCount > 0 ? 'kpi-success' : 'kpi-neutral' }}">
            <div class="kpi-num">{{ $poReadyCloseCount }}</div>
            <div class="kpi-label">Siap Close</div>
            <div class="kpi-sub">semua syarat terpenuhi</div>
        </a>

        {{-- KPI 9: PO belum bisa close --}}
        <a href="#sectionNotReadyClose"
           class="kpi-card {{ $poNotReadyCloseCount > 0 ? 'kpi-warn' : 'kpi-neutral' }}">
            <div class="kpi-num">{{ $poNotReadyCloseCount }}</div>
            <div class="kpi-label">Belum Bisa Close</div>
            <div class="kpi-sub">ada blocker</div>
        </a>

        {{-- KPI 10: Closed bulan ini --}}
        <div class="kpi-card kpi-neutral" style="cursor:default;">
            <div class="kpi-num">{{ $poClosedMonthCount }}</div>
            <div class="kpi-label">Closed Bulan Ini</div>
            <div class="kpi-sub">{{ now()->isoFormat('MMMM Y') }}</div>
        </div>

    </div>

    {{-- ════════════════════════════════════════════════════════════
    SECTION A — Purchase Request Action Needed
    PR Approved belum convert ke PO
    ════════════════════════════════════════════════════════════ --}}
    @if ($hasPrTable && $prApprovedNotConvertedList->isNotEmpty())
    <div class="dash-section">
        <div class="dash-section-title">
            <span>📋</span>
            <span>Purchase Request — perlu diconvert ke PO</span>
            <span class="ms-auto text-muted fw-normal" style="font-size:.68rem;">{{ $prApprovedNotConvertedList->count() }} item</span>
        </div>
        <div class="dash-card">
            <div class="dash-card-body">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>No PR</th>
                            <th>Tanggal</th>
                            <th>Supplier</th>
                            <th>Requester</th>
                            <th class="text-center">Item</th>
                            @if ($canSeeMoney)<th class="text-end">Est. Total</th>@endif
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($prApprovedNotConvertedList as $pr)
                        @php
                            $estTotal = $pr->lines->sum(fn($l) => ($l->qty ?? 0) * ($l->unit_price ?? 0));
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('purchasing.purchase_requests.show', $pr->id) }}" class="tbl-link mono">
                                    {{ $pr->code }}
                                </a>
                            </td>
                            <td class="mono text-muted">
                                {{ $pr->created_at ? $pr->created_at->format('d/m/Y') : '—' }}
                            </td>
                            <td>{{ $pr->supplier?->name ?? '—' }}</td>
                            <td>{{ $pr->requestedBy?->name ?? '—' }}</td>
                            <td class="text-center">{{ $pr->lines->count() }}</td>
                            @if ($canSeeMoney)
                            <td class="text-end mono">
                                {{ $estTotal > 0 ? rupiah($estTotal) : '—' }}
                            </td>
                            @endif
                            <td class="text-end" style="white-space:nowrap;">
                                <a href="{{ route('purchasing.purchase_requests.show', $pr->id) }}"
                                   class="btn btn-xs btn-outline-secondary me-1"
                                   style="font-size:.72rem;padding:.15rem .5rem;">
                                    Lihat
                                </a>
                                @if (($isOwner || $isAdmin) && Route::has('purchasing.purchase_requests.convert'))
                                <a href="{{ route('purchasing.purchase_requests.show', $pr->id) }}"
                                   class="btn btn-xs btn-outline-primary"
                                   style="font-size:.72rem;padding:.15rem .5rem;">
                                    Convert →PO
                                </a>
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

    {{-- ════════════════════════════════════════════════════════════
    SECTION B — PO Butuh Penerimaan (not_received + partial)
    ════════════════════════════════════════════════════════════ --}}
    @if ($poBelumTerimaList->isNotEmpty())
    <div class="dash-section">
        <div class="dash-section-title">
            <span>📦</span>
            <span>PO Butuh Penerimaan Barang</span>
            <span class="ms-auto text-muted fw-normal" style="font-size:.68rem;">{{ $poBelumTerimaList->count() }} item</span>
        </div>
        <div class="dash-card">
            <div class="dash-card-body">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>No PO</th>
                            <th>Supplier</th>
                            <th>Jenis PO</th>
                            <th>Status Terima</th>
                            <th class="text-center">Item</th>
                            @if ($canSeeMoney)<th class="text-end">Total</th>@endif
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($poBelumTerimaList as $po)
                        <tr>
                            <td>
                                <a href="{{ route('purchasing.purchase_orders.show', $po->id) }}" class="tbl-link mono">
                                    {{ $po->code }}
                                </a>
                            </td>
                            <td>{{ $po->supplier?->name ?? '—' }}</td>
                            <td>
                                @if (function_exists('po_order_type_label'))
                                    {!! po_order_type_label($po->order_type, true) !!}
                                @else
                                    {{ $po->order_type ?? '—' }}
                                @endif
                            </td>
                            <td>
                                @php
                                    $rs = $po->received_status ?? 'not_received';
                                    $rsBadge = match($rs) {
                                        'partial' => 'pill-partial',
                                        'fully_received' => 'pill-full',
                                        default => 'pill-draft',
                                    };
                                @endphp
                                <span class="pill {{ $rsBadge }}">
                                    @if (function_exists('received_status_label'))
                                        {{ received_status_label($rs) }}
                                    @else
                                        {{ $rs }}
                                    @endif
                                </span>
                            </td>
                            <td class="text-center">{{ $po->lines->count() }}</td>
                            @if ($canSeeMoney)
                            <td class="text-end mono">{{ rupiah($po->grand_total) }}</td>
                            @endif
                            <td>
                                <a href="{{ route('purchasing.purchase_orders.show', $po->id) }}"
                                   class="btn btn-xs btn-outline-secondary"
                                   style="font-size:.72rem;padding:.15rem .5rem;">
                                    Lihat
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════
    SECTION: PO Fully Received belum ada Invoice
    ════════════════════════════════════════════════════════════ --}}
    @if ($hasInvoiceTable && $poFullyNoInvoiceList->isNotEmpty())
    <div class="dash-section" id="sectionFullyNoInv">
        <div class="dash-section-title">
            <span>🧾</span>
            <span>Sudah Diterima Penuh — belum ada Faktur Supplier</span>
        </div>
        <div class="dash-card">
            <div class="dash-card-body">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>No PO</th>
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
                            <td>{{ $po->supplier?->name ?? '—' }}</td>
                            <td class="mono text-muted">{{ id_date($po->date) }}</td>
                            @if ($canSeeMoney)
                            <td class="text-end mono">{{ rupiah($po->grand_total) }}</td>
                            @endif
                            <td>
                                <a href="{{ route('purchasing.purchase_orders.show', $po->id) }}"
                                   class="btn btn-xs btn-outline-secondary me-1"
                                   style="font-size:.72rem;padding:.15rem .5rem;">
                                    Lihat
                                </a>
                                @if (($isOwner || $isAdmin || in_array(auth()->user()?->role ?? '', ['accounting','developer'])) && Route::has('purchasing.supplier_invoices.create'))
                                <a href="{{ route('purchasing.supplier_invoices.create', ['purchase_order_id' => $po->id]) }}"
                                   class="btn btn-xs btn-outline-success"
                                   style="font-size:.72rem;padding:.15rem .5rem;">
                                    + Faktur
                                </a>
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

    {{-- ════════════════════════════════════════════════════════════
    SECTION C — Invoice Outstanding
    ════════════════════════════════════════════════════════════ --}}
    @if ($hasInvoiceTable && $invOutstandingList->isNotEmpty())
    <div class="dash-section">
        <div class="dash-section-title">
            <span>💳</span>
            <span>Invoice Outstanding</span>
            @if ($canSeeMoney && $invOutstandingTotal > 0)
                <span class="ms-2 text-danger fw-semibold mono" style="font-size:.75rem;text-transform:none;letter-spacing:0;">
                    {{ rupiah($invOutstandingTotal) }}
                </span>
            @endif
        </div>
        <div class="dash-card">
            <div class="dash-card-body">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>No Invoice</th>
                            <th>Supplier</th>
                            <th>No PO</th>
                            <th>Jatuh Tempo</th>
                            <th>Status</th>
                            @if ($canSeeMoney)
                            <th class="text-end">Total</th>
                            <th class="text-end">Dibayar</th>
                            <th class="text-end">Outstanding</th>
                            @endif
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invOutstandingList as $inv)
                        @php
                            $isOverdue = $inv->due_date && $inv->due_date < now()->startOfDay();
                            $outstanding = max(0, ($inv->total_amount ?? 0) - ($inv->paid_amount ?? 0));
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('purchasing.supplier_invoices.show', $inv->id) }}" class="tbl-link mono">
                                    {{ $inv->invoice_no }}
                                </a>
                            </td>
                            <td>{{ $inv->supplier?->name ?? '—' }}</td>
                            <td class="mono">
                                @if ($inv->purchaseOrder)
                                <a href="{{ route('purchasing.purchase_orders.show', $inv->purchaseOrder->id) }}" class="tbl-link">
                                    {{ $inv->purchaseOrder->code }}
                                </a>
                                @else —
                                @endif
                            </td>
                            <td class="mono {{ $isOverdue ? 'text-danger fw-semibold' : 'text-muted' }}">
                                {{ $inv->due_date ? $inv->due_date->format('d/m/Y') : '—' }}
                                @if ($isOverdue) <span class="pill pill-danger ms-1">Lewat</span> @endif
                            </td>
                            <td>
                                <span class="pill {{ $inv->status === 'partial_paid' ? 'pill-partial' : 'pill-approved' }}">
                                    {{ $inv->status === 'partial_paid' ? 'Sebagian' : 'Belum Bayar' }}
                                </span>
                            </td>
                            @if ($canSeeMoney)
                            <td class="text-end mono">{{ rupiah($inv->total_amount ?? 0) }}</td>
                            <td class="text-end mono">{{ rupiah($inv->paid_amount ?? 0) }}</td>
                            <td class="text-end mono {{ $isOverdue ? 'text-danger' : '' }}">
                                {{ rupiah($outstanding) }}
                            </td>
                            @endif
                            <td>
                                <a href="{{ route('purchasing.supplier_invoices.show', $inv->id) }}"
                                   class="btn btn-xs btn-outline-secondary"
                                   style="font-size:.72rem;padding:.15rem .5rem;">
                                    Lihat
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════
    SECTION D — PO Siap Close
    ════════════════════════════════════════════════════════════ --}}
    @if ($poReadyCloseList->isNotEmpty())
    <div class="dash-section" id="sectionReadyClose">
        <div class="dash-section-title">
            <span>✅</span>
            <span>PO Siap Close — semua syarat terpenuhi</span>
        </div>
        <div class="dash-card">
            <div class="dash-card-body">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>No PO</th>
                            <th>Supplier</th>
                            <th>Tanggal</th>
                            @if ($canSeeMoney)
                            <th class="text-end">Total Invoice</th>
                            <th class="text-end">Dibayar</th>
                            @endif
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($poReadyCloseList as $po)
                        @php
                            $totalInv  = $po->supplierInvoices->sum('total_amount');
                            $totalPaid = $po->supplierInvoices->sum('paid_amount');
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('purchasing.purchase_orders.show', $po->id) }}" class="tbl-link mono">
                                    {{ $po->code }}
                                </a>
                            </td>
                            <td>{{ $po->supplier?->name ?? '—' }}</td>
                            <td class="mono text-muted">{{ id_date($po->date) }}</td>
                            @if ($canSeeMoney)
                            <td class="text-end mono">{{ $totalInv > 0 ? rupiah($totalInv) : rupiah($po->grand_total) }}</td>
                            <td class="text-end mono" style="color:#15803d;">{{ rupiah($totalPaid) }}</td>
                            @endif
                            <td style="white-space:nowrap;">
                                <a href="{{ route('purchasing.purchase_orders.show', $po->id) }}"
                                   class="btn btn-xs btn-outline-secondary me-1"
                                   style="font-size:.72rem;padding:.15rem .5rem;">
                                    Lihat
                                </a>
                                @if ($isOwner && Route::has('purchasing.purchase_orders.close'))
                                <form method="POST"
                                      action="{{ route('purchasing.purchase_orders.close', $po->id) }}"
                                      style="display:inline;"
                                      onsubmit="return confirm('Close PO {{ $po->code }}?\nTindakan ini tidak bisa dibatalkan.')">
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

    {{-- ════════════════════════════════════════════════════════════
    SECTION: PO Belum Bisa Close (dengan alasan)
    ════════════════════════════════════════════════════════════ --}}
    @if ($poNotReadyCloseList->isNotEmpty())
    <div class="dash-section" id="sectionNotReadyClose">
        <div class="dash-section-title">
            <span>⛔</span>
            <span>PO Belum Bisa Close</span>
        </div>
        <div class="dash-card">
            <div class="dash-card-body">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>No PO</th>
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
                            <td>{{ $po->supplier?->name ?? '—' }}</td>
                            <td class="mono text-muted">{{ id_date($po->date) }}</td>
                            <td>
                                <div class="blocker-list">
                                    @forelse ($po->_blockers as $blk)
                                        <span class="blocker-tag">{{ $blk }}</span>
                                    @empty
                                        <span class="text-muted small">—</span>
                                    @endforelse
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

    {{-- ════════════════════════════════════════════════════════════
    SECTION E — Recent Activity
    ════════════════════════════════════════════════════════════ --}}
    @php
        $hasAnyActivity = $recentPr->isNotEmpty()
            || $recentPo->isNotEmpty()
            || $recentInvoices->isNotEmpty()
            || $recentPayments->isNotEmpty();
    @endphp
    @if ($hasAnyActivity)
    <div class="dash-section">
        <div class="dash-section-title"><span>🕐</span><span>Aktivitas Terbaru</span></div>
        <div class="activity-grid">

            {{-- PR Terbaru --}}
            @if ($hasPrTable && $recentPr->isNotEmpty())
            <div class="activity-card">
                <div class="activity-card-header">📋 Purchase Request</div>
                @foreach ($recentPr as $pr)
                <div class="activity-row">
                    <div>
                        <a href="{{ route('purchasing.purchase_requests.show', $pr->id) }}" class="tbl-link activity-code">
                            {{ $pr->code }}
                        </a>
                        <div class="activity-meta">{{ $pr->supplier?->name ?? '—' }} · {{ $pr->requestedBy?->name ?? '—' }}</div>
                    </div>
                    <div class="text-end">
                        @php
                            $prBadge = match($pr->status) {
                                'approved'  => 'pill-approved',
                                'converted' => 'pill-converted',
                                'rejected'  => 'pill-danger',
                                default     => 'pill-draft',
                            };
                        @endphp
                        <span class="pill {{ $prBadge }}">
                            @if (function_exists('pr_status_label'))
                                {{ pr_status_label($pr->status) }}
                            @else
                                {{ $pr->status }}
                            @endif
                        </span>
                        <div class="activity-meta mt-1">{{ $pr->created_at?->diffForHumans() }}</div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            {{-- PO Terbaru --}}
            @if ($recentPo->isNotEmpty())
            <div class="activity-card">
                <div class="activity-card-header">🧾 Purchase Order</div>
                @foreach ($recentPo as $po)
                <div class="activity-row">
                    <div>
                        <a href="{{ route('purchasing.purchase_orders.show', $po->id) }}" class="tbl-link activity-code">
                            {{ $po->code }}
                        </a>
                        <div class="activity-meta">{{ $po->supplier?->name ?? '—' }}</div>
                    </div>
                    <div class="text-end">
                        @php
                            $poBadge = match($po->status) {
                                'approved'  => 'pill-approved',
                                'cancelled' => 'pill-danger',
                                default     => 'pill-draft',
                            };
                        @endphp
                        <span class="pill {{ $poBadge }}">{{ ucfirst($po->status) }}</span>
                        <div class="activity-meta mt-1">{{ $po->created_at?->diffForHumans() }}</div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Invoice Terbaru --}}
            @if ($hasInvoiceTable && $recentInvoices->isNotEmpty())
            <div class="activity-card">
                <div class="activity-card-header">🧾 Faktur Supplier</div>
                @foreach ($recentInvoices as $inv)
                <div class="activity-row">
                    <div>
                        <a href="{{ route('purchasing.supplier_invoices.show', $inv->id) }}" class="tbl-link activity-code">
                            {{ $inv->invoice_no }}
                        </a>
                        <div class="activity-meta">{{ $inv->supplier?->name ?? '—' }}</div>
                    </div>
                    <div class="text-end">
                        @php
                            $invBadge = match($inv->status) {
                                'paid'         => 'pill-paid',
                                'partial_paid' => 'pill-partial',
                                'void'         => 'pill-danger',
                                'posted'       => 'pill-approved',
                                default        => 'pill-draft',
                            };
                            $invLabel = match($inv->status) {
                                'paid'         => 'Lunas',
                                'partial_paid' => 'Sebagian',
                                'void'         => 'Void',
                                'posted'       => 'Belum Bayar',
                                default        => 'Draft',
                            };
                        @endphp
                        <span class="pill {{ $invBadge }}">{{ $invLabel }}</span>
                        <div class="activity-meta mt-1">{{ $inv->created_at?->diffForHumans() }}</div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Payment Terbaru --}}
            @if ($hasPaymentTable && $recentPayments->isNotEmpty())
            <div class="activity-card">
                <div class="activity-card-header">💳 Pembayaran</div>
                @foreach ($recentPayments as $pay)
                <div class="activity-row">
                    <div>
                        <a href="{{ route('purchasing.purchase_orders.show', $pay->purchase_order_id) }}" class="tbl-link activity-code">
                            {{ $pay->purchaseOrder?->code ?? '—' }}
                        </a>
                        <div class="activity-meta">{{ $pay->purchaseOrder?->supplier?->name ?? '—' }}</div>
                    </div>
                    <div class="text-end">
                        @if ($canSeeMoney)
                        <div class="mono" style="font-size:.78rem;font-weight:600;">{{ rupiah($pay->amount) }}</div>
                        @endif
                        <div class="activity-meta mt-1">{{ $pay->created_at?->diffForHumans() }}</div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

        </div>
    </div>
    @endif

    {{-- Empty state --}}
    @php
        $totalAlert = $prApprovedNotConvertedCount + $poApprovedNotReceivedCount
            + $poPartialCount + $invOutstandingCount + $invOverdueCount;
    @endphp
    @if ($totalAlert === 0 && $poBelumTerimaList->isEmpty() && $invOutstandingList->isEmpty())
    <div class="text-center py-5 text-muted">
        <div style="font-size:2.5rem;">✅</div>
        <div class="fw-semibold mt-2">Semua bersih!</div>
        <div class="small">Tidak ada item yang perlu perhatian saat ini.</div>
    </div>
    @endif

</div>
@endsection
