@extends('layouts.app')
@section('title', 'Marketplace • Analisa Iklan')

@include('marketplace._shared')

@push('head')
<style>
/* ── Badge rekomendasi ─────────────────────────────────────────── */
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

/* ── ACOS bar ──────────────────────────────────────────────────── */
.acos-bar-wrap { position:relative; height:5px; border-radius:999px; background:#f1f5f9; overflow:visible; margin-top:3px; min-width:60px; }
.acos-bar-fill { position:absolute; left:0; top:0; height:100%; border-radius:999px; transition:width .3s; }
.acos-bar-be   { position:absolute; top:-2px; width:2px; height:9px; background:#0f172a; border-radius:1px; }

/* ── Period tabs ───────────────────────────────────────────────── */
.period-tabs { display:flex; gap:.4rem; flex-wrap:wrap; }
.period-tab {
    font-size:.75rem; font-weight:700; padding:.28rem .7rem; border-radius:999px;
    border:1px solid #e2e8f0; background:#f8fafc; color:#475569; cursor:pointer; transition:all .15s;
}
.period-tab.active, .period-tab:hover { background:#0f172a; color:#fff; border-color:#0f172a; }

/* ── Filter bar ────────────────────────────────────────────────── */
.ads-filter-bar {
    display:flex; gap:.6rem; align-items:center; flex-wrap:wrap;
    padding: .6rem .8rem; background:#f8fafc; border-radius:12px; border:1px solid #e2e8f0;
    margin-bottom:.75rem;
}
.ads-filter-bar input[type=search], .ads-filter-bar select {
    font-size:.8rem; padding:.3rem .65rem; border-radius:8px; border:1px solid #e2e8f0;
    background:#fff; color:#0f172a; outline:none;
}
.ads-filter-bar input[type=search] { min-width:200px; }
.ads-filter-bar input[type=search]:focus,
.ads-filter-bar select:focus { border-color:#0f172a; box-shadow:0 0 0 2px rgba(15,23,42,.08); }

/* ── Tabel sortable ────────────────────────────────────────────── */
.gf-clean-table th.sortable { cursor:pointer; user-select:none; white-space:nowrap; }
.gf-clean-table th.sortable:hover { color:#0f172a; }
.gf-clean-table th .sort-icon { margin-left:.25rem; opacity:.35; font-size:.7rem; }
.gf-clean-table th.sort-asc .sort-icon::after  { content:'↑'; opacity:1; }
.gf-clean-table th.sort-desc .sort-icon::after { content:'↓'; opacity:1; }
.gf-clean-table th:not(.sort-asc):not(.sort-desc) .sort-icon::after { content:'⇅'; }

/* ── KPI mini ──────────────────────────────────────────────────── */
.kpi-mini { font-size:.72rem; color:#64748b; margin-top:2px; }

/* ── Toggle switch ─────────────────────────────────────────────── */
.ads-toggle { display:flex; align-items:center; gap:.4rem; font-size:.78rem; color:#475569; font-weight:600; cursor:pointer; }
.ads-toggle input { width:32px; height:18px; accent-color:#0f172a; cursor:pointer; }

/* ── Campaign type pill ────────────────────────────────────────── */
.type-pill {
    display:inline-block; font-size:.65rem; font-weight:700; padding:.1rem .45rem;
    border-radius:4px; background:#f1f5f9; color:#64748b; text-transform:uppercase; letter-spacing:.03em;
}

/* ── Row inactive ──────────────────────────────────────────────── */
.gf-clean-table tbody tr:hover td { background:#fafafa; }
.gf-clean-table tbody tr.row-inactive td { opacity:.4; }

/* ── ROAS chip ─────────────────────────────────────────────────── */
.roas-chip { display:inline-block; font-weight:900; font-size:.88rem; padding:.05rem .4rem; border-radius:6px; }
.roas-good { background:#dcfce7; color:#15803d; }
.roas-ok   { background:#e0f2fe; color:#0369a1; }
.roas-bad  { background:#fee2e2; color:#b91c1c; }
.roas-none { color:#94a3b8; }

/* ── Pagination ────────────────────────────────────────────────── */
.ads-pager { display:flex; align-items:center; gap:.4rem; justify-content:flex-end; margin-top:.6rem; flex-wrap:wrap; }
.ads-pager-btn {
    font-size:.75rem; font-weight:700; padding:.25rem .65rem; border-radius:8px;
    border:1px solid #e2e8f0; background:#f8fafc; cursor:pointer; color:#475569; transition:all .12s;
}
.ads-pager-btn:hover:not(:disabled) { background:#0f172a; color:#fff; border-color:#0f172a; }
.ads-pager-btn:disabled { opacity:.4; cursor:default; }
.ads-pager-btn.active { background:#0f172a; color:#fff; border-color:#0f172a; }
.ads-pager-info { font-size:.75rem; color:#64748b; }
</style>
@endpush

@section('content')
<x-gf.page eyebrow="Marketplace" title="Analisa Iklan"
    description="Performa campaign Shopee Ads — spend, ROAS, ACOS, break-even & rekomendasi scale / stop.">

    {{-- ── KPI ─────────────────────────────────────────────────────────────── --}}
    <div class="oc-kpi-grid" style="grid-template-columns:repeat(7,minmax(0,1fr))">
        <div class="oc-kpi-card" style="border:1px solid rgba(34,197,94,.35)">
            <div class="oc-kpi-label">💰 Saldo Iklan</div>
            <div class="oc-kpi-value" id="kpiBalance" style="font-size:.88rem">—</div>
            <div class="kpi-mini" id="kpiBalanceSub">pilih toko untuk cek</div>
        </div>
        <div class="oc-kpi-card">
            <div class="oc-kpi-label">Total Spend</div>
            <div class="oc-kpi-value" id="kpiSpend" style="font-size:.88rem">—</div>
            <div class="kpi-mini">biaya iklan</div>
        </div>
        <div class="oc-kpi-card">
            <div class="oc-kpi-label">Sales Iklan</div>
            <div class="oc-kpi-value" id="kpiGmv" style="font-size:.88rem">—</div>
            <div class="kpi-mini">GMV attributed</div>
        </div>
        <div class="oc-kpi-card">
            <div class="oc-kpi-label">ROAS</div>
            <div class="oc-kpi-value" id="kpiRoas">—</div>
            <div class="kpi-mini">sales / spend</div>
        </div>
        <div class="oc-kpi-card">
            <div class="oc-kpi-label">ACOS</div>
            <div class="oc-kpi-value" id="kpiAcos">—</div>
            <div class="kpi-mini">spend / sales ×100</div>
        </div>
        <div class="oc-kpi-card">
            <div class="oc-kpi-label">Orders</div>
            <div class="oc-kpi-value" id="kpiOrders">—</div>
            <div class="kpi-mini" id="kpiOrdersSub">dari iklan</div>
        </div>
        <div class="oc-kpi-card">
            <div class="oc-kpi-label">Profit Setelah Iklan</div>
            <div class="oc-kpi-value" id="kpiProfit" style="font-size:.88rem">—</div>
            <div class="kpi-mini">gross profit − spend</div>
        </div>
    </div>

    {{-- ── Sync & Filter ────────────────────────────────────────────────────── --}}
    <x-gf.panel title="Sync & Filter">
        <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end">
            <div style="flex:1;min-width:180px">
                <label class="form-label fw-bold" style="font-size:.72rem;color:#64748b;text-transform:uppercase">TOKO</label>
                <select class="form-select" id="adsStoreId" style="border-radius:12px;font-size:.83rem"></select>
            </div>
            <div>
                <label class="form-label fw-bold" style="font-size:.72rem;color:#64748b;text-transform:uppercase">DARI</label>
                <input type="date" class="form-control" id="dateFrom" style="border-radius:12px;font-size:.83rem;width:150px">
            </div>
            <div>
                <label class="form-label fw-bold" style="font-size:.72rem;color:#64748b;text-transform:uppercase">SAMPAI</label>
                <input type="date" class="form-control" id="dateTo" style="border-radius:12px;font-size:.83rem;width:150px">
            </div>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                <button class="btn btn-dark fw-bold" id="syncBtn" style="border-radius:999px;min-width:150px" onclick="runSync()">
                    ↓ Sync dari API
                </button>
                <button class="btn btn-light border fw-bold btn-sm" style="border-radius:999px;font-size:.78rem" onclick="loadAds()">
                    ↻ Refresh
                </button>
            </div>
        </div>

        <div style="margin-top:.75rem">
            <div class="period-tabs" id="periodTabs">
                <span style="font-size:.72rem;color:#94a3b8;font-weight:700;align-self:center">Cepat:</span>
                <button class="period-tab" data-days="7">7 hari</button>
                <button class="period-tab active" data-days="30">30 hari</button>
                <button class="period-tab" data-days="90">90 hari</button>
            </div>
        </div>

        <div id="adsSyncAlert" class="alert d-none mt-3" style="border-radius:12px;font-size:.85rem"></div>
    </x-gf.panel>

    {{-- ── Performa Harian Toko (live dari API) ─────────────────────────────── --}}
    <x-gf.panel title="Performa Harian Toko" subtitle="Gabungan semua campaign CPC — live dari Shopee Ads API. Pilih toko lalu klik Muat.">
        <div class="d-flex align-items-center gap-2 mb-2">
            <button class="btn btn-light border fw-bold btn-sm" style="border-radius:999px;font-size:.78rem" id="btnShopPerf" onclick="loadShopPerf()">📊 Muat Performa Harian</button>
            <span class="text-muted" style="font-size:.72rem" id="shopPerfInfo"></span>
        </div>
        <div style="overflow-x:auto">
            <table class="table table-sm" style="font-size:.75rem" id="shopPerfTable">
                <thead>
                    <tr style="color:#64748b;font-size:.68rem;text-transform:uppercase">
                        <th>Tanggal</th><th class="text-end">Impresi</th><th class="text-end">Klik</th>
                        <th class="text-end">CTR</th><th class="text-end">Spend</th>
                        <th class="text-end">Order</th><th class="text-end">GMV</th><th class="text-end">ROAS</th>
                    </tr>
                </thead>
                <tbody id="shopPerfBody">
                    <tr><td colspan="8" class="text-center text-muted py-3">Belum dimuat.</td></tr>
                </tbody>
            </table>
        </div>
    </x-gf.panel>

    {{-- ── Tabel Campaign ───────────────────────────────────────────────────── --}}
    <x-gf.panel title="Performa per Campaign" subtitle="Klik header kolom untuk sort. Klik ✎ di Break-Even ACOS untuk set margin.">

        {{-- Filter bar --}}
        <div class="ads-filter-bar">
            <input type="search" id="searchCampaign" placeholder="🔍  Cari nama campaign…" oninput="applyFilters()">
            <select id="filterStatus" onchange="applyFilters()" style="min-width:130px">
                <option value="">Semua Status</option>
                <option value="ongoing">ongoing</option>
                <option value="suspended">suspended</option>
                <option value="ended">ended</option>
            </select>
            <select id="filterReco" onchange="applyFilters()" style="min-width:160px">
                <option value="">Semua Rekomendasi</option>
                <option value="🚀">🚀 Scale</option>
                <option value="✅">✅ Pertahankan</option>
                <option value="⚡">⚡ Perhatikan</option>
                <option value="🔴">🔴 Stop</option>
                <option value="⚠️">⚠️ Set Break-Even</option>
                <option value="⚪">⚪ Tidak Aktif</option>
            </select>
            <label class="ads-toggle" style="margin-left:auto">
                <input type="checkbox" id="hideInactive" checked onchange="applyFilters()">
                Sembunyikan 0-spend
            </label>
        </div>

        <div id="adsBody">
            <div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>
        </div>
        <div class="ads-pager" id="adsPager" style="display:none"></div>
    </x-gf.panel>

    {{-- Legend --}}
    <div style="display:flex;gap:.8rem;flex-wrap:wrap;font-size:.75rem;color:#64748b;margin-top:-.2rem;padding:0 .1rem">
        <span>🚀 <strong>Scale</strong> — ACOS &lt; 60% break-even</span>
        <span>✅ <strong>Pertahankan</strong> — ACOS 60–85% break-even</span>
        <span>⚡ <strong>Perhatikan</strong> — margin tipis (85–100%)</span>
        <span>🔴 <strong>Stop/Kurangi Bid</strong> — ACOS &gt; break-even</span>
        <span style="margin-left:auto;color:#94a3b8">⚠️ Set break-even ACOS agar rekomendasi akurat</span>
    </div>

</x-gf.page>
@endsection

@push('scripts')
<script>
(function () {
    const { api, fmtRp, esc } = window.mpHelpers;
    let allRows  = [];
    let filtered = [];
    let stores   = [];
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
    }

    document.querySelectorAll('.period-tab').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.period-tab').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            setDateRange(parseInt(this.dataset.days));
            loadAds();
        });
    });

    // ── Init ──────────────────────────────────────────────────────────────────
    async function init() {
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
        if (!sid) {
            $('kpiBalance').textContent = '—';
            $('kpiBalanceSub').textContent = 'pilih toko untuk cek';
            return;
        }
        $('kpiBalance').textContent = '⏳';
        try {
            const d = await api(`/api/marketplace/stores/${sid}/ads-balance`);
            $('kpiBalance').textContent = d.balance != null ? fmtRp(d.balance) : '—';
            $('kpiBalanceSub').textContent = 'sisa kredit iklan';
            // Peringatan saldo menipis
            if (d.balance != null && d.balance < 100000) {
                $('kpiBalance').style.color = '#dc2626';
                $('kpiBalanceSub').textContent = '⚠ saldo menipis — top up!';
            } else {
                $('kpiBalance').style.color = '';
            }
        } catch (e) {
            $('kpiBalance').textContent = '—';
            $('kpiBalanceSub').textContent = e.message.length > 40 ? 'gagal cek saldo' : e.message;
        }
    }

    // ── Performa Harian Toko (v2.ads.get_all_cpc_ads_daily_performance) ──────
    window.loadShopPerf = async function () {
        const sid = $('adsStoreId').value;
        if (!sid) { alert('Pilih toko dulu.'); return; }
        const btn = $('btnShopPerf');
        btn.disabled = true; btn.textContent = '⏳ Memuat…';
        $('shopPerfBody').innerHTML = '<tr><td colspan="8" class="text-center text-muted py-3">Memuat dari Shopee…</td></tr>';
        try {
            const d = await api(`/api/marketplace/stores/${sid}/ads-shop-performance?date_from=${$('dateFrom').value}&date_to=${$('dateTo').value}`);
            const days = d.days || [];
            if (!days.length) {
                $('shopPerfBody').innerHTML = '<tr><td colspan="8" class="text-center text-muted py-3">Tidak ada data di rentang ini.</td></tr>';
            } else {
                const tot = { imp:0, clk:0, spend:0, ord:0, gmv:0 };
                $('shopPerfBody').innerHTML = days.map(r => {
                    tot.imp += +r.impressions || 0; tot.clk += +r.clicks || 0;
                    tot.spend += +r.spend || 0; tot.ord += +r.orders || 0; tot.gmv += +r.gmv || 0;
                    return `<tr>
                        <td>${r.date || '—'}</td>
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
            btn.disabled = false; btn.textContent = '📊 Muat Performa Harian';
        }
    };

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
            alertEl.innerHTML = `<strong>✓ Sync selesai.</strong>
                <small> Synced: <strong>${d.synced}</strong> &nbsp;·&nbsp;
                Skipped: ${d.skipped} &nbsp;·&nbsp; Errors: ${d.errors}</small>`;
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
        $('adsBody').innerHTML = '<div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>';
        $('adsPager').style.display = 'none';
        const from = $('dateFrom').value;
        const to   = $('dateTo').value;
        const sid  = $('adsStoreId').value;
        const url  = `/api/marketplace/ads-analytics?date_from=${from}&date_to=${to}` + (sid ? `&store_id=${sid}` : '');

        const data = await api(url).catch(() => ({ rows: [], kpi: {} }));
        allRows = (data.rows || []).slice();
        renderKpi(data.kpi || {});
        page = 1;
        applyFilters();
    };

    // ── KPI ───────────────────────────────────────────────────────────────────
    function renderKpi(kpi) {
        $('kpiSpend').textContent  = fmtRp(kpi.spend || 0);
        $('kpiGmv').textContent    = fmtRp(kpi.gmv   || 0);
        $('kpiRoas').textContent   = kpi.roas != null ? kpi.roas.toFixed(2) + 'x' : '—';
        $('kpiAcos').textContent   = kpi.acos != null ? kpi.acos.toFixed(1) + '%' : '—';
        $('kpiOrders').textContent = kpi.orders != null ? kpi.orders.toLocaleString('id') : '—';
        $('kpiOrdersSub').textContent = kpi.clicks ? kpi.clicks.toLocaleString('id') + ' klik' : 'dari iklan';

        const el = $('kpiProfit');
        const p  = kpi.profit_after_ads;
        el.textContent = p != null ? fmtRp(p) : '—';
        el.style.color = p != null ? (p >= 0 ? '#16a34a' : '#b91c1c') : '';
    }

    // ── Filter + Sort ─────────────────────────────────────────────────────────
    window.applyFilters = function () {
        const q        = ($('searchCampaign').value || '').toLowerCase();
        const fStatus  = $('filterStatus').value;
        const fReco    = $('filterReco').value;
        const hideZero = $('hideInactive').checked;

        filtered = allRows.filter(r => {
            if (hideZero && Number(r.spend || 0) === 0) return false;
            if (q && !(r.campaign_name || '').toLowerCase().includes(q) &&
                     !String(r.campaign_id).includes(q)) return false;
            if (fStatus && r.status !== fStatus) return false;
            if (fReco   && (r.reco?.icon ?? '') !== fReco) return false;
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

        body.innerHTML = `
        <div class="gf-table-scroll">
        <table class="gf-clean-table w-100">
            <thead><tr>
                ${sortTh('campaign_name','Campaign','left')}
                <th>Tipe</th>
                ${sortTh('spend','Spend','right')}
                ${sortTh('gmv','Sales','right')}
                ${sortTh('roas','ROAS','right')}
                ${sortTh('acos_pct','ACOS','right')}
                <th class="text-end" title="Klik ✎ untuk set margin kotor">Break-Even ✎</th>
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

                return `<tr class="${isInactive ? 'row-inactive' : ''}">
                    <td style="max-width:220px">
                        <div class="fw-bold" style="font-size:.8rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                            title="${esc(r.campaign_name || r.campaign_id)}">${esc(r.campaign_name || r.campaign_id || '—')}</div>
                        <div style="font-size:.68rem;color:#94a3b8;font-family:monospace">#${esc(String(r.campaign_id))}</div>
                        <div style="font-size:.67rem;font-weight:700;color:${statusColors[r.status]??'#94a3b8'}">${esc(r.status||'—')}</div>
                    </td>
                    <td><span class="type-pill">${esc(r.campaign_type || '—')}</span></td>
                    <td class="text-end" style="font-size:.83rem;font-weight:700">${fmtRp(r.spend)}</td>
                    <td class="text-end" style="font-size:.83rem;font-weight:700;color:#0369a1">
                        ${fmtRp(r.gmv)}
                        ${r.direct_gmv > 0 ? `<div style="font-size:.65rem;color:#94a3b8">direct ${fmtRp(r.direct_gmv)}</div>` : ''}
                    </td>
                    <td class="text-end">
                        <span class="roas-chip ${roasClass}">${r.roas != null ? r.roas.toFixed(2) + 'x' : '—'}</span>
                    </td>
                    <td class="text-end" style="font-size:.82rem">
                        <div style="font-weight:800;color:${acosColor}">${acosPct != null ? acosPct + '%' : '—'}</div>
                        ${barHtml}
                    </td>
                    <td class="text-end" style="font-size:.82rem">
                        <span style="cursor:pointer;text-decoration:underline dotted;color:#0369a1;font-weight:800"
                            onclick="editBreakEven(${r.id})" title="Klik untuk set break-even ACOS">
                            ${bePct != null ? bePct + '%' : '<span style="color:#94a3b8">Set ✎</span>'}
                        </span>
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
        </table></div>`;

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
            foot.className = 'gf-table-foot';
            foot.innerHTML = `<span class="gf-table-foot-hint">${info}</span>`;
            body.querySelector('.gf-table-scroll').after(foot);
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

    init();
})();
</script>
@endpush
