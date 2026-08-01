
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
        $hasSales      = (float) ($camp->gmv ?? 0) > 0 || (int) ($camp->orders ?? 0) > 0 || $itemsSold > 0;
        $totalCogsRow  = !$hasSales
            ? 0.0
            : ($camp->total_cogs !== null
            ? (float) $camp->total_cogs
            : null);
        $spendAfterTax = $camp->spend * 1.11; // PPN 11% topup iklan
        $profit        = !$hasSales
            ? -$spendAfterTax
            : ($camp->profit_after_ads !== null
            ? (float) $camp->profit_after_ads
            : null);
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
    // Parent GMV Max adalah agregat, bukan produk/SKU. Tetap dipakai untuk
    // KPI profit di atas, tetapi tidak ditampilkan sebagai baris campaign.
    $displayRows = $rows->reject(fn ($r) => str_starts_with((string) ($r->camp->channel_campaign_id ?? ''), 'GMS-'))->values();

    // ── Group by KATEGORI (hasil mapping SKU produk marketplace → item internal) ──
    $byCategory = $displayRows
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
    // Selisih Seller Center vs rincian campaign adalah belanja GMV Max Auto
    // yang tidak muncul sebagai campaign parent. Masukkan ke hitungan utama
    // sebelum menghitung seluruh KPI turunan.
    $shopSpend       = isset($kpi['current']) ? (float) ($kpi['current']->spend ?? 0) : null;
    $sellerCenterSpend = $shopSpend !== null ? $shopSpend : $totalSpend;
    $gmsSpendFromRows = $rows
        ->filter(fn ($r) => str_starts_with((string) ($r->camp->channel_campaign_id ?? ''), 'GMS-'))
        ->sum(fn ($r) => (float) ($r->camp->spend ?? 0));
    $gmvAutoSpend    = $shopSpend !== null ? min($sellerCenterSpend, $gmsSpendFromRows) : 0;
    $unattributedSpend = $shopSpend !== null
        ? max(0, $sellerCenterSpend - $rows->sum(fn ($r) => (float) ($r->camp->spend ?? 0)))
        : 0;
    $totalSpend      = $sellerCenterSpend;
    $totalTopup      = $sellerCenterSpend * 1.11;
    // GMS Auto yang ada sebagai row sudah masuk ke knownRows sebagai rugi
    // biaya iklan saat tidak ada penjualan. Hanya biaya tanpa row yang perlu
    // dikurangkan di sini agar tidak dihitung dua kali.
    $totalProfit    -= $unattributedSpend * 1.11;
    $totalSellerCenterSpend = max(0, $sellerCenterSpend);
    $regularCampaignSpend = max(0, $sellerCenterSpend - $gmvAutoSpend);
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
    $profitPerOrder  = $totalOrders > 0 ? $totalProfit / $totalOrders : 0;
    $tacosPct        = $totalGmv > 0 ? ($totalTopup / $totalGmv) * 100 : 0;
    $beRoas          = $grossMargin > 0 ? (1.11 * $knownGmv) / $grossMargin : 0;   // Break-even ROAS; spend termasuk PPN
    $mappableProfitRows = $rows->filter(function ($r) {
        $campaignId = (string) ($r->camp->channel_campaign_id ?? '');
        $isGms = str_starts_with($campaignId, 'GMS-');
        return $r->totalCogs === null && (!empty($r->camp->channel_item_id) || $isGms);
    })->sortByDesc(fn ($r) => (float) ($r->camp->spend ?? 0))->values();
    $gmsProfitRows = $rows->filter(fn ($r) => str_starts_with((string) ($r->camp->channel_campaign_id ?? ''), 'GMS-'));
    $gmsKnownRows = $gmsProfitRows->filter(fn ($r) => $r->profit !== null);
    $gmsTotalGmv = $gmsProfitRows->sum(fn ($r) => (float) $r->camp->gmv);
    $gmsTotalSpend = $gmsProfitRows->sum(fn ($r) => (float) $r->camp->spend);
    $gmsTotalOrders = $gmsProfitRows->sum(fn ($r) => (int) $r->camp->orders);
    $gmsTotalProfit = $gmsKnownRows->sum('profit');
    $gmsUnknownCount = $gmsProfitRows->count() - $gmsKnownRows->count();
    $gmsRoas = $gmsTotalSpend > 0 ? $gmsTotalGmv / $gmsTotalSpend : 0;
    $gmsMarginPct = $gmsTotalGmv > 0 ? ($gmsTotalProfit / $gmsTotalGmv) * 100 : 0;
    $roasKnownRows = $displayRows->filter(fn ($r) => $r->profit !== null);
    $roasTotalGmv = $displayRows->sum(fn ($r) => (float) $r->camp->gmv);
    $roasTotalSpend = $displayRows->sum(fn ($r) => (float) $r->camp->spend);
    $roasTotalOrders = $displayRows->sum(fn ($r) => (int) $r->camp->orders);
    $roasTotalProfit = $roasKnownRows->sum('profit');
    $roasUnknownCount = $displayRows->count() - $roasKnownRows->count();
    $roasTotalCogs = $roasKnownRows->sum('totalCogs');
    $roasKnownGmv = $roasKnownRows->sum(fn ($r) => (float) $r->camp->gmv);
    $roasValue = $roasTotalSpend > 0 ? $roasTotalGmv / $roasTotalSpend : 0;
    $roasMarginPct = $roasKnownGmv > 0 ? ($roasTotalProfit / $roasKnownGmv) * 100 : 0;
    $roasPreviousKnownRows = $displayRows->filter(fn ($r) => $r->camp->prev_profit_after_ads !== null);
    $roasPreviousGmv = $displayRows->sum(fn ($r) => (float) ($r->camp->prev_gmv ?? 0));
    $roasPreviousSpend = $displayRows->sum(fn ($r) => (float) ($r->camp->prev_spend ?? 0));
    $roasPreviousProfit = $roasPreviousKnownRows->sum(fn ($r) => (float) ($r->camp->prev_profit_after_ads ?? 0));
    $roasPreviousCogs = $roasPreviousKnownRows->sum(fn ($r) => (float) ($r->camp->prev_total_cogs ?? 0));
    $roasPreviousValue = $roasPreviousSpend > 0 ? $roasPreviousGmv / $roasPreviousSpend : 0;
    $gmsPreviousGmv = $gmsProfitRows->sum(fn ($r) => (float) ($r->camp->prev_gmv ?? 0));
    $gmsPreviousSpend = $gmsProfitRows->sum(fn ($r) => (float) ($r->camp->prev_spend ?? 0));
    $gmsPreviousKnownRows = $gmsProfitRows->filter(fn ($r) => $r->camp->prev_profit_after_ads !== null);
    $gmsPreviousProfit = $gmsPreviousKnownRows->sum(fn ($r) => (float) ($r->camp->prev_profit_after_ads ?? 0));
    $gmsPreviousCogs = $gmsPreviousKnownRows->sum(fn ($r) => (float) ($r->camp->prev_total_cogs ?? 0));
    $gmsPreviousValue = $gmsPreviousSpend > 0 ? $gmsPreviousGmv / $gmsPreviousSpend : 0;
    $mappableProfitRows = $mappableProfitRows
        ->reject(fn ($r) => str_starts_with((string) ($r->camp->channel_campaign_id ?? ''), 'GMS-'))
        ->values();
    // Subtab produk hanya menampilkan campaign regular yang punya item/SKU.
    // GMV Max Auto dimuat dari endpoint item-performance saat tab dibuka.
    $productUnmappedRows = $mappableProfitRows
        ->filter(fn ($r) => !empty($r->camp->channel_item_id))
        ->values();
    // Saat satu toko dipilih, tetap tampilkan GMV Max Auto walaupun periode
    // belum memiliki baris campaign parent; data item akan diambil dari endpoint.
    $hasGmsTab = $gmsProfitRows->isNotEmpty() || (($storeId ?? 'all') !== 'all' && is_numeric($storeId));
    $gmsStoreIds = $gmsProfitRows->pluck('camp.store_id')->filter()->unique()->values()->all();
    if ($hasGmsTab && is_numeric($storeId) && !in_array((int) $storeId, array_map('intval', $gmsStoreIds), true)) {
        $gmsStoreIds[] = (int) $storeId;
    }
    $hasProductUnmappedTab = $productUnmappedRows->isNotEmpty() || $hasGmsTab;
    
    $feeMode         = $adsSetting->admin_fee_mode ?? 'auto';
    $feeManualPct    = $adsSetting->admin_fee_pct ?? null;
    $manualFeeValue  = $feeManualPct !== null ? (float) $feeManualPct : 21.9;
    $autoFeeValue    = (float) $avgFeePct;
    $activeFeeValue  = $feeMode === 'manual' ? $manualFeeValue : $autoFeeValue;
    $compositionFeePct = $activeFeeValue;
    $compositionFeeAmt = $totalGmv * ($compositionFeePct / 100);
    $fmt = fn ($n) => 'Rp ' . number_format(abs($n), 0, ',', '.');
    $comparisonLabel = match ($compareMode ?? 'prev_period') {
        'prev_month' => 'bulan lalu',
        'prev_year' => 'tahun lalu',
        default => 'periode lalu',
    };
    $previousKpi = $kpi['previous'] ?? (object) [];
    $previousSpend = (float) ($previousKpi->spend ?? 0);
    $previousGmv = (float) ($previousKpi->gmv ?? 0);
    $previousOrders = (int) ($previousKpi->orders ?? 0);
    $previousTopup = $previousSpend * 1.11;
    $previousGrossProfit = (float) ($previousKpi->net_revenue ?? 0) - (float) ($previousKpi->total_cogs ?? 0);
    $previousProfit = (float) ($previousKpi->net_profit ?? 0);
    $previousNetMarginPct = $previousGmv > 0 ? ($previousProfit / $previousGmv) * 100 : 0;
    $previousRoas = $previousSpend > 0 ? $previousGmv / $previousSpend : 0;
    $previousPoas = $previousTopup > 0 ? $previousProfit / $previousTopup : 0;
    $previousBeRoas = $previousGrossProfit > 0 ? (1.11 * $previousGmv) / $previousGrossProfit : 0;
    $previousCac = $previousOrders > 0 ? $previousTopup / $previousOrders : 0;
    $previousProfitPerOrder = $previousOrders > 0 ? $previousProfit / $previousOrders : 0;
    $previousTacosPct = $previousGmv > 0 ? ($previousTopup / $previousGmv) * 100 : 0;
    $formatCompareValue = function (float $value, string $unit): string {
        $sign = $value < 0 ? '−' : '';
        $absolute = abs($value);
        return match ($unit) {
            'currency' => $sign . 'Rp ' . number_format($absolute, 0, ',', '.'),
            'percent' => $sign . number_format($absolute, 1, ',', '.') . '%',
            'multiple' => $sign . number_format($absolute, 2, ',', '.') . 'x',
            default => $sign . number_format($absolute, 0, ',', '.'),
        };
    };
    $profitCompare = function (float $current, float $previous, bool $cost = false, string $unit = 'currency') use ($formatCompareValue): array {
        if ($previous == 0) {
            return [
                'value' => null,
                'good' => true,
                'is_new' => abs($current) > 0,
                'previous' => $previous,
                'previous_display' => $formatCompareValue($previous, $unit),
            ];
        }
        $change = (($current - $previous) / abs($previous)) * 100;
        return [
            'value' => round($change, 1),
            'good' => $cost ? $change <= 0 : $change >= 0,
            'previous' => $previous,
            'previous_display' => $formatCompareValue($previous, $unit),
        ];
    };
@endphp
<script>
window.__profitChartData = {
    totalRev: {{ $totalGmv }},
    totalNetRev: {{ $totalNetRevenue }},
    totalCogs: {{ $totalCogsAll }},
    totalSpend: {{ $totalSpend }},
    totalTopup: {{ $totalTopup }},
    totalProfit: {{ $totalProfit }},
    feeAmt: {{ $compositionFeeAmt }},
    feePct: {{ $compositionFeePct }}
};
</script>
@if($shopSpend !== null && $shopSpend > 0)
<div class="profit-recon-grid">
    <div class="profit-recon-card">
        <div class="profit-recon-label"><i class="bi bi-shop"></i> Total Seller Center</div>
        <div class="profit-recon-value">{{ $fmt($totalSellerCenterSpend) }}</div>
        <div class="profit-recon-label" style="margin-top:.15rem;">+PPN {{ $fmt($totalSellerCenterSpend * 1.11) }}</div>
    </div>
    <div class="profit-recon-card">
        <div class="profit-recon-label"><i class="bi bi-megaphone"></i> Kampanye Regular</div>
        <div class="profit-recon-value">{{ $fmt($regularCampaignSpend) }}</div>
    </div>
    <div class="profit-recon-card">
        <div class="profit-recon-label"><i class="bi bi-stars"></i> GMV Max Auto <span style="font-size:.58rem;">sebelum PPN</span></div>
        <div class="profit-recon-value" style="color:#b45309;">{{ $fmt($gmvAutoSpend) }}</div>
    </div>
    <div class="profit-recon-card">
        <div class="profit-recon-label"><i class="bi bi-check-circle"></i> Status BEP</div>
        <div class="profit-recon-value" style="color:{{ $totalProfit >= 0 ? '#15803d' : '#b91c1c' }};">{{ $totalProfit >= 0 ? 'Sudah BEP' : 'Belum BEP' }}</div>
        <div class="profit-recon-label" style="margin-top:.15rem;">{{ $totalProfit >= 0 ? 'Surplus ' : 'Defisit ' }}{{ $fmt($totalProfit) }}</div>
    </div>
</div>
@endif

