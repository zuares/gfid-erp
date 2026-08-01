
@php
    // Fallback variables to prevent undefined errors when no active store
    $campaigns = $campaigns ?? collect();
    $kpi = $kpi ?? [];
    $adsSetting = $adsSetting ?? (object) [];
    $storeId = $storeId ?? 'all';

    /*
    |--------------------------------------------------------------------
    | SATU SUMBER KEBENARAN
    | Semua angka (KPI atas + tabel) dihitung dari formula yang SAMA:
    |   Net Profit = Pendapatan Bersih − HPP − (Iklan + PPN 11%)
    | supaya angka ringkasan selalu cocok dengan penjumlahan tabel.
    |--------------------------------------------------------------------
    */
    $rows = collect();
    foreach ($campaigns as $camp) {
        if (!($camp->spend > 0 || $camp->gmv > 0)) continue;

        // Gunakan hasil kalkulasi service sebagai satu-satunya sumber
        // kebenaran. Jangan hitung ulang formula profit di Blade karena
        // syaratnya mudah berbeda dari KPI service.
        $netRevRatio   = $camp->net_revenue_ratio ?? 0.781;
        $netRevenue    = (float) ($camp->net_revenue ?? ($camp->gmv * $netRevRatio));
        $itemsSold     = (int) ($camp->items_sold ?? 0);
        $unitCogsRow   = (float) ($camp->unit_cogs ?? 0);
        $cogsExact     = $unitCogsRow > 0 && $itemsSold > 0 && ($camp->items_sold_source ?? 'api') === 'api';
        $totalCogsRow  = $camp->total_cogs !== null
            ? (float) $camp->total_cogs
            : null;
        $spendAfterTax = $camp->spend * 1.11; // PPN 11% topup iklan
        $profit        = $camp->profit_after_ads !== null
            ? (float) $camp->profit_after_ads
            : null;
        $acos          = $camp->gmv > 0 ? ($camp->spend / $camp->gmv) * 100 : 0;
        $beAcos        = $camp->break_even_acos_pct; // batas aman (sudah faktor pajak)

        $rows->push((object) [
            'camp'          => $camp,
            'feePct'        => max(0, (1 - $netRevRatio) * 100), // potongan admin marketplace
            'feeIsEstimate' => ($camp->net_revenue_ratio_source ?? 'default') === 'default', // default global — bukan settlement item / set manual
            'unitCogs'      => $unitCogsRow,
            'itemsSold'     => $itemsSold,
            'cogsExact'     => $cogsExact,
            'netRevenue'    => $netRevenue,
            'totalCogs'     => $totalCogsRow,
            'spendAfterTax' => $spendAfterTax,
            'aov'           => $camp->orders > 0 ? $camp->gmv / $camp->orders : 0,
            'aovNet'        => $camp->orders > 0 ? $netRevenue / $camp->orders : 0,
            'acos'          => $acos,
            'beAcos'        => $beAcos,
            'acosBarPct'    => ($beAcos && $beAcos > 0) ? min(100, ($acos / $beAcos) * 100) : 0,
            'acosOk'        => $beAcos !== null ? $acos <= $beAcos : null,
            'profit'        => $profit,
            'isProfitable'  => $profit === null ? null : $profit >= 0,
        ]);
    }

    // Kampanye paling rugi ditaruh paling atas — itu yang butuh perhatian duluan.
    $rows = $rows->sortBy(fn ($r) => $r->profit === null ? INF : $r->profit)->values();

    // ── Group by KATEGORI (hasil mapping SKU produk marketplace → item internal) ──
    $byCategory = $rows
        ->groupBy(fn ($r) => $r->camp->item_category ?? 'Belum termapping')
        ->map(function ($grp, $name) {
            $known = $grp->filter(fn ($r) => $r->profit !== null);
            return (object) [
                'name'       => $name,
                'campaigns'  => $grp->count(),
                'orders'     => $grp->sum(fn ($r) => (int) $r->camp->orders),
                'items'      => $grp->sum('itemsSold'),
                'spend'      => $grp->sum(fn ($r) => (float) $r->camp->spend),
                'topup'      => $grp->sum('spendAfterTax'),
                'gmv'        => $grp->sum(fn ($r) => (float) $r->camp->gmv),
                'netRevenue' => $grp->sum('netRevenue'),
                'cogs'       => $known->sum('totalCogs'),
                'profit'     => $known->sum('profit'),
                'lossCount'  => $known->where('isProfitable', false)->count(),
                'unknownCount'=> $grp->count() - $known->count(),
                'unmapped'   => $name === 'Belum termapping',
            ];
        })
        ->sortBy([['unmapped', 'asc'], ['profit', 'asc']])
        ->values();

    $totalCampaigns  = $rows->count();
    $knownRows       = $rows->filter(fn ($r) => $r->profit !== null);
    $profitableCount = $knownRows->where('isProfitable', true)->count();
    $lossCount       = $knownRows->where('isProfitable', false)->count();
    $unknownProfitCount = $totalCampaigns - $knownRows->count();
    $totalSpend      = $rows->sum(fn ($r) => $r->camp->spend);
    $totalTopup      = $rows->sum('spendAfterTax');
    $totalGmv        = $rows->sum(fn ($r) => $r->camp->gmv);
    $totalNetRevenue = $rows->sum('netRevenue');
    $totalCogsAll    = $knownRows->sum('totalCogs');
    $totalProfit     = $knownRows->sum('profit');
    $knownGmv        = $knownRows->sum(fn ($r) => (float) $r->camp->gmv);
    $knownNetRevenue = $knownRows->sum('netRevenue');
    $avgAcos         = $totalGmv > 0 ? ($totalSpend / $totalGmv) * 100 : 0;
    $cogsPctOmzet    = $totalGmv > 0 ? ($totalCogsAll / $totalGmv) * 100 : 0;   // porsi modal terhadap omzet
    $grossMargin     = $knownNetRevenue - $totalCogsAll;                        // sisa setelah HPP, sebelum iklan
    $noHppCount      = $rows->filter(fn ($r) => $r->totalCogs === null)->count(); // kampanye tanpa HPP yang dapat dipakai
    $avgFeePct       = $totalGmv > 0 ? max(0, (1 - ($totalNetRevenue / $totalGmv)) * 100) : 0; // rata-rata tertimbang potongan admin
    $totalFeeAmt     = max(0, $totalGmv - $totalNetRevenue); // total rupiah potongan platform
    $totalRoas       = $totalSpend > 0 ? $totalGmv / $totalSpend : 0;              // omzet per rupiah iklan
    $netRoas         = $totalTopup > 0 ? $totalNetRevenue / $totalTopup : 0;       // uang cair per rupiah iklan (incl. PPN)
    
    // Additional metrics for POAS, Margin, CAC, Break-even ROAS
    $poas            = $totalTopup > 0 ? $totalProfit / $totalTopup : 0;           // Net Profit / Ad Spend (dengan PPN)
    $grossMarginPct  = $knownGmv > 0 ? ($grossMargin / $knownGmv) * 100 : 0;       // Gross Margin %
    $netMarginPct    = $knownGmv > 0 ? ($totalProfit / $knownGmv) * 100 : 0;       // Net Profit Margin %
    $totalOrders     = $rows->sum(fn ($r) => (int) $r->camp->orders);
    $cac             = $totalOrders > 0 ? $totalTopup / $totalOrders : 0;          // Cost per Acquisition
    $beRoas          = $grossMargin > 0 ? (1.11 * $knownGmv) / $grossMargin : 0;   // Break-even ROAS; spend termasuk PPN
    $mappableProfitRows = $rows->filter(function ($r) {
        $campaignId = (string) ($r->camp->channel_campaign_id ?? '');
        $isGms = str_starts_with($campaignId, 'GMS-');
        return $r->totalCogs === null && (!empty($r->camp->channel_item_id) || $isGms);
    });
    $gmsProfitRows = $rows->filter(fn ($r) => str_starts_with((string) ($r->camp->channel_campaign_id ?? ''), 'GMS-'));
    
    $feeMode         = $adsSetting->admin_fee_mode ?? 'auto';
    $feeManualPct    = $adsSetting->admin_fee_pct ?? null;
    $fmt = fn ($n) => 'Rp ' . number_format(abs($n), 0, ',', '.');
