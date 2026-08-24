@extends('layouts.app')

@section('title', 'Analisis Iklan Shopee')

@push('head')
@include('dashboard.partials._styles')

@php
    // Fallbacks to prevent undefined variable errors when $stores->isEmpty() triggers early return
    $kpi = $kpi ?? [];
    $dailyChartData = $dailyChartData ?? [];
    $heatmapData = $heatmapData ?? [];
    $historicalData = $historicalData ?? [];
    $itemPerformance = collect($itemPerformance ?? []);
    $syncRuns = $syncRuns ?? collect();
    $lastSuccessRun = $lastSuccessRun ?? null;
    $insightTraffic = $insightTraffic ?? collect();
    $campaigns = $campaigns ?? collect();
    $campaignSchedules = collect($campaignSchedules ?? []);
    $adsSetting = $adsSetting ?? (object)[];
    $metrics = $metrics ?? [];
    $heatmapInternalItems = collect($campaigns)->filter(function ($campaign) {
        return !empty($campaign->internal_item_id) && $campaign->internalItem;
    })->map(function ($campaign) {
        return [
            'store_id' => (int) $campaign->store_id,
            'campaign_name' => $campaign->campaign_name ?: ($campaign->channel_campaign_id ?? 'Campaign'),
            'internal_item_id' => (int) $campaign->internal_item_id,
            'internal_item_name' => $campaign->internalItem->name ?: ('Item #' . $campaign->internal_item_id),
            'category' => $campaign->item_category ?? $campaign->internalItem->category?->name,
        ];
    })->unique(fn ($item) => $item['store_id'] . '|' . $item['internal_item_id'] . '|' . $item['campaign_name'])->values();

    $analysisCurrentPeriod = collect($historicalData)->get(0, []);
    $analysisPreviousPeriod = collect($historicalData)->get(1, []);
    $formatAnalysisDate = function ($date) {
        if (!$date) {
            return '-';
        }

        try {
            return \Carbon\Carbon::parse($date)->format('d/m/Y');
        } catch (\Throwable) {
            return (string) $date;
        }
    };
    $analysisCurrentRange = $formatAnalysisDate(data_get($analysisCurrentPeriod, 'start', $dateFrom ?? null))
        . ' – ' . $formatAnalysisDate(data_get($analysisCurrentPeriod, 'end', $dateTo ?? null));
    $analysisPreviousRange = $formatAnalysisDate(data_get($analysisPreviousPeriod, 'start'))
        . ' – ' . $formatAnalysisDate(data_get($analysisPreviousPeriod, 'end'));
    $analysisTwoPeriodsAgo = collect($historicalData)->get(2, []);
    $analysisTwoPeriodsAgoRange = $formatAnalysisDate(data_get($analysisTwoPeriodsAgo, 'start'))
        . ' – ' . $formatAnalysisDate(data_get($analysisTwoPeriodsAgo, 'end'));
    
    // Default JS empty arrays just in case they are used in scripts without ??
    $dailyChartDataJson = json_encode($dailyChartData);
    $heatmapDataJson = json_encode($heatmapData);
    $historicalDataJson = json_encode($historicalData);
@endphp

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

/* ─────────────────────────────────────────────────────────────────────────────
   MODERN DASHBOARD UI UPGRADE (Rich Aesthetics, Glassmorphism, Micro-animations)
───────────────────────────────────────────────────────────────────────────── */
body {
    font-family: 'Inter', sans-serif !important;
}

.compare-mode-tabs {
    display: inline-flex;
    gap: .2rem;
    padding: .2rem;
    border: 1px solid var(--dsh-border);
    border-radius: 9px;
    background: var(--dsh-bg);
}
.compare-mode-tab {
    border: 0;
    border-radius: 7px;
    padding: .32rem .55rem;
    background: transparent;
    color: var(--dsh-muted);
    font-size: .65rem;
    font-weight: 750;
    white-space: nowrap;
    transition: background .15s ease, color .15s ease, box-shadow .15s ease;
}
.compare-mode-tab:hover,
.compare-mode-tab.active {
    color: var(--text);
    background: var(--card-bg);
    box-shadow: 0 1px 3px rgba(15,23,42,.12);
}
@media (max-width: 768px) {
    .compare-mode-tabs { display: none; }
    #compareModeSelect { display: block !important; min-width: 180px !important; }
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
    background: var(--hero-bg);
    border-bottom: 1px solid var(--card-border);
    font-size: .75rem;
    font-weight: 700;
    color: var(--dsh-muted);
    padding: .75rem 1rem;
    text-align: left;
    white-space: nowrap;
    letter-spacing: 0.02em;
}
body[data-theme="dark"] .dpanel-table thead th { background: var(--hero-bg); }
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
    border-radius: 16px;
    gap: .35rem;
    border:1px solid rgba(148,163,184,.18);
    box-shadow:0 12px 28px rgba(15,23,42,.06);
}
body[data-theme="dark"] .dash-tabs-modern { background: linear-gradient(180deg, rgba(15,23,42,.96), rgba(30,41,59,.92)); border-color:rgba(51,65,85,.85); box-shadow:none; }
.dash-tab-m {
    border: none; background: transparent; padding: .72rem 1rem; border-radius: 12px;
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
    grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
    gap:.75rem;
    margin-bottom:.95rem;
}

.ads-kpi{
    position:relative;
    display:flex;
    flex-direction:column;
    min-height:95px;
    padding:.85rem 1rem;
    overflow:hidden;
    background: linear-gradient(0deg, var(--kpi-bg, transparent), var(--kpi-bg, transparent)), var(--card-bg);
}

