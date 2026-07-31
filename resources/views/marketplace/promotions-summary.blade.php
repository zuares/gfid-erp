@extends('layouts.app')
@section('title', 'Marketplace • Summary Promosi')

@include('marketplace._shared')

@push('head')
<style>
    .summary-wrap{ max-width:1180px; margin-inline:auto; padding:.75rem .75rem 4rem; background:transparent!important; }
    .summary-shell{ display:grid; gap:1rem; }
    .summary-hero{
        border:1px solid rgba(148,163,184,.24);
        border-radius:16px;
        padding:1rem;
        background:linear-gradient(135deg, rgba(248,250,252,.98) 0%, rgba(241,245,249,.95) 100%);
        box-shadow:0 10px 30px rgba(15,23,42,.04);
    }
    body[data-theme="dark"] .summary-hero{
        background:linear-gradient(135deg, rgba(15,23,42,.98) 0%, rgba(2,6,23,.96) 100%);
        border-color:rgba(51,65,85,.88);
    }
    .summary-topline{
        display:flex; justify-content:space-between; align-items:flex-start; gap:.75rem; flex-wrap:wrap;
    }
    .summary-title{ font-weight:800; font-size:1.05rem; margin:0; }
    .summary-sub{ color:var(--shp-muted); font-size:.8rem; margin-top:.15rem; }
    body[data-theme="dark"] .summary-sub{ color:#9ca3af; }
    .summary-filters{
        display:flex; flex-wrap:wrap; gap:.5rem; align-items:center;
        margin-top:1rem; padding-top:1rem; border-top:1px solid rgba(148,163,184,.16);
    }
    body[data-theme="dark"] .summary-filters{ border-top-color:rgba(51,65,85,.78); }
    .summary-filter{
        border-radius:8px; font-size:.82rem; border:1px solid var(--shp-border);
        padding:.4rem .65rem; background:var(--card,#fff); color:inherit; outline:none;
    }
    body[data-theme="dark"] .summary-filter{ background:rgba(15,23,42,.98); }
    .summary-filter:focus{ border-color:var(--shp-accent); }
    .summary-kpis{
        display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.75rem;
    }
    .summary-kpi{
        border:1px solid rgba(148,163,184,.22);
        border-radius:14px;
        padding:.9rem 1rem;
        background:linear-gradient(180deg, rgba(255,255,255,.98) 0%, rgba(248,250,252,.98) 100%);
    }
    body[data-theme="dark"] .summary-kpi{
        background:linear-gradient(180deg, rgba(15,23,42,.96) 0%, rgba(2,6,23,.96) 100%);
        border-color:rgba(51,65,85,.85);
    }
    .summary-kpi .lbl{
        color:#64748b; font-size:.67rem; font-weight:900; text-transform:uppercase; letter-spacing:.04em;
    }
    body[data-theme="dark"] .summary-kpi .lbl{ color:#94a3b8; }
    .summary-kpi .val{
        color:#0f172a; font-size:1.35rem; font-weight:950; line-height:1.1; margin-top:.15rem;
    }
    body[data-theme="dark"] .summary-kpi .val{ color:#f8fafc; }
    .summary-kpi .note{ color:#94a3b8; font-size:.7rem; font-weight:700; margin-top:.2rem; }
    .summary-store-grid{
        display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.75rem;
    }
    .summary-store-card{
        border:1px solid rgba(148,163,184,.22);
        border-radius:14px;
        padding:1rem;
        background:var(--card,#fff);
    }
    body[data-theme="dark"] .summary-store-card{
        background:rgba(15,23,42,.96);
        border-color:rgba(51,65,85,.84);
    }
    .summary-store-head{ display:flex; justify-content:space-between; align-items:flex-start; gap:.75rem; flex-wrap:wrap; }
    .summary-store-name{ font-weight:800; }
    .summary-store-meta{ color:var(--shp-muted); font-size:.76rem; margin-top:.2rem; }
    body[data-theme="dark"] .summary-store-meta{ color:#9ca3af; }
    .summary-badges{ display:flex; flex-wrap:wrap; gap:.3rem; margin-top:.75rem; }
    .summary-badge{
        display:inline-flex; align-items:center; gap:.3rem;
        border-radius:999px; padding:.15rem .52rem;
        font-size:.68rem; font-weight:800; white-space:nowrap;
    }
    .summary-badge.ongoing{ background:rgba(22,163,74,.12); color:#15803d; }
    .summary-badge.upcoming{ background:rgba(37,99,235,.10); color:#1d4ed8; }
    .summary-badge.ended{ background:rgba(100,116,139,.12); color:#475569; }
    .summary-badge.suspended{ background:rgba(217,119,6,.12); color:#b45309; }
    .summary-table{
        width:100%; margin-bottom:0; border-collapse:collapse;
    }
    .summary-table thead th{
        border-bottom:1px solid var(--shp-border); font-size:.68rem; color:#64748b;
        background:var(--card,#fff); padding:.52rem .62rem; white-space:nowrap; text-align:left;
    }
    body[data-theme="dark"] .summary-table thead th{ background:rgba(15,23,42,.98); color:#9ca3af; }
    .summary-table tbody td{
        vertical-align:middle; border-top:1px solid rgba(148,163,184,.16); padding:.52rem .62rem; font-size:.78rem;
    }
    body[data-theme="dark"] .summary-table tbody td{ border-top-color:rgba(51,65,85,.84); }
    .promo-badge{
        display:inline-flex; align-items:center; gap:.3rem; border-radius:999px; padding:.15rem .55rem;
        font-size:.68rem; font-weight:800; white-space:nowrap;
    }
    .promo-ongoing{ background:rgba(22,163,74,.12); color:#15803d; }
    .promo-upcoming{ background:rgba(37,99,235,.10); color:#1d4ed8; }
    .promo-ended{ background:rgba(100,116,139,.12); color:#475569; }
    .promo-suspended{ background:rgba(217,119,6,.12); color:#b45309; }
    .summary-empty{
        text-align:center; color:var(--shp-muted); font-size:.85rem; padding:2.4rem 1rem;
    }
    @media (max-width: 768px){
        .summary-kpis{ grid-template-columns:repeat(2,minmax(0,1fr)); }
        .summary-store-grid{ grid-template-columns:1fr; }
    }
</style>
@endpush

@section('content')
@php($initialFilters = $filters ?? ['store_id' => 'all', 'status' => 'all', 'date_from' => now()->subDays(29)->toDateString(), 'date_to' => now()->toDateString()])

<div class="summary-wrap">
    <div class="summary-shell">
        <div class="summary-hero">
            <div class="summary-topline">
                <div>
                    <h1 class="summary-title">Summary Promosi</h1>
                    <div class="summary-sub">Ringkasan campaign diskon per toko, difilter berdasarkan status dan rentang jadwal.</div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('marketplace.promotions') }}" class="btn btn-pill btn-ship-outline">
                        <i class="bi bi-arrow-left me-1"></i>Ke Promosi
                    </a>
                    <button type="button" class="btn btn-pill btn-ship-outline" onclick="loadSummary()">
                        <i class="bi bi-arrow-repeat me-1"></i>Refresh
                    </button>
                </div>
            </div>

            <div class="summary-filters">
                <select id="summaryStoreSelect" class="form-select form-select-sm summary-filter" style="min-width:240px"></select>
                <select id="summaryStatusSelect" class="form-select form-select-sm summary-filter" style="min-width:170px">
                    <option value="all">Semua status</option>
                    <option value="ongoing">Ongoing</option>
                    <option value="upcoming">Upcoming</option>
                    <option value="ended">Ended</option>
                    <option value="suspended">Suspended</option>
                </select>
                <input id="summaryDateFrom" type="date" class="form-control form-control-sm summary-filter" style="min-width:160px">
                <input id="summaryDateTo" type="date" class="form-control form-control-sm summary-filter" style="min-width:160px">
                <button type="button" class="btn btn-sm btn-ship-primary" onclick="loadSummary()">Terapkan</button>
                <button type="button" class="btn btn-sm btn-ship-outline" onclick="resetSummaryFilters()">Reset</button>
            </div>
        </div>

        <div class="summary-kpis">
            <div class="summary-kpi"><div class="lbl">Toko</div><div class="val" id="kpiStores">—</div><div class="note">yang ikut diringkas</div></div>
            <div class="summary-kpi"><div class="lbl">Promo</div><div class="val" id="kpiPromotions">—</div><div class="note">total campaign</div></div>
            <div class="summary-kpi"><div class="lbl">Ongoing</div><div class="val" id="kpiOngoing">—</div><div class="note">sedang berjalan</div></div>
            <div class="summary-kpi"><div class="lbl">Upcoming</div><div class="val" id="kpiUpcoming">—</div><div class="note">menunggu mulai</div></div>
            <div class="summary-kpi"><div class="lbl">Ended</div><div class="val" id="kpiEnded">—</div><div class="note">sudah selesai</div></div>
            <div class="summary-kpi"><div class="lbl">Suspended</div><div class="val" id="kpiSuspended">—</div><div class="note">dijeda / suspended</div></div>
            <div class="summary-kpi"><div class="lbl">Items</div><div class="val" id="kpiItems">—</div><div class="note">total item promo</div></div>
            <div class="summary-kpi"><div class="lbl">Range</div><div class="val" id="kpiRange">—</div><div class="note">tanggal filter aktif</div></div>
        </div>

        <div id="summaryAlert" class="alert d-none mb-0" style="border-radius:12px;font-size:.85rem"></div>

        <div class="summary-store-grid" id="summaryStoreCards"></div>

        <div class="card card-main">
            <div class="table-responsive">
                <table class="summary-table">
                    <thead>
                        <tr>
                            <th>Toko</th>
                            <th>Promo</th>
                            <th>Status</th>
                            <th>Jadwal</th>
                            <th class="text-end">Items</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="summaryBody">
                        <tr><td colspan="6" class="summary-empty">Belum ada data.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const initialFilters = @json($initialFilters);
    const promotionsUrl = @json(route('marketplace.promotions'));
    const { api, esc } = window.mpHelpers;

    const state = {
        stores: [],
        rows: [],
        storeSummaries: [],
        totals: null,
    };

    const refs = {
        storeSelect: document.getElementById('summaryStoreSelect'),
        statusSelect: document.getElementById('summaryStatusSelect'),
        dateFrom: document.getElementById('summaryDateFrom'),
        dateTo: document.getElementById('summaryDateTo'),
        alert: document.getElementById('summaryAlert'),
        body: document.getElementById('summaryBody'),
        storeCards: document.getElementById('summaryStoreCards'),
        kpiStores: document.getElementById('kpiStores'),
        kpiPromotions: document.getElementById('kpiPromotions'),
        kpiOngoing: document.getElementById('kpiOngoing'),
        kpiUpcoming: document.getElementById('kpiUpcoming'),
        kpiEnded: document.getElementById('kpiEnded'),
        kpiSuspended: document.getElementById('kpiSuspended'),
        kpiItems: document.getElementById('kpiItems'),
        kpiRange: document.getElementById('kpiRange'),
    };

    function toast(message, type = 'success') {
        const el = document.createElement('div');
        el.className = `alert alert-${type === 'error' ? 'danger' : type} shadow`;
        el.style.position = 'fixed';
        el.style.right = '16px';
        el.style.bottom = '16px';
        el.style.zIndex = '9999';
        el.style.maxWidth = '420px';
        el.style.margin = '0';
        el.textContent = message;
        document.body.appendChild(el);
        setTimeout(() => {
            el.style.opacity = '0';
            el.style.transition = 'opacity .25s ease';
            setTimeout(() => el.remove(), 250);
        }, 2600);
    }

    function setAlert(message, type = 'info') {
        refs.alert.className = `alert alert-${type === 'error' ? 'danger' : type} mb-0`;
        refs.alert.textContent = message;
        refs.alert.classList.remove('d-none');
    }

    function clearAlert() {
        refs.alert.classList.add('d-none');
        refs.alert.textContent = '';
    }

    function fmtInt(value) {
        return Number(value || 0).toLocaleString('id-ID');
    }

    function statusClass(status) {
        const key = String(status || '').toLowerCase();
        if (key === 'ongoing') return 'promo-ongoing';
        if (key === 'upcoming') return 'promo-upcoming';
        if (key === 'ended') return 'promo-ended';
        if (key === 'suspended') return 'promo-suspended';
        return 'promo-ended';
    }

    function formatStoreLabel(store) {
        if (!store) return '—';
        return store.channel ? `${store.name} • ${store.channel.name}` : store.name;
    }

    function renderStoreOptions() {
        const selected = initialFilters.store_id ? String(initialFilters.store_id) : 'all';
        refs.storeSelect.innerHTML = [
            `<option value="all">Semua toko</option>`,
            ...state.stores.map((store) => `<option value="${store.id}">${esc(formatStoreLabel(store))}</option>`),
        ].join('');
        refs.storeSelect.value = selected;
    }

    function renderKpis() {
        const totals = state.totals || {};
        refs.kpiStores.textContent = fmtInt(totals.stores);
        refs.kpiPromotions.textContent = fmtInt(totals.promotions);
        refs.kpiOngoing.textContent = fmtInt(totals.ongoing);
        refs.kpiUpcoming.textContent = fmtInt(totals.upcoming);
        refs.kpiEnded.textContent = fmtInt(totals.ended);
        refs.kpiSuspended.textContent = fmtInt(totals.suspended);
        refs.kpiItems.textContent = fmtInt(totals.items);
        refs.kpiRange.textContent = `${refs.dateFrom.value || '—'} → ${refs.dateTo.value || '—'}`;
    }

    function renderStoreCards() {
        const summaries = state.storeSummaries || [];
        if (!summaries.length) {
            refs.storeCards.innerHTML = '';
            return;
        }

        refs.storeCards.innerHTML = summaries.map((summary) => {
            const store = summary.store || {};
            const link = `${promotionsUrl}?store_id=${encodeURIComponent(store.id || '')}&status=all`;
            return `
                <div class="summary-store-card">
                    <div class="summary-store-head">
                        <div>
                            <div class="summary-store-name">${esc(summary.store_label || formatStoreLabel(store))}</div>
                            <div class="summary-store-meta">
                                ${fmtInt(summary.promotions)} promo • ${fmtInt(summary.items)} items
                            </div>
                        </div>
                        <a href="${link}" class="btn btn-sm btn-ship-outline">Buka List</a>
                    </div>
                    <div class="summary-badges">
                        <span class="summary-badge ongoing">Ongoing ${fmtInt(summary.ongoing)}</span>
                        <span class="summary-badge upcoming">Upcoming ${fmtInt(summary.upcoming)}</span>
                        <span class="summary-badge ended">Ended ${fmtInt(summary.ended)}</span>
                        <span class="summary-badge suspended">Suspended ${fmtInt(summary.suspended)}</span>
                    </div>
                    <div class="summary-store-meta mt-2">
                        Mulai terdekat: <strong>${esc(summary.next_start_label || '—')}</strong><br>
                        Selesai terjauh: <strong>${esc(summary.next_end_label || '—')}</strong>
                    </div>
                </div>
            `;
        }).join('');
    }

    function renderRows() {
        const rows = state.rows || [];
        if (!rows.length) {
            refs.body.innerHTML = `<tr><td colspan="6" class="summary-empty">Tidak ada campaign yang cocok dengan filter ini.</td></tr>`;
            return;
        }

        refs.body.innerHTML = rows.map((row) => {
            const store = row.store || {};
            const link = `${promotionsUrl}?store_id=${encodeURIComponent(store.id || '')}&status=${encodeURIComponent(row.status_key || 'all')}`;
            return `
                <tr>
                    <td>
                        <div class="fw-semibold">${esc(row.store_label || formatStoreLabel(store))}</div>
                        <div class="summary-store-meta">ID toko: ${esc(String(store.id || '-'))}</div>
                    </td>
                    <td>
                        <div class="fw-semibold">${esc(row.discount_name || '-')}</div>
                        <div class="summary-store-meta">Campaign #${esc(String(row.discount_id || '-'))}</div>
                    </td>
                    <td><span class="promo-badge ${statusClass(row.status_key)}">${esc(row.status_label || row.status_key || '-')}</span></td>
                    <td>
                        <div>${esc(row.schedule_label || '—')}</div>
                        <div class="summary-store-meta">Mulai: ${esc(row.start_label || '—')}</div>
                    </td>
                    <td class="text-end">${fmtInt(row.item_count)}</td>
                    <td class="text-end">
                        <a href="${link}" class="btn btn-sm btn-outline-primary">Buka List</a>
                    </td>
                </tr>
            `;
        }).join('');
    }

    async function loadStores() {
        const data = await api('/api/marketplace/stores');
        state.stores = Array.isArray(data) ? data.filter((store) => {
            const code = String(store?.channel?.code || '').toLowerCase();
            return store?.is_active !== false && (code === 'shopee' || code === 'shp');
        }) : [];
        renderStoreOptions();
    }

    async function loadSummary() {
        clearAlert();
        const params = new URLSearchParams({
            store_id: refs.storeSelect.value || 'all',
            status: refs.statusSelect.value || 'all',
            date_from: refs.dateFrom.value || '',
            date_to: refs.dateTo.value || '',
        });

        refs.body.innerHTML = `<tr><td colspan="6" class="summary-empty">Memuat ringkasan promosi...</td></tr>`;
        refs.storeCards.innerHTML = '';

        try {
            const res = await api(`/api/marketplace/promotions/summary?${params.toString()}`);
            state.rows = Array.isArray(res.rows) ? res.rows : [];
            state.storeSummaries = Array.isArray(res.store_summaries) ? res.store_summaries : [];
            state.totals = res.totals || {};
            renderKpis();
            renderStoreCards();
            renderRows();
        } catch (err) {
            state.rows = [];
            state.storeSummaries = [];
            state.totals = null;
            renderKpis();
            refs.body.innerHTML = `<tr><td colspan="6" class="summary-empty text-danger">${esc(err.message || 'Gagal memuat summary promosi')}</td></tr>`;
            setAlert(err.message || 'Gagal memuat summary promosi', 'danger');
        }
    }

    function resetSummaryFilters() {
        refs.storeSelect.value = initialFilters.store_id || 'all';
        refs.statusSelect.value = initialFilters.status || 'all';
        refs.dateFrom.value = initialFilters.date_from || '';
        refs.dateTo.value = initialFilters.date_to || '';
        loadSummary();
    }

    refs.storeSelect.addEventListener('change', loadSummary);
    refs.statusSelect.addEventListener('change', loadSummary);
    refs.dateFrom.addEventListener('change', loadSummary);
    refs.dateTo.addEventListener('change', loadSummary);

    window.loadSummary = loadSummary;
    window.resetSummaryFilters = resetSummaryFilters;
    window.__promoSummaryApp = { loadSummary, resetSummaryFilters };

    document.addEventListener('DOMContentLoaded', async () => {
        try {
            refs.statusSelect.value = initialFilters.status || 'all';
            refs.dateFrom.value = initialFilters.date_from || '';
            refs.dateTo.value = initialFilters.date_to || '';
            await loadStores();
            await loadSummary();
        } catch (err) {
            toast(err.message || 'Gagal inisialisasi summary promosi', 'danger');
        }
    });
})();
</script>
@endpush
