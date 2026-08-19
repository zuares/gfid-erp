@php
    // GMV Max Auto pada dashboard disimpan sebagai agregat campaign GMS.
    // Gunakan baris parent campaign yang sudah tersedia, tanpa memuat ulang
    // performa item-level agar tab Product tetap ringan.
    $gmsChannelItemIds = collect($campaigns ?? [])
        ->filter(fn ($campaign) => str_starts_with(strtoupper((string) ($campaign->channel_campaign_id ?? '')), 'GMS-'))
        ->pluck('channel_item_id')
        ->filter()
        ->map(fn ($id) => (string) $id)
        ->all();

    $gmsProductRows = collect($campaigns ?? [])
        ->filter(fn ($campaign) => str_starts_with(strtoupper((string) ($campaign->channel_campaign_id ?? '')), 'GMS-'))
        ->filter(fn ($campaign) => (float) ($campaign->spend ?? 0) > 0 || (float) ($campaign->gmv ?? 0) > 0)
        ->map(function ($campaign) {
            $row = clone $campaign;
            $reco = is_array($campaign->reco ?? null) ? $campaign->reco : [];
            $recoLabel = $reco['label'] ?? (is_string($campaign->reco ?? null) ? $campaign->reco : 'Review');
            $recoClass = $reco['class'] ?? null;
            $row->is_gms = true;
            $row->has_gms = true;
            $row->item_name = $campaign->campaign_name ?: 'GMV Max Auto';
            $row->item_sku = 'Campaign ' . ($campaign->channel_campaign_id ?: 'GMS');
            $row->item_category = 'GMV Max Auto';
            $row->image_url = null;
            $row->stock_total = 0;
            $row->ctr = ($campaign->impressions ?? 0) > 0
                ? (($campaign->clicks ?? 0) / $campaign->impressions) * 100
                : 0;
            $row->cvr = ($campaign->clicks ?? 0) > 0
                ? (($campaign->orders ?? 0) / $campaign->clicks) * 100
                : 0;
            $row->gross_profit = $campaign->profit_after_ads !== null && $campaign->spend > 0
                ? (float) $campaign->profit_after_ads + ((float) $campaign->spend * 1.11)
                : null;
            $row->poas = $campaign->profit_after_ads !== null && $campaign->spend > 0
                ? (float) $campaign->profit_after_ads / ((float) $campaign->spend * 1.11)
                : null;
            $row->classification = $recoLabel;
            $row->class_color = match ($recoClass) {
                'reco-scale' => 'success',
                'reco-ok' => 'info',
                'reco-warn' => 'warning',
                'reco-stop' => 'danger',
                default => 'secondary',
            };

            return $row;
        });

    $productSourceRows = $itemPerformance
        ->reject(function ($row) use ($gmsChannelItemIds) {
            $itemId = (string) ($row->channel_item_id ?? '');
            return !empty($row->has_gms)
                || str_starts_with(strtoupper($itemId), 'GMS-')
                || in_array($itemId, $gmsChannelItemIds, true);
        })
        ->concat($gmsProductRows)
        ->sortByDesc('spend')
        ->values();

    $totalActiveProducts = $productSourceRows->count();
    $totalStock = $productSourceRows->sum('stock_total');
    $knownProfitItems = $productSourceRows->filter(fn ($r) => $r->profit_after_ads !== null);
    $totalProfit = $knownProfitItems->sum('profit_after_ads');
    $totalGrossProfit = $knownProfitItems->sum('gross_profit');
    $unknownProfitItems = $productSourceRows->count() - $knownProfitItems->count();
    $totalAdSpend = $productSourceRows->sum('spend');
    $knownAdSpend = $knownProfitItems->sum('spend');
    $totalOrders = $productSourceRows->sum('orders');
    $totalGmv = $productSourceRows->sum('gmv');
    $productRows = $productSourceRows->map(function ($row) {
        $isGms = !empty($row->has_gms) || !empty($row->is_gms);
        $isProblematic = (float) ($row->unit_cogs ?? 0) <= 0;
        return [
            'row' => $row,
            'name' => $row->item_name ?: 'Produk tanpa nama',
            'category' => $row->item_category ?: 'Uncategorized',
            'is_gms' => $isGms,
            'views' => $isGms
                ? ['gms']
                : array_values(array_unique(array_merge(['category', 'roas'], $isProblematic ? ['unmapped'] : []))),
        ];
    })->values();
    $productRegularRows = $productRows->filter(fn ($row) => !$row['is_gms']);
    $productCategoryProductRows = $productRegularRows->groupBy('category');
    $productCategoryRows = $productCategoryProductRows->map(function ($group, $category) {
        $knownProfit = $group->filter(fn ($entry) => $entry['row']->profit_after_ads !== null);
        $spend = $group->sum(fn ($entry) => (float) $entry['row']->spend);
        $impressions = $group->sum(fn ($entry) => (int) ($entry['row']->impressions ?? 0));
        $clicks = $group->sum(fn ($entry) => (int) ($entry['row']->clicks ?? 0));
        $orders = $group->sum(fn ($entry) => (int) ($entry['row']->orders ?? 0));
        $gmv = $group->sum(fn ($entry) => (float) $entry['row']->gmv);
        $profit = $knownProfit->sum(fn ($entry) => (float) ($entry['row']->profit_after_ads ?? 0));
        return [
            'category' => $category,
            'products' => $group->count(),
            'stock' => $group->sum(fn ($entry) => (int) ($entry['row']->stock_total ?? 0)),
            'spend' => $spend,
            'orders' => $orders,
            'ctr' => $impressions > 0 ? ($clicks / $impressions) * 100 : 0,
            'cvr' => $clicks > 0 ? ($orders / $clicks) * 100 : 0,
            'gmv' => $gmv,
            'gross_profit' => $knownProfit->sum(fn ($entry) => (float) ($entry['row']->gross_profit ?? 0)),
            'profit' => $profit,
            'profit_unknown' => $group->count() - $knownProfit->count(),
            'poas' => $spend > 0 ? $profit / ($spend * 1.11) : 0,
        ];
    })->sortByDesc('spend')->values();
    $productSegmentRows = [
        'category' => $productRegularRows,
        'roas' => $productRows->filter(fn ($row) => in_array('roas', $row['views'], true)),
        'gms' => $productRows->filter(fn ($row) => in_array('gms', $row['views'], true)),
        'unmapped' => $productRows->filter(fn ($row) => in_array('unmapped', $row['views'], true)),
    ];
    $productSegmentKpis = collect($productSegmentRows)->map(function ($rows) {
        $knownProfit = $rows->filter(fn ($entry) => $entry['row']->profit_after_ads !== null);
        $spend = $rows->sum(fn ($entry) => (float) $entry['row']->spend);
        $gmv = $rows->sum(fn ($entry) => (float) $entry['row']->gmv);
        return [
            'products' => $rows->count(),
            'stock' => $rows->sum(fn ($entry) => (int) ($entry['row']->stock_total ?? 0)),
            'spend' => $spend,
            'orders' => $rows->sum(fn ($entry) => (int) ($entry['row']->orders ?? 0)),
            'gmv' => $gmv,
            'roas' => $spend > 0 ? $gmv / $spend : 0,
            'profit' => $knownProfit->sum(fn ($entry) => (float) ($entry['row']->profit_after_ads ?? 0)),
            'profit_unknown' => $rows->count() - $knownProfit->count(),
        ];
    })->all();
    $productViewCounts = [
        'category' => $productCategoryRows->count(),
        'roas' => $productSegmentRows['roas']->count(),
        'gms' => $productSegmentRows['gms']->count(),
        'unmapped' => $productSegmentRows['unmapped']->count(),
    ];
    $productInitialKpi = $productSegmentKpis['category'];

    $scatterDataJson = json_encode($productSourceRows->map(function($r) {
        return [
            'x' => $r->spend,
            'y' => $r->profit_after_ads,
            'name' => $r->item_name,
            'reco' => $r->classification,
            'r' => max(4, min(15, $r->orders * 2)) // Bubble size based on orders
        ];
    })->values());
