{{--
╔══════════════════════════════════════════════════════════════════════════════╗
║  TEMPLATE INDEX PAGE — GFID ERP                                            ║
║  Copy file ini, rename, lalu ganti semua placeholder:                      ║
║    [JUDUL]      → nama modul, misal "Purchase Requests"                    ║
║    [SUB]        → subjudul singkat, misal "Daftar PR"                      ║
║    [ROUTE]      → prefix route, misal "purchasing.purchase_requests"       ║
║    [MODEL]      → nama variabel koleksi, misal "$requests"                 ║
║    [COL_*]      → nama kolom tabel sesuai kebutuhan                        ║
╚══════════════════════════════════════════════════════════════════════════════╝
--}}
@extends('layouts.app')

@section('title', '[JUDUL]')

@php
    $user     = auth()->user();
    $canSee   = $user?->isOwner() ?? false;   // ganti kondisi sesuai modul

    // ── Sorting ────────────────────────────────────────────────────────────
    // Daftar kolom yang boleh disort — sesuaikan dengan kolom tabel DB
    $allowedSort = ['created_at', 'COL_A', 'COL_B', 'COL_C'];
    $sortCol = in_array(request('sort'), $allowedSort, true) ? request('sort') : 'created_at';
    $sortDir = request('dir') === 'asc' ? 'asc' : 'desc';

    $sortUrl  = fn(string $col) => request()->fullUrlWithQuery([
        'sort' => $col,
        'dir'  => ($sortCol === $col && $sortDir === 'asc') ? 'desc' : 'asc',
        'page' => 1,
    ]);
    $sortIcon = fn(string $col) => $sortCol === $col
        ? ($sortDir === 'asc' ? '↑' : '↓')
        : '↕';

    // ── Flatpickr range display (Indonesian) ──────────────────────────────
    $idMonths    = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $rangeDisplay = '';
    if (request('from') && request('to')) {
        try {
            $f = \Carbon\Carbon::parse(request('from'));
            $t = \Carbon\Carbon::parse(request('to'));
            $rangeDisplay = $f->day.' '.$idMonths[$f->month-1]
                .' – '.$t->day.' '.$idMonths[$t->month-1].' '.$t->year;
        } catch (\Exception $e) {}
    } elseif (request('from')) {
        try {
            $f = \Carbon\Carbon::parse(request('from'));
            $rangeDisplay = $f->day.' '.$idMonths[$f->month-1].' '.$f->year;
        } catch (\Exception $e) {}
    }
@endphp

@push('head')
<style>
/* ─────────────────────────────────────────────────────────────────────────────
   LAYOUT
───────────────────────────────────────────────────────────────────────────── */
.page-wrap {
    max-width: 1080px;
    margin-inline: auto;
    padding-bottom: 3rem;
}

/* ─────────────────────────────────────────────────────────────────────────────
   KPI STRIP  (4 kolom → 2 di mobile)
───────────────────────────────────────────────────────────────────────────── */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: .6rem;
    margin-bottom: .85rem;
}
.kpi-cell {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 12px;
    padding: .72rem .82rem;
    min-width: 0;
}
.kpi-label {
    font-size: .68rem;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--muted);
    font-weight: 800;
    margin-bottom: .18rem;
}
.kpi-value {
    font-size: .95rem;
    font-weight: 850;
    line-height: 1.2;
}
.kpi-sub {
    font-size: .72rem;
    color: var(--muted);
    margin-top: .08rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ─────────────────────────────────────────────────────────────────────────────
   FILTER CARD
───────────────────────────────────────────────────────────────────────────── */
.card-filter {
    background: var(--card);
    border-radius: 14px;
    border: 1px solid var(--line);
    padding: .8rem .9rem;
    margin-bottom: .85rem;
}
.filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    align-items: center;
}

/* ─────────────────────────────────────────────────────────────────────────────
   TABLE CARD
───────────────────────────────────────────────────────────────────────────── */
.card-table {
    background: var(--card);
    border-radius: 14px;
    border: 1px solid var(--line);
    overflow: hidden;
}

.table thead th {
    background: color-mix(in srgb, var(--card) 90%, var(--bg) 10%);
    border-bottom-color: var(--line);
    font-size: .68rem;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--muted);
    white-space: nowrap;
    padding: .52rem .65rem;
}
.table tbody td {
    vertical-align: middle;
    font-size: .83rem;
    padding: .58rem .65rem;
    border-bottom-color: var(--line);
}
.table tbody tr:last-child td { border-bottom: none; }
.tbl-row:hover { background: rgba(59,130,246,.04); }

