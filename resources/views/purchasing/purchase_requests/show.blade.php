@extends('layouts.app')

@section('title', $purchase_request->code)

@php
    $pr = $purchase_request;
    $estTotal = $pr->lines->sum(fn ($line) => ($line->qty ?? 0) * ($line->unit_price ?? 0));
    $hasEstimate = $pr->lines->whereNotNull('unit_price')->isNotEmpty();
    $actualSuppliers = $pr->lines->pluck('supplier')->filter()->unique('id');
    $firstPo = $pr->purchaseOrders->first();
@endphp

@push('head')
<style>
    .pr-detail-wrap { max-width: 1080px; margin-inline: auto; padding-bottom: 3rem; }
    .pr-detail-card { background: var(--card); border: 1px solid var(--line); border-radius: 14px; margin-bottom: .85rem; overflow: hidden; }
    .pr-detail-head { padding: .85rem 1rem; border-bottom: 1px solid var(--line); }
    .pr-detail-body { padding: 1rem; }
    .pr-badge { display: inline-flex; border-radius: 999px; font-size: .72rem; padding: .15rem .58rem; border: 1px solid transparent; white-space: nowrap; font-weight: 700; }
    .pr-draft { background:rgba(148,163,184,.12); color:#64748b; border-color:rgba(148,163,184,.5); }
    .pr-approved { background:rgba(22,163,74,.1); color:#15803d; border-color:rgba(22,163,74,.45); }
    .pr-rejected { background:rgba(220,38,38,.08); color:#b91c1c; border-color:rgba(220,38,38,.45); }
    .pr-converted { background:rgba(59,130,246,.1); color:#1d4ed8; border-color:rgba(59,130,246,.45); }
    .pr-cancelled { background:rgba(100,116,139,.08); color:#475569; border-color:rgba(100,116,139,.4); }
    .pr-action { border-radius: 8px; font-size: .78rem; padding: .26rem .65rem; }
    .pr-info-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); }
    .pr-info-cell { padding: .85rem 1rem; border-right: 1px solid var(--line); }
    .pr-info-cell:last-child { border-right: 0; }
    .pr-info-label { color: var(--muted); font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; margin-bottom: .15rem; }
    .pr-info-value { font-size: .88rem; font-weight: 750; line-height: 1.35; }
    .pr-note { padding: .75rem 1rem; border-top: 1px solid var(--line); color: var(--muted); font-size: .84rem; white-space: pre-line; }
    .pr-lines-table th { padding: .7rem 1rem; color: var(--muted); font-size: .7rem; text-transform: uppercase; letter-spacing: .06em; white-space: nowrap; }
    .pr-lines-table td { padding: .7rem 1rem; vertical-align: middle; }
    .pr-item-name { font-weight: 750; line-height: 1.2; }
    .pr-item-code { color: var(--muted); font-size: .74rem; margin-top: .12rem; }
    .pr-mono { font-variant-numeric: tabular-nums; }
    .pr-history { display: flex; gap: 0; overflow-x: auto; }
    .pr-history-step { min-width: 180px; flex: 1 0 0; padding: .8rem 1rem; border-right: 1px solid var(--line); }
    .pr-history-step:last-child { border-right: 0; }
    .pr-history-title { font-size: .8rem; font-weight: 750; }
    .pr-history-meta { color: var(--muted); font-size: .72rem; margin-top: .15rem; }
    @media (max-width: 767.98px) {
        .pr-detail-wrap { padding-inline: .75rem; }
        .pr-info-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .pr-info-cell { border-bottom: 1px solid var(--line); }
        .pr-info-cell:nth-child(2n) { border-right: 0; }
        .pr-info-cell:last-child { grid-column: 1 / -1; border-bottom: 0; }
        .pr-lines-table thead { display: none; }
        .pr-lines-table, .pr-lines-table tbody, .pr-lines-table tr, .pr-lines-table td { display: block; width: 100%; }
        .pr-lines-table tr { padding: .7rem .85rem; border-bottom: 1px solid var(--line); }
        .pr-lines-table td { border: 0; padding: .18rem 0; text-align: left !important; }
        .pr-lines-table td[data-label]::before { content: attr(data-label); display: block; color: var(--muted); font-size: .66rem; font-weight: 700; text-transform: uppercase; margin-top: .25rem; }
    }
</style>
@endpush

@section('content')
<div class="pr-detail-wrap py-3">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-3 flex-wrap">
        <div>
            <a href="{{ route('purchasing.purchase_requests.index') }}" class="small text-decoration-none">Purchase Requests</a>
            <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                <h2 class="mb-0">{{ $pr->code }}</h2>
                <span class="pr-badge pr-{{ $pr->status }}">{{ pr_status_label($pr->status) }}</span>
            </div>
            <div class="text-muted small mt-1">{{ $pr->date?->format('d/m/Y') }} · {{ $pr->requestedBy?->name ?? '-' }} · {{ $pr->lines->count() }} barang</div>
        </div>
        <div class="d-flex gap-1 flex-wrap">
            @if ($pr->isDraft())
                <a href="{{ route('purchasing.purchase_requests.edit', $pr) }}" class="btn btn-sm btn-outline-primary pr-action">Edit</a>
            @endif
            @if ($canApproveReject)
                <form method="POST" action="{{ route('purchasing.purchase_requests.approve', $pr) }}" onsubmit="return confirm('Setujui {{ $pr->code }}?')">@csrf<button class="btn btn-sm btn-success pr-action">Approve</button></form>
                <form method="POST" action="{{ route('purchasing.purchase_requests.reject', $pr) }}" onsubmit="return confirm('Tolak {{ $pr->code }}?')">@csrf<button class="btn btn-sm btn-outline-danger pr-action">Tolak</button></form>
            @endif
            @if ($pr->isConvertible() && ($user->isOwner() || in_array($user->role, ['admin'], true) || $user->isDeveloper()))
                <a href="{{ route('purchasing.purchase_requests.allocate_suppliers', $pr) }}" class="btn btn-sm btn-primary pr-action">Pilih Supplier</a>
            @elseif ($firstPo)
                <a href="{{ route('purchasing.purchase_orders.show', $firstPo) }}" class="btn btn-sm btn-primary pr-action">Buka PO</a>
            @endif
        </div>
    </div>

    @if (session('success'))<div class="alert alert-success py-2 small">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger py-2 small">{{ session('error') }}</div>@endif

    <div class="pr-detail-card">
        <div class="pr-info-grid">
            <div class="pr-info-cell"><div class="pr-info-label">Tanggal</div><div class="pr-info-value">{{ $pr->date?->format('d/m/Y') ?? '-' }}</div></div>
            <div class="pr-info-cell"><div class="pr-info-label">Peminta</div><div class="pr-info-value">{{ $pr->requestedBy?->name ?? '-' }}</div></div>
            <div class="pr-info-cell"><div class="pr-info-label">Supplier</div><div class="pr-info-value">
                @if ($actualSuppliers->isNotEmpty()){{ $actualSuppliers->pluck('code')->join(', ') }}
                @elseif ($pr->supplier){{ $pr->supplier->code }}
                @else Otomatis saat PO @endif
            </div></div>
            <div class="pr-info-cell"><div class="pr-info-label">Purchase Order</div><div class="pr-info-value">
                @forelse ($pr->purchaseOrders as $order)<a href="{{ route('purchasing.purchase_orders.show', $order) }}" class="d-block text-decoration-none">{{ $order->code }}</a>@empty Belum dibuat @endforelse
            </div></div>
            <div class="pr-info-cell"><div class="pr-info-label">{{ $canSeeMoney ? 'Estimasi' : 'Jumlah Barang' }}</div><div class="pr-info-value">
                @if ($canSeeMoney){{ $hasEstimate ? 'Rp ' . number_format($estTotal, 0, ',', '.') : 'Belum diisi' }}@else{{ $pr->lines->count() }} barang @endif
            </div></div>
        </div>
        @if ($pr->notes)<div class="pr-note"><strong>Catatan:</strong> {{ $pr->notes }}</div>@endif
    </div>

    <div class="pr-detail-card">
        <div class="pr-detail-head d-flex justify-content-between align-items-center">
            <div class="fw-semibold">Kebutuhan Barang</div>
            <span class="text-muted small">{{ $pr->lines->count() }} barang</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0 pr-lines-table">
                <thead class="table-light"><tr><th>Barang</th><th class="text-end">Qty</th>@if ($canSeeMoney)<th class="text-end">Harga</th><th class="text-end">Total</th>@endif<th>Catatan</th>@if ($pr->isConverted())<th>Supplier / PO</th>@endif</tr></thead>
                <tbody>
                    @forelse ($pr->lines as $line)
                        <tr>
                            <td><div class="pr-item-name">{{ $line->item?->name ?? 'Barang tidak ditemukan' }}</div><div class="pr-item-code">{{ $line->item?->code }}</div></td>
                            <td class="text-end pr-mono" data-label="Qty">{{ number_format($line->qty, 2, ',', '.') }} {{ $line->item?->unit }}</td>
                            @if ($canSeeMoney)
                                <td class="text-end pr-mono" data-label="Harga">{{ $line->unit_price !== null ? 'Rp ' . number_format($line->unit_price, 0, ',', '.') : '-' }}</td>
                                <td class="text-end pr-mono" data-label="Total">{{ $line->unit_price !== null ? 'Rp ' . number_format($line->qty * $line->unit_price, 0, ',', '.') : '-' }}</td>
                            @endif
                            <td data-label="Catatan" class="text-muted">{{ $line->notes ?? '-' }}</td>
                            @if ($pr->isConverted())
                                <td data-label="Supplier / PO"><strong>{{ $line->supplier?->code ?? '-' }}</strong>@if ($line->purchaseOrder)<a href="{{ route('purchasing.purchase_orders.show', $line->purchaseOrder) }}" class="d-block small">{{ $line->purchaseOrder->code }}</a>@endif</td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ 3 + ($canSeeMoney ? 2 : 0) + ($pr->isConverted() ? 1 : 0) }}" class="text-center text-muted py-4">Belum ada barang.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="pr-detail-card">
        <div class="pr-detail-head fw-semibold">Riwayat</div>
        <div class="pr-history">
            <div class="pr-history-step"><div class="pr-history-title">Dibuat</div><div class="pr-history-meta">{{ $pr->requestedBy?->name ?? '-' }} · {{ $pr->created_at?->format('d/m/Y H:i') }}</div></div>
            @if ($pr->approved_by || $pr->isApproved() || $pr->isConverted())<div class="pr-history-step"><div class="pr-history-title text-success">Disetujui</div><div class="pr-history-meta">{{ $pr->approvedBy?->name ?? '-' }}</div></div>@endif
            @if ($pr->isRejected())<div class="pr-history-step"><div class="pr-history-title text-danger">Ditolak</div><div class="pr-history-meta">{{ $pr->rejectedBy?->name ?? '-' }} · {{ $pr->updated_at?->format('d/m/Y H:i') }}</div></div>@endif
            @if ($pr->isConverted())<div class="pr-history-step"><div class="pr-history-title text-primary">Dibuatkan PO</div><div class="pr-history-meta">{{ $pr->purchaseOrders->pluck('code')->join(', ') }} · {{ $pr->converted_at?->format('d/m/Y H:i') }}</div></div>@endif
            @if ($pr->isDraft())<div class="pr-history-step"><div class="pr-history-title text-muted">Menunggu persetujuan</div><div class="pr-history-meta">Belum diproses</div></div>@endif
        </div>
    </div>
</div>
@endsection
