@extends('layouts.app')

@section('title', 'Daftar Purchase Order')

@php
    $user = auth()->user();
    $canSeeMoney = $user?->canSeePurchasePrices() ?? false;
    $sortCol ??= 'date';
    $sortDir ??= 'desc';
    $sortUrl = fn(string $col) => request()->fullUrlWithQuery([
        'sort' => $col,
        'dir'  => ($sortCol === $col && $sortDir === 'asc') ? 'desc' : 'asc',
        'page' => 1,
    ]);
    $sortIcon = fn(string $col) => $sortCol === $col
        ? ($sortDir === 'asc' ? '↑' : '↓')
        : '↕';
@endphp

@push('head')
    <style>

    :root {
        --shp-accent: #334155;
        --shp-accent-2: #1f2937;
        --shp-border: rgba(148,163,184,.18);
        --shp-muted: #64748b;
    }
    .page-wrap { max-width: 1040px; margin-inline: auto; padding: .75rem .75rem 4rem; background: transparent !important; }

    .card-main {
        background: var(--card, #fff);
        border-radius: 8px;
        border: 1px solid var(--shp-border);
        box-shadow: none;
        overflow: hidden;
    }

    .ship-topbar {
        display: flex; justify-content: space-between; align-items: center; gap: .6rem; flex-wrap: wrap;
        padding: .45rem .75rem; margin-inline: -.75rem; margin-bottom: .65rem;
        background: var(--card, #fff); border-bottom: 1px solid var(--shp-border);
    }
    .title { font-weight: 750; font-size: 1rem; margin: 0; color: #0f172a; }
    .sub { color: var(--shp-muted); font-size: .78rem; }

    .kpis { display: flex; flex-wrap: wrap; gap: .32rem; margin-top: .35rem; }
    .kpi { display: inline-flex; align-items: baseline; gap: .45rem; border-radius: 7px; padding: .2rem .48rem; border: 1px solid rgba(148,163,184,.28); font-size: .72rem; }
    .kpi .lbl { color: #94a3b8; font-size: .66rem; }
    .kpi .val { font-weight: 650; color: var(--shp-accent); }

    .controls { display: flex; gap: .75rem; align-items: center; flex-wrap: wrap; }
    .filter-label { font-size: .8rem; color: #6b7280; }
    


    .filter-select {
        border-radius: 6px;
        font-size: .8rem;
        background-color: #fff;
        border: 1px solid rgba(148,163,184,.3);
        transition: all 0.2s;
        box-shadow: 0 1px 2px rgba(0,0,0,.02) !important;
        color: #334155;
        height: 36px !important;
        padding: 0.35rem 0.75rem;
        line-height: 1.5;
    }
    .filter-select:hover, .filter-select:focus {
        background-color: #fff;
        border-color: rgba(148,163,184,.4);
    }

    .btn-pill { border-radius: 7px; padding-inline: .78rem; box-shadow: none !important; font-weight: 600; text-decoration: none; }
    .btn-ship-primary { background: var(--shp-accent) !important; border-color: var(--shp-accent) !important; color: #fff !important; }
    .btn-ship-primary:hover { background: var(--shp-accent-2) !important; border-color: var(--shp-accent-2) !important; color: #fff !important; }
    .btn-ship-outline { color: #475569 !important; border: 1px solid rgba(148,163,184,.35) !important; }
    .btn-ship-outline:hover { background: rgba(148,163,184,.08) !important; color: #111827 !important; }

    @media (min-width: 769px) {
        .filter-select { width: auto; min-width: 130px; }
        #po-date-range { width: 190px !important; }
        .search-input { width: 160px; }
    }

    .table-responsive {
        overflow: visible;
    }
    .table-responsive::-webkit-scrollbar { width: 6px; height: 6px; }
    .table-responsive::-webkit-scrollbar-track { background: transparent; }
    .table-responsive::-webkit-scrollbar-thumb { background: rgba(148,163,184,.3); border-radius: 4px; }
    .table-responsive::-webkit-scrollbar-thumb:hover { background: rgba(148,163,184,.5); }

    .table-list { margin-bottom: 0; }

    .table-list thead th { position: sticky; top: 0; z-index: 10; border-bottom-width: 1px; font-size: .68rem; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b; background: var(--card, #fff); padding: .75rem 1rem; white-space: nowrap; }
    .table-list thead th::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        width: 100%;
        border-bottom: 1px solid rgba(148,163,184,.25);
    }

    .table-list tbody td { vertical-align: middle; border-top-color: rgba(148,163,184,.12); padding: .65rem 1rem; font-size: .83rem; }

    .badge-status { border-radius: 7px; padding: .16rem .48rem; font-size: .68rem; border: 1px solid transparent; display: inline-flex; align-items: center; gap: .35rem; white-space: nowrap; }
    .badge-status::before { content: ''; width: 7px; height: 7px; border-radius: 999px; display: inline-block; }
    
    .st-draft { background: rgba(148,163,184,.10); color: #475569; border-color: rgba(148,163,184,.30); }
    .st-draft::before { background: rgba(100,116,139,.95); }
    .st-approved { background: rgba(59,130,246,.10); color: #1d4ed8; border-color: rgba(59,130,246,.30); }
    .st-approved::before { background: rgba(59,130,246,.95); }
    .st-cancelled { background: rgba(239,68,68,.10); color: #991b1b; border-color: rgba(239,68,68,.30); }
    .st-cancelled::before { background: rgba(239,68,68,.95); }

    .code-link { font-weight: 600; text-decoration: none; color: inherit; }
    .code-link:hover { text-decoration: underline; }
    .muted { font-size: .82rem; color: #6b7280; }
    .supplier-name { font-weight: 600; }

    .badge-pay { border-radius: 7px; font-size: .7rem; padding: .1rem .55rem; border: 1px solid rgba(148,163,184,.45); background: rgba(148,163,184,.10); color: #64748b; white-space: nowrap; }
    .badge-pay-paid { border-color: rgba(22,163,74,.55); background: rgba(22,163,74,.12); color: #15803d; }
    .badge-pay-partial { border-color: rgba(234,179,8,.55); background: rgba(234,179,8,.12); color: #a16207; }

    .badge-rcv { border-radius: 7px; font-size: .65rem; padding: .05rem .45rem; border: 1px solid transparent; white-space: nowrap; display: inline-block; }
    .badge-rcv-none { background: rgba(148,163,184,.08); color: #94a3b8; border-color: rgba(148,163,184,.4); }
    .badge-rcv-partial { background: #fef08a; color: #854d0e; border-color: #fde047; }
    .badge-rcv-full { background: #16a34a; color: #fff; border-color: #15803d; box-shadow: 0 1px 2px rgba(22,163,74,.3); }

    .mono { font-variant-numeric: tabular-nums; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono"; }

    .empty { padding: 2.2rem 1.25rem; text-align: center; color: #64748b; }
    .divider { height: 1px; background: rgba(148,163,184,.20); }

    .th-sort {
        cursor: pointer;
        user-select: none;
        text-decoration: none;
        color: #64748b;
        display: inline-flex;
        align-items: center;
        gap: .25rem;
    }
    .th-sort:hover { color: #0f172a; }
    .th-sort.active { color: #0f172a; font-weight: 700; }

    @media (max-width: 768px) {
        .page-wrap { padding: .5rem .5rem 4rem; }
        .ship-topbar { margin-inline: -.5rem; padding: .5rem .65rem; }
        .title { font-size: 1.05rem; }
        .sub { display: none; }
        
        .filter-bar { padding: .65rem; }
        form#po-filter-form { display: flex; flex-wrap: wrap; gap: .75rem; width: 100%; }
        .filter-select { flex: 1 1 calc(50% - .375rem); min-width: 120px; }
        #po-date-range { flex: 1 1 100%; width: 100% !important; }
        .position-relative { flex: 1 1 100%; width: 100%; }
        .btn { flex: 1 1 100%; }
        .controls { width: 100%; align-items: stretch; }

        .kpis { display: none; }
        .table-responsive { overflow: visible; max-height: none; }
        .table-list thead { display: none; }
        .table-list, .table-list tbody, .table-list tr, .table-list td { display: block; width: 100%; }
        .table-list tbody tr { padding: .66rem; border-top: 1px solid rgba(148,163,184,.16); }
        .table-list tbody td { vertical-align: middle; border-top-color: rgba(148,163,184,.12); padding: .65rem 1rem; font-size: .83rem; border: 0; padding: 0;}
        .mobile-hide { display: none !important; }
        .ship-row-main { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; }
        .ship-row-meta { display: flex; align-items: center; gap: .45rem; flex-wrap: wrap; margin-top: .35rem; color: #64748b; font-size: .78rem; }
        .ship-row-action { margin-top: .55rem; }
        .ship-row-action .btn { width: 100%; min-height: 38px; }
    }

</style>
@endpush

@section('content')
    @php
        $user = auth()->user();

        $statusOptions = [
            '' => 'Semua Status',
            'draft' => 'Draft',
            'approved' => 'Approved',
            'cancelled' => 'Cancelled',
        ];

        $payStatusOptions = [
            '' => 'Semua Bayar',
            'unpaid' => 'Unpaid',
            'partial' => 'Partial',
            'paid' => 'Paid',
        ];

        $payBadge = function ($s) {
            return match ((string) $s) {
                'paid' => 'badge-pay badge-pay-paid',
                'partial' => 'badge-pay badge-pay-partial',
                default => 'badge-pay badge-pay-unpaid',
            };
        };
    @endphp

    <div class="page-wrap">
    @if (session('success'))
        <div class="flash-clean alert alert-success py-2 small mb-2" style="border-radius:8px; border:1px solid rgba(148,163,184,.25);">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="flash-clean alert alert-danger py-2 small mb-2" style="border-radius:8px; border:1px solid rgba(148,163,184,.25);">{{ session('error') }}</div>
    @endif

    <div class="ship-topbar">
        <div>
            <div class="title">Purchase Orders</div>
            <div class="sub">Daftar pemesanan barang.</div>

            @if (isset($summary))
                <div class="kpis">
                    <span class="kpi"><span class="lbl">Total PO</span><span class="val mono">{{ $summary->total_orders }}</span></span>
                    <span class="kpi"><span class="lbl">Draft</span><span class="val mono">{{ $summary->draft_count }}</span></span>
                    <span class="kpi"><span class="lbl">Approved</span><span class="val mono">{{ $summary->approved_count }}</span></span>
                    @if ($canSeeMoney)
                        <span class="kpi"><span class="lbl">Nilai</span><span class="val mono">Rp {{ number_format($summary->total_grand_total, 0, ',', '.') }}</span></span>
                    @endif
                </div>
            @endif
        </div>

        <div class="controls">
            

            @if ($user && in_array($user->role, ['owner', 'admin']))
                <a href="{{ route('purchasing.purchase_orders.create') }}" class="btn btn-sm btn-ship-primary btn-pill">
                    PO Baru
                </a>
            @endif
        </div>
    </div>

    <div class="mb-4">
        <form id="po-filter-form" method="GET" class="d-flex align-items-center flex-wrap" style="width:100%; gap: 1rem;">
                <input type="hidden" name="from_date" id="po-from-date" value="{{ request('from_date') }}">
                <input type="hidden" name="to_date"   id="po-to-date"   value="{{ request('to_date') }}">

                <!-- Search Input -->
                <div class="position-relative">
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm filter-select search-input" placeholder="Cari PO..." autocomplete="off" onchange="this.form.submit()">
                    <i class="bi bi-search position-absolute text-muted" style="right: 8px; top: 50%; transform: translateY(-50%); font-size: .75rem;"></i>
                </div>

                <select name="supplier_id" class="form-select form-select-sm filter-select po-filter-auto">
                    <option value="">Semua Supplier</option>
                    @foreach ($suppliers as $sup)
                        <option value="{{ $sup->id }}" @selected(request('supplier_id') == $sup->id)>{{ $sup->name }}</option>
                    @endforeach
                </select>

                <select name="status" class="form-select form-select-sm filter-select po-filter-auto">
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                @if ($canSeeMoney)
                    <select name="pay_status" class="form-select form-select-sm filter-select po-filter-auto">
                        @foreach ($payStatusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(request('pay_status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                @endif

                @php
                    $idMonths = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
                    $rangeDisplay = '';
                    if (request('from_date') && request('to_date')) {
                        try {
                            $f = \Carbon\Carbon::parse(request('from_date'));
                            $t = \Carbon\Carbon::parse(request('to_date'));
                            $rangeDisplay = $f->day . ' ' . $idMonths[$f->month-1]
                                . ' – ' . $t->day . ' ' . $idMonths[$t->month-1] . ' ' . $t->year;
                        } catch (\Exception $e) {
                            $rangeDisplay = request('from_date') . ' – ' . request('to_date');
                        }
                    } elseif (request('from_date')) {
                        try {
                            $f = \Carbon\Carbon::parse(request('from_date'));
                            $rangeDisplay = $f->day . ' ' . $idMonths[$f->month-1] . ' ' . $f->year;
                        } catch (\Exception $e) {
                            $rangeDisplay = request('from_date');
                        }
                    }
                @endphp
                <input type="text" id="po-date-range" value="{{ $rangeDisplay }}"
                    placeholder="Tanggal" autocomplete="off"
                    class="form-control form-control-sm filter-select" style="cursor:pointer; width:180px;"
                    data-gf-date="off" readonly />

                @if (request()->filled('q') || request()->filled('supplier_id') || request()->filled('status') || request()->filled('pay_status') || request()->filled('from_date') || request()->filled('to_date'))
                    <a href="{{ route('purchasing.purchase_orders.index') }}"
                       class="btn btn-sm btn-ship-outline btn-pill">
                        Reset
                    </a>
                @endif
            </form>
    </div>

    <div class="card card-main">
        <div class="card-body p-0">
            @if ($orders->count() === 0)
                <div class="empty">Belum ada Purchase Order.</div>
            @else
            <div class="table-responsive">
                <table class="table table-hover align-middle table-list mb-0">
                    <thead>
                    <tr>
                        <th style="width: 46px;" class="mobile-hide">#</th>
                        <th style="width: 130px;">
                            <a href="{{ $sortUrl('date') }}" class="th-sort {{ $sortCol === 'date' ? 'active' : '' }}">
                                Tanggal {{ $sortIcon('date') }}
                            </a>
                        </th>
                        <th style="width: 160px;">PO</th>
                        <th>
                            <a href="{{ $sortUrl('supplier_id') }}" class="th-sort {{ $sortCol === 'supplier_id' ? 'active' : '' }}">
                                Supplier {{ $sortIcon('supplier_id') }}
                            </a>
                        </th>
                        @if ($canSeeMoney)
                            <th class="text-end" style="width: 140px;">
                                <a href="{{ $sortUrl('grand_total') }}" class="th-sort {{ $sortCol === 'grand_total' ? 'active' : '' }}">
                                    Total Rp {{ $sortIcon('grand_total') }}
                                </a>
                            </th>
                        @endif
                        <th style="width: 140px;">Status</th>
                        <th style="width: 90px;" class="mobile-hide"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        @php
                            $grnCount = $order->purchaseReceipts?->count() ?? 0;
                            $ps = (string) ($order->payment_status ?? 'unpaid');
                            $payBadgeClass = $payBadge($ps);
                            $rcv = $order->received_status ?? 'not_received';
                            $rcvClass = match($rcv) {
                                'fully_received' => 'badge-rcv badge-rcv-full',
                                'partial'        => 'badge-rcv badge-rcv-partial',
                                default          => 'badge-rcv badge-rcv-none',
                            };
                            
                            $uiStatus = $order->status;
                            $statusClass = match ($uiStatus) {
                                'approved' => 'st-approved',
                                'cancelled' => 'st-cancelled',
                                default => 'st-draft',
                            };
                            $statusLabel = match ((string) $uiStatus) {
                                'approved' => 'Approved',
                                'cancelled' => 'Cancelled',
                                default => 'Draft',
                            };
                            $rcvLabel = match($rcv) {
                                'fully_received' => 'Masuk Gudang',
                                'partial' => 'Masuk Sebagian',
                                default => 'Belum Masuk',
                            };
                            $payLabel = match($ps) {
                                'paid' => 'Lunas',
                                'partial' => 'Bayar sebagian',
                                default => 'Belum bayar',
                            };

                            $actionRoute = $uiStatus === 'draft'
                                ? route('purchasing.purchase_orders.edit', $order->id)
                                : route('purchasing.purchase_orders.show', $order->id);
                            $actionLabel = $uiStatus === 'draft' ? 'Lanjutkan' : 'Detail';
                        @endphp

                        <tr>
                            <td class="text-muted small mobile-hide">
                                {{ ($orders->currentPage() - 1) * $orders->perPage() + $loop->iteration }}
                            </td>

                            <td class="small mobile-hide mono">{{ id_date($order->date) }}</td>

                            <td>
                                <div class="ship-row-main">
                                    <div>
                                        <a class="code-link mono" style="font-size:.84rem;" href="{{ $actionRoute }}">
                                            {{ $order->code }}
                                        </a>

                                        <div class="muted mt-1" style="font-size: .78rem;">
                                            @php
                                                $subParts = [];
                                                if ($canSeeMoney) $subParts[] = $payLabel;
                                                if ($grnCount > 0) $subParts[] = 'GRN ' . $grnCount;
                                                if (!empty($order->purchase_request_id)) $subParts[] = 'PR';
                                                if (!empty($order->due_date) && $canSeeMoney) $subParts[] = 'JT: ' . id_date($order->due_date);
                                            @endphp
                                            {{ implode(' · ', $subParts) }}
                                        </div>
                                    </div>
                                    <div class="d-flex flex-column align-items-end d-md-none gap-1">
                                        <span class="badge-status {{ $statusClass }}">{{ $statusLabel }}</span>
                                        @if ($rcv === 'fully_received')
                                            <span class="badge-rcv badge-rcv-full fw-semibold" style="font-size: .62rem; padding: .15rem .45rem;"><i class="bi bi-check2-all me-1"></i>Masuk Gudang</span>
                                        @elseif ($rcv === 'partial')
                                            <span class="badge-rcv badge-rcv-partial fw-semibold" style="font-size: .62rem; padding: .15rem .45rem;"><i class="bi bi-box-seam me-1"></i>Masuk Sebagian</span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td>
                                <div class="supplier-name">{{ optional($order->supplier)->name ?? '—' }}</div>
                                <div class="muted">{{ optional($order->supplier)->code ?? '' }} · {{ po_order_type_label($order->order_type) }}</div>
                                <div class="ship-row-meta d-md-none">
                                    <span class="mono">{{ id_date($order->date) }}</span>
                                </div>
                            </td>

                            @if($canSeeMoney)
                                <td class="text-end mobile-hide">
                                    <span class="fw-semibold mono">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</span>
                                </td>
                            @endif

                            <td class="mobile-hide">
                                <div><span class="badge-status {{ $statusClass }}">{{ $statusLabel }}</span></div>
                                @if ($rcv === 'fully_received')
                                    <div class="mt-1"><span class="badge-rcv badge-rcv-full fw-semibold" style="font-size: .62rem; padding: .15rem .45rem;"><i class="bi bi-check2-all me-1"></i>Masuk Gudang</span></div>
                                @elseif ($rcv === 'partial')
                                    <div class="mt-1"><span class="badge-rcv badge-rcv-partial fw-semibold" style="font-size: .62rem; padding: .15rem .45rem;"><i class="bi bi-box-seam me-1"></i>Masuk Sebagian</span></div>
                                @endif
                            </td>

                            <td class="text-end ship-row-action mobile-hide">
                                <a href="{{ $actionRoute }}" class="btn btn-sm btn-ship-outline btn-pill">
                                    {{ $actionLabel }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="divider"></div>
        <div class="p-3">
            {{ $orders->links() }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // Auto-focus ke input cari saat halaman dibuka (kursor di akhir teks)
    const searchInput = document.querySelector('input[name="q"].search-input')
        || document.querySelector('input[name="q"]');
    if (searchInput) {
        setTimeout(function () {
            searchInput.focus();
            const len = searchInput.value.length;
            try { searchInput.setSelectionRange(len, len); } catch (e) {}
        }, 100);
    }

    // Row click via data-href (safer than inline onclick)
    document.querySelectorAll('tr.po-row').forEach(function (row) {
        row.addEventListener('click', function (e) {
            if (e.target.closest('a, button, form')) return;
            const href = row.dataset.href;
            if (href) window.location = href;
        });
    });

    const form = document.getElementById('po-filter-form');
    if (!form) return;

    // Realtime: selects auto-submit
    form.querySelectorAll('select.po-filter-auto').forEach(function (el) {
        el.addEventListener('change', function () { form.submit(); });
    });

    // Supplier dropdown auto-submit is already handled by po-filter-auto class

    // Single flatpickr range input
    const rangeInput = document.getElementById('po-date-range');
    const fromHidden = document.getElementById('po-from-date');
    const toHidden   = document.getElementById('po-to-date');

    if (rangeInput && window.flatpickr) {
        const ID_MONTHS = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        function fmtDate(d, withYear) {
            return d.getDate() + ' ' + ID_MONTHS[d.getMonth()] + (withYear ? ' ' + d.getFullYear() : '');
        }
        function fmtRange(dates) {
            if (dates.length === 2) {
                const sameYear = dates[0].getFullYear() === dates[1].getFullYear();
                return fmtDate(dates[0], !sameYear) + ' – ' + fmtDate(dates[1], true);
            }
            if (dates.length === 1) return fmtDate(dates[0], true) + ' …';
            return '';
        }

        flatpickr(rangeInput, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            locale: { firstDayOfWeek: 1 },
            allowInput: false,
            defaultDate: [fromHidden.value, toHidden.value].filter(Boolean),
            onChange: function (selectedDates, dateStr, fp) {
                fp.input.value = fmtRange(selectedDates);
                if (selectedDates.length === 1) {
                    fromHidden.value = flatpickr.formatDate(selectedDates[0], 'Y-m-d');
                    toHidden.value   = '';
                } else if (selectedDates.length === 2) {
                    fromHidden.value = flatpickr.formatDate(selectedDates[0], 'Y-m-d');
                    toHidden.value   = flatpickr.formatDate(selectedDates[1], 'Y-m-d');
                    form.submit();
                }
            },
            onReady: function (selectedDates, dateStr, fp) {
                fp.input.classList.add('gf-date-input');
                if (selectedDates.length) fp.input.value = fmtRange(selectedDates);
            },
        });
    }
});
</script>
@endpush
@endsection
