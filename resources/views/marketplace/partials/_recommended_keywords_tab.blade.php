<div class="ads-tab-panel mb-3" id="recommendedKeywordsModule">
    <div class="ads-tab-panel-head">
        <div>
            <div class="ads-tab-panel-title"><i class="bi bi-search-heart text-primary"></i> Rekomendasi Keyword Shopee</div>
            <div class="ads-tab-panel-note">Temukan keyword yang direkomendasikan Shopee berdasarkan produk dan kata pencarian.</div>
        </div>
        <a href="https://open.shopee.com/documents/v2/v2.ads.get_recommended_keyword_list?module=117&amp;type=1" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary" style="font-size:.7rem; border-radius:8px;">
            <i class="bi bi-box-arrow-up-right"></i> Dokumentasi API
        </a>
    </div>

    <div class="p-3">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-lg-5">
                <label for="keywordProductSelect" class="form-label mb-1" style="font-size:.72rem; font-weight:700;">Produk</label>
                <select id="keywordProductSelect" class="form-select form-select-sm" style="border-radius:9px;">
                    <option value="">Pilih produk…</option>
                </select>
                <div id="keywordProductHint" class="mt-1" style="font-size:.65rem; color:var(--dsh-muted);">Pilih satu toko pada filter di atas untuk memuat produk.</div>
            </div>
            <div class="col-12 col-lg-4">
                <label for="keywordItemId" class="form-label mb-1" style="font-size:.72rem; font-weight:700;">Item ID Shopee</label>
                <input id="keywordItemId" type="text" inputmode="numeric" class="form-control form-control-sm" placeholder="Terisi otomatis dari produk" style="border-radius:9px;">
                <div style="font-size:.65rem; color:var(--dsh-muted);" class="mt-1">Bisa diisi manual jika produk belum ada di katalog lokal.</div>
            </div>
            <div class="col-12 col-lg-3">
                <label for="keywordInput" class="form-label mb-1" style="font-size:.72rem; font-weight:700;">Filter keyword <span style="font-weight:500;">(opsional)</span></label>
                <div class="input-group input-group-sm">
                    <input id="keywordInput" type="text" class="form-control" maxlength="100" placeholder="Contoh: jaket pria" style="border-radius:9px 0 0 9px;">
                    <button id="keywordSearchButton" type="button" class="btn btn-primary" style="border-radius:0 9px 9px 0;">
                        <i class="bi bi-search"></i> Cari
                    </button>
                </div>
            </div>
        </div>

        <div id="keywordModuleMessage" class="mt-3" role="status" aria-live="polite" style="display:none; font-size:.76rem;"></div>

        <div id="keywordSummary" class="row g-2 mt-1" style="display:none;">
            <div class="col-6 col-md-3"><div class="ads-kpi p-2"><div class="ads-kpi-label">Rekomendasi</div><div id="keywordCount" class="ads-kpi-value">0</div></div></div>
            <div class="col-6 col-md-3"><div class="ads-kpi p-2"><div class="ads-kpi-label">Rata-rata kualitas</div><div id="keywordQuality" class="ads-kpi-value">0</div></div></div>
            <div class="col-6 col-md-3"><div class="ads-kpi p-2"><div class="ads-kpi-label">Total pencarian 30 hari</div><div id="keywordVolume" class="ads-kpi-value">0</div></div></div>
            <div class="col-6 col-md-3"><div class="ads-kpi p-2"><div class="ads-kpi-label">Bid rekomendasi</div><div id="keywordBid" class="ads-kpi-value">-</div></div></div>
        </div>

        <div class="table-responsive mt-3" id="keywordTableWrap" style="display:none;">
            <table class="table table-sm align-middle mb-0" style="font-size:.76rem;">
                <thead><tr>
                    <th>Keyword</th>
                    <th class="text-end">Kualitas</th>
                    <th class="text-end">Pencarian 30 hari</th>
                    <th class="text-end">Bid rekomendasi</th>
                    <th class="text-end">Aksi</th>
                </tr></thead>
                <tbody id="keywordTableBody"></tbody>
            </table>
        </div>
        <div id="keywordEmptyState" class="text-center py-4" style="display:none; color:var(--dsh-muted); font-size:.78rem;">
            <i class="bi bi-search fs-4 d-block mb-1"></i>
            Tidak ada rekomendasi untuk kombinasi produk dan filter ini.
        </div>
    </div>
</div>