<!-- ── Pengaturan admin fee: otomatis (settlement) / manual ── -->
@if(($storeId ?? null) !== 'all')
<div class="ads-tab-panel mb-3 profit-fee-panel">
    <div class="ads-tab-panel-head">
        <div>
            <div class="ads-tab-panel-title">
                <i class="bi bi-gear" style="color: var(--dsh-accent);"></i> Pengaturan Fee
            </div>
        </div>
    </div>
    <div class="p-3">
        <form id="profitAdminFeeForm" method="POST" action="{{ route('marketplace.ads.fee.setting') }}" class="profit-fee-form">
            @csrf
            <input type="hidden" name="store_id" value="{{ $storeId }}">
            <div>
                <label class="profit-fee-label">Mode Admin Fee</label>
                <div class="profit-fee-switch-row">
                    <span>Manual</span>
                    <label class="profit-fee-switch">
                        <input id="profitAdminFeeModeToggle" type="checkbox" {{ $feeMode !== 'manual' ? 'checked' : '' }}>
                        <span class="profit-fee-switch-slider"></span>
                    </label>
                    <span>Otomatis</span>
                    <input id="profitAdminFeeMode" type="hidden" name="admin_fee_mode" value="{{ $feeMode !== 'manual' ? 'auto' : 'manual' }}">
                </div>
            </div>
            <div class="profit-fee-field">
                <label class="profit-fee-label">Admin Fee %</label>
                <div id="profitAdminFeeDisplay" class="profit-fee-display" role="button" tabindex="0" style="display:inline-flex; align-items:center; gap:.35rem; min-height:38px; padding:.35rem .7rem; border:1px solid var(--dsh-border); border-radius:10px; color:var(--text); background:var(--bg); font-size:1rem; font-weight:800; font-variant-numeric:tabular-nums; cursor:pointer;">
                    {{ number_format($activeFeeValue, 1, ',', '.') }}%
                </div>
                <div id="profitAdminFeeEditor" class="input-group profit-fee-editor" style="display:none;">
                    <input id="profitAdminFeePct" type="number" name="admin_fee_pct" step="0.1" min="0" max="99" value="{{ number_format($activeFeeValue, 1, '.', '') }}" class="form-control" style="border-radius:10px 0 0 10px; font-size:.85rem; background:var(--bg); color:var(--text); border-color:var(--dsh-border);">
                    <span class="input-group-text" style="border-radius:0 10px 10px 0; background:var(--bg); color:var(--dsh-muted); border-color:var(--dsh-border);">%</span>
                </div>
                <div id="profitAdminFeeStatus" aria-live="polite" style="display:none; font-size:.64rem; color:var(--dsh-muted); margin-top:.2rem;"></div>
            </div>
        </form>
    </div>
</div>
@endif

@if(($storeId ?? null) !== 'all')
<script>
(function () {
    const form = document.getElementById('profitAdminFeeForm');
    const display = document.getElementById('profitAdminFeeDisplay');
    const editor = document.getElementById('profitAdminFeeEditor');
    const modeToggle = document.getElementById('profitAdminFeeModeToggle');
    const modeInput = document.getElementById('profitAdminFeeMode');
    const input = document.getElementById('profitAdminFeePct');
    const status = document.getElementById('profitAdminFeeStatus');
    if (!form || !display || !editor || !modeToggle || !modeInput || !input || !status) return;
    let timer = null;
    let saving = false;
    const autoFeeValue = Number({{ (float) $autoFeeValue }});
    let manualFeeValue = Number({{ (float) $manualFeeValue }});

    function setStatus(message, color) {
        status.textContent = message;
        status.style.color = color || 'var(--dsh-muted)';
        status.style.display = message ? 'block' : 'none';
    }

    function syncModeState() {
        const mode = modeToggle.checked ? 'auto' : 'manual';
        modeInput.value = mode;
        if (mode === 'auto') {
            input.value = autoFeeValue.toFixed(1);
            display.textContent = autoFeeValue.toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%';
        } else {
            input.value = manualFeeValue.toFixed(1);
            display.textContent = manualFeeValue.toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%';
        }
        input.disabled = mode !== 'manual';
        input.style.opacity = mode === 'manual' ? '1' : '.6';
        display.style.cursor = mode === 'manual' ? 'pointer' : 'default';
    }

    function openEditor() {
        const mode = modeToggle.checked ? 'auto' : 'manual';
        if (mode !== 'manual') return;
        display.style.display = 'none';
        editor.style.display = 'flex';
        input.focus();
        input.select();
    }

    async function saveFee() {
        if (saving) return;
        const value = Number(input.value);
        if (!Number.isFinite(value) || value < 0 || value > 99) {
            setStatus('Masukkan fee 0–99%.', '#b91c1c');
            return;
        }
        saving = true;
        setStatus('Menyimpan…', '#2563eb');
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': form.querySelector('input[name="_token"]')?.value || '', 'Accept': 'application/json' },
                body: new URLSearchParams(new FormData(form))
            });
            if (!response.ok) throw new Error('Gagal menyimpan fee.');
            setStatus('', '#15803d');
            setTimeout(() => window.location.reload(), 350);
        } catch (error) {
            setStatus(error.message, '#b91c1c');
        } finally {
            saving = false;
        }
    }

    form.addEventListener('submit', (event) => { event.preventDefault(); saveFee(); });
    display.addEventListener('click', openEditor);
    display.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') openEditor();
    });
    input.addEventListener('change', () => {
        manualFeeValue = Number(input.value);
        saveFee();
    });
    modeToggle.addEventListener('change', () => {
        syncModeState();
        clearTimeout(timer);
        timer = setTimeout(saveFee, 120);
    });
    syncModeState();
})();
</script>
@endif

<!-- ── KPI — urutan alur hitung: Omzet − HPP − Iklan = Net Profit ── -->
@php
    $profitMarginPct = $totalGmv > 0 ? ($totalProfit / $totalGmv) * 100 : 0;
