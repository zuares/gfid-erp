{{-- resources/views/sales/shipments/rekon.blade.php --}}
@extends('layouts.app')
@section('title', 'Rekonsiliasi · ' . $shipment->code)

@push('head')
<style>
/* ══════════════════════════════════════════════════
   THEME VARIABLES — identik dengan edit.blade.php
══════════════════════════════════════════════════ */
:root {
    --shp-accent:      #2563eb;
    --shp-accent-2:    #1d4ed8;
    --shp-accent-bg:   rgba(37,99,235,.08);
    --shp-accent-ring: rgba(37,99,235,.22);
    --shp-ok:          #15803d;
    --shp-ok-bg:       rgba(21,128,61,.08);
    --shp-err:         #b91c1c;
    --shp-err-bg:      rgba(185,28,28,.08);
    --shp-warn:        #92400e;
    --shp-warn-bg:     rgba(245,158,11,.08);
}
.page-theme-shopee {
    --shp-accent:      #f97316;
    --shp-accent-2:    #ea580c;
    --shp-accent-bg:   rgba(249,115,22,.08);
    --shp-accent-ring: rgba(249,115,22,.22);
}
.page-theme-tiktok {
    --shp-accent:      #0f766e;
    --shp-accent-2:    #0e7490;
    --shp-accent-bg:   rgba(15,118,110,.08);
    --shp-accent-ring: rgba(45,212,191,.22);
}

