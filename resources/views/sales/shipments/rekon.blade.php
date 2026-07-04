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

/* ── Ticker ── */
.shp-ticker {
    display: none; align-items: center; gap: .8rem;
    margin-top: .8rem; padding: .8rem 1.1rem; border-radius: 14px;
    animation: tickSlide .2s ease;
}
@keyframes tickSlide { from { opacity:0; transform:translateY(-5px); } to { opacity:1; transform:translateY(0); } }
.shp-ticker-ok  { background: var(--shp-ok-bg);   border: 1.5px solid rgba(21,128,61,.25); }
.shp-ticker-err { background: var(--shp-err-bg);  border: 1.5px solid rgba(185,28,28,.28); color: var(--shp-err); font-weight: 700; font-size: .88rem; }
.shp-ticker-warn{ background: var(--shp-warn-bg); border: 1.5px solid rgba(245,158,11,.3);  color: var(--shp-warn); font-size: .86rem; font-weight: 600; }
.shp-tick-code  { font-weight: 900; font-size: 1.1rem; font-family: monospace; color: var(--shp-ok); }
body[data-theme="dark"] .shp-tick-code { color: #4ade80; }
.shp-tick-store { font-size: .84rem; color: #374151; }
body[data-theme="dark"] .shp-tick-store { color: #bbf7d0; }
.shp-tick-right { margin-left: auto; flex-shrink: 0; }

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
.sb-mp       { background: rgba(249,115,22,.08); color: #ea580c; border: 1px solid rgba(249,115,22,.3); }
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

/* Compact neutral override, aligned with shipment edit */
.page-theme-shopee,
.page-theme-tiktok {
    --shp-accent: #334155;
    --shp-accent-2: #1f2937;
    --shp-accent-bg: rgba(148,163,184,.08);
    --shp-accent-ring: rgba(148,163,184,.18);
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
.shp-ticker {
    margin-top: .55rem;
    padding: .55rem .7rem;
    border-radius: 8px;
    animation: none;
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
#restoreBanner button,
.rk-modal-footer button {
    border-radius: 7px !important;
    box-shadow: none !important;
}
#reAnalisisBtn {
    background: #334155 !important;
    border-color: #334155 !important;
    color: #fff !important;
}
#reAnalisisBtn:hover {
    background: #1f2937 !important;
    border-color: #1f2937 !important;
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
    .btn-shp-submit {
        width: 100%;
        order: 5;
    }
    .rk-phases,
    .rk-batch-bar {
        display: none;
    }
    #restoreBanner {
        margin-top: .5rem !important;
        padding: .55rem .65rem !important;
        border-radius: 8px !important;
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
</style>
@endpush

@section('content')
@php
    $totalLines = $shipment->lines->count();
    $totalQty   = $shipment->lines->sum('qty_scanned');
@endphp

<div class="shp-topbar">
    <a href="{{ route('sales.shipments.edit', $shipment) }}" class="btn-shp-outline" style="text-decoration:none"
       title="Data rekonsiliasi tersimpan otomatis dan akan dipulihkan saat kembali ke halaman ini">
        Scan Barang
    </a>
    <span class="shp-topbar-code">{{ $shipment->code }}</span>
    <span class="shp-badge shp-badge-draft">Draft</span>
    <span class="shp-topbar-spacer"></span>
    <span class="shp-pill">Batch <b>{{ $totalLines }}</b> SKU</span>
    <span class="shp-pill shp-pill-accent">Qty <b>{{ number_format($totalQty, 0, ',', '.') }}</b></span>
    <span class="shp-pill" id="topPillOrders" style="display:none">Pesanan <b id="topOrderCount">0</b></span>
    <button id="topConfirmBtn" class="btn-shp-submit" disabled>Konfirmasi</button>
</div>

<div class="rk-wrap">

    {{-- RESTORE BANNER --}}
    <div id="restoreBanner" style="display:none;align-items:center;gap:.65rem;flex-wrap:wrap;
         margin-top:.75rem;padding:.65rem 1rem;border-radius:12px;
         border:1.5px solid rgba(245,158,11,.35);">
        <span id="restoreBannerText" style="font-size:.8rem;font-weight:600;"></span>
        <button id="reAnalisisBtn" onclick="reAnalisis()"
                style="border-radius:999px;border:1.5px solid;
                       background:var(--shp-accent);border-color:var(--shp-accent);color:#fff;
                       font-size:.75rem;font-weight:700;padding:.25rem .85rem;cursor:pointer;">
            Re-Analisis Ulang
        </button>
        <button onclick="window.resetRekon()"
                style="border-radius:999px;border:1.5px solid rgba(148,163,184,.4);
                       background:transparent;color:#6b7280;
                       font-size:.74rem;font-weight:700;padding:.25rem .75rem;cursor:pointer;">
            Mulai Ulang
        </button>
    </div>

    {{-- PHASE BAR --}}
    <div class="rk-phases">
        <span class="rk-phase done">① Scan Barang</span>
        <span class="rk-sep">→</span>
        <span class="rk-phase active">② Rekonsiliasi Pesanan</span>
        <span class="rk-sep">→</span>
        <span class="rk-phase" id="ph3">③ Konfirmasi</span>
    </div>

    {{-- BATCH SKU CHIPS --}}
    <div class="rk-batch-bar">
        <span style="font-size:.68rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;white-space:nowrap">Isi Batch:</span>
        @forelse ($batchPool as $item)
            <span class="rk-sku-chip">
                <span class="c">{{ $item['item_code'] }}</span>
                <span style="color:#d1d5db">·</span>
                <span class="q">{{ number_format($item['qty'], 0, ',', '.') }}</span>
            </span>
        @empty
            <span style="color:#94a3b8;font-size:.82rem">Belum ada item di batch.</span>
        @endforelse
    </div>

    {{-- HERO SCAN CARD --}}
    <div class="shp-scan-card">
        <div class="shp-scan-header">
            <span class="shp-scan-label">Scan Nomor Pesanan</span>
            <span class="shp-scan-counter" id="scanCounter">0 pesanan</span>
        </div>

        <input type="text" id="orderInput" class="shp-scan-input"
               placeholder="Scan barcode atau ketik nomor order, lalu Enter"
               autocomplete="off" spellcheck="false">

        <div class="shp-ticker shp-ticker-ok" id="tickerOk">
            <div>
                <div class="shp-tick-code" id="tickCode">—</div>
                <div class="shp-tick-store" id="tickStore"></div>
            </div>
            <div class="shp-tick-right" id="tickStatus"></div>
        </div>
        <div class="shp-ticker shp-ticker-warn" id="tickerWarn">
            <span id="tickWarnMsg">Pesanan tidak ditemukan.</span>
        </div>
        <div class="shp-ticker shp-ticker-err" id="tickerErr">
            <span id="tickErrMsg">Terjadi kesalahan.</span>
        </div>
    </div>

    {{-- ORDER LIST --}}
    <div id="orderList"></div>

    {{-- EMPTY STATE --}}
    <div class="rk-empty" id="emptyState">
        <div class="rk-empty-icon"></div>
        <div class="rk-empty-title">Scan nomor pesanan untuk mulai rekonsiliasi</div>
        <div class="rk-empty-sub">Bisa dari barcode scanner atau ketik manual lalu tekan Enter</div>
    </div>

    {{-- SISA STOK CARD --}}
    <div id="sisaCard" style="display:none"></div>

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

/* ── URLs ── */
const CSRF       = document.querySelector('meta[name="csrf-token"]')?.content || '';
const MATCH_URL  = @json(parse_url(route('sales.shipments.rekon_match', $shipment), PHP_URL_PATH));
const APPLY_URL  = @json(parse_url(route('sales.shipments.rekon_apply', $shipment), PHP_URL_PATH));
const EDIT_URL   = @json(parse_url(route('sales.shipments.edit', $shipment), PHP_URL_PATH));
const FMT        = new Intl.NumberFormat('id-ID');
const STORE_KEY  = 'rk_state_{{ $shipment->id }}';

// Batch pool dari server — sumber kebenaran qty total per item
const BATCH_POOL = @json(array_values($batchPool->toArray()));

/* ── State ── */
let poolUsed = {};
let orders   = [];
let drawerCtx        = null;
let drawerPool       = [];
let drawerPendingSub = null;   // item yang di-tap di list, belum dikonfirmasi
let drawerPendingQty = 1;      // qty yang akan di-sub

/* ── Persistence ── */
function batchFingerprint() {
    // Hash sederhana: sorted item_id:qty pairs
    return BATCH_POOL.map(p => p.item_id + ':' + p.qty).sort().join('|');
}
function saveState() {
    try {
        localStorage.setItem(STORE_KEY, JSON.stringify({
            batch_fp: batchFingerprint(),
            poolUsed,
            orders: orders.map(o => ({
                no:       o.no,
                found:    o.found,
                order:    o.order,
                pool_full:o.pool_full,
                decision: o.decision,
                subs:     o.subs,
            })),
        }));
    } catch {}
}
function loadState() {
    try {
        const raw = localStorage.getItem(STORE_KEY);
        if (!raw) return 'empty';
        const s = JSON.parse(raw);
        if (!s || !Array.isArray(s.orders) || !s.orders.length) return 'empty';
        poolUsed = s.poolUsed || {};
        orders   = s.orders;
        // Cek apakah batch sudah berubah sejak terakhir discan
        // Jika batch_fp tidak ada (data lama) → treat as stale agar re-analisis otomatis
        const fp = batchFingerprint();
        return (!s.batch_fp || s.batch_fp !== fp) ? 'stale' : 'ok';
    } catch { return 'empty'; }
}
function clearState() {
    try { localStorage.removeItem(STORE_KEY); } catch {}
}

/* ── Re-Analisis Semua: scan ulang semua no pesanan ke server dengan batch terkini ── */
window.reAnalisis = async function reAnalisis() {
    if (!orders.length) return;
    const btn = document.getElementById('reAnalisisBtn');
    if (btn) { btn.disabled = true; btn.textContent = 'Re-analisis...'; }

    const savedNos      = orders.map(o => o.no);
    const savedDecisions= {};
    orders.forEach(o => { if (o.decision) savedDecisions[o.no] = o.decision; });
    const savedSubs     = {};
    orders.forEach(o => { if (Object.keys(o.subs||{}).length) savedSubs[o.no] = o.subs; });

    // Reset state
    orders   = [];
    poolUsed = {};

    for (const no of savedNos) {
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
            if (!res.ok || data.status !== 'ok') throw new Error(data.message);

            const entry = {
                no, found: data.found, order: data.order,
                pool_full: data.pool_full,
                decision: savedDecisions[no] || null,
                subs:     savedSubs[no]     || {},
            };
            orders.push(entry);

            if (data.found) {
                const alloc = data.order?.allocated || {};
                for (const [id, qty] of Object.entries(alloc)) {
                    poolUsed[id] = (poolUsed[id] || 0) + qty;
                }
            }
        } catch (err) {
            orders.push({ no, found: false, order: null, pool_full: [], decision: savedDecisions[no] || null, subs: {} });
        }
    }

    saveState();
    renderAll();
    document.getElementById('restoreBanner').style.display = 'none';
    toast('ok', 'Re-analisis selesai — data diperbarui dengan batch terkini');
    focusInput();
}

/* ── DOM ── */
const orderInput    = document.getElementById('orderInput');
const orderList     = document.getElementById('orderList');
const emptyState    = document.getElementById('emptyState');
const scanCounter   = document.getElementById('scanCounter');
const topPillOrders = document.getElementById('topPillOrders');
const topOrderCount = document.getElementById('topOrderCount');
const topConfirmBtn = document.getElementById('topConfirmBtn');

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
function beep(ok) {
    try {
        const ac = new (window.AudioContext || window.webkitAudioContext)();
        const o  = ac.createOscillator(); const g = ac.createGain();
        o.connect(g); g.connect(ac.destination);
        o.frequency.value = ok ? 880 : 280;
        g.gain.setValueAtTime(.4, ac.currentTime);
        g.gain.exponentialRampToValueAtTime(.001, ac.currentTime + (ok ? .14 : .28));
        o.start(ac.currentTime); o.stop(ac.currentTime + (ok ? .14 : .28));
    } catch {}
}

/* ── Ticker ── */
const tickerOk   = document.getElementById('tickerOk');
const tickerWarn = document.getElementById('tickerWarn');
const tickerErr  = document.getElementById('tickerErr');
function showTicker(which, data) {
    [tickerOk, tickerWarn, tickerErr].forEach(t => t.style.display = 'none');
    const map = { ok: tickerOk, warn: tickerWarn, err: tickerErr };
    const el = map[which]; if (!el) return;
    if (which === 'ok') {
        document.getElementById('tickCode').textContent  = data.no;
        document.getElementById('tickStore').textContent = data.order?.store_name || '';
        document.getElementById('tickStatus').innerHTML  = statusBadge(data.order?.status || 'ready') + (data.order?.source === 'marketplace_order' ? ' <span class="rk-sbadge sb-mp">MP</span>' : '');
    } else if (which === 'warn') {
        document.getElementById('tickWarnMsg').textContent = '"' + data.no + '" tidak ditemukan di sistem.';
    } else {
        document.getElementById('tickErrMsg').textContent = data.msg || 'Terjadi kesalahan.';
    }
    el.style.display = 'flex';
    el.style.animation = 'none'; void el.offsetWidth; el.style.animation = '';
}

/* ── Status badge ── */
function statusBadge(s) {
    const map = {
        ready:    ['sb-ready',   'Stok Cukup'],
        partial:  ['sb-partial', 'Stok Kurang'],
        missing:  ['sb-missing', 'Stok Habis'],
        pending:  ['sb-pending', 'Ditunda'],
        skip:     ['sb-skip',    'Diabaikan'],
        not_found:['sb-notfound','? Tidak Ditemukan'],
    };
    const [cls, lbl] = map[s] || map['not_found'];
    return `<span class="rk-sbadge ${cls}">${lbl}</span>`;
}

function fmtDate(s) {
    if (!s) return '';
    try { return new Date(s + 'T00:00:00').toLocaleDateString('id-ID', {day:'2-digit',month:'short',year:'2-digit'}); }
    catch { return s; }
}

/* ── Focus ── */
function focusInput() { orderInput?.focus(); }

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

/* ── Enter → process ── */
orderInput?.addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); processOrder(orderInput.value.trim()); }
});

