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
        .escrow-table-wrap{overflow:auto;border:1px solid var(--shp-border);border-radius:14px;background:var(--card)}.escrow-table{margin:0;min-width:620px}.escrow-table th{font-size:.67rem;text-transform:uppercase;letter-spacing:.04em;color:var(--shp-muted);white-space:nowrap;background:rgba(148,163,184,.06);padding:.65rem .75rem}.escrow-table td{font-size:.78rem;vertical-align:middle;padding:.62rem .75rem;border-top-color:var(--shp-border);white-space:nowrap}.escrow-table .escrow-detail-col{min-width:135px;max-width:260px;white-space:normal;word-break:break-word}.escrow-table code{display:block;max-width:520px;white-space:normal;word-break:break-word;color:var(--shp-muted);font-size:.66rem}.escrow-order{font-weight:850;color:var(--shp-text);font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.74rem}.escrow-muted{color:var(--shp-muted)}.escrow-money{font-weight:850;color:#047857;white-space:nowrap}.escrow-empty{padding:2rem 1rem;text-align:center;color:var(--shp-muted);font-size:.82rem}.escrow-status{padding:.22rem .48rem;border-radius:999px;background:#dcfce7;color:#166534;font-size:.65rem;font-weight:850}.escrow-detail-loading{color:#2563eb;font-size:.7rem;font-weight:750}.escrow-detail-error{color:#b91c1c;font-size:.7rem;white-space:normal;max-width:180px;display:inline-block}.escrow-actions{display:flex;justify-content:space-between;align-items:center;gap:.65rem;padding:.75rem;border:1px solid var(--shp-border);border-top:0;border-radius:0 0 14px 14px;background:var(--card)}.escrow-page-info{font-size:.72rem;color:var(--shp-muted)}
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
                    <label for="escrowSource">Sumber daftar</label>
                    <select id="escrowSource" class="form-select" aria-label="Pilih sumber daftar escrow">
                        <option value="orders">Order baru + escrow (push)</option>
                        <option value="released">Escrow release</option>
                    </select>
                </div>
                <div class="escrow-field">
                    <label for="escrowFrom">Tanggal dari</label>
                    <input id="escrowFrom" type="date" class="form-control">
                </div>
                <div class="escrow-field">
                    <label for="escrowTo">Tanggal sampai</label>
                    <input id="escrowTo" type="date" class="form-control">
                </div>
                <button id="escrowLoad" type="button" class="btn btn-ship-primary btn-pill"><span class="escrow-load-label">Tampilkan escrow</span></button>
            </div>
            <div class="escrow-help"><i class="bi bi-info-circle me-1"></i> Mode <strong>Order baru + escrow</strong> memakai order lokal yang masuk dari push, lalu detail accounting diambil live dengan batch maksimal 50 order. Mode <strong>Escrow release</strong> memakai rentang release Shopee (maksimal 15 hari). Tidak ada cache escrow baru.</div>
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
    const state = { stores: [], page: 1, more: false, items: [], details: {}, detailErrors: {}, detailLoading: {}, refreshTimer: null, loading: false };
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
    const api = async (url, options = {}) => {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const headers = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...(options.headers || {}) };
        if (options.method && options.method !== 'GET') headers['X-CSRF-TOKEN'] = csrf;
        const response = await fetch(url, { ...options, headers });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || payload.success === false) throw new Error(payload.message || `Request gagal (${response.status})`);
        return payload;
    };

    const listUrl = () => {
        const params = new URLSearchParams({ date_from: $('escrowFrom').value, date_to: $('escrowTo').value, page_no: state.page, page_size: 100 });
        const path = $('escrowSource').value === 'released' ? 'escrow-list' : 'escrow-orders';
        return `/api/marketplace/stores/${encodeURIComponent($('escrowStore').value)}/${path}?${params}`;
    };

    const incomeLabels = {
        escrow_amount:'Escrow amount', buyer_total_amount:'Buyer total amount', original_price:'Original price', seller_discount:'Seller discount', shopee_discount:'Shopee discount', voucher_from_seller:'Voucher dari seller', voucher_from_shopee:'Voucher dari Shopee', coins:'Shopee Coins', buyer_paid_shipping_fee:'Shipping dibayar buyer', buyer_transaction_fee:'Buyer transaction fee', cross_border_tax:'Cross-border tax', payment_promotion:'Payment promotion', commission_fee:'Commission fee', service_fee:'Service fee', seller_transaction_fee:'Seller transaction fee', seller_lost_compensation:'Seller lost compensation', seller_coin_cash_back:'Seller coin cashback', escrow_tax:'Escrow tax', final_shipping_fee:'Final shipping fee', actual_shipping_fee:'Actual shipping fee', order_chargeable_weight:'Chargeable weight', shopee_shipping_rebate:'Shipping rebate Shopee', shipping_fee_discount_from_3pl:'Shipping discount 3PL', seller_shipping_discount:'Seller shipping discount', estimated_shipping_fee:'Estimated shipping fee', seller_voucher_code:'Seller voucher code', drc_adjustable_refund:'DRC adjustable refund', cost_of_goods_sold:'Cost of goods sold', original_cost_of_goods_sold:'Original cost of goods sold', original_shopee_discount:'Original Shopee discount', seller_return_refund:'Seller return refund', reverse_shipping_fee:'Reverse shipping fee', final_product_protection:'Final product protection', credit_card_promotion:'Credit card promotion', credit_card_transaction_fee:'Credit card transaction fee', final_product_vat_tax:'Final product VAT tax', final_shipping_vat_tax:'Final shipping VAT tax', campaign_fee:'Campaign fee', sip_subsidy:'SIP subsidy', escrow_amount_pri:'Escrow amount (primary)', buyer_total_amount_pri:'Buyer total (primary)', original_price_pri:'Original price (primary)', seller_return_refund_pri:'Seller return refund (primary)', commission_fee_pri:'Commission fee (primary)', service_fee_pri:'Service fee (primary)', drc_adjustable_refund_pri:'DRC refund (primary)', pri_currency:'Primary currency', aff_currency:'Affiliate currency', exchange_rate:'Exchange rate', rsf_seller_protection_fee_claim_amount:'RSF protection claim', rsf_seller_protection_fee_premium_amount:'RSF protection premium', final_escrow_product_gst:'Final escrow product GST', final_escrow_shipping_gst:'Final escrow shipping GST', delivery_seller_protection_fee_premium_amount:'Delivery protection premium', final_return_to_seller_shipping_fee:'Return-to-seller shipping fee', items:'Items'
    };
    const nonMoneyFields = new Set(['order_chargeable_weight', 'quantity_purchased', 'activity_id', 'item_id', 'model_id']);
    const incomeColumns = (items) => {
        const keys = [...Object.keys(incomeLabels)];
        items.forEach((item) => Object.keys(state.details[item.order_sn]?.income || {}).forEach((key) => { if (!keys.includes(key)) keys.push(key); }));
        return keys;
    };
    const detailValue = (key, value, orderSn = '') => {
        if (value === null || value === undefined || value === '') return '<span class="escrow-muted">—</span>';
        if (key === 'items' && Array.isArray(value)) return `<button type="button" class="btn btn-sm btn-ship-outline btn-pill escrow-items-btn" data-order-sn="${escapeHtml(orderSn)}"><i class="bi bi-list-ul me-1"></i>Lihat ${value.length} item</button>`;
        if (Array.isArray(value)) return escapeHtml(value.join(', '));
        if (typeof value === 'object') return `<code>${escapeHtml(JSON.stringify(value))}</code>`;
        if (nonMoneyFields.has(key) && Number.isFinite(Number(value))) return escapeHtml(Number(value).toLocaleString('id-ID'));
        if (typeof value === 'number' || (typeof value === 'string' && value.trim() !== '' && Number.isFinite(Number(value)))) return money(value);
        return escapeHtml(value);
    };

    const renderRows = (items) => {
        if (!items.length) { $('escrowTableWrap').innerHTML = `<div class="escrow-empty">Tidak ada data pada rentang ${$('escrowSource').value === 'released' ? 'release' : 'order'} yang dipilih.</div>`; return; }
        const columns = incomeColumns(items);
        const headers = columns.map((key) => `<th class="escrow-detail-col">${escapeHtml(incomeLabels[key] || key.replaceAll('_', ' '))}</th>`).join('');
        const rows = items.map((item, index) => {
            const detail = state.details[item.order_sn] || {};
            const income = detail.income || {};
            const orderError = state.detailErrors[item.order_sn];
            const loading = state.detailLoading[item.order_sn];
            const loaded = Object.prototype.hasOwnProperty.call(state.details, item.order_sn);
            const detailState = loading ? '<span class="escrow-detail-loading">Memuat detail…</span>' : (orderError ? `<span class="escrow-status" title="${escapeHtml(orderError)}">Pending</span>` : (loaded ? '<span class="escrow-status">Lengkap</span>' : '<span class="escrow-detail-loading">Menunggu…</span>'));
            const incomeCells = columns.map((key) => `<td class="escrow-detail-col">${detailValue(key, income[key], item.order_sn)}</td>`).join('');
            const payout = item.payout_amount ?? income.escrow_amount;
            const date = item.escrow_release_at || item.ordered_at;
            return `<tr><td><span class="escrow-order">${escapeHtml(item.order_sn || '—')}</span></td><td>${escapeHtml(item.order_status || '—')}</td><td>${detailState}</td><td>${money(item.order_total)}</td><td class="escrow-money">${money(payout)}</td><td class="escrow-muted">${escapeHtml(dateTime(date))}</td><td class="escrow-detail-col">${escapeHtml(detail.buyer_user_name || item.buyer_user_name || '—')}</td><td class="escrow-detail-col">${escapeHtml((detail.return_order_sn_list || []).join(', ') || '—')}</td>${incomeCells}<td class="text-end"><button type="button" class="btn btn-sm btn-ship-outline btn-pill escrow-detail-btn" data-index="${index}">Raw</button></td></tr>`;
        }).join('');
        $('escrowTableWrap').innerHTML = `<table class="table escrow-table"><thead><tr><th>Order SN</th><th>Status order</th><th>Status detail</th><th>Nilai order</th><th>Payout amount</th><th>Waktu order/release</th><th class="escrow-detail-col">Buyer</th><th class="escrow-detail-col">Return order</th>${headers}<th></th></tr></thead><tbody>${rows}</tbody></table>`;
        document.querySelectorAll('.escrow-detail-btn').forEach((button) => button.addEventListener('click', () => openDetail(items[Number(button.dataset.index)]?.order_sn)));
        document.querySelectorAll('.escrow-items-btn').forEach((button) => button.addEventListener('click', () => openItems(button.dataset.orderSn)));
    };

    const renderPagination = () => { $('escrowPagination').style.display = 'flex'; $('escrowPrev').disabled = state.page <= 1; $('escrowNext').disabled = !state.more; $('escrowPageInfo').textContent = `Halaman ${state.page}${state.more ? ' · masih ada data berikutnya' : ' · halaman terakhir'}`; };
    const setLoading = (loading) => { const button = $('escrowLoad'); button.disabled = loading; button.querySelector('.escrow-load-label').innerHTML = loading ? '<span class="escrow-spinner"></span> Memuat…' : 'Tampilkan escrow'; };
    const detailUrl = (orderSn) => `/api/marketplace/stores/${encodeURIComponent($('escrowStore').value)}/escrow-detail?order_sn=${encodeURIComponent(orderSn)}`;
    const batchDetailUrl = () => `/api/marketplace/stores/${encodeURIComponent($('escrowStore').value)}/escrow-detail-batch`;

    const loadDetails = async () => {
        const queue = state.items.filter((item) => item.order_sn);
        if (!queue.length) return;
        queue.forEach((item) => { state.detailLoading[item.order_sn] = true; });
        renderRows(state.items);
        let completed = 0;
        for (let offset = 0; offset < queue.length; offset += 50) {
            const orderSnList = queue.slice(offset, offset + 50).map((item) => item.order_sn);
            try {
                const payload = await api(batchDetailUrl(), { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ order_sn_list: orderSnList }) });
                Object.assign(state.details, payload.data?.details || {});
                Object.entries(payload.data?.failed || {}).forEach(([orderSn, failure]) => { state.detailErrors[orderSn] = failure.message || failure.error || 'Detail escrow belum tersedia.'; });
            } catch (error) {
                orderSnList.forEach((orderSn) => { state.detailErrors[orderSn] = error.message; });
            } finally {
                orderSnList.forEach((orderSn) => { state.detailLoading[orderSn] = false; });
                completed += orderSnList.length;
                $('escrowStatus').textContent = 'Memuat detail…';
                $('escrowStatusNote').textContent = `${selectedStore()?.name || 'Toko'} · ${completed}/${queue.length} detail diproses batch`;
                renderRows(state.items);
            }
        }
    };

    const loadList = async () => {
        hideAlert();
        if (!$('escrowStore').value) { showAlert('Pilih toko Shopee terlebih dahulu.'); return; }
        if (!$('escrowFrom').value || !$('escrowTo').value) { showAlert('Rentang tanggal wajib diisi.'); return; }
        if ($('escrowFrom').value > $('escrowTo').value) { showAlert('Tanggal mulai tidak boleh melewati tanggal akhir.'); return; }
        const rangeDays = Math.round((new Date(`${$('escrowTo').value}T00:00:00`) - new Date(`${$('escrowFrom').value}T00:00:00`)) / 86400000) + 1;
        if ($('escrowSource').value === 'released' && rangeDays > 15) { showAlert('Rentang tanggal maksimal 15 hari sesuai batas endpoint escrow release Shopee.'); return; }
        state.loading = true;
        setLoading(true); $('escrowStatus').textContent = 'Memuat…'; $('escrowTableWrap').innerHTML = '<div class="escrow-empty"><span class="escrow-spinner"></span> Mengambil data live dari Shopee…</div>';
        try {
            const payload = await api(listUrl()); state.items = payload.data.items || []; state.more = Boolean(payload.data.more); state.details = {}; state.detailErrors = {}; state.detailLoading = {};
            renderRows(state.items); renderPagination();
            $('escrowCount').textContent = state.items.length.toLocaleString('id-ID'); $('escrowTotal').textContent = money(state.items.reduce((sum, item) => sum + Number(item.payout_amount || 0), 0)); $('escrowStatus').textContent = state.items.length ? 'Memuat detail…' : 'Berhasil'; $('escrowStatusNote').textContent = `${selectedStore()?.name || 'Toko'} · 0/${state.items.length} detail dimuat`;
            await loadDetails();
            $('escrowStatus').textContent = 'Berhasil'; $('escrowStatusNote').textContent = `${selectedStore()?.name || 'Toko'} · seluruh detail halaman dimuat live`;
        } catch (error) { $('escrowTableWrap').innerHTML = `<div class="escrow-empty">Tidak dapat memuat data escrow.</div>`; $('escrowPagination').style.display = 'none'; $('escrowStatus').textContent = 'Gagal'; $('escrowStatusNote').textContent = 'Periksa koneksi/credential Shopee'; showAlert(error.message); }
        finally { state.loading = false; setLoading(false); }
    };

    const openDetail = async (orderSn) => {
        if (!orderSn) return; $('escrowModalBackdrop').classList.add('show'); $('escrowModalTitle').textContent = `Detail escrow · ${orderSn}`; $('escrowModalSub').textContent = `${selectedStore()?.name || 'Toko'} · live dari Shopee`; $('escrowDetailBody').innerHTML = '<div class="escrow-empty"><span class="escrow-spinner"></span> Memuat detail dari Shopee…</div>';
        try {
            const payload = state.details[orderSn]?.raw_response ? { data: state.details[orderSn] } : await api(detailUrl(orderSn)); const detail = payload.data || {}; state.details[orderSn] = detail; const income = detail.income || {};
            const fields = Object.entries(income).map(([key]) => [key, incomeLabels[key] || key.replaceAll('_', ' ')]);
            $('escrowDetailBody').innerHTML = `<div class="escrow-detail-summary"><div class="escrow-detail-card"><div class="escrow-detail-label">Order SN</div><div class="escrow-detail-value">${escapeHtml(detail.order_sn)}</div></div><div class="escrow-detail-card"><div class="escrow-detail-label">Buyer</div><div class="escrow-detail-value">${escapeHtml(detail.buyer_user_name || '—')}</div></div><div class="escrow-detail-card"><div class="escrow-detail-label">Escrow amount</div><div class="escrow-detail-value escrow-money">${money(income.escrow_amount)}</div></div></div><div class="escrow-detail-section"><h3>Return order</h3><div class="escrow-return-list">${(detail.return_order_sn_list || []).length ? detail.return_order_sn_list.map((value) => `<span>${escapeHtml(value)}</span>`).join('') : '<span class="escrow-muted">Tidak ada return order</span>'}</div></div><div class="escrow-detail-section"><h3>Rincian income</h3><div class="escrow-income-grid">${fields.length ? fields.map(([key, label]) => `<div class="escrow-income-row"><span>${escapeHtml(label)}</span><span>${detailValue(key, income[key], detail.order_sn)}</span></div>`).join('') : '<span class="escrow-muted">Field income tidak dikembalikan Shopee.</span>'}</div></div><div class="escrow-detail-section"><h3>Raw response Shopee</h3><pre class="escrow-raw">${escapeHtml(json(detail.raw_response))}</pre></div>`;
            document.querySelectorAll('#escrowDetailBody .escrow-items-btn').forEach((button) => button.addEventListener('click', () => openItems(detail.order_sn)));
        } catch (error) { $('escrowDetailBody').innerHTML = `<div class="escrow-empty">${escapeHtml(error.message)}</div>`; }
    };

    const openItems = (orderSn) => {
        const detail = state.details[orderSn];
        const items = detail?.income?.items;
        if (!Array.isArray(items)) return;
        $('escrowModalBackdrop').classList.add('show');
        $('escrowModalTitle').textContent = `Items escrow · ${orderSn}`;
        $('escrowModalSub').textContent = `${selectedStore()?.name || 'Toko'} · raw items dari Shopee`;
        $('escrowDetailBody').innerHTML = `<div class="escrow-detail-section" style="border-top:0;padding-top:0;margin-top:0"><h3>${items.length} item dalam order</h3><pre class="escrow-raw">${escapeHtml(json(items))}</pre></div>`;
    };

    const loadStores = async () => {
        try {
            const response = await api('/api/marketplace/stores'); state.stores = (Array.isArray(response) ? response : response.data || []).filter((store) => ['shopee', 'shp'].includes(String(store.channel?.code || '').toLowerCase()) && store.is_active !== false);
            $('escrowStore').innerHTML = state.stores.length ? state.stores.map((store) => `<option value="${escapeHtml(store.id)}">${escapeHtml(store.name)}${store.connection_status === 'CONNECTED' ? ' · Connected' : ''}</option>`).join('') : '<option value="">Tidak ada toko Shopee aktif</option>';
        } catch (error) { $('escrowStore').innerHTML = '<option value="">Gagal memuat toko</option>'; showAlert(error.message); }
    };
    $('escrowLoad').addEventListener('click', () => { state.page = 1; loadList(); }); $('escrowSource').addEventListener('change', () => { state.page = 1; loadList(); }); $('escrowPrev').addEventListener('click', () => { if (state.page > 1) { state.page--; loadList(); } }); $('escrowNext').addEventListener('click', () => { if (state.more) { state.page++; loadList(); } }); $('escrowClose').addEventListener('click', () => $('escrowModalBackdrop').classList.remove('show')); $('escrowModalBackdrop').addEventListener('click', (event) => { if (event.target === $('escrowModalBackdrop')) $('escrowModalBackdrop').classList.remove('show'); }); document.addEventListener('keydown', (event) => { if (event.key === 'Escape') $('escrowModalBackdrop').classList.remove('show'); });
    if (window.Echo) {
        try {
            window.Echo.channel('marketplace').listen('OrderUpdated', (event) => {
                if (String(event.store_id) !== String($('escrowStore').value) || $('escrowSource').value !== 'orders') return;
                clearTimeout(state.refreshTimer);
                state.refreshTimer = setTimeout(() => {
                    showAlert(`Order ${event.order_sn || 'baru'} diterima dari push. Daftar escrow diperbarui.`, 'info');
                    state.page = 1;
                    loadList();
                }, 700);
            });
        } catch (error) { console.warn('Escrow realtime listener gagal:', error); }
    }
    loadStores();
})();
</script>
@endpush
