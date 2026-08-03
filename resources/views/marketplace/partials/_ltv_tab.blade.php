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
            <div class="text-muted small fw-bold mb-2">Blended CAC (Estimasi)</div>
            <div class="fs-4 fw-bolder {{ $blendedCac > 50000 ? 'text-danger' : 'text-success' }}">
                Rp {{ number_format($blendedCac, 0, ',', '.') }}
            </div>
            <div class="small text-muted mt-1" style="font-size: 0.7rem;">Spend iklan ÷ pelanggan baru; bukan atribusi klik.</div>
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
                
                <div class="dpanel p-3 mt-3 text-start">
                    <div class="fw-bold mb-1"><i class="bi bi-database-exclamation text-warning me-1"></i> Cohort belum tersedia</div>
                    <div class="small text-muted">
                        Tabel cohort membutuhkan histori pembelian minimal beberapa bulan dan identitas pelanggan yang konsisten.
                        Dashboard ini belum memiliki data cohort aktual, jadi tidak menampilkan angka estimasi yang dapat disalahartikan sebagai data nyata.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
