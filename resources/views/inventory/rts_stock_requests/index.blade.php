@extends('layouts.app')

@section('title', 'RTS • Permintaan')

@push('head')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    .page-wrap {
        max-width: 960px;
        margin-inline: auto;
        padding: 1rem .9rem 5rem;
    }

    body[data-theme="light"] .page-wrap {
        background: radial-gradient(circle at top left,
            rgba(59,130,246,.08) 0, rgba(45,212,191,.10) 30%, #f9fafb 65%);
    }

    /* ── filter bar ── */
    .filter-bar {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        align-items: center;
        margin-bottom: .7rem;
    }

    .f-input {
        height: 34px;
        padding: 0 .65rem;
        border-radius: 9px;
        border: 1px solid rgba(148,163,184,.30);
        background: var(--card);
        color: inherit;
        font-size: .80rem;
        outline: none;
        transition: border-color .12s, box-shadow .12s;
    }
    .f-input:focus {
        border-color: rgba(45,212,191,.55);
        box-shadow: 0 0 0 2px rgba(45,212,191,.12);
    }

    #inp-search { width: 190px; }

    /* ── date section ── */
    .date-section {
        display: inline-flex;
        align-items: center;
        border: 1px solid rgba(148,163,184,.30);
        border-radius: 10px;
        overflow: hidden;
        background: var(--card);
        height: 34px;
    }

    .date-section .ds-presets {
        display: flex;
        align-items: center;
        border-right: 1px solid rgba(148,163,184,.20);
    }

    .ds-preset-btn {
        height: 34px;
        padding: 0 .55rem;
        font-size: .74rem;
        font-weight: 700;
        border: none;
        border-right: 1px solid rgba(148,163,184,.14);
        background: transparent;
        color: inherit;
        cursor: pointer;
        opacity: .60;
        white-space: nowrap;
        transition: opacity .10s, background .10s;
    }
    .ds-preset-btn:last-child { border-right: none; }
    .ds-preset-btn:hover { opacity: 1; background: rgba(45,212,191,.08); }
    .ds-preset-btn.active { opacity: 1; background: rgba(45,212,191,.14); color: rgba(45,212,191,1); }

    .ds-divider {
        width: 1px; height: 20px;
        background: rgba(148,163,184,.22);
        flex-shrink: 0;
    }

    #inp-date {
        height: 34px;
        min-width: 130px;
        padding: 0 .6rem;
        border: none;
        background: transparent;
        color: inherit;
        font-size: .78rem;
        outline: none;
        cursor: pointer;
    }

    .ds-clear {
        padding: 0 .5rem;
        height: 34px;
        border: none;
        background: transparent;
        color: inherit;
        cursor: pointer;
        opacity: .35;
        font-size: .78rem;
    }
    .ds-clear:hover { opacity: .8; color: #ef4444; }

    .f-select {
        height: 34px;
        padding: 0 .6rem;
        border-radius: 9px;
        border: 1px solid rgba(148,163,184,.30);
        background: var(--card);
        color: inherit;
        font-size: .78rem;
        outline: none;
        min-width: 130px;
    }
    .f-select:focus { border-color: rgba(45,212,191,.55); }

    .btn-reset {
        height: 34px;
        padding: 0 .65rem;
        border-radius: 9px;
        border: 1px solid rgba(148,163,184,.28);
        background: transparent;
        color: inherit;
        font-size: .76rem;
        cursor: pointer;
        opacity: .65;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
    }
    .btn-reset:hover { opacity: 1; border-color: rgba(239,68,68,.45); color: #ef4444; }

    /* table */
    .tbl-wrap {
        border: 1px solid rgba(148,163,184,.22);
        border-radius: 14px;
        overflow: auto;
        background: var(--card);
        max-height: calc(100vh - 220px);
    }

    table { width: 100%; border-collapse: collapse; }

    thead tr {
        background: rgba(148,163,184,.10);
        border-bottom: 1px solid rgba(148,163,184,.20);
    }

    th {
        position: sticky; top: 0; z-index: 2;
        background: rgba(148,163,184,.10);
        backdrop-filter: none;
        padding: .48rem .75rem;
        font-size: .67rem; font-weight: 900; opacity: .65;
        text-transform: uppercase; letter-spacing: .09em;
        text-align: left; white-space: nowrap;
        box-shadow: 0 1px 0 rgba(148,163,184,.20);
    }

    tbody tr {
        border-bottom: 1px solid rgba(148,163,184,.11);
        cursor: pointer;
        transition: background .10s;
    }
    tbody tr:last-child { border-bottom: 0; }
    tbody tr:hover { background: rgba(45,212,191,.05); }

    td { padding: .52rem .75rem; vertical-align: middle; font-size: .85rem; }

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
        display: inline-flex; padding: .11rem .40rem;
        border-radius: 999px; font-size: .68rem; font-weight: 800;
        border: 1px solid rgba(148,163,184,.28);
        background: rgba(148,163,184,.10); white-space: nowrap;
    }
    .badge.ok     { border-color: rgba(16,185,129,.35);  background: rgba(16,185,129,.11); }
    .badge.warn   { border-color: rgba(245,158,11,.38);  background: rgba(245,158,11,.10); }
    .badge.danger { border-color: rgba(239,68,68,.35);   background: rgba(239,68,68,.08);  }

    .empty-row td { text-align: center; padding: 2.5rem; opacity: .45; font-size: .86rem; }

    /* flatpickr override */
    .flatpickr-input { background: var(--card) !important; color: inherit !important; }
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

<div class="page-wrap">

    {{-- Header --}}
    <div style="display:flex;justify-content:space-between;align-items:center;gap:.6rem;flex-wrap:wrap;margin-bottom:.75rem">
        <div>
            <h1 style="font-size:1.1rem;font-weight:900;margin:0">Permintaan RTS</h1>
            <div style="font-size:.74rem;opacity:.52;margin-top:.06rem">
                {{ $stats['total'] ?? 0 }} total
                @if(($stats['submitted'] ?? 0) > 0)
                    · <span style="color:#f59e0b;font-weight:700">{{ $stats['submitted'] }} menunggu</span>
                @endif
            </div>
        </div>
        @if($canManage)
            <a href="{{ route('rts.stock-requests.create') }}" class="btn btn-primary btn-sm">+ Buat</a>
        @endif
    </div>

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('rts.stock-requests.index') }}" id="filterForm">
        <input type="hidden" name="date_from"  id="hid-from"   value="{{ $dateFromNow }}">
        <input type="hidden" name="date_to"    id="hid-to"     value="{{ $dateToNow }}">
        <input type="hidden" name="period"     id="hid-period" value="{{ $periodNow }}">

        <div class="filter-bar">

            {{-- Search --}}
            <input type="text" id="inp-search" name="search" class="f-input"
                value="{{ $searchNow }}" placeholder="Cari kode / item…" autocomplete="off">

            {{-- Date section: preset pills + range picker in one box --}}
            <div class="date-section">
                <div class="ds-presets">
                    <button type="button" class="ds-preset-btn {{ $periodNow==='today' ? 'active':'' }}"
                        data-period="today">Hari ini</button>
                    <button type="button" class="ds-preset-btn {{ $periodNow==='week' ? 'active':'' }}"
                        data-period="week">Minggu ini</button>
                    <button type="button" class="ds-preset-btn {{ $periodNow==='month' ? 'active':'' }}"
                        data-period="month">Bulan ini</button>
                </div>
                <div class="ds-divider"></div>
                <input type="text" id="inp-date" placeholder="Pilih tanggal…" readonly autocomplete="off">
                @if($dateFromNow || $dateToNow || $periodNow !== 'all')
                    <button type="button" class="ds-clear" id="btn-clear-date" title="Hapus filter tanggal">✕</button>
                @endif
            </div>

            {{-- Status --}}
            <select name="status" class="f-select" onchange="this.form.submit()">
                <option value="all"       {{ $statusNow==='all'       ? 'selected':'' }}>Semua status</option>
                <option value="submitted" {{ $statusNow==='submitted' ? 'selected':'' }}>Menunggu</option>
                <option value="partial"   {{ $statusNow==='partial'   ? 'selected':'' }}>Sebagian</option>
                <option value="completed" {{ $statusNow==='completed' ? 'selected':'' }}>Selesai</option>
            </select>

            {{-- Reset --}}
            @if($searchNow || $dateFromNow || $dateToNow || $statusNow !== 'all' || $periodNow !== 'all')
                <a href="{{ route('rts.stock-requests.index') }}" class="btn-reset">✕ Reset</a>
            @endif
        </div>
    </form>

    {{-- Table --}}
    <div class="tbl-wrap">
        <table>
            <thead>
                <tr>
                    <th class="col-date">Tanggal</th>
                    <th class="col-item">Item</th>
                    <th class="col-qty">Qty</th>
                    <th class="col-st">Status</th>
                </tr>
            </thead>
            <tbody>
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
                <tr onclick="window.location='{{ $url }}'">
                    <td class="col-date mono" style="opacity:.62;font-size:.76rem">
                        {{ optional($sr->date)->format('d M Y') }}
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
                <tr class="empty-row"><td colspan="4">Belum ada permintaan RTS.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:1rem">{{ $stockRequests->links() }}</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script>
