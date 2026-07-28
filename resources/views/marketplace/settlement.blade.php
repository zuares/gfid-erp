@extends('layouts.app')
@section('title', 'Marketplace • Settlement')

@include('marketplace._shared')

@push('head')
    <style>
        .settlement-tabs{
            display:inline-flex;
            gap:.25rem;
            padding:.3rem;
            border-radius:14px;
            background:rgba(148,163,184,.08);
            margin-bottom:.9rem;
            overflow-x:auto;
            max-width:100%;
            scrollbar-width:none;
        }
        .settlement-tabs::-webkit-scrollbar{ display:none; }
        body[data-theme="dark"] .settlement-tabs{ background:rgba(30,41,59,.5); }
        .settlement-tab-btn{
            border:none;
            background:transparent;
            color:#64748b;
            border-radius:10px;
            padding:.5rem .9rem;
            font-size:.8rem;
            font-weight:800;
            cursor:pointer;
            white-space:nowrap;
            transition:all .15s ease;
        }
        .settlement-tab-btn:hover{ color:var(--shp-text); }
        .settlement-tab-btn.active{
            background:var(--card,#fff);
            color:var(--shp-text);
            box-shadow:0 1px 3px rgba(0,0,0,.05), 0 1px 2px rgba(0,0,0,.1);
        }
        body[data-theme="dark"] .settlement-tab-btn{ color:#94a3b8; }
        body[data-theme="dark"] .settlement-tab-btn.active{
            background:var(--card,#1e293b);
            color:var(--text,#e2e8f0);
        }
        .settlement-sync-shell{
            padding:1rem;
        }
        .settlement-sync-header{
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            gap:.85rem;
            flex-wrap:wrap;
        }
        .settlement-sync-eyebrow{
            display:inline-flex;
            align-items:center;
            gap:.35rem;
            font-size:.68rem;
            font-weight:900;
            letter-spacing:.04em;
            text-transform:uppercase;
            color:#64748b;
        }
        .settlement-sync-title{
            font-size:1.02rem;
            font-weight:800;
            color:var(--shp-text);
            line-height:1.2;
            margin-top:.1rem;
        }
        .settlement-sync-actions-row{
            display:flex;
            gap:.5rem;
            align-items:center;
            flex-wrap:wrap;
            justify-content:flex-end;
        }
        .settlement-sync-feedback{
            display:flex;
            align-items:flex-start;
            gap:.65rem;
            border-radius:12px;
            border:1px solid rgba(148,163,184,.22);
            background:rgba(248,250,252,.9);
            padding:.75rem .85rem;
            margin-top:1rem;
            font-size:.82rem;
        }
        .settlement-sync-feedback strong{ display:block; font-size:.85rem; line-height:1.2; }
        .settlement-sync-feedback .settlement-feedback-meta{
            display:flex;
            flex-wrap:wrap;
            gap:.35rem;
            margin-top:.45rem;
        }
        .settlement-sync-feedback .settlement-feedback-meta span{
            display:inline-flex;
            align-items:center;
            gap:.25rem;
            padding:.16rem .5rem;
            border-radius:999px;
            font-size:.68rem;
            font-weight:800;
            background:rgba(148,163,184,.12);
            color:#475569;
        }
        .settlement-sync-feedback.success{
            border-color:rgba(34,197,94,.25);
            background:rgba(240,253,244,.92);
        }
        .settlement-sync-feedback.warn{
            border-color:rgba(245,158,11,.25);
            background:rgba(255,251,235,.92);
        }
        .settlement-sync-feedback.error{
            border-color:rgba(239,68,68,.22);
            background:rgba(254,242,242,.95);
        }
        .settlement-sync-feedback.info{
            border-color:rgba(59,130,246,.22);
            background:rgba(239,246,255,.95);
        }
        .settlement-sync-status-grid{
            display:grid;
            grid-template-columns:repeat(3,minmax(0,1fr));
            gap:.5rem;
            margin-top:.9rem;
        }
        .settlement-sync-chip{
            display:inline-flex;
            align-items:center;
            gap:.3rem;
            padding:.22rem .55rem;
            border-radius:999px;
            font-size:.68rem;
            font-weight:800;
            background:rgba(148,163,184,.12);
            color:#475569;
            min-width:0;
        }
        .settlement-sync-chip.success{ background:rgba(34,197,94,.14); color:#166534; }
        .settlement-sync-chip.warn{ background:rgba(245,158,11,.16); color:#b45309; }
        .settlement-sync-chip.error{ background:rgba(239,68,68,.14); color:#b91c1c; }
        .settlement-sync-chip.running{ background:rgba(59,130,246,.12); color:#1d4ed8; }
        .settlement-sync-action-strip{
            margin-top:1rem;
            padding:1rem;
            border:1px solid var(--shp-border);
            border-radius:12px;
            background:rgba(148,163,184,.03);
        }
        .settlement-sync-field{
            flex:1 1 200px;
            min-width:200px;
            position:relative;
        }
        .settlement-sync-field label{
            display:block;
            margin-bottom:.28rem;
            font-size:.68rem;
            font-weight:900;
            color:#64748b;
            text-transform:uppercase;
            letter-spacing:.04em;
        }
        .settlement-sync-details{
            margin-top:.9rem;
            border:1px solid var(--shp-border);
            border-radius:12px;
            overflow:hidden;
            background:var(--card);
        }
        .settlement-sync-details > summary{
            list-style:none;
            cursor:pointer;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:.5rem;
            padding:.85rem 1rem;
            font-size:.82rem;
            font-weight:800;
            color:var(--shp-text);
            border-bottom:1px solid transparent;
        }
        .settlement-sync-details > summary::-webkit-details-marker{ display:none; }
        .settlement-sync-details[open] > summary{ border-bottom-color:var(--shp-border); }
        .settlement-sync-details-body{
            padding:1rem;
            display:grid;
            gap:.9rem;
        }
        .settlement-sync-console-wrap{
            display:grid;
            gap:.35rem;
        }
        .settlement-sync-console{
            background:rgba(2,6,23,.92);
            border:1px solid var(--shp-border);
            border-radius:10px;
            padding:10px 12px;
            font-family:ui-monospace,monospace;
            font-size:.7rem;
            color:#a1a1aa;
            height:118px;
            overflow-y:auto;
            line-height:1.45;
            white-space:pre-wrap;
        }
        .settlement-sync-advanced{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:.5rem;
            flex-wrap:wrap;
            padding:.8rem 1rem;
            border:1px dashed var(--shp-border-strong);
            border-radius:12px;
            background:rgba(148,163,184,.03);
        }
        body[data-theme="dark"] .settlement-sync-feedback,
        body[data-theme="dark"] .settlement-sync-action-strip,
        body[data-theme="dark"] .settlement-sync-details{
            background:rgba(15,23,42,.92);
        }
        body[data-theme="dark"] .settlement-sync-advanced{
            background:rgba(15,23,42,.78);
        }
        .settlement-sync-log-ok{ color:#4ade80; }
        .settlement-sync-log-warn{ color:#f59e0b; }
        .settlement-sync-log-error{ color:#f87171; }
        .settlement-sync-log-muted{ color:#94a3b8; }

        @media (max-width: 768px) {
            .settlement-sync-status-grid{ grid-template-columns:1fr; }
            .settlement-sync-header{ align-items:stretch; }
            .settlement-sync-actions-row{ justify-content:flex-start; }
            .settlement-sync-field,
            .settlement-sync-field[style]{ max-width:none !important; min-width:0 !important; flex:1 1 100%; }
            .settlement-sync-action-strip{ padding:.85rem; }
        }
    </style>
@endpush

@section('content')
<div class="page-wrap">
    <div class="ship-topbar">
        <div>
            <h1 class="title">Payment & Settlement</h1>
            <div class="sub">Dana cair per order — breakdown fee, voucher, subsidi ongkir, dan net payout dari marketplace.</div>
        </div>
        <div class="controls">
            <button class="btn btn-sm btn-ship-outline btn-pill" onclick="switchSettlementTab('sync')" title="Buka tab sync settlement">
                <i class="bi bi-arrow-repeat"></i> Tab Sync
            </button>
            <button class="btn btn-sm btn-ship-outline btn-pill" onclick="resetFilters()">Reset Filter</button>
            <button class="btn btn-sm btn-ship-outline btn-pill" onclick="loadSettlements()">Refresh</button>
        </div>
    </div>

    <div class="settlement-tabs" role="tablist" aria-label="Tabs settlement">
        <button type="button" id="settlementTabDataBtn" class="settlement-tab-btn active" onclick="switchSettlementTab('data')">
            <i class="bi bi-receipt"></i> Settlement
        </button>
        <button type="button" id="settlementTabSyncBtn" class="settlement-tab-btn" onclick="switchSettlementTab('sync')">
            <i class="bi bi-arrow-repeat"></i> Sync
            <span id="settlementSyncDot" style="display:none; width:8px; height:8px; border-radius:50%; background:#3b82f6; margin-left:2px;"></span>
        </button>
    </div>

    <div id="settlementTabData">
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

    <div id="settlementTabSync" style="display:none;">
        <div class="card-main settlement-sync-shell mb-3">
            <div class="settlement-sync-header">
                <div>
                    <div class="settlement-sync-eyebrow"><i class="bi bi-arrow-repeat"></i> Settlement Sync</div>
                </div>
                <div class="settlement-sync-actions-row">
                    <button class="btn btn-sm btn-ship-outline btn-pill" onclick="switchSettlementTab('data')">
                        <i class="bi bi-receipt"></i> Lihat Data
                    </button>
                    <button class="btn btn-sm btn-ship-outline btn-pill" onclick="refreshSettlementSyncLogs()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                </div>
            </div>

            <div id="settlementSyncFeedback" class="settlement-sync-feedback d-none"></div>

            <div class="settlement-sync-status-grid">
                <span id="settlementSyncStateChip" class="settlement-sync-chip">
                    <i class="bi bi-shield-check" style="color:#64748b;"></i>
                    <strong id="settlementSyncStateValue" style="color:#15803d;">Siap</strong>
                </span>
                <span class="settlement-sync-chip">
                    <i class="bi bi-check2-circle" style="color:#15803d;"></i>
                    Sukses terakhir: <strong id="settlementSyncLastSuccess" style="color:var(--shp-text);">—</strong>
                </span>
                <span class="settlement-sync-chip">
                    <i class="bi bi-broadcast" style="color:#64748b;"></i>
                    Last check: <strong id="settlementSyncLastCheck" style="color:var(--shp-text);">—</strong>
                </span>
            </div>

            <div class="settlement-sync-action-strip">
                <div style="display:flex; flex-wrap:wrap; gap:.75rem; align-items:flex-end;">
                    <div class="settlement-sync-field">
                        <label for="syncStore">Toko</label>
                        <select class="form-select form-select-sm w-100" style="padding-left:26px; cursor:pointer;" id="syncStore">
                            <option value="">Pilih Toko</option>
                        </select>
                        <i class="bi bi-shop" style="position:absolute;left:8px;top:50%;transform:translateY(8px);color:#94a3b8;font-size:.75rem"></i>
                    </div>

                    <div class="settlement-sync-field" style="max-width:170px; min-width:170px;">
                        <label for="syncBackfillMonths">Backfill</label>
                        <select class="form-select form-select-sm w-100" id="syncBackfillMonths">
                            <option value="1">1 bulan</option>
                            <option value="2">2 bulan</option>
                            <option value="3" selected>3 bulan</option>
                        </select>
                    </div>

                    <div class="settlement-sync-actions-row" style="margin-left:auto;">
                        <button class="btn btn-sm btn-ship-primary btn-pill" id="runSettlementBtn" onclick="runSettlementSync()">
                            <i class="bi bi-arrow-down-circle"></i> Tarik Settlement
                        </button>
                        <button class="btn btn-sm btn-ship-outline btn-pill" id="runSettlementBackfillBtn" onclick="runSettlementBackfill()">
                            <i class="bi bi-clock-history"></i> Jalankan Backfill
                        </button>
                    </div>
                </div>

            </div>

            <details class="settlement-sync-details">
                <summary>
                    <span><i class="bi bi-activity me-1"></i> Riwayat</span>
                </summary>
                <div class="settlement-sync-details-body">
                    <div class="settlement-sync-advanced">
                        <div class="controls">
                            <button class="btn btn-sm btn-ship-outline btn-pill" id="runSettlementBgBtn" onclick="runSettlementSyncBackground()">
                                <i class="bi bi-cloud-download"></i> Sync Semua
                            </button>
                            <button type="button" onclick="clearSettlementSyncConsole()" class="btn btn-sm btn-ship-outline btn-pill">
                                <i class="bi bi-eraser"></i> Clear Log
                            </button>
                        </div>
                    </div>

                    <div class="settlement-sync-console-wrap">
                        <div style="display:flex; justify-content:space-between; align-items:center; gap:.5rem;">
                            <span style="font-size:.72rem; font-weight:800; color:var(--shp-text);"><i class="bi bi-terminal"></i> Console</span>
                        </div>
                        <div id="settlementSyncConsole" class="settlement-sync-console">&gt; Kosong.</div>
                    </div>

                    <div class="card-main">
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:.75rem 1rem; border-bottom:1px solid var(--shp-border);">
                            <div style="font-weight:700; font-size:.85rem;"><i class="bi bi-clock-history" style="color:#64748b;"></i> Riwayat Sync Settlement</div>
                            <span style="font-size:.68rem; color:#94a3b8;">20 terakhir</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-list w-100" style="margin-bottom:0">
                                <thead>
                                    <tr>
                                        <th style="min-width:130px">Waktu</th>
                                        <th style="min-width:150px">Toko</th>
                                        <th style="min-width:90px">Status</th>
                                        <th style="min-width:180px">Ringkasan</th>
                                        <th style="min-width:220px">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody id="settlementSyncLogsBody">
                                    <tr>
                                        <td colspan="5" class="text-center py-4" style="color:var(--shp-muted); font-size:.8rem;">Memuat...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </details>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const { api, fmtDate, fmtRp, esc } = window.mpHelpers;
    let settlements = [], stores = [], syncLogs = [];
    let fpOrderDate = null, fpSettlementDate = null;
    let currentPage = 1;
    let paginationData = null;
    let activeTab = sessionStorage.getItem('settlementActiveTab') || 'data';
    let syncPollTimer = null;
    const $ = id => document.getElementById(id);

    function fmtDateTime(value) {
        if (!value) return '—';
        const d = new Date(value);
        if (Number.isNaN(d.getTime())) return String(value);
        return d.toLocaleString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    function getSyncStoreId() {
        return $('syncStore') ? $('syncStore').value : '';
    }

    function setSyncState(status, text) {
        const chip = $('settlementSyncStateChip');
        const value = $('settlementSyncStateValue');
        if (!chip || !value) return;

        chip.classList.remove('success', 'running', 'warn', 'error');
        if (status === 'running') chip.classList.add('running');
        else if (status === 'success') chip.classList.add('success');
        else if (status === 'warn') chip.classList.add('warn');
        else if (status === 'error') chip.classList.add('error');

        value.textContent = text;
    }

    function updateSyncDot(visible) {
        const dot = $('settlementSyncDot');
        if (!dot) return;
        dot.style.display = visible ? 'inline-block' : 'none';
    }

    function showSyncFeedback(level, title, message, metaLines = [], action = null) {
        const box = $('settlementSyncFeedback');
        if (!box) return;

        const variant = ['success', 'warn', 'error', 'info'].includes(level) ? level : 'info';
        box.className = 'settlement-sync-feedback ' + variant;

        const metaHtml = (metaLines || []).filter(Boolean).map(item => `<span>${esc(String(item))}</span>`).join('');
        const actionHtml = action?.url
            ? `<a href="${esc(action.url)}" class="btn btn-sm btn-ship-outline btn-pill" style="margin-top:.65rem;">${esc(action.label || 'Buka Aksi')}</a>`
            : '';

        box.innerHTML = `
            <i class="bi ${variant === 'success' ? 'bi-check-circle' : (variant === 'warn' ? 'bi-exclamation-triangle' : (variant === 'error' ? 'bi-x-circle' : 'bi-info-circle'))}" style="margin-top:.1rem; font-size:1rem; color:${variant === 'success' ? '#16a34a' : (variant === 'warn' ? '#d97706' : (variant === 'error' ? '#dc2626' : '#2563eb'))};"></i>
            <div style="min-width:0; flex:1 1 auto;">
                <strong>${esc(title || 'Info')}</strong>
                <div style="color:var(--shp-text); line-height:1.45; margin-top:.1rem;">${esc(message || '')}</div>
                ${metaHtml ? `<div class="settlement-feedback-meta">${metaHtml}</div>` : ''}
                ${actionHtml}
            </div>
        `;
        box.classList.remove('d-none');
    }

    function hideSyncFeedback() {
        const box = $('settlementSyncFeedback');
        if (!box) return;
        box.className = 'settlement-sync-feedback d-none';
        box.innerHTML = '';
    }

    function clearSettlementSyncConsole() {
        const box = $('settlementSyncConsole');
        if (box) box.innerHTML = '&gt; Kosong.';
    }

    window.clearSettlementSyncConsole = clearSettlementSyncConsole;

    function populateStoreSelects() {
        const filterSel = $('filterStore');
        const syncSel = $('syncStore');
        if (!filterSel || !syncSel) return;

        filterSel.innerHTML = '<option value="">Semua Toko</option>';
        syncSel.innerHTML = '<option value="">Pilih Toko</option>';

        stores.forEach(s => {
            const label = s.name + ' (' + (s.channel?.name || '?') + ')';

            const filterOpt = document.createElement('option');
            filterOpt.value = s.id;
            filterOpt.textContent = label;
            filterSel.appendChild(filterOpt);

            const syncOpt = document.createElement('option');
            syncOpt.value = s.id;
            syncOpt.textContent = label;
            syncSel.appendChild(syncOpt);
        });

        if (stores.length === 1) {
            syncSel.value = String(stores[0].id);
        }
    }

    function startSyncPolling() {
        if (syncPollTimer) return;
        syncPollTimer = setInterval(() => {
            if (activeTab === 'sync') {
                refreshSettlementSyncLogs();
            }
        }, 15000);
    }

    function stopSyncPolling() {
        if (!syncPollTimer) return;
        clearInterval(syncPollTimer);
        syncPollTimer = null;
    }

    function renderSyncConsole() {
        const box = $('settlementSyncConsole');
        if (!box) return;

        if (!syncLogs.length) {
            box.innerHTML = '&gt; Kosong.';
            return;
        }

        box.innerHTML = syncLogs.slice(0, 5).map(log => {
            const status = String(log.status || '').toLowerCase();
            const cls = status === 'success'
                ? 'settlement-sync-log-ok'
                : (status === 'processing' ? 'settlement-sync-log-muted' : (status === 'partial_success' ? 'settlement-sync-log-warn' : 'settlement-sync-log-error'));

            return `
                <div style="margin-bottom:.35rem;">
                    <span class="settlement-sync-log-muted">[${esc(fmtDateTime(log.created_at))}]</span>
                    <span class="${cls}" style="font-weight:800;">${esc(String(log.status || '-').toUpperCase())}</span>
                    <span class="settlement-sync-log-muted">${log.store_name ? '[' + esc(log.store_name) + '] ' : ''}</span>
                    <span>${esc(log.message || '—')}</span>
                </div>
            `;
        }).join('');
    }

    function renderSyncLogs() {
        const body = $('settlementSyncLogsBody');
        if (!body) return;

        if (!syncLogs.length) {
            body.innerHTML = '<tr><td colspan="5" class="text-center py-4" style="color:var(--shp-muted); font-size:.8rem;">Riwayat kosong.</td></tr>';
            return;
        }

        body.innerHTML = syncLogs.slice(0, 20).map(log => {
            const status = String(log.status || '').toLowerCase();
            const badge = status === 'success'
                ? '<span class="oc-badge oc-badge-green">SUCCESS</span>'
                : (status === 'partial_success'
                    ? '<span class="oc-badge oc-badge-amber">PARTIAL</span>'
                    : (status === 'processing'
                        ? '<span class="oc-badge oc-badge-muted">PROCESS</span>'
                        : '<span class="oc-badge oc-badge-red">FAILED</span>'));

            const payload = log.payload || {};
            const summaryParts = [];
            if (payload.found !== undefined) summaryParts.push('Found: ' + payload.found);
            if (payload.synced !== undefined) summaryParts.push('Synced: ' + payload.synced);
            if (payload.new !== undefined) summaryParts.push('Baru: ' + payload.new);
            if (payload.updated !== undefined) summaryParts.push('Update: ' + payload.updated);
            if (payload.errors !== undefined) summaryParts.push('Error: ' + payload.errors);
            if (payload.skipped !== undefined) summaryParts.push('Skip: ' + payload.skipped);

            return `
                <tr>
                    <td style="font-size:.78rem; color:var(--shp-muted); white-space:nowrap;">${esc(fmtDateTime(log.created_at))}</td>
                    <td style="font-size:.78rem; font-weight:600;">${esc(log.store_name || '—')}</td>
                    <td>${badge}</td>
                    <td style="font-size:.76rem; color:var(--shp-text);">${esc(summaryParts.length ? summaryParts.join(' · ') : log.action || '—')}</td>
                    <td style="font-size:.76rem; color:var(--shp-muted); max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${esc(log.message || '—')}">${esc(log.message || '—')}</td>
                </tr>
            `;
        }).join('');
    }

    async function refreshSettlementSyncLogs() {
        const lastCheck = $('settlementSyncLastCheck');
        if (lastCheck) lastCheck.textContent = fmtDateTime(new Date().toISOString());

        const logs = await api('/api/marketplace/sync-logs').catch(() => []);
        syncLogs = (logs || []).filter(log => log.action === 'sync_settlements');

        const latest = syncLogs[0] || null;
        const lastSuccess = syncLogs.find(log => log.status === 'success') || null;
        const running = syncLogs.some(log => log.status === 'processing');

        if (!latest) {
            setSyncState('warn', 'Kosong');
            updateSyncDot(false);
            if ($('settlementSyncLastSuccess')) $('settlementSyncLastSuccess').textContent = '—';
        } else if (running) {
            setSyncState('running', 'Berjalan');
            updateSyncDot(true);
            if ($('settlementSyncLastSuccess')) $('settlementSyncLastSuccess').textContent = lastSuccess ? fmtDateTime(lastSuccess.created_at) : '—';
        } else if (latest.status === 'success' || latest.status === 'partial_success') {
            setSyncState('success', latest.status === 'success' ? 'Siap' : 'Parsial');
            updateSyncDot(false);
            if ($('settlementSyncLastSuccess')) $('settlementSyncLastSuccess').textContent = lastSuccess ? fmtDateTime(lastSuccess.created_at) : '—';
        } else {
            setSyncState('error', 'Gagal');
            updateSyncDot(false);
            if ($('settlementSyncLastSuccess')) $('settlementSyncLastSuccess').textContent = lastSuccess ? fmtDateTime(lastSuccess.created_at) : '—';
        }

        renderSyncConsole();
        renderSyncLogs();

        return syncLogs;
    }

    window.refreshSettlementSyncLogs = refreshSettlementSyncLogs;

    window.switchSettlementTab = function (tab, skipPersist = false) {
        activeTab = tab === 'sync' ? 'sync' : 'data';
        if (!skipPersist) {
            sessionStorage.setItem('settlementActiveTab', activeTab);
        }

        const isSync = activeTab === 'sync';
        $('settlementTabData').style.display = isSync ? 'none' : '';
        $('settlementTabSync').style.display = isSync ? '' : 'none';
        $('settlementTabDataBtn').classList.toggle('active', !isSync);
        $('settlementTabSyncBtn').classList.toggle('active', isSync);

        if (isSync) {
            refreshSettlementSyncLogs();
            startSyncPolling();
        } else {
            stopSyncPolling();
        }
    };

    async function init() {
        stores = await api('/api/marketplace/stores').catch(() => []);
        populateStoreSelects();

        if (window.GFID && window.GFID.initDateRange) {
            const onDateClose = function(selectedDates) {
                if (selectedDates.length === 2 || selectedDates.length === 0) {
                    currentPage = 1;
                    loadSettlements();
                }
            };
            fpOrderDate = window.GFID.initDateRange('#filterOrderDate', { onClose: onDateClose });
            fpSettlementDate = window.GFID.initDateRange('#filterSettlementDate', { onClose: onDateClose });
        }

        document.querySelectorAll('.filter-select').forEach(el => {
            if (el.tagName === 'SELECT' || el.id === 'filterSearch') {
                el.addEventListener('change', () => {
                    if (el.id !== 'filterOrderDate' && el.id !== 'filterSettlementDate') {
                        currentPage = 1;
                        loadSettlements();
                    }
                });
            }
        });

        const filterStore = $('filterStore');
        const syncStore = $('syncStore');
        if (syncStore) {
            syncStore.dataset.manual = '0';
            syncStore.addEventListener('change', () => {
                syncStore.dataset.manual = '1';
            });
        }
        if (filterStore && syncStore) {
            filterStore.addEventListener('change', () => {
                if (syncStore.dataset.manual !== '1') {
                    syncStore.value = filterStore.value;
                }
            });
        }

        document.querySelectorAll('.filter-select').forEach(el => el.value = '');
        if (syncStore) {
            syncStore.value = stores.length === 1 ? String(stores[0].id) : '';
        }

        switchSettlementTab(activeTab, true);
        loadSettlements();
    }

    window.resetFilters = function() {
        document.querySelectorAll('.filter-select').forEach(el => el.value = '');
        if (fpOrderDate) fpOrderDate.clear();
        if (fpSettlementDate) fpSettlementDate.clear();
        const syncStore = $('syncStore');
        if (syncStore && syncStore.dataset.manual !== '1') {
            syncStore.value = '';
        }
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

        let linksHtml = '';
        if (paginationData && paginationData.last_page > 1) {
            linksHtml += '<div class="btn-group">';
            if (paginationData.current_page > 1) {
                linksHtml += `<button class="btn btn-sm btn-light border" onclick="goToPage(${paginationData.current_page - 1})">Prev</button>`;
            } else {
                linksHtml += `<button class="btn btn-sm btn-light border" disabled>Prev</button>`;
            }

            let start = Math.max(1, paginationData.current_page - 2);
            let end = Math.min(paginationData.last_page, paginationData.current_page + 2);

            for (let p = start; p <= end; p++) {
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
        const storeId = getSyncStoreId() || $('filterStore').value;
        if (!storeId) { alert('Pilih toko dulu di tab Sync sebelum tarik settlement.'); return; }

        await runSettlementSyncRequest({
            btnId: 'runSettlementBtn',
            payload: {},
            successTitle: 'Settlement sync selesai.',
            busyText: 'Syncing…',
            idleText: 'Tarik Settlement',
        });
    };

    window.runSettlementBackfill = async function (months) {
        const storeId = getSyncStoreId() || $('filterStore').value;
        if (!storeId) { alert('Pilih toko dulu di tab Sync sebelum backfill settlement.'); return; }
        const selectedMonths = Number(months || $('syncBackfillMonths')?.value || 3);
        if (![1,2,3].includes(selectedMonths)) {
            alert('Backfill hanya tersedia untuk 1, 2, atau 3 bulan.');
            return;
        }

        await runSettlementSyncRequest({
            btnId: 'runSettlementBackfillBtn',
            payload: { backfill_months: selectedMonths },
            successTitle: `Backfill ${selectedMonths} bulan selesai.`,
            busyText: `Backfill ${selectedMonths} bulan…`,
            idleText: 'Jalankan Backfill',
        });
    };

    async function runSettlementSyncRequest({ btnId, payload, successTitle, busyText, idleText }) {
        const storeId = getSyncStoreId() || $('filterStore').value;
        const btn = $(btnId);
        const state = $('settlementSyncStateValue');
        if (btn.disabled) return;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> ' + busyText;
        hideSyncFeedback();
        setSyncState('running', 'Berjalan');
        updateSyncDot(true);
        if (state) state.textContent = 'Berjalan';

        try {
            const d = await api('/api/marketplace/stores/' + storeId + '/sync-settlements', {
                method: 'POST',
                body: JSON.stringify(payload || {}),
            });

            const summaryBits = [
                `Diterima: <strong>${d.found ?? 0}</strong>`,
                `Baru: <strong>${d.new ?? 0}</strong>`,
                `Diperbarui: <strong>${d.updated ?? 0}</strong>`,
                `Dilewati: ${d.skipped ?? 0}`,
                `Gagal: ${d.errors ?? 0}`,
            ];
            if (d.batches !== undefined) summaryBits.push(`Batch: ${d.batches}`);

            showSyncFeedback('success', successTitle, d.message || 'Sync settlement selesai.', summaryBits);
            btn.innerHTML = '✓ Selesai';
            loadSettlements();
            refreshSettlementSyncLogs();
            setTimeout(() => {
                btn.disabled = false;
                btn.textContent = idleText;
                if (state) state.textContent = 'Siap';
                setSyncState('success', 'Siap');
            }, 3000);
        } catch (e) {
            const level = e.status === 401 || e.status === 422 ? 'warn' : 'error';
            const code = e.data?.code ? `Kode: ${e.data.code}` : null;
            const status = e.status ? `HTTP ${e.status}` : null;
            const action = e.data?.action && e.data.action.type === 'redirect'
                ? { url: e.data.action.url, label: e.data.action.label }
                : null;
            const message = e.data?.message || e.message || 'Sync settlement gagal. Coba lagi beberapa saat lagi.';

            showSyncFeedback(level, 'Sync gagal', message, [code, status].filter(Boolean), action);
            setSyncState('error', 'Gagal');
            updateSyncDot(false);

            btn.disabled = false;
            btn.textContent = idleText;
            refreshSettlementSyncLogs();
        }
    }

    window.runSettlementSyncBackground = async function () {
        const storeId = getSyncStoreId() || $('filterStore').value;
        if (!storeId) { alert('Pilih toko dulu di tab Sync sebelum sync latar belakang.'); return; }

        const storeName = $('syncStore')?.selectedOptions[0]?.textContent || $('filterStore').selectedOptions[0]?.textContent || 'toko ini';
        if (!confirm(`Tarik SEMUA settlement yang belum tersinkron untuk ${storeName} di latar belakang?\n\nProses ini bisa berjalan beberapa menit dan TIDAK bisa dibatalkan dari sini. Anda tetap bisa menutup/pindah halaman.`)) {
            return;
        }

        const btn = $('runSettlementBgBtn');
        const state = $('settlementSyncStateValue');
        if (btn.disabled) return;
        btn.disabled = true;
        btn.textContent = 'Mengirim ke antrian…';
        hideSyncFeedback();

        try {
            const d = await api('/api/marketplace/stores/' + storeId + '/sync-settlements-background', { method: 'POST' });
            showSyncFeedback('info', 'Dikirim ke antrian', d.message || 'Sinkronisasi dikirim ke latar belakang.', ['Latar belakang']);
            if (state) state.textContent = 'Dikirim';
            refreshSettlementSyncLogs();
        } catch (e) {
            const level = e.status === 401 || e.status === 422 ? 'warn' : 'error';
            const action = e.data?.action && e.data.action.type === 'redirect'
                ? { url: e.data.action.url, label: e.data.action.label }
                : null;
            showSyncFeedback(level, 'Gagal mengirim', e.data?.message || e.message || 'Gagal mengirim sinkronisasi ke latar belakang.', [e.data?.code ? `Kode: ${e.data.code}` : null].filter(Boolean), action);
            setSyncState('error', 'Gagal');
            refreshSettlementSyncLogs();
        } finally {
            btn.disabled = false;
            btn.textContent = 'Sync Latar Belakang (Semua Order)';
        }
    };

    init();
})();
</script>
@endpush
