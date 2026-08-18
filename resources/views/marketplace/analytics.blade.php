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
    .an-kpis { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.7rem; }
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
    .an-enterprise-grid { display:grid; grid-template-columns:minmax(0,1.35fr) minmax(320px,.65fr); gap:.85rem; }
    .an-enterprise-card { min-width:0; border:1px solid var(--dsh-border); border-radius:14px; background:var(--card,#fff); box-shadow:0 10px 22px rgba(15,23,42,.05); overflow:hidden; }
    .an-enterprise-head { display:flex; align-items:flex-start; justify-content:space-between; gap:.7rem; padding:.75rem .85rem; border-bottom:1px solid var(--dsh-border); }
    .an-enterprise-title { color:var(--text,#0f172a); font-size:.84rem; font-weight:800; }
    .an-enterprise-sub { color:var(--dsh-muted); font-size:.66rem; font-weight:600; margin-top:.18rem; }
    .an-enterprise-body { padding:.8rem .85rem; }
    .an-pulse-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.55rem; }
    .an-pulse { min-width:0; padding:.62rem .68rem; border:1px solid var(--dsh-border); border-radius:10px; background:var(--hero-bg,#f8fafc); }
    .an-pulse-label { color:var(--dsh-muted); font-size:.59rem; font-weight:800; text-transform:uppercase; letter-spacing:.05em; }
    .an-pulse-value { color:var(--text,#0f172a); font-size:1rem; font-weight:900; margin-top:.22rem; }
    .an-pulse-note { color:var(--dsh-muted); font-size:.62rem; font-weight:650; margin-top:.18rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .an-pulse-note.good { color:#15803d; } .an-pulse-note.bad { color:#b91c1c; } .an-pulse-note.warn { color:#a16207; }
    .an-health-list { display:grid; gap:.65rem; }
    .an-health-row { display:grid; grid-template-columns:minmax(110px,.6fr) minmax(0,1fr) auto; align-items:center; gap:.55rem; color:var(--text,#0f172a); font-size:.67rem; font-weight:750; }
    .an-health-row small { color:var(--dsh-muted); font-size:.61rem; font-weight:650; }
    .an-health-track { height:7px; border-radius:99px; background:#e2e8f0; overflow:hidden; }
    .an-health-track span { display:block; height:100%; border-radius:inherit; background:#16a34a; }
    .an-health-track span.warn { background:#f59e0b; } .an-health-track span.bad { background:#ef4444; }
    .an-health-score { display:inline-flex; align-items:center; gap:.35rem; padding:.3rem .5rem; border-radius:8px; background:#dcfce7; color:#166534; font-size:.63rem; font-weight:850; white-space:nowrap; }
    .an-health-score.warn { background:#fef3c7; color:#92400e; } .an-health-score.bad { background:#fee2e2; color:#991b1b; }
    .an-alerts { display:grid; gap:.45rem; }
    .an-alert { display:grid; grid-template-columns:22px minmax(0,1fr) auto; align-items:center; gap:.55rem; padding:.55rem .6rem; border:1px solid #e2e8f0; border-radius:10px; background:#fff; }
    .an-alert-icon { width:22px; height:22px; display:grid; place-items:center; border-radius:7px; background:#e0f2fe; color:#0369a1; font-size:.7rem; }
    .an-alert.warn { border-color:#fde68a; background:#fffbeb; } .an-alert.warn .an-alert-icon { background:#fef3c7; color:#a16207; }
    .an-alert.bad { border-color:#fecaca; background:#fef2f2; } .an-alert.bad .an-alert-icon { background:#fee2e2; color:#b91c1c; }
    .an-alert-title { color:#0f172a; font-size:.68rem; font-weight:850; } .an-alert-note { color:#64748b; font-size:.61rem; font-weight:650; margin-top:.12rem; }
    .an-alert-action { color:#475569; font-size:.61rem; font-weight:850; white-space:nowrap; }
    .an-contribution-grid { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr); gap:.85rem; }
    .an-contribution-list { display:grid; gap:.42rem; }
    .an-contribution-row { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:.6rem; align-items:center; }
    .an-contribution-name { color:var(--text,#0f172a); font-size:.68rem; font-weight:750; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .an-contribution-meta { color:var(--dsh-muted); font-size:.59rem; font-weight:650; margin-top:.1rem; }
    .an-contribution-value { color:var(--text,#0f172a); font-size:.67rem; font-weight:900; text-align:right; }
    .an-contribution-bar { height:5px; margin-top:.28rem; border-radius:99px; background:#eef2f7; overflow:hidden; }
    .an-contribution-bar span { display:block; height:100%; border-radius:inherit; background:#2563eb; }
    .an-product-toolbar { display:flex; align-items:center; justify-content:flex-end; gap:.35rem; flex-wrap:wrap; }
    .an-product-toolbar input, .an-product-toolbar select { min-height:28px; border:1px solid var(--dsh-border-strong); border-radius:7px; padding:.25rem .5rem; background:var(--card,#fff); color:var(--text,#0f172a); font-size:.66rem; font-weight:700; }
    .an-product-toolbar input { width:170px; }
    .an-product-toolbar select { width:135px; }
    .an-product-link { display:block; max-width:100%; color:var(--text,#0f172a); text-decoration:none; }
    .an-product-link:hover { color:#2563eb; text-decoration:underline; }
    body[data-theme="dark"] .an-enterprise-card, body[data-theme="dark"] .an-pulse, body[data-theme="dark"] .an-alert { background:var(--card,#1e293b); }
    body[data-theme="dark"] .an-pulse, body[data-theme="dark"] .an-alert { border-color:var(--dsh-border); }
    .an-empty { padding:1.6rem 0; text-align:center; color:#94a3b8; font-size:.75rem; font-weight:750; }
    .an-error { padding:.8rem .9rem; border:1px solid #fecaca; border-radius:12px; background:#fef2f2; color:#b91c1c; font-size:.73rem; font-weight:750; }
    @media (max-width: 760px) { .an-grid-main, .an-grid-secondary, .an-enterprise-grid, .an-contribution-grid { grid-template-columns:1fr; } .an-pulse-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .an-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); } .an-kpi-value { font-size:1.12rem; } .an-field input { min-width:150px; } .an-product-toolbar { width:100%; justify-content:flex-start; margin-top:.35rem; } .an-product-toolbar input, .an-product-toolbar select { flex:1 1 140px; width:auto; } }
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
    .an-kpis { grid-template-columns:repeat(4,minmax(0,1fr)); gap:.6rem; }
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
                <div class="an-field"><label for="anCompare">Bandingkan</label><select id="anCompare"><option value="prev_period" @selected(($filters['compare_mode'] ?? 'prev_period') === 'prev_period')>Periode lalu</option><option value="prev_month" @selected(($filters['compare_mode'] ?? '') === 'prev_month')>Bulan lalu</option><option value="prev_year" @selected(($filters['compare_mode'] ?? '') === 'prev_year')>Tahun lalu</option></select></div>
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
            <div class="an-kpi primary"><span class="an-kpi-label">Total Order</span><strong class="an-kpi-value" id="kpiOrders">—</strong><span class="an-kpi-note" id="kpiOrdersNote">semua status kecuali batal</span></div>
            <div class="an-kpi"><span class="an-kpi-label">Omzet Marketplace</span><strong class="an-kpi-value" id="kpiRevenue">—</strong><span class="an-kpi-note" id="kpiRevenueNote">GMV · non-batal</span></div>
            <div class="an-kpi"><span class="an-kpi-label">Omzet Cair</span><strong class="an-kpi-value" id="kpiPayout">—</strong><span class="an-kpi-note" id="kpiPayoutNote">settlement complete</span></div>
            <div class="an-kpi"><span class="an-kpi-label">Estimasi Profit</span><strong class="an-kpi-value" id="kpiEstimatedProfit">—</strong><span class="an-kpi-note" id="kpiEstimatedProfitNote">margin estimasi</span></div>
            <div class="an-kpi"><span class="an-kpi-label">Fee Marketplace (estimasi)</span><strong class="an-kpi-value" id="kpiAdminFee">—</strong><span class="an-kpi-note" id="kpiAdminFeeNote">21% dari GMV</span></div>
            <div class="an-kpi"><span class="an-kpi-label">Laba Kotor</span><strong class="an-kpi-value" id="kpiGrossProfit">—</strong><span class="an-kpi-note" id="kpiHppNote">HPP: —</span></div>
            <div class="an-kpi"><span class="an-kpi-label">Biaya Iklan</span><strong class="an-kpi-value" id="kpiAdCost">—</strong><span class="an-kpi-note" id="kpiAdCostNote">dari Ads Daily</span></div>
            <div class="an-kpi"><span class="an-kpi-label">Laba Operasional</span><strong class="an-kpi-value" id="kpiNetProfit">—</strong><span class="an-kpi-note" id="kpiNetProfitNote">payout − HPP − iklan</span></div>
          </div>

          <div class="an-enterprise-grid an-tab-pane" data-an-pane="summary">
            <section class="an-enterprise-card">
                <div class="an-enterprise-head"><div><div class="an-enterprise-title">Executive pulse</div><div class="an-enterprise-sub">Perubahan metrik kunci terhadap periode pembanding</div></div><span class="an-health-score" id="anOverallScore">—</span></div>
                <div class="an-enterprise-body"><div class="an-pulse-grid" id="anPulseGrid"><div class="an-empty">Memuat insight…</div></div></div>
            </section>
            <section class="an-enterprise-card">
                <div class="an-enterprise-head"><div><div class="an-enterprise-title">Business health</div><div class="an-enterprise-sub">Kualitas data dan eksekusi order</div></div></div>
                <div class="an-enterprise-body"><div class="an-health-list" id="anHealthList"><div class="an-empty">Memuat health score…</div></div></div>
            </section>
          </div>

          <section class="an-enterprise-card an-tab-pane" data-an-pane="summary">
              <div class="an-enterprise-head"><div><div class="an-enterprise-title">Management attention</div><div class="an-enterprise-sub">Prioritas yang perlu ditindaklanjuti pada periode ini</div></div></div>
              <div class="an-enterprise-body"><div class="an-alerts" id="anAlerts"><div class="an-empty">Memuat alert…</div></div></div>
          </section>

        <div class="an-grid-main an-tab-pane" data-an-pane="summary">
            <section class="an-card"><div class="an-card-head"><div><div class="an-card-title">GMV vs laba tervalidasi harian</div><div class="an-card-sub" id="chartCompareNote">GMV mencakup semua status selain batal; laba hanya dari order verified</div></div><div class="an-legend"><span><i class="blue"></i>GMV kini</span><span><i class="slate"></i>GMV lalu</span><span><i class="green"></i>Laba kini</span><span><i class="amber"></i>Laba lalu</span></div></div><div class="an-card-body"><div class="an-chart" id="revenueChart"><div class="an-empty">Memuat grafik…</div></div></div></section>
            <section class="an-card"><div class="an-card-head"><div><div class="an-card-title">Kesehatan order &amp; keuangan</div><div class="an-card-sub">Order operasional dan profit yang sudah tervalidasi</div></div></div><div class="an-card-body"><div class="an-funnel" id="salesFunnel"><div class="an-empty">Memuat…</div></div></div></section>
        </div>

        <div class="an-contribution-grid an-tab-pane" data-an-pane="summary">
            <section class="an-enterprise-card"><div class="an-enterprise-head"><div><div class="an-enterprise-title">Kontribusi toko</div><div class="an-enterprise-sub">Toko dengan kontribusi omzet terbesar</div></div></div><div class="an-enterprise-body"><div class="an-contribution-list" id="anTopStores"><div class="an-empty">Memuat…</div></div></div></section>
            <section class="an-enterprise-card"><div class="an-enterprise-head"><div><div class="an-enterprise-title">Unit economics</div><div class="an-enterprise-sub">Berbasis omzet yang sudah cair</div></div></div><div class="an-enterprise-body"><div class="an-contribution-list" id="anEconomics"><div class="an-empty">Memuat…</div></div></div></section>
        </div>

        <div class="an-enterprise-grid an-tab-pane is-hidden" data-an-pane="stores">
            <section class="an-enterprise-card"><div class="an-enterprise-head"><div><div class="an-enterprise-title">Store performance snapshot</div><div class="an-enterprise-sub">Ranking toko berdasarkan omzet dan profit operasional</div></div></div><div class="an-enterprise-body"><div class="an-pulse-grid" id="anStorePulse"><div class="an-empty">Memuat…</div></div></div></section>
            <section class="an-enterprise-card"><div class="an-enterprise-head"><div><div class="an-enterprise-title">Cost efficiency</div><div class="an-enterprise-sub">Beban biaya dan payout per toko</div></div></div><div class="an-enterprise-body"><div class="an-health-list" id="anStoreCostPulse"><div class="an-empty">Memuat…</div></div></div></section>
        </div>

        <div class="an-grid-secondary an-tab-pane is-hidden" data-an-pane="stores">
            <section class="an-card"><div class="an-card-head"><div><div class="an-card-title">Performa per toko</div></div></div><div class="an-card-body"><div class="an-table-wrap"><table class="an-table"><thead><tr><th>Toko</th><th>Order</th><th>Selesai</th><th>Cancel</th><th>Omzet marketplace</th><th>Laba Bersih</th></tr></thead><tbody id="storeBody"><tr><td colspan="6"><div class="an-empty">Memuat…</div></td></tr></tbody></table></div></div></section>
            <section class="an-card"><div class="an-card-head"><div><div class="an-card-title">Biaya marketplace</div><div class="an-card-sub">Fee diestimasi 21% dari GMV; biaya aktual settlement tetap tersedia untuk audit</div></div></div><div class="an-card-body"><div class="an-costs" id="costBody"><div class="an-empty">Memuat…</div></div></div></section>
        </div>

        <div class="an-enterprise-grid an-tab-pane is-hidden" data-an-pane="products">
            <section class="an-enterprise-card"><div class="an-enterprise-head"><div><div class="an-enterprise-title">Product portfolio</div><div class="an-enterprise-sub">Kualitas portofolio berdasarkan profit tervalidasi</div></div></div><div class="an-enterprise-body"><div class="an-pulse-grid" id="anProductPulse"><div class="an-empty">Menunggu tab Produk dibuka…</div></div></div></section>
            <section class="an-enterprise-card"><div class="an-enterprise-head"><div><div class="an-enterprise-title">Product focus</div><div class="an-enterprise-sub">Rekomendasi fokus berdasarkan data periode ini</div></div></div><div class="an-enterprise-body"><div class="an-alerts" id="anProductFocus"><div class="an-empty">Menunggu data produk…</div></div></div></section>
        </div>

        <div class="an-grid-secondary an-tab-pane is-hidden" data-an-pane="products">
            <section class="an-card"><div class="an-card-head"><div><div class="an-card-title">Semua penjualan produk</div><div class="an-card-sub">Hanya produk dari order dengan settlement dan HPP tervalidasi; iklan dialokasikan berdasar omzet produk</div></div><div class="an-product-toolbar"><input id="anProductSearch" type="search" placeholder="Cari produk / SKU…"><select id="anProductSort"><option value="gross_sales">Urutkan: Omzet</option><option value="operating_profit">Urutkan: Laba</option><option value="margin_pct">Urutkan: Margin</option><option value="qty">Urutkan: Qty</option></select></div></div><div class="an-card-body"><div class="an-table-wrap"><table class="an-table an-product-table"><thead><tr><th>#</th><th>Produk</th><th>Qty</th><th>Omzet</th><th>HPP</th><th>Iklan (alokasi)</th><th>Laba Kotor</th><th>Laba Operasional</th></tr></thead><tbody id="bestProductBody"><tr><td colspan="8"><div class="an-empty">Buka tab Produk untuk memuat detail.</div></td></tr></tbody></table></div></div></section>
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
    let summary = null;
    let stores = [];
    let orders = [];
    let productsLoaded = false;
    let productData = [];
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
    const productPageUrl = @json(route('marketplace.products'));
    const productUrl = product => `${productPageUrl}?search=${encodeURIComponent(product.sku || product.product_name || '')}`;
    const initialStore = @json($filters['store_id'] ?? '');
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
        const unique = new Map(stores
            .filter(store => store && store.id)
            .map(store => [String(store.id), store.name || `Toko #${store.id}`]));
        $('anStore').innerHTML = '<option value="">Semua toko</option>' + [...unique.entries()]
            .sort((a,b) => a[1].localeCompare(b[1]))
            .map(([id,name]) => `<option value="${esc(id)}">${esc(name)}</option>`)
            .join('');
        $('anStore').value = unique.has(current) ? current : '';
    }
    function renderKpis(rows) {
        const current = summary?.current || {};
        const adCost = Number(current.ad_cost ?? current.ads_spend ?? 0);
        const gmv = Number(current.gmv || 0);
        const cashPayout = Number(current.cash_payout ?? current.payout ?? 0);
        const estimatedProfit = Number(current.estimated_profit || 0);
        const actualFee = Number(current.cash_marketplace_fees ?? current.marketplace_fees_actual ?? 0);
        $('kpiOrders').textContent = Number(current.order_total || 0).toLocaleString('id-ID');
        $('kpiRevenue').textContent = money(gmv);
        $('kpiPayout').textContent = money(cashPayout);
        $('kpiEstimatedProfit').textContent = money(estimatedProfit);
        $('kpiAdminFee').textContent = money(current.marketplace_fee_estimate);
        $('kpiAdCost').textContent = money(adCost);
        $('kpiGrossProfit').textContent = money(current.gross_profit);
        $('kpiNetProfit').textContent = money(current.operating_profit);
        $('kpiRevenueNote').textContent = `${from()} — ${to()}`;
        $('kpiOrdersNote').textContent = `${Number(current.completed_count || 0).toLocaleString('id-ID')} selesai · ${Number(current.cancelled_count || 0).toLocaleString('id-ID')} batal`;
        $('kpiPayoutNote').textContent = `${Number(current.cash_order_count || 0).toLocaleString('id-ID')} cair · ${gmv > 0 ? (cashPayout / gmv * 100).toFixed(1) : '0.0'}% GMV`;
        $('kpiEstimatedProfitNote').textContent = `${Number(current.estimated_profit_margin || 0).toFixed(1)}% margin · estimasi`;
        $('kpiAdminFeeNote').textContent = `21% GMV · actual ${money(actualFee)}`;
        $('kpiAdCostNote').textContent = `${cashPayout > 0 ? (adCost / cashPayout * 100).toFixed(1) : '0.0'}% omzet cair`;
        $('kpiHppNote').textContent = `HPP: ${money(current.hpp)}`;
        $('kpiNetProfitNote').textContent = `${Number(current.profit_margin || 0).toFixed(1)}% margin · payout − HPP − iklan`;
    }
    function chartPoints(rows) {
        return (rows || []).map(row => ({
            date: row.date,
            rev: n(row.gmv || row.gross_sales),
            prof: n(row.operating_profit),
        }));
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
    function renderFunnel() {
        const current = summary?.current || {};
        const max = Math.max(Number(current.order_total || 0), 1);
        const data = [
            ['Order masuk', Number(current.order_total || 0), Number(current.order_total || 0)],
            ['Order selesai', Number(current.completed_count || 0), Number(current.completed_count || 0)],
            ['Order dibatalkan', Number(current.cancelled_count || 0), Number(current.cancelled_count || 0)],
            ['Laba operasional', money(current.operating_profit), Math.max(Number(current.operating_profit || 0), 0)],
        ];
        $('salesFunnel').innerHTML = data.map(([label,value,amount]) => `<div class="an-funnel-row"><span>${label}</span><div class="an-funnel-track"><span style="width:${Math.max(5,Math.round(amount / max * 100))}%"></span></div><strong class="an-funnel-value">${typeof value === 'number' ? value.toLocaleString('id-ID') : value}</strong></div>`).join('');
    }
    function renderStores() {
        const list = summary?.stores || [];
        const totalGross = list.reduce((sum, store) => sum + Number(store.gmv || 0), 0);
        const top = [...list].sort((a,b) => Number(b.gmv || 0) - Number(a.gmv || 0))[0];
        const bestMargin = [...list].sort((a,b) => Number(b.margin_pct || 0) - Number(a.margin_pct || 0))[0];
        $('anStorePulse').innerHTML = [
            ['Toko aktif', Number(list.length || 0).toLocaleString('id-ID'), 'dalam periode terpilih'],
            ['Kontributor utama', top?.store_name || '—', top ? `${(Number(top.gmv || 0) / Math.max(totalGross,1) * 100).toFixed(1)}% omzet` : '—'],
            ['Margin terbaik', bestMargin?.store_name || '—', bestMargin ? `${Number(bestMargin.margin_pct || 0).toFixed(1)}% margin` : '—'],
        ].map(([label,value,note]) => `<div class="an-pulse"><div class="an-pulse-label">${label}</div><div class="an-pulse-value" style="font-size:${String(value).length > 16 ? '.78rem' : '1rem'}">${esc(value)}</div><div class="an-pulse-note">${note}</div></div>`).join('');
        const storeBase = Math.max(totalGross, 1);
        $('anStoreCostPulse').innerHTML = [
            ['Payout rate', list.reduce((sum, store) => sum + Number(store.payout || 0), 0) / storeBase * 100],
            ['Fee rate (estimasi)', list.reduce((sum, store) => sum + Number(store.marketplace_fee_estimate || 0), 0) / storeBase * 100],
            ['HPP rate', list.reduce((sum, store) => sum + Number(store.hpp || 0), 0) / storeBase * 100],
            ['Ad rate', list.reduce((sum, store) => sum + Number(store.ad_cost || 0), 0) / storeBase * 100],
        ].map(([label,value]) => `<div class="an-health-row"><span>${label}<small>terhadap omzet</small></span><div class="an-health-track"><span style="width:${Math.min(100, Math.max(0, value))}%"></span></div><strong>${Number(value || 0).toFixed(1)}%</strong></div>`).join('');
        $('storeBody').innerHTML = list.length ? list.map(s=>`<tr><td style="text-align:left;font-weight:850;color:#0f172a">${esc(s.store_name || 'Tanpa toko')}</td><td>${Number(s.order_total || 0).toLocaleString('id-ID')}</td><td>${Number(s.completed_count || 0).toLocaleString('id-ID')} <small style="color:#94a3b8">(${pct(s.completed_count,s.order_total)})</small></td><td style="color:${s.cancelled_count?'#dc2626':'inherit'}">${Number(s.cancelled_count || 0).toLocaleString('id-ID')}</td><td style="font-weight:900">${money(s.gmv)}<span class="an-table-subline">AOV ${money(s.order_total ? s.gmv / s.order_total : 0)}</span></td><td style="font-weight:900;color:${s.operating_profit>=0?'#15803d':'#dc2626'}">${money(s.operating_profit)}<span class="an-table-subline">Profit verified · Iklan ${money(s.ad_cost)}</span></td></tr>`).join('') : '<tr><td colspan="6"><div class="an-empty">Belum ada data toko siap profit.</div></td></tr>';
    }
    function renderCosts() {
        const current = summary?.current || {};
        const base = Math.max(Number(current.gmv || current.gross_sales || 0), 1);
        const rows = [
            ['Fee marketplace (estimasi 21%)', current.marketplace_fee_estimate],
            ['Refund / adjustment', current.refund],
            ['Biaya iklan', current.ad_cost],
            ['HPP', current.hpp],
        ];
        $('costBody').innerHTML = rows.map(([label,value]) => `<div class="an-cost-row"><span>${label} <small style="color:#94a3b8">(${(Number(value || 0) / base * 100).toFixed(1)}%)</small></span><strong>${money(value)}</strong><div class="an-bar" style="grid-column:1/-1"><span style="width:${Math.min(100, Math.max(0, Number(value || 0) / base * 100))}%"></span></div></div>`).join('');
    }
    function delta(key) {
        const value = summary?.changes?.[key];
        if (value === null || typeof value === 'undefined') return { text: 'Baru', className: 'good' };
        const numeric = Number(value || 0);
        return { text: `${numeric > 0 ? '+' : ''}${numeric.toFixed(1)}% vs lalu`, className: numeric > 0 ? 'good' : (numeric < 0 ? 'bad' : '') };
    }
    function healthClass(value, inverse = false) {
        const score = inverse ? 100 - Number(value || 0) : Number(value || 0);
        return score >= 85 ? '' : (score >= 65 ? 'warn' : 'bad');
    }
    function renderEnterprise() {
        const current = summary?.current || {};
        const quality = summary?.quality || {};
        const cashPayout = Number(current.cash_payout ?? current.payout ?? 0);
        const actualCashFee = Number(current.cash_marketplace_fees ?? current.marketplace_fees_actual ?? 0);
        const total = Math.max(Number(quality.total || 0), 1);
        const readyRate = Number(quality.ready || 0) / total * 100;
        const topStores = [...(summary?.stores || [])].sort((a,b) => Number(b.gross_sales || 0) - Number(a.gross_sales || 0)).slice(0, 5);
        const pulse = [
            ['Omzet', money(current.gmv), delta('gmv')],
            ['Laba operasional', money(current.operating_profit), delta('operating_profit')],
            ['AOV', money(current.aov), delta('aov')],
        ];
        $('anPulseGrid').innerHTML = pulse.map(([label,value,change]) => `<div class="an-pulse"><div class="an-pulse-label">${label}</div><div class="an-pulse-value">${value}</div><div class="an-pulse-note ${change.className}">${change.text}</div></div>`).join('');
        const scoreClass = healthClass(readyRate);
        $('anOverallScore').className = `an-health-score ${scoreClass}`;
        $('anOverallScore').textContent = `Data ready ${readyRate.toFixed(0)}%`;
        const health = [
            ['Completion rate', Number(current.completion_rate || 0), false, `${Number(current.completed_count || 0).toLocaleString('id-ID')} selesai`],
            ['Cancellation', Number(current.cancel_rate || 0), true, `${Number(current.cancelled_count || 0).toLocaleString('id-ID')} dibatalkan`],
            ['Data readiness', readyRate, false, `${Number(quality.ready || 0).toLocaleString('id-ID')} siap profit`],
            ['Profit margin', Math.max(0, Number(current.profit_margin || 0)), false, `${Number(current.profit_margin || 0).toFixed(1)}% operasional`],
        ];
        $('anHealthList').innerHTML = health.map(([label,value,inverse,note]) => `<div class="an-health-row"><span>${label}<small>${note}</small></span><div class="an-health-track"><span class="${healthClass(value,inverse)}" style="width:${Math.min(100, Math.max(0, inverse ? 100 - value : value))}%"></span></div><strong>${Number(value || 0).toFixed(1)}%</strong></div>`).join('');
        const alerts = [];
        if (Number(quality.incomplete || 0) + Number(quality.unknown || 0) > 0) alerts.push(['warn','bi-clipboard2-x','Data finansial belum lengkap',`${(Number(quality.incomplete || 0) + Number(quality.unknown || 0)).toLocaleString('id-ID')} order belum siap dihitung profit`,'Audit data']);
        if (Number(current.cancel_rate || 0) >= 10) alerts.push(['bad','bi-x-octagon','Cancellation tinggi',`${Number(current.cancel_rate).toFixed(1)}% dari order masuk`,'Review operasional']);
        else if (Number(current.cancel_rate || 0) >= 5) alerts.push(['warn','bi-exclamation-triangle','Cancellation perlu dipantau',`${Number(current.cancel_rate).toFixed(1)}% dari order masuk`,'Monitor']);
        if (Number(current.profit_margin || 0) < 10) alerts.push(['bad','bi-graph-down-arrow','Margin operasional rendah',`Margin saat ini ${Number(current.profit_margin || 0).toFixed(1)}%`,'Review pricing']);
        if (Number(summary?.changes?.operating_profit || 0) < 0) alerts.push(['warn','bi-arrow-down-right','Laba turun dari periode lalu',`${delta('operating_profit').text}`,'Analisis biaya']);
        if (!alerts.length) alerts.push(['','bi-check2-circle','Tidak ada alert kritis','Performa dan kualitas data berada dalam batas aman','—']);
        $('anAlerts').innerHTML = alerts.map(([level,icon,title,note,action]) => `<div class="an-alert ${level}"><span class="an-alert-icon"><i class="bi ${icon}"></i></span><div><div class="an-alert-title">${title}</div><div class="an-alert-note">${note}</div></div><span class="an-alert-action">${action}</span></div>`).join('');
        const maxStore = Math.max(...topStores.map(store => Number(store.gmv || 0)), 1);
        $('anTopStores').innerHTML = topStores.length ? topStores.map(store => `<div class="an-contribution-row"><div><div class="an-contribution-name">${esc(store.store_name || 'Tanpa toko')}</div><div class="an-contribution-meta">${Number(store.order_total || 0).toLocaleString('id-ID')} order · profit verified ${money(store.operating_profit)}</div><div class="an-contribution-bar"><span style="width:${Math.max(4, Number(store.gmv || 0) / maxStore * 100)}%"></span></div></div><div class="an-contribution-value">${money(store.gmv)}</div></div>`).join('') : '<div class="an-empty">Belum ada kontribusi toko.</div>';
        const economics = [
            ['Omzet cair', cashPayout, 100, '#16a34a'],
            ['Fee marketplace estimasi · 21% total order', Number(current.marketplace_fee_estimate || 0), cashPayout ? Number(current.marketplace_fee_estimate || 0) / cashPayout * 100 : 0, '#f59e0b'],
            ['Fee marketplace actual · omzet cair', actualCashFee, cashPayout ? actualCashFee / cashPayout * 100 : 0, '#d97706'],
            ['HPP', Number(current.hpp || 0), cashPayout ? Number(current.hpp || 0) / cashPayout * 100 : 0, '#64748b'],
            ['Biaya iklan', Number(current.ad_cost || 0), cashPayout ? Number(current.ad_cost || 0) / cashPayout * 100 : 0, '#dc2626'],
        ];
        $('anEconomics').innerHTML = economics.map(([label,amount,rate,color]) => `<div class="an-contribution-row"><div><div class="an-contribution-name">${label}</div><div class="an-contribution-bar"><span style="width:${Math.min(100, Math.max(0, rate))}%;background:${color}"></span></div></div><div class="an-contribution-value">${money(amount)}<span class="an-table-subline">${rate.toFixed(1)}% dari cair</span></div></div>`).join('');
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
    function renderProductInsights(rows) {
        const list = rows || [];
        const negative = list.filter(product => Number(product.operating_profit || 0) < 0).length;
        const totalGross = list.reduce((sum, product) => sum + Number(product.gross_sales || 0), 0);
        const totalProfit = list.reduce((sum, product) => sum + Number(product.operating_profit || 0), 0);
        $('anProductPulse').innerHTML = [
            ['Produk tervalidasi', Number(list.length).toLocaleString('id-ID'), 'dengan HPP lengkap'],
            ['Produk rugi', Number(negative).toLocaleString('id-ID'), negative ? 'perlu review segera' : 'tidak ada'],
            ['Profit produk', money(totalProfit), `${totalGross > 0 ? (totalProfit / totalGross * 100).toFixed(1) : '0.0'}% margin`],
        ].map(([label,value,note]) => `<div class="an-pulse"><div class="an-pulse-label">${label}</div><div class="an-pulse-value">${esc(value)}</div><div class="an-pulse-note ${negative && label === 'Produk rugi' ? 'bad' : ''}">${note}</div></div>`).join('');
        const focus = [];
        const best = [...list].sort((a,b) => Number(b.operating_profit || 0) - Number(a.operating_profit || 0))[0];
        const worst = [...list].sort((a,b) => Number(a.operating_profit || 0) - Number(b.operating_profit || 0))[0];
        if (best) focus.push(['','bi-trophy','Produk profit tertinggi',`${esc(best.product_name)} · ${money(best.operating_profit)}`,'Pertahankan']);
        if (worst && Number(worst.operating_profit || 0) < 0) focus.push(['bad','bi-exclamation-triangle','Produk dengan profit negatif',`${esc(worst.product_name)} · ${money(worst.operating_profit)}`,'Review harga/HPP']);
        if (!focus.length) focus.push(['','bi-check2-circle','Portofolio sehat','Belum ada produk dengan profit negatif','—']);
        $('anProductFocus').innerHTML = focus.map(([level,icon,title,note,action]) => `<div class="an-alert ${level}"><span class="an-alert-icon"><i class="bi ${icon}"></i></span><div><div class="an-alert-title">${title}</div><div class="an-alert-note">${note}</div></div><span class="an-alert-action">${action}</span></div>`).join('');
    }
    function renderProductSummary(rows) {
        const query = String($('anProductSearch')?.value || '').trim().toLowerCase();
        const sortKey = $('anProductSort')?.value || 'gross_sales';
        const filteredRows = (rows || []).filter(product => !query || `${product.product_name} ${product.sku}`.toLowerCase().includes(query));
        const sales = [...filteredRows].sort((a,b) => Number(b[sortKey] || 0) - Number(a[sortKey] || 0));
        const worst = [...sales].sort((a,b) => a.operating_profit - b.operating_profit).slice(0, 6);
        const max = Math.max(...sales.map(p => Math.abs(Number(p.operating_profit || 0))), 1);
        renderProductInsights(rows || []);
        $('bestProductBody').innerHTML = sales.length ? sales.map((p,i) => `<tr><td class="an-product-value">${i+1}</td><td><span class="an-product"><span class="an-product-copy"><a class="an-product-link" href="${productUrl(p)}" title="Buka produk terkait"> <span class="an-product-name">${esc(p.product_name)}</span></a><span class="an-product-sku">Kode: ${esc(p.sku || '—')}</span></span></span></td><td>${Number(p.qty || 0).toLocaleString('id-ID')}</td><td class="an-product-value">${money(p.gross_sales)}</td><td class="an-product-value">${money(p.hpp)}</td><td class="an-product-value" style="color:#b45309">${money(p.ad_cost)}</td><td class="an-product-value" style="color:${p.gross_profit>=0?'#15803d':'#dc2626'}">${money(p.gross_profit)}<span class="an-table-subline">${p.margin_pct}%</span></td><td class="an-product-value" style="color:${p.operating_profit>=0?'#15803d':'#dc2626'}">${money(p.operating_profit)}</td></tr>`).join('') : '<tr><td colspan="8"><div class="an-empty">Belum ada produk siap profit.</div></td></tr>';
        $('worstProductBody').innerHTML = worst.length ? worst.map(p => `<div class="an-list-row"><div class="an-list-main"><div class="an-list-name"><span class="an-dot ${p.operating_profit<0?'red':''}"></span> <a class="an-product-link" href="${productUrl(p)}" title="Buka produk terkait">${esc(p.product_name)}</a></div><div class="an-list-meta">Kode: ${esc(p.sku || '—')} · ${Number(p.qty || 0).toLocaleString('id-ID')} pcs</div><div class="an-bar"><span style="width:${Math.max(4, Math.min(100, Math.round(Math.abs(Number(p.operating_profit || 0)) / max * 100)))}%;background:${p.operating_profit<0?'#ef4444':'#facc15'}"></span></div></div><div class="an-list-value" style="color:${p.operating_profit<0?'#dc2626':'#a16207'}">${money(p.operating_profit)}</div></div>`).join('') : '<div class="an-empty">Belum ada produk untuk ditinjau.</div>';
    }
    function render() {
        const current = summary?.current || {};
        renderKpis();
        renderEnterprise();
        renderChart(summary?.daily || [], summary?.previous_daily || []);
        renderFunnel();
        renderStores();
        renderCosts();
        $('anSyncNote').textContent = `${Number(current.order_total || 0).toLocaleString('id-ID')} order · ${from()} sampai ${to()} · ${Number(current.order_count || 0).toLocaleString('id-ID')} siap profit`;
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
    async function loadStores() {
        stores = await api('/api/marketplace/stores').catch(() => []);
        fillStores();
    }
    async function loadProducts() {
        if (productsLoaded) return;
        $('bestProductBody').innerHTML = '<tr><td colspan="8"><div class="an-empty">Memuat detail produk…</div></td></tr>';
        try {
            const params = new URLSearchParams({ date_from: from(), date_to: to() });
            if (selectedStore()) params.set('store_id', selectedStore());
            params.set('_ts', Date.now().toString());
            const result = await api('/api/marketplace/analytics-products?' + params.toString(), { cache: 'no-store' });
            productData = result?.data || [];
            productsLoaded = true;
            renderProductSummary(productData);
        } catch (e) {
            $('bestProductBody').innerHTML = '<tr><td colspan="8"><div class="an-error">Detail produk gagal dimuat.</div></td></tr>';
        }
    }
    async function load() {
        setLoading('Mengambil ringkasan settlement…');
        $('anRefresh').disabled = true;
        productsLoaded = false;
        productData = [];
        orders = [];
        try {
            const params = new URLSearchParams({ date_from: from(), date_to: to(), compare_mode: $('anCompare').value, _ts: Date.now().toString() });
            if (selectedStore()) params.set('store_id', selectedStore());
            summary = await api('/api/marketplace/analytics-summary?' + params.toString(), { cache: 'no-store' });
            fillStores();
            render();
            $('bestProductBody').innerHTML = '<tr><td colspan="8"><div class="an-empty">Buka tab Produk untuk memuat detail.</div></td></tr>';
        } catch (e) {
            console.error('Analytics summary load failed', e);
            summary = null;
            $('anSyncNote').textContent = 'Data gagal dimuat';
            $('storeBody').innerHTML = '<tr><td colspan="6"><div class="an-error">Tidak dapat memuat ringkasan analytics.</div></td></tr>';
            $('bestProductBody').innerHTML = '<tr><td colspan="8"><div class="an-error">Tidak dapat memuat data analytics.</div></td></tr>';
        } finally {
            $('anRefresh').disabled = false;
        }
    }
    function activateTab(name) {
        document.querySelectorAll('[data-an-tab]').forEach(button => {
            const active = button.dataset.anTab === name;
            button.classList.toggle('active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        document.querySelectorAll('[data-an-pane]').forEach(pane => pane.classList.toggle('is-hidden', pane.dataset.anPane !== name));
    }
    document.querySelectorAll('[data-an-tab]').forEach(button => button.addEventListener('click', () => {
        activateTab(button.dataset.anTab);
        if (button.dataset.anTab === 'products') loadProducts();
    }));
    const syncUrl = () => { const params = new URLSearchParams({date_from:from(),date_to:to(),compare_mode:$('anCompare').value}); if (selectedStore()) params.set('store_id', selectedStore()); history.replaceState(null,'',location.pathname+'?'+params.toString()); };
    $('anRefresh').addEventListener('click',load); $('anStore').addEventListener('change',load); $('anCompare').addEventListener('change',()=>{syncUrl();load();});
    $('anProductSearch').addEventListener('input', () => renderProductSummary(productData));
    $('anProductSort').addEventListener('change', () => renderProductSummary(productData));
    if (window.flatpickr) flatpickr($('anDateRange'),{mode:'range',dateFormat:'Y-m-d',defaultDate:[from(),to()],onChange(dates){if(dates.length===2){$('anDateFrom').value=dates[0].toISOString().slice(0,10);$('anDateTo').value=dates[1].toISOString().slice(0,10);$('anDateRange').value=from()+' — '+to();syncUrl();load();}}});
    $('anStore').value = initialStore || '';
    loadStores().finally(load);
})();
</script>
@endpush
