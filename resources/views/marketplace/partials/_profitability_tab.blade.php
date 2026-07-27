@php
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

        $netRevRatio   = $camp->net_revenue_ratio ?? 0.781; // default = potongan ±21,9% (selaras controller)
        $netRevenue    = $camp->gmv * $netRevRatio;
        $itemsSold     = (int) ($camp->items_sold ?? 0);
        $unitCogsRow   = (float) ($camp->unit_cogs ?? 0);
        // COGS eksak = HPP/unit × pcs terjual; fallback estimasi rasio harga
        // hanya untuk data lama yang belum punya pcs.
        $cogsExact     = $unitCogsRow > 0 && $itemsSold > 0;
        $totalCogsRow  = $cogsExact
            ? $unitCogsRow * $itemsSold
            : $camp->gmv * ($camp->cogs_ratio ?? 0);
        $spendAfterTax = $camp->spend * 1.11; // PPN 11% topup iklan
        $profit        = $netRevenue - $totalCogsRow - $spendAfterTax;
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
            'isProfitable'  => $profit >= 0,
        ]);
    }

    // Kampanye paling rugi ditaruh paling atas — itu yang butuh perhatian duluan.
    $rows = $rows->sortBy('profit')->values();

    // ── Group by KATEGORI (hasil mapping SKU produk marketplace → item internal) ──
    $byCategory = $rows
        ->groupBy(fn ($r) => $r->camp->item_category ?? 'Belum termapping')
        ->map(function ($grp, $name) {
            return (object) [
                'name'       => $name,
                'campaigns'  => $grp->count(),
                'orders'     => $grp->sum(fn ($r) => (int) $r->camp->orders),
                'items'      => $grp->sum('itemsSold'),
                'spend'      => $grp->sum(fn ($r) => (float) $r->camp->spend),
                'topup'      => $grp->sum('spendAfterTax'),
                'gmv'        => $grp->sum(fn ($r) => (float) $r->camp->gmv),
                'netRevenue' => $grp->sum('netRevenue'),
                'cogs'       => $grp->sum('totalCogs'),
                'profit'     => $grp->sum('profit'),
                'lossCount'  => $grp->where('isProfitable', false)->count(),
                'unmapped'   => $name === 'Belum termapping',
            ];
        })
        ->sortBy([['unmapped', 'asc'], ['profit', 'asc']])
        ->values();

    $totalCampaigns  = $rows->count();
    $profitableCount = $rows->where('isProfitable', true)->count();
    $lossCount       = $totalCampaigns - $profitableCount;
    $totalSpend      = $rows->sum(fn ($r) => $r->camp->spend);
    $totalTopup      = $rows->sum('spendAfterTax');
    $totalGmv        = $rows->sum(fn ($r) => $r->camp->gmv);
    $totalNetRevenue = $rows->sum('netRevenue');
    $totalCogsAll    = $rows->sum('totalCogs');
    $totalProfit     = $rows->sum('profit');
    $avgAcos         = $totalGmv > 0 ? ($totalSpend / $totalGmv) * 100 : 0;
    $cogsPctOmzet    = $totalGmv > 0 ? ($totalCogsAll / $totalGmv) * 100 : 0;   // porsi modal terhadap omzet
    $grossMargin     = $totalNetRevenue - $totalCogsAll;                        // sisa setelah HPP, sebelum iklan
    $noHppCount      = $rows->filter(fn ($r) => $r->unitCogs <= 0)->count();    // kampanye tanpa data HPP
    $avgFeePct       = $totalGmv > 0 ? max(0, (1 - ($totalNetRevenue / $totalGmv)) * 100) : 0; // rata-rata tertimbang potongan admin
    $totalFeeAmt     = max(0, $totalGmv - $totalNetRevenue); // total rupiah potongan platform
    $totalRoas       = $totalSpend > 0 ? $totalGmv / $totalSpend : 0;              // omzet per rupiah iklan
    $netRoas         = $totalTopup > 0 ? $totalNetRevenue / $totalTopup : 0;       // uang cair per rupiah iklan (incl. PPN)
    $feeMode         = $adsSetting->admin_fee_mode ?? 'auto';
    $feeManualPct    = $adsSetting->admin_fee_pct ?? null;
    $fmt = fn ($n) => 'Rp ' . number_format(abs($n), 0, ',', '.');

    // Rekonsiliasi dengan Seller Center: belanja level toko (sumber yang sama
    // dengan angka platform) vs total kampanye di tabel ini. Selisihnya =
    // tipe iklan tanpa rincian kampanye (mis. Iklan Pencarian Toko/afiliasi).
    $shopSpend = isset($kpi['current']) ? (float) ($kpi['current']->spend ?? 0) : null;
    $otherAdsSpend = $shopSpend !== null ? max(0, $shopSpend - $totalSpend) : null;