@endphp

<div class="dash-sec"><i class="bi bi-robot"></i> Insight Produk</div>
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="dpanel p-3 h-100">
            <div class="text-muted small fw-bold text-uppercase mb-1">Total Produk Iklan</div>
            <div class="fs-4 fw-bolder text-dark">{{ number_format($totalActiveProducts, 0, ',', '.') }}</div>
            <div class="small text-muted mt-1"><i class="bi bi-box"></i> SKU unik</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dpanel p-3 h-100">
            <div class="text-muted small fw-bold text-uppercase mb-1">Stok Tersedia</div>
            <div class="fs-4 fw-bolder text-dark">{{ number_format($totalStock, 0, ',', '.') }}</div>
            <div class="small text-muted mt-1">Total stok tercatat</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dpanel p-3 h-100">
            <div class="text-muted small fw-bold text-uppercase mb-1">Gross Profit (Iklan)</div>
            <div class="fs-4 fw-bolder text-dark">Rp {{ number_format($totalGrossProfit, 0, ',', '.') }}</div>
            <div class="small text-muted mt-1">Terhitung dari {{ $knownProfitItems->count() }} produk ber-HPP</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dpanel p-3 h-100">
            <div class="text-muted small fw-bold text-uppercase mb-1" data-bs-toggle="tooltip" title="Profit hanya dijumlahkan untuk produk yang memiliki HPP">Net Profit Terhitung</div>
            <div class="fs-4 fw-bolder {{ $unknownProfitItems > 0 ? 'text-warning' : ($totalProfit >= 0 ? 'text-success' : 'text-danger') }}">
                Rp {{ number_format($totalProfit, 0, ',', '.') }}
            </div>
            <div class="small text-muted mt-1">POAS: {{ number_format($knownAdSpend > 0 ? $totalProfit / ($knownAdSpend * 1.11) : 0, 2) }}x · {{ $unknownProfitItems }} belum ada HPP</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Scatter Chart (Spend vs Profit) -->
    <div class="col-12 col-xl-12">
        <div class="dpanel p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-graph-up text-primary me-2"></i>Scatter Produk: Spend vs Est. Profit</h6>
                <span class="badge bg-light text-dark border">Hero Product = Profitabilitas Tinggi & Budget Cukup</span>
            </div>
            <div style="height: 300px; position: relative;">
                <canvas id="productScatterChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="dpanel p-0 overflow-hidden">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
        <h6 class="mb-0 fw-bold"><i class="bi bi-box-seam text-primary me-2"></i>Semua Produk Diiklankan</h6>
        <div>
            <span class="badge bg-success">Hero Product</span>
            <span class="badge bg-primary">Profit Driver</span>
            <span class="badge bg-warning text-dark">Traffic Driver</span>
            <span class="badge bg-danger">Loss Maker</span>
        </div>
    </div>
    <div style="display:flex; gap:.35rem; margin:.75rem .75rem .75rem; overflow-x:auto;" role="tablist" aria-label="Jenis tampilan produk">
        <button type="button" id="btnProductCategory" class="btn fw-bold" onclick="__productPerformanceView('category')" data-product-count="{{ $productViewCounts['category'] }}" aria-selected="false" style="border-radius:999px; font-size:.72rem; padding:.38rem .95rem; color:var(--dsh-muted); border:1px solid var(--dsh-border); background:transparent; white-space:nowrap;">Per Kategori</button>
        <button type="button" id="btnProductRoas" class="btn fw-bold" onclick="__productPerformanceView('roas')" data-product-count="{{ $productViewCounts['roas'] }}" aria-selected="false" style="border-radius:999px; font-size:.72rem; padding:.38rem .95rem; color:var(--dsh-muted); border:1px solid var(--dsh-border); background:transparent; white-space:nowrap;">GMV Max ROAS</button>
        <button type="button" id="btnProductGms" class="btn fw-bold" onclick="__productPerformanceView('gms')" data-product-count="{{ $productViewCounts['gms'] }}" aria-selected="false" style="border-radius:999px; font-size:.72rem; padding:.38rem .95rem; color:var(--dsh-muted); border:1px solid var(--dsh-border); background:transparent; white-space:nowrap;">GMV Max Auto</button>
        <button type="button" id="btnProductUnmapped" class="btn fw-bold" onclick="__productPerformanceView('unmapped')" data-product-count="{{ $productViewCounts['unmapped'] }}" aria-selected="false" style="border-radius:999px; font-size:.72rem; padding:.38rem .95rem; color:var(--dsh-muted); border:1px solid var(--dsh-border); background:transparent; white-space:nowrap;">Produk Bermasalah</button>
    </div>
    <div class="px-3 pb-2">
        <div class="ads-kpi-grid mb-2">
            <div class="dpanel ads-kpi kpi-revenue">
                <div class="ads-kpi-label"><i class="bi bi-box-seam"></i> Produk</div>
                <div class="ads-kpi-value" data-product-kpi-value="products">{{ number_format($productInitialKpi['products'], 0, ',', '.') }}</div>
                <div class="ads-kpi-sub">sesuai subtab aktif</div>
            </div>
            <div class="dpanel ads-kpi kpi-spend">
                <div class="ads-kpi-label"><i class="bi bi-wallet2"></i> Belanja Iklan</div>
                <div class="ads-kpi-value" data-product-kpi-value="spend">Rp {{ number_format($productInitialKpi['spend'], 0, ',', '.') }}</div>
                <div class="ads-kpi-sub"><span data-product-kpi-value="orders">{{ number_format($productInitialKpi['orders'], 0, ',', '.') }}</span> order</div>
            </div>
            <div class="dpanel ads-kpi">
                <div class="ads-kpi-label"><i class="bi bi-graph-up-arrow"></i> GMV</div>
                <div class="ads-kpi-value" data-product-kpi-value="gmv">Rp {{ number_format($productInitialKpi['gmv'], 0, ',', '.') }}</div>
                <div class="ads-kpi-sub">ROAS <span data-product-kpi-value="roas">{{ number_format($productInitialKpi['roas'], 2, ',', '.') }}x</span></div>
            </div>
            <div class="dpanel ads-kpi kpi-profit">
                <div class="ads-kpi-label"><i class="bi bi-cash-stack"></i> Net Profit</div>
                <div class="ads-kpi-value" data-product-kpi-value="profit">Rp {{ number_format($productInitialKpi['profit'], 0, ',', '.') }}</div>
                <div class="ads-kpi-sub"><span data-product-kpi-value="profit_unknown">{{ $productInitialKpi['profit_unknown'] }}</span> belum ada HPP</div>
            </div>
        </div>
    </div>
    <style>
        .product-category-row { cursor:pointer; }
        .product-category-row:hover { background:rgba(37,99,235,.04); }
        .product-caret { transition:transform .15s; }
        .product-category-row.open .product-caret { transform:rotate(90deg); }
    </style>
    <div class="table-responsive" style="max-height: 600px;">
        <table class="table table-hover align-middle mb-0 text-nowrap" style="font-size: 0.85rem;" id="productsPerformanceTable">
            <thead class="table-light sticky-top" style="z-index: 2;">
                <tr>
                    <th>Produk / SKU</th>
                    <th>Klasifikasi</th>
                    <th class="text-end">Stok</th>
                    <th class="text-end">Spend</th>
                    <th class="text-end">Pesanan</th>
                    <th class="text-end" data-bs-toggle="tooltip" title="Click-Through Rate">CTR</th>
                    <th class="text-end" data-bs-toggle="tooltip" title="Conversion Rate">CVR</th>
                    <th class="text-end">GMV</th>
                    <th class="text-end">Gross Profit</th>
                    <th class="text-end" data-bs-toggle="tooltip" title="Net Profit = Gross Profit - Spend">Net Profit</th>
                    <th class="text-end" data-bs-toggle="tooltip" title="Profit on Ad Spend">POAS</th>
                </tr>
            </thead>
            <tbody>
                @foreach($productCategoryRows as $categoryRow)
                    <tr class="product-category-row" data-product-views="category" onclick="productToggleCategory(this)">
                        <td><div class="d-flex align-items-center gap-1"><i class="bi bi-chevron-right product-caret text-muted" style="font-size:.7rem;"></i><div><div class="fw-bold">{{ $categoryRow['category'] }}</div><div class="text-muted small">{{ number_format($categoryRow['products'], 0, ',', '.') }} produk</div></div></div></td>
                        <td><span class="badge bg-light text-dark border">Ringkasan</span></td>
                        <td class="text-end">{{ number_format($categoryRow['stock'], 0, ',', '.') }}</td>
                        <td class="text-end fw-bold text-danger">Rp {{ number_format($categoryRow['spend'], 0, ',', '.') }}</td>
                        <td class="text-end fw-bold">{{ number_format($categoryRow['orders'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($categoryRow['ctr'], 2) }}%</td>
                        <td class="text-end">{{ number_format($categoryRow['cvr'], 2) }}%</td>
                        <td class="text-end text-success fw-bold">Rp {{ number_format($categoryRow['gmv'], 0, ',', '.') }}</td>
                        <td class="text-end text-muted">Rp {{ number_format($categoryRow['gross_profit'], 0, ',', '.') }}</td>
                        <td class="text-end fw-bold {{ $categoryRow['profit'] >= 0 ? 'text-success' : 'text-danger' }}">{{ $categoryRow['profit_unknown'] > 0 ? 'N/A' : 'Rp ' . number_format($categoryRow['profit'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($categoryRow['poas'], 2) }}x</td>
                    </tr>
                    <tr class="product-category-detail" style="display:none;">
                        <td colspan="11" class="bg-light">
                            <div class="text-muted fw-bold mb-1" style="font-size:.66rem;">Daftar produk dalam kategori {{ $categoryRow['category'] }}</div>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0" style="font-size:.72rem;">
                                    <thead><tr><th>Produk / SKU</th><th>Klasifikasi</th><th class="text-end">Stok</th><th class="text-end">Spend</th><th class="text-end">Orders</th><th class="text-end">GMV</th><th class="text-end">Net Profit</th><th class="text-end">POAS</th></tr></thead>
                                    <tbody>
                                        @foreach($productCategoryProductRows->get($categoryRow['category'], collect()) as $productEntry)
                                            @php $detailProduct = $productEntry['row']; @endphp
                                            <tr>
                                                <td><div class="fw-bold text-truncate" style="max-width:260px;" title="{{ $detailProduct->item_name }}">{{ $detailProduct->item_name }}</div><div class="text-muted" style="font-size:.62rem;">{{ $detailProduct->item_sku }}</div></td>
                                                <td><span class="badge bg-{{ $detailProduct->class_color }}">{{ $detailProduct->classification }}</span></td>
                                                <td class="text-end">{{ number_format($detailProduct->stock_total ?? 0, 0, ',', '.') }}</td>
                                                <td class="text-end">Rp {{ number_format($detailProduct->spend, 0, ',', '.') }}</td>
                                                <td class="text-end">{{ number_format($detailProduct->orders, 0, ',', '.') }}</td>
                                                <td class="text-end text-success">Rp {{ number_format($detailProduct->gmv, 0, ',', '.') }}</td>
                                                <td class="text-end">{{ $detailProduct->profit_after_ads === null ? 'N/A' : 'Rp ' . number_format($detailProduct->profit_after_ads, 0, ',', '.') }}</td>
                                                <td class="text-end">{{ $detailProduct->poas === null ? 'N/A' : number_format($detailProduct->poas, 2) . 'x' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                @endforeach
                @foreach($productRows as $productEntry)
                    @php $row = $productEntry['row']; @endphp
                    <tr class="product-performance-row" data-product-views="{{ implode(' ', $productEntry['views']) }}">
                        <td>
                            <div class="d-flex align-items-center">
                                @if($row->image_url)
                                    <img src="{{ $row->image_url }}" alt="img" class="rounded me-2" style="width: 32px; height: 32px; object-fit: cover; border: 1px solid #ddd;">
                                @else
                                    <div class="rounded me-2 bg-light d-flex align-items-center justify-content-center text-muted" style="width: 32px; height: 32px; border: 1px solid #ddd;">
                                        <i class="bi bi-image"></i>
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-bold text-truncate" style="max-width: 250px;" title="{{ $row->item_name }}">
                                        {{ $row->item_name }}
                                    </div>
                                    <div class="text-muted small">
                                        {{ $row->item_sku }} <span class="badge bg-light text-dark ms-1">{{ $row->item_category }}</span>
                                    </div>
                                    <div class="text-muted" style="font-size:.65rem;">
                                        HPP/unit: {{ ($row->unit_cogs ?? 0) > 0 ? 'Rp ' . number_format($row->unit_cogs, 0, ',', '.') : 'belum tersedia' }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-{{ $row->class_color }}">
                                {{ $row->classification }}
                            </span>
                        </td>
                        <td class="text-end">
                            @if(($row->stock_total ?? 0) < 5)
                                <span class="text-danger fw-bold">{{ $row->stock_total ?? 0 }}</span>
                            @else
                                {{ $row->stock_total ?? 0 }}
                            @endif
                        </td>
                        <td class="text-end fw-bold text-danger">Rp {{ number_format($row->spend, 0, ',', '.') }}</td>
                        <td class="text-end fw-bold">{{ number_format($row->orders, 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($row->ctr, 2) }}%</td>
                        <td class="text-end">{{ number_format($row->cvr, 2) }}%</td>
                        <td class="text-end text-success fw-bold">Rp {{ number_format($row->gmv, 0, ',', '.') }}</td>
                        <td class="text-end text-muted">{{ $row->gross_profit === null ? 'N/A' : 'Rp ' . number_format($row->gross_profit, 0, ',', '.') }}</td>
                        <td class="text-end fw-bold {{ $row->profit_after_ads === null ? 'text-muted' : ($row->profit_after_ads >= 0 ? 'text-success' : 'text-danger') }}">
                            {{ $row->profit_after_ads === null ? 'N/A' : 'Rp ' . number_format($row->profit_after_ads, 0, ',', '.') }}
                        </td>
                        <td class="text-end">{{ $row->poas === null ? 'N/A' : number_format($row->poas, 2) . 'x' }}</td>
                    </tr>
                @endforeach
                @if($productRows->isEmpty())
                    <tr class="product-no-data">
                        <td colspan="11" class="text-center py-4 text-muted">Belum ada data performa produk yang memadai untuk tanggal ini.</td>
                    </tr>
                @endif
                <tr class="product-view-empty" style="display:none;">
                    <td colspan="11" class="text-center py-4 text-muted">Belum ada data untuk kategori ini.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
const productKpiData = {!! json_encode($productSegmentKpis) !!};

function productToggleCategory(row) {
    row.classList.toggle('open');
    const detail = row.nextElementSibling;
    if (detail && detail.classList.contains('product-category-detail')) {
        detail.style.display = (detail.style.display === 'none' || !detail.style.display) ? 'table-row' : 'none';
    }
}

function productFormatNumber(value, decimals) {
    return Number(value || 0).toLocaleString('id-ID', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });
}

function productUpdateKpis(view) {
    const kpi = productKpiData[view] || productKpiData.category || {};
    const setValue = function(key, value) {
        document.querySelectorAll('[data-product-kpi-value="' + key + '"]').forEach(function(el) {
            el.textContent = value;
        });
    };

    setValue('products', productFormatNumber(kpi.products, 0));
    setValue('spend', 'Rp ' + productFormatNumber(kpi.spend, 0));
    setValue('orders', productFormatNumber(kpi.orders, 0));
    setValue('gmv', 'Rp ' + productFormatNumber(kpi.gmv, 0));
    setValue('roas', productFormatNumber(kpi.roas, 2) + 'x');
    setValue('profit', 'Rp ' + productFormatNumber(kpi.profit, 0));
    setValue('profit_unknown', productFormatNumber(kpi.profit_unknown, 0));
}

window.__productPerformanceView = function(view) {
    const rows = Array.from(document.querySelectorAll('.product-performance-row, .product-category-row'));
    const categoryDetails = Array.from(document.querySelectorAll('.product-category-detail'));
    const emptyState = document.querySelector('.product-view-empty');
    const noDataState = document.querySelector('.product-no-data');
    let visibleCount = 0;

    productUpdateKpis(view);
    categoryDetails.forEach(function(detail) { detail.style.display = 'none'; });

    rows.forEach(function(row) {
        const isCategoryView = view === 'category';
        const visible = isCategoryView
            ? row.classList.contains('product-category-row')
            : row.classList.contains('product-performance-row')
                && (row.dataset.productViews || '').split(/\s+/).includes(view);
        row.style.display = visible ? '' : 'none';
        row.classList.remove('open');
        if (visible) visibleCount++;
    });

    if (noDataState) noDataState.style.display = rows.length ? 'none' : 'table-row';
    if (emptyState) emptyState.style.display = rows.length && !visibleCount ? 'table-row' : 'none';

    const buttonOn = 'border-radius:999px; font-size:.72rem; padding:.38rem .95rem; background:var(--dsh-accent); color:#fff; border:1px solid var(--dsh-accent); white-space:nowrap;';
    const buttonOff = 'border-radius:999px; font-size:.72rem; padding:.38rem .95rem; color:var(--dsh-muted); border:1px solid var(--dsh-border); background:transparent; white-space:nowrap;';
    const buttons = {
        category: document.getElementById('btnProductCategory'),
        roas: document.getElementById('btnProductRoas'),
        gms: document.getElementById('btnProductGms'),
        unmapped: document.getElementById('btnProductUnmapped'),
    };
    Object.entries(buttons).forEach(function([key, button]) {
        if (!button) return;
        const active = key === view;
        button.style.cssText = active ? buttonOn : buttonOff;
        button.setAttribute('aria-selected', active ? 'true' : 'false');
    });
    try { localStorage.setItem('adsProductView', view); } catch (e) {}
};

document.addEventListener('DOMContentLoaded', function() {
    let savedView = null;
    try { savedView = localStorage.getItem('adsProductView'); } catch (e) {}
    const validViews = ['category', 'roas', 'gms', 'unmapped'];
    window.__productPerformanceView(validViews.includes(savedView) ? savedView : 'category');
});

document.addEventListener('DOMContentLoaded', function() {
    const pScatterData = {!! $scatterDataJson !!};

    const pctx = document.getElementById('productScatterChart');
    if (!pctx) return;

    const classColorMap = {
        'Hero Product': 'rgba(25, 135, 84, 0.7)',
        'Profit Driver': 'rgba(13, 110, 253, 0.7)',
        'Volume Driver': 'rgba(13, 202, 240, 0.7)',
        'Traffic Driver (No Conv)': 'rgba(255, 193, 7, 0.7)',
        'Loss Maker': 'rgba(220, 53, 69, 0.7)',
        'Stock Risk': 'rgba(220, 53, 69, 0.9)',
        'Review': 'rgba(108, 117, 125, 0.7)',
        'Low Performer': 'rgba(173, 181, 189, 0.7)'
    };

    const pDatasets = Object.keys(classColorMap).map(cls => {
        return {
            label: cls,
            data: pScatterData.filter(d => d.reco === cls),
            backgroundColor: classColorMap[cls],
            borderColor: classColorMap[cls].replace('0.7', '1').replace('0.9', '1'),
            borderWidth: 1
        };
    }).filter(ds => ds.data.length > 0);

    new Chart(pctx, {
        type: 'bubble',
        data: {
            datasets: pDatasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const data = context.raw;
                            return [
                                data.name,
                                'Spend: Rp ' + data.x.toLocaleString('id-ID'),
                                'Net Profit: Rp ' + data.y.toLocaleString('id-ID')
                            ];
                        }
                    }
                },
                legend: {
                    display: true,
                    position: 'bottom'
                }
            },
            scales: {
                x: {
                    title: { display: true, text: 'Ad Spend (Rp)' },
                    ticks: { callback: function(value) { return 'Rp ' + (value/1000).toLocaleString('id-ID') + 'k'; } }
                },
                y: {
                    title: { display: true, text: 'Est. Net Profit (Rp)' },
                    ticks: { callback: function(value) { return 'Rp ' + (value/1000).toLocaleString('id-ID') + 'k'; } },
                    grid: { color: function(context) { return context.tick.value === 0 ? '#ff0000' : 'rgba(0,0,0,0.1)'; } }
                }
            }
        }
    });
});
</script>
