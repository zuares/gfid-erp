@extends('layouts.app')

@section('title', 'Akuntansi Baru • Stock Opname & Variance')

@push('head')
    <style>
        .variance-page { max-width: 1280px; margin-inline: auto; }
        .variance-btn { display:inline-flex; align-items:center; gap:.4rem; min-height:38px; padding:.45rem .85rem; border:1px solid #dbe3ee; border-radius:10px; background:#fff; color:#0f172a; text-decoration:none; font-size:.84rem; font-weight:700; }
        .variance-btn:hover { background:#f8fafc; color:#0f172a; }
        .variance-btn.primary { background:#0f172a; border-color:#0f172a; color:#fff; }
        .variance-btn.primary:hover { background:#1e293b; color:#fff; }
        .variance-btn:focus-visible { outline:3px solid rgba(59,130,246,.35); outline-offset:2px; }
        .variance-toolbar { display:grid; grid-template-columns:1.4fr 1fr 1fr 1fr 1.4fr auto; gap:.65rem; align-items:end; }
        .variance-toolbar label { margin-bottom:.3rem; color:#475569; font-size:.72rem; font-weight:800; }
        .variance-table th { color:#64748b; font-size:.7rem; letter-spacing:.03em; text-transform:uppercase; white-space:nowrap; }
        .variance-table td { vertical-align:middle; }
        .variance-code { color:#0f172a; font-weight:800; text-decoration:none; }
        .variance-code:hover { color:#4338ca; }
        .variance-muted { color:#64748b; font-size:.78rem; }
        .variance-status { display:inline-flex; padding:.22rem .5rem; border-radius:999px; background:#f1f5f9; color:#475569; font-size:.72rem; font-weight:800; white-space:nowrap; }
        .variance-status.approved, .variance-status.finalized { background:#dcfce7; color:#166534; }
        .variance-status.pending, .variance-status.reviewed { background:#fef3c7; color:#92400e; }
        .variance-status.void, .variance-status.cancelled { background:#fee2e2; color:#991b1b; }
        .variance-empty { padding:3rem 1rem; text-align:center; color:#64748b; }
        @media (max-width: 1050px) { .variance-toolbar { grid-template-columns:repeat(3,minmax(0,1fr)); } }
        @media (max-width: 650px) { .variance-toolbar { grid-template-columns:1fr; } .variance-actions { width:100%; } .variance-actions .variance-btn { flex:1; justify-content:center; } }
    </style>
@endpush

@php
    $statusLabel = fn (?string $status): string => $statusOptions[$status] ?? ucfirst((string) $status);
    $statusClass = fn (?string $status): string => match ($status) {
        'approved', 'finalized' => 'approved',
        'pending', 'reviewed' => 'pending',
        'void', 'cancelled' => 'void',
        default => '',
    };
@endphp

@section('content')
    <div class="variance-page">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
            <div>
                <h4 class="mb-1">Stock Opname &amp; Variance</h4>
                <div class="text-muted small">Read-only audit view dari dokumen Inventory dan jurnal Akuntansi Baru.</div>
            </div>
            <div class="d-flex gap-2 flex-wrap variance-actions">
                <a class="variance-btn" href="{{ route('standalone-accounting.journal-menu.index') }}"><i class="bi bi-grid-1x2" aria-hidden="true"></i> Menu Jurnal</a>
                <a class="variance-btn primary" href="{{ route('standalone-accounting.journals.index') }}"><i class="bi bi-journal-text" aria-hidden="true"></i> Jurnal Baru</a>
            </div>
        </div>

        <div class="alert alert-info small mb-3">
            Approval dan perubahan stok tetap dilakukan di modul Inventory. Halaman ini hanya untuk monitoring, rekonsiliasi, dan penelusuran jurnal.
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <form class="variance-toolbar" method="GET" action="{{ route('standalone-accounting.stock-opname-variance.index') }}" role="search">
                    <div><label for="q">Cari dokumen / gudang</label><input id="q" name="q" class="form-control form-control-sm" value="{{ $filters['q'] }}" placeholder="SO-... atau kode gudang"></div>
                    <div><label for="warehouse_id">Gudang</label><select id="warehouse_id" name="warehouse_id" class="form-select form-select-sm"><option value="">Semua gudang</option>@foreach ($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected((string) $filters['warehouse_id'] === (string) $warehouse->id)>{{ $warehouse->code }} — {{ $warehouse->name }}</option>@endforeach</select></div>
                    <div><label for="status">Status</label><select id="status" name="status" class="form-select form-select-sm">@foreach ($statusOptions as $key => $label)<option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>@endforeach</select></div>
                    <div><label for="date_from">Tanggal mulai</label><input id="date_from" name="date_from" type="date" class="form-control form-control-sm" value="{{ $filters['date_from'] }}"></div>
                    <div><label for="date_to">Tanggal akhir</label><input id="date_to" name="date_to" type="date" class="form-control form-control-sm" value="{{ $filters['date_to'] }}"></div>
                    <div class="d-flex gap-2"><button class="variance-btn primary" type="submit"><i class="bi bi-funnel" aria-hidden="true"></i> Terapkan</button><a class="variance-btn" href="{{ route('standalone-accounting.stock-opname-variance.index') }}" title="Reset filter"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i><span class="visually-hidden">Reset filter</span></a></div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-sm variance-table align-middle mb-0">
                    <thead class="table-light"><tr><th>Dokumen sumber</th><th>Gudang</th><th>Cutoff</th><th>Nilai variance</th><th>Status approval</th><th>Jurnal baru</th></tr></thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td><a class="variance-code" href="{{ route('inventory.stock_opnames.show', $row->opname) }}">{{ $row->opname->code }}</a><div class="variance-muted">{{ ucfirst($row->opname->type) }} · {{ $row->opname->lines_count }} baris</div></td>
                                <td><div class="fw-semibold">{{ $row->opname->warehouse?->code ?: '-' }}</div><div class="variance-muted">{{ $row->opname->warehouse?->name ?: '-' }}</div></td>
                                <td><div>{{ $row->opname->snapshot_at?->format('d-m-Y H:i') ?: '-' }}</div><div class="variance-muted">Dokumen {{ $row->opname->date?->format('d-m-Y') ?: '-' }}</div></td>
                                <td>@if ($row->variance_amount > 0)<div class="fw-semibold">Rp {{ number_format($row->variance_amount, 0, ',', '.') }}</div><div class="variance-muted">{{ $row->variance_side }} · akun {{ $row->variance_account_code }}</div>@else<span class="variance-muted">Belum diposting</span>@endif</td>
                                <td><span class="variance-status {{ $statusClass($row->approval_status) }}">{{ $statusLabel($row->approval_status) }}</span>@if ($row->adjustment)<div class="variance-muted mt-1">Adj: {{ $row->adjustment->code }}</div>@endif</td>
                                <td>@if ($row->journal)<a class="variance-btn" href="{{ route('standalone-accounting.journals.show', $row->journal) }}"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Lihat jurnal</a>@else<span class="variance-muted">Belum ada mirror</span>@endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><div class="variance-empty"><i class="bi bi-clipboard2-x fs-2 d-block mb-2" aria-hidden="true"></i><div>Belum ada dokumen stock opname.</div><div class="small mt-1">Dokumen yang dibuat di Inventory akan muncul di sini.</div></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($opnames->hasPages())<div class="card-footer bg-white">{{ $opnames->links() }}</div>@endif
        </div>
    </div>
@endsection
