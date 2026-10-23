@extends('layouts.app')

@section('title', 'Master • Suppliers')

@php
    $fmt = fn ($number) => number_format((float) $number, 0, ',', '.');
    $totalSuppliers = method_exists($suppliers, 'total') ? $suppliers->total() : $suppliers->count();
    $collection = method_exists($suppliers, 'getCollection') ? $suppliers->getCollection() : collect($suppliers);
    $activeCount = $collection->where('active', 1)->count();
    $inactiveCount = $collection->where('active', 0)->count();
    $poType = $poType ?? request('po_type', '');
    $hasFilter = filled($q ?? request('q')) || filled($poType);
    $poLabels = ['material' => 'Bahan Baku', 'finished_good' => 'Barang Jadi', 'packing' => 'Packing'];
@endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .supplier-page {
            --supplier-text: var(--gf-color-content, #101828);
            --supplier-muted: var(--gf-color-muted, #667085);
            --supplier-surface: var(--gf-color-surface, #fff);
            --supplier-soft: var(--gf-color-surface-muted, #f2f4f7);
            --supplier-line: var(--gf-color-line, #e4e7ec);
            --supplier-brand: var(--gf-color-brand, #5b5ce2);
            --supplier-brand-strong: var(--gf-color-brand-strong, #4142bb);
            --supplier-shadow: var(--gf-shadow-sm, 0 1px 2px rgba(16,24,40,.04), 0 4px 12px rgba(16,24,40,.04));
            display: grid;
            gap: 1rem;
            width: 100%;
            max-width: var(--gf-content-max, 1280px);
            min-width: 0;
            margin-inline: auto;
        }

        .supplier-page .gf-master-header { border-color: #e0e2fb; border-radius: var(--gf-radius-card, 16px); background: linear-gradient(120deg, #f4f3ff 0%, #fff 58%, #edfcff 100%); box-shadow: 0 12px 30px rgba(48, 49, 125, .07); }
        .supplier-page .gf-master-title { color: var(--supplier-text); font-size: clamp(1.35rem, 2vw, 1.8rem); letter-spacing: -.045em; }
        .supplier-page .gf-master-desc { max-width: 720px; color: var(--supplier-muted); line-height: 1.55; }
        .supplier-page .gf-master-eyebrow { color: var(--supplier-brand-strong); }
        .supplier-actions { display: flex; justify-content: flex-end; gap: .5rem; flex-wrap: wrap; }
        .supplier-btn { display: inline-flex; align-items: center; justify-content: center; gap: .42rem; min-height: 40px; padding: .58rem .9rem; border: 1px solid var(--supplier-line); border-radius: var(--gf-radius-control, 10px); color: var(--supplier-text); background: var(--supplier-surface); font: inherit; font-size: .73rem; font-weight: 800; line-height: 1; text-decoration: none; cursor: pointer; box-shadow: 0 1px 2px rgba(16,24,40,.03); transition: color .18s ease, background .18s ease, border-color .18s ease, transform .18s ease, box-shadow .18s ease; }
        .supplier-btn:hover { color: var(--supplier-brand-strong); border-color: color-mix(in srgb, var(--supplier-brand) 38%, var(--supplier-line)); transform: translateY(-1px); }
        .supplier-btn:active { transform: translateY(0); box-shadow: none; }
        .supplier-btn:focus-visible { outline: 3px solid color-mix(in srgb, var(--supplier-brand) 30%, transparent); outline-offset: 2px; }
        .supplier-btn:disabled { cursor: not-allowed; opacity: .55; pointer-events: none; transform: none; box-shadow: none; }
        .supplier-btn-primary { color: #fff; border-color: var(--supplier-brand); background: var(--supplier-brand); box-shadow: 0 7px 14px rgba(91, 92, 226, .2); }
        .supplier-btn-primary:hover,
        .supplier-btn-primary:focus-visible { color: #fff; border-color: var(--supplier-brand-strong); background: var(--supplier-brand-strong); }
        .supplier-btn-sm { min-height: 36px; padding: .48rem .7rem; font-size: .69rem; }

        .supplier-alert { display: flex; align-items: flex-start; gap: .55rem; margin: 0; padding: .72rem .85rem; border: 1px solid; border-radius: 12px; font-size: .76rem; line-height: 1.45; }
        .supplier-alert-success { color: #067647; border-color: #abefc6; background: #ecfdf3; }
        .supplier-alert-danger { color: #b42318; border-color: #fecdca; background: #fef3f2; }
        .supplier-alert i { margin-top: .05rem; }

        .supplier-summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .75rem; }
        .supplier-stat { position: relative; min-width: 0; overflow: hidden; min-height: 108px; padding: 1rem; border: 1px solid var(--supplier-line); border-radius: var(--gf-radius-card, 16px); background: var(--supplier-surface); box-shadow: var(--supplier-shadow); }
        .supplier-stat::after { position: absolute; right: -22px; bottom: -28px; width: 88px; height: 88px; border-radius: 50%; background: #eeefff; content: ''; }
        .supplier-stat:nth-child(2)::after { background: #ecfdf3; }
        .supplier-stat:nth-child(3)::after { background: #fef3f2; }
        .supplier-stat-top { position: relative; z-index: 1; display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
        .supplier-stat-label { color: var(--supplier-muted); font-size: .67rem; font-weight: 850; letter-spacing: .04em; text-transform: uppercase; }
        .supplier-stat-icon { display: grid; width: 30px; height: 30px; place-items: center; border-radius: 10px; color: var(--supplier-brand-strong); background: #eeefff; font-size: .85rem; }
        .supplier-stat:nth-child(2) .supplier-stat-icon { color: #067647; background: #ecfdf3; }
        .supplier-stat:nth-child(3) .supplier-stat-icon { color: #b42318; background: #fef3f2; }
        .supplier-stat-value { position: relative; z-index: 1; margin-top: .45rem; color: var(--supplier-text); font-size: 1.35rem; font-weight: 900; letter-spacing: -.045em; font-variant-numeric: tabular-nums; }
        .supplier-stat-note { position: relative; z-index: 1; margin-top: .12rem; color: var(--supplier-muted); font-size: .68rem; }

        .supplier-page .supplier-panel { border-radius: var(--gf-radius-card, 16px); box-shadow: var(--supplier-shadow); }
        .supplier-page .supplier-panel .gf-panel-header { padding: 1rem 1.15rem; }
        .supplier-page .supplier-panel .gf-panel-body { padding: 1rem 1.15rem; }
        .supplier-page .supplier-list-panel .gf-panel-body { padding: 0; }
        .supplier-page .supplier-panel .gf-panel-title { color: var(--supplier-text); font-size: .88rem; }
        .supplier-page .supplier-panel .gf-subtext { margin-top: .16rem; color: var(--supplier-muted); }

        .supplier-filter { display: grid; grid-template-columns: minmax(220px, 1fr) minmax(150px, .28fr) auto; align-items: end; gap: .7rem; padding: 1rem 1.15rem; border-bottom: 1px solid var(--supplier-line); background: var(--supplier-surface); }
        .supplier-field { display: grid; gap: .3rem; min-width: 0; }
        .supplier-label { color: var(--supplier-muted); font-size: .68rem; font-weight: 850; letter-spacing: .035em; text-transform: uppercase; }
        .supplier-search { position: relative; }
        .supplier-search > i { position: absolute; top: 50%; left: .75rem; z-index: 1; color: var(--supplier-muted); transform: translateY(-50%); pointer-events: none; }
        .supplier-control { width: 100%; min-height: 42px; padding: .55rem .7rem; border: 1px solid var(--supplier-line); border-radius: var(--gf-radius-control, 10px); color: var(--supplier-text); background: var(--supplier-surface); box-shadow: none; font: inherit; font-size: .8rem; }
        .supplier-search .supplier-control { padding-left: 2.1rem; padding-right: 2.3rem; }
        .supplier-control:focus { border-color: var(--supplier-brand); box-shadow: 0 0 0 .2rem rgba(91, 92, 226, .12); }
        .supplier-control::placeholder { color: var(--supplier-muted); }
        .supplier-search .supplier-live-indicator { position: absolute; top: 50%; right: .7rem; display: none; color: var(--supplier-brand-strong); font-size: .68rem; font-weight: 800; transform: translateY(-50%); }
        .supplier-search .supplier-live-indicator.is-show { display: inline-flex; }
        .supplier-filter-actions { display: flex; align-items: center; gap: .45rem; }

        .supplier-table-wrap { position: relative; isolation: isolate; max-height: min(68vh, 720px); overflow: auto; scrollbar-color: rgba(148,163,184,.55) transparent; scrollbar-width: thin; }
        .supplier-table-wrap::-webkit-scrollbar { width: 7px; height: 7px; }
        .supplier-table-wrap::-webkit-scrollbar-thumb { border-radius: 999px; background: rgba(148,163,184,.55); }
        .supplier-table { min-width: 880px; margin: 0; border-collapse: separate; border-spacing: 0; }
        .supplier-table thead th { position: sticky; top: 0; z-index: 5; padding: .7rem .7rem; color: var(--supplier-muted); background: color-mix(in srgb, var(--supplier-soft) 82%, var(--supplier-surface) 18%); box-shadow: inset 0 -1px 0 var(--supplier-line); font-size: .63rem; font-weight: 850; letter-spacing: .045em; text-transform: uppercase; white-space: nowrap; }
        .supplier-table tbody td { padding: .72rem .7rem; color: var(--supplier-text); vertical-align: middle; border-bottom-color: var(--supplier-line); font-size: .75rem; }
        .supplier-table tbody tr:last-child td { border-bottom: 0; }
        .supplier-table tbody tr:hover { background: color-mix(in srgb, var(--supplier-brand) 4%, var(--supplier-surface)); }
        .supplier-table thead th:last-child, .supplier-table tbody td:last-child { position: sticky; right: 0; z-index: 4; background: var(--supplier-surface); background-clip: padding-box; box-shadow: -10px 0 18px rgba(16,24,40,.07); }
        .supplier-table thead th:last-child { z-index: 7; background: color-mix(in srgb, var(--supplier-soft) 82%, var(--supplier-surface) 18%); }
        .supplier-table tbody tr:hover td:last-child { background: color-mix(in srgb, var(--supplier-brand) 4%, var(--supplier-surface)); }
        .supplier-table-scroll-hint { display: none; align-items: center; gap: .35rem; padding: .65rem .85rem; color: var(--supplier-muted); background: color-mix(in srgb, var(--supplier-soft) 70%, var(--supplier-surface) 30%); font-size: .67rem; font-weight: 700; }
        .supplier-code { color: var(--supplier-text); font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .72rem; font-weight: 800; }
        .supplier-name { color: var(--supplier-text); font-weight: 800; }
        .supplier-muted { color: var(--supplier-muted); font-size: .69rem; }
        .supplier-contact { display: grid; gap: .2rem; }
        .supplier-bank { display: inline-flex; align-items: center; gap: .3rem; color: var(--supplier-muted); font-size: .68rem; }
        .supplier-bank strong { color: var(--supplier-text); font-family: ui-monospace, monospace; font-size: .64rem; }
        .supplier-pills { display: flex; flex-wrap: wrap; gap: .25rem; }
        .supplier-pill { display: inline-flex; align-items: center; min-height: 23px; padding: 0 .52rem; border: 1px solid transparent; border-radius: 999px; font-size: .65rem; font-weight: 800; white-space: nowrap; }
        .supplier-pill::before { width: 5px; height: 5px; margin-right: .35rem; border-radius: 50%; background: currentColor; content: ''; }
        .supplier-pill-material { color: #175cd3; border-color: #b2ddff; background: #eff8ff; }
        .supplier-pill-finished { color: #067647; border-color: #abefc6; background: #ecfdf3; }
        .supplier-pill-packing { color: #7a5af8; border-color: #d9d6fe; background: #f4f3ff; }
        .supplier-pill-all { color: #475467; border-color: var(--supplier-line); background: var(--supplier-soft); }
        .supplier-status { display: inline-flex; align-items: center; gap: .35rem; min-height: 23px; padding: 0 .52rem; border: 1px solid transparent; border-radius: 999px; font-size: .65rem; font-weight: 800; white-space: nowrap; }
        .supplier-status::before { width: 5px; height: 5px; border-radius: 50%; background: currentColor; content: ''; }
        .supplier-status-active { color: #067647; border-color: #abefc6; background: #ecfdf3; }
        .supplier-status-inactive { color: #b42318; border-color: #fecdca; background: #fef3f2; }
        .supplier-actions-cell { display: flex; justify-content: flex-end; align-items: center; gap: .35rem; min-width: 96px; }
        .supplier-empty { display: grid; place-items: center; min-height: 210px; padding: 1.25rem; text-align: center; }
        .supplier-empty-icon { display: grid; width: 42px; height: 42px; place-items: center; margin-bottom: .55rem; border-radius: 13px; color: var(--supplier-brand-strong); background: #eeefff; font-size: 1.1rem; }
        .supplier-empty-title { color: var(--supplier-text); font-size: .85rem; font-weight: 850; }
        .supplier-empty-text { max-width: 330px; margin: .25rem 0 .7rem; color: var(--supplier-muted); font-size: .72rem; line-height: 1.5; }
        .supplier-mobile-list { display: none; }
        .supplier-mobile-card { display: grid; gap: .8rem; padding: .9rem; border-bottom: 1px solid var(--supplier-line); }
        .supplier-mobile-card:last-child { border-bottom: 0; }
        .supplier-mobile-card-head { display: flex; align-items: flex-start; justify-content: space-between; gap: .7rem; }
        .supplier-mobile-title { min-width: 0; }
        .supplier-mobile-title strong, .supplier-mobile-title span { display: block; }
        .supplier-mobile-title strong { overflow-wrap: anywhere; color: var(--supplier-text); font-size: .82rem; font-weight: 850; }
        .supplier-mobile-title span { margin-top: .18rem; color: var(--supplier-muted); font-family: ui-monospace, monospace; font-size: .64rem; }
        .supplier-mobile-details { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem .8rem; padding-top: .75rem; border-top: 1px solid var(--supplier-line); }
        .supplier-mobile-details dt { color: var(--supplier-muted); font-size: .59rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
        .supplier-mobile-details dd { margin: .16rem 0 0; overflow-wrap: anywhere; color: var(--supplier-text); font-size: .69rem; line-height: 1.4; }
        .supplier-mobile-action { width: 100%; min-height: 44px; }
        .supplier-footer { display: flex; align-items: center; justify-content: space-between; gap: .8rem; padding: .85rem 1.15rem; border-top: 1px solid var(--supplier-line); }
        .supplier-footer-copy { color: var(--supplier-muted); font-size: .7rem; }
        .supplier-footer-copy strong { color: var(--supplier-text); }
        .supplier-footer .pagination { margin: 0; }
        .supplier-footer .page-link { color: var(--supplier-brand-strong); border-color: var(--supplier-line); background: var(--supplier-surface); font-size: .7rem; }
        .supplier-footer .page-item.active .page-link { color: #fff; border-color: var(--supplier-brand); background: var(--supplier-brand); }

        body[data-theme="dark"] .supplier-page .gf-master-header { border-color: #334155; background: linear-gradient(120deg, #17203a 0%, #11263f 58%, #0e2b35 100%); }
        body[data-theme="dark"] .supplier-table thead th, body[data-theme="dark"] .supplier-table thead th:last-child { background: #132a45; box-shadow: inset 0 -1px 0 #334155; }
        body[data-theme="dark"] .supplier-table thead th:last-child, body[data-theme="dark"] .supplier-table tbody td:last-child { background: #11263f; }
        body[data-theme="dark"] .supplier-table tbody tr:hover td:last-child { background: #172033; }
        body[data-theme="dark"] .supplier-pill-all { color: #cbd5e1; border-color: #334155; background: #1e293b; }
        body[data-theme="dark"] .supplier-pill-material { color: #93c5fd; border-color: rgba(37,99,235,.4); background: rgba(29,78,216,.2); }
        body[data-theme="dark"] .supplier-pill-finished { color: #86efac; border-color: rgba(34,197,94,.4); background: rgba(22,101,52,.2); }
        body[data-theme="dark"] .supplier-pill-packing { color: #c4b5fd; border-color: rgba(124,58,237,.4); background: rgba(76,29,149,.2); }
        body[data-theme="dark"] .supplier-status-active { color: #86efac; border-color: rgba(34,197,94,.4); background: rgba(22,101,52,.2); }
        body[data-theme="dark"] .supplier-status-inactive { color: #fda4af; border-color: rgba(244,63,94,.4); background: rgba(159,18,57,.2); }

        @media (max-width: 1050px) { .supplier-filter { grid-template-columns: minmax(180px, 1fr) minmax(130px, .4fr); } .supplier-filter-actions { grid-column: 1 / -1; } }
        @media (max-width: 900px) { .supplier-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 767px) {
            .supplier-page { gap: .75rem; }
            .supplier-page .gf-master-header { padding: 1rem; }
            .supplier-page .gf-master-header-layout, .supplier-actions, .supplier-actions .supplier-btn { width: 100%; }
            .supplier-actions { display: grid; grid-template-columns: 1fr; }
            .supplier-actions .supplier-btn { min-height: 44px; }
            .supplier-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem; }
            .supplier-stat { min-height: 98px; padding: .78rem; }
            .supplier-stat-value { font-size: 1.08rem; }
            .supplier-stat-label { font-size: .61rem; }
            .supplier-stat-note { font-size: .63rem; }
            .supplier-filter { grid-template-columns: 1fr; padding: .85rem; }
            .supplier-filter-actions { display: grid; grid-template-columns: 1fr 1fr; width: 100%; }
            .supplier-filter-actions .supplier-btn { width: 100%; }
            .supplier-page .supplier-panel .gf-panel-header { padding: .85rem; }
            .supplier-page .supplier-panel .gf-panel-body { padding: .85rem; }
            .supplier-list-panel .gf-panel-body { padding: 0 !important; }
            .supplier-table-wrap, .supplier-table-scroll-hint { display: none; }
            .supplier-mobile-list { display: block; }
            .supplier-footer { align-items: flex-start; flex-direction: column; padding: .85rem; }
            .supplier-footer > :last-child { width: 100%; overflow-x: auto; }
        }
        @media (max-width: 430px) {
            .supplier-summary { gap: .45rem; }
            .supplier-stat-icon { width: 26px; height: 26px; font-size: .75rem; }
            .supplier-stat-note { display: none; }
        }
    </style>
@endpush

@section('content')
    <x-gf.page class="supplier-page" eyebrow="Master Data · Purchasing" title="Suppliers" description="Kelola data supplier, kontak, status aktif, dan mapping item pembelian.">
        <x-slot:actions>
            <div class="supplier-actions">
                <a href="{{ route('master.suppliers.create') }}" class="supplier-btn supplier-btn-primary">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i>
                    <span>Tambah Supplier</span>
                </a>
            </div>
        </x-slot:actions>

        @if (session('success'))
            <div class="supplier-alert supplier-alert-success" role="status"><i class="bi bi-check-circle" aria-hidden="true"></i><span>{{ session('success') }}</span></div>
        @endif
        @if ($errors->any())
            <div class="supplier-alert supplier-alert-danger" role="alert"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i><span>{{ $errors->first() }}</span></div>
        @endif

        <section class="supplier-summary" aria-label="Ringkasan supplier">
            <article class="supplier-stat"><div class="supplier-stat-top"><span class="supplier-stat-label">Total supplier</span><span class="supplier-stat-icon"><i class="bi bi-buildings" aria-hidden="true"></i></span></div><div class="supplier-stat-value">{{ $fmt($totalSuppliers) }}</div><div class="supplier-stat-note">Mengikuti filter aktif</div></article>
            <article class="supplier-stat"><div class="supplier-stat-top"><span class="supplier-stat-label">Aktif</span><span class="supplier-stat-icon"><i class="bi bi-check2-circle" aria-hidden="true"></i></span></div><div class="supplier-stat-value">{{ $fmt($activeCount) }}</div><div class="supplier-stat-note">Di halaman saat ini</div></article>
            <article class="supplier-stat"><div class="supplier-stat-top"><span class="supplier-stat-label">Nonaktif</span><span class="supplier-stat-icon"><i class="bi bi-pause-circle" aria-hidden="true"></i></span></div><div class="supplier-stat-value">{{ $fmt($inactiveCount) }}</div><div class="supplier-stat-note">Perlu ditinjau ulang</div></article>
        </section>

        <x-gf.panel class="supplier-panel supplier-list-panel" title="Daftar supplier" subtitle="Cari supplier berdasarkan identitas, kontak, atau jenis PO.">
            <form class="supplier-filter" method="GET" action="{{ route('master.suppliers.index') }}" data-supplier-filter>
                <div class="supplier-field supplier-search">
                    <label class="supplier-label" for="supplierSearch">Cari supplier</label>
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input id="supplierSearch" type="search" name="q" value="{{ $q ?? request('q') }}" class="supplier-control" autocomplete="off" placeholder="Nama, kode, HP, atau email...">
                    <span class="supplier-live-indicator" aria-live="polite">Memfilter…</span>
                </div>
                <div class="supplier-field">
                    <label class="supplier-label" for="filterPoType">Jenis PO</label>
                    <select name="po_type" class="supplier-control" id="filterPoType">
                        <option value="" @selected($poType === '')>Semua jenis</option>
                        <option value="material" @selected($poType === 'material')>Bahan baku</option>
                        <option value="finished_good" @selected($poType === 'finished_good')>Barang jadi</option>
                        <option value="packing" @selected($poType === 'packing')>Packing</option>
                        <option value="none" @selected($poType === 'none')>Tanpa jenis</option>
                    </select>
                </div>
                <div class="supplier-filter-actions">
                    <button class="supplier-btn supplier-btn-sm supplier-btn-primary" type="submit"><i class="bi bi-funnel" aria-hidden="true"></i><span>Filter</span></button>
                    @if ($hasFilter)<a href="{{ route('master.suppliers.index') }}" class="supplier-btn supplier-btn-sm">Reset</a>@endif
                </div>
            </form>

            @if ($suppliers->count())
                <div class="supplier-table-scroll-hint"><i class="bi bi-arrows-expand" aria-hidden="true"></i><span>Geser tabel untuk melihat kolom dan aksi lainnya.</span></div>
                <div class="supplier-table-wrap">
                    <table class="table supplier-table">
                        <thead><tr><th style="width:56px;" class="text-center">No.</th><th style="width:120px;">Kode</th><th>Supplier</th><th style="width:190px;">Kontak</th><th style="width:175px;">Jenis PO</th><th style="width:115px;">Status</th><th style="width:96px;" class="text-end">Aksi</th></tr></thead>
                        <tbody>
                            @foreach ($suppliers as $s)
                                @php $banks = $s->bankAccounts ?? collect(); $poTypes = $s->po_types ?? []; @endphp
                                <tr>
                                    <td class="text-center supplier-muted">{{ method_exists($suppliers, 'firstItem') ? $suppliers->firstItem() + $loop->index : $loop->iteration }}</td>
                                    <td><span class="supplier-code">{{ $s->code }}</span></td>
                                    <td><div class="supplier-name">{{ $s->name }}</div><div class="supplier-muted">{{ $s->address ?: 'Alamat belum diisi' }}</div></td>
                                    <td><div class="supplier-contact"><span class="supplier-name">{{ $s->phone ?: 'Kontak belum diisi' }}</span>@if ($s->email)<span class="supplier-muted">{{ $s->email }}</span>@endif @foreach ($banks as $bk)<span class="supplier-bank"><strong>{{ $bk->bank_name }}</strong> {{ $bk->account_number }}</span>@endforeach</div></td>
                                    <td>@if (empty($poTypes))<span class="supplier-pill supplier-pill-all">Semua jenis</span>@else<div class="supplier-pills">@foreach ($poTypes as $pt)<span class="supplier-pill supplier-pill-{{ $pt === 'material' ? 'material' : ($pt === 'packing' ? 'packing' : 'finished') }}">{{ $poLabels[$pt] ?? $pt }}</span>@endforeach</div>@endif</td>
                                    <td><span class="supplier-status {{ (int) $s->active === 1 ? 'supplier-status-active' : 'supplier-status-inactive' }}">{{ (int) $s->active === 1 ? 'Aktif' : 'Nonaktif' }}</span></td>
                                    <td><div class="supplier-actions-cell"><a href="{{ route('master.suppliers.show', $s) }}" class="supplier-btn supplier-btn-sm" aria-label="Buka detail {{ $s->name }}">Detail</a></div></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="supplier-mobile-list">
                    @foreach ($suppliers as $s)
                        @php $banks = $s->bankAccounts ?? collect(); $poTypes = $s->po_types ?? []; @endphp
                        <article class="supplier-mobile-card">
                            <div class="supplier-mobile-card-head"><div class="supplier-mobile-title"><strong>{{ $s->name }}</strong><span>{{ $s->code }}</span></div><span class="supplier-status {{ (int) $s->active === 1 ? 'supplier-status-active' : 'supplier-status-inactive' }}">{{ (int) $s->active === 1 ? 'Aktif' : 'Nonaktif' }}</span></div>
                            <dl class="supplier-mobile-details">
                                <div><dt>Kontak</dt><dd>{{ $s->phone ?: 'Belum diisi' }}@if ($s->email)<br>{{ $s->email }}@endif</dd></div>
                                <div><dt>Jenis PO</dt><dd>@if (empty($poTypes))<span class="supplier-pill supplier-pill-all">Semua jenis</span>@else<span class="supplier-pills">@foreach ($poTypes as $pt)<span class="supplier-pill supplier-pill-{{ $pt === 'material' ? 'material' : ($pt === 'packing' ? 'packing' : 'finished') }}">{{ $poLabels[$pt] ?? $pt }}</span>@endforeach</span>@endif</dd></div>
                                <div><dt>Alamat</dt><dd>{{ $s->address ?: 'Belum diisi' }}</dd></div>
                                <div><dt>Rekening</dt><dd>@if ($banks->isNotEmpty()){{ $banks->first()->bank_name }} · {{ $banks->first()->account_number }}@if ($banks->count() > 1) <span class="supplier-muted">+{{ $banks->count() - 1 }}</span>@endif @else Belum diisi @endif</dd></div>
                            </dl>
                            <a href="{{ route('master.suppliers.show', $s) }}" class="supplier-btn supplier-btn-primary supplier-mobile-action"><i class="bi bi-arrow-right" aria-hidden="true"></i><span>Buka detail supplier</span></a>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="supplier-empty"><div class="supplier-empty-icon"><i class="bi bi-truck" aria-hidden="true"></i></div><div class="supplier-empty-title">Belum ada supplier yang cocok.</div><div class="supplier-empty-text">Tambah supplier baru untuk mulai mapping item dan harga pembelian.</div><a href="{{ route('master.suppliers.create') }}" class="supplier-btn supplier-btn-primary supplier-btn-sm">Tambah supplier</a></div>
            @endif

            <div class="supplier-footer">
                <div class="supplier-footer-copy">@if (method_exists($suppliers, 'firstItem') && $suppliers->total())Menampilkan <strong>{{ $suppliers->firstItem() }}–{{ $suppliers->lastItem() }}</strong> dari <strong>{{ $fmt($suppliers->total()) }}</strong> supplier @else Total <strong>{{ $fmt($suppliers->count()) }}</strong> supplier @endif</div>
                <div class="ms-auto">{{ $suppliers->links() }}</div>
            </div>
        </x-gf.panel>
    </x-gf.page>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('[data-supplier-filter]');
            if (!form || form.dataset.gfRealtime === '1') return;
            form.dataset.gfRealtime = '1';
            const input = form.querySelector('input[name="q"]');
            const submitBtn = form.querySelector('button[type="submit"]');
            const indicator = form.querySelector('.supplier-live-indicator');
            let timer = null;
            let submitting = false;

            function submitLive(delay = 450) {
                clearTimeout(timer);
                timer = setTimeout(function () {
                    if (submitting) return;
                    submitting = true;
                    indicator?.classList.add('is-show');
                    if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span>Memfilter</span>'; }
                    if (String(input?.value || '').trim() === '' && input) input.disabled = true;
                    form.requestSubmit ? form.requestSubmit() : form.submit();
                }, delay);
            }

            input?.addEventListener('input', function () { submitLive(); });
            input?.addEventListener('keydown', function (event) { if (event.key === 'Enter') { event.preventDefault(); submitLive(0); } });
            form.querySelector('#filterPoType')?.addEventListener('change', function () { submitLive(0); });
        });
    </script>
@endpush
