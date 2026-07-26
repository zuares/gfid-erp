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
<div class="table-responsive">
    <table class="dpanel-table dpanel-table-sm table-hover" style="white-space: nowrap;">
        <thead>
            <tr>
                <th>Kampanye</th>
                <th class="text-end" title="Total tayangan iklan">Impresi</th>
                <th class="text-end" title="Total klik iklan">Klik</th>
                <th class="text-end" title="Biaya Iklan">Pengeluaran</th>
                <th class="text-end" title="Pesanan Broad">Pesanan (B)</th>
                <th class="text-end" title="Pendapatan Broad">GMV (B)</th>
                <th class="text-end" title="Pesanan Direct">Pesanan (D)</th>
                <th class="text-end" title="Pendapatan Direct">GMV (D)</th>
                <th class="text-end" title="Biaya per klik">CPC</th>
                <th class="text-end" title="Return on Ad Spend">ROAS</th>
            </tr>
        </thead>
        <tbody>
            @forelse($campaigns as $camp)
                @php
                    $cpc = $camp->sum_clicks > 0 ? $camp->sum_expense / $camp->sum_clicks : 0;
                    
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
                <tr style="border-bottom: 1px solid var(--dsh-border);">
                    <td style="padding: 0.75rem 0.5rem; max-width: 250px;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            @if($camp->campaign_status === 'ongoing')
                                <span style="display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px; background:rgba(22, 163, 74, 0.15); border-radius:50%;" title="Berjalan">
                                    <i class="bi bi-play-fill text-success" style="font-size: .8rem;"></i>
                                </span>
                                <i class="bi bi-pause-circle-fill text-warning status-toggle-btn" style="cursor:pointer; font-size: 1rem; opacity: 0.5; transition: 0.2s; margin-left:-4px;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.5" title="Klik untuk Jeda" onclick="toggleCampaignStatus(this, '{{ $camp->channel_campaign_id }}', 'pause')"></i>
                            @elseif($camp->campaign_status === 'paused')
                                <span style="display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px; background:rgba(234, 179, 8, 0.15); border-radius:50%;" title="Jeda">
                                    <i class="bi bi-pause-fill text-warning" style="font-size: .8rem;"></i>
                                </span>
                                <i class="bi bi-play-circle-fill text-success status-toggle-btn" style="cursor:pointer; font-size: 1rem; opacity: 0.5; transition: 0.2s; margin-left:-4px;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.5" title="Klik untuk Lanjut" onclick="toggleCampaignStatus(this, '{{ $camp->channel_campaign_id }}', 'resume')"></i>
                            @else
                                <span style="display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px; background:rgba(148, 163, 184, 0.15); border-radius:50%;" title="{{ ucfirst($camp->campaign_status) }}">
                                    <span style="display:inline-block; width:6px; height:6px; background:#94a3b8; border-radius:50%;"></span>
                                </span>
                            @endif
                            
                            <span style="font-weight: 700; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size:.85rem;" title="{{ $camp->campaign_name ?: 'Kampanye Tidak Ditemukan' }} (Camp ID: {{ $camp->channel_campaign_id }} | Item ID: {{ $camp->channel_item_id ?: '-' }})">
                                {{ $camp->campaign_name ?: 'Kampanye Tidak Ditemukan' }}
                            </span>
                        </div>

                        <div style="display:flex; align-items:center; gap:8px;">
                            <div class="inline-edit-wrapper" data-type="daily_budget" data-camp="{{ $camp->channel_campaign_id }}" data-val="{{ $camp->campaign_budget > 0 ? $camp->campaign_budget : 0 }}">
                                <div class="inline-edit-text config-pill" title="Klik untuk edit" onclick="showInlineEdit(this)">
                                    <i class="bi bi-wallet2 text-muted"></i> 
                                    <span>{{ $camp->campaign_budget > 0 ? 'Rp ' . number_format($camp->campaign_budget, 0, ',', '.') : 'Unlimited' }}</span>
                                </div>
                                <div class="inline-edit-input" style="display:none; align-items:center; gap:4px; background:var(--bg); border:1px solid var(--dsh-border); border-radius:6px; padding:2px 4px;">
                                    <input type="number" class="form-control form-control-sm" style="width: 75px; height: 22px; font-size: .7rem; padding: 0 4px; border:none; background:transparent;" value="{{ $camp->campaign_budget > 0 ? $camp->campaign_budget : 0 }}">
                                    <i class="bi bi-check text-success" style="cursor:pointer; font-size:1.1rem; line-height:1;" onclick="saveInlineEdit(this)"></i>
                                    <i class="bi bi-x text-danger" style="cursor:pointer; font-size:1.1rem; line-height:1;" onclick="cancelInlineEdit(this)"></i>
                                </div>
                            </div>

                            <div class="inline-edit-wrapper" data-type="roas_target" data-camp="{{ $camp->channel_campaign_id }}" data-val="{{ $camp->target_roas ?? 0 }}">
                                <div class="inline-edit-text config-pill" title="Klik untuk edit" onclick="showInlineEdit(this)">
                                    <i class="bi bi-bullseye text-muted"></i> 
                                    <span>{{ $camp->target_roas ? number_format($camp->target_roas, 2).'x' : 'Auto' }}</span>
                                </div>
                                <div class="inline-edit-input" style="display:none; align-items:center; gap:4px; background:var(--bg); border:1px solid var(--dsh-border); border-radius:6px; padding:2px 4px;">
                                    <input type="number" step="0.1" class="form-control form-control-sm" style="width: 50px; height: 22px; font-size: .7rem; padding: 0 4px; border:none; background:transparent;" value="{{ $camp->target_roas ?? 0 }}">
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
                    <td colspan="10" class="text-center py-4" style="color: var(--dsh-muted); font-size: .8rem;">
                        Belum ada data kampanye.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>