@endphp
<script>
window.__profitChartData = {
    totalRev: {{ $totalGmv }},
    totalNetRev: {{ $totalNetRevenue }},
    totalCogs: {{ $totalCogsAll }},
    totalSpend: {{ $totalSpend }},
    totalTopup: {{ $totalTopup }},
    totalProfit: {{ $totalProfit }},
    feeAmt: {{ $totalFeeAmt }}
};
</script>
@php

    // Rekonsiliasi dengan Seller Center: belanja level toko (sumber yang sama
    // dengan angka platform) vs total kampanye di tabel ini. Selisihnya =
    // tipe iklan tanpa rincian kampanye (mis. Iklan Pencarian Toko/afiliasi).
    $shopSpend = isset($kpi['current']) ? (float) ($kpi['current']->spend ?? 0) : null;
    $otherAdsSpend = $shopSpend !== null ? max(0, $shopSpend - $totalSpend) : null;
@endphp

@if($otherAdsSpend !== null && $shopSpend > 0 && $otherAdsSpend > ($shopSpend * 0.005))
<div style="display:flex; flex-wrap:wrap; gap:.4rem 1rem; align-items:center; font-size:.68rem; color:var(--dsh-muted); margin-bottom:.75rem; padding:.6rem .8rem; border:1px dashed var(--dsh-border); border-radius:12px;">
    <span><i class="bi bi-shop"></i> Belanja iklan versi Seller Center: <b style="color:var(--text);">{{ $fmt($shopSpend) }}</b></span>
    <span>Terpetakan ke kampanye di tabel ini: <b style="color:var(--text);">{{ $fmt($totalSpend) }}</b></span>
    <span>Iklan lainnya (tanpa rincian kampanye, mis. Iklan Toko/afiliasi): <b style="color:#b45309;">{{ $fmt($otherAdsSpend) }}</b></span>
</div>
@endif

<!-- ── Pengaturan admin fee: otomatis (settlement) / manual ── -->
@if(($storeId ?? null) !== 'all')
<div class="ads-tab-panel mb-3">
    <div class="ads-tab-panel-head">
        <div>
            <div class="ads-tab-panel-title">
                <i class="bi bi-gear" style="color: var(--dsh-accent);"></i> Pengaturan Fee
            </div>
            <div class="ads-tab-panel-note">Tersambung langsung ke route `ads.fee.setting`.</div>
        </div>
    </div>
    <div class="p-3">
        <form method="POST" action="{{ route('marketplace.ads.fee.setting') }}" class="row g-3 align-items-end">
            @csrf
            <input type="hidden" name="store_id" value="{{ $storeId }}">
            <div class="col-lg-5">
                <label class="form-label" style="font-size:.75rem; font-weight:650; color:var(--dsh-muted);">Mode Admin Fee</label>
                <div class="d-flex flex-wrap gap-2">
                    <label class="d-flex align-items-center gap-2 px-3 py-2" style="border:1px solid var(--dsh-border); border-radius:10px; cursor:pointer; background:var(--bg); font-size:.76rem;">
                        <input type="radio" name="admin_fee_mode" value="auto" {{ $feeMode !== 'manual' ? 'checked' : '' }}>
                        <span>Otomatis</span>
                    </label>
                    <label class="d-flex align-items-center gap-2 px-3 py-2" style="border:1px solid var(--dsh-border); border-radius:10px; cursor:pointer; background:var(--bg); font-size:.76rem;">
                        <input type="radio" name="admin_fee_mode" value="manual" {{ $feeMode === 'manual' ? 'checked' : '' }}>
                        <span>Manual</span>
                    </label>
                </div>
                <div style="font-size:.68rem; color:var(--dsh-muted); margin-top:.4rem;">Otomatis pakai data settlement per item, manual pakai persen yang kamu isi.</div>
            </div>
            <div class="col-lg-3">
                <label class="form-label" style="font-size:.75rem; font-weight:650; color:var(--dsh-muted);">Admin Fee %</label>
                <input type="number" name="admin_fee_pct" step="0.1" min="0" max="99" value="{{ $feeManualPct !== null ? number_format((float) $feeManualPct, 1, '.', '') : '21.9' }}" class="form-control" style="border-radius:10px; font-size:.85rem; background:var(--bg); color:var(--text); border-color:var(--dsh-border);">
                <div style="font-size:.68rem; color:var(--dsh-muted); margin-top:.35rem;">Contoh: 21.9 untuk fee 21.9%.</div>
            </div>
            <div class="col-lg-4">
                <button type="submit" class="btn fw-bold w-100" style="background: var(--dsh-accent); color:#fff; border-radius:10px; padding:.6rem .9rem;">
                    <i class="bi bi-save"></i> Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>
</div>
@endif

<!-- ── KPI — urutan alur hitung: Omzet − HPP − Iklan = Net Profit ── -->
@php
    $profitMarginPct = $totalGmv > 0 ? ($totalProfit / $totalGmv) * 100 : 0;
