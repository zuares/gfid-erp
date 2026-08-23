{{-- resources/views/master/items/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Master Item')

@php
    $fmt = fn($value) => number_format((float) $value, 0, ',', '.');
    $stats = $itemStats ?? [
        'total' => $items->total(),
        'active' => $items->count(),
        'can_buy' => 0,
        'can_make' => 0,
        'hybrid' => 0,
        'missing_hpp' => 0,
    ];
    $hasFilters = request()->hasAny(['q', 'type', 'category_kind', 'item_category_id', 'supply_mode']);
    $canManageMasterData = auth()->user()?->canAccessModule('master') ?? false;
    $supplyModes = [
        'buy' => 'Beli jadi saja',
        'make' => 'Produksi sendiri saja',
        'hybrid' => 'Hybrid: beli / produksi',
        'undefined' => 'Belum ditentukan',
    ];
@endphp

@push('head')
<style>
    .item-page { max-width: 1280px; margin: 0 auto; padding: 16px 12px 32px; color: #0f172a; }
    .item-hero, .item-card { border: 1px solid #e2e8f0; border-radius: 22px; background: #fff; box-shadow: 0 14px 36px rgba(15,23,42,.06); }
    .item-hero { display:flex; justify-content:space-between; align-items:center; gap:16px; padding:18px; margin-bottom:14px; }
    .item-hero-main { display:flex; align-items:center; gap:13px; min-width:0; }
    .item-icon { width:48px; height:48px; flex:0 0 48px; display:inline-flex; align-items:center; justify-content:center; border-radius:16px; color:#fff; background:linear-gradient(135deg,#0f172a,#475569); font-size:1.25rem; box-shadow:0 12px 25px rgba(15,23,42,.16); }
    .item-eyebrow { display:inline-flex; align-items:center; gap:6px; color:#475569; background:#f1f5f9; border:1px solid #e2e8f0; border-radius:999px; padding:4px 9px; font-size:.68rem; font-weight:800; margin-bottom:6px; }
    .item-title { margin:0; font-size:1.35rem; line-height:1.1; letter-spacing:-.04em; font-weight:900; }
    .item-subtitle { margin-top:4px; color:#64748b; font-size:.82rem; font-weight:600; }
    .item-actions, .item-row-actions { display:flex; flex-wrap:wrap; gap:7px; align-items:center; justify-content:flex-end; }
    .item-actions .btn, .item-row-actions .btn, .item-card .btn { border-radius:999px; font-weight:800; display:inline-flex; align-items:center; gap:6px; }
    .item-primary { color:#fff!important; background:linear-gradient(135deg,#0f172a,#334155)!important; border-color:transparent!important; }
    .item-soft { color:#334155!important; background:#fff!important; border:1px solid #cbd5e1!important; }
    .item-soft:hover { background:#f8fafc!important; }
    .item-kpis { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:10px; margin-bottom:14px; }
    .item-kpi { min-width:0; padding:13px; border:1px solid #e2e8f0; border-radius:17px; background:linear-gradient(180deg,#fff,#f8fafc); }
    .item-kpi-label { color:#64748b; font-size:.67rem; font-weight:850; text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .item-kpi-value { margin-top:5px; color:#0f172a; font-size:1.2rem; font-weight:900; letter-spacing:-.04em; }
    .item-kpi-note { color:#94a3b8; font-size:.7rem; margin-top:1px; }
    .item-card { overflow:hidden; }
    .item-filter { display:grid; grid-template-columns:minmax(220px,1.5fr) repeat(4,minmax(130px,1fr)) auto; gap:9px; align-items:end; padding:14px; border-bottom:1px solid #eef2f7; }
    .item-label { display:block; margin-bottom:5px; color:#475569; font-size:.68rem; font-weight:850; text-transform:uppercase; letter-spacing:.04em; }
    .item-filter .form-control, .item-filter .form-select { min-height:38px; border-radius:12px; border-color:#e2e8f0; font-size:.8rem; font-weight:600; box-shadow:none; }
    .item-filter .form-control:focus, .item-filter .form-select:focus { border-color:#94a3b8; box-shadow:0 0 0 .2rem rgba(15,23,42,.07); }
    .item-filter-actions { display:flex; gap:7px; align-items:center; }
    .item-filter-actions .btn { min-height:38px; }
    .item-bulk { display:flex; align-items:center; gap:9px; flex-wrap:wrap; padding:10px 14px; background:#f8fafc; border-bottom:1px solid #e2e8f0; }
    .item-bulk strong { font-size:.8rem; }
    .item-table-wrap { overflow:auto; }
    .item-table { min-width:980px; margin:0; font-size:.8rem; }
    .item-table thead th { padding:11px 10px; background:#f8fafc; color:#64748b; border-bottom:1px solid #e2e8f0; font-size:.67rem; font-weight:900; text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; }
    .item-table tbody td { padding:12px 10px; border-color:#eef2f7; vertical-align:middle; }
    .item-table tbody tr:hover { background:#fbfdff; }
    .item-code { display:inline-flex; border-radius:9px; padding:4px 7px; background:#f1f5f9; border:1px solid #e2e8f0; color:#334155; font-size:.7rem; font-weight:900; font-family:ui-monospace,SFMono-Regular,Menlo,monospace; }
    .item-name { color:#0f172a; font-weight:850; letter-spacing:-.015em; }
    .item-muted { color:#64748b; font-size:.72rem; }
    .item-meta { color:#94a3b8; font-size:.7rem; margin-top:2px; }
    .item-type { display:inline-flex; border-radius:999px; padding:4px 8px; color:#475569; background:#f8fafc; border:1px solid #e2e8f0; font-size:.68rem; font-weight:800; }
    .item-status { display:inline-flex; align-items:center; gap:5px; border-radius:999px; padding:4px 8px; font-size:.68rem; font-weight:800; white-space:nowrap; }
    .item-status:before { content:''; width:6px; height:6px; border-radius:50%; background:currentColor; }
    .item-status-active { color:#166534; background:#f0fdf4; border:1px solid #bbf7d0; }
    .item-status-off { color:#991b1b; background:#fef2f2; border:1px solid #fecaca; }
    .item-status-buy { color:#1d4ed8; background:#eff6ff; border:1px solid #bfdbfe; }
    .item-status-make { color:#047857; background:#ecfdf5; border:1px solid #a7f3d0; }
    .item-status-hybrid { color:#7c3aed; background:#f5f3ff; border:1px solid #ddd6fe; }
    .item-status-undefined { color:#92400e; background:#fffbeb; border:1px solid #fde68a; }
    .item-footer { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; padding:13px 14px; color:#64748b; font-size:.75rem; font-weight:650; }
    .item-footer .pagination { margin:0; }
    .item-footer .page-link { border-radius:9px; color:#475569; border-color:#e2e8f0; font-size:.75rem; }
    .item-footer .page-item.active .page-link { color:#fff; background:#0f172a; border-color:#0f172a; }
    .item-empty { padding:44px 18px; text-align:center; color:#64748b; }
    .item-empty-icon { width:50px; height:50px; margin:0 auto 10px; display:flex; align-items:center; justify-content:center; border-radius:16px; color:#64748b; background:#f1f5f9; font-size:1.25rem; }
    .modal-content { border:0; border-radius:18px; box-shadow:0 24px 60px rgba(15,23,42,.2); }
    .modal-header, .modal-footer { border-color:#eef2f7; }
    .modal-title { font-weight:850; font-size:.95rem; }
    .modal .form-control, .modal .form-select { border-radius:11px; font-size:.82rem; }
    .item-mobile-list { display:none; }
    .item-mobile-card { padding:13px; border-bottom:1px solid #eef2f7; }
    .item-mobile-card:last-child { border-bottom:0; }
    .item-mobile-card-head, .item-mobile-card-footer { display:flex; align-items:center; justify-content:space-between; gap:9px; }
    .item-mobile-card-head { align-items:flex-start; }
    .item-mobile-card-title { min-width:0; }
    .item-mobile-card-title .item-name { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .item-mobile-card-meta { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:9px; margin-top:12px; }
    .item-mobile-card-meta > div { min-width:0; }
    .item-mobile-card-label { color:#94a3b8; font-size:.63rem; font-weight:850; text-transform:uppercase; letter-spacing:.04em; }
    .item-mobile-card-value { margin-top:2px; color:#334155; font-size:.74rem; font-weight:750; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .item-mobile-card-footer { margin-top:12px; }
    .item-mobile-card-actions { display:flex; flex:1 1 auto; gap:6px; }
    .item-mobile-card-actions .btn { flex:1 1 0; justify-content:center; }
    body[data-theme="dark"] .item-mobile-card { border-color:#334155; }
    body[data-theme="dark"] .item-mobile-card-value { color:#e2e8f0; }
    body[data-theme="dark"] .item-page { color:#e5e7eb; }
    body[data-theme="dark"] .item-hero, body[data-theme="dark"] .item-card, body[data-theme="dark"] .item-kpi { background:#0f172a; border-color:#334155; }
    body[data-theme="dark"] .item-eyebrow, body[data-theme="dark"] .item-bulk, body[data-theme="dark"] .item-table thead th { background:#111827; border-color:#334155; }
    body[data-theme="dark"] .item-title, body[data-theme="dark"] .item-name, body[data-theme="dark"] .item-kpi-value { color:#f8fafc; }
    body[data-theme="dark"] .item-subtitle, body[data-theme="dark"] .item-muted, body[data-theme="dark"] .item-label, body[data-theme="dark"] .item-kpi-label, body[data-theme="dark"] .item-footer { color:#94a3b8; }
    body[data-theme="dark"] .item-table tbody td { border-color:#334155; }
    body[data-theme="dark"] .item-table tbody tr:hover { background:#111827; }
    body[data-theme="dark"] .item-filter .form-control, body[data-theme="dark"] .item-filter .form-select, body[data-theme="dark"] .modal-content { background:#0f172a; border-color:#475569; color:#f8fafc; }
    @media (max-width:1100px) { .item-kpis { grid-template-columns:repeat(3,minmax(0,1fr)); } .item-filter { grid-template-columns:repeat(3,minmax(0,1fr)); } .item-filter-search { grid-column:1 / -1; } }
    @media (max-width:700px) {
        .item-page { padding:8px 6px 22px; }
        .item-hero { align-items:flex-start; flex-direction:column; padding:13px; border-radius:17px; }
        .item-icon { width:40px; height:40px; flex-basis:40px; border-radius:13px; }
        .item-title { font-size:1.15rem; }
        .item-actions, .item-actions .btn { width:100%; }
        .item-actions { display:grid; grid-template-columns:1fr 1fr; }
        .item-actions .btn { justify-content:center; }
        .item-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); gap:7px; }
        .item-kpi { padding:10px; border-radius:13px; }
        .item-kpi-value { font-size:1.05rem; }
        .item-kpi-note { display:none; }
        .item-filter { grid-template-columns:1fr; padding:11px; }
        .item-filter-search { grid-column:auto; }
        .item-filter-actions, .item-filter-actions .btn { width:100%; }
        .item-filter-actions { display:grid; grid-template-columns:1fr auto; }
        .item-filter-actions .btn { justify-content:center; }
        .item-table-wrap { display:none; }
        .item-mobile-list { display:block; }
        .item-footer { align-items:flex-start; padding:11px; }
        .item-footer .pagination { width:100%; overflow:auto; padding-bottom:2px; }
        .item-footer .pagination .page-link { white-space:nowrap; }
        .item-bulk { padding:9px 11px; }
        .item-bulk .item-muted { display:none; }
        .item-bulk .btn { flex:1 1 auto; }
        .item-bulk #bulkClear { flex:0 0 auto; }
    }
</style>
@endpush

@section('content')
<div class="item-page">
    <div class="item-hero">
        <div class="item-hero-main">
            <div class="item-icon"><i class="bi bi-box-seam"></i></div>
            <div>
                <div class="item-eyebrow"><i class="bi bi-database-check"></i> Master Data</div>
                <h1 class="item-title">Master Item</h1>
            </div>
        </div>
        <div class="item-actions">
            @if($canManageMasterData)
                <a href="{{ route('master.item_categories.index') }}" class="btn item-soft btn-sm"><i class="bi bi-tags"></i>Kategori</a>
            @endif
            <a href="{{ route('master.items.create') }}" class="btn item-primary btn-sm"><i class="bi bi-plus-lg"></i>Tambah Item</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success py-2 px-3 mb-3" style="font-size:.8rem;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:.8rem;">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:.8rem;">{{ $errors->first() }}</div>
    @endif

    <div class="item-kpis">
        <div class="item-kpi"><div class="item-kpi-label">Total Item</div><div class="item-kpi-value">{{ $fmt($stats['total']) }}</div></div>
        <div class="item-kpi"><div class="item-kpi-label">Aktif</div><div class="item-kpi-value">{{ $fmt($stats['active']) }}</div></div>
        <div class="item-kpi"><div class="item-kpi-label">Bisa Dibeli</div><div class="item-kpi-value">{{ $fmt($stats['can_buy']) }}</div></div>
        <div class="item-kpi"><div class="item-kpi-label">Bisa Dibuat</div><div class="item-kpi-value">{{ $fmt($stats['can_make']) }}</div></div>
        <div class="item-kpi"><div class="item-kpi-label">Hybrid</div><div class="item-kpi-value">{{ $fmt($stats['hybrid']) }}</div></div>
        <div class="item-kpi"><div class="item-kpi-label">HPP Kosong</div><div class="item-kpi-value">{{ $fmt($stats['missing_hpp']) }}</div></div>
    </div>

    <div class="item-card">
        <form method="GET" action="{{ route('master.items.index') }}" class="item-filter">
            <div class="item-filter-search">
                <label class="item-label" for="item-search">Cari item</label>
                <input id="item-search" type="search" name="q" value="{{ request('q') }}" class="form-control" autocomplete="off">
            </div>
            <div>
                <label class="item-label" for="item-type">Tipe</label>
                <select id="item-type" name="type" class="form-select">
                    <option value="">Semua tipe</option>
                    @foreach($typeLabels as $key => $label)
                        <option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="item-label" for="item-category-kind">Kelompok</label>
                <select id="item-category-kind" name="category_kind" class="form-select">
                    <option value="">Semua kelompok</option>
                    @foreach($categoryKinds as $key => $label)
                        <option value="{{ $key }}" @selected(request('category_kind') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="item-label" for="item-category">Kategori</label>
                <select id="item-category" name="item_category_id" class="form-select">
                    <option value="">Semua kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('item_category_id') === (string) $category->id)>{{ $category->code }} — {{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="item-label" for="item-supply-mode">Metode pasok</label>
                <select id="item-supply-mode" name="supply_mode" class="form-select">
                    <option value="">Semua metode</option>
                    @foreach($supplyModes as $key => $label)
                        <option value="{{ $key }}" @selected(request('supply_mode') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="item-filter-actions">
                <button class="btn item-primary btn-sm" type="submit"><i class="bi bi-search"></i>Cari</button>
                @if($hasFilters)
                    <a href="{{ route('master.items.index') }}" class="btn item-soft btn-sm" title="Hapus semua filter"><i class="bi bi-x-lg"></i><span class="d-none d-xl-inline">Reset</span></a>
                @endif
            </div>
        </form>
        <div id="bulkToolbar" class="item-bulk d-none">
            <strong><span id="selectedCount">0</span> item dipilih</strong>
            <span class="item-muted">Aksi massal:</span>
            <button type="button" class="btn item-soft btn-sm" data-bs-toggle="modal" data-bs-target="#bulkCategoryModal"><i class="bi bi-tags"></i>Kategori</button>
            <button type="button" class="btn item-soft btn-sm" data-bs-toggle="modal" data-bs-target="#bulkTypeModal"><i class="bi bi-diagram-3"></i>Tipe</button>
            <button type="button" class="btn item-soft btn-sm" data-bs-toggle="modal" data-bs-target="#bulkHppModal"><i class="bi bi-cash-stack"></i>Set HPP</button>
            <button type="button" class="btn btn-link btn-sm text-muted ms-auto" id="bulkClear">Batal</button>
        </div>

        @if($items->count())
            <div class="item-table-wrap">
                <table class="table table-hover align-middle item-table">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:38px;"><input type="checkbox" class="form-check-input" id="checkAll" aria-label="Pilih semua"></th>
                            <th style="min-width:245px;">Item</th>
                            <th style="min-width:185px;">Klasifikasi</th>
                            <th style="min-width:190px;">Metode Pasok</th>
                            <th style="min-width:145px;">HPP / Barcode</th>
                            <th style="width:105px;">Status</th>
                            <th class="text-end" style="width:190px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            @php
                                $hpp = (float) ($item->active_unit_cost ?? $item->effective_unit_cost ?? $item->hpp ?? 0);
                                $barcodeCount = (int) ($item->barcodes_count ?? 0);
                                $modeClass = $item->isHybrid() ? 'item-status-hybrid' : ($item->canMake() ? 'item-status-make' : ($item->canBuy() ? 'item-status-buy' : 'item-status-undefined'));
                            @endphp
                            <tr>
                                <td class="text-center"><input type="checkbox" class="form-check-input row-check" value="{{ $item->id }}" aria-label="Pilih {{ $item->code }}"></td>
                                <td>
                                    <div><a class="item-code text-decoration-none" href="{{ route('master.items.show', $item) }}">{{ $item->code }}</a></div>
                                    <div class="item-name mt-1">{{ $item->name }}</div>
                                    <div class="item-meta">SKU: {{ $item->sku ?: 'mengikuti kode' }} · {{ $item->unit ?: 'pcs' }}</div>
                                </td>
                                <td>
                                    <span class="item-type">{{ $item->itemTypeOption?->name ?? ($typeLabels[$item->type] ?? $item->type) }}</span>
                                    <div class="item-meta">{{ $item->category?->code ?? 'Tanpa kategori' }}{{ $item->category ? ' · '.$item->category->name : '' }}</div>
                                </td>
                                <td>
                                    @if(in_array($item->type, ['finished_good', 'wip'], true))
                                        <span class="item-status {{ $modeClass }}">{{ $item->supply_mode_label }}</span>
                                        <div class="item-meta">Default: {{ $item->default_supply_source_label }}</div>
                                    @else
                                        <span class="item-muted">Tidak berlaku untuk material</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="item-name">{{ $hpp > 0 ? 'Rp '.$fmt($hpp) : 'Belum di-set' }}</div>
                                    <div class="item-meta"><i class="bi bi-upc-scan me-1"></i>{{ $barcodeCount }} barcode</div>
                                </td>
                                <td><span class="item-status {{ $item->active ? 'item-status-active' : 'item-status-off' }}">{{ $item->active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                <td class="text-end">
                                    <div class="item-row-actions">
                                        <a href="{{ route('master.items.show', $item) }}" class="btn item-soft btn-sm" title="Lihat detail"><i class="bi bi-eye"></i><span class="d-none d-xl-inline">Lihat</span></a>
                                        <a href="{{ route('master.items.edit', $item) }}" class="btn item-primary btn-sm" title="Edit item"><i class="bi bi-pencil"></i><span class="d-none d-xl-inline">Edit</span></a>
                                        <div class="dropdown">
                                            <button type="button" class="btn item-soft btn-sm px-2" data-bs-toggle="dropdown" aria-expanded="false" title="Aksi lainnya"><i class="bi bi-three-dots"></i></button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                <li><a class="dropdown-item small" href="{{ route('master.items.hpp_temp.edit', $item) }}"><i class="bi bi-cash-coin me-2"></i>Set HPP</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="POST" action="{{ route('master.items.destroy', $item) }}" onsubmit="return confirm('Hapus item {{ addslashes($item->code) }}?');">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="dropdown-item small text-danger"><i class="bi bi-trash3 me-2"></i>Hapus item</button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="item-mobile-list">
                @foreach($items as $item)
                    @php
                        $hpp = (float) ($item->active_unit_cost ?? $item->effective_unit_cost ?? $item->hpp ?? 0);
                        $barcodeCount = (int) ($item->barcodes_count ?? 0);
                        $modeClass = $item->isHybrid() ? 'item-status-hybrid' : ($item->canMake() ? 'item-status-make' : ($item->canBuy() ? 'item-status-buy' : 'item-status-undefined'));
                    @endphp
                    <article class="item-mobile-card">
                        <div class="item-mobile-card-head">
                            <div class="d-flex align-items-start gap-2 min-w-0">
                                <input type="checkbox" class="form-check-input row-check mt-1" value="{{ $item->id }}" aria-label="Pilih {{ $item->code }}">
                                <div class="item-mobile-card-title">
                                    <a class="item-code text-decoration-none" href="{{ route('master.items.show', $item) }}">{{ $item->code }}</a>
                                    <div class="item-name mt-1">{{ $item->name }}</div>
                                    <div class="item-meta">SKU: {{ $item->sku ?: 'mengikuti kode' }} · {{ $item->unit ?: 'pcs' }}</div>
                                </div>
                            </div>
                            <span class="item-status {{ $item->active ? 'item-status-active' : 'item-status-off' }}">{{ $item->active ? 'Aktif' : 'Nonaktif' }}</span>
                        </div>
                        <div class="item-mobile-card-meta">
                            <div><div class="item-mobile-card-label">Tipe</div><div class="item-mobile-card-value">{{ $item->itemTypeOption?->name ?? ($typeLabels[$item->type] ?? $item->type) }}</div></div>
                            <div><div class="item-mobile-card-label">Kategori</div><div class="item-mobile-card-value">{{ $item->category?->code ?? 'Tanpa kategori' }}</div></div>
                            <div><div class="item-mobile-card-label">Pasok</div><div class="item-mobile-card-value">@if(in_array($item->type, ['finished_good', 'wip'], true))<span class="item-status {{ $modeClass }}">{{ $item->supply_mode_label }}</span>@else-@endif</div></div>
                            <div><div class="item-mobile-card-label">HPP / Barcode</div><div class="item-mobile-card-value">{{ $hpp > 0 ? 'Rp '.$fmt($hpp) : 'Belum di-set' }} · {{ $barcodeCount }}</div></div>
                        </div>
                        <div class="item-mobile-card-footer">
                            <div class="item-mobile-card-actions">
                                <a href="{{ route('master.items.show', $item) }}" class="btn item-soft btn-sm"><i class="bi bi-eye"></i>Lihat</a>
                                <a href="{{ route('master.items.edit', $item) }}" class="btn item-primary btn-sm"><i class="bi bi-pencil"></i>Edit</a>
                            </div>
                            <div class="dropdown">
                                <button type="button" class="btn item-soft btn-sm px-2" data-bs-toggle="dropdown" aria-expanded="false" title="Aksi lainnya"><i class="bi bi-three-dots"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li><a class="dropdown-item small" href="{{ route('master.items.hpp_temp.edit', $item) }}"><i class="bi bi-cash-coin me-2"></i>Set HPP</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('master.items.destroy', $item) }}" onsubmit="return confirm('Hapus item {{ addslashes($item->code) }}?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="dropdown-item small text-danger"><i class="bi bi-trash3 me-2"></i>Hapus item</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="item-footer"><div>Menampilkan {{ $items->firstItem() }}–{{ $items->lastItem() }} dari {{ $fmt($items->total()) }} item</div>{{ $items->links() }}</div>
        @else
            <div class="item-empty">
                <div class="item-empty-icon"><i class="bi bi-search"></i></div>
                <div class="fw-bold">Tidak ada item yang cocok</div>
                <div class="small mt-1">Coba ubah kata pencarian atau reset filter.</div>
                @if($hasFilters)<a href="{{ route('master.items.index') }}" class="btn item-soft btn-sm mt-3">Reset filter</a>@endif
            </div>
        @endif
    </div>
</div>

<div class="modal fade" id="bulkCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('master.items.bulk_update') }}" class="modal-content bulk-form">
            @csrf
            <input type="hidden" name="action" value="set_category">
            <div class="modal-header"><h5 class="modal-title">Ubah kategori <span class="text-muted">(<span class="sel-count">0</span> item)</span></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body"><label class="item-label" for="bulk-category">Kategori baru</label><select id="bulk-category" name="item_category_id" class="form-select" required><option value="">Pilih kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->code }} — {{ $category->name }}</option>@endforeach</select><div class="ids-container"></div></div>
            <div class="modal-footer"><button type="button" class="btn item-soft btn-sm" data-bs-dismiss="modal">Batal</button><button class="btn item-primary btn-sm">Terapkan</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="bulkTypeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('master.items.bulk_update') }}" class="modal-content bulk-form">
            @csrf
            <input type="hidden" name="action" value="set_type">
            <div class="modal-header"><h5 class="modal-title">Ubah tipe <span class="text-muted">(<span class="sel-count">0</span> item)</span></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body"><label class="item-label" for="bulk-type">Tipe baru</label><select id="bulk-type" name="type" class="form-select" required>@foreach($typeLabels as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select><div class="alert alert-info py-2 px-3 mt-3 mb-0 small"><i class="bi bi-info-circle me-1"></i>Item yang menjadi Finished Good otomatis disiapkan sebagai bisa dibeli.</div><div class="ids-container"></div></div>
            <div class="modal-footer"><button type="button" class="btn item-soft btn-sm" data-bs-dismiss="modal">Batal</button><button class="btn item-primary btn-sm">Terapkan</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="bulkHppModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('master.items.bulk_update') }}" class="modal-content bulk-form">
            @csrf
            <input type="hidden" name="action" value="set_hpp">
            <div class="modal-header"><h5 class="modal-title">Set HPP sementara <span class="text-muted">(<span class="sel-count">0</span> item)</span></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body"><label class="item-label" for="bulk-hpp">HPP per unit (Rp)</label><input id="bulk-hpp" type="number" min="0" step="0.01" name="unit_cost" class="form-control" required><label class="item-label mt-3" for="bulk-hpp-notes">Catatan (opsional)</label><input id="bulk-hpp-notes" type="text" name="notes" maxlength="255" class="form-control"><div class="ids-container"></div></div>
            <div class="modal-footer"><button type="button" class="btn item-soft btn-sm" data-bs-dismiss="modal">Batal</button><button class="btn btn-success btn-sm rounded-pill fw-bold">Terapkan HPP</button></div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkAll = document.getElementById('checkAll');
    const toolbar = document.getElementById('bulkToolbar');
    const selectedCount = document.getElementById('selectedCount');
    const clearButton = document.getElementById('bulkClear');
    const rowChecks = () => Array.from(document.querySelectorAll('.row-check'));
    const selectedChecks = () => rowChecks().filter(input => input.checked);
    function refreshBulkUi() {
        const rows = rowChecks(), selected = selectedChecks();
        if (toolbar) toolbar.classList.toggle('d-none', selected.length === 0);
        if (selectedCount) selectedCount.textContent = selected.length;
        document.querySelectorAll('.sel-count').forEach(node => node.textContent = selected.length);
        if (checkAll) { checkAll.checked = rows.length > 0 && selected.length === rows.length; checkAll.indeterminate = selected.length > 0 && selected.length < rows.length; }
    }
    checkAll?.addEventListener('change', () => { rowChecks().forEach(input => input.checked = checkAll.checked); refreshBulkUi(); });
    rowChecks().forEach(input => input.addEventListener('change', refreshBulkUi));
    clearButton?.addEventListener('click', () => { rowChecks().forEach(input => input.checked = false); refreshBulkUi(); });
    document.querySelectorAll('.bulk-form').forEach(form => form.addEventListener('submit', function (event) {
        const selected = selectedChecks();
        if (!selected.length) { event.preventDefault(); window.alert('Pilih minimal satu item terlebih dahulu.'); return; }
        const container = form.querySelector('.ids-container');
        if (!container) return;
        container.replaceChildren(...selected.map(input => { const hidden = document.createElement('input'); hidden.type = 'hidden'; hidden.name = 'item_ids[]'; hidden.value = input.value; return hidden; }));
    }));
    const filterForm = document.querySelector('.item-filter');
    document.getElementById('item-search')?.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); filterForm?.submit(); } });
    filterForm?.querySelectorAll('select').forEach(select => select.addEventListener('change', () => filterForm.submit()));
    refreshBulkUi();
});
</script>
@endpush
