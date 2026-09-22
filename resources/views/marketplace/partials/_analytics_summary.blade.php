<section class="an-enterprise-card an-executive-top an-tab-pane" data-an-pane="summary">
    <div class="an-enterprise-head"><div><div class="an-section-kicker"><span class="an-section-kicker-dot"></span>Executive overview</div><div class="an-enterprise-title">Executive pulse</div><div class="an-enterprise-sub">Volume, basket, dan exception operasional</div></div></div>
    <div class="an-enterprise-body"><div class="an-pulse-grid an-pulse-grid-executive" id="anPulseGrid"><div class="an-empty">Memuat insight…</div></div></div>
</section>

<section class="an-enterprise-card an-tab-pane" data-an-pane="summary">
    <div class="an-enterprise-head"><div><div class="an-section-kicker"><span class="an-section-kicker-dot"></span>Decision cockpit</div><div class="an-enterprise-title">KPI keputusan</div><div class="an-enterprise-sub">GMV → dana → biaya → estimasi net profit</div></div></div>
    <div class="an-enterprise-body"><div class="an-pulse-grid an-pulse-grid-finance an-decision-pulse-grid" id="anDecisionPulse"><div class="an-empty">Memuat KPI…</div></div></div>
</section>

<section class="an-control-tower an-tab-pane" data-an-pane="summary">
    <div class="an-control-head">
        <div>
            <div class="an-section-kicker"><span class="an-section-kicker-dot"></span>Executive control</div>
            <div class="an-control-title">Business control tower</div>
            <div class="an-control-sub">Ringkasan operasional, kualitas data, dan prioritas tindakan dalam satu tampilan.</div>
        </div>
        <div class="an-control-status"><span class="an-control-status-dot"></span><strong>Live monitoring</strong><small id="anControlPeriod">Periode aktif</small></div>
    </div>
    <div class="an-control-grid">
        <section class="an-control-panel an-control-flow">
            <div class="an-control-panel-head"><div><div class="an-control-panel-kicker">Operational health</div><div class="an-control-panel-title">Order-to-cash flow</div><div class="an-control-panel-sub">Alur order dari masuk sampai profit tervalidasi.</div></div><span class="an-control-panel-value" id="anFlowStatus">—</span></div>
            <div class="an-control-panel-body"><div class="an-funnel" id="salesFunnel"><div class="an-empty">Memuat…</div></div></div>
        </section>
        <div class="an-control-column">
            <section class="an-control-panel">
                <div class="an-control-panel-head"><div><div class="an-control-panel-kicker">Business health</div><div class="an-control-panel-title">Execution quality</div><div class="an-control-panel-sub">Kualitas data dan eksekusi order.</div></div><span class="an-health-score" id="anHealthScore">—</span></div>
                <div class="an-control-panel-body"><div class="an-health-list" id="anHealthList"><div class="an-empty">Memuat health score…</div></div></div>
            </section>
            <section class="an-control-panel">
                <div class="an-control-panel-head"><div><div class="an-control-panel-kicker">Management attention</div><div class="an-control-panel-title">Priority actions</div><div class="an-control-panel-sub">Exception yang perlu ditindaklanjuti.</div></div><span class="an-alert-count" id="anAlertCount">—</span></div>
                <div class="an-control-panel-body"><div class="an-alerts" id="anAlerts"><div class="an-empty">Memuat alert…</div></div></div>
            </section>
        </div>
    </div>
</section>

<div class="an-grid-main an-grid-main-chart an-tab-pane" data-an-pane="summary">
    <section class="an-card"><div class="an-card-head"><div><div class="an-section-kicker"><span class="an-section-kicker-dot"></span>Performance trend</div><div class="an-card-title"><i class="bi bi-graph-up-arrow me-1" style="color:#16a34a"></i>Grafik harian</div><div class="an-card-sub">Omzet &amp; estimasi profit</div></div></div><div class="an-card-body"><div class="an-chart-panel-head"><div class="an-chart-panel-title">Performa harian</div><div class="an-chart-summary" id="chartCompareNote">Memuat…</div></div><div class="an-chart" id="revenueChart"><div class="an-empty">Memuat grafik…</div></div></div></section>
</div>

