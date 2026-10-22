@extends('layouts.app')

@section('title', 'Kategori Item')

@php
    $fmt = fn ($number) => number_format((float) $number, 0, ',', '.');
    $activeKind = (string) request('kind', '');
    $activeKindLabel = $activeKind !== '' ? ($kindLabels[$activeKind] ?? $activeKind) : null;
    $isItemMode = $activeKind !== '';
    $user = auth()->user();
    $isAdminRole = $user && strtolower((string) $user->role) === 'admin' && ! $user->isDeveloper();
    $hasFilters = filled(request('q')) || filled(request('kind')) || filled(request('status'));
    $tabBase = request()->only(['q', 'status']);
    $tabUrl = function (?string $kind = null) use ($tabBase) {
        $params = $tabBase;
        if ($kind !== null && $kind !== '') {
            $params['kind'] = $kind;
        }

        return route('master.item_categories.index', array_filter($params, fn ($value) => $value !== null && $value !== ''));
    };
    $kindTone = [
        'product' => 'dark',
        'material' => 'blue',
        'support' => 'amber',
        'accessory' => 'slate',
        'packaging' => 'green',
        'operational' => 'amber',
        'other' => 'muted',
    ];
    $typeLabels = [
        'material' => 'Material',
        'wip' => 'WIP',
        'finished_good' => 'Finished Good',
    ];
    $defaultItemCategoryId = $itemCategoryOptions->firstWhere('kind', $activeKind)?->id;
@endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .ic-page {
            --ic-text: var(--gf-color-content, #101828);
            --ic-muted: var(--gf-color-muted, #667085);
            --ic-surface: var(--gf-color-surface, #fff);
            --ic-soft: var(--gf-color-surface-muted, #f2f4f7);
            --ic-line: var(--gf-color-line, #e4e7ec);
            --ic-brand: var(--gf-color-brand, #5b5ce2);
            --ic-brand-strong: var(--gf-color-brand-strong, #4142bb);
            --ic-shadow: var(--gf-shadow-sm, 0 1px 2px rgba(16,24,40,.04), 0 4px 12px rgba(16,24,40,.04));
            display: grid;
            gap: 1rem;
            min-width: 0;
        }
        .ic-page .gf-master-header { border-color: #e0e2fb; background: linear-gradient(120deg, #f4f3ff 0%, #fff 58%, #edfcff 100%); box-shadow: 0 12px 30px rgba(48, 49, 125, .07); }
        .ic-page .gf-master-title { color: var(--ic-text); font-size: clamp(1.35rem, 2vw, 1.8rem); letter-spacing: -.045em; }
        .ic-page .gf-master-desc { color: var(--ic-muted); max-width: 720px; line-height: 1.55; }
        .ic-page .gf-master-eyebrow { color: var(--ic-brand-strong); }
        .ic-actions { display: flex; justify-content: flex-end; gap: .5rem; flex-wrap: wrap; }
        .ic-btn { display: inline-flex; align-items: center; justify-content: center; gap: .42rem; min-height: 38px; padding: .5rem .82rem; border: 1px solid var(--ic-line); border-radius: var(--gf-radius-control, 10px); color: var(--ic-text); background: var(--ic-surface); font: inherit; font-size: .75rem; font-weight: 800; line-height: 1; text-decoration: none; transition: color .18s ease, background .18s ease, border-color .18s ease, transform .18s ease, box-shadow .18s ease; }
        .ic-btn:hover { color: var(--ic-brand-strong); border-color: color-mix(in srgb, var(--ic-brand) 38%, var(--ic-line)); transform: translateY(-1px); }
        .ic-btn:focus-visible, .ic-tab:focus-visible, .ic-icon-btn:focus-visible { outline: 3px solid color-mix(in srgb, var(--ic-brand) 30%, transparent); outline-offset: 2px; }
        .ic-btn-primary { color: #fff; border-color: var(--ic-brand); background: var(--ic-brand); box-shadow: 0 7px 14px rgba(91, 92, 226, .2); }
        .ic-btn-primary:hover { color: #fff; border-color: var(--ic-brand-strong); background: var(--ic-brand-strong); }
        .ic-btn-sm { min-height: 32px; padding: .42rem .62rem; font-size: .7rem; }
        .ic-alert { display: flex; align-items: flex-start; gap: .55rem; padding: .72rem .85rem; border: 1px solid; border-radius: 12px; font-size: .76rem; line-height: 1.45; }
        .ic-alert-success { color: #067647; border-color: #abefc6; background: #ecfdf3; }
        .ic-alert-danger { color: #b42318; border-color: #fecdca; background: #fef3f2; }
        .ic-alert i { margin-top: .05rem; }
        .ic-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; }
        .ic-stat { position: relative; overflow: hidden; min-height: 108px; padding: 1rem; border: 1px solid var(--ic-line); border-radius: var(--gf-radius-card, 16px); background: var(--ic-surface); box-shadow: var(--ic-shadow); }
        .ic-stat::after { position: absolute; right: -22px; bottom: -28px; width: 88px; height: 88px; border-radius: 50%; background: #eeefff; content: ''; }
        .ic-stat:nth-child(2)::after { background: #ecfdf3; }
        .ic-stat:nth-child(3)::after { background: #eff8ff; }
        .ic-stat:nth-child(4)::after { background: #fffaeb; }
        .ic-stat-top { position: relative; z-index: 1; display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
        .ic-stat-label { color: var(--ic-muted); font-size: .67rem; font-weight: 850; letter-spacing: .04em; text-transform: uppercase; }
        .ic-stat-icon { display: grid; width: 30px; height: 30px; place-items: center; border-radius: 10px; color: var(--ic-brand-strong); background: #eeefff; font-size: .85rem; }
        .ic-stat:nth-child(2) .ic-stat-icon { color: #067647; background: #ecfdf3; }
        .ic-stat:nth-child(3) .ic-stat-icon { color: #175cd3; background: #eff8ff; }
        .ic-stat:nth-child(4) .ic-stat-icon { color: #a15c07; background: #fffaeb; }
        .ic-stat-value { position: relative; z-index: 1; margin-top: .45rem; color: var(--ic-text); font-size: 1.35rem; font-weight: 900; letter-spacing: -.045em; font-variant-numeric: tabular-nums; }
        .ic-stat-note { position: relative; z-index: 1; margin-top: .12rem; color: var(--ic-muted); font-size: .68rem; }
        .ic-panel { min-width: 0; border-radius: var(--gf-radius-card, 16px); }
        .ic-panel .gf-panel-header { padding: 1rem 1.15rem; }
        .ic-panel .gf-panel-body { padding: 1rem 1.15rem; }
        .ic-list-panel .gf-panel-body { padding: 0; }
        .ic-panel .gf-panel-title { color: var(--ic-text); font-size: .88rem; }
        .ic-panel .gf-subtext { color: var(--ic-muted); margin-top: .16rem; }
        .ic-filter { display: grid; grid-template-columns: minmax(220px, 1fr) minmax(150px, .28fr) auto; align-items: end; gap: .7rem; }
        .ic-field { display: grid; gap: .3rem; min-width: 0; }
        .ic-label { color: var(--ic-muted); font-size: .68rem; font-weight: 850; letter-spacing: .035em; text-transform: uppercase; }
        .ic-control { min-height: 40px; border-radius: 10px; border-color: var(--ic-line); color: var(--ic-text); background: var(--ic-surface); box-shadow: none; font-size: .8rem; }
        .ic-control:focus { border-color: var(--ic-brand); box-shadow: 0 0 0 .2rem rgba(91, 92, 226, .12); }
        .ic-search { position: relative; }
        .ic-search i { position: absolute; top: 50%; left: .75rem; z-index: 1; color: var(--ic-muted); transform: translateY(-50%); pointer-events: none; }
        .ic-search .ic-control { padding-left: 2.05rem; }
        .ic-search .ic-search-clear { position: absolute; top: 50%; right: .4rem; display: inline-grid; width: 28px; height: 28px; place-items: center; padding: 0; border: 0; border-radius: 7px; color: var(--ic-muted); background: transparent; transform: translateY(-50%); }
        .ic-search .ic-search-clear[hidden] { display: none; }
        .ic-search .ic-search-clear:hover { color: var(--ic-text); background: var(--ic-soft); }
        .ic-search .ic-search-clear:focus-visible { outline: 3px solid color-mix(in srgb, var(--ic-brand) 30%, transparent); outline-offset: 1px; }
        .ic-search .ic-control { padding-right: 2.35rem; }
        .ic-filter-actions { display: flex; align-items: center; gap: .45rem; }
        .ic-tabs { display: flex; gap: .18rem; padding: .3rem; border-bottom: 1px solid var(--ic-line); background: color-mix(in srgb, var(--ic-soft) 70%, var(--ic-surface) 30%); overflow-x: auto; scrollbar-width: thin; }
        .ic-tab { display: inline-flex; flex: 0 0 auto; align-items: center; gap: .4rem; min-height: 34px; padding: .42rem .68rem; border: 1px solid transparent; border-radius: 9px; color: var(--ic-muted); background: transparent; font: inherit; font-size: .71rem; font-weight: 800; text-decoration: none; white-space: nowrap; }
        .ic-tab:hover { color: var(--ic-brand-strong); background: color-mix(in srgb, var(--ic-brand) 8%, var(--ic-surface)); }
        .ic-tab.is-active { color: var(--ic-brand-strong); border-color: #dfe1ff; background: var(--ic-surface); box-shadow: 0 1px 4px rgba(16,24,40,.08); }
        .ic-tab-count { display: inline-flex; min-width: 20px; justify-content: center; padding: .13rem .3rem; border-radius: 999px; color: var(--ic-muted); background: var(--ic-soft); font-size: .62rem; font-variant-numeric: tabular-nums; }
        .ic-tab.is-active .ic-tab-count { color: var(--ic-brand-strong); background: #eeefff; }
        .ic-table-wrap { overflow-x: auto; overscroll-behavior-inline: contain; }
        .ic-table { min-width: 760px; margin: 0; }
        .ic-table thead th { padding: .7rem .7rem; color: var(--ic-muted); background: color-mix(in srgb, var(--ic-soft) 72%, var(--ic-surface) 28%); font-size: .63rem; font-weight: 850; letter-spacing: .045em; text-transform: uppercase; white-space: nowrap; }
        .ic-table tbody td { padding: .72rem .7rem; color: var(--ic-text); vertical-align: middle; border-bottom-color: var(--ic-line); font-size: .75rem; }
        .ic-table tbody tr:hover { background: color-mix(in srgb, var(--ic-brand) 4%, var(--ic-surface)); }
        .ic-table tbody tr:last-child td { border-bottom: 0; }
        .ic-code { color: var(--ic-text); font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .72rem; font-weight: 800; }
        .ic-name { color: var(--ic-text); font-weight: 800; }
        .ic-muted { color: var(--ic-muted); font-size: .69rem; }
        .ic-pill { display: inline-flex; align-items: center; gap: .35rem; min-height: 23px; padding: 0 .52rem; border: 1px solid transparent; border-radius: 999px; font-size: .65rem; font-weight: 800; white-space: nowrap; }
        .ic-pill::before { width: 5px; height: 5px; border-radius: 50%; background: currentColor; content: ''; }
        .ic-pill-active { color: #067647; background: #ecfdf3; border-color: #abefc6; }
        .ic-pill-inactive { color: #b42318; background: #fef3f2; border-color: #fecdca; }
        .ic-pill-warning { color: #a15c07; background: #fffaeb; border-color: #fedf89; }
        .ic-kind { display: inline-flex; align-items: center; gap: .35rem; padding: .28rem .52rem; border: 1px solid #e4e7ec; border-radius: 999px; color: #475467; background: #f2f4f7; font-size: .66rem; font-weight: 800; white-space: nowrap; }
        .ic-kind::before { width: 6px; height: 6px; border-radius: 50%; background: currentColor; content: ''; }
        .ic-kind-blue { color: #175cd3; background: #eff8ff; border-color: #b2ddff; }
        .ic-kind-amber { color: #a15c07; background: #fffaeb; border-color: #fedf89; }
        .ic-kind-green { color: #067647; background: #ecfdf3; border-color: #abefc6; }
        .ic-actions-cell { display: flex; justify-content: flex-end; align-items: center; gap: .35rem; min-width: 145px; }
        .ic-icon-btn { display: inline-grid; width: 30px; height: 30px; place-items: center; padding: 0; border: 1px solid transparent; border-radius: 8px; color: var(--ic-muted); background: transparent; }
        .ic-icon-btn:hover { color: #b42318; border-color: #fecdca; background: #fef3f2; }
        .ic-icon-btn:disabled { cursor: not-allowed; opacity: .45; }
        .ic-empty { display: grid; place-items: center; min-height: 210px; padding: 1.25rem; text-align: center; }
        .ic-empty-icon { display: grid; width: 42px; height: 42px; place-items: center; margin-bottom: .55rem; border-radius: 13px; color: var(--ic-brand-strong); background: #eeefff; font-size: 1.1rem; }
        .ic-empty-title { color: var(--ic-text); font-size: .85rem; font-weight: 850; }
        .ic-empty-text { max-width: 330px; margin: .25rem 0 .75rem; color: var(--ic-muted); font-size: .72rem; line-height: 1.5; }
        .ic-footer { display: flex; align-items: center; justify-content: space-between; gap: .8rem; padding: .85rem 1.15rem; border-top: 1px solid var(--ic-line); }
        .ic-footer-copy { color: var(--ic-muted); font-size: .7rem; }
        .ic-footer-copy strong { color: var(--ic-text); }
        .ic-footer .pagination { margin: 0; }
        .ic-footer .page-link { color: var(--ic-brand-strong); border-color: var(--ic-line); background: var(--ic-surface); font-size: .7rem; }
        .ic-modal .modal-content { overflow: hidden; border: 1px solid var(--ic-line); border-radius: 18px; background: var(--ic-surface); box-shadow: 0 24px 60px rgba(15,23,42,.18); }
        .ic-modal .modal-header { padding: 1rem 1.15rem; border-bottom-color: var(--ic-line); background: color-mix(in srgb, var(--ic-soft) 60%, var(--ic-surface)); }
        .ic-modal .modal-title { color: var(--ic-text); font-size: .95rem; font-weight: 900; }
        .ic-modal .modal-body { padding: 1.15rem; }
        .ic-modal .modal-footer { padding: .85rem 1.15rem; border-top-color: var(--ic-line); }
        .ic-modal-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .85rem; }
        .ic-modal-field { display: grid; gap: .3rem; }
        .ic-modal-field-full { grid-column: 1 / -1; }
        .ic-modal-help { color: var(--ic-muted); font-size: .68rem; line-height: 1.45; }
        .ic-modal .form-check-label { color: var(--ic-text); font-size: .76rem; font-weight: 700; }
        #itemFormModal .form-control, #itemFormModal .form-select { min-height: 42px; }
        #itemFormModal .modal-footer { align-items: center; gap: .5rem; }
        #itemFormModal .modal-footer .ic-modal-help { text-decoration: none; }
        body[data-theme="dark"] .ic-page .gf-master-header { border-color: #334155; background: linear-gradient(120deg, #17203a 0%, #11263f 58%, #0e2b35 100%); }
        body[data-theme="dark"] .ic-tab.is-active { border-color: #334155; background: #11263f; }
        body[data-theme="dark"] .ic-table thead th { background: #132a45; }
        body[data-theme="dark"] .ic-kind { color: #cbd5e1; border-color: #334155; background: #1e293b; }
        body[data-theme="dark"] .ic-kind-blue { color: #93c5fd; background: rgba(29,78,216,.2); border-color: rgba(37,99,235,.4); }
        body[data-theme="dark"] .ic-kind-amber { color: #fcd34d; background: rgba(146,64,14,.2); border-color: rgba(217,119,6,.4); }
        body[data-theme="dark"] .ic-kind-green { color: #86efac; background: rgba(22,101,52,.2); border-color: rgba(34,197,94,.4); }
        body[data-theme="dark"] .ic-pill-active { color: #86efac; background: rgba(22,101,52,.2); border-color: rgba(34,197,94,.4); }
        body[data-theme="dark"] .ic-pill-inactive { color: #fda4af; background: rgba(159,18,57,.2); border-color: rgba(244,63,94,.4); }
        body[data-theme="dark"] .ic-pill-warning { color: #fcd34d; background: rgba(146,64,14,.2); border-color: rgba(217,119,6,.4); }
        body[data-theme="dark"] .ic-modal .modal-header { background: #132a45; }
        @media (max-width: 1050px) { .ic-filter { grid-template-columns: minmax(180px, 1fr) minmax(130px, .4fr); } .ic-filter-actions { grid-column: 1 / -1; } }
        @media (max-width: 900px) { .ic-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 767px) { .ic-page .gf-master-header { padding: 1rem; } .ic-page .gf-master-header-layout, .ic-actions, .ic-actions .ic-btn { width: 100%; } .ic-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(145px, 1fr)); } .ic-actions .ic-btn { min-height: 44px; } .ic-filter { grid-template-columns: 1fr; } .ic-filter-actions { display: grid; grid-template-columns: 1fr 1fr; width: 100%; } .ic-filter-actions .ic-btn { width: 100%; } .ic-footer { align-items: flex-start; flex-direction: column; } .ic-footer > :last-child { width: 100%; overflow-x: auto; } .ic-modal-grid { grid-template-columns: 1fr; } .ic-modal-field-full { grid-column: auto; } #itemFormModal .form-control, #itemFormModal .form-select, #categoryModal .form-control, #categoryModal .form-select { min-height: 44px; } .ic-modal .modal-dialog { margin: .5rem; } .ic-modal .modal-footer { align-items: stretch; flex-wrap: wrap; } .ic-modal .modal-footer .ic-modal-help { flex-basis: 100%; min-height: 32px; } .ic-modal .modal-footer .ic-btn { flex: 1 1 0; min-height: 44px; } }
        @media (max-width: 430px) { .ic-summary { gap: .55rem; } .ic-stat { min-height: 98px; padding: .78rem; } .ic-stat-value { font-size: 1.08rem; } .ic-stat-label { font-size: .61rem; } .ic-actions { grid-template-columns: 1fr; } }
        /* Final component contract: shared controls and modal rhythm. */
        .ic-btn { appearance: none; cursor: pointer; min-height: 40px; padding: .58rem .9rem; border-color: var(--ic-line); border-radius: 10px; font-size: .73rem; font-weight: 780; letter-spacing: -.01em; box-shadow: 0 1px 2px rgba(16,24,40,.03); }
        .ic-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(16,24,40,.08); }
        .ic-btn:active { transform: translateY(0); box-shadow: none; }
        .ic-btn:disabled, .ic-btn[disabled] { cursor: not-allowed; opacity: .55; pointer-events: none; transform: none; box-shadow: none; }
        .ic-btn-primary { color: #fff; border-color: var(--ic-brand); background: var(--ic-brand); box-shadow: 0 7px 14px rgba(91,92,226,.2); }
        .ic-btn-primary:hover, .ic-btn-primary:focus-visible { color: #fff; border-color: var(--ic-brand-strong); background: var(--ic-brand-strong); box-shadow: 0 9px 18px rgba(65,66,187,.24); }
        .ic-actions { align-items: center; gap: .55rem; }
        .ic-actions .ic-btn { white-space: nowrap; }
        .ic-modal .modal-dialog { max-width: 640px; margin-block: .75rem; }
        #itemFormModal .modal-dialog { max-width: 720px; }
        .ic-modal .modal-content { border-radius: 18px; box-shadow: 0 24px 56px rgba(48,49,125,.16); }
        .ic-modal .modal-header { display: grid; grid-template-columns: 36px minmax(0, 1fr) 40px; align-items: start; gap: .7rem; padding: 1rem 1.15rem; }
        .ic-modal-heading { display: contents; }
        .ic-modal-icon { display: grid; width: 36px; height: 36px; place-items: center; border-radius: 11px; color: var(--ic-brand-strong); background: #eeefff; font-size: .88rem; }
        .ic-modal-kicker { margin-top: .03rem; color: var(--ic-brand-strong); font-size: .59rem; font-weight: 850; letter-spacing: .1em; text-transform: uppercase; }
        .ic-modal .modal-title { margin-top: .15rem; font-size: 1.02rem; letter-spacing: -.03em; }
        .ic-modal .modal-header .ic-modal-help { margin-top: .25rem; }
        .ic-modal .btn-close { grid-column: 3; width: 40px; height: 40px; margin-top: -.15rem; padding: .75rem; border-radius: 9px; }
        .ic-modal .modal-body { padding: 1.1rem 1.15rem 1.2rem; background: color-mix(in srgb, var(--ic-soft) 28%, var(--ic-surface)); }
        .ic-modal-grid { gap: .9rem; }
        .ic-modal-field { min-width: 0; }
        .ic-modal .ic-label { margin-bottom: .05rem; }
        .ic-modal .ic-control { min-height: 42px; border-color: #d0d5dd; border-radius: 10px; background: var(--ic-surface); font-size: .76rem; }
        .ic-modal .ic-control:focus { border-color: var(--ic-brand); box-shadow: 0 0 0 3px rgba(91,92,226,.12); }
        .ic-modal .form-check { display: flex; align-items: center; gap: .45rem; min-height: 42px; padding: .65rem .75rem; border: 1px solid var(--ic-line); border-radius: 10px; background: var(--ic-surface); }
        .ic-modal .form-check-input { margin: 0; }
        .ic-modal .form-check-label { line-height: 1.35; }
        .ic-modal .modal-footer { display: flex; align-items: center; gap: .55rem; padding: .8rem 1.15rem; background: var(--ic-surface); }
        .ic-modal .modal-footer .ic-btn { min-height: 40px; }
        .ic-modal .modal-footer .ic-modal-help { color: var(--ic-muted); font-weight: 650; text-decoration: none; }
        .ic-modal .modal-footer .ic-modal-help:hover { color: var(--ic-brand-strong); }
        body[data-theme="dark"] .ic-modal .modal-body { background: #0f172a; }
        body[data-theme="dark"] .ic-modal .modal-header, body[data-theme="dark"] .ic-modal .modal-footer { background: #111827; }
        body[data-theme="dark"] .ic-modal .modal-content, body[data-theme="dark"] .ic-modal .form-check { border-color: #334155; }
        body[data-theme="dark"] .ic-modal .ic-control { border-color: #475569; background: #0f172a; color: #f8fafc; }
        body[data-theme="dark"] .ic-modal .ic-control::placeholder { color: #94a3b8; }
        body[data-theme="dark"] .ic-modal .form-check { background: #111827; }
        @media (prefers-reduced-motion: reduce) { .ic-page *, .ic-page *::before, .ic-page *::after { transition-duration: .01ms !important; animation-duration: .01ms !important; } }
    </style>
@endpush

@section('content')
    <x-gf.page class="ic-page" eyebrow="Master Data" title="Kategori item" description="Kelola pengelompokan produk jadi, bahan, pendukung, accessories, packaging, dan kebutuhan operasional.">
        <x-slot:actions>
            <div class="ic-actions">
                <a class="ic-btn" href="{{ route('master.items.index') }}"><i class="bi bi-box-seam" aria-hidden="true"></i><span>Master item</span></a>
                @if ($isItemMode && ! $isAdminRole)
                    <button class="ic-btn ic-btn-primary" type="button" id="btnAddItem" data-bs-toggle="modal" data-bs-target="#itemFormModal"><i class="bi bi-plus-lg" aria-hidden="true"></i><span>Tambah item</span></button>
                @endif
                <button class="ic-btn {{ $isItemMode ? '' : 'ic-btn-primary' }}" type="button" id="btnAddCategory" data-bs-toggle="modal" data-bs-target="#categoryModal"><i class="bi bi-plus-lg" aria-hidden="true"></i><span>Tambah kategori</span></button>
            </div>
        </x-slot:actions>

        @if (session('success'))
            <div class="ic-alert ic-alert-success" role="status"><i class="bi bi-check-circle-fill" aria-hidden="true"></i><span>{{ session('success') }}</span></div>
        @endif
        @if (session('error'))
            <div class="ic-alert ic-alert-danger" role="alert"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i><span>{{ session('error') }}</span></div>
        @endif
        @if ($errors->any())
            <div class="ic-alert ic-alert-danger" role="alert"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i><span>{{ $errors->first() }}</span></div>
        @endif

        <div class="ic-summary" aria-label="Ringkasan kategori item">
            <div class="ic-stat"><div class="ic-stat-top"><span class="ic-stat-label">Total kategori</span><span class="ic-stat-icon"><i class="bi bi-collection" aria-hidden="true"></i></span></div><div class="ic-stat-value">{{ $fmt($totalCategories) }}</div><div class="ic-stat-note">Semua kelompok master</div></div>
            <div class="ic-stat"><div class="ic-stat-top"><span class="ic-stat-label">Kategori aktif</span><span class="ic-stat-icon"><i class="bi bi-check2-circle" aria-hidden="true"></i></span></div><div class="ic-stat-value">{{ $fmt($activeCount) }}</div><div class="ic-stat-note">Siap dipakai di item</div></div>
            <div class="ic-stat"><div class="ic-stat-top"><span class="ic-stat-label">Dipakai item</span><span class="ic-stat-icon"><i class="bi bi-link-45deg" aria-hidden="true"></i></span></div><div class="ic-stat-value">{{ $fmt($usedCount) }}</div><div class="ic-stat-note">Memiliki item terkait</div></div>
            <div class="ic-stat"><div class="ic-stat-top"><span class="ic-stat-label">Belum terpakai</span><span class="ic-stat-icon"><i class="bi bi-inbox" aria-hidden="true"></i></span></div><div class="ic-stat-value">{{ $fmt($emptyCount) }}</div><div class="ic-stat-note">Bisa dihapus jika tanpa referensi</div></div>
        </div>

        <x-gf.panel class="ic-panel" title="Cari dan filter" subtitle="Gunakan pencarian untuk menemukan kategori atau item dengan cepat.">
            <form class="ic-filter" method="GET" action="{{ route('master.item_categories.index') }}">
                <div class="ic-field"><label class="ic-label" for="category-search">Pencarian</label><span class="ic-search"><i class="bi bi-search" aria-hidden="true"></i><input class="form-control ic-control" id="category-search" type="search" name="q" value="{{ request('q') }}" placeholder="{{ $isItemMode ? 'Kode atau nama item' : 'Kode atau nama kategori' }}" autocomplete="off"><button class="ic-search-clear" type="button" data-category-search-clear aria-label="Hapus pencarian" hidden><i class="bi bi-x-lg" aria-hidden="true"></i></button></span></div>
                <label class="ic-field"><span class="ic-label">Status</span><select class="form-select ic-control" name="status"><option value="">Semua status</option><option value="active" @selected(request('status') === 'active')>Aktif</option><option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option></select></label>
                <input type="hidden" name="kind" value="{{ $activeKind }}">
                <div class="ic-filter-actions"><button class="ic-btn ic-btn-primary" type="submit"><i class="bi bi-funnel" aria-hidden="true"></i><span>Terapkan</span></button>@if ($hasFilters)<a class="ic-btn" href="{{ route('master.item_categories.index') }}"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i><span>Reset</span></a>@endif</div>
            </form>
        </x-gf.panel>

        <x-gf.panel class="ic-panel ic-list-panel" :flush="true" title="{{ $isItemMode ? 'Item dalam kelompok' : 'Daftar kategori' }}" subtitle="{{ $isItemMode ? 'Review item yang masuk ke kelompok ' . $activeKindLabel . '.' : 'Kelola kategori dan pastikan referensinya tetap aman.' }}">
            <nav class="ic-tabs" aria-label="Filter kelompok item">
                <a class="ic-tab {{ $activeKind === '' ? 'is-active' : '' }}" href="{{ $tabUrl(null) }}" @if($activeKind === '') aria-current="page" @endif><i class="bi bi-grid" aria-hidden="true"></i><span>Semua kategori</span><span class="ic-tab-count">{{ $fmt($totalCategories) }}</span></a>
                @foreach ($kindLabels as $key => $label)
                    <a class="ic-tab {{ $activeKind === $key ? 'is-active' : '' }}" href="{{ $tabUrl($key) }}" title="{{ $fmt($kindCounts[$key] ?? 0) }} kategori, {{ $fmt($kindItemCounts[$key] ?? 0) }} item" @if($activeKind === $key) aria-current="page" @endif><span>{{ $label }}</span><span class="ic-tab-count">{{ $fmt($kindItemCounts[$key] ?? 0) }}</span></a>
                @endforeach
            </nav>

            @if ($isItemMode)
                @if ($kindItems->isEmpty())
                    <div class="ic-empty"><div class="ic-empty-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></div><div class="ic-empty-title">Belum ada item di kelompok ini</div><p class="ic-empty-text">Ubah kata kunci atau cek kembali klasifikasi item di Master item.</p><div class="d-flex flex-wrap justify-content-center gap-2">@if(! $isAdminRole)<button class="ic-btn ic-btn-primary ic-btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#itemFormModal" id="btnEmptyAddItem"><i class="bi bi-plus-lg" aria-hidden="true"></i><span>Tambah item</span></button>@endif<a class="ic-btn ic-btn-sm" href="{{ route('master.items.index') }}"><i class="bi bi-arrow-right" aria-hidden="true"></i><span>Buka Master item</span></a></div></div>
                @else
                    <div class="ic-table-wrap"><table class="table align-middle ic-table"><thead><tr><th class="text-center">No.</th><th>Kode item</th><th>Nama item</th><th>Kategori</th><th>Tipe</th><th>Produksi</th><th>Satuan</th><th class="text-center">Barcode</th><th class="text-end">HPP</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
                        @foreach ($kindItems as $item)
                            @php $hpp = (float) ($item->active_unit_cost ?? 0); @endphp
                            <tr><td class="text-center ic-muted">{{ $kindItems->firstItem() + $loop->index }}</td><td><span class="ic-code">{{ $item->code }}</span></td><td><div class="ic-name">{{ $item->name }}</div><div class="ic-muted">{{ $item->active ? 'Aktif di master item' : 'Nonaktif di master item' }}</div></td><td>@if($item->category)<div class="ic-code">{{ $item->category->code }}</div><div class="ic-muted">{{ $item->category->name }}</div>@else<span class="ic-muted">-</span>@endif</td><td><span class="ic-muted">{{ $typeLabels[$item->type] ?? $item->type }}</span></td><td>@if(in_array($item->type, ['finished_good', 'wip'], true))<div class="ic-name">{{ $item->supply_mode_label }}</div><div class="ic-muted">Default: {{ $item->default_supply_source_label }}</div>@else<span class="ic-muted">-</span>@endif</td><td><span class="ic-muted">{{ $item->unit }}</span></td><td class="text-center">@if($item->barcodes_count > 0)<span class="ic-pill ic-pill-active">{{ $fmt($item->barcodes_count) }}</span>@else<span class="ic-pill ic-pill-warning">0</span>@endif</td><td class="text-end">@if($hpp > 0)<span class="fw-semibold">Rp {{ $fmt($hpp) }}</span>@else<span class="ic-muted">Kosong</span>@endif</td><td>@if($item->active)<span class="ic-pill ic-pill-active">Aktif</span>@else<span class="ic-pill ic-pill-inactive">Nonaktif</span>@endif</td><td class="text-end"><button class="ic-btn ic-btn-sm btn-edit-item" type="button" data-bs-toggle="modal" data-bs-target="#itemFormModal" data-item-edit-url="{{ route('master.items.update', $item) }}" data-item-edit-full-url="{{ route('master.items.edit', $item) }}" data-item-edit-code="{{ $item->code }}" data-item-edit-name="{{ $item->name }}" data-item-edit-sku="{{ $item->sku }}" data-item-edit-type="{{ $item->type }}" data-item-edit-category="{{ $item->item_category_id }}" data-item-edit-stock-unit="{{ $item->stock_unit ?: $item->unit }}" data-item-edit-purchase-unit="{{ $item->purchase_unit ?: $item->unit }}" data-item-edit-conversion="{{ $item->purchase_conversion_factor ?: 1 }}" data-item-edit-production-source="{{ $item->production_source }}" data-item-edit-can-buy="{{ $item->can_buy ? 1 : 0 }}" data-item-edit-can-make="{{ $item->can_make ? 1 : 0 }}" data-item-edit-default-source="{{ $item->default_supply_source }}" data-item-edit-active="{{ $item->active ? 1 : 0 }}" data-item-edit-type-option="{{ $item->item_type_option_id }}" data-item-edit-allocation="{{ $item->default_allocation ?: 'hpp' }}" data-item-edit-treatment="{{ $item->purchase_treatment_id }}" data-item-edit-expense-account="{{ $item->default_expense_account_id }}"><span>Edit</span><i class="bi bi-pencil-square" aria-hidden="true"></i></button></td></tr>
                        @endforeach
                    </tbody></table></div>
                @endif
            @elseif ($categories->isEmpty())
                <div class="ic-empty"><div class="ic-empty-icon"><i class="bi bi-collection" aria-hidden="true"></i></div><div class="ic-empty-title">Kategori tidak ditemukan</div><p class="ic-empty-text">Ubah filter atau buat kategori baru untuk melanjutkan pengelolaan master item.</p><button class="ic-btn ic-btn-primary ic-btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#categoryModal" id="btnEmptyAddCategory"><i class="bi bi-plus-lg" aria-hidden="true"></i><span>Tambah kategori</span></button></div>
            @else
                <div class="ic-table-wrap"><table class="table align-middle ic-table"><thead><tr><th class="text-center">No.</th><th>Kode</th><th>Nama kategori</th><th>Kelompok</th><th class="text-center">Item</th><th>Referensi</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
                    @foreach ($categories as $cat)
                        @php $tone = $kindTone[$cat->kind] ?? 'muted'; $referenceCount = (int) $cat->items_count + (int) $cat->piece_rates_count + (int) $cat->piecework_payroll_lines_count + (int) $cat->cutting_job_bundles_count + (int) $cat->supplier_category_mapping_count; @endphp
                        <tr><td class="text-center ic-muted">{{ $categories->firstItem() + $loop->index }}</td><td><span class="ic-code">{{ $cat->code }}</span></td><td><div class="ic-name">{{ $cat->name }}</div><div class="ic-muted">{{ $cat->items_count > 0 ? 'Dipakai di master item' : 'Belum dipakai item' }}</div></td><td><span class="ic-kind {{ $tone === 'blue' ? 'ic-kind-blue' : ($tone === 'amber' ? 'ic-kind-amber' : ($tone === 'green' ? 'ic-kind-green' : '')) }}">{{ $cat->kind_label }}</span></td><td class="text-center">@if($cat->items_count > 0)<span class="ic-pill ic-pill-active">{{ $fmt($cat->items_count) }} item</span>@else<span class="ic-pill ic-pill-warning">0 item</span>@endif</td><td><span class="ic-muted">{{ $referenceCount > 0 ? $fmt($referenceCount) . ' referensi' : 'Belum ada referensi' }}</span></td><td>@if($cat->active)<span class="ic-pill ic-pill-active">Aktif</span>@else<span class="ic-pill ic-pill-inactive">Nonaktif</span>@endif</td><td><div class="ic-actions-cell"><button class="ic-btn ic-btn-sm btn-edit-category" type="button" data-id="{{ $cat->id }}" data-code="{{ $cat->code }}" data-name="{{ $cat->name }}" data-kind="{{ $cat->kind }}" data-active="{{ $cat->active ? 1 : 0 }}" data-bs-toggle="modal" data-bs-target="#categoryModal"><i class="bi bi-pencil-square" aria-hidden="true"></i><span>Edit</span></button>@if($referenceCount === 0)<form method="POST" action="{{ route('master.item_categories.destroy', $cat) }}" data-confirm-delete data-category-name="{{ $cat->name }}">@csrf @method('DELETE')<button class="ic-icon-btn" type="submit" aria-label="Hapus kategori {{ $cat->name }}" title="Hapus kategori"><i class="bi bi-trash3" aria-hidden="true"></i></button></form>@else<button class="ic-icon-btn" type="button" disabled aria-label="Kategori {{ $cat->name }} tidak dapat dihapus" title="Masih memiliki {{ $referenceCount }} referensi"><i class="bi bi-trash3" aria-hidden="true"></i></button>@endif</div></td></tr>
                    @endforeach
                </tbody></table></div>
            @endif

            <div class="ic-footer"><div class="ic-footer-copy">@if($isItemMode) @if($kindItems->total() > 0) Menampilkan <strong>{{ $kindItems->firstItem() }}–{{ $kindItems->lastItem() }}</strong> dari <strong>{{ $kindItems->total() }}</strong> item {{ strtolower($activeKindLabel) }} @else Tidak ada item {{ strtolower($activeKindLabel) }}. @endif @elseif($categories->total() > 0) Menampilkan <strong>{{ $categories->firstItem() }}–{{ $categories->lastItem() }}</strong> dari <strong>{{ $categories->total() }}</strong> kategori @else Tidak ada data kategori. @endif</div><div>{{ $isItemMode ? $kindItems->links() : $categories->links() }}</div></div>
        </x-gf.panel>
    </x-gf.page>

    <div class="modal fade ic-modal" id="itemFormModal" tabindex="-1" aria-labelledby="itemFormModalTitle" aria-describedby="itemFormModalDescription" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg"><form method="POST" action="{{ route('master.items.store') }}" id="itemForm" class="modal-content">@csrf<input type="hidden" name="_method" id="itemFormMethod" value="POST"><input type="hidden" name="return_to_category" value="1"><input type="hidden" name="return_kind" value="{{ $activeKind }}"><input type="hidden" name="return_q" value="{{ request('q') }}"><input type="hidden" name="return_status" value="{{ request('status') }}"><input type="hidden" name="compact_edit" value="0" id="itemFormCompact"><input type="hidden" name="unit" value="" data-item-form-unit><input type="hidden" name="item_type_option_id" value="" data-item-form-type-option><input type="hidden" name="production_source" value="" data-item-form-production-source><input type="hidden" name="can_buy" value="0" data-item-form-can-buy><input type="hidden" name="can_make" value="0" data-item-form-can-make><input type="hidden" name="default_supply_source" value="" data-item-form-default-source><input type="hidden" name="default_allocation" value="hpp" data-item-form-allocation><input type="hidden" name="purchase_treatment_id" value="" data-item-form-treatment><input type="hidden" name="default_expense_account_id" value="" data-item-form-expense-account>
            <div class="modal-header"><span class="ic-modal-icon" aria-hidden="true"><i class="bi bi-box-seam"></i></span><div><div class="ic-modal-kicker">Master item</div><h2 class="modal-title" id="itemFormModalTitle">Tambah item</h2><div class="ic-modal-help" id="itemFormModalDescription">Isi identitas dan klasifikasi item tanpa meninggalkan kelompok ini.</div></div><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body"><div class="alert alert-info py-2 px-3 mb-3 small" id="itemFormNote"><i class="bi bi-shield-check me-1"></i>Edit ringkas mempertahankan supplier, barcode, dan HPP yang sudah tersimpan.</div><div class="ic-modal-grid">
                <div class="ic-modal-field"><label class="ic-label" for="itemFormCode">Kode item <span aria-hidden="true">*</span></label><input class="form-control ic-control" type="text" name="code" id="itemFormCode" maxlength="50" required></div>
                <div class="ic-modal-field"><label class="ic-label" for="itemFormName">Nama item <span aria-hidden="true">*</span></label><input class="form-control ic-control" type="text" name="name" id="itemFormName" maxlength="190" required></div>
                <div class="ic-modal-field"><label class="ic-label" for="itemFormSku">SKU <span class="text-muted fw-normal">(opsional)</span></label><input class="form-control ic-control" type="text" name="sku" id="itemFormSku" maxlength="100"></div>
                <div class="ic-modal-field"><label class="ic-label" for="itemFormType">Tipe item <span aria-hidden="true">*</span></label><select class="form-select ic-control" name="type" id="itemFormType" required>@foreach($typeLabels as $key => $label)<option value="{{ $key }}" @selected(old('type', $activeKind === 'product' ? 'finished_good' : 'material') === $key)>{{ $label }}</option>@endforeach</select></div>
                <div class="ic-modal-field ic-modal-field-full"><label class="ic-label" for="itemFormCategory">Kategori</label><select class="form-select ic-control" name="item_category_id" id="itemFormCategory"><option value="">Tanpa kategori</option>@foreach($itemCategoryOptions as $category)<option value="{{ $category->id }}" data-category-kind="{{ $category->kind }}" @selected((string) old('item_category_id', $defaultItemCategoryId) === (string) $category->id)>{{ $category->code }} — {{ $category->name }}{{ $category->active ? '' : ' (nonaktif)' }}</option>@endforeach</select><div class="ic-modal-help" id="itemFormCategoryHelp">Kategori akan divalidasi sesuai tipe item.</div></div>
                <div class="ic-modal-field"><label class="ic-label" for="itemFormStockUnit">Satuan stok <span aria-hidden="true">*</span></label><input class="form-control ic-control" type="text" name="stock_unit" id="itemFormStockUnit" maxlength="20" value="{{ old('stock_unit', 'pcs') }}" required></div>
                <div class="ic-modal-field"><label class="ic-label" for="itemFormPurchaseUnit">Satuan beli <span aria-hidden="true">*</span></label><input class="form-control ic-control" type="text" name="purchase_unit" id="itemFormPurchaseUnit" maxlength="20" value="{{ old('purchase_unit', 'pcs') }}" required></div>
                <div class="ic-modal-field"><label class="ic-label" for="itemFormConversion">Konversi ke stok <span aria-hidden="true">*</span></label><input class="form-control ic-control" type="number" name="purchase_conversion_factor" id="itemFormConversion" min="0.000001" step="0.000001" value="{{ old('purchase_conversion_factor', '1') }}" required></div>
                <div class="form-check ic-modal-field ic-modal-field-full"><input type="hidden" name="active" value="0"><input class="form-check-input" type="checkbox" name="active" value="1" id="itemFormActive" @checked(old('active', '1') === '1')><label class="form-check-label" for="itemFormActive">Item aktif dan dapat dipakai di transaksi</label></div>
            </div></div>
            <div class="modal-footer"><a class="ic-modal-help me-auto" href="{{ route('master.items.create') }}" id="itemFormFullLink"><i class="bi bi-sliders2 me-1"></i>Form lengkap</a><button class="ic-btn" type="button" data-bs-dismiss="modal">Batal</button><button class="ic-btn ic-btn-primary" type="submit" id="itemFormSubmit"><i class="bi bi-check2" aria-hidden="true"></i><span>Simpan item</span></button></div>
        </form></div>
    </div>

    <div class="modal fade ic-modal" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg"><form method="POST" id="categoryForm" class="modal-content">@csrf<input type="hidden" name="_method" id="categoryMethod" value="POST"><div class="modal-header"><span class="ic-modal-icon" aria-hidden="true"><i class="bi bi-tags"></i></span><div><div class="ic-modal-kicker">Master data</div><h2 class="modal-title" id="categoryModalTitle">Tambah kategori</h2><div class="ic-modal-help">Kode digunakan sebagai identitas singkat di laporan dan transaksi.</div></div><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><div class="ic-modal-grid"><div class="ic-modal-field"><label class="ic-label" for="categoryCode">Kode kategori <span aria-hidden="true">*</span></label><input class="form-control ic-control @error('code') is-invalid @enderror" type="text" name="code" id="categoryCode" maxlength="50" value="{{ old('code') }}" required>@error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror</div><div class="ic-modal-field"><label class="ic-label" for="categoryName">Nama kategori <span aria-hidden="true">*</span></label><input class="form-control ic-control @error('name') is-invalid @enderror" type="text" name="name" id="categoryName" maxlength="190" value="{{ old('name') }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div><div class="ic-modal-field ic-modal-field-full"><label class="ic-label" for="categoryKind">Kelompok <span aria-hidden="true">*</span></label><select class="form-select ic-control @error('kind') is-invalid @enderror" name="kind" id="categoryKind" required>@foreach($kindLabels as $key => $label)<option value="{{ $key }}" @selected(old('kind', $activeKind ?: 'product') === $key)>{{ $label }}</option>@endforeach</select>@error('kind')<div class="invalid-feedback">{{ $message }}</div>@enderror</div><div class="form-check ic-modal-field-full"><input type="hidden" name="active" value="0"><input class="form-check-input" type="checkbox" name="active" value="1" id="categoryActive" @checked(old('active', '1') === '1')><label class="form-check-label" for="categoryActive">Kategori aktif dan tersedia untuk dipilih pada master item</label></div></div></div><div class="modal-footer"><button class="ic-btn" type="button" data-bs-dismiss="modal">Batal</button><button class="ic-btn ic-btn-primary" type="submit"><i class="bi bi-check2" aria-hidden="true"></i><span>Simpan kategori</span></button></div></form></div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('category-search');
            const searchClear = document.querySelector('[data-category-search-clear]');
            const form = document.getElementById('categoryForm');
            const title = document.getElementById('categoryModalTitle');
            const methodInput = document.getElementById('categoryMethod');
            const codeInput = document.getElementById('categoryCode');
            const nameInput = document.getElementById('categoryName');
            const kindInput = document.getElementById('categoryKind');
            const activeInput = document.getElementById('categoryActive');
            const storeUrl = @json(route('master.item_categories.store'));
            const updateUrlBase = @json(url('master/item-categories'));
            const activeKind = @json($activeKind ?: 'product');
            const itemFormModal = document.getElementById('itemFormModal');
            const itemForm = document.getElementById('itemForm');
            const itemFormAdminReadonly = @json($isAdminRole);
            const defaultItemCategoryId = @json($defaultItemCategoryId);
            const itemFormLockedFields = itemForm ? Array.from(itemForm.querySelectorAll('#itemFormCode, #itemFormSku, #itemFormType, #itemFormCategory, #itemFormStockUnit, #itemFormPurchaseUnit, #itemFormConversion, #itemFormActive')) : [];

            function syncItemCategoryOptions() {
                const type = document.getElementById('itemFormType')?.value;
                const category = document.getElementById('itemFormCategory');
                if (!type || !category) return;
                const allowedKinds = ['finished_good', 'wip'].includes(type)
                    ? ['product']
                    : ['material', 'support', 'accessory', 'packaging', 'operational', 'other'];
                Array.from(category.options).forEach(option => {
                    if (!option.dataset.categoryKind) return;
                    const allowed = allowedKinds.includes(option.dataset.categoryKind);
                    option.hidden = !allowed;
                    option.disabled = !allowed;
                });
                if (category.options[category.selectedIndex]?.disabled) category.value = '';
                const help = document.getElementById('itemFormCategoryHelp');
                if (help) help.textContent = ['finished_good', 'wip'].includes(type)
                    ? 'Finished Good dan WIP harus memakai kelompok produk.'
                    : 'Material memakai kelompok bahan, pendukung, accessories, packaging, operasional, atau lainnya.';
            }

            function setItemFormValue(selector, value, fallback = '') {
                const input = itemForm?.querySelector(selector);
                if (input) input.value = value ?? fallback;
            }

            function prepareCreateItem() {
                if (!itemForm) return;
                itemForm.action = @json(route('master.items.store'));
                document.getElementById('itemFormMethod').value = 'POST';
                document.getElementById('itemFormCompact').value = '0';
                document.getElementById('itemFormModalTitle').textContent = 'Tambah item';
                document.getElementById('itemFormModalDescription').textContent = 'Isi identitas dan klasifikasi item tanpa meninggalkan kelompok ini.';
                document.getElementById('itemFormSubmit').querySelector('span').textContent = 'Simpan item';
                document.getElementById('itemFormFullLink').href = @json(route('master.items.create'));
                setItemFormValue('[name="code"]', '');
                setItemFormValue('[name="name"]', '');
                setItemFormValue('[name="sku"]', '');
                setItemFormValue('#itemFormType', activeKind === 'product' ? 'finished_good' : 'material');
                setItemFormValue('#itemFormCategory', defaultItemCategoryId || '');
                setItemFormValue('#itemFormStockUnit', 'pcs');
                setItemFormValue('#itemFormPurchaseUnit', 'pcs');
                setItemFormValue('#itemFormConversion', '1');
                setItemFormValue('[data-item-form-unit]', 'pcs');
                setItemFormValue('[data-item-form-type-option]', '');
                setItemFormValue('[data-item-form-production-source]', '');
                setItemFormValue('[data-item-form-can-buy]', '0');
                setItemFormValue('[data-item-form-can-make]', '0');
                setItemFormValue('[data-item-form-default-source]', '');
                setItemFormValue('[data-item-form-allocation]', 'hpp');
                setItemFormValue('[data-item-form-treatment]', '');
                setItemFormValue('[data-item-form-expense-account]', '');
                document.getElementById('itemFormActive').checked = true;
                itemFormLockedFields.forEach(field => field.disabled = false);
                syncItemCategoryOptions();
            }

            function prepareEditItem(button) {
                if (!itemForm) return;
                const data = button.dataset;
                itemForm.action = data.itemEditUrl || '#';
                document.getElementById('itemFormMethod').value = 'PUT';
                document.getElementById('itemFormCompact').value = '1';
                document.getElementById('itemFormModalTitle').textContent = itemFormAdminReadonly ? 'Edit nama item' : 'Edit item';
                document.getElementById('itemFormModalDescription').textContent = itemFormAdminReadonly
                    ? 'Admin dapat memperbarui nama item tanpa mengubah konfigurasi master.'
                    : 'Perbarui identitas inti tanpa mengubah supplier, barcode, atau HPP yang sudah tersimpan.';
                document.getElementById('itemFormSubmit').querySelector('span').textContent = 'Simpan perubahan';
                document.getElementById('itemFormFullLink').href = data.itemEditFullUrl || '#';
                setItemFormValue('[name="code"]', data.itemEditCode);
                setItemFormValue('[name="name"]', data.itemEditName);
                setItemFormValue('[name="sku"]', data.itemEditSku);
                setItemFormValue('#itemFormType', data.itemEditType || 'material');
                setItemFormValue('#itemFormCategory', data.itemEditCategory || '');
                setItemFormValue('#itemFormStockUnit', data.itemEditStockUnit || 'pcs');
                setItemFormValue('#itemFormPurchaseUnit', data.itemEditPurchaseUnit || data.itemEditStockUnit || 'pcs');
                setItemFormValue('#itemFormConversion', data.itemEditConversion || '1');
                setItemFormValue('[data-item-form-unit]', data.itemEditStockUnit || 'pcs');
                setItemFormValue('[data-item-form-type-option]', data.itemEditTypeOption || '');
                setItemFormValue('[data-item-form-production-source]', data.itemEditProductionSource || '');
                setItemFormValue('[data-item-form-can-buy]', data.itemEditCanBuy || '0');
                setItemFormValue('[data-item-form-can-make]', data.itemEditCanMake || '0');
                setItemFormValue('[data-item-form-default-source]', data.itemEditDefaultSource || '');
                setItemFormValue('[data-item-form-allocation]', data.itemEditAllocation || 'hpp');
                setItemFormValue('[data-item-form-treatment]', data.itemEditTreatment || '');
                setItemFormValue('[data-item-form-expense-account]', data.itemEditExpenseAccount || '');
                document.getElementById('itemFormActive').checked = data.itemEditActive === '1';
                itemFormLockedFields.forEach(field => field.disabled = itemFormAdminReadonly);
                syncItemCategoryOptions();
            }

            document.querySelectorAll('#btnAddItem, #btnEmptyAddItem').forEach(button => button.addEventListener('click', prepareCreateItem));
            document.querySelectorAll('.btn-edit-item').forEach(button => button.addEventListener('click', () => prepareEditItem(button)));
            document.getElementById('itemFormType')?.addEventListener('change', syncItemCategoryOptions);
            itemFormModal?.addEventListener('shown.bs.modal', () => document.getElementById('itemFormName')?.focus());
            itemForm?.addEventListener('submit', () => {
                const submit = document.getElementById('itemFormSubmit');
                if (!submit) return;
                submit.disabled = true;
                submit.querySelector('span').textContent = 'Menyimpan…';
            });
            syncItemCategoryOptions();

            function syncSearchClear() {
                if (searchClear) searchClear.hidden = !searchInput?.value.trim();
            }

            searchInput?.addEventListener('input', syncSearchClear);
            searchInput?.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && searchInput.value) {
                    searchInput.value = '';
                    syncSearchClear();
                }
            });
            searchClear?.addEventListener('click', function () {
                if (!searchInput) return;
                searchInput.value = '';
                syncSearchClear();
                searchInput.focus();
            });
            syncSearchClear();

            function prepareCreate() {
                form.action = storeUrl;
                methodInput.value = 'POST';
                title.textContent = 'Tambah kategori';
                codeInput.value = '';
                nameInput.value = '';
                kindInput.value = activeKind;
                activeInput.checked = true;
            }

            document.querySelectorAll('#btnAddCategory, #btnEmptyAddCategory').forEach(function (button) {
                button.addEventListener('click', prepareCreate);
            });

            document.querySelectorAll('.btn-edit-category').forEach(function (button) {
                button.addEventListener('click', function () {
                    form.action = updateUrlBase + '/' + button.dataset.id;
                    methodInput.value = 'PUT';
                    title.textContent = 'Edit kategori';
                    codeInput.value = button.dataset.code || '';
                    nameInput.value = button.dataset.name || '';
                    kindInput.value = button.dataset.kind || 'product';
                    activeInput.checked = button.dataset.active === '1';
                });
            });

            document.querySelectorAll('[data-confirm-delete]').forEach(function (deleteForm) {
                deleteForm.addEventListener('submit', function (event) {
                    const name = deleteForm.dataset.categoryName || 'kategori ini';
                    if (!window.confirm('Hapus kategori "' + name + '"? Tindakan ini tidak dapat dibatalkan.')) {
                        event.preventDefault();
                    }
                });
            });

            @if($errors->any() && old('return_to_category'))
                prepareCreateItem();
                if (itemFormModal && window.bootstrap) bootstrap.Modal.getOrCreateInstance(itemFormModal).show();
            @endif
        });
    </script>
@endpush