@endphp
<div class="ads-tab-panel mb-3">
    <div class="ads-tab-panel-head profitability-panel-head">
        <div>
            <div class="ads-tab-panel-title"><i class="bi bi-cash-stack text-success"></i> Profitabilitas &amp; Margin</div>
            <div class="ads-tab-panel-note">Omzet − HPP − iklan · vs {{ $comparisonLabel }}</div>
        </div>
        <select name="compare_mode" form="filterForm" onchange="submitAdsFilters(document.getElementById('filterForm'))" class="form-select profit-compare-select" title="Pilih dasar perbandingan KPI">
            <option value="prev_period" {{ ($compareMode ?? 'prev_period') === 'prev_period' ? 'selected' : '' }}>Bandingkan: Periode lalu</option>
            <option value="prev_month" {{ ($compareMode ?? 'prev_period') === 'prev_month' ? 'selected' : '' }}>Bandingkan: Bulan lalu</option>
            <option value="prev_year" {{ ($compareMode ?? 'prev_period') === 'prev_year' ? 'selected' : '' }}>Bandingkan: Tahun lalu</option>
        </select>
    </div>
    <div class="p-3 p-md-3">
        <div class="ads-kpi-grid mb-3">
            <div class="dpanel ads-kpi kpi-revenue">
                <div class="ads-kpi-label" title="Total nilai penjualan sebelum biaya"><i class="bi bi-graph-up-arrow"></i> Omzet Kotor</div>
                <div class="ads-kpi-value" style="font-variant-numeric:tabular-nums;">{{ $fmt($totalGmv) }}</div>
                <div class="ads-kpi-sub">Net {{ $fmt($totalNetRevenue) }} · {{ number_format($avgFeePct, 1, ',', '.') }}%</div>
                @include('marketplace.partials._profit_kpi_compare', ['cmp' => $profitCompare((float) $totalGmv, $previousGmv, false, 'currency'), 'label' => $comparisonLabel])
            </div>
            
            <div class="dpanel ads-kpi kpi-cogs">
                <div class="ads-kpi-label" title="Omzet setelah dikurangi HPP"><i class="bi bi-box-seam"></i> Laba Kotor</div>
                <div class="ads-kpi-value" style="font-variant-numeric:tabular-nums;">{{ $grossMargin < 0 ? '−' : '' }}{{ $fmt($grossMargin) }}</div>
                <div class="ads-kpi-sub">HPP {{ $fmt($totalCogsAll) }}
                @if($noHppCount > 0)
                    · <span class="text-warning"><i class="bi bi-exclamation-triangle"></i> {{ $noHppCount }} belum lengkap</span>
                @endif
                </div>
                @include('marketplace.partials._profit_kpi_compare', ['cmp' => $profitCompare((float) $grossMargin, $previousGrossProfit, false, 'currency'), 'label' => $comparisonLabel])
            </div>

            <div class="dpanel ads-kpi kpi-spend">
                <div class="ads-kpi-label" title="Total biaya iklan termasuk PPN"><i class="bi bi-wallet2"></i> Biaya Iklan</div>
                <div class="ads-kpi-value" style="font-variant-numeric:tabular-nums;">&minus;{{ $fmt($totalTopup) }}</div>
                <div class="ads-kpi-sub">SC net {{ $fmt($sellerCenterSpend) }}</div>
                @include('marketplace.partials._profit_kpi_compare', ['cmp' => $profitCompare((float) $totalTopup, $previousTopup, true, 'currency'), 'label' => $comparisonLabel])
            </div>

            <div class="dpanel ads-kpi kpi-profit" style="border-left-color: {{ $unknownProfitCount > 0 ? '#d97706' : ($totalProfit >= 0 ? '#16a34a' : '#dc2626') }};">
                <div class="ads-kpi-label" title="Laba setelah HPP dan biaya iklan"><i class="bi bi-cash-stack"></i> Laba Bersih</div>
                <div class="ads-kpi-value" style="font-variant-numeric:tabular-nums; color: {{ $unknownProfitCount > 0 ? '#b45309' : ($totalProfit >= 0 ? '#16a34a' : '#dc2626') }};">{{ $totalProfit < 0 ? '−' : '' }}{{ $fmt($totalProfit) }}</div>
                <div class="ads-kpi-sub">{{ $profitableCount }} untung · {{ $lossCount }} rugi @if($unknownProfitCount > 0) · <span class="text-warning">{{ $unknownProfitCount }} belum lengkap</span>@endif</div>
                @include('marketplace.partials._profit_kpi_compare', ['cmp' => $profitCompare((float) $totalProfit, $previousProfit, false, 'currency'), 'label' => $comparisonLabel])
            </div>

            <div class="dpanel ads-kpi">
                <div class="ads-kpi-label" title="Persentase laba bersih dari omzet"><i class="bi bi-percent"></i> Margin Bersih</div>
                <div class="ads-kpi-value" style="font-variant-numeric:tabular-nums; color:{{ $netMarginPct >= 0 ? '#16a34a' : '#dc2626' }};">{{ number_format($netMarginPct, 1, ',', '.') }}%</div>
                <div class="ads-kpi-sub">Kotor {{ number_format($grossMarginPct, 1, ',', '.') }}%</div>
                @include('marketplace.partials._profit_kpi_compare', ['cmp' => $profitCompare((float) $netMarginPct, $previousNetMarginPct, false, 'percent'), 'label' => $comparisonLabel])
            </div>

            <div class="dpanel ads-kpi">
                <div class="ads-kpi-label" title="Laba bersih dibanding biaya iklan setelah PPN"><i class="bi bi-speedometer2"></i> POAS</div>
                <div class="ads-kpi-value" style="font-variant-numeric:tabular-nums;">{{ number_format($poas, 2, ',', '.') }}x</div>
                <div class="ads-kpi-sub">ROAS Omzet {{ number_format($totalRoas, 2, ',', '.') }}x</div>
                @include('marketplace.partials._profit_kpi_compare', ['cmp' => $profitCompare((float) $poas, $previousPoas, false, 'multiple'), 'label' => $comparisonLabel])
            </div>

            <div class="dpanel ads-kpi">
                <div class="ads-kpi-label" title="ROAS minimum agar tidak rugi"><i class="bi bi-dash-circle-dotted"></i> ROAS Impas</div>
                <div class="ads-kpi-value" style="font-variant-numeric:tabular-nums;">{{ number_format($beRoas, 2, ',', '.') }}x</div>
                <div class="ads-kpi-sub">Target minimum</div>
                @include('marketplace.partials._profit_kpi_compare', ['cmp' => $profitCompare((float) $beRoas, $previousBeRoas, true, 'multiple'), 'label' => $comparisonLabel])
            </div>

            <div class="dpanel ads-kpi">
                <div class="ads-kpi-label" title="Biaya iklan untuk mendapatkan satu order"><i class="bi bi-person-plus"></i> Biaya Iklan / Order</div>
                <div class="ads-kpi-value" style="font-variant-numeric:tabular-nums;">{{ $fmt($cac) }}</div>
                <div class="ads-kpi-sub">Per order</div>
                @include('marketplace.partials._profit_kpi_compare', ['cmp' => $profitCompare((float) $cac, $previousCac, true, 'currency'), 'label' => $comparisonLabel])
            </div>

            <div class="dpanel ads-kpi">
                <div class="ads-kpi-label" title="Laba bersih rata-rata per order"><i class="bi bi-cash-coin"></i> Laba / Order</div>
                <div class="ads-kpi-value" style="font-variant-numeric:tabular-nums; color:{{ $profitPerOrder >= 0 ? '#16a34a' : '#dc2626' }};">{{ $profitPerOrder < 0 ? '−' : '' }}{{ $fmt($profitPerOrder) }}</div>
                <div class="ads-kpi-sub">Rata-rata</div>
                @include('marketplace.partials._profit_kpi_compare', ['cmp' => $profitCompare((float) $profitPerOrder, $previousProfitPerOrder, false, 'currency'), 'label' => $comparisonLabel])
            </div>

            <div class="dpanel ads-kpi">
                <div class="ads-kpi-label" title="Persentase omzet yang dipakai untuk iklan"><i class="bi bi-pie-chart"></i> Iklan / Omzet (TACOS)</div>
                <div class="ads-kpi-value" style="font-variant-numeric:tabular-nums;">{{ number_format($tacosPct, 1, ',', '.') }}%</div>
                <div class="ads-kpi-sub">Porsi omzet</div>
                @include('marketplace.partials._profit_kpi_compare', ['cmp' => $profitCompare((float) $tacosPct, $previousTacosPct, true, 'percent'), 'label' => $comparisonLabel])
            </div>
        </div>
        
        {{-- CHARTS PROFITABILITAS --}}
        <div class="row gx-3">
            <div class="col-md-5 mb-3">
                <div class="p-2 border rounded h-100" style="background: var(--card-bg);">
                    <div class="fw-bold mb-1 text-center text-muted" style="font-size: 0.75rem;"><i class="bi bi-pie-chart-fill me-1"></i> Komposisi Omzet</div>
                    <div class="profit-composition-layout">
                        <div class="profit-composition-chart">
                            <canvas id="chartProfitComposition"></canvas>
                        </div>
                        <div id="profitCompositionLegend" class="profit-composition-legend"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-7 mb-3">
                <div class="p-2 border rounded h-100" style="background: var(--card-bg);">
                    <div class="fw-bold mb-2 text-center text-muted" style="font-size: 0.75rem;"><i class="bi bi-graph-up me-1"></i> Trend Harian</div>
                    <div class="profit-trend-chart-box">
                        <canvas id="chartProfitTrend"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .profit-trend-chart-box {
        position:relative;
        height:240px;
        width:100%;
        margin-top:.25rem;
    }
    .gms-profit-compare-mobile { display:none; }
    @media (max-width:575.98px) {
        .profit-trend-chart-box { height:220px; }
    }
    .profit-compare-select {
        width:auto;
        min-width:155px;
        border-radius:9px;
        padding:.35rem 1.8rem .35rem .65rem;
        border:1px solid var(--dsh-border);
        background:var(--card-bg);
        color:var(--text);
        font-size:.68rem;
        font-weight:700;
    }
    .ads-tab-panel-note {
        font-size:.58rem !important;
        line-height:1.15;
    }
    .ads-kpi-grid .ads-kpi-sub {
        font-size:.56rem;
    }
    @media (max-width:575.98px) {
        .profitability-panel-head { align-items:center; gap:.55rem; padding:.6rem .7rem; }
        .profitability-panel-head > div { min-width:0; flex:1 1 auto; }
        .profit-compare-select { width:auto; min-width:132px; flex:0 0 auto; margin-top:0; }
    }
    .profit-composition-legend {
        display:grid;
        grid-template-columns:1fr;
        gap:.35rem .65rem;
        margin:.2rem 0 0;
    }
    .profit-composition-layout {
        display:grid;
        grid-template-columns:minmax(155px,.95fr) minmax(145px,1.05fr);
        align-items:center;
        gap:.65rem;
    }
    .profit-composition-chart {
        position:relative;
        height:170px;
        min-width:0;
    }
    .profit-composition-item {
        display:grid;
        grid-template-columns:8px minmax(0,1fr) minmax(92px,auto);
        align-items:center;
        gap:.3rem;
        min-width:0;
        font-size:.61rem;
        line-height:1.15;
    }
    .profit-composition-dot {
        width:8px;
        height:8px;
        border-radius:50%;
    }
    .profit-composition-name {
        min-width:0;
        color:var(--dsh-muted);
        white-space:normal;
    }
    .profit-composition-value {
        min-width:0;
        color:var(--text);
        font-weight:800;
        white-space:normal;
        text-align:right;
        justify-self:end;
        line-height:1.15;
        font-variant-numeric:tabular-nums;
    }
    @media (max-width:575.98px) {
        .profit-composition-layout { grid-template-columns:1fr; gap:.75rem; }
        .profit-composition-chart { height:190px; }
        .profit-composition-legend { grid-template-columns:repeat(2, minmax(0,1fr)); gap:.5rem .7rem; margin:.1rem .25rem .15rem; }
        .profit-composition-item { grid-template-columns:8px minmax(0,1fr) minmax(78px,auto); font-size:.6rem; }
    }
    .profit-table-wrap { overflow-x: hidden !important; }
    .profit-table-compact {
        width: 100% !important;
        table-layout: fixed;
        white-space: normal !important;
        font-size: .72rem;
    }
    .profit-table-compact th,
    .profit-table-compact td {
        padding: .48rem .4rem !important;
        white-space: normal !important;
        overflow-wrap: anywhere;
        word-break: break-word;
        vertical-align: middle;
    }
    .profit-table-compact td span,
    .profit-table-compact td div {
        max-width: 100% !important;
        white-space: normal !important;
        overflow-wrap: anywhere;
    }
    .profit-table-compact .profit-campaign-product,
    .profit-table-compact .profit-table-item-name {
        display:block;
        min-width:0;
        overflow:hidden !important;
        text-overflow:ellipsis;
        white-space:nowrap !important;
        overflow-wrap:normal !important;
    }
    .profit-table-compact tbody tr:nth-child(even) {
        background-color: rgba(148, 163, 184, .035);
    }
    .profit-table-compact thead th {
        font-size: .65rem;
        letter-spacing: .01em;
        line-height: 1.25;
    }
    .profit-drilldown-trigger {
        cursor:pointer;
        transition:background-color .16s ease, box-shadow .16s ease;
    }
    .profit-drilldown-trigger:hover {
        background-color:rgba(14, 165, 233, .07) !important;
        box-shadow:inset 3px 0 0 var(--dsh-accent);
    }
    .profit-drilldown-hint {
        display:inline-flex;
        align-items:center;
        gap:.22rem;
        margin-top:.16rem;
        color:var(--dsh-muted);
        font-size:.58rem;
        font-weight:650;
    }
    .profit-drilldown-row td {
        padding:.65rem .7rem !important;
        background:var(--bg,#f8fafc) !important;
        border-top:1px solid var(--dsh-border);
        border-bottom:1px solid var(--dsh-border);
    }
    .profit-drilldown-panel {
        padding:.7rem;
        border:1px solid var(--dsh-border);
        border-radius:12px;
        background:var(--card-bg,#fff);
    }
    .profit-drilldown-head {
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:.75rem;
        margin-bottom:.65rem;
    }
    .profit-drilldown-title {
        min-width:0;
        color:var(--text);
        font-size:.74rem;
        font-weight:800;
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
    }
    .profit-drilldown-subtitle {
        margin-top:.12rem;
        color:var(--dsh-muted);
        font-size:.62rem;
    }
    .profit-drilldown-close {
        flex:0 0 auto;
        border:1px solid var(--dsh-border);
        border-radius:999px;
        padding:.18rem .48rem;
        color:var(--dsh-muted);
        background:transparent;
        font-size:.62rem;
        font-weight:700;
    }
    .profit-drilldown-kpis {
        display:grid;
        grid-template-columns:repeat(4,minmax(0,1fr));
        gap:.45rem;
        margin-bottom:.6rem;
    }
    .profit-drilldown-kpi {
        min-width:0;
        padding:.45rem .55rem;
        border:1px solid var(--dsh-border);
        border-radius:9px;
        background:var(--bg,#fff);
    }
    .profit-drilldown-kpi-label {
        color:var(--dsh-muted);
        font-size:.58rem;
        font-weight:650;
    }
    .profit-drilldown-kpi-value {
        margin-top:.13rem;
        color:var(--text);
        font-size:.78rem;
        font-weight:800;
        font-variant-numeric:tabular-nums;
    }
    .profit-drilldown-chart {
        position:relative;
        height:240px;
    }
    @media (max-width:768px) {
        .profit-drilldown-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .profit-drilldown-chart { height:220px; }
        .profit-drilldown-row td { padding:.5rem .4rem !important; }
        .profit-drilldown-panel { padding:.55rem; }
        .profit-drilldown-hint { display:none; }
    }
    .profit-index-cell {
        width: 2.4rem !important;
        min-width: 2.4rem;
        text-align: center !important;
        color: var(--dsh-muted);
        font-size: .66rem;
        font-variant-numeric: tabular-nums;
        white-space: nowrap !important;
    }
    .profit-campaign-cell { width: 22%; }
    .profit-campaign-name {
        display: block;
        min-width: 0;
        overflow: hidden !important;
        text-overflow: ellipsis;
        white-space: nowrap !important;
        overflow-wrap: normal !important;
    }
    .profit-campaign-meta {
        margin-top: .18rem;
        color: var(--dsh-muted);
        font-size: .62rem;
        line-height: 1.2;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis;
    }
    .profit-campaign-product {
        margin-top: .12rem;
        color: var(--dsh-muted);
        font-size: .62rem;
        line-height: 1.2;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis;
    }
    .profit-roas-table {
        font-size: .68rem;
    }
    .profit-roas-table .profit-campaign-name {
        font-size: .74rem !important;
    }
    .profit-fee-form {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        gap: .6rem .9rem;
        align-items: end;
        max-width: 620px;
        margin: 0 auto;
    }
    .profit-fee-panel .ads-tab-panel-head { padding:.65rem .85rem; }
    .profit-fee-panel > .p-3 { padding:.7rem .85rem !important; }
    .profit-fee-label {
        display: block;
        margin-bottom: .25rem;
        color: var(--dsh-muted);
        font-size: .64rem;
        font-weight: 700;
    }
    .profit-fee-mode-options { display:flex; gap:.45rem; flex-wrap:wrap; }
    .profit-fee-mode-option {
        display:inline-flex;
        align-items:center;
        gap:.4rem;
        min-height:32px;
        padding:.25rem .55rem;
        border:1px solid var(--dsh-border);
        border-radius:10px;
        cursor:pointer;
        background:var(--bg);
        color:var(--text);
        flex:1 1 120px;
        justify-content:center;
        font-size:.7rem;
    }
    .profit-fee-switch-row { display:flex; align-items:center; gap:.55rem; min-height:38px; color:var(--dsh-muted); font-size:.7rem; font-weight:700; }
    .profit-fee-switch { position:relative; display:inline-block; width:38px; height:21px; flex:0 0 auto; }
    .profit-fee-switch input { opacity:0; width:0; height:0; }
    .profit-fee-switch-slider { position:absolute; inset:0; cursor:pointer; border-radius:999px; background:#94a3b8; transition:.2s ease; }
    .profit-fee-switch-slider::before { content:""; position:absolute; width:17px; height:17px; left:2px; top:2px; border-radius:50%; background:#fff; box-shadow:0 1px 3px rgba(15,23,42,.25); transition:.2s ease; }
    .profit-fee-switch input:checked + .profit-fee-switch-slider { background:var(--dsh-accent); }
    .profit-fee-switch input:checked + .profit-fee-switch-slider::before { transform:translateX(17px); }
    .profit-fee-field { min-width:0; display:flex; flex-direction:column; }
    .profit-fee-display, .profit-fee-editor { width:max-content; max-width:100%; box-sizing:border-box; }
    .profit-fee-display { justify-content:space-between; min-height:38px; padding:.3rem .6rem; font-size:.9rem; }
    .profit-fee-editor input { width:72px; flex:0 0 72px; }
    @media (max-width:768px) {
        .profit-fee-form { grid-template-columns:repeat(2, minmax(0, 1fr)); gap:.55rem; }
        .profit-fee-form { max-width:none; width:100%; }
        .profit-fee-panel .ads-tab-panel-head { padding:.6rem .7rem; }
        .profit-fee-panel > .p-3 { padding:.65rem .7rem !important; }
        .profit-fee-mode-options { display:grid; grid-template-columns:1fr 1fr; gap:.35rem; }
        .profit-fee-mode-option { min-width:0; width:100%; flex:none; }
        .profit-fee-display { min-height:36px !important; font-size:.92rem !important; }
        .profit-fee-editor input { min-width:0; }
    }
    @media (max-width:360px) {
        .profit-fee-form { grid-template-columns:1fr; }
        .profit-fee-mode-options { grid-template-columns:1fr; }
    }
    .profit-recon-grid {
        display:grid;
        grid-template-columns:repeat(4, minmax(0, 1fr));
        gap:.55rem;
        margin-bottom:.75rem;
    }
    .profit-recon-card {
        min-width:0;
        padding:.6rem .75rem;
        border:1px solid var(--dsh-border);
        border-radius:10px;
        background:var(--card-bg, var(--card, #fff));
    }
    .ads-kpi-grid .ads-kpi-sub {
        display:block;
        width:auto;
        min-width:0;
        grid-column:1;
        grid-row:3;
        align-self:center;
        margin:0;
        padding-top:.72rem;
        color:var(--dsh-muted);
        font-size:.56rem;
        line-height:1.2;
        min-height:1rem;
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
        text-align:left;
    }
    .ads-kpi-grid .ads-kpi {
        display:grid;
        grid-template-columns:minmax(0,1fr) auto;
        align-content:start;
        row-gap:.12rem;
    }
    .ads-kpi-grid .ads-kpi-label,
    .ads-kpi-grid .ads-kpi-value {
        grid-column:1 / -1;
    }
    .profit-kpi-compare {
        display:flex;
        grid-column:2;
        grid-row:3;
        flex-direction:column;
        align-items:flex-end;
        justify-self:end;
        align-self:center;
        gap:.12rem;
        min-height:1.7rem;
        margin:.2rem 0 0 .3rem;
        font-size:.6rem;
        line-height:1.1;
        font-weight:800;
        white-space:nowrap;
        text-align:right;
        vertical-align:middle;
    }
    .profit-kpi-compare-change {
        display:flex;
        align-items:center;
        justify-content:flex-end;
        gap:.2rem;
    }
    .profit-kpi-compare-prev {
        color:var(--dsh-muted);
        font-size:.5rem;
        font-weight:650;
        line-height:1.15;
        white-space:nowrap;
        text-align:right;
    }
    .profit-kpi-compare.is-good { color:#15803d; }
    .profit-kpi-compare.is-bad { color:#b91c1c; }
    .profit-kpi-compare.is-neutral { color:var(--dsh-muted); font-weight:700; }
    .profit-kpi-compare-label { color:var(--dsh-muted); font-weight:600; }
    .profit-recon-label {
        color:var(--dsh-muted);
        font-size:.63rem;
        line-height:1.2;
    }
    .profit-recon-value {
        margin-top:.2rem;
        color:var(--text);
        font-size:.86rem;
        font-weight:800;
        font-variant-numeric:tabular-nums;
    }
    @media (max-width:768px) {
        .profit-recon-grid { grid-template-columns:repeat(2, minmax(0, 1fr)); gap:.45rem; }
    }
    @media (max-width:360px) {
        .profit-recon-grid { grid-template-columns:1fr; }
    }
    @media (max-width: 768px) {
        .profit-table-compact { font-size: .66rem; }
        .profit-table-compact,
        .profit-table-compact.dpanel-table { min-width: 100% !important; width: 100% !important; }
        .profit-table-compact th,
        .profit-table-compact td { padding: .38rem .24rem !important; }
        .profit-index-cell { width: 1.9rem !important; min-width: 1.9rem; }
        .profit-table-compact .btn { font-size: .58rem !important; padding: .14rem .3rem !important; }

        /* Mobile: tampilkan kolom inti saja agar tabel tetap mudah dibaca. */
        #profitViewProductUnmapped .product-unmapped-table th:nth-child(3),
        #profitViewProductUnmapped .product-unmapped-table td:nth-child(3),
        #profitViewProductUnmapped .product-unmapped-table th:nth-child(5),
        #profitViewProductUnmapped .product-unmapped-table td:nth-child(5),
        #profitViewCategory .profit-table-compact th:nth-child(3),
        #profitViewCategory .profit-table-compact td:nth-child(3),
        #profitViewCategory .profit-table-compact th:nth-child(5),
        #profitViewCategory .profit-table-compact td:nth-child(5),
        #profitViewCategory .profit-table-compact th:nth-child(6),
        #profitViewCategory .profit-table-compact td:nth-child(6),
        #profitViewCategory .profit-table-compact th:nth-child(8),
        #profitViewCategory .profit-table-compact td:nth-child(8),
        #profitViewCampaign .profit-table-compact th:nth-child(4),
        #profitViewCampaign .profit-table-compact td:nth-child(4),
        #profitViewCampaign .profit-table-compact th:nth-child(5),
        #profitViewCampaign .profit-table-compact td:nth-child(5),
        #profitViewCampaign .profit-table-compact th:nth-child(6),
        #profitViewCampaign .profit-table-compact td:nth-child(6),
        #profitViewCampaign .profit-table-compact th:nth-child(10),
        #profitViewCampaign .profit-table-compact td:nth-child(10),
        #profitViewGms .profit-table-compact th:nth-child(4),
        #profitViewGms .profit-table-compact td:nth-child(4),
        #profitViewGms .profit-table-compact th:nth-child(5),
        #profitViewGms .profit-table-compact td:nth-child(5),
        #profitViewGms .profit-table-compact th:nth-child(6),
        #profitViewGms .profit-table-compact td:nth-child(6),
        #profitViewGms .profit-table-compact th:nth-child(8),
        #profitViewGms .profit-table-compact td:nth-child(8),
        #profitViewProductUnmapped .product-unmapped-api-table th:nth-child(4),
        #profitViewProductUnmapped .product-unmapped-api-table td:nth-child(4) {
            display: none;
        }

        /* Mode ultra-compact: tepat tiga kolom utama di layar kecil. */
        #profitViewCategory .profit-table-compact th,
        #profitViewCategory .profit-table-compact td,
        #profitViewCampaign .profit-table-compact th,
        #profitViewCampaign .profit-table-compact td,
        #profitViewGms .profit-table-compact th,
        #profitViewGms .profit-table-compact td,
        #profitViewProductUnmapped .profit-table-compact th,
        #profitViewProductUnmapped .profit-table-compact td {
            display: none;
        }

        #profitViewCategory .profit-table-compact th:nth-child(2),
        #profitViewCategory .profit-table-compact td:nth-child(2),
        #profitViewCategory .profit-table-compact th:nth-child(7),
        #profitViewCategory .profit-table-compact td:nth-child(7),
        #profitViewCategory .profit-table-compact th:nth-child(10),
        #profitViewCategory .profit-table-compact td:nth-child(10),
        #profitViewCampaign .profit-table-compact th:nth-child(2),
        #profitViewCampaign .profit-table-compact td:nth-child(2),
        #profitViewCampaign .profit-table-compact th:nth-child(8),
        #profitViewCampaign .profit-table-compact td:nth-child(8),
        #profitViewCampaign .profit-table-compact th:nth-child(9),
        #profitViewCampaign .profit-table-compact td:nth-child(9),
        #profitViewGms .profit-table-compact th:nth-child(2),
        #profitViewGms .profit-table-compact td:nth-child(2),
        #profitViewGms .profit-table-compact th:nth-child(7),
        #profitViewGms .profit-table-compact td:nth-child(7),
        #profitViewGms .profit-table-compact th:nth-child(10),
        #profitViewGms .profit-table-compact td:nth-child(10),
        #profitViewProductUnmapped .product-unmapped-table th:nth-child(2),
        #profitViewProductUnmapped .product-unmapped-table td:nth-child(2),
        #profitViewProductUnmapped .product-unmapped-table th:nth-child(6),
        #profitViewProductUnmapped .product-unmapped-table td:nth-child(6),
        #profitViewProductUnmapped .product-unmapped-table th:nth-child(7),
        #profitViewProductUnmapped .product-unmapped-table td:nth-child(7),
        #profitViewProductUnmapped .product-unmapped-api-table th:nth-child(2),
        #profitViewProductUnmapped .product-unmapped-api-table td:nth-child(2),
        #profitViewProductUnmapped .product-unmapped-api-table th:nth-child(5),
        #profitViewProductUnmapped .product-unmapped-api-table td:nth-child(5),
        #profitViewProductUnmapped .product-unmapped-api-table th:nth-child(6),
        #profitViewProductUnmapped .product-unmapped-api-table td:nth-child(6) {
            display: table-cell;
        }
        #profitViewGms .gms-profit-compare-mobile {
            display:block;
            margin-top:.12rem;
        }
    }
</style>

<!-- ── Toggle tampilan: GMV Max ROAS / Per Kategori ── -->
<div style="display:flex; gap:.35rem; margin-bottom:.75rem;">
    <button type="button" id="btnViewCategory" class="btn fw-bold" onclick="__profitView('category')" style="border-radius:999px; font-size:.72rem; padding:.38rem .95rem; background:var(--dsh-accent); color:#fff; border:1px solid var(--dsh-accent);">Per Kategori</button>
    <button type="button" id="btnViewCampaign" class="btn fw-bold" onclick="__profitView('campaign')" style="border-radius:999px; font-size:.72rem; padding:.38rem .95rem; color:var(--dsh-muted); border:1px solid var(--dsh-border); background:transparent;">GMV Max ROAS</button>
    @if($hasGmsTab)
        <button type="button" id="btnViewGms" class="btn fw-bold" onclick="__profitView('gms')" style="border-radius:999px; font-size:.72rem; padding:.38rem .95rem; color:#0369a1; border:1px solid rgba(3,105,161,.35); background:rgba(3,105,161,.06);">GMV Max Auto</button>
    @endif
    @if($hasProductUnmappedTab)
        <button type="button" id="btnViewProductUnmapped" class="btn fw-bold" onclick="__profitView('product-unmapped')" style="border-radius:999px; font-size:.72rem; padding:.38rem .95rem; color:#b45309; border:1px solid rgba(180,83,9,.35); background:rgba(217,119,6,.06);">Produk Belum Mapping</button>
    @endif
</div>
@if($mappableProfitRows->isNotEmpty())
    <div class="mb-3" style="display:flex;align-items:center;gap:.55rem;flex-wrap:wrap;padding:.65rem .8rem;border:1px dashed rgba(180,83,9,.35);border-radius:12px;background:rgba(217,119,6,.05);font-size:.72rem;color:var(--dsh-muted);">
        <i class="bi bi-info-circle" style="color:#b45309;"></i>
        <span><b style="color:#b45309;">{{ $mappableProfitRows->count() }} campaign</b> belum punya HPP/mapping item.</span>
        <button type="button" class="btn btn-sm" style="font-size:.68rem;padding:.2rem .55rem;border-radius:999px;color:#92400e;border:1px solid rgba(180,83,9,.35);background:transparent;" onclick="__profitView('product-unmapped')">Tampilkan &amp; perbaiki</button>
    </div>
@endif

@if($hasProductUnmappedTab)
<div id="profitViewProductUnmapped" style="display:none;">
    <div class="ads-tab-panel mb-3">
        <div class="ads-tab-panel-head">
            <div>
                <div class="ads-tab-panel-title"><i class="bi bi-box-seam" style="color:#b45309;"></i> Produk Belum Mapping</div>
                <div class="ads-tab-panel-note">Produk tanpa HPP · pilih item untuk mapping.</div>
            </div>
            <span id="productUnmappedBadge" style="font-size:.7rem;font-weight:800;color:#92400e;background:rgba(217,119,6,.1);padding:.3rem .6rem;border-radius:999px;">Memuat…</span>
        </div>
        <div class="p-3">
            @if($productUnmappedRows->isNotEmpty())
                <div style="font-size:.75rem;font-weight:800;color:var(--text);margin-bottom:.45rem;">Produk iklan regular</div>
                <div class="table-responsive profit-table-wrap mb-3">
                    <table class="dpanel-table dpanel-table-sm table-hover w-100 profit-table-compact product-unmapped-table">
                        <thead>
                            <tr>
                                <th class="profit-index-cell">#</th>
                                <th>Produk / SKU</th>
                                <th>Kampanye</th>
                                <th class="text-end">Penjualan</th>
                                <th class="text-end">Omzet</th>
                                <th class="text-end">Biaya Iklan</th>
                                <th class="text-center">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($productUnmappedRows as $index => $r)
                            @php
                                $camp = $r->camp;
                                $itemName = $camp->marketplace_item_name ?: 'Produk belum ditemukan';
                                $itemSku = $camp->marketplace_item_sku ?: ('Item ID ' . $camp->channel_item_id);
                            @endphp
                            <tr style="border-bottom:1px solid var(--dsh-border);">
                                <td class="profit-index-cell">{{ $index + 1 }}</td>
                                <td style="padding:.65rem .5rem;max-width:280px;">
                                    <div style="font-weight:750;color:var(--text);overflow:hidden;text-overflow:ellipsis;" title="{{ $itemName }}">{{ $itemName }}</div>
                                    <div style="font-size:.64rem;color:var(--dsh-muted);">{{ $itemSku }}</div>
                                </td>
                                <td style="max-width:230px;overflow:hidden;text-overflow:ellipsis;" title="{{ $camp->campaign_name ?: 'Kampanye' }}">{{ $camp->campaign_name ?: 'Kampanye' }}</td>
                                <td class="text-end" style="font-variant-numeric:tabular-nums;">
                                    <b>{{ number_format($camp->orders, 0, ',', '.') }} order</b>
                                    @if($camp->items_sold > 0)<div style="font-size:.62rem;color:var(--dsh-muted);">{{ number_format($camp->items_sold, 0, ',', '.') }} pcs{{ ($camp->items_sold_source ?? 'api') === 'order_fallback' ? ' · estimasi' : '' }}</div>@endif
                                </td>
                                <td class="text-end" style="font-variant-numeric:tabular-nums;">{{ $fmt($camp->gmv) }}</td>
                                <td class="text-end" style="font-variant-numeric:tabular-nums;color:#dc2626;"><b>{{ $fmt($camp->spend * 1.11) }}</b><div style="font-size:.62rem;color:var(--dsh-muted);">sebelum PPN {{ $fmt($camp->spend) }}</div></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm" style="font-size:.64rem;padding:.2rem .55rem;border-radius:999px;color:#92400e;border:1px solid rgba(180,83,9,.35);background:rgba(217,119,6,.06);" data-profit-map-campaign="{{ $camp->id }}" data-profit-map-name="{{ e($itemName) }}" data-profit-map-item="{{ e((string) $camp->channel_item_id) }}" data-profit-map-store="{{ $camp->store_id }}" data-profit-map-channel-item="{{ e((string) ($camp->channel_item_id ?? '')) }}" onclick="openProfitCampaignMapping(this)">
                                        <i class="bi bi-link-45deg"></i> Pilih item HPP
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
            @if($hasGmsTab)
                <div style="font-size:.75rem;font-weight:800;color:var(--text);margin:.8rem 0 .45rem;">GMV Max Auto yang belum mapping</div>
                <div id="productUnmappedGmsBody" style="color:var(--dsh-muted);font-size:.75rem;">Buka tab ini untuk memuat GMV Max Auto…</div>
            @endif
        </div>
    </div>
</div>
@endif

<!-- ── Tabel per KATEGORI ── -->
<div id="profitViewCategory" style="display:block;">
    <div class="ads-tab-panel">
        <div class="table-responsive profit-table-wrap">
            <table class="dpanel-table dpanel-table-sm table-hover w-100 profit-table-compact">
            <thead>
                <tr>
                    <th class="profit-index-cell">#</th>
                    <th>Kategori</th>
                    <th class="text-end">Kampanye</th>
                    <th class="text-end">Pesanan</th>
                    <th class="text-end">Terjual</th>
                    <th class="text-end">AOV</th>
                    <th class="text-end">Iklan</th>
                    <th class="text-end">Dana Cair</th>
                    <th class="text-end">HPP</th>
                    <th class="text-end">Laba Bersih</th>
                </tr>
            </thead>
            <tbody>
                @forelse($byCategory as $index => $cat)
                    <tr style="border-bottom: 1px solid var(--dsh-border); {{ $cat->unknownCount > 0 && $cat->profit == 0 ? 'background: rgba(217, 119, 6, 0.045);' : ($cat->profit >= 0 ? '' : 'background: rgba(220, 38, 38, 0.045);') }}">
                        <td class="profit-index-cell">{{ $index + 1 }}</td>
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
                        <td class="text-end" style="font-variant-numeric:tabular-nums; color:var(--text); vertical-align:middle;">{{ number_format($cat->orders, 0, ',', '.') }}</td>
                        <td class="text-end" style="font-variant-numeric:tabular-nums; color:var(--text); vertical-align:middle;">{{ number_format($cat->items, 0, ',', '.') }} pcs</td>
                        <td class="text-end" style="font-variant-numeric:tabular-nums; font-weight:700; color:var(--text); vertical-align:middle;">{{ $cat->orders > 0 ? $fmt($cat->gmv / $cat->orders) : '—' }}</td>
                        <td class="text-end" style="font-variant-numeric:tabular-nums; font-weight:700; color:#dc2626; vertical-align:middle;">−{{ $fmt($cat->topup) }}<div style="font-size:.62rem;color:var(--dsh-muted);font-weight:500;">sebelum PPN {{ $fmt($cat->spend) }}</div></td>
                        <td class="text-end" style="font-variant-numeric:tabular-nums; font-weight:800; color:#0369a1; vertical-align:middle;">{{ $fmt($cat->netRevenue) }}</td>
                        <td class="text-end" style="font-variant-numeric:tabular-nums; font-weight:700; color:var(--text); vertical-align:middle;">&minus;{{ $fmt($cat->cogs) }}<div style="font-size:.62rem;color:var(--dsh-muted);font-weight:500;">{{ $cat->items > 0 && $cat->cogs > 0 ? $fmt($cat->cogs / $cat->items) . '/pcs' : 'HPP/pcs —' }}</div></td>
                        <td class="text-end" style="font-variant-numeric:tabular-nums; font-weight:800; font-size:.95rem; color:{{ $cat->unknownCount > 0 && $cat->profit == 0 ? '#b45309' : ($cat->profit >= 0 ? '#16a34a' : '#dc2626') }}; vertical-align:middle;">{{ $cat->unknownCount > 0 && $cat->profit == 0 ? 'N/A' : (($cat->profit < 0 ? '-' : '') . $fmt($cat->profit)) }}</td>
                    </tr>
            @empty
                <tr><td colspan="10" class="text-center py-4" style="color:var(--dsh-muted); font-size:.8rem;">Belum ada data.</td></tr>
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
    const productUnmapped = document.getElementById('profitViewProductUnmapped');
    const gms = document.getElementById('profitViewGms');
    const bCat = document.getElementById('btnViewCategory');
    const bCamp = document.getElementById('btnViewCampaign');
    const bProductUnmapped = document.getElementById('btnViewProductUnmapped');
    const bGms = document.getElementById('btnViewGms');
    const on  = 'border-radius:999px; font-size:.72rem; padding:.38rem .95rem; background:var(--dsh-accent); color:#fff; border:1px solid var(--dsh-accent);';
    const off = 'border-radius:999px; font-size:.72rem; padding:.38rem .95rem; color:var(--dsh-muted); border:1px solid var(--dsh-border); background:transparent;';
    const isCat = mode === 'category';
    const isProductUnmapped = mode === 'product-unmapped';
    const isGms = mode === 'gms';
    cat.style.display = isCat ? 'block' : 'none';
    camp.style.display = isCat || isProductUnmapped || isGms ? 'none' : 'block';
    if (productUnmapped) productUnmapped.style.display = isProductUnmapped ? 'block' : 'none';
    if (gms) gms.style.display = isGms ? 'block' : 'none';
    bCat.style.cssText = isCat ? on : off;
    bCamp.style.cssText = !isCat && !isProductUnmapped && !isGms ? on : off;
    if (bProductUnmapped) bProductUnmapped.style.cssText = isProductUnmapped ? on : off;
    if (bGms) bGms.style.cssText = isGms ? on : off;
    if (isGms && window.loadGmsItemsTab) window.loadGmsItemsTab();
    if (isProductUnmapped && window.loadProductUnmappedTab) window.loadProductUnmappedTab();
    try { localStorage.setItem('profitViewMode', mode); } catch (e) {}
};
(function () {
    try { 
        const savedMode = localStorage.getItem('profitViewMode');
        if (savedMode === 'campaign' || savedMode === 'product-unmapped' || savedMode === 'gms') {
            window.__profitView(savedMode);
        } else {
            window.__profitView('category'); 
        }
    } catch (e) {}
})();
</script>

@if($mappableProfitRows->isNotEmpty() || $hasGmsTab)
<div class="modal fade" id="profitCampaignMappingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:18px;border:1px solid var(--dsh-border);background:var(--card-bg,#fff);color:var(--text,#0f172a);">
            <div class="modal-header" style="border-bottom:1px solid var(--dsh-border);">
                <div>
                    <h5 class="modal-title" style="font-size:1rem;font-weight:800;">Pilih item utama untuk HPP</h5>
                    <div id="profitMapCampaignLabel" style="font-size:.7rem;color:var(--dsh-muted);margin-top:.2rem;"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div id="profitMapScopeNote" style="font-size:.72rem;color:var(--dsh-muted);margin-bottom:.65rem;">Pilih produk utama, bukan variant. HPP akan memakai rata-rata HPP variant yang terkait.</div>
                <input type="search" id="profitMapItemSearch" class="form-control" placeholder="Cari nama produk atau kode item…" autocomplete="off" style="font-size:.82rem;border-radius:10px;">
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
    let state = { mode: 'campaign', campaignId: null, storeId: null, gmsItemId: null, suggestStoreId: null, suggestChannelItemId: null, itemId: null, timer: null };

    const esc = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
    }[char]));

    async function searchItems(query) {
        resultsEl.innerHTML = '<div style="padding:.7rem;color:var(--dsh-muted);font-size:.75rem;">Mencari produk…</div>';
        try {
            const params = new URLSearchParams({ q: query, group_products: '1' });
            if (state.suggestStoreId && state.suggestChannelItemId) {
                params.set('store_id', state.suggestStoreId);
                params.set('channel_item_id', state.suggestChannelItemId);
            }
            const response = await fetch('/api/marketplace/items/search?' + params.toString(), { headers: { Accept: 'application/json' } });
            const items = await response.json();
            if (!response.ok) throw new Error(items.message || 'Gagal mencari item.');
            resultsEl.innerHTML = items.length
                ? items.map((item) => {
                    const hppNote = item.hpp_source === 'variant_average'
                        ? `HPP rata-rata ${Number(item.variant_count || 0).toLocaleString('id-ID')} variant`
                        : 'HPP item utama';
                    const suggestionNote = item.suggestion_source ? `<span style="display:inline-block;margin-left:.25rem;color:#0369a1;">· ${esc(item.suggestion_source)}</span>` : '';
                    return `<button type="button" class="profit-map-option" data-id="${item.id}" data-code="${esc(item.code || '')}" data-name="${esc(item.name || '')}" data-hpp="${Number(item.hpp || 0)}" style="display:block;width:100%;text-align:left;padding:.55rem .65rem;margin-bottom:.35rem;border:1px solid var(--dsh-border);border-radius:10px;background:transparent;color:var(--text);cursor:pointer;"><b style="font-size:.76rem;">${esc(item.name || 'Produk tanpa nama')}</b>${suggestionNote}<span style="display:block;font-size:.7rem;color:var(--dsh-muted);margin-top:.12rem;">Item utama: ${esc(item.code || 'Tanpa kode')}</span><span style="display:block;font-size:.66rem;color:var(--dsh-muted);margin-top:.15rem;">${hppNote} · ${window.__profitFmtRp ? window.__profitFmtRp(item.hpp) : ('Rp ' + Number(item.hpp || 0).toLocaleString('id-ID'))}</span></button>`;
                }).join('')
                : '<div style="padding:.7rem;color:var(--dsh-muted);font-size:.75rem;">Item utama tidak ditemukan. Pastikan produk sudah memiliki mapping variant ke item internal.</div>';
            resultsEl.querySelectorAll('.profit-map-option').forEach((button) => {
                button.addEventListener('click', () => {
                    state.itemId = Number(button.dataset.id);
                    selectedEl.style.display = 'block';
                    selectedEl.innerHTML = '✓ ' + button.dataset.name + '<div style="font-size:.66rem;color:var(--dsh-muted);margin-top:.15rem;">Item utama: ' + button.dataset.code + ' · HPP ' + (window.__profitFmtRp ? window.__profitFmtRp(button.dataset.hpp) : ('Rp ' + Number(button.dataset.hpp || 0).toLocaleString('id-ID'))) + '</div>';
                    saveEl.disabled = !state.itemId;
                    resultsEl.innerHTML = '';
                });
            });
        } catch (error) {
            resultsEl.innerHTML = '<div style="padding:.7rem;color:#b91c1c;font-size:.75rem;">' + esc(error.message) + '</div>';
        }
    }

    window.openProfitCampaignMapping = function (button) {
        state = { mode: 'campaign', campaignId: Number(button.dataset.profitMapCampaign), storeId: null, gmsItemId: null, suggestStoreId: Number(button.dataset.profitMapStore || 0) || null, suggestChannelItemId: button.dataset.profitMapChannelItem || button.dataset.profitMapItem || null, itemId: null, timer: null };
        const isGms = button.dataset.profitMapItem.indexOf('Semua Produk') !== -1;
        labelEl.textContent = button.dataset.profitMapName + (isGms ? ' · HPP acuan agregat GMV Max' : ' · Shopee item ' + button.dataset.profitMapItem);
        document.getElementById('profitMapScopeNote').textContent = isGms
            ? 'Pilih produk utama. Jika produk memiliki beberapa variant, HPP yang dipakai adalah rata-rata HPP variant tersebut.'
            : 'Pilih produk utama, bukan variant. Jika tersedia, HPP dihitung sebagai rata-rata HPP seluruh variant produk.';
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
        state = { mode: 'gms-item', campaignId: null, storeId: Number(button.dataset.gmsMapStore), gmsItemId: String(button.dataset.gmsMapItem), suggestStoreId: Number(button.dataset.gmsMapStore), suggestChannelItemId: String(button.dataset.gmsMapItem), itemId: null, timer: null };
        labelEl.textContent = (button.dataset.gmsMapName || 'GMV Max Auto') + ' · item GMV Max Auto ' + state.gmsItemId;
        document.getElementById('profitMapScopeNote').textContent = 'Pilih produk utama, bukan variant. HPP per pcs GMV Max dihitung dari rata-rata HPP variant produk tersebut.';
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
    <div class="ads-tab-panel-head">
        <div>
            <div class="ads-tab-panel-title"><i class="bi bi-stars" style="color:#0369a1;"></i> GMV Max ROAS</div>
            <div class="ads-tab-panel-note">Omzet · HPP · iklan · laba · vs {{ $comparisonLabel }}</div>
        </div>
    </div>
    <div class="p-3 pb-2">
        <div class="ads-kpi-grid mb-3">
            <div class="dpanel ads-kpi kpi-revenue">
                <div class="ads-kpi-label" title="Total omzet dari kampanye GMV Max ROAS"><i class="bi bi-graph-up-arrow"></i> Omzet GMV</div>
                <div class="ads-kpi-value" style="font-variant-numeric:tabular-nums;">{{ $fmt($roasTotalGmv) }}</div>
                <div class="ads-kpi-sub">{{ number_format($roasTotalOrders, 0, ',', '.') }} order</div>
                @include('marketplace.partials._profit_kpi_compare', ['cmp' => $profitCompare((float) $roasTotalGmv, $roasPreviousGmv, false, 'currency'), 'label' => $comparisonLabel])
            </div>
            <div class="dpanel ads-kpi kpi-spend">
                <div class="ads-kpi-label" title="Belanja iklan termasuk PPN"><i class="bi bi-wallet2"></i> Belanja Iklan</div>
                <div class="ads-kpi-value" style="font-variant-numeric:tabular-nums;">−{{ $fmt($roasTotalSpend * 1.11) }}</div>
                <div class="ads-kpi-sub">Net {{ $fmt($roasTotalSpend) }} · +PPN</div>
                @include('marketplace.partials._profit_kpi_compare', ['cmp' => $profitCompare((float) ($roasTotalSpend * 1.11), (float) ($roasPreviousSpend * 1.11), true, 'currency'), 'label' => $comparisonLabel])
            </div>
            <div class="dpanel ads-kpi kpi-cogs">
                <div class="ads-kpi-label"><i class="bi bi-box-seam"></i> HPP Produk</div>
                <div class="ads-kpi-value" style="font-variant-numeric:tabular-nums;">{{ $fmt($roasTotalCogs) }}</div>
                <div class="ads-kpi-sub">{{ $roasKnownRows->count() }}/{{ $displayRows->count() }} dihitung</div>
                @include('marketplace.partials._profit_kpi_compare', ['cmp' => $profitCompare((float) $roasTotalCogs, (float) $roasPreviousCogs, true, 'currency'), 'label' => $comparisonLabel])
            </div>
            <div class="dpanel ads-kpi kpi-profit" style="border-left-color:{{ $roasTotalProfit >= 0 ? '#16a34a' : '#dc2626' }};">
                <div class="ads-kpi-label" title="Laba setelah HPP dan biaya iklan"><i class="bi bi-cash-stack"></i> Laba Bersih</div>
                <div class="ads-kpi-value" style="font-variant-numeric:tabular-nums;color:{{ $roasTotalProfit >= 0 ? '#16a34a' : '#dc2626' }};">{{ $roasTotalProfit < 0 ? '−' : '' }}{{ $fmt($roasTotalProfit) }}</div>
                <div class="ads-kpi-sub">HPP tersedia · Margin <b>{{ number_format($roasMarginPct, 1, ',', '.') }}%</b></div>
                @include('marketplace.partials._profit_kpi_compare', ['cmp' => $profitCompare((float) $roasTotalProfit, (float) $roasPreviousProfit, false, 'currency'), 'label' => $comparisonLabel])
            </div>
            <div class="dpanel ads-kpi">
                <div class="ads-kpi-label" title="Omzet dibanding biaya iklan"><i class="bi bi-speedometer2"></i> ROAS</div>
                <div class="ads-kpi-value" style="font-variant-numeric:tabular-nums;">{{ number_format($roasValue, 2, ',', '.') }}x</div>
                <div class="ads-kpi-sub">{{ $roasUnknownCount > 0 ? $roasUnknownCount . ' belum dihitung' : 'HPP lengkap' }}</div>
                @include('marketplace.partials._profit_kpi_compare', ['cmp' => $profitCompare((float) $roasValue, (float) $roasPreviousValue, false, 'multiple'), 'label' => $comparisonLabel])
            </div>
        </div>
    </div>
    <div class="p-3 pt-0">
    <div class="table-responsive profit-table-wrap">
        <table class="dpanel-table dpanel-table-sm table-hover w-100 profit-table-compact profit-roas-table">
        <thead>
            <tr>
                <th class="profit-index-cell">#</th>
                <th onclick="sortProfitTable('campaign')" style="cursor:pointer">Kampanye / Produk <i class="bi bi-arrow-down-up" style="font-size: 0.6rem; opacity: 0.5;"></i></th>
                <th class="text-end" onclick="sortProfitTable('orders')" style="cursor:pointer">Pesanan <i class="bi bi-arrow-down-up" style="font-size: 0.6rem; opacity: 0.5;"></i></th>
                <th class="text-end">Terjual</th>
                <th class="text-end">AOV</th>
                <th class="text-end" onclick="sortProfitTable('net_revenue')" style="cursor:pointer">Dana Cair <i class="bi bi-arrow-down-up" style="font-size: 0.6rem; opacity: 0.5;"></i></th>
                <th class="text-end" onclick="sortProfitTable('hpp')" style="cursor:pointer"><span style="color:var(--dsh-muted); font-weight:600;">&minus;</span> HPP <i class="bi bi-arrow-down-up" style="font-size: 0.6rem; opacity: 0.5;"></i></th>
                <th class="text-end" onclick="sortProfitTable('ad_spend')" style="cursor:pointer"><span style="color:var(--dsh-muted); font-weight:600;">&minus;</span> Iklan <i class="bi bi-arrow-down-up" style="font-size: 0.6rem; opacity: 0.5;"></i></th>
                <th class="text-end" onclick="sortProfitTable('net_profit')" style="cursor:pointer; border-left: 2px solid var(--dsh-border);"><span style="color:var(--dsh-muted); font-weight:600;">=</span> Net Profit <i class="bi bi-arrow-down-up" style="font-size: 0.6rem; opacity: 0.5;"></i></th>
                <th class="text-end">Vs {{ $comparisonLabel }}</th>
                <th class="text-end" onclick="sortProfitTable('acos')" style="cursor:pointer">ACOS <i class="bi bi-arrow-down-up" style="font-size: 0.6rem; opacity: 0.5;"></i></th>
                <th class="text-center">Rekomendasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($displayRows as $index => $r)
                @php
                    $camp = $r->camp;
                    $reco = $camp->reco ?? ['label' => 'Optimize', 'color' => '#ca8a04', 'icon' => '⚡'];
                    $profitKnown = $r->profit !== null;
                    $previousProfitRow = $camp->prev_profit_after_ads;
                    $profitChangeRow = $profitKnown && $previousProfitRow !== null && (float) $previousProfitRow != 0
                        ? (($r->profit - (float) $previousProfitRow) / abs((float) $previousProfitRow)) * 100
                        : null;
                    $profitChangeGood = $profitChangeRow === null || $profitChangeRow >= 0;
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
                <tr class="profit-drilldown-trigger"
                    data-drilldown-kind="campaign"
                    data-drilldown-store="{{ $camp->store_id }}"
                    data-drilldown-id="{{ $camp->channel_campaign_id }}"
                    data-drilldown-label="{{ e($camp->marketplace_item_name ?: ($camp->campaign_name ?: 'Kampanye GMV Max ROAS')) }}"
                    data-campaign="{{ strtolower($camp->campaign_name ?? '') }}"
                    data-orders="{{ $camp->orders }}"
                    data-net_revenue="{{ $r->netRevenue }}"
                    data-hpp="{{ $r->totalCogs ?? '' }}"
                    data-ad_spend="{{ $r->spendAfterTax }}"
                    data-net_profit="{{ $r->profit ?? '' }}"
                    data-acos="{{ $r->acos }}"
                    style="border-bottom: 1px solid var(--dsh-border); {{ !$profitKnown ? 'background: rgba(217, 119, 6, 0.045);' : ($r->isProfitable ? '' : 'background: rgba(220, 38, 38, 0.045);') }}">
                    <td class="profit-index-cell">{{ $index + 1 }}</td>
                    <td class="profit-campaign-cell" style="padding: 0.75rem 0.5rem; max-width: 250px;">
                        <span class="profit-campaign-product" style="margin-top:0; font-weight:700; color:var(--text); font-size:.74rem;" title="{{ $camp->marketplace_item_name ?: ($camp->campaign_name ?: 'Produk marketplace') }}">
                            {{ $camp->marketplace_item_name ?: ($camp->campaign_name ?: 'Produk marketplace') }}
                        </span>
                        <div class="profit-campaign-meta" title="{{ $camp->channel_item_id ? 'SKU ' . ($camp->marketplace_item_sku ?: '-') : 'GMV Max Auto · semua item' }}">
                            {{ $camp->channel_item_id ? 'SKU ' . ($camp->marketplace_item_sku ?: '-') : 'GMV Max Auto · semua item' }}
                        </div>
                        <span class="profit-drilldown-hint" title="Detail harian"><i class="bi bi-graph-up"></i></span>
                    </td>

                    <td class="text-end" style="vertical-align: middle;">
                        <div style="font-weight: 700; color: var(--text); font-variant-numeric: tabular-nums;">{{ number_format($camp->orders, 0, ',', '.') }}</div>
                    </td>
                    <td class="text-end" style="vertical-align: middle; font-weight:700; color:var(--text); font-variant-numeric:tabular-nums;">{{ number_format($camp->items_sold, 0, ',', '.') }} pcs</td>
                    <td class="text-end" style="vertical-align: middle; font-weight:700; color:var(--text); font-variant-numeric:tabular-nums;">{{ $r->aov > 0 ? $fmt($r->aov) : '—' }}</td>

                    <td class="text-end" style="vertical-align: middle;">
                        <div style="font-weight: 800; color: #0369a1; font-variant-numeric: tabular-nums;" @if($r->feeIsEstimate) title="Estimasi dana cair" @endif>{{ $fmt($r->netRevenue) }}</div>
                    </td>

                        <td class="text-end" style="vertical-align: middle;">
                        <div style="font-weight: 700; color: var(--text); font-variant-numeric: tabular-nums;">{!! $r->totalCogs > 0 ? '&minus;' . $fmt($r->totalCogs) : '<span style="color:#b45309;">&mdash;</span>' !!}</div>
                        <div style="font-size: .65rem; color: {{ $r->totalCogs !== null ? 'var(--dsh-muted)' : '#b45309' }}; margin-top: 2px;" title="{{ $r->totalCogs === null ? 'HPP belum tersedia, profit tidak dihitung' : ($r->cogsExact ? 'Eksak: HPP × pcs terjual' : 'Estimasi dari rasio harga (pcs belum tercatat)') }}">{{ $r->totalCogs === null ? 'belum diisi' : ($r->cogsExact ? $fmt($r->unitCogs) . '/pcs × ' . $r->itemsSold : $fmt($r->unitCogs) . '/pcs · estimasi') }}</div>
                        @if($r->totalCogs === null && ($camp->channel_item_id || str_starts_with((string) $camp->channel_campaign_id, 'GMS-')))
                            <button type="button" class="btn btn-sm mt-1" style="font-size:.62rem; padding:.16rem .45rem; border-radius:999px; color:#b45309; border:1px solid rgba(180,83,9,.35); background:rgba(217,119,6,.06);" data-profit-map-campaign="{{ $camp->id }}" data-profit-map-name="{{ e($camp->marketplace_item_name ?: ($camp->campaign_name ?: 'Kampanye')) }}" data-profit-map-item="{{ e($camp->channel_item_id ? (string) $camp->channel_item_id : 'Semua Produk (GMV Max)') }}" data-profit-map-store="{{ $camp->store_id }}" data-profit-map-channel-item="{{ e((string) ($camp->channel_item_id ?? '')) }}" onclick="openProfitCampaignMapping(this)">
                                <i class="bi bi-link-45deg"></i> {{ str_starts_with((string) $camp->channel_campaign_id, 'GMS-') ? 'Atur HPP acuan' : 'Pilih item HPP' }}
                            </button>
                        @endif
                    </td>

                    <td class="text-end" style="vertical-align: middle;">
                        <div style="font-weight: 700; color: #dc2626; font-variant-numeric: tabular-nums;">&minus;{{ $fmt($r->spendAfterTax) }}</div>
                        <div style="font-size:.65rem;color:var(--dsh-muted);margin-top:2px;">sebelum PPN {{ $fmt($camp->spend) }}</div>
                    </td>

                    <td class="text-end" style="vertical-align: middle; border-left: 2px solid var(--dsh-border); background: rgba({{ !$profitKnown ? '217, 119, 6' : ($r->isProfitable ? '22, 163, 74' : '220, 38, 38') }}, 0.05);">
                        <div style="font-weight: 800; font-size: .95rem; color: {{ $profitColor }}; font-variant-numeric: tabular-nums;" @if(!$profitKnown || $r->feeIsEstimate) title="{{ !$profitKnown ? 'Tidak dihitung — HPP belum tersedia' : 'Estimasi — belum ada data pencairan' }}" @endif>{{ !$profitKnown ? 'N/A' : (($r->feeIsEstimate ? '±' : '') . ($r->profit < 0 ? '−' : '') . $fmt($r->profit)) }}</div>
                    </td>

                    <td class="text-end" style="vertical-align: middle;">
                        @if($profitChangeRow !== null)
                            <span style="font-size:.68rem;font-weight:800;color:{{ $profitChangeGood ? '#15803d' : '#b91c1c' }};white-space:nowrap;">
                                <i class="bi bi-arrow-{{ $profitChangeRow >= 0 ? 'up-right' : 'down-right' }}"></i> {{ number_format(abs($profitChangeRow), 1, ',', '.') }}%
                            </span>
                        @else
                            <span style="color:var(--dsh-muted);font-size:.68rem;">—</span>
                        @endif
                    </td>

                    <td class="text-end" style="vertical-align: middle;">
                        <div style="font-weight: 800; font-size: .95rem; color: {{ $r->acosOk === null ? 'var(--text)' : ($r->acosOk ? '#16a34a' : '#dc2626') }}; font-variant-numeric: tabular-nums;" title="Batas aman {{ $r->beAcos !== null ? number_format($r->beAcos, 1, ',', '.') . '%' : '-' }}">{{ $r->acos > 0 ? number_format($r->acos, 1, ',', '.') . '%' : '-' }}</div>
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
                        <td colspan="12" class="text-center py-4" style="color: var(--dsh-muted); font-size: .8rem;">
                        Belum ada data kampanye yang memiliki pengeluaran atau pendapatan.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if($displayRows->isNotEmpty())
        <tfoot>
            <tr style="border-top: 2px solid var(--dsh-border);">
                <td class="profit-index-cell"></td>
                <td style="padding: .7rem .5rem; font-weight: 800; font-size: .75rem; color: var(--text); text-transform: uppercase;">Total</td>
                <td class="text-end" style="font-weight: 700; color: var(--text); font-variant-numeric: tabular-nums;">{{ number_format($displayRows->sum(fn ($r) => $r->camp->orders), 0, ',', '.') }} order</td>
                <td class="text-end" style="font-weight: 700; color: var(--text); font-variant-numeric: tabular-nums;">{{ number_format($displayRows->sum(fn ($r) => $r->camp->items_sold), 0, ',', '.') }} pcs</td>
                <td class="text-end" style="font-weight:700; color:var(--text); font-variant-numeric:tabular-nums;">{{ $displayRows->sum(fn ($r) => $r->camp->orders) > 0 ? $fmt($displayRows->sum(fn ($r) => $r->camp->gmv) / $displayRows->sum(fn ($r) => $r->camp->orders)) : '—' }}</td>
                <td class="text-end" style="font-weight: 800; color: #0369a1; font-variant-numeric: tabular-nums;">{{ $fmt($roasKnownRows->sum('netRevenue')) }}<div style="font-size:.62rem; color:var(--dsh-muted); font-weight:500;">Omzet {{ $fmt($roasTotalGmv) }}</div></td>
                <td class="text-end" style="font-weight: 700; color: var(--text); font-variant-numeric: tabular-nums;">&minus;{{ $fmt($roasTotalCogs) }}</td>
                <td class="text-end" style="font-weight: 700; color: #dc2626; font-variant-numeric: tabular-nums;">&minus;{{ $fmt($roasTotalSpend * 1.11) }}<div style="font-size:.62rem; color:var(--dsh-muted); font-weight:500;">sebelum PPN {{ $fmt($roasTotalSpend) }}</div></td>
                <td class="text-end" style="font-weight: 800; font-size: .95rem; color: {{ $roasTotalProfit >= 0 ? '#16a34a' : '#dc2626' }}; font-variant-numeric: tabular-nums; border-left: 2px solid var(--dsh-border); background: rgba({{ $roasTotalProfit >= 0 ? '22, 163, 74' : '220, 38, 38' }}, 0.05);">{{ $roasTotalProfit < 0 ? '−' : '' }}{{ $fmt($roasTotalProfit) }}<div style="font-size:.62rem; color:var(--dsh-muted); font-weight:500;">{{ $roasUnknownCount > 0 ? $roasUnknownCount . ' campaign belum dihitung' : 'HPP tersedia' }}</div></td>
                <td class="text-end" style="font-weight:700;color:var(--dsh-muted);">—</td>
                <td class="text-end" style="font-weight: 700; color: var(--text); font-variant-numeric: tabular-nums;">{{ $roasTotalGmv > 0 ? number_format(($roasTotalSpend / $roasTotalGmv) * 100, 1, ',', '.') : '0,0' }}%</td>
                <td></td>
            </tr>
        </tfoot>
        @endif
        </table>
    </div>
    </div>
</div>
</div>

@if($hasGmsTab)
<div id="profitViewGms" style="display:none;">
    <div class="ads-tab-panel">
        <div class="ads-tab-panel-head">
            <div>
                <div class="ads-tab-panel-title"><i class="bi bi-stars" style="color:#0369a1;"></i> GMV Max Auto</div>
                <div class="ads-tab-panel-note">Omzet · HPP · iklan · laba · vs {{ $comparisonLabel }}</div>
                <div id="gmsItemsPeriod" style="font-size:.68rem;color:var(--dsh-muted);margin-top:.2rem;"></div>
            </div>
        </div>
        <div class="p-3">
            <div class="ads-kpi-grid mb-3">
                <div class="dpanel ads-kpi kpi-revenue">
                    <div class="ads-kpi-label" title="Total omzet dari GMV Max Auto"><i class="bi bi-graph-up-arrow"></i> Omzet GMV Auto</div>
                    <div class="ads-kpi-value">{{ $fmt($gmsTotalGmv) }}</div>
                    <div class="ads-kpi-sub">{{ number_format($gmsTotalOrders, 0, ',', '.') }} order</div>
                    @include('marketplace.partials._profit_kpi_compare', ['cmp' => $profitCompare((float) $gmsTotalGmv, (float) $gmsPreviousGmv, false, 'currency'), 'label' => $comparisonLabel])
                </div>
                <div class="dpanel ads-kpi kpi-spend">
                    <div class="ads-kpi-label" title="Belanja iklan termasuk PPN"><i class="bi bi-wallet2"></i> Belanja Iklan</div>
                    <div class="ads-kpi-value">−{{ $fmt($gmsTotalSpend * 1.11) }}</div>
                    <div class="ads-kpi-sub">Net {{ $fmt($gmsTotalSpend) }} · +PPN</div>
                    @include('marketplace.partials._profit_kpi_compare', ['cmp' => $profitCompare((float) ($gmsTotalSpend * 1.11), (float) ($gmsPreviousSpend * 1.11), true, 'currency'), 'label' => $comparisonLabel])
                </div>
                <div class="dpanel ads-kpi kpi-cogs">
                    <div class="ads-kpi-label"><i class="bi bi-box-seam"></i> HPP Produk</div>
                    <div class="ads-kpi-value">{{ $gmsUnknownCount > 0 ? 'N/A' : $fmt($gmsKnownRows->sum('totalCogs')) }}</div>
                    <div class="ads-kpi-sub">{{ $gmsKnownRows->count() }}/{{ $gmsProfitRows->count() }} dihitung</div>
                    @include('marketplace.partials._profit_kpi_compare', ['cmp' => $profitCompare((float) $gmsKnownRows->sum('totalCogs'), (float) $gmsPreviousCogs, true, 'currency'), 'label' => $comparisonLabel])
                </div>
                <div class="dpanel ads-kpi kpi-profit">
                    <div class="ads-kpi-label" title="Laba setelah HPP dan biaya iklan"><i class="bi bi-cash-stack"></i> Laba Bersih</div>
                    <div class="ads-kpi-value" style="color:{{ $gmsTotalProfit >= 0 ? '#16a34a' : '#dc2626' }};">{{ $gmsUnknownCount > 0 ? 'N/A' : (($gmsTotalProfit < 0 ? '−' : '') . $fmt($gmsTotalProfit)) }}</div>
                    <div class="ads-kpi-sub">Margin <b>{{ number_format($gmsMarginPct, 1, ',', '.') }}%</b></div>
                    @include('marketplace.partials._profit_kpi_compare', ['cmp' => $profitCompare((float) $gmsTotalProfit, (float) $gmsPreviousProfit, false, 'currency'), 'label' => $comparisonLabel])
                </div>
                <div class="dpanel ads-kpi">
                    <div class="ads-kpi-label" title="Omzet dibanding biaya iklan"><i class="bi bi-speedometer2"></i> ROAS</div>
                    <div class="ads-kpi-value">{{ number_format($gmsRoas, 2, ',', '.') }}x</div>
                    <div class="ads-kpi-sub">{{ $gmsUnknownCount > 0 ? $gmsUnknownCount . ' belum dihitung' : 'HPP lengkap' }}</div>
                    @include('marketplace.partials._profit_kpi_compare', ['cmp' => $profitCompare((float) $gmsRoas, (float) $gmsPreviousValue, false, 'multiple'), 'label' => $comparisonLabel])
                </div>
            </div>
            <div id="gmsItemsSummary" style="display:flex;flex-wrap:wrap;gap:.55rem;margin-bottom:.8rem;"></div>
            <div id="gmsItemsBody">
                <div style="padding:1.2rem;text-align:center;color:var(--dsh-muted);font-size:.75rem;">Pilih tab GMV Max Auto untuk memuat data…</div>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    const bodyEl = document.getElementById('gmsItemsBody');
    const summaryEl = document.getElementById('gmsItemsSummary');
    const periodEl = document.getElementById('gmsItemsPeriod');
    const endpointBase = @json(url('/marketplace/ads-dashboard/gms-items'));
    const fromDate = @json($dateFrom ?? now()->subDays(6)->toDateString());
    const toDate = @json($dateTo ?? now()->toDateString());
    const compareMode = @json($compareMode ?? 'prev_period');
    const comparisonLabel = @json($comparisonLabel);
    const storeIds = @json($gmsStoreIds);
    const storeNames = @json($gmsProfitRows->mapWithKeys(fn ($r) => [$r->camp->store_id => $r->camp->store?->name ?: ('Toko #' . $r->camp->store_id)])->all());
    const esc = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));
    const fmtRp = (value) => 'Rp ' + Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });

    const summaryCard = (label, value, color, note) => '<div style="flex:1 1 150px;min-width:145px;padding:.65rem .75rem;border:1px solid var(--dsh-border);border-radius:12px;background:var(--card-bg,#fff);"><div style="font-size:.65rem;color:var(--dsh-muted);">' + label + '</div><div style="font-size:1rem;font-weight:800;color:' + color + ';margin-top:.15rem;">' + value + '</div><div style="font-size:.62rem;color:var(--dsh-muted);margin-top:.1rem;">' + note + '</div></div>';

    function renderStore(storeId, payload) {
        if (!payload.data || !payload.data.length) return '<div style="padding:1rem;color:var(--dsh-muted);font-size:.75rem;">Belum ada data produk untuk ' + esc(storeNames[storeId] || ('Toko #' + storeId)) + '.</div>';
        const rows = payload.data.map(function (item, index) {
            const mapped = item.mapped;
            const profit = item.profit_after_ads;
            const profitText = profit === null ? 'N/A' : (profit < 0 ? '−' : '') + fmtRp(profit);
            const previousProfit = item.previous_profit_after_ads;
            const profitChange = profit !== null && previousProfit !== null && previousProfit !== undefined && Number(previousProfit) !== 0
                ? ((Number(profit) - Number(previousProfit)) / Math.abs(Number(previousProfit))) * 100
                : null;
            const comparisonText = profitChange === null
                ? '<span style="color:var(--dsh-muted);font-size:.68rem;">—</span>'
                : '<span style="font-size:.68rem;font-weight:800;color:' + (profitChange >= 0 ? '#15803d' : '#b91c1c') + ';white-space:nowrap;"><i class="bi bi-arrow-' + (profitChange >= 0 ? 'up-right' : 'down-right') + '"></i> ' + Math.abs(profitChange).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + '%</span>';
            const itemName = item.item_name || 'Produk tanpa nama';
            const mappingStatus = mapped
                ? '<span style="color:#15803d;font-weight:700;"><i class="bi bi-check-circle"></i> HPP tersedia</span>'
                : '<button type="button" class="btn btn-sm" style="font-size:.62rem;padding:.16rem .48rem;border-radius:999px;color:#b45309;border:1px solid rgba(180,83,9,.35);background:rgba(217,119,6,.06);" data-gms-map-store="' + esc(storeId) + '" data-gms-map-item="' + esc(item.channel_item_id) + '" data-gms-map-name="' + esc(itemName) + '" onclick="openGmsItemMapping(this)"><i class="bi bi-link-45deg"></i> Mapping HPP</button>';
            return '<tr class="profit-drilldown-trigger" data-drilldown-kind="gms_item" data-drilldown-store="' + esc(storeId) + '" data-drilldown-id="' + esc(item.channel_item_id) + '" data-drilldown-label="' + esc(itemName) + '">' +
                '<td class="profit-index-cell">' + (index + 1) + '</td>' +
                '<td><div class="profit-table-item-name" style="font-weight:700;" title="' + esc(itemName) + '">' + esc(itemName) + '</div><div style="font-size:.64rem;color:var(--dsh-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">SKU ' + esc(item.item_sku || '-') + ' · ID ' + esc(item.channel_item_id) + '</div><span class="profit-drilldown-hint" title="Detail harian"><i class="bi bi-graph-up"></i></span></td>' +
                '<td class="text-end"><div style="font-weight:700;">' + Number(item.orders || 0).toLocaleString('id-ID') + '</div></td>' +
                '<td class="text-end"><div style="font-weight:700;">' + Number(item.pcs || 0).toLocaleString('id-ID') + ' pcs</div></td>' +
                '<td class="text-end"><div style="font-weight:700;">' + (Number(item.orders || 0) > 0 ? fmtRp(Number(item.gmv || 0) / Number(item.orders || 0)) : '—') + '</div></td>' +
                '<td class="text-end"><div style="font-weight:700;">' + fmtRp(item.gmv) + '</div></td>' +
                '<td class="text-end" style="color:#dc2626;"><div style="font-weight:700;">−' + fmtRp(Number(item.spend || 0) * 1.11) + '</div><div style="font-size:.62rem;color:var(--dsh-muted);">sebelum PPN ' + fmtRp(Number(item.spend || 0)) + '</div></td>' +
                '<td class="text-end">' + (mapped ? fmtRp(item.unit_cogs) : '<span style="color:#b45309;">—</span>') + '</td>' +
                '<td class="text-end">' + (item.hpp_total !== null ? '−' + fmtRp(item.hpp_total) : '<span style="color:#b45309;">—</span>') + '</td>' +
                '<td class="text-end" style="font-weight:800;color:' + (profit === null ? '#b45309' : (profit >= 0 ? '#15803d' : '#b91c1c')) + ';"><div>' + profitText + '</div><div class="gms-profit-compare-mobile">' + comparisonText + '</div></td>' +
                '<td class="text-end">' + comparisonText + '</td>' +
                '<td>' + mappingStatus + '</td>' +
                '</tr>';
        }).join('');
        return '<div style="font-size:.75rem;font-weight:800;color:var(--text);margin:1rem 0 .45rem;">' + esc(storeNames[storeId] || ('Toko #' + storeId)) + '</div><div class="table-responsive profit-table-wrap"><table class="dpanel-table dpanel-table-sm w-100 profit-table-compact"><thead><tr><th class="profit-index-cell">#</th><th>Item</th><th class="text-end">Pesanan</th><th class="text-end">Terjual</th><th class="text-end">AOV</th><th class="text-end">GMV</th><th class="text-end">Iklan</th><th class="text-end">HPP/pcs</th><th class="text-end">Total HPP</th><th class="text-end">Profit</th><th class="text-end">Vs ' + esc(comparisonLabel) + '</th><th>Status HPP</th></tr></thead><tbody>' + rows + '</tbody></table></div>';
    }

    window.loadGmsItemsTab = async function () {
        periodEl.textContent = 'Periode ' + fromDate + ' s/d ' + toDate;
        bodyEl.innerHTML = '<div style="padding:1.2rem;text-align:center;color:var(--dsh-muted);font-size:.75rem;">Memuat GMV Max Auto…</div>';
        summaryEl.textContent = '';
        try {
            const payloads = await Promise.all(storeIds.map(async function (storeId) {
                const response = await fetch(endpointBase + '/' + storeId + '?date_from=' + encodeURIComponent(fromDate) + '&date_to=' + encodeURIComponent(toDate) + '&compare_mode=' + encodeURIComponent(compareMode), { headers: { Accept: 'application/json' } });
                const payload = await response.json();
                if (!response.ok) throw new Error(payload.message || 'Gagal memuat item GMV Max.');
                return { storeId: storeId, payload: payload };
            }));
            const totals = payloads.reduce((sum, entry) => ({
                items: sum.items + Number(entry.payload.total_items || 0),
                mapped: sum.mapped + Number(entry.payload.mapped_items || 0),
                unmapped: sum.unmapped + Number(entry.payload.unmapped_items || 0),
            }), { items: 0, mapped: 0, unmapped: 0 });
            summaryEl.innerHTML = summaryCard('Item GMV Max Auto', totals.items.toLocaleString('id-ID'), 'var(--text)', 'terjual pada periode ini')
                + summaryCard('HPP sudah tersedia', totals.mapped.toLocaleString('id-ID'), '#15803d', 'profit dapat dihitung')
                + summaryCard('Perlu mapping', totals.unmapped.toLocaleString('id-ID'), totals.unmapped > 0 ? '#b45309' : '#15803d', totals.unmapped > 0 ? 'pilih item internal' : 'semua sudah siap');
            bodyEl.innerHTML = payloads.map((entry) => renderStore(entry.storeId, entry.payload)).join('');
        } catch (error) {
            bodyEl.innerHTML = '<div style="padding:1rem;color:#b91c1c;font-size:.75rem;">' + esc(error.message) + '</div>';
        }
    };
    try { if (localStorage.getItem('profitViewMode') === 'gms') window.loadGmsItemsTab(); } catch (e) {}
})();
</script>
@endif

