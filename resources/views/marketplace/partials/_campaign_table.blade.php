<style>
.config-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 12px;
    background: rgba(148, 163, 184, 0.08);
    border: 1px solid rgba(148, 163, 184, 0.15);
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--text);
    cursor: pointer;
    transition: all 0.2s ease;
}
.config-pill:hover {
    background: rgba(148, 163, 184, 0.15);
    border-color: rgba(148, 163, 184, 0.3);
}
.config-pill-roas {
    border-color: rgba(37, 99, 235, 0.25);
    background: rgba(37, 99, 235, 0.07);
}
.config-pill-label {
    color: var(--dsh-muted);
    font-size: .62rem;
    font-weight: 700;
}
.campaign-type-badge {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    flex: 0 0 auto;
    padding: 2px 6px;
    border-radius: 999px;
    font-size: .58rem;
    font-weight: 750;
    letter-spacing: .01em;
}
.campaign-type-cpc {
    color: #0369a1;
    background: rgba(14, 165, 233, .12);
}
.campaign-type-gms {
    color: #7c3aed;
    background: rgba(139, 92, 246, .13);
}
.growth-badge {
    display: inline-block;
    padding: 1px 5px;
    border-radius: 4px;
    font-size: 0.6rem;
    font-weight: 600;
    margin-top: 2px;
}
.growth-up {
    background: rgba(22, 163, 74, 0.1);
    color: #16a34a;
}
.growth-down {
    background: rgba(220, 38, 38, 0.1);
    color: #dc2626;
}
</style>
<div class="d-flex align-items-center gap-2 mb-2" style="font-size:.68rem; color:var(--dsh-muted);">
    <i class="bi bi-info-circle text-primary"></i>
    <span>Klik nilai pada kolom <strong style="color:var(--text);">Target ROAS</strong> untuk mengubahnya langsung dari tabel.</span>
