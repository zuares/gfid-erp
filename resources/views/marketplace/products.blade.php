@extends('layouts.app')
@section('title', 'Marketplace • Produk')

@push('head')
<style>
    .prd-toolbar { display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-bottom:12px; }
    .prd-table { width:100%; background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; font-size:.78rem; }
    .prd-table th { background:#f8fafc; padding:8px 10px; font-weight:800; color:#475569; font-size:.7rem; text-transform:uppercase; }
    .prd-table td { padding:8px 10px; border-top:1px solid #f1f5f9; vertical-align:middle; }
    .prd-img { width:44px; height:44px; border-radius:8px; object-fit:cover; background:#f1f5f9; }
    .prd-name { font-weight:700; color:#0f172a; max-width:320px; }
    .prd-sku { font-size:.68rem; color:#94a3b8; }
    .st-badge { font-size:.65rem; font-weight:800; border-radius:99px; padding:2px 8px; }
    .st-NORMAL { background:#dcfce7; color:#166534; }
    .st-UNLIST { background:#f1f5f9; color:#64748b; }
    .st-BANNED { background:#fee2e2; color:#991b1b; }
    .model-row td { background:#fbfdff; font-size:.72rem; }
    .inp-mini { width:90px; font-size:.72rem; padding:2px 6px; border:1px solid #e2e8f0; border-radius:6px; }
    .btn-mini { font-size:.65rem; padding:2px 8px; }
    .prd-caret { cursor:pointer; user-select:none; color:#64748b; }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-2">
    <h5 class="fw-black mb-0">🏷 Produk Marketplace</h5>
    <button class="btn btn-sm btn-primary" id="btnSync" onclick="syncProducts()">⟳ Sync dari Shopee</button>
</div>

<div class="prd-toolbar">
    <input type="text" class="form-control form-control-sm" style="max-width:260px" placeholder="Cari nama / SKU / item id…" id="prdSearch" onkeydown="if(event.key==='Enter')loadProducts()">
    <select class="form-select form-select-sm" style="max-width:160px" id="prdStatus" onchange="loadProducts()">
        <option value="">Semua Status</option>
        <option value="NORMAL">Tampil (NORMAL)</option>
        <option value="UNLIST">Disembunyikan</option>
        <option value="BANNED">Banned</option>
    </select>
    <span class="text-muted" style="font-size:.72rem" id="prdCount"></span>
</div>

<div style="overflow-x:auto">
<table class="prd-table">
    <thead><tr>
        <th style="width:30px"></th><th style="width:54px"></th><th>Produk</th><th>Toko</th>
        <th>Status</th><th>Harga</th><th>Stok</th><th>Terjual</th><th style="width:210px">Aksi</th>
    </tr></thead>
    <tbody id="prdBody"><tr><td colspan="9" class="text-center text-muted py-4">Memuat…</td></tr></tbody>
</table>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const API = '/api/marketplace/products';
    let products = [];
    const $ = id => document.getElementById(id);
    const esc = s => (s ?? '').toString().replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const rp = n => n == null ? '—' : 'Rp' + Number(n).toLocaleString('id-ID');

    async function api(url, opts = {}) {
        const res = await fetch(url, { headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' }, ...opts });
        if (!res.ok) throw new Error((await res.json().catch(() => ({})))?.message || ('HTTP ' + res.status));
        return res.json();
    }

    window.loadProducts = async function () {
        const p = new URLSearchParams();
        if ($('prdSearch').value.trim()) p.set('search', $('prdSearch').value.trim());
        if ($('prdStatus').value) p.set('status', $('prdStatus').value);
        try {
            products = await api(`${API}?${p}`);
            render();
        } catch (e) {
            $('prdBody').innerHTML = `<tr><td colspan="9" class="text-center text-danger py-4">${esc(e.message)}</td></tr>`;
        }
    };

    window.syncProducts = async function () {
        $('btnSync').disabled = true; $('btnSync').textContent = '⏳ Sync…';
        try {
            const res = await api(`${API}/sync`, { method: 'POST', body: '{}' });
            if (window.Swal) Swal.fire({ toast:true, position:'top-end', icon: res.errors?.length ? 'warning' : 'success', title: res.message, showConfirmButton:false, timer:3500 });
            if (res.errors?.length) console.warn('Sync errors:', res.errors);
            loadProducts();
        } catch (e) { alert('Sync gagal: ' + e.message); }
        finally { $('btnSync').disabled = false; $('btnSync').textContent = '⟳ Sync dari Shopee'; }
    };

    function render() {
        $('prdCount').textContent = products.length + ' produk';
        if (!products.length) {
            $('prdBody').innerHTML = '<tr><td colspan="9" class="text-center text-muted py-4">Belum ada produk. Klik "Sync dari Shopee".</td></tr>';
            return;
        }
        $('prdBody').innerHTML = products.map(p => {
            const st = p.item_status || '—';
            const models = p.models || [];
            const multiModel = p.has_model && models.length > 0;
            const price = p.price_min != null
                ? (p.price_min === p.price_max ? rp(p.price_min) : `${rp(p.price_min)} – ${rp(p.price_max)}`)
                : '—';

            const mainRow = `<tr data-pid="${p.id}">
                <td>${multiModel ? `<span class="prd-caret" onclick="toggleModels(${p.id}, this)">▶</span>` : ''}</td>
                <td>${p.image_url ? `<img class="prd-img" src="${esc(p.image_url)}" loading="lazy">` : '<div class="prd-img"></div>'}</td>
                <td><div class="prd-name">${esc(p.item_name || '—')}</div>
                    <div class="prd-sku">SKU: ${esc(p.item_sku || '—')} · ID: ${esc(p.item_id)}</div></td>
                <td style="font-size:.7rem">${esc(p.store?.name || '')}</td>
                <td><span class="st-badge st-${esc(st)}">${esc(st)}</span></td>
                <td>${price}</td>
                <td class="fw-bold">${p.stock_total ?? 0}</td>
                <td>${p.sales ?? '—'}</td>
                <td>
                    ${!multiModel && models.length ? inlineEditors(p.id, models[0]) : ''}
                    ${st === 'NORMAL'
                        ? `<button class="btn btn-outline-secondary btn-mini mt-1" onclick="setUnlist(${p.id}, true)">🙈 Sembunyikan</button>`
                        : (st === 'UNLIST' ? `<button class="btn btn-outline-success btn-mini mt-1" onclick="setUnlist(${p.id}, false)">👁 Tampilkan</button>` : '')}
                </td>
            </tr>`;

            const modelRows = multiModel ? models.map(m => `
                <tr class="model-row mr-${p.id}" style="display:none">
                    <td></td><td></td>
                    <td style="padding-left:24px">↳ ${esc(m.model_name || 'Varian')} <span class="prd-sku">SKU: ${esc(m.model_sku || '—')}</span></td>
                    <td></td><td></td>
                    <td>${rp(m.price)}</td>
                    <td>${m.stock}</td>
                    <td></td>
                    <td>${inlineEditors(p.id, m)}</td>
                </tr>`).join('') : '';

            return mainRow + modelRows;
        }).join('');
    }

    function inlineEditors(pid, m) {
        return `<div class="d-flex gap-1 flex-wrap align-items-center">
            <input class="inp-mini" type="number" min="0" value="${m.stock}" id="stk-${pid}-${m.model_id}" title="Stok">
            <button class="btn btn-outline-primary btn-mini" onclick="saveStock(${pid}, '${m.model_id}')">Stok</button>
            <input class="inp-mini" type="number" min="100" value="${m.price ?? ''}" id="prc-${pid}-${m.model_id}" title="Harga">
            <button class="btn btn-outline-primary btn-mini" onclick="savePrice(${pid}, '${m.model_id}')">Harga</button>
        </div>`;
    }

    window.toggleModels = function (pid, caret) {
        const rows = document.querySelectorAll('.mr-' + pid);
        const show = rows.length && rows[0].style.display === 'none';
        rows.forEach(r => r.style.display = show ? '' : 'none');
        caret.textContent = show ? '▼' : '▶';
    };

    window.saveStock = async function (pid, modelId) {
        const val = parseInt($(`stk-${pid}-${modelId}`).value);
        if (isNaN(val) || val < 0) return alert('Stok tidak valid');
        try {
            await api(`${API}/${pid}/stock`, { method: 'POST', body: JSON.stringify({ stock_list: [{ model_id: modelId, stock: val }] }) });
            toast('Stok tersimpan ke Shopee ✔');
            loadProducts();
        } catch (e) { alert('Gagal: ' + e.message); }
    };

    window.savePrice = async function (pid, modelId) {
        const val = parseFloat($(`prc-${pid}-${modelId}`).value);
        if (isNaN(val) || val < 100) return alert('Harga tidak valid (min 100)');
        try {
            await api(`${API}/${pid}/price`, { method: 'POST', body: JSON.stringify({ price_list: [{ model_id: modelId, original_price: val }] }) });
            toast('Harga tersimpan ke Shopee ✔');
            loadProducts();
        } catch (e) { alert('Gagal: ' + e.message); }
    };

    window.setUnlist = async function (pid, unlist) {
        try {
            await api(`${API}/${pid}/unlist`, { method: 'POST', body: JSON.stringify({ unlist }) });
            toast(unlist ? 'Produk disembunyikan' : 'Produk ditampilkan');
            loadProducts();
        } catch (e) { alert('Gagal: ' + e.message); }
    };

    function toast(title) {
        if (window.Swal) Swal.fire({ toast:true, position:'top-end', icon:'success', title, showConfirmButton:false, timer:2200 });
    }

    // Realtime: webhook item_update → refresh daftar
    if (window.Echo) {
        try {
            window.Echo.channel('marketplace').listen('ProductUpdated', () => loadProducts());
        } catch (e) {}
    }

    loadProducts();
})();
</script>
@endpush
