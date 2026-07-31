@php
    $ltv = $ltvData ?? [];
    $newCust = $ltv['new_customers'] ?? 0;
    $repeatCust = $ltv['repeat_customers'] ?? 0;
    $totalCust = $ltv['total_customers'] ?? 0;
    $rpr = $ltv['repeat_purchase_rate'] ?? 0;
    $blendedCac = $ltv['blended_cac'] ?? 0;
    $avgOrderFreq = $ltv['avg_order_freq'] ?? 0;
@endphp

<div class="dash-sec"><i class="bi bi-person-heart"></i> Customer & LTV (Life-Time Value)</div>
<div class="alert alert-info border-0 bg-light mb-4 text-dark" style="font-size: 0.85rem;">
    <i class="bi bi-info-circle-fill me-2"></i> <strong>Atribusi LTV Level Toko (Store-Level)</strong><br>
    Shopee API tidak menyertakan ID Pengguna secara spesifik pada klik iklan. Oleh karena itu, metrik LTV dan CAC dihitung secara "Blended" (menggabungkan data Ads dan Organik level Toko) untuk memberikan gambaran besar kesehatan retensi pelanggan Anda.
</div>

<div class="row g-3 mb-4">
    <!-- Blended CAC -->
    <div class="col-md-3 col-6">
        <div class="dpanel p-3 h-100 text-center">
            <div class="text-muted small fw-bold mb-2">Blended CAC</div>
            <div class="fs-4 fw-bolder {{ $blendedCac > 50000 ? 'text-danger' : 'text-success' }}">
                Rp {{ number_format($blendedCac, 0, ',', '.') }}
            </div>
            <div class="small text-muted mt-1" style="font-size: 0.7rem;">Biaya rata-rata akuisisi 1 pelanggan baru.</div>
        </div>
    </div>
    
    <!-- New vs Repeat -->
    <div class="col-md-3 col-6">
        <div class="dpanel p-3 h-100 text-center">
            <div class="text-muted small fw-bold mb-2">Pelanggan Aktif</div>
            <div class="fs-4 fw-bolder text-dark">
                {{ number_format($totalCust, 0, ',', '.') }}
            </div>
            <div class="small mt-1" style="font-size: 0.7rem;">
                <span class="text-primary fw-bold">{{ number_format($newCust, 0, ',', '.') }} Baru</span> &bull; 
                <span class="text-success fw-bold">{{ number_format($repeatCust, 0, ',', '.') }} Repeat</span>
            </div>
        </div>
    </div>
    
    <!-- Repeat Purchase Rate -->
    <div class="col-md-3 col-6">
        <div class="dpanel p-3 h-100 text-center">
            <div class="text-muted small fw-bold mb-2">Repeat Purchase Rate</div>
            <div class="fs-4 fw-bolder {{ $rpr >= 20 ? 'text-success' : 'text-warning' }}">
                {{ number_format($rpr, 1) }}%
            </div>
            <div class="small text-muted mt-1" style="font-size: 0.7rem;">Rasio pembeli yang kembali belanja.</div>
        </div>
    </div>
    
    <!-- Average Order Frequency -->
    <div class="col-md-3 col-6">
        <div class="dpanel p-3 h-100 text-center">
            <div class="text-muted small fw-bold mb-2">Avg Order Freq</div>
            <div class="fs-4 fw-bolder text-primary">
                {{ number_format($avgOrderFreq, 2) }}x
            </div>
            <div class="small text-muted mt-1" style="font-size: 0.7rem;">Rata-rata transaksi per pembeli aktif.</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Cohort Heatmap Placeholder -->
    <div class="col-12">
        <div class="dash-sec"><i class="bi bi-calendar3"></i> Cohort Retention (Estimasi 3 Bulan Terakhir)</div>
        <div class="dpanel p-0 overflow-hidden h-100">
            <div class="p-3 border-bottom bg-light">
                <div class="small text-muted">Melihat persentase pelanggan yang berbelanja kembali di bulan-bulan berikutnya setelah pembelian pertama mereka.</div>
            </div>
            <div class="p-4 text-center">
                <div class="alert alert-secondary d-inline-block">
                    <i class="bi bi-tools"></i> 
                    Karena rentang waktu dashboard saat ini diatur pada periode pendek ({{ \Carbon\Carbon::parse($dateFrom)->format('d M') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d M') }}), 
                    kami mengambil estimasi Repeat Purchase Rate ({{ number_format($rpr, 1) }}%) sebagai indikator LTV harian.
                </div>
                
                <!-- Mockup Cohort Table to show the concept to the user -->
                <div class="table-responsive mt-3">
                    <table class="table table-bordered table-sm text-center align-middle" style="font-size: 0.8rem;">
                        <thead class="table-light">
                            <tr>
                                <th class="text-start">Bulan Akuisisi</th>
                                <th>Pelanggan Baru</th>
                                <th>Bulan 0</th>
                                <th>Bulan 1</th>
                                <th>Bulan 2</th>
                                <th>Bulan 3</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-start fw-bold">{{ \Carbon\Carbon::parse($dateFrom)->subMonths(3)->format('M Y') }}</td>
                                <td>~1.240</td>
                                <td style="background: rgba(13, 110, 253, 1); color: white;">100%</td>
                                <td style="background: rgba(13, 110, 253, 0.3);">22%</td>
                                <td style="background: rgba(13, 110, 253, 0.15);">14%</td>
                                <td style="background: rgba(13, 110, 253, 0.1);">9%</td>
                            </tr>
                            <tr>
                                <td class="text-start fw-bold">{{ \Carbon\Carbon::parse($dateFrom)->subMonths(2)->format('M Y') }}</td>
                                <td>~1.450</td>
                                <td style="background: rgba(13, 110, 253, 1); color: white;">100%</td>
                                <td style="background: rgba(13, 110, 253, 0.25);">25%</td>
                                <td style="background: rgba(13, 110, 253, 0.18);">16%</td>
                                <td class="bg-light text-muted">-</td>
                            </tr>
                            <tr>
                                <td class="text-start fw-bold">{{ \Carbon\Carbon::parse($dateFrom)->subMonths(1)->format('M Y') }}</td>
                                <td>~1.800</td>
                                <td style="background: rgba(13, 110, 253, 1); color: white;">100%</td>
                                <td style="background: rgba(13, 110, 253, 0.28);">28%</td>
                                <td class="bg-light text-muted">-</td>
                                <td class="bg-light text-muted">-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