</div>
<div class="table-responsive">
    <table class="dpanel-table dpanel-table-sm table-hover" style="white-space: nowrap;">
        <thead>
            <tr>
                <th style="cursor:pointer;" onclick="sortCampaignTable('name', this)">Kampanye <i class="bi bi-arrow-down-up ms-1" style="font-size:0.6rem; opacity:0.3;"></i></th>
                <th class="text-end" style="cursor:pointer;" title="Total jangkauan iklan" onclick="sortCampaignTable('impressions', this)">Jangkauan <i class="bi bi-arrow-down-up ms-1" style="font-size:0.6rem; opacity:0.3;"></i></th>
                <th class="text-end" style="cursor:pointer;" title="Total klik iklan" onclick="sortCampaignTable('clicks', this)">Klik <i class="bi bi-arrow-down-up ms-1" style="font-size:0.6rem; opacity:0.3;"></i></th>
                <th class="text-end" style="cursor:pointer;" title="Biaya Iklan" onclick="sortCampaignTable('spend', this)">Pengeluaran <i class="bi bi-arrow-down-up ms-1" style="font-size:0.6rem; opacity:0.3;"></i></th>
                <th class="text-end" style="cursor:pointer;" title="Pesanan Broad" onclick="sortCampaignTable('orders_b', this)">Pesanan (B) <i class="bi bi-arrow-down-up ms-1" style="font-size:0.6rem; opacity:0.3;"></i></th>
                <th class="text-end" style="cursor:pointer;" title="Pendapatan Broad" onclick="sortCampaignTable('gmv_b', this)">GMV (B) <i class="bi bi-arrow-down-up ms-1" style="font-size:0.6rem; opacity:0.3;"></i></th>
                <th class="text-end" style="cursor:pointer;" title="Pesanan Direct" onclick="sortCampaignTable('orders_d', this)">Pesanan (D) <i class="bi bi-arrow-down-up ms-1" style="font-size:0.6rem; opacity:0.3;"></i></th>
                <th class="text-end" style="cursor:pointer;" title="Pendapatan Direct" onclick="sortCampaignTable('gmv_d', this)">GMV (D) <i class="bi bi-arrow-down-up ms-1" style="font-size:0.6rem; opacity:0.3;"></i></th>
                <th class="text-end" style="cursor:pointer;" title="Biaya per klik" onclick="sortCampaignTable('cpc', this)">CPC <i class="bi bi-arrow-down-up ms-1" style="font-size:0.6rem; opacity:0.3;"></i></th>
                <th class="text-end" style="cursor:pointer;" title="Target ROAS — klik nilai untuk mengubah" onclick="sortCampaignTable('target_roas', this)">Target ROAS <i class="bi bi-arrow-down-up ms-1" style="font-size:0.6rem; opacity:0.3;"></i></th>
                <th class="text-end" style="cursor:pointer;" title="Return on Ad Spend aktual" onclick="sortCampaignTable('roas', this)">ROAS Aktual <i class="bi bi-arrow-down-up ms-1" style="font-size:0.6rem; opacity:0.3;"></i></th>
            </tr>
        </thead>
        <tbody>
            @forelse($campaigns as $camp)
                @php
                    $cpc = $camp->sum_clicks > 0 ? $camp->sum_expense / $camp->sum_clicks : 0;
                    $isGmsCampaign = str_starts_with((string) ($camp->channel_campaign_id ?? ''), 'GMS-');
                    
                    // Growth calculations
                    $imp_growth = $camp->sum_prev_impressions > 0 ? (($camp->sum_impressions - $camp->sum_prev_impressions) / $camp->sum_prev_impressions) * 100 : ($camp->sum_impressions > 0 ? 100 : 0);
                    $click_growth = $camp->sum_prev_clicks > 0 ? (($camp->sum_clicks - $camp->sum_prev_clicks) / $camp->sum_prev_clicks) * 100 : ($camp->sum_clicks > 0 ? 100 : 0);
                    $exp_growth = $camp->sum_prev_expense > 0 ? (($camp->sum_expense - $camp->sum_prev_expense) / $camp->sum_prev_expense) * 100 : ($camp->sum_expense > 0 ? 100 : 0);
                    $bo_growth = $camp->sum_prev_broad_orders > 0 ? (($camp->sum_broad_orders - $camp->sum_prev_broad_orders) / $camp->sum_prev_broad_orders) * 100 : ($camp->sum_broad_orders > 0 ? 100 : 0);
                    $bg_growth = $camp->sum_prev_broad_gmv > 0 ? (($camp->sum_broad_gmv - $camp->sum_prev_broad_gmv) / $camp->sum_prev_broad_gmv) * 100 : ($camp->sum_broad_gmv > 0 ? 100 : 0);
                    $do_growth = $camp->sum_prev_direct_orders > 0 ? (($camp->sum_direct_orders - $camp->sum_prev_direct_orders) / $camp->sum_prev_direct_orders) * 100 : ($camp->sum_direct_orders > 0 ? 100 : 0);
                    $dg_growth = $camp->sum_prev_direct_gmv > 0 ? (($camp->sum_direct_gmv - $camp->sum_prev_direct_gmv) / $camp->sum_prev_direct_gmv) * 100 : ($camp->sum_direct_gmv > 0 ? 100 : 0);
                    $roas_growth = $camp->prev_roas > 0 ? (($camp->roas - $camp->prev_roas) / $camp->prev_roas) * 100 : ($camp->roas > 0 ? 100 : 0);
                @endphp
                <tr class="campaign-row" 
                    data-name="{{ strtolower($camp->campaign_name ?: '') }}" 
                    data-status="{{ strtolower($camp->campaign_status) }}"
                    data-impressions="{{ $camp->sum_impressions }}"
                    data-clicks="{{ $camp->sum_clicks }}"
                    data-spend="{{ $camp->sum_expense }}"
                    data-orders_b="{{ $camp->sum_broad_orders }}"
                    data-gmv_b="{{ $camp->sum_broad_gmv }}"
                    data-orders_d="{{ $camp->sum_direct_orders }}"
                    data-gmv_d="{{ $camp->sum_direct_gmv }}"
                    data-cpc="{{ $cpc }}"
                    data-target_roas="{{ $camp->target_roas ?? 0 }}"
                    data-roas="{{ $camp->roas }}"
                    data-gmv="{{ $camp->sum_broad_gmv + $camp->sum_direct_gmv }}"
                    style="border-bottom: 1px solid var(--dsh-border);">
                    <td style="padding: 0.75rem 0.5rem; max-width: 250px;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            @if($camp->campaign_status === 'ongoing')
                                <span style="display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px; background:rgba(22, 163, 74, 0.15); border-radius:50%;" title="Berjalan">
                                    <i class="bi bi-play-fill text-success" style="font-size: .8rem;"></i>
                                </span>
                                @if(!$isGmsCampaign)
                                <i class="bi bi-pause-circle-fill text-warning status-toggle-btn" style="cursor:pointer; font-size: 1rem; opacity: 0.5; transition: 0.2s; margin-left:-4px;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.5" title="Klik untuk Jeda" onclick="toggleCampaignStatus(this, '{{ $camp->channel_campaign_id }}', 'pause')"></i>
                                @endif
                            @elseif($camp->campaign_status === 'paused')
                                <span style="display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px; background:rgba(234, 179, 8, 0.15); border-radius:50%;" title="Jeda">
                                    <i class="bi bi-pause-fill text-warning" style="font-size: .8rem;"></i>
                                </span>
                                @if(!$isGmsCampaign)
                                <i class="bi bi-play-circle-fill text-success status-toggle-btn" style="cursor:pointer; font-size: 1rem; opacity: 0.5; transition: 0.2s; margin-left:-4px;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.5" title="Klik untuk Lanjut" onclick="toggleCampaignStatus(this, '{{ $camp->channel_campaign_id }}', 'resume')"></i>
                                @endif
                            @else
                                <span style="display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px; background:rgba(148, 163, 184, 0.15); border-radius:50%;" title="{{ ucfirst($camp->campaign_status) }}">
                                    <span style="display:inline-block; width:6px; height:6px; background:#94a3b8; border-radius:50%;"></span>
                                </span>
                            @endif
                            
                            <span style="font-weight: 700; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size:.85rem;" title="{{ $camp->campaign_name ?: 'Kampanye Tidak Ditemukan' }} (Camp ID: {{ $camp->channel_campaign_id }} | Item ID: {{ $camp->channel_item_id ?: '-' }})">
                                {{ $camp->campaign_name ?: 'Kampanye Tidak Ditemukan' }}
                            </span>
                            <span class="campaign-type-badge {{ $isGmsCampaign ? 'campaign-type-gms' : 'campaign-type-cpc' }}">
                                <i class="bi {{ $isGmsCampaign ? 'bi-stars' : 'bi-mouse2' }}"></i>
                                {{ $isGmsCampaign ? 'GMV Max' : 'CPC' }}
                            </span>
                        </div>
                        @if(($camp->channel_item_id ?? null) !== null)
                            <div style="font-size:.65rem; color:var(--dsh-muted); margin:-4px 0 7px 28px;">
                                HPP/unit: {{ ($camp->unit_cogs ?? 0) > 0 ? 'Rp ' . number_format($camp->unit_cogs, 0, ',', '.') : 'belum tersedia' }}
                            </div>
                        @endif

                        <div style="display:flex; align-items:center; gap:8px;">
                            <div class="inline-edit-wrapper" data-type="daily_budget" data-camp="{{ $camp->channel_campaign_id }}" data-campaign-kind="{{ $isGmsCampaign ? 'gms' : 'cpc' }}" data-val="{{ $camp->campaign_budget > 0 ? $camp->campaign_budget : 0 }}">
                                <div class="inline-edit-text config-pill" title="{{ $isGmsCampaign ? 'Edit budget GMV Max' : 'Edit budget CPC' }}" onclick="showInlineEdit(this)">
                                    <i class="bi bi-wallet2 text-muted"></i> 
                                    <span>{{ $camp->campaign_budget > 0 ? 'Rp ' . number_format($camp->campaign_budget, 0, ',', '.') : 'Unlimited' }}</span>
                                </div>
                                <div class="inline-edit-input" style="display:none; align-items:center; gap:4px; background:var(--bg); border:1px solid var(--dsh-border); border-radius:6px; padding:2px 4px;">
                                    <input type="number" class="form-control form-control-sm" style="width: 75px; height: 22px; font-size: .7rem; padding: 0 4px; border:none; background:transparent;" value="{{ $camp->campaign_budget > 0 ? $camp->campaign_budget : 0 }}">
                                    <i class="bi bi-check text-success" style="cursor:pointer; font-size:1.1rem; line-height:1;" onclick="saveInlineEdit(this)"></i>
                                    <i class="bi bi-x text-danger" style="cursor:pointer; font-size:1.1rem; line-height:1;" onclick="cancelInlineEdit(this)"></i>
                                </div>
                            </div>

                        </div>
                    </td>
                    
                    <td class="text-end" style="vertical-align: middle;">
                        <div style="font-weight: 600; color: var(--dsh-muted); font-variant-numeric: tabular-nums;">
                            {{ number_format($camp->sum_impressions ?? 0, 0, ',', '.') }}
                        </div>
                        @if($imp_growth != 0)
                            <div class="growth-badge {{ $imp_growth > 0 ? 'growth-up' : 'growth-down' }}">
                                {{ $imp_growth > 0 ? '▲' : '▼' }} {{ abs(round($imp_growth)) }}%
                            </div>
                        @endif
                    </td>
                    
                    <td class="text-end" style="vertical-align: middle;">
                        <div style="font-weight: 600; color: var(--dsh-muted); font-variant-numeric: tabular-nums;">
                            {{ number_format($camp->sum_clicks ?? 0, 0, ',', '.') }}
                        </div>
                        @if($click_growth != 0)
                            <div class="growth-badge {{ $click_growth > 0 ? 'growth-up' : 'growth-down' }}">
                                {{ $click_growth > 0 ? '▲' : '▼' }} {{ abs(round($click_growth)) }}%
                            </div>
                        @endif
                    </td>
                    
                    <td class="text-end" style="vertical-align: middle;">
                        <div style="font-weight: 700; color: #dc2626; font-variant-numeric: tabular-nums;">
                            Rp {{ number_format($camp->sum_expense ?? 0, 0, ',', '.') }}
                        </div>
                        @if($exp_growth != 0)
                            <div class="growth-badge {{ $exp_growth > 0 ? 'growth-down' : 'growth-up' }}">
                                {{ $exp_growth > 0 ? '▲' : '▼' }} {{ abs(round($exp_growth)) }}%
                            </div>
                        @endif
                    </td>
                    
                    <td class="text-end" style="vertical-align: middle;">
                        <div style="font-weight: 600; color: var(--text); font-variant-numeric: tabular-nums;">
                            {{ number_format($camp->sum_broad_orders ?? 0, 0, ',', '.') }}
                        </div>
                        @if($bo_growth != 0)
                            <div class="growth-badge {{ $bo_growth > 0 ? 'growth-up' : 'growth-down' }}">
                                {{ $bo_growth > 0 ? '▲' : '▼' }} {{ abs(round($bo_growth)) }}%
                            </div>
                        @endif
                    </td>
                    
                    <td class="text-end" style="vertical-align: middle;">
                        <div style="font-weight: 700; color: #16a34a; font-variant-numeric: tabular-nums;">
                            Rp {{ number_format($camp->sum_broad_gmv ?? 0, 0, ',', '.') }}
                        </div>
                        @if($bg_growth != 0)
                            <div class="growth-badge {{ $bg_growth > 0 ? 'growth-up' : 'growth-down' }}">
                                {{ $bg_growth > 0 ? '▲' : '▼' }} {{ abs(round($bg_growth)) }}%
                            </div>
                        @endif
                    </td>
                    
                    <td class="text-end" style="vertical-align: middle;">
                        <div style="font-weight: 600; color: var(--dsh-muted); font-variant-numeric: tabular-nums;">
                            {{ number_format($camp->sum_direct_orders ?? 0, 0, ',', '.') }}
                        </div>
                        @if($do_growth != 0)
                            <div class="growth-badge {{ $do_growth > 0 ? 'growth-up' : 'growth-down' }}">
                                {{ $do_growth > 0 ? '▲' : '▼' }} {{ abs(round($do_growth)) }}%
                            </div>
                        @endif
                    </td>
                    
                    <td class="text-end" style="vertical-align: middle;">
                        <div style="font-weight: 600; color: var(--dsh-muted); font-variant-numeric: tabular-nums;">
                            Rp {{ number_format($camp->sum_direct_gmv ?? 0, 0, ',', '.') }}
                        </div>
                        @if($dg_growth != 0)
                            <div class="growth-badge {{ $dg_growth > 0 ? 'growth-up' : 'growth-down' }}">
                                {{ $dg_growth > 0 ? '▲' : '▼' }} {{ abs(round($dg_growth)) }}%
                            </div>
                        @endif
                    </td>
                    
                    <td class="text-end" style="vertical-align: middle;">
                        <div style="font-weight: 600; color: #ca8a04; font-variant-numeric: tabular-nums;">
                            Rp {{ number_format($cpc, 0, ',', '.') }}
                        </div>
                    </td>

                    <td class="text-end" style="vertical-align: middle;">
                        <div class="inline-edit-wrapper d-inline-flex" data-type="roas_target" data-camp="{{ $camp->channel_campaign_id }}" data-campaign-kind="{{ $isGmsCampaign ? 'gms' : 'cpc' }}" data-val="{{ $camp->target_roas ?? 0 }}">
                            <div class="inline-edit-text config-pill config-pill-roas" title="{{ $isGmsCampaign ? 'Edit Target ROAS GMV Max' : 'Edit Target ROAS CPC' }}" onclick="showInlineEdit(this)">
                                <i class="bi bi-pencil-square" style="font-size:.65rem;"></i>
                                <span>{{ $camp->target_roas ? number_format($camp->target_roas, 2).'x' : 'Auto' }}</span>
                            </div>
                            <div class="inline-edit-input" style="display:none; align-items:center; gap:4px; background:var(--bg); border:1px solid var(--dsh-border); border-radius:6px; padding:2px 4px;">
                                    <input type="text" inputmode="decimal" class="form-control form-control-sm" style="width: 58px; height: 22px; font-size: .7rem; padding: 0 4px; border:none; background:transparent;" value="{{ $camp->target_roas ?? 0 }}">
                                <i class="bi bi-check text-success" style="cursor:pointer; font-size:1.1rem; line-height:1;" onclick="saveInlineEdit(this)"></i>
                                <i class="bi bi-x text-danger" style="cursor:pointer; font-size:1.1rem; line-height:1;" onclick="cancelInlineEdit(this)"></i>
                            </div>
                        </div>
                    </td>
                    
                    <td class="text-end" style="vertical-align: middle;">
                        <div style="font-weight: 700; color: #16a34a; font-variant-numeric: tabular-nums;">
                            {{ number_format($camp->roas, 2) }}x
                        </div>
                        @if($roas_growth != 0)
                            <div class="growth-badge {{ $roas_growth > 0 ? 'growth-up' : 'growth-down' }}">
                                {{ $roas_growth > 0 ? '▲' : '▼' }} {{ abs(round($roas_growth)) }}%
                            </div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center py-4" style="color: var(--dsh-muted); font-size: .8rem;">
                        Belum ada data kampanye.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
    let currentSortCol = null;
    let currentSortDir = 'desc';

    function sortCampaignTable(col, thElement) {
        if(currentSortCol === col) {
            currentSortDir = currentSortDir === 'desc' ? 'asc' : 'desc';
        } else {
            currentSortCol = col;
            currentSortDir = col === 'name' ? 'asc' : 'desc';
        }

        const allTh = thElement.parentNode.querySelectorAll('th');
        allTh.forEach(th => {
            const icon = th.querySelector('i');
            if(icon) {
                icon.className = 'bi bi-arrow-down-up ms-1';
                icon.style.opacity = '0.3';
            }
        });

        const activeIcon = thElement.querySelector('i');
        if(activeIcon) {
            if(col === 'name') {
                activeIcon.className = currentSortDir === 'asc' ? 'bi bi-sort-alpha-down ms-1 text-primary' : 'bi bi-sort-alpha-up-alt ms-1 text-primary';
            } else {
                activeIcon.className = currentSortDir === 'desc' ? 'bi bi-sort-down ms-1 text-primary' : 'bi bi-sort-up-alt ms-1 text-primary';
            }
            activeIcon.style.opacity = '1';
        }

        const tbody = thElement.closest('table').querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('.campaign-row'));

        rows.sort((a, b) => {
            let valA = a.getAttribute('data-' + col);
            let valB = b.getAttribute('data-' + col);
            
            if(col === 'name') {
                valA = valA.toLowerCase();
                valB = valB.toLowerCase();
                if(valA < valB) return currentSortDir === 'asc' ? -1 : 1;
                if(valA > valB) return currentSortDir === 'asc' ? 1 : -1;
                return 0;
            } else {
                valA = parseFloat(valA) || 0;
                valB = parseFloat(valB) || 0;
                return currentSortDir === 'desc' ? valB - valA : valA - valB;
            }
        });

        rows.forEach(row => tbody.appendChild(row));
    }
</script>
