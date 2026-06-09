{{-- Shared SKU Mapping Modal --}}
<div class="modal fade" id="mappingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-black">Tambah SKU Mapping</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.75rem;color:#64748b">MARKETPLACE SKU</label>
                    <input type="text" class="form-control" id="mapSku" placeholder="cth: K3BLK-1" style="border-radius:12px" autocomplete="off">
                </div>

                {{-- Rekomendasi --}}
                <div id="mapRecommendations" style="display:none;margin-bottom:1rem">
                    <div class="fw-bold mb-1" style="font-size:.72rem;color:#64748b;text-transform:uppercase;letter-spacing:.04em">REKOMENDASI ITEM INTERNAL</div>
                    <div id="mapRecoList" class="d-flex flex-wrap gap-2"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.75rem;color:#64748b">CHANNEL (opsional)</label>
                    <select class="form-select" id="mapChannel" style="border-radius:12px">
                        <option value="">Semua Channel</option>
                        <option value="shopee">Shopee</option>
                        <option value="tiktok">TikTok</option>
                        <option value="tokopedia">Tokopedia</option>
                        <option value="lazada">Lazada</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.75rem;color:#64748b">ITEM INTERNAL</label>
                    <input type="text" class="form-control mb-1" id="mapItemSearch" placeholder="Cari kode atau nama item…" style="border-radius:12px" autocomplete="off">
                    <div id="mapItemResults" class="border rounded" style="border-radius:12px;overflow:hidden;display:none;max-height:200px;overflow-y:auto"></div>
                    <input type="hidden" id="mapItemId">
                    <div id="mapItemSelected" class="mt-1 text-success fw-bold" style="font-size:.8rem"></div>
                </div>

                {{-- Form buat item baru --}}
                <div id="mapNewItemForm" style="display:none;border:1.5px dashed rgba(37,99,235,.3);border-radius:14px;padding:.85rem 1rem;background:rgba(239,246,255,.6);margin-bottom:1rem">
                    <div class="fw-bold mb-2" style="font-size:.78rem;color:#1d4ed8">✦ Buat Item Varian Baru
                        <span class="text-muted fw-normal" style="font-size:.72rem"> — tidak ditemukan di database</span>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold" style="font-size:.72rem;color:#64748b;margin-bottom:.2rem">KODE ITEM</label>
                        <input type="text" class="form-control form-control-sm" id="newItemCode" style="border-radius:10px;text-transform:uppercase">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold" style="font-size:.72rem;color:#64748b;margin-bottom:.2rem">NAMA ITEM</label>
                        <input type="text" class="form-control form-control-sm" id="newItemName" style="border-radius:10px">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:.72rem;color:#64748b;margin-bottom:.2rem">SATUAN</label>
                        <input type="text" class="form-control form-control-sm" id="newItemUnit" value="pcs" style="border-radius:10px;max-width:100px">
                    </div>
                    <div id="mapNewItemAlert" class="alert d-none mb-2" style="border-radius:10px;font-size:.8rem;padding:.45rem .75rem"></div>
                    <button type="button" class="btn btn-primary btn-sm fw-bold" style="border-radius:999px;font-size:.75rem"
                        onclick="mpMapping.quickCreateItem()">+ Buat & Pilih Item Ini</button>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.75rem;color:#64748b">CATATAN</label>
                    <input type="text" class="form-control" id="mapNotes" style="border-radius:12px">
                </div>
                <div id="mapSaveAlert" class="alert d-none mb-3" style="border-radius:12px;font-size:.85rem"></div>
                <div class="d-flex justify-content-end gap-2">
                    <button class="btn btn-light border" style="border-radius:999px" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-dark" style="border-radius:999px;font-weight:700" onclick="mpMapping.save()">Simpan</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