/* ══════════════════════════════════════════════════
   PAGE WRAP
══════════════════════════════════════════════════ */
.rk-wrap {
    max-width: 1000px;
    margin-inline: auto;
    padding: 0 .75rem 6rem;
}
body[data-theme="light"] .rk-wrap { background: #f3f4f6; }
body[data-theme="dark"]  .rk-wrap {
    background: radial-gradient(circle at top left, rgba(15,23,42,.9) 0, #020617 65%);
}

/* ══════════════════════════════════════════════════
   STICKY TOPBAR — sama persis dengan edit.blade.php
══════════════════════════════════════════════════ */
.shp-topbar {
    position: sticky; top: 0; z-index: 300;
    display: flex; align-items: center; gap: .6rem; flex-wrap: wrap;
    padding: .5rem .85rem;
    background: rgba(248,250,252,.97);
    border-bottom: 1px solid rgba(148,163,184,.22);
    backdrop-filter: blur(14px);
}
body[data-theme="dark"] .shp-topbar {
    background: rgba(2,6,23,.96);
    border-bottom-color: rgba(30,64,175,.45);
}
.shp-topbar-code { font-weight: 900; font-size: 1.05rem; letter-spacing: .04em; white-space: nowrap; }
body[data-theme="dark"] .shp-topbar-code { color: #e5e7eb; }

.shp-badge {
    border-radius: 999px; padding: .15rem .65rem;
    font-size: .7rem; letter-spacing: .08em; text-transform: uppercase; white-space: nowrap;
}
.shp-badge-draft {
    background: rgba(251,191,36,.1); color: #92400e;
    border: 1px solid rgba(245,158,11,.28);
}
body[data-theme="dark"] .shp-badge-draft {
    background: rgba(251,191,36,.2); color: #fef9c3; border-color: rgba(245,158,11,.6);
}
.shp-topbar-spacer { flex: 1; min-width: .5rem; }

.shp-pill {
    border-radius: 999px; padding: .2rem .75rem;
    font-size: .77rem; border: 1px solid rgba(148,163,184,.32);
    background: rgba(248,250,252,.96); white-space: nowrap;
}
body[data-theme="dark"] .shp-pill {
    background: rgba(15,23,42,.98); border-color: rgba(30,64,175,.65); color: #e5e7eb;
}
.shp-pill b { font-size: .87rem; }
.shp-pill-accent {
    border-color: var(--shp-accent) !important;
    background: var(--shp-accent-bg) !important;
    color: var(--shp-accent) !important;
    font-weight: 700;
}
body[data-theme="dark"] .shp-pill-accent { color: #93c5fd !important; }

.btn-shp-submit {
    border-radius: 999px; font-size: .8rem; font-weight: 800; letter-spacing: .06em;
    text-transform: uppercase; padding: .42rem 1.35rem;
    border: 1px solid var(--shp-accent); background: var(--shp-accent); color: #fff;
    box-shadow: 0 4px 14px rgba(0,0,0,.2);
    transition: background .12s, box-shadow .12s; white-space: nowrap;
    opacity: .4; pointer-events: none;
}
.btn-shp-submit.active {
    opacity: 1; pointer-events: auto;
}
.btn-shp-submit:hover { background: var(--shp-accent-2); border-color: var(--shp-accent-2); color: #fff; }

.btn-shp-outline {
    border-radius: 999px; font-size: .77rem; letter-spacing: .05em; text-transform: uppercase;
    padding: .32rem 1rem; border: 1px solid rgba(148,163,184,.5);
    background: transparent; color: #6b7280; white-space: nowrap;
    transition: background .12s, color .12s;
}
.btn-shp-outline:hover { background: rgba(226,232,240,.7); color: #374151; }
body[data-theme="dark"] .btn-shp-outline { color: #d1d5db; border-color: rgba(71,85,105,.8); }
.btn-return-scan {
    display: inline-flex;
    align-items: center;
    gap: .38rem;
    font-weight: 800;
    border-color: var(--shp-accent) !important;
    background: var(--shp-accent-bg) !important;
    color: var(--shp-accent) !important;
}
.btn-return-scan:hover {
    background: rgba(37,99,235,.14) !important;
    color: var(--shp-accent-2) !important;
}
body[data-theme="dark"] .btn-return-scan {
    color: #bfdbfe !important;
    background: rgba(37,99,235,.16) !important;
    border-color: rgba(147,197,253,.55) !important;
}

/* ══════════════════════════════════════════════════
   PHASE BAR
══════════════════════════════════════════════════ */
.rk-phases {
    display: flex; align-items: center; gap: .35rem;
    margin: .85rem 0 .65rem; flex-wrap: wrap;
}
.rk-phase {
    padding: .26rem .9rem; border-radius: 999px;
    font-size: .73rem; font-weight: 700;
    background: rgba(148,163,184,.12); color: #94a3b8;
    transition: all .2s;
}
body[data-theme="dark"] .rk-phase { background: rgba(30,41,59,.6); color: #64748b; }
.rk-phase.done   { background: var(--shp-ok-bg); color: var(--shp-ok); }
.rk-phase.active { background: var(--shp-accent); color: #fff; box-shadow: 0 2px 10px var(--shp-accent-ring); }
.rk-sep { color: rgba(148,163,184,.5); font-size: .8rem; }

/* ══════════════════════════════════════════════════
   BATCH SUMMARY BAR
══════════════════════════════════════════════════ */
.rk-batch-bar {
    display: flex; flex-wrap: wrap; gap: .4rem; align-items: center;
    padding: .65rem 1rem;
    background: var(--card,#fff);
    border: 1px solid rgba(148,163,184,.16);
    border-radius: 14px; margin-bottom: .75rem;
    box-shadow: 0 2px 8px rgba(15,23,42,.04);
}
body[data-theme="dark"] .rk-batch-bar {
    border-color: rgba(30,64,175,.4); box-shadow: 0 6px 20px rgba(15,23,42,.6);
}
.rk-sku-chip {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .2rem .65rem; border-radius: 999px;
    border: 1px solid rgba(148,163,184,.3);
    background: rgba(248,250,252,.9); font-size: .75rem;
}
body[data-theme="dark"] .rk-sku-chip {
    background: rgba(15,23,42,.8); border-color: rgba(30,64,175,.5); color: #e2e8f0;
}
.rk-sku-chip .c { font-weight: 800; font-family: monospace; font-size: .78rem; }
.rk-sku-chip .q { font-weight: 800; color: var(--shp-accent); }
.rk-tabs {
    display: flex; gap: .25rem; margin-bottom: .75rem;
    border-bottom: 1px solid rgba(148,163,184,.2); flex-wrap: wrap;
}
body[data-theme="dark"] .rk-tabs { border-bottom-color: rgba(30,64,175,.35); }
.rk-tab {
    appearance: none; display: inline-flex; align-items: center; gap: .4rem;
    border: none; background: transparent; color: #94a3b8;
    font-family: inherit; font-weight: 800; font-size: .84rem;
    padding: .55rem .85rem; cursor: pointer;
    border-bottom: 2px solid transparent; margin-bottom: -1px;
}
.rk-tab:hover { color: #475569; }
body[data-theme="dark"] .rk-tab:hover { color: #cbd5e1; }
.rk-tab.active { color: var(--shp-accent); border-bottom-color: var(--shp-accent); }
body[data-theme="dark"] .rk-tab.active { color: #93c5fd; border-bottom-color: #93c5fd; }
.rk-tab-count {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 1.35rem; height: 1.35rem; padding: 0 .3rem; border-radius: 999px;
    background: rgba(148,163,184,.18); color: #64748b;
    font-family: monospace; font-size: .72rem; font-weight: 900;
}
.rk-tab.active .rk-tab-count { background: var(--shp-accent); color: #fff; }
.rk-tabpane { display: none; }
.rk-tabpane.active { display: block; }
/* Item Batch table (mirip halaman detail) */
.rk-batch-head {
    display: flex; align-items: center; justify-content: space-between; gap: .55rem;
    padding: .8rem 1.1rem; border-bottom: 1px solid rgba(148,163,184,.12);
}
body[data-theme="dark"] .rk-batch-head { border-bottom-color: rgba(30,64,175,.2); }
.rk-batch-title { font-weight: 900; color: #334155; font-size: .95rem; }
body[data-theme="dark"] .rk-batch-title { color: #e2e8f0; }
.rk-batch-sub { color: #64748b; font-size: .78rem; margin-top: .1rem; }
.rk-batch-pill {
    display: inline-flex; align-items: center; border-radius: 999px;
    border: 1px solid rgba(148,163,184,.3); padding: .18rem .55rem;
    font-size: .72rem; font-weight: 800; color: #64748b; white-space: nowrap;
}
.rk-batch-tbl { padding: 0 .55rem; }
.rk-batch-tbl th { padding: .55rem 1.1rem; }
.rk-batch-tbl td { padding: .6rem 1.1rem; }
.rk-batch-name { color: #64748b; font-size: .78rem; margin-top: .1rem; }
.rk-batch-cat { color: #94a3b8; font-size: .74rem; margin-top: .08rem; }
.rk-qty-badge {
    display: inline-flex; align-items: center; justify-content: center;
    border-radius: 7px; border: 1px solid rgba(148,163,184,.25);
    padding: .16rem .5rem; font-size: .78rem; font-weight: 800; color: #475569; white-space: nowrap;
}
body[data-theme="dark"] .rk-qty-badge { color: #cbd5e1; border-color: rgba(71,85,105,.6); }
.rk-batch-total td { font-weight: 900; color: #111827; background: rgba(148,163,184,.05); }
body[data-theme="dark"] .rk-batch-total td { color: #e2e8f0; background: rgba(30,41,59,.5); }
.rk-batch-group td {
    padding: .28rem 1.1rem;
    font-size: .6rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: #475569;
    background: rgba(148,163,184,.12);
    border-top: 1px solid rgba(148,163,184,.18);
}
.rk-batch-tbl .rk-icode { font-size: .8rem; }
body[data-theme="dark"] .rk-batch-group td {
    color: #cbd5e1;
    background: rgba(30,41,59,.65);
    border-top-color: rgba(51,65,85,.6);
}
.rk-batch-group-count { font-weight: 700; color: #94a3b8; }
.rk-batch-empty { padding: 1.6rem 1rem; text-align: center; color: #64748b; font-size: .84rem; }
.rk-show-sm { display: none; }
@media (max-width: 640px) {
    .rk-hide-sm { display: none; }
    .rk-show-sm { display: block; }
}

/* ══════════════════════════════════════════════════
   HERO SCAN CARD — identik dengan edit.blade.php
══════════════════════════════════════════════════ */
.shp-scan-card {
    background: var(--card,#fff);
    border-radius: 20px; border: 2px solid rgba(148,163,184,.18);
    box-shadow: 0 4px 22px rgba(15,23,42,.06);
    padding: 1.35rem 1.5rem 1.2rem; margin-bottom: .75rem;
    transition: border-color .15s, box-shadow .15s;
}
.shp-scan-card:focus-within {
    border-color: var(--shp-accent);
    box-shadow: 0 0 0 4px var(--shp-accent-ring), 0 8px 28px rgba(15,23,42,.1);
}
body[data-theme="dark"] .shp-scan-card { border-color: rgba(30,64,175,.5); box-shadow: 0 12px 36px rgba(15,23,42,.85); }
body[data-theme="dark"] .shp-scan-card:focus-within {
    border-color: var(--shp-accent);
    box-shadow: 0 0 0 4px var(--shp-accent-ring), 0 12px 36px rgba(15,23,42,.85);
}

.shp-scan-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: .65rem; flex-wrap: wrap; gap: .35rem;
}
.shp-scan-label {
    font-size: .64rem; text-transform: uppercase; letter-spacing: .12em;
    color: #9ca3af; font-weight: 700;
}
body[data-theme="dark"] .shp-scan-label { color: #6b7280; }
.shp-scan-counter { font-size: .7rem; color: var(--shp-accent); font-weight: 700; letter-spacing: .04em; }
body[data-theme="dark"] .shp-scan-counter { color: #93c5fd; }

.shp-scan-input {
    font-size: 2rem; font-weight: 800; letter-spacing: .12em;
    padding: .7rem 1rem; text-transform: uppercase;
    border-radius: 14px; border: 2.5px solid rgba(148,163,184,.3);
    width: 100%; line-height: 1.2; background: transparent;
    color: #0f172a;
    transition: border-color .12s, box-shadow .12s;
}
body[data-theme="dark"] .shp-scan-input {
    background: rgba(15,23,42,.5); border-color: rgba(51,65,85,.9); color: #f1f5f9;
}
.shp-scan-input::placeholder {
    text-transform: none; letter-spacing: normal;
    font-weight: 400; font-size: 1.05rem; color: #cbd5e1;
}
body[data-theme="dark"] .shp-scan-input::placeholder { color: #334155; }
.shp-scan-input:focus { border-color: var(--shp-accent); box-shadow: 0 0 0 3px var(--shp-accent-ring); outline: none; }

/* ══════════════════════════════════════════════════
   STATUS BADGES
══════════════════════════════════════════════════ */
.rk-sbadge {
    border-radius: 999px; padding: .15rem .65rem;
    font-size: .69rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; white-space: nowrap;
}
.sb-ready    { background: var(--shp-ok-bg);   color: var(--shp-ok);   border: 1px solid rgba(21,128,61,.25); }
.sb-partial  { background: var(--shp-warn-bg); color: var(--shp-warn); border: 1px solid rgba(245,158,11,.3); }
.sb-missing  { background: var(--shp-err-bg);  color: var(--shp-err);  border: 1px solid rgba(185,28,28,.25); }
.sb-notfound { background: rgba(148,163,184,.1); color: #64748b; border: 1px solid rgba(148,163,184,.3); }
.sb-pending  { background: var(--shp-accent-bg); color: var(--shp-accent); border: 1px solid rgba(37,99,235,.25); }
.sb-skip     { background: rgba(148,163,184,.1); color: #9ca3af; border: 1px solid rgba(148,163,184,.25); }
.sb-sub      { background: rgba(139,92,246,.09); color: #7c3aed; border: 1px solid rgba(139,92,246,.28); }
.rk-sub-fulfilled {
    display: flex; align-items: center; flex-wrap: wrap; gap: .35rem;
    margin-top: .45rem; padding: .45rem .6rem;
    background: rgba(139,92,246,.07); border: 1px solid rgba(139,92,246,.2);
    border-radius: 10px;
}
.rk-sub-fulfilled .sf-label  { font-size: .73rem; font-weight: 700; color: #7c3aed; }
.rk-sub-fulfilled .sf-code   { font-size: .78rem; font-weight: 700; background: rgba(139,92,246,.12); color: #6d28d9; padding: .1rem .42rem; border-radius: 6px; }
.rk-sub-fulfilled .sf-name   { font-size: .73rem; color: #6b7280; }
.rk-sub-fulfilled .sf-qty    { font-size: .73rem; font-weight: 700; color: #7c3aed; }
.rk-sub-fulfilled .sf-change { font-size: .69rem; color: var(--shp-accent); cursor: pointer; text-decoration: underline; padding: 0 .2rem; }

/* ══════════════════════════════════════════════════
   ORDER CARDS
══════════════════════════════════════════════════ */
.rk-order-card {
    background: var(--card,#fff);
    border: 1px solid rgba(148,163,184,.16); border-radius: 18px;
    box-shadow: 0 2px 12px rgba(15,23,42,.04);
    margin-bottom: .65rem; overflow: hidden;
    transition: box-shadow .15s, border-color .15s;
}
body[data-theme="dark"] .rk-order-card {
    border-color: rgba(30,64,175,.35); box-shadow: 0 8px 28px rgba(15,23,42,.7);
}
.rk-order-card.decided-fulfill { border-left: 3.5px solid var(--shp-ok); }
.rk-order-card.decided-pending { border-left: 3.5px solid #f59e0b; }
.rk-order-card.decided-skip    { border-left: 3.5px solid rgba(148,163,184,.5); }
.rk-order-card.rk-dupe         { border-left: 3.5px solid #f43f5e !important; }
.rk-dupe-badge { display:inline-flex; align-items:center; gap:.25rem;
    background:#fda4af; color:#9f1239; font-size:.72rem; font-weight:700;
    border-radius:6px; padding:.1rem .45rem; letter-spacing:.02em; }
body[data-theme="dark"] .rk-dupe-badge { background:#4c0519; color:#fda4af; }
@keyframes rkDupePulse {
    0%,100% { box-shadow: 0 0 0 0 rgba(244,63,94,.0); }
    50%      { box-shadow: 0 0 0 5px rgba(244,63,94,.35); }
}
.rk-order-card.rk-dupe-flash { animation: rkDupePulse .35s ease 4; }

.rk-order-hdr {
    display: flex; align-items: center; gap: .6rem; flex-wrap: wrap;
    padding: .8rem 1.1rem; cursor: pointer; user-select: none;
    border-bottom: 1px solid rgba(148,163,184,.1); transition: background .1s;
}
.rk-order-hdr:hover { background: rgba(241,245,249,.6); }
body[data-theme="dark"] .rk-order-hdr { border-bottom-color: rgba(30,64,175,.2); }
body[data-theme="dark"] .rk-order-hdr:hover { background: rgba(30,41,59,.5); }

.rk-order-no   { font-weight: 900; font-family: monospace; font-size: .98rem; }
body[data-theme="dark"] .rk-order-no { color: #e2e8f0; }
.rk-order-num  { font-weight: 900; font-family: monospace; font-size: .9rem; color: #94a3b8; flex-shrink: 0; }
body[data-theme="dark"] .rk-order-num { color: #64748b; }
.rk-order-store {
    padding: .13rem .55rem; border-radius: 999px;
    border: 1px solid rgba(148,163,184,.4); font-size: .72rem; font-weight: 700; color: #475569;
}
body[data-theme="dark"] .rk-order-store { color: #94a3b8; border-color: rgba(71,85,105,.6); }
.rk-order-chev { margin-left: auto; color: #94a3b8; font-size: .75rem; transition: transform .2s; }
.rk-order-chev.open { transform: rotate(180deg); }

.rk-order-body { padding: .75rem 1.1rem 1rem; }

/* lines table */
.rk-tbl { width: 100%; border-collapse: collapse; }
.rk-tbl th {
    font-size: .66rem; text-transform: uppercase; letter-spacing: .07em;
    color: #9ca3af; font-weight: 700; text-align: left;
    padding: .35rem .55rem; border-bottom: 1px solid rgba(148,163,184,.14);
}
.rk-tbl td { padding: .48rem .55rem; border-bottom: 1px solid rgba(148,163,184,.07); font-size: .84rem; vertical-align: middle; }
body[data-theme="dark"] .rk-tbl th { color: #6b7280; border-bottom-color: rgba(30,64,175,.25); }
body[data-theme="dark"] .rk-tbl td { border-bottom-color: rgba(30,64,175,.12); color: #d1d5db; }
.rk-tbl tr:last-child td { border-bottom: none; }
.rk-icode { font-family: monospace; font-weight: 800; font-size: .92rem; }
body[data-theme="dark"] .rk-icode { color: #e2e8f0; }
.rk-qty-ok    { color: var(--shp-ok);  font-weight: 800; }
.rk-qty-short { color: var(--shp-err); font-weight: 800; }
.rk-qty-dim   { color: #94a3b8; }

/* action strip */
.rk-action-strip {
    display: flex; gap: .5rem; align-items: center; flex-wrap: wrap;
    margin-top: .75rem; padding-top: .65rem;
    border-top: 1px solid rgba(148,163,184,.12);
}
body[data-theme="dark"] .rk-action-strip { border-top-color: rgba(30,64,175,.2); }

.rk-act-btn {
    border-radius: 999px; border: 1.5px solid;
    font-size: .78rem; font-weight: 700;
    padding: .3rem .95rem; cursor: pointer; transition: background .1s, color .1s; background: transparent;
}
.rk-act-btn.fulfill { border-color: var(--shp-ok);   color: var(--shp-ok); }
.rk-act-btn.fulfill:hover, .rk-act-btn.fulfill.on { background: var(--shp-ok);   color: #fff; border-color: var(--shp-ok); }
.rk-act-btn.pending { border-color: #f59e0b; color: #92400e; }
.rk-act-btn.pending:hover, .rk-act-btn.pending.on { background: #f59e0b; color: #fff; }
.rk-act-btn.skip    { border-color: rgba(148,163,184,.45); color: #6b7280; }
.rk-act-btn.skip:hover, .rk-act-btn.skip.on { background: #94a3b8; color: #fff; border-color: #94a3b8; }

/* ══════════════════════════════════════════════════
   SUBSTITUSI ROW + DRAWER
══════════════════════════════════════════════════ */
.rk-sub-row {
    display: flex; align-items: center; gap: .55rem;
    margin-top: .55rem; padding: .55rem .7rem;
    background: var(--shp-accent-bg);
    border: 1px solid rgba(37,99,235,.2); border-radius: 10px; flex-wrap: wrap;
}
.rk-sub-lbl { font-size: .75rem; font-weight: 700; color: var(--shp-accent); white-space: nowrap; }
.rk-sub-tag {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .2rem .65rem; border-radius: 999px;
    background: var(--shp-accent); color: #fff;
    font-size: .76rem; font-weight: 700; font-family: monospace;
}
.rk-sub-btn-pick {
    border-radius: 999px; border: 1.5px solid var(--shp-accent);
    background: transparent; color: var(--shp-accent);
    font-size: .76rem; font-weight: 700; padding: .2rem .75rem; cursor: pointer;
    transition: background .1s, color .1s;
}
.rk-sub-btn-pick:hover { background: var(--shp-accent); color: #fff; }

/* MODAL SUBSTITUSI */
.rk-modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(15,23,42,.5); z-index: 500;
    backdrop-filter: blur(4px);
    justify-content: center; align-items: center;
    padding: 1rem;
}
.rk-modal-overlay.open { display: flex; }

.rk-modal {
    width: 100%; max-width: 480px; max-height: 80vh;
    background: var(--card,#fff);
    border-radius: 20px;
    box-shadow: 0 24px 60px rgba(15,23,42,.35);
    display: flex; flex-direction: column; overflow: hidden;
    animation: modalPop .2s cubic-bezier(.34,1.56,.64,1);
}
body[data-theme="dark"] .rk-modal {
    background: #0f172a;
    border: 1px solid rgba(30,64,175,.45);
    box-shadow: 0 24px 60px rgba(0,0,0,.6);
}
@keyframes modalPop {
    from { transform: scale(.92) translateY(12px); opacity: 0; }
    to   { transform: scale(1)   translateY(0);    opacity: 1; }
}

/* header */
.rk-modal-hdr {
    display: flex; align-items: flex-start; gap: .75rem;
    padding: 1.1rem 1.25rem .9rem;
    border-bottom: 1px solid rgba(148,163,184,.14);
    flex-shrink: 0;
}
body[data-theme="dark"] .rk-modal-hdr { border-bottom-color: rgba(30,64,175,.3); }

.rk-modal-icon {
    font-size: 1.5rem; line-height: 1; flex-shrink: 0; margin-top: .05rem;
}
.rk-modal-title { font-weight: 800; font-size: 1rem; line-height: 1.2; }
body[data-theme="dark"] .rk-modal-title { color: #e2e8f0; }
.rk-modal-sub { font-size: .78rem; color: #94a3b8; margin-top: .25rem; line-height: 1.4; }
body[data-theme="dark"] .rk-modal-sub { color: #64748b; }

/* highlight box: item yang dicari */
.rk-modal-target {
    margin-top: .4rem; padding: .3rem .7rem; border-radius: 8px;
    background: var(--shp-err-bg); border: 1px solid rgba(185,28,28,.2);
    display: inline-flex; align-items: center; gap: .5rem;
    font-size: .78rem;
}
.rk-modal-target-code {
    font-family: monospace; font-weight: 800;
    color: var(--shp-err); font-size: .85rem;
}
.rk-modal-target-qty { color: #94a3b8; }

.rk-modal-close {
    margin-left: auto; flex-shrink: 0;
    width: 30px; height: 30px; border-radius: 8px;
    border: none; background: rgba(148,163,184,.12);
    font-size: .95rem; cursor: pointer; color: #64748b;
    display: flex; align-items: center; justify-content: center;
    transition: background .1s;
}
.rk-modal-close:hover { background: rgba(148,163,184,.28); }

/* search */
.rk-modal-search {
    padding: .65rem 1.1rem;
    border-bottom: 1px solid rgba(148,163,184,.1);
    flex-shrink: 0;
}
body[data-theme="dark"] .rk-modal-search { border-bottom-color: rgba(30,64,175,.2); }
.rk-modal-search input {
    width: 100%; border-radius: 10px;
    border: 1.5px solid rgba(148,163,184,.3);
    padding: .45rem .85rem; font-size: .88rem;
    background: transparent; transition: border-color .12s;
}
body[data-theme="dark"] .rk-modal-search input { color: #f1f5f9; background: rgba(15,23,42,.4); }
.rk-modal-search input:focus { outline: none; border-color: var(--shp-accent); }

/* list */
.rk-modal-list { overflow-y: auto; flex: 1; padding: .45rem .6rem; }

.rk-pool-item {
    display: flex; align-items: center; gap: .75rem;
    padding: .6rem .75rem; border-radius: 12px;
    cursor: pointer; transition: background .1s;
    border: 1.5px solid transparent;
}
.rk-pool-item:hover    { background: var(--shp-accent-bg); }
.rk-pool-item.selected {
    background: var(--shp-accent-bg);
    border-color: var(--shp-accent);
}
.rk-pool-code { font-family: monospace; font-weight: 800; font-size: .93rem; }
body[data-theme="dark"] .rk-pool-code { color: #e2e8f0; }
.rk-pool-name { font-size: .81rem; color: #6b7280; }
body[data-theme="dark"] .rk-pool-name { color: #94a3b8; }
.rk-pool-qty-badge {
    margin-left: auto; flex-shrink: 0;
    font-weight: 800; font-size: .82rem; white-space: nowrap;
    padding: .15rem .55rem; border-radius: 999px;
    background: var(--shp-ok-bg); color: var(--shp-ok);
    border: 1px solid rgba(21,128,61,.2);
}
.rk-pool-empty {
    text-align: center; color: #94a3b8; padding: 2.5rem 1rem; font-size: .85rem;
}
.rk-pool-empty-icon { font-size: 2rem; margin-bottom: .4rem; opacity: .5; }

/* footer */
.rk-modal-footer {
    padding: .7rem 1.1rem;
    border-top: 1px solid rgba(148,163,184,.1);
    display: flex; align-items: center; gap: .5rem;
    flex-shrink: 0;
    font-size: .76rem; color: #94a3b8;
}
body[data-theme="dark"] .rk-modal-footer { border-top-color: rgba(30,64,175,.2); }

/* Qty stepper di footer modal */
.rk-qty-stepper {
    display: flex; align-items: center; gap: .35rem;
}
.rk-qty-stepper button {
    width: 28px; height: 28px; border-radius: 50%;
    border: 1.5px solid rgba(37,99,235,.4); background: rgba(37,99,235,.07);
    color: #2563eb; font-size: 1rem; font-weight: 900; cursor: pointer;
    display: flex; align-items: center; justify-content: center; line-height:1;
    transition: background .12s;
}
.rk-qty-stepper button:hover:not(:disabled) { background: rgba(37,99,235,.18); }
.rk-qty-stepper button:disabled { opacity: .3; cursor: default; }
.rk-qty-val {
    min-width: 32px; text-align: center; font-size: 1rem; font-weight: 900;
    font-variant-numeric: tabular-nums; color: #1e40af;
}

/* Partial sub display di baris line */
.rk-sub-partial-row {
    display: flex; align-items: center; flex-wrap: wrap; gap: .35rem;
    margin-top: .45rem; padding: .4rem .6rem;
    background: rgba(139,92,246,.06); border: 1px solid rgba(139,92,246,.18);
    border-radius: 10px;
}
.rk-still-short {
    display: inline-flex; align-items: center; gap: .2rem;
    font-size: .7rem; font-weight: 700; color: var(--shp-err);
    background: var(--shp-err-bg); border: 1px solid rgba(185,28,28,.18);
    border-radius: 6px; padding: .06rem .35rem;
}

/* ══════════════════════════════════════════════════
   SISA STOK CARD
══════════════════════════════════════════════════ */
.rk-sisa-card {
    background: var(--card,#fff);
    border: 1.5px solid rgba(148,163,184,.18);
    border-radius: 16px;
    box-shadow: 0 2px 10px rgba(15,23,42,.04);
    margin-top: .65rem; overflow: hidden;
    transition: border-color .2s;
}
body[data-theme="dark"] .rk-sisa-card { border-color: rgba(30,64,175,.35); }

.rk-sisa-card.has-sisa {
    border-color: rgba(245,158,11,.4);
    background: var(--shp-warn-bg);
}
body[data-theme="dark"] .rk-sisa-card.has-sisa { border-color: rgba(245,158,11,.35); }

.rk-sisa-hdr {
    display: flex; align-items: center; gap: .65rem; flex-wrap: wrap;
    padding: .75rem 1.1rem;
    border-bottom: 1px solid rgba(148,163,184,.1);
    cursor: pointer; user-select: none; transition: background .1s;
}
.rk-sisa-hdr:hover { background: rgba(241,245,249,.5); }
body[data-theme="dark"] .rk-sisa-hdr:hover { background: rgba(30,41,59,.5); }
.rk-sisa-card.has-sisa .rk-sisa-hdr { border-bottom-color: rgba(245,158,11,.2); }

.rk-sisa-title { font-size: .82rem; font-weight: 700; }
body[data-theme="dark"] .rk-sisa-title { color: #e2e8f0; }
.rk-sisa-card.has-sisa .rk-sisa-title { color: var(--shp-warn); }

.rk-sisa-body { padding: .65rem 1.1rem .85rem; }

.rk-sisa-row {
    display: flex; align-items: center; gap: .75rem;
    padding: .45rem .5rem; border-radius: 10px;
    transition: background .1s;
}
.rk-sisa-row:hover { background: rgba(148,163,184,.07); }
.rk-sisa-code { font-family: monospace; font-weight: 800; font-size: .9rem; min-width: 80px; }
body[data-theme="dark"] .rk-sisa-code { color: #e2e8f0; }
.rk-sisa-name { font-size: .82rem; color: #6b7280; flex: 1; }
body[data-theme="dark"] .rk-sisa-name { color: #9ca3af; }
.rk-sisa-qty  {
    font-weight: 800; font-size: .92rem; white-space: nowrap;
    padding: .18rem .65rem; border-radius: 999px;
    background: rgba(245,158,11,.12); color: var(--shp-warn);
    border: 1px solid rgba(245,158,11,.3);
}
.rk-sisa-note {
    font-size: .76rem; color: #94a3b8; margin-top: .55rem;
    padding: .45rem .55rem; border-radius: 8px;
    background: rgba(148,163,184,.07);
}

/* ══════════════════════════════════════════════════
   TOAST — sama dengan edit.blade.php
══════════════════════════════════════════════════ */
.shp-toast {
    position: fixed; top: 4.5rem; left: 50%; transform: translateX(-50%);
    z-index: 1090; min-width: 200px; max-width: 360px;
    border-radius: 999px; padding: .5rem 1.2rem;
    font-size: .86rem; font-weight: 700;
    display: none; align-items: center; gap: .45rem;
    box-shadow: 0 12px 32px rgba(15,23,42,.35); pointer-events: none;
}
.shp-toast-ok  { background: var(--shp-ok);  color: #f0fdf4; }
.shp-toast-err { background: var(--shp-err); color: #fee2e2; }
.shp-toast-warn{ background: #f59e0b; color: #fff; }

/* ══════════════════════════════════════════════════
   EMPTY STATE
══════════════════════════════════════════════════ */
.rk-empty {
    text-align: center; padding: 3.5rem 1rem; color: #94a3b8;
}
.rk-empty-icon { font-size: 2.8rem; margin-bottom: .5rem; opacity: .7; }
.rk-empty-title { font-size: .92rem; font-weight: 600; color: #6b7280; }
body[data-theme="dark"] .rk-empty-title { color: #94a3b8; }
.rk-empty-sub { font-size: .8rem; margin-top: .3rem; }

/* ══════════════════════════════════════════════════
   SEARCH BAR
══════════════════════════════════════════════════ */
.rk-searchbar {
    display: flex;
    flex-wrap: wrap;
    gap: .45rem;
    align-items: center;
    margin-bottom: .7rem;
}
.rk-searchbox {
    flex: 1 1 360px;
    display: flex;
    align-items: center;
    gap: .45rem;
    padding: .42rem .55rem;
    border: 1px solid rgba(148,163,184,.24);
    border-radius: 10px;
    background: var(--card,#fff);
    box-shadow: 0 1px 6px rgba(15,23,42,.03);
}
body[data-theme="dark"] .rk-searchbox {
    background: rgba(15,23,42,.92);
    border-color: rgba(30,64,175,.3);
}
.rk-search-icon {
    flex-shrink: 0;
    color: #94a3b8;
    font-size: .92rem;
}
.rk-search-input {
    flex: 1;
    min-width: 0;
    border: none;
    outline: none;
    background: transparent;
    font-size: .9rem;
    color: #0f172a;
}
body[data-theme="dark"] .rk-search-input { color: #e2e8f0; }
.rk-search-input::placeholder { color: #94a3b8; }
.rk-search-clear {
    flex-shrink: 0;
    border: none;
    background: rgba(148,163,184,.1);
    color: #64748b;
    border-radius: 8px;
    width: 28px;
    height: 28px;
    font-size: .88rem;
    cursor: pointer;
}
.rk-search-clear:hover { background: rgba(148,163,184,.2); }
.rk-search-clear[hidden] { display: none; }
.rk-search-meta {
    font-size: .76rem;
    color: #64748b;
    white-space: nowrap;
}
body[data-theme="dark"] .rk-search-meta { color: #94a3b8; }

/* Compact neutral override, aligned with shipment edit */
:root,
.page-theme-shopee,
.page-theme-tiktok {
    --shp-accent: #334155 !important;
    --shp-accent-2: #1f2937 !important;
    --shp-accent-bg: rgba(148,163,184,.08) !important;
    --shp-accent-ring: rgba(148,163,184,.18) !important;
}
.rk-wrap,
body[data-theme="light"] .rk-wrap,
body[data-theme="dark"] .rk-wrap {
    background: transparent !important;
}
.rk-wrap {
    max-width: 1040px;
    padding: .75rem .75rem 4rem;
}
.shp-topbar {
    padding: .45rem .75rem;
    gap: .45rem;
    background: var(--card, #fff);
    border-bottom: 1px solid rgba(148,163,184,.18);
    box-shadow: none;
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
}
body[data-theme="dark"] .shp-topbar {
    background: var(--card, #0f172a);
    border-bottom-color: rgba(148,163,184,.18);
}
.shp-topbar-code {
    font-size: .95rem;
    letter-spacing: 0;
}
.shp-badge,
.shp-pill,
.rk-phase,
.rk-sku-chip,
.rk-sbadge {
    border-radius: 7px;
    letter-spacing: 0;
    text-transform: none;
    box-shadow: none !important;
}
.shp-badge,
.shp-pill {
    padding: .18rem .48rem;
    font-size: .68rem;
    background: transparent !important;
    color: #64748b !important;
    border: 1px solid rgba(148,163,184,.28) !important;
}
.shp-pill-accent,
.rk-phase.active,
.rk-phase.done {
    color: #334155 !important;
    background: transparent !important;
    border-color: rgba(148,163,184,.28) !important;
}
.btn-shp-submit,
.btn-shp-outline,
.rk-act-btn,
.rk-sub-btn-pick {
    border-radius: 7px !important;
    letter-spacing: 0;
    text-transform: none;
    box-shadow: none !important;
}
.btn-shp-submit {
    padding: .34rem .78rem;
    font-size: .76rem;
    font-weight: 700;
    background: #334155 !important;
    border-color: #334155 !important;
}
.btn-shp-submit:hover,
.btn-shp-submit.active:hover {
    background: #1f2937 !important;
    border-color: #1f2937 !important;
}
.btn-shp-outline,
.rk-act-btn,
.rk-sub-btn-pick {
    padding: .28rem .62rem !important;
    font-size: .74rem !important;
    color: #475569 !important;
    background: transparent !important;
    border: 1px solid rgba(148,163,184,.35) !important;
}
.btn-shp-outline:hover,
.rk-act-btn:hover,
.rk-act-btn.on,
.rk-sub-btn-pick:hover {
    background: rgba(148,163,184,.08) !important;
    color: #111827 !important;
}
.btn-return-scan {
    padding: .34rem .78rem !important;
    font-weight: 800 !important;
    color: #1d4ed8 !important;
    background: rgba(37,99,235,.08) !important;
    border-color: rgba(37,99,235,.32) !important;
}
.btn-return-scan:hover {
    background: rgba(37,99,235,.14) !important;
    color: #1e3a8a !important;
}
.rk-phases {
    margin: .55rem 0;
    gap: .28rem;
}
.rk-phase {
    padding: .18rem .52rem;
    font-size: .7rem;
    background: transparent;
    border: 1px solid rgba(148,163,184,.25);
}
.rk-sep {
    display: none;
}
.rk-batch-bar,
.shp-scan-card,
.rk-order-card,
.rk-sisa-card,
.rk-modal {
    border-radius: 8px;
    box-shadow: none !important;
}
.rk-batch-bar {
    padding: .5rem .65rem;
    margin-bottom: .55rem;
    gap: .32rem;
}
.rk-sku-chip {
    padding: .16rem .45rem;
    font-size: .72rem;
    background: transparent;
}
.rk-sku-chip .q,
.rk-sub-lbl,
.rk-qty-val {
    color: #334155;
}
.shp-scan-card {
    padding: .75rem;
    margin-bottom: .55rem;
    border: 1px solid rgba(148,163,184,.22);
}
.shp-scan-card:focus-within,
body[data-theme="dark"] .shp-scan-card:focus-within {
    border-color: rgba(100,116,139,.55);
    box-shadow: none !important;
}
.shp-scan-header {
    margin-bottom: .42rem;
}
.shp-scan-label {
    letter-spacing: .04em;
}
.shp-scan-counter {
    color: #64748b !important;
    font-weight: 600;
}
.shp-scan-input {
    border-radius: 8px;
    border: 1px solid rgba(148,163,184,.35);
    padding: .48rem .7rem;
    font-size: 1.25rem;
    letter-spacing: .08em;
}
.shp-scan-input:focus {
    border-color: rgba(71,85,105,.75);
    box-shadow: none;
}
.shp-scan-input::placeholder {
    font-size: .92rem;
}
.rk-order-card {
    margin-bottom: .5rem;
    border: 1px solid rgba(148,163,184,.18);
}
.rk-order-card.decided-fulfill,
.rk-order-card.decided-pending,
.rk-order-card.decided-skip {
    border-left-width: 2px;
}
.rk-order-hdr {
    padding: .58rem .75rem;
}
.rk-order-body {
    padding: .6rem .75rem .75rem;
}
.rk-order-no {
    font-size: .9rem;
}
.rk-order-store {
    border-radius: 7px;
}
.rk-tbl th {
    padding: .34rem .45rem;
}
.rk-tbl td {
    padding: .42rem .45rem;
}
.rk-action-strip {
    margin-top: .55rem;
    padding-top: .55rem;
}
.rk-sub-row,
.rk-sub-fulfilled,
.rk-sub-partial-row,
.rk-sisa-row,
.rk-modal-target {
    border-radius: 8px;
}
.rk-modal-overlay {
    backdrop-filter: none;
}
.rk-modal {
    max-width: 480px;
    animation: none;
}
.rk-modal-close {
    border-radius: 7px;
}
.rk-modal-search input {
    border-radius: 8px;
}
.rk-pool-item {
    border-radius: 8px;
}
.rk-empty {
    padding: 2.25rem 1rem;
}
.rk-empty-icon {
    display: none;
}
.shp-toast {
    border-radius: 8px;
    box-shadow: none;
}
.rk-modal-footer button {
    border-radius: 7px !important;
    box-shadow: none !important;
}
@media (max-width: 768px) {
    .rk-wrap {
        padding: .5rem .5rem 4.75rem;
    }
    .shp-topbar {
        position: sticky;
        padding: .5rem;
        gap: .38rem;
    }
    .shp-topbar-code {
        flex: 1 1 auto;
        min-width: 145px;
        font-size: 1.05rem;
    }
    .shp-topbar-spacer,
    .shp-badge,
    .shp-topbar > .shp-pill:not(.shp-pill-accent) {
        display: none !important;
    }
    .shp-pill,
    .btn-shp-outline,
    .btn-shp-submit {
        min-height: 38px;
        font-size: .82rem !important;
    }
    .shp-pill-accent {
        margin-left: auto;
    }
    .btn-return-scan {
        width: 100%;
        justify-content: center;
    }
    .btn-shp-submit {
        width: 100%;
        order: 5;
    }
    .rk-phases,
    .rk-batch-bar {
        display: none;
    }
    .shp-scan-card {
        padding: .7rem;
        margin-bottom: .5rem;
    }
    .shp-scan-label,
    .shp-scan-counter {
        font-size: .74rem;
    }
    .shp-scan-input {
        min-height: 54px;
        font-size: 1.42rem;
        padding: .62rem .72rem;
        letter-spacing: .06em;
    }
    .shp-scan-input::placeholder {
        font-size: 1rem;
    }
    .rk-order-hdr {
        padding: .62rem .65rem;
    }
    .rk-order-body {
        padding: .55rem .65rem .7rem;
    }
    .rk-order-no {
        width: 100%;
    }
    .rk-order-store,
    .rk-order-chev {
        display: none;
    }
    .rk-tbl th {
        display: none;
    }
    .rk-tbl,
    .rk-tbl tbody,
    .rk-tbl tr,
    .rk-tbl td {
        display: block;
        width: 100%;
    }
    .rk-tbl tr {
        padding: .45rem 0;
        border-bottom: 1px solid rgba(148,163,184,.12);
    }
    .rk-tbl td {
        border: 0;
        padding: .16rem 0;
        font-size: .84rem;
    }
    .rk-action-strip {
        gap: .4rem;
    }
    .rk-act-btn {
        min-height: 36px;
        flex: 1 1 auto;
    }
    .rk-modal-overlay {
        align-items: flex-end;
        padding: .5rem;
    }
    .rk-modal {
        max-height: 86vh;
        border-radius: 10px;
    }
    .rk-modal-hdr {
        padding: .85rem .9rem .7rem;
    }
    .rk-modal-search,
    .rk-modal-footer {
        padding-inline: .85rem;
    }
    .rk-empty-title {
        font-size: .9rem;
    }
}

/* ══════════════════════════════════════════════════
   WORKFLOW NAV + STICKY NEXT ACTION
══════════════════════════════════════════════════ */
.rk-flow {
    display: flex;
    align-items: center;
    gap: .3rem;
    flex-wrap: wrap;
    margin: .55rem 0 .45rem;
    padding: .42rem .55rem;
    border: 1px solid rgba(148,163,184,.18);
    border-radius: 8px;
    background: var(--card, #fff);
}
.rk-flow-step {
    display: inline-flex;
    align-items: center;
    gap: .32rem;
    min-height: 28px;
    padding: .18rem .55rem;
    border: 1px solid rgba(148,163,184,.25);
    border-radius: 7px;
    color: #64748b;
    font-size: .72rem;
    font-weight: 700;
    white-space: nowrap;
}
.rk-flow-step.done { color: #334155; background: rgba(148,163,184,.08); }
.rk-flow-step.active { color: #fff; background: #334155; border-color: #334155; }
.rk-flow-sep { color: #cbd5e1; font-size: .72rem; }
.shp-topbar .rk-topbar-primary { display: inline-flex; align-items: center; gap: .35rem; }
.shp-topbar .rk-topbar-primary:not(:disabled) { opacity: 1; pointer-events: auto; }
@media (max-width: 768px) {
    .rk-flow { display: none; }
    .shp-topbar .rk-topbar-utility { display: none; }
}

/* Visual hierarchy: action states are intentionally color-coded. */
.shp-topbar .rk-topbar-primary {
    color: #fff !important;
    background: #2563eb !important;
    border-color: #2563eb !important;
    font-weight: 850;
    box-shadow: 0 3px 10px rgba(37,99,235,.28) !important;
}
.shp-topbar .rk-topbar-primary:hover:not(:disabled) {
    color: #fff !important;
    background: #1d4ed8 !important;
    border-color: #1d4ed8 !important;
}
.shp-topbar .rk-topbar-primary:disabled {
    color: #64748b !important;
    background: #e2e8f0 !important;
    border-color: #cbd5e1 !important;
    box-shadow: none !important;
}
.shp-topbar .shp-pill-accent {
    color: #1d4ed8 !important;
    background: #eff6ff !important;
    border-color: #93c5fd !important;
}
.shp-scan-input:focus,
.rk-searchbox:focus-within {
    border-color: #2563eb !important;
    box-shadow: 0 0 0 3px rgba(37,99,235,.16) !important;
}
.rk-tab.active { color: #2563eb !important; border-bottom-color: #2563eb !important; }
.rk-act-btn.fulfill {
    color: #166534 !important;
    border-color: #86efac !important;
    background: #f0fdf4 !important;
}
.rk-act-btn.fulfill:hover,
.rk-act-btn.fulfill.on {
    color: #fff !important;
    border-color: #16a34a !important;
    background: #16a34a !important;
}
.rk-act-btn.pending {
    color: #92400e !important;
    border-color: #fcd34d !important;
    background: #fffbeb !important;
}
.rk-act-btn.pending:hover,
.rk-act-btn.pending.on {
    color: #fff !important;
    border-color: #d97706 !important;
    background: #d97706 !important;
}
.rk-act-btn.skip {
    color: #64748b !important;
    border-color: #cbd5e1 !important;
    background: #f8fafc !important;
}
.rk-act-btn.skip:hover,
.rk-act-btn.skip.on {
    color: #fff !important;
    border-color: #64748b !important;
    background: #64748b !important;
}
.rk-item-first-hidden { display: none !important; }
.rk-item-first-panel {
    margin-bottom: .75rem;
    padding: 1rem 1.1rem;
    border: 1px solid rgba(37,99,235,.18);
    border-radius: 12px;
    background: linear-gradient(135deg, rgba(239,246,255,.95), #fff);
}
.rk-item-first-title { color: #1e40af; font-weight: 900; font-size: .9rem; }
.rk-item-first-sub { margin-top: .2rem; color: #64748b; font-size: .78rem; }
.rk-item-first-map {
    display: flex; align-items: center; gap: .45rem; flex-wrap: wrap;
    margin-top: .75rem; padding: .5rem .65rem; border-radius: 8px;
    background: rgba(255,255,255,.8); border: 1px solid rgba(37,99,235,.12);
    color: #475569; font-size: .76rem; font-weight: 700;
}
.rk-item-first-list { display: grid; gap: .35rem; margin-top: .65rem; }
.rk-item-first-row { display: flex; align-items: center; gap: .5rem; padding: .45rem .55rem; border-radius: 7px; background: #fff; }
.rk-item-first-code { color: #0f172a; font-family: ui-monospace,SFMono-Regular,Menlo,monospace; font-weight: 900; }
.rk-item-first-name { color: #64748b; font-size: .74rem; flex: 1; }
.rk-item-first-qty { color: #1d4ed8; font-weight: 900; }

/* Semua tombol aksi utama memakai teks putih agar tetap focal dan terbaca. */
.shp-topbar .rk-topbar-primary,
.shp-topbar .rk-topbar-primary:disabled {
    color: #fff !important;
}
.shp-topbar .btn-return-scan {
    color: #fff !important;
    background: #2563eb !important;
    border-color: #2563eb !important;
}
.shp-topbar .btn-return-scan:hover {
    color: #fff !important;
    background: #1d4ed8 !important;
    border-color: #1d4ed8 !important;
}
.shp-topbar .rk-topbar-allocate {
    color: #fff !important;
    background: #0f766e !important;
    border-color: #0f766e !important;
}
.shp-topbar .rk-topbar-allocate:hover:not(:disabled) {
    color: #fff !important;
    background: #0d5f59 !important;
    border-color: #0d5f59 !important;
}
.shp-topbar .rk-topbar-allocate:disabled {
    color: #64748b !important;
    background: #e2e8f0 !important;
    border-color: #cbd5e1 !important;
}
.rk-act-btn.fulfill,
.rk-act-btn.fulfill:hover,
.rk-act-btn.fulfill.on {
    color: #fff !important;
    background: #16a34a !important;
    border-color: #16a34a !important;
}
.rk-act-btn.pending,
.rk-act-btn.pending:hover,
.rk-act-btn.pending.on {
    color: #fff !important;
    background: #d97706 !important;
    border-color: #d97706 !important;
}
.rk-act-btn.skip,
.rk-act-btn.skip:hover,
.rk-act-btn.skip.on,
.rk-sub-btn-pick,
.rk-sub-btn-pick:hover {
    color: #fff !important;
    background: #64748b !important;
    border-color: #64748b !important;
}
</style>
@endpush

@section('content')
@php
    $totalLines = $shipment->lines->count();
    $totalQty   = $shipment->lines->sum('qty_scanned');
    $isItemFirst = ($shipment->scan_mode ?? 'item_first') === 'item_first';
@endphp

<div class="shp-topbar">
    <a href="{{ route('sales.shipments.edit', $shipment) }}" class="btn-shp-outline btn-return-scan btn-sm" style="text-decoration:none;font-size:0.75rem;padding:0.25rem 0.6rem;">
        <span aria-hidden="true">&larr;</span>
        <span>Scan Item</span>
    </a>
    @if (!$isItemFirst)
        <a href="/marketplace/orders" class="btn-shp-outline btn-sm rk-topbar-utility" style="text-decoration:none; background:#f8fafc; border-color:#e2e8f0; color:#475569;font-size:0.75rem;padding:0.25rem 0.6rem;">
            📦 List Order
        </a>
    @endif
    <span class="shp-topbar-code">{{ $shipment->code }}</span>
    <span class="shp-badge shp-badge-draft">Draft</span>
    <span class="shp-topbar-spacer"></span>
    <button type="button" id="gfidScanSoundToggle" class="gf-scan-sound-toggle" aria-pressed="true">🔊 Suara ON</button>
    <span class="shp-pill">Batch <b>{{ $totalLines }}</b> SKU</span>
    <span class="shp-pill shp-pill-accent">Qty <b>{{ number_format($totalQty, 0, ',', '.') }}</b></span>
    <span class="shp-pill" id="topPillOrders" style="display:none">Pesanan <b id="topOrderCount">0</b></span>
    <button type="button" id="topConfirmBtn" class="btn-shp-submit rk-topbar-primary" disabled>
        <i class="bi bi-arrow-right-short" aria-hidden="true"></i>
        Konfirmasi Pesanan
    </button>
</div>

<div class="rk-wrap">

    <nav class="rk-flow" aria-label="Alur shipment">
        <span class="rk-flow-step done"><span>1</span> Scan Item</span>
        <span class="rk-flow-sep" aria-hidden="true">→</span>
        <span class="rk-flow-step active"><span>2</span> Rekonsiliasi</span>
        <span class="rk-flow-sep" aria-hidden="true">→</span>
        <span class="rk-flow-step"><span>3</span> Konfirmasi &amp; Submit</span>
    </nav>

    @if ($isItemFirst)
        <div class="rk-item-first-panel">
            <div class="rk-item-first-title">Mapping Order &amp; Item Otomatis</div>
            <div class="rk-item-first-sub">Mode Scan Item aktif. Order yang sudah tercatat ditampilkan di bawah. Alokasikan item batch ke masing-masing order terlebih dahulu, lalu lanjutkan ke konfirmasi.</div>
            <div class="rk-item-first-map">
                <span>Order:</span>
                <strong>{{ $shipment->orderScans->count() === 1 ? $shipment->orderScans->first()->order_no : 'Item shipment' }}</strong>
                <span style="margin-left:auto;color:#2563eb">{{ $totalLines }} SKU · {{ number_format($totalQty, 0, ',', '.') }} qty</span>
            </div>
            <div class="rk-item-first-list">
                @forelse ($batchPool as $item)
                    <div class="rk-item-first-row">
                        <span class="rk-item-first-code">{{ $item['item_code'] }}</span>
                        <span class="rk-item-first-name">{{ $item['item_name'] }}</span>
                        <span class="rk-item-first-qty">×{{ number_format($item['qty'], 0, ',', '.') }}</span>
                    </div>
                @empty
                    <div class="rk-batch-empty">Belum ada item yang discan.</div>
                @endforelse
            </div>
            <div style="display:flex;align-items:center;gap:.55rem;flex-wrap:wrap;margin-top:.8rem">
                <button type="button" id="itemFirstAllocateBtn" class="btn-shp-submit" disabled>
                    <i class="bi bi-diagram-3" aria-hidden="true"></i> Alokasikan Item ke Order
                </button>
                <span id="itemFirstAllocationHint" style="color:#64748b;font-size:.74rem">Menunggu order yang tercatat.</span>
            </div>
        </div>
    @endif


    {{-- TABS --}}
    <div class="rk-tabs" role="tablist">
        <button type="button" class="rk-tab active" data-tab="pesanan">Pesanan <span class="rk-tab-count" id="rkOrderCount">0</span></button>
        <button type="button" class="rk-tab" data-tab="sisa">Belum Alokasi <span class="rk-tab-count" id="rkSisaCount">0</span></button>
        <button type="button" class="rk-tab" data-tab="batch">Isi Batch <span class="rk-tab-count">{{ $batchPool->count() }}</span></button>
    </div>

    {{-- TAB: PESANAN --}}
    <div class="rk-tabpane active" id="rk-tab-pesanan" role="tabpanel">
        {{-- HERO SCAN CARD --}}
        <div class="shp-scan-card" style="padding: 1rem 1.25rem 0.8rem; margin-bottom: 0.75rem; border-radius: 12px; border-width: 1px;">
            <div class="shp-scan-header" style="margin-bottom: 0.35rem;">
                <span class="shp-scan-label" style="font-size:0.75rem;">{{ $isItemFirst ? 'Order untuk Alokasi' : 'Scan Pesanan' }}</span>
                <span class="shp-scan-counter" id="scanCounter" style="font-size:0.75rem;">0 pesanan</span>
            </div>

            @if ($isItemFirst)
                <div style="color:#64748b;font-size:.82rem;line-height:1.45">
                    Order sudah tercatat dari shipment ini. Buka detail order di bawah, lalu klik <strong>Alokasikan Item ke Order</strong> untuk membagi item batch secara otomatis.
                </div>
            @else
                <input type="text" id="orderInput" class="shp-scan-input"
                       placeholder="Scan / ketik no pesanan lalu Enter"
                       style="font-size: 1.25rem; padding: 0.5rem 0.85rem; border-width: 1.5px; border-radius: 8px;"
                       autocomplete="off" spellcheck="false" autofocus>
            @endif

        </div>

        <div class="rk-searchbar">
            <div class="rk-searchbox">
                <span class="rk-search-icon" aria-hidden="true">⌕</span>
                <input type="search" id="orderSearchInput" class="rk-search-input"
                       placeholder="Cari no resi, no pesanan, kode atau nama item"
                       autocomplete="off" spellcheck="false">
                <button type="button" id="orderSearchClear" class="rk-search-clear" aria-label="Hapus pencarian" hidden>x</button>
            </div>
            <div class="rk-search-meta" id="orderSearchMeta">Menampilkan semua pesanan</div>
        </div>

        {{-- ORDER LIST --}}
        <div id="orderList"></div>

        {{-- EMPTY STATE --}}
        <div class="rk-empty" id="emptyState">
            <div class="rk-empty-icon"></div>
            <div class="rk-empty-title">Scan nomor pesanan</div>
            <div class="rk-empty-sub">Bisa dari barcode scanner atau ketik manual lalu tekan Enter</div>
        </div>

    </div>

    {{-- TAB: SISA STOK --}}
    <div class="rk-tabpane" id="rk-tab-sisa" role="tabpanel">
        <div id="sisaCard" style="display:none"></div>
    </div>

    {{-- TAB: ISI BATCH --}}
    <div class="rk-tabpane" id="rk-tab-batch" role="tabpanel">
        <div class="rk-order-card">
            <div class="rk-batch-head">
                <div>
                    <div class="rk-batch-title">Item Batch</div>
                    <div class="rk-batch-sub">Ringkasan barang yang discan untuk shipment ini.</div>
                </div>
                <span class="rk-batch-pill">{{ $batchPool->count() }} SKU</span>
            </div>
            @if ($batchPool->isEmpty())
                <div class="rk-batch-empty">Belum ada item di batch.</div>
            @else
                @php
                    $batchGrouped = $batchPool
                        ->groupBy(fn ($i) => $i['category_name'] ?? 'Tanpa Kategori')
                        ->sortKeys();
                @endphp
                <div style="overflow-x:auto">
                    <table class="rk-tbl rk-batch-tbl">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th style="text-align:right">Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($batchGrouped as $catName => $items)
                                @php
                                    $catQty = collect($items)->sum('qty');
                                    $catSku = count($items);
                                @endphp
                                <tr class="rk-batch-group">
                                    <td>
                                        {{ $catName }}
                                        <span class="rk-batch-group-count">· {{ $catSku }} SKU</span>
                                    </td>
                                    <td style="text-align:right">{{ number_format($catQty, 0, ',', '.') }} pcs</td>
                                </tr>
                                @foreach ($items as $item)
                                    <tr>
                                        <td>
                                            <div class="rk-icode">{{ $item['item_code'] }}</div>
                                            <div class="rk-batch-name">{{ $item['item_name'] }}</div>
                                        </td>
                                        <td style="text-align:right">
                                            <span class="rk-qty-badge">{{ number_format($item['qty'], 0, ',', '.') }} pcs</span>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                            <tr class="rk-batch-total">
                                <td style="text-align:right">Total</td>
                                <td style="text-align:right">{{ number_format($totalQty, 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

</div>

{{-- SUBSTITUSI MODAL --}}
<div class="rk-modal-overlay" id="drawerOverlay">
    <div class="rk-modal">
        <div class="rk-modal-hdr">
            <span class="rk-modal-icon"></span>
            <div style="flex:1;min-width:0">
                <div class="rk-modal-title">Pilih Item Pengganti</div>
                <div class="rk-modal-sub">Dari sisa stok batch yang belum dialokasikan</div>
                <div class="rk-modal-target" id="drawerTargetBox" style="display:none">
                    <span style="color:#94a3b8;font-size:.74rem">Ganti kurang:</span>
                    <span class="rk-modal-target-code" id="drawerForItem"></span>
                    <span class="rk-modal-target-qty">−<span id="drawerNeedQty"></span> pcs</span>
                </div>
            </div>
            <button class="rk-modal-close" onclick="closeDrawer()">x</button>
        </div>
        <div class="rk-modal-search">
            <input type="text" id="drawerSearch" placeholder="Cari kode atau nama barang"
                   oninput="filterDrawer(this.value)">
        </div>
        <div class="rk-modal-list" id="drawerList"></div>

        {{-- Footer idle: hanya count + batal --}}
        <div class="rk-modal-footer" id="drawerFooterIdle">
            <span id="drawerFooterCount"></span>
            <span style="flex:1"></span>
            <button onclick="closeDrawer()"
                    style="border-radius:999px;border:1px solid rgba(148,163,184,.4);
                           background:transparent;color:#6b7280;font-size:.76rem;
                           font-weight:600;padding:.25rem .85rem;cursor:pointer">
                Batal
            </button>
        </div>

        {{-- Footer confirm: item dipilih + qty stepper + tombol konfirmasi --}}
        <div class="rk-modal-footer" id="drawerFooterConfirm" style="display:none;flex-direction:column;gap:.5rem;align-items:stretch;padding:.75rem 1rem;">
            <div style="display:flex;align-items:center;gap:.5rem;">
                <div style="flex:1;min-width:0;">
                    <div style="font-size:.64rem;font-weight:800;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;">Item pengganti dipilih</div>
                    <div id="drawerSelCode" style="font-size:.88rem;font-weight:900;color:#1e40af;"></div>
                    <div id="drawerSelName" style="font-size:.75rem;color:#6b7280;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"></div>
                </div>
                <div style="display:flex;flex-direction:column;align-items:center;gap:.15rem;">
                    <div style="font-size:.6rem;font-weight:800;color:#9ca3af;text-transform:uppercase;letter-spacing:.04em;">Qty substitusi</div>
                    <div class="rk-qty-stepper">
                        <button type="button" id="drawerQtyMinus" onclick="adjDrawerQty(-1)">−</button>
                        <span class="rk-qty-val" id="drawerQtyVal">1</span>
                        <button type="button" id="drawerQtyPlus"  onclick="adjDrawerQty(+1)">+</button>
                        <span style="font-size:.72rem;color:#9ca3af;font-weight:600">pcs</span>
                    </div>
                    <div id="drawerQtyHint" style="font-size:.62rem;color:#94a3b8;"></div>
                </div>
            </div>
            <div style="display:flex;gap:.5rem;">
                <button onclick="closeDrawer()"
                        style="flex:1;border-radius:999px;border:1px solid rgba(148,163,184,.4);
                               background:transparent;color:#6b7280;font-size:.78rem;
                               font-weight:700;padding:.35rem .7rem;cursor:pointer;">
                    Batal
                </button>
                <button onclick="confirmPickSub()"
                        style="flex:2;border-radius:999px;border:none;
                               background:var(--shp-accent,#2563eb);color:#fff;font-size:.82rem;
                               font-weight:800;padding:.38rem .9rem;cursor:pointer;">
                    Konfirmasi Substitusi
                </button>
            </div>
        </div>
    </div>
</div>

<div id="shpToast" class="shp-toast"></div>
@endsection

@push('scripts')
<script>
(function () {
'use strict';

window.GFID?.bindScanSoundToggle(document.getElementById('gfidScanSoundToggle'));

/* ── URLs ── */
const CSRF       = document.querySelector('meta[name="csrf-token"]')?.content || '';
const MATCH_URL  = @json(parse_url(route('sales.shipments.rekon_match', $shipment), PHP_URL_PATH));
const APPLY_URL  = @json(parse_url(route('sales.shipments.rekon_apply', $shipment), PHP_URL_PATH));
const EDIT_URL   = @json(parse_url(route('sales.shipments.edit', $shipment), PHP_URL_PATH));
const CONFIRM_URL = @json(parse_url(route('sales.shipments.confirm_orders', $shipment), PHP_URL_PATH));
const FMT        = new Intl.NumberFormat('id-ID');
const RESET_URL  = @json(parse_url(route('sales.shipments.rekon_reset_scans', $shipment), PHP_URL_PATH));
const UPDATE_SCAN_URL = {!! json_encode(parse_url(route('sales.shipments.rekon_update_scan', [$shipment, '__NO__']), PHP_URL_PATH)) !!};
const LINK_SCAN_URL = {!! json_encode(parse_url(route('sales.shipments.rekon_link_scan', [$shipment, '__NO__']), PHP_URL_PATH)) !!};
const SERVER_ORDER_SCANS = @json($savedOrderScans ?? []);
const SHIPMENT_TYPE = @json($shipment->shipment_type ?? 'marketplace');
const ITEM_FIRST = @json(($shipment->scan_mode ?? 'item_first') === 'item_first');
const IS_OWNER = @json(auth()->user()?->hasRole('owner') ?? false);

// Batch pool dari server — sumber kebenaran qty total per item
const BATCH_POOL = @json(array_values($batchPool->toArray()));

/* ── State ── */
let poolUsed = {};
let orders   = [];
let drawerCtx        = null;
let drawerPool       = [];
let drawerPendingSub = null;   // item yang di-tap di list, belum dikonfirmasi
let drawerPendingQty = 1;      // qty yang akan di-sub
let orderSearchQuery = '';

/* ── Persistence ── */
function normalizeOrderNo(no) {
    const value = String(no ?? '').trim();
    if (!value || ['undefined', 'null', 'nan'].includes(value.toLowerCase())) return '';
    return value.toUpperCase();
}

function normalizeSearchText(value) {
    return String(value ?? '')
        .toLowerCase()
        .replace(/\s+/g, ' ')
        .trim();
}

function getOrderSearchBlob(o) {
    const parts = [
        o?.no,
        o?.order?.order_no,
        o?.order?.invoice_code,
        o?.order?.shipping_awb_no,
        o?.order?.store_name,
        o?.order?.store_code,
        o?.order?.status,
        o?.scanned_at,
    ];

    (o?.order?.lines || []).forEach(line => {
        parts.push(line?.item_code, line?.item_name, line?.sub_code, line?.sub_name);
    });

    return normalizeSearchText(parts.filter(Boolean).join(' '));
}

function orderMatchesSearch(o, q) {
    const query = normalizeSearchText(q);
    if (!query) return true;

    const blob = getOrderSearchBlob(o);
    return query
        .split(' ')
        .filter(Boolean)
        .every(part => blob.includes(part));
}

function getVisibleOrders() {
    if (!orderSearchQuery.trim()) {
        return orders.map((o, idx) => ({ order: o, idx, displayNo: idx + 1 }));
    }

    return orders
        .map((o, idx) => ({ order: o, idx }))
        .filter(row => orderMatchesSearch(row.order, orderSearchQuery))
        .map((row, visibleIdx) => ({ ...row, displayNo: visibleIdx + 1 }));
}

function updateSearchUi(visibleCount) {
    if (orderSearchInput && orderSearchClear) {
        orderSearchClear.hidden = !orderSearchQuery.trim();
    }

    if (!orderSearchMeta) return;

    if (!orders.length) {
        orderSearchMeta.textContent = 'Belum ada pesanan tersimpan';
        return;
    }

    if (orderSearchQuery.trim()) {
        orderSearchMeta.textContent = `Menampilkan ${visibleCount} dari ${orders.length} pesanan`;
    } else {
        orderSearchMeta.textContent = `Menampilkan semua ${orders.length} pesanan`;
    }
}

function setOrderSearchQuery(value) {
    orderSearchQuery = String(value || '');
    renderAll();
}
function rebuildPoolUsed() {
    poolUsed = {};
    orders.forEach(o => {
        if (o.decision === 'skip') return; // Abaikan pesanan yang di-skip
        if (o.order && o.order.lines) {
            o.order.lines.forEach(l => {
                if (l.item_id && l.qty_alloc) {
                    poolUsed[l.item_id] = (poolUsed[l.item_id] || 0) + l.qty_alloc;
                }
            });
        }
    });
}

async function recalculateAllocations() {
    if (!orders.length) return;

    // 1. Inisialisasi pool murni dari BATCH_POOL
    const pool = {};
    BATCH_POOL.forEach(p => pool[p.item_id] = p.qty);

    let anyChanged = false;
    const pendingSaves = [];

    // 2. Evaluasi ulang pesanan berurutan
    orders.forEach((o, idx) => {
        if (!o.found || !o.order || !o.order.lines) return;

        let changed = false;
        let hasShort = false;

        if (o.decision === 'skip') {
            o.order.lines.forEach(l => {
                if (l.qty_alloc !== 0) {
                    l.qty_alloc = 0;
                    l.qty_short = l.qty_need;
                    l.status = 'short';
                    changed = true;
                }
            });
            if (changed) {
                anyChanged = true;
                pendingSaves.push(saveOrderScan(idx));
            }
            return;
        }

        o.order.lines.forEach(l => {
            const need = l.qty_need;
            const sub = o.subs ? o.subs[l.item_id] : null;
            
            if (sub) {
                if (l.qty_alloc !== 0) {
                    l.qty_alloc = 0;
                    l.qty_short = need;
                    l.status = 'short';
                    changed = true;
                }
                const subQty = Math.min(sub.qty, need);
                if (pool[sub.sub_id]) {
                    pool[sub.sub_id] = Math.max(0, pool[sub.sub_id] - subQty);
                }
                if (subQty < need) hasShort = true;
            } else {
                let alloc = 0;
                if (pool[l.item_id] > 0) {
                    alloc = Math.min(need, pool[l.item_id]);
                    pool[l.item_id] -= alloc;
                }
                const short = need - alloc;
                
                if (l.qty_alloc !== alloc) {
                    l.qty_alloc = alloc;
                    l.qty_short = short;
                    l.status = short > 0 ? 'short' : 'ok';
                    changed = true;
                }
                if (short > 0) hasShort = true;
            }
        });

        const newDecision = hasShort ? 'pending' : 'fulfill';
        // Otomatis ubah status jika stok mencukupi
        if (o.decision !== newDecision) {
            o.decision = newDecision;
            changed = true;
        }

        if (changed) {
            anyChanged = true;
            pendingSaves.push(saveOrderScan(idx));
        }
    });

    if (anyChanged) {
        toast('info', 'Alokasi stok disesuaikan otomatis.');
        rebuildPoolUsed();
    }

    if (pendingSaves.length) {
        const results = await Promise.allSettled(pendingSaves);
        if (results.some(result => result.status === 'rejected')) {
            toast('err', 'Sebagian alokasi gagal disimpan. Silakan coba lagi.');
        }
    }
}
function saveState() {
    // No-op: DB stores the state now.
}

function getActualAllocations(order) {
    const allocations = {};

    (order?.order?.lines || []).forEach(line => {
        const substitution = order.subs?.[line.item_id];
        if (substitution?.sub_id && Number(substitution.qty) > 0) {
            const qty = Math.min(Number(substitution.qty), Number(line.qty_need || 0));
            allocations[substitution.sub_id] = (allocations[substitution.sub_id] || 0) + qty;
            return;
        }

        if (line.item_id && Number(line.qty_alloc) > 0) {
            allocations[line.item_id] = (allocations[line.item_id] || 0) + Number(line.qty_alloc);
        }
    });

    return Object.entries(allocations).map(([item_id, qty]) => ({
        item_id: Number(item_id),
        qty: Number(qty),
    }));
}

async function saveOrderScan(idx) {
    const o = orders[idx];
    if (!o) return null;
    const orderNo = normalizeOrderNo(o.no || o.order?.order_no);
    if (!orderNo) throw new Error('Nomor order tidak tersedia. Scan ulang nomor order tersebut.');
    const url = UPDATE_SCAN_URL.replace('__NO__', encodeURIComponent(orderNo));
    const response = await fetch(url, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            decision: o.decision,
            subs: o.subs,
            allocations: getActualAllocations(o),
        })
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok || data.status !== 'ok') {
        throw new Error(data.message || 'Failed to sync scan');
    }
    return data;
}

window.editManualOrder = function(idx, oldNo) {
    const newNo = prompt('Masukkan nomor pesanan baru:', oldNo);
    if (!newNo || newNo.trim() === oldNo) return;
    
    const url = UPDATE_SCAN_URL.replace('__NO__', encodeURIComponent(oldNo));
    fetch(url, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ new_order_no: newNo.trim() })
    }).then(r => r.json()).then(res => {
        if (res.status === 'ok') {
            orders[idx].no = newNo.trim();
            if (orders[idx].order) orders[idx].order.order_no = newNo.trim();
            toast('ok', 'Nomor pesanan berhasil diubah.');
            renderAll();
        } else {
            toast('err', res.message || 'Gagal mengubah nomor pesanan.');
        }
    }).catch(err => toast('err', 'Terjadi kesalahan sistem.'));
};

window.deleteManualOrder = function(idx, no) {
    if (!confirm('Hapus pesanan ' + no + ' dari daftar?')) return;
    
    const url = UPDATE_SCAN_URL.replace('__NO__', encodeURIComponent(no));
    fetch(url, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': CSRF,
            'Accept': 'application/json'
        }
    }).then(r => r.json()).then(res => {
        if (res.status === 'ok') {
            orders.splice(idx, 1);
            toast('ok', 'Pesanan dihapus.');
            rebuildPoolUsed();
            recalculateAllocations();
            renderAll();
        } else {
            toast('err', res.message || 'Gagal menghapus pesanan.');
        }
    }).catch(err => toast('err', 'Terjadi kesalahan sistem.'));
};

async function loadState() {
    orders = (Array.isArray(SERVER_ORDER_SCANS) ? SERVER_ORDER_SCANS : [])
        .map((row) => {
            const no = normalizeOrderNo(
                row?.no
                    || row?.order_no
                    || row?.order?.order_no
                    || row?.order?.code
            );
            if (!no) return null;

            const order = row?.order && typeof row.order === 'object'
                ? { ...row.order }
                : {};
            order.order_no = normalizeOrderNo(order.order_no || order.code) || no;

            return { ...row, no, order };
        })
        .filter(Boolean);
    rebuildPoolUsed();
    // Item-first harus melewati aksi alokasi yang terlihat operator sebelum
    // lanjut ke halaman konfirmasi. Mode order-first tetap otomatis.
    if (!ITEM_FIRST) await recalculateAllocations();
    return orders.length ? 'ok' : 'empty';
}
function clearState() {
    orders = [];
    poolUsed = {};
}

/* ── DOM ── */
const orderInput    = document.getElementById('orderInput');
const orderSearchInput = document.getElementById('orderSearchInput');
const orderSearchClear = document.getElementById('orderSearchClear');
const orderSearchMeta = document.getElementById('orderSearchMeta');
const orderList     = document.getElementById('orderList');
const emptyState    = document.getElementById('emptyState');
const scanCounter   = document.getElementById('scanCounter');
const topPillOrders = document.getElementById('topPillOrders');
const topOrderCount = document.getElementById('topOrderCount');
const topConfirmBtn = document.getElementById('topConfirmBtn');
const itemFirstAllocateBtn = document.getElementById('itemFirstAllocateBtn');
const itemFirstAllocationHint = document.getElementById('itemFirstAllocationHint');

/* ── Toast ── */
const toastEl = document.getElementById('shpToast');
let toastT;
function toast(type, msg) {
    clearTimeout(toastT);
    toastEl.className = 'shp-toast shp-toast-' + type;
    toastEl.textContent = msg;
    toastEl.style.display = 'flex'; toastEl.style.opacity = '1';
    toastT = setTimeout(() => {
        toastEl.style.transition = 'opacity .3s'; toastEl.style.opacity = '0';
        setTimeout(() => { toastEl.style.display = 'none'; toastEl.style.transition = ''; }, 320);
    }, 2000);
}

/* ── Beep (AudioContext) ── */
let audioCtx = null;

function getAudioContext() {
    try {
        const Ctx = window.AudioContext || window.webkitAudioContext;
        if (!Ctx) return null;
        if (!audioCtx) audioCtx = new Ctx();
        return audioCtx;
    } catch {
        return null;
    }
}

function unlockAudio() {
    const ctx = getAudioContext();
    if (ctx && ctx.state === 'suspended') {
        ctx.resume().catch(() => {});
    }
}

function tone(freq, dur = 0.14, vol = 0.2, delay = 0, type = 'sine') {
    if (window.GFID && typeof window.GFID.isScanSoundEnabled === 'function' && !window.GFID.isScanSoundEnabled()) return;
    try {
        const ctx = getAudioContext();
        if (!ctx) return;
        const play = () => {
            const start = ctx.currentTime + delay;
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = type;
            osc.frequency.value = freq;
            osc.connect(gain);
            gain.connect(ctx.destination);
            gain.gain.setValueAtTime(vol, start);
            gain.gain.exponentialRampToValueAtTime(0.001, start + dur);
            osc.start(start);
            osc.stop(start + dur);
        };

        if (ctx.state === 'suspended') {
            ctx.resume().then(play).catch(() => {});
            return;
        }

        play();
    } catch {
    }
}

/* ── Named sounds ── */
function playConfiguredSound(eventKey, fallback) {
    if (window.GFID && typeof window.GFID.playScanSound === 'function') {
        return window.GFID.playScanSound(eventKey, fallback);
    }
    fallback();
}
/* order ditemukan, semua stok cukup — 3-nada arpeggio naik, nyaring */
function sndOrderReady()   { playConfiguredSound('order_ready', () => { tone(880, 0.13, 0.80, 0, 'square'); tone(1100, 0.13, 0.78, 0.14, 'square'); tone(1320, 0.16, 0.75, 0.28, 'square'); }); }
/* order ditemukan, stok kurang — ok lalu turun (waspada) */
function sndOrderPartial() { playConfiguredSound('order_partial', () => { tone(880, 0.13, 0.78, 0, 'square'); tone(660,  0.16, 0.75, 0.14, 'triangle'); }); }
/* order tidak ditemukan di batch — 2-nada rendah agak nyaring */
function sndOrderNoMatch() { playConfiguredSound('order_no_match', () => { tone(660, 0.18, 0.72, 0, 'triangle'); tone(500, 0.18, 0.70, 0.19, 'triangle'); }); }
/* duplikat / blocked — buzz pendek double */
function sndGuard()        { playConfiguredSound('item_duplicate', () => { tone(450, 0.09, 0.72, 0, 'square'); tone(380,  0.11, 0.70, 0.10, 'square'); }); }
/* server / network error — 3-nada turun sawtooth */
function sndErr()          { playConfiguredSound('error_network', () => { tone(240, 0.16, 0.72, 0, 'sawtooth'); tone(150, 0.20, 0.72, 0.16, 'sawtooth'); tone(110, 0.24, 0.70, 0.36, 'sawtooth'); }); }
/* pindah mode (NEXT) — 3-nada sweep naik halus */
function sndNav()          { playConfiguredSound('navigation', () => { tone(700, 0.06, 0.38, 0, 'sine'); tone(1100, 0.06, 0.38, 0.07, 'sine'); tone(1700, 0.10, 0.38, 0.14, 'sine'); }); }
/* compat shim */
function beep(ok) { ok ? sndOrderReady() : sndErr(); }

['pointerdown', 'keydown', 'touchstart'].forEach(eventName => {
    document.addEventListener(eventName, unlockAudio, { once: true, passive: true });
});

/* ── Status badge ── */
function statusBadge(s) {
    const map = {
        ready:    ['sb-ready',   'Stok Cukup'],
        partial:  ['sb-partial', 'Stok Kurang'],
        missing:  ['sb-missing', 'Stok Habis'],
        pending:  ['sb-pending', 'Ditunda'],
        skip:     ['sb-skip',    'Diabaikan'],
        not_found:['sb-notfound','Belum Tertaut'],
    };
    const [cls, lbl] = map[s] || map['not_found'];
    return `<span class="rk-sbadge ${cls}">${lbl}</span>`;
}

function fmtDate(s) {
    if (!s) return '';
    try {
        const d = new Date(s);
        if (isNaN(d)) return s;
        return d.toLocaleDateString('id-ID', {day:'2-digit',month:'short',year:'2-digit'});
    } catch { return s; }
}

/* ── Focus ── */
function focusInput() { orderInput?.focus(); }
function refocusScan() {
    focusInput();
    setTimeout(focusInput, 40);
    setTimeout(focusInput, 160);
}

/* ── Auto-refocus ── */
document.addEventListener('keydown', e => {
    if (document.getElementById('drawerOverlay')?.classList.contains('open')) return;
    const tag = (document.activeElement?.tagName || '').toLowerCase();
    if (tag === 'input' || tag === 'textarea' || document.activeElement?.isContentEditable) return;
    if (e.ctrlKey || e.metaKey || e.altKey || e.key.length !== 1) return;
    focusInput();
});

/* ── Input: uppercase ── */
orderInput?.addEventListener('input', function () { this.value = this.value.toUpperCase(); });
orderSearchInput?.addEventListener('input', function () {
    setOrderSearchQuery(this.value);
});
orderSearchInput?.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        e.preventDefault();
        this.value = '';
        setOrderSearchQuery('');
        orderSearchInput?.blur();
        refocusScan();
    }
});
orderSearchClear?.addEventListener('click', function () {
    if (!orderSearchInput) return;
    orderSearchInput.value = '';
    setOrderSearchQuery('');
    orderSearchInput.focus();
});

/* ── Enter → process ── */
orderInput?.addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); processOrder(orderInput.value.trim()); }
});

/* ══════════════════════════════════════════════
   PROCESS ONE ORDER
══════════════════════════════════════════════ */
async function processOrder(no) {
    unlockAudio();
    /* ── Barcode navigasi: scan NEXT → kembali ke Scan Barang ── */
    if (no.toUpperCase() === 'NEXT') { sndNav(); window.location.href = EDIT_URL; return; }
    no = normalizeOrderNo(no);
    if (!no) return;
    const dupeIdx = orders.findIndex(o => normalizeOrderNo(o.no) === no);
    if (dupeIdx >= 0) {
        setTimeout(() => sndGuard(), 20);
        toast('err', 'Pesanan ' + no + ' sudah ada dalam daftar.');
        /* tandai card yang sudah ada */
        orders[dupeIdx].dupe = true;
        renderAll();
        const card = document.getElementById('ocard-' + dupeIdx);
        if (card) {
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            card.classList.add('rk-dupe-flash');
            setTimeout(() => card.classList.remove('rk-dupe-flash'), 1500);
        }
        orderInput.value = ''; refocusScan(); return;
    }
    orderInput.value = '';

    try {
        const fd = new FormData();
        fd.append('_token', CSRF);
        fd.append('order_no', no);
        fd.append('pool_used', JSON.stringify(poolUsed));

        const res  = await fetch(MATCH_URL, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: fd,
        });
        const data = await res.json();
        if (!res.ok || data.status !== 'ok') throw new Error(data.message || 'Gagal memuat pesanan.');

        const savedNo = normalizeOrderNo(data.no || data.order?.order_no || data.order?.code || no) || no;
        data.no = savedNo;
        data.order = data.order && typeof data.order === 'object' ? data.order : {};
        data.order.order_no = normalizeOrderNo(data.order.order_no || data.order.code) || savedNo;
        const defaultDecision = data.decision || (SHIPMENT_TYPE === 'manual' ? 'fulfill' : (data.order?.source === 'manual_scan' ? 'pending' : null));
        orders.push({ no: savedNo, found: data.found === true, order: data.order, pool_full: data.pool_full, decision: defaultDecision, subs: {} });

        if (data.found) {
            // Akumulasikan alokasi ke poolUsed
            const alloc = data.order?.allocated || {};
            for (const [id, qty] of Object.entries(alloc)) {
                poolUsed[id] = (poolUsed[id] || 0) + qty;
            }
            saveState();
            const s = data.order.status;
            if (s === 'ready')        sndOrderReady();
            else if (s === 'partial') sndOrderPartial();
            else                      sndOrderNoMatch();
            const scanMessage = data.order?.source === 'manual_scan'
                ? `${no} dicatat`
                : s === 'ready'
                    ? `${no} tercatat`
                    : s === 'partial'
                        ? `${no} tercatat, cek rincian item`
                        : `${no} tercatat tanpa item yang cocok`;
            toast(s === 'partial' || s === 'ready' ? 'ok' : 'warn', scanMessage);
        } else {
            saveState();
            if (SHIPMENT_TYPE === 'manual') {
                sndOrderReady();
                toast('ok', no + ' dicatat (manual)');
            } else {
                sndOrderNoMatch();
                toast('warn', no + ' dicatat tanpa tautan');
            }
        }

        renderAll();
        refocusScan();
    } catch (err) {
        sndErr();
        toast('err', err.message);
        refocusScan();
    }
}

/* ══════════════════════════════════════════════
   RENDER
══════════════════════════════════════════════ */
function renderAll() {
    var _rkc = document.getElementById('rkOrderCount');
    if (_rkc) _rkc.textContent = orders.length;
    const visibleOrders = getVisibleOrders();
    const hasSearch = orderSearchQuery.trim().length > 0;

    if (!orders.length) {
        emptyState.style.display = '';
        emptyState.querySelector('.rk-empty-title').textContent = 'Scan nomor pesanan';
        emptyState.querySelector('.rk-empty-sub').textContent = 'Bisa dari barcode scanner atau ketik manual lalu tekan Enter';
        orderList.innerHTML = '';
        topPillOrders.style.display = 'none';
        scanCounter.textContent = '0 pesanan';
        updateSearchUi(0);
        renderSisa();
        updateConfirmBtn();
        return;
    }
    if (!visibleOrders.length && hasSearch) {
        emptyState.style.display = '';
        emptyState.querySelector('.rk-empty-title').textContent = 'Tidak ada hasil pencarian';
        emptyState.querySelector('.rk-empty-sub').textContent = 'Coba kata kunci lain: no resi, no pesanan, kode item, atau nama item';
    } else {
        emptyState.style.display = 'none';
        emptyState.querySelector('.rk-empty-title').textContent = 'Scan nomor pesanan';
        emptyState.querySelector('.rk-empty-sub').textContent = 'Bisa dari barcode scanner atau ketik manual lalu tekan Enter';
    }

    orderList.innerHTML = visibleOrders.map((row) => renderCard(row.order, row.idx, row.displayNo)).join('');

    orderList.querySelectorAll('.rk-act-btn[data-idx]').forEach(btn => {
        btn.addEventListener('click', function () { setDecision(+this.dataset.idx, this.dataset.action); });
    });
    orderList.querySelectorAll('.rk-sub-btn-pick[data-idx]').forEach(btn => {
        btn.addEventListener('click', function () { openDrawer(+this.dataset.idx, +this.dataset.item, +this.dataset.qty); });
    });

    topPillOrders.style.display = '';
    topOrderCount.textContent   = hasSearch ? `${visibleOrders.length}/${orders.length}` : String(orders.length);
    scanCounter.textContent     = hasSearch
        ? `${visibleOrders.length} dari ${orders.length} pesanan`
        : `${orders.length} pesanan`;

    updateSearchUi(visibleOrders.length);
    renderSisa();
    updateConfirmBtn();
}

function renderCard(o, idx, displayNo) {
    const no = normalizeOrderNo(o?.no || o?.order?.order_no) || 'Nomor order tersimpan';
    const { found, order, decision } = o;
    const dupeBadge = o.dupe ? '<span class="rk-dupe-badge">⚠ Duplikat</span>' : '';
    const cls = 'rk-order-card' + (decision ? ' decided-' + decision : '') + (o.dupe ? ' rk-dupe' : '');

    const rowNo = displayNo || (idx + 1);

    if (!found) {
        if (SHIPMENT_TYPE === 'manual') {
            const decBadge = decision ? statusBadge(decision) : statusBadge('ready');
            return `<div class="${cls}" id="ocard-${idx}">
              <div class="rk-order-hdr" onclick="toggleCard(${idx})">
                <span class="rk-order-num">${rowNo}.</span><span class="rk-order-no">${no}</span>
                <span class="rk-order-store">Pesanan Manual</span>
                ${o.scanned_at ? `<span style="font-size:0.7rem; color:#94a3b8; margin-left:0.3rem">${o.scanned_at}</span>` : ''}
                ${decBadge}${dupeBadge}
                <span class="rk-order-chev" id="chev-${idx}">▼</span>
              </div>
              <div id="obody-${idx}" style="display:none">
                <div class="rk-order-body">
                  <p style="color:#64748b;font-size:.84rem;margin:0">
                    Nomor pesanan dicatat. Stok akan dipotong sesuai barang yang disiapkan di batch ini.
                  </p>
                  <div class="rk-action-strip">
                    <span style="font-size:.77rem;color:#9ca3af;font-weight:600">Keputusan:</span>
                    <button class="rk-act-btn fulfill ${decision==='fulfill'?'on':''}" data-idx="${idx}" data-action="fulfill">Siap Kirim</button>
                    <button class="rk-act-btn pending ${decision==='pending'?'on':''}" data-idx="${idx}" data-action="pending">Tunda</button>
                    <button class="rk-act-btn skip    ${decision==='skip'   ?'on':''}" data-idx="${idx}" data-action="skip">Abaikan</button>
                    ${IS_OWNER ? `
                    <div style="flex:1"></div>
                    <button class="rk-act-btn" style="color:#64748b" onclick="editManualOrder(${idx}, '${no}')">Edit</button>
                    <button class="rk-act-btn btn-del" onclick="deleteManualOrder(${idx}, '${no}')">Hapus</button>
                    ` : ''}
                  </div>
                </div>
              </div>
            </div>`;
        }

        return `<div class="${cls}" id="ocard-${idx}">
          <div class="rk-order-hdr" onclick="toggleCard(${idx})">
            <span class="rk-order-num">${rowNo}.</span><span class="rk-order-no">${no}</span>
            ${statusBadge('not_found')}${dupeBadge}
            <span class="rk-order-chev" id="chev-${idx}">▼</span>
          </div>
          <div id="obody-${idx}" style="display:none">
            <div class="rk-order-body">
              <p style="color:#6b7280;font-size:.84rem;margin:0">
                Nomor pesanan dicatat tanpa tautan.<br>
                <span style="color:#9ca3af;font-size:.78rem">Pencocokan ke invoice/order dilakukan nanti.</span>
              </p>
              <div class="rk-action-strip">
                <span style="font-size:.77rem;color:#9ca3af;font-weight:600">Aksi:</span>
                <button class="rk-act-btn fulfill" style="border-color:#3b82f6;color:#2563eb;" onclick="promptLinkScan(${idx})">Tautkan</button>
                <button class="rk-act-btn pending ${decision==='pending'?'on':''}" data-idx="${idx}" data-action="pending">Tunda</button>
                <button class="rk-act-btn skip    ${decision==='skip'   ?'on':''}" data-idx="${idx}" data-action="skip">Abaikan</button>
              </div>
            </div>
          </div>
        </div>`;
    }

    if (order?.source === 'manual_scan') {
        const decBadge = decision ? statusBadge(decision) : statusBadge('pending');
        return `<div class="${cls}" id="ocard-${idx}">
          <div class="rk-order-hdr" onclick="toggleCard(${idx})">
            <span class="rk-order-num">${rowNo}.</span><span class="rk-order-no">${no}</span>
            <span class="rk-order-store">Belum tertaut</span>
            ${o.scanned_at ? `<span style="font-size:0.7rem; color:#94a3b8; margin-left:0.3rem">${o.scanned_at}</span>` : ''}
            ${decBadge}${dupeBadge}
            <span class="rk-order-chev" id="chev-${idx}">▼</span>
          </div>
          <div id="obody-${idx}" style="display:none">
            <div class="rk-order-body">
              <p style="color:#64748b;font-size:.84rem;margin:0">
                Nomor pesanan sudah dicatat. Pencocokan ke invoice/order akan dilakukan nanti.
              </p>
              <div class="rk-action-strip">
                <span style="font-size:.77rem;color:#9ca3af;font-weight:600">Status:</span>
                <button class="rk-act-btn pending ${decision==='pending'?'on':''}" data-idx="${idx}" data-action="pending">Tunda</button>
                <button class="rk-act-btn skip    ${decision==='skip'   ?'on':''}" data-idx="${idx}" data-action="skip">Abaikan</button>
                ${IS_OWNER ? `
                <div style="flex:1"></div>
                <button class="rk-act-btn" style="color:#64748b" onclick="editManualOrder(${idx}, '${no}')">Edit</button>
                <button class="rk-act-btn btn-del" onclick="deleteManualOrder(${idx}, '${no}')">Hapus</button>
                ` : ''}
              </div>
            </div>
          </div>
        </div>`;
    }

    // ── lines table ──
    let linesHtml = `<table class="rk-tbl">
      <thead><tr>
        <th>Barang</th>
        <th style="text-align:right">Dipesan</th>
        <th style="text-align:right">Tersedia</th>
        <th style="text-align:right">Kurang</th>
        <th style="text-align:center">Status</th>
        <th>Barang Pengganti</th>
      </tr></thead><tbody>`;

    for (const line of (order.lines || [])) {
        const ok    = line.status === 'ok';
        const short = line.qty_short || 0;
        const sub   = o.subs[line.item_id];
        const hasSub = !ok && sub?.sub_id;

        const subQty      = hasSub ? (sub.qty || short) : 0;
        const remaining   = hasSub ? Math.max(0, short - subQty) : short;
        const fullyCovered = hasSub && remaining === 0;
        const partiallyCov = hasSub && remaining > 0;

        // Kolom status
        const statusCell = (ok || fullyCovered)
            ? '<span style="color:var(--shp-ok);font-size:.78rem;font-weight:800">OK</span>'
            : partiallyCov
                ? '<span class="rk-sbadge sb-sub" style="opacity:.75">Sebagian</span>'
                : statusBadge(line.status);

        // Kolom "Barang Pengganti"
        let subCell;
        if (ok) {
            subCell = `<td style="color:#d1d5db;text-align:center">—</td>`;
        } else if (hasSub) {
            subCell = `<td>
              <div style="display:flex;flex-direction:column;gap:.18rem">
                <div style="display:flex;align-items:center;gap:.3rem;flex-wrap:wrap">
                  <span class="sf-code">${sub.sub_code}</span>
                  <span class="sf-qty">×${FMT.format(subQty)}</span>
                  ${remaining > 0 ? `<span class="rk-still-short">Masih −${FMT.format(remaining)}</span>` : ''}
                  <button class="rk-sub-btn-pick sf-change" data-idx="${idx}" data-item="${line.item_id}" data-qty="${short}"
                          style="margin-left:auto;font-size:.7rem;padding:.15rem .5rem">Ganti</button>
                </div>
                <span style="font-size:.7rem;color:#9ca3af;line-height:1.3">${sub.sub_name}</span>
              </div>
            </td>`;
        } else {
            subCell = `<td>
              <button class="rk-sub-btn-pick rk-act-btn"
                      style="border-color:var(--shp-accent);color:var(--shp-accent);font-size:.72rem;white-space:nowrap;padding:.25rem .6rem"
                      data-idx="${idx}" data-item="${line.item_id}" data-qty="${short}">Pilih Pengganti</button>
            </td>`;
        }

        linesHtml += `<tr>
          <td>
            <div style="display:flex;flex-direction:column;gap:.1rem">
              <span class="rk-icode" style="font-size:.82rem">${line.item_code}</span>
              <span style="font-size:.78rem;color:#6b7280;line-height:1.3">${line.item_name}</span>
            </div>
          </td>
          <td style="text-align:right">${FMT.format(line.qty_need)}</td>
          <td style="text-align:right" class="${ok?'rk-qty-ok':'rk-qty-dim'}">${FMT.format(line.qty_alloc)}</td>
          <td style="text-align:right" class="${remaining>0?'rk-qty-short':'rk-qty-dim'}">${remaining>0?'-'+FMT.format(remaining):short>0?'<span style="color:var(--shp-ok);font-weight:800">OK</span>':'-'}</td>
          <td style="text-align:center">${statusCell}</td>
          ${subCell}
        </tr>`;
    }
    linesHtml += '</tbody></table>';

    const mpBadge = '';

    // Hitung coverage substitusi — memperhitungkan partial qty
    const shortLines = (order.lines || []).filter(l => (l.qty_short || 0) > 0);
    let coveredCount = 0;
    let hasPartial   = false;
    for (const l of shortLines) {
        const s = o.subs[l.item_id];
        if (!s?.sub_id) continue;
        const subQty = s.qty || (l.qty_short || 0);  // fallback ke full shortage jika qty belum tersimpan
        const rem    = Math.max(0, (l.qty_short || 0) - subQty);
        if (rem === 0) coveredCount++;
        else           hasPartial = true;
    }
    const allFullyCovered  = shortLines.length > 0 && coveredCount === shortLines.length && !hasPartial;
    const someSubbed        = coveredCount > 0 || hasPartial;
    const partialCoverage   = someSubbed && !allFullyCovered;

    let decBadge;
    if (decision) {
        decBadge = statusBadge(decision);
    } else if (allFullyCovered) {
        decBadge = '<span class="rk-sbadge sb-sub">Semua Diganti</span>';
    } else if (partialCoverage) {
        decBadge = '<span class="rk-sbadge sb-sub" style="opacity:.8">Diganti Sebagian</span>';
    } else {
        decBadge = statusBadge(order.status || 'ready');
    }

    return `<div class="${cls}" id="ocard-${idx}">
      <div class="rk-order-hdr" onclick="toggleCard(${idx})">
        <span class="rk-order-num">${rowNo}.</span><span class="rk-order-no">${no}</span>
        ${order.store_name ? `<span class="rk-order-store">${order.store_name}</span>` : ''}
        ${mpBadge}
        ${order.date ? `<span style="font-size:.73rem;color:#94a3b8">${fmtDate(order.date)}</span>` : ''}
        ${decBadge}${dupeBadge}
        <span class="rk-order-chev" id="chev-${idx}">▼</span>
      </div>
      <div id="obody-${idx}" style="display:none">
        <div class="rk-order-body">
          ${linesHtml}
          <div class="rk-action-strip">
            <span style="font-size:.77rem;color:#9ca3af;font-weight:600">Keputusan:</span>
            <button class="rk-act-btn fulfill ${decision==='fulfill'?'on':''}" data-idx="${idx}" data-action="fulfill">Siap Kirim</button>
            <button class="rk-act-btn pending ${decision==='pending'?'on':''}" data-idx="${idx}" data-action="pending">Tunda</button>
            <button class="rk-act-btn skip    ${decision==='skip'   ?'on':''}" data-idx="${idx}" data-action="skip">Abaikan</button>
            ${IS_OWNER ? `
            <div style="flex:1"></div>
            <button class="rk-act-btn" style="color:#64748b" onclick="editManualOrder(${idx}, '${no}')">Edit</button>
            <button class="rk-act-btn btn-del" onclick="deleteManualOrder(${idx}, '${no}')">Hapus</button>
            ` : ''}
          </div>
        </div>
      </div>
    </div>`;
}

window.toggleCard = function (idx) {
    const body = document.getElementById('obody-' + idx);
    const chev = document.getElementById('chev-' + idx);
    if (!body) return;
    const open = body.style.display !== 'none';
    body.style.display = open ? 'none' : '';
    chev?.classList.toggle('open', !open);
};

function setDecision(idx, action) {
    orders[idx].decision = action;
    saveOrderScan(idx);
    recalculateAllocations();
    renderAll();
}

window.promptLinkScan = async function(idx) {
    const o = orders[idx];
    if (!o) return;
    const targetNo = prompt(`Masukkan Nomor Resi / Order Marketplace untuk pesanan manual: ${o.no}\n(Contoh: SPXID..., ID234...)`);
    if (!targetNo || !targetNo.trim()) return;

    try {
        const url = LINK_SCAN_URL.replace('__NO__', encodeURIComponent(o.no));
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ target_order_no: targetNo.trim() })
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Gagal menautkan pesanan.');

        // Update the order locally
        orders[idx] = data;
        recalculateAllocations();
        rebuildPoolUsed();
        renderAll();
        toast('ok', `Berhasil ditautkan ke ${data.order.order_no}`);
    } catch (err) {
        toast('err', err.message);
    }
};

function isOrderFullyAllocated(order) {
    if (!order?.found || !order.order?.lines?.length || order.decision === 'skip') return false;

    return order.order.lines.every(line => {
        const normalQty = Number(line.qty_alloc || 0);
        const substitutionQty = order.subs?.[line.item_id]
            ? Number(order.subs[line.item_id].qty || 0)
            : 0;
        return normalQty + substitutionQty >= Number(line.qty_need || 0);
    });
}

function isItemFirstAllocationReady() {
    if (!ITEM_FIRST || !orders.length) return false;

    const activeOrders = orders.filter(order => order.decision !== 'skip');
    if (!activeOrders.length || !activeOrders.every(isOrderFullyAllocated)) return false;

    return buildSisaPool().every(item => Number(item.qty || 0) === 0);
}

function updateItemFirstAllocationUi() {
    if (!ITEM_FIRST || !itemFirstAllocateBtn) return;

    const hasItems = BATCH_POOL.some(item => Number(item.qty || 0) > 0);
    const hasOrders = orders.length > 0;
    const ready = isItemFirstAllocationReady();

    itemFirstAllocateBtn.disabled = !hasItems || !hasOrders;
    if (itemFirstAllocationHint) {
        itemFirstAllocationHint.textContent = !hasOrders
            ? 'Belum ada order yang tercatat.'
            : ready
                ? 'Semua item sudah dialokasikan. Anda bisa lanjut ke konfirmasi.'
                : 'Klik tombol ini untuk membagi item batch ke order.';
    }
}

function updateConfirmBtn() {
    if (ITEM_FIRST) {
        const ready = isItemFirstAllocationReady();
        updateItemFirstAllocationUi();
        if (topConfirmBtn) {
            topConfirmBtn.disabled = !ready;
            topConfirmBtn.classList.toggle('active', ready);
        }
        return;
    }

    const validOrders = orders.filter(o => o.found || SHIPMENT_TYPE === 'manual');
    const allDecided  = validOrders.length > 0 && validOrders.every(o => o.decision);
    if (topConfirmBtn) {
        topConfirmBtn.disabled = !allDecided;
        topConfirmBtn.classList.toggle('active', allDecided);
    }
}

/* ══════════════════════════════════════════════
   DRAWER — Substitusi bebas
══════════════════════════════════════════════ */
const drawerOverlay = document.getElementById('drawerOverlay');
const drawerListEl  = document.getElementById('drawerList');
const drawerSearch  = document.getElementById('drawerSearch');

/* Hitung qty yang sudah dipakai oleh substitusi yang dipilih operator */
function computeSubsUsed() {
    const used = {};
    orders.forEach(o => {
        (o.order?.lines || []).forEach(line => {
            const sub = o.subs?.[line.item_id];
            if (sub?.sub_id) {
                const qty = sub.qty || line.qty_short || 0;
                used[sub.sub_id] = (used[sub.sub_id] || 0) + qty;
            }
        });
    });
    return used;
}

function buildSisaPool() {
    // Sisa = batch total - poolUsed (alokasi normal) - subsUsed (alokasi substitusi)
    const subsUsed = computeSubsUsed();
    return BATCH_POOL
        .map(item => ({
            item_id:   item.item_id,
            item_code: item.item_code,
            item_name: item.item_name,
            qty: Math.max(0,
                item.qty
                - (poolUsed[item.item_id] || 0)
                - (subsUsed[item.item_id] || 0)
            ),
        }))
        .filter(p => p.qty > 0);
}

function openDrawer(orderIdx, lineItemId, qtyShort) {
    drawerCtx        = { orderIdx, lineItemId, qtyShort };
    drawerPendingSub = null;
    drawerPendingQty = 1;
    drawerPool       = buildSisaPool();

    const line = orders[orderIdx]?.order?.lines?.find(l => l.item_id === lineItemId);
    const code = line?.item_code || '—';

    document.getElementById('drawerForItem').textContent = code;
    document.getElementById('drawerNeedQty').textContent = FMT.format(qtyShort);
    document.getElementById('drawerTargetBox').style.display = 'inline-flex';

    // Reset footer ke state idle
    setDrawerFooter('idle');
    drawerSearch.value = '';
    renderDrawer('');
    drawerOverlay.classList.add('open');
    setTimeout(() => drawerSearch.focus(), 150);
}

function setDrawerFooter(state) {
    document.getElementById('drawerFooterIdle').style.display    = state === 'idle'    ? 'flex'   : 'none';
    document.getElementById('drawerFooterConfirm').style.display = state === 'confirm' ? 'flex'   : 'none';
}

function updateStepperUI() {
    if (!drawerCtx || !drawerPendingSub) return;
    const { qtyShort } = drawerCtx;
    const maxQty = Math.min(qtyShort, drawerPendingSub.qty);  // max = min(shortage, sisa item)

    document.getElementById('drawerQtyVal').textContent  = drawerPendingQty;
    document.getElementById('drawerQtyMinus').disabled   = drawerPendingQty <= 1;
    document.getElementById('drawerQtyPlus').disabled    = drawerPendingQty >= maxQty;
    document.getElementById('drawerQtyHint').textContent =
        drawerPendingQty < qtyShort
            ? 'Sisa kurang ' + (qtyShort - drawerPendingQty) + ' pcs tidak disubstitusi'
            : 'Semua kekurangan disubstitusi';
    document.getElementById('drawerSelCode').textContent = drawerPendingSub.item_code;
    document.getElementById('drawerSelName').textContent = drawerPendingSub.item_name;
}

window.adjDrawerQty = function (delta) {
    if (!drawerCtx || !drawerPendingSub) return;
    const { qtyShort } = drawerCtx;
    const maxQty = Math.min(qtyShort, drawerPendingSub.qty);
    drawerPendingQty = Math.max(1, Math.min(maxQty, drawerPendingQty + delta));
    updateStepperUI();
};

window.cancelSub = function (orderIdx, lineItemId) {
    if (!orders[orderIdx] || !orders[orderIdx].subs) return;
    delete orders[orderIdx].subs[lineItemId];
    saveOrderScan(orderIdx);
    renderAll();
    toast('ok', 'Substitusi dibatalkan');
};

window.confirmPickSub = function () {
    if (!drawerCtx || !drawerPendingSub) return;
    const { orderIdx, lineItemId } = drawerCtx;
    orders[orderIdx].subs[lineItemId] = {
        sub_id:   drawerPendingSub.item_id,
        sub_code: drawerPendingSub.item_code,
        sub_name: drawerPendingSub.item_name,
        qty:      drawerPendingQty,
    };
    saveOrderScan(orderIdx);
    closeDrawer();
    renderAll();
    toast('ok', 'Substitusi: ' + drawerPendingSub.item_code + ' ×' + drawerPendingQty);
};

window.closeDrawer = function () {
    drawerOverlay.classList.remove('open');
    drawerCtx        = null;
    drawerPendingSub = null;
    focusInput();
};
drawerOverlay.addEventListener('click', e => { if (e.target === drawerOverlay) closeDrawer(); });
window.filterDrawer = function (q) { renderDrawer(q); };

// Tap item di list → set pending (belum konfirmasi), tampilkan footer stepper
window.selectSubItem = function (itemId, itemCode, itemName, sisaQty) {
    if (!drawerCtx) return;
    const { qtyShort } = drawerCtx;
    drawerPendingSub = { item_id: itemId, item_code: itemCode, item_name: itemName, qty: sisaQty };
    drawerPendingQty = Math.min(qtyShort, sisaQty);   // default = full shortage (jika stok cukup)
    updateStepperUI();
    setDrawerFooter('confirm');
};

function renderDrawer(q) {
    const t     = q.toLowerCase().trim();
    const all   = drawerPool;
    const items = all.filter(p =>
        !t || p.item_code.toLowerCase().includes(t) || p.item_name.toLowerCase().includes(t)
    );

    const footer = document.getElementById('drawerFooterCount');
    if (footer) footer.textContent = items.length + ' item tersedia';

    if (!all.length) {
        drawerListEl.innerHTML = `
            <div class="rk-pool-empty">
                <div class="rk-pool-empty-icon"></div>
                Tidak ada sisa stok di batch.<br>
                <span style="font-size:.76rem">Semua item sudah teralokasi ke pesanan.</span>
            </div>`;
        return;
    }
    if (!items.length) {
        drawerListEl.innerHTML = `
            <div class="rk-pool-empty">
                <div class="rk-pool-empty-icon"></div>
                Tidak ada item yang cocok dengan "<b>${q}</b>"
            </div>`;
        return;
    }

    const cur = drawerCtx ? orders[drawerCtx.orderIdx]?.subs[drawerCtx.lineItemId] : null;
    drawerListEl.innerHTML = items.map(p => {
        const isCur     = cur?.sub_id === p.item_id;
        const isPending = drawerPendingSub?.item_id === p.item_id;
        return `
        <div class="rk-pool-item ${isCur || isPending ? 'selected' : ''}"
             onclick="selectSubItem(${p.item_id},'${p.item_code.replace(/'/g,"\\'")}','${p.item_name.replace(/'/g,"\\'").replace(/"/g,'&quot;')}',${p.qty})">
          <div style="flex:1;min-width:0">
            <div class="rk-pool-code">${p.item_code}</div>
            <div class="rk-pool-name">${p.item_name}</div>
          </div>
          <span class="rk-pool-qty-badge">${FMT.format(p.qty)} sisa</span>
          ${isPending ? '<span style="color:var(--shp-accent);font-size:.8rem;font-weight:700">✓</span>' : ''}
        </div>`;
    }).join('');
}

/* ══════════════════════════════════════════════
   SISA STOK — barang di batch yang tidak dialokasikan ke pesanan mana pun
══════════════════════════════════════════════ */
function renderSisa() {
    const sisaCard = document.getElementById('sisaCard');
    if (!sisaCard) return;

    // Hitung sisa: batchQty - poolUsed - subsUsed
    const subsUsed = computeSubsUsed();
    const sisa = BATCH_POOL
        .map(item => ({
            item_id:   item.item_id,
            item_code: item.item_code,
            item_name: item.item_name,
            qty_total: item.qty,
            qty_used:  (poolUsed[item.item_id] || 0) + (subsUsed[item.item_id] || 0),
            qty_sisa:  Math.max(0, item.qty - (poolUsed[item.item_id] || 0) - (subsUsed[item.item_id] || 0)),
        }))
        .filter(s => s.qty_sisa > 0);

    const rkSisaCount = document.getElementById('rkSisaCount');
    if (rkSisaCount) rkSisaCount.textContent = sisa.length;

    if (!sisa.length) {
        sisaCard.style.display = 'none';
        return;
    }

    sisaCard.style.display = '';

    const totalSisa = sisa.reduce((a, s) => a + s.qty_sisa, 0);
    const hasOrders = orders.length > 0;
    const hasAllocation = Object.values(poolUsed).some(q => Number(q) > 0)
        || Object.values(subsUsed).some(q => Number(q) > 0);
    const validOrders = orders.filter(o => o.found || SHIPMENT_TYPE === 'manual');
    const allDecided = validOrders.length > 0 && validOrders.every(o => o.decision);
    const isWarning  = hasAllocation && allDecided && sisa.length > 0;

    const rows = sisa.map(s => `
        <div class="rk-sisa-row">
            <span class="rk-sisa-code">${s.item_code}</span>
            <span class="rk-sisa-name">${s.item_name}</span>
            <span class="rk-sisa-qty">Sisa ${FMT.format(s.qty_sisa)} pcs</span>
        </div>`).join('');

    const note = isWarning
        ? `<div class="rk-sisa-note">Masih ada <b>${FMT.format(totalSisa)} pcs</b> yang belum masuk pesanan mana pun. Scan pesanan tambahan atau abaikan jika memang lebih.</div>`
        : hasOrders && !hasAllocation
            ? `<div class="rk-sisa-note">Pesanan sudah dicatat, tapi stok batch belum dialokasikan karena belum ditautkan ke invoice/order.</div>`
            : hasOrders
                ? `<div class="rk-sisa-note">Barang-barang ini ada di batch tapi belum dialokasikan ke pesanan yang discan.</div>`
            : `<div class="rk-sisa-note">Semua item batch masih belum dialokasikan. Scan pesanan untuk mulai mencocokkan stok.</div>`;

    sisaCard.innerHTML = `
        <div class="rk-sisa-card ${isWarning ? 'has-sisa' : ''}" id="sisaCardInner">
            <div class="rk-sisa-hdr" style="cursor:default;">
                <span class="rk-sisa-title">${hasAllocation ? 'Sisa Stok Batch' : 'Stok Batch Belum Dialokasikan'}${isWarning ? ' — Ada Kelebihan' : ''}</span>
                <span class="shp-pill" style="margin-left:auto; font-size:.7rem;padding:.12rem .55rem">
                    ${FMT.format(totalSisa)} pcs · ${sisa.length} SKU
                </span>
            </div>
            <div id="sisaBody">
                <div class="rk-sisa-body">
                    ${rows}
                    ${note}
                </div>
            </div>
        </div>`;
}

let sisaOpen = false;
window.toggleSisa = function () {
    const body = document.getElementById('sisaBody');
    const chev = document.getElementById('sisaChev');
    if (!body) return;
    sisaOpen = !sisaOpen;
    body.style.display = sisaOpen ? '' : 'none';
    if (chev) chev.style.transform = sisaOpen ? '' : 'rotate(-90deg)';
};

/* ── Reset semua ── */
window.resetRekon = function () {
    if (!confirm('Hapus semua pesanan yang sudah discan dan mulai ulang?')) return;
    
    // AJAX call to delete all from database
    fetch(RESET_URL, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': CSRF,
            'Accept': 'application/json'
        }
    }).then(res => res.json()).then(data => {
        if (data.status === 'ok') {
            clearState();
            renderAll();
            toast('ok', 'Pesanan direset.');
            focusInput();
        } else {
            toast('err', 'Gagal mereset pesanan.');
        }
    }).catch(err => {
        toast('err', 'Terjadi kesalahan jaringan.');
    });
};

itemFirstAllocateBtn?.addEventListener('click', async function () {
    if (this.disabled) return;

    this.disabled = true;
    if (itemFirstAllocationHint) itemFirstAllocationHint.textContent = 'Sedang menyimpan alokasi item...';

    try {
        await recalculateAllocations();
        renderAll();
        toast('ok', isItemFirstAllocationReady()
            ? 'Alokasi item berhasil disimpan.'
            : 'Alokasi selesai, tetapi masih ada item/order yang belum lengkap.');
    } catch (error) {
        toast('err', error.message || 'Alokasi item gagal disimpan.');
    } finally {
        updateItemFirstAllocationUi();
    }
});

topConfirmBtn?.addEventListener('click', function () {
    if (this.disabled) return;
    unlockAudio();
    sndNav();
    saveState();
    window.location.href = CONFIRM_URL;
});

/* ── tabs ── */
(function(){
    var tabs  = document.querySelectorAll('.rk-tab');
    var panes = document.querySelectorAll('.rk-tabpane');
    window.rkActivateTab = function(name){
        tabs.forEach(function(t){ t.classList.toggle('active', t.dataset.tab === name); });
        panes.forEach(function(p){ p.classList.toggle('active', p.id === 'rk-tab-' + name); });
        if (name === 'pesanan') {
            var oi = document.getElementById('orderInput');
            if (oi) { try { oi.focus(); } catch(e){} }
        }
    };
    tabs.forEach(function(t){
        t.addEventListener('click', function(){ rkActivateTab(t.dataset.tab); });
    });
    // Kembali ke tab Pesanan otomatis saat mulai scan (mis. via barcode scanner)
    var inp = document.getElementById('orderInput');
    if (inp) inp.addEventListener('focus', function(){ rkActivateTab('pesanan'); });
})();

/* ── init ── */
window.addEventListener('load', async function () {
    await loadState();
    renderAll();
    if (!ITEM_FIRST) focusInput();
});

})();
</script>
@endpush
