@php
    /*
    |--------------------------------------------------------------------
    | CAMPAIGN PERFORMANCE TAB
    | Tujuan: Membandingkan performa seluruh campaign untuk scaling.
    |--------------------------------------------------------------------
    */
    
    // Siapkan koleksi untuk scatter chart & tabel
    $perfRows = collect();
    $totalSpend = 0;
    $totalGmv = 0;
    $totalOrders = 0;
    $totalClicks = 0;
    $totalImpressions = 0;
    $totalProfit = 0;
    $profitUnknownCount = 0;

    // Target profit toko (dari tab Pengaturan). Dipakai untuk hitung target
    // ROAS per campaign yang menyesuaikan HPP tiap item.
    $perfTargetProfitPct = $targetProfitPct ?? null;

    // Label periode pembanding (mengikuti compare_mode halaman).
    $perfCompareMode = $compareMode ?? 'prev_period';
    $perfCompareLabel = match ($perfCompareMode) {
        'prev_month' => 'bulan lalu',
        'prev_year'  => 'tahun lalu',
        default      => 'periode lalu',
    };

    // Helper render delta perbandingan vs periode pembanding.
    // $dir: true = makin tinggi makin baik, false = makin rendah makin baik,
    //       null = netral (tanpa warna baik/buruk).
    $fmtDelta = function ($cur, $prev, $dir = true) {
        if ($prev === null || $prev == 0) {
            return '<span class="text-muted" style="font-size:.58rem;">—</span>';
        }
        $chg = (($cur - $prev) / abs($prev)) * 100;
        $up = $chg >= 0;
        $arrow = $up ? '▲' : '▼';
        if ($dir === null || abs($chg) < 0.5) {
            $cls = 'text-muted';
        } else {
            $good = $dir ? $up : ! $up;
            $cls = $good ? 'text-success' : 'text-danger';
        }
        return '<span class="' . $cls . ' perf-delta">' . $arrow . ' ' . number_format(abs($chg), 0) . '%</span>';
    };

    foreach ($campaigns as $camp) {
        if (!($camp->spend > 0 || $camp->gmv > 0)) continue;

        // Metrik Dasar
        $spend = (float) $camp->spend;
        $gmv = (float) $camp->gmv;
        $orders = (int) $camp->orders;
        $clicks = (int) $camp->clicks;
        $impressions = (int) $camp->impressions;
        
        $totalSpend += $spend;
        $totalGmv += $gmv;
        $totalOrders += $orders;
        $totalClicks += $clicks;
        $totalImpressions += $impressions;

        // Metrik Turunan
        $cpc = $clicks > 0 ? $spend / $clicks : 0;
        $ctr = $impressions > 0 ? ($clicks / $impressions) * 100 : 0;
        $cvr = $clicks > 0 ? ($orders / $clicks) * 100 : 0;
        $roas = $spend > 0 ? $gmv / $spend : 0;
        $cpa = $orders > 0 ? $spend / $orders : 0;
        $cpm = $impressions > 0 ? ($spend / $impressions) * 1000 : 0;

        // Data periode pembanding (untuk kolom perbandingan).
        $prevSpend = (float) ($camp->prev_spend ?? 0);
        $prevGmv = (float) ($camp->prev_gmv ?? 0);
        $prevOrders = (int) ($camp->prev_orders ?? 0);
        $prevClicks = (int) ($camp->prev_clicks ?? 0);
        $prevImpressions = (int) ($camp->prev_impressions ?? 0);
        $prevProfit = $camp->prev_profit_after_ads !== null ? (float) $camp->prev_profit_after_ads : null;
        $prevRoas = $prevSpend > 0 ? $prevGmv / $prevSpend : 0;

        // Profit & POAS
        $profitAvailable = $camp->profit_after_ads !== null;
        $profit = $profitAvailable ? (float) $camp->profit_after_ads : null;
        if ($profitAvailable) {
            $totalProfit += $profit;
        } else {
            $profitUnknownCount++;
        }
        $topup = $spend * 1.11;
        $poas = $profitAvailable && $topup > 0 ? $profit / $topup : null;

        // Target ROAS per campaign (menyesuaikan HPP item):
        //   target ROAS = 1 / ((netRatio - cogsRatio - targetProfit) / 1.11)
        // HPP diambil sama seperti tab Profit (unit_cogs); cogs_ratio dihitung
        // dari HPP / harga rata-rata bila service belum mengisinya. PPN iklan
        // 11% diperhitungkan. Bila target profit toko belum di-set, dipakai 0
        // (= ROAS impas / break-even). Null hanya bila HPP item belum tersedia.
        $rowUnitCogs = (float) ($camp->unit_cogs ?? 0);
        $rowAvgPrice = ($orders > 0 && $gmv > 0) ? ($gmv / $orders) : 0;
        $rowNetRatio = (float) ($camp->net_revenue_ratio ?? 0.781);
        $rowCogsRatio = (float) ($camp->cogs_ratio ?? 0);
        if ($rowCogsRatio <= 0 && $rowUnitCogs > 0 && $rowAvgPrice > 0) {
            $rowCogsRatio = $rowUnitCogs / $rowAvgPrice;
        }
        $rowTargetProfitFrac = $perfTargetProfitPct !== null ? ((float) $perfTargetProfitPct / 100) : 0.0;
        $targetRoas = null;
        $targetIsBreakEven = ($perfTargetProfitPct === null);
        if ($rowCogsRatio > 0 && $rowNetRatio > 0) {
            $acosTarget = ($rowNetRatio - $rowCogsRatio - $rowTargetProfitFrac) / 1.11;
            $targetRoas = $acosTarget > 0 ? round(1 / $acosTarget, 2) : null;
        }

        // Net margin (% dari GMV) & biaya iklan riil (termasuk PPN 11%).
        $margin = ($profitAvailable && $gmv > 0) ? ($profit / $gmv) * 100 : null;
        $spendWithPpn = $spend * 1.11;
        // Selisih ROAS aktual vs target (positif = di atas target).
        $roasGap = ($targetRoas !== null) ? ($roas - $targetRoas) : null;

        // Rekomendasi berbasis logika baru: bandingkan ROAS aktual vs Target
        // ROAS (yang sudah menyesuaikan HPP + PPN). 4 status yang jelas.
        if (!$profitAvailable || $targetRoas === null) {
            $reco = 'Data HPP';   $recoColor = 'secondary';
        } elseif ($profit <= 0) {
            $reco = 'Stop';       $recoColor = 'danger';
        } elseif ($roas >= $targetRoas * 1.2) {
            $reco = 'Scale';      $recoColor = 'success';   // di atas target, ada ruang
        } elseif ($roas >= $targetRoas) {
            $reco = 'Aman';       $recoColor = 'info';      // memenuhi target
        } else {
            $reco = 'Optimasi';   $recoColor = 'warning';   // profit tapi di bawah target
        }

        $campaignId = (string) $camp->channel_campaign_id;
        $campaignName = (string) ($camp->campaign_name ?? '');
        $isGmvMax = str_starts_with(strtoupper($campaignId), 'GMS-')
            || str_contains(strtolower($campaignName), 'gmv max');
        $isUnmapped = !$isGmvMax && ($camp->internalItem === null || (float) ($camp->unit_cogs ?? 0) <= 0);
        $performanceViews = $isGmvMax
            ? ['gms']
            : array_values(array_unique(array_merge(['category', 'roas'], $isUnmapped ? ['unmapped'] : [])));

        $perfRows->push([
            'id' => $camp->channel_campaign_id,
            'name' => $camp->campaign_name,
            'type' => $camp->ad_type,
            'performance_views' => $performanceViews,
            'category' => $camp->item_category ?? $camp->internalItem?->category?->name ?? 'Belum termapping',
            'status' => $camp->campaign_status,
            'spend' => $spend,
            'gmv' => $gmv,
            'orders' => $orders,
            'clicks' => $clicks,
            'impressions' => $impressions,
            'cpc' => $cpc,
            'ctr' => $ctr,
            'cvr' => $cvr,
            'cpm' => $cpm,
            'roas' => $roas,
            'target_roas' => $targetRoas,
            'target_is_break_even' => $targetIsBreakEven,
            'roas_gap' => $roasGap,
            'cpa' => $cpa,
            'profit' => $profit,
            'profit_available' => $profitAvailable,
            'margin' => $margin,
            'spend_ppn' => $spendWithPpn,
            'poas' => $poas,
            'prev_spend' => $prevSpend,
            'prev_gmv' => $prevGmv,
            'prev_orders' => $prevOrders,
            'prev_clicks' => $prevClicks,
            'prev_impressions' => $prevImpressions,
            'prev_profit' => $prevProfit,
            'prev_roas' => $prevRoas,
            'reco' => $reco,
            'recoColor' => $recoColor,
            'item_name' => $camp->internalItem ? $camp->internalItem->name : 'N/A',
            'unit_cogs' => (float) ($camp->unit_cogs ?? 0),
        ]);
    }
    
    // Sort by Spend Descending by default
    $perfRows = $perfRows->sortByDesc('spend')->values();
    $perfCategoryCampaignRows = $perfRows
        ->filter(fn ($row) => in_array('category', $row['performance_views'] ?? [], true))
        ->groupBy('category');
    $perfCategoryRows = $perfRows
        ->filter(fn ($row) => in_array('category', $row['performance_views'] ?? [], true))
        ->groupBy('category')
        ->map(function ($group, $category) {
            $spend = $group->sum('spend');
            $gmv = $group->sum('gmv');
            $orders = $group->sum('orders');
            $clicks = $group->sum('clicks');
            $impressions = $group->sum('impressions');
            $profitKnown = $group->filter(fn ($row) => $row['profit_available']);
            $targets = $group->pluck('target_roas')->filter(fn ($target) => $target !== null);
            $roas = $spend > 0 ? $gmv / $spend : 0;
            $targetRoas = $targets->isNotEmpty() ? round($targets->avg(), 2) : null;
            $profit = $profitKnown->sum('profit');
            $reco = $targetRoas === null
                ? ['label' => 'Data HPP', 'color' => 'secondary']
                : ($profit <= 0 ? ['label' => 'Stop', 'color' => 'danger']
                    : ($roas >= $targetRoas * 1.2 ? ['label' => 'Scale', 'color' => 'success']
                        : ($roas >= $targetRoas ? ['label' => 'Aman', 'color' => 'info'] : ['label' => 'Optimasi', 'color' => 'warning'])));

            return [
                'category' => $category,
                'key' => 'perf-cat-' . md5((string) $category),
                'campaign_count' => $group->count(),
                'spend' => $spend,
                'gmv' => $gmv,
                'orders' => $orders,
                'clicks' => $clicks,
                'impressions' => $impressions,
                'roas' => $roas,
                'target_roas' => $targetRoas,
                'profit' => $profit,
                'profit_available' => $profitKnown->count() === $group->count(),
                'margin' => $gmv > 0 ? ($profit / $gmv) * 100 : null,
                'cvr' => $clicks > 0 ? ($orders / $clicks) * 100 : 0,
                'reco' => $reco,
            ];
        })
        ->sortByDesc('spend')
        ->values();
    $perfSegmentCounts = [
        'category' => $perfCategoryRows->count(),
        'roas' => $perfRows->filter(fn ($row) => in_array('roas', $row['performance_views'] ?? [], true))->count(),
        'gms' => $perfRows->filter(fn ($row) => in_array('gms', $row['performance_views'] ?? [], true))->count(),
        'unmapped' => $perfRows->filter(fn ($row) => in_array('unmapped', $row['performance_views'] ?? [], true))->count(),
    ];

    $scatterDataJson = json_encode($perfRows->map(function($r) {
        return [
            'x' => $r['spend'],
            'y' => $r['profit'],
            'name' => $r['name'],
            'reco' => $r['reco'],
            'r' => max(4, min(15, $r['orders'] * 2)) // Bubble size based on orders
        ];
    })->values());