/* ══════════════════════════════════════════════
   PROCESS ONE ORDER
══════════════════════════════════════════════ */
async function processOrder(no) {
    if (!no) return;
    if (orders.find(o => o.no === no)) {
        toast('err', 'Pesanan ' + no + ' sudah ada dalam daftar.');
        orderInput.value = ''; focusInput(); return;
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

        data.no = no;
        orders.push({ no, found: data.found, order: data.order, pool_full: data.pool_full, decision: null, subs: {} });

        if (data.found) {
            // Akumulasikan alokasi ke poolUsed
            const alloc = data.order?.allocated || {};
            for (const [id, qty] of Object.entries(alloc)) {
                poolUsed[id] = (poolUsed[id] || 0) + qty;
            }
            saveState();
            showTicker('ok', data);
            beep(true);
            const s = data.order.status;
            toast(s === 'ready' ? 'ok' : 'warn', no + (s === 'ready' ? ' - semua item tersedia' : s === 'partial' ? ' - stok kurang' : ' - item tidak ada di batch'));
        } else {
            saveState();
            showTicker('warn', data);
            beep(false);
            toast('err', no + ' tidak ditemukan di sistem');
        }

        renderAll();
        focusInput();
    } catch (err) {
        showTicker('err', { msg: err.message });
        beep(false);
        toast('err', err.message);
        focusInput();
    }
}

