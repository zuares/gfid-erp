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
        position: relative; display: flex; flex-direction: column; gap: .8rem; overflow: hidden;
        margin-bottom: .85rem; padding: 1rem; border: 1px solid color-mix(in srgb, var(--sv-brand) 20%, var(--sv-line));
        border-radius: var(--sv-radius-card); background: linear-gradient(135deg, color-mix(in srgb, var(--sv-brand) 9%, var(--sv-surface)), var(--sv-surface) 60%, color-mix(in srgb, var(--gf-color-accent, #13b8c4) 7%, var(--sv-surface)));
        box-shadow: var(--sv-shadow);
    }
    .sv-head::after { content: ''; position: absolute; right: -3rem; bottom: -4.5rem; width: 11rem; height: 11rem; border-radius: 50%; background: color-mix(in srgb, var(--sv-brand) 8%, transparent); pointer-events: none; }
    .sv-head > * { position: relative; z-index: 1; }
    .sv-eyebrow {
        display: inline-flex; align-items: center; gap: .35rem; margin-bottom: .35rem;
        color: var(--sv-brand-strong); font-size: .68rem; font-weight: 800;
        letter-spacing: .1em; text-transform: uppercase;
    }
    .sv-title {
        margin: 0; color: var(--sv-content); font-size: clamp(1.25rem, 4vw, 1.7rem);
        font-weight: 850; letter-spacing: -.04em; line-height: 1.15;
    }
    .sv-copy { max-width: 720px; margin: .4rem 0 0; color: var(--sv-muted); font-size: .8rem; line-height: 1.55; }
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
    .sv-filter, .sv-panel {
        border: 1px solid var(--sv-line); border-radius: var(--sv-radius-card);
        background: var(--sv-surface); box-shadow: var(--sv-shadow);
    }
    .sv-section-label, .sv-field-label, .sv-cell-label {
        color: var(--sv-muted); font-size: .66rem; font-weight: 750; letter-spacing: .025em;
    }
    .sv-section-label { display: block; margin: 0 0 .5rem .15rem; text-transform: uppercase; }
    .sv-store-tabs {
        display: flex; gap: .2rem; overflow-x: auto; padding: .25rem;
        border: 1px solid var(--sv-line); border-radius: var(--sv-radius-control); background: var(--sv-soft);
        scroll-snap-type: x proximity; scrollbar-width: none;
    }
    .sv-store-tabs::-webkit-scrollbar { display: none; }
    .sv-store-tab {
        display: inline-flex; flex: 0 0 auto; align-items: center; justify-content: center; gap: .4rem;
        min-height: 34px; padding: .4rem .65rem; border: 1px solid transparent; border-radius: 8px;
        background: transparent; color: var(--sv-muted); font-size: .71rem; font-weight: 750;
        line-height: 1; text-decoration: none; white-space: nowrap; scroll-snap-align: start;
    }
    .sv-store-tab:hover { color: var(--sv-brand-strong); }
    .sv-store-tab.active {
        border-color: color-mix(in srgb, var(--sv-brand) 16%, var(--sv-line));
        background: var(--sv-surface); box-shadow: 0 1px 2px rgba(16, 24, 40, .06); color: var(--sv-brand-strong);
    }
    .sv-scope-nav { display: flex; gap: .25rem; flex-wrap: wrap; padding: .25rem; border: 1px solid var(--sv-line); border-radius: var(--sv-radius-control); background: var(--sv-soft); }
    .sv-scope-btn {
        display: inline-flex; align-items: center; gap: .5rem;
        min-height: 38px; padding: .4rem .65rem; border: 1px solid transparent;
        border-radius: 8px; background: transparent;
        color: var(--sv-muted); font-size: .76rem; font-weight: 750; text-decoration: none;
        transition: all .15s ease;
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
        display: grid; grid-template-columns: minmax(0, 1fr); gap: .7rem;
        margin-bottom: .75rem; padding: .85rem;
    }
    .sv-filter-head { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; grid-column: 1 / -1; }
    .sv-filter-title { display: flex; align-items: center; gap: .4rem; margin: 0; color: var(--sv-content); font-size: .8rem; font-weight: 800; }
    .sv-filter-title i { display: inline-grid; width: 1.65rem; height: 1.65rem; place-items: center; border-radius: .5rem; background: color-mix(in srgb, var(--sv-brand) 10%, var(--sv-surface)); color: var(--sv-brand); font-size: .75rem; }
    .sv-filter-copy { margin: .18rem 0 0; color: var(--sv-muted); font-size: .67rem; line-height: 1.4; }
    .sv-filter-state { display: inline-flex; flex: 0 0 auto; align-items: center; gap: .3rem; min-height: 26px; padding: .2rem .45rem; border-radius: 999px; background: color-mix(in srgb, var(--sv-success) 10%, var(--sv-surface)); color: var(--sv-success); font-size: .62rem; font-weight: 800; white-space: nowrap; }
    .sv-field { min-width: 0; }
    .sv-field-label { display: block; margin-bottom: .32rem; }
    .sv-search-wrap { position: relative; }
    .sv-search-wrap > i {
        position: absolute; top: 50%; left: .75rem; color: var(--sv-muted);
        pointer-events: none; transform: translateY(-50%);
    }
    .sv-control {
        width: 100%; min-height: 44px; border-color: var(--sv-line); border-radius: var(--sv-radius-control);
        background-color: var(--sv-surface); color: var(--sv-content); font-size: max(16px, .78rem);
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
    .sv-panel-head {
        display: grid; gap: .7rem;
        padding: .9rem; border-bottom: 1px solid var(--sv-line); background: linear-gradient(90deg, color-mix(in srgb, var(--sv-brand) 5%, var(--sv-surface)), var(--sv-surface) 55%);
    }
    .sv-panel-title-row { display: flex; align-items: center; justify-content: space-between; gap: .75rem; }
    .sv-store-picker-row {
        display: flex; flex-direction: column; gap: .45rem; padding-top: .7rem; border-top: 1px solid var(--sv-line);
    }
    @media (min-width: 768px) {
        .sv-store-picker-row { flex-direction: row; align-items: flex-start; gap: .75rem; }
    }
    .sv-store-picker-label {
        display: inline-flex; align-items: center; gap: .35rem; font-size: .7rem; font-weight: 800; color: var(--sv-muted); text-transform: uppercase; letter-spacing: .03em; white-space: nowrap; flex: 0 0 auto;
    }
    .sv-panel-tabs { margin: 0; padding-bottom: .15rem; flex: 1 1 auto; }
    .sv-store-groups { display: grid; gap: .55rem; min-width: 0; flex: 1 1 auto; }
    .sv-store-group { display: grid; gap: .35rem; min-width: 0; }
    .sv-store-group-label {
        display: inline-flex; align-items: center; gap: .35rem; color: var(--sv-muted);
        font-size: .65rem; font-weight: 800; letter-spacing: .025em; text-transform: uppercase;
    }
    .sv-store-group-label i { color: var(--sv-brand); }
    .sv-panel-summary {
        display: flex; flex-wrap: wrap; align-items: center; gap: .25rem .65rem; padding-top: .6rem; border-top: 1px solid var(--sv-line);
        color: var(--sv-muted); font-size: .7rem; line-height: 1.35;
    }
    .sv-summary-item {
        display: inline-flex; align-items: center; gap: .25rem; min-height: 28px;
        color: var(--sv-muted); font-weight: 700; text-decoration: none;
    }
    .sv-summary-item strong { color: var(--sv-content); font-size: .76rem; font-variant-numeric: tabular-nums; }
    a.sv-summary-item { padding: .15rem .35rem; border-radius: 999px; }
    a.sv-summary-item:hover { background: var(--sv-soft); color: var(--sv-brand-strong); }
    a.sv-summary-item:focus-visible { outline: 2px solid var(--sv-brand); outline-offset: 2px; }
    a.sv-summary-item.active { background: color-mix(in srgb, var(--sv-brand) 10%, var(--sv-surface)); color: var(--sv-brand-strong); }
    .sv-summary-item--warning strong { color: #b45309; }
    .sv-scope-note { color: var(--sv-muted); font-size: .68rem; line-height: 1.35; }
    .sv-panel-title { margin: 0; color: var(--sv-content); font-size: .92rem; font-weight: 850; letter-spacing: -.015em; }
    .sv-panel-count {
        display: inline-flex; align-items: center; min-height: 26px; padding: .2rem .5rem;
        border: 1px solid var(--sv-line); border-radius: 999px; background: var(--sv-surface); color: var(--sv-muted);
        font-size: .65rem; font-weight: 750; white-space: nowrap;
    }
    .sv-list-head { display: none; }
    .sv-records { display: grid; gap: .65rem; padding: .65rem; }
    .sv-record {
        display: grid; grid-template-columns: 52px minmax(0, 1fr);
        grid-template-areas: 'photo variant' 'support support' 'metrics metrics' 'action action';
        gap: .7rem .75rem; padding: .75rem; border: 1px solid var(--sv-line);
        border-radius: calc(var(--sv-radius-card) - 3px); background: var(--sv-surface); box-shadow: 0 1px 2px rgba(16, 24, 40, .035);
        transition: border-color .16s ease, box-shadow .16s ease, transform .16s ease;
    }
    .sv-record:hover { border-color: color-mix(in srgb, var(--sv-brand) 28%, var(--sv-line)); box-shadow: 0 7px 18px rgba(16, 24, 40, .07); transform: translateY(-1px); }
    .sv-photo-cell { grid-area: photo; }
    .sv-variant-cell { grid-area: variant; min-width: 0; }
    .sv-support { grid-area: support; display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, .75fr); gap: .65rem; }
    .sv-metrics { grid-area: metrics; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .5rem; }
    .sv-action { grid-area: action; min-width: 0; padding: .65rem; border: 1px solid color-mix(in srgb, var(--sv-brand) 14%, var(--sv-line)); border-radius: var(--sv-radius-control); background: color-mix(in srgb, var(--sv-brand) 4%, var(--sv-surface)); }
    .sv-cell-label { display: block; margin-bottom: .28rem; text-transform: uppercase; }
    .sv-img {
        width: 52px; height: 52px; border: 1px solid var(--sv-line);
        border-radius: var(--sv-radius-control); background: var(--sv-soft); object-fit: cover;
    }
    .sv-img-empty { display: grid; place-items: center; color: var(--sv-muted); }
    .sv-item-id {
        display: inline-block; max-width: 100%; overflow: hidden;
        border-bottom: 1px dotted color-mix(in srgb, var(--sv-muted) 55%, transparent);
        color: var(--sv-muted); font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: .67rem; font-weight: 750; text-overflow: ellipsis; white-space: nowrap; cursor: help;
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
        width: min(420px, calc(100% - 1.5rem)); margin: auto; padding: 0; overflow: hidden;
        border: 1px solid var(--sv-line); border-radius: var(--sv-radius-card); background: var(--sv-surface);
        box-shadow: 0 24px 70px rgba(16, 24, 40, .26); color: var(--sv-content);
    }
    .sv-confirm-dialog::backdrop { background: rgba(16, 24, 40, .46); backdrop-filter: blur(2px); }
    .sv-confirm-head { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; padding: 1rem 1rem .7rem; }
    .sv-confirm-kicker { display: flex; align-items: center; gap: .4rem; color: var(--sv-brand-strong); font-size: .65rem; font-weight: 850; letter-spacing: .07em; text-transform: uppercase; }
    .sv-confirm-title { margin: .24rem 0 0; color: var(--sv-content); font-size: .96rem; font-weight: 850; }
    .sv-confirm-close {
        display: inline-grid; width: 36px; height: 36px; flex: 0 0 auto; place-items: center; border: 0;
        border-radius: 50%; background: transparent; color: var(--sv-muted); font-size: 1rem;
    }
    .sv-confirm-close:hover { background: var(--sv-soft); color: var(--sv-content); }
    .sv-confirm-body { padding: .1rem 1rem 1rem; }
    .sv-confirm-copy { margin: 0; color: var(--sv-muted); font-size: .76rem; line-height: 1.5; }
    .sv-confirm-detail { display: grid; grid-template-columns: 1fr auto; gap: .55rem 1rem; margin: .85rem 0 0; padding: .75rem; border-radius: var(--sv-radius-control); background: var(--sv-soft); }
    .sv-confirm-detail dt { color: var(--sv-muted); font-size: .64rem; font-weight: 750; }
    .sv-confirm-detail dd { max-width: 210px; margin: 0; overflow: hidden; color: var(--sv-content); font-size: .72rem; font-weight: 800; text-align: right; text-overflow: ellipsis; white-space: nowrap; }
    .sv-confirm-stock { color: var(--sv-success) !important; font-variant-numeric: tabular-nums; }
    .sv-confirm-actions { display: grid; grid-template-columns: 1fr 1fr; gap: .55rem; padding: .8rem 1rem 1rem; border-top: 1px solid var(--sv-line); }
    .sv-confirm-button { min-height: 42px; border: 1px solid var(--sv-line); border-radius: var(--sv-radius-control); background: var(--sv-surface); color: var(--sv-content); font-size: .75rem; font-weight: 800; }
    .sv-confirm-button:hover { background: var(--sv-soft); }
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
        .sv-head { flex-direction: row; align-items: flex-start; justify-content: space-between; }
        .sv-back { min-height: 38px; }
        .sv-filter { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .sv-search-field { grid-column: 1 / -1; }
    }
    @media (min-width: 768px) {
        .sv-page { padding-top: 1.25rem; }
        .sv-filter { grid-template-columns: minmax(220px, 2fr) repeat(3, minmax(140px, 1fr)); align-items: end; }
        .sv-search-field { grid-column: auto; }
        .sv-records { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (min-width: 1120px) {
        .sv-panel-head { padding: .75rem 1rem; }
        .sv-list-head, .sv-record {
            grid-template-columns: 56px minmax(170px, 1.4fr) minmax(130px, 1fr) 96px 112px 108px minmax(180px, 1.1fr) 100px;
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
        .sv-record {
            grid-template-areas: none; align-items: center; border: 0; border-bottom: 1px solid var(--sv-line);
            border-radius: 0; padding: .65rem .75rem;
        }
        .sv-record:last-child { border-bottom: 0; }
        .sv-record:hover { background: color-mix(in srgb, var(--sv-brand) 3%, var(--sv-surface)); box-shadow: none; transform: none; }
        .sv-photo-cell, .sv-variant-cell, .sv-map-cell, .sv-mp-stock-cell, .sv-internal-cell,
        .sv-average-cell, .sv-action, .sv-sync-cell { grid-area: auto; min-width: 0; padding-inline: .55rem; }
        .sv-photo-cell { grid-column: 1; }
        .sv-variant-cell { grid-column: 2; }
        .sv-map-cell { grid-column: 3; }
        .sv-mp-stock-cell { grid-column: 4; }
        .sv-internal-cell { grid-column: 5; }
        .sv-average-cell { grid-column: 6; }
        .sv-action { grid-column: 7; }
        .sv-sync-cell { grid-column: 8; }
        .sv-photo-cell, .sv-variant-cell, .sv-map-cell, .sv-mp-stock-cell, .sv-internal-cell,
        .sv-average-cell, .sv-action, .sv-sync-cell { grid-row: 1; }
        .sv-support, .sv-metrics { display: contents; }
        .sv-cell-label { display: none; }
        .sv-img { width: 44px; height: 44px; }
        .sv-metric { padding: 0; border: 0; background: transparent; }
        .sv-action { padding-block: 0; border: 0; border-radius: 0; background: transparent; }
        .sv-stock-form { grid-template-columns: 74px auto; }
        .sv-stock-input { min-height: 38px; font-size: .78rem; }
        .sv-save { min-height: 38px; padding-inline: .7rem; }
    }
    @media (prefers-reduced-motion: reduce) {
        .sv-dashboard-content, .sv-record, .sv-save-feedback { transition: none; }
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
        'min_avg_sales' => $minAverageSales > 0 ? $minAverageSales : null,
        'stock_scope' => $stockScope !== 'marketplace_empty' ? $stockScope : null,
    ], fn ($value) => $value !== null && $value !== '');
    $storeSharedFilters = array_filter([
        'stock_scope' => $stockScope !== 'marketplace_empty' ? $stockScope : null,
        'stock_status' => $stockStatus ?: null,
        'search' => $search ?: null,
        'min_avg_sales' => $minAverageSales > 0 ? $minAverageSales : null,
    ], fn ($value) => $value !== null && $value !== '');
    $allStoreFilters = array_merge($storeSharedFilters, array_filter(['channel' => $channel ?: null]));
    $emptyScopeTotal = $summary['empty_total'] ?? $summary['total'];
    $internalEmptyTotal = $summary['internal_empty_mp_stock'] ?? 0;
    $storesByChannel = $stores->groupBy(function ($store): string {
        $channelCode = strtolower((string) $store->channel?->code);
        return in_array($channelCode, ['tiktok', 'ttk', 'tt'], true) ? 'tiktok' : 'shopee';
    });
    $storeGroups = [
        ['key' => 'tiktok', 'label' => 'TikTok Shop', 'icon' => 'bi-music-note-beamed', 'stores' => $storesByChannel->get('tiktok', collect())],
        ['key' => 'shopee', 'label' => 'Shopee', 'icon' => 'bi-bag', 'stores' => $storesByChannel->get('shopee', collect())],
    ];
@endphp

<main class="sv-page">
    <header class="sv-head">
        <div>
            <span class="sv-eyebrow"><i class="bi bi-box-seam"></i> Inventory Marketplace</span>
            <h1 class="sv-title">Stok Variant Kosong</h1>
            <p class="sv-copy">Prioritaskan variant ter-mapping yang pernah terjual, lalu kirim stok internal tersedia langsung ke marketplace.</p>
        </div>
        <a class="sv-back" href="{{ route('marketplace.products') }}"><i class="bi bi-arrow-left"></i><span>Kembali ke Produk</span></a>
    </header>

    <div class="sv-feedback" id="svFilterFeedback" role="alert" aria-live="assertive"></div>

    <div class="sv-dashboard-content" id="svDashboardContent">
        <form class="sv-filter" method="GET" action="{{ route('marketplace.products.empty-variants') }}" data-realtime-filter>
            @if($storeId > 0)<input type="hidden" name="store_id" value="{{ $storeId }}">@endif
            <div class="sv-filter-head">
                <div>
                    <h2 class="sv-filter-title"><i class="bi bi-sliders2"></i>Filter variant</h2>
                    <p class="sv-filter-copy">Filter diterapkan otomatis saat nilai berubah.</p>
                </div>
                <span class="sv-filter-state"><i class="bi bi-lightning-charge-fill"></i>Realtime</span>
            </div>
            <div class="sv-field sv-search-field">
                <label class="sv-field-label" for="svSearch">Cari variant atau SKU</label>
                <div class="sv-search-wrap"><i class="bi bi-search"></i><input class="form-control sv-control sv-search" id="svSearch" name="search" value="{{ $search }}" maxlength="100" autocomplete="off" placeholder="Nama variant, SKU, atau ID produk"></div>
            </div>
            <div class="sv-field">
                <label class="sv-field-label" for="svChannel">Channel</label>
                <select class="form-select sv-control" id="svChannel" name="channel"><option value="">Semua channel</option><option value="shopee" @selected($channel === 'shopee')>Shopee</option><option value="tiktok" @selected($channel === 'tiktok')>TikTok Shop</option></select>
            </div>
            <div class="sv-field">
                <label class="sv-field-label" for="svStock">Status stok marketplace</label>
                <select class="form-select sv-control" id="svStock" name="stock_status" @disabled($stockScope !== 'marketplace_empty')><option value="zero" @selected($stockStatus === 'zero')>Stok tepat 0</option><option value="negative" @selected($stockStatus === 'negative')>Stok minus</option></select>
            </div>
            <div class="sv-field">
                <label class="sv-field-label" for="svAverage">Rata² terjual/hari lebih dari</label>
                <input class="form-control sv-control" id="svAverage" name="min_avg_sales" type="number" min="0" max="1000000" step="0.1" value="{{ $minAverageSales }}" inputmode="decimal" placeholder="Contoh: 2">
            </div>
        </form>

        <section class="sv-panel" aria-live="polite" aria-label="Daftar variant stok kosong">
            <div class="sv-panel-head">
                <div class="sv-panel-title-row">
                    <div>
                        <h2 class="sv-panel-title">Variant perlu diperbarui</h2>
                        @if($stockScope === 'internal_empty_marketplace_stock')
                            <span class="sv-scope-note"><i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>Audit mismatch: stok internal kosong, tetapi marketplace masih menjual variant ini.</span>
                        @else
                            <span class="sv-scope-note">Menampilkan stok marketplace 0 dengan stok internal tersedia untuk pembaruan.</span>
                        @endif
                    </div>
                    <span class="sv-panel-count">{{ number_format($variants->count()) }} di halaman ini</span>
                </div>

                <nav class="sv-scope-nav" aria-label="Mode pemeriksaan stok">
                    <a class="sv-scope-btn {{ $stockScope === 'marketplace_empty' ? 'active' : '' }}"
                       aria-current="{{ $stockScope === 'marketplace_empty' ? 'page' : 'false' }}"
                       data-dashboard-link
                       href="{{ route('marketplace.products.empty-variants', array_merge($baseFilters, ['stock_scope' => null, 'stock_status' => 'zero'])) }}">
                        <i class="bi bi-box-seam"></i>
                        <span>Stok MP 0 · Internal Ada</span>
                        <span class="sv-badge" data-summary="scope_empty">{{ number_format($emptyScopeTotal) }}</span>
                    </a>
                    <a class="sv-scope-btn sv-scope-btn--warning {{ $stockScope === 'internal_empty_marketplace_stock' ? 'active' : '' }}"
                       aria-current="{{ $stockScope === 'internal_empty_marketplace_stock' ? 'page' : 'false' }}"
                       data-dashboard-link
                       href="{{ route('marketplace.products.empty-variants', array_merge($baseFilters, ['stock_scope' => 'internal_empty_marketplace_stock', 'stock_status' => null])) }}">
                        <i class="bi bi-exclamation-triangle"></i>
                        <span>Stok Kosong tapi di Marketplace Ada Stok</span>
                        <span class="sv-badge sv-badge--warning" data-summary="scope_internal_empty">{{ number_format($internalEmptyTotal) }}</span>
                    </a>
                </nav>

                <div class="sv-store-picker-row">
                    <span class="sv-store-picker-label"><i class="bi bi-shop"></i> Pilih Toko:</span>
                    <div class="sv-store-groups">
                        <nav class="sv-store-tabs sv-panel-tabs" aria-label="Semua toko marketplace">
                            <a class="sv-store-tab {{ $storeId === 0 ? 'active' : '' }}" aria-current="{{ $storeId === 0 ? 'page' : 'false' }}" data-dashboard-link href="{{ route('marketplace.products.empty-variants', $allStoreFilters) }}"><i class="bi bi-grid"></i>Semua Toko</a>
                        </nav>
                        @foreach($storeGroups as $group)
                            @continue($group['stores']->isEmpty())
                            <section class="sv-store-group" aria-labelledby="svStoreGroup{{ ucfirst($group['key']) }}">
                                <span class="sv-store-group-label" id="svStoreGroup{{ ucfirst($group['key']) }}"><i class="bi {{ $group['icon'] }}"></i>{{ $group['label'] }}</span>
                                <nav class="sv-store-tabs sv-panel-tabs" aria-label="Toko {{ $group['label'] }}">
                                    @foreach($group['stores'] as $store)
                                        <a class="sv-store-tab {{ $storeId === $store->id ? 'active' : '' }}" aria-current="{{ $storeId === $store->id ? 'page' : 'false' }}" data-dashboard-link href="{{ route('marketplace.products.empty-variants', array_merge($storeSharedFilters, ['store_id' => $store->id])) }}"><i class="bi bi-shop"></i>{{ $store->name }}</a>
                                    @endforeach
                                </nav>
                            </section>
                        @endforeach
                    </div>
                </div>

                <nav class="sv-panel-summary" aria-label="Ringkasan dan filter stok">
                    @if($stockScope === 'internal_empty_marketplace_stock')
                        <span class="sv-summary-item sv-summary-item--warning"><strong data-summary="internal_empty">{{ number_format($internalEmptyTotal) }}</strong> variant internal kosong</span>
                        <span class="sv-summary-item"><strong>{{ number_format($summary['products']) }}</strong> produk</span>
                        <span class="sv-summary-item">Marketplace masih ada stok</span>
                    @else
                        <span class="sv-summary-item"><strong data-summary="total">{{ number_format($emptyScopeTotal) }}</strong> variant siap diisi</span>
                        <span class="sv-summary-item"><strong>{{ number_format($summary['products']) }}</strong> produk</span>
                        <a class="sv-summary-item {{ $stockStatus === 'zero' ? 'active' : '' }}" data-dashboard-link href="{{ route('marketplace.products.empty-variants', array_merge($baseFilters, ['stock_status' => 'zero'])) }}">Stok 0: <strong data-summary="zero">{{ number_format($summary['zero']) }}</strong></a>
                        <a class="sv-summary-item {{ $stockStatus === 'negative' ? 'active' : '' }}" data-dashboard-link href="{{ route('marketplace.products.empty-variants', array_merge($baseFilters, ['stock_status' => 'negative'])) }}">Minus: <strong data-summary="negative">{{ number_format($summary['negative']) }}</strong></a>
                    @endif
                </nav>
            </div>
            <div class="sv-list-head" aria-hidden="true">
                <span>Produk</span>
                <span>Variant</span>
                <span>Mapping</span>
                <span>Stok MP</span>
                <span>Stok Internal</span>
                <span>Rata² Terjual/Hari</span>
                <span>Update Stok</span>
                <span>Terakhir Sync</span>
            </div>
            <div class="sv-records" id="svRows">
                @forelse($variants as $variant)
                    @php
                        $product = $variant->product;
                        $mapping = $variant->mapping_detail;
                        $available = max(0, (int) floor($mapping['internal_available'] ?? 0));
                        $averageWindow = max(1, (int) ($mapping['avg_daily_sales_window'] ?? 30));
                    @endphp
                    <article class="sv-record" data-stock-row data-stock-scope="{{ $stockScope }}" data-current-stock="{{ $variant->stock }}">
                        <div class="sv-photo-cell">
                            <span class="sv-cell-label">Produk</span>
                            @if($product?->image_url)
                                <img class="sv-img sv-product-photo" src="{{ $product->image_url }}" alt="{{ $product->item_name ?: 'Produk marketplace' }}" loading="lazy">
                            @else
                                <span class="sv-img sv-img-empty sv-product-photo" title="Tidak ada foto" aria-label="Tidak ada foto produk"><i class="bi bi-image"></i></span>
                            @endif
                        </div>
                        <div class="sv-variant-cell">
                            <span class="sv-cell-label">Variant</span>
                            <span class="sv-item-id" tabindex="0" title="{{ $product?->item_name ?: 'Produk tanpa nama' }}" aria-label="Item ID {{ $product?->item_id }}. {{ $product?->item_name ?: 'Produk tanpa nama' }}">ID {{ $product?->item_id ?: '—' }}</span>
                            <div class="sv-variant-name">{{ $variant->model_name ?: 'Variant tanpa nama' }}</div>
                            <div class="sv-sku">{{ $variant->model_sku }}</div>
                            <div class="sv-meta">{{ $product?->store?->name ?: 'Toko tidak ditemukan' }}</div>
                        </div>
                        <div class="sv-support">
                            <div class="sv-map-cell">
                                <span class="sv-cell-label">Mapping internal</span>
                                <div class="sv-map-code">{{ $mapping['item_code'] ?? '—' }}</div>
                                <div class="sv-meta">{{ $mapping['item_name'] ?? 'Item internal' }}</div>
                            </div>
                            <div class="sv-sync-cell">
                                <span class="sv-cell-label">Terakhir sync</span>
                                <span class="sv-sync">{{ $product?->synced_at?->diffForHumans() ?: 'Belum pernah' }}</span>
                            </div>
                        </div>
                        <div class="sv-metrics">
                            <div class="sv-metric sv-mp-stock-cell">
                                <span class="sv-cell-label">Stok Marketplace</span>
                                <span class="sv-mp-stock @if($variant->stock < 0) is-negative @elseif($variant->stock === 0) is-zero @else is-available @endif" data-mp-stock-val>
                                    {{ number_format($variant->stock) }}
                                </span>
                                <div class="sv-meta">
                                    @if($variant->stock < 0)
                                        <span class="text-danger fw-bold">Minus {{ abs($variant->stock) }}</span>
                                    @elseif($variant->stock === 0)
                                        <span class="text-muted">Kosong (0)</span>
                                    @else
                                        <span class="text-primary fw-bold">Ada stok</span>
                                    @endif
                                </div>
                            </div>
                            <div class="sv-metric sv-internal-cell">
                                <span class="sv-cell-label">Stok internal</span>
                                <span class="sv-internal">{{ number_format($available, 0, ',', '.') }}</span>
                                <div class="sv-meta">Fisik {{ number_format($mapping['internal_physical'] ?? 0, 0, ',', '.') }} · dialokasi {{ number_format($mapping['internal_allocated'] ?? 0, 0, ',', '.') }}</div>
                            </div>
                            <div class="sv-metric sv-average-cell">
                                <span class="sv-cell-label">Rata² terjual/hari</span>
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
                @empty
                    <div class="sv-empty" data-empty-row>
                        <span class="sv-empty-icon"><i class="bi bi-check2"></i></span>
                        <strong>Tidak ada variant ditemukan</strong>
                        <span>Tidak ada stok kosong yang cocok dengan toko dan filter aktif.</span>
                    </div>
                @endforelse
            </div>
            @if($variants->hasPages())<div class="sv-pages">{{ $variants->links() }}</div>@endif
        </section>
    </div>
</main>
<dialog class="sv-confirm-dialog" id="svStockConfirm" aria-labelledby="svStockConfirmTitle" aria-describedby="svStockConfirmCopy">
    <form method="dialog">
        <div class="sv-confirm-head">
            <div>
                <span class="sv-confirm-kicker"><i class="bi bi-cloud-arrow-up"></i> Konfirmasi pembaruan</span>
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
                    row.remove();
                    showEmpty();
                } else if (!isInternalEmptyAudit && stock > 0) {
                    setCount('total', -1);
                    setCount('scope_empty', -1);
                    setCount(previous < 0 ? 'negative' : 'zero', -1);
                    row.remove();
                    showEmpty();
                } else if (!isInternalEmptyAudit && previous < 0 && stock === 0) {
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
    const bindAll = () => { bindStockActions(); bindRealtimeFilter(); bindDashboardLinks(); bindStoreTabs(); };
    window.addEventListener('popstate', () => loadDashboard(window.location.href, false));
    bindAll();
})();
</script>
@endpush
