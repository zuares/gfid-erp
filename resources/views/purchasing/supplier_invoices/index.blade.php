@extends('layouts.app')

@section('title', 'Faktur Supplier')

@php
    $user = auth()->user();
    $canSeeMoney = $user?->isOwner() || in_array($user?->role ?? '', ['accounting', 'developer']);
@endphp

@push('head')
<style>
    .page-wrap { max-width: 1080px; margin-inline: auto; padding-bottom: 3rem; }

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
        font-size: .8rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: var(--muted);
        white-space: nowrap;
    }
    .mono { font-variant-numeric: tabular-nums; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono"; }

    /* Status badge */
    .inv-badge {
        border-radius: 999px;
        font-size: .7rem;
        padding: .1rem .6rem;
        border: 1px solid transparent;
        white-space: nowrap;
    }
    .inv-draft      { background: rgba(148,163,184,.12); color: #64748b; border-color: rgba(148,163,184,.5); }
    .inv-posted     { background: rgba(59,130,246,.10); color: #1d4ed8; border-color: rgba(59,130,246,.5); }
    .inv-partial    { background: rgba(234,179,8,.12); color: #a16207; border-color: rgba(234,179,8,.5); }
    .inv-paid       { background: rgba(22,163,74,.12); color: #15803d; border-color: rgba(22,163,74,.5); }
    .inv-void       { background: rgba(220,38,38,.08); color: #b91c1c; border-color: rgba(220,38,38,.5); }

    /* Summary pills */
    .summary-pills { display: flex; flex-wrap: wrap; gap: .5rem; }
    .summary-pill  { background: var(--surface); border: 1px solid var(--line); border-radius: 10px; padding: .4rem .75rem; font-size: .82rem; }
    .summary-pill-label { color: var(--muted); font-size: .72rem; display: block; }
    .summary-pill-value { font-weight: 600; }

    /* Mobile card */
    .card-inv-mobile {
        border-bottom: 1px solid var(--line);
        padding: .75rem .9rem;
    }
    .card-inv-mobile:last-child { border-bottom: none; }
    .meta { font-size: .78rem; color: var(--muted); display: flex; flex-wrap: wrap; gap: .35rem; }
</style>
@endpush

@section('content')
<div class="page-wrap">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
        <h1 class="h5 mb-0 fw-bold">Faktur Supplier</h1>
        <a href="{{ route('purchasing.supplier_invoices.create') }}" class="btn btn-sm btn-primary">
            + Faktur Baru
        </a>
    </div>

    {{-- Summary --}}
    @if ($canSeeMoney)
    <div class="card-filter mb-3">
        <div class="summary-pills">
            <div class="summary-pill">
                <span class="summary-pill-label">Total Faktur</span>
                <span class="summary-pill-value mono">{{ $summary->total }}</span>
            </div>
            <div class="summary-pill">
                <span class="summary-pill-label">Draft</span>
                <span class="summary-pill-value mono">{{ $summary->draft_count }}</span>
            </div>
            <div class="summary-pill">
                <span class="summary-pill-label">Posted / Lunas</span>
                <span class="summary-pill-value mono">{{ $summary->posted_count }}</span>
            </div>
            <div class="summary-pill">
                <span class="summary-pill-label">Outstanding</span>
                <span class="summary-pill-value mono text-danger">{{ rupiah($summary->unpaid_total) }}</span>
            </div>
        </div>
    </div>
    @endif

    {{-- Filter --}}
    <div class="card-filter mb-3">
        <form method="GET" action="{{ route('purchasing.supplier_invoices.index') }}"
              class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label form-label-sm mb-1">Cari</label>
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="No. Faktur / Supplier…"
                       value="{{ request('search') }}">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label form-label-sm mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach (['draft'=>'Draft','posted'=>'Posted','partial_paid'=>'Bayar Sebagian','paid'=>'Lunas','void'=>'Void'] as $val => $lbl)
                        <option value="{{ $val }}" @selected(request('status') === $val)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label form-label-sm mb-1">Supplier</label>
                <select name="supplier_id" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach ($suppliers as $sup)
                        <option value="{{ $sup->id }}" @selected((string) request('supplier_id') === (string) $sup->id)>
                            {{ $sup->code }} — {{ $sup->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-outline-primary flex-fill">Filter</button>
                <a href="{{ route('purchasing.supplier_invoices.index') }}" class="btn btn-sm btn-outline-secondary">×</a>
            </div>
        </form>
    </div>

    {{-- DESKTOP TABLE --}}
    <div class="card-table d-none d-md-block">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:4%;" class="text-center">#</th>
                        <th style="width:12%;">Tanggal</th>
                        <th style="width:16%;">No. Faktur</th>
                        <th>Supplier</th>
                        <th style="width:14%;">No. PO</th>
                        @if ($canSeeMoney)
                            <th style="width:13%;" class="text-end">Total</th>
                            <th style="width:12%;" class="text-end">Outstanding</th>
                        @endif
                        <th style="width:12%;">Status</th>
                        <th style="width:8%;" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $inv)
                        @php
                            $badgeClass = match($inv->status) {
                                'posted'       => 'inv-badge inv-posted',
                                'partial_paid' => 'inv-badge inv-partial',
                                'paid'         => 'inv-badge inv-paid',
                                'void'         => 'inv-badge inv-void',
                                default        => 'inv-badge inv-draft',
                            };
                            $statusLabel = match($inv->status) {
                                'posted'       => 'POSTED',
                                'partial_paid' => 'PARTIAL',
                                'paid'         => 'LUNAS',
                                'void'         => 'VOID',
                                default        => 'DRAFT',
                            };
                            $outstanding = $inv->outstanding();
                        @endphp
                        <tr>
                            <td class="text-center">
                                {{ $loop->iteration + ($invoices->currentPage() - 1) * $invoices->perPage() }}
                            </td>
                            <td class="mono small">{{ id_date($inv->invoice_date) }}</td>
                            <td class="mono">
                                <a href="{{ route('purchasing.supplier_invoices.show', $inv->id) }}"
                                   class="text-decoration-none">
                                    {{ $inv->invoice_no }}
                                </a>
                                @if ($inv->supplier_invoice_ref)
                                    <div class="text-muted" style="font-size:.7rem;">{{ $inv->supplier_invoice_ref }}</div>
                                @endif
                            </td>
                            <td>{{ optional($inv->supplier)->name ?? '—' }}</td>
                            <td class="mono small">
                                @if ($inv->purchaseOrder)
                                    <a href="{{ route('purchasing.purchase_orders.show', $inv->purchase_order_id) }}"
                                       class="text-decoration-none">
                                        {{ $inv->purchaseOrder->code }}
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            @if ($canSeeMoney)
                                <td class="text-end mono">{{ rupiah($inv->total_amount) }}</td>
                                <td class="text-end mono {{ $outstanding > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ rupiah($outstanding) }}
                                </td>
                            @endif
                            <td><span class="{{ $badgeClass }}">{{ $statusLabel }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('purchasing.supplier_invoices.show', $inv->id) }}"
                                   class="btn btn-xs btn-outline-secondary btn-sm">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canSeeMoney ? 9 : 7 }}" class="text-center text-muted py-3">
                                Belum ada Faktur Supplier.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-3 py-2">{{ $invoices->links() }}</div>
    </div>

    {{-- MOBILE LIST --}}
    <div class="card-table d-md-none">
        @forelse ($invoices as $inv)
            @php
                $badgeClass = match($inv->status) {
                    'posted'       => 'inv-badge inv-posted',
                    'partial_paid' => 'inv-badge inv-partial',
                    'paid'         => 'inv-badge inv-paid',
                    'void'         => 'inv-badge inv-void',
                    default        => 'inv-badge inv-draft',
                };
                $statusLabel = match($inv->status) {
                    'posted'       => 'POSTED',
                    'partial_paid' => 'PARTIAL',
                    'paid'         => 'LUNAS',
                    'void'         => 'VOID',
                    default        => 'DRAFT',
                };
            @endphp
            <div class="card-inv-mobile">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <div>
                        <div class="fw-semibold mono">
                            <a href="{{ route('purchasing.supplier_invoices.show', $inv->id) }}"
                               class="text-decoration-none">
                                {{ $inv->invoice_no }}
                            </a>
                        </div>
                        <div class="meta mt-1">
                            <span class="mono">{{ id_date($inv->invoice_date) }}</span>
                            <span>{{ optional($inv->supplier)->name ?? '—' }}</span>
                        </div>
                    </div>
                    <span class="{{ $badgeClass }}">{{ $statusLabel }}</span>
                </div>
                @if ($canSeeMoney)
                    <div class="d-flex justify-content-between align-items-center mt-1">
                        <span class="text-muted small">Total</span>
                        <span class="mono small fw-semibold">{{ rupiah($inv->total_amount) }}</span>
                    </div>
                    @if ($inv->outstanding() > 0)
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Outstanding</span>
                            <span class="mono small text-danger">{{ rupiah($inv->outstanding()) }}</span>
                        </div>
                    @endif
                @endif
            </div>
        @empty
            <div class="p-4 text-center text-muted">Belum ada Faktur Supplier.</div>
        @endforelse
        <div class="px-3 py-2">{{ $invoices->links() }}</div>
    </div>

</div>
@endsection
