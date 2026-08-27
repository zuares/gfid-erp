<section class="an-cohort-workspace an-tab-pane is-hidden" data-an-pane="cohort" aria-labelledby="cohortWorkspaceTitle">
    <header class="an-cohort-hero">
        <div class="an-cohort-hero-copy">
            <div class="an-section-kicker"><span class="an-section-kicker-dot"></span> Customer intelligence</div>
            <h2 class="an-cohort-title" id="cohortWorkspaceTitle">Cohort Analysis</h2>
            <p class="an-cohort-description">Ukur retensi customer dan perkembangan produk dari transaksi pertama sampai aktivitas berulang.</p>
        </div>
        <div class="an-cohort-hero-meta">
            <span class="an-status-pill"><i class="bi bi-database-check" aria-hidden="true"></i> SQL aggregated</span>
            <div class="an-cohort-period-label">Active period <strong id="anCohortPeriodLabel">—</strong></div>
        </div>
    </header>

    <section class="an-cohort-control-card" aria-labelledby="cohortControlsTitle">
        <div class="an-cohort-control-head">
            <div>
                <div class="an-cohort-control-eyebrow">Scope &amp; methodology</div>
                <h3 class="an-cohort-control-title" id="cohortControlsTitle"><i class="bi bi-sliders2 me-1" aria-hidden="true"></i>Analysis controls</h3>
                <p class="an-cohort-control-sub">Atur sudut pandang dan metric utama. Filter global periode serta toko tetap mengikuti toolbar Analytics.</p>
            </div>
            <button class="an-cohort-reset" id="anCohortReset" type="button"><i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>Reset filter</button>
        </div>

        <div class="an-cohort-control-grid an-cohort-primary-controls">
            <div class="an-cohort-field"><label for="anCohortMode">View</label><select id="anCohortMode"><option value="customer">Customer cohort</option><option value="product">Product cohort</option></select></div>
            <div class="an-cohort-field"><label for="anCohortMetric">Primary metric</label><select id="anCohortMetric"></select></div>
            <div class="an-cohort-run-wrap"><span class="an-cohort-run-hint">Changes apply to the matrix and charts</span><button class="an-btn an-btn-dark an-cohort-apply" id="anCohortApply" type="button"><i class="bi bi-play-fill me-1" aria-hidden="true"></i>Run analysis</button></div>
        </div>

        <details class="an-cohort-advanced" open>
            <summary><span><i class="bi bi-funnel me-1" aria-hidden="true"></i>Advanced dimensions</span><small>Optional filters for channel and catalog</small></summary>
            <div class="an-cohort-control-grid an-cohort-advanced-grid">
                <div class="an-cohort-field"><label for="anCohortMarketplace">Marketplace</label><select id="anCohortMarketplace"><option value="">All marketplaces</option></select></div>
                <div class="an-cohort-field"><label for="anCohortCategory">Category</label><select id="anCohortCategory"><option value="">All categories</option></select></div>
                <div class="an-cohort-field"><label for="anCohortProduct">Product</label><select id="anCohortProduct"><option value="">All products</option></select></div>
                <div class="an-cohort-field"><label for="anCohortSku">SKU</label><select id="anCohortSku"><option value="">All SKUs</option></select></div>
            </div>
        </details>

        <div class="an-cohort-active-filters" id="anCohortActiveFilters" aria-live="polite"><span class="an-cohort-filter-caption">Active scope</span><span class="an-filter-chip">Periode: <strong>—</strong></span><span class="an-filter-chip">Toko: <strong>Semua toko</strong></span></div>
    </section>

    <section class="an-cohort-summary-grid" id="anCohortKpis" aria-label="Cohort summary">
        <div class="an-cohort-summary-card is-primary"><span class="an-cohort-summary-label">Cohorts in view</span><strong class="an-cohort-summary-value">—</strong><span class="an-cohort-summary-note">Menunggu analisis</span></div>
        <div class="an-cohort-summary-card"><span class="an-cohort-summary-label">Primary metric</span><strong class="an-cohort-summary-value">—</strong><span class="an-cohort-summary-note">Metric aktif</span></div>
        <div class="an-cohort-summary-card"><span class="an-cohort-summary-label">Latest activity</span><strong class="an-cohort-summary-value">—</strong><span class="an-cohort-summary-note">Periode terakhir</span></div>
        <div class="an-cohort-summary-card"><span class="an-cohort-summary-label">Data confidence</span><strong class="an-cohort-summary-value">—</strong><span class="an-cohort-summary-note">Coverage / retention</span></div>
    </section>

    <section class="an-cohort-insights" aria-label="Cohort visual insights">
        <section class="an-enterprise-card an-cohort-chart-card">
            <div class="an-enterprise-head">
                <div><div class="an-cohort-panel-eyebrow">Trend view</div><div class="an-enterprise-title"><i class="bi bi-graph-up-arrow me-1" style="color:#2563eb" aria-hidden="true"></i>Progression by cohort age</div><div class="an-enterprise-sub" id="anCohortCurveSubtitle">Rata-rata metric per umur cohort.</div></div>
                <span class="an-cohort-panel-badge blue">M0 → latest</span>
            </div>
            <div class="an-enterprise-body an-cohort-chart-body"><div class="an-cohort-chart-canvas" id="anCohortCurveChart"><div class="an-empty">Buka analisis untuk memuat grafik.</div></div></div>
        </section>
        <section class="an-enterprise-card an-cohort-chart-card">
            <div class="an-enterprise-head">
                <div><div class="an-cohort-panel-eyebrow">Portfolio view</div><div class="an-enterprise-title"><i class="bi bi-bar-chart-line me-1" style="color:#16a34a" aria-hidden="true"></i>Cohort distribution</div><div class="an-enterprise-sub" id="anCohortDistributionSubtitle">Ukuran cohort berdasarkan bulan transaksi pertama.</div></div>
                <span class="an-cohort-panel-badge green">By month</span>
            </div>
            <div class="an-enterprise-body an-cohort-chart-body"><div class="an-cohort-chart-canvas" id="anCohortDistributionChart"><div class="an-empty">Buka analisis untuk memuat grafik.</div></div></div>
        </section>
    </section>

    <div class="an-cohort-detail-layout">
        <section class="an-enterprise-card an-cohort-matrix-card">
            <div class="an-enterprise-head an-cohort-panel-head">
                <div><div class="an-cohort-panel-eyebrow">Detail view</div><div class="an-enterprise-title"><i class="bi bi-grid-3x3-gap me-1" style="color:#2563eb" aria-hidden="true"></i>Cohort matrix</div><div class="an-enterprise-sub" id="anCohortMatrixSubtitle">Buka analisis untuk memuat matriks.</div></div>
                <div class="an-cohort-legend" aria-label="Cohort matrix legend"><span><i class="an-cohort-legend-swatch primary"></i> Higher value</span><span><i class="an-cohort-legend-swatch muted"></i> No activity</span></div>
            </div>
            <div class="an-enterprise-body an-cohort-matrix-body">
                <div class="an-cohort-note" id="anCohortNote" aria-live="polite">Buka tab Cohort untuk memuat data.</div>
                <div class="an-cohort-reading-bar" id="anCohortReadingBar">
                    <span class="an-cohort-reading-item"><i class="bi bi-people" aria-hidden="true"></i><strong>Base</strong><small>ukuran cohort</small></span>
                    <span class="an-cohort-reading-item"><i class="bi bi-calendar2-check" aria-hidden="true"></i><strong>M0</strong><small>bulan pertama</small></span>
                    <span class="an-cohort-reading-item"><i class="bi bi-arrow-right" aria-hidden="true"></i><strong>M1+</strong><small>bulan berikutnya</small></span>
                    <span class="an-cohort-reading-hint" id="anCohortMetricHint">Pilih metric untuk melihat definisinya.</span>
                </div>
                <div class="an-table-wrap an-cohort-table-wrap"><table id="anCohortTable" class="an-table an-cohort-table"><caption class="visually-hidden">Cohort matrix berdasarkan periode transaksi pertama</caption><thead id="anCohortHead"><tr><th>Cohort</th><th>Base</th><th>M0</th><th>M1</th><th>M2</th></tr></thead><tbody id="anCohortBody" aria-live="polite"><tr><td colspan="5"><div class="an-empty">Buka tab Cohort untuk memuat data.</div></td></tr></tbody></table></div>
            </div>
        </section>

        <aside class="an-enterprise-card an-cohort-guide-card" aria-labelledby="cohortGuideTitle">
            <div class="an-enterprise-head"><div><div class="an-cohort-panel-eyebrow">Decision support</div><div class="an-enterprise-title" id="cohortGuideTitle">How to read</div><div class="an-enterprise-sub">Panduan cepat untuk keputusan bisnis</div></div></div>
            <div class="an-enterprise-body">
                <div class="an-cohort-guide-list">
                    <div class="an-cohort-guide-item"><span class="an-cohort-guide-icon blue"><i class="bi bi-calendar2-event" aria-hidden="true"></i></span><div><strong>M0 = first transaction</strong><p>Setiap cohort dimulai pada bulan transaksi pertama dalam scope aktif.</p></div></div>
                    <div class="an-cohort-guide-item"><span class="an-cohort-guide-icon green"><i class="bi bi-arrow-repeat" aria-hidden="true"></i></span><div><strong>M1+ = returning activity</strong><p>Retention customer dihitung dari customer unik yang kembali bertransaksi.</p></div></div>
                    <div class="an-cohort-guide-item"><span class="an-cohort-guide-icon amber"><i class="bi bi-exclamation-circle" aria-hidden="true"></i></span><div><strong>Blank = no activity</strong><p>Sel dengan tanda dash berarti belum ada aktivitas pada umur cohort tersebut.</p></div></div>
                </div>
                <div class="an-cohort-guide-footer"><i class="bi bi-info-circle me-1" aria-hidden="true"></i><span>Profit hanya dialokasikan saat settlement coverage tersedia.</span></div>
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
