@php
    $totalSpend = 0;
    $totalGmv = 0;
    $totalProfit = 0;
    $totalCampaigns = 0;
    $profitableCount = 0;

    foreach ($campaigns as $camp) {
        if ($camp->spend > 0 || $camp->gmv > 0) {
            $totalSpend += $camp->spend;
            $totalGmv += $camp->gmv;
            
            // Profit dihitung dari formula: GMV * BE_ACOS - Spend
            // Nilai break_even_acos_pct sudah tersedia di $camp (persentase)
            $beAcos = $camp->break_even_acos_pct ? ($camp->break_even_acos_pct / 100) : 0.2; // default 20% margin if null
            $profit = ($camp->gmv * $beAcos) - $camp->spend;
            $totalProfit += $profit;
            
            if ($profit > 0) {
                $profitableCount++;
            }
            $totalCampaigns++;
        }
    }
    
    $avgAcos = $totalGmv > 0 ? ($totalSpend / $totalGmv) * 100 : 0;
    $overallHealth = $totalProfit >= 0 ? 'Sehat (Cuan)' : 'Kritis (Boncos)';
@endphp

<!-- KPI Cards -->
<div class="dash-panels mb-4" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.75rem;">
    <!-- Profit Bersih -->
    <div class="dpanel" style="padding: 0.75rem 1rem; border-radius: 12px; border: 1px solid rgba({{ $totalProfit >= 0 ? '22, 163, 74' : '220, 38, 38' }}, 0.2); background: rgba({{ $totalProfit >= 0 ? '22, 163, 74' : '220, 38, 38' }}, 0.05); display: flex; align-items: center; justify-content: space-between;">
        <div>
            <div style="font-size: 0.65rem; color: {{ $totalProfit >= 0 ? '#15803d' : '#b91c1c' }}; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px;"><i class="bi bi-cash-stack"></i> Total Net Profit</div>
            <div style="font-size: 1.15rem; font-weight: 700; color: {{ $totalProfit >= 0 ? '#15803d' : '#b91c1c' }};">{{ $totalProfit < 0 ? '-' : '' }}Rp {{ number_format(abs($totalProfit), 0, ',', '.') }}</div>
        </div>
    </div>
    
    <!-- Total Spend -->
    <div class="dpanel" style="padding: 0.75rem 1rem; border-radius: 12px; border: 1px solid rgba(245, 158, 11, 0.2); background: rgba(245, 158, 11, 0.05); display: flex; align-items: center; justify-content: space-between;">
        <div>
            <div style="font-size: 0.65rem; color: #b45309; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px;"><i class="bi bi-wallet2"></i> Total Pengeluaran</div>
            <div style="font-size: 1.15rem; font-weight: 700; color: #92400e;">Rp {{ number_format($totalSpend, 0, ',', '.') }}</div>
        </div>
    </div>

    <!-- Avg ACOS -->
    <div class="dpanel" style="padding: 0.75rem 1rem; border-radius: 12px; border: 1px solid rgba(37, 99, 235, 0.2); background: rgba(37, 99, 235, 0.05); display: flex; align-items: center; justify-content: space-between;">
        <div>
            <div style="font-size: 0.65rem; color: #1d4ed8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px;"><i class="bi bi-percent"></i> Rata-rata ACOS</div>
            <div style="font-size: 1.15rem; font-weight: 700; color: #1d4ed8;">{{ number_format($avgAcos, 1, ',', '.') }}%</div>
        </div>
    </div>
    
    <!-- Status Kampanye -->
    <div class="dpanel" style="padding: 0.75rem 1rem; border-radius: 12px; border: 1px solid rgba(148, 163, 184, 0.2); background: rgba(148, 163, 184, 0.05); display: flex; align-items: center; justify-content: space-between;">
        <div>
            <div style="font-size: 0.65rem; color: var(--dsh-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px;"><i class="bi bi-bullseye"></i> Kampanye Sehat</div>
            <div style="font-size: 1.15rem; font-weight: 700; color: var(--text);">{{ $profitableCount }} / {{ $totalCampaigns }}</div>
        </div>
    </div>
</div>

