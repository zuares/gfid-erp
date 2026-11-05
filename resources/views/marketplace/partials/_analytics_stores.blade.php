<section class="an-enterprise-card an-tab-pane is-hidden" data-an-pane="stores">
    <div class="an-enterprise-head"><div><div class="an-section-kicker"><span class="an-section-kicker-dot"></span>Account performance</div><div class="an-enterprise-title">Executive pulse</div><div class="an-enterprise-sub">Efisiensi toko berbasis omzet dan profit yang sudah siap secara finansial.</div></div></div>
    <div class="an-enterprise-body"><div class="an-pulse-grid an-pulse-grid-finance" id="anFinancePulse"><x-gf.state type="loading" title="Memuat…" /></div></div>
</section>

<div class="an-tab-pane is-hidden" data-an-pane="stores">
    <div class="an-kpis">
        <x-gf.comparison-kpi label="Total Order" value-id="kpiOrders" note-id="kpiOrdersNote" icon="bi-bag-check" info-id="kpiOrdersInfo" info-label="Penjelasan Total Order" tooltip="Order aktif yang sudah dibayar; belum bayar, batal, dan return/refund dikecualikan"><span title="Sudah cair"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> —</span> · <span title="Belum cair"><i class="bi bi-clock-history" aria-hidden="true"></i> —</span></x-gf.comparison-kpi>
        <x-gf.comparison-kpi class="kpi-cancelled" label="Dibatalkan" value-id="kpiCancelled" note-id="kpiCancelledNote" icon="bi-x-circle" info-id="kpiCancelledInfo" info-label="Penjelasan order dibatalkan" tooltip="Order batal dipisahkan dan tidak masuk omzet maupun estimasi profit"><span title="Nilai order batal"><i class="bi bi-x-circle" aria-hidden="true"></i> —</span> · tidak masuk omzet</x-gf.comparison-kpi>
        <x-gf.comparison-kpi label="Produk Terjual" value-id="kpiProductsSold" note-id="kpiProductsSoldNote" icon="bi-box-seam" info-id="kpiProductsSoldInfo" info-label="Penjelasan Produk Terjual" tooltip="Total unit · cair · pending · refund"><span title="Produk sudah cair"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> —</span> · <span title="Produk pending"><i class="bi bi-clock-history" aria-hidden="true"></i> —</span> · <span title="Produk refund"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> —</span></x-gf.comparison-kpi>
        <x-gf.comparison-kpi label="Omzet Eligible / Gross Sales" value-id="kpiRevenue" note-id="kpiRevenueNote" icon="bi-graph-up-arrow" info-id="kpiRevenueInfo" info-label="Penjelasan Omzet Eligible atau Gross Sales" tooltip="Omzet dari order yang sudah dibayar; belum bayar, batal, dan return/refund dikecualikan"><span title="Sudah cair"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> —</span> · <span title="Estimasi belum cair"><i class="bi bi-clock-history" aria-hidden="true"></i> —</span> · <span title="Belum bayar/batal/refund dikecualikan"><i class="bi bi-shield-check" aria-hidden="true"></i> belum bayar/batal/refund dikecualikan</span></x-gf.comparison-kpi>
        <x-gf.comparison-kpi label="Prakiraan Admin Fee" value-id="kpiAdminFee" note-id="kpiAdminFeeNote" icon="bi-receipt" info-id="kpiAdminFeeInfo" info-label="Penjelasan Prakiraan Admin Fee" tooltip="Omzet × rate fee actual · komposisi fee">komposisi fee actual</x-gf.comparison-kpi>
        <x-gf.comparison-kpi label="Prakiraan Cair / Net sales" value-id="kpiEstimatedCash" note-id="kpiEstimatedCashNote" icon="bi-wallet2" info-id="kpiEstimatedCashInfo" info-label="Penjelasan Prakiraan Cair atau Net sales" tooltip="Omzet − admin fee · net cair + estimasi"><span title="Net sudah cair"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> —</span> · <span title="Estimasi net belum cair"><i class="bi bi-clock-history" aria-hidden="true"></i> —</span> · <span title="Return/refund"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> —</span></x-gf.comparison-kpi>
        <x-gf.comparison-kpi label="Total HPP / COGS" value-id="kpiHpp" note-id="kpiHppKpiNote" icon="bi-box-seam" info-id="kpiHppInfo" info-label="Penjelasan Total HPP atau COGS" tooltip="Total HPP · cair · belum cair · return/refund"><span title="HPP sudah cair"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> —</span> · <span title="HPP belum cair"><i class="bi bi-clock-history" aria-hidden="true"></i> —</span> · <span title="HPP return/refund"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> —</span></x-gf.comparison-kpi>
        <x-gf.comparison-kpi label="Biaya Iklan (incl. PPN)" value-id="kpiAdCost" note-id="kpiAdCostNote" icon="bi-megaphone"><span title="Biaya iklan sebelum PPN"><i class="bi bi-receipt" aria-hidden="true"></i> —</span> · <span title="PPN 11%"><i class="bi bi-percent" aria-hidden="true"></i> —</span> · <span title="Biaya per order"><i class="bi bi-bag" aria-hidden="true"></i> —</span></x-gf.comparison-kpi>
        <x-gf.comparison-kpi label="Omzet Cair / Net Revenue" value-id="kpiPayout" note-id="kpiPayoutNote" icon="bi-cash-coin"><span title="Order settlement complete"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> —</span> · <span title="Payout rate"><i class="bi bi-percent" aria-hidden="true"></i> —</span></x-gf.comparison-kpi>
        <x-gf.comparison-kpi class="kpi-refund" label="Refund / Return" value-id="kpiRefund" note-id="kpiRefundNote" icon="bi-arrow-counterclockwise" info-id="kpiRefundInfo" info-label="Penjelasan refund atau return" tooltip="Nilai refund dipisahkan dan tidak masuk omzet maupun estimasi profit"><span title="Jumlah order refund"><i class="bi bi-bag" aria-hidden="true"></i> —</span> · rate —</x-gf.comparison-kpi>
        <x-gf.comparison-kpi label="Laba Kotor / Gross Profit" value-id="kpiGrossProfit" note-id="kpiHppNote" icon="bi-bar-chart-line" info-id="kpiGrossProfitInfo" info-label="Penjelasan Laba Kotor atau Gross Profit" tooltip="Net revenue − total HPP"><span title="Net revenue"><i class="bi bi-cash-stack" aria-hidden="true"></i> —</span> · <span title="Total HPP"><i class="bi bi-box-seam" aria-hidden="true"></i> —</span></x-gf.comparison-kpi>
        <x-gf.comparison-kpi label="Est. Laba Bersih / Est. Net Profit" value-id="kpiEstimatedProfit" note-id="kpiEstimatedProfitNote" icon="bi-graph-up-arrow"><span title="Margin estimasi"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i> —</span> · <span title="Net revenue − HPP − iklan"><i class="bi bi-calculator" aria-hidden="true"></i> estimasi</span></x-gf.comparison-kpi>
        <x-gf.comparison-kpi label="Laba Operasional / Net Operasional" value-id="kpiOperatingProfit" note-id="kpiOperatingProfitNote" icon="bi-activity"><span title="Omzet cair"><i class="bi bi-cash-coin" aria-hidden="true"></i> —</span> · <span title="Total HPP + iklan"><i class="bi bi-dash-circle" aria-hidden="true"></i> —</span></x-gf.comparison-kpi>
    </div>