body[data-theme="dark"] .ads-kpi{
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

.ads-kpi.kpi-profit{ --kpi-accent-start:#16a34a; --kpi-accent-end:#22c55e; --kpi-bg: rgba(22, 163, 74, 0.08); }
.ads-kpi.kpi-revenue{ --kpi-accent-start:#2563eb; --kpi-accent-end:#38bdf8; --kpi-bg: rgba(3, 105, 161, 0.07); }
.ads-kpi.kpi-cogs{ --kpi-accent-start:#64748b; --kpi-accent-end:#94a3b8; --kpi-bg: rgba(148, 163, 184, 0.08); }
.ads-kpi.kpi-spend{ --kpi-accent-start:#b45309; --kpi-accent-end:#f59e0b; --kpi-bg: rgba(245, 158, 11, 0.08); }
.ads-kpi.kpi-roas{ --kpi-accent-start:#1d4ed8; --kpi-accent-end:#60a5fa; --kpi-bg: rgba(37, 99, 235, 0.07); }

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
    background: var(--hero-bg) !important;
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
    .ads-tabs-wrap {
        position:sticky;
        top:3.1rem;
        z-index:300;
        margin-inline:-.75rem;
        padding:.25rem .75rem .35rem !important;
        background:var(--card-bg, #fff);
        border-bottom:1px solid var(--card-border, rgba(148,163,184,.18));
    }
    body[data-theme="dark"] .ads-tabs-wrap {
        background:var(--card-bg, #0f172a);
    }
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
    border-radius: 16px;
    padding: .3rem;
    box-shadow: 0 12px 28px rgba(15,23,42,.06);
    position: sticky;
    top: 0;
    z-index: 250;
    background: linear-gradient(180deg, rgba(248,250,252,.96), rgba(241,245,249,.92));
    border: 1px solid rgba(148,163,184,.18);
}
body[data-theme="dark"] .dash-tabs-modern { background: linear-gradient(180deg, rgba(15,23,42,.96), rgba(30,41,59,.92)); border-color:rgba(51,65,85,.85); box-shadow:none; }
.dash-tab-m { border-radius: 12px; font-size: .78rem; }
.dash-tab-m.active { box-shadow: 0 10px 20px rgba(15,23,42,.18); }

/* Tombol & chip: radius 7px konsisten, tanpa bayangan */
.btn-pill, .role-chip, .mini-log-toggle { border-radius: 7px !important; box-shadow: none !important; }

/* Tabel: header kecil-tenang tanpa uppercase, padding rapat ala table-list */
.table-responsive {
    max-height: 480px;
    overflow-y: auto;
    scrollbar-width: thin;
}
.dpanel-table thead th {
    font-size: .68rem;
    text-transform: none;
    letter-spacing: 0;
    color: #64748b;
    padding: .52rem .62rem;
    position: sticky;
    top: 0;
    z-index: 10;
    background: var(--hero-bg);
    border-bottom: none;
    box-shadow: inset 0 -1px 0 var(--card-border);
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
    /* Flatpickr harus selalu 7 kolom. max-width 34px membuatnya
       memadat menjadi 9 kolom pada kalender desktop/mobile tertentu. */
    width: 14.2857143%;
    max-width: 14.2857143%;
    flex-basis: 14.2857143%;
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
    max-width:1040px; /* Selaras dengan shipments */
    width:100%;
    min-width:0;
    box-sizing:border-box;
    margin-inline:auto;
}

/* Layout lebar hanya untuk desktop besar; tablet dan mobile tetap fluid. */
@media (min-width: 1200px) {
    .ads-shell {
        min-width: 1040px;
    }
}

@media (max-width: 1199.98px) {
    .ads-shell {
        width:100%;
        min-width:0;
        max-width:none;
        padding-inline:.75rem;
    }
}

.ads-hero{
    position:relative;
    top:0;
    z-index:auto;
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
    position:relative;
    top:auto;
    z-index:auto;
    padding:.25rem 0 .35rem;
    background:var(--card-bg,#fff);
    border-bottom:1px solid var(--card-border,rgba(148,163,184,.18));
    overflow-x:auto;
    scrollbar-width:none;
}
.ads-tabs-wrap .dash-tabs-modern{
    position:relative;
    top:auto;
    z-index:auto;
}
.ads-tabs-wrap::-webkit-scrollbar{display:none;}
body[data-theme="dark"] .ads-tabs-wrap{background:var(--card-bg,#0f172a);}

.daily-trend-stats{
    display:flex;
    flex-wrap:wrap;
    justify-content:flex-end;
    gap:.5rem;
}
.daily-trend-stat{
    display:inline-flex;
    flex-direction:column;
    gap:.08rem;
    min-width:86px;
    padding:.42rem .58rem;
    border:1px solid var(--dsh-border);
    border-radius:8px;
    background:var(--bg,#fff);
    line-height:1.1;
    text-align:right;
}
.daily-trend-stat-label{font-size:.6rem;color:var(--dsh-muted);font-weight:650;}
.daily-trend-stat-value{font-size:.76rem;font-weight:800;font-variant-numeric:tabular-nums;}
@media (max-width:575.98px){
    .daily-trend-stats{justify-content:flex-start;width:100%;}
    .daily-trend-stat{flex:1 1 calc(50% - .35rem);min-width:0;text-align:left;}
}

.ads-tabs-wrap::before{
    display:none;
}

.dash-tabs-modern{
    display:inline-flex;
    gap:.25rem;
    padding:.24rem;
    border-radius:16px;
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
    border-radius:10px;
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

.ads-phase2-simulation{
    margin-top:.85rem;
    padding:.8rem;
    border:1px solid rgba(37,99,235,.16);
    border-radius:13px;
    background:linear-gradient(135deg, rgba(239,246,255,.72), rgba(248,250,252,.92));
}

body[data-theme="dark"] .ads-phase2-simulation{
    border-color:rgba(96,165,250,.25);
    background:linear-gradient(135deg, rgba(30,58,138,.22), rgba(15,23,42,.78));
}

.ads-phase2-simulation-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:.7rem;
    flex-wrap:wrap;
    margin-bottom:.65rem;
}

.ads-phase2-simulation-title{
    display:flex;
    align-items:center;
    gap:.4rem;
    color:var(--text);
    font-size:.78rem;
    font-weight:900;
}

.ads-phase2-simulation .form-label{
    margin-bottom:.25rem;
    color:var(--dsh-muted);
    font-size:.64rem;
    font-weight:850;
}

.ads-phase2-simulation .form-control,
.ads-phase2-simulation .form-select{
    min-height:35px;
    border-radius:9px;
    border-color:rgba(148,163,184,.28);
    color:var(--text);
    font-size:.75rem;
    font-weight:750;
}

.ads-phase2-result-grid{
    display:grid;
    grid-template-columns:repeat(4, minmax(0, 1fr));
    gap:.45rem;
    margin-top:.7rem;
}

.ads-phase2-result-card{
    min-width:0;
    padding:.55rem .6rem;
    border:1px solid rgba(148,163,184,.2);
    border-radius:10px;
    background:rgba(255,255,255,.68);
}

body[data-theme="dark"] .ads-phase2-result-card{
    background:rgba(15,23,42,.58);
    border-color:rgba(51,65,85,.9);
}

.ads-phase2-result-label{
    color:var(--dsh-muted);
    font-size:.61rem;
    font-weight:750;
}

.ads-phase2-result-value{
    margin-top:.12rem;
    color:var(--text);
    font-size:.82rem;
    font-weight:900;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}

@media (max-width: 767.98px){
    .ads-phase2-result-grid{ grid-template-columns:repeat(2, minmax(0, 1fr)); }
}

.ads-phase3-impact{
    margin-top:.85rem;
    padding:.8rem;
    border:1px solid rgba(15,118,110,.18);
    border-radius:13px;
    background:linear-gradient(135deg, rgba(240,253,250,.72), rgba(248,250,252,.92));
}

body[data-theme="dark"] .ads-phase3-impact{
    border-color:rgba(45,212,191,.25);
    background:linear-gradient(135deg, rgba(19,78,74,.22), rgba(15,23,42,.78));
}

.ads-phase3-impact-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:.7rem;
    flex-wrap:wrap;
    margin-bottom:.65rem;
}

.ads-phase3-impact-title{
    display:flex;
    align-items:center;
    gap:.4rem;
    color:var(--text);
    font-size:.78rem;
    font-weight:900;
}

.ads-phase3-impact-grid{
    display:grid;
    grid-template-columns:repeat(6, minmax(0, 1fr));
    gap:.4rem;
}

.ads-phase3-impact-step{
    min-width:0;
    padding:.5rem .55rem;
    border:1px solid rgba(148,163,184,.2);
    border-radius:10px;
    background:rgba(255,255,255,.68);
}

body[data-theme="dark"] .ads-phase3-impact-step{
    background:rgba(15,23,42,.58);
    border-color:rgba(51,65,85,.9);
}

.ads-phase3-impact-label{ color:var(--dsh-muted); font-size:.6rem; font-weight:750; }
.ads-phase3-impact-value{ margin-top:.12rem; color:var(--text); font-size:.76rem; font-weight:900; white-space:nowrap; }
.ads-phase3-impact-delta{ margin-top:.12rem; font-size:.62rem; font-weight:800; white-space:nowrap; }

.ads-phase3-note{
    margin-top:.6rem;
    padding:.6rem .7rem;
    border:1px solid var(--dsh-border);
    border-radius:10px;
    color:var(--dsh-muted);
    font-size:.66rem;
    line-height:1.5;
}

@media (max-width: 991.98px){
    .ads-phase3-impact-grid{ grid-template-columns:repeat(3, minmax(0, 1fr)); }
}

@media (max-width: 575.98px){
    .ads-phase3-impact-grid{ grid-template-columns:repeat(2, minmax(0, 1fr)); }
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
    // === Navigasi 2 tingkat (grup induk -> sub-tab) ===
    // Setiap panel .tab-pane lama tetap utuh; grup hanya mengatur navigasi.
    // Format sub-tab: [paneId, label, iconClass]
    const ADS_GROUPS = [
        { id: 'grp-ringkasan', panes: [
            ['tab-campaigns', 'Ringkasan', 'bi-grid-1x2'],
            ['tab-analysis', 'Analisa', 'bi-graph-up'],
            ['tab-alerts', 'Alerts & Actions', 'bi-bell'],
        ]},
        { id: 'grp-performa', panes: [
            ['tab-campaign-performance', 'Campaign', 'bi-bar-chart-steps'],
            ['tab-traffic', 'Traffic', 'bi-stoplights'],
            ['tab-items', 'Produk', 'bi-box-seam'],
        ]},
        { id: 'grp-profit', panes: [
            ['tab-profit', 'Profit', 'bi-cash-coin'],
        ]},
        { id: 'grp-funnel', panes: [
            ['tab-funnel', 'Funnel', 'bi-funnel'],
            ['tab-creative', 'Creative & Audience', 'bi-person-video2'],
        ]},
        { id: 'grp-ltv', panes: [
            ['tab-ltv', 'Customer & LTV', 'bi-person-heart'],
        ]},
        { id: 'grp-experiment', panes: [
            ['tab-experiments', 'Experiment', 'bi-bezier2'],
        ]},
        { id: 'grp-control', panes: [
            ['tab-control', 'Control Panel', 'bi-toggles'],
        ]},
        { id: 'grp-settings', panes: [
            ['tab-settings', 'Pengaturan', 'bi-sliders'],
        ]},
    ];

    const paneToGroup = {};
    ADS_GROUPS.forEach(g => g.panes.forEach(p => { paneToGroup[p[0]] = g.id; }));

    const primaryBtns = document.querySelectorAll('#adsPrimaryTabs .dash-tab-m[data-group]');
    const subTabsWrap = document.getElementById('adsSubTabsWrap');
    const subTabsBar = document.getElementById('adsSubTabs');
    const tabPanes = document.querySelectorAll('.tab-pane');

    function showPane(paneId) {
        tabPanes.forEach(p => p.classList.remove('active'));
        const pane = document.getElementById(paneId);
        if (pane) pane.classList.add('active');
        localStorage.setItem('adsDashboardActivePane', paneId);
        // charts sering perlu re-layout saat panel baru tampil
        try { window.dispatchEvent(new Event('resize')); } catch (e) {}
    }

    function renderSubTabs(groupId, activePaneId) {
        const group = ADS_GROUPS.find(g => g.id === groupId) || ADS_GROUPS[0];
        subTabsBar.innerHTML = '';
        // Grup dengan 1 panel: sembunyikan baris sub-tab (tak perlu).
        if (group.panes.length <= 1) {
            subTabsWrap.style.display = 'none';
        } else {
            subTabsWrap.style.display = '';
            group.panes.forEach(([paneId, label, icon]) => {
                const b = document.createElement('button');
                b.className = 'dash-tab-sm' + (paneId === activePaneId ? ' active' : '');
                b.setAttribute('data-target', paneId);
                b.innerHTML = '<i class="bi ' + icon + '"></i> ' + label;
                b.addEventListener('click', () => {
                    subTabsBar.querySelectorAll('.dash-tab-sm').forEach(x => x.classList.remove('active'));
                    b.classList.add('active');
                    showPane(paneId);
                });
                subTabsBar.appendChild(b);
            });
        }
        showPane(activePaneId);
    }

    function activateGroup(groupId, preferredPaneId) {
        const group = ADS_GROUPS.find(g => g.id === groupId) || ADS_GROUPS[0];
        primaryBtns.forEach(b => b.classList.toggle('active', b.getAttribute('data-group') === group.id));
        const paneIds = group.panes.map(p => p[0]);
        const activePaneId = paneIds.includes(preferredPaneId) ? preferredPaneId : paneIds[0];
        localStorage.setItem('adsDashboardActiveGroup', group.id);
        renderSubTabs(group.id, activePaneId);
    }

    primaryBtns.forEach(btn => {
        btn.addEventListener('click', () => activateGroup(btn.getAttribute('data-group')));
    });

    // Restore: prioritaskan panel terakhir; fallback ke grup lama; default grup pertama.
    (function restore() {
        const savedPane = localStorage.getItem('adsDashboardActivePane')
            || localStorage.getItem('adsDashboardActiveTab'); // kompat lama
        if (savedPane && paneToGroup[savedPane]) {
            activateGroup(paneToGroup[savedPane], savedPane);
            return;
        }
        activateGroup(ADS_GROUPS[0].id);
    })();

    // Buka panel tertentu dari mana saja (dipakai openSyncTab & auto-switch sync).
    window.openAdsPane = function (paneId) {
        const groupId = paneToGroup[paneId];
        if (groupId) activateGroup(groupId, paneId);
    };

    // Shortcut: semua pintu "log" menuju satu tempat — tab Pengaturan.
    window.openSyncTab = function () {
        window.openAdsPane('tab-settings');
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
    const compareModeSelect = document.getElementById('compareModeSelect');
    const compareModeHidden = document.getElementById('compareModeHidden');
    const compareModeStatus = document.getElementById('compareModeStatus');
    const compareModeTabs = document.getElementById('compareModeTabs');
    const submitCompareMode = value => {
        if (compareModeHidden) compareModeHidden.value = value;
        if (compareModeSelect) compareModeSelect.value = value;
        compareModeTabs?.querySelectorAll('[data-compare-mode]').forEach(tab => {
            const active = tab.dataset.compareMode === value;
            tab.classList.toggle('active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        if (compareModeStatus) compareModeStatus.style.display = 'inline-flex';
        const summaryTable = document.getElementById('historicalSummaryTable');
        if (summaryTable) summaryTable.style.opacity = '.45';
        if (compareModeSelect) compareModeSelect.disabled = true;
        compareModeTabs?.querySelectorAll('[data-compare-mode]').forEach(tab => { tab.disabled = true; });
        if (typeof window.updateHistoricalComparison === 'function') {
            window.updateHistoricalComparison(value);
        } else {
            window.__dashLoading?.();
            window.submitAdsFilters(filterForm);
        }
    };
    compareModeSelect?.addEventListener('change', function () {
        submitCompareMode(this.value);
    });
    compareModeTabs?.querySelectorAll('[data-compare-mode]').forEach(tab => {
        tab.addEventListener('click', () => submitCompareMode(tab.dataset.compareMode));
        tab.addEventListener('keydown', event => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                submitCompareMode(tab.dataset.compareMode);
            }
        });
    });

    function ymd(d) { return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0'); }

    // Jangan gunakan new Date('YYYY-MM-DD'): format ISO date-only diparse
    // sebagai UTC oleh browser dan dapat bergeser satu hari di Asia/Jakarta.
    window.parseLocalDate = function(value) {
        if (value instanceof Date) {
            return new Date(value.getFullYear(), value.getMonth(), value.getDate());
        }
        const match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (match) {
            return new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
        }
        return value ? new Date(value) : null;
    };

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
            locale: Object.assign({}, (flatpickr.l10ns && flatpickr.l10ns.id) || {}, {
                firstDayOfWeek: 1
            }),
            showMonths: 1, // kompak — rentang panjang lebih cepat lewat preset di bawah kalender
            dateFormat: 'd M Y',
            altInput: false,
            defaultDate: [parseLocalDate(fromEl.value), parseLocalDate(toEl.value)],
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

    fetch(`${window.AdsDashboardRoutes.realtimeStatus}?store_id=${storeId}`)
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

</script>

<script>
var sortTrafficCol = window.__adsTrafficSortCol || 'spend';
var sortTrafficDir = window.__adsTrafficSortDir || 'desc';

function sortTrafficTable(col) {
    if (sortTrafficCol === col) {
        sortTrafficDir = sortTrafficDir === 'desc' ? 'asc' : 'desc';
    } else {
        sortTrafficCol = col;
        sortTrafficDir = 'desc';
    }

    const tbody = document.querySelector('#trafficTable tbody');
    if (!tbody) return;
    
    const rows = Array.from(tbody.querySelectorAll('tr[data-campaign_name]'));
    if (rows.length === 0) return;

    rows.sort((a, b) => {
        let valA, valB;
        if (col === 'campaign_name') {
            valA = a.getAttribute('data-campaign_name') || '';
            valB = b.getAttribute('data-campaign_name') || '';
            return sortTrafficDir === 'asc' ? valA.localeCompare(valB) : valB.localeCompare(valA);
        } else {
            valA = parseFloat(a.getAttribute('data-' + col)) || 0;
            valB = parseFloat(b.getAttribute('data-' + col)) || 0;
            return sortTrafficDir === 'asc' ? valA - valB : valB - valA;
        }
    });

    rows.forEach(row => tbody.appendChild(row));
}

function trafficToggleCategory(row) {
    row.classList.toggle('open');
    const detail = row.nextElementSibling;
    if (detail && detail.classList.contains('traffic-category-detail')) {
        detail.style.display = (detail.style.display === 'none' || !detail.style.display) ? 'table-row' : 'none';
    }
}

function trafficFormatNumber(value, decimals) {
    return Number(value || 0).toLocaleString('id-ID', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });
}

let trafficQualityChart = null;

function trafficMedian(values) {
    const sorted = values.filter(value => Number.isFinite(value)).sort((a, b) => a - b);
    if (!sorted.length) return 0;
    const middle = Math.floor(sorted.length / 2);
    return sorted.length % 2 ? sorted[middle] : (sorted[middle - 1] + sorted[middle]) / 2;
}

function trafficRenderFunnel(kpi) {
    const values = {
        impressions: Number(kpi.impressions || 0),
        clicks: Number(kpi.clicks || 0),
        orders: Number(kpi.orders || 0),
    };
    const maxValue = Math.max(values.impressions, 1);

    Object.entries(values).forEach(function([key, value]) {
        document.querySelectorAll('[data-traffic-funnel-value="' + key + '"]').forEach(function(el) {
            el.textContent = trafficFormatNumber(value, 0);
        });
        document.querySelectorAll('[data-traffic-funnel-stage="' + key + '"]').forEach(function(el) {
            el.style.width = Math.max(34, (value / maxValue) * 100) + '%';
        });
    });

    document.querySelectorAll('[data-traffic-funnel-rate="ctr"]').forEach(function(el) {
        el.textContent = 'CTR ' + trafficFormatNumber(kpi.ctr, 2) + '%';
    });
    document.querySelectorAll('[data-traffic-funnel-rate="cvr"]').forEach(function(el) {
        el.textContent = 'CVR ' + trafficFormatNumber(kpi.cvr, 2) + '%';
    });
}

function trafficRenderQualityChart(view) {
    const canvas = document.getElementById('trafficQualityChart');
    if (!canvas || typeof Chart === 'undefined') return;

    const rows = ((window.__trafficChartData || {})[view] || []).map(function(row) {
        return {
            ...row,
            x: Number(row.ctr || 0),
            y: Number(row.cvr || 0),
            r: Math.max(4, Math.min(15, 4 + Math.log10(Number(row.impressions || 0) + 1) * 2)),
        };
    });
    const ctrMedian = trafficMedian(rows.map(row => row.x));
    const cvrMedian = trafficMedian(rows.map(row => row.y));
    const colors = {
        'CTR & CVR tinggi': 'rgba(16, 185, 129, .72)',
        'CTR tinggi · CVR rendah': 'rgba(245, 158, 11, .72)',
        'CTR rendah · CVR tinggi': 'rgba(139, 92, 246, .72)',
        'CTR & CVR rendah': 'rgba(239, 68, 68, .72)',
    };
    const groupFor = row => row.x >= ctrMedian
        ? (row.y >= cvrMedian ? 'CTR & CVR tinggi' : 'CTR tinggi · CVR rendah')
        : (row.y >= cvrMedian ? 'CTR rendah · CVR tinggi' : 'CTR & CVR rendah');

    if (trafficQualityChart) trafficQualityChart.destroy();
    trafficQualityChart = new Chart(canvas, {
        type: 'bubble',
        data: {
            datasets: Object.keys(colors).map(function(label) {
                const color = colors[label];
                return {
                    label: label,
                    data: rows.filter(row => groupFor(row) === label),
                    backgroundColor: color,
                    borderColor: color.replace('.72', '1'),
                    borderWidth: 1,
                };
            }),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'nearest', intersect: true },
            plugins: {
                legend: { display: true, position: 'bottom', labels: { boxWidth: 10, font: { size: 9 } } },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const row = context.raw;
                            const money = value => 'Rp ' + Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
                            return [
                                row.name,
                                'CTR: ' + trafficFormatNumber(row.ctr, 2) + '% · CVR: ' + trafficFormatNumber(row.cvr, 2) + '%',
                                'Jangkauan: ' + trafficFormatNumber(row.impressions, 0) + ' · Klik: ' + trafficFormatNumber(row.clicks, 0),
                                'Orders: ' + trafficFormatNumber(row.orders, 0) + ' · CPC: ' + money(row.cpc),
                                'AOV: ' + money(row.aov),
                            ];
                        },
                    },
                },
            },
            scales: {
                x: {
                    beginAtZero: true,
                    title: { display: true, text: 'CTR (%)', font: { size: 10 } },
                    ticks: { callback: value => value + '%' },
                },
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'CVR (%)', font: { size: 10 } },
                    ticks: { callback: value => value + '%' },
                },
            },
        },
    });

    const hint = document.getElementById('trafficScatterHint');
    if (hint) hint.textContent = 'Median CTR ' + trafficFormatNumber(ctrMedian, 2) + '% · CVR ' + trafficFormatNumber(cvrMedian, 2) + '%';
}

function trafficUpdateKpis(view) {
    const kpi = (window.__trafficKpiData || {})[view] || (window.__trafficKpiData || {}).category || {};
    const setValue = function(key, value) {
        document.querySelectorAll('[data-traffic-kpi-value="' + key + '"]').forEach(function(el) {
            el.textContent = value;
        });
    };

    setValue('campaigns', trafficFormatNumber(kpi.campaigns, 0));
    setValue('spend', 'Rp ' + trafficFormatNumber(kpi.spend, 0));
    setValue('impressions', trafficFormatNumber(kpi.impressions, 0));
    setValue('clicks', trafficFormatNumber(kpi.clicks, 0));
    setValue('orders', trafficFormatNumber(kpi.orders, 0));
    setValue('ctr', trafficFormatNumber(kpi.ctr, 2) + '%');
    setValue('cvr', trafficFormatNumber(kpi.cvr, 2) + '%');
    setValue('cpc', 'Rp ' + trafficFormatNumber(kpi.cpc, 0));
    trafficRenderFunnel(kpi);
    trafficRenderQualityChart(view);
}

window.__trafficPerformanceView = function(view) {
    const rows = Array.from(document.querySelectorAll('.traffic-performance-row, .traffic-category-row'));
    const categoryDetails = Array.from(document.querySelectorAll('.traffic-category-detail'));
    const emptyState = document.querySelector('.traffic-view-empty');
    const noDataState = document.querySelector('.traffic-no-data');
    let visibleCount = 0;

    trafficUpdateKpis(view);
    categoryDetails.forEach(function(detail) { detail.style.display = 'none'; });

    rows.forEach(function(row) {
        const isCategoryView = view === 'category';
        const visible = isCategoryView
            ? row.classList.contains('traffic-category-row')
            : row.classList.contains('traffic-performance-row')
                && (row.dataset.trafficViews || '').split(/\s+/).includes(view);
        row.style.display = visible ? '' : 'none';
        row.classList.remove('open');
        if (visible) visibleCount++;
    });

    if (noDataState) noDataState.style.display = rows.length ? 'none' : 'table-row';
    if (emptyState) emptyState.style.display = rows.length && !visibleCount ? 'table-row' : 'none';

    const buttonOn = 'border-radius:999px; font-size:.72rem; padding:.38rem .95rem; background:var(--dsh-accent); color:#fff; border:1px solid var(--dsh-accent); white-space:nowrap;';
    const buttonOff = 'border-radius:999px; font-size:.72rem; padding:.38rem .95rem; color:var(--dsh-muted); border:1px solid var(--dsh-border); background:transparent; white-space:nowrap;';
    const buttons = {
        category: document.getElementById('btnTrafficCategory'),
        roas: document.getElementById('btnTrafficRoas'),
        gms: document.getElementById('btnTrafficGms'),
        unmapped: document.getElementById('btnTrafficUnmapped'),
    };
    Object.entries(buttons).forEach(function([key, button]) {
        if (!button) return;
        const active = key === view;
        button.style.cssText = active ? buttonOn : buttonOff;
        button.setAttribute('aria-selected', active ? 'true' : 'false');
    });
    try { localStorage.setItem('adsTrafficView', view); } catch (e) {}
};

document.addEventListener('DOMContentLoaded', function() {
    let savedView = null;
    try { savedView = localStorage.getItem('adsTrafficView'); } catch (e) {}
    const validViews = ['category', 'roas', 'gms', 'unmapped'];
    window.__trafficPerformanceView(validViews.includes(savedView) ? savedView : 'category');
});
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
    <div class="ads-hero" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
        <div>
            <h1 class="title" style="margin-bottom: 0.2rem;"><i class="bi bi-megaphone text-primary me-1"></i> Iklan Shopee</h1>
            <div class="sub" style="font-size: .8rem; margin: 0;">Pantau efisiensi, biaya, dan margin profit kampanye.</div>
            <div id="globalSyncStatus" data-last-sync="{{ $lastSyncTime ?? '' }}" style="display:flex;align-items:center;gap:.35rem;flex-wrap:wrap;margin-top:.35rem;font-size:.68rem;color:var(--dsh-muted);">
                <span class="global-sync-dot" style="width:7px;height:7px;border-radius:50%;background:#16a34a;display:inline-block;"></span>
                <span class="global-sync-label">Sync terakhir: {{ ($lastSyncTime ?? '') ?: 'Belum pernah' }}</span>
                <span style="opacity:.65;">· otomatis tiap jam</span>
            </div>
        </div>
        
        <form method="GET" action="{{ route('marketplace.ads.dashboard') }}" id="filterForm" style="display:flex; gap:.6rem; align-items:center; flex-wrap:wrap; margin:0;">
            <div>
                <select name="store_id" onchange="submitAdsFilters(this.form)" class="form-select" style="border-radius:10px; font-size:.78rem; padding:.4rem 2rem .4rem .85rem; border:1px solid rgba(148,163,184,.4); font-weight:650; cursor:pointer; min-width: 140px;">
                    <option value="all" {{ $storeId == 'all' ? 'selected' : '' }}>&#127970; Semua Toko</option>
                    @foreach($stores as $s)
                        <option value="{{ $s->id }}" {{ $storeId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div style="display:flex; align-items:center; background:rgba(148,163,184,.1); border-radius: 8px; padding: .35rem .7rem; transition: background .2s;" onmouseover="this.style.background='rgba(148,163,184,.18)'" onmouseout="this.style.background='rgba(148,163,184,.1)'">
                <i class="bi bi-calendar2-week" style="color:var(--dsh-muted); font-size:.75rem; margin-right: .4rem;"></i>
                <input type="text" id="rangePicker" placeholder="Pilih tanggal" readonly style="width:150px; border:none; background:transparent; font-size:.75rem; padding:0; font-weight:700; color:var(--text); cursor:pointer; box-shadow:none; outline:none;">
                <input type="hidden" name="date_from" id="fromHidden" value="{{ $dateFrom }}" data-gf-date="off">
                <input type="hidden" name="date_to" id="toHidden" value="{{ $dateTo }}" data-gf-date="off">
                <input type="hidden" name="compare_mode" id="compareModeHidden" value="{{ $compareMode ?? 'prev_period' }}">
            </div>

        </form>
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

    @php
        // ================= Target Profit & ROAS (per toko) =================
        // net & COGS ratio dari KPI agregat (data yang sudah dihitung service).
        $adsCurGmv  = (float) data_get($kpi ?? [], 'current.gmv', 0);
        $adsCurNet  = (float) data_get($kpi ?? [], 'current.net_revenue', 0);
        $adsCurCogs = (float) data_get($kpi ?? [], 'current.total_cogs', 0);
        // GMV khusus campaign yang PUNYA HPP — dipakai sebagai pembagi cogs ratio
        // agar rasio HPP tidak "encer" karena ikut terbagi GMV item tanpa HPP.
        $adsProfitGmv = (float) data_get($kpi ?? [], 'current.profit_gmv', 0);
        $adsNetRatio  = $adsCurGmv > 0 ? $adsCurNet / $adsCurGmv : null;
        $adsCogsRatio = $adsProfitGmv > 0
            ? $adsCurCogs / $adsProfitGmv
            : ($adsCurGmv > 0 ? $adsCurCogs / $adsCurGmv : null);

        $targetProfitPct  = $adsSetting->target_profit_pct ?? null;
        $targetRoasMode   = $adsSetting->target_roas_mode ?? 'auto';
        $targetRoasManual = $adsSetting->target_roas ?? null;

        // Auto: target ROAS = 1 / ACOS, ACOS = (netRatio - cogsRatio - profit) / 1.11 (PPN).
        $targetRoasAuto = null;
        if ($targetProfitPct !== null && $adsNetRatio !== null && $adsCogsRatio !== null) {
            $acos = ($adsNetRatio - $adsCogsRatio - ((float) $targetProfitPct / 100)) / 1.11;
            $targetRoasAuto = $acos > 0 ? round(1 / $acos, 2) : null;
        }

        $targetRoasEffective = $targetRoasMode === 'manual'
            ? ($targetRoasManual ?? $targetRoasAuto)
            : ($targetRoasAuto ?? $targetRoasManual);
    @endphp

    @if(!empty($storeId))
        @if($storeId !== 'all' && $targetRoasEffective)
            <div style="display:flex; justify-content:flex-end; gap:.4rem; margin-bottom:.35rem; flex-wrap:wrap;">
                <span title="Target ROAS acuan untuk toko ini (tampil di semua tab)"
                    style="display:inline-flex; align-items:center; gap:.35rem; padding:.28rem .7rem; border-radius:999px;
                           font-size:.72rem; font-weight:700; background:rgba(37,99,235,.1); color:var(--dsh-accent);
                           border:1px solid rgba(37,99,235,.28);">
                    <i class="bi bi-bullseye"></i>
                    Target ROAS {{ number_format($targetRoasEffective, 2, ',', '.') }}x
                    @if($targetProfitPct !== null)
                        <span style="opacity:.75;">· profit {{ rtrim(rtrim(number_format($targetProfitPct, 2, ',', '.'), '0'), ',') }}%</span>
                    @endif
                    <span style="opacity:.6; text-transform:uppercase; font-size:.62rem;">{{ $targetRoasMode }}</span>
                </span>
            </div>
        @endif

        {{-- ==============================================
             TABS (SEGMENTED CONTROL)
        ============================================== --}}
        {{-- Navigasi 2 tingkat: 6 grup induk + sub-tab (dibangun oleh JS dari ADS_GROUPS).
             Semua panel .tab-pane lama dipertahankan; grup hanya mengelompokkan navigasi. --}}
        <div class="ads-tabs-wrap" style="overflow-x: auto; padding-bottom: 0.25rem; scrollbar-width: none;">
            <div class="dash-tabs-modern" id="adsPrimaryTabs">
                <button class="dash-tab-m active" data-group="grp-ringkasan"><i class="bi bi-grid-1x2"></i> Ringkasan</button>
                <button class="dash-tab-m" data-group="grp-performa"><i class="bi bi-bar-chart-steps"></i> Performa</button>
                <button class="dash-tab-m" data-group="grp-profit"><i class="bi bi-cash-coin"></i> Profit</button>
                <button class="dash-tab-m" data-group="grp-funnel"><i class="bi bi-funnel"></i> Funnel &amp; Creative</button>
                <button class="dash-tab-m" data-group="grp-ltv"><i class="bi bi-person-heart"></i> Customer &amp; LTV</button>
                <button class="dash-tab-m" data-group="grp-experiment"><i class="bi bi-bezier2"></i> Experiment</button>
                <button class="dash-tab-m" data-group="grp-control"><i class="bi bi-toggles"></i> Control Panel</button>
                <button class="dash-tab-m" data-group="grp-settings"><i class="bi bi-sliders"></i> Pengaturan <span id="tabSyncBadge" style="display:none; margin-left:2px; min-width:16px; height:16px; padding:0 4px; border-radius:999px; background:#3b82f6; color:#fff; font-size:.6rem; font-weight:800; line-height:16px; text-align:center;"></span></button>
            </div>
        </div>
        <div class="ads-tabs-wrap" id="adsSubTabsWrap" style="overflow-x: auto; padding-top: 0.35rem; padding-bottom: 0.25rem; scrollbar-width: none;">
            <div class="dash-tabs-modern" id="adsSubTabs" style="padding:.2rem;"></div>
        </div>
    @endif

    {{-- Filter has been moved to the ads-hero header above --}}

    @if(empty($storeId))
        <div class="dash-empty">
            <i class="bi bi-shop"></i>
            Pilih toko dulu.
        </div>
    @else
        {{-- Tabs have been moved above the filter --}}

        {{-- ==============================================
             TAB CONTENT
        ============================================== --}}
        
        <!-- DASHBOARD TAB -->

        <!-- ANALYSIS TAB -->
        <div class="tab-pane" id="tab-analysis">
            <div class="ads-tab-panel mb-3">
                <div class="ads-tab-panel-head">
                    <div>
                        <div class="ads-tab-panel-title"><i class="bi bi-clock-history text-primary"></i> Komparasi</div>
                        <div class="ads-tab-panel-note">Perbandingan rentang saat ini vs sebelumnya.</div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                        <label for="compareModeSelect" class="d-flex align-items-center gap-1 mb-0" style="font-size:.66rem; color:var(--dsh-muted); font-weight:700;">
                            <i class="bi bi-arrow-left-right"></i> Bandingkan dengan
                        </label>
                        <div id="compareModeTabs" class="compare-mode-tabs" role="tablist" aria-label="Pilih periode pembanding">
                            <button type="button" class="compare-mode-tab {{ ($compareMode ?? 'prev_period') === 'prev_period' ? 'active' : '' }}" data-compare-mode="prev_period" role="tab" aria-selected="{{ ($compareMode ?? 'prev_period') === 'prev_period' ? 'true' : 'false' }}"><i class="bi bi-calendar2-week me-1"></i>Rentang</button>
                            <button type="button" class="compare-mode-tab {{ ($compareMode ?? 'prev_period') === 'prev_month' ? 'active' : '' }}" data-compare-mode="prev_month" role="tab" aria-selected="{{ ($compareMode ?? 'prev_period') === 'prev_month' ? 'true' : 'false' }}"><i class="bi bi-calendar2-month me-1"></i>Bulan</button>
                            <button type="button" class="compare-mode-tab {{ ($compareMode ?? 'prev_period') === 'prev_year' ? 'active' : '' }}" data-compare-mode="prev_year" role="tab" aria-selected="{{ ($compareMode ?? 'prev_period') === 'prev_year' ? 'true' : 'false' }}"><i class="bi bi-calendar3 me-1"></i>Tahun</button>
                        </div>
                        <select id="compareModeSelect" class="form-select form-select-sm" aria-label="Pilih periode pembanding" style="display:none; width:auto; min-width:190px; font-size:.68rem; border-radius:8px;">
                            <option value="prev_period" {{ ($compareMode ?? 'prev_period') === 'prev_period' ? 'selected' : '' }}>Periode sebelumnya (rentang sama)</option>
                            <option value="prev_month" {{ ($compareMode ?? 'prev_period') === 'prev_month' ? 'selected' : '' }}>Bulan sebelumnya</option>
                            <option value="prev_year" {{ ($compareMode ?? 'prev_period') === 'prev_year' ? 'selected' : '' }}>Tahun sebelumnya</option>
                        </select>
                        <span id="compareModeStatus" aria-live="polite" style="display:none; font-size:.64rem; color:var(--dsh-muted);"><i class="bi bi-arrow-repeat spin-icon"></i> Memuat...</span>
                    <div style="overflow-x:auto; scrollbar-width:none;">
                        <input type="hidden" id="histMetricSelect" value="roas">
                        <div class="dash-tabs-modern" id="histMetricChips" style="padding:.2rem;">
                            <button class="dash-tab-sm active" data-val="roas"><i class="bi bi-lightning-charge"></i> ROAS</button>
                            <button class="dash-tab-sm" data-val="gmv"><i class="bi bi-bag-check"></i> GMV</button>
                            <button class="dash-tab-sm" data-val="aov"><i class="bi bi-cart3"></i> AOV</button>
                            <button class="dash-tab-sm" data-val="spend"><i class="bi bi-wallet2"></i> Biaya</button>
                            <button class="dash-tab-sm" data-val="impressions"><i class="bi bi-eye"></i> Jangkauan</button>
                            <button class="dash-tab-sm" data-val="clicks"><i class="bi bi-cursor"></i> Klik</button>
                            <button class="dash-tab-sm" data-val="ctr"><i class="bi bi-hand-index"></i> CTR</button>
                            <button class="dash-tab-sm" data-val="cvr"><i class="bi bi-funnel"></i> CVR</button>
                            <button class="dash-tab-sm" data-val="profit"><i class="bi bi-cash-stack"></i> Profit setelah admin</button>
                        </div>
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
                            <div>
                                <div style="font-size: .72rem; color: var(--dsh-muted);">Ringkas perubahan antar periode.</div>
                                <div id="histPeriodLabel" style="display:flex; flex-wrap:wrap; align-items:center; gap:.3rem; margin-top:.35rem; font-size:.64rem; color:var(--dsh-muted);">
                                    <span class="badge bg-primary-subtle text-primary border">Aktif: {{ $analysisCurrentRange }}</span>
                                    <span>vs</span>
                                    <span class="badge bg-light text-dark border">Pembanding: {{ $analysisPreviousRange }}</span>
                                    <span>·</span>
                                    <span class="badge bg-light text-dark border">2 lalu: {{ $analysisTwoPeriodsAgoRange }}</span>
                                </div>
                            </div>
                            <div id="histSummary" style="font-size: .78rem; font-weight: 700; color: var(--text); text-align: right;"></div>
                        </div>
                        <div style="position: relative; height: 320px;">
                            <canvas id="historicalChart"></canvas>
                        </div>
                        <div id="historicalSummaryTable" class="table-responsive mt-3"></div>
                    </div>
                </div>
            </div>

            <div class="ads-tab-panel mb-3">
                <div class="ads-tab-panel-head">
                    <div>
                        <div class="ads-tab-panel-title"><i class="bi bi-clock text-primary"></i> Heatmap Jam</div>
                        <div class="ads-tab-panel-note">Peringkat waktu berdasarkan CVR · Gross Profit sebelum HPP.</div>
                    </div>
                </div>
                <div class="p-3">
                    <div id="hourlySummary" class="daily-trend-stats mb-3"></div>
                    <div id="hourlyDataNotice" class="mb-2 p-2" style="display:none; border-left:4px solid var(--dsh-border); background:var(--dsh-bg); border-radius:4px; font-size:.72rem; color:var(--dsh-muted);"></div>
                    <div style="position: relative; height: 250px;">
                        <canvas id="hourlyChart"></canvas>
                    </div>
                    <div id="insightTime" class="mb-3 p-2" style="border-left: 4px solid var(--dsh-border); background: var(--dsh-bg); border-radius: 4px;">
                        <div style="color: var(--dsh-muted); font-size: .75rem;">Menganalisis jam terbaik...</div>
                    </div>
                    <div>
                        <div style="font-size:.72rem; font-weight:700; color:var(--dsh-muted); margin-bottom:.35rem;">Semua jam · peringkat berdasarkan CVR</div>
                        <div id="hourlyBreakdown" class="table-responsive"></div>
                        <div id="hourlyItemDetail" class="mt-3 p-2" style="display:none; border:1px solid var(--dsh-border); border-radius:8px; background:var(--dsh-bg);">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div id="hourlyItemDetailTitle" style="font-size:.7rem; font-weight:800; color:var(--text);"></div>
                                <button type="button" id="hourlyItemDetailClose" class="btn btn-sm" style="font-size:.65rem; padding:.15rem .4rem; color:var(--dsh-muted);">Tutup</button>
                            </div>
                            <div id="hourlyItemDetailContent"></div>
                        </div>
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
                            <div id="dailyDataTable" class="table-responsive mt-3"></div>
                        </div>

                        <div class="dpanel p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <div style="font-weight: 650; font-size: 0.82rem; color: var(--dsh-muted);">Trafik</div>
                                    <div class="mt-1" style="font-size: 0.7rem; color: var(--dsh-muted); opacity: 0.85;">Jangkauan, klik, dan CVR.</div>
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
                            <div id="trafficDataTable" class="table-responsive mt-3"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ads-tab-panel-head mb-3" style="border-bottom:none; background:transparent;">
                <div class="ads-tab-panel-title"><i class="bi bi-bar-chart-line text-success"></i> Top 5 Kampanye / Produk</div>
            </div>
            <div class="dash-panels mt-4 mb-4" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
                {{-- BARIS 1: TRAFFIC --}}
                {{-- CHART IMPRESI --}}
                <div class="dpanel p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="text-muted small fw-bold mt-0 mb-0"><i class="bi bi-eye text-info"></i> Jangkauan</div>
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
        </div>


        <!-- TAB TRAFFIC -->
        <div class="tab-pane" id="tab-traffic">
            <div class="ads-tab-panel mb-4">
                <div class="ads-tab-panel-head">
                    <div>
                        <div class="ads-tab-panel-title"><i class="bi bi-stoplights text-primary"></i> Analisa Traffic</div>
                        <div class="ads-tab-panel-note">Jangkauan → klik → pesanan, plus efisiensi biaya iklan.</div>
                    </div>
                </div>
                
                {{-- KOTAK KPI TRAFFIC --}}
                <div class="ads-kpi-grid p-3">
                    @php
                        // Kalkulasi Traffic KPI
                        $trSpend = ($kpi['current']->spend ?? 0) * 1.11;
                        $trImp = $kpi['current']->impressions ?? 0;
                        $trClicks = $kpi['current']->clicks ?? 0;
                        $trOrders = $kpi['current']->orders ?? 0;
                        $trGmv = $kpi['current']->gmv ?? 0;
                        
                        $trCtr = $trImp > 0 ? ($trClicks / $trImp) * 100 : 0;
                        $trCpc = $trClicks > 0 ? $trSpend / $trClicks : 0;
                        $trCpm = $trImp > 0 ? ($trSpend / $trImp) * 1000 : 0;
                        $trCvr = $trClicks > 0 ? ($trOrders / $trClicks) * 100 : 0;
                        $trCpa = $trOrders > 0 ? $trSpend / $trOrders : 0;
                        $trNetProfit = $kpi['current']->net_profit ?? 0;
                        $trSpendAfterTax = $trSpend;
                        $trPoas = $trSpendAfterTax > 0 ? $trNetProfit / $trSpendAfterTax : 0;
                    @endphp
                    <div class="kpi-card">
                        <div class="kpi-label"><i class="bi bi-cash-coin me-1"></i> Belanja Iklan</div>
                        <div class="kpi-value fw-bold text-dark">Rp {{ number_format($trSpend, 0, ',', '.') }}</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-label"><i class="bi bi-eye me-1"></i> Jangkauan</div>
                        <div class="kpi-value fw-bold text-dark">{{ number_format($trImp, 0, ',', '.') }}</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-label"><i class="bi bi-hand-index-thumb me-1"></i> Klik</div>
                        <div class="kpi-value fw-bold text-dark">{{ number_format($trClicks, 0, ',', '.') }}</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-label"><i class="bi bi-percent me-1"></i> CTR</div>
                        <div class="kpi-value fw-bold text-dark">{{ number_format($trCtr, 2, ',', '.') }}%</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-label"><i class="bi bi-currency-dollar me-1"></i> CPC</div>
                        <div class="kpi-value fw-bold text-dark">Rp {{ number_format($trCpc, 0, ',', '.') }}</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-label"><i class="bi bi-tags me-1"></i> CPM</div>
                        <div class="kpi-value fw-bold text-dark">Rp {{ number_format($trCpm, 0, ',', '.') }}</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-label"><i class="bi bi-bag-check me-1"></i> Gross Sales</div>
                        <div class="kpi-value fw-bold text-dark">Rp {{ number_format($trGmv, 0, ',', '.') }}</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-label"><i class="bi bi-funnel me-1"></i> CVR</div>
                        <div class="kpi-value fw-bold text-dark">{{ number_format($trCvr, 2, ',', '.') }}%</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-label"><i class="bi bi-cash-coin me-1"></i> CPA</div>
                        <div class="kpi-value fw-bold text-dark">Rp {{ number_format($trCpa, 0, ',', '.') }}</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-label" title="Laba bersih setelah iklan ÷ biaya iklan setelah PPN"><i class="bi bi-graph-up-arrow me-1"></i> POAS</div>
                        <div class="kpi-value fw-bold text-dark">{{ number_format($trPoas, 2, ',', '.') }}x</div>
                    </div>
                </div>

                {{-- TREN HARIAN TRAFFIC --}}
                <div class="row g-3 px-3 pb-3">
                    <div class="col-12">
                        <div class="dpanel p-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div>
                                    <div class="fw-bold" style="font-size:.78rem;"><i class="bi bi-calendar3 text-primary me-1"></i> Tren Harian Traffic</div>
                                    <div class="text-muted" style="font-size:.65rem;">Impression, klik, CTR, dan CVR mengikuti periode yang dipilih.</div>
                                </div>
                                <span class="badge bg-light text-dark border" style="font-size:.6rem;">
                                    {{ date('d M Y', strtotime($dateFrom)) }} – {{ date('d M Y', strtotime($dateTo)) }}
                                </span>
                            </div>
                            <div class="row g-3 mt-1">
                                <div class="col-12 col-xl-6">
                                    <div class="small text-muted fw-bold mb-1">Volume Traffic</div>
                                    <div style="position:relative; height:220px; width:100%;">
                                        <canvas id="chartTrafficVolume"></canvas>
                                    </div>
                                </div>
                                <div class="col-12 col-xl-6">
                                    <div class="small text-muted fw-bold mb-1">Kualitas Traffic</div>
                                    <div style="position:relative; height:220px; width:100%;">
                                        <canvas id="chartTrafficRates"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- INSIGHT TRAFFIC --}}

                @php
                    $insightTraffic = [];
                    if ($trImp > 0) {
                        if ($trCtr > 0 && $trCtr < 1.5) {
                            $insightTraffic[] = ['type' => 'warning', 'icon' => 'bi-exclamation-triangle', 'text' => 'Rata-rata CTR keseluruhan cukup rendah (< 1.5%). Evaluasi ulang gambar produk atau relevansi keyword iklan Anda.'];
                        }
                        if ($trCpc > 500) {
                            $insightTraffic[] = ['type' => 'danger', 'icon' => 'bi-exclamation-octagon', 'text' => 'Biaya per Klik (CPC) cukup mahal (> Rp 500). Pertimbangkan untuk menurunkan batas maksimum bid.'];
                        }
                        if(empty($insightTraffic)) {
                            $insightTraffic[] = ['type' => 'success', 'icon' => 'bi-check-circle', 'text' => 'Performa traffic berjalan dengan baik. Metrik CTR dan CPC berada dalam batas wajar.'];
                        }
                    }
                @endphp
                @if(!empty($insightTraffic))
                    <div class="row px-3 pb-3">
                        <div class="col-12">
                            <div class="p-3 rounded" style="background: var(--hero-bg); border: 1px solid var(--dsh-border);">
                                <div class="fw-bold mb-2" style="font-size: 0.85rem;"><i class="bi bi-lightbulb-fill text-warning"></i> Insight Traffic</div>
                                @foreach($insightTraffic as $insight)
                                    <div class="mb-1" style="font-size: 0.78rem;">
                                        <i class="bi {{ $insight['icon'] }} text-{{ $insight['type'] }} me-1"></i> {{ $insight['text'] }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            @php
                $trafficRows = collect($campaigns)->map(function ($row) {
                    $isGms = str_starts_with((string) ($row->channel_campaign_id ?? ''), 'GMS-');
                    $isProblematic = !$isGms && (float) ($row->unit_cogs ?? 0) <= 0;
                    $impressions = (int) ($row->sum_impressions ?? 0);
                    $clicks = (int) ($row->clicks ?? 0);
                    $spend = (float) ($row->spend ?? 0) * 1.11;
                    $orders = (int) ($row->orders ?? 0);
                    $ctr = $impressions > 0 ? ($clicks / $impressions) * 100 : 0;
                    $cvr = $clicks > 0 ? ($orders / $clicks) * 100 : 0;

                    return [
                        'row' => $row,
                        'name' => $row->campaign_name ?: 'Tanpa Nama',
                        'id' => $row->channel_campaign_id,
                        'category' => $isGms ? 'GMV Max Auto' : ($row->item_category ?? $row->internalItem?->category?->name ?? 'Belum termapping'),
                        'is_gms' => $isGms,
                        'views' => $isGms
                            ? ['category', 'gms']
                            : array_values(array_unique(array_merge(['category', 'roas'], $isProblematic ? ['unmapped'] : []))),
                        'spend' => $spend,
                        'impressions' => $impressions,
                        'clicks' => $clicks,
                        'ctr' => $ctr,
                        'orders' => $orders,
                        'gmv' => (float) ($row->gmv ?? 0),
                        'cvr' => $cvr,
                        'aov' => $orders > 0 ? (float) ($row->gmv ?? 0) / $orders : 0,
                        'cpc' => $clicks > 0 ? $spend / $clicks : 0,
                        'cpm' => $impressions > 0 ? ($spend / $impressions) * 1000 : 0,
                    ];
                })->values();
                $trafficCtrMedian = $trafficRows->filter(fn ($row) => $row['impressions'] > 0)->median('ctr') ?? 0;
                $trafficCvrMedian = $trafficRows->filter(fn ($row) => $row['clicks'] > 0)->median('cvr') ?? 0;
                $trafficImpressionsMedian = $trafficRows->filter(fn ($row) => $row['impressions'] > 0)->median('impressions') ?? 0;
                $trafficSignal = function ($row) use ($trafficCtrMedian, $trafficCvrMedian, $trafficImpressionsMedian) {
                    if ($row['impressions'] < max(100, $trafficImpressionsMedian * 0.25)) {
                        return ['label' => 'Data Rendah', 'class' => 'secondary', 'icon' => 'bi-dash-circle'];
                    }

                    $ctrHigh = $trafficCtrMedian > 0 && $row['ctr'] >= $trafficCtrMedian;
                    $cvrHigh = $trafficCvrMedian > 0 && $row['cvr'] >= $trafficCvrMedian;
                    if ($ctrHigh && $cvrHigh) {
                        return $row['impressions'] < $trafficImpressionsMedian
                            ? ['label' => 'Potensi Scale', 'class' => 'info', 'icon' => 'bi-rocket-takeoff']
                            : ['label' => 'Traffic Optimal', 'class' => 'success', 'icon' => 'bi-check-circle'];
                    }
                    if (!$ctrHigh && $cvrHigh) {
                        return ['label' => 'Perbaiki Kreatif', 'class' => 'warning', 'icon' => 'bi-image'];
                    }
                    if ($ctrHigh && !$cvrHigh) {
                        return ['label' => 'Evaluasi Produk', 'class' => 'danger', 'icon' => 'bi-cart-x'];
                    }

                    return ['label' => 'Optimasi Traffic', 'class' => 'danger', 'icon' => 'bi-exclamation-triangle'];
                };
                $trafficRows = $trafficRows->map(function ($row) use ($trafficSignal) {
                    return array_merge($row, ['signal' => $trafficSignal($row)]);
                })->values();
                $trafficCategoryCampaignRows = $trafficRows->groupBy('category');
                $trafficCategoryRows = $trafficCategoryCampaignRows->map(function ($group, $category) {
                    $impressions = $group->sum('impressions');
                    $clicks = $group->sum('clicks');
                    $spend = $group->sum('spend');
                    $orders = $group->sum('orders');
                    $gmv = $group->sum('gmv');
                    return [
                        'category' => $category,
                        'campaigns' => $group->count(),
                        'spend' => $spend,
                        'impressions' => $impressions,
                        'clicks' => $clicks,
                        'ctr' => $impressions > 0 ? ($clicks / $impressions) * 100 : 0,
                        'orders' => $orders,
                        'cvr' => $clicks > 0 ? ($orders / $clicks) * 100 : 0,
                        'gmv' => $gmv,
                        'aov' => $orders > 0 ? $gmv / $orders : 0,
                        'cpc' => $clicks > 0 ? $spend / $clicks : 0,
                        'cpm' => $impressions > 0 ? ($spend / $impressions) * 1000 : 0,
                    ];
                })->map(function ($row) use ($trafficSignal) {
                    return array_merge($row, ['signal' => $trafficSignal($row)]);
                })->sortByDesc('spend')->values();
                $trafficSegmentRows = [
                    'category' => $trafficRows,
                    'roas' => $trafficRows->filter(fn ($row) => in_array('roas', $row['views'], true)),
                    'gms' => $trafficRows->filter(fn ($row) => in_array('gms', $row['views'], true)),
                    'unmapped' => $trafficRows->filter(fn ($row) => in_array('unmapped', $row['views'], true)),
                ];
                $trafficSegmentKpis = collect($trafficSegmentRows)->map(function ($rows) {
                    $impressions = $rows->sum('impressions');
                    $clicks = $rows->sum('clicks');
                    $spend = $rows->sum('spend');
                    $orders = $rows->sum('orders');
                    return [
                        'campaigns' => $rows->count(),
                        'spend' => $spend,
                        'impressions' => $impressions,
                        'clicks' => $clicks,
                        'orders' => $orders,
                        'ctr' => $impressions > 0 ? ($clicks / $impressions) * 100 : 0,
                        'cvr' => $clicks > 0 ? ($orders / $clicks) * 100 : 0,
                        'cpc' => $clicks > 0 ? $spend / $clicks : 0,
                    ];
                })->all();
                $trafficInitialKpi = $trafficSegmentKpis['category'];
                $trafficChartSegmentRows = [
                    'category' => $trafficCategoryRows->map(fn ($row) => [
                        'name' => $row['category'],
                        'impressions' => $row['impressions'],
                        'clicks' => $row['clicks'],
                        'orders' => $row['orders'],
                        'gmv' => $row['gmv'],
                        'ctr' => $row['ctr'],
                        'cvr' => $row['cvr'],
                        'aov' => $row['aov'],
                        'spend' => $row['spend'],
                        'cpc' => $row['cpc'],
                        'cpm' => $row['cpm'],
                    ])->values(),
                    'roas' => $trafficSegmentRows['roas'],
                    'gms' => $trafficSegmentRows['gms'],
                    'unmapped' => $trafficSegmentRows['unmapped'],
                ];
                $trafficChartData = collect($trafficChartSegmentRows)->map(function ($rows) {
                    return collect($rows)->map(fn ($row) => [
                        'name' => $row['name'] ?? $row['category'] ?? 'Tanpa Nama',
                        'impressions' => (int) ($row['impressions'] ?? 0),
                        'clicks' => (int) ($row['clicks'] ?? 0),
                        'orders' => (int) ($row['orders'] ?? 0),
                        'gmv' => (float) ($row['gmv'] ?? 0),
                        'ctr' => (float) ($row['ctr'] ?? 0),
                        'cvr' => (float) ($row['cvr'] ?? 0),
                        'aov' => (float) ($row['aov'] ?? 0),
                        'spend' => (float) ($row['spend'] ?? 0),
                        'cpc' => (float) ($row['cpc'] ?? 0),
                        'cpm' => (float) ($row['cpm'] ?? 0),
                    ])->values();
                })->all();
            @endphp
            <script>window.__trafficKpiData = {!! json_encode($trafficSegmentKpis) !!};</script>
            <script>window.__trafficChartData = {!! json_encode($trafficChartData) !!};</script>
            <style>
                .traffic-category-row { cursor:pointer; }
                .traffic-category-row:hover { background:rgba(37,99,235,.04); }
                .traffic-caret { transition:transform .15s; }
                .traffic-category-row.open .traffic-caret { transform:rotate(90deg); }
                .traffic-funnel { display:flex; flex-direction:column; gap:.45rem; align-items:center; padding:.4rem .25rem .2rem; }
                .traffic-funnel-stage { width:100%; min-height:42px; border-radius:8px; display:flex; align-items:center; justify-content:space-between; gap:.5rem; padding:.45rem .7rem; color:#fff; transition:width .2s ease; }
                .traffic-funnel-stage:nth-child(1) { background:#2563eb; }
                .traffic-funnel-stage:nth-child(2) { background:#0ea5e9; }
                .traffic-funnel-stage:nth-child(3) { background:#10b981; }
                .traffic-funnel-label { font-size:.68rem; font-weight:700; }
                .traffic-funnel-value { font-size:.78rem; font-weight:800; }
                .traffic-funnel-rate { font-size:.6rem; opacity:.9; white-space:nowrap; }
                .traffic-funnel-arrow { color:var(--dsh-muted); font-size:.65rem; line-height:.5; }
            </style>

            {{-- TABEL TRAFFIC --}}
            <div class="ads-tab-panel mb-4">
                <div class="ads-tab-panel-head">
                    <div>
                        <div class="ads-tab-panel-title"><i class="bi bi-list-columns-reverse text-primary"></i> Performa Campaign (Traffic)</div>
                        <div class="ads-tab-panel-note">Jangkauan, klik, pesanan, konversi, dan efisiensi biaya per campaign.</div>
                    </div>
                </div>
                <div style="display:flex; gap:.35rem; margin:.75rem .75rem .75rem; overflow-x:auto;" role="tablist" aria-label="Jenis tampilan traffic">
                    <button type="button" id="btnTrafficCategory" class="btn fw-bold" onclick="__trafficPerformanceView('category')" aria-selected="false" style="border-radius:999px; font-size:.72rem; padding:.38rem .95rem; color:var(--dsh-muted); border:1px solid var(--dsh-border); background:transparent; white-space:nowrap;">Per Kategori</button>
                    <button type="button" id="btnTrafficRoas" class="btn fw-bold" onclick="__trafficPerformanceView('roas')" aria-selected="false" style="border-radius:999px; font-size:.72rem; padding:.38rem .95rem; color:var(--dsh-muted); border:1px solid var(--dsh-border); background:transparent; white-space:nowrap;">GMV Max ROAS</button>
                    <button type="button" id="btnTrafficGms" class="btn fw-bold" onclick="__trafficPerformanceView('gms')" aria-selected="false" style="border-radius:999px; font-size:.72rem; padding:.38rem .95rem; color:var(--dsh-muted); border:1px solid var(--dsh-border); background:transparent; white-space:nowrap;">GMV Max Auto</button>
                    <button type="button" id="btnTrafficUnmapped" class="btn fw-bold" onclick="__trafficPerformanceView('unmapped')" aria-selected="false" style="border-radius:999px; font-size:.72rem; padding:.38rem .95rem; color:var(--dsh-muted); border:1px solid var(--dsh-border); background:transparent; white-space:nowrap;">Produk Bermasalah</button>
                </div>
                <div class="px-3 pb-2">
                    <div class="ads-kpi-grid mb-2">
                        <div class="dpanel ads-kpi kpi-revenue">
                            <div class="ads-kpi-label"><i class="bi bi-list-nested"></i> Campaign</div>
                            <div class="ads-kpi-value" data-traffic-kpi-value="campaigns">{{ number_format($trafficInitialKpi['campaigns'], 0, ',', '.') }}</div>
                            <div class="ads-kpi-sub">sesuai subtab aktif</div>
                        </div>
                        <div class="dpanel ads-kpi kpi-spend">
                            <div class="ads-kpi-label"><i class="bi bi-wallet2"></i> Belanja Iklan</div>
                            <div class="ads-kpi-value" data-traffic-kpi-value="spend">Rp {{ number_format($trafficInitialKpi['spend'], 0, ',', '.') }}</div>
                            <div class="ads-kpi-sub">CPC <span data-traffic-kpi-value="cpc">Rp {{ number_format($trafficInitialKpi['cpc'], 0, ',', '.') }}</span></div>
                        </div>
                        <div class="dpanel ads-kpi">
                            <div class="ads-kpi-label"><i class="bi bi-eye"></i> Jangkauan</div>
                            <div class="ads-kpi-value" data-traffic-kpi-value="impressions">{{ number_format($trafficInitialKpi['impressions'], 0, ',', '.') }}</div>
                            <div class="ads-kpi-sub"><span data-traffic-kpi-value="clicks">{{ number_format($trafficInitialKpi['clicks'], 0, ',', '.') }}</span> klik</div>
                        </div>
                        <div class="dpanel ads-kpi">
                            <div class="ads-kpi-label"><i class="bi bi-percent"></i> CTR</div>
                            <div class="ads-kpi-value" data-traffic-kpi-value="ctr">{{ number_format($trafficInitialKpi['ctr'], 2, ',', '.') }}%</div>
                            <div class="ads-kpi-sub">klik dibanding jangkauan</div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 px-3 pb-3" id="trafficVisuals">
                    <div class="col-12 col-xl-5">
                        <div class="p-3 border rounded h-100" style="background:var(--card-bg);">
                            <div class="fw-bold mb-1" style="font-size:.75rem;">Funnel Traffic</div>
                            <div class="small text-muted mb-2" style="font-size:.65rem;">Impression → klik → order</div>
                            <div class="traffic-funnel">
                                <div class="traffic-funnel-stage" data-traffic-funnel-stage="impressions">
                                    <span class="traffic-funnel-label">Jangkauan</span>
                                    <span class="text-end"><span class="traffic-funnel-value" data-traffic-funnel-value="impressions">{{ number_format($trafficInitialKpi['impressions'], 0, ',', '.') }}</span><br><span class="traffic-funnel-rate">100%</span></span>
                                </div>
                                <div class="traffic-funnel-arrow">▼</div>
                                <div class="traffic-funnel-stage" data-traffic-funnel-stage="clicks">
                                    <span class="traffic-funnel-label">Klik</span>
                                    <span class="text-end"><span class="traffic-funnel-value" data-traffic-funnel-value="clicks">{{ number_format($trafficInitialKpi['clicks'], 0, ',', '.') }}</span><br><span class="traffic-funnel-rate" data-traffic-funnel-rate="ctr">CTR {{ number_format($trafficInitialKpi['ctr'], 2, ',', '.') }}%</span></span>
                                </div>
                                <div class="traffic-funnel-arrow">▼</div>
                                <div class="traffic-funnel-stage" data-traffic-funnel-stage="orders">
                                    <span class="traffic-funnel-label">Orders</span>
                                    <span class="text-end"><span class="traffic-funnel-value" data-traffic-funnel-value="orders">{{ number_format($trafficInitialKpi['orders'], 0, ',', '.') }}</span><br><span class="traffic-funnel-rate" data-traffic-funnel-rate="cvr">CVR {{ number_format($trafficInitialKpi['cvr'], 2, ',', '.') }}%</span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-7">
                        <div class="p-3 border rounded h-100" style="background:var(--card-bg);">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div class="fw-bold" style="font-size:.75rem;">Kualitas Traffic: CTR vs CVR</div>
                                <span id="trafficScatterHint" class="badge bg-light text-dark border" style="font-size:.58rem;">Median subtab</span>
                            </div>
                            <div class="small text-muted mb-2" style="font-size:.65rem;">Bubble lebih besar = jangkauan lebih besar.</div>
                            <div style="position:relative; height:230px; width:100%;"><canvas id="trafficQualityChart"></canvas></div>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="dpanel-table" id="trafficTable">
                        <thead style="position: sticky; top: 0; z-index: 10;">
                            <tr>
                                <th onclick="sortTrafficTable('campaign_name')" style="cursor:pointer">Kategori / Campaign <i class="bi bi-arrow-down-up" style="font-size: 0.6rem; opacity: 0.5;"></i></th>
                                <th>Sinyal</th>
                                <th class="text-end" onclick="sortTrafficTable('impressions')" style="cursor:pointer">Jangkauan <i class="bi bi-arrow-down-up" style="font-size: 0.6rem; opacity: 0.5;"></i></th>
                                <th class="text-end" onclick="sortTrafficTable('clicks')" style="cursor:pointer">Clicks <i class="bi bi-arrow-down-up" style="font-size: 0.6rem; opacity: 0.5;"></i></th>
                                <th class="text-end" onclick="sortTrafficTable('ctr')" style="cursor:pointer">CTR <i class="bi bi-arrow-down-up" style="font-size: 0.6rem; opacity: 0.5;"></i></th>
                                <th class="text-end" onclick="sortTrafficTable('orders')" style="cursor:pointer">Orders <i class="bi bi-arrow-down-up" style="font-size: 0.6rem; opacity: 0.5;"></i></th>
                                <th class="text-end" onclick="sortTrafficTable('cvr')" style="cursor:pointer">CVR <i class="bi bi-arrow-down-up" style="font-size: 0.6rem; opacity: 0.5;"></i></th>
                                <th class="text-end" onclick="sortTrafficTable('spend')" style="cursor:pointer">Biaya Iklan <i class="bi bi-arrow-down-up" style="font-size: 0.6rem; opacity: 0.5;"></i></th>
                                <th class="text-end" onclick="sortTrafficTable('cpc')" style="cursor:pointer">CPC <i class="bi bi-arrow-down-up" style="font-size: 0.6rem; opacity: 0.5;"></i></th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($trafficRows->isEmpty())
                                <tr class="traffic-no-data">
                                    <td colspan="9" class="text-center text-muted py-4">Belum ada data traffic di periode ini.</td>
                                </tr>
                            @else
                                @foreach($trafficCategoryRows as $categoryRow)
                                    <tr class="traffic-category-row" data-traffic-views="category" onclick="trafficToggleCategory(this)">
                                        <td>
                                            <div class="d-flex align-items-center gap-1"><i class="bi bi-chevron-right traffic-caret text-muted" style="font-size:.7rem;"></i><div class="fw-bold" style="font-size: 0.78rem;">{{ $categoryRow['category'] }}</div></div>
                                            <div class="text-muted" style="font-size: 0.65rem;">{{ number_format($categoryRow['campaigns'], 0, ',', '.') }} campaign</div>
                                        </td>
                                        <td><span class="badge bg-{{ $categoryRow['signal']['class'] }}" style="font-size:.6rem;"><i class="bi {{ $categoryRow['signal']['icon'] }} me-1"></i>{{ $categoryRow['signal']['label'] }}</span></td>
                                        <td class="text-end">{{ number_format($categoryRow['impressions'], 0, ',', '.') }}</td>
                                        <td class="text-end">{{ number_format($categoryRow['clicks'], 0, ',', '.') }}</td>
                                        <td class="text-end">{{ number_format($categoryRow['ctr'], 2, ',', '.') }}%</td>
                                        <td class="text-end">{{ number_format($categoryRow['orders'], 0, ',', '.') }}</td>
                                        <td class="text-end">{{ number_format($categoryRow['cvr'], 2, ',', '.') }}%</td>
                                        <td class="text-end">Rp {{ number_format($categoryRow['spend'], 0, ',', '.') }}</td>
                                        <td class="text-end">Rp {{ number_format($categoryRow['cpc'], 0, ',', '.') }}</td>
                                    </tr>
                                    <tr class="traffic-category-detail" style="display:none;">
                                        <td colspan="9" class="bg-light">
                                            <div class="text-muted fw-bold mb-1" style="font-size:.66rem;">Daftar campaign dalam kategori {{ $categoryRow['category'] }}</div>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-hover align-middle mb-0" style="font-size:.72rem;">
                                                    <thead>
                                                        <tr><th>Campaign</th><th>Sinyal</th><th class="text-end">Jangkauan</th><th class="text-end">Klik</th><th class="text-end">CTR</th><th class="text-end">Orders</th><th class="text-end">CVR</th><th class="text-end">Biaya</th><th class="text-end">CPC</th><th class="text-end">CPM</th></tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($trafficCategoryCampaignRows->get($categoryRow['category'], collect()) as $campaignRow)
                                                            <tr>
                                                                <td><div class="fw-bold text-truncate" style="max-width:260px;" title="{{ $campaignRow['name'] }}">{{ $campaignRow['name'] }}</div><div class="text-muted" style="font-size:.62rem;">ID: {{ $campaignRow['id'] }}</div></td>
                                                                <td><span class="badge bg-{{ $campaignRow['signal']['class'] }}" style="font-size:.58rem;">{{ $campaignRow['signal']['label'] }}</span></td>
                                                                <td class="text-end">{{ number_format($campaignRow['impressions'], 0, ',', '.') }}</td>
                                                                <td class="text-end">{{ number_format($campaignRow['clicks'], 0, ',', '.') }}</td>
                                                                <td class="text-end">{{ number_format($campaignRow['ctr'], 2, ',', '.') }}%</td>
                                                                <td class="text-end">{{ number_format($campaignRow['orders'], 0, ',', '.') }}</td>
                                                                <td class="text-end">{{ number_format($campaignRow['cvr'], 2, ',', '.') }}%</td>
                                                                <td class="text-end">Rp {{ number_format($campaignRow['spend'], 0, ',', '.') }}</td>
                                                                <td class="text-end">Rp {{ number_format($campaignRow['cpc'], 0, ',', '.') }}</td>
                                                                <td class="text-end">Rp {{ number_format($campaignRow['cpm'], 0, ',', '.') }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                @foreach($trafficRows as $trafficRow)
                                    <tr class="traffic-performance-row" data-traffic-views="{{ implode(' ', $trafficRow['views']) }}" data-campaign_name="{{ strtolower($trafficRow['name']) }}"
                                        data-spend="{{ $trafficRow['spend'] }}"
                                        data-impressions="{{ $trafficRow['impressions'] }}"
                                        data-clicks="{{ $trafficRow['clicks'] }}"
                                        data-ctr="{{ $trafficRow['ctr'] }}"
                                        data-orders="{{ $trafficRow['orders'] }}"
                                        data-cvr="{{ $trafficRow['cvr'] }}"
                                        data-cpc="{{ $trafficRow['cpc'] }}"
                                        data-cpm="{{ $trafficRow['cpm'] }}">
                                        <td><div class="fw-bold" style="font-size: 0.78rem;">{{ $trafficRow['name'] }}</div><div class="text-muted" style="font-size: 0.65rem;">ID: {{ $trafficRow['id'] }}</div></td>
                                        <td><span class="badge bg-{{ $trafficRow['signal']['class'] }}" style="font-size:.6rem;"><i class="bi {{ $trafficRow['signal']['icon'] }} me-1"></i>{{ $trafficRow['signal']['label'] }}</span></td>
                                        <td class="text-end">{{ number_format($trafficRow['impressions'], 0, ',', '.') }}</td>
                                        <td class="text-end">{{ number_format($trafficRow['clicks'], 0, ',', '.') }}</td>
                                        <td class="text-end">{{ number_format($trafficRow['ctr'], 2, ',', '.') }}%</td>
                                        <td class="text-end">{{ number_format($trafficRow['orders'], 0, ',', '.') }}</td>
                                        <td class="text-end">{{ number_format($trafficRow['cvr'], 2, ',', '.') }}%</td>
                                        <td class="text-end">Rp {{ number_format($trafficRow['spend'], 0, ',', '.') }}</td>
                                        <td class="text-end">Rp {{ number_format($trafficRow['cpc'], 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            @endif
                            <tr class="traffic-view-empty" style="display:none;">
                                <td colspan="9" class="text-center text-muted py-4">Belum ada data untuk kategori ini.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB PROFITABILITAS -->
        <div class="tab-pane" id="tab-profit">
            <div class="dash-sec"><i class="bi bi-cash-coin"></i> Profitabilitas</div>
            @include('marketplace.partials._profitability_tab')
        </div>

        <!-- TAB CAMPAIGN PERFORMANCE -->
        <div class="tab-pane" id="tab-campaign-performance">
            @include('marketplace.partials._campaign_performance_tab')
        </div>

        <!-- (Tab Sync has been merged into Tab Pengaturan) -->

        <!-- TAB ADS EXPERIMENT -->
        <div class="tab-pane" id="tab-experiments">
            <div class="ads-tab-panel mb-3">
                <div class="ads-tab-panel-head">
                    <div>
                        <div class="ads-tab-panel-title"><i class="bi bi-bezier2 text-primary"></i> Ads Experiment &amp; Impact</div>
                        <div class="ads-tab-panel-note">Bandingkan 7 hari sebelum dan sesudah perubahan harga atau target ROAS.</div>
                    </div>
                    <button type="button" id="btnReloadExperiments" class="btn btn-sm" style="border:1px solid var(--dsh-border); color:var(--text); background:var(--card-bg); border-radius:9px; font-size:.74rem; font-weight:700;">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                </div>
                <div class="p-3">
                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-12 col-md-3">
                            <label for="experimentChangeFilter" class="form-label mb-1" style="font-size:.7rem; font-weight:800; color:var(--dsh-muted);">Perubahan</label>
                            <select id="experimentChangeFilter" class="form-select form-select-sm" style="border-radius:9px; font-size:.76rem;">
                                <option value="">Semua perubahan</option>
                                <option value="price">Harga</option>
                                <option value="target_roas">Target ROAS</option>
                                <option value="price_and_target_roas">Harga + Target ROAS</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="experimentStatusFilter" class="form-label mb-1" style="font-size:.7rem; font-weight:800; color:var(--dsh-muted);">Status</label>
                            <select id="experimentStatusFilter" class="form-select form-select-sm" style="border-radius:9px; font-size:.76rem;">
                                <option value="">Semua status</option>
                                <option value="LEARNING">Learning</option>
                                <option value="EARLY_SIGNAL">Early Signal</option>
                                <option value="READY_TO_EVALUATE">Ready to Evaluate</option>
                                <option value="INSUFFICIENT_DATA">Insufficient Data</option>
                                <option value="CONFOUNDED">Confounded</option>
                                <option value="COMPLETED">Completed</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <div id="experimentDataNote" style="font-size:.74rem; color:var(--dsh-muted); line-height:1.45;">
                                Data performa dibaca langsung dari fact table iklan. Hari perubahan tidak dihitung.
                            </div>
                        </div>
                    </div>

                    <div id="experimentsLoading" style="display:none; padding:2.4rem 1rem; text-align:center; color:var(--dsh-muted);">
                        <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
                        <div style="font-size:.78rem;">Memuat experiment...</div>
                    </div>

                    <div id="experimentsEmpty" style="display:none; padding:2.6rem 1rem; text-align:center; border:1px dashed var(--dsh-border); border-radius:14px; color:var(--dsh-muted);">
                        <i class="bi bi-bezier2" style="font-size:2rem; color:#93c5fd;"></i>
                        <div style="font-weight:800; color:var(--text); margin-top:.55rem;">Belum ada experiment</div>
                        <div style="font-size:.75rem; max-width:34rem; margin:.35rem auto 0; line-height:1.55;">
                            Experiment akan tercatat setelah perubahan harga atau target ROAS dilakukan melalui Ads Dashboard.
                            History lama belum di-backfill karena timestamp perubahan belum tersedia.
                        </div>
                    </div>

                    <div id="experimentsError" style="display:none; padding:.8rem 1rem; border-radius:10px; border:1px solid rgba(220,38,38,.25); background:rgba(220,38,38,.06); color:#b91c1c; font-size:.76rem; font-weight:700;"></div>

                    <div id="experimentsTableWrap" class="table-responsive" style="display:none;">
                        <table class="dpanel-table align-middle mb-0" style="min-width:860px;">
                            <thead>
                                <tr>
                                    <th>Perubahan</th>
                                    <th>Scope</th>
                                    <th>Waktu</th>
                                    <th>Status</th>
                                    <th>Baseline</th>
                                    <th>Observation</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="experimentsTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="experimentDetailPanel" class="ads-tab-panel mb-3" style="display:none;">
                <div class="ads-tab-panel-head">
                    <div>
                        <div class="ads-tab-panel-title"><i class="bi bi-layout-split text-primary"></i> Detail Experiment</div>
                        <div class="ads-tab-panel-note" id="experimentDetailSubtitle">Baseline vs observation.</div>
                    </div>
                    <button type="button" id="btnCloseExperimentDetail" class="btn btn-sm" style="border:1px solid var(--dsh-border); color:var(--text); background:var(--card-bg); border-radius:9px; font-size:.74rem; font-weight:700;">
                        Tutup
                    </button>
                </div>
                <div class="p-3">
                    <div id="experimentDetailMeta" class="row g-2 mb-3"></div>
                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <div style="border:1px solid var(--dsh-border); border-radius:12px; overflow:hidden;">
                                <div style="padding:.7rem .85rem; background:rgba(148,163,184,.08); font-size:.75rem; font-weight:850;">Baseline</div>
                                <div id="experimentBaselineMetrics" class="p-2"></div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div style="border:1px solid var(--dsh-border); border-radius:12px; overflow:hidden;">
                                <div style="padding:.7rem .85rem; background:rgba(37,99,235,.08); font-size:.75rem; font-weight:850;">Observation</div>
                                <div id="experimentObservationMetrics" class="p-2"></div>
                            </div>
                        </div>
                    </div>
                    <div id="experimentQualityNote" class="mt-3" style="font-size:.74rem; line-height:1.55;"></div>

                    <div id="experimentImpactPanel" class="ads-phase3-impact" style="display:none;">
                        <div class="ads-phase3-impact-head">
                            <div>
                                <div class="ads-phase3-impact-title"><i class="bi bi-signpost-split text-success"></i> Impact &amp; Verdict</div>
                                <div style="font-size:.66rem; color:var(--dsh-muted); margin-top:.15rem;">Traffic → CTR → CVR → Qty → ROAS → Profit</div>
                            </div>
                            <span id="experimentVerdictBadge" class="ads-tab-panel-note">INCONCLUSIVE</span>
                        </div>
                        <div id="experimentImpactGrid" class="ads-phase3-impact-grid"></div>
                        <div id="experimentSufficiencyNote" class="ads-phase3-note"></div>
                        <div id="experimentConflictNote" class="ads-phase3-note" style="display:none; border-color:rgba(220,38,38,.25); color:#b91c1c;"></div>
                    </div>

                    <div id="experimentSimulationPanel" class="ads-phase2-simulation" style="display:none;">
                        <div class="ads-phase2-simulation-head">
                            <div>
                                <div class="ads-phase2-simulation-title"><i class="bi bi-sliders2-vertical text-primary"></i> Simulasi Phase 2</div>
                                <div style="font-size:.66rem; color:var(--dsh-muted); margin-top:.15rem;">Hitung dampak harga dan target ROAS tanpa menyimpan perubahan.</div>
                            </div>
                            <span class="ads-tab-panel-note">Read-only</span>
                        </div>
                        <div class="row g-2 align-items-end">
                            <div class="col-6 col-md-3">
                                <label for="experimentSimulationPeriod" class="form-label">Basis data</label>
                                <select id="experimentSimulationPeriod" class="form-select form-select-sm">
                                    <option value="observation">Observation</option>
                                    <option value="baseline">Baseline</option>
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <label for="experimentSimulationPrice" class="form-label">Harga simulasi</label>
                                <input id="experimentSimulationPrice" class="form-control form-control-sm" inputmode="decimal" placeholder="Harga">
                            </div>
                            <div class="col-6 col-md-3">
                                <label for="experimentSimulationRoas" class="form-label">Target ROAS</label>
                                <input id="experimentSimulationRoas" class="form-control form-control-sm" inputmode="decimal" placeholder="ROAS x">
                            </div>
                            <div class="col-6 col-md-3">
                                <button type="button" id="btnRunExperimentSimulation" class="btn btn-primary btn-sm w-100" style="min-height:35px; border-radius:9px; font-size:.72rem; font-weight:850;">
                                    <i class="bi bi-calculator"></i> Hitung simulasi
                                </button>
                            </div>
                        </div>
                        <div id="experimentSimulationError" style="display:none; margin-top:.6rem; color:#b91c1c; font-size:.7rem; font-weight:750;"></div>
                        <div id="experimentSimulationLoading" style="display:none; margin-top:.65rem; color:var(--dsh-muted); font-size:.7rem;">
                            <span class="spinner-border spinner-border-sm me-1" role="status"></span> Menghitung...
                        </div>
                        <div id="experimentSimulationResult" style="display:none;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB CONTROL PANEL -->
        <div class="tab-pane" id="tab-control">
            <style>
                .ads-control-list { display:flex; flex-direction:column; gap:.5rem; overflow-x:auto; padding-bottom:.35rem; }
                .ads-control-row, .ads-control-groupbar { --ads-control-grid:minmax(310px,1.55fr) minmax(78px,.45fr) repeat(4,minmax(72px,.55fr)) repeat(6,minmax(88px,.72fr)); }
                .ads-control-row { display:grid; grid-template-columns:var(--ads-control-grid); align-items:center; gap:.65rem; min-width:1240px; padding:.65rem .75rem; border:1px solid var(--dsh-border); border-radius:12px; background:var(--card-bg); }
                .ads-control-row:hover { border-color:rgba(37,99,235,.35); box-shadow:0 4px 12px rgba(15,23,42,.05); }
                .ads-control-groupbar { display:grid; grid-template-columns:var(--ads-control-grid); min-width:1240px; gap:.65rem; padding:0 .75rem .25rem; color:var(--dsh-muted); font-size:.56rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; }
                .ads-control-groupbar span { padding:.2rem .35rem; border-radius:6px; }
                .ads-control-groupbar .ads-control-group-funnel { grid-column:3 / 7; background:rgba(37,99,235,.06); color:#2563eb; text-align:center; }
                .ads-control-groupbar .ads-control-group-result { grid-column:7 / 13; background:rgba(16,185,129,.07); color:#047857; text-align:center; }
                .ads-control-header { border:0; box-shadow:none; background:transparent; padding:.1rem .75rem; color:var(--dsh-muted); font-size:.58rem; font-weight:800; text-transform:uppercase; letter-spacing:.02em; }
                .ads-control-header:hover { border-color:transparent; box-shadow:none; }
                .ads-control-header span { white-space:nowrap; }
                .ads-control-header span:nth-child(n+3):nth-child(-n+6) { color:#2563eb; }
                .ads-control-header span:nth-child(n+7) { color:#047857; }
                .ads-control-label { display:block; font-size:.58rem; color:var(--dsh-muted); margin-bottom:.2rem; }
                .ads-control-input { max-width:125px; min-height:30px; padding:.25rem .45rem; border-radius:8px; font-size:.72rem; font-weight:700; color:var(--text); background:var(--card-bg); border:1px solid var(--dsh-border); }
                .ads-control-input:focus { border-color:var(--dsh-accent); box-shadow:0 0 0 2px rgba(37,99,235,.12); }
                .ads-control-value { min-height:30px; padding:.25rem .55rem; border:1px solid var(--dsh-border); border-radius:8px; color:var(--text); background:var(--card-bg); font-size:.7rem; font-weight:750; white-space:nowrap; }
                .ads-control-value:hover { border-color:var(--dsh-accent); background:rgba(37,99,235,.05); }
                .ads-control-icon { width:30px; height:30px; display:inline-flex; align-items:center; justify-content:center; padding:0; border-radius:8px; }
                .ads-control-actions { display:flex; align-items:center; gap:.3rem; flex-wrap:wrap; }
                .ads-control-metrics { display:flex; align-items:center; gap:.45rem; flex-wrap:wrap; font-size:.63rem; line-height:1.25; }
                .ads-control-metric { white-space:nowrap; color:var(--dsh-muted); }
                .ads-control-metric strong { color:var(--text); font-weight:800; }
                .ads-control-metric .is-negative { color:#dc2626; }
                .ads-control-metric .is-positive { color:#15803d; }
                .ads-control-funnel { display:flex; align-items:center; gap:.4rem; flex-wrap:wrap; font-size:.6rem; color:var(--dsh-muted); line-height:1.25; }
                .ads-control-funnel strong { color:var(--text); font-weight:800; }
                .ads-control-cell { min-width:0; font-size:.65rem; line-height:1.2; }
                .ads-control-cell strong { display:block; color:var(--text); font-size:.69rem; font-weight:800; white-space:nowrap; }
                .ads-control-cell .ads-control-prev { display:block; margin-top:.16rem; color:var(--dsh-muted); font-size:.55rem; white-space:nowrap; }
                .ads-control-cell-funnel { border-left:1px solid rgba(37,99,235,.12); padding-left:.45rem; }
                .ads-control-cell-result { border-left:1px solid rgba(16,185,129,.12); padding-left:.45rem; }
                .ads-control-cell-result.is-negative strong { color:#dc2626; }
                .ads-control-cell-result.is-positive strong { color:#15803d; }
                .ads-control-name-controls { display:flex; align-items:center; gap:.3rem; flex-wrap:wrap; margin-top:.45rem; }
                .ads-control-avatar { width:34px; height:34px; border-radius:9px; object-fit:cover; flex:none; border:1px solid var(--dsh-border); background:#f1f5f9; }
                .ads-control-avatar-empty { width:34px; height:34px; display:inline-flex; align-items:center; justify-content:center; border-radius:9px; flex:none; color:var(--dsh-muted); background:#f1f5f9; border:1px solid var(--dsh-border); }
                @media (max-width: 900px) {
                    .ads-control-row, .ads-control-groupbar { min-width:1120px; }
                }
                @media (max-width: 620px) {
                    .ads-control-row, .ads-control-groupbar { min-width:1060px; }
                }
            </style>
            <div class="ads-tab-panel mb-3">
                <div class="ads-tab-panel-head">
                    <div>
                        <div class="ads-tab-panel-title">
                            <i class="bi bi-toggles text-primary"></i> Kontrol Campaign
                        </div>
                        <div class="ads-tab-panel-note">Atur ROAS, modal, status, atau jadwal dari satu baris.</div>
                    </div>
                    <span class="badge rounded-pill text-bg-light" style="font-size:.65rem;" title="Jumlah campaign yang tersedia untuk dikontrol">{{ $campaigns->count() }} campaign</span>
                </div>
                <div class="p-3">
                    @if($storeId === 'all')
                        <div class="alert alert-warning py-2 px-3 mb-3" style="font-size:.68rem; border-radius:10px;">
                            <i class="bi bi-info-circle me-1"></i> Pastikan toko pada baris campaign sudah benar sebelum menyimpan.
                        </div>
                    @endif
                    @if($campaignSchedules->isNotEmpty())
                        <div class="dpanel mb-3 p-2" style="border:1px solid var(--dsh-border); border-radius:10px;">
                            <div style="font-size:.7rem;font-weight:800;margin-bottom:.35rem;"><i class="bi bi-calendar2-week text-primary me-1"></i> Jadwal aktif</div>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0" style="font-size:.66rem;">
                                    <thead><tr><th>Campaign</th><th>Toko</th><th>Aksi</th><th>Waktu</th><th>Status</th><th></th></tr></thead>
                                    <tbody>
                                        @foreach($campaignSchedules as $schedule)
                                            <tr>
                                                <td class="fw-semibold">{{ data_get($schedule->meta, 'campaign_name') ?: $schedule->channel_campaign_id }}</td>
                                                <td>{{ $schedule->store?->name ?? 'Toko #' . $schedule->store_id }}</td>
                                                <td>
                                                    @if($schedule->action === 'budget')
                                                        @php
                                                            $scheduledBudget = (float) data_get($schedule->meta, 'daily_budget', 0);
                                                        @endphp
                                                        Modal {{ $scheduledBudget > 0 ? number_format($scheduledBudget, 0, ',', '.') : 'Unlimited' }}
                                                    @else
                                                        {{ $schedule->action === 'pause' ? 'Jeda' : 'Lanjut' }}
                                                    @endif
                                                </td>
                                                <td>{{ $schedule->scheduled_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                                                <td>
                                                    <span class="badge rounded-pill" style="font-size:.58rem;background:{{ $schedule->status === 'failed' ? '#fee2e2' : '#eff6ff' }};color:{{ $schedule->status === 'failed' ? '#b91c1c' : '#1d4ed8' }};">
                                                        {{ match($schedule->status) { 'pending' => 'Menunggu', 'queued' => 'Diantrekan', 'running' => 'Diproses', 'failed' => 'Gagal', default => ucfirst($schedule->status) } }}
                                                    </span>
                                                    @if($schedule->status === 'failed' && $schedule->error_message)
                                                        <span title="{{ $schedule->error_message }}" style="cursor:help;color:#b91c1c;"><i class="bi bi-info-circle"></i></span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    @if(in_array($schedule->status, ['pending', 'queued'], true))
                                                        <button type="button" class="btn btn-sm btn-link text-danger p-0" data-schedule-cancel="{{ $schedule->id }}" title="Batalkan jadwal"><i class="bi bi-x-circle"></i></button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                    <div class="ads-control-list">
                        <div class="ads-control-groupbar d-none d-md-grid" aria-hidden="true">
                            <span></span>
                            <span></span>
                            <span class="ads-control-group-funnel">Funnel</span>
                            <span class="ads-control-group-result">Hasil</span>
                        </div>
                        <div class="ads-control-row ads-control-header d-none d-md-grid">
                            <span class="ads-control-label mb-0">Campaign</span>
                            <span class="ads-control-label mb-0">Status</span>
                            <span class="ads-control-label mb-0">Impresi</span>
                            <span class="ads-control-label mb-0">Klik</span>
                            <span class="ads-control-label mb-0">CTR</span>
                            <span class="ads-control-label mb-0">CVR</span>
                            <span class="ads-control-label mb-0">ROAS Aktual</span>
                            <span class="ads-control-label mb-0">Orders</span>
                            <span class="ads-control-label mb-0">Biaya + PPN</span>
                            <span class="ads-control-label mb-0">AOV</span>
                            <span class="ads-control-label mb-0">GMV</span>
                            <span class="ads-control-label mb-0">Profit</span>
                        </div>
                        @forelse($campaigns as $controlCampaign)
                            @php
                                $controlId = (string) ($controlCampaign->channel_campaign_id ?? '');
                                $controlIsGms = str_starts_with($controlId, 'GMS-') || ($controlCampaign->ad_type ?? null) === 'auto';
                                $controlStatus = strtolower((string) ($controlCampaign->campaign_status ?? 'ongoing'));
                                $controlType = $controlIsGms ? 'GMV Max' : 'Regular';
                                $controlSpend = (float) ($controlCampaign->spend ?? $controlCampaign->sum_expense ?? 0) * 1.11;
                                $controlGmv = (float) ($controlCampaign->gmv ?? ($controlCampaign->sum_broad_gmv ?? 0) + ($controlCampaign->sum_direct_gmv ?? 0));
                                $controlOrders = (int) ($controlCampaign->orders ?? ($controlCampaign->sum_broad_orders ?? 0) + ($controlCampaign->sum_direct_orders ?? 0));
                                $controlImpressions = (int) ($controlCampaign->impressions ?? $controlCampaign->sum_impressions ?? 0);
                                $controlClicks = (int) ($controlCampaign->clicks ?? $controlCampaign->sum_clicks ?? 0);
                                $controlCtr = $controlImpressions > 0 ? ($controlClicks / $controlImpressions) * 100 : 0;
                                $controlCvr = $controlClicks > 0 ? ($controlOrders / $controlClicks) * 100 : 0;
                                $controlActualRoas = $controlSpend > 0 ? $controlGmv / $controlSpend : 0;
                                $controlAov = $controlOrders > 0 ? $controlGmv / $controlOrders : 0;
                                $controlProfit = $controlCampaign->profit_after_ads;
                                $controlImage = $controlCampaign->marketplace_item_image_url ?? null;
                            @endphp
                            <div class="ads-control-row" data-control-row
                                data-store-id="{{ $controlCampaign->store_id }}"
                                data-campaign-id="{{ $controlId }}"
                                data-kind="{{ $controlIsGms ? 'gms' : 'cpc' }}"
                                data-status="{{ $controlStatus }}">
                                <div class="ads-control-name">
                                    <div style="display:flex;align-items:center;gap:.5rem;min-width:0;">
                                        @if($controlImage)
                                            <img src="{{ $controlImage }}" alt="" class="ads-control-avatar" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';">
                                            <span class="ads-control-avatar-empty" style="display:none;"><i class="bi bi-image"></i></span>
                                        @else
                                            <span class="ads-control-avatar-empty"><i class="bi bi-image"></i></span>
                                        @endif
                                        <div style="min-width:0;">
                                            <div style="font-weight:800;font-size:.76rem;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ $controlCampaign->campaign_name ?: 'Campaign ' . $controlId }}">{{ $controlCampaign->campaign_name ?: 'Campaign ' . $controlId }}</div>
                                            <div style="font-size:.6rem;color:var(--dsh-muted);margin-top:.2rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $controlCampaign->marketplace_item_name ?: 'Produk campaign' }} · {{ $controlCampaign->store?->name ?? 'Toko #' . $controlCampaign->store_id }} · {{ $controlType }}</div>
                                        </div>
                                    </div>
                                    <div class="ads-control-name-controls" onclick="event.stopPropagation()">
                                        <div class="ads-control-field" data-control-field="roas">
                                            <div data-control-display>
                                                <button type="button" class="btn btn-light btn-sm ads-control-value" data-control-toggle title="Klik untuk edit Target ROAS">
                                                    <i class="bi bi-bullseye text-primary me-1"></i>{{ $controlCampaign->target_roas !== null ? number_format((float) $controlCampaign->target_roas, 2, ',', '') . 'x' : 'Auto' }}
                                                </button>
                                            </div>
                                            <div data-control-editor class="input-group input-group-sm" style="display:none;max-width:150px;">
                                                <input type="text" data-control-roas class="ads-control-input" inputmode="decimal" placeholder="Auto" value="{{ $controlCampaign->target_roas !== null ? number_format((float) $controlCampaign->target_roas, 2, ',', '') : '' }}" title="Isi 0 untuk Auto">
                                                <button type="button" class="btn btn-outline-primary ads-control-icon" data-control-action="roas" title="Simpan target ROAS"><i class="bi bi-check2"></i></button>
                                                <button type="button" class="btn btn-outline-secondary ads-control-icon" data-control-cancel title="Batal"><i class="bi bi-x"></i></button>
                                            </div>
                                        </div>
                                        <div class="ads-control-field" data-control-field="budget">
                                            <div data-control-display>
                                                <button type="button" class="btn btn-light btn-sm ads-control-value" data-control-toggle title="Klik untuk edit Modal Harian">
                                                    <i class="bi bi-wallet2 text-primary me-1"></i>{{ ($controlCampaign->campaign_budget ?? 0) > 0 ? 'Rp ' . number_format((float) $controlCampaign->campaign_budget, 0, ',', '.') : 'Unlimited' }}
                                                </button>
                                            </div>
                                            <div data-control-editor class="input-group input-group-sm" style="display:none;max-width:175px;">
                                                <input type="text" data-control-budget class="ads-control-input" inputmode="numeric" placeholder="Unlimited" value="{{ ($controlCampaign->campaign_budget ?? 0) > 0 ? number_format((float) $controlCampaign->campaign_budget, 0, ',', '.') : '' }}" title="Kosong atau 0 untuk Unlimited">
                                                <button type="button" class="btn btn-outline-primary ads-control-icon" data-control-action="budget" title="Simpan modal harian"><i class="bi bi-check2"></i></button>
                                                <button type="button" class="btn btn-outline-secondary ads-control-icon" data-control-cancel title="Batal"><i class="bi bi-x"></i></button>
                                            </div>
                                        </div>
                                        <div class="ads-control-actions">
                                            @if($controlStatus === 'ongoing')
                                                <button type="button" class="btn btn-sm btn-outline-warning ads-control-icon" data-control-action="pause" title="Jeda sekarang"><i class="bi bi-pause-fill"></i></button>
                                            @elseif($controlStatus === 'paused')
                                                <button type="button" class="btn btn-sm btn-outline-success ads-control-icon" data-control-action="resume" title="Lanjutkan sekarang"><i class="bi bi-play-fill"></i></button>
                                            @endif
                                            @if(!in_array($controlStatus, ['closed', 'ended'], true))
                                                <button type="button" class="btn btn-sm btn-outline-danger ads-control-icon" data-control-action="stop" title="Hentikan hari ini"><i class="bi bi-stop-fill"></i></button>
                                                <button type="button" class="btn btn-sm btn-outline-primary ads-control-icon" data-control-schedule-open title="Buat jadwal"><i class="bi bi-calendar-plus"></i></button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <span data-control-status class="badge rounded-pill" style="font-size:.6rem;background:{{ $controlStatus === 'ongoing' ? '#dcfce7' : ($controlStatus === 'paused' ? '#fef3c7' : '#f1f5f9') }};color:{{ $controlStatus === 'ongoing' ? '#166534' : ($controlStatus === 'paused' ? '#92400e' : '#64748b') }};">
                                        {{ match($controlStatus) { 'ongoing' => 'Aktif', 'paused' => 'Jeda', 'closed', 'ended' => 'Selesai', default => ucfirst($controlStatus ?: '—') } }}
                                    </span>
                                </div>
                                <div class="ads-control-cell ads-control-cell-funnel text-end">
                                    <label class="ads-control-label d-md-none">Impresi</label>
                                    <strong>{{ number_format($controlImpressions, 0, ',', '.') }}</strong>
                                </div>
                                <div class="ads-control-cell ads-control-cell-funnel text-end">
                                    <label class="ads-control-label d-md-none">Klik</label>
                                    <strong>{{ number_format($controlClicks, 0, ',', '.') }}</strong>
                                </div>
                                <div class="ads-control-cell ads-control-cell-funnel text-end">
                                    <label class="ads-control-label d-md-none">CTR</label>
                                    <strong>{{ number_format($controlCtr, 2, ',', '.') }}%</strong>
                                </div>
                                <div class="ads-control-cell ads-control-cell-funnel text-end">
                                    <label class="ads-control-label d-md-none">CVR</label>
                                    <strong>{{ number_format($controlCvr, 2, ',', '.') }}%</strong>
                                </div>
                                <div class="ads-control-cell ads-control-cell-result text-end">
                                    <label class="ads-control-label d-md-none">ROAS Aktual</label>
                                    <strong>{{ number_format($controlActualRoas, 2, ',', '.') }}x</strong>
                                </div>
                                <div class="ads-control-cell ads-control-cell-result text-end">
                                    <label class="ads-control-label d-md-none">Orders</label>
                                    <strong>{{ number_format($controlOrders, 0, ',', '.') }}</strong>
                                </div>
                                <div class="ads-control-cell ads-control-cell-result text-end">
                                    <label class="ads-control-label d-md-none">Biaya + PPN</label>
                                    <strong>Rp {{ number_format($controlSpend, 0, ',', '.') }}</strong>
                                </div>
                                <div class="ads-control-cell ads-control-cell-result text-end">
                                    <label class="ads-control-label d-md-none">AOV</label>
                                    <strong>Rp {{ number_format($controlAov, 0, ',', '.') }}</strong>
                                </div>
                                <div class="ads-control-cell ads-control-cell-result text-end">
                                    <label class="ads-control-label d-md-none">GMV</label>
                                    <strong>Rp {{ number_format($controlGmv, 0, ',', '.') }}</strong>
                                </div>
                                <div class="ads-control-cell ads-control-cell-result text-end {{ $controlProfit !== null && $controlProfit < 0 ? 'is-negative' : 'is-positive' }}">
                                    <label class="ads-control-label d-md-none">Profit</label>
                                    <strong>{{ $controlProfit === null ? 'N/A' : 'Rp ' . number_format((float) $controlProfit, 0, ',', '.') }}</strong>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4" style="font-size:.75rem;color:var(--dsh-muted);">Belum ada campaign yang bisa dikontrol.</div>
                        @endforelse
                    </div>
                    <div style="font-size:.62rem;color:var(--dsh-muted);margin-top:.65rem;">
                        <i class="bi bi-shield-check me-1"></i> Perubahan diproses setelah API Shopee berhasil.
                    </div>
                </div>
            </div>

            <div class="modal fade" id="modalCampaignSchedule" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-sm">
                    <div class="modal-content" style="border-radius:16px;background:var(--card-bg);border:1px solid var(--card-border);">
                        <div class="modal-header border-0 pb-1">
                            <div>
                                <h6 class="modal-title fw-bold mb-1">Jadwalkan aksi</h6>
                                <div id="campaignScheduleName" style="font-size:.66rem;color:var(--dsh-muted);"></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body pt-2">
                            <div class="mb-2">
                                <label class="ads-control-label">Aksi</label>
                                <select id="campaignScheduleAction" class="form-select form-select-sm" style="border-radius:8px;">
                                    <option value="pause">Jeda campaign</option>
                                    <option value="resume">Lanjutkan campaign</option>
                                    <option value="budget">Ubah modal harian</option>
                                </select>
                            </div>
                            <div class="mb-2" id="campaignScheduleBudgetWrap" style="display:none;">
                                <label class="ads-control-label">Modal harian</label>
                                <input id="campaignScheduleBudget" type="text" class="form-control form-control-sm" inputmode="numeric" placeholder="0 = Unlimited" style="border-radius:8px;">
                            </div>
                            <div class="mb-3">
                                <label class="ads-control-label">Waktu eksekusi</label>
                                <input id="campaignScheduleAt" type="datetime-local" min="{{ now()->format('Y-m-d\TH:i') }}" class="form-control form-control-sm" style="border-radius:8px;">
                            </div>
                            <button type="button" id="campaignScheduleSubmit" class="btn btn-primary btn-sm w-100" style="border-radius:9px;font-weight:750;">
                                <i class="bi bi-calendar-check me-1"></i> Simpan jadwal
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB PENGATURAN -->
        <div class="tab-pane" id="tab-settings">
            @php
                $settingsCurrentGmv = (float) data_get($kpi ?? [], 'current.gmv', 0);
                $settingsCurrentNetRevenue = (float) data_get($kpi ?? [], 'current.net_revenue', 0);
                $settingsAutoFee = $settingsCurrentGmv > 0
                    ? max(0, (1 - ($settingsCurrentNetRevenue / $settingsCurrentGmv)) * 100)
                    : 21.9;
            @endphp
            @include('marketplace.partials._ads_fee_setting', ['autoFeeValue' => $settingsAutoFee])
            @include('marketplace.partials._ads_target_setting', [
                'targetStoreId'    => $storeId,
                'targetProfitPct'  => $targetProfitPct,
                'targetRoasMode'   => $targetRoasMode,
                'targetRoasManual' => $targetRoasManual,
                'targetRoasAuto'   => $targetRoasAuto,
            ])
            <div class="ads-tab-panel mb-3">
                <div class="ads-tab-panel-head">
                    <div>
                        <div class="ads-tab-panel-title"><i class="bi bi-sliders text-primary"></i> Pengaturan & Sinkronisasi</div>
                        <div class="ads-tab-panel-note">Kelola pengaturan sinkronisasi dan data.</div>
                    </div>
                </div>
                <div class="p-4" style="display: flex; flex-direction: column; gap: 1rem; align-items: flex-start;">
                    <!-- LIVE SYNC PROGRESS INDICATOR -->
                    <div id="liveSyncProgressContainer" style="display: none; width: 100%; max-width: 640px; background: rgba(37,99,235,0.05); border: 1px solid rgba(37,99,235,0.2); border-radius: 12px; padding: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: .5rem; font-size: .85rem; font-weight: 650; color: var(--dsh-accent);">
                            <span id="liveSyncLabel"><i class="spinner-border spinner-border-sm me-2" role="status"></i> Menyiapkan sinkronisasi...</span>
                            <div style="display:flex; align-items:center; gap:.5rem;">
                                <span id="liveSyncPercent">0%</span>
                                <button id="btnCancelSync" onclick="window.__cancelSync(this)" title="Batalkan sinkronisasi"
                                    style="display:inline-flex; align-items:center; gap:.3rem; padding:.25rem .65rem; font-size:.72rem; font-weight:600;
                                           background:rgba(239,68,68,0.1); color:#dc2626; border:1px solid rgba(239,68,68,0.3);
                                           border-radius:8px; cursor:pointer; transition:all .15s; line-height:1;">
                                    <i class="bi bi-x-circle-fill"></i> Batal
                                </button>
                            </div>
                        </div>
                        <div class="progress" style="height: 10px; border-radius: 10px; background-color: rgba(37,99,235,0.1);">
                            <div id="liveSyncProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%; background-color: var(--dsh-accent);"></div>
                        </div>
                    </div>
                    @if(isset($syncRuns) && $syncRuns->isNotEmpty())
                        @php
                            $latestRun = $syncRuns->first();
                            $lastSuccess = $lastSuccessRun ?? null;
                        @endphp
                        @if(in_array($latestRun->status, ['error', 'partial_success'], true))
                            @php
                                $isPartial = $latestRun->status === 'partial_success';
                            @endphp
                            <div class="ads-hero-meta {{ $isPartial ? '' : 'ads-hero-error' }}" style="background: {{ $isPartial ? 'rgba(234, 179, 8, 0.1)' : 'rgba(239, 68, 68, 0.1)' }}; color: {{ $isPartial ? '#ca8a04' : '#ef4444' }}; padding: 0.5rem 1rem; border-radius: 8px; border: 1px solid {{ $isPartial ? 'rgba(234, 179, 8, 0.3)' : 'rgba(239, 68, 68, 0.3)' }};">
                                <i class="bi {{ $isPartial ? 'bi-exclamation-circle-fill' : 'bi-exclamation-triangle-fill' }}"></i>
                                {{ $isPartial ? 'Sync sebagian' : 'Sync gagal' }}: {{ Str::limit($latestRun->error_message, 60) }}
                            </div>
                        @endif
                        <div class="ads-hero-meta" style="font-size: 0.85rem; font-weight: 600;">
                            @if($lastSuccess)
                                <span style="color: #16a34a;"><i class="bi bi-check-circle-fill"></i></span>
                                Sync terakhir: {{ $lastSuccess->updated_at?->timezone('Asia/Jakarta')?->format('d M Y, H:i') ?? 'waktu tidak tersedia' }}
                            @else
                                <span style="color: #eab308;"><i class="bi bi-clock"></i></span>
                                Belum ada sync
                            @endif
                        </div>
                    @endif

                    <div style="display: flex; gap: .75rem; flex-wrap: wrap;">
                        <button type="button" class="btn fw-bold" data-bs-toggle="modal" data-bs-target="#modalSyncAds" style="background: var(--dsh-accent); color:#fff; border-radius:10px; font-size:.75rem; padding:.45rem 1rem;">
                            <i class="bi bi-arrow-repeat"></i> Sync Manual
                        </button>
                        <button type="button" class="btn fw-bold" onclick="openSyncTab()" style="border: 1px solid var(--dsh-border); color:var(--text); background: var(--card-bg); border-radius:10px; font-size:.75rem; padding:.45rem 1rem;">
                            <i class="bi bi-journal-text"></i> Log Sync
                        </button>
                        <button type="button" class="btn fw-bold" data-bs-toggle="modal" data-bs-target="#modalGmsSettings" style="border: 1px solid var(--dsh-accent); background: rgba(37, 99, 235, 0.05); color: var(--dsh-accent); border-radius:10px; font-size:.75rem; padding:.45rem 1rem;">
                            <i class="bi bi-gear"></i> Pengaturan GMV Max
                        </button>
                        @if(auth()->user()->role === 'owner')
                        <button type="button" class="btn fw-bold" onclick="clearAdsData()" style="border: 1px solid rgba(239, 68, 68, 0.3); background: rgba(239, 68, 68, 0.1); color: #ef4444; border-radius:10px; font-size:.75rem; padding:.45rem 1rem;">
                            <i class="bi bi-trash"></i> Bersihkan Data
                        </button>
                        @endif
                    </div>
                    
                    <div id="syncCountdown" class="role-chip live-btn live-off mt-2" style="font-size: 0.75rem; padding: 0.35rem 0.75rem;">
                        <i class="bi bi-clock-history"></i> Menghitung...
                    </div>
                </div>
            </div>

            <!-- Konten Sync Tab Digabung di sini -->
            @include('marketplace.partials._sync_tab')
        </div>

        <!-- TAB KAMPANYE -->
        <div class="tab-pane active" id="tab-campaigns">
            
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
            
            
            <div class="ads-tab-panel mb-4">
                <div class="ads-tab-panel-head">
                    <div>
                        <div class="ads-tab-panel-title"><i class="bi bi-megaphone text-primary"></i> Performa Kampanye</div>
                        <div class="ads-tab-panel-note">Metrik interaksi dan tayangan iklan secara keseluruhan.</div>
                    </div>
                </div>
                <div class="p-2">
                    <div class="ads-kpi-grid mb-0" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px;">
                @php
                    $comparisonLabel = match ($compareMode ?? 'prev_period') {
                        'prev_month' => 'bulan lalu',
                        'prev_year' => 'tahun lalu',
                        default => 'periode lalu',
                    };

                    // KPI metrics are now fully calculated and aggregated 
                    // within AdsDashboardService and passed precisely via $kpi.

                    $metrics = [
                        ['title' => 'Omzet', 'key' => 'gmv', 'prefix' => 'Rp ', 'suffix' => '', 'cls' => 'revenue', 'icon' => 'bi-wallet2'],
                        ['title' => 'Biaya Iklan', 'key' => 'spend', 'prefix' => 'Rp ', 'suffix' => '', 'cls' => 'spend', 'icon' => 'bi-cash-stack'],
                        ['title' => 'Net Profit', 'key' => 'net_profit', 'prefix' => 'Rp ', 'suffix' => '', 'cls' => 'profit', 'icon' => 'bi-piggy-bank'],
                        ['title' => 'Pesanan', 'key' => 'orders', 'prefix' => '', 'suffix' => '', 'cls' => 'profit', 'icon' => 'bi-box-seam'],
                        ['title' => 'ROAS', 'key' => 'roas', 'prefix' => '', 'suffix' => 'x', 'cls' => 'roas', 'icon' => 'bi-lightning-charge'],
                        ['title' => 'Jangkauan', 'key' => 'impressions', 'prefix' => '', 'suffix' => '', 'cls' => 'revenue', 'icon' => 'bi-eye'],
                        ['title' => 'Klik', 'key' => 'clicks', 'prefix' => '', 'suffix' => '', 'cls' => 'spend', 'icon' => 'bi-cursor'],
                        ['title' => 'CTR', 'key' => 'ctr', 'prefix' => '', 'suffix' => '%', 'cls' => 'roas', 'icon' => 'bi-hand-index-thumb'],
                        ['title' => 'CVR', 'key' => 'cvr', 'prefix' => '', 'suffix' => '%', 'cls' => 'profit', 'icon' => 'bi-funnel'],
                        ['title' => 'CPC', 'key' => 'cpc', 'prefix' => 'Rp ', 'suffix' => '', 'cls' => 'spend', 'icon' => 'bi-cash'],
                    ];
                @endphp
                @foreach($metrics as $m)
                    @php
                        $currSpend = $kpi['current']->spend ?? 0;
                        $currGmv = $kpi['current']->gmv ?? 0;
                        $currOrders = $kpi['current']->orders ?? 0;
                        $currClicks = $kpi['current']->clicks ?? 0;
                        $currImpressions = $kpi['current']->impressions ?? 0;
                        $currSpendAfterTax = $currSpend * 1.11;

                        $prevSpend = $kpi['previous']->spend ?? 0;
                        $prevGmv = $kpi['previous']->gmv ?? 0;
                        $prevOrders = $kpi['previous']->orders ?? 0;
                        $prevClicks = $kpi['previous']->clicks ?? 0;
                        $prevImpressions = $kpi['previous']->impressions ?? 0;
                        $prevSpendAfterTax = $prevSpend * 1.11;

                        $val = $kpi['current']->{$m['key']} ?? 0;
                        $prevVal = $kpi['previous']->{$m['key']} ?? 0;

                        if($m['key'] === 'spend') {
                            $val = $currSpendAfterTax;
                            $prevVal = $prevSpendAfterTax;
                        } elseif($m['key'] === 'roas') {
                            $val = $currSpendAfterTax > 0 ? round($currGmv / $currSpendAfterTax, 2) : 0;
                            $prevVal = $prevSpendAfterTax > 0 ? round($prevGmv / $prevSpendAfterTax, 2) : 0;
                        } elseif ($m['key'] === 'spend_topup') {
                            $val = $currSpendAfterTax;
                            $prevVal = $prevSpendAfterTax;
                        } elseif ($m['key'] === 'cpc') {
                            $val = $currClicks > 0 ? round($currSpendAfterTax / $currClicks, 0) : 0;
                            $prevVal = $prevClicks > 0 ? round($prevSpendAfterTax / $prevClicks, 0) : 0;
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
                                $change = $val > 0 ? null : 0;
                            } else {
                                $change = round((($val - $prevVal) / abs($prevVal)) * 100, 2);
                            }
                        }


                        $hasComparison = $change !== null;
                        $isUp = $hasComparison ? $change >= 0 : true;
                        
                        // For cost metrics, going down is good (green). For others, going up is good.
                        if (!$hasComparison) {
                            $colorClass = 'color: #64748b;';
                        } elseif (in_array($m['key'], ['spend', 'cpc', 'spend_topup'])) {
                            $colorClass = $isUp && $change > 0 ? 'color: #dc2626;' : 'color: #16a34a;';
                        } else {
                            $colorClass = $isUp ? 'color: #16a34a;' : 'color: #dc2626;';
                        }

                        $valueDecimals = in_array($m['key'], ['gmv', 'spend', 'net_profit', 'cpc', 'orders', 'impressions', 'clicks']) ? 0 : 2;
                    @endphp
                    <div class="dpanel ads-kpi kpi-{{ $m['cls'] }}">
                        <div class="ads-kpi-label" @if($m['key'] === 'spend') title="Biaya iklan setelah PPN 11%, termasuk GMV Max Auto" @endif>
                            <i class="bi {{ $m['icon'] }}"></i> {{ $m['title'] }}
                        </div>
                        <div class="ads-kpi-value" style="font-variant-numeric: tabular-nums;">
                            {{ $m['prefix'] }}{{ number_format($val, $valueDecimals, ',', '.') }}{{ $m['suffix'] }}
                        </div>
                        <div class="ads-kpi-sub">
                            @if($m['key'] === 'net_profit')
                                <div style="font-size:.65rem;color:var(--dsh-muted);margin-bottom:.15rem;">
                                    {{ $kpi['current']->profit_campaign_count ?? 0 }} campaign punya HPP
                                    @if(($kpi['current']->profit_unknown_campaign_count ?? 0) > 0)
                                        · <span style="color:#b45309;">{{ $kpi['current']->profit_unknown_campaign_count }} belum dihitung</span>
                                    @endif
                                    @if(($kpi['current']->profit_estimated_campaign_count ?? 0) > 0)
                                        · <span style="color:#b45309;">{{ $kpi['current']->profit_estimated_campaign_count }} estimasi pcs</span>
                                    @endif
                                </div>
                            @endif
                            <span style="font-weight:900; {{ $colorClass }}">
                                @if($hasComparison)<i class="bi bi-arrow-{{ $isUp ? 'up-right' : 'down-right' }}"></i> {{ abs($change) }}%@else<span>Baru</span>@endif
                            </span> 
                            vs {{ $comparisonLabel }}
                        </div>
                    </div>
                @endforeach
            </div>
            </div>
        </div>

            {{-- De-dup: blok "Ringkasan Profit" dipindah sepenuhnya ke tab Profit
                 (_profitability_tab sudah punya Per Kategori / GMV Max ROAS / GMV Max Auto / Produk Bermasalah
                 versi lengkap dengan drilldown). Dihapus dari Ringkasan agar tidak redundan.
                 @include('marketplace.partials._summary_profit_breakdown') --}}

            <div class="dash-panels mb-3">
                <div class="dpanel p-3 d-flex flex-wrap align-items-center gap-3" style="background: var(--card-bg);">
                    <div style="flex: 1; min-width: 200px;">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text" style="background: transparent; border-right: none;"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="campSearch" class="form-control" placeholder="Cari nama kampanye..." style="border-left: none; box-shadow: none;" onkeyup="filterCampaigns()">
                        </div>
                    </div>
                    <div style="width: 150px;">
                        <select id="campStatus" class="form-select form-select-sm" onchange="filterCampaigns()" style="box-shadow: none;">
                            <option value="all">Semua Status</option>
                            <option value="ongoing">Berjalan</option>
                            <option value="paused">Jeda</option>
                            <option value="ended">Selesai</option>
                        </select>
                    </div>
                    <div style="width: 150px;">
                        <select id="campPerf" class="form-select form-select-sm" onchange="filterCampaigns()" style="box-shadow: none;">
                            <option value="all">Semua Performa</option>
                            <option value="boncos">Sedang Boncos</option>
                            <option value="gem">Efisiensi Tinggi</option>
                        </select>
                    </div>
                    <div style="width: 160px;">
                        <select id="campSort" class="form-select form-select-sm" onchange="filterCampaigns()" style="box-shadow: none;">
                            <option value="default">Urutkan (Default)</option>
                            <option value="roas_desc">ROAS Tertinggi</option>
                            <option value="spend_desc">Biaya Tertinggi</option>
                            <option value="gmv_desc">Omzet Tertinggi</option>
                        </select>
                    </div>
                </div>
            </div>

            <script>
            function filterCampaigns() {
                const search = document.getElementById('campSearch').value.toLowerCase();
                const status = document.getElementById('campStatus').value;
                const perf = document.getElementById('campPerf').value;
                const sort = document.getElementById('campSort').value;
                
                const rows = Array.from(document.querySelectorAll('.campaign-row'));
                const tbody = rows[0]?.closest('tbody');
                if(!tbody) return;

                rows.forEach(row => {
                    const rName = row.getAttribute('data-name') || '';
                    const rStatus = row.getAttribute('data-status') || '';
                    const rRoas = parseFloat(row.getAttribute('data-roas')) || 0;
                    const rSpend = parseFloat(row.getAttribute('data-spend')) || 0;
                    
                    let match = true;
                    if(search && !rName.includes(search)) match = false;
                    if(status !== 'all' && rStatus !== status) match = false;
                    
                    if(perf === 'boncos' && !(rSpend > 50000 && rRoas < 1.5)) match = false;
                    if(perf === 'gem' && !(rSpend > 10000 && rRoas >= 5.0)) match = false;
                    
                    row.style.display = match ? '' : 'none';
                });

                if(sort !== 'default') {
                    rows.sort((a, b) => {
                        let valA = 0, valB = 0;
                        if(sort === 'roas_desc') { valA = parseFloat(a.getAttribute('data-roas'))||0; valB = parseFloat(b.getAttribute('data-roas'))||0; }
                        if(sort === 'spend_desc') { valA = parseFloat(a.getAttribute('data-spend'))||0; valB = parseFloat(b.getAttribute('data-spend'))||0; }
                        if(sort === 'gmv_desc') { valA = parseFloat(a.getAttribute('data-gmv'))||0; valB = parseFloat(b.getAttribute('data-gmv'))||0; }
                        return valB - valA;
                    });
                    rows.forEach(row => tbody.appendChild(row));
                }
            }
            </script>
            
            <div class="dpanel">
                @include('marketplace.partials._campaign_table', ['campaigns' => $campaigns])
            </div>

        </div>

        <!-- TAB PERFORMA PRODUK -->
        <div class="tab-pane" id="tab-items">
            @include('marketplace.partials._products_tab')
        </div>

        <!-- TAB FUNNEL -->
        <div class="tab-pane" id="tab-funnel">
            @include('marketplace.partials._funnel_tab')
        </div>

        <!-- TAB CREATIVE & AUDIENCE -->
        <div class="tab-pane" id="tab-creative">
            @include('marketplace.partials._creative_audience_tab')
        </div>

        <!-- TAB CUSTOMER & LTV -->
        <div class="tab-pane" id="tab-ltv">
            @include('marketplace.partials._ltv_tab')
        </div>

        <!-- TAB ALERTS & ACTION CENTER -->
        <div class="tab-pane" id="tab-alerts">
            @include('marketplace.partials._alerts_tab')
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

@php
    // Fallbacks to prevent undefined variable errors when $stores->isEmpty() triggers early return
    $kpi = $kpi ?? [];
    $dailyChartData = $dailyChartData ?? [];
    $heatmapData = $heatmapData ?? [];
    $historicalData = $historicalData ?? [];
    $itemPerformance = collect($itemPerformance ?? []);
    $syncRuns = $syncRuns ?? collect();
    $lastSuccessRun = $lastSuccessRun ?? null;
    $insightTraffic = $insightTraffic ?? collect();
    $campaigns = $campaigns ?? collect();
    $adsSetting = $adsSetting ?? (object)[];
    $metrics = $metrics ?? [];
    
    // Default JS empty arrays just in case they are used in scripts without ??
    $dailyChartDataJson = json_encode($dailyChartData);
    $heatmapDataJson = json_encode($heatmapData);
    $historicalDataJson = json_encode($historicalData);
@endphp

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
                <form id="formSyncAds" action="{{ route('marketplace.ads.sync') }}" method="POST">
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

                    <label style="font-size: .75rem; font-weight: 650; color: var(--dsh-muted); display: block; margin-bottom: .4rem;">Pilih Mode Sync</label>
                    <div id="syncRangeOptions" style="display:grid; gap:.45rem; margin-bottom:.6rem;">
                        <label class="sync-range-card">
                            <span style="display:flex; align-items:center; gap:.6rem;">
                                <input type="radio" name="sync_type" value="today" checked>
                                <span>
                                    <span style="display:block; font-size:.82rem; font-weight:650; color:var(--text);">Hari Ini</span>
                                    <span style="display:block; font-size:.68rem; color:var(--dsh-muted);">Sync data hari ini saja</span>
                                </span>
                            </span>
                            <i class="bi bi-sun" style="color:var(--dsh-muted);"></i>
                        </label>
                        <label class="sync-range-card">
                            <span style="display:flex; align-items:center; gap:.6rem;">
                                <input type="radio" name="sync_type" value="yesterday">
                                <span>
                                    <span style="display:block; font-size:.82rem; font-weight:650; color:var(--text);">Kemarin</span>
                                    <span style="display:block; font-size:.68rem; color:var(--dsh-muted);">Sync data kemarin saja</span>
                                </span>
                            </span>
                            <i class="bi bi-moon" style="color:var(--dsh-muted);"></i>
                        </label>
                        <label class="sync-range-card">
                            <span style="display:flex; align-items:center; gap:.6rem;">
                                <input type="radio" name="sync_type" value="last_7_days">
                                <span>
                                    <span style="display:block; font-size:.82rem; font-weight:650; color:var(--text);">1 Minggu Terakhir</span>
                                    <span style="display:block; font-size:.68rem; color:var(--dsh-muted);">Sync 7 hari ke belakang</span>
                                </span>
                            </span>
                            <i class="bi bi-calendar-week" style="color:var(--dsh-muted);"></i>
                        </label>
                        <label class="sync-range-card">
                            <span style="display:flex; align-items:center; gap:.6rem;">
                                <input type="radio" name="sync_type" value="custom">
                                <span>
                                    <span style="display:block; font-size:.82rem; font-weight:650; color:var(--text);">Rentang Kustom (Backfill)</span>
                                    <span style="display:block; font-size:.68rem; color:var(--dsh-muted);">Tarik hingga maksimal 6 bulan terakhir</span>
                                </span>
                            </span>
                            <i class="bi bi-calendar-range" style="color:var(--dsh-muted);"></i>
                        </label>
                    </div>

                    <!-- Pilihan Tanggal Khusus -->
                    <div id="customDateRangeSettings" style="display: none; background: rgba(37,99,235,0.05); border: 1px dashed rgba(37,99,235,0.3); border-radius: 12px; padding: 1rem; margin-bottom: 1rem;">
                        <div class="row g-2">
                            <div class="col-6">
                                <label style="font-size:.7rem; font-weight:600; color:var(--dsh-muted); margin-bottom:.3rem;">Dari Tanggal</label>
                                <input type="date" name="date_from_custom" id="dateFromCustom" class="form-control" style="font-size:.8rem; border-radius:8px;" value="{{ now()->subMonths(1)->toDateString() }}" max="{{ now()->toDateString() }}">
                            </div>
                            <div class="col-6">
                                <label style="font-size:.7rem; font-weight:600; color:var(--dsh-muted); margin-bottom:.3rem;">Sampai Tanggal</label>
                                <input type="date" name="date_to_custom" id="dateToCustom" class="form-control" style="font-size:.8rem; border-radius:8px;" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}">
                            </div>
                        </div>
                        <div style="font-size:.65rem; color:var(--dsh-accent); margin-top:.5rem;"><i class="bi bi-info-circle"></i> Penarikan lebih dari 14 hari akan memerlukan waktu yang agak lama.</div>
                    </div>

                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const syncTypeRadios = document.querySelectorAll('input[name="sync_type"]');
                            const customDateDiv = document.getElementById('customDateRangeSettings');
                            
                            syncTypeRadios.forEach(radio => {
                                radio.addEventListener('change', function() {
                                    if (this.value === 'custom') {
                                        customDateDiv.style.display = 'block';
                                    } else {
                                        customDateDiv.style.display = 'none';
                                    }
                                });
                            });
                        });
                    </script>

                    <div style="font-size:.68rem; color:var(--dsh-muted); margin-bottom:1rem;">
                        <div><i class="bi bi-info-circle"></i> Mode lain ditutup dulu supaya alur sync lebih jelas dan aman.</div>
                        <div class="mt-1">
                            <i class="bi bi-clock-history"></i> Terakhir berhasil ditarik: <strong style="color:var(--text);">{{ $lastSyncAt ?? 'Belum pernah' }}</strong>
                            @if(!empty($lastSyncTime))
                                <span style="opacity: 0.7;">({{ $lastSyncTime }})</span>
                            @endif
                        </div>
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
window.AdsDashboardRoutes = {
    realtimeStatus: @json(route('marketplace.ads.realtime.status')),
    cpcCampaignEdit: @json(route('marketplace.ads.cpc.campaign.edit')),
    gmsItemAction: @json(route('marketplace.ads.gms.action')),
    gmsCampaignEdit: @json(route('marketplace.ads.gms.campaign.edit')),
    campaignSchedule: @json(route('marketplace.ads.campaign.schedule')),
    campaignScheduleCancel: @json(route('marketplace.ads.campaign.schedule.cancel', ['schedule' => '__SCHEDULE_ID__'])),
    historicalComparison: @json(route('marketplace.ads.historical.comparison')),
    campaignHourly: @json(route('marketplace.ads.campaign.hourly')),
    clear: @json(route('marketplace.ads.clear')),
    sync: @json(route('marketplace.ads.sync')),
    syncCancel: @json(route('marketplace.ads.sync.cancel')),
    feeSetting: @json(route('marketplace.ads.fee.setting')),
    experimentsIndex: @json(route('marketplace.ads.experiments.index')),
    experimentsShow: @json(route('marketplace.ads.experiments.show', ['experiment' => '__EXPERIMENT_ID__'])),
    experimentsSimulate: @json(route('marketplace.ads.experiments.simulate')),
};
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const controlRows = document.querySelectorAll('[data-control-row]');
    if (!controlRows.length) return;

    if (window.bootstrap?.Tooltip) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
            window.bootstrap.Tooltip.getOrCreateInstance(element, { boundary: 'viewport' });
        });
    }

    const parseControlNumber = (value, kind) => {
        let raw = String(value ?? '').trim().replace(/\s/g, '');
        if (!raw) return null;
        if (kind === 'roas') {
            if (raw.includes(',') && raw.includes('.')) raw = raw.replace(/\./g, '').replace(',', '.');
            else raw = raw.replace(',', '.');
        } else {
            raw = raw.replace(/[^0-9]/g, '');
        }
        const number = Number(raw);
        return Number.isFinite(number) && number >= 0 ? number : null;
    };

    const controlFeedback = (title, text, icon = 'success') => {
        if (window.Swal) return Swal.fire({ title, text, icon, timer: icon === 'success' ? 1600 : undefined, showConfirmButton: icon !== 'success' });
        window.alert(title + (text ? '\n' + text : ''));
        return Promise.resolve();
    };

    const runControlAction = async (button, action) => {
        const row = button.closest('[data-control-row]');
        if (!row || button.disabled) return;
        const kind = row.dataset.kind;
        const storeId = row.dataset.storeId;
        const campaignId = row.dataset.campaignId;
        const isGms = kind === 'gms';
        let endpoint = isGms ? window.AdsDashboardRoutes.gmsCampaignEdit : window.AdsDashboardRoutes.cpcCampaignEdit;
        const payload = {
            store_id: storeId,
            campaign_id: campaignId,
        };

        if (action === 'schedule') {
            endpoint = window.AdsDashboardRoutes.campaignSchedule;
            const scheduledAt = row.querySelector('[data-control-schedule-at]')?.value || '';
            const scheduleAction = row.querySelector('[data-control-schedule-action]')?.value || '';
            if (!scheduledAt) return controlFeedback('Waktu jadwal belum dipilih', 'Pilih tanggal dan jam untuk aksi campaign.', 'warning');
            payload.action = scheduleAction;
            payload.scheduled_at = scheduledAt;
            if (scheduleAction === 'budget') {
                const budget = parseControlNumber(row.querySelector('[data-control-schedule-budget]')?.value, 'budget');
                if (budget === null) return controlFeedback('Modal harian belum valid', 'Isi angka modal, gunakan 0 untuk Unlimited.', 'warning');
                payload.daily_budget = budget;
            }
        } else if (action === 'roas') {
            const value = parseControlNumber(row.querySelector('[data-control-roas]')?.value, 'roas');
            if (value === null) return controlFeedback('Target ROAS belum valid', 'Isi angka ROAS, atau 0 untuk Auto.', 'warning');
            payload.roas_target = value;
        } else if (action === 'budget') {
            const input = row.querySelector('[data-control-budget]');
            const value = input?.value?.trim() === '' ? 0 : parseControlNumber(input?.value, 'budget');
            if (value === null) return controlFeedback('Modal harian belum valid', 'Isi angka tanpa simbol mata uang.', 'warning');
            payload.daily_budget = value;
        } else {
            payload.status_action = action;
            if (action === 'stop') {
                const confirmed = window.Swal
                    ? await Swal.fire({ title: 'Hentikan iklan?', text: 'Campaign akan diberi tanggal akhir hari ini.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Hentikan', cancelButtonText: 'Batal', confirmButtonColor: '#dc2626' }).then(result => result.isConfirmed)
                    : window.confirm('Hentikan campaign ini hari ini?');
                if (!confirmed) return;
            }
        }

        const originalHtml = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify(payload),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || data.status !== 'success') {
                throw new Error(data.message || 'API Shopee menolak perubahan.');
            }
            await controlFeedback('Berhasil diperbarui', data.message || 'Pengaturan campaign tersimpan.');
            window.location.reload();
        } catch (error) {
            await controlFeedback('Gagal memperbarui campaign', error.message || 'Terjadi kesalahan koneksi.', 'error');
            button.disabled = false;
            button.innerHTML = originalHtml;
        }
    };

    controlRows.forEach(row => {
        row.querySelectorAll('[data-control-toggle]').forEach(toggle => {
            toggle.addEventListener('click', () => {
                const field = toggle.closest('[data-control-field]');
                if (!field) return;
                field.querySelector('[data-control-display]')?.style.setProperty('display', 'none');
                const editor = field.querySelector('[data-control-editor]');
                editor?.style.setProperty('display', 'flex');
                const input = editor?.querySelector('input');
                input?.focus();
                input?.select();
            });
        });
        row.querySelectorAll('[data-control-cancel]').forEach(cancel => {
            cancel.addEventListener('click', () => {
                const field = cancel.closest('[data-control-field]');
                if (!field) return;
                field.querySelector('[data-control-editor]')?.style.setProperty('display', 'none');
                field.querySelector('[data-control-display]')?.style.setProperty('display', '');
            });
        });
        row.querySelectorAll('[data-control-action]').forEach(button => {
            button.addEventListener('click', () => runControlAction(button, button.dataset.controlAction));
        });
    });

    const scheduleModalEl = document.getElementById('modalCampaignSchedule');
    const scheduleActionEl = document.getElementById('campaignScheduleAction');
    const scheduleBudgetWrap = document.getElementById('campaignScheduleBudgetWrap');
    const scheduleBudgetEl = document.getElementById('campaignScheduleBudget');
    const scheduleAtEl = document.getElementById('campaignScheduleAt');
    const scheduleNameEl = document.getElementById('campaignScheduleName');
    const scheduleSubmitEl = document.getElementById('campaignScheduleSubmit');
    let scheduleRow = null;

    const syncScheduleModalFields = () => {
        if (!scheduleActionEl || !scheduleBudgetWrap || !scheduleBudgetEl) return;
        const isBudget = scheduleActionEl.value === 'budget';
        scheduleBudgetWrap.style.display = isBudget ? '' : 'none';
        if (!isBudget) scheduleBudgetEl.value = '';
    };
    scheduleActionEl?.addEventListener('change', syncScheduleModalFields);

    document.querySelectorAll('[data-control-schedule-open]').forEach(button => {
        button.addEventListener('click', () => {
            scheduleRow = button.closest('[data-control-row]');
            if (!scheduleRow || !scheduleModalEl) return;
            const campaignName = scheduleRow.querySelector('.ads-control-name > div')?.textContent?.trim() || 'Campaign';
            if (scheduleNameEl) scheduleNameEl.textContent = campaignName;
            if (scheduleActionEl) scheduleActionEl.value = 'pause';
            if (scheduleBudgetEl) scheduleBudgetEl.value = '';
            if (scheduleAtEl) scheduleAtEl.value = '';
            syncScheduleModalFields();
            window.bootstrap?.Modal.getOrCreateInstance(scheduleModalEl).show();
        });
    });

    scheduleSubmitEl?.addEventListener('click', async () => {
        if (!scheduleRow || scheduleSubmitEl.disabled) return;
        const scheduleAction = scheduleActionEl?.value || '';
        const scheduledAt = scheduleAtEl?.value || '';
        if (!scheduledAt) return controlFeedback('Waktu belum dipilih', 'Pilih tanggal dan jam eksekusi.', 'warning');

        const payload = {
            store_id: scheduleRow.dataset.storeId,
            campaign_id: scheduleRow.dataset.campaignId,
            action: scheduleAction,
            scheduled_at: scheduledAt,
        };
        if (scheduleAction === 'budget') {
            const budget = parseControlNumber(scheduleBudgetEl?.value, 'budget');
            if (budget === null) return controlFeedback('Modal belum valid', 'Isi angka modal, gunakan 0 untuk Unlimited.', 'warning');
            payload.daily_budget = budget;
        }

        const originalHtml = scheduleSubmitEl.innerHTML;
        scheduleSubmitEl.disabled = true;
        scheduleSubmitEl.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';
        try {
            const response = await fetch(window.AdsDashboardRoutes.campaignSchedule, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify(payload),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || data.status !== 'success') throw new Error(data.message || 'Jadwal gagal dibuat.');
            window.bootstrap?.Modal.getOrCreateInstance(scheduleModalEl).hide();
            await controlFeedback('Jadwal tersimpan', data.message || 'Aksi campaign berhasil dijadwalkan.');
            window.location.reload();
        } catch (error) {
            await controlFeedback('Gagal menyimpan jadwal', error.message || 'Terjadi kesalahan koneksi.', 'error');
            scheduleSubmitEl.disabled = false;
            scheduleSubmitEl.innerHTML = originalHtml;
        }
    });

    document.querySelectorAll('[data-schedule-cancel]').forEach(button => {
        button.addEventListener('click', async () => {
            const confirmed = window.Swal
                ? await Swal.fire({ title: 'Batalkan jadwal?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Batalkan', cancelButtonText: 'Tutup' }).then(result => result.isConfirmed)
                : window.confirm('Batalkan jadwal ini?');
            if (!confirmed) return;

            const scheduleId = button.dataset.scheduleCancel;
            const endpoint = window.AdsDashboardRoutes.campaignScheduleCancel.replace('__SCHEDULE_ID__', scheduleId);
            button.disabled = true;
            try {
                const response = await fetch(endpoint, {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok || data.status !== 'success') throw new Error(data.message || 'Jadwal gagal dibatalkan.');
                await controlFeedback('Jadwal dibatalkan', data.message || '');
                window.location.reload();
            } catch (error) {
                await controlFeedback('Gagal membatalkan jadwal', error.message, 'error');
                button.disabled = false;
            }
        });
    });
});
</script>
<script src="{{ asset('js/marketplace-ads-dashboard-extra.js') }}"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const rawDaily = @json($dailyChartData ?? []);
    const rawHourly = @json($heatmapData ?? []);
    const hourlyInternalItems = @json($heatmapInternalItems ?? []);
    let rawHistorical = @json($historicalData ?? []);
    let historicalCompareMode = @json($compareMode ?? 'prev_period');
    
    // Pad Daily Data to show full range
    const dailyData = [];
    const fromEl = document.getElementById('fromHidden');
    const toEl = document.getElementById('toHidden');
    if (fromEl && toEl && fromEl.value && toEl.value) {
        // use ymd function defined earlier or create inline logic
        function ymdLocal(d) { return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0'); }
        const dStart = parseLocalDate(fromEl.value);
        const dEnd = parseLocalDate(toEl.value);
        for (let d = new Date(dStart); d <= dEnd; d.setDate(d.getDate() + 1)) {
            let ds = ymdLocal(d);
            let found = rawDaily.find(item => {
                if(!item.date) return false;
                let itemDate = parseLocalDate(item.date);
                return ymdLocal(itemDate) === ds;
            });
            dailyData.push(found ? found : { date: ds, spend: 0, gmv: 0, roas: 0, impressions: 0, clicks: 0, ctr: 0 });
        }
    } else {
        dailyData.push(...rawDaily);
    }

    // Pad Hourly Data to show full 24 hours
    const hourlyData = [];
    for (let i = 0; i < 24; i++) {
        let found = rawHourly.find(d => parseInt(d.performance_hour) === i);
        hourlyData.push(found ? found : { performance_hour: i, impressions: 0, clicks: 0, orders: 0, expense: 0, gmv: 0 });
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
    const ADS_COST_MULTIPLIER = 1.11;

    // Helper: Format Indo Date (YYYY-MM-DD to DD MMM)
    const formatIndoDate = (dateStr) => {
        if(!dateStr) return '';
        const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
        let parts = dateStr.split('-');
        if(parts.length !== 3) return dateStr;
        return parseInt(parts[2]) + ' ' + months[parseInt(parts[1])-1];
    };

    // Calculate summaries for charts
    let totalDailySpend = dailyData.reduce((sum, d) => sum + (parseFloat(d.spend || 0) * ADS_COST_MULTIPLIER), 0);
    let totalDailyGmv = dailyData.reduce((sum, d) => sum + parseFloat(d.gmv || 0), 0);
    let totalDailyOrders = dailyData.reduce((sum, d) => sum + parseInt(d.orders || 0), 0);
    let totalDailyAov = totalDailyOrders > 0 ? (totalDailyGmv / totalDailyOrders) : 0;
    let totalDailyRoas = totalDailySpend > 0 ? (totalDailyGmv / totalDailySpend).toFixed(2) : "0.00";
    const hasDailyGrossProfit = dailyData.some(d => d.gross_profit !== null && d.gross_profit !== undefined);
    let totalDailyGrossProfit = dailyData.reduce((sum, d) => sum + (parseFloat(d.gross_profit || 0)), 0);
    let dsEl = document.getElementById('dailySummary');
    if(dsEl) {
        dsEl.className = 'daily-trend-stats';
        dsEl.innerHTML = `
            <span class="daily-trend-stat"><span class="daily-trend-stat-label">Biaya</span><span class="daily-trend-stat-value" style="color:#dc2626">Rp ${formatShortIDR(totalDailySpend)}</span></span>
            <span class="daily-trend-stat"><span class="daily-trend-stat-label">GMV</span><span class="daily-trend-stat-value" style="color:#16a34a">Rp ${formatShortIDR(totalDailyGmv)}</span></span>
            <span class="daily-trend-stat"><span class="daily-trend-stat-label">AOV</span><span class="daily-trend-stat-value" style="color:#64748b">Rp ${formatShortIDR(totalDailyAov)}</span></span>
            <span class="daily-trend-stat"><span class="daily-trend-stat-label">Gross Profit</span><span class="daily-trend-stat-value" style="color:${totalDailyGrossProfit >= 0 ? '#7c3aed' : '#dc2626'}">${hasDailyGrossProfit ? 'Rp ' + formatShortIDR(totalDailyGrossProfit) : '—'}</span></span>
            <span class="daily-trend-stat"><span class="daily-trend-stat-label">ROAS</span><span class="daily-trend-stat-value" style="color:#b45309">${totalDailyRoas}x</span></span>`;
    }

    const dailyDataTableEl = document.getElementById('dailyDataTable');
    if (dailyDataTableEl) {
        let dailySortKey = 'date';
        let dailySortDirection = 'desc';
        const dailyRows = dailyData.map(day => {
            const spend = parseFloat(day.spend || 0) * ADS_COST_MULTIPLIER;
            const gmv = parseFloat(day.gmv || 0);
            const impressions = parseInt(day.impressions || 0);
            const clicks = parseInt(day.clicks || 0);
            const orders = parseInt(day.orders || 0);
            return {
                date: day.date || '',
                spend,
                gmv,
                impressions,
                clicks,
                orders,
                grossProfit: day.gross_profit === null || day.gross_profit === undefined ? null : parseFloat(day.gross_profit || 0),
                ctr: impressions > 0 ? (clicks / impressions) * 100 : 0,
                aov: orders > 0 ? gmv / orders : 0,
                roas: spend > 0 ? gmv / spend : 0,
            };
        });
        const dailyHeader = (key, label, className = '') => {
            const active = dailySortKey === key;
            const indicator = active ? (dailySortDirection === 'asc' ? '↑' : '↓') : '↕';
            const priority = ['roas', 'ctr', 'grossProfit'].includes(key);
            return `<th class="${className}" data-daily-sort="${key}" role="button" tabindex="0" title="Klik untuk mengurutkan" style="cursor:pointer;user-select:none;${priority ? 'background:rgba(245,158,11,.07);' : ''}">${label} <span style="font-size:.62rem;opacity:${active ? '1' : '.45'};">${indicator}</span></th>`;
        };
        const renderDailyDataTable = () => {
            const rows = [...dailyRows].sort((a, b) => {
                const aValue = a[dailySortKey];
                const bValue = b[dailySortKey];
                const comparison = typeof aValue === 'string' ? aValue.localeCompare(bValue) : aValue - bValue;
                return comparison * (dailySortDirection === 'asc' ? 1 : -1);
            });
            dailyDataTableEl.innerHTML = rows.length
                ? `<div style="font-size:.68rem; color:var(--dsh-muted); font-weight:700; margin-bottom:.35rem;">Data per tanggal</div>
                    <table class="table table-sm table-hover align-middle mb-0" style="font-size:.67rem;">
                        <thead><tr>${dailyHeader('date', 'Tanggal')}${dailyHeader('roas', 'ROAS', 'text-end')}${dailyHeader('impressions', 'Impresi', 'text-end')}${dailyHeader('clicks', 'Klik', 'text-end')}${dailyHeader('ctr', 'CTR', 'text-end')}${dailyHeader('orders', 'Orders', 'text-end')}${dailyHeader('spend', 'Biaya', 'text-end')}${dailyHeader('gmv', 'GMV', 'text-end')}${dailyHeader('grossProfit', 'Gross Profit', 'text-end')}${dailyHeader('aov', 'AOV', 'text-end')}</tr></thead>
                        <tbody>${rows.map(row => `<tr>
                            <td class="fw-semibold">${formatIndoDate(row.date)}</td>
                            <td class="text-end fw-bold" style="background:rgba(245,158,11,.05);">${row.roas.toFixed(2)}x</td>
                            <td class="text-end">${row.impressions.toLocaleString('id-ID')}</td>
                            <td class="text-end">${row.clicks.toLocaleString('id-ID')}</td>
                            <td class="text-end" style="background:rgba(59,130,246,.05);">${row.ctr.toFixed(2)}%</td>
                            <td class="text-end">${row.orders.toLocaleString('id-ID')}</td>
                            <td class="text-end text-danger">Rp ${formatShortIDR(row.spend)}</td>
                            <td class="text-end text-success">Rp ${formatShortIDR(row.gmv)}</td>
                            <td class="text-end fw-bold" style="color:${row.grossProfit === null ? '#94a3b8' : (row.grossProfit >= 0 ? '#7c3aed' : '#dc2626')};">${row.grossProfit === null ? '—' : 'Rp ' + formatShortIDR(row.grossProfit)}</td>
                            <td class="text-end">Rp ${formatShortIDR(row.aov)}</td>
                        </tr>`).join('')}</tbody>
                    </table>`
                : '<div style="font-size:.72rem;color:var(--dsh-muted);">Belum ada data harian.</div>';
            dailyDataTableEl.querySelectorAll('[data-daily-sort]').forEach(header => {
                const sortByHeader = () => {
                    const nextKey = header.dataset.dailySort;
                    if (dailySortKey === nextKey) {
                        dailySortDirection = dailySortDirection === 'asc' ? 'desc' : 'asc';
                    } else {
                        dailySortKey = nextKey;
                        dailySortDirection = nextKey === 'date' ? 'desc' : 'desc';
                    }
                    renderDailyDataTable();
                };
                header.addEventListener('click', sortByHeader);
                header.addEventListener('keydown', event => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        sortByHeader();
                    }
                });
            });
        };
        renderDailyDataTable();
    }

    const trafficDataTableEl = document.getElementById('trafficDataTable');
    if (trafficDataTableEl) {
        let trafficSortKey = 'date';
        let trafficSortDirection = 'desc';
        const trafficRows = dailyData.map(day => {
            const gmv = parseFloat(day.gmv || 0);
            const impressions = parseInt(day.impressions || 0);
            const clicks = parseInt(day.clicks || 0);
            const orders = parseInt(day.orders || 0);
            return {
                date: day.date || '',
                impressions,
                clicks,
                orders,
                ctr: impressions > 0 ? (clicks / impressions) * 100 : 0,
                cvr: clicks > 0 ? (orders / clicks) * 100 : 0,
                aov: orders > 0 ? gmv / orders : 0,
            };
        });
        const trafficHeader = (key, label, className = '') => {
            const active = trafficSortKey === key;
            const indicator = active ? (trafficSortDirection === 'asc' ? '↑' : '↓') : '↕';
            const priorityStyle = ['ctr', 'cvr'].includes(key) ? 'background:rgba(139,92,246,.07);' : '';
            return `<th class="${className}" data-traffic-sort="${key}" role="button" tabindex="0" title="Klik untuk mengurutkan" style="cursor:pointer;user-select:none;${priorityStyle}">${label} <span style="font-size:.62rem;opacity:${active ? '1' : '.45'};">${indicator}</span></th>`;
        };
        const renderTrafficDataTable = () => {
            const rows = [...trafficRows].sort((a, b) => {
                const aValue = a[trafficSortKey];
                const bValue = b[trafficSortKey];
                const comparison = typeof aValue === 'string' ? aValue.localeCompare(bValue) : aValue - bValue;
                return comparison * (trafficSortDirection === 'asc' ? 1 : -1);
            });
            trafficDataTableEl.innerHTML = rows.length
                ? `<div style="font-size:.68rem; color:var(--dsh-muted); font-weight:700; margin-bottom:.35rem;">Data trafik per tanggal</div>
                    <table class="table table-sm table-hover align-middle mb-0" style="font-size:.67rem;">
                        <thead><tr>${trafficHeader('date', 'Tanggal')}${trafficHeader('impressions', 'Impresi', 'text-end')}${trafficHeader('clicks', 'Klik', 'text-end')}${trafficHeader('ctr', 'CTR', 'text-end')}${trafficHeader('orders', 'Orders', 'text-end')}${trafficHeader('cvr', 'CVR', 'text-end')}${trafficHeader('aov', 'AOV', 'text-end')}</tr></thead>
                        <tbody>${rows.map(row => `<tr>
                            <td class="fw-semibold">${formatIndoDate(row.date)}</td>
                            <td class="text-end">${row.impressions.toLocaleString('id-ID')}</td>
                            <td class="text-end">${row.clicks.toLocaleString('id-ID')}</td>
                            <td class="text-end fw-bold" style="background:rgba(139,92,246,.06);">${row.ctr.toFixed(2)}%</td>
                            <td class="text-end">${row.orders.toLocaleString('id-ID')}</td>
                            <td class="text-end fw-bold" style="background:rgba(139,92,246,.06);">${row.cvr.toFixed(2)}%</td>
                            <td class="text-end">Rp ${formatShortIDR(row.aov)}</td>
                        </tr>`).join('')}</tbody>
                    </table>`
                : '<div style="font-size:.72rem;color:var(--dsh-muted);">Belum ada data trafik harian.</div>';
            trafficDataTableEl.querySelectorAll('[data-traffic-sort]').forEach(header => {
                const sortByHeader = () => {
                    const nextKey = header.dataset.trafficSort;
                    if (trafficSortKey === nextKey) {
                        trafficSortDirection = trafficSortDirection === 'asc' ? 'desc' : 'asc';
                    } else {
                        trafficSortKey = nextKey;
                        trafficSortDirection = 'desc';
                    }
                    renderTrafficDataTable();
                };
                header.addEventListener('click', sortByHeader);
                header.addEventListener('keydown', event => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        sortByHeader();
                    }
                });
            });
        };
        renderTrafficDataTable();
    }

    let totalHourlySpend = hourlyData.reduce((sum, d) => sum + (parseFloat(d.expense || 0) * ADS_COST_MULTIPLIER), 0);
    let totalHourlyGmv = hourlyData.reduce((sum, d) => sum + parseFloat(d.gmv || 0), 0);
    let totalHourlyImpressions = hourlyData.reduce((sum, d) => sum + parseInt(d.impressions || 0), 0);
    let totalHourlyClicks = hourlyData.reduce((sum, d) => sum + parseInt(d.clicks || 0), 0);
    let totalHourlyOrders = hourlyData.reduce((sum, d) => sum + parseInt(d.orders || 0), 0);
    let totalHourlyRoas = totalHourlySpend > 0 ? (totalHourlyGmv / totalHourlySpend).toFixed(2) : "0.00";
    let hsEl = document.getElementById('hourlySummary');
    if(hsEl) {
        hsEl.innerHTML = `<span style="color:#f59e0b">${totalHourlyImpressions.toLocaleString('id-ID')} Impresi</span> &bull; <span style="color:#3b82f6">${totalHourlyClicks.toLocaleString('id-ID')} Klik</span> &bull; <span style="color:#10b981">${totalHourlyOrders.toLocaleString('id-ID')} Orders</span> &bull; <span style="color:#eab308">${totalHourlyRoas}x ROAS</span>`;
    }
    const hourlyDataNotice = document.getElementById('hourlyDataNotice');
    if (hourlyDataNotice && rawHourly.length === 0) {
        const selectedFrom = fromEl && fromEl.value ? formatIndoDate(fromEl.value) : 'tanggal terpilih';
        const selectedTo = toEl && toEl.value ? formatIndoDate(toEl.value) : selectedFrom;
        hourlyDataNotice.style.display = 'block';
        hourlyDataNotice.innerHTML = `<i class="bi bi-info-circle me-1"></i> Belum ada data per jam untuk periode ${selectedFrom}${selectedFrom !== selectedTo ? ' – ' + selectedTo : ''}. Grafik tetap menampilkan 24 jam sebagai baseline.`;
    }

    const hourlyMetricRows = hourlyData.map(d => {
        const hour = parseInt(d.performance_hour || 0);
        const spend = parseFloat(d.expense || 0) * ADS_COST_MULTIPLIER;
        const gmv = parseFloat(d.gmv || 0);
        const impressions = parseInt(d.impressions || 0);
        const clicks = parseInt(d.clicks || 0);
        const orders = parseInt(d.orders || 0);
        return {
            hour,
            label: String(hour).padStart(2, '0') + ':00–' + String((hour + 1) % 24).padStart(2, '0') + ':00',
            spend,
            gmv,
            impressions,
            clicks,
            orders,
            roas: spend > 0 ? gmv / spend : 0,
            aov: orders > 0 ? gmv / orders : 0,
            ctr: impressions > 0 ? (clicks / impressions) * 100 : 0,
            cvr: clicks > 0 ? (orders / clicks) * 100 : 0,
            grossProfit: gmv - spend,
        };
    });
    const hourlyItemDetailEl = document.getElementById('hourlyItemDetail');
    const hourlyItemDetailTitleEl = document.getElementById('hourlyItemDetailTitle');
    const hourlyItemDetailContentEl = document.getElementById('hourlyItemDetailContent');
    const hourlyItemDetailCloseEl = document.getElementById('hourlyItemDetailClose');
    const escapeHourlyHtml = value => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    const showHourlyItemDetail = (hour) => {
        if (!hourlyItemDetailEl || !hourlyItemDetailTitleEl || !hourlyItemDetailContentEl) return;
        const selectedStore = document.querySelector('select[name="store_id"]')?.value || 'all';
        const items = hourlyInternalItems.filter(item => selectedStore === 'all' || String(item.store_id) === String(selectedStore));
        hourlyItemDetailTitleEl.innerHTML = `<i class="bi bi-box-seam text-primary me-1"></i> Item internal terkait · ${String(hour).padStart(2, '0')}:00`;
        hourlyItemDetailContentEl.innerHTML = items.length
            ? `<div style="font-size:.62rem; color:var(--dsh-muted); margin-bottom:.4rem;">Mapping item pada campaign aktif di periode terpilih.</div>
                <div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0" style="font-size:.66rem;">
                    <thead><tr><th>Item internal</th><th>Campaign</th><th>Kategori</th></tr></thead>
                    <tbody>${items.map(item => `<tr>
                        <td><div class="fw-semibold">${escapeHourlyHtml(item.internal_item_name)}</div><div style="font-size:.6rem;color:var(--dsh-muted);">ID ${escapeHourlyHtml(item.internal_item_id)}</div></td>
                        <td>${escapeHourlyHtml(item.campaign_name)}</td>
                        <td>${escapeHourlyHtml(item.category || '—')}</td>
                    </tr>`).join('')}</tbody>
                </table></div>`
            : '<div style="font-size:.68rem; color:var(--dsh-muted);">Belum ada item internal yang ter-mapping pada campaign periode ini.</div>';
        hourlyItemDetailEl.style.display = 'block';
    };
    hourlyItemDetailCloseEl?.addEventListener('click', () => {
        if (hourlyItemDetailEl) hourlyItemDetailEl.style.display = 'none';
    });
    const hourlyBreakdownEl = document.getElementById('hourlyBreakdown');
    if (hourlyBreakdownEl) {
        let hourlySortKey = 'cvr';
        let hourlySortDirection = 'desc';
        const hourlyPriorityMeta = {
            roas: { icon: 'bi-lightning-charge-fill', color: '#b45309', background: 'rgba(245, 158, 11, 0.10)', title: 'Efisiensi iklan' },
            ctr: { icon: 'bi-bullseye', color: '#1d4ed8', background: 'rgba(59, 130, 246, 0.10)', title: 'Daya tarik iklan' },
            cvr: { icon: 'bi-funnel-fill', color: '#7e22ce', background: 'rgba(139, 92, 246, 0.10)', title: 'Konversi' },
            grossProfit: { icon: 'bi-cash-coin', color: '#15803d', background: 'rgba(22, 163, 74, 0.10)', title: 'Profitabilitas' },
        };
        const sortableHeader = (key, label, className = '') => {
            const active = hourlySortKey === key;
            const indicator = active ? (hourlySortDirection === 'asc' ? '↑' : '↓') : '↕';
            const priority = hourlyPriorityMeta[key];
            const priorityIcon = priority ? `<i class="bi ${priority.icon} me-1"></i>` : '';
            const priorityStyle = priority ? `background:${priority.background};color:${priority.color};` : '';
            const title = priority ? `${priority.title} · klik untuk mengurutkan` : 'Klik untuk mengurutkan';
            return `<th class="${className}" data-hourly-sort="${key}" role="button" tabindex="0" title="${title}" style="cursor:pointer;user-select:none;${priorityStyle}">${priorityIcon}${label} <span style="font-size:.62rem;opacity:${active ? '1' : '.45'};">${indicator}</span></th>`;
        };
        const renderHourlyBreakdown = () => {
            const eligibleRows = hourlyMetricRows;
            const tableWorstHour = eligibleRows
                .filter(row => row.spend > 1000 && row.clicks >= 5)
                .sort((a, b) => (a.cvr - b.cvr) || (a.orders - b.orders) || (b.clicks - a.clicks) || (a.roas - b.roas))[0];
            const activeMetricRows = eligibleRows.filter(row => row.impressions > 0 || row.clicks > 0 || row.orders > 0 || row.spend > 0 || row.gmv > 0);
            const bestCtrHour = activeMetricRows
                .filter(row => row.impressions > 0)
                .sort((a, b) => (b.ctr - a.ctr) || (b.clicks - a.clicks) || (b.orders - a.orders))[0];
            const bestGrossProfitHour = activeMetricRows
                .filter(row => row.spend > 0 || row.gmv > 0)
                .sort((a, b) => (b.grossProfit - a.grossProfit) || (b.gmv - a.gmv))[0];
            const breakdownRows = [...eligibleRows]
                .sort((a, b) => {
                    const aValue = a[hourlySortKey];
                    const bValue = b[hourlySortKey];
                    const comparison = typeof aValue === 'string'
                        ? aValue.localeCompare(bValue)
                        : aValue - bValue;
                    return comparison * (hourlySortDirection === 'asc' ? 1 : -1);
                });

            hourlyBreakdownEl.innerHTML = breakdownRows.length
                ? `<table class="table table-sm table-hover align-middle mb-0" style="font-size:.68rem;">
                    <thead><tr>${sortableHeader('hour', 'Jam')}${sortableHeader('roas', 'ROAS', 'text-end')}${sortableHeader('impressions', 'Impresi', 'text-end')}${sortableHeader('clicks', 'Klik', 'text-end')}${sortableHeader('ctr', 'CTR', 'text-end')}${sortableHeader('cvr', 'CVR', 'text-end')}${sortableHeader('orders', 'Orders', 'text-end')}${sortableHeader('spend', 'Biaya', 'text-end')}${sortableHeader('gmv', 'GMV', 'text-end')}${sortableHeader('aov', 'AOV', 'text-end')}${sortableHeader('grossProfit', 'Gross Profit', 'text-end')}</tr></thead>
                    <tbody>${breakdownRows.map((row, index) => {
                        const isWorst = tableWorstHour && row.hour === tableWorstHour.hour;
                        const isLoss = row.grossProfit < 0;
                        const isBestCtr = bestCtrHour && row.hour === bestCtrHour.hour;
                        const isBestGrossProfit = bestGrossProfitHour && row.hour === bestGrossProfitHour.hour;
                        const rowStyle = isLoss
                            ? ' style="background:rgba(220, 38, 38, 0.07);"'
                            : (index === 0 ? ' style="background:rgba(245, 158, 11, 0.08);"' : '');
                        const rowIcon = (isWorst ? '<i class="bi bi-exclamation-triangle-fill text-danger me-1" title="CVR terendah"></i>' : '')
                            + (isBestCtr ? '<i class="bi bi-bullseye text-primary me-1" title="CTR tertinggi"></i>' : '')
                            + (isBestGrossProfit ? '<i class="bi bi-cash-coin text-success me-1" title="Gross Profit tertinggi"></i>' : '')
                            + (!isWorst && !isBestCtr && !isBestGrossProfit && index === 0 ? '<i class="bi bi-trophy-fill text-warning me-1" title="Peringkat teratas"></i>' : '');
                        const roasCellStyle = ' style="background:rgba(245, 158, 11, 0.05);"';
                        const ctrCellStyle = isBestCtr ? ' style="background:rgba(59, 130, 246, 0.16);"' : ' style="background:rgba(59, 130, 246, 0.04);"';
                        const cvrCellStyle = ' style="background:rgba(139, 92, 246, 0.05);"';
                        const grossProfitCellStyle = isBestGrossProfit
                            ? ' style="background:rgba(22, 163, 74, 0.16);"'
                            : (isLoss ? ' style="background:rgba(220, 38, 38, 0.14);"' : ' style="background:rgba(22, 163, 74, 0.04);"');
                        return `<tr${rowStyle} data-hour="${row.hour}" class="hourly-detail-row" role="button" tabindex="0" title="Klik untuk melihat item internal">
                        <td class="fw-bold">${rowIcon}${row.label}</td>
                        <td class="text-end fw-bold"${roasCellStyle}>${row.roas.toFixed(2)}x</td>
                        <td class="text-end">${row.impressions.toLocaleString('id-ID')}</td>
                        <td class="text-end">${row.clicks.toLocaleString('id-ID')}</td>
                        <td class="text-end"${ctrCellStyle}>${row.ctr.toFixed(2)}%</td>
                        <td class="text-end fw-bold"${cvrCellStyle}>${row.cvr.toFixed(2)}%</td>
                        <td class="text-end">${row.orders.toLocaleString('id-ID')}</td>
                        <td class="text-end text-danger">Rp ${formatShortIDR(row.spend)}</td>
                        <td class="text-end text-success">Rp ${formatShortIDR(row.gmv)}</td>
                        <td class="text-end">Rp ${formatShortIDR(row.aov)}</td>
                        <td class="text-end fw-bold ${isLoss ? 'text-danger' : 'text-success'}"${grossProfitCellStyle} title="${isLoss ? 'Gross Profit negatif' : 'Gross Profit positif'}">${row.grossProfit < 0 ? '−' : ''}Rp ${formatShortIDR(Math.abs(row.grossProfit))}</td>
                    </tr>`;
                    }).join('')}</tbody>
                </table>`
                : '<div style="font-size:.72rem; color:var(--dsh-muted); padding:.6rem 0;">Belum ada aktivitas per jam pada periode ini.</div>';

            if (breakdownRows.length) {
                hourlyBreakdownEl.innerHTML += `<div style="font-size:.64rem; color:var(--dsh-muted); margin-top:.45rem;">
                    <span style="color:#64748b;"><i class="bi bi-info-circle"></i> Prioritas: ROAS · CTR · CVR · Gross Profit</span>
                    <span class="ms-2 text-primary"><i class="bi bi-bullseye"></i> CTR tertinggi</span>
                    <span class="ms-2 text-success"><i class="bi bi-cash-coin"></i> Gross Profit tertinggi</span>
                    <span class="ms-2 text-danger"><i class="bi bi-exclamation-triangle-fill"></i> CVR terendah</span>
                </div>`;
            }

            hourlyBreakdownEl.querySelectorAll('[data-hourly-sort]').forEach(header => {
                const sortByHeader = () => {
                    const nextKey = header.dataset.hourlySort;
                    if (hourlySortKey === nextKey) {
                        hourlySortDirection = hourlySortDirection === 'asc' ? 'desc' : 'asc';
                    } else {
                        hourlySortKey = nextKey;
                        hourlySortDirection = nextKey === 'hour' ? 'asc' : 'desc';
                    }
                    renderHourlyBreakdown();
                };
                header.addEventListener('click', sortByHeader);
                header.addEventListener('keydown', event => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        sortByHeader();
                    }
                });
            });
            hourlyBreakdownEl.querySelectorAll('.hourly-detail-row').forEach(row => {
                const openDetail = () => showHourlyItemDetail(parseInt(row.dataset.hour, 10));
                row.addEventListener('click', openDetail);
                row.addEventListener('keydown', event => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        openDetail();
                    }
                });
            });
        };
        renderHourlyBreakdown();
    }

    let totalDailyImpressions = dailyData.reduce((sum, d) => sum + parseInt(d.impressions || 0), 0);
    let totalDailyClicks = dailyData.reduce((sum, d) => sum + parseInt(d.clicks || 0), 0);
    let avgDailyCtr = totalDailyImpressions > 0 ? ((totalDailyClicks / totalDailyImpressions) * 100).toFixed(2) : "0.00";
    let tsEl = document.getElementById('trafficSummary');
    if(tsEl) {
        tsEl.innerHTML = `<span style="color:#f59e0b">${formatShortIDR(totalDailyImpressions)} Jangkauan</span> &bull; <span style="color:#3b82f6">${formatShortIDR(totalDailyClicks)} Klik</span> &bull; <span style="color:#8b5cf6">${avgDailyCtr}% CTR</span>`;
    }

    // --- AI INSIGHTS GENERATOR (ULTRA SMART EDITION) ---
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
        const hourlyCandidates = hourlyMetricRows
            .filter(row => row.spend > 1000 && row.clicks >= 5);
        const goldenHourCandidates = hourlyCandidates
            .filter(row => row.gmv > (totalHourlyGmv * 0.05))
            .sort((a, b) => (b.cvr - a.cvr) || (b.clicks - a.clicks) || (b.orders - a.orders) || (b.roas - a.roas));
        const bestHour = goldenHourCandidates[0];
        const worstHour = [...hourlyCandidates]
            .filter(row => !bestHour || row.hour !== bestHour.hour)
            .sort((a, b) => (a.cvr - b.cvr) || (a.orders - b.orders) || (b.clicks - a.clicks) || (a.roas - b.roas))[0];

        if (bestHour) {
            let gmvPct = totalHourlyGmv > 0 ? ((bestHour.gmv / totalHourlyGmv) * 100).toFixed(0) : '0';
            let outsideGmvPct = Math.max(0, 100 - Number(gmvPct));
            let worstGmvPct = totalHourlyGmv > 0 ? ((worstHour.gmv / totalHourlyGmv) * 100).toFixed(0) : '0';
            let bestHourEnd = String((bestHour.hour + 1) % 24).padStart(2, '0');
            let worstHourEnd = worstHour ? String((worstHour.hour + 1) % 24).padStart(2, '0') : '00';
            timeEl.innerHTML = `<div style="font-weight: 700; color: #d97706; font-size: 0.85rem; margin-bottom: 0.3rem;">⏳ Waktu Emas (Dayparting)</div>
                                <div style="font-size: 0.72rem; color: var(--dsh-muted);">Jam <b>${String(bestHour.hour).padStart(2, '0')}:00 - ${bestHourEnd}:00</b> memiliki CVR tertinggi <b>${bestHour.cvr.toFixed(2)}%</b>. Kontribusi GMV: <b>${gmvPct}%</b> di waktu emas dan <b>${outsideGmvPct}%</b> di luar waktu emas.</div>
                                ${worstHour ? `<div style="font-size: 0.72rem; color: #dc2626; margin-top: 0.25rem;">⚠️ Waktu terburuk: <b>${String(worstHour.hour).padStart(2, '0')}:00 - ${worstHourEnd}:00</b> dengan CVR <b>${worstHour.cvr.toFixed(2)}%</b> dan kontribusi GMV <b>${worstGmvPct}%</b>. 💡 <b>Saran:</b> Evaluasi atau kurangi budget di jam ini.</div>` : ''}`;
            timeEl.style.borderLeftColor = '#d97706';
        } else if (worstHour) {
            let worstGmvPct = totalHourlyGmv > 0 ? ((worstHour.gmv / totalHourlyGmv) * 100).toFixed(0) : '0';
            let worstHourEnd = String((worstHour.hour + 1) % 24).padStart(2, '0');
            timeEl.innerHTML = `<div style="font-weight: 700; color: #dc2626; font-size: 0.85rem; margin-bottom: 0.3rem;">⚠️ Waktu Terburuk</div>
                                <div style="font-size: 0.72rem; color: var(--dsh-muted);">Jam <b>${String(worstHour.hour).padStart(2, '0')}:00 - ${worstHourEnd}:00</b> memiliki CVR terendah <b>${worstHour.cvr.toFixed(2)}%</b> dengan kontribusi GMV <b>${worstGmvPct}%</b>. Evaluasi budget dan materi iklan pada jam ini.</div>`;
            timeEl.style.borderLeftColor = '#dc2626';
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

        let gradientGrossProfit = ctxDaily2D.createLinearGradient(0, 0, 0, 300);
        gradientGrossProfit.addColorStop(0, 'rgba(124, 58, 237, 0.18)');
        gradientGrossProfit.addColorStop(1, 'rgba(124, 58, 237, 0.0)');

        new Chart(ctxDaily2D, {
            type: 'line',
            data: {
                labels: dailyData.map(d => {
                    // ubah format "2026-07-22" jadi "22 Jul"
                    const date = parseLocalDate(d.date);
                    return date.getDate() + ' ' + date.toLocaleString('id-ID', { month: 'short' });
                }),
                datasets: [
                    {
                        label: 'ROAS',
                        data: dailyData.map(d => {
                let sp = parseFloat(d.spend || 0) * ADS_COST_MULTIPLIER;
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
                        data: dailyData.map(d => parseFloat(d.spend || 0) * ADS_COST_MULTIPLIER),
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
                    },
                    {
                        label: 'Gross Profit',
                        data: dailyData.map(d => d.gross_profit === null || d.gross_profit === undefined ? null : parseFloat(d.gross_profit || 0)),
                        borderColor: '#7c3aed',
                        backgroundColor: gradientGrossProfit,
                        fill: false,
                        tension: 0.35,
                        borderWidth: 2,
                        borderDash: [5, 3],
                        pointRadius: dailyData.length <= 1 ? 5 : 0,
                        pointHitRadius: 15,
                        pointHoverRadius: 4,
                        pointHoverBackgroundColor: '#7c3aed',
                        spanGaps: true,
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
                            },
                            afterBody: function(items) {
                                const point = items && items[0];
                                if (!point) return [];
                                const day = dailyData[point.dataIndex] || {};
                                const orders = parseInt(day.orders || 0);
                                const aov = orders > 0 ? (parseFloat(day.gmv || 0) / orders) : 0;
                                const grossProfit = day.gross_profit === null || day.gross_profit === undefined
                                    ? 'N/A'
                                    : formatFullIDR(parseFloat(day.gross_profit || 0));
                                return ['AOV: ' + formatFullIDR(aov), 'Gross Profit: ' + grossProfit];
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
                            let sp = parseFloat(d.expense || 0) * ADS_COST_MULTIPLIER;
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
                        data: hourlyData.map(d => parseFloat(d.expense || 0) * ADS_COST_MULTIPLIER),
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
                onClick: function(event, elements) {
                    const point = elements && elements[0];
                    const row = point ? hourlyMetricRows[point.index] : null;
                    if (row) showHourlyItemDetail(row.hour);
                },
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
                            },
                            afterBody: function(items) {
                                const row = hourlyMetricRows[items[0]?.dataIndex];
                                if (!row) return [];
                                return [
                                    'Breakdown ' + row.label,
                                    'Impresi: ' + row.impressions.toLocaleString('id-ID'),
                                    'Klik: ' + row.clicks.toLocaleString('id-ID'),
                                    'CTR: ' + row.ctr.toFixed(2) + '%',
                                    'Orders: ' + row.orders.toLocaleString('id-ID'),
                                    'AOV: ' + formatFullIDR(row.aov),
                                    'Gross Profit: ' + formatFullIDR(row.grossProfit) + ' (sebelum HPP)',
                                    'CVR: ' + row.cvr.toFixed(2) + '%',
                                    'ROAS: ' + row.roas.toFixed(2) + 'x',
                                ];
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
                    const date = parseLocalDate(d.date);
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
                        label: 'Jangkauan',
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


    // 6. PROFIT CHARTS
    const ctxProfitComp = document.getElementById('chartProfitComposition');
    const ctxProfitTrend = document.getElementById('chartProfitTrend');
    
    if (ctxProfitComp && window.__profitChartData) {
        const pd = window.__profitChartData;
        const hasProfit = pd.totalProfit >= 0;
        const pNet = Math.abs(pd.totalProfit || 0);
        const compositionValues = [pd.totalCogs, pd.feeAmt, pd.totalTopup, pNet].map(value => Number(value) || 0);
        const compositionTotal = compositionValues.reduce((sum, value) => sum + value, 0);
        const compactComposition = window.matchMedia('(max-width: 991.98px)').matches;
        const compositionNames = compactComposition
            ? ['HPP', 'Fee', 'Iklan', hasProfit ? 'Laba' : 'Rugi']
            : ['HPP Produk', 'Fee Marketplace', 'Iklan + PPN', hasProfit ? 'Laba Bersih' : 'Rugi Bersih'];
        const compositionPercentages = compositionNames.map((name, index) => {
            const percentage = index === 1
                ? Number(pd.feePct || 0).toFixed(1).replace('.', ',')
                : (compositionTotal > 0 ? ((compositionValues[index] / compositionTotal) * 100).toFixed(1).replace('.', ',') : '0,0');
            return percentage;
        });
        const formatCompositionIDR = value => 'Rp ' + Math.round(Math.abs(Number(value) || 0)).toLocaleString('id-ID');
        const compositionPercentPlugin = {
            id: 'profitCompositionCenterText',
            afterDatasetsDraw(chart) {
                const ctx = chart.ctx;
                const arc = chart.getDatasetMeta(0).data[0];
                if (!arc) return;
                ctx.save();
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillStyle = tooltipText;
                ctx.font = '700 9px Inter, sans-serif';
                ctx.fillText(compactComposition ? (hasProfit ? 'Laba' : 'Rugi') : (hasProfit ? 'Laba Bersih' : 'Rugi Bersih'), arc.x, arc.y - 7);
                ctx.font = '800 11px Inter, sans-serif';
                ctx.fillText((hasProfit ? '' : '−') + formatCompositionIDR(pd.totalProfit), arc.x, arc.y + 8);
                ctx.restore();
            }
        };

        const legend = document.getElementById('profitCompositionLegend');
        if (legend) {
            const colors = ['#94a3b8', '#f59e0b', '#dc2626', hasProfit ? '#10b981' : '#7f1d1d'];
            legend.innerHTML = compositionNames.map((name, index) =>
                '<div class="profit-composition-item">' +
                    '<span class="profit-composition-dot" style="background:' + colors[index] + '"></span>' +
                    '<span class="profit-composition-name">' + name + '</span>' +
                    '<span class="profit-composition-value">' + formatCompositionIDR(compositionValues[index]) + ' · ' + compositionPercentages[index] + '%</span>' +
                '</div>'
            ).join('');
        }
        
        new Chart(ctxProfitComp.getContext('2d'), {
            type: 'doughnut',
            plugins: [compositionPercentPlugin],
            data: {
                labels: compositionNames,
                datasets: [{
                    data: compositionValues,
                    backgroundColor: ['#94a3b8', '#f59e0b', '#dc2626', hasProfit ? '#10b981' : '#7f1d1d'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: tooltipBg,
                        titleColor: tooltipText,
                        bodyColor: tooltipText,
                        borderColor: tooltipBorder,
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + formatShortIDR(context.parsed);
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });
    }

    if (ctxProfitTrend && dailyData.length > 0 && window.__profitChartData) {
        const pd = window.__profitChartData;
        // Gunakan rata-rata rasio margin global untuk memetakan tren harian (estimasi karena data harian belum memuat cogs per item)
        const globalCogsRatio = pd.totalRev > 0 ? (pd.totalCogs / pd.totalRev) : 0;
        const globalFeeRatio = pd.totalRev > 0 ? (pd.feeAmt / pd.totalRev) : 0;
        
        new Chart(ctxProfitTrend.getContext('2d'), {
            type: 'bar',
            data: {
                labels: dailyData.map(d => formatIndoDate(d.date)),
                datasets: [
                    {
                        label: 'Revenue',
                        data: dailyData.map(d => parseFloat(d.gmv) || 0),
                        backgroundColor: 'rgba(59, 130, 246, 0.2)',
                        borderColor: 'rgba(59, 130, 246, 0.5)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Net Profit (Est)',
                        data: dailyData.map(d => {
                            let rev = parseFloat(d.gmv) || 0;
                            let spd = parseFloat(d.spend) || 0;
                            let estCogs = rev * globalCogsRatio;
                            let estFee = rev * globalFeeRatio;
                            return rev - estCogs - estFee - (spd * 1.11);
                        }),
                        type: 'line',
                        borderColor: '#10b981',
                        backgroundColor: '#10b981',
                        borderWidth: 2,
                        tension: 0.3,
                        pointRadius: 2,
                        yAxisID: 'y'
                    },
                    {
                        label: 'ROAS',
                        data: dailyData.map(d => {
                            const spend = parseFloat(d.spend) || 0;
                            const gmv = parseFloat(d.gmv) || 0;
                            return spend > 0 ? parseFloat((gmv / spend).toFixed(2)) : 0;
                        }),
                        type: 'line',
                        borderColor: '#eab308',
                        backgroundColor: '#eab308',
                        borderWidth: 2,
                        tension: 0.3,
                        pointRadius: 2,
                        pointHoverRadius: 4,
                        yAxisID: 'y1'
                    },
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: true, position: 'bottom', labels: { boxWidth: 12, font: {size: 10} } },
                    tooltip: {
                        backgroundColor: tooltipBg,
                        titleColor: tooltipText,
                        bodyColor: tooltipText,
                        borderColor: tooltipBorder,
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                const label = context.dataset.label || '';
                                return context.dataset.yAxisID === 'y1'
                                    ? label + ': ' + Number(context.parsed.y || 0).toFixed(2) + 'x'
                                    : label + ': ' + formatFullIDR(context.parsed.y || 0);
                            },
                            afterBody: function(items) {
                                const point = items && items[0];
                                if (!point) return [];
                                const day = dailyData[point.dataIndex] || {};
                                const orders = parseInt(day.orders || 0);
                                const aov = orders > 0 ? (parseFloat(day.gmv || 0) / orders) : 0;
                                return ['AOV: ' + formatFullIDR(aov)];
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { maxRotation: 45, font: {size: 9} } },
                    y: { type: 'linear', display: true, position: 'left', grid: { color: gridColor }, title: { display: false }, ticks: { callback: value => formatShortIDR(value) } },
                    y1: { type: 'linear', display: true, position: 'right', beginAtZero: true, grid: { drawOnChartArea: false }, ticks: { callback: value => value + 'x' } }
                }
            }
        });
    }

    // 5. TRAFFIC CHARTS
    const ctxTrafficVol = document.getElementById('chartTrafficVolume');
    const ctxTrafficRates = document.getElementById('chartTrafficRates');
    
    if (ctxTrafficVol && dailyData.length > 0) {
        new Chart(ctxTrafficVol.getContext('2d'), {
            type: 'bar',
            data: {
                labels: dailyData.map(d => formatIndoDate(d.date)),
                datasets: [
                    {
                        label: 'Jangkauan',
                        data: dailyData.map(d => parseInt(d.impressions) || 0),
                        backgroundColor: 'rgba(59, 130, 246, 0.7)',
                        borderRadius: 4,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Clicks',
                        data: dailyData.map(d => parseInt(d.clicks) || 0),
                        type: 'line',
                        borderColor: '#f59e0b',
                        backgroundColor: '#f59e0b',
                        borderWidth: 2,
                        tension: 0.3,
                        pointRadius: 2,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: true, position: 'bottom', labels: { boxWidth: 12, font: {size: 10} } },
                    tooltip: { backgroundColor: tooltipBg, titleColor: tooltipText, bodyColor: tooltipText, borderColor: tooltipBorder, borderWidth: 1 }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { maxRotation: 45, font: {size: 9} } },
                    y: { type: 'linear', display: true, position: 'left', grid: { color: gridColor }, title: { display: true, text: 'Jangkauan', font: {size:10} } },
                    y1: { type: 'linear', display: true, position: 'right', grid: { display: false }, title: { display: true, text: 'Clicks', font: {size:10} } }
                }
            }
        });
    }

    if (ctxTrafficRates && dailyData.length > 0) {
        new Chart(ctxTrafficRates.getContext('2d'), {
            type: 'line',
            data: {
                labels: dailyData.map(d => formatIndoDate(d.date)),
                datasets: [
                    {
                        label: 'CTR (%)',
                        data: dailyData.map(d => {
                            let imp = parseInt(d.impressions) || 0;
                            let clk = parseInt(d.clicks) || 0;
                            return imp > 0 ? (clk/imp*100).toFixed(2) : 0;
                        }),
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true,
                        pointRadius: 0,
                        yAxisID: 'y'
                    },
                    {
                        label: 'CVR (%)',
                        data: dailyData.map(d => {
                            let clk = parseInt(d.clicks) || 0;
                            let ord = parseInt(d.orders) || 0;
                            return clk > 0 ? (ord/clk*100).toFixed(2) : 0;
                        }),
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
                        borderWidth: 2,
                        borderDash: [5,5],
                        tension: 0.3,
                        pointRadius: 0,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: true, position: 'bottom', labels: { boxWidth: 12, font: {size: 10} } },
                    tooltip: {
                        backgroundColor: tooltipBg,
                        titleColor: tooltipText,
                        bodyColor: tooltipText,
                        borderColor: tooltipBorder,
                        borderWidth: 1,
                        callbacks: {
                            afterBody: function(tooltipItems) {
                                const point = tooltipItems[0];
                                const day = dailyData[point.dataIndex] || {};
                                const orders = parseInt(day.orders || 0);
                                const gmv = parseFloat(day.gmv || 0);
                                const aov = orders > 0 ? gmv / orders : 0;
                                return [
                                    'Orders: ' + orders.toLocaleString('id-ID'),
                                    'AOV: ' + formatFullIDR(aov),
                                ];
                            },
                        },
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { maxRotation: 45, font: {size: 9} } },
                    y: { type: 'linear', display: true, position: 'left', grid: { color: gridColor }, title: { display: true, text: 'CTR (%)', font: {size:10} } },
                    y1: { type: 'linear', display: true, position: 'right', grid: { display: false }, title: { display: true, text: 'CVR (%)', font: {size:10} }, ticks: { callback: value => value + '%' } }
                }
            }
        });
    }

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
                const dStartHist = fromElHist && fromElHist.value ? parseLocalDate(fromElHist.value) : new Date();
                const dEndHist = toElHist && toElHist.value ? parseLocalDate(toElHist.value) : new Date();
                if (dStartHist && dEndHist) {
                    maxDays = Math.round((dEndHist - dStartHist) / (1000 * 60 * 60 * 24)) + 1;
                }
                if (maxDays < 1) maxDays = 1;
                
                const formatHistDate = (value, withYear = false) => {
                    const date = parseLocalDate(value);
                    if (!date) return '-';
                    return date.toLocaleDateString('id-ID', withYear
                        ? { day: '2-digit', month: 'short', year: 'numeric' }
                        : { day: '2-digit', month: 'short' });
                };
                const formatHistRange = (period) => {
                    if (!period || !period.start || !period.end) return '-';
                    return formatHistDate(period.start, true) + ' – ' + formatHistDate(period.end, true);
                };
                const histPeriodLabel = document.getElementById('histPeriodLabel');
                if (histPeriodLabel && rawHistorical.length > 0) {
                    histPeriodLabel.innerHTML = '<span class="badge bg-primary-subtle text-primary border">Aktif: ' + formatHistRange(rawHistorical[0]) + '</span>'
                        + '<span>vs</span>'
                        + '<span class="badge bg-light text-dark border">Pembanding: ' + formatHistRange(rawHistorical[1]) + '</span>'
                        + (rawHistorical[2] ? '<span>·</span><span class="badge bg-light text-dark border">2 lalu: ' + formatHistRange(rawHistorical[2]) + '</span>' : '');
                }

                // Generate X-Axis dengan tanggal aktual periode aktif.
                let histLabels = [];
                for (let i = 1; i <= maxDays; i++) {
                    const axisDate = new Date(dStartHist);
                    axisDate.setDate(axisDate.getDate() + i - 1);
                    histLabels.push(formatHistDate(axisDate));
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
                    } else if (metric === 'aov') {
                        return orders > 0 ? gm / orders : 0;
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
                    } else if (metric === 'profit') {
                        // Profit per hari memakai omzet bersih setelah admin.
                        // Backend mengirim net_revenue historis per toko/tanggal;
                        // fallback hanya dipakai untuk data lama yang belum punya
                        // field tersebut.
                        const pd = window.__profitChartData || {};
                        const rev = parseFloat(pd.totalRev || 0);
                        const cogsRatio = rev > 0 ? (parseFloat(pd.totalCogs || 0) / rev) : 0;
                        const feeRatio  = rev > 0 ? (parseFloat(pd.feeAmt || 0) / rev) : 0;
                        const netRevenue = d.net_revenue !== undefined
                            ? parseFloat(d.net_revenue || 0)
                            : gm - (gm * feeRatio);
                        return netRevenue - (gm * cogsRatio) - (sp * 1.11);
                    }
                    return 0;
                };

                const renderHistoricalSummaryTable = () => {
                const historicalSummaryTable = document.getElementById('historicalSummaryTable');
                if (historicalSummaryTable) {
                    const aggregateHistoricalPeriod = (period) => {
                        const rows = period?.data || [];
                        const spend = rows.reduce((sum, d) => sum + (parseFloat(d.spend || 0) * ADS_COST_MULTIPLIER), 0);
                        const gmv = rows.reduce((sum, d) => sum + parseFloat(d.gmv || 0), 0);
                        const impressions = rows.reduce((sum, d) => sum + parseInt(d.impressions || 0), 0);
                        const clicks = rows.reduce((sum, d) => sum + parseInt(d.clicks || 0), 0);
                        const orders = rows.reduce((sum, d) => sum + parseInt(d.orders || 0), 0);
                        const profit = rows.reduce((sum, d) => sum + getMetricValue(d, 'profit'), 0);

                        return {
                            roas: spend > 0 ? gmv / spend : 0,
                            aov: orders > 0 ? gmv / orders : 0,
                            gmv,
                            spend,
                            impressions,
                            clicks,
                            ctr: impressions > 0 ? (clicks / impressions) * 100 : 0,
                            cvr: clicks > 0 ? (orders / clicks) * 100 : 0,
                            orders,
                            profit,
                        };
                    };
                    const currentSummary = aggregateHistoricalPeriod(rawHistorical[0]);
                    const previousSummary = aggregateHistoricalPeriod(rawHistorical[1]);
                    const twoPeriodsAgoSummary = aggregateHistoricalPeriod(rawHistorical[2]);
                    const previousLabel = historicalCompareMode === 'prev_month' ? '1 Bulan Lalu' : (historicalCompareMode === 'prev_year' ? '1 Tahun Lalu' : '1 Periode Lalu');
                    const twoPeriodsAgoLabel = historicalCompareMode === 'prev_month' ? '2 Bulan Lalu' : (historicalCompareMode === 'prev_year' ? '2 Tahun Lalu' : '2 Periode Lalu');
                    const summaryRows = [
                        ['roas', 'ROAS', 'ratio'],
                        ['gmv', 'GMV', 'money'],
                        ['aov', 'AOV', 'money'],
                        ['spend', 'Biaya iklan', 'money'],
                        ['impressions', 'Impresi', 'count'],
                        ['clicks', 'Klik', 'count'],
                        ['ctr', 'CTR', 'percent'],
                        ['cvr', 'CVR', 'percent'],
                        ['orders', 'Orders', 'count'],
                        ['profit', 'Profit setelah admin', 'money'],
                    ];
                    const formatSummaryValue = (value, type) => {
                        if (type === 'money') {
                            const negative = value < 0;
                            return (negative ? '−' : '') + 'Rp ' + formatShortIDR(Math.abs(value));
                        }
                        if (type === 'ratio') return value.toFixed(2) + 'x';
                        if (type === 'percent') return value.toFixed(2) + '%';
                        return Math.round(value).toLocaleString('id-ID');
                    };
                    const formatSummaryChange = (current, previous) => {
                        if (previous === 0) return current > 0 ? 'Baru' : '—';
                        const change = ((current - previous) / Math.abs(previous)) * 100;
                        return (change >= 0 ? '↑ ' : '↓ ') + Math.abs(change).toFixed(1) + '%';
                    };
                    const summaryChangeClass = (current, previous) => {
                        if (previous === 0 || current === previous) return 'text-muted';
                        return current > previous ? 'text-success' : 'text-danger';
                    };
                    const formatSummaryComparison = (current, previous) => previous === 0
                        ? (current > 0 ? 'Baru' : '—')
                        : ((current >= previous ? '↑ ' : '↓ ') + Math.abs(((current - previous) / Math.abs(previous)) * 100).toFixed(1) + '%');

                    historicalSummaryTable.innerHTML = `<table class="table table-sm table-hover align-middle mb-0" style="font-size:.7rem;">
                        <thead><tr>
                            <th>Metrik</th>
                            <th class="text-end">Aktif</th>
                            <th class="text-end">${previousLabel}</th>
                            <th class="text-end">${twoPeriodsAgoLabel}</th>
                            <th class="text-end">vs ${previousLabel}</th>
                            <th class="text-end">vs ${twoPeriodsAgoLabel}</th>
                        </tr></thead>
                        <tbody>${summaryRows.map(([key, label, type]) => {
                            const current = currentSummary[key] || 0;
                            const previous = previousSummary[key] || 0;
                            const twoPeriodsAgo = twoPeriodsAgoSummary[key] || 0;
                            return `<tr>
                                <td class="fw-semibold">${label}</td>
                                <td class="text-end fw-semibold">${formatSummaryValue(current, type)}</td>
                                <td class="text-end text-muted">${formatSummaryValue(previous, type)}</td>
                                <td class="text-end text-muted">${rawHistorical[2] ? formatSummaryValue(twoPeriodsAgo, type) : '—'}</td>
                                <td class="text-end fw-semibold ${summaryChangeClass(current, previous)}">${formatSummaryChange(current, previous)}</td>
                                <td class="text-end fw-semibold ${rawHistorical[2] ? summaryChangeClass(current, twoPeriodsAgo) : 'text-muted'}">${rawHistorical[2] ? formatSummaryComparison(current, twoPeriodsAgo) : '—'}</td>
                            </tr>`;
                        }).join('')}</tbody>
                    </table>`;
                }
                };
                renderHistoricalSummaryTable();

                const renderHistChart = (metric) => {
                    let datasets = rawHistorical.map((period, idx) => {
                        let dataPoints = new Array(maxDays).fill(null);
                        
                        // Align data to specific day offset
                        if (period.start) {
                            const pStart = parseLocalDate(period.start);
                            period.data.forEach(d => {
                                if (d.date) {
                                    const pDate = parseLocalDate(d.date);
                                    const dayOffset = Math.round((pDate - pStart) / (1000 * 60 * 60 * 24));
                                    if (dayOffset >= 0 && dayOffset < maxDays) {
                                        dataPoints[dayOffset] = getMetricValue(d, metric);
                                    }
                                }
                            });
                        }
                        
                        let label = idx === 0 ? 'Rentang Saat Ini' : `${idx} Rentang Lalu`;
                        if (idx === 1) {
                            label = historicalCompareMode === 'prev_month'
                                ? '1 Bulan Lalu'
                                : (historicalCompareMode === 'prev_year' ? '1 Tahun Lalu' : '1 Periode Lalu');
                        } else if (idx === 2) {
                            label = historicalCompareMode === 'prev_month'
                                ? '2 Bulan Lalu'
                                : (historicalCompareMode === 'prev_year' ? '2 Tahun Lalu' : '2 Periode Lalu');
                        }

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
                                                    let neg = val < 0; let a = Math.abs(val); let str;
                                                    if(a >= 1000000) str = (a/1000000).toFixed(1) + ' Jt';
                                                    else if(a >= 1000) str = (a/1000).toFixed(1) + ' Rb';
                                                    else str = a.toFixed(0);
                                                    return ctx.dataset.label + ': ' + (neg ? '−' : '') + 'Rp ' + str;
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
                                                return (value < 0 ? '−' : '') + 'Rp ' + formatShortIDR(Math.abs(value));
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
                            return (v < 0 ? '−' : '') + 'Rp ' + formatShortIDR(Math.abs(v));
                        };

                        let isAvg = (metric === 'roas' || metric === 'aov' || metric === 'cvr' || metric === 'ctr');
                        const currentRange = formatHistRange(rawHistorical[0]);
                        const previousRange = formatHistRange(rawHistorical[1]);
                        html += `<div style="margin-bottom:2px;">Aktif <span style="font-size:.68rem;color:var(--dsh-muted);">(${currentRange})</span>: <span style="color:var(--dsh-accent)">${isAvg ? 'Rata-rata' : 'Total'} ${formatVal(isAvg ? currAvg : currSum)}</span></div>`;
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

                            html += `<div style="font-size:0.75rem; color:var(--dsh-muted)">Pembanding <span style="font-size:.68rem;">(${previousRange})</span>: ${formatVal(isAvg ? prevAvg : prevSum)}
                                     <span style="color:${color}; font-weight:bold; margin-left:5px;">(${sign}${diff.toFixed(1)}%)</span></div>`;
                        }
                        
                        summaryEl.innerHTML = html;
                    }
                };

                const updateHistoricalPeriodLabels = () => {
                    const histPeriodLabel = document.getElementById('histPeriodLabel');
                    if (!histPeriodLabel || rawHistorical.length === 0) return;
                    histPeriodLabel.innerHTML = '<span class="badge bg-primary-subtle text-primary border">Aktif: ' + formatHistRange(rawHistorical[0]) + '</span>'
                        + '<span>vs</span>'
                        + '<span class="badge bg-light text-dark border">Pembanding: ' + formatHistRange(rawHistorical[1]) + '</span>'
                        + (rawHistorical[2] ? '<span>·</span><span class="badge bg-light text-dark border">2 lalu: ' + formatHistRange(rawHistorical[2]) + '</span>' : '');
                };

                window.updateHistoricalComparison = async function (mode) {
                    const routes = window.AdsDashboardRoutes || {};
                    const selectedStore = document.querySelector('select[name="store_id"]')?.value || 'all';
                    const dateFrom = document.getElementById('fromHidden')?.value || '';
                    const dateTo = document.getElementById('toHidden')?.value || '';
                    const url = new URL(routes.historicalComparison, window.location.origin);
                    url.searchParams.set('store_id', selectedStore);
                    url.searchParams.set('date_from', dateFrom);
                    url.searchParams.set('date_to', dateTo);
                    url.searchParams.set('compare_mode', mode);

                    try {
                        const response = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
                        const payload = await response.json();
                        if (!response.ok) throw new Error(payload.message || 'Gagal memuat perbandingan periode.');
                        rawHistorical = payload.data || [];
                        historicalCompareMode = payload.compare_mode || mode;
                        const pageUrl = new URL(window.location.href);
                        pageUrl.searchParams.set('compare_mode', historicalCompareMode);
                        window.history.replaceState({}, '', pageUrl.toString());
                        updateHistoricalPeriodLabels();
                        renderHistoricalSummaryTable();
                        renderHistChart(document.getElementById('histMetricSelect')?.value || 'roas');
                    } catch (error) {
                        window.showToast?.(error.message || 'Gagal memuat perbandingan periode.');
                    } finally {
                        const status = document.getElementById('compareModeStatus');
                        const select = document.getElementById('compareModeSelect');
                        const tabs = document.querySelectorAll('#compareModeTabs [data-compare-mode]');
                        if (status) status.style.display = 'none';
                        if (select) select.disabled = false;
                        tabs.forEach(tab => { tab.disabled = false; });
                        const summaryTable = document.getElementById('historicalSummaryTable');
                        if (summaryTable) summaryTable.style.opacity = '1';
                        const loadingOverlay = document.getElementById('dashLoadingOverlay');
                        if (loadingOverlay) loadingOverlay.style.display = 'none';
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
            }
        } else if (ctxHist) {
             histContainer.innerHTML = '<div style="text-align:center; padding: 40px; color: var(--dsh-muted);">Data rawHistorical tidak ditemukan atau kosong.</div>';
        }
                
                // ==========================================
                // CHART PRODUK (9 Bar Charts)
                // ==========================================
                const rawItems = @json($itemPerformance->toArray()).map(c => {
                    c.spend = parseFloat(c.spend || 0) * ADS_COST_MULTIPLIER;
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
                            let cause = isImpressionLeak ? "Penyebab utamanya terlihat di <b>Trafik (Kebocoran Jangkauan Ekstrem)</b>; iklan tayang tapi orang malas klik." : "Trafik melemah, kemungkinan karena kalah bersaing harga dengan kompetitor.";
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
                                tfHtml = `<div style="font-weight: 700; color: #dc2626; font-size: 0.8rem; margin-bottom: 0.2rem;">🚨 Kebocoran Jangkauan Ekstrem</div>
                                          <div style="font-size: 0.7rem; color: var(--dsh-muted);">Pada <b>${formatIndoDate(maxImpressionsDay.date)}</b>, ada <b>${parseInt(maxImpressionsDay.impressions).toLocaleString('id-ID')}</b> jangkauan tapi CTR hanya <b>${impCtr.toFixed(2)}%</b>. 🔗 <b>Dampak Finansial:</b> ${impact} 💡 <b>Saran:</b> Iklan tayang tapi diabaikan. Cek harga/thumbnail segera!</div>`;
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
                } else if (insightDailyEl) {
                    let emptyMsg = `<div style="font-weight: 700; color: var(--dsh-muted); font-size: 0.85rem; margin-bottom: 0.3rem;">⏳ Data Tidak Cukup</div>
                                    <div style="font-size: 0.72rem; color: var(--dsh-muted);">Belum ada data harian yang memadai pada periode ini untuk memunculkan AI Insight.</div>`;
                    insightDailyEl.innerHTML = emptyMsg;
                    insightDailyEl.style.borderLeftColor = 'var(--dsh-border)';
                    
                    let itEl = document.getElementById('insightDailyTraffic');
                    if (itEl) {
                        itEl.innerHTML = emptyMsg;
                        itEl.style.borderLeftColor = 'var(--dsh-border)';
                    }
                }
    } catch (err) {
        if (histContainer) {
            histContainer.innerHTML = '<div style="color:#dc2626; padding: 20px; font-family: monospace;"><b>JS Error:</b> ' + err.message + '<br>' + err.stack + '</div>';
        }
    }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const formSync = document.getElementById('formSyncAds');
    if (formSync) {
        formSync.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Sembunyikan modal
            const modalEl = document.getElementById('modalSyncAds');
            if (modalEl && typeof bootstrap !== 'undefined') {
                const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                modalInstance.hide();
            }

            // Tampilkan container loading
            const progressContainer = document.getElementById('liveSyncProgressContainer');
            if (progressContainer) progressContainer.style.display = 'block';

            // Pindah ke tab pengaturan secara otomatis
            if (typeof window.openSyncTab === 'function') {
                window.openSyncTab();
            }
            
            const formData = new FormData(formSync);
            const storeId = formData.get('store_id');
            const tokenMeta = document.querySelector('meta[name="csrf-token"]');
            
            fetch(formSync.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': tokenMeta ? tokenMeta.content : ''
                },
                body: formData
            }).then(res => res.json()).then(data => {
                if(data.status === 'queued') {
                    pollSyncProgress(storeId);
                } else {
                    alert(data.message || 'Gagal memulai sinkronisasi.');
                }
            }).catch(err => {
                alert('Terjadi kesalahan jaringan atau route salah.');
                console.error(err);
            });
        });
    }
});

// Simpan referensi interval polling agar bisa dihentikan saat cancel
let _syncPollInterval = null;
let _syncPollStoreId  = null;

window.__cancelSync = async function (btn) {
    if (!confirm('Batalkan sinkronisasi yang sedang berjalan?')) return;

    const original = btn ? btn.innerHTML : '';
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Membatalkan...'; }

    const cancelRoute = (window.AdsDashboardRoutes || {}).syncCancel || '/marketplace/ads-dashboard/sync-cancel';
    const csrfToken  = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    const storeId    = _syncPollStoreId || 'all';

    try {
        const res  = await fetch(cancelRoute, {
            method : 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body   : JSON.stringify({ store_id: storeId }),
        });
        const data = await res.json().catch(() => ({}));

        // Hentikan polling
        if (_syncPollInterval) { clearInterval(_syncPollInterval); _syncPollInterval = null; }

        // Update UI progress bar → cancelled
        const progressBar    = document.getElementById('liveSyncProgressBar');
        const progressLabel  = document.getElementById('liveSyncLabel');
        const progressPercent= document.getElementById('liveSyncPercent');
        const cancelBtn      = document.getElementById('btnCancelSync');

        if (progressBar)  { progressBar.classList.remove('progress-bar-animated'); progressBar.style.backgroundColor = '#64748b'; }
        if (progressLabel){ progressLabel.innerHTML = '<i class="bi bi-slash-circle me-2" style="color:#64748b"></i> Sinkronisasi dibatalkan.'; }
        if (progressPercent) { progressPercent.textContent = ''; }
        if (cancelBtn)    { cancelBtn.style.display = 'none'; }

        if (typeof window.showToast === 'function') {
            window.showToast(data.message || 'Sinkronisasi dibatalkan.');
        }

        setTimeout(() => window.location.reload(), 2000);
    } catch (err) {
        if (btn) { btn.disabled = false; btn.innerHTML = original; }
        alert('Gagal membatalkan: ' + err.message);
    }
};

function pollSyncProgress(storeId) {
    const progressLabel  = document.getElementById('liveSyncLabel');
    const progressPercent= document.getElementById('liveSyncPercent');
    const progressBar    = document.getElementById('liveSyncProgressBar');
    const cancelBtn      = document.getElementById('btnCancelSync');
    let idleTicks = 0;

    _syncPollStoreId = storeId;

    // Tampilkan tombol batal
    if (cancelBtn) cancelBtn.style.display = 'inline-flex';

    _syncPollInterval = setInterval(() => {
        fetch(`{{ route('marketplace.ads.syncProgress') }}?store_id=${storeId}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'processing' || data.status === 'queued') {
                    progressPercent.textContent = data.percent + '%';
                    progressBar.style.width = data.percent + '%';
                    progressLabel.innerHTML = `<i class="spinner-border spinner-border-sm me-2" role="status"></i> ${data.label || 'Memproses...'}`;
                    idleTicks = 0;
                } else if (data.status === 'success') {
                    clearInterval(_syncPollInterval); _syncPollInterval = null;
                    if (cancelBtn) cancelBtn.style.display = 'none';
                    progressPercent.textContent = '100%';
                    progressBar.style.width = '100%';
                    if (data.stats) {
                        const totalSuccess = (data.stats.inserted || 0) + (data.stats.updated || 0);
                        const totalFail    = data.stats.failed || 0;
                        progressLabel.innerHTML = `Selesai! Berhasil menyimpan ${totalSuccess} data (Gagal: ${totalFail}).`;
                    } else {
                        progressLabel.innerHTML = `Selesai! Semua tahap berhasil.`;
                    }
                    setTimeout(() => finishSuccess(), 2500);
                } else if (data.status === 'error' || data.status === 'cancelled') {
                    clearInterval(_syncPollInterval); _syncPollInterval = null;
                    if (cancelBtn) cancelBtn.style.display = 'none';
                    progressBar.classList.remove('progress-bar-animated');
                    progressBar.style.backgroundColor = data.status === 'cancelled' ? '#64748b' : '#dc2626';
                    const icon = data.status === 'cancelled' ? 'bi-slash-circle' : 'bi-exclamation-triangle-fill';
                    const color= data.status === 'cancelled' ? '#64748b' : '#dc2626';
                    progressLabel.innerHTML = `<i class="bi ${icon} me-2" style="color:${color}"></i> ${data.label || (data.status === 'cancelled' ? 'Dibatalkan.' : 'Gagal.')} `;
                    if (progressPercent) progressPercent.textContent = '';
                    setTimeout(() => window.location.reload(), 2500);
                } else if (data.status === 'partial_success') {
                    clearInterval(_syncPollInterval); _syncPollInterval = null;
                    if (cancelBtn) cancelBtn.style.display = 'none';
                    progressPercent.textContent = '100%';
                    progressBar.style.width = '100%';
                    progressBar.classList.remove('progress-bar-animated');
                    progressBar.style.backgroundColor = '#ca8a04';
                    progressLabel.innerHTML = `<i class="bi bi-exclamation-circle-fill me-2" style="color:#ca8a04;"></i> ${data.label || 'Sinkronisasi selesai sebagian.'}`;
                    setTimeout(() => window.location.reload(), 2500);
                } else {
                    // Idle state
                    idleTicks++;
                    if (idleTicks > 15) {
                        clearInterval(_syncPollInterval); _syncPollInterval = null;
                        if (cancelBtn) cancelBtn.style.display = 'none';
                        progressLabel.innerHTML = 'Batas waktu / Selesai (No Response)';
                        setTimeout(() => window.location.reload(), 1500);
                    }
                }
            })
            .catch(err => console.error('Error polling:', err));
    }, 2000);

    function finishSuccess() {
        clearInterval(_syncPollInterval); _syncPollInterval = null;
        progressPercent.textContent = '100%';
        progressBar.style.width = '100%';
        progressBar.classList.remove('progress-bar-animated');
        progressBar.style.backgroundColor = '#16a34a';
        progressLabel.innerHTML = `<i class="bi bi-check-circle-fill me-2" style="color:#16a34a;"></i> Sinkronisasi Selesai! Memuat ulang...`;
        setTimeout(() => window.location.reload(), 1500);
    }
}
</script>
<script>
(function () {
    const statusEl = document.getElementById('globalSyncStatus');
    if (!statusEl) return;

    const labelEl = statusEl.querySelector('.global-sync-label');
    const dotEl = statusEl.querySelector('.global-sync-dot');
    let lastSync = statusEl.dataset.lastSync || 'Belum pernah';
    const endpoint = `{{ route('marketplace.ads.syncProgress') }}?store_id=all`;

    function setStatus(color, icon, text) {
        if (dotEl) {
            dotEl.style.background = color;
            dotEl.style.boxShadow = color === '#2563eb' ? '0 0 0 3px rgba(37,99,235,.15)' : 'none';
        }
        if (labelEl) labelEl.innerHTML = `<i class="bi ${icon}" style="margin-right:.15rem;"></i>${text}`;
    }

    function pollGlobalSync() {
        fetch(endpoint, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then(response => response.json())
            .then(data => {
                if (data.last_sync_time) {
                    lastSync = data.last_sync_time;
                    statusEl.dataset.lastSync = lastSync;
                }
                if (data.status === 'queued' || data.status === 'processing') {
                    const percent = Number(data.percent || 0);
                    setStatus('#2563eb', 'bi-arrow-repeat spin-icon', `Sync berjalan ${percent}%`);
                    statusEl.title = data.label || 'Sinkronisasi iklan sedang diproses.';
                } else if (data.status === 'error') {
                    setStatus('#dc2626', 'bi-exclamation-circle-fill', 'Sync gagal');
                    statusEl.title = data.label || 'Sinkronisasi iklan gagal.';
                } else if (data.status === 'partial_success') {
                    setStatus('#d97706', 'bi-exclamation-triangle-fill', 'Sync sebagian');
                    statusEl.title = data.label || 'Sinkronisasi iklan selesai sebagian.';
                } else {
                    setStatus('#16a34a', 'bi-check-circle-fill', `Sync terakhir: ${lastSync}`);
                    statusEl.title = 'Auto-sync berjalan setiap jam.';
                }
            })
            .catch(() => {
                setStatus('#64748b', 'bi-dash-circle', `Sync terakhir: ${lastSync}`);
                statusEl.title = 'Status sync belum dapat diperbarui.';
            });
    }

    pollGlobalSync();
    setInterval(pollGlobalSync, 30000);
})();
</script>
<script>
// Ads Experiment UI
document.addEventListener('DOMContentLoaded', function () {
    const routes = window.AdsDashboardRoutes || {};
    const selectedStoreId = @json($storeId ?? 'all');
    const loading = document.getElementById('experimentsLoading');
    const empty = document.getElementById('experimentsEmpty');
    const error = document.getElementById('experimentsError');
    const tableWrap = document.getElementById('experimentsTableWrap');
    const tableBody = document.getElementById('experimentsTableBody');
    const detailPanel = document.getElementById('experimentDetailPanel');
    const detailMeta = document.getElementById('experimentDetailMeta');
    const detailSubtitle = document.getElementById('experimentDetailSubtitle');
    const baselineMetrics = document.getElementById('experimentBaselineMetrics');
    const observationMetrics = document.getElementById('experimentObservationMetrics');
    const qualityNote = document.getElementById('experimentQualityNote');
    const impactPanel = document.getElementById('experimentImpactPanel');
    const impactGrid = document.getElementById('experimentImpactGrid');
    const verdictBadge = document.getElementById('experimentVerdictBadge');
    const sufficiencyNote = document.getElementById('experimentSufficiencyNote');
    const conflictNote = document.getElementById('experimentConflictNote');
    const simulationPanel = document.getElementById('experimentSimulationPanel');
    const simulationPeriod = document.getElementById('experimentSimulationPeriod');
    const simulationPrice = document.getElementById('experimentSimulationPrice');
    const simulationRoas = document.getElementById('experimentSimulationRoas');
    const simulationButton = document.getElementById('btnRunExperimentSimulation');
    const simulationError = document.getElementById('experimentSimulationError');
    const simulationLoading = document.getElementById('experimentSimulationLoading');
    const simulationResult = document.getElementById('experimentSimulationResult');
    const changeFilter = document.getElementById('experimentChangeFilter');
    const statusFilter = document.getElementById('experimentStatusFilter');
    const dataNote = document.getElementById('experimentDataNote');
    let lastRows = [];
    let activeExperiment = null;

    if (!loading || !routes.experimentsIndex) return;

    const statusMap = {
        LEARNING: ['Learning', '#2563eb', 'rgba(37,99,235,.1)'],
        EARLY_SIGNAL: ['Early Signal', '#d97706', 'rgba(217,119,6,.12)'],
        READY_TO_EVALUATE: ['Ready to Evaluate', '#15803d', 'rgba(21,128,61,.12)'],
        INSUFFICIENT_DATA: ['Insufficient Data', '#b45309', 'rgba(180,83,9,.12)'],
        CONFOUNDED: ['Confounded', '#b91c1c', 'rgba(185,28,28,.1)'],
        COMPLETED: ['Completed', '#475569', 'rgba(71,85,105,.12)'],
    };

    const qualityLabels = {
        partial_day_excluded: 'hari perubahan dikecualikan',
        estimated_qty: 'qty memakai fallback order',
        estimated_price: 'harga diestimasi dari GMV / qty',
        estimated_profit: 'profit masih estimasi',
        missing_mapping: 'mapping HPP belum tersedia',
        missing_hpp: 'HPP belum tersedia',
        missing_price: 'harga belum tersedia',
        missing_metric: 'metric belum tersedia',
        low_volume: 'volume masih rendah',
        confounded: 'ada perubahan lain yang overlap',
    };

    const verdictMap = {
        POSITIVE: ['Positive', '#15803d', 'rgba(21,128,61,.12)'],
        NEGATIVE: ['Negative', '#b91c1c', 'rgba(185,28,28,.1)'],
        MIXED: ['Mixed', '#b45309', 'rgba(180,83,9,.12)'],
        NO_CLEAR_IMPACT: ['No clear impact', '#475569', 'rgba(71,85,105,.12)'],
        INCONCLUSIVE: ['Inconclusive', '#b45309', 'rgba(180,83,9,.12)'],
        INSUFFICIENT_DATA: ['Insufficient data', '#b45309', 'rgba(180,83,9,.12)'],
        CONFOUNDED: ['Confounded', '#b91c1c', 'rgba(185,28,28,.1)'],
    };

    function esc(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function number(value, digits) {
        if (value === null || value === undefined || value === '') return 'N/A';
        return new Intl.NumberFormat('id-ID', {
            maximumFractionDigits: digits === undefined ? 0 : digits,
            minimumFractionDigits: 0,
        }).format(Number(value));
    }

    function money(value) {
        if (value === null || value === undefined) return 'N/A';
        return 'Rp ' + number(value, 0);
    }

    function metricValue(key, value) {
        if (value === null || value === undefined) return 'N/A';
        if (key === 'ctr' || key === 'cvr') return number(Number(value) * 100, 1) + '%';
        if (key === 'cvr_bep') return number(Number(value) * 100, 1) + '%';
        if (key === 'roas' || key === 'break_even_roas') return number(value, 2) + 'x';
        if (['revenue', 'spend', 'net_revenue', 'ad_cost_incl_vat', 'total_cogs', 'profit'].indexOf(key) >= 0) return money(value);
        if (key === 'break_even_qty') return number(value, 2);
        return number(value, key === 'qty' ? 1 : 0);
    }

    function statusBadge(status) {
        const info = statusMap[status] || [status || 'Unknown', '#475569', 'rgba(71,85,105,.12)'];
        return '<span style="display:inline-flex; align-items:center; padding:.28rem .5rem; border-radius:999px; color:' + info[1] + '; background:' + info[2] + '; font-size:.66rem; font-weight:850; white-space:nowrap;">' + esc(info[0]) + '</span>';
    }

    function changeLabel(experiment) {
        const type = experiment.change_type;
        if (type === 'price') {
            return 'Harga · ' + (experiment.old_price === null ? 'N/A' : money(experiment.old_price)) + ' → ' + (experiment.new_price === null ? 'N/A' : money(experiment.new_price));
        }
        if (type === 'target_roas') {
            return 'Target ROAS · ' + (experiment.old_target_roas === null ? 'N/A' : number(experiment.old_target_roas, 2) + 'x') + ' → ' + (experiment.new_target_roas === null ? 'N/A' : number(experiment.new_target_roas, 2) + 'x');
        }
        return 'Harga + Target ROAS';
    }

    function scopeLabel(experiment) {
        const parts = [];
        if (experiment.channel_campaign_id) parts.push('Campaign ' + experiment.channel_campaign_id);
        if (experiment.channel_item_id) parts.push('Item ' + experiment.channel_item_id);
        if (!parts.length) parts.push('Store-level');
        return parts.join(' · ');
    }

    function periodSummary(period) {
        if (!period || Number(period.days_with_data || 0) === 0) {
            return '<div style="font-size:.7rem; line-height:1.65; color:var(--dsh-muted);">' +
                '<b style="color:#b45309;">Menunggu data selesai</b><br>' +
                esc((period || {}).from || '-') + ' → ' + esc((period || {}).to || '-') +
                '</div>';
        }
        const metrics = (period || {}).metrics || {};
        return '<div style="font-size:.7rem; line-height:1.65; color:var(--dsh-muted);">' +
            '<b style="color:var(--text);">ROAS ' + esc(metricValue('roas', metrics.roas)) + '</b> · ' +
            'Order ' + esc(metricValue('orders', metrics.orders)) + ' · ' +
            'Spend ' + esc(metricValue('spend', metrics.spend)) +
            '</div>';
    }

    function setLoading(active) {
        loading.style.display = active ? 'block' : 'none';
        if (active) {
            empty.style.display = 'none';
            tableWrap.style.display = 'none';
            error.style.display = 'none';
        }
    }

    function renderList(rows) {
        lastRows = rows || [];
        tableBody.innerHTML = '';
        if (!lastRows.length) {
            empty.style.display = 'block';
            tableWrap.style.display = 'none';
            dataNote.textContent = 'Belum ada event perubahan yang tercatat untuk filter ini.';
            return;
        }

        empty.style.display = 'none';
        tableWrap.style.display = 'block';
        dataNote.textContent = lastRows.length + ' experiment ditemukan. Profit actual belum dianggap siap sampai allocation ad cost lengkap.';

        lastRows.forEach(function (row) {
            const experiment = row.experiment || {};
            const details = row.details || {};
            const changedAt = (details.change || {}).effective_date || experiment.effective_date || '-';
            const tr = document.createElement('tr');
            tr.innerHTML =
                '<td><div style="font-weight:800; font-size:.72rem; color:var(--text);">' + esc(changeLabel(experiment)) + '</div>' +
                    '<div style="font-size:.64rem; color:var(--dsh-muted); margin-top:.15rem;">' + esc(experiment.change_event_id || '') + '</div></td>' +
                '<td><div style="font-size:.7rem; max-width:210px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="' + esc(scopeLabel(experiment)) + '">' + esc(scopeLabel(experiment)) + '</div></td>' +
                '<td><div style="font-size:.7rem; font-weight:700;">' + esc(changedAt) + '</div><div style="font-size:.64rem; color:var(--dsh-muted);">' + esc(experiment.profit_basis || 'incomplete') + '</div></td>' +
                '<td>' + statusBadge(details.lifecycle_status || experiment.lifecycle_status) + '</td>' +
                '<td>' + periodSummary(details.baseline) + '</td>' +
                '<td>' + periodSummary(details.observation) + '</td>' +
                '<td><button type="button" class="btn btn-sm js-view-experiment" data-id="' + esc(experiment.id) + '" style="border:1px solid rgba(37,99,235,.25); color:var(--dsh-accent); background:rgba(37,99,235,.06); border-radius:8px; font-size:.68rem; font-weight:800;">Detail</button></td>';
            tableBody.appendChild(tr);
        });

        tableBody.querySelectorAll('.js-view-experiment').forEach(function (button) {
            button.addEventListener('click', function () {
                loadDetail(button.dataset.id);
            });
        });
    }

    function loadList() {
        setLoading(true);
        const params = new URLSearchParams();
        if (selectedStoreId && selectedStoreId !== 'all') params.set('store_id', selectedStoreId);
        if (changeFilter.value) params.set('change_type', changeFilter.value);
        if (statusFilter.value) params.set('lifecycle_status', statusFilter.value);
        params.set('limit', '100');

        fetch(routes.experimentsIndex + '?' + params.toString(), {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        })
            .then(function (response) {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.json();
            })
            .then(function (payload) {
                setLoading(false);
                renderList(payload.data || []);
            })
            .catch(function (err) {
                setLoading(false);
                empty.style.display = 'none';
                tableWrap.style.display = 'none';
                error.textContent = 'Experiment gagal dimuat. Silakan refresh halaman. (' + err.message + ')';
                error.style.display = 'block';
            });
    }

    function renderMetricGrid(target, period) {
        const metrics = (period || {}).metrics || {};
        const hasData = Number((period || {}).days_with_data || 0) > 0;
        const rows = [
            ['Impressions', 'impressions'],
            ['Clicks', 'clicks'],
            ['Orders', 'orders'],
            ['Qty', 'qty'],
            ['Revenue', 'revenue'],
            ['Spend', 'spend'],
            ['CTR', 'ctr'],
            ['CVR', 'cvr'],
            ['ROAS', 'roas'],
            ['Profit', 'profit'],
            ['BEP Qty', 'break_even_qty'],
            ['CVR BEP', 'cvr_bep'],
            ['BEP ROAS', 'break_even_roas'],
        ];
        target.innerHTML = '<div style="display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:.45rem;">' +
            rows.map(function (item) {
                const value = hasData ? metricValue(item[1], metrics[item[1]]) : '—';
                return '<div style="padding:.55rem .6rem; border:1px solid var(--dsh-border); border-radius:9px;">' +
                    '<div style="font-size:.62rem; color:var(--dsh-muted);">' + item[0] + '</div>' +
                    '<div style="font-size:.78rem; font-weight:850; color:' + (hasData ? 'var(--text)' : '#b45309') + '; margin-top:.1rem;">' + esc(value) + '</div>' +
                '</div>';
            }).join('') + '</div>' +
            '<div style="font-size:.65rem; color:' + (hasData ? 'var(--dsh-muted)' : '#b45309') + '; margin-top:.55rem;">' +
                (hasData
                    ? 'Data: ' + esc((period || {}).source_table || 'belum tersedia') + ' · ' + number((period || {}).days_with_data || 0) + ' hari memiliki data'
                    : 'Belum ada hari selesai dengan data · periode ' + esc((period || {}).from || '-') + ' → ' + esc((period || {}).to || '-')) +
            '</div>';
    }

    function renderImpact(data) {
        const impact = data.impact || {};
        const steps = impact.steps || {};
        const labels = ['traffic', 'ctr', 'cvr', 'qty', 'roas', 'profit'];
        const verdict = data.verdict || 'INCONCLUSIVE';
        const verdictInfo = verdictMap[verdict] || [verdict, '#475569', 'rgba(71,85,105,.12)'];
        verdictBadge.textContent = verdictInfo[0];
        verdictBadge.style.color = verdictInfo[1];
        verdictBadge.style.background = verdictInfo[2];
        verdictBadge.style.borderColor = verdictInfo[2];

        impactGrid.innerHTML = labels.map(function (key) {
            const step = steps[key] || {};
            const direction = step.direction || 'unavailable';
            const tone = direction === 'up' ? '#15803d' : (direction === 'down' ? '#b91c1c' : 'var(--dsh-muted)');
            const delta = step.change_percent === null || step.change_percent === undefined
                ? 'N/A'
                : (step.change_percent > 0 ? '+' : '') + number(step.change_percent, 1) + '%';
            const value = key === 'ctr' || key === 'cvr'
                ? metricValue(key, step.after)
                : metricValue(key === 'traffic' ? 'impressions' : key, step.after);
            const measurement = key === 'profit'
                ? ' · ' + (step.measurement === 'actual' ? 'actual' : (step.measurement === 'estimated' ? 'estimasi' : 'N/A'))
                : '';

            return '<div class="ads-phase3-impact-step">' +
                '<div class="ads-phase3-impact-label">' + esc(step.label || key) + esc(measurement) + '</div>' +
                '<div class="ads-phase3-impact-value">' + esc(value) + '</div>' +
                '<div class="ads-phase3-impact-delta" style="color:' + tone + ';">' + esc(delta) + '</div>' +
            '</div>';
        }).join('');

        const sufficiency = data.data_sufficiency || {};
        const reasons = sufficiency.reasons || [];
        const reasonText = reasons.length ? ' · Alasan: ' + reasons.join(', ') : '';
        sufficiencyNote.innerHTML = '<b style="color:var(--text);">Data sufficiency:</b> ' +
            (sufficiency.metric_ready ? 'cukup untuk evaluasi metrik' : 'belum cukup untuk verdict') +
            (sufficiency.profit_ready ? ' · profit actual siap' : ' · profit masih estimasi atau belum lengkap') +
            esc(reasonText);

        const conflicts = data.conflicts || [];
        if (conflicts.length) {
            conflictNote.textContent = 'Conflict terdeteksi: ' + conflicts.map(function (conflict) {
                return conflict.reason + (conflict.experiment_id ? ' #' + conflict.experiment_id : '');
            }).join(', ') + '. Verdict tidak dianggap kausal.';
            conflictNote.style.display = 'block';
        } else {
            conflictNote.textContent = '';
            conflictNote.style.display = 'none';
        }
        impactPanel.style.display = 'block';
    }

    function parseInputNumber(value) {
        let raw = String(value === null || value === undefined ? '' : value).trim()
            .replace(/[^0-9,.-]/g, '');
        if (!raw) return null;

        if (raw.includes(',') && raw.includes('.')) {
            raw = raw.replace(/\./g, '').replace(',', '.');
        } else if (raw.includes(',')) {
            raw = raw.replace(',', '.');
        } else if (/^\d{1,3}(\.\d{3})+$/.test(raw)) {
            raw = raw.replace(/\./g, '');
        }

        const parsed = Number(raw);
        return Number.isFinite(parsed) ? parsed : null;
    }

    function simulationCard(label, value, tone) {
        return '<div class="ads-phase2-result-card">' +
            '<div class="ads-phase2-result-label">' + esc(label) + '</div>' +
            '<div class="ads-phase2-result-value" style="color:' + (tone || 'var(--text)') + ';">' + esc(value) + '</div>' +
        '</div>';
    }

    function renderSimulation(result) {
        const current = result.current_quantity || {};
        const target = result.target_roas || {};
        const bep = result.break_even || {};
        const profitLabel = current.actual_profit_ready ? 'Aktual' : 'Estimasi';
        const profitTone = current.actual_profit_ready ? '#15803d' : '#b45309';

        simulationResult.innerHTML =
            '<div class="ads-phase2-result-grid">' +
                simulationCard('Profit ' + profitLabel, metricValue('profit', current.profit), profitTone) +
                simulationCard('BEP Qty', metricValue('break_even_qty', bep.qty), '#2563eb') +
                simulationCard('CVR BEP', metricValue('cvr_bep', bep.cvr), '#2563eb') +
                simulationCard('BEP ROAS', metricValue('break_even_roas', bep.roas), '#2563eb') +
            '</div>' +
            '<div class="ads-phase2-result-grid">' +
                simulationCard('GMV pada Target ROAS', money(target.target_gmv), '#0f766e') +
                simulationCard('Qty pada Target ROAS', number(target.target_qty, 2), '#0f766e') +
                simulationCard('CVR pada Target ROAS', target.target_cvr === null || target.target_cvr === undefined ? 'N/A' : number(target.target_cvr * 100, 1) + '%', '#0f766e') +
                simulationCard('Profit Target', money(target.estimated_profit), target.estimated_profit !== null && target.estimated_profit < 0 ? '#b91c1c' : '#0f766e') +
            '</div>' +
            '<div style="display:flex; gap:.45rem; flex-wrap:wrap; margin-top:.6rem; color:var(--dsh-muted); font-size:.65rem;">' +
                '<span>Spend: ' + esc(money(result.assumptions?.spend)) + '</span>' +
                '<span>·</span><span>Qty: ' + esc(number(result.assumptions?.qty, 2)) + (result.assumptions?.qty_estimated ? ' (estimasi)' : '') + '</span>' +
                '<span>·</span><span>Basis: ' + esc(result.profit_basis || 'net_hpp_ads') + '</span>' +
            '</div>';
        simulationResult.style.display = 'block';
    }

    function setSimulationLoading(active) {
        simulationLoading.style.display = active ? 'block' : 'none';
        simulationButton.disabled = active;
        if (active) simulationError.style.display = 'none';
    }

    function runSimulation() {
        if (!activeExperiment || !routes.experimentsSimulate) return;

        const payload = {
            experiment_id: Number(activeExperiment.id),
            period: simulationPeriod.value || 'observation',
        };
        const price = parseInputNumber(simulationPrice.value);
        const targetRoas = parseInputNumber(simulationRoas.value);
        if (price !== null) payload.price = price;
        if (targetRoas !== null) payload.target_roas = targetRoas;

        setSimulationLoading(true);
        fetch(routes.experimentsSimulate, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        })
            .then(function (response) {
                return response.json().catch(function () { return {}; }).then(function (data) {
                    if (!response.ok) throw new Error(data.message || 'Simulasi gagal diproses.');
                    return data;
                });
            })
            .then(function (payload) {
                renderSimulation(payload.data?.result || {});
            })
            .catch(function (err) {
                simulationResult.style.display = 'none';
                simulationError.textContent = err.message || 'Simulasi gagal diproses.';
                simulationError.style.display = 'block';
            })
            .finally(function () {
                setSimulationLoading(false);
            });
    }

    function loadDetail(id) {
        if (!routes.experimentsShow || !id) return;
        const url = routes.experimentsShow.replace('__EXPERIMENT_ID__', encodeURIComponent(id));
        detailPanel.style.display = 'block';
        detailSubtitle.textContent = 'Memuat detail experiment...';
        detailMeta.innerHTML = '';
        baselineMetrics.innerHTML = '<div class="text-muted small p-2">Memuat...</div>';
        observationMetrics.innerHTML = '<div class="text-muted small p-2">Memuat...</div>';
        qualityNote.innerHTML = '';
        impactPanel.style.display = 'none';
        impactGrid.innerHTML = '';
        sufficiencyNote.innerHTML = '';
        conflictNote.style.display = 'none';
        simulationPanel.style.display = 'none';
        simulationResult.style.display = 'none';
        simulationError.style.display = 'none';
        activeExperiment = null;
        detailPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });

        fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then(function (response) {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.json();
            })
            .then(function (payload) {
                const data = payload.data || {};
                const experiment = data.experiment || {};
                const change = data.change || {};
                const quality = data.data_quality || {};
                activeExperiment = experiment;
                detailSubtitle.textContent = changeLabel(experiment) + ' · ' + (change.effective_date || '-');
                detailMeta.innerHTML =
                    '<div class="col-6 col-md-3"><div style="font-size:.64rem; color:var(--dsh-muted);">Status</div><div style="margin-top:.25rem;">' + statusBadge(data.lifecycle_status || experiment.lifecycle_status) + '</div></div>' +
                    '<div class="col-6 col-md-3"><div style="font-size:.64rem; color:var(--dsh-muted);">Profit basis</div><div style="font-size:.78rem; font-weight:800; margin-top:.25rem;">' + esc(data.profit_basis || 'incomplete') + '</div></div>' +
                    '<div class="col-6 col-md-3"><div style="font-size:.64rem; color:var(--dsh-muted);">Scope</div><div style="font-size:.72rem; font-weight:800; margin-top:.25rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="' + esc(scopeLabel(experiment)) + '">' + esc(scopeLabel(experiment)) + '</div></div>' +
                    '<div class="col-6 col-md-3"><div style="font-size:.64rem; color:var(--dsh-muted);">Verdict</div><div style="font-size:.78rem; font-weight:800; margin-top:.25rem;">' + esc(data.verdict || 'INCONCLUSIVE') + '</div></div>';
                renderMetricGrid(baselineMetrics, data.baseline);
                renderMetricGrid(observationMetrics, data.observation);
                renderImpact(data);
                const observationPrice = data.observation?.metrics?.price;
                simulationPrice.value = number(change.new_price ?? change.old_price ?? observationPrice, 0) === 'N/A'
                    ? ''
                    : number(change.new_price ?? change.old_price ?? observationPrice, 0);
                simulationRoas.value = change.new_target_roas === null || change.new_target_roas === undefined
                    ? ''
                    : number(change.new_target_roas, 2);
                simulationPanel.style.display = 'block';

                const flags = []
                    .concat(quality.experiment_flags || [])
                    .concat(quality.baseline_flags || [])
                    .concat(quality.observation_flags || [])
                    .filter(function (value, index, all) { return value && all.indexOf(value) === index; });
                const flagText = flags.length
                    ? flags.map(function (flag) { return qualityLabels[flag] || flag; }).join(' · ')
                    : 'Tidak ada flag tambahan.';
                const observationDays = Number(data.observation?.days_with_data || 0);
                const observationNote = observationDays === 0
                    ? '<br><b style="color:#b45309;">Observation:</b> menunggu data hari selesai setelah periode perubahan.'
                    : '';
                const profitNote = quality.actual_profit_ready
                    ? 'actual'
                    : 'belum aktual (mapping/HPP atau data observation belum lengkap)';
                qualityNote.innerHTML =
                    '<div style="padding:.7rem .8rem; border-radius:10px; background:rgba(148,163,184,.08); border:1px solid var(--dsh-border); color:var(--dsh-muted);">' +
                    '<b style="color:var(--text);">Kualitas data:</b> ' + esc(flagText) + '<br>' +
                    '<b style="color:var(--text);">Mapping:</b> ' + esc(quality.mapping_status || 'unknown') + ' · ' +
                    '<b style="color:var(--text);">Profit:</b> ' + esc(profitNote) +
                    observationNote +
                    '</div>';
            })
            .catch(function (err) {
                detailSubtitle.textContent = 'Detail tidak dapat dimuat.';
                qualityNote.innerHTML = '<div style="color:#b91c1c; font-size:.75rem;">Gagal memuat detail: ' + esc(err.message) + '</div>';
            });
    }

    document.getElementById('btnReloadExperiments')?.addEventListener('click', loadList);
    document.getElementById('btnCloseExperimentDetail')?.addEventListener('click', function () {
        detailPanel.style.display = 'none';
    });
    simulationButton?.addEventListener('click', runSimulation);
    simulationPeriod?.addEventListener('change', function () {
        simulationResult.style.display = 'none';
        simulationError.style.display = 'none';
    });
    changeFilter?.addEventListener('change', loadList);
    statusFilter?.addEventListener('change', loadList);
    document.querySelector('#adsPrimaryTabs [data-group="grp-experiment"]')?.addEventListener('click', loadList);

    loadList();
});
</script>
@endpush
