@extends('layouts.app')

@section('title', 'Analisis Iklan Shopee')

@push('head')
@include('dashboard.partials._styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

/* ─────────────────────────────────────────────────────────────────────────────
   MODERN DASHBOARD UI UPGRADE (Rich Aesthetics, Glassmorphism, Micro-animations)
───────────────────────────────────────────────────────────────────────────── */
body {
    font-family: 'Inter', sans-serif !important;
}

.spin-icon { animation: spin 1.5s linear infinite; }
@keyframes spin { 100% { transform: rotate(360deg); } }

:root {
    --card-bg: #ffffff;
    --card-border: #e2e8f0;
    --card-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
    --hero-bg: #f8fafc;
    --card-hover-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
    --dsh-accent-hover: #1d4ed8;
}

body[data-theme="dark"] {
    --card-bg: #1e293b;
    --card-border: #334155;
    --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
    --hero-bg: #0f172a;
    --card-hover-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
}

.dpanel {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    box-shadow: 0 12px 28px rgba(15,23,42,.05);
    border-radius: 18px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.dpanel:hover {
    transform: translateY(-2px);
    box-shadow: 0 16px 34px rgba(15,23,42,.08);
}

.dash-tabs {
    display: inline-flex;
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    border-radius: 10px;
    padding: .35rem;
    gap: .35rem;
    margin-bottom: 1rem;
}

.dash-tab {
    background: transparent;
    border: none;
    padding: .5rem 1.25rem;
    font-size: .85rem;
    font-weight: 600;
    color: var(--dsh-muted);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.dash-tab:hover {
    color: var(--text, #0f172a);
    background: rgba(148,163,184,.1);
}

.dash-tab.active {
    background: var(--dsh-accent);
    color: #fff;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.ads-shell{
    position:relative;
}

.ads-hero{
    position:sticky;
    top:.5rem;
    z-index:320;
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:1rem;
    flex-wrap:wrap;
    margin-bottom:.85rem;
    padding:1rem 1.05rem;
    border-radius:20px;
    border:1px solid rgba(148,163,184,.14);
    background:
        radial-gradient(circle at top right, rgba(96,165,250,.28), transparent 30%),
        radial-gradient(circle at bottom left, rgba(16,185,129,.16), transparent 28%),
        linear-gradient(135deg, #0f172a 0%, #111827 45%, #1d4ed8 135%);
    box-shadow:0 18px 50px rgba(15,23,42,.16);
    overflow:hidden;
}

.ads-hero::before,
.ads-hero::after{
    content:'';
    position:absolute;
    border-radius:999px;
    pointer-events:none;
    opacity:.35;
    filter:blur(2px);
}

.ads-hero::before{
    width:180px;
    height:180px;
    right:-60px;
    top:-80px;
    background:rgba(59,130,246,.28);
}

.ads-hero::after{
    width:220px;
    height:220px;
    left:-90px;
    bottom:-130px;
    background:rgba(16,185,129,.18);
}

.ads-hero > *{
    position:relative;
    z-index:1;
}

.ads-eyebrow{
    display:inline-flex;
    align-items:center;
    gap:.35rem;
    margin-bottom:.35rem;
    font-size:.66rem;
    font-weight:900;
    letter-spacing:.08em;
    text-transform:uppercase;
    color:rgba(255,255,255,.72);
}

.ads-hero .title{
    color:#fff;
    font-size:1.2rem;
    letter-spacing:-0.04em;
    margin:0;
}

.ads-hero .sub{
    color:rgba(226,232,240,.8);
    max-width:48rem;
}

.ads-hero-badges{
    display:flex;
    flex-wrap:wrap;
    gap:.4rem;
    margin-top:.75rem;
}

.ads-chip{
    display:inline-flex;
    align-items:center;
    gap:.35rem;
    padding:.34rem .6rem;
    border-radius:999px;
    background:rgba(255,255,255,.08);
    border:1px solid rgba(255,255,255,.12);
    color:rgba(255,255,255,.92);
    font-size:.72rem;
    font-weight:800;
    white-space:nowrap;
}

.ads-hero-meta{
    font-size:.72rem;
    color:rgba(226,232,240,.8);
    text-align:right;
    margin-bottom:.35rem;
    font-weight:500;
}

.ads-hero-error{
    color:#fecaca;
    font-weight:700;
    margin-bottom:.25rem;
}

.ads-hero .controls{
    justify-content:flex-end;
    gap:.45rem;
}

.ads-hero .role-chip{
    background:rgba(255,255,255,.08);
    border:1px solid rgba(255,255,255,.12)!important;
    color:rgba(255,255,255,.92)!important;
    border-radius:999px!important;
    box-shadow:none!important;
}

.ads-hero .btn-pill{
    border-radius:999px!important;
    padding-inline:.82rem!important;
    font-size:.78rem!important;
    font-weight:800!important;
}

.ads-hero .btn-ship-outline{
    background:rgba(255,255,255,.06)!important;
    border-color:rgba(255,255,255,.18)!important;
    color:#fff!important;
    box-shadow:none!important;
}

.ads-hero .btn-ship-outline:hover{
    background:rgba(255,255,255,.14)!important;
    border-color:rgba(255,255,255,.26)!important;
    color:#fff!important;
}

body[data-theme="dark"] .ads-hero{
    background:
        radial-gradient(circle at top right, rgba(59,130,246,.22), transparent 30%),
        radial-gradient(circle at bottom left, rgba(16,185,129,.12), transparent 28%),
        linear-gradient(135deg, rgba(15,23,42,.98) 0%, rgba(30,41,59,.94) 46%, rgba(30,64,175,.88) 135%);
}

.ads-tabs-wrap{
    margin:0 0 1rem;
    position:relative;
}

.ads-tabs-wrap::before{
    content:'';
    position:absolute;
    left:.9rem;
    right:.9rem;
    top:50%;
    height:1px;
    background:linear-gradient(90deg, transparent, rgba(148,163,184,.18), transparent);
    transform:translateY(-50%);
    pointer-events:none;
}

.dash-filter{
    border-radius:20px;
    padding:1rem 1rem .95rem;
    background:var(--card-bg);
    border:1px solid rgba(148,163,184,.16);
    box-shadow:0 12px 28px rgba(15,23,42,.05);
}

.filter-item label{
    text-transform:none;
    letter-spacing:0;
    font-size:.66rem;
    font-weight:900;
    color:#334155;
}

.filter-item input,
.filter-item select{
    height:42px;
    border-radius:14px;
    border:1px solid rgba(148,163,184,.22);
    background:var(--card-bg);
    color:var(--text, #0f172a);
}

body[data-theme="dark"] .filter-item label{
    color:#cbd5e1;
}

body[data-theme="dark"] .filter-item input,
body[data-theme="dark"] .filter-item select{
    background:rgba(15,23,42,.72);
    border-color:rgba(255,255,255,.12);
    color:#e2e8f0;
}

.ads-surface{
    border-radius:20px;
    border:1px solid rgba(148,163,184,.16);
    background:var(--card-bg);
    box-shadow:0 12px 28px rgba(15,23,42,.05);
    overflow:hidden;
}

body[data-theme="dark"] .ads-surface{
    background:var(--card-bg);
    border-color:rgba(51,65,85,.85);
    box-shadow:none;
}

.ads-panel-head{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:1rem;
    flex-wrap:wrap;
    padding:1rem 1rem .95rem;
    border-bottom:1px solid rgba(148,163,184,.16);
    background:linear-gradient(180deg, rgba(248,250,252,.95), rgba(255,255,255,.98));
}

body[data-theme="dark"] .ads-panel-head{
    background:linear-gradient(180deg, rgba(15,23,42,.96), rgba(15,23,42,.88));
    border-bottom-color:rgba(51,65,85,.9);
}

.ads-panel-title{
    margin:.08rem 0 0;
    font-size:1.02rem;
    font-weight:900;
    letter-spacing:-0.04em;
    color:var(--text);
}

.ads-panel-note{
    font-size:.76rem;
    color:var(--dsh-muted);
    padding:.38rem .68rem;
    border-radius:999px;
    background:rgba(148,163,184,.12);
    border:1px solid rgba(148,163,184,.22);
    box-shadow:inset 0 1px 0 rgba(255,255,255,.42);
}

body[data-theme="dark"] .ads-panel-note{
    background:rgba(30,41,59,.82);
    border-color:rgba(51,65,85,.92);
    color:#cbd5e1;
    box-shadow:none;
}

.dash-hero {
    background: var(--hero-bg);
    border: 1px solid var(--card-border);
    border-radius: 16px;
    padding: 1.5rem 2rem;
    margin-bottom: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    z-index: 20;
}

.dash-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(37,99,235,0.1) 0%, rgba(255,255,255,0) 70%);
    border-radius: 50%;
    pointer-events: none;
}

.dash-hero h1 { font-size: 1.5rem; font-weight: 800; margin: 0; letter-spacing: -0.025em; }
.dash-hero .sub { font-size: .85rem; color: var(--dsh-muted); margin-top: .4rem; font-weight: 500; }
.role-chip { 
    display: inline-flex; align-items: center; gap: .4rem; padding: .4rem .75rem; 
    border-radius: 8px; font-size: .75rem; font-weight: 600; 
    background: var(--card-bg);
    border: 1px solid var(--card-border); color: var(--text); 
}

.live-btn {
    cursor: default;
    transition: all 0.3s ease;
    user-select: none;
}
.live-on {
    background: rgba(22, 163, 74, 0.15) !important;
    color: #15803d !important;
    border: 1px solid rgba(22, 163, 74, 0.3) !important;
    box-shadow: 0 0 12px rgba(22, 163, 74, 0.2);
}
.live-off {
    background: rgba(100, 116, 139, 0.1) !important;
    color: var(--dsh-muted) !important;
    border: 1px solid var(--card-border) !important;
}
body[data-theme="dark"] .live-on { color: #4ade80 !important; }

body[data-theme="dark"] .dash-tab.active {
    background: var(--text);
    color: var(--bg);
    box-shadow: 0 4px 12px rgba(255, 255, 255, 0.15);
}
.tab-pane { display: none; opacity: 0; transition: opacity 0.3s ease; }
.tab-pane.active { display: block; opacity: 1; animation: fadeIn 0.4s ease-out; }

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Filter Container */
.dash-filter {
    background: var(--card-bg);
    border: 1px solid rgba(148,163,184,.16);
    border-radius: 20px;
    padding: 1rem 1rem .95rem;
    display: flex;
    gap: 1rem;
    align-items: flex-end;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
    box-shadow: 0 12px 28px rgba(15,23,42,.05);
}
.filter-item { flex: 1; min-width: 180px; }
.filter-item label { font-size: .66rem; font-weight: 900; color: #334155; margin-bottom: .35rem; display: block; text-transform: none; letter-spacing: 0; }
.filter-item input, .filter-item select {
    width: 100%; font-size: .84rem; padding: .55rem .85rem; border-radius: 14px;
    border: 1px solid rgba(148,163,184,.22); background: var(--card-bg); color: var(--text, #0f172a);
    transition: all 0.2s ease; font-weight: 600;
}
.filter-item input:focus, .filter-item select:focus {
    outline: none;
    border-color: var(--dsh-accent);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}
body[data-theme="dark"] .filter-item label { color:#cbd5e1; }
body[data-theme="dark"] .filter-item input, body[data-theme="dark"] .filter-item select {
    background: rgba(15, 23, 42, 0.72); border-color: rgba(255,255,255,.12); color: #e2e8f0;
}

/* Tabel di dalam dpanel */
.dpanel-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.dpanel-table thead th {
    background: rgba(241, 245, 249, 0.6);
    backdrop-filter: blur(4px);
    border-bottom: 1px solid var(--card-border);
    font-size: .75rem;
    font-weight: 700;
    color: var(--dsh-muted);
    padding: .75rem 1rem;
    text-align: left;
    white-space: nowrap;
    letter-spacing: 0.02em;
}
body[data-theme="dark"] .dpanel-table thead th { background: rgba(30, 41, 59, 0.6); }
.dpanel-table tbody td {
    padding: .85rem 1rem;
    font-size: .85rem;
    border-bottom: 1px solid var(--card-border);
    color: var(--text, #0f172a);
    vertical-align: middle;
    transition: background 0.2s ease;
}
.dpanel-table tbody tr { transition: transform 0.2s ease, box-shadow 0.2s ease; }
.dpanel-table tbody tr:hover td { background: rgba(241, 245, 249, 0.8); }
body[data-theme="dark"] .dpanel-table tbody tr:hover td { background: rgba(51, 65, 85, 0.4); }

/* Periode Bar (Pill) */
.period-bar { display: flex; gap: .75rem; align-items: center; }
.range-pill {
    display: inline-flex; align-items: center; justify-content: space-between; gap: .75rem;
    border: 1px solid var(--card-border); background: rgba(148,163,184,.06); padding: .5rem 1rem; border-radius: 14px;
    cursor: pointer; font-size: .85rem; color: var(--text, #0f172a); font-weight: 650;
    transition: all 0.25s ease; box-shadow: 0 1px 2px rgba(0,0,0,0.02);
}
body[data-theme="dark"] .range-pill { color: #f8fafc; background: rgba(30, 41, 59, 0.6); }
.range-pill:hover { 
    background: rgba(241, 245, 249, 1); 
    border-color: var(--dsh-accent);
}
body[data-theme="dark"] .range-pill:hover { background: rgba(51, 65, 85, 0.8); }

/* Kpi Cards Upgrade */
.dash-tabs-modern {
    display: inline-flex;
    background: linear-gradient(180deg, rgba(248,250,252,.96), rgba(241,245,249,.92));
    padding: .45rem;
    border-radius: 999px;
    gap: .35rem;
    border:1px solid rgba(148,163,184,.18);
    box-shadow:0 12px 28px rgba(15,23,42,.06);
}
body[data-theme="dark"] .dash-tabs-modern { background: linear-gradient(180deg, rgba(15,23,42,.96), rgba(30,41,59,.92)); border-color:rgba(51,65,85,.85); box-shadow:none; }
.dash-tab-m {
    border: none; background: transparent; padding: .72rem 1rem; border-radius: 999px;
    font-weight: 900; font-size: .8rem; color: var(--dsh-muted); cursor: pointer;
    transition: all .2s ease; display: flex; align-items: center; gap: .5rem; white-space: nowrap;
}
.dash-tab-m:hover { color: #0f172a; background: rgba(255,255,255,.82); }
.dash-tab-m.active {
    background: #0f172a; color: #fff;
    box-shadow: 0 10px 20px rgba(15,23,42,.18);
}
body[data-theme="dark"] .dash-tab-m{ color:#94a3b8; }
body[data-theme="dark"] .dash-tab-m:hover{ color:#e2e8f0; background:rgba(255,255,255,.06); }
body[data-theme="dark"] .dash-tab-m.active{ background:#1d4ed8; color:#fff; box-shadow:none; }

.dash-tab-sm {
    border: none; background: transparent; padding: .36rem .72rem; border-radius: 999px;
    font-weight: 800; font-size: .72rem; color: var(--dsh-muted); cursor: pointer;
    transition: all .2s ease; display: flex; align-items: center; gap: .35rem; white-space: nowrap;
}
.dash-tab-sm:hover { color: #0f172a; background: rgba(255,255,255,.7); }
.dash-tab-sm.active {
    background: var(--card-bg); color: var(--text);
    box-shadow: 0 4px 10px rgba(15,23,42,.08);
}
body[data-theme="dark"] .dash-tab-sm.active { background: var(--card-bg); }

.ads-kpi-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:.75rem;
    margin-bottom:.95rem;
}

.ads-kpi{
    position:relative;
    display:flex;
    flex-direction:column;
    min-height:172px;
    border:1px solid rgba(148,163,184,.16);
    border-radius:18px;
    padding:1rem 1rem .95rem;
    background:linear-gradient(180deg,#fff 0%,#f8fafc 100%);
    box-shadow:0 12px 28px rgba(15,23,42,0.05);
    overflow:hidden;
}

body[data-theme="dark"] .ads-kpi{
    background:rgba(15,23,42,.92);
    border-color:rgba(51,65,85,.85);
    box-shadow:none;
}

.ads-kpi::before{
    content:'';
    position:absolute;
    inset:0 auto auto 0;
    width:100%;
    height:3px;
    background:linear-gradient(90deg,var(--kpi-accent-start,#334155),var(--kpi-accent-end,#94a3b8));
}

.ads-kpi.kpi-profit{ --kpi-accent-start:#16a34a; --kpi-accent-end:#22c55e; }
.ads-kpi.kpi-revenue{ --kpi-accent-start:#2563eb; --kpi-accent-end:#38bdf8; }
.ads-kpi.kpi-cogs{ --kpi-accent-start:#64748b; --kpi-accent-end:#94a3b8; }
.ads-kpi.kpi-spend{ --kpi-accent-start:#b45309; --kpi-accent-end:#f59e0b; }
.ads-kpi.kpi-roas{ --kpi-accent-start:#1d4ed8; --kpi-accent-end:#60a5fa; }

.ads-kpi-label{
    font-size:.62rem;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.08em;
    color:#334155;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
    line-height:1.05;
}

.ads-kpi-value{
    font-size:1.28rem;
    font-weight:950;
    line-height:1;
    letter-spacing:-0.03em;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
    color:var(--shp-text);
    margin-top:.34rem;
    margin-bottom:0;
}

.ads-kpi-sub{
    margin-top:auto;
    padding-top:.6rem;
    border-top:1px dashed rgba(148,163,184,.22);
    font-size:.62rem;
    font-weight:900;
    text-transform:none;
    letter-spacing:0;
    color:#64748b;
}

body[data-theme="dark"] .ads-kpi-label{ color:#cbd5e1; }
body[data-theme="dark"] .ads-kpi-value{ color:#e2e8f0; }
body[data-theme="dark"] .ads-kpi-sub{ color:#94a3b8; border-top-color:rgba(148,163,184,.18); }

.kpi {
    background: var(--card-bg);
    backdrop-filter: blur(10px);
    border: 1px solid var(--card-border);
    border-radius: 16px;
    padding: 1rem 1rem .95rem;
    box-shadow: 0 12px 28px rgba(15,23,42,.05);
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}
.kpi::after {
    content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%;
    background: currentColor; opacity: 0.7;
}
.kpi:hover {
    transform: translateY(-2px);
    box-shadow: 0 18px 34px rgba(15,23,42,.08);
}
.kpi-label { font-size: .66rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.08em; display:flex; align-items:center; gap:0.5rem; opacity:0.82;}
.kpi-value { font-size: 1.35rem; font-weight: 900; margin: 0.45rem 0 0.35rem; letter-spacing: -0.03em; }
.kpi-sub { font-size: .75rem; opacity: 0.85; }

/* ─── Mini Sync Log ─── */
.mini-log-toggle {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .35rem .7rem; border-radius: 8px; font-size: .72rem; font-weight: 600;
    cursor: pointer; user-select: none;
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    color: var(--dsh-muted); transition: all 0.25s ease;
}
.mini-log-toggle:hover { border-color: var(--dsh-accent); color: var(--dsh-accent); }
.mini-log-toggle .chevron {
    display: inline-block; transition: transform 0.3s ease; font-size: .6rem;
}
.mini-log-toggle.open .chevron { transform: rotate(180deg); }

.mini-log-panel {
    position: absolute; top: calc(100% + 8px); right: 0; z-index: 9999;
    min-width: 380px; max-width: 440px;
    background: var(--card-bg);
    border: 1px solid var(--card-border); border-radius: 12px;
    box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15);
    padding: 0; overflow: hidden;
    opacity: 0; transform: translateY(-8px) scale(0.97); pointer-events: none;
    transition: opacity 0.25s ease, transform 0.25s ease;
}
.mini-log-panel.show {
    opacity: 1; transform: translateY(0) scale(1); pointer-events: auto;
}
body[data-theme="dark"] .mini-log-panel {
    box-shadow: 0 16px 48px -12px rgba(0,0,0,0.5);
}

.mini-log-header {
    padding: .65rem .85rem; font-size: .72rem; font-weight: 700;
    color: var(--dsh-muted); text-transform: uppercase; letter-spacing: .06em;
    border-bottom: 1px solid var(--glass-border);
    display: flex; align-items: center; gap: .4rem;
}

.mini-log-entry {
    display: grid; grid-template-columns: 24px 1fr auto;
    gap: .5rem; align-items: center;
    padding: .55rem .85rem;
    border-bottom: 1px solid rgba(0,0,0,0.03);
    font-size: .72rem;
    transition: background 0.15s ease;
}
.mini-log-entry:last-child { border-bottom: none; }
.mini-log-entry:hover { background: rgba(0,0,0,0.02); }
body[data-theme="dark"] .mini-log-entry { border-bottom-color: rgba(255,255,255,0.03); }
body[data-theme="dark"] .mini-log-entry:hover { background: rgba(255,255,255,0.03); }

.mle-icon {
    width: 22px; height: 22px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .6rem;
}
.mle-icon.success { background: rgba(22,163,74,0.12); color: #16a34a; }
.mle-icon.error   { background: rgba(220,38,38,0.12); color: #dc2626; }
.mle-icon.processing { background: rgba(37,99,235,0.12); color: #2563eb; }
.mle-icon.rate_limited { background: rgba(234,179,8,0.12); color: #ca8a04; }

.mle-info { line-height: 1.35; }
.mle-time { font-weight: 600; color: var(--text, #0f172a); }
.mle-type { color: var(--dsh-muted); font-size: .65rem; }
.mle-badge {
    font-size: .6rem; font-weight: 700; letter-spacing: .03em;
    padding: .15rem .4rem; border-radius: 5px;
    text-transform: uppercase; white-space: nowrap;
}
.mle-badge.success { background: rgba(22,163,74,0.1); color: #16a34a; }
.mle-badge.error   { background: rgba(220,38,38,0.1); color: #dc2626; }
.mle-badge.processing { background: rgba(37,99,235,0.1); color: #2563eb; }
.mle-badge.rate_limited { background: rgba(234,179,8,0.1); color: #ca8a04; }

.mle-stats { color: var(--dsh-muted); font-size: .62rem; margin-top: .1rem; }

@keyframes spin { to { transform: rotate(360deg); } }
.mle-icon.processing i { animation: spin 1.2s linear infinite; }

/* Custom Table SM (Ultra Compact) */
.dpanel-table-sm thead th {
    font-size: 0.65rem !important;
    padding: 0.25rem 0.5rem !important;
    letter-spacing: 0.05em;
    background: transparent !important;
}
.dpanel-table-sm tbody td {
    font-size: 0.7rem !important;
    padding: 0.35rem 0.5rem !important;
    border-bottom: 1px solid rgba(0,0,0,0.04) !important;
}
body[data-theme="dark"] .dpanel-table-sm tbody td {
    border-bottom: 1px solid rgba(255,255,255,0.04) !important;
}

/* Responsive Overrides */
@media (max-width: 768px) {
    .dash-hero {
        flex-direction: column;
        align-items: stretch;
        padding: 1.25rem 1rem;
        gap: 1rem;
    }
    .dash-hero > div {
        text-align: left !important;
    }
    .dash-filter {
        flex-direction: column;
        align-items: stretch;
    }
    .filter-item {
        width: 100%;
        min-width: unset;
    }
    .range-pill {
        font-size: 0.75rem;
        padding: 0.5rem;
    }
    .dpanel-table {
        min-width: 1000px; /* force horizontal scroll on mobile */
    }
    .ads-hero .controls{
        justify-content:flex-start;
        width:100%;
    }
}

/* ═════════════════════════════════════════════════════════════════════════
   PENYELARASAN DENGAN GAYA SHIPMENTS — flat, rapat, tenang.
   Lapisan override (ditaruh paling akhir agar menang tanpa menghapus
   definisi di atas). Tidak mengubah struktur/fitur apa pun.
═════════════════════════════════════════════════════════════════════════ */

/* Hero → topbar kompak ala ship-topbar */
.dash-hero {
    border-radius: 20px;
    padding: 1rem 1.05rem;
    margin-bottom: .85rem;
    background: var(--hero-bg);
}
.dash-hero::before { width:180px; height:180px; right:-60px; top:-80px; background:rgba(59,130,246,.18); }
.dash-hero h1 { font-size: 1.2rem; font-weight: 900; letter-spacing: -0.04em; color:#fff; }
.dash-hero .sub { font-size: .78rem; margin-top: .25rem; color:rgba(226,232,240,.8); }
.dash-hero .btn { margin-right: 0 !important; } /* gap parent sudah cukup */

/* Kartu: flat seperti shipments — tanpa bayangan & hover-lift */
.dpanel {
    border-radius: 14px;
    box-shadow: 0 10px 22px rgba(15,23,42,.05);
}
.dpanel:hover {
    transform: none;
    box-shadow: 0 12px 26px rgba(15,23,42,.06);
}

/* Tab bar: rapat + sticky seperti topbar shipments */
.dash-tabs-modern {
    border-radius: 999px;
    padding: .3rem;
    box-shadow: 0 12px 28px rgba(15,23,42,.06);
    position: sticky;
    top: 0;
    z-index: 250;
    background: linear-gradient(180deg, rgba(248,250,252,.96), rgba(241,245,249,.92));
    border: 1px solid rgba(148,163,184,.18);
}
body[data-theme="dark"] .dash-tabs-modern { background: linear-gradient(180deg, rgba(15,23,42,.96), rgba(30,41,59,.92)); border-color:rgba(51,65,85,.85); box-shadow:none; }
.dash-tab-m { border-radius: 999px; font-size: .78rem; }
.dash-tab-m.active { box-shadow: 0 10px 20px rgba(15,23,42,.18); }

/* Tombol & chip: radius 7px konsisten, tanpa bayangan */
.btn-pill, .role-chip, .mini-log-toggle { border-radius: 7px !important; box-shadow: none !important; }

/* Tabel: header kecil-tenang tanpa uppercase, padding rapat ala table-list */
.dpanel-table thead th {
    font-size: .68rem;
    text-transform: none;
    letter-spacing: 0;
    color: #64748b;
    padding: .52rem .62rem;
}
body[data-theme="dark"] .dpanel-table thead th { color: #9ca3af; }
.dpanel-table tbody td { padding: .52rem .62rem; }

/* Label filter & input: tanpa uppercase, radius 7px */
.filter-item label { text-transform: none; letter-spacing: 0; }
.filter-select, .dash .form-control, .dash .form-select { border-radius: 7px; }

/* Pill status (riwayat sync dll.): titik indikator ala badge-status shipments */
.pill, .badge-status { border-radius: 7px; }

/* ── Input periode & kalender flatpickr: kompak-minimalis ── */
.range-pill {
    width: 240px;
    text-align: left;
    cursor: pointer;
    background: rgba(148,163,184,.06);
    border: 1px solid var(--dsh-border);
    padding: .4rem .85rem .4rem 2.1rem;
    border-radius: 12px;
    color: var(--text, #0f172a);
    font-weight: 650;
    font-size: .82rem;
}
.range-pill:hover { border-color: var(--dsh-accent); }
.range-pill:focus { outline: none; border-color: var(--dsh-accent); }

.ads-fp {
    border-radius: 10px !important;
    border: 1px solid var(--dsh-border) !important;
    box-shadow: 0 10px 30px rgba(15,23,42,.14) !important;
    font-family: 'Inter', sans-serif !important;
    overflow: hidden;
}
.ads-fp .flatpickr-months { padding: .25rem 0; }
.ads-fp .flatpickr-current-month { font-size: .85rem; font-weight: 700; }
.ads-fp .flatpickr-weekday { font-size: .64rem; font-weight: 700; }
.ads-fp .flatpickr-day {
    border-radius: 7px;
    font-size: .78rem;
    height: 32px;
    line-height: 32px;
    max-width: 34px;
}
.ads-fp .flatpickr-day.selected,
.ads-fp .flatpickr-day.startRange,
.ads-fp .flatpickr-day.endRange {
    background: var(--dsh-accent);
    border-color: var(--dsh-accent);
}
.ads-fp .flatpickr-day.inRange {
    background: rgba(37,99,235,.1);
    border-color: transparent;
    box-shadow: none;
    color: var(--dsh-accent);
}
.ads-fp .flatpickr-day.today { border-color: var(--dsh-accent); }
.ads-fp-presets {
    display: flex;
    flex-wrap: wrap;
    gap: .3rem;
    padding: .5rem .55rem;
    border-top: 1px solid rgba(148,163,184,.25);
    background: var(--card-bg);
}
.ads-fp-chip {
    border: 1px solid rgba(148,163,184,.35);
    background: transparent;
    border-radius: 999px;
    padding: .22rem .6rem;
    font-size: .68rem;
    font-weight: 650;
    color: #475569;
    cursor: pointer;
    transition: border-color .15s, color .15s;
}
.ads-fp-chip:hover { border-color: var(--dsh-accent); color: var(--dsh-accent); }
body[data-theme="dark"] .ads-fp { background: #1e293b !important; }
body[data-theme="dark"] .ads-fp .flatpickr-day { color: #e2e8f0; }
body[data-theme="dark"] .ads-fp-chip { color: #9ca3af; }

/* Final UI pass: flat header + cleaner tabs, aligned with shipments/income */
.ads-shell{
    max-width:1120px;
    margin-inline:auto;
}

.ads-hero{
    top:0;
    align-items:center;
    padding:.4rem .7rem;
    margin-bottom:.55rem;
    margin-inline:-.75rem;
    border-radius:0;
    background:var(--card,#fff);
    border:0;
    border-bottom:1px solid var(--shp-border, rgba(148,163,184,.18));
    box-shadow:none;
    overflow:visible;
}

.ads-hero::before,
.ads-hero::after{
    display:none;
}

body[data-theme="dark"] .ads-hero{
    background:var(--card,#0f172a);
    border-bottom-color:rgba(51,65,85,.85);
}

.ads-eyebrow{
    color:var(--shp-muted,#64748b);
    letter-spacing:0;
    margin-bottom:.1rem;
}

.ads-hero .title{
    color:var(--text,#0f172a);
    font-size:.98rem;
    font-weight:750;
    letter-spacing:-0.03em;
}

.ads-hero .sub{
    color:var(--shp-muted,#64748b);
    font-size:.75rem;
    max-width:44rem;
}

.ads-hero-badges{
    margin-top:.3rem;
    gap:.28rem;
}

.ads-chip{
    padding:.24rem .5rem;
    border-radius:7px;
    border:1px solid rgba(148,163,184,.28);
    background:transparent;
    color:var(--text,#0f172a);
    font-size:.66rem;
    font-weight:700;
}

body[data-theme="dark"] .ads-chip{
    background:rgba(15,23,42,.6);
    border-color:rgba(51,65,85,.9);
    color:#e2e8f0;
}

.ads-hero .controls{
    gap:.3rem;
}

.ads-hero .role-chip,
.ads-hero .btn-pill{
    border-radius:7px!important;
    padding-inline:.68rem!important;
    font-size:.7rem!important;
    font-weight:700!important;
}

.ads-hero .btn-ship-outline{
    background:transparent!important;
    color:var(--text,#0f172a)!important;
    border-color:rgba(148,163,184,.35)!important;
}

.ads-tabs-wrap{
    margin:0 0 .65rem;
}

.ads-tabs-wrap::before{
    display:none;
}

.dash-tabs-modern{
    display:inline-flex;
    gap:.25rem;
    padding:.24rem;
    border-radius:999px;
    background:linear-gradient(180deg, rgba(248,250,252,.96), rgba(241,245,249,.92));
    border:1px solid rgba(148,163,184,.18);
    box-shadow:0 12px 28px rgba(15,23,42,.06);
    position:static;
    top:auto;
}

body[data-theme="dark"] .dash-tabs-modern{
    background:linear-gradient(180deg, rgba(15,23,42,.96), rgba(30,41,59,.92));
    border-color:rgba(51,65,85,.85);
    box-shadow:none;
}

.dash-tab-m{
    border:none;
    background:transparent;
    color:#64748b;
    border-radius:999px;
    padding:.56rem .82rem;
    font-size:.76rem;
    font-weight:900;
    letter-spacing:-0.01em;
    cursor:pointer;
    white-space:nowrap;
    transition:all .18s ease;
}

.dash-tab-m:hover{
    color:#0f172a;
    background:rgba(255,255,255,.82);
}

.dash-tab-m.active{
    background:#0f172a;
    color:#fff;
    box-shadow:0 10px 20px rgba(15,23,42,.18);
}

body[data-theme="dark"] .dash-tab-m{
    color:#94a3b8;
}

body[data-theme="dark"] .dash-tab-m:hover{
    color:#e2e8f0;
    background:rgba(255,255,255,.06);
}

body[data-theme="dark"] .dash-tab-m.active{
    background:#1d4ed8;
    color:#fff;
    box-shadow:none;
}

.dash-tab-sm{
    border:none;
    background:transparent;
    color:#64748b;
    border-radius:999px;
    padding:.34rem .68rem;
    font-size:.7rem;
    font-weight:800;
    white-space:nowrap;
    transition:all .15s ease;
}

.dash-tab-sm:hover{
    color:#0f172a;
    background:rgba(255,255,255,.7);
}

.dash-tab-sm.active{
    background:var(--card-bg);
    color:var(--text);
    box-shadow:0 4px 10px rgba(15,23,42,.08);
}

body[data-theme="dark"] .dash-tab-sm{
    color:#94a3b8;
}

body[data-theme="dark"] .dash-tab-sm:hover{
    color:#e2e8f0;
    background:rgba(255,255,255,.06);
}

body[data-theme="dark"] .dash-tab-sm.active{
    background:var(--card-bg);
    color:#e2e8f0;
}

.ads-surface{
    border-radius:16px;
}

.ads-panel-head{
    padding:.75rem .9rem;
    margin-bottom:.7rem;
    border-bottom:1px solid rgba(148,163,184,.16);
}

.ads-panel-title{
    font-size:.94rem;
    font-weight:900;
    letter-spacing:-0.03em;
}

.ads-panel-note{
    font-size:.7rem;
}

.ads-tab-stack{
    display:grid;
    gap:.7rem;
}

.ads-tab-panel{
    border-radius:14px;
    border:1px solid var(--shp-border, rgba(148,163,184,.18));
    background:var(--card,#fff);
    box-shadow:none;
    overflow:hidden;
}

body[data-theme="dark"] .ads-tab-panel{
    background:var(--card,#0f172a);
    border-color:rgba(51,65,85,.85);
}

.ads-tab-panel-head{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:1rem;
    flex-wrap:wrap;
    padding:.75rem .9rem .7rem;
    border-bottom:1px solid var(--shp-border, rgba(148,163,184,.18));
    background:linear-gradient(180deg, rgba(248,250,252,.95), rgba(255,255,255,.98));
}

body[data-theme="dark"] .ads-tab-panel-head{
    background:linear-gradient(180deg, rgba(15,23,42,.96), rgba(15,23,42,.88));
    border-bottom-color:rgba(51,65,85,.85);
}

.ads-tab-panel-title{
    margin:0;
    font-size:.92rem;
    font-weight:900;
    letter-spacing:-0.03em;
    color:var(--text,#0f172a);
}

.ads-tab-panel-note{
    font-size:.7rem;
    color:var(--shp-muted,#64748b);
    padding:.34rem .62rem;
    border-radius:999px;
    background:rgba(148,163,184,.12);
    border:1px solid rgba(148,163,184,.22);
}

body[data-theme="dark"] .ads-tab-panel-note{
    background:rgba(30,41,59,.82);
    border-color:rgba(51,65,85,.92);
    color:#cbd5e1;
}

.dash-sec{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:.55rem;
    padding:.1rem 0 .3rem;
    margin:0 0 .55rem;
    border-bottom:1px solid rgba(148,163,184,.1);
    font-size:.68rem;
    font-weight:850;
    letter-spacing:.02em;
    text-transform:none;
    color:#475569;
}

body[data-theme="dark"] .dash-sec{
    color:#cbd5e1;
    border-bottom-color:rgba(51,65,85,.85);
}

.dash-sec i{
    font-size:.85rem;
}

@media (max-width: 768px) {
    .ads-hero{
        padding:.5rem .65rem;
        margin-bottom:.55rem;
    }
    .ads-hero .title{
        font-size:.98rem;
    }
    .ads-hero .sub{
        display:none;
    }
    .ads-hero-badges{
        display:none;
    }
    .ads-hero .controls{
        width:100%;
        justify-content:flex-start;
    }
    .dash-tabs-modern{
        width:max-content;
        min-width:100%;
    }
    .dash-tab-m{
        padding:.52rem .78rem;
    }
    .dash-sec{
        font-size:.66rem;
    }
}

@keyframes adsToastIn { from { transform: translateX(16px); opacity: 0; } to { transform: none; opacity: 1; } }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabBtns = document.querySelectorAll('.dash-tab-m[data-target]');
    const tabPanes = document.querySelectorAll('.tab-pane');

    const savedTab = localStorage.getItem('adsDashboardActiveTab');
    if (savedTab) {
        tabBtns.forEach(b => b.classList.remove('active'));
        tabPanes.forEach(p => p.classList.remove('active'));
        const targetBtn = document.querySelector(`.dash-tab-m[data-target="${savedTab}"]`);
        const targetPane = document.getElementById(savedTab);
        if (targetBtn && targetPane) {
            targetBtn.classList.add('active');
            targetPane.classList.add('active');
        } else {
            if (tabBtns.length > 0) tabBtns[0].classList.add('active');
            if (tabPanes.length > 0) tabPanes[0].classList.add('active');
        }
    }

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            tabBtns.forEach(b => b.classList.remove('active'));
            tabPanes.forEach(p => p.classList.remove('active'));
            
            btn.classList.add('active');
            const targetId = btn.getAttribute('data-target');
            document.getElementById(targetId).classList.add('active');
            localStorage.setItem('adsDashboardActiveTab', targetId);
        });
    });

    // Shortcut: semua pintu "log" menuju satu tempat — tab Sync.
    window.openSyncTab = function () {
        const btn = document.querySelector('.dash-tab-m[data-target="tab-sync"]');
        if (btn) btn.click();
        try { window.scrollTo({ top: 0, behavior: 'smooth' }); } catch (e) {}
    };

    // Ingat filter terakhir (toko + periode + mode banding) selama sesi browser.
    try {
        if (window.location.search && window.location.search.length > 1) {
            sessionStorage.setItem('adsDashFilters', window.location.search);
        } else {
            const savedQ = sessionStorage.getItem('adsDashFilters');
            if (savedQ && savedQ.length > 1) window.location.replace(window.location.pathname + savedQ);
        }
    } catch (e) {}


    window.dispatchEvent(new Event('resize')); // re-render charts on load

    // Flatpickr Logic
    const rangePicker = document.getElementById('rangePicker');
    const fromEl = document.getElementById('fromHidden');
    const toEl = document.getElementById('toHidden');
    const filterForm = document.getElementById('filterForm');

    function ymd(d) { return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0'); }

    function canonicalQuery(params) {
        return [...params.entries()]
            .filter(([, value]) => value !== null && value !== undefined && String(value).length > 0)
            .sort(([a], [b]) => a.localeCompare(b))
            .map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(value)}`)
            .join('&');
    }

    window.submitAdsFilters = function (form) {
        const targetForm = form || filterForm;
        if (!targetForm) return false;

        const currentQuery = canonicalQuery(new URLSearchParams(window.location.search));
        const nextQuery = canonicalQuery(new FormData(targetForm));
        if (currentQuery === nextQuery) return false;

        if (window.__dashLoading) window.__dashLoading();
        requestAnimationFrame(() => {
            if (typeof targetForm.requestSubmit === 'function') {
                targetForm.requestSubmit();
            } else {
                targetForm.submit();
            }
        });
        return true;
    };
    
    function applyRange(from, to) {
        fromEl.value = ymd(from);
        toEl.value = ymd(to);
        window.submitAdsFilters(filterForm);
    }

    if(typeof flatpickr !== 'undefined' && rangePicker) {
        flatpickr(rangePicker, {
            mode: 'range',
            locale: 'id',
            showMonths: 1, // kompak — rentang panjang lebih cepat lewat preset di bawah kalender
            dateFormat: 'Y-m-d',
            altInput: true,            // tampilan manusiawi: "1 Mei 2026 s.d. 27 Jul 2026"
            altFormat: 'j M Y',
            altInputClass: 'range-pill',
            defaultDate: [fromEl.value, toEl.value],
            onChange: function(selectedDates, dateStr, instance) {
                if(selectedDates.length === 2) {
                    applyRange(selectedDates[0], selectedDates[1]);
                }
            },
            onClose: function(selectedDates, dateStr, instance) {
                // Pilih 1 tanggal lalu tutup = lihat data 1 hari itu.
                if(selectedDates.length === 1) {
                    instance.setDate([selectedDates[0], selectedDates[0]], false);
                    applyRange(selectedDates[0], selectedDates[0]);
                }
            },
            onReady: function(sd, ds, instance) {
                // Preset sekali-klik di kaki kalender — pengganti teks petunjuk:
                // tombolnya sendiri yang menjelaskan.
                instance.calendarContainer.classList.add('ads-fp');
                const bar = document.createElement('div');
                bar.className = 'ads-fp-presets';
                const mk = (label, fn) => {
                    const b = document.createElement('button');
                    b.type = 'button';
                    b.className = 'ads-fp-chip';
                    b.textContent = label;
                    b.addEventListener('click', () => {
                        const [f, t] = fn();
                        instance.setDate([f, t], false);
                        instance.close();
                        applyRange(f, t);
                    });
                    bar.appendChild(b);
                };
                const today = () => new Date();
                const back = (n) => { const t = today(); const f = new Date(); f.setDate(t.getDate() - (n - 1)); return [f, t]; };
                mk('Hari Ini',   () => { const t = today(); return [t, t]; });
                mk('7 Hari',     () => back(7));
                mk('30 Hari',    () => back(30));
                mk('90 Hari',    () => back(90));
                mk('Bulan Ini',  () => { const t = today(); return [new Date(t.getFullYear(), t.getMonth(), 1), t]; });
                mk('Bulan Lalu', () => { const t = today(); return [new Date(t.getFullYear(), t.getMonth() - 1, 1), new Date(t.getFullYear(), t.getMonth(), 0)]; });
                instance.calendarContainer.appendChild(bar);
            }
        });
    }


    // --- NEXT SYNC COUNTDOWN INDICATOR ---
    const syncChip = document.getElementById('syncCountdown');
    
    function updateSyncCountdown() {
        const now = new Date();
        const nextSync = new Date(now);
        nextSync.setHours(now.getHours() + 1, 0, 0, 0); // next :00
        
        const diffMs = nextSync - now;
        const diffMin = Math.floor(diffMs / 60000);
        const diffSec = Math.floor((diffMs % 60000) / 1000);
        
        if (diffMin <= 1) {
            syncChip.innerHTML = `<i class="bi bi-arrow-repeat"></i> Sync segera...`;
            syncChip.classList.remove('live-off');
            syncChip.classList.add('live-on');
        } else {
            syncChip.innerHTML = `<i class="bi bi-clock-history"></i> Sync berikutnya: ${diffMin} mnt`;
            syncChip.classList.remove('live-on');
            syncChip.classList.add('live-off');
        }
    }
    
    updateSyncCountdown();
    setInterval(updateSyncCountdown, 30000); // update tiap 30 detik

});

// --- FETCH REAL-TIME STATUS ---
function fetchRealtimeStatus() {
    const storeId = document.querySelector('select[name="store_id"]').value;
    const container = document.getElementById('realtimeStatusContainer');
    if (!storeId || !container) return;

    fetch(`{{ route('marketplace.ads.realtime.status') }}?store_id=${storeId}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                const bal = data.data.balance || {};
                const toggle = data.data.toggle_info || {};
                
                const formatRp = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(val || 0);
                
                // Format total balance (gratis + berbayar)
                const totalBal = formatRp(bal.total_balance || 0);
                
                // Auto top-up status
                let topupHtml = '';
                if (toggle.auto_top_up === true) {
                    topupHtml = `<span style="color: #16a34a; font-weight: 700;"><i class="bi bi-check-circle-fill"></i> AKTIF</span>`;
                } else {
                    topupHtml = `<span style="color: var(--dsh-muted); font-weight: 700;"><i class="bi bi-x-circle-fill"></i> NON-AKTIF</span>`;
                }
                
                container.innerHTML = `
                    <div class="dpanel" style="padding: 0.75rem 1rem; border-radius: 12px; border: 1px solid rgba(245, 158, 11, 0.2); background: rgba(245, 158, 11, 0.05); animation: fadeIn 0.4s ease-out; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <div style="font-size: 0.65rem; color: #b45309; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px;"><i class="bi bi-wallet2"></i> Saldo Iklan</div>
                            <div style="font-size: 1.15rem; font-weight: 700; color: #92400e;">${totalBal}</div>
                        </div>
                    </div>
                    
                    <div class="dpanel" style="padding: 0.75rem 1rem; border-radius: 12px; border: 1px solid rgba(22, 163, 74, 0.2); background: rgba(22, 163, 74, 0.05); animation: fadeIn 0.4s ease-out; animation-delay: 0.1s; animation-fill-mode: both; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <div style="font-size: 0.65rem; color: #15803d; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px;"><i class="bi bi-arrow-repeat"></i> Auto Top-Up</div>
                            <div style="font-size: 0.95rem; margin-top: 4px;">${topupHtml}</div>
                        </div>
                    </div>
                `;
            } else {
                container.innerHTML = `<div class="dpanel p-3 w-100" style="color: #dc2626; border-left: 4px solid #dc2626;">Gagal memuat informasi real-time.</div>`;
            }
        })
        .catch(err => {
            container.innerHTML = `<div class="dpanel p-3 w-100" style="color: #dc2626; border-left: 4px solid #dc2626;">Koneksi error: Gagal memuat informasi.</div>`;
        });
}

document.addEventListener('DOMContentLoaded', function () {
    setTimeout(fetchRealtimeStatus, 500); // Fetch after 500ms
});

function toggleCampaignStatus(el, campId, action) {
    if (!confirm('Apakah Anda yakin ingin ' + (action === 'pause' ? 'menjeda (pause)' : 'melanjutkan (resume)') + ' kampanye ini?')) {
        return;
    }
    
    const storeId = "{{ $storeId }}";
    if (storeId === 'all') { showToast('Mode Semua Toko — pilih satu toko dulu untuk aksi ini.'); return; }
    let payload = {
        _token: document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '',
        store_id: storeId,
        campaign_id: campId,
        status_action: action
    };
    
    // Show loading on the icon
    const originalClass = el.className;
    el.className = 'spinner-border spinner-border-sm text-primary';
    el.style.opacity = '1';
    
    fetch('/marketplace/ads-dashboard/cpc-campaign-edit', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': payload._token
        },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Update UI by reloading the page to reflect the new badge, or we can just reload the page for simplicity
            window.location.reload();
        } else {
            showToast('Gagal mengubah status: ' + (data.message || 'Unknown error'));
            el.className = originalClass;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Terjadi kesalahan sistem.');
        el.className = originalClass;
    });
}
</script>
@endpush

@section('content')
<div class="dash py-3">
<div class="page-wrap ads-shell">

    {{-- Toast ringan pengganti alert() — tipe otomatis dari isi pesan --}}
    <div id="adsToastWrap" style="position:fixed; top:14px; right:14px; z-index:4000; display:flex; flex-direction:column; gap:.4rem; max-width:340px;"></div>
    {{-- Overlay loading saat ganti filter — respons instan --}}
    <div id="dashLoadingOverlay" style="display:none; position:fixed; inset:0; z-index:5000; background:rgba(15,23,42,.35); backdrop-filter:blur(2px); align-items:center; justify-content:center;">
        <div style="background:var(--card-bg); border:1px solid var(--card-border); border-radius:12px; padding:1rem 1.5rem; display:flex; align-items:center; gap:.7rem; font-size:.85rem; font-weight:700; color:var(--text);">
            <i class="bi bi-arrow-repeat spin-icon" style="display:inline-block; color:var(--dsh-accent); font-size:1.1rem;"></i> Memuat data&hellip;
        </div>
    </div>
    <script>
    window.__dashLoading = function () {
        const o = document.getElementById('dashLoadingOverlay');
        if (o) o.style.display = 'flex';
    };
    window.showToast = function (msg) {
        try {
            const wrap = document.getElementById('adsToastWrap');
            const s = String(msg || '');
            let fg = '#1d4ed8', bg = 'rgba(59,130,246,.12)', bd = 'rgba(59,130,246,.35)', ic = 'bi-info-circle';
            if (/berhasil|selesai|sukses/i.test(s)) { fg = '#15803d'; bg = 'rgba(22,163,74,.12)'; bd = 'rgba(22,163,74,.35)'; ic = 'bi-check-circle'; }
            else if (/gagal|error|kesalahan|tidak ditemukan/i.test(s)) { fg = '#b91c1c'; bg = 'rgba(220,38,38,.12)'; bd = 'rgba(220,38,38,.35)'; ic = 'bi-x-circle'; }
            else if (/harap|tunggu|dibatalkan|peringatan/i.test(s)) { fg = '#b45309'; bg = 'rgba(245,158,11,.14)'; bd = 'rgba(245,158,11,.4)'; ic = 'bi-exclamation-triangle'; }
            const t = document.createElement('div');
            t.style.cssText = 'display:flex; gap:.5rem; align-items:flex-start; padding:.6rem .8rem; border-radius:10px; font-size:.78rem; font-weight:600; color:' + fg + '; background:' + bg + '; border:1px solid ' + bd + '; backdrop-filter:blur(6px); box-shadow:0 6px 20px rgba(15,23,42,.15); animation:adsToastIn .25s ease;';
            t.innerHTML = '<i class="bi ' + ic + '" style="margin-top:1px;"></i><span></span>';
            t.querySelector('span').textContent = s;
            t.addEventListener('click', () => t.remove());
            wrap.appendChild(t);
            setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity .3s'; setTimeout(() => t.remove(), 350); }, 4500);
        } catch (e) { /* fallback terakhir */ window.alert(msg); }
    };
    </script>

    {{-- ==============================================
         HERO SECTION (Header)
    ============================================== --}}
    <div class="ads-hero">
        <div>
            <div class="ads-eyebrow"><i class="bi bi-megaphone-fill"></i> Marketplace Ads</div>
            <h1 class="title">Analisis Iklan Shopee</h1>
            <div class="sub">Pantau biaya, GMV, dan profit harian.</div>
            <div class="ads-hero-badges">
                <span class="ads-chip"><i class="bi bi-lightning-charge-fill"></i> ROAS</span>
                <span class="ads-chip"><i class="bi bi-wallet2"></i> Biaya</span>
            </div>
        </div>
        <div class="controls" style="position: relative; text-align: right; display:flex; flex-wrap:wrap;">
            @if(isset($syncRuns) && $syncRuns->isNotEmpty())
                @php
                    $latestRun = $syncRuns->first();
                    $lastSuccess = $lastSuccessRun ?? null;
                @endphp
                @if($latestRun->status === 'error')
                    <div class="ads-hero-meta ads-hero-error">
                        <i class="bi bi-exclamation-triangle-fill"></i> Sync gagal: {{ Str::limit($latestRun->error_message, 60) }}
                    </div>
                @endif
                <div class="ads-hero-meta">
                    @if($lastSuccess)
                        <span style="color: #16a34a;"><i class="bi bi-check-circle-fill"></i></span>
                        Sync terakhir: {{ $lastSuccess->updated_at?->timezone('Asia/Jakarta')?->format('d M Y, H:i') ?? 'waktu tidak tersedia' }}
                    @else
                        <span style="color: #eab308;"><i class="bi bi-clock"></i></span>
                        Belum ada sync
                    @endif
                </div>
            @endif
            <div class="ads-hero-actions" style="display: flex; gap: .5rem; justify-content: flex-end; align-items: center; flex-wrap: wrap;">
                <div id="syncCountdown" class="role-chip live-btn live-off">
                    <i class="bi bi-clock-history"></i> Menghitung...
                </div>
                @if(auth()->user()->role === 'owner')
                <button type="button" class="btn btn-sm btn-ship-outline btn-pill" onclick="clearAdsData()" style="border-radius:8px;font-weight:600;font-size:.72rem;padding:.35rem .7rem; border: 1px solid rgba(239, 68, 68, 0.3); background: rgba(239, 68, 68, 0.1); color: #ef4444; box-shadow: var(--card-shadow);">
                    <i class="bi bi-trash"></i> Bersihkan Data
                </button>
                @endif
                
                <button type="button" class="btn btn-sm btn-ship-outline btn-pill" data-bs-toggle="modal" data-bs-target="#modalGmsSettings" style="border-radius:8px;font-weight:600;font-size:.72rem;padding:.35rem .7rem; border: 1px solid var(--dsh-accent); background: rgba(37, 99, 235, 0.05); color: var(--dsh-accent); margin-right: 0.5rem;">
                    <i class="bi bi-gear"></i> Pengaturan GMV Max
                </button>
                <button type="button" class="btn btn-sm btn-ship-outline btn-pill" onclick="openSyncTab()" style="border-radius:8px;font-weight:600;font-size:.72rem;padding:.35rem .7rem; border: 1px solid var(--card-border); background: var(--card-bg); box-shadow: var(--card-shadow); color: var(--text); margin-right: 0.5rem;">
                    <i class="bi bi-journal-text"></i> Log Sync
                </button>
                <button type="button" class="btn btn-sm btn-ship-outline btn-pill" data-bs-toggle="modal" data-bs-target="#modalSyncAds" style="border-radius:8px;font-weight:600;font-size:.72rem;padding:.35rem .7rem; border: 1px solid var(--card-border); background: var(--card-bg); box-shadow: var(--card-shadow); color: var(--text);">
                    <i class="bi bi-arrow-repeat"></i> Sync Manual
                </button>
            </div>
        </div>
    </div>

    @if(session('error'))
        <div class="dpanel dpanel-body" style="border-left: 4px solid #dc2626; color: #dc2626; font-size:.82rem; font-weight: 500;">
            <i class="bi bi-exclamation-circle me-1"></i> {{ session('error') }}
        </div>
    @endif
    @if(session('success'))
        <div class="dpanel dpanel-body" style="border-left: 4px solid #16a34a; color: #16a34a; font-size:.82rem; font-weight: 500;">
            <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
        </div>
    @endif

    {{-- ==============================================
         FILTER
    ============================================== --}}
    <form method="GET" action="{{ route('marketplace.ads.dashboard') }}" class="dash-filter" id="filterForm">
        <div class="filter-item">
            <label>Toko Shopee</label>
            <select name="store_id" onchange="submitAdsFilters(this.form)">
                <option value="all" {{ $storeId == 'all' ? 'selected' : '' }}>&#127970; Semua Toko</option>
                @foreach($stores as $s)
                    <option value="{{ $s->id }}" {{ $storeId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-item" style="flex: 2;">
            <label>Periode Data</label>
            <div class="period-bar" style="position: relative;">
                <i class="bi bi-calendar3" style="position:absolute; left:.7rem; top:50%; transform:translateY(-50%); color:var(--dsh-muted); font-size:.8rem; pointer-events:none; z-index:2;"></i>
                <input type="text" id="rangePicker" class="range-pill" placeholder="Pilih tanggal" readonly>
                <input type="hidden" name="date_from" id="fromHidden" value="{{ $dateFrom }}">
                <input type="hidden" name="date_to" id="toHidden" value="{{ $dateTo }}">
            </div>
        </div>
        <div class="filter-item">
            <label>Bandingkan:</label>
            <select name="compare_mode" onchange="submitAdsFilters(this.form)">
                <option value="prev_period" {{ (isset($compareMode) && $compareMode == 'prev_period') ? 'selected' : '' }}>Durasi Sama (Sblmnya)</option>
                <option value="prev_month" {{ (isset($compareMode) && $compareMode == 'prev_month') ? 'selected' : '' }}>Bulan Lalu (Tgl Sama)</option>
                <option value="prev_year" {{ (isset($compareMode) && $compareMode == 'prev_year') ? 'selected' : '' }}>Tahun Lalu (Tgl Sama)</option>
            </select>
        </div>
    </form>

    @if(empty($storeId))
        <div class="dash-empty">
            <i class="bi bi-shop"></i>
            Pilih toko dulu.
        </div>
    @else
        {{-- ==============================================
             TABS (SEGMENTED CONTROL)
        ============================================== --}}
        <div class="ads-tabs-wrap" style="overflow-x: auto; padding-bottom: 0.25rem; scrollbar-width: none;">
            <div class="dash-tabs-modern">
                <button class="dash-tab-m active" data-target="tab-dashboard"><i class="bi bi-grid-1x2"></i> Ringkasan</button>
                <button class="dash-tab-m" data-target="tab-campaigns"><i class="bi bi-megaphone"></i> Kampanye</button>
                <button class="dash-tab-m" data-target="tab-items"><i class="bi bi-box-seam"></i> Produk</button>
                <button class="dash-tab-m" data-target="tab-profit"><i class="bi bi-cash-coin"></i> Profit</button>
                <button class="dash-tab-m" data-target="tab-sync"><i class="bi bi-arrow-repeat"></i> Sync <span id="tabSyncBadge" style="display:none; margin-left:2px; min-width:16px; height:16px; padding:0 4px; border-radius:999px; background:#3b82f6; color:#fff; font-size:.6rem; font-weight:800; line-height:16px; text-align:center;"></span></button>
            </div>
        </div>

        {{-- ==============================================
             TAB CONTENT
        ============================================== --}}
        
        <!-- DASHBOARD TAB -->
        <div class="tab-pane active" id="tab-dashboard">
            
            <!-- Live Progress Bar (Hidden by default) -->
            <div id="liveSyncProgress" class="dpanel ads-surface mb-3 p-3" style="display: none; border-left: 4px solid var(--dsh-accent); background: var(--dsh-bg);">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="font-weight: 600; font-size: .85rem; color: var(--text);">Sinkronisasi...</span>
                    <span id="liveSyncPercent" style="font-size: .75rem; font-weight: 700; color: var(--dsh-accent);">0%</span>
                </div>
                <div style="width: 100%; height: 6px; background: var(--dsh-border); border-radius: 4px; overflow: hidden;">
                    <div id="liveSyncBar" style="width: 0%; height: 100%; background: var(--dsh-accent); transition: width 0.3s ease;"></div>
                </div>
                <div id="liveSyncLog" style="margin-top: .5rem; font-size: .7rem; font-family: ui-monospace, monospace; color: var(--dsh-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    Menghubungkan ke server...
                </div>
            </div>

            {{-- RINGKASAN PROFITABILITAS — angka bisnis dulu, baru metrik iklan --}}
            @php
                $rSpend  = (float) ($kpi['current']->spend ?? 0);
                $rGmv    = (float) ($kpi['current']->gmv ?? 0);
                $rOrders = (int) ($kpi['current']->orders ?? 0);
                $rNetRevenue = 0.0; $rCogs = 0.0; $rUntung = 0; $rRugi = 0;
                foreach ($campaigns as $c) {
                    if (!($c->spend > 0 || $c->gmv > 0)) continue;
                    $rNetRevenue += $c->gmv * ($c->net_revenue_ratio ?? 0.781);
                    $rCogs += ($c->unit_cogs > 0 && ($c->items_sold ?? 0) > 0)
                        ? $c->unit_cogs * $c->items_sold
                        : $c->gmv * ($c->cogs_ratio ?? 0);
                    if ($c->profit_after_ads !== null) { $c->profit_after_ads >= 0 ? $rUntung++ : $rRugi++; }
                }
                $rProfit = $rNetRevenue - $rCogs - ($rSpend * 1.11);
                $rRoas   = $rSpend > 0 ? $rGmv / $rSpend : 0;
                // Pembanding periode lalu: estimasi rasio periode ini (selaras tab Kampanye)
                $pGmv = (float) ($kpi['previous']->gmv ?? 0);
                $pSpend = (float) ($kpi['previous']->spend ?? 0);
                $revR = $rGmv > 0 ? $rNetRevenue / $rGmv : 0.781;
                $cogsR = $rGmv > 0 ? $rCogs / $rGmv : 0;
                $pProfit = ($pGmv * $revR) - ($pGmv * $cogsR) - ($pSpend * 1.11);
                $dProfit = $pProfit != 0.0 ? (($rProfit - $pProfit) / abs($pProfit)) * 100 : ($rProfit > 0 ? 100 : 0);
                $rfmt = fn ($n) => 'Rp ' . number_format(abs($n), 0, ',', '.');
                $rProfitPos = $rProfit >= 0;
            @endphp
            <div class="ads-tab-panel mb-3">
                <div class="ads-tab-panel-head">
                    <div>
                        <div class="ads-tab-panel-title"><i class="bi bi-piggy-bank text-success"></i> Profit Periode Ini</div>
                        <div class="ads-tab-panel-note">Snapshot periode aktif vs sebelumnya.</div>
                    </div>
                    <a href="javascript:void(0)" onclick="document.querySelector('.dash-tab-m[data-target=&quot;tab-profit&quot;]').click()" style="font-size:.7rem; font-weight:700; color:var(--dsh-accent); text-decoration:none;">Lihat rincian &rarr;</a>
                </div>
                <div class="p-3 p-md-3">
                    <div class="ads-kpi-grid mb-0">
                        <div class="dpanel ads-kpi kpi-profit" style="border-color:rgba({{ $rProfitPos ? '22,163,74' : '220,38,38' }},.28); background:linear-gradient(180deg, rgba({{ $rProfitPos ? '22,163,74' : '220,38,38' }},.08), rgba(248,250,252,1));">
                            <div class="ads-kpi-label" style="color:{{ $rProfitPos ? '#15803d' : '#b91c1c' }};"><i class="bi bi-piggy-bank"></i> Net Profit</div>
                            <div class="ads-kpi-value" style="color:{{ $rProfitPos ? '#15803d' : '#b91c1c' }}; font-variant-numeric:tabular-nums;">{{ $rProfit < 0 ? '-' : '' }}{{ $rfmt($rProfit) }}</div>
                            <div class="ads-kpi-sub">{{ $rUntung }} untung &bull; {{ $rRugi }} rugi &bull; <span style="font-weight:900; color:{{ $dProfit >= 0 ? '#16a34a' : '#dc2626' }};"><i class="bi bi-arrow-{{ $dProfit >= 0 ? 'up' : 'down' }}-right"></i> {{ number_format(abs($dProfit), 1, ',', '.') }}%</span> vs lalu</div>
                        </div>
                        <div class="dpanel ads-kpi kpi-revenue" style="border-color:rgba(3,105,161,.25); background:linear-gradient(180deg, rgba(3,105,161,.07), rgba(248,250,252,1));">
                            <div class="ads-kpi-label" style="color:#0369a1;"><i class="bi bi-cash-coin"></i> Pendapatan Bersih</div>
                            <div class="ads-kpi-value" style="color:#0369a1; font-variant-numeric:tabular-nums;">{{ $rfmt($rNetRevenue) }}</div>
                            <div class="ads-kpi-sub">Omzet (GMV): {{ $rfmt($rGmv) }}</div>
                        </div>
                        <div class="dpanel ads-kpi kpi-cogs" style="border-color:rgba(148,163,184,.3); background:linear-gradient(180deg, rgba(148,163,184,.08), rgba(248,250,252,1));">
                            <div class="ads-kpi-label" style="color:var(--dsh-muted);"><i class="bi bi-box-seam"></i> Modal (HPP)</div>
                            <div class="ads-kpi-value" style="color:var(--text); font-variant-numeric:tabular-nums;">&minus;{{ $rfmt($rCogs) }}</div>
                            <div class="ads-kpi-sub">dari pcs terjual &times; HPP</div>
                        </div>
                        <div class="dpanel ads-kpi kpi-spend" style="border-color:rgba(245,158,11,.3); background:linear-gradient(180deg, rgba(245,158,11,.08), rgba(248,250,252,1));">
                            <div class="ads-kpi-label" style="color:#b45309;"><i class="bi bi-wallet2"></i> Biaya Iklan</div>
                            <div class="ads-kpi-value" style="color:#92400e; font-variant-numeric:tabular-nums;">&minus;{{ $rfmt($rSpend * 1.11) }}</div>
                            <div class="ads-kpi-sub">nyata {{ $rfmt($rSpend) }} + PPN 11%</div>
                        </div>
                        <div class="dpanel ads-kpi kpi-roas" style="border-color:rgba(37,99,235,.25); background:linear-gradient(180deg, rgba(37,99,235,.07), rgba(248,250,252,1));">
                            <div class="ads-kpi-label" style="color:#1d4ed8;"><i class="bi bi-lightning-charge"></i> ROAS &bull; Pesanan</div>
                            <div class="ads-kpi-value" style="color:#1d4ed8; font-variant-numeric:tabular-nums;">{{ number_format($rRoas, 2, ',', '.') }}x <span style="font-size:.85rem; color:var(--text);">&bull; {{ number_format($rOrders, 0, ',', '.') }}</span></div>
                            <div class="ads-kpi-sub">omzet per rupiah iklan &bull; total order</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ads-tab-panel mb-3">
                <div class="ads-tab-panel-head">
                    <div>
                        <div class="ads-tab-panel-title"><i class="bi bi-wallet2 text-primary"></i> Status Toko</div>
                        <div class="ads-tab-panel-note">Ringkasan real-time saldo dan kondisi toko.</div>
                    </div>
                </div>
                <div class="p-3" id="realtimeStatusContainer" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: .65rem;">
                    <div class="dpanel p-3" style="border-left: 4px solid var(--dsh-border)">
                        <div class="placeholder-glow">
                            <span class="placeholder col-6 mb-2"></span>
                            <span class="placeholder col-4 d-block" style="height: 2rem;"></span>
                        </div>
                    </div>
                    <div class="dpanel p-3" style="border-left: 4px solid var(--dsh-border)">
                        <div class="placeholder-glow">
                            <span class="placeholder col-7 mb-2"></span>
                            <span class="placeholder col-5 d-block" style="height: 2rem;"></span>
                        </div>
                    </div>
                    <div class="dpanel p-3" style="border-left: 4px solid var(--dsh-border)">
                        <div class="placeholder-glow">
                            <span class="placeholder col-8 mb-2"></span>
                            <span class="placeholder col-4 d-block" style="height: 2rem;"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ads-tab-panel mb-3">
                <div class="ads-tab-panel-head">
                    <div>
                        <div class="ads-tab-panel-title"><i class="bi bi-clock-history text-primary"></i> Komparasi</div>
                        <div class="ads-tab-panel-note">Perbandingan rentang saat ini vs sebelumnya.</div>
                    </div>
                    <div style="overflow-x:auto; scrollbar-width:none;">
                        <input type="hidden" id="histMetricSelect" value="roas">
                        <div class="dash-tabs-modern" id="histMetricChips" style="padding:.2rem;">
                            <button class="dash-tab-sm active" data-val="roas"><i class="bi bi-lightning-charge"></i> ROAS</button>
                            <button class="dash-tab-sm" data-val="gmv"><i class="bi bi-bag-check"></i> GMV</button>
                            <button class="dash-tab-sm" data-val="spend"><i class="bi bi-wallet2"></i> Biaya</button>
                            <button class="dash-tab-sm" data-val="impressions"><i class="bi bi-eye"></i> Impresi</button>
                            <button class="dash-tab-sm" data-val="clicks"><i class="bi bi-cursor"></i> Klik</button>
                            <button class="dash-tab-sm" data-val="ctr"><i class="bi bi-hand-index"></i> CTR</button>
                            <button class="dash-tab-sm" data-val="cvr"><i class="bi bi-funnel"></i> CVR</button>
                        </div>
                    </div>
                </div>
                <div class="p-3 pt-0">
                    <div class="dpanel p-3 mb-3" style="border-left: 4px solid var(--dsh-border)" id="insightHistorical">
                        <div style="color: var(--dsh-muted); font-size: 0.8rem; display:flex; align-items:center; gap:0.5rem;">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            Memuat data...
                        </div>
                    </div>
                    <div class="dpanel p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div style="font-size: .72rem; color: var(--dsh-muted);">Ringkas perubahan antar periode.</div>
                            <div id="histSummary" style="font-size: .78rem; font-weight: 700; color: var(--text); text-align: right;"></div>
                        </div>
                        <div style="position: relative; height: 320px;">
                            <canvas id="historicalChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ads-tab-panel mb-3">
                <div class="ads-tab-panel-head">
                    <div>
                        <div class="ads-tab-panel-title"><i class="bi bi-clock text-primary"></i> Heatmap Jam</div>
                        <div class="ads-tab-panel-note">Jam paling ramai.</div>
                    </div>
                </div>
                <div class="p-3">
                    <div style="position: relative; height: 250px;">
                        <canvas id="hourlyChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="ads-tab-panel mb-3">
                <div class="ads-tab-panel-head">
                    <div>
                        <div class="ads-tab-panel-title"><i class="bi bi-graph-up"></i> Grafik Harian</div>
                        <div class="ads-tab-panel-note">Finansial dan trafik dalam satu tampilan.</div>
                    </div>
                </div>
                <div class="p-3">
                    <div class="ads-tab-stack" style="grid-template-columns: 1fr; gap: .75rem;">
                        <div class="dpanel p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <div style="font-weight: 650; font-size: 0.82rem; color: var(--dsh-muted);">Finansial</div>
                                    <div class="mt-1" style="font-size: 0.7rem; color: var(--dsh-muted); opacity: 0.85;">GMV, biaya, dan margin.</div>
                                </div>
                                <div id="dailySummary" style="font-size: 0.78rem; font-weight: 700; color: var(--text); text-align: right;"></div>
                            </div>

                            <div class="mb-3 p-2" style="border-left: 4px solid var(--dsh-border); background: var(--dsh-bg); border-radius: 4px;" id="insightDailyTrend">
                                <div style="color: var(--dsh-muted); font-size: 0.75rem; display:flex; align-items:center; gap:0.5rem;">
                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                    Memuat data...
                                </div>
                            </div>

                            <div style="height: 280px;">
                                <canvas id="dailyChart"></canvas>
                            </div>
                        </div>

                        <div class="dpanel p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <div style="font-weight: 650; font-size: 0.82rem; color: var(--dsh-muted);">Trafik</div>
                                    <div class="mt-1" style="font-size: 0.7rem; color: var(--dsh-muted); opacity: 0.85;">Impresi, klik, dan CVR.</div>
                                </div>
                                <div id="trafficSummary" style="font-size: 0.78rem; font-weight: 700; color: var(--text); text-align: right;"></div>
                            </div>

                            <div class="mb-3 p-2" style="border-left: 4px solid var(--dsh-border); background: var(--dsh-bg); border-radius: 4px;" id="insightDailyTraffic">
                                <div style="color: var(--dsh-muted); font-size: 0.75rem; display:flex; align-items:center; gap:0.5rem;">
                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                    Memuat data...
                                </div>
                            </div>

                            <div style="height: 280px;">
                                <canvas id="trafficChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB PROFITABILITAS -->
        <div class="tab-pane" id="tab-profit">
            <div class="ads-tab-panel mb-3">
                <div class="ads-tab-panel-head">
                    <div>
                        <div class="ads-tab-panel-title"><i class="bi bi-cash-coin text-success"></i> Profitabilitas</div>
                        <div class="ads-tab-panel-note">Profit, komposisi biaya, dan performa kampanye.</div>
                    </div>
                </div>
            </div>
            @include('marketplace.partials._profitability_tab')
        </div>

        <!-- TAB SYNC -->
        <div class="tab-pane" id="tab-sync">
            @include('marketplace.partials._sync_tab')
        </div>

        <!-- TAB KAMPANYE -->
        <div class="tab-pane" id="tab-campaigns">
            
            <div class="dash-sec"><i class="bi bi-grid-1x2"></i> KPI Kampanye</div>
            
            <div class="dash-grid mb-4">
                @php
                    // Calculate Global Profit Data
                    $totalNetRevenue = 0;
                    $totalCogs = 0;
                    foreach($campaigns as $camp) {
                        if ($camp->spend > 0 || $camp->gmv > 0) {
                            $netRev = $camp->gmv * ($camp->net_revenue_ratio ?? 0.781);
                            $totalNetRevenue += $netRev;
                            // COGS eksak: HPP × pcs terjual (fallback rasio utk data lama)
                            $totalCogs += ($camp->unit_cogs > 0 && ($camp->items_sold ?? 0) > 0)
                                ? $camp->unit_cogs * $camp->items_sold
                                : $camp->gmv * ($camp->cogs_ratio ?? 0);
                        }
                    }
                    
                    // Inject to KPI current
                    if (!isset($kpi['current'])) $kpi['current'] = new \stdClass();
                    if (!isset($kpi['previous'])) $kpi['previous'] = new \stdClass();
                    
                    $currGmv = $kpi['current']->gmv ?? 0;
                    $currSpend = $kpi['current']->spend ?? 0;
                    $currOrders = $kpi['current']->orders ?? 0;
                    
                    $kpi['current']->net_revenue = $totalNetRevenue;
                    $kpi['current']->net_profit = $totalNetRevenue - $totalCogs - ($currSpend * 1.11);
                    $kpi['current']->aov_net = $currOrders > 0 ? $totalNetRevenue / $currOrders : 0;
                    
                    // Estimate previous based on current ratios
                    $prevGmv = $kpi['previous']->gmv ?? 0;
                    $prevSpend = $kpi['previous']->spend ?? 0;
                    $prevOrders = $kpi['previous']->orders ?? 0;
                    
                    $revRatio = $currGmv > 0 ? $totalNetRevenue / $currGmv : 0.89;
                    $cogsRatio = $currGmv > 0 ? $totalCogs / $currGmv : 0.60;
                    
                    $kpi['previous']->net_revenue = $prevGmv * $revRatio;
                    $kpi['previous']->net_profit = ($prevGmv * $revRatio) - ($prevGmv * $cogsRatio) - ($prevSpend * 1.11);
                    $kpi['previous']->aov_net = $prevOrders > 0 ? ($prevGmv * $revRatio) / $prevOrders : 0;

                    $metrics = [
                        ['title' => 'Biaya (Topup PPN)', 'key' => 'spend_topup', 'prefix' => 'Rp ', 'suffix' => '', 'cls' => 'red', 'icon' => 'bi-wallet2'],
                        ['title' => 'GMV (Kotor)', 'key' => 'gmv', 'prefix' => 'Rp ', 'suffix' => '', 'cls' => 'green', 'icon' => 'bi-bag-check'],
                        ['title' => 'Net Revenue', 'key' => 'net_revenue', 'prefix' => 'Rp ', 'suffix' => '', 'cls' => 'blue', 'icon' => 'bi-cash-coin'],
                        ['title' => 'Net Profit', 'key' => 'net_profit', 'prefix' => 'Rp ', 'suffix' => '', 'cls' => 'emerald', 'icon' => 'bi-piggy-bank'],
                        ['title' => 'ROAS', 'key' => 'roas', 'prefix' => '', 'suffix' => 'x', 'cls' => 'blue', 'icon' => 'bi-lightning-charge'],
                        ['title' => 'AOV Net', 'key' => 'aov_net', 'prefix' => 'Rp ', 'suffix' => '', 'cls' => 'slate', 'icon' => 'bi-cart-check'],
                        ['title' => 'Pesanan', 'key' => 'orders', 'prefix' => '', 'suffix' => '', 'cls' => 'slate', 'icon' => 'bi-box-seam'],
                        ['title' => 'CVR', 'key' => 'cvr', 'prefix' => '', 'suffix' => '%', 'cls' => 'violet', 'icon' => 'bi-funnel'],
                        ['title' => 'Klik', 'key' => 'clicks', 'prefix' => '', 'suffix' => '', 'cls' => 'amber', 'icon' => 'bi-cursor'],
                        ['title' => 'CPC', 'key' => 'cpc', 'prefix' => 'Rp ', 'suffix' => '', 'cls' => 'amber', 'icon' => 'bi-coin'],
                    ];
                @endphp
                @foreach($metrics as $m)
                    @php
                        $currSpend = $kpi['current']->spend ?? 0;
                        $currGmv = $kpi['current']->gmv ?? 0;
                        $currOrders = $kpi['current']->orders ?? 0;
                        $currClicks = $kpi['current']->clicks ?? 0;
                        $currImpressions = $kpi['current']->impressions ?? 0;

                        $prevSpend = $kpi['previous']->spend ?? 0;
                        $prevGmv = $kpi['previous']->gmv ?? 0;
                        $prevOrders = $kpi['previous']->orders ?? 0;
                        $prevClicks = $kpi['previous']->clicks ?? 0;
                        $prevImpressions = $kpi['previous']->impressions ?? 0;

                        $val = $kpi['current']->{$m['key']} ?? 0;
                        $prevVal = $kpi['previous']->{$m['key']} ?? 0;

                        if($m['key'] === 'roas') {
                            $val = $currSpend > 0 ? round($currGmv / $currSpend, 2) : 0;
                            $prevVal = $prevSpend > 0 ? round($prevGmv / $prevSpend, 2) : 0;
                        } elseif ($m['key'] === 'spend_topup') {
                            $val = $currSpend * 1.11;
                            $prevVal = $prevSpend * 1.11;
                        } elseif ($m['key'] === 'cpc') {
                            $val = $currClicks > 0 ? round($currSpend / $currClicks, 0) : 0;
                            $prevVal = $prevClicks > 0 ? round($prevSpend / $prevClicks, 0) : 0;
                        } elseif ($m['key'] === 'ctr') {
                            $val = $currImpressions > 0 ? round(($currClicks / $currImpressions) * 100, 2) : 0;
                            $prevVal = $prevImpressions > 0 ? round(($prevClicks / $prevImpressions) * 100, 2) : 0;
                        } elseif ($m['key'] === 'cvr') {
                            $val = $currClicks > 0 ? round(($currOrders / $currClicks) * 100, 2) : 0;
                            $prevVal = $prevClicks > 0 ? round(($prevOrders / $prevClicks) * 100, 2) : 0;
                        }

                        $change = $kpi['changes'][$m['key']] ?? 0;
                        if (in_array($m['key'], ['aov', 'aov_net', 'cpc', 'ctr', 'cvr', 'net_revenue', 'net_profit', 'spend_topup'])) {
                            if ($prevVal == 0) {
                                $change = $val > 0 ? 100 : 0;
                            } else {
                                $change = round((($val - $prevVal) / abs($prevVal)) * 100, 2);
                            }
                        }


                        $isUp = $change >= 0;
                        
                        // For cost metrics, going down is good (green). For others, going up is good.
                        if (in_array($m['key'], ['spend', 'cpc', 'spend_topup'])) {
                            $colorClass = $isUp && $change > 0 ? 'color: #dc2626;' : 'color: #16a34a;';
                        } else {
                            $colorClass = $isUp ? 'color: #16a34a;' : 'color: #dc2626;';
                        }
                    @endphp
                    <div class="kpi {{ $m['cls'] }}">
                        <div class="kpi-label">
                            <div class="ico"><i class="bi {{ $m['icon'] }}"></i></div>
                            {{ $m['title'] }}
                        </div>
                        <div class="kpi-value {{ in_array($m['key'], ['spend', 'gmv', 'aov']) ? 'sm' : '' }}" style="font-family: ui-monospace, monospace;">
                            {{ $m['prefix'] }}{{ is_float($val) ? number_format($val, 2, ',', '.') : number_format($val, 0, ',', '.') }}{{ $m['suffix'] }}
                        </div>
                        <div class="kpi-sub">
                            <span style="font-weight:700; {{ $colorClass }}">
                                <i class="bi bi-arrow-{{ $isUp ? 'up-right' : 'down-right' }}"></i> {{ abs($change) }}%
                            </span> 
                            vs rentang lalu
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- INNER TABS FOR KAMPANYE -->
            <div class="dash-tabs-modern mb-3">
                <button class="dash-tab-sm inner-tab-btn active" data-inner-target="inner-campaign-gms"><i class="bi bi-lightning-charge"></i> GMV Max</button>
                <button class="dash-tab-sm inner-tab-btn" data-inner-target="inner-campaign-individual"><i class="bi bi-list-ul"></i> Iklan Individual</button>
            </div>

            <div id="inner-campaign-gms" class="inner-tab-pane active" style="display: block;">
                <div class="dash-sec"><i class="bi bi-gear"></i> GMV Max</div>
                <div class="dpanel p-4 mb-4" style="background: var(--card-bg);">
                    @if($autoCampaign)
                        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                                    <h5 style="margin: 0; font-weight: 700; color: var(--text);">{{ $autoCampaign->campaign_name ?: 'GMV Max (Auto)' }}</h5>
                                    @if($autoCampaign->campaign_status === 'ongoing')
                                        <span class="badge" style="background: #16a34a; color: #fff; font-weight: 600; border-radius: 6px;">Berjalan</span>
                                    @elseif($autoCampaign->campaign_status === 'paused')
                                        <span class="badge" style="background: #eab308; color: #fff; font-weight: 600; border-radius: 6px;">Jeda</span>
                                    @elseif($autoCampaign->campaign_status)
                                        <span class="badge bg-secondary" style="border-radius: 6px;">{{ ucfirst($autoCampaign->campaign_status) }}</span>
                                    @endif
                                </div>
                                <div style="font-family: ui-monospace, monospace; font-size: .85rem; color: var(--dsh-muted);">
                                    Camp ID: {{ $autoCampaign->channel_campaign_id }} &bull; Budget: <span style="font-weight: 600; color: var(--text);">{{ $autoCampaign->campaign_budget > 0 ? 'Rp ' . number_format($autoCampaign->campaign_budget, 0, ',', '.') : 'Unlimited' }}</span>
                                </div>
                            </div>
                            
                            <div style="text-align: right; background: rgba(37, 99, 235, 0.05); border: 1px solid rgba(37, 99, 235, 0.1); padding: 12px 24px; border-radius: 12px; min-width: 150px;">
                                <div style="font-size: .75rem; font-weight: 600; color: var(--dsh-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px;">Target ROAS</div>
                                <div style="font-weight: 800; font-size: 1.5rem; color: var(--dsh-accent); font-variant-numeric: tabular-nums;">
                                    {{ $autoCampaign->target_roas ? number_format($autoCampaign->target_roas, 2).'x' : 'Auto' }}
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-3" style="color: var(--dsh-muted); font-size: .9rem;">
                            <i class="bi bi-info-circle mb-2" style="font-size: 1.5rem; display: block;"></i>
                            Data GMV Max belum tersinkronisasi.
                        </div>
                    @endif
                </div>

                <div class="dash-sec"><i class="bi bi-lightning-charge"></i> Produk GMV Max</div>
                <div class="dpanel">
                    @include('marketplace.partials._gms_item_table', ['gmsItems' => $gmsItems, 'globalRoas' => $adsSetting->target_roas ?? null])
                </div>
            </div>

            <div id="inner-campaign-individual" class="inner-tab-pane" style="display: none;">
                <div class="dash-sec"><i class="bi bi-megaphone"></i> Iklan Individual</div>
            
            @php
                $totalBoncos = 0;
                $totalHiddenGem = 0;
                $totalSpendBoncos = 0;
                $individualCamps = $campaigns->filter(fn($c) => !str_starts_with($c->channel_campaign_id, 'GMS-'));
                foreach($individualCamps as $c) {
                    $r = $c->spend > 0 ? $c->gmv / $c->spend : 0;
                    if ($c->spend > 50000 && $r < 1.5) { $totalBoncos++; $totalSpendBoncos += $c->spend; }
                    elseif ($r >= 5.0 && $c->spend > 10000) { $totalHiddenGem++; }
                }
            @endphp
            
            <div class="dash-panels mb-3" style="grid-template-columns: 1fr;">
                <div class="dpanel p-3" style="border-left: 4px solid {{ $totalBoncos > 0 ? '#dc2626' : '#16a34a' }}; background: var(--dsh-bg);">
                    <div class="d-flex align-items-center gap-2 mb-1" style="font-weight: 700; color: {{ $totalBoncos > 0 ? '#dc2626' : '#16a34a' }}; font-size: 0.85rem;">
                        <i class="bi {{ $totalBoncos > 0 ? 'bi-exclamation-triangle' : 'bi-check-circle' }}"></i> Ringkasan
                    </div>
                    <div style="font-size: 0.72rem; color: var(--dsh-muted);">
                        @if($totalBoncos > 0)
                            <b>{{ $totalBoncos }} kampanye</b> boros biaya sebesar <b>Rp {{ number_format($totalSpendBoncos, 0, ',', '.') }}</b>.
                        @else
                            Tidak ada kampanye boros.
                        @endif
                        @if($totalHiddenGem > 0)
                            <b>{{ $totalHiddenGem }} produk</b> punya efisiensi tinggi.
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="dpanel">
                @include('marketplace.partials._campaign_table', ['campaigns' => $individualCamps])
            </div>
            </div> <!-- CLOSE inner-campaign-individual -->

        </div>

        <!-- TAB PERFORMA PRODUK (GMV MAX) -->
        <div class="tab-pane" id="tab-items">


            <div class="dash-sec"><i class="bi bi-robot"></i> Insight Produk</div>
            <div class="dash-panels mb-4" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
                <div class="dpanel p-3" style="border-left: 4px solid var(--dsh-border)" id="insightHealth"></div>
                <div class="dpanel p-3" style="border-left: 4px solid var(--dsh-border)" id="insightTraffic"></div>
                <div class="dpanel p-3" style="border-left: 4px solid var(--dsh-border)" id="insightTime"></div>
            </div>

            <div class="dash-panels mt-4 mb-4" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
                {{-- BARIS 1: TRAFFIC --}}
                {{-- CHART IMPRESI --}}
                <div class="dpanel p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="text-muted small fw-bold mt-0 mb-0"><i class="bi bi-eye text-info"></i> Impresi</div>
                    </div>
                    <div style="font-size: 0.72rem; color: var(--dsh-muted); opacity: 0.85; margin-bottom: 0.5rem;">
                        Top 5.
                    </div>
                    <div style="position: relative; height: 180px; display: flex; justify-content: center; align-items: center;">
                        @if(empty($itemPerformance) || count($itemPerformance) === 0)
                            <div style="color: var(--dsh-muted); font-size: 0.8rem; text-align: center;">Belum ada data.</div>
                        @else
                            <canvas id="chartImpressions"></canvas>
                        @endif
                    </div>
                </div>

                {{-- CHART KLIK --}}
                <div class="dpanel p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="text-muted small fw-bold mt-0 mb-0"><i class="bi bi-cursor text-primary"></i> Klik</div>
                    </div>
                    <div style="font-size: 0.72rem; color: var(--dsh-muted); opacity: 0.85; margin-bottom: 0.5rem;">
                        Top 5.
                    </div>
                    <div style="position: relative; height: 180px; display: flex; justify-content: center; align-items: center;">
                        @if(empty($itemPerformance) || count($itemPerformance) === 0)
                            <div style="color: var(--dsh-muted); font-size: 0.8rem; text-align: center;">Belum ada data.</div>
                        @else
                            <canvas id="chartClicks"></canvas>
                        @endif
                    </div>
                </div>

                {{-- CHART CTR --}}
                <div class="dpanel p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="text-muted small fw-bold mt-0 mb-0"><i class="bi bi-hand-index text-warning"></i> CTR</div>
                    </div>
                    <div style="font-size: 0.72rem; color: var(--dsh-muted); opacity: 0.85; margin-bottom: 0.5rem;">
                        Top 5.
                    </div>
                    <div style="position: relative; height: 180px; display: flex; justify-content: center; align-items: center;">
                        @if(empty($itemPerformance) || count($itemPerformance) === 0)
                            <div style="color: var(--dsh-muted); font-size: 0.8rem; text-align: center;">Belum ada data.</div>
                        @else
                            <canvas id="chartCtr"></canvas>
                        @endif
                    </div>
                </div>

                {{-- BARIS 2: CONVERSION --}}
                {{-- CHART PESANAN --}}
                <div class="dpanel p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="text-muted small fw-bold mt-0 mb-0"><i class="bi bi-box-seam text-success"></i> Pesanan</div>
                    </div>
                    <div style="font-size: 0.72rem; color: var(--dsh-muted); opacity: 0.85; margin-bottom: 0.5rem;">
                        Top 5.
                    </div>
                    <div style="position: relative; height: 180px; display: flex; justify-content: center; align-items: center;">
                        @if(empty($itemPerformance) || count($itemPerformance) === 0)
                            <div style="color: var(--dsh-muted); font-size: 0.8rem; text-align: center;">Belum ada data.</div>
                        @else
                            <canvas id="chartOrders"></canvas>
                        @endif
                    </div>
                </div>

                {{-- CHART CVR --}}
                <div class="dpanel p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="text-muted small fw-bold mt-0 mb-0"><i class="bi bi-funnel" style="color: #a855f7;"></i> CVR</div>
                    </div>
                    <div style="font-size: 0.72rem; color: var(--dsh-muted); opacity: 0.85; margin-bottom: 0.5rem;">
                        Top 5.
                    </div>
                    <div style="position: relative; height: 180px; display: flex; justify-content: center; align-items: center;">
                        @if(empty($itemPerformance) || count($itemPerformance) === 0)
                            <div style="color: var(--dsh-muted); font-size: 0.8rem; text-align: center;">Belum ada data.</div>
                        @else
                            <canvas id="chartCvr"></canvas>
                        @endif
                    </div>
                </div>

                {{-- CHART CPA --}}
                <div class="dpanel p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="text-muted small fw-bold mt-0 mb-0"><i class="bi bi-cash-coin text-danger"></i> CPA</div>
                    </div>
                    <div style="font-size: 0.72rem; color: var(--dsh-muted); opacity: 0.85; margin-bottom: 0.5rem;">
                        Top 5.
                    </div>
                    <div style="position: relative; height: 180px; display: flex; justify-content: center; align-items: center;">
                        @if(empty($itemPerformance) || count($itemPerformance) === 0)
                            <div style="color: var(--dsh-muted); font-size: 0.8rem; text-align: center;">Belum ada data.</div>
                        @else
                            <canvas id="chartCpa"></canvas>
                        @endif
                    </div>
                </div>

                {{-- BARIS 3: FINANCIAL --}}
                {{-- CHART BIAYA --}}
                <div class="dpanel p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="text-muted small fw-bold mt-0 mb-0"><i class="bi bi-wallet2 text-danger"></i> Biaya</div>
                    </div>
                    <div style="font-size: 0.72rem; color: var(--dsh-muted); opacity: 0.85; margin-bottom: 0.5rem;">
                        Top 5.
                    </div>
                    <div style="position: relative; height: 180px; display: flex; justify-content: center; align-items: center;">
                        @if(empty($itemPerformance) || count($itemPerformance) === 0)
                            <div style="color: var(--dsh-muted); font-size: 0.8rem; text-align: center;">Belum ada data.</div>
                        @else
                            <canvas id="chartSpend"></canvas>
                        @endif
                    </div>
                </div>

                {{-- CHART GMV --}}
                <div class="dpanel p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="text-muted small fw-bold mt-0 mb-0"><i class="bi bi-bag-check text-success"></i> GMV</div>
                    </div>
                    <div style="font-size: 0.72rem; color: var(--dsh-muted); opacity: 0.85; margin-bottom: 0.5rem;">
                        Top 5.
                    </div>
                    <div style="position: relative; height: 180px; display: flex; justify-content: center; align-items: center;">
                        @if(empty($itemPerformance) || count($itemPerformance) === 0)
                            <div style="color: var(--dsh-muted); font-size: 0.8rem; text-align: center;">Belum ada data.</div>
                        @else
                            <canvas id="chartGmv"></canvas>
                        @endif
                    </div>
                </div>

                {{-- CHART ROAS --}}
                <div class="dpanel p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="text-muted small fw-bold mt-0 mb-0"><i class="bi bi-lightning-charge text-warning"></i> ROAS</div>
                    </div>
                    <div style="font-size: 0.72rem; color: var(--dsh-muted); opacity: 0.85; margin-bottom: 0.5rem;">
                        Top 5.
                    </div>
                    <div style="position: relative; height: 180px; display: flex; justify-content: center; align-items: center;">
                        @if(empty($itemPerformance) || count($itemPerformance) === 0)
                            <div style="color: var(--dsh-muted); font-size: 0.8rem; text-align: center;">Belum ada data.</div>
                        @else
                            <canvas id="chartRoas"></canvas>
                        @endif
                    </div>
                </div>
            </div>

            <div class="dash-sec mt-2 mb-2"><i class="bi bi-stars text-warning"></i> Ringkasan Produk</div>
            <div class="dpanel p-3 mb-4" id="productInsights" style="border-left: 4px solid #a855f7;">
                <div class="d-flex align-items-center" style="color: var(--dsh-muted); font-size: 0.8rem;">
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    Memuat data...
                </div>
            </div>

            <div class="dash-sec"><i class="bi bi-box-seam"></i> Semua Produk</div>
            
            <div class="dash-panels mb-3" style="grid-template-columns: 1fr;">
                <div class="dpanel p-3" style="border-left: 4px solid var(--dsh-accent); background: var(--dsh-bg);">
                    <div style="font-size: 0.72rem; color: var(--dsh-muted);">
                        CPC dan GMV Max.
                    </div>
                </div>
            </div>

            <div class="dpanel">
                <div class="table-responsive">
                    <table class="dpanel-table">
                        <thead>
                            <tr>
                                <th>Produk / Kampanye</th>
                                <th>Tipe</th>
                                <th>Status</th>
                                <th class="text-end">Biaya (Spend)</th>
                                <th class="text-end">GMV</th>
                                <th class="text-end">ROAS</th>
                                <th class="text-end">Impresi</th>
                                <th class="text-end">Klik</th>
                                <th class="text-end">Pesanan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($itemPerformance ?? [] as $item)
                                @php
                                    $itemRoas = $item->spend > 0 ? $item->gmv / $item->spend : 0;
                                    $isGms = str_starts_with($item->channel_campaign_id ?? '', 'GMS-');
                                    $typeLabel = $isGms ? 'GMV Max' : 'CPC';
                                    $typeColor = $isGms ? '#2563eb' : '#ca8a04';
                                    $typeBg = $isGms ? 'rgba(37,99,235,0.1)' : 'rgba(234,179,8,0.1)';
                                    $statusLabel = $item->campaign_status ?? '-';
                                    $statusColor = $statusLabel === 'ongoing' ? '#16a34a' : ($statusLabel === 'paused' ? '#eab308' : '#64748b');
                                @endphp
                                <tr>
                                    <td style="padding-top: 0.8rem; padding-bottom: 0.8rem;">
                                        <div style="font-weight: 700; color: var(--text); white-space: normal; max-width: 300px;">
                                            {{ $item->item_name ?? $item->campaign_name ?? 'Produk Tidak Diketahui' }}
                                        </div>
                                        <div style="font-family: ui-monospace, monospace; font-size: .7rem; color: var(--dsh-muted); display: flex; align-items: center; gap: 6px; margin-top: 4px;">
                                            @if($item->channel_item_id)
                                                <span>ID: {{ $item->channel_item_id }}</span>
                                            @endif
                                            @if($item->item_sku)
                                                <span>SKU: {{ $item->item_sku }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span style="background: {{ $typeBg }}; color: {{ $typeColor }}; padding: 2px 6px; border-radius: 4px; font-weight: 700; font-family: sans-serif; font-size: 0.65rem;">{{ $typeLabel }}</span>
                                    </td>
                                    <td>
                                        <span style="color: {{ $statusColor }}; font-weight: 700; font-size: 0.72rem; text-transform: capitalize;">{{ $statusLabel }}</span>
                                    </td>
                                    <td class="text-end" style="font-family: ui-monospace, monospace; font-weight:700; color: #dc2626;">
                                        Rp {{ number_format($item->spend, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end" style="font-family: ui-monospace, monospace; font-weight:700; color: #16a34a;">
                                        Rp {{ number_format($item->gmv, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end" style="font-family: ui-monospace, monospace; font-weight:700; color: {{ $itemRoas >= 4.0 ? '#16a34a' : ($itemRoas >= 2.0 ? '#eab308' : '#dc2626') }};">
                                        {{ number_format($itemRoas, 2) }}x
                                    </td>
                                    <td class="text-end" style="font-family: ui-monospace, monospace; color: var(--dsh-muted);">
                                        {{ number_format($item->impressions, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end" style="font-family: ui-monospace, monospace; color: var(--dsh-muted);">
                                        {{ number_format($item->clicks, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end" style="font-family: ui-monospace, monospace; color: var(--dsh-muted);">
                                        {{ number_format($item->orders, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4" style="color: var(--dsh-muted); font-size: .8rem;">
                                        Belum ada data performa produk.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- SINKRONISASI LOG (MODAL) -->
    @endif
</div>
</div>

<!-- Modal GMS Settings -->
<div class="modal fade" id="modalGmsSettings" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:16px; background: var(--card-bg); border: 1px solid var(--card-border); box-shadow: var(--card-shadow);">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" style="color: var(--text);">Pengaturan GMV Max</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formGmsSettings" onsubmit="submitGmsSettings(event)">
                        <label style="font-size: .75rem; font-weight: 650; color: var(--dsh-muted); display: block; margin-bottom: .4rem;">ID Kampanye GMV Max (Opsional)</label>
                        <input type="text" id="gmsCampaignId" class="form-control" placeholder="Contoh: 123456789" style="border-radius: 8px; font-size: .85rem; background: var(--bg); color: var(--text); border-color: var(--dsh-border);">
                        <small style="font-size: 0.65rem; color: var(--dsh-muted);">*Biarkan kosong untuk mengubah kampanye GMV Max utama/seluruh toko.</small>
                    </div>

                    <div class="mb-4">
                        <label style="font-size: .75rem; font-weight: 650; color: var(--dsh-muted); display: block; margin-bottom: .4rem;">Target ROAS (x)</label>
                        <input type="number" id="gmsRoasTarget" class="form-control" placeholder="Contoh: 5.5, atau 0 untuk Auto" style="border-radius: 8px; font-size: .85rem; background: var(--bg); color: var(--text); border-color: var(--dsh-border);" min="0" step="0.1">
                        <small style="font-size: 0.65rem; color: var(--dsh-muted);">*Isi 0 untuk Auto Bidding. Maksimal 1 angka di belakang koma.</small>
                    </div>

                    <button type="submit" id="btnSubmitGmsSettings" class="btn w-100 fw-bold" style="background: var(--dsh-accent); color: #fff; border-radius: 12px; padding: .6rem;">
                        <i class="bi bi-cloud-arrow-up"></i> Simpan Pengaturan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Sync Manual -->
<style>
#syncRangeOptions .sync-range-card { display:flex; align-items:center; justify-content:space-between; gap:.6rem; border:1px solid var(--dsh-border); border-radius:10px; padding:.5rem .7rem; cursor:pointer; margin:0; background: var(--bg); transition: border-color .15s, box-shadow .15s; }
#syncRangeOptions .sync-range-card:hover { border-color: var(--dsh-accent); }
#syncRangeOptions .sync-range-card:has(input:checked) { border-color: var(--dsh-accent); box-shadow: 0 0 0 1px var(--dsh-accent) inset; }
#syncRangeOptions input[type=radio] { accent-color: var(--dsh-accent); }
</style>
<div class="modal fade" id="modalSyncAds" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px; background: var(--card-bg); border: 1px solid var(--card-border); box-shadow: var(--card-shadow);">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold" style="color: var(--text); margin-bottom:.1rem;">Manual Sync Shopee Ads</h5>
                    <div style="font-size:.72rem; color: var(--dsh-muted);">Tarik data performa iklan terbaru langsung dari Shopee</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Form State -->
                <form id="formSyncAds" action="/api/marketplace/ads-daily/sync" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label style="font-size: .75rem; font-weight: 650; color: var(--dsh-muted); display: block; margin-bottom: .4rem;">Toko Target</label>
                        <select name="store_id" class="form-control" style="border-radius: 10px; font-size: .85rem; background: var(--bg); color: var(--text); border-color: var(--dsh-border);" required>
                            <option value="all">&#127970; Semua Toko (bergiliran)</option>
                            @foreach($stores as $s)
                                <option value="{{ $s->id }}" {{ isset($storeId) && $storeId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <label style="font-size: .75rem; font-weight: 650; color: var(--dsh-muted); display: block; margin-bottom: .4rem;">Jangka Waktu Sinkronisasi</label>
                    <div id="syncRangeOptions" style="display:grid; gap:.45rem; margin-bottom:.6rem;">
                        <label class="sync-range-card">
                            <span style="display:flex; align-items:center; gap:.6rem;">
                                <input type="radio" name="sync_type" value="today">
                                <span>
                                    <span style="display:block; font-size:.82rem; font-weight:650; color:var(--text);">Hari Ini</span>
                                    <span style="display:block; font-size:.68rem; color:var(--dsh-muted);">Paling cepat &bull; &plusmn;10 detik</span>
                                </span>
                            </span>
                            <i class="bi bi-sun" style="color:var(--dsh-muted);"></i>
                        </label>
                        <label class="sync-range-card">
                            <span style="display:flex; align-items:center; gap:.6rem;">
                                <input type="radio" name="sync_type" value="yesterday">
                                <span>
                                    <span style="display:block; font-size:.82rem; font-weight:650; color:var(--text);">Kemarin</span>
                                    <span style="display:block; font-size:.68rem; color:var(--dsh-muted);">&plusmn;10 detik</span>
                                </span>
                            </span>
                            <i class="bi bi-moon" style="color:var(--dsh-muted);"></i>
                        </label>
                        <label class="sync-range-card">
                            <span style="display:flex; align-items:center; gap:.6rem;">
                                <input type="radio" name="sync_type" value="1_week" checked>
                                <span>
                                    <span style="display:block; font-size:.82rem; font-weight:650; color:var(--text);">1 Minggu Terakhir</span>
                                    <span style="display:block; font-size:.68rem; color:var(--dsh-muted);">Direkomendasikan &bull; &plusmn;30 detik</span>
                                </span>
                            </span>
                            <i class="bi bi-calendar-week" style="color:var(--dsh-muted);"></i>
                        </label>
                        <label class="sync-range-card">
                            <span style="display:flex; align-items:center; gap:.6rem;">
                                <input type="radio" name="sync_type" value="1_month">
                                <span>
                                    <span style="display:block; font-size:.82rem; font-weight:650; color:var(--text);">1 Bulan Terakhir</span>
                                    <span style="display:block; font-size:.68rem; color:var(--dsh-muted);">&plusmn;1&ndash;2 menit</span>
                                </span>
                            </span>
                            <i class="bi bi-calendar-month" style="color:var(--dsh-muted);"></i>
                        </label>
                        <label class="sync-range-card">
                            <span style="display:flex; align-items:center; gap:.6rem;">
                                <input type="radio" name="sync_type" value="2_months">
                                <span>
                                    <span style="display:block; font-size:.82rem; font-weight:650; color:var(--text);">2 Bulan Terakhir</span>
                                    <span style="display:block; font-size:.68rem; color:var(--dsh-muted);">&plusmn;2&ndash;3 menit &bull; progress live di sini</span>
                                </span>
                            </span>
                            <i class="bi bi-calendar-range" style="color:var(--dsh-muted);"></i>
                        </label>
                        <label class="sync-range-card">
                            <span style="display:flex; align-items:center; gap:.6rem;">
                                <input type="radio" name="sync_type" value="3_months">
                                <span>
                                    <span style="display:block; font-size:.82rem; font-weight:650; color:var(--text);">3 Bulan Terakhir</span>
                                    <span style="display:block; font-size:.68rem; color:var(--dsh-muted);">Pintar &mdash; hanya tarik tanggal yang belum ada</span>
                                </span>
                            </span>
                            <span style="font-size:.6rem; font-weight:700; letter-spacing:.03em; background:rgba(59,130,246,.14); color:#3b82f6; padding:.18rem .5rem; border-radius:999px;">BACKGROUND</span>
                        </label>
                        <label class="sync-range-card">
                            <span style="display:flex; align-items:center; gap:.6rem;">
                                <input type="radio" name="sync_type" value="6_months">
                                <span>
                                    <span style="display:block; font-size:.82rem; font-weight:650; color:var(--text);">6 Bulan Terakhir</span>
                                    <span style="display:block; font-size:.68rem; color:var(--dsh-muted);">Maksimum riwayat &bull; hanya tarik yang kurang</span>
                                </span>
                            </span>
                            <span style="font-size:.6rem; font-weight:700; letter-spacing:.03em; background:rgba(59,130,246,.14); color:#3b82f6; padding:.18rem .5rem; border-radius:999px;">BACKGROUND</span>
                        </label>
                        <label class="sync-range-card">
                            <span style="display:flex; align-items:center; gap:.6rem;">
                                <input type="radio" name="sync_type" value="custom">
                                <span>
                                    <span style="display:block; font-size:.82rem; font-weight:650; color:var(--text);">Ikut Filter Tanggal</span>
                                    <span style="display:block; font-size:.68rem; color:var(--dsh-muted);">Sesuai rentang filter di halaman ini</span>
                                </span>
                            </span>
                            <i class="bi bi-sliders" style="color:var(--dsh-muted);"></i>
                        </label>
                    </div>
                    <div style="font-size:.68rem; color:var(--dsh-muted); margin-bottom:1rem;">
                        <i class="bi bi-info-circle"></i> Rentang lebih dari 2 bulan otomatis diproses di background agar aman dari batas API Shopee.
                    </div>

                    <button type="submit" class="btn w-100 fw-bold" style="background: var(--dsh-accent); color: #fff; border-radius: 12px; padding: .6rem;">
                        <i class="bi bi-cloud-download"></i> Jalankan Sync
                    </button>
                </form>

                <!-- Loading State -->
                <div id="loadingSyncAds" style="display: none; padding:.25rem 0;">
                    <div style="text-align:center; margin-bottom:1rem;">
                        <h6 class="fw-bold" style="color: var(--text); margin-bottom:.2rem;"><i class="bi bi-arrow-repeat spin-icon" style="display: inline-block;"></i> Sedang Menarik Data&hellip;</h6>
                        <div id="syncStatusLine" style="font-size:.75rem; color:var(--dsh-muted); min-height:1.2em;">Menghubungkan ke server&hellip;</div>
                    </div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="progress flex-grow-1" style="height: 10px; border-radius: 10px; background: var(--dsh-border);">
                            <div id="syncProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%; background: var(--dsh-accent);"></div>
                        </div>
                        <span id="syncPercentText" style="font-size:.72rem; font-weight:700; color:var(--text); min-width:2.6rem; text-align:right;">0%</span>
                    </div>
                    <div id="syncLogs" style="background: rgba(0,0,0,0.4); border: 1px solid var(--dsh-border); border-radius: 8px; padding: 10px; font-family: monospace; font-size: 0.72rem; color: #a1a1aa; height: 130px; overflow-y: auto; text-align: left; line-height: 1.5;"></div>
                    <div style="font-size:.66rem; color:var(--dsh-muted); margin-top:.5rem; text-align:center;">
                        <i class="bi bi-shield-check"></i> Aman menutup jendela ini &mdash; proses tetap berjalan di server.
                    </div>
                </div>

                <!-- Result State (sukses / background / warning / error) -->
                <div id="resultSyncAds" style="display: none; text-align: center; padding: 1.25rem 0;">
                    <div id="resultSyncIcon" style="font-size: 3rem; margin-bottom: .75rem;"></div>
                    <h6 id="resultSyncTitle" class="fw-bold" style="color: var(--text);"></h6>
                    <div id="resultSyncMessage" style="font-size:.78rem; color:var(--dsh-muted); max-width: 320px; margin: 0 auto;"></div>
                    <div id="resultSyncErrors" style="display:none; text-align:left; font-size:.72rem; color:var(--text); background:var(--bg); border:1px solid var(--dsh-border); border-radius:8px; padding:.6rem .75rem; margin:.75rem auto 0; max-width:340px; max-height:120px; overflow-y:auto;"></div>
                    <div class="d-flex gap-2 justify-content-center" style="margin-top:1rem;">
                        <button type="button" id="btnSyncRetry" class="btn fw-bold" style="display:none; border:1px solid var(--dsh-border); color:var(--text); border-radius:10px; font-size:.8rem; padding:.45rem 1rem;">
                            <i class="bi bi-arrow-counterclockwise"></i> Coba Lagi
                        </button>
                        <button type="button" id="btnSyncClose" class="btn fw-bold" style="background: var(--dsh-accent); color:#fff; border-radius:10px; font-size:.8rem; padding:.45rem 1rem;">
                            Tutup &amp; Muat Ulang
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


    <!-- MODAL CAMPAIGN HOURLY -->
    <div class="modal fade" id="modalCampaignHourly" tabindex="-1" aria-labelledby="modalCampaignHourlyLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="background: var(--dsh-bg); color: var(--text); border: 1px solid var(--dsh-border); border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
                <div class="modal-header" style="border-bottom: 1px solid var(--dsh-border);">
                    <h6 class="modal-title" id="modalCampaignHourlyLabel" style="font-weight: 700;">
                        <i class="bi bi-clock-history text-primary"></i> Performa 24 Jam (Hari Ini)
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: var(--btn-close-filter);"></button>
                </div>
                <div class="modal-body" style="background: var(--card-bg);">
                    <div id="campaignHourlySubtitle" style="font-size: 0.85rem; font-weight: 600; color: var(--text); margin-bottom: 1rem;">Data 24 jam</div>
                    <div id="campaignHourlyLoader" style="text-align: center; padding: 3rem 0; color: var(--dsh-muted);">
                        <div class="spinner-border text-primary mb-2" role="status"></div>
                        <div style="font-size: 0.8rem;">Memuat data...</div>
                    </div>
                    <div id="campaignHourlyContent" style="display: none;">
                        <div style="position: relative; height: 300px; width: 100%;">
                            <canvas id="campaignHourlyChartCanvas"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    window.clearAdsData = async function() {
        const jawaban = prompt('PERINGATAN: SEMUA data performa iklan + riwayat sync akan dihapus PERMANEN dan harus di-backfill ulang dari Shopee.\n\nKetik HAPUS (huruf besar) untuk melanjutkan:');
        if (jawaban !== 'HAPUS') {
            if (jawaban !== null) showToast('Dibatalkan — ketik HAPUS persis untuk konfirmasi.');
            return;
        }
        {
            try {
                const res = await fetch("{{ route('marketplace.ads.clear') }}", {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await res.json();
                if (res.ok) {
                    showToast('Data iklan berhasil dibersihkan!');
                    location.reload();
                } else {
                    throw new Error(data.message || 'Terjadi kesalahan.');
                }
            } catch (err) {
                showToast('Gagal membersihkan data: ' + err.message);
            }
        }
    };

    const formSync = document.getElementById('formSyncAds');
    if (formSync) {
        const syncModalEl = document.getElementById('modalSyncAds');
        const syncLoadingEl = document.getElementById('loadingSyncAds');
        const syncResultEl = document.getElementById('resultSyncAds');
        let syncRunning = false;

        const showSyncState = (state) => {
            formSync.style.display = state === 'form' ? 'block' : 'none';
            syncLoadingEl.style.display = state === 'loading' ? 'block' : 'none';
            syncResultEl.style.display = state === 'result' ? 'block' : 'none';
        };

        const showSyncResult = (opts) => {
            const iconEl = document.getElementById('resultSyncIcon');
            iconEl.innerHTML = opts.icon;
            iconEl.style.color = opts.color;
            document.getElementById('resultSyncTitle').innerText = opts.title;
            document.getElementById('resultSyncMessage').innerText = opts.message || '';
            const errBox = document.getElementById('resultSyncErrors');
            if (opts.errors && opts.errors.length) {
                errBox.innerHTML = opts.errors.map(er => '<div style="margin-bottom:.35rem;"><i class="bi bi-exclamation-triangle" style="color:#f59e0b;"></i> ' + er + '</div>').join('');
                errBox.style.display = 'block';
            } else {
                errBox.style.display = 'none';
            }
            document.getElementById('btnSyncRetry').style.display = opts.canRetry ? 'inline-block' : 'none';
            showSyncState('result');
            if (opts.autoReload) setTimeout(() => location.reload(), 1800);
        };

        document.getElementById('btnSyncRetry').addEventListener('click', () => showSyncState('form'));
        document.getElementById('btnSyncClose').addEventListener('click', () => location.reload());
        if (syncModalEl) syncModalEl.addEventListener('show.bs.modal', () => { if (!syncRunning) showSyncState('form'); });

        formSync.addEventListener('submit', async function(e) {
            e.preventDefault();
            if (syncRunning) return;
            syncRunning = true;

            const form = this;
            showSyncState('loading');

            const logEl = document.getElementById('syncLogs');
            const progressBar = document.getElementById('syncProgressBar');
            const percentText = document.getElementById('syncPercentText');
            const statusLine = document.getElementById('syncStatusLine');

            const liveProgress = document.getElementById('liveSyncProgress');
            const liveBar = document.getElementById('liveSyncBar');
            const livePercent = document.getElementById('liveSyncPercent');
            const liveLog = document.getElementById('liveSyncLog');
            if (liveProgress) liveProgress.style.display = 'block';

            logEl.innerHTML = '<div style="color: #4ade80;">> Menghubungkan ke server...</div>';
            progressBar.style.width = '0%';
            percentText.innerText = '0%';
            statusLine.innerText = 'Menghubungkan ke server\u2026';

            let savedData = 0;
            let allErrors = [];
            let isDone = false;
            let doneStatus = null;
            let doneMessage = '';

            try {
                const formData = new FormData(form);
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/x-ndjson',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });

                if (!res.ok) {
                    throw new Error('Terjadi kesalahan koneksi (' + res.status + ')');
                }

                const reader = res.body.getReader();
                const decoder = new TextDecoder();

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;

                    const chunkStr = decoder.decode(value, { stream: true });
                    const lines = chunkStr.split('\n').filter(line => line.trim() !== '');

                    for (const line of lines) {
                        try {
                            const data = JSON.parse(line);

                            if (data.type === 'log' || data.type === 'info' || data.type === 'done') {
                                const color = data.type === 'done' ? '#60a5fa' : (data.type === 'info' ? '#facc15' : '#a1a1aa');
                                logEl.innerHTML += '<div style="color: ' + color + ';">> ' + data.message + '</div>';
                                logEl.scrollTop = logEl.scrollHeight;
                                statusLine.innerText = data.message;
                                if (liveLog) liveLog.innerText = '> ' + data.message;
                            }

                            if (data.progress !== undefined && data.progress !== null) {
                                const pct = Math.min(100, Math.round(data.progress));
                                progressBar.style.width = pct + '%';
                                percentText.innerText = pct + '%';
                                if (liveBar) liveBar.style.width = pct + '%';
                                if (livePercent) livePercent.innerText = pct + '%';
                            }

                            if (data.type === 'done') {
                                isDone = true;
                                savedData = data.saved || 0;
                                doneStatus = data.status || 'success';
                                doneMessage = data.message || '';
                                if (data.errors && data.errors.length > 0) {
                                    allErrors = data.errors;
                                }
                            }
                        } catch (parseErr) {
                            console.error('Failed to parse NDJSON line:', line, parseErr);
                        }
                    }
                }

                syncRunning = false;

                if (doneStatus === 'queued') {
                    showSyncResult({
                        icon: '<i class="bi bi-clock-history"></i>',
                        color: '#3b82f6',
                        title: 'Diproses di Background',
                        message: doneMessage || 'Rentang panjang diproses bertahap di server. Pantau progresnya di bagian Riwayat Sync.',
                        canRetry: false
                    });
                } else if (allErrors.length > 0) {
                    showSyncResult({
                        icon: '<i class="bi bi-exclamation-circle-fill"></i>',
                        color: '#f59e0b',
                        title: 'Selesai dengan Catatan',
                        message: savedData > 0 ? savedData + ' baris data berhasil diperbarui, tapi ada kendala:' : 'Sync tidak selesai sepenuhnya:',
                        errors: allErrors,
                        canRetry: true
                    });
                } else if (!isDone) {
                    showSyncResult({
                        icon: '<i class="bi bi-wifi-off"></i>',
                        color: '#ef4444',
                        title: 'Koneksi Terputus',
                        message: 'Streaming terputus sebelum selesai. Tenang \u2014 proses tetap dilanjutkan di server; cek Riwayat Sync beberapa saat lagi.',
                        canRetry: true
                    });
                } else {
                    showSyncResult({
                        icon: '<i class="bi bi-check-circle-fill"></i>',
                        color: '#16a34a',
                        title: 'Sinkronisasi Selesai!',
                        message: savedData + ' baris data diperbarui. Halaman akan dimuat ulang\u2026',
                        autoReload: true
                    });
                }
            } catch (err) {
                syncRunning = false;
                showSyncResult({
                    icon: '<i class="bi bi-x-circle-fill"></i>',
                    color: '#ef4444',
                    title: 'Gagal Terhubung',
                    message: err.message,
                    canRetry: true
                });
            }
        });
    }

    // ==========================================
    // CAMPAIGN HOURLY MODAL SCRIPT
    // ==========================================
    let campaignHourlyChartObj = null;

    window.openCampaignHourlyModal = function(campaignId, campaignName) {
        const storeId = document.querySelector('select[name="store_id"]').value;
        const modalEl = document.getElementById('modalCampaignHourly');
        const modal = new bootstrap.Modal(modalEl);
        
        document.getElementById('campaignHourlySubtitle').innerHTML = `Kampanye: <span style="color:var(--dsh-primary);">${campaignName}</span>`;
        document.getElementById('campaignHourlyLoader').style.display = 'block';
        document.getElementById('campaignHourlyContent').style.display = 'none';
        
        modal.show();

        fetch(`{{ route('marketplace.ads.campaign.hourly') }}?store_id=${storeId}&campaign_id=${campaignId}`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('campaignHourlyLoader').style.display = 'none';
                if (data.status === 'success') {
                    document.getElementById('campaignHourlyContent').style.display = 'block';
                    renderCampaignHourlyChart(data.data.campaign_list?.[0]?.daily_performance || []);
                } else {
                    document.getElementById('campaignHourlyLoader').style.display = 'block';
                    document.getElementById('campaignHourlyLoader').innerHTML = `<i class="bi bi-x-circle text-danger fs-3"></i><br>Gagal menarik data performa: ${data.message || 'Error API'}`;
                }
            })
            .catch(err => {
                document.getElementById('campaignHourlyLoader').style.display = 'block';
                document.getElementById('campaignHourlyLoader').innerHTML = `<i class="bi bi-wifi-off text-danger fs-3"></i><br>Koneksi terputus.`;
            });
    };

    function renderCampaignHourlyChart(hourlyData) {
        const ctx = document.getElementById('campaignHourlyChartCanvas').getContext('2d');
        if (campaignHourlyChartObj) {
            campaignHourlyChartObj.destroy();
        }

        if (!hourlyData || hourlyData.length === 0) {
            ctx.canvas.parentElement.innerHTML = `<div style="text-align:center; padding: 2rem; color:var(--dsh-muted); font-size:0.8rem;">Belum ada data performa 24 jam untuk kampanye ini pada hari ini.</div><canvas id="campaignHourlyChartCanvas" style="display:none;"></canvas>`;
            return;
        }

        // Data is likely grouped by hour, let's create a 0-23 timeline
        const labels = Array.from({length: 24}, (_, i) => `${String(i).padStart(2, '0')}:00`);
        const clicks = new Array(24).fill(0);
        const spend = new Array(24).fill(0);
        const gmv = new Array(24).fill(0);

        hourlyData.forEach(row => {
            if (row.time !== undefined && row.time >= 0 && row.time <= 23) {
                clicks[row.time] = row.click || row.clicks || 0;
                spend[row.time] = row.expense || row.spend || 0;
                gmv[row.time] = row.broad_gmv || row.gmv || 0;
            }
        });

        campaignHourlyChartObj = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'GMV (Rp)',
                        data: gmv,
                        borderColor: 'rgba(16, 185, 129, 0.9)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        yAxisID: 'y1',
                        tension: 0.4,
                        borderDash: [5, 5]
                    },
                    {
                        label: 'Klik',
                        data: clicks,
                        borderColor: 'rgba(59, 130, 246, 0.9)',
                        backgroundColor: 'transparent',
                        type: 'bar',
                        yAxisID: 'y',
                        order: 2
                    },
                    {
                        label: 'Biaya (Rp)',
                        data: spend,
                        borderColor: 'rgba(239, 68, 68, 0.9)',
                        backgroundColor: 'rgba(239, 68, 68, 0.2)',
                        fill: true,
                        yAxisID: 'y1',
                        tension: 0.4,
                        order: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    x: {
                        grid: { color: document.body.getAttribute('data-theme') === 'dark' ? '#334155' : '#e2e8f0', drawBorder: false },
                        ticks: { color: document.body.getAttribute('data-theme') === 'dark' ? '#94a3b8' : '#64748b', font: { size: 10 } }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: { display: true, text: 'Trafik (Klik)', color: '#3b82f6', font: { size: 10 } },
                        grid: { display: false },
                        ticks: { color: document.body.getAttribute('data-theme') === 'dark' ? '#94a3b8' : '#64748b', font: { size: 10 } }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: { display: true, text: 'Finansial (Rp)', color: '#10b981', font: { size: 10 } },
                        grid: { color: document.body.getAttribute('data-theme') === 'dark' ? '#334155' : '#e2e8f0' },
                        ticks: { 
                            color: document.body.getAttribute('data-theme') === 'dark' ? '#94a3b8' : '#64748b', 
                            font: { size: 10 },
                            callback: function(v) { return formatShortIDR(v); }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: document.body.getAttribute('data-theme') === 'dark' ? '#f8fafc' : '#334155', font: { size: 11, family: 'Inter, sans-serif' }, boxWidth: 12 }
                    },
                    tooltip: {
                        backgroundColor: document.body.getAttribute('data-theme') === 'dark' ? 'rgba(15, 23, 42, 0.9)' : 'rgba(255, 255, 255, 0.95)',
                        titleColor: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#0f172a',
                        bodyColor: document.body.getAttribute('data-theme') === 'dark' ? '#cbd5e1' : '#334155',
                        borderColor: document.body.getAttribute('data-theme') === 'dark' ? '#334155' : '#e2e8f0',
                        borderWidth: 1,
                        padding: 10,
                        callbacks: {
                            label: function(c) {
                                if (c.dataset.yAxisID === 'y1') return c.dataset.label + ': Rp ' + c.raw.toLocaleString('id-ID');
                                return c.dataset.label + ': ' + c.raw.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>

@if(!empty($dailyChartData))
<script>
document.addEventListener("DOMContentLoaded", function() {
    const rawDaily = @json($dailyChartData ?? []);
    const rawHourly = @json($heatmapData ?? []);
    const rawHistorical = @json($historicalData ?? []);
    
    // Pad Daily Data to show full range
    const dailyData = [];
    const fromEl = document.getElementById('fromHidden');
    const toEl = document.getElementById('toHidden');
    if (fromEl && toEl && fromEl.value && toEl.value) {
        // use ymd function defined earlier or create inline logic
        function ymdLocal(d) { return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0'); }
        const dStart = new Date(fromEl.value);
        const dEnd = new Date(toEl.value);
        for (let d = new Date(dStart); d <= dEnd; d.setDate(d.getDate() + 1)) {
            let ds = ymdLocal(d);
            let found = rawDaily.find(item => {
                if(!item.date) return false;
                let itemDate = new Date(item.date);
                return ymdLocal(itemDate) === ds;
            });
            dailyData.push(found ? found : { date: ds, spend: 0, gmv: 0, roas: 0 });
        }
    } else {
        dailyData.push(...rawDaily);
    }

    // Pad Hourly Data to show full 24 hours
    const hourlyData = [];
    for (let i = 0; i < 24; i++) {
        let found = rawHourly.find(d => parseInt(d.performance_hour) === i);
        hourlyData.push(found ? found : { performance_hour: i, clicks: 0, orders: 0, expense: 0, gmv: 0 });
    }
    
    // Theme & UX Colors
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const textColor = isDark ? '#94a3b8' : '#64748b'; // softer text for axes
    const gridColor = isDark ? 'rgba(255,255,255,0.04)' : 'rgba(0,0,0,0.04)';
    const tooltipBg = isDark ? 'rgba(15,23,42,0.95)' : 'rgba(255,255,255,0.95)';
    const tooltipBorder = isDark ? 'rgba(255,255,255,0.15)' : 'rgba(0,0,0,0.08)';
    const tooltipText = isDark ? '#f8fafc' : '#0f172a';
    
    Chart.defaults.color = textColor;
    Chart.defaults.font.family = 'ui-monospace, SFMono-Regular, Menlo, Consolas, monospace';

    // Helper: Format Rupiah Singkat (Jt/Rb)
    const formatShortIDR = (value) => {
        if(value >= 1000000) return (value / 1000000).toFixed(1).replace(/\.0$/, '') + ' Jt';
        if(value >= 1000) return (value / 1000).toFixed(1).replace(/\.0$/, '') + ' Rb';
        return value;
    };

    // Helper: Format Indo Date (YYYY-MM-DD to DD MMM YYYY)
    const formatIndoDate = (dateStr) => {
        if(!dateStr) return '';
        const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
        let parts = dateStr.split('-');
        if(parts.length !== 3) return dateStr;
        return parseInt(parts[2]) + ' ' + months[parseInt(parts[1])-1] + ' ' + parts[0];
    };

    // Calculate summaries for charts
    let totalDailySpend = dailyData.reduce((sum, d) => sum + parseFloat(d.spend || 0), 0);
    let totalDailyGmv = dailyData.reduce((sum, d) => sum + parseFloat(d.gmv || 0), 0);
    let totalDailyRoas = totalDailySpend > 0 ? (totalDailyGmv / totalDailySpend).toFixed(2) : "0.00";
    let dsEl = document.getElementById('dailySummary');
    if(dsEl) {
        dsEl.innerHTML = `<span style="color:#dc2626">Rp ${formatShortIDR(totalDailySpend)} Biaya</span> &bull; <span style="color:#16a34a">Rp ${formatShortIDR(totalDailyGmv)} GMV</span> &bull; <span style="color:#eab308">${totalDailyRoas}x ROAS</span>`;
    }

    let totalHourlySpend = hourlyData.reduce((sum, d) => sum + parseFloat(d.expense || 0), 0);
    let totalHourlyGmv = hourlyData.reduce((sum, d) => sum + parseFloat(d.gmv || 0), 0);
    let totalHourlyRoas = totalHourlySpend > 0 ? (totalHourlyGmv / totalHourlySpend).toFixed(2) : "0.00";
    let hsEl = document.getElementById('hourlySummary');
    if(hsEl) {
        hsEl.innerHTML = `<span style="color:#dc2626">Rp ${formatShortIDR(totalHourlySpend)} Biaya</span> &bull; <span style="color:#10b981">Rp ${formatShortIDR(totalHourlyGmv)} GMV</span> &bull; <span style="color:#eab308">${totalHourlyRoas}x ROAS</span>`;
    }

    let totalDailyImpressions = dailyData.reduce((sum, d) => sum + parseInt(d.impressions || 0), 0);
    let totalDailyClicks = dailyData.reduce((sum, d) => sum + parseInt(d.clicks || 0), 0);
    let avgDailyCtr = totalDailyImpressions > 0 ? ((totalDailyClicks / totalDailyImpressions) * 100).toFixed(2) : "0.00";
    let tsEl = document.getElementById('trafficSummary');
    if(tsEl) {
        tsEl.innerHTML = `<span style="color:#f59e0b">${formatShortIDR(totalDailyImpressions)} Impresi</span> &bull; <span style="color:#3b82f6">${formatShortIDR(totalDailyClicks)} Klik</span> &bull; <span style="color:#8b5cf6">${avgDailyCtr}% CTR</span>`;
    }

    // --- AI INSIGHTS GENERATOR (ULTRA SMART EDITION) ---
    let totalDailyOrders = dailyData.reduce((sum, d) => sum + parseInt(d.orders || 0), 0);
    let avgDailyCvr = totalDailyClicks > 0 ? ((totalDailyOrders / totalDailyClicks) * 100).toFixed(2) : "0.00";
    let avgCpc = totalDailyClicks > 0 ? (totalDailySpend / totalDailyClicks) : 0;
    
    // Hitung Average Order Value (AOV) / Rata-rata Nilai Pesanan
    let aov = totalDailyOrders > 0 ? (totalDailyGmv / totalDailyOrders) : 0;
    // Asumsi batas aman CPC adalah 10% dari AOV (Batas wajar margin)
    let maxSafeCpc = aov * 0.10;
    
    let dayCount = dailyData.length || 1;

    // 1. Health Check & Margin Analysis
    let healthEl = document.getElementById('insightHealth');
    if (healthEl) {
        let healthHtml = '';
        if (totalDailyRoas >= 5.0 && (totalDailySpend / dayCount) < 50000) {
            healthHtml = `<div style="font-weight: 700; color: #16a34a; font-size: 0.85rem; margin-bottom: 0.3rem;">🚀 Kehilangan Momentum (Scaling)</div>
                          <div style="font-size: 0.72rem; color: var(--dsh-muted);">ROAS Anda luar biasa (<b>${totalDailyRoas}x</b>), tapi modal harian terlalu kecil. Anda membiarkan kompetitor mengambil sisa pelanggan! 💡 <b>Saran:</b> Naikkan modal harian 15-20%.</div>`;
            healthEl.style.borderLeftColor = '#16a34a';
        } else if (totalDailyRoas >= 4.0) {
            healthHtml = `<div style="font-weight: 700; color: #16a34a; font-size: 0.85rem; margin-bottom: 0.3rem;">🟢 Mesin Profit Maksimal</div>
                          <div style="font-size: 0.72rem; color: var(--dsh-muted);">Efisiensi luar biasa dengan ROAS <b>${totalDailyRoas}x</b>. Rata-rata keranjang belanja (AOV) berada di <b>Rp ${formatShortIDR(aov)}</b>. Pertahankan strategi ini!</div>`;
            healthEl.style.borderLeftColor = '#16a34a';
        } else if (totalDailyRoas >= 1.5 && avgCpc > maxSafeCpc && maxSafeCpc > 0) {
            healthHtml = `<div style="font-weight: 700; color: #dc2626; font-size: 0.85rem; margin-bottom: 0.3rem;">🚨 Bahaya Margin (CPC vs AOV)</div>
                          <div style="font-size: 0.72rem; color: var(--dsh-muted);">Biaya per klik Anda (<b>Rp ${formatShortIDR(avgCpc)}</b>) terlalu mahal dibanding rata-rata nilai pesanan (<b>Rp ${formatShortIDR(aov)}</b>). Ini akan menggerus profit bersih Anda! 💡 <b>Saran:</b> Naikkan Target ROAS pada kampanye GMV Max Anda agar algoritma mencari pembeli yang lebih murah.</div>`;
            healthEl.style.borderLeftColor = '#dc2626';
        } else if (totalDailyRoas >= 2.0) {
            healthHtml = `<div style="font-weight: 700; color: #eab308; font-size: 0.85rem; margin-bottom: 0.3rem;">🟡 Profit Tipis (Waspada)</div>
                          <div style="font-size: 0.72rem; color: var(--dsh-muted);">ROAS di level <b>${totalDailyRoas}x</b>. Masih profit, namun sangat rentan jika ada retur barang atau perang harga. Evaluasi produk mana di GMV Max yang menyedot biaya tapi seret penjualan.</div>`;
            healthEl.style.borderLeftColor = '#eab308';
        } else {
            healthHtml = `<div style="font-weight: 700; color: #dc2626; font-size: 0.85rem; margin-bottom: 0.3rem;">🔴 Darurat Kebocoran Anggaran</div>
                          <div style="font-size: 0.72rem; color: var(--dsh-muted);">ROAS hancur di angka <b>${totalDailyRoas}x</b>. Anda mensubsidi pembeli. 💡 <b>Saran:</b> Segera evaluasi produk di dalam kampanye GMV Max, atau naikkan Target ROAS secara drastis untuk mengerem pengeluaran!</div>`;
            healthEl.style.borderLeftColor = '#dc2626';
        }
        healthEl.innerHTML = healthHtml;
    }

    // 2. Traffic Detective (CTR vs CVR)
    let trafficEl = document.getElementById('insightTraffic');
    if (trafficEl) {
        let trafficHtml = '';
        if (avgDailyCtr > 3.0 && avgDailyCvr < 1.0 && totalDailyClicks > 50) {
            trafficHtml = `<div style="font-weight: 700; color: #dc2626; font-size: 0.85rem; margin-bottom: 0.3rem;">📉 Sindrom "Cuma Lihat-Lihat"</div>
                           <div style="font-size: 0.72rem; color: var(--dsh-muted);">Iklan sangat memancing klik (CTR <b>${avgDailyCtr}%</b>), tapi gagal jadi penjualan (CVR <b>${avgDailyCvr}%</b>). 💡 <b>Saran:</b> Harga mungkin terlalu mahal dibanding kompetitor, atau ulasan produk kurang meyakinkan.</div>`;
            trafficEl.style.borderLeftColor = '#dc2626';
        } else if (avgDailyCtr < 1.5 && avgDailyCvr > 3.0 && totalDailyImpressions > 500) {
            trafficHtml = `<div style="font-weight: 700; color: #8b5cf6; font-size: 0.85rem; margin-bottom: 0.3rem;">💎 Berlian Tersembunyi</div>
                           <div style="font-size: 0.72rem; color: var(--dsh-muted);">Produk Anda laku keras bagi yang sudah klik (CVR <b>${avgDailyCvr}%</b>), tapi jarang diklik di hasil pencarian (CTR <b>${avgDailyCtr}%</b>). 💡 <b>Saran:</b> Segera ganti foto utama agar lebih mencolok!</div>`;
            trafficEl.style.borderLeftColor = '#8b5cf6';
        } else if (avgDailyCtr < 1.5 && totalDailyImpressions > 1000) {
            trafficHtml = `<div style="font-weight: 700; color: #f59e0b; font-size: 0.85rem; margin-bottom: 0.3rem;">👁️ Kebocoran Trafik</div>
                           <div style="font-size: 0.72rem; color: var(--dsh-muted);">CTR sangat rendah (<b>${avgDailyCtr}%</b>). Ribuan orang melihat iklan tapi lewat begitu saja. 💡 <b>Saran:</b> Optimalkan judul atau pasang label diskon di foto utama.</div>`;
            trafficEl.style.borderLeftColor = '#f59e0b';
        } else {
            trafficHtml = `<div style="font-weight: 700; color: #3b82f6; font-size: 0.85rem; margin-bottom: 0.3rem;">🎯 Trafik Optimal</div>
                           <div style="font-size: 0.72rem; color: var(--dsh-muted);">Daya tarik iklan (CTR <b>${avgDailyCtr}%</b>) dan daya beli (CVR <b>${avgDailyCvr}%</b>) berada dalam rasio yang seimbang dan wajar.</div>`;
            trafficEl.style.borderLeftColor = '#3b82f6';
        }
        trafficEl.innerHTML = trafficHtml;
    }

    // 3. Golden Hour (Dayparting)
    let timeEl = document.getElementById('insightTime');
    if (timeEl) {
        let bestHour = '-';
        let highestScore = 0;
        let gmvAtBest = 0;
        
        hourlyData.forEach(d => {
            let sp = parseFloat(d.expense || 0);
            let gm = parseFloat(d.gmv || 0);
            // Cek jika jam ini menghasilkan GMV setidaknya 5% dari total (bukan kebetulan 1 klik hoki)
            if (sp > 1000 && gm > (totalHourlyGmv * 0.05)) { 
                let r = gm / sp;
                // Score kombinasi antara ROAS dan besaran GMV
                let score = r * (gm / totalHourlyGmv);
                if (score > highestScore) {
                    highestScore = score;
                    bestHour = d.performance_hour;
                    gmvAtBest = gm;
                }
            }
        });
        
        if (highestScore > 0) {
            let gmvPct = ((gmvAtBest / totalHourlyGmv) * 100).toFixed(0);
            timeEl.innerHTML = `<div style="font-weight: 700; color: #d97706; font-size: 0.85rem; margin-bottom: 0.3rem;">⏳ Waktu Emas (Dayparting)</div>
                                <div style="font-size: 0.72rem; color: var(--dsh-muted);">Jam <b>${bestHour}:00 - ${parseInt(bestHour)+1}:00</b> adalah lumbung emas Anda, menyumbang <b>${gmvPct}%</b> dari total pendapatan! 💡 <b>Saran:</b> Habiskan mayoritas budget di jam ini!</div>`;
            timeEl.style.borderLeftColor = '#d97706';
        } else {
            timeEl.innerHTML = `<div style="font-weight: 700; color: var(--dsh-muted); font-size: 0.85rem; margin-bottom: 0.3rem;">⏳ Data Waktu Berpencar</div>
                                <div style="font-size: 0.72rem; color: var(--dsh-muted);">Performa iklan tersebar merata di berbagai jam. Belum ada "Waktu Emas" dominan yang bisa disimpulkan untuk rentang ini.</div>`;
            timeEl.style.borderLeftColor = 'var(--dsh-border)';
        }
    }

    // Helper: Format Full Rupiah untuk Tooltip
    const formatFullIDR = (value) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
    };

    // --- DAILY LINE CHART ---
    const ctxDaily = document.getElementById("dailyChart");
    if(ctxDaily) {
        const ctxDaily2D = ctxDaily.getContext('2d');
        
        // Buat Gradient (atas lebih pekat, bawah memudar)
        let gradientSpend = ctxDaily2D.createLinearGradient(0, 0, 0, 300);
        gradientSpend.addColorStop(0, 'rgba(220, 38, 38, 0.25)'); // Red pekat
        gradientSpend.addColorStop(1, 'rgba(220, 38, 38, 0.0)');  // Red pudar

        let gradientGMV = ctxDaily2D.createLinearGradient(0, 0, 0, 300);
        gradientGMV.addColorStop(0, 'rgba(22, 163, 74, 0.25)'); // Green pekat
        gradientGMV.addColorStop(1, 'rgba(22, 163, 74, 0.0)');  // Green pudar

        new Chart(ctxDaily2D, {
            type: 'line',
            data: {
                labels: dailyData.map(d => {
                    // ubah format "2026-07-22" jadi "22 Jul"
                    const date = new Date(d.date);
                    return date.getDate() + ' ' + date.toLocaleString('id-ID', { month: 'short' });
                }),
                datasets: [
                    {
                        label: 'AOV',
                        data: dailyData.map(d => {
                            let gm = parseFloat(d.gmv || 0);
                            let or = parseInt(d.orders || 0);
                            return or > 0 ? parseFloat((gm/or).toFixed(0)) : 0;
                        }),
                        borderColor: '#94a3b8', // slate
                        backgroundColor: '#94a3b8',
                        borderDash: [5, 5],
                        fill: false,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: 0, // hide dots
                        pointHitRadius: 15,
                        pointHoverRadius: 4,
                        pointHoverBackgroundColor: '#94a3b8',
                        yAxisID: 'y2'
                    },
                    {
                        label: 'ROAS',
                        data: dailyData.map(d => {
                            let sp = parseFloat(d.spend || 0);
                            let gm = parseFloat(d.gmv || 0);
                            return sp > 0 ? parseFloat((gm/sp).toFixed(2)) : 0;
                        }),
                        borderColor: '#eab308', // Gold
                        backgroundColor: '#eab308',
                        fill: false,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: dailyData.length <= 1 ? 5 : 0,
                        pointHitRadius: 15,
                        pointHoverRadius: 4,
                        pointHoverBackgroundColor: '#eab308',
                        yAxisID: 'y1'
                    },
                    {
                        label: 'GMV (Pendapatan)',
                        data: dailyData.map(d => parseFloat(d.gmv || 0)),
                        borderColor: '#16a34a',
                        backgroundColor: gradientGMV,
                        fill: true,
                        tension: 0.4, 
                        borderWidth: 2,
                        pointRadius: dailyData.length <= 1 ? 5 : 0,
                        pointHitRadius: 15,
                        pointHoverRadius: 4,
                        pointHoverBackgroundColor: '#16a34a',
                        yAxisID: 'y'
                    },
                    {
                        label: 'Biaya (Spend)',
                        data: dailyData.map(d => parseFloat(d.spend || 0)),
                        borderColor: '#dc2626',
                        backgroundColor: gradientSpend,
                        fill: true,
                        tension: 0.4, 
                        borderWidth: 2,
                        pointRadius: dailyData.length <= 1 ? 5 : 0,
                        pointHitRadius: 15,
                        pointHoverRadius: 4,
                        pointHoverBackgroundColor: '#dc2626',
                        yAxisID: 'y'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: { 
                        position: 'top', 
                        labels: { usePointStyle: true, boxWidth: 6, font: { size: 11, family: 'Inter, sans-serif' } } 
                    },
                    tooltip: {
                        backgroundColor: tooltipBg,
                        titleColor: tooltipText,
                        bodyColor: tooltipText,
                        borderColor: tooltipBorder,
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: true,
                        boxPadding: 4,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) label += ': ';
                                if (context.dataset.yAxisID === 'y1') {
                                    return label + context.parsed.y + 'x';
                                } else {
                                    return label + formatFullIDR(context.parsed.y);
                                }
                            }
                        }
                    }
                },
                scales: {
                    x: { 
                        grid: { display: false }, 
                        ticks: { font: { size: 10 } } 
                    },
                    y: { 
                        type: 'linear', 
                        position: 'left', 
                        beginAtZero: true, 
                        grid: { color: gridColor, drawBorder: false }, 
                        ticks: { 
                            font: { size: 10 }, 
                            padding: 8,
                            callback: function(value) { return formatShortIDR(value); }
                        }
                    },
                    y1: { 
                        type: 'linear', 
                        position: 'right', 
                        beginAtZero: true, 
                        grid: { drawOnChartArea: false, drawBorder: false }, 
                        ticks: { 
                            font: { size: 10 }, 
                            padding: 8,
                            callback: function(value) { return value + 'x'; }
                        }
                    },
                    y2: {
                        type: 'linear',
                        display: false,
                        position: 'left',
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // --- HOURLY BAR CHART ---
    const ctxHourly = document.getElementById("hourlyChart");
    if(ctxHourly) {
        new Chart(ctxHourly.getContext('2d'), {
            type: 'bar',
            data: {
                labels: hourlyData.map(d => d.performance_hour + ':00'),
                datasets: [
                    {
                        type: 'line',
                        label: 'Klik (Trafik)',
                        data: hourlyData.map(d => parseInt(d.clicks || 0)),
                        borderColor: 'rgba(148, 163, 184, 0)',
                        backgroundColor: 'rgba(148, 163, 184, 0.15)',
                        borderWidth: 0,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHitRadius: 15,
                        pointHoverRadius: 0,
                        yAxisID: 'y2'
                    },
                    {
                        type: 'line',
                        label: 'ROAS',
                        data: hourlyData.map(d => {
                            let sp = parseFloat(d.expense || 0);
                            let gm = parseFloat(d.gmv || 0);
                            return sp > 0 ? parseFloat((gm/sp).toFixed(2)) : 0;
                        }),
                        borderColor: '#eab308',
                        backgroundColor: '#eab308',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        yAxisID: 'y1'
                    },
                    {
                        type: 'bar',
                        label: 'Biaya (Spend)',
                        data: hourlyData.map(d => parseFloat(d.expense || 0)),
                        backgroundColor: 'rgba(220, 38, 38, 0.85)',
                        borderRadius: 4,
                        yAxisID: 'y'
                    },
                    {
                        type: 'bar',
                        label: 'GMV (Pendapatan)',
                        data: hourlyData.map(d => parseFloat(d.gmv || 0)),
                        backgroundColor: 'rgba(16, 185, 129, 0.85)',
                        borderRadius: 4,
                        yAxisID: 'y'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { 
                    legend: { 
                        display: true, 
                        position: 'top', 
                        labels: { usePointStyle: true, boxWidth: 6, font: { size: 10, family: 'Inter, sans-serif' } } 
                    },
                    tooltip: {
                        backgroundColor: tooltipBg,
                        titleColor: tooltipText,
                        bodyColor: tooltipText,
                        borderColor: tooltipBorder,
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) label += ': ';
                                if (context.dataset.yAxisID === 'y1') {
                                    return label + context.parsed.y + 'x';
                                } else if (context.dataset.yAxisID === 'y2') {
                                    return label + context.parsed.y;
                                } else {
                                    return label + formatFullIDR(context.parsed.y);
                                }
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                    y: { 
                        type: 'linear',
                        position: 'left',
                        beginAtZero: true, 
                        grid: { color: gridColor, drawBorder: false }, 
                        ticks: { 
                            font: { size: 10 }, 
                            padding: 8,
                            callback: function(value) { return formatShortIDR(value); }
                        }
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        beginAtZero: true,
                        grid: { drawOnChartArea: false, drawBorder: false },
                        ticks: { 
                            font: { size: 10 }, 
                            padding: 8,
                            callback: function(value) { return value + 'x'; }
                        }
                    },
                    y2: {
                        type: 'linear',
                        display: false,
                        position: 'left',
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // --- DAILY TRAFFIC CHART ---
    const ctxTraffic = document.getElementById("trafficChart");
    if(ctxTraffic) {
        const ctxTraffic2D = ctxTraffic.getContext('2d');
        
        let gradImp = ctxTraffic2D.createLinearGradient(0, 0, 0, 300);
        gradImp.addColorStop(0, 'rgba(245, 158, 11, 0.25)'); // Amber
        gradImp.addColorStop(1, 'rgba(245, 158, 11, 0.0)');
        
        let gradClick = ctxTraffic2D.createLinearGradient(0, 0, 0, 300);
        gradClick.addColorStop(0, 'rgba(59, 130, 246, 0.25)'); // Blue
        gradClick.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

        new Chart(ctxTraffic2D, {
            type: 'line',
            data: {
                labels: dailyData.map(d => {
                    const date = new Date(d.date);
                    return date.getDate() + ' ' + date.toLocaleString('id-ID', { month: 'short' });
                }),
                datasets: [
                    {
                        label: 'CTR',
                        data: dailyData.map(d => {
                            let imp = parseInt(d.impressions || 0);
                            let clk = parseInt(d.clicks || 0);
                            return imp > 0 ? parseFloat(((clk/imp)*100).toFixed(2)) : 0;
                        }),
                        borderColor: '#8b5cf6', // Violet
                        backgroundColor: '#8b5cf6',
                        fill: false,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: dailyData.length <= 1 ? 5 : 0,
                        pointHitRadius: 15,
                        pointHoverRadius: 4,
                        yAxisID: 'y2'
                    },
                    {
                        label: 'Klik',
                        data: dailyData.map(d => parseInt(d.clicks || 0)),
                        borderColor: '#3b82f6',
                        backgroundColor: gradClick,
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: dailyData.length <= 1 ? 5 : 0,
                        pointHitRadius: 15,
                        pointHoverRadius: 4,
                        yAxisID: 'y1'
                    },
                    {
                        label: 'Impression',
                        data: dailyData.map(d => parseInt(d.impressions || 0)),
                        borderColor: '#f59e0b',
                        backgroundColor: gradImp,
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: dailyData.length <= 1 ? 5 : 0,
                        pointHitRadius: 15,
                        pointHoverRadius: 4,
                        yAxisID: 'y'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 6, font: { size: 11, family: 'Inter, sans-serif' } } },
                    tooltip: {
                        backgroundColor: tooltipBg,
                        titleColor: tooltipText,
                        bodyColor: tooltipText,
                        borderColor: tooltipBorder,
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: true,
                        boxPadding: 4,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) label += ': ';
                                if (context.dataset.yAxisID === 'y2') {
                                    return label + context.parsed.y + '%';
                                } else {
                                    return label + context.parsed.y.toLocaleString('id-ID');
                                }
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                    y: { 
                        type: 'linear', position: 'left', beginAtZero: true, 
                        grid: { color: gridColor, drawBorder: false }, 
                        ticks: { font: { size: 10 }, padding: 8, callback: function(value) { return formatShortIDR(value); } } 
                    },
                    y1: { 
                        type: 'linear', position: 'right', beginAtZero: true, 
                        grid: { drawOnChartArea: false, drawBorder: false }, 
                        ticks: { font: { size: 10 }, padding: 8, callback: function(value) { return formatShortIDR(value); } }
                    },
                    y2: { type: 'linear', display: false, position: 'left', beginAtZero: true }
                }
            }
        });
    }

    // ==========================================
    // 4. HISTORICAL CHART (PERIOD-OVER-PERIOD)
    // ==========================================
    let histChart;
    const ctxHist = document.getElementById('historicalChart');
    const histContainer = ctxHist ? ctxHist.parentElement : null;
    
    try {
        if (ctxHist && rawHistorical && rawHistorical.length > 0) {
            // Check if all periods have 0 data
            let hasAnyData = false;
            rawHistorical.forEach(p => { if (p.data && p.data.length > 0) hasAnyData = true; });
            
            if (!hasAnyData) {
                histContainer.innerHTML = '<div style="text-align:center; padding: 40px; color: var(--dsh-muted);">Tidak ada data historis yang tersedia untuk rentang ini. Pastikan Anda telah melakukan Sinkronisasi data di bulan-bulan sebelumnya.</div>';
            } else {
                
                // Calculate maxDays strictly from the selected date range
                let maxDays = 0;
                const fromElHist = document.getElementById('fromHidden');
                const toElHist = document.getElementById('toHidden');
                const dStartHist = fromElHist && fromElHist.value ? new Date(fromElHist.value) : new Date();
                const dEndHist = toElHist && toElHist.value ? new Date(toElHist.value) : new Date();
                if (dStartHist && dEndHist) {
                    maxDays = Math.round((dEndHist - dStartHist) / (1000 * 60 * 60 * 24)) + 1;
                }
                if (maxDays < 1) maxDays = 1;
                
                // Generate X-Axis: "Hari 1", "Hari 2", etc.
                let histLabels = [];
                for (let i = 1; i <= maxDays; i++) {
                    histLabels.push(`Hari ${i}`);
                }

                // Colors for periods
                const lineColors = [
                    '#ef4444', // Period 0 (Current) - Red
                    '#94a3b8', // Period 1 (Last) - Slate
                    'rgba(148, 163, 184, 0.4)', // Period 2 - Light Slate
                    'rgba(148, 163, 184, 0.2)'  // Period 3 - Lighter
                ];

                const dashStyles = [
                    [], // Solid
                    [5, 5], // Dashed
                    [2, 2], // Dotted
                    [2, 4]
                ];

                const getMetricValue = (d, metric) => {
                    let sp = parseFloat(d.spend || d.expense || 0);
                    let gm = parseFloat(d.gmv || 0);
                    let clicks = parseFloat(d.clicks || 0);
                    let orders = parseFloat(d.orders || d.broad_order || d.direct_order || 0);

                    if (metric === 'roas') {
                        return sp > 0 ? (gm / sp) : 0;
                    } else if (metric === 'gmv') {
                        return gm;
                    } else if (metric === 'spend') {
                        return sp;
                    } else if (metric === 'impressions') {
                        return parseFloat(d.impressions || 0);
                    } else if (metric === 'clicks') {
                        return clicks;
                    } else if (metric === 'ctr') {
                        let im = parseFloat(d.impressions || 0);
                        return im > 0 ? (clicks / im) * 100 : 0;
                    } else if (metric === 'cvr') {
                        return clicks > 0 ? (orders / clicks) * 100 : 0;
                    }
                    return 0;
                };

                const renderHistChart = (metric) => {
                    let datasets = rawHistorical.map((period, idx) => {
                        let dataPoints = new Array(maxDays).fill(null);
                        
                        // Align data to specific day offset
                        if (period.start) {
                            const pStart = new Date(period.start);
                            period.data.forEach(d => {
                                if (d.date) {
                                    const pDate = new Date(d.date);
                                    const dayOffset = Math.round((pDate - pStart) / (1000 * 60 * 60 * 24));
                                    if (dayOffset >= 0 && dayOffset < maxDays) {
                                        dataPoints[dayOffset] = getMetricValue(d, metric);
                                    }
                                }
                            });
                        }
                        
                        let label = idx === 0 ? 'Rentang Saat Ini' : `${idx} Rentang Lalu`;
                        if (idx === 1) label = 'Rentang Sebelumnya';

                        return {
                            label: label,
                            data: dataPoints,
                            borderColor: lineColors[idx] || lineColors[0],
                            borderWidth: idx === 0 ? 3 : 2,
                            borderDash: dashStyles[idx] || [],
                            tension: 0.3,
                            pointRadius: 0,
                            pointHoverRadius: 4,
                            fill: false,
                            spanGaps: true
                        };
                    });

                    if (histChart) {
                        histChart.data.datasets = datasets;
                        histChart.update();
                    } else {
                        histChart = new Chart(ctxHist.getContext('2d'), {
                            type: 'line',
                            data: {
                                labels: histLabels,
                                datasets: datasets
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: { mode: 'index', intersect: false },
                                plugins: {
                                    legend: { display: true, position: 'top', labels: { color: textColor } },
                                    tooltip: {
                                        backgroundColor: tooltipBg,
                                        titleColor: tooltipText,
                                        bodyColor: tooltipText,
                                        borderColor: tooltipBorder,
                                        borderWidth: 1,
                                        padding: 10,
                                        callbacks: {
                                            label: function(ctx) {
                                                let val = ctx.raw;
                                                let metric = document.getElementById('histMetricSelect').value;
                                                if (metric === 'roas') {
                                                    return ctx.dataset.label + ': ' + val.toFixed(2) + 'x';
                                                } else if (metric === 'cvr' || metric === 'ctr') {
                                                    return ctx.dataset.label + ': ' + val.toFixed(2) + '%';
                                                } else if (metric === 'impressions' || metric === 'clicks') {
                                                    return ctx.dataset.label + ': ' + val.toLocaleString('id-ID');
                                                } else {
                                                    let str = val;
                                                    if(val >= 1000000) str = (val/1000000).toFixed(1) + ' Jt';
                                                    else if(val >= 1000) str = (val/1000).toFixed(1) + ' Rb';
                                                    return ctx.dataset.label + ': Rp ' + str;
                                                }
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    x: { grid: { display: false }, ticks: { maxTicksLimit: 10 } },
                                    y: { 
                                        beginAtZero: true, 
                                        grid: { color: gridColor }, 
                                        ticks: {
                                            callback: function(value) {
                                                let metric = document.getElementById('histMetricSelect').value;
                                                if (metric === 'roas') return value + 'x';
                                                if (metric === 'cvr' || metric === 'ctr') return value + '%';
                                                if (metric === 'impressions' || metric === 'clicks') {
                                                    if (value >= 1000000) return (value/1000000).toFixed(1) + 'M';
                                                    if (value >= 1000) return (value/1000).toFixed(1) + 'K';
                                                    return value;
                                                }
                                                return formatShortIDR(value);
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }

                    // Update histSummary
                    const summaryEl = document.getElementById('histSummary');
                    if (summaryEl && datasets.length > 0) {
                        let html = '';
                        // Rentang saat ini is dataset 0, previous is dataset 1
                        const currData = datasets[0].data.filter(v => v !== null);
                        const currSum = currData.reduce((a,b)=>a+b, 0);
                        let currAvg = currData.length > 0 ? (currSum / currData.length) : 0;
                        
                        let prevSum = 0;
                        let prevAvg = 0;
                        if (datasets.length > 1) {
                            const prevData = datasets[1].data.filter(v => v !== null);
                            prevSum = prevData.reduce((a,b)=>a+b, 0);
                            prevAvg = prevData.length > 0 ? (prevSum / prevData.length) : 0;
                        }

                        let formatVal = (v) => {
                            if (metric === 'roas') return v.toFixed(2) + 'x';
                            if (metric === 'cvr' || metric === 'ctr') return v.toFixed(2) + '%';
                            if (metric === 'impressions' || metric === 'clicks') return Math.round(v).toLocaleString('id-ID');
                            return 'Rp ' + formatShortIDR(v);
                        };

                        let isAvg = (metric === 'roas' || metric === 'cvr' || metric === 'ctr');
                        html += `<div style="margin-bottom:2px;">Sekarang: <span style="color:var(--dsh-accent)">${isAvg ? 'Rata-rata' : 'Total'} ${formatVal(isAvg ? currAvg : currSum)}</span></div>`;
                        if (datasets.length > 1) {
                            let diff = 0;
                            let compareVal1 = isAvg ? currAvg : currSum;
                            let compareVal2 = isAvg ? prevAvg : prevSum;
                            
                            if (compareVal2 > 0) diff = ((compareVal1 - compareVal2) / compareVal2) * 100;
                            else if (compareVal1 > 0) diff = 100;

                            let color = diff >= 0 ? '#10b981' : '#ef4444';
                            if (metric === 'spend' || metric === 'cpc') {
                                color = diff > 0 ? '#ef4444' : '#10b981'; // Spend going up is bad (red)
                            }
                            let sign = diff > 0 ? '+' : '';

                            html += `<div style="font-size:0.75rem; color:var(--dsh-muted)">Sebelumnya: ${formatVal(isAvg ? prevAvg : prevSum)} 
                                     <span style="color:${color}; font-weight:bold; margin-left:5px;">(${sign}${diff.toFixed(1)}%)</span></div>`;
                        }
                        
                        summaryEl.innerHTML = html;
                    }
                };

                // Initial render
                renderHistChart('roas');

                // Handle metric toggle
                const metricSelect = document.getElementById('histMetricSelect');
                const metricChips = document.querySelectorAll('#histMetricChips .dash-tab-sm');
                metricChips.forEach(chip => {
                    chip.addEventListener('click', function() {
                        metricChips.forEach(c => c.classList.remove('active'));
                        this.classList.add('active');
                        metricSelect.value = this.dataset.val;
                        renderHistChart(this.dataset.val);
                    });
                });
                
                // ==========================================
                // CHART PRODUK (9 Bar Charts)
                // ==========================================
                const rawItems = @json($itemPerformance->toArray()).map(c => {
                    c.ctr = parseFloat(c.impressions) > 0 ? (parseFloat(c.clicks) / parseFloat(c.impressions)) * 100 : 0;
                    c.cvr = parseFloat(c.clicks) > 0 ? (parseFloat(c.orders) / parseFloat(c.clicks)) * 100 : 0;
                    c.roas = parseFloat(c.spend) > 0 ? (parseFloat(c.gmv) / parseFloat(c.spend)) : 0;
                    c.cpa = parseFloat(c.orders) > 0 ? (parseFloat(c.spend) / parseFloat(c.orders)) : 0;
                    return c;
                });

                const renderSingleChart = (ctxId, metric, labelFormat, colorTheme) => {
                    const ctx = document.getElementById(ctxId);
                    if (!ctx || !rawItems || rawItems.length === 0) return;
                    
                    let sorted = [...rawItems].sort((a,b) => parseFloat(b[metric] || 0) - parseFloat(a[metric] || 0)).slice(0, 5);
                    
                    // The original barLabels array is no longer strictly needed for drawing since we hide default ticks,
                    // but we pass empty strings so Chart.js knows how many bars to draw.
                    const barLabels = sorted.map(() => '');
                    
                    const customLabelPlugin = {
                        id: 'customLabels',
                        afterDatasetsDraw: (chart) => {
                            const { ctx, data } = chart;
                            ctx.save();
                            chart.getDatasetMeta(0).data.forEach((bar, index) => {
                                // 1. Draw Custom Y-Axis Labels (Rich text)
                                const sku = sorted[index].item_sku || '-';
                                const id = sorted[index].channel_item_id || 'No ID';
                                
                                const textColor = document.body.getAttribute('data-theme') === 'dark' ? '#f8fafc' : '#334155';
                                const mutedColor = document.body.getAttribute('data-theme') === 'dark' ? '#64748b' : '#94a3b8';

                                ctx.textAlign = 'right';
                                
                                // SKU (Focal Point)
                                ctx.font = 'bold 11px "Inter", sans-serif';
                                ctx.fillStyle = textColor;
                                ctx.textBaseline = 'bottom';
                                
                                // Truncate SKU if too long for the left padding (approx 120px)
                                let displaySku = sku;
                                if (ctx.measureText(displaySku).width > 110) {
                                    displaySku = displaySku.substring(0, 15) + '...';
                                }
                                ctx.fillText(displaySku, bar.base - 10, bar.y - 1);
                                
                                // Product ID (Muted & Small)
                                ctx.font = 'normal 9px "Inter", sans-serif';
                                ctx.fillStyle = mutedColor;
                                ctx.textBaseline = 'top';
                                ctx.fillText(id, bar.base - 10, bar.y + 1);

                                // 2. Draw the metric value on/outside the bar
                                const value = data.datasets[0].data[index];
                                let formattedValue = value.toLocaleString('id-ID');
                                if (labelFormat === 'currency') formattedValue = 'Rp ' + formatShortIDR(value);
                                if (labelFormat === 'percent') formattedValue = value.toLocaleString('id-ID', {minimumFractionDigits: 1, maximumFractionDigits: 1}) + '%';
                                if (labelFormat === 'multiplier') formattedValue = value.toLocaleString('id-ID', {minimumFractionDigits: 1, maximumFractionDigits: 2}) + 'x';
                                
                                ctx.font = 'bold 10px "Inter", sans-serif';
                                ctx.textBaseline = 'middle';
                                
                                const textWidth = ctx.measureText(formattedValue).width;
                                const barWidth = bar.width;
                                
                                if (barWidth > textWidth + 15) {
                                    ctx.fillStyle = '#ffffff';
                                    ctx.textAlign = 'right';
                                    ctx.fillText(formattedValue, bar.x - 6, bar.y + 1);
                                } else {
                                    ctx.fillStyle = document.body.getAttribute('data-theme') === 'dark' ? '#94a3b8' : '#64748b';
                                    ctx.textAlign = 'left';
                                    ctx.fillText(formattedValue, bar.x + 6, bar.y + 1);
                                }
                            });
                            ctx.restore();
                        }
                    };

                    const barData = sorted.map(c => parseFloat(c[metric] || 0));

                    new Chart(ctx.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: barLabels,
                            datasets: [{
                                data: barData,
                                backgroundColor: colorTheme,
                                borderRadius: 4,
                                barThickness: 16
                            }]
                        },
                        plugins: [customLabelPlugin],
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: {
                                padding: {
                                    left: 125, // Reserve space for custom rich Y-axis labels
                                    right: 50 // Give space for values drawn outside the bar
                                }
                            },
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: tooltipBg,
                                    titleColor: tooltipText,
                                    bodyColor: tooltipText,
                                    borderColor: tooltipBorder,
                                    borderWidth: 1,
                                    padding: 10,
                                    callbacks: {
                                        title: function(context) {
                                            let idx = context[0].dataIndex;
                                            let fullSku = sorted[idx].item_sku || sorted[idx].channel_item_id;
                                            let fullName = sorted[idx].item_name || sorted[idx].campaign_name || 'Unknown Product';
                                            return fullSku + ' | ' + fullName;
                                        },
                                        label: function(c) {
                                            if (labelFormat === 'currency') return 'Nilai: Rp ' + formatShortIDR(c.raw);
                                            if (labelFormat === 'percent') return 'Nilai: ' + c.raw.toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '%';
                                            if (labelFormat === 'multiplier') return 'Nilai: ' + c.raw.toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + 'x';
                                            return 'Nilai: ' + c.raw.toLocaleString('id-ID');
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: { 
                                    display: false, 
                                    grid: { display: false } 
                                },
                                y: { 
                                    grid: { display: false }, 
                                    ticks: { 
                                        display: false // Hide default ticks as we draw them manually
                                    },
                                    border: { display: false }
                                }
                            }
                        }
                    });
                };
                
                // Colors matched to the BI icons:
                renderSingleChart('chartImpressions', 'impressions', 'number', 'rgba(14, 165, 233, 0.85)'); // Info (Sky)
                renderSingleChart('chartClicks', 'clicks', 'number', 'rgba(59, 130, 246, 0.85)'); // Primary (Blue)
                renderSingleChart('chartCtr', 'ctr', 'percent', 'rgba(245, 158, 11, 0.85)'); // Warning (Amber)
                
                renderSingleChart('chartOrders', 'orders', 'number', 'rgba(16, 185, 129, 0.85)'); // Success (Green)
                renderSingleChart('chartCvr', 'cvr', 'percent', 'rgba(168, 85, 247, 0.85)'); // Purple
                renderSingleChart('chartCpa', 'cpa', 'currency', 'rgba(239, 68, 68, 0.85)'); // Danger (Red)
                
                renderSingleChart('chartSpend', 'spend', 'currency', 'rgba(239, 68, 68, 0.85)'); // Danger (Red)
                renderSingleChart('chartGmv', 'gmv', 'currency', 'rgba(16, 185, 129, 0.85)'); // Success (Green)
                renderSingleChart('chartRoas', 'roas', 'multiplier', 'rgba(245, 158, 11, 0.85)'); // Warning (Amber)
                
                // ==========================================
                // KESIMPULAN PRODUK (INSIGHTS)
                // ==========================================
                const generateProductInsights = () => {
                    const container = document.getElementById('productInsights');
                    if (!container) return;

                    if (!rawItems || rawItems.length === 0) {
                        container.innerHTML = '<div style="color: var(--dsh-muted); font-size: 0.85rem;">Belum ada data performa produk untuk dianalisis.</div>';
                        return;
                    }

                    let insightsHtml = '';

                    // 1. Star Product (Top GMV)
                    let topGmv = [...rawItems].sort((a,b) => parseFloat(b.gmv || 0) - parseFloat(a.gmv || 0))[0];
                    if (topGmv && parseFloat(topGmv.gmv) > 0) {
                        let sku = topGmv.item_sku || topGmv.channel_item_id;
                        let name = topGmv.item_name || topGmv.campaign_name || 'Produk';
                        if (name.length > 25) name = name.substring(0, 25) + '...';
                        insightsHtml += `<div class="mb-2"><i class="bi bi-star-fill text-warning me-2"></i> <b>Produk Bintang:</b> SKU <b>${sku}</b> (${name}) adalah penyumbang GMV terbesar (Rp ${formatShortIDR(topGmv.gmv)}). Pastikan stok produk ini selalu aman.</div>`;
                    }

                    // 2. Bleeding Product (High Spend, Low ROAS)
                    let topSpend = [...rawItems].sort((a,b) => parseFloat(b.spend || 0) - parseFloat(a.spend || 0))[0];
                    if (topSpend && parseFloat(topSpend.spend) > 0) {
                        let sku = topSpend.item_sku || topSpend.channel_item_id;
                        let roas = parseFloat(topSpend.roas || 0);
                        if (roas < 2 && parseFloat(topSpend.spend) > 5000) {
                            insightsHtml += `<div class="mb-2"><i class="bi bi-exclamation-triangle-fill text-danger me-2"></i> <b>Perlu Evaluasi:</b> SKU <b>${sku}</b> menyerap biaya tertinggi (Rp ${formatShortIDR(topSpend.spend)}) namun ROAS-nya hanya ${roas.toFixed(1)}x. Pertimbangkan untuk menurunkan batas bid atau mengevaluasi ulang kata kunci.</div>`;
                        } else {
                            insightsHtml += `<div class="mb-2"><i class="bi bi-wallet2 text-danger me-2"></i> <b>Investasi Utama:</b> SKU <b>${sku}</b> menyerap biaya tertinggi (Rp ${formatShortIDR(topSpend.spend)}) dengan tingkat ROAS sebesar ${roas.toFixed(1)}x.</div>`;
                        }
                    }

                    // 3. Hidden Gem (High CVR/CTR)
                    let topCvr = [...rawItems].sort((a,b) => parseFloat(b.cvr || 0) - parseFloat(a.cvr || 0))[0];
                    if (topCvr && parseFloat(topCvr.cvr) > 3 && parseFloat(topCvr.spend) > 0) {
                        let sku = topCvr.item_sku || topCvr.channel_item_id;
                        insightsHtml += `<div class="mb-0"><i class="bi bi-gem text-info me-2"></i> <b>Peluang Emas:</b> SKU <b>${sku}</b> memiliki konversi (CVR) sangat baik yaitu ${parseFloat(topCvr.cvr).toFixed(1)}%. Direkomendasikan untuk sedikit menaikkan bid pada produk ini agar tayangannya bertambah.</div>`;
                    } else {
                        let topCtr = [...rawItems].sort((a,b) => parseFloat(b.ctr || 0) - parseFloat(a.ctr || 0))[0];
                        if (topCtr && parseFloat(topCtr.ctr) > 5) {
                            let sku = topCtr.item_sku || topCtr.channel_item_id;
                            insightsHtml += `<div class="mb-0"><i class="bi bi-hand-index text-info me-2"></i> <b>Daya Tarik Tinggi:</b> SKU <b>${sku}</b> memiliki rasio klik (CTR) yang sangat memikat yaitu ${parseFloat(topCtr.ctr).toFixed(1)}%.</div>`;
                        }
                    }

                    if (insightsHtml === '') {
                        insightsHtml = '<div style="color: var(--dsh-muted); font-size: 0.85rem;">Performa produk relatif merata, tidak ada anomali signifikan yang terdeteksi.</div>';
                    }

                    container.innerHTML = `<div style="font-size: 0.85rem; line-height: 1.5; color: var(--text);">${insightsHtml}</div>`;
                };
                
                setTimeout(generateProductInsights, 200);

                // ==========================================
                // AI INSIGHTS HISTORICAL (PERIOD-OVER-PERIOD)
                // ==========================================
                const insightHistEl = document.getElementById('insightHistorical');
                if (insightHistEl && rawHistorical.length >= 2) {
                    // Extract current period (idx 0) and previous period (idx 1)
                    let currentData = rawHistorical[0].data || [];
                    let prevData = rawHistorical[1].data || [];
                    
                    let currSpend = currentData.reduce((sum, d) => sum + parseFloat(d.spend || d.expense || 0), 0);
                    let currGmv = currentData.reduce((sum, d) => sum + parseFloat(d.gmv || 0), 0);
                    let currRoas = currSpend > 0 ? (currGmv / currSpend) : 0;
                    
                    let prevSpend = prevData.reduce((sum, d) => sum + parseFloat(d.spend || d.expense || 0), 0);
                    let prevGmv = prevData.reduce((sum, d) => sum + parseFloat(d.gmv || 0), 0);
                    let prevRoas = prevSpend > 0 ? (prevGmv / prevSpend) : 0;
                    
                    // Prevent divide by zero if previous data is entirely 0
                    if (prevSpend > 0 && prevGmv > 0) {
                        let gmvGrowth = ((currGmv - prevGmv) / prevGmv) * 100;
                        let spendGrowth = ((currSpend - prevSpend) / prevSpend) * 100;
                        let roasGrowth = ((currRoas - prevRoas) / prevRoas) * 100;
                        
                        let histHtml = '';
                        
                        // Condition 0: Data Too Small to Judge
                        if (currGmv < 50000 && prevGmv < 50000) {
                            histHtml = `<div style="font-weight: 700; color: var(--dsh-muted); font-size: 0.85rem; margin-bottom: 0.3rem;">🔍 Data Belum Signifikan</div>
                                        <div style="font-size: 0.72rem; color: var(--dsh-muted);">Penjualan di kedua periode masih sangat kecil (di bawah 50rb) sehingga persentase pertumbuhan belum relevan untuk dianalisis.</div>`;
                            insightHistEl.style.borderLeftColor = 'var(--dsh-border)';
                        }
                        // Condition 1: Anomaly - Huge spending spike but no GMV
                        else if (spendGrowth > 100 && gmvGrowth < 10) {
                            histHtml = `<div style="font-weight: 700; color: #dc2626; font-size: 0.85rem; margin-bottom: 0.3rem;">🚨 ANOMALI: Kebocoran Fatal!</div>
                                        <div style="font-size: 0.72rem; color: var(--dsh-muted);">Ada yang salah! Anda membakar uang <b>${spendGrowth.toFixed(1)}%</b> lebih gila dari bulan lalu, tapi omzet hanya bergerak <b>${gmvGrowth.toFixed(1)}%</b>. 💡 <b>Saran:</b> Algoritma GMV Max mungkin memaksakan budget pada produk yang salah. Coba naikkan Target ROAS atau keluarkan produk yang tidak relevan.</div>`;
                            insightHistEl.style.borderLeftColor = '#dc2626';
                        }
                        // Condition 2: Law of Diminishing Returns
                        else if (spendGrowth > 30 && gmvGrowth > 0 && gmvGrowth < (spendGrowth / 2)) {
                            histHtml = `<div style="font-weight: 700; color: #eab308; font-size: 0.85rem; margin-bottom: 0.3rem;">⚠️ Hukum Hasil yang Berkurang (Diminishing Returns)</div>
                                        <div style="font-size: 0.72rem; color: var(--dsh-muted);">Biaya iklan dinaikkan <b>${spendGrowth.toFixed(1)}%</b> tapi omzet hanya naik <b>${gmvGrowth.toFixed(1)}%</b>. Anda mulai membeli klik-klik sampah/mahal. 💡 <b>Saran:</b> Skalasi sudah mentok. Jangan naikkan budget lagi, fokus pada optimasi tingkat konversi (CVR).</div>`;
                            insightHistEl.style.borderLeftColor = '#eab308';
                        }
                        // Condition 3: Healthy Scaling (Algorithmic Favor)
                        else if (spendGrowth > 10 && gmvGrowth >= spendGrowth) {
                            histHtml = `<div style="font-weight: 700; color: #16a34a; font-size: 0.85rem; margin-bottom: 0.3rem;">🚀 Momentum Skalasi Maksimal</div>
                                        <div style="font-size: 0.72rem; color: var(--dsh-muted);">Sempurna! Anda menambah modal <b>${spendGrowth.toFixed(1)}%</b> dan dibalas dengan kenaikan omzet <b>${gmvGrowth.toFixed(1)}%</b>. Algoritma Shopee sedang memihak produk Anda. 💡 <b>Saran:</b> Injak gas! Naikkan budget pelan-pelan selagi momentum ini ada.</div>`;
                            insightHistEl.style.borderLeftColor = '#16a34a';
                        }
                        // Condition 4: Slowdown (GMV down, Spend down)
                        else if (gmvGrowth < -10 && spendGrowth < -10) {
                            histHtml = `<div style="font-weight: 700; color: #f59e0b; font-size: 0.85rem; margin-bottom: 0.3rem;">📉 Tren Gugur (Dying Trend)</div>
                                        <div style="font-size: 0.72rem; color: var(--dsh-muted);">Pasar mereda. Pendapatan anjlok <b>${Math.abs(gmvGrowth).toFixed(1)}%</b> dan sistem mengerem biaya iklan sebesar <b>${Math.abs(spendGrowth).toFixed(1)}%</b>. 💡 <b>Saran:</b> Cek siklus musiman (habis gajian/tanggal kembar). Jika bukan karena musim, berarti kompetitor merebut pasar Anda!</div>`;
                            insightHistEl.style.borderLeftColor = '#f59e0b';
                        }
                        // Condition 5: Stable with subtle warnings
                        else {
                            let gmvDir = gmvGrowth >= 0 ? "naik" : "turun";
                            histHtml = `<div style="font-weight: 700; color: #3b82f6; font-size: 0.85rem; margin-bottom: 0.3rem;">⚖️ Stabilitas (Fase Plateau)</div>
                                        <div style="font-size: 0.72rem; color: var(--dsh-muted);">Bisnis berjalan stabil bagai mesin. Omzet ${gmvDir} perlahan <b>${Math.abs(gmvGrowth).toFixed(1)}%</b> dengan struktur biaya yang terjaga. 💡 <b>Saran:</b> Saatnya bereksperimen dengan memasukkan 1-2 produk jagoan baru ke dalam kampanye GMV Max Anda.</div>`;
                            insightHistEl.style.borderLeftColor = '#3b82f6';
                        }
                        insightHistEl.innerHTML = histHtml;
                    } else {
                        insightHistEl.innerHTML = `<div style="font-weight: 700; color: var(--dsh-muted); font-size: 0.85rem; margin-bottom: 0.3rem;">⏳ Menunggu Data Historis</div>
                                                   <div style="font-size: 0.72rem; color: var(--dsh-muted);">Data pada rentang waktu sebelumnya (Period-1) kosong atau belum disinkronisasi, sehingga sistem tidak bisa membandingkan pertumbuhan.</div>`;
                    }
                } else if (insightHistEl) {
                    insightHistEl.innerHTML = `<div style="font-weight: 700; color: var(--dsh-muted); font-size: 0.85rem; margin-bottom: 0.3rem;">⏳ Kurang Data Pembanding</div>
                                               <div style="font-size: 0.72rem; color: var(--dsh-muted);">Data komparasi tidak cukup panjang untuk memunculkan wawasan AI historis.</div>`;
                }

                // ==========================================
                // AI INSIGHTS DAILY TREND (ANALISIS HARIAN)
                // ==========================================
                const insightDailyEl = document.getElementById('insightDailyTrend');
                if (insightDailyEl && dailyData.length > 0) {
                    // Filter out zero spend days for accurate analysis
                    let activeDays = dailyData.filter(d => parseFloat(d.spend || 0) > 0);
                    
                    if (activeDays.length > 2) {
                        let bestDay = null;
                        let worstDay = null;
                        let maxRoas = -1;
                        let minRoas = 999999;
                        
                        let totalTrendRoas = 0;
                        let roasArray = [];

                        activeDays.forEach(d => {
                            let r = parseFloat(d.spend) > 0 ? (parseFloat(d.gmv || 0) / parseFloat(d.spend)) : 0;
                            totalTrendRoas += r;
                            roasArray.push(r);
                            
                            if (r > maxRoas) { maxRoas = r; bestDay = d.date; }
                            // Consider worst day only if they spent a decent amount (e.g. > 10000)
                            if (r < minRoas && parseFloat(d.spend) > 10000) { minRoas = r; worstDay = d.date; }
                        });
                        
                        let avgTrendRoas = totalTrendRoas / activeDays.length;
                        
                        // Calculate Volatility (Standard Deviation)
                        let variance = roasArray.reduce((acc, val) => acc + Math.pow(val - avgTrendRoas, 2), 0) / roasArray.length;
                        let stdDev = Math.sqrt(variance);
                        let isVolatile = stdDev > (avgTrendRoas * 0.5); // If std dev is > 50% of mean
                        
                        // Trend Direction (Compare first half to second half)
                        let halfPoint = Math.floor(activeDays.length / 2);
                        let firstHalf = activeDays.slice(0, halfPoint);
                        let secondHalf = activeDays.slice(halfPoint);
                        
                        let firstHalfGmv = firstHalf.reduce((sum, d) => sum + parseFloat(d.gmv || 0), 0);
                        let secondHalfGmv = secondHalf.reduce((sum, d) => sum + parseFloat(d.gmv || 0), 0);

                        // ==========================================
                        // 1. COMPUTE TRAFFIC & FUNNEL METRICS FIRST
                        // ==========================================
                        let maxImpressionsDay = activeDays.reduce((max, d) => (parseInt(d.impressions) > parseInt(max.impressions) ? d : max), activeDays[0]);
                        let maxClicksDay = activeDays.reduce((max, d) => (parseInt(d.clicks) > parseInt(max.clicks) ? d : max), activeDays[0]);
                        
                        let impCtr = parseInt(maxImpressionsDay.impressions) > 0 ? (parseInt(maxImpressionsDay.clicks) / parseInt(maxImpressionsDay.impressions) * 100) : 0;
                        let clkCvr = parseInt(maxClicksDay.clicks) > 0 ? (parseInt(maxClicksDay.orders) / parseInt(maxClicksDay.clicks) * 100) : 0;
                        
                        let isImpressionLeak = (parseInt(maxImpressionsDay.impressions) > 1000 && impCtr < 1.0);
                        let isBounceAnomaly = (parseInt(maxClicksDay.clicks) > 50 && clkCvr < 0.5);

                        // ==========================================
                        // 2. BUILD FINANCIAL NARRATIVE (WITH CROSS-REFERENCE)
                        // ==========================================
                        let trendHtml = '';
                        let trendColor = '';
                        
                        if (isVolatile) {
                            let cause = isBounceAnomaly ? "Akar masalah ada di <b>Trafik (Anomali Bounce Rate)</b>, banyak klik bodong yang merusak rasio konversi." : "Algoritma GMV Max sedang kebingungan mencari audiens yang tepat.";
                            trendHtml = `<div style="font-weight: 700; color: #dc2626; font-size: 0.8rem; margin-bottom: 0.2rem;">🎢 Deteksi Volatilitas ROAS</div>
                                         <div style="font-size: 0.7rem; color: var(--dsh-muted);">Performa sangat tidak stabil (ROAS terbaik <b>${maxRoas.toFixed(1)}x</b> vs terburuk <b>${minRoas.toFixed(1)}x</b>). 🔗 <b>Korelasi AI:</b> ${cause} 💡 <b>Saran:</b> Hentikan perubahan budget/target ROAS agar mesin stabil.</div>`;
                            trendColor = '#dc2626';
                        } else if (secondHalfGmv > firstHalfGmv * 1.2) {
                            let cause = (impCtr >= 1.0 && clkCvr >= 0.5) ? "Kenaikan ini didukung penuh oleh <b>Distribusi Funnel Trafik yang sangat sehat</b> (cek grafik di bawah)." : "Mesin mulai panas meskipun metrik klik belum sempurna.";
                            trendHtml = `<div style="font-weight: 700; color: #16a34a; font-size: 0.8rem; margin-bottom: 0.2rem;">📈 Momentum Algoritma (Uptrend)</div>
                                         <div style="font-size: 0.7rem; color: var(--dsh-muted);">GMV Paruh-2 melampaui Paruh-1. Algoritma optimal pada <b>${formatIndoDate(bestDay)}</b>. 🔗 <b>Korelasi AI:</b> ${cause} 💡 <b>Saran:</b> Jangan ubah Target ROAS saat ini.</div>`;
                            trendColor = '#16a34a';
                        } else if (secondHalfGmv < firstHalfGmv * 0.8) {
                            let cause = isImpressionLeak ? "Penyebab utamanya terlihat di <b>Trafik (Kebocoran Impresi Ekstrem)</b>; iklan tayang tapi orang malas klik." : "Trafik melemah, kemungkinan karena kalah bersaing harga dengan kompetitor.";
                            trendHtml = `<div style="font-weight: 700; color: #f59e0b; font-size: 0.8rem; margin-bottom: 0.2rem;">📉 Peringatan Downtrend</div>
                                         <div style="font-size: 0.7rem; color: var(--dsh-muted);">GMV merosot perlahan (puncak anjlok di <b>${formatIndoDate(worstDay)}</b>). 🔗 <b>Korelasi AI:</b> ${cause} 💡 <b>Saran:</b> Segera evaluasi harga atau perbarui foto produk di GMV Max.</div>`;
                            trendColor = '#f59e0b';
                        } else {
                            trendHtml = `<div style="font-weight: 700; color: #3b82f6; font-size: 0.8rem; margin-bottom: 0.2rem;">🛥️ Konvergensi Stabil</div>
                                         <div style="font-size: 0.7rem; color: var(--dsh-muted);">ROAS harian sangat stabil di <b>${avgTrendRoas.toFixed(1)}x</b>. 🔗 <b>Korelasi AI:</b> Trafik dan konversi berjalan selaras tanpa lonjakan aneh. 💡 <b>Saran:</b> Sistem GMV Max telah konvergen. Anda bisa naikkan Target ROAS 5% per hari.</div>`;
                            trendColor = '#3b82f6';
                        }
                        
                        insightDailyEl.innerHTML = trendHtml;
                        insightDailyEl.style.borderLeftColor = trendColor;
                        
                        // ==========================================
                        // 3. BUILD TRAFFIC NARRATIVE (WITH CROSS-REFERENCE)
                        // ==========================================
                        const insightTrafficDailyEl = document.getElementById('insightDailyTraffic');
                        if (insightTrafficDailyEl) {
                            let tfHtml = '';
                            let tfColor = '';
                            
                            if (isImpressionLeak) {
                                let impact = (secondHalfGmv < firstHalfGmv * 0.8) ? "Sistem akhirnya menghukum Anda dengan <b>Downtrend Finansial</b> di grafik atas." : "Hati-hati, lambat laun ini akan menyeret ROAS Anda ke bawah.";
                                tfHtml = `<div style="font-weight: 700; color: #dc2626; font-size: 0.8rem; margin-bottom: 0.2rem;">🚨 Kebocoran Impresi Ekstrem</div>
                                          <div style="font-size: 0.7rem; color: var(--dsh-muted);">Pada <b>${formatIndoDate(maxImpressionsDay.date)}</b>, ada <b>${parseInt(maxImpressionsDay.impressions).toLocaleString('id-ID')}</b> impresi tapi CTR hanya <b>${impCtr.toFixed(2)}%</b>. 🔗 <b>Dampak Finansial:</b> ${impact} 💡 <b>Saran:</b> Iklan tayang tapi diabaikan. Cek harga/thumbnail segera!</div>`;
                                tfColor = '#dc2626';
                            } else if (isBounceAnomaly) {
                                let impact = isVolatile ? "Inilah alasan mengapa grafik <b>Finansial Anda sangat fluktuatif (Volatile)</b>." : "Budget Anda habis dimakan klik tanpa omzet.";
                                tfHtml = `<div style="font-weight: 700; color: #eab308; font-size: 0.8rem; margin-bottom: 0.2rem;">⚠️ Anomali *Bounce Rate* (Klik Bodong)</div>
                                          <div style="font-size: 0.7rem; color: var(--dsh-muted);">Pada <b>${formatIndoDate(maxClicksDay.date)}</b>, terjadi <b>${parseInt(maxClicksDay.clicks).toLocaleString('id-ID')}</b> klik, tapi nyaris 0 pesanan (CVR <b>${clkCvr.toFixed(2)}%</b>). 🔗 <b>Dampak Finansial:</b> ${impact} 💡 <b>Saran:</b> Cek log kompetitor (apakah Flash Sale?) atau ketersediaan stok Anda.</div>`;
                                tfColor = '#eab308';
                            } else {
                                let impact = (secondHalfGmv > firstHalfGmv * 1.2) ? "Kondisi sehat ini mendorong <b>Momentum Algoritma (Uptrend)</b> pada grafik finansial Anda." : "Finansial Anda terlindungi dari kebocoran yang tidak perlu.";
                                tfHtml = `<div style="font-weight: 700; color: #16a34a; font-size: 0.8rem; margin-bottom: 0.2rem;">✅ Distribusi *Funnel* Sehat</div>
                                          <div style="font-size: 0.7rem; color: var(--dsh-muted);">Tidak ada kebocoran parah pada puncak trafik (<b>${formatIndoDate(maxImpressionsDay.date)}</b>). Konversi ke pesanan mengalir normal. 🔗 <b>Dampak Finansial:</b> ${impact}</div>`;
                                tfColor = '#16a34a';
                            }
                            insightTrafficDailyEl.innerHTML = tfHtml;
                            insightTrafficDailyEl.style.borderLeftColor = tfColor;
                        }
                        
                    } else {
                        insightDailyEl.innerHTML = `<div style="font-weight: 700; color: var(--dsh-muted); font-size: 0.85rem; margin-bottom: 0.3rem;">⏳ Butuh Lebih Banyak Hari</div>
                                                    <div style="font-size: 0.72rem; color: var(--dsh-muted);">AI membutuhkan minimal 3 hari data aktif untuk membaca tren dan volatilitas.</div>`;
                        insightDailyEl.style.borderLeftColor = 'var(--dsh-border)';
                        
                        let itEl = document.getElementById('insightDailyTraffic');
                        if (itEl) {
                            itEl.innerHTML = insightDailyEl.innerHTML;
                            itEl.style.borderLeftColor = 'var(--dsh-border)';
                        }
                    }
                }

            }
        } else if (ctxHist) {
             histContainer.innerHTML = '<div style="text-align:center; padding: 40px; color: var(--dsh-muted);">Data rawHistorical tidak ditemukan atau kosong.</div>';
        }
    } catch (err) {
        if (histContainer) {
            histContainer.innerHTML = '<div style="color:#dc2626; padding: 20px; font-family: monospace;"><b>JS Error:</b> ' + err.message + '<br>' + err.stack + '</div>';
        }
    }
});

function toggleGmsItem(itemId, action, btn) {
    if (!confirm('Apakah Anda yakin ingin ' + (action === 'add' ? 'mengaktifkan' : 'mematikan') + ' GMS untuk item ini?')) return;
    
    let originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat spin-icon" style="display:inline-block"></i> Proses...';
    
    fetch('{{ route("marketplace.ads.gms.action") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            store_id: '{{ $storeId ?? "" }}',
            item_id: itemId,
            action: action
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === 'success') {
            showToast(res.message);
            // Ubah tombol
            if (action === 'remove') {
                btn.className = 'btn btn-sm btn-outline-success';
                btn.innerHTML = '<i class="bi bi-play-circle"></i> Aktifkan GMS';
                btn.setAttribute('onclick', `toggleGmsItem('${itemId}', 'add', this)`);
            } else {
                btn.className = 'btn btn-sm btn-outline-danger';
                btn.innerHTML = '<i class="bi bi-pause-circle"></i> Hentikan GMS';
                btn.setAttribute('onclick', `toggleGmsItem('${itemId}', 'remove', this)`);
            }
        } else {
            showToast('Error: ' + res.message);
            btn.innerHTML = originalHtml;
        }
    })
    .catch(err => {
        showToast('Terjadi kesalahan koneksi.');
        btn.innerHTML = originalHtml;
    })
    .finally(() => {
        btn.disabled = false;
    });
}

function openGmsSettingsForProduct(campaignId) {
    if (campaignId) {
        document.getElementById('gmsCampaignId').value = campaignId;
    } else {
        document.getElementById('gmsCampaignId').value = '';
    }
    // Pindah ke tab Kampanye dan scroll ke atas
    document.querySelector('.dash-tab-m[data-target="tab-campaigns"]').click();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function submitInlineGms(channelItemId, campaignId, storeId, btnElement) {
    const budgetInput = document.getElementById('inlineBudget_' + channelItemId).value;
    const roasInput = document.getElementById('inlineRoas_' + channelItemId).value;
    
    if (!budgetInput && !roasInput) {
        showToast('Harap isi minimal salah satu pengaturan (Budget atau ROAS).');
        return;
    }

    const originalHtml = btnElement.innerHTML;
    btnElement.disabled = true;
    btnElement.innerHTML = '<i class="bi bi-arrow-repeat spin-icon"></i>';

    fetch('{{ route("marketplace.ads.gms.campaign.edit") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            store_id: storeId,
            daily_budget: budgetInput,
            roas_target: roasInput,
            campaign_id: campaignId || null
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === 'success') {
            btnElement.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
            setTimeout(() => {
                btnElement.innerHTML = originalHtml;
                btnElement.disabled = false;
            }, 2000);
        } else {
            showToast(res.message || 'Gagal menyimpan pengaturan.');
            btnElement.innerHTML = originalHtml;
            btnElement.disabled = false;
        }
    })
    .catch(err => {
        showToast('Terjadi kesalahan koneksi.');
        btnElement.innerHTML = originalHtml;
        btnElement.disabled = false;
    });
}

function submitInlineCpc(channelItemId, campaignId, storeId, btnElement) {
    const budgetInput = document.getElementById('inlineBudget_' + channelItemId).value;
    const roasInput = document.getElementById('inlineRoas_' + channelItemId).value;
    
    if (!budgetInput && !roasInput) {
        showToast('Harap isi minimal salah satu pengaturan (Budget atau ROAS) untuk kampanye CPC.');
        return;
    }

    if (!campaignId) {
        showToast('ID Kampanye CPC tidak ditemukan.');
        return;
    }

    const originalHtml = btnElement.innerHTML;
    btnElement.disabled = true;
    btnElement.innerHTML = '<i class="bi bi-arrow-repeat spin-icon"></i>';

    fetch('{{ route("marketplace.ads.cpc.campaign.edit") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            store_id: storeId,
            daily_budget: budgetInput,
            roas_target: roasInput,
            campaign_id: campaignId
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === 'success') {
            btnElement.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
            setTimeout(() => {
                btnElement.innerHTML = originalHtml;
                btnElement.disabled = false;
            }, 2000);
        } else {
            showToast(res.message || 'Gagal menyimpan pengaturan CPC.');
            btnElement.innerHTML = originalHtml;
            btnElement.disabled = false;
        }
    })
    .catch(err => {
        showToast('Terjadi kesalahan koneksi.');
        btnElement.innerHTML = originalHtml;
        btnElement.disabled = false;
    });
}

function submitGmsSettings(e) {
    e.preventDefault();
    
    const btn = document.getElementById('btnSubmitGmsSettings');
    const storeId = document.getElementById('gmsStoreId').value;
    const dailyBudget = document.getElementById('gmsDailyBudget').value;
    const roasTarget = document.getElementById('gmsRoasTarget').value;
    const campaignId = document.getElementById('gmsCampaignId').value;
    
    if (!dailyBudget && !roasTarget) {
        showToast('Harap isi minimal salah satu pengaturan (Budget atau ROAS).');
        return;
    }
    
    let originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat spin-icon" style="display:inline-block"></i> Menyimpan...';
    
    fetch('{{ route("marketplace.ads.gms.campaign.edit") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            store_id: storeId,
            daily_budget: dailyBudget,
            roas_target: roasTarget,
            campaign_id: campaignId || null
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === 'success') {
            showToast(res.message);
        } else {
            showToast('Error: ' + res.message);
        }
    })
    .catch(err => {
        showToast('Terjadi kesalahan koneksi.');
    })
    .finally(() => {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
    });
}
// INNER TABS LOGIC
document.querySelectorAll('.inner-tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        // Hapus class active dari semua tombol di grup yang sama
        const container = this.closest('.dash-tabs-modern');
        container.querySelectorAll('.inner-tab-btn').forEach(b => b.classList.remove('active'));
        
        // Tambahkan class active ke tombol yang diklik
        this.classList.add('active');
        
        // Sembunyikan semua inner pane
        const targetId = this.getAttribute('data-inner-target');
        const parentPane = this.closest('.tab-pane');
        parentPane.querySelectorAll('.inner-tab-pane').forEach(pane => {
            pane.style.display = 'none';
            pane.classList.remove('active');
        });
        
        // Tampilkan inner pane yang dituju
        const targetPane = document.getElementById(targetId);
        if(targetPane) {
            targetPane.style.display = 'block';
            // setTimeout agar animasi css bisa jalan (karena ganti display)
            setTimeout(() => targetPane.classList.add('active'), 10);
        }
    });
});

function saveAdsSetting() {
    const form = document.getElementById('formAdsSetting');
    const formData = new FormData(form);
    const storeIdElement = document.querySelector('select[name="store_id"]');
    const storeId = storeIdElement ? storeIdElement.value : null;
    
    if(!storeId) {
        showToast("Pilih toko terlebih dahulu dari filter di atas.");
        return;
    }
    
    const payload = {
        store_id: storeId,
        target_roas: formData.get('target_roas'),
        notes: formData.get('notes')
    };

    fetch('/marketplace/ads-settings/save', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            // Gunakan meta tag CSRF token yang sudah ada di head
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : ''
        },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            showToast(data.message);
        } else {
            showToast('Gagal menyimpan: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Terjadi kesalahan saat menyimpan pengaturan.');
    });
}
</script>
@endif
<script>
function showInlineEdit(el) {
    const wrapper = el.closest('.inline-edit-wrapper');
    wrapper.querySelector('.inline-edit-text').style.display = 'none';
    const inputContainer = wrapper.querySelector('.inline-edit-input');
    inputContainer.style.display = 'inline-flex';
    const input = inputContainer.querySelector('input');
    input.focus();
    
    // Add event listener for Enter key
    input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            saveInlineEdit(inputContainer.querySelector('.text-success'));
        }
    }, { once: true });
}

function cancelInlineEdit(el) {
    const wrapper = el.closest('.inline-edit-wrapper');
    wrapper.querySelector('.inline-edit-input').style.display = 'none';
    wrapper.querySelector('.inline-edit-text').style.display = 'inline';
    
    // Reset value
    const input = wrapper.querySelector('input');
    input.value = wrapper.getAttribute('data-val');
}

function saveInlineEdit(el) {
    const wrapper = el.closest('.inline-edit-wrapper');
    const input = wrapper.querySelector('input');
    const newVal = input.value;
    const oldVal = wrapper.getAttribute('data-val');
    
    if (newVal === oldVal) {
        cancelInlineEdit(el);
        return;
    }
    
    const type = wrapper.getAttribute('data-type');
    const campId = wrapper.getAttribute('data-camp');
    const storeId = "{{ $storeId }}";
    if (storeId === 'all') { showToast('Mode Semua Toko — pilih satu toko dulu untuk aksi ini.'); return; }
    
    let payload = {
        _token: document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '',
        store_id: storeId,
        campaign_id: campId
    };
    
    if (type === 'daily_budget') {
        payload.daily_budget = newVal;
    } else if (type === 'roas_target') {
        payload.roas_target = newVal;
    }
    
    const originalContent = wrapper.querySelector('.inline-edit-text').innerHTML;
    wrapper.querySelector('.inline-edit-text').innerHTML = '<span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span> Menyimpan...';
    wrapper.querySelector('.inline-edit-input').style.display = 'none';
    wrapper.querySelector('.inline-edit-text').style.display = 'inline';
    
    fetch('/marketplace/ads-dashboard/cpc-campaign-edit', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': payload._token
        },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Update UI
            wrapper.setAttribute('data-val', newVal);
            let displayVal = '';
            if (type === 'daily_budget') {
                let numVal = parseFloat(newVal);
                displayVal = numVal > 0 ? 'Rp ' + numVal.toLocaleString('id-ID') : 'Unlimited';
            } else {
                let numVal = parseFloat(newVal);
                displayVal = numVal > 0 ? numVal.toFixed(2) + 'x' : 'Auto';
            }
            wrapper.querySelector('.inline-edit-text').innerHTML = displayVal + ' <i class="bi bi-pencil-fill text-muted" style="font-size: .6rem; opacity: 0.5;"></i>';
            
            // Optional: showToast('Tersimpan!');
        } else {
            showToast('Gagal menyimpan: ' + (data.message || 'Unknown error'));
            wrapper.querySelector('.inline-edit-text').innerHTML = originalContent;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Terjadi kesalahan saat menyimpan pengaturan.');
        wrapper.querySelector('.inline-edit-text').innerHTML = originalContent;
    });
}
function toggleCampaignStatus(el, campId, action) {
    if (!confirm('Apakah Anda yakin ingin ' + (action === 'pause' ? 'menjeda (pause)' : 'melanjutkan (resume)') + ' kampanye ini?')) {
        return;
    }
    
    const storeId = "{{ $storeId }}";
    if (storeId === 'all') { showToast('Mode Semua Toko — pilih satu toko dulu untuk aksi ini.'); return; }
    let payload = {
        _token: document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '',
        store_id: storeId,
        campaign_id: campId,
        status_action: action
    };
    
    // Show loading on the icon
    const originalClass = el.className;
    el.className = 'spinner-border spinner-border-sm text-primary';
    el.style.opacity = '1';
    
    fetch('/marketplace/ads-dashboard/cpc-campaign-edit', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': payload._token
        },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Update UI by reloading the page to reflect the new badge, or we can just reload the page for simplicity
            window.location.reload();
        } else {
            showToast('Gagal mengubah status: ' + (data.message || 'Unknown error'));
            el.className = originalClass;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Terjadi kesalahan sistem.');
        el.className = originalClass;
    });
}
</script>
@endpush
