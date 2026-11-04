@extends('layouts.app')

@section('title', 'Marketplace • Stok Variant Kosong')

@push('head')
<style>
    .sv-page {
        --sv-brand: var(--gf-color-brand, #5b5ce2);
        --sv-brand-strong: var(--gf-color-brand-strong, #4142bb);
        --sv-content: var(--gf-color-content, #101828);
        --sv-muted: var(--gf-color-muted, #667085);
        --sv-surface: var(--gf-color-surface, #fff);
        --sv-soft: var(--gf-color-surface-muted, #f2f4f7);
        --sv-line: var(--gf-color-line, #e4e7ec);
        --sv-success: var(--gf-color-success, #14945a);
        --sv-warning: var(--gf-color-warning, #b7791f);
        --sv-danger: var(--gf-color-danger, #d6455d);
        --sv-radius-card: var(--gf-radius-card, 16px);
        --sv-radius-control: var(--gf-radius-control, 10px);
        --sv-shadow: var(--gf-shadow-sm, 0 1px 2px rgba(16, 24, 40, .04));
        width: 100%;
        max-width: var(--gf-content-max, 1280px);
        min-width: 0;
        margin-inline: auto;
        padding: 1rem var(--gf-content-gutter, .75rem) 4rem;
        color: var(--sv-content);
    }
    .sv-page :where(a, button, input, select, summary):focus-visible {
        outline: 3px solid color-mix(in srgb, var(--sv-brand) 28%, transparent);
        outline-offset: 2px;
    }
    .sv-head {
        display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem;
        margin-bottom: .9rem; padding: .15rem .1rem;
    }
    .sv-eyebrow {
        display: inline-flex; align-items: center; gap: .35rem; margin-bottom: .35rem;
        color: var(--sv-brand-strong); font-size: .68rem; font-weight: 800;
        letter-spacing: .1em; text-transform: uppercase;
    }
    .sv-title {
        margin: 0; color: var(--sv-content); font-size: clamp(1.25rem, 4vw, 1.7rem);
        font-weight: 850; letter-spacing: -.04em; line-height: 1.15;
    }
    .sv-copy { display: none; }
    .sv-back {
        display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
        min-height: 40px; padding: .5rem .75rem; border: 1px solid var(--sv-line);
        border-radius: var(--sv-radius-control); background: color-mix(in srgb, var(--sv-surface) 88%, transparent); box-shadow: 0 1px 2px rgba(16, 24, 40, .04);
        color: var(--sv-content); font-size: .76rem; font-weight: 750; text-decoration: none;
    }
    .sv-back:hover { border-color: color-mix(in srgb, var(--sv-brand) 32%, var(--sv-line)); color: var(--sv-brand-strong); }
    .sv-dashboard-content { position: relative; transition: opacity .16s ease; }
    .sv-dashboard-content.is-loading { opacity: .58; pointer-events: none; }
    .sv-dashboard-content.is-loading::before {
        content: ''; position: absolute; z-index: 20; top: 0; right: 0; left: 0; height: 3px;
        border-radius: 999px; background: linear-gradient(90deg, transparent, var(--sv-brand), transparent);
        background-size: 45% 100%; animation: sv-loading 1s linear infinite;
    }
    .sv-panel {
        border: 1px solid var(--sv-line); border-radius: var(--sv-radius-card);
        background: var(--sv-surface); box-shadow: var(--sv-shadow);
    }
    .sv-section-label, .sv-field-label, .sv-cell-label {
        color: var(--sv-muted); font-size: .66rem; font-weight: 750; letter-spacing: .025em;
    }
    .sv-section-label { display: block; margin: 0 0 .5rem .15rem; text-transform: uppercase; }
    .sv-store-tab {
        display: inline-flex; flex: 0 0 auto; align-items: center; justify-content: center; gap: .4rem;
        min-height: 34px; padding: .4rem .65rem; border: 1px solid var(--sv-line); border-radius: 8px;
        background: var(--sv-soft); color: var(--sv-muted); font-size: .71rem; font-weight: 750;
        line-height: 1; text-decoration: none; white-space: nowrap; scroll-snap-align: start;
    }
    .sv-store-tab:hover { border-color: color-mix(in srgb, var(--sv-brand) 28%, var(--sv-line)); color: var(--sv-brand-strong); }
    .sv-store-tab.active {
        border-color: color-mix(in srgb, var(--sv-brand) 38%, var(--sv-line));
        background: color-mix(in srgb, var(--sv-brand) 10%, var(--sv-surface)); box-shadow: 0 1px 2px rgba(16, 24, 40, .06); color: var(--sv-brand-strong);
    }
    .sv-scope-nav { display: flex; gap: .25rem; flex-wrap: wrap; padding: .25rem; border: 1px solid var(--sv-line); border-radius: 12px; background: var(--sv-soft); }
    .sv-scope-btn {
        display: inline-flex; align-items: center; gap: .45rem; flex: 1 1 0;
        min-height: 40px; padding: .4rem .7rem; border: 1px solid transparent;
        border-radius: 9px; background: transparent;
        color: var(--sv-muted); font-size: .76rem; font-weight: 750; text-decoration: none;
        line-height: 1.2; white-space: nowrap; transition: color .15s ease, background .15s ease, border-color .15s ease, box-shadow .15s ease;
    }
    .sv-scope-btn:hover {
        border-color: color-mix(in srgb, var(--sv-brand) 35%, var(--sv-line));
        color: var(--sv-brand-strong);
    }
    .sv-scope-btn.active {
        border-color: color-mix(in srgb, var(--sv-brand) 16%, var(--sv-line));
        background: var(--sv-surface); box-shadow: 0 1px 2px rgba(16, 24, 40, .06);
        color: var(--sv-brand-strong);
    }
    .sv-scope-btn > span:first-of-type { overflow: hidden; text-overflow: ellipsis; }
    .sv-scope-btn--warning.active {
        border-color: color-mix(in srgb, var(--sv-warning) 30%, var(--sv-line));
        color: var(--sv-warning);
    }
    .sv-badge {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 22px; height: 22px; padding: 0 .4rem; border-radius: 999px;
        background: var(--sv-soft); color: var(--sv-muted); font-size: .68rem; font-weight: 800;
    }
    .sv-scope-btn.active .sv-badge { background: var(--sv-brand); color: #fff; }
    .sv-scope-btn--warning.active .sv-badge { background: var(--sv-warning); color: #fff; }
    .sv-filter {
        display: grid; grid-template-columns: minmax(0, 1fr); gap: .65rem;
        grid-column: 1 / -1; margin: 0; padding: 0;
    }
    .sv-filter-head { display: flex; align-items: center; gap: .4rem; grid-column: 1 / -1; }
    .sv-filter-title { display: flex; align-items: center; gap: .4rem; margin: 0; color: var(--sv-content); font-size: .78rem; font-weight: 800; }
    .sv-filter-title i { display: inline-grid; width: 1.5rem; height: 1.5rem; place-items: center; border-radius: .45rem; background: color-mix(in srgb, var(--sv-brand) 10%, var(--sv-surface)); color: var(--sv-brand); font-size: .7rem; }
    .sv-field { min-width: 0; }
    .sv-field-label { display: block; margin-bottom: .32rem; }
    .sv-search-wrap { position: relative; }
    .sv-search-wrap > i {
        position: absolute; top: 50%; left: .75rem; color: var(--sv-muted);
        pointer-events: none; transform: translateY(-50%);
    }
    .sv-control {
        width: 100%; min-height: 44px; border-color: var(--sv-line); border-radius: var(--sv-radius-control);
        background-color: var(--sv-surface); color: var(--sv-content); font-size: .78rem;
    }
    .sv-search { padding-left: 2.25rem; }
    .sv-control:focus {
        border-color: var(--sv-brand); box-shadow: 0 0 0 .2rem color-mix(in srgb, var(--sv-brand) 15%, transparent);
    }
    .sv-control:disabled { background: var(--sv-soft); color: var(--sv-muted); cursor: not-allowed; }
    .sv-feedback {
        display: none; margin-bottom: .75rem; padding: .65rem .75rem;
        border: 1px solid color-mix(in srgb, var(--sv-danger) 28%, var(--sv-line));
        border-radius: var(--sv-radius-control); background: color-mix(in srgb, var(--sv-danger) 7%, var(--sv-surface));
        color: var(--sv-danger); font-size: .74rem;
    }
    .sv-feedback.is-visible { display: block; }
    .sv-panel { overflow: hidden; }
    .sv-panel-head { display: grid; gap: .7rem; padding: .9rem; border-bottom: 1px solid var(--sv-line); background: var(--sv-surface); }
    .sv-panel-title-row { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; min-width: 0; }
    .sv-panel-title-row > div { min-width: 0; }
    .sv-store-picker-row { display: block; padding-top: .15rem; }
    .sv-store-picker { display: grid; gap: .55rem; min-width: 0; }
    .sv-store-platform-tabs { display: flex; gap: .25rem; max-width: 100%; padding: .2rem; overflow-x: auto; border: 1px solid var(--sv-line); border-radius: 10px; background: var(--sv-soft); scrollbar-width: none; }
    .sv-store-platform-tabs::-webkit-scrollbar { display: none; }
    .sv-store-platform-tab {
        display: inline-flex; flex: 0 0 auto; align-items: center; gap: .35rem; min-height: 32px; padding: .35rem .6rem;
        border: 1px solid transparent; border-radius: 8px; background: transparent; color: var(--sv-muted); cursor: pointer; font-size: .7rem; font-weight: 800; text-decoration: none; white-space: nowrap;
    }
    .sv-store-platform-tab:hover { color: var(--sv-brand-strong); }
    .sv-store-platform-tab.active { border-color: color-mix(in srgb, var(--sv-brand) 18%, var(--sv-line)); background: var(--sv-surface); box-shadow: 0 1px 2px rgba(16, 24, 40, .06); color: var(--sv-brand-strong); }
    .sv-store-platform-tab:focus-visible { outline: 3px solid color-mix(in srgb, var(--sv-brand) 25%, transparent); outline-offset: 1px; }
    .sv-store-platform-count { min-width: 19px; padding: .12rem .32rem; border-radius: 999px; background: var(--sv-soft); color: var(--sv-muted); font-size: .62rem; text-align: center; }
    .sv-store-platform-tab.active .sv-store-platform-count { background: var(--sv-brand); color: #fff; }
    .sv-store-groups { display: grid; gap: .45rem; min-width: 0; }
    .sv-store-group { display: flex; flex-wrap: wrap; align-items: center; gap: .3rem .45rem; min-width: 0; }
    .sv-store-group-label {
        display: inline-flex; align-items: center; gap: .3rem; color: var(--sv-muted);
        font-size: .6rem; font-weight: 800; letter-spacing: .025em; text-transform: uppercase; white-space: nowrap;
    }
    .sv-store-group-label i { color: var(--sv-brand); }
    .sv-store-tab-list { display: flex; flex-wrap: wrap; gap: .25rem; min-width: 0; }
    .sv-store-empty { padding: .35rem .1rem 0; color: var(--sv-muted); font-size: .7rem; }
    @media (max-width: 767.98px) {
        .sv-control { font-size: 16px; }
        .sv-store-group { width: 100%; padding-left: 0; border-left: 0; }
        .sv-store-tab-list { flex-wrap: nowrap; max-width: 100%; overflow-x: auto; padding-bottom: .15rem; scrollbar-width: none; }
        .sv-store-tab-list::-webkit-scrollbar { display: none; }
    }
    .sv-panel-summary {
        display: flex; flex-wrap: wrap; align-items: center; gap: .2rem .55rem; padding: .45rem .6rem;
        border: 0; border-radius: 10px;
        background: var(--sv-soft); color: var(--sv-muted); font-size: .68rem; line-height: 1.35;
    }
    .sv-summary-item {
        display: inline-flex; align-items: center; gap: .25rem; min-height: 26px;
        color: var(--sv-muted); font-weight: 700; text-decoration: none;
    }
    .sv-summary-item strong { color: var(--sv-content); font-size: .76rem; font-variant-numeric: tabular-nums; }
    a.sv-summary-item { padding: .15rem .35rem; border-radius: 999px; }
    a.sv-summary-item:hover { background: var(--sv-soft); color: var(--sv-brand-strong); }
    a.sv-summary-item:focus-visible { outline: 2px solid var(--sv-brand); outline-offset: 2px; }
    a.sv-summary-item.active { background: color-mix(in srgb, var(--sv-brand) 10%, var(--sv-surface)); color: var(--sv-brand-strong); }
    .sv-summary-item--warning strong { color: #b45309; }
    .sv-summary-item--status { color: var(--sv-muted); }
    .sv-summary-item--status i { color: var(--sv-brand); }
    .sv-scope-note { color: var(--sv-muted); font-size: .68rem; line-height: 1.35; }
    .sv-panel-title { margin: 0; color: var(--sv-content); font-size: .92rem; font-weight: 850; letter-spacing: -.015em; }
    .sv-panel-count {
        display: inline-flex; align-items: center; min-height: 26px; padding: .2rem .5rem;
        border: 1px solid var(--sv-line); border-radius: 999px; background: var(--sv-surface); color: var(--sv-muted);
        font-size: .65rem; font-weight: 750; white-space: nowrap;
    }
    .sv-list-head { display: none; }
    .sv-records { display: grid; gap: .65rem; padding: .65rem; }
    .sv-product-group {
        overflow: hidden; border: 1px solid var(--sv-line); border-radius: var(--sv-radius-card);
        background: var(--sv-surface); box-shadow: 0 1px 2px rgba(16, 24, 40, .035);
    }
    .sv-product-toggle {
        display: grid; grid-template-columns: 26px 42px minmax(0, 1fr) auto auto; align-items: center; gap: .65rem;
        width: 100%; min-height: 68px; padding: .65rem 1rem .65rem 1.2rem; border: 0; background: var(--sv-surface);
        color: var(--sv-content); text-align: left; cursor: pointer;
    }
    .sv-product-toggle:hover { background: color-mix(in srgb, var(--sv-brand) 4%, var(--sv-surface)); }
    .sv-product-toggle-index {
        display: grid; width: 24px; height: 24px; place-items: center; border: 1px solid var(--sv-line);
        border-radius: 7px; background: var(--sv-soft); color: var(--sv-muted); font-size: .64rem; font-weight: 850; font-variant-numeric: tabular-nums;
    }
    .sv-product-toggle-icon {
        position: relative; z-index: 1; display: grid; width: 42px; height: 42px; place-items: center; overflow: visible;
        border: 1px solid var(--sv-line); border-radius: var(--sv-radius-control);
        background: var(--sv-soft); color: var(--sv-muted); font-size: .9rem;
    }
    .sv-product-toggle-icon img { position: relative; width: 100%; height: 100%; border-radius: inherit; object-fit: cover; transition: transform .18s ease, box-shadow .18s ease; }
    .sv-product-toggle-copy { display: grid; gap: .12rem; min-width: 0; }
    .sv-product-toggle-id {
        overflow: hidden; color: var(--sv-muted); font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: .64rem; font-weight: 800; text-overflow: ellipsis; white-space: nowrap;
    }
    .sv-product-toggle-copy strong { overflow: hidden; color: var(--sv-content); font-size: .74rem; font-weight: 750; line-height: 1.25; text-overflow: ellipsis; white-space: nowrap; }
    .sv-product-toggle-copy small { overflow: hidden; color: var(--sv-muted); font-size: .65rem; text-overflow: ellipsis; white-space: nowrap; }
    .sv-product-toggle-count {
        display: inline-flex; align-items: center; min-height: 26px; padding: .2rem .5rem;
        border-radius: 999px; background: color-mix(in srgb, var(--sv-brand) 10%, var(--sv-surface));
        color: var(--sv-brand-strong); font-size: .65rem; font-weight: 800; white-space: nowrap;
    }
    .sv-product-toggle-chevron { color: var(--sv-muted); font-size: .8rem; transition: transform .16s ease; }
    .sv-variant-dialog {
        width: min(900px, calc(100% - 1rem)); max-height: min(84dvh, 720px); margin: auto; padding: 0; overflow: hidden;
        border: 1px solid var(--sv-line); border-radius: var(--sv-radius-card); background: var(--sv-surface) !important;
        color: var(--sv-content); opacity: 1 !important; isolation: isolate; box-shadow: 0 24px 70px rgba(16, 24, 40, .26);
    }
    .sv-variant-dialog::backdrop { background: rgba(16, 24, 40, .46); backdrop-filter: blur(2px); }
    .sv-variant-dialog-panel { display: flex; min-height: 0; max-height: inherit; flex-direction: column; }
    .sv-variant-dialog-head { display: flex; min-width: 0; flex: 0 0 auto; align-items: flex-start; justify-content: space-between; gap: .85rem; padding: .85rem 1rem; border-bottom: 1px solid var(--sv-line); background: var(--sv-surface); }
    .sv-variant-dialog-product { display: flex; min-width: 0; max-width: 100%; align-items: center; gap: .7rem; }
    .sv-variant-dialog-thumb { display: grid; width: 52px; height: 52px; flex: 0 0 auto; place-items: center; overflow: hidden; border: 1px solid var(--sv-line); border-radius: var(--sv-radius-control); background: var(--sv-soft); color: var(--sv-muted); }
    .sv-variant-dialog-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .sv-variant-dialog-product > div { min-width: 0; }
    .sv-variant-dialog-kicker { display: block; margin-bottom: .25rem; color: var(--sv-brand-strong); font-size: .62rem; font-weight: 850; letter-spacing: .07em; text-transform: uppercase; }
    .sv-variant-dialog-title { display: -webkit-box; margin: 0; overflow: hidden; color: var(--sv-content); font-size: clamp(.95rem, 2vw, 1.1rem); font-weight: 800; line-height: 1.25; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
    .sv-variant-dialog-meta { display: block; margin-top: .25rem; color: var(--sv-muted); font-size: .68rem; }
    .sv-variant-dialog-close { display: inline-grid; width: 40px; height: 40px; flex: 0 0 auto; place-items: center; border: 1px solid transparent; border-radius: 50%; background: transparent; color: var(--sv-muted); font-size: .95rem; }
    .sv-variant-dialog-close:hover { border-color: var(--sv-line); background: var(--sv-soft); color: var(--sv-content); }
    .sv-variant-dialog-close:focus-visible { outline: 3px solid color-mix(in srgb, var(--sv-brand) 28%, transparent); outline-offset: 2px; }
    .sv-variant-dialog-body { min-width: 0; min-height: 0; max-height: calc(84dvh - 6rem); overflow-x: hidden; overflow-y: auto; background: color-mix(in srgb, var(--sv-soft) 45%, var(--sv-surface)); overscroll-behavior: contain; }
    .sv-variant-dialog-body .sv-product-group-body { display: grid; gap: .45rem; padding: .6rem; }
    .sv-variant-dialog-body .sv-record {
        display: grid; grid-template-columns: minmax(0, 1fr);
        grid-template-areas: 'variant' 'support' 'metrics' 'action'; width: 100%; min-width: 0; box-sizing: border-box;
        padding: .7rem; box-shadow: 0 1px 2px rgba(16, 24, 40, .035);
    }
    .sv-variant-dialog-body .sv-cell-label { display: block; }
    .sv-variant-dialog-body .sv-support { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, .75fr); }
    .sv-variant-dialog-body .sv-metrics { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .sv-variant-dialog-body .sv-metrics > .sv-metric { grid-column: auto; grid-row: auto; min-width: 0; }
    .sv-variant-dialog-body .sv-action { padding: .65rem; }
    @media (min-width: 768px) {
        .sv-variant-dialog-body .sv-record {
            grid-template-columns: minmax(190px, 1.25fr) minmax(130px, .9fr) minmax(225px, 1.4fr) minmax(160px, auto);
            grid-template-areas: 'variant support metrics action';
            align-items: start;
        }
        .sv-variant-dialog-body .sv-record > .sv-variant-cell { grid-area: variant; grid-column: auto; grid-row: auto; }
        .sv-variant-dialog-body .sv-record > .sv-support { grid-area: support; grid-column: auto; grid-row: auto; }
        .sv-variant-dialog-body .sv-record > .sv-metrics { grid-area: metrics; grid-column: auto; grid-row: auto; }
        .sv-variant-dialog-body .sv-record > .sv-action { grid-area: action; grid-column: auto; grid-row: auto; }
        .sv-variant-dialog-body .sv-support > .sv-map-cell { grid-column: auto; grid-row: auto; }
        .sv-variant-dialog-body .sv-support { display: block; }
        .sv-variant-dialog-body .sv-metrics { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .35rem; }
        .sv-variant-dialog-body .sv-action { min-width: 160px; align-self: stretch; padding: .55rem; }
    }
    .sv-record {
        display: grid; grid-template-columns: minmax(0, 1fr);
        grid-template-areas: 'variant' 'support' 'metrics' 'action';
        gap: .7rem .75rem; padding: .75rem; border: 1px solid var(--sv-line);
        border-radius: calc(var(--sv-radius-card) - 3px); background: var(--sv-surface); box-shadow: 0 1px 2px rgba(16, 24, 40, .035);
        transition: border-color .16s ease, box-shadow .16s ease, transform .16s ease;
    }
    .sv-record:hover { border-color: color-mix(in srgb, var(--sv-brand) 28%, var(--sv-line)); box-shadow: 0 7px 18px rgba(16, 24, 40, .07); transform: translateY(-1px); }
    .sv-variant-cell { grid-area: variant; min-width: 0; }
    .sv-support { grid-area: support; display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, .75fr); gap: .65rem; }
    .sv-metrics { grid-area: metrics; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .5rem; }
    .sv-action { grid-area: action; min-width: 0; padding: .65rem; border: 1px solid color-mix(in srgb, var(--sv-brand) 14%, var(--sv-line)); border-radius: var(--sv-radius-control); background: color-mix(in srgb, var(--sv-brand) 4%, var(--sv-surface)); }
    .sv-cell-label { display: block; margin-bottom: .28rem; text-transform: uppercase; }
    @media (hover: hover) and (pointer: fine) {
        .sv-product-toggle-icon:hover img {
            z-index: 20; border-color: color-mix(in srgb, var(--sv-brand) 42%, var(--sv-line));
            box-shadow: 0 10px 24px rgba(16, 24, 40, .2); transform: scale(2.15);
        }
    }
    .sv-variant-name {
        margin-top: .22rem; overflow: hidden; color: var(--sv-content); font-size: .82rem;
        font-weight: 800; line-height: 1.3; text-overflow: ellipsis; white-space: nowrap;
    }
    .sv-sku, .sv-map-code {
        overflow-wrap: anywhere; color: var(--sv-content); font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: .68rem; font-weight: 750;
    }
    .sv-meta { margin-top: .18rem; color: var(--sv-muted); font-size: .65rem; line-height: 1.35; }
    .sv-sync { color: var(--sv-content); font-size: .7rem; font-weight: 700; }
    .sv-metric { min-width: 0; padding: .6rem; border: 1px solid color-mix(in srgb, var(--sv-line) 88%, transparent); border-radius: var(--sv-radius-control); background: color-mix(in srgb, var(--sv-soft) 78%, var(--sv-surface)); }
    .sv-mp-stock-cell { min-width: 0; }
    .sv-mp-stock {
        display: block; font-size: 1.05rem; font-weight: 850;
        letter-spacing: -.025em; font-variant-numeric: tabular-nums; line-height: 1.15;
    }
    .sv-mp-stock.is-zero { color: var(--sv-muted); }
    .sv-mp-stock.is-negative { color: var(--sv-danger); }
    .sv-mp-stock.is-available { color: var(--sv-brand-strong); }
    .sv-internal, .sv-average {
        display: block; color: var(--sv-content); font-size: 1.05rem; font-weight: 850;
        letter-spacing: -.025em; font-variant-numeric: tabular-nums; line-height: 1.15;
    }
    .sv-internal { color: var(--sv-success); }
    .sv-average small {
        display: block; margin-top: .2rem; color: var(--sv-muted);
        font-size: .6rem; font-weight: 600; letter-spacing: 0;
    }
    .sv-stock-form { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: .5rem; }
    .sv-stock-input {
        width: 100%; min-width: 0; min-height: 44px; border: 1px solid var(--sv-line);
        border-radius: var(--sv-radius-control); padding: .45rem .65rem; background: var(--sv-surface);
        color: var(--sv-content); font-size: 16px; font-weight: 800; font-variant-numeric: tabular-nums;
    }
    .sv-stock-input:focus {
        border-color: var(--sv-brand); box-shadow: 0 0 0 .2rem color-mix(in srgb, var(--sv-brand) 15%, transparent); outline: 0;
    }
    .sv-save {
        display: inline-flex; align-items: center; justify-content: center; gap: .35rem; min-height: 44px;
        border: 1px solid var(--sv-brand); border-radius: var(--sv-radius-control); padding: .5rem .85rem;
        background: var(--sv-brand); color: #fff; font-size: .73rem; font-weight: 800;
    }
    .sv-save :where(i, span) { color: inherit; }
    .sv-save:hover { border-color: var(--sv-brand-strong); background: var(--sv-brand-strong); }
    .sv-save:disabled { cursor: wait; opacity: .62; }
    .sv-status { display: block; min-height: 1rem; margin-top: .32rem; color: var(--sv-muted); font-size: .64rem; }
    .sv-status.error { color: var(--sv-danger); }
    .sv-status.ok { color: var(--sv-success); }
    .sv-save-feedback {
        position: fixed; z-index: 1080; right: max(1rem, env(safe-area-inset-right)); bottom: max(1rem, env(safe-area-inset-bottom));
        display: flex; width: min(360px, calc(100vw - 2rem)); align-items: center; gap: .55rem;
        padding: .75rem .85rem; border: 1px solid var(--sv-line); border-radius: var(--sv-radius-control);
        background: var(--sv-surface); box-shadow: 0 14px 32px rgba(16, 24, 40, .16); color: var(--sv-content);
        font-size: .74rem; font-weight: 750; line-height: 1.4; opacity: 0; pointer-events: none;
        transform: translateY(.5rem); transition: opacity .16s ease, transform .16s ease;
    }
    .sv-save-feedback.is-visible { opacity: 1; transform: translateY(0); }
    .sv-save-feedback.success { border-color: color-mix(in srgb, var(--sv-success) 35%, var(--sv-line)); }
    .sv-save-feedback.error { border-color: color-mix(in srgb, var(--sv-danger) 35%, var(--sv-line)); }
    .sv-save-feedback i { font-size: 1rem; }
    .sv-save-feedback.success i { color: var(--sv-success); }
    .sv-save-feedback.error i { color: var(--sv-danger); }
    .sv-confirm-dialog {
        width: min(440px, calc(100% - 1.5rem)); max-height: min(720px, calc(100dvh - 2rem)); margin: auto; padding: 0; overflow: hidden;
        border: 1px solid var(--sv-line); border-radius: var(--sv-radius-card);
        /* Keep the dialog surface above the dimmed backdrop; native dialog styles can otherwise bleed through. */
        background: var(--sv-surface, #fff) !important; background-color: var(--sv-surface, #fff) !important;
        opacity: 1 !important; isolation: isolate;
        box-shadow: 0 24px 70px rgba(16, 24, 40, .26); color: var(--sv-content);
    }
    .sv-confirm-dialog > form {
        display: flex; min-height: 0; max-height: inherit; flex-direction: column;
        background: var(--sv-surface, #fff); opacity: 1;
    }
    .sv-confirm-dialog::backdrop { background: rgba(16, 24, 40, .46); backdrop-filter: blur(2px); }
    .sv-confirm-head { display: flex; flex: 0 0 auto; align-items: flex-start; justify-content: space-between; gap: .75rem; padding: 1.15rem 1.25rem .85rem; }
    .sv-confirm-kicker { display: flex; align-items: center; gap: .45rem; color: var(--sv-brand-strong); font-size: .65rem; font-weight: 850; letter-spacing: .07em; text-transform: uppercase; }
    .sv-confirm-kicker-icon { display: inline-grid; width: 1.65rem; height: 1.65rem; flex: 0 0 auto; place-items: center; border-radius: .55rem; background: color-mix(in srgb, var(--sv-brand) 11%, var(--sv-surface)); color: var(--sv-brand); font-size: .78rem; }
    .sv-confirm-title { margin: .35rem 0 0; color: var(--sv-content); font-size: clamp(1rem, 3vw, 1.12rem); font-weight: 850; letter-spacing: -.025em; line-height: 1.2; }
    .sv-confirm-close {
        display: inline-grid; width: 44px; height: 44px; flex: 0 0 auto; place-items: center; border: 1px solid transparent;
        border-radius: 50%; background: transparent; color: var(--sv-muted); font-size: 1rem;
    }
    .sv-confirm-close:hover { border-color: var(--sv-line); background: var(--sv-soft); color: var(--sv-content); }
    .sv-confirm-close:focus-visible, .sv-confirm-button:focus-visible { outline: 3px solid color-mix(in srgb, var(--sv-brand) 28%, transparent); outline-offset: 2px; }
    .sv-confirm-body { min-height: 0; overflow-y: auto; padding: .1rem 1.25rem 1.25rem; overscroll-behavior: contain; }
    .sv-confirm-copy { margin: 0; color: var(--sv-muted); font-size: .76rem; line-height: 1.5; }
    .sv-confirm-detail { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.4fr); gap: .7rem 1rem; margin: 1rem 0 0; padding: .85rem; border: 1px solid color-mix(in srgb, var(--sv-line) 80%, transparent); border-radius: var(--sv-radius-control); background: var(--sv-soft); }
    .sv-confirm-detail dt { color: var(--sv-muted); font-size: .64rem; font-weight: 750; }
    .sv-confirm-detail dd { min-width: 0; margin: 0; overflow: hidden; color: var(--sv-content); font-size: .72rem; font-weight: 800; text-align: right; text-overflow: ellipsis; white-space: nowrap; }
    .sv-confirm-stock { color: var(--sv-success) !important; font-variant-numeric: tabular-nums; }
    .sv-confirm-actions { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; flex: 0 0 auto; padding: .9rem 1.25rem max(1rem, env(safe-area-inset-bottom)); border-top: 1px solid var(--sv-line); background: var(--sv-surface); }
    .sv-confirm-button { min-height: 44px; border: 1px solid var(--sv-line); border-radius: var(--sv-radius-control); background: var(--sv-surface); color: var(--sv-content); font-size: .75rem; font-weight: 800; }
    .sv-confirm-button :where(i, span) { color: inherit; }
    .sv-confirm-button:hover { border-color: color-mix(in srgb, var(--sv-brand) 26%, var(--sv-line)); background: var(--sv-soft); }
    .sv-confirm-button--primary { border-color: var(--sv-brand); background: var(--sv-brand); color: #fff; }
    .sv-confirm-button--primary:hover { border-color: var(--sv-brand-strong); background: var(--sv-brand-strong); }
    .sv-empty {
        display: grid; justify-items: center; gap: .35rem; padding: 3.5rem 1rem;
        color: var(--sv-muted); text-align: center;
    }
    .sv-empty-icon {
        display: grid; width: 44px; height: 44px; place-items: center; margin-bottom: .2rem;
        border-radius: 50%; background: color-mix(in srgb, var(--sv-success) 11%, var(--sv-surface));
        color: var(--sv-success); font-size: 1.15rem;
    }
    .sv-empty strong { color: var(--sv-content); font-size: .82rem; }
    .sv-empty span { max-width: 420px; font-size: .72rem; line-height: 1.5; }
    .sv-pages { padding: .75rem; border-top: 1px solid var(--sv-line); }
    @keyframes sv-loading { from { background-position: -80% 0; } to { background-position: 180% 0; } }
    @media (min-width: 576px) {
        .sv-back { min-height: 38px; }
        .sv-filter { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .sv-search-field { grid-column: 1 / -1; }
    }
    @media (min-width: 768px) {
        .sv-page { padding-top: 1.25rem; }
        .sv-filter { grid-template-columns: minmax(200px, 1.7fr) repeat(4, minmax(125px, 1fr)); align-items: end; }
        .sv-search-field { grid-column: auto; }
        .sv-records { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (min-width: 992px) {
        .sv-panel-head { grid-template-columns: minmax(190px, .62fr) minmax(0, 1.38fr); align-items: center; }
        .sv-panel-title-row { grid-column: 1; }
        .sv-scope-nav { grid-column: 2; }
        .sv-filter { grid-column: 1 / -1; }
        .sv-store-picker-row, .sv-panel-summary { grid-column: 1 / -1; }
    }
    @media (min-width: 1120px) {
        .sv-panel-head { padding: .75rem 1rem; }
        .sv-list-head, .sv-record {
            grid-template-columns: minmax(170px, 1.4fr) minmax(130px, 1fr) 96px 112px 108px minmax(180px, 1.1fr) 100px;
            gap: 0;
        }
        .sv-list-head {
            display: grid; align-items: center; padding: .55rem .75rem;
            border-bottom: 1px solid var(--sv-line);
            background: color-mix(in srgb, var(--sv-soft) 68%, var(--sv-surface));
        }
        .sv-list-head span {
            padding-inline: .55rem; color: var(--sv-muted); font-size: .63rem;
            font-weight: 800; letter-spacing: .035em; text-transform: uppercase;
        }
        .sv-records { display: block; padding: 0; }
        .sv-product-group { border-inline: 0; border-radius: 0; box-shadow: none; }
        .sv-product-group + .sv-product-group { border-top: 1px solid var(--sv-line); }
        .sv-product-toggle { padding-inline: 1rem; padding-left: 1.2rem; }
        .sv-product-group-body { display: block; padding: 0; }
        .sv-record {
            grid-template-areas: none; align-items: center; border: 0; border-bottom: 1px solid var(--sv-line);
            border-radius: 0; padding: .65rem .75rem;
        }
        .sv-record:last-child { border-bottom: 0; }
        .sv-record:hover { background: color-mix(in srgb, var(--sv-brand) 3%, var(--sv-surface)); box-shadow: none; transform: none; }
        .sv-variant-cell, .sv-map-cell, .sv-mp-stock-cell, .sv-internal-cell,
        .sv-average-cell, .sv-action, .sv-sync-cell { grid-area: auto; min-width: 0; padding-inline: .55rem; }
        .sv-variant-cell { grid-column: 1; }
        .sv-map-cell { grid-column: 2; }
        .sv-mp-stock-cell { grid-column: 3; }
        .sv-internal-cell { grid-column: 4; }
        .sv-average-cell { grid-column: 5; }
        .sv-action { grid-column: 6; }
        .sv-sync-cell { grid-column: 7; }
        .sv-variant-cell, .sv-map-cell, .sv-mp-stock-cell, .sv-internal-cell,
        .sv-average-cell, .sv-action, .sv-sync-cell { grid-row: 1; }
        .sv-support, .sv-metrics { display: contents; }
        .sv-cell-label { display: none; }
        .sv-metric { padding: 0; border: 0; background: transparent; }
        .sv-action { padding-block: 0; border: 0; border-radius: 0; background: transparent; }
        .sv-stock-form { grid-template-columns: 74px auto; }
        .sv-stock-input { min-height: 38px; font-size: .78rem; }
        .sv-save { min-height: 38px; padding-inline: .7rem; }
    }
    @media (max-width: 575.98px) {
        .sv-page { padding-inline: .65rem; }
        .sv-head { align-items: center; gap: .65rem; }
        .sv-eyebrow { margin-bottom: .2rem; font-size: .6rem; }
        .sv-title { font-size: 1.22rem; }
        .sv-back { min-width: 40px; width: 40px; padding-inline: 0; }
        .sv-back-label { display: none; }
        .sv-filter { padding: .65rem; }
        .sv-scope-nav { flex-wrap: nowrap; overflow-x: auto; scrollbar-width: none; }
        .sv-scope-nav::-webkit-scrollbar { display: none; }
        .sv-scope-btn { flex: 0 0 auto; }
        .sv-panel-head { padding: .7rem; }
        .sv-panel-title { font-size: .86rem; }
        .sv-panel-summary { gap: .15rem .45rem; }
        .sv-variant-dialog { width: calc(100% - .75rem); max-height: calc(100dvh - .75rem); border-radius: 18px; }
        .sv-variant-dialog-head { padding: .85rem .9rem; }
        .sv-variant-dialog-thumb { width: 44px; height: 44px; }
        .sv-variant-dialog-body .sv-product-group-body { padding: .5rem; }
        .sv-confirm-dialog { width: calc(100% - 1rem); max-height: calc(100dvh - 1rem); border-radius: 18px; }
        .sv-confirm-head { padding: 1rem 1rem .75rem; }
        .sv-confirm-body { padding-inline: 1rem; }
        .sv-confirm-detail { grid-template-columns: 1fr; gap: .2rem; }
        .sv-confirm-detail dt:not(:first-child) { margin-top: .55rem; }
        .sv-confirm-detail dd { text-align: left; white-space: normal; overflow-wrap: anywhere; }
        .sv-confirm-actions { grid-template-columns: 1fr; padding-inline: 1rem; }
        .sv-confirm-button--primary { order: -1; }
    }
    @media (prefers-reduced-motion: reduce) {
        .sv-dashboard-content, .sv-record, .sv-save-feedback, .sv-product-toggle-icon img { transition: none; }
        .sv-dashboard-content.is-loading::before { animation: none; }
    }
</style>
@endpush

@section('content')
@php
    $baseFilters = array_filter([
        'store_id' => $storeId ?: null,
        'channel' => $channel ?: null,
        'search' => $search ?: null,
        'sales_scope' => $salesScope !== 'best_sellers' ? $salesScope : null,
        'min_avg_sales' => $minAverageSales,
        'stock_scope' => $stockScope !== 'all' ? $stockScope : null,
    ], fn ($value) => $value !== null && $value !== '');
    $storeSharedFilters = array_filter([
        'stock_scope' => $stockScope !== 'all' ? $stockScope : null,
        'stock_status' => $stockStatus ?: null,
        'search' => $search ?: null,
        'sales_scope' => $salesScope !== 'best_sellers' ? $salesScope : null,
        'min_avg_sales' => $minAverageSales,
    ], fn ($value) => $value !== null && $value !== '');
    $emptyScopeTotal = $summary['empty_total'] ?? $summary['total'];
    $internalEmptyTotal = $summary['internal_empty_mp_stock'] ?? 0;
    $mappedTotal = $summary['mapped_total'] ?? $summary['total'];
    $storesByChannel = $stores->groupBy(function ($store): string {
        $channelCode = strtolower((string) $store->channel?->code);
        return in_array($channelCode, ['tiktok', 'ttk', 'tt'], true) ? 'tiktok' : 'shopee';
    });
    $storeGroups = [
        ['key' => 'tiktok', 'label' => 'TikTok Shop', 'icon' => 'bi-music-note-beamed', 'stores' => $storesByChannel->get('tiktok', collect())],
        ['key' => 'shopee', 'label' => 'Shopee', 'icon' => 'bi-bag', 'stores' => $storesByChannel->get('shopee', collect())],
    ];
    $selectedStore = $storeId > 0 ? $stores->firstWhere('id', $storeId) : null;
    $selectedStoreChannel = strtolower((string) $selectedStore?->channel?->code);
    $activeStorePlatform = in_array($selectedStoreChannel, ['tiktok', 'ttk', 'tt'], true)
        ? 'tiktok'
        : (in_array($selectedStoreChannel, ['shopee', 'shp'], true)
            ? 'shopee'
            : (in_array($channel, ['tiktok', 'shopee'], true) ? $channel : 'all'));
@endphp

<main class="sv-page">
    <header class="sv-head">
        <div>
            <span class="sv-eyebrow"><i class="bi bi-box-seam"></i> Inventory Marketplace</span>
            <h1 class="sv-title">Stok Variant Kosong</h1>
            <p class="sv-copy">Tinjau seluruh variant ter-mapping, lalu gunakan filter untuk memprioritaskan pembaruan stok.</p>
        </div>
        <a class="sv-back" href="{{ route('marketplace.products') }}" aria-label="Kembali ke produk"><i class="bi bi-arrow-left"></i><span class="sv-back-label">Kembali</span></a>
    </header>

    <div class="sv-feedback" id="svFilterFeedback" role="alert" aria-live="assertive"></div>

    <div class="sv-dashboard-content" id="svDashboardContent">
        <section class="sv-panel" aria-live="polite" aria-label="Daftar variant stok kosong">
            <div class="sv-panel-head">
                <div class="sv-panel-title-row">
                    <div>
                        <h2 class="sv-panel-title">{{ $stockScope === 'all' ? 'Semua variant' : ($stockScope === 'internal_empty_marketplace_stock' ? 'Stok tidak sinkron' : 'Marketplace kosong') }}</h2>
                    </div>
                    <span class="sv-panel-count">{{ number_format($variants->count()) }} variant</span>
                </div>

                <nav class="sv-scope-nav" aria-label="Mode pemeriksaan stok">
                    <a class="sv-scope-btn {{ $stockScope === 'marketplace_empty' ? 'active' : '' }}"
                       aria-current="{{ $stockScope === 'marketplace_empty' ? 'page' : 'false' }}"
                       data-dashboard-link
                       href="{{ route('marketplace.products.empty-variants', array_merge($baseFilters, ['stock_scope' => 'marketplace_empty', 'stock_status' => 'zero'])) }}">
                        <i class="bi bi-box-seam"></i>
                        <span>Marketplace kosong</span>
                        <span class="sv-badge" data-summary="scope_empty">{{ number_format($emptyScopeTotal) }}</span>
                    </a>
                    <a class="sv-scope-btn sv-scope-btn--warning {{ $stockScope === 'internal_empty_marketplace_stock' ? 'active' : '' }}"
                       aria-current="{{ $stockScope === 'internal_empty_marketplace_stock' ? 'page' : 'false' }}"
                       data-dashboard-link
                       href="{{ route('marketplace.products.empty-variants', array_merge($baseFilters, ['stock_scope' => 'internal_empty_marketplace_stock', 'stock_status' => null])) }}">
                        <i class="bi bi-exclamation-triangle"></i>
                        <span>Stok tidak sinkron</span>
                        <span class="sv-badge sv-badge--warning" data-summary="scope_internal_empty">{{ number_format($internalEmptyTotal) }}</span>
                    </a>
                </nav>

                <form class="sv-filter" method="GET" action="{{ route('marketplace.products.empty-variants') }}" data-realtime-filter>
                    @if($storeId > 0)<input type="hidden" name="store_id" value="{{ $storeId }}">@endif
                    <div class="sv-field sv-search-field">
                        <label class="sv-field-label" for="svSearch">Cari variant / SKU</label>
                        <div class="sv-search-wrap"><i class="bi bi-search"></i><input class="form-control sv-control sv-search" id="svSearch" name="search" value="{{ $search }}" maxlength="100" autocomplete="off" placeholder="Nama, SKU, atau ID produk"></div>
                    </div>
                    <div class="sv-field">
                        <label class="sv-field-label" for="svChannel">Channel</label>
                        <select class="form-select sv-control" id="svChannel" name="channel"><option value="">Semua channel</option><option value="shopee" @selected($channel === 'shopee')>Shopee</option><option value="tiktok" @selected($channel === 'tiktok')>TikTok Shop</option></select>
                    </div>
                    <div class="sv-field">
                        <label class="sv-field-label" for="svSales">Penjualan</label>
                        <select class="form-select sv-control" id="svSales" name="sales_scope">
                            <option value="best_sellers" @selected($salesScope === 'best_sellers')>Terlaris</option>
                            <option value="all" @selected($salesScope === 'all')>Semua produk</option>
                        </select>
                    </div>
                    <div class="sv-field">
                        <label class="sv-field-label" for="svStock">Stok MP</label>
                        <select class="form-select sv-control" id="svStock" name="stock_status" @disabled($stockScope !== 'marketplace_empty')><option value="zero" @selected($stockStatus === 'zero')>Stok 0</option><option value="negative" @selected($stockStatus === 'negative')>Stok minus</option></select>
                    </div>
                    <div class="sv-field">
                        <label class="sv-field-label" for="svAverage">Terjual / hari &gt;</label>
                        <input class="form-control sv-control" id="svAverage" name="min_avg_sales" type="number" min="0" max="1000000" step="0.1" value="{{ $minAverageSales }}" inputmode="decimal" placeholder="Semua">
                    </div>
                </form>

                <div class="sv-store-picker-row">
                    <div class="sv-store-picker" data-store-picker data-active-platform="{{ $activeStorePlatform }}">
                        <div class="sv-store-platform-tabs" role="group" aria-label="Filter platform toko">
                            @foreach($storeGroups as $group)
                                @continue($group['stores']->isEmpty())
                                @php
                                    $platformFilters = array_merge($storeSharedFilters, ['channel' => $group['key']]);
                                @endphp
                                <button type="button" class="sv-store-platform-tab {{ $activeStorePlatform === $group['key'] ? 'active' : '' }}" data-store-platform="{{ $group['key'] }}" data-platform-url="{{ route('marketplace.products.empty-variants', $platformFilters) }}" aria-pressed="{{ $activeStorePlatform === $group['key'] ? 'true' : 'false' }}">
                                    <i class="bi {{ $group['icon'] }}"></i><span>{{ $group['label'] }}</span><span class="sv-store-platform-count">{{ $group['stores']->count() }}</span>
                                </button>
                            @endforeach
                        </div>

                        <div class="sv-store-groups" role="group" aria-label="Pilih toko marketplace">
                            @foreach($storeGroups as $group)
                                @continue($group['stores']->isEmpty())
                                <section class="sv-store-group" data-store-group data-store-channel="{{ $group['key'] }}" aria-labelledby="svStoreGroup{{ ucfirst($group['key']) }}">
                                    <span class="sv-store-group-label" id="svStoreGroup{{ ucfirst($group['key']) }}"><i class="bi {{ $group['icon'] }}"></i>{{ $group['label'] }}</span>
                                    <nav class="sv-store-tab-list" aria-label="Toko {{ $group['label'] }}">
                                        @foreach($group['stores'] as $store)
                                            <a class="sv-store-tab {{ $storeId === $store->id ? 'active' : '' }}" aria-current="{{ $storeId === $store->id ? 'page' : 'false' }}" data-dashboard-link data-store-link href="{{ route('marketplace.products.empty-variants', array_merge($storeSharedFilters, ['store_id' => $store->id])) }}"><i class="bi bi-shop"></i>{{ $store->name }}</a>
                                        @endforeach
                                    </nav>
                                </section>
                            @endforeach
                        </div>
                        <span class="sv-store-empty" data-store-empty hidden>Belum ada toko terhubung.</span>
                    </div>
                </div>

                <nav class="sv-panel-summary" aria-label="Ringkasan dan filter stok">
                    @if($stockScope === 'internal_empty_marketplace_stock')
                        <span class="sv-summary-item sv-summary-item--warning"><strong data-summary="internal_empty">{{ number_format($internalEmptyTotal) }}</strong> variant</span>
                        <span class="sv-summary-item"><strong>{{ number_format($summary['products']) }}</strong> produk</span>
                        <span class="sv-summary-item sv-summary-item--status"><i class="bi bi-info-circle"></i>Marketplace masih ada stok</span>
                    @elseif($stockScope === 'marketplace_empty')
                        <span class="sv-summary-item"><strong data-summary="total">{{ number_format($emptyScopeTotal) }}</strong> variant</span>
                        <span class="sv-summary-item"><strong>{{ number_format($summary['products']) }}</strong> produk</span>
                        <a class="sv-summary-item {{ $stockStatus === 'zero' ? 'active' : '' }}" data-dashboard-link href="{{ route('marketplace.products.empty-variants', array_merge($baseFilters, ['stock_status' => 'zero'])) }}">Stok 0 <strong data-summary="zero">{{ number_format($summary['zero']) }}</strong></a>
                        <a class="sv-summary-item {{ $stockStatus === 'negative' ? 'active' : '' }}" data-dashboard-link href="{{ route('marketplace.products.empty-variants', array_merge($baseFilters, ['stock_status' => 'negative'])) }}">Minus <strong data-summary="negative">{{ number_format($summary['negative']) }}</strong></a>
                    @else
                        <span class="sv-summary-item"><strong data-summary="total">{{ number_format($mappedTotal) }}</strong> variant</span>
                        <span class="sv-summary-item"><strong>{{ number_format($summary['products']) }}</strong> produk</span>
                    @endif
                </nav>
            </div>
            <div class="sv-records" id="svRows">
                @php
                    $variantGroups = $variants->getCollection()->groupBy(fn ($variant) => $variant->product?->id ?: 'variant-'.$variant->id);
                @endphp
                @if($variantGroups->isEmpty())
                    <div class="sv-empty" data-empty-row>
                        <span class="sv-empty-icon"><i class="bi bi-check2"></i></span>
                        <strong>Tidak ada variant ditemukan</strong>
                        <span>Tidak ada stok kosong yang cocok dengan toko dan filter aktif.</span>
                    </div>
                @else
                    @foreach($variantGroups as $groupKey => $groupVariants)
                        @php
                            $groupProduct = $groupVariants->first()->product;
                            $groupId = 'sv-product-group-'.preg_replace('/[^a-zA-Z0-9_-]/', '-', (string) $groupKey);
                        @endphp
                        <section class="sv-product-group" data-product-group data-product-id="{{ $groupProduct?->id }}">
                            <button class="sv-product-toggle" type="button" data-variant-dialog-trigger data-dialog-target="{{ $groupId }}" aria-haspopup="dialog" aria-controls="{{ $groupId }}">
                                <span class="sv-product-toggle-index" aria-hidden="true">{{ $loop->iteration }}</span>
                                <span class="sv-product-toggle-icon" aria-hidden="true">
                                    @if($groupProduct?->image_url)
                                        <img src="{{ $groupProduct->image_url }}" alt="" loading="lazy">
                                    @else
                                        <i class="bi bi-image"></i>
                                    @endif
                                </span>
                                <span class="sv-product-toggle-copy">
                                    <span class="sv-product-toggle-id">ID {{ $groupProduct?->item_id ?: '—' }}</span>
                                    <strong>{{ $groupProduct?->item_name ?: 'Produk tanpa nama' }}</strong>
                                    <small>{{ $groupProduct?->store?->name ?: 'Toko tidak ditemukan' }}</small>
                                </span>
                                <span class="sv-product-toggle-count">{{ number_format($groupVariants->count()) }} variant</span>
                                <i class="bi bi-arrow-up-right sv-product-toggle-chevron" aria-hidden="true"></i>
                            </button>
                            <dialog class="sv-variant-dialog" id="{{ $groupId }}" data-variant-dialog aria-labelledby="{{ $groupId }}-title">
                                <div class="sv-variant-dialog-panel">
                                    <header class="sv-variant-dialog-head">
                                        <div class="sv-variant-dialog-product">
                                            <span class="sv-variant-dialog-thumb" aria-hidden="true">
                                                @if($groupProduct?->image_url)
                                                    <img src="{{ $groupProduct->image_url }}" alt="" loading="lazy">
                                                @else
                                                    <i class="bi bi-image"></i>
                                                @endif
                                            </span>
                                            <div>
                                                <span class="sv-variant-dialog-kicker">Detail produk</span>
                                                <h2 class="sv-variant-dialog-title" id="{{ $groupId }}-title">{{ $groupProduct?->item_name ?: 'Produk tanpa nama' }}</h2>
                                                <span class="sv-variant-dialog-meta">ID {{ $groupProduct?->item_id ?: '—' }} · {{ $groupProduct?->store?->name ?: 'Toko tidak ditemukan' }}</span>
                                            </div>
                                        </div>
                                        <button type="button" class="sv-variant-dialog-close" data-dialog-close aria-label="Tutup detail produk"><i class="bi bi-x-lg"></i></button>
                                    </header>
                                    <div class="sv-variant-dialog-body">
                                        <div class="sv-product-group-body">
                                @foreach($groupVariants as $variant)
                    @php
                        $product = $variant->product;
                        $mapping = $variant->mapping_detail;
                        $available = max(0, (int) floor($mapping['internal_available'] ?? 0));
                        $averageWindow = max(1, (int) ($mapping['avg_daily_sales_window'] ?? 30));
                    @endphp
                    <article class="sv-record" data-stock-row data-stock-scope="{{ $stockScope }}" data-current-stock="{{ $variant->stock }}">
                        <div class="sv-variant-cell" title="{{ $product?->item_name ?: 'Produk tanpa nama' }}">
                            <span class="sv-cell-label">Variant</span>
                            <div class="sv-variant-name">{{ $variant->model_name ?: 'Variant tanpa nama' }}</div>
                            <div class="sv-sku">{{ $variant->model_sku }}</div>
                        </div>
                        <div class="sv-support">
                            <div class="sv-map-cell" title="{{ $mapping['item_name'] ?? 'Item internal' }}">
                                <span class="sv-cell-label">Mapping</span>
                                <div class="sv-map-code">{{ $mapping['item_code'] ?? '—' }}</div>
                            </div>
                        </div>
                        <div class="sv-metrics">
                            <div class="sv-metric sv-mp-stock-cell">
                                <span class="sv-cell-label">MP</span>
                                <span class="sv-mp-stock @if($variant->stock < 0) is-negative @elseif($variant->stock === 0) is-zero @else is-available @endif" data-mp-stock-val>
                                    {{ number_format($variant->stock) }}
                                </span>
                            </div>
                            <div class="sv-metric sv-internal-cell">
                                <span class="sv-cell-label">Internal</span>
                                <span class="sv-internal">{{ number_format($available, 0, ',', '.') }}</span>
                                <div class="sv-meta">Fisik {{ number_format($mapping['internal_physical'] ?? 0, 0, ',', '.') }} · dialokasi {{ number_format($mapping['internal_allocated'] ?? 0, 0, ',', '.') }}</div>
                            </div>
                            <div class="sv-metric sv-average-cell">
                                <span class="sv-cell-label">Terjual / hari</span>
                                <span class="sv-average">{{ number_format((float) $variant->average_daily_sales, 1, ',', '.') }}<small>Inventory · {{ $averageWindow }} hari</small></span>
                            </div>
                        </div>
                        <div class="sv-action">
                            <span class="sv-cell-label">Update Stok</span>
                            <form class="sv-stock-form" data-stock-form data-product-id="{{ $product?->id }}" data-model-id="{{ $variant->model_id }}" data-variant-name="{{ $variant->model_name ?: $variant->model_sku }}" data-store-name="{{ $product?->store?->name ?: 'Marketplace' }}">
                                <label class="visually-hidden" for="stock-{{ $variant->id }}">Update stok {{ $variant->model_name }}</label>
                                <input class="sv-stock-input" id="stock-{{ $variant->id }}" name="stock" type="number" min="0" step="1" value="{{ $stockScope === 'internal_empty_marketplace_stock' ? 0 : $available }}" required inputmode="numeric">
                                <button class="sv-save" type="submit" title="Kirim perubahan stok ke marketplace"><i class="bi bi-cloud-arrow-up"></i><span>Simpan</span></button>
                            </form>
                            <span class="sv-status" data-stock-status aria-live="polite"></span>
                        </div>
                                </article>
                                @endforeach
                                        </div>
                                    </div>
                                </div>
                            </dialog>
                        </section>
                    @endforeach
                @endif
            </div>
            @if($variants->hasPages())<div class="sv-pages">{{ $variants->links() }}</div>@endif
        </section>
    </div>
<dialog class="sv-confirm-dialog" id="svStockConfirm" aria-labelledby="svStockConfirmTitle" aria-describedby="svStockConfirmCopy">
    <form method="dialog">
        <div class="sv-confirm-head">
            <div>
                <span class="sv-confirm-kicker"><span class="sv-confirm-kicker-icon" aria-hidden="true"><i class="bi bi-cloud-arrow-up"></i></span> Konfirmasi pembaruan</span>
                <h2 class="sv-confirm-title" id="svStockConfirmTitle">Simpan stok ke marketplace?</h2>
            </div>
            <button class="sv-confirm-close" type="submit" value="cancel" aria-label="Batalkan pembaruan stok"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="sv-confirm-body">
            <p class="sv-confirm-copy" id="svStockConfirmCopy">Stok marketplace akan diperbarui sesuai nilai berikut.</p>
            <dl class="sv-confirm-detail">
                <dt>Variant</dt><dd id="svConfirmVariant">—</dd>
                <dt>Toko</dt><dd id="svConfirmStore">—</dd>
                <dt>Stok baru</dt><dd class="sv-confirm-stock" id="svConfirmStock">0</dd>
            </dl>
        </div>
        <div class="sv-confirm-actions">
            <button class="sv-confirm-button" type="submit" value="cancel">Batal</button>
            <button class="sv-confirm-button sv-confirm-button--primary" type="submit" value="confirm"><i class="bi bi-check2"></i> Simpan stok</button>
        </div>
    </form>
</dialog>
<div class="sv-save-feedback" id="svSaveFeedback" role="status" aria-live="polite" aria-atomic="true"></div>
</main>
@endsection

@push('scripts')
<script>
(() => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let filterTimer = null;
    let activeRequest = null;
    let saveFeedbackTimer = null;
    let pendingConfirmation = null;
    const stockConfirmDialog = document.getElementById('svStockConfirm');
    const setCount = (name, delta) => {
        const element = document.querySelector(`[data-summary="${name}"]`);
        if (!element) return;
        const value = Number(String(element.textContent).replace(/\D/g, '')) || 0;
        element.textContent = Math.max(0, value + delta).toLocaleString('id-ID');
    };
    const showFeedback = message => {
        const feedback = document.getElementById('svFilterFeedback');
        if (!feedback) return;
        feedback.textContent = message;
        feedback.classList.toggle('is-visible', Boolean(message));
    };
    const showSaveFeedback = (message, type) => {
        const feedback = document.getElementById('svSaveFeedback');
        if (!feedback) return;
        window.clearTimeout(saveFeedbackTimer);
        const icon = document.createElement('i');
        icon.className = type === 'success' ? 'bi bi-check-circle-fill' : 'bi bi-exclamation-circle-fill';
        feedback.replaceChildren(icon, document.createTextNode(message));
        feedback.className = `sv-save-feedback ${type} is-visible`;
        feedback.setAttribute('role', type === 'error' ? 'alert' : 'status');
        saveFeedbackTimer = window.setTimeout(() => feedback.classList.remove('is-visible'), 5000);
    };
    const confirmStockUpdate = (form, stock) => new Promise(resolve => {
        if (!stockConfirmDialog || typeof stockConfirmDialog.showModal !== 'function') {
            resolve(window.confirm(`Simpan stok ${stock.toLocaleString('id-ID')} untuk ${form.dataset.variantName || 'variant ini'}?`));
            return;
        }
        document.getElementById('svConfirmVariant').textContent = form.dataset.variantName || 'Variant tanpa nama';
        document.getElementById('svConfirmStore').textContent = form.dataset.storeName || 'Marketplace';
        document.getElementById('svConfirmStock').textContent = stock.toLocaleString('id-ID');
        pendingConfirmation = {resolve};
        stockConfirmDialog.returnValue = '';
        stockConfirmDialog.showModal();
    });
    stockConfirmDialog?.addEventListener('close', () => {
        const confirmation = pendingConfirmation;
        pendingConfirmation = null;
        confirmation?.resolve(stockConfirmDialog.returnValue === 'confirm');
    });
    const showEmpty = () => {
        const rows = document.getElementById('svRows');
        if (!rows || rows.querySelector('[data-stock-row]')) return;
        rows.innerHTML = '<div class="sv-empty" data-empty-row><span class="sv-empty-icon"><i class="bi bi-check2"></i></span><strong>Semua stok variant terisi</strong><span>Perubahan berhasil dikirim ke marketplace.</span></div>';
    };
    const removeStockRow = row => {
        const productGroup = row.closest('[data-product-group]');
        row.remove();
        if (productGroup && !productGroup.querySelector('[data-stock-row]')) {
            const dialog = productGroup.querySelector('[data-variant-dialog]');
            if (dialog?.open && typeof dialog.close === 'function') dialog.close();
            productGroup.remove();
        }
        showEmpty();
    };
    const bindProductDialogs = () => {
        document.querySelectorAll('[data-variant-dialog-trigger]').forEach(trigger => {
            const dialog = document.getElementById(trigger.dataset.dialogTarget || '');
            if (!dialog) return;
            const closeDialog = () => {
                if (typeof dialog.close === 'function') dialog.close();
                else dialog.removeAttribute('open');
            };
            trigger.addEventListener('click', () => {
                if (typeof dialog.showModal === 'function') dialog.showModal();
                else dialog.setAttribute('open', '');
                dialog.querySelector('[data-dialog-close]')?.focus();
            });
            dialog.querySelectorAll('[data-dialog-close]').forEach(button => button.addEventListener('click', closeDialog));
            dialog.addEventListener('click', event => {
                if (event.target === dialog) closeDialog();
            });
            dialog.addEventListener('close', () => trigger.focus());
        });
    };
    const bindStockActions = () => {
        document.querySelectorAll('[data-stock-form]').forEach(form => form.addEventListener('submit', async event => {
            event.preventDefault();
            const input = form.querySelector('.sv-stock-input');
            const button = form.querySelector('.sv-save');
            const row = form.closest('[data-stock-row]');
            const status = form.closest('.sv-action')?.querySelector('[data-stock-status]');
            const stock = Number(input.value);
            if (!Number.isInteger(stock) || stock < 0) {
                status.textContent = 'Masukkan stok bulat 0 atau lebih.';
                status.className = 'sv-status error';
                input.focus();
                return;
            }
            if (!await confirmStockUpdate(form, stock)) {
                status.textContent = 'Pembaruan stok dibatalkan.';
                status.className = 'sv-status';
                input.focus();
                return;
            }
            button.disabled = true;
            input.disabled = true;
            status.textContent = 'Mengirim stok ke marketplace…';
            status.className = 'sv-status';
            try {
                const response = await fetch(`/api/marketplace/products/${form.dataset.productId}/stock`, {
                    method: 'POST',
                    headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf},
                    body: JSON.stringify({stock_list: [{model_id: form.dataset.modelId, stock}]})
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);
                const previous = Number(row.dataset.currentStock || 0);
                const isInternalEmptyAudit = row.dataset.stockScope === 'internal_empty_marketplace_stock';
                status.textContent = 'Stok berhasil dikirim.';
                status.className = 'sv-status ok';
                showSaveFeedback('Stok berhasil dikirim ke marketplace.', 'success');
                row.dataset.currentStock = String(stock);
                const mpStockVal = row.querySelector('[data-mp-stock-val]');
                if (mpStockVal) {
                    mpStockVal.textContent = stock.toLocaleString('id-ID');
                    mpStockVal.className = `sv-mp-stock ${stock === 0 ? 'is-zero' : (stock < 0 ? 'is-negative' : 'is-available')}`;
                }
                if (isInternalEmptyAudit && stock === 0) {
                    setCount('internal_empty', -1);
                    setCount('scope_internal_empty', -1);
                    removeStockRow(row);
                } else if (row.dataset.stockScope === 'marketplace_empty' && stock > 0) {
                    setCount('total', -1);
                    setCount('scope_empty', -1);
                    setCount(previous < 0 ? 'negative' : 'zero', -1);
                    removeStockRow(row);
                } else if (row.dataset.stockScope === 'marketplace_empty' && previous < 0 && stock === 0) {
                    setCount('negative', -1);
                    setCount('zero', 1);
                    status.textContent = 'Tersimpan · stok masih 0.';
                }
            } catch (error) {
                status.textContent = error.message || 'Gagal menyimpan stok.';
                status.className = 'sv-status error';
                showSaveFeedback(error.message || 'Gagal menyimpan stok. Coba lagi.', 'error');
            } finally {
                if (document.body.contains(button)) {
                    button.disabled = false;
                    input.disabled = false;
                }
            }
        }));
    };
    const dashboardUrl = form => {
        const params = new URLSearchParams(new FormData(form));
        [...params.entries()].forEach(([key, value]) => {
            if (String(value).trim() === '') params.delete(key);
        });
        params.delete('page');
        const query = params.toString();
        return `${form.action}${query ? `?${query}` : ''}`;
    };
    const loadDashboard = async (url, pushState = true) => {
        const content = document.getElementById('svDashboardContent');
        if (!content) { window.location.assign(url); return; }
        activeRequest?.abort();
        const requestController = new AbortController();
        activeRequest = requestController;
        const activeId = document.activeElement?.id;
        showFeedback('');
        content.classList.add('is-loading');
        content.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(url, {
                headers: {'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest'},
                signal: requestController.signal
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const documentResult = new DOMParser().parseFromString(await response.text(), 'text/html');
            const fresh = documentResult.getElementById('svDashboardContent');
            if (!fresh) throw new Error('Konten filter tidak ditemukan.');
            content.innerHTML = fresh.innerHTML;
            if (pushState) window.history.pushState({}, '', url);
            bindAll();
            const focusTarget = activeId ? document.getElementById(activeId) : null;
            if (focusTarget) {
                focusTarget.focus();
                if (focusTarget.setSelectionRange) focusTarget.setSelectionRange(focusTarget.value.length, focusTarget.value.length);
            }
        } catch (error) {
            if (error.name !== 'AbortError') showFeedback('Filter gagal dimuat. Periksa koneksi lalu ubah filter untuk mencoba lagi.');
        } finally {
            if (activeRequest === requestController) {
                content.classList.remove('is-loading');
                content.removeAttribute('aria-busy');
            }
        }
    };
    const bindRealtimeFilter = () => {
        const form = document.querySelector('[data-realtime-filter]');
        if (!form) return;
        form.addEventListener('submit', event => { event.preventDefault(); loadDashboard(dashboardUrl(form)); });
        form.querySelectorAll('select').forEach(control => control.addEventListener('change', () => loadDashboard(dashboardUrl(form))));
        form.querySelectorAll('input:not([type="hidden"])').forEach(control => control.addEventListener('input', () => {
            window.clearTimeout(filterTimer);
            filterTimer = window.setTimeout(() => loadDashboard(dashboardUrl(form)), 400);
        }));
    };
    const bindDashboardLinks = () => {
        document.querySelectorAll('[data-dashboard-link], .sv-pages a').forEach(link => link.addEventListener('click', event => {
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            loadDashboard(link.href);
        }));
    };
    const bindStorePicker = () => {
        const picker = document.querySelector('[data-store-picker]');
        if (!picker) return;

        const empty = picker.querySelector('[data-store-empty]');
        const platformTabs = [...picker.querySelectorAll('[data-store-platform]')];
        const storeGroups = [...picker.querySelectorAll('[data-store-group]')];

        const apply = () => {
            const platform = picker.dataset.activePlatform || 'all';
            let visibleCount = 0;

            storeGroups.forEach(group => {
                const platformMatches = platform === 'all' || group.dataset.storeChannel === platform;
                let groupVisible = false;
                group.querySelectorAll('[data-store-link]').forEach(link => {
                    link.hidden = !platformMatches;
                    groupVisible = groupVisible || platformMatches;
                    if (platformMatches) visibleCount += 1;
                });
                group.hidden = !groupVisible;
            });

            if (empty) empty.hidden = visibleCount > 0;
            platformTabs.forEach(tab => {
                const active = tab.dataset.storePlatform === platform;
                tab.classList.toggle('active', active);
                tab.setAttribute('aria-pressed', String(active));
            });
        };

        platformTabs.forEach((tab, index) => {
            tab.addEventListener('click', event => {
                const platformUrl = tab.dataset.platformUrl;
                if (platformUrl) {
                    event.preventDefault();
                    loadDashboard(platformUrl);
                    return;
                }
                picker.dataset.activePlatform = tab.dataset.storePlatform || 'all';
                apply();
            });
            tab.addEventListener('keydown', event => {
                if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
                event.preventDefault();
                const nextIndex = event.key === 'Home'
                    ? 0
                    : event.key === 'End'
                        ? platformTabs.length - 1
                        : (index + (event.key === 'ArrowRight' ? 1 : -1) + platformTabs.length) % platformTabs.length;
                platformTabs[nextIndex]?.focus();
                platformTabs[nextIndex]?.click();
            });
        });
        apply();
    };
    const bindStoreTabs = () => {
        const tabs = [...document.querySelectorAll('.sv-store-tab')];
        tabs.forEach((tab, index) => tab.addEventListener('keydown', event => {
            if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
            event.preventDefault();
            const nextIndex = event.key === 'Home'
                ? 0
                : event.key === 'End'
                    ? tabs.length - 1
                    : (index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
            tabs[nextIndex]?.focus();
            tabs[nextIndex]?.click();
        }));
    };
    const bindAll = () => { bindProductDialogs(); bindStockActions(); bindRealtimeFilter(); bindDashboardLinks(); bindStorePicker(); bindStoreTabs(); };
    window.addEventListener('popstate', () => loadDashboard(window.location.href, false));
    bindAll();
})();
</script>
@endpush
