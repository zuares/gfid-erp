{{-- resources/views/purchasing/purchase_receipts/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Goods Receipts • Terima Barang')

@push('head')
<style>
    :root{
        --shp-accent:#334155;
        --shp-accent-2:#1f2937;
        --shp-border:rgba(148,163,184,.18);
        --shp-border-strong:rgba(148,163,184,.30);
        --shp-muted:#64748b;
    }
    .page-wrap{ max-width:1040px; margin-inline:auto; padding:.75rem .75rem 4rem; background:transparent!important; }
    .mono{ font-variant-numeric:tabular-nums; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,"Liberation Mono"; }

    .card-main{ background:var(--card); border-radius:8px; border:1px solid var(--shp-border); overflow:hidden; }
    body[data-theme="dark"] .card-main{ border-color:rgba(51,65,85,.85); }

    .ship-topbar{
        display:flex; justify-content:space-between; align-items:flex-start;
        gap:.6rem; flex-wrap:wrap;
        padding:.55rem .75rem; margin-inline:-.75rem; margin-bottom:.65rem;
        background:var(--card,#fff); border-bottom:1px solid var(--shp-border);
    }
    body[data-theme="dark"] .ship-topbar{ background:var(--card,#0f172a); }
    .title{ font-weight:750; font-size:1rem; margin:0; }
    .sub{ color:var(--shp-muted); font-size:.78rem; }
    body[data-theme="dark"] .sub{ color:#9ca3af; }

    .kpi-banner { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: .65rem; margin-bottom: .85rem; }
    .kpi-card {
        background: var(--card, #fff); border: 1px solid rgba(148,163,184,.22);
        border-radius: 8px; padding: .85rem 1rem; display: flex; flex-direction: column; justify-content: center;
    }
    body[data-theme="dark"] .kpi-card { background: rgba(15,23,42,.98); }
    .kpi-card .k-lbl { font-size: .68rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: .25rem; }
    .kpi-card .k-val { font-size: 1.1rem; font-weight: 700; color: var(--shp-accent); }
    body[data-theme="dark"] .kpi-card .k-val { color: #e2e8f0; }

    .kpi-card.kpi-blue { border-color: rgba(59, 130, 246, 0.3); background: rgba(59, 130, 246, 0.03); }
    .kpi-card.kpi-blue .k-lbl { color: #3b82f6; }
    .kpi-card.kpi-blue .k-val { color: #2563eb; }
    body[data-theme="dark"] .kpi-card.kpi-blue .k-val { color: #60a5fa; }

    .kpi-card.kpi-green { border-color: rgba(34, 197, 94, 0.3); background: rgba(34, 197, 94, 0.03); }
    .kpi-card.kpi-green .k-lbl { color: #22c55e; }
    .kpi-card.kpi-green .k-val { color: #16a34a; }
    body[data-theme="dark"] .kpi-card.kpi-green .k-val { color: #4ade80; }

    .kpi-card.kpi-red { border-color: rgba(239, 68, 68, 0.3); background: rgba(239, 68, 68, 0.03); }
    .kpi-card.kpi-red .k-lbl { color: #ef4444; }
    .kpi-card.kpi-red .k-val { color: #dc2626; }
    body[data-theme="dark"] .kpi-card.kpi-red .k-val { color: #f87171; }

    .btn-pill{ border-radius:7px; padding-inline:.85rem; box-shadow:none!important; font-weight:600; }
    .btn-ship-primary{ background:var(--shp-accent)!important; border-color:var(--shp-accent)!important; color:#fff!important; }
    .btn-ship-primary:hover{ background:var(--shp-accent-2)!important; border-color:var(--shp-accent-2)!important; color:#fff!important; }
    .btn-ship-outline{ color:#475569!important; background:transparent!important; border:1px solid rgba(148,163,184,.35)!important; }
    .btn-ship-outline:hover{ background:rgba(148,163,184,.08)!important; color:#111827!important; }

    /* Filter bar */
    .filter-bar{
        background:var(--card, #fff); border:1px solid rgba(148,163,184,.15);
        border-radius:10px; padding:.75rem .85rem; margin-bottom:1rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.02);
    }
    body[data-theme="dark"] .filter-bar{ background:rgba(15,23,42,.98); border-color:rgba(51,65,85,.6); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .filter-bar .form-control, .filter-bar .form-select{ border-radius:8px; font-size:.84rem; border-color: rgba(148,163,184,.3); }
    .filter-bar .form-control:focus, .filter-bar .form-select:focus { box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2); border-color: #3b82f6; }
    .filter-summary{ font-size:.74rem; color:var(--shp-muted); }
    .filter-summary strong{ color:var(--shp-accent); }
    body[data-theme="dark"] .filter-summary strong{ color:#cbd5e1; }

    /* Table (selaras shipment) */
    .table-responsive {
        overflow-x: auto;
        overflow-y: auto;
        max-height: calc(100vh - 210px);
    }
    .table-responsive::-webkit-scrollbar { width: 6px; height: 6px; }
    .table-responsive::-webkit-scrollbar-track { background: transparent; }
    .table-responsive::-webkit-scrollbar-thumb { background: rgba(148,163,184,.3); border-radius: 4px; }
    .table-responsive::-webkit-scrollbar-thumb:hover { background: rgba(148,163,184,.5); }

    .table-list{ margin-bottom:0; }
    .table-list thead th{
        border-bottom-width:1px; font-size:.68rem; text-transform:none; letter-spacing:0;
        color:#64748b; background:var(--card,#fff); padding:.52rem .62rem; white-space:nowrap;
    }
    body[data-theme="dark"] .table-list thead th{ background:rgba(15,23,42,.98); color:#9ca3af; }
    .table-list tbody td{ vertical-align:middle; border-top-color:rgba(148,163,184,.16); padding:.52rem .62rem; }
    body[data-theme="dark"] .table-list tbody td{ border-top-color:rgba(51,65,85,.85); }

    .grn-row{ cursor:pointer; }
    .grn-row:hover{ background:rgba(51,65,85,.035); }
    .code-link{ font-weight:700; text-decoration:none; color:inherit; font-size:.9rem; }
    .code-link:hover{ text-decoration:underline; }
    .muted{ font-size:.78rem; color:#6b7280; }
    body[data-theme="dark"] .muted{ color:#9ca3af; }
    .store-name{ font-weight:600; font-size:.86rem; }
    .ret-flag{ color:#b45309; font-weight:600; }

    /* Dot status badges */
    .badge-status{
        border-radius:7px; padding:.16rem .48rem; font-size:.68rem; white-space:nowrap;
        border:1px solid transparent; display:inline-flex; align-items:center; gap:.35rem;
    }
    .badge-status::before{ content:''; width:7px; height:7px; border-radius:999px; display:inline-block; }
    .st-draft{ background:rgba(148,163,184,.10); color:#475569; border-color:rgba(148,163,184,.30); }
    .st-draft::before{ background:rgba(100,116,139,.95); }
    .st-posted{ background:rgba(34,197,94,.10); color:#166534; border-color:rgba(34,197,94,.30); }
    .st-posted::before{ background:rgba(34,197,94,.95); }
    .st-closed{ background:rgba(15,23,42,.08); color:#334155; border-color:rgba(15,23,42,.22); }
    .st-closed::before{ background:rgba(51,65,85,.95); }
    body[data-theme="dark"] .st-posted{ background:rgba(34,197,94,.20); color:#dcfce7; border-color:rgba(34,197,94,.55); }
    body[data-theme="dark"] .st-closed{ color:#cbd5e1; border-color:rgba(203,213,225,.3); }

    .empty{ padding:2.2rem 1.25rem; text-align:center; color:#64748b; }
    body[data-theme="dark"] .empty{ color:#9ca3af; }

    @media (max-width:768px){
        .page-wrap{ padding:.5rem .5rem 4rem; }
        .ship-topbar{ margin-inline:-.5rem; padding:.5rem .65rem; }
        .title{ font-size:1.05rem; }
        .sub{ display:none; }
        .kpis{ gap:.3rem; }
        .table-list thead{ display:none; }
        .table-list, .table-list tbody, .table-list tr, .table-list td{ display:block; width:100%; }
        .table-list tbody tr{ padding:.7rem .8rem; border-top:1px solid rgba(148,163,184,.16); }
        .table-list tbody td{ border:0; padding:0; }
        .table-list tbody td.mobile-hide{ display:none; }
        .ship-row-main{ display:flex; align-items:flex-start; justify-content:space-between; gap:.75rem; }
        .ship-row-meta{ display:flex; align-items:center; gap:.45rem; flex-wrap:wrap; margin-top:.4rem; color:#64748b; font-size:.78rem; }
        .ship-row-meta span+span::before{ content:'•'; margin-right:.45rem; opacity:.6; }
        .ship-row-action{ margin-top:.6rem; }
        .ship-row-action .btn{ width:100%; min-height:40px; display:flex; align-items:center; justify-content:center; }
        .code-link{ font-size:.95rem; }
        .store-name{ font-size:.9rem; margin-top:.5rem; }
    }
</style>
@endpush

@section('content')
@php
    $user = auth()->user();
    $startIndex = method_exists($receipts, 'firstItem') ? ($receipts->firstItem() ?? 1) : 1;
@endphp

<div class="page-wrap">

    {{-- TOPBAR --}}
    <div class="ship-topbar">
        <div>
            <div class="title">Goods Receipt</div>
            <div class="sub">Penerimaan barang dari supplier ke gudang.</div>
            @if (isset($summary))
                <!-- Summary is displayed in banner below -->
            @endif
        </div>

        <a href="{{ route('purchasing.purchase_receipts.create') }}" class="btn btn-sm btn-ship-primary btn-pill">
            <i class="bi bi-plus-lg me-1"></i> GRN Baru
        </a>
    </div>

    @if (isset($summary))
        <div class="kpi-banner">
            <div class="kpi-card">
                <span class="k-lbl">Total Penerimaan</span>
                <span class="k-val mono">{{ rtrim(rtrim(number_format($summary->total_qty_sum ?? 0, 2, ',', '.'), '0'), ',') }}</span>
            </div>
            <div class="kpi-card kpi-red">
                <span class="k-lbl">Total Reject</span>
                <span class="k-val mono">{{ rtrim(rtrim(number_format($summary->total_reject_sum ?? 0, 2, ',', '.'), '0'), ',') }}</span>
            </div>
            <div class="kpi-card kpi-green">
                <span class="k-lbl">Total Nilai</span>
                <span class="k-val mono">Rp {{ number_format($summary->grand_total_sum ?? 0, 0, ',', '.') }}</span>
            </div>
            <div class="kpi-card kpi-blue">
                <span class="k-lbl">Dokumen (Total / Draft / Posted)</span>
                <span class="k-val mono" style="font-size:1rem; font-weight:600;">
                    {{ $summary->total_receipts ?? 0 }} <span style="color:#94a3b8; font-weight:400;">/</span>
                    {{ $summary->draft_count ?? 0 }} <span style="color:#94a3b8; font-weight:400;">/</span>
                    {{ $summary->posted_count ?? 0 }}
                </span>
            </div>
        </div>
    @endif

    {{-- FLASH --}}
    @if (session('success'))
        <div class="alert alert-success py-2 small">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
    @endif

    {{-- FILTER --}}
    <div class="filter-bar">
        <form id="grn-filter-form" method="GET" action="{{ route('purchasing.purchase_receipts.index') }}">
            <input type="hidden" name="from_date" id="grn-from-date" value="{{ request('from_date') }}">
            <input type="hidden" name="to_date"   id="grn-to-date"   value="{{ request('to_date') }}">

            <div class="d-flex flex-wrap gap-2 align-items-center">
                <input type="text" name="q" id="grn-search"
                    value="{{ request('q') }}" placeholder="Cari GRN / Surat Jalan..."
                    class="form-control form-control-sm search-input" style="max-width:200px;" autocomplete="off" />
                
                <input type="text" name="supplier_search" id="grn-supplier-search"
                    value="{{ request('supplier_search') }}" placeholder="Cari supplier…"
                    class="form-control form-control-sm search-input" style="max-width:160px;" autocomplete="off" />

                <select name="warehouse_id" class="form-select form-select-sm grn-filter-auto" style="max-width:160px;">
                    <option value="">Semua Gudang</option>
                    @foreach ($warehouses as $wh)
                        <option value="{{ $wh->id }}" @selected(request('warehouse_id') == $wh->id)>{{ $wh->name }}</option>
                    @endforeach
                </select>

                <select name="status" class="form-select form-select-sm grn-filter-auto" style="max-width:130px;">
                    <option value="">Semua Status</option>
                    <option value="draft"  @selected(request('status') === 'draft')>Draft</option>
                    <option value="posted" @selected(request('status') === 'posted')>Posted</option>
                    <option value="closed" @selected(request('status') === 'closed')>Closed</option>
                </select>

                @php
                    $idMonthsGrn = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
                    $grnRangeDisplay = '';
                    if (request('from_date') && request('to_date')) {
                        try {
                            $f = \Carbon\Carbon::parse(request('from_date'));
                            $t = \Carbon\Carbon::parse(request('to_date'));
                            $grnRangeDisplay = $f->day.' '.$idMonthsGrn[$f->month-1].' – '.$t->day.' '.$idMonthsGrn[$t->month-1].' '.$t->year;
                        } catch (\Exception $e) { $grnRangeDisplay = request('from_date').' – '.request('to_date'); }
                    } elseif (request('from_date')) {
                        try {
                            $f = \Carbon\Carbon::parse(request('from_date'));
                            $grnRangeDisplay = $f->day.' '.$idMonthsGrn[$f->month-1].' '.$f->year;
                        } catch (\Exception $e) { $grnRangeDisplay = request('from_date'); }
                    }
                @endphp
                <input type="text" id="grn-date-range" value="{{ $grnRangeDisplay }}" placeholder="Pilih tanggal…"
                    autocomplete="off" data-gf-date="off" class="form-control form-control-sm"
                    style="max-width:200px;cursor:pointer;" readonly />

                @if (request()->filled('q') || request()->filled('supplier_search') || request()->filled('warehouse_id') || request()->filled('status') || request()->filled('from_date') || request()->filled('to_date'))
                    <a href="{{ route('purchasing.purchase_receipts.index') }}" class="btn btn-sm btn-ship-outline btn-pill">
                        <i class="bi bi-x me-1"></i>Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    @if (isset($summary) && !empty($summary->last_date))
        <div class="filter-summary mb-2 px-1">
            Terakhir diterima: <strong class="mono">{{ id_date($summary->last_date) }}</strong>
        </div>
    @endif

    {{-- TABLE (satu tabel responsif; mobile → kartu) --}}
    <div class="card card-main">
        <div class="card-body p-0">
            <div class="table-responsive" style="overflow-x: auto; overflow-y: auto; max-height: 60vh;">
                <table class="table table-hover align-middle table-list mb-0">
                    <thead>
                        <tr>
                            <th style="width:46px; position: sticky; top: 0; z-index: 10; background: var(--card, #fff);" class="mobile-hide">#</th>
                            <th style="width:230px; position: sticky; top: 0; z-index: 10; background: var(--card, #fff);">Dokumen & Tanggal</th>
                            <th style="position: sticky; top: 0; z-index: 10; background: var(--card, #fff);">Supplier / Gudang</th>
                            <th style="width:150px; position: sticky; top: 0; z-index: 10; background: var(--card, #fff);" class="text-end mobile-hide">Total (Qty & Rp)</th>
                            <th style="width:100px; position: sticky; top: 0; z-index: 10; background: var(--card, #fff);" class="text-end mobile-hide">Reject</th>
                            <th style="width:120px; position: sticky; top: 0; z-index: 10; background: var(--card, #fff);" class="mobile-hide">Status</th>
                            <th style="width:100px; position: sticky; top: 0; z-index: 10; background: var(--card, #fff);" class="mobile-hide"></th>
                        </tr>
                    </thead>
                    <tbody id="grn-table-body">
                        @include('purchasing.purchase_receipts._rows', ['receipts' => $receipts, 'startIndex' => $startIndex])
                    </tbody>
                </table>
            </div>

            @if (method_exists($receipts, 'links'))
                <div class="px-3 py-2 border-top">{{ $receipts->links() }}</div>
            @endif
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('grn-filter-form');
    if (!form) return;

    form.querySelectorAll('select.grn-filter-auto').forEach(function (el) {
        el.addEventListener('change', function () { form.submit(); });
    });

    let searchTimer;
    form.querySelectorAll('.search-input').forEach(function (input) {
        input.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () { form.submit(); }, 500);
        });
    });
    
    // Focus the first search input if it was just typed in
    const searchParams = new URLSearchParams(window.location.search);
    if (searchParams.has('q')) {
        const qInput = document.getElementById('grn-search');
        if (qInput) {
            setTimeout(function () {
                qInput.focus();
                const len = qInput.value.length;
                qInput.setSelectionRange(len, len);
            }, 100);
        }
    } else if (searchParams.has('supplier_search')) {
        const supplierInput = document.getElementById('grn-supplier-search');
        if (supplierInput) {
            setTimeout(function () {
                supplierInput.focus();
                const len = supplierInput.value.length;
                supplierInput.setSelectionRange(len, len);
            }, 100);
        }
    }

    const rangeInput = document.getElementById('grn-date-range');
    const fromHidden = document.getElementById('grn-from-date');
    const toHidden   = document.getElementById('grn-to-date');
    if (rangeInput && window.flatpickr) {
        const ID_MONTHS = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        function fmtDate(d, withYear) { return d.getDate() + ' ' + ID_MONTHS[d.getMonth()] + (withYear ? ' ' + d.getFullYear() : ''); }
        function fmtRange(dates) {
            if (dates.length === 2) {
                const sameYear = dates[0].getFullYear() === dates[1].getFullYear();
                return fmtDate(dates[0], !sameYear) + ' – ' + fmtDate(dates[1], true);
            }
            if (dates.length === 1) return fmtDate(dates[0], true) + ' …';
            return '';
        }
        flatpickr(rangeInput, {
            mode: 'range', dateFormat: 'Y-m-d', locale: { firstDayOfWeek: 1 }, allowInput: false,
            defaultDate: [fromHidden.value, toHidden.value].filter(Boolean),
            onChange: function (selectedDates, dateStr, fp) {
                fp.input.value = fmtRange(selectedDates);
                if (selectedDates.length === 1) { fromHidden.value = flatpickr.formatDate(selectedDates[0], 'Y-m-d'); toHidden.value = ''; }
                else if (selectedDates.length === 2) {
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

    // Row click (delegated so appended rows work too)
    document.addEventListener('click', function (e) {
        const row = e.target.closest('tr.grn-row');
        if (!row) return;
        if (e.target.closest('a, button, form')) return;
        const href = row.dataset.href;
        if (href) window.location = href;
    });
});
</script>
@endpush