@endphp

@if($otherAdsSpend !== null && $shopSpend > 0 && $otherAdsSpend > ($shopSpend * 0.005))
<div style="display:flex; flex-wrap:wrap; gap:.4rem 1.2rem; align-items:center; font-size:.68rem; color:var(--dsh-muted); margin-bottom:.6rem; padding:.5rem .75rem; border:1px dashed var(--dsh-border); border-radius:10px;">
    <span><i class="bi bi-shop"></i> Belanja iklan versi Seller Center: <b style="color:var(--text);">{{ $fmt($shopSpend) }}</b></span>
    <span>Terpetakan ke kampanye di tabel ini: <b style="color:var(--text);">{{ $fmt($totalSpend) }}</b></span>
    <span>Iklan lainnya (tanpa rincian kampanye, mis. Iklan Toko/afiliasi): <b style="color:#b45309;">{{ $fmt($otherAdsSpend) }}</b></span>
</div>
@endif

<!-- ── Pengaturan admin fee: otomatis (settlement) / manual ── -->
@if(($storeId ?? null) !== 'all')
<div style="display:flex; justify-content:flex-end; margin-bottom:.5rem;">
    <details style="position:relative;">
        <summary style="cursor:pointer; list-style:none; display:inline-flex; align-items:center; gap:.35rem; padding:.35rem .7rem; border:1px solid var(--dsh-border); border-radius:8px; font-size:.72rem; font-weight:600; color:var(--text); background:var(--bg);">
            <i class="bi bi-gear" style="color:var(--dsh-muted);"></i>
            Adm fee: {{ $feeMode === 'manual' ? 'Manual ' . number_format((float) $feeManualPct, 1, ',', '.') . '%' : 'Otomatis' }}
        </summary>
        <form method="POST" action="{{ route('marketplace.ads.fee.setting') }}" style="position:absolute; right:0; top:calc(100% + 6px); z-index:30; min-width:250px; padding:.8rem .9rem; border:1px solid var(--dsh-border); border-radius:10px; background:var(--bg); box-shadow:0 8px 24px rgba(0,0,0,.12); font-size:.75rem;">
            @csrf
            <input type="hidden" name="store_id" value="{{ $storeId }}">
            <label style="display:flex; align-items:center; gap:.45rem; margin-bottom:.5rem; cursor:pointer;">
                <input type="radio" name="admin_fee_mode" value="auto" {{ $feeMode !== 'manual' ? 'checked' : '' }}>
                <span>Otomatis — dari data pencairan per item</span>
            </label>
            <label style="display:flex; align-items:center; gap:.45rem; margin-bottom:.7rem; cursor:pointer;">
                <input type="radio" name="admin_fee_mode" value="manual" {{ $feeMode === 'manual' ? 'checked' : '' }}>
                <span>Manual</span>
                <input type="number" name="admin_fee_pct" step="0.1" min="0" max="99" value="{{ $feeManualPct !== null ? number_format((float) $feeManualPct, 1, '.', '') : '21.9' }}" style="width:64px; padding:.2rem .35rem; border:1px solid var(--dsh-border); border-radius:6px; background:var(--bg); color:var(--text); font-size:.75rem; text-align:right;" onfocus="this.closest('form').querySelector('input[value=manual]').checked = true">
                <span>%</span>
            </label>
            <button type="submit" class="btn btn-sm btn-primary" style="width:100%; font-size:.72rem; font-weight:700;">Simpan</button>
        </form>
    </details>
</div>
@endif

<!-- ── KPI — urutan alur hitung: Omzet − HPP − Iklan = Net Profit ── -->
@php
    $kLabel = 'font-size:.6rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px;';
    $kValue = 'font-size:1.15rem; font-weight:800; font-variant-numeric:tabular-nums; margin-top:1px; white-space:nowrap;';
    $kSub   = 'font-size:.65rem; color:var(--dsh-muted); margin-top:2px; font-variant-numeric:tabular-nums; white-space:nowrap;';
    $profitMarginPct = $totalGmv > 0 ? ($totalProfit / $totalGmv) * 100 : 0;
