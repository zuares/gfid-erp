<section class="an-cohort-workspace an-tab-pane is-hidden" data-an-pane="cohort" aria-labelledby="cohortWorkspaceTitle">
    <div class="an-cohort-header">
        <div class="an-cohort-heading">
            <div class="an-section-kicker"><span class="an-section-kicker-dot"></span> Customer intelligence</div>
            <h2 class="an-cohort-title" id="cohortWorkspaceTitle">Cohort Analysis</h2>
            <p class="an-cohort-description">Pantau kualitas retensi customer dan perkembangan produk dari bulan transaksi pertama.</p>
        </div>
        <div class="an-cohort-header-meta">
            <span class="an-status-pill"><i class="bi bi-database-check" aria-hidden="true"></i> SQL aggregated</span>
            <span class="an-cohort-period" id="anCohortPeriodLabel">Periode aktif</span>
        </div>
    </div>

    <div class="an-cohort-filter-card">
        <div class="an-cohort-filter-head">
            <div><div class="an-cohort-filter-title"><i class="bi bi-sliders2 me-1"></i>Analysis controls</div><div class="an-cohort-filter-sub">Gunakan filter utama di atas untuk periode dan toko. Filter lanjutan berlaku khusus untuk cohort.</div></div>
            <button class="an-cohort-reset" id="anCohortReset" type="button"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset filter</button>
        </div>
        <div class="an-cohort-filter-grid">
            <div class="an-cohort-field"><label for="anCohortMode">View</label><select id="anCohortMode"><option value="customer">Customer cohort</option><option value="product">Product cohort</option></select></div>
            <div class="an-cohort-field"><label for="anCohortMetric">Primary metric</label><select id="anCohortMetric"></select></div>
            <div class="an-cohort-field"><label for="anCohortMarketplace">Marketplace</label><select id="anCohortMarketplace"><option value="">All marketplaces</option></select></div>
            <div class="an-cohort-field"><label for="anCohortCategory">Category</label><select id="anCohortCategory"><option value="">All categories</option></select></div>
            <div class="an-cohort-field"><label for="anCohortProduct">Product</label><select id="anCohortProduct"><option value="">All products</option></select></div>
            <div class="an-cohort-field"><label for="anCohortSku">SKU</label><select id="anCohortSku"><option value="">All SKUs</option></select></div>
            <button class="an-btn an-btn-dark an-cohort-apply" id="anCohortApply" type="button"><i class="bi bi-play-fill me-1"></i>Run analysis</button>
        </div>
        <div class="an-cohort-active-filters" id="anCohortActiveFilters"><span class="an-cohort-filter-caption">Active scope</span><span class="an-filter-chip">Periode: <strong>—</strong></span><span class="an-filter-chip">Toko: <strong>Semua toko</strong></span></div>
    </div>

    <div class="an-cohort-summary-grid" id="anCohortKpis">
        <div class="an-cohort-summary-card is-primary"><span class="an-cohort-summary-label">Cohorts in view</span><strong class="an-cohort-summary-value">—</strong><span class="an-cohort-summary-note">Menunggu analisis</span></div>
        <div class="an-cohort-summary-card"><span class="an-cohort-summary-label">Primary metric</span><strong class="an-cohort-summary-value">—</strong><span class="an-cohort-summary-note">Metric aktif</span></div>
        <div class="an-cohort-summary-card"><span class="an-cohort-summary-label">Latest activity</span><strong class="an-cohort-summary-value">—</strong><span class="an-cohort-summary-note">Periode terakhir</span></div>
        <div class="an-cohort-summary-card"><span class="an-cohort-summary-label">Data confidence</span><strong class="an-cohort-summary-value">—</strong><span class="an-cohort-summary-note">Coverage / retention</span></div>
    </div>

    <div class="an-cohort-layout">
        <section class="an-enterprise-card an-cohort-matrix-card">
            <div class="an-enterprise-head an-cohort-panel-head">
                <div><div class="an-enterprise-title"><i class="bi bi-grid-3x3-gap me-1" style="color:#2563eb"></i> Cohort matrix</div><div class="an-enterprise-sub" id="anCohortMatrixSubtitle">Buka analisis untuk memuat matriks.</div></div>
                <div class="an-cohort-legend" aria-label="Cohort matrix legend"><span><i class="an-cohort-legend-swatch primary"></i> Higher value</span><span><i class="an-cohort-legend-swatch muted"></i> Unavailable</span></div>
            </div>
            <div class="an-enterprise-body an-cohort-matrix-body">
                <div class="an-cohort-note" id="anCohortNote" aria-live="polite">Buka tab Cohort untuk memuat data.</div>
                <div class="an-cohort-reading-bar" id="anCohortReadingBar">
                    <span class="an-cohort-reading-item"><i class="bi bi-people" aria-hidden="true"></i><strong>Base</strong><small>ukuran cohort</small></span>
                    <span class="an-cohort-reading-item"><i class="bi bi-calendar2-check" aria-hidden="true"></i><strong>M0</strong><small>bulan transaksi pertama</small></span>
                    <span class="an-cohort-reading-item"><i class="bi bi-arrow-right" aria-hidden="true"></i><strong>M1+</strong><small>bulan setelahnya</small></span>
                    <span class="an-cohort-reading-hint" id="anCohortMetricHint">Pilih metric untuk melihat definisinya.</span>
                </div>
                <div class="an-table-wrap an-cohort-table-wrap"><table id="anCohortTable" class="an-table an-cohort-table"><caption class="visually-hidden">Cohort matrix berdasarkan periode transaksi pertama</caption><thead id="anCohortHead"><tr><th>Cohort</th><th>Base</th><th>M0</th><th>M1</th><th>M2</th><th>M3</th><th>M4</th><th>M5</th></tr></thead><tbody id="anCohortBody" aria-live="polite"><tr><td colspan="8"><div class="an-empty">Buka tab Cohort untuk memuat data.</div></td></tr></tbody></table></div>
            </div>
        </section>
        <aside class="an-enterprise-card an-cohort-guide-card">
            <div class="an-enterprise-head"><div><div class="an-enterprise-title">How to read</div><div class="an-enterprise-sub">Panduan cepat untuk keputusan bisnis</div></div></div>
            <div class="an-enterprise-body">
                <div class="an-cohort-guide-list">
                    <div class="an-cohort-guide-item"><span class="an-cohort-guide-icon blue"><i class="bi bi-calendar2-event"></i></span><div><strong>M0 = first transaction</strong><p>Setiap cohort dimulai pada bulan transaksi pertama dalam scope aktif.</p></div></div>
                    <div class="an-cohort-guide-item"><span class="an-cohort-guide-icon green"><i class="bi bi-arrow-repeat"></i></span><div><strong>M1+ = returning activity</strong><p>Retention customer dihitung dari customer unik yang kembali bertransaksi.</p></div></div>
                    <div class="an-cohort-guide-item"><span class="an-cohort-guide-icon amber"><i class="bi bi-exclamation-circle"></i></span><div><strong>Blank ≠ zero</strong><p>Sel kosong berarti periode belum tersedia, bukan performa nol.</p></div></div>
                </div>
                <div class="an-cohort-guide-footer"><i class="bi bi-info-circle me-1"></i><span>Profit hanya dialokasikan saat settlement coverage tersedia.</span></div>
            </div>
        </aside>
    </div>
</section>

<div class="an-modal" id="cohortDetailModal" aria-hidden="true">
    <div class="an-modal-backdrop" data-cohort-close></div>
    <section class="an-modal-dialog an-cohort-detail-dialog" role="dialog" aria-modal="true" aria-labelledby="cohortDetailTitle">
        <div class="an-modal-head"><div><div class="an-modal-eyebrow">Cohort detail</div><div class="an-modal-title" id="cohortDetailTitle">Rincian cohort</div><div class="an-modal-sub" id="cohortDetailSubtitle">Periode aktif</div></div><button class="an-modal-close" type="button" data-cohort-close aria-label="Tutup">×</button></div>
        <div class="an-modal-summary" id="cohortDetailSummary"><div class="an-empty">Pilih sel cohort.</div></div>
        <div class="an-modal-body"><div class="an-cohort-note" id="cohortDetailNote">Detail ini mengikuti filter tab Cohort dan tidak membuka daftar identitas customer.</div></div>
    </section>
</div>