</div>

<section class="an-portfolio-section an-enterprise-card an-tab-pane is-hidden" data-an-pane="stores">
    <div class="an-enterprise-head">
        <div>
            <div class="an-section-kicker"><span class="an-section-kicker-dot"></span>Portfolio view</div>
            <div class="an-enterprise-title">Store performance snapshot</div>
            <div class="an-enterprise-sub">Satu ruang untuk membaca performa toko, efisiensi biaya, dan ledger operasional.</div>
        </div>
        <span class="an-product-scope-pill"><i class="bi bi-shop-window" aria-hidden="true"></i> Store portfolio</span>
    </div>
    <div class="an-portfolio-section-body">
        <div class="an-portfolio-overview-grid">
            <section class="an-portfolio-subsection">
                <div class="an-portfolio-subsection-head">
                    <div>
                        <div class="an-section-kicker"><span class="an-section-kicker-dot"></span>Store performance</div>
                        <div class="an-card-title">Ranking toko</div>
                        <div class="an-card-sub">Gross sales dan profit operasional terverifikasi.</div>
                    </div>
                </div>
                <div class="an-pulse-grid" id="anStorePulse"><x-gf.state type="loading" title="Memuat…" /></div>
            </section>
            <section class="an-portfolio-subsection">
                <div class="an-portfolio-subsection-head">
                    <div>
                        <div class="an-section-kicker"><span class="an-section-kicker-dot"></span>Cost structure</div>
                        <div class="an-card-title">Cost efficiency</div>
                        <div class="an-card-sub">Rasio biaya terhadap gross sales terverifikasi.</div>
                    </div>
                </div>
                <div class="an-health-list" id="anStoreCostPulse"><x-gf.state type="loading" title="Memuat…" /></div>
            </section>
        </div>

        <div class="an-portfolio-ledger-grid">
            <section class="an-portfolio-subsection an-portfolio-table-section">
                <div class="an-portfolio-subsection-head">
                    <div>
                        <div class="an-section-kicker"><span class="an-section-kicker-dot"></span>Store ledger</div>
                        <div class="an-card-title">Performa per toko</div>
                        <div class="an-card-sub">Settlement complete, item valid, dan HPP tersedia.</div>
                    </div>
                </div>
                <div class="an-table-wrap"><table class="an-table"><thead><tr><th>Toko</th><th>Order eligible</th><th>Selesai</th><th>Cancel</th><th>Gross sales terverifikasi</th><th>Adm fee aktual</th><th>Biaya iklan</th><th>Profit operasional</th></tr></thead><tbody id="storeBody"><tr><td colspan="8"><x-gf.state type="loading" title="Memuat…" /></td></tr></tbody></table></div>
            </section>
            <section class="an-portfolio-subsection">
                <div class="an-portfolio-subsection-head">
                    <div>
                        <div class="an-section-kicker"><span class="an-section-kicker-dot"></span>Cost ledger</div>
                        <div class="an-card-title">Biaya marketplace</div>
                        <div class="an-card-sub">Fee estimasi dan biaya settlement untuk audit.</div>
                    </div>
                </div>
                <div class="an-costs" id="costBody"><x-gf.state type="loading" title="Memuat…" /></div>
            </section>
        </div>
    </div>