<div class="an-modal" id="cashOrdersModal" aria-hidden="true">
    <div class="an-modal-backdrop" data-cash-close></div>
    <section class="an-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="cashOrdersTitle">
        <div class="an-modal-head">
            <div>
                <div class="an-modal-eyebrow">Status order &amp; pencairan</div>
                <div class="an-modal-title" id="cashOrdersTitle">Status pencairan order</div>
                <div class="an-modal-sub" id="cashOrdersSubtitle">Periode aktif</div>
            </div>
            <button class="an-modal-close" type="button" data-cash-close aria-label="Tutup">×</button>
        </div>
        <div class="an-modal-tabs" id="cashOrdersTabs" role="tablist" aria-label="Status pencairan order">
            <button class="an-modal-tab active" type="button" role="tab" aria-selected="true" data-cash-settlement="all"><span>Semua status</span><small data-cash-tab-kpi>—</small></button>
            <button class="an-modal-tab" type="button" role="tab" aria-selected="false" data-cash-settlement="settled"><span>Sudah cair</span><small data-cash-tab-kpi>—</small></button>
            <button class="an-modal-tab" type="button" role="tab" aria-selected="false" data-cash-settlement="shipped"><span>Masih dikirim</span><small data-cash-tab-kpi>—</small></button>
            <button class="an-modal-tab" type="button" role="tab" aria-selected="false" data-cash-settlement="warehouse"><span>Disimpan di Gudang Shopee</span><small data-cash-tab-kpi>—</small></button>
            <button class="an-modal-tab" type="button" role="tab" aria-selected="false" data-cash-settlement="confirm"><span>Menunggu konfirmasi</span><small data-cash-tab-kpi>—</small></button>
            <button class="an-modal-tab" type="button" role="tab" aria-selected="false" data-cash-settlement="cancelled"><span>Dibatalkan</span><small data-cash-tab-kpi>—</small></button>
            <button class="an-modal-tab" type="button" role="tab" aria-selected="false" data-cash-settlement="return_refund"><span>Return / Refund</span><small data-cash-tab-kpi>—</small></button>
            <button class="an-modal-tab" type="button" role="tab" aria-selected="false" data-cash-settlement="unsettled"><span>Dana belum cair</span><small data-cash-tab-kpi>—</small></button>
        </div>
        <div class="an-modal-summary" id="cashOrdersSummary"><div class="an-empty">Memuat ringkasan…</div></div>
        <div class="an-modal-body" id="cashOrdersBody"><div class="an-empty">Pilih status pencairan untuk memuat order.</div></div>
        <div class="an-modal-foot"><button class="an-btn" id="cashOrdersPrev" type="button" aria-label="Halaman sebelumnya">‹ Sebelumnya</button><div class="an-modal-pagination" aria-live="polite"><div><strong class="an-modal-page" id="cashOrdersPage">—</strong><small class="an-modal-page-meta" id="cashOrdersPageMeta">—</small></div></div><button class="an-btn" id="cashOrdersNext" type="button" aria-label="Halaman berikutnya">Berikutnya ›</button></div>
    </section>
</div>

<div class="an-modal" id="returnOrdersModal" aria-hidden="true">
    <div class="an-modal-backdrop" data-return-close></div>
    <section class="an-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="returnOrdersTitle">
        <div class="an-modal-head">
            <div>
                <div class="an-modal-eyebrow">Operational exceptions</div>
                <div class="an-modal-title" id="returnOrdersTitle">Return dan pengiriman</div>
                <div class="an-modal-sub" id="returnOrdersSubtitle">Periode aktif</div>
            </div>
            <button class="an-modal-close" type="button" data-return-close aria-label="Tutup">×</button>
        </div>
        <div class="an-modal-tabs" id="returnOrdersTabs" role="tablist" aria-label="Jenis exception order">
            <button class="an-modal-tab active" type="button" role="tab" aria-selected="true" data-return-type="return_refund">Return / refund</button>
            <button class="an-modal-tab" type="button" role="tab" aria-selected="false" data-return-type="failed_delivery">Pengiriman gagal</button>
        </div>
        <div class="an-modal-summary" id="returnOrdersSummary"><div class="an-empty">Memuat ringkasan…</div></div>
        <div class="an-modal-body" id="returnOrdersBody"><div class="an-empty">Pilih jenis exception untuk memuat data.</div></div>
        <div class="an-modal-foot"><button class="an-btn" id="returnOrdersPrev" type="button">‹ Sebelumnya</button><span class="an-modal-page" id="returnOrdersPage">—</span><button class="an-btn" id="returnOrdersNext" type="button">Berikutnya ›</button></div>
    </section>
</div>

<div class="an-contribution-grid an-tab-pane" data-an-pane="summary">
    <section class="an-enterprise-card"><div class="an-enterprise-head"><div><div class="an-enterprise-title">Kontribusi toko</div><div class="an-enterprise-sub">Toko dengan kontribusi omzet terbesar</div></div></div><div class="an-enterprise-body"><div class="an-contribution-list" id="anTopStores"><div class="an-empty">Memuat…</div></div></div></section>
    <section class="an-enterprise-card"><div class="an-enterprise-head"><div><div class="an-enterprise-title">Unit economics</div><div class="an-enterprise-sub">Omzet total, pencairan, dan biaya aktual</div></div><div class="an-economics-legend" aria-label="Legenda status omzet"><span><i></i>Cair</span><span><i class="pending"></i>Belum cair</span></div></div><div class="an-enterprise-body"><div class="an-contribution-list" id="anEconomics"><div class="an-empty">Memuat…</div></div></div></section>
</div>
