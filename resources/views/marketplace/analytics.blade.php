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
    .an-btn:disabled { cursor:wait; opacity:.7; }
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
    .an-grid-main-chart { grid-template-columns:1fr; }
    .an-grid-secondary { display:grid; grid-template-columns:minmax(0,1.2fr) minmax(280px,.8fr); gap:1rem; }
    .an-card { min-width:0; border:1px solid var(--gf-border,#e5e7eb); border-radius:20px; background:#fff; box-shadow:0 8px 24px rgba(15,23,42,.035); overflow:hidden; }
    .an-card-head { padding:1rem 1.15rem .75rem; display:flex; justify-content:space-between; align-items:start; gap:.75rem; }
    .an-card-title { color:#0f172a; font-size:.9rem; font-weight:950; }
    .an-card-sub { color:#94a3b8; font-size:.7rem; font-weight:700; margin-top:.2rem; }
    .an-card-body { padding:0 1.15rem 1.15rem; }
    .an-chart-panel-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin:.1rem 0 .7rem; }
    .an-chart-panel-title { color:#64748b; font-size:.82rem; font-weight:750; }
    .an-chart-summary { color:#0f172a; font-size:.72rem; font-weight:800; line-height:1.45; text-align:right; }
    .an-chart { min-height:280px; position:relative; padding:.65rem 0 .35rem; }
    .an-chart-canvas { position:relative; height:280px; border-radius:14px; background:linear-gradient(180deg,#fbfdff 0%,#fff 100%); overflow:hidden; }
    .an-chart-canvas canvas { display:block; width:100% !important; height:280px !important; }
    .an-chart-grid { position:absolute; inset:.75rem 0 1.2rem; display:flex; flex-direction:column; justify-content:space-between; pointer-events:none; }
    .an-chart-grid span { border-top:1px solid #eef2f7; width:100%; }
    .an-chart-svg { width:100%; height:232px; position:relative; z-index:1; overflow:visible; }
    .an-chart-svg .an-chart-area { pointer-events:none; }
    .an-chart-svg .an-chart-line { fill:none; stroke-linecap:round; stroke-linejoin:round; vector-effect:non-scaling-stroke; }
    .an-chart-svg .an-chart-line.current { stroke-width:3; }
    .an-chart-svg .an-chart-line.previous { stroke-width:1.8; stroke-dasharray:5 6; opacity:.75; }
    .an-chart-svg .an-chart-point { stroke:#fff; stroke-width:2; vector-effect:non-scaling-stroke; }
    .an-chart-hover { fill:transparent; cursor:crosshair; }
    .an-chart-guide { stroke:#cbd5e1; stroke-width:1; stroke-dasharray:3 4; opacity:0; vector-effect:non-scaling-stroke; pointer-events:none; }
    .an-chart-tooltip { position:absolute; z-index:3; top:.65rem; left:0; min-width:174px; padding:.58rem .68rem; border:1px solid rgba(148,163,184,.28); border-radius:10px; background:rgba(15,23,42,.96); box-shadow:0 10px 24px rgba(15,23,42,.18); color:#fff; font-size:.66rem; line-height:1.45; opacity:0; transform:translateY(-3px); transition:opacity .14s ease,transform .14s ease; pointer-events:none; }
    .an-chart-tooltip.is-visible { opacity:1; transform:translateY(0); }
    .an-chart-tooltip strong { display:block; margin-bottom:.2rem; color:#e2e8f0; font-size:.68rem; }
    .an-chart-tooltip span { display:flex; justify-content:space-between; gap:1rem; color:#cbd5e1; }
    .an-chart-tooltip b { color:#fff; font-weight:850; }
    .an-chart-axis { display:flex; justify-content:space-between; color:#94a3b8; font-size:.62rem; font-weight:750; padding:.35rem .1rem 0; }
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
    .an-health-wide .an-funnel { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.7rem; }
    .an-health-wide .an-funnel-row { padding:.65rem .7rem; border:1px solid #eef2f7; border-radius:12px; background:#fbfdff; }
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
    .an-enterprise-grid-summary { grid-template-columns:1fr; }
    .an-enterprise-card { min-width:0; border:1px solid var(--dsh-border); border-radius:14px; background:var(--card,#fff); box-shadow:0 10px 22px rgba(15,23,42,.05); overflow:hidden; }
    .an-enterprise-head { display:flex; align-items:flex-start; justify-content:space-between; gap:.7rem; padding:.75rem .85rem; border-bottom:1px solid var(--dsh-border); }
    .an-enterprise-title { color:var(--text,#0f172a); font-size:.84rem; font-weight:800; }
    .an-enterprise-sub { color:var(--dsh-muted); font-size:.66rem; font-weight:600; margin-top:.18rem; }
    .an-enterprise-body { padding:.8rem .85rem; }
    .an-pulse-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.55rem; }
    .an-pulse-grid-executive { grid-template-columns:repeat(4,minmax(0,1fr)); }
    .an-pulse-grid-finance { grid-template-columns:repeat(5,minmax(0,1fr)); }
    .an-pulse-grid-executive .an-pulse { min-height:96px; display:flex; flex-direction:column; justify-content:space-between; }
    .an-pulse-grid-executive .an-pulse-label { font-size:.66rem; text-transform:none; letter-spacing:0; }
    .an-pulse-grid-executive .an-pulse-note { white-space:normal; overflow:visible; text-overflow:clip; line-height:1.35; }
    .an-pulse { min-width:0; padding:.62rem .68rem; border:1px solid var(--dsh-border); border-radius:10px; background:var(--hero-bg,#f8fafc); }
    .an-pulse-action { cursor:pointer; transition:border-color .16s ease, box-shadow .16s ease, transform .16s ease; }
    .an-pulse-action:hover { border-color:#93c5fd; box-shadow:0 6px 14px rgba(37,99,235,.1); transform:translateY(-1px); }
    .an-pulse-action.is-active { border-color:#2563eb; box-shadow:0 0 0 2px rgba(37,99,235,.12); }
    .an-pulse-label { color:var(--dsh-muted); font-size:.59rem; font-weight:800; text-transform:uppercase; letter-spacing:.05em; }
    .an-pulse-value { color:var(--text,#0f172a); font-size:1rem; font-weight:900; margin-top:.22rem; }
    .an-pulse-note { color:var(--dsh-muted); font-size:.62rem; font-weight:650; margin-top:.18rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .an-pulse-note i { margin-right:.18rem; font-size:.62rem; }
    .an-pulse-compare { font-weight:850; }
    .an-pulse-compare.good { color:#15803d; }
    .an-pulse-compare.bad { color:#b91c1c; }
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
    @media (max-width: 1100px) and (min-width: 761px) { .an-pulse-grid-executive, .an-pulse-grid-finance { grid-template-columns:repeat(3,minmax(0,1fr)); } }
    @media (max-width: 760px) { .an-grid-main, .an-grid-secondary, .an-enterprise-grid, .an-contribution-grid { grid-template-columns:1fr; } .an-pulse-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .an-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); } .an-kpi-value { font-size:1.12rem; } .an-field input { min-width:150px; } .an-product-toolbar { width:100%; justify-content:flex-start; margin-top:.35rem; } .an-product-toolbar input, .an-product-toolbar select { flex:1 1 140px; width:auto; } .an-chart-panel-head { flex-direction:column; gap:.35rem; } .an-chart-summary { text-align:left; } .an-health-wide .an-funnel { grid-template-columns:1fr; } }
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
    .an-hero { flex-wrap:nowrap; min-height:58px; }
    .an-hero-copy { flex:1 1 auto; }
    .an-hero-controls { flex:0 0 auto; justify-content:flex-end; }
    .an-chart-panel-head { display:grid; grid-template-columns:minmax(0,1fr) 220px; align-items:center; min-height:24px; }
    .an-chart-summary { width:220px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
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
    .an-tab-pane[data-an-pane="stores"] .an-kpi { min-height:118px; justify-content:center; gap:.12rem; text-align:center; }
    .an-tab-pane[data-an-pane="stores"] .an-kpi-label, .an-tab-pane[data-an-pane="stores"] .an-kpi-note { text-align:center; }
    .an-kpi-compare { display:block; max-width:100%; margin-top:.38rem; padding-top:.38rem; border-top:1px dashed var(--dsh-border); color:var(--dsh-muted); font-size:.61rem; font-weight:800; line-height:1.25; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .an-kpi-compare.good { color:#15803d; }
    .an-kpi-compare.bad { color:#b91c1c; }
    .an-kpi-compare i { margin-right:.16rem; }
    .an-kpi { overflow:visible; }
    .an-kpi-info { position:relative; display:inline-block; margin-left:.18rem; color:#94a3b8; cursor:help; font-size:.7rem; vertical-align:middle; }
    .an-kpi-info::after { content:attr(data-tooltip); position:absolute; z-index:40; left:50%; bottom:calc(100% + .45rem); width:220px; padding:.55rem .65rem; border-radius:9px; background:#0f172a; color:#fff; box-shadow:0 8px 20px rgba(15,23,42,.18); font-size:.65rem; font-weight:650; line-height:1.4; letter-spacing:0; text-transform:none; text-align:left; opacity:0; visibility:hidden; transform:translate(-50%,4px); transition:opacity .16s ease, transform .16s ease, visibility .16s ease; pointer-events:none; }
    .an-kpi-info:hover::after, .an-kpi-info:focus-visible::after { opacity:1; visibility:visible; transform:translate(-50%,0); }
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
    @media (max-width:760px) { .an-page { padding-inline:.5rem; } .an-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); } .an-hero { margin-inline:-.5rem; padding:.6rem .75rem; flex-wrap:wrap; min-height:0; } .an-hero-controls { align-items:stretch; width:100%; flex:1 1 100%; } .an-hero .an-field, .an-hero .an-field input, .an-hero .an-field select, .an-hero .an-btn { width:100%; } .an-hero .an-field { display:block; } .an-chart-panel-head { display:flex; min-height:0; } .an-chart-summary { width:auto; white-space:normal; overflow:visible; text-overflow:clip; } }
    body[data-theme="dark"] .an-tabs { background:linear-gradient(180deg,rgba(15,23,42,.96),rgba(30,41,59,.92)); border-color:rgba(51,65,85,.85); }
    body[data-theme="dark"] .an-tab:hover { color:#e2e8f0; background:rgba(255,255,255,.06); }
    @media (min-width:761px) {
        .an-kpi { min-width:0; padding:.75rem .8rem; }
        .an-kpi-label { font-size:.58rem; letter-spacing:.06em; }
        .an-kpi-value { font-size:1.06rem; font-weight:800; letter-spacing:-.02em; }
        .an-kpi-note { font-size:.58rem; padding-top:.42rem; margin-top:.42rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    }
    .an-economics-clickable { cursor:pointer; border-radius:9px; padding:.35rem .3rem; margin-inline:-.3rem; }
    .an-economics-clickable:hover { background:rgba(37,99,235,.05); }
    .an-economics-clickable:focus-visible { outline:3px solid rgba(37,99,235,.28); outline-offset:2px; }
    .an-economics-legend { display:flex; align-items:center; gap:.55rem; flex-wrap:wrap; color:var(--dsh-muted); font-size:.58rem; font-weight:700; white-space:nowrap; }
    .an-economics-legend span { display:inline-flex; align-items:center; gap:.25rem; }
    .an-economics-legend i { width:7px; height:7px; display:inline-block; border-radius:99px; background:#16a34a; }
    .an-economics-legend i.pending { background:#f59e0b; }
    .an-economics-row { grid-template-columns:minmax(0,1fr) auto; gap:1rem; padding:.62rem .3rem; margin-inline:-.3rem; border-bottom:1px solid var(--dsh-border); }
    .an-economics-row:last-child { border-bottom:0; }
    .an-economics-row:first-child { padding-top:.35rem; padding-bottom:.8rem; }
    .an-economics-copy { min-width:0; }
    .an-economics-row .an-contribution-name { font-size:.7rem; font-weight:800; }
    .an-economics-row .an-contribution-value { font-size:.73rem; font-weight:900; }
    .an-economics-action { display:inline-flex; align-items:center; gap:.18rem; margin-left:.3rem; color:#2563eb; font-size:.56rem; font-weight:800; }
    .an-economics-action::after { content:'↗'; font-size:.62rem; }
    .an-omzet-progress { display:flex; height:6px; margin-top:.28rem; overflow:hidden; border-radius:99px; background:#e2e8f0; }
    .an-omzet-progress-settled { background:#16a34a; }
    .an-omzet-progress-unsettled { background:#f59e0b; }
    .an-omzet-progress-meta { display:flex; justify-content:space-between; gap:.5rem; margin-top:.22rem; color:var(--dsh-muted); font-size:.58rem; font-weight:650; white-space:nowrap; }
    .an-hpp-progress-return { background:#be123c; }
    .an-hpp-progress-meta { flex-wrap:wrap; }
    .an-modal { display:none; position:fixed; inset:0; z-index:1060; }
    .an-modal.is-open { display:block; }
    .an-modal-backdrop { position:absolute; inset:0; background:rgba(15,23,42,.44); backdrop-filter:blur(3px); }
    .an-modal-dialog { position:relative; z-index:1; display:flex; flex-direction:column; width:calc(100% - 1.5rem); max-width:980px; max-height:calc(100vh - 2rem); margin:1rem auto; overflow:hidden; border:1px solid var(--dsh-border); border-radius:16px; background:var(--card,#fff); box-shadow:0 24px 70px rgba(15,23,42,.22); }
    .an-modal-head, .an-modal-foot { display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:.8rem .9rem; border-bottom:1px solid var(--dsh-border); }
    .an-modal-foot { justify-content:center; border-top:1px solid var(--dsh-border); border-bottom:0; }
    .an-modal-eyebrow { color:var(--dsh-muted); font-size:.6rem; font-weight:850; text-transform:uppercase; letter-spacing:.07em; }
    .an-modal-title { color:var(--text,#0f172a); font-size:.92rem; font-weight:800; margin-top:.12rem; }
    .an-modal-sub { color:var(--dsh-muted); font-size:.66rem; font-weight:600; margin-top:.18rem; }
    .an-modal-tabs { display:flex; gap:.25rem; padding:.45rem .85rem 0; border-bottom:1px solid var(--dsh-border); }
    .an-modal-tab { border:0; border-bottom:2px solid transparent; padding:.5rem .65rem; background:transparent; color:var(--dsh-muted); font-size:.68rem; font-weight:800; cursor:pointer; }
    .an-modal-tab:hover { color:var(--text,#0f172a); }
    .an-modal-tab.active { border-bottom-color:#2563eb; color:#2563eb; }
    .an-modal-close { width:30px; height:30px; border:1px solid var(--dsh-border); border-radius:8px; background:var(--card,#fff); color:var(--dsh-muted); font-size:1.2rem; line-height:1; cursor:pointer; }
    .an-modal-close:hover { color:var(--text,#0f172a); background:var(--hero-bg,#f8fafc); }
    .an-modal-summary { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.5rem; padding:.7rem .85rem; border-bottom:1px solid var(--dsh-border); }
    .an-modal-stat { min-width:0; padding:.55rem .6rem; border:1px solid var(--dsh-border); border-radius:10px; background:var(--hero-bg,#f8fafc); }
    .an-modal-stat-label { color:var(--dsh-muted); font-size:.58rem; font-weight:800; }
    .an-modal-stat-value { color:var(--text,#0f172a); font-size:.78rem; font-weight:850; margin-top:.2rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .an-modal-stat-note { display:block; margin-top:.16rem; color:var(--dsh-muted); font-size:.55rem; font-weight:700; }
    .an-modal-body { min-height:130px; overflow:auto; padding:.25rem .85rem .65rem; }
    .an-modal-fee-breakdown { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.35rem .65rem; margin:.45rem 0 .7rem; padding:.65rem .7rem; border:1px solid var(--dsh-border); border-radius:10px; background:var(--hero-bg,#f8fafc); }
    .an-modal-fee-breakdown div { display:flex; align-items:center; justify-content:space-between; gap:.5rem; color:var(--dsh-muted); font-size:.64rem; font-weight:700; }
    .an-modal-fee-breakdown strong { color:var(--text,#0f172a); font-weight:850; }
    .an-modal-fee-breakdown small { display:block; margin-top:.12rem; color:var(--dsh-muted); font-size:.55rem; font-weight:700; text-align:right; }
    .an-cash-table { min-width:900px; }
    .an-cash-table td { vertical-align:top; }
    .an-cash-order { color:var(--text,#0f172a); font-weight:800; white-space:nowrap; }
    .an-cash-status { display:inline-block; margin-top:.22rem; padding:.16rem .35rem; border-radius:5px; background:#dcfce7; color:#166534; font-size:.58rem; font-weight:800; text-transform:capitalize; }
    .an-cash-meta { display:block; margin-top:.18rem; color:var(--dsh-muted); font-size:.6rem; font-weight:600; white-space:nowrap; }
    .an-cash-detail { margin-top:.35rem; font-size:.6rem; font-weight:650; }
    .an-cash-detail summary { color:#2563eb; cursor:pointer; list-style:none; }
    .an-cash-detail summary::-webkit-details-marker { display:none; }
    .an-cash-detail summary::before { content:'+'; display:inline-block; width:.85rem; color:var(--dsh-muted); }
    .an-cash-detail[open] summary::before { content:'−'; }
    .an-cash-detail-grid { display:grid; grid-template-columns:repeat(2,minmax(130px,1fr)); gap:.22rem .75rem; margin-top:.35rem; padding:.45rem .55rem; border:1px solid var(--dsh-border); border-radius:8px; background:var(--hero-bg,#f8fafc); color:var(--dsh-muted); }
    .an-cash-detail-grid span { display:flex; justify-content:space-between; gap:.4rem; }
    .an-cash-detail-grid strong { color:var(--text,#0f172a); font-weight:800; }
    .an-cash-money { color:var(--text,#0f172a); font-weight:800; white-space:nowrap; }
    .an-cash-money.good { color:#15803d; }
    .an-cash-money.fee { color:#b45309; }
    .an-cash-money.affiliate { color:#9333ea; }
    .an-exception-kind { display:inline-block; margin-top:.22rem; padding:.16rem .35rem; border-radius:5px; background:#fee2e2; color:#991b1b; font-size:.58rem; font-weight:800; }
    .an-exception-kind.return { background:#fef3c7; color:#92400e; }
    .an-modal-page { color:var(--dsh-muted); font-size:.66rem; font-weight:750; }
    body.an-modal-open { overflow:hidden; }
    body[data-theme="dark"] .an-modal-dialog, body[data-theme="dark"] .an-modal-close { background:var(--card,#1e293b); }
    body[data-theme="dark"] .an-modal-stat, body[data-theme="dark"] .an-cash-detail-grid { background:#0f172a; }
    body[data-theme="dark"] .an-modal-fee-breakdown { background:#0f172a; }
    @media (max-width:760px) { .an-modal-dialog { width:calc(100% - .75rem); max-height:calc(100vh - .75rem); margin:.375rem auto; } .an-modal-summary { grid-template-columns:repeat(2,minmax(0,1fr)); } .an-modal-body { padding-inline:.5rem; } .an-modal-head { padding-inline:.65rem; } }
    .an-cohort-workspace { display:grid; gap:.8rem; min-width:0; }
    .an-cohort-header { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; padding:1rem 1.1rem; border:1px solid #1e293b; border-radius:15px; background:linear-gradient(135deg,#0f172a 0%,#1e3a5f 100%); box-shadow:0 12px 28px rgba(15,23,42,.12); }
    .an-cohort-heading { min-width:0; }
    .an-section-kicker { display:flex; align-items:center; gap:.35rem; color:#93c5fd; font-size:.6rem; font-weight:850; letter-spacing:.1em; text-transform:uppercase; }
    .an-section-kicker-dot { width:6px; height:6px; border-radius:99px; background:#38bdf8; box-shadow:0 0 0 4px rgba(56,189,248,.14); }
    .an-cohort-title { margin:.3rem 0 0; color:#fff; font-size:1.15rem; font-weight:850; letter-spacing:-.03em; }
    .an-cohort-description { margin:.28rem 0 0; color:#cbd5e1; font-size:.7rem; font-weight:600; line-height:1.45; }
    .an-cohort-header-meta { display:flex; align-items:flex-end; flex-direction:column; gap:.42rem; flex:0 0 auto; }
    .an-status-pill { display:inline-flex; align-items:center; gap:.3rem; padding:.32rem .5rem; border:1px solid rgba(147,197,253,.26); border-radius:7px; background:rgba(15,23,42,.26); color:#bfdbfe; font-size:.59rem; font-weight:850; white-space:nowrap; }
    .an-cohort-period { color:#93c5fd; font-size:.62rem; font-weight:700; white-space:nowrap; }
    .an-cohort-filter-card { padding:.8rem .9rem .65rem; border:1px solid var(--dsh-border); border-radius:14px; background:var(--card,#fff); box-shadow:0 8px 20px rgba(15,23,42,.04); }
    .an-cohort-filter-head { display:flex; align-items:center; justify-content:space-between; gap:.8rem; margin-bottom:.65rem; }
    .an-cohort-filter-title { color:var(--text,#0f172a); font-size:.75rem; font-weight:850; }
    .an-cohort-filter-sub { margin-top:.16rem; color:var(--dsh-muted); font-size:.63rem; font-weight:600; }
    .an-cohort-reset { border:0; padding:.25rem .35rem; background:transparent; color:#2563eb; font-size:.63rem; font-weight:800; cursor:pointer; white-space:nowrap; }
    .an-cohort-reset:hover { color:#1d4ed8; text-decoration:underline; }
    .an-cohort-filter-grid { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)) auto; gap:.55rem; align-items:end; }
    .an-cohort-field { display:flex; flex-direction:column; gap:.22rem; min-width:0; }
    .an-cohort-field label { color:var(--dsh-muted); font-size:.59rem; font-weight:850; }
    .an-cohort-field input, .an-cohort-field select { width:100%; min-height:33px; border:1px solid var(--dsh-border-strong); border-radius:8px; padding:.35rem .55rem; background:var(--card,#fff); color:var(--text,#0f172a); font-size:.67rem; font-weight:700; }
    .an-cohort-field input:focus, .an-cohort-field select:focus { border-color:#60a5fa; outline:3px solid rgba(96,165,250,.18); }
    .an-cohort-apply { min-height:33px; white-space:nowrap; }
    .an-cohort-active-filters { display:flex; align-items:center; gap:.35rem; flex-wrap:wrap; margin-top:.65rem; padding-top:.6rem; border-top:1px solid var(--dsh-border); }
    .an-cohort-filter-caption { color:var(--dsh-muted); font-size:.59rem; font-weight:850; text-transform:uppercase; letter-spacing:.05em; }
    .an-filter-chip { padding:.24rem .45rem; border:1px solid var(--dsh-border); border-radius:99px; background:var(--hero-bg,#f8fafc); color:var(--dsh-muted); font-size:.59rem; font-weight:650; }
    .an-filter-chip strong { color:var(--text,#0f172a); font-weight:850; }
    .an-cohort-summary-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.6rem; }
    .an-cohort-summary-card { min-width:0; min-height:88px; display:flex; flex-direction:column; justify-content:space-between; padding:.75rem .8rem; border:1px solid var(--dsh-border); border-radius:12px; background:var(--card,#fff); box-shadow:0 8px 18px rgba(15,23,42,.035); position:relative; overflow:hidden; }
    .an-cohort-summary-card::before { content:''; position:absolute; inset:0 auto auto 0; width:100%; height:3px; background:#2563eb; }
    .an-cohort-summary-card:nth-child(3)::before { background:#16a34a; }
    .an-cohort-summary-card:nth-child(4)::before { background:#d97706; }
    .an-cohort-summary-card.is-primary { border-color:#0f172a; background:#0f172a; }
    .an-cohort-summary-card.is-primary::before { background:#38bdf8; }
    .an-cohort-summary-label { color:var(--dsh-muted); font-size:.59rem; font-weight:850; letter-spacing:.04em; text-transform:uppercase; }
    .an-cohort-summary-card.is-primary .an-cohort-summary-label { color:#94a3b8; }
    .an-cohort-summary-value { margin-top:.2rem; color:var(--text,#0f172a); font-size:1.05rem; font-weight:900; letter-spacing:-.025em; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .an-cohort-summary-card.is-primary .an-cohort-summary-value { color:#fff; }
    .an-cohort-summary-note { color:var(--dsh-muted); font-size:.59rem; font-weight:650; }
    .an-cohort-summary-card.is-primary .an-cohort-summary-note { color:#64748b; }
    .an-cohort-chart-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.8rem; }
    .an-cohort-chart-card { min-width:0; }
    .an-cohort-chart-body { padding-top:.15rem; }
    .an-cohort-chart-canvas { position:relative; height:220px; border-radius:10px; background:linear-gradient(180deg,#fbfdff 0%,#fff 100%); }
    .an-cohort-chart-canvas canvas { display:block; width:100% !important; height:220px !important; }
    .an-cohort-layout { display:grid; grid-template-columns:minmax(0,1.65fr) minmax(240px,.55fr); gap:.8rem; align-items:start; }
    .an-cohort-matrix-card, .an-cohort-guide-card { min-width:0; }
    .an-cohort-panel-head { align-items:center; }
    .an-cohort-legend { display:flex; align-items:center; gap:.55rem; flex-wrap:wrap; color:var(--dsh-muted); font-size:.59rem; font-weight:700; white-space:nowrap; }
    .an-cohort-legend span { display:inline-flex; align-items:center; gap:.25rem; }
    .an-cohort-legend-swatch { width:8px; height:8px; display:inline-block; border-radius:3px; background:#2563eb; }
    .an-cohort-legend-swatch.muted { background:#e2e8f0; }
    .an-cohort-matrix-body { padding-top:.65rem; }
    .an-cohort-note { margin:0 0 .7rem; padding:.55rem .65rem; border:1px solid #fde68a; border-radius:9px; background:#fffbeb; color:#92400e; font-size:.64rem; font-weight:700; line-height:1.45; }
    .an-cohort-reading-bar { display:flex; align-items:center; gap:.7rem; flex-wrap:wrap; margin:0 0 .7rem; padding:.55rem .65rem; border:1px solid var(--dsh-border); border-radius:9px; background:var(--hero-bg,#f8fafc); }
    .an-cohort-reading-item { display:inline-flex; align-items:center; gap:.28rem; color:var(--text,#0f172a); font-size:.62rem; font-weight:850; }
    .an-cohort-reading-item i { color:#2563eb; font-size:.72rem; }
    .an-cohort-reading-item small { color:var(--dsh-muted); font-size:.58rem; font-weight:650; }
    .an-cohort-reading-hint { margin-left:auto; color:var(--dsh-muted); font-size:.6rem; font-weight:700; }
    .an-cohort-table-wrap { overflow:hidden; border:1px solid var(--dsh-border); border-radius:10px; }
    .an-cohort-table { width:100%; min-width:0; table-layout:fixed; }
    .an-cohort-table th, .an-cohort-table td { overflow:hidden; text-align:right; white-space:normal; }
    .an-cohort-table:not(.is-product) th:first-child { width:18%; }
    .an-cohort-table:not(.is-product) th:nth-child(2) { width:10%; }
    .an-cohort-table.is-product th:first-child { width:26%; }
    .an-cohort-table.is-product th:nth-child(2) { width:14%; }
    .an-cohort-table th:first-child, .an-cohort-table td:first-child { text-align:left; position:sticky; left:0; z-index:3; background:var(--card,#fff); }
    .an-cohort-table thead th { background:var(--hero-bg,#f8fafc); vertical-align:bottom; }
    .an-cohort-table thead th small { display:block; margin-top:.12rem; color:var(--dsh-muted); font-size:.53rem; font-weight:650; text-transform:none; letter-spacing:0; }
    .an-cohort-table tbody tr:hover td { background:rgba(37,99,235,.035); }
    .an-cohort-row-title { font-weight:850; color:var(--text,#0f172a); }
    .an-cohort-row-sub { display:block; margin-top:.12rem; color:var(--dsh-muted); font-size:.6rem; font-weight:650; }
    .an-cohort-base-value { display:block; color:var(--text,#0f172a); font-weight:900; }
    .an-cohort-product-cell .an-cohort-row-title { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .an-cohort-cell { min-width:78px; border:0; border-radius:7px; padding:.35rem .42rem; color:var(--text,#0f172a); background:rgba(37,99,235,var(--heat,.08)); font-size:.68rem; font-weight:850; cursor:pointer; }
    .an-cohort-cell-value, .an-cohort-cell-sub { display:block; }
    .an-cohort-cell-value { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .an-cohort-cell-sub { margin-top:.12rem; color:var(--dsh-muted); font-size:.53rem; font-weight:700; }
    .an-cohort-table.is-dense .an-cohort-cell { min-width:0; padding-inline:.18rem; font-size:.61rem; }
    .an-cohort-table.is-dense .an-cohort-cell-sub { display:none; }
    .an-cohort-table.is-dense thead th small { display:none; }
    .an-cohort-cell:hover, .an-cohort-cell:focus-visible { outline:2px solid #2563eb; outline-offset:1px; }
    .an-cohort-cell.product { background:rgba(217,119,6,var(--heat,.1)); }
    .an-cohort-cell.is-empty { background:transparent; color:#cbd5e1; cursor:default; }
    .an-cohort-table .an-cohort-sticky { position:sticky; left:0; z-index:3; background:var(--card,#fff); }
    .an-cohort-guide-list { display:grid; gap:.72rem; }
    .an-cohort-guide-item { display:grid; grid-template-columns:28px minmax(0,1fr); gap:.55rem; align-items:start; }
    .an-cohort-guide-icon { width:28px; height:28px; display:grid; place-items:center; border-radius:8px; font-size:.75rem; }
    .an-cohort-guide-icon.blue { background:#dbeafe; color:#1d4ed8; }
    .an-cohort-guide-icon.green { background:#dcfce7; color:#15803d; }
    .an-cohort-guide-icon.amber { background:#fef3c7; color:#a16207; }
    .an-cohort-guide-item strong { color:var(--text,#0f172a); font-size:.67rem; font-weight:850; }
    .an-cohort-guide-item p { margin:.16rem 0 0; color:var(--dsh-muted); font-size:.62rem; font-weight:600; line-height:1.45; }
    .an-cohort-guide-footer { display:flex; gap:.3rem; margin-top:.8rem; padding-top:.7rem; border-top:1px solid var(--dsh-border); color:var(--dsh-muted); font-size:.6rem; font-weight:650; line-height:1.4; }
    .an-cohort-guide-footer i { color:#2563eb; }
    body[data-theme="dark"] .an-cohort-field input, body[data-theme="dark"] .an-cohort-field select { background:#0f172a; color:#e2e8f0; }
    body[data-theme="dark"] .an-cohort-table th:first-child, body[data-theme="dark"] .an-cohort-table td:first-child, body[data-theme="dark"] .an-cohort-table .an-cohort-sticky { background:var(--card,#1e293b); }
    @media (max-width:1100px) { .an-cohort-filter-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } .an-cohort-apply { width:100%; } .an-cohort-chart-grid, .an-cohort-layout { grid-template-columns:1fr; } }
    @media (max-width:760px) { .an-cohort-header { flex-direction:column; } .an-cohort-header-meta { align-items:flex-start; } .an-cohort-filter-head { align-items:flex-start; } .an-cohort-filter-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .an-cohort-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .an-cohort-field, .an-cohort-field input, .an-cohort-field select { width:100%; min-width:0; } .an-cohort-apply { grid-column:1/-1; } .an-cohort-reading-hint { width:100%; margin-left:0; padding-top:.35rem; border-top:1px solid var(--dsh-border); } }
    @media (max-width:420px) { .an-cohort-filter-grid, .an-cohort-summary-grid { grid-template-columns:1fr; } .an-cohort-apply { grid-column:auto; } .an-cohort-filter-head { flex-direction:column; } }
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
                <div class="an-field"><label for="anCompare">Bandingkan</label><select id="anCompare"><option value="prev_period" @selected(($filters['compare_mode'] ?? 'prev_period') === 'prev_period')>Periode lalu</option><option value="prev_month" @selected(($filters['compare_mode'] ?? '') === 'prev_month')>Tanggal sama bulan lalu</option><option value="prev_quarter" @selected(($filters['compare_mode'] ?? '') === 'prev_quarter')>Tanggal sama 3 bulan lalu</option><option value="prev_year" @selected(($filters['compare_mode'] ?? '') === 'prev_year')>Tanggal sama tahun lalu</option></select></div>
                <input type="hidden" id="anDateFrom" value="{{ $filters['date_from'] }}"><input type="hidden" id="anDateTo" value="{{ $filters['date_to'] }}">
                <button class="an-btn an-btn-dark" id="anRefresh" type="button">↻ Refresh</button>
            </div>
        </div>

        <div class="an-shell">
          <div class="an-tabs-wrap">
            <div class="an-tabs" id="analyticsTabs" role="tablist" aria-label="Navigasi analytics">
                <button class="an-tab active" type="button" data-an-tab="summary" role="tab" aria-selected="true"><i class="bi bi-grid-1x2 me-1"></i>Ringkasan</button>
                <button class="an-tab" type="button" data-an-tab="stores" role="tab" aria-selected="false"><i class="bi bi-shop me-1"></i>Akun &amp; Biaya</button>
                <button class="an-tab" type="button" data-an-tab="products" role="tab" aria-selected="false"><i class="bi bi-box-seam me-1"></i>Produk</button>
                <button class="an-tab" type="button" data-an-tab="cohort" role="tab" aria-selected="false"><i class="bi bi-grid-3x3-gap me-1"></i>Cohort</button>
            </div>
          </div>
          <section class="an-enterprise-card an-executive-top an-tab-pane" data-an-pane="summary">
              <div class="an-enterprise-head"><div><div class="an-enterprise-title">Executive pulse</div><div class="an-enterprise-sub">Metrik utama periode aktif</div></div><span class="an-health-score" id="anOverallScore">—</span></div>
              <div class="an-enterprise-body"><div class="an-pulse-grid an-pulse-grid-executive" id="anPulseGrid"><div class="an-empty">Memuat insight…</div></div></div>
          </section>

        <div class="an-grid-main an-grid-main-chart an-tab-pane" data-an-pane="summary">
            <section class="an-card"><div class="an-card-head"><div><div class="an-card-title"><i class="bi bi-graph-up-arrow me-1" style="color:#16a34a"></i>Grafik harian</div><div class="an-card-sub">Omzet &amp; estimasi profit</div></div></div><div class="an-card-body"><div class="an-chart-panel-head"><div class="an-chart-panel-title">Performa harian</div><div class="an-chart-summary" id="chartCompareNote">Memuat…</div></div><div class="an-chart" id="revenueChart"><div class="an-empty">Memuat grafik…</div></div></div></section>
        </div>

        <section class="an-card an-health-wide an-tab-pane" data-an-pane="summary">
            <div class="an-card-head"><div><div class="an-card-title"><i class="bi bi-activity me-1" style="color:#2563eb"></i>Kesehatan order &amp; keuangan</div><div class="an-card-sub">Order operasional dan profit yang sudah tervalidasi.</div></div></div>
            <div class="an-card-body"><div class="an-funnel" id="salesFunnel"><div class="an-empty">Memuat…</div></div></div>
        </section>

          <div class="an-modal" id="cashOrdersModal" aria-hidden="true">
            <div class="an-modal-backdrop" data-cash-close></div>
            <section class="an-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="cashOrdersTitle">
                <div class="an-modal-head">
                    <div>
                        <div class="an-modal-eyebrow">Settlement complete</div>
                        <div class="an-modal-title" id="cashOrdersTitle">Status pencairan order</div>
                <div class="an-modal-sub" id="cashOrdersSubtitle">Periode aktif</div>
                    </div>
                    <button class="an-modal-close" type="button" data-cash-close aria-label="Tutup">×</button>
                </div>
                <div class="an-modal-tabs" id="cashOrdersTabs" role="tablist" aria-label="Status pencairan order">
                    <button class="an-modal-tab active" type="button" role="tab" aria-selected="true" data-cash-settlement="settled">Sudah cair</button>
                    <button class="an-modal-tab" type="button" role="tab" aria-selected="false" data-cash-settlement="unsettled">Belum cair</button>
                </div>
                <div class="an-modal-summary" id="cashOrdersSummary"><div class="an-empty">Memuat ringkasan…</div></div>
                <div class="an-modal-body" id="cashOrdersBody"><div class="an-empty">Pilih status pencairan untuk memuat order.</div></div>
                <div class="an-modal-foot"><button class="an-btn" id="cashOrdersPrev" type="button">‹ Sebelumnya</button><span class="an-modal-page" id="cashOrdersPage">—</span><button class="an-btn" id="cashOrdersNext" type="button">Berikutnya ›</button></div>
            </section>
          </div>

          <div class="an-modal" id="returnOrdersModal" aria-hidden="true">
            <div class="an-modal-backdrop" data-return-close></div>
            <section class="an-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="returnOrdersTitle">
                <div class="an-modal-head">
                    <div>
                        <div class="an-modal-eyebrow">Operational exceptions</div>
                        <div class="an-modal-title" id="returnOrdersTitle">Return dan pengiriman</div>
                        <div class="an-modal-sub" id="returnOrdersSubtitle">Periode aktif</div>
                    </div>
                    <button class="an-modal-close" type="button" data-return-close aria-label="Tutup">×</button>
                </div>
                <div class="an-modal-tabs" id="returnOrdersTabs" role="tablist" aria-label="Jenis exception order">
                    <button class="an-modal-tab active" type="button" role="tab" aria-selected="true" data-return-type="return_refund">Return / refund</button>
                    <button class="an-modal-tab" type="button" role="tab" aria-selected="false" data-return-type="failed_delivery">Pengiriman gagal</button>
                </div>
                <div class="an-modal-summary" id="returnOrdersSummary"><div class="an-empty">Memuat ringkasan…</div></div>
                <div class="an-modal-body" id="returnOrdersBody"><div class="an-empty">Pilih jenis exception untuk memuat data.</div></div>
                <div class="an-modal-foot"><button class="an-btn" id="returnOrdersPrev" type="button">‹ Sebelumnya</button><span class="an-modal-page" id="returnOrdersPage">—</span><button class="an-btn" id="returnOrdersNext" type="button">Berikutnya ›</button></div>
            </section>
          </div>

          <div class="an-enterprise-grid an-enterprise-grid-summary an-tab-pane" data-an-pane="summary">
            <section class="an-enterprise-card">
                <div class="an-enterprise-head"><div><div class="an-enterprise-title">Business health</div><div class="an-enterprise-sub">Kualitas data dan eksekusi order</div></div></div>
                <div class="an-enterprise-body"><div class="an-health-list" id="anHealthList"><div class="an-empty">Memuat health score…</div></div></div>
            </section>
          </div>

          <section class="an-enterprise-card an-tab-pane" data-an-pane="summary">
              <div class="an-enterprise-head"><div><div class="an-enterprise-title">Management attention</div><div class="an-enterprise-sub">Prioritas yang perlu ditindaklanjuti pada periode ini</div></div></div>
              <div class="an-enterprise-body"><div class="an-alerts" id="anAlerts"><div class="an-empty">Memuat alert…</div></div></div>
          </section>

        <div class="an-contribution-grid an-tab-pane" data-an-pane="summary">
            <section class="an-enterprise-card"><div class="an-enterprise-head"><div><div class="an-enterprise-title">Kontribusi toko</div><div class="an-enterprise-sub">Toko dengan kontribusi omzet terbesar</div></div></div><div class="an-enterprise-body"><div class="an-contribution-list" id="anTopStores"><div class="an-empty">Memuat…</div></div></div></section>
            <section class="an-enterprise-card"><div class="an-enterprise-head"><div><div class="an-enterprise-title">Unit economics</div><div class="an-enterprise-sub">Omzet total, pencairan, dan biaya aktual</div></div><div class="an-economics-legend" aria-label="Legenda status omzet"><span><i></i>Cair</span><span><i class="pending"></i>Belum cair</span></div></div><div class="an-enterprise-body"><div class="an-contribution-list" id="anEconomics"><div class="an-empty">Memuat…</div></div></div></section>
        </div>

        <section class="an-enterprise-card an-tab-pane is-hidden" data-an-pane="stores">
            <div class="an-enterprise-head"><div><div class="an-enterprise-title">Executive pulse</div><div class="an-enterprise-sub">Ringkasan efisiensi akun dan profitabilitas</div></div></div>
            <div class="an-enterprise-body"><div class="an-pulse-grid an-pulse-grid-finance" id="anFinancePulse"><div class="an-empty">Memuat…</div></div></div>
        </section>

        <div class="an-tab-pane is-hidden" data-an-pane="stores">
          <div class="an-kpis">
            <div class="an-kpi primary"><span class="an-kpi-label">Total Order <i id="kpiOrdersInfo" class="bi bi-info-circle an-kpi-info" tabindex="0" role="img" aria-label="Penjelasan Total Order" data-tooltip="Order non-batal · cair · belum cair · return/refund"></i></span><strong class="an-kpi-value" id="kpiOrders">—</strong><span class="an-kpi-note" id="kpiOrdersNote"><span title="Sudah cair"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> —</span> · <span title="Belum cair"><i class="bi bi-clock-history" aria-hidden="true"></i> —</span> · <span title="Return/refund"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> —</span></span></div>
            <div class="an-kpi"><span class="an-kpi-label">Produk Terjual <i id="kpiProductsSoldInfo" class="bi bi-info-circle an-kpi-info" tabindex="0" role="img" aria-label="Penjelasan Produk Terjual" data-tooltip="Total unit · cair · pending · refund"></i></span><strong class="an-kpi-value" id="kpiProductsSold">—</strong><span class="an-kpi-note" id="kpiProductsSoldNote"><span title="Produk sudah cair"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> —</span> · <span title="Produk pending"><i class="bi bi-clock-history" aria-hidden="true"></i> —</span> · <span title="Produk refund"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> —</span></span></div>
            <div class="an-kpi"><span class="an-kpi-label">Omzet / Gross Sales <i id="kpiRevenueInfo" class="bi bi-info-circle an-kpi-info" tabindex="0" role="img" aria-label="Penjelasan Omzet atau Gross Sales" data-tooltip="Omzet non-batal setelah return/refund"></i></span><strong class="an-kpi-value" id="kpiRevenue">—</strong><span class="an-kpi-note" id="kpiRevenueNote"><span title="Sudah cair"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> —</span> · <span title="Estimasi belum cair"><i class="bi bi-clock-history" aria-hidden="true"></i> —</span> · <span title="Return/refund"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> —</span></span></div>
            <div class="an-kpi"><span class="an-kpi-label">Prakiraan Admin Fee <i id="kpiAdminFeeInfo" class="bi bi-info-circle an-kpi-info" tabindex="0" role="img" aria-label="Penjelasan Prakiraan Admin Fee" data-tooltip="Omzet × rate fee actual · komposisi fee"></i></span><strong class="an-kpi-value" id="kpiAdminFee">—</strong><span class="an-kpi-note" id="kpiAdminFeeNote">komposisi fee actual</span></div>
            <div class="an-kpi"><span class="an-kpi-label">Prakiraan Cair / Net sales <i id="kpiEstimatedCashInfo" class="bi bi-info-circle an-kpi-info" tabindex="0" role="img" aria-label="Penjelasan Prakiraan Cair atau Net sales" data-tooltip="Omzet − admin fee · net cair + estimasi"></i></span><strong class="an-kpi-value" id="kpiEstimatedCash">—</strong><span class="an-kpi-note" id="kpiEstimatedCashNote"><span title="Net sudah cair"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> —</span> · <span title="Estimasi net belum cair"><i class="bi bi-clock-history" aria-hidden="true"></i> —</span> · <span title="Return/refund"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> —</span></span></div>
            <div class="an-kpi"><span class="an-kpi-label">Total HPP / COGS <i id="kpiHppInfo" class="bi bi-info-circle an-kpi-info" tabindex="0" role="img" aria-label="Penjelasan Total HPP atau COGS" data-tooltip="Total HPP · cair · belum cair · return/refund"></i></span><strong class="an-kpi-value" id="kpiHpp">—</strong><span class="an-kpi-note" id="kpiHppKpiNote"><span title="HPP sudah cair"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> —</span> · <span title="HPP belum cair"><i class="bi bi-clock-history" aria-hidden="true"></i> —</span> · <span title="HPP return/refund"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> —</span></span></div>
            <div class="an-kpi"><span class="an-kpi-label">Biaya Iklan (incl. PPN)</span><strong class="an-kpi-value" id="kpiAdCost">—</strong><span class="an-kpi-note" id="kpiAdCostNote"><span title="Biaya iklan sebelum PPN"><i class="bi bi-receipt" aria-hidden="true"></i> —</span> · <span title="PPN 11%"><i class="bi bi-percent" aria-hidden="true"></i> —</span> · <span title="Biaya per order"><i class="bi bi-bag" aria-hidden="true"></i> —</span></span></div>
            <div class="an-kpi"><span class="an-kpi-label">Omzet Cair / Net Revenue</span><strong class="an-kpi-value" id="kpiPayout">—</strong><span class="an-kpi-note" id="kpiPayoutNote"><span title="Order settlement complete"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> —</span> · <span title="Payout rate"><i class="bi bi-percent" aria-hidden="true"></i> —</span></span></div>
            <div class="an-kpi"><span class="an-kpi-label">Return / Refund Rate</span><strong class="an-kpi-value" id="kpiReturnRate">—</strong><span class="an-kpi-note" id="kpiReturnRateNote"><span title="Nilai return/refund"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> —</span> · <span title="Jumlah order"><i class="bi bi-bag" aria-hidden="true"></i> —</span></span></div>
            <div class="an-kpi"><span class="an-kpi-label">Laba Kotor / Gross Profit <i id="kpiGrossProfitInfo" class="bi bi-info-circle an-kpi-info" tabindex="0" role="img" aria-label="Penjelasan Laba Kotor atau Gross Profit" data-tooltip="Net revenue − total HPP"></i></span><strong class="an-kpi-value" id="kpiGrossProfit">—</strong><span class="an-kpi-note" id="kpiHppNote"><span title="Net revenue"><i class="bi bi-cash-stack" aria-hidden="true"></i> —</span> · <span title="Total HPP"><i class="bi bi-box-seam" aria-hidden="true"></i> —</span></span></div>
            <div class="an-kpi"><span class="an-kpi-label">Est. Laba Bersih / Est. Net Profit</span><strong class="an-kpi-value" id="kpiEstimatedProfit">—</strong><span class="an-kpi-note" id="kpiEstimatedProfitNote"><span title="Margin estimasi"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i> —</span> · <span title="Net revenue − HPP − iklan"><i class="bi bi-calculator" aria-hidden="true"></i> estimasi</span></span></div>
            <div class="an-kpi"><span class="an-kpi-label">Laba Operasional / Net Operasional</span><strong class="an-kpi-value" id="kpiOperatingProfit">—</strong><span class="an-kpi-note" id="kpiOperatingProfitNote"><span title="Omzet cair"><i class="bi bi-cash-coin" aria-hidden="true"></i> —</span> · <span title="Total HPP + iklan"><i class="bi bi-dash-circle" aria-hidden="true"></i> —</span></span></div>
          </div>
        </div>

        <div class="an-enterprise-grid an-tab-pane is-hidden" data-an-pane="stores">
            <section class="an-enterprise-card"><div class="an-enterprise-head"><div><div class="an-enterprise-title">Store performance snapshot</div><div class="an-enterprise-sub">Ranking toko berdasarkan omzet dan profit operasional</div></div></div><div class="an-enterprise-body"><div class="an-pulse-grid" id="anStorePulse"><div class="an-empty">Memuat…</div></div></div></section>
            <section class="an-enterprise-card"><div class="an-enterprise-head"><div><div class="an-enterprise-title">Cost efficiency</div><div class="an-enterprise-sub">Beban biaya dan payout per toko</div></div></div><div class="an-enterprise-body"><div class="an-health-list" id="anStoreCostPulse"><div class="an-empty">Memuat…</div></div></div></section>
        </div>

        <div class="an-grid-secondary an-tab-pane is-hidden" data-an-pane="stores">
            <section class="an-card"><div class="an-card-head"><div><div class="an-card-title">Performa per toko</div></div></div><div class="an-card-body"><div class="an-table-wrap"><table class="an-table"><thead><tr><th>Toko</th><th>Order</th><th>Selesai</th><th>Cancel</th><th>Omzet marketplace</th><th>Laba Bersih</th></tr></thead><tbody id="storeBody"><tr><td colspan="6"><div class="an-empty">Memuat…</div></td></tr></tbody></table></div></div></section>
            <section class="an-card"><div class="an-card-head"><div><div class="an-card-title">Biaya marketplace</div><div class="an-card-sub">Fee estimasi mengikuti rate actual Unit economics; biaya settlement tetap tersedia untuk audit</div></div></div><div class="an-card-body"><div class="an-costs" id="costBody"><div class="an-empty">Memuat…</div></div></div></section>
        </div>

        <div class="an-enterprise-grid an-tab-pane is-hidden" data-an-pane="products">
            <section class="an-enterprise-card"><div class="an-enterprise-head"><div><div class="an-enterprise-title">Product portfolio</div><div class="an-enterprise-sub">Kualitas portofolio berdasarkan profit tervalidasi</div></div></div><div class="an-enterprise-body"><div class="an-pulse-grid" id="anProductPulse"><div class="an-empty">Menunggu tab Produk dibuka…</div></div></div></section>
            <section class="an-enterprise-card"><div class="an-enterprise-head"><div><div class="an-enterprise-title">Product focus</div><div class="an-enterprise-sub">Rekomendasi fokus berdasarkan data periode ini</div></div></div><div class="an-enterprise-body"><div class="an-alerts" id="anProductFocus"><div class="an-empty">Menunggu data produk…</div></div></div></section>
        </div>

        <div class="an-grid-secondary an-tab-pane is-hidden" data-an-pane="products">
            <section class="an-card"><div class="an-card-head"><div><div class="an-card-title">Semua penjualan produk</div><div class="an-card-sub">Hanya produk dari order dengan settlement dan HPP tervalidasi; iklan dialokasikan berdasar omzet produk</div></div><div class="an-product-toolbar"><input id="anProductSearch" type="search" placeholder="Cari produk / SKU…"><select id="anProductSort"><option value="gross_sales">Urutkan: Omzet</option><option value="operating_profit">Urutkan: Laba</option><option value="margin_pct">Urutkan: Margin</option><option value="qty">Urutkan: Qty</option></select></div></div><div class="an-card-body"><div class="an-table-wrap"><table class="an-table an-product-table"><thead><tr><th>#</th><th>Produk</th><th>Qty</th><th>Omzet</th><th>HPP</th><th>Iklan incl. PPN</th><th>Laba Kotor</th><th>Laba Operasional</th></tr></thead><tbody id="bestProductBody"><tr><td colspan="8"><div class="an-empty">Buka tab Produk untuk memuat detail.</div></td></tr></tbody></table></div></div></section>
            <section class="an-card"><div class="an-card-head"><div><div class="an-card-title">Produk perlu perhatian</div></div></div><div class="an-card-body"><div class="an-list" id="worstProductBody"><div class="an-empty">Memuat…</div></div></div></section>
        </div>

        @include('marketplace.partials._analytics_cohort')
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
    let storesLoaded = false;
    let orders = [];
    let productsLoaded = false;
    let productData = [];
    let adSpendBySku = {};
    let cashPage = 1;
    let cashPayload = null;
    let cashLoading = false;
    let cashSettlement = 'settled';
    let cashFocus = 'payout';
    let returnPage = 1;
    let returnPayload = null;
    let returnLoading = false;
    let returnType = 'return_refund';
    let cohortPayload = null;
    let cohortLoading = false;
    let cohortOptionsKey = null;
    let revenueChartInstance = null;
    let cohortChartInstance = null;
    let cohortDistributionChartInstance = null;
    let chartLibraryPromise = null;
    let chartRenderToken = 0;
    let cohortChartRenderToken = 0;
    let selectedPulseMetric = null;
    const APP_TIMEZONE = @json(config('app.timezone', 'Asia/Jakarta'));
    const from = () => $('anDateFrom').value;
    const to = () => $('anDateTo').value;
    const n = v => Number.parseFloat(v || 0) || 0;
    const status = o => String(o.order_status || o.status || '').toUpperCase();
    const completed = o => ['COMPLETED', 'DELIVERED', 'CLOSED'].includes(status(o));
    const money = v => fmtRp(Math.round(v || 0));
    const pct = (a,b) => b ? (a / b * 100).toFixed(1) + '%' : '0%';
    const skuKey = value => String(value || '').trim().toUpperCase();
    const parseAppDate = value => {
        const raw = String(value || '').trim();
        if (!raw) return null;
        const normalized = raw.includes(' ') ? raw.replace(' ', 'T') : raw;
        const hasTimezone = /(?:Z|[+-]\d{2}:?\d{2})$/i.test(normalized);
        const date = new Date(hasTimezone ? normalized : `${normalized}+07:00`);
        return Number.isNaN(date.getTime()) ? null : date;
    };
    const dateInAppTimezone = value => {
        const date = parseAppDate(value);
        if (!date) return null;
        const parts = new Intl.DateTimeFormat('en-CA', { timeZone: APP_TIMEZONE, year:'numeric', month:'2-digit', day:'2-digit' }).formatToParts(date);
        const values = Object.fromEntries(parts.filter(part => part.type !== 'literal').map(part => [part.type, part.value]));
        return `${values.year}-${values.month}-${values.day}`;
    };
    const dateKey = o => dateInAppTimezone(o.ordered_at || o.created_at);
    const selectedStore = () => $('anStore').value;
    const productPageUrl = @json(route('marketplace.products'));
    const productUrl = product => `${productPageUrl}?search=${encodeURIComponent(product.sku || product.product_name || '')}`;
    const initialStore = @json($filters['store_id'] ?? '');
    const compareParam = new URLSearchParams(location.search).get('compare_mode');
    if (['prev_period','prev_month','prev_quarter','prev_year'].includes(compareParam)) $('anCompare').value = compareParam;
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
        if ($('anCompare')?.value === 'prev_quarter') return { from: ymd(shiftCalendar(start, -3)), to: ymd(shiftCalendar(end, -3)) };
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
        const adCostBeforeTax = Number(current.ad_cost_before_tax ?? 0);
        const adCostVat = Number(current.ad_cost_vat ?? Math.max(0, adCost - adCostBeforeTax));
        const settledOrderRevenue = Number(current.cash_order_revenue || 0);
        const unsettledOrderRevenue = Number(current.cash_unsettled_order_revenue || 0);
        const orderRevenue = settledOrderRevenue + unsettledOrderRevenue || Number(current.gmv || 0);
        const returnRefundAmount = Number(current.return_refund_amount ?? current.cash_refund ?? current.refund ?? 0);
        const netOrderRevenue = Math.max(0, orderRevenue - returnRefundAmount);
        const cashPayout = Number(current.cash_payout ?? current.payout ?? 0);
        const reportedHppTotal = Number(current.hpp_total ?? current.hpp ?? 0);
        const rawHppSettled = Number(current.hpp_settled ?? 0);
        const rawHppUnsettled = current.hpp_unsettled !== undefined
            ? Number(current.hpp_unsettled || 0)
            : Math.max(0, reportedHppTotal - rawHppSettled);
        const hppReturnRefund = Number(current.hpp_return_refund ?? 0);
        const hppReturnRefundSettled = Number(current.hpp_return_refund_settled ?? 0);
        const hppReturnRefundUnsettled = Number(current.hpp_return_refund_unsettled ?? 0);
        const hppSettled = Math.max(0, rawHppSettled - hppReturnRefundSettled);
        const hppUnsettled = Math.max(0, rawHppUnsettled - hppReturnRefundUnsettled);
        const hppTotal = hppSettled + hppUnsettled + hppReturnRefund || reportedHppTotal;
        const hppShipped = Number(current.hpp_shipped ?? 0);
        const actualFee = Number(current.cash_marketplace_fees ?? current.marketplace_fees_actual ?? 0);
        const actualFeeRate = settledOrderRevenue > 0 ? actualFee / settledOrderRevenue : 0;
        const effectiveFeeRate = actualFeeRate > 0 ? actualFeeRate : 0.21;
        const estimatedFee = orderRevenue * effectiveFeeRate;
        const netSettledBeforeRefund = Math.max(0, settledOrderRevenue - actualFee);
        const estimatedUnsettledNetBeforeRefund = Math.max(0, unsettledOrderRevenue * (1 - effectiveFeeRate));
        const unsettledRefund = Math.min(returnRefundAmount, estimatedUnsettledNetBeforeRefund);
        const settledRefund = Math.max(0, returnRefundAmount - unsettledRefund);
        const netSettled = Math.max(0, netSettledBeforeRefund - settledRefund);
        const estimatedUnsettledNet = Math.max(0, estimatedUnsettledNetBeforeRefund - unsettledRefund);
        const estimatedPayout = netSettled + estimatedUnsettledNet;
        const feeComponents = [
            ['bi-receipt', 'Administrasi', Number(current.cash_commission_fee || 0)],
            ['bi-headset', 'Layanan', Number(current.cash_service_fee || 0)],
            ['bi-arrow-left-right', 'Transaksi', Number(current.cash_transaction_fee || 0)],
            ['bi-shield-check', 'Asuransi', Number(current.cash_shipping_insurance_fee || 0)],
            ['bi-bank', 'Pajak escrow', Number(current.cash_escrow_tax || 0)],
        ];
        const feeRateBase = settledOrderRevenue;
        const feeComposition = actualFee > 0 && feeRateBase > 0
            ? feeComponents.filter(([, , value]) => value > 0).map(([icon, label, value]) => `<span title="${label}"><i class="bi ${icon}" aria-hidden="true"></i> ${(value / feeRateBase * 100).toFixed(1)}%</span>`).join(' · ')
            : 'komposisi belum tersedia';
        const feeRateTotal = actualFee > 0 && feeRateBase > 0
            ? `<span title="Total fee actual"><i class="bi bi-percent" aria-hidden="true"></i> ${(actualFee / feeRateBase * 100).toFixed(1)}%</span>`
            : '';
        const feeCompositionText = actualFee > 0 && feeRateBase > 0
            ? feeComponents.filter(([, , value]) => value > 0).map(([, label, value]) => `${({Administrasi: 'Adm', Layanan: 'Lyn', Transaksi: 'Trx', Asuransi: 'Asr', 'Pajak escrow': 'Esc'})[label] || label} ${(value / feeRateBase * 100).toFixed(1)}%`).join(' · ')
            : 'komposisi belum tersedia';
        const grossProfit = estimatedPayout - hppTotal;
        const estimatedProfit = grossProfit - adCost;
        const operatingProfit = cashPayout - hppTotal - adCost;
        const payoutRate = orderRevenue > 0 ? cashPayout / orderRevenue * 100 : 0;
        const returnRefundRate = orderRevenue > 0 ? returnRefundAmount / orderRevenue * 100 : 0;
        const returnRefundCount = Number(current.return_refund_order_count ?? current.return_refund_count ?? 0);
        const totalQty = Number(current.product_qty ?? current.qty ?? 0);
        const productQtySettled = Number(current.product_qty_settled ?? 0);
        const productQtyUnsettled = Number(current.product_qty_unsettled ?? Math.max(0, totalQty - productQtySettled));
        const productQtyReturnRefund = Number(current.product_qty_return_refund ?? 0);
        const totalOrderCount = Number(current.order_total || 0);
        const adCostPerOrder = totalOrderCount > 0 ? adCost / totalOrderCount : 0;
        const settledOrderCountRaw = Number(current.cash_order_count || 0);
        const unsettledOrderCountRaw = Number(current.cash_unsettled_order_count || 0);
        const returnSettledOrderCount = Number(current.return_refund_settled_order_count || 0);
        const returnUnsettledOrderCount = Number(current.return_refund_unsettled_order_count || 0);
        const settledOrderCount = Math.max(0, settledOrderCountRaw - returnSettledOrderCount);
        const unsettledOrderCount = Math.max(0, unsettledOrderCountRaw - returnUnsettledOrderCount);
        $('kpiOrders').textContent = Number(current.order_total || 0).toLocaleString('id-ID');
        $('kpiProductsSold').textContent = totalQty.toLocaleString('id-ID');
        $('kpiProductsSoldNote').innerHTML = `<span title="Produk sudah cair"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> ${productQtySettled.toLocaleString('id-ID')}</span> · <span title="Produk pending"><i class="bi bi-clock-history" aria-hidden="true"></i> ${productQtyUnsettled.toLocaleString('id-ID')}</span> · <span title="Produk refund"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> ${productQtyReturnRefund.toLocaleString('id-ID')}</span>`;
        $('kpiProductsSoldInfo').dataset.tooltip = `Total ${totalQty.toLocaleString('id-ID')} · cair ${productQtySettled.toLocaleString('id-ID')} · pending ${productQtyUnsettled.toLocaleString('id-ID')} · refund ${productQtyReturnRefund.toLocaleString('id-ID')}`;
        $('kpiRevenue').textContent = money(netOrderRevenue);
        $('kpiAdminFee').textContent = money(estimatedFee);
        $('kpiEstimatedCash').textContent = money(estimatedPayout);
        $('kpiHpp').textContent = money(hppTotal);
        $('kpiGrossProfit').textContent = money(grossProfit);
        $('kpiAdCost').textContent = money(adCost);
        $('kpiEstimatedProfit').textContent = money(estimatedProfit);
        $('kpiPayout').textContent = money(cashPayout);
        $('kpiOperatingProfit').textContent = money(operatingProfit);
        $('kpiReturnRate').textContent = `${returnRefundRate.toFixed(1)}%`;
        $('kpiRevenueNote').innerHTML = `<span title="Sudah cair"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> ${money(settledOrderRevenue)}</span> · <span title="Estimasi belum cair"><i class="bi bi-clock-history" aria-hidden="true"></i> ${money(unsettledOrderRevenue)}</span> · <span title="Return/refund"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> ${money(returnRefundAmount)}</span>`;
        $('kpiEstimatedCashNote').innerHTML = `<span title="Net sudah cair"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> ${money(netSettled)}</span> · <span title="Estimasi net belum cair"><i class="bi bi-clock-history" aria-hidden="true"></i> ${money(estimatedUnsettledNet)}</span> · <span title="Return/refund"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> ${money(returnRefundAmount)}</span>`;
        $('kpiAdminFeeNote').innerHTML = feeRateTotal ? `${feeRateTotal} · ${feeComposition}` : feeComposition;
        $('kpiOrdersNote').innerHTML = `<span title="Sudah cair"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> ${settledOrderCount.toLocaleString('id-ID')}</span> · <span title="Belum cair"><i class="bi bi-clock-history" aria-hidden="true"></i> ${unsettledOrderCount.toLocaleString('id-ID')}</span> · <span title="Return/refund"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> ${returnRefundCount.toLocaleString('id-ID')}</span>`;
        $('kpiHppKpiNote').innerHTML = `<span title="HPP sudah cair"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> ${money(hppSettled)}</span> · <span title="HPP belum cair"><i class="bi bi-clock-history" aria-hidden="true"></i> ${money(hppUnsettled)}</span> · <span title="HPP return/refund"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> ${money(hppReturnRefund)}</span>`;
        $('kpiHppNote').innerHTML = `<span title="Net revenue"><i class="bi bi-cash-stack" aria-hidden="true"></i> ${money(estimatedPayout)}</span> · <span title="Total HPP"><i class="bi bi-box-seam" aria-hidden="true"></i> ${money(hppTotal)}</span>`;
        $('kpiAdCostNote').innerHTML = `<span title="Biaya iklan sebelum PPN"><i class="bi bi-receipt" aria-hidden="true"></i> ${money(adCostBeforeTax)}</span> · <span title="PPN 11%"><i class="bi bi-percent" aria-hidden="true"></i> ${money(adCostVat)}</span> · <span title="Biaya per order"><i class="bi bi-bag" aria-hidden="true"></i> ${money(adCostPerOrder)}</span>`;
        $('kpiEstimatedProfitNote').innerHTML = `<span title="Margin estimasi"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i> ${orderRevenue > 0 ? (estimatedProfit / orderRevenue * 100).toFixed(1) : '0.0'}%</span> · <span title="Net revenue − HPP − iklan"><i class="bi bi-calculator" aria-hidden="true"></i> estimasi</span>`;
        $('kpiPayoutNote').innerHTML = `<span title="Order settlement complete"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> ${Number(current.cash_order_count || 0).toLocaleString('id-ID')}</span> · <span title="Rasio omzet cair"><i class="bi bi-percent" aria-hidden="true"></i> ${payoutRate.toFixed(1)}%</span>`;
        $('kpiOperatingProfitNote').innerHTML = `<span title="Omzet cair"><i class="bi bi-cash-coin" aria-hidden="true"></i> ${money(cashPayout)}</span> · <span title="Total HPP + iklan"><i class="bi bi-dash-circle" aria-hidden="true"></i> ${money(hppTotal + adCost)}</span>`;
        $('kpiReturnRateNote').innerHTML = `<span title="Nilai return/refund"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> ${money(returnRefundAmount)}</span> · <span title="Jumlah order"><i class="bi bi-bag" aria-hidden="true"></i> ${returnRefundCount.toLocaleString('id-ID')}</span>`;
        $('kpiOrdersInfo').dataset.tooltip = `Order ${Number(current.order_total || 0).toLocaleString('id-ID')} · cair ${settledOrderCount.toLocaleString('id-ID')} · belum ${unsettledOrderCount.toLocaleString('id-ID')} · return ${returnRefundCount.toLocaleString('id-ID')}`;
        $('kpiRevenueInfo').dataset.tooltip = `Net ${money(netOrderRevenue)} · gross ${money(orderRevenue)} · return ${money(returnRefundAmount)}`;
        $('kpiAdminFeeInfo').dataset.tooltip = `Total ${money(estimatedFee)} · rate ${(effectiveFeeRate * 100).toFixed(1)}% · ${feeCompositionText}`;
        $('kpiEstimatedCashInfo').dataset.tooltip = `Total ${money(estimatedPayout)} · cair ${money(netSettled)} · est. belum ${money(estimatedUnsettledNet)} · return ${money(returnRefundAmount)}`;
        $('kpiHppInfo').dataset.tooltip = `Total ${money(hppTotal)} · cair ${money(hppSettled)} · belum ${money(hppUnsettled)} · return ${money(hppReturnRefund)}`;
        $('kpiGrossProfitInfo').dataset.tooltip = `Laba ${money(grossProfit)} · net ${money(estimatedPayout)} · HPP ${money(hppTotal)}`;

        const previous = summary?.previous || {};
        const previousGrossOrderRevenue = Number(previous.cash_order_revenue || 0) + Number(previous.cash_unsettled_order_revenue || 0) || Number(previous.gmv || 0);
        const previousReturnRefundAmount = Number(previous.return_refund_amount ?? previous.cash_refund ?? previous.refund ?? 0);
        const previousNetOrderRevenue = Math.max(0, previousGrossOrderRevenue - previousReturnRefundAmount);
        const previousSettledOrderRevenue = Number(previous.cash_order_revenue || 0);
        const previousUnsettledOrderRevenue = Number(previous.cash_unsettled_order_revenue || 0);
        const previousActualFee = Number(previous.cash_marketplace_fees ?? previous.marketplace_fees_actual ?? 0);
        const previousFeeRate = previousSettledOrderRevenue > 0 ? previousActualFee / previousSettledOrderRevenue : 0.21;
        const previousEstimatedFee = previousGrossOrderRevenue * previousFeeRate;
        const previousNetSettledBeforeRefund = Math.max(0, previousSettledOrderRevenue - previousActualFee);
        const previousEstimatedUnsettledNetBeforeRefund = Math.max(0, previousUnsettledOrderRevenue * (1 - previousFeeRate));
        const previousUnsettledRefund = Math.min(previousReturnRefundAmount, previousEstimatedUnsettledNetBeforeRefund);
        const previousSettledRefund = Math.max(0, previousReturnRefundAmount - previousUnsettledRefund);
        const previousNetSettled = Math.max(0, previousNetSettledBeforeRefund - previousSettledRefund);
        const previousEstimatedUnsettledNet = Math.max(0, previousEstimatedUnsettledNetBeforeRefund - previousUnsettledRefund);
        const previousEstimatedPayout = previousNetSettled + previousEstimatedUnsettledNet;
        const previousReportedHpp = Number(previous.hpp_total ?? previous.hpp ?? 0);
        const previousHppSettledRaw = Number(previous.hpp_settled ?? 0);
        const previousHppUnsettledRaw = previous.hpp_unsettled !== undefined
            ? Number(previous.hpp_unsettled || 0)
            : Math.max(0, previousReportedHpp - previousHppSettledRaw);
        const previousHppReturnRefund = Number(previous.hpp_return_refund ?? 0);
        const previousHppReturnRefundSettled = Number(previous.hpp_return_refund_settled ?? 0);
        const previousHppReturnRefundUnsettled = Number(previous.hpp_return_refund_unsettled ?? 0);
        const previousHppSettled = Math.max(0, previousHppSettledRaw - previousHppReturnRefundSettled);
        const previousHppUnsettled = Math.max(0, previousHppUnsettledRaw - previousHppReturnRefundUnsettled);
        const previousHppTotal = previousHppSettled + previousHppUnsettled + previousHppReturnRefund || previousReportedHpp;
        const previousAdCost = Number(previous.ad_cost || 0);
        const previousCashPayout = Number(previous.cash_payout ?? previous.payout ?? 0);
        const previousGrossProfit = previousEstimatedPayout - previousHppTotal;
        const previousEstimatedProfit = Number(previous.estimated_profit ?? (previousGrossProfit - previousAdCost));
        const previousOperatingProfit = previousCashPayout - previousHppTotal - previousAdCost;
        const previousReturnRate = previousGrossOrderRevenue > 0 ? previousReturnRefundAmount / previousGrossOrderRevenue * 100 : 0;
        const comparisonText = (value, baseline, formatter = money) => {
            const currentValue = Number(value || 0);
            const previousValue = Number(baseline || 0);
            const previousText = formatter(previousValue);
            if (previousValue === 0) {
                return { text: `<i class="bi bi-arrow-left-right" aria-hidden="true"></i> ${previousText} · ${currentValue === 0 ? '0,0%' : 'baru'}`, className: currentValue > 0 ? 'good' : '' };
            }
            const change = (currentValue - previousValue) / Math.abs(previousValue) * 100;
            return { text: `<i class="bi bi-arrow-left-right" aria-hidden="true"></i> ${previousText} · ${change > 0 ? '+' : ''}${change.toFixed(1).replace('.', ',')}%`, className: change > 0 ? 'good' : (change < 0 ? 'bad' : '') };
        };
        const setKpiComparison = (valueId, value, baseline, formatter = money) => {
            const valueElement = $(valueId);
            const card = valueElement?.closest('.an-kpi');
            if (!card) return;
            let element = card.querySelector('.an-kpi-compare');
            if (!element) {
                element = document.createElement('span');
                card.appendChild(element);
            }
            const comparison = comparisonText(value, baseline, formatter);
            element.className = `an-kpi-compare ${comparison.className}`;
            element.title = 'Nilai periode pembanding dan perubahan';
            element.innerHTML = comparison.text;
        };
        const countFormatter = value => Number(value || 0).toLocaleString('id-ID');
        const percentFormatter = value => `${Number(value || 0).toFixed(1).replace('.', ',')}%`;
        setKpiComparison('kpiOrders', totalOrderCount, previous.order_total, countFormatter);
        setKpiComparison('kpiProductsSold', totalQty, previous.product_qty ?? previous.qty, countFormatter);
        setKpiComparison('kpiRevenue', netOrderRevenue, previousNetOrderRevenue);
        setKpiComparison('kpiAdminFee', estimatedFee, previousEstimatedFee);
        setKpiComparison('kpiEstimatedCash', estimatedPayout, previousEstimatedPayout);
        setKpiComparison('kpiHpp', hppTotal, previousHppTotal);
        setKpiComparison('kpiAdCost', adCost, previousAdCost);
        setKpiComparison('kpiPayout', cashPayout, previousCashPayout);
        setKpiComparison('kpiReturnRate', returnRefundRate, previousReturnRate, percentFormatter);
        setKpiComparison('kpiGrossProfit', grossProfit, previousGrossProfit);
        setKpiComparison('kpiEstimatedProfit', estimatedProfit, previousEstimatedProfit);
        setKpiComparison('kpiOperatingProfit', operatingProfit, previousOperatingProfit);
    }
    const dateTime = value => {
        if (!value) return '—';
        const raw = String(value);
        const date = /^\d{10,13}$/.test(raw) ? new Date(Number(raw) * (raw.length === 10 ? 1000 : 1)) : new Date(raw.replace(' ', 'T'));
        return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleString('id-ID', {day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit'});
    };
    const cashStatus = value => String(value || '—').replace(/_/g, ' ').toLowerCase();
    const cashStat = (label, value, note = '') => `<div class="an-modal-stat"><div class="an-modal-stat-label">${label}</div><div class="an-modal-stat-value">${value}</div>${note ? `<span class="an-modal-stat-note">${note}</span>` : ''}</div>`;
    const cashFeeDetail = (row, label, value, className = '') => `<span>${label}<strong class="${className}">${money(value)}</strong></span>`;

    function renderCashOrders() {
        const payload = cashPayload || {};
        const aggregate = payload.summary || {};
        const meta = payload.meta || {};
        const rows = Array.isArray(payload.data) ? payload.data : [];
        const isSettled = cashSettlement === 'settled';
        const isFeeFocus = cashFocus === 'fees';
        const totalOrders = Number(meta.total || aggregate.cash_order_count || 0).toLocaleString('id-ID');
        const feePercent = value => aggregate.cash_order_revenue > 0 ? `${(n(value) / n(aggregate.cash_order_revenue) * 100).toFixed(1)}% omzet order` : '0.0% omzet order';
        $('cashOrdersTitle').textContent = isFeeFocus ? 'Rincian fee marketplace actual' : 'Status pencairan order';
        $('cashOrdersSubtitle').textContent = `${from()} — ${to()} · ${isSettled ? 'settlement complete' : 'belum settlement complete'} · ${totalOrders} order`;
        $('cashOrdersSummary').innerHTML = isFeeFocus ? [
            cashStat(isSettled ? 'Total fee marketplace' : 'Fee marketplace tercatat', money(aggregate.cash_marketplace_fees), feePercent(aggregate.cash_marketplace_fees)),
            cashStat('Administrasi', money(aggregate.cash_commission_fee), feePercent(aggregate.cash_commission_fee)),
            cashStat('Layanan', money(aggregate.cash_service_fee), feePercent(aggregate.cash_service_fee)),
            cashStat('Transaksi', money(aggregate.cash_transaction_fee), feePercent(aggregate.cash_transaction_fee)),
        ].join('') : [
            cashStat(isSettled ? 'Omzet cair' : 'Omzet belum cair', money(isSettled ? aggregate.cash_payout : aggregate.cash_gross_sales)),
            cashStat(isSettled ? 'Fee marketplace actual' : 'Fee marketplace tercatat', money(aggregate.cash_marketplace_fees)),
            cashStat('Affiliate / AMS', money(aggregate.cash_affiliate_fees)),
            cashStat('Refund / adjustment', money(aggregate.cash_refund)),
        ].join('');
        const feeBreakdown = isFeeFocus ? `<div class="an-modal-fee-breakdown"><div><span>Total fee marketplace</span><strong>${money(aggregate.cash_marketplace_fees)}<small>${feePercent(aggregate.cash_marketplace_fees)}</small></strong></div><div><span>Administrasi</span><strong>${money(aggregate.cash_commission_fee)}<small>${feePercent(aggregate.cash_commission_fee)}</small></strong></div><div><span>Layanan</span><strong>${money(aggregate.cash_service_fee)}<small>${feePercent(aggregate.cash_service_fee)}</small></strong></div><div><span>Transaksi</span><strong>${money(aggregate.cash_transaction_fee)}<small>${feePercent(aggregate.cash_transaction_fee)}</small></strong></div><div><span>Asuransi</span><strong>${money(aggregate.cash_shipping_insurance_fee)}<small>${feePercent(aggregate.cash_shipping_insurance_fee)}</small></strong></div><div><span>Pajak escrow</span><strong>${money(aggregate.cash_escrow_tax)}<small>${feePercent(aggregate.cash_escrow_tax)}</small></strong></div></div>` : '';
        const table = rows.length ? `<div class="an-table-wrap"><table class="an-table an-cash-table"><thead><tr><th>Order</th><th>Toko &amp; status</th><th>Omzet order</th><th>Pembayaran pembeli</th><th>${isSettled ? 'Omzet cair' : 'Payout tercatat'}</th><th>Fee marketplace</th><th>Affiliate / AMS</th></tr></thead><tbody>${rows.map(row => { const payout = isSettled ? money(row.cash_payout) : (n(row.cash_payout) > 0 ? money(row.cash_payout) : 'Belum tersedia'); return `<tr><td><div class="an-cash-order">${esc(row.channel_order_id)}</div><span class="an-cash-meta">Order ${esc(dateTime(row.ordered_at))}</span><details class="an-cash-detail"><summary>Rincian fee</summary><div class="an-cash-detail-grid">${cashFeeDetail(row, 'Administrasi', row.commission_fee, 'fee')}${cashFeeDetail(row, 'Layanan', row.service_fee, 'fee')}${cashFeeDetail(row, 'Transaksi', row.transaction_fee, 'fee')}${cashFeeDetail(row, 'Asuransi', row.shipping_insurance_fee, 'fee')}${cashFeeDetail(row, 'Pajak escrow', row.escrow_tax, 'fee')}${cashFeeDetail(row, 'Affiliate fee', row.affiliate_fee_raw, 'affiliate')}${cashFeeDetail(row, 'Activity / AMS', row.activity_fee, 'affiliate')}${cashFeeDetail(row, 'Refund', row.refund)}${cashFeeDetail(row, 'Total fee', row.total_fees, 'fee')}</div></details></td><td><div>${esc(row.store_name)}</div><span class="an-cash-status">${esc(cashStatus(row.status))}</span><span class="an-cash-meta">${isSettled ? `Cair ${esc(dateTime(row.settlement_time))}` : 'Belum cair'}</span></td><td class="an-cash-money">${money(row.gross_sales)}</td><td class="an-cash-money">${money(row.buyer_payment_amount)}</td><td class="an-cash-money ${isSettled ? 'good' : ''}">${payout}</td><td class="an-cash-money fee">${money(row.marketplace_fee)}</td><td class="an-cash-money affiliate">${money(row.affiliate_fee)}</td></tr>`; }).join('')}</tbody></table></div>` : `<div class="an-empty">Tidak ada order ${isSettled ? 'yang sudah cair' : 'yang belum cair'} pada periode ini.</div>`;
        $('cashOrdersBody').innerHTML = feeBreakdown + table;
        const currentPage = Number(meta.current_page || cashPage);
        const lastPage = Number(meta.last_page || 1);
        $('cashOrdersPage').textContent = `Halaman ${currentPage} / ${lastPage}`;
        $('cashOrdersPrev').disabled = cashLoading || currentPage <= 1;
        $('cashOrdersNext').disabled = cashLoading || currentPage >= lastPage;
    }
    function closeCashOrders() {
        $('cashOrdersModal').classList.remove('is-open');
        $('cashOrdersModal').setAttribute('aria-hidden', 'true');
        document.body.classList.remove('an-modal-open');
    }
    function setCashSettlementTab(value) {
        cashSettlement = value === 'unsettled' ? 'unsettled' : 'settled';
        document.querySelectorAll('[data-cash-settlement]').forEach(button => {
            const active = button.dataset.cashSettlement === cashSettlement;
            button.classList.toggle('active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });
    }
    async function loadCashOrders() {
        if (cashLoading) return;
        cashLoading = true;
        $('cashOrdersBody').innerHTML = '<div class="an-empty">Memuat order cair…</div>';
        $('cashOrdersSummary').innerHTML = '<div class="an-empty">Memuat ringkasan…</div>';
        $('cashOrdersPrev').disabled = true;
        $('cashOrdersNext').disabled = true;
        try {
            const params = new URLSearchParams({ date_from: from(), date_to: to(), settlement: cashSettlement, page: String(cashPage), per_page: '50', _ts: Date.now().toString() });
            if (selectedStore()) params.set('store_id', selectedStore());
            cashPayload = await api('/api/marketplace/analytics-cash-orders?' + params.toString(), { cache: 'no-store' });
            renderCashOrders();
        } catch (e) {
            console.error('Cash order detail load failed', e);
            $('cashOrdersSummary').innerHTML = '<div class="an-error">Ringkasan order cair gagal dimuat.</div>';
            $('cashOrdersBody').innerHTML = '<div class="an-error">Detail order cair gagal dimuat.</div>';
            $('cashOrdersPage').textContent = '—';
        } finally {
            cashLoading = false;
            if (cashPayload) renderCashOrders();
        }
    }
    function openCashOrders() {
        cashFocus = 'payout';
        $('cashOrdersModal').classList.add('is-open');
        $('cashOrdersModal').setAttribute('aria-hidden', 'false');
        document.body.classList.add('an-modal-open');
        cashPage = 1;
        cashPayload = null;
        setCashSettlementTab('settled');
        $('cashOrdersSubtitle').textContent = `${from()} — ${to()} · settlement complete`;
        loadCashOrders();
    }
    function openFeeOrders() {
        closeReturnOrders();
        cashFocus = 'fees';
        $('cashOrdersModal').classList.add('is-open');
        $('cashOrdersModal').setAttribute('aria-hidden', 'false');
        document.body.classList.add('an-modal-open');
        cashPage = 1;
        cashPayload = null;
        setCashSettlementTab('settled');
        $('cashOrdersSubtitle').textContent = `${from()} — ${to()} · settlement complete`;
        loadCashOrders();
    }
    function renderReturnOrders() {
        const payload = returnPayload || {};
        const meta = payload.meta || {};
        const aggregate = payload.summary || {};
        const rows = Array.isArray(payload.data) ? payload.data : [];
        const isFailed = returnType === 'failed_delivery';
        $('returnOrdersSubtitle').textContent = `${from()} — ${to()} · ${isFailed ? 'exception pengiriman eksplisit' : 'data return / refund tersimpan'} · ${Number(meta.total || 0).toLocaleString('id-ID')} kasus`;
        $('returnOrdersSummary').innerHTML = [
            cashStat('Jumlah kasus', Number(aggregate.case_count || 0).toLocaleString('id-ID')),
            cashStat('Nilai kasus', money(aggregate.amount)),
            cashStat('Kategori', isFailed ? 'Pengiriman gagal' : 'Return / refund'),
            cashStat('Basis data', isFailed ? 'RTS / status gagal' : 'Marketplace returns'),
        ].join('');
        $('returnOrdersBody').innerHTML = rows.length ? `<div class="an-table-wrap"><table class="an-table an-cash-table"><thead><tr><th>Referensi</th><th>Order &amp; toko</th><th>Status</th><th>Nilai kasus</th><th>Waktu</th></tr></thead><tbody>${rows.map(row => { const kindLabel = row.kind === 'failed_delivery' ? 'Pengiriman gagal' : (row.kind === 'refund' ? 'Refund' : 'Return + refund'); const kindClass = row.kind === 'failed_delivery' ? '' : 'return'; return `<tr><td><div class="an-cash-order">${esc(row.reference)}</div><span class="an-exception-kind ${kindClass}">${kindLabel}</span><span class="an-cash-meta">${esc(row.reason || '—')}</span></td><td><div class="an-cash-order">${esc(row.order_sn)}</div><span class="an-cash-meta">${esc(row.store_name)}</span></td><td><span class="an-cash-status">${esc(cashStatus(row.status))}</span>${row.tracking_number ? `<span class="an-cash-meta">AWB ${esc(row.tracking_number)}</span>` : ''}</td><td class="an-cash-money fee">${money(row.amount)}</td><td><span class="an-cash-meta">${esc(dateTime(row.event_time))}</span></td></tr>`; }).join('')}</tbody></table></div>` : `<div class="an-empty">Belum ada data ${isFailed ? 'pengiriman gagal eksplisit' : 'return / refund'} pada periode ini.</div>`;
        const currentPage = Number(meta.current_page || returnPage);
        const lastPage = Number(meta.last_page || 1);
        $('returnOrdersPage').textContent = `Halaman ${currentPage} / ${lastPage}`;
        $('returnOrdersPrev').disabled = returnLoading || currentPage <= 1;
        $('returnOrdersNext').disabled = returnLoading || currentPage >= lastPage;
    }
    function closeReturnOrders() {
        $('returnOrdersModal').classList.remove('is-open');
        $('returnOrdersModal').setAttribute('aria-hidden', 'true');
        document.body.classList.remove('an-modal-open');
    }
    function setReturnTypeTab(value) {
        returnType = value === 'failed_delivery' ? 'failed_delivery' : 'return_refund';
        document.querySelectorAll('[data-return-type]').forEach(button => {
            const active = button.dataset.returnType === returnType;
            button.classList.toggle('active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });
    }
    async function loadReturnOrders() {
        if (returnLoading) return;
        returnLoading = true;
        $('returnOrdersBody').innerHTML = '<div class="an-empty">Memuat data return…</div>';
        $('returnOrdersSummary').innerHTML = '<div class="an-empty">Memuat ringkasan…</div>';
        $('returnOrdersPrev').disabled = true;
        $('returnOrdersNext').disabled = true;
        try {
            const params = new URLSearchParams({ date_from: from(), date_to: to(), type: returnType, page: String(returnPage), per_page: '50', _ts: Date.now().toString() });
            if (selectedStore()) params.set('store_id', selectedStore());
            returnPayload = await api('/api/marketplace/analytics-return-orders?' + params.toString(), { cache: 'no-store' });
            renderReturnOrders();
        } catch (e) {
            console.error('Return detail load failed', e);
            $('returnOrdersSummary').innerHTML = '<div class="an-error">Ringkasan return gagal dimuat.</div>';
            $('returnOrdersBody').innerHTML = '<div class="an-error">Data return gagal dimuat.</div>';
            $('returnOrdersPage').textContent = '—';
        } finally {
            returnLoading = false;
            if (returnPayload) renderReturnOrders();
        }
    }
    function openReturnOrders() {
        closeCashOrders();
        $('returnOrdersModal').classList.add('is-open');
        $('returnOrdersModal').setAttribute('aria-hidden', 'false');
        document.body.classList.add('an-modal-open');
        returnPage = 1;
        returnPayload = null;
        setReturnTypeTab('return_refund');
        $('returnOrdersSubtitle').textContent = `${from()} — ${to()} · data operasional`;
        loadReturnOrders();
    }
    function chartPoints(rows) {
        return (rows || []).map(row => ({
            date: row.date,
            rev: n(row.gmv || row.gross_sales),
            prof: n(row.operating_profit),
        }));
    }
    function loadChartLibrary() {
        if (window.Chart) return Promise.resolve(window.Chart);
        if (chartLibraryPromise) return chartLibraryPromise;
        chartLibraryPromise = new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
            script.async = true;
            script.onload = () => resolve(window.Chart);
            script.onerror = () => reject(new Error('Chart.js gagal dimuat'));
            document.head.appendChild(script);
        });
        return chartLibraryPromise;
    }
    function renderChart(rows, previousRows) {
        const renderToken = ++chartRenderToken;
        const points = chartPoints(rows), previousPoints = chartPoints(previousRows || []);
        if (!points.length && !previousPoints.length) { $('revenueChart').innerHTML = '<div class="an-empty">Belum ada order selesai untuk dibandingkan.</div>'; return; }
        const labelsSource = points.length ? points : previousPoints;
        const modeLabel = {prev_period:'periode lalu',prev_month:'tanggal sama bulan lalu',prev_quarter:'tanggal sama 3 bulan lalu',prev_year:'tanggal sama tahun lalu'}[$('anCompare')?.value] || 'periode lalu';
        $('chartCompareNote').textContent = `vs ${modeLabel}`;
        if (!window.Chart) {
            $('revenueChart').innerHTML = '<div class="an-empty">Menyiapkan grafik…</div>';
            loadChartLibrary().then(() => {
                if (renderToken === chartRenderToken) renderChart(rows, previousRows);
            }).catch(() => {
                $('revenueChart').innerHTML = '<div class="an-empty">Grafik belum tersedia.</div>';
            });
            return;
        }
        const labels = labelsSource.map(v => new Date(v.date + 'T00:00:00').toLocaleDateString('id-ID', { day:'2-digit', month:'short' }));
        $('revenueChart').innerHTML = '<div class="an-chart-canvas"><canvas id="anRevenueChart" aria-label="Grafik interaktif omzet dan laba"></canvas></div>';
        if (revenueChartInstance) revenueChartInstance.destroy();
        const canvas = $('anRevenueChart');
        const context = canvas.getContext('2d');
        const revenueGradient = context.createLinearGradient(0, 0, 0, 240);
        revenueGradient.addColorStop(0, 'rgba(22, 163, 74, .24)');
        revenueGradient.addColorStop(1, 'rgba(22, 163, 74, 0)');
        const chartMoneyTick = value => {
            const absolute = Math.abs(Number(value || 0));
            if (absolute >= 1000000) return `${value < 0 ? '-' : ''}Rp ${(absolute / 1000000).toFixed(1)} Jt`;
            if (absolute >= 1000) return `${value < 0 ? '-' : ''}Rp ${(absolute / 1000).toFixed(0)} Rb`;
            return `Rp ${Number(value || 0).toLocaleString('id-ID')}`;
        };
        revenueChartInstance = new Chart(context, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    { label:'Omzet kini', data:points.map(point => point.rev), borderColor:'#16a34a', backgroundColor:revenueGradient, fill:true, tension:.4, borderWidth:2, pointRadius:points.length <= 1 ? 4 : 0, pointHitRadius:15, pointHoverRadius:4, pointHoverBackgroundColor:'#16a34a' },
                    { label:'Estimasi profit', data:points.map(point => point.prof), borderColor:'#2563eb', backgroundColor:'transparent', fill:false, tension:.4, borderWidth:2, pointRadius:points.length <= 1 ? 4 : 0, pointHitRadius:15, pointHoverRadius:4, pointHoverBackgroundColor:'#2563eb' },
                    { label:`Omzet ${modeLabel}`, data:previousPoints.map(point => point.rev), borderColor:'#94a3b8', backgroundColor:'transparent', fill:false, tension:.4, borderWidth:1.6, borderDash:[6,5], pointRadius:0, pointHitRadius:15, pointHoverRadius:3, pointHoverBackgroundColor:'#94a3b8' },
                    { label:`Estimasi profit ${modeLabel}`, data:previousPoints.map(point => point.prof), borderColor:'#f59e0b', backgroundColor:'transparent', fill:false, tension:.4, borderWidth:1.6, borderDash:[6,5], pointRadius:0, pointHitRadius:15, pointHoverRadius:3, pointHoverBackgroundColor:'#f59e0b' },
                ],
            },
            options: {
                responsive:true,
                maintainAspectRatio:false,
                interaction:{ mode:'index', intersect:false },
                animation:{ duration:420, easing:'easeOutQuart' },
                plugins:{
                    legend:{ position:'top', labels:{ usePointStyle:true, boxWidth:6, padding:14, color:'#64748b', font:{ size:10, family:'Inter, sans-serif', weight:'700' } } },
                    tooltip:{ backgroundColor:'rgba(15,23,42,.95)', titleColor:'#f8fafc', bodyColor:'#f8fafc', borderColor:'rgba(255,255,255,.15)', borderWidth:1, padding:10, cornerRadius:8, displayColors:true, boxPadding:4, callbacks:{ label:context => `${context.dataset.label}: ${money(context.parsed.y)}` } },
                },
                scales:{
                    x:{ grid:{ display:false }, ticks:{ color:'#94a3b8', font:{ size:10, family:'Inter, sans-serif' }, maxRotation:0, autoSkip:true, maxTicksLimit:8 } },
                    y:{ beginAtZero:true, grid:{ color:'rgba(148,163,184,.16)', drawBorder:false }, ticks:{ color:'#94a3b8', padding:8, font:{ size:10, family:'Inter, sans-serif' }, callback:chartMoneyTick } },
                },
            },
        });
    }
    function renderCohortCharts(payload) {
        const renderToken = ++cohortChartRenderToken;
        const rows = Array.isArray(payload?.rows) ? payload.rows : [];
        const mode = payload?.mode === 'product' ? 'product' : 'customer';
        const metric = payload?.metric || (mode === 'product' ? 'revenue' : 'retention_pct');
        const periods = Array.from({ length: Math.max(0, Number(payload?.max_period || 0)) + 1 }, (_, index) => index);
        const empty = '<div class="an-empty">Belum ada data cohort untuk divisualisasikan.</div>';
        if (!rows.length) {
            if (cohortChartInstance) cohortChartInstance.destroy();
            if (cohortDistributionChartInstance) cohortDistributionChartInstance.destroy();
            cohortChartInstance = null;
            cohortDistributionChartInstance = null;
            $('anCohortCurveChart').innerHTML = empty;
            $('anCohortDistributionChart').innerHTML = empty;
            return;
        }
        if (!window.Chart) {
            $('anCohortCurveChart').innerHTML = '<div class="an-empty">Menyiapkan grafik…</div>';
            $('anCohortDistributionChart').innerHTML = '<div class="an-empty">Menyiapkan grafik…</div>';
            loadChartLibrary().then(() => {
                if (renderToken === cohortChartRenderToken) renderCohortCharts(payload);
            }).catch(() => {
                $('anCohortCurveChart').innerHTML = '<div class="an-empty">Grafik belum tersedia.</div>';
                $('anCohortDistributionChart').innerHTML = '<div class="an-empty">Grafik belum tersedia.</div>';
            });
            return;
        }
        const isPercent = ['retention_pct', 'gross_margin_pct'].includes(metric);
        const formatValue = value => isPercent ? `${Number(value || 0).toFixed(1)}%` : cohortFormat(value, metric);
        const tickValue = value => {
            if (isPercent) return `${Number(value || 0).toFixed(0)}%`;
            const absolute = Math.abs(Number(value || 0));
            if (absolute >= 1000000) return `${value < 0 ? '-' : ''}Rp ${(absolute / 1000000).toFixed(1)} Jt`;
            if (absolute >= 1000) return `${value < 0 ? '-' : ''}Rp ${(absolute / 1000).toFixed(0)} Rb`;
            return metric === 'qty_sold' || metric === 'orders' || metric === 'active_customers' ? Number(value || 0).toLocaleString('id-ID') : `Rp ${Number(value || 0).toLocaleString('id-ID')}`;
        };
        const averageByPeriod = periods.map(periodIndex => {
            const values = rows.map(row => Number(row.periods?.[periodIndex]?.[metric])).filter(value => Number.isFinite(value));
            return values.length ? values.reduce((sum, value) => sum + value, 0) / values.length : null;
        });
        const distribution = mode === 'customer'
            ? rows.map(row => ({ label: cohortMonthLabel(row.cohort_month), value: Number(row.cohort_size || 0) }))
            : Object.entries(rows.reduce((carry, row) => { carry[row.cohort_month] = (carry[row.cohort_month] || 0) + 1; return carry; }, {})).sort(([a], [b]) => a.localeCompare(b)).map(([label, value]) => ({ label: cohortMonthLabel(label), value }));
        $('anCohortCurveSubtitle').textContent = `${payload.metric_label || 'Metric'} · rata-rata ${mode === 'product' ? 'product cohort' : 'customer cohort'} per umur`;
        $('anCohortDistributionSubtitle').textContent = mode === 'customer' ? 'Ukuran customer cohort berdasarkan bulan transaksi pertama.' : 'Jumlah product cohort berdasarkan bulan transaksi pertama.';
        $('anCohortCurveChart').innerHTML = '<canvas id="anCohortCurveCanvas" aria-label="Grafik progression cohort"></canvas>';
        $('anCohortDistributionChart').innerHTML = '<canvas id="anCohortDistributionCanvas" aria-label="Grafik distribusi cohort"></canvas>';
        if (cohortChartInstance) cohortChartInstance.destroy();
        if (cohortDistributionChartInstance) cohortDistributionChartInstance.destroy();
        const curveColor = mode === 'product' ? '#d97706' : '#2563eb';
        cohortChartInstance = new Chart($('anCohortCurveCanvas').getContext('2d'), {
            type:'line',
            data:{ labels:periods.map(index => `M${index}`), datasets:[{ label:payload.metric_label || 'Metric', data:averageByPeriod, borderColor:curveColor, backgroundColor:mode === 'product' ? 'rgba(217,119,6,.14)' : 'rgba(37,99,235,.14)', fill:true, tension:.35, borderWidth:2.4, pointRadius:periods.length <= 8 ? 3 : 1, pointHoverRadius:5, spanGaps:false }] },
            options:{ responsive:true, maintainAspectRatio:false, interaction:{mode:'index', intersect:false}, animation:{duration:420, easing:'easeOutQuart'}, plugins:{ legend:{display:false}, tooltip:{ backgroundColor:'rgba(15,23,42,.95)', titleColor:'#f8fafc', bodyColor:'#f8fafc', borderColor:'rgba(255,255,255,.15)', borderWidth:1, padding:9, callbacks:{ label:context => `${context.dataset.label}: ${formatValue(context.parsed.y)}` } } }, scales:{ x:{grid:{display:false}, ticks:{color:'#94a3b8',font:{size:10,family:'Inter, sans-serif'}}}, y:{grid:{color:'rgba(148,163,184,.16)'}, ticks:{color:'#94a3b8',font:{size:10,family:'Inter, sans-serif'}, callback:tickValue}} } },
        });
        cohortDistributionChartInstance = new Chart($('anCohortDistributionCanvas').getContext('2d'), {
            type:'bar',
            data:{ labels:distribution.map(item => item.label), datasets:[{ label:mode === 'customer' ? 'Customers' : 'Product cohorts', data:distribution.map(item => item.value), backgroundColor:mode === 'product' ? 'rgba(217,119,6,.72)' : 'rgba(22,163,74,.72)', borderRadius:5, maxBarThickness:32 }] },
            options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{display:false}, tooltip:{ backgroundColor:'rgba(15,23,42,.95)', titleColor:'#f8fafc', bodyColor:'#f8fafc', borderColor:'rgba(255,255,255,.15)', borderWidth:1, padding:9, callbacks:{ label:context => `${context.dataset.label}: ${Number(context.parsed.y || 0).toLocaleString('id-ID')}` } } }, scales:{ x:{grid:{display:false}, ticks:{color:'#94a3b8',font:{size:10,family:'Inter, sans-serif'},maxRotation:0,autoSkip:true,maxTicksLimit:8}}, y:{beginAtZero:true, grid:{color:'rgba(148,163,184,.16)'}, ticks:{color:'#94a3b8',font:{size:10,family:'Inter, sans-serif'},precision:0}} } },
        });
    }
    function renderFunnel() {
        const current = summary?.current || {};
        const max = Math.max(Number(current.order_total || 0), 1);
        const data = [
            ['Order masuk', Number(current.order_total || 0), Number(current.order_total || 0)],
            ['Order dikirim', Number(current.shipped_count || 0), Number(current.shipped_count || 0)],
            ['Order selesai', Number(current.completed_count || 0), Number(current.completed_count || 0)],
            ['Order dibatalkan', Number(current.cancelled_count || 0), Number(current.cancelled_count || 0)],
            ['Return / refund', Number(current.return_refund_count || 0), Number(current.return_refund_count || 0)],
            ['Laba operasional', money(current.operating_profit), Math.max(Number(current.operating_profit || 0), 0)],
        ];
        $('salesFunnel').innerHTML = data.map(([label,value,amount]) => `<div class="an-funnel-row"><span>${label}</span><div class="an-funnel-track"><span style="width:${Math.max(5,Math.round(amount / max * 100))}%"></span></div><strong class="an-funnel-value">${typeof value === 'number' ? value.toLocaleString('id-ID') : value}</strong></div>`).join('');
    }
    function renderFinancePulse() {
        const current = summary?.current || {};
        const previous = summary?.previous || {};
        const adSpend = Number(current.ad_cost || 0);
        const previousAdSpend = Number(previous.ad_cost || 0);
        const cogs = Number(current.hpp_total ?? current.hpp ?? 0);
        const previousCogs = Number(previous.hpp_total ?? previous.hpp ?? 0);
        const estimatedNetProfit = Number(current.estimated_profit ?? 0);
        const previousEstimatedNetProfit = Number(previous.estimated_profit ?? 0);
        const roiBase = cogs + adSpend;
        const previousRoiBase = previousCogs + previousAdSpend;
        const roi = roiBase > 0 ? estimatedNetProfit / roiBase * 100 : 0;
        const previousRoi = previousRoiBase > 0 ? previousEstimatedNetProfit / previousRoiBase * 100 : 0;
        const roe = cogs > 0 ? estimatedNetProfit / cogs * 100 : 0;
        const previousRoe = previousCogs > 0 ? previousEstimatedNetProfit / previousCogs * 100 : 0;
        const compare = (value, baseline, formatter = money) => {
            const currentValue = Number(value || 0);
            const previousValue = Number(baseline || 0);
            if (previousValue === 0) {
                const className = currentValue > 0 ? 'good' : '';
                return `<span class="an-pulse-compare ${className}"><i class="bi bi-arrow-left-right" aria-hidden="true"></i> ${currentValue === 0 ? '0,0% vs lalu' : 'baru'}</span>`;
            }
            const change = (currentValue - previousValue) / Math.abs(previousValue) * 100;
            const className = change > 0 ? 'good' : (change < 0 ? 'bad' : '');
            return `<span class="an-pulse-compare ${className}"><i class="bi bi-arrow-left-right" aria-hidden="true"></i> ${formatter(previousValue)} · ${change > 0 ? '+' : ''}${change.toFixed(1).replace('.', ',')}%</span>`;
        };
        const cards = [
            ['bi-megaphone', 'Spend iklan', money(adSpend), compare(adSpend, previousAdSpend), 'Biaya iklan periode ini'],
            ['bi-box-seam', 'COGS', money(cogs), compare(cogs, previousCogs), 'Total HPP / COGS periode ini'],
            ['bi-piggy-bank', 'Net Profit', money(estimatedNetProfit), compare(estimatedNetProfit, previousEstimatedNetProfit), 'Net profit setelah fee, return/refund, COGS, dan iklan'],
            ['bi-graph-up-arrow', 'ROI', `${roi.toFixed(1).replace('.', ',')}%`, compare(roi, previousRoi, value => `${Number(value || 0).toFixed(1).replace('.', ',')}%`), 'Net profit ÷ (COGS + spend iklan)'],
            ['bi-person-check', 'ROE estimasi', `${roe.toFixed(1).replace('.', ',')}%`, compare(roe, previousRoe, value => `${Number(value || 0).toFixed(1).replace('.', ',')}%`), 'Proxy modal barang: net profit ÷ COGS'],
        ];
        $('anFinancePulse').innerHTML = cards.map(([icon, label, value, note, title]) => `<div class="an-pulse" title="${title}"><div class="an-pulse-label"><i class="bi ${icon} me-1" aria-hidden="true"></i>${label}</div><div class="an-pulse-value">${value}</div><div class="an-pulse-note">${note}</div></div>`).join('');
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
            ['Fee rate (actual)', list.reduce((sum, store) => sum + Number(store.marketplace_fee_estimate || 0), 0) / storeBase * 100],
            ['HPP rate', list.reduce((sum, store) => sum + Number(store.hpp || 0), 0) / storeBase * 100],
            ['Ad rate', list.reduce((sum, store) => sum + Number(store.ad_cost || 0), 0) / storeBase * 100],
        ].map(([label,value]) => `<div class="an-health-row"><span>${label}<small>terhadap omzet</small></span><div class="an-health-track"><span style="width:${Math.min(100, Math.max(0, value))}%"></span></div><strong>${Number(value || 0).toFixed(1)}%</strong></div>`).join('');
        $('storeBody').innerHTML = list.length ? list.map(s=>`<tr><td style="text-align:left;font-weight:850;color:#0f172a">${esc(s.store_name || 'Tanpa toko')}</td><td>${Number(s.order_total || 0).toLocaleString('id-ID')}</td><td>${Number(s.completed_count || 0).toLocaleString('id-ID')} <small style="color:#94a3b8">(${pct(s.completed_count,s.order_total)})</small></td><td style="color:${s.cancelled_count?'#dc2626':'inherit'}">${Number(s.cancelled_count || 0).toLocaleString('id-ID')}</td><td style="font-weight:900">${money(s.gmv)}<span class="an-table-subline">AOV ${money(s.order_total ? s.gmv / s.order_total : 0)}</span></td><td style="font-weight:900;color:${s.operating_profit>=0?'#15803d':'#dc2626'}">${money(s.operating_profit)}<span class="an-table-subline">Profit verified · Iklan incl. PPN ${money(s.ad_cost)}</span></td></tr>`).join('') : '<tr><td colspan="6"><div class="an-empty">Belum ada data toko siap profit.</div></td></tr>';
    }
    function renderCosts() {
        const current = summary?.current || {};
        const orderRevenue = Number(current.cash_order_revenue || 0) + Number(current.cash_unsettled_order_revenue || 0);
        const base = Math.max(orderRevenue || Number(current.gmv || current.gross_sales || 0), 1);
        const rows = [
            [`Fee marketplace (estimasi ${(Number(current.marketplace_fee_estimate_rate || 0)).toFixed(1)}% actual)`, current.marketplace_fee_estimate],
            ['Refund / adjustment', current.return_refund_amount ?? current.refund],
            ['Biaya iklan (incl. PPN 11%)', current.ad_cost],
            ['HPP total', current.hpp_total ?? current.hpp],
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
        const actualAffiliateFee = Number(current.cash_affiliate_fees ?? current.affiliate_fees_actual ?? 0);
        const grossOrderRevenue = Number(current.cash_order_revenue || 0) + Number(current.cash_unsettled_order_revenue || 0) || Number(current.gmv || 0);
        const netOrderRevenue = Math.max(0, grossOrderRevenue - Number(current.return_refund_amount ?? 0));
        const totalHpp = Number(current.hpp_total ?? current.hpp ?? 0);
        const adCost = Number(current.ad_cost || 0);
        const buyerPayment = Number(current.cash_gross_sales || 0) + Number(current.cash_unsettled_gross_sales || 0);
        const buyerPaymentOrders = Number(current.cash_order_count || 0) + Number(current.cash_unsettled_order_count || 0);
        const apc = buyerPaymentOrders > 0 ? buyerPayment / buyerPaymentOrders : 0;
        const aovNet = Number(current.order_total || 0) > 0 ? netOrderRevenue / Number(current.order_total) : 0;
        const actualFeeRate = Number(current.cash_order_revenue || 0) > 0
            ? Number(current.cash_marketplace_fees || 0) / Number(current.cash_order_revenue)
            : 0.21;
        const estimatedProfitFallback = netOrderRevenue - (grossOrderRevenue * actualFeeRate) - totalHpp - Number(current.ad_cost || 0);
        const totalOrder = Number(current.order_total || 0);
        const totalProducts = Number(current.product_qty ?? current.qty ?? 0);
        const cancelledOrders = Number(current.cancelled_count || 0);
        const previous = summary?.previous || {};
        const previousGrossOrderRevenue = Number(previous.cash_order_revenue || 0) + Number(previous.cash_unsettled_order_revenue || 0) || Number(previous.gmv || 0);
        const previousNetOrderRevenue = Math.max(0, previousGrossOrderRevenue - Number(previous.return_refund_amount ?? 0));
        const previousHpp = Number(previous.hpp_total ?? previous.hpp ?? 0);
        const previousAdCost = Number(previous.ad_cost || 0);
        const previousFeeRate = Number(previous.cash_order_revenue || 0) > 0
            ? Number(previous.cash_marketplace_fees || 0) / Number(previous.cash_order_revenue)
            : 0.21;
        const previousEstimatedProfitFallback = previousNetOrderRevenue - (previousGrossOrderRevenue * previousFeeRate) - previousHpp - Number(previous.ad_cost || 0);
        const previousAovNet = Number(previous.order_total || 0) > 0 ? previousNetOrderRevenue / Number(previous.order_total) : 0;
        const estimatedProfit = Number(current.estimated_profit ?? estimatedProfitFallback);
        const previousEstimatedProfit = Number(previous.estimated_profit ?? previousEstimatedProfitFallback);
        const estimatedMargin = Number(current.estimated_profit_margin ?? (netOrderRevenue > 0 ? estimatedProfit / netOrderRevenue * 100 : 0));
        const previousEstimatedMargin = Number(previous.estimated_profit_margin ?? (previousNetOrderRevenue > 0 ? previousEstimatedProfit / previousNetOrderRevenue * 100 : 0));
        const previousBuyerPayment = Number(previous.cash_gross_sales || 0) + Number(previous.cash_unsettled_gross_sales || 0);
        const previousBuyerPaymentOrders = Number(previous.cash_order_count || 0) + Number(previous.cash_unsettled_order_count || 0);
        const previousApc = previousBuyerPaymentOrders > 0 ? previousBuyerPayment / previousBuyerPaymentOrders : 0;
        const pulseChange = (value, previousValue, formatter = value => String(value)) => {
            const currentValue = Number(value || 0);
            const baseline = Number(previousValue || 0);
            const previousText = formatter(baseline);
            if (baseline === 0) return { text: `<i class="bi bi-arrow-left-right" title="Periode lalu" aria-hidden="true"></i> ${previousText} · ${currentValue === 0 ? '0.0% vs lalu' : 'baru'}`, className: currentValue > 0 ? 'good' : '' };
            const change = (currentValue - baseline) / Math.abs(baseline) * 100;
            return { text: `<i class="bi bi-arrow-left-right" title="Periode lalu" aria-hidden="true"></i> ${previousText} · ${change > 0 ? '+' : ''}${change.toFixed(1)}% vs lalu`, className: change > 0 ? 'good' : (change < 0 ? 'bad' : '') };
        };
        const countText = value => Number(value || 0).toLocaleString('id-ID');
        const moneyText = value => money(value);
        const totalOrderChange = pulseChange(totalOrder, previous.order_total, countText);
        const totalProductsChange = pulseChange(totalProducts, previous.product_qty ?? previous.qty, countText);
        const netRevenueChange = pulseChange(netOrderRevenue, previousNetOrderRevenue, moneyText);
        const adCostChange = pulseChange(adCost, previousAdCost, moneyText);
        const aovChange = pulseChange(aovNet, previousAovNet, moneyText);
        const apcChange = pulseChange(apc, previousApc, moneyText);
        const estimatedProfitChange = pulseChange(estimatedProfit, previousEstimatedProfit, moneyText);
        const percentText = value => `${Number(value || 0).toFixed(1)}%`;
        const estimatedMarginChange = pulseChange(estimatedMargin, previousEstimatedMargin, percentText);
        const total = Math.max(Number(quality.total || 0), 1);
        const readyRate = Number(quality.ready || 0) / total * 100;
        const topStores = [...(summary?.stores || [])].sort((a,b) => Number(b.gross_sales || 0) - Number(a.gross_sales || 0)).slice(0, 5);
        const pulse = [
            ['Total order', totalOrder.toLocaleString('id-ID'), { text: `batal ${cancelledOrders.toLocaleString('id-ID')} · ${totalOrderChange.text}`, className: totalOrderChange.className }, 'orders'],
            ['Produk terjual', totalProducts.toLocaleString('id-ID'), { text: `unit · ${totalProductsChange.text}`, className: totalProductsChange.className }, 'products'],
            ['Omzet net', money(netOrderRevenue), netRevenueChange, 'revenue'],
            ['Biaya iklan incl. PPN', money(adCost), adCostChange, 'ads'],
            ['AOV net', money(aovNet), aovChange, 'aov'],
            ['APC pembeli', money(apc), { text: `cair + pending · ${apcChange.text}`, className: apcChange.className }, 'apc'],
            ['Estimasi profit', money(estimatedProfit), estimatedProfitChange, 'estimated-profit'],
            ['Margin estimasi', `${estimatedMargin.toFixed(1)}%`, estimatedMarginChange, 'estimated-margin'],
        ];
        $('anPulseGrid').innerHTML = pulse.map(([label,value,change,metric]) => `<div class="an-pulse an-pulse-action ${selectedPulseMetric === metric ? 'is-active' : ''}" data-pulse-metric="${metric}" role="button" tabindex="0" title="Bandingkan grafik dengan tanggal sama bulan lalu"><div class="an-pulse-label">${label}</div><div class="an-pulse-value">${value}</div><div class="an-pulse-note ${change.className}">${change.text}</div></div>`).join('');
        const scoreClass = healthClass(readyRate);
        $('anOverallScore').className = `an-health-score ${scoreClass}`;
        $('anOverallScore').textContent = `Data ready ${readyRate.toFixed(0)}%`;
        const health = [
            ['Completion rate', Number(current.completion_rate || 0), false, `${Number(current.completed_count || 0).toLocaleString('id-ID')} selesai`],
            ['Cancellation', Number(current.cancel_rate || 0), true, `${Number(current.cancelled_count || 0).toLocaleString('id-ID')} dibatalkan`],
            ['Data readiness', readyRate, false, `${Number(quality.ready || 0).toLocaleString('id-ID')} siap profit · ${Number(quality.waiting || 0).toLocaleString('id-ID')} menunggu selesai`],
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
        const settledGross = Number(current.cash_order_revenue ?? current.cash_gross_sales ?? 0);
        const unsettledGross = Number(current.cash_unsettled_order_revenue ?? Math.max(0, Number(current.gmv || 0) - settledGross));
        const totalOmzet = settledGross + unsettledGross || Number(current.gmv || 0);
        const settledPct = totalOmzet > 0 ? settledGross / totalOmzet * 100 : 0;
        const unsettledPct = totalOmzet > 0 ? unsettledGross / totalOmzet * 100 : 0;
        const economicsBase = totalOmzet || cashPayout;
        const cashEconomicsBase = cashPayout;
        const feeEconomicsBase = Number(current.cash_order_revenue || 0);
        const rawHppTotal = Number(current.hpp_total ?? current.hpp ?? 0);
        const rawHppSettled = Number(current.hpp_settled ?? 0);
        const rawHppUnsettled = Number(current.hpp_unsettled ?? Math.max(0, rawHppTotal - rawHppSettled));
        const hppReturnRefund = Number(current.hpp_return_refund || 0);
        const hppReturnRefundSettled = Number(current.hpp_return_refund_settled || 0);
        const hppReturnRefundUnsettled = Number(current.hpp_return_refund_unsettled || 0);
        const hppSettled = Math.max(0, rawHppSettled - hppReturnRefundSettled);
        const hppUnsettled = Math.max(0, rawHppUnsettled - hppReturnRefundUnsettled);
        const hppTotal = hppSettled + hppUnsettled + hppReturnRefund || rawHppTotal;
        const hppSettledPct = hppTotal > 0 ? hppSettled / hppTotal * 100 : 0;
        const hppUnsettledPct = hppTotal > 0 ? hppUnsettled / hppTotal * 100 : 0;
        const hppReturnRefundPct = hppTotal > 0 ? hppReturnRefund / hppTotal * 100 : 0;
        const returnRefundAmount = Number(current.return_refund_amount ?? current.cash_refund ?? current.refund ?? 0);
        const economics = [
            ['Omzet', totalOmzet, 100, '#16a34a'],
            ['Return / refund', returnRefundAmount, economicsBase ? returnRefundAmount / economicsBase * 100 : 0, '#dc2626'],
            ['Fee marketplace actual · omzet marketplace', actualCashFee, feeEconomicsBase ? actualCashFee / feeEconomicsBase * 100 : 0, '#d97706'],
            ['Affiliate / AMS actual · omzet cair', actualAffiliateFee, cashEconomicsBase ? actualAffiliateFee / cashEconomicsBase * 100 : 0, '#a855f7'],
            ['HPP', hppTotal, hppTotal > 0 ? 100 : 0, '#64748b'],
            ['Biaya iklan incl. PPN 11%', Number(current.ad_cost || 0), economicsBase ? Number(current.ad_cost || 0) / economicsBase * 100 : 0, '#dc2626'],
        ];
        $('anEconomics').innerHTML = economics.map(([label,amount,rate,color], index) => { const isOmzet = index === 0; const isReturn = index === 1; const isFee = index === 2; const isAffiliate = index === 3; const isHpp = index === 4; const interactive = isOmzet || isReturn || isFee; const progress = isOmzet ? `<div class="an-omzet-progress"><span class="an-omzet-progress-settled" style="width:${settledPct}%"></span><span class="an-omzet-progress-unsettled" style="width:${unsettledPct}%"></span></div><div class="an-omzet-progress-meta"><span>Cair ${money(settledGross)}</span><span>Belum cair ${money(unsettledGross)}</span></div>` : (isHpp ? `<div class="an-omzet-progress"><span class="an-omzet-progress-settled" style="width:${hppSettledPct}%"></span><span class="an-omzet-progress-unsettled" style="width:${hppUnsettledPct}%"></span><span class="an-hpp-progress-return" style="width:${Math.min(100, Math.max(0, hppReturnRefundPct))}%"></span></div><div class="an-omzet-progress-meta an-hpp-progress-meta"><span>HPP cair ${money(hppSettled)} · ${hppSettledPct.toFixed(1)}%</span><span>HPP belum cair ${money(hppUnsettled)} · ${hppUnsettledPct.toFixed(1)}%</span><span>Return/refund ${money(hppReturnRefund)} · ${hppReturnRefundPct.toFixed(1)}%</span></div>` : `<div class="an-contribution-bar"><span style="width:${Math.min(100, Math.max(0, rate))}%;background:${color}"></span></div>`); const actionAttrs = isOmzet ? 'role="button" tabindex="0" aria-controls="cashOrdersModal" aria-label="Lihat status pencairan order" data-open-cash-orders' : (isReturn ? 'role="button" tabindex="0" aria-controls="returnOrdersModal" aria-label="Lihat return dan pengiriman gagal" data-open-return-orders' : (isFee ? 'role="button" tabindex="0" aria-controls="cashOrdersModal" aria-label="Lihat rincian fee marketplace" data-open-fee-orders' : '')); const actionHint = interactive ? '<span class="an-economics-action">Lihat detail</span>' : ''; const basisLabel = isFee ? 'omzet order' : (isAffiliate ? 'omzet cair' : 'total omzet'); return `<div class="an-contribution-row an-economics-row ${interactive ? 'an-economics-clickable' : ''}" ${actionAttrs}><div class="an-economics-copy"><div class="an-contribution-name">${label}</div>${progress}</div><div class="an-contribution-value an-economics-value">${money(amount)}<span class="an-table-subline">${isOmzet ? 'total omzet' : isHpp ? '100.0% dari total HPP' : `${rate.toFixed(1)}% dari ${basisLabel}`}${actionHint}</span></div></div>`; }).join('');
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
        renderFinancePulse();
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
        if (storesLoaded) return stores;
        stores = await api('/api/marketplace/stores').catch(() => []);
        storesLoaded = true;
        fillStores();
        return stores;
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
    const cohortMetricOptions = {
        customer: [
            ['retention_pct', 'Retention %'], ['active_customers', 'Active Customers'], ['orders', 'Orders'], ['qty_sold', 'Qty Sold'], ['revenue', 'Revenue'],
        ],
        product: [
            ['qty_sold', 'Qty Sold'], ['revenue', 'Revenue'], ['gross_profit', 'Gross Profit'], ['gross_margin_pct', 'Gross Margin %'], ['net_profit', 'Net Profit'],
        ],
    };
    const cohortMetricHints = {
        retention_pct: 'Persentase customer dari cohort yang kembali aktif pada periode tersebut.',
        active_customers: 'Jumlah customer unik yang aktif pada periode tersebut.',
        orders: 'Jumlah order yang tercatat dari cohort pada periode tersebut.',
        qty_sold: 'Total unit produk yang terjual pada periode tersebut.',
        revenue: 'Nilai omzet kotor dari transaksi pada periode tersebut.',
        gross_profit: 'Omzet dikurangi fee marketplace dan HPP yang ter-cover.',
        gross_margin_pct: 'Gross profit sebagai persentase dari revenue.',
        net_profit: 'Gross profit setelah alokasi biaya iklan.',
    };
    function syncCohortMetricOptions() {
        const mode = $('anCohortMode').value === 'product' ? 'product' : 'customer';
        const current = $('anCohortMetric').value;
        const options = cohortMetricOptions[mode];
        $('anCohortMetric').innerHTML = options.map(([value, label]) => `<option value="${value}">${label}</option>`).join('');
        $('anCohortMetric').value = options.some(([value]) => value === current) ? current : options[0][0];
        $('anCohortMetricHint').textContent = cohortMetricHints[$('anCohortMetric').value] || 'Pilih metric untuk melihat definisinya.';
    }
    function setCohortSelectOptions(id, placeholder, values) {
        const select = $(id);
        const current = String(select.value || '');
        const uniqueValues = [...new Set((Array.isArray(values) ? values : []).map(value => String(value || '').trim()).filter(Boolean))].sort((a, b) => a.localeCompare(b, 'id'));
        if (current && !uniqueValues.includes(current)) uniqueValues.unshift(current);
        select.innerHTML = `<option value="">${placeholder}</option>${uniqueValues.map(value => `<option value="${esc(value)}">${esc(value)}</option>`).join('')}`;
        select.value = current;
    }
    async function loadCohortOptions(force = false) {
        const key = [from(), to(), selectedStore(), $('anCohortMarketplace').value].join('|');
        if (!force && cohortOptionsKey === key) return;
        const params = new URLSearchParams({ date_from: from(), date_to: to(), _ts: Date.now().toString() });
        if (selectedStore()) params.set('store_id', selectedStore());
        if ($('anCohortMarketplace').value) params.set('marketplace', $('anCohortMarketplace').value);
        try {
            const result = await api('/api/marketplace/analytics-cohort-options?' + params.toString(), { cache:'no-store' });
            cohortOptionsKey = key;
            setCohortSelectOptions('anCohortMarketplace', 'All marketplaces', result?.marketplaces);
            setCohortSelectOptions('anCohortCategory', 'All categories', result?.categories);
            setCohortSelectOptions('anCohortProduct', 'All products', result?.products);
            setCohortSelectOptions('anCohortSku', 'All SKUs', result?.skus);
        } catch (error) {
            console.warn('Cohort filter options failed', error);
        }
    }
    const cohortFormat = (value, metric) => {
        if (value === null || value === undefined || value === '') return '—';
        if (metric === 'retention_pct' || metric === 'gross_margin_pct') return `${Number(value).toFixed(1)}%`;
        if (['revenue', 'gross_profit', 'net_profit'].includes(metric)) return money(value);
        return Number(value).toLocaleString('id-ID');
    };
    const cohortMonthLabel = value => {
        const date = new Date(`${value}-01T00:00:00`);
        return Number.isNaN(date.getTime()) ? value : date.toLocaleDateString('id-ID', { month:'short', year:'numeric' });
    };
    const cohortParams = () => {
        const params = new URLSearchParams({ date_from: from(), date_to: to(), mode: $('anCohortMode').value, metric: $('anCohortMetric').value, _ts: Date.now().toString() });
        if (selectedStore()) params.set('store_id', selectedStore());
        [['marketplace','anCohortMarketplace'],['category','anCohortCategory'],['product','anCohortProduct'],['sku','anCohortSku']].forEach(([key, id]) => {
            const value = String($(id)?.value || '').trim();
            if (value) params.set(key, value);
        });
        return params;
    };
    function renderCohortActiveFilters() {
        const activeStore = stores.find(store => String(store.id ?? store.store_id) === selectedStore());
        const filters = [
            ['Periode', `${from()} — ${to()}`],
            ['Toko', activeStore?.store_name || activeStore?.name || 'Semua toko'],
            ['Marketplace', $('anCohortMarketplace').value.trim()],
            ['Kategori', $('anCohortCategory').value.trim()],
            ['Produk', $('anCohortProduct').value.trim()],
            ['SKU', $('anCohortSku').value.trim()],
        ].filter(([, value]) => value);
        $('anCohortActiveFilters').innerHTML = `<span class="an-cohort-filter-caption">Active scope</span>${filters.map(([label, value]) => `<span class="an-filter-chip">${esc(label)}: <strong>${esc(value)}</strong></span>`).join('')}`;
    }
    function renderCohortKpis(payload) {
        const summary = payload?.summary || {};
        const isProduct = payload?.mode === 'product';
        const periodLabel = `${from()} — ${to()}`;
        const cards = isProduct
            ? [
                ['Product cohorts', Number(summary.product_count ?? 0).toLocaleString('id-ID'), 'Unique products in scope'],
                ['Primary metric', payload.metric_label || '—', 'Metric aktif'],
                ['Revenue in scope', money(summary.revenue || 0), 'Across selected cohorts'],
                ['Financial coverage', `${Number(summary.avg_financial_coverage_pct || 0).toFixed(1)}%`, 'Average settlement coverage'],
            ]
            : [
                ['Customer cohorts', Number(summary.cohort_count ?? 0).toLocaleString('id-ID'), 'First transaction month'],
                ['Primary metric', payload.metric_label || '—', 'Metric aktif'],
                ['Latest activity', Number(summary.latest_active_customers || 0).toLocaleString('id-ID'), 'Active customers in latest month'],
                ['Data confidence', summary.avg_m1_retention_pct === null ? '—' : `${Number(summary.avg_m1_retention_pct).toFixed(1)}%`, 'Average M1 retention'],
            ];
        $('anCohortKpis').innerHTML = cards.map(([label, value, note], index) => `<div class="an-cohort-summary-card ${index === 0 ? 'is-primary' : ''}"><span class="an-cohort-summary-label">${esc(label)}</span><strong class="an-cohort-summary-value">${esc(value)}</strong><span class="an-cohort-summary-note">${esc(note)}</span></div>`).join('');
        $('anCohortPeriodLabel').textContent = periodLabel;
        renderCohortActiveFilters();
        $('anCohortMatrixSubtitle').textContent = `${payload.metric_label || 'Metric'} · ${Number(payload?.rows?.length || 0).toLocaleString('id-ID')} ${isProduct ? 'product cohorts' : 'customer cohorts'} · klik sel untuk detail`;
        const notes = Array.isArray(payload?.notes) ? Object.values(payload.notes) : [];
        $('anCohortNote').textContent = notes.join(' · ') || 'Agregasi cohort mengikuti filter periode dan toko di halaman ini.';
    }
    function cohortCell(detail, metric, isProduct, maxValue, context = {}) {
        if (!detail) return '<span class="an-cohort-cell is-empty">—</span>';
        const value = detail[metric];
        if (value === null || value === undefined) return '<span class="an-cohort-cell is-empty">—</span>';
        const intensity = Math.min(.46, .08 + (Math.abs(Number(value || 0)) / Math.max(maxValue, 1)) * .38);
        const encoded = esc(JSON.stringify({ ...context, ...detail, metric, metric_label: payloadMetricLabel(metric), is_product: isProduct }));
        const supportingValue = isProduct ? `Rev ${money(detail.revenue)}` : `${Number(detail.active_customers || 0).toLocaleString('id-ID')} active`;
        const cellLabel = `${payloadMetricLabel(metric)} ${cohortFormat(value, metric)}, ${supportingValue}`;
        return `<button type="button" class="an-cohort-cell ${isProduct ? 'product' : ''}" style="--heat:${intensity}" title="${esc(cellLabel)}" aria-label="${esc(cellLabel)}" data-cohort-detail="${encoded}"><span class="an-cohort-cell-value">${cohortFormat(value, metric)}</span><span class="an-cohort-cell-sub">${esc(supportingValue)}</span></button>`;
    }
    const payloadMetricLabel = metric => ({retention_pct:'Retention %',active_customers:'Active Customers',orders:'Orders',qty_sold:'Qty Sold',revenue:'Revenue',gross_profit:'Gross Profit',gross_margin_pct:'Gross Margin %',net_profit:'Net Profit'})[metric] || metric;
    const cohortHeader = (label, sub = '') => `<th>${label}${sub ? `<small>${sub}</small>` : ''}</th>`;
    function renderCohortTable(payload) {
        const mode = payload?.mode === 'product' ? 'product' : 'customer';
        const metric = payload?.metric || (mode === 'product' ? 'revenue' : 'retention_pct');
        const maxPeriod = Math.max(0, Number(payload?.max_period || 0));
        const periods = Array.from({ length: maxPeriod + 1 }, (_, index) => index);
        $('anCohortTable').classList.toggle('is-product', mode === 'product');
        $('anCohortTable').classList.toggle('is-dense', periods.length > 8);
        if (mode === 'customer') {
            $('anCohortHead').innerHTML = `<tr>${cohortHeader('Cohort', 'first transaction')}${cohortHeader('Base', 'customers')}${periods.map(index => cohortHeader(`M${index}`, index === 0 ? 'same month' : `+${index} month`)).join('')}</tr>`;
            const maxValue = Math.max(...(payload?.rows || []).flatMap(row => Object.values(row.periods || {}).map(period => Number(period[metric] || 0))), 1);
            $('anCohortBody').innerHTML = payload?.rows?.length ? payload.rows.map(row => `<tr><td class="an-cohort-sticky"><span class="an-cohort-row-title">${esc(cohortMonthLabel(row.cohort_month))}</span><span class="an-cohort-row-sub">First transaction month</span></td><td><span class="an-cohort-base-value">${Number(row.cohort_size || 0).toLocaleString('id-ID')}</span><span class="an-cohort-row-sub">customers</span></td>${periods.map(index => cohortCell(row.periods?.[index], metric, false, maxValue, { cohort_month:row.cohort_month })).join('')}</tr>`).join('') : `<tr><td colspan="${periods.length + 2}"><div class="an-empty">Tidak ada cohort customer untuk filter ini.</div></td></tr>`;
            return;
        }
        $('anCohortHead').innerHTML = `<tr>${cohortHeader('Product', 'catalog item')}${cohortHeader('Cohort', 'first transaction')}${periods.map(index => cohortHeader(`M${index}`, index === 0 ? 'same month' : `+${index} month`)).join('')}</tr>`;
        const maxValue = Math.max(...(payload?.rows || []).flatMap(row => Object.values(row.periods || {}).map(period => Number(period[metric] || 0))), 1);
        $('anCohortBody').innerHTML = payload?.rows?.length ? payload.rows.map(row => `<tr><td class="an-cohort-sticky an-cohort-product-cell"><span class="an-cohort-row-title">${esc(row.product_name)}</span><span class="an-cohort-row-sub">${esc(row.sku)} · ${esc(row.category)}</span></td><td>${esc(cohortMonthLabel(row.cohort_month))}</td>${periods.map(index => cohortCell(row.periods?.[index], metric, true, maxValue, { cohort_month:row.cohort_month, product_name:row.product_name, sku:row.sku, category:row.category })).join('')}</tr>`).join('') : `<tr><td colspan="${periods.length + 2}"><div class="an-empty">Tidak ada cohort product untuk filter ini.</div></td></tr>`;
    }
    function openCohortDetail(detail) {
        const label = detail.is_product ? detail.product_name : cohortMonthLabel(detail.cohort_month);
        $('cohortDetailTitle').textContent = `${detail.metric_label} · ${label}`;
        $('cohortDetailSubtitle').textContent = `M${detail.period_index} · ${detail.period_month || detail.cohort_month} · filter aktif`;
        const entries = detail.is_product
            ? [['Nilai', cohortFormat(detail[detail.metric], detail.metric)], ['Orders', Number(detail.orders || 0).toLocaleString('id-ID')], ['Qty Sold', Number(detail.qty_sold || 0).toLocaleString('id-ID')], ['Revenue', money(detail.revenue)], ['Gross Profit', money(detail.gross_profit)], ['Coverage', `${Number(detail.financial_coverage_pct || 0).toFixed(1)}%`]]
            : [['Nilai', cohortFormat(detail[detail.metric], detail.metric)], ['Active Customers', Number(detail.active_customers || 0).toLocaleString('id-ID')], ['Orders', Number(detail.orders || 0).toLocaleString('id-ID')], ['Qty Sold', Number(detail.qty_sold || 0).toLocaleString('id-ID')], ['Revenue', money(detail.revenue)]];
        $('cohortDetailSummary').innerHTML = entries.map(([key, value]) => cashStat(key, value)).join('');
        $('cohortDetailNote').textContent = detail.is_product
            ? 'Nilai ini menunjukkan performa produk pada umur cohort tersebut. Coverage menunjukkan bagian order dengan settlement lengkap.'
            : 'Nilai ini menunjukkan performa customer cohort pada umur tersebut. M0 adalah bulan transaksi pertama; M1+ adalah aktivitas berulang.';
        $('cohortDetailModal').classList.add('is-open');
        $('cohortDetailModal').setAttribute('aria-hidden', 'false');
        document.body.classList.add('an-modal-open');
    }
    function closeCohortDetail() {
        $('cohortDetailModal').classList.remove('is-open');
        $('cohortDetailModal').setAttribute('aria-hidden', 'true');
        document.body.classList.remove('an-modal-open');
    }
    async function loadCohort() {
        if (cohortLoading) return;
        cohortLoading = true;
        $('anCohortApply').disabled = true;
        $('anCohortApply').innerHTML = '<i class="bi bi-arrow-repeat me-1"></i>Loading…';
        renderCohortActiveFilters();
        $('anCohortBody').innerHTML = '<tr><td colspan="8"><div class="an-empty">Memuat cohort…</div></td></tr>';
        try {
            await loadCohortOptions();
            cohortPayload = await api('/api/marketplace/analytics-cohort?' + cohortParams().toString(), { cache:'no-store' });
            renderCohortKpis(cohortPayload);
            renderCohortTable(cohortPayload);
            renderCohortCharts(cohortPayload);
        } catch (error) {
            console.error('Cohort load failed', error);
            cohortPayload = null;
            $('anCohortNote').textContent = 'Cohort gagal dimuat. Periksa filter atau log aplikasi.';
            $('anCohortBody').innerHTML = '<tr><td colspan="8"><div class="an-error">Data cohort gagal dimuat.</div></td></tr>';
            $('anCohortCurveChart').innerHTML = '<div class="an-error">Grafik cohort gagal dimuat.</div>';
            $('anCohortDistributionChart').innerHTML = '<div class="an-error">Grafik cohort gagal dimuat.</div>';
        } finally {
            cohortLoading = false;
            $('anCohortApply').disabled = false;
            $('anCohortApply').innerHTML = '<i class="bi bi-play-fill me-1"></i>Run analysis';
        }
    }
    function resetCohortFilters() {
        $('anCohortMode').value = 'customer';
        syncCohortMetricOptions();
        ['anCohortMarketplace','anCohortCategory','anCohortProduct','anCohortSku'].forEach(id => { $(id).value = ''; });
        loadCohort();
    }
    async function load() {
        closeCashOrders();
        closeReturnOrders();
        cashPage = 1;
        cashPayload = null;
        returnPage = 1;
        returnPayload = null;
        setLoading('Mengambil ringkasan settlement…');
        $('anRefresh').disabled = true;
        $('anRefresh').textContent = 'Memuat…';
        document.querySelector('.an-shell')?.setAttribute('aria-busy', 'true');
        productsLoaded = false;
        productData = [];
        orders = [];
        try {
            const params = new URLSearchParams({ date_from: from(), date_to: to(), compare_mode: $('anCompare').value, _ts: Date.now().toString() });
            if (selectedStore()) params.set('store_id', selectedStore());
            const [storeRows, summaryPayload] = await Promise.all([
                loadStores(),
                api('/api/marketplace/analytics-summary?' + params.toString(), { cache: 'no-store' }),
            ]);
            stores = storeRows;
            summary = summaryPayload;
            fillStores();
            render();
            $('bestProductBody').innerHTML = '<tr><td colspan="8"><div class="an-empty">Buka tab Produk untuk memuat detail.</div></td></tr>';
            if (document.querySelector('[data-an-tab="cohort"]')?.classList.contains('active')) await loadCohort();
        } catch (e) {
            console.error('Analytics summary load failed', e);
            summary = null;
            $('anSyncNote').textContent = 'Data gagal dimuat';
            $('storeBody').innerHTML = '<tr><td colspan="6"><div class="an-error">Tidak dapat memuat ringkasan analytics.</div></td></tr>';
            $('bestProductBody').innerHTML = '<tr><td colspan="8"><div class="an-error">Tidak dapat memuat data analytics.</div></td></tr>';
        } finally {
            $('anRefresh').disabled = false;
            $('anRefresh').textContent = '↻ Refresh';
            document.querySelector('.an-shell')?.removeAttribute('aria-busy');
        }
    }
    function activateTab(name) {
        document.querySelectorAll('[data-an-tab]').forEach(button => {
            const active = button.dataset.anTab === name;
            button.classList.toggle('active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        document.querySelectorAll('[data-an-pane]').forEach(pane => pane.classList.toggle('is-hidden', pane.dataset.anPane !== name));
        const params = new URLSearchParams(location.search);
        params.set('view', name);
        history.replaceState(null, '', location.pathname + '?' + params.toString());
    }
    document.querySelectorAll('[data-an-tab]').forEach(button => button.addEventListener('click', () => {
        activateTab(button.dataset.anTab);
        if (button.dataset.anTab === 'products') loadProducts();
        if (button.dataset.anTab === 'cohort') loadCohort();
    }));
    const syncUrl = () => { const params = new URLSearchParams({date_from:from(),date_to:to(),compare_mode:$('anCompare').value}); if (selectedStore()) params.set('store_id', selectedStore()); const activeTab = document.querySelector('[data-an-tab].active')?.dataset.anTab; if (activeTab) params.set('view', activeTab); history.replaceState(null,'',location.pathname+'?'+params.toString()); };
    $('anRefresh').addEventListener('click',load); $('anStore').addEventListener('change',load); $('anCompare').addEventListener('change',()=>{syncUrl();load();});
    const focusPulse = card => {
        if (!card) return;
        selectedPulseMetric = card?.dataset?.pulseMetric || null;
        if ($('anCompare').value !== 'prev_month') {
            $('anCompare').value = 'prev_month';
            syncUrl();
            load();
        } else {
            renderEnterprise();
            renderChart(summary?.daily || [], summary?.previous_daily || []);
        }
        requestAnimationFrame(() => $('revenueChart')?.scrollIntoView({ behavior:'smooth', block:'start' }));
    };
    $('anPulseGrid').addEventListener('click', event => focusPulse(event.target.closest('[data-pulse-metric]')));
    $('anPulseGrid').addEventListener('keydown', event => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); focusPulse(event.target.closest('[data-pulse-metric]')); } });
    $('anEconomics').addEventListener('click', event => { if (event.target.closest('[data-open-cash-orders]')) openCashOrders(); });
    $('anEconomics').addEventListener('click', event => { if (event.target.closest('[data-open-return-orders]')) openReturnOrders(); });
    $('anEconomics').addEventListener('click', event => { if (event.target.closest('[data-open-fee-orders]')) openFeeOrders(); });
    $('anEconomics').addEventListener('keydown', event => { if ((event.key === 'Enter' || event.key === ' ') && event.target.closest('[data-open-cash-orders]')) { event.preventDefault(); openCashOrders(); } });
    $('anEconomics').addEventListener('keydown', event => { if ((event.key === 'Enter' || event.key === ' ') && event.target.closest('[data-open-return-orders]')) { event.preventDefault(); openReturnOrders(); } });
    $('anEconomics').addEventListener('keydown', event => { if ((event.key === 'Enter' || event.key === ' ') && event.target.closest('[data-open-fee-orders]')) { event.preventDefault(); openFeeOrders(); } });
    document.querySelectorAll('[data-cash-settlement]').forEach(button => button.addEventListener('click', () => {
        if (!$('cashOrdersModal').classList.contains('is-open')) return;
        if (cashSettlement === button.dataset.cashSettlement && cashPayload) return;
        setCashSettlementTab(button.dataset.cashSettlement);
        cashPage = 1;
        cashPayload = null;
        loadCashOrders();
    }));
    document.querySelectorAll('[data-cash-close]').forEach(element => element.addEventListener('click', closeCashOrders));
    document.querySelectorAll('[data-return-close]').forEach(element => element.addEventListener('click', closeReturnOrders));
    $('cashOrdersPrev').addEventListener('click', () => { if (cashPage > 1) { cashPage -= 1; loadCashOrders(); } });
    $('cashOrdersNext').addEventListener('click', () => { const lastPage = Number(cashPayload?.meta?.last_page || 1); if (cashPage < lastPage) { cashPage += 1; loadCashOrders(); } });
    $('returnOrdersPrev').addEventListener('click', () => { if (returnPage > 1) { returnPage -= 1; loadReturnOrders(); } });
    $('returnOrdersNext').addEventListener('click', () => { const lastPage = Number(returnPayload?.meta?.last_page || 1); if (returnPage < lastPage) { returnPage += 1; loadReturnOrders(); } });
    document.querySelectorAll('[data-return-type]').forEach(button => button.addEventListener('click', () => {
        if (!$('returnOrdersModal').classList.contains('is-open')) return;
        if (returnType === button.dataset.returnType && returnPayload) return;
        setReturnTypeTab(button.dataset.returnType);
        returnPage = 1;
        returnPayload = null;
        loadReturnOrders();
    }));
    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;
        if ($('cashOrdersModal').classList.contains('is-open')) closeCashOrders();
        if ($('returnOrdersModal').classList.contains('is-open')) closeReturnOrders();
    });
    $('anProductSearch').addEventListener('input', () => renderProductSummary(productData));
    $('anProductSort').addEventListener('change', () => renderProductSummary(productData));
    syncCohortMetricOptions();
    $('anCohortMode').addEventListener('change', syncCohortMetricOptions);
    $('anCohortApply').addEventListener('click', loadCohort);
    $('anCohortReset').addEventListener('click', resetCohortFilters);
    ['anCohortCategory','anCohortProduct','anCohortSku'].forEach(id => $(id).addEventListener('change', renderCohortActiveFilters));
    $('anCohortMarketplace').addEventListener('change', () => { renderCohortActiveFilters(); loadCohortOptions(true); });
    document.querySelectorAll('[data-cohort-close]').forEach(element => element.addEventListener('click', closeCohortDetail));
    $('anCohortBody').addEventListener('click', event => {
        const button = event.target.closest('[data-cohort-detail]');
        if (!button) return;
        try { openCohortDetail(JSON.parse(button.dataset.cohortDetail)); } catch (error) { console.error('Cohort detail payload invalid', error); }
    });
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && $('cohortDetailModal').classList.contains('is-open')) closeCohortDetail(); });
    if (window.flatpickr) flatpickr($('anDateRange'), {
        mode: 'range',
        dateFormat: 'Y-m-d',
        locale: (flatpickr.l10ns && flatpickr.l10ns.id) ? 'id' : { firstDayOfWeek: 1 },
        allowInput: false,
        defaultDate: [from(), to()],
        onReady(dates, _, fp) {
            if (dates.length === 2) fp.input.value = `${fp.formatDate(dates[0], 'j F Y')} — ${fp.formatDate(dates[1], 'j F Y')}`;
        },
        onChange(dates, _, fp) {
            if (dates.length === 1) {
                $('anDateFrom').value = fp.formatDate(dates[0], 'Y-m-d');
                $('anDateTo').value = '';
                fp.input.value = `${fp.formatDate(dates[0], 'j F Y')} …`;
                return;
            }
            if (dates.length === 2) {
                $('anDateFrom').value = fp.formatDate(dates[0], 'Y-m-d');
                $('anDateTo').value = fp.formatDate(dates[1], 'Y-m-d');
                fp.input.value = `${fp.formatDate(dates[0], 'j F Y')} — ${fp.formatDate(dates[1], 'j F Y')}`;
                syncUrl();
                load();
            }
        },
    });
    $('anStore').value = initialStore || '';
    const initialView = new URLSearchParams(location.search).get('view');
    if (['summary', 'stores', 'products', 'cohort'].includes(initialView)) activateTab(initialView);
    load();
})();
</script>
@endpush
