{{-- resources/views/sales/shipment_returns/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Scan Retur ' . $shipmentReturn->code)

@push('head')
<style>
    :root {
        --sr-accent: #334155;
        --sr-accent-2: #1f2937;
        --sr-accent-bg: rgba(148, 163, 184, .08);
        --sr-border: rgba(148, 163, 184, .22);
        --sr-text: #111827;
        --sr-muted: #64748b;
        --sr-soft: #f8fafc;
        --sr-mobile-nav-offset: calc(78px + env(safe-area-inset-bottom, 0px));
    }

    .sr-scan-page {
        max-width: 1100px;
        margin: 0 auto;
        padding: 0 .75rem 6rem;
        color: var(--sr-text);
        background: #f8fafc;
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
        background: rgba(248, 250, 252, .92);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }

    .sr-top-main {
        min-width: 0;
    }

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
        letter-spacing: .05em;
        text-transform: uppercase;
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
        border: 1px solid rgba(148, 163, 184, .12);
        border-radius: 8px;
        background: #fff;
        overflow: hidden;
        box-shadow: none;
    }

    .sr-panel-body {
        padding: .72rem .85rem;
    }

    .sr-meta-panel .sr-panel-body {
        padding: .12rem .15rem;
    }

    .sr-meta-panel {
        background: transparent;
        border-color: transparent;
    }

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
        display: inline;
        font-size: .62rem;
        font-weight: 500;
        letter-spacing: 0;
        text-transform: uppercase;
    }

    .sr-meta-label::after {
        content: ':';
    }

    .sr-meta-value {
        display: inline;
        margin-top: 0;
        color: #64748b;
        font-size: .76rem;
        font-weight: 500;
        overflow-wrap: anywhere;
    }

    .sr-meta-store {
        grid-column: auto;
    }

    .sr-scan-card {
        display: grid;
        gap: .55rem;
        border: 0;
        border-radius: 6px;
        background: #fff;
        padding: .35rem;
    }

    .sr-dev-command {
        display: grid;
        gap: .45rem;
        grid-column: 1 / -1;
        padding: .48rem;
        border: 1px dashed rgba(148, 163, 184, .28);
        border-radius: 6px;
        background: rgba(248, 250, 252, .72);
    }

    .sr-dev-command[hidden] {
        display: none;
    }

    .sr-dev-command-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto auto auto;
        gap: .45rem;
    }

    .sr-dev-input {
        min-height: 36px;
        border: 1px solid rgba(148, 163, 184, .28) !important;
        border-radius: 8px !important;
        font-size: .78rem;
        font-weight: 500;
        padding: .38rem .75rem;
        box-shadow: none !important;
        text-transform: uppercase;
    }

    .sr-dev-command .sr-btn {
        min-height: 36px;
        padding-inline: .78rem;
        font-size: .68rem;
    }

    .sr-dev-hint {
        color: #94a3b8;
        font-size: .66rem;
        font-weight: 500;
    }

    .sr-mode-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .35rem;
        flex-wrap: wrap;
    }

    .sr-mode {
        display: inline-flex;
        border-radius: 6px;
        padding: .2rem .75rem;
        border: 1px solid var(--sr-accent);
        background: var(--sr-accent-bg);
        color: var(--sr-accent);
        font-size: .77rem;
        font-weight: 550;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .sr-current {
        color: var(--sr-muted);
        font-size: .77rem;
        font-weight: 500;
    }

    .sr-scan-input {
        width: 100%;
        min-height: 64px;
        border: 1px solid rgba(148, 163, 184, .38) !important;
        border-radius: 8px !important;
        background: #fff;
        color: var(--sr-text);
        font-size: 1.24rem;
        font-weight: 750;
        letter-spacing: .02em;
        text-transform: uppercase;
        padding: .78rem .9rem;
        box-shadow: none !important;
    }

    .sr-scan-input::placeholder {
        color: #94a3b8;
        font-size: .95rem;
        font-weight: 500;
        letter-spacing: 0;
        text-transform: none;
    }

    .sr-summary {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .35rem;
    }

    .sr-stat {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: .35rem;
        min-width: 0;
        border: 1px solid rgba(148, 163, 184, .12);
        border-radius: 6px;
        padding: .32rem .48rem;
        background: rgba(255, 255, 255, .68);
        box-shadow: none;
    }

    .sr-stat-label {
        color: #9ca3af;
        font-size: .62rem;
        font-weight: 500;
        letter-spacing: 0;
        text-transform: uppercase;
    }

    .sr-stat-value {
        margin-top: 0;
        color: var(--sr-text);
        font-size: .84rem;
        font-weight: 650;
        font-variant-numeric: tabular-nums;
    }

    .sr-orders {
        display: grid;
        gap: .5rem;
    }

    .sr-order-tools {
        display: none;
        padding: .45rem .55rem;
        border-bottom: 1px solid rgba(148, 163, 184, .12);
        background: #f8fafc;
    }

    .sr-order-panel-body {
        min-height: 0;
    }

    .sr-order-section {
        border-color: rgba(148, 163, 184, .16);
        background: #fff;
    }

    .sr-order-search {
        width: 100%;
        min-height: 38px;
        border: 1px solid rgba(148, 163, 184, .28) !important;
        border-radius: 8px !important;
        background: transparent;
        color: var(--sr-text);
        font-size: .84rem;
        font-weight: 500;
        padding: .42rem .85rem;
        box-shadow: none !important;
        text-transform: uppercase;
    }

    .sr-order-search::placeholder {
        color: #94a3b8;
        text-transform: none;
    }

    .sr-order {
        position: relative;
        border: 1px solid rgba(148, 163, 184, .12);
        border-radius: 6px;
        background: #fff;
        overflow: hidden;
    }

    .sr-order-active {
        border-color: rgba(51, 65, 85, .32);
        box-shadow: none;
    }

    .sr-order-active::before {
        content: '';
        position: absolute;
        inset: 0 auto 0 0;
        width: 3px;
        background: var(--sr-accent);
        z-index: 2;
    }

    .sr-order-empty {
        opacity: .78;
    }

    .sr-order-empty .sr-order-head {
        background: #fff;
    }

    .sr-order-active .sr-order-head {
        background: #f8fafc;
    }

    .sr-order-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        padding: .52rem .7rem;
        border-bottom: 1px solid rgba(148, 163, 184, .1);
        background: #fff;
    }

    .sr-order-toggle {
        width: 100%;
        border: 0;
        background: transparent;
        color: inherit;
        text-align: left;
        cursor: pointer;
    }

    .sr-order-head-right {
        display: inline-flex;
        align-items: center;
        gap: .38rem;
        flex-shrink: 0;
    }

    .sr-order-count {
        color: var(--sr-muted);
        font-size: .72rem;
        font-weight: 500;
        white-space: nowrap;
    }

    .sr-order-empty .sr-order-count,
    .sr-order-empty .sr-order-qty {
        color: #94a3b8;
        background: transparent;
        border-color: transparent;
    }

    .sr-order-chevron {
        color: #94a3b8;
        font-size: .8rem;
        line-height: 1;
    }

    .sr-order-no {
        color: #9ca3af;
        font-size: .64rem;
        font-weight: 500;
        letter-spacing: .07em;
        text-transform: uppercase;
    }

    .sr-order-code,
    .sr-item-code {
        color: var(--sr-text);
        font-weight: 700;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    }

    .sr-order-code {
        font-size: 1rem;
    }

    .sr-item-code {
        font-size: .86rem;
    }

    .sr-order-info,
    .sr-item-name {
        color: var(--sr-muted);
        font-size: .74rem;
        font-weight: 500;
        line-height: 1.2;
    }

    .sr-order-qty,
    .sr-item-qty {
        min-width: 42px;
        text-align: center;
        border-radius: 6px;
        padding: .16rem .5rem;
        font-weight: 700;
    }

    .sr-order-qty {
        background: var(--sr-accent);
        color: #fff;
    }

    .sr-order-active .sr-order-qty {
        background: var(--sr-accent);
        color: #fff;
    }

    .sr-item-list {
        padding: 0;
        overflow-x: auto;
    }

    .sr-order-collapsed .sr-item-list {
        display: none;
    }

    .sr-return-table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }

    .sr-return-table thead th {
        padding: .35rem .55rem;
        border-bottom: 1px solid rgba(148, 163, 184, .16);
        background: #fff;
        color: #6b7280;
        font-size: .7rem;
        font-weight: 550;
        letter-spacing: .05em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .sr-return-table tbody td {
        vertical-align: middle;
        border-top: 1px solid rgba(148, 163, 184, .12);
        padding: .38rem .55rem;
    }

    .sr-return-table tbody tr:first-child td {
        border-top: 0;
    }

    .sr-return-table tbody tr:nth-child(even) {
        background: transparent;
    }

    .order-cell {
        width: 42px;
        color: #9ca3af !important;
        font-size: .8rem;
        font-weight: 500;
        text-align: right;
        font-variant-numeric: tabular-nums;
    }

    .item-code {
        color: var(--sr-text);
        font-size: .9rem;
        font-weight: 700;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        letter-spacing: 0;
    }

    .item-name {
        color: var(--sr-muted);
        font-size: .78rem;
        font-weight: 500;
        line-height: 1.2;
    }

    @keyframes rowFlash {
        0% { background: rgba(241, 245, 249, .95); }
        100% { background: transparent; }
    }

    .sr-return-table tr.row-flash td {
        animation: rowFlash .75s ease-out forwards;
    }

    .sr-return-table tr.last-scanned-row td {
        background: rgba(241, 245, 249, .9) !important;
    }

    .sr-return-table tr.last-scanned-row td:first-child {
        border-left: 2px solid #64748b;
    }

    .sr-qty-input {
        width: 64px;
        min-height: 32px;
        border: 1px solid rgba(148, 163, 184, .38);
        border-radius: 6px;
        color: var(--sr-text);
        font-size: .84rem;
        font-weight: 600;
        text-align: center;
        box-shadow: none !important;
        background: #fff;
        transition: border-color .12s, background .12s, box-shadow .12s;
    }

    .qty-edit-input {
        width: 64px;
        text-align: right;
        padding-right: .5rem;
        font-size: .9rem;
    }

    .sr-qty-input:focus {
        border-color: var(--sr-accent);
        background: #fff;
    }

    .sr-mini-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 6px;
        border: 1px solid rgba(148, 163, 184, .34);
        background: transparent;
        color: #64748b;
        font-size: .78rem;
        font-weight: 550;
        cursor: pointer;
        line-height: 1;
        transition: background .12s, border-color .12s, color .12s;
    }

    .btn-del {
        width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 5px;
        border: 1px solid rgba(148, 163, 184, .35);
        background: transparent;
        color: #64748b;
        font-size: .78rem;
        font-weight: 600;
        transition: background .1s, border-color .1s, color .1s;
        padding: 0;
        cursor: pointer;
    }

    .btn-del:hover {
        background: rgba(148, 163, 184, .08);
        color: #991b1b;
        border-color: rgba(185, 28, 28, .35);
    }

    .sr-mini-btn:hover {
        background: rgba(226, 232, 240, .7);
        color: #374151;
    }

    .sr-mini-btn-danger {
        border-color: rgba(148, 163, 184, .32);
        color: #9ca3af;
        background: transparent;
    }

    .sr-mini-btn-danger:hover {
        border-color: rgba(239, 68, 68, .28);
        color: #b91c1c;
        background: rgba(239, 68, 68, .06);
    }

    .sr-empty {
        padding: 1.4rem .9rem;
        text-align: center;
        color: #94a3b8;
        font-size: .85rem;
        font-weight: 500;
    }

    .sr-actions {
        position: sticky;
        bottom: .75rem;
        z-index: 40;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: .5rem;
        align-items: stretch;
        padding: .55rem;
        border: 0;
        border-radius: 0;
        background: transparent;
        box-shadow: none;
        backdrop-filter: none;
        -webkit-backdrop-filter: none;
    }

    .sr-actions form {
        margin: 0;
        min-width: 0;
    }

    .sr-actions form .sr-btn {
        width: 100%;
    }

    .sr-toast {
        position: fixed;
        left: 50%;
        bottom: 1rem;
        z-index: 1080;
        transform: translateX(-50%);
        display: none;
        max-width: calc(100vw - 2rem);
        border-radius: 999px;
        padding: .48rem .9rem;
        color: #fff;
        font-size: .82rem;
        font-weight: 650;
        box-shadow: 0 14px 30px rgba(15, 23, 42, .22);
    }

    .sr-toast-ok { background: #15803d; }
    .sr-toast-err { background: #b91c1c; }

    body[data-theme="dark"] .sr-scan-page { background: #020617; color: #e5e7eb; }
    body[data-theme="dark"] .sr-topbar {
        background: rgba(2, 6, 23, .96);
        border-bottom-color: rgba(30, 64, 175, .45);
    }
    body[data-theme="dark"] .sr-title,
    body[data-theme="dark"] .sr-meta-value,
    body[data-theme="dark"] .sr-stat-value,
    body[data-theme="dark"] .sr-order-code,
    body[data-theme="dark"] .sr-item-code {
        color: #e5e7eb;
    }
    body[data-theme="dark"] .sr-panel,
    body[data-theme="dark"] .sr-meta-item,
    body[data-theme="dark"] .sr-stat,
    body[data-theme="dark"] .sr-scan-card,
    body[data-theme="dark"] .sr-order,
    body[data-theme="dark"] .sr-workflow-stepper {
        background: #0f172a;
        border-color: rgba(30, 64, 175, .5);
        box-shadow: none;
    }
    body[data-theme="dark"] .sr-flow-step {
        color: #cbd5e1;
        border-color: rgba(71, 85, 105, .75);
    }
    body[data-theme="dark"] .sr-flow-step.done {
        color: #e5e7eb;
        background: rgba(30, 41, 59, .8);
    }
    body[data-theme="dark"] .sr-order-head {
        background: rgba(15, 23, 42, .98);
        border-bottom-color: rgba(30, 64, 175, .5);
    }
    body[data-theme="dark"] .sr-order-active .sr-order-head {
        background: rgba(30, 41, 59, .9);
    }
    body[data-theme="dark"] .sr-order-empty .sr-order-head {
        background: #0f172a;
    }
    body[data-theme="dark"] .sr-dev-command {
        background: rgba(15, 23, 42, .72);
        border-color: rgba(71, 85, 105, .55);
    }
    body[data-theme="dark"] .sr-return-table thead th {
        background: rgba(15, 23, 42, .98);
        border-bottom-color: rgba(30, 64, 175, .5);
        color: #6b7280;
    }
    body[data-theme="dark"] .sr-return-table tbody td {
        border-top-color: rgba(51, 65, 85, .65);
    }
    body[data-theme="dark"] .sr-return-table tr.last-scanned-row td {
        background: rgba(30, 64, 175, .42) !important;
    }
    body[data-theme="dark"] .sr-return-table tr.last-scanned-row td:first-child {
        border-left-color: #38bdf8;
    }
    body[data-theme="dark"] .sr-btn {
        color: #d1d5db;
        border-color: rgba(71, 85, 105, .8);
    }
    body[data-theme="dark"] .sr-btn:hover {
        background: rgba(30, 41, 59, .7);
        color: #f1f5f9;
    }
    body[data-theme="dark"] .sr-mini-btn {
        color: #d1d5db;
        border-color: rgba(71, 85, 105, .8);
    }
    body[data-theme="dark"] .sr-mini-btn:hover {
        background: rgba(30, 41, 59, .7);
        color: #f1f5f9;
    }
    body[data-theme="dark"] .sr-mini-btn-danger:hover {
        border-color: rgba(248, 113, 113, .5);
        color: #fecaca;
        background: rgba(127, 29, 29, .22);
    }
    body[data-theme="dark"] .btn-del {
        color: #fca5a5;
        border-color: rgba(239, 68, 68, .45);
    }
    body[data-theme="dark"] .btn-del:hover {
        background: rgba(127, 29, 29, .55);
    }
    body[data-theme="dark"] .sr-scan-input,
    body[data-theme="dark"] .sr-qty-input,
    body[data-theme="dark"] .sr-order-search,
    body[data-theme="dark"] .sr-dev-input {
        background: #020617;
        color: #e5e7eb;
        border-color: rgba(71, 85, 105, .8) !important;
    }
    body[data-theme="dark"] .sr-actions {
        background: transparent;
        border-color: transparent;
        box-shadow: none;
    }
    body[data-theme="dark"] .sr-order-tools {
        background: rgba(15, 23, 42, .92);
        border-bottom-color: rgba(71, 85, 105, .55);
    }
    @media (min-width: 900px) {
        .sr-order-section {
            border-radius: 6px;
            overflow: hidden;
        }

        .sr-order-panel-body {
            max-height: clamp(260px, calc(100vh - 430px), 520px);
            overflow-y: auto;
            overscroll-behavior: contain;
        }

        .sr-order-panel-body::-webkit-scrollbar {
            width: 8px;
        }

        .sr-order-panel-body::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, .45);
            border-radius: 6px;
        }
    }

    @media (max-width: 720px) {
        .sr-scan-page {
            padding: 0 .45rem calc(9.5rem + var(--sr-mobile-nav-offset));
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
        .sr-sub, .sr-top-actions .sr-btn[href] { display: none; }
        .sr-top-actions {
            grid-column: 1 / -1;
            justify-content: stretch;
        }
        .sr-top-actions .sr-btn {
            width: 100%;
            min-height: 34px;
            font-size: .64rem;
        }
        .sr-dev-command {
            padding: .4rem;
            border-radius: 6px;
        }
        .sr-dev-hint { display: none; }
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
        .sr-flow-sep {
            font-size: .62rem;
        }
        .sr-meta-panel {
            background: transparent;
            border: 0;
            box-shadow: none;
        }
        .sr-meta-panel .sr-panel-body { padding: 0; }
        .sr-meta { grid-template-columns: 1fr; gap: .4rem; }
        .sr-meta-item {
            display: none;
            border-radius: 6px;
            padding: .5rem .65rem;
            box-shadow: none;
        }
        .sr-meta-store { display: block; grid-column: auto; }
        .sr-meta-label { display: none; }
        .sr-meta-value {
            margin-top: 0;
            font-size: .8rem;
            line-height: 1.15;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sr-panel {
            border-radius: 6px;
            box-shadow: none;
        }
        .sr-panel-body { padding: .5rem; }
        .sr-scan-card { padding: .25rem; }
        .sr-scan-card {
            gap: .48rem;
            border-radius: 6px;
        }
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
        .sr-dev-command {
            margin-top: 0;
            padding-top: .4rem;
        }
        .sr-dev-command-row {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .35rem;
        }
        .sr-dev-input {
            grid-column: 1 / -1;
        }
        .sr-dev-input {
            min-height: 38px;
            border-radius: 8px !important;
            font-size: .72rem;
            padding: .42rem .55rem;
        }
        .sr-dev-command .sr-btn {
            min-height: 38px;
            padding-inline: .58rem;
            font-size: .62rem;
        }
        .sr-summary {
            gap: .3rem;
        }
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
        .sr-order-tools {
            padding: .42rem .5rem;
        }
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
        .sr-order-no,
        .sr-order-info {
            display: none;
        }
        .sr-order-code {
            font-size: .9rem;
            max-width: calc(100vw - 6rem);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .sr-order-qty {
            min-width: 34px;
            padding: .12rem .42rem;
            font-size: .74rem;
            background: #e5e7eb;
            color: #374151;
        }
        .sr-order-count {
            font-size: .68rem;
        }
        .sr-order-chevron {
            font-size: .72rem;
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
            min-height: 48px;
            padding: .45rem .65rem;
            font-size: .72rem;
        }
        .sr-toast {
            bottom: calc(var(--sr-mobile-nav-offset) + 4.25rem);
        }
        .sr-return-table thead { display: none; }
        .sr-return-table thead th,
        .sr-return-table tbody td { padding: .5rem .46rem; }
        .order-cell {
            width: 28px;
            font-size: .68rem;
            padding-right: .25rem !important;
        }
        .item-code { font-size: .9rem; }
        .item-name { font-size: .68rem; line-height: 1.15; color: #9ca3af; }
        .sr-qty-input { width: 54px; }
        .qty-edit-input { width: 58px; min-height: 38px; font-size: 1rem; }
        .btn-del { width: 36px; height: 36px; }
    }
</style>
@endpush

@section('content')
@php
    $initialLines = $shipmentReturn->orderScans->isNotEmpty()
        ? $shipmentReturn->orderScans->flatMap(function ($scan) {
            return $scan->items->map(fn ($scanItem) => [
                'id' => $scanItem->shipment_return_line_id ?: $scanItem->id,
                'order_number' => ($scan->order_number ?: $scan->order_no) ?: 'MANUAL',
                'item_id' => $scanItem->item_id,
                'code' => $scanItem->item->code ?? '-',
                'name' => $scanItem->item->name ?? '',
                'qty' => (int) ($scanItem->qty_scanned ?: $scanItem->qty),
                'update_url' => $scanItem->shipment_return_line_id
                    ? route('sales.shipment_returns.update_line_qty', $scanItem->shipment_return_line_id)
                    : null,
            ]);
        })->values()
        : $shipmentReturn->lines
            ->map(fn ($line) => [
                'id' => $line->id,
                'order_number' => $line->remarks ?: 'MANUAL',
                'item_id' => $line->item_id,
                'code' => $line->item->code ?? '-',
                'name' => $line->item->name ?? '',
                'qty' => (int) $line->qty,
                'update_url' => route('sales.shipment_returns.update_line_qty', $line),
            ])
            ->values();
    $initialOrders = $shipmentReturn->orderScans
        ->map(function ($scan) {
            $payload = is_array($scan->raw_payload ?? null) ? $scan->raw_payload : [];
            $label = collect([
                $payload['store_code'] ?? null,
                $payload['store_name'] ?? null,
            ])->filter()->implode(' - ');

            return [
                'code' => ($scan->order_number ?: $scan->order_no) ?: 'MANUAL',
                'label' => $label !== '' ? $label : 'Manual',
            ];
        })
        ->values();
    $canUseDevOrderCommands = (auth()->user()?->role === 'owner')
        && (
            app()->environment(['local', 'development', 'testing'])
            || config('app.debug')
            || str_contains(strtolower((string) config('app.url')), 'dev')
            || str_contains(strtolower((string) config('database.connections.' . config('database.default') . '.database')), 'dev')
        );
@endphp

<div class="sr-scan-page">
    <div class="sr-topbar">
        <div class="sr-top-main">
            <h1 class="sr-title">{{ $shipmentReturn->code }}</h1>
            <div class="sr-sub">Scan order, scan item, lalu order baru.</div>
        </div>
        <div class="sr-top-actions">
            @if ($shipmentReturn->status === 'draft' && $canUseDevOrderCommands)
                <button type="button" class="sr-btn" id="devToggleOrdersBtn">Command Order</button>
            @endif
            <a href="{{ route('sales.shipment_returns.show', $shipmentReturn) }}" class="sr-btn">Detail</a>
        </div>
        @if ($shipmentReturn->status === 'draft' && $canUseDevOrderCommands)
            <div class="sr-dev-command" id="devOrderCommand" hidden>
                <div class="sr-dev-hint">Dev owner: buat beberapa order kosong untuk test queue.</div>
                <div class="sr-dev-command-row">
                    <input type="text" id="devOrderInput" class="form-control sr-dev-input" placeholder="ORD001, ORD002, ORD003" autocomplete="off">
                    <button type="button" class="sr-btn" id="devAddOrdersBtn">Tambah</button>
                    <button type="button" class="sr-btn" id="devDummyOrdersBtn">+10</button>
                    <button type="button" class="sr-btn sr-btn-danger" id="devClearOrdersBtn">Bersihkan</button>
                </div>
            </div>
        @endif
    </div>

    <div class="sr-shell">
        <div class="sr-workflow-stepper" id="returnWorkflowStepper" aria-label="Workflow Retur">
            <span class="sr-flow-step active" data-flow-step="order">Scan Order</span>
            <span class="sr-flow-sep">-&gt;</span>
            <span class="sr-flow-step" data-flow-step="item">Scan Item</span>
            <span class="sr-flow-sep">-&gt;</span>
            <span class="sr-flow-step" data-flow-step="review">Cek Retur</span>
            <span class="sr-flow-sep">-&gt;</span>
            <span class="sr-flow-step" data-flow-step="confirm">Konfirmasi Retur</span>
        </div>

        <div class="sr-panel sr-meta-panel">
            <div class="sr-panel-body">
                <div class="sr-meta">
                    <div class="sr-meta-item sr-meta-store">
                        <div class="sr-meta-label">Marketplace</div>
                        <div class="sr-meta-value">{{ $shipmentReturn->store->code ?? '-' }} - {{ $shipmentReturn->store->name ?? '-' }}</div>
                    </div>
                    <div class="sr-meta-item sr-meta-date">
                        <div class="sr-meta-label">Tanggal</div>
                        <div class="sr-meta-value">{{ optional($shipmentReturn->date)->format('d M Y') }}</div>
                    </div>
                    <div class="sr-meta-item sr-meta-status">
                        <div class="sr-meta-label">Status</div>
                        <div class="sr-meta-value">{{ ucfirst($shipmentReturn->status) }}</div>
                    </div>
                    <div class="sr-meta-item sr-meta-source">
                        <div class="sr-meta-label">Shipment Asal</div>
                        <div class="sr-meta-value">{{ $shipmentReturn->shipment->code ?? 'Manual' }}</div>
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

        @if ($shipmentReturn->status === 'draft')
            <div class="sr-panel">
                <div class="sr-panel-body">
                    <div class="sr-scan-card">
                        <div class="sr-mode-row">
                            <span class="sr-mode" id="modeBadge">SCAN ORDER</span>
                            <span class="sr-current" id="currentLabel">Belum ada pesanan aktif</span>
                        </div>
                        <input type="text" id="scanInput" class="form-control sr-scan-input" placeholder="Scan nomor pesanan" inputmode="text" autocomplete="off" autofocus>
                    </div>
                </div>
            </div>
        @endif

        <div class="sr-panel sr-order-section">
            <div class="sr-order-tools" id="orderTools">
                <input type="search" id="orderSearch" class="form-control sr-order-search" placeholder="Cari order" autocomplete="off">
            </div>
            <div class="sr-panel-body sr-order-panel-body">
                <div class="sr-orders" id="ordersWrap">
                    <div class="sr-empty">Belum ada item retur.</div>
                </div>
            </div>
        </div>

        <div class="sr-actions">
            @if ($shipmentReturn->status === 'draft')
                <button type="button" class="sr-btn" id="nextOrderBtn">Order Baru</button>
                <a href="{{ route('sales.shipment_returns.show', $shipmentReturn) }}"
                   class="sr-btn sr-btn-primary is-disabled"
                   id="submitBtn"
                   aria-disabled="true"
                   tabindex="-1">Cek Retur</a>
            @else
                <a href="{{ route('sales.shipment_returns.show', $shipmentReturn) }}" class="sr-btn">Detail</a>
                <a href="{{ route('sales.shipment_returns.index') }}" class="sr-btn sr-btn-primary">Daftar Retur</a>
            @endif
        </div>
    </div>
</div>

<div id="toast" class="sr-toast"></div>
@endsection

@push('scripts')
<script>
(function () {
    const scanUrl = @json(route('sales.shipment_returns.scan_item', $shipmentReturn));
    const scanLookupUrl = @json(route('sales.shipment_returns.scan_lookup', $shipmentReturn));
    const bulkOrdersUrl = @json(route('sales.shipment_returns.bulk_orders', $shipmentReturn));
    const clearOrdersUrl = @json(route('sales.shipment_returns.clear_orders', $shipmentReturn));
    const lookupShipmentUrl = @json(route('sales.api.shipments.lookup'));
    const csrf = @json(csrf_token());
    const isDraft = @json($shipmentReturn->status === 'draft');
    const initialLines = @json($initialLines);
    const initialOrders = @json($initialOrders);
    const canUseDevOrderCommands = @json($canUseDevOrderCommands);

    const state = { mode: 'order', current: null, expanded: null, search: '', orders: [] };
    const scanInput = document.getElementById('scanInput');
    const modeBadge = document.getElementById('modeBadge');
    const currentLabel = document.getElementById('currentLabel');
    const workflowStepper = document.getElementById('returnWorkflowStepper');
    const nextOrderBtn = document.getElementById('nextOrderBtn');
    const submitBtn = document.getElementById('submitBtn');
    const orderTools = document.getElementById('orderTools');
    const orderSearch = document.getElementById('orderSearch');
    const orderPanelBody = document.querySelector('.sr-order-panel-body');
    const devOrderCommand = document.getElementById('devOrderCommand');
    const devToggleOrdersBtn = document.getElementById('devToggleOrdersBtn');
    const devOrderInput = document.getElementById('devOrderInput');
    const devAddOrdersBtn = document.getElementById('devAddOrdersBtn');
    const devDummyOrdersBtn = document.getElementById('devDummyOrdersBtn');
    const devClearOrdersBtn = document.getElementById('devClearOrdersBtn');
    const ordersWrap = document.getElementById('ordersWrap');
    const sumOrders = document.getElementById('sumOrders');
    const sumItems = document.getElementById('sumItems');
    const sumQty = document.getElementById('sumQty');
    const toastEl = document.getElementById('toast');
    let toastTimer = null;
    let scrollTarget = null;
    let lastScannedLineId = null;
    let audioCtx = null;

    function normalize(value) { return String(value || '').trim().toUpperCase(); }
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
        return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    function audioContext() {
        try {
            const Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return null;
            audioCtx = audioCtx || new Ctx();
            if (audioCtx.state === 'suspended') audioCtx.resume().catch(() => {});
            return audioCtx;
        } catch (e) {}
        return null;
    }
    function beep(freq, dur = .12, vol = .16, type = 'sine', delay = 0) {
        try {
            const ctx = audioContext();
            if (!ctx) return;
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            const start = ctx.currentTime + delay;
            osc.type = type;
            osc.frequency.setValueAtTime(freq, start);
            osc.connect(gain);
            gain.connect(ctx.destination);
            gain.gain.setValueAtTime(vol, start);
            gain.gain.exponentialRampToValueAtTime(.001, start + dur);
            osc.start(start);
            osc.stop(start + dur);
        } catch (e) {}
    }
    function playTone(kind) {
        const tones = {
            order: [[660, .07, .13, 'sine', 0], [880, .11, .13, 'sine', .08]],
            orderRepeat: [[784, .08, .12, 'triangle', 0], [784, .08, .1, 'triangle', .11]],
            item: [[1046, .06, .12, 'sine', 0], [1318, .08, .1, 'sine', .07]],
            next: [[740, .06, .12, 'triangle', 0], [932, .06, .11, 'triangle', .07], [1175, .1, .1, 'triangle', .14]],
            reset: [[392, .08, .13, 'sawtooth', 0], [294, .14, .12, 'sawtooth', .1]],
            undo: [[587, .06, .12, 'square', 0], [440, .1, .1, 'square', .08]],
            error: [[220, .16, .16, 'square', 0]],
            errorGuard: [[170, .08, .17, 'square', 0], [170, .12, .15, 'square', .12]],
            errorNoOrder: [[247, .08, .15, 'sawtooth', 0], [196, .12, .14, 'sawtooth', .1]],
            errorItem: [[196, .08, .15, 'square', 0], [262, .08, .13, 'square', .1], [196, .12, .13, 'square', .2]],
            errorNetwork: [[330, .06, .14, 'sawtooth', 0], [220, .06, .14, 'sawtooth', .08], [165, .12, .13, 'sawtooth', .16]],
            errorQty: [[262, .06, .14, 'triangle', 0], [262, .06, .11, 'triangle', .09], [220, .1, .12, 'triangle', .18]],
            errorEmpty: [[185, .1, .13, 'square', 0], [147, .12, .11, 'square', .12]],
        };
        (tones[kind] || tones.error).forEach(([freq, dur, vol, type, delay]) => beep(freq, dur, vol, type, delay));
    }
    function toast(type, message) {
        toastEl.className = 'sr-toast ' + (type === 'ok' ? 'sr-toast-ok' : 'sr-toast-err');
        toastEl.textContent = message;
        toastEl.style.display = 'block';
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toastEl.style.display = 'none', 1500);
    }
    function focusScan(options = {}) {
        if (!scanInput) return;
        try {
            scanInput.focus({ preventScroll: !!options.preventScroll });
        } catch (e) {
            scanInput.focus();
        }
    }
    function alertError(message, sound = 'error') {
        playTone(sound);

        if (window.Swal) {
            Swal.fire({
                icon: 'error',
                title: 'Scan gagal',
                text: message || 'Terjadi kesalahan saat scan.',
                confirmButtonText: 'OK',
                confirmButtonColor: '#111827',
                timer: 2200,
                timerProgressBar: true,
            }).then(() => {
                if (scanInput) {
                    scanInput.value = '';
                    focusScan();
                }
            });
            return;
        }

        toast('err', message || 'Terjadi kesalahan saat scan.');
        if (scanInput) {
            scanInput.value = '';
            focusScan();
        }
    }
    function setMode(mode) {
        state.mode = mode;
        if (modeBadge) modeBadge.textContent = mode === 'order' ? 'SCAN ORDER' : 'SCAN ITEM';
        if (scanInput) {
            scanInput.placeholder = mode === 'order' ? 'Scan nomor pesanan' : 'Scan kode item retur';
            scanInput.value = '';
            setTimeout(() => focusScan({ preventScroll: !!scrollTarget }), 30);
        }
        render();
    }
    function activeOrder() {
        return state.orders.find(order => order.code === state.current) || null;
    }
    function findOrder(code) {
        code = normalize(code);
        return state.orders.find(order => order.code === code) || null;
    }
    function latestOrder() {
        return activeOrder() || state.orders[state.orders.length - 1] || null;
    }
    function latestLineId() {
        if (lastScannedLineId) return lastScannedLineId;
        const order = latestOrder();
        if (!order) return null;
        const items = Object.values(order.items);
        return items.length ? String(items[items.length - 1].line_id) : null;
    }
    function findItemByLineId(lineId) {
        let found = null;
        state.orders.some(order => {
            const item = order.items[String(lineId)];
            if (!item) return false;
            found = { order, item };
            return true;
        });
        return found;
    }
    function orderQty(order) {
        return Object.values(order.items).reduce((sum, item) => sum + item.qty, 0);
    }
    function totals() {
        let qty = 0;
        const itemIds = new Set();
        state.orders.forEach(order => {
            Object.values(order.items).forEach(item => {
                itemIds.add(String(item.item_id));
                qty += item.qty;
            });
        });
        return { orders: state.orders.length, items: itemIds.size, qty };
    }
    function setSubmitDisabled(disabled) {
        if (!submitBtn) return;
        if ('disabled' in submitBtn) submitBtn.disabled = disabled;
        submitBtn.classList.toggle('is-disabled', disabled);
        submitBtn.setAttribute('aria-disabled', disabled ? 'true' : 'false');
        if (submitBtn.tagName === 'A') submitBtn.tabIndex = disabled ? -1 : 0;
    }
    function updateWorkflow(total = totals()) {
        if (!workflowStepper) return;

        workflowStepper.querySelectorAll('[data-flow-step]').forEach(step => {
            step.classList.remove('active', 'done');
        });

        const orderStep = workflowStepper.querySelector('[data-flow-step="order"]');
        const itemStep = workflowStepper.querySelector('[data-flow-step="item"]');
        const reviewStep = workflowStepper.querySelector('[data-flow-step="review"]');
        const confirmStep = workflowStepper.querySelector('[data-flow-step="confirm"]');

        if (total.orders > 0 || state.mode === 'item') orderStep?.classList.add('done');
        if (total.qty > 0) itemStep?.classList.add('done');
        if (total.qty > 0) reviewStep?.classList.add('active');

        if (state.mode === 'order' && total.qty <= 0) {
            orderStep?.classList.add('active');
            orderStep?.classList.remove('done');
        } else if (state.mode === 'item') {
            itemStep?.classList.add('active');
            itemStep?.classList.remove('done');
            reviewStep?.classList.remove('active');
        } else if (total.qty > 0) {
            reviewStep?.classList.add('active');
            reviewStep?.classList.remove('done');
        }

        if (!isDraft && total.qty > 0) {
            reviewStep?.classList.add('done');
            reviewStep?.classList.remove('active');
            confirmStep?.classList.add('active');
        }
    }
    function ensureOrder(code, info = {}) {
        code = normalize(code) || 'MANUAL';
        let order = state.orders.find(row => row.code === code);
        if (!order) {
            order = { code, info: info.label || (code === 'MANUAL' ? 'Tanpa order' : 'Manual'), items: {} };
            state.orders.push(order);
        }
        if (info.label) order.info = info.label;
        state.current = code;
        state.expanded = code;
        state.search = '';
        if (orderSearch) orderSearch.value = '';
        return order;
    }
    function upsertItem(orderCode, item) {
        const order = ensureOrder(orderCode);
        const key = String(item.line_id || item.id || item.item_id);
        order.items[key] = {
            line_id: item.line_id || item.id,
            item_id: item.item_id,
            code: item.code,
            name: item.name || '',
            qty: Number(item.qty || 0),
            update_url: item.update_url || null,
        };
        lastScannedLineId = String(order.items[key].line_id);
        scrollTarget = { orderCode: order.code, lineId: String(order.items[key].line_id) };
    }
    function removeItem(lineId) {
        state.orders.forEach(order => {
            delete order.items[String(lineId)];
        });
        state.orders = state.orders.filter(order => Object.keys(order.items).length > 0);
        if (state.expanded && !state.orders.some(order => order.code === state.expanded)) {
            state.expanded = state.current || state.orders[state.orders.length - 1]?.code || null;
        }
    }
    function render() {
        const current = activeOrder();
        const total = totals();
        if (currentLabel) currentLabel.textContent = current ? `${current.code} | ${orderQty(current)} qty` : 'Belum ada pesanan aktif';
        if (sumOrders) sumOrders.textContent = total.orders;
        if (sumItems) sumItems.textContent = total.items;
        if (sumQty) sumQty.textContent = total.qty;
        setSubmitDisabled(total.qty <= 0);
        updateWorkflow(total);

        if (!state.orders.length) {
            if (orderTools) orderTools.style.display = 'none';
            ordersWrap.innerHTML = '<div class="sr-empty">Belum ada item retur.</div>';
            return;
        }

        if (!state.expanded || !state.orders.some(order => order.code === state.expanded)) {
            state.expanded = state.current || state.orders[state.orders.length - 1]?.code || null;
        }
        if (scrollTarget?.orderCode) {
            state.expanded = scrollTarget.orderCode;
        }

        const query = normalize(state.search);
        const visibleOrders = state.orders.filter(order => {
            if (!query) return true;
            return normalize(order.code).includes(query)
                || normalize(order.info).includes(query)
                || Object.values(order.items).some(item => normalize(item.code).includes(query) || normalize(item.name).includes(query));
        });

        if (orderTools) orderTools.style.display = state.orders.length > 5 || state.search ? 'block' : 'none';

        if (!visibleOrders.length) {
            ordersWrap.innerHTML = '<div class="sr-empty">Order tidak ditemukan.</div>';
            return;
        }

        ordersWrap.innerHTML = visibleOrders.map(order => {
            const items = Object.values(order.items);
            const expanded = order.code === state.expanded;
            const empty = items.length === 0;
            const orderQtyTotal = orderQty(order);
            return `
                <div class="sr-order ${expanded ? 'sr-order-active' : 'sr-order-collapsed'} ${empty ? 'sr-order-empty' : ''}" data-order-code="${esc(order.code)}">
                    <button type="button" class="sr-order-head sr-order-toggle" data-toggle-order="${esc(order.code)}" aria-expanded="${expanded ? 'true' : 'false'}">
                        <div>
                            <div class="sr-order-no">No Order</div>
                            <div class="sr-order-code">${esc(order.code)}</div>
                            <div class="sr-order-info">${esc(order.info || 'Manual')}</div>
                        </div>
                        <div class="sr-order-head-right">
                            <span class="sr-order-count">${empty ? 'Kosong' : `${items.length} item`}</span>
                            ${empty ? '' : `<span class="sr-order-qty">${orderQtyTotal}</span>`}
                            <span class="sr-order-chevron">${expanded ? '-' : '+'}</span>
                        </div>
                    </button>
                    <div class="sr-item-list">
                        ${items.length ? `
                            <table class="sr-return-table">
                                <thead>
                                    <tr>
                                        <th class="text-end" style="width:42px">#</th>
                                        <th>Item</th>
                                        <th class="text-end" style="width:86px">Qty</th>
                                        <th class="text-end" style="width:46px"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${items.map((item, index) => `
                                        <tr data-line-id="${item.line_id}" class="${String(item.line_id) === String(lastScannedLineId) ? 'last-scanned-row row-flash' : ''}">
                                            <td class="text-muted small order-cell">${index + 1}</td>
                                            <td>
                                                <div class="item-code">${esc(item.code)}</div>
                                                <div class="item-name">${esc(item.name)}</div>
                                            </td>
                                            <td class="text-end">
                                                <input type="number" class="form-control form-control-sm qty-edit-input sr-qty-input" min="0" value="${item.qty}" data-line-id="${item.line_id}" aria-label="Qty ${esc(item.code)}">
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn-del" data-delete-line="${item.line_id}" title="Hapus" aria-label="Hapus ${esc(item.code)}">x</button>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        ` : '<div class="sr-empty">Scan item untuk pesanan ini.</div>'}
                    </div>
                </div>
            `;
        }).join('');

        ordersWrap.querySelectorAll('[data-toggle-order]').forEach(button => {
            button.addEventListener('click', () => {
                const code = normalize(button.dataset.toggleOrder);
                state.current = code;
                state.expanded = code;
                if (isDraft) {
                    setMode('item');
                } else {
                    render();
                }
            });
        });
        ordersWrap.querySelectorAll('[data-delete-line]').forEach(button => {
            button.addEventListener('click', () => confirmDeleteLine(button.dataset.deleteLine));
        });
        ordersWrap.querySelectorAll('.sr-qty-input').forEach(input => {
            input.dataset.originalQty = input.value;
            input.addEventListener('keydown', event => {
                if (event.key !== 'Enter') return;
                event.preventDefault();
                input.blur();
            });
            input.addEventListener('blur', () => {
                if (input.value === input.dataset.originalQty) return;
                saveLineQty(input.dataset.lineId, Number(input.value || 0));
            });
        });

        if (scrollTarget) {
            const row = ordersWrap.querySelector(`[data-line-id="${scrollTarget.lineId}"]`)
                || Array.from(ordersWrap.querySelectorAll('.sr-order'))
                    .find(orderEl => orderEl.dataset.orderCode === scrollTarget.orderCode);
            if (row) {
                setTimeout(() => {
                    if (orderPanelBody && orderPanelBody.scrollHeight > orderPanelBody.clientHeight) {
                        const panelBox = orderPanelBody.getBoundingClientRect();
                        const rowBox = row.getBoundingClientRect();
                        orderPanelBody.scrollTop += rowBox.bottom - panelBox.bottom + 12;
                    }
                    row.scrollIntoView({ behavior: 'smooth', block: 'end' });
                    focusScan({ preventScroll: true });
                }, 40);
            }
            scrollTarget = null;
        }
    }
    function updateLineInState(lineId, qty) {
        state.orders.forEach(order => {
            const item = order.items[String(lineId)];
            if (item) item.qty = qty;
        });
    }
    function saveLineQty(lineId, qty, options = {}) {
        if (!lineId) return Promise.resolve(null);
        if (!Number.isInteger(qty) || qty < 0) {
            alertError('Qty harus angka 0 atau lebih.', 'errorQty');
            return Promise.resolve(null);
        }

        return fetch(`/sales/shipment-return-lines/${lineId}`, {
            method: 'PATCH',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ qty })
        })
            .then(async response => {
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(payload.message || 'Gagal update qty');
                return payload;
            })
            .then(payload => {
                if (payload.deleted || qty === 0) {
                    removeItem(lineId);
                    if (String(lastScannedLineId) === String(lineId)) lastScannedLineId = null;
                    if (!options.silent) toast('ok', 'Baris dihapus');
                } else {
                    updateLineInState(lineId, qty);
                    if (!options.silent) toast('ok', 'Qty diperbarui');
                }
                if (!options.silent) {
                    render();
                    focusScan({ preventScroll: true });
                }
                return payload;
            })
            .catch(error => {
                alertError(error.message || 'Gagal update qty', 'errorNetwork');
                return null;
            });
    }
    function confirmDeleteLine(lineId) {
        if (!lineId) return;
        if (window.Swal) {
            Swal.fire({
                icon: 'warning',
                title: 'Hapus baris?',
                text: 'Item ini akan dihapus dari retur.',
                showCancelButton: true,
                confirmButtonText: 'Hapus',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#b91c1c',
                cancelButtonColor: '#64748b',
            }).then(result => {
                if (result.isConfirmed) saveLineQty(lineId, 0);
            });
            return;
        }
        if (confirm('Hapus baris ini?')) saveLineQty(lineId, 0);
    }
    function nextOrderCommand() {
        state.current = null;
        playTone('next');
        toast('ok', 'Order baru');
        setMode('order');
    }
    function resetActiveOrderCommand() {
        const order = latestOrder();
        if (!order) {
            alertError('Belum ada order aktif untuk di-reset.', 'errorEmpty');
            setMode('order');
            return;
        }

        const lineIds = Object.values(order.items).map(item => item.line_id).filter(Boolean);
        if (!lineIds.length) {
            state.orders = state.orders.filter(row => row.code !== order.code);
            state.current = null;
            render();
            setMode('order');
            playTone('reset');
            toast('ok', `Order ${order.code} di-reset`);
            return;
        }

        Promise.all(lineIds.map(lineId => saveLineQty(lineId, 0, { silent: true }))).then(() => {
            state.current = null;
            lastScannedLineId = null;
            render();
            setMode('order');
            playTone('reset');
            toast('ok', `Order ${order.code} di-reset`);
        });
    }
    function undoLastItemCommand() {
        const lineId = latestLineId();
        if (!lineId) {
            alertError('Belum ada item terakhir untuk di-undo.', 'errorEmpty');
            return;
        }

        const found = findItemByLineId(lineId);
        if (!found) {
            alertError('Item terakhir sudah tidak ada di daftar.', 'errorEmpty');
            lastScannedLineId = null;
            return;
        }

        const currentQty = Number(found.item.qty || 0);
        const nextQty = Math.max(currentQty - 1, 0);

        saveLineQty(lineId, nextQty, { silent: true }).then(payload => {
            if (!payload) return;
            if (nextQty > 0) {
                lastScannedLineId = String(lineId);
                scrollTarget = { orderCode: found.order.code, lineId: String(lineId) };
            } else {
                lastScannedLineId = null;
                scrollTarget = { orderCode: found.order.code, lineId: null };
            }
            render();
            focusScan({ preventScroll: true });
            playTone('undo');
            toast('ok', 'Scan terakhir dibatalkan');
        });
    }
    function lookupScanCode(code) {
        return fetch(`${scanLookupUrl}?code=${encodeURIComponent(code)}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(r => r.ok ? r.json() : Promise.reject(new Error('lookup_failed')));
    }
    function lookupShipment(code) {
        return fetch(`${lookupShipmentUrl}?code=${encodeURIComponent(code)}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(r => r.ok ? r.json() : null);
    }
    function startOrder(code) {
        code = normalize(code);
        if (!code) return;

        const existingOrder = findOrder(code);
        if (existingOrder) {
            state.current = existingOrder.code;
            scrollTarget = { orderCode: existingOrder.code, lineId: null };
            playTone('orderRepeat');
            toast('ok', `Kembali ke order ${existingOrder.code}`);
            setMode('item');
            return;
        }

        lookupScanCode(code).then(data => {
            if (data?.type === 'item') {
                alertError(`Yang discan adalah item ${data.item?.code || code}. Scan nomor order dulu.`, 'errorGuard');
                setMode('order');
                return;
            }

            const orderCode = normalize(data?.order?.code || code);
            const label = data?.type === 'order'
                ? [data.order?.store_code, data.order?.store_name].filter(Boolean).join(' - ')
                : 'Manual';
            const order = ensureOrder(orderCode, { label: label || 'Manual' });
            scrollTarget = { orderCode: order.code, lineId: null };
            playTone('order');
            toast('ok', `Order ${order.code}`);
            setMode('item');
        }).catch(() => {
            alertError('Gagal cek nomor order. Coba scan ulang.', 'errorNetwork');
        });
    }
    function scanItem(code) {
        code = normalize(code);
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
            body: JSON.stringify({ scan_code: code, qty: 1, order_number: order.code === 'MANUAL' ? '' : order.code })
        })
            .then(async response => {
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(payload.message || 'Gagal scan item');
                return payload;
            })
            .then(payload => {
                upsertItem(order.code, {
                    line_id: payload.line.id,
                    item_id: payload.line.item_id || payload.line.id,
                    code: payload.line.item_code,
                    name: payload.line.item_name,
                    qty: payload.line.qty,
                    update_url: payload.line.update_qty_url,
                });
                playTone('item');
                toast('ok', `+1 ${payload.line.item_code}`);
                render();
            })
            .catch(error => {
                const message = error.message || 'Gagal scan item';
                alertError(message, message.includes('tidak ditemukan') ? 'errorItem' : 'errorNetwork');
            });
    }
    function addBulkOrders(rawOrders) {
        const orders = String(rawOrders || '').trim();
        if (!orders) {
            alertError('Nomor order belum diisi.', 'errorEmpty');
            return;
        }

        fetch(bulkOrdersUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ orders })
        })
            .then(async response => {
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(payload.message || 'Gagal tambah order');
                return payload;
            })
            .then(payload => {
                const createdOrders = payload.orders || [];
                createdOrders.forEach(order => {
                    ensureOrder(order.code, { label: order.label || 'Manual' });
                });
                if (createdOrders.length) {
                    const firstOrderCode = normalize(createdOrders[0].code);
                    state.current = firstOrderCode;
                    state.expanded = firstOrderCode;
                    scrollTarget = { orderCode: firstOrderCode, lineId: null };
                }
                if (devOrderInput) devOrderInput.value = '';
                if (devOrderCommand) devOrderCommand.hidden = true;
                playTone('order');
                toast('ok', payload.message || 'Order ditambahkan');
                setMode(createdOrders.length ? 'item' : 'order');
            })
            .catch(error => {
                alertError(error.message || 'Gagal tambah order', 'errorNetwork');
            });
    }
    function makeDummyOrders(count = 10) {
        const stamp = new Date();
        const prefix = 'DEV' + String(stamp.getHours()).padStart(2, '0') + String(stamp.getMinutes()).padStart(2, '0');
        const orders = Array.from({ length: count }, (_, index) => `${prefix}-${String(index + 1).padStart(3, '0')}`);
        addBulkOrders(orders.join(','));
    }
    function clearDevOrders() {
        const runClear = () => {
            fetch(clearOrdersUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({})
            })
                .then(async response => {
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) throw new Error(payload.message || 'Gagal bersihkan order');
                    return payload;
                })
                .then(payload => {
                    state.orders = [];
                    state.current = null;
                    state.expanded = null;
                    state.search = '';
                    lastScannedLineId = null;
                    scrollTarget = null;
                    if (orderSearch) orderSearch.value = '';
                    if (devOrderInput) devOrderInput.value = '';
                    if (devOrderCommand) devOrderCommand.hidden = true;
                    playTone('reset');
                    toast('ok', payload.message || 'Order dibersihkan');
                    setMode('order');
                })
                .catch(error => {
                    alertError(error.message || 'Gagal bersihkan order', 'errorNetwork');
                });
        };

        if (window.Swal) {
            Swal.fire({
                icon: 'warning',
                title: 'Bersihkan order?',
                text: 'Semua order dan item retur yang terhubung ke order di draft ini akan dihapus.',
                showCancelButton: true,
                confirmButtonText: 'Bersihkan',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#991b1b',
                cancelButtonColor: '#64748b',
            }).then(result => {
                if (result.isConfirmed) runClear();
            });
            return;
        }

        if (confirm('Bersihkan semua order di draft ini?')) runClear();
    }

    initialOrders.forEach(order => {
        ensureOrder(order.code, { label: order.label || 'Manual' });
    });
    initialLines.forEach(line => {
        const orderCode = normalize(line.order_number || 'MANUAL') || 'MANUAL';
        upsertItem(orderCode, {
            line_id: line.id,
            item_id: line.item_id,
            code: line.code,
            name: line.name,
            qty: line.qty,
            update_url: line.update_url,
        });
    });
    scrollTarget = null;
    lastScannedLineId = null;
    render();
    orderSearch?.addEventListener('input', function () {
        state.search = this.value;
        render();
    });

    if (isDraft) {
        setMode('order');
        scanInput?.addEventListener('input', function () { this.value = this.value.toUpperCase(); });
        scanInput?.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            const code = normalize(this.value);
            this.value = '';
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

            state.mode === 'order' ? startOrder(code) : scanItem(code);
        });
        nextOrderBtn?.addEventListener('click', function () {
            nextOrderCommand();
        });
        if (canUseDevOrderCommands) {
            devToggleOrdersBtn?.addEventListener('click', function () {
                if (!devOrderCommand) return;
                devOrderCommand.hidden = !devOrderCommand.hidden;
                if (!devOrderCommand.hidden) {
                    setTimeout(() => devOrderInput?.focus(), 30);
                } else {
                    focusScan({ preventScroll: true });
                }
            });
            devAddOrdersBtn?.addEventListener('click', function () {
                addBulkOrders(devOrderInput?.value || '');
            });
            devDummyOrdersBtn?.addEventListener('click', function () {
                makeDummyOrders(10);
            });
            devClearOrdersBtn?.addEventListener('click', function () {
                clearDevOrders();
            });
            devOrderInput?.addEventListener('keydown', function (event) {
                if (event.key !== 'Enter') return;
                event.preventDefault();
                addBulkOrders(this.value);
            });
        }
        window.addEventListener('load', () => focusScan());
    }
})();
</script>
@endpush