@endphp

<style>
    /* Sel metrik bertingkat: nilai utama + Δ sejajar, sub-metrik di bawah. */
    .perf-cell { line-height: 1.18; }
    .perf-cell .perf-main { display: flex; align-items: baseline; justify-content: flex-end; gap: .3rem; white-space: nowrap; }
    .perf-cell .perf-val { font-weight: 700; }
    .perf-delta { font-size: .58rem; font-weight: 700; }
    .perf-cell .perf-sub { font-size: .62rem; font-weight: 500; color: var(--dsh-muted, #6b7280); margin-top: 1px; white-space: nowrap; }
    /* Baris bisa di-klik untuk expand detail. */
    .perf-table th { font-size: .66rem; text-transform: uppercase; letter-spacing: .02em; color: var(--dsh-muted, #6b7280); }
    .perf-row { cursor: pointer; }
    .perf-row:hover { background: rgba(37, 99, 235, .04); }
    .perf-category-row { cursor: pointer; }
    .perf-category-row:hover { background: rgba(37, 99, 235, .04); }
    .perf-caret { transition: transform .15s; }
    .perf-row.open .perf-caret { transform: rotate(90deg); }
    .perf-category-row.open .perf-caret { transform: rotate(90deg); }
    .perf-detail > td { padding: .5rem .75rem; }
    .perf-category-detail > td { padding: .55rem .75rem; }
    .perf-category-campaign-table { font-size: .72rem; }
    .perf-category-campaign-table th { color: var(--dsh-muted, #6b7280); font-size: .62rem; text-transform: uppercase; letter-spacing: .02em; }
    .perf-category-campaign-table th, .perf-category-campaign-table td { padding: .4rem .45rem; }
    .perf-chips { display: flex; flex-wrap: wrap; gap: .4rem; }
    .perf-chip { display: flex; align-items: baseline; gap: .35rem; background: var(--card-bg, #fff); border: 1px solid var(--dsh-border, rgba(0,0,0,.08)); border-radius: 8px; padding: .3rem .55rem; font-size: .68rem; }
    .perf-chip > span { color: var(--dsh-muted, #6b7280); }
    .perf-chip > b { font-weight: 700; }
</style>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="dpanel p-3 h-100">
            <div class="text-muted small fw-bold text-uppercase mb-1">Total Spend</div>
            <div class="fs-4 fw-bolder text-dark">Rp {{ number_format($totalSpend, 0, ',', '.') }}</div>
            <div class="small text-muted mt-1"><i class="bi bi-bullseye"></i> Ad Budget Used</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dpanel p-3 h-100">
            <div class="text-muted small fw-bold text-uppercase mb-1">Total Orders (Ads)</div>
            <div class="fs-4 fw-bolder text-dark">{{ number_format($totalOrders, 0, ',', '.') }}</div>
            <div class="small text-muted mt-1">CPA: Rp {{ number_format($totalOrders > 0 ? $totalSpend / $totalOrders : 0, 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dpanel p-3 h-100">
            <div class="text-muted small fw-bold text-uppercase mb-1">Total GMV (Ads)</div>
            <div class="fs-4 fw-bolder text-dark">Rp {{ number_format($totalGmv, 0, ',', '.') }}</div>
            <div class="small text-muted mt-1">ROAS: {{ number_format($totalSpend > 0 ? $totalGmv / $totalSpend : 0, 2) }}x</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dpanel p-3 h-100">
            <div class="text-muted small fw-bold text-uppercase mb-1" data-bs-toggle="tooltip" title="Profit hanya dijumlahkan untuk campaign yang memiliki HPP">Net Profit Terhitung</div>
            <div class="fs-4 fw-bolder {{ $profitUnknownCount > 0 ? 'text-warning' : ($totalProfit >= 0 ? 'text-success' : 'text-danger') }}">
                Rp {{ number_format($totalProfit, 0, ',', '.') }}
            </div>
            <div class="small text-muted mt-1">POAS: {{ number_format($totalSpend > 0 ? $totalProfit / ($totalSpend * 1.11) : 0, 2) }}x · {{ $profitUnknownCount }} belum ada HPP</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Scatter Chart (Spend vs Profit) -->
    <div class="col-12 col-xl-12">
        <div class="dpanel p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-graph-up text-primary me-2"></i>Scatter: Spend vs Est. Profit</h6>
                <span class="badge bg-light text-dark border">Kuadran Atas-Kanan = Potensial Scale</span>
            </div>
            <div style="height: 300px; position: relative;">
                <canvas id="campaignScatterChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="dpanel p-0 overflow-hidden">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2 bg-light">
        <h6 class="mb-0 fw-bold"><i class="bi bi-list-nested text-primary me-2"></i>Tabel Performa Campaign</h6>
        <div class="d-flex align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-1">
                <span class="text-muted" style="font-size:.68rem;"><i class="bi bi-arrow-left-right"></i> Bandingkan:</span>
                <select onchange="const u=new URL(location); u.searchParams.set('compare_mode', this.value); u.hash='tab-campaign-performance'; location.assign(u.toString());"
                        class="form-select form-select-sm" style="width:auto; font-size:.7rem; padding:.15rem 1.6rem .15rem .5rem;">
                    <option value="prev_period" {{ $perfCompareMode === 'prev_period' ? 'selected' : '' }}>Periode Sebelumnya</option>
                    <option value="prev_month" {{ $perfCompareMode === 'prev_month' ? 'selected' : '' }}>Bulan Lalu</option>
                    <option value="prev_year" {{ $perfCompareMode === 'prev_year' ? 'selected' : '' }}>Tahun Lalu (tanggal sama)</option>
                </select>
            </div>
            <div style="font-size:.7rem;">
                <span class="badge bg-success" title="ROAS ≥ 1,2× target">Scale</span>
                <span class="badge bg-info" title="ROAS memenuhi target">Aman</span>
                <span class="badge bg-warning text-dark" title="Profit tapi di bawah target ROAS">Optimasi</span>
                <span class="badge bg-danger" title="Rugi (profit ≤ 0)">Stop</span>
                <span class="badge bg-secondary" title="HPP belum tersedia">Data HPP</span>
            </div>
        </div>
    </div>
    <div class="p-2 px-3 border-bottom text-muted" style="font-size:.62rem;">
        <i class="bi bi-info-circle"></i> Klik baris untuk lihat detail (Jangkauan, CPC, CTR, CPM, CPA, POAS) &amp; perbandingan vs {{ $perfCompareLabel }}.
    </div>
    <div style="display:flex; gap:.35rem; margin:.75rem .75rem .75rem; overflow-x:auto;" role="tablist" aria-label="Jenis tampilan performa">
        <button type="button" id="btnPerformanceCategory" class="btn fw-bold" onclick="__campaignPerformanceView('category')" data-perf-count="{{ $perfSegmentCounts['category'] }}" aria-selected="false" style="border-radius:999px; font-size:.72rem; padding:.38rem .95rem; color:var(--dsh-muted); border:1px solid var(--dsh-border); background:transparent; white-space:nowrap;">Per Kategori</button>
        <button type="button" id="btnPerformanceRoas" class="btn fw-bold" onclick="__campaignPerformanceView('roas')" data-perf-count="{{ $perfSegmentCounts['roas'] }}" aria-selected="false" style="border-radius:999px; font-size:.72rem; padding:.38rem .95rem; color:var(--dsh-muted); border:1px solid var(--dsh-border); background:transparent; white-space:nowrap;">GMV Max ROAS</button>
        <button type="button" id="btnPerformanceGms" class="btn fw-bold" onclick="__campaignPerformanceView('gms')" data-perf-count="{{ $perfSegmentCounts['gms'] }}" aria-selected="false" style="border-radius:999px; font-size:.72rem; padding:.38rem .95rem; color:var(--dsh-muted); border:1px solid var(--dsh-border); background:transparent; white-space:nowrap;">GMV Max Auto</button>
        <button type="button" id="btnPerformanceUnmapped" class="btn fw-bold" onclick="__campaignPerformanceView('unmapped')" data-perf-count="{{ $perfSegmentCounts['unmapped'] }}" aria-selected="false" style="border-radius:999px; font-size:.72rem; padding:.38rem .95rem; color:var(--dsh-muted); border:1px solid var(--dsh-border); background:transparent; white-space:nowrap;">Produk Belum Mapping</button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 perf-table" style="font-size: 0.82rem;">
            <thead class="table-light sticky-top" style="z-index: 2;">
                <tr>
                    <th style="min-width:180px;">Kategori / Campaign</th>
                    <th class="text-center" data-bs-toggle="tooltip" title="Rekomendasi berdasarkan ROAS aktual vs Target ROAS">Sinyal</th>
                    <th class="text-end" data-bs-toggle="tooltip" title="Biaya iklan (atas) & Omzet (bawah) · Δ vs {{ $perfCompareLabel }}">Biaya / Omzet</th>
                    <th class="text-end" data-bs-toggle="tooltip" title="ROAS aktual & Target ROAS (menyesuaikan HPP, PPN 11%)">ROAS / Target</th>
                    <th class="text-end" data-bs-toggle="tooltip" title="Est. Net Profit & Margin · Δ vs {{ $perfCompareLabel }}">Profit</th>
                    <th class="text-end" data-bs-toggle="tooltip" title="Jumlah order & CVR">Orders</th>
                </tr>
            </thead>
            <tbody>
                @foreach($perfCategoryRows as $categoryRow)
                    <tr class="perf-category-row" data-perf-views="category" onclick="perfToggleCategory(this)">
                        <td>
                            <div class="d-flex align-items-start gap-1">
                                <i class="bi bi-chevron-right perf-caret text-muted" style="font-size:.7rem; margin-top:.15rem;"></i>
                                <div class="fw-bold text-truncate" style="max-width:180px;" title="{{ $categoryRow['category'] }}">{{ $categoryRow['category'] }}</div>
                            </div>
                            <div class="text-muted" style="font-size:.68rem;">{{ number_format($categoryRow['campaign_count'], 0, ',', '.') }} campaign</div>
                        </td>
                        <td class="text-center"><span class="badge bg-{{ $categoryRow['reco']['color'] }}">{{ $categoryRow['reco']['label'] }}</span></td>
                        <td class="text-end perf-cell"><div class="perf-main"><span class="perf-val">Rp {{ number_format($categoryRow['spend'], 0, ',', '.') }}</span></div><div class="perf-sub"><span class="text-success">Rp {{ number_format($categoryRow['gmv'], 0, ',', '.') }}</span></div></td>
                        <td class="text-end perf-cell"><div class="perf-main"><span class="perf-val">{{ number_format($categoryRow['roas'], 2) }}x</span></div><div class="perf-sub">tgt {{ $categoryRow['target_roas'] === null ? '—' : number_format($categoryRow['target_roas'], 2) . 'x' }}</div></td>
                        <td class="text-end perf-cell"><div class="perf-main"><span class="perf-val {{ !$categoryRow['profit_available'] ? 'text-muted' : ($categoryRow['profit'] >= 0 ? 'text-success' : 'text-danger') }}">{{ !$categoryRow['profit_available'] ? 'N/A' : 'Rp ' . number_format($categoryRow['profit'], 0, ',', '.') }}</span></div>@if($categoryRow['profit_available'])<div class="perf-sub">margin {{ $categoryRow['margin'] === null ? '—' : number_format($categoryRow['margin'], 1) . '%' }}</div>@endif</td>
                        <td class="text-end perf-cell"><div class="perf-main"><span class="perf-val">{{ number_format($categoryRow['orders'], 0, ',', '.') }}</span></div><div class="perf-sub">CVR {{ number_format($categoryRow['cvr'], 2) }}%</div></td>
                    </tr>
                    <tr class="perf-category-detail" data-perf-views="category" style="display:none;">
                        <td colspan="6" class="bg-light">
                            <div class="text-muted fw-bold mb-1" style="font-size:.66rem;">Daftar campaign dalam kategori {{ $categoryRow['category'] }}</div>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0 perf-category-campaign-table">
                                    <thead>
                                        <tr>
                                            <th>Campaign / Item</th>
                                            <th class="text-end">Spend</th>
                                            <th class="text-end">GMV</th>
                                            <th class="text-end">ROAS</th>
                                            <th class="text-end">Target</th>
                                            <th class="text-end">Orders</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($perfCategoryCampaignRows->get($categoryRow['category'], collect()) as $campaignRow)
                                            <tr>
                                                <td>
                                                    <div class="fw-bold text-truncate" style="max-width:260px;" title="{{ $campaignRow['name'] }}">{{ $campaignRow['name'] }}</div>
                                                    <div class="text-muted text-truncate" style="font-size:.62rem; max-width:260px;">{{ $campaignRow['item_name'] }} @if($campaignRow['type']) · {{ strtoupper($campaignRow['type']) }}@endif</div>
                                                </td>
                                                <td class="text-end">Rp {{ number_format($campaignRow['spend'], 0, ',', '.') }}</td>
                                                <td class="text-end text-success">Rp {{ number_format($campaignRow['gmv'], 0, ',', '.') }}</td>
                                                <td class="text-end fw-bold">{{ number_format($campaignRow['roas'], 2) }}x</td>
                                                <td class="text-end">{{ $campaignRow['target_roas'] === null ? '—' : number_format($campaignRow['target_roas'], 2) . 'x' }}</td>
                                                <td class="text-end">{{ number_format($campaignRow['orders'], 0, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                @endforeach
                @forelse($perfRows as $row)
                    @php
                        $meetsTarget = $row['target_roas'] !== null && $row['roas'] >= $row['target_roas'];
                    @endphp
                    <tr class="perf-row" data-perf-views="{{ implode(' ', $row['performance_views'] ?? []) }}" onclick="perfToggle(this)">
                        {{-- Campaign / Item --}}
                        <td>
                            <div class="d-flex align-items-start gap-1">
                                <i class="bi bi-chevron-right perf-caret text-muted" style="font-size:.7rem; margin-top:.15rem;"></i>
                                <div style="min-width:0;">
                                    <div class="fw-bold text-truncate" style="max-width: 180px;" title="{{ $row['name'] }}">{{ $row['name'] }}</div>
                                    <div class="text-muted text-truncate" style="max-width: 180px; font-size:.68rem;">
                                        <i class="bi bi-box"></i> {{ $row['item_name'] }}
                                        @if($row['type'])<span class="badge bg-light text-dark border ms-1" style="font-size:.52rem;">{{ strtoupper($row['type']) }}</span>@endif
                                    </div>
                                    @if($row['item_name'] !== 'N/A')
                                        <div class="text-muted" style="font-size:.6rem;">HPP {{ $row['unit_cogs'] > 0 ? 'Rp ' . number_format($row['unit_cogs'], 0, ',', '.') : '—' }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- Sinyal --}}
                        <td class="text-center">
                            <span class="badge bg-{{ $row['recoColor'] }}">{{ $row['reco'] }}</span>
                        </td>

                        {{-- Biaya / Omzet --}}
                        <td class="text-end perf-cell">
                            <div class="perf-main"><span class="perf-val">Rp {{ number_format($row['spend'], 0, ',', '.') }}</span></div>
                            <div class="perf-sub"><span class="text-success">Rp {{ number_format($row['gmv'], 0, ',', '.') }}</span> {!! $fmtDelta($row['gmv'], $row['prev_gmv'], true) !!}</div>
                        </td>

                        {{-- ROAS / Target --}}
                        <td class="text-end perf-cell">
                            <div class="perf-main"><span class="perf-val {{ $row['target_roas'] === null ? '' : ($meetsTarget ? 'text-success' : 'text-danger') }}">{{ number_format($row['roas'], 2) }}x</span>{!! $fmtDelta($row['roas'], $row['prev_roas'], true) !!}</div>
                            <div class="perf-sub">
                                @if($row['target_roas'] === null)
                                    tgt —
                                @else
                                    tgt {{ number_format($row['target_roas'], 2) }}x{{ $row['target_is_break_even'] ? ' (impas)' : '' }}
                                @endif
                            </div>
                        </td>

                        {{-- Profit --}}
                        <td class="text-end perf-cell">
                            <div class="perf-main"><span class="perf-val {{ !$row['profit_available'] ? 'text-muted' : ($row['profit'] >= 0 ? 'text-success' : 'text-danger') }}">{{ !$row['profit_available'] ? 'N/A' : 'Rp ' . number_format($row['profit'], 0, ',', '.') }}</span>@if($row['profit_available']){!! $fmtDelta($row['profit'], $row['prev_profit'], true) !!}@endif</div>
                            @if($row['profit_available'])
                                <div class="perf-sub">margin {{ $row['margin'] === null ? '—' : number_format($row['margin'], 1) . '%' }}</div>
                            @endif
                        </td>

                        {{-- Orders --}}
                        <td class="text-end perf-cell">
                            <div class="perf-main"><span class="perf-val">{{ number_format($row['orders'], 0, ',', '.') }}</span>{!! $fmtDelta($row['orders'], $row['prev_orders'], true) !!}</div>
                            <div class="perf-sub">CVR {{ number_format($row['cvr'], 2) }}%</div>
                        </td>
                    </tr>

                    {{-- Detail (expand) --}}
                    <tr class="perf-detail" data-perf-views="{{ implode(' ', $row['performance_views'] ?? []) }}" style="display:none;">
                        <td colspan="6" class="bg-light">
                            <div class="perf-chips">
                                <div class="perf-chip"><span>Jangkauan</span><b>{{ number_format($row['impressions'], 0, ',', '.') }}</b>{!! $fmtDelta($row['impressions'], $row['prev_impressions'], true) !!}</div>
                                <div class="perf-chip"><span>CPM</span><b>Rp {{ number_format($row['cpm'], 0, ',', '.') }}</b></div>
                                <div class="perf-chip"><span>Klik</span><b>{{ number_format($row['clicks'], 0, ',', '.') }}</b>{!! $fmtDelta($row['clicks'], $row['prev_clicks'], true) !!}</div>
                                <div class="perf-chip"><span>CPC</span><b>Rp {{ number_format($row['cpc'], 0, ',', '.') }}</b></div>
                                <div class="perf-chip"><span>CTR</span><b>{{ number_format($row['ctr'], 2) }}%</b></div>
                                <div class="perf-chip"><span>CPA</span><b>Rp {{ number_format($row['cpa'], 0, ',', '.') }}</b></div>
                                <div class="perf-chip"><span>POAS</span><b>{{ $row['poas'] === null ? '—' : number_format($row['poas'], 2) . 'x' }}</b></div>
                                <div class="perf-chip"><span>Spend riil (PPN)</span><b>Rp {{ number_format($row['spend_ppn'], 0, ',', '.') }}</b>{!! $fmtDelta($row['spend'], $row['prev_spend'], null) !!}</div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr class="perf-no-data">
                        <td colspan="6" class="text-center py-4 text-muted">Belum ada data performa campaign yang memadai.</td>
                    </tr>
                @endforelse
                <tr class="perf-segment-empty" style="display:none;">
                    <td colspan="6" class="text-center py-4 text-muted">Belum ada data untuk kategori ini.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
// Toggle baris detail campaign (expand/collapse).
function perfToggle(row) {
    row.classList.toggle('open');
    const d = row.nextElementSibling;
    if (d && d.classList.contains('perf-detail')) {
        d.style.display = (d.style.display === 'none' || !d.style.display) ? 'table-row' : 'none';
    }
}

function perfToggleCategory(row) {
    row.classList.toggle('open');
    const detail = row.nextElementSibling;
    if (detail && detail.classList.contains('perf-category-detail')) {
        detail.style.display = (detail.style.display === 'none' || !detail.style.display) ? 'table-row' : 'none';
    }
}

function perfApplySegment(segment) {
    const rows = Array.from(document.querySelectorAll('.perf-row, .perf-category-row'));
    const categoryDetails = Array.from(document.querySelectorAll('.perf-category-detail'));
    const emptyState = document.querySelector('.perf-segment-empty');
    const noDataState = document.querySelector('.perf-no-data');

    if (!rows.length) {
        if (emptyState) emptyState.style.display = 'none';
        if (noDataState) noDataState.style.display = 'table-row';
        return;
    }

    if (noDataState) noDataState.style.display = 'none';
    categoryDetails.forEach(function(detail) {
        detail.style.display = 'none';
    });
    let visibleCount = 0;

    rows.forEach(function(row) {
        const isVisible = (row.dataset.perfViews || '').split(/\s+/).includes(segment);
        const detail = row.classList.contains('perf-row') ? row.nextElementSibling : null;
        row.style.display = isVisible ? '' : 'none';
        row.classList.remove('open');

        if (detail && detail.classList.contains('perf-detail')) {
            detail.style.display = 'none';
            detail.style.visibility = isVisible ? '' : 'hidden';
        }

        if (isVisible) visibleCount++;
    });

    if (emptyState) emptyState.style.display = visibleCount ? 'none' : 'table-row';

}

window.__campaignPerformanceView = function(view) {
    perfApplySegment(view);

    const buttonOn = 'border-radius:999px; font-size:.72rem; padding:.38rem .95rem; background:var(--dsh-accent); color:#fff; border:1px solid var(--dsh-accent);';
    const buttonOff = 'border-radius:999px; font-size:.72rem; padding:.38rem .95rem; color:var(--dsh-muted); border:1px solid var(--dsh-border); background:transparent;';
    const buttons = {
        category: document.getElementById('btnPerformanceCategory'),
        roas: document.getElementById('btnPerformanceRoas'),
        gms: document.getElementById('btnPerformanceGms'),
        unmapped: document.getElementById('btnPerformanceUnmapped'),
    };

    Object.entries(buttons).forEach(function([key, button]) {
        if (!button) return;
        const active = key === view;
        button.style.cssText = active ? buttonOn : buttonOff;
        button.setAttribute('aria-selected', active ? 'true' : 'false');
    });

    try { localStorage.setItem('adsPerformanceView', view); } catch (e) {}
};

// Kembali ke tab Campaign setelah reload akibat ganti periode pembanding.
document.addEventListener('DOMContentLoaded', function() {
    const categoryButton = document.getElementById('btnPerformanceCategory');
    if (categoryButton) {
        let savedView = null;
        try { savedView = localStorage.getItem('adsPerformanceView'); } catch (e) {}
        const validViews = ['category', 'roas', 'gms', 'unmapped'];
        const initialView = validViews.includes(savedView) ? savedView : 'category';
        window.__campaignPerformanceView(initialView);
    }

    if (location.hash === '#tab-campaign-performance' && typeof window.openAdsPane === 'function') {
        setTimeout(function () { window.openAdsPane('tab-campaign-performance'); }, 150);
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const scatterData = {!! $scatterDataJson !!};

    const ctx = document.getElementById('campaignScatterChart');
    if (!ctx) return;

    // Define colors for recommendations
    const colorMap = {
        'Scale': 'rgba(25, 135, 84, 0.7)',      // Success
        'Aman': 'rgba(13, 202, 240, 0.7)',      // Info
        'Optimasi': 'rgba(255, 193, 7, 0.7)',   // Warning
        'Stop': 'rgba(220, 53, 69, 0.7)',       // Danger
        'Data HPP': 'rgba(108, 117, 125, 0.7)'  // Secondary
    };

    const datasets = Object.keys(colorMap).map(reco => {
        return {
            label: reco,
            data: scatterData.filter(d => d.reco === reco),
            backgroundColor: colorMap[reco],
            borderColor: colorMap[reco].replace('0.7', '1'),
            borderWidth: 1
        };
    });

    new Chart(ctx, {
        type: 'bubble',
        data: {
            datasets: datasets
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
                                'Profit: Rp ' + data.y.toLocaleString('id-ID')
                            ];
                        }
                    }
                },
                legend: {
                    display: false
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
