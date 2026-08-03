@extends('layouts.app')

@section('title', 'Marketplace • Audit Keuangan')

@push('head')
<style>
    :root {
        --fin-accent: #334155;
        --fin-accent-dark: #1f2937;
        --fin-border: rgba(148, 163, 184, .20);
        --fin-muted: #64748b;
    }
    .fin-page { max-width: 1180px; margin-inline: auto; padding: .75rem .75rem 4rem; }
    .fin-topbar { display:flex; justify-content:space-between; align-items:center; gap:.75rem; flex-wrap:wrap; padding:.45rem 0 .65rem; border-bottom:1px solid var(--fin-border); }
    .fin-title { margin:0; font-size:1.05rem; font-weight:750; color:var(--fin-accent); }
    .fin-sub { color:var(--fin-muted); font-size:.76rem; }
    .fin-actions { display:flex; gap:.4rem; flex-wrap:wrap; }
    .fin-btn { border-radius:7px; padding:.42rem .72rem; font-size:.76rem; font-weight:650; box-shadow:none!important; }
    .fin-primary { background:var(--fin-accent)!important; border-color:var(--fin-accent)!important; color:#fff!important; }
    .fin-primary:hover { background:var(--fin-accent-dark)!important; border-color:var(--fin-accent-dark)!important; }
    .fin-panel { background:var(--card,#fff); border:1px solid var(--fin-border); border-radius:8px; overflow:hidden; }
    .fin-filter { display:flex; align-items:center; gap:.65rem; padding:.6rem .7rem; margin:.65rem 0; }
    .fin-filter-title { min-width:100px; color:var(--fin-accent); font-size:.75rem; font-weight:800; }
    .fin-filter-form { display:grid; grid-template-columns:minmax(190px,1.55fr) repeat(4,minmax(105px,1fr)) 118px 118px auto auto; gap:.35rem; align-items:center; flex:1; }
    .fin-filter-form .form-control, .fin-filter-form .form-select { min-width:0; height:32px; border-radius:7px; font-size:.76rem; padding-top:.25rem; padding-bottom:.25rem; }
    .fin-kpis { display:flex; flex-wrap:wrap; gap:.35rem; margin-bottom:.65rem; }
    .fin-kpi { display:inline-flex; align-items:baseline; gap:.4rem; border:1px solid var(--fin-border); border-radius:7px; padding:.28rem .55rem; font-size:.73rem; background:var(--card,#fff); }
    .fin-kpi-label { color:var(--fin-muted); font-size:.67rem; }
    .fin-kpi-value { font-weight:750; color:var(--fin-accent); }
    .fin-section-head { display:flex; justify-content:space-between; align-items:center; gap:.5rem; padding:.62rem .72rem; border-bottom:1px solid var(--fin-border); }
    .fin-section-title { margin:0; font-size:.82rem; font-weight:750; color:var(--fin-accent); }
    .fin-section-meta { color:var(--fin-muted); font-size:.7rem; }
    .fin-table { margin:0; font-size:.76rem; }
    .fin-table thead th { position:sticky; top:0; z-index:2; padding:.5rem .58rem; color:var(--fin-muted); background:var(--card,#fff); border-bottom:1px solid var(--fin-border); white-space:nowrap; font-size:.68rem; font-weight:750; }
    .fin-table tbody td { padding:.5rem .58rem; vertical-align:middle; border-top:1px solid rgba(148,163,184,.13); }
    .fin-table-wrap { max-height:min(64vh, 660px); overflow:auto; }
    .fin-order { font-weight:700; color:var(--fin-accent); }
    .fin-muted { color:var(--fin-muted); font-size:.7rem; }
    .fin-badge { display:inline-flex; align-items:center; gap:.28rem; border-radius:6px; padding:.16rem .42rem; border:1px solid transparent; font-size:.67rem; font-weight:700; white-space:nowrap; }
    .fin-badge::before { content:''; width:6px; height:6px; border-radius:50%; background:currentColor; }
    .fin-ready { color:#166534; background:#f0fdf4; border-color:#bbf7d0; }
    .fin-incomplete { color:#92400e; background:#fffbeb; border-color:#fde68a; }
    .fin-unknown { color:#475569; background:#f8fafc; border-color:#cbd5e1; }
    .fin-na { color:#64748b; background:#f8fafc; border-color:#e2e8f0; }
    .fin-complete { color:#166534; background:#f0fdf4; border-color:#bbf7d0; }
    .fin-missing { color:#991b1b; background:#fef2f2; border-color:#fecaca; }
    .fin-summary-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:.45rem; padding:.55rem .7rem; }
    .fin-summary-item { border:1px solid var(--fin-border); border-radius:7px; padding:.45rem .55rem; }
    .fin-summary-label { color:var(--fin-muted); font-size:.68rem; }
    .fin-summary-value { margin-top:.08rem; font-weight:750; font-size:.92rem; color:var(--fin-accent); }
    .fin-empty { padding:2.2rem 1rem; text-align:center; color:var(--fin-muted); }
    .fin-alert { margin:.65rem 0; padding:.55rem .7rem; border-radius:7px; font-size:.78rem; border:1px solid var(--fin-border); }
    .fin-alert-success { color:#166534; background:#f0fdf4; border-color:#bbf7d0; }
    .fin-alert-info { color:#1e40af; background:#eff6ff; border-color:#bfdbfe; }
    .fin-alert-danger { color:#991b1b; background:#fef2f2; border-color:#fecaca; }
    @media (max-width: 900px) {
        .fin-filter { display:block; }
        .fin-filter-title { margin-bottom:.45rem; }
        .fin-filter-form { grid-template-columns:repeat(4,1fr); }
        .fin-filter-form .search { grid-column:1 / -1; }
    }
    @media (max-width: 640px) {
        .fin-page { padding:.5rem .5rem 3rem; }
        .fin-sub { display:none; }
        .fin-actions { width:100%; }
        .fin-actions .btn { flex:1; }
        .fin-filter-form { grid-template-columns:1fr 1fr; }
        .fin-filter-form .search, .fin-filter-form .wide { grid-column:1 / -1; }
        .fin-summary-grid { grid-template-columns:1fr 1fr; }
        .fin-table-wrap { max-height:none; }
        .fin-table thead { display:none; }
        .fin-table, .fin-table tbody, .fin-table tr, .fin-table td { display:block; width:100%; }
        .fin-table tbody tr { padding:.7rem; border-top:1px solid var(--fin-border); }
        .fin-table tbody td { border:0; padding:.08rem 0; }
        .fin-table tbody td[data-label]::before { content:attr(data-label); display:inline-block; min-width:92px; color:var(--fin-muted); font-size:.67rem; }
        .fin-table tbody td.fin-action { margin-top:.45rem; }
        .fin-table tbody td.fin-action::before { display:none; }
        .fin-action .btn { width:100%; }
    }
</style>
@endpush

@php
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
    $statusLabels = [
        'ready' => 'Ready',
        'incomplete' => 'Incomplete',
        'not_applicable' => 'Tidak berlaku',
        'unknown' => 'Unknown',
    ];
    $statusClasses = [
        'ready' => 'fin-ready',
        'incomplete' => 'fin-incomplete',
        'not_applicable' => 'fin-na',
        'unknown' => 'fin-unknown',
    ];
    $result = session('quality_result');
    $hasFilters = filled($search) || filled($storeId) || filled($orderStatus) || filled($settlementStatus) || filled($dateFrom) || filled($dateTo) || $status !== 'incomplete';
@endphp

@section('content')
<div class="fin-page">
    <div class="fin-topbar">
        <div>
            <h1 class="fin-title">Audit Kualitas Data Keuangan</h1>
            <div class="fin-sub">Queue operasional settlement dan HPP · Owner</div>
        </div>
        <div class="fin-actions">
            <a href="{{ route('marketplace.reports.financial-statement') }}" class="btn btn-sm btn-outline-secondary fin-btn"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Financial statement</a>
            <a href="{{ route('marketplace.reports.financial-closing') }}" class="btn btn-sm btn-outline-warning fin-btn"><i class="bi bi-lock-fill me-1"></i>Closing</a>
        </div>
    </div>

    @if ($result)
        <div class="fin-alert {{ $result['dry_run'] ? 'fin-alert-info' : 'fin-alert-success' }}"><i class="bi {{ $result['dry_run'] ? 'bi-search' : 'bi-check-circle' }} me-1"></i>{{ $result['message'] }}</div>
    @endif
    @if ($errors->has('store_id'))
        <div class="fin-alert fin-alert-danger">{{ $errors->first('store_id') }}</div>
    @endif

    <div class="fin-kpis">
        <div class="fin-kpi"><span class="fin-kpi-label">Ready</span><span class="fin-kpi-value text-success">{{ $fmt($orderCounts['ready'] ?? 0) }}</span></div>
        <div class="fin-kpi"><span class="fin-kpi-label">Incomplete</span><span class="fin-kpi-value text-warning-emphasis">{{ $fmt($orderCounts['incomplete'] ?? 0) }}</span></div>
        <div class="fin-kpi"><span class="fin-kpi-label">Tidak berlaku</span><span class="fin-kpi-value">{{ $fmt($orderCounts['not_applicable'] ?? 0) }}</span></div>
        <div class="fin-kpi"><span class="fin-kpi-label">Settlement complete</span><span class="fin-kpi-value text-success">{{ $fmt($settlementCounts['complete'] ?? 0) }}</span></div>
        <div class="fin-kpi"><span class="fin-kpi-label">Settlement incomplete</span><span class="fin-kpi-value text-danger">{{ $fmt($settlementCounts['incomplete'] ?? 0) }}</span></div>
        @if ($lastCheckedAt)<div class="fin-kpi"><span class="fin-kpi-label">Audit terakhir</span><span class="fin-kpi-value">{{ date('d M Y H:i', strtotime($lastCheckedAt)) }}</span></div>@endif
    </div>

    <div class="fin-panel fin-filter">
        <div class="fin-filter-title"><i class="bi bi-funnel me-1"></i>Filter operasional</div>
        <form method="GET" class="fin-filter-form">
            <input class="form-control search" type="search" name="q" value="{{ $search }}" placeholder="Cari order / booking / buyer" autocomplete="off">
            <select name="store_id" class="form-select"><option value="">Semua toko</option>@foreach ($stores as $store)<option value="{{ $store->id }}" @selected((int) $storeId === (int) $store->id)>{{ $store->name }}</option>@endforeach</select>
            <select name="order_status" class="form-select"><option value="">Semua status order</option>@foreach ($orderStatuses as $value)<option value="{{ $value }}" @selected($orderStatus === $value)>{{ $value }}</option>@endforeach</select>
            <select name="status" class="form-select"><option value="">Semua kualitas</option>@foreach ($statusLabels as $value => $label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach</select>
            <select name="settlement_status" class="form-select"><option value="">Semua settlement</option><option value="complete" @selected($settlementStatus === 'complete')>Complete</option><option value="incomplete" @selected($settlementStatus === 'incomplete')>Incomplete</option><option value="missing" @selected($settlementStatus === 'missing')>Missing</option><option value="unknown" @selected($settlementStatus === 'unknown')>Unknown</option></select>
            <input class="form-control wide" type="date" name="date_from" value="{{ $dateFrom }}" aria-label="Dari tanggal">
            <input class="form-control wide" type="date" name="date_to" value="{{ $dateTo }}" aria-label="Sampai tanggal">
            <button class="btn btn-sm fin-btn fin-primary" type="submit">Terapkan</button>
            @if ($hasFilters)<a class="btn btn-sm btn-outline-secondary fin-btn" href="{{ route('marketplace.reports.financial-quality') }}">Reset</a>@endif
        </form>
    </div>

    <div class="fin-panel mb-3">
        <div class="fin-section-head"><h2 class="fin-section-title">Refresh quality status</h2><span class="fin-section-meta">{{ $storeId ? 'Toko terpilih' : 'Semua toko' }}</span></div>
        <div class="p-2 d-flex flex-wrap gap-2 align-items-center">
            <form method="POST" action="{{ route('marketplace.reports.financial-quality.refresh') }}" class="d-flex flex-wrap gap-2">
                @csrf
                <input type="hidden" name="store_id" value="{{ $storeId ?: 'all' }}">
                <button name="dry_run" value="1" class="btn btn-sm btn-outline-secondary fin-btn"><i class="bi bi-search me-1"></i>Audit dry-run</button>
                <button name="dry_run" value="0" class="btn btn-sm fin-btn fin-primary"><i class="bi bi-arrow-repeat me-1"></i>Audit & simpan status</button>
            </form>
            <span class="fin-section-meta">Order belum COMPLETED masuk kategori Tidak berlaku.</span>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <div class="fin-panel h-100">
                <div class="fin-section-head"><h2 class="fin-section-title">Settlement</h2><span class="fin-section-meta">{{ $lastCheckedAt ? 'Update ' . date('d M Y H:i', strtotime($lastCheckedAt)) : 'Belum diaudit' }}</span></div>
                <div class="fin-summary-grid">
                    <div class="fin-summary-item"><div class="fin-summary-label">Complete</div><div class="fin-summary-value text-success">{{ $fmt($settlementCounts['complete'] ?? 0) }}</div></div>
                    <div class="fin-summary-item"><div class="fin-summary-label">Incomplete</div><div class="fin-summary-value text-warning-emphasis">{{ $fmt($settlementCounts['incomplete'] ?? 0) }}</div></div>
                    <div class="fin-summary-item"><div class="fin-summary-label">Unknown</div><div class="fin-summary-value">{{ $fmt($settlementCounts['unknown'] ?? 0) }}</div></div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="fin-panel h-100">
                <div class="fin-section-head"><h2 class="fin-section-title">Alasan incomplete</h2><span class="fin-section-meta">{{ $issueBreakdown->sum('total') }} order</span></div>
                <div class="p-2">
                    @forelse ($issueBreakdown->take(4) as $issue)
                        <div class="d-flex justify-content-between align-items-center py-1 border-bottom"><span class="fin-muted">{{ str_replace('_', ' ', $issue->reason) }}</span><span class="fin-badge fin-incomplete">{{ $fmt($issue->total) }}</span></div>
                    @empty
                        <div class="fin-muted py-2">Tidak ada incomplete.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="fin-panel">
        <div class="fin-section-head"><h2 class="fin-section-title">Queue perbaikan</h2><span class="fin-section-meta">{{ $orders->total() }} hasil · halaman {{ $orders->currentPage() }}</span></div>
        <div class="fin-table-wrap">
            <table class="table fin-table">
                <thead><tr><th>Order</th><th>Toko</th><th>Order status</th><th>Quality</th><th>Settlement</th><th>Item / HPP</th><th>Masalah</th><th></th></tr></thead>
                <tbody>
                @forelse ($orders as $order)
                    @php
                        $qualityStatus = $order->financial_data_status ?: 'unknown';
                        $flags = $order->settlement?->data_quality_flags ?? [];
                        $missing = $flags['blocking_missing_fields'] ?? $flags['missing_financial_fields'] ?? [];
                        $validItems = $order->items->filter(fn ($item) => ($item->data_status ?? null) === 'valid' && (float) ($item->hpp_snapshot ?? 0) > 0)->count();
                        $settlementStatusRow = $order->settlement?->data_status ?: ($order->settlement ? 'unknown' : 'missing');
                    @endphp
                    <tr>
                        <td data-label="Order"><div class="fin-order">{{ $order->channel_order_id ?: $order->external_order_id }}</div><div class="fin-muted">{{ optional($order->ordered_at)->format('d M Y H:i') ?: '-' }}</div></td>
                        <td data-label="Toko"><div>{{ $order->store?->name ?: '-' }}</div><div class="fin-muted">{{ strtoupper($order->store?->channel?->code ?? '-') }}</div></td>
                        <td data-label="Order status"><span class="fin-badge fin-{{ in_array(strtoupper((string) $order->order_status), ['COMPLETED'], true) ? 'complete' : 'na' }}">{{ $order->order_status ?: '-' }}</span></td>
                        <td data-label="Quality"><span class="fin-badge {{ $statusClasses[$qualityStatus] ?? 'fin-unknown' }}">{{ $statusLabels[$qualityStatus] ?? 'Unknown' }}</span></td>
                        <td data-label="Settlement"><span class="fin-badge {{ $settlementStatusRow === 'complete' ? 'fin-complete' : ($settlementStatusRow === 'missing' ? 'fin-missing' : 'fin-incomplete') }}">{{ $settlementStatusRow }}</span></td>
                        <td data-label="Item / HPP"><span class="{{ $validItems === $order->items->count() && $order->items->count() > 0 ? 'text-success' : 'text-warning-emphasis' }} fw-semibold">{{ $validItems }}/{{ $order->items->count() }} valid</span></td>
                        <td data-label="Masalah" class="fin-muted">@if ($order->financial_issue_reason)<span class="d-block">{{ str_replace('_', ' ', $order->financial_issue_reason) }}</span>@endif @if (count($missing))<span>Missing: {{ implode(', ', array_slice($missing, 0, 3)) }}{{ count($missing) > 3 ? '…' : '' }}</span>@elseif (!$order->financial_issue_reason)<span>-</span>@endif</td>
                        <td data-label="Aksi" class="text-end fin-action"><a href="{{ route('marketplace.orders.show', $order) }}" class="btn btn-sm btn-outline-secondary fin-btn">Detail <i class="bi bi-arrow-up-right ms-1"></i></a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="fin-empty">Tidak ada order sesuai filter.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($orders->hasPages())<div class="p-2 border-top">{{ $orders->links() }}</div>@endif
    </div>
</div>
@endsection
