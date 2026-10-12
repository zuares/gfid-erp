{{-- resources/views/sales/shipments/edit_order_first.blade.php --}}
@extends('layouts.app')

@section('title', 'Scan Order • ' . $shipment->code)

@push('head')
<style>
    :root {
        --sr-accent: #334155;
        --sr-accent-2: #1f2937;
        --sr-accent-bg: rgba(148, 163, 184, .08);
        --sr-text: #111827;
        --sr-muted: #64748b;
        --sr-mobile-nav-offset: calc(78px + env(safe-area-inset-bottom, 0px));
    }

    .sr-scan-page {
        max-width: 1100px;
        margin: 0 auto;
        padding: 0 .75rem 6rem;
        color: var(--sr-text);
        background: #f6f8fb;
    }

    .sr-topbar {
        position: sticky;
        top: 0;
        z-index: 300;
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        gap: .6rem;
        margin: 0 -.75rem;
        padding: .5rem .85rem;
        border-bottom: 1px solid rgba(148, 163, 184, .16);
        background: rgba(255, 255, 255, .94);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }

    .sr-top-main { min-width: 0; }

    .sr-top-actions {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        justify-content: flex-end;
    }

    .sr-title {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 750;
        letter-spacing: .04em;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .sr-sub {
        color: var(--sr-muted);
        font-size: .77rem;
        font-weight: 500;
    }

    .sr-shell {
        display: grid;
        gap: .5rem;
        margin-top: .5rem;
    }

    .sr-workflow-stepper {
        display: flex;
        align-items: center;
        gap: .35rem;
        flex-wrap: wrap;
        padding: .3rem 0;
        border: 0;
        border-radius: 0;
        background: transparent;
        box-shadow: none;
    }

    .sr-flow-step {
        display: inline-flex;
        align-items: center;
        min-height: 28px;
        padding: .18rem .5rem;
        border-radius: 5px;
        border: 1px solid rgba(148, 163, 184, .2);
        color: #64748b;
        font-size: .72rem;
        font-weight: 500;
        white-space: nowrap;
    }

    .sr-flow-step.active {
        color: #fff;
        background: var(--sr-accent);
        border-color: var(--sr-accent);
    }

    .sr-flow-step.done {
        color: #334155;
        background: transparent;
    }

    .sr-flow-sep {
        color: #cbd5e1;
        font-size: .72rem;
    }

    .sr-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 38px;
        border-radius: 8px;
        padding: .38rem 1rem;
        border: 1px solid rgba(148, 163, 184, .5);
        background: transparent;
        color: #6b7280;
        font-size: .77rem;
        font-weight: 500;
        letter-spacing: 0;
        text-decoration: none;
        cursor: pointer;
        transition: background .12s, color .12s, border-color .12s;
    }

    .sr-btn:hover {
        background: rgba(226, 232, 240, .7);
        color: #374151;
    }

    .sr-btn-primary {
        border-color: var(--sr-accent);
        background: var(--sr-accent);
        color: #fff;
        font-weight: 650;
    }

    .sr-btn-primary:hover {
        border-color: var(--sr-accent-2);
        background: var(--sr-accent-2);
        color: #fff;
    }

    .sr-btn-danger {
        border-color: rgba(185, 28, 28, .28);
        color: #991b1b;
        background: transparent;
    }

    .sr-btn-danger:hover {
        border-color: rgba(185, 28, 28, .4);
        color: #7f1d1d;
        background: rgba(254, 242, 242, .75);
    }

    .sr-btn:disabled,
    .sr-btn.is-disabled,
    .sr-btn[aria-disabled="true"] {
        opacity: .45;
        cursor: not-allowed;
        pointer-events: none;
    }

    .sr-btn:focus-visible,
    .sr-mini-btn:focus-visible,
    .sr-qty-input:focus-visible,
    .sr-scan-input:focus-visible {
        outline: 0;
        box-shadow: 0 0 0 .18rem var(--sr-accent-bg) !important;
    }

    .sr-panel {
        border: 1px solid #dbe3ee;
        border-radius: 8px;
        background: #fff;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .035);
    }

    .sr-panel-body { padding: .72rem .85rem; }

    .sr-meta-panel {
        background: transparent;
        border-color: transparent;
    }

    .sr-meta-panel .sr-panel-body { padding: .12rem .15rem; }

    .sr-meta {
        display: flex;
        align-items: center;
        gap: .45rem;
        flex-wrap: wrap;
    }

    .sr-meta-item {
        border: 0;
        border-radius: 0;
        padding: 0;
        background: transparent;
        min-width: 0;
        box-shadow: none;
    }

    .sr-meta-label {
        color: #9ca3af;
        font-size: .65rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        font-weight: 650;
    }

    .sr-meta-value {
        color: #334155;
        font-size: .82rem;
        font-weight: 650;
    }

    .sr-summary {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .45rem;
    }

    .sr-stat {
        border: 1px solid #dbe3ee;
        border-radius: 8px;
        padding: .55rem .7rem;
        background: #fff;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .035);
    }

    .sr-stat-label {
        color: #94a3b8;
        font-size: .62rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        font-weight: 650;
    }

    .sr-stat-value {
        margin-top: .1rem;
        color: #0f172a;
        font-size: 1.05rem;
        font-weight: 800;
    }

    .sr-scan-card {
        display: grid;
        gap: .65rem;
    }

    .sr-scan-card.mode-order {
        --scan-accent: #6d5dd3;
        --scan-accent-soft: #f8f7ff;
        --scan-accent-border: #dcd8f7;
        --scan-accent-badge: #eeebff;
        --scan-accent-badge-text: #5747b3;
        --scan-accent-ring: rgba(109, 93, 211, .18);
    }

    .sr-scan-card.mode-item {
        --scan-accent: #0f8f8c;
        --scan-accent-soft: #f0fdfa;
        --scan-accent-border: #bfe9e4;
        --scan-accent-badge: #d9f7f3;
        --scan-accent-badge-text: #0f716e;
        --scan-accent-ring: rgba(15, 143, 140, .18);
    }

    .sr-mode-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
    }

    .sr-mode {
        display: inline-flex;
        align-items: center;
        min-height: 26px;
        border-radius: 999px;
        padding: .18rem .68rem;
        border: 1px solid var(--scan-accent-border);
        background: var(--scan-accent-badge);
        color: var(--scan-accent-badge-text);
        font-size: .7rem;
        font-weight: 800;
        letter-spacing: .06em;
    }

    .sr-current {
        color: #64748b;
        font-size: .8rem;
        font-weight: 650;
        text-align: right;
    }

    .sr-scan-input {
        min-height: 72px;
        border-radius: 10px !important;
        border: 1px solid var(--scan-accent-border, rgba(148, 163, 184, .22)) !important;
        border-left: 4px solid var(--scan-accent, var(--sr-accent)) !important;
        background: var(--scan-accent-soft, #f8fafc);
        font-size: 1.35rem;
        font-weight: 800;
        letter-spacing: .03em;
        padding: .8rem 1rem;
        text-transform: uppercase;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .03);
    }

    .sr-scan-input:focus {
        border-color: var(--scan-accent, var(--sr-accent)) !important;
        border-left-color: var(--scan-accent, var(--sr-accent)) !important;
        box-shadow: 0 0 0 .18rem var(--scan-accent-ring, var(--sr-accent-bg)) !important;
    }

    .sr-order-section { min-height: 180px; }

    .sr-order-tools {
        padding: .55rem .7rem;
        border-bottom: 1px solid #e2e8f0;
        background: #fff;
    }

    .sr-order-tools-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .75rem;
        margin-bottom: .55rem;
    }

    .sr-section-title {
        color: #1e293b;
        font-size: .86rem;
        font-weight: 800;
    }

    .sr-section-help {
        margin-top: .12rem;
        color: #64748b;
        font-size: .7rem;
        line-height: 1.35;
    }

    .sr-order-tools-count {
        flex: 0 0 auto;
        padding: .22rem .45rem;
        border: 1px solid #dbeafe;
        border-radius: 999px;
        color: #1d4ed8;
        background: #eff6ff;
        font-size: .68rem;
        font-weight: 800;
    }

    .sr-marketplace-tabs {
        display: flex;
        gap: .3rem;
        margin: 0 0 .05rem;
        padding: .22rem;
        border: 1px solid #e2e8f0;
        border-radius: 9px;
        background: #f8fafc;
        overflow-x: auto;
        scrollbar-width: none;
    }

    .sr-marketplace-tabs::-webkit-scrollbar { display: none; }

    .sr-marketplace-tab {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .32rem;
        min-height: 32px;
        flex: 1 0 0;
        padding: .24rem .58rem;
        border: 1px solid transparent;
        border-radius: 7px;
        color: #64748b;
        background: transparent;
        font-size: .68rem;
        font-weight: 750;
        white-space: nowrap;
        cursor: pointer;
        transition: background .12s ease, color .12s ease, border-color .12s ease, box-shadow .12s ease;
    }

    .sr-marketplace-tab:hover { background: #f1f5f9; color: #334155; }
    .sr-marketplace-tab.active { color: #334155; border-color: #dbe3ee; background: #fff; box-shadow: 0 1px 2px rgba(15, 23, 42, .06); }
    .sr-marketplace-tab-count { color: #94a3b8; font-size: .64rem; font-weight: 850; }
    .sr-marketplace-tab::before { content: ''; width: 5px; height: 5px; border-radius: 999px; background: #94a3b8; }
    .sr-marketplace-tab[data-order-marketplace-tab="shopee"]::before { background: #f97316; }
    .sr-marketplace-tab[data-order-marketplace-tab="tiktok"]::before { background: #0f8f8c; }
    .sr-marketplace-tab[data-order-marketplace-tab="anomaly"]::before { background: #d97706; }
    .sr-marketplace-tab[data-order-marketplace-tab="problem"]::before { background: #dc2626; }
    .sr-marketplace-tab[data-order-marketplace-tab="shopee"].active { color: #c2410c; border-color: #fed7aa; background: #fff7ed; }
    .sr-marketplace-tab[data-order-marketplace-tab="tiktok"].active { color: #0f716e; border-color: #bfe9e4; background: #f0fdfa; }
    .sr-marketplace-tab[data-order-marketplace-tab="anomaly"].active { color: #a16207; border-color: #fde68a; background: #fffbeb; }
    .sr-marketplace-tab[data-order-marketplace-tab="problem"].active { color: #b91c1c; border-color: #fecaca; background: #fff1f2; }

    .sr-order-search {
        min-height: 42px;
        margin: .2rem 0 .7rem;
        border-radius: 8px !important;
        font-size: .9rem;
    }

    .sr-order-panel-body {
        max-height: 52vh;
        overflow: auto;
    }

    .sr-orders {
        display: grid;
        gap: .5rem;
    }

    .sr-empty {
        min-height: 150px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.25rem;
        border: 1px dashed rgba(148, 163, 184, .35);
        border-radius: 8px;
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        color: #64748b;
        font-size: .86rem;
        font-weight: 650;
        text-align: center;
    }

    .sr-order {
        border: 1px solid #dbe3ee;
        border-radius: 8px;
        background: #fff;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .025);
    }

    .sr-order.active {
        border-color: rgba(37, 99, 235, .48);
        box-shadow: 0 0 0 2px rgba(37, 99, 235, .08);
    }

    .sr-order.sr-theme-shopee .sr-order-head {
        border-bottom-color: #fed7aa;
        background: #fff7ed;
    }

    .sr-order.sr-theme-shopee .sr-order-code { color: #c2410c; }

    .sr-order.sr-theme-green .sr-order-head {
        border-bottom-color: #bbf7d0;
        background: #f0fdf4;
    }

    .sr-order.sr-theme-green .sr-order-code { color: #15803d; }

    .sr-order-head {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto auto auto;
        align-items: center;
        gap: .5rem;
        padding: .55rem .7rem;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
        cursor: pointer;
    }

    .sr-order.active .sr-order-head { background: #eff6ff; }

    .sr-order-code {
        color: #0f172a;
        font-size: .95rem;
        font-weight: 850;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .sr-order-code-line {
        display: flex;
        align-items: center;
        gap: .4rem;
        min-width: 0;
    }

    .sr-duplicate-badge {
        display: inline-flex;
        align-items: center;
        flex-shrink: 0;
        min-height: 20px;
        padding: .12rem .42rem;
        border: 1px solid #fcd34d;
        border-radius: 999px;
        background: #fffbeb;
        color: #a16207;
        font-size: .62rem;
        font-weight: 850;
        letter-spacing: .01em;
        line-height: 1;
    }

    .sr-missing-item-badge {
        display: inline-flex;
        align-items: center;
        flex-shrink: 0;
        min-height: 20px;
        padding: .12rem .42rem;
        border: 1px solid #cbd5e1;
        border-radius: 999px;
        background: #f8fafc;
        color: #64748b;
        font-size: .62rem;
        font-weight: 750;
        letter-spacing: .01em;
        line-height: 1;
    }

    .sr-order-info {
        color: #94a3b8;
        font-size: .72rem;
        margin-top: .1rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .sr-order-qty {
        min-width: 38px;
        border-radius: 999px;
        padding: .14rem .45rem;
        background: #e5e7eb;
        color: #374151;
        text-align: center;
        font-size: .78rem;
        font-weight: 800;
    }

    .sr-delete-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 28px;
        height: 28px;
        padding: 0 .42rem;
        border: 1px solid rgba(248, 113, 113, .35);
        border-radius: 7px;
        background: #fff1f2;
        color: #be123c;
        font-size: .78rem;
        font-weight: 850;
        cursor: pointer;
    }

    .sr-delete-btn:hover {
        background: #ffe4e6;
        border-color: rgba(225, 29, 72, .45);
    }

    .sr-order-chevron {
        color: #94a3b8;
        font-size: .78rem;
        font-weight: 800;
    }

    .sr-order-items {
        border-top: 1px solid rgba(148, 163, 184, .1);
        padding: 0;
        display: grid;
        gap: 0;
        background: #fff;
    }

    .sr-items-head {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto auto;
        align-items: center;
        gap: .5rem;
        padding: .52rem .65rem;
        border-bottom: 1px solid #dbe3ee;
        background: #f8fafc;
        color: #64748b;
        font-size: .62rem;
        font-weight: 850;
        letter-spacing: .05em;
        text-transform: uppercase;
    }

    .sr-items-head span:nth-child(3) { text-align: center; }

    .sr-item-row {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto auto;
        align-items: center;
        gap: .5rem;
        padding: .58rem .65rem;
        border-bottom: 1px solid #edf1f6;
        border-radius: 0;
        background: #fff;
        transition: background .12s ease;
    }

    .sr-item-row:hover { background: #f8fafc; }
    .sr-item-row.is-scan-highlight {
        background: #ecfdf5;
        box-shadow: inset 3px 0 0 #0f8f8c;
        animation: sr-item-scan-highlight 1.8s ease-out both;
    }
    .sr-item-row.is-scan-highlight .sr-item-index {
        background: #ccfbf1;
        color: #0f716e;
    }
    .sr-item-row:last-child { border-bottom: 0; }

    @keyframes sr-item-scan-highlight {
        0% { background: #ccfbf1; }
        100% { background: #fff; }
    }

    .sr-item-index {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.5rem;
        height: 1.5rem;
        border-radius: 6px;
        background: #dbeafe;
        color: #1d4ed8;
        font-size: .72rem;
        font-weight: 850;
    }

    .sr-item-code {
        font-weight: 800;
        color: #0f172a;
        font-size: .85rem;
    }

    .sr-item-name {
        color: #94a3b8;
        font-size: .7rem;
        line-height: 1.2;
        margin-top: .08rem;
    }

    .sr-item-qty {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .32rem;
        min-width: 64px;
        padding: .28rem .5rem;
        border: 1px solid #e2e8f0;
        border-radius: 7px;
        color: #334155;
        background: #f8fafc;
        font-size: .78rem;
        font-weight: 850;
        white-space: nowrap;
    }

    .sr-item-qty-label {
        color: #64748b;
        font-size: .62rem;
        font-weight: 750;
        letter-spacing: .03em;
        text-transform: uppercase;
    }

    .sr-item-qty-value {
        color: #0f172a;
        font-size: .84rem;
        font-variant-numeric: tabular-nums;
    }

    .sr-qty-edit {
        display: inline-flex;
        align-items: center;
        gap: .18rem;
        justify-self: end;
        padding: .18rem .22rem .18rem .45rem;
        border: 1px solid rgba(148, 163, 184, .28);
        border-radius: 8px;
        background: #f8fafc;
    }

    .sr-qty-cell {
        justify-self: end;
    }

    .sr-qty-label {
        color: #64748b;
        font-size: .62rem;
        font-weight: 850;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .sr-qty-input {
        width: 46px;
        min-height: 30px;
        padding: .2rem .25rem;
        border: 0;
        border-radius: 5px;
        color: #334155;
        background: #fff;
        font-size: .78rem;
        font-weight: 850;
        text-align: right;
    }

    .sr-qty-input:focus {
        outline: 2px solid rgba(37, 99, 235, .2);
        outline-offset: 1px;
    }

    .sr-qty-save {
        min-height: 30px;
        padding: .2rem .55rem;
        border: 1px solid #2563eb;
        border-radius: 6px;
        background: #2563eb;
        color: #fff;
        font-size: .7rem;
        font-weight: 800;
        cursor: pointer;
    }

    .sr-qty-save:hover { background: #1d4ed8; border-color: #1d4ed8; }
    .sr-qty-save:disabled { opacity: .55; cursor: wait; }

    .sr-qty-trigger {
        border: 1px solid #cbd5e1;
        cursor: pointer;
        font: inherit;
    }

    .sr-qty-trigger:hover {
        border-color: rgba(37, 99, 235, .3);
        background: #eff6ff;
        color: #1d4ed8;
    }

    @media (max-width: 640px) {
        .sr-items-head { grid-template-columns: auto minmax(0, 1fr) auto; }
        .sr-items-head span:last-child { display: none; }
        .sr-item-row { grid-template-columns: auto minmax(0, 1fr) auto; }
        .sr-item-delete { grid-column: 3; grid-row: 2; justify-self: end; }
        .sr-qty-cell { grid-column: 3; grid-row: 1; }
    }

    .sr-actions {
        display: flex;
        align-items: center;
        gap: .5rem;
        justify-content: flex-end;
        padding: .65rem 0 0;
    }

    .sr-toast {
        position: fixed;
        left: 50%;
        bottom: 1.25rem;
        z-index: 10000;
        transform: translateX(-50%);
        display: none;
        max-width: min(92vw, 520px);
        border-radius: 999px;
        padding: .72rem 1rem;
        background: #0f172a;
        color: #fff;
        font-size: .86rem;
        font-weight: 700;
        box-shadow: 0 16px 40px rgba(15, 23, 42, .2);
    }

    .sr-toast.show { display: block; }
    .sr-toast.ok { background: #0f172a; }
    .sr-toast.error { background: #991b1b; }

    /* Skala halaman mengikuti modul sidebar: ringkas, konsisten, tetap mudah
       disentuh pada kontrol scanner dan qty. */
    .sr-scan-page {
        font-size: .82rem;
    }

    .sr-title {
        font-size: 1rem;
    }

    .sr-sub,
    .sr-section-help,
    .sr-order-info {
        font-size: .7rem;
    }

    .sr-btn,
    .gf-scan-sound-toggle {
        min-height: 34px;
        padding: .3rem .72rem;
        font-size: .7rem;
    }

    .sr-btn .bi,
    .gf-scan-sound-toggle .bi,
    .sr-delete-btn,
    .sr-order-chevron {
        font-size: .84rem;
        line-height: 1;
    }

    .sr-scan-input {
        font-size: 1.15rem;
    }

    .sr-section-title,
    .sr-order-code {
        font-size: .82rem;
    }

    .sr-item-code {
        font-size: .78rem;
    }

    .sr-item-name,
    .sr-current,
    .sr-item-qty,
    .sr-qty-input {
        font-size: .74rem;
    }

    .sr-item-qty-value {
        font-size: .78rem;
    }

    .sr-item-index {
        width: 1.4rem;
        height: 1.4rem;
        font-size: .68rem;
    }

    .sr-marketplace-tab,
    .sr-flow-step,
    .sr-order-tools-count,
    .sr-mode {
        font-size: .66rem;
    }

    /* Metadata dibuat sebagai satu strip ringan agar tidak bersaing dengan
       area scanner dan daftar order. */
    .sr-meta {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0;
        padding: .12rem;
        border: 1px solid #edf1f6;
        border-radius: 8px;
        background: #fafbfc;
    }

    .sr-meta-item {
        min-width: 0;
        padding: .32rem .55rem;
        border-left: 1px solid #edf1f6;
    }

    .sr-meta-item:first-child {
        border-left: 0;
    }

    .sr-meta-label {
        font-size: .58rem;
        letter-spacing: .07em;
        line-height: 1.15;
    }

    .sr-meta-value {
        margin-top: .12rem;
        overflow: hidden;
        color: #475569;
        font-size: .74rem;
        font-weight: 650;
        line-height: 1.25;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    @media (max-width: 640px) {
        .sr-scan-page {
            padding: 0 .45rem 6rem;
        }
        .sr-topbar {
            margin: 0 -.45rem;
            padding: .46rem .55rem;
            grid-template-columns: minmax(0, 1fr);
        }
        .sr-title {
            max-width: calc(100vw - 1.1rem);
            font-size: .92rem;
            letter-spacing: .02em;
        }
        .sr-sub,
        .sr-top-actions .sr-btn[href] {
            display: none;
        }
        .sr-top-actions {
            grid-column: 1 / -1;
            justify-content: stretch;
        }
        .sr-top-actions .sr-btn {
            width: 100%;
            min-height: 34px;
            font-size: .64rem;
        }
        .sr-shell {
            gap: .45rem;
            margin-top: .45rem;
        }
        .sr-workflow-stepper {
            gap: .24rem;
            padding: .38rem .45rem;
            overflow-x: auto;
            flex-wrap: nowrap;
            border-radius: 6px;
            scrollbar-width: none;
        }
        .sr-workflow-stepper::-webkit-scrollbar { display: none; }
        .sr-flow-step {
            min-height: 26px;
            padding: .15rem .42rem;
            font-size: .64rem;
            font-weight: 550;
        }
        .sr-flow-sep { font-size: .62rem; }
        .sr-meta-panel {
            background: transparent;
            border: 0;
            box-shadow: none;
        }
        .sr-meta-panel .sr-panel-body { padding: 0; }
        .sr-meta { gap: .4rem; }
        .sr-meta-item {
            display: none;
            border-radius: 6px;
            padding: .5rem .65rem;
            box-shadow: none;
        }
        .sr-meta-store { display: block; }
        .sr-meta-label { display: none; }
        .sr-meta-value {
            margin-top: 0;
            font-size: .8rem;
            line-height: 1.15;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sr-panel { border-radius: 6px; }
        .sr-panel-body { padding: .5rem; }
        .sr-scan-card { gap: .48rem; }
        .sr-mode-row {
            align-items: center;
            flex-wrap: nowrap;
        }
        .sr-mode {
            padding: .16rem .56rem;
            font-size: .68rem;
            letter-spacing: .03em;
            white-space: nowrap;
        }
        .sr-current {
            min-width: 0;
            overflow: hidden;
            text-align: right;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: .7rem;
        }
        .sr-scan-input {
            min-height: 62px;
            border-radius: 8px !important;
            font-size: 1.08rem;
            padding: .7rem .75rem;
        }
        .sr-scan-input::placeholder { font-size: .84rem; }
        .sr-summary { gap: .3rem; }
        .sr-stat {
            padding: .28rem .38rem;
            border-radius: 6px;
            box-shadow: none;
        }
        .sr-stat-label {
            font-size: .54rem;
            letter-spacing: 0;
        }
        .sr-stat-value {
            margin-top: 0;
            font-size: .76rem;
            font-weight: 600;
        }
        .sr-orders { gap: .42rem; }
        .sr-order-panel-body {
            max-height: none;
            overflow: visible;
        }
        .sr-order-tools { padding: .42rem .5rem; }
        .sr-order-search {
            min-height: 40px;
            border-radius: 8px !important;
            font-size: .86rem;
            padding: .48rem .65rem;
        }
        .sr-order { border-radius: 6px; }
        .sr-order-head {
            padding: .46rem .58rem;
            align-items: center;
        }
        .sr-order-info { display: none; }
        .sr-order-code {
            font-size: .9rem;
            max-width: calc(100vw - 6rem);
        }
        .sr-order-qty {
            min-width: 34px;
            padding: .12rem .42rem;
            font-size: .74rem;
        }
        .sr-actions {
            position: fixed;
            left: 0;
            right: 0;
            bottom: var(--sr-mobile-nav-offset);
            z-index: 9998;
            padding: .55rem;
            border: 0;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
            backdrop-filter: none;
            -webkit-backdrop-filter: none;
        }
        .sr-actions .sr-btn {
            flex: 1;
            min-height: 48px;
            padding: .45rem .65rem;
            font-size: .72rem;
        }
        .sr-toast {
            bottom: calc(var(--sr-mobile-nav-offset) + 4.25rem);
        }

        .sr-meta {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .3rem;
            padding: 0;
            border: 0;
            background: transparent;
        }

        .sr-meta-item,
        .sr-meta-item.sr-meta-store {
            display: block;
            padding: .38rem .5rem;
            border: 1px solid #edf1f6;
            border-radius: 7px;
            background: #fafbfc;
        }

        .sr-meta-item:first-child {
            border-left: 1px solid #edf1f6;
        }

        .sr-meta-value {
            font-size: .7rem;
        }
    }
</style>
@endpush

@section('content')
@php
    $initialOrders = collect($savedOrderScans ?? [])
        ->map(function ($scan) {
            return [
                'code' => strtoupper((string) ($scan['code'] ?? $scan['order_no'] ?? '')),
                'label' => $scan['label'] ?? 'Pencatatan order',
                'duplicateCount' => (int) ($scan['duplicate_count'] ?? 0),
                'items' => $scan['items'] ?? [],
            ];
        })
        ->filter(fn ($scan) => $scan['code'] !== '')
        ->values();

    $initialLines = $shipment->lines
        ->map(fn ($line) => [
            'id' => $line->id,
            'order_scan_id' => $line->shipment_order_scan_id,
            'item_id' => $line->item_id,
            'code' => $line->item->code ?? '-',
            'name' => $line->item->name ?? '',
            'qty' => (int) $line->qty_scanned,
        ])
        ->values();

    $canDeleteShipmentRows = auth()->user()?->role === 'owner';
    $canEditShipmentQty = in_array(auth()->user()?->role, ['owner', 'admin'], true);
@endphp

    @php
        $shipmentTypeLabel = match ($shipment->shipment_type ?? 'manual') {
            'affiliate' => 'Paket Afiliasi',
            'marketplace' => 'Marketplace',
            default => 'Manual',
        };
    @endphp

<div class="sr-scan-page">
    <div class="sr-topbar">
        <div class="sr-top-main">
            <h1 class="sr-title">{{ $shipment->code }}</h1>
            <div class="sr-sub">{{ $shipmentTypeLabel }} · scan order terlebih dahulu untuk mulai.</div>
        </div>
        <div class="sr-top-actions">
            <button type="button" id="gfidScanSoundToggle" class="gf-scan-sound-toggle" aria-pressed="true">🔊 Suara ON</button>
            @if ($shipment->status === 'draft')
                <a href="{{ route('sales.shipments.cancel_form', $shipment) }}" class="sr-btn sr-btn-danger"><i class="bi bi-x-circle" aria-hidden="true"></i> Batalkan</a>
            @endif
            <a href="{{ route('sales.shipments.edit', $shipment) }}" class="sr-btn"><i class="bi bi-upc-scan" aria-hidden="true"></i> Scan Item</a>
            <a href="{{ route('sales.shipments.show', $shipment) }}" class="sr-btn"><i class="bi bi-info-circle" aria-hidden="true"></i> Detail</a>
        </div>
    </div>

    <div class="sr-shell">
        <div class="sr-workflow-stepper" id="shipmentWorkflowStepper" aria-label="Workflow Shipment">
            <span class="sr-flow-step active" data-flow-step="order">Scan Order</span>
            <span class="sr-flow-sep">-&gt;</span>
            <span class="sr-flow-step" data-flow-step="item">Scan Item</span>
            <span class="sr-flow-sep">-&gt;</span>
            <span class="sr-flow-step" data-flow-step="review">Cek Shipment</span>
            <span class="sr-flow-sep">-&gt;</span>
            <span class="sr-flow-step" data-flow-step="confirm">Submit</span>
        </div>

        <div class="sr-panel sr-meta-panel">
            <div class="sr-panel-body">
                <div class="sr-meta">
                    <div class="sr-meta-item sr-meta-store">
                        <div class="sr-meta-label">Tipe</div>
                        <div class="sr-meta-value">{{ $shipmentTypeLabel }}</div>
                    </div>
                    <div class="sr-meta-item sr-meta-store">
                        <div class="sr-meta-label">Channel</div>
                        <div class="sr-meta-value">{{ $shipment->store ? (($shipment->store->code ?? '-') . ' - ' . ($shipment->store->name ?? '-')) : 'Belum dihubungkan' }}</div>
                    </div>
                    <div class="sr-meta-item">
                        <div class="sr-meta-label">Tanggal</div>
                        <div class="sr-meta-value">{{ optional($shipment->date)->format('d M Y') }}</div>
                    </div>
                    <div class="sr-meta-item">
                        <div class="sr-meta-label">Status</div>
                        <div class="sr-meta-value">{{ ucfirst($shipment->status) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="sr-summary">
            <div class="sr-stat">
                <div class="sr-stat-label">Pesanan</div>
                <div class="sr-stat-value" id="sumOrders">0</div>
            </div>
            <div class="sr-stat">
                <div class="sr-stat-label">Item</div>
                <div class="sr-stat-value" id="sumItems">0</div>
            </div>
            <div class="sr-stat">
                <div class="sr-stat-label">Qty</div>
                <div class="sr-stat-value" id="sumQty">0</div>
            </div>
        </div>

        @if ($shipment->status === 'draft')
            <div class="sr-panel">
                <div class="sr-panel-body">
                    <div class="sr-scan-card mode-order" id="scanCard">
                        <div class="sr-mode-row">
                            <span class="sr-mode" id="modeBadge">SCAN ORDER</span>
                            <span class="sr-current" id="currentLabel">Belum ada pesanan aktif</span>
                        </div>
                        <input type="text"
                               id="scanInput"
                               class="form-control sr-scan-input"
                               placeholder="Scan nomor pesanan"
                               inputmode="text"
                               autocomplete="off"
                               autofocus>
                    </div>
                </div>
            </div>
        @endif

        <div class="sr-panel sr-order-section">
            <div class="sr-order-tools" id="orderTools">
                <div class="sr-order-tools-head">
                    <div>
                        <div class="sr-section-title">Order tercatat</div>
                        <div class="sr-section-help">
                            @if($canEditShipmentQty)
                                Klik qty item untuk mengubah jumlah.
                            @else
                                Pilih order untuk melihat item yang tercatat.
                            @endif
                        </div>
                    </div>
                    <span class="sr-order-tools-count" id="orderToolsCount">0 order</span>
                </div>
                <input type="search" id="orderSearch" class="form-control sr-order-search" placeholder="Cari order" autocomplete="off">
                <div class="sr-marketplace-tabs" role="tablist" aria-label="Filter order berdasarkan marketplace">
                    <button type="button" class="sr-marketplace-tab active" data-order-marketplace-tab="all" role="tab" aria-selected="true">
                        <span>Semua</span><span class="sr-marketplace-tab-count" data-order-marketplace-count="all">0</span>
                    </button>
                    <button type="button" class="sr-marketplace-tab" data-order-marketplace-tab="shopee" role="tab" aria-selected="false">
                        <span>Shopee</span><span class="sr-marketplace-tab-count" data-order-marketplace-count="shopee">0</span>
                    </button>
                    <button type="button" class="sr-marketplace-tab" data-order-marketplace-tab="tiktok" role="tab" aria-selected="false">
                        <span>TikTok</span><span class="sr-marketplace-tab-count" data-order-marketplace-count="tiktok">0</span>
                    </button>
                    <button type="button" class="sr-marketplace-tab" data-order-marketplace-tab="anomaly" role="tab" aria-selected="false">
                        <span>Anomali</span><span class="sr-marketplace-tab-count" data-order-marketplace-count="anomaly">0</span>
                    </button>
                    <button type="button" class="sr-marketplace-tab" data-order-marketplace-tab="problem" role="tab" aria-selected="false">
                        <span>Order Bermasalah</span><span class="sr-marketplace-tab-count" data-order-marketplace-count="problem">0</span>
                    </button>
                </div>
            </div>
            <div class="sr-panel-body sr-order-panel-body">
                <div class="sr-orders" id="ordersWrap">
                    <div class="sr-empty">Belum ada item shipment.</div>
                </div>
            </div>
        </div>

        <div class="sr-actions">
            @if ($shipment->status === 'draft')
                <button type="button" class="sr-btn" id="nextOrderBtn"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Order Baru</button>
                <a href="{{ route('sales.shipments.confirm_orders', $shipment) }}"
                   class="sr-btn sr-btn-primary"
                   id="submitBtn"
                   aria-disabled="true"><i class="bi bi-clipboard-check" aria-hidden="true"></i> Cek Shipment</a>
            @else
                <a href="{{ route('sales.shipments.show', $shipment) }}" class="sr-btn"><i class="bi bi-info-circle" aria-hidden="true"></i> Detail</a>
                <a href="{{ route('sales.shipments.index') }}" class="sr-btn sr-btn-primary"><i class="bi bi-list-ul" aria-hidden="true"></i> Daftar Shipment</a>
            @endif
        </div>
    </div>
</div>

<div id="toast" class="sr-toast"></div>
@endsection

@push('scripts')
<script>
(function () {
    const scanUrl = @json(route('sales.shipments.scan_item', $shipment));
    const recordOrderUrl = @json(route('sales.shipments.scan_order_store', $shipment));
    const deleteOrderUrl = @json(route('sales.shipments.delete_order_scan', $shipment));
    const updateQtyUrlTemplate = @json(route('sales.shipments.update_line_qty', '__LINE_ID__'));
    const deleteLineUrlTemplate = @json(route('sales.shipments.destroy_line', '__LINE_ID__'));
    const csrf = @json(csrf_token());
    const isDraft = @json($shipment->status === 'draft');
    const canDelete = @json($canDeleteShipmentRows);
    const canEditQty = @json($canEditShipmentQty);
    const initialOrders = @json($initialOrders);
    const initialLines = @json($initialLines);

    const state = { mode: 'order', current: null, expanded: null, search: '', marketplaceTab: 'all', highlightedItemKey: null, orders: [] };
    const lastScanStack = [];
    let highlightTimer = null;

    const scanInput = document.getElementById('scanInput');
    const scanCard = document.getElementById('scanCard');
    const modeBadge = document.getElementById('modeBadge');
    const currentLabel = document.getElementById('currentLabel');
    const workflowStepper = document.getElementById('shipmentWorkflowStepper');
    const nextOrderBtn = document.getElementById('nextOrderBtn');
    const orderSearch = document.getElementById('orderSearch');
    const ordersWrap = document.getElementById('ordersWrap');
    const marketplaceTabs = document.querySelectorAll('[data-order-marketplace-tab]');
    const sumOrders = document.getElementById('sumOrders');
    const sumItems = document.getElementById('sumItems');
    const sumQty = document.getElementById('sumQty');
    const orderToolsCount = document.getElementById('orderToolsCount');
    const submitBtn = document.getElementById('submitBtn');
    const toastEl = document.getElementById('toast');

    window.GFID?.bindScanSoundToggle(document.getElementById('gfidScanSoundToggle'));

    let toastTimer = null;
    let audioCtx = null;

    function normalize(value) {
        const normalized = String(value ?? '').trim();
        if (!normalized || ['undefined', 'null', 'nan'].includes(normalized.toLowerCase())) return '';
        return normalized.toUpperCase();
    }

    function orderTheme(orderCode) {
        const code = normalize(orderCode);
        if (code.startsWith('SPX')) return 'sr-theme-shopee';
        if (code.startsWith('JY')) return 'sr-theme-green';
        return '';
    }

    function marketplaceGroup(orderCode) {
        const code = normalize(orderCode);
        if (code.startsWith('SPX') || code.startsWith('SHP') || code.includes('SHOPEE')) return 'shopee';
        if (code.startsWith('JY') || code.startsWith('TTK') || code.includes('TIKTOK')) return 'tiktok';
        return 'anomaly';
    }

    function itemHighlightKey(orderCode, itemOrLineId, itemCode = '') {
        const lineId = typeof itemOrLineId === 'object'
            ? (itemOrLineId.line_id || itemOrLineId.id || itemOrLineId.code || itemOrLineId.item_code || '')
            : itemOrLineId;
        return `${normalize(orderCode)}::${normalize(lineId || itemCode)}`;
    }

    function highlightScannedItem(orderCode, itemOrLineId, itemCode = '') {
        state.highlightedItemKey = itemHighlightKey(orderCode, itemOrLineId, itemCode);
        render();
        clearTimeout(highlightTimer);
        highlightTimer = setTimeout(() => {
            state.highlightedItemKey = null;
            render();
        }, 1800);
    }

    function selectMarketplaceForOrder(orderCode) {
        state.marketplaceTab = marketplaceGroup(orderCode);
    }

    function isNextOrderCommand(code) {
        return ['ORDER BARU', 'BARU', 'NEXT', 'NEXT ORDER', 'ORDER NEXT'].includes(normalize(code));
    }

    function isResetOrderCommand(code) {
        return ['RESET', 'RESET ORDER'].includes(normalize(code));
    }

    function isUndoCommand(code) {
        return ['UNDO', 'BATAL'].includes(normalize(code));
    }

    function esc(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function audioContext() {
        try {
            const Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return null;
            audioCtx = audioCtx || new Ctx();
            return audioCtx;
        } catch (_) {
            return null;
        }
    }


    function playTone(type = 'ok', fromConfig = false) {
        const eventMap = {
            ok: 'item_success',
            item: 'item_success',
            order: 'order_success',
            orderRepeat: 'order_duplicate',
            next: 'navigation',
            undo: 'undo',
            reset: 'reset',
            errorGuard: 'item_duplicate',
            errorNoOrder: 'order_not_found',
            errorNetwork: 'error_network',
            errorItem: 'error_general',
            error: 'error_general',
        };
        if (!fromConfig && window.GFID && typeof window.GFID.playScanSound === 'function') {
            return window.GFID.playScanSound(eventMap[type] || type, () => playTone(type, true));
        }
        if (window.GFID && typeof window.GFID.isScanSoundEnabled === 'function' && !window.GFID.isScanSoundEnabled()) return;
        if (!window.AudioContext && !window.webkitAudioContext) return;

        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        const ctx = new AudioCtx();

        const presets = {
            // proses normal
            ok: [
                { freq: 880, duration: 0.055, gap: 0 },
                { freq: 1175, duration: 0.065, gap: 0.045 },
            ],
            item: [
                { freq: 932, duration: 0.045, gap: 0 },
                { freq: 1244, duration: 0.05, gap: 0.035 },
            ],
            order: [
                { freq: 587, duration: 0.07, gap: 0 },
                { freq: 784, duration: 0.08, gap: 0.045 },
            ],
            orderRepeat: [
                { freq: 440, duration: 0.07, gap: 0, type: 'triangle', volume: 0.27 },
                { freq: 330, duration: 0.075, gap: 0.045, type: 'triangle', volume: 0.27 },
                { freq: 440, duration: 0.06, gap: 0.04, type: 'triangle', volume: 0.27 },
            ],
            next: [
                { freq: 523, duration: 0.06, gap: 0 },
                { freq: 698, duration: 0.06, gap: 0.035 },
                { freq: 880, duration: 0.075, gap: 0.035 },
            ],

            // aksi koreksi
            undo: [
                { freq: 784, duration: 0.055, gap: 0 },
                { freq: 523, duration: 0.075, gap: 0.04 },
            ],
            reset: [
                { freq: 440, duration: 0.07, gap: 0 },
                { freq: 330, duration: 0.07, gap: 0.05 },
                { freq: 220, duration: 0.09, gap: 0.05 },
            ],

            // guard / error
            errorGuard: [
                { freq: 196, duration: 0.09, gap: 0 },
                { freq: 196, duration: 0.09, gap: 0.055 },
            ],
            errorNoOrder: [
                { freq: 220, duration: 0.08, gap: 0 },
                { freq: 165, duration: 0.105, gap: 0.055 },
            ],
            errorItem: [
                { freq: 180, duration: 0.09, gap: 0 },
                { freq: 140, duration: 0.12, gap: 0.055 },
            ],
            errorNetwork: [
                { freq: 155, duration: 0.08, gap: 0 },
                { freq: 120, duration: 0.08, gap: 0.05 },
                { freq: 100, duration: 0.11, gap: 0.05 },
            ],
            error: [
                { freq: 180, duration: 0.09, gap: 0 },
                { freq: 140, duration: 0.12, gap: 0.055 },
            ],
        };

        const notes = presets[type] || presets.ok;

        let cursor = ctx.currentTime;

        notes.forEach(note => {
            cursor += Number(note.gap || 0);

            const oscillator = ctx.createOscillator();
            const gain = ctx.createGain();

            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(note.freq, cursor);

            gain.gain.setValueAtTime(0.0001, cursor);
            gain.gain.exponentialRampToValueAtTime(0.08, cursor + 0.01);
            gain.gain.exponentialRampToValueAtTime(0.0001, cursor + Number(note.duration || 0.06));

            oscillator.connect(gain);
            gain.connect(ctx.destination);

            oscillator.start(cursor);
            oscillator.stop(cursor + Number(note.duration || 0.06) + 0.025);

            cursor += Number(note.duration || 0.06);
        });

        window.setTimeout(() => {
            try { ctx.close(); } catch (e) {}
        }, Math.ceil((cursor - ctx.currentTime) * 1000) + 180);
    }

    function toast(type, message) {
        if (!toastEl) return;
        clearTimeout(toastTimer);
        toastEl.className = 'sr-toast show ' + (type || 'ok');
        toastEl.textContent = message;
        toastTimer = setTimeout(() => {
            toastEl.className = 'sr-toast';
        }, 1700);
    }

    function alertError(message, toneType = 'error') {
        const errorMessage = message || 'Terjadi kesalahan.';
        playTone(toneType || 'error');

        if (window.GFID && typeof window.GFID.errorAlert === 'function') {
            const alertPromise = window.GFID.errorAlert(errorMessage);
            if (alertPromise && typeof alertPromise.then === 'function') {
                alertPromise.then(() => {
                    focusScan({ preventScroll: true });
                });
                return;
            }
        }

        toast('error', errorMessage);
        focusScan({ preventScroll: true });
    }

    function focusScan(options = {}) {
        if (!scanInput || !isDraft) return;
        setTimeout(() => {
            try {
                scanInput.focus(options);
                scanInput.select();
            } catch (_) {
                scanInput.focus();
            }

        }, 40);
    }

    function findOrder(code) {
        code = normalize(code);
        return state.orders.find(order => order.code === code);
    }

    function ensureOrder(code, meta = {}) {
        code = normalize(code);
        if (!code) return null;

        let order = findOrder(code);
        if (!order) {
            order = {
                code,
                label: meta.label || 'Manual',
                duplicateCount: Number(meta.duplicateCount || 0),
                items: Array.isArray(meta.items) ? meta.items : []
            };
            state.orders.push(order);
        } else if (Number(meta.duplicateCount || 0) > Number(order.duplicateCount || 0)) {
            order.duplicateCount = Number(meta.duplicateCount);
        }

        state.current = order.code;
        state.expanded = order.code;
        render();
        return order;
    }

    function activeOrder() {
        return state.current ? findOrder(state.current) : null;
    }


    function latestOrder() {
        if (state.current) return activeOrder();
        return state.orders.length ? state.orders[state.orders.length - 1] : null;
    }

    function setMode(mode) {
        state.mode = mode === 'item' ? 'item' : 'order';

        if (scanCard) {
            scanCard.classList.toggle('mode-order', state.mode === 'order');
            scanCard.classList.toggle('mode-item', state.mode === 'item');
        }

        if (modeBadge) {
            modeBadge.textContent = state.mode === 'order' ? 'SCAN ORDER' : 'SCAN ITEM';
        }

        if (currentLabel) {
            const order = activeOrder();
            currentLabel.textContent = order
                ? `Order aktif: ${order.code}`
                : 'Belum ada pesanan aktif';
        }

        if (scanInput) {
            scanInput.placeholder = state.mode === 'order'
                ? 'Scan nomor pesanan'
                : 'Scan item / barcode barang';
        }

        workflowStepper?.querySelectorAll('[data-flow-step]').forEach(step => {
            const key = step.dataset.flowStep;
            step.classList.remove('active', 'done');

            if (state.mode === 'order') {
                if (key === 'order') step.classList.add('active');
            } else {
                if (key === 'order') step.classList.add('done');
                if (key === 'item') step.classList.add('active');
            }
        });

        render();
        focusScan({ preventScroll: true });
    }

    function upsertItem(orderCode, item) {
        const order = ensureOrder(orderCode || 'MANUAL');
        if (!order) return;

        const key = String(item.line_id || item.id || item.code || '').toUpperCase();
        let row = order.items.find(existing => {
            const existingKey = String(existing.line_id || existing.id || existing.code || '').toUpperCase();
            return existingKey === key;
        });

        if (row) {
            row.qty = Number(item.qty || row.qty || 0);
            row.name = item.name || row.name;
            row.code = item.code || row.code;
            row.line_id = item.line_id || row.line_id;
        } else {
            row = {
                line_id: item.line_id || item.id || null,
                item_id: item.item_id || null,
                code: item.code || item.item_code || key,
                name: item.name || item.item_name || '',
                qty: Number(item.qty || item.qty_scanned || 1),
            };
            order.items.push(row);
        }

        state.expanded = order.code;
        render();
    }

    function filteredOrders() {
        const query = normalize(state.search);
        return state.orders.filter(order => {
            if (state.marketplaceTab === 'problem' && Number(order.duplicateCount || 0) <= 0 && (order.items || []).length > 0) return false;
            if (state.marketplaceTab !== 'all' && state.marketplaceTab !== 'problem' && marketplaceGroup(order.code) !== state.marketplaceTab) return false;
            if (!query) return true;
            if (order.code.includes(query)) return true;
            return Object.values(order.items || {}).some(item => {
                return normalize(item.code).includes(query) || normalize(item.name).includes(query);
            });
        });
    }

    function updateMarketplaceTabs(visibleOrders) {
        const counts = { all: state.orders.length, shopee: 0, tiktok: 0, anomaly: 0, problem: 0 };
        state.orders.forEach(order => {
            counts[marketplaceGroup(order.code)] += 1;
            if (Number(order.duplicateCount || 0) > 0 || (order.items || []).length === 0) counts.problem += 1;
        });

        marketplaceTabs.forEach(tab => {
            const key = tab.dataset.orderMarketplaceTab || 'all';
            tab.classList.toggle('active', state.marketplaceTab === key);
            tab.setAttribute('aria-selected', state.marketplaceTab === key ? 'true' : 'false');
            const count = tab.querySelector('[data-order-marketplace-count]');
            if (count) count.textContent = counts[key] ?? 0;
        });

        if (orderToolsCount) orderToolsCount.textContent = `${visibleOrders.length} order`;
    }

    function render() {
        const orders = filteredOrders();
        let totalItems = 0;
        let totalQty = 0;

        state.orders.forEach(order => {
            (order.items || []).forEach(item => {
                totalItems += 1;
                totalQty += Number(item.qty || 0);
            });
        });

        if (sumOrders) {
            sumOrders.textContent = state.orders.filter(order => order.code !== 'BELUM DIKELOMPOKKAN').length;
        }
        if (orderToolsCount) {
            orderToolsCount.textContent = `${orders.length} order`;
        }
        updateMarketplaceTabs(orders);
        if (sumItems) sumItems.textContent = totalItems || initialLines.length || 0;
        if (sumQty) {
            const initialQty = (initialLines || []).reduce((total, line) => total + Number(line.qty || 0), 0);
            sumQty.textContent = totalQty || initialQty || 0;
        }

        if (submitBtn) {
            const hasItems = totalQty > 0 || (initialLines || []).some(line => Number(line.qty || 0) > 0);
            submitBtn.setAttribute('aria-disabled', hasItems ? 'false' : 'true');
            submitBtn.title = hasItems ? 'Cek shipment sebelum submit' : 'Scan item terlebih dahulu';
        }

        if (!ordersWrap) return;

        if (!orders.length) {
            ordersWrap.innerHTML = '<div class="sr-empty">Belum ada order pada tab ini.</div>';
            return;
        }

        ordersWrap.innerHTML = orders.slice().reverse().map(order => {
            const items = order.items || [];
            const qty = items.reduce((total, item) => total + Number(item.qty || 0), 0);
            const isActive = state.current === order.code;
            const expanded = state.expanded === order.code || isActive;

            const rows = items.length
                ? items.map((item, index) => {
                    const deleteButton = canDelete && item.line_id
                        ? `<button type="button" class="sr-delete-btn sr-item-delete" data-delete-line="${esc(item.line_id)}" data-order-code="${esc(order.code)}" title="Hapus item">×</button>`
                        : '';
                    const qtyControl = canEditQty && item.line_id
                        ? `<div class="sr-qty-cell">
                                <button type="button" class="sr-item-qty sr-qty-trigger" data-edit-qty-trigger data-line-id="${esc(item.line_id)}" aria-label="Edit qty ${esc(item.code || 'item')}" title="Klik untuk edit qty">
                                    <strong class="sr-item-qty-value">${Number(item.qty || 0)}</strong>
                                </button>
                                <form class="sr-qty-edit" data-edit-qty data-line-id="${esc(item.line_id)}" data-order-code="${esc(order.code)}" hidden>
                                    <span class="sr-qty-label">Qty</span>
                                    <input type="number" class="sr-qty-input" min="0" value="${Math.max(0, Number(item.qty || 0))}" aria-label="Qty ${esc(item.code || 'item')}">
                                    <button type="submit" class="sr-qty-save">Simpan</button>
                                </form>
                           </div>`
                        : `<div class="sr-item-qty"><strong class="sr-item-qty-value">${Number(item.qty || 0)}</strong></div>`;

                    const isScanHighlighted = state.highlightedItemKey === itemHighlightKey(order.code, item);

                    return `
                        <div class="sr-item-row ${isScanHighlighted ? 'is-scan-highlight' : ''}">
                            <span class="sr-item-index" aria-label="Item ${index + 1}">${index + 1}</span>
                            <div>
                                <div class="sr-item-code">${esc(item.code || '-')}</div>
                                <div class="sr-item-name">${esc(item.name || '')}</div>
                            </div>
                            ${qtyControl}
                            ${deleteButton}
                        </div>
                    `;
                }).join('')
                : '<div class="sr-empty">Belum ada item untuk order ini.</div>';

            const deleteOrderButton = canDelete
                ? `<button type="button" class="sr-delete-btn sr-order-delete" data-delete-order="${esc(order.code)}" title="Hapus order dan itemnya">×</button>`
                : '';
            const duplicateBadge = Number(order.duplicateCount || 0) > 0
                ? `<span class="sr-duplicate-badge" title="Order ini discan lebih dari sekali">Duplikat</span>`
                : '';
            const missingItemBadge = items.length === 0
                ? `<span class="sr-missing-item-badge" title="Order belum memiliki item">Belum ada item</span>`
                : '';

            return `
                <div class="sr-order ${isActive ? 'active' : ''} ${orderTheme(order.code)}" data-order-code="${esc(order.code)}">
                    <div class="sr-order-head" data-toggle-order="${esc(order.code)}">
                        <div>
                            <div class="sr-order-code-line">
                                <div class="sr-order-code">${esc(order.code)}</div>
                                ${duplicateBadge}
                                ${missingItemBadge}
                            </div>
                            <div class="sr-order-info">${esc(order.label || ('{{ $shipment->shipment_type ?? 'manual' }}' === 'marketplace' ? 'Marketplace' : 'Manual'))}</div>
                        </div>
                        <div class="sr-order-qty">${qty}</div>
                        ${deleteOrderButton}
                        <div class="sr-order-chevron">${expanded ? '▲' : '▼'}</div>
                    </div>
                    ${expanded ? `<div class="sr-order-items">${items.length ? '<div class="sr-items-head"><span>No</span><span>Kode Item</span><span>Qty</span><span>' + (canDelete ? 'Aksi' : '') + '</span></div>' : ''}${rows}</div>` : ''}
                </div>
            `;
        }).join('');

        ordersWrap.querySelectorAll('[data-toggle-order]').forEach(el => {
            el.addEventListener('click', function () {
                const code = this.dataset.toggleOrder;
                state.expanded = state.expanded === code ? null : code;
                render();
            });
        });

        ordersWrap.querySelectorAll('[data-delete-order]').forEach(el => {
            el.addEventListener('click', function (event) {
                event.stopPropagation();

                const orderCode = this.dataset.deleteOrder;
                if (!orderCode || !confirm(`Hapus order ${orderCode} beserta semua itemnya?`)) return;

                this.disabled = true;
                deleteOrderScan(orderCode)
                    .then(() => {
                        state.orders = state.orders.filter(order => order.code !== orderCode);
                        for (let i = lastScanStack.length - 1; i >= 0; i--) {
                            if (lastScanStack[i].orderCode === orderCode) lastScanStack.splice(i, 1);
                        }

                        if (state.current === orderCode) {
                            state.current = null;
                            state.expanded = null;
                            setMode('order');
                        } else {
                            render();
                        }

                        toast('ok', `Order ${orderCode} dan itemnya dihapus`);
                    })
                    .catch(error => {
                        this.disabled = false;
                        alertError(error.message || 'Gagal menghapus order.', 'errorNetwork');
                    });
            });
        });

        ordersWrap.querySelectorAll('[data-delete-line]').forEach(el => {
            el.addEventListener('click', function (event) {
                event.stopPropagation();

                const lineId = this.dataset.deleteLine;
                const orderCode = this.dataset.orderCode;
                if (!lineId || !confirm('Hapus item ini dari shipment?')) return;

                this.disabled = true;
                deleteLine(lineId)
                    .then(() => {
                        for (let i = lastScanStack.length - 1; i >= 0; i--) {
                            if (String(lastScanStack[i].lineId || '') === String(lineId)) lastScanStack.splice(i, 1);
                        }
                        removeItemLocal(orderCode, lineId);
                        toast('ok', 'Item berhasil dihapus');
                    })
                    .catch(error => {
                        this.disabled = false;
                        alertError(error.message || 'Gagal menghapus item.', 'errorNetwork');
                    });
            });
        });

        ordersWrap.querySelectorAll('[data-edit-qty-trigger]').forEach(trigger => {
            trigger.addEventListener('click', function (event) {
                event.stopPropagation();

                const cell = this.closest('.sr-qty-cell');
                const form = cell?.querySelector('[data-edit-qty]');
                const input = form?.querySelector('.sr-qty-input');
                if (!form || !input) return;

                this.hidden = true;
                form.hidden = false;
                input.focus();
                input.select();
            });
        });

        ordersWrap.querySelectorAll('[data-edit-qty]').forEach(form => {
            form.addEventListener('click', event => event.stopPropagation());
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                event.stopPropagation();

                const lineId = this.dataset.lineId;
                const orderCode = this.dataset.orderCode;
                const input = this.querySelector('.sr-qty-input');
                const qty = Number(input?.value);

                if (!lineId || !Number.isInteger(qty) || qty < 0) {
                    toast('err', 'Qty harus berupa angka 0 atau lebih.');
                    return;
                }

                const button = this.querySelector('.sr-qty-save');
                if (button) button.disabled = true;

                updateLineQty(lineId, qty)
                    .then(payload => {
                        if (payload.deleted) {
                            removeItemLocal(orderCode, lineId);
                        } else {
                            updateItemLocalQty(orderCode, lineId, qty);
                        }
                        toast('ok', payload.message || 'Qty berhasil diperbarui.');
                        render();
                    })
                    .catch(error => {
                        if (button) button.disabled = false;
                        toast('err', error.message || 'Gagal memperbarui qty.');
                    });
            });

            form.querySelector('.sr-qty-input')?.addEventListener('keydown', function (event) {
                if (event.key !== 'Escape') return;

                event.preventDefault();
                form.hidden = true;
                form.closest('.sr-qty-cell')?.querySelector('[data-edit-qty-trigger]')?.removeAttribute('hidden');
            });
        });
    }



    function decrementItemLocal(orderCode, lineId, nextQty) {
        const order = findOrder(orderCode);
        if (!order || !Array.isArray(order.items)) return;

        const targetId = String(lineId || '');
        const item = order.items.find(row => String(row.line_id || row.id || '') === targetId);

        if (!item) return;

        item.qty = Math.max(0, Number(nextQty || 0));

        if (item.qty <= 0) {
            order.items = order.items.filter(row => String(row.line_id || row.id || '') !== targetId);
        }

        if (!order.items.length) {
            state.expanded = order.code;
        }

        render();
    }



    function findLocalOrderItem(orderCode, lineId, itemCode = null) {
        const order = findOrder(orderCode);

        if (!order || !Array.isArray(order.items)) {
            return { order: order || null, item: null };
        }

        const targetId = String(lineId || '');
        const targetCode = String(itemCode || '').toUpperCase();

        let item = null;

        if (targetId) {
            item = order.items.find(row => String(row.line_id || row.id || '') === targetId) || null;
        }

        if (!item && targetCode) {
            item = order.items.find(row => String(row.code || row.item_code || '').toUpperCase() === targetCode) || null;
        }

        return { order, item };
    }

    function readItemQty(item) {
        if (!item) return 0;

        const rawQty = item.qty ?? item.qty_scanned ?? item.quantity ?? 0;
        return Math.max(0, Number(rawQty || 0));
    }

    function writeItemQty(item, qty) {
        if (!item) return;

        const safeQty = Math.max(0, Number(qty || 0));

        item.qty = safeQty;
        item.qty_scanned = safeQty;
        item.quantity = safeQty;
    }

    function setItemLocalQty(orderCode, lineId, nextQty, itemCode = null) {
        const found = findLocalOrderItem(orderCode, lineId, itemCode);
        const order = found.order;
        const item = found.item;

        if (!order || !Array.isArray(order.items)) return;

        const safeQty = Math.max(0, Number(nextQty || 0));

        if (item && safeQty > 0) {
            writeItemQty(item, safeQty);
        }

        if (safeQty <= 0) {
            const targetId = String(lineId || '');
            const targetCode = String(itemCode || '').toUpperCase();

            order.items = order.items.filter(row => {
                const sameId = targetId && String(row.line_id || row.id || '') === targetId;
                const sameCode = targetCode && String(row.code || row.item_code || '').toUpperCase() === targetCode;

                return !(sameId || sameCode);
            });
        }

        // Order tetap aktif walaupun item kosong.
        state.current = order.code;
        state.expanded = order.code;

        render();
    }

    function updateItemLocalQty(orderCode, lineId, nextQty, itemCode = null) {
        setItemLocalQty(orderCode, lineId, nextQty, itemCode);
    }

    function removeItemLocal(orderCode, lineId) {
        const order = findOrder(orderCode);
        if (!order || !Array.isArray(order.items)) return;

        const targetId = String(lineId || '');

        order.items = order.items.filter(item => String(item.line_id || item.id || '') !== targetId);

        // Penting:
        // UNDO hanya menghapus item terakhir, bukan menghapus pesanan/order.
        // Jadi walaupun items kosong, order tetap dipertahankan agar operator bisa scan item lagi.
        state.current = order.code;
        state.expanded = order.code;

        render();
    }


    function updateLineQty(lineId, qty) {
        if (!lineId) return Promise.resolve(false);

        const url = updateQtyUrlTemplate.replace('__LINE_ID__', lineId);

        return fetch(url, {
            method: 'PATCH',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({
                qty: Math.max(0, Number(qty || 0))
            })
        }).then(async response => {
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(payload.message || 'Gagal update qty');
            return payload;
        });
    }

    function deleteLine(lineId) {
        if (!lineId) return Promise.resolve(false);

        const url = deleteLineUrlTemplate.replace('__LINE_ID__', lineId);

        return fetch(url, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({})
        }).then(async response => {
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(payload.message || 'Gagal hapus item');
            return payload;
        });
    }

    function deleteOrderScan(orderNo) {
        return fetch(deleteOrderUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ order_no: orderNo })
        }).then(async response => {
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(payload.message || 'Gagal hapus pencatatan order');
            return payload;
        });
    }




    function isConfirmModalOpen() {
        return !!document.querySelector('.swal2-container.swal2-shown, .swal2-container');
    }

    function handleConfirmModalScanCommand(code) {
        if (!isConfirmModalOpen()) return false;

        if (isNextOrderCommand(code)) {
            const confirmButton = document.querySelector('.swal2-confirm');

            if (confirmButton) {
                playTone('next');
                confirmButton.click();
                return true;
            }
        }

        if (isResetOrderCommand(code)) {
            const cancelButton = document.querySelector('.swal2-cancel, .swal2-close, .swal2-deny');

            if (cancelButton) {
                playTone('undo');
                cancelButton.click();
                return true;
            }

            if (window.Swal && typeof Swal.close === 'function') {
                playTone('undo');
                Swal.close();
                return true;
            }
        }

        return true;
    }

    function nextOrderCommand() {
        // NEXT = selesai order aktif, lanjut scan nomor order berikutnya.
        state.current = null;
        state.expanded = null;
        state.search = '';

        if (orderSearch) {
            orderSearch.value = '';
        }

        playTone('next');
        toast('ok', 'Scan nomor order berikutnya');
        setMode('order');
        focusScan();
    }

    function markDuplicateOrder(code, order) {
        fetch(recordOrderUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ order_no: code })
        })
            .then(response => response.json().catch(() => ({})))
            .then(payload => {
                const duplicateCount = Number(payload.duplicate_count || 0);
                if (duplicateCount > Number(order.duplicateCount || 0)) {
                    order.duplicateCount = duplicateCount;
                    render();
                }
            })
            .catch(() => {
                // Badge lokal tetap ditampilkan jika pencatatan ulang gagal.
            });
    }

    function startOrder(code) {
        code = normalize(code);
        if (!code) return;

        const existingOrder = findOrder(code);
        if (existingOrder) {
            existingOrder.duplicateCount = Number(existingOrder.duplicateCount || 0) + 1;
            markDuplicateOrder(code, existingOrder);
            selectMarketplaceForOrder(existingOrder.code);
            render();

            const continueExistingOrder = () => {
                state.current = existingOrder.code;
                state.expanded = existingOrder.code;
                toast('ok', `Kembali ke order ${existingOrder.code}`);
                setMode('item');
            };

            playTone('orderRepeat');

            if (window.Swal) {
                Swal.fire({
                    icon: 'info',
                    title: 'Order sudah discan',
                    text: `${existingOrder.code} sudah ada. Lanjut scan item untuk order ini?`,
                    showCancelButton: true,
                    confirmButtonText: 'Lanjut scan item',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#2563eb',
                    cancelButtonColor: '#64748b',
                }).then(result => {
                    if (result.isConfirmed) continueExistingOrder();
                    else focusScan({ preventScroll: true });
                });
                return;
            }

            if (window.confirm(`Order ${existingOrder.code} sudah ada. Lanjut scan item untuk order ini?`)) {
                continueExistingOrder();
            } else {
                focusScan({ preventScroll: true });
            }
            return;
        }

        fetch(recordOrderUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ order_no: code })
        }).then(async response => {
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(payload.message || 'Gagal mencatat nomor order');
            return payload;
        }).then(payload => {
            const orderCode = normalize(payload.order?.code || payload.order?.order_no || code);
            if (!orderCode) throw new Error('Nomor order tidak tersedia. Scan ulang nomor order tersebut.');
            ensureOrder(orderCode, {
                label: payload.order?.label || 'Pencatatan order',
                duplicateCount: payload.duplicate ? Math.max(1, Number(payload.duplicate_count || 0)) : Number(payload.duplicate_count || 0),
            });
            selectMarketplaceForOrder(orderCode);
            playTone(payload.created ? 'order' : 'orderRepeat');
            toast('ok', payload.message || `Order ${orderCode} tercatat`);
            setMode('item');
        }).catch(error => {
            playTone('errorNetwork');
            alertError(error.message || 'Gagal mencatat nomor order.', 'errorNetwork');
        });
    }

    function scanItem(code) {
        code = normalize(code);
        if (!code) return;

        if (isNextOrderCommand(code)) {
            nextOrderCommand();
            return;
        }

        if (isResetOrderCommand(code)) {
            resetActiveOrderCommand();
            return;
        }

        if (isUndoCommand(code)) {
            undoLastItemCommand();
            return;
        }

        const order = activeOrder();

        if (!order) {
            playTone('errorNoOrder');
            alertError('Scan nomor order dulu sebelum scan item.', 'errorNoOrder');
            setMode('order');
            return;
        }

        state.expanded = order.code;
        state.search = '';
        if (orderSearch) orderSearch.value = '';

        fetch(scanUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({
                scan_code: code,
                qty: 1,
                order_no: order.code === 'MANUAL' ? '' : order.code
            })
        })
            .then(async response => {
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(payload.message || 'Gagal scan item');
                return payload;
            })
            .then(payload => {
                const line = payload.line || payload.data?.line || null;

                if (line) {
                    const scannedItemCode = line.item_code || line.code || code;
                    const beforeScanFound = findLocalOrderItem(order.code, line.id, line.item_code || line.code || code);
                    const beforeScanQty = readItemQty(beforeScanFound.item);

                    upsertItem(order.code, {
                        line_id: line.id,
                        item_id: line.item_id,
                        code: scannedItemCode,
                        name: line.item_name || line.name || '',
                        qty: beforeScanQty + 1,
                    });

                    const afterScanItem = findLocalOrderItem(order.code, line.id, line.item_code || line.code || code).item;
                    lastScanStack.push({
                        orderCode: order.code,
                        lineId: line.id,
                        code: scannedItemCode,
                        qtyAfter: readItemQty(afterScanItem),
                        backendQtyAfter: Number(line.qty_scanned || line.qty || 1),
                    });

                    highlightScannedItem(order.code, line.id, scannedItemCode);

                    playTone('item');
                    toast('ok', payload.message || `+1 ${line.item_code || line.code || code}`);
                } else {
                    upsertItem(order.code, {
                        code,
                        name: '',
                        qty: 1,
                    });

                    highlightScannedItem(order.code, null, code);

                    playTone('item');
                    toast('ok', payload.message || `Berhasil scan ${code}`);
                }

                setMode('item');
            })
            .catch(error => {
                const toneType = (error.message || '').includes('tidak ditemukan') ? 'errorItem' : 'errorNetwork';
                playTone(toneType);
                alertError(error.message || 'Gagal scan item.', toneType);
            });
    }

    function resetActiveOrderCommand() {
        const order = activeOrder() || latestOrder();

        if (!order) {
            alertError('Belum ada order aktif untuk di-reset.');
            setMode('order');
            return;
        }

        const runReset = () => {
            const lineIds = (order.items || [])
                .map(item => item.line_id || item.id)
                .filter(Boolean);

            if (!lineIds.length) {
                deleteOrderScan(order.code)
                    .then(() => {
                        state.orders = state.orders.filter(row => row.code !== order.code);
                        state.current = null;
                        state.expanded = null;
                        render();
                        playTone('reset');
                        toast('ok', `Order ${order.code} di-reset`);
                        setMode('order');
                    })
                    .catch(error => alertError(error.message || 'Gagal reset order.', 'errorNetwork'));
                return;
            }

            Promise.all(lineIds.map(lineId => deleteLine(lineId).catch(error => ({ error }))))
                .then(results => {
                    const failed = results.filter(row => row && row.error);
                    if (failed.length) {
                        playTone('errorNetwork');
                        alertError('Sebagian item gagal di-reset. Cek ulang daftar item.', 'errorNetwork');
                        return;
                    }

                    deleteOrderScan(order.code)
                        .then(() => {
                            state.orders = state.orders.filter(row => row.code !== order.code);
                            state.current = null;
                            state.expanded = null;

                            for (let i = lastScanStack.length - 1; i >= 0; i--) {
                                if (lastScanStack[i].orderCode === order.code) {
                                    lastScanStack.splice(i, 1);
                                }
                            }

                            render();
                            playTone('reset');
                            toast('ok', `Order ${order.code} di-reset`);
                            setMode('order');
                        })
                        .catch(error => alertError(error.message || 'Gagal reset order.', 'errorNetwork'));
                });
        };

        if (window.Swal) {
            Swal.fire({
                icon: 'warning',
                title: 'Reset order?',
                text: `Item pada order ${order.code} akan dihapus dari shipment ini.`,
                showCancelButton: true,
                confirmButtonText: 'NEXT / Lanjut',
                cancelButtonText: 'RESET / Batal',
                confirmButtonColor: '#991b1b',
                cancelButtonColor: '#64748b',
            }).then(result => {
                if (result.isConfirmed) runReset();
            });
            return;
        }

        if (confirm(`Reset order ${order.code}?`)) runReset();
    }

    function undoLastItemCommand() {
        const last = lastScanStack.pop();

        if (!last || !last.lineId) {
            playTone('errorGuard');
            alertError('Belum ada item terakhir untuk di-undo.', 'errorGuard');
            return;
        }

        const found = findLocalOrderItem(last.orderCode, last.lineId, last.code);
        const order = found.order || findOrder(last.orderCode);
        const item = found.item;

        const currentUiQty = readItemQty(item) || Math.max(1, Number(last.qtyAfter || 1));
        const nextUiQty = Math.max(0, currentUiQty - 1);

        const backendQtyAfter = Math.max(1, Number(last.backendQtyAfter || last.qtyAfter || currentUiQty || 1));
        const nextBackendQty = Math.max(0, backendQtyAfter - 1);

        const request = nextBackendQty > 0
            ? updateLineQty(last.lineId, nextBackendQty)
            : deleteLine(last.lineId);

        request
            .then(() => {
                setItemLocalQty(last.orderCode, last.lineId, nextUiQty, last.code);

                playTone('undo');

                if (nextUiQty > 0) {
                    toast('ok', `Scan terakhir dibatalkan. Qty sekarang ${nextUiQty}`);
                } else {
                    toast('ok', 'Scan terakhir dibatalkan');
                }

                if (order) {
                    state.current = order.code;
                    state.expanded = order.code;
                    setMode('item');
                } else {
                    ensureOrder(last.orderCode, { label: 'Manual' });
                    state.current = last.orderCode;
                    state.expanded = last.orderCode;
                    setMode('item');
                }

                render();
            })
            .catch(error => {
                lastScanStack.push(last);
                playTone('errorNetwork');
                alertError(error.message || 'Gagal undo item terakhir.', 'errorNetwork');
            });
    }

    initialOrders.forEach(order => {
        ensureOrder(order.code, {
            label: order.label || 'Pencatatan order',
            items: order.items || [],
        });
    });

    const ungroupedLines = initialLines.filter(line => !line.order_scan_id);

    if (ungroupedLines.length) {
        ensureOrder('BELUM DIKELOMPOKKAN', { label: 'Item existing' });
    }

    ungroupedLines.forEach(line => {
        upsertItem('BELUM DIKELOMPOKKAN', {
            line_id: line.id,
            item_id: line.item_id,
            code: line.code,
            name: line.name,
            qty: line.qty,
        });
    });

    state.current = null;
    const lastScannedOrder = state.orders
        .filter(order => order.code !== 'BELUM DIKELOMPOKKAN')
        .at(-1) || null;
    if (lastScannedOrder) {
        state.marketplaceTab = marketplaceGroup(lastScannedOrder.code);
    }
    state.expanded = lastScannedOrder?.code || null;
    state.search = '';
    render();

    orderSearch?.addEventListener('input', function () {
        state.search = this.value;
        render();
    });

    marketplaceTabs.forEach(tab => {
        tab.addEventListener('click', function () {
            state.marketplaceTab = this.dataset.orderMarketplaceTab || 'all';
            state.expanded = null;
            render();
        });
    });

    if (isDraft) {
        setMode('order');

        scanInput?.addEventListener('input', function () {
            this.value = this.value.toUpperCase();
        });

        scanInput?.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') return;

            event.preventDefault();

            const code = normalize(this.value);
            this.value = '';

            if (!code) return;

            if (handleConfirmModalScanCommand(code)) {
                return;
            }

            if (isNextOrderCommand(code)) {
                nextOrderCommand();
                return;
            }

            if (isResetOrderCommand(code)) {
                resetActiveOrderCommand();
                return;
            }

            if (isUndoCommand(code)) {
                undoLastItemCommand();
                return;
            }

            state.mode === 'order' ? startOrder(code) : scanItem(code);
        });


        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') return;
            if (!isConfirmModalOpen()) return;

            const target = event.target;
            const rawValue = target && 'value' in target ? target.value : '';
            const code = normalize(rawValue);

            if (!code) return;

            if (handleConfirmModalScanCommand(code)) {
                event.preventDefault();

                if (target && 'value' in target) {
                    target.value = '';
                }
            }
        }, true);

        nextOrderBtn?.addEventListener('click', function () {
            nextOrderCommand();
        });

        submitBtn?.addEventListener('click', function (event) {
            if (this.getAttribute('aria-disabled') === 'true') {
                event.preventDefault();
                alertError('Scan minimal satu item sebelum cek shipment.', 'errorGuard');
                return;
            }

            playTone('next');
        });

        window.addEventListener('load', function () {
            focusScan();
        });

        document.addEventListener('click', function (event) {
            if (event.target.closest('a,button,input,textarea,select')) return;
            focusScan({ preventScroll: true });
        });
    }
})();
</script>
@endpush
