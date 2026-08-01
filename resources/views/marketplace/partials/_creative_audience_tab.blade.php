@php
    // --- AUDIENCE INTENT ANALYSIS ---
    // Aggregate by ad_type
    $audienceIntent = [
        'search' => ['name' => 'Search Ads (High Intent)', 'impressions' => 0, 'clicks' => 0, 'spend' => 0, 'orders' => 0, 'gmv' => 0, 'profit' => 0, 'profit_known' => 0, 'color' => 'primary'],
        'discovery' => ['name' => 'Discovery Ads (Prospecting)', 'impressions' => 0, 'clicks' => 0, 'spend' => 0, 'orders' => 0, 'gmv' => 0, 'profit' => 0, 'profit_known' => 0, 'color' => 'info'],
        'auto' => ['name' => 'Auto / GMS', 'impressions' => 0, 'clicks' => 0, 'spend' => 0, 'orders' => 0, 'gmv' => 0, 'profit' => 0, 'profit_known' => 0, 'color' => 'success'],
        'other' => ['name' => 'Lainnya', 'impressions' => 0, 'clicks' => 0, 'spend' => 0, 'orders' => 0, 'gmv' => 0, 'profit' => 0, 'profit_known' => 0, 'color' => 'secondary']
    ];

    foreach ($campaigns as $camp) {
        $type = strtolower($camp->ad_type ?? '');
        $placement = strtolower($camp->campaign_placement ?? '');
        $key = 'other';
        if (str_contains($placement, 'search') || str_contains($type, 'search')) $key = 'search';
        elseif (str_contains($placement, 'discovery') || str_contains($type, 'discovery') || str_contains($type, 'target')) $key = 'discovery';
        elseif (str_contains($type, 'auto') || str_contains($camp->channel_campaign_id, 'GMS')) $key = 'auto';

        $audienceIntent[$key]['impressions'] += $camp->impressions;
        $audienceIntent[$key]['clicks'] += $camp->clicks;
        $audienceIntent[$key]['spend'] += $camp->spend;
        $audienceIntent[$key]['orders'] += $camp->orders;
        $audienceIntent[$key]['gmv'] += $camp->gmv;
        if ($camp->profit_after_ads !== null) {
            $audienceIntent[$key]['profit'] += $camp->profit_after_ads;
            $audienceIntent[$key]['profit_known']++;
        }
    }

    // Filter out types with no spend/impressions
    $audienceIntent = array_filter($audienceIntent, fn($a) => $a['impressions'] > 0 || $a['spend'] > 0);

    // --- CREATIVE ANALYSIS ---
    // Filter items with at least 50 impressions
    $validItems = $itemPerformance->filter(fn($p) => $p->impressions >= 50);

    // Top CTR (The "Hook/Thumbnail" Winners)
    $topCtrItems = $validItems->sortByDesc('ctr')->take(6);

    // High CTR but Loss Maker (The "Clickbait" or Price/Copy Losers)
    $clickbaitItems = $validItems->filter(fn($p) => $p->ctr > 2.0 && $p->profit_after_ads !== null && $p->profit_after_ads < 0)->sortBy('profit_after_ads')->take(6);
@endphp

