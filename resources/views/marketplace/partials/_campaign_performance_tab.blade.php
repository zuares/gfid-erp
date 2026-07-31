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

    foreach ($campaigns as $camp) {
        if (!($camp->spend > 0 || $camp->gmv > 0)) continue;

        // Metrik Dasar
        $spend = (float) $camp->spend;
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

        // Profit & POAS
        $profit = (float) ($camp->profit_after_ads ?? 0);
        $totalProfit += $profit;
        $poas = $spend > 0 ? $profit / $spend : 0;

        // Recommendation Logic
        $reco = 'Review';
        $recoColor = 'secondary';
        
        if ($profit > 0) {
            if ($poas > 0.2) { // Profit 20% of spend is good enough to scale
                $reco = 'Scale';
                $recoColor = 'success';
            } else {
                $reco = 'Maintain';
                $recoColor = 'info';
            }
        } else {
            if ($clicks > 50 || $spend > 20000) {
                if ($cvr < 1) {
                    $reco = 'Optimize'; // Traffic is there, but no conversion
                    $recoColor = 'warning';
                } else {
                    $reco = 'Pause'; // Spending but negative profit
                    $recoColor = 'danger';
                }
            } else {
                $reco = 'Review'; // Not enough data
                $recoColor = 'secondary';
            }
        }

        $perfRows->push([
            'id' => $camp->channel_campaign_id,
            'name' => $camp->campaign_name,
            'type' => $camp->ad_type,
            'status' => $camp->campaign_status,
            'spend' => $spend,
            'gmv' => $gmv,
            'orders' => $orders,
            'clicks' => $clicks,
            'impressions' => $impressions,
            'cpc' => $cpc,
            'ctr' => $ctr,
            'cvr' => $cvr,
            'roas' => $roas,
            'cpa' => $cpa,
            'profit' => $profit,
            'poas' => $poas,
            'reco' => $reco,
            'recoColor' => $recoColor,
            'item_name' => $camp->internalItem ? $camp->internalItem->name : 'N/A'
        ]);
    }
    
    // Sort by Spend Descending by default
    $perfRows = $perfRows->sortByDesc('spend')->values();

    $scatterDataJson = json_encode($perfRows->map(function($r) {
        return [
            'x' => $r['spend'],
            'y' => $r['profit'],
            'name' => $r['name'],
            'reco' => $r['reco'],
            'r' => max(4, min(15, $r['orders'] * 2)) // Bubble size based on orders
        ];
    })->values());