@endphp
<div class="ads-tab-panel mb-3">
    <div class="ads-tab-panel-head">
        <div>
            <div class="ads-tab-panel-title"><i class="bi bi-cash-stack text-success"></i> Profitabilitas &amp; Margin</div>
            <div class="ads-tab-panel-note">Rincian efisiensi untung dan rugi iklan.</div>
        </div>
    </div>
    <div class="p-3 p-md-3">
        <div class="ads-kpi-grid mb-3">
            <div class="dpanel ads-kpi kpi-revenue">
                <div class="ads-kpi-label"><i class="bi bi-graph-up-arrow"></i> Omzet</div>
                <div class="ads-kpi-value" style="font-variant-numeric:tabular-nums;">{{ $fmt($totalGmv) }}</div>
                <div class="ads-kpi-sub">Cair <b>{{ $fmt($totalNetRevenue) }}</b> · adm {{ number_format($avgFeePct, 1, ',', '.') }}%</div>
            </div>
            
            <div class="dpanel ads-kpi kpi-cogs">
                <div class="ads-kpi-label"><i class="bi bi-box-seam"></i> Gross Profit</div>
                <div class="ads-kpi-value" style="font-variant-numeric:tabular-nums;">{{ $grossMargin < 0 ? '−' : '' }}{{ $fmt($grossMargin) }}</div>
                <div class="ads-kpi-sub">HPP terhitung <b>{{ $fmt($totalCogsAll) }}</b>
                @if($noHppCount > 0)
                    · <span class="text-warning"><i class="bi bi-exclamation-triangle"></i> {{ $noHppCount }} campaign belum ada HPP</span>
                @endif
                </div>
            </div>

            <div class="dpanel ads-kpi kpi-spend">
                <div class="ads-kpi-label"><i class="bi bi-wallet2"></i> Ad Spend</div>
                <div class="ads-kpi-value" style="font-variant-numeric:tabular-nums;">&minus;{{ $fmt($totalTopup) }}</div>
                <div class="ads-kpi-sub">Net spend <b>{{ $fmt($totalSpend) }}</b> + PPN</div>
            </div>

            <div class="dpanel ads-kpi kpi-profit" style="border-left-color: {{ $unknownProfitCount > 0 ? '#d97706' : ($totalProfit >= 0 ? '#16a34a' : '#dc2626') }};">
                <div class="ads-kpi-label"><i class="bi bi-cash-stack"></i> Net Profit Setelah Iklan</div>
                <div class="ads-kpi-value" style="font-variant-numeric:tabular-nums; color: {{ $unknownProfitCount > 0 ? '#b45309' : ($totalProfit >= 0 ? '#16a34a' : '#dc2626') }};">{{ $totalProfit < 0 ? '−' : '' }}{{ $fmt($totalProfit) }}</div>
                <div class="ads-kpi-sub">Terhitung dari {{ $knownRows->count() }} campaign · Margin <b>{{ number_format($netMarginPct, 1, ',', '.') }}%</b> &bull; {{ $profitableCount }} untung, {{ $lossCount }} rugi @if($unknownProfitCount > 0) &bull; <span class="text-warning">{{ $unknownProfitCount }} belum dihitung</span>@endif</div>
            </div>

            <div class="dpanel ads-kpi">
                <div class="ads-kpi-label"><i class="bi bi-percent"></i> Margins</div>
                <div class="ads-kpi-value" style="font-variant-numeric:tabular-nums;">{{ number_format($grossMarginPct, 1, ',', '.') }}%</div>
                <div class="ads-kpi-sub">Gross. Net Margin <b>{{ number_format($netMarginPct, 1, ',', '.') }}%</b></div>
            </div>

            <div class="dpanel ads-kpi">
                <div class="ads-kpi-label"><i class="bi bi-speedometer2"></i> POAS · ROAS</div>
                <div class="ads-kpi-value" style="font-variant-numeric:tabular-nums;">{{ number_format($poas, 2, ',', '.') }}x</div>
                <div class="ads-kpi-sub">ROAS <b>{{ number_format($totalRoas, 2, ',', '.') }}x</b> · Net <b>{{ number_format($netRoas, 2, ',', '.') }}x</b></div>
            </div>

            <div class="dpanel ads-kpi">
                <div class="ads-kpi-label"><i class="bi bi-dash-circle-dotted"></i> Break-even ROAS</div>
                <div class="ads-kpi-value" style="font-variant-numeric:tabular-nums;">{{ number_format($beRoas, 2, ',', '.') }}x</div>
                <div class="ads-kpi-sub">Batas minimum; biaya iklan sudah +PPN 11%</div>
            </div>

            <div class="dpanel ads-kpi">
                <div class="ads-kpi-label"><i class="bi bi-person-plus"></i> CAC</div>
                <div class="ads-kpi-value" style="font-variant-numeric:tabular-nums;">{{ $fmt($cac) }}</div>
                <div class="ads-kpi-sub">Cost per Acquisition (per order)</div>
            </div>
        </div>
        
        {{-- CHARTS PROFITABILITAS --}}
        <div class="row gx-3">
            <div class="col-md-5 mb-3">
                <div class="p-2 border rounded h-100" style="background: var(--card-bg);">
                    <div class="fw-bold mb-2 text-center text-muted" style="font-size: 0.75rem;"><i class="bi bi-pie-chart-fill me-1"></i> Cost Composition</div>
                    <div style="position: relative; height: 180px; width: 100%;">
                        <canvas id="chartProfitComposition"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-7 mb-3">
                <div class="p-2 border rounded h-100" style="background: var(--card-bg);">
                    <div class="fw-bold mb-2 text-center text-muted" style="font-size: 0.75rem;"><i class="bi bi-graph-up me-1"></i> Trend Harian (Omzet vs Profit)</div>
                    <div style="position: relative; height: 180px; width: 100%;">
                        <canvas id="chartProfitTrend"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Toggle tampilan: Per Kampanye / Per Kategori ── -->
<div style="display:flex; gap:.35rem; margin-bottom:.75rem;">
    <button type="button" id="btnViewCategory" class="btn fw-bold" onclick="__profitView('category')" style="border-radius:999px; font-size:.72rem; padding:.38rem .95rem; background:var(--dsh-accent); color:#fff; border:1px solid var(--dsh-accent);">Per Kategori</button>
    <button type="button" id="btnViewCampaign" class="btn fw-bold" onclick="__profitView('campaign')" style="border-radius:999px; font-size:.72rem; padding:.38rem .95rem; color:var(--dsh-muted); border:1px solid var(--dsh-border); background:transparent;">Per Kampanye</button>
</div>
@if($mappableProfitRows->isNotEmpty())
    <div class="mb-3" style="display:flex;align-items:center;gap:.55rem;flex-wrap:wrap;padding:.65rem .8rem;border:1px dashed rgba(180,83,9,.35);border-radius:12px;background:rgba(217,119,6,.05);font-size:.72rem;color:var(--dsh-muted);">
        <i class="bi bi-info-circle" style="color:#b45309;"></i>
        <span><b style="color:#b45309;">{{ $mappableProfitRows->count() }} campaign</b> belum punya HPP/mapping item.</span>
        <button type="button" class="btn btn-sm" style="font-size:.68rem;padding:.2rem .55rem;border-radius:999px;color:#92400e;border:1px solid rgba(180,83,9,.35);background:transparent;" onclick="__profitView('campaign')">Tampilkan &amp; perbaiki</button>
    </div>
@endif

