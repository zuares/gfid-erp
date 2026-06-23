@extends('layouts.app')

@section('title', 'Purchase Request')

@php
    $user        = auth()->user();
    $canSeeMoney = $user?->isOwner();
@endphp

@push('head')
<style>
    .page-wrap { max-width: 1100px; margin-inline: auto; padding-bottom: 3rem; }

    .card-filter {
        background: var(--card);
        border-radius: 14px;
        border: 1px solid var(--line);
        padding: .85rem .95rem;
        margin-bottom: .85rem;
    }
    .card-table {
        background: var(--card);
        border-radius: 14px;
        border: 1px solid var(--line);
        overflow: hidden;
    }
    .table thead th {
        border-bottom-width: 1px;
        font-size: .78rem;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: var(--muted);
        white-space: nowrap;
    }
    .mono { font-variant-numeric: tabular-nums; }

    /* Status badge */
    .pr-badge {
        border-radius: 999px;
        font-size: .7rem;
        padding: .1rem .6rem;
        border: 1px solid transparent;
        white-space: nowrap;
        display: inline-block;
    }
    .pr-draft    { background: rgba(148,163,184,.12); color: #64748b; border-color: rgba(148,163,184,.5); }
    .pr-approved { background: rgba(22,163,74,.12); color: #15803d; border-color: rgba(22,163,74,.5); }
    .pr-rejected { background: rgba(220,38,38,.08); color: #b91c1c; border-color: rgba(220,38,38,.5); }
    .pr-converted { background: rgba(59,130,246,.10); color: #1d4ed8; border-color: rgba(59,130,246,.5); }
    .pr-cancelled { background: rgba(100,116,139,.08); color: #475569; border-color: rgba(100,116,139,.4); }

    /* Summary pills */
    .summary-pills { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: .75rem; }
    .summary-pill  { background: var(--surface); border: 1px solid var(--line); border-radius: 10px; padding: .4rem .75rem; font-size: .82rem; cursor: pointer; text-decoration: none; color: inherit; }
    .summary-pill:hover { border-color: var(--primary); }
    .summary-pill-label { font-size: .7rem; color: var(--muted); display: block; }
    .summary-pill-value { font-weight: 700; }

    /* Mobile card */
    .pr-card-mobile {
        border-bottom: 1px solid var(--line);
        padding: .75rem .9rem;
    }
    .pr-card-mobile:last-child { border-bottom: none; }
    .meta-row { font-size: .78rem; color: var(--muted); display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .25rem; }
</style>
@endpush

@section('content')
<div class="page-wrap">

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

    {{-- Summary pills --}}
    <div class="summary-pills">
        <a href="{{ route('purchasing.purchase_requests.index') }}" class="summary-pill">
            <span class="summary-pill-label">Total</span>
            <span class="summary-pill-value">{{ $summary['total'] }}</span>
        </a>
        <a href="{{ route('purchasing.purchase_requests.index', ['status' => 'draft']) }}" class="summary-pill">
            <span class="summary-pill-label">Draft</span>
            <span class="summary-pill-value">{{ $summary['draft'] }}</span>
        </a>
        <a href="{{ route('purchasing.purchase_requests.index', ['status' => 'approved']) }}" class="summary-pill">
            <span class="summary-pill-label">Approved</span>
            <span class="summary-pill-value">{{ $summary['approved'] }}</span>
        </a>
        <a href="{{ route('purchasing.purchase_requests.index', ['status' => 'rejected']) }}" class="summary-pill">
            <span class="summary-pill-label">Ditolak</span>
            <span class="summary-pill-value">{{ $summary['rejected'] }}</span>
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
                    @foreach (['draft' => 'Draft', 'approved' => 'Approved', 'rejected' => 'Ditolak', 'converted' => 'Converted', 'cancelled' => 'Dibatalkan'] as $val => $label)
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
                <a href="{{ route('purchasing.purchase_requests.index') }}" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
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
                    <th>Diminta Oleh</th>
                    <th class="text-center">Jml Item</th>
                    <th class="text-center">Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($prs as $pr)
                    <tr>
                        <td class="mono fw-semibold">{{ $pr->code }}</td>
                        <td class="text-nowrap">{{ $pr->date?->format('d/m/Y') }}</td>
                        <td>{{ $pr->supplier?->name ?? '—' }}</td>
                        <td>{{ $pr->requestedBy?->name ?? '—' }}</td>
                        <td class="text-center">
                            {{ $pr->lines_count ?? '—' }}
                        </td>
                        <td class="text-center">
                            <span class="pr-badge pr-{{ $pr->status }}">
                                {{ pr_status_label($pr->status) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('purchasing.purchase_requests.show', $pr->id) }}"
                                class="btn btn-xs btn-outline-secondary" style="font-size:.78rem; border-radius:8px; padding:.15rem .6rem;">
                                Lihat
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Tidak ada data Purchase Request.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- CARD LIST — mobile --}}
    <div class="card-table d-md-none">
        @forelse ($prs as $pr)
            <div class="pr-card-mobile">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="fw-semibold mono">{{ $pr->code }}</div>
                        <div class="meta-row">
                            <span>{{ $pr->date?->format('d/m/Y') }}</span>
                            @if ($pr->supplier)
                                <span>· {{ $pr->supplier->name }}</span>
                            @endif
                            <span>· {{ $pr->requestedBy?->name ?? '—' }}</span>
                        </div>
                    </div>
                    <span class="pr-badge pr-{{ $pr->status }}">
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
