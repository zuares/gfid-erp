<section class="an-revenue-workspace an-tab-pane is-hidden" data-an-pane="revenue" role="tabpanel" tabindex="-1" aria-labelledby="revenueWorkspaceTitle">
    <div class="an-revenue-head">
        <div class="an-revenue-head-copy">
            <span class="an-revenue-head-icon" aria-hidden="true"><i class="bi bi-wallet2"></i></span>
            <div>
            <div class="an-section-kicker"><span class="an-section-kicker-dot"></span>Financial ledger</div>
            <div class="an-enterprise-title" id="revenueWorkspaceTitle">Omzet, Pencairan &amp; Fee</div>
            <div class="an-enterprise-sub">Omzet, payout, dan fee per order.</div>
            </div>
        </div>
        <div class="an-revenue-head-meta">
            <span class="an-revenue-status"><i class="bi bi-broadcast-pin" aria-hidden="true"></i><span>Ledger aktif</span></span>
            <span class="an-revenue-period"><i class="bi bi-calendar3" aria-hidden="true"></i><span id="revenueOrdersSubtitle">Periode aktif</span></span>
        </div>
    </div>

    <div class="an-revenue-panel" id="revenueOrdersPanel" role="tabpanel" tabindex="-1" aria-labelledby="revenueOrdersTabs">
        <div class="an-modal-tabs an-revenue-tabs" id="revenueOrdersTabs" role="tablist" aria-label="Filter status omzet dan pencairan">
            <button class="an-modal-tab active" type="button" role="tab" aria-selected="true" aria-controls="revenueOrdersPanel" tabindex="0" data-revenue-settlement="all"><span class="an-revenue-tab-label"><i class="bi bi-grid-1x2" aria-hidden="true"></i><span>Pesanan</span></span><small data-revenue-tab-kpi>—</small></button>
            <button class="an-modal-tab" type="button" role="tab" aria-selected="false" aria-controls="revenueOrdersPanel" tabindex="-1" data-revenue-settlement="settled"><span class="an-revenue-tab-label"><i class="bi bi-check2-circle" aria-hidden="true"></i><span>Sudah cair</span></span><small data-revenue-tab-kpi>—</small></button>
            <button class="an-modal-tab" type="button" role="tab" aria-selected="false" aria-controls="revenueOrdersPanel" tabindex="-1" data-revenue-settlement="shipped"><span class="an-revenue-tab-label"><i class="bi bi-truck" aria-hidden="true"></i><span>Masih dikirim</span></span><small data-revenue-tab-kpi>—</small></button>
            <button class="an-modal-tab" type="button" role="tab" aria-selected="false" aria-controls="revenueOrdersPanel" tabindex="-1" data-revenue-settlement="warehouse"><span class="an-revenue-tab-label"><i class="bi bi-box-seam" aria-hidden="true"></i><span>Disimpan di Gudang Shopee</span></span><small data-revenue-tab-kpi>—</small></button>
            <button class="an-modal-tab" type="button" role="tab" aria-selected="false" aria-controls="revenueOrdersPanel" tabindex="-1" data-revenue-settlement="confirm"><span class="an-revenue-tab-label"><i class="bi bi-hourglass-split" aria-hidden="true"></i><span>Menunggu konfirmasi</span></span><small data-revenue-tab-kpi>—</small></button>
            <button class="an-modal-tab" type="button" role="tab" aria-selected="false" aria-controls="revenueOrdersPanel" tabindex="-1" data-revenue-settlement="cancelled"><span class="an-revenue-tab-label"><i class="bi bi-x-circle" aria-hidden="true"></i><span>Dibatalkan</span></span><small data-revenue-tab-kpi>—</small></button>
            <button class="an-modal-tab" type="button" role="tab" aria-selected="false" aria-controls="revenueOrdersPanel" tabindex="-1" data-revenue-settlement="return_refund"><span class="an-revenue-tab-label"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i><span>Return / Refund</span></span><small data-revenue-tab-kpi>—</small></button>
            <button class="an-modal-tab" type="button" role="tab" aria-selected="false" aria-controls="revenueOrdersPanel" tabindex="-1" data-revenue-settlement="unsettled"><span class="an-revenue-tab-label"><i class="bi bi-clock-history" aria-hidden="true"></i><span>Dana belum cair</span></span><small data-revenue-tab-kpi>—</small></button>
            <button class="an-modal-tab an-revenue-fee-tab" type="button" role="tab" aria-selected="false" aria-controls="revenueOrdersPanel" tabindex="-1" data-revenue-fee-tab><span class="an-revenue-tab-label"><i class="bi bi-receipt" aria-hidden="true"></i><span>Fee Marketplace</span></span><small data-revenue-fee-kpi>—</small></button>
        </div>
        <section class="an-revenue-section" aria-label="Ringkasan omzet dan biaya">
            <div class="an-modal-summary an-revenue-summary" id="revenueOrdersSummary"><x-gf.state type="loading" title="Memuat ringkasan…" /></div>
        </section>
        <section class="an-revenue-section an-revenue-detail-section" aria-label="Detail transaksi per order">
            <div class="an-modal-body an-revenue-body" id="revenueOrdersBody" aria-live="polite"><x-gf.state type="loading" title="Memuat data omzet…" /></div>
        </section>
        <div class="an-modal-foot"><button class="an-btn" id="revenueOrdersPrev" type="button" aria-label="Halaman sebelumnya">‹ Sebelumnya</button><div class="an-modal-pagination" aria-live="polite"><div><strong class="an-modal-page" id="revenueOrdersPage">—</strong><small class="an-modal-page-meta" id="revenueOrdersPageMeta">—</small></div></div><button class="an-btn" id="revenueOrdersNext" type="button" aria-label="Halaman berikutnya">Berikutnya ›</button></div>
    </div>
</section>
