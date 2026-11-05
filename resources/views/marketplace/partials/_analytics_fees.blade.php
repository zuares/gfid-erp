<section class="an-fee-analysis an-tab-pane is-hidden" data-an-pane="fees" role="tabpanel" tabindex="-1" aria-labelledby="feeWorkspaceTitle">
    <header class="an-fee-analysis-head">
        <div class="an-fee-analysis-head-copy">
            <span class="an-fee-analysis-icon" aria-hidden="true"><i class="bi bi-receipt"></i></span>
            <div>
                <div class="an-section-kicker"><span class="an-section-kicker-dot"></span>Fee ledger</div>
                <h2 id="feeWorkspaceTitle">Fee Marketplace</h2>
                <p>Bandingkan beban fee berdasarkan tipe, toko, dan order untuk menemukan kebocoran margin.</p>
            </div>
        </div>
        <div class="an-fee-analysis-meta">
            <span class="an-revenue-status"><i class="bi bi-check2-circle" aria-hidden="true"></i><span>Settlement complete</span></span>
            <span class="an-revenue-period"><i class="bi bi-calendar3" aria-hidden="true"></i><span id="feeOrdersSubtitle">Periode aktif</span></span>
        </div>
    </header>

    <div class="an-fee-analysis-summary ds-grid" id="feeOrdersSummary" aria-label="Ringkasan biaya marketplace">
        <x-gf.state type="loading" title="Memuat ringkasan fee…" />
    </div>

    <div class="an-fee-analysis-grid">
        <article class="an-fee-analysis-card" aria-labelledby="feeByTypeTitle">
            <div class="an-fee-analysis-card-head">
                <div><span class="an-fee-analysis-kicker">Komposisi biaya</span><h3 id="feeByTypeTitle">Fee by type</h3></div>
                <div class="an-fee-analysis-head-metrics">
                    <div class="an-fee-composition-total" aria-live="polite">
                        <span>Total fee seller</span>
                        <strong id="feeByTypeSellerTotal">—</strong>
                        <small id="feeByTypeSellerPercent">— dari total fee</small>
                    </div>
                    <span class="an-fee-analysis-count" id="feeByTypeCount">— tipe</span>
                </div>
            </div>
            <div class="an-fee-analysis-table-wrap" id="feeByTypeTable" aria-live="polite"><x-gf.state type="loading" title="Memuat tipe fee…" /></div>
        </article>

        <article class="an-fee-analysis-card" aria-labelledby="feeByStoreTitle">
            <div class="an-fee-analysis-card-head">
                <div><span class="an-fee-analysis-kicker">Performa channel</span><h3 id="feeByStoreTitle">Fee by store</h3></div>
                <span class="an-fee-analysis-count" id="feeByStoreCount">— toko</span>
            </div>
            <div class="an-fee-analysis-table-wrap" id="feeByStoreTable" aria-live="polite"><x-gf.state type="loading" title="Memuat toko…" /></div>
        </article>

        <article class="an-fee-analysis-card an-fee-analysis-card-wide" aria-labelledby="feeByOrderTitle">
            <div class="an-fee-analysis-card-head">
                <div><span class="an-fee-analysis-kicker">Drill-down</span><h3 id="feeByOrderTitle">Fee by order</h3></div>
                <span class="an-fee-analysis-count" id="feeByOrderCount">— order</span>
            </div>
            <div class="an-fee-analysis-table-wrap" id="feeOrdersBody" aria-live="polite"><x-gf.state type="loading" title="Memuat order cair…" /></div>
            <footer class="an-fee-analysis-footer">
                <button class="an-btn" id="feeOrdersPrev" type="button" aria-label="Halaman fee sebelumnya">‹ Sebelumnya</button>
                <div class="an-modal-pagination" aria-live="polite"><strong class="an-modal-page" id="feeOrdersPage">—</strong><small class="an-modal-page-meta" id="feeOrdersPageMeta">—</small></div>
                <button class="an-btn" id="feeOrdersNext" type="button" aria-label="Halaman fee berikutnya">Berikutnya ›</button>
            </footer>
        </article>
    </div>
</section>
