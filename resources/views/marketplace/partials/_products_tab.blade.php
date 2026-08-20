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
            $row->variant_stock_empty = false;
            $row->empty_variant_skus = [];
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
        ->map(function ($row) {
            $row->spend_ppn = round((float) ($row->spend ?? 0) * 1.11, 2);
            return $row;
        })
        ->sortByDesc('spend_ppn')
        ->values();

    $totalActiveProducts = $productSourceRows->count();
    $totalStock = $productSourceRows->sum('stock_total');
    $knownProfitItems = $productSourceRows->filter(fn ($r) => $r->profit_after_ads !== null);
    $totalProfit = $knownProfitItems->sum('profit_after_ads');
    $totalGrossProfit = $knownProfitItems->sum('gross_profit');
    $unknownProfitItems = $productSourceRows->count() - $knownProfitItems->count();
    $totalAdSpend = $productSourceRows->sum('spend_ppn');
    $knownAdSpend = $knownProfitItems->sum('spend_ppn');
    $totalOrders = $productSourceRows->sum('orders');
    $totalGmv = $productSourceRows->sum('gmv');
    $productOverallKpi = ($kpi ?? [])['current'] ?? null;
    $productOverallOrders = $productOverallKpi !== null ? (int) ($productOverallKpi->orders ?? 0) : $totalOrders;
    $productOverallGmv = $productOverallKpi !== null ? (float) ($productOverallKpi->gmv ?? 0) : $totalGmv;
    $productOverallSpendAfterTax = $productOverallKpi !== null
        ? (float) ($productOverallKpi->spend ?? 0) * 1.11
        : $totalAdSpend;
    $productOverallProfit = $productOverallKpi !== null
        ? (float) ($productOverallKpi->net_profit ?? 0)
        : $totalProfit;
    $productPeriodDays = max(1, (int) (\Carbon\Carbon::parse($dateFrom ?? now()->toDateString())->diffInDays(\Carbon\Carbon::parse($dateTo ?? now()->toDateString())) + 1));
    $productDayDivisor = max(1, (int) $productPeriodDays);
    $productStockRisk = function ($stock, $orders, $isGms = false) use ($productDayDivisor) {
        if ($isGms) {
            return ['label' => 'Agregat Campaign', 'class' => 'secondary', 'icon' => 'bi-collection'];
        }
        $stock = (int) $stock;
        $velocity = (int) $orders / $productDayDivisor;
        if ($stock <= 0 && $orders > 0) {
            return ['label' => 'Stok Habis', 'class' => 'danger', 'icon' => 'bi-box-seam'];
        }
        if ($velocity <= 0) {
            return $stock > 0
                ? ['label' => 'Tidak Ada Penjualan', 'class' => 'secondary', 'icon' => 'bi-pause-circle']
                : ['label' => 'Stok Kosong', 'class' => 'danger', 'icon' => 'bi-box'];
        }
        $daysCoverage = $stock / $velocity;
        if ($daysCoverage < 7) {
            return ['label' => 'Stok Kritis', 'class' => 'danger', 'icon' => 'bi-exclamation-triangle'];
        }
        if ($daysCoverage < 14) {
            return ['label' => 'Stok Menipis', 'class' => 'warning', 'icon' => 'bi-hourglass-split'];
        }
        return ['label' => 'Stok Aman', 'class' => 'success', 'icon' => 'bi-check-circle'];
    };
    $productRows = $productSourceRows->map(function ($row) {
        $isGms = !empty($row->has_gms) || !empty($row->is_gms);
        $isProblematic = (float) ($row->unit_cogs ?? 0) <= 0;
        return [
            'row' => $row,
            'name' => $row->item_name ?: 'Produk tanpa nama',
            'category' => $row->item_category ?: 'Uncategorized',
            'is_gms' => $isGms,
            'views' => $isGms
                ? ['category', 'gms']
                : array_values(array_unique(array_merge(['category', 'roas'], $isProblematic ? ['unmapped'] : []))),
        ];
    })->values()->map(function ($entry) use ($productStockRisk, $productDayDivisor) {
        $row = $entry['row'];
        $orders = (int) ($row->orders ?? 0);
        $stock = (int) ($row->stock_total ?? 0);
        $entry['aov'] = $orders > 0 ? (float) ($row->gmv ?? 0) / $orders : 0;
        $entry['velocity'] = $orders / $productDayDivisor;
        $entry['stock_days'] = $entry['velocity'] > 0 ? $stock / $entry['velocity'] : null;
        $entry['stock_risk'] = $productStockRisk($stock, $orders, !empty($entry['is_gms']));
        $entry['empty_variant_skus'] = collect($row->empty_variant_skus ?? [])
            ->filter(fn ($sku) => trim((string) $sku) !== '')
            ->map(fn ($sku) => trim((string) $sku))
            ->unique()
            ->values()
            ->all();
        $entry['variant_stock_empty'] = ! empty($entry['empty_variant_skus']);
        return $entry;
    })->values();
    $productCategoryProductRows = $productRows->groupBy('category');
    $productCategoryRows = $productCategoryProductRows->map(function ($group, $category) use ($productStockRisk) {
        $knownProfit = $group->filter(fn ($entry) => $entry['row']->profit_after_ads !== null);
        $spend = $group->sum(fn ($entry) => (float) ($entry['row']->spend_ppn ?? 0));
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
            'aov' => $orders > 0 ? $gmv / $orders : 0,
            'ctr' => $impressions > 0 ? ($clicks / $impressions) * 100 : 0,
            'cvr' => $clicks > 0 ? ($orders / $clicks) * 100 : 0,
            'gmv' => $gmv,
            'gross_profit' => $knownProfit->sum(fn ($entry) => (float) ($entry['row']->gross_profit ?? 0)),
            'profit' => $profit,
            'profit_unknown' => $group->count() - $knownProfit->count(),
            'variant_stock_empty_products' => $group->filter(fn ($entry) => $entry['variant_stock_empty'])->count(),
            'empty_variant_products' => $group
                ->filter(fn ($entry) => $entry['variant_stock_empty'])
                ->map(fn ($entry) => [
                    'product' => $entry['name'],
                    'variants' => $entry['empty_variant_skus'],
                ])
                ->values()
                ->all(),
            'poas' => $spend > 0 ? $profit / $spend : 0,
            'stock_risk' => $productStockRisk(
                $group->sum(fn ($entry) => (int) ($entry['row']->stock_total ?? 0)),
                $orders,
                $group->contains(fn ($entry) => !empty($entry['is_gms']))
            ),
        ];
    })->sortByDesc('spend')->values();
    $productSegmentRows = [
        'category' => $productRows,
        'roas' => $productRows->filter(fn ($row) => in_array('roas', $row['views'], true)),
        'gms' => $productRows->filter(fn ($row) => in_array('gms', $row['views'], true)),
        'unmapped' => $productRows->filter(fn ($row) => in_array('unmapped', $row['views'], true)),
    ];
    $productSegmentKpis = collect($productSegmentRows)->map(function ($rows) {
        $knownProfit = $rows->filter(fn ($entry) => $entry['row']->profit_after_ads !== null);
        $spend = $rows->sum(fn ($entry) => (float) ($entry['row']->spend_ppn ?? 0));
        $gmv = $rows->sum(fn ($entry) => (float) $entry['row']->gmv);
        return [
            'products' => $rows->count(),
            'stock' => $rows->sum(fn ($entry) => (int) ($entry['row']->stock_total ?? 0)),
            'spend' => $spend,
            'orders' => $rows->sum(fn ($entry) => (int) ($entry['row']->orders ?? 0)),
            'gmv' => $gmv,
            'aov' => $rows->sum(fn ($entry) => (int) ($entry['row']->orders ?? 0)) > 0
                ? $gmv / $rows->sum(fn ($entry) => (int) ($entry['row']->orders ?? 0))
                : 0,
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
    $criticalStockProducts = $productRows->filter(fn ($entry) => in_array($entry['stock_risk']['label'], ['Stok Habis', 'Stok Kritis'], true))->count();
    $productInitialKpi = $productSegmentKpis['category'];

    $productChartSegmentRows = [
        'category' => $productCategoryRows->map(fn ($row) => [
            'name' => $row['category'],
            'spend' => $row['spend'],
            'profit' => $row['profit'],
            'profit_available' => $row['profit_unknown'] === 0,
            'orders' => $row['orders'],
            'gmv' => $row['gmv'],
            'aov' => $row['aov'],
            'roas' => $row['spend'] > 0 ? $row['gmv'] / $row['spend'] : 0,
            'stock' => $row['stock'],
            'velocity' => $row['orders'] / $productDayDivisor,
            'classification' => 'Kategori',
            'stock_risk' => $row['stock_risk'],
        ])->values(),
        'roas' => $productSegmentRows['roas'],
        'gms' => $productSegmentRows['gms'],
        'unmapped' => $productSegmentRows['unmapped'],
    ];
    $productChartData = collect($productChartSegmentRows)->map(function ($rows) use ($productDayDivisor) {
        return collect($rows)->map(function ($entry) use ($productDayDivisor) {
            $isAggregate = is_array($entry) && !isset($entry['row']);
            $row = $isAggregate ? null : $entry['row'];
            return [
                'name' => $isAggregate ? $entry['name'] : ($row->item_name ?: 'Produk tanpa nama'),
                'x' => (float) ($isAggregate ? $entry['spend'] : ($row->spend_ppn ?? (($row->spend ?? 0) * 1.11))),
                'y' => $isAggregate ? (float) $entry['profit'] : (float) ($row->profit_after_ads ?? 0),
                'profit_available' => $isAggregate ? (bool) $entry['profit_available'] : $row->profit_after_ads !== null,
                'orders' => (int) ($isAggregate ? $entry['orders'] : ($row->orders ?? 0)),
                'gmv' => (float) ($isAggregate ? $entry['gmv'] : ($row->gmv ?? 0)),
                'aov' => (float) ($isAggregate ? $entry['aov'] : (($row->orders ?? 0) > 0 ? $row->gmv / $row->orders : 0)),
                'roas' => (float) ($isAggregate ? $entry['roas'] : (($row->spend_ppn ?? (($row->spend ?? 0) * 1.11)) > 0 ? $row->gmv / ($row->spend_ppn ?? (($row->spend ?? 0) * 1.11)) : 0)),
                'stock' => (int) ($isAggregate ? $entry['stock'] : ($row->stock_total ?? 0)),
                'velocity' => (float) ($isAggregate ? $entry['velocity'] : (($row->orders ?? 0) / $productDayDivisor)),
                'reco' => $isAggregate ? $entry['classification'] : ($row->classification ?? 'Review'),
                'stock_risk' => $isAggregate ? $entry['stock_risk'] : ($entry['stock_risk'] ?? ['label' => 'Review', 'class' => 'secondary']),
                'r' => max(4, min(15, ((int) ($isAggregate ? $entry['orders'] : ($row->orders ?? 0))) * 2)),
            ];
        })->values();
    })->all();
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
            <div class="text-muted small fw-bold text-uppercase mb-1">AOV Produk</div>
            <div class="fs-4 fw-bolder text-dark">Rp {{ number_format($productOverallOrders > 0 ? $productOverallGmv / $productOverallOrders : 0, 0, ',', '.') }}</div>
            <div class="small text-muted mt-1">{{ number_format($productOverallOrders, 0, ',', '.') }} orders</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dpanel p-3 h-100">
            <div class="text-muted small fw-bold text-uppercase mb-1" data-bs-toggle="tooltip" title="Profit hanya dijumlahkan untuk produk yang memiliki HPP">Net Profit Terhitung</div>
            <div class="fs-4 fw-bolder {{ $unknownProfitItems > 0 ? 'text-warning' : ($productOverallProfit >= 0 ? 'text-success' : 'text-danger') }}">
                Rp {{ number_format($productOverallProfit, 0, ',', '.') }}
            </div>
            <div class="small text-muted mt-1">POAS: {{ number_format($productOverallSpendAfterTax > 0 ? $productOverallProfit / $productOverallSpendAfterTax : 0, 2) }}x · {{ $unknownProfitItems }} belum ada HPP · {{ $criticalStockProducts }} stok kritis</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Scatter Chart (Spend vs Profit) -->
    <div class="col-12 col-xl-12">
        <div class="dpanel p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-graph-up text-primary me-2"></i>Product Matrix: Biaya Iklan vs Net Profit Terhitung</h6>
                <span id="productScatterHint" class="badge bg-light text-dark border">Subtab: Per Kategori</span>
            </div>
            <div style="height: 300px; position: relative;">
                <canvas id="productScatterChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="dpanel p-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <h6 class="mb-0 fw-bold"><i class="bi bi-boxes text-primary me-2"></i>Stock Risk vs Sales Velocity</h6>
                <span class="badge bg-light text-dark border" style="font-size:.6rem;">Periode {{ $productPeriodDays }} hari</span>
            </div>
            <div style="height:280px; position:relative;"><canvas id="productStockChart"></canvas></div>
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
                <div class="ads-kpi-sub">produk</div>
            </div>
            <div class="dpanel ads-kpi kpi-spend">
                <div class="ads-kpi-label"><i class="bi bi-wallet2"></i> Belanja Iklan</div>
                <div class="ads-kpi-value" data-product-kpi-value="spend">Rp {{ number_format($productInitialKpi['spend'], 0, ',', '.') }}</div>
                <div class="ads-kpi-sub"><span data-product-kpi-value="orders">{{ number_format($productInitialKpi['orders'], 0, ',', '.') }}</span> order</div>
            </div>
            <div class="dpanel ads-kpi">
                <div class="ads-kpi-label"><i class="bi bi-graph-up-arrow"></i> GMV</div>
                <div class="ads-kpi-value" data-product-kpi-value="gmv">Rp {{ number_format($productInitialKpi['gmv'], 0, ',', '.') }}</div>
                <div class="ads-kpi-sub">ROAS <span data-product-kpi-value="roas">{{ number_format($productInitialKpi['roas'], 2, ',', '.') }}x</span> · AOV <span data-product-kpi-value="aov">Rp {{ number_format($productInitialKpi['aov'], 0, ',', '.') }}</span></div>
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
        .product-variant-empty-button { display:inline-flex; align-items:center; justify-content:center; border:0; padding:.1rem .2rem; background:transparent; color:#b91c1c; font-size:.78rem; line-height:1; }
        .product-variant-empty-button:hover { color:#7f1d1d; transform:scale(1.08); }
        .product-variant-modal-list { display:flex; flex-wrap:wrap; gap:.35rem; }
        .product-variant-modal-group { width:100%; }
        .product-variant-modal-product { margin-bottom:.3rem; color:#334155; font-size:.72rem; font-weight:750; }
        .product-variant-modal-chip { display:inline-flex; align-items:center; padding:.25rem .45rem; border:1px solid rgba(220,38,38,.2); border-radius:999px; background:rgba(220,38,38,.06); color:#991b1b; font-size:.68rem; font-weight:650; }
    </style>
    <div class="table-responsive" style="max-height: 600px;">
        <table class="table table-hover align-middle mb-0 text-nowrap" style="font-size: 0.85rem;" id="productsPerformanceTable">
            <thead class="table-light sticky-top" style="z-index: 2;">
                <tr>
                    <th>Produk / SKU</th>
                    <th>Sinyal</th>
                    <th class="text-end">Stok</th>
                    <th class="text-end">Orders</th>
                    <th class="text-end" data-bs-toggle="tooltip" title="Click-Through Rate">CTR</th>
                    <th class="text-end" data-bs-toggle="tooltip" title="Conversion Rate">CVR</th>
                    <th class="text-end">Biaya Iklan</th>
                    <th class="text-end">AOV</th>
                    <th class="text-end">GMV</th>
                    <th class="text-end" data-bs-toggle="tooltip" title="Net Profit = Gross Profit - Spend">Net Profit</th>
                    <th class="text-end" data-bs-toggle="tooltip" title="Profit on Ad Spend">POAS</th>
                </tr>
            </thead>
            <tbody>
                @foreach($productCategoryRows as $categoryRow)
                    <tr class="product-category-row" data-product-views="category" onclick="productToggleCategory(this)">
                        <td><div class="d-flex align-items-center gap-1"><i class="bi bi-chevron-right product-caret text-muted" style="font-size:.7rem;"></i><div><div class="fw-bold">{{ $categoryRow['category'] }}</div><div class="text-muted small">{{ number_format($categoryRow['products'], 0, ',', '.') }} produk</div></div></div></td>
                        <td><span class="badge bg-light text-dark border">Ringkasan</span><br><span class="badge bg-{{ $categoryRow['stock_risk']['class'] }}" style="font-size:.58rem;">{{ $categoryRow['stock_risk']['label'] }}</span></td>
                        <td class="text-end">{{ number_format($categoryRow['stock'], 0, ',', '.') }}@if($categoryRow['variant_stock_empty_products'] > 0)<br><button type="button" class="product-variant-empty-button" aria-label="Lihat variant kosong" data-product-name="{{ e($categoryRow['category']) }}" data-variant-groups="{{ e(json_encode($categoryRow['empty_variant_products'], JSON_UNESCAPED_UNICODE)) }}" onclick="event.stopPropagation(); productOpenVariantModal(this);"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i></button>@endif</td>
                        <td class="text-end fw-bold">{{ number_format($categoryRow['orders'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($categoryRow['ctr'], 2) }}%</td>
                        <td class="text-end">{{ number_format($categoryRow['cvr'], 2) }}%</td>
                        <td class="text-end fw-bold text-danger" title="Biaya iklan setelah PPN 11%">Rp {{ number_format($categoryRow['spend'], 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format($categoryRow['aov'], 0, ',', '.') }}</td>
                        <td class="text-end text-success fw-bold">Rp {{ number_format($categoryRow['gmv'], 0, ',', '.') }}</td>
                        <td class="text-end fw-bold {{ $categoryRow['profit'] >= 0 ? 'text-success' : 'text-danger' }}">{{ $categoryRow['profit_unknown'] > 0 ? 'N/A' : 'Rp ' . number_format($categoryRow['profit'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($categoryRow['poas'], 2) }}x</td>
                    </tr>
                    <tr class="product-category-detail" style="display:none;">
                        <td colspan="11" class="bg-light">
                            <div class="text-muted fw-bold mb-1" style="font-size:.66rem;">Daftar produk dalam kategori {{ $categoryRow['category'] }}</div>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0" style="font-size:.72rem;">
                                    <thead><tr><th>Produk / SKU</th><th>Sinyal</th><th class="text-end">Stok</th><th class="text-end">Orders</th><th class="text-end">AOV</th><th class="text-end">GMV</th><th class="text-end">Gross Profit</th><th class="text-end">Net Profit</th><th class="text-end">POAS</th></tr></thead>
                                    <tbody>
                                        @foreach($productCategoryProductRows->get($categoryRow['category'], collect()) as $productEntry)
                                            @php $detailProduct = $productEntry['row']; @endphp
                                            <tr>
                                                <td><div class="fw-bold text-truncate" style="max-width:260px;" title="{{ $detailProduct->item_name }}">{{ $detailProduct->item_name }}</div><div class="text-muted" style="font-size:.62rem;">{{ $detailProduct->item_sku }}</div></td>
                                                <td><span class="badge bg-{{ $detailProduct->class_color }}">{{ $detailProduct->classification }}</span><br><span class="badge bg-{{ $productEntry['stock_risk']['class'] }}" style="font-size:.56rem;">{{ $productEntry['stock_risk']['label'] }}</span></td>
                                                <td class="text-end">{{ number_format($detailProduct->stock_total ?? 0, 0, ',', '.') }}@if($productEntry['variant_stock_empty'])<br><button type="button" class="product-variant-empty-button" aria-label="Lihat variant kosong" data-product-name="{{ e($detailProduct->item_name ?: 'Produk') }}" data-variant-groups="{{ e(json_encode([['product' => $detailProduct->item_name ?: 'Produk', 'variants' => $productEntry['empty_variant_skus']]], JSON_UNESCAPED_UNICODE)) }}" onclick="event.stopPropagation(); productOpenVariantModal(this);"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i></button>@endif</td>
                                                <td class="text-end">{{ number_format($detailProduct->orders, 0, ',', '.') }}</td>
                                                <td class="text-end">Rp {{ number_format(($detailProduct->orders ?? 0) > 0 ? $detailProduct->gmv / $detailProduct->orders : 0, 0, ',', '.') }}</td>
                                                <td class="text-end text-success">Rp {{ number_format($detailProduct->gmv, 0, ',', '.') }}</td>
                                                <td class="text-end">{{ $detailProduct->gross_profit === null ? 'N/A' : 'Rp ' . number_format($detailProduct->gross_profit, 0, ',', '.') }}</td>
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
                            <br><span class="badge bg-{{ $productEntry['stock_risk']['class'] }}" style="font-size:.58rem;">{{ $productEntry['stock_risk']['label'] }}</span>
                        </td>
                        <td class="text-end">
                            @if(($row->stock_total ?? 0) < 5)
                                <span class="text-danger fw-bold">{{ $row->stock_total ?? 0 }}</span>
                            @else
                                {{ $row->stock_total ?? 0 }}
                            @endif
                            @if($productEntry['variant_stock_empty'])
                                <button type="button" class="product-variant-empty-button" aria-label="Lihat variant kosong" data-product-name="{{ e($row->item_name ?: 'Produk') }}" data-variant-groups="{{ e(json_encode([['product' => $row->item_name ?: 'Produk', 'variants' => $productEntry['empty_variant_skus']]], JSON_UNESCAPED_UNICODE)) }}" onclick="event.stopPropagation(); productOpenVariantModal(this);"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i></button>
                            @endif
                        </td>
                        <td class="text-end fw-bold">{{ number_format($row->orders, 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($row->ctr, 2) }}%</td>
                        <td class="text-end">{{ number_format($row->cvr, 2) }}%</td>
                        <td class="text-end fw-bold text-danger" title="Biaya iklan setelah PPN 11%">Rp {{ number_format($row->spend_ppn ?? (($row->spend ?? 0) * 1.11), 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format(($row->orders ?? 0) > 0 ? $row->gmv / $row->orders : 0, 0, ',', '.') }}</td>
                        <td class="text-end text-success fw-bold">Rp {{ number_format($row->gmv, 0, ',', '.') }}</td>
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

<div class="modal fade" id="productVariantEmptyModal" tabindex="-1" aria-labelledby="productVariantEmptyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-sm">
            <div class="modal-header py-2 px-3">
                <h6 class="modal-title mb-0" id="productVariantEmptyModalLabel">Variant kosong</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body px-3 py-2">
            <div id="productVariantEmptyList" class="product-variant-modal-list"></div>
            </div>
        </div>
    </div>
</div>

<script>
const productKpiData = {!! json_encode($productSegmentKpis) !!};
const productChartData = {!! json_encode($productChartData) !!};
let productMatrixChart = null;
let productStockChart = null;

window.productOpenVariantModal = function(button) {
    const modalEl = document.getElementById('productVariantEmptyModal');
    const listEl = document.getElementById('productVariantEmptyList');
    if (!modalEl || !listEl) return;

    let groups = [];
    try { groups = JSON.parse(button.dataset.variantGroups || '[]'); } catch (e) { groups = []; }
    if (!Array.isArray(groups) || groups.length === 0) {
        groups = [{ product: button.dataset.productName || 'Produk', variants: [] }];
    }
    listEl.innerHTML = groups.map(function (group) {
        const product = document.createElement('div');
        product.textContent = group.product || 'Produk';
        const chips = (Array.isArray(group.variants) ? group.variants : []).map(function (variant) {
            const item = document.createElement('span');
            item.textContent = variant;
            return '<span class="product-variant-modal-chip">' + item.innerHTML + '</span>';
        }).join('');
        return '<div class="product-variant-modal-group"><div class="product-variant-modal-product">' + product.innerHTML + '</div><div class="product-variant-modal-list">' + chips + '</div></div>';
    }).join('');
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
};

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
    setValue('aov', 'Rp ' + productFormatNumber(kpi.aov, 0));
    setValue('profit', 'Rp ' + productFormatNumber(kpi.profit, 0));
    setValue('profit_unknown', productFormatNumber(kpi.profit_unknown, 0));
    productRenderCharts(view);
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

function productRenderCharts(view) {
    if (typeof Chart === 'undefined') return;
    const rows = productChartData[view] || productChartData.category || [];
    const money = value => 'Rp ' + Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
    const classColorMap = {
        'Hero Product': 'rgba(25, 135, 84, 0.7)',
        'Profit Driver': 'rgba(13, 110, 253, 0.7)',
        'Volume Driver': 'rgba(13, 202, 240, 0.7)',
        'Traffic Driver (No Conv)': 'rgba(255, 193, 7, 0.7)',
        'Loss Maker': 'rgba(220, 53, 69, 0.7)',
        'Stock Risk': 'rgba(220, 53, 69, 0.9)',
        'Kategori': 'rgba(71, 85, 105, 0.7)',
        'Review': 'rgba(108, 117, 125, 0.7)',
        'Low Performer': 'rgba(173, 181, 189, 0.7)',
    };
    const colorFor = label => classColorMap[label] || classColorMap.Review;

    const matrixCanvas = document.getElementById('productScatterChart');
    if (matrixCanvas) {
        if (productMatrixChart) productMatrixChart.destroy();
        const matrixRows = rows.map(row => ({ ...row, y: row.profit_available ? row.y : 0 }));
        productMatrixChart = new Chart(matrixCanvas, {
            type: 'bubble',
            data: {
                datasets: [...new Set(matrixRows.map(row => row.reco))].map(function(label) {
                    const color = colorFor(label);
                    return {
                        label: label,
                        data: matrixRows.filter(row => row.reco === label),
                        backgroundColor: color,
                        borderColor: color.replace('0.7', '1').replace('0.9', '1'),
                        borderWidth: 1,
                    };
                }),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, position: 'bottom', labels: { boxWidth: 10, font: { size: 9 } } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const row = context.raw;
                                return [
                                    row.name,
                                    'Biaya iklan: ' + money(row.x) + ' · Orders: ' + productFormatNumber(row.orders, 0),
                                    'Net profit: ' + (row.profit_available ? money(row.y) : 'N/A · HPP belum tersedia'),
                                    'GMV: ' + money(row.gmv) + ' · AOV: ' + money(row.aov),
                                    'ROAS: ' + Number(row.roas || 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + 'x',
                                ];
                            },
                        },
                    },
                },
                scales: {
                    x: { title: { display: true, text: 'Biaya Iklan (Rp)' }, ticks: { callback: value => 'Rp ' + (value / 1000).toLocaleString('id-ID') + 'k' } },
                    y: { title: { display: true, text: 'Net Profit Terhitung (Rp)' }, ticks: { callback: value => 'Rp ' + (value / 1000).toLocaleString('id-ID') + 'k' }, grid: { color: context => context.tick.value === 0 ? '#ff0000' : 'rgba(0,0,0,0.1)' } },
                },
            },
        });
    }

    const stockCanvas = document.getElementById('productStockChart');
    if (stockCanvas) {
        if (productStockChart) productStockChart.destroy();
        const stockColors = {
            'Stok Aman': 'rgba(16, 185, 129, .72)',
            'Stok Menipis': 'rgba(245, 158, 11, .72)',
            'Stok Kritis': 'rgba(239, 68, 68, .78)',
            'Stok Habis': 'rgba(185, 28, 28, .85)',
            'Tidak Ada Penjualan': 'rgba(100, 116, 139, .72)',
            'Stok Kosong': 'rgba(71, 85, 105, .72)',
            'Agregat Campaign': 'rgba(139, 92, 246, .72)',
        };
        const stockLabels = [...new Set(rows.map(row => row.stock_risk?.label || 'Review'))];
        productStockChart = new Chart(stockCanvas, {
            type: 'bubble',
            data: {
                datasets: stockLabels.map(function(label) {
                    const color = stockColors[label] || 'rgba(108, 117, 125, .72)';
                    return {
                        label: label,
                        data: rows.filter(row => (row.stock_risk?.label || 'Review') === label).map(row => ({
                            x: row.velocity,
                            y: row.stock,
                            r: Math.max(4, Math.min(16, 4 + Math.log10(Number(row.gmv || 0) + 1) * 2)),
                            name: row.name,
                            orders: row.orders,
                            gmv: row.gmv,
                            stock_days: row.stock_risk?.label === 'Agregat Campaign' ? null : (row.velocity > 0 ? row.stock / row.velocity : null),
                        })),
                        backgroundColor: color,
                        borderColor: color.replace('.72', '1').replace('.78', '1').replace('.85', '1'),
                        borderWidth: 1,
                    };
                }),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, position: 'bottom', labels: { boxWidth: 10, font: { size: 9 } } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const row = context.raw;
                                return [
                                    row.name,
                                    'Orders/hari: ' + Number(row.x || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 }),
                                    'Stok: ' + productFormatNumber(row.y, 0) + ' · GMV: ' + money(row.gmv),
                                    row.stock_days === null ? 'Coverage: N/A' : 'Coverage: ' + Number(row.stock_days).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' hari',
                                ];
                            },
                        },
                    },
                },
                scales: {
                    x: { beginAtZero: true, title: { display: true, text: 'Orders per Hari' } },
                    y: { beginAtZero: true, title: { display: true, text: 'Stok Tersedia' } },
                },
            },
        });
    }

    const hint = document.getElementById('productScatterHint');
    if (hint) hint.textContent = 'Subtab: ' + ({ category: 'Per Kategori', roas: 'GMV Max ROAS', gms: 'GMV Max Auto', unmapped: 'Produk Bermasalah' }[view] || 'Per Kategori');
}
</script>
