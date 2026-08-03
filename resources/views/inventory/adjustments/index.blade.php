@extends('layouts.app')

@section('title', 'Inventory • Adjustments')

@push('head')
<style>
    :root{
        --adj-accent:#334155;
        --adj-accent-2:#1f2937;
        --adj-border:rgba(148,163,184,.18);
        --adj-muted:#64748b;
    }

    .adj-topbar{
        position:sticky;
        top:0;
        z-index:300;
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:.4rem;
        padding:.45rem .75rem;
        margin:0 -.75rem .65rem;
        background:var(--card,#fff);
        border-bottom:1px solid var(--adj-border);
    }
    body[data-theme="dark"] .adj-topbar{ background:var(--card,#0f172a); }
    .adj-topbar-left{ flex:0 0 auto; min-width:0; }
    .adj-topbar-right{ flex:1 1 0; min-width:0; display:flex; justify-content:flex-end; }

    .title{ font-weight:750; font-size:1rem; letter-spacing:0; margin:0; }
    .sub{ color:var(--adj-muted); font-size:.78rem; }
    body[data-theme="dark"] .sub{ color:#9ca3af; }

    .kpis{ display:flex; flex-wrap:wrap; gap:.32rem; margin-top:.35rem; }
    .kpi{
        display:inline-flex; align-items:baseline; gap:.45rem;
        border-radius:7px; padding:.2rem .48rem;
        border:1px solid rgba(148,163,184,.28);
        background:transparent;
        font-size:.72rem;
    }
    body[data-theme="dark"] .kpi{
        background:rgba(15,23,42,.96);
        border-color:rgba(51,65,85,.85);
    }
    .kpi .lbl{ text-transform:none; letter-spacing:0; font-size:.66rem; color:#94a3b8; }
    body[data-theme="dark"] .kpi .lbl{ color:#6b7280; }
    .kpi .val{ font-weight:650; color:var(--adj-accent); }

    .controls{ display:flex; gap:.35rem; align-items:center; flex-wrap:nowrap; }
    .filter-label{ font-size:.72rem; color:#94a3b8; white-space:nowrap; }
    body[data-theme="dark"] .filter-label{ color:#6b7280; }
    .filter-select{ border-radius:7px; padding-left:.6rem; padding-right:1.5rem; font-size:.78rem; height:30px; }
    .btn-pill{ border-radius:7px; padding-inline:.65rem; box-shadow:none!important; font-weight:600; }
    .btn-adj-primary{ background:var(--adj-accent)!important; border-color:var(--adj-accent)!important; color:#fff!important; font-size:.78rem; height:30px; }
    .btn-adj-primary:hover{ background:var(--adj-accent-2)!important; border-color:var(--adj-accent-2)!important; color:#fff!important; }
    .btn-adj-outline{ color:#475569!important; background:transparent!important; border:1px solid rgba(148,163,184,.35)!important; }
    .btn-adj-outline:hover{ background:rgba(148,163,184,.08)!important; color:#111827!important; }

    .card-main{
        background:var(--card);
        border-radius:8px;
        border:1px solid var(--adj-border);
        box-shadow:none;
    }
    body[data-theme="dark"] .card-main{ border-color:rgba(51,65,85,.85); box-shadow:none; }

    .adj-table-scroll{
        max-height:calc(100vh - 160px);
        overflow-y:auto;
        overflow-x:hidden;
        overscroll-behavior:contain;
    }

    .table-list{ margin-bottom:0; }
    .table-list thead th{
        position:sticky;
        top:0;
        z-index:2;
        border-bottom-width:1px;
        font-size:.68rem;
        text-transform:none;
        letter-spacing:0;
        color:#64748b;
        background:var(--card,#fff);
        padding:.52rem .62rem;
        white-space:nowrap;
    }
    body[data-theme="dark"] .table-list thead th{
        background:rgba(15,23,42,.98);
        color:#9ca3af;
        border-bottom-color:rgba(30,64,175,.6);
    }
    .table-list tbody td{
        vertical-align:middle;
        border-top-color:rgba(148,163,184,.16);
        padding:.52rem .62rem;
    }
    body[data-theme="dark"] .table-list tbody td{ border-top-color:rgba(51,65,85,.85); }

    .code-link{ font-weight:700; text-decoration:none; color:inherit; }
    .code-link:hover{ text-decoration:underline; }
    .muted{ font-size:.82rem; color:#6b7280; }
    body[data-theme="dark"] .muted{ color:#9ca3af; }

    .badge-status{
        border-radius:7px; padding:.16rem .48rem;
        font-size:.68rem; letter-spacing:0; text-transform:none;
        border:1px solid transparent;
        display:inline-flex; align-items:center; gap:.35rem;
        white-space:nowrap;
    }
    .badge-status::before{ content:''; width:7px; height:7px; border-radius:999px; display:inline-block; }

    .st-draft{ background:rgba(148,163,184,.10); color:#475569; border-color:rgba(148,163,184,.30); }
    .st-draft::before{ background:rgba(100,116,139,.95); }
    .st-pending{ background:rgba(234,179,8,.10); color:#854d0e; border-color:rgba(234,179,8,.30); }
    .st-pending::before{ background:rgba(234,179,8,.95); }
    .st-approved{ background:rgba(34,197,94,.10); color:#166534; border-color:rgba(34,197,94,.30); }
    .st-approved::before{ background:rgba(34,197,94,.95); }
    .st-void{ background:rgba(239,68,68,.10); color:#991b1b; border-color:rgba(239,68,68,.30); }
    .st-void::before{ background:rgba(239,68,68,.95); }

    body[data-theme="dark"] .st-pending{ background:rgba(234,179,8,.18); color:#fef3c7; border-color:rgba(234,179,8,.50); }
    body[data-theme="dark"] .st-approved{ background:rgba(34,197,94,.18); color:#dcfce7; border-color:rgba(34,197,94,.50); }
    body[data-theme="dark"] .st-void{ background:rgba(239,68,68,.18); color:#fecaca; border-color:rgba(239,68,68,.50); }

    .empty{ padding:2.2rem 1.25rem; text-align:center; color:#64748b; }
    body[data-theme="dark"] .empty{ color:#9ca3af; }
    .divider{ height:1px; background:rgba(148,163,184,.20); }
    body[data-theme="dark"] .divider{ background:rgba(51,65,85,.85); }
    .flash-clean{ border-radius:8px; padding:.62rem .75rem; font-size:.84rem; border:1px solid rgba(148,163,184,.25); }

    .text-mono{ font-variant-numeric:tabular-nums; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace; }
    .diff-plus{ color:#16a34a; }
    .diff-minus{ color:#dc2626; }
    .source-pill{ border-radius:7px; padding:.14rem .44rem; font-size:.68rem; border:1px solid rgba(148,163,184,.28); background:transparent; color:#64748b; display:inline-flex; align-items:center; gap:.35rem; white-space:nowrap; }
    body[data-theme="dark"] .source-pill{ border-color:rgba(51,65,85,.85); color:#9ca3af; }
    .source-pill::before{ content:''; width:7px; height:7px; border-radius:999px; display:inline-block; }
    .source-pill--so::before{ background:rgba(59,130,246,.95); }
    .source-pill--manual::before{ background:rgba(148,163,184,.95); }

    @media (max-width:768px){
        .adj-topbar{ margin:0 -.5rem .65rem; padding:.5rem .65rem; flex-wrap:wrap; }
        .adj-topbar-left{ flex:1 1 100%; }
        .adj-topbar-right{ flex:1 1 100%; justify-content:stretch; }
        .title{ font-size:1.05rem; }
        .sub{ display:none; }
        .controls{ flex-wrap:wrap; gap:.35rem; }
        .controls form{ flex:1 1 100%; }
        .filter-select{ width:100%; min-height:40px; height:auto; }
        .controls .btn{ min-height:40px; width:100%; }
        .kpis{ display:none; }
        .adj-table-scroll{ max-height:none; overflow:visible; }
        .table-list thead{ display:none; }
        .table-list,
        .table-list tbody,
        .table-list tr,
        .table-list td{ display:block; width:100%; }
        .table-list tbody tr{
            padding:.66rem;
            border-top:1px solid rgba(148,163,184,.16);
        }
        .table-list tbody td{ border:0; padding:0; }
        .table-list tbody td.mobile-hide{ display:none; }
        .adj-row-main{
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:.75rem;
        }
        .adj-row-meta{
            display:flex;
            align-items:center;
            gap:.45rem;
            flex-wrap:wrap;
            margin-top:.35rem;
            color:#64748b;
            font-size:.78rem;
        }
        .adj-row-action{ margin-top:.55rem; }
        .adj-row-action .btn{ width:100%; min-height:40px; }
    }
</style>
@endpush

@section('content')
    @php
        use Illuminate\Support\Carbon;

        $fmtDate = function ($value, string $format = 'd M Y', string $fallback = '-') {
            if (empty($value)) return $fallback;
            try {
                if ($value instanceof \DateTimeInterface) return $value->format($format);
                return Carbon::parse($value)->format($format);
            } catch (\Throwable $e) {
                return $fallback;
            }
        };

        $adjustmentSummaries = $adjustmentSummaries ?? [];

        $sourceLabel = function ($adj) {
            if ($adj->source_type === \App\Models\StockOpname::class) return 'Stock Opname';
            return 'Manual';
        };

        $statusFilter    = $filters['status'] ?? 'all';
        $warehouseFilter = $filters['warehouse_id'] ?? '';
        $sourceFilter    = $filters['source_type'] ?? 'all';

        $pageTotal = method_exists($adjustments, 'total') ? $adjustments->total() : $adjustments->count();
        $pageCount = $adjustments->count();
    @endphp

    @if(session('message'))
        <div class="flash-clean alert alert-{{ session('status') === 'success' ? 'success' : 'danger' }} mb-2">
            {{ session('message') }}
        </div>
    @endif

    <div class="adj-topbar">
        <div class="adj-topbar-left">
            <div class="title">Adjustments</div>
            <div class="sub">Penyesuaian stok manual & stock opname.</div>
            <div class="kpis">
                <span class="kpi"><span class="lbl">Total</span><span class="val">{{ number_format($pageTotal, 0, ',', '.') }}</span></span>
                <span class="kpi"><span class="lbl">Halaman</span><span class="val">{{ number_format($pageCount, 0, ',', '.') }}</span></span>
            </div>
        </div>
        <div class="adj-topbar-right">
            <div class="controls">
                <form method="GET" class="d-flex gap-2 align-items-center">
                    <span class="filter-label">Status</span>
                    <select name="status" class="form-select form-select-sm filter-select" onchange="this.form.submit()">
                        <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All</option>
                        <option value="draft" {{ $statusFilter === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ $statusFilter === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="void" {{ $statusFilter === 'void' ? 'selected' : '' }}>Void</option>
                    </select>
                    <span class="filter-label">Sumber</span>
                    <select name="source_type" class="form-select form-select-sm filter-select" onchange="this.form.submit()">
                        <option value="all" {{ $sourceFilter === 'all' ? 'selected' : '' }}>Semua</option>
                        <option value="stock_opname" {{ $sourceFilter === 'stock_opname' ? 'selected' : '' }}>SO</option>
                        <option value="manual" {{ $sourceFilter === 'manual' ? 'selected' : '' }}>Manual</option>
                    </select>
                    <span class="filter-label">Gudang</span>
                    <select name="warehouse_id" class="form-select form-select-sm filter-select" onchange="this.form.submit()">
                        <option value="">Semua</option>
                        @foreach ($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ (string) $wh->id === (string) $warehouseFilter ? 'selected' : '' }}>{{ $wh->code }}</option>
                        @endforeach
                    </select>
                </form>
                <a href="{{ route('inventory.adjustments.manual.create') }}" class="btn btn-sm btn-adj-primary btn-pill">+ Manual</a>
            </div>
        </div>
    </div>

    <div class="card card-main">
        <div class="card-body p-0">
            @if ($adjustments->isEmpty())
                <div class="empty">
                    Belum ada dokumen adjustment.
                    <div class="mt-1">Klik <b>+ Manual</b> untuk membuat baru.</div>
                </div>
            @else
                <div class="adj-table-scroll">
                    <table class="table table-hover align-middle table-list">
                        <thead>
                            <tr>
                                <th style="width:46px;">#</th>
                                <th style="width:120px;">Tanggal</th>
                                <th>Adjustment</th>
                                <th>Gudang</th>
                                <th class="text-center" style="width:70px;">Item</th>
                                <th style="width:100px;">Sumber</th>
                                <th style="width:100px;">Status</th>
                                <th style="width:90px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($adjustments as $index => $adj)
                                @php
                                    $statusClass = match ($adj->status) {
                                        'pending' => 'st-pending',
                                        'approved' => 'st-approved',
                                        'void', 'cancelled' => 'st-void',
                                        default => 'st-draft',
                                    };
                                    $source = $sourceLabel($adj);
                                    $sourceClass = $adj->source_type === \App\Models\StockOpname::class ? 'source-pill--so' : 'source-pill--manual';
                                @endphp
                                <tr>
                                    <td class="text-muted small mobile-hide">{{ ($adjustments->currentPage() - 1) * $adjustments->perPage() + $index + 1 }}</td>
                                    <td class="small mobile-hide">{{ $fmtDate($adj->date) }}</td>
                                    <td>
                                        <div class="adj-row-main">
                                            <div>
                                                <a class="code-link" href="{{ route('inventory.adjustments.show', ['inventoryAdjustment' => $adj->getKey()]) }}">{{ $adj->code }}</a>
                                                <div class="muted mt-1">{{ $adj->reason ?: '—' }}</div>
                                            </div>
                                            <span class="badge-status {{ $statusClass }} d-md-none">{{ ucfirst($adj->status) }}</span>
                                        </div>
                                        <div class="adj-row-meta d-md-none">
                                            <span>{{ $fmtDate($adj->date) }}</span>
                                            <span>{{ $adj->lines_count ?? ($adj->lines?->count() ?? 0) }} item</span>
                                            <span>{{ $source }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span>{{ $adj->warehouse?->code ?? '-' }}</span>
                                        <div class="muted mobile-hide">{{ $adj->warehouse?->name }}</div>
                                    </td>
                                    <td class="text-center mobile-hide">
                                        <span class="fw-semibold">{{ $adj->lines_count ?? ($adj->lines?->count() ?? 0) }}</span>
                                    </td>
                                    <td class="mobile-hide">
                                        <span class="source-pill {{ $sourceClass }}">{{ $source }}</span>
                                    </td>
                                    <td class="mobile-hide">
                                        <span class="badge-status {{ $statusClass }}">{{ ucfirst($adj->status) }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('inventory.adjustments.show', ['inventoryAdjustment' => $adj->getKey()]) }}" class="btn btn-sm btn-adj-outline btn-pill">Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="empty">Belum ada dokumen adjustment.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="divider"></div>
                <div class="p-3">{{ $adjustments->links() }}</div>
            @endif
        </div>
    </div>
@endsection