</section>

<section class="an-product-snapshot-section an-enterprise-card an-tab-pane is-hidden" data-an-pane="stores">
    <div class="an-enterprise-head">
        <div>
            <div class="an-section-kicker"><span class="an-section-kicker-dot"></span>Product portfolio</div>
            <div class="an-enterprise-title">Product performance snapshot</div>
            <div class="an-enterprise-sub">Ringkasan produk teratas berdasarkan gross sales pada filter aktif.</div>
        </div>
        <button class="ds-btn ds-btn-ghost ds-btn-sm an-product-open-tab" type="button" data-open-products-tab><i class="bi bi-box-seam" aria-hidden="true"></i>Lihat semua produk</button>
    </div>
    <div class="an-product-snapshot-body">
        <div class="an-pulse-grid an-product-snapshot-pulse" id="anProductSnapshotPulse"><x-gf.state title="Menunggu data produk" message="Data produk akan dimuat saat portfolio dibuka." /></div>
        <div class="an-product-snapshot-content">
            <div class="an-product-snapshot-list" id="anProductSnapshotList"><x-gf.state title="Menunggu data produk" message="Belum ada ringkasan produk untuk ditampilkan." /></div>
            <div class="an-product-reading" id="anProductSnapshotReading"><i class="bi bi-info-circle" aria-hidden="true"></i><span>Profit operasional memperhitungkan HPP dan alokasi iklan.</span></div>
        </div>
    </div>
</section>
