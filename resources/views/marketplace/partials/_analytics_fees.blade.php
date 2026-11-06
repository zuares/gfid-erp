<section class="an-fee-analysis an-tab-pane is-hidden" data-an-pane="fees" role="tabpanel" tabindex="-1" aria-label="Fee Marketplace">
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

        <article class="an-fee-analysis-card" aria-labelledby="feePlatformTitle">
            <div class="an-fee-analysis-card-head">
                <div><span class="an-fee-analysis-kicker">Platform cost</span><h3 id="feePlatformTitle">Fee platform</h3></div>
                <span class="an-fee-analysis-count" id="feePlatformCount">— komponen</span>
            </div>
            <div class="an-fee-analysis-table-wrap" id="feePlatformTable" aria-live="polite"><x-gf.state type="loading" title="Memuat fee platform…" /></div>
        </article>

        <article class="an-fee-analysis-card" aria-labelledby="feeShippingTitle">
            <div class="an-fee-analysis-card-head">
                <div><span class="an-fee-analysis-kicker">Logistics cost</span><h3 id="feeShippingTitle">Ongkir &amp; logistik</h3></div>
                <span class="an-fee-analysis-count" id="feeShippingCount">— jenis biaya</span>
            </div>
            <div class="an-fee-analysis-table-wrap" id="feeShippingTable" aria-live="polite"><x-gf.state type="loading" title="Memuat ongkir dan logistik…" /></div>
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

<div class="an-modal" id="otherFeeModal" aria-hidden="true">
    <div class="an-modal-backdrop" data-other-fee-close></div>
    <section class="an-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="otherFeeTitle" aria-describedby="otherFeeSubtitle">
        <div class="an-modal-head">
            <div>
                <div class="an-modal-eyebrow">Other cost</div>
                <div class="an-modal-title" id="otherFeeTitle">Detail biaya lainnya</div>
                <div class="an-modal-sub" id="otherFeeSubtitle">Periode aktif · payout sudah cair</div>
            </div>
            <button class="an-modal-close" type="button" data-other-fee-close aria-label="Tutup detail biaya lainnya">×</button>
        </div>
        <div class="an-modal-summary ds-grid" id="otherFeeSummary" aria-label="Ringkasan biaya lainnya"></div>
        <div class="an-modal-body" id="otherFeeBody" aria-live="polite"></div>
        <div class="an-modal-foot">
            <span class="an-modal-sub">Tidak termasuk ongkir dan biaya platform yang sudah dipisahkan.</span>
            <button class="an-btn" type="button" data-other-fee-close>Tutup</button>
        </div>
    </section>
</div>

<div class="an-modal" id="sellerFeeModal" aria-hidden="true">
    <div class="an-modal-backdrop" data-seller-fee-close></div>
    <section class="an-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="sellerFeeTitle" aria-describedby="sellerFeeSubtitle">
        <div class="an-modal-head">
            <div>
                <div class="an-modal-eyebrow">Seller cost</div>
                <div class="an-modal-title" id="sellerFeeTitle">Detail biaya Seller</div>
                <div class="an-modal-sub" id="sellerFeeSubtitle">Pilih angka Seller untuk melihat komponennya</div>
            </div>
            <button class="an-modal-close" type="button" data-seller-fee-close aria-label="Tutup detail biaya Seller">×</button>
        </div>
        <div class="an-modal-summary ds-grid" id="sellerFeeSummary" aria-label="Ringkasan biaya Seller"></div>
        <div class="an-modal-body" id="sellerFeeBody" aria-live="polite"></div>
        <div class="an-modal-foot">
            <span class="an-modal-sub">Selisih Ongkir = biaya kirim aktual − subsidi ongkir − ongkir dibayar pembeli.</span>
            <button class="an-btn" type="button" data-seller-fee-close>Tutup</button>
        </div>
    </section>
</div>
