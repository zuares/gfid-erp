@extends('layouts.app')
@section('title', 'Marketplace • Analisa Iklan')

@include('marketplace._shared')

@push('head')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/airbnb.css">
<style>
    /* ── Shipments UI Match ─────────────────────────────────────────── */
    :root{
        --shp-accent:#334155;
        --shp-accent-2:#1f2937;
        --shp-border:rgba(148,163,184,.18);
        --shp-border-strong:rgba(148,163,184,.30);
        --shp-muted:#64748b;
    }
    .page-wrap{ max-width:1040px; margin-inline:auto; padding:.75rem .75rem 4rem; background:transparent!important; }

    .card-main{
        background: var(--card); border-radius: 8px; border: 1px solid var(--shp-border);
        box-shadow: none; overflow:hidden; margin-bottom: 1rem;
    }
    body[data-theme="dark"] .card-main{ border-color: rgba(51,65,85,.85); box-shadow: none; background:var(--card,#0f172a); }

    .ship-topbar{
        position:sticky; top:0; z-index:300; display:flex; justify-content:space-between; align-items:center; gap:.6rem; flex-wrap:wrap;
        padding:.45rem .75rem; margin-inline:-.75rem; margin-bottom:.65rem; background:var(--card,#fff); border-bottom:1px solid var(--shp-border);
    }
    body[data-theme="dark"] .ship-topbar{ background:var(--card,#0f172a); }
    .title{ font-weight: 750; font-size:1rem; letter-spacing: 0; margin:0; }
    .sub{ color:var(--shp-muted); font-size:.78rem; }
    body[data-theme="dark"] .sub{ color:#9ca3af; }

    .kpis{ display:flex; flex-wrap:wrap; gap:.32rem; margin-top:.35rem; }
    .kpi{
        display:inline-flex; align-items:baseline; gap:.45rem; border-radius:7px; padding:.2rem .48rem;
        border:1px solid rgba(148,163,184,.28); background: transparent; font-size:.72rem;
    }
    body[data-theme="dark"] .kpi{ background: rgba(15, 23, 42, 0.96); border-color: rgba(51, 65, 85, 0.85); }
    .kpi .lbl{ text-transform:none; letter-spacing:0; font-size:.66rem; color:#94a3b8; }
    body[data-theme="dark"] .kpi .lbl{ color:#6b7280; }
    .kpi .val{ font-weight:650; color:var(--shp-accent); }
    body[data-theme="dark"] .kpi .val{ color:#e2e8f0; }

    .controls{ display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; }
    .filter-label{ font-size:.8rem; color:#6b7280; }
    body[data-theme="dark"] .filter-label{ color:#9ca3af; }
    .filter-select, .filter-input { 
        border-radius:7px; font-size:.82rem; border: 1px solid var(--shp-border); 
        padding:.3rem .6rem; background:var(--card,#fff); color:inherit; outline:none;
    }
    body[data-theme="dark"] .filter-select, body[data-theme="dark"] .filter-input { background: rgba(15, 23, 42, 0.98); }
    .filter-select:focus, .filter-input:focus { border-color:var(--shp-accent); }
    
    .btn-pill{ border-radius:7px; padding-inline:.78rem; box-shadow:none!important; font-weight:600; }
    .btn-ship-primary{ background:var(--shp-accent)!important; border-color:var(--shp-accent)!important; color:#fff!important; }
    .btn-ship-primary:hover{ background:var(--shp-accent-2)!important; border-color:var(--shp-accent-2)!important; color:#fff!important; }
    .btn-ship-outline{ color:#475569!important; background:transparent!important; border:1px solid rgba(148,163,184,.35)!important; }
    .btn-ship-outline:hover{ background:rgba(148,163,184,.08)!important; color:#111827!important; }
    body[data-theme="dark"] .btn-ship-outline{ color:#9ca3af!important; }
    body[data-theme="dark"] .btn-ship-outline:hover{ color:#e2e8f0!important; background:rgba(148,163,184,.15)!important; }

    .table-list{ margin-bottom:0; width:100%; border-collapse: collapse; }
    .table-list thead th{
        border-bottom:1px solid var(--shp-border); font-size:.68rem; text-transform:none; letter-spacing:0;
        color:#64748b; background: var(--card,#fff); padding:.52rem .62rem; white-space:nowrap;
    }
    body[data-theme="dark"] .table-list thead th{ background: rgba(15, 23, 42, 0.98); color:#9ca3af; }
    .table-list tbody td{ vertical-align:middle; border-top: 1px solid rgba(148, 163, 184, 0.16); padding:.52rem .62rem; }
    body[data-theme="dark"] .table-list tbody td{ border-top-color: rgba(51, 65, 85, 0.85); }
    .table-list tbody tr:hover td { background:rgba(148,163,184,.05); }

    .table-list th.sortable { cursor:pointer; user-select:none; }
    .table-list th.sortable:hover { color:#0f172a; }
    body[data-theme="dark"] .table-list th.sortable:hover { color:#e2e8f0; }
    .table-list th .sort-icon { margin-left:.25rem; opacity:.35; font-size:.7rem; }
    .table-list th.sort-asc .sort-icon::after  { content:'↑'; opacity:1; }
    .table-list th.sort-desc .sort-icon::after { content:'↓'; opacity:1; }
    .table-list th:not(.sort-asc):not(.sort-desc) .sort-icon::after { content:'⇅'; }

    .reco-badge {
        display: inline-flex; align-items: center; gap: .3rem;
        font-size: .72rem; font-weight: 800; padding: .2rem .65rem;
        border-radius: 999px; white-space: nowrap;
    }
    .reco-scale  { background: rgba(22,163,74,.12); color: #15803d; }
    .reco-ok     { background: rgba(37,99,235,.1);  color: #1d4ed8; }
    .reco-warn   { background: rgba(217,119,6,.12); color: #b45309; }
    .reco-stop   { background: rgba(185,28,28,.1);  color: #b91c1c; }
    .reco-nodata { background: rgba(148,163,184,.12); color: #64748b; }

    .acos-bar-wrap { position:relative; height:5px; border-radius:999px; background:#f1f5f9; overflow:visible; margin-top:3px; min-width:60px; }
    body[data-theme="dark"] .acos-bar-wrap { background:#334155; }
    .acos-bar-fill { position:absolute; left:0; top:0; height:100%; border-radius:999px; transition:width .3s; }
    .acos-bar-be   { position:absolute; top:-2px; width:2px; height:9px; background:#0f172a; border-radius:1px; }
    body[data-theme="dark"] .acos-bar-be { background:#e2e8f0; }

    .period-tabs { display:flex; gap:.2rem; }
    .period-tab {
        font-size:.75rem; font-weight:700; padding:.28rem .7rem; border-radius:999px;
        border:1px solid transparent; background:transparent; color:var(--shp-muted); cursor:pointer; transition:all .15s;
    }
    .period-tab.active, .period-tab:hover { background:rgba(148,163,184,.1); color:var(--shp-accent); border-color:var(--shp-border); }
    body[data-theme="dark"] .period-tab.active, body[data-theme="dark"] .period-tab:hover { color:#e2e8f0; }

    .toggle-switch { display:flex; align-items:center; gap:.4rem; font-size:.78rem; color:#475569; font-weight:600; cursor:pointer; }
    body[data-theme="dark"] .toggle-switch { color:#9ca3af; }
    .toggle-switch input { width:32px; height:18px; accent-color:#0f172a; cursor:pointer; }

    .type-pill {
        display:inline-block; font-size:.65rem; font-weight:700; padding:.1rem .45rem;
        border-radius:4px; background:#f1f5f9; color:#64748b; text-transform:uppercase; letter-spacing:.03em;
    }
    body[data-theme="dark"] .type-pill { background:#1e293b; color:#94a3b8; }

    .roas-chip { display:inline-block; font-weight:900; font-size:.88rem; padding:.05rem .4rem; border-radius:6px; }
    .roas-good { background:#dcfce7; color:#15803d; }
    .roas-ok   { background:#e0f2fe; color:#0369a1; }
    .roas-bad  { background:#fee2e2; color:#b91c1c; }
    .roas-none { color:#94a3b8; }
    body[data-theme="dark"] .roas-good { background:rgba(22,163,74,.2); color:#4ade80; }
    body[data-theme="dark"] .roas-ok { background:rgba(37,99,235,.2); color:#60a5fa; }
    body[data-theme="dark"] .roas-bad { background:rgba(220,38,38,.2); color:#f87171; }

    .row-inactive td { opacity:.4; }
    
    .ads-pager { display:flex; align-items:center; gap:.4rem; justify-content:flex-end; padding:.6rem; flex-wrap:wrap; }
    .ads-pager-btn {
        font-size:.75rem; font-weight:700; padding:.25rem .65rem; border-radius:8px;
        border:1px solid var(--shp-border); background:var(--card,#fff); cursor:pointer; color:#475569; transition:all .12s;
    }
    body[data-theme="dark"] .ads-pager-btn { background:rgba(15,23,42,0.98); color:#9ca3af; }
    .ads-pager-btn:hover:not(:disabled) { background:var(--shp-accent); color:#fff; border-color:var(--shp-accent); }
    .ads-pager-btn:disabled { opacity:.4; cursor:default; }
    .ads-pager-btn.active { background:var(--shp-accent); color:#fff; border-color:var(--shp-accent); }
    .ads-pager-info { font-size:.75rem; color:#64748b; }
</style>
@endpush

@section('content')
<div class="page-wrap">
    <div class="ship-topbar" style="margin-bottom: .8rem; border-bottom: none; background: transparent;">
        <div>
            <div class="title">Analisa Iklan</div>
            <div class="sub">Performa campaign Ads</div>

            <div class="kpis">
                <span class="kpi" title="Sisa kredit iklan"><span class="lbl">Saldo</span><span class="val" id="kpiBalance">—</span></span>
                <span class="kpi"><span class="lbl">Spend</span><span class="val" id="kpiSpend">—</span></span>
                <span class="kpi"><span class="lbl">Sales</span><span class="val" id="kpiGmv">—</span></span>
                <span class="kpi"><span class="lbl">ROAS</span><span class="val" id="kpiRoas">—</span></span>
                <span class="kpi"><span class="lbl">ACOS</span><span class="val" id="kpiAcos">—</span></span>
                <span class="kpi"><span class="lbl">Orders</span><span class="val" id="kpiOrders">—</span></span>
                <span class="kpi" title="Gross profit - spend"><span class="lbl">Profit</span><span class="val" id="kpiProfit">—</span></span>
                <span class="kpi" title="Target ROAS rata-rata tertimbang spend (campaign Custom ROAS)"><span class="lbl">Target ROAS</span><span class="val" id="kpiTargetRoas">—</span></span>
                <span class="kpi" title="Campaign aktif dgn actual ROAS di bawah target"><span class="lbl">Di bawah target</span><span class="val" id="kpiBelowTarget">—</span></span>
                <span class="kpi" title="Sinkronisasi terakhir (setting/performa)"><span class="lbl">Last Sync</span><span class="val" id="kpiLastSync">—</span></span>
            </div>
        </div>
    </div>

    <!-- Filter Bar Separated -->
    <div class="card card-main mb-3" style="border-radius: 8px;">
        <div class="card-body p-2" style="overflow-x:auto;">
            <div class="d-flex align-items-center justify-content-between gap-3 flex-nowrap" style="min-width: max-content;">
                <div class="d-flex align-items-center gap-2 flex-nowrap">
                    <select class="filter-select" id="adsStoreId"></select>
                    <div style="position:relative; display:flex; align-items:center;">
                        <i class="bi bi-calendar3" style="position:absolute; left: .65rem; color:#64748b; font-size:.75rem; pointer-events:none;"></i>
                        <input type="text" id="dateRangePicker" class="filter-input text-nowrap" style="padding-left:1.9rem; width:180px; cursor:pointer; text-align:center; font-size:.78rem;" placeholder="Pilih Tanggal..." readonly>
                    </div>
                    <input type="hidden" id="dateFrom">
                    <input type="hidden" id="dateTo">
                </div>
                
                <div class="d-flex align-items-center gap-2 flex-nowrap">
                    <button class="btn btn-sm btn-ship-primary btn-pill text-nowrap" id="syncBtn" onclick="runSync()">Sync API</button>
                    <button class="btn btn-sm btn-ship-outline btn-pill text-nowrap" onclick="loadAds()">Refresh</button>
                </div>
            </div>
        </div>
    </div>
    
    <div id="adsSyncAlert" class="alert d-none mb-3" style="border-radius:12px;font-size:.85rem"></div>

    <div class="card card-main">
        <div class="card-body p-2" style="border-bottom:1px solid var(--shp-border); overflow-x:auto;">
            <div class="d-flex justify-content-between align-items-center gap-4 flex-nowrap" style="min-width:max-content;">
                <div class="d-flex align-items-center gap-2 flex-nowrap">
                    <span class="filter-label text-nowrap" style="font-size:.7rem;font-weight:700;text-transform:uppercase">Harian</span>
                    <div class="period-tabs flex-nowrap" id="periodTabs">
                        <button class="period-tab text-nowrap" data-days="7">7 Hari</button>
                        <button class="period-tab active text-nowrap" data-days="30">30 Hari</button>
                        <button class="period-tab text-nowrap" data-days="90">90 Hari</button>
                    </div>
                </div>
                
                <div class="d-flex align-items-center gap-2 flex-nowrap">
                    <button class="btn btn-sm btn-ship-outline btn-pill text-nowrap" id="btnSyncShopPerf" onclick="syncShopPerf()">Sync DB</button>
                    <button class="btn btn-sm btn-ship-outline btn-pill text-nowrap" id="btnShopPerf" onclick="loadShopPerf()">Muat DB</button>
                    <button class="btn btn-sm btn-ship-outline btn-pill text-nowrap" id="btnBackfill" onclick="backfillAds()">Tarik 6 Bln</button>
                    <button class="btn btn-sm btn-ship-outline btn-pill text-nowrap" onclick="showBalanceHistory()">Riwayat Saldo</button>
                </div>
            </div>
        </div>
        
        <div id="perStoreWrap" class="p-2" style="display:none; border-bottom:1px solid var(--shp-border)">
            <div style="font-size:.7rem;color:#64748b;font-weight:700;text-transform:uppercase;margin-bottom:4px">Perbandingan per Toko</div>
            <div id="perStoreChips" style="display:flex;gap:.4rem;flex-wrap:wrap"></div>
        </div>
        
        <div class="table-responsive">
            <table class="table-list" id="shopPerfTable">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th class="text-end">Impresi</th>
                        <th class="text-end">Klik</th>
                        <th class="text-end">CTR</th>
                        <th class="text-end">Spend</th>
                        <th class="text-end">Order</th>
                        <th class="text-end">GMV</th>
                        <th class="text-end">ROAS</th>
                    </tr>
                </thead>
                <tbody id="shopPerfBody">
                    <tr><td colspan="8" class="text-center text-muted py-3" style="font-size:.8rem">Belum dimuat.</td></tr>
                </tbody>
            </table>
            <div id="shopPerfInfo" class="px-3 pb-2 text-muted" style="font-size:.7rem;text-align:right"></div>
        </div>
    </div>

    <div class="card card-main">
        <div class="card-body p-2" style="border-bottom:1px solid var(--shp-border); overflow-x:auto;">
            <div class="d-flex align-items-center gap-3 flex-nowrap" style="min-width:max-content;">
                
                <div class="period-tabs flex-nowrap" style="border-right:1px solid var(--shp-border); padding-right:.75rem;">
                    <button class="period-tab active text-nowrap" data-view="campaign" onclick="setView('campaign')">Campaign</button>
                    <button class="period-tab text-nowrap" data-view="item" onclick="setView('item')">Item</button>
                    <button class="period-tab text-nowrap" data-view="group" onclick="setView('group')">Grup</button>
                </div>
                
                <div class="d-flex gap-2 align-items-center flex-nowrap" style="border-right:1px solid var(--shp-border); padding-right:.75rem;">
                    <span id="unmappedBadge" class="reco-badge reco-warn" style="display:none;cursor:pointer;white-space:nowrap" onclick="toggleUnmappedFilter()"></span>
                    <input type="search" id="searchCampaign" class="filter-input" placeholder="Cari..." oninput="applyFilters()" style="width:130px">
                    <select id="filterStatus" class="filter-select" onchange="applyFilters()" style="width:auto;">
                        <option value="">Status (Semua)</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="suspended">Suspended</option>
                        <option value="ended">Ended</option>
                    </select>
                    <select id="filterReco" class="filter-select" onchange="applyFilters()" style="width:auto;">
                        <option value="">Rekomendasi (Semua)</option>
                        <option value="🚀">Scale</option>
                        <option value="✅">Pertahankan</option>
                        <option value="⚡">Perhatikan</option>
                        <option value="🔴">Stop</option>
                    </select>
                    <select id="filterBidding" class="filter-select" onchange="applyFilters()" style="width:auto;" title="Bidding method">
                        <option value="">Bidding (Semua)</option>
                        <option value="auto">Auto / GMV Max</option>
                        <option value="manual">Manual</option>
                    </select>
                    <select id="filterTarget" class="filter-select" onchange="applyFilters()" style="width:auto;" title="Status pencapaian target ROAS">
                        <option value="">Target (Semua)</option>
                        <option value="above">Di atas target</option>
                        <option value="below">Di bawah target</option>
                        <option value="maximize">Maksimalkan GMV</option>
                        <option value="no_data">Belum ada data</option>
                    </select>
                </div>

                <div class="d-flex gap-3 align-items-center flex-nowrap">
                    <label class="toggle-switch mb-0" id="unmappedFilterWrap" style="display:none; white-space:nowrap;">
                        <input type="checkbox" id="onlyUnmapped" onchange="applyFilters()">
                        Hanya Unmapped
                    </label>
                    <label class="toggle-switch mb-0" style="white-space:nowrap;">
                        <input type="checkbox" id="hideInactive" checked onchange="applyFilters()">
                        Sembunyikan 0
                    </label>
                    <button class="btn btn-sm btn-ship-outline btn-pill text-nowrap" onclick="manageGroups()">Kelola Grup</button>
                </div>
                
            </div>
        </div>
        
        <div class="table-responsive" style="overflow-y:hidden">
            <div id="adsBody">
                <div class="text-center text-muted py-4" style="font-size:.8rem">Memuat...</div>
            </div>
            <div id="adsPager" class="ads-pager" style="display:none"></div>
        </div>
    </div>
    
    {{-- Legend --}}
    <div style="display:flex;gap:.8rem;flex-wrap:wrap;font-size:.75rem;color:#64748b;margin-top:-.2rem;padding:0 .1rem">
        <span>🚀 <strong>Scale</strong> — ACOS &lt; 60% break-even</span>
        <span>✅ <strong>Pertahankan</strong> — ACOS 60–85% break-even</span>
        <span>⚡ <strong>Perhatikan</strong> — margin tipis (85–100%)</span>
        <span>🔴 <strong>Stop/Kurangi Bid</strong> — ACOS &gt; break-even</span>
        <span style="margin-left:auto;color:#94a3b8">⚠️ Set break-even ACOS agar rekomendasi akurat</span>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>

(function () {
    const { api, fmtRp, esc } = window.mpHelpers;
    let allRows  = [];
    let filtered = [];
    let stores   = [];
    let groups   = [];
    let view     = 'campaign'; // campaign | item | group
    let sortCol  = 'spend';
    let sortDir  = 'desc';
    let page     = 1;
    const PER_PAGE = 50;
    const $ = id => document.getElementById(id);

    // ── Dates ─────────────────────────────────────────────────────────────────
    function toDateStr(d) { return d.toISOString().slice(0, 10); }
    function setDateRange(days) {
        const to = new Date(), from = new Date();
        from.setDate(from.getDate() - (days - 1));
        $('dateFrom').value = toDateStr(from);
        $('dateTo').value   = toDateStr(to);
        if (window._adsDatePicker) {
            window._adsDatePicker.setDate([from, to]);
        }
    }

    document.querySelectorAll('.period-tab[data-days]').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.period-tab[data-days]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            setDateRange(parseInt(this.dataset.days));
            loadAds();
        });
    });

    // ── Init ──────────────────────────────────────────────────────────────────
    async function init() {
        if (window.flatpickr) {
            window._adsDatePicker = flatpickr('#dateRangePicker', {
                mode: 'range',
                locale: (window.flatpickr && window.flatpickr.l10ns && window.flatpickr.l10ns.id) ? 'id' : 'default',
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd M Y',
                onChange: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        $('dateFrom').value = instance.formatDate(selectedDates[0], 'Y-m-d');
                        $('dateTo').value = instance.formatDate(selectedDates[1], 'Y-m-d');
                        // Optional: auto-refresh on select
                        // loadAds();
                        
                        // Hapus status active dari period-tabs karena pakai custom date
                        document.querySelectorAll('.period-tab[data-days]').forEach(b => b.classList.remove('active'));
                    }
                }
            });
        }
        
        stores = await api('/api/marketplace/stores').catch(() => []);
        const sel = $('adsStoreId');
        sel.innerHTML = '<option value="">— semua toko —</option>';
        stores.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.name + ' (' + (s.channel?.name || '?') + ')';
            sel.appendChild(opt);
        });
        sel.addEventListener('change', () => { loadAds(); loadBalance(); });
        setDateRange(30);
        loadAds();
    }

    // ── Saldo Iklan (v2.ads.get_total_balance) ────────────────────────────────
    async function loadBalance() {
        const sid = $('adsStoreId').value;
        const setTxt = (id, v) => { const n = $(id); if (n) n.textContent = v; };
        const setCol = (id, c) => { const n = $(id); if (n) n.style.color = c; };
        const setTit = (id, t) => { const n = $(id); if (n) n.title = t; };

        setTxt('kpiBalance', '⏳');
        try {
            let balance, sub;
            if (sid) {
                const d = await api(`/api/marketplace/stores/${sid}/ads-balance`);
                balance = d.balance;
                sub = 'sisa kredit iklan';
            } else {
                // Semua toko → total saldo gabungan
                const d = await api('/api/marketplace/ads-balance-all');
                balance = d.total;
                const ok = (d.stores || []).filter(s => s.balance != null).length;
                sub = `total ${ok}/${(d.stores || []).length} toko`;
                setTit('kpiBalance', (d.stores || []).map(s => `${s.store}: ${s.balance != null ? fmtRp(s.balance) : (s.error || '—')}`).join('\n'));
            }
            setTxt('kpiBalance', balance != null ? fmtRp(balance) : '—');
            setTxt('kpiBalanceSub', sub);
            if (balance != null && balance < 100000) {
                setCol('kpiBalance', '#dc2626');
                setTxt('kpiBalanceSub', '⚠ saldo menipis — top up!');
            } else {
                setCol('kpiBalance', '');
            }
        } catch (e) {
            setTxt('kpiBalance', '—');
            setTxt('kpiBalanceSub', e.message.length > 40 ? 'gagal cek saldo' : e.message);
        }
    }

    // ── Performa Harian Toko (v2.ads.get_all_cpc_ads_daily_performance) ──────
    // Sync data harian semua toko (atau toko terpilih) ke database
    window.syncShopPerf = async function () {
        const btn = $('btnSyncShopPerf');
        btn.disabled = true; btn.textContent = '⏳ Sync…';
        try {
            const sid = $('adsStoreId').value;
            const d = await api(`/api/marketplace/ads-daily/sync?date_from=${$('dateFrom').value}&date_to=${$('dateTo').value}` + (sid ? `&store_id=${sid}` : ''), { method: 'POST' });
            if (d.errors?.length) console.warn('Sync errors:', d.errors);
            $('shopPerfInfo').textContent = d.message;
            loadShopPerf();
            loadBalance();
        } catch (e) { alert('Sync gagal: ' + e.message); }
        finally { btn.disabled = false; btn.textContent = '↓ Sync ke DB'; }
    };

    window.loadShopPerf = async function () {
        const sid = $('adsStoreId').value;
        const btn = $('btnShopPerf');
        btn.disabled = true; btn.textContent = '⏳ Memuat…';
        $('shopPerfBody').innerHTML = '<tr><td colspan="8" class="text-center text-muted py-3">Memuat dari database…</td></tr>';
        try {
            // Baca dari DB — mendukung agregat semua toko
            const d = await api(`/api/marketplace/ads-daily?date_from=${$('dateFrom').value}&date_to=${$('dateTo').value}` + (sid ? `&store_id=${sid}` : ''));
            const days = d.days || [];
            renderPerStore(d.per_store || []);
            if (!days.length) {
                $('shopPerfBody').innerHTML = '<tr><td colspan="8" class="text-center text-muted py-3">Belum ada data di rentang ini — klik "↓ Sync ke DB" dulu.</td></tr>';
            } else {
                const tot = { imp:0, clk:0, spend:0, ord:0, gmv:0 };
                const _months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
                const _fmtD = (d) => { if (!d) return '—'; const p = d.split('-'); return p.length===3 ? `${p[2]} ${_months[parseInt(p[1])-1]} ${p[0]}` : d; };
                
                $('shopPerfBody').innerHTML = days.map(r => {
                    tot.imp += +r.impressions || 0; tot.clk += +r.clicks || 0;
                    tot.spend += +r.spend || 0; tot.ord += +r.orders || 0; tot.gmv += +r.gmv || 0;
                    return `<tr>
                        <td>${_fmtD(r.date)}</td>
                        <td class="text-end">${(+r.impressions || 0).toLocaleString('id-ID')}</td>
                        <td class="text-end">${(+r.clicks || 0).toLocaleString('id-ID')}</td>
                        <td class="text-end">${r.ctr != null ? (+r.ctr).toFixed(2) + '%' : '—'}</td>
                        <td class="text-end">${fmtRp(r.spend || 0)}</td>
                        <td class="text-end">${r.orders ?? 0}</td>
                        <td class="text-end">${fmtRp(r.gmv || 0)}</td>
                        <td class="text-end fw-bold" style="color:${r.roas >= 4 ? '#166534' : (r.roas != null && r.roas < 2 ? '#dc2626' : 'inherit')}">${r.roas != null ? (+r.roas).toFixed(2) : '—'}</td>
                    </tr>`;
                }).join('') + `<tr class="fw-bold" style="border-top:2px solid #cbd5e1;background:#f8fafc">
                        <td>TOTAL</td>
                        <td class="text-end">${tot.imp.toLocaleString('id-ID')}</td>
                        <td class="text-end">${tot.clk.toLocaleString('id-ID')}</td>
                        <td class="text-end">${tot.imp ? (tot.clk / tot.imp * 100).toFixed(2) + '%' : '—'}</td>
                        <td class="text-end">${fmtRp(tot.spend)}</td>
                        <td class="text-end">${tot.ord}</td>
                        <td class="text-end">${fmtRp(tot.gmv)}</td>
                        <td class="text-end">${tot.spend ? (tot.gmv / tot.spend).toFixed(2) : '—'}</td>
                    </tr>`;
                $('shopPerfInfo').textContent = `${days.length} hari · ${$('dateFrom').value} s/d ${$('dateTo').value}`;
            }
        } catch (e) {
            $('shopPerfBody').innerHTML = `<tr><td colspan="8" class="text-center text-danger py-3">${e.message}</td></tr>`;
        } finally {
            btn.disabled = false; btn.textContent = '📊 Muat dari DB';
        }
    };

    // ── Mesin waktu: backfill 6 bulan ke belakang (per bulan, berurutan) ─────
    window.backfillAds = async function () {
        const btn = $('btnBackfill');
        btn.disabled = true;
        const sid = $('adsStoreId').value;
        let total = 0;
        try {
            for (let i = 5; i >= 0; i--) {
                const start = new Date(); start.setMonth(start.getMonth() - i, 1);
                const end = new Date(start.getFullYear(), start.getMonth() + 1, 0);
                const today = new Date(); if (end > today) end.setTime(today.getTime());
                const f = toDateStr(start), t = toDateStr(end);
                btn.textContent = `⏳ ${f.substring(0,7)}…`;
                const d = await api(`/api/marketplace/ads-daily/sync?date_from=${f}&date_to=${t}` + (sid ? `&store_id=${sid}` : ''), { method: 'POST' });
                total += d.saved || 0;
            }
            $('shopPerfInfo').textContent = `Riwayat 6 bulan tertarik: ${total} baris tersimpan.`;
            loadShopPerf();
        } catch (e) {
            alert('Backfill berhenti: ' + e.message + ' — data yang sudah tersimpan tetap aman, ulangi untuk melanjutkan.');
        } finally {
            btn.disabled = false; btn.textContent = '⏳ Tarik Riwayat 6 Bln';
        }
    };

    // ── Riwayat saldo iklan ──────────────────────────────────────────────────
    window.showBalanceHistory = async function () {
        try {
            const sid = $('adsStoreId').value;
            const d = await api(`/api/marketplace/ads-balance-history?days=60` + (sid ? `&store_id=${sid}` : ''));
            const days = (d.days || []).slice().reverse();
            const body = days.length ? `
                <div style="max-height:320px;overflow-y:auto">
                <table style="width:100%;font-size:.78rem;border-collapse:collapse">
                    <thead><tr style="color:#64748b;font-size:.66rem;text-transform:uppercase;position:sticky;top:0;background:#fff">
                        <th style="text-align:left;padding:4px">Tanggal</th><th style="text-align:right">Saldo</th><th style="text-align:right">Δ vs sebelumnya</th>
                    </tr></thead>
                    <tbody>${days.map((r, idx) => {
                        const prev = days[idx + 1];
                        const delta = prev ? r.balance - prev.balance : null;
                        const _m = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
                        const fmtDt = (d) => { if (!d) return '—'; const p = d.split('-'); return p.length===3 ? `${p[2]} ${_m[parseInt(p[1])-1]} ${p[0]}` : d; };
                        return `<tr style="border-top:1px solid #f1f5f9">
                            <td style="padding:4px">${fmtDt(r.date)}</td>
                            <td style="text-align:right;font-weight:700">${fmtRp(r.balance)}</td>
                            <td style="text-align:right;color:${delta > 0 ? '#166534' : (delta < 0 ? '#dc2626' : '#94a3b8')}">${delta != null ? (delta > 0 ? '+' : '') + fmtRp(delta) : '—'}</td>
                        </tr>`;
                    }).join('')}</tbody>
                </table>`
                : '<div class="text-muted p-3">Belum ada riwayat — snapshot saldo tercatat otomatis tiap sync & tiap malam 23:30.</div>';
            Swal.fire({ title: '💰 Riwayat Saldo Iklan', html: body, width: 480, showConfirmButton: false, showCloseButton: true });
        } catch (e) { alert('Gagal: ' + e.message); }
    };

    function renderPerStore(rows) {
        const wrap = $('perStoreWrap');
        if (!rows.length || $('adsStoreId').value) { wrap.style.display = 'none'; return; }
        wrap.style.display = '';
        $('perStoreChips').innerHTML = rows
            .sort((a, b) => b.spend - a.spend)
            .map(r => `<span style="border:1px solid rgba(148,163,184,.3);border-radius:8px;padding:4px 10px;font-size:.72rem">
                <b>${r.store || '?'}</b> · spend ${fmtRp(r.spend)} · ${r.orders} order ·
                ROAS <b style="color:${r.roas >= 4 ? '#166534' : (r.roas != null && r.roas < 2 ? '#dc2626' : 'inherit')}">${r.roas ?? '—'}</b>
            </span>`).join('');
    }

    // ── Sync dari API ─────────────────────────────────────────────────────────
    window.runSync = async function () {
        const storeId = $('adsStoreId').value;
        if (!storeId) { alert('Pilih toko dulu sebelum sync.'); return; }

        const btn     = $('syncBtn');
        const alertEl = $('adsSyncAlert');
        btn.disabled  = true;
        btn.textContent = 'Syncing…';
        alertEl.className = 'alert d-none';

        const from = $('dateFrom').value;
        const to   = $('dateTo').value;

        try {
            const d = await api(
                `/api/marketplace/stores/${storeId}/sync-ad-campaigns?date_from=${from}&date_to=${to}`,
                { method: 'POST' }
            );
            alertEl.className = 'alert alert-success';
            if (d.message && !d.synced) {
                alertEl.innerHTML = `<strong>✓ Sinkronisasi dimulai.</strong><small> ${d.message}</small>`;
            } else {
                alertEl.innerHTML = `<strong>✓ Sync selesai.</strong>
                    <small> Synced: <strong>${d.synced}</strong> &nbsp;·&nbsp;
                    Skipped: ${d.skipped} &nbsp;·&nbsp; Errors: ${d.errors}</small>`;
            }
            btn.textContent = '✓ Selesai';
            loadAds();
            setTimeout(() => { btn.disabled = false; btn.textContent = '↓ Sync dari API'; }, 3000);
        } catch (e) {
            alertEl.className = 'alert alert-danger';
            alertEl.textContent = '✗ ' + e.message;
            btn.disabled = false;
            btn.textContent = '↓ Sync dari API';
        }
    };

    // ── Load ──────────────────────────────────────────────────────────────────
    window.loadAds = async function () {
        $('adsBody').innerHTML = '<div class="text-center text-muted py-4"> Memuat…</div>';
        $('adsPager').style.display = 'none';
        const from = $('dateFrom').value;
        const to   = $('dateTo').value;
        const sid  = $('adsStoreId').value;
        const url  = `/api/marketplace/ads-analytics?date_from=${from}&date_to=${to}&group_by=${view}` + (sid ? `&store_id=${sid}` : '');

        const data = await api(url).catch(() => ({ rows: [], kpi: {}, groups: [] }));
        allRows = (data.rows || []).slice();
        groups  = data.groups || [];
        renderKpi(data.kpi || {});
        renderUnmapped(data.kpi || {});
        page = 1;
        applyFilters();
    };

    // ── View toggle: Campaign / Item / Grup ────────────────────────────────────
    window.setView = function (v) {
        if (view === v) return;
        view = v;
        document.querySelectorAll('[data-view]').forEach(b =>
            b.classList.toggle('active', b.dataset.view === v));
        // filter "hanya belum di-mapping" hanya relevan di view campaign/item
        $('unmappedFilterWrap').style.display = (v === 'campaign' || v === 'item') ? '' : 'none';
        sortCol = 'spend'; sortDir = 'desc';
        loadAds();
    };

    function renderUnmapped(kpi) {
        const badge = $('unmappedBadge');
        const n = kpi.unmapped || 0;
        if (n > 0 && view !== 'group') {
            badge.style.display = '';
            badge.textContent = `⚠️ ${n} belum di-mapping`;
        } else {
            badge.style.display = 'none';
        }
    }

    window.toggleUnmappedFilter = function () {
        const cb = $('onlyUnmapped');
        cb.checked = !cb.checked;
        $('unmappedFilterWrap').style.display = '';
        applyFilters();
    };

    // ── KPI ───────────────────────────────────────────────────────────────────
    function renderKpi(kpi) {
        const setTxt = (id, v) => { const n = $(id); if (n) n.textContent = v; };
        
        setTxt('kpiSpend', fmtRp(kpi.spend || 0));
        setTxt('kpiGmv', fmtRp(kpi.gmv || 0));
        setTxt('kpiRoas', kpi.roas != null ? kpi.roas.toFixed(2) + 'x' : '—');
        setTxt('kpiAcos', kpi.acos != null ? kpi.acos.toFixed(1) + '%' : '—');
        setTxt('kpiOrders', kpi.orders != null ? kpi.orders.toLocaleString('id') : '—');
        setTxt('kpiOrdersSub', kpi.clicks ? kpi.clicks.toLocaleString('id') + ' klik' : 'dari iklan');

        const el = $('kpiProfit');
        if (el) {
            const p  = kpi.profit_after_ads;
            el.textContent = p != null ? fmtRp(p) : '—';
            el.style.color = p != null ? (p >= 0 ? '#16a34a' : '#b91c1c') : '';
        }

        // ── GMV Max KPI (null-safe: elemen mungkin tak ada di layout tertentu) ──
        setTxt('kpiTargetRoas', kpi.weighted_target_roas != null ? kpi.weighted_target_roas.toFixed(2) + 'x' : '—');
        setTxt('kpiBelowTarget', kpi.below_target != null ? String(kpi.below_target) : '—');
        setTxt('kpiLastSync', kpi.last_sync ? fmtSyncTime(kpi.last_sync) : '—');
    }

    // Format "YYYY-MM-DD HH:MM:SS" → "24 Jul 10:30" (Asia/Jakarta apa adanya dari server)
    function fmtSyncTime(s) {
        try {
            const [d, t] = String(s).split(' ');
            const [y, m, day] = d.split('-');
            const bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
            return `${parseInt(day)} ${bulan[parseInt(m)-1]} ${(t||'').slice(0,5)}`;
        } catch (e) { return s; }
    }

    // ── Filter + Sort ─────────────────────────────────────────────────────────
    window.applyFilters = function () {
        const q        = ($('searchCampaign').value || '').toLowerCase();
        const fStatus  = $('filterStatus').value;
        const fReco    = $('filterReco').value;
        const hideZero = $('hideInactive').checked;

        const onlyUnmapped = $('onlyUnmapped').checked;
        const fBidding = $('filterBidding') ? $('filterBidding').value : '';
        const fTarget  = $('filterTarget')  ? $('filterTarget').value  : '';

        filtered = allRows.filter(r => {
            if (hideZero && Number(r.spend || 0) === 0) return false;
            if (q && !(r.campaign_name || '').toLowerCase().includes(q) &&
                     !(r.item_name || '').toLowerCase().includes(q) &&
                     !(r.item_code || '').toLowerCase().includes(q) &&
                     !String(r.campaign_id ?? '').includes(q)) return false;
            // Status & reco filter hanya untuk baris campaign (bukan agregat)
            if (fStatus && !r.is_group && r.status !== fStatus) return false;
            if (fReco   && (r.reco?.icon ?? '') !== fReco) return false;
            if (onlyUnmapped && r.internal_item_id) return false;
            // GMV Max: bidding & target status (hanya baris campaign)
            if (fBidding && !r.is_group && (r.bidding_method ?? '') !== fBidding) return false;
            if (fTarget  && !r.is_group && (r.target_status ?? '') !== fTarget) return false;
            return true;
        });

        filtered.sort((a, b) => {
            let va = a[sortCol], vb = b[sortCol];
            if (va == null) va = sortDir === 'asc' ?  Infinity : -Infinity;
            if (vb == null) vb = sortDir === 'asc' ?  Infinity : -Infinity;
            if (typeof va === 'string') va = va.toLowerCase();
            if (typeof vb === 'string') vb = vb.toLowerCase();
            return va < vb ? (sortDir === 'asc' ? -1 : 1) :
                   va > vb ? (sortDir === 'asc' ?  1 : -1) : 0;
        });

        page = 1;
        renderTable();
    };

    window.sortBy = function (col) {
        if (sortCol === col) sortDir = sortDir === 'desc' ? 'asc' : 'desc';
        else { sortCol = col; sortDir = 'desc'; }
        applyFilters();
    };

    // ── Tabel ─────────────────────────────────────────────────────────────────
    function renderTable() {
        const body   = $('adsBody');
        const pager  = $('adsPager');
        const hiddenCount = allRows.filter(r => (r.spend||0) === 0 && $('hideInactive').checked).length;

        if (!allRows.length) {
            body.innerHTML = '<div class="oc-empty">Belum ada data iklan. Pilih toko dan klik "Sync dari API".</div>';
            pager.style.display = 'none';
            return;
        }
        if (!filtered.length) {
            const hint = hiddenCount > 0
                ? `<br><small style="color:#94a3b8">${hiddenCount} campaign 0-spend disembunyikan — hilangkan centang toggle untuk tampilkan.</small>`
                : '';
            body.innerHTML = `<div class="oc-empty">Tidak ada campaign yang cocok filter.${hint}</div>`;
            pager.style.display = 'none';
            return;
        }

        const totalPages = Math.ceil(filtered.length / PER_PAGE);
        if (page > totalPages) page = totalPages;
        const slice = filtered.slice((page - 1) * PER_PAGE, page * PER_PAGE);

        function sortTh(col, label, align) {
            const cls = sortCol === col ? (sortDir === 'asc' ? 'sort-asc' : 'sort-desc') : '';
            return `<th class="sortable ${cls} ${align === 'right' ? 'text-end' : ''}" onclick="sortBy('${col}')">${label}<span class="sort-icon"></span></th>`;
        }

        const firstLabel = view === 'item' ? 'Item Internal' : view === 'group' ? 'Grup' : 'Campaign';
        const showMapCol = view === 'campaign';

        body.innerHTML = `
        <div class="">
        <table class="table-list w-100">
            <thead><tr>
                ${sortTh('campaign_name',firstLabel,'left')}
                <th>Tipe</th>
                ${showMapCol ? '<th>Item / Grup</th>' : ''}
                ${showMapCol ? '<th>Bidding</th>' : ''}
                ${showMapCol ? sortTh('target_roas','Target','right') : ''}
                ${showMapCol ? sortTh('campaign_budget','Budget','right') : ''}
                ${sortTh('spend','Spend','right')}
                ${sortTh('gmv','Sales','right')}
                ${sortTh('roas','ROAS','right')}
                ${sortTh('acos_pct','ACOS','right')}
                <th class="text-end" title="${showMapCol ? 'Klik ✎ untuk set margin kotor' : 'break-even tertimbang'}">Break-Even${showMapCol ? ' ✎' : ''}</th>
                ${sortTh('profit_after_ads','Profit Stlh Iklan','right')}
                ${sortTh('orders','Orders','right')}
                ${sortTh('clicks','Klik','right')}
                <th>Rekomendasi</th>
            </tr></thead>
            <tbody>
            ${slice.map(r => {
                const acosPct  = r.acos_pct;
                const bePct    = r.break_even_acos_pct;
                const profit   = r.profit_after_ads;

                const acosColor = acosPct !== null && bePct !== null
                    ? (acosPct <= bePct ? '#16a34a' : '#b91c1c') : '#0f172a';

                let barHtml = '';
                if (acosPct !== null && bePct !== null) {
                    const maxW     = Math.max(bePct * 1.5, 1);
                    const fillPct  = Math.round(Math.min(acosPct, maxW) / maxW * 100);
                    const bePos    = Math.round(bePct / maxW * 100);
                    const fillColor = acosPct <= bePct ? '#16a34a' : '#ef4444';
                    barHtml = `<div class="acos-bar-wrap">
                        <div class="acos-bar-fill" style="width:${fillPct}%;background:${fillColor}"></div>
                        <div class="acos-bar-be"  style="left:${bePos}%"></div>
                    </div>`;
                }

                const recoClass = {
                    '🚀':'reco-scale','✅':'reco-ok','⚡':'reco-warn',
                    '🔴':'reco-stop','⚪':'reco-nodata','⚠️':'reco-warn'
                }[r.reco?.icon] ?? 'reco-nodata';

                const statusColors = {
                    ongoing:'#16a34a', suspended:'#b45309',
                    ended:'#94a3b8',   unknown:'#94a3b8'
                };

                const roasClass = r.roas == null ? 'roas-none'
                    : r.roas >= 4 ? 'roas-good'
                    : r.roas >= 2 ? 'roas-ok'
                    : 'roas-bad';

                const isInactive = (r.spend || 0) === 0;

                // ── Sel pertama: berbeda per view ──────────────────────────
                let firstCell;
                if (r.is_group) {
                    const dot = r.group_color ? `<span style="display:inline-block;width:9px;height:9px;border-radius:50%;background:${esc(r.group_color)};margin-right:5px"></span>` : '';
                    const sub = r.item_code ? `<div style="font-size:.68rem;color:#94a3b8;font-family:monospace">${esc(r.item_code)}</div>` : '';
                    firstCell = `<td style="max-width:240px">
                        <div class="fw-bold" style="font-size:.8rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${esc(r.campaign_name)}">${dot}${esc(r.campaign_name || '—')}</div>
                        ${sub}
                        <div style="font-size:.67rem;color:#94a3b8">${r.members} campaign digabung</div>
                    </td>`;
                } else {
                    const cs = r.campaign_status
                        ? ` · <span style="font-weight:700;color:${campStatusColor(r.campaign_status)}">${statusLabel(r.campaign_status)}</span>` : '';
                    firstCell = `<td style="max-width:220px">
                        <div class="fw-bold" style="font-size:.8rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                            title="${esc(r.campaign_name || r.campaign_id)}">${esc(r.campaign_name || r.campaign_id || '—')}</div>
                        <div style="font-size:.67rem;color:#94a3b8">
                            <span style="cursor:pointer;text-decoration:underline dotted;font-family:monospace" onclick="openCampaignDetail(${r.id})" title="Lihat detail (read-only)">#${esc(String(r.campaign_id))} · Detail</span>${cs}
                        </div>
                    </td>`;
                }

                // ── Sel mapping (hanya view campaign) ──────────────────────
                let mapCell = '';
                if (showMapCol) {
                    const mapped = r.internal_item_id;
                    const mapBadge = mapped
                        ? `<span class="reco-badge reco-ok" style="cursor:pointer" onclick="mapItem(${r.id})" title="${esc((r.item_code||'')+' '+(r.item_name||''))}">🔗 ${esc(r.item_code || r.item_name || 'item')}</span>`
                        : `<span class="reco-badge reco-warn" style="cursor:pointer" onclick="mapItem(${r.id})">＋ Map item</span>`;
                    const grpChip = r.ad_group_id
                        ? `<span class="type-pill" style="cursor:pointer;background:${r.group_color?esc(r.group_color)+'22':'#f1f5f9'}" onclick="assignGroup(${r.id})">${esc(r.group_name)}</span>`
                        : `<span class="type-pill" style="cursor:pointer" onclick="assignGroup(${r.id})">＋ grup</span>`;
                    mapCell = `<td style="max-width:170px"><div style="display:flex;flex-direction:column;gap:3px;align-items:flex-start">${mapBadge}${grpChip}</div></td>`;
                }

                // ── Sel GMV Max (hanya view campaign) ──────────────────────
                let biddingCell = '', targetCell = '', budgetCell = '';
                if (showMapCol) {
                    biddingCell = `<td>${biddingLabel(r.bidding_method)}</td>`;
                    const tRoas = r.target_roas;
                    const targetText = (tRoas != null && tRoas > 0) ? fmtRoas(tRoas)
                        : (r.bidding_method === 'auto' && tRoas === 0) ? '<span style="font-size:.68rem;color:#0369a1">Maks. GMV</span>'
                        : '<span style="color:#cbd5e1">—</span>';
                    targetCell = `<td class="text-end" style="font-size:.78rem">${targetText}</td>`;
                    budgetCell = `<td class="text-end" style="font-size:.78rem">${r.campaign_budget != null ? fmtRp(r.campaign_budget) : '<span style="color:#cbd5e1">—</span>'}</td>`;
                }

                return `<tr class="${isInactive ? 'row-inactive' : ''}">
                    ${firstCell}
                    <td><span class="type-pill">${esc(r.campaign_type || '—')}</span></td>
                    ${mapCell}
                    ${biddingCell}
                    ${targetCell}
                    ${budgetCell}
                    <td class="text-end" style="font-size:.83rem;font-weight:700">${fmtRp(r.spend)}</td>
                    <td class="text-end" style="font-size:.83rem;font-weight:700;color:#0369a1">
                        ${fmtRp(r.gmv)}
                        ${r.direct_gmv > 0 ? `<div style="font-size:.65rem;color:#94a3b8">direct ${fmtRp(r.direct_gmv)}</div>` : ''}
                    </td>
                    <td class="text-end">
                        <span class="roas-chip ${roasClass}">${r.roas != null ? r.roas.toFixed(2) + 'x' : '—'}</span>
                        ${showMapCol ? targetBadge(r.target_status) : ''}
                    </td>
                    <td class="text-end" style="font-size:.82rem">
                        <div style="font-weight:800;color:${acosColor}">${acosPct != null ? acosPct + '%' : '—'}</div>
                        ${barHtml}
                    </td>
                    <td class="text-end" style="font-size:.82rem">
                        ${r.is_group
                            ? `<span style="font-weight:800;color:#475569">${bePct != null ? bePct + '%' : '—'}</span>`
                            : `<span style="cursor:pointer;text-decoration:underline dotted;color:#0369a1;font-weight:800"
                                onclick="editBreakEven(${r.id})" title="Klik untuk set break-even ACOS">
                                ${bePct != null ? bePct + '%' : '<span style="color:#94a3b8">Set ✎</span>'}
                               </span>`}
                    </td>
                    <td class="text-end" style="font-size:.83rem;font-weight:900;color:${profit===null?'#94a3b8':profit>=0?'#16a34a':'#b91c1c'}">
                        ${profit != null ? fmtRp(profit) : '<span style="color:#94a3b8;font-weight:400">—</span>'}
                    </td>
                    <td class="text-end" style="font-size:.83rem">${(r.orders||0).toLocaleString('id')}</td>
                    <td class="text-end" style="font-size:.8rem">
                        ${(r.clicks||0).toLocaleString('id')}
                        ${r.cpc && r.cpc > 0 ? `<div style="font-size:.65rem;color:#94a3b8">CPC ${fmtRp(r.cpc)}</div>` : ''}
                    </td>
                    <td><span class="reco-badge ${recoClass}">${r.reco?.icon ?? ''} ${esc(r.reco?.label ?? '—')}</span></td>
                </tr>`;
            }).join('')}
            </tbody>
        </table>`;

        // Footer / pager
        const hiddenNote = hiddenCount > 0
            ? ` &nbsp;·&nbsp; <span style="color:#94a3b8">${hiddenCount} campaign 0-spend disembunyikan</span>` : '';
        const info = `${filtered.length} campaign ditampilkan${hiddenNote}`;

        if (totalPages > 1) {
            pager.style.display = 'flex';
            pager.innerHTML = buildPager(page, totalPages, info);
        } else {
            pager.style.display = 'none';
            const foot = document.createElement('div');
            foot.className = 'p-2';
            foot.innerHTML = `<span class="text-muted small">${info}</span>`;
            body.querySelector('.table-list').after(foot);
        }
    }

    function buildPager(cur, total, info) {
        let h = `<span class="ads-pager-info">${info}</span>`;
        h += `<button class="ads-pager-btn" onclick="goPage(${cur-1})" ${cur<=1?'disabled':''}>‹ Prev</button>`;
        const pages = [];
        if (total <= 7) { for (let i=1;i<=total;i++) pages.push(i); }
        else {
            pages.push(1);
            if (cur > 3) pages.push('…');
            for (let i=Math.max(2,cur-1); i<=Math.min(total-1,cur+1); i++) pages.push(i);
            if (cur < total-2) pages.push('…');
            pages.push(total);
        }
        pages.forEach(p => {
            if (p==='…') h += `<span class="ads-pager-info">…</span>`;
            else h += `<button class="ads-pager-btn${p===cur?' active':''}" onclick="goPage(${p})">${p}</button>`;
        });
        h += `<button class="ads-pager-btn" onclick="goPage(${cur+1})" ${cur>=total?'disabled':''}>Next ›</button>`;
        return h;
    }

    window.goPage = function (p) {
        const total = Math.ceil(filtered.length / PER_PAGE);
        if (p < 1 || p > total) return;
        page = p;
        renderTable();
        $('adsBody').scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    // ── Edit Break-Even ACOS ──────────────────────────────────────────────────
    window.editBreakEven = function (rowId) {
        const r = allRows.find(x => x.id === rowId);
        if (!r) return;
        const cur = r.break_even_acos != null ? (r.break_even_acos * 100).toFixed(1) : '';
        const val = prompt(
            `Break-even ACOS untuk "${r.campaign_name || r.campaign_id}" (%)\n\nContoh: 25 berarti 25%\n(= margin kotor produk setelah fee marketplace)`,
            cur
        );
        if (val === null) return;
        const pct = parseFloat(val);
        if (isNaN(pct) || pct < 0 || pct > 100) { alert('Masukkan angka 0–100'); return; }

        api(`/api/marketplace/ad-campaigns/${r.id}/break-even`, {
            method: 'PATCH',
            body: JSON.stringify({ break_even_acos: pct / 100 }),
        }).then(() => loadAds()).catch(e => alert('Gagal simpan: ' + e.message));
    };

    // ── Mapping campaign → item internal ───────────────────────────────────────
    window.mapItem = function (rowId) {
        const r = allRows.find(x => x.id === rowId);
        if (!r) return;
        let timer = null;

        Swal.fire({
            title: 'Mapping ke Item Internal',
            html: `
                <div style="text-align:left;font-size:.82rem">
                    <div style="color:#64748b;margin-bottom:.4rem">Campaign: <b>${esc(r.campaign_name || r.campaign_id)}</b>
                    ${r.channel_item_id ? `<br><span style="font-family:monospace;font-size:.72rem;color:#94a3b8">Shopee item_id: ${esc(String(r.channel_item_id))}</span>` : ''}</div>
                    <input id="mapSearch" class="form-control" placeholder="Cari nama / kode item…" autocomplete="off" style="font-size:.85rem;border-radius:10px">
                    <div id="mapResults" style="max-height:240px;overflow-y:auto;margin-top:.5rem"></div>
                    ${r.internal_item_id ? `<button id="mapClear" class="btn btn-sm btn-light border w-100 mt-2" style="font-size:.78rem;border-radius:999px">✕ Hapus mapping (kembali ke otomatis)</button>` : ''}
                </div>`,
            showConfirmButton: false, showCloseButton: true, width: 460,
            didOpen: () => {
                const input = document.getElementById('mapSearch');
                const box   = document.getElementById('mapResults');
                const doSave = (itemId) => {
                    api(`/api/marketplace/ad-campaigns/${rowId}/map-item`, {
                        method: 'PATCH', body: JSON.stringify({ internal_item_id: itemId }),
                    }).then(() => { Swal.close(); loadAds(); })
                      .catch(e => alert('Gagal: ' + e.message));
                };
                const render = (items) => {
                    box.innerHTML = items.length ? items.map(it => `
                        <div class="map-opt" data-id="${it.id}" style="padding:.45rem .5rem;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:4px;cursor:pointer">
                            <div style="font-weight:700;font-size:.82rem">${esc(it.name || '—')}</div>
                            <div style="font-size:.7rem;color:#94a3b8;font-family:monospace">${esc(it.code || '')} · COGS ${fmtRp(it.hpp || 0)}</div>
                        </div>`).join('') : '<div style="color:#94a3b8;padding:.5rem">Tidak ada hasil.</div>';
                    box.querySelectorAll('.map-opt').forEach(el =>
                        el.onclick = () => doSave(parseInt(el.dataset.id)));
                };
                const search = (q) => api(`/api/marketplace/items/search?q=${encodeURIComponent(q)}`)
                    .then(render).catch(() => box.innerHTML = '<div style="color:#dc2626;padding:.5rem">Gagal cari.</div>');
                input.oninput = () => { clearTimeout(timer); timer = setTimeout(() => search(input.value.trim()), 250); };
                search('');
                input.focus();
                const clr = document.getElementById('mapClear');
                if (clr) clr.onclick = () => doSave(null);
            },
        });
    };

    // ── Assign campaign ke grup ────────────────────────────────────────────────
    window.assignGroup = function (rowId) {
        const r = allRows.find(x => x.id === rowId);
        if (!r) return;
        const opts = groups.map(g =>
            `<option value="${g.id}" ${g.id === r.ad_group_id ? 'selected' : ''}>${esc(g.name)}</option>`).join('');
        Swal.fire({
            title: 'Assign ke Grup',
            html: `<div style="text-align:left;font-size:.82rem">
                <div style="color:#64748b;margin-bottom:.4rem">${esc(r.campaign_name || r.campaign_id)}</div>
                <select id="grpSel" class="form-select" style="font-size:.85rem;border-radius:10px">
                    <option value="">— tanpa grup —</option>${opts}
                </select>
                <button id="grpNew" class="btn btn-sm btn-light border mt-2" style="font-size:.76rem;border-radius:999px">＋ Grup baru</button>
            </div>`,
            showCancelButton: true, confirmButtonText: 'Simpan', cancelButtonText: 'Batal',
            didOpen: () => {
                document.getElementById('grpNew').onclick = async () => {
                    const name = prompt('Nama grup baru:');
                    if (!name) return;
                    try {
                        const d = await api('/api/marketplace/ad-groups', { method: 'POST', body: JSON.stringify({ name }) });
                        groups.push(d.group);
                        const sel = document.getElementById('grpSel');
                        const o = document.createElement('option');
                        o.value = d.group.id; o.textContent = d.group.name; o.selected = true;
                        sel.appendChild(o);
                    } catch (e) { alert('Gagal: ' + e.message); }
                };
            },
            preConfirm: () => document.getElementById('grpSel').value,
        }).then(res => {
            if (!res.isConfirmed) return;
            api(`/api/marketplace/ad-campaigns/${rowId}/group`, {
                method: 'PATCH', body: JSON.stringify({ ad_group_id: res.value ? parseInt(res.value) : null }),
            }).then(() => loadAds()).catch(e => alert('Gagal: ' + e.message));
        });
    };

    // ── Kelola grup ────────────────────────────────────────────────────────────
    window.manageGroups = function () {
        const list = groups.length ? groups.map(g => `
            <div style="display:flex;align-items:center;gap:.5rem;padding:.4rem .5rem;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:4px">
                <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:${g.color || '#cbd5e1'}"></span>
                <b style="font-size:.82rem">${esc(g.name)}</b>
                <span style="font-size:.7rem;color:#94a3b8">${g.campaigns_count} campaign</span>
            </div>`).join('') : '<div style="color:#94a3b8;padding:.5rem">Belum ada grup.</div>';
        Swal.fire({
            title: '🏷️ Kelola Grup',
            html: `<div style="text-align:left">${list}
                <button id="mgNew" class="btn btn-sm btn-dark w-100 mt-2" style="font-size:.8rem;border-radius:999px">＋ Buat Grup Baru</button>
                <div style="font-size:.72rem;color:#94a3b8;margin-top:.5rem">Assign campaign ke grup lewat kolom "Item / Grup" di tabel (view Campaign).</div>
            </div>`,
            showConfirmButton: false, showCloseButton: true, width: 440,
            didOpen: () => {
                document.getElementById('mgNew').onclick = async () => {
                    const name = prompt('Nama grup:');
                    if (!name) return;
                    const color = prompt('Warna (hex, opsional, mis. #2563eb):', '#2563eb') || null;
                    try {
                        await api('/api/marketplace/ad-groups', { method: 'POST', body: JSON.stringify({ name, color }) });
                        Swal.close(); loadAds();
                    } catch (e) { alert('Gagal: ' + e.message); }
                };
            },
        });
    };

    // ── GMV Max helpers (label read-only; tidak mengubah DB) ───────────────────
    function fmtRoas(v) { return (Math.round(v * 100) / 100).toFixed(2).replace('.', ',') + 'x'; }
    function statusLabel(s) { return ({ ongoing:'Aktif', closed:'Selesai', paused:'Dijeda', scheduled:'Terjadwal' })[s] || (s || '—'); }
    function campStatusColor(s) { return ({ ongoing:'#16a34a', closed:'#94a3b8', paused:'#b45309', scheduled:'#2563eb' })[s] || '#94a3b8'; }
    function biddingLabel(m) {
        if (m === 'auto') return `<span class="type-pill" title="API tidak memberi label literal GMV Max. Identifikasi berdasarkan bidding method (auto) & struktur auto_bidding." style="background:#ecfdf5;color:#047857">Auto / GMV Max</span>`;
        if (m === 'manual') return `<span class="type-pill">Manual</span>`;
        return '<span style="color:#cbd5e1">—</span>';
    }
    function targetBadge(st) {
        const m = ({ above:['Di atas','#15803d'], below:['Di bawah','#b91c1c'], maximize:['Maks GMV','#475569'] })[st];
        return m ? `<div style="font-size:.6rem;font-weight:700;margin-top:2px;color:${m[1]}">${m[0]}</div>` : '';
    }

    // ── Detail campaign (READ-ONLY modal) ──────────────────────────────────────
    window.openCampaignDetail = async function (id) {
        Swal.fire({ title: 'Memuat detail…', didOpen: () => Swal.showLoading(), showConfirmButton: false });
        try {
            const from = $('dateFrom').value, to = $('dateTo').value;
            const d = await api(`/api/marketplace/ad-campaigns/${id}/detail?date_from=${from}&date_to=${to}`);
            const s = d.setting || {}, p = d.performance || {};
            const roasFmt = v => v != null ? v.toFixed(2) + 'x' : '—';
            const rp  = v => v != null ? fmtRp(v) : '—';
            const pct = v => v != null ? v.toFixed(2) + '%' : '—';
            const num = v => v != null ? Number(v).toLocaleString('id') : '—';
            const targetTxt = (s.target_roas != null && s.target_roas > 0) ? fmtRoas(s.target_roas)
                : (s.bidding_method === 'auto' && s.target_roas === 0) ? 'Maksimalkan GMV' : '—';
            const daily = d.daily || [];
            const dailyRows = daily.length ? daily.map(row => `<tr>
                <td>${esc(row.date)}</td><td class="text-end">${rp(row.expense)}</td>
                <td class="text-end">${num(row.impressions)}</td><td class="text-end">${num(row.clicks)}</td>
                <td class="text-end">${rp(row.broad_gmv)}</td><td class="text-end">${num(row.broad_order)}</td>
            </tr>`).join('') : '<tr><td colspan="6" style="text-align:center;color:#94a3b8">Belum ada data harian di rentang ini.</td></tr>';
            let rawBlock = '';
            if (d.raw_setting_payload) {
                rawBlock = `<details style="margin-top:.6rem"><summary style="cursor:pointer;font-size:.72rem;color:#64748b">Raw setting payload (debug, owner)</summary>
                  <pre style="max-height:200px;overflow:auto;font-size:.65rem;background:#0f172a;color:#e2e8f0;padding:.5rem;border-radius:6px">${esc(JSON.stringify(d.raw_setting_payload, null, 2))}</pre></details>`;
            }
            Swal.fire({
                title: esc(s.item_name || ('Campaign #' + s.campaign_id)),
                width: 640, showCloseButton: true, showConfirmButton: false,
                html: `<div style="text-align:left;font-size:.8rem">
                  <div style="display:grid;grid-template-columns:1fr 1fr;gap:.3rem .8rem;margin-bottom:.5rem">
                    <div><b>Campaign ID</b><br>${esc(String(s.campaign_id || '—'))}</div>
                    <div><b>Item ID</b><br>${esc(String(s.item_id || '—'))} ${s.item_code ? ('· ' + esc(s.item_code)) : ''}</div>
                    <div><b>Ad type</b><br>${esc(s.ad_type || '—')}</div>
                    <div><b>Bidding</b><br>${esc(s.bidding_method || '—')}</div>
                    <div><b>Target ROAS</b><br>${targetTxt}</div>
                    <div><b>Budget</b><br>${rp(s.campaign_budget)}</div>
                    <div><b>Placement</b><br>${esc(s.campaign_placement || '—')}</div>
                    <div><b>Status</b><br>${esc(s.campaign_status || '—')}</div>
                    <div><b>Setting synced</b><br>${esc(s.setting_synced_at || '—')}</div>
                  </div><hr>
                  <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.3rem .6rem;font-size:.75rem">
                    <div><b>Spend</b><br>${rp(p.spend)}</div>
                    <div><b>Broad GMV</b><br>${rp(p.broad_gmv)}</div>
                    <div><b>Direct GMV</b><br>${rp(p.direct_gmv)}</div>
                    <div><b>Broad ROAS</b><br>${roasFmt(p.broad_roas)}</div>
                    <div><b>Direct ROAS</b><br>${roasFmt(p.direct_roas)}</div>
                    <div><b>CTR</b><br>${pct(p.ctr)}</div>
                    <div><b>CPC</b><br>${rp(p.cpc)}</div>
                    <div><b>Broad order</b><br>${num(p.broad_order)}</div>
                    <div><b>Broad CVR</b><br>${pct(p.broad_cvr)}</div>
                  </div>
                  <details style="margin-top:.6rem"><summary style="cursor:pointer;font-size:.72rem;color:#64748b">Tren harian (${daily.length} hari)</summary>
                    <table style="width:100%;font-size:.68rem;margin-top:.3rem"><thead><tr style="color:#64748b"><th style="text-align:left">Tgl</th><th class="text-end">Spend</th><th class="text-end">Impr</th><th class="text-end">Klik</th><th class="text-end">GMV</th><th class="text-end">Order</th></tr></thead><tbody>${dailyRows}</tbody></table>
                  </details>${rawBlock}
                </div>`,
            });
        } catch (e) { Swal.fire('Gagal', esc(e.message || 'Tidak dapat memuat detail'), 'error'); }
    };

    init();
})();

</script>
@endpush
