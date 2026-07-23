@extends('layouts.app')
@section('title', 'Marketplace • Profit Order')

@include('marketplace._shared')

@section('content')
<div class="page-wrap">
    
    <div class="ship-topbar">
        <div>
            <h1 class="title">Profit per Order</h1>
            <div class="sub">Harga jual dikurangi HPP, fee marketplace, voucher, dan promosi — profit bersih per order.</div>
        </div>
        <div class="controls">
            <button class="btn btn-sm btn-ship-outline btn-pill" onclick="exportCsv()">Export CSV</button>
            <button class="btn btn-sm btn-ship-outline btn-pill" onclick="resetFilters()">Reset Filter</button>
            <button class="btn btn-sm btn-ship-outline btn-pill" onclick="loadProfits()">Refresh</button>
        </div>
    </div>

    <div id="profitHppWarning" class="alert alert-warning d-none mb-3" style="border-radius:8px;font-size:.85rem">
        ⚠️ Beberapa order tidak memiliki mapping SKU → Item, sehingga HPP-nya <strong>0</strong>. Lengkapi <a href="{{ route('marketplace.sku-mapping') }}">SKU Mapping</a> dan pastikan sudah ada <em>HPP Snapshot</em> aktif.
    </div>

    <div class="card-main mb-3">
        <div style="padding:.75rem; background:rgba(148,163,184,.03);">
            <div class="controls" style="flex-wrap: nowrap; overflow-x: auto; padding-bottom: 4px; gap: .4rem;">
                
                <div style="position:relative; min-width:125px; width:125px;">
                    <select class="form-select form-select-sm filter-select w-100" style="padding-left:26px; cursor:pointer;" id="profitStoreId" onchange="loadProfits()">
                        <option value="">Semua Toko</option>
                    </select>
                    <i class="bi bi-shop" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.75rem"></i>
                </div>
                
                <div style="position:relative; min-width:125px; width:125px;">
                    <select class="form-select form-select-sm filter-select w-100" style="padding-left:26px; cursor:pointer;" id="filterStatus" onchange="loadProfits()">
                        <option value="">Semua Status</option>
                        <option value="COMPLETED">Selesai</option>
                        <option value="SHIPPED">Dikirim</option>
                        <option value="CANCELLED">Batal</option>
                    </select>
                    <i class="bi bi-box-seam" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.75rem"></i>
                </div>
                
                <div style="position:relative; min-width:125px; width:125px;">
                    <select class="form-select form-select-sm filter-select w-100" style="padding-left:26px; cursor:pointer;" id="filterSettlementStatus" onchange="loadProfits()">
                        <option value="">Semua Dana</option>
                        <option value="cair">Sudah Cair</option>
                        <option value="belum_cair">Belum Cair</option>
                    </select>
                    <i class="bi bi-wallet2" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.75rem"></i>
                </div>
                
                <div style="position:relative; min-width:125px; width:125px;">
                    <select class="form-select form-select-sm filter-select w-100" style="padding-left:26px; cursor:pointer;" id="filterHppStatus" onchange="loadProfits()">
                        <option value="">Semua HPP</option>
                        <option value="empty">HPP Kosong</option>
                        <option value="mapped">HPP Terisi</option>
                    </select>
                    <i class="bi bi-tags" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.75rem"></i>
                </div>
                
                <div style="position:relative; min-width:125px; width:125px;">
                    <select class="form-select form-select-sm filter-select w-100" style="padding-left:26px; cursor:pointer;" id="filterSort" onchange="loadProfits()">
                        <option value="">Waktu Cair</option>
                        <option value="margin_asc">Margin ↑</option>
                        <option value="margin_desc">Margin ↓</option>
                        <option value="profit_asc">Profit ↑</option>
                        <option value="profit_desc">Profit ↓</option>
                    </select>
                    <i class="bi bi-sort-down" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.75rem"></i>
                </div>
                
                <div style="position:relative; min-width:135px; width:135px;">
                    <input type="text" class="form-control form-control-sm filter-select w-100" style="background:#fff;cursor:pointer;padding-left:26px" id="filterOrderDate" placeholder="Tgl Order...">
                    <i class="bi bi-calendar3" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.75rem"></i>
                </div>
                
                <div style="position:relative; min-width:135px; width:135px;">
                    <input type="text" class="form-control form-control-sm filter-select w-100" style="background:#fff;cursor:pointer;padding-left:26px" id="filterSettlementDate" placeholder="Tgl Cair...">
                    <i class="bi bi-calendar3" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.75rem"></i>
                </div>
                
                <div style="position:relative; min-width:145px; width:145px;">
                    <input type="text" class="form-control form-control-sm filter-select w-100" style="padding-left:26px" id="filterSearch" placeholder="Cari Order..." onkeyup="if(event.key==='Enter')loadProfits()">
                    <i class="bi bi-search" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.75rem"></i>
                </div>
                
            </div>
        </div>
    </div>
    
    <div style="display:flex; flex-wrap: nowrap; gap: .65rem; margin-bottom: 1rem;">
        <div class="oc-kpi-card" style="flex: 1 1 0; min-width: 0; margin: 0; padding: .65rem .75rem; overflow:hidden;">
            <div class="oc-kpi-label" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="Harga Jual">Harga Jual</div>
            <div class="oc-kpi-value" id="kpiOmzet" style="font-size: 1.05rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">—</div>
        </div>
        <div class="oc-kpi-card" style="flex: 1 1 0; min-width: 0; margin: 0; padding: .65rem .75rem; overflow:hidden;">
            <div class="oc-kpi-label" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="Total HPP">Total HPP</div>
            <div class="oc-kpi-value" id="kpiHpp" style="font-size: 1.05rem; color:#b45309; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">—</div>
        </div>
        <div class="oc-kpi-card" style="flex: 1 1 0; min-width: 0; margin: 0; padding: .65rem .75rem; overflow:hidden;">
            <div class="oc-kpi-label" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="Dana Cair">Dana Cair</div>
            <div class="oc-kpi-value" id="kpiIncome" style="font-size: 1.05rem; color:#0369a1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">—</div>
        </div>
        <div class="oc-kpi-card" style="flex: 1 1 0; min-width: 0; margin: 0; padding: .65rem .75rem; overflow:hidden;">
            <div class="oc-kpi-label" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="Profit Bersih">Profit Bersih</div>
            <div class="oc-kpi-value" id="kpiProfit" style="font-size: 1.05rem; color:#16a34a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">—</div>
        </div>
        <div class="oc-kpi-card" style="flex: 1 1 0; min-width: 0; margin: 0; padding: .65rem .75rem; overflow:hidden;">
            <div class="oc-kpi-label" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="Rata-Rata Profit / Order">Avg Profit/Order</div>
            <div class="oc-kpi-value" id="kpiAvgProfit" style="font-size: 1.05rem; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">—</div>
        </div>
        <div class="oc-kpi-card" style="flex: 1 1 0; min-width: 0; margin: 0; padding: .65rem .75rem; overflow:hidden;">
            <div class="oc-kpi-label" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="Margin">Margin</div>
            <div class="oc-kpi-value" id="kpiMargin" style="font-size: 1.05rem; color:#16a34a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">—</div>
        </div>
    </div>

    <div class="card-main">
        <div id="profitBody">
            <div style="padding:2rem;text-align:center;color:var(--shp-muted);font-size:.85rem;">
                <span class="spinner-border spinner-border-sm"></span> Memuat data...
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .table-list thead th{
        position: sticky;
        top: 0;
        z-index: 10;
        border-bottom-width:1px;
        font-size:.68rem;
        text-transform:none;
        color:#64748b;
        background: var(--card,#fff);
        padding:.45rem .62rem;
        box-shadow: 0 1px 0 rgba(148,163,184,.18);
    }
    body[data-theme="dark"] .table-list thead th{
        background: rgba(15, 23, 42, 0.98);
        color:#9ca3af;
        border-bottom-color: rgba(30, 64, 175, 0.6);
    }
    .table-list tbody td{
        vertical-align:top;
        border-top-color: rgba(148, 163, 184, 0.16);
        padding:.4rem .45rem;
    }
    body[data-theme="dark"] .table-list tbody td{ border-top-color: rgba(51, 65, 85, 0.85); }
    .code-link{ font-weight:700; text-decoration:none; color:inherit; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const { api, fmt, fmtDate, fmtRp, esc, channelPill, statusBadge } = window.mpHelpers;
    let rows = [], stores = [];
    let fpOrderDate = null, fpSettlementDate = null;
    let currentPage = 1;
    let paginationData = null;
    const $ = id => document.getElementById(id);

    // ── Init ──────────────────────────────────────────────────────────────────
    async function init() {
        stores = await api('/api/marketplace/stores').catch(() => []);
        const sel = $('profitStoreId');
        stores.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.name + ' (' + (s.channel?.name || '?') + ')';
            sel.appendChild(opt);
        });

        if (window.GFID && window.GFID.initDateRange) {
            const onDateClose = function(selectedDates) {
                if (selectedDates.length === 2 || selectedDates.length === 0) {
                    currentPage = 1; loadProfits();
                }
            };
            fpOrderDate = window.GFID.initDateRange('#filterOrderDate', { onClose: onDateClose });
            fpSettlementDate = window.GFID.initDateRange('#filterSettlementDate', { onClose: onDateClose });
        }
        
        document.querySelectorAll('.filter-select').forEach(el => {
            if (el.tagName === 'SELECT' || el.id === 'filterSearch') {
                el.addEventListener('change', () => { 
                    if(el.id !== 'filterOrderDate' && el.id !== 'filterSettlementDate') { 
                        currentPage = 1; loadProfits(); 
                    } 
                });
            }
        });
        // Reset fields to empty just in case browser cached them on F5
        document.querySelectorAll('.filter-select').forEach(el => el.value = '');

        loadProfits();
    }
    
    window.resetFilters = function() {
        document.querySelectorAll('.filter-select').forEach(el => el.value = '');
        if (fpOrderDate) fpOrderDate.clear();
        if (fpSettlementDate) fpSettlementDate.clear();
        currentPage = 1;
        loadProfits();
    };
    
    window.goToPage = function(page) {
        currentPage = page;
        loadProfits();
    };

    // ── Load ──────────────────────────────────────────────────────────────────
    window.loadProfits = async function () {
        $('profitBody').innerHTML = '<div style="padding:2rem;text-align:center;color:var(--shp-muted);font-size:.85rem;"><span class="spinner-border spinner-border-sm"></span> Memuat data...</div>';
        
        const params = new URLSearchParams();
        if ($('profitStoreId').value) params.append('store_id', $('profitStoreId').value);
        if ($('filterStatus').value) params.append('status', $('filterStatus').value);
        if ($('filterSettlementStatus').value) params.append('settlement_status', $('filterSettlementStatus').value);
        if ($('filterHppStatus').value) params.append('hpp_status', $('filterHppStatus').value);
        if ($('filterSort').value) params.append('sort', $('filterSort').value);
        if ($('filterSearch').value) params.append('search', $('filterSearch').value);
        params.append('page', currentPage);
        params.append('per_page', 50);

        if (fpOrderDate && fpOrderDate.selectedDates && fpOrderDate.selectedDates.length === 2) {
            params.append('order_date_from', fpOrderDate.formatDate(fpOrderDate.selectedDates[0], 'Y-m-d'));
            params.append('order_date_to', fpOrderDate.formatDate(fpOrderDate.selectedDates[1], 'Y-m-d'));
        }
        
        if (fpSettlementDate && fpSettlementDate.selectedDates && fpSettlementDate.selectedDates.length === 2) {
            params.append('settlement_date_from', fpSettlementDate.formatDate(fpSettlementDate.selectedDates[0], 'Y-m-d'));
            params.append('settlement_date_to', fpSettlementDate.formatDate(fpSettlementDate.selectedDates[1], 'Y-m-d'));
        }

        try {
            const res = await api('/api/marketplace/order-profits?' + params.toString());
            if (!res || !res.paginator) {
                throw new Error("Invalid response format from server.");
            }
            
            rows = res.paginator.data || [];
            paginationData = res.paginator;
            
            renderKpi(res.meta);
            renderTable();
        } catch (e) {
            $('profitBody').innerHTML = '<div style="padding:2rem;text-align:center;color:#b91c1c;">Gagal memuat data: ' + e.message + '</div>';
        }
    };
    
    window.exportCsv = function() {
        const params = new URLSearchParams();
        if ($('profitStoreId').value) params.append('store_id', $('profitStoreId').value);
        if ($('filterStatus').value) params.append('status', $('filterStatus').value);
        if ($('filterSettlementStatus').value) params.append('settlement_status', $('filterSettlementStatus').value);
        if ($('filterHppStatus').value) params.append('hpp_status', $('filterHppStatus').value);
        if ($('filterSort').value) params.append('sort', $('filterSort').value);
        if ($('filterSearch').value) params.append('search', $('filterSearch').value);
        if (fpOrderDate && fpOrderDate.selectedDates && fpOrderDate.selectedDates.length === 2) {
            params.append('order_date_from', fpOrderDate.formatDate(fpOrderDate.selectedDates[0], 'Y-m-d'));
            params.append('order_date_to', fpOrderDate.formatDate(fpOrderDate.selectedDates[1], 'Y-m-d'));
        }
        if (fpSettlementDate && fpSettlementDate.selectedDates && fpSettlementDate.selectedDates.length === 2) {
            params.append('settlement_date_from', fpSettlementDate.formatDate(fpSettlementDate.selectedDates[0], 'Y-m-d'));
            params.append('settlement_date_to', fpSettlementDate.formatDate(fpSettlementDate.selectedDates[1], 'Y-m-d'));
        }
        params.append('export', 'csv');
        
        window.location.href = '/api/marketplace/order-profits?' + params.toString();
    };

    function renderKpi(meta) {
        if (!meta) return;
        const hasUnmapped = rows.some(r => !r.hpp_mapped);

        $('kpiOmzet').textContent  = fmtRp(meta.kpi_omzet);
        $('kpiIncome').textContent = fmtRp(meta.kpi_net);
        $('kpiHpp').textContent    = fmtRp(meta.kpi_hpp);
        $('kpiProfit').textContent = fmtRp(meta.kpi_profit);
        $('kpiAvgProfit').textContent = fmtRp(meta.avg_profit);
        $('kpiMargin').textContent = meta.kpi_margin !== null ? meta.kpi_margin + '%' : '—';

        $('profitHppWarning').className = 'alert alert-warning mt-3' + (hasUnmapped ? '' : ' d-none');
    }

    function renderTable() {
        const body = $('profitBody');
        if (!rows.length) {
            body.innerHTML = '<div class="oc-empty">Belum ada data profit. Pastikan sudah ada settlement dan HPP aktif.</div>';
            return;
        }

        let html = `
        <div class="table-responsive" style="margin:0; border:none; max-height:65vh; overflow-y:auto;">
        <table class="table table-list w-100" style="margin-bottom:0">
            <thead>
                <tr>
                    <th style="min-width:130px">Order & Toko</th>
                    <th style="min-width:100px">Waktu</th>
                    <th class="text-end" style="min-width:90px">Harga Jual</th>
                    <th class="text-end" style="min-width:110px">Promosi Seller</th>
                    <th class="text-end" style="min-width:100px">Dana Cair</th>
                    <th class="text-end" title="HPP dari snapshot aktif">HPP</th>
                    <th class="text-end" style="color:#16a34a;font-weight:900">Profit</th>
                    <th class="text-end">Margin</th>
                </tr>
            </thead>
            <tbody>
            ${rows.map((r, idx) => {
                let marginColor = '#b91c1c'; // Red
                if (r.margin_pct >= 15) marginColor = '#16a34a'; // Green
                else if (r.margin_pct >= 0) marginColor = '#d97706'; // Amber/Orange
                
                const profitColor = r.profit_net >= 0 ? '#16a34a' : '#b91c1c';
                const hppLabel = r.hpp_mapped
                    ? fmtRp(r.hpp_total)
                    : `<span style="color:#b91c1c" title="SKU belum ter-mapping">${fmtRp(r.hpp_total)} ⚠</span>`;
                
                const omzetGross = r.raw_json ? (r.raw_json.cost_of_goods_sold || r.raw_json.order_selling_price || r.buyer_payment_amount) : r.buyer_payment_amount;

                return `<tr>
                    <td>
                        <a href="/marketplace/orders/${r.order?.id || ''}" class="code-link">${esc(r.channel_order_id)}</a>
                        <div style="margin-top:3px">${r.order?.order_status ? `<span class="oc-badge oc-badge-muted" style="font-size:.65rem">${esc(r.order.order_status)}</span>` : ''}</div>
                        <div style="font-size:.75rem; font-weight:600; margin-top:4px; color:var(--shp-muted);">${esc(r.store?.name || '—')}</div>
                        ${r.items && r.items.length ? `
                        <div style="margin-top:6px; border-top:1px dashed var(--shp-border); padding-top:4px;">
                            ${r.items.map(i => `
                                <div style="display:flex; align-items:center; gap:4px; font-size:.68rem; margin-bottom:2px;" title="${esc(i.sku)} (Qty: ${i.qty})">
                                    ${i.mapped ? '<span style="color:#16a34a">✓</span>' : '<span style="color:#b91c1c;font-weight:bold" title="Belum di-mapping atau HPP kosong">!</span>'}
                                    <span style="color:var(--shp-text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:110px; flex:1;">${esc(i.sku)}</span>
                                    <span style="color:#94a3b8">x${i.qty}</span>
                                </div>
                            `).join('')}
                        </div>
                        ` : ''}
                    </td>
                    
                    <td style="font-size:.78rem;">
                        <div style="color:var(--shp-text)">${r.order?.ordered_at ? fmtDate(r.order.ordered_at) : '<span style="color:#94a3b8">—</span>'}</div>
                        <div style="font-size:.72rem;color:var(--shp-muted);margin-top:2px;">
                            ${r.settlement_time ? fmtDate(r.settlement_time) : '<span class="oc-badge oc-badge-amber" style="font-size:.65rem;padding:.1rem .3rem">Belum Cair</span>'}
                        </div>
                    </td>
                    
                    <td class="text-end">
                        <div class="fw-bold" style="font-size:.85rem; color:var(--shp-text);">${fmtRp(omzetGross)}</div>
                    </td>

                    <td class="text-end">
                        <div style="font-size:.75rem; color:var(--shp-muted);">
                            Vcr: <span style="font-weight:600;color:${r.seller_voucher ? '#b91c1c' : '#94a3b8'}">${r.seller_voucher ? '−'+fmtRp(r.seller_voucher) : '0'}</span>
                        </div>
                        <div style="font-size:.75rem; color:var(--shp-muted); margin-top:2px;">
                            Koin: <span style="font-weight:600;color:${r.seller_coin_cash_back ? '#b91c1c' : '#94a3b8'}">${r.seller_coin_cash_back ? '−'+fmtRp(r.seller_coin_cash_back) : '0'}</span>
                        </div>
                    </td>

                    <td class="text-end">
                        <div class="fw-black" style="font-size:.9rem; color:#0369a1;">${fmtRp(r.final_income)}</div>
                        ${!r.settlement_time ? `<div style="font-size:.65rem; color:#d97706; margin-top:2px; font-weight:600;">(Estimasi)</div>` : ''}
                    </td>

                    <td class="text-end" style="font-size:.78rem;color:#b45309">${hppLabel}
                        ${r.hpp_total > 0 ? `<div style="font-size:.66rem;color:#94a3b8">HPP unit tersimpan</div>` : ''}
                    </td>

                    <td class="text-end fw-black" style="font-size:.88rem;color:${profitColor}">${fmtRp(r.profit_net)}</td>
                    
                    <td class="text-end" style="font-size:.82rem;font-weight:700;color:${marginColor}">
                        ${r.margin_pct !== null ? r.margin_pct + '%' : '—'}
                    </td>
                </tr>`;
            }).join('')}
            </tbody>
        </table>
        </div>`;

        // Pagination UI
        let linksHtml = '';
        if (paginationData && paginationData.last_page > 1) {
            linksHtml += '<div class="btn-group">';
            if (paginationData.current_page > 1) {
                linksHtml += `<button class="btn btn-sm btn-light border" onclick="goToPage(${paginationData.current_page - 1})">Prev</button>`;
            } else {
                linksHtml += `<button class="btn btn-sm btn-light border" disabled>Prev</button>`;
            }

            // Simple pagination (show +/- 2 pages)
            let start = Math.max(1, paginationData.current_page - 2);
            let end = Math.min(paginationData.last_page, paginationData.current_page + 2);
            
            for(let p = start; p <= end; p++) {
                if (p === paginationData.current_page) {
                    linksHtml += `<button class="btn btn-sm btn-primary active">${p}</button>`;
                } else {
                    linksHtml += `<button class="btn btn-sm btn-light border" onclick="goToPage(${p})">${p}</button>`;
                }
            }

            if (paginationData.current_page < paginationData.last_page) {
                linksHtml += `<button class="btn btn-sm btn-light border" onclick="goToPage(${paginationData.current_page + 1})">Next</button>`;
            } else {
                linksHtml += `<button class="btn btn-sm btn-light border" disabled>Next</button>`;
            }
            linksHtml += '</div>';
        }

        html += `
        <div style="padding:.5rem .75rem; border-top:1px solid var(--shp-border); display:flex; justify-content:space-between; align-items:center; font-size:.75rem; color:var(--shp-muted);">
            <div>Menampilkan baris ${paginationData.from || 0} - ${paginationData.to || 0} dari total ${paginationData.total || 0}</div>
            <div>${linksHtml}</div>
        </div>`;

        body.innerHTML = html;
    }

    // ── Edit Ad Cost (inline) ─────────────────────────────────────────────────
    window.editAdCost = function (idx) {
        const r   = rows[idx];
        const cur = r.ad_cost || 0;
        const val = prompt(`Biaya iklan untuk order ${r.channel_order_id}:`, cur);
        if (val === null) return;
        const num = parseFloat(val);
        if (isNaN(num) || num < 0) { alert('Masukkan angka ≥ 0'); return; }

        api('/api/marketplace/settlements/' + r.id + '/ad-cost', {
            method: 'PATCH',
            body: JSON.stringify({ ad_cost: num }),
        }).then(res => {
            rows[idx].ad_cost    = res.ad_cost;
            rows[idx].profit_net = rows[idx].final_income - rows[idx].hpp_total - res.ad_cost;
            rows[idx].margin_pct = rows[idx].buyer_payment_amount > 0
                ? Math.round(rows[idx].profit_net / rows[idx].buyer_payment_amount * 1000) / 10
                : null;
            renderKpi();
            renderTable();
        }).catch(e => alert('Gagal simpan: ' + e.message));
    };

    init();
})();
</script>
@endpush