const hidFrom   = document.getElementById('hid-from');
const hidTo     = document.getElementById('hid-to');
const hidPeriod = document.getElementById('hid-period');
const form      = document.getElementById('filterForm');

function submitDate({ from='', to='', period='all' } = {}) {
    hidFrom.value   = from;
    hidTo.value     = to;
    hidPeriod.value = period;
    form.submit();
}

// ── Flatpickr (range + single) ────────────────────────────
const fp = flatpickr('#inp-date', {
    mode: 'range',
    dateFormat: 'Y-m-d',
    locale: 'id',
    altInput: true,
    altFormat: 'j M Y',
    defaultDate: [hidFrom.value, hidTo.value].filter(Boolean),
    onChange(dates) {
        if (dates.length === 1) {
            // single date → same from & to
            const d = flatpickr.formatDate(dates[0], 'Y-m-d');
            submitDate({ from: d, to: d });
        }
        if (dates.length === 2) {
            submitDate({
                from: flatpickr.formatDate(dates[0], 'Y-m-d'),
                to:   flatpickr.formatDate(dates[1], 'Y-m-d'),
            });
        }
    },
});

// ── Preset pill buttons ───────────────────────────────────
document.querySelectorAll('.ds-preset-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        submitDate({ period: btn.dataset.period });
    });
});

