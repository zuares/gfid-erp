<section class="an-enterprise-card an-executive-top an-tab-pane" data-an-pane="summary">
    <div class="an-enterprise-head"><div><div class="an-section-kicker"><span class="an-section-kicker-dot"></span>Executive overview</div><div class="an-enterprise-title">Executive pulse</div><div class="an-enterprise-sub">Volume, basket, dan exception operasional</div></div></div>
    <div class="an-enterprise-body"><div class="an-pulse-grid an-pulse-grid-executive" id="anPulseGrid"><div class="an-empty">Memuat insight…</div></div></div>
</section>

<section class="an-enterprise-card an-tab-pane" data-an-pane="summary">
    <div class="an-enterprise-head"><div><div class="an-section-kicker"><span class="an-section-kicker-dot"></span>Decision cockpit</div><div class="an-enterprise-title">KPI keputusan</div><div class="an-enterprise-sub">GMV → dana → biaya → estimasi net profit</div></div></div>
    <div class="an-enterprise-body"><div class="an-pulse-grid an-pulse-grid-finance an-decision-pulse-grid" id="anDecisionPulse"><div class="an-empty">Memuat KPI…</div></div></div>
</section>

<div class="an-grid-main an-grid-main-chart an-tab-pane" data-an-pane="summary">
    <section class="an-card"><div class="an-card-head"><div><div class="an-section-kicker"><span class="an-section-kicker-dot"></span>Performance trend</div><div class="an-card-title"><i class="bi bi-graph-up-arrow me-1" style="color:#16a34a"></i>Grafik harian</div><div class="an-card-sub">Omzet &amp; estimasi profit</div></div></div><div class="an-card-body"><div class="an-chart-panel-head"><div class="an-chart-panel-title">Performa harian</div><div class="an-chart-summary" id="chartCompareNote">Memuat…</div></div><div class="an-chart" id="revenueChart"><div class="an-empty">Memuat grafik…</div></div></div></section>
</div>

<section class="an-card an-health-wide an-tab-pane" data-an-pane="summary">
    <div class="an-card-head"><div><div class="an-section-kicker"><span class="an-section-kicker-dot"></span>Operational health</div><div class="an-card-title"><i class="bi bi-activity me-1" style="color:#2563eb"></i>Kesehatan order &amp; keuangan</div><div class="an-card-sub">Order operasional dan profit yang sudah tervalidasi.</div></div></div>
    <div class="an-card-body"><div class="an-funnel" id="salesFunnel"><div class="an-empty">Memuat…</div></div></div>
</section>

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
            <button class="an-modal-tab active" type="button" role="tab" aria-selected="true" data-cash-settlement="all">Semua status</button>
            <button class="an-modal-tab" type="button" role="tab" aria-selected="false" data-cash-settlement="settled">Sudah cair</button>
            <button class="an-modal-tab" type="button" role="tab" aria-selected="false" data-cash-settlement="shipped">Masih dikirim</button>
            <button class="an-modal-tab" type="button" role="tab" aria-selected="false" data-cash-settlement="confirm">Menunggu konfirmasi</button>
            <button class="an-modal-tab" type="button" role="tab" aria-selected="false" data-cash-settlement="cancelled">Dibatalkan</button>
            <button class="an-modal-tab" type="button" role="tab" aria-selected="false" data-cash-settlement="return_refund">Return / Refund</button>
            <button class="an-modal-tab" type="button" role="tab" aria-selected="false" data-cash-settlement="unsettled">Belum cair</button>
        </div>
        <div class="an-modal-summary" id="cashOrdersSummary"><div class="an-empty">Memuat ringkasan…</div></div>
        <div class="an-modal-body" id="cashOrdersBody"><div class="an-empty">Pilih status pencairan untuk memuat order.</div></div>
        <div class="an-modal-foot"><button class="an-btn" id="cashOrdersPrev" type="button">‹ Sebelumnya</button><span class="an-modal-page" id="cashOrdersPage">—</span><button class="an-btn" id="cashOrdersNext" type="button">Berikutnya ›</button></div>
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

<div class="an-enterprise-grid an-enterprise-grid-summary an-tab-pane" data-an-pane="summary">
    <section class="an-enterprise-card">
        <div class="an-enterprise-head"><div><div class="an-enterprise-title">Business health</div><div class="an-enterprise-sub">Kualitas data dan eksekusi order</div></div></div>
        <div class="an-enterprise-body"><div class="an-health-list" id="anHealthList"><div class="an-empty">Memuat health score…</div></div></div>
    </section>
</div>

<section class="an-enterprise-card an-tab-pane" data-an-pane="summary">
    <div class="an-enterprise-head"><div><div class="an-enterprise-title">Management attention</div><div class="an-enterprise-sub">Prioritas yang perlu ditindaklanjuti pada periode ini</div></div></div>
    <div class="an-enterprise-body"><div class="an-alerts" id="anAlerts"><div class="an-empty">Memuat alert…</div></div></div>
</section>

<div class="an-contribution-grid an-tab-pane" data-an-pane="summary">
    <section class="an-enterprise-card"><div class="an-enterprise-head"><div><div class="an-enterprise-title">Kontribusi toko</div><div class="an-enterprise-sub">Toko dengan kontribusi omzet terbesar</div></div></div><div class="an-enterprise-body"><div class="an-contribution-list" id="anTopStores"><div class="an-empty">Memuat…</div></div></div></section>
    <section class="an-enterprise-card"><div class="an-enterprise-head"><div><div class="an-enterprise-title">Unit economics</div><div class="an-enterprise-sub">Omzet total, pencairan, dan biaya aktual</div></div><div class="an-economics-legend" aria-label="Legenda status omzet"><span><i></i>Cair</span><span><i class="pending"></i>Belum cair</span></div></div><div class="an-enterprise-body"><div class="an-contribution-list" id="anEconomics"><div class="an-empty">Memuat…</div></div></div></section>
</div>
