@php
    $summaryFmt = fn ($value) => 'Rp ' . number_format(abs((float) $value), 0, ',', '.');
    $summaryCampaigns = collect($campaigns ?? []);
    $summaryRegularRows = $summaryCampaigns
        ->reject(fn ($camp) => str_starts_with((string) ($camp->channel_campaign_id ?? ''), 'GMS-'))
        ->filter(fn ($camp) => (float) ($camp->spend ?? 0) > 0 || (float) ($camp->gmv ?? 0) > 0)
        ->map(function ($camp) {
            $spendAfterTax = (float) ($camp->spend ?? 0) * 1.11;
            $hasSales = (float) ($camp->gmv ?? 0) > 0 || (int) ($camp->orders ?? 0) > 0 || (float) ($camp->items_sold ?? 0) > 0;
            $hpp = !$hasSales ? 0.0 : ($camp->total_cogs !== null ? (float) $camp->total_cogs : null);
            $profit = !$hasSales
                ? -$spendAfterTax
                : ($camp->profit_after_ads !== null ? (float) $camp->profit_after_ads : null);
            $previousProfit = $camp->prev_profit_after_ads !== null ? (float) $camp->prev_profit_after_ads : null;
            return (object) [
                'camp' => $camp,
                'spendAfterTax' => $spendAfterTax,
                'netRevenue' => (float) ($camp->net_revenue ?? 0),
                'hpp' => $hpp,
                'profit' => $profit,
                'previousProfit' => $previousProfit,
                'category' => $camp->item_category ?: 'Belum termapping',
            ];
        })
        ->sortBy(fn ($row) => $row->profit === null ? INF : $row->profit)
        ->values();

    $summaryCategoryRows = $summaryRegularRows
        ->groupBy('category')
        ->map(function ($group, $category) {
            $known = $group->filter(fn ($row) => $row->profit !== null);
            return (object) [
                'category' => $category,
                'orders' => $group->sum(fn ($row) => (int) ($row->camp->orders ?? 0)),
                'items' => $group->sum(fn ($row) => (float) ($row->camp->items_sold ?? 0)),
                'gmv' => $group->sum(fn ($row) => (float) ($row->camp->gmv ?? 0)),
                'netRevenue' => $group->sum('netRevenue'),
                'spendAfterTax' => $group->sum('spendAfterTax'),
                'hpp' => $known->sum(fn ($row) => (float) ($row->hpp ?? 0)),
                'profit' => $known->sum('profit'),
                'unknown' => $group->count() - $known->count(),
                'previousProfit' => $group->sum(fn ($row) => (float) ($row->previousProfit ?? 0)),
            ];
        })
        ->sortBy('profit')
        ->values();

    $summaryUnmappedRows = $summaryRegularRows
        ->filter(fn ($row) => $row->hpp === null && !empty($row->camp->channel_item_id))
        ->values();
    $summaryGmsStoreIds = $summaryCampaigns
        ->filter(fn ($camp) => str_starts_with((string) ($camp->channel_campaign_id ?? ''), 'GMS-'))
        ->pluck('store_id')
        ->filter()
        ->unique()
        ->values()
        ->all();
    if (($storeId ?? 'all') !== 'all' && is_numeric($storeId) && !in_array((int) $storeId, array_map('intval', $summaryGmsStoreIds), true)) {
        $summaryGmsStoreIds[] = (int) $storeId;
    }
    if (($storeId ?? 'all') === 'all') {
        $summaryGmsStoreIds = collect($stores ?? [])->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }
@endphp