<!-- ── Tabel per KATEGORI ── -->
<div id="profitViewCategory" style="display:block;">
    <div class="ads-tab-panel">
        <div class="table-responsive">
            <table class="dpanel-table dpanel-table-sm table-hover w-100" style="white-space: nowrap;">
            <thead>
                <tr>
                    <th>Kategori<div style="font-size:.6rem; font-weight:500; color:var(--dsh-muted); text-transform:none;">dari mapping SKU</div></th>
                    <th class="text-end">Kampanye</th>
                    <th class="text-end">Konversi<div style="font-size:.6rem; font-weight:500; color:var(--dsh-muted); text-transform:none;">order &bull; pcs</div></th>
                    <th class="text-end">Biaya Iklan<div style="font-size:.6rem; font-weight:500; color:var(--dsh-muted); text-transform:none;">+PPN 11%</div></th>
                    <th class="text-end">Pendapatan<div style="font-size:.6rem; font-weight:500; color:var(--dsh-muted); text-transform:none;">bersih vs omzet</div></th>
                    <th class="text-end">HPP</th>
                    <th class="text-end">Net Profit</th>
                </tr>
            </thead>
            <tbody>
                @forelse($byCategory as $cat)
                    <tr style="border-bottom: 1px solid var(--dsh-border); {{ $cat->unknownCount > 0 && $cat->profit == 0 ? 'background: rgba(217, 119, 6, 0.045);' : ($cat->profit >= 0 ? '' : 'background: rgba(220, 38, 38, 0.045);') }}">
                        <td style="padding:.6rem .5rem;">
                            <span style="font-weight:700; color:{{ $cat->unmapped ? '#b45309' : 'var(--text)' }}; font-size:.85rem;">{{ $cat->name }}</span>
                            @if($cat->unmapped)
                                <div style="font-size:.62rem; color:var(--dsh-muted);">lengkapi <a href="{{ route('marketplace.sku-mapping') }}" style="color:var(--dsh-accent);">SKU Mapping</a> agar terkelompok</div>
                            @elseif($cat->lossCount > 0)
                                <div style="font-size:.62rem; color:#b91c1c;">{{ $cat->lossCount }} kampanye rugi</div>
                            @endif
                            @if($cat->unknownCount > 0)<div style="font-size:.62rem; color:#b45309;">{{ $cat->unknownCount }} belum ada HPP</div>@endif
                        </td>
                        <td class="text-end" style="font-variant-numeric:tabular-nums; font-weight:700; color:var(--text); vertical-align:middle;">{{ $cat->campaigns }}</td>
                        <td class="text-end" style="font-variant-numeric:tabular-nums; color:var(--text); vertical-align:middle;">{{ number_format($cat->orders, 0, ',', '.') }} <span style="color:var(--dsh-muted);">&bull;</span> {{ number_format($cat->items, 0, ',', '.') }} pcs</td>
                        <td class="text-end" style="font-variant-numeric:tabular-nums; font-weight:700; color:#dc2626; vertical-align:middle;">{{ $fmt($cat->spend) }}<div style="font-size:.62rem; color:var(--dsh-muted); font-weight:500;">+PPN {{ $fmt($cat->topup) }}</div></td>
                        <td class="text-end" style="font-variant-numeric:tabular-nums; font-weight:800; color:#0369a1; vertical-align:middle;">{{ $fmt($cat->netRevenue) }}<div style="font-size:.62rem; color:var(--dsh-muted); font-weight:500;">Omzet {{ $fmt($cat->gmv) }}</div></td>
                        <td class="text-end" style="font-variant-numeric:tabular-nums; font-weight:700; color:var(--text); vertical-align:middle;">&minus;{{ $fmt($cat->cogs) }}</td>
                        <td class="text-end" style="font-variant-numeric:tabular-nums; font-weight:800; font-size:.95rem; color:{{ $cat->unknownCount > 0 && $cat->profit == 0 ? '#b45309' : ($cat->profit >= 0 ? '#16a34a' : '#dc2626') }}; vertical-align:middle;">{{ $cat->unknownCount > 0 && $cat->profit == 0 ? 'N/A' : (($cat->profit < 0 ? '-' : '') . $fmt($cat->profit)) }}</td>
                    </tr>
            @empty
                <tr><td colspan="7" class="text-center py-4" style="color:var(--dsh-muted); font-size:.8rem;">Belum ada data.</td></tr>
            @endforelse
            </tbody>
            </table>
        </div>
    </div>
</div>

<script>
window.__profitView = function (mode) {
    const cat = document.getElementById('profitViewCategory');
    const camp = document.getElementById('profitViewCampaign');
    const bCat = document.getElementById('btnViewCategory');
    const bCamp = document.getElementById('btnViewCampaign');
    const on  = 'border-radius:999px; font-size:.72rem; padding:.38rem .95rem; background:var(--dsh-accent); color:#fff; border:1px solid var(--dsh-accent);';
    const off = 'border-radius:999px; font-size:.72rem; padding:.38rem .95rem; color:var(--dsh-muted); border:1px solid var(--dsh-border); background:transparent;';
    const isCat = mode === 'category';
    cat.style.display = isCat ? 'block' : 'none';
    camp.style.display = isCat ? 'none' : 'block';
    bCat.style.cssText = isCat ? on : off;
    bCamp.style.cssText = isCat ? off : on;
    try { localStorage.setItem('profitViewMode', mode); } catch (e) {}
};
(function () {
    try { 
        if (localStorage.getItem('profitViewMode') === 'campaign') {
            window.__profitView('campaign'); 
        } else {
            window.__profitView('category'); 
        }
    } catch (e) {}
})();
</script>

