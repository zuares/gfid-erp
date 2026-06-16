@extends('layouts.app')

@section('title', 'Purchase Request')

@push('head')
<style>
    .pr-page-wrap { max-width: 1140px; margin-inline: auto; padding-bottom: 3rem; }

    /* ── KPI CARDS ── */
    .pr-kpi-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: .6rem;
        margin-bottom: .9rem;
    }
    @media (max-width: 767px) {
        .pr-kpi-grid { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 480px) {
        .pr-kpi-grid { grid-template-columns: repeat(2, 1fr); }
    }
    .pr-kpi-card {
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: 14px;
        padding: .8rem 1rem;
        text-decoration: none;
        color: inherit;
        display: block;
        transition: border-color .15s;
    }
    .pr-kpi-card:hover { border-color: var(--primary); color: inherit; }
    .pr-kpi-card.active { border-color: var(--primary); background: rgba(37,99,235,.05); }
    .pr-kpi-number {
        font-size: 1.5rem;
        font-weight: 800;
        line-height: 1;
        margin-bottom: .2rem;
        font-variant-numeric: tabular-nums;
    }
    .pr-kpi-label {
        font-size: .7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--muted);
    }
    .pr-kpi-card.kpi-draft    .pr-kpi-number { color: #64748b; }
    .pr-kpi-card.kpi-approved .pr-kpi-number { color: #15803d; }
    .pr-kpi-card.kpi-converted .pr-kpi-number { color: #1d4ed8; }
    .pr-kpi-card.kpi-rejected .pr-kpi-number { color: #b91c1c; }

    /* ── FILTER ── */
    .card-filter {
        background: var(--card);
        border-radius: 14px;
        border: 1px solid var(--line);
        padding: .85rem .95rem;
        margin-bottom: .85rem;
    }

    /* ── TABLE ── */
    .card-table {
        background: var(--card);
        border-radius: 14px;
        border: 1px solid var(--line);
        overflow: hidden;
    }
    .table thead th {
        border-bottom-width: 1px;
        font-size: .75rem;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: var(--muted);
        white-space: nowrap;
    }
    .mono { font-variant-numeric: tabular-nums; }

    /* ── STATUS BADGE ── */
    .pr-badge {
        border-radius: 999px;
        font-size: .7rem;
        padding: .12rem .6rem;
        border: 1px solid transparent;
        white-space: nowrap;
        display: inline-block;
        font-weight: 600;
    }
    .pr-draft     { background: rgba(148,163,184,.12); color: #64748b;  border-color: rgba(148,163,184,.5); }
    .pr-approved  { background: rgba(22,163,74,.12);   color: #15803d;  border-color: rgba(22,163,74,.5); }
    .pr-rejected  { background: rgba(220,38,38,.08);   color: #b91c1c;  border-color: rgba(220,38,38,.5); }
    .pr-converted { background: rgba(59,130,246,.10);  color: #1d4ed8;  border-color: rgba(59,130,246,.5); }
    .pr-cancelled { background: rgba(100,116,139,.08); color: #475569;  border-color: rgba(100,116,139,.4); }

    /* PO linkage badge */
    .badge-po-ref {
        border-radius: 999px;
        font-size: .65rem;
        padding: .08rem .45rem;
        background: rgba(59,130,246,.08);
        color: #1d4ed8;
        border: 1px solid rgba(59,130,246,.4);
        white-space: nowrap;
        text-decoration: none;
    }
    .badge-po-ref:hover { background: rgba(59,130,246,.16); color: #1d4ed8; }

    /* ── MOBILE CARD ── */
    .pr-card-mobile {
        border-bottom: 1px solid var(--line);
        padding: .75rem .9rem;
    }
    .pr-card-mobile:last-child { border-bottom: none; }
    .meta-row { font-size: .78rem; color: var(--muted); display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .2rem; }
    .meta-row span { display: inline-flex; align-items: center; gap: .2rem; }
</style>
@endpush

@section('content')
<div class="pr-page-wrap">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
        <h1 class="h5 mb-0 fw-bold">Purchase Request</h1>
        <a href="{{ route('purchasing.purchase_requests.create') }}" class="btn btn-sm btn-primary">
            + PR Baru
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- KPI Cards --}}
    @php $activeStatus = request('status'); @endphp
    <div class="pr-kpi-grid">
        <a href="{{ route('purchasing.purchase_requests.index') }}"
            class="pr-kpi-card {{ !$activeStatus ? 'active' : '' }}">
            <div class="pr-kpi-number">{{ $summary['total'] }}</div>
            <div class="pr-kpi-label">Total PR</div>
        </a>
        <a href="{{ route('purchasing.purchase_requests.index', ['status' => 'draft']) }}"
            class="pr-kpi-card kpi-draft {{ $activeStatus === 'draft' ? 'active' : '' }}">
            <div class="pr-kpi-number">{{ $summary['draft'] }}</div>
            <div class="pr-kpi-label">Draft</div>
        </a>
        <a href="{{ route('purchasing.purchase_requests.index', ['status' => 'approved']) }}"
            class="pr-kpi-card kpi-approved {{ $activeStatus === 'approved' ? 'active' : '' }}">
            <div class="pr-kpi-number">{{ $summary['approved'] }}</div>
            <div class="pr-kpi-label">Approved</div>
        </a>
        <a href="{{ route('purchasing.purchase_requests.index', ['status' => 'converted']) }}"
            class="pr-kpi-card kpi-converted {{ $activeStatus === 'converted' ? 'active' : '' }}">
            <div class="pr-kpi-number">{{ $summary['converted'] }}</div>
            <div class="pr-kpi-label">Converted</div>
        </a>
        <a href="{{ route('purchasing.purchase_requests.index', ['status' => 'rejected']) }}"
            class="pr-kpi-card kpi-rejected {{ $activeStatus === 'rejected' ? 'active' : '' }}">
            <div class="pr-kpi-number">{{ $summary['rejected'] }}</div>
            <div class="pr-kpi-label">Ditolak</div>
        </a>
    </div>

    {{-- Filter --}}
    <div class="card-filter">
        <form method="GET" action="{{ route('purchasing.purchase_requests.index') }}"
            class="row g-2 align-items-end">
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label form-label-sm mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    @foreach ([
                        'draft'     => 'Draft',
                        'approved'  => 'Approved',
                        'converted' => 'Converted',
                        'rejected'  => 'Ditolak',
                        'cancelled' => 'Dibatalkan',
                    ] as $val => $label)
                        <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label form-label-sm mb-1">Supplier</label>
                <select name="supplier_id" class="form-select form-select-sm">
                    <option value="">Semua Supplier</option>
                    @foreach ($suppliers as $sup)
                        <option value="{{ $sup->id }}" @selected(request('supplier_id') == $sup->id)>
                            {{ $sup->code }} — {{ $sup->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label form-label-sm mb-1">Cari</label>
                <input type="text" name="search" class="form-control form-control-sm"
                    placeholder="Kode PR / supplier..." value="{{ request('search') }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-secondary">Filter</button>
                <a href="{{ route('purchasing.purchase_requests.index') }}"
                    class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
            </div>
        </form>
    </div>

    {{-- TABLE — desktop --}}
    <div class="card-table d-none d-md-block">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>Kode</th>
                    <th>Tanggal</th>
                    <th>Supplier</th>
                    <th>Requester</th>
                    <th class="text-center">Item</th>
                    @if ($canSeeMoney)
                        <th class="text-end">Est. Total</th>
                    @endif
                    <th class="text-center">Status</th>
                    <th>PO</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($prs as $pr)
                    @php
                        $estTotal = $pr->lines->sum(fn($l) => ($l->qty ?? 0) * ($l->unit_price ?? 0));
                        $hasEstimate = $pr->lines->whereNotNull('unit_price')->count() > 0;
                    @endphp
                    <tr>
                        <td class="mono fw-semibold">
                            <a href="{{ route('purchasing.purchase_requests.show', $pr->id) }}"
                                class="text-decoration-none">{{ $pr->code }}</a>
                        </td>
                        <td class="text-nowrap text-muted" style="font-size:.88rem;">
                            {{ $pr->date?->format('d/m/Y') }}
                        </td>
                        <td style="font-size:.88rem;">{{ $pr->supplier?->name ?? '—' }}</td>
                        <td style="font-size:.88rem;">{{ $pr->requestedBy?->name ?? '—' }}</td>
                        <td class="text-center text-muted" style="font-size:.88rem;">
                            {{ $pr->lines_count ?? 0 }}
                        </td>
                        @if ($canSeeMoney)
                            <td class="text-end mono" style="font-size:.88rem;">
                                @if ($hasEstimate)
                                    Rp {{ number_format($estTotal, 0, ',', '.') }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        @endif
                        <td class="text-center">
                            <span class="pr-badge pr-{{ $pr->status }}">
                                {{ pr_status_label($pr->status) }}
                            </span>
                        </td>
                        <td style="font-size:.82rem;">
                            @if ($pr->convertedToPo)
                                <a href="{{ route('purchasing.purchase_orders.show', $pr->converted_to_po_id) }}"
                                    class="badge-po-ref">
                                    {{ $pr->convertedToPo->code }}
                                </a>
                            @elseif ($pr->status === 'approved')
                                <span class="text-muted" style="font-size:.78rem;">Menunggu convert</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('purchasing.purchase_requests.show', $pr->id) }}"
                                class="btn btn-xs btn-outline-secondary"
                                style="font-size:.78rem; border-radius:8px; padding:.15rem .6rem;">
                                Lihat
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $canSeeMoney ? 9 : 8 }}" class="text-center text-muted py-4">
                            Tidak ada data Purchase Request.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- CARD LIST — mobile --}}
    <div class="card-table d-md-none">
        @forelse ($prs as $pr)
            @php
                $estTotal    = $pr->lines->sum(fn($l) => ($l->qty ?? 0) * ($l->unit_price ?? 0));
                $hasEstimate = $pr->lines->whereNotNull('unit_price')->count() > 0;
            @endphp
            <div class="pr-card-mobile">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="min-w-0">
                        <div class="fw-semibold mono">{{ $pr->code }}</div>
                        <div class="meta-row">
                            <span>{{ $pr->date?->format('d/m/Y') }}</span>
                            @if ($pr->supplier)
                                <span>· {{ $pr->supplier->name }}</span>
                            @endif
                            <span>· {{ $pr->requestedBy?->name ?? '—' }}</span>
                        </div>
                        <div class="meta-row mt-1">
                            <span>{{ $pr->lines_count ?? 0 }} item</span>
                            @if ($canSeeMoney && $hasEstimate)
                                <span>· Est. Rp {{ number_format($estTotal, 0, ',', '.') }}</span>
                            @endif
                            @if ($pr->convertedToPo)
                                <span>·
                                    <a href="{{ route('purchasing.purchase_orders.show', $pr->converted_to_po_id) }}"
                                        class="badge-po-ref">{{ $pr->convertedToPo->code }}</a>
                                </span>
                            @endif
                        </div>
                    </div>
                    <span class="pr-badge pr-{{ $pr->status }} flex-shrink-0">
                        {{ pr_status_label($pr->status) }}
                    </span>
                </div>
                <div class="mt-2">
                    <a href="{{ route('purchasing.purchase_requests.show', $pr->id) }}"
                        class="btn btn-xs btn-outline-secondary"
                        style="font-size:.78rem; border-radius:8px; padding:.15rem .6rem;">
                        Lihat Detail
                    </a>
                </div>
            </div>
        @empty
            <div class="text-center text-muted py-4">Tidak ada data.</div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if ($prs->hasPages())
        <div class="mt-3">
            {{ $prs->links() }}
        </div>
    @endif

</div>
@endsection