<!-- Kampanye Table -->
<div class="table-responsive">
    <table class="dpanel-table dpanel-table-sm table-hover" style="white-space: nowrap;">
        <thead>
            <tr>
                <th>Kampanye</th>
                <th class="text-end" title="Modal Pokok Asli Barang (HPP)">COGS (Modal)</th>
                <th class="text-end" title="Total Biaya Iklan & Estimasi Topup (Inc. PPN 11%)">Biaya Iklan</th>
                <th class="text-end" title="Jumlah Transaksi & Barang Terjual">Konversi</th>
                <th class="text-end" title="Pendapatan Bersih & Total Omzet (GMV)">Pendapatan (Revenue)</th>
                <th class="text-end" title="Rata-rata Nominal Transaksi (AOV)">AOV</th>
                <th class="text-end" title="Actual ACOS (Shopee) vs Batas Aman ACOS (Batas aman telah disesuaikan dengan pajak 11%)">Performa ACOS</th>
                <th class="text-end" title="Laba Bersih Riil (Net Revenue - COGS - Ads Topup)">Net Profit</th>
                <th class="text-center" title="Saran Algoritma">Rekomendasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($campaigns->filter(fn($c) => $c->spend > 0 || $c->gmv > 0) as $camp)
                @php
                    $unitCogs = $camp->unit_cogs ?? 0;
                    $beAcos = $camp->break_even_acos_pct ? ($camp->break_even_acos_pct / 100) : 0.2;
                    $aov = $camp->orders > 0 ? $camp->gmv / $camp->orders : 0;
                    
                    // Net Revenue & COGS Calculation
                    $netRevenue = $camp->gmv * ($camp->net_revenue_ratio ?? 0.89);
                    $aovNet = $camp->orders > 0 ? $netRevenue / $camp->orders : 0;
                    $totalCogs = $camp->gmv * ($camp->cogs_ratio ?? 0);
                    
                    // Taxes
                    $spendAfterTax = $camp->spend * 1.11; // PPN 11%
                    $acosBeforeTax = $camp->gmv > 0 ? ($camp->spend / $camp->gmv) * 100 : 0;
                    $acosAfterTax = $camp->gmv > 0 ? ($spendAfterTax / $camp->gmv) * 100 : 0;
                    
                    // Real Profit: Uang Masuk (Net Revenue) - Modal Barang (Total COGS) - Bakar Uang Iklan (Spend)
                    $profit = $netRevenue - $totalCogs - $spendAfterTax;
                    $isProfitable = $profit >= 0;
                    
                    $reco = $camp->reco ?? ['label' => 'Optimize', 'color' => '#ca8a04', 'icon' => '⚡', 'class' => 'reco-optimize'];
                    // Kita bisa ambil warna dari $reco['color'] untuk teks, dan bikin background rgba dari warna tersebut,
                    // Tapi untuk simpelnya, kita gunakan class CSS atau hardcode rgba
                    $baseColor = $reco['color'] ?? '#64748b';
                    $bgColor = match($baseColor) {
                        '#16a34a' => 'rgba(22, 163, 74, 0.15)',
                        '#ca8a04' => 'rgba(234, 179, 8, 0.15)',
                        '#b91c1c' => 'rgba(220, 38, 38, 0.15)',
                        '#b45309' => 'rgba(180, 83, 9, 0.15)',
                        default => 'rgba(148, 163, 184, 0.15)'
                    };
                    $recoLabel = $reco['label'] ?? 'Optimize';
                    $recoIcon = $reco['icon'] ?? '⚡';
                @endphp
                <tr style="border-bottom: 1px solid var(--dsh-border);">
                    <td style="padding: 0.75rem 0.5rem; max-width: 250px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            @if($camp->campaign_status === 'ongoing')
                                <span style="display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px; background:rgba(22, 163, 74, 0.15); border-radius:50%;" title="Berjalan">
                                    <i class="bi bi-play-fill text-success" style="font-size: .8rem;"></i>
                                </span>
                            @elseif($camp->campaign_status === 'paused')
                                <span style="display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px; background:rgba(234, 179, 8, 0.15); border-radius:50%;" title="Jeda">
                                    <i class="bi bi-pause-fill text-warning" style="font-size: .8rem;"></i>
                                </span>
                            @else
                                <span style="display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px; background:rgba(148, 163, 184, 0.15); border-radius:50%;" title="{{ ucfirst($camp->campaign_status) }}">
                                    <span style="display:inline-block; width:6px; height:6px; background:#94a3b8; border-radius:50%;"></span>
                                </span>
                            @endif
                            
                            <span style="font-weight: 700; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size:.85rem;" title="{{ $camp->campaign_name ?: 'Kampanye Tidak Ditemukan' }} (Camp ID: {{ $camp->channel_campaign_id }})">
                                {{ $camp->campaign_name ?: 'Kampanye Tidak Ditemukan' }}
                            </span>
                        </div>
                    </td>
                    
                    <td class="text-end" style="vertical-align: middle;">
                        <div style="font-weight: 700; color: var(--text); font-variant-numeric: tabular-nums;">
                            {{ $unitCogs > 0 ? 'Rp ' . number_format($unitCogs, 0, ',', '.') : '-' }}
                        </div>
                        <div style="font-size: .65rem; color: var(--dsh-muted); margin-top: 2px;">
                            per unit
                        </div>
                    </td>

                    <td class="text-end" style="vertical-align: middle;">
                        <div style="font-weight: 700; color: #dc2626; font-variant-numeric: tabular-nums;">
                            Rp {{ number_format($camp->spend, 0, ',', '.') }}
                        </div>
                        <div style="font-size: .65rem; color: var(--dsh-muted); margin-top: 2px;" title="Estimasi Biaya Topup (+PPN 11%)">
                            Topup: Rp {{ number_format($spendAfterTax, 0, ',', '.') }}
                        </div>
                    </td>
                    
                    <td class="text-end" style="vertical-align: middle;">
                        <div style="font-weight: 700; color: var(--text); font-variant-numeric: tabular-nums;">
                            {{ number_format($camp->orders, 0, ',', '.') }} Orders
                        </div>
                        <div style="font-size: .65rem; color: var(--dsh-muted); margin-top: 2px;" title="Kuantitas Barang Terjual">
                            {{ $camp->items_sold > 0 ? number_format($camp->items_sold, 0, ',', '.') . ' pcs Terjual' : '-' }}
                        </div>
                    </td>

                    <td class="text-end" style="vertical-align: middle;">
                        <div style="font-weight: 800; color: #0369a1; font-variant-numeric: tabular-nums;">
                            Rp {{ number_format($netRevenue, 0, ',', '.') }}
                        </div>
                        <div style="font-size: .65rem; color: #16a34a; margin-top: 2px; font-weight: 600;" title="Total Omzet Kotor (GMV)">
                            GMV: Rp {{ number_format($camp->gmv, 0, ',', '.') }}
                        </div>
                    </td>

                    <td class="text-end" style="vertical-align: middle;">
                        <div style="font-weight: 700; color: var(--text); font-variant-numeric: tabular-nums;">
                            {{ $aov > 0 ? 'Rp ' . number_format($aov, 0, ',', '.') : '-' }}
                        </div>
                        <div style="font-size: .65rem; color: #0369a1; margin-top: 2px; font-weight: 600;" title="AOV Bersih (Setelah Potongan Marketplace)">
                            Net: {{ $aovNet > 0 ? 'Rp ' . number_format($aovNet, 0, ',', '.') : '-' }}
                        </div>
                    </td>
                    
                    <td class="text-end" style="vertical-align: middle;">
                        <div style="font-weight: 800; font-size: .95rem; color: {{ ($acosBeforeTax <= ($camp->break_even_acos_pct ?? 20)) ? '#16a34a' : '#dc2626' }}; font-variant-numeric: tabular-nums;" title="Actual ACOS di Shopee Seller Center">
                            {{ $acosBeforeTax > 0 ? number_format($acosBeforeTax, 1, ',', '.') . '%' : '-' }}
                        </div>
                        <div style="font-size: .65rem; color: var(--dsh-muted); margin-top: 2px; font-weight: 600;" title="Batas Aman ACOS (Telah memasukkan faktor pajak PPN 11% topup)">
                            Batas Aman: {{ $camp->break_even_acos_pct !== null ? number_format($camp->break_even_acos_pct, 1, ',', '.') . '%' : '-' }}
                        </div>
                    </td>
                    
                    <td class="text-end" style="vertical-align: middle;">
                        <div style="font-weight: 800; font-size: .95rem; color: {{ $isProfitable ? '#16a34a' : '#dc2626' }}; font-variant-numeric: tabular-nums;">
                            {{ $profit < 0 ? '-' : '' }}Rp {{ number_format(abs($profit), 0, ',', '.') }}
                        </div>
                        <div style="font-size: .65rem; color: {{ $isProfitable ? '#16a34a' : '#dc2626' }}; margin-top: 2px; opacity: 0.8;">
                            {{ $isProfitable ? 'Untung' : 'Rugi' }}
                        </div>
                    </td>
                    
                    <td class="text-center" style="vertical-align: middle;">
                        <span style="display:inline-block; padding: 4px 10px; border-radius: 6px; font-size: 0.65rem; font-weight: 700; background: {{ $bgColor }}; color: {{ $baseColor }}; text-transform: uppercase;">
                            {{ $recoIcon }} {{ $recoLabel }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-4" style="color: var(--dsh-muted); font-size: .8rem;">
                        Belum ada data kampanye yang memiliki pengeluaran atau pendapatan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>