<script>
(function () {
    const module = document.getElementById('recommendedKeywordsModule');
    if (!module || module.dataset.initialized === '1') return;
    module.dataset.initialized = '1';

    const routes = {
        keywordProducts: @json(route('marketplace.ads.keyword.products')),
        keywordRecommendations: @json(route('marketplace.ads.keyword.recommendations')),
    };
    const storeSelect = document.getElementById('ads-store-filter');
    const productSelect = document.getElementById('keywordProductSelect');
    const itemInput = document.getElementById('keywordItemId');
    const keywordInput = document.getElementById('keywordInput');
    const searchButton = document.getElementById('keywordSearchButton');
    const message = document.getElementById('keywordModuleMessage');
    const summary = document.getElementById('keywordSummary');
    const tableWrap = document.getElementById('keywordTableWrap');
    const tableBody = document.getElementById('keywordTableBody');
    const emptyState = document.getElementById('keywordEmptyState');
    const productHint = document.getElementById('keywordProductHint');

    const money = value => 'Rp ' + Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
    const number = value => Number(value || 0).toLocaleString('id-ID');

    function showMessage(text, type) {
        message.textContent = text || '';
        message.style.display = text ? 'block' : 'none';
        message.style.color = type === 'error' ? '#dc2626' : 'var(--dsh-muted)';
    }

    function resetResults() {
        summary.style.display = 'none';
        tableWrap.style.display = 'none';
        emptyState.style.display = 'none';
        tableBody.replaceChildren();
    }

    async function loadProducts() {
        const storeId = storeSelect?.value || 'all';
        productSelect.replaceChildren(new Option(storeId === 'all' ? 'Pilih toko terlebih dahulu…' : 'Memuat produk…', ''));
        productSelect.disabled = storeId === 'all';
        if (storeId === 'all') {
            productHint.textContent = 'Pilih satu toko pada filter di atas untuk memuat produk.';
            return;
        }

        try {
            const response = await fetch(routes.keywordProducts + '?store_id=' + encodeURIComponent(storeId), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'Produk gagal dimuat.');

            productSelect.replaceChildren(new Option('Pilih produk…', ''));
            (payload.data || []).forEach(product => {
                const label = product.item_name + ' · ID ' + product.item_id + (product.item_sku ? ' · SKU ' + product.item_sku : '');
                productSelect.add(new Option(label, product.item_id));
            });
            productHint.textContent = (payload.data || []).length
                ? 'Pilih produk atau masukkan Item ID Shopee secara manual.'
                : 'Katalog lokal kosong. Masukkan Item ID Shopee secara manual.';
        } catch (error) {
            productSelect.replaceChildren(new Option('Produk gagal dimuat', ''));
            productHint.textContent = error.message || 'Produk gagal dimuat.';
        }
    }

    function renderResults(items) {
        const rows = Array.isArray(items) ? items : [];
        resetResults();
        if (!rows.length) {
            emptyState.style.display = 'block';
            return;
        }

        const avgQuality = rows.reduce((sum, row) => sum + Number(row.quality_score || 0), 0) / rows.length;
        const totalVolume = rows.reduce((sum, row) => sum + Number(row.search_volume || 0), 0);
        const bids = rows.map(row => Number(row.suggested_bid || 0)).filter(value => value > 0);
        document.getElementById('keywordCount').textContent = number(rows.length);
        document.getElementById('keywordQuality').textContent = avgQuality.toLocaleString('id-ID', { maximumFractionDigits: 1 });
        document.getElementById('keywordVolume').textContent = number(totalVolume);
        document.getElementById('keywordBid').textContent = bids.length ? money(Math.min(...bids)) + ' – ' + money(Math.max(...bids)) : '-';
        summary.style.display = 'flex';
        tableWrap.style.display = 'block';

        rows.forEach(row => {
            const tr = document.createElement('tr');
            const keywordCell = document.createElement('td');
            keywordCell.textContent = row.keyword || '-';
            keywordCell.style.fontWeight = '700';
            const qualityCell = document.createElement('td');
            qualityCell.className = 'text-end';
            qualityCell.textContent = number(row.quality_score);
            const volumeCell = document.createElement('td');
            volumeCell.className = 'text-end';
            volumeCell.textContent = number(row.search_volume);
            const bidCell = document.createElement('td');
            bidCell.className = 'text-end';
            bidCell.textContent = money(row.suggested_bid);
            const actionCell = document.createElement('td');
            actionCell.className = 'text-end';
            const copyButton = document.createElement('button');
            copyButton.type = 'button';
            copyButton.className = 'btn btn-sm btn-outline-secondary';
            copyButton.style.cssText = 'font-size:.68rem; border-radius:7px;';
            copyButton.innerHTML = '<i class="bi bi-copy"></i> Salin';
            copyButton.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(row.keyword || '');
                    copyButton.innerHTML = '<i class="bi bi-check2"></i> Tersalin';
                    setTimeout(() => { copyButton.innerHTML = '<i class="bi bi-copy"></i> Salin'; }, 1200);
                } catch (error) {
                    showMessage('Browser tidak mengizinkan penyalinan otomatis.', 'error');
                }
            });
            actionCell.appendChild(copyButton);
            tr.append(keywordCell, qualityCell, volumeCell, bidCell, actionCell);
            tableBody.appendChild(tr);
        });
    }

    async function searchKeywords() {
        const storeId = storeSelect?.value || 'all';
        const itemId = String(itemInput.value || '').trim();
        if (storeId === 'all') return showMessage('Pilih satu toko terlebih dahulu.', 'error');
        if (!/^\d+$/.test(itemId) || Number(itemId) < 1) return showMessage('Item ID Shopee wajib berupa angka positif.', 'error');

        searchButton.disabled = true;
        searchButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Memuat';
        showMessage('Mengambil rekomendasi dari Shopee…');
        resetResults();
        try {
            const params = new URLSearchParams({ store_id: storeId, item_id: itemId });
            const inputKeyword = String(keywordInput.value || '').trim();
            if (inputKeyword) params.set('input_keyword', inputKeyword);
            const response = await fetch(routes.keywordRecommendations + '?' + params.toString(), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'Rekomendasi keyword gagal dimuat.');
            const items = payload.data?.suggested_keywords || [];
            renderResults(items);
            showMessage(items.length ? 'Rekomendasi berhasil diperbarui.' : 'Shopee tidak mengembalikan rekomendasi untuk produk ini.');
        } catch (error) {
            resetResults();
            showMessage(error.message || 'Rekomendasi keyword gagal dimuat.', 'error');
        } finally {
            searchButton.disabled = false;
            searchButton.innerHTML = '<i class="bi bi-search"></i> Cari';
        }
    }

    productSelect.addEventListener('change', () => { itemInput.value = productSelect.value; });
    searchButton.addEventListener('click', searchKeywords);
    keywordInput.addEventListener('keydown', event => { if (event.key === 'Enter') searchKeywords(); });
    window.loadRecommendedKeywordProducts = loadProducts;
    loadProducts();
})();
</script>
