@php
    $totalActiveProducts = $itemPerformance->count();
    $totalStock = $itemPerformance->sum('stock_total');
    $knownProfitItems = $itemPerformance->filter(fn ($r) => $r->profit_after_ads !== null);
    $totalProfit = $knownProfitItems->sum('profit_after_ads');
    $totalGrossProfit = $knownProfitItems->sum('gross_profit');
    $unknownProfitItems = $itemPerformance->count() - $knownProfitItems->count();
    $totalAdSpend = $itemPerformance->sum('spend');
    $knownAdSpend = $knownProfitItems->sum('spend');
    $totalOrders = $itemPerformance->sum('orders');
    $totalGmv = $itemPerformance->sum('gmv');

    $scatterDataJson = json_encode($itemPerformance->map(function($r) {
        return [
            'x' => $r->spend,
            'y' => $r->profit_after_ads,
            'name' => $r->item_name,
            'reco' => $r->classification,
            'r' => max(4, min(15, $r->orders * 2)) // Bubble size based on orders
        ];
    })->values());
@endphp

<div class="dash-sec"><i class="bi bi-robot"></i> Insight Produk</div>
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="dpanel p-3 h-100">
            <div class="text-muted small fw-bold text-uppercase mb-1">Total Produk Iklan</div>
            <div class="fs-4 fw-bolder text-dark">{{ number_format($totalActiveProducts, 0, ',', '.') }}</div>
            <div class="small text-muted mt-1"><i class="bi bi-box"></i> SKU unik</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dpanel p-3 h-100">
            <div class="text-muted small fw-bold text-uppercase mb-1">Stok Tersedia</div>
            <div class="fs-4 fw-bolder text-dark">{{ number_format($totalStock, 0, ',', '.') }}</div>
            <div class="small text-muted mt-1">Total stok tercatat</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dpanel p-3 h-100">
            <div class="text-muted small fw-bold text-uppercase mb-1">Gross Profit (Iklan)</div>
            <div class="fs-4 fw-bolder text-dark">Rp {{ number_format($totalGrossProfit, 0, ',', '.') }}</div>
            <div class="small text-muted mt-1">Terhitung dari {{ $knownProfitItems->count() }} produk ber-HPP</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dpanel p-3 h-100">
            <div class="text-muted small fw-bold text-uppercase mb-1" data-bs-toggle="tooltip" title="Profit hanya dijumlahkan untuk produk yang memiliki HPP">Net Profit Terhitung</div>
            <div class="fs-4 fw-bolder {{ $unknownProfitItems > 0 ? 'text-warning' : ($totalProfit >= 0 ? 'text-success' : 'text-danger') }}">
                Rp {{ number_format($totalProfit, 0, ',', '.') }}
            </div>
            <div class="small text-muted mt-1">POAS: {{ number_format($knownAdSpend > 0 ? $totalProfit / ($knownAdSpend * 1.11) : 0, 2) }}x · {{ $unknownProfitItems }} belum ada HPP</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Scatter Chart (Spend vs Profit) -->
    <div class="col-12 col-xl-12">
        <div class="dpanel p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-graph-up text-primary me-2"></i>Scatter Produk: Spend vs Est. Profit</h6>
                <span class="badge bg-light text-dark border">Hero Product = Profitabilitas Tinggi & Budget Cukup</span>
            </div>
            <div style="height: 300px; position: relative;">
                <canvas id="productScatterChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="dpanel p-0 overflow-hidden">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
        <h6 class="mb-0 fw-bold"><i class="bi bi-box-seam text-primary me-2"></i>Semua Produk Diiklankan</h6>
        <div>
            <span class="badge bg-success">Hero Product</span>
            <span class="badge bg-primary">Profit Driver</span>
            <span class="badge bg-warning text-dark">Traffic Driver</span>
            <span class="badge bg-danger">Loss Maker</span>
        </div>
    </div>
    <div class="table-responsive" style="max-height: 600px;">
        <table class="table table-hover align-middle mb-0 text-nowrap" style="font-size: 0.85rem;" id="productsPerformanceTable">
            <thead class="table-light sticky-top" style="z-index: 2;">
                <tr>
                    <th>Produk / SKU</th>
                    <th>Klasifikasi</th>
                    <th class="text-end">Stok</th>
                    <th class="text-end">Spend</th>
                    <th class="text-end">Pesanan</th>
                    <th class="text-end" data-bs-toggle="tooltip" title="Click-Through Rate">CTR</th>
                    <th class="text-end" data-bs-toggle="tooltip" title="Conversion Rate">CVR</th>
                    <th class="text-end">GMV</th>
                    <th class="text-end">Gross Profit</th>
                    <th class="text-end" data-bs-toggle="tooltip" title="Net Profit = Gross Profit - Spend">Net Profit</th>
                    <th class="text-end" data-bs-toggle="tooltip" title="Profit on Ad Spend">POAS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($itemPerformance as $row)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                @if($row->image_url)
                                    <img src="{{ $row->image_url }}" alt="img" class="rounded me-2" style="width: 32px; height: 32px; object-fit: cover; border: 1px solid #ddd;">
                                @else
                                    <div class="rounded me-2 bg-light d-flex align-items-center justify-content-center text-muted" style="width: 32px; height: 32px; border: 1px solid #ddd;">
                                        <i class="bi bi-image"></i>
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-bold text-truncate" style="max-width: 250px;" title="{{ $row->item_name }}">
                                        {{ $row->item_name }}
                                    </div>
                                    <div class="text-muted small">
                                        {{ $row->item_sku }} <span class="badge bg-light text-dark ms-1">{{ $row->item_category }}</span>
                                    </div>
                                    <div class="text-muted" style="font-size:.65rem;">
                                        HPP/unit: {{ ($row->unit_cogs ?? 0) > 0 ? 'Rp ' . number_format($row->unit_cogs, 0, ',', '.') : 'belum tersedia' }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-{{ $row->class_color }}">
                                {{ $row->classification }}
                            </span>
                        </td>
                        <td class="text-end">
                            @if(($row->stock_total ?? 0) < 5)
                                <span class="text-danger fw-bold">{{ $row->stock_total ?? 0 }}</span>
                            @else
                                {{ $row->stock_total ?? 0 }}
                            @endif
                        </td>
                        <td class="text-end fw-bold text-danger">Rp {{ number_format($row->spend, 0, ',', '.') }}</td>
                        <td class="text-end fw-bold">{{ number_format($row->orders, 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($row->ctr, 2) }}%</td>
                        <td class="text-end">{{ number_format($row->cvr, 2) }}%</td>
                        <td class="text-end text-success fw-bold">Rp {{ number_format($row->gmv, 0, ',', '.') }}</td>
                        <td class="text-end text-muted">{{ $row->gross_profit === null ? 'N/A' : 'Rp ' . number_format($row->gross_profit, 0, ',', '.') }}</td>
                        <td class="text-end fw-bold {{ $row->profit_after_ads === null ? 'text-muted' : ($row->profit_after_ads >= 0 ? 'text-success' : 'text-danger') }}">
                            {{ $row->profit_after_ads === null ? 'N/A' : 'Rp ' . number_format($row->profit_after_ads, 0, ',', '.') }}
                        </td>
                        <td class="text-end">{{ $row->poas === null ? 'N/A' : number_format($row->poas, 2) . 'x' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center py-4 text-muted">Belum ada data performa produk yang memadai untuk tanggal ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pScatterData = {!! $scatterDataJson !!};

    const pctx = document.getElementById('productScatterChart');
    if (!pctx) return;

    const classColorMap = {
        'Hero Product': 'rgba(25, 135, 84, 0.7)',
        'Profit Driver': 'rgba(13, 110, 253, 0.7)',
        'Volume Driver': 'rgba(13, 202, 240, 0.7)',
        'Traffic Driver (No Conv)': 'rgba(255, 193, 7, 0.7)',
        'Loss Maker': 'rgba(220, 53, 69, 0.7)',
        'Stock Risk': 'rgba(220, 53, 69, 0.9)',
        'Review': 'rgba(108, 117, 125, 0.7)',
        'Low Performer': 'rgba(173, 181, 189, 0.7)'
    };

    const pDatasets = Object.keys(classColorMap).map(cls => {
        return {
            label: cls,
            data: pScatterData.filter(d => d.reco === cls),
            backgroundColor: classColorMap[cls],
            borderColor: classColorMap[cls].replace('0.7', '1').replace('0.9', '1'),
            borderWidth: 1
        };
    }).filter(ds => ds.data.length > 0);

    new Chart(pctx, {
        type: 'bubble',
        data: {
            datasets: pDatasets
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
                                'Net Profit: Rp ' + data.y.toLocaleString('id-ID')
                            ];
                        }
                    }
                },
                legend: {
                    display: true,
                    position: 'bottom'
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
