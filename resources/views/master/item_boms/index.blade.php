@extends('layouts.app')

@section('title', 'Master • BOM SKU')

@push('head')
<style>
    .bom-page {
        --bom-brand: var(--gf-color-brand, #5b5ce2);
        --bom-brand-strong: var(--gf-color-brand-strong, #4142bb);
        --bom-accent: var(--gf-color-accent, #13b8c4);
        --bom-ink: var(--gf-color-content, #101828);
        --bom-muted: var(--gf-color-muted, #667085);
        --bom-surface: var(--gf-color-surface, #fff);
        --bom-surface-muted: var(--gf-color-surface-muted, #f2f4f7);
        --bom-line: var(--gf-color-line, #e4e7ec);
        --bom-success: var(--gf-color-success, #14945a);
        --bom-danger: var(--gf-color-danger, #d6455d);
        width: 100%; max-width: var(--gf-content-max, 1280px); margin-inline: auto;
        padding: 1rem var(--gf-content-gutter, .75rem) 4rem; color: var(--bom-ink);
    }
    .bom-hero { display: flex; align-items: flex-start; justify-content: space-between; gap: 1.25rem; padding: 1.35rem 1.5rem; margin-bottom: 1rem; border: 1px solid color-mix(in srgb, var(--bom-brand) 14%, var(--bom-line)); border-radius: 20px; background: linear-gradient(120deg, color-mix(in srgb, var(--bom-brand) 10%, var(--bom-surface)) 0%, var(--bom-surface) 55%, color-mix(in srgb, var(--bom-accent) 10%, var(--bom-surface)) 100%); box-shadow: var(--gf-shadow-lg, 0 24px 56px rgba(48,49,125,.14)); }
    .bom-hero-main { display: flex; align-items: flex-start; gap: .8rem; min-width: 0; }
    .bom-hero-icon { display: grid; flex: 0 0 auto; width: 2.7rem; height: 2.7rem; place-items: center; border-radius: 13px; color: #fff; background: linear-gradient(145deg, var(--bom-brand), var(--bom-accent)); box-shadow: 0 8px 16px color-mix(in srgb, var(--bom-brand) 24%, transparent); font-size: 1.1rem; }
    .bom-eyebrow { margin: .1rem 0 .28rem; color: var(--bom-brand-strong); font-size: .65rem; font-weight: 850; letter-spacing: .12em; line-height: 1; text-transform: uppercase; }
    .bom-title { margin: 0; color: var(--bom-ink); font-size: clamp(1.35rem, 2.2vw, 1.8rem); font-weight: 850; letter-spacing: -.045em; line-height: 1.05; }
    .bom-subtitle { max-width: 610px; margin: .5rem 0 0; color: var(--bom-muted); font-size: .8rem; line-height: 1.55; }
    .bom-hero-actions { display: flex; flex: 0 0 auto; flex-wrap: wrap; justify-content: flex-end; gap: .45rem; }
    .bom-btn { appearance: none; display: inline-flex; align-items: center; justify-content: center; gap: .42rem; min-height: 40px; padding: 0 .8rem; border: 1px solid transparent; border-radius: var(--gf-radius-control, 10px); font: inherit; font-size: .72rem; font-weight: 750; letter-spacing: -.01em; line-height: 1; text-decoration: none; cursor: pointer; transition: color .18s ease, background .18s ease, border-color .18s ease, box-shadow .18s ease, transform .18s ease; }
    .bom-btn > i { flex: 0 0 auto; font-size: .88em; line-height: 1; }
    .bom-btn:hover { transform: translateY(-1px); }
    .bom-btn:active { transform: translateY(0); }
    .bom-btn:focus-visible, .bom-search input:focus-visible, .bom-page a:focus-visible { outline: 3px solid color-mix(in srgb, var(--bom-brand) 28%, transparent); outline-offset: 2px; }
    .bom-btn:disabled, .bom-btn[aria-disabled="true"] { opacity: .55; pointer-events: none; cursor: not-allowed; transform: none; box-shadow: none !important; }
    .bom-btn-primary { color: #fff !important; border-color: var(--bom-brand); background: var(--bom-brand); box-shadow: 0 7px 14px color-mix(in srgb, var(--bom-brand) 20%, transparent); }
    .bom-btn-primary:hover, .bom-btn-primary:focus-visible { color: #fff !important; border-color: var(--bom-brand-strong); background: var(--bom-brand-strong); box-shadow: 0 9px 18px color-mix(in srgb, var(--bom-brand-strong) 24%, transparent); }
    .bom-btn-ghost { color: #475467 !important; border-color: #d0d5dd; background: rgba(255,255,255,.82); box-shadow: none; }
    .bom-btn-ghost:hover, .bom-btn-ghost:focus-visible { color: var(--bom-brand-strong) !important; border-color: color-mix(in srgb, var(--bom-brand) 30%, #d0d5dd); background: var(--bom-surface); box-shadow: 0 4px 10px rgba(16,24,40,.06); }
    .bom-btn-danger { color: var(--bom-danger) !important; border-color: color-mix(in srgb, var(--bom-danger) 22%, var(--bom-line)); background: color-mix(in srgb, var(--bom-danger) 7%, var(--bom-surface)); box-shadow: none; }
    .bom-btn-danger:hover, .bom-btn-danger:focus-visible { color: #fff !important; border-color: var(--bom-danger); background: var(--bom-danger); box-shadow: 0 5px 12px color-mix(in srgb, var(--bom-danger) 20%, transparent); }
    .bom-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .65rem; margin-bottom: 1rem; }
    .bom-stat { min-width: 0; padding: .8rem .9rem; border: 1px solid var(--bom-line); border-radius: 14px; background: var(--bom-surface); box-shadow: var(--gf-shadow-sm, 0 1px 2px rgba(16,24,40,.04), 0 4px 12px rgba(16,24,40,.04)); }
    .bom-stat-label { display: flex; align-items: center; gap: .35rem; color: var(--bom-muted); font-size: .66rem; font-weight: 750; line-height: 1.2; }
    .bom-stat-label i { color: var(--bom-brand); }
    .bom-stat-value { display: block; margin-top: .35rem; color: var(--bom-ink); font-size: 1.3rem; font-weight: 850; letter-spacing: -.045em; line-height: 1; font-variant-numeric: tabular-nums; }
    .bom-stat-note { display: block; margin-top: .3rem; color: var(--bom-muted); font-size: .62rem; }
    .bom-card { overflow: hidden; border: 1px solid var(--bom-line); border-radius: var(--gf-radius-card, 16px); background: var(--bom-surface); box-shadow: var(--gf-shadow-sm, 0 1px 2px rgba(16,24,40,.04), 0 4px 12px rgba(16,24,40,.04)); }
    .bom-toolbar { padding: .9rem 1rem; border-bottom: 1px solid var(--bom-line); background: color-mix(in srgb, var(--bom-surface-muted) 56%, var(--bom-surface)); }
    .bom-filter-grid { display: grid; grid-template-columns: minmax(240px, 1.65fr) minmax(145px, .72fr) minmax(180px, .9fr) auto; align-items: end; gap: .65rem; }
    .bom-search { position: relative; min-width: 0; }
    .bom-search label { display: block; margin: 0 0 .35rem .1rem; color: var(--bom-muted); font-size: .65rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
    .bom-search i { position: absolute; left: .75rem; bottom: .72rem; color: var(--bom-brand); font-size: .8rem; pointer-events: none; }
    .bom-search input { width: 100%; min-height: 40px; padding: 0 2.25rem; border: 1px solid var(--bom-line); border-radius: var(--gf-radius-control, 10px); color: var(--bom-ink); background: var(--bom-surface); font: inherit; font-size: .8rem; outline: none; transition: border-color .16s ease, box-shadow .16s ease; }
    .bom-search input:focus { border-color: var(--bom-brand); box-shadow: 0 0 0 3px color-mix(in srgb, var(--bom-brand) 14%, transparent); }
    .bom-search input::placeholder { color: color-mix(in srgb, var(--bom-muted) 68%, transparent); }
    .bom-search-status { position: absolute; right: .7rem; bottom: .73rem; display: none; color: var(--bom-brand); font-size: .68rem; font-weight: 750; }
    .bom-search-status.is-show { display: inline-flex; }
    .bom-filter-field label { display: block; margin: 0 0 .35rem .1rem; color: var(--bom-muted); font-size: .65rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
    .bom-filter-field select { width: 100%; min-height: 40px; padding: 0 2rem 0 .7rem; border: 1px solid var(--bom-line); border-radius: var(--gf-radius-control, 10px); color: var(--bom-ink); background-color: var(--bom-surface); font: inherit; font-size: .78rem; outline: none; }
    .bom-filter-field select:focus { border-color: var(--bom-brand); box-shadow: 0 0 0 3px color-mix(in srgb, var(--bom-brand) 14%, transparent); }
    .bom-filter-actions { display: flex; gap: .4rem; }
    .bom-filter-actions .bom-btn { min-height: 40px; }
    .bom-toolbar-meta { display: flex; align-items: center; flex-wrap: wrap; gap: .45rem; min-height: 24px; margin-top: .7rem; color: var(--bom-muted); font-size: .7rem; }
    .bom-filter-chip { display: inline-flex; align-items: center; gap: .3rem; padding: .35rem .55rem; border: 1px solid var(--bom-line); border-radius: 999px; color: var(--bom-muted); background: var(--bom-surface); font-weight: 700; }
    .bom-filter-chip strong { color: var(--bom-ink); }
    .bom-table-wrap { position: relative; max-height: min(66vh, 720px); overflow: auto; isolation: isolate; scrollbar-color: color-mix(in srgb, var(--bom-brand) 32%, var(--bom-line)) var(--bom-surface-muted); }
    .bom-table { width: 100%; min-width: 920px; margin: 0; border-collapse: separate; border-spacing: 0; color: var(--bom-ink); font-size: .76rem; }
    .bom-table th { position: sticky; top: 0; z-index: 10; padding: .75rem .85rem; border-bottom: 1px solid var(--bom-line); color: var(--bom-muted); background: var(--bom-surface-muted); box-shadow: 0 1px 0 var(--bom-line), 0 4px 10px rgba(16,24,40,.04); font-size: .65rem; font-weight: 800; letter-spacing: .045em; text-transform: uppercase; white-space: nowrap; }
    .bom-table td { padding: .85rem; border-bottom: 1px solid color-mix(in srgb, var(--bom-line) 72%, transparent); vertical-align: middle; }
    .bom-table tbody tr:last-child td { border-bottom: 0; }
    .bom-table tbody tr:hover td { background: color-mix(in srgb, var(--bom-brand) 3%, var(--bom-surface)); }
    .bom-table th:last-child, .bom-table td:last-child { position: sticky; right: 0; }
    .bom-table th:last-child { z-index: 12; background: var(--bom-surface-muted); box-shadow: -5px 0 10px rgba(16,24,40,.05), 0 1px 0 var(--bom-line); }
    .bom-table td:last-child { z-index: 2; background: var(--bom-surface); box-shadow: -5px 0 10px rgba(16,24,40,.035); }
    .bom-table tbody tr:hover td:last-child { background: color-mix(in srgb, var(--bom-brand) 3%, var(--bom-surface)); }
    .bom-index { width: 48px; color: var(--bom-muted); text-align: center; font-variant-numeric: tabular-nums; }
    .bom-item-code { display: inline-block; color: var(--bom-ink); font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .8rem; font-weight: 850; letter-spacing: -.02em; text-decoration: none; }
    .bom-item-code:hover { color: var(--bom-brand-strong); }
    .bom-item-name { margin-top: .22rem; color: var(--bom-muted); font-size: .7rem; line-height: 1.35; }
    .bom-name { margin-top: .2rem; color: var(--bom-muted); font-size: .68rem; }
    .bom-structure { color: var(--bom-ink); line-height: 1.45; }
    .bom-structure strong { font-weight: 850; }
    .bom-structure-meta { margin-top: .22rem; color: var(--bom-muted); font-size: .67rem; }
    .bom-actual { margin-top: .35rem; color: var(--bom-muted); font-size: .66rem; line-height: 1.45; }
    .bom-actual-value { color: var(--bom-ink); font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-weight: 800; }
    .bom-usage-over { color: var(--bom-danger); font-weight: 750; }
    .bom-usage-under { color: var(--bom-success); font-weight: 750; }
    .bom-status { display: inline-flex; align-items: center; gap: .32rem; padding: .32rem .55rem; border: 1px solid transparent; border-radius: 999px; font-size: .66rem; font-weight: 800; white-space: nowrap; }
    .bom-status::before { width: 6px; height: 6px; border-radius: 50%; content: ''; }
    .bom-status-active { color: var(--bom-success); border-color: color-mix(in srgb, var(--bom-success) 24%, var(--bom-line)); background: color-mix(in srgb, var(--bom-success) 8%, var(--bom-surface)); }
    .bom-status-active::before { background: var(--bom-success); }
    .bom-status-inactive { color: var(--bom-muted); border-color: var(--bom-line); background: var(--bom-surface-muted); }
    .bom-status-inactive::before { background: var(--bom-muted); }
    .bom-actions { display: inline-flex; align-items: center; justify-content: flex-end; gap: .35rem; }
    .bom-actions .bom-btn { min-height: 36px; padding-inline: .68rem; font-size: .7rem; }
    .bom-actions .bom-btn-danger { width: 34px; padding: 0; }
    .bom-mobile-list { display: none; }
    .bom-mobile-card { padding: .9rem 1rem; border-bottom: 1px solid var(--bom-line); }
    .bom-mobile-card:last-child { border-bottom: 0; }
    .bom-mobile-head { display: flex; align-items: flex-start; justify-content: space-between; gap: .7rem; }
    .bom-mobile-structure { display: flex; flex-wrap: wrap; gap: .35rem .65rem; margin-top: .75rem; padding-top: .65rem; border-top: 1px solid color-mix(in srgb, var(--bom-line) 72%, transparent); color: var(--bom-muted); font-size: .68rem; }
    .bom-mobile-structure strong { color: var(--bom-ink); }
    .bom-mobile-actions { display: grid; grid-template-columns: 1fr 42px; gap: .45rem; margin-top: .8rem; }
    .bom-mobile-actions .bom-btn { width: 100%; min-height: 44px; }
    .bom-empty { padding: 2.8rem 1.25rem; text-align: center; }
    .bom-empty-icon { display: grid; width: 2.6rem; height: 2.6rem; margin: 0 auto .75rem; place-items: center; border-radius: 13px; color: var(--bom-brand); background: color-mix(in srgb, var(--bom-brand) 9%, var(--bom-surface)); font-size: 1.05rem; }
    .bom-empty-title { margin: 0; color: var(--bom-ink); font-size: .9rem; font-weight: 800; }
    .bom-empty-copy { max-width: 390px; margin: .35rem auto 0; color: var(--bom-muted); font-size: .75rem; line-height: 1.55; }
    .bom-footer { display: flex; align-items: center; justify-content: space-between; gap: .8rem; padding: .75rem 1rem; border-top: 1px solid var(--bom-line); color: var(--bom-muted); font-size: .7rem; }
    .bom-footer .pagination { margin: 0; gap: .2rem; }
    .bom-footer .page-link { min-width: 32px; border-color: var(--bom-line); border-radius: 8px; color: var(--bom-muted); background: var(--bom-surface); font-size: .7rem; text-align: center; }
    .bom-footer .page-item.active .page-link { color: #fff; border-color: var(--bom-brand); background: var(--bom-brand); }
    .bom-alert { padding: .65rem .8rem; margin-bottom: .75rem; border: 1px solid transparent; border-radius: 12px; font-size: .78rem; }
    .bom-alert-success { color: var(--bom-success); border-color: color-mix(in srgb, var(--bom-success) 22%, var(--bom-line)); background: color-mix(in srgb, var(--bom-success) 8%, var(--bom-surface)); }
    .bom-alert-danger { color: var(--bom-danger); border-color: color-mix(in srgb, var(--bom-danger) 22%, var(--bom-line)); background: color-mix(in srgb, var(--bom-danger) 8%, var(--bom-surface)); }
    body[data-theme="dark"] .bom-hero { border-color: color-mix(in srgb, var(--bom-brand) 30%, var(--bom-line)); background: linear-gradient(120deg, color-mix(in srgb, var(--bom-brand) 18%, var(--bom-surface)) 0%, var(--bom-surface) 58%, color-mix(in srgb, var(--bom-accent) 15%, var(--bom-surface)) 100%); }
    body[data-theme="dark"] .bom-table th { background: color-mix(in srgb, var(--bom-surface-muted) 72%, var(--bom-surface)); }
    body[data-theme="dark"] .bom-btn-ghost { color: #e2e8f0 !important; border-color: #475569; background: #0f172a; }
    body[data-theme="dark"] .bom-btn-ghost:hover, body[data-theme="dark"] .bom-btn-ghost:focus-visible { color: #c7d2fe !important; border-color: #6366f1; background: #111827; }
    @media (min-width: 768px) { .app-main .page-wrap:has(.bom-page) { max-width: none; } }
    @media (max-width: 767.98px) {
        .bom-page { padding: .7rem .65rem 5rem; }
        .bom-hero { display: block; padding: 1rem; border-radius: 16px; }
        .bom-hero-main { gap: .65rem; }
        .bom-hero-icon { width: 2.35rem; height: 2.35rem; border-radius: 11px; font-size: .95rem; }
        .bom-subtitle { margin-top: .4rem; font-size: .74rem; }
        .bom-hero-actions { display: grid; grid-template-columns: 1fr 1fr 1.2fr; margin-top: .9rem; }
        .bom-hero-actions .bom-btn { min-height: 44px; padding-inline: .45rem; font-size: .68rem; }
        .bom-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem; }
        .bom-stat { padding: .72rem .75rem; border-radius: 12px; }
        .bom-stat-value { font-size: 1.15rem; }
        .bom-toolbar { padding: .8rem; }
        .bom-filter-grid { grid-template-columns: 1fr; gap: .55rem; }
        .bom-filter-actions { display: grid; grid-template-columns: 1fr auto; }
        .bom-filter-actions .bom-btn { width: 100%; min-height: 44px; }
        .bom-empty .bom-btn { min-height: 44px; }
        .bom-toolbar-meta { justify-content: space-between; margin-top: .6rem; }
        .bom-table-wrap { display: none; }
        .bom-mobile-list { display: block; }
        .bom-footer { display: block; padding: .75rem .8rem; }
        .bom-footer nav { margin-top: .65rem; }
        .bom-footer .pagination { justify-content: center; }
    }
    @media (prefers-reduced-motion: reduce) { .bom-btn, .bom-search input { transition: none; } .bom-btn:hover { transform: none; } }

    .bom-create-modal[hidden] { display: none; }
    .bom-create-modal { position: fixed; inset: 0; z-index: 1080; display: grid; place-items: center; padding: 1rem; }
    .bom-create-backdrop { position: absolute; inset: 0; background: rgba(16,24,40,.52); backdrop-filter: blur(3px); }
    .bom-create-dialog { position: relative; z-index: 1; display: flex; width: min(100%, 560px); max-height: min(720px, calc(100dvh - 2rem)); flex-direction: column; overflow: hidden; border: 1px solid var(--bom-line); border-radius: 20px; background: var(--bom-surface); box-shadow: 0 24px 64px rgba(16,24,40,.24); animation: bom-modal-in .16s ease-out; }
    .bom-create-head { display: flex; align-items: flex-start; justify-content: space-between; gap: .8rem; padding: 1.15rem 1.2rem .9rem; border-bottom: 1px solid var(--bom-line); }
    .bom-create-step { display: inline-flex; align-items: center; gap: .35rem; margin-bottom: .45rem; color: var(--bom-brand-strong); font-size: .63rem; font-weight: 850; letter-spacing: .08em; text-transform: uppercase; }
    .bom-create-step span { display: inline-grid; width: 1.25rem; height: 1.25rem; place-items: center; border-radius: 7px; color: #fff; background: var(--bom-brand); font-size: .62rem; }
    .bom-create-title { margin: 0; color: var(--bom-ink); font-size: 1.1rem; font-weight: 850; letter-spacing: -.035em; }
    .bom-create-copy { max-width: 410px; margin: .35rem 0 0; color: var(--bom-muted); font-size: .75rem; line-height: 1.5; }
    .bom-create-close { display: inline-grid; flex: 0 0 auto; width: 40px; height: 40px; place-items: center; padding: 0; border: 1px solid var(--bom-line); border-radius: 10px; color: var(--bom-muted); background: var(--bom-surface); cursor: pointer; transition: color .18s ease, border-color .18s ease, background .18s ease; }
    .bom-create-close:hover, .bom-create-close:focus-visible { color: var(--bom-brand-strong); border-color: color-mix(in srgb, var(--bom-brand) 30%, var(--bom-line)); background: color-mix(in srgb, var(--bom-brand) 5%, var(--bom-surface)); }
    .bom-create-body { overflow-y: auto; padding: 1rem 1.2rem; }
    .bom-create-label { display: block; margin: 0 0 .4rem .1rem; color: var(--bom-muted); font-size: .66rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
    .bom-create-search { position: relative; }
    .bom-create-search > i { position: absolute; left: .8rem; top: 50%; color: var(--bom-brand); font-size: .82rem; pointer-events: none; transform: translateY(-50%); }
    .bom-create-search input { width: 100%; min-height: 44px; padding: 0 2.35rem; border: 1px solid var(--bom-line); border-radius: var(--gf-radius-control, 10px); color: var(--bom-ink); background: var(--bom-surface); font: inherit; font-size: .8rem; outline: none; transition: border-color .18s ease, box-shadow .18s ease; }
    .bom-create-search input:focus { border-color: var(--bom-brand); box-shadow: 0 0 0 3px color-mix(in srgb, var(--bom-brand) 14%, transparent); }
    .bom-create-search-clear { position: absolute; top: 50%; right: .4rem; display: inline-grid; width: 32px; height: 32px; place-items: center; padding: 0; border: 0; border-radius: 8px; color: var(--bom-muted); background: transparent; cursor: pointer; transform: translateY(-50%); }
    .bom-create-search-clear:hover, .bom-create-search-clear:focus-visible { color: var(--bom-brand-strong); background: color-mix(in srgb, var(--bom-brand) 8%, var(--bom-surface)); }
    .bom-create-state { display: grid; min-height: 132px; place-items: center; padding: 1rem; color: var(--bom-muted); font-size: .75rem; line-height: 1.5; text-align: center; }
    .bom-create-state i { display: block; margin-bottom: .35rem; color: var(--bom-brand); font-size: 1.15rem; }
    .bom-create-state.is-error { color: var(--bom-danger); }
    .bom-create-results { display: grid; gap: .45rem; margin-top: .7rem; }
    .bom-create-option { display: flex; align-items: center; justify-content: space-between; gap: .8rem; width: 100%; min-height: 58px; padding: .65rem .75rem; border: 1px solid var(--bom-line); border-radius: 12px; color: var(--bom-ink); background: var(--bom-surface); font: inherit; text-align: left; cursor: pointer; transition: color .16s ease, border-color .16s ease, background .16s ease, box-shadow .16s ease; }
    .bom-create-option:hover, .bom-create-option:focus-visible { border-color: color-mix(in srgb, var(--bom-brand) 38%, var(--bom-line)); background: color-mix(in srgb, var(--bom-brand) 5%, var(--bom-surface)); box-shadow: 0 4px 12px color-mix(in srgb, var(--bom-brand) 10%, transparent); outline: none; }
    .bom-create-option[aria-selected="true"] { border-color: var(--bom-brand); background: color-mix(in srgb, var(--bom-brand) 8%, var(--bom-surface)); box-shadow: 0 0 0 3px color-mix(in srgb, var(--bom-brand) 12%, transparent); }
    .bom-create-option-main { min-width: 0; }
    .bom-create-option-code { display: block; overflow: hidden; color: var(--bom-ink); font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .76rem; font-weight: 850; text-overflow: ellipsis; white-space: nowrap; }
    .bom-create-option-name { display: block; overflow: hidden; margin-top: .2rem; color: var(--bom-muted); font-size: .7rem; text-overflow: ellipsis; white-space: nowrap; }
    .bom-create-option-check { display: inline-grid; flex: 0 0 auto; width: 24px; height: 24px; place-items: center; border: 1px solid var(--bom-line); border-radius: 8px; color: transparent; background: var(--bom-surface); font-size: .7rem; }
    .bom-create-option[aria-selected="true"] .bom-create-option-check { color: #fff; border-color: var(--bom-brand); background: var(--bom-brand); }
    .bom-create-selected { display: flex; align-items: center; gap: .5rem; margin-top: .7rem; padding: .55rem .65rem; border: 1px solid color-mix(in srgb, var(--bom-brand) 22%, var(--bom-line)); border-radius: 10px; color: var(--bom-brand-strong); background: color-mix(in srgb, var(--bom-brand) 6%, var(--bom-surface)); font-size: .7rem; font-weight: 750; }
    .bom-create-selected i { font-size: .85rem; }
    .bom-create-foot { display: flex; align-items: center; justify-content: flex-end; gap: .45rem; padding: .85rem 1.2rem; border-top: 1px solid var(--bom-line); background: color-mix(in srgb, var(--bom-surface-muted) 42%, var(--bom-surface)); }
    .bom-create-foot .bom-btn { min-width: 106px; }
    body.bom-modal-open { overflow: hidden; }
    body[data-theme="dark"] .bom-create-backdrop { background: rgba(2,8,23,.68); }
    body[data-theme="dark"] .bom-create-close { background: #0f172a; border-color: #475569; color: #e2e8f0; }
    body[data-theme="dark"] .bom-create-close:hover, body[data-theme="dark"] .bom-create-close:focus-visible { color: #c7d2fe; border-color: #6366f1; background: #111827; }
    body[data-theme="dark"] .bom-create-foot { background: color-mix(in srgb, var(--bom-surface-muted) 74%, var(--bom-surface)); }
    @keyframes bom-modal-in { from { opacity: 0; transform: translateY(8px) scale(.985); } to { opacity: 1; transform: translateY(0) scale(1); } }
    @media (max-width: 767.98px) {
        .bom-create-modal { align-items: end; padding: 0; }
        .bom-create-dialog { width: 100%; max-height: min(720px, calc(100dvh - .5rem)); border-radius: 20px 20px 0 0; }
        .bom-create-head { padding: 1rem .9rem .8rem; }
        .bom-create-body { padding: .9rem; }
        .bom-create-foot { padding: .75rem .9rem calc(.75rem + env(safe-area-inset-bottom, 0px)); }
        .bom-create-foot .bom-btn { flex: 1 1 0; min-height: 44px; }
    }
    @media (prefers-reduced-motion: reduce) { .bom-create-dialog, .bom-create-option, .bom-create-close { animation: none; transition: none; } }
</style>
@endpush

@section('content')
<main class="bom-page" aria-labelledby="bom-page-title">
    @if(session('success'))
        <div class="bom-alert bom-alert-success" role="status"><i class="bi bi-check-circle me-1" aria-hidden="true"></i>{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bom-alert bom-alert-danger" role="alert"><i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="bom-alert bom-alert-danger" role="alert"><i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>{{ $errors->first() }}</div>
    @endif

    <section class="bom-hero">
        <div class="bom-hero-main">
            <div class="bom-hero-icon" aria-hidden="true"><i class="bi bi-diagram-3"></i></div>
            <div>
                <div class="bom-eyebrow"><i class="bi bi-box-seam me-1" aria-hidden="true"></i>Master Data</div>
                <h1 id="bom-page-title" class="bom-title">BOM per SKU</h1>
                <p class="bom-subtitle">Atur komponen utama, bahan jahit, dan packing untuk menjaga standar produksi setiap SKU.</p>
            </div>
        </div>
        <div class="bom-hero-actions" aria-label="Aksi BOM">
            <a href="{{ route('master.item_boms.import_form') }}" class="bom-btn bom-btn-ghost"><i class="bi bi-upload" aria-hidden="true"></i><span>Import</span></a>
            <a href="{{ route('master.item_boms.duplicate_form') }}" class="bom-btn bom-btn-ghost"><i class="bi bi-files" aria-hidden="true"></i><span>Duplikat</span></a>
            <button type="button" class="bom-btn bom-btn-primary" data-bom-open-create aria-haspopup="dialog" aria-controls="bom-create-modal"><i class="bi bi-plus-lg" aria-hidden="true"></i><span>BOM Baru</span></button>
        </div>
    </section>

    <div id="bom-create-modal" class="bom-create-modal" data-bom-create-modal hidden>
        <div class="bom-create-backdrop" data-bom-close-create></div>
        <section class="bom-create-dialog" role="dialog" aria-modal="true" aria-labelledby="bom-create-title" aria-describedby="bom-create-copy">
            <header class="bom-create-head">
                <div>
                    <div class="bom-create-step"><span>1</span>Mulai BOM baru</div>
                    <h2 id="bom-create-title" class="bom-create-title">Pilih SKU produksi</h2>
                    <p id="bom-create-copy" class="bom-create-copy">Pilih finished good yang bisa diproduksi. Setelah itu kamu akan masuk ke form untuk menambahkan komponen BOM.</p>
                </div>
                <button type="button" class="bom-create-close" data-bom-close-create aria-label="Tutup modal BOM baru" title="Tutup"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
            </header>
            <div class="bom-create-body">
                <label class="bom-create-label" for="bom-create-search">Cari SKU finished good</label>
                <div class="bom-create-search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input id="bom-create-search" type="search" autocomplete="off" inputmode="search" placeholder="Ketik kode atau nama SKU…" aria-controls="bom-create-results" aria-describedby="bom-create-copy">
                    <button type="button" class="bom-create-search-clear" data-bom-create-clear aria-label="Hapus pencarian SKU" title="Hapus pencarian" hidden><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                </div>
                <div class="bom-create-state" data-bom-create-state aria-live="polite"><div><i class="bi bi-search" aria-hidden="true"></i>Ketik minimal 2 karakter untuk mencari SKU.</div></div>
                <div id="bom-create-results" class="bom-create-results" role="listbox" aria-label="Hasil SKU produksi"></div>
                <div class="bom-create-selected" data-bom-create-selected hidden><i class="bi bi-check-circle" aria-hidden="true"></i><span data-bom-create-selected-text></span></div>
            </div>
            <footer class="bom-create-foot">
                <button type="button" class="bom-btn bom-btn-ghost" data-bom-close-create>Batal</button>
                <button type="button" class="bom-btn bom-btn-primary" data-bom-continue-create disabled><i class="bi bi-arrow-right" aria-hidden="true"></i><span>Lanjut ke Form</span></button>
            </footer>
        </section>
    </div>

    <section class="bom-stats" aria-label="Ringkasan BOM">
        <div class="bom-stat"><span class="bom-stat-label"><i class="bi bi-diagram-3" aria-hidden="true"></i>Total BOM</span><strong class="bom-stat-value">{{ number_format((int) ($bomSummary['total'] ?? 0), 0, ',', '.') }}</strong><small class="bom-stat-note">Sesuai pencarian aktif</small></div>
        <div class="bom-stat"><span class="bom-stat-label"><i class="bi bi-check-circle" aria-hidden="true"></i>Aktif</span><strong class="bom-stat-value">{{ number_format((int) ($bomSummary['active'] ?? 0), 0, ',', '.') }}</strong><small class="bom-stat-note">Siap dipakai produksi</small></div>
        <div class="bom-stat"><span class="bom-stat-label"><i class="bi bi-pause-circle" aria-hidden="true"></i>Nonaktif</span><strong class="bom-stat-value">{{ number_format((int) ($bomSummary['inactive'] ?? 0), 0, ',', '.') }}</strong><small class="bom-stat-note">Perlu ditinjau</small></div>
        <div class="bom-stat"><span class="bom-stat-label"><i class="bi bi-list-check" aria-hidden="true"></i>Halaman ini</span><strong class="bom-stat-value">{{ number_format((int) $boms->count(), 0, ',', '.') }}</strong><small class="bom-stat-note">{{ $boms->perPage() }} item per halaman</small></div>
    </section>

    <section class="bom-card" aria-label="Daftar BOM">
        <form method="get" action="{{ route('master.item_boms.index') }}" class="bom-toolbar" data-bom-search-form>
            <div class="bom-filter-grid">
                <div class="bom-search">
                    <label for="bom-search-input">Cari BOM</label>
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input id="bom-search-input" type="search" name="q" value="{{ $q }}" autocomplete="off" inputmode="search" enterkeyhint="search" placeholder="Kode SKU, nama item, atau nama BOM" aria-label="Cari kode SKU, nama item, atau nama BOM">
                    <span class="bom-search-status" data-bom-search-status aria-live="polite">Mencari…</span>
                </div>
                <div class="bom-filter-field">
                    <label for="bom-status-filter">Status</label>
                    <select id="bom-status-filter" name="status" aria-label="Filter status BOM">
                        <option value="">Semua status</option>
                        <option value="active" @selected($status === 'active')>Aktif</option>
                        <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
                    </select>
                </div>
                <div class="bom-filter-field">
                    <label for="bom-structure-filter">Struktur</label>
                    <select id="bom-structure-filter" name="structure" aria-label="Filter struktur BOM">
                        <option value="">Semua struktur</option>
                        <option value="main" @selected($structure === 'main')>Ada bahan utama</option>
                        <option value="sewing" @selected($structure === 'sewing')>Ada bahan jahit</option>
                        <option value="packing" @selected($structure === 'packing')>Ada bahan packing</option>
                        <option value="complete" @selected($structure === 'complete')>Struktur lengkap</option>
                    </select>
                </div>
                <div class="bom-filter-actions">
                    <button type="submit" class="bom-btn bom-btn-primary"><i class="bi bi-funnel" aria-hidden="true"></i><span>Terapkan</span></button>
                    @if($hasFilters)
                        <a href="{{ route('master.item_boms.index') }}" class="bom-btn bom-btn-ghost" aria-label="Reset semua filter" title="Reset semua filter"><i class="bi bi-x-lg" aria-hidden="true"></i><span class="d-none d-lg-inline">Reset</span></a>
                    @endif
                </div>
            </div>
            <div class="bom-toolbar-meta">
                @if($q !== '')<span class="bom-filter-chip"><i class="bi bi-search" aria-hidden="true"></i><strong>{{ $q }}</strong></span>@endif
                @if($status !== '')<span class="bom-filter-chip"><i class="bi bi-activity" aria-hidden="true"></i><strong>{{ $status === 'active' ? 'Aktif' : 'Nonaktif' }}</strong></span>@endif
                @if($structure !== '')<span class="bom-filter-chip"><i class="bi bi-diagram-3" aria-hidden="true"></i><strong>{{ ['main' => 'Bahan utama', 'sewing' => 'Bahan jahit', 'packing' => 'Bahan packing', 'complete' => 'Struktur lengkap'][$structure] }}</strong></span>@endif
                @if(!$hasFilters)<span><i class="bi bi-info-circle me-1" aria-hidden="true"></i>Live search aktif · gunakan filter untuk mempersempit daftar</span>@else<span><i class="bi bi-funnel me-1" aria-hidden="true"></i>{{ collect([$q !== '', $status !== '', $structure !== ''])->filter()->count() }} filter aktif</span>@endif
            </div>
        </form>

        @if($boms->count() === 0)
            <div class="bom-empty">
                <div class="bom-empty-icon"><i class="bi bi-search" aria-hidden="true"></i></div>
                <h2 class="bom-empty-title">{{ $hasFilters ? 'BOM tidak ditemukan' : 'Belum ada BOM' }}</h2>
                <p class="bom-empty-copy">{{ $hasFilters ? 'Coba ubah kata kunci atau filter, atau reset untuk melihat semua BOM.' : 'Buat BOM pertama untuk mulai mengatur standar komponen produksi.' }}</p>
                @if($hasFilters)
                    <a href="{{ route('master.item_boms.index') }}" class="bom-btn bom-btn-ghost mt-3">Reset filter</a>
                @else
                    <a href="{{ route('master.item_boms.create') }}" class="bom-btn bom-btn-primary mt-3"><i class="bi bi-plus-lg" aria-hidden="true"></i>Buat BOM</a>
                @endif
            </div>
        @else
            <div class="bom-table-wrap">
                <table class="bom-table">
                    <caption class="visually-hidden">Daftar BOM SKU</caption>
                    <thead>
                        <tr><th class="bom-index">No.</th><th>SKU / Item</th><th>Struktur BOM</th><th>Status</th><th class="text-end">Aksi</th></tr>
                    </thead>
                    <tbody>
                        @foreach($boms as $i => $b)
                            @php $ub = $bomUsageBadges[$b->id] ?? null; @endphp
                            <tr>
                                <td class="bom-index">{{ ($boms->currentPage() - 1) * $boms->perPage() + $i + 1 }}</td>
                                <td>
                                    <a href="{{ route('master.item_boms.edit', $b) }}" class="bom-item-code">{{ $b->item?->code ?? '—' }}</a>
                                    <div class="bom-item-name">{{ $b->item?->name ?? 'Item tidak ditemukan' }}</div>
                                    @if($b->name)<div class="bom-name">{{ $b->name }}</div>@endif
                                </td>
                                <td>
                                    <div class="bom-structure"><strong>{{ (int) $b->main_material_lines_count }}</strong> utama <span class="text-muted">·</span> <strong>{{ (int) $b->sewing_supply_lines_count }}</strong> jahit <span class="text-muted">·</span> <strong>{{ (int) $b->packing_supply_lines_count }}</strong> packing</div>
                                    <div class="bom-structure-meta">{{ (int) $b->lines_count }} komponen total</div>
                                    @if($ub)<div class="bom-actual">Aktual <span class="bom-actual-value">{{ number_format($ub['kg_per_pcs'], 3, ',', '') }}/pcs</span> · {{ $ub['job_code'] }} @if($ub['status'] === 'over')<span class="bom-usage-over">· melebihi standar</span>@elseif($ub['status'] === 'under')<span class="bom-usage-under">· lebih hemat</span>@elseif($ub['status'] === 'ok')<span>· sesuai standar</span>@endif</div>@endif
                                </td>
                                <td><span class="bom-status {{ $b->active ? 'bom-status-active' : 'bom-status-inactive' }}">{{ $b->active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                <td>
                                    <div class="bom-actions">
                                        <a href="{{ route('master.item_boms.edit', $b) }}" class="bom-btn bom-btn-ghost"><i class="bi bi-pencil" aria-hidden="true"></i><span>Edit</span></a>
                                        <form method="post" action="{{ route('master.item_boms.destroy', $b) }}" data-gf-confirm data-gf-confirm-title="Hapus BOM?" data-gf-confirm-text="Semua komponen pada BOM ini ikut terhapus." data-gf-confirm-icon="warning" data-gf-confirm-ok="Ya, hapus" data-gf-confirm-cancel="Batal">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="bom-btn bom-btn-danger" aria-label="Hapus BOM {{ $b->item?->code ?? '' }}" title="Hapus BOM"><i class="bi bi-trash3" aria-hidden="true"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="bom-mobile-list">
                @foreach($boms as $b)
                    @php $ub = $bomUsageBadges[$b->id] ?? null; @endphp
                    <article class="bom-mobile-card">
                        <div class="bom-mobile-head">
                            <div>
                                <a href="{{ route('master.item_boms.edit', $b) }}" class="bom-item-code">{{ $b->item?->code ?? '—' }}</a>
                                <div class="bom-item-name">{{ $b->item?->name ?? 'Item tidak ditemukan' }}</div>
                                @if($b->name)<div class="bom-name">{{ $b->name }}</div>@endif
                            </div>
                            <span class="bom-status {{ $b->active ? 'bom-status-active' : 'bom-status-inactive' }}">{{ $b->active ? 'Aktif' : 'Nonaktif' }}</span>
                        </div>
                        <div class="bom-mobile-structure"><span><strong>{{ (int) $b->main_material_lines_count }}</strong> utama</span><span><strong>{{ (int) $b->sewing_supply_lines_count }}</strong> jahit</span><span><strong>{{ (int) $b->packing_supply_lines_count }}</strong> packing</span><span><strong>{{ (int) $b->lines_count }}</strong> total</span></div>
                        @if($ub)<div class="bom-actual">Aktual <span class="bom-actual-value">{{ number_format($ub['kg_per_pcs'], 3, ',', '') }}/pcs</span> · {{ $ub['job_code'] }} @if($ub['status'] === 'over')<span class="bom-usage-over">· melebihi standar</span>@elseif($ub['status'] === 'under')<span class="bom-usage-under">· lebih hemat</span>@elseif($ub['status'] === 'ok')<span>· sesuai standar</span>@endif</div>@endif
                        <div class="bom-mobile-actions">
                            <a href="{{ route('master.item_boms.edit', $b) }}" class="bom-btn bom-btn-primary"><i class="bi bi-pencil" aria-hidden="true"></i>Edit BOM</a>
                            <form method="post" action="{{ route('master.item_boms.destroy', $b) }}" data-gf-confirm data-gf-confirm-title="Hapus BOM?" data-gf-confirm-text="Semua komponen pada BOM ini ikut terhapus." data-gf-confirm-icon="warning" data-gf-confirm-ok="Ya, hapus" data-gf-confirm-cancel="Batal">
                                @csrf @method('DELETE')
                                <button type="submit" class="bom-btn bom-btn-danger" aria-label="Hapus BOM {{ $b->item?->code ?? '' }}" title="Hapus BOM"><i class="bi bi-trash3" aria-hidden="true"></i></button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>

            <footer class="bom-footer"><span>Menampilkan {{ $boms->firstItem() }}–{{ $boms->lastItem() }} dari {{ number_format($boms->total(), 0, ',', '.') }} BOM</span><nav aria-label="Navigasi halaman BOM">{{ $boms->links() }}</nav></footer>
        @endif
    </section>
</main>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('[data-bom-search-form]');
    const input = document.querySelector('#bom-search-input');
    const status = document.querySelector('[data-bom-search-status]');
    if (!form || !input) return;
    let timer = null;
    let submitting = false;
    const submitSearch = (delay = 450) => {
        window.clearTimeout(timer);
        timer = window.setTimeout(() => {
            if (submitting) return;
            submitting = true;
            if (status) status.classList.add('is-show');
            form.requestSubmit ? form.requestSubmit() : form.submit();
        }, delay);
    };
    input.addEventListener('input', () => submitSearch());
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') { event.preventDefault(); submitSearch(0); }
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.querySelector('[data-bom-create-modal]');
    const openButton = document.querySelector('[data-bom-open-create]');
    if (!modal || !openButton) return;

    const searchInput = modal.querySelector('#bom-create-search');
    const clearButton = modal.querySelector('[data-bom-create-clear]');
    const state = modal.querySelector('[data-bom-create-state]');
    const results = modal.querySelector('#bom-create-results');
    const selectedPanel = modal.querySelector('[data-bom-create-selected]');
    const selectedText = modal.querySelector('[data-bom-create-selected-text]');
    const continueButton = modal.querySelector('[data-bom-continue-create]');
    const closeButtons = modal.querySelectorAll('[data-bom-close-create]');
    const ajaxUrl = @json(route('master.item_boms.ajax_items'));
    const createUrl = @json(route('master.item_boms.create'));

    let searchTimer = null;
    let requestController = null;
    let requestId = 0;
    let selectedItem = null;
    let lastFocused = null;

    function setState(message, icon = 'bi-search', className = '') {
        state.className = `bom-create-state${className ? ` ${className}` : ''}`;
        state.innerHTML = `<div><i class="bi ${icon}" aria-hidden="true"></i>${message}</div>`;
        state.hidden = false;
    }

    function resetSelection() {
        selectedItem = null;
        selectedPanel.hidden = true;
        selectedText.textContent = '';
        continueButton.disabled = true;
        results.querySelectorAll('[role="option"]').forEach(option => option.setAttribute('aria-selected', 'false'));
    }

    function resetModal() {
        window.clearTimeout(searchTimer);
        if (requestController) requestController.abort();
        requestController = null;
        requestId += 1;
        searchInput.value = '';
        clearButton.hidden = true;
        results.replaceChildren();
        resetSelection();
        setState('Ketik minimal 2 karakter untuk mencari SKU.', 'bi-search');
    }

    function renderResults(items) {
        results.replaceChildren();
        if (!items.length) {
            setState('SKU tidak ditemukan. Coba kode atau nama lain.', 'bi-inbox');
            return;
        }

        state.hidden = true;
        items.forEach(item => {
            const option = document.createElement('button');
            option.type = 'button';
            option.className = 'bom-create-option';
            option.setAttribute('role', 'option');
            option.setAttribute('aria-selected', 'false');
            option.dataset.itemId = String(item.id);
            option.dataset.itemText = item.text || '';

            const [code, ...nameParts] = String(item.text || '').split(' — ');
            const main = document.createElement('span');
            main.className = 'bom-create-option-main';
            const codeNode = document.createElement('span');
            codeNode.className = 'bom-create-option-code';
            codeNode.textContent = code || item.text || 'SKU';
            const nameNode = document.createElement('span');
            nameNode.className = 'bom-create-option-name';
            nameNode.textContent = nameParts.join(' — ') || 'Finished good';
            main.append(codeNode, nameNode);

            const check = document.createElement('span');
            check.className = 'bom-create-option-check';
            check.innerHTML = '<i class="bi bi-check2" aria-hidden="true"></i>';
            option.append(main, check);
            option.addEventListener('click', () => {
                selectedItem = { id: item.id, text: item.text || '' };
                results.querySelectorAll('[role="option"]').forEach(node => node.setAttribute('aria-selected', node === option ? 'true' : 'false'));
                selectedText.textContent = `SKU dipilih: ${selectedItem.text}`;
                selectedPanel.hidden = false;
                continueButton.disabled = false;
            });
            results.appendChild(option);
        });
    }

    async function searchItems(term) {
        const normalized = term.trim();
        if (requestController) requestController.abort();
        if (normalized.length < 2) {
            results.replaceChildren();
            resetSelection();
            setState('Ketik minimal 2 karakter untuk mencari SKU.', 'bi-search');
            return;
        }

        requestController = new AbortController();
        const currentRequest = ++requestId;
        setState('Mencari SKU produksi…', 'bi-arrow-repeat');

        try {
            const url = new URL(ajaxUrl, window.location.origin);
            url.searchParams.set('type', 'finished_good');
            url.searchParams.set('q', normalized);
            const response = await fetch(url, { headers: { 'Accept': 'application/json' }, signal: requestController.signal });
            if (!response.ok) throw new Error('Search failed');
            const payload = await response.json();
            if (currentRequest !== requestId) return;
            renderResults(Array.isArray(payload.results) ? payload.results : []);
        } catch (error) {
            if (error.name === 'AbortError') return;
            if (currentRequest !== requestId) return;
            results.replaceChildren();
            setState('Pencarian gagal. Coba lagi.', 'bi-exclamation-triangle', 'is-error');
        }
    }

    function openModal() {
        lastFocused = document.activeElement;
        resetModal();
        modal.hidden = false;
        document.body.classList.add('bom-modal-open');
        openButton.setAttribute('aria-expanded', 'true');
        window.requestAnimationFrame(() => searchInput.focus());
    }

    function closeModal() {
        modal.hidden = true;
        document.body.classList.remove('bom-modal-open');
        openButton.setAttribute('aria-expanded', 'false');
        if (requestController) requestController.abort();
        if (lastFocused && typeof lastFocused.focus === 'function') lastFocused.focus();
    }

    openButton.setAttribute('aria-expanded', 'false');
    openButton.addEventListener('click', openModal);
    closeButtons.forEach(button => button.addEventListener('click', closeModal));
    searchInput.addEventListener('input', () => {
        clearButton.hidden = searchInput.value.length === 0;
        resetSelection();
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(() => searchItems(searchInput.value), 250);
    });
    clearButton.addEventListener('click', () => {
        searchInput.value = '';
        clearButton.hidden = true;
        searchInput.focus();
        resetModal();
    });
    continueButton.addEventListener('click', () => {
        if (!selectedItem) return;
        const nextUrl = new URL(createUrl, window.location.origin);
        nextUrl.searchParams.set('item_id', selectedItem.id);
        continueButton.disabled = true;
        continueButton.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span>Membuka form…</span>';
        window.location.assign(nextUrl.toString());
    });
    modal.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            event.preventDefault();
            closeModal();
            return;
        }
        if (event.key !== 'Tab') return;
        const focusable = [...modal.querySelectorAll('button:not([disabled]), input:not([disabled])')].filter(node => !node.hidden && node.offsetParent !== null);
        if (!focusable.length) return;
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    });
});
</script>
@endpush
