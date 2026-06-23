@extends('layouts.app')

@section('title', 'PR — ' . $purchase_request->code)

@php
    $pr = $purchase_request;
@endphp

@push('head')
<style>
    .pr-show-wrap { max-width: 960px; margin-inline: auto; padding-bottom: 3rem; }

    .pr-info-card {
        background: var(--card);
        border-radius: 14px;
        border: 1px solid var(--line);
        margin-bottom: .85rem;
    }
    .pr-info-label {
        font-size: .72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--muted);
        margin-bottom: .15rem;
    }
    .pr-info-value { font-size: .92rem; font-weight: 500; }

    /* Status badge */
    .pr-badge {
        border-radius: 999px;
        font-size: .75rem;
        padding: .18rem .75rem;
        border: 1px solid transparent;
        white-space: nowrap;
        font-weight: 600;
    }
    .pr-draft    { background: rgba(148,163,184,.14); color: #64748b; border-color: rgba(148,163,184,.5); }
    .pr-approved { background: rgba(22,163,74,.12); color: #15803d; border-color: rgba(22,163,74,.5); }
    .pr-rejected { background: rgba(220,38,38,.08); color: #b91c1c; border-color: rgba(220,38,38,.5); }
    .pr-converted { background: rgba(59,130,246,.10); color: #1d4ed8; border-color: rgba(59,130,246,.5); }
    .pr-cancelled { background: rgba(100,116,139,.08); color: #475569; border-color: rgba(100,116,139,.4); }

    .table thead th {
        font-size: .75rem;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--muted);
        border-bottom-width: 1px;
        white-space: nowrap;
    }
    .mono { font-variant-numeric: tabular-nums; }
    .btn-action { border-radius: 10px; font-size: .82rem; padding: .3rem .85rem; }

    /* Timeline / info bawah */
    .timeline-item { display: flex; gap: .6rem; align-items: flex-start; }
    .timeline-dot  { width: 8px; height: 8px; border-radius: 50%; background: var(--muted); margin-top: .35rem; flex-shrink: 0; }
    .timeline-dot.green  { background: #16a34a; }
    .timeline-dot.red    { background: #dc2626; }
    .timeline-dot.blue   { background: #2563eb; }
</style>
@endpush

@section('content')
<div class="pr-show-wrap">

    {{-- Top action bar --}}
    <div class="d-flex align-items-center justify-content-between mb-3 gap-2 flex-wrap">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('purchasing.purchase_requests.index') }}"
                class="btn btn-sm btn-outline-secondary btn-action">← Daftar PR</a>
            <h1 class="h5 mb-0 fw-bold">{{ $pr->code }}</h1>
            <span class="pr-badge pr-{{ $pr->status }}">
                {{ pr_status_label($pr->status) }}
            </span>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            {{-- Edit — hanya jika masih draft --}}
            @if ($pr->isDraft())
                <a href="{{ route('purchasing.purchase_requests.edit', $pr->id) }}"
                    class="btn btn-sm btn-outline-secondary btn-action">✏ Edit</a>
            @endif

            {{-- Approve / Reject — owner/admin, PR masih draft --}}
            @if ($canApproveReject)
                <form method="POST"
                    action="{{ route('purchasing.purchase_requests.approve', $pr->id) }}"
                    onsubmit="return confirm('Approve PR {{ $pr->code }}?')">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success btn-action">✔ Approve</button>
                </form>

                <form method="POST"
                    action="{{ route('purchasing.purchase_requests.reject', $pr->id) }}"
                    onsubmit="return confirm('Tolak PR {{ $pr->code }}?')">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-danger btn-action">✖ Tolak</button>
                </form>
            @endif
        </div>
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2">
            {{ session('success') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show py-2">
            {{ session('error') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Info header --}}
    <div class="pr-info-card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <div class="pr-info-label">Tanggal</div>
                    <div class="pr-info-value">{{ $pr->date?->format('d/m/Y') ?? '—' }}</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="pr-info-label">Status</div>
                    <div>
                        <span class="pr-badge pr-{{ $pr->status }}">
                            {{ pr_status_label($pr->status) }}
                        </span>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="pr-info-label">Supplier</div>
                    <div class="pr-info-value">
                        {{ $pr->supplier ? $pr->supplier->code . ' — ' . $pr->supplier->name : '—' }}
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="pr-info-label">Diminta Oleh</div>
                    <div class="pr-info-value">{{ $pr->requestedBy?->name ?? '—' }}</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="pr-info-label">Dibuat</div>
                    <div class="pr-info-value" style="font-size:.82rem;">
                        {{ $pr->created_at?->format('d/m/Y H:i') ?? '—' }}
                    </div>
                </div>

                @if ($pr->notes)
                    <div class="col-12">
                        <div class="pr-info-label">Catatan</div>
                        <div class="pr-info-value" style="white-space:pre-line;">{{ $pr->notes }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Lines table --}}
    <div class="pr-info-card">
        <div class="card-header d-flex align-items-center"
            style="background:transparent; border-bottom:1px solid var(--line); padding:.85rem 1rem;">
            <div class="fw-semibold" style="font-size:.95rem;">
                Detail Item yang Diminta
                <span class="text-muted fw-normal ms-1">({{ $pr->lines->count() }} item)</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="4%" class="text-center">No</th>
                        <th>Item</th>
                        <th>Unit</th>
                        <th class="text-end" style="min-width:90px;">Qty</th>
                        @if ($canSeeMoney)
                            <th class="text-end" style="min-width:110px;">Harga Est.</th>
                            <th class="text-end" style="min-width:120px;">Total Est.</th>
                        @endif
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pr->lines as $i => $line)
                        <tr>
                            <td class="text-center text-muted">{{ $i + 1 }}</td>
                            <td>
                                @if ($line->item)
                                    <span class="fw-semibold mono">{{ $line->item->code }}</span>
                                    <span class="text-muted ms-1">{{ $line->item->name }}</span>
                                @else
                                    <span class="text-muted fst-italic">Item tidak ditemukan</span>
                                @endif
                            </td>
                            <td class="text-muted">{{ $line->item?->unit ?? '—' }}</td>
                            <td class="text-end mono">{{ number_format($line->qty, 2, ',', '.') }}</td>
                            @if ($canSeeMoney)
                                <td class="text-end mono">
                                    {{ $line->unit_price !== null ? 'Rp ' . number_format($line->unit_price, 0, ',', '.') : '—' }}
                                </td>
                                <td class="text-end mono">
                                    @if ($line->unit_price !== null)
                                        Rp {{ number_format($line->qty * $line->unit_price, 0, ',', '.') }}
                                    @else
                                        —
                                    @endif
                                </td>
                            @endif
                            <td class="text-muted" style="font-size:.85rem;">{{ $line->notes ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canSeeMoney ? 7 : 4 }}" class="text-center text-muted py-3">
                                Tidak ada item.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($canSeeMoney && $pr->lines->whereNotNull('unit_price')->count() > 0)
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="5" class="text-end">Total Estimasi</th>
                            <th class="text-end mono">
                                Rp {{ number_format(
                                    $pr->lines->sum(fn($l) => ($l->qty ?? 0) * ($l->unit_price ?? 0)),
                                    0, ',', '.'
                                ) }}
                            </th>
                            <th></th>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Status history / approved / rejected by --}}
    @if ($pr->isApproved() || $pr->isRejected())
        <div class="pr-info-card">
            <div class="card-body">
                <div class="pr-info-label mb-2">Riwayat Status</div>
                <div class="d-flex flex-column gap-2">
                    <div class="timeline-item">
                        <div class="timeline-dot blue"></div>
                        <div style="font-size:.85rem;">
                            Dibuat oleh <strong>{{ $pr->requestedBy?->name ?? '—' }}</strong>
                            <span class="text-muted ms-1">{{ $pr->created_at?->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>

                    @if ($pr->isApproved())
                        <div class="timeline-item">
                            <div class="timeline-dot green"></div>
                            <div style="font-size:.85rem;">
                                Di-approve oleh <strong>{{ $pr->approvedBy?->name ?? '—' }}</strong>
                                <span class="text-muted ms-1">{{ $pr->updated_at?->format('d/m/Y H:i') }}</span>
                            </div>
                        </div>
                    @endif

                    @if ($pr->isRejected())
                        <div class="timeline-item">
                            <div class="timeline-dot red"></div>
                            <div style="font-size:.85rem;">
                                Ditolak oleh <strong>{{ $pr->rejectedBy?->name ?? '—' }}</strong>
                                <span class="text-muted ms-1">{{ $pr->updated_at?->format('d/m/Y H:i') }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

</div>
@endsection
