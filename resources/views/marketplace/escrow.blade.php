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
        .escrow-modal-backdrop{display:none;position:fixed;inset:0;z-index:1050;background:rgba(15,23,42,.58);padding:1rem;overflow:auto}.escrow-modal-backdrop.show{display:flex;align-items:flex-start;justify-content:center}.escrow-modal{width:min(920px,100%);margin:4vh auto;background:var(--card,#fff);border-radius:16px;box-shadow:0 24px 80px rgba(15,23,42,.3);overflow:hidden}.escrow-modal-head{display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;padding:1rem;border-bottom:1px solid var(--shp-border);background:linear-gradient(135deg,#f8fafc,#fff)}body[data-theme="dark"] .escrow-modal-head{background:rgba(30,41,59,.8)}.escrow-modal-title{font-weight:900;color:var(--shp-text);font-size:.95rem}.escrow-modal-sub{font-size:.7rem;color:var(--shp-muted);margin-top:.18rem}.escrow-close{border:0;background:transparent;color:var(--shp-muted);font-size:1.2rem;cursor:pointer}.escrow-detail-body{padding:1rem}.escrow-detail-summary{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:.55rem;margin-bottom:1rem}.escrow-detail-card{border:1px solid var(--shp-border);border-radius:10px;padding:.6rem .7rem}.escrow-detail-label{font-size:.62rem;text-transform:uppercase;letter-spacing:.04em;color:var(--shp-muted);font-weight:850}.escrow-detail-value{font-size:.78rem;font-weight:800;color:var(--shp-text);margin-top:.2rem;word-break:break-word}.escrow-detail-section{border-top:1px solid var(--shp-border);padding-top:.8rem;margin-top:.8rem}.escrow-detail-section h3{font-size:.76rem;font-weight:900;color:var(--shp-text);margin:0 0 .55rem}.escrow-income-groups{display:grid;gap:.7rem}.escrow-income-group{border:1px solid var(--shp-border);border-radius:10px;padding:.65rem;background:rgba(148,163,184,.035)}.escrow-income-group h4{display:flex;justify-content:space-between;gap:.5rem;margin:0 0 .5rem;color:var(--shp-text);font-size:.7rem;font-weight:900}.escrow-income-group-count{color:var(--shp-muted);font-size:.63rem;font-weight:700}.escrow-income-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.45rem}.escrow-income-row{display:flex;justify-content:space-between;gap:.6rem;border:1px solid var(--shp-border);border-radius:8px;padding:.45rem .55rem;font-size:.7rem}.escrow-income-row span:first-child{color:var(--shp-muted)}.escrow-income-row span:last-child{color:var(--shp-text);font-weight:800;text-align:right;word-break:break-word}.escrow-income-row code{font-size:.64rem;white-space:normal}.escrow-raw{max-height:280px;overflow:auto;background:#0f172a;color:#cbd5e1;padding:.75rem;border-radius:9px;font-size:.68rem;line-height:1.45;margin:0}.escrow-return-list{display:flex;flex-wrap:wrap;gap:.3rem}.escrow-return-list span{font-family:ui-monospace,monospace;font-size:.68rem;padding:.2rem .4rem;border-radius:5px;background:rgba(148,163,184,.12);color:var(--shp-text)}
        @media (max-width:760px){.escrow-filters{grid-template-columns:1fr 1fr}.escrow-filters .escrow-store-field{grid-column:1/-1}.escrow-filters .btn{grid-column:1/-1}.escrow-kpis{grid-template-columns:1fr}.escrow-detail-summary,.escrow-income-grid{grid-template-columns:1fr}.escrow-modal{margin:1vh auto}}
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
            <div class="escrow-help"><i class="bi bi-info-circle me-1"></i> Tabel utama hanya menampilkan field daftar order. Klik <strong>Detail</strong> untuk membuka seluruh rincian accounting live dari Shopee. Mode <strong>Order baru + escrow</strong> memakai order lokal yang masuk dari push, sedangkan mode <strong>Escrow release</strong> memakai rentang release Shopee (maksimal 15 hari). Tidak ada cache escrow baru.</div>
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
    const PAGE_SIZE = 100;
    const state = { stores: [], page: 1, more: false, items: [], details: {}, detailErrors: {}, detailLoading: {}, refreshTimer: null, loading: false, storePages: {}, storeMore: {}, storeReleaseTimes: {} };
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
    const activeStores = () => $('escrowStore').value === 'all' ? state.stores : [selectedStore()].filter(Boolean);
    const storeById = (storeId) => state.stores.find((store) => String(store.id) === String(storeId));
    const storeLabel = (storeId) => storeById(storeId)?.name || '—';
    const activeStoreLabel = () => $('escrowStore').value === 'all' ? `${state.stores.length} toko Shopee` : selectedStore()?.name || 'Toko';
    const detailKey = (storeId, orderSn) => `${storeId}:${orderSn}`;
    const api = async (url, options = {}) => {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const headers = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...(options.headers || {}) };
        if (options.method && options.method !== 'GET') headers['X-CSRF-TOKEN'] = csrf;
        const response = await fetch(url, { ...options, headers });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || payload.success === false) throw new Error(payload.message || `Request gagal (${response.status})`);
        return payload;
    };

    const listUrl = (storeId, pageNo = state.page) => {
        const params = new URLSearchParams({ date_from: $('escrowFrom').value, date_to: $('escrowTo').value, page_no: pageNo, page_size: PAGE_SIZE });
        const path = $('escrowSource').value === 'released' ? 'escrow-list' : 'escrow-orders';
        return `/api/marketplace/stores/${encodeURIComponent(storeId)}/${path}?${params}`;
    };
    const releaseListUrl = (storeId, pageNo = 1) => {
        const params = new URLSearchParams({ date_from: $('escrowFrom').value, date_to: $('escrowTo').value, page_no: pageNo, page_size: PAGE_SIZE });
        return `/api/marketplace/stores/${encodeURIComponent(storeId)}/escrow-list?${params}`;
    };

    const incomeLabels = {
        escrow_amount:'Escrow amount', buyer_total_amount:'Buyer total amount', original_price:'Original price', seller_discount:'Seller discount', shopee_discount:'Shopee discount', voucher_from_seller:'Voucher dari seller', voucher_from_shopee:'Voucher dari Shopee', coins:'Shopee Coins', buyer_paid_shipping_fee:'Shipping dibayar buyer', buyer_transaction_fee:'Buyer transaction fee', cross_border_tax:'Cross-border tax', payment_promotion:'Payment promotion', commission_fee:'Commission fee', service_fee:'Service fee', seller_transaction_fee:'Seller transaction fee', seller_order_processing_fee:'Biaya proses pesanan seller', seller_lost_compensation:'Seller lost compensation', seller_coin_cash_back:'Seller coin cashback', escrow_tax:'Escrow tax', final_shipping_fee:'Final shipping fee', actual_shipping_fee:'Actual shipping fee', order_chargeable_weight:'Chargeable weight', shopee_shipping_rebate:'Shipping rebate Shopee', shipping_fee_discount_from_3pl:'Shipping discount 3PL', seller_shipping_discount:'Seller shipping discount', estimated_shipping_fee:'Estimated shipping fee', seller_voucher_code:'Seller voucher code', drc_adjustable_refund:'DRC adjustable refund', cost_of_goods_sold:'Cost of goods sold', original_cost_of_goods_sold:'Original cost of goods sold', original_shopee_discount:'Original Shopee discount', seller_return_refund:'Seller return refund', reverse_shipping_fee:'Reverse shipping fee', final_product_protection:'Final product protection', credit_card_promotion:'Credit card promotion', credit_card_transaction_fee:'Credit card transaction fee', final_product_vat_tax:'Final product VAT tax', final_shipping_vat_tax:'Final shipping VAT tax', campaign_fee:'Campaign fee', sip_subsidy:'SIP subsidy', escrow_amount_pri:'Escrow amount (primary)', buyer_total_amount_pri:'Buyer total (primary)', original_price_pri:'Original price (primary)', seller_return_refund_pri:'Seller return refund (primary)', commission_fee_pri:'Commission fee (primary)', service_fee_pri:'Service fee (primary)', drc_adjustable_refund_pri:'DRC refund (primary)', pri_currency:'Primary currency', aff_currency:'Affiliate currency', exchange_rate:'Exchange rate', rsf_seller_protection_fee_claim_amount:'RSF protection claim', rsf_seller_protection_fee_premium_amount:'RSF protection premium', final_escrow_product_gst:'Final escrow product GST', final_escrow_shipping_gst:'Final escrow shipping GST', delivery_seller_protection_fee_premium_amount:'Delivery protection premium', final_return_to_seller_shipping_fee:'Return-to-seller shipping fee', items:'Items'
    };
    const buyerPaymentGroups = [
        { title: 'Total & biaya buyer', keys: ['buyer_total_amount', 'merchant_subtotal', 'buyer_service_fee', 'buyer_instant_fee', 'buyer_paid_extended_warranty', 'buyer_paid_installation_fee', 'shipping_fee', 'bulky_handling_fee', 'insurance_premium', 'import_processing_charge'] },
        { title: 'Voucher & diskon', keys: ['ads_voucher_discount', 'discount_pix', 'seller_voucher', 'shopee_voucher', 'shopee_coins_redeemed', 'trade_in_bonus', 'trade_in_discount'] },
        { title: 'Pajak & pungutan', keys: ['buyer_tax_amount', 'total_tax_and_fees_amount', 'vat', 'footwear_tax', 'icms_tax_amount', 'import_duty_and_excise_tax', 'import_tax_amount', 'iof_tax_amount', 'lvg_sales_tax_adjustment', 'shipping_fee_sst_amount'] },
        { title: 'Metode pembayaran', keys: ['buyer_payment_method', 'is_paid_by_credit_card', 'initial_buyer_txn_fee', 'shopeevip_subtotal', 'bcrs_deposit'] },
        { title: 'Informasi buyer & checkout', match: () => true },
    ];
    const incomeGroups = [
        { title: 'Nominal order & escrow', keys: ['escrow_amount', 'escrow_amount_after_adjustment', 'escrow_amount_pri', 'buyer_total_amount', 'buyer_total_amount_pri', 'order_discounted_price', 'order_original_price', 'order_selling_price', 'original_price', 'original_price_pri', 'cost_of_goods_sold', 'original_cost_of_goods_sold', 'order_chargeable_weight'] },
        { title: 'Biaya seller & marketplace', keys: ['commission_fee', 'commission_fee_pri', 'service_fee', 'service_fee_pri', 'seller_transaction_fee', 'seller_order_processing_fee', 'order_ams_commission_fee', 'ams_commission_fee', 'campaign_fee', 'fbs_fee', 'overseas_return_service_fee', 'ads_escrow_top_up_fee_or_technical_support_fee'] },
        { title: 'Diskon, voucher & coins', keys: ['seller_discount', 'order_seller_discount', 'voucher_from_seller', 'voucher_from_shopee', 'voucher_from_external_party', 'shopee_discount', 'original_shopee_discount', 'coins', 'seller_coin_cash_back', 'seller_voucher_code', 'pix_discount', 'trade_in_bonus_by_seller'] },
        { title: 'Pengiriman & perlindungan', keys: ['actual_shipping_fee', 'estimated_shipping_fee', 'final_shipping_fee', 'buyer_paid_shipping_fee', 'shopee_shipping_rebate', 'shipping_fee_discount_from_3pl', 'seller_shipping_discount', 'reverse_shipping_fee', 'reverse_shipping_fee_sst', 'return_to_seller_shipping_fee_sst', 'final_return_to_seller_shipping_fee', 'delivery_seller_protection_fee_premium_amount', 'shipping_seller_protection_fee_amount', 'final_product_protection', 'rsf_seller_protection_fee_claim_amount', 'rsf_seller_protection_fee_premium_amount', 'fsf_seller_protection_fee_claim_amount', 'lff_seller_protection_fee_amount', 'sip_subsidy'] },
        { title: 'Pajak & import', keys: ['escrow_tax', 'escrow_import_tax', 'cross_border_tax', 'final_product_vat_tax', 'final_shipping_vat_tax', 'final_escrow_product_gst', 'final_escrow_shipping_gst', 'shipping_fee_sst', 'sales_tax_on_lvg', 'th_import_duty', 'vat_on_imported_goods', 'withholding_tax', 'withholding_vat_tax', 'withholding_cit_tax', 'withholding_pit_tax'] },
        { title: 'Refund & penyesuaian', keys: ['drc_adjustable_refund', 'drc_adjustable_refund_pr', 'seller_return_refund', 'seller_return_refund_pr', 'total_adjustment_amount', 'seller_lost_compensation', 'prorated_coins_value_offset_return_items', 'prorated_payment_channel_promo_bank_offset_return_items', 'prorated_payment_channel_promo_shopee_offset_return_items', 'prorated_pix_discount_offset_return_items', 'prorated_seller_voucher_offset_return_items', 'prorated_shopee_voucher_offset_return_items'] },
        { title: 'Pembayaran & cicilan', keys: ['buyer_payment_method', 'buyer_transaction_fee', 'payment_promotion', 'credit_card_promotion', 'credit_card_transaction_fee', 'instalment_plan', 'tenure_info_list', 'installation_fee_paid_by_buyer', 'actual_installation_fee', 'buyer_paid_extended_warranty', 'bcrs_deposit'] },
        { title: 'Produk / items', keys: ['items'] },
        { title: 'Mata uang & konversi', keys: ['pri_currency', 'aff_currency', 'exchange_rate'] },
        { title: 'Order & settlement', match: () => true },
    ];
    const fieldLabel = (key) => incomeLabels[key] || key.split('_').map((part) => part.charAt(0).toUpperCase() + part.slice(1)).join(' ');
    const groupedFields = (data, groups, orderSn, storeId) => {
        const entries = Object.entries(data || {});
        const used = new Set();
        const matches = (group, key) => group.keys?.includes(key) || group.prefixes?.some((prefix) => key.startsWith(prefix)) || group.match?.(key);
        const renderGroup = (title, fields) => `<div class="escrow-income-group"><h4>${escapeHtml(title)} <span class="escrow-income-group-count">${fields.length} field</span></h4><div class="escrow-income-grid">${fields.map(([key, value]) => `<div class="escrow-income-row"><span>${escapeHtml(fieldLabel(key))}</span><span>${detailValue(key, value, orderSn, storeId)}</span></div>`).join('')}</div></div>`;
        const sections = groups.map((group) => {
            const fields = entries.filter(([key]) => !used.has(key) && matches(group, key));
            fields.forEach(([key]) => used.add(key));
            return fields.length ? renderGroup(group.title, fields) : '';
        }).filter(Boolean);
        const otherFields = entries.filter(([key]) => !used.has(key));
        if (otherFields.length) sections.push(renderGroup('Order & settlement', otherFields));
        return sections.length ? `<div class="escrow-income-groups">${sections.join('')}</div>` : '<span class="escrow-muted">Tidak ada field pada response Shopee.</span>';
    };
    const nonMoneyFields = new Set(['order_chargeable_weight', 'quantity_purchased', 'activity_id', 'item_id', 'model_id']);
    const incomeColumns = (items) => {
        const keys = [...Object.keys(incomeLabels)];
        items.forEach((item) => Object.keys(state.details[detailKey(item.store_id, item.order_sn)]?.income || {}).forEach((key) => { if (!keys.includes(key)) keys.push(key); }));
        return keys;
    };
    const detailValue = (key, value, orderSn = '', storeId = '') => {
        if (value === null || value === undefined || value === '') return '<span class="escrow-muted">—</span>';
        if (key === 'items' && Array.isArray(value)) return `<button type="button" class="btn btn-sm btn-ship-outline btn-pill escrow-items-btn" data-order-sn="${escapeHtml(orderSn)}" data-store-id="${escapeHtml(storeId)}"><i class="bi bi-list-ul me-1"></i>Lihat ${value.length} item</button>`;
        if (Array.isArray(value)) return `<code>${escapeHtml(JSON.stringify(value))}</code>`;
        if (typeof value === 'object') return `<code>${escapeHtml(JSON.stringify(value))}</code>`;
        if (nonMoneyFields.has(key) && Number.isFinite(Number(value))) return escapeHtml(Number(value).toLocaleString('id-ID'));
        if (typeof value === 'number' || (typeof value === 'string' && value.trim() !== '' && Number.isFinite(Number(value)))) return money(value);
        return escapeHtml(value);
    };

    const renderRows = (items) => {
        if (!items.length) { $('escrowTableWrap').innerHTML = `<div class="escrow-empty">Tidak ada data pada rentang ${$('escrowSource').value === 'released' ? 'release' : 'order'} yang dipilih.</div>`; return; }
        const rows = items.map((item, index) => {
            return `<tr><td><span class="escrow-order">${escapeHtml(item.order_sn || '—')}</span></td><td>${escapeHtml(storeLabel(item.store_id))}</td><td>${escapeHtml(item.order_status || '—')}</td><td>${money(item.order_total)}</td><td class="escrow-money">${money(item.payout_amount)}</td><td class="escrow-muted">${escapeHtml(dateTime(item.ordered_at))}</td><td class="escrow-muted">${escapeHtml(dateTime(item.escrow_release_at))}</td><td class="text-end"><button type="button" class="btn btn-sm btn-ship-outline btn-pill escrow-detail-btn" data-index="${index}"><i class="bi bi-eye me-1"></i>Detail</button></td></tr>`;
        }).join('');
        $('escrowTableWrap').innerHTML = `<table class="table escrow-table"><thead><tr><th>Order SN</th><th>Toko</th><th>Status order</th><th>Nilai order</th><th>Payout amount</th><th>Tanggal order</th><th>Tanggal release</th><th>Aksi</th></tr></thead><tbody>${rows}</tbody></table>`;
        document.querySelectorAll('.escrow-detail-btn').forEach((button) => button.addEventListener('click', () => { const item = items[Number(button.dataset.index)]; openDetail(item?.order_sn, item?.store_id); }));
    };

    const renderPagination = () => { $('escrowPagination').style.display = 'flex'; $('escrowPrev').disabled = state.page <= 1; $('escrowNext').disabled = !state.more; $('escrowPageInfo').textContent = `Halaman ${state.page}${state.more ? ' · masih ada data berikutnya' : ' · halaman terakhir'}`; };
    const setLoading = (loading) => { const button = $('escrowLoad'); button.disabled = loading; button.querySelector('.escrow-load-label').innerHTML = loading ? '<span class="escrow-spinner"></span> Memuat…' : 'Tampilkan escrow'; };
    const detailUrl = (storeId, orderSn) => `/api/marketplace/stores/${encodeURIComponent(storeId)}/escrow-detail?order_sn=${encodeURIComponent(orderSn)}`;
    const decorateItems = (items, store) => (Array.isArray(items) ? items : []).map((item) => ({ ...item, store_id: store.id }));
    const canLookupRelease = () => {
        const rangeDays = Math.round((new Date(`${$('escrowTo').value}T00:00:00`) - new Date(`${$('escrowFrom').value}T00:00:00`)) / 86400000) + 1;
        return rangeDays <= 15;
    };
    const fetchReleaseTimes = async (store) => {
        if ($('escrowSource').value !== 'orders' || !canLookupRelease() || state.storeReleaseTimes[store.id]) return;
        const releaseTimes = {};
        let pageNo = 1;
        let more = true;
        while (more && pageNo <= 100) {
            const payload = await api(releaseListUrl(store.id, pageNo));
            (payload.data?.items || []).forEach((item) => {
                if (item.order_sn && item.escrow_release_at) releaseTimes[item.order_sn] = { time: item.escrow_release_time, at: item.escrow_release_at, payout: item.payout_amount };
            });
            more = Boolean(payload.data?.more);
            pageNo++;
        }
        state.storeReleaseTimes[store.id] = releaseTimes;
    };
    const addReleaseTimes = (items, store) => items.map((item) => {
        const release = state.storeReleaseTimes[store.id]?.[item.order_sn];
        return release ? { ...item, escrow_release_time: release.time, escrow_release_at: release.at, payout_amount: item.payout_amount ?? release.payout } : item;
    });
    const fetchStorePage = async (store, pageNo) => {
        const payload = await api(listUrl(store.id, pageNo));
        state.storePages[store.id] = state.storePages[store.id] || {};
        let items = decorateItems(payload.data?.items, store);
        if ($('escrowSource').value === 'orders') {
            try {
                await fetchReleaseTimes(store);
                items = addReleaseTimes(items, store);
            } catch (error) {
                console.warn(`Lookup tanggal release toko ${store.name} gagal:`, error);
            }
        }
        state.storePages[store.id][pageNo] = items;
        state.storeMore[store.id] = state.storeMore[store.id] || {};
        state.storeMore[store.id][pageNo] = Boolean(payload.data?.more);
    };
    const mergedStoreItems = (stores) => {
        const unique = new Map();
        stores.forEach((store) => Object.values(state.storePages[store.id] || {}).flat().forEach((item) => unique.set(detailKey(item.store_id, item.order_sn), item)));
        return [...unique.values()].sort((left, right) => {
            const leftTime = Date.parse(left.escrow_release_at || left.ordered_at || '') || 0;
            const rightTime = Date.parse(right.escrow_release_at || right.ordered_at || '') || 0;
            return rightTime - leftTime;
        });
    };
    const loadAllStorePage = async (stores) => {
        for (let pageNo = 1; pageNo <= state.page; pageNo++) {
            const pending = stores.filter((store) => !state.storePages[store.id]?.[pageNo]);
            await Promise.all(pending.map((store) => fetchStorePage(store, pageNo)));
        }
        const merged = mergedStoreItems(stores);
        const start = (state.page - 1) * PAGE_SIZE;
        return {
            items: merged.slice(start, start + PAGE_SIZE),
            more: merged.length > start + PAGE_SIZE || stores.some((store) => state.storeMore[store.id]?.[state.page]),
        };
    };

    const loadList = async () => {
        hideAlert();
        const stores = activeStores();
        if (!stores.length) { showAlert('Tidak ada toko Shopee aktif.'); return; }
        if (!$('escrowFrom').value || !$('escrowTo').value) { showAlert('Rentang tanggal wajib diisi.'); return; }
        if ($('escrowFrom').value > $('escrowTo').value) { showAlert('Tanggal mulai tidak boleh melewati tanggal akhir.'); return; }
        const rangeDays = Math.round((new Date(`${$('escrowTo').value}T00:00:00`) - new Date(`${$('escrowFrom').value}T00:00:00`)) / 86400000) + 1;
        if ($('escrowSource').value === 'released' && rangeDays > 15) { showAlert('Rentang tanggal maksimal 15 hari sesuai batas endpoint escrow release Shopee.'); return; }
        if (state.page === 1) { state.storePages = {}; state.storeMore = {}; state.storeReleaseTimes = {}; }
        state.loading = true;
        setLoading(true); $('escrowStatus').textContent = 'Memuat…'; $('escrowTableWrap').innerHTML = '<div class="escrow-empty"><span class="escrow-spinner"></span> Mengambil data live dari Shopee…</div>';
        try {
            const payload = $('escrowStore').value === 'all'
                ? await loadAllStorePage(stores)
                : await fetchStorePage(stores[0], state.page).then(() => ({ items: state.storePages[stores[0].id]?.[state.page] || [], more: Boolean(state.storeMore[stores[0].id]?.[state.page]) }));
            state.items = payload.items || []; state.more = Boolean(payload.more); state.details = {}; state.detailErrors = {}; state.detailLoading = {};
            renderRows(state.items); renderPagination();
            $('escrowCount').textContent = state.items.length.toLocaleString('id-ID'); $('escrowTotal').textContent = money(state.items.reduce((sum, item) => sum + Number(item.payout_amount || 0), 0)); $('escrowStatus').textContent = 'Berhasil'; $('escrowStatusNote').textContent = `${activeStoreLabel()} · daftar dimuat live; klik Detail untuk rincian`;
        } catch (error) { $('escrowTableWrap').innerHTML = `<div class="escrow-empty">Tidak dapat memuat data escrow.</div>`; $('escrowPagination').style.display = 'none'; $('escrowStatus').textContent = 'Gagal'; $('escrowStatusNote').textContent = 'Periksa koneksi/credential Shopee'; showAlert(error.message); }
        finally { state.loading = false; setLoading(false); }
    };

    const openDetail = async (orderSn, storeId) => {
        if (!orderSn || !storeId) return; $('escrowModalBackdrop').classList.add('show'); $('escrowModalTitle').textContent = `Detail escrow · ${orderSn}`; $('escrowModalSub').textContent = `${storeLabel(storeId)} · live dari Shopee`; $('escrowDetailBody').innerHTML = '<div class="escrow-empty"><span class="escrow-spinner"></span> Memuat detail dari Shopee…</div>';
        try {
            const key = detailKey(storeId, orderSn);
            const payload = state.details[key]?.raw_response ? { data: state.details[key] } : await api(detailUrl(storeId, orderSn));
            const detail = payload.data || {};
            state.details[key] = detail;
            const income = detail.income || {};
            const buyerPayment = detail.buyer_payment_info || {};
            const resolvedOrderSn = detail.order_sn || orderSn;
            const returnOrders = Array.isArray(detail.return_order_sn_list) ? detail.return_order_sn_list : [];
            $('escrowDetailBody').innerHTML = `<div class="escrow-detail-summary"><div class="escrow-detail-card"><div class="escrow-detail-label">Order SN</div><div class="escrow-detail-value">${escapeHtml(resolvedOrderSn)}</div></div><div class="escrow-detail-card"><div class="escrow-detail-label">Toko</div><div class="escrow-detail-value">${escapeHtml(storeLabel(storeId))}</div></div><div class="escrow-detail-card"><div class="escrow-detail-label">Buyer</div><div class="escrow-detail-value">${escapeHtml(detail.buyer_user_name || '—')}</div></div><div class="escrow-detail-card"><div class="escrow-detail-label">Escrow amount</div><div class="escrow-detail-value escrow-money">${money(income.escrow_amount)}</div></div></div><div class="escrow-detail-section"><h3>Return order</h3><div class="escrow-return-list">${returnOrders.length ? returnOrders.map((value) => `<span>${escapeHtml(value)}</span>`).join('') : '<span class="escrow-muted">Tidak ada return order</span>'}</div></div>${Object.keys(buyerPayment).length ? `<div class="escrow-detail-section"><h3>Buyer payment info <span class="escrow-income-group-count">${Object.keys(buyerPayment).length} field</span></h3>${groupedFields(buyerPayment, buyerPaymentGroups, resolvedOrderSn, storeId)}</div>` : ''}<div class="escrow-detail-section"><h3>Order income <span class="escrow-income-group-count">${Object.keys(income).length} field</span></h3>${groupedFields(income, incomeGroups, resolvedOrderSn, storeId)}</div><div class="escrow-detail-section"><h3>Raw response Shopee</h3><pre class="escrow-raw">${escapeHtml(json(detail.raw_response))}</pre></div>`;
            document.querySelectorAll('#escrowDetailBody .escrow-items-btn').forEach((button) => button.addEventListener('click', () => openItems(resolvedOrderSn, storeId)));
        } catch (error) { $('escrowDetailBody').innerHTML = `<div class="escrow-empty">${escapeHtml(error.message)}</div>`; }
    };

    const openItems = (orderSn, storeId) => {
        const detail = state.details[detailKey(storeId, orderSn)];
        const items = detail?.income?.items;
        if (!Array.isArray(items)) return;
        $('escrowModalBackdrop').classList.add('show');
        $('escrowModalTitle').textContent = `Items escrow · ${orderSn}`;
        $('escrowModalSub').textContent = `${storeLabel(storeId)} · raw items dari Shopee`;
        $('escrowDetailBody').innerHTML = `<div class="escrow-detail-section" style="border-top:0;padding-top:0;margin-top:0"><h3>${items.length} item dalam order</h3><pre class="escrow-raw">${escapeHtml(json(items))}</pre></div>`;
    };

    const loadStores = async () => {
        try {
            const response = await api('/api/marketplace/stores'); state.stores = (Array.isArray(response) ? response : response.data || []).filter((store) => ['shopee', 'shp'].includes(String(store.channel?.code || '').toLowerCase()) && store.is_active !== false);
            $('escrowStore').innerHTML = state.stores.length ? `<option value="all">Semua toko Shopee (${state.stores.length})</option>${state.stores.map((store) => `<option value="${escapeHtml(store.id)}">${escapeHtml(store.name)}${store.connection_status === 'CONNECTED' ? ' · Connected' : ''}</option>`).join('')}` : '<option value="">Tidak ada toko Shopee aktif</option>';
        } catch (error) { $('escrowStore').innerHTML = '<option value="">Gagal memuat toko</option>'; showAlert(error.message); }
    };
    $('escrowLoad').addEventListener('click', () => { state.page = 1; loadList(); }); $('escrowSource').addEventListener('change', () => { state.page = 1; loadList(); }); $('escrowPrev').addEventListener('click', () => { if (state.page > 1) { state.page--; loadList(); } }); $('escrowNext').addEventListener('click', () => { if (state.more) { state.page++; loadList(); } }); $('escrowClose').addEventListener('click', () => $('escrowModalBackdrop').classList.remove('show')); $('escrowModalBackdrop').addEventListener('click', (event) => { if (event.target === $('escrowModalBackdrop')) $('escrowModalBackdrop').classList.remove('show'); }); document.addEventListener('keydown', (event) => { if (event.key === 'Escape') $('escrowModalBackdrop').classList.remove('show'); });
    if (window.Echo) {
        try {
            window.Echo.channel('marketplace').listen('OrderUpdated', (event) => {
                const matchesStore = $('escrowStore').value === 'all'
                    ? state.stores.some((store) => String(store.id) === String(event.store_id))
                    : String(event.store_id) === String($('escrowStore').value);
                if (!matchesStore || $('escrowSource').value !== 'orders') return;
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
