<div class="table-responsive">
    <table class="dpanel-table dpanel-table-sm table-hover" style="white-space: nowrap;">
        <thead>
            <tr>
                <th>Produk</th>
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
            @forelse($gmsItems as $item)
                @php
                    $imp_growth = $item->prev_impression > 0 ? (($item->impression - $item->prev_impression) / $item->prev_impression) * 100 : ($item->impression > 0 ? 100 : 0);
                    $click_growth = $item->prev_click > 0 ? (($item->click - $item->prev_click) / $item->prev_click) * 100 : ($item->click > 0 ? 100 : 0);
                    $exp_growth = $item->prev_expense > 0 ? (($item->expense - $item->prev_expense) / $item->prev_expense) * 100 : ($item->expense > 0 ? 100 : 0);
                    $bo_growth = $item->prev_broad_order > 0 ? (($item->broad_order - $item->prev_broad_order) / $item->prev_broad_order) * 100 : ($item->broad_order > 0 ? 100 : 0);
                    $bg_growth = $item->prev_broad_gmv > 0 ? (($item->broad_gmv - $item->prev_broad_gmv) / $item->prev_broad_gmv) * 100 : ($item->broad_gmv > 0 ? 100 : 0);
                    $do_growth = $item->prev_direct_order > 0 ? (($item->direct_order - $item->prev_direct_order) / $item->prev_direct_order) * 100 : ($item->direct_order > 0 ? 100 : 0);
                    $dg_growth = $item->prev_direct_gmv > 0 ? (($item->direct_gmv - $item->prev_direct_gmv) / $item->prev_direct_gmv) * 100 : ($item->direct_gmv > 0 ? 100 : 0);
                    
                    $roas = $item->expense > 0 ? $item->broad_gmv / $item->expense : 0;
                    $prev_roas = $item->prev_expense > 0 ? $item->prev_broad_gmv / $item->prev_expense : 0;
                    $roas_growth = $prev_roas > 0 ? (($roas - $prev_roas) / $prev_roas) * 100 : ($roas > 0 ? 100 : 0);
                    $cpc = $item->click > 0 ? $item->expense / $item->click : 0;
                @endphp
                <tr style="border-bottom: 1px solid var(--dsh-border);">
                    <td style="padding: 0.75rem 0.5rem; max-width: 250px;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            @if($item->campaign_status === 'ongoing')
                                <span style="display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px; background:rgba(22, 163, 74, 0.15); border-radius:50%;" title="Berjalan">
                                    <i class="bi bi-play-fill text-success" style="font-size: .8rem;"></i>
                                </span>
                            @elseif($item->campaign_status === 'paused')
                                <span style="display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px; background:rgba(234, 179, 8, 0.15); border-radius:50%;" title="Jeda">
                                    <i class="bi bi-pause-fill text-warning" style="font-size: .8rem;"></i>
                                </span>
                            @else
                                <span style="display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px; background:rgba(148, 163, 184, 0.15); border-radius:50%;" title="{{ ucfirst($item->campaign_status) }}">
                                    <span style="display:inline-block; width:6px; height:6px; background:#94a3b8; border-radius:50%;"></span>
                                </span>
                            @endif
                            
                            <span style="font-weight: 700; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size:.85rem;" title="{{ $item->item_name ?: 'Produk Tidak Ditemukan' }} (Camp ID: {{ $item->channel_campaign_id ?: '-' }} | Item ID: {{ $item->channel_item_id }})">
                                {{ $item->item_name ?: 'Produk Tidak Ditemukan' }}
                            </span>
                        </div>

                        <div style="display:flex; align-items:center; gap:8px;">
                            <div class="config-pill" style="cursor:default;" title="Batas Modal Harian">
                                <i class="bi bi-wallet2 text-muted"></i> 
                                <span>{{ $item->campaign_budget > 0 ? 'Rp ' . number_format($item->campaign_budget, 0, ',', '.') : 'Unlimited' }}</span>
                            </div>

                            <div class="config-pill" style="cursor:default;" title="Target ROAS">
                                <i class="bi bi-bullseye text-muted"></i> 
                                <span>{{ $item->target_roas ? number_format($item->target_roas, 2).'x' : 'Auto' }}</span>
                            </div>
                        </div>
                    </td>
                    
                    <td class="text-end" style="vertical-align: middle;">
                        <div style="font-weight: 600; color: var(--dsh-muted); font-variant-numeric: tabular-nums;">
                            {{ number_format($item->impression, 0, ',', '.') }}
                        </div>
                        @if($imp_growth != 0)
                            <div class="growth-badge {{ $imp_growth > 0 ? 'growth-up' : 'growth-down' }}">
                                {{ $imp_growth > 0 ? '▲' : '▼' }} {{ abs(round($imp_growth)) }}%
                            </div>
                        @endif
                    </td>
                    
                    <td class="text-end" style="vertical-align: middle;">
                        <div style="font-weight: 600; color: var(--dsh-muted); font-variant-numeric: tabular-nums;">
                            {{ number_format($item->click, 0, ',', '.') }}
                        </div>
                        @if($click_growth != 0)
                            <div class="growth-badge {{ $click_growth > 0 ? 'growth-up' : 'growth-down' }}">
                                {{ $click_growth > 0 ? '▲' : '▼' }} {{ abs(round($click_growth)) }}%
                            </div>
                        @endif
                    </td>
                    
                    <td class="text-end" style="vertical-align: middle;">
                        <div style="font-weight: 700; color: #dc2626; font-variant-numeric: tabular-nums;">
                            Rp {{ number_format($item->expense, 0, ',', '.') }}
                        </div>
                        @if($exp_growth != 0)
                            <div class="growth-badge {{ $exp_growth > 0 ? 'growth-down' : 'growth-up' }}">
                                {{ $exp_growth > 0 ? '▲' : '▼' }} {{ abs(round($exp_growth)) }}%
                            </div>
                        @endif
                    </td>
                    
                    <td class="text-end" style="vertical-align: middle;">
                        <div style="font-weight: 600; color: var(--text); font-variant-numeric: tabular-nums;">
                            {{ number_format($item->broad_order, 0, ',', '.') }}
                        </div>
                        @if($bo_growth != 0)
                            <div class="growth-badge {{ $bo_growth > 0 ? 'growth-up' : 'growth-down' }}">
                                {{ $bo_growth > 0 ? '▲' : '▼' }} {{ abs(round($bo_growth)) }}%
                            </div>
                        @endif
                    </td>
                    
                    <td class="text-end" style="vertical-align: middle;">
                        <div style="font-weight: 700; color: #16a34a; font-variant-numeric: tabular-nums;">
                            Rp {{ number_format($item->broad_gmv, 0, ',', '.') }}
                        </div>
                        @if($bg_growth != 0)
                            <div class="growth-badge {{ $bg_growth > 0 ? 'growth-up' : 'growth-down' }}">
                                {{ $bg_growth > 0 ? '▲' : '▼' }} {{ abs(round($bg_growth)) }}%
                            </div>
                        @endif
                    </td>
                    
                    <td class="text-end" style="vertical-align: middle;">
                        <div style="font-weight: 600; color: var(--dsh-muted); font-variant-numeric: tabular-nums;">
                            {{ number_format($item->direct_order, 0, ',', '.') }}
                        </div>
                        @if($do_growth != 0)
                            <div class="growth-badge {{ $do_growth > 0 ? 'growth-up' : 'growth-down' }}">
                                {{ $do_growth > 0 ? '▲' : '▼' }} {{ abs(round($do_growth)) }}%
                            </div>
                        @endif
                    </td>
                    
                    <td class="text-end" style="vertical-align: middle;">
                        <div style="font-weight: 600; color: var(--dsh-muted); font-variant-numeric: tabular-nums;">
                            Rp {{ number_format($item->direct_gmv, 0, ',', '.') }}
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
                            {{ number_format($roas, 2) }}x
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
                        Belum ada data GMS Maksimal untuk periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>