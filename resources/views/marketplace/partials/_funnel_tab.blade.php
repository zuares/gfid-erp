@php
    $funnelImpressions = $kpi['current']->impressions ?? 0;
    $funnelClicks = $kpi['current']->clicks ?? 0;
    $funnelOrders = $kpi['current']->orders ?? 0;

    $ctr = $funnelImpressions > 0 ? ($funnelClicks / $funnelImpressions) * 100 : 0;
    $cvr = $funnelClicks > 0 ? ($funnelOrders / $funnelClicks) * 100 : 0;
    $overallConv = $funnelImpressions > 0 ? ($funnelOrders / $funnelImpressions) * 100 : 0;
    $impressionDropoff = max(0, min(100, 100 - $ctr));
    $clickDropoff = max(0, min(100, 100 - $cvr));

    // Drop-off Analysis: Products with high clicks but 0 orders (Clickbait / Pricing issue)
    $highDropoffProducts = $itemPerformance->filter(function($p) {
        return $p->clicks >= 50 && $p->orders == 0;
    })->sortByDesc('clicks')->take(10);

    // Drop-off Analysis: Campaigns with high impressions but very low CTR
    $lowCtrCampaigns = $campaigns->filter(function($c) {
        $campCtr = $c->impressions > 0 ? ($c->clicks / $c->impressions) * 100 : 0;
        return $c->impressions >= 1000 && $campCtr < 1.0;
    })->sortBy('clicks')->take(10); // Lowest clicks first
@endphp

