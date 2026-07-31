@php
    $generatedAlerts = [];

    // Evaluate Campaigns
    foreach ($campaigns as $camp) {
        $cName = $camp->campaign_name ?? 'Unknown Campaign';
        $spend = $camp->spend ?? 0;
        $orders = $camp->orders ?? 0;
        $clicks = $camp->clicks ?? 0;
        $impressions = $camp->impressions ?? 0;
        $profit = $camp->profit_after_ads ?? 0;
        $acos = $camp->acos_pct ?? null;
        $breakEven = $camp->break_even_acos_pct ?? null;
        $budget = $camp->campaign_budget ?? 0;
        
        $prevOrders = $camp->prev_orders ?? 0;
        $prevClicks = $camp->prev_clicks ?? 0;

        // 1. Bakar Uang (Spend up, no orders)
        if ($spend > 25000 && $orders == 0) {
            $generatedAlerts[] = [
                'priority' => 1,
                'type' => 'danger',
                'icon' => 'bi-fire',
                'title' => 'Bakar Budget Tanpa Pesanan',
                'message' => "Kampanye <strong>{$cName}</strong> telah menghabiskan Rp " . number_format($spend, 0, ',', '.') . " tanpa mencetak 1 pun pesanan.",
                'action' => 'Pause Kampanye / Turunkan Bid',
                'entity' => 'Campaign'
            ];
        }

        // 2. Loss Maker (ACOS > Break Even)
        if ($spend > 10000 && $orders > 0 && $breakEven !== null && $acos !== null && $acos > $breakEven) {
            $generatedAlerts[] = [
                'priority' => 1,
                'type' => 'danger',
                'icon' => 'bi-graph-down-arrow',
                'title' => 'Rugi (ACOS Menjebol Break-Even)',
                'message' => "Kampanye <strong>{$cName}</strong> mencetak ACOS {$acos}%, melebihi batas toleransi margin ({$breakEven}%). Anda rugi Rp " . number_format(abs($profit), 0, ',', '.') . ".",
                'action' => 'Kurangi Bid Keyword / Pause',
                'entity' => 'Campaign'
            ];
        }

        // 3. Scale Up Opportunity (Profitable, near budget limit)
        if ($profit > 50000 && $budget > 0 && $spend >= ($budget * 0.8)) {
            $generatedAlerts[] = [
                'priority' => 3,
                'type' => 'success',
                'icon' => 'bi-rocket',
                'title' => 'Peluang Scale Up (Batas Budget)',
                'message' => "Kampanye <strong>{$cName}</strong> sangat profitable (Untung Rp " . number_format($profit, 0, ',', '.') . "), namun pengeluaran hampir menyentuh batas modal harian (Rp " . number_format($budget, 0, ',', '.') . ").",
                'action' => 'Naikkan Budget Harian',
                'entity' => 'Campaign'
            ];
        }

        // 4. CVR Drop Warning
        $currentCvr = $clicks > 0 ? ($orders / $clicks) * 100 : 0;
        $prevCvr = $prevClicks > 0 ? ($prevOrders / $prevClicks) * 100 : 0;
        
        if ($clicks >= 30 && $prevCvr > 2.0 && $currentCvr < ($prevCvr * 0.5)) {
            $generatedAlerts[] = [
                'priority' => 2,
                'type' => 'warning',
                'icon' => 'bi-exclamation-triangle',
                'title' => 'Konversi (CVR) Anjlok',
                'message' => "CVR Kampanye <strong>{$cName}</strong> anjlok drastis dari " . number_format($prevCvr, 2) . "% menjadi " . number_format($currentCvr, 2) . "%.",
                'action' => 'Periksa Pesaing Baru atau Ulasan Buruk',
                'entity' => 'Campaign'
            ];
        }
    }

    // Evaluate Item Performance (Products)
    foreach ($itemPerformance as $item) {
        $iName = $item->item_name ?? 'Unknown Product';
        $stock = $item->stock_total ?? null;
        $profit = $item->profit_after_ads ?? 0;
        
        // 5. Stock Running Out
        if ($stock !== null && $stock <= 3 && $item->orders > 0) {
            $generatedAlerts[] = [
                'priority' => 2,
                'type' => 'warning',
                'icon' => 'bi-box-seam',
                'title' => 'Produk Terlaris Hampir Habis',
                'message' => "Produk <strong>{$iName}</strong> mencetak pesanan iklan hari ini, tetapi sisa stok sistem tinggal {$stock} unit.",
                'action' => 'Restock / Pause Iklan Sementara',
                'entity' => 'Product'
            ];
        }
    }

    // Sort Alerts by Priority (1 = Highest)
    usort($generatedAlerts, function($a, $b) {
        return $a['priority'] <=> $b['priority'];
    });
@endphp

<div class="dash-sec"><i class="bi bi-bell"></i> Alerts & Action Center</div>
<div class="alert alert-secondary border-0 bg-light mb-4 text-dark" style="font-size: 0.85rem;">
    <i class="bi bi-robot me-2 text-primary"></i> <strong>Sistem Deteksi Anomali Aktif.</strong> 
    Kami terus memantau metrik Anda secara *real-time* untuk menemukan kebocoran pengeluaran atau peluang Scale-Up tersembunyi.
</div>

<div class="row g-3">
    <div class="col-12">
        <div class="dpanel p-0 overflow-hidden">
            @if(count($generatedAlerts) > 0)
                <div class="list-group list-group-flush">
                    @foreach($generatedAlerts as $alert)
                        <div class="list-group-item p-4 border-bottom position-relative hover-bg-light" style="border-left: 4px solid var(--bs-{{ $alert['type'] }});">
                            <div class="d-flex w-100 justify-content-between align-items-start">
                                <div class="d-flex gap-3">
                                    <div class="fs-4 text-{{ $alert['type'] }} mt-1">
                                        <i class="bi {{ $alert['icon'] }}"></i>
                                    </div>
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <h6 class="mb-0 fw-bold text-dark">{{ $alert['title'] }}</h6>
                                            <span class="badge bg-light text-secondary border" style="font-size: 0.65rem;">{{ $alert['entity'] }}</span>
                                        </div>
                                        <p class="mb-2 text-muted" style="font-size: 0.85rem;">{!! $alert['message'] !!}</p>
                                        <div class="d-inline-flex align-items-center gap-2" style="background: rgba(var(--bs-{{ $alert['type'] }}-rgb), 0.1); padding: 4px 12px; border-radius: 6px; border: 1px dashed rgba(var(--bs-{{ $alert['type'] }}-rgb), 0.4);">
                                            <i class="bi bi-arrow-right-short text-{{ $alert['type'] }}"></i>
                                            <span class="fw-bold text-{{ $alert['type'] }}" style="font-size: 0.75rem;">Action: {{ $alert['action'] }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button class="btn btn-sm btn-outline-secondary rounded-pill" style="font-size: 0.7rem;">
                                        Tinjau Data
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-5 text-center">
                    <div class="fs-1 text-success mb-3"><i class="bi bi-shield-check"></i></div>
                    <h5 class="fw-bold">Semua Aman Terkendali</h5>
                    <p class="text-muted small">Tidak ada anomali atau kebocoran dana yang terdeteksi pada kampanye Anda saat ini.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .hover-bg-light:hover {
        background-color: #f8f9fa;
    }
</style>