@if($mappableProfitRows->isNotEmpty() || $gmsProfitRows->isNotEmpty())
<div class="modal fade" id="profitCampaignMappingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:18px;border:1px solid var(--dsh-border);background:var(--card-bg,#fff);color:var(--text,#0f172a);">
            <div class="modal-header" style="border-bottom:1px solid var(--dsh-border);">
                <div>
                    <h5 class="modal-title" style="font-size:1rem;font-weight:800;">Pilih item untuk HPP</h5>
                    <div id="profitMapCampaignLabel" style="font-size:.7rem;color:var(--dsh-muted);margin-top:.2rem;"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div id="profitMapScopeNote" style="font-size:.72rem;color:var(--dsh-muted);margin-bottom:.65rem;">Pilih item internal yang sesuai. HPP item tersebut akan dipakai untuk menghitung ulang profit.</div>
                <input type="search" id="profitMapItemSearch" class="form-control" placeholder="Cari kode atau nama item…" autocomplete="off" style="font-size:.82rem;border-radius:10px;">
                <div id="profitMapItemResults" style="max-height:260px;overflow-y:auto;margin-top:.65rem;"></div>
                <div id="profitMapSelected" style="display:none;margin-top:.65rem;padding:.55rem .7rem;border-radius:10px;background:rgba(22,163,74,.08);color:#15803d;font-size:.75rem;"></div>
                <div id="profitMapError" style="display:none;margin-top:.65rem;color:#b91c1c;font-size:.75rem;"></div>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--dsh-border);">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal" style="border-radius:999px;font-size:.75rem;">Batal</button>
                <button type="button" id="profitMapSave" class="btn btn-primary" disabled style="border-radius:999px;font-size:.75rem;font-weight:700;">Simpan HPP</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const modalEl = document.getElementById('profitCampaignMappingModal');
    const searchEl = document.getElementById('profitMapItemSearch');
    const resultsEl = document.getElementById('profitMapItemResults');
    const selectedEl = document.getElementById('profitMapSelected');
    const errorEl = document.getElementById('profitMapError');
    const saveEl = document.getElementById('profitMapSave');
    const labelEl = document.getElementById('profitMapCampaignLabel');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let state = { mode: 'campaign', campaignId: null, storeId: null, gmsItemId: null, itemId: null, timer: null };

    const esc = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
    }[char]));

    async function searchItems(query) {
        resultsEl.innerHTML = '<div style="padding:.7rem;color:var(--dsh-muted);font-size:.75rem;">Mencari item…</div>';
        try {
            const response = await fetch('/api/marketplace/items/search?q=' + encodeURIComponent(query), { headers: { Accept: 'application/json' } });
            const items = await response.json();
            if (!response.ok) throw new Error(items.message || 'Gagal mencari item.');
            resultsEl.innerHTML = items.length
                ? items.map((item) => `<button type="button" class="profit-map-option" data-id="${item.id}" data-code="${esc(item.code || '')}" data-name="${esc(item.name || '')}" style="display:block;width:100%;text-align:left;padding:.55rem .65rem;margin-bottom:.35rem;border:1px solid var(--dsh-border);border-radius:10px;background:transparent;color:var(--text);cursor:pointer;"><b style="font-size:.76rem;">${esc(item.code || 'Tanpa kode')}</b><span style="font-size:.72rem;color:var(--dsh-muted);"> — ${esc(item.name || '')}</span><span style="display:block;font-size:.66rem;color:var(--dsh-muted);margin-top:.15rem;">HPP ${window.__profitFmtRp ? window.__profitFmtRp(item.hpp) : ('Rp ' + Number(item.hpp || 0).toLocaleString('id-ID'))}</span></button>`).join('')
                : '<div style="padding:.7rem;color:var(--dsh-muted);font-size:.75rem;">Item tidak ditemukan.</div>';
            resultsEl.querySelectorAll('.profit-map-option').forEach((button) => {
                button.addEventListener('click', () => {
                    state.itemId = Number(button.dataset.id);
                    selectedEl.style.display = 'block';
                    selectedEl.innerHTML = '✓ ' + button.dataset.code + ' — ' + button.dataset.name;
                    saveEl.disabled = !state.itemId;
                    resultsEl.innerHTML = '';
                });
            });
        } catch (error) {
            resultsEl.innerHTML = '<div style="padding:.7rem;color:#b91c1c;font-size:.75rem;">' + esc(error.message) + '</div>';
        }
    }

    window.openProfitCampaignMapping = function (button) {
        state = { mode: 'campaign', campaignId: Number(button.dataset.profitMapCampaign), storeId: null, gmsItemId: null, itemId: null, timer: null };
        const isGms = button.dataset.profitMapItem.indexOf('Semua Produk') !== -1;
        labelEl.textContent = button.dataset.profitMapName + (isGms ? ' · HPP acuan agregat GMV Max' : ' · Shopee item ' + button.dataset.profitMapItem);
        document.getElementById('profitMapScopeNote').textContent = isGms
            ? 'Ini hanya HPP acuan untuk angka agregat campaign. Untuk hasil paling akurat, buka “Lihat produk & HPP” lalu mapping setiap produk GMV Max.'
            : 'Pilih item internal yang sesuai. HPP item tersebut akan dipakai untuk menghitung ulang profit campaign.';
        searchEl.value = '';
        selectedEl.style.display = 'none';
        errorEl.style.display = 'none';
        saveEl.disabled = true;
        saveEl.textContent = 'Simpan HPP';
        resultsEl.innerHTML = '';
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
        setTimeout(() => { searchEl.focus(); searchItems(''); }, 150);
    };

    window.openGmsItemMapping = function (button) {
        state = { mode: 'gms-item', campaignId: null, storeId: Number(button.dataset.gmsMapStore), gmsItemId: String(button.dataset.gmsMapItem), itemId: null, timer: null };
        labelEl.textContent = (button.dataset.gmsMapName || 'Produk GMV Max') + ' · item GMV Max ' + state.gmsItemId;
        document.getElementById('profitMapScopeNote').textContent = 'Pilih item internal yang sesuai. HPP per pcs dari item ini akan dipakai untuk menghitung profit produk GMV Max.';
        searchEl.value = '';
        selectedEl.style.display = 'none';
        errorEl.style.display = 'none';
        saveEl.disabled = true;
        saveEl.textContent = 'Simpan HPP';
        resultsEl.innerHTML = '';
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
        setTimeout(() => { searchEl.focus(); searchItems(''); }, 150);
    };

    searchEl.addEventListener('input', () => {
        clearTimeout(state.timer);
        state.timer = setTimeout(() => searchItems(searchEl.value.trim()), 250);
    });

    saveEl.addEventListener('click', async () => {
        if (!state.itemId || (state.mode === 'campaign' && !state.campaignId) || (state.mode === 'gms-item' && (!state.storeId || !state.gmsItemId))) return;
        saveEl.disabled = true;
        saveEl.textContent = 'Menyimpan…';
        errorEl.style.display = 'none';
        try {
            const endpoint = state.mode === 'gms-item'
                ? '/marketplace/ads-dashboard/gms-items/' + state.storeId + '/' + encodeURIComponent(state.gmsItemId) + '/map'
                : '/api/marketplace/ad-campaigns/' + state.campaignId + '/map-item';
            const response = await fetch(endpoint, {
                method: 'PATCH',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ internal_item_id: state.itemId }),
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'Mapping gagal disimpan.');
            bootstrap.Modal.getInstance(modalEl)?.hide();
            window.location.reload();
        } catch (error) {
            errorEl.textContent = error.message;
            errorEl.style.display = 'block';
            saveEl.disabled = false;
            saveEl.textContent = 'Simpan HPP';
        }
    });
})();
</script>
@endif

