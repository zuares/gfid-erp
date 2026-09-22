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
    .an-decision-pulse-grid { grid-template-columns:repeat(3,minmax(0,1fr)); align-items:stretch; }
    .an-decision-pulse-grid .an-pulse { min-height:136px; display:flex; flex-direction:column; justify-content:space-between; gap:.45rem; }
    .an-decision-pulse-grid .an-pulse-label { min-height:2.35em; line-height:1.25; }
    .an-decision-pulse-grid .an-pulse-value { font-size:clamp(.95rem,1.55vw,1.12rem); white-space:nowrap; }
    .an-decision-pulse-grid .an-pulse-note { white-space:normal; overflow:visible; text-overflow:clip; line-height:1.35; margin:0; }
    .an-decision-pulse-footer { display:grid; grid-template-columns:minmax(0,1fr) minmax(96px,auto); align-items:center; gap:.55rem; min-height:2.4em; }
    .an-decision-pulse-footer .an-pulse-compare { color:var(--dsh-muted); font-size:.58rem; line-height:1.3; text-align:right; white-space:normal; }
    .an-decision-pulse-footer .an-pulse-compare.good { color:#15803d; }
    .an-decision-pulse-footer .an-pulse-compare.bad { color:#b91c1c; }
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
    @media (max-width: 760px) { .an-grid-main, .an-grid-secondary, .an-enterprise-grid, .an-contribution-grid { grid-template-columns:1fr; } .an-pulse-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .an-decision-pulse-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .an-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); } .an-kpi-value { font-size:1.12rem; } .an-field input { min-width:150px; } .an-product-toolbar { width:100%; justify-content:flex-start; margin-top:.35rem; } .an-product-toolbar input, .an-product-toolbar select { flex:1 1 140px; width:auto; } .an-chart-panel-head { flex-direction:column; gap:.35rem; } .an-chart-summary { text-align:left; } .an-health-wide .an-funnel { grid-template-columns:1fr; } }
    @media (max-width: 520px) { .an-decision-pulse-grid { grid-template-columns:1fr; } .an-decision-pulse-grid .an-pulse { min-height:116px; } }
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
    .an-kpi.kpi-cancelled::before, .an-kpi.kpi-refund::before { background:linear-gradient(90deg,#dc2626,#fb7185); }
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
    .an-product-table { min-width:920px; }
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
    .an-cash-table { min-width:1040px; }
    .an-cash-table td { vertical-align:top; }
    .an-cash-payment { display:inline-block; max-width:140px; color:var(--text,#0f172a); font-size:.64rem; font-weight:800; line-height:1.25; overflow-wrap:anywhere; }
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
    .an-cash-group { margin-bottom:.85rem; }
    .an-cash-group:last-child { margin-bottom:0; }
    .an-cash-group-head { display:flex; align-items:center; justify-content:space-between; gap:.6rem; margin-bottom:.35rem; padding:.5rem .65rem; border:1px solid var(--dsh-border); border-radius:8px; background:var(--hero-bg,#f8fafc); color:var(--text,#0f172a); font-size:.68rem; }
    .an-cash-group-head span { color:var(--dsh-muted); font-size:.6rem; font-weight:700; }
    .an-exception-kind { display:inline-block; margin-top:.22rem; padding:.16rem .35rem; border-radius:5px; background:#fee2e2; color:#991b1b; font-size:.58rem; font-weight:800; }
    .an-exception-kind.return { background:#fef3c7; color:#92400e; }
    .an-modal-page { color:var(--dsh-muted); font-size:.66rem; font-weight:750; }
    body.an-modal-open { overflow:hidden; }
    body[data-theme="dark"] .an-modal-dialog, body[data-theme="dark"] .an-modal-close { background:var(--card,#1e293b); }
    body[data-theme="dark"] .an-modal-stat, body[data-theme="dark"] .an-cash-detail-grid { background:#0f172a; }
    body[data-theme="dark"] .an-modal-fee-breakdown { background:#0f172a; }
    @media (max-width:760px) { .an-modal-dialog { width:calc(100% - .75rem); max-height:calc(100vh - .75rem); margin:.375rem auto; } .an-modal-summary { grid-template-columns:repeat(2,minmax(0,1fr)); } .an-modal-body { padding-inline:.5rem; } .an-modal-head { padding-inline:.65rem; } }
    .an-cohort-workspace { display:grid; gap:1rem; min-width:0; }
    .an-cohort-hero { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; padding:1.15rem 1.2rem; border:1px solid #1e293b; border-radius:16px; background:linear-gradient(135deg,#0f172a 0%,#1e3a5f 100%); box-shadow:0 12px 28px rgba(15,23,42,.12); }
    .an-cohort-hero-copy { min-width:0; }
    .an-cohort-hero-meta { display:flex; align-items:flex-end; flex-direction:column; gap:.5rem; flex:0 0 auto; }
    .an-cohort-period-label { color:#94a3b8; font-size:.59rem; font-weight:750; text-align:right; text-transform:uppercase; letter-spacing:.05em; }
    .an-cohort-period-label strong { display:block; margin-top:.15rem; color:#bfdbfe; font-size:.65rem; font-weight:800; letter-spacing:0; text-transform:none; }
    .an-section-kicker { display:flex; align-items:center; gap:.35rem; color:#93c5fd; font-size:.6rem; font-weight:850; letter-spacing:.1em; text-transform:uppercase; }
    .an-section-kicker-dot { width:6px; height:6px; border-radius:99px; background:#38bdf8; box-shadow:0 0 0 4px rgba(56,189,248,.14); }
    .an-cohort-title { margin:.3rem 0 0; color:#fff; font-size:1.15rem; font-weight:850; letter-spacing:-.03em; }
    .an-cohort-description { margin:.28rem 0 0; color:#cbd5e1; font-size:.7rem; font-weight:600; line-height:1.45; }
    .an-status-pill { display:inline-flex; align-items:center; gap:.3rem; padding:.32rem .5rem; border:1px solid rgba(147,197,253,.26); border-radius:7px; background:rgba(15,23,42,.26); color:#bfdbfe; font-size:.59rem; font-weight:850; white-space:nowrap; }
    .an-cohort-control-card { padding:.9rem 1rem .7rem; border:1px solid var(--dsh-border); border-radius:14px; background:var(--card,#fff); box-shadow:0 8px 20px rgba(15,23,42,.04); }
    .an-cohort-control-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; padding-bottom:.75rem; border-bottom:1px solid var(--dsh-border); }
    .an-cohort-control-eyebrow, .an-cohort-panel-eyebrow { color:#2563eb; font-size:.56rem; font-weight:850; letter-spacing:.1em; text-transform:uppercase; }
    .an-cohort-control-title { margin:.22rem 0 0; color:var(--text,#0f172a); font-size:.78rem; font-weight:900; }
    .an-cohort-control-sub { max-width:680px; margin:.18rem 0 0; color:var(--dsh-muted); font-size:.62rem; font-weight:600; line-height:1.45; }
    .an-cohort-control-grid { display:grid; gap:.65rem; align-items:end; }
    .an-cohort-primary-controls { grid-template-columns:minmax(150px,190px) minmax(170px,230px) minmax(170px,230px) minmax(220px,1fr); padding: .8rem 0; }
    .an-cohort-grouping-field[hidden] { display:none; }
    .an-cohort-advanced { border-top:1px solid var(--dsh-border); }
    .an-cohort-advanced summary { display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:.65rem 0 .5rem; color:var(--text,#0f172a); font-size:.64rem; font-weight:850; cursor:pointer; list-style:none; }
    .an-cohort-advanced summary::-webkit-details-marker { display:none; }
    .an-cohort-advanced summary::after { content:'+'; color:var(--dsh-muted); font-size:.9rem; font-weight:500; }
    .an-cohort-advanced[open] summary::after { content:'−'; }
    .an-cohort-advanced summary small { margin-left:auto; color:var(--dsh-muted); font-size:.58rem; font-weight:650; }
    .an-cohort-advanced-grid { grid-template-columns:repeat(4,minmax(0,1fr)); padding-bottom:.75rem; }
    .an-cohort-run-wrap { display:flex; align-items:flex-end; justify-content:flex-end; gap:.65rem; min-width:0; }
    .an-cohort-run-hint { color:var(--dsh-muted); font-size:.58rem; font-weight:650; text-align:right; }
    .an-cohort-reset { border:0; padding:.25rem .35rem; background:transparent; color:#2563eb; font-size:.63rem; font-weight:800; cursor:pointer; white-space:nowrap; }
    .an-cohort-reset:hover { color:#1d4ed8; text-decoration:underline; }
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
    .an-cohort-panel-badge { display:inline-flex; align-items:center; padding:.25rem .42rem; border-radius:6px; font-size:.55rem; font-weight:850; white-space:nowrap; }
    .an-cohort-panel-badge.blue { background:#dbeafe; color:#1d4ed8; }
    .an-cohort-panel-badge.green { background:#dcfce7; color:#15803d; }
    .an-cohort-insights { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.8rem; }
    .an-cohort-chart-card { min-width:0; }
    .an-cohort-chart-body { padding-top:.15rem; }
    .an-cohort-chart-canvas { position:relative; height:220px; border-radius:10px; background:linear-gradient(180deg,#fbfdff 0%,#fff 100%); }
    .an-cohort-chart-canvas canvas { display:block; width:100% !important; height:220px !important; }
    .an-cohort-detail-layout { display:grid; grid-template-columns:minmax(0,1.65fr) minmax(240px,.55fr); gap:.8rem; align-items:start; }
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
    .an-cohort-table-wrap { overflow-x:auto; border:1px solid var(--dsh-border); border-radius:10px; }
    .an-cohort-table { width:100%; min-width:0; table-layout:fixed; }
    .an-cohort-table th, .an-cohort-table td { overflow:hidden; text-align:right; white-space:normal; }
    .an-cohort-table:not(.is-product) th:first-child { width:18%; }
    .an-cohort-table:not(.is-product) th:nth-child(2) { width:10%; }
    .an-cohort-table.is-product th:first-child { width:26%; }
    .an-cohort-table.is-product th:nth-child(2) { width:14%; }
    .an-cohort-table th:first-child, .an-cohort-table td:first-child { text-align:left; position:sticky; left:0; z-index:3; background:var(--card,#fff); }
    .an-cohort-table thead th { background:var(--hero-bg,#f8fafc); vertical-align:bottom; }
    .an-cohort-table thead th small { display:block; margin-top:.12rem; color:var(--dsh-muted); font-size:.53rem; font-weight:650; text-transform:none; letter-spacing:0; }
    .an-cohort-table tbody td { padding:.45rem .3rem; vertical-align:middle; }
    .an-cohort-table tbody tr:hover td { background:rgba(37,99,235,.035); }
    .an-cohort-row-title { font-weight:850; color:var(--text,#0f172a); }
    .an-cohort-row-sub { display:block; margin-top:.12rem; color:var(--dsh-muted); font-size:.6rem; font-weight:650; }
    .an-cohort-base-value { display:block; color:var(--text,#0f172a); font-weight:900; }
    .an-cohort-product-cell .an-cohort-row-title { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .an-cohort-cell { display:block; width:100%; min-width:0; border:0; border-radius:7px; padding:.35rem .3rem; color:var(--text,#0f172a); background:rgba(37,99,235,var(--heat,.08)); font-size:.68rem; font-weight:850; cursor:pointer; }
    .an-cohort-cell-value, .an-cohort-cell-sub { display:block; }
    .an-cohort-cell-value { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .an-cohort-cell-sub { margin-top:.12rem; color:var(--dsh-muted); font-size:.53rem; font-weight:700; }
    .an-cohort-table.is-dense .an-cohort-cell { min-width:0; padding-inline:.18rem; font-size:.61rem; }
    .an-cohort-table.is-dense .an-cohort-cell-sub { display:none; }
    .an-cohort-table.is-dense thead th small { display:none; }
    .an-cohort-cell:hover, .an-cohort-cell:focus-visible { outline:2px solid #2563eb; outline-offset:1px; }
    .an-cohort-cell.product { background:rgba(217,119,6,var(--heat,.1)); }
    .an-cohort-cell.is-empty { display:block; min-height:2.1rem; padding:.35rem .2rem; background:transparent; color:#94a3b8; cursor:default; text-align:center; }
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
    @media (max-width:1100px) { .an-cohort-primary-controls { grid-template-columns:repeat(2,minmax(0,1fr)); } .an-cohort-run-wrap { grid-column:1/-1; justify-content:flex-start; } .an-cohort-run-hint { text-align:left; } .an-cohort-advanced-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .an-cohort-apply { width:auto; } .an-cohort-insights, .an-cohort-detail-layout { grid-template-columns:1fr; } }
    @media (max-width:760px) { .an-cohort-hero { flex-direction:column; } .an-cohort-hero-meta { align-items:flex-start; } .an-cohort-period-label { text-align:left; } .an-cohort-control-head { align-items:flex-start; } .an-cohort-primary-controls, .an-cohort-advanced-grid { grid-template-columns:1fr; } .an-cohort-run-wrap { grid-column:auto; flex-direction:column; align-items:stretch; gap:.45rem; } .an-cohort-run-hint { text-align:left; } .an-cohort-apply { width:100%; } .an-cohort-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .an-cohort-field, .an-cohort-field input, .an-cohort-field select { width:100%; min-width:0; } .an-cohort-reading-hint { width:100%; margin-left:0; padding-top:.35rem; border-top:1px solid var(--dsh-border); } }
    @media (max-width:420px) { .an-cohort-summary-grid { grid-template-columns:1fr; } .an-cohort-apply { grid-column:auto; } .an-cohort-control-head { flex-direction:column; } .an-cohort-reset { align-self:flex-start; } .an-cohort-advanced summary { align-items:flex-start; flex-direction:column; gap:.15rem; } .an-cohort-advanced summary small { margin-left:0; } }

    /* Enterprise command-center pass: stronger hierarchy, denser context, and clearer executive scanning. */
    .an-page { max-width:1280px; }
    .an-shell { gap:1rem; }
    .an-hero { position:relative; min-height:132px; margin-inline:-.75rem; padding:1.15rem 1.25rem; border:1px solid #1e3a5f; border-radius:18px; background:linear-gradient(135deg,#0b1220 0%,#10233f 62%,#12395a 100%); box-shadow:0 18px 34px rgba(15,23,42,.16); align-items:flex-end; }
    .an-hero::after { content:''; position:absolute; inset:auto 1.2rem 0 auto; width:210px; height:76px; border-radius:100% 0 0 0; background:radial-gradient(ellipse at bottom right,rgba(56,189,248,.2),transparent 68%); pointer-events:none; }
    .an-hero-copy { position:relative; z-index:1; }
    .an-hero-eyebrow { display:flex; align-items:center; gap:.4rem; color:#93c5fd; font-size:.61rem; font-weight:850; letter-spacing:.11em; text-transform:uppercase; }
    .an-hero-eyebrow::before { content:''; width:7px; height:7px; border-radius:99px; background:#38bdf8; box-shadow:0 0 0 4px rgba(56,189,248,.16); }
    .an-hero-title { margin-top:.4rem; color:#fff; font-size:1.35rem; font-weight:850; letter-spacing:-.04em; }
    .an-hero-title i { color:#7dd3fc; }
    .an-hero-sub { margin-top:.25rem; color:#cbd5e1; font-size:.7rem; font-weight:600; }
    .an-hero .an-sync-note { color:#94a3b8 !important; font-size:.63rem; }
    .an-hero-controls { position:relative; z-index:1; padding:.55rem .65rem; border:1px solid rgba(191,219,254,.2); border-radius:12px; background:rgba(15,23,42,.38); backdrop-filter:blur(8px); }
    .an-hero .an-field label { color:#bfdbfe; }
    .an-hero .an-field input, .an-hero .an-field select { border-color:rgba(148,163,184,.34); }
    .an-hero .an-btn-dark { background:#38bdf8; border-color:#38bdf8; color:#082f49; }
    .an-hero .an-btn-dark:hover { background:#7dd3fc; border-color:#7dd3fc; color:#082f49; }
    .an-decision-pulse-grid .an-pulse { min-height:112px; gap:.28rem; }
    .an-decision-pulse-grid .an-pulse-label { min-height:1.3em; }
    .an-decision-pulse-grid .an-pulse-note { font-size:.6rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .an-decision-pulse-footer { min-height:1.5em; grid-template-columns:minmax(0,1fr) auto; gap:.35rem; }
    .an-decision-pulse-footer .an-pulse-compare { font-size:.57rem; white-space:nowrap; }
    .an-decision-pulse-grid .an-pulse.decision-cash { border-top:3px solid #2563eb; }
    .an-decision-pulse-grid .an-pulse.decision-profit { border-top:3px solid #16a34a; }
    .an-decision-pulse-grid .an-pulse.decision-cost { border-top:3px solid #d97706; }
    .an-tabs-wrap { padding:.25rem 0 .15rem; border-bottom:0; }
    .an-tabs { border-color:var(--dsh-border); border-radius:12px; background:var(--card,#fff); box-shadow:0 8px 18px rgba(15,23,42,.05); }
    .an-tab { padding:.58rem .85rem; border-radius:9px; font-size:.7rem; }
    .an-tab.active { background:#172554; box-shadow:0 6px 14px rgba(23,37,84,.18); }
    .an-enterprise-card, .an-card { border-radius:16px; box-shadow:0 14px 30px rgba(15,23,42,.055); }
    .an-enterprise-head, .an-card-head { padding:1rem 1.05rem .82rem; }
    .an-enterprise-head { background:linear-gradient(180deg,rgba(248,250,252,.78),rgba(255,255,255,0)); }
    .an-enterprise-title, .an-card-title { font-size:.86rem; font-weight:850; letter-spacing:-.01em; }
    .an-enterprise-sub, .an-card-sub { max-width:720px; line-height:1.45; }
    .an-executive-top { border-color:#bfdbfe; }
    .an-executive-top .an-enterprise-head { border-left:3px solid #2563eb; padding-left:.85rem; }
    .an-executive-top .an-pulse { background:linear-gradient(180deg,#f8fbff,#fff); }
    .an-tab-pane[data-an-pane="summary"]:nth-of-type(4) .an-enterprise-head { border-left:3px solid #0f766e; padding-left:.85rem; }
    .an-health-wide .an-card-head, .an-health-wide .an-enterprise-head { border-left:3px solid #2563eb; padding-left:.85rem; }
    .an-chart-panel-title { color:var(--text,#0f172a); font-size:.73rem; font-weight:850; text-transform:uppercase; letter-spacing:.06em; }
    .an-chart-summary { color:#2563eb; font-size:.67rem; font-weight:850; }
    .an-chart-canvas { border:1px solid #eef2f7; }
    .an-kpi { border-radius:14px; box-shadow:0 10px 22px rgba(15,23,42,.045); }
    .an-kpi.primary { background:linear-gradient(135deg,#0f172a,#1e293b); }
    /* Keep the default analytics surface light and readable; dark mode remains opt-in. */
    .an-hero { border-color:#bfdbfe; background:linear-gradient(135deg,#f8fbff 0%,#eff6ff 58%,#e0f2fe 100%); box-shadow:0 18px 34px rgba(37,99,235,.09); }
    .an-hero::after { background:radial-gradient(ellipse at bottom right,rgba(37,99,235,.12),transparent 68%); }
    .an-hero-title { color:#0f172a; }
    .an-hero-title i { color:#2563eb; }
    .an-hero-sub { color:#475569; }
    .an-hero .an-sync-note { color:#64748b !important; }
    .an-hero-controls { border-color:#bfdbfe; background:rgba(255,255,255,.78); }
    .an-hero .an-field label { color:#334155; }
    .an-hero .an-btn-dark, .an-btn-dark { background:#2563eb; border-color:#2563eb; color:#fff; }
    .an-hero .an-btn-dark:hover, .an-btn-dark:hover { background:#1d4ed8; border-color:#1d4ed8; color:#fff; }
    .an-tab.active { background:#2563eb; box-shadow:0 6px 14px rgba(37,99,235,.18); }
    .an-kpi.primary { background:linear-gradient(135deg,#eff6ff,#dbeafe); color:#0f172a; border-color:#bfdbfe; }
    .an-kpi.primary .an-kpi-label, .an-kpi.primary .an-kpi-value { color:#0f172a; }
    .an-cohort-hero { border-color:#bfdbfe; background:linear-gradient(135deg,#f8fbff 0%,#eff6ff 60%,#e0f2fe 100%); }
    .an-cohort-title { color:#0f172a; }
    .an-cohort-description { color:#475569; }
    .an-cohort-summary-card.is-primary { border-color:#bfdbfe; background:#eff6ff; }
    .an-cohort-summary-card.is-primary .an-cohort-summary-label,
    .an-cohort-summary-card.is-primary .an-cohort-summary-value,
    .an-cohort-summary-card.is-primary .an-cohort-summary-note { color:#0f172a; }
    body[data-theme="dark"] .an-hero { border-color:#1e40af; background:linear-gradient(135deg,#020617 0%,#0f172a 62%,#12395a 100%); }
    body[data-theme="dark"] .an-enterprise-head { background:linear-gradient(180deg,rgba(30,41,59,.55),rgba(30,41,59,0)); }
    body[data-theme="dark"] .an-executive-top .an-pulse { background:linear-gradient(180deg,#1e293b,#172033); }
    @media (min-width:1200px) { .an-page { min-width:0; } }
    @media (max-width:760px) { .an-hero { margin-inline:-.5rem; padding:.9rem .8rem; } .an-hero-title { font-size:1.12rem; } .an-hero-controls { padding:.55rem; } .an-enterprise-head, .an-card-head { padding:.85rem .8rem .7rem; } }
</style>
@endpush

@section('content')
<div class="dash py-3">
    <div class="page-wrap ads-shell an-page">
        <div class="an-hero">
            <div class="an-hero-copy">
                <div class="an-hero-eyebrow">Marketplace · Analytics</div>
                <div class="an-hero-title"><i class="bi bi-bar-chart-line me-1"></i>Analytics Marketplace</div>
                <div class="an-hero-sub">Executive command center untuk omzet, pencairan, biaya, dan profit.</div>
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
        @include('marketplace.partials._analytics_summary')
        @include('marketplace.partials._analytics_stores')
        @include('marketplace.partials._analytics_products')
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
    let cashSettlement = 'all';
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
    @include('marketplace.partials._analytics_summary_script')
    @include('marketplace.partials._analytics_stores_script')
    @include('marketplace.partials._analytics_products_script')
    @include('marketplace.partials._analytics_cohort_script')

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
        $('bestProductBody').innerHTML = '<tr><td colspan="9"><div class="an-empty">Memuat detail produk…</div></td></tr>';
        try {
            const params = new URLSearchParams({ date_from: from(), date_to: to() });
            if (selectedStore()) params.set('store_id', selectedStore());
            params.set('_ts', Date.now().toString());
            const result = await api('/api/marketplace/analytics-products?' + params.toString(), { cache: 'no-store' });
            productData = result?.data || [];
            productsLoaded = true;
            renderProductSummary(productData);
        } catch (e) {
            $('bestProductBody').innerHTML = '<tr><td colspan="9"><div class="an-error">Detail produk gagal dimuat.</div></td></tr>';
        }
    }
    async function load() {
        closeCashOrders();
        closeReturnOrders();
        cashPage = 1;
        cashPayload = null;
        returnPage = 1;
        returnPayload = null;
        setLoading('Menyiapkan KPI utama…');
        $('anRefresh').disabled = true;
        $('anRefresh').textContent = 'Memuat…';
        document.querySelector('.an-shell')?.setAttribute('aria-busy', 'true');
        productsLoaded = false;
        productData = [];
        orders = [];
        try {
            const params = new URLSearchParams({ date_from: from(), date_to: to(), compare_mode: $('anCompare').value, _ts: Date.now().toString() });
            if (selectedStore()) params.set('store_id', selectedStore());
            try {
                const kpiPayload = await api('/api/marketplace/analytics-kpis?' + params.toString(), { cache: 'no-store' });
                summary = kpiPayload;
                renderEnterprise();
                setLoading('KPI utama siap · memuat detail analytics…');
            } catch (kpiError) {
                console.warn('Analytics KPI fast load failed', kpiError);
            }
            const detailsPromise = Promise.all([
                loadStores(),
                api('/api/marketplace/analytics-summary?' + params.toString(), { cache: 'no-store' }),
            ]);
            const [storeRows, summaryPayload] = await detailsPromise;
            stores = storeRows;
            summary = summaryPayload;
            fillStores();
            render();
            $('bestProductBody').innerHTML = '<tr><td colspan="9"><div class="an-empty">Buka tab Produk untuk memuat detail.</div></td></tr>';
            if (document.querySelector('[data-an-tab="cohort"]')?.classList.contains('active')) await loadCohort();
        } catch (e) {
            console.error('Analytics summary load failed', e);
            summary = null;
            $('anSyncNote').textContent = 'Data gagal dimuat';
            $('storeBody').innerHTML = '<tr><td colspan="6"><div class="an-error">Tidak dapat memuat ringkasan analytics.</div></td></tr>';
            $('bestProductBody').innerHTML = '<tr><td colspan="9"><div class="an-error">Tidak dapat memuat data analytics.</div></td></tr>';
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
    $('anCohortMode').addEventListener('change', () => { syncCohortMetricOptions(); renderCohortActiveFilters(); });
    $('anCohortGroupBy').addEventListener('change', renderCohortActiveFilters);
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
