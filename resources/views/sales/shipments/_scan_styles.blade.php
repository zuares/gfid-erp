@push('head')
<style>
    :root {
        --sr-accent: #2563eb;
        --sr-accent-2: #1d4ed8;
        --sr-accent-bg: rgba(37, 99, 235, .12);
        --sr-text: #0f172a;
        --sr-muted: #64748b;
        --sr-mobile-nav-offset: 0px;
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
        border: 1px solid rgba(148, 163, 184, .12);
        border-radius: 8px;
        padding: .55rem .7rem;
        background: #fff;
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
        background: var(--sr-accent);
        color: #fff;
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
        border: 2px solid rgba(37, 99, 235, .18) !important;
        font-size: 1.35rem;
        font-weight: 800;
        letter-spacing: .03em;
        padding: .8rem 1rem;
        text-transform: uppercase;
    }

    .sr-scan-input:focus {
        border-color: var(--sr-accent) !important;
        box-shadow: 0 0 0 .18rem var(--sr-accent-bg) !important;
    }

    .sr-order-section { min-height: 180px; }

    .sr-order-tools {
        padding: .55rem .7rem;
        border-bottom: 1px solid rgba(148, 163, 184, .1);
        background: #f8fafc;
    }

    .sr-order-search {
        min-height: 42px;
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
        padding: 1rem;
        border-radius: 8px;
        background: #f8fafc;
        color: #94a3b8;
        font-size: .86rem;
        text-align: center;
    }

    .sr-order {
        border: 1px solid rgba(148, 163, 184, .15);
        border-radius: 8px;
        background: #fff;
        overflow: hidden;
    }

    .sr-order.active {
        border-color: rgba(37, 99, 235, .35);
        box-shadow: 0 0 0 2px rgba(37, 99, 235, .06);
    }

    .sr-order-head {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto auto;
        align-items: center;
        gap: .5rem;
        padding: .55rem .7rem;
        cursor: pointer;
    }

    .sr-order-code {
        color: #0f172a;
        font-size: .95rem;
        font-weight: 850;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
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

    .sr-order-chevron {
        color: #94a3b8;
        font-size: .78rem;
        font-weight: 800;
    }

    .sr-order-items {
        border-top: 1px solid rgba(148, 163, 184, .1);
        padding: .4rem .55rem .55rem;
        display: grid;
        gap: .35rem;
    }

    .sr-item-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        gap: .5rem;
        padding: .45rem .55rem;
        border-radius: 7px;
        background: #f8fafc;
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
        font-weight: 850;
        color: #334155;
        font-size: .85rem;
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
    }
</style>
@endpush
