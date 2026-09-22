<section class="an-cohort-workspace an-tab-pane is-hidden" data-an-pane="cohort" aria-labelledby="cohortWorkspaceTitle">
    <header class="an-cohort-pagebar">
        <div>
            <div class="an-cohort-breadcrumb">Marketplace / Analytics / Cohort</div>
            <h2 class="an-cohort-title" id="cohortWorkspaceTitle">Analisis cohort</h2>
            <p class="an-cohort-description">Bandingkan pelanggan atau produk berdasarkan bulan transaksi pertama.</p>
        </div>
        <div class="an-cohort-pagebar-meta">
            <span class="an-cohort-period-label">Periode analisis</span>
            <strong id="anCohortPeriodLabel">—</strong>
            <button class="an-cohort-reset" id="anCohortReset" type="button"><i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>Reset</button>
        </div>
    </header>

    <section class="an-cohort-toolbar" aria-labelledby="cohortControlsTitle">
        <div class="an-cohort-toolbar-head">
            <div>
                <div class="an-cohort-control-eyebrow">Konfigurasi analisis</div>
                <h3 class="an-cohort-control-title" id="cohortControlsTitle">Tentukan fokus data</h3>
            </div>
            <span class="an-cohort-toolbar-note"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>Hasil diperbarui setelah diterapkan</span>
        </div>

        <div class="an-cohort-control-grid an-cohort-primary-controls">
            <div class="an-cohort-field"><label for="anCohortMode">Fokus</label><select id="anCohortMode"><option value="customer">Perilaku pelanggan</option><option value="product">Performa produk</option></select></div>
            <div class="an-cohort-field"><label for="anCohortMetric">Metric</label><select id="anCohortMetric"></select></div>
            <div class="an-cohort-field an-cohort-grouping-field" id="anCohortGroupingField"><label for="anCohortGroupBy">Kelompok produk</label><select id="anCohortGroupBy"><option value="category">Kategori master</option><option value="product">Produk / SKU</option></select></div>
            <button class="an-btn an-btn-dark an-cohort-apply" id="anCohortApply" type="button"><i class="bi bi-play-fill me-1" aria-hidden="true"></i>Terapkan</button>
        </div>

        <details class="an-cohort-advanced">
            <summary><span><i class="bi bi-funnel me-1" aria-hidden="true"></i>Filter tambahan</span><small>Toko, marketplace, kategori, produk, dan SKU</small></summary>
            <div class="an-cohort-control-grid an-cohort-advanced-grid">
                <div class="an-cohort-field"><label for="anCohortMarketplace">Marketplace</label><select id="anCohortMarketplace"><option value="">Semua marketplace</option></select></div>
                <div class="an-cohort-field"><label for="anCohortCategory">Kategori master</label><select id="anCohortCategory"><option value="">Semua kategori</option></select></div>
                <div class="an-cohort-field"><label for="anCohortProduct">Produk</label><select id="anCohortProduct"><option value="">Semua produk</option></select></div>
                <div class="an-cohort-field"><label for="anCohortSku">SKU</label><select id="anCohortSku"><option value="">Semua SKU</option></select></div>
            </div>
        </details>

        <div class="an-cohort-active-filters" id="anCohortActiveFilters" aria-live="polite"><span class="an-cohort-filter-caption">Filter aktif</span><span class="an-filter-chip">Periode: <strong>—</strong></span><span class="an-filter-chip">Toko: <strong>Semua toko</strong></span></div>
    </section>

    <section class="an-cohort-summary-grid" id="anCohortKpis" aria-label="Ringkasan cohort">
        <div class="an-cohort-summary-card is-primary"><span class="an-cohort-summary-label">Cohort yang terlihat</span><strong class="an-cohort-summary-value">—</strong><span class="an-cohort-summary-note">Menunggu analisis</span></div>
        <div class="an-cohort-summary-card"><span class="an-cohort-summary-label">Metric aktif</span><strong class="an-cohort-summary-value">—</strong><span class="an-cohort-summary-note">Angka utama</span></div>
        <div class="an-cohort-summary-card"><span class="an-cohort-summary-label">Aktivitas terakhir</span><strong class="an-cohort-summary-value">—</strong><span class="an-cohort-summary-note">Periode terakhir</span></div>
        <div class="an-cohort-summary-card"><span class="an-cohort-summary-label">Kualitas data</span><strong class="an-cohort-summary-value">—</strong><span class="an-cohort-summary-note">Coverage / retention</span></div>
    </section>

    <section class="an-cohort-insights" aria-label="Ringkasan visual cohort">
        <section class="an-enterprise-card an-cohort-chart-card">
            <div class="an-enterprise-head">
                <div><div class="an-cohort-panel-eyebrow">Perkembangan</div><div class="an-enterprise-title">Perubahan berdasarkan umur cohort</div><div class="an-enterprise-sub" id="anCohortCurveSubtitle">Rata-rata metric sejak transaksi pertama.</div></div>
                <span class="an-cohort-panel-badge blue">M0 → terbaru</span>
            </div>
            <div class="an-enterprise-body an-cohort-chart-body"><div class="an-cohort-chart-canvas" id="anCohortCurveChart"><div class="an-empty">Terapkan analisis untuk melihat grafik.</div></div></div>
        </section>
        <section class="an-enterprise-card an-cohort-chart-card">
            <div class="an-enterprise-head">
                <div><div class="an-cohort-panel-eyebrow">Sebaran</div><div class="an-enterprise-title">Cohort berdasarkan bulan mulai</div><div class="an-enterprise-sub" id="anCohortDistributionSubtitle">Ukuran cohort dari transaksi pertama.</div></div>
                <span class="an-cohort-panel-badge green">Per bulan</span>
            </div>
            <div class="an-enterprise-body an-cohort-chart-body"><div class="an-cohort-chart-canvas" id="anCohortDistributionChart"><div class="an-empty">Terapkan analisis untuk melihat grafik.</div></div></div>
        </section>
    </section>

    <section class="an-enterprise-card an-cohort-matrix-card">
        <div class="an-enterprise-head an-cohort-panel-head">
            <div><div class="an-cohort-panel-eyebrow">Rincian utama</div><div class="an-enterprise-title">Matriks cohort</div><div class="an-enterprise-sub" id="anCohortMatrixSubtitle">Terapkan analisis untuk memuat matriks.</div></div>
            <div class="an-cohort-legend" aria-label="Legenda matriks cohort"><span><i class="an-cohort-legend-swatch primary"></i> Nilai lebih tinggi</span><span><i class="an-cohort-legend-swatch muted"></i> Belum ada aktivitas</span></div>
        </div>
        <div class="an-enterprise-body an-cohort-matrix-body">
            <div class="an-cohort-note" id="anCohortNote" aria-live="polite">Terapkan analisis untuk memuat data.</div>
            <div class="an-cohort-reading-bar" id="anCohortReadingBar">
                <span class="an-cohort-reading-item"><strong>Base</strong><small>ukuran awal</small></span>
                <span class="an-cohort-reading-item"><strong>M0</strong><small>bulan pertama</small></span>
                <span class="an-cohort-reading-item"><strong>M1+</strong><small>bulan berikutnya</small></span>
                <span class="an-cohort-reading-hint" id="anCohortMetricHint">Pilih metric untuk melihat definisinya.</span>
            </div>
            <div class="an-table-wrap an-cohort-table-wrap"><table id="anCohortTable" class="an-table an-cohort-table"><caption class="visually-hidden">Matriks cohort berdasarkan periode transaksi pertama</caption><thead id="anCohortHead"><tr><th>Cohort</th><th>Base</th><th>M0</th><th>M1</th><th>M2</th></tr></thead><tbody id="anCohortBody" aria-live="polite"><tr><td colspan="5"><div class="an-empty">Terapkan analisis untuk memuat data.</div></td></tr></tbody></table></div>
        </div>
    </section>

    <details class="an-cohort-help-card">
        <summary><span><i class="bi bi-question-circle me-1" aria-hidden="true"></i>Cara membaca data</span><small>Panduan singkat</small></summary>
        <div class="an-cohort-help-grid">
            <div><strong>M0</strong><p>Bulan transaksi pertama pada cohort.</p></div>
            <div><strong>M1+</strong><p>Aktivitas pada bulan-bulan berikutnya.</p></div>
            <div><strong>Tanda —</strong><p>Belum ada aktivitas, bukan berarti nol.</p></div>
            <div><strong>Coverage</strong><p>Profit hanya berasal dari settlement complete.</p></div>
        </div>
    </details>
</section>

<div class="an-modal" id="cohortDetailModal" aria-hidden="true">
    <div class="an-modal-backdrop" data-cohort-close></div>
    <section class="an-modal-dialog an-cohort-detail-dialog" role="dialog" aria-modal="true" aria-labelledby="cohortDetailTitle">
        <div class="an-modal-head"><div><div class="an-modal-eyebrow">Detail cohort</div><div class="an-modal-title" id="cohortDetailTitle">Rincian cohort</div><div class="an-modal-sub" id="cohortDetailSubtitle">Periode aktif</div></div><button class="an-modal-close" type="button" data-cohort-close aria-label="Tutup">×</button></div>
        <div class="an-modal-summary" id="cohortDetailSummary"><div class="an-empty">Pilih sel cohort.</div></div>
        <div class="an-modal-body"><div class="an-cohort-note" id="cohortDetailNote">Detail mengikuti filter Cohort dan tidak menampilkan identitas pelanggan.</div></div>
    </section>
</div>
