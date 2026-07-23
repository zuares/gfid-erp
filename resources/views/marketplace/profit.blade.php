@extends('layouts.app')
@section('title', 'Marketplace • Profit Order')

@include('marketplace._shared')

@section('content')
<div class="page-wrap">
    
    <div class="ship-topbar">
        <div>
            <h1 class="title">Profit per Order</h1>
            <div class="sub">Harga jual dikurangi HPP, fee marketplace, voucher, dan promosi — profit bersih per order.</div>
            <div class="sub" id="lastSyncLabel" style="margin-top:4px; font-size:0.75rem; color:#64748b; font-weight: 500;">
                Terakhir Sync: <span id="lastSyncTime">—</span>
            </div>
        </div>
        <div class="controls">
            <button class="btn btn-sm btn-ship-outline btn-pill" onclick="syncFinance()">Sync Manual</button>
            <button class="btn btn-sm btn-dark btn-pill" onclick="syncHpp()" style="border-radius:999px;font-weight:600">Sync HPP</button>
            <button class="btn btn-sm btn-ship-outline btn-pill" onclick="exportCsv()">Export CSV</button>
            <button class="btn btn-sm btn-ship-outline btn-pill" onclick="resetFilters()">Reset Filter</button>
            <button class="btn btn-sm btn-ship-outline btn-pill" onclick="loadProfits()">Refresh</button>
        </div>
    </div>

    <div id="profitHppWarning" class="alert alert-warning d-none mb-3" style="border-radius:8px;font-size:.85rem">
        ⚠️ Beberapa order tidak memiliki mapping SKU → Item, sehingga HPP-nya <strong>0</strong>. Lengkapi <a href="{{ route('marketplace.sku-mapping') }}">SKU Mapping</a> dan pastikan sudah ada <em>HPP Snapshot</em> aktif.
    </div>

    <style>
        .profit-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
            align-items: center;
        }
        .profit-filters .filter-item {
            flex: 1 1 130px;
            position: relative;
            min-width: 130px;
        }
        .profit-filters .filter-item.search-item {
            flex: 2 1 200px;
        }
        .profit-filters select.form-select, 
        .profit-filters input.form-control {
            padding-left: 28px;
            font-size: 0.8rem;
            height: 32px;
            border-color: rgba(148, 163, 184, 0.3);
            border-radius: 6px;
            box-shadow: none;
        }
        .profit-filters select.form-select:focus,
        .profit-filters input.form-control:focus {
            border-color: var(--shp-primary);
        }
        .profit-filters .filter-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.8rem;
            pointer-events: none;
        }
    </style>

    <div class="card-main mb-3" style="padding:1rem;">
        <div class="profit-filters">
            
            <div class="filter-item">
                <select class="form-select filter-select w-100" style="cursor:pointer;" id="profitStoreId" onchange="loadProfits()">
                    <option value="">Toko</option>
                </select>
                <i class="bi bi-shop filter-icon"></i>
            </div>
            
            <div class="filter-item">
                <select class="form-select filter-select w-100" style="cursor:pointer;" id="filterStatus" onchange="loadProfits()">
                    <option value="">Status</option>
                    <option value="COMPLETED">Selesai</option>
                    <option value="SHIPPED">Dikirim</option>
                    <option value="CANCELLED">Batal</option>
                </select>
                <i class="bi bi-box-seam filter-icon"></i>
            </div>
            
            <div class="filter-item">
                <select class="form-select filter-select w-100" style="cursor:pointer;" id="filterSettlementStatus" onchange="loadProfits()">
                    <option value="">Dana</option>
                    <option value="cair">Cair</option>
                    <option value="belum_cair">Belum Cair</option>
                </select>
                <i class="bi bi-wallet2 filter-icon"></i>
            </div>
            
            <div class="filter-item">
                <select class="form-select filter-select w-100" style="cursor:pointer;" id="filterHppStatus" onchange="loadProfits()">
                    <option value="">HPP</option>
                    <option value="empty">Kosong</option>
                    <option value="mapped">Terisi</option>
                </select>
                <i class="bi bi-tags filter-icon"></i>
            </div>
            
            <div class="filter-item">
                <select class="form-select filter-select w-100" style="cursor:pointer;" id="filterSort" onchange="loadProfits()">
                    <option value="">Waktu Cair</option>
                    <option value="margin_asc">Margin ↑</option>
                    <option value="margin_desc" selected>Margin ↓</option>
                    <option value="profit_asc">Profit ↑</option>
                    <option value="profit_desc">Profit ↓</option>
                </select>
                <i class="bi bi-sort-down filter-icon"></i>
            </div>
            
            <div class="filter-item">
                <input type="text" class="form-control filter-select w-100" style="background:#fff;cursor:pointer;" id="filterOrderDate" placeholder="Tgl Order">
                <i class="bi bi-calendar3 filter-icon"></i>
            </div>
            
            <div class="filter-item">
                <input type="text" class="form-control filter-select w-100" style="background:#fff;cursor:pointer;" id="filterSettlementDate" placeholder="Tgl Cair">
                <i class="bi bi-calendar3 filter-icon"></i>
            </div>
            
            <div class="filter-item search-item" style="display:flex; gap:0.5rem;">
                <div style="position:relative; flex-grow:1;">
                    <input type="text" class="form-control filter-select w-100" id="filterSearch" placeholder="Cari..." onkeyup="if(event.key==='Enter')loadProfits()">
                    <i class="bi bi-search filter-icon"></i>
                </div>
                <button class="btn btn-light border" style="height:32px; padding:0 0.75rem; font-size:0.8rem; display:flex; align-items:center; gap:0.4rem; color:var(--shp-muted); border-radius:6px;" onclick="resetFilters()" title="Reset Filter">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>
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

        // 1. Prepare default dates
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        let defaultOrderDate = [firstDay, lastDay];
        let defaultSettlementDate = [firstDay, lastDay];

        // 2. Read saved state
        const savedRaw = sessionStorage.getItem('mpProfitFilters');
        let saved = null;
        if (savedRaw) {
            try { saved = JSON.parse(savedRaw); } catch(e){}
        }

        if (saved) {
            if (saved.filterOrderDate) defaultOrderDate = saved.filterOrderDate;
            if (saved.filterSettlementDate) defaultSettlementDate = saved.filterSettlementDate;
        }

        // 3. Init Flatpickr
        if (window.GFID && window.GFID.initDateRange) {
            const onDateClose = function(selectedDates) {
                if (selectedDates.length === 2 || selectedDates.length === 0) {
                    currentPage = 1; loadProfits();
                }
            };
            fpOrderDate = window.GFID.initDateRange('#filterOrderDate', { 
                onClose: onDateClose,
                defaultDate: defaultOrderDate
            });
            fpSettlementDate = window.GFID.initDateRange('#filterSettlementDate', { 
                onClose: onDateClose,
                defaultDate: defaultSettlementDate
            });
        }
        
        // 4. Restore other filters or apply defaults
        if (saved) {
            ['profitStoreId', 'filterStatus', 'filterSettlementStatus', 'filterHppStatus', 'filterSort', 'filterSearch'].forEach(id => {
                if (saved[id] !== undefined && $(id)) $(id).value = saved[id];
            });
        } else {
            ['filterStatus', 'filterSettlementStatus', 'filterHppStatus', 'filterSearch'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
            $('filterSort').value = 'margin_desc';
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

        loadProfits();
    }

    async function syncFinance() {
        if (!confirm('Jalankan proses sync data finance (order, settlement, hpp, ads)? Proses ini akan berjalan di latar belakang.')) return;
        try {
            const btn = document.querySelector('button[onclick="syncFinance()"]');
            const oldText = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Menyiapkan...';
            btn.disabled = true;

            const res = await fetch('/api/marketplace/sync-finance-all', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            const data = await res.json();
            
            alert(data.message || 'Sync finance berhasil dijadwalkan.');
            setTimeout(() => loadProfits(), 2000);
        } catch (err) {
            alert('Gagal trigger sync finance: ' + err.message);
        } finally {
            const btn = document.querySelector('button[onclick="syncFinance()"]');
            if (btn) {
                btn.innerHTML = 'Sync Manual';
                btn.disabled = false;
            }
        }
    }
    
    window.resetFilters = function() {
        sessionStorage.removeItem('mpProfitFilters');
        $('profitStoreId').value = '';
        ['filterStatus', 'filterSettlementStatus', 'filterHppStatus', 'filterSearch'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        $('filterSort').value = 'margin_desc';
        
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);

        if (fpOrderDate) fpOrderDate.setDate([firstDay, lastDay]);
        if (fpSettlementDate) fpSettlementDate.setDate([firstDay, lastDay]);
        
        currentPage = 1;
        loadProfits();
    };
    
    window.goToPage = function(page) {
        currentPage = page;
        loadProfits();
    };

    window.syncHpp = async function() {
        if (!confirm('Tarik HPP terbaru dari master produk untuk semua order yang sudah ter-mapping?')) return;
        
        // Find the button and show loading state
        const btn = document.activeElement && document.activeElement.tagName === 'BUTTON' ? document.activeElement : null;
        let oldText = '';
        if (btn) {
            oldText = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Syncing...';
            btn.disabled = true;
        }
        
        const storeId = $('profitStoreId').value;
        const payload = storeId ? { store_id: storeId } : {};
        
        try {
            const res = await api('/api/marketplace/sync-hpp', {
                method: 'POST',
                body: JSON.stringify(payload)
            });
            alert(res.message + ' (' + res.updated + ' diperbarui)');
            loadProfits();
        } catch (e) {
            alert('Gagal: ' + e.message);
        } finally {
            if (btn) {
                btn.innerHTML = oldText;
                btn.disabled = false;
            }
        }
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

        let savedOrderDate = null;
        if (fpOrderDate && fpOrderDate.selectedDates && fpOrderDate.selectedDates.length === 2) {
            savedOrderDate = fpOrderDate.selectedDates;
            params.append('order_date_from', fpOrderDate.formatDate(fpOrderDate.selectedDates[0], 'Y-m-d'));
            params.append('order_date_to', fpOrderDate.formatDate(fpOrderDate.selectedDates[1], 'Y-m-d'));
        }
        
        let savedSettlementDate = null;
        if (fpSettlementDate && fpSettlementDate.selectedDates && fpSettlementDate.selectedDates.length === 2) {
            savedSettlementDate = fpSettlementDate.selectedDates;
            params.append('settlement_date_from', fpSettlementDate.formatDate(fpSettlementDate.selectedDates[0], 'Y-m-d'));
            params.append('settlement_date_to', fpSettlementDate.formatDate(fpSettlementDate.selectedDates[1], 'Y-m-d'));
        }

        // Save state to sessionStorage
        try {
            sessionStorage.setItem('mpProfitFilters', JSON.stringify({
                profitStoreId: $('profitStoreId').value,
                filterStatus: $('filterStatus').value,
                filterSettlementStatus: $('filterSettlementStatus').value,
                filterHppStatus: $('filterHppStatus').value,
                filterSort: $('filterSort').value,
                filterSearch: $('filterSearch').value,
                filterOrderDate: savedOrderDate,
                filterSettlementDate: savedSettlementDate
            }));
        } catch(e) {}

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

    let isSyncing = false;
    let hasUnmapped = false;

    const fmtDateTime = (iso) => {
        if (!iso) return '';
        const d = new Date(iso);
        if (isNaN(d.getTime())) return iso;
        const pad = n => n.toString().padStart(2, '0');
        const day = pad(d.getDate());
        const month = d.toLocaleString('id-ID', { month: 'short' });
        const year = d.getFullYear();
        const hrs = pad(d.getHours());
        const min = pad(d.getMinutes());
        return `${day} ${month} ${year} ${hrs}:${min}`;
    };

    function renderKpi(meta) {
        if (!meta) return;
        hasUnmapped = rows.some(r => !r.hpp_mapped);

        $('kpiOmzet').textContent  = fmtRp(meta.kpi_omzet);
        $('kpiIncome').textContent = fmtRp(meta.kpi_net);
        $('kpiHpp').textContent    = fmtRp(meta.kpi_hpp);
        $('kpiProfit').textContent = fmtRp(meta.kpi_profit);
        $('kpiAvgProfit').textContent = fmtRp(meta.avg_profit);
        $('kpiMargin').textContent = meta.kpi_margin !== null ? meta.kpi_margin + '%' : '—';

        if (meta.last_sync) {
            $('lastSyncTime').textContent = fmtDateTime(meta.last_sync);
        } else {
            $('lastSyncTime').textContent = '—';
        }

        $('profitHppWarning').className = 'alert alert-warning mt-3' + (hasUnmapped ? '' : ' d-none');
    }

    function renderTable() {
        const body = $('profitBody');
        if (!rows || rows.length === 0) {
            body.innerHTML = '<div style="padding:40px; text-align:center; color:#94a3b8;"><i class="bi bi-inbox fs-1 d-block mb-2"></i>Tidak ada data margin untuk filter ini.</div>';
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
                
                let omzetGross = r.buyer_payment_amount;
                if (r.raw_json) {
                    const inc = r.raw_json.income_details || {};
                    omzetGross = inc.cost_of_goods_sold || inc.order_selling_price || r.raw_json.cost_of_goods_sold || r.raw_json.order_selling_price || r.buyer_payment_amount;
                }

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
                        <div style="color:var(--shp-text)">${r.order?.ordered_at ? fmtDateTime(r.order.ordered_at) : '<span style="color:#94a3b8">—</span>'}</div>
                        <div style="font-size:.72rem;color:var(--shp-muted);margin-top:2px;">
                            ${r.settlement_time ? fmtDateTime(r.settlement_time) : '<span class="oc-badge oc-badge-amber" style="font-size:.65rem;padding:.1rem .3rem">Belum Cair</span>'}
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
                        <div style="font-size:.75rem; color:var(--shp-muted); margin-top:2px;">
                            Pkt Diskon: <span style="font-weight:600;color:${r.seller_discount ? '#b91c1c' : '#94a3b8'}">${r.seller_discount ? '−'+fmtRp(r.seller_discount) : '0'}</span>
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
        if (!r.id) {
            alert('Order belum cair, belum ada catatan settlement untuk menyimpan biaya iklan.');
            return;
        }
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
