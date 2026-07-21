import re

with open('resources/views/purchasing/purchase_orders/index.blade.php', 'r') as f:
    content = f.read()

# 1. Remove @push('head') block
content = re.sub(r"@push\('head'\).*?@endpush\n", "", content, flags=re.DOTALL)

# 2. Replace topbar with component start
topbar_pattern = r'<div class="page-wrap">.*?<div class="ship-topbar">.*?</div>\n        @endif\n    </div>'
component_start = """<x-index-layout title="Purchase Orders" subtitle="Daftar pemesanan barang.">
    @if (isset($summary))
        <x-slot name="kpis">
            <span class="kpi"><span class="lbl">Total PO</span><span class="val mono">{{ $summary->total_orders ?? 0 }}</span></span>
            <span class="kpi"><span class="lbl">Draft</span><span class="val mono">{{ $summary->draft_count ?? 0 }}</span></span>
            <span class="kpi"><span class="lbl">Approved</span><span class="val mono">{{ $summary->approved_count ?? 0 }}</span></span>
            @if ($canSeeMoney)
                <span class="kpi" style="background: rgba(22, 163, 74, 0.05); border-color: rgba(22, 163, 74, 0.2);"><span class="lbl" style="color:#15803d;">Total Nilai</span><span class="val mono" style="color:#16a34a;">Rp {{ number_format($summary->total_grand_total ?? 0, 0, ',', '.') }}</span></span>
            @endif
        </x-slot>
    @endif

    @if ($user && in_array($user->role, ['owner', 'admin']))
        <x-slot name="actions">
            <a href="{{ route('purchasing.purchase_orders.create') }}" class="btn btn-sm btn-ship-primary btn-pill">
                <i class="bi bi-plus-lg me-1"></i> PO Baru
            </a>
        </x-slot>
    @endif"""
content = re.sub(topbar_pattern, component_start, content, flags=re.DOTALL)

# 3. Wrap filters
content = content.replace("{{-- FILTER --}}", "<x-slot name=\"filters\">\n        {{-- FILTER --}}")
content = content.replace("</form>", "</form>\n    </x-slot>")

# 4. Wrap summary
summary_pattern = r'(@if \(isset\(\$summary\) && !empty\(\$summary->last_date\)\)\s*<div class="filter-summary mb-2 px-1">\s*PO terakhir dibuat: <strong class="mono">{{ id_date\(\$summary->last_date\) }}</strong>\s*</div>\s*@endif)'
content = re.sub(summary_pattern, r'<x-slot name="summary">\n        \1\n    </x-slot>', content)

# 5. Replace card and thead
card_pattern = r'<div class="card card-main">.*?<table class="table table-hover align-middle table-list mb-0">\s*<thead>'
thead_start = """    <x-slot name="emptyState">
        @if ($orders->count() === 0)
            <div class="empty">Belum ada Purchase Order.</div>
        @endif
    </x-slot>

    <x-slot name="thead">"""
content = re.sub(card_pattern, thead_start, content, flags=re.DOTALL)

# Close thead
content = content.replace("</thead>\n                <tbody>", "</x-slot>")

# 6. Pagination and closing
bottom_pattern = r'</tbody>\s*</table>\s*</div>\s*@if \(method_exists\(\$orders, \'links\'\)\).*?</div>'
closing = """
    <x-slot name="pagination">
        @if (method_exists($orders, 'links'))
            {{ $orders->links() }}
        @endif
    </x-slot>
</x-index-layout>"""
content = re.sub(bottom_pattern, closing, content, flags=re.DOTALL)

# 7. Clean up scripts
scripts_pattern = r"@push\('scripts'\).*?const form = document\.getElementById\('po-filter-form'\);"
new_scripts = """@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('po-filter-form');"""
content = re.sub(scripts_pattern, new_scripts, content, flags=re.DOTALL)

with open('resources/views/purchasing/purchase_orders/index.blade.php', 'w') as f:
    f.write(content)

