@props([
    'title' => 'Daftar',
    'subtitle' => null,
    'tableClass' => 'table table-hover align-middle table-list mb-0'
])

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


    .btn-pill { border-radius: 7px; padding-inline: .78rem; box-shadow: none !important; font-weight: 600; text-decoration: none; }
    .btn-ship-primary { background: var(--shp-accent) !important; border-color: var(--shp-accent) !important; color: #fff !important; }
    .btn-ship-primary:hover { background: var(--shp-accent-2) !important; border-color: var(--shp-accent-2) !important; color: #fff !important; }
    .btn-ship-outline { color: #475569 !important; border: 1px solid rgba(148,163,184,.35) !important; }
    .btn-ship-outline:hover { background: rgba(148,163,184,.08) !important; color: #111827 !important; }

    @media (min-width: 769px) {
        .filter-select { width: auto; min-width: 130px; }
        .search-input { width: 160px; }
    }

    .table-responsive {
        overflow-x: auto;
        overflow-y: auto;
        max-height: calc(100vh - 210px);
    }
    .table-responsive::-webkit-scrollbar { width: 6px; height: 6px; }
    .table-responsive::-webkit-scrollbar-track { background: transparent; }
    .table-responsive::-webkit-scrollbar-thumb { background: rgba(148,163,184,.3); border-radius: 4px; }
    .table-responsive::-webkit-scrollbar-thumb:hover { background: rgba(148,163,184,.5); }

    .table-list { margin-bottom: 0; }

    .table-list thead th { position: sticky; top: 0; z-index: 10; border-bottom-width: 1px; font-size: .64rem; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b; background: var(--card, #fff); padding: .75rem 1rem; white-space: nowrap; }
    .table-list thead th::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        width: 100%;
        border-bottom: 1px solid rgba(148,163,184,.25);
    }

    .table-list tbody td { vertical-align: middle; border-top-color: rgba(148,163,184,.12); padding: .55rem .85rem; font-size: .78rem; }

    .badge-status { border-radius: 7px; padding: .16rem .48rem; font-size: .68rem; border: 1px solid transparent; display: inline-flex; align-items: center; gap: .35rem; white-space: nowrap; }
    .badge-status::before { content: ''; width: 7px; height: 7px; border-radius: 999px; display: inline-block; }
    
    .st-draft { background: rgba(148,163,184,.10); color: #475569; border-color: rgba(148,163,184,.30); }
    .st-draft::before { background: rgba(100,116,139,.95); }
    .st-approved { background: rgba(59,130,246,.10); color: #1d4ed8; border-color: rgba(59,130,246,.30); }
    .st-approved::before { background: rgba(59,130,246,.95); }
    .st-cancelled { background: rgba(239,68,68,.10); color: #991b1b; border-color: rgba(239,68,68,.30); }
    .st-cancelled::before { background: rgba(239,68,68,.95); }

    .code-link { font-weight: 650; text-decoration: none; color: #334155; font-size: .68rem; background: rgba(148,163,184,.12); padding: .15rem .45rem; border-radius: 6px; border: 1px solid rgba(148,163,184,.25); display: inline-block; transition: all 0.2s; white-space: nowrap; }
    .code-link:hover { background: rgba(148,163,184,.22); color: #0f172a; border-color: rgba(148,163,184,.4); }
    .muted { font-size: .74rem; color: #6b7280; }
    .supplier-name { font-weight: 600; font-size: .78rem; }

    .badge-pay { border-radius: 7px; font-size: .7rem; padding: .1rem .55rem; border: 1px solid rgba(148,163,184,.45); background: rgba(148,163,184,.10); color: #64748b; white-space: nowrap; }
    .badge-pay-paid { border-color: rgba(22,163,74,.55); background: rgba(22,163,74,.12); color: #15803d; }
    .badge-pay-partial { border-color: rgba(234,179,8,.55); background: rgba(234,179,8,.12); color: #a16207; }

    .badge-rcv { border-radius: 7px; font-size: .65rem; padding: .05rem .45rem; border: 1px solid transparent; white-space: nowrap; display: inline-block; }
    .badge-rcv-none { background: rgba(148,163,184,.08); color: #94a3b8; border-color: rgba(148,163,184,.4); }
    .badge-rcv-partial { background: #fef08a; color: #854d0e; border-color: #fde047; }
    .badge-rcv-full { background: #16a34a; color: #fff; border-color: #15803d; box-shadow: 0 1px 2px rgba(22,163,74,.3); }

    .pay-icon { font-size: 1rem; line-height: 1; }
    .pay-icon.paid { color: #16a34a; }
    .pay-icon.partial { color: #eab308; }
    .pay-icon.unpaid { color: #94a3b8; }

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
        .position-relative { flex: 1 1 100%; width: 100%; }

        .kpis { display: none; }
        .table-responsive { overflow: visible; max-height: none; }
        .table-list thead { display: none; }
        .table-list, .table-list tbody, .table-list tr, .table-list td { display: block; width: 100%; }
        .table-list tbody tr { padding: .66rem; border-top: 1px solid rgba(148,163,184,.16); cursor: pointer; }
        .table-list tbody tr:hover { background: rgba(248, 250, 252, 0.6); }
        .table-list tbody td { vertical-align: middle; border-top-color: rgba(148,163,184,.12); padding: .65rem 1rem; font-size: .83rem; border: 0; padding: 0;}
        .mobile-hide { display: none !important; }
        .ship-row-main { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; }
        .ship-row-meta { display: flex; align-items: center; gap: .45rem; flex-wrap: wrap; margin-top: .35rem; color: #64748b; font-size: .78rem; }
        .ship-row-action { margin-top: .55rem; }
        .ship-row-action .btn { width: 100%; min-height: 38px; }
    }
</style>
@endpush

<div class="page-wrap">
    @if (session('success'))
        <div class="flash-clean alert alert-success py-2 small mb-2" style="border-radius:8px; border:1px solid rgba(148,163,184,.25);">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="flash-clean alert alert-danger py-2 small mb-2" style="border-radius:8px; border:1px solid rgba(148,163,184,.25);">{{ session('error') }}</div>
    @endif

    <div class="ship-topbar">
        <div>
            <div class="title">{{ $title }}</div>
            @if($subtitle)
                <div class="sub">{{ $subtitle }}</div>
            @endif

            @if(isset($kpis))
                <div class="kpis">
                    {{ $kpis }}
                </div>
            @endif
        </div>

        @if(isset($actions))
            <div>
                {{ $actions }}
            </div>
        @endif
    </div>

    @if(isset($filters))
        {{ $filters }}
    @endif

    @if(isset($summary))
        <div class="filter-summary mb-2 px-1">
            {{ $summary }}
        </div>
    @endif

    <div class="card card-main">
        <div class="card-body p-0">
            @if(isset($emptyState))
                {{ $emptyState }}
            @else
                <div class="table-responsive" style="overflow-x: auto; overflow-y: auto; max-height: 60vh;">
                    <table class="{{ $tableClass }}">
                        @if(isset($thead))
                            <thead>
                                {{ $thead }}
                            </thead>
                        @endif
                        <tbody>
                            {{ $slot }}
                        </tbody>
                    </table>
                </div>
                @if(isset($pagination))
                    <div class="px-3 py-2 border-top">
                        {{ $pagination }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Auto-focus ke input cari saat halaman dibuka
    const searchInput = document.querySelector('input[name="q"].search-input')
        || document.querySelector('input[name="search"]') 
        || document.querySelector('input[name="q"]');
    if (searchInput) {
        setTimeout(function () {
            searchInput.focus();
            const len = searchInput.value.length;
            try { searchInput.setSelectionRange(len, len); } catch (e) {}
        }, 100);
    }

    // Row click via data-href
    document.querySelectorAll('tbody tr[data-href]').forEach(function (row) {
        row.addEventListener('click', function (e) {
            if (e.target.closest('a, button, form')) return;
            const href = row.dataset.href;
            if (href) window.location = href;
        });
    });
});
</script>
@endpush
