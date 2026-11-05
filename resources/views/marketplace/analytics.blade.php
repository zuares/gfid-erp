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
    .an-control-tower { border:1px solid var(--dsh-border); border-radius:14px; background:var(--card,#fff); box-shadow:0 8px 20px rgba(15,23,42,.035); overflow:hidden; }
    .an-control-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; padding:.85rem .95rem; border-bottom:1px solid var(--dsh-border); }
    .an-control-title { margin-top:.2rem; color:var(--text,#0f172a); font-size:.96rem; font-weight:850; letter-spacing:-.025em; }
    .an-control-sub { margin-top:.2rem; color:var(--dsh-muted); font-size:.65rem; font-weight:600; }
    .an-control-status { display:flex; align-items:center; gap:.35rem; padding:.35rem .5rem; border:1px solid #bbf7d0; border-radius:8px; background:#f0fdf4; color:#166534; font-size:.6rem; white-space:nowrap; }
    .an-control-status-dot { width:6px; height:6px; border-radius:99px; background:#16a34a; box-shadow:0 0 0 3px rgba(22,163,74,.12); }
    .an-control-status small { color:#4d7c5b; font-size:.56rem; font-weight:700; }
    .an-control-grid { display:grid; grid-template-columns:minmax(0,1.2fr) minmax(300px,.8fr); gap:.7rem; padding:.7rem; background:var(--hero-bg,#f8fafc); }
    .an-control-column { display:grid; gap:.7rem; min-width:0; }
    .an-control-panel { min-width:0; border:1px solid var(--dsh-border); border-radius:11px; background:var(--card,#fff); overflow:hidden; }
    .an-control-panel-head { display:flex; align-items:flex-start; justify-content:space-between; gap:.65rem; padding:.65rem .7rem; border-bottom:1px solid var(--dsh-border); }
    .an-control-panel-kicker { color:#2563eb; font-size:.55rem; font-weight:850; letter-spacing:.08em; text-transform:uppercase; }
    .an-control-panel-title { margin-top:.15rem; color:var(--text,#0f172a); font-size:.76rem; font-weight:850; }
    .an-control-panel-sub { margin-top:.13rem; color:var(--dsh-muted); font-size:.59rem; font-weight:600; line-height:1.35; }
    .an-control-panel-value { color:var(--text,#0f172a); font-size:.64rem; font-weight:850; text-align:right; white-space:nowrap; }
    .an-control-panel-body { padding:.65rem .7rem; }
    .an-control-flow .an-control-panel-body { padding:.75rem .8rem; }
    .an-control-flow .an-funnel { gap:.4rem; }
    .an-control-flow .an-funnel-row { grid-template-columns:minmax(88px,.35fr) minmax(0,1fr) auto; padding:.32rem 0; }
    .an-control-flow .an-funnel-track { height:20px; border-radius:6px; }
    .an-control-flow .an-funnel-value { font-size:.65rem; }
    .an-health-score, .an-alert-count { display:inline-flex; align-items:center; flex:0 0 auto; padding:.27rem .42rem; border-radius:6px; background:#eff6ff; color:#1d4ed8; font-size:.58rem; font-weight:850; white-space:nowrap; }
    .an-alert-count { background:#f8fafc; color:#64748b; }
    .an-control-panel .an-health-list { gap:.48rem; }
    .an-control-panel .an-health-row { grid-template-columns:minmax(92px,.55fr) minmax(0,1fr) auto; gap:.45rem; font-size:.62rem; }
    .an-control-panel .an-health-row small { font-size:.56rem; }
    .an-control-panel .an-alerts { gap:.35rem; }
    .an-control-panel .an-alert { grid-template-columns:20px minmax(0,1fr) auto; padding:.42rem .48rem; border-radius:8px; }
    .an-control-panel .an-alert-icon { width:20px; height:20px; border-radius:6px; font-size:.62rem; }
    .an-control-panel .an-alert-title { font-size:.62rem; }
    .an-control-panel .an-alert-note, .an-control-panel .an-alert-action { font-size:.56rem; }
    body[data-theme="dark"] .an-control-tower, body[data-theme="dark"] .an-control-panel { background:var(--card,#1e293b); }
    body[data-theme="dark"] .an-control-grid { background:#0f172a; }
    body[data-theme="dark"] .an-control-status { background:rgba(22,101,52,.2); border-color:rgba(134,239,172,.25); }
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
    .an-portfolio-section, .an-product-snapshot-section { margin-top:.85rem; }
    .an-portfolio-section-body, .an-product-snapshot-body { padding:.85rem; }
    .an-portfolio-overview-grid { display:grid; grid-template-columns:minmax(0,1.25fr) minmax(300px,.75fr); gap:.75rem; }
    .an-portfolio-ledger-grid { display:grid; grid-template-columns:minmax(0,1.45fr) minmax(280px,.55fr); gap:.75rem; margin-top:.75rem; }
    .an-portfolio-subsection { min-width:0; padding:.75rem; border:1px solid var(--dsh-border); border-radius:12px; background:var(--hero-bg,#fbfcfe); }
    .an-portfolio-subsection-head { display:flex; align-items:flex-start; justify-content:space-between; gap:.65rem; margin-bottom:.65rem; }
    .an-portfolio-subsection .an-pulse-grid { grid-template-columns:repeat(3,minmax(0,1fr)); }
    .an-portfolio-table-section { overflow:hidden; }
    .an-portfolio-table-section .an-table-wrap { margin:0 -.75rem -.75rem; border-top:1px solid var(--dsh-border); border-radius:0; }
    .an-product-snapshot-content { display:grid; grid-template-columns:minmax(0,1fr) minmax(280px,.42fr); gap:.75rem; margin-top:.75rem; }
    .an-product-snapshot-pulse { grid-template-columns:repeat(4,minmax(0,1fr)); }
    .an-product-snapshot-list { display:grid; gap:.35rem; }
    .an-product-snapshot-row { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:.75rem; align-items:center; padding:.58rem .65rem; border:1px solid var(--dsh-border); border-radius:10px; background:var(--card,#fff); }
    .an-product-snapshot-row + .an-product-snapshot-row { margin-top:.35rem; }
    .an-product-snapshot-name { min-width:0; color:var(--text,#0f172a); font-size:.7rem; font-weight:800; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .an-product-snapshot-meta { margin-top:.12rem; color:var(--dsh-muted); font-size:.59rem; font-weight:650; }
    .an-product-snapshot-value { color:var(--text,#0f172a); font-size:.69rem; font-weight:900; text-align:right; white-space:nowrap; }
    .an-product-snapshot-value small { display:block; margin-top:.12rem; color:var(--dsh-muted); font-size:.57rem; font-weight:650; }
    .an-product-open-tab { white-space:nowrap; }
    body[data-theme="dark"] .an-portfolio-subsection, body[data-theme="dark"] .an-product-snapshot-row { background:var(--card,#1e293b); }
    body[data-theme="dark"] .an-enterprise-card, body[data-theme="dark"] .an-pulse, body[data-theme="dark"] .an-alert { background:var(--card,#1e293b); }
    body[data-theme="dark"] .an-pulse, body[data-theme="dark"] .an-alert { border-color:var(--dsh-border); }
    .an-empty { padding:1.6rem 0; text-align:center; color:#94a3b8; font-size:.75rem; font-weight:750; }
    .an-error { padding:.8rem .9rem; border:1px solid #fecaca; border-radius:12px; background:#fef2f2; color:#b91c1c; font-size:.73rem; font-weight:750; }
    @media (max-width: 1100px) and (min-width: 761px) { .an-pulse-grid-executive, .an-pulse-grid-finance { grid-template-columns:repeat(3,minmax(0,1fr)); } }
    @media (max-width: 760px) { .an-grid-main, .an-grid-secondary, .an-enterprise-grid, .an-contribution-grid, .an-control-grid, .an-portfolio-overview-grid, .an-portfolio-ledger-grid, .an-product-snapshot-content { grid-template-columns:1fr; } .an-control-head { flex-direction:column; gap:.55rem; } .an-control-status { align-self:flex-start; } .an-pulse-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .an-portfolio-subsection .an-pulse-grid, .an-product-snapshot-pulse { grid-template-columns:repeat(2,minmax(0,1fr)); } .an-decision-pulse-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .an-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); } .an-kpi-value { font-size:1.12rem; } .an-field input { min-width:150px; } .an-product-toolbar { width:100%; justify-content:flex-start; margin-top:.35rem; } .an-product-toolbar input, .an-product-toolbar select { flex:1 1 140px; width:auto; } .an-chart-panel-head { flex-direction:column; gap:.35rem; } .an-chart-summary { text-align:left; } .an-health-wide .an-funnel { grid-template-columns:1fr; } .an-product-open-tab { align-self:flex-start; } }
    @media (max-width: 520px) { .an-decision-pulse-grid { grid-template-columns:1fr; } .an-decision-pulse-grid .an-pulse { min-height:116px; } }
    @media (max-width: 420px) { .an-kpis { grid-template-columns:1fr 1fr; gap:.45rem; } .an-kpi { padding:.72rem; min-height:100px; } .an-toolbar, .an-toolbar-controls { align-items:stretch; } .an-field, .an-field input, .an-btn { width:100%; } }

    /* Selaras dengan Ads Dashboard: header flat, panel rapat, dan KPI beraksen. */
    .an-page { max-width:var(--gf-content-max,1280px); width:100%; min-width:0; margin-inline:auto; padding-inline:var(--gf-content-gutter,.75rem); }
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
    .an-product-portfolio-card { border-color:#cbd5e1; }
    .an-product-portfolio-card .an-pulse-grid { grid-template-columns:repeat(4,minmax(0,1fr)); }
    .an-product-portfolio-card .an-enterprise-head { background:linear-gradient(180deg,#fbfdff 0%,#fff 100%); }
    .an-product-scope-pill { display:inline-flex; align-items:center; gap:.28rem; padding:.28rem .45rem; border:1px solid #bfdbfe; border-radius:7px; background:#eff6ff; color:#1d4ed8; font-size:.57rem; font-weight:850; white-space:nowrap; }
    .an-product-scope-pill i { font-size:.65rem; }
    .an-product-reading { display:flex; align-items:flex-start; gap:.38rem; margin-top:.7rem; padding:.52rem .62rem; border:1px solid #dbeafe; border-radius:9px; background:#f8fbff; color:#475569; font-size:.61rem; font-weight:650; line-height:1.4; }
    .an-product-reading i { flex:0 0 auto; color:#2563eb; font-size:.72rem; margin-top:.05rem; }
    .an-product-reading strong { color:#1e3a8a; font-weight:850; }
    .an-product-toolbar { align-items:center; }
    .an-product-result-count { color:var(--dsh-muted); font-size:.59rem; font-weight:800; white-space:nowrap; }
    .an-product-table-note { display:flex; align-items:center; justify-content:space-between; gap:.6rem; padding:.42rem .55rem .5rem; color:var(--dsh-muted); font-size:.58rem; font-weight:650; }
    .an-product-table-note span { display:inline-flex; align-items:center; gap:.25rem; }
    .an-product-table-note i { color:#2563eb; }
    .an-product-table tbody tr[data-profit-state="negative"] td { background:rgba(254,242,242,.34); }
    .an-product-table tbody tr[data-profit-state="missing_hpp"] td, .an-product-table tbody tr[data-profit-state="thin_margin"] td { background:rgba(255,251,235,.26); }
    .an-product-table tbody tr:hover td { background:rgba(219,234,254,.24); }
    .an-product-table th:first-child, .an-product-table td:first-child { position:sticky; left:0; z-index:3; background:var(--card,#fff); }
    .an-product-table th:nth-child(2), .an-product-table td:nth-child(2) { position:sticky; left:32px; z-index:3; background:var(--card,#fff); box-shadow:7px 0 10px -10px rgba(15,23,42,.45); }
    .an-product-table thead th:first-child, .an-product-table thead th:nth-child(2) { background:var(--hero-bg,#f8fafc); z-index:4; }
    .an-product-table tr:hover td:first-child, .an-product-table tr:hover td:nth-child(2) { background:#f8fbff; }
    .an-rank { width:22px; height:22px; border-radius:6px; font-size:.6rem; }
    .an-rank.top { background:#dbeafe; color:#1d4ed8; }
    .an-product-status { display:inline-flex; align-items:center; gap:.25rem; padding:.28rem .42rem; border-radius:6px; font-size:.57rem; font-weight:850; white-space:nowrap; }
    .an-product-status.good { background:#dcfce7; color:#166534; }
    .an-product-status.warn { background:#fef3c7; color:#92400e; }
    .an-product-status.bad { background:#fee2e2; color:#991b1b; }
    .an-product-status i { font-size:.62rem; }
    body[data-theme="dark"] .an-product-portfolio-card .an-enterprise-head { background:linear-gradient(180deg,#1e293b 0%,var(--card,#1e293b) 100%); }
    body[data-theme="dark"] .an-product-reading { background:#0f172a; border-color:#1e40af; color:#cbd5e1; }
    body[data-theme="dark"] .an-product-reading strong { color:#bfdbfe; }
    body[data-theme="dark"] .an-product-table th:first-child, body[data-theme="dark"] .an-product-table td:first-child, body[data-theme="dark"] .an-product-table th:nth-child(2), body[data-theme="dark"] .an-product-table td:nth-child(2) { background:var(--card,#1e293b); }
    body[data-theme="dark"] .an-product-table thead th:first-child, body[data-theme="dark"] .an-product-table thead th:nth-child(2) { background:var(--hero-bg,#0f172a); }
    @media (max-width:900px) { .an-product-result-count { order:3; width:100%; } .an-product-table-note { align-items:flex-start; flex-direction:column; gap:.2rem; } }
    @media (max-width:760px) { .an-product-portfolio-card .an-pulse-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .an-product-scope-pill { display:none; } }
    .an-list-row { padding:.55rem .6rem; border-bottom:1px solid var(--dsh-border); }
    .an-list-name { color:var(--text,#0f172a); font-size:.78rem; font-weight:650; }
    .an-list-meta, .an-product-sku { color:var(--dsh-muted); }
    .an-empty { color:var(--dsh-muted); }
    .gf-analytics-state.ds-empty { display:grid; place-items:center; min-height:160px; padding:1rem; text-align:center; border:1px dashed var(--dsh-border-strong,#d0d5dd); border-radius:13px; background:var(--hero-bg,#fcfcfd); }
    .gf-analytics-state.ds-empty > div { width:100%; }
    .gf-analytics-state .ds-empty-icon { display:grid; width:34px; height:34px; place-items:center; margin:0 auto .5rem; border-radius:11px; color:var(--gf-color-brand,#4f46e5); background:#eeefff; }
    .gf-analytics-state strong { display:block; color:var(--text,#101828); font-size:.75rem; font-weight:800; }
    .gf-analytics-state p { max-width:260px; margin:.22rem auto .7rem; color:var(--dsh-muted,#98a2b3); font-size:.65rem; font-weight:600; line-height:1.45; }
    .gf-analytics-state .ds-empty-loading-label { margin-bottom:0 !important; }
    .gf-analytics-state .ds-skeleton { display:grid; width:max-content; gap:.5rem; margin:0 auto .7rem; }
    .gf-analytics-state .ds-skeleton span { display:block; width:130px; height:10px; border-radius:999px; background:linear-gradient(90deg,#eef0f3,#f8f9fa,#eef0f3); background-size:180% 100%; animation:gf-analytics-shimmer 1.3s ease-in-out infinite; }
    .gf-analytics-state .ds-skeleton span:nth-child(2) { width:100px; }
    .gf-analytics-state .ds-skeleton span:nth-child(3) { width:72px; }
    .gf-analytics-state-error .ds-empty-icon { color:#b42318; background:#fef3f2; }
    .gf-analytics-state-error strong { color:#b42318; }
    .gf-analytics-state-error p { color:#b42318; }
    .an-empty:not(.gf-analytics-state) { display:grid; min-height:120px; place-items:center; padding:1rem; border:1px dashed var(--dsh-border-strong,#d0d5dd); border-radius:13px; background:var(--hero-bg,#fcfcfd); font-size:.68rem; }
    .an-error:not(.gf-analytics-state) { border-color:#fecaca; background:#fff7f7; }
    body[data-theme="dark"] .gf-analytics-state.ds-empty { border-color:var(--dsh-border,#334155); background:#0f172a; }
    body[data-theme="dark"] .gf-analytics-state strong { color:#f8fafc; }
    body[data-theme="dark"] .gf-analytics-state .ds-empty-icon { background:#1e1b4b; }
    body[data-theme="dark"] .gf-analytics-state-error .ds-empty-icon { background:#3f1d1d; }
    @keyframes gf-analytics-shimmer { from { background-position:180% 0; } to { background-position:-20% 0; } }
    @media (prefers-reduced-motion: reduce) { .gf-analytics-state .ds-skeleton span { animation:none; } }
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
    .an-modal-foot { justify-content:space-between; border-top:1px solid var(--dsh-border); border-bottom:0; background:var(--hero-bg,#f8fafc); }
    .an-modal-eyebrow { color:var(--dsh-muted); font-size:.6rem; font-weight:850; text-transform:uppercase; letter-spacing:.07em; }
    .an-modal-title { color:var(--text,#0f172a); font-size:.92rem; font-weight:800; margin-top:.12rem; }
    .an-modal-sub { color:var(--dsh-muted); font-size:.66rem; font-weight:600; margin-top:.18rem; }
    .an-modal-tabs { display:flex !important; flex:0 0 auto; min-height:3.35rem; gap:.25rem; padding:.45rem .85rem 0; border-bottom:1px solid var(--dsh-border); overflow-x:auto; visibility:visible; opacity:1; }
    .an-modal-tab { display:flex !important; visibility:visible; flex:0 0 auto; flex-direction:column; align-items:flex-start; gap:.12rem; border:0; border-bottom:2px solid transparent; padding:.5rem .65rem .42rem; background:transparent; color:var(--dsh-muted,#64748b); font-size:.68rem; font-weight:800; cursor:pointer; text-align:left; }
    .an-modal-tab:hover { color:var(--text,#0f172a); }
    .an-modal-tab.active { border-bottom-color:#2563eb; color:#2563eb; }
    .an-modal-tab small { color:var(--dsh-muted); font-size:.56rem; font-weight:700; line-height:1.1; white-space:nowrap; }
    .an-modal-tab.active small { color:#60a5fa; }
    .an-modal-close { width:30px; height:30px; border:1px solid var(--dsh-border); border-radius:8px; background:var(--card,#fff); color:var(--dsh-muted); font-size:1.2rem; line-height:1; cursor:pointer; }
    .an-modal-close:hover { color:var(--text,#0f172a); background:var(--hero-bg,#f8fafc); }
    .an-modal-summary { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.5rem; padding:.7rem .85rem; border-bottom:1px solid var(--dsh-border); }
    .an-modal-stat { min-width:0; padding:.55rem .6rem; border:1px solid var(--dsh-border); border-radius:10px; background:var(--hero-bg,#f8fafc); }
    .an-modal-stat-label { color:var(--dsh-muted); font-size:.58rem; font-weight:800; }
    .an-modal-stat-value { color:var(--text,#0f172a); font-size:.78rem; font-weight:850; margin-top:.2rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .an-modal-stat-note { display:block; margin-top:.16rem; color:var(--dsh-muted); font-size:.55rem; font-weight:700; }
    .an-modal-body { min-height:130px; overflow:auto; padding:.25rem .85rem .65rem; }
    .an-revenue-workspace { --revenue-brand:var(--gf-color-brand,#5b5ce2); --revenue-brand-strong:var(--gf-color-brand-strong,#4142bb); --revenue-accent:var(--gf-color-accent,#13b8c4); --revenue-text:var(--gf-color-content,#101828); --revenue-muted:var(--gf-color-muted,#667085); --revenue-surface:var(--gf-color-surface,#fff); --revenue-muted-surface:var(--gf-color-surface-muted,#f2f4f7); --revenue-line:var(--gf-color-line,#e4e7ec); --revenue-success:var(--gf-color-success,#14945a); --revenue-warning:var(--gf-color-warning,#b7791f); --revenue-danger:var(--gf-color-danger,#d6455d); margin-top:.8rem; }
    .an-revenue-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; padding:1rem 1.05rem; border:1px solid #e0e2fb; border-radius:var(--gf-radius-card,16px) var(--gf-radius-card,16px) 0 0; background:linear-gradient(120deg,#f4f3ff 0%,var(--revenue-surface) 52%,#edfcff 100%); box-shadow:var(--gf-shadow-sm,0 1px 2px rgba(16,24,40,.04)); }
    .an-revenue-head-copy { display:flex; align-items:flex-start; gap:.7rem; min-width:0; }
    .an-revenue-head-icon { display:grid; flex:0 0 38px; width:38px; height:38px; place-items:center; border-radius:12px; color:var(--revenue-brand-strong); background:#e8e8ff; box-shadow:0 6px 12px rgba(91,92,226,.14); font-size:.9rem; }
    .an-revenue-head-meta { display:grid; justify-items:end; gap:.5rem; flex:0 0 auto; color:var(--dsh-muted); font-size:.62rem; font-weight:750; white-space:nowrap; }
    .an-revenue-status, .an-revenue-period { display:inline-flex; align-items:center; gap:.35rem; }
    .an-revenue-status { padding:.35rem .5rem; border:1px solid color-mix(in srgb,var(--revenue-success) 28%,var(--revenue-line)); border-radius:999px; color:var(--revenue-success); background:#effaf4; font-size:.58rem; font-weight:850; }
    .an-revenue-status i { color:var(--revenue-success); }
    .an-revenue-period i { color:var(--revenue-brand); }
    .an-revenue-panel { overflow:hidden; border:1px solid var(--revenue-line); border-top:0; border-radius:0 0 var(--gf-radius-card,16px) var(--gf-radius-card,16px); background:var(--revenue-surface); box-shadow:var(--gf-shadow-sm,0 1px 2px rgba(16,24,40,.04)); }
    .an-revenue-panel .an-modal-tabs { border-bottom-color:var(--revenue-line); }
    .an-revenue-tabs { gap:.1rem; padding:.15rem .65rem 0; border-bottom:1px solid var(--revenue-line); background:var(--revenue-surface); scrollbar-width:thin; }
    .an-revenue-tabs .an-modal-tab { position:relative; min-height:var(--gf-touch-target,44px); justify-content:center; border:0; border-radius:0; padding:.55rem .65rem .5rem; color:var(--revenue-muted); background:transparent; transition:color .18s ease, background .18s ease; }
    .an-revenue-tabs .an-modal-tab::after { position:absolute; right:.55rem; bottom:-1px; left:.55rem; height:2px; border-radius:999px; background:var(--revenue-brand); content:''; opacity:0; transform:scaleX(.45); transition:opacity .18s ease, transform .18s ease; }
    .an-revenue-tabs .an-modal-tab:hover { color:var(--revenue-brand-strong); background:#faf9ff; }
    .an-revenue-tabs .an-modal-tab:focus-visible { outline:3px solid rgba(91,92,226,.2); outline-offset:-3px; }
    .an-revenue-tabs .an-modal-tab.active { border-bottom-color:transparent; color:var(--revenue-brand-strong); background:transparent; box-shadow:none; }
    .an-revenue-tabs .an-modal-tab.active::after { opacity:1; transform:scaleX(1); }
    .an-revenue-tabs .an-modal-tab.active small { color:var(--revenue-brand-strong); background:#eeefff; }
    .an-revenue-tab-label { display:inline-flex; align-items:center; gap:.35rem; white-space:nowrap; }
    .an-revenue-tab-label i { color:currentColor; font-size:.7rem; }
    .an-revenue-tabs .an-modal-tab small { display:inline-flex; align-items:center; align-self:flex-start; min-height:18px; padding:0 .35rem; border-radius:999px; color:var(--revenue-muted); background:var(--revenue-muted-surface); font-size:.54rem; font-weight:750; line-height:1; white-space:nowrap; }
    .an-revenue-section { min-width:0; border-top:1px solid var(--revenue-line); }
    .an-revenue-section-head { display:flex; align-items:flex-end; justify-content:space-between; gap:1rem; padding:.82rem .85rem .3rem; }
    .an-revenue-section-head > div { min-width:0; }
    .an-revenue-section-kicker { display:block; color:var(--revenue-brand-strong); font-size:.55rem; font-weight:850; letter-spacing:.09em; text-transform:uppercase; }
    .an-revenue-section-head strong { display:block; margin-top:.16rem; color:var(--revenue-text); font-size:.76rem; font-weight:850; letter-spacing:-.01em; }
    .an-revenue-section-hint { color:var(--revenue-muted); font-size:.58rem; font-weight:650; text-align:right; }
    .an-revenue-detail-section .an-revenue-section-head { padding-bottom:.55rem; }
    .an-revenue-summary { grid-template-columns:repeat(4,minmax(0,1fr)); gap:.65rem; padding:.55rem .85rem .85rem; background:var(--revenue-surface); }
    .an-revenue-summary .an-modal-stat { min-height:82px; padding:.7rem .72rem; border-radius:var(--gf-radius-control,10px); background:var(--revenue-surface); box-shadow:var(--gf-shadow-sm,0 1px 2px rgba(16,24,40,.04)); }
    .an-revenue-summary .an-modal-stat:nth-child(-n+2) { border-color:#dfe1ff; background:#f6f6ff; }
    .an-revenue-summary .an-modal-stat:nth-child(3) { border-color:#bcecef; background:#effdfd; }
    .an-revenue-summary .an-modal-stat-label { font-size:.57rem; }
    .an-revenue-summary .an-modal-stat-value { font-size:.86rem; font-variant-numeric:tabular-nums; }
    .an-revenue-summary .an-modal-stat-note { line-height:1.3; }
    .an-revenue-summary .an-modal-stat-primary { border-color:#dfe1ff; background:#f6f6ff; }
    .an-revenue-summary .an-modal-stat-accent { border-color:#bcecef; background:#effdfd; }
    .an-revenue-summary .an-modal-stat-fee { border-color:#f3dfbc; background:#fffaf0; }
    .an-revenue-summary .an-modal-stat-shipping { border-color:#c8e2f4; background:#f2faff; }
    .an-revenue-summary .an-modal-stat-tax { border-color:#f1cbd3; background:#fff6f7; }
    .an-revenue-panel .an-modal-body { min-height:180px; max-height:none; overflow:visible; }
    .an-revenue-panel .an-modal-foot { background:var(--revenue-muted-surface); }
    body[data-theme="dark"] .an-revenue-head { border-color:#3b3e88; background:linear-gradient(135deg,#211f4c 0%,#1e293b 55%,#103d45 100%); }
    body[data-theme="dark"] .an-revenue-status { border-color:rgba(134,239,172,.25); color:#bbf7d0; background:rgba(22,101,52,.2); }
    body[data-theme="dark"] .an-revenue-tabs { background:var(--card,#1e293b); }
    body[data-theme="dark"] .an-revenue-tabs .an-modal-tab:hover { background:rgba(91,92,226,.12); }
    body[data-theme="dark"] .an-revenue-tabs .an-modal-tab small { background:#0f172a; }
    body[data-theme="dark"] .an-revenue-tabs .an-modal-tab.active { background:transparent; }
    body[data-theme="dark"] .an-revenue-summary { background:var(--card,#1e293b); }
    body[data-theme="dark"] .an-revenue-section { border-color:#334155; }
    body[data-theme="dark"] .an-revenue-summary .an-modal-stat:nth-child(-n+2) { background:rgba(91,92,226,.18); border-color:rgba(129,140,248,.45); }
    body[data-theme="dark"] .an-revenue-summary .an-modal-stat:nth-child(3) { background:rgba(6,78,59,.25); border-color:rgba(45,212,191,.35); }
    body[data-theme="dark"] .an-revenue-summary .an-modal-stat-primary { background:rgba(49,46,129,.25); border-color:rgba(129,140,248,.45); }
    body[data-theme="dark"] .an-revenue-summary .an-modal-stat-accent { background:rgba(6,78,59,.25); border-color:rgba(45,212,191,.35); }
    body[data-theme="dark"] .an-revenue-summary .an-modal-stat-fee { background:rgba(124,45,18,.25); border-color:rgba(251,146,60,.38); }
    body[data-theme="dark"] .an-revenue-summary .an-modal-stat-shipping { background:rgba(30,64,175,.25); border-color:rgba(96,165,250,.38); }
    body[data-theme="dark"] .an-revenue-summary .an-modal-stat-tax { background:rgba(127,29,29,.25); border-color:rgba(248,113,113,.38); }
    @media (max-width:900px) { .an-revenue-summary { grid-template-columns:repeat(3,minmax(0,1fr)); } }
    @media (max-width:760px) { .an-revenue-head { flex-direction:column; gap:.6rem; } .an-revenue-head-meta { justify-items:start; white-space:normal; } .an-revenue-section-head { align-items:flex-start; flex-direction:column; gap:.2rem; padding-inline:.65rem; } .an-revenue-section-hint { text-align:left; } .an-revenue-summary { grid-template-columns:repeat(2,minmax(0,1fr)); padding:.55rem .65rem .7rem; } }
    @media (max-width:420px) { .an-revenue-summary { grid-template-columns:1fr; } }
    @media (prefers-reduced-motion:reduce) { .an-revenue-tabs .an-modal-tab { transition:none; } }
    .an-modal-fee-breakdown { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.35rem .65rem; margin:.45rem 0 .7rem; padding:.65rem .7rem; border:1px solid var(--dsh-border); border-radius:10px; background:var(--hero-bg,#f8fafc); }
    .an-modal-fee-breakdown div { display:flex; align-items:center; justify-content:space-between; gap:.5rem; color:var(--dsh-muted); font-size:.64rem; font-weight:700; }
    .an-modal-fee-breakdown strong { color:var(--text,#0f172a); font-weight:850; }
    .an-modal-fee-breakdown small { display:block; margin-top:.12rem; color:var(--dsh-muted); font-size:.55rem; font-weight:700; text-align:right; }
    .an-revenue-fee-breakdown { margin:.35rem 0 .8rem; padding:.75rem .8rem .2rem; border:1px solid #dfe1ff; border-radius:var(--gf-radius-card,16px); background:linear-gradient(180deg,#f8f7ff 0%,var(--revenue-muted-surface) 100%); box-shadow:var(--gf-shadow-sm,0 1px 2px rgba(16,24,40,.04)); }
    .an-revenue-fee-breakdown-head { display:flex; align-items:flex-start; justify-content:space-between; gap:.75rem; margin-bottom:.15rem; }
    .an-revenue-fee-breakdown-kicker { color:var(--revenue-brand-strong); font-size:.55rem; font-weight:850; letter-spacing:.08em; text-transform:uppercase; }
    .an-revenue-fee-breakdown-head strong { display:block; margin-top:.12rem; color:var(--revenue-text); font-size:.76rem; font-weight:850; }
    .an-revenue-fee-breakdown-head small { max-width:220px; color:var(--revenue-muted); font-size:.57rem; font-weight:650; line-height:1.35; text-align:right; }
    .an-revenue-fee-breakdown-total { flex:0 0 auto; text-align:right; }
    .an-revenue-fee-breakdown-total strong { color:var(--revenue-text); font-size:.9rem; font-variant-numeric:tabular-nums; }
    .an-revenue-fee-breakdown-total small { display:block; max-width:none; margin-top:.08rem; color:var(--revenue-brand-strong); font-weight:800; }
    .an-revenue-fee-breakdown .an-modal-fee-breakdown { margin:.55rem 0 .45rem; border-color:rgba(148,163,184,.35); background:rgba(255,255,255,.68); }
    body[data-theme="dark"] .an-revenue-fee-breakdown { border-color:rgba(129,140,248,.45); background:linear-gradient(180deg,rgba(49,46,129,.25) 0%,#0f172a 100%); }
    body[data-theme="dark"] .an-revenue-fee-breakdown .an-modal-fee-breakdown { background:#0f172a; }
    @media (max-width:560px) { .an-revenue-fee-breakdown-head { flex-direction:column; gap:.2rem; } .an-revenue-fee-breakdown-head small { max-width:none; text-align:left; } }
    .an-cash-table { min-width:1040px; }
    .an-cash-table td { vertical-align:top; }
    .an-cash-payment { display:inline-block; max-width:140px; color:var(--revenue-text); font-size:.64rem; font-weight:800; line-height:1.25; overflow-wrap:anywhere; }
    .an-cash-order { color:var(--revenue-text); font-weight:800; white-space:nowrap; }
    .an-cash-status { display:inline-block; margin-top:.22rem; padding:.16rem .35rem; border:1px solid #bce7cd; border-radius:999px; background:#effaf4; color:#117346; font-size:.58rem; font-weight:800; text-transform:capitalize; }
    .an-cash-meta { display:block; margin-top:.18rem; color:var(--dsh-muted); font-size:.6rem; font-weight:600; white-space:nowrap; }
    .an-cash-detail { margin-top:.35rem; font-size:.6rem; font-weight:650; }
    .an-cash-detail summary { color:var(--revenue-brand-strong); cursor:pointer; list-style:none; }
    .an-cash-detail summary::-webkit-details-marker { display:none; }
    .an-cash-detail summary::before { content:'+'; display:inline-block; width:.85rem; color:var(--dsh-muted); }
    .an-cash-detail[open] summary::before { content:'−'; }
    .an-cash-detail-grid { display:grid; grid-template-columns:repeat(2,minmax(130px,1fr)); gap:.22rem .75rem; margin-top:.35rem; padding:.45rem .55rem; border:1px solid var(--dsh-border); border-radius:8px; background:var(--hero-bg,#f8fafc); color:var(--dsh-muted); }
    .an-cash-detail-grid span { display:flex; justify-content:space-between; gap:.4rem; }
    .an-cash-detail-grid strong { color:var(--text,#0f172a); font-weight:800; }
    .an-cash-money { color:var(--revenue-text); font-weight:800; white-space:nowrap; }
    .an-cash-money.good { color:var(--revenue-success); }
    .an-cash-money.fee { color:var(--revenue-warning); }
    .an-cash-money.affiliate { color:var(--revenue-brand-strong); }
    .an-revenue-body .an-cash-table td[data-label] { font-variant-numeric:tabular-nums; }
    .an-revenue-body .an-cash-table td[data-label="Order"] { font-variant-numeric:normal; }
    .an-cash-group { margin-bottom:.85rem; }
    .an-cash-group:last-child { margin-bottom:0; }
    .an-cash-group-head { display:flex; align-items:center; justify-content:space-between; gap:.6rem; margin-bottom:.35rem; padding:.5rem .65rem; border:1px solid var(--dsh-border); border-radius:8px; background:var(--hero-bg,#f8fafc); color:var(--text,#0f172a); font-size:.68rem; }
    .an-cash-group-head span { color:var(--dsh-muted); font-size:.6rem; font-weight:700; }
    .an-exception-kind { display:inline-block; margin-top:.22rem; padding:.16rem .35rem; border-radius:5px; background:#fee2e2; color:#991b1b; font-size:.58rem; font-weight:800; }
    .an-exception-kind.return { background:#fef3c7; color:#92400e; }
    .an-modal-pagination { display:flex; align-items:center; justify-content:center; min-width:0; gap:.45rem; text-align:center; }
    .an-modal-page { color:var(--text,#0f172a); font-size:.66rem; font-weight:850; white-space:nowrap; }
    .an-modal-page-meta { display:block; margin-top:.12rem; color:var(--dsh-muted); font-size:.56rem; font-weight:650; white-space:nowrap; }
    .an-modal-foot .an-btn:disabled { cursor:not-allowed; opacity:.42; }
    body.an-modal-open { overflow:hidden; }
    body[data-theme="dark"] .an-modal-dialog, body[data-theme="dark"] .an-modal-close { background:var(--card,#1e293b); }
    body[data-theme="dark"] .an-modal-stat, body[data-theme="dark"] .an-cash-detail-grid { background:#0f172a; }
    body[data-theme="dark"] .an-modal-fee-breakdown { background:#0f172a; }
    @media (max-width:760px) { .an-modal-dialog { width:calc(100% - .75rem); max-height:calc(100vh - .75rem); margin:.375rem auto; } .an-modal-summary { grid-template-columns:repeat(2,minmax(0,1fr)); } .an-modal-body { padding-inline:.5rem; } .an-modal-head { padding-inline:.65rem; } .an-modal-foot { flex-wrap:wrap; gap:.45rem; } .an-modal-foot > .an-btn { flex:1 1 0; } .an-modal-pagination { order:-1; width:100%; } .an-revenue-body .an-table-wrap { overflow:visible; } .an-revenue-body .an-cash-table { min-width:0; border-collapse:separate; border-spacing:0 .55rem; } .an-revenue-body .an-cash-table thead { display:none; } .an-revenue-body .an-cash-table tbody { display:grid; gap:.55rem; } .an-revenue-body .an-cash-table tr { display:grid; gap:.35rem; padding:.7rem; border:1px solid var(--gf-color-line,var(--dsh-border)); border-radius:13px; background:var(--gf-color-surface,var(--card,#fff)); box-shadow:var(--gf-shadow-sm,0 1px 2px rgba(16,24,40,.04)); } .an-revenue-body .an-cash-table td { display:grid; grid-template-columns:minmax(92px,.55fr) minmax(0,1fr); align-items:start; gap:.65rem; padding:.32rem 0; border-bottom:1px solid var(--gf-color-line,#f0f1f3); text-align:right !important; white-space:normal; } .an-revenue-body .an-cash-table td::before { color:var(--dsh-muted); content:attr(data-label); font-size:.57rem; font-weight:800; letter-spacing:.03em; text-align:left; text-transform:uppercase; } .an-revenue-body .an-cash-table td:first-child { display:block; padding-top:0; text-align:left !important; } .an-revenue-body .an-cash-table td:first-child::before { display:none; } .an-revenue-body .an-cash-table td:last-child { padding-bottom:0; border-bottom:0; } .an-revenue-body .an-cash-table td:nth-child(8) { display:none; } .an-revenue-body .an-cash-table .an-cash-payment { max-width:none; text-align:right; } .an-revenue-body .an-cash-table .an-cash-detail-grid { grid-template-columns:1fr; } body[data-theme="dark"] .an-revenue-body .an-cash-table tr { background:var(--card,#1e293b); } }
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
    .an-hero { position:relative; min-height:96px; margin-inline:-.75rem; padding:1rem 1.25rem; border:1px solid #1e3a5f; border-radius:18px; background:linear-gradient(135deg,#0b1220 0%,#10233f 62%,#12395a 100%); box-shadow:0 18px 34px rgba(15,23,42,.16); align-items:center; }
    .an-hero::after { content:''; position:absolute; inset:auto 1.2rem 0 auto; width:210px; height:76px; border-radius:100% 0 0 0; background:radial-gradient(ellipse at bottom right,rgba(56,189,248,.2),transparent 68%); pointer-events:none; }
    .an-hero-copy { position:relative; z-index:1; }
    .an-hero-eyebrow { display:flex; align-items:center; gap:.4rem; color:#93c5fd; font-size:.61rem; font-weight:850; letter-spacing:.11em; text-transform:uppercase; }
    .an-hero-eyebrow::before { content:''; width:7px; height:7px; border-radius:99px; background:#38bdf8; box-shadow:0 0 0 4px rgba(56,189,248,.16); }
    .an-hero-title { margin-top:.25rem; color:#fff; font-size:1.35rem; font-weight:850; letter-spacing:-.04em; }
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

    /* Cohort minimal enterprise surface: one clear action area, compact metrics, and a data-first matrix. */
    .an-cohort-workspace { gap:.75rem; }
    .an-cohort-pagebar { display:flex; align-items:flex-end; justify-content:space-between; gap:1rem; padding:.2rem 0 .35rem; }
    .an-cohort-breadcrumb { color:#64748b; font-size:.58rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; }
    .an-cohort-title { margin:.32rem 0 0; color:#0f172a; font-size:1.28rem; font-weight:850; letter-spacing:-.04em; }
    .an-cohort-description { margin:.22rem 0 0; color:#64748b; font-size:.68rem; font-weight:600; }
    .an-cohort-pagebar-meta { display:grid; grid-template-columns:auto auto; align-items:center; gap:.1rem .5rem; min-width:160px; padding-left:1rem; border-left:1px solid #e2e8f0; }
    .an-cohort-pagebar-meta .an-cohort-period-label { grid-column:1/-1; color:#64748b; font-size:.55rem; font-weight:800; letter-spacing:.07em; text-align:left; text-transform:uppercase; }
    .an-cohort-pagebar-meta > strong { color:#0f172a; font-size:.7rem; font-weight:850; }
    .an-cohort-pagebar-meta .an-cohort-reset { justify-self:end; padding:.15rem 0; font-size:.6rem; }
    .an-cohort-toolbar { padding:.75rem .85rem .65rem; border:1px solid #dbe3ee; border-radius:12px; background:#fff; box-shadow:0 6px 14px rgba(15,23,42,.035); }
    .an-cohort-toolbar-head { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding-bottom:.6rem; border-bottom:1px solid #eef2f7; }
    .an-cohort-control-eyebrow, .an-cohort-panel-eyebrow { color:#64748b; font-size:.53rem; font-weight:850; letter-spacing:.1em; text-transform:uppercase; }
    .an-cohort-control-title { margin:.18rem 0 0; color:#0f172a; font-size:.75rem; font-weight:850; }
    .an-cohort-toolbar-note { color:#64748b; font-size:.58rem; font-weight:650; white-space:nowrap; }
    .an-cohort-primary-controls { grid-template-columns:1.1fr 1.1fr 1.1fr auto; padding:.7rem 0; }
    .an-cohort-field { gap:.2rem; }
    .an-cohort-field label { color:#64748b; font-size:.56rem; font-weight:800; }
    .an-cohort-field select { min-height:32px; border-color:#d5deea; border-radius:7px; font-size:.64rem; }
    .an-cohort-apply { align-self:end; min-height:32px; padding-inline:.85rem; border-radius:7px; background:#0f172a; border-color:#0f172a; font-size:.63rem; font-weight:800; }
    .an-cohort-apply:hover { background:#1e293b; border-color:#1e293b; }
    .an-cohort-advanced { border-top:1px solid #eef2f7; }
    .an-cohort-advanced summary { padding:.55rem 0 .4rem; color:#334155; font-size:.6rem; }
    .an-cohort-advanced summary small { color:#94a3b8; font-size:.55rem; font-weight:600; }
    .an-cohort-advanced-grid { padding-bottom:.45rem; }
    .an-cohort-active-filters { margin-top:.25rem; padding-top:.5rem; border-top:1px solid #eef2f7; }
    .an-cohort-filter-caption { color:#94a3b8; font-size:.55rem; }
    .an-filter-chip { padding:.2rem .4rem; border-color:#e2e8f0; background:#f8fafc; font-size:.55rem; }
    .an-cohort-summary-grid { gap:.5rem; }
    .an-cohort-summary-card { min-height:76px; padding:.65rem .7rem; border-color:#dbe3ee; border-radius:10px; box-shadow:none; }
    .an-cohort-summary-card::before { height:2px; background:#2563eb; }
    .an-cohort-summary-card:nth-child(3)::before { background:#0f766e; }
    .an-cohort-summary-card:nth-child(4)::before { background:#d97706; }
    .an-cohort-summary-card.is-primary { border-color:#cbd5e1; background:#f8fafc; }
    .an-cohort-summary-card.is-primary::before { background:#0f172a; }
    .an-cohort-summary-card.is-primary .an-cohort-summary-label, .an-cohort-summary-card.is-primary .an-cohort-summary-value, .an-cohort-summary-card.is-primary .an-cohort-summary-note { color:#0f172a; }
    .an-cohort-summary-label { color:#64748b; font-size:.54rem; }
    .an-cohort-summary-value { margin-top:.15rem; color:#0f172a; font-size:.95rem; }
    .an-cohort-summary-note { color:#94a3b8; font-size:.55rem; }
    .an-cohort-insights { gap:.65rem; }
    .an-cohort-chart-card, .an-cohort-matrix-card { border-color:#dbe3ee; border-radius:12px; box-shadow:none; }
    .an-cohort-chart-card .an-enterprise-head, .an-cohort-matrix-card .an-enterprise-head { padding:.75rem .85rem .55rem; background:#fff; }
    .an-cohort-panel-eyebrow { color:#94a3b8; }
    .an-enterprise-title { color:#0f172a; font-size:.75rem; font-weight:850; }
    .an-enterprise-sub { color:#64748b; font-size:.59rem; }
    .an-cohort-panel-badge { padding:.2rem .35rem; border-radius:5px; font-size:.52rem; }
    .an-cohort-chart-body { padding:.1rem .65rem .55rem; }
    .an-cohort-chart-canvas { height:185px; background:#fff; }
    .an-cohort-chart-canvas canvas { height:185px !important; }
    .an-cohort-matrix-card .an-enterprise-head { align-items:center; border-bottom:1px solid #eef2f7; }
    .an-cohort-matrix-body { padding:.55rem .65rem .65rem; }
    .an-cohort-note { margin:0 0 .5rem; padding:.45rem .55rem; border-color:#e2e8f0; border-radius:7px; background:#f8fafc; color:#64748b; font-size:.58rem; font-weight:650; }
    .an-cohort-reading-bar { gap:.65rem; margin-bottom:.5rem; padding:.42rem .55rem; border-color:#e2e8f0; border-radius:7px; background:#f8fafc; }
    .an-cohort-reading-item { gap:.22rem; color:#334155; font-size:.58rem; }
    .an-cohort-reading-item i { display:none; }
    .an-cohort-reading-item small { color:#94a3b8; font-size:.54rem; }
    .an-cohort-reading-hint { color:#64748b; font-size:.55rem; }
    .an-cohort-table-wrap { border-color:#dbe3ee; border-radius:8px; }
    .an-cohort-table thead th { color:#64748b; background:#f8fafc; font-size:.55rem; }
    .an-cohort-table tbody td { padding:.35rem .25rem; }
    .an-cohort-row-title { color:#0f172a; font-size:.63rem; }
    .an-cohort-row-sub { color:#94a3b8; font-size:.53rem; }
    .an-cohort-cell { border-radius:5px; padding:.3rem .2rem; font-size:.61rem; }
    .an-cohort-cell-sub { color:#64748b; font-size:.49rem; }
    .an-cohort-help-card { border:1px solid #dbe3ee; border-radius:10px; background:#fff; }
    .an-cohort-help-card summary { display:flex; align-items:center; justify-content:space-between; padding:.55rem .7rem; color:#334155; font-size:.6rem; font-weight:800; cursor:pointer; list-style:none; }
    .an-cohort-help-card summary::-webkit-details-marker { display:none; }
    .an-cohort-help-card summary::after { content:'+'; color:#94a3b8; font-size:.8rem; font-weight:500; }
    .an-cohort-help-card[open] summary::after { content:'−'; }
    .an-cohort-help-card summary small { margin-left:auto; margin-right:.55rem; color:#94a3b8; font-size:.54rem; font-weight:600; }
    .an-cohort-help-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.5rem; padding:.65rem .7rem .7rem; border-top:1px solid #eef2f7; }
    .an-cohort-help-grid > div { padding-left:.5rem; border-left:2px solid #e2e8f0; }
    .an-cohort-help-grid strong { color:#0f172a; font-size:.6rem; font-weight:850; }
    .an-cohort-help-grid p { margin:.15rem 0 0; color:#64748b; font-size:.55rem; line-height:1.4; }
    body[data-theme="dark"] .an-cohort-pagebar-meta { border-color:#334155; }
    body[data-theme="dark"] .an-cohort-title, body[data-theme="dark"] .an-cohort-pagebar-meta > strong, body[data-theme="dark"] .an-cohort-control-title, body[data-theme="dark"] .an-enterprise-title, body[data-theme="dark"] .an-cohort-row-title, body[data-theme="dark"] .an-cohort-help-grid strong { color:#e2e8f0; }
    body[data-theme="dark"] .an-cohort-toolbar, body[data-theme="dark"] .an-cohort-chart-card .an-enterprise-head, body[data-theme="dark"] .an-cohort-matrix-card .an-enterprise-head, body[data-theme="dark"] .an-cohort-help-card { background:#1e293b; }
    body[data-theme="dark"] .an-cohort-summary-card.is-primary, body[data-theme="dark"] .an-cohort-note, body[data-theme="dark"] .an-cohort-reading-bar, body[data-theme="dark"] .an-cohort-help-card summary { background:#0f172a; }
    @media (max-width:1100px) { .an-cohort-primary-controls { grid-template-columns:repeat(2,minmax(0,1fr)); } .an-cohort-apply { width:auto; } .an-cohort-help-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width:760px) { .an-cohort-pagebar { align-items:flex-start; flex-direction:column; } .an-cohort-pagebar-meta { width:100%; padding:0; border-left:0; border-top:1px solid #e2e8f0; padding-top:.55rem; } .an-cohort-toolbar-head { align-items:flex-start; flex-direction:column; gap:.25rem; } .an-cohort-toolbar-note { white-space:normal; } .an-cohort-primary-controls, .an-cohort-advanced-grid { grid-template-columns:1fr; } .an-cohort-apply { width:100%; } .an-cohort-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .an-cohort-insights { grid-template-columns:1fr; } .an-cohort-legend { display:none; } .an-cohort-help-grid { grid-template-columns:1fr 1fr; } }
    @media (max-width:420px) { .an-cohort-summary-grid, .an-cohort-help-grid { grid-template-columns:1fr; } .an-cohort-help-card summary { align-items:flex-start; flex-direction:column; gap:.2rem; } .an-cohort-help-card summary small { margin:0; } }

    /* Align analytics with the Marketplace Sales dashboard visual system. */
    @media (min-width: 768px) {
        .app-main .page-wrap:has(.an-page) { max-width: none; }
        .app-main .dash:has(.an-page) { width: 100%; max-width: none; }
    }

    .an-page {
        max-width: 1480px;
        padding-inline: clamp(.75rem, 1.6vw, 1.5rem);
    }

    .an-shell { gap: .9rem; }

    .an-summary-top-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .9rem;
    }

    .an-hero {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        min-height: 0;
        margin: 0 0 1rem;
        padding: 0;
        border: 0;
        border-radius: 0;
        background: transparent;
        box-shadow: none;
    }

    .an-hero::after { display: none; }
    .an-hero-copy { position: static; }
    .an-hero-eyebrow {
        display: block;
        color: var(--dsh-muted, #6b7280);
        font-size: .72rem;
        font-weight: 600;
        letter-spacing: 0;
        text-transform: none;
    }
    .an-hero-eyebrow::before { display: none; }
    .an-hero-title {
        margin-top: 0;
        color: var(--text, #0f172a);
        font-size: clamp(1.35rem, 1.8vw, 1.7rem);
        font-weight: 800;
        letter-spacing: -.035em;
    }
    .an-hero-title i { color: var(--accent, #2563eb); }

    .an-hero-controls {
        position: static;
        display: flex;
        align-items: flex-end;
        justify-content: flex-end;
        flex: 0 1 auto;
        gap: .55rem;
        width: 100%;
        margin-top: .15rem;
        padding: .85rem;
        border: 1px solid var(--dsh-border, #d4d7e3);
        border-top: 3px solid color-mix(in srgb, var(--accent, #2563eb) 76%, var(--dsh-border, #d4d7e3) 24%);
        border-radius: 10px;
        background: var(--card, #fff);
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 8px 24px rgba(15, 23, 42, .045);
        backdrop-filter: none;
    }
    .an-hero-controls .an-field { display: block; min-width: 0; }
    .an-hero-controls .an-field label {
        display: flex;
        align-items: center;
        gap: .3rem;
        margin: 0 0 .28rem .1rem;
        color: var(--dsh-muted, #6b7280);
        font-size: .61rem;
        font-weight: 800;
        letter-spacing: .055em;
        line-height: 1;
        text-transform: uppercase;
    }
    .an-hero-controls .an-field input,
    .an-hero-controls .an-field select {
        min-height: 34px;
        min-width: 0;
        border-color: var(--dsh-border, #d4d7e3);
        border-radius: 8px;
        background: var(--card, #fff);
        color: var(--text, #0f172a);
        font-size: .72rem;
        font-weight: 650;
        box-shadow: none;
    }
    .an-hero-controls .an-field:first-child { width: 210px; }
    .an-hero-controls .an-field:nth-child(2) { width: 230px; }
    .an-hero-controls .an-field:nth-child(3) { width: 220px; }
    .an-hero-controls .an-field input:focus,
    .an-hero-controls .an-field select:focus {
        border-color: var(--accent, #2563eb);
        box-shadow: 0 0 0 .18rem color-mix(in srgb, var(--accent, #2563eb) 14%, transparent);
    }
    .an-btn {
        min-height: 34px;
        border-radius: 8px;
        font-size: .72rem;
        font-weight: 750;
    }
    .an-hero .an-btn-dark,
    .an-btn-dark {
        background: var(--accent, #2563eb);
        border-color: var(--accent, #2563eb);
        color: #fff;
    }
    .an-hero .an-btn-dark:hover,
    .an-btn-dark:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
        color: #fff;
    }

    .an-tabs-wrap {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .28rem;
        border: 1px solid var(--dsh-border, #d4d7e3);
        border-radius: 10px;
        background: color-mix(in srgb, var(--card, #fff) 94%, var(--bg, #f4f5fb) 6%);
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    }
    .an-tabs-meta {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        flex: 0 0 auto;
        padding: .45rem .6rem .45rem .65rem;
        color: var(--dsh-muted, #6b7280);
        font-size: .61rem;
        font-weight: 800;
        letter-spacing: .055em;
        line-height: 1;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .an-tabs-meta i { color: var(--accent, #2563eb); font-size: .7rem; }
    .an-tabs {
        display: flex;
        gap: .18rem;
        width: 100%;
        padding: 0;
        border: 0;
        border-radius: 0;
        background: transparent;
        box-shadow: none;
    }
    .an-tab {
        border-radius: 7px;
        padding: .58rem .72rem;
        color: var(--dsh-muted, #6b7280);
        font-size: .72rem;
        font-weight: 750;
        letter-spacing: .01em;
    }
    .an-tab:hover { color: var(--accent, #2563eb); background: var(--accent-soft, #dbeafe); }
    .an-tab.active {
        color: #fff;
        background: var(--accent, #2563eb);
        box-shadow: 0 2px 5px color-mix(in srgb, var(--accent, #2563eb) 22%, transparent);
    }

    .an-enterprise-card,
    .an-card,
    .an-control-tower {
        border-radius: 10px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .05), 0 8px 24px rgba(15, 23, 42, .045);
    }
    .an-enterprise-head,
    .an-card-head,
    .an-control-head { border-bottom-color: color-mix(in srgb, var(--dsh-border, #d4d7e3) 76%, transparent); }
    .an-enterprise-title,
    .an-card-title,
    .an-control-title { font-size: .95rem; font-weight: 800; letter-spacing: -.01em; }
    .an-enterprise-sub,
    .an-card-sub,
    .an-control-sub { color: var(--dsh-muted, #6b7280); }

    .an-kpi {
        min-height: 132px;
        border-radius: 10px;
        border-left: 3px solid color-mix(in srgb, var(--accent, #2563eb) 58%, var(--dsh-border, #d4d7e3) 42%);
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 5px 16px rgba(15, 23, 42, .035);
    }
    .an-kpi.primary {
        background: var(--card, #fff);
        border-color: var(--dsh-border, #d4d7e3);
        border-left-color: var(--accent, #2563eb);
    }
    .an-kpi.kpi-cancelled,
    .an-kpi.kpi-refund { border-left-color: var(--danger, #dc2626); }
    .an-kpi-value { font-size: clamp(1.05rem, 1.4vw, 1.32rem); font-variant-numeric: tabular-nums; }
    .an-kpi-note { color: var(--dsh-muted, #6b7280); }

    .an-table th {
        background: color-mix(in srgb, var(--bg, #f4f5fb) 72%, var(--card, #fff) 28%);
        color: var(--dsh-muted, #6b7280);
        font-size: .62rem;
        letter-spacing: .075em;
    }
    .an-table td { font-variant-numeric: tabular-nums; }
    .an-table tbody tr:hover td { background: color-mix(in srgb, var(--accent-soft, #dbeafe) 22%, var(--card, #fff) 78%); }

    .an-sales-pane { display: grid; gap: .8rem; }
    .an-sales-head,
    .an-sales-section-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
    .an-sales-head { padding: .2rem .1rem .15rem; }
    .an-sales-title,
    .an-sales-section-title { color: var(--text, #0f172a); font-size: 1.02rem; font-weight: 850; letter-spacing: -.025em; }
    .an-sales-sub,
    .an-sales-section-sub { margin-top: .2rem; color: var(--dsh-muted, #6b7280); font-size: .69rem; font-weight: 650; line-height: 1.45; }
    .an-sales-section-title { font-size: .92rem; }
    .an-sales-period { display: inline-flex; align-items: center; gap: .35rem; color: var(--dsh-muted, #6b7280); font-size: .65rem; font-weight: 750; white-space: nowrap; }
    .an-sales-period i { color: var(--accent, #2563eb); }
    .an-sales-kpi-grid { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: .6rem; }
    .an-sales-kpi { position: relative; min-width: 0; min-height: 122px; overflow: hidden; padding: .72rem .76rem .66rem; border: 1px solid var(--dsh-border, #d4d7e3); border-left: 3px solid color-mix(in srgb, var(--accent, #2563eb) 56%, var(--dsh-border, #d4d7e3) 44%); border-radius: 10px; background: var(--card, #fff); box-shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 5px 16px rgba(15, 23, 42, .035); }
    .an-sales-kpi::after { position: absolute; right: -1rem; bottom: -1.3rem; width: 4.4rem; height: 4.4rem; border-radius: 50%; background: var(--accent, #2563eb); content: ''; opacity: .045; }
    .an-sales-kpi--primary { border-left-color: var(--accent, #2563eb); }
    .an-sales-kpi--success { border-left-color: var(--success, #16a34a); }
    .an-sales-kpi--warning { border-left-color: var(--danger, #dc2626); }
    .an-sales-kpi-top { display: flex; align-items: flex-start; justify-content: space-between; gap: .45rem; }
    .an-sales-kpi-label { max-width: calc(100% - 2rem); color: var(--dsh-muted, #6b7280); font-size: .62rem; font-weight: 800; line-height: 1.25; }
    .an-sales-kpi-icon { display: inline-flex; flex: 0 0 1.65rem; width: 1.65rem; height: 1.65rem; align-items: center; justify-content: center; border: 1px solid color-mix(in srgb, var(--accent, #2563eb) 14%, var(--dsh-border, #d4d7e3) 86%); border-radius: .5rem; background: var(--accent-soft, #dbeafe); color: var(--accent, #2563eb); font-size: .72rem; }
    .an-sales-kpi--success .an-sales-kpi-icon { background: var(--success-soft, #dcfce7); color: var(--success, #16a34a); }
    .an-sales-kpi--warning .an-sales-kpi-icon { background: var(--danger-soft, #fee2e2); color: var(--danger, #dc2626); }
    .an-sales-kpi-value { position: relative; z-index: 1; overflow: hidden; margin-top: .85rem; color: var(--text, #0f172a); font-size: clamp(.95rem, 1.25vw, 1.18rem); font-weight: 850; letter-spacing: -.025em; line-height: 1.1; text-overflow: ellipsis; white-space: nowrap; }
    .an-sales-kpi-note { position: relative; z-index: 1; overflow: hidden; margin-top: .27rem; color: var(--dsh-muted, #6b7280); font-size: .58rem; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
    .an-sales-trend-section,
    .an-sales-table-section { overflow: hidden; border: 1px solid var(--dsh-border, #d4d7e3); border-radius: 10px; background: var(--card, #fff); box-shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 8px 24px rgba(15, 23, 42, .035); }
    .an-sales-section-head { padding: .82rem .9rem .7rem; border-bottom: 1px solid color-mix(in srgb, var(--dsh-border, #d4d7e3) 76%, transparent); }
    .an-sales-trend-body { padding: .75rem .85rem .85rem; }
    .an-sales-trend-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .55rem; margin-bottom: .75rem; }
    .an-sales-trend-summary-card { display: flex; min-width: 0; align-items: center; gap: .52rem; padding: .58rem .64rem; border: 1px solid color-mix(in srgb, var(--dsh-border, #d4d7e3) 72%, transparent); border-radius: .65rem; background: color-mix(in srgb, var(--accent-soft, #dbeafe) 28%, var(--card, #fff) 72%); }
    .an-sales-trend-summary-icon { display: inline-flex; flex: 0 0 1.75rem; width: 1.75rem; height: 1.75rem; align-items: center; justify-content: center; border-radius: .5rem; background: var(--accent-soft, #dbeafe); color: var(--accent, #2563eb); font-size: .76rem; }
    .an-sales-trend-summary-content { min-width: 0; }
    .an-sales-trend-summary-label { overflow: hidden; color: var(--dsh-muted, #6b7280); font-size: .56rem; font-weight: 800; letter-spacing: .035em; text-overflow: ellipsis; text-transform: uppercase; white-space: nowrap; }
    .an-sales-trend-summary-value { overflow: hidden; margin-top: .08rem; color: var(--text, #0f172a); font-size: .74rem; font-weight: 850; text-overflow: ellipsis; white-space: nowrap; }
    .an-sales-trend-summary-delta { display: inline-flex; margin-top: .07rem; font-size: .56rem; font-weight: 800; }
    .an-sales-trend-summary-delta.good { color: #15803d; }
    .an-sales-trend-summary-delta.bad { color: #b91c1c; }
    .an-sales-trend-summary-delta.neutral { color: var(--dsh-muted, #6b7280); }
    .an-sales-trend-layout { display: grid; grid-template-columns: minmax(0, 1.65fr) minmax(230px, .75fr); gap: .75rem; }
    .an-sales-trend-panel,
    .an-sales-trend-insight { min-width: 0; border-radius: .75rem; background: color-mix(in srgb, var(--bg, #f4f5fb) 42%, var(--card, #fff) 58%); }
    .an-sales-trend-panel { padding: .7rem .75rem .55rem; }
    .an-sales-trend-toolbar { display: flex; align-items: center; justify-content: space-between; gap: .65rem; margin-bottom: .45rem; }
    .an-sales-trend-label { color: var(--dsh-muted, #6b7280); font-size: .59rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
    .an-sales-trend-total { color: var(--text, #0f172a); font-size: .82rem; font-weight: 850; white-space: nowrap; }
    .an-sales-trend-chart { min-height: 215px; border-radius: .65rem; background: linear-gradient(180deg, color-mix(in srgb, var(--card, #fff) 92%, var(--bg, #f4f5fb) 8%), var(--card, #fff)); }
    .an-sales-trend-chart svg { display: block; width: 100%; height: 215px; overflow: visible; }
    .an-sales-trend-chart .sales-grid { stroke: color-mix(in srgb, var(--dsh-border, #d4d7e3) 75%, transparent); stroke-width: 1; vector-effect: non-scaling-stroke; }
    .an-sales-trend-chart .sales-axis-label { fill: var(--dsh-muted, #6b7280); font-size: 10px; font-weight: 700; }
    .an-sales-trend-chart .sales-area { fill: color-mix(in srgb, var(--accent-soft, #dbeafe) 55%, transparent); }
    .an-sales-trend-chart .sales-line { fill: none; stroke: var(--accent, #2563eb); stroke-linecap: round; stroke-linejoin: round; stroke-width: 3; vector-effect: non-scaling-stroke; }
    .an-sales-trend-chart .sales-line.previous { stroke: var(--dsh-muted, #6b7280); stroke-dasharray: 5 5; stroke-width: 1.8; opacity: .72; }
    .an-sales-trend-chart .sales-point { fill: var(--accent, #2563eb); stroke: var(--card, #fff); stroke-width: 2; vector-effect: non-scaling-stroke; }
    .an-sales-trend-chart .sales-point.previous { fill: var(--dsh-muted, #6b7280); }
    .an-sales-trend-legend { display: flex; flex-wrap: wrap; gap: .7rem; margin-top: .35rem; color: var(--dsh-muted, #6b7280); font-size: .59rem; font-weight: 750; }
    .an-sales-trend-legend span { display: inline-flex; align-items: center; gap: .25rem; }
    .an-sales-trend-legend i { width: .45rem; height: .45rem; border-radius: 50%; background: var(--accent, #2563eb); }
    .an-sales-trend-legend i.previous { background: var(--dsh-muted, #6b7280); }
    .an-sales-trend-insights { display: grid; align-content: start; gap: .45rem; }
    .an-sales-trend-insight { padding: .68rem .72rem; }
    .an-sales-trend-insight-label { color: var(--dsh-muted, #6b7280); font-size: .57rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
    .an-sales-trend-insight-value { margin-top: .18rem; color: var(--text, #0f172a); font-size: .79rem; font-weight: 850; }
    .an-sales-trend-insight-meta { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-top: .12rem; color: var(--dsh-muted, #6b7280); font-size: .59rem; font-weight: 700; }
    .an-sales-trend-insight-meta strong { color: var(--text, #0f172a); font-weight: 850; }
    .an-sales-trend-insight--alert { border: 1px solid color-mix(in srgb, var(--danger, #dc2626) 18%, var(--dsh-border, #d4d7e3) 82%); background: color-mix(in srgb, var(--danger-soft, #fee2e2) 30%, var(--card, #fff) 70%); }
    .an-sales-trend-insight--alert .an-sales-trend-insight-label,
    .an-sales-trend-insight--alert .an-sales-trend-insight-value { color: #b91c1c; }
    .an-sales-table-section .an-table-wrap { max-height: none; }
    .an-sales-table { min-width: 1260px; }
    .an-sales-table-note { padding: .58rem .9rem .72rem; color: var(--dsh-muted, #6b7280); font-size: .61rem; font-weight: 700; }
    .an-sales-table th:not(:first-child), .an-sales-table td:not(:first-child) { text-align: right; }
    .an-sales-table td:first-child { font-weight: 800; }
    .an-sales-table th, .an-sales-table td { white-space: nowrap; }
    .an-sales-table-value { display: block; color: var(--text, #0f172a); font-weight: 800; }
    .an-sales-table-delta { display: block; margin-top: .13rem; color: var(--dsh-muted, #6b7280); font-size: .59rem; font-weight: 750; }
    .an-sales-table-delta.good { color: #15803d; }
    .an-sales-table-delta.bad { color: #b91c1c; }
    .an-sales-table-delta.neutral { color: var(--dsh-muted, #6b7280); }
    .an-sales-table .is-negative { color: #b91c1c; }
    .an-sales-table .is-positive { color: #15803d; }

    @media (max-width: 1120px) {
        .an-sales-kpi-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }

    @media (max-width: 900px) {
        .an-hero { flex-direction: column; }
        .an-hero-controls { align-items: stretch; flex-wrap: wrap; }
        .an-hero-controls .an-field:first-child,
        .an-hero-controls .an-field:nth-child(2),
        .an-hero-controls .an-field:nth-child(3) { flex: 1 1 180px; width: auto; }
    }
    @media (max-width: 760px) {
        .an-page { padding-inline: .5rem; }
        .an-hero { margin-bottom: .75rem; }
        .an-summary-top-grid { grid-template-columns: 1fr; }
        .an-hero-controls { align-items: stretch; padding: .75rem; }
        .an-hero-controls .an-field,
        .an-hero-controls .an-field:first-child,
        .an-hero-controls .an-field:nth-child(2),
        .an-hero-controls .an-field:nth-child(3),
        .an-hero-controls .an-field input,
        .an-hero-controls .an-field select,
        .an-hero-controls .an-btn { width: 100%; }
        .an-tabs-wrap { align-items: stretch; flex-direction: column; gap: .1rem; }
        .an-tabs-meta { padding: .35rem .45rem .2rem; }
        .an-tabs { overflow-x: auto; }
        .an-tab { flex: 0 0 auto; }
        .an-sales-head,
        .an-sales-section-head { flex-direction: column; gap: .35rem; }
        .an-sales-period { white-space: normal; }
        .an-sales-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .an-sales-trend-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .an-sales-trend-layout { grid-template-columns: 1fr; }
        .an-sales-trend-toolbar { align-items: flex-start; flex-direction: column; gap: .2rem; }
    }

    body[data-theme="dark"] .an-hero-title { color: #f8fafc; }
    body[data-theme="dark"] .an-hero-controls,
    body[data-theme="dark"] .an-tabs-wrap { background: var(--card, #1e293b); }

    /* Design System header + filter adapter for the Blade analytics page. */
    .an-hero {
        align-items: center;
        min-height: 0;
        margin: 0 0 .8rem;
        padding: 1.25rem 1.4rem;
        border: 1px solid #e0e2fb;
        border-radius: var(--gf-radius-card, 16px);
        background: linear-gradient(120deg, #f4f3ff 0%, var(--gf-color-surface, #fff) 52%, #edfcff 100%);
        box-shadow: var(--gf-shadow-sm, 0 1px 2px rgba(16, 24, 40, .04));
    }
    .an-hero-copy { min-width: 0; }
    .an-hero-heading { display: flex; align-items: flex-start; gap: .72rem; }
    .an-hero-mark { display: grid; flex: 0 0 42px; width: 42px; height: 42px; place-items: center; border-radius: 13px; color: #fff; background: linear-gradient(145deg, var(--gf-color-brand, #5b5ce2), var(--gf-color-accent, #13b8c4)); box-shadow: 0 8px 16px rgba(91, 92, 226, .2); font-size: 1rem; }
    .an-hero-eyebrow { display: inline-flex; align-items: center; gap: .35rem; margin: 0 0 .25rem; color: var(--gf-color-brand-strong, #4142bb); font-size: .62rem; font-weight: 850; letter-spacing: .09em; text-transform: uppercase; }
    .an-hero-eyebrow-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--gf-color-accent, #13b8c4); box-shadow: 0 0 0 4px rgba(19, 184, 196, .12); }
    .an-hero-title { margin: 0; color: var(--gf-color-content, #101828); font-size: clamp(1.35rem, 1.8vw, 1.7rem); font-weight: 820; letter-spacing: -.045em; line-height: 1.08; }
    .an-hero-title i { color: inherit; }
    .an-hero-sub { max-width: 620px; margin: .38rem 0 0; color: var(--gf-color-muted, #667085); font-size: .72rem; line-height: 1.45; }
    .an-hero-meta { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: .35rem; flex: 0 0 auto; }
    .an-hero-chip { display: inline-flex; align-items: center; gap: .32rem; min-height: 30px; padding: .34rem .55rem; border: 1px solid #dfe1ff; border-radius: 999px; color: var(--gf-color-brand-strong, #4142bb); background: rgba(255,255,255,.72); font-size: .6rem; font-weight: 800; white-space: nowrap; }
    .an-hero-chip i { color: var(--gf-color-brand, #5b5ce2); }
    .an-hero-chip-muted { border-color: #bcecef; color: #11737b; background: #effdfd; }
    .an-hero-chip-muted i { color: var(--gf-color-accent, #13b8c4); }
    .an-hero-controls { display: grid; grid-template-columns: minmax(190px, .65fr) minmax(0, 2.35fr); align-items: center; gap: .9rem; width: 100%; margin: 0 0 .2rem; padding: .8rem; border: 1px solid var(--gf-color-line, #e4e7ec); border-top: 3px solid var(--gf-color-brand, #5b5ce2); border-radius: var(--gf-radius-card, 16px); background: var(--gf-color-surface, #fff); box-shadow: var(--gf-shadow-sm, 0 1px 2px rgba(16, 24, 40, .04)); }
    .an-filter-intro { display: flex; align-items: center; gap: .55rem; min-width: 0; padding: .25rem .45rem; }
    .an-filter-icon { display: grid; flex: 0 0 30px; width: 30px; height: 30px; place-items: center; border-radius: 9px; color: var(--gf-color-brand-strong, #4142bb); background: #eeefff; font-size: .78rem; }
    .an-filter-intro strong { display: block; color: var(--gf-color-content, #101828); font-size: .68rem; font-weight: 820; }
    .an-filter-intro small { display: block; margin-top: .15rem; color: var(--gf-color-muted, #667085); font-size: .58rem; line-height: 1.35; }
    .an-filter-fields { display: grid; grid-template-columns: minmax(125px, .9fr) minmax(145px, 1.1fr) minmax(180px, 1.3fr) minmax(150px, 1.1fr) auto; align-items: end; gap: .55rem; min-width: 0; }
    .an-hero-controls .an-field { width: auto; min-width: 0; }
    .an-hero-controls .an-field label { display: flex; align-items: center; gap: .25rem; margin: 0 0 .28rem .1rem; color: var(--gf-color-muted, #667085); font-size: .58rem; font-weight: 820; letter-spacing: .055em; line-height: 1; text-transform: uppercase; }
    .an-hero-controls .an-field label i { color: var(--gf-color-brand, #5b5ce2); font-size: .68rem; }
    .an-hero-controls .an-field input, .an-hero-controls .an-field select { width: 100%; min-width: 0; min-height: var(--gf-touch-target, 44px); padding: .45rem .65rem; border: 1px solid #d0d5dd; border-radius: var(--gf-radius-control, 10px); color: var(--gf-color-content, #101828); background: var(--gf-color-surface, #fff); font-size: .7rem; font-weight: 700; outline: none; transition: border-color .18s ease, box-shadow .18s ease, background .18s ease; }
    .an-hero-controls .an-field input:hover, .an-hero-controls .an-field select:hover { border-color: #b9baf4; }
    .an-hero-controls .an-field input:focus, .an-hero-controls .an-field select:focus { border-color: var(--gf-color-brand, #5b5ce2); box-shadow: 0 0 0 3px rgba(91, 92, 226, .12); }
    .an-period-field input { color: var(--gf-color-brand-strong, #4142bb) !important; background: #f8f7ff !important; font-weight: 800 !important; cursor: pointer; }
    .an-filter-fields .an-btn { width: auto; min-height: var(--gf-touch-target, 44px); border-radius: var(--gf-radius-control, 10px); padding-inline: .78rem; white-space: nowrap; }
    .an-hero-controls .an-btn-dark { border-color: var(--gf-color-brand, #5b5ce2); color: #fff; background: var(--gf-color-brand, #5b5ce2); box-shadow: 0 6px 12px rgba(91, 92, 226, .16); }
    .an-hero-controls .an-btn-dark:hover { border-color: var(--gf-color-brand-strong, #4142bb); color: #fff; background: var(--gf-color-brand-strong, #4142bb); }
    @media (max-width: 1120px) { .an-hero-controls { grid-template-columns: 1fr; gap: .55rem; } .an-filter-intro { padding-inline: .25rem; } }
    @media (max-width: 900px) { .an-filter-fields { grid-template-columns: repeat(2, minmax(0, 1fr)); } .an-filter-fields .an-btn { width: 100%; } }
    @media (max-width: 760px) { .an-hero { align-items: flex-start; flex-direction: column; gap: .85rem; padding: 1rem; } .an-hero-meta { justify-content: flex-start; } .an-hero-controls { padding: .7rem; } .an-filter-fields { grid-template-columns: 1fr; } .an-filter-fields .an-btn { width: 100%; } }
    @media (prefers-reduced-motion: reduce) { .an-hero-controls .an-field input, .an-hero-controls .an-field select { transition: none; } }
    body[data-theme="dark"] .an-hero { border-color: rgba(129, 140, 248, .4); background: linear-gradient(120deg, rgba(49, 46, 129, .38) 0%, #1e293b 56%, rgba(6, 78, 59, .28) 100%); }
    body[data-theme="dark"] .an-hero-title, body[data-theme="dark"] .an-filter-intro strong { color: #f8fafc; }
    body[data-theme="dark"] .an-hero-eyebrow { color: #c7c8f5; }
    body[data-theme="dark"] .an-hero-sub, body[data-theme="dark"] .an-filter-intro small { color: #cbd5e1; }
    body[data-theme="dark"] .an-hero-chip { border-color: rgba(129, 140, 248, .45); color: #dfe1ff; background: rgba(15, 23, 42, .42); }
    body[data-theme="dark"] .an-hero-chip-muted { border-color: rgba(45, 212, 191, .35); color: #99f6e4; background: rgba(6, 78, 59, .25); }
    body[data-theme="dark"] .an-hero-controls { border-color: #334155; background: #1e293b; }
    body[data-theme="dark"] .an-hero-controls .an-field input, body[data-theme="dark"] .an-hero-controls .an-field select { border-color: #475569; color: #f8fafc; background: #0f172a; }
    body[data-theme="dark"] .an-period-field input { color: #dfe1ff !important; background: rgba(49, 46, 129, .28) !important; }

    /* ComparisonKpi adapter: keep the Blade analytics page on the same KPI contract as /design-system. */
    .an-kpis { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .65rem; align-items: stretch; }
    .an-kpis .ds-comparison-kpi.an-kpi { min-height: 0; overflow: hidden; padding: 0; border: 1px solid #e0e2fb; border-radius: 14px; background: linear-gradient(145deg, #fafaff 0%, #fff 58%, #f3feff 100%); box-shadow: var(--gf-shadow-sm, 0 1px 2px rgba(16, 24, 40, .04)); }
    .an-kpis .ds-comparison-kpi.an-kpi::before { display: none; }
    .an-kpis .ds-comparison-kpi-main { min-height: 0; padding: .9rem .95rem .8rem; }
    .an-kpis .ds-comparison-kpi-label { display: block; color: var(--gf-color-muted, #667085); font-size: .68rem; font-weight: 800; line-height: 1.3; }
    .an-kpis .ds-comparison-kpi-main strong.an-kpi-value { display: block; margin: .35rem 0 .18rem; color: var(--gf-color-content, #101828); font-size: clamp(1rem, 1.55vw, 1.75rem); font-weight: 850; letter-spacing: -.06em; line-height: 1; }
    .an-kpis .ds-comparison-kpi-description.an-kpi-note { display: block; min-height: 2.2em; margin: 0; padding: 0; border: 0; color: var(--gf-color-muted, #667085); font-size: .63rem; font-weight: 650; line-height: 1.35; white-space: normal; overflow: visible; }
    .an-kpis .ds-comparison-kpi-icon { width: 33px; height: 33px; flex: 0 0 auto; border-radius: 11px; color: var(--gf-color-brand-strong, #4142bb); background: #e9e9ff; font-size: .85rem; }
    .an-kpis .ds-comparison-kpi-comparisons { padding: .2rem .95rem .55rem; }
    .an-kpis .ds-comparison-kpi-row { min-height: 49px; padding: .62rem 0; border-top: 1px solid rgba(228, 231, 236, .84); }
    .an-kpis .ds-comparison-kpi-row span { color: #475467; font-size: .66rem; font-weight: 750; }
    .an-kpis .ds-comparison-kpi-row small { margin-top: .12rem; color: #98a2b3; font-size: .59rem; }
    .an-kpis .ds-comparison-kpi-result strong { color: var(--gf-color-content, #101828); font-size: .72rem; font-weight: 800; font-variant-numeric: tabular-nums; }
    .an-kpis .ds-comparison-kpi-change { display: inline-flex; align-items: center; gap: .1rem; font-size: .62rem; font-weight: 800; white-space: nowrap; }
    .an-kpis .ds-comparison-kpi-change.is-up { color: var(--gf-color-success, #14945a) !important; }
    .an-kpis .ds-comparison-kpi-change.is-down { color: var(--gf-color-danger, #d6455d) !important; }
    .an-kpis .ds-comparison-kpi-change.is-neutral { color: var(--gf-color-muted, #667085) !important; }
    .an-kpis .ds-comparison-kpi.an-kpi.kpi-cancelled,
    .an-kpis .ds-comparison-kpi.an-kpi.kpi-refund { border-color: #f1cbd3; }
    .an-kpis .ds-comparison-kpi.kpi-cancelled .ds-comparison-kpi-icon,
    .an-kpis .ds-comparison-kpi.kpi-refund .ds-comparison-kpi-icon { color: var(--gf-color-danger, #d6455d); background: #fff0f2; }
    @media (max-width: 1120px) { .an-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (max-width: 760px) { .an-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem; } .an-kpis .ds-comparison-kpi-main { min-height: 116px; padding: .72rem .75rem .65rem; } .an-kpis .ds-comparison-kpi-main strong.an-kpi-value { font-size: 1.08rem; } .an-kpis .ds-comparison-kpi-comparisons { padding-inline: .75rem; } .an-kpis .ds-comparison-kpi-row { align-items: flex-start; flex-direction: column; gap: .22rem; } .an-kpis .ds-comparison-kpi-result { justify-content: space-between; width: 100%; } }
    @media (max-width: 420px) { .an-kpis { grid-template-columns: 1fr; } }
    @media (prefers-reduced-motion: reduce) { .an-kpis .ds-comparison-kpi-change { transition: none; } }
    body[data-theme="dark"] .an-kpis .ds-comparison-kpi.an-kpi { border-color: #334155; background: linear-gradient(145deg, #17203a 0%, #111827 58%, #0e2b35 100%); }
    body[data-theme="dark"] .an-kpis .ds-comparison-kpi-main strong.an-kpi-value,
    body[data-theme="dark"] .an-kpis .ds-comparison-kpi-result strong { color: #f8fafc; }
    body[data-theme="dark"] .an-kpis .ds-comparison-kpi-label,
    body[data-theme="dark"] .an-kpis .ds-comparison-kpi-description.an-kpi-note,
    body[data-theme="dark"] .an-kpis .ds-comparison-kpi-row small { color: #aab4c5; }
    body[data-theme="dark"] .an-kpis .ds-comparison-kpi-row { border-color: #263149; }
    body[data-theme="dark"] .an-kpis .ds-comparison-kpi-row span { color: #d1d5db; }

    /* SelectField adapter: Blade filters follow the /design-system contract. */
    .gf-select-field { display: grid; align-content: start; gap: .32rem; min-width: 0; }
    .gf-select-field > label { color: var(--gf-color-muted, #667085); font-size: .68rem; font-weight: 750; }
    .gf-select-field > label span { color: var(--gf-color-danger, #d6455d); }
    .gf-select-field .ds-select-control { position: relative; display: flex; align-items: center; min-width: 0; min-height: var(--gf-touch-target, 44px); border: 1px solid var(--gf-color-line, #d0d5dd); border-radius: var(--gf-radius-control, 10px); background: var(--gf-color-surface, #fff); transition: border-color .18s ease, box-shadow .18s ease, background .18s ease; }
    .gf-select-field .ds-select-control:focus-within { border-color: var(--gf-color-brand, #5b5ce2); box-shadow: 0 0 0 3px rgba(91, 92, 226, .12); }
    .gf-select-field .ds-select-leading-icon { flex: 0 0 auto; margin-left: .7rem; color: var(--gf-color-muted, #98a2b3); font-size: .75rem; pointer-events: none; }
    .gf-select-field .ds-select-control select { flex: 1; min-width: 0; min-height: 42px; padding: .55rem 2rem .55rem .48rem; border: 0; border-radius: 9px; color: var(--gf-color-content, #344054); outline: 0; background: transparent; font: inherit; font-size: .7rem; font-weight: 700; appearance: none; cursor: pointer; }
    .gf-select-field .ds-select-control select:disabled { color: var(--gf-color-muted, #98a2b3); cursor: not-allowed; }
    .gf-select-field .ds-select-chevron { position: absolute; right: .7rem; color: var(--gf-color-muted, #98a2b3); font-size: .65rem; pointer-events: none; }
    .gf-select-field.is-disabled .ds-select-control { border-color: var(--gf-color-line, #e4e7ec); background: var(--gf-color-surface-muted, #f8fafc); }
    .gf-select-field.is-error .ds-select-control { border-color: var(--gf-color-danger, #d6455d); }
    .gf-select-field.is-error .ds-select-control:focus-within { box-shadow: 0 0 0 3px rgba(214, 69, 93, .12); }
    .gf-select-field .ds-select-hint, .gf-select-field .ds-select-error { display: block; font-size: .6rem; line-height: 1.35; }
    .gf-select-field .ds-select-hint { color: var(--gf-color-muted, #98a2b3); }
    .gf-select-field .ds-select-hint i { margin-right: .2rem; color: var(--gf-color-brand, #5b5ce2); }
    .gf-select-field .ds-select-error { color: var(--gf-color-danger, #d6455d); font-weight: 650; }
    .gf-select-field .ds-select-error i { margin-right: .2rem; }
    .an-hero-controls .gf-select-field .ds-select-control select { min-height: 42px; padding: .55rem 2rem .55rem .48rem; border: 0; border-radius: 9px; color: var(--gf-color-content, #101828); background: transparent; box-shadow: none; }
    .an-cohort-field.gf-select-field { display: grid; gap: .22rem; }
    .an-cohort-field.gf-select-field > label { color: var(--dsh-muted, #64748b); font-size: .59rem; font-weight: 850; }
    .an-cohort-field.gf-select-field .ds-select-control { min-height: 38px; border-radius: 9px; }
    .an-cohort-field.gf-select-field .ds-select-control select { min-height: 36px; font-size: .67rem; }
    .an-product-select-control { position: relative; display: flex; align-items: center; min-width: 0; min-height: 32px; border: 1px solid var(--gf-color-line, #d0d5dd); border-radius: 9px; background: var(--gf-color-surface, #fff); transition: border-color .18s ease, box-shadow .18s ease; }
    .an-product-select-control:focus-within { border-color: var(--gf-color-brand, #5b5ce2); box-shadow: 0 0 0 3px rgba(91, 92, 226, .12); }
    .an-product-select-control select { flex: 1; min-width: 0; min-height: 30px; padding: .25rem 1.65rem .25rem .5rem; border: 0; border-radius: 8px; color: var(--gf-color-content, #344054); background: transparent; font: inherit; font-size: .66rem; font-weight: 700; outline: 0; appearance: none; cursor: pointer; }
    .an-product-select-control .ds-select-chevron { position: absolute; right: .5rem; color: var(--gf-color-muted, #98a2b3); font-size: .6rem; pointer-events: none; }
    .an-product-select-control select:disabled { color: var(--gf-color-muted, #98a2b3); cursor: not-allowed; }
    body[data-theme="dark"] .gf-select-field .ds-select-control, body[data-theme="dark"] .an-product-select-control { border-color: #475569; background: #0f172a; }
    body[data-theme="dark"] .gf-select-field .ds-select-control select, body[data-theme="dark"] .an-product-select-control select { color: #f8fafc; background: transparent; }
    body[data-theme="dark"] .gf-select-field > label, body[data-theme="dark"] .an-cohort-field.gf-select-field > label { color: #aab4c5; }
    body[data-theme="dark"] .gf-select-field.is-disabled .ds-select-control { background: #111827; }
    @media (max-width: 900px) { .an-product-select-control { flex: 1 1 140px; } }
    @media (max-width: 760px) { .an-product-select-control { width: 100%; } }
    @media (prefers-reduced-motion: reduce) { .gf-select-field .ds-select-control, .an-product-select-control { transition: none; } }

    /* Filter controls: keep native behavior while matching FlatpickrField, SearchInput, and Button tokens. */
    .an-period-field.ds-flatpickr-field { display: grid; align-content: start; gap: .32rem; min-width: 0; }
    .an-period-field.ds-flatpickr-field > label { display: block; margin: 0 0 .28rem .1rem; color: var(--gf-color-muted, #667085); font-size: .58rem; font-weight: 820; letter-spacing: .055em; line-height: 1; text-transform: uppercase; }
    .an-period-field.ds-flatpickr-field .ds-flatpickr-control { position: relative; display: flex; align-items: center; min-width: 0; }
    .an-period-field.ds-flatpickr-field .ds-flatpickr-control input { width: 100%; min-width: 0; min-height: var(--gf-touch-target, 44px); padding: 0 2.15rem 0 2.05rem; border: 1px solid var(--gf-color-line, #d0d5dd); border-radius: var(--gf-radius-control, 10px); color: var(--gf-color-content, #101828) !important; background: var(--gf-color-surface, #fff) !important; font: inherit; font-size: .7rem; font-weight: 700; outline: none; cursor: pointer; transition: border-color .18s ease, box-shadow .18s ease; }
    .an-period-field.ds-flatpickr-field .ds-flatpickr-control input:focus { border-color: var(--gf-color-brand, #5b5ce2); box-shadow: 0 0 0 3px rgba(91, 92, 226, .12); }
    .an-period-field.ds-flatpickr-field .ds-flatpickr-icon { position: absolute; left: .7rem; z-index: 1; color: var(--gf-color-brand, #5b5ce2); font-size: .76rem; pointer-events: none; }
    .an-product-search-control { position: relative; display: flex; align-items: center; min-width: 0; }
    .an-product-search-control .ds-search-leading-icon { position: absolute; left: .72rem; z-index: 1; color: var(--gf-color-muted, #98a2b3); font-size: .75rem; pointer-events: none; }
    .an-product-search-control input { width: 100%; min-height: 32px; padding: 0 .6rem 0 2rem; border: 1px solid var(--gf-color-line, #d0d5dd); border-radius: 9px; color: var(--gf-color-content, #101828); background: var(--gf-color-surface, #fff); font: inherit; font-size: .66rem; font-weight: 700; outline: none; transition: border-color .18s ease, box-shadow .18s ease; }
    .an-product-search-control input::placeholder { color: var(--gf-color-muted, #98a2b3); }
    .an-product-search-control input:focus { border-color: var(--gf-color-brand, #5b5ce2); box-shadow: 0 0 0 3px rgba(91, 92, 226, .12); }
    .an-cohort-reset.ds-btn { display: inline-flex; align-items: center; justify-content: center; gap: .25rem; min-height: 30px; padding: 0 .62rem; border: 1px solid var(--gf-color-line, #d0d5dd); border-radius: 8px; color: #475467; background: var(--gf-color-surface, #fff); font: inherit; font-size: .68rem; font-weight: 750; cursor: pointer; }
    .an-cohort-reset.ds-btn:hover { border-color: #b9baf4; color: var(--gf-color-brand-strong, #4142bb); background: #f5f5ff; }
    .an-cohort-reset.ds-btn:focus-visible, .an-cohort-apply.ds-btn:focus-visible, #anRefresh.ds-btn:focus-visible { outline: 3px solid rgba(91, 92, 226, .28); outline-offset: 2px; }
    .an-cohort-apply.ds-btn, #anRefresh.ds-btn { display: inline-flex; align-items: center; justify-content: center; gap: .35rem; font: inherit; font-weight: 750; cursor: pointer; }
    .an-cohort-apply.ds-btn { min-height: var(--gf-touch-target, 44px); border: 1px solid var(--gf-color-brand, #5b5ce2); border-radius: var(--gf-radius-control, 10px); color: #fff; background: var(--gf-color-brand, #5b5ce2); box-shadow: 0 6px 12px rgba(91, 92, 226, .16); }
    .an-cohort-apply.ds-btn:hover, #anRefresh.ds-btn:hover { border-color: var(--gf-color-brand-strong, #4142bb); color: #fff; background: var(--gf-color-brand-strong, #4142bb); }
    #anRefresh.ds-btn:disabled, .an-cohort-apply.ds-btn:disabled { cursor: wait; opacity: .58; transform: none; }
    body[data-theme="dark"] .an-period-field.ds-flatpickr-field .ds-flatpickr-control input,
    body[data-theme="dark"] .an-product-search-control input { border-color: #475569; color: #f8fafc !important; background: #0f172a !important; }
    body[data-theme="dark"] .an-cohort-reset.ds-btn { border-color: #475569; color: #d1d5db; background: #0f172a; }
    body[data-theme="dark"] .an-cohort-reset.ds-btn:hover { color: #e0e7ff; background: #1e1b4b; }
    @media (max-width: 760px) { .an-product-search-control { width: 100%; } .an-period-field.ds-flatpickr-field .ds-flatpickr-control input { min-height: var(--gf-touch-target, 44px); } }
    @media (prefers-reduced-motion: reduce) { .an-period-field.ds-flatpickr-field .ds-flatpickr-control input, .an-product-search-control input { transition: none; } }

    /* Typography and sizing contract: display 40/850, body 14/400, caption 11/650. */
    .an-page { --an-type-display: 2.5rem; --an-type-section: 1.05rem; --an-type-body: .875rem; --an-type-caption: .6875rem; --an-radius-card: var(--gf-radius-card, 16px); --an-radius-control: var(--gf-radius-control, 10px); }
    .an-hero-title { font-size: var(--an-type-display); font-weight: 850; letter-spacing: -.06em; line-height: 1.04; }
    .an-hero-sub { font-size: var(--an-type-body); font-weight: 400; line-height: 1.6; }
    .an-hero-eyebrow, .an-hero-chip, .an-tabs-meta, .an-tab { font-size: var(--an-type-caption); font-weight: 650; }
    .an-hero-chip { letter-spacing: .01em; }
    .an-filter-intro strong { font-size: var(--an-type-body); font-weight: 400; }
    .an-filter-intro small, .an-hero-controls .an-field label, .an-revenue-section-kicker, .an-revenue-section-hint { font-size: var(--an-type-caption); font-weight: 650; }
    .an-hero-controls .an-field input, .an-hero-controls .an-field select { font-size: var(--an-type-body); font-weight: 400; }
    .an-period-field input { font-weight: 650 !important; }
    .an-revenue-status, .an-revenue-period { font-size: var(--an-type-caption); }
    .an-revenue-tabs .an-modal-tab { min-height: var(--gf-touch-target, 44px); font-size: var(--an-type-caption); font-weight: 650; }
    .an-revenue-tabs .an-modal-tab small { font-size: .58rem; font-weight: 850; }
    .an-revenue-section-head strong { font-size: var(--an-type-section); font-weight: 800; }
    .an-revenue-head .an-enterprise-title { font-size: var(--an-type-section); font-weight: 800; }
    .an-revenue-head .an-enterprise-sub { font-size: var(--an-type-caption); line-height: 1.4; }
    .an-revenue-summary .an-modal-stat-label, .an-revenue-summary .an-modal-stat-note { font-size: var(--an-type-caption); }
    .an-revenue-summary .an-modal-stat-value { font-size: var(--an-type-body); }
    .an-modal-fee-breakdown div, .an-modal-fee-breakdown small, .an-cash-meta, .an-cash-detail, .an-cash-group-head span { font-size: var(--an-type-caption); }
    @media (max-width: 760px) { .an-hero-title { font-size: clamp(1.75rem, 8vw, 2.25rem); } .an-hero-sub { font-size: var(--an-type-body); } }
    /* Final filter cascade: keep legacy analytics selectors from overriding Design System controls. */
    .an-hero-controls .gf-select-field .ds-select-control select { font-size: .7rem; font-weight: 700; }
    .an-period-field.ds-flatpickr-field .ds-flatpickr-control input { font-size: .7rem; font-weight: 700 !important; }
    .an-product-search-control { flex: 1 1 170px; width: 170px; }
    .an-product-select-control { flex: 0 1 135px; width: 135px; }
    .an-product-search-control input { font-size: .66rem; font-weight: 700; }
    @media (max-width: 760px) { .an-product-search-control, .an-product-select-control { flex: 1 1 100%; width: 100%; } }

    /* Filter canvas: give desktop layouts breathing room while preserving a fluid mobile stack. */
    .an-hero-controls { width: 100%; margin: 0 0 1rem; padding: 1rem 1.1rem; }
    .an-filter-fields { grid-template-columns: repeat(4, minmax(0, 1fr)) auto; gap: .7rem; }
    .an-filter-intro { padding-inline: .2rem; }
    @media (max-width: 1240px) {
        .an-hero-controls { width: 100%; }
    }
    @media (max-width: 1120px) {
        .an-hero-controls { width: 100%; padding: .9rem 1rem; }
        .an-filter-fields { grid-template-columns: repeat(4, minmax(0, 1fr)) auto; }
    }
    @media (max-width: 900px) {
        .an-filter-fields { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .65rem; }
        .an-filter-fields .an-btn { width: 100%; }
    }
    @media (max-width: 760px) {
        .an-hero-controls { width: 100%; margin-bottom: .75rem; padding: .75rem; }
        .an-filter-fields { grid-template-columns: 1fr; gap: .6rem; }
        .an-filter-intro { padding-inline: .1rem; }
    }

    /* Keep each desktop control visually separated; collapse only when the canvas can no longer keep readable widths. */
    @media (min-width: 1121px) {
        .an-hero-controls { grid-template-columns: 1fr; gap: .8rem; }
        .an-filter-intro { padding: .2rem .2rem .75rem; border-bottom: 1px solid var(--gf-color-line, #e4e7ec); }
        .an-filter-fields { grid-template-columns: minmax(140px, .9fr) minmax(140px, .9fr) minmax(230px, 1.45fr) minmax(210px, 1.3fr) minmax(96px, auto); column-gap: .8rem; row-gap: .75rem; }
        .an-filter-fields > * { min-width: 0; }
        .an-filter-fields .an-btn { min-width: 96px; }
    }
    .an-hero-controls .an-filter-fields > .an-field:first-child,
    .an-hero-controls .an-filter-fields > .an-field:nth-child(2),
    .an-hero-controls .an-filter-fields > .an-field:nth-child(3),
    .an-hero-controls .an-filter-fields > .an-field:nth-child(4) { width: 100%; min-width: 0; }
    @media (min-width: 901px) and (max-width: 1120px) {
        .an-filter-fields { column-gap: .9rem; row-gap: .7rem; }
        .an-filter-fields > * { min-width: 0; }
    }
    @media (max-width: 900px) {
        .an-filter-fields { column-gap: .8rem; row-gap: .75rem; }
        .an-filter-fields > * { min-width: 0; }
    }
    @media (max-width: 760px) {
        .an-filter-fields { gap: .7rem; }
    }

    /* Data table adapter: align revenue and exception tables with the Design System surfaces. */
    .an-revenue-body .an-table-wrap,
    #returnOrdersBody .an-table-wrap { overflow: auto; border: 1px solid var(--gf-color-line, #e4e7ec); border-radius: var(--gf-radius-card, 14px); background: var(--gf-color-surface, #fff); box-shadow: var(--gf-shadow-sm, 0 1px 2px rgba(16, 24, 40, .04)); }
    .an-revenue-body .an-cash-table,
    #returnOrdersBody .an-cash-table { min-width: 1040px; border-collapse: separate; border-spacing: 0; }
    #returnOrdersBody .an-exception-table { min-width: 700px; }
    .an-revenue-body .an-cash-table thead th,
    #returnOrdersBody .an-cash-table thead th { position: sticky; top: 0; z-index: 1; padding: .64rem .55rem; color: var(--gf-color-muted, #667085); background: var(--gf-color-surface-muted, #f8f9fc); border-bottom: 1px solid var(--gf-color-line, #e4e7ec); font-size: .6rem; font-weight: 800; letter-spacing: .04em; line-height: 1.2; text-transform: uppercase; white-space: nowrap; }
    .an-revenue-body .an-cash-table tbody td,
    #returnOrdersBody .an-cash-table tbody td { padding: .64rem .55rem; color: var(--gf-color-content, #101828); background: var(--gf-color-surface, #fff); border-bottom: 1px solid var(--gf-color-line, #f0f1f3); font-size: var(--an-type-caption); font-weight: 400; vertical-align: top; }
    .an-revenue-body .an-cash-table tbody tr:last-child td,
    #returnOrdersBody .an-cash-table tbody tr:last-child td { border-bottom: 0; }
    .an-revenue-body .an-cash-table tbody tr:hover td,
    #returnOrdersBody .an-cash-table tbody tr:hover td { background: #faf9ff; }
    .an-revenue-body .an-cash-table tbody tr:focus-within td,
    #returnOrdersBody .an-cash-table tbody tr:focus-within td { background: #f8f7ff; }
    .an-cash-order { color: var(--gf-color-content, #101828); font-size: var(--an-type-caption); font-weight: 750; letter-spacing: -.01em; }
    .an-cash-payment { max-width: 150px; color: var(--gf-color-content, #101828); font-size: var(--an-type-caption); font-weight: 400; line-height: 1.35; }
    .an-cash-meta { color: var(--gf-color-muted, #667085); font-weight: 650; line-height: 1.35; }
    .an-cash-money { font-size: var(--an-type-caption); font-weight: 750; font-variant-numeric: tabular-nums; }
    .an-cash-status { padding: .2rem .45rem; border-color: #b8e5ca; border-radius: 999px; background: #effaf3; color: #117346; font-size: var(--an-type-caption); font-weight: 650; line-height: 1.2; }
    .an-cash-status.pending { border-color: #f2d39a; background: #fff8e8; color: #9a6700; }
    .an-cash-status.cancelled { border-color: #f2b8b5; background: #fff2f1; color: #b42318; }
    .an-cash-status.return { border-color: #f2d39a; background: #fff8e8; color: #9a6700; }
    .an-cash-status.failed { border-color: #f2b8b5; background: #fff2f1; color: #b42318; }
    .an-cash-status.settled { border-color: #b8e5ca; background: #effaf3; color: #117346; }
    .an-cash-detail summary { display: inline-flex; min-height: 32px; align-items: center; padding: .25rem .45rem; border-radius: var(--an-radius-control); color: var(--gf-color-brand-strong, #4142bb); font-size: var(--an-type-caption); font-weight: 650; line-height: 1.2; transition: background-color .18s ease, color .18s ease; }
    .an-cash-detail summary:hover { background: #f1f1ff; color: var(--gf-color-brand-strong, #4142bb); }
    .an-cash-detail summary:focus-visible { outline: 3px solid rgba(91, 92, 226, .2); outline-offset: 2px; }
    .an-cash-detail-grid { border-color: var(--gf-color-line, #e4e7ec); border-radius: var(--gf-radius-control, 10px); background: var(--gf-color-surface-muted, #f8f9fc); }
    .an-cash-detail-grid strong { color: var(--gf-color-content, #101828); }
    .an-cash-group-head { margin-bottom: .5rem; padding: .65rem .8rem; border-color: var(--gf-color-line, #e4e7ec); border-radius: var(--an-radius-control); background: var(--gf-color-surface-muted, #f8f9fc); color: var(--gf-color-content, #101828); font-size: var(--an-type-body); }
    .an-cash-group-head span { color: var(--gf-color-muted, #667085); font-weight: 650; }
    body[data-theme="dark"] .an-revenue-body .an-table-wrap,
    body[data-theme="dark"] #returnOrdersBody .an-table-wrap { border-color: #334155; background: #1e293b; }
    body[data-theme="dark"] .an-revenue-body .an-cash-table thead th,
    body[data-theme="dark"] #returnOrdersBody .an-cash-table thead th { background: #0f172a; border-color: #334155; }
    body[data-theme="dark"] .an-revenue-body .an-cash-table tbody td,
    body[data-theme="dark"] #returnOrdersBody .an-cash-table tbody td { background: #1e293b; border-color: #334155; }
    body[data-theme="dark"] .an-revenue-body .an-cash-table tbody tr:hover td,
    body[data-theme="dark"] #returnOrdersBody .an-cash-table tbody tr:hover td { background: #263449; }
    body[data-theme="dark"] .an-cash-detail-grid,
    body[data-theme="dark"] .an-cash-group-head { background: #0f172a; border-color: #334155; }
    @media (max-width: 760px) {
        .an-revenue-body .an-table-wrap,
        #returnOrdersBody .an-table-wrap { overflow: visible; border: 0; background: transparent; box-shadow: none; }
        .an-revenue-body .an-cash-table,
        #returnOrdersBody .an-cash-table { display: block; width: 100%; min-width: 0; border-spacing: 0; }
        .an-revenue-body .an-cash-table thead,
        #returnOrdersBody .an-cash-table thead { display: none; }
        .an-revenue-body .an-cash-table tbody,
        #returnOrdersBody .an-cash-table tbody { display: grid; gap: .7rem; }
        .an-revenue-body .an-cash-table tr,
        #returnOrdersBody .an-cash-table tr { display: grid; gap: .35rem; padding: .8rem; border: 1px solid var(--gf-color-line, #e4e7ec); border-radius: var(--an-radius-card); background: var(--gf-color-surface, #fff); box-shadow: var(--gf-shadow-sm, 0 1px 2px rgba(16, 24, 40, .04)); }
        .an-revenue-body .an-cash-table td,
        #returnOrdersBody .an-cash-table td { display: grid; grid-template-columns: minmax(96px, .58fr) minmax(0, 1fr); align-items: start; gap: .7rem; padding: .35rem 0; border-bottom: 1px solid var(--gf-color-line, #f0f1f3); text-align: right !important; white-space: normal; }
        .an-revenue-body .an-cash-table td::before,
        #returnOrdersBody .an-cash-table td::before { content: attr(data-label); color: var(--gf-color-muted, #667085); font-size: .6875rem; font-weight: 650; letter-spacing: .03em; line-height: 1.25; text-align: left; text-transform: uppercase; }
        .an-revenue-body .an-cash-table td:first-child,
        #returnOrdersBody .an-cash-table td:first-child { display: block; padding-top: 0; text-align: left !important; }
        .an-revenue-body .an-cash-table td:first-child::before,
        #returnOrdersBody .an-cash-table td:first-child::before { display: none; }
        .an-revenue-body .an-cash-table td:last-child,
        #returnOrdersBody .an-cash-table td:last-child { padding-bottom: 0; border-bottom: 0; }
        .an-revenue-body .an-cash-table td:nth-child(3),
        .an-revenue-body .an-cash-table td:nth-child(8) { display: none; }
        .an-revenue-body .an-cash-table .an-cash-payment { max-width: none; text-align: right; }
        .an-revenue-body .an-cash-table .an-cash-detail-grid { grid-template-columns: 1fr; }
        body[data-theme="dark"] .an-revenue-body .an-cash-table tr,
        body[data-theme="dark"] #returnOrdersBody .an-cash-table tr { background: #1e293b; }
    }

    /* Overview KPI adapter: dynamic pulse cards use the same ComparisonKpi contract as /design-system. */
    .an-pulse-grid .ds-comparison-kpi.an-kpi {
        display: block;
        min-height: 0;
        overflow: hidden;
        padding: 0;
        border: 1px solid #e0e2fb;
        border-radius: 14px;
        background: linear-gradient(145deg, #fafaff 0%, #fff 58%, #f3feff 100%);
        box-shadow: var(--gf-shadow-sm, 0 1px 2px rgba(16, 24, 40, .04));
    }
    .an-pulse-grid .ds-comparison-kpi.an-kpi::before { display: none; }
    .an-pulse-grid .ds-comparison-kpi-main { min-height: 0; padding: .9rem .95rem .8rem; }
    .an-pulse-grid .ds-comparison-kpi-label { color: var(--gf-color-muted, #667085); font-size: .68rem; font-weight: 800; line-height: 1.3; }
    .an-pulse-grid .ds-comparison-kpi-main strong { display: block; margin: .35rem 0 .18rem; color: var(--gf-color-content, #101828); font-size: clamp(1rem, 1.55vw, 1.75rem); font-weight: 850; letter-spacing: -.06em; line-height: 1; font-variant-numeric: tabular-nums; }
    .an-decision-pulse-grid .ds-comparison-kpi-main strong { font-size: clamp(.95rem, 1.1vw, 1.55rem); letter-spacing: -.05em; }
    .an-pulse-grid .ds-comparison-kpi-description { min-height: 2.2em; color: #98a2b3; font-size: .63rem; line-height: 1.35; }
    .an-pulse-grid .ds-comparison-kpi-icon { width: 33px; height: 33px; flex: 0 0 auto; border-radius: 11px; color: var(--gf-color-brand-strong, #4142bb); background: #e9e9ff; font-size: .85rem; }
    .an-pulse-grid .ds-comparison-kpi-comparisons { padding: .2rem .95rem .65rem; }
    .an-pulse-grid .ds-comparison-kpi-row { min-height: 49px; padding: .62rem 0; border-top: 1px solid rgba(228, 231, 236, .84); }
    .an-pulse-grid .ds-comparison-kpi-row span { color: #475467; font-size: .66rem; font-weight: 750; }
    .an-pulse-grid .ds-comparison-kpi-row small { margin-top: .12rem; color: #98a2b3; font-size: .59rem; }
    .an-pulse-grid .ds-comparison-kpi-result strong { color: var(--gf-color-content, #101828); font-size: .72rem; font-weight: 800; font-variant-numeric: tabular-nums; }
    .an-pulse-grid .ds-comparison-kpi-change { display: inline-flex !important; align-items: center; gap: .1rem; font-size: .62rem !important; font-weight: 800 !important; white-space: nowrap; }
    .an-pulse-grid .ds-comparison-kpi-change.is-up { color: var(--gf-color-success, #14945a) !important; }
    .an-pulse-grid .ds-comparison-kpi-change.is-down { color: var(--gf-color-danger, #d6455d) !important; }
    .an-pulse-grid .ds-comparison-kpi-change.is-neutral { color: var(--gf-color-muted, #667085) !important; }
    .an-pulse-grid .ds-comparison-kpi.an-pulse-action { cursor: pointer; transition: border-color .16s ease, box-shadow .16s ease, transform .16s ease; }
    .an-pulse-grid .ds-comparison-kpi.an-pulse-action:hover { border-color: #93c5fd; box-shadow: 0 6px 14px rgba(37, 99, 235, .1); transform: translateY(-1px); }
    .an-pulse-grid .ds-comparison-kpi.an-pulse-action.is-active { border-color: #2563eb; box-shadow: 0 0 0 2px rgba(37, 99, 235, .12); }
    .an-pulse-grid .ds-comparison-kpi:focus-visible { outline: 3px solid rgba(37, 99, 235, .28); outline-offset: 2px; }
    body[data-theme="dark"] .an-pulse-grid .ds-comparison-kpi.an-kpi { border-color: #334155; background: linear-gradient(145deg, #17203a 0%, #111827 58%, #0e2b35 100%); }
    body[data-theme="dark"] .an-pulse-grid .ds-comparison-kpi-main strong,
    body[data-theme="dark"] .an-pulse-grid .ds-comparison-kpi-result strong { color: #f8fafc; }
    body[data-theme="dark"] .an-pulse-grid .ds-comparison-kpi-label,
    body[data-theme="dark"] .an-pulse-grid .ds-comparison-kpi-description,
    body[data-theme="dark"] .an-pulse-grid .ds-comparison-kpi-row small { color: #aab4c5; }
    body[data-theme="dark"] .an-pulse-grid .ds-comparison-kpi-row { border-color: #263149; }
    body[data-theme="dark"] .an-pulse-grid .ds-comparison-kpi-row span { color: #d1d5db; }
    @media (max-width: 760px) {
        .an-pulse-grid .ds-comparison-kpi-main { padding: .72rem .75rem .65rem; }
        .an-pulse-grid .ds-comparison-kpi-main strong { font-size: 1.08rem; }
        .an-decision-pulse-grid .ds-comparison-kpi-main strong { font-size: 1.08rem; }
        .an-pulse-grid .ds-comparison-kpi-comparisons { padding-inline: .75rem; }
        .an-pulse-grid .ds-comparison-kpi-row { align-items: flex-start; flex-direction: column; gap: .22rem; }
        .an-pulse-grid .ds-comparison-kpi-result { justify-content: space-between; width: 100%; }
    }

    /* Revenue ledger width contract: every layer follows the available viewport. */
    .an-revenue-workspace,
    .an-revenue-head,
    .an-revenue-panel,
    .an-revenue-section,
    .an-revenue-body,
    .an-revenue-body .an-table-wrap { width: 100%; max-width: 100%; min-width: 0; }
    .an-revenue-body .an-table-wrap { scrollbar-gutter: stable; }
    .an-revenue-body .an-cash-table { width: 100%; }
    @media (min-width: 1200px) {
        .an-revenue-body .an-cash-table { min-width: 1040px; }
    }
    @media (min-width: 961px) and (max-width: 1199px) {
        .an-revenue-body .an-cash-table { min-width: 100%; table-layout: fixed; }
        .an-revenue-body .an-cash-table th,
        .an-revenue-body .an-cash-table td { padding-inline: .55rem; }
        .an-revenue-body .an-cash-table th:nth-child(1),
        .an-revenue-body .an-cash-table td:nth-child(1) { width: 20%; }
        .an-revenue-body .an-cash-table th:nth-child(2),
        .an-revenue-body .an-cash-table td:nth-child(2) { width: 18%; }
        .an-revenue-body .an-cash-table th:nth-child(3),
        .an-revenue-body .an-cash-table td:nth-child(3) { display: none; }
        .an-revenue-body .an-cash-table .an-cash-order,
        .an-revenue-body .an-cash-table .an-cash-meta { white-space: normal; overflow-wrap: anywhere; }
        .an-revenue-body .an-cash-table .an-cash-payment { max-width: none; overflow-wrap: anywhere; }
    }
    @media (max-width: 960px) {
        .an-revenue-head { box-sizing: border-box; flex-direction: column; gap: .6rem; }
        .an-revenue-head-meta { justify-items: start; white-space: normal; }
        .an-revenue-panel { overflow: visible; }
        .an-revenue-body { padding-inline: .65rem; }
        .an-revenue-body .an-table-wrap { overflow: visible; border: 0; background: transparent; box-shadow: none; }
        .an-revenue-body .an-cash-table { display: block; width: 100%; min-width: 0; border-spacing: 0; }
        .an-revenue-body .an-cash-table thead { display: none; }
        .an-revenue-body .an-cash-table tbody { display: grid; gap: .7rem; }
        .an-revenue-body .an-cash-table tr { display: grid; gap: .35rem; padding: .8rem; border: 1px solid var(--gf-color-line, #e4e7ec); border-radius: var(--an-radius-card); background: var(--gf-color-surface, #fff); box-shadow: var(--gf-shadow-sm, 0 1px 2px rgba(16, 24, 40, .04)); }
        .an-revenue-body .an-cash-table td { display: grid; grid-template-columns: minmax(96px, .58fr) minmax(0, 1fr); align-items: start; gap: .7rem; padding: .35rem 0; border-bottom: 1px solid var(--gf-color-line, #f0f1f3); text-align: right !important; white-space: normal; }
        .an-revenue-body .an-cash-table td::before { content: attr(data-label); color: var(--gf-color-muted, #667085); font-size: .6875rem; font-weight: 650; letter-spacing: .03em; line-height: 1.25; text-align: left; text-transform: uppercase; }
        .an-revenue-body .an-cash-table td:first-child { display: block; padding-top: 0; text-align: left !important; }
        .an-revenue-body .an-cash-table td:first-child::before { display: none; }
        .an-revenue-body .an-cash-table td:last-child { padding-bottom: 0; border-bottom: 0; }
        .an-revenue-body .an-cash-table td:nth-child(3),
        .an-revenue-body .an-cash-table td:nth-child(8) { display: none; }
        .an-revenue-body .an-cash-table .an-cash-payment { max-width: none; text-align: right; }
        .an-revenue-body .an-cash-table .an-cash-detail-grid { grid-template-columns: 1fr; }
        body[data-theme="dark"] .an-revenue-body .an-cash-table tr { background: #1e293b; }
    }

    /* Revenue content contract: shared rhythm for header, tabs, snapshot, detail and pagination. */
    .an-revenue-head-copy > div,
    .an-revenue-section-head > div { min-width: 0; }
    .an-revenue-head-copy .an-enterprise-sub { max-width: 760px; overflow-wrap: anywhere; }
    .an-revenue-tabs { width: 100%; max-width: 100%; overscroll-behavior-inline: contain; }
    .an-revenue-tabs .an-modal-tab { min-width: max-content; }
    .an-revenue-summary { width: 100%; min-width: 0; }
    .an-revenue-summary .an-modal-stat { min-width: 0; }
    .an-revenue-summary .an-modal-stat-value,
    .an-revenue-summary .an-modal-stat-note { overflow-wrap: anywhere; }
    .an-revenue-section-head { min-width: 0; }
    .an-revenue-section-hint { max-width: 360px; overflow-wrap: anywhere; }
    .an-revenue-panel > .an-modal-foot { min-width: 0; }
    .an-revenue-head { padding: .78rem .85rem; }
    .an-revenue-head-copy { gap: .55rem; }
    .an-revenue-head-icon { flex-basis: 34px; width: 34px; height: 34px; border-radius: 10px; }
    .an-revenue-head-copy .an-enterprise-sub { font-size: var(--an-type-caption); line-height: 1.35; }
    .an-revenue-section-head { padding: .62rem .75rem .2rem; }
    .an-revenue-section-kicker { font-size: var(--an-type-caption); }
    .an-revenue-summary { gap: .5rem; padding: .4rem .75rem .65rem; }
    .an-revenue-summary .an-modal-stat { min-height: 70px; padding: .58rem .62rem; }
    .an-revenue-summary { grid-template-columns: repeat(auto-fit, minmax(172px, 1fr)); align-items: stretch; }
    .an-revenue-summary .an-modal-stat { position: relative; display: flex; flex-direction: column; min-height: 104px; overflow: hidden; padding: .72rem .75rem; border-radius: var(--an-radius-card); }
    .an-revenue-summary .an-modal-stat::after { position: absolute; right: -.9rem; bottom: -.9rem; width: 52px; height: 52px; border-radius: 50%; background: currentColor; content: ''; opacity: .045; pointer-events: none; }
    .an-modal-stat-head { display: flex; align-items: center; min-width: 0; gap: .42rem; }
    .an-modal-stat-icon { display: grid; width: 28px; height: 28px; flex: 0 0 28px; place-items: center; border-radius: 9px; color: var(--gf-color-brand-strong, #4142bb); background: #eeefff; font-size: .78rem; }
    .an-modal-stat-label { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .an-revenue-summary .an-modal-stat-value { margin-top: .65rem; font-size: clamp(1.05rem, 1.5vw, 1.35rem); font-weight: 850; letter-spacing: -.045em; line-height: 1.05; }
    .an-revenue-summary .an-modal-stat-note { min-height: 1.35em; margin-top: auto; padding-top: .32rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .an-revenue-summary .an-modal-stat-primary { background: linear-gradient(145deg, #f1f0ff 0%, #fbfbff 100%); }
    .an-revenue-summary .an-modal-stat-primary .an-modal-stat-icon { color: var(--gf-color-brand-strong, #4142bb); background: #e5e5ff; }
    .an-revenue-summary .an-modal-stat-accent { background: linear-gradient(145deg, #edfcfc 0%, #fbffff 100%); }
    .an-revenue-summary .an-modal-stat-accent .an-modal-stat-icon { color: #087f86; background: #d9f7f7; }
    .an-revenue-summary .an-modal-stat-fee .an-modal-stat-icon { color: #a15c00; background: #fff0cc; }
    .an-revenue-summary .an-modal-stat-shipping .an-modal-stat-icon { color: #1769aa; background: #e2f1ff; }
    .an-revenue-summary .an-modal-stat-tax .an-modal-stat-icon { color: #b42318; background: #ffe4e1; }
    .an-revenue-summary .an-modal-stat-neutral .an-modal-stat-icon { color: var(--gf-color-muted, #667085); background: var(--gf-color-surface-muted, #f2f4f7); }
    .an-revenue-summary .an-modal-stat-settled { border-color: #bcecef; background: linear-gradient(145deg, #effdfd 0%, #fbffff 100%); }
    .an-revenue-summary .an-modal-stat-settled .an-modal-stat-icon { color: #087f86; background: #d9f7f7; }
    body[data-theme="dark"] .an-revenue-summary .an-modal-stat-icon { color: #c7c8f5; background: #1e293b; }
    body[data-theme="dark"] .an-revenue-summary .an-modal-stat-accent .an-modal-stat-icon { color: #99f6e4; background: rgba(6, 78, 59, .45); }
    body[data-theme="dark"] .an-revenue-summary .an-modal-stat-fee .an-modal-stat-icon { color: #fed7aa; background: rgba(124, 45, 18, .45); }
    body[data-theme="dark"] .an-revenue-summary .an-modal-stat-shipping .an-modal-stat-icon { color: #bfdbfe; background: rgba(30, 64, 175, .45); }
    body[data-theme="dark"] .an-revenue-summary .an-modal-stat-tax .an-modal-stat-icon { color: #fecaca; background: rgba(127, 29, 29, .45); }
    body[data-theme="dark"] .an-revenue-summary .an-modal-stat-settled { border-color: rgba(45, 212, 191, .35); background: rgba(6, 78, 59, .25); }
    body[data-theme="dark"] .an-revenue-summary .an-modal-stat-settled .an-modal-stat-icon { color: #99f6e4; background: rgba(6, 78, 59, .45); }
    .an-revenue-tabs .an-modal-tab { min-height: var(--gf-touch-target, 44px); padding: .44rem .55rem .4rem; }
    .an-revenue-fee-breakdown { margin: .25rem 0 .55rem; padding: .62rem .68rem .12rem; }
    .an-revenue-fee-breakdown-kicker { font-size: var(--an-type-caption); }
    .an-revenue-fee-breakdown-head strong { font-size: var(--an-type-body); }
    .an-revenue-fee-breakdown-head small { display: none; }
    .an-revenue-fee-breakdown { margin: .45rem .75rem .7rem; padding: .7rem; border-color: var(--gf-color-line, #e4e7ec); border-radius: var(--an-radius-card); background: var(--gf-color-surface-muted, #f8f9fc); box-shadow: none; }
    .an-revenue-fee-breakdown-head { align-items: center; margin: 0; }
    .an-revenue-fee-breakdown-head strong { display: block; margin: 0; font-size: var(--an-type-body); font-weight: 800; }
    .an-revenue-fee-breakdown-head small { display: block; margin-top: .12rem; color: var(--gf-color-muted, #667085); font-size: var(--an-type-caption); font-weight: 650; }
    .an-revenue-fee-breakdown-total strong { font-size: var(--an-type-body); }
    .an-revenue-fee-breakdown-total small { color: var(--gf-color-brand-strong, #4142bb); font-size: var(--an-type-caption); }
    .an-fee-burden-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem; margin-top: .6rem; }
    .an-fee-burden { min-width: 0; padding: .65rem; border: 1px solid var(--gf-color-line, #e4e7ec); border-radius: var(--an-radius-control); background: var(--gf-color-surface, #fff); }
    .an-fee-burden-head { display: grid; grid-template-columns: 28px minmax(0, 1fr) auto; align-items: center; gap: .42rem; }
    .an-fee-burden-icon { display: grid; width: 28px; height: 28px; place-items: center; border-radius: 9px; color: var(--gf-color-brand-strong, #4142bb); background: #eeefff; font-size: .78rem; }
    .an-fee-burden-head > div { min-width: 0; }
    .an-fee-burden-head strong, .an-fee-burden-head small { display: block; }
    .an-fee-burden-head strong { color: var(--gf-color-content, #101828); font-size: .72rem; font-weight: 800; }
    .an-fee-burden-head small { margin-top: .1rem; color: var(--gf-color-muted, #667085); font-size: .6rem; font-weight: 650; }
    .an-fee-burden-head b { color: var(--gf-color-content, #101828); font-size: .72rem; font-weight: 800; white-space: nowrap; }
    .an-fee-burden-list { display: grid; gap: .32rem; margin-top: .55rem; padding-top: .5rem; border-top: 1px solid var(--gf-color-line, #eef0f3); }
    .an-fee-burden-row { display: flex; align-items: baseline; justify-content: space-between; gap: .6rem; color: var(--gf-color-muted, #667085); font-size: var(--an-type-caption); }
    .an-fee-burden-row span { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .an-fee-burden-row strong { flex: 0 0 auto; color: var(--gf-color-content, #101828); font-size: .68rem; font-weight: 750; text-align: right; }
    .an-fee-burden-row small { display: block; margin-top: .08rem; color: var(--gf-color-muted, #667085); font-size: .58rem; font-weight: 650; }
    .an-fee-burden-empty { color: var(--gf-color-muted, #98a2b3); font-size: var(--an-type-caption); }
    .an-fee-burden-platform .an-fee-burden-icon { color: #087f86; background: #d9f7f7; }
    .an-fee-burden-platform .an-fee-burden-head b { color: #087f86; }
    body[data-theme="dark"] .an-revenue-fee-breakdown { border-color: #334155; background: #0f172a; }
    body[data-theme="dark"] .an-fee-burden { border-color: #334155; background: #1e293b; }
    body[data-theme="dark"] .an-fee-burden-icon { background: #1e1b4b; color: #c7c8f5; }
    body[data-theme="dark"] .an-fee-burden-platform .an-fee-burden-icon { background: rgba(6, 78, 59, .45); color: #99f6e4; }
    body[data-theme="dark"] .an-fee-burden-list { border-color: #334155; }
    .an-revenue-panel > .an-modal-foot { padding: .55rem .75rem; }
    @media (min-width: 961px) and (max-width: 1199px) {
        .an-revenue-summary { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (max-width: 960px) {
        .an-revenue-head-copy { width: 100%; }
        .an-revenue-head-meta { width: 100%; grid-template-columns: auto minmax(0, 1fr); align-items: center; gap: .45rem .7rem; }
        .an-revenue-period { min-width: 0; overflow-wrap: anywhere; }
        .an-revenue-tabs { padding-inline: .45rem; }
        .an-revenue-section-head { align-items: flex-start; flex-direction: column; gap: .25rem; padding-inline: .65rem; }
        .an-revenue-section-hint { max-width: none; text-align: left; }
        .an-revenue-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem; padding-inline: .65rem; }
        .an-revenue-summary .an-modal-stat { min-height: 96px; padding: .65rem; }
        .an-revenue-fee-breakdown-head { align-items: flex-start; flex-direction: column; gap: .25rem; }
        .an-revenue-fee-breakdown-head small { max-width: none; text-align: left; }
        .an-revenue-fee-breakdown-total { width: 100%; text-align: left; }
        .an-fee-burden-grid { grid-template-columns: 1fr; }
        .an-revenue-body .an-modal-fee-breakdown { grid-template-columns: 1fr; }
        .an-revenue-panel > .an-modal-foot { flex-wrap: wrap; gap: .5rem; padding: .65rem; }
        .an-revenue-panel > .an-modal-foot > .an-btn { flex: 1 1 0; min-width: 120px; min-height: var(--gf-touch-target, 44px); }
        .an-revenue-panel > .an-modal-foot .an-modal-pagination { order: -1; width: 100%; }
    }
    @media (max-width: 420px) {
        .an-revenue-summary { grid-template-columns: 1fr; }
        .an-revenue-summary .an-modal-stat { min-height: 92px; }
        .an-revenue-head-copy { gap: .55rem; }
        .an-revenue-head-icon { flex-basis: 34px; width: 34px; height: 34px; border-radius: 10px; }
        .an-revenue-head-meta { grid-template-columns: 1fr; justify-items: start; }
        .an-revenue-panel > .an-modal-foot > .an-btn { min-width: 100%; }
    }

    /* Simple KPI adapter: align revenue and fee summaries with Design System ds-kpi. */
    .an-modal-summary.ds-grid,
    .an-fee-analysis-summary.ds-grid { align-items: stretch; }
    .an-modal-summary.ds-grid .ds-kpi,
    .an-fee-analysis-summary.ds-grid .ds-kpi {
        display: flex;
        min-height: 104px;
        flex-direction: column;
        justify-content: space-between;
        padding: .9rem;
        border: 1px solid var(--gf-color-line, #e4e7ec);
        border-radius: var(--gf-radius-card, 16px);
        background: var(--gf-color-surface, #fff);
        box-shadow: var(--gf-shadow-sm, 0 1px 2px rgba(16, 24, 40, .04));
    }
    .an-modal-summary.ds-grid .ds-kpi small,
    .an-fee-analysis-summary.ds-grid .ds-kpi small { color: var(--gf-color-muted, #667085); font-size: var(--an-type-caption, .68rem); font-weight: 700; line-height: 1.3; }
    .an-modal-summary.ds-grid .ds-kpi strong,
    .an-fee-analysis-summary.ds-grid .ds-kpi strong { display: block; margin: .48rem 0 .2rem; color: var(--gf-color-content, #101828); font-size: clamp(1.15rem, 1.65vw, 1.55rem); font-weight: 850; letter-spacing: -.06em; line-height: 1; font-variant-numeric: tabular-nums; }
    .an-modal-summary.ds-grid .ds-kpi p,
    .an-fee-analysis-summary.ds-grid .ds-kpi p { min-height: 1.35em; margin: 0; color: var(--gf-color-success, #14945a); font-size: var(--an-type-caption, .66rem); font-weight: 700; line-height: 1.35; }
    .an-modal-summary.ds-grid .ds-kpi p i,
    .an-fee-analysis-summary.ds-grid .ds-kpi p i { margin-right: .16rem; }
    .an-modal-summary.ds-grid .ds-kpi.an-modal-stat-fee,
    .an-fee-analysis-summary.ds-grid .ds-kpi.an-modal-stat-fee { border-color: color-mix(in srgb, var(--gf-color-warning, #b7791f) 32%, var(--gf-color-line, #e4e7ec)); background: color-mix(in srgb, var(--gf-color-warning, #b7791f) 8%, var(--gf-color-surface, #fff)); }
    .an-modal-summary.ds-grid .ds-kpi.an-modal-stat-settled,
    .an-fee-analysis-summary.ds-grid .ds-kpi.an-modal-stat-settled { border-color: color-mix(in srgb, var(--gf-color-accent, #13b8c4) 32%, var(--gf-color-line, #e4e7ec)); background: color-mix(in srgb, var(--gf-color-accent, #13b8c4) 8%, var(--gf-color-surface, #fff)); }
    .an-modal-summary.ds-grid .ds-kpi.an-modal-stat-primary { border-color: color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 26%, var(--gf-color-line, #e4e7ec)); background: color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 6%, var(--gf-color-surface, #fff)); }
    .an-modal-summary.ds-grid .ds-kpi.an-modal-stat-accent { border-color: color-mix(in srgb, var(--gf-color-accent, #13b8c4) 32%, var(--gf-color-line, #e4e7ec)); background: color-mix(in srgb, var(--gf-color-accent, #13b8c4) 8%, var(--gf-color-surface, #fff)); }
    body[data-theme="dark"] .an-modal-summary.ds-grid .ds-kpi,
    body[data-theme="dark"] .an-fee-analysis-summary.ds-grid .ds-kpi { border-color: #334155; background: #1e293b; }
    body[data-theme="dark"] .an-modal-summary.ds-grid .ds-kpi strong,
    body[data-theme="dark"] .an-fee-analysis-summary.ds-grid .ds-kpi strong { color: #f8fafc; }
    body[data-theme="dark"] .an-modal-summary.ds-grid .ds-kpi small,
    body[data-theme="dark"] .an-fee-analysis-summary.ds-grid .ds-kpi small { color: #aab4c5; }
    @media (max-width: 760px) {
        .an-modal-summary.ds-grid,
        .an-fee-analysis-summary.ds-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem; }
        .an-modal-summary.ds-grid .ds-kpi,
        .an-fee-analysis-summary.ds-grid .ds-kpi { min-height: 96px; padding: .75rem; }
    }
    @media (max-width: 420px) {
        .an-modal-summary.ds-grid,
        .an-fee-analysis-summary.ds-grid { grid-template-columns: 1fr; }
    }

    /* Design System tab hierarchy adapter. */
    .an-tabs-wrap {
        display: grid;
        gap: .45rem;
        min-width: 0;
        padding: .6rem .65rem .5rem;
        border: 1px solid var(--gf-color-line, var(--dsh-border, #d4d7e3));
        border-radius: var(--gf-radius-card, 14px);
        background: var(--gf-color-surface, var(--card, #fff));
        box-shadow: var(--gf-shadow-sm, 0 1px 2px rgba(16, 24, 40, .04));
    }
    .an-tabs-meta {
        min-height: 24px;
        padding: 0 .2rem;
        color: var(--gf-color-muted, var(--dsh-muted, #667085));
    }
    .an-tabs-primary,
    .an-subtabs {
        width: 100%;
        min-width: 0;
        scrollbar-width: thin;
    }
    .an-tabs-primary .an-tab {
        min-height: var(--gf-touch-target, 44px);
        flex: 0 0 auto;
        border-radius: 9px;
        padding-inline: .72rem;
        color: var(--gf-color-muted, var(--dsh-muted, #667085));
        background: transparent;
        font-size: .68rem;
        font-weight: 750;
    }
    .an-tabs-primary .an-tab:hover {
        color: var(--gf-color-brand-strong, var(--accent, #2563eb));
        background: var(--gf-color-brand-soft, var(--accent-soft, #eef0ff));
    }
    .an-tabs-primary .an-tab.is-active,
    .an-tabs-primary .an-tab.active {
        color: var(--gf-color-brand-strong, var(--accent, #2563eb));
        background: var(--gf-color-surface, #fff);
        box-shadow: 0 1px 4px rgba(16, 24, 40, .1);
    }
    .an-tabs-primary .an-tab:focus-visible,
    .an-subtabs .ds-tab-system-button:focus-visible {
        outline: 3px solid rgba(91, 92, 226, .28);
        outline-offset: 2px;
    }
    .an-tabs-connector {
        width: auto;
        height: 1px;
        margin: 0 .5rem;
        background: var(--gf-color-line, #eaecf0);
    }
    .an-subtabs .ds-tab-system-button {
        min-height: 32px;
        padding-inline: .55rem;
        font-size: .65rem;
    }
    @media (max-width: 760px) {
        .an-tabs-wrap { gap: .38rem; padding: .55rem .5rem .45rem; }
        .an-tabs-meta { padding-inline: .1rem; }
        .an-tabs-primary,
        .an-subtabs { overflow-x: auto; }
        .an-tabs-primary .an-tab { min-height: 42px; padding-inline: .65rem; }
    }
    body[data-theme="dark"] .an-tabs-primary .an-tab.is-active,
    body[data-theme="dark"] .an-tabs-primary .an-tab.active { color: #e0e7ff; background: #1f2937; }
    body[data-theme="dark"] .an-tabs-connector { background: #334155; }

    /* Keep every analytics section on the same Design System rhythm. */
    .an-hero-controls { margin-bottom: var(--gf-space-section, 24px); }
    .an-shell { gap: var(--gf-space-section, 24px); }
    #analyticsTabPanel {
        display: grid;
        gap: var(--gf-space-section, 24px);
        min-width: 0;
    }
    #analyticsTabPanel > .an-tab-pane { margin-top: 0; }
    #analyticsTabPanel .an-portfolio-ledger-grid,
    #analyticsTabPanel .an-product-snapshot-content { margin-top: var(--gf-space-card, 16px); }
    #analyticsTabPanel .an-portfolio-overview-grid,
    #analyticsTabPanel .an-portfolio-ledger-grid { gap: var(--gf-space-card, 16px); }

    /* Fee Analysis assembly: same section hierarchy as Finance Fee Analysis,
       with the analytics data contract and Design System tokens. */
    .an-fee-analysis { display: grid; gap: var(--gf-space-card, 16px); min-width: 0; }
    .an-fee-analysis-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 1rem 1.1rem; border: 1px solid #e0e2fb; border-radius: var(--gf-radius-card, 16px); background: linear-gradient(120deg, #f7f6ff 0%, #fff 56%, #effdff 100%); box-shadow: var(--gf-shadow-sm, 0 1px 2px rgba(15,23,42,.04)); }
    .an-fee-analysis-head-copy { display: flex; align-items: flex-start; gap: .72rem; min-width: 0; }
    .an-fee-analysis-icon { display: grid; width: 34px; height: 34px; flex: 0 0 auto; place-items: center; border-radius: 11px; color: var(--gf-color-brand-strong, #4142bb); background: #e9e9ff; }
    .an-fee-analysis-head h2 { margin: .18rem 0 0; color: var(--gf-color-content, #101828); font-size: clamp(1.05rem, 2vw, 1.35rem); font-weight: 820; letter-spacing: -.04em; }
    .an-fee-analysis-head p { max-width: 640px; margin: .32rem 0 0; color: var(--gf-color-muted, #667085); font-size: var(--an-type-caption, .66rem); line-height: 1.45; }
    .an-fee-analysis-meta { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: .4rem; }
    .an-fee-analysis-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: var(--gf-space-card, 16px); min-width: 0; }
    .an-fee-analysis-summary:empty { display: none; }
    .an-fee-analysis-summary .an-modal-stat { min-width: 0; }
    .an-fee-analysis-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: var(--gf-space-card, 16px); min-width: 0; }
    .an-fee-analysis-card { min-width: 0; overflow: hidden; border: 1px solid var(--gf-color-line, #e4e7ec); border-radius: var(--gf-radius-card, 16px); background: var(--gf-color-surface, #fff); box-shadow: var(--gf-shadow-sm, 0 1px 2px rgba(15,23,42,.04)); }
    .an-fee-analysis-card-wide { grid-column: 1 / -1; }
    .an-fee-analysis-card-head { display: flex; align-items: flex-start; justify-content: space-between; gap: .7rem; min-height: 72px; padding: .9rem 1rem; border-bottom: 1px solid var(--gf-color-line, #e4e7ec); }
    .an-fee-analysis-kicker { display: block; color: var(--gf-color-muted, #667085); font-size: .59rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
    .an-fee-analysis-card-head h3 { margin: .18rem 0 0; color: var(--gf-color-content, #101828); font-size: .82rem; font-weight: 820; letter-spacing: -.02em; }
    .an-fee-analysis-count { flex: 0 0 auto; color: var(--gf-color-muted, #667085); font-size: .62rem; font-weight: 750; white-space: nowrap; }
    .an-fee-analysis-head-metrics { display: flex; align-items: center; gap: .75rem; flex: 0 0 auto; }
    .an-fee-composition-total { display: grid; justify-items: end; gap: .08rem; }
    .an-fee-composition-total span, .an-fee-composition-total small { color: var(--gf-color-muted, #667085); font-size: .57rem; font-weight: 750; line-height: 1.25; }
    .an-fee-composition-total strong { color: var(--gf-color-brand-strong, #4142bb); font-size: .76rem; font-weight: 850; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .an-fee-composition-percent { color: var(--gf-color-muted, #667085) !important; font-size: .65rem; font-weight: 800; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .an-fee-analysis-table-wrap { min-width: 0; }
    .an-fee-analysis-table { width: 100%; margin: 0; }
    .an-fee-analysis-table th:last-child, .an-fee-analysis-table td:last-child { text-align: right; }
    .an-fee-analysis-table td { vertical-align: middle; }
    .an-fee-analysis-table td:nth-child(2) { color: var(--gf-color-muted, #667085); font-variant-numeric: tabular-nums; }
    .an-fee-analysis-amount { color: var(--gf-color-content, #101828) !important; font-weight: 800; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .an-fee-order-table th:nth-child(n+3), .an-fee-order-table td:nth-child(n+3) { text-align: right; }
    .an-fee-platform-amount { color: #087f86 !important; }
    .an-fee-ads-amount { color: #a15c07 !important; }
    .an-fee-ads-amount small { display: block; margin-top: .1rem; color: var(--gf-color-muted, #667085); font-size: .52rem; font-weight: 700; }
    .an-fee-type-name { color: var(--gf-color-content, #101828); font-size: .7rem; font-weight: 800; }
    .an-fee-burden-badge { display: inline-flex; align-items: center; justify-content: center; gap: .25rem; min-height: 22px; padding: 0 .48rem; border-radius: 999px; font-size: .58rem; font-weight: 800; white-space: nowrap; }
    .an-fee-burden-badge-seller { color: var(--gf-color-brand-strong, #4142bb); background: #eeefff; }
    .an-fee-burden-badge-platform { color: #087f86; background: #d9f7f7; }
    .an-fee-store-table th:nth-child(n+2), .an-fee-store-table td:nth-child(n+2) { text-align: right; }
    .an-fee-shipping-section { margin: .8rem; border: 1px solid #bcecef; border-radius: var(--gf-radius-control, 10px); background: linear-gradient(135deg, #f3feff 0%, #fff 72%); overflow: hidden; }
    .an-fee-shipping-section > header { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; padding: .75rem .85rem .65rem; border-bottom: 1px solid #d9f7f7; }
    .an-fee-shipping-section > header strong, .an-fee-shipping-section > header small { display: block; }
    .an-fee-shipping-section > header strong { margin-top: .15rem; color: var(--gf-color-content, #101828); font-size: .76rem; font-weight: 820; }
    .an-fee-shipping-section > header small { max-width: 560px; margin-top: .18rem; color: var(--gf-color-muted, #667085); font-size: .6rem; line-height: 1.4; }
    .an-fee-shipping-chip { display: inline-flex; align-items: center; gap: .28rem; min-height: 24px; padding: 0 .48rem; border-radius: 999px; color: #087f86; background: #d9f7f7; font-size: .58rem; font-weight: 800; white-space: nowrap; }
    .an-fee-shipping-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .6rem; padding: .7rem .85rem .85rem; }
    .an-fee-shipping-metric { display: flex; align-items: center; justify-content: space-between; gap: .65rem; padding: .65rem .7rem; border: 1px solid var(--gf-color-line, #e4e7ec); border-radius: var(--gf-radius-control, 10px); background: var(--gf-color-surface, #fff); }
    .an-fee-shipping-metric-head { display: flex; align-items: center; gap: .45rem; min-width: 0; }
    .an-fee-shipping-metric-head > div { min-width: 0; }
    .an-fee-shipping-icon { display: grid; width: 28px; height: 28px; flex: 0 0 auto; place-items: center; border-radius: 9px; color: var(--gf-color-brand-strong, #4142bb); background: #eeefff; font-size: .75rem; }
    .an-fee-shipping-metric-platform .an-fee-shipping-icon { color: #087f86; background: #d9f7f7; }
    .an-fee-shipping-metric strong, .an-fee-shipping-metric small { display: block; }
    .an-fee-shipping-metric strong { color: var(--gf-color-content, #101828); font-size: .66rem; font-weight: 800; }
    .an-fee-shipping-metric small { margin-top: .1rem; color: var(--gf-color-muted, #667085); font-size: .57rem; }
    .an-fee-shipping-metric > b { display: block; flex: 0 0 auto; color: var(--gf-color-content, #101828); font-size: .72rem; font-weight: 850; text-align: right; white-space: nowrap; }
    .an-fee-shipping-metric > b small { display: block; margin-top: .12rem; color: var(--gf-color-muted, #667085); font-size: .54rem; font-weight: 700; }
    .an-fee-shipping-metric-platform > b { color: #087f86; }
    .an-fee-other-section { margin: .8rem; border: 1px solid #ead9b7; border-radius: var(--gf-radius-control, 10px); background: linear-gradient(135deg, #fffbf3 0%, #fff 72%); overflow: hidden; }
    .an-fee-other-section > header { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; padding: .75rem .85rem .65rem; border-bottom: 1px solid #f3e7ca; }
    .an-fee-other-section > header strong, .an-fee-other-section > header small { display: block; }
    .an-fee-other-section > header strong { margin-top: .15rem; color: var(--gf-color-content, #101828); font-size: .76rem; font-weight: 820; }
    .an-fee-other-section > header small { max-width: 560px; margin-top: .18rem; color: var(--gf-color-muted, #667085); font-size: .6rem; line-height: 1.4; }
    .an-fee-other-chip { display: inline-flex; align-items: center; gap: .28rem; min-height: 24px; padding: 0 .48rem; border-radius: 999px; color: #9a6700; background: #fff0c7; font-size: .58rem; font-weight: 800; white-space: nowrap; }
    .an-fee-other-total { display: flex; align-items: center; justify-content: space-between; gap: .65rem; padding: .75rem .85rem; }
    .an-fee-other-total span, .an-fee-other-total small { display: block; }
    .an-fee-other-total span { color: var(--gf-color-content, #101828); font-size: .66rem; font-weight: 800; }
    .an-fee-other-total small { margin-top: .12rem; color: var(--gf-color-muted, #667085); font-size: .57rem; font-weight: 700; }
    .an-fee-other-total > strong { color: #9a6700; font-size: .78rem; font-weight: 850; text-align: right; white-space: nowrap; }
    .an-fee-other-total > strong small { color: var(--gf-color-muted, #667085); font-size: .54rem; }
    .an-fee-analysis-burden-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; padding: .8rem; }
    .an-fee-analysis-burden { min-width: 0; overflow: hidden; border: 1px solid var(--gf-color-line, #e4e7ec); border-radius: var(--gf-radius-control, 10px); background: var(--gf-color-surface-muted, #f8f9fc); }
    .an-fee-analysis-burden header { display: grid; grid-template-columns: 28px minmax(0, 1fr) auto; align-items: center; gap: .45rem; padding: .7rem .75rem; }
    .an-fee-analysis-burden header > div { min-width: 0; }
    .an-fee-analysis-burden header strong, .an-fee-analysis-burden header small { display: block; }
    .an-fee-analysis-burden header strong { color: var(--gf-color-content, #101828); font-size: .68rem; font-weight: 800; }
    .an-fee-analysis-burden header small { margin-top: .1rem; color: var(--gf-color-muted, #667085); font-size: .58rem; }
    .an-fee-analysis-burden header b { color: var(--gf-color-content, #101828); font-size: .68rem; font-weight: 800; white-space: nowrap; }
    .an-fee-analysis-burden-icon { display: grid; width: 28px; height: 28px; place-items: center; border-radius: 9px; color: var(--gf-color-brand-strong, #4142bb); background: #eeefff; font-size: .75rem; }
    .an-fee-analysis-burden-platform .an-fee-analysis-burden-icon { color: #087f86; background: #d9f7f7; }
    .an-fee-analysis-burden-platform header b { color: #087f86; }
    .an-fee-analysis-burden .an-table-wrap { overflow-x: auto; }
    .an-fee-analysis-burden .an-fee-analysis-table { min-width: 360px; background: var(--gf-color-surface, #fff); }
    .an-fee-analysis-footer { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .7rem 1rem; border-top: 1px solid var(--gf-color-line, #e4e7ec); }
    .an-fee-analysis-footer .an-modal-pagination { display: grid; justify-items: center; gap: .08rem; min-width: 0; }
    .an-fee-analysis-footer .an-modal-page-meta { color: var(--gf-color-muted, #667085); font-size: .6rem; }
    @media (max-width: 760px) {
        .an-fee-analysis-head { flex-direction: column; }
        .an-fee-analysis-meta { justify-content: flex-start; }
        .an-fee-analysis-head-metrics { width: 100%; justify-content: space-between; }
        .an-fee-composition-total { justify-items: start; }
        .an-fee-other-section { margin: .65rem; }
        .an-fee-other-section > header { flex-direction: column; }
        .an-fee-analysis-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .an-fee-analysis-grid { grid-template-columns: 1fr; }
        .an-fee-analysis-card-wide { grid-column: auto; }
        .an-fee-analysis-burden-grid { grid-template-columns: 1fr; padding: .65rem; }
        .an-fee-shipping-section { margin: .65rem; }
        .an-fee-shipping-section > header { flex-direction: column; }
        .an-fee-shipping-grid { grid-template-columns: 1fr; }
        .an-fee-analysis-table-wrap { overflow-x: auto; }
        .an-fee-analysis-table { min-width: 540px; }
        .an-fee-order-table { min-width: 0; }
        .an-fee-order-table thead { display: none; }
        .an-fee-order-table, .an-fee-order-table tbody, .an-fee-order-table tr, .an-fee-order-table td { display: block; width: 100%; }
        .an-fee-order-table tr { padding: .7rem .85rem; border-bottom: 1px solid var(--gf-color-line, #e4e7ec); }
        .an-fee-order-table tr:last-child { border-bottom: 0; }
        .an-fee-order-table td { display: flex; align-items: baseline; justify-content: space-between; gap: .75rem; padding: .18rem 0; border: 0; text-align: right !important; }
        .an-fee-order-table td::before { content: attr(data-label); color: var(--gf-color-muted, #667085); font-size: .62rem; font-weight: 750; text-align: left; }
        .an-fee-order-table td:first-child { display: block; text-align: left !important; }
        .an-fee-order-table td:first-child::before { display: none; }
        .an-fee-order-table td:first-child .an-cash-meta { display: block; }
        .an-fee-analysis-footer { align-items: stretch; }
        .an-fee-analysis-footer .an-btn { min-height: 40px; }
    }
    body[data-theme="dark"] .an-fee-analysis-head { border-color: #334155; background: linear-gradient(120deg, #17173a 0%, #111827 58%, #0f2c32 100%); }
    body[data-theme="dark"] .an-fee-analysis-card { border-color: #334155; background: #111827; }
    body[data-theme="dark"] .an-fee-analysis-card-head, body[data-theme="dark"] .an-fee-analysis-footer { border-color: #334155; }
    @media (max-width: 760px) {
        .an-hero-controls { margin-bottom: var(--gf-space-section, 24px); }
        #analyticsTabPanel { gap: var(--gf-space-section, 24px); }
    }
</style>
@endpush

@section('content')
<div class="dash py-3">
    <div class="page-wrap ads-shell an-page">
        <div class="an-hero">
            <div class="an-hero-copy">
                <div class="an-hero-heading">
                    <span class="an-hero-mark" aria-hidden="true"><i class="bi bi-bar-chart-line"></i></span>
                    <div>
                        <div class="an-hero-eyebrow"><span class="an-hero-eyebrow-dot"></span>Marketplace analytics</div>
                        <div class="an-hero-title">Performance Analytics</div>
                        <p class="an-hero-sub">Omzet, payout, fee, dan operasional.</p>
                    </div>
                </div>
            </div>
            <div class="an-hero-meta" aria-label="Status workspace">
                <span class="an-hero-chip"><i class="bi bi-grid-1x2" aria-hidden="true"></i>Decision workspace</span>
                <span class="an-hero-chip an-hero-chip-muted"><i class="bi bi-broadcast" aria-hidden="true"></i>Data aktif</span>
            </div>
        </div>
        <div class="an-hero-controls" role="group" aria-label="Filter analytics">
            <div class="an-filter-intro">
                <span class="an-filter-icon" aria-hidden="true"><i class="bi bi-sliders2"></i></span>
                <div><strong>Filter laporan</strong><small>Pilih periode dan scope.</small></div>
            </div>
            <div class="an-filter-fields">
                <x-gf.select-field id="anPlatform" label="Platform" icon="bi-shop" wrapper-class="an-field" aria-label="Filter platform"><option value="" @selected(empty($filters['platform']))>Semua platform</option><option value="shopee" @selected(($filters['platform'] ?? '') === 'shopee')>Shopee</option><option value="tiktok" @selected(($filters['platform'] ?? '') === 'tiktok')>TikTok Shop</option></x-gf.select-field>
                <x-gf.select-field id="anStore" label="Filter toko" icon="bi-shop-window" wrapper-class="an-field" aria-label="Filter toko"><option value="">Semua toko</option></x-gf.select-field>
                <div class="an-field an-period-field ds-flatpickr-field"><label for="anDateRange">Periode</label><div class="ds-flatpickr-control"><i class="bi bi-calendar3 ds-flatpickr-icon" aria-hidden="true"></i><input type="text" id="anDateRange" autocomplete="off" aria-label="Periode" value="{{ $filters['date_from'] }} — {{ $filters['date_to'] }}"></div></div>
                <x-gf.select-field id="anCompare" label="Bandingkan" icon="bi-arrow-left-right" wrapper-class="an-field"><option value="prev_period" @selected(($filters['compare_mode'] ?? '') === 'prev_period')>Periode lalu</option><option value="prev_month" @selected(($filters['compare_mode'] ?? 'prev_month') === 'prev_month')>Tanggal sama bulan lalu</option><option value="prev_quarter" @selected(($filters['compare_mode'] ?? '') === 'prev_quarter')>Tanggal sama 3 bulan lalu</option><option value="prev_year" @selected(($filters['compare_mode'] ?? '') === 'prev_year')>Tanggal sama tahun lalu</option></x-gf.select-field>
                <input type="hidden" id="anDateFrom" value="{{ $filters['date_from'] }}"><input type="hidden" id="anDateTo" value="{{ $filters['date_to'] }}">
                <button class="an-btn an-btn-dark ds-btn ds-btn-primary" id="anRefresh" type="button"><i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>Refresh data</button>
            </div>
        </div>

        <div class="an-shell">
          <div class="an-tabs-wrap" id="analyticsTabNavigation">
            <div class="an-tabs-meta"><i class="bi bi-sliders2" aria-hidden="true"></i><span>Analytics workspace</span></div>
            <nav class="ds-tab-system an-tabs-primary" id="analyticsTabs" role="tablist" aria-label="Navigasi analytics">
                <button class="ds-tab-system-button an-tab is-active active" type="button" data-an-tab="overview" role="tab" id="analytics-tab-overview" aria-selected="true" aria-controls="analyticsTabPanel" tabindex="0"><i class="bi bi-grid-1x2" aria-hidden="true"></i>Ikhtisar</button>
                <button class="ds-tab-system-button an-tab" type="button" data-an-tab="sales" role="tab" id="analytics-tab-sales" aria-selected="false" aria-controls="analyticsTabPanel" tabindex="-1"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i>Penjualan</button>
                <button class="ds-tab-system-button an-tab" type="button" data-an-tab="revenue" role="tab" id="analytics-tab-revenue" aria-selected="false" aria-controls="analyticsTabPanel" tabindex="-1"><i class="bi bi-cash-stack" aria-hidden="true"></i>Omzet, Pencairan &amp; Fee</button>
                <button class="ds-tab-system-button an-tab" type="button" data-an-tab="fees" role="tab" id="analytics-tab-fees" aria-selected="false" aria-controls="analyticsTabPanel" tabindex="-1"><i class="bi bi-receipt" aria-hidden="true"></i>Fee Marketplace</button>
                <button class="ds-tab-system-button an-tab" type="button" data-an-tab="operations" role="tab" id="analytics-tab-operations" aria-selected="false" aria-controls="analyticsTabPanel" tabindex="-1"><i class="bi bi-activity" aria-hidden="true"></i>Operasional</button>
                <button class="ds-tab-system-button an-tab" type="button" data-an-tab="stores" role="tab" id="analytics-tab-stores" aria-selected="false" aria-controls="analyticsTabPanel" tabindex="-1"><i class="bi bi-shop" aria-hidden="true"></i>Toko &amp; Biaya</button>
                <button class="ds-tab-system-button an-tab" type="button" data-an-tab="products" role="tab" id="analytics-tab-products" aria-selected="false" aria-controls="analyticsTabPanel" tabindex="-1"><i class="bi bi-box-seam" aria-hidden="true"></i>Produk</button>
                <button class="ds-tab-system-button an-tab" type="button" data-an-tab="cohort" role="tab" id="analytics-tab-cohort" aria-selected="false" aria-controls="analyticsTabPanel" tabindex="-1"><i class="bi bi-grid-3x3-gap" aria-hidden="true"></i>Cohort</button>
            </nav>
          </div>
          <div id="analyticsTabPanel" role="tabpanel" aria-live="polite" aria-label="Ikhtisar">
            @include('marketplace.partials._analytics_summary')
            @include('marketplace.partials._analytics_sales')
            @include('marketplace.partials._analytics_revenue')
            @include('marketplace.partials._analytics_fees')
            @include('marketplace.partials._analytics_stores')
            @include('marketplace.partials._analytics_products')
            @include('marketplace.partials._analytics_cohort')
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
    let storesLoaded = false;
    let orders = [];
    let productsLoaded = false;
    let productsLoading = false;
    let productRequestToken = 0;
    let productAbortController = null;
    let productData = [];
    let adSpendBySku = {};
    let cashPage = 1;
    let cashPayload = null;
    let cashLoading = false;
    let cashRequestToken = 0;
    let cashAbortController = null;
    let cashSettlement = 'all';
    let cashFocus = 'payout';
    let cashTabSummaries = {};
    let cashTabSummaryToken = 0;
    let returnPage = 1;
    let returnPayload = null;
    let returnLoading = false;
    let returnRequestToken = 0;
    let returnAbortController = null;
    let returnType = 'return_refund';
    let cohortPayload = null;
    let cohortLoading = false;
    let cohortRequestToken = 0;
    let cohortAbortController = null;
    let cohortOptionsRequestToken = 0;
    let cohortOptionsAbortController = null;
    let cohortOptionsKey = null;
    let revenueChartInstance = null;
    let cohortChartInstance = null;
    let cohortDistributionChartInstance = null;
    let chartLibraryPromise = null;
    let chartRenderToken = 0;
    let cohortChartRenderToken = 0;
    let selectedPulseMetric = null;
    let analyticsLoadToken = 0;
    let analyticsAbortController = null;
    const APP_TIMEZONE = @json(config('app.timezone', 'Asia/Jakarta'));
    const from = () => $('anDateFrom').value;
    const to = () => $('anDateTo').value;
    const dateRangeReady = () => Boolean(from() && to());
    const isAbortError = error => error?.name === 'AbortError';
    const cancelPendingAnalytics = () => {
        analyticsLoadToken++;
        analyticsAbortController?.abort();
        analyticsAbortController = null;
        cashRequestToken++;
        cashAbortController?.abort();
        cashAbortController = null;
        cashLoading = false;
        returnRequestToken++;
        returnAbortController?.abort();
        returnAbortController = null;
        returnLoading = false;
        productRequestToken++;
        productAbortController?.abort();
        productsLoading = false;
        cohortRequestToken++;
        cohortAbortController?.abort();
        cohortOptionsRequestToken++;
        cohortOptionsAbortController?.abort();
        cohortLoading = false;
    };
    const n = v => Number.parseFloat(v || 0) || 0;
    const status = o => String(o.order_status || o.status || '').toUpperCase();
    const completed = o => ['COMPLETED', 'DELIVERED', 'CLOSED'].includes(status(o));
    const money = v => fmtRp(Math.round(v || 0));
    const pct = (a,b) => b ? (a / b * 100).toFixed(1) + '%' : '0%';
    const stateMarkup = (type = 'empty', title = '', message = '') => {
        const stateType = ['empty', 'loading', 'error'].includes(type) ? type : 'empty';
        const defaults = {
            empty: { title: 'Belum ada data', message: 'Belum ada data untuk ditampilkan pada filter ini.', icon: 'bi-inbox' },
            loading: { title: 'Memuat data', message: 'Menyiapkan data terbaru…', icon: '' },
            error: { title: 'Data belum dapat dimuat', message: 'Coba muat ulang halaman atau ubah filter.', icon: 'bi-exclamation-triangle' },
        }[stateType];
        const heading = esc(title || defaults.title);
        const detail = esc(message || defaults.message);
        const content = stateType === 'loading'
            ? `<div class="ds-skeleton" aria-hidden="true"><span></span><span></span><span></span></div><p class="ds-empty-loading-label">${heading}</p>`
            : `<div class="ds-empty-icon" aria-hidden="true"><i class="bi ${defaults.icon}"></i></div><strong>${heading}</strong><p>${detail}</p>`;
        return `<div class="ds-empty gf-analytics-state gf-analytics-state-${stateType}" role="status" aria-live="polite"><div>${content}</div></div>`;
    };
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
    const selectedPlatform = () => $('anPlatform').value;
    const selectedStore = () => $('anStore').value;
    const productPageUrl = @json(route('marketplace.products'));
    const productUrl = product => `${productPageUrl}?search=${encodeURIComponent(product.sku || product.product_name || '')}`;
    const initialStore = @json($filters['store_id'] ?? '');
    const initialPlatform = @json($filters['platform'] ?? '');
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

    const salesSnapshot = snapshot => {
        const row = snapshot || {};
        const netSales = n(row.net_order_revenue ?? row.gross_order_revenue ?? row.gmv ?? row.gross_sales);
        const payout = n(row.estimated_payout ?? row.payout ?? row.cash_payout);
        const hpp = n(row.hpp_total ?? row.hpp);
        const adCost = n(row.ad_cost);
        return {
            netSales,
            orders: n(row.order_total ?? row.order_count),
            qty: n(row.product_qty ?? row.qty),
            payout,
            profit: n(row.estimated_profit ?? row.operating_profit ?? (payout - hpp - adCost)),
            adCost,
            refund: n(row.return_refund_amount ?? row.cash_refund ?? row.refund),
        };
    };
    const salesDailyRow = row => {
        const grossSales = n(row?.gross_sales ?? row?.gmv);
        const cogs = n(row?.hpp);
        const payout = n(row?.payout);
        const grossProfit = n(row?.gross_profit ?? (payout - cogs));
        const adCost = n(row?.ad_cost);
        const orders = n(row?.orders ?? row?.order_count);
        const netSales = row?.net_sales == null ? grossSales : n(row.net_sales);
        const paid = row?.paid == null
            ? n(row?.cash_order_revenue ?? row?.cash_gross_sales ?? row?.gross_sales ?? 0)
            : n(row.paid);

        return {
            date: String(row?.date || ''),
            sales: grossSales,
            grossSales,
            netSales,
            paid,
            payout,
            cogs,
            grossProfit,
            profit: n(row?.operating_profit || (grossProfit - adCost)),
            adCost,
            orders,
            qty: n(row?.qty),
            avgOrder: orders > 0 ? grossSales / orders : 0,
        };
    };
    const salesDelta = (value, baseline, lowerIsBetter = false) => {
        const currentValue = n(value), previousValue = n(baseline);
        if (previousValue === 0) return { label: currentValue === 0 ? '0,0%' : 'Baru', tone: currentValue === 0 ? 'neutral' : (lowerIsBetter && currentValue > 0 ? 'bad' : 'good') };
        const change = (currentValue - previousValue) / Math.abs(previousValue) * 100;
        return { label: `${change >= 0 ? '↑' : '↓'} ${Math.abs(change).toFixed(1).replace('.', ',')}%`, tone: change === 0 ? 'neutral' : ((lowerIsBetter ? change < 0 : change > 0) ? 'good' : 'bad') };
    };
    const salesPeriodLabel = value => {
        const date = String(value || '');
        if (!date) return '—';
        return new Date(`${date}T00:00:00`).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    };
    const renderSalesTrendSummary = (currentRows, previousRows) => {
        const currentTotals = {
            sales: currentRows.reduce((sum, row) => sum + row.sales, 0),
            payout: currentRows.reduce((sum, row) => sum + row.payout, 0),
            profit: currentRows.reduce((sum, row) => sum + row.profit, 0),
            adCost: currentRows.reduce((sum, row) => sum + row.adCost, 0),
        };
        const previousTotals = {
            sales: previousRows.reduce((sum, row) => sum + row.sales, 0),
            payout: previousRows.reduce((sum, row) => sum + row.payout, 0),
            profit: previousRows.reduce((sum, row) => sum + row.profit, 0),
            adCost: previousRows.reduce((sum, row) => sum + row.adCost, 0),
        };
        const items = [
            ['Penjualan harian', 'sales', 'bi-graph-up-arrow', false],
            ['Estimasi payout', 'payout', 'bi-wallet2', false],
            ['Profit operasional', 'profit', 'bi-bar-chart-line', false],
            ['Biaya iklan', 'adCost', 'bi-megaphone', true],
        ];
        $('anSalesTrendSummary').innerHTML = items.map(([label, key, icon, lowerIsBetter]) => {
            const delta = salesDelta(currentTotals[key], previousTotals[key], lowerIsBetter);
            return `<div class="an-sales-trend-summary-card"><span class="an-sales-trend-summary-icon"><i class="bi ${icon}" aria-hidden="true"></i></span><div class="an-sales-trend-summary-content"><div class="an-sales-trend-summary-label">${label}</div><div class="an-sales-trend-summary-value">${money(currentTotals[key])}</div><span class="an-sales-trend-summary-delta ${delta.tone}">${delta.label} vs pembanding</span></div></div>`;
        }).join('');
        $('anSalesTrendTotal').textContent = money(currentTotals.sales);
        return { currentTotals, previousTotals };
    };
    const renderSalesChart = (currentRows, previousRows) => {
        const container = $('anSalesTrendChart');
        const current = currentRows.slice(-31);
        const previous = previousRows.slice(-31);
        if (!current.length && !previous.length) {
            container.innerHTML = stateMarkup('empty', 'Belum ada data penjualan harian', 'Belum ada data penjualan harian pada periode ini.');
            return;
        }
        const active = current.length ? current : previous;
        const width = 760, height = 215, pad = { top: 18, right: 16, bottom: 30, left: 58 };
        const values = [...current, ...previous].map(row => row.sales);
        const max = Math.max(...values, 1);
        const x = index => pad.left + (active.length <= 1 ? 0 : index / (active.length - 1)) * (width - pad.left - pad.right);
        const y = value => pad.top + (1 - (value / max)) * (height - pad.top - pad.bottom);
        const points = rows => rows.map((row, index) => `${x(index).toFixed(1)},${y(row.sales).toFixed(1)}`).join(' ');
        const area = current.length > 1 ? `${pad.left},${height - pad.bottom} ${points(current)} ${x(current.length - 1).toFixed(1)},${height - pad.bottom}` : '';
        const grid = [0, .25, .5, .75, 1].map(level => {
            const lineY = y(max * level);
            const label = money(max * level).replace('Rp ', '');
            return `<line class="sales-grid" x1="${pad.left}" x2="${width - pad.right}" y1="${lineY}" y2="${lineY}"></line><text class="sales-axis-label" x="${pad.left - 8}" y="${lineY + 3}" text-anchor="end">${esc(label)}</text>`;
        }).join('');
        const labels = active.map((row, index) => index === 0 || index === active.length - 1 || index === Math.floor(active.length / 2) ? `<text class="sales-axis-label" x="${x(index)}" y="${height - 8}" text-anchor="middle">${esc(salesPeriodLabel(row.date).replace(/\s+\d{4}$/, ''))}</text>` : '').join('');
        const circles = current.length <= 14 ? current.map((row, index) => `<circle class="sales-point" cx="${x(index)}" cy="${y(row.sales)}" r="3"><title>${esc(salesPeriodLabel(row.date))}: ${esc(money(row.sales))}</title></circle>`).join('') : '';
        const previousCircles = previous.length <= 14 ? previous.map((row, index) => `<circle class="sales-point previous" cx="${x(index)}" cy="${y(row.sales)}" r="2.5"><title>${esc(salesPeriodLabel(row.date))}: ${esc(money(row.sales))}</title></circle>`).join('') : '';
        container.innerHTML = `<svg viewBox="0 0 ${width} ${height}" role="img" aria-label="Grafik penjualan harian"><g>${grid}${labels}</g>${area ? `<polygon class="sales-area" points="${area}"></polygon>` : ''}${previous.length > 1 ? `<polyline class="sales-line previous" points="${points(previous)}"></polyline>` : ''}${current.length > 1 ? `<polyline class="sales-line" points="${points(current)}"></polyline>` : ''}${previousCircles}${circles}</svg>`;
    };
    const renderSalesInsights = (currentRows, totals) => {
        const peak = currentRows.reduce((best, row) => !best || row.sales > best.sales ? row : best, null);
        const low = currentRows.reduce((best, row) => !best || row.sales < best.sales ? row : best, null);
        const negativeDays = currentRows.filter(row => row.profit < 0).length;
        $('anSalesTrendInsights').innerHTML = [
            `<div class="an-sales-trend-insight"><div class="an-sales-trend-insight-label">Hari penjualan tertinggi</div><div class="an-sales-trend-insight-value">${peak ? money(peak.sales) : '—'}</div><div class="an-sales-trend-insight-meta"><span>${peak ? esc(salesPeriodLabel(peak.date)) : 'Belum ada data'}</span><strong>${peak ? `${peak.orders.toLocaleString('id-ID')} order` : '—'}</strong></div></div>`,
            `<div class="an-sales-trend-insight"><div class="an-sales-trend-insight-label">Rata-rata per hari</div><div class="an-sales-trend-insight-value">${currentRows.length ? money(totals.sales / currentRows.length) : '—'}</div><div class="an-sales-trend-insight-meta"><span>${currentRows.length.toLocaleString('id-ID')} hari berdata</span><strong>${currentRows.length ? money(totals.profit / currentRows.length) : '—'} profit</strong></div></div>`,
            `<div class="an-sales-trend-insight ${negativeDays ? 'an-sales-trend-insight--alert' : ''}"><div class="an-sales-trend-insight-label">${negativeDays ? 'Perlu perhatian' : 'Profitabilitas'}</div><div class="an-sales-trend-insight-value">${negativeDays ? `${negativeDays} hari profit negatif` : 'Tidak ada hari negatif'}</div><div class="an-sales-trend-insight-meta"><span>Hari terendah</span><strong>${low ? money(low.sales) : '—'}</strong></div></div>`,
        ].join('');
    };
    function renderSalesPane() {
        const current = salesSnapshot(summary?.current);
        const previous = salesSnapshot(summary?.previous);
        const currentRows = (summary?.daily || []).map(salesDailyRow).sort((a, b) => a.date.localeCompare(b.date));
        const previousRows = (summary?.previous_daily || []).map(salesDailyRow).sort((a, b) => a.date.localeCompare(b.date));
        const labels = [
            ['anSalesNetSales', current.netSales, previous.netSales, false, money],
            ['anSalesOrders', current.orders, previous.orders, false, value => value.toLocaleString('id-ID')],
            ['anSalesPayout', current.payout, previous.payout, false, money],
            ['anSalesQty', current.qty, previous.qty, false, value => value.toLocaleString('id-ID')],
            ['anSalesProfit', current.profit, previous.profit, false, money],
            ['anSalesRefund', current.refund, previous.refund, true, money],
        ];
        labels.forEach(([id, value, baseline, lowerIsBetter, formatter]) => {
            const element = $(id);
            const note = $(`${id}Note`);
            if (!element) return;
            element.textContent = formatter(value);
            const delta = salesDelta(value, baseline, lowerIsBetter);
            if (note) { note.textContent = `${delta.label} vs pembanding`; note.className = `an-sales-kpi-note ${delta.tone}`; }
        });
        $('anSalesPeriod').textContent = `${from()} — ${to()}`;
        $('anSalesCompareLabel').textContent = `vs ${$('anCompare')?.selectedOptions?.[0]?.textContent || 'periode pembanding'}`;
        const trend = renderSalesTrendSummary(currentRows, previousRows);
        renderSalesChart(currentRows, previousRows);
        renderSalesInsights(currentRows, trend.currentTotals);
        const number = value => Number(value || 0).toLocaleString('id-ID');
        const tableCell = (value, baseline, formatter = money, lowerIsBetter = false, valueClass = '') => {
            const delta = salesDelta(value, baseline, lowerIsBetter);
            return `<span class="an-sales-table-value ${valueClass}">${formatter(value)}</span><span class="an-sales-table-delta ${delta.tone}">${delta.label}</span>`;
        };
        const orderedRows = currentRows.slice().reverse();
        const maxSalesDailyRows = 31;
        const displayedRows = orderedRows.slice(0, maxSalesDailyRows);
        const omittedRows = Math.max(0, orderedRows.length - displayedRows.length);
        $('anSalesDailyBody').innerHTML = currentRows.length ? displayedRows.map((row, displayIndex) => {
            const reverseIndex = displayIndex;
            const index = currentRows.length - reverseIndex - 1;
            const previousRow = previousRows[index] || {};
            return `<tr><td>${esc(salesPeriodLabel(row.date))}</td><td>${tableCell(row.orders, previousRow.orders, number)}</td><td>${tableCell(row.qty, previousRow.qty, number)}</td><td>${tableCell(row.avgOrder, previousRow.avgOrder, money)}</td><td>${tableCell(row.grossSales, previousRow.grossSales)}</td><td>${tableCell(row.netSales, previousRow.netSales)}</td><td>${tableCell(row.paid, previousRow.paid)}</td><td>${tableCell(row.payout, previousRow.payout)}</td><td>${tableCell(row.cogs, previousRow.cogs, money, true)}</td><td>${tableCell(row.grossProfit, previousRow.grossProfit, money, false, row.grossProfit < 0 ? 'is-negative' : 'is-positive')}</td><td>${tableCell(row.adCost, previousRow.adCost, money, true)}</td><td>${tableCell(row.profit, previousRow.profit, money, false, row.profit < 0 ? 'is-negative' : 'is-positive')}</td></tr>`;
        }).join('') : `<tr><td colspan="12">${stateMarkup('empty', 'Belum ada rincian harian', 'Belum ada rincian harian pada periode ini.')}</td></tr>`;
        const dailyNote = $('anSalesDailyNote');
        if (dailyNote) {
            dailyNote.textContent = currentRows.length
                ? (omittedRows ? `Menampilkan ${displayedRows.length} hari terbaru dari ${orderedRows.length} hari pada periode.` : `Menampilkan ${displayedRows.length} hari pada periode aktif.`)
                : '';
        }
    }

    function render() {
        const current = summary?.current || {};
        renderKpis();
        renderFinancePulse();
        renderEnterprise();
        renderChart(summary?.daily || [], summary?.previous_daily || []);
        renderSalesPane();
        renderFunnel();
        renderStores();
        renderCosts();
        $('anControlPeriod').textContent = `${from()} — ${to()}`;
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
        stores = await api('/api/marketplace/stores?active_only=0').catch(() => []);
        storesLoaded = true;
        fillStores();
        return stores;
    }
    async function loadProducts() {
        if (productsLoaded || productsLoading || !dateRangeReady()) return;
        const requestToken = ++productRequestToken;
        productAbortController?.abort();
        const controller = new AbortController();
        productAbortController = controller;
        productsLoading = true;
        await loadStores();
        if (requestToken !== productRequestToken) return;
        $('bestProductBody').innerHTML = `<tr><td colspan="10">${stateMarkup('loading', 'Memuat detail produk…')}</td></tr>`;
        try {
            const params = new URLSearchParams({ date_from: from(), date_to: to() });
            if (selectedPlatform()) params.set('platform', selectedPlatform());
            if (selectedStore()) params.set('store_id', selectedStore());
            params.set('_ts', Date.now().toString());
            const result = await api('/api/marketplace/analytics-products?' + params.toString(), { cache: 'no-store', signal: controller.signal });
            if (requestToken !== productRequestToken) return;
            productData = result?.data || [];
            productsLoaded = true;
            renderProductSummary(productData);
        } catch (e) {
            if (requestToken !== productRequestToken || isAbortError(e)) return;
            $('bestProductBody').innerHTML = `<tr><td colspan="9">${stateMarkup('error', 'Detail produk gagal dimuat')}</td></tr>`;
            if ($('anProductSnapshotList')) $('anProductSnapshotList').innerHTML = stateMarkup('error', 'Ringkasan produk gagal dimuat');
        } finally {
            if (requestToken === productRequestToken) {
                productsLoading = false;
                productAbortController = null;
            }
        }
    }
    async function load() {
        if (!dateRangeReady()) {
            cancelPendingAnalytics();
            setLoading('Pilih rentang tanggal lengkap terlebih dahulu…');
            return;
        }
        const loadToken = ++analyticsLoadToken;
        analyticsAbortController?.abort();
        const controller = new AbortController();
        analyticsAbortController = controller;
        const cashModalWasOpen = $('cashOrdersModal').classList.contains('is-open');
        const revenueTabWasActive = document.querySelector('[data-an-tab="revenue"]')?.classList.contains('active');
        const feesTabWasActive = document.querySelector('[data-an-tab="fees"]')?.classList.contains('active');
        if (feesTabWasActive) cashFocus = 'fees';
        else if (revenueTabWasActive) cashFocus = 'combined';
        if (!cashModalWasOpen) closeCashOrders();
        closeReturnOrders();
        cashPage = 1;
        cashPayload = null;
        cashTabSummaries = {};
        cashTabSummaryToken++;
        returnPage = 1;
        returnPayload = null;
        cohortRequestToken++;
        cohortAbortController?.abort();
        cohortOptionsRequestToken++;
        cohortOptionsAbortController?.abort();
        cohortPayload = null;
        cohortLoading = false;
        cohortOptionsKey = null;
        productRequestToken++;
        productAbortController?.abort();
        if (cashModalWasOpen || revenueTabWasActive || feesTabWasActive) {
            loadCashOrders();
            loadCashTabSummaries(cashSettlementTabs);
        }
        setLoading('Menyiapkan KPI utama…');
        $('anRefresh').disabled = true;
        $('anRefresh').textContent = 'Memuat…';
        document.querySelector('.an-shell')?.setAttribute('aria-busy', 'true');
        productsLoaded = false;
        productsLoading = false;
        productData = [];
        orders = [];
        try {
            const params = new URLSearchParams({ date_from: from(), date_to: to(), compare_mode: $('anCompare').value, _ts: Date.now().toString() });
            if (selectedPlatform()) params.set('platform', selectedPlatform());
            if (selectedStore()) params.set('store_id', selectedStore());
            try {
                const kpiPayload = await api('/api/marketplace/analytics-kpis?' + params.toString(), { cache: 'no-store', signal: controller.signal });
                if (loadToken !== analyticsLoadToken) return;
                summary = kpiPayload;
                renderEnterprise();
                setLoading('KPI utama siap · memuat detail analytics…');
            } catch (kpiError) {
                console.warn('Analytics KPI fast load failed', kpiError);
            }
            const activeView = document.querySelector('[data-an-tab].active')?.dataset.anTab || 'overview';
            const storeSummaryPromise = ['overview', 'stores'].includes(activeView)
                ? api('/api/marketplace/analytics-stores?' + params.toString(), { cache: 'no-store', signal: controller.signal })
                    .then(payload => ({ ok: true, payload }))
                    .catch(error => ({ ok: false, error }))
                : Promise.resolve({ ok: true, payload: null });
            const detailsPromise = Promise.all([
                loadStores(),
                api('/api/marketplace/analytics-summary?' + params.toString(), { cache: 'no-store', signal: controller.signal })
                    .then(payload => ({ ok: true, payload }))
                    .catch(error => ({ ok: false, error })),
                storeSummaryPromise,
            ]);
            const [storeRows, summaryResult, storeSummaryResult] = await detailsPromise;
            if (loadToken !== analyticsLoadToken) return;
            if (!summaryResult.ok && !storeSummaryResult.ok) throw summaryResult.error;
            const summaryPayload = summaryResult.ok ? summaryResult.payload : { stores: [] };
            const fallbackStores = storeSummaryResult.ok ? (storeSummaryResult.payload?.stores || []) : [];
            if (!Array.isArray(summaryPayload.stores) || summaryPayload.stores.length === 0) summaryPayload.stores = fallbackStores;
            stores = storeRows;
            summary = summaryPayload;
            fillStores();
            render();
            if (document.querySelector('[data-an-tab="stores"]')?.classList.contains('active')) loadProducts();
            $('bestProductBody').innerHTML = `<tr><td colspan="9">${stateMarkup('empty', 'Menunggu data produk', 'Buka tab Produk untuk memuat detail.')}</td></tr>`;
            if (document.querySelector('[data-an-tab="cohort"]')?.classList.contains('active')) await loadCohort();
        } catch (e) {
            if (loadToken !== analyticsLoadToken || isAbortError(e)) return;
            console.error('Analytics summary load failed', e);
            summary = null;
            $('storeBody').innerHTML = `<tr><td colspan="8">${stateMarkup('error', 'Tidak dapat memuat ringkasan analytics')}</td></tr>`;
            $('overviewStoreLedgerBody').innerHTML = `<tr><td colspan="8">${stateMarkup('error', 'Tidak dapat memuat performa toko')}</td></tr>`;
            $('bestProductBody').innerHTML = `<tr><td colspan="9">${stateMarkup('error', 'Tidak dapat memuat data analytics')}</td></tr>`;
        } finally {
            if (loadToken !== analyticsLoadToken) return;
            $('anRefresh').disabled = false;
            $('anRefresh').textContent = '↻ Refresh';
            document.querySelector('.an-shell')?.removeAttribute('aria-busy');
        }
    }
    const analyticsSubtabDefinitions = {
        overview: { label: 'Detail Ikhtisar', items: [{ key: 'summary', label: 'Ringkasan', icon: 'bi-grid-1x2', target: 'anPulseGrid' }] },
        sales: { label: 'Detail Penjualan', items: [{ key: 'trend', label: 'Tren penjualan', icon: 'bi-graph-up-arrow', target: 'anSalesTrendChart' }, { key: 'daily', label: 'Rincian harian', icon: 'bi-table', target: 'anSalesDailyBody' }] },
        revenue: { label: 'Detail Omzet, Pencairan & Fee', items: [{ key: 'snapshot', label: 'Snapshot', icon: 'bi-grid-1x2', target: 'revenueOrdersSummary' }, { key: 'ledger', label: 'Detail transaksi', icon: 'bi-receipt', target: 'revenueOrdersBody' }] },
        fees: { label: 'Detail Fee Marketplace', items: [{ key: 'ledger', label: 'Order cair', icon: 'bi-receipt', target: 'feeOrdersBody' }] },
        operations: { label: 'Detail Operasional', items: [{ key: 'funnel', label: 'Order funnel', icon: 'bi-filter', target: 'salesFunnel' }, { key: 'trend', label: 'Tren harian', icon: 'bi-graph-up-arrow', target: 'revenueChart' }] },
        stores: { label: 'Detail Toko & Biaya', items: [{ key: 'portfolio', label: 'Portfolio', icon: 'bi-shop-window', target: 'anStorePulse' }, { key: 'ledger', label: 'Store ledger', icon: 'bi-table', target: 'storeBody' }, { key: 'products', label: 'Produk', icon: 'bi-box-seam', target: 'anProductSnapshotList' }] },
        products: { label: 'Detail Produk', items: [{ key: 'portfolio', label: 'Portfolio', icon: 'bi-grid-1x2', target: 'anProductPulse' }, { key: 'ledger', label: 'Kinerja produk', icon: 'bi-table', target: 'bestProductBody' }, { key: 'attention', label: 'Perlu perhatian', icon: 'bi-exclamation-circle', target: 'worstProductBody' }] },
        cohort: { label: 'Detail Cohort', items: [{ key: 'summary', label: 'Ringkasan', icon: 'bi-bar-chart-line', target: 'anCohortKpis' }, { key: 'matrix', label: 'Matriks', icon: 'bi-grid-3x3-gap', target: 'anCohortTable' }] },
    };
    const activeAnalyticsSubtabs = {};
    function activateSubtab(key, { scroll = false } = {}) {
        const activeTab = document.querySelector('[data-an-tab].is-active')?.dataset.anTab || 'overview';
        const definition = analyticsSubtabDefinitions[activeTab] || analyticsSubtabDefinitions.overview;
        const selected = definition.items.some(item => item.key === key) ? key : definition.items[0].key;
        activeAnalyticsSubtabs[activeTab] = selected;
        document.querySelectorAll('[data-an-subtab]').forEach(button => {
            const active = button.dataset.anSubtab === selected;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
            button.setAttribute('tabindex', active ? '0' : '-1');
        });
        const selectedLabel = definition.items.find(item => item.key === selected)?.label || '';
        $('analyticsTabPanel')?.setAttribute('aria-label', `${definition.label} · ${selectedLabel}`);
        if (!scroll) return;
        const item = definition.items.find(candidate => candidate.key === selected);
        const target = item?.target ? $(item.target) : null;
        const section = target?.closest('section, .an-card, .an-enterprise-card, .an-grid-secondary') || target;
        section?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    function renderSubtabs(name) {
        const navigation = $('analyticsSubtabs');
        if (!navigation) return;
        const definition = analyticsSubtabDefinitions[name] || analyticsSubtabDefinitions.overview;
        navigation.setAttribute('aria-label', definition.label);
        navigation.innerHTML = definition.items.map((item, index) => `<button class="ds-tab-system-button" type="button" role="tab" data-an-subtab="${item.key}" id="analytics-subtab-${name}-${item.key}" aria-selected="${index === 0 ? 'true' : 'false'}" aria-controls="analyticsTabPanel" tabindex="${index === 0 ? '0' : '-1'}"><i class="bi ${item.icon}" aria-hidden="true"></i>${item.label}</button>`).join('');
        navigation.querySelectorAll('[data-an-subtab]').forEach(button => {
            button.addEventListener('click', () => activateSubtab(button.dataset.anSubtab, { scroll: true }));
            button.addEventListener('keydown', event => handleTabKeydown(event, '[data-an-subtab]'));
        });
        activateSubtab(activeAnalyticsSubtabs[name] || definition.items[0].key);
    }
    function handleTabKeydown(event, selector) {
        const keys = ['ArrowLeft', 'ArrowRight', 'Home', 'End'];
        if (!keys.includes(event.key)) return;
        const tabs = [...document.querySelectorAll(selector)];
        const currentIndex = tabs.indexOf(event.currentTarget);
        if (currentIndex < 0) return;
        const nextIndex = event.key === 'Home' ? 0 : (event.key === 'End' ? tabs.length - 1 : (currentIndex + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length);
        event.preventDefault();
        tabs[nextIndex]?.focus();
        tabs[nextIndex]?.click();
    }
    function activateTab(name) {
        document.querySelectorAll('[data-an-tab]').forEach(button => {
            const active = button.dataset.anTab === name;
            button.classList.toggle('active', active);
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
            button.setAttribute('tabindex', active ? '0' : '-1');
        });
        document.querySelectorAll('[data-an-pane]').forEach(pane => pane.classList.toggle('is-hidden', pane.dataset.anPane !== name));
        $('analyticsTabPanel')?.setAttribute('aria-label', (analyticsSubtabDefinitions[name] || analyticsSubtabDefinitions.overview).label);
        renderSubtabs(name);
        const params = new URLSearchParams(location.search);
        params.set('view', name);
        history.replaceState(null, '', location.pathname + '?' + params.toString());
    }
    document.querySelectorAll('[data-an-tab]').forEach(button => {
        button.addEventListener('click', () => {
        activateTab(button.dataset.anTab);
        if (button.dataset.anTab === 'products' || button.dataset.anTab === 'stores') loadProducts();
        if (button.dataset.anTab === 'cohort') loadCohort();
        if (button.dataset.anTab === 'revenue') {
            const needsReload = cashFocus !== 'combined' || !cashPayload;
            cashFocus = 'combined';
            if (needsReload && !cashLoading) { cashPage = 1; cashPayload = null; loadCashOrders(); }
            loadCashTabSummaries(cashSettlementTabs);
        }
        if (button.dataset.anTab === 'fees') {
            const needsReload = cashFocus !== 'fees' || cashSettlement !== 'settled' || !cashPayload;
            cashFocus = 'fees';
            cashSettlement = 'settled';
            if (needsReload && !cashLoading) { cashPage = 1; cashPayload = null; loadCashOrders(); }
            loadCashTabSummaries(cashSettlementTabs);
        }
        });
        button.addEventListener('keydown', event => handleTabKeydown(event, '[data-an-tab]'));
    });
    const syncUrl = () => { const params = new URLSearchParams({date_from:from(),date_to:to(),compare_mode:$('anCompare').value}); if (selectedPlatform()) params.set('platform', selectedPlatform()); if (selectedStore()) params.set('store_id', selectedStore()); const activeTab = document.querySelector('[data-an-tab].active')?.dataset.anTab; if (activeTab) params.set('view', activeTab); history.replaceState(null,'',location.pathname+'?'+params.toString()); };
    const refreshFromFilter = () => { syncUrl(); if (!dateRangeReady()) { cancelPendingAnalytics(); setLoading('Pilih tanggal akhir untuk menerapkan filter…'); return; } load(); };
    $('anRefresh').addEventListener('click',load); $('anPlatform').addEventListener('change',()=>{ $('anStore').value = ''; fillStores(); refreshFromFilter(); }); $('anStore').addEventListener('change',refreshFromFilter); $('anCompare').addEventListener('change',refreshFromFilter);
    document.querySelectorAll('[data-open-products-tab]').forEach(button => button.addEventListener('click', () => { activateTab('products'); loadProducts(); }));
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
    document.querySelectorAll('[data-revenue-settlement]').forEach(button => button.addEventListener('click', () => {
        const needsReload = cashFocus !== 'combined' || cashSettlement !== button.dataset.revenueSettlement || !cashPayload;
        cashFocus = 'combined';
        if (!needsReload) return;
        setCashSettlementTab(button.dataset.revenueSettlement);
        cashPage = 1;
        cashPayload = null;
        loadCashOrders();
    }));
    document.querySelectorAll('[data-revenue-settlement]').forEach(button => button.addEventListener('keydown', event => handleTabKeydown(event, '[data-revenue-settlement]')));
    document.querySelectorAll('[data-cash-close]').forEach(element => element.addEventListener('click', closeCashOrders));
    document.querySelectorAll('[data-return-close]').forEach(element => element.addEventListener('click', closeReturnOrders));
    $('cashOrdersPrev').addEventListener('click', () => { if (cashPage > 1) { cashPage -= 1; loadCashOrders(); } });
    $('cashOrdersNext').addEventListener('click', () => { const lastPage = Number(cashPayload?.meta?.last_page || 1); if (cashPage < lastPage) { cashPage += 1; loadCashOrders(); } });
    $('revenueOrdersPrev').addEventListener('click', () => { if (cashPage > 1) { cashPage -= 1; loadCashOrders(); } });
    $('revenueOrdersNext').addEventListener('click', () => { const lastPage = Number(cashPayload?.meta?.last_page || 1); if (cashPage < lastPage) { cashPage += 1; loadCashOrders(); } });
    $('feeOrdersPrev').addEventListener('click', () => { if (cashPage > 1) { cashPage -= 1; loadCashOrders(); } });
    $('feeOrdersNext').addEventListener('click', () => { const lastPage = Number(cashPayload?.meta?.last_page || 1); if (cashPage < lastPage) { cashPage += 1; loadCashOrders(); } });
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
    $('anProductHealth').addEventListener('change', () => renderProductSummary(productData));
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
                cancelPendingAnalytics();
                setLoading('Pilih tanggal akhir untuk menerapkan filter…');
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
    $('anPlatform').value = initialPlatform || '';
    $('anStore').value = initialStore || '';
    const initialParams = new URLSearchParams(location.search);
    const initialView = initialParams.get('view') || initialParams.get('tab');
    const normalizedInitialView = initialView === 'summary' ? 'overview' : initialView;
    if (normalizedInitialView === 'revenue') {
        cashFocus = 'combined';
        cashSettlement = 'all';
    }
    if (normalizedInitialView === 'fees') {
        cashFocus = 'fees';
        cashSettlement = 'settled';
    }
    if (['overview', 'sales', 'revenue', 'fees', 'operations', 'stores', 'products', 'cohort'].includes(normalizedInitialView)) activateTab(normalizedInitialView);
    load();
    if (normalizedInitialView === 'products') loadProducts();
})();
</script>
@endpush