window.mpMapping = (function () {
    const { api, esc } = window.mpHelpers;
    let _onSaved = null; // callback after save

    function open(prefillSku = '', onSaved = null) {
        _onSaved = onSaved;
        document.getElementById('mapSku').value = prefillSku;
        document.getElementById('mapChannel').value = '';
        document.getElementById('mapItemId').value = '';
        document.getElementById('mapItemSearch').value = '';
        document.getElementById('mapItemSelected').textContent = '';
        document.getElementById('mapNotes').value = '';
        document.getElementById('mapRecommendations').style.display = 'none';
        document.getElementById('mapRecoList').innerHTML = '';
        document.getElementById('mapNewItemForm').style.display = 'none';
        document.getElementById('mapNewItemAlert').className = 'alert d-none';
        document.getElementById('mapSaveAlert').className = 'alert d-none';
        delete document.getElementById('mappingModal').dataset.editLineId;
        new bootstrap.Modal(document.getElementById('mappingModal')).show();
        if (prefillSku) setTimeout(() => fetchReco(prefillSku), 200);
    }

    function openForLine(lineId, fulfillId, sku) {
        open(sku);
        document.getElementById('mappingModal').dataset.editLineId   = lineId;
        document.getElementById('mappingModal').dataset.editFulfillId = fulfillId;
    }

    async function fetchReco(sku) {
        document.getElementById('mapNewItemForm').style.display = 'none';
        if (!sku || sku.length < 2) { document.getElementById('mapRecommendations').style.display = 'none'; return; }

        const queries = new Set([sku, sku.split('-')[0], sku.replace(/[-_]\d+$/, ''), sku.replace(/\d+$/, '')]);
        let results = [];
        for (const q of queries) {
            if (!q || q.length < 2) continue;
            const items = await api('/api/sku-mappings/search-items?q=' + encodeURIComponent(q)).catch(() => []);
            items.forEach(i => { if (!results.find(r => r.id === i.id)) results.push(i); });
            if (results.length >= 6) break;
        }

        if (!results.length) {
            document.getElementById('mapRecommendations').style.display = 'none';
            const prefix = sku.split('-')[0].replace(/\d+$/, '');
            document.getElementById('newItemCode').value = prefix.toUpperCase();
            document.getElementById('newItemName').value = '';
            document.getElementById('mapNewItemForm').style.display = 'block';
            return;
        }

        document.getElementById('mapNewItemForm').style.display = 'none';
        document.getElementById('mapRecommendations').style.display = 'block';
        document.getElementById('mapRecoList').innerHTML = results.slice(0, 6).map(i => `
            <button type="button" class="oc-reco-chip" onclick="mpMapping.selectReco(${i.id},'${esc(i.code)}','${esc(i.name)}')">
                <span class="oc-reco-chip-code">${esc(i.code)}</span>
                <span class="oc-reco-chip-name">${esc(i.name)}</span>
            </button>`).join('');
    }

    function selectReco(id, code, name) {
        document.getElementById('mapItemId').value = id;
        document.getElementById('mapItemSearch').value = code;
        document.getElementById('mapItemSelected').textContent = '✓ ' + code + ' — ' + name;
        document.getElementById('mapItemResults').style.display = 'none';
        document.getElementById('mapNewItemForm').style.display = 'none';
        document.querySelectorAll('#mapRecoList .oc-reco-chip').forEach(c => {
            c.classList.toggle('is-selected', c.querySelector('.oc-reco-chip-code')?.textContent === code);
        });
    }

    async function quickCreateItem() {
        const code = document.getElementById('newItemCode').value.trim().toUpperCase();
        const name = document.getElementById('newItemName').value.trim();
        const unit = document.getElementById('newItemUnit').value.trim() || 'pcs';
        const alertEl = document.getElementById('mapNewItemAlert');
        if (!code || !name) { alertEl.className = 'alert alert-warning'; alertEl.textContent = 'Isi kode dan nama item.'; return; }
        alertEl.className = 'alert d-none';
        try {
            const item = await api('/api/sku-mappings/quick-create-item', { method: 'POST', body: JSON.stringify({ code, name, unit }) });
            selectReco(item.id, item.code, item.name);
        } catch (e) { alertEl.className = 'alert alert-danger'; alertEl.textContent = e.message; }
    }

    async function save() {
        const sku    = document.getElementById('mapSku').value.trim();
        const itemId = document.getElementById('mapItemId').value;
        const alertEl = document.getElementById('mapSaveAlert');
        if (!sku || !itemId) { alertEl.className = 'alert alert-warning'; alertEl.textContent = 'Isi SKU dan pilih item.'; return; }
        alertEl.className = 'alert d-none';
        try {
            await api('/api/sku-mappings', {
                method: 'POST',
                body: JSON.stringify({
                    marketplace_sku: sku,
                    channel_code: document.getElementById('mapChannel').value || null,
                    item_id: parseInt(itemId),
                    notes: document.getElementById('mapNotes').value || null,
                }),
            });

            // Jika dibuka dari edit line fulfillment → update line sekalian
            const modal = document.getElementById('mappingModal');
            if (modal.dataset.editLineId && modal.dataset.editFulfillId) {
                await api(`/api/fulfillments/${modal.dataset.editFulfillId}/lines/${modal.dataset.editLineId}`, {
                    method: 'PATCH', body: JSON.stringify({ item_id: parseInt(itemId) }),
                });
            }

            // Auto remap pending fulfillments di background
            api('/api/fulfillments/remap-all', { method: 'POST' }).catch(() => {});

            bootstrap.Modal.getInstance(modal)?.hide();
            if (_onSaved) _onSaved();
        } catch (e) { alertEl.className = 'alert alert-danger'; alertEl.textContent = e.message; }
    }

    // Item search autocomplete
    let _searchTimer;
    document.addEventListener('DOMContentLoaded', () => {
        const skuInp  = document.getElementById('mapSku');
        const srchInp = document.getElementById('mapItemSearch');
        if (skuInp) {
            let skuTimer;
            skuInp.addEventListener('input', () => {
                clearTimeout(skuTimer);
                skuTimer = setTimeout(() => fetchReco(skuInp.value.trim()), 400);
            });
        }
        if (srchInp) {
            srchInp.addEventListener('input', function () {
                clearTimeout(_searchTimer);
                const q = this.value.trim();
                if (q.length < 2) { document.getElementById('mapItemResults').style.display = 'none'; return; }
                _searchTimer = setTimeout(async () => {
                    const items = await api('/api/sku-mappings/search-items?q=' + encodeURIComponent(q)).catch(() => []);
                    const box = document.getElementById('mapItemResults');
                    if (!items.length) { box.style.display = 'none'; return; }
                    box.style.display = 'block';
                    box.innerHTML = items.map(i =>
                        `<div class="p-2 border-bottom" style="cursor:pointer;font-size:.82rem"
                            onmousedown="mpMapping.selectReco(${i.id},'${esc(i.code)}','${esc(i.name)}')">
                            <strong>${esc(i.code)}</strong> — ${esc(i.name)}</div>`
                    ).join('');
                }, 250);
            });
        }
    });

    return { open, openForLine, fetchReco, selectReco, quickCreateItem, save };
})();
</script>
@endpush