@endphp

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="dpanel p-3 h-100">
            <div class="text-muted small fw-bold text-uppercase mb-1">Total Spend</div>
            <div class="fs-4 fw-bolder text-dark">Rp {{ number_format($totalSpend, 0, ',', '.') }}</div>
            <div class="small text-muted mt-1"><i class="bi bi-bullseye"></i> Ad Budget Used</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dpanel p-3 h-100">
            <div class="text-muted small fw-bold text-uppercase mb-1">Total Orders (Ads)</div>
            <div class="fs-4 fw-bolder text-dark">{{ number_format($totalOrders, 0, ',', '.') }}</div>
            <div class="small text-muted mt-1">CPA: Rp {{ number_format($totalOrders > 0 ? $totalSpend / $totalOrders : 0, 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dpanel p-3 h-100">
            <div class="text-muted small fw-bold text-uppercase mb-1">Total GMV (Ads)</div>
            <div class="fs-4 fw-bolder text-dark">Rp {{ number_format($totalGmv, 0, ',', '.') }}</div>
            <div class="small text-muted mt-1">ROAS: {{ number_format($totalSpend > 0 ? $totalGmv / $totalSpend : 0, 2) }}x</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dpanel p-3 h-100">
            <div class="text-muted small fw-bold text-uppercase mb-1" data-bs-toggle="tooltip" title="Estimasi profit bersih setelah dikurangi HPP, Ad Spend, dan Potongan Marketplace">Est. Net Profit</div>
            <div class="fs-4 fw-bolder {{ $totalProfit >= 0 ? 'text-success' : 'text-danger' }}">
                Rp {{ number_format($totalProfit, 0, ',', '.') }}
            </div>
            <div class="small text-muted mt-1">POAS: {{ number_format($totalSpend > 0 ? $totalProfit / $totalSpend : 0, 2) }}x</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Scatter Chart (Spend vs Profit) -->
    <div class="col-12 col-xl-12">
        <div class="dpanel p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-graph-up text-primary me-2"></i>Scatter: Spend vs Est. Profit</h6>
                <span class="badge bg-light text-dark border">Kuadran Atas-Kanan = Potensial Scale</span>
            </div>
            <div style="height: 300px; position: relative;">
                <canvas id="campaignScatterChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="dpanel p-0 overflow-hidden">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
        <h6 class="mb-0 fw-bold"><i class="bi bi-list-nested text-primary me-2"></i>Tabel Performa Campaign</h6>
        <div>
            <span class="badge bg-success">Scale</span>
            <span class="badge bg-info">Maintain</span>
            <span class="badge bg-warning text-dark">Optimize</span>
            <span class="badge bg-danger">Pause</span>
        </div>
    </div>
    <div class="table-responsive" style="max-height: 600px;">
        <table class="table table-hover align-middle mb-0 text-nowrap" style="font-size: 0.85rem;">
            <thead class="table-light sticky-top" style="z-index: 2;">
                <tr>
                    <th>Campaign / Item</th>
                    <th>Status</th>
                    <th class="text-end">Spend</th>
                    <th class="text-end">Impressions</th>
                    <th class="text-end">Clicks</th>
                    <th class="text-end" data-bs-toggle="tooltip" title="Click-Through Rate">CTR</th>
                    <th class="text-end" data-bs-toggle="tooltip" title="Cost Per Click">CPC</th>
                    <th class="text-end">Orders</th>
                    <th class="text-end" data-bs-toggle="tooltip" title="Conversion Rate (Orders/Clicks)">CVR</th>
                    <th class="text-end">GMV</th>
                    <th class="text-end" data-bs-toggle="tooltip" title="Cost Per Acquisition">CPA</th>
                    <th class="text-end">ROAS</th>
                    <th class="text-end" data-bs-toggle="tooltip" title="Estimated Net Profit">Est. Profit</th>
                    <th class="text-end" data-bs-toggle="tooltip" title="Profit on Ad Spend">POAS</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($perfRows as $row)
                    <tr>
                        <td>
                            <div class="fw-bold text-truncate" style="max-width: 200px;" title="{{ $row['name'] }}">
                                {{ $row['name'] }}
                            </div>
                            <div class="text-muted small text-truncate" style="max-width: 200px;">
                                <i class="bi bi-box"></i> {{ $row['item_name'] }}
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-{{ $row['status'] === 'ongoing' ? 'success' : 'secondary' }}">
                                {{ $row['status'] }}
                            </span>
                            <br>
                            <span class="badge bg-{{ $row['recoColor'] }} mt-1">{{ $row['reco'] }}</span>
                        </td>
                        <td class="text-end fw-bold">Rp {{ number_format($row['spend'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($row['impressions'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($row['clicks'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($row['ctr'], 2) }}%</td>
                        <td class="text-end">Rp {{ number_format($row['cpc'], 0, ',', '.') }}</td>
                        <td class="text-end fw-bold">{{ number_format($row['orders'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($row['cvr'], 2) }}%</td>
                        <td class="text-end text-success fw-bold">Rp {{ number_format($row['gmv'], 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format($row['cpa'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($row['roas'], 2) }}x</td>
                        <td class="text-end fw-bold {{ $row['profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                            Rp {{ number_format($row['profit'], 0, ',', '.') }}
                        </td>
                        <td class="text-end">{{ number_format($row['poas'], 2) }}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-light border" onclick="alert('Drill-down insight detail campaign {{ $row['id'] }}')">
                                <i class="bi bi-search"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="15" class="text-center py-4 text-muted">Belum ada data performa campaign yang memadai.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const scatterData = {!! $scatterDataJson !!};

    const ctx = document.getElementById('campaignScatterChart');
    if (!ctx) return;

    // Define colors for recommendations
    const colorMap = {
        'Scale': 'rgba(25, 135, 84, 0.7)',     // Success
        'Maintain': 'rgba(13, 202, 240, 0.7)',  // Info
        'Optimize': 'rgba(255, 193, 7, 0.7)',   // Warning
        'Pause': 'rgba(220, 53, 69, 0.7)',      // Danger
        'Review': 'rgba(108, 117, 125, 0.7)'    // Secondary
    };

    const datasets = Object.keys(colorMap).map(reco => {
        return {
            label: reco,
            data: scatterData.filter(d => d.reco === reco),
            backgroundColor: colorMap[reco],
            borderColor: colorMap[reco].replace('0.7', '1'),
            borderWidth: 1
        };
    });

    new Chart(ctx, {
        type: 'bubble',
        data: {
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const data = context.raw;
                            return [
                                data.name,
                                'Spend: Rp ' + data.x.toLocaleString('id-ID'),
                                'Profit: Rp ' + data.y.toLocaleString('id-ID')
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
                    title: { display: true, text: 'Ad Spend (Rp)' },
                    ticks: { callback: function(value) { return 'Rp ' + (value/1000).toLocaleString('id-ID') + 'k'; } }
                },
                y: {
                    title: { display: true, text: 'Est. Net Profit (Rp)' },
                    ticks: { callback: function(value) { return 'Rp ' + (value/1000).toLocaleString('id-ID') + 'k'; } },
                    grid: { color: function(context) { return context.tick.value === 0 ? '#ff0000' : 'rgba(0,0,0,0.1)'; } }
                }
            }
        }
    });
});
</script>
