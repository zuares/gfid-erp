<section class="an-revenue-workspace an-tab-pane is-hidden" data-an-pane="revenue" aria-labelledby="revenueWorkspaceTitle">
    <div class="an-revenue-head">
        <div>
            <div class="an-section-kicker"><span class="an-section-kicker-dot"></span>Financial order ledger</div>
            <div class="an-enterprise-title" id="revenueWorkspaceTitle">Omzet, Pencairan &amp; Fee</div>
            <div class="an-enterprise-sub">Rincian omzet, status pencairan, fee marketplace aktual, dan payout per order pada periode aktif.</div>
        </div>
        <div class="an-revenue-head-meta"><i class="bi bi-info-circle" aria-hidden="true"></i><span id="revenueOrdersSubtitle">Periode aktif</span></div>
    </div>

    <div class="an-revenue-panel">
        <div class="an-modal-tabs an-revenue-tabs" id="revenueOrdersTabs" role="tablist" aria-label="Filter status omzet dan pencairan">
            <button class="an-modal-tab active" type="button" role="tab" aria-selected="true" data-revenue-settlement="all"><span>Pesanan</span><small data-revenue-tab-kpi>—</small></button>
            <button class="an-modal-tab" type="button" role="tab" aria-selected="false" data-revenue-settlement="settled"><span>Sudah cair</span><small data-revenue-tab-kpi>—</small></button>
            <button class="an-modal-tab" type="button" role="tab" aria-selected="false" data-revenue-settlement="shipped"><span>Masih dikirim</span><small data-revenue-tab-kpi>—</small></button>
            <button class="an-modal-tab" type="button" role="tab" aria-selected="false" data-revenue-settlement="warehouse"><span>Disimpan di Gudang Shopee</span><small data-revenue-tab-kpi>—</small></button>
            <button class="an-modal-tab" type="button" role="tab" aria-selected="false" data-revenue-settlement="confirm"><span>Menunggu konfirmasi</span><small data-revenue-tab-kpi>—</small></button>
            <button class="an-modal-tab" type="button" role="tab" aria-selected="false" data-revenue-settlement="cancelled"><span>Dibatalkan</span><small data-revenue-tab-kpi>—</small></button>
            <button class="an-modal-tab" type="button" role="tab" aria-selected="false" data-revenue-settlement="return_refund"><span>Return / Refund</span><small data-revenue-tab-kpi>—</small></button>
            <button class="an-modal-tab" type="button" role="tab" aria-selected="false" data-revenue-settlement="unsettled"><span>Dana belum cair</span><small data-revenue-tab-kpi>—</small></button>
        </div>
        <div class="an-modal-summary" id="revenueOrdersSummary"><div class="an-empty">Memuat ringkasan…</div></div>
        <div class="an-modal-body an-revenue-body" id="revenueOrdersBody"><div class="an-empty">Memuat data omzet…</div></div>
        <div class="an-modal-foot"><button class="an-btn" id="revenueOrdersPrev" type="button" aria-label="Halaman sebelumnya">‹ Sebelumnya</button><div class="an-modal-pagination" aria-live="polite"><div><strong class="an-modal-page" id="revenueOrdersPage">—</strong><small class="an-modal-page-meta" id="revenueOrdersPageMeta">—</small></div></div><button class="an-btn" id="revenueOrdersNext" type="button" aria-label="Halaman berikutnya">Berikutnya ›</button></div>
    </div>
</section>
