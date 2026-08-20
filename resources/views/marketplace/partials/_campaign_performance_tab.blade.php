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
        // Biaya performa memakai biaya iklan riil setelah PPN 11%.
        // Profit service juga sudah memakai basis biaya setelah PPN.
        $spend = (float) $camp->spend * 1.11;
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
        $prevSpend = (float) ($camp->prev_spend ?? 0) * 1.11;
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
        $topup = $spend;
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
        $bepRoas = null;
        if ($rowCogsRatio > 0 && $rowNetRatio > 0) {
            $bepAcos = ($rowNetRatio - $rowCogsRatio) / 1.11;
            $bepRoas = $bepAcos > 0 ? round(1 / $bepAcos, 2) : null;
        }
        if ($rowCogsRatio > 0 && $rowNetRatio > 0) {
            $acosTarget = ($rowNetRatio - $rowCogsRatio - $rowTargetProfitFrac) / 1.11;
            $targetRoas = $acosTarget > 0 ? round(1 / $acosTarget, 2) : null;
        }

        // Net margin (% dari GMV) & biaya iklan riil (termasuk PPN 11%).
        $margin = ($profitAvailable && $gmv > 0) ? ($profit / $gmv) * 100 : null;
        $spendWithPpn = $spend;
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
        // Produk dianggap bermasalah bila HPP efektif belum tersedia. HPP bisa
        // berasal dari mapping SKU, jadi jangan hanya bergantung pada relasi
        // internalItem yang masih bisa kosong setelah mapping diperbaiki.
        $isUnmapped = !$isGmvMax && (float) ($camp->unit_cogs ?? 0) <= 0;
        $performanceViews = $isGmvMax
            ? ['category', 'gms']
            : array_values(array_unique(array_merge(['category', 'roas'], $isUnmapped ? ['unmapped'] : [])));

        $perfRows->push([
            'id' => $camp->channel_campaign_id,
            'store_id' => $camp->store_id,
            'campaign_id' => (string) $camp->channel_campaign_id,
            'name' => $camp->campaign_name,
            'type' => $camp->ad_type,
            'performance_views' => $performanceViews,
            'category' => $isGmvMax ? 'GMV Max Auto' : ($camp->item_category ?? $camp->internalItem?->category?->name ?? 'Belum termapping'),
            'status' => $camp->campaign_status,
            'spend' => $spend,
            'gmv' => $gmv,
            'orders' => $orders,
            'aov' => $orders > 0 ? $gmv / $orders : 0,
            'clicks' => $clicks,
            'impressions' => $impressions,
            'cpc' => $cpc,
            'ctr' => $ctr,
            'cvr' => $cvr,
            'cpm' => $cpm,
            'roas' => $roas,
            'target_roas' => $targetRoas,
            'bep_roas' => $bepRoas,
            'configured_roas' => $camp->target_roas !== null ? (float) $camp->target_roas : null,
            'actual_vs_target_pct' => ((float) ($camp->target_roas ?? 0)) > 0
                ? round(($roas / (float) $camp->target_roas) * 100, 1)
                : null,
            'campaign_budget' => (float) ($camp->campaign_budget ?? 0),
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
            'prev_aov' => $prevOrders > 0 ? $prevGmv / $prevOrders : 0,
            'prev_clicks' => $prevClicks,
            'prev_impressions' => $prevImpressions,
            'prev_profit' => $prevProfit,
            'prev_roas' => $prevRoas,
            'reco' => $reco,
            'recoColor' => $recoColor,
            'variant_empty' => (bool) ($camp->variant_empty ?? false),
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
            $configuredTargets = $group->pluck('configured_roas')->filter(fn ($target) => $target !== null);
            $roas = $spend > 0 ? $gmv / $spend : 0;
            $targetRoas = $targets->isNotEmpty() ? round($targets->avg(), 2) : null;
            $configuredRoas = $configuredTargets->isNotEmpty() ? round($configuredTargets->avg(), 2) : null;
            $bepValues = $group->pluck('bep_roas')->filter(fn ($bep) => $bep !== null);
            $bepRoas = $bepValues->isNotEmpty() ? round($bepValues->avg(), 2) : null;
            $profit = $profitKnown->sum('profit');
            $prevSpend = $group->sum('prev_spend');
            $prevGmv = $group->sum('prev_gmv');
            $prevOrders = $group->sum('prev_orders');
            $prevProfitRows = $group->filter(fn ($row) => $row['prev_profit'] !== null);
            $prevProfit = $prevProfitRows->sum('prev_profit');
            $prevRoas = $prevSpend > 0 ? $prevGmv / $prevSpend : 0;
            $reco = $targetRoas === null
                ? ['label' => 'Data HPP', 'color' => 'secondary']
                : ($profit <= 0 ? ['label' => 'Stop', 'color' => 'danger']
                    : ($roas >= $targetRoas * 1.2 ? ['label' => 'Scale', 'color' => 'success']
                        : ($roas >= $targetRoas ? ['label' => 'Aman', 'color' => 'info'] : ['label' => 'Optimasi', 'color' => 'warning'])));
            $emptyVariantCount = $group->where('variant_empty', true)->count();

            return [
                'category' => $category,
                'key' => 'perf-cat-' . md5((string) $category),
                'campaign_count' => $group->count(),
                'spend' => $spend,
                'gmv' => $gmv,
                'orders' => $orders,
                'aov' => $orders > 0 ? $gmv / $orders : 0,
                'clicks' => $clicks,
                'impressions' => $impressions,
                'roas' => $roas,
                'target_roas' => $targetRoas,
                'bep_roas' => $bepRoas,
                'configured_roas' => $configuredRoas,
                'campaign_budget' => $group->sum('campaign_budget'),
                'actual_vs_target_pct' => $configuredRoas !== null && $configuredRoas > 0
                    ? round(($roas / $configuredRoas) * 100, 1)
                    : null,
                'profit' => $profit,
                'profit_available' => $profitKnown->count() === $group->count(),
                'margin' => $gmv > 0 ? ($profit / $gmv) * 100 : null,
                'cvr' => $clicks > 0 ? ($orders / $clicks) * 100 : 0,
                'prev_spend' => $prevSpend,
                'prev_gmv' => $prevGmv,
                'prev_orders' => $prevOrders,
                'prev_aov' => $prevOrders > 0 ? $prevGmv / $prevOrders : 0,
                'prev_roas' => $prevRoas,
                'prev_profit' => $prevProfit,
                'prev_profit_available' => $prevProfitRows->count() > 0,
                'reco' => $reco,
                'empty_variant_count' => $emptyVariantCount,
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

    // KPI mengikuti subtab aktif, seperti pada tab Profit. Baris campaign
    // tetap menjadi sumber daftar detail di tabel bawahnya.
    $perfSegmentRows = [
        'category' => $perfRows->filter(fn ($row) => in_array('category', $row['performance_views'] ?? [], true)),
        'roas' => $perfRows->filter(fn ($row) => in_array('roas', $row['performance_views'] ?? [], true)),
        'gms' => $perfRows->filter(fn ($row) => in_array('gms', $row['performance_views'] ?? [], true)),
        'unmapped' => $perfRows->filter(fn ($row) => in_array('unmapped', $row['performance_views'] ?? [], true)),
    ];
    $perfSegmentKpis = collect($perfSegmentRows)->map(function ($rows) {
        $knownProfitRows = $rows->filter(fn ($row) => $row['profit_available']);
        $spend = $rows->sum('spend');
        $gmv = $rows->sum('gmv');
        $prevSpend = $rows->sum('prev_spend');
        $prevGmv = $rows->sum('prev_gmv');
        $prevOrders = $rows->sum('prev_orders');
        $prevProfitRows = $rows->filter(fn ($row) => $row['prev_profit'] !== null);

        return [
            'campaigns' => $rows->count(),
            'spend' => $spend,
            'gmv' => $gmv,
            'orders' => $rows->sum('orders'),
            'aov' => $rows->sum('orders') > 0 ? $gmv / $rows->sum('orders') : 0,
            'cpa' => $rows->sum('orders') > 0 ? $spend / $rows->sum('orders') : 0,
            'roas' => $spend > 0 ? $gmv / $spend : 0,
            'profit' => $knownProfitRows->sum('profit'),
            'profit_per_order' => $rows->sum('orders') > 0 ? $knownProfitRows->sum('profit') / $rows->sum('orders') : 0,
            'profit_unknown' => $rows->count() - $knownProfitRows->count(),
            'losses' => $knownProfitRows->filter(fn ($row) => $row['profit'] <= 0)->count(),
            'prev_spend' => $prevSpend,
            'prev_gmv' => $prevGmv,
            'prev_orders' => $prevOrders,
            'prev_aov' => $prevOrders > 0 ? $prevGmv / $prevOrders : 0,
            'prev_cpa' => $prevOrders > 0 ? $prevSpend / $prevOrders : 0,
            'prev_roas' => $prevSpend > 0 ? $prevGmv / $prevSpend : 0,
            'prev_profit' => $prevProfitRows->sum('prev_profit'),
            'prev_profit_per_order' => $prevOrders > 0 ? $prevProfitRows->sum('prev_profit') / $prevOrders : 0,
            'prev_profit_available' => $prevProfitRows->count() > 0,
        ];
    })->all();
    // KPI keseluruhan harus memakai sumber Seller Center yang sama dengan
    // tab Profit. KPI subtab tetap memakai agregasi baris GMV Max ROAS/Auto.
    $overallCurrentKpi = ($kpi ?? [])['current'] ?? null;
    $overallPreviousKpi = ($kpi ?? [])['previous'] ?? null;
    $overallCurrentGmv = $overallCurrentKpi !== null
        ? (float) ($overallCurrentKpi->gmv ?? 0)
        : $totalGmv;
    $overallCurrentOrders = $overallCurrentKpi !== null
        ? (int) ($overallCurrentKpi->orders ?? 0)
        : $totalOrders;
    $overallPreviousGmv = $overallPreviousKpi !== null
        ? (float) ($overallPreviousKpi->gmv ?? 0)
        : $perfRows->sum('prev_gmv');
    $overallPreviousOrders = $overallPreviousKpi !== null
        ? (int) ($overallPreviousKpi->orders ?? 0)
        : $perfRows->sum('prev_orders');
    $overallPreviousSpend = $overallPreviousKpi !== null
        ? (float) ($overallPreviousKpi->spend ?? 0) * 1.11
        : $perfRows->sum('prev_spend');
    $overallPreviousProfit = $overallPreviousKpi !== null
        ? (float) ($overallPreviousKpi->net_profit ?? 0)
        : $perfRows->sum('prev_profit');
    if ($overallCurrentKpi !== null) {
        $totalSpend = (float) ($overallCurrentKpi->spend ?? 0) * 1.11;
        $totalGmv = $overallCurrentGmv;
        $totalOrders = $overallCurrentOrders;
        $totalProfit = (float) ($overallCurrentKpi->net_profit ?? 0);
    }

    // Subtab Per Kategori merepresentasikan total dashboard, bukan hanya
    // penjumlahan baris campaign. Dengan begitu KPI mini dan KPI utama tetap
    // memakai basis Seller Center yang sama, termasuk biaya yang belum punya
    // baris campaign.
    $categoryKpi = $perfSegmentKpis['category'];
    if ($overallCurrentKpi !== null) {
        $categoryKpi['spend'] = $totalSpend;
        $categoryKpi['gmv'] = $overallCurrentGmv;
        $categoryKpi['orders'] = $overallCurrentOrders;
        $categoryKpi['aov'] = $overallCurrentOrders > 0 ? $overallCurrentGmv / $overallCurrentOrders : 0;
        $categoryKpi['cpa'] = $overallCurrentOrders > 0 ? $totalSpend / $overallCurrentOrders : 0;
        $categoryKpi['roas'] = $totalSpend > 0 ? $overallCurrentGmv / $totalSpend : 0;
        $categoryKpi['profit'] = $totalProfit;
        $categoryKpi['profit_per_order'] = $overallCurrentOrders > 0 ? $totalProfit / $overallCurrentOrders : 0;
        $categoryKpi['prev_spend'] = $overallPreviousSpend;
        $categoryKpi['prev_gmv'] = $overallPreviousGmv;
        $categoryKpi['prev_orders'] = $overallPreviousOrders;
        $categoryKpi['prev_aov'] = $overallPreviousOrders > 0 ? $overallPreviousGmv / $overallPreviousOrders : 0;
        $categoryKpi['prev_cpa'] = $overallPreviousOrders > 0 ? $overallPreviousSpend / $overallPreviousOrders : 0;
        $categoryKpi['prev_roas'] = $overallPreviousSpend > 0 ? $overallPreviousGmv / $overallPreviousSpend : 0;
        $categoryKpi['prev_profit'] = $overallPreviousProfit;
        $categoryKpi['prev_profit_per_order'] = $overallPreviousOrders > 0 ? $overallPreviousProfit / $overallPreviousOrders : 0;
    }
    $perfSegmentKpis['category'] = $categoryKpi;
    $perfInitialKpi = $perfSegmentKpis['category'];
    $perfVisibleProfit = $perfRows->sum(fn ($row) => (float) ($row['profit'] ?? 0));
    $perfVisiblePreviousProfit = $perfRows->sum(fn ($row) => (float) ($row['prev_profit'] ?? 0));
    $perfProfitAdjustment = $overallCurrentKpi !== null
        ? round($totalProfit - $perfVisibleProfit, 2)
        : 0.0;
    $perfPreviousProfitAdjustment = $overallPreviousKpi !== null
        ? round($overallPreviousProfit - $perfVisiblePreviousProfit, 2)
        : 0.0;
    $perfSpendAdjustment = $overallCurrentKpi !== null
        ? max(0, round($totalSpend - $perfRows->sum('spend'), 2))
        : 0.0;
    if (abs($perfProfitAdjustment) >= 0.01) {
        $reconciliationCategory = 'Penyesuaian Seller Center';
        $reconciliationCampaign = [
            'id' => 'seller-center-adjustment',
            'store_id' => null,
            'campaign_id' => 'seller-center-adjustment',
            'name' => 'Penyesuaian Seller Center',
            'item_name' => 'Biaya/profit belum teralokasi ke campaign',
            'type' => null,
            'bep_roas' => null,
            'configured_roas' => null,
            'campaign_budget' => 0,
            'roas' => 0,
            'actual_vs_target_pct' => null,
            'prev_roas' => 0,
            'orders' => 0,
            'prev_orders' => 0,
            'spend' => $perfSpendAdjustment,
            'prev_spend' => 0,
            'aov' => 0,
            'prev_aov' => 0,
            'gmv' => 0,
            'prev_gmv' => 0,
            'profit' => $perfProfitAdjustment,
            'profit_available' => true,
            'prev_profit' => $perfPreviousProfitAdjustment,
        ];
        $perfCategoryRows = collect([[
            'category' => $reconciliationCategory,
            'key' => 'perf-cat-seller-center-adjustment',
            'campaign_count' => 1,
            'spend' => $perfSpendAdjustment,
            'gmv' => 0,
            'orders' => 0,
            'aov' => 0,
            'clicks' => 0,
            'impressions' => 0,
            'roas' => 0,
            'target_roas' => null,
            'bep_roas' => null,
            'configured_roas' => null,
            'campaign_budget' => 0,
            'actual_vs_target_pct' => null,
            'profit' => $perfProfitAdjustment,
            'profit_available' => true,
            'margin' => null,
            'cvr' => 0,
            'prev_spend' => 0,
            'prev_gmv' => 0,
            'prev_orders' => 0,
            'prev_aov' => 0,
            'prev_roas' => 0,
            'prev_profit' => $perfPreviousProfitAdjustment,
            'prev_profit_available' => true,
            'reco' => ['label' => 'Rekonsiliasi', 'color' => 'warning'],
            'empty_variant_count' => 0,
        ]])->concat($perfCategoryRows)->values();
        $perfCategoryCampaignRows = $perfCategoryCampaignRows->put($reconciliationCategory, collect([$reconciliationCampaign]));
        $perfSegmentCounts['category']++;
    }

    $scatterDataJson = json_encode($perfRows->map(function($r) {
        return [
            'x' => $r['spend'],
            // Campaign tanpa HPP diberi titik netral di garis 0, tetapi nilai
            // tooltip tetap N/A agar tidak dianggap sebagai profit nol.
            'y' => $r['profit_available'] ? $r['profit'] : 0,
            'name' => $r['name'],
            'reco' => $r['reco'],
            'views' => $r['performance_views'],
            'profit' => $r['profit'],
            'profit_available' => $r['profit_available'],
            'orders' => $r['orders'],
            'roas' => $r['roas'],
            'target_roas' => $r['configured_roas'] ?? $r['target_roas'],
            'cpa' => $r['cpa'],
            'profit_per_order' => $r['profit_available'] && $r['orders'] > 0
                ? $r['profit'] / $r['orders']
                : null,
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
    .perf-kpi-compare { font-size: .61rem; color: var(--dsh-muted, #6b7280); margin-top: .15rem; white-space: nowrap; }
    .perf-previous { color: var(--dsh-muted, #6b7280); font-size: .59rem; white-space: nowrap; }
    .perf-overall-row { background: rgba(37, 99, 235, .055); border-top: 2px solid var(--dsh-border, #e2e8f0); }
    .perf-overall-row td { font-weight: 700; }
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
    .perf-edit-target { font-size:.58rem; padding:.12rem .35rem; border-radius:6px; line-height:1; }
    .perf-inline-config { display: inline-flex; justify-content: flex-end; max-width: 100%; }
    .perf-inline-config-display { display: inline-flex; align-items: center; gap: .22rem; cursor: pointer; white-space: nowrap; border-radius: 5px; padding: .12rem .2rem; }
    .perf-inline-config-display:hover { background: rgba(37, 99, 235, .08); }
    .perf-inline-config-display i { font-size: .55rem; color: var(--dsh-muted, #6b7280); opacity: .55; }
    .perf-inline-config-editor { display: inline-flex; align-items: center; gap: .2rem; background: var(--bg, #fff); border: 1px solid var(--dsh-border, rgba(0,0,0,.12)); border-radius: 6px; padding: .12rem .2rem; }
    .perf-inline-config-editor input { width: 78px; height: 22px; border: 0; background: #fff; color: #0f172a !important; caret-color: #0f172a; padding: 0 .2rem; font-size: .68rem; font-weight: 700; text-align: right; outline: none; }
    .perf-inline-config-editor input::placeholder { color: #64748b; opacity: 1; font-weight: 500; }
    .perf-inline-config-editor input:focus { color: #020617 !important; }
    .perf-inline-config-editor[data-perf-inline-mode="roas"] input { width: 48px; }
    .perf-inline-config-editor i { cursor: pointer; font-size: .95rem; line-height: 1; }
    .campaign-performance-inline-editor { min-width: 145px; }
    .campaign-performance-inline-editor .form-control { font-size:.68rem; padding:.25rem .35rem; min-width:0; }
    .campaign-performance-inline-editor label { display:block; font-size:.55rem; color:var(--dsh-muted, #6b7280); margin-bottom:.1rem; }
    .campaign-performance-inline-editor .btn { font-size:.58rem; padding:.2rem .35rem; line-height:1.1; }

    /* Compact UI: angka tetap terbaca, ruang kosong dan teks bantu dipangkas. */
    #campaignPerformanceOverallKpis { margin-bottom: .8rem !important; }
    #campaignPerformanceOverallKpis .dpanel { padding: .65rem .75rem !important; border-radius: 11px; }
    #campaignPerformanceOverallKpis .fs-4 { font-size: 1.05rem !important; line-height: 1.15; }
    #campaignPerformanceOverallKpis .small { font-size: .58rem !important; margin-top: .25rem !important; }
    #campaignPerformanceOverallKpis .perf-kpi-compare { font-size: .55rem; }
    #campaignPerformanceSubtabKpis { padding-left: .65rem !important; padding-right: .65rem !important; }
    #campaignPerformanceSubtabKpis .ads-kpi-grid { gap: .45rem; margin-bottom: .35rem !important; }
    #campaignPerformanceSubtabKpis .ads-kpi-grid { grid-template-columns: repeat(5, minmax(0, 1fr)); }
    #campaignPerformanceSubtabKpis .ads-kpi { padding: .6rem .65rem; border-radius: 10px; min-height: 0; }
    #campaignPerformanceSubtabKpis .ads-kpi-label { font-size: .57rem; line-height: 1.1; }
    #campaignPerformanceSubtabKpis .ads-kpi-value { font-size: 1rem; line-height: 1.15; margin-top: .25rem; }
    #campaignPerformanceSubtabKpis .ads-kpi-sub { font-size: .56rem; margin-top: .2rem; }
    #campaignPerformanceSubtabKpis .perf-kpi-compare { font-size: .54rem; margin-top: .1rem; white-space: normal; overflow: visible; text-overflow: clip; }
    .campaign-performance-panel > .p-3 { padding: .65rem .8rem !important; }
    .campaign-performance-panel > .p-2 { padding: .35rem .8rem !important; }
    .campaign-performance-panel [role="tablist"] { margin: .45rem .55rem !important; }
    .campaign-performance-panel [role="tablist"] .btn { font-size: .64rem !important; padding: .3rem .7rem !important; }
    .campaign-performance-panel .perf-table { font-size: .72rem !important; }
    .campaign-performance-panel .perf-table th { padding: .42rem .45rem !important; font-size: .57rem; }
    .campaign-performance-panel .perf-table td { padding: .42rem .45rem !important; }
    .campaign-performance-panel .perf-main { gap: .18rem; }
    .campaign-performance-panel .perf-main .perf-val { font-size: .72rem; }
    .campaign-performance-panel .perf-sub,
    .campaign-performance-panel .perf-previous { font-size: .54rem; line-height: 1.15; }
    .campaign-performance-panel .perf-delta { font-size: .52rem; }
    .campaign-performance-panel .perf-category-campaign-table { font-size: .65rem; }
    .campaign-performance-panel .perf-category-campaign-table th,
    .campaign-performance-panel .perf-category-campaign-table td { padding: .3rem .35rem; }
    @media (max-width: 991.98px) {
        #campaignPerformanceSubtabKpis .ads-kpi-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (max-width: 575.98px) {
        #campaignPerformanceSubtabKpis .ads-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
</style>

<div class="row g-3 mb-4" id="campaignPerformanceOverallKpis">
    <div class="col-6 col-md-3">
            <div class="dpanel p-3 h-100">
                <div class="text-muted small fw-bold text-uppercase mb-1" title="Total biaya iklan Seller Center setelah PPN 11%">Total Seller Center + PPN</div>
                <div class="fs-4 fw-bolder text-dark">Rp {{ number_format($totalSpend, 0, ',', '.') }}</div>
                <div class="small text-muted mt-1"><i class="bi bi-bullseye"></i> GMV Max ROAS + GMV Max Auto</div>
                <div class="perf-kpi-compare" title="Perbandingan: {{ $perfCompareLabel }}">↔ Rp {{ number_format($overallPreviousSpend, 0, ',', '.') }} {!! $fmtDelta($totalSpend, $overallPreviousSpend, null) !!}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dpanel p-3 h-100">
                <div class="text-muted small fw-bold text-uppercase mb-1">Total Orders (Ads)</div>
                <div class="fs-4 fw-bolder text-dark">{{ number_format($totalOrders, 0, ',', '.') }}</div>
                <div class="small text-muted mt-1">Biaya/order: Rp {{ number_format($totalOrders > 0 ? $totalSpend / $totalOrders : 0, 0, ',', '.') }}</div>
                <div class="perf-kpi-compare" title="Perbandingan: {{ $perfCompareLabel }}">↔ {{ number_format($overallPreviousOrders, 0, ',', '.') }} {!! $fmtDelta($totalOrders, $overallPreviousOrders, true) !!}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dpanel p-3 h-100">
                <div class="text-muted small fw-bold text-uppercase mb-1">Total GMV (Ads)</div>
                <div class="fs-4 fw-bolder text-dark">Rp {{ number_format($totalGmv, 0, ',', '.') }}</div>
                <div class="small text-muted mt-1">ROAS setelah PPN: {{ number_format($totalSpend > 0 ? $totalGmv / $totalSpend : 0, 2) }}x · AOV: Rp {{ number_format($totalOrders > 0 ? $totalGmv / $totalOrders : 0, 0, ',', '.') }}</div>
                <div class="perf-kpi-compare" title="Perbandingan: {{ $perfCompareLabel }}">↔ Rp {{ number_format($overallPreviousGmv, 0, ',', '.') }} {!! $fmtDelta($totalGmv, $overallPreviousGmv, true) !!}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dpanel p-3 h-100">
            <div class="text-muted small fw-bold text-uppercase mb-1" data-bs-toggle="tooltip" title="Pendapatan bersih dikurangi HPP dan biaya iklan setelah PPN 11%">Net Profit Terhitung</div>
            <div class="fs-4 fw-bolder {{ $profitUnknownCount > 0 ? 'text-warning' : ($totalProfit >= 0 ? 'text-success' : 'text-danger') }}">
                Rp {{ number_format($totalProfit, 0, ',', '.') }}
            </div>
            <div class="small text-muted mt-1">POAS: {{ number_format($totalSpend > 0 ? $totalProfit / $totalSpend : 0, 2) }}x · Profit/order: Rp {{ number_format($totalOrders > 0 ? $totalProfit / $totalOrders : 0, 0, ',', '.') }} · {{ $profitUnknownCount }} belum ada HPP</div>
            <div class="perf-kpi-compare" title="Perbandingan: {{ $perfCompareLabel }}">↔ Rp {{ number_format($overallPreviousProfit, 0, ',', '.') }} {!! $fmtDelta($totalProfit, $overallPreviousProfit, true) !!}</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Scatter Chart (Spend vs Profit) -->
    <div class="col-12 col-xl-12">
        <div class="dpanel p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-graph-up text-primary me-2"></i>Biaya Iklan vs Net Profit Terhitung</h6>
                <span id="campaignScatterHint" class="badge bg-light text-dark border">Subtab: Per Kategori</span>
            </div>
            <div style="height: 300px; position: relative;">
                <canvas id="campaignScatterChart"></canvas>
            </div>
            <div class="small text-muted mt-2">X = biaya iklan setelah PPN · Y = net profit · ukuran bubble = orders · Data HPP ditandai abu-abu.</div>
        </div>
    </div>
</div>

<div class="dpanel p-0 overflow-hidden campaign-performance-panel">
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
        <i class="bi bi-info-circle"></i> Klik baris untuk lihat detail performa.
    </div>
    <div style="display:flex; gap:.35rem; margin:.75rem .75rem .75rem; overflow-x:auto;" role="tablist" aria-label="Jenis tampilan performa">
        <button type="button" id="btnPerformanceCategory" class="btn fw-bold" onclick="__campaignPerformanceView('category')" data-perf-count="{{ $perfSegmentCounts['category'] }}" aria-selected="false" style="border-radius:999px; font-size:.72rem; padding:.38rem .95rem; color:var(--dsh-muted); border:1px solid var(--dsh-border); background:transparent; white-space:nowrap;">Per Kategori</button>
        <button type="button" id="btnPerformanceRoas" class="btn fw-bold" onclick="__campaignPerformanceView('roas')" data-perf-count="{{ $perfSegmentCounts['roas'] }}" aria-selected="false" style="border-radius:999px; font-size:.72rem; padding:.38rem .95rem; color:var(--dsh-muted); border:1px solid var(--dsh-border); background:transparent; white-space:nowrap;">GMV Max ROAS</button>
        <button type="button" id="btnPerformanceGms" class="btn fw-bold" onclick="__campaignPerformanceView('gms')" data-perf-count="{{ $perfSegmentCounts['gms'] }}" aria-selected="false" style="border-radius:999px; font-size:.72rem; padding:.38rem .95rem; color:var(--dsh-muted); border:1px solid var(--dsh-border); background:transparent; white-space:nowrap;">GMV Max Auto</button>
        <button type="button" id="btnPerformanceUnmapped" class="btn fw-bold" onclick="__campaignPerformanceView('unmapped')" data-perf-count="{{ $perfSegmentCounts['unmapped'] }}" aria-selected="false" style="border-radius:999px; font-size:.72rem; padding:.38rem .95rem; color:var(--dsh-muted); border:1px solid var(--dsh-border); background:transparent; white-space:nowrap;">Produk Bermasalah</button>
    </div>
    <div class="px-3 pb-2" id="campaignPerformanceSubtabKpis">
        <div class="ads-kpi-grid mb-2">
            <div class="dpanel ads-kpi kpi-spend">
                <div class="ads-kpi-label" data-bs-toggle="tooltip" title="Total biaya iklan setelah PPN 11%."><i class="bi bi-wallet2" aria-hidden="true"></i> Biaya Iklan</div>
                <div class="ads-kpi-value" data-perf-kpi-value="spend">Rp {{ number_format($perfInitialKpi['spend'], 0, ',', '.') }}</div>
                <div class="ads-kpi-sub" title="Campaign adalah jumlah campaign pada subtab aktif; rugi berarti profit tidak lebih dari nol."><span title="Jumlah campaign pada subtab aktif"><i class="bi bi-megaphone me-1" aria-hidden="true"></i><span data-perf-kpi-value="campaigns">{{ number_format($perfInitialKpi['campaigns'], 0, ',', '.') }}</span> campaign</span> · <span title="Campaign dengan profit tidak lebih dari nol"><i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i><span data-perf-kpi-value="losses">{{ number_format($perfInitialKpi['losses'], 0, ',', '.') }}</span> rugi</span></div>
                <div class="perf-kpi-compare" data-perf-kpi-compare="spend" title="Perbandingan: {{ $perfCompareLabel }}">↔ Rp {{ number_format($perfInitialKpi['prev_spend'], 0, ',', '.') }} {!! $fmtDelta($perfInitialKpi['spend'], $perfInitialKpi['prev_spend'], null) !!}</div>
            </div>
            <div class="dpanel ads-kpi">
                <div class="ads-kpi-label" data-bs-toggle="tooltip" title="Biaya iklan setelah PPN 11% dibagi jumlah order."><i class="bi bi-receipt" aria-hidden="true"></i> Biaya per Order</div>
                <div class="ads-kpi-value" data-perf-kpi-value="cpa">Rp {{ number_format($perfInitialKpi['cpa'], 0, ',', '.') }}</div>
                <div class="ads-kpi-sub">{{ number_format($perfInitialKpi['orders'], 0, ',', '.') }} order</div>
                <div class="perf-kpi-compare" data-perf-kpi-compare="cpa" title="Perbandingan: {{ $perfCompareLabel }}">↔ Rp {{ number_format($perfInitialKpi['prev_cpa'], 0, ',', '.') }} {!! $fmtDelta($perfInitialKpi['cpa'], $perfInitialKpi['prev_cpa'], null) !!}</div>
            </div>
            <div class="dpanel ads-kpi">
                <div class="ads-kpi-label" data-bs-toggle="tooltip" title="Average Order Value: omzet GMV dibagi jumlah order."><i class="bi bi-graph-up-arrow" aria-hidden="true"></i> AOV</div>
                <div class="ads-kpi-value" data-perf-kpi-value="aov">Rp {{ number_format($perfInitialKpi['aov'], 0, ',', '.') }}</div>
                <div class="ads-kpi-sub">{{ number_format($perfInitialKpi['orders'], 0, ',', '.') }} order</div>
                <div class="perf-kpi-compare" data-perf-kpi-compare="aov" title="Perbandingan: {{ $perfCompareLabel }}">↔ Rp {{ number_format($perfInitialKpi['prev_aov'], 0, ',', '.') }} {!! $fmtDelta($perfInitialKpi['aov'], $perfInitialKpi['prev_aov'], true) !!}</div>
            </div>
            <div class="dpanel ads-kpi">
                <div class="ads-kpi-label" data-bs-toggle="tooltip" title="Return on Ad Spend: GMV dibandingkan dengan biaya iklan setelah PPN 11%."><i class="bi bi-speedometer2" aria-hidden="true"></i> ROAS</div>
                <div class="ads-kpi-value" data-perf-kpi-value="roas">{{ number_format($perfInitialKpi['roas'], 2, ',', '.') }}x</div>
                <div class="ads-kpi-sub">GMV dibanding iklan</div>
                <div class="perf-kpi-compare" data-perf-kpi-compare="roas" title="Perbandingan: {{ $perfCompareLabel }}">↔ {{ number_format($perfInitialKpi['prev_roas'], 2, ',', '.') }}x {!! $fmtDelta($perfInitialKpi['roas'], $perfInitialKpi['prev_roas'], true) !!}</div>
            </div>
            <div class="dpanel ads-kpi kpi-profit">
                <div class="ads-kpi-label" data-bs-toggle="tooltip" title="Net Profit keseluruhan setelah HPP dan biaya iklan setelah PPN 11%."><i class="bi bi-cash-coin" aria-hidden="true"></i> Net Profit Keseluruhan</div>
                <div class="ads-kpi-value {{ $profitUnknownCount > 0 ? 'text-warning' : ($totalProfit >= 0 ? 'text-success' : 'text-danger') }}">Rp {{ number_format($totalProfit, 0, ',', '.') }}</div>
                <div class="ads-kpi-sub">Profit/order: Rp {{ number_format($totalOrders > 0 ? $totalProfit / $totalOrders : 0, 0, ',', '.') }} <span class="perf-kpi-compare" title="Perbandingan: {{ $perfCompareLabel }}">↔ Rp {{ number_format($overallPreviousOrders > 0 ? $overallPreviousProfit / $overallPreviousOrders : 0, 0, ',', '.') }} {!! $fmtDelta($totalOrders > 0 ? $totalProfit / $totalOrders : 0, $overallPreviousOrders > 0 ? $overallPreviousProfit / $overallPreviousOrders : 0, true) !!}</span></div>
                <div class="perf-kpi-compare" title="Perbandingan: {{ $perfCompareLabel }}">↔ Rp {{ number_format($overallPreviousProfit, 0, ',', '.') }} {!! $fmtDelta($totalProfit, $overallPreviousProfit, true) !!}</div>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 perf-table" style="font-size: 0.82rem;">
            <thead class="table-light sticky-top" style="z-index: 2;">
                <tr>
                    <th style="min-width:180px;">Kategori / Campaign</th>
                    <th class="text-center" data-bs-toggle="tooltip" title="Rekomendasi berdasarkan ROAS aktual vs Target ROAS">Sinyal</th>
                    <th class="text-end" data-bs-toggle="tooltip" title="ROAS impas berdasarkan HPP, fee, dan PPN 11%">BEP</th>
                    <th class="text-end" data-bs-toggle="tooltip" title="Target ROAS yang tersimpan di campaign dan bisa diedit">Target ROAS</th>
                    <th class="text-end" data-bs-toggle="tooltip" title="Modal harian campaign yang tersimpan">Modal Harian</th>
                    <th class="text-end" data-bs-toggle="tooltip" title="ROAS aktual berdasarkan GMV dibagi biaya iklan">ROAS Aktual</th>
                    <th class="text-end" data-bs-toggle="tooltip" title="Jumlah order dan perbandingannya">Orders</th>
                    <th class="text-end" data-bs-toggle="tooltip" title="Biaya iklan setelah PPN 11% · Δ vs {{ $perfCompareLabel }}">Biaya + PPN</th>
                    <th class="text-end" data-bs-toggle="tooltip" title="Rata-rata omzet per order · Δ vs {{ $perfCompareLabel }}">AOV</th>
                    <th class="text-end" data-bs-toggle="tooltip" title="Omzet dari iklan · Δ vs {{ $perfCompareLabel }}">Omzet</th>
                    <th class="text-end" data-bs-toggle="tooltip" title="Est. Net Profit & Margin · Δ vs {{ $perfCompareLabel }}">Profit</th>
                </tr>
            </thead>
            <tbody>
                <tr class="perf-overall-row" data-perf-views="category roas gms unmapped">
                    <td>
                        <div class="fw-bold">Keseluruhan Campaign</div>
                        <div class="text-muted" style="font-size:.68rem;">{{ number_format($perfInitialKpi['campaigns'], 0, ',', '.') }} campaign</div>
                    </td>
                    <td class="text-center"><span class="badge bg-primary">Total</span></td>
                    <td class="text-end">—</td>
                    <td class="text-end">—</td>
                    <td class="text-end">—</td>
                    <td class="text-end perf-cell">
                        <div class="perf-main"><span class="perf-val">{{ number_format($totalSpend > 0 ? $totalGmv / $totalSpend : 0, 2) }}x</span></div>
                        <div class="perf-previous">↔ {{ number_format($overallPreviousSpend > 0 ? $overallPreviousGmv / $overallPreviousSpend : 0, 2) }}x</div>
                    </td>
                    <td class="text-end perf-cell">
                        <div class="perf-main"><span class="perf-val">{{ number_format($totalOrders, 0, ',', '.') }}</span></div>
                        <div class="perf-previous">↔ {{ number_format($overallPreviousOrders, 0, ',', '.') }}</div>
                    </td>
                    <td class="text-end">Rp {{ number_format($totalSpend, 0, ',', '.') }}</td>
                    <td class="text-end">Rp {{ number_format($totalOrders > 0 ? $totalGmv / $totalOrders : 0, 0, ',', '.') }}</td>
                    <td class="text-end text-success">Rp {{ number_format($totalGmv, 0, ',', '.') }}</td>
                    <td class="text-end perf-cell">
                        <div class="perf-main"><span class="perf-val {{ $totalProfit >= 0 ? 'text-success' : 'text-danger' }}">Rp {{ number_format($totalProfit, 0, ',', '.') }}</span></div>
                        <div class="perf-sub">Net Profit keseluruhan</div>
                        <div class="perf-previous">↔ Rp {{ number_format($overallPreviousProfit, 0, ',', '.') }}</div>
                    </td>
                </tr>
                @foreach($perfCategoryRows as $categoryRow)
                    <tr class="perf-category-row" data-perf-views="category" onclick="perfToggleCategory(this)">
                        <td>
                            <div class="d-flex align-items-start gap-1">
                                <i class="bi bi-chevron-right perf-caret text-muted" style="font-size:.7rem; margin-top:.15rem;"></i>
                                <div class="fw-bold text-truncate" style="max-width:180px;" title="{{ $categoryRow['category'] }}">{{ $categoryRow['category'] }}</div>
                            </div>
                            <div class="text-muted" style="font-size:.68rem;">{{ number_format($categoryRow['campaign_count'], 0, ',', '.') }} campaign</div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-{{ $categoryRow['reco']['color'] }}">{{ $categoryRow['reco']['label'] }}</span>
                            @if(($categoryRow['empty_variant_count'] ?? 0) > 0)
                                <span class="badge bg-warning text-dark mt-1" title="{{ $categoryRow['empty_variant_count'] }} campaign memiliki variant kosong">Variant kosong{{ $categoryRow['empty_variant_count'] > 1 ? ' (' . $categoryRow['empty_variant_count'] . ')' : '' }}</span>
                            @endif
                        </td>
                        <td class="text-end perf-cell"><div class="perf-main"><span class="perf-val">{{ $categoryRow['bep_roas'] === null ? '—' : number_format($categoryRow['bep_roas'], 2) . 'x' }}</span></div><div class="perf-sub">impas</div></td>
                        <td class="text-end perf-cell"><div class="perf-main"><span class="perf-val">{{ $categoryRow['configured_roas'] === null ? 'Auto' : number_format($categoryRow['configured_roas'], 2) . 'x' }}</span></div><div class="perf-sub">rata-rata kategori</div></td>
                        <td class="text-end perf-cell"><div class="perf-main"><span class="perf-val">{{ $categoryRow['campaign_budget'] > 0 ? 'Rp ' . number_format($categoryRow['campaign_budget'], 0, ',', '.') : 'Unlimited' }}</span></div><div class="perf-sub">total modal harian</div></td>
                        <td class="text-end perf-cell"><div class="perf-main"><span class="perf-val">{{ number_format($categoryRow['roas'], 2) }}x</span>{!! $fmtDelta($categoryRow['roas'], $categoryRow['prev_roas'], true) !!}</div><div class="perf-sub {{ $categoryRow['actual_vs_target_pct'] !== null && $categoryRow['actual_vs_target_pct'] >= 100 ? 'text-success' : 'text-danger' }}">{{ $categoryRow['actual_vs_target_pct'] === null ? '—' : number_format($categoryRow['actual_vs_target_pct'], 1) . '% target' }}</div><div class="perf-previous" title="Perbandingan: {{ $perfCompareLabel }}">↔ {{ number_format($categoryRow['prev_roas'], 2) }}x</div></td>
                        <td class="text-end perf-cell"><div class="perf-main"><span class="perf-val">{{ number_format($categoryRow['orders'], 0, ',', '.') }}</span>{!! $fmtDelta($categoryRow['orders'], $categoryRow['prev_orders'], true) !!}</div><div class="perf-sub">CVR {{ number_format($categoryRow['cvr'], 2) }}%</div><div class="perf-previous" title="Perbandingan: {{ $perfCompareLabel }}">↔ {{ number_format($categoryRow['prev_orders'], 0, ',', '.') }}</div></td>
                        <td class="text-end perf-cell"><div class="perf-main"><span class="perf-val">Rp {{ number_format($categoryRow['spend'], 0, ',', '.') }}</span>{!! $fmtDelta($categoryRow['spend'], $categoryRow['prev_spend'], null) !!}</div><div class="perf-previous" title="Perbandingan: {{ $perfCompareLabel }}">↔ Rp {{ number_format($categoryRow['prev_spend'], 0, ',', '.') }}</div></td>
                        <td class="text-end perf-cell"><div class="perf-main"><span class="perf-val">Rp {{ number_format($categoryRow['aov'], 0, ',', '.') }}</span>{!! $fmtDelta($categoryRow['aov'], $categoryRow['prev_aov'], true) !!}</div><div class="perf-previous" title="Perbandingan: {{ $perfCompareLabel }}">↔ Rp {{ number_format($categoryRow['prev_aov'], 0, ',', '.') }}</div></td>
                        <td class="text-end perf-cell"><div class="perf-main"><span class="perf-val text-success">Rp {{ number_format($categoryRow['gmv'], 0, ',', '.') }}</span>{!! $fmtDelta($categoryRow['gmv'], $categoryRow['prev_gmv'], true) !!}</div><div class="perf-previous" title="Perbandingan: {{ $perfCompareLabel }}">↔ Rp {{ number_format($categoryRow['prev_gmv'], 0, ',', '.') }}</div></td>
                        <td class="text-end perf-cell"><div class="perf-main"><span class="perf-val {{ !$categoryRow['profit_available'] ? 'text-muted' : ($categoryRow['profit'] >= 0 ? 'text-success' : 'text-danger') }}">{{ !$categoryRow['profit_available'] ? 'N/A' : 'Rp ' . number_format($categoryRow['profit'], 0, ',', '.') }}</span>{!! $fmtDelta($categoryRow['profit'], $categoryRow['prev_profit'], true) !!}</div>@if($categoryRow['profit_available'])<div class="perf-sub">margin {{ $categoryRow['margin'] === null ? '—' : number_format($categoryRow['margin'], 1) . '%' }}</div>@endif<div class="perf-previous" title="Perbandingan: {{ $perfCompareLabel }}">↔ Rp {{ number_format($categoryRow['prev_profit'], 0, ',', '.') }}</div></td>
                    </tr>
                    <tr class="perf-category-detail" data-perf-views="category" style="display:none;">
                        <td colspan="11" class="bg-light">
                            <div class="text-muted fw-bold mb-1" style="font-size:.66rem;">Daftar campaign dalam kategori {{ $categoryRow['category'] }}</div>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0 perf-category-campaign-table">
                                    <thead>
                                        <tr>
                                            <th>Campaign / Item</th>
                                            <th class="text-end">BEP</th>
                                            <th class="text-end">Target ROAS</th>
                                            <th class="text-end">Modal Harian</th>
                                            <th class="text-end">ROAS Aktual</th>
                                            <th class="text-end">Orders</th>
                                            <th class="text-end">Biaya + PPN</th>
                                            <th class="text-end">AOV</th>
                                            <th class="text-end">GMV</th>
                                            <th class="text-end">Profit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($perfCategoryCampaignRows->get($categoryRow['category'], collect()) as $campaignRow)
                                            <tr>
                                                <td>
                                                    <div class="fw-bold text-truncate" style="max-width:260px;" title="{{ $campaignRow['name'] }}">{{ $campaignRow['name'] }}</div>
                                                    <div class="text-muted text-truncate" style="font-size:.62rem; max-width:260px;">{{ $campaignRow['item_name'] }} @if($campaignRow['type']) · {{ strtoupper($campaignRow['type']) }}@endif</div>
                                                </td>
                                                <td class="text-end">{{ $campaignRow['bep_roas'] === null ? '—' : number_format($campaignRow['bep_roas'], 2) . 'x' }}</td>
                                                <td class="text-end">
                                                    <div class="perf-inline-config" data-perf-inline-mode="roas"
                                                        data-campaign-id="{{ e($campaignRow['campaign_id']) }}"
                                                        data-store-id="{{ e((string) $campaignRow['store_id']) }}">
                                                        <div class="perf-inline-config-display" title="Klik untuk edit Target ROAS" onclick="event.stopPropagation(); openPerformanceInlineConfig(this)">
                                                            <span>{{ $campaignRow['configured_roas'] === null ? 'Auto' : number_format($campaignRow['configured_roas'], 2) . 'x' }}</span><i class="bi bi-pencil-fill"></i>
                                                        </div>
                                                        <div class="perf-inline-config-editor" data-perf-inline-mode="roas" style="display:none;" onclick="event.stopPropagation()">
                                                            <input type="text" inputmode="decimal" value="{{ $campaignRow['configured_roas'] === null ? '' : $campaignRow['configured_roas'] }}" aria-label="Target ROAS">
                                                            <i class="bi bi-check text-success" title="Simpan" onclick="savePerformanceInlineConfig(this)"></i>
                                                            <i class="bi bi-x text-danger" title="Batal" onclick="cancelPerformanceInlineConfig(this)"></i>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <div class="perf-inline-config" data-perf-inline-mode="budget"
                                                        data-campaign-id="{{ e($campaignRow['campaign_id']) }}"
                                                        data-store-id="{{ e((string) $campaignRow['store_id']) }}">
                                                        <div class="perf-inline-config-display" title="Klik untuk edit Modal Harian" onclick="event.stopPropagation(); openPerformanceInlineConfig(this)">
                                                            <span>{{ $campaignRow['campaign_budget'] > 0 ? 'Rp ' . number_format($campaignRow['campaign_budget'], 0, ',', '.') : 'Unlimited' }}</span><i class="bi bi-pencil-fill"></i>
                                                        </div>
                                                        <div class="perf-inline-config-editor" data-perf-inline-mode="budget" style="display:none;" onclick="event.stopPropagation()">
                                                            <input type="text" inputmode="decimal" value="{{ $campaignRow['campaign_budget'] }}" aria-label="Modal Harian">
                                                            <i class="bi bi-check text-success" title="Simpan" onclick="savePerformanceInlineConfig(this)"></i>
                                                            <i class="bi bi-x text-danger" title="Batal" onclick="cancelPerformanceInlineConfig(this)"></i>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-end fw-bold">
                                                    <div>{{ number_format($campaignRow['roas'], 2) }}x {!! $fmtDelta($campaignRow['roas'], $campaignRow['prev_roas'], true) !!}</div>
                                                    <div class="perf-sub {{ $campaignRow['actual_vs_target_pct'] !== null && $campaignRow['actual_vs_target_pct'] >= 100 ? 'text-success' : 'text-danger' }}">{{ $campaignRow['actual_vs_target_pct'] === null ? '—' : number_format($campaignRow['actual_vs_target_pct'], 1) . '% target' }}</div>
                                                    <div class="perf-previous" title="Perbandingan: {{ $perfCompareLabel }}">↔ {{ number_format($campaignRow['prev_roas'], 2) }}x</div>
                                                </td>
                                                <td class="text-end">
                                                    <div>{{ number_format($campaignRow['orders'], 0, ',', '.') }} {!! $fmtDelta($campaignRow['orders'], $campaignRow['prev_orders'], true) !!}</div>
                                                    <div class="perf-previous" title="Perbandingan: {{ $perfCompareLabel }}">↔ {{ number_format($campaignRow['prev_orders'], 0, ',', '.') }}</div>
                                                </td>
                                                <td class="text-end">
                                                    <div>Rp {{ number_format($campaignRow['spend'], 0, ',', '.') }} {!! $fmtDelta($campaignRow['spend'], $campaignRow['prev_spend'], null) !!}</div>
                                                    <div class="perf-previous" title="Perbandingan: {{ $perfCompareLabel }}">↔ Rp {{ number_format($campaignRow['prev_spend'], 0, ',', '.') }}</div>
                                                </td>
                                                <td class="text-end">
                                                    <div>Rp {{ number_format($campaignRow['aov'], 0, ',', '.') }} {!! $fmtDelta($campaignRow['aov'], $campaignRow['prev_aov'], true) !!}</div>
                                                    <div class="perf-previous" title="Perbandingan: {{ $perfCompareLabel }}">↔ Rp {{ number_format($campaignRow['prev_aov'], 0, ',', '.') }}</div>
                                                </td>
                                                <td class="text-end text-success">
                                                    <div>Rp {{ number_format($campaignRow['gmv'], 0, ',', '.') }} {!! $fmtDelta($campaignRow['gmv'], $campaignRow['prev_gmv'], true) !!}</div>
                                                    <div class="perf-previous" title="Perbandingan: {{ $perfCompareLabel }}">↔ Rp {{ number_format($campaignRow['prev_gmv'], 0, ',', '.') }}</div>
                                                </td>
                                                <td class="text-end {{ !$campaignRow['profit_available'] ? 'text-muted' : ($campaignRow['profit'] >= 0 ? 'text-success' : 'text-danger') }}">
                                                    <div>{{ !$campaignRow['profit_available'] ? 'N/A' : 'Rp ' . number_format($campaignRow['profit'], 0, ',', '.') }} {!! $fmtDelta($campaignRow['profit'], $campaignRow['prev_profit'], true) !!}</div>
                                                    <div class="perf-previous" title="Perbandingan: {{ $perfCompareLabel }}">↔ Rp {{ number_format($campaignRow['prev_profit'], 0, ',', '.') }}</div>
                                                </td>
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
                            @if($row['variant_empty'])
                                <span class="badge bg-warning text-dark mt-1" title="Produk memiliki variant kosong">Variant kosong</span>
                            @endif
                        </td>

                        {{-- BEP --}}
                        <td class="text-end perf-cell">
                            <div class="perf-main"><span class="perf-val">{{ $row['bep_roas'] === null ? '—' : number_format($row['bep_roas'], 2) . 'x' }}</span></div>
                            <div class="perf-sub">impas</div>
                        </td>

                        {{-- Target ROAS tersimpan --}}
                        <td class="text-end">
                            <div class="perf-inline-config" data-perf-inline-mode="roas"
                                data-campaign-id="{{ e($row['campaign_id']) }}"
                                data-store-id="{{ e((string) $row['store_id']) }}">
                                <div class="perf-inline-config-display" title="Klik untuk edit Target ROAS" onclick="event.stopPropagation(); openPerformanceInlineConfig(this)">
                                    <span class="fw-bold">{{ $row['configured_roas'] === null ? 'Auto' : number_format($row['configured_roas'], 2) . 'x' }}</span><i class="bi bi-pencil-fill"></i>
                                </div>
                                <div class="perf-inline-config-editor" data-perf-inline-mode="roas" style="display:none;" onclick="event.stopPropagation()">
                                    <input type="text" inputmode="decimal" value="{{ $row['configured_roas'] === null ? '' : $row['configured_roas'] }}" aria-label="Target ROAS">
                                    <i class="bi bi-check text-success" title="Simpan" onclick="savePerformanceInlineConfig(this)"></i>
                                    <i class="bi bi-x text-danger" title="Batal" onclick="cancelPerformanceInlineConfig(this)"></i>
                                </div>
                            </div>
                        </td>

                        {{-- Modal Harian --}}
                        <td class="text-end">
                            <div class="perf-inline-config" data-perf-inline-mode="budget"
                                data-campaign-id="{{ e($row['campaign_id']) }}"
                                data-store-id="{{ e((string) $row['store_id']) }}">
                                <div class="perf-inline-config-display" title="Klik untuk edit Modal Harian" onclick="event.stopPropagation(); openPerformanceInlineConfig(this)">
                                    <span>{{ $row['campaign_budget'] > 0 ? 'Rp ' . number_format($row['campaign_budget'], 0, ',', '.') : 'Unlimited' }}</span><i class="bi bi-pencil-fill"></i>
                                </div>
                                <div class="perf-inline-config-editor" data-perf-inline-mode="budget" style="display:none;" onclick="event.stopPropagation()">
                                    <input type="text" inputmode="decimal" value="{{ $row['campaign_budget'] }}" aria-label="Modal Harian">
                                    <i class="bi bi-check text-success" title="Simpan" onclick="savePerformanceInlineConfig(this)"></i>
                                    <i class="bi bi-x text-danger" title="Batal" onclick="cancelPerformanceInlineConfig(this)"></i>
                                </div>
                            </div>
                        </td>

                        {{-- ROAS Aktual --}}
                        <td class="text-end perf-cell">
                            <div class="perf-main"><span class="perf-val {{ $row['target_roas'] === null ? '' : ($meetsTarget ? 'text-success' : 'text-danger') }}">{{ number_format($row['roas'], 2) }}x</span>{!! $fmtDelta($row['roas'], $row['prev_roas'], true) !!}</div>
                            <div class="perf-sub {{ $row['actual_vs_target_pct'] !== null && $row['actual_vs_target_pct'] >= 100 ? 'text-success' : 'text-danger' }}">{{ $row['actual_vs_target_pct'] === null ? '—' : number_format($row['actual_vs_target_pct'], 1) . '% target' }}</div>
                            <div class="perf-previous" title="Perbandingan: {{ $perfCompareLabel }}">↔ {{ number_format($row['prev_roas'], 2) }}x</div>
                        </td>

                        {{-- Orders --}}
                        <td class="text-end perf-cell">
                            <div class="perf-main"><span class="perf-val">{{ number_format($row['orders'], 0, ',', '.') }}</span>{!! $fmtDelta($row['orders'], $row['prev_orders'], true) !!}</div>
                            <div class="perf-sub">CVR {{ number_format($row['cvr'], 2) }}%</div>
                            <div class="perf-previous" title="Perbandingan: {{ $perfCompareLabel }}">↔ {{ number_format($row['prev_orders'], 0, ',', '.') }}</div>
                        </td>

                        {{-- Biaya setelah PPN 11% --}}
                        <td class="text-end perf-cell">
                            <div class="perf-main"><span class="perf-val">Rp {{ number_format($row['spend'], 0, ',', '.') }}</span>{!! $fmtDelta($row['spend'], $row['prev_spend'], null) !!}</div>
                            <div class="perf-previous" title="Perbandingan: {{ $perfCompareLabel }}">↔ Rp {{ number_format($row['prev_spend'], 0, ',', '.') }}</div>
                        </td>

                        {{-- AOV --}}
                        <td class="text-end perf-cell">
                            <div class="perf-main"><span class="perf-val">Rp {{ number_format($row['aov'], 0, ',', '.') }}</span>{!! $fmtDelta($row['aov'], $row['prev_aov'], true) !!}</div>
                            <div class="perf-previous" title="Perbandingan: {{ $perfCompareLabel }}">↔ Rp {{ number_format($row['prev_aov'], 0, ',', '.') }}</div>
                        </td>

                        {{-- Omzet --}}
                        <td class="text-end perf-cell">
                            <div class="perf-main"><span class="perf-val text-success">Rp {{ number_format($row['gmv'], 0, ',', '.') }}</span>{!! $fmtDelta($row['gmv'], $row['prev_gmv'], true) !!}</div>
                            <div class="perf-previous" title="Perbandingan: {{ $perfCompareLabel }}">↔ Rp {{ number_format($row['prev_gmv'], 0, ',', '.') }}</div>
                        </td>

                        {{-- Profit --}}
                        <td class="text-end perf-cell">
                            <div class="perf-main"><span class="perf-val {{ !$row['profit_available'] ? 'text-muted' : ($row['profit'] >= 0 ? 'text-success' : 'text-danger') }}">{{ !$row['profit_available'] ? 'N/A' : 'Rp ' . number_format($row['profit'], 0, ',', '.') }}</span>@if($row['profit_available']){!! $fmtDelta($row['profit'], $row['prev_profit'], true) !!}@endif</div>
                            @if($row['profit_available'])
                                <div class="perf-sub">margin {{ $row['margin'] === null ? '—' : number_format($row['margin'], 1) . '%' }}</div>
                            @endif
                            <div class="perf-previous" title="Perbandingan: {{ $perfCompareLabel }}">↔ Rp {{ number_format($row['prev_profit'], 0, ',', '.') }}</div>
                        </td>
                    </tr>

                    {{-- Detail (expand) --}}
                    <tr class="perf-detail" data-perf-views="{{ implode(' ', $row['performance_views'] ?? []) }}" style="display:none;">
                        <td colspan="11" class="bg-light">
                            <div class="perf-chips">
                                <div class="perf-chip"><span>Jangkauan</span><b>{{ number_format($row['impressions'], 0, ',', '.') }}</b>{!! $fmtDelta($row['impressions'], $row['prev_impressions'], true) !!}</div>
                                <div class="perf-chip"><span>CPM</span><b>Rp {{ number_format($row['cpm'], 0, ',', '.') }}</b></div>
                                <div class="perf-chip"><span>Klik</span><b>{{ number_format($row['clicks'], 0, ',', '.') }}</b>{!! $fmtDelta($row['clicks'], $row['prev_clicks'], true) !!}</div>
                                <div class="perf-chip"><span>CPC</span><b>Rp {{ number_format($row['cpc'], 0, ',', '.') }}</b></div>
                                <div class="perf-chip"><span>CTR</span><b>{{ number_format($row['ctr'], 2) }}%</b></div>
                                <div class="perf-chip"><span>CPA</span><b>Rp {{ number_format($row['cpa'], 0, ',', '.') }}</b></div>
                                <div class="perf-chip"><span>POAS</span><b>{{ $row['poas'] === null ? '—' : number_format($row['poas'], 2) . 'x' }}</b></div>
                                <div class="perf-chip"><span>Biaya riil (PPN 11%)</span><b>Rp {{ number_format($row['spend_ppn'], 0, ',', '.') }}</b>{!! $fmtDelta($row['spend'], $row['prev_spend'], null) !!}</div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr class="perf-no-data">
                        <td colspan="11" class="text-center py-4 text-muted">Belum ada data performa campaign yang memadai.</td>
                    </tr>
                @endforelse
                <tr class="perf-segment-empty" style="display:none;">
                    <td colspan="11" class="text-center py-4 text-muted">Belum ada data untuk kategori ini.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="campaignPerformanceEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px; background:var(--card-bg); color:var(--text); border:1px solid var(--dsh-border);">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold">Edit Target Campaign</h5>
                    <div id="campaignPerformanceEditName" class="text-muted" style="font-size:.72rem;"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form id="campaignPerformanceEditForm" onsubmit="submitCampaignPerformanceEdit(event)">
                <div class="modal-body">
                    <input type="hidden" id="campaignPerformanceEditStoreId">
                    <input type="hidden" id="campaignPerformanceEditCampaignId">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Target ROAS</label>
                        <input type="text" inputmode="decimal" id="campaignPerformanceEditRoas" class="form-control" placeholder="Contoh: 8,5 atau 0 untuk Auto">
                        <div class="form-text">Gunakan 0 atau kosong untuk mempertahankan Auto.</div>
                    </div>
                    <div class="mb-1">
                        <label class="form-label small fw-bold">Modal Harian</label>
                        <input type="text" inputmode="decimal" id="campaignPerformanceEditBudget" class="form-control" placeholder="Contoh: 150.000">
                        <div class="form-text">Isi 0 untuk Unlimited. Angka dengan koma/titik akan dinormalisasi.</div>
                    </div>
                    <div id="campaignPerformanceEditError" class="alert alert-danger py-2 px-3 mt-3 mb-0" style="display:none; font-size:.75rem;"></div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="campaignPerformanceEditSubmit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function performanceEditNormalizeNumber(value) {
    let normalized = String(value ?? '').trim().replace(/\s+/g, '').replace(/[^0-9,.-]/g, '');
    if (!normalized) return '';

    if (normalized.includes(',') && normalized.includes('.')) {
        normalized = normalized.replace(/\./g, '').replace(',', '.');
    } else if (normalized.includes(',')) {
        normalized = normalized.replace(',', '.');
    } else if ((normalized.match(/\./g) || []).length > 1) {
        normalized = normalized.replace(/\./g, '');
    }

    return normalized;
}

function performanceEditCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || document.querySelector('input[name="_token"]')?.value
        || '';
}

function performanceEditResponseMessage(rawBody, status) {
    if (/<html|502\s+Bad\s+Gateway|504\s+Gateway/i.test(String(rawBody || ''))) {
        return `Gateway error (HTTP ${status}). Server/API iklan terlalu lama merespons; coba lagi.`;
    }
    return rawBody;
}

function performanceEditEscapeAttribute(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

function performanceEditAlert(icon, title, message) {
    if (window.Swal) {
        window.Swal.fire({
            icon,
            title,
            text: String(message || ''),
            confirmButtonColor: '#1e293b',
            timer: icon === 'success' ? 2600 : undefined,
            timerProgressBar: icon === 'success',
        });
        return;
    }
    if (typeof window.getToast === 'function') window.getToast(`${title}: ${message || ''}`);
}

window.openPerformanceInlineConfig = function (display) {
    const wrapper = display?.closest('.perf-inline-config');
    if (!wrapper || wrapper.__perfInlineEditing) return;

    const editor = wrapper.querySelector('.perf-inline-config-editor');
    const input = editor?.querySelector('input');
    if (!editor || !input) return;

    wrapper.__perfInlineEditing = true;
    wrapper.dataset.perfInlineValue = input.value;
    display.style.display = 'none';
    editor.style.display = 'inline-flex';
    input.focus();
    input.select();
    input.onkeydown = event => {
        if (event.key === 'Enter') {
            event.preventDefault();
            savePerformanceInlineConfig(editor.querySelector('.text-success'));
        }
        if (event.key === 'Escape') cancelPerformanceInlineConfig(editor.querySelector('.text-danger'));
    };
};

window.cancelPerformanceInlineConfig = function (icon) {
    const wrapper = icon?.closest('.perf-inline-config');
    if (!wrapper) return;

    const display = wrapper.querySelector('.perf-inline-config-display');
    const editor = wrapper.querySelector('.perf-inline-config-editor');
    const input = editor?.querySelector('input');
    if (input && wrapper.dataset.perfInlineValue !== undefined) input.value = wrapper.dataset.perfInlineValue;
    if (editor) editor.style.display = 'none';
    if (display) display.style.display = 'inline-flex';
    wrapper.__perfInlineEditing = false;
};

window.savePerformanceInlineConfig = function (icon) {
    const wrapper = icon?.closest('.perf-inline-config');
    if (!wrapper) return;

    const mode = wrapper.dataset.perfInlineMode === 'budget' ? 'budget' : 'roas';
    const input = wrapper.querySelector('.perf-inline-config-editor input');
    const rawValue = performanceEditNormalizeNumber(input?.value);
    const value = rawValue === '' ? '0' : rawValue;
    const storeId = wrapper.dataset.storeId || '';
    const campaignId = wrapper.dataset.campaignId || '';
    const errorMessage = message => {
        performanceEditAlert('error', 'Gagal menyimpan', message);
        wrapper.__perfInlineEditing = false;
    };

    if (!storeId || !campaignId) return errorMessage('Store atau campaign tidak ditemukan.');

    const routes = window.AdsDashboardRoutes || {};
    const isGms = String(campaignId).toUpperCase().startsWith('GMS-');
    const route = isGms ? routes.gmsCampaignEdit : routes.cpcCampaignEdit;
    if (!route) return errorMessage('Endpoint edit campaign tidak tersedia.');

    const editGuard = window.__adsCampaignEditGuard;
    const editGuardKey = `${storeId}:${campaignId}`;
    if (editGuard && !editGuard.acquire(editGuardKey)) {
        return errorMessage('Tunggu sebentar sebelum mengubah campaign lagi agar tidak terkena batas API.');
    }

    const payload = {
        store_id: storeId,
        campaign_id: campaignId,
        roas_target: mode === 'roas' ? value : '',
        daily_budget: mode === 'budget' ? value : '',
    };
    const display = wrapper.querySelector('.perf-inline-config-display');
    const editor = wrapper.querySelector('.perf-inline-config-editor');
    const originalDisplay = display?.innerHTML || '';
    if (display) display.innerHTML = '<span class="spinner-border spinner-border-sm text-primary"></span>';
    if (editor) editor.style.display = 'none';
    if (display) display.style.display = 'inline-flex';

    fetch(route, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': performanceEditCsrfToken(),
        },
        body: JSON.stringify(payload),
    })
        .then(async response => {
            const rawBody = await response.text();
            let data = {};
            try { data = rawBody ? JSON.parse(rawBody) : {}; } catch (error) {
                data = { message: performanceEditResponseMessage(rawBody, response.status) };
            }
            const validationMessages = Object.values(data.errors || {}).flat().filter(Boolean).join(' ');
            if (!response.ok || data.status !== 'success') {
                const detail = [data.message, validationMessages].filter(Boolean).join(' ');
                throw new Error(detail || `Gagal menyimpan (HTTP ${response.status}).`);
            }
            return data;
        })
        .then(data => {
            wrapper.dataset.perfInlineValue = value;
            if (display) {
                const formatted = mode === 'budget'
                    ? (Number(value) > 0 ? 'Rp ' + Number(value).toLocaleString('id-ID', { maximumFractionDigits: 0 }) : 'Unlimited')
                    : (Number(value) > 0 ? Number(value).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + 'x' : 'Auto');
                display.innerHTML = `<span>${formatted}</span><i class="bi bi-pencil-fill"></i>`;
            }
            wrapper.__perfInlineEditing = false;
            performanceEditAlert('success', 'Berhasil diperbarui', data.message || 'Pengaturan campaign berhasil disimpan.');
        })
        .catch(error => {
            if (display) display.innerHTML = originalDisplay;
            if (display) display.style.display = 'inline-flex';
            wrapper.__perfInlineEditing = false;
            performanceEditAlert('error', 'Gagal menyimpan', error.message || 'Terjadi kesalahan saat menyimpan.');
        })
        .finally(() => {
            editGuard?.release(editGuardKey);
        });
};

window.openCampaignPerformanceInlineEdit = function (button, mode = 'roas') {
    const cell = button?.closest('td');
    if (!cell || cell.__inlineEditData) return;

    const isBudget = mode === 'budget';
    const value = isBudget ? (button.dataset.dailyBudget || '0') : (button.dataset.targetRoas || '');
    const edit = {
        button,
        cell,
        mode: isBudget ? 'budget' : 'roas',
        originalHtml: cell.innerHTML,
        storeId: button.dataset.storeId || '',
        campaignId: button.dataset.campaignId || '',
    };
    cell.__inlineEditData = edit;
    cell.innerHTML = `
        <div class="campaign-performance-inline-editor" onclick="event.stopPropagation()">
            <label>${isBudget ? 'Modal Harian' : 'Target ROAS'}</label>
            <input type="text" inputmode="decimal" class="form-control" data-inline-field="${isBudget ? 'budget' : 'roas'}" value="${performanceEditEscapeAttribute(value)}" placeholder="${isBudget ? '0 = unlimited' : 'Auto'}">
            <div class="d-flex gap-1">
                <button type="button" class="btn btn-primary" onclick="event.stopPropagation(); submitCampaignPerformanceInlineEdit(this)"><i class="bi bi-check2"></i> Simpan</button>
                <button type="button" class="btn btn-light" onclick="event.stopPropagation(); cancelCampaignPerformanceInlineEdit(this)"><i class="bi bi-x"></i> Batal</button>
            </div>
            <div class="inline-edit-error text-danger mt-1" style="display:none; font-size:.58rem; line-height:1.25;"></div>
        </div>`;
    cell.querySelector(`[data-inline-field="${isBudget ? 'budget' : 'roas'}"]`)?.focus();
};

window.cancelCampaignPerformanceInlineEdit = function (button) {
    const cell = button?.closest('td');
    const edit = cell?.__inlineEditData;
    if (!edit) return;
    edit.cell.innerHTML = edit.originalHtml;
    delete edit.cell.__inlineEditData;
};

window.submitCampaignPerformanceInlineEdit = function (button) {
    const cell = button?.closest('td');
    const edit = cell?.__inlineEditData;
    if (!cell || !edit) return;

    const rawValue = performanceEditNormalizeNumber(edit.cell.querySelector('[data-inline-field]')?.value);
    const value = rawValue === '' ? '0' : rawValue;
    const errorEl = edit.cell.querySelector('.inline-edit-error');
    const setError = message => {
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.style.display = 'block';
        }
    };

    if (!edit.storeId || !edit.campaignId) return setError('Store atau campaign tidak ditemukan.');

    const routes = window.AdsDashboardRoutes || {};
    const isGms = String(edit.campaignId).toUpperCase().startsWith('GMS-');
    const route = isGms ? routes.gmsCampaignEdit : routes.cpcCampaignEdit;
    if (!route) return setError('Endpoint edit campaign tidak tersedia.');

    const payload = {
        store_id: edit.storeId,
        campaign_id: edit.campaignId,
        roas_target: edit.mode === 'roas' ? value : '',
        daily_budget: edit.mode === 'budget' ? value : '',
    };
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    fetch(route, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': performanceEditCsrfToken(),
        },
        body: JSON.stringify(payload),
    })
        .then(async response => {
            const rawBody = await response.text();
            let data = {};
            try { data = rawBody ? JSON.parse(rawBody) : {}; } catch (error) { data = { message: performanceEditResponseMessage(rawBody, response.status) }; }
            const validationMessages = Object.values(data.errors || {}).flat().filter(Boolean).join(' ');
            if (!response.ok || data.status !== 'success') {
                throw new Error(data.message || validationMessages || `Gagal menyimpan (HTTP ${response.status}).`);
            }
            return data;
        })
        .then(data => {
            if (edit.mode === 'roas') {
                edit.button.dataset.targetRoas = Number(value) > 0 ? value : '';
            } else {
                edit.button.dataset.dailyBudget = Number(value) > 0 ? value : '0';
            }
            edit.cell.innerHTML = edit.originalHtml;
            const restoredButton = edit.cell.querySelector('.perf-edit-target');
            const valueEl = edit.cell.querySelector(':scope > div');
            if (restoredButton) {
                if (edit.mode === 'roas') restoredButton.dataset.targetRoas = edit.button.dataset.targetRoas || '';
                if (edit.mode === 'budget') restoredButton.dataset.dailyBudget = edit.button.dataset.dailyBudget || '0';
            }
            if (valueEl && edit.mode === 'roas') valueEl.textContent = Number(value) > 0
                ? Number(value).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + 'x'
                : 'Auto';
            if (valueEl && edit.mode === 'budget') valueEl.textContent = Number(value) > 0
                ? 'Rp ' + Number(value).toLocaleString('id-ID', { maximumFractionDigits: 0 })
                : 'Unlimited';
            delete edit.cell.__inlineEditData;
            if (typeof window.getToast === 'function') window.getToast(data.message || 'Pengaturan campaign berhasil disimpan.');
        })
        .catch(error => {
            setError(error.message);
            button.disabled = false;
            button.innerHTML = '<i class="bi bi-check2"></i> Simpan';
        });
};

window.openCampaignPerformanceEdit = function (button) {
    const modalEl = document.getElementById('campaignPerformanceEditModal');
    if (!modalEl || !button) return;

    window.__campaignPerformanceEditTrigger = button;
    document.getElementById('campaignPerformanceEditStoreId').value = button.dataset.storeId || '';
    document.getElementById('campaignPerformanceEditCampaignId').value = button.dataset.campaignId || '';
    document.getElementById('campaignPerformanceEditRoas').value = button.dataset.targetRoas || '';
    document.getElementById('campaignPerformanceEditBudget').value = button.dataset.dailyBudget || '0';
    document.getElementById('campaignPerformanceEditName').textContent = button.dataset.campaignName || 'Campaign';
    document.getElementById('campaignPerformanceEditError').style.display = 'none';

    if (typeof bootstrap !== 'undefined') {
        (bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl)).show();
    }
};

window.submitCampaignPerformanceEdit = function (event) {
    event.preventDefault();

    const storeId = document.getElementById('campaignPerformanceEditStoreId').value;
    const campaignId = document.getElementById('campaignPerformanceEditCampaignId').value;
    const roas = performanceEditNormalizeNumber(document.getElementById('campaignPerformanceEditRoas').value);
    const budget = performanceEditNormalizeNumber(document.getElementById('campaignPerformanceEditBudget').value);
    const trigger = window.__campaignPerformanceEditTrigger;
    const initialBudget = performanceEditNormalizeNumber(trigger?.dataset.dailyBudget || '');
    const budgetCompare = value => value === '' || Number(value) === 0 ? '0' : value;
    const budgetChanged = budgetCompare(budget) !== budgetCompare(initialBudget);
    const errorEl = document.getElementById('campaignPerformanceEditError');
    const submitButton = document.getElementById('campaignPerformanceEditSubmit');

    if (!storeId || !campaignId) {
        errorEl.textContent = 'Store atau campaign tidak ditemukan.';
        errorEl.style.display = 'block';
        return;
    }
    if (roas === '' && budget === '') {
        errorEl.textContent = 'Isi minimal Target ROAS atau Modal Harian.';
        errorEl.style.display = 'block';
        return;
    }

    const routes = window.AdsDashboardRoutes || {};
    const isGms = String(campaignId).toUpperCase().startsWith('GMS-');
    const route = isGms ? routes.gmsCampaignEdit : routes.cpcCampaignEdit;
    const payload = {
        store_id: storeId,
        campaign_id: campaignId,
        roas_target: roas,
        daily_budget: budgetChanged ? budget : '',
    };

    submitButton.disabled = true;
    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
    errorEl.style.display = 'none';

    fetch(route, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': performanceEditCsrfToken(),
        },
        body: JSON.stringify(payload),
    })
    .then(async response => {
        const rawBody = await response.text();
        let data = {};
        try {
            data = rawBody ? JSON.parse(rawBody) : {};
        } catch (parseError) {
            data = { message: performanceEditResponseMessage(rawBody, response.status) };
        }
        const validationMessages = Object.values(data.errors || {})
            .flat()
            .filter(Boolean)
            .join(' ');
        if (!response.ok || data.status !== 'success') {
            throw new Error(data.message || validationMessages || `Gagal menyimpan pengaturan campaign (HTTP ${response.status}).`);
        }
        return data;
    })
    .then(data => {
        if (trigger && roas !== '') {
            trigger.dataset.targetRoas = roas;
            const valueEl = trigger.closest('td')?.querySelector('div');
            if (valueEl) valueEl.textContent = Number(roas) > 0 ? Number(roas).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + 'x' : 'Auto';
        }
        if (typeof window.getToast === 'function') window.getToast(data.message || 'Pengaturan campaign berhasil disimpan.');
        const modalEl = document.getElementById('campaignPerformanceEditModal');
        if (typeof bootstrap !== 'undefined' && modalEl) (bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl)).hide();
    })
    .catch(error => {
        errorEl.textContent = error.message;
        errorEl.style.display = 'block';
    })
    .finally(() => {
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="bi bi-save"></i> Simpan Perubahan';
    });
};

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

const perfKpiData = {!! json_encode($perfSegmentKpis) !!};
let campaignScatterChart = null;
let campaignScatterData = [];
let perfActiveSegment = 'category';

const campaignScatterColorMap = {
    'Scale': 'rgba(25, 135, 84, 0.7)',
    'Aman': 'rgba(13, 202, 240, 0.7)',
    'Optimasi': 'rgba(255, 193, 7, 0.7)',
    'Stop': 'rgba(220, 53, 69, 0.7)',
    'Data HPP': 'rgba(108, 117, 125, 0.7)'
};

function perfKpiNumber(value, decimals) {
    return Number(value || 0).toLocaleString('id-ID', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });
}

const perfCompareLabel = {!! json_encode($perfCompareLabel) !!};

function perfKpiCompare(current, previous, formatter, direction) {
    const currentValue = Number(current || 0);
    const previousValue = Number(previous || 0);
    if (previousValue === 0) {
        return '↔ ' + formatter(previousValue) + ' <span class="text-muted">—</span>';
    }

    const change = ((currentValue - previousValue) / Math.abs(previousValue)) * 100;
    const up = change >= 0;
    const arrow = up ? '▲' : '▼';
    let className = 'text-muted';
    if (direction !== null && Math.abs(change) >= 0.5) {
        const good = direction ? up : !up;
        className = good ? 'text-success' : 'text-danger';
    }

    return '↔ ' + formatter(previousValue)
        + ' <span class="' + className + ' perf-delta">' + arrow + ' ' + Math.abs(change).toFixed(0) + '%</span>';
}

function perfUpdateKpis(segment) {
    const kpi = perfKpiData[segment] || perfKpiData.category || {};
    const setValue = function(key, value) {
        document.querySelectorAll('[data-perf-kpi-value="' + key + '"]').forEach(function(el) {
            el.textContent = value;
        });
    };

    setValue('campaigns', perfKpiNumber(kpi.campaigns, 0));
    setValue('losses', perfKpiNumber(kpi.losses, 0));
    setValue('spend', 'Rp ' + perfKpiNumber(kpi.spend, 0));
    setValue('gmv', 'Rp ' + perfKpiNumber(kpi.gmv, 0));
    setValue('aov', 'Rp ' + perfKpiNumber(kpi.aov, 0));
    setValue('cpa', 'Rp ' + perfKpiNumber(kpi.cpa, 0));
    setValue('orders', perfKpiNumber(kpi.orders, 0));
    setValue('roas', perfKpiNumber(kpi.roas, 2) + 'x');
    setValue('profit', 'Rp ' + perfKpiNumber(kpi.profit, 0));
    setValue('profit_per_order', 'Rp ' + perfKpiNumber(kpi.profit_per_order, 0));
    setValue('profit_unknown', perfKpiNumber(kpi.profit_unknown, 0));

    const compareValues = {
        cpa: perfKpiCompare(kpi.cpa, kpi.prev_cpa, value => 'Rp ' + perfKpiNumber(value, 0), null),
        aov: perfKpiCompare(kpi.aov, kpi.prev_aov, value => 'Rp ' + perfKpiNumber(value, 0), true),
        roas: perfKpiCompare(kpi.roas, kpi.prev_roas, value => perfKpiNumber(value, 2) + 'x', true),
        profit: perfKpiCompare(kpi.profit, kpi.prev_profit, value => 'Rp ' + perfKpiNumber(value, 0), true),
        profit_per_order: perfKpiCompare(kpi.profit_per_order, kpi.prev_profit_per_order, value => 'Rp ' + perfKpiNumber(value, 0), true),
    };
    Object.entries(compareValues).forEach(function([key, value]) {
        document.querySelectorAll('[data-perf-kpi-compare="' + key + '"]').forEach(function(el) {
            el.innerHTML = value;
        });
    });

    const profitValue = document.querySelector('[data-perf-kpi-value="profit"]');
    if (profitValue) {
        profitValue.classList.remove('text-warning', 'text-success', 'text-danger');
        profitValue.classList.add(Number(kpi.profit_unknown || 0) > 0
            ? 'text-warning'
            : Number(kpi.profit || 0) >= 0 ? 'text-success' : 'text-danger');
    }
}

function perfApplySegment(segment) {
    const rows = Array.from(document.querySelectorAll('.perf-row, .perf-category-row, .perf-overall-row, .perf-reconciliation-row'));
    const categoryDetails = Array.from(document.querySelectorAll('.perf-category-detail'));
    const emptyState = document.querySelector('.perf-segment-empty');
    const noDataState = document.querySelector('.perf-no-data');

    perfUpdateKpis(segment);
    perfRenderScatter(segment);

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
        // Tab kategori hanya menampilkan baris agregasi kategori. Baris campaign
        // detail tetap tersedia melalui accordion kategori, agar detail CPC dan
        // metrik campaign tidak ikut muncul di tampilan utama kategori.
        const isCategoryView = segment === 'category';
        const isVisible = row.classList.contains('perf-overall-row')
            ? true
            : row.classList.contains('perf-reconciliation-row')
            ? isCategoryView
            : isCategoryView
            ? row.classList.contains('perf-category-row')
            : row.classList.contains('perf-row')
                && (row.dataset.perfViews || '').split(/\s+/).includes(segment);
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
    perfActiveSegment = view;
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
    campaignScatterData = {!! $scatterDataJson !!};

    const ctx = document.getElementById('campaignScatterChart');
    if (!ctx) return;

    campaignScatterChart = new Chart(ctx, {
        type: 'bubble',
        data: {
            datasets: []
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const data = context.raw;
                            const money = value => value === null || value === undefined
                                ? 'N/A'
                                : 'Rp ' + Number(value).toLocaleString('id-ID', { maximumFractionDigits: 0 });
                            const ratio = value => value === null || value === undefined
                                ? 'N/A'
                                : Number(value).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + 'x';
                            return [
                                data.name,
                                'Biaya iklan: ' + money(data.x),
                                'Net profit: ' + (data.profit_available ? money(data.profit) : 'N/A · HPP belum tersedia'),
                                'ROAS: ' + ratio(data.roas) + (data.target_roas !== null && data.target_roas !== undefined ? ' · Target: ' + ratio(data.target_roas) : ''),
                                'Biaya/order: ' + money(data.cpa),
                                'Profit/order: ' + money(data.profit_per_order),
                                'Orders: ' + Number(data.orders || 0).toLocaleString('id-ID')
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
                    title: { display: true, text: 'Biaya Iklan setelah PPN (Rp)' },
                    ticks: { callback: function(value) { return 'Rp ' + (value/1000).toLocaleString('id-ID') + 'k'; } }
                },
                y: {
                    title: { display: true, text: 'Net Profit Terhitung (Rp)' },
                    ticks: { callback: function(value) { return 'Rp ' + (value/1000).toLocaleString('id-ID') + 'k'; } },
                    grid: { color: function(context) { return context.tick.value === 0 ? '#ff0000' : 'rgba(0,0,0,0.1)'; } }
                }
            }
        }
    });

    perfRenderScatter(perfActiveSegment);
});

function perfRenderScatter(segment) {
    if (!campaignScatterChart) return;

    const viewLabels = {
        category: 'Per Kategori',
        roas: 'GMV Max ROAS',
        gms: 'GMV Max Auto',
        unmapped: 'Produk Bermasalah',
    };
    const visibleData = campaignScatterData.filter(function(data) {
        return Array.isArray(data.views) && data.views.includes(segment);
    });

    campaignScatterChart.data.datasets = Object.keys(campaignScatterColorMap).map(function(reco) {
        const color = campaignScatterColorMap[reco];
        return {
            label: reco,
            data: visibleData.filter(data => data.reco === reco),
            backgroundColor: color,
            borderColor: color.replace('0.7', '1'),
            borderWidth: 1
        };
    });
    campaignScatterChart.update('none');

    const hint = document.getElementById('campaignScatterHint');
    if (hint) hint.textContent = 'Subtab: ' + (viewLabels[segment] || 'Per Kategori');
}
</script>
