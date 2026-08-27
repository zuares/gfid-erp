@extends('layouts.app')
@section('title', 'Marketplace • Escrow')

@include('marketplace._shared')

@push('head')
    <style>
        .escrow-hero{display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap;margin-bottom:.85rem;padding:1rem 1.1rem;border-radius:18px;background:linear-gradient(135deg,#0f172a,#1e3a8a);color:#fff;box-shadow:0 14px 34px rgba(15,23,42,.15)}
        .escrow-hero h1{font-size:1.18rem;letter-spacing:-.03em;margin:0 0 .28rem;font-weight:850}.escrow-hero p{color:#cbd5e1;margin:0;font-size:.78rem;max-width:42rem}
        .escrow-live{display:inline-flex;align-items:center;gap:.35rem;border:1px solid rgba(255,255,255,.2);border-radius:999px;padding:.35rem .62rem;font-size:.7rem;font-weight:800;color:#dcfce7;background:rgba(34,197,94,.12);white-space:nowrap}.escrow-live i{width:7px;height:7px;border-radius:50%;background:#4ade80;box-shadow:0 0 0 4px rgba(74,222,128,.16)}
        .escrow-panel{border:1px solid var(--shp-border);border-radius:14px;background:var(--card);padding:.85rem;margin-bottom:.8rem}.escrow-filters{display:grid;grid-template-columns:minmax(180px,1.1fr) repeat(2,minmax(145px,.7fr)) auto;gap:.65rem;align-items:end}.escrow-field label{display:block;font-size:.67rem;font-weight:850;text-transform:uppercase;letter-spacing:.04em;color:var(--shp-muted);margin-bottom:.25rem}.escrow-field .form-control,.escrow-field .form-select{font-size:.8rem;border-radius:8px}.escrow-help{color:var(--shp-muted);font-size:.72rem;margin-top:.7rem}
        .escrow-kpis{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.65rem;margin-bottom:.8rem}.escrow-kpi{border:1px solid var(--shp-border);border-radius:12px;padding:.75rem .85rem;background:var(--card)}.escrow-kpi-label{color:var(--shp-muted);font-size:.65rem;text-transform:uppercase;letter-spacing:.05em;font-weight:850}.escrow-kpi-value{font-size:1.15rem;line-height:1.2;font-weight:900;color:var(--shp-text);margin-top:.2rem}.escrow-kpi-note{font-size:.68rem;color:var(--shp-muted);margin-top:.15rem}
        .escrow-table-wrap{overflow:auto;border:1px solid var(--shp-border);border-radius:14px;background:var(--card)}.escrow-table{margin:0;min-width:620px}.escrow-table th{font-size:.67rem;text-transform:uppercase;letter-spacing:.04em;color:var(--shp-muted);white-space:nowrap;background:rgba(148,163,184,.06);padding:.65rem .75rem}.escrow-table td{font-size:.78rem;vertical-align:middle;padding:.62rem .75rem;border-top-color:var(--shp-border)}.escrow-order{font-weight:850;color:var(--shp-text);font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.74rem}.escrow-muted{color:var(--shp-muted)}.escrow-money{font-weight:850;color:#047857;white-space:nowrap}.escrow-empty{padding:2rem 1rem;text-align:center;color:var(--shp-muted);font-size:.82rem}.escrow-status{padding:.22rem .48rem;border-radius:999px;background:#dcfce7;color:#166534;font-size:.65rem;font-weight:850}.escrow-actions{display:flex;justify-content:space-between;align-items:center;gap:.65rem;padding:.75rem;border:1px solid var(--shp-border);border-top:0;border-radius:0 0 14px 14px;background:var(--card)}.escrow-page-info{font-size:.72rem;color:var(--shp-muted)}
        .escrow-alert{display:none;border-radius:10px;padding:.7rem .8rem;font-size:.78rem;margin-bottom:.8rem}.escrow-alert.show{display:block}.escrow-alert.error{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}.escrow-alert.info{background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8}.escrow-spinner{display:inline-block;width:14px;height:14px;border:2px solid currentColor;border-right-color:transparent;border-radius:50%;animation:escrow-spin .65s linear infinite;vertical-align:-2px}@keyframes escrow-spin{to{transform:rotate(360deg)}}
        .escrow-modal-backdrop{display:none;position:fixed;inset:0;z-index:1050;background:rgba(15,23,42,.58);padding:1rem;overflow:auto}.escrow-modal-backdrop.show{display:flex;align-items:flex-start;justify-content:center}.escrow-modal{width:min(920px,100%);margin:4vh auto;background:var(--card,#fff);border-radius:16px;box-shadow:0 24px 80px rgba(15,23,42,.3);overflow:hidden}.escrow-modal-head{display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;padding:1rem;border-bottom:1px solid var(--shp-border);background:linear-gradient(135deg,#f8fafc,#fff)}body[data-theme="dark"] .escrow-modal-head{background:rgba(30,41,59,.8)}.escrow-modal-title{font-weight:900;color:var(--shp-text);font-size:.95rem}.escrow-modal-sub{font-size:.7rem;color:var(--shp-muted);margin-top:.18rem}.escrow-close{border:0;background:transparent;color:var(--shp-muted);font-size:1.2rem;cursor:pointer}.escrow-detail-body{padding:1rem}.escrow-detail-summary{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.55rem;margin-bottom:1rem}.escrow-detail-card{border:1px solid var(--shp-border);border-radius:10px;padding:.6rem .7rem}.escrow-detail-label{font-size:.62rem;text-transform:uppercase;letter-spacing:.04em;color:var(--shp-muted);font-weight:850}.escrow-detail-value{font-size:.78rem;font-weight:800;color:var(--shp-text);margin-top:.2rem;word-break:break-word}.escrow-detail-section{border-top:1px solid var(--shp-border);padding-top:.8rem;margin-top:.8rem}.escrow-detail-section h3{font-size:.76rem;font-weight:900;color:var(--shp-text);margin:0 0 .55rem}.escrow-income-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.45rem}.escrow-income-row{display:flex;justify-content:space-between;gap:.6rem;border:1px solid var(--shp-border);border-radius:8px;padding:.45rem .55rem;font-size:.7rem}.escrow-income-row span:first-child{color:var(--shp-muted)}.escrow-income-row span:last-child{color:var(--shp-text);font-weight:800;text-align:right;word-break:break-word}.escrow-raw{max-height:280px;overflow:auto;background:#0f172a;color:#cbd5e1;padding:.75rem;border-radius:9px;font-size:.68rem;line-height:1.45;margin:0}.escrow-return-list{display:flex;flex-wrap:wrap;gap:.3rem}.escrow-return-list span{font-family:ui-monospace,monospace;font-size:.68rem;padding:.2rem .4rem;border-radius:5px;background:rgba(148,163,184,.12);color:var(--shp-text)}
        @media (max-width:760px){.escrow-filters{grid-template-columns:1fr 1fr}.escrow-filters .escrow-store-field{grid-column:1/-1}.escrow-filters .btn{grid-column:1/-1}.escrow-kpis{grid-template-columns:1fr}.escrow-detail-summary,.escrow-income-grid{grid-template-columns:1fr 1fr}.escrow-modal{margin:1vh auto}}
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        <div class="escrow-hero">
            <div>
                <h1>Escrow Shopee</h1>
                <p>Daftar order yang sudah masuk pencairan dan rincian accounting per order dari API Shopee.</p>
            </div>
            <div class="escrow-live"><i></i> Live · tanpa cache database</div>
        </div>

        <div id="escrowAlert" class="escrow-alert" role="alert"></div>

        <div class="escrow-panel">
            <div class="escrow-filters">
                <div class="escrow-field escrow-store-field">
                    <label for="escrowStore">Toko Shopee</label>
                    <select id="escrowStore" class="form-select" aria-label="Pilih toko Shopee"><option value="">Memuat toko…</option></select>
                </div>
                <div class="escrow-field">
                    <label for="escrowFrom">Release dari</label>
                    <input id="escrowFrom" type="date" class="form-control">
                </div>
                <div class="escrow-field">
                    <label for="escrowTo">Release sampai</label>
                    <input id="escrowTo" type="date" class="form-control">
                </div>
                <button id="escrowLoad" type="button" class="btn btn-ship-primary btn-pill"><span class="escrow-load-label">Tampilkan escrow</span></button>
            </div>
            <div class="escrow-help"><i class="bi bi-info-circle me-1"></i> Endpoint Shopee membatasi rentang release maksimal 15 hari. Data diambil langsung saat tombol ditekan dan tidak disimpan ke database.</div>
        </div>

        <div class="escrow-kpis">
            <div class="escrow-kpi"><div class="escrow-kpi-label">Order pada halaman</div><div id="escrowCount" class="escrow-kpi-value">—</div><div class="escrow-kpi-note">mengikuti pagination API Shopee</div></div>
            <div class="escrow-kpi"><div class="escrow-kpi-label">Total payout halaman</div><div id="escrowTotal" class="escrow-kpi-value">—</div><div class="escrow-kpi-note">field payout_amount</div></div>
            <div class="escrow-kpi"><div class="escrow-kpi-label">Status data</div><div id="escrowStatus" class="escrow-kpi-value">Belum dimuat</div><div id="escrowStatusNote" class="escrow-kpi-note">Pilih toko untuk mulai</div></div>
        </div>

        <div id="escrowTableWrap" class="escrow-table-wrap">
            <div class="escrow-empty">Pilih toko dan rentang tanggal, lalu klik <strong>Tampilkan escrow</strong>.</div>
        </div>
        <div id="escrowPagination" class="escrow-actions" style="display:none">
            <button id="escrowPrev" type="button" class="btn btn-sm btn-ship-outline btn-pill">← Sebelumnya</button>
            <span id="escrowPageInfo" class="escrow-page-info"></span>
            <button id="escrowNext" type="button" class="btn btn-sm btn-ship-outline btn-pill">Berikutnya →</button>
        </div>
    </div>

    <div id="escrowModalBackdrop" class="escrow-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="escrowModalTitle">
        <div class="escrow-modal">
            <div class="escrow-modal-head">
                <div><div id="escrowModalTitle" class="escrow-modal-title">Detail escrow</div><div id="escrowModalSub" class="escrow-modal-sub">Memuat…</div></div>
                <button id="escrowClose" type="button" class="escrow-close" aria-label="Tutup">×</button>
            </div>
            <div id="escrowDetailBody" class="escrow-detail-body"><div class="escrow-empty"><span class="escrow-spinner"></span> Memuat detail dari Shopee…</div></div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(() => {
    const $ = (id) => document.getElementById(id);
    const state = { stores: [], page: 1, more: false, items: [] };
    const today = new Date();
    const iso = (date) => date.toISOString().slice(0, 10);
    const from = new Date(today); from.setDate(from.getDate() - 14);
    $('escrowFrom').value = iso(from); $('escrowTo').value = iso(today);

    const money = (value) => {
        if (value === null || value === undefined || value === '' || Number.isNaN(Number(value))) return '—';
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(Number(value));
    };
    const dateTime = (value) => {
        if (!value) return '—';
        const date = new Date(value);
        return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
    };
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));
    const json = (value) => JSON.stringify(value ?? {}, null, 2);
    const showAlert = (message, type = 'error') => { const el = $('escrowAlert'); el.textContent = message; el.className = `escrow-alert show ${type}`; };
    const hideAlert = () => { $('escrowAlert').className = 'escrow-alert'; };
    const selectedStore = () => state.stores.find((store) => String(store.id) === String($('escrowStore').value));
    const api = async (url) => {
        const response = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || payload.success === false) throw new Error(payload.message || `Request gagal (${response.status})`);
        return payload;
    };

    const listUrl = () => {
        const params = new URLSearchParams({ date_from: $('escrowFrom').value, date_to: $('escrowTo').value, page_no: state.page, page_size: 100 });
        return `/api/marketplace/stores/${encodeURIComponent($('escrowStore').value)}/escrow-list?${params}`;
    };

    const renderRows = (items) => {
        if (!items.length) { $('escrowTableWrap').innerHTML = '<div class="escrow-empty">Tidak ada escrow pada rentang release yang dipilih.</div>'; return; }
        $('escrowTableWrap').innerHTML = `<table class="table escrow-table"><thead><tr><th>Order SN</th><th>Status</th><th>Payout amount</th><th>Waktu release</th><th></th></tr></thead><tbody>${items.map((item, index) => `<tr><td><span class="escrow-order">${escapeHtml(item.order_sn || '—')}</span></td><td><span class="escrow-status">Released</span></td><td class="escrow-money">${money(item.payout_amount)}</td><td class="escrow-muted">${escapeHtml(dateTime(item.escrow_release_at))}</td><td class="text-end"><button type="button" class="btn btn-sm btn-ship-outline btn-pill escrow-detail-btn" data-index="${index}">Detail</button></td></tr>`).join('')}</tbody></table>`;
        document.querySelectorAll('.escrow-detail-btn').forEach((button) => button.addEventListener('click', () => openDetail(items[Number(button.dataset.index)]?.order_sn)));
    };

    const renderPagination = () => { $('escrowPagination').style.display = 'flex'; $('escrowPrev').disabled = state.page <= 1; $('escrowNext').disabled = !state.more; $('escrowPageInfo').textContent = `Halaman ${state.page}${state.more ? ' · masih ada data berikutnya' : ' · halaman terakhir'}`; };
    const setLoading = (loading) => { const button = $('escrowLoad'); button.disabled = loading; button.querySelector('.escrow-load-label').innerHTML = loading ? '<span class="escrow-spinner"></span> Memuat…' : 'Tampilkan escrow'; };

    const loadList = async () => {
        hideAlert();
        if (!$('escrowStore').value) { showAlert('Pilih toko Shopee terlebih dahulu.'); return; }
        if (!$('escrowFrom').value || !$('escrowTo').value) { showAlert('Tanggal release wajib diisi.'); return; }
        if ($('escrowFrom').value > $('escrowTo').value) { showAlert('Tanggal mulai tidak boleh melewati tanggal akhir.'); return; }
        const rangeDays = Math.round((new Date(`${$('escrowTo').value}T00:00:00`) - new Date(`${$('escrowFrom').value}T00:00:00`)) / 86400000) + 1;
        if (rangeDays > 15) { showAlert('Rentang tanggal maksimal 15 hari sesuai batas endpoint Shopee.'); return; }
        setLoading(true); $('escrowStatus').textContent = 'Memuat…'; $('escrowTableWrap').innerHTML = '<div class="escrow-empty"><span class="escrow-spinner"></span> Mengambil data live dari Shopee…</div>';
        try {
            const payload = await api(listUrl()); state.items = payload.data.items || []; state.more = Boolean(payload.data.more);
            renderRows(state.items); renderPagination();
            $('escrowCount').textContent = state.items.length.toLocaleString('id-ID'); $('escrowTotal').textContent = money(state.items.reduce((sum, item) => sum + Number(item.payout_amount || 0), 0)); $('escrowStatus').textContent = 'Berhasil'; $('escrowStatusNote').textContent = `${selectedStore()?.name || 'Toko'} · diambil live`;
        } catch (error) { $('escrowTableWrap').innerHTML = `<div class="escrow-empty">Tidak dapat memuat data escrow.</div>`; $('escrowPagination').style.display = 'none'; $('escrowStatus').textContent = 'Gagal'; $('escrowStatusNote').textContent = 'Periksa koneksi/credential Shopee'; showAlert(error.message); }
        finally { setLoading(false); }
    };

    const incomeLabels = { escrow_amount:'Escrow amount', buyer_total_amount:'Buyer total amount', original_price:'Original price', seller_discount:'Seller discount', shopee_discount:'Shopee discount', voucher_from_seller:'Voucher dari seller', voucher_from_shopee:'Voucher dari Shopee', coins:'Shopee Coins', buyer_paid_shipping_fee:'Shipping dibayar buyer', commission_fee:'Commission fee', service_fee:'Service fee', seller_transaction_fee:'Seller transaction fee', seller_order_processing_fee:'Order processing fee', seller_coin_cash_back:'Seller coin cashback', escrow_tax:'Escrow tax', final_shipping_fee:'Final shipping fee', actual_shipping_fee:'Actual shipping fee', shopee_shipping_rebate:'Shipping rebate Shopee', cost_of_goods_sold:'Cost of goods sold', drc_adjustable_refund:'DRC adjustable refund' };
    const openDetail = async (orderSn) => {
        if (!orderSn) return; const storeId = $('escrowStore').value; $('escrowModalBackdrop').classList.add('show'); $('escrowModalTitle').textContent = `Detail escrow · ${orderSn}`; $('escrowModalSub').textContent = `${selectedStore()?.name || 'Toko'} · live dari Shopee`; $('escrowDetailBody').innerHTML = '<div class="escrow-empty"><span class="escrow-spinner"></span> Memuat detail dari Shopee…</div>';
        try {
            const payload = await api(`/api/marketplace/stores/${encodeURIComponent(storeId)}/escrow-detail?order_sn=${encodeURIComponent(orderSn)}`); const detail = payload.data || {}; const income = detail.income || {};
            const fields = Object.entries(incomeLabels).filter(([key]) => Object.prototype.hasOwnProperty.call(income, key));
            $('escrowDetailBody').innerHTML = `<div class="escrow-detail-summary"><div class="escrow-detail-card"><div class="escrow-detail-label">Order SN</div><div class="escrow-detail-value">${escapeHtml(detail.order_sn)}</div></div><div class="escrow-detail-card"><div class="escrow-detail-label">Buyer</div><div class="escrow-detail-value">${escapeHtml(detail.buyer_user_name || '—')}</div></div><div class="escrow-detail-card"><div class="escrow-detail-label">Escrow amount</div><div class="escrow-detail-value escrow-money">${money(income.escrow_amount)}</div></div></div><div class="escrow-detail-section"><h3>Return order</h3><div class="escrow-return-list">${(detail.return_order_sn_list || []).length ? detail.return_order_sn_list.map((value) => `<span>${escapeHtml(value)}</span>`).join('') : '<span class="escrow-muted">Tidak ada return order</span>'}</div></div><div class="escrow-detail-section"><h3>Rincian income</h3><div class="escrow-income-grid">${fields.length ? fields.map(([key, label]) => `<div class="escrow-income-row"><span>${label}</span><span>${money(income[key])}</span></div>`).join('') : '<span class="escrow-muted">Field income tidak dikembalikan Shopee.</span>'}</div></div><div class="escrow-detail-section"><h3>Raw response Shopee</h3><pre class="escrow-raw">${escapeHtml(json(detail.raw_response))}</pre></div>`;
        } catch (error) { $('escrowDetailBody').innerHTML = `<div class="escrow-empty">${escapeHtml(error.message)}</div>`; }
    };

    const loadStores = async () => {
        try {
            const response = await api('/api/marketplace/stores'); state.stores = (Array.isArray(response) ? response : response.data || []).filter((store) => ['shopee', 'shp'].includes(String(store.channel?.code || '').toLowerCase()) && store.is_active !== false);
            $('escrowStore').innerHTML = state.stores.length ? state.stores.map((store) => `<option value="${escapeHtml(store.id)}">${escapeHtml(store.name)}${store.connection_status === 'CONNECTED' ? ' · Connected' : ''}</option>`).join('') : '<option value="">Tidak ada toko Shopee aktif</option>';
        } catch (error) { $('escrowStore').innerHTML = '<option value="">Gagal memuat toko</option>'; showAlert(error.message); }
    };
    $('escrowLoad').addEventListener('click', () => { state.page = 1; loadList(); }); $('escrowPrev').addEventListener('click', () => { if (state.page > 1) { state.page--; loadList(); } }); $('escrowNext').addEventListener('click', () => { if (state.more) { state.page++; loadList(); } }); $('escrowClose').addEventListener('click', () => $('escrowModalBackdrop').classList.remove('show')); $('escrowModalBackdrop').addEventListener('click', (event) => { if (event.target === $('escrowModalBackdrop')) $('escrowModalBackdrop').classList.remove('show'); }); document.addEventListener('keydown', (event) => { if (event.key === 'Escape') $('escrowModalBackdrop').classList.remove('show'); });
    loadStores();
})();
</script>
@endpush