<div class="dash-sec"><i class="bi bi-funnel"></i> Funnel Shopee Ads</div>
<div class="dpanel p-4 mb-4">
    <div class="row align-items-center">
        <!-- Funnel Visualization -->
        <div class="col-md-6 border-end pe-4">
            <h6 class="fw-bold mb-4">Keseluruhan Funnel (Top-to-Bottom)</h6>
            
            <!-- Step 1: Impressions -->
            <div class="mb-3 position-relative">
                <div class="d-flex justify-content-between mb-1">
                    <span class="fw-bold text-secondary"><i class="bi bi-eye"></i> Impressions</span>
                    <span class="fw-bold">{{ number_format($funnelImpressions, 0, ',', '.') }}</span>
                </div>
                <div class="progress" style="height: 25px; border-radius: 8px;">
                    <div class="progress-bar bg-secondary" role="progressbar" style="width: 100%;">100%</div>
                </div>
                <!-- Drop-off arrow -->
                <div class="text-center my-2 text-muted small">
                    <i class="bi bi-arrow-down"></i> Drop-off: {{ number_format($impressionDropoff, 2) }}% (Tidak klik iklan)
                </div>
            </div>

            <!-- Step 2: Clicks -->
            <div class="mb-3 position-relative">
                <div class="d-flex justify-content-between mb-1">
                    <span class="fw-bold text-primary"><i class="bi bi-hand-index-thumb"></i> Clicks (Kunjungan Produk)</span>
                    <span class="fw-bold">{{ number_format($funnelClicks, 0, ',', '.') }}</span>
                </div>
                <div class="progress" style="height: 25px; border-radius: 8px;">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $ctr > 0 ? max(5, $ctr) : 0 }}%;">
                        CTR: {{ number_format($ctr, 2) }}%
                    </div>
                </div>
                <!-- Drop-off arrow -->
                <div class="text-center my-2 text-muted small">
                    <i class="bi bi-arrow-down"></i> Drop-off: {{ number_format($clickDropoff, 2) }}% (Klik tapi tidak beli)
                </div>
            </div>

            <!-- Step 3: Purchases -->
            <div class="mb-3 position-relative">
                <div class="d-flex justify-content-between mb-1">
                    <span class="fw-bold text-success"><i class="bi bi-bag-check"></i> Purchases (Pesanan)</span>
                    <span class="fw-bold">{{ number_format($funnelOrders, 0, ',', '.') }}</span>
                </div>
                <div class="progress" style="height: 25px; border-radius: 8px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $cvr > 0 ? max(5, $cvr) : 0 }}%;">
                        CVR: {{ number_format($cvr, 2) }}%
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Summary Insights -->
        <div class="col-md-6 ps-4">
            <h6 class="fw-bold mb-3">Insight Konversi</h6>
            <div class="alert alert-info border-0 bg-light">
                <ul class="mb-0 ps-3">
                    <li class="mb-2">Secara keseluruhan, dari total impresi iklan, persentase orang yang pada akhirnya membeli adalah <strong>{{ number_format($overallConv, 2) }}%</strong>.</li>
                    @if($ctr < 1.5)
                        <li class="mb-2 text-warning fw-bold"><i class="bi bi-exclamation-triangle"></i> CTR ({{ number_format($ctr, 2) }}%) cukup rendah. Pertimbangkan memperbaiki Thumbnail Gambar Produk atau Judul.</li>
                    @else
                        <li class="mb-2 text-success"><i class="bi bi-check-circle"></i> CTR ({{ number_format($ctr, 2) }}%) berada di level yang baik. Iklan menarik perhatian audiens.</li>
                    @endif
                    
                    @if($cvr < 2.0)
                        <li class="mb-0 text-danger fw-bold"><i class="bi bi-exclamation-triangle"></i> CVR ({{ number_format($cvr, 2) }}%) mengindikasikan banyak orang klik tapi batal beli. Periksa Harga, Deskripsi, atau Review produk Anda.</li>
                    @else
                        <li class="mb-0 text-success"><i class="bi bi-check-circle"></i> Tingkat konversi (CVR {{ number_format($cvr, 2) }}%) sudah optimal.</li>
                    @endif
                </ul>
            </div>
            <div class="text-muted small mt-3">
                <i class="bi bi-info-circle"></i> <em>Catatan API: Shopee Ads API tidak menyediakan data Landing Page View, Add to Cart (ATC), Checkout, atau Device Breakdown secara spesifik dari interaksi iklan.</em>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Analisa Drop-off Produk -->
    <div class="col-md-6">
        <div class="dash-sec"><i class="bi bi-box"></i> Kebocoran Produk (Klik Tinggi, Nol Pesanan)</div>
        <div class="dpanel p-0 overflow-hidden h-100">
            <div class="p-3 border-bottom bg-light">
                <div class="small text-muted">Produk yang menyedot budget (Klik > 50) namun gagal menghasilkan konversi. Kemungkinan harga tidak kompetitif atau foto menipu (Clickbait).</div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th>SKU / Produk</th>
                            <th class="text-end">Klik</th>
                            <th class="text-end">Spend</th>
                            <th class="text-end">Pesanan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($highDropoffProducts as $p)
                            <tr>
                                <td>
                                    <div class="fw-bold text-truncate" style="max-width: 200px;" title="{{ $p->item_name }}">{{ $p->item_name }}</div>
                                    <div class="text-muted small">{{ $p->item_sku }}</div>
                                </td>
                                <td class="text-end fw-bold text-warning">{{ number_format($p->clicks, 0, ',', '.') }}</td>
                                <td class="text-end text-danger">Rp {{ number_format($p->spend, 0, ',', '.') }}</td>
                                <td class="text-end fw-bold text-danger">0</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">Tidak ditemukan produk dengan Drop-off parah.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Analisa Drop-off Campaign -->
    <div class="col-md-6">
        <div class="dash-sec"><i class="bi bi-megaphone"></i> Kebocoran Kampanye (Impresi Tinggi, CTR Rendah)</div>
        <div class="dpanel p-0 overflow-hidden h-100">
            <div class="p-3 border-bottom bg-light">
                <div class="small text-muted">Kampanye dengan impresi besar (> 1.000) namun CTR di bawah 1%. Iklan tayang ke audiens yang salah atau visual tidak menarik.</div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th>Nama Kampanye</th>
                            <th class="text-end">Impresi</th>
                            <th class="text-end">CTR</th>
                            <th class="text-end">Spend</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lowCtrCampaigns as $c)
                            @php
                                $campCtr = $c->impressions > 0 ? ($c->clicks / $c->impressions) * 100 : 0;
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-bold text-truncate" style="max-width: 200px;" title="{{ $c->campaign_name }}">{{ $c->campaign_name }}</div>
                                    <div class="text-muted small text-uppercase">{{ $c->ad_type }}</div>
                                </td>
                                <td class="text-end fw-bold">{{ number_format($c->impressions, 0, ',', '.') }}</td>
                                <td class="text-end fw-bold text-danger">{{ number_format($campCtr, 2) }}%</td>
                                <td class="text-end text-muted">Rp {{ number_format($c->spend, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">Semua kampanye memiliki CTR yang baik.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