/* ─────────────────────────────────────────────────────────────────────────────
   SORTABLE HEADER
───────────────────────────────────────────────────────────────────────────── */
.th-sort {
    cursor: pointer;
    user-select: none;
    text-decoration: none;
    color: var(--muted);
    display: inline-flex;
    align-items: center;
    gap: .25rem;
}
.th-sort:hover  { color: var(--body); }
.th-sort.active { color: var(--body); font-weight: 700; }

/* ─────────────────────────────────────────────────────────────────────────────
   BADGES
   Pola: .badge-status sebagai base, + modifier spesifik
───────────────────────────────────────────────────────────────────────────── */
.badge-status {
    border-radius: 999px;
    font-size: .7rem;
    padding: .1rem .6rem;
    border: 1px solid transparent;
    white-space: nowrap;
    display: inline-block;
}

/* Status dokumen */
.badge-draft     { background:rgba(148,163,184,.12); color:#64748b; border-color:rgba(148,163,184,.5); }
.badge-approved  { background:rgba(22,163,74,.12);   color:#15803d; border-color:rgba(22,163,74,.6); }
.badge-posted    { background:rgba(22,163,74,.12);   color:#15803d; border-color:rgba(22,163,74,.6); }
.badge-cancelled { background:rgba(220,38,38,.08);   color:#b91c1c; border-color:rgba(220,38,38,.6); }
.badge-voided    { background:rgba(220,38,38,.08);   color:#b91c1c; border-color:rgba(220,38,38,.6); }
.badge-closed    { background:rgba(30,58,138,.08);   color:#1e3a8a; border-color:rgba(30,58,138,.4); }
.badge-danger    { background:rgba(220,38,38,.08);   color:#b91c1c; border-color:rgba(220,38,38,.6); }
.badge-partial   { background:rgba(234,179,8,.10);   color:#a16207; border-color:rgba(234,179,8,.55); }

/* Status bayar */
.badge-pay         { border-radius:999px; font-size:.7rem; padding:.1rem .55rem; border:1px solid transparent; white-space:nowrap; display:inline-block; }
.badge-pay-paid    { background:rgba(22,163,74,.12);  color:#15803d; border-color:rgba(22,163,74,.55); }
.badge-pay-partial { background:rgba(234,179,8,.12);  color:#a16207; border-color:rgba(234,179,8,.55); }
.badge-pay-unpaid  { background:rgba(148,163,184,.10);color:#64748b; border-color:rgba(148,163,184,.45); }

/* Status terima */
.badge-rcv         { border-radius:999px; font-size:.65rem; padding:.05rem .45rem; border:1px solid transparent; white-space:nowrap; display:inline-block; }
.badge-rcv-none    { background:rgba(148,163,184,.08); color:#94a3b8; border-color:rgba(148,163,184,.4); }
.badge-rcv-partial { background:rgba(234,179,8,.10);   color:#a16207; border-color:rgba(234,179,8,.5); }
.badge-rcv-full    { background:rgba(22,163,74,.10);   color:#15803d; border-color:rgba(22,163,74,.5); }

/* ─────────────────────────────────────────────────────────────────────────────
   TYPOGRAPHY HELPERS
───────────────────────────────────────────────────────────────────────────── */
.mono {
    font-variant-numeric: tabular-nums;
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
}
.row-code  { font-weight: 850; font-size: .85rem; white-space: nowrap; }
.row-sub   { font-size: .72rem; color: var(--muted); white-space: nowrap; }
.row-total { font-size: .86rem; font-weight: 850; white-space: nowrap; }

/* ─────────────────────────────────────────────────────────────────────────────
   MOBILE CARD LIST  (menggantikan tabel di < md)
───────────────────────────────────────────────────────────────────────────── */
.card-mobile-item {
    background: var(--card);
    border-radius: 12px;
    border: 1px solid var(--line);
    padding: .75rem .85rem;
    margin-bottom: .6rem;
}
.card-mobile-item .item-title   { font-size: .92rem; font-weight: 700; margin-bottom: .2rem; }
.card-mobile-item .item-meta    { font-size: .75rem; color: var(--muted); }
.card-mobile-item .item-meta span+span::before { content:"•"; margin-inline:.35rem; opacity:.65; }
.card-mobile-item .item-amount  { font-size: .95rem; font-weight: 700; }

/* ─────────────────────────────────────────────────────────────────────────────
   RESPONSIVE
───────────────────────────────────────────────────────────────────────────── */
@media (max-width: 767.98px) {
    .page-wrap   { padding-inline: .75rem; }
    .card-filter { padding: .75rem .8rem; }
    .kpi-grid    { grid-template-columns: repeat(2, minmax(0,1fr)); gap:.5rem; }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('idx-filter-form');

    // Auto-submit select filters
    document.querySelectorAll('.filter-auto').forEach(el =>
        el.addEventListener('change', () => form && form.submit())
    );

    // Debounce text search
    const searchInput = document.getElementById('filter-search');
    if (searchInput) {
        let timer;
        searchInput.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => form && form.submit(), 500);
        });
    }

    // Flatpickr range — tanggal
    const fromHidden  = document.getElementById('filter-from');
    const toHidden    = document.getElementById('filter-to');
    const rangeInput  = document.getElementById('filter-date-range');

    if (rangeInput && typeof flatpickr !== 'undefined') {
        const ID_MONTHS = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        const fmtDate   = (d, withYear) =>
            d.getDate()+' '+ID_MONTHS[d.getMonth()]+(withYear?' '+d.getFullYear():'');
        const fmtRange  = dates => {
            if (dates.length === 2) {
                const sy = dates[0].getFullYear() === dates[1].getFullYear();
                return fmtDate(dates[0], !sy)+' – '+fmtDate(dates[1], true);
            }
            return dates.length === 1 ? fmtDate(dates[0], true)+' …' : '';
        };

        flatpickr(rangeInput, {
            mode: 'range', dateFormat: 'Y-m-d',
            locale: { firstDayOfWeek: 1 }, allowInput: false,
            defaultDate: [fromHidden.value, toHidden.value].filter(Boolean),
            onChange(selectedDates, _, fp) {
                fp.input.value = fmtRange(selectedDates);
                if (selectedDates.length === 1) {
                    fromHidden.value = flatpickr.formatDate(selectedDates[0], 'Y-m-d');
                    toHidden.value   = '';
                } else if (selectedDates.length === 2) {
                    fromHidden.value = flatpickr.formatDate(selectedDates[0], 'Y-m-d');
                    toHidden.value   = flatpickr.formatDate(selectedDates[1], 'Y-m-d');
                    form && form.submit();
                }
            },
            onReady(selectedDates, _, fp) {
                fp.input.classList.add('gf-date-input');
                if (selectedDates.length) fp.input.value = fmtRange(selectedDates);
            },
        });
    }
});
</script>
@endpush

@section('content')
<div class="page-wrap py-3">

    {{-- ── HEADER ──────────────────────────────────────────────────────────── --}}
    <div class="d-flex justify-content-between align-items-center gap-3 mb-3 flex-wrap">
        <div>
            <h2 class="mb-0 lh-1" style="font-size:1.35rem;">[JUDUL]</h2>
            <div class="text-muted small mt-1">[SUB]</div>
        </div>
        <div class="d-flex gap-2">
            {{-- Tombol aksi header — sesuaikan role check --}}
            @if ($user && $user->isOwner())
                <a href="{{ route('[ROUTE].create') }}" class="btn btn-primary btn-sm">
                    + [JUDUL SINGKAT]
                </a>
            @endif
        </div>
    </div>

    {{-- ── FLASH ───────────────────────────────────────────────────────────── --}}
    @if (session('success'))
        <div class="alert alert-success py-2 small">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
    @endif

    {{-- ── KPI STRIP ───────────────────────────────────────────────────────── --}}
    {{-- Hapus section ini jika tidak butuh ringkasan angka --}}
    @isset($summary)
    <div class="kpi-grid">
        <div class="kpi-cell">
            <div class="kpi-label">Total</div>
            <div class="kpi-value mono">{{ $summary->total ?? 0 }}</div>
            <div class="kpi-sub">{{ $summary->sub ?? '' }}</div>
        </div>
        <div class="kpi-cell">
            <div class="kpi-label">Draft</div>
            <div class="kpi-value mono">{{ $summary->draft ?? 0 }}</div>
            <div class="kpi-sub">Belum diproses</div>
        </div>
        <div class="kpi-cell">
            <div class="kpi-label">Aktif</div>
            <div class="kpi-value mono">{{ $summary->active ?? 0 }}</div>
            <div class="kpi-sub">Sedang berjalan</div>
        </div>
        <div class="kpi-cell">
            <div class="kpi-label">Nilai</div>
            <div class="kpi-value mono" style="font-size:.86rem;">{{ rupiah($summary->total_value ?? 0) }}</div>
            <div class="kpi-sub">&nbsp;</div>
        </div>
    </div>
    @endisset

    {{-- ── FILTER ──────────────────────────────────────────────────────────── --}}
    <div class="card-filter mb-3">
        <form id="idx-filter-form" method="GET" action="{{ route('[ROUTE].index') }}">
            {{-- Hidden inputs untuk flatpickr range --}}
            <input type="hidden" name="from" id="filter-from" value="{{ request('from') }}" data-gf-date="off">
            <input type="hidden" name="to"   id="filter-to"   value="{{ request('to') }}"   data-gf-date="off">

            <div class="filter-row">
                {{-- Search teks --}}
                <input type="text" id="filter-search" name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari…"
                    class="form-control form-control-sm"
                    style="max-width:220px;" autocomplete="off">

                {{-- Contoh select filter --}}
                <select name="status" class="form-select form-select-sm filter-auto" style="max-width:140px;">
                    <option value="">Semua Status</option>
                    <option value="draft"    @selected(request('status') === 'draft')>Draft</option>
                    <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                    <option value="posted"   @selected(request('status') === 'posted')>Posted</option>
                </select>

                {{-- Flatpickr range --}}
                <input type="text" id="filter-date-range"
                    value="{{ $rangeDisplay }}"
                    placeholder="Tanggal…"
                    autocomplete="off" readonly
                    class="form-control form-control-sm"
                    style="max-width:185px;cursor:pointer;"
                    data-gf-date="off">

                {{-- Reset — hanya tampil kalau ada filter aktif --}}
                @if (request()->hasAny(['q','status','from','to']))
                    <a href="{{ route('[ROUTE].index') }}"
                       class="btn btn-sm btn-outline-secondary"
                       style="font-size:.78rem;padding:.25rem .65rem;">
                        <i class="bi bi-x me-1"></i>Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- ── TABLE (DESKTOP) ─────────────────────────────────────────────────── --}}
    <div class="card-table d-none d-md-block">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        {{-- Kolom sortable: ganti 'col_name' dengan nama kolom DB --}}
                        <th style="width:20%;">
                            <a href="{{ $sortUrl('created_at') }}"
                               class="th-sort {{ $sortCol === 'created_at' ? 'active' : '' }}">
                                Kode {{ $sortIcon('created_at') }}
                            </a>
                        </th>
                        <th>
                            <a href="{{ $sortUrl('COL_A') }}"
                               class="th-sort {{ $sortCol === 'COL_A' ? 'active' : '' }}">
                                [COL_A] {{ $sortIcon('COL_A') }}
                            </a>
                        </th>
                        <th style="width:15%;" class="text-end">
                            <a href="{{ $sortUrl('COL_B') }}"
                               class="th-sort {{ $sortCol === 'COL_B' ? 'active' : '' }}">
                                [COL_B] {{ $sortIcon('COL_B') }}
                            </a>
                        </th>
                        <th style="width:20%;">
                            <a href="{{ $sortUrl('status') }}"
                               class="th-sort {{ $sortCol === 'status' ? 'active' : '' }}">
                                Status {{ $sortIcon('status') }}
                            </a>
                        </th>
                        <th style="width:4%;"></th>{{-- kolom aksi --}}
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                    <tr class="tbl-row">
                        {{-- Kolom Kode --}}
                        <td>
                            <div class="row-code">{{ $item->code }}</div>
                            <div class="row-sub">{{ id_date($item->date ?? $item->created_at) }}</div>
                        </td>
                        {{-- Kolom COL_A (contoh: nama/supplier) --}}
                        <td>
                            <div style="font-weight:600;font-size:.85rem;">{{ $item->col_a }}</div>
                            <div class="row-sub">{{ $item->col_a_sub ?? '' }}</div>
                        </td>
                        {{-- Kolom COL_B (contoh: nilai) --}}
                        <td class="text-end">
                            <div class="row-total mono">{{ rupiah($item->col_b ?? 0) }}</div>
                        </td>
                        {{-- Status --}}
                        <td>
                            @php
                                $badgeClass = match ($item->status ?? '') {
                                    'approved', 'posted' => 'badge-status badge-approved',
                                    'cancelled', 'voided' => 'badge-status badge-cancelled',
                                    'closed' => 'badge-status badge-closed',
                                    default => 'badge-status badge-draft',
                                };
                            @endphp
                            <span class="{{ $badgeClass }}">{{ ucfirst($item->status ?? 'draft') }}</span>
                        </td>
                        {{-- Aksi --}}
                        <td class="text-end" style="white-space:nowrap;">
                            <a href="{{ route('[ROUTE].show', $item->id) }}"
                               class="btn btn-sm btn-outline-secondary"
                               title="Detail">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Belum ada data.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($items->hasPages())
        <div class="px-3 py-2">
            {{ $items->links() }}
        </div>
        @endif
    </div>

    {{-- ── MOBILE CARD LIST ────────────────────────────────────────────────── --}}
    <div class="d-md-none">
        @forelse ($items as $item)
        <div class="card-mobile-item">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div style="min-width:0;">
                    <div class="item-title">{{ $item->code }}</div>
                    <div class="item-meta">
                        <span>{{ id_date($item->date ?? $item->created_at) }}</span>
                        <span>{{ $item->col_a ?? '' }}</span>
                    </div>
                </div>
                <div class="text-end flex-shrink-0">
                    <div class="item-amount mono">{{ rupiah($item->col_b ?? 0) }}</div>
                    @php
                        $badgeClass = match ($item->status ?? '') {
                            'approved', 'posted' => 'badge-status badge-approved',
                            'cancelled', 'voided' => 'badge-status badge-cancelled',
                            default => 'badge-status badge-draft',
                        };
                    @endphp
                    <div class="mt-1"><span class="{{ $badgeClass }}">{{ ucfirst($item->status ?? 'draft') }}</span></div>
                </div>
            </div>
            <div class="d-flex justify-content-end mt-2">
                <a href="{{ route('[ROUTE].show', $item->id) }}"
                   class="btn btn-sm btn-outline-secondary" style="font-size:.75rem;">
                    Detail <i class="bi bi-chevron-right"></i>
                </a>
            </div>
        </div>
        @empty
        <div class="text-center text-muted py-4 small">Belum ada data.</div>
        @endforelse

        {{-- Pagination mobile --}}
        @if ($items->hasPages())
        <div class="mt-2">{{ $items->links() }}</div>
        @endif
    </div>

</div>
@endsection

{{--
════════════════════════════════════════════════════════════════════════════════
  CONTROLLER SNIPPET — salin ke method index()
════════════════════════════════════════════════════════════════════════════════

    public function index(Request $request)
    {
        // Sorting
        $allowed = ['created_at', 'COL_A', 'COL_B', 'status'];
        $sortCol = in_array($request->sort, $allowed, true) ? $request->sort : 'created_at';
        $sortDir = $request->dir === 'asc' ? 'asc' : 'desc';

        $q = [Model]::query()->with([...]);

        // Filter: cari teks
        if ($request->filled('q')) {
            $q->where('code', 'like', '%'.$request->q.'%');
        }

        // Filter: status
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        // Filter: tanggal range
        if ($request->filled('from')) $q->whereDate('date', '>=', $request->from);
        if ($request->filled('to'))   $q->whereDate('date', '<=', $request->to);

        // Sorting (join untuk relasi, orderBy langsung untuk kolom sendiri)
        $q->orderBy($sortCol, $sortDir)->orderByDesc('id');

        $summary = (object) [
            'total'       => (clone $q)->count(),
            'draft'       => (clone $q)->where('status','draft')->count(),
            'active'      => (clone $q)->where('status','approved')->count(),
            'total_value' => (clone $q)->sum('grand_total'),
        ];

        $items = $q->paginate(20)->withQueryString();

        return view('...index', compact('items', 'summary', 'sortCol', 'sortDir'));
    }

════════════════════════════════════════════════════════════════════════════════
  BADGE HELPER REFERENCE
════════════════════════════════════════════════════════════════════════════════

  Status dokumen:
    badge-status badge-draft
    badge-status badge-approved
    badge-status badge-posted
    badge-status badge-cancelled
    badge-status badge-voided
    badge-status badge-closed
    badge-status badge-partial
    badge-status badge-danger

  Status bayar:
    badge-pay badge-pay-paid
    badge-pay badge-pay-partial
    badge-pay badge-pay-unpaid

  Status terima:
    badge-rcv badge-rcv-full
    badge-rcv badge-rcv-partial
    badge-rcv badge-rcv-none

════════════════════════════════════════════════════════════════════════════════
  CSS VARIABLES (dari layouts/app.blade.php)
════════════════════════════════════════════════════════════════════════════════

  var(--card)   → background kartu / panel
  var(--bg)     → background halaman
  var(--line)   → warna border / garis pemisah
  var(--muted)  → teks abu-abu / label kecil
  var(--body)   → teks utama

--}}