<div class="ads-tab-panel mb-3 summary-profit-breakdown">
    <div class="ads-tab-panel-head">
        <div>
            <div class="ads-tab-panel-title"><i class="bi bi-layout-split text-success"></i> Ringkasan Profit</div>
            <div class="ads-tab-panel-note">Sumber hitungan sama dengan tab Profit.</div>
        </div>
    </div>
    <div class="p-3">
        <div class="summary-breakdown-tabs" role="tablist">
            <button type="button" class="summary-breakdown-tab is-active" data-summary-view="category">Per Kategori</button>
            <button type="button" class="summary-breakdown-tab" data-summary-view="roas">GMV Max ROAS</button>
            <button type="button" class="summary-breakdown-tab" data-summary-view="auto">GMV Max Auto</button>
            <button type="button" class="summary-breakdown-tab" data-summary-view="unmapped">Produk Belum Mapping</button>
        </div>

        <div class="summary-breakdown-view is-active" data-summary-panel="category">
            <div class="table-responsive summary-breakdown-table-wrap">
                <table class="dpanel-table dpanel-table-sm w-100 summary-breakdown-table">
                    <thead><tr><th>#</th><th>Kategori</th><th class="text-end">Pesanan</th><th class="text-end">Omzet</th><th class="text-end">Iklan</th><th class="text-end">HPP</th><th class="text-end">Laba Bersih</th></tr></thead>
                    <tbody>
                    @forelse($summaryCategoryRows as $index => $row)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><div class="summary-breakdown-name">{{ $row->category }}</div>@if($row->unknown > 0)<div class="summary-breakdown-muted">{{ $row->unknown }} belum dihitung</div>@endif</td>
                            <td class="text-end">{{ number_format($row->orders, 0, ',', '.') }}</td>
                            <td class="text-end" title="Gross · AOV · Net"><div>{{ $summaryFmt($row->gmv) }}</div><div class="summary-breakdown-muted">{{ $row->orders > 0 ? $summaryFmt($row->gmv / $row->orders) : '—' }}</div><div class="summary-breakdown-net">{{ $summaryFmt($row->netRevenue) }}</div></td>
                            <td class="text-end" title="Setelah PPN · Sebelum PPN"><div class="summary-breakdown-cost">−{{ $summaryFmt($row->spendAfterTax) }}</div><div class="summary-breakdown-muted">−{{ $summaryFmt($row->spendAfterTax / 1.11) }}</div></td>
                            <td class="text-end" title="Total HPP · HPP rata-rata per pcs"><div>−{{ $summaryFmt($row->hpp) }}</div><div class="summary-breakdown-muted">{{ $row->items > 0 && $row->hpp > 0 ? $summaryFmt($row->hpp / $row->items) : '—' }}</div></td>
                            <td class="text-end summary-breakdown-profit {{ $row->profit >= 0 ? 'is-positive' : 'is-negative' }}">{{ $row->profit < 0 ? '−' : '' }}{{ $summaryFmt($row->profit) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center summary-breakdown-muted">Belum ada data.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="summary-breakdown-view" data-summary-panel="roas">
            <div class="table-responsive summary-breakdown-table-wrap">
                <table class="dpanel-table dpanel-table-sm w-100 summary-breakdown-table">
                    <thead><tr><th>#</th><th>Kampanye / Produk</th><th class="text-end">Pesanan</th><th class="text-end">Omzet</th><th class="text-end">Iklan</th><th class="text-end">HPP</th><th class="text-end">Laba Bersih</th></tr></thead>
                    <tbody>
                    @forelse($summaryRegularRows as $index => $row)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><div class="summary-breakdown-name" title="{{ $row->camp->marketplace_item_name ?: ($row->camp->campaign_name ?: 'Produk marketplace') }}">{{ $row->camp->marketplace_item_name ?: ($row->camp->campaign_name ?: 'Produk marketplace') }}</div><div class="summary-breakdown-muted">{{ $row->camp->marketplace_item_sku ?: ($row->camp->channel_item_id ? 'SKU ' . $row->camp->channel_item_id : 'Kampanye') }}</div></td>
                            <td class="text-end">{{ number_format((int) ($row->camp->orders ?? 0), 0, ',', '.') }}</td>
                            <td class="text-end" title="Gross · AOV · Net"><div>{{ $summaryFmt($row->camp->gmv) }}</div><div class="summary-breakdown-muted">{{ $row->camp->orders > 0 ? $summaryFmt($row->camp->gmv / $row->camp->orders) : '—' }}</div><div class="summary-breakdown-net">{{ $summaryFmt($row->netRevenue) }}</div></td>
                            <td class="text-end" title="Setelah PPN · Sebelum PPN"><div class="summary-breakdown-cost">−{{ $summaryFmt($row->spendAfterTax) }}</div><div class="summary-breakdown-muted">−{{ $summaryFmt($row->camp->spend) }}</div></td>
                            <td class="text-end" title="Total HPP · HPP per pcs"><div>{{ $row->hpp === null ? '—' : '−' . $summaryFmt($row->hpp) }}</div><div class="summary-breakdown-muted">{{ $row->hpp !== null && (float) ($row->camp->items_sold ?? 0) > 0 ? $summaryFmt($row->hpp / $row->camp->items_sold) : '—' }}</div></td>
                            <td class="text-end summary-breakdown-profit {{ $row->profit >= 0 ? 'is-positive' : 'is-negative' }}">{{ $row->profit === null ? 'N/A' : (($row->profit < 0 ? '−' : '') . $summaryFmt($row->profit)) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center summary-breakdown-muted">Belum ada data.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="summary-breakdown-view" data-summary-panel="auto">
            <div id="summaryGmsAutoBody" class="summary-breakdown-loading">Pilih tab untuk memuat GMV Max Auto…</div>
        </div>

        <div class="summary-breakdown-view" data-summary-panel="unmapped">
            <div class="table-responsive summary-breakdown-table-wrap">
                <table class="dpanel-table dpanel-table-sm w-100 summary-breakdown-table">
                    <thead><tr><th>#</th><th>Produk / SKU</th><th class="text-end">Pesanan</th><th class="text-end">Omzet</th><th class="text-end">Iklan</th><th class="text-center">Status</th></tr></thead>
                    <tbody>
                    @forelse($summaryUnmappedRows as $index => $row)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><div class="summary-breakdown-name">{{ $row->camp->marketplace_item_name ?: 'Produk belum ditemukan' }}</div><div class="summary-breakdown-muted">{{ $row->camp->marketplace_item_sku ?: ('Item ID ' . $row->camp->channel_item_id) }}</div></td>
                            <td class="text-end">{{ number_format((int) ($row->camp->orders ?? 0), 0, ',', '.') }}</td>
                            <td class="text-end">{{ $summaryFmt($row->camp->gmv) }}</td>
                            <td class="text-end" title="Setelah PPN · Sebelum PPN"><div class="summary-breakdown-cost">−{{ $summaryFmt($row->spendAfterTax) }}</div><div class="summary-breakdown-muted">−{{ $summaryFmt($row->camp->spend) }}</div></td>
                            <td class="text-center"><span class="summary-breakdown-status">HPP belum ada</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center summary-breakdown-muted">Semua produk regular sudah memiliki HPP.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div id="summaryGmsUnmappedBody" class="summary-breakdown-loading"></div>
        </div>
    </div>
</div>

<style>
    .summary-breakdown-tabs { display:flex; gap:.35rem; overflow-x:auto; padding-bottom:.45rem; scrollbar-width:none; }
    .summary-breakdown-tabs::-webkit-scrollbar { display:none; }
    .summary-breakdown-tab { border:1px solid var(--dsh-border); border-radius:999px; background:transparent; color:var(--dsh-muted); padding:.38rem .7rem; font-size:.68rem; font-weight:750; white-space:nowrap; }
    .summary-breakdown-tab.is-active { color:#fff; background:var(--dsh-accent); border-color:var(--dsh-accent); }
    .summary-breakdown-view { display:none; padding-top:.55rem; }
    .summary-breakdown-view.is-active { display:block; }
    .summary-breakdown-table-wrap { overflow-x:hidden; }
    .summary-breakdown-table { table-layout:fixed; font-size:.7rem; }
    .summary-breakdown-table th, .summary-breakdown-table td { padding:.48rem .35rem; vertical-align:middle; }
    .summary-breakdown-table th:first-child, .summary-breakdown-table td:first-child { width:2rem; }
    .summary-breakdown-name { font-weight:700; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .summary-breakdown-muted { color:var(--dsh-muted); font-size:.6rem; font-weight:500; }
    .summary-breakdown-net { color:#0369a1; font-size:.62rem; font-weight:650; }
    .summary-breakdown-cost { color:#dc2626; font-weight:700; }
    .summary-breakdown-profit { font-weight:700; font-variant-numeric:tabular-nums; }
    .summary-breakdown-profit.is-positive { color:#15803d; }
    .summary-breakdown-profit.is-negative { color:#b91c1c; }
    .summary-breakdown-status { color:#b45309; font-size:.62rem; font-weight:700; }
    .summary-breakdown-loading { color:var(--dsh-muted); font-size:.72rem; padding:.75rem 0; }
    @media (max-width:768px) {
        .summary-breakdown-table { font-size:.64rem; }
        .summary-breakdown-table th, .summary-breakdown-table td { padding:.38rem .22rem; }
        .summary-breakdown-table th:nth-child(3), .summary-breakdown-table td:nth-child(3),
        .summary-breakdown-table th:nth-child(5), .summary-breakdown-table td:nth-child(5) { display:none; }
    }
</style>

<script>
(function () {
    const tabs = Array.from(document.querySelectorAll('.summary-breakdown-tab'));
    const panels = Array.from(document.querySelectorAll('.summary-breakdown-view'));
    const autoBody = document.getElementById('summaryGmsAutoBody');
    const unmappedBody = document.getElementById('summaryGmsUnmappedBody');
    const endpoint = @json(url('/marketplace/ads-dashboard/gms-items'));
    const storeIds = @json($summaryGmsStoreIds);
    const fromDate = @json($dateFrom ?? now()->subDays(6)->toDateString());
    const toDate = @json($dateTo ?? now()->toDateString());
    const compareMode = @json($compareMode ?? 'prev_period');
    const esc = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));
    const fmt = (value) => 'Rp ' + Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
    let loaded = false;

    function gmsTable(rows, includeUnmapped) {
        const filtered = includeUnmapped ? rows.filter((item) => !item.mapped) : rows;
        if (!filtered.length) return '<div class="summary-breakdown-loading">' + (includeUnmapped ? 'Semua produk GMV Max Auto sudah memiliki HPP.' : 'Belum ada data GMV Max Auto.') + '</div>';
        const body = filtered.map((item, index) => {
            const profit = item.profit_after_ads === null
                ? Number(item.net_revenue || 0) - (Number(item.spend || 0) * 1.11)
                : Number(item.profit_after_ads);
            const profitText = profit < 0 ? '−' + fmt(Math.abs(profit)) : fmt(profit);
            const hppText = item.hpp_total !== null && Number(item.hpp_total || 0) > 0 ? '−' + fmt(item.hpp_total) : '—';
            const name = item.item_name || 'Produk GMV Max';
            if (includeUnmapped) {
                return '<tr><td>' + (index + 1) + '</td><td><div class="summary-breakdown-name" title="' + esc(name) + '">' + esc(name) + '</div><div class="summary-breakdown-muted">' + esc(item.item_sku || ('Item ID ' + item.channel_item_id)) + '</div></td><td class="text-end">' + Number(item.orders || 0).toLocaleString('id-ID') + '</td><td class="text-end">' + fmt(item.gmv) + '</td><td class="text-end" title="Setelah PPN · Sebelum PPN"><div class="summary-breakdown-cost">−' + fmt(Number(item.spend || 0) * 1.11) + '</div><div class="summary-breakdown-muted">−' + fmt(item.spend) + '</div></td><td class="text-center"><span class="summary-breakdown-status">HPP belum ada</span></td></tr>';
            }
            return '<tr><td>' + (index + 1) + '</td><td><div class="summary-breakdown-name" title="' + esc(name) + '">' + esc(name) + '</div><div class="summary-breakdown-muted">' + esc(item.item_sku || ('Item ID ' + item.channel_item_id)) + '</div></td><td class="text-end">' + Number(item.orders || 0).toLocaleString('id-ID') + '</td><td class="text-end" title="Gross · AOV · Net"><div>' + fmt(item.gmv) + '</div><div class="summary-breakdown-muted">' + (Number(item.orders || 0) > 0 ? fmt(Number(item.gmv || 0) / Number(item.orders || 0)) : '—') + '</div><div class="summary-breakdown-net">' + fmt(item.net_revenue) + '</div></td><td class="text-end" title="Setelah PPN · Sebelum PPN"><div class="summary-breakdown-cost">−' + fmt(Number(item.spend || 0) * 1.11) + '</div><div class="summary-breakdown-muted">−' + fmt(item.spend) + '</div></td><td class="text-end" title="Total HPP · HPP per pcs"><div>' + hppText + '</div><div class="summary-breakdown-muted">' + (item.mapped ? fmt(item.unit_cogs) : '—') + '</div></td><td class="text-end summary-breakdown-profit ' + (profit >= 0 ? 'is-positive' : 'is-negative') + '">' + profitText + '</td></tr>';
        }).join('');
        const heading = includeUnmapped ? 'GMV Max Auto belum mapping' : 'GMV Max Auto';
        const colspan = includeUnmapped ? 6 : 7;
        const header = includeUnmapped ? '<th>#</th><th>Produk / SKU</th><th class="text-end">Pesanan</th><th class="text-end">Omzet</th><th class="text-end">Iklan</th><th class="text-center">Status</th>' : '<th>#</th><th>Produk / SKU</th><th class="text-end">Pesanan</th><th class="text-end">Omzet</th><th class="text-end">Iklan</th><th class="text-end">HPP</th><th class="text-end">Laba Bersih</th>';
        return '<div class="summary-breakdown-muted" style="margin:.35rem 0 .3rem;font-size:.65rem;font-weight:750;">' + heading + '</div><div class="table-responsive summary-breakdown-table-wrap"><table class="dpanel-table dpanel-table-sm w-100 summary-breakdown-table"><thead><tr>' + header + '</tr></thead><tbody>' + body + '</tbody></table></div>';
    }

    async function loadAuto() {
        if (loaded || !storeIds.length) {
            if (!storeIds.length) {
                autoBody.innerHTML = '<div class="summary-breakdown-loading">Belum ada data GMV Max Auto.</div>';
                unmappedBody.innerHTML = '';
            }
            return;
        }
        loaded = true;
        autoBody.textContent = 'Memuat GMV Max Auto…';
        unmappedBody.textContent = 'Memuat produk belum mapping…';
        try {
            const payloads = await Promise.all(storeIds.map(async (storeId) => {
                const response = await fetch(endpoint + '/' + encodeURIComponent(storeId) + '?date_from=' + encodeURIComponent(fromDate) + '&date_to=' + encodeURIComponent(toDate) + '&compare_mode=' + encodeURIComponent(compareMode), { headers: { Accept: 'application/json' } });
                const payload = await response.json();
                if (!response.ok) throw new Error(payload.message || 'Gagal memuat GMV Max Auto.');
                return payload.data || [];
            }));
            const rows = payloads.flat();
            autoBody.innerHTML = gmsTable(rows, false);
            unmappedBody.innerHTML = gmsTable(rows, true);
        } catch (error) {
            autoBody.innerHTML = '<div class="summary-breakdown-loading" style="color:#b91c1c;">' + esc(error.message) + '</div>';
            unmappedBody.textContent = '';
        }
    }

    tabs.forEach((tab) => tab.addEventListener('click', () => {
        const view = tab.dataset.summaryView;
        tabs.forEach((item) => item.classList.toggle('is-active', item === tab));
        panels.forEach((panel) => panel.classList.toggle('is-active', panel.dataset.summaryPanel === view));
        if (view === 'auto' || view === 'unmapped') loadAuto();
    }));
})();
</script>
