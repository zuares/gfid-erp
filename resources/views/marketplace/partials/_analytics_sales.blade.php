<section class="an-sales-pane an-tab-pane is-hidden" data-an-pane="sales" aria-labelledby="analyticsSalesTitle">
    <div class="an-sales-head">
        <div>
            <div class="an-section-kicker"><span class="an-section-kicker-dot"></span>Sales performance</div>
            <div class="an-sales-title" id="analyticsSalesTitle">Penjualan marketplace</div>
            <div class="an-sales-sub">Ringkasan penjualan harian dari data analytics aktif, dengan pembanding periode yang sama.</div>
        </div>
        <div class="an-sales-period"><i class="bi bi-calendar3" aria-hidden="true"></i><span id="anSalesPeriod">Periode aktif</span></div>
    </div>

    <div class="an-sales-kpi-grid" aria-label="KPI penjualan">
        <article class="an-sales-kpi an-sales-kpi--primary">
            <div class="an-sales-kpi-top"><span class="an-sales-kpi-label">Penjualan neto</span><span class="an-sales-kpi-icon"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i></span></div>
            <div class="an-sales-kpi-value" id="anSalesNetSales">—</div>
            <div class="an-sales-kpi-note" id="anSalesNetSalesNote">vs periode pembanding</div>
        </article>
        <article class="an-sales-kpi">
            <div class="an-sales-kpi-top"><span class="an-sales-kpi-label">Order penjualan</span><span class="an-sales-kpi-icon"><i class="bi bi-bag-check" aria-hidden="true"></i></span></div>
            <div class="an-sales-kpi-value" id="anSalesOrders">—</div>
            <div class="an-sales-kpi-note" id="anSalesOrdersNote">Order revenue</div>
        </article>
        <article class="an-sales-kpi an-sales-kpi--success">
            <div class="an-sales-kpi-top"><span class="an-sales-kpi-label">Estimasi payout</span><span class="an-sales-kpi-icon"><i class="bi bi-wallet2" aria-hidden="true"></i></span></div>
            <div class="an-sales-kpi-value" id="anSalesPayout">—</div>
            <div class="an-sales-kpi-note" id="anSalesPayoutNote">Cair + estimasi belum cair</div>
        </article>
        <article class="an-sales-kpi">
            <div class="an-sales-kpi-top"><span class="an-sales-kpi-label">Produk terjual</span><span class="an-sales-kpi-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></span></div>
            <div class="an-sales-kpi-value" id="anSalesQty">—</div>
            <div class="an-sales-kpi-note" id="anSalesQtyNote">Unit pada order revenue</div>
        </article>
        <article class="an-sales-kpi an-sales-kpi--success">
            <div class="an-sales-kpi-top"><span class="an-sales-kpi-label">Laba operasional</span><span class="an-sales-kpi-icon"><i class="bi bi-bar-chart-line" aria-hidden="true"></i></span></div>
            <div class="an-sales-kpi-value" id="anSalesProfit">—</div>
            <div class="an-sales-kpi-note" id="anSalesProfitNote">Payout − HPP − iklan</div>
        </article>
        <article class="an-sales-kpi an-sales-kpi--warning">
            <div class="an-sales-kpi-top"><span class="an-sales-kpi-label">Return / refund</span><span class="an-sales-kpi-icon"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i></span></div>
            <div class="an-sales-kpi-value" id="anSalesRefund">—</div>
            <div class="an-sales-kpi-note" id="anSalesRefundNote">Exception yang dikecualikan dari omzet</div>
        </article>
    </div>

    <section class="an-sales-trend-section">
        <div class="an-sales-section-head">
            <div>
                <div class="an-section-kicker"><span class="an-section-kicker-dot"></span>Daily sales trend</div>
                <div class="an-sales-section-title">Tren penjualan</div>
                <div class="an-sales-section-sub">Omzet terverifikasi dan profit operasional dari baris harian analytics.</div>
            </div>
            <div class="an-sales-header-meta"><span class="an-sales-period" id="anSalesCompareLabel">vs periode pembanding</span></div>
        </div>
        <div class="an-sales-trend-body">
            <div class="an-sales-trend-summary" id="anSalesTrendSummary"><div class="an-empty">Memuat ringkasan penjualan…</div></div>
            <div class="an-sales-trend-layout">
                <div class="an-sales-trend-panel">
                    <div class="an-sales-trend-toolbar"><span class="an-sales-trend-label">Penjualan harian</span><span class="an-sales-trend-total" id="anSalesTrendTotal">—</span></div>
                    <div class="an-sales-trend-chart" id="anSalesTrendChart"><div class="an-empty">Memuat grafik…</div></div>
                    <div class="an-sales-trend-legend"><span><i></i>Periode aktif</span><span><i class="previous"></i>Periode pembanding</span></div>
                </div>
                <div class="an-sales-trend-insights" id="anSalesTrendInsights"><div class="an-empty">Memuat insight…</div></div>
            </div>
        </div>
    </section>

    <section class="an-sales-table-section">
        <div class="an-sales-section-head">
            <div>
                <div class="an-sales-section-title">Rincian harian</div>
                <div class="an-sales-section-sub">Sumber data: <code>analytics-summary.daily</code>. Tidak mengubah perhitungan atau mengambil data dari dashboard sales.</div>
            </div>
        </div>
        <div class="an-table-wrap">
            <table class="an-table an-sales-table">
                <thead><tr><th>Tanggal</th><th>Omzet / GMV</th><th>Payout</th><th>Profit operasional</th><th>Iklan</th><th>Order siap profit</th></tr></thead>
                <tbody id="anSalesDailyBody"><tr><td colspan="6"><div class="an-empty">Memuat rincian harian…</div></td></tr></tbody>
            </table>
        </div>
    </section>
</section>