@endphp
<div class="dash-panels mb-3" style="grid-template-columns: repeat(auto-fit, minmax(185px, 1fr)); gap: .6rem;">

    <div class="dpanel" style="padding:.75rem .95rem; border-radius:12px; border:1px solid rgba(3,105,161,.2); background:rgba(3,105,161,.05);">
        <div style="{{ $kLabel }} color:#0369a1;"><i class="bi bi-graph-up-arrow"></i> Omzet</div>
        <div style="{{ $kValue }} color:#0369a1;">{{ $fmt($totalGmv) }}</div>
        <div style="{{ $kSub }}">Cair <b style="color:#0369a1;">{{ $fmt($totalNetRevenue) }}</b> · adm {{ number_format($avgFeePct, 1, ',', '.') }}%</div>
    </div>

    <div class="dpanel" style="padding:.75rem .95rem; border-radius:12px; border:1px solid rgba(100,116,139,.25); background:rgba(100,116,139,.05);">
        <div style="{{ $kLabel }} color:#475569;"><i class="bi bi-box-seam"></i> HPP · Modal</div>
        <div style="{{ $kValue }} color:#334155;">&minus;{{ $fmt($totalCogsAll) }}</div>
        <div style="{{ $kSub }}">{{ number_format($cogsPctOmzet, 1, ',', '.') }}% omzet · margin <b style="color:{{ $grossMargin >= 0 ? '#15803d' : '#b91c1c' }};">{{ $grossMargin < 0 ? '−' : '' }}{{ $fmt($grossMargin) }}</b></div>
        @if($noHppCount > 0)
            <div style="{{ $kSub }} color:#b45309;"><i class="bi bi-exclamation-triangle"></i> {{ $noHppCount }} tanpa HPP</div>
        @endif
    </div>

    <div class="dpanel" style="padding:.75rem .95rem; border-radius:12px; border:1px solid rgba(245,158,11,.25); background:rgba(245,158,11,.05);">
        <div style="{{ $kLabel }} color:#b45309;"><i class="bi bi-wallet2"></i> Iklan</div>
        <div style="{{ $kValue }} color:#92400e;">&minus;{{ $fmt($totalTopup) }}</div>
        <div style="{{ $kSub }}">{{ $fmt($totalSpend) }} + PPN 11%</div>
    </div>

    <div class="dpanel" style="padding:.75rem .95rem; border-radius:12px; border:1px solid rgba({{ $totalProfit >= 0 ? '22,163,74' : '220,38,38' }},.3); background:rgba({{ $totalProfit >= 0 ? '22,163,74' : '220,38,38' }},.07);">
        <div style="{{ $kLabel }} color:{{ $totalProfit >= 0 ? '#15803d' : '#b91c1c' }};"><i class="bi bi-cash-stack"></i> = Net Profit</div>
        <div style="{{ $kValue }} color:{{ $totalProfit >= 0 ? '#15803d' : '#b91c1c' }};">{{ $totalProfit < 0 ? '−' : '' }}{{ $fmt($totalProfit) }}</div>
        <div style="{{ $kSub }}">{{ number_format($profitMarginPct, 1, ',', '.') }}% dari omzet</div>
    </div>

    <div class="dpanel" style="padding:.75rem .95rem; border-radius:12px; border:1px solid rgba(124,58,237,.2); background:rgba(124,58,237,.04);">
        <div style="{{ $kLabel }} color:#7c3aed;"><i class="bi bi-speedometer2"></i> ROAS · ACOS</div>
        <div style="{{ $kValue }} color:#6d28d9;">{{ number_format($totalRoas, 2, ',', '.') }}x <span style="font-size:.8rem; color:var(--dsh-muted);">· {{ number_format($avgAcos, 1, ',', '.') }}%</span></div>
        <div style="{{ $kSub }}">bersih <b style="color:#6d28d9;">{{ number_format($netRoas, 2, ',', '.') }}x</b></div>
    </div>

    <div class="dpanel" style="padding:.75rem .95rem; border-radius:12px; border:1px solid rgba(148,163,184,.25); background:rgba(148,163,184,.05);">
        <div style="{{ $kLabel }} color:var(--dsh-muted);"><i class="bi bi-bullseye"></i> Kampanye Untung</div>
        <div style="{{ $kValue }} color:var(--text);">{{ $profitableCount }} <span style="font-size:.8rem; color:var(--dsh-muted); font-weight:600;">/ {{ $totalCampaigns }}</span></div>
        <div style="{{ $kSub }} {{ $lossCount > 0 ? 'color:#b91c1c;' : '' }}">{{ $lossCount > 0 ? $lossCount . ' rugi' : 'semua untung' }}</div>
    </div>
</div>

