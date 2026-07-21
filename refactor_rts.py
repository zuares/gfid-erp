import re

with open('resources/views/inventory/rts_stock_requests/index.blade.php', 'r') as f:
    content = f.read()

# Replace all CSS in @push('head') with just the specific ones needed
css_pattern = r"@push\('head'\).*?@endpush\n"
new_css = """@push('head')
<style>
    /* table specifics */
    .col-date { width: 135px; }
    .col-item { min-width: 250px; }
    .col-qty  { width: 110px; text-align: right; }
    .col-st   { width: 140px; }

    .doc-label {
        font-weight: 750; font-size: .84rem; display: block; margin-bottom: .45rem;
    }

    .chips { display: flex; flex-wrap: wrap; gap: .35rem; }
    .ic {
        display: inline-flex; align-items: stretch;
        border: 1px solid rgba(148,163,184,.30); border-radius: 6px;
        overflow: hidden; font-size: .70rem; font-weight: 650;
        background: transparent;
    }
    body[data-theme="dark"] .ic { border-color: rgba(148,163,184,.20); }
    .ic > span {
        background: rgba(148,163,184,.14);
        padding: .15rem .35rem; border-left: 1px solid rgba(148,163,184,.25);
    }
    .ic::before {
        content: attr(data-text); padding: .15rem .45rem; display: flex; align-items: center;
    }
    .ic { padding-left: .45rem; padding-right: 0; align-items: center; }

    .more-btn {
        border: 1px dashed rgba(148,163,184,.45); border-radius: 6px; background: transparent;
        color: inherit; font-size: .65rem; font-weight: 700; padding: .15rem .4rem; cursor: pointer;
    }
    .more-btn:hover { border-style: solid; background: rgba(148,163,184,.12); }

    .badge {
        padding: .20rem .5rem; border-radius: 6px; font-size: .70rem; font-weight: 700;
        border: 1px solid transparent; display: inline-flex; align-items: center;
    }
    .badge.ok     { background: rgba(16,185,129,.12); color: #059669; border-color: rgba(16,185,129,.3); }
    .badge.warn   { background: rgba(245,158,11,.12); color: #d97706; border-color: rgba(245,158,11,.3); }
    .badge.danger { background: rgba(239,68,68,.12); color: #dc2626; border-color: rgba(239,68,68,.3); }

    @media (max-width: 768px) {
        .col-date, .col-qty, .col-st { display: none !important; }
        .col-item { width: 100%; display: block; }
    }
</style>
@endpush
"""
content = re.sub(css_pattern, new_css, content, flags=re.DOTALL)

# Refactor the top section
top_pattern = r'<div class="page-wrap">.*?<div class="filter-bar">'
component_start = """<x-index-layout title="RTS • Permintaan Stok" subtitle="Permintaan barang jadi dari PRD ke RTS.">
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

    <x-slot name="emptyState">
        @if ($stockRequests->count() === 0)
            <div class="empty">Belum ada permintaan RTS.</div>
        @endif
    </x-slot>

    <x-slot name="thead">
"""
content = re.sub(top_pattern, component_start, content, flags=re.DOTALL)

# Remove the old inputs and resets up to the <div class="tbl-wrap">
old_filter_pattern = r'            <input type="text".*?<div class="tbl-wrap">\s*<table>\s*<thead>'
content = re.sub(old_filter_pattern, "", content, flags=re.DOTALL)

# Handle the </thead> closing
content = content.replace("</thead>\n            <tbody>", "</x-slot>")

# Update the empty row logic
content = content.replace('<tr class="empty-row"><td colspan="4">Belum ada permintaan RTS.</td></tr>', "")

# Handle the bottom of the page
bottom_pattern = r'            </tbody>\s*</table>\s*</div>\s*<div style="margin-top:1rem">{{ \$stockRequests->links\(\) }}</div>\s*</div>'
closing = """    <x-slot name="pagination">
        @if (method_exists($stockRequests, 'links'))
            {{ $stockRequests->links() }}
        @endif
    </x-slot>
</x-index-layout>"""
content = re.sub(bottom_pattern, closing, content, flags=re.DOTALL)


with open('resources/views/inventory/rts_stock_requests/index.blade.php', 'w') as f:
    f.write(content)