<div class="dash-sec"><i class="bi bi-people"></i> Analisa Audience Intent</div>
<div class="dpanel p-0 overflow-hidden mb-4">
    <div class="p-3 border-bottom bg-light">
    <div class="small text-muted">Karena Shopee API tidak memberikan data demografi audiens secara spesifik, segmentasi didekati melalui <strong>Placement/Tipe Iklan</strong>. Search mewakili niat beli tinggi, sedangkan Discovery mewakili <em>prospecting</em>. Profit hanya dihitung jika HPP tersedia.</div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
            <thead class="table-light">
                <tr>
                    <th>Segmen Audiens (Tipe Iklan)</th>
                    <th class="text-end">Impresi</th>
                    <th class="text-end">Klik</th>
                    <th class="text-end">CTR</th>
                    <th class="text-end">Spend</th>
                    <th class="text-end">Pesanan</th>
                    <th class="text-end">CVR</th>
                    <th class="text-end">Net Profit</th>
                    <th class="text-end">POAS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($audienceIntent as $key => $data)
                    @php
                        $ctr = $data['impressions'] > 0 ? ($data['clicks'] / $data['impressions']) * 100 : 0;
                        $cvr = $data['clicks'] > 0 ? ($data['orders'] / $data['clicks']) * 100 : 0;
                        $poas = $data['profit_known'] > 0 && $data['spend'] > 0 ? ($data['profit'] / ($data['spend'] * 1.11)) : null;
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-bold"><span class="badge bg-{{ $data['color'] }} me-2">{{ strtoupper($key) }}</span> {{ $data['name'] }}</div>
                        </td>
                        <td class="text-end">{{ number_format($data['impressions'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($data['clicks'], 0, ',', '.') }}</td>
                        <td class="text-end fw-bold">{{ number_format($ctr, 2) }}%</td>
                        <td class="text-end text-danger">Rp {{ number_format($data['spend'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($data['orders'], 0, ',', '.') }}</td>
                        <td class="text-end fw-bold">{{ number_format($cvr, 2) }}%</td>
                        <td class="text-end fw-bold {{ $data['profit_known'] === 0 ? 'text-muted' : ($data['profit'] >= 0 ? 'text-success' : 'text-danger') }}">
                            {{ $data['profit_known'] === 0 ? 'N/A' : 'Rp ' . number_format($data['profit'], 0, ',', '.') }}
                        </td>
                        <td class="text-end fw-bold">{{ $poas === null ? 'N/A' : number_format($poas, 2) . 'x' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">Belum ada data iklan untuk dianalisis.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="dash-sec"><i class="bi bi-image"></i> Analisa Creative (Visual Produk)</div>
<div class="alert alert-info border-0 bg-light mb-4 text-dark" style="font-size: 0.85rem;">
    <i class="bi bi-info-circle-fill me-2"></i> Pemenang iklan <strong>bukanlah</strong> yang CTR-nya paling tinggi, melainkan yang menghasilkan profit maksimal. Produk dengan gambar super menarik (CTR tinggi) seringkali menjadi *loss maker* (rugi) jika harga atau deskripsinya tidak disesuaikan dengan ekspektasi visualnya.
</div>

<div class="row g-3">
    <!-- TOP CTR Winners -->
    <div class="col-md-6">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-bold mb-0 text-success"><i class="bi bi-trophy"></i> Pemenang Hook Visual (CTR Tertinggi)</h6>
        </div>
        <div class="row g-2">
            @forelse($topCtrItems as $item)
                <div class="col-6 col-sm-4">
                    <div class="card h-100 border-0 shadow-sm overflow-hidden" style="border-radius: 12px; background: var(--card-bg);">
                        <div style="position: relative; padding-top: 100%; background: #f8f9fa;">
                            @if($item->image_url)
                                <img src="{{ $item->image_url }}" alt="Creative" style="position: absolute; top:0; left:0; width:100%; height:100%; object-fit: cover;">
                            @else
                                <div style="position: absolute; top:0; left:0; width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:#adb5bd;">
                                    <i class="bi bi-image fs-1"></i>
                                </div>
                            @endif
                            <div style="position: absolute; top: 8px; right: 8px; background: rgba(0,0,0,0.7); color: #fff; padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 0.7rem;">
                                CTR: {{ number_format($item->ctr, 2) }}%
                            </div>
                        </div>
                        <div class="p-2">
                            <div class="text-truncate small fw-bold" title="{{ $item->item_name }}">{{ $item->item_name }}</div>
                            <div class="text-muted" style="font-size:.65rem;">HPP/unit: {{ ($item->unit_cogs ?? 0) > 0 ? 'Rp ' . number_format($item->unit_cogs, 0, ',', '.') : 'belum tersedia' }}</div>
                            <div class="d-flex justify-content-between mt-1" style="font-size: 0.7rem;">
                                <span class="text-muted">CVR: {{ number_format($item->cvr, 2) }}%</span>
                                <span class="{{ $item->profit_after_ads === null ? 'text-muted' : ($item->profit_after_ads >= 0 ? 'text-success' : 'text-danger') }} fw-bold">
                                    {{ $item->profit_after_ads === null ? 'HPP?' : (($item->profit_after_ads >= 0 ? '+' : '') . number_format($item->profit_after_ads / 1000, 0) . 'k') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-4 text-muted small">Data belum cukup.</div>
            @endforelse
        </div>
    </div>

    <!-- CLICKBAIT (High CTR, Loss Maker) -->
    <div class="col-md-6">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-bold mb-0 text-danger"><i class="bi bi-exclamation-octagon"></i> "Clickbait" Loss Makers</h6>
        </div>
        <div class="row g-2">
            @forelse($clickbaitItems as $item)
                <div class="col-6 col-sm-4">
                    <div class="card h-100 border-0 shadow-sm overflow-hidden" style="border-radius: 12px; background: var(--card-bg); border-top: 3px solid #dc3545 !important;">
                        <div style="position: relative; padding-top: 100%; background: #f8f9fa;">
                            @if($item->image_url)
                                <img src="{{ $item->image_url }}" alt="Creative" style="position: absolute; top:0; left:0; width:100%; height:100%; object-fit: cover;">
                            @else
                                <div style="position: absolute; top:0; left:0; width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:#adb5bd;">
                                    <i class="bi bi-image fs-1"></i>
                                </div>
                            @endif
                            <div style="position: absolute; top: 8px; right: 8px; background: rgba(220, 53, 69, 0.9); color: #fff; padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 0.7rem;">
                                CTR: {{ number_format($item->ctr, 2) }}%
                            </div>
                        </div>
                        <div class="p-2">
                            <div class="text-truncate small fw-bold" title="{{ $item->item_name }}">{{ $item->item_name }}</div>
                            <div class="text-muted" style="font-size:.65rem;">HPP/unit: {{ ($item->unit_cogs ?? 0) > 0 ? 'Rp ' . number_format($item->unit_cogs, 0, ',', '.') : 'belum tersedia' }}</div>
                            <div class="d-flex justify-content-between mt-1" style="font-size: 0.7rem;">
                                <span class="text-muted">Spend: {{ number_format($item->spend / 1000, 0) }}k</span>
                                <span class="text-danger fw-bold">
                                    {{ number_format($item->profit_after_ads / 1000, 0) }}k
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-4 text-muted small">Bagus! Tidak ditemukan indikasi Iklan Clickbait / Loss Maker yang merugikan.</div>
            @endforelse
        </div>
    </div>
</div>