@if($hasProductUnmappedTab)
<script>
(function () {
    const bodyEl = document.getElementById('productUnmappedGmsBody');
    const badgeEl = document.getElementById('productUnmappedBadge');
    const endpointBase = @json(url('/marketplace/ads-dashboard/gms-items'));
    const fromDate = @json($dateFrom ?? now()->subDays(6)->toDateString());
    const toDate = @json($dateTo ?? now()->toDateString());
    const storeIds = @json($gmsStoreIds);
    const storeNames = @json($gmsProfitRows->mapWithKeys(fn ($r) => [$r->camp->store_id => $r->camp->store?->name ?: ('Toko #' . $r->camp->store_id)])->all());
    const regularCount = @json($productUnmappedRows->count());
    const esc = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));
    const fmtRp = (value) => 'Rp ' + Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
    let loaded = false;
    let loading = null;

    function renderStore(storeId, items) {
        if (!items.length) return '';
        const rows = items.map(function (item, index) {
            const itemName = item.item_name || 'GMV Max Auto';
            return '<tr>'
                + '<td class="profit-index-cell">' + (index + 1) + '</td>'
                + '<td><div style="font-weight:700;max-width:280px;overflow:hidden;text-overflow:ellipsis;" title="' + esc(itemName) + '">' + esc(itemName) + '</div><div style="font-size:.64rem;color:var(--dsh-muted);">SKU ' + esc(item.item_sku || '-') + ' · ID ' + esc(item.channel_item_id) + '</div></td>'
                + '<td class="text-end"><b>' + Number(item.orders || 0).toLocaleString('id-ID') + ' order</b><div style="font-size:.62rem;color:var(--dsh-muted);">' + Number(item.pcs || 0).toLocaleString('id-ID') + ' pcs</div></td>'
                + '<td class="text-end">' + fmtRp(item.gmv) + '<div style="font-size:.62rem;color:var(--dsh-muted);">dana cair ± ' + fmtRp(item.net_revenue) + '</div></td>'
                + '<td class="text-end" style="color:#dc2626;"><b>−' + fmtRp(Number(item.spend || 0) * 1.11) + '</b><div style="font-size:.62rem;color:var(--dsh-muted);">sebelum PPN ' + fmtRp(Number(item.spend || 0)) + '</div></td>'
                + '<td class="text-center"><span style="color:#b45309;font-weight:700;"><i class="bi bi-exclamation-circle"></i> HPP belum ada</span><button type="button" class="btn btn-sm d-block mt-1" style="font-size:.62rem;padding:.16rem .48rem;border-radius:999px;color:#b45309;border:1px solid rgba(180,83,9,.35);background:rgba(217,119,6,.06);" data-gms-map-store="' + esc(storeId) + '" data-gms-map-item="' + esc(item.channel_item_id) + '" data-gms-map-name="' + esc(itemName) + '" onclick="openGmsItemMapping(this)"><i class="bi bi-link-45deg"></i> Pilih item HPP</button></td>'
                + '</tr>';
        }).join('');
        return '<div style="font-size:.72rem;font-weight:750;color:var(--dsh-muted);margin:.8rem 0 .35rem;">' + esc(storeNames[storeId] || ('Toko #' + storeId)) + '</div>'
            + '<div class="table-responsive profit-table-wrap"><table class="dpanel-table dpanel-table-sm w-100 profit-table-compact product-unmapped-api-table"><thead><tr><th class="profit-index-cell">#</th><th>Item / SKU</th><th class="text-end">Penjualan</th><th class="text-end">GMV</th><th class="text-end">Iklan</th><th class="text-center">Tindakan</th></tr></thead><tbody>' + rows + '</tbody></table></div>';
    }

    window.loadProductUnmappedTab = function () {
        if (loaded) return Promise.resolve();
        if (loading) return loading;
        if (!bodyEl) {
            if (badgeEl) badgeEl.textContent = regularCount + ' produk perlu mapping';
            loaded = true;
            return Promise.resolve();
        }
        if (!storeIds.length) {
            if (badgeEl) badgeEl.textContent = regularCount + ' produk perlu mapping';
            bodyEl.textContent = 'Tidak ada item GMV Max Auto yang perlu ditampilkan.';
            loaded = true;
            return Promise.resolve();
        }
        loading = Promise.all(storeIds.map(async function (storeId) {
            const response = await fetch(endpointBase + '/' + storeId + '?date_from=' + encodeURIComponent(fromDate) + '&date_to=' + encodeURIComponent(toDate), { headers: { Accept: 'application/json' } });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'Gagal memuat item GMV Max Auto belum mapping.');
            return { storeId: storeId, items: (payload.data || []).filter((item) => !item.mapped) };
        })).then(function (entries) {
            const gmsItems = entries.reduce((sum, entry) => sum + entry.items.length, 0);
            const total = regularCount + gmsItems;
            if (badgeEl) badgeEl.textContent = total + ' produk perlu mapping';
            bodyEl.innerHTML = gmsItems
                ? entries.map((entry) => renderStore(entry.storeId, entry.items)).join('')
                : 'Semua item GMV Max Auto sudah memiliki HPP.';
            loaded = true;
        }).catch(function (error) {
            if (badgeEl) badgeEl.textContent = regularCount + ' produk perlu mapping';
            bodyEl.innerHTML = '<span style="color:#b91c1c;">' + esc(error.message) + '</span>';
        }).finally(function () {
            loading = null;
        });
        return loading;
    };
    try { if (localStorage.getItem('profitViewMode') === 'product-unmapped') window.loadProductUnmappedTab(); } catch (e) {}
})();
</script>
@endif