// ── Clear date ────────────────────────────────────────────
document.getElementById('btn-clear-date')?.addEventListener('click', () => {
    submitDate({ period: 'all' });
});

// set display label for active preset
const periodNow = hidPeriod.value;
if (periodNow && periodNow !== 'all') {
    const labels = { today: 'Hari ini', week: 'Minggu ini', month: 'Bulan ini' };
    const alt = fp.altInput || document.querySelector('#inp-date + input');
    if (alt) alt.value = labels[periodNow] || '';
}

// clear date button
document.getElementById('btn-clear-date')?.addEventListener('click', () => {
    hidFrom.value = hidTo.value = hidPeriod.value = '';
    form.submit();
});

// search: submit on Enter
document.getElementById('inp-search')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); form.submit(); }
});
// debounce 500ms
let _st;
document.getElementById('inp-search')?.addEventListener('input', () => {
    clearTimeout(_st);
    _st = setTimeout(() => form.submit(), 500);
});

// ── Expand chips ──────────────────────────────────────────
function toggleMore(btn) {
    const id = btn.dataset.id;
    const extras = document.querySelectorAll(`[data-extra="${id}"]`);
    const isHidden = extras[0]?.style.display === 'none';
    extras.forEach(el => el.style.display = isHidden ? 'inline-flex' : 'none');
    btn.textContent = isHidden ? '−' : '+' + extras.length;
}
</script>
@endsection
