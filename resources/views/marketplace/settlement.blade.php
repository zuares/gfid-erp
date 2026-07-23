@extends('layouts.app')
@section('title', 'Marketplace • Settlement')

@include('marketplace._shared')

@push('head')
@endpush

@section('content')
<div class="page-wrap">
    
    <div class="ship-topbar">
        <div>
            <h1 class="title">Payment & Settlement</h1>
            <div class="sub">Dana cair per order — breakdown fee, voucher, subsidi ongkir, dan net payout dari marketplace.</div>
        </div>
        <div class="controls">
            <button class="btn btn-sm btn-ship-primary btn-pill" id="runSettlementBtn" onclick="runSettlementSync()">
                Tarik Settlement Baru
            </button>
            <button class="btn btn-sm btn-ship-outline btn-pill" onclick="resetFilters()">Reset Filter</button>
            <button class="btn btn-sm btn-ship-outline btn-pill" onclick="loadSettlements()">Refresh</button>
        </div>
    </div>

    <div id="settlementSyncAlert" class="alert d-none mb-3" style="border-radius:8px;font-size:.85rem"></div>

    <div class="card-main mb-3">
        <div style="padding:.75rem; background:rgba(148,163,184,.03);">
            <div class="controls" style="flex-wrap: nowrap; overflow-x: auto; padding-bottom: 4px; gap: .4rem;">
                
                <div style="position:relative; min-width:125px; width:125px;">
                    <select class="form-select form-select-sm filter-select w-100" style="padding-left:26px; cursor:pointer;" id="filterStore" onchange="loadSettlements()">
                        <option value="">Semua Toko</option>
                    </select>
                    <i class="bi bi-shop" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.75rem"></i>
                </div>
                
                <div style="position:relative; min-width:125px; width:125px;">
                    <select class="form-select form-select-sm filter-select w-100" style="padding-left:26px; cursor:pointer;" id="filterStatus" onchange="loadSettlements()">
                        <option value="">Semua Status</option>
                        <option value="COMPLETED">Selesai</option>
                        <option value="SHIPPED">Dikirim</option>
                        <option value="CANCELLED">Batal</option>
                    </select>
                    <i class="bi bi-box-seam" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.75rem"></i>
                </div>
                
                <div style="position:relative; min-width:125px; width:125px;">
                    <select class="form-select form-select-sm filter-select w-100" style="padding-left:26px; cursor:pointer;" id="filterSettlementStatus" onchange="loadSettlements()">
                        <option value="">Semua Dana</option>
                        <option value="cair">Sudah Cair</option>
                        <option value="belum_cair">Belum Cair</option>
                    </select>
                    <i class="bi bi-wallet2" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.75rem"></i>
                </div>
                
                <div style="position:relative; min-width:135px; width:135px;">
                    <input type="text" class="form-control form-control-sm filter-select w-100" style="background:#fff;cursor:pointer;padding-left:26px" id="filterOrderDate" placeholder="Tgl Order...">
                    <i class="bi bi-calendar3" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.75rem"></i>
                </div>
                
                <div style="position:relative; min-width:135px; width:135px;">
                    <input type="text" class="form-control form-control-sm filter-select w-100" style="background:#fff;cursor:pointer;padding-left:26px" id="filterSettlementDate" placeholder="Tgl Cair...">
                    <i class="bi bi-calendar3" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.75rem"></i>
                </div>
                
                <div style="position:relative; min-width:135px; width:135px;">
                    <input type="text" class="form-control form-control-sm filter-select w-100" style="padding-left:26px" id="filterSearch" placeholder="Cari Order..." onkeyup="if(event.key==='Enter')loadSettlements()">
                    <i class="bi bi-search" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.75rem"></i>
                </div>
                
            </div>
        </div>
    </div>
    
    <div style="display:flex; flex-wrap: nowrap; gap: .65rem; margin-bottom: 1rem;">
        <div class="oc-kpi-card" style="flex: 1 1 0; min-width: 0; margin: 0; padding: .65rem .75rem; overflow:hidden;">
            <div class="oc-kpi-label" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="Tersettlement">Tersettlement</div>
            <div class="oc-kpi-value" id="kpiCount" style="font-size: 1.05rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">—</div>
        </div>
        <div class="oc-kpi-card" style="flex: 1 1 0; min-width: 0; margin: 0; padding: .65rem .75rem; overflow:hidden;">
            <div class="oc-kpi-label" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="Gross Sales">Gross Sales</div>
            <div class="oc-kpi-value" id="kpiBuyerTotal" style="font-size: 1.05rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">—</div>
        </div>
        <div class="oc-kpi-card" style="flex: 1 1 0; min-width: 0; margin: 0; padding: .65rem .75rem; overflow:hidden;">
            <div class="oc-kpi-label" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="Total Fee Marketplace">Fee Marketplace</div>
            <div class="oc-kpi-value" id="kpiFeeTotal" style="font-size: 1.05rem; color:#b91c1c; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">—</div>
        </div>
        <div class="oc-kpi-card" style="flex: 1 1 0; min-width: 0; margin: 0; padding: .65rem .75rem; overflow:hidden;">
            <div class="oc-kpi-label" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="Total Dana Cair (Net)">Dana Cair (Net)</div>
            <div class="oc-kpi-value" id="kpiNetPayout" style="font-size: 1.05rem; color:#16a34a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">—</div>
        </div>
    </div>

    <div class="card-main">
        <div id="settlementBody">
            <div style="padding:2rem;text-align:center;color:var(--shp-muted);font-size:.85rem;">
                <span class="spinner-border spinner-border-sm"></span> Memuat data...
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const { api, fmtDate, fmtRp, esc, statusBadge } = window.mpHelpers;
    let settlements = [], stores = [];
    let fpOrderDate = null, fpSettlementDate = null;
    let currentPage = 1;
    let paginationData = null;
    const $ = id => document.getElementById(id);

    async function init() {
        stores = await api('/api/marketplace/stores').catch(() => []);
        const sel = $('filterStore');
        stores.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.name + ' (' + (s.channel?.name || '?') + ')';
            sel.appendChild(opt);
        });

        if (window.GFID && window.GFID.initDateRange) {
            const onDateClose = function(selectedDates) {
                if (selectedDates.length === 2 || selectedDates.length === 0) {
                    currentPage = 1; loadSettlements();
                }
            };
            fpOrderDate = window.GFID.initDateRange('#filterOrderDate', { onClose: onDateClose });
            fpSettlementDate = window.GFID.initDateRange('#filterSettlementDate', { onClose: onDateClose });
        }
        
        // Reset page to 1 on any select filter change
        document.querySelectorAll('.filter-select').forEach(el => {
            if (el.tagName === 'SELECT' || el.id === 'filterSearch') {
                el.addEventListener('change', () => { 
                    if(el.id !== 'filterOrderDate' && el.id !== 'filterSettlementDate') { 
                        currentPage = 1; loadSettlements(); 
                    } 
                });
            }
        });

        // Reset fields to empty just in case browser cached them on F5
        document.querySelectorAll('.filter-select').forEach(el => el.value = '');

        loadSettlements();
    }

    window.resetFilters = function() {
        document.querySelectorAll('.filter-select').forEach(el => el.value = '');
        if (fpOrderDate) fpOrderDate.clear();
        if (fpSettlementDate) fpSettlementDate.clear();
        currentPage = 1;
        loadSettlements();
    };

    window.goToPage = function(page) {
        currentPage = page;
        loadSettlements();
    };

    window.loadSettlements = async function () {
        $('settlementBody').innerHTML = '<div style="padding:2rem;text-align:center;color:var(--shp-muted);font-size:.85rem;"><span class="spinner-border spinner-border-sm"></span> Memuat data...</div>';
        
        const params = new URLSearchParams();
        if ($('filterStore').value) params.append('store_id', $('filterStore').value);
        if ($('filterStatus').value) params.append('status', $('filterStatus').value);
        if ($('filterSettlementStatus').value) params.append('settlement_status', $('filterSettlementStatus').value);
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
            const res = await api('/api/marketplace/settlements?' + params.toString());
            if (!res || !res.paginator) {
                throw new Error("Invalid response format from server.");
            }

            settlements = res.paginator.data || [];
            paginationData = res.paginator;
            
            if (res.meta) {
                $('kpiCount').textContent = res.meta.kpi_count;
                $('kpiBuyerTotal').textContent = fmtRp(res.meta.kpi_gross);
                $('kpiFeeTotal').textContent = fmtRp(res.meta.kpi_fees);
                $('kpiNetPayout').textContent = fmtRp(res.meta.kpi_net);
            }

            renderTable();
        } catch (e) {
            $('settlementBody').innerHTML = '<div style="padding:2rem;text-align:center;color:#b91c1c;">Gagal memuat data: ' + e.message + '</div>';
        }
    };

    function renderTable() {
        const body = $('settlementBody');
        if (!settlements.length) {
            body.innerHTML = '<div style="padding:3rem 1rem;text-align:center;color:var(--shp-muted);font-size:.9rem;">Tidak ada data settlement yang sesuai dengan filter.</div>';
            return;
        }

        let html = `
        <div class="table-responsive" style="margin:0; border:none; max-height:65vh; overflow-y:auto;">
        <table class="table table-list w-100" style="margin-bottom:0">
            <thead>
                <tr>
                    <th style="min-width:130px">Order & Toko</th>
                    <th style="min-width:90px">Tgl Dibuat</th>
                    <th style="min-width:90px">Tgl Cair</th>
                    <th class="text-end" style="min-width:110px">Gross & Net Payout</th>
                    <th style="min-width:145px">Biaya Marketplace</th>
                    <th style="min-width:110px">Promosi Seller</th>
                    <th style="min-width:140px">Logistik & Ongkir</th>
                    <th class="text-end" style="min-width:90px">Penyesuaian (DRC)</th>
                </tr>
            </thead>
            <tbody>
            ${settlements.map(s => {
                const totalFee = s.commission_fee + s.service_fee + s.transaction_fee + s.activity_fee + s.escrow_tax + (s.ad_cost || 0);
                
                return `<tr>
                    <td>
                        <a href="/marketplace/orders/${s.order?.id || ''}" class="code-link">${esc(s.channel_order_id)}</a>
                        <div style="margin-top:3px">${s.order?.order_status ? `<span class="oc-badge oc-badge-muted" style="font-size:.65rem">${esc(s.order.order_status)}</span>` : ''}</div>
                        <div style="font-size:.75rem; font-weight:600; margin-top:4px; color:var(--shp-muted);">${esc(s.store?.name || '—')}</div>
                    </td>
                    <td style="font-size:.78rem;">
                        ${s.order?.ordered_at ? fmtDate(s.order.ordered_at) : '<span style="color:#94a3b8">—</span>'}
                    </td>
                    
                    <td style="font-size:.75rem;color:var(--shp-muted);">
                        ${s.settlement_time ? fmtDate(s.settlement_time) : '<span class="oc-badge oc-badge-amber" style="font-size:.65rem">Belum Cair</span>'}
                    </td>

                    <td class="text-end">
                        <div class="fw-bold" style="font-size:.82rem; color:var(--shp-text);">${fmtRp(s.raw_json ? (s.raw_json.cost_of_goods_sold || s.raw_json.order_selling_price || s.buyer_payment_amount) : s.buyer_payment_amount)}</div>
                        <div class="fw-black" style="font-size:.9rem; color:#16a34a; margin-top:2px;">${fmtRp(s.final_income)}</div>
                        ${!s.settlement_time ? `<div style="font-size:.65rem; color:#d97706; margin-top:2px; font-weight:600;">(Estimasi)</div>` : ''}
                    </td>
                    
                    <td>
                        <ul class="fee-list">
                            ${(() => {
                                let html = '';
                                if (s.raw_json) {
                                    const r = s.raw_json;
                                    if (r.commission_fee) html += `<li><span>Biaya Administrasi:</span> <span class="fee-val">−${fmtRp(r.commission_fee)}</span></li>`;
                                    if (r.service_fee) html += `<li><span>Biaya Layanan:</span> <span class="fee-val">−${fmtRp(r.service_fee)}</span></li>`;
                                    if (r.seller_transaction_fee) html += `<li><span>Biaya Transaksi:</span> <span class="fee-val">−${fmtRp(r.seller_transaction_fee)}</span></li>`;
                                    if (r.seller_order_processing_fee) html += `<li><span>Biaya Proses Pesanan:</span> <span class="fee-val">−${fmtRp(r.seller_order_processing_fee)}</span></li>`;
                                    if (r.campaign_fee) html += `<li><span>Biaya Kampanye:</span> <span class="fee-val">−${fmtRp(r.campaign_fee)}</span></li>`;
                                    if (r.escrow_tax) html += `<li><span>Pajak (Escrow):</span> <span class="fee-val">−${fmtRp(r.escrow_tax)}</span></li>`;
                                } else {
                                    if (s.commission_fee) html += `<li><span>Biaya Administrasi:</span> <span class="fee-val">−${fmtRp(s.commission_fee)}</span></li>`;
                                    if (s.service_fee) html += `<li><span>Biaya Layanan:</span> <span class="fee-val">−${fmtRp(s.service_fee)}</span></li>`;
                                    if (s.transaction_fee) html += `<li><span>Biaya Transaksi:</span> <span class="fee-val">−${fmtRp(s.transaction_fee)}</span></li>`;
                                    if (s.activity_fee) html += `<li><span>Biaya Kampanye:</span> <span class="fee-val">−${fmtRp(s.activity_fee)}</span></li>`;
                                    if (s.escrow_tax) html += `<li><span>Pajak (Escrow):</span> <span class="fee-val">−${fmtRp(s.escrow_tax)}</span></li>`;
                                }
                                if (s.ad_cost) html += `<li><span>Biaya Iklan:</span> <span class="fee-val">−${fmtRp(s.ad_cost)}</span></li>`;
                                return html || '<li><span style="color:var(--shp-muted)">Tidak ada potongan</span></li>';
                            })()}
                        </ul>
                    </td>

                    <td>
                        <ul class="fee-list">
                            <li><span>Voucher:</span> <span class="fee-val">${s.seller_voucher ? '−'+fmtRp(s.seller_voucher) : '-'}</span></li>
                            <li><span>Koin CB:</span> <span class="fee-val">${s.seller_coin_cash_back ? '−'+fmtRp(s.seller_coin_cash_back) : '-'}</span></li>
                        </ul>
                    </td>

                    <td>
                        <ul class="fee-list">
                            <li><span>Ongkir Aktual:</span> <span class="fee-val">${s.actual_shipping_fee ? '−'+fmtRp(s.actual_shipping_fee) : '-'}</span></li>
                            <li><span>Ongkir Balik:</span> <span class="fee-val">${s.reverse_shipping_fee ? '−'+fmtRp(s.reverse_shipping_fee) : '-'}</span></li>
                            <li><span>Subsidi (Plaftorm):</span> <span class="subsidy-val">${s.shipping_fee_subsidy ? '+'+fmtRp(s.shipping_fee_subsidy) : '-'}</span></li>
                        </ul>
                    </td>

                    <td class="text-end">
                        <span class="drc-val" style="font-size:.82rem">${s.drc_adjustable_refund ? (s.drc_adjustable_refund > 0 ? '+' : '') + fmtRp(s.drc_adjustable_refund) : '-'}</span>
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

    window.runSettlementSync = async function () {
        const storeId = $('filterStore').value;
        if (!storeId) { alert('Pilih toko dulu sebelum sync settlement (dropdown "Semua Toko" di filter).'); return; }

        const btn     = $('runSettlementBtn');
        const alertEl = $('settlementSyncAlert');
        if (btn.disabled) return; // cegah double-klik selagi masih syncing
        btn.disabled  = true;
        btn.textContent = '⏳ Syncing…';
        alertEl.className = 'alert d-none mb-3';

        try {
            const d = await api('/api/marketplace/stores/' + storeId + '/sync-settlements', { method: 'POST' });
            alertEl.className = 'alert alert-success mb-3';
            alertEl.innerHTML = `<strong>✓ Settlement sync selesai.</strong><br>
                <small>
                    Diterima: <strong>${d.found ?? 0}</strong> &nbsp;·&nbsp;
                    Baru: <strong>${d.new ?? 0}</strong> &nbsp;·&nbsp;
                    Diperbarui: <strong>${d.updated ?? 0}</strong> &nbsp;·&nbsp;
                    Dilewati: ${d.skipped ?? 0} &nbsp;·&nbsp;
                    Gagal: ${d.errors ?? 0}
                </small>`;
            btn.textContent = '✓ Selesai';
            loadSettlements();
            setTimeout(() => { btn.disabled = false; btn.textContent = '↓ Tarik Settlement Baru'; }, 3000);
        } catch (e) {
            alertEl.className = 'alert alert-danger mb-3';

            // Token/koneksi Shopee bermasalah — tawarkan link login ulang, jangan cuma pesan generik.
            if (e.data && e.data.action && e.data.action.type === 'redirect') {
                alertEl.innerHTML = `✗ ${esc(e.data.message || e.message)} ` +
                    `<a href="${esc(e.data.action.url)}" class="alert-link">${esc(e.data.action.label)}</a>`;
            } else {
                alertEl.textContent = '✗ ' + (e.message || 'Sync settlement gagal. Coba lagi beberapa saat lagi.');
            }

            btn.disabled = false;
            btn.textContent = '↓ Tarik Settlement Baru';
        }
    };

    init();
})();
</script>
@endpush