<div id="profitViewCampaign" style="display:none;">
<!-- ── Tabel per kampanye — kolom mengikuti alur hitung: Pendapatan Bersih − HPP − Iklan = Net Profit ── -->
<div class="ads-tab-panel">
    <div class="table-responsive">
        <table class="dpanel-table dpanel-table-sm table-hover w-100" style="white-space: nowrap;">
        <thead>
            <tr>
                <th onclick="sortProfitTable('campaign')" style="cursor:pointer">Kampanye <i class="bi bi-arrow-down-up" style="font-size: 0.6rem; opacity: 0.5;"></i></th>
                <th class="text-end" onclick="sortProfitTable('orders')" style="cursor:pointer">Konversi <i class="bi bi-arrow-down-up" style="font-size: 0.6rem; opacity: 0.5;"></i></th>
                <th class="text-end" onclick="sortProfitTable('net_revenue')" style="cursor:pointer">Pendapatan Bersih <i class="bi bi-arrow-down-up" style="font-size: 0.6rem; opacity: 0.5;"></i></th>
                <th class="text-end" onclick="sortProfitTable('hpp')" style="cursor:pointer"><span style="color:var(--dsh-muted); font-weight:600;">&minus;</span> HPP <i class="bi bi-arrow-down-up" style="font-size: 0.6rem; opacity: 0.5;"></i></th>
                <th class="text-end" onclick="sortProfitTable('ad_spend')" style="cursor:pointer"><span style="color:var(--dsh-muted); font-weight:600;">&minus;</span> Iklan <span style="font-size:.6rem; font-weight:500; color:var(--dsh-muted); text-transform:none;">+PPN 11%</span> <i class="bi bi-arrow-down-up" style="font-size: 0.6rem; opacity: 0.5;"></i></th>
                <th class="text-end" onclick="sortProfitTable('net_profit')" style="cursor:pointer; border-left: 2px solid var(--dsh-border);"><span style="color:var(--dsh-muted); font-weight:600;">=</span> Net Profit <i class="bi bi-arrow-down-up" style="font-size: 0.6rem; opacity: 0.5;"></i></th>
                <th class="text-end" onclick="sortProfitTable('acos')" style="cursor:pointer">ACOS <i class="bi bi-arrow-down-up" style="font-size: 0.6rem; opacity: 0.5;"></i></th>
                <th class="text-center">Rekomendasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $r)
                @php
                    $camp = $r->camp;
                    $reco = $camp->reco ?? ['label' => 'Optimize', 'color' => '#ca8a04', 'icon' => '⚡'];
                    $profitKnown = $r->profit !== null;
                    $profitColor = !$profitKnown ? '#64748b' : ($r->isProfitable ? '#16a34a' : '#dc2626');
                    $baseColor = $reco['color'] ?? '#64748b';
                    $bgColor = match($baseColor) {
                        '#16a34a' => 'rgba(22, 163, 74, 0.15)',
                        '#ca8a04' => 'rgba(234, 179, 8, 0.15)',
                        '#b91c1c' => 'rgba(220, 38, 38, 0.15)',
                        '#b45309' => 'rgba(180, 83, 9, 0.15)',
                        default => 'rgba(148, 163, 184, 0.15)'
                    };
                @endphp
                <tr data-campaign="{{ strtolower($camp->campaign_name ?? '') }}"
                    data-orders="{{ $camp->orders }}"
                    data-net_revenue="{{ $r->netRevenue }}"
                    data-hpp="{{ $r->totalCogs ?? '' }}"
                    data-ad_spend="{{ $r->spendAfterTax }}"
                    data-net_profit="{{ $r->profit ?? '' }}"
                    data-acos="{{ $r->acos }}"
                    style="border-bottom: 1px solid var(--dsh-border); {{ !$profitKnown ? 'background: rgba(217, 119, 6, 0.045);' : ($r->isProfitable ? '' : 'background: rgba(220, 38, 38, 0.045);') }}">
                    <td style="padding: 0.75rem 0.5rem; max-width: 250px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            @if($camp->campaign_status === 'ongoing')
                                <span style="display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px; background:rgba(22, 163, 74, 0.15); border-radius:50%; flex-shrink:0;" title="Berjalan">
                                    <i class="bi bi-play-fill text-success" style="font-size: .8rem;"></i>
                                </span>
                            @elseif($camp->campaign_status === 'paused')
                                <span style="display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px; background:rgba(234, 179, 8, 0.15); border-radius:50%; flex-shrink:0;" title="Jeda">
                                    <i class="bi bi-pause-fill text-warning" style="font-size: .8rem;"></i>
                                </span>
                            @else
                                <span style="display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px; background:rgba(148, 163, 184, 0.15); border-radius:50%; flex-shrink:0;" title="{{ ucfirst($camp->campaign_status ?? '-') }}">
                                    <span style="display:inline-block; width:6px; height:6px; background:#94a3b8; border-radius:50%;"></span>
                                </span>
                            @endif
                            <span style="font-weight: 700; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size:.85rem;" title="{{ $camp->campaign_name ?: 'Kampanye Tidak Ditemukan' }} (Camp ID: {{ $camp->channel_campaign_id }})">
                                {{ $camp->campaign_name ?: 'Kampanye Tidak Ditemukan' }}
                            </span>
                        </div>
                    </td>

                    <td class="text-end" style="vertical-align: middle;">
                        <div style="font-weight: 700; color: var(--text); font-variant-numeric: tabular-nums;">{{ number_format($camp->orders, 0, ',', '.') }} order{{ $camp->items_sold > 0 ? ' · ' . number_format($camp->items_sold, 0, ',', '.') . ' pcs' : '' }}</div>
                        @if($r->aov > 0)
                            <div style="font-size: .65rem; color: var(--dsh-muted); margin-top: 2px; font-variant-numeric: tabular-nums;">AOV {{ $fmt($r->aov) }} &rarr; cair <b style="color:#0369a1;">{{ $fmt($r->aovNet) }}</b></div>
                        @endif
                    </td>

                    <td class="text-end" style="vertical-align: middle;">
                        <div style="font-weight: 800; color: #0369a1; font-variant-numeric: tabular-nums;">{{ $fmt($r->netRevenue) }}</div>
                        <div style="font-size: .65rem; color: var(--dsh-muted); margin-top: 2px;" @if($r->feeIsEstimate) title="Estimasi — belum ada data pencairan untuk item ini" @endif>Omzet {{ $fmt($camp->gmv) }} · adm {{ $r->feeIsEstimate ? '±' : '' }}{{ number_format($r->feePct, 1, ',', '.') }}%</div>
                    </td>

                        <td class="text-end" style="vertical-align: middle;">
                        <div style="font-weight: 700; color: var(--text); font-variant-numeric: tabular-nums;">{!! $r->totalCogs > 0 ? '&minus;' . $fmt($r->totalCogs) : '<span style="color:#b45309;">&mdash;</span>' !!}</div>
                        <div style="font-size: .65rem; color: {{ $r->totalCogs !== null ? 'var(--dsh-muted)' : '#b45309' }}; margin-top: 2px;" title="{{ $r->totalCogs === null ? 'HPP belum tersedia, profit tidak dihitung' : ($r->cogsExact ? 'Eksak: HPP × pcs terjual' : 'Estimasi dari rasio harga (pcs belum tercatat)') }}">{{ $r->totalCogs === null ? 'belum diisi' : ($r->cogsExact ? $fmt($r->unitCogs) . '/pcs × ' . $r->itemsSold : $fmt($r->unitCogs) . '/pcs · estimasi') }}</div>
                        @if($r->totalCogs === null && ($camp->channel_item_id || str_starts_with((string) $camp->channel_campaign_id, 'GMS-')))
                            <button type="button" class="btn btn-sm mt-1" style="font-size:.62rem; padding:.16rem .45rem; border-radius:999px; color:#b45309; border:1px solid rgba(180,83,9,.35); background:rgba(217,119,6,.06);" data-profit-map-campaign="{{ $camp->id }}" data-profit-map-name="{{ e($camp->campaign_name ?: 'Kampanye') }}" data-profit-map-item="{{ e($camp->channel_item_id ? (string) $camp->channel_item_id : 'Semua Produk (GMV Max)') }}" onclick="openProfitCampaignMapping(this)">
                                <i class="bi bi-link-45deg"></i> {{ str_starts_with((string) $camp->channel_campaign_id, 'GMS-') ? 'Atur HPP acuan' : 'Pilih item HPP' }}
                            </button>
                        @endif
                        @if(str_starts_with((string) $camp->channel_campaign_id, 'GMS-'))
                            <button type="button" class="btn btn-sm mt-1" style="font-size:.62rem; padding:.16rem .45rem; border-radius:999px; color:#0369a1; border:1px solid rgba(3,105,161,.35); background:rgba(3,105,161,.06);" data-gms-store="{{ $camp->store_id }}" onclick="openGmsItems(this)">
                                <i class="bi bi-list-ul"></i> Lihat produk &amp; HPP
                            </button>
                        @endif
                    </td>

                    <td class="text-end" style="vertical-align: middle;">
                        <div style="font-weight: 700; color: #dc2626; font-variant-numeric: tabular-nums;">&minus;{{ $fmt($r->spendAfterTax) }}</div>
                        <div style="font-size: .65rem; color: var(--dsh-muted); margin-top: 2px;">iklan {{ $fmt($camp->spend) }}</div>
                    </td>

                    <td class="text-end" style="vertical-align: middle; border-left: 2px solid var(--dsh-border); background: rgba({{ !$profitKnown ? '217, 119, 6' : ($r->isProfitable ? '22, 163, 74' : '220, 38, 38') }}, 0.05);">
                        <div style="font-weight: 800; font-size: .95rem; color: {{ $profitColor }}; font-variant-numeric: tabular-nums;" @if(!$profitKnown || $r->feeIsEstimate) title="{{ !$profitKnown ? 'Tidak dihitung — HPP belum tersedia' : 'Estimasi — belum ada data pencairan' }}" @endif>{{ !$profitKnown ? 'N/A' : (($r->feeIsEstimate ? '±' : '') . ($r->profit < 0 ? '−' : '') . $fmt($r->profit)) }}</div>
                    </td>

                    <td class="text-end" style="vertical-align: middle;">
                        <div style="font-weight: 800; font-size: .95rem; color: {{ $r->acosOk === null ? 'var(--text)' : ($r->acosOk ? '#16a34a' : '#dc2626') }}; font-variant-numeric: tabular-nums;">{{ $r->acos > 0 ? number_format($r->acos, 1, ',', '.') . '%' : '-' }}</div>
                        <div style="font-size: .65rem; color: var(--dsh-muted); margin-top: 2px;">batas {{ $r->beAcos !== null ? number_format($r->beAcos, 1, ',', '.') . '%' : '-' }}</div>
                        @if($r->beAcos !== null && $r->acos > 0)
                            <div style="height: 4px; width: 72px; margin-left: auto; margin-top: 4px; background: var(--dsh-border); border-radius: 99px; overflow: hidden;">
                                <div style="height: 100%; width: {{ number_format($r->acosBarPct, 0, '.', '') }}%; background: {{ $r->acosOk ? '#16a34a' : '#dc2626' }};"></div>
                            </div>
                        @endif
                    </td>

                    <td class="text-center" style="vertical-align: middle;">
                        <span style="display:inline-block; padding: 4px 10px; border-radius: 6px; font-size: 0.65rem; font-weight: 700; background: {{ $bgColor }}; color: {{ $baseColor }}; text-transform: uppercase;" title="Saran otomatis berdasarkan posisi ACOS terhadap batas aman & tren profit">
                            {{ $reco['icon'] ?? '⚡' }} {{ $reco['label'] ?? 'Optimize' }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-4" style="color: var(--dsh-muted); font-size: .8rem;">
                        Belum ada data kampanye yang memiliki pengeluaran atau pendapatan.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if($rows->isNotEmpty())
        <tfoot>
            <tr style="border-top: 2px solid var(--dsh-border);">
                <td style="padding: .7rem .5rem; font-weight: 800; font-size: .75rem; color: var(--text); text-transform: uppercase;">Total</td>
                <td class="text-end" style="font-weight: 700; color: var(--text); font-variant-numeric: tabular-nums;">{{ number_format($rows->sum(fn ($r) => $r->camp->orders), 0, ',', '.') }} order</td>
                <td class="text-end" style="font-weight: 800; color: #0369a1; font-variant-numeric: tabular-nums;">{{ $fmt($totalNetRevenue) }}<div style="font-size:.62rem; color:var(--dsh-muted); font-weight:500;">Omzet {{ $fmt($totalGmv) }} · adm {{ $fmt($totalFeeAmt) }} ({{ number_format($avgFeePct, 1, ',', '.') }}%)</div></td>
                <td class="text-end" style="font-weight: 700; color: var(--text); font-variant-numeric: tabular-nums;">&minus;{{ $fmt($totalCogsAll) }}</td>
                <td class="text-end" style="font-weight: 700; color: #dc2626; font-variant-numeric: tabular-nums;">&minus;{{ $fmt($totalTopup) }}<div style="font-size:.62rem; color:var(--dsh-muted); font-weight:500;">iklan {{ $fmt($totalSpend) }}</div></td>
                <td class="text-end" style="font-weight: 800; font-size: .95rem; color: {{ $unknownProfitCount > 0 ? '#b45309' : ($totalProfit >= 0 ? '#16a34a' : '#dc2626') }}; font-variant-numeric: tabular-nums; border-left: 2px solid var(--dsh-border); background: rgba({{ $unknownProfitCount > 0 ? '217, 119, 6' : ($totalProfit >= 0 ? '22, 163, 74' : '220, 38, 38') }}, 0.05);">{{ $totalProfit < 0 ? '−' : '' }}{{ $fmt($totalProfit) }}<div style="font-size:.62rem; color:var(--dsh-muted); font-weight:500;">{{ $unknownProfitCount > 0 ? $unknownProfitCount . ' campaign belum dihitung' : 'HPP tersedia' }}</div></td>
                <td class="text-end" style="font-weight: 700; color: var(--text); font-variant-numeric: tabular-nums;">{{ number_format($avgAcos, 1, ',', '.') }}%</td>
                <td></td>
            </tr>
        </tfoot>
        @endif
        </table>
    </div>
</div>
</div>

@if($gmsProfitRows->isNotEmpty())
<div class="modal fade" id="gmsItemsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content" style="border-radius:18px;border:1px solid var(--dsh-border);background:var(--card-bg,#fff);color:var(--text,#0f172a);">
            <div class="modal-header" style="border-bottom:1px solid var(--dsh-border);">
                <div>
                    <h5 class="modal-title" style="font-size:1rem;font-weight:800;">Rincian produk GMV Max</h5>
                    <div style="font-size:.72rem;color:var(--dsh-muted);margin-top:.2rem;">Mapping HPP dilakukan per produk agar profit campaign tidak membingungkan.</div>
                    <div id="gmsItemsPeriod" style="font-size:.7rem;color:var(--dsh-muted);margin-top:.2rem;"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div style="padding:.7rem .8rem;margin-bottom:.75rem;border-radius:12px;background:rgba(3,105,161,.06);border:1px solid rgba(3,105,161,.14);font-size:.72rem;color:var(--dsh-muted);">
                    <i class="bi bi-info-circle" style="color:#0369a1;"></i>
                    <b style="color:var(--text);">GMV Max adalah campaign agregat.</b> Angka di bawah memecahnya menjadi produk. Jika ada produk yang belum punya HPP, klik <b style="color:var(--text);">Pilih item HPP</b> pada baris tersebut.
                </div>
                <div id="gmsItemsSummary" style="display:flex;flex-wrap:wrap;gap:.55rem;margin-bottom:.8rem;"></div>
                <div id="gmsItemsBody" class="table-responsive">
                    <div style="padding:1.2rem;text-align:center;color:var(--dsh-muted);font-size:.75rem;">Memuat produk GMV Max…</div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    const modalEl = document.getElementById('gmsItemsModal');
    const bodyEl = document.getElementById('gmsItemsBody');
    const summaryEl = document.getElementById('gmsItemsSummary');
    const periodEl = document.getElementById('gmsItemsPeriod');
    const endpointBase = @json(url('/marketplace/ads-dashboard/gms-items'));
    const fromDate = @json($dateFrom ?? now()->subDays(6)->toDateString());
    const toDate = @json($dateTo ?? now()->toDateString());
    const esc = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));
    const fmtRp = (value) => 'Rp ' + Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });

    window.openGmsItems = async function (button) {
        const storeId = button.dataset.gmsStore;
        periodEl.textContent = 'Periode ' + fromDate + ' s/d ' + toDate;
        bodyEl.innerHTML = '<div style="padding:1.2rem;text-align:center;color:var(--dsh-muted);font-size:.75rem;">Memuat produk GMV Max…</div>';
        summaryEl.textContent = '';
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
        try {
            const response = await fetch(endpointBase + '/' + storeId + '?date_from=' + encodeURIComponent(fromDate) + '&date_to=' + encodeURIComponent(toDate), { headers: { Accept: 'application/json' } });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'Gagal memuat item GMV Max.');
            const summaryCard = (label, value, color, note) => '<div style="flex:1 1 150px;min-width:145px;padding:.65rem .75rem;border:1px solid var(--dsh-border);border-radius:12px;background:var(--card-bg,#fff);"><div style="font-size:.65rem;color:var(--dsh-muted);">' + label + '</div><div style="font-size:1rem;font-weight:800;color:' + color + ';margin-top:.15rem;">' + value + '</div><div style="font-size:.62rem;color:var(--dsh-muted);margin-top:.1rem;">' + note + '</div></div>';
            summaryEl.innerHTML = summaryCard('Produk GMV Max', Number(payload.total_items || 0).toLocaleString('id-ID'), 'var(--text)', 'terjual pada periode ini')
                + summaryCard('HPP sudah tersedia', Number(payload.mapped_items || 0).toLocaleString('id-ID'), '#15803d', 'profit dapat dihitung')
                + summaryCard('Perlu mapping', Number(payload.unmapped_items || 0).toLocaleString('id-ID'), payload.unmapped_items > 0 ? '#b45309' : '#15803d', payload.unmapped_items > 0 ? 'pilih item internal' : 'semua sudah siap');
            if (!payload.data || !payload.data.length) {
                bodyEl.innerHTML = '<div style="padding:1.2rem;text-align:center;color:var(--dsh-muted);font-size:.75rem;">Belum ada data item GMV Max. Jalankan sync GMS terlebih dahulu.</div>';
                return;
            }
            const rows = payload.data.map(function (item) {
                const mapped = item.mapped;
                const profit = item.profit_after_ads;
                const profitText = profit === null ? 'N/A' : (profit < 0 ? '−' : '') + fmtRp(profit);
                const itemName = item.item_name || 'Produk tanpa nama';
                const pcsNote = item.pcs_source === 'api' ? 'pcs dari data penjualan' : 'pcs estimasi dari order';
                const mappingStatus = mapped
                    ? '<span style="color:#15803d;font-weight:700;"><i class="bi bi-check-circle"></i> HPP tersedia</span><div style="font-size:.62rem;color:var(--dsh-muted);margin-top:.15rem;max-width:170px;overflow:hidden;text-overflow:ellipsis;">' + esc(item.internal_item_code || item.internal_item_name || item.mapping_source || 'Item internal') + '</div>'
                    : '<span style="color:#b45309;font-weight:700;"><i class="bi bi-exclamation-circle"></i> HPP belum ada</span><button type="button" class="btn btn-sm d-block mt-1" style="font-size:.62rem;padding:.16rem .48rem;border-radius:999px;color:#b45309;border:1px solid rgba(180,83,9,.35);background:rgba(217,119,6,.06);" data-gms-map-store="' + esc(storeId) + '" data-gms-map-item="' + esc(item.channel_item_id) + '" data-gms-map-name="' + esc(itemName) + '" onclick="openGmsItemMapping(this)"><i class="bi bi-link-45deg"></i> Pilih item HPP</button>';
                return '<tr>' +
                    '<td><div style="font-weight:700;max-width:280px;overflow:hidden;text-overflow:ellipsis;" title="' + esc(itemName) + '">' + esc(itemName) + '</div><div style="font-size:.64rem;color:var(--dsh-muted);">SKU ' + esc(item.item_sku || '-') + ' · ID ' + esc(item.channel_item_id) + '</div></td>' +
                    '<td class="text-end"><div style="font-weight:700;">' + Number(item.orders || 0).toLocaleString('id-ID') + ' order · ' + Number(item.pcs || 0).toLocaleString('id-ID') + ' pcs</div><div style="font-size:.62rem;color:var(--dsh-muted);">' + pcsNote + '</div></td>' +
                    '<td class="text-end"><div style="font-weight:700;">' + fmtRp(item.gmv) + '</div><div style="font-size:.62rem;color:var(--dsh-muted);">Dana cair ± ' + fmtRp(item.net_revenue) + '</div></td>' +
                    '<td class="text-end" style="color:#dc2626;"><div style="font-weight:700;">−' + fmtRp(Number(item.spend || 0) * 1.11) + '</div><div style="font-size:.62rem;color:var(--dsh-muted);">termasuk PPN 11%</div></td>' +
                    '<td class="text-end">' + (mapped ? fmtRp(item.unit_cogs) + '<div style="font-size:.62rem;color:var(--dsh-muted);">per pcs</div>' : '<span style="color:#b45309;">—</span>') + '</td>' +
                    '<td class="text-end">' + (item.hpp_total !== null ? '−' + fmtRp(item.hpp_total) : '<span style="color:#b45309;">—</span>') + '</td>' +
                    '<td class="text-end" style="font-weight:800;color:' + (profit === null ? '#b45309' : (profit >= 0 ? '#15803d' : '#b91c1c')) + ';">' + profitText + (profit === null ? '<div style="font-size:.62rem;font-weight:500;color:var(--dsh-muted);">menunggu HPP</div>' : '') + '</td>' +
                    '<td>' + mappingStatus + '</td>' +
                    '</tr>';
            }).join('');
            bodyEl.innerHTML = '<table class="dpanel-table dpanel-table-sm w-100" style="white-space:nowrap;min-width:1120px;"><thead><tr><th>Produk</th><th class="text-end">Penjualan</th><th class="text-end">Omzet kotor<br><span style="font-size:.6rem;font-weight:500;text-transform:none;">dana cair</span></th><th class="text-end">Biaya iklan<br><span style="font-size:.6rem;font-weight:500;text-transform:none;">setelah PPN</span></th><th class="text-end">HPP / pcs</th><th class="text-end">Total HPP</th><th class="text-end">Profit setelah iklan</th><th>Status HPP</th></tr></thead><tbody>' + rows + '</tbody></table>';
        } catch (error) {
            bodyEl.innerHTML = '<div style="padding:1rem;color:#b91c1c;font-size:.75rem;">' + esc(error.message) + '</div>';
        }
    };
})();
</script>
@endif

<script>
let sortProfitCol = 'net_profit';
let sortProfitDir = 'asc';

function sortProfitTable(col) {
    if (sortProfitCol === col) {
        sortProfitDir = sortProfitDir === 'desc' ? 'asc' : 'desc';
    } else {
        sortProfitCol = col;
        sortProfitDir = 'desc';
    }

    const tbody = document.querySelector('#profitViewCampaign tbody');
    if (!tbody) return;
    
    const rows = Array.from(tbody.querySelectorAll('tr[data-campaign]'));
    if (rows.length === 0) return;

    rows.sort((a, b) => {
        let valA, valB;
        if (col === 'campaign') {
            valA = a.getAttribute('data-campaign') || '';
            valB = b.getAttribute('data-campaign') || '';
            return sortProfitDir === 'asc' ? valA.localeCompare(valB) : valB.localeCompare(valA);
        } else {
            valA = parseFloat(a.getAttribute('data-' + col)) || 0;
            valB = parseFloat(b.getAttribute('data-' + col)) || 0;
            return sortProfitDir === 'asc' ? valA - valB : valB - valA;
        }
    });

    rows.forEach(row => tbody.appendChild(row));
}
</script>
