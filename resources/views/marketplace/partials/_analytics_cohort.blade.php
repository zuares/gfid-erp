<section class="an-cohort-workspace an-tab-pane is-hidden" data-an-pane="cohort" aria-labelledby="cohortWorkspaceTitle">
    <header class="an-cohort-hero">
        <div class="an-cohort-hero-copy">
            <div class="an-section-kicker"><span class="an-section-kicker-dot"></span> Pelanggan &amp; produk</div>
            <h2 class="an-cohort-title" id="cohortWorkspaceTitle">Analisis Cohort</h2>
            <p class="an-cohort-description">Lihat kapan pelanggan kembali membeli dan bagaimana performa produk berkembang sejak transaksi pertama.</p>
        </div>
        <div class="an-cohort-hero-meta">
            <span class="an-status-pill"><i class="bi bi-database-check" aria-hidden="true"></i> Data teragregasi</span>
            <div class="an-cohort-period-label">Periode aktif <strong id="anCohortPeriodLabel">—</strong></div>
        </div>
    </header>

    <section class="an-cohort-control-card" aria-labelledby="cohortControlsTitle">
        <div class="an-cohort-control-head">
            <div>
                <div class="an-cohort-control-eyebrow">Atur tampilan analisis</div>
                <h3 class="an-cohort-control-title" id="cohortControlsTitle"><i class="bi bi-sliders2 me-1" aria-hidden="true"></i>Pilih sudut pandang</h3>
                <p class="an-cohort-control-sub">Pilih pelanggan atau produk, lalu tentukan angka utama yang ingin dibandingkan dari bulan ke bulan.</p>
            </div>
            <button class="an-cohort-reset" id="anCohortReset" type="button"><i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>Reset filter</button>
        </div>

        <div class="an-cohort-control-grid an-cohort-primary-controls">
            <div class="an-cohort-field"><label for="anCohortMode">Yang ingin dilihat</label><select id="anCohortMode"><option value="customer">Perilaku pelanggan</option><option value="product">Performa produk</option></select></div>
            <div class="an-cohort-field"><label for="anCohortMetric">Angka utama</label><select id="anCohortMetric"></select></div>
            <div class="an-cohort-field an-cohort-grouping-field" id="anCohortGroupingField"><label for="anCohortGroupBy">Kelompokkan produk</label><select id="anCohortGroupBy"><option value="category">Kategori master</option><option value="product">Produk / SKU</option></select></div>
            <div class="an-cohort-run-wrap"><span class="an-cohort-run-hint">Tampilan akan diperbarui setelah tombol ditekan</span><button class="an-btn an-btn-dark an-cohort-apply" id="anCohortApply" type="button"><i class="bi bi-play-fill me-1" aria-hidden="true"></i>Terapkan analisis</button></div>
        </div>

        <details class="an-cohort-advanced" open>
            <summary><span><i class="bi bi-funnel me-1" aria-hidden="true"></i>Filter tambahan</span><small>Opsional: toko, channel, dan katalog</small></summary>
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
        <div class="an-cohort-summary-card"><span class="an-cohort-summary-label">Angka utama</span><strong class="an-cohort-summary-value">—</strong><span class="an-cohort-summary-note">Metric aktif</span></div>
        <div class="an-cohort-summary-card"><span class="an-cohort-summary-label">Aktivitas terakhir</span><strong class="an-cohort-summary-value">—</strong><span class="an-cohort-summary-note">Periode terakhir</span></div>
        <div class="an-cohort-summary-card"><span class="an-cohort-summary-label">Kualitas data</span><strong class="an-cohort-summary-value">—</strong><span class="an-cohort-summary-note">Coverage / retention</span></div>
    </section>

    <section class="an-cohort-insights" aria-label="Cohort visual insights">
        <section class="an-enterprise-card an-cohort-chart-card">
            <div class="an-enterprise-head">
                <div><div class="an-cohort-panel-eyebrow">Perkembangan</div><div class="an-enterprise-title"><i class="bi bi-graph-up-arrow me-1" style="color:#2563eb" aria-hidden="true"></i>Perubahan sejak transaksi pertama</div><div class="an-enterprise-sub" id="anCohortCurveSubtitle">Rata-rata metric berdasarkan umur cohort.</div></div>
                <span class="an-cohort-panel-badge blue">M0 → terbaru</span>
            </div>
            <div class="an-enterprise-body an-cohort-chart-body"><div class="an-cohort-chart-canvas" id="anCohortCurveChart"><div class="an-empty">Buka analisis untuk memuat grafik.</div></div></div>
        </section>
        <section class="an-enterprise-card an-cohort-chart-card">
            <div class="an-enterprise-head">
                <div><div class="an-cohort-panel-eyebrow">Sebaran</div><div class="an-enterprise-title"><i class="bi bi-bar-chart-line me-1" style="color:#16a34a" aria-hidden="true"></i>Jumlah cohort per bulan</div><div class="an-enterprise-sub" id="anCohortDistributionSubtitle">Ukuran cohort berdasarkan bulan transaksi pertama.</div></div>
                <span class="an-cohort-panel-badge green">Per bulan</span>
            </div>
            <div class="an-enterprise-body an-cohort-chart-body"><div class="an-cohort-chart-canvas" id="anCohortDistributionChart"><div class="an-empty">Buka analisis untuk memuat grafik.</div></div></div>
        </section>
    </section>

    <div class="an-cohort-detail-layout">
        <section class="an-enterprise-card an-cohort-matrix-card">
            <div class="an-enterprise-head an-cohort-panel-head">
                <div><div class="an-cohort-panel-eyebrow">Rincian</div><div class="an-enterprise-title"><i class="bi bi-grid-3x3-gap me-1" style="color:#2563eb" aria-hidden="true"></i>Matriks cohort</div><div class="an-enterprise-sub" id="anCohortMatrixSubtitle">Terapkan analisis untuk memuat matriks.</div></div>
                <div class="an-cohort-legend" aria-label="Legenda matriks cohort"><span><i class="an-cohort-legend-swatch primary"></i> Nilai lebih tinggi</span><span><i class="an-cohort-legend-swatch muted"></i> Belum ada aktivitas</span></div>
            </div>
            <div class="an-enterprise-body an-cohort-matrix-body">
                <div class="an-cohort-note" id="anCohortNote" aria-live="polite">Terapkan analisis untuk memuat data.</div>
                <div class="an-cohort-reading-bar" id="anCohortReadingBar">
                    <span class="an-cohort-reading-item"><i class="bi bi-people" aria-hidden="true"></i><strong>Base</strong><small>ukuran awal</small></span>
                    <span class="an-cohort-reading-item"><i class="bi bi-calendar2-check" aria-hidden="true"></i><strong>M0</strong><small>bulan pertama</small></span>
                    <span class="an-cohort-reading-item"><i class="bi bi-arrow-right" aria-hidden="true"></i><strong>M1+</strong><small>bulan berikutnya</small></span>
                    <span class="an-cohort-reading-hint" id="anCohortMetricHint">Pilih angka utama untuk melihat definisinya.</span>
                </div>
                <div class="an-table-wrap an-cohort-table-wrap"><table id="anCohortTable" class="an-table an-cohort-table"><caption class="visually-hidden">Cohort matrix berdasarkan periode transaksi pertama</caption><thead id="anCohortHead"><tr><th>Cohort</th><th>Base</th><th>M0</th><th>M1</th><th>M2</th></tr></thead><tbody id="anCohortBody" aria-live="polite"><tr><td colspan="5"><div class="an-empty">Buka tab Cohort untuk memuat data.</div></td></tr></tbody></table></div>
            </div>
        </section>

        <aside class="an-enterprise-card an-cohort-guide-card" aria-labelledby="cohortGuideTitle">
            <div class="an-enterprise-head"><div><div class="an-cohort-panel-eyebrow">Panduan keputusan</div><div class="an-enterprise-title" id="cohortGuideTitle">Cara membaca data</div><div class="an-enterprise-sub">Ringkasan cepat untuk mengambil keputusan</div></div></div>
            <div class="an-enterprise-body">
                <div class="an-cohort-guide-list">
                    <div class="an-cohort-guide-item"><span class="an-cohort-guide-icon blue"><i class="bi bi-calendar2-event" aria-hidden="true"></i></span><div><strong>M0 = transaksi pertama</strong><p>Setiap cohort dimulai pada bulan transaksi pertama dalam filter aktif.</p></div></div>
                    <div class="an-cohort-guide-item"><span class="an-cohort-guide-icon green"><i class="bi bi-arrow-repeat" aria-hidden="true"></i></span><div><strong>M1+ = aktivitas berikutnya</strong><p>Retention pelanggan dihitung dari pelanggan unik yang kembali bertransaksi.</p></div></div>
                    <div class="an-cohort-guide-item"><span class="an-cohort-guide-icon amber"><i class="bi bi-exclamation-circle" aria-hidden="true"></i></span><div><strong>Tanda — = belum ada aktivitas</strong><p>Sel kosong berarti belum ada aktivitas pada umur cohort tersebut, bukan nol.</p></div></div>
                </div>
        <div class="an-cohort-guide-footer"><i class="bi bi-info-circle me-1" aria-hidden="true"></i><span>Gross sales dapat lebih besar dari profit. Profit hanya dihitung dari settlement complete, dan coverage ditampilkan di setiap sel.</span></div>
            </div>
        </aside>
    </div>
</section>

<div class="an-modal" id="cohortDetailModal" aria-hidden="true">
    <div class="an-modal-backdrop" data-cohort-close></div>
    <section class="an-modal-dialog an-cohort-detail-dialog" role="dialog" aria-modal="true" aria-labelledby="cohortDetailTitle">
        <div class="an-modal-head"><div><div class="an-modal-eyebrow">Detail cohort</div><div class="an-modal-title" id="cohortDetailTitle">Rincian cohort</div><div class="an-modal-sub" id="cohortDetailSubtitle">Periode aktif</div></div><button class="an-modal-close" type="button" data-cohort-close aria-label="Tutup">×</button></div>
        <div class="an-modal-summary" id="cohortDetailSummary"><div class="an-empty">Pilih sel cohort.</div></div>
        <div class="an-modal-body"><div class="an-cohort-note" id="cohortDetailNote">Detail mengikuti filter Cohort dan tidak menampilkan identitas pelanggan.</div></div>
    </section>
</div>