<!-- ── Toggle tampilan: Per Kampanye / Per Kategori ── -->
<div style="display:flex; gap:.35rem; margin-bottom:.6rem;">
    <button type="button" id="btnViewCampaign" class="btn fw-bold" onclick="__profitView('campaign')" style="border-radius:999px; font-size:.72rem; padding:.3rem .9rem; background:var(--dsh-accent); color:#fff; border:1px solid var(--dsh-accent);">Per Kampanye</button>
    <button type="button" id="btnViewCategory" class="btn fw-bold" onclick="__profitView('category')" style="border-radius:999px; font-size:.72rem; padding:.3rem .9rem; color:var(--dsh-muted); border:1px solid var(--dsh-border); background:transparent;">Per Kategori</button>
</div>

<!-- ── Tabel per KATEGORI ── -->
<div id="profitViewCategory" style="display:none;">
    <div class="table-responsive">
        <table class="dpanel-table dpanel-table-sm" style="white-space: nowrap;">
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
                    <tr style="border-bottom: 1px solid var(--dsh-border); {{ $cat->profit >= 0 ? '' : 'background: rgba(220, 38, 38, 0.045);' }}">
                        <td style="padding:.6rem .5rem;">
                            <span style="font-weight:700; color:{{ $cat->unmapped ? '#b45309' : 'var(--text)' }}; font-size:.85rem;">{{ $cat->name }}</span>
                            @if($cat->unmapped)
                                <div style="font-size:.62rem; color:var(--dsh-muted);">lengkapi <a href="{{ route('marketplace.sku-mapping') }}" style="color:var(--dsh-accent);">SKU Mapping</a> agar terkelompok</div>
                            @elseif($cat->lossCount > 0)
                                <div style="font-size:.62rem; color:#b91c1c;">{{ $cat->lossCount }} kampanye rugi</div>
                            @endif
                        </td>
                        <td class="text-end" style="font-variant-numeric:tabular-nums; font-weight:700; color:var(--text); vertical-align:middle;">{{ $cat->campaigns }}</td>
                        <td class="text-end" style="font-variant-numeric:tabular-nums; color:var(--text); vertical-align:middle;">{{ number_format($cat->orders, 0, ',', '.') }} <span style="color:var(--dsh-muted);">&bull;</span> {{ number_format($cat->items, 0, ',', '.') }} pcs</td>
                        <td class="text-end" style="font-variant-numeric:tabular-nums; font-weight:700; color:#dc2626; vertical-align:middle;">{{ $fmt($cat->spend) }}<div style="font-size:.62rem; color:var(--dsh-muted); font-weight:500;">+PPN {{ $fmt($cat->topup) }}</div></td>
                        <td class="text-end" style="font-variant-numeric:tabular-nums; font-weight:800; color:#0369a1; vertical-align:middle;">{{ $fmt($cat->netRevenue) }}<div style="font-size:.62rem; color:var(--dsh-muted); font-weight:500;">Omzet {{ $fmt($cat->gmv) }}</div></td>
                        <td class="text-end" style="font-variant-numeric:tabular-nums; font-weight:700; color:var(--text); vertical-align:middle;">&minus;{{ $fmt($cat->cogs) }}</td>
                        <td class="text-end" style="font-variant-numeric:tabular-nums; font-weight:800; font-size:.95rem; color:{{ $cat->profit >= 0 ? '#16a34a' : '#dc2626' }}; vertical-align:middle;">{{ $cat->profit < 0 ? '-' : '' }}{{ $fmt($cat->profit) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-4" style="color:var(--dsh-muted); font-size:.8rem;">Belum ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
window.__profitView = function (mode) {
    const cat = document.getElementById('profitViewCategory');
    const camp = document.getElementById('profitViewCampaign');
    const bCat = document.getElementById('btnViewCategory');
    const bCamp = document.getElementById('btnViewCampaign');
    const on  = 'border-radius:999px; font-size:.72rem; padding:.3rem .9rem; background:var(--dsh-accent); color:#fff; border:1px solid var(--dsh-accent);';
    const off = 'border-radius:999px; font-size:.72rem; padding:.3rem .9rem; color:var(--dsh-muted); border:1px solid var(--dsh-border); background:transparent;';
    const isCat = mode === 'category';
    cat.style.display = isCat ? 'block' : 'none';
    camp.style.display = isCat ? 'none' : 'block';
    bCat.style.cssText = isCat ? on : off;
    bCamp.style.cssText = isCat ? off : on;
    try { localStorage.setItem('profitViewMode', mode); } catch (e) {}
};
(function () {
    try { if (localStorage.getItem('profitViewMode') === 'category') window.__profitView('category'); } catch (e) {}
})();
</script>

<div id="profitViewCampaign">
<!-- ── Tabel per kampanye — kolom mengikuti alur hitung: Pendapatan Bersih − HPP − Iklan = Net Profit ── -->
<div class="table-responsive">
    <table class="dpanel-table dpanel-table-sm table-hover" style="white-space: nowrap;">
        <thead>
            <tr>
                <th>Kampanye</th>
                <th class="text-end">Konversi</th>
                <th class="text-end">Pendapatan Bersih</th>
                <th class="text-end"><span style="color:var(--dsh-muted); font-weight:600;">&minus;</span> HPP</th>
                <th class="text-end"><span style="color:var(--dsh-muted); font-weight:600;">&minus;</span> Iklan <span style="font-size:.6rem; font-weight:500; color:var(--dsh-muted); text-transform:none;">+PPN 11%</span></th>
                <th class="text-end" style="border-left: 2px solid var(--dsh-border);"><span style="color:var(--dsh-muted); font-weight:600;">=</span> Net Profit</th>
                <th class="text-end">ACOS</th>
                <th class="text-center">Rekomendasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $r)
                @php
                    $camp = $r->camp;
                    $reco = $camp->reco ?? ['label' => 'Optimize', 'color' => '#ca8a04', 'icon' => '⚡'];
                    $baseColor = $reco['color'] ?? '#64748b';
                    $bgColor = match($baseColor) {
                        '#16a34a' => 'rgba(22, 163, 74, 0.15)',
                        '#ca8a04' => 'rgba(234, 179, 8, 0.15)',
                        '#b91c1c' => 'rgba(220, 38, 38, 0.15)',
                        '#b45309' => 'rgba(180, 83, 9, 0.15)',
                        default => 'rgba(148, 163, 184, 0.15)'
                    };
                @endphp
                <tr style="border-bottom: 1px solid var(--dsh-border); {{ $r->isProfitable ? '' : 'background: rgba(220, 38, 38, 0.045);' }}">
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
                        <div style="font-size: .65rem; color: {{ $r->unitCogs > 0 ? 'var(--dsh-muted)' : '#b45309' }}; margin-top: 2px;" title="{{ $r->cogsExact ? 'Eksak: HPP × pcs terjual' : 'Estimasi dari rasio harga (pcs belum tercatat)' }}">{{ $r->unitCogs > 0 ? ($r->cogsExact ? $fmt($r->unitCogs) . '/pcs × ' . $r->itemsSold : $fmt($r->unitCogs) . '/pcs · estimasi') : 'belum diisi' }}</div>
                    </td>

                    <td class="text-end" style="vertical-align: middle;">
                        <div style="font-weight: 700; color: #dc2626; font-variant-numeric: tabular-nums;">&minus;{{ $fmt($r->spendAfterTax) }}</div>
                        <div style="font-size: .65rem; color: var(--dsh-muted); margin-top: 2px;">iklan {{ $fmt($camp->spend) }}</div>
                    </td>

                    <td class="text-end" style="vertical-align: middle; border-left: 2px solid var(--dsh-border); background: rgba({{ $r->isProfitable ? '22, 163, 74' : '220, 38, 38' }}, 0.05);">
                        <div style="font-weight: 800; font-size: .95rem; color: {{ $r->isProfitable ? '#16a34a' : '#dc2626' }}; font-variant-numeric: tabular-nums;" @if($r->feeIsEstimate || $r->unitCogs <= 0) title="Estimasi — {{ $r->unitCogs <= 0 ? 'HPP belum diisi' : 'belum ada data pencairan' }}" @endif>{{ ($r->feeIsEstimate || $r->unitCogs <= 0) ? '±' : '' }}{{ $r->profit < 0 ? '−' : '' }}{{ $fmt($r->profit) }}</div>
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
                <td class="text-end" style="font-weight: 800; font-size: .95rem; color: {{ $totalProfit >= 0 ? '#16a34a' : '#dc2626' }}; font-variant-numeric: tabular-nums; border-left: 2px solid var(--dsh-border); background: rgba({{ $totalProfit >= 0 ? '22, 163, 74' : '220, 38, 38' }}, 0.05);">{{ $totalProfit < 0 ? '−' : '' }}{{ $fmt($totalProfit) }}</td>
                <td class="text-end" style="font-weight: 700; color: var(--text); font-variant-numeric: tabular-nums;">{{ number_format($avgAcos, 1, ',', '.') }}%</td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>
</div>