<script>
(function () {
    const endpointBase = @json(url('/marketplace/ads-dashboard/drilldown'));
    const fromDate = @json($dateFrom ?? now()->subDays(6)->toDateString());
    const toDate = @json($dateTo ?? now()->toDateString());
    let activeRow = null;
    let activeDetail = null;
    let activeChart = null;

    const esc = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));
    const fmtRp = (value) => 'Rp ' + Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
    const signedRp = (value) => {
        if (value === null || value === undefined) return 'N/A';
        return (Number(value) < 0 ? '−' : '') + fmtRp(Math.abs(Number(value)));
    };
    const fmtNumber = (value) => Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
    const fmtShort = (value) => {
        const number = Number(value || 0);
        if (Math.abs(number) >= 1000000) return 'Rp ' + (number / 1000000).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' jt';
        if (Math.abs(number) >= 1000) return 'Rp ' + (number / 1000).toLocaleString('id-ID', { maximumFractionDigits: 0 }) + ' rb';
        return 'Rp ' + number.toLocaleString('id-ID', { maximumFractionDigits: 0 });
    };
    const formatDate = (value) => {
        const date = new Date(value + 'T00:00:00');
        return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }).replace('.', '');
    };

    function closeDrilldown() {
        if (activeChart) {
            activeChart.destroy();
            activeChart = null;
        }
        if (activeDetail) activeDetail.remove();
        if (activeRow) activeRow.classList.remove('is-drilldown-open');
        activeRow = null;
        activeDetail = null;
    }

    function metricCard(label, value, color) {
        return '<div class="profit-drilldown-kpi"><div class="profit-drilldown-kpi-label">' + label + '</div><div class="profit-drilldown-kpi-value" style="color:' + (color || 'var(--text)') + ';">' + value + '</div></div>';
    }

    function renderChart(canvas, payload) {
        if (!window.Chart) {
            canvas.parentElement.innerHTML = '<div style="padding:1.5rem;text-align:center;color:var(--dsh-muted);font-size:.72rem;">Grafik belum tersedia.</div>';
            return;
        }
        const rows = payload.data || [];
        activeChart = new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: rows.map((row) => formatDate(row.date)),
                datasets: [
                    {
                        label: 'Omzet',
                        data: rows.map((row) => Number(row.gmv || 0)),
                        backgroundColor: 'rgba(59,130,246,.20)',
                        borderColor: 'rgba(59,130,246,.50)',
                        borderWidth: 1,
                        yAxisID: 'money',
                    },
                    {
                        label: 'Net Profit (Est)',
                        type: 'line',
                        data: rows.map((row) => row.profit_after_ads === null ? null : Number(row.profit_after_ads)),
                        borderColor: '#10b981',
                        backgroundColor: '#10b981',
                        borderWidth: 2,
                        tension: .3,
                        pointRadius: 2,
                        yAxisID: 'money',
                    },
                    {
                        label: 'ROAS',
                        type: 'line',
                        data: rows.map((row) => row.roas === null ? null : Number(row.roas)),
                        borderColor: '#eab308',
                        backgroundColor: '#eab308',
                        borderWidth: 2,
                        tension: .3,
                        pointRadius: 2,
                        pointHoverRadius: 4,
                        yAxisID: 'ratio',
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, padding: 12, font: { size: 10 } },
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const value = context.parsed.y;
                                return context.dataset.label + ': ' + (context.dataset.yAxisID === 'ratio'
                                    ? Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 }) + 'x'
                                    : signedRp(value));
                            },
                            afterBody: (items) => {
                                const row = rows[items[0]?.dataIndex] || {};
                                return ['AOV: ' + (row.aov ? fmtRp(row.aov) : '—')];
                            },
                        },
                    },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { maxRotation: 45, font: { size: 9 } } },
                    money: { type: 'linear', position: 'left', beginAtZero: true, grid: { color: 'rgba(148,163,184,.14)' }, ticks: { font: { size: 9 }, callback: (value) => fmtShort(value) } },
                    ratio: { type: 'linear', position: 'right', beginAtZero: true, grid: { drawOnChartArea: false }, ticks: { font: { size: 9 }, callback: (value) => Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + 'x' } },
                },
            },
        });
    }

    function renderDetail(detail, payload, row) {
        const totals = payload.totals || {};
        const profitColor = totals.profit_after_ads === null ? '#b45309' : (Number(totals.profit_after_ads) >= 0 ? '#15803d' : '#b91c1c');
        detail.innerHTML = '<td colspan="' + (row.closest('table')?.querySelectorAll('thead th').length || 12) + '">' +
            '<div class="profit-drilldown-panel">' +
                '<div class="profit-drilldown-head">' +
                    '<div><div class="profit-drilldown-title"><i class="bi bi-graph-up-arrow"></i> ' + esc(payload.label || row.dataset.drilldownLabel || 'Detail performa') + '</div>' +
                    '<div class="profit-drilldown-subtitle">' + esc(payload.date_from) + ' s/d ' + esc(payload.date_to) + ' · tren harian</div></div>' +
                    '<button type="button" class="profit-drilldown-close" data-close-profit-drilldown><i class="bi bi-chevron-up"></i> Tutup</button>' +
                '</div>' +
                '<div class="profit-drilldown-kpis">' +
                    metricCard('GMV', fmtRp(totals.gmv), '#15803d') +
                    metricCard('Iklan +PPN', '−' + fmtRp(totals.spend_after_tax), '#dc2626') +
                    metricCard('Net Profit', signedRp(totals.profit_after_ads), profitColor) +
                    metricCard('ROAS', totals.roas === null ? '—' : Number(totals.roas).toLocaleString('id-ID', { maximumFractionDigits: 2 }) + 'x', '#b45309') +
                '</div>' +
                '<div class="profit-drilldown-chart"><canvas></canvas></div>' +
            '</div>' +
        '</td>';
        renderChart(detail.querySelector('canvas'), payload);
    }

    async function openDrilldown(row) {
        if (activeRow === row) {
            closeDrilldown();
            return;
        }
        closeDrilldown();
        const detail = document.createElement('tr');
        detail.className = 'profit-drilldown-row';
        detail.innerHTML = '<td colspan="12"><div class="profit-drilldown-panel" style="text-align:center;color:var(--dsh-muted);font-size:.72rem;padding:1.2rem;"><span class="spinner-border spinner-border-sm me-1"></span> Memuat tren harian…</div></td>';
        row.parentNode.insertBefore(detail, row.nextSibling);
        row.classList.add('is-drilldown-open');
        activeRow = row;
        activeDetail = detail;
        try {
            const query = '?kind=' + encodeURIComponent(row.dataset.drilldownKind || 'campaign')
                + '&id=' + encodeURIComponent(row.dataset.drilldownId || '')
                + '&date_from=' + encodeURIComponent(fromDate)
                + '&date_to=' + encodeURIComponent(toDate);
            const response = await fetch(endpointBase + '/' + encodeURIComponent(row.dataset.drilldownStore || '') + query, { headers: { Accept: 'application/json' } });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'Gagal memuat detail harian.');
            if (activeRow === row) renderDetail(detail, payload, row);
        } catch (error) {
            if (activeRow === row) detail.querySelector('td').innerHTML = '<div class="profit-drilldown-panel" style="color:#b91c1c;font-size:.72rem;">' + esc(error.message) + '</div>';
        }
    }

    window.closeProfitDrilldown = closeDrilldown;
    document.addEventListener('click', function (event) {
        if (event.target.closest('[data-close-profit-drilldown]')) {
            closeDrilldown();
            return;
        }
        const row = event.target.closest('tr.profit-drilldown-trigger');
        if (!row || event.target.closest('button,a,input,select,textarea')) return;
        openDrilldown(row);
    });
})();

let sortProfitCol = 'net_profit';
let sortProfitDir = 'asc';

function sortProfitTable(col) {
    if (window.closeProfitDrilldown) window.closeProfitDrilldown();
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

    rows.forEach((row, index) => {
        tbody.appendChild(row);
        const indexCell = row.querySelector('.profit-index-cell');
        if (indexCell) indexCell.textContent = index + 1;
    });
}
</script>
