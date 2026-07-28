@extends('layouts.app')
@section('title', 'Marketplace - Rincian Penghasilan')

@include('marketplace._shared')

@push('head')
    <style>
        .income-grid{
            display:grid;
            grid-template-columns:repeat(6,minmax(0,1fr));
            gap:.65rem;
            margin-bottom:1rem;
        }
        .income-kpi{
            border:1px solid rgba(148,163,184,.22);
            border-radius:14px;
            padding:.75rem .85rem;
            background:linear-gradient(180deg,#fff 0%,#fcfcfd 100%);
            overflow:hidden;
        }
        body[data-theme="dark"] .income-kpi{
            background:rgba(15,23,42,.92);
            border-color:rgba(51,65,85,.85);
        }
        .income-kpi-label{
            font-size:.64rem;
            font-weight:900;
            text-transform:uppercase;
            letter-spacing:.04em;
            color:#64748b;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }
        .income-kpi-value{
            font-size:1.06rem;
            font-weight:900;
            line-height:1.15;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
            color:var(--shp-text);
            margin-top:.15rem;
        }
        .income-kpi-note{
            font-size:.7rem;
            color:var(--shp-muted);
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
            margin-top:.14rem;
        }
        .income-filters{
            display:flex;
            flex-wrap:wrap;
            gap:.65rem;
            align-items:flex-end;
        }
        .income-field{
            flex:1 1 170px;
            min-width:170px;
        }
        .income-field.wide{ flex:2 1 240px; }
        .income-field label{
            display:block;
            margin-bottom:.3rem;
            font-size:.68rem;
            font-weight:900;
            text-transform:uppercase;
            letter-spacing:.04em;
            color:#64748b;
        }
        .income-field input,
        .income-field select{
            width:100%;
            height:36px;
            border-radius:9px;
            border:1px solid rgba(148,163,184,.28);
            background:var(--card,#fff);
            color:var(--shp-text);
            font-size:.82rem;
            padding:.35rem .7rem;
            outline:none;
        }
        .income-field input:focus,
        .income-field select:focus{
            border-color:#2563eb;
            box-shadow:0 0 0 3px rgba(37,99,235,.08);
        }
        body[data-theme="dark"] .income-field input,
        body[data-theme="dark"] .income-field select{
            background:rgba(15,23,42,.72);
            border-color:rgba(255,255,255,.12);
            color:#e2e8f0;
        }
        .income-fee-list{
            list-style:none;
            padding:0;
            margin:0;
            font-size:.7rem;
            color:#64748b;
        }
        .income-fee-list li{
            display:flex;
            justify-content:space-between;
            gap:.45rem;
            margin-bottom:2px;
        }
        .income-fee-val{ font-weight:700; color:#b91c1c; }
        .income-sect{
            margin-top:.4rem;
            padding-top:.3rem;
            border-top:1px dashed rgba(148,163,184,.24);
            font-size:.62rem;
            font-weight:900;
            text-transform:uppercase;
            letter-spacing:.04em;
            color:#64748b;
        }
        .income-breakdown-btn{
            border:1px solid rgba(37,99,235,.18);
            background:rgba(37,99,235,.06);
            color:#1d4ed8;
            font-size:.74rem;
            font-weight:800;
            border-radius:999px;
            padding:.38rem .72rem;
            line-height:1;
            transition:background .15s ease, transform .15s ease, border-color .15s ease;
        }
        .income-breakdown-btn:hover{
            background:rgba(37,99,235,.12);
            border-color:rgba(37,99,235,.28);
            transform:translateY(-1px);
        }
        .income-breakdown-summary{
            display:grid;
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:.6rem;
            margin-bottom:1rem;
        }
        .income-breakdown-pill{
            padding:.65rem .75rem;
            border:1px solid rgba(148,163,184,.18);
            border-radius:12px;
            background:rgba(248,250,252,.8);
        }
        body[data-theme="dark"] .income-breakdown-pill{
            background:rgba(15,23,42,.88);
        }
        .income-breakdown-pill .label{
            display:block;
            font-size:.64rem;
            font-weight:900;
            text-transform:uppercase;
            letter-spacing:.04em;
            color:#64748b;
        }
        .income-breakdown-pill .value{
            display:block;
            margin-top:.14rem;
            font-size:.95rem;
            font-weight:900;
            color:var(--shp-text);
        }
        .income-scroll{
            max-height:66vh;
            overflow:auto;
        }
        @media (max-width: 1100px){
            .income-grid{ grid-template-columns:repeat(3,minmax(0,1fr)); }
        }
        @media (max-width: 640px){
            .income-grid{ grid-template-columns:repeat(2,minmax(0,1fr)); }
            .income-field{ flex:1 1 100%; min-width:0; }
            .income-field.wide{ flex:1 1 100%; }
        }
    </style>
@endpush

@section('content')
<div class="page-wrap">
    <div class="ship-topbar">
        <div>
            <h1 class="title">Rincian Penghasilan</h1>
            <div class="sub">Gross setelah voucher, beban seller/pembeli/platform, dan dana cair per order.</div>
        </div>
        <div class="controls">
            <a href="{{ route('marketplace.settlement') }}" class="btn btn-sm btn-ship-outline btn-pill">
                <i class="bi bi-arrow-left"></i> Ke Settlement
            </a>
            <a href="{{ route('marketplace.profit') }}" class="btn btn-sm btn-ship-outline btn-pill">
                <i class="bi bi-graph-up"></i> Ke Profit
            </a>
            <button class="btn btn-sm btn-ship-outline btn-pill" onclick="resetFilters()">Reset Filter</button>
            <button class="btn btn-sm btn-ship-outline btn-pill" onclick="loadIncomeDetail()">Refresh</button>
        </div>
    </div>

    <div class="card-main" style="padding:1rem 1.1rem; margin-bottom:1rem;">
        <div class="income-filters">
            <div class="income-field">
                <label>Toko</label>
                <select id="filterStore" onchange="goFirstPage()">
                    <option value="">Semua</option>
                </select>
            </div>
            <div class="income-field">
                <label>Status Order</label>
                <select id="filterStatus" onchange="goFirstPage()">
                    <option value="">Semua</option>
                    <option value="COMPLETED">Selesai</option>
                    <option value="SHIPPED">Dikirim</option>
                    <option value="CANCELLED">Batal</option>
                </select>
            </div>
            <div class="income-field">
                <label>Status Cair</label>
                <select id="filterSettlementStatus" onchange="goFirstPage()">
                    <option value="">Semua</option>
                    <option value="cair">Cair</option>
                    <option value="belum_cair">Belum Cair</option>
                </select>
            </div>
            <div class="income-field wide">
                <label>Cari</label>
                <input id="filterSearch" type="text" placeholder="Order SN / produk / SKU..." oninput="onSearchInput()">
            </div>
            <div class="income-field">
                <label>Tgl Order Dari</label>
                <input id="filterOrderFrom" type="date" onchange="goFirstPage()">
            </div>
            <div class="income-field">
                <label>Tgl Order Sampai</label>
                <input id="filterOrderTo" type="date" onchange="goFirstPage()">
            </div>
            <div class="income-field">
                <label>Tgl Cair Dari</label>
                <input id="filterSettlementFrom" type="date" onchange="goFirstPage()">
            </div>
            <div class="income-field">
                <label>Tgl Cair Sampai</label>
                <input id="filterSettlementTo" type="date" onchange="goFirstPage()">
            </div>
        </div>
    </div>

    <div class="income-grid">
        <div class="income-kpi">
            <div class="income-kpi-label">Order Cair</div>
            <div class="income-kpi-value" id="kpiCount">-</div>
            <div class="income-kpi-note">Order yang masuk filter aktif</div>
        </div>
        <div class="income-kpi">
            <div class="income-kpi-label">Gross Sales</div>
            <div class="income-kpi-value" id="kpiGross">-</div>
            <div class="income-kpi-note">Sebelum voucher</div>
        </div>
        <div class="income-kpi">
            <div class="income-kpi-label">Voucher</div>
            <div class="income-kpi-value" id="kpiVoucher">-</div>
            <div class="income-kpi-note">Voucher toko + platform</div>
        </div>
    <div class="income-kpi">
        <div class="income-kpi-label">Gross Setelah Voucher</div>
        <div class="income-kpi-value" id="kpiGrossAfterVoucher">-</div>
        <div class="income-kpi-note">Setelah voucher toko + platform</div>
    </div>
        <div class="income-kpi">
            <div class="income-kpi-label">Beban Seller</div>
            <div class="income-kpi-value" id="kpiFeeTotal">-</div>
            <div class="income-kpi-note" id="kpiFeePercent">-</div>
        </div>
        <div class="income-kpi">
            <div class="income-kpi-label">Dana Cair</div>
            <div class="income-kpi-value" id="kpiNetPayout">-</div>
            <div class="income-kpi-note">Net payout dari marketplace</div>
        </div>
    </div>

    <div class="card-main">
        <div id="incomeBody" class="income-scroll">
            <div style="padding:2rem;text-align:center;color:var(--shp-muted);font-size:.85rem;">
                <span class="spinner-border spinner-border-sm"></span> Memuat data...
            </div>
        </div>
    </div>

    <div class="modal fade" id="incomeBreakdownModal" tabindex="-1" aria-labelledby="incomeBreakdownModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="incomeBreakdownModalLabel">Rincian Potongan</h5>
                        <div class="text-muted small" id="incomeBreakdownModalSub">-</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body" id="incomeBreakdownModalBody">
                    <div class="text-center py-4 text-muted">Pilih baris order untuk melihat rincian potongan.</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const { api, fmt, fmtRp, esc } = window.mpHelpers;
    const storageKey = 'marketplace:income_detail_filters:v1';
    const $ = (id) => document.getElementById(id);

    let stores = [];
    let rows = [];
    let paginationData = null;
    let currentPage = 1;
    let searchTimer = null;

    function readState() {
        try {
            return JSON.parse(localStorage.getItem(storageKey) || '{}') || {};
        } catch (e) {
            return {};
        }
    }

    function saveState() {
        localStorage.setItem(storageKey, JSON.stringify({
            store_id: $('filterStore')?.value || '',
            status: $('filterStatus')?.value || '',
            settlement_status: $('filterSettlementStatus')?.value || '',
            search: $('filterSearch')?.value || '',
            order_from: $('filterOrderFrom')?.value || '',
            order_to: $('filterOrderTo')?.value || '',
            settlement_from: $('filterSettlementFrom')?.value || '',
            settlement_to: $('filterSettlementTo')?.value || '',
        }));
    }

    function restoreState() {
        const state = readState();
        if ($('filterStore')) $('filterStore').value = state.store_id || '';
        if ($('filterStatus')) $('filterStatus').value = state.status || '';
        if ($('filterSettlementStatus')) $('filterSettlementStatus').value = state.settlement_status || '';
        if ($('filterSearch')) $('filterSearch').value = state.search || '';
        if ($('filterOrderFrom')) $('filterOrderFrom').value = state.order_from || '';
        if ($('filterOrderTo')) $('filterOrderTo').value = state.order_to || '';
        if ($('filterSettlementFrom')) $('filterSettlementFrom').value = state.settlement_from || '';
        if ($('filterSettlementTo')) $('filterSettlementTo').value = state.settlement_to || '';
    }

    function buildParams() {
        const params = new URLSearchParams();
        if ($('filterStore')?.value) params.append('store_id', $('filterStore').value);
        if ($('filterStatus')?.value) params.append('status', $('filterStatus').value);
        if ($('filterSettlementStatus')?.value) params.append('settlement_status', $('filterSettlementStatus').value);
        if ($('filterSearch')?.value) params.append('search', $('filterSearch').value);
        if ($('filterOrderFrom')?.value) params.append('order_date_from', $('filterOrderFrom').value);
        if ($('filterOrderTo')?.value) params.append('order_date_to', $('filterOrderTo').value);
        if ($('filterSettlementFrom')?.value) params.append('settlement_date_from', $('filterSettlementFrom').value);
        if ($('filterSettlementTo')?.value) params.append('settlement_date_to', $('filterSettlementTo').value);
        params.append('page', currentPage);
        params.append('per_page', 50);
        return params;
    }

    function goFirstPage() {
        currentPage = 1;
        loadIncomeDetail();
    }

    window.goFirstPage = goFirstPage;

    window.onSearchInput = function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(goFirstPage, 250);
    };

    window.resetFilters = function () {
        localStorage.removeItem(storageKey);
        currentPage = 1;
        if ($('filterStore')) $('filterStore').value = '';
        if ($('filterStatus')) $('filterStatus').value = '';
        if ($('filterSettlementStatus')) $('filterSettlementStatus').value = '';
        if ($('filterSearch')) $('filterSearch').value = '';
        if ($('filterOrderFrom')) $('filterOrderFrom').value = '';
        if ($('filterOrderTo')) $('filterOrderTo').value = '';
        if ($('filterSettlementFrom')) $('filterSettlementFrom').value = '';
        if ($('filterSettlementTo')) $('filterSettlementTo').value = '';
        loadIncomeDetail();
    };

    function renderKpi(meta) {
        if (!meta) return;
        $('kpiCount').textContent = meta.kpi_count != null ? Number(meta.kpi_count).toLocaleString('id-ID') : '-';
        $('kpiGross').textContent = fmtRp(meta.kpi_gross || 0);
        $('kpiVoucher').textContent = fmtRp(meta.kpi_voucher || 0);
        $('kpiGrossAfterVoucher').textContent = fmtRp(meta.kpi_gross_after_voucher || 0);
        $('kpiFeeTotal').textContent = fmtRp(meta.kpi_fees || 0);
        $('kpiFeePercent').textContent = Number.isFinite(Number(meta.kpi_fee_pct))
            ? `${Number(meta.kpi_fee_pct).toFixed(1)}% dari gross setelah voucher`
            : '-';
        $('kpiNetPayout').textContent = fmtRp(meta.kpi_net || 0);
    }

    function categoryTone(category) {
        if (category === 'buyer') return { color: '#0f766e', sign: '' };
        if (category === 'platform') return { color: '#15803d', sign: '' };
        if (category === 'voucher') return { color: '#b91c1c', sign: '-' };
        if (category === 'adjustment') return { color: '#b91c1c', sign: '-' };
        return { color: '#b91c1c', sign: '-' };
    }

    function categoryTitle(category) {
        if (category === 'buyer') return 'Beban Pembeli';
        if (category === 'platform') return 'Beban Platform';
        if (category === 'voucher') return 'Voucher';
        if (category === 'adjustment') return 'Penyesuaian';
        return 'Beban Seller';
    }

    function renderFeeLine(label, value, force = false, percent = null, category = 'seller') {
        const n = Number(value || 0);
        if (!force && !n) return '';
        const tone = categoryTone(category);
        const sign = tone.sign || (n < 0 ? '-' : '');
        const pct = percent !== null && percent !== undefined
            ? ` <span style="font-size:.66rem;color:var(--shp-muted);">(${Number(percent).toFixed(1)}%)</span>`
            : '';
        return `<li><span>${esc(label)}:</span><span class="income-fee-val" style="color:${tone.color};">${sign}${fmtRp(Math.abs(n))}${pct}</span></li>`;
    }

    function fmtOptionalRp(value) {
        const n = Number(value || 0);
        return n > 0 ? fmtRp(Math.abs(n)) : '—';
    }

    function fmtPct(value) {
        const n = Number(value);
        return Number.isFinite(n) ? `${n.toFixed(1)}%` : '-';
    }

    function renderBreakdownSummary(s) {
        const gross = Number(s.gross_amount ?? s.buyer_payment_amount ?? 0);
        const buyerPaid = Number(s.buyer_paid_amount ?? s.buyer_payment_amount ?? 0);
        const voucherPlatform = Number(s.voucher_platform_total ?? 0);
        const voucherToko = Number(s.voucher_toko_total ?? s.seller_voucher ?? 0);
        const voucherTotal = Number(s.voucher_total ?? (voucherPlatform + voucherToko));
        const feeTotal = Number(s.seller_burden_total ?? s.fee_breakdown_total ?? s.fee_total ?? 0);
        const net = Number(s.final_income || 0);
        const grossAfterVoucher = Number(s.gross_after_voucher_toko || Math.max(gross - voucherToko, 0));

        return `
            <div class="income-breakdown-summary">
                <div class="income-breakdown-pill"><span class="label">Buyer Paid</span><span class="value">${fmtRp(buyerPaid)}</span></div>
                <div class="income-breakdown-pill"><span class="label">Gross Sales</span><span class="value">${fmtRp(gross)}</span></div>
                <div class="income-breakdown-pill"><span class="label">Voucher Platform</span><span class="value" style="color:#b91c1c">-${fmtRp(voucherPlatform)}</span></div>
                <div class="income-breakdown-pill"><span class="label">Voucher Toko</span><span class="value" style="color:#b91c1c">-${fmtRp(voucherToko)}</span></div>
                <div class="income-breakdown-pill"><span class="label">Gross Setelah Voucher Toko</span><span class="value">${fmtRp(grossAfterVoucher)}</span></div>
                <div class="income-breakdown-pill"><span class="label">Beban Seller</span><span class="value" style="color:#b91c1c">-${fmtRp(feeTotal)}</span></div>
                <div class="income-breakdown-pill"><span class="label">Total Voucher</span><span class="value" style="color:#b91c1c">-${fmtRp(voucherTotal)}</span></div>
                <div class="income-breakdown-pill"><span class="label">Dana Cair</span><span class="value" style="color:#16a34a">${fmtRp(net)}</span></div>
            </div>
        `;
    }

    function fmtShortDate(d) {
        if (!d) return '—';
        const dt = new Date(d);
        if (Number.isNaN(dt.getTime())) return '—';
        return dt.toLocaleString('id-ID', {
            day: '2-digit',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    function renderBreakdownModalContent(s) {
        const feePercent = s.fee_percent !== undefined && s.fee_percent !== null ? Number(s.fee_percent) : null;
        const voucherPlatform = Number(s.voucher_platform_total ?? 0);
        const voucherToko = Number(s.voucher_toko_total ?? s.seller_voucher ?? 0);
        const grossAfterVoucher = Number(s.gross_after_voucher_toko || Math.max(Number(s.gross_amount ?? s.buyer_payment_amount ?? 0) - voucherToko, 0));

        return `
            ${renderBreakdownSummary(s)}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="small text-muted">Detail per kategori</div>
                <div class="small text-muted">${Number.isFinite(feePercent) ? feePercent.toFixed(1) + '% dari gross setelah voucher total' : '-'}</div>
            </div>
            <div class="mb-3 small text-muted">Gross setelah voucher toko: <strong>${fmtRp(grossAfterVoucher)}</strong></div>
            <ul class="income-fee-list">${feeBreakdownList(s)}</ul>
        `;
    }

    window.openBreakdownModal = function (index) {
        const s = rows[index];
        if (!s) return;

        const title = $('incomeBreakdownModalLabel');
        const sub = $('incomeBreakdownModalSub');
        const body = $('incomeBreakdownModalBody');

        title.textContent = `Rincian Potongan • ${s.channel_order_id || '-'}`;
        sub.textContent = `${s.store?.name || '-'} • ${s.order?.order_status || s.order_status || '-'}`;
        body.innerHTML = renderBreakdownModalContent(s);

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('incomeBreakdownModal'));
        modal.show();
    };

    function feeBreakdownList(s) {
        const breakdown = Array.isArray(s.fee_breakdown) ? s.fee_breakdown : [];
        const feeTotal = Number(s.seller_burden_total ?? s.fee_breakdown_total ?? s.fee_total ?? 0);
        const buyerTotal = Number(s.buyer_burden_total ?? 0);
        const platformTotal = Number(s.platform_burden_total ?? 0);
        const voucherPlatformTotal = Number(s.voucher_platform_total ?? 0);
        const voucherTokoTotal = Number(s.voucher_toko_total ?? s.seller_voucher ?? 0);
        const voucherTotal = Number(s.voucher_total ?? (voucherPlatformTotal + voucherTokoTotal));
        const adjustmentTotal = Number(s.adjustment_total ?? 0);
        const grandTotal = Number(s.total_burden_total ?? (feeTotal + buyerTotal + platformTotal + voucherTotal + adjustmentTotal));
        const feePercent = s.fee_percent !== undefined && s.fee_percent !== null ? Number(s.fee_percent) : null;
        const grouped = breakdown.reduce((acc, item) => {
            const cat = item.category || 'seller';
            if (!acc[cat]) acc[cat] = [];
            acc[cat].push(item);
            return acc;
        }, {});

        const renderSection = (category, total, items) => {
            if (!items.length && !total) return '';
            const tone = categoryTone(category);
            const sign = tone.sign || '';
            let html = `<li class="income-sect" style="color:${tone.color};">${esc(categoryTitle(category))}<span style="float:right;color:${tone.color};">${sign}${fmtRp(Math.abs(total))}</span></li>`;
            if (items.length) {
                items.forEach(item => {
                    html += renderFeeLine(item.label || 'Biaya', item.amount ?? 0, true, null, item.category || category);
                });
            } else {
                html += '<li><span style="color:var(--shp-muted)">Tidak ada rincian</span></li>';
            }
            return html;
        };

        if (breakdown.length) {
            let html = '';
            html += renderFeeLine('Total Beban Seller', feeTotal, true, feePercent, 'seller');
            html += renderFeeLine('Total Voucher', voucherTotal, true, null, 'voucher');
            html += renderFeeLine('Total Beban Pembeli', buyerTotal, true, null, 'buyer');
            html += renderFeeLine('Total Beban Platform', platformTotal, true, null, 'platform');
            html += renderFeeLine('Total Penyesuaian', adjustmentTotal, true, null, 'adjustment');
            html += renderSection('seller', feeTotal, grouped.seller || []);
            html += renderSection('voucher', voucherTotal, grouped.voucher || []);
            html += renderSection('buyer', buyerTotal, grouped.buyer || []);
            html += renderSection('platform', platformTotal, grouped.platform || []);
            html += renderSection('adjustment', adjustmentTotal, grouped.adjustment || []);
            return html || '<li><span style="color:var(--shp-muted)">Tidak ada potongan</span></li>';
        }

        let html = '';
        html += renderFeeLine('Total Beban Seller', feeTotal, true, feePercent, 'seller');
        html += renderFeeLine('Total Voucher', voucherTotal, true, null, 'voucher');
        html += renderFeeLine('Total Beban Pembeli', buyerTotal, true, null, 'buyer');
        html += renderFeeLine('Total Beban Platform', platformTotal, true, null, 'platform');
        html += renderFeeLine('Total Penyesuaian', adjustmentTotal, true, null, 'adjustment');
        html += '<li class="income-sect">Komisi & Admin</li>';
        html += renderFeeLine('Biaya Administrasi', s.commission_fee);
        html += renderFeeLine('Biaya Layanan', s.service_fee);
        html += renderFeeLine('Biaya Proses Pesanan', s.seller_order_processing_fee || s.transaction_fee);
        html += renderFeeLine('Biaya Transaksi', s.seller_transaction_fee);

        html += '<li class="income-sect">Promo & Affiliate</li>';
        html += renderFeeLine('Premi', s.premi, true);
        html += renderFeeLine('Biaya Komisi AMS', s.activity_fee);
        html += renderFeeLine('Biaya Affiliate', s.biaya_affiliate ?? s.affiliate_commission_fee ?? s.seller_affiliate_fee, true);
        const affiliateCommissionValue = Number(s.affiliate_display ?? s.affiliate ?? s.affiliate_fee ?? s.affiliate_commission_fee ?? s.seller_affiliate_fee ?? s.activity_fee ?? 0);
        html += affiliateCommissionValue > 0
            ? renderFeeLine('Komisi Affiliate', affiliateCommissionValue, true)
            : '<li><span>Komisi Affiliate:</span><span class="income-fee-val" style="color:#64748b;">—</span></li>';

        html += '<li class="income-sect">Asuransi & Pajak</li>';
        html += renderFeeLine('Biaya Asuransi Pengiriman', s.shipping_insurance_fee, true);
        html += renderFeeLine('Pajak (Escrow)', s.escrow_tax);

        html += '<li class="income-sect">Biaya Iklan</li>';
        html += renderFeeLine('Biaya Iklan', s.ad_cost);
        html += '<li class="income-sect">Voucher</li>';
        html += renderFeeLine('Voucher Platform', s.voucher_platform_total);
        html += renderFeeLine('Voucher Toko', s.voucher_toko_total ?? s.seller_voucher, true, null, 'voucher');

        html += '<li class="income-sect">Penyesuaian</li>';
        html += renderFeeLine('Refund / Adjustment', s.adjustment_total ?? s.drc_adjustable_refund, true, null, 'adjustment');
        return html || '<li><span style="color:var(--shp-muted)">Tidak ada potongan</span></li>';
    }

    function renderTable() {
        const body = $('incomeBody');
        if (!rows || rows.length === 0) {
            body.innerHTML = `
                <div style="padding:2rem;text-align:center;color:#94a3b8;">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>Tidak ada data untuk filter ini.
                    <div style="margin-top:12px;"><button class="btn btn-sm btn-light border" style="border-radius:8px;font-size:.78rem;" onclick="resetFilters()"><i class="bi bi-arrow-counterclockwise"></i> Reset filter</button></div>
                </div>`;
            return;
        }

        const html = `
        <div class="table-responsive" style="margin:0; border:none;">
        <table class="table table-list w-100" style="margin-bottom:0">
            <thead>
                <tr>
                    <th style="min-width:140px">Order & Toko</th>
                    <th style="min-width:150px">Tgl Order / Cair</th>
                    <th class="text-end" style="min-width:115px">Gross</th>
                    <th class="text-end" style="min-width:115px">Buyer Paid</th>
                    <th class="text-end" style="min-width:110px">Voucher Platform</th>
                    <th class="text-end" style="min-width:110px">Voucher Toko</th>
                    <th class="text-end" style="min-width:110px">Komisi Affiliate</th>
                    <th class="text-end" style="min-width:150px">Biaya Marketplace</th>
                    <th class="text-end" style="min-width:130px">Beban Seller</th>
                    <th class="text-end" style="min-width:120px">Penyesuaian</th>
                    <th class="text-end" style="min-width:120px">Dana Cair</th>
                    <th style="min-width:230px">Rincian Potongan</th>
                </tr>
            </thead>
            <tbody>
            ${rows.map((s, idx) => {
                const gross = Number(s.gross_amount ?? s.buyer_payment_amount ?? 0);
                const buyerPaid = Number(s.buyer_paid_amount ?? s.buyer_payment_amount ?? 0);
                const voucherPlatform = Number(s.voucher_platform_total ?? 0);
                const voucherToko = Number(s.voucher_toko_total ?? s.seller_voucher ?? 0);
                const grossAfterVoucherToko = Number(s.gross_after_voucher_toko || Math.max(gross - voucherToko, 0));
                const affiliateCommission = Number(s.affiliate_display ?? s.affiliate ?? s.affiliate_fee ?? s.affiliate_commission_fee ?? s.seller_affiliate_fee ?? s.activity_fee ?? s.biaya_affiliate ?? 0);
                const affiliateCommissionPercent = Number.isFinite(Number(s.affiliate_percent))
                    ? Number(s.affiliate_percent)
                    : (grossAfterVoucherToko > 0 ? (affiliateCommission / grossAfterVoucherToko) * 100 : 0);
                const affiliateCommissionText = affiliateCommission > 0 ? `-${fmtOptionalRp(affiliateCommission)}` : '—';
                const marketplaceFeeAfterAffiliate = Number(s.marketplace_fee_after_affiliate ?? Math.max((Number(s.seller_burden_total ?? s.fee_breakdown_total ?? s.fee_total ?? 0)) - affiliateCommission, 0));
                const marketplaceFeePercent = Number.isFinite(Number(s.marketplace_fee_percent))
                    ? Number(s.marketplace_fee_percent)
                    : (grossAfterVoucherToko > 0 ? (marketplaceFeeAfterAffiliate / grossAfterVoucherToko) * 100 : 0);
                const voucherTotal = Number(s.voucher_total ?? (voucherPlatform + voucherToko));
                const grossAfterVoucher = grossAfterVoucherToko;
                const sellerBurdenTotal = Number(s.seller_burden_total ?? s.fee_breakdown_total ?? s.fee_total ?? 0);
                const buyerBurdenTotal = Number(s.buyer_burden_total ?? 0);
                const platformBurdenTotal = Number(s.platform_burden_total ?? 0);
                const adjustmentTotal = Number(s.adjustment_total ?? 0);
                const feePercent = s.fee_percent !== undefined && s.fee_percent !== null ? Number(s.fee_percent) : null;
                const net = Number(s.final_income || 0);

                return `<tr>
                    <td>
                        <a href="/marketplace/orders/${s.order?.id || ''}" class="code-link">${esc(s.channel_order_id)}</a>
                        <div style="margin-top:3px">${s.order?.order_status ? `<span class="oc-badge oc-badge-muted" style="font-size:.65rem">${esc(s.order.order_status)}</span>` : ''}</div>
                        <div style="font-size:.75rem; font-weight:600; margin-top:4px; color:var(--shp-muted);">${esc(s.store?.name || '-')}</div>
                    </td>
                    <td style="font-size:.75rem; line-height:1.35;">
                        <div><span style="color:var(--shp-muted);font-weight:700;">Dibuat:</span> <span style="font-weight:700;color:var(--shp-text);">${s.order?.ordered_at ? fmtShortDate(s.order.ordered_at) : '—'}</span></div>
                        <div style="margin-top:2px"><span style="color:var(--shp-muted);font-weight:700;">Cair:</span> <span style="font-weight:700;color:var(--shp-text);">${s.settlement_time ? fmtShortDate(s.settlement_time) : '<span class="oc-badge oc-badge-amber" style="font-size:.65rem">Belum Cair</span>'}</span></div>
                    </td>
                    <td class="text-end">
                        <div style="font-weight:900; color:var(--shp-text);">${fmtRp(gross)}</div>
                        <div style="font-size:.68rem; color:var(--shp-muted);">Setelah voucher toko: ${fmtRp(grossAfterVoucher)}</div>
                    </td>
                    <td class="text-end">
                        <div style="font-weight:900; color:#111827;">${fmtRp(buyerPaid)}</div>
                        <div style="font-size:.68rem; color:var(--shp-muted);">Total pembayaran pembeli</div>
                    </td>
                    <td class="text-end">
                        <div style="font-weight:900; color:#b91c1c;">-${fmtRp(voucherPlatform)}</div>
                        <div style="font-size:.68rem; color:var(--shp-muted);">Voucher dari platform</div>
                    </td>
                    <td class="text-end">
                        <div style="font-weight:900; color:#b91c1c;">-${fmtRp(voucherToko)}</div>
                        <div style="font-size:.68rem; color:var(--shp-muted);">Voucher toko</div>
                    </td>
                    <td class="text-end">
                        <div style="font-weight:900; color:#b91c1c;">${affiliateCommissionText}</div>
                        <div style="font-size:.68rem; color:var(--shp-muted);">${affiliateCommission > 0 ? `${fmtPct(affiliateCommissionPercent)} dari gross setelah voucher toko` : 'Tidak ada komisi affiliate'}</div>
                    </td>
                    <td class="text-end">
                        <div style="font-weight:900; color:#b91c1c;">-${fmtRp(marketplaceFeeAfterAffiliate)}</div>
                        <div style="font-size:.68rem; color:var(--shp-muted);">${fmtPct(marketplaceFeePercent)} dari gross setelah voucher toko</div>
                    </td>
                    <td class="text-end">
                        <div style="font-weight:900; color:#b91c1c;">-${fmtRp(sellerBurdenTotal)}</div>
                        <div style="font-size:.68rem; color:var(--shp-muted);">${Number.isFinite(feePercent) ? feePercent.toFixed(1) + '% dari gross setelah voucher total' : '-'}</div>
                    </td>
                    <td class="text-end">
                        <div style="font-weight:900; color:#b91c1c;">-${fmtRp(adjustmentTotal)}</div>
                        <div style="font-size:.68rem; color:var(--shp-muted);">Penyesuaian</div>
                    </td>
                    <td class="text-end">
                        <div style="font-weight:900; color:#16a34a;">${fmtRp(net)}</div>
                        ${!s.settlement_time ? '<div style="font-size:.65rem;color:#d97706;margin-top:2px;font-weight:600;">(Estimasi)</div>' : ''}
                    </td>
                    <td>
                        <button type="button" class="income-breakdown-btn" onclick="openBreakdownModal(${idx})">
                            Lihat Rincian
                        </button>
                    </td>
                </tr>`;
            }).join('')}
            </tbody>
        </table>
        </div>`;

        let pager = '';
        if (paginationData && paginationData.last_page > 1) {
            pager += '<div class="d-flex justify-content-between align-items-center gap-2 flex-wrap" style="padding: .85rem 1rem; border-top:1px solid rgba(148,163,184,.16)">';
            pager += `<div style="font-size:.72rem;color:var(--shp-muted)">Menampilkan ${paginationData.from || 0} - ${paginationData.to || 0} dari ${paginationData.total || 0}</div>`;
            pager += '<div class="btn-group">';
            pager += `<button class="btn btn-sm btn-light border" ${paginationData.current_page <= 1 ? 'disabled' : ''} onclick="goToPage(${Math.max(1, paginationData.current_page - 1)})">Prev</button>`;
            const start = Math.max(1, paginationData.current_page - 2);
            const end = Math.min(paginationData.last_page, paginationData.current_page + 2);
            for (let p = start; p <= end; p++) {
                pager += p === paginationData.current_page
                    ? `<button class="btn btn-sm btn-primary active">${p}</button>`
                    : `<button class="btn btn-sm btn-light border" onclick="goToPage(${p})">${p}</button>`;
            }
            pager += `<button class="btn btn-sm btn-light border" ${paginationData.current_page >= paginationData.last_page ? 'disabled' : ''} onclick="goToPage(${Math.min(paginationData.last_page, paginationData.current_page + 1)})">Next</button>`;
            pager += '</div></div>';
        }

        body.innerHTML = html + pager;
    }

    window.goToPage = function (page) {
        currentPage = page;
        loadIncomeDetail();
    };

    window.loadIncomeDetail = async function () {
        saveState();
        $('incomeBody').innerHTML = '<div style="padding:2rem;text-align:center;color:var(--shp-muted);font-size:.85rem;"><span class="spinner-border spinner-border-sm"></span> Memuat data...</div>';

        const params = buildParams();
        try {
            const res = await api('/api/marketplace/settlements?' + params.toString());
            if (!res || !res.paginator) {
                throw new Error('Response tidak valid.');
            }

            rows = res.paginator.data || [];
            paginationData = res.paginator;
            renderKpi(res.meta || {});
            renderTable();
        } catch (e) {
            $('incomeBody').innerHTML = '<div style="padding:2rem;text-align:center;color:#b91c1c;">Gagal memuat data: ' + esc(e.message) + '</div>';
        }
    };

    async function init() {
        stores = await api('/api/marketplace/stores').catch(() => []);
        const sel = $('filterStore');
        stores.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.name + ' (' + (s.channel?.name || '?') + ')';
            sel.appendChild(opt);
        });

        restoreState();
        await loadIncomeDetail();
    }

    init();
})();
</script>
@endpush
