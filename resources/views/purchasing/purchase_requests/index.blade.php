@extends('layouts.app')

@section('title', 'Purchase Requests')

@php
    $user = auth()->user();
    $canManagePr = $user && ($user->isOwner() || in_array($user->role, ['admin'], true));
    $activeStatus = request('status');
    $hasFilters = request()->filled('status') || request()->filled('supplier_id') || request()->filled('search');
@endphp

@push('head')
<style>
    .pr-page-wrap { max-width: 1080px; margin-inline: auto; padding-bottom: 3rem; }
    .pr-filter-card, .pr-list-card { background: var(--card); border: 1px solid var(--line); border-radius: 14px; }
    .pr-list-card { overflow: hidden; }
    .pr-summary { display: flex; gap: .4rem; flex-wrap: wrap; margin-top: .5rem; }
    .pr-summary-pill { display: inline-flex; align-items: center; gap: .35rem; padding: .22rem .6rem; border: 1px solid var(--line); border-radius: 999px; color: inherit; text-decoration: none; font-size: .76rem; background: var(--card); }
    .pr-summary-pill:hover, .pr-summary-pill.is-active { color: #1d4ed8; border-color: #60a5fa; background: rgba(37,99,235,.06); }
    .pr-summary-count { font-weight: 800; }
    .pr-filter-card { padding: .8rem .9rem; margin-bottom: .8rem; }
    .pr-table th { color: var(--muted); font-size: .72rem; text-transform: uppercase; letter-spacing: .06em; white-space: nowrap; padding: .7rem .85rem; }
    .pr-table td { vertical-align: middle; padding: .75rem .85rem; }
    .pr-code { font-weight: 800; font-variant-numeric: tabular-nums; }
    .pr-meta { color: var(--muted); font-size: .76rem; margin-top: .08rem; }
    .pr-badge { display: inline-flex; border-radius: 999px; font-size: .7rem; padding: .12rem .55rem; border: 1px solid transparent; white-space: nowrap; font-weight: 700; }
    .pr-draft { background: rgba(148,163,184,.12); color:#64748b; border-color:rgba(148,163,184,.5); }
    .pr-approved { background:rgba(22,163,74,.1); color:#15803d; border-color:rgba(22,163,74,.45); }
    .pr-rejected { background:rgba(220,38,38,.08); color:#b91c1c; border-color:rgba(220,38,38,.45); }
    .pr-converted { background:rgba(59,130,246,.1); color:#1d4ed8; border-color:rgba(59,130,246,.45); }
    .pr-cancelled { background:rgba(100,116,139,.08); color:#475569; border-color:rgba(100,116,139,.4); }
    .pr-action { border-radius: 8px; font-size: .76rem; padding: .22rem .55rem; }
    .pr-items-preview { min-width: 250px; }
    .pr-item-line { display: flex; align-items: baseline; justify-content: space-between; gap: .75rem; padding: .12rem 0; }
    .pr-item-main { min-width: 0; }
    .pr-item-name { font-size: .82rem; font-weight: 700; }
    .pr-item-code { color: var(--muted); font-size: .7rem; margin-left: .3rem; }
    .pr-item-qty { flex: 0 0 auto; font-size: .76rem; font-variant-numeric: tabular-nums; }
    .pr-items-toggle { font-size: .72rem; text-decoration: none; }
    .pr-item-estimate { margin-top: .25rem; color: var(--muted); font-size: .72rem; font-weight: 600; }
    .pr-note-preview { max-width: 220px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
    .pr-po-links { display: flex; flex-direction: column; align-items: flex-start; gap: .15rem; }
    .pr-po-link { font-size: .73rem; text-decoration: none; font-weight: 700; }
    .pr-mobile-card { padding: .75rem .85rem; border-bottom: 1px solid var(--line); }
    .pr-mobile-card:last-child { border-bottom: 0; }
    @media (max-width: 767.98px) {
        .pr-page-wrap { padding-inline: .75rem; }
        .pr-filter-card { padding: .7rem .75rem; }
        .pr-summary { flex-wrap: nowrap; overflow-x: auto; padding-bottom: .2rem; }
        .pr-summary-pill { flex: 0 0 auto; }
    }
</style>
@endpush

@section('content')
<div class="pr-page-wrap py-3">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h2 class="mb-0">Purchase Requests</h2>
            <div class="text-muted small">Daftar kebutuhan barang sebelum dibuatkan Purchase Order.</div>
            <div class="pr-summary">
                @foreach ([
                    '' => ['Semua', $summary['total']],
                    'draft' => ['Draft', $summary['draft']],
                    'approved' => ['Siap PO', $summary['approved']],
                    'converted' => ['Sudah PO', $summary['converted']],
                    'rejected' => ['Ditolak', $summary['rejected']],
                ] as $statusKey => [$label, $count])
                    <a href="{{ route('purchasing.purchase_requests.index', $statusKey ? ['status' => $statusKey] : []) }}"
                        class="pr-summary-pill {{ (string) $activeStatus === $statusKey ? 'is-active' : '' }}">
                        <span>{{ $label }}</span><span class="pr-summary-count">{{ $count }}</span>
                    </a>
                @endforeach
            </div>
        </div>
        <a href="{{ route('purchasing.purchase_requests.create') }}" class="btn btn-primary btn-sm flex-shrink-0">+ PR Baru</a>
    </div>

    @if (session('success'))<div class="alert alert-success py-2 small">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger py-2 small">{{ session('error') }}</div>@endif

    <div class="pr-filter-card">
        <form method="GET" action="{{ route('purchasing.purchase_requests.index') }}" class="row g-2 align-items-end" id="pr-filter-form">
            <div class="col-12 col-md-5">
                <label class="form-label small mb-1">Cari PR</label>
                <div class="input-group input-group-sm">
                    <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Kode PR atau supplier">
                    <button class="btn btn-outline-secondary" type="submit">Cari</button>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm js-pr-auto-filter">
                    <option value="">Semua status</option>
                    @foreach (['draft'=>'Draft','approved'=>'Siap dibuat PO','converted'=>'Sudah dibuat PO','rejected'=>'Ditolak','cancelled'=>'Dibatalkan'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Supplier Awal</label>
                <select name="supplier_id" class="form-select form-select-sm js-pr-auto-filter">
                    <option value="">Semua supplier</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected((string) request('supplier_id') === (string) $supplier->id)>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-1">
                @if ($hasFilters)
                    <a href="{{ route('purchasing.purchase_requests.index') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <div class="pr-list-card d-none d-md-block">
        <table class="table table-hover table-sm mb-0 pr-table">
            <thead class="table-light">
                <tr>
                    <th>PR / PO</th>
                    <th style="width:32%;">Kebutuhan Barang</th>
                    <th>Supplier</th>
                    <th class="text-center">Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($prs as $pr)
                    @php
                        $firstPo = $pr->purchaseOrders->first();
                        $actualSuppliers = $pr->lines->pluck('supplier')->filter()->unique('id');
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('purchasing.purchase_requests.show', $pr) }}" class="pr-code text-decoration-none">{{ $pr->code }}</a>
                            <div class="pr-meta">{{ $pr->date?->format('d/m/Y') }} · {{ $pr->requestedBy?->name ?? '-' }}</div>
                            @if ($pr->purchaseOrders->isNotEmpty())
                                <div class="pr-po-links mt-1">
                                    @foreach ($pr->purchaseOrders->take(2) as $order)
                                        <a href="{{ route('purchasing.purchase_orders.show', $order) }}" class="pr-po-link">{{ $order->code }}</a>
                                    @endforeach
                                    @if ($pr->purchaseOrders->count() > 2)<span class="pr-meta">+{{ $pr->purchaseOrders->count() - 2 }} PO</span>@endif
                                </div>
                            @endif
                            @if ($pr->notes)<div class="pr-meta pr-note-preview" title="{{ $pr->notes }}">{{ $pr->notes }}</div>@endif
                        </td>
                        <td>@include('purchasing.purchase_requests._index_item_summary', ['surface' => 'desktop'])</td>
                        <td style="font-size:.8rem;">
                            @if ($actualSuppliers->isNotEmpty())
                                @foreach ($actualSuppliers->take(2) as $supplier)<div class="fw-semibold">{{ $supplier->code }}</div>@endforeach
                                @if ($actualSuppliers->count() > 2)<div class="pr-meta">+{{ $actualSuppliers->count() - 2 }} supplier</div>@endif
                            @elseif ($pr->supplier)
                                <div class="fw-semibold">{{ $pr->supplier->code }}</div><div class="pr-meta">Supplier awal</div>
                            @else
                                <span class="text-muted">Otomatis saat PO</span>
                            @endif
                        </td>
                        <td class="text-center"><span class="pr-badge pr-{{ $pr->status }}">{{ pr_status_label($pr->status) }}</span></td>
                        <td class="text-end text-nowrap">
                            @if ($pr->status === 'draft')
                                <a href="{{ route('purchasing.purchase_requests.edit', $pr) }}" class="btn btn-sm btn-outline-primary pr-action">Edit</a>
                                @if ($canManagePr)
                                    <form method="POST" action="{{ route('purchasing.purchase_requests.approve', $pr) }}" class="d-inline" onsubmit="return confirm('Setujui {{ $pr->code }}?')">
                                        @csrf
                                        <button class="btn btn-sm btn-success pr-action" type="submit">Approve</button>
                                    </form>
                                @endif
                            @elseif ($pr->status === 'approved' && $canManagePr)
                                <a href="{{ route('purchasing.purchase_requests.allocate_suppliers', $pr) }}" class="btn btn-sm btn-primary pr-action">Pilih Supplier</a>
                            @endif
                            <a href="{{ route('purchasing.purchase_requests.show', $pr) }}" class="btn btn-sm btn-outline-secondary pr-action">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada Purchase Request.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pr-list-card d-md-none">
        @forelse ($prs as $pr)
            @php
                $firstPo = $pr->purchaseOrders->first();
            @endphp
            <div class="pr-mobile-card">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <a href="{{ route('purchasing.purchase_requests.show', $pr) }}" class="pr-code text-decoration-none">{{ $pr->code }}</a>
                        <div class="pr-meta">{{ $pr->date?->format('d/m/Y') }} · {{ $pr->lines_count ?? 0 }} barang · {{ $pr->requestedBy?->name ?? '-' }}</div>
                        @if ($pr->purchaseOrders->isNotEmpty())
                            <div class="pr-meta">
                                @foreach ($pr->purchaseOrders->take(2) as $order)
                                    <a href="{{ route('purchasing.purchase_orders.show', $order) }}" class="pr-po-link me-1">{{ $order->code }}</a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <span class="pr-badge pr-{{ $pr->status }}">{{ pr_status_label($pr->status) }}</span>
                </div>
                <div class="mt-2">@include('purchasing.purchase_requests._index_item_summary', ['surface' => 'mobile'])</div>
                <div class="d-flex gap-1 mt-2 flex-wrap">
                    @if ($pr->status === 'draft')
                        <a href="{{ route('purchasing.purchase_requests.edit', $pr) }}" class="btn btn-sm btn-outline-primary pr-action">Edit</a>
                        @if ($canManagePr)
                            <form method="POST" action="{{ route('purchasing.purchase_requests.approve', $pr) }}" onsubmit="return confirm('Setujui {{ $pr->code }}?')">@csrf<button class="btn btn-sm btn-success pr-action">Approve</button></form>
                        @endif
                    @elseif ($pr->status === 'approved' && $canManagePr)
                        <a href="{{ route('purchasing.purchase_requests.allocate_suppliers', $pr) }}" class="btn btn-sm btn-primary pr-action">Pilih Supplier</a>
                    @elseif ($firstPo)
                        <a href="{{ route('purchasing.purchase_orders.show', $firstPo) }}" class="btn btn-sm btn-outline-primary pr-action">Buka PO</a>
                    @endif
                    <a href="{{ route('purchasing.purchase_requests.show', $pr) }}" class="btn btn-sm btn-outline-secondary pr-action">Detail</a>
                </div>
            </div>
        @empty
            <div class="text-center text-muted py-4">Belum ada Purchase Request.</div>
        @endforelse
    </div>

    @if ($prs->hasPages())<div class="mt-3">{{ $prs->links() }}</div>@endif
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.js-pr-auto-filter').forEach(function (select) {
    select.addEventListener('change', function () {
        document.getElementById('pr-filter-form')?.submit();
    });
});
document.addEventListener('click', function (event) {
    const button = event.target.closest('.pr-items-toggle');
    if (!button) return;
    const target = document.getElementById(button.dataset.target);
    if (!target) return;
    const willShow = target.classList.contains('d-none');
    target.classList.toggle('d-none', !willShow);
    button.textContent = willShow ? 'Sembunyikan' : `+${button.dataset.count} barang`;
});
</script>
@endpush
