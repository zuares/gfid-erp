@extends('layouts.app')
@section('title', 'Marketplace • Analytics')

@include('marketplace._shared')

@push('head')
@include('dashboard.partials._styles')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

    .an-shell { display:grid; gap:1rem; }
    .an-toolbar { display:flex; align-items:end; justify-content:space-between; gap:.75rem; flex-wrap:wrap; }
    .an-toolbar-controls { display:flex; gap:.5rem; align-items:end; flex-wrap:wrap; }
    .an-field label { display:block; color:#64748b; font-size:.68rem; font-weight:850; margin:0 0 .25rem; }
    .an-field input, .an-field select { min-height:38px; border:1px solid rgba(15,23,42,.12); border-radius:12px; padding:.45rem .7rem; background:#fff; color:#0f172a; font-size:.78rem; font-weight:700; }
    .an-field input { min-width:190px; }
    .an-btn { min-height:38px; border:1px solid rgba(15,23,42,.1); border-radius:12px; padding:.45rem .8rem; background:#fff; color:#0f172a; font-size:.76rem; font-weight:850; cursor:pointer; }
    .an-btn:hover { background:#f8fafc; }
    .an-btn-dark { background:#0f172a; border-color:#0f172a; color:#fff; }
    .an-btn-dark:hover { background:#1e293b; color:#fff; }
    .an-sync-note { color:#94a3b8; font-size:.7rem; font-weight:700; margin-top:.3rem; }
    .an-kpis { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:.7rem; }
    .an-kpi { min-height:112px; border:1px solid rgba(15,23,42,.08); border-radius:18px; background:#fff; padding:1rem; display:flex; flex-direction:column; justify-content:space-between; box-shadow:0 8px 22px rgba(15,23,42,.035); }
    .an-kpi.primary { background:#0f172a; color:#fff; border-color:#0f172a; }
    .an-kpi-label { color:#64748b; font-size:.67rem; font-weight:850; text-transform:uppercase; letter-spacing:.06em; }
    .an-kpi.primary .an-kpi-label { color:#94a3b8; }
    .an-kpi-value { color:#0f172a; font-size:1.35rem; font-weight:950; letter-spacing:-.035em; line-height:1.1; }
    .an-kpi.primary .an-kpi-value { color:#fff; }
    .an-kpi-note { color:#94a3b8; font-size:.69rem; font-weight:700; }
    .an-kpi-note.good { color:#16a34a; }
    .an-kpi-note.bad { color:#dc2626; }
    .an-grid-main { display:grid; grid-template-columns:minmax(0,1.6fr) minmax(280px,.9fr); gap:1rem; }
    .an-grid-secondary { display:grid; grid-template-columns:minmax(0,1.2fr) minmax(280px,.8fr); gap:1rem; }
    .an-card { min-width:0; border:1px solid var(--gf-border,#e5e7eb); border-radius:20px; background:#fff; box-shadow:0 8px 24px rgba(15,23,42,.035); overflow:hidden; }
    .an-card-head { padding:1rem 1.15rem .75rem; display:flex; justify-content:space-between; align-items:start; gap:.75rem; }
    .an-card-title { color:#0f172a; font-size:.9rem; font-weight:950; }
    .an-card-sub { color:#94a3b8; font-size:.7rem; font-weight:700; margin-top:.2rem; }
    .an-card-body { padding:0 1.15rem 1.15rem; }
    .an-chart { min-height:250px; position:relative; padding:1rem 0 .35rem; }
    .an-chart-grid { position:absolute; inset:1rem 0 2rem; display:flex; flex-direction:column; justify-content:space-between; pointer-events:none; }
    .an-chart-grid span { border-top:1px dashed #e2e8f0; width:100%; }
    .an-chart-svg { width:100%; height:220px; position:relative; z-index:1; overflow:visible; }
    .an-chart-axis { display:flex; justify-content:space-between; color:#94a3b8; font-size:.64rem; font-weight:750; padding:0 .1rem; }
    .an-legend { display:flex; gap:.85rem; color:#64748b; font-size:.68rem; font-weight:750; }
    .an-legend i { display:inline-block; width:8px; height:8px; border-radius:99px; margin-right:.3rem; background:#0f172a; }
    .an-legend i.green { background:#16a34a; }
    .an-legend i.blue { background:#2563eb; }
    .an-legend i.slate { background:#94a3b8; }
    .an-legend i.amber { background:#f59e0b; }
    .an-list { display:grid; gap:.3rem; }
    .an-list-row { display:grid; grid-template-columns:minmax(0,1fr) auto; align-items:center; gap:.8rem; padding:.68rem 0; border-bottom:1px solid #f1f5f9; }
    .an-list-row:last-child { border-bottom:0; }
    .an-list-main { min-width:0; }
    .an-list-name { color:#0f172a; font-size:.76rem; font-weight:850; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .an-list-meta { color:#94a3b8; font-size:.65rem; font-weight:700; margin-top:.15rem; }
    .an-list-value { text-align:right; color:#0f172a; font-size:.75rem; font-weight:900; }
    .an-bar { height:6px; margin-top:.38rem; border-radius:999px; background:#f1f5f9; overflow:hidden; }
    .an-bar > span { display:block; height:100%; border-radius:inherit; background:#0f172a; }
    .an-bar.green > span { background:#16a34a; }
    .an-table-wrap { overflow:auto; }
    .an-table { width:100%; border-collapse:collapse; min-width:560px; }
    .an-table th { padding:.55rem .5rem; text-align:left; border-bottom:1px solid #e2e8f0; color:#94a3b8; font-size:.64rem; font-weight:850; text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; }
    .an-table td { padding:.68rem .5rem; border-bottom:1px solid #f1f5f9; color:#334155; font-size:.73rem; font-weight:700; vertical-align:middle; }
    .an-table th:not(:first-child), .an-table td:not(:first-child) { text-align:right; }
    .an-table tr:last-child td { border-bottom:0; }
    .an-rank { width:24px; height:24px; display:inline-grid; place-items:center; border-radius:8px; background:#f1f5f9; color:#64748b; font-size:.65rem; font-weight:950; }
    .an-product { display:inline-flex; align-items:center; gap:.55rem; min-width:180px; text-align:left; }
    .an-product-copy { min-width:0; }
    .an-product-name { color:#0f172a; font-size:.74rem; font-weight:850; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:250px; }
    .an-product-sku { color:#94a3b8; font-size:.63rem; font-weight:700; margin-top:.1rem; }
    .an-dot { width:8px; height:8px; border-radius:99px; background:#16a34a; flex:0 0 auto; }
    .an-dot.red { background:#ef4444; }
    .an-funnel { display:flex; flex-direction:column; gap:.55rem; }
    .an-funnel-row { display:grid; grid-template-columns:90px 1fr auto; align-items:center; gap:.6rem; font-size:.7rem; font-weight:800; color:#475569; }
    .an-funnel-track { height:28px; border-radius:8px; background:#f1f5f9; overflow:hidden; }
    .an-funnel-track span { display:block; height:100%; border-radius:inherit; background:#16a34a; }
    .an-funnel-row:nth-child(2) .an-funnel-track span { background:#86efac; }
    .an-funnel-row:nth-child(3) .an-funnel-track span { background:#facc15; }
    .an-funnel-row:nth-child(4) .an-funnel-track span { background:#fda4af; }
    .an-funnel-value { color:#0f172a; text-align:right; white-space:nowrap; }
    .an-costs { display:grid; gap:.72rem; }
    .an-cost-row { display:grid; grid-template-columns:1fr auto; gap:.5rem; font-size:.73rem; color:#64748b; font-weight:750; }
    .an-cost-row strong { color:#0f172a; font-weight:900; }
    .an-empty { padding:1.6rem 0; text-align:center; color:#94a3b8; font-size:.75rem; font-weight:750; }
    .an-error { padding:.8rem .9rem; border:1px solid #fecaca; border-radius:12px; background:#fef2f2; color:#b91c1c; font-size:.73rem; font-weight:750; }
    @media (max-width: 760px) { .an-grid-main, .an-grid-secondary { grid-template-columns:1fr; } .an-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); } .an-kpi-value { font-size:1.12rem; } .an-field input { min-width:150px; } }
    @media (max-width: 420px) { .an-kpis { grid-template-columns:1fr 1fr; gap:.45rem; } .an-kpi { padding:.72rem; min-height:100px; } .an-toolbar, .an-toolbar-controls { align-items:stretch; } .an-field, .an-field input, .an-btn { width:100%; } }

    /* Selaras dengan Ads Dashboard: header flat, panel rapat, dan KPI beraksen. */
    body { font-family:'Inter', sans-serif !important; }
    .an-page { max-width:1040px; width:100%; min-width:0; margin-inline:auto; padding-inline:.75rem; }
    .an-shell { gap:.85rem; }
    .an-hero { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-inline:-.75rem; padding:.4rem .7rem; border-bottom:1px solid var(--dsh-border); background:var(--card,#fff); }
    .an-hero-copy { min-width:0; }
    .an-hero-eyebrow { color:var(--dsh-muted); font-size:.66rem; font-weight:800; margin-bottom:.1rem; }
    .an-hero-title { color:var(--text,#0f172a); font-size:.98rem; font-weight:750; letter-spacing:-.03em; line-height:1.25; }
    .an-hero-sub { color:var(--dsh-muted); font-size:.75rem; margin-top:.2rem; }
    .an-hero .an-sync-note { color:var(--dsh-muted); font-size:.66rem; margin-top:.3rem; }
    .an-hero-controls { display:flex; align-items:center; gap:.3rem; flex-wrap:wrap; }
    .an-hero .an-field { display:flex; align-items:center; gap:.35rem; }
    .an-hero .an-field label { color:var(--dsh-muted); margin:0; font-size:.68rem; font-weight:700; }
    .an-hero .an-field input, .an-hero .an-field select { min-height:32px; border:1px solid var(--dsh-border-strong); border-radius:7px; padding:.35rem .6rem; background:var(--card,#fff); color:var(--text,#0f172a); font-size:.74rem; font-weight:700; }
    .an-hero .an-field input { min-width:175px; }
    .an-tabs-wrap { overflow-x:auto; padding:.25rem 0 .35rem; border-bottom:1px solid var(--dsh-border); scrollbar-width:none; }
    .an-tabs-wrap::-webkit-scrollbar { display:none; }
    .an-tabs { display:inline-flex; gap:.35rem; padding:.3rem; border:1px solid rgba(148,163,184,.18); border-radius:16px; background:linear-gradient(180deg,rgba(248,250,252,.96),rgba(241,245,249,.92)); box-shadow:0 10px 22px rgba(15,23,42,.05); }
    .an-tab { border:0; border-radius:12px; padding:.62rem .9rem; background:transparent; color:var(--dsh-muted); font-size:.74rem; font-weight:900; cursor:pointer; white-space:nowrap; }
    .an-tab:hover { color:var(--text,#0f172a); background:rgba(255,255,255,.8); }
    .an-tab.active { background:var(--dsh-accent,#334155); color:#fff; box-shadow:0 8px 16px rgba(15,23,42,.16); }
    .an-tab-pane.is-hidden { display:none; }
    .an-btn { min-height:32px; border-radius:7px; padding:.35rem .7rem; font-size:.7rem; font-weight:700; }
    .an-btn-dark { background:var(--dsh-accent); border-color:var(--dsh-accent); color:#fff; }
    .an-btn-dark:hover { background:var(--dsh-accent-2); color:#fff; }
    .an-kpis { grid-template-columns:repeat(6,minmax(0,1fr)); gap:.6rem; }
    .an-kpi { min-height:95px; padding:.85rem 1rem; border:1px solid var(--dsh-border); border-radius:14px; background:var(--card,#fff); box-shadow:0 10px 22px rgba(15,23,42,.05); position:relative; overflow:hidden; }
    .an-kpi::before { content:''; position:absolute; inset:0 auto auto 0; width:100%; height:3px; background:linear-gradient(90deg,#64748b,#94a3b8); }
    .an-kpi:nth-child(2)::before { background:linear-gradient(90deg,#2563eb,#38bdf8); }
    .an-kpi:nth-child(3)::before { background:linear-gradient(90deg,#64748b,#94a3b8); }
    .an-kpi:nth-child(4)::before { background:linear-gradient(90deg,#16a34a,#22c55e); }
    .an-kpi:nth-child(5)::before { background:linear-gradient(90deg,#b45309,#f59e0b); }
    .an-kpi:nth-child(6)::before { background:linear-gradient(90deg,#16a34a,#22c55e); }
    .an-kpi.primary { background:var(--card,#fff); color:var(--text,#0f172a); border-color:var(--dsh-border); }
    .an-kpi.primary .an-kpi-label, .an-kpi.primary .an-kpi-value { color:var(--text,#0f172a); }
    .an-kpi-label { color:var(--dsh-muted); font-size:.62rem; font-weight:900; letter-spacing:.08em; }
    .an-kpi-value { color:var(--text,#0f172a); font-size:1.28rem; font-weight:950; line-height:1; letter-spacing:-.03em; margin-top:.34rem; }
    .an-kpi-note { color:var(--dsh-muted); font-size:.62rem; font-weight:700; border-top:1px dashed var(--dsh-border); padding-top:.55rem; margin-top:.55rem; }
    .an-card { border:1px solid var(--dsh-border); border-radius:14px; background:var(--card,#fff); box-shadow:0 10px 22px rgba(15,23,42,.05); }
    .an-card-head { padding:.65rem .85rem; border-bottom:1px solid var(--dsh-border); }
    .an-card-title { color:var(--text,#0f172a); font-size:.88rem; font-weight:700; }
    .an-card-sub { color:var(--dsh-muted); font-size:.7rem; font-weight:500; }
    .an-card-body { padding:.2rem .3rem; }
    .an-table { border-collapse:separate; border-spacing:0; min-width:540px; }
    .an-table th { background:var(--hero-bg,#f8fafc); border-bottom:1px solid var(--dsh-border); color:var(--dsh-muted); padding:.52rem .62rem; font-size:.68rem; font-weight:700; text-transform:none; letter-spacing:0; position:sticky; top:0; z-index:2; }
    .an-table td { border-bottom:1px solid var(--dsh-border); color:var(--text,#0f172a); padding:.52rem .62rem; font-size:.75rem; }
    .an-table tr:hover td { background:rgba(148,163,184,.05); }
    .an-table-subline { display:block; margin-top:.18rem; color:var(--dsh-muted); font-size:.62rem; font-weight:600; }
    .an-product-table { min-width:760px; }
    .an-product-table th, .an-product-table td { padding:.44rem .5rem; }
    .an-product-table .an-product { min-width:155px; gap:.45rem; }
    .an-product-table .an-product-name { max-width:180px; font-size:.7rem; }
    .an-product-table .an-product-sku { font-size:.59rem; }
    .an-product-table .an-table-subline { margin-top:.08rem; font-size:.58rem; }
    .an-product-table .an-product-value { white-space:nowrap; font-weight:850; }
    .an-product-table th:first-child, .an-product-table td:first-child { width:32px; text-align:center; }
    .an-product-table th:nth-child(2), .an-product-table td:nth-child(2) { text-align:left; }
    .an-list-row { padding:.55rem .6rem; border-bottom:1px solid var(--dsh-border); }
    .an-list-name { color:var(--text,#0f172a); font-size:.78rem; font-weight:650; }
    .an-list-meta, .an-product-sku { color:var(--dsh-muted); }
    .an-empty { color:var(--dsh-muted); }
    body[data-theme="dark"] .an-hero { background:var(--card,#0f172a); border-bottom-color:var(--dsh-border); }
    body[data-theme="dark"] .an-card, body[data-theme="dark"] .an-kpi { background:var(--card,#1e293b); }
    body[data-theme="dark"] .an-hero .an-field input, body[data-theme="dark"] .an-hero .an-field select { background:#0f172a; color:#e2e8f0; }
    @media (min-width:1200px) { .an-page { min-width:1040px; } }
    @media (max-width:760px) { .an-page { padding-inline:.5rem; } .an-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); } .an-hero { margin-inline:-.5rem; padding:.6rem .75rem; } .an-hero-controls { align-items:stretch; width:100%; } .an-hero .an-field, .an-hero .an-field input, .an-hero .an-field select, .an-hero .an-btn { width:100%; } .an-hero .an-field { display:block; } }
    body[data-theme="dark"] .an-tabs { background:linear-gradient(180deg,rgba(15,23,42,.96),rgba(30,41,59,.92)); border-color:rgba(51,65,85,.85); }
    body[data-theme="dark"] .an-tab:hover { color:#e2e8f0; background:rgba(255,255,255,.06); }
    @media (min-width:761px) {
        .an-kpi { min-width:0; padding:.75rem .8rem; }
        .an-kpi-label { font-size:.58rem; letter-spacing:.06em; }
        .an-kpi-value { font-size:1.06rem; font-weight:800; letter-spacing:-.02em; }
        .an-kpi-note { font-size:.58rem; padding-top:.42rem; margin-top:.42rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    }
</style>
@endpush

@section('content')
<div class="dash py-3">
    <div class="page-wrap ads-shell an-page">
        <div class="an-hero">
            <div class="an-hero-copy">
                <div class="an-hero-eyebrow">Marketplace · Analytics</div>
                <div class="an-hero-title"><i class="bi bi-bar-chart-line me-1"></i>Analytics Marketplace</div>
                <div class="an-sync-note" id="anSyncNote">Memuat data marketplace…</div>
            </div>
            <div class="an-hero-controls">
                <div class="an-field"><label for="anStore">Toko</label><select id="anStore"><option value="">Semua toko</option></select></div>
                <div class="an-field"><label for="anDateRange">Periode</label><input type="text" id="anDateRange" autocomplete="off" value="{{ $filters['date_from'] }} — {{ $filters['date_to'] }}"></div>
                <div class="an-field"><label for="anCompare">Bandingkan</label><select id="anCompare"><option value="prev_period">Periode lalu</option><option value="prev_month">Bulan lalu</option><option value="prev_year">Tahun lalu</option></select></div>
                <input type="hidden" id="anDateFrom" value="{{ $filters['date_from'] }}"><input type="hidden" id="anDateTo" value="{{ $filters['date_to'] }}">
                <button class="an-btn an-btn-dark" id="anRefresh" type="button">↻ Refresh</button>
            </div>
        </div>

        <div class="an-shell">
          <div class="an-tabs-wrap">
            <div class="an-tabs" id="analyticsTabs" role="tablist" aria-label="Navigasi analytics">
                <button class="an-tab active" type="button" data-an-tab="summary" role="tab" aria-selected="true"><i class="bi bi-grid-1x2 me-1"></i>Ringkasan</button>
                <button class="an-tab" type="button" data-an-tab="stores" role="tab" aria-selected="false"><i class="bi bi-shop me-1"></i>Toko &amp; Biaya</button>
                <button class="an-tab" type="button" data-an-tab="products" role="tab" aria-selected="false"><i class="bi bi-box-seam me-1"></i>Produk</button>
            </div>
          </div>
          <div class="an-kpis">
            <div class="an-kpi primary"><span class="an-kpi-label">Total Order</span><strong class="an-kpi-value" id="kpiOrders">—</strong><span class="an-kpi-note" id="kpiOrdersNote">—</span></div>
            <div class="an-kpi"><span class="an-kpi-label">Omset</span><strong class="an-kpi-value" id="kpiRevenue">—</strong><span class="an-kpi-note" id="kpiRevenueNote">setelah diskon</span></div>
            <div class="an-kpi"><span class="an-kpi-label">Biaya Platform</span><strong class="an-kpi-value" id="kpiAdminFee">—</strong><span class="an-kpi-note">21%</span></div>
            <div class="an-kpi"><span class="an-kpi-label">Laba Kotor</span><strong class="an-kpi-value" id="kpiGrossProfit">—</strong><span class="an-kpi-note" id="kpiHppNote">Total HPP: —</span></div>
            <div class="an-kpi"><span class="an-kpi-label">Biaya Iklan</span><strong class="an-kpi-value" id="kpiAdCost">—</strong><span class="an-kpi-note">Ads Dashboard</span></div>
            <div class="an-kpi"><span class="an-kpi-label">Laba Bersih</span><strong class="an-kpi-value" id="kpiNetProfit">—</strong><span class="an-kpi-note" id="kpiNetProfitNote">kotor − HPP − iklan</span></div>
          </div>

        <div class="an-grid-main an-tab-pane" data-an-pane="summary">
            <section class="an-card"><div class="an-card-head"><div><div class="an-card-title">Omzet vs laba harian</div><div class="an-card-sub" id="chartCompareNote">Perbandingan periode terpilih vs periode sebelumnya</div></div><div class="an-legend"><span><i class="blue"></i>Omzet kini</span><span><i class="slate"></i>Omzet lalu</span><span><i class="green"></i>Laba kini</span><span><i class="amber"></i>Laba lalu</span></div></div><div class="an-card-body"><div class="an-chart" id="revenueChart"><div class="an-empty">Memuat grafik…</div></div></div></section>
            <section class="an-card"><div class="an-card-head"><div><div class="an-card-title">Funnel penjualan</div></div></div><div class="an-card-body"><div class="an-funnel" id="salesFunnel"><div class="an-empty">Memuat…</div></div></div></section>
        </div>

        <div class="an-grid-secondary an-tab-pane is-hidden" data-an-pane="stores">
            <section class="an-card"><div class="an-card-head"><div><div class="an-card-title">Performa per toko</div></div></div><div class="an-card-body"><div class="an-table-wrap"><table class="an-table"><thead><tr><th>Toko</th><th>Order</th><th>Selesai</th><th>Cancel</th><th>Omzet marketplace</th><th>Laba Bersih</th></tr></thead><tbody id="storeBody"><tr><td colspan="6"><div class="an-empty">Memuat…</div></td></tr></tbody></table></div></div></section>
            <section class="an-card"><div class="an-card-head"><div><div class="an-card-title">Biaya marketplace</div></div></div><div class="an-card-body"><div class="an-costs" id="costBody"><div class="an-empty">Memuat…</div></div></div></section>
        </div>

        <div class="an-grid-secondary an-tab-pane is-hidden" data-an-pane="products">
            <section class="an-card"><div class="an-card-head"><div><div class="an-card-title">Semua penjualan produk</div></div></div><div class="an-card-body"><div class="an-table-wrap"><table class="an-table an-product-table"><thead><tr><th>#</th><th>Kategori</th><th>Qty</th><th>Omset</th><th>HPP</th><th>Iklan</th><th>Laba Kotor</th><th>Laba Bersih</th></tr></thead><tbody id="bestProductBody"><tr><td colspan="8"><div class="an-empty">Memuat…</div></td></tr></tbody></table></div></div></section>
            <section class="an-card"><div class="an-card-head"><div><div class="an-card-title">Produk perlu perhatian</div></div></div><div class="an-card-body"><div class="an-list" id="worstProductBody"><div class="an-empty">Memuat…</div></div></div></section>
        </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const { api, fmtRp, esc } = window.mpHelpers;
    const $ = id => document.getElementById(id);
    let orders = [], previousOrders = [];
    let adSpendBySku = {};
    const from = () => $('anDateFrom').value;
    const to = () => $('anDateTo').value;
    const n = v => Number.parseFloat(v || 0) || 0;
    const status = o => String(o.order_status || o.status || '').toUpperCase();
    const completed = o => ['COMPLETED', 'DELIVERED', 'CLOSED'].includes(status(o));
    const money = v => fmtRp(Math.round(v || 0));
    const pct = (a,b) => b ? (a / b * 100).toFixed(1) + '%' : '0%';
    const skuKey = value => String(value || '').trim().toUpperCase();
    const dateKey = o => { const d = new Date(o.ordered_at || o.created_at); return Number.isNaN(d.getTime()) ? null : d.toISOString().slice(0,10); };
    const selectedStore = () => $('anStore').value;
    const compareParam = new URLSearchParams(location.search).get('compare_mode');
    if (['prev_period','prev_month','prev_year'].includes(compareParam)) $('anCompare').value = compareParam;
    const inRange = o => { const d = dateKey(o); return !d || (d >= from() && d <= to()); };
    const filterRows = (source, start = from(), end = to()) => source.filter(o => { const d = dateKey(o); return (!d || (d >= start && d <= end)) && (!selectedStore() || String(o.store_id || o.store?.id) === selectedStore()); });
    const filtered = () => filterRows(orders);
    const ymd = d => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
    const shiftCalendar = (date, months = 0, years = 0) => {
        const day = date.getDate(), shifted = new Date(date);
        shifted.setDate(1);
        shifted.setMonth(shifted.getMonth() + months);
        shifted.setFullYear(shifted.getFullYear() + years);
        shifted.setDate(Math.min(day, new Date(shifted.getFullYear(), shifted.getMonth() + 1, 0).getDate()));
        return shifted;
    };
    const previousRange = () => {
        const start = new Date(`${from()}T00:00:00`), end = new Date(`${to()}T00:00:00`);
        if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) return { from: from(), to: to() };
        if ($('anCompare')?.value === 'prev_month') return { from: ymd(shiftCalendar(start, -1)), to: ymd(shiftCalendar(end, -1)) };
        if ($('anCompare')?.value === 'prev_year') return { from: ymd(shiftCalendar(start, 0, -1)), to: ymd(shiftCalendar(end, 0, -1)) };
        const days = Math.max(1, Math.round((end - start) / 86400000) + 1);
        const prevEnd = new Date(start); prevEnd.setDate(prevEnd.getDate() - 1);
        const prevStart = new Date(prevEnd); prevStart.setDate(prevStart.getDate() - days + 1);
        return { from: ymd(prevStart), to: ymd(prevEnd) };
    };
    const discountedLine = i => {
        const qty = n(i.model_quantity_purchased || i.quantity_purchased || i.qty || i.active_qty || 0);
        const discounted = n(i.model_discounted_price || i.discounted_price || i.price_after_discount);
        if (discounted > 0) return discounted * (qty || 1);
        if (n(i.line_net_amount) > 0) return n(i.line_net_amount);
        if (n(i.line_gross_amount) > 0) return n(i.line_gross_amount);
        const price = n(i.price || i.model_original_price || i.original_price);
        return price * (qty || 1);
    };
    const marketplaceSales = o => {
        const sourceItems = Array.isArray(o.items) ? o.items : [];
        const itemTotal = sourceItems.reduce((sum, i) => sum + discountedLine(i), 0);
        return itemTotal > 0 ? itemTotal : n(o.total_amount || o.total_paid_customer);
    };
    const itemRevenue = i => discountedLine(i);
    const itemUnitHpp = i => n(i.hpp_snapshot) > 0 ? n(i.hpp_snapshot) : (n(i.hpp_unit_snapshot) > 0 ? n(i.hpp_unit_snapshot) : n(i.internal_hpp));
    const itemCost = i => n(i.hpp_total_snapshot) > 0 ? n(i.hpp_total_snapshot) : itemUnitHpp(i) * n(i.qty || 0);
    const revenue = o => Math.max(0, marketplaceSales(o) - n(o.voucher_discount));
    const feeRates = { admin: 0.095, service: 0.115, affiliate: 0 };
    const estimatedFees = value => ({ admin: value * feeRates.admin, service: value * feeRates.service, affiliate: value * feeRates.affiliate, total: value * (feeRates.admin + feeRates.service + feeRates.affiliate) });
    const profit = o => { const items = o.items || []; const sales = revenue(o); const cost = items.reduce((s,i) => s + itemCost(i), 0); return sales - estimatedFees(sales).total - cost; };

    function setLoading(message) { $('anSyncNote').textContent = message; }
    function fillStores() {
        const current = selectedStore();
        const unique = new Map();
        orders.forEach(o => { const id = o.store_id || o.store?.id; if (id && !unique.has(String(id))) unique.set(String(id), o.store?.name || `Toko #${id}`); });
        $('anStore').innerHTML = '<option value="">Semua toko</option>' + [...unique.entries()].sort((a,b) => a[1].localeCompare(b[1])).map(([id,name]) => `<option value="${esc(id)}">${esc(name)}</option>`).join('');
        $('anStore').value = unique.has(current) ? current : '';
    }
    function renderKpis(rows) {
        const saleRows = rows.filter(o => !['CANCELLED','BATAL','RETURNED'].includes(status(o)));
        const net = saleRows.reduce((s,o) => s + revenue(o), 0), hpp = saleRows.reduce((sum,o) => sum + (o.items || []).reduce((itemSum,i) => itemSum + itemCost(i), 0), 0);
        const platformFee = net * (feeRates.admin + feeRates.service), grossProfit = net - platformFee, adCost = n(window.__analyticsAdCost || 0), netProfit = grossProfit - hpp - adCost;
        $('kpiOrders').textContent = rows.length.toLocaleString('id-ID');
        $('kpiRevenue').textContent = money(net); $('kpiAdminFee').textContent = money(platformFee); $('kpiAdCost').textContent = money(window.__analyticsAdCost || 0); $('kpiGrossProfit').textContent = money(grossProfit); $('kpiNetProfit').textContent = money(netProfit);
        $('kpiRevenueNote').textContent = rows.length ? `${saleRows.length} order` : '—'; $('kpiOrdersNote').textContent = `${from()} — ${to()}`; $('kpiHppNote').textContent = `Total HPP: ${money(hpp)}`; $('kpiNetProfitNote').textContent = 'kotor − HPP − iklan';
    }
    function chartPoints(rows) {
        const map = {};
        rows.filter(completed).forEach(o => { const k = dateKey(o); if (!k) return; if (!map[k]) map[k] = {rev:0, prof:0}; map[k].rev += revenue(o); map[k].prof += profit(o); });
        return Object.entries(map).sort((a,b) => a[0].localeCompare(b[0])).map(([date, values]) => ({date, ...values}));
    }
    function renderChart(rows, previousRows) {
        const points = chartPoints(rows), previousPoints = chartPoints(previousRows || []);
        if (!points.length && !previousPoints.length) { $('revenueChart').innerHTML = '<div class="an-empty">Belum ada order selesai untuk dibandingkan.</div>'; return; }
        const max = Math.max(...points.concat(previousPoints).flatMap(v => [v.rev,v.prof]), 1), w = 720, h = 210, pad = 12, slots = Math.max(points.length, previousPoints.length, 1);
        const line = (key, series) => series.map((v,i) => { const x = pad + (i * (w-pad*2) / Math.max(slots-1,1)); const y = h-pad - (Math.max(v[key],0) / max) * (h-pad*2); return `${x},${y}`; }).join(' ');
        const labels = points.map(v => `<span>${new Date(v.date+'T00:00:00').toLocaleDateString('id-ID',{day:'2-digit',month:'short'})}</span>`).join('');
        const total = (series, key) => series.reduce((sum, point) => sum + point[key], 0);
        const change = (current, previous) => previous > 0 ? `${((current - previous) / previous * 100).toFixed(1)}%` : (current > 0 ? 'baru' : '0%');
        const modeLabel = {prev_period:'periode lalu',prev_month:'bulan lalu',prev_year:'tahun lalu'}[$('anCompare')?.value] || 'periode lalu';
        $('chartCompareNote').textContent = `vs ${modeLabel} · omzet ${change(total(points,'rev'),total(previousPoints,'rev'))} · laba ${change(total(points,'prof'),total(previousPoints,'prof'))}`;
        $('revenueChart').innerHTML = `<div class="an-chart-grid"><span></span><span></span><span></span><span></span></div><svg class="an-chart-svg" viewBox="0 0 ${w} ${h}" preserveAspectRatio="none" role="img" aria-label="Grafik omzet dan laba dengan perbandingan periode sebelumnya"><polyline fill="none" stroke="#94a3b8" stroke-width="2" stroke-dasharray="6 5" stroke-linecap="round" stroke-linejoin="round" points="${line('rev',previousPoints)}"/><polyline fill="none" stroke="#f59e0b" stroke-width="2" stroke-dasharray="6 5" stroke-linecap="round" stroke-linejoin="round" points="${line('prof',previousPoints)}"/><polyline fill="none" stroke="#2563eb" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" points="${line('rev',points)}"/><polyline fill="none" stroke="#16a34a" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" points="${line('prof',points)}"/></svg><div class="an-chart-axis">${labels}</div>`;
    }
    function renderFunnel(rows) {
        const done = rows.filter(completed), rev = done.reduce((s,o)=>s+revenue(o),0), grossProfit = done.reduce((s,o)=>s+profit(o),0), netProfit = grossProfit - n(window.__analyticsAdCost || 0), max = Math.max(rev,1);
        const data = [['Order masuk', rows.length, rows.length],['Order selesai', done.length, done.length],['Omzet marketplace', money(rev), rev],['Laba bersih estimasi', money(Math.max(netProfit,0)), Math.max(netProfit,0)]];
        $('salesFunnel').innerHTML = data.map(([label,value,amount]) => `<div class="an-funnel-row"><span>${label}</span><div class="an-funnel-track"><span style="width:${Math.max(5,Math.round(amount / max * 100))}%"></span></div><strong class="an-funnel-value">${typeof value === 'number' ? value.toLocaleString('id-ID') : value}</strong></div>`).join('');
    }
    function renderStores(rows) {
        const map = {}; rows.forEach(o => { const id = String(o.store_id || o.store?.id || '0'); const s = map[id] ||= {name:o.store?.name || 'Tanpa toko',orders:0,saleOrders:0,done:0,cancel:0,rev:0,prof:0}; const cancelled = ['CANCELLED','BATAL','RETURNED'].includes(status(o)); s.orders++; if (!cancelled) { s.saleOrders++; s.rev += revenue(o); } if (completed(o)) { s.done++; s.prof += profit(o); } if (cancelled) s.cancel++; });
        const totalRevenue = Object.values(map).reduce((sum,s)=>sum+s.rev,0), totalAdCost = n(window.__analyticsAdCost || 0);
        const list = Object.values(map).map(s => {
            const adCost = totalRevenue > 0 ? totalAdCost * (s.rev / totalRevenue) : 0;
            return {...s, adCost, prof: s.prof - adCost};
        }).sort((a,b)=>b.rev-a.rev);
        $('storeBody').innerHTML = list.length ? list.map(s=>`<tr><td style="text-align:left;font-weight:850;color:#0f172a">${esc(s.name)}</td><td>${s.orders}</td><td>${s.done} <small style="color:#94a3b8">(${pct(s.done,s.orders)})</small></td><td style="color:${s.cancel?'#dc2626':'inherit'}">${s.cancel}</td><td style="font-weight:900">${money(s.rev)}<span class="an-table-subline">AOV ${money(s.saleOrders ? s.rev / s.saleOrders : 0)}</span></td><td style="font-weight:900;color:${s.prof>=0?'#15803d':'#dc2626'}">${money(s.prof)}<span class="an-table-subline">Iklan ${money(s.adCost)}</span></td></tr>`).join('') : '<tr><td colspan="6"><div class="an-empty">Belum ada data toko.</div></td></tr>';
    }
    function renderCosts(rows) {
        const saleRows = rows.filter(o => !['CANCELLED','BATAL','RETURNED'].includes(status(o)));
        const totalRevenue = saleRows.reduce((sum,o) => sum + revenue(o), 0);
        const costs = estimatedFees(totalRevenue);
        const rowsHtml = [
            ['Biaya administrasi', costs.admin, '9,5%'],
            ['Biaya layanan', costs.service, '11,5%'],
            ['Komisi affiliate', costs.affiliate, '0%'],
        ].map(([label,value,rate]) => `<div class="an-cost-row"><span>${label} <small style="color:#94a3b8">(${rate})</small></span><strong>${money(value)}</strong><div class="an-bar" style="grid-column:1/-1"><span style="width:${totalRevenue ? (value / totalRevenue * 100) : 0}%"></span></div></div>`).join('');
        $('costBody').innerHTML = `${rowsHtml}<div style="display:flex;justify-content:space-between;gap:.5rem;border-top:1px solid #e2e8f0;padding-top:.7rem;margin-top:.15rem;color:#0f172a;font-size:.75rem;font-weight:900"><span>Total estimasi biaya (21%)</span><span>${money(costs.total)}</span></div>`;
    }
    function products(rows) {
        const map = {};
        rows.filter(o => !['CANCELLED','BATAL','RETURNED'].includes(status(o))).forEach(o => {
            const storedItems = Array.isArray(o.items) ? o.items : [];
            storedItems.forEach(i => {
                const marketplaceSku = i.model_sku || i.item_sku || i.external_sku || '—';
                const internalSku = i.internal_sku || '';
                const category = i.internal_category || i.internal_category_name || 'Tanpa kategori';
                const key = skuKey(category);
                const qty = n(i.model_quantity_purchased || i.quantity_purchased || i.qty || i.active_qty);
                const cost = itemCost(i);
                const p = map[key] ||= {category,qty:0,rev:0,cost:0,unitHppWeighted:0,unitHppQty:0,missingQty:0,skuRevenue:{},itemKeys:{}};
                const lineRevenue = discountedLine(i);
                p.qty += qty;
                p.rev += lineRevenue;
                p.cost += cost;
                p.skuRevenue[skuKey(marketplaceSku)] = (p.skuRevenue[skuKey(marketplaceSku)] || 0) + lineRevenue;
                p.itemKeys[skuKey(internalSku || marketplaceSku)] = true;
                if (cost > 0 && qty > 0) { p.unitHppWeighted += itemUnitHpp(i) * qty; p.unitHppQty += qty; }
                else p.missingQty += qty || 1;
            });
        });
        const list = Object.values(map);
        const totalRevenue = list.reduce((sum, p) => sum + p.rev, 0);
        const revenueBySku = list.reduce((carry, p) => {
            Object.entries(p.skuRevenue).forEach(([key, value]) => { carry[key] = (carry[key] || 0) + value; });
            return carry;
        }, {});
        const directAdSpend = Object.entries(adSpendBySku).reduce((sum, [key, value]) => {
            return sum + (revenueBySku[key] > 0 ? n(value) : 0);
        }, 0);
        const adScale = directAdSpend > 0 ? Math.min(1, n(window.__analyticsAdCost || 0) / directAdSpend) : 0;
        const remainingAdSpend = Math.max(0, n(window.__analyticsAdCost || 0) - directAdSpend * adScale);

        return list.map(p => {
            const grossProfit = p.rev - estimatedFees(p.rev).total;
            const directSkuSpend = Object.entries(p.skuRevenue).reduce((sum, [key]) => sum + n(adSpendBySku[key]) * adScale, 0);
            const allocatedAdSpend = directSkuSpend + (totalRevenue > 0 ? remainingAdSpend * (p.rev / totalRevenue) : 0);
            return {
                ...p,
                itemCount: Object.keys(p.itemKeys).length,
                unitHpp: p.unitHppQty > 0 ? p.unitHppWeighted / p.unitHppQty : 0,
                missing: p.missingQty > 0,
                grossProfit,
                adCost: allocatedAdSpend,
                profit: grossProfit - p.cost - allocatedAdSpend,
            };
        });
    }
    function renderProducts(rows) {
        const list=products(rows), sales=[...list].sort((a,b)=>b.rev-a.rev || b.qty-a.qty), worst=[...list].sort((a,b)=>a.profit-b.profit).slice(0,6); const max=Math.max(...sales.map(p=>Math.abs(p.profit)),1);
        $('bestProductBody').innerHTML=sales.length?sales.map((p,i)=>`<tr><td class="an-product-value">${i+1}</td><td><span class="an-product"><span class="an-product-copy"><span class="an-product-name">${esc(p.category)}</span><span class="an-product-sku">${p.itemCount} item</span></span></span></td><td>${p.qty}</td><td class="an-product-value">${money(p.rev)}<span class="an-table-subline">AOV ${money(p.qty ? p.rev / p.qty : 0)}</span></td><td class="an-product-value">${money(p.cost)}<span class="an-table-subline">${p.unitHpp > 0 ? money(p.unitHpp) + '/unit' : '—'}</span></td><td class="an-product-value" style="color:#b45309">${money(p.adCost)}</td><td class="an-product-value" style="color:#15803d">${money(p.grossProfit)}<span class="an-table-subline">${pct(p.grossProfit,p.rev)}</span></td><td class="an-product-value" style="color:${p.profit>=0?'#15803d':'#dc2626'}">${money(p.profit)}</td></tr>`).join(''):'<tr><td colspan="8"><div class="an-empty">Belum ada penjualan produk.</div></td></tr>';
        $('worstProductBody').innerHTML=worst.length?worst.map(p=>`<div class="an-list-row"><div class="an-list-main"><div class="an-list-name"><span class="an-dot ${p.profit<0?'red':''}"></span> ${esc(p.category)}</div><div class="an-list-meta">${p.itemCount} item · ${p.qty} pcs${p.missing?' · HPP belum lengkap':''}</div><div class="an-bar"><span style="width:${Math.max(4,Math.min(100,Math.round(Math.abs(p.profit)/max*100)))}%;background:${p.profit<0?'#ef4444':'#facc15'}"></span></div></div><div class="an-list-value" style="color:${p.profit<0?'#dc2626':'#a16207'}">${money(p.profit)}</div></div>`).join(''):'<div class="an-empty">Belum ada produk untuk ditinjau.</div>';
    }
    function render() {
        const rows=filtered(), prev=previousRange(); renderKpis(rows); renderChart(rows, filterRows(previousOrders, prev.from, prev.to)); renderFunnel(rows); renderStores(rows); renderCosts(rows);
        const latest = orders.map(dateKey).filter(Boolean).sort().pop();
        const latestLabel = latest ? new Date(latest+'T00:00:00').toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'}) : 'belum ada';
        $('anSyncNote').textContent = rows.length ? `${rows.length.toLocaleString('id-ID')} order · ${from()} sampai ${to()}` : `Tidak ada order pada periode ini · data terakhir ${latestLabel}`;
        renderProducts(rows);
    }
    const normalize = payload => {
        const source = Array.isArray(payload) ? payload : (Array.isArray(payload?.data) ? payload.data : []);
        return source
            .filter(order => order && typeof order === 'object')
            .map(order => ({
                ...order,
                store: order.store && typeof order.store === 'object' ? order.store : null,
                items: Array.isArray(order.items) ? order.items.filter(item => item && typeof item === 'object') : [],
            }));
    };
    async function load() { setLoading('Mengambil data order & biaya iklan…'); $('anRefresh').disabled=true; try { const storeQuery = selectedStore() ? `&store_id=${encodeURIComponent(selectedStore())}` : ''; const prev = previousRange(); const [ordersResult, previousResult, adResult] = await Promise.allSettled([api(`/api/marketplace/analytics-orders?date_from=${encodeURIComponent(from())}&date_to=${encodeURIComponent(to())}&limit=3000`), api(`/api/marketplace/analytics-orders?date_from=${encodeURIComponent(prev.from)}&date_to=${encodeURIComponent(prev.to)}&limit=3000`), api(`/api/marketplace/analytics-ad-cost?date_from=${encodeURIComponent(from())}&date_to=${encodeURIComponent(to())}${storeQuery}`)]); if (ordersResult.status === 'rejected') throw ordersResult.reason; orders=normalize(ordersResult.value); previousOrders=previousResult.status === 'fulfilled' ? normalize(previousResult.value) : []; window.__analyticsAdCost = adResult.status === 'fulfilled' ? Number(adResult.value?.spend || 0) : 0; adSpendBySku = adResult.status === 'fulfilled' && adResult.value?.ad_spend_by_sku && typeof adResult.value.ad_spend_by_sku === 'object' ? adResult.value.ad_spend_by_sku : {}; if (adResult.status === 'rejected') console.warn('Analytics ad cost unavailable', adResult.reason); fillStores(); render(); } catch(e) { console.error('Analytics load failed', e); $('anSyncNote').textContent='Data gagal dimuat'; $('storeBody').innerHTML='<tr><td colspan="6"><div class="an-error">Tidak dapat memuat data analytics. Coba refresh atau periksa koneksi sinkronisasi marketplace.</div></td></tr>'; $('bestProductBody').innerHTML='<tr><td colspan="8"><div class="an-error">Tidak dapat memuat data analytics. Coba refresh atau periksa koneksi sinkronisasi marketplace.</div></td></tr>'; } finally { $('anRefresh').disabled=false; } }
    function activateTab(name) {
        document.querySelectorAll('[data-an-tab]').forEach(button => {
            const active = button.dataset.anTab === name;
            button.classList.toggle('active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        document.querySelectorAll('[data-an-pane]').forEach(pane => pane.classList.toggle('is-hidden', pane.dataset.anPane !== name));
    }
    document.querySelectorAll('[data-an-tab]').forEach(button => button.addEventListener('click', () => activateTab(button.dataset.anTab)));
    const syncUrl = () => { const params = new URLSearchParams({date_from:from(),date_to:to(),compare_mode:$('anCompare').value}); history.replaceState(null,'',location.pathname+'?'+params.toString()); };
    $('anRefresh').addEventListener('click',load); $('anStore').addEventListener('change',load); $('anCompare').addEventListener('change',()=>{syncUrl();load();});
    if (window.flatpickr) flatpickr($('anDateRange'),{mode:'range',dateFormat:'Y-m-d',defaultDate:[from(),to()],onChange(dates){if(dates.length===2){$('anDateFrom').value=dates[0].toISOString().slice(0,10);$('anDateTo').value=dates[1].toISOString().slice(0,10);$('anDateRange').value=from()+' — '+to();syncUrl();load();}}});
    load();
})();
</script>
@endpush
