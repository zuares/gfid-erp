@extends('layouts.app')

@section('title', 'RTS • Permintaan')

@push('head')
<style>
    /* RTS Custom Table Styles */
    .rts-table { width: 100%; border-collapse: collapse; }
    
    .rts-table thead tr {
        background: rgba(148,163,184,.10);
        border-bottom: 1px solid rgba(148,163,184,.20);
    }
    .rts-table th {
        position: sticky; top: 0; z-index: 2;
        background: rgba(148,163,184,.10);
        backdrop-filter: none;
        padding: .48rem .75rem;
        font-size: .67rem; font-weight: 900; opacity: .65;
        text-transform: uppercase; letter-spacing: .09em;
        text-align: left; white-space: nowrap;
        box-shadow: 0 1px 0 rgba(148,163,184,.20);
    }
    .rts-table tbody tr {
        border-bottom: 1px solid rgba(148,163,184,.11);
        cursor: pointer;
        transition: background .10s;
    }
    .rts-table tbody tr:last-child { border-bottom: 0; }
    .rts-table tbody tr:hover { background: rgba(45,212,191,.05); }
    .rts-table td { padding: .52rem .75rem; vertical-align: middle; font-size: .85rem; }

    .mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }

    .col-date { width: 90px; white-space: nowrap; }
    .col-item { }
    .col-qty  { width: 80px; text-align: right; }
    .col-st   { width: 105px; }

    .doc-label {
        font-size: .66rem; opacity: .45;
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-weight: 700; margin-bottom: .2rem;
        display: block; letter-spacing: .02em;
    }

    .chips { display: flex; flex-wrap: wrap; gap: .22rem; }
    .ic {
        display: inline-flex; align-items: center; gap: .2rem;
        padding: .11rem .36rem; border-radius: 6px;
        border: 1px solid rgba(148,163,184,.20);
        background: rgba(148,163,184,.07);
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: .72rem; font-weight: 900; white-space: nowrap;
    }
    .ic span { font-weight: 400; opacity: .58; }

    .more-btn {
        font-size: .68rem; opacity: .50; cursor: pointer;
        padding: .11rem .36rem; border-radius: 6px;
        border: 1px solid rgba(148,163,184,.20);
        background: transparent; color: inherit; font-family: inherit;
    }

    .badge {
        display: inline-flex; padding: .15rem .50rem;
        border-radius: 999px; font-size: .70rem; font-weight: 800;
        border: 1px solid rgba(148,163,184,.35);
        background: rgba(148,163,184,.12); color: #475569; white-space: nowrap;
    }
    .badge.ok     { border-color: rgba(16,185,129,.40);  background: rgba(16,185,129,.12); color: #059669; }
    .badge.warn   { border-color: rgba(245,158,11,.40);  background: rgba(245,158,11,.12); color: #d97706; }
    .badge.danger { border-color: rgba(239,68,68,.40);   background: rgba(239,68,68,.12);  color: #dc2626; }

    @media (max-width: 768px) {
        .col-date, .col-qty, .col-st { display: none !important; }
        .col-item { width: 100%; display: block; }
    }
</style>
@endpush

@section('content')
@php
    $role      = strtolower((string)(auth()->user()?->role ?? ''));
    $canManage = in_array($role, ['owner','admin'], true);
    $statusNow = $statusFilter ?? 'all';
    $periodNow = $period ?? 'all';
    $searchNow = $search ?? '';
    $dateFromNow = $dateFrom ?? '';
    $dateToNow   = $dateTo   ?? '';
    $fmt = fn($n) => rtrim(rtrim(number_format((float)$n, 2, '.', ''), '0'), '.');
    $THRESHOLD = 3;
@endphp

<x-index-layout title="RTS • Permintaan Stok" subtitle="Permintaan barang jadi dari PRD ke RTS." tableClass="rts-table">
    <x-slot name="kpis">
        <span class="kpi"><span class="lbl">Total</span><span class="val mono">{{ number_format(isset($stockRequests) && method_exists($stockRequests, 'total') ? $stockRequests->total() : (isset($stockRequests) ? $stockRequests->count() : 0), 0, ',', '.') }}</span></span>
        <span class="kpi"><span class="lbl">Halaman</span><span class="val mono">{{ number_format(isset($stockRequests) ? $stockRequests->count() : 0, 0, ',', '.') }}</span></span>
        <span class="kpi"><span class="lbl">Gudang</span><span class="val mono">RTS</span></span>
    </x-slot>

    @if ($canManage)
        <x-slot name="actions">
            <a href="{{ route('rts.stock-requests.create') }}" class="btn btn-sm btn-ship-primary btn-pill">
                <i class="bi bi-plus-lg me-1"></i> Buat Permintaan
            </a>
        </x-slot>
    @endif

    <x-slot name="filters">
        <form method="GET" action="{{ route('rts.stock-requests.index') }}" id="filterForm">
            <div class="filter-bar mb-3">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <input type="text" id="inp-search" name="search" class="form-control form-control-sm search-input" style="max-width:200px;" value="{{ $searchNow }}" placeholder="Cari kode / item…" autocomplete="off">
                    
                    <select name="status" class="form-select form-select-sm po-filter-auto" style="max-width:140px;" onchange="this.form.submit()">
                        <option value="all"       {{ $statusNow==='all'       ? 'selected':'' }}>Semua status</option>
                        <option value="submitted" {{ $statusNow==='submitted' ? 'selected':'' }}>Menunggu</option>
                        <option value="partial"   {{ $statusNow==='partial'   ? 'selected':'' }}>Sebagian</option>
                        <option value="completed" {{ $statusNow==='completed' ? 'selected':'' }}>Selesai</option>
                    </select>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 align-items-center mb-3" style="background: rgba(148,163,184,.08); border: 1px dashed rgba(148,163,184,.35); padding: .75rem .85rem; border-radius: 10px;">
                <div style="font-size: .8rem; font-weight: 600; color: #64748b; margin-right: .5rem;">Filter Tanggal:</div>
                <x-date-range-picker 
                    :date-from="$dateFromNow" 
                    :date-to="$dateToNow" 
                    :period="$periodNow" 
                    form-id="filterForm" 
                />

                @if($searchNow || $dateFromNow || $dateToNow || $statusNow !== 'all' || $periodNow !== 'all')
                    <a href="{{ route('rts.stock-requests.index') }}" class="btn btn-sm btn-ship-outline btn-pill ms-auto" style="height: 32px; display: flex; align-items: center; background: #fff;">
                        <i class="bi bi-x me-1"></i>Reset Semua Filter
                    </a>
                @endif
            </div>
        </form>
    </x-slot>

    @if ($stockRequests->count() === 0)
        <x-slot name="emptyState">
            <div class="empty" style="text-align: center; padding: 2.5rem; opacity: .45; font-size: .86rem;">Belum ada permintaan RTS.</div>
        </x-slot>
    @endif

    <x-slot name="thead">
        <tr>
            <th class="col-date">Tanggal</th>
            <th class="col-item">Item</th>
            <th class="col-qty">Qty</th>
            <th class="col-st">Status</th>
        </tr>
    </x-slot>

    @forelse($stockRequests as $sr)
        @php
            $req  = (float)($sr->total_requested_qty ?? 0);
            $recv = (float)($sr->total_received_qty  ?? 0);
            $pick = (float)($sr->total_picked_qty    ?? 0);
            $sisa = max($req - $recv - $pick, 0);
            $lines = $sr->lines ?? collect();
            $url  = route('rts.stock-requests.show', $sr);

            $statusMap = [
                'completed' => ['ok',      'Selesai'],
                'partial'   => ['warn',    'Sebagian'],
                'submitted' => ['danger',  'Menunggu'],
                'shipped'   => ['warn',    'Dikirim'],
                'cancelled' => ['',        'Dibatal'],
            ];
            [$badgeType, $badgeText] = $statusMap[$sr->status] ?? ['', ucfirst($sr->status ?? '-')];
        @endphp
        <tr data-href="{{ $url }}" style="cursor: pointer;">
            <td class="col-date mono">
                <div class="d-flex align-items-center gap-2" style="opacity:.62;font-size:.76rem;line-height:1.3;">
                    <span>{{ optional($sr->date)->format('d M Y') }}</span>
                    @if($sr->updated_at)
                        <span style="font-size:.65rem;opacity:.7;">{{ $sr->updated_at->format('H:i') }}</span>
                    @endif
                </div>
            </td>
            <td class="col-item">
                <span class="doc-label">{{ $sr->code }}</span>
                <div class="chips">
                    @foreach($lines->take($THRESHOLD) as $ln)
                        <span class="ic">{{ $ln->item?->code ?? '—' }}<span>{{ $fmt($ln->qty_request) }}</span></span>
                    @endforeach
                    @if($lines->count() > $THRESHOLD)
                        @foreach($lines->skip($THRESHOLD) as $ln)
                            <span class="ic" data-extra="{{ $sr->id }}" style="display:none">
                                {{ $ln->item?->code ?? '—' }}<span>{{ $fmt($ln->qty_request) }}</span>
                            </span>
                        @endforeach
                        <button class="more-btn" data-id="{{ $sr->id }}"
                            onclick="event.stopPropagation();toggleMore(this)">
                            +{{ $lines->count() - $THRESHOLD }}
                        </button>
                    @endif
                </div>
            </td>
            <td class="col-qty mono" style="font-weight:900">{{ $fmt($req) }}</td>
            <td class="col-st">
                <span class="badge {{ $badgeType }}">{{ $badgeText }}</span>
            </td>
        </tr>
    @empty
    @endforelse

    <x-slot name="pagination">
        @if (method_exists($stockRequests, 'links'))
            {{ $stockRequests->links() }}
        @endif
    </x-slot>
</x-index-layout>

@push('scripts')
<script>
// ── Expand chips ──────────────────────────────────────────
function toggleMore(btn) {
    const id = btn.dataset.id;
    const extras = document.querySelectorAll(`[data-extra="${id}"]`);
    const isHidden = extras[0]?.style.display === 'none';
    extras.forEach(el => el.style.display = isHidden ? 'inline-flex' : 'none');
    btn.textContent = isHidden ? '−' : '+' + extras.length;
}
</script>
@endpush
@endsection