/* ══════════════════════════════════════════════
   RENDER
══════════════════════════════════════════════ */
function renderAll() {
    if (!orders.length) {
        emptyState.style.display = '';
        orderList.innerHTML = '';
        topPillOrders.style.display = 'none';
        scanCounter.textContent = '0 pesanan';
        renderSisa();
        updateConfirmBtn();
        return;
    }
    emptyState.style.display = 'none';

    orderList.innerHTML = orders.map((o, i) => renderCard(o, i)).join('');

    orderList.querySelectorAll('.rk-act-btn[data-idx]').forEach(btn => {
        btn.addEventListener('click', function () { setDecision(+this.dataset.idx, this.dataset.action); });
    });
    orderList.querySelectorAll('.rk-sub-btn-pick[data-idx]').forEach(btn => {
        btn.addEventListener('click', function () { openDrawer(+this.dataset.idx, +this.dataset.item, +this.dataset.qty); });
    });

    topPillOrders.style.display = '';
    topOrderCount.textContent   = orders.length;
    scanCounter.textContent     = orders.length + ' pesanan';

    renderSisa();
    updateConfirmBtn();
}

function renderCard(o, idx) {
    const { no, found, order, decision } = o;
    const cls = 'rk-order-card' + (decision ? ' decided-' + decision : '');

    const isLast = idx === orders.length - 1;

    if (!found) {
        return `<div class="${cls}" id="ocard-${idx}">
          <div class="rk-order-hdr" onclick="toggleCard(${idx})">
            <span class="rk-order-no">${no}</span>
            ${statusBadge('not_found')}
            <span class="rk-order-chev ${isLast?'open':''}" id="chev-${idx}">▼</span>
          </div>
          <div id="obody-${idx}" style="display:${isLast?'':'none'}">
            <div class="rk-order-body">
              <p style="color:#6b7280;font-size:.84rem;margin:0">
                Nomor pesanan tidak ditemukan di sistem.<br>
                <span style="color:#9ca3af;font-size:.78rem">Pastikan pesanan sudah di-import atau coba sync ulang dari marketplace.</span>
              </p>
              <div class="rk-action-strip">
                <span style="font-size:.77rem;color:#9ca3af;font-weight:600">Aksi:</span>
                <button class="rk-act-btn pending ${decision==='pending'?'on':''}" data-idx="${idx}" data-action="pending">Tunda</button>
                <button class="rk-act-btn skip    ${decision==='skip'   ?'on':''}" data-idx="${idx}" data-action="skip">Abaikan</button>
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

    const mpBadge = order.source === 'marketplace_order' ? '<span class="rk-sbadge sb-mp">Marketplace</span>' : '';

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
        <span class="rk-order-no">${no}</span>
        ${order.store_name ? `<span class="rk-order-store">${order.store_name}</span>` : ''}
        ${mpBadge}
        ${order.date ? `<span style="font-size:.73rem;color:#94a3b8">${fmtDate(order.date)}</span>` : ''}
        ${decBadge}
        <span class="rk-order-chev ${isLast?'open':''}" id="chev-${idx}">▼</span>
      </div>
      <div id="obody-${idx}" style="display:${isLast?'':'none'}">
        <div class="rk-order-body">
          ${linesHtml}
          <div class="rk-action-strip">
            <span style="font-size:.77rem;color:#9ca3af;font-weight:600">Keputusan:</span>
            <button class="rk-act-btn fulfill ${decision==='fulfill'?'on':''}" data-idx="${idx}" data-action="fulfill">Siap Kirim</button>
            <button class="rk-act-btn pending ${decision==='pending'?'on':''}" data-idx="${idx}" data-action="pending">Tunda</button>
            <button class="rk-act-btn skip    ${decision==='skip'   ?'on':''}" data-idx="${idx}" data-action="skip">Abaikan</button>
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
    saveState();
    renderAll();
}

function updateConfirmBtn() {
    const foundOrders = orders.filter(o => o.found);
    const allDecided  = foundOrders.length > 0 && foundOrders.every(o => o.decision);
    topConfirmBtn.disabled = !allDecided;
    topConfirmBtn.classList.toggle('active', allDecided);
    if (allDecided) document.getElementById('ph3').classList.add('active');
    else            document.getElementById('ph3').classList.remove('active');
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

window.confirmPickSub = function () {
    if (!drawerCtx || !drawerPendingSub) return;
    const { orderIdx, lineItemId } = drawerCtx;
    orders[orderIdx].subs[lineItemId] = {
        sub_id:   drawerPendingSub.item_id,
        sub_code: drawerPendingSub.item_code,
        sub_name: drawerPendingSub.item_name,
        qty:      drawerPendingQty,
    };
    saveState();
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

    if (!sisa.length) {
        sisaCard.style.display = 'none';
        return;
    }

    sisaCard.style.display = '';

    const totalSisa = sisa.reduce((a, s) => a + s.qty_sisa, 0);
    const hasOrders = orders.length > 0;
    const foundOrders = orders.filter(o => o.found);
    const allDecided = foundOrders.length > 0 && foundOrders.every(o => o.decision);
    const isWarning  = hasOrders && allDecided && sisa.length > 0;

    const rows = sisa.map(s => `
        <div class="rk-sisa-row">
            <span class="rk-sisa-code">${s.item_code}</span>
            <span class="rk-sisa-name">${s.item_name}</span>
            <span class="rk-sisa-qty">Sisa ${FMT.format(s.qty_sisa)} pcs</span>
        </div>`).join('');

    const note = isWarning
        ? `<div class="rk-sisa-note">Masih ada <b>${FMT.format(totalSisa)} pcs</b> yang belum masuk pesanan mana pun. Scan pesanan tambahan atau abaikan jika memang lebih.</div>`
        : hasOrders
            ? `<div class="rk-sisa-note">Barang-barang ini ada di batch tapi belum dialokasikan ke pesanan yang discan.</div>`
            : `<div class="rk-sisa-note">Semua item batch masih belum dialokasikan. Scan pesanan untuk mulai mencocokkan stok.</div>`;

    sisaCard.innerHTML = `
        <div class="rk-sisa-card ${isWarning ? 'has-sisa' : ''}" id="sisaCardInner">
            <div class="rk-sisa-hdr" onclick="toggleSisa()">
                <span class="rk-sisa-title">${hasOrders ? 'Sisa Stok Batch' : 'Stok Batch Belum Dialokasikan'}${isWarning ? ' — Ada Kelebihan' : ''}</span>
                <span class="shp-pill" style="font-size:.7rem;padding:.12rem .55rem">
                    ${FMT.format(totalSisa)} pcs · ${sisa.length} SKU
                </span>
                <span style="margin-left:auto;color:#94a3b8;font-size:.75rem;transition:transform .2s" id="sisaChev">▼</span>
            </div>
            <div id="sisaBody">
                <div class="rk-sisa-body">
                    ${rows}
                    ${note}
                </div>
            </div>
        </div>`;
}

let sisaOpen = true;
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
    orders = []; poolUsed = {};
    clearState();
    renderAll();
    document.getElementById('restoreBanner').style.display = 'none';
    [tickerOk, tickerWarn, tickerErr].forEach(t => t.style.display = 'none');
    toast('ok', 'Rekonsiliasi direset.');
    focusInput();
};

/* ══════════════════════════════════════════════
   KONFIRMASI
══════════════════════════════════════════════ */
topConfirmBtn.addEventListener('click', async function () {
    const btn = this;
    btn.disabled = true; btn.textContent = 'Menyimpan...';

    try {
        const fd = new FormData();
        fd.append('_token', CSRF);
        orders.forEach((o, i) => {
            fd.append(`decisions[${i}][order_no]`,    o.no);
            fd.append(`decisions[${i}][action]`,      o.decision || 'skip');
            if (o.order?.invoice_id) fd.append(`decisions[${i}][invoice_id]`, o.order.invoice_id);
        });

        const res  = await fetch(APPLY_URL, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: fd,
        });
        const data = await res.json();
        if (!res.ok || data.status !== 'ok') throw new Error(data.message || 'Gagal menyimpan.');

        toast('ok', data.message);
        clearState();

        const pendingNos = data.pending_nos || [];
        const pendingTxt = pendingNos.length
            ? '\n\nPesanan pending (' + pendingNos.length + '):\n' + pendingNos.join('\n')
            : '';

        const goSubmit = confirm('Rekonsiliasi disimpan.' + pendingTxt + '\n\nLanjut submit shipment & potong stok sekarang?');

        if (goSubmit) {
            const sf = document.createElement('form');
            sf.method = 'POST'; sf.action = data.submit_url;
            sf.innerHTML = `<input type="hidden" name="_token" value="${CSRF}">`;
            document.body.appendChild(sf); sf.submit();
        } else {
            window.location.href = data.edit_url || EDIT_URL;
        }
    } catch (err) {
        toast('err', err.message);
        btn.disabled = false;
        btn.classList.add('active');
        btn.textContent = 'Konfirmasi';
    }
});

/* ── init: restore state dari localStorage ── */
window.addEventListener('load', async function () {
    const status = loadState();
    const banner = document.getElementById('restoreBanner');
    const bannerText = document.getElementById('restoreBannerText');

    if (status === 'stale') {
        // Batch berubah sejak terakhir scan — data lama tidak bisa dipercaya
        renderAll();
        if (banner) {
            banner.style.display = 'flex';
            banner.style.background = 'rgba(185,28,28,.07)';
            banner.style.borderColor = 'rgba(185,28,28,.3)';
        }
        if (bannerText) {
            bannerText.style.color = 'var(--shp-err)';
            bannerText.textContent = 'Batch berubah sejak scan terakhir - sedang re-analisis...';
        }
        // Auto re-analisis dan tunggu selesai sebelum lanjut
        await window.reAnalisis();
    } else if (status === 'ok') {
        renderAll();
        if (banner) {
            banner.style.display = 'flex';
            banner.style.background = 'var(--shp-warn-bg)';
        }
        if (bannerText) {
            bannerText.style.color = 'var(--shp-warn)';
            bannerText.textContent = orders.length + ' pesanan dipulihkan dari sesi sebelumnya.';
        }
        toast('warn', orders.length + ' pesanan dipulihkan');
    } else {
        renderAll();
    }

    focusInput();
});

})();
</script>
@endpush
