@extends('layouts.app')

@section('title', 'Pemasok Barang')

@push('head')
<style>
    .mapping-wrap { max-width: 1120px; margin-inline: auto; padding: 1rem .75rem 3rem; }
    .mapping-hero { display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; margin-bottom:.85rem; }
    .mapping-title { font-size:1.35rem; font-weight:800; margin:0; letter-spacing:-.01em; }
    .mapping-subtitle { color:var(--muted); font-size:.82rem; margin-top:.15rem; }
    .mapping-stats { display:flex; flex-wrap:wrap; gap:.4rem; margin-top:.55rem; }
    .mapping-stat { display:inline-flex; align-items:center; gap:.35rem; border:1px solid var(--line); border-radius:999px; padding:.22rem .58rem; background:var(--card); font-size:.75rem; color:var(--muted); }
    .mapping-stat strong { color:var(--text); font-size:.8rem; }
    .mapping-tabs { display:flex; gap:.35rem; margin-bottom:.8rem; }
    .mapping-tab { border:1px solid var(--line); background:var(--card); color:var(--text); border-radius:999px; padding:.35rem .75rem; font-size:.78rem; font-weight:800; text-decoration:none; }
    .mapping-tab.is-active { color:#fff; background:#2563eb; border-color:#2563eb; }
    .mapping-panel { background: var(--card); border: 1px solid var(--line); border-radius: 14px; overflow:hidden; }
    .mapping-panel + .mapping-panel { margin-top:.8rem; }
    .mapping-panel-head { padding: .75rem .85rem; border-bottom: 1px solid var(--line); }
    .mapping-panel-body { padding: .85rem; }
    .mapping-filter { padding:.75rem .85rem; background:rgba(248,250,252,.55); border-bottom:1px solid var(--line); }
    .mapping-table th { color: var(--muted); font-size: .68rem; letter-spacing: .06em; text-transform: uppercase; white-space: nowrap; padding:.65rem .75rem; }
    .mapping-table td { vertical-align: middle; padding:.7rem .75rem; }
    .mapping-item-name { font-weight: 800; line-height: 1.2; }
    .mapping-item-code { color: var(--muted); font-size: .74rem; margin-top:.08rem; }
    .mapping-control { min-width: 86px; }
    .primary-label, .state-pill { display:inline-flex; align-items:center; border-radius:999px; padding:.12rem .5rem; font-size:.68rem; font-weight:800; border:1px solid transparent; white-space:nowrap; }
    .primary-label { color:#1d4ed8; background:rgba(37,99,235,.08); border-color:rgba(37,99,235,.32); }
    .state-pill.is-active { color:#15803d; background:rgba(22,163,74,.1); border-color:rgba(22,163,74,.35); }
    .state-pill.is-muted { color:#64748b; background:rgba(148,163,184,.1); border-color:rgba(148,163,184,.35); }
    .mapping-actions { display:flex; justify-content:flex-end; gap:.3rem; }
    .mapping-mobile-card { padding:.78rem .85rem; border-bottom:1px solid var(--line); }
    .mapping-mobile-card:last-child { border-bottom:0; }
    .mapping-mobile-grid { display:grid; grid-template-columns:1fr 1fr; gap:.45rem; margin-top:.65rem; }
    .mapping-mobile-field label { color:var(--muted); font-size:.66rem; font-weight:800; text-transform:uppercase; margin-bottom:.15rem; }
    .category-chip-list { display: flex; gap: .45rem; overflow-x: auto; padding-bottom: .25rem; scrollbar-width: thin; }
    .category-chip { flex: 0 0 auto; border: 1px solid var(--line); background: var(--card); color: var(--text); border-radius: 999px; padding: .38rem .68rem; font-size: .78rem; font-weight: 800; }
    .category-chip.is-active { color: #fff; background: #2563eb; border-color: #2563eb; }
    .category-chip-count { opacity: .75; margin-left: .25rem; }
    .supplier-check-list { border-top: 1px solid var(--line); }
    .supplier-check-row { display: grid; grid-template-columns: minmax(220px, 1fr) 120px 150px; gap: .75rem; align-items: center; padding: .65rem .9rem; border-bottom: 1px solid var(--line); }
    .supplier-check-row.is-selected { background: rgba(37, 99, 235, .045); }
    .supplier-check-main { display: flex; align-items: center; gap: .65rem; min-width: 0; }
    .supplier-check-name { font-weight: 800; line-height: 1.2; }
    .supplier-check-code { color: var(--muted); font-size: .73rem; }
    .autosave-state { min-height: 1.2rem; font-size: .76rem; font-weight: 700; }
    .autosave-state.is-saving { color: #a16207; }
    .autosave-state.is-saved { color: #15803d; }
    .autosave-state.is-error { color: #b91c1c; }
    @media (max-width: 767.98px) {
        .mapping-wrap { padding-inline:.65rem; }
        .mapping-hero { display:block; }
        .mapping-title { font-size:1.18rem; }
        .mapping-stats { flex-wrap:nowrap; overflow-x:auto; padding-bottom:.15rem; }
        .mapping-stat { flex:0 0 auto; }
        .mapping-tabs { overflow-x:auto; padding-bottom:.1rem; }
        .mapping-tab { flex:0 0 auto; }
        .mapping-panel-head, .mapping-panel-body, .mapping-filter { padding:.7rem .75rem; }
        .supplier-check-row { grid-template-columns: 1fr 92px; gap: .45rem .6rem; padding: .6rem .75rem; }
        .supplier-check-main { grid-column: 1 / -1; }
        .mapping-actions { justify-content:stretch; }
        .mapping-actions .btn, .mapping-actions form { flex:1; }
    }
</style>
@endpush

@section('content')
<div class="mapping-wrap">
    <div class="mapping-hero">
        <div>
            <h1 class="mapping-title">Mapping Pemasok</h1>
            <div class="mapping-subtitle">Atur pemasok utama dan alternatif agar PR/PO lebih cepat diarahkan.</div>
            <div class="mapping-stats">
                <span class="mapping-stat">Mapping <strong>{{ number_format($mappingStats['total'] ?? 0, 0, ',', '.') }}</strong></span>
                <span class="mapping-stat">Aktif <strong>{{ number_format($mappingStats['active'] ?? 0, 0, ',', '.') }}</strong></span>
                <span class="mapping-stat">Utama <strong>{{ number_format($mappingStats['primary'] ?? 0, 0, ',', '.') }}</strong></span>
                <span class="mapping-stat">Kategori <strong>{{ number_format($mappingStats['category'] ?? 0, 0, ',', '.') }}</strong></span>
            </div>
        </div>
        @if ($mode === 'item')
            <button class="btn btn-sm btn-primary flex-shrink-0" type="button" data-bs-toggle="collapse" data-bs-target="#mapping-create-panel">
                + Mapping
            </button>
        @endif
    </div>

    @if (session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
    @endif

    <div class="mapping-tabs" role="tablist">
        <a href="{{ route('purchasing.supplier_items.index', ['mode' => 'item']) }}"
            class="mapping-tab {{ $mode === 'item' ? 'is-active' : '' }}">Per Barang</a>
        <a href="{{ route('purchasing.supplier_items.index', ['mode' => 'category']) }}"
            class="mapping-tab {{ $mode === 'category' ? 'is-active' : '' }}">Per Kategori</a>
    </div>

    @if ($mode === 'item')
    <div class="mapping-panel collapse {{ $errors->any() ? 'show' : '' }}" id="mapping-create-panel">
        <div class="mapping-panel-head fw-semibold">Mapping Baru</div>
        <div class="mapping-panel-body">
            <form method="POST" action="{{ route('purchasing.supplier_items.store') }}">
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-lg-4">
                        <label class="form-label small fw-semibold mb-1">Barang</label>
                        <x-item-suggest
                            id-name="item_id"
                            placeholder="Cari nama atau kode barang..."
                            :show-name="true"
                            :show-category="true"
                            :min-chars="1"
                            :max-results="8"
                        />
                    </div>
                    <div class="col-12 col-md-5 col-lg-3">
                        <label class="form-label small fw-semibold mb-1">Pemasok</label>
                        <select name="supplier_id" class="form-select form-select-sm" required>
                            <option value="">Pilih pemasok</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected((string) old('supplier_id') === (string) $supplier->id)>
                                    {{ $supplier->name }}{{ $supplier->code ? ' · ' . $supplier->code : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-lg-1">
                        <label class="form-label small fw-semibold mb-1">MOQ</label>
                        <input type="number" name="minimum_order_qty" value="{{ old('minimum_order_qty') }}" min="0" step="0.01" class="form-control form-control-sm" placeholder="0">
                    </div>
                    <div class="col-6 col-md-2 col-lg-1">
                        <label class="form-label small fw-semibold mb-1">Lead Time</label>
                        <input type="number" name="lead_time_days" value="{{ old('lead_time_days') }}" min="0" class="form-control form-control-sm" placeholder="Hari">
                    </div>
                    @if ($canSeeMoney)
                        <div class="col-6 col-md-3 col-lg-2">
                            <label class="form-label small fw-semibold mb-1">Harga Terakhir</label>
                            <input type="number" name="last_price" value="{{ old('last_price') }}" min="0" step="0.01" class="form-control form-control-sm" placeholder="Rp 0">
                        </div>
                    @endif
                    <div class="col-6 col-md-3 col-lg-1">
                        <input type="hidden" name="active" value="1">
                        <input type="hidden" name="is_primary" value="0">
                        <div class="form-check mb-2 small">
                            <input type="checkbox" name="is_primary" value="1" class="form-check-input" id="new-primary" @checked(old('is_primary'))>
                            <label class="form-check-label" for="new-primary">Utama</label>
                        </div>
                        <button class="btn btn-sm btn-primary w-100" type="submit">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="mapping-panel">
        <div class="mapping-filter">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="form-label small fw-semibold mb-1">Cari</label>
                    <div class="input-group input-group-sm">
                        <input type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Kode atau nama barang">
                        <button class="btn btn-outline-secondary" type="submit">Cari</button>
                    </div>
                </div>
                <div class="col-7 col-md-4">
                    <label class="form-label small fw-semibold mb-1">Pemasok</label>
                    <select name="supplier_id" class="form-select form-select-sm">
                        <option value="">Semua pemasok</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected($supplierId === $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-5 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="active" @selected($status === 'active')>Aktif</option>
                        <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
                        <option value="all" @selected($status === 'all')>Semua</option>
                    </select>
                </div>
                <div class="col-12 col-md-1">
                    @if ($search !== '' || $supplierId > 0 || $status !== 'active')
                        <a href="{{ route('purchasing.supplier_items.index', ['mode' => 'item']) }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="table-responsive d-none d-md-block">
            <table class="table table-sm mb-0 mapping-table">
                <thead class="table-light">
                    <tr>
                        <th>Barang</th>
                        <th>Pemasok</th>
                        <th class="text-center">Utama</th>
                        <th>MOQ</th>
                        <th>Lead Time</th>
                        @if ($canSeeMoney)<th>Harga Terakhir</th>@endif
                        <th class="text-center">Aktif</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($mappings as $mapping)
                        @php
                            $formId = 'mapping-' . $mapping->id;
                        @endphp
                        <tr>
                            <td>
                                <div class="mapping-item-name">{{ $mapping->item?->name ?? 'Barang tidak ditemukan' }}</div>
                                <div class="mapping-item-code">{{ $mapping->item?->code }} · {{ $mapping->item?->unit }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $mapping->supplier?->name ?? 'Pemasok tidak ditemukan' }}</div>
                                <div class="mapping-item-code">{{ $mapping->supplier?->code }}</div>
                            </td>
                            <td class="text-center mobile-field" data-label="Pemasok Utama">
                                <input type="hidden" name="is_primary" value="0" form="{{ $formId }}">
                                <input type="checkbox" name="is_primary" value="1" class="form-check-input" form="{{ $formId }}" @checked($mapping->is_primary)>
                                @if ($mapping->is_primary)<span class="primary-label ms-1">Utama</span>@endif
                            </td>
                            <td class="mobile-field" data-label="Minimum Order">
                                <input type="number" name="minimum_order_qty" value="{{ $mapping->minimum_order_qty }}" min="0" step="0.01" class="form-control form-control-sm mapping-control" form="{{ $formId }}" placeholder="-"><span class="d-md-none small text-muted">{{ $mapping->item?->unit }}</span>
                            </td>
                            <td class="mobile-field" data-label="Lead Time">
                                <div class="input-group input-group-sm mapping-control"><input type="number" name="lead_time_days" value="{{ $mapping->lead_time_days }}" min="0" class="form-control" form="{{ $formId }}" placeholder="-"><span class="input-group-text">hari</span></div>
                            </td>
                            @if ($canSeeMoney)
                                <td class="mobile-field" data-label="Harga Terakhir">
                                    <input type="number" name="last_price" value="{{ $mapping->last_price }}" min="0" step="0.01" class="form-control form-control-sm mapping-control" form="{{ $formId }}">
                                </td>
                            @endif
                            <td class="text-center mobile-field" data-label="Status Aktif">
                                <input type="hidden" name="active" value="0" form="{{ $formId }}">
                                <input type="checkbox" name="active" value="1" class="form-check-input" form="{{ $formId }}" @checked($mapping->active)>
                                <span class="state-pill {{ $mapping->active ? 'is-active' : 'is-muted' }} ms-1">{{ $mapping->active ? 'Aktif' : 'Nonaktif' }}</span>
                            </td>
                            <td class="text-end text-nowrap">
                                <form id="{{ $formId }}" method="POST" action="{{ route('purchasing.supplier_items.update', $mapping) }}" class="d-inline">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="item_id" value="{{ $mapping->item_id }}">
                                    <input type="hidden" name="supplier_id" value="{{ $mapping->supplier_id }}">
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Simpan</button>
                                </form>
                                <form method="POST" action="{{ route('purchasing.supplier_items.destroy', $mapping) }}" class="d-inline" onsubmit="return confirm('Hapus mapping pemasok barang ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $canSeeMoney ? 8 : 7 }}" class="text-center text-muted py-4">Belum ada mapping pemasok barang.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-md-none">
            @forelse ($mappings as $mapping)
                @php
                    $mobileFormId = 'mapping-mobile-' . $mapping->id;
                @endphp
                <div class="mapping-mobile-card">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div class="min-w-0">
                            <div class="mapping-item-name">{{ $mapping->item?->code ?? '-' }}</div>
                            <div class="mapping-item-code">{{ $mapping->item?->name ?? 'Barang tidak ditemukan' }}</div>
                        </div>
                        <div class="text-end flex-shrink-0">
                            @if ($mapping->is_primary)<span class="primary-label">Utama</span>@endif
                            <div class="mt-1"><span class="state-pill {{ $mapping->active ? 'is-active' : 'is-muted' }}">{{ $mapping->active ? 'Aktif' : 'Nonaktif' }}</span></div>
                        </div>
                    </div>
                    <div class="fw-semibold mt-2">{{ $mapping->supplier?->name ?? 'Pemasok tidak ditemukan' }}</div>
                    <div class="mapping-item-code">{{ $mapping->supplier?->code }}</div>

                    <div class="mapping-mobile-grid">
                        <div class="mapping-mobile-field">
                            <label class="d-block">MOQ</label>
                            <input type="number" name="minimum_order_qty" value="{{ $mapping->minimum_order_qty }}" min="0" step="0.01" class="form-control form-control-sm" form="{{ $mobileFormId }}" placeholder="-">
                        </div>
                        <div class="mapping-mobile-field">
                            <label class="d-block">Lead Time</label>
                            <div class="input-group input-group-sm">
                                <input type="number" name="lead_time_days" value="{{ $mapping->lead_time_days }}" min="0" class="form-control" form="{{ $mobileFormId }}" placeholder="-">
                                <span class="input-group-text">hari</span>
                            </div>
                        </div>
                        @if ($canSeeMoney)
                            <div class="mapping-mobile-field">
                                <label class="d-block">Harga</label>
                                <input type="number" name="last_price" value="{{ $mapping->last_price }}" min="0" step="0.01" class="form-control form-control-sm" form="{{ $mobileFormId }}">
                            </div>
                        @endif
                        <div class="mapping-mobile-field">
                            <label class="d-block">Status</label>
                            <div class="d-flex gap-3 pt-1">
                                <label class="form-check mb-0 small">
                                    <input type="hidden" name="is_primary" value="0" form="{{ $mobileFormId }}">
                                    <input type="checkbox" name="is_primary" value="1" class="form-check-input" form="{{ $mobileFormId }}" @checked($mapping->is_primary)>
                                    Utama
                                </label>
                                <label class="form-check mb-0 small">
                                    <input type="hidden" name="active" value="0" form="{{ $mobileFormId }}">
                                    <input type="checkbox" name="active" value="1" class="form-check-input" form="{{ $mobileFormId }}" @checked($mapping->active)>
                                    Aktif
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mapping-actions mt-2">
                        <form id="{{ $mobileFormId }}" method="POST" action="{{ route('purchasing.supplier_items.update', $mapping) }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="item_id" value="{{ $mapping->item_id }}">
                            <input type="hidden" name="supplier_id" value="{{ $mapping->supplier_id }}">
                            <button type="submit" class="btn btn-sm btn-outline-primary w-100">Simpan</button>
                        </form>
                        <form method="POST" action="{{ route('purchasing.supplier_items.destroy', $mapping) }}" onsubmit="return confirm('Hapus mapping pemasok barang ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger w-100">Hapus</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-4">Belum ada mapping pemasok barang.</div>
            @endforelse
        </div>
        @if ($mappings->hasPages())
            <div class="p-3 border-top">{{ $mappings->links() }}</div>
        @endif
    </div>
    @else
        <div class="mapping-panel">
            <div class="mapping-panel-head">
                <div class="category-chip-list" id="category-chip-list"></div>
            </div>
            <div class="mapping-panel-body border-bottom">
                <div class="d-flex justify-content-between align-items-end gap-2 flex-wrap">
                    <div class="flex-grow-1" style="max-width:420px;">
                        <label class="form-label small fw-semibold mb-1">Cari Pemasok</label>
                        <input type="search" id="category-supplier-search" class="form-control form-control-sm" placeholder="Nama atau kode pemasok">
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="form-check mb-0">
                            <input type="checkbox" class="form-check-input" id="category-selected-only">
                            <label class="form-check-label small" for="category-selected-only">Terpilih saja</label>
                        </div>
                        <div class="autosave-state" id="category-save-state"></div>
                    </div>
                </div>
            </div>
            <div class="supplier-check-list" id="category-supplier-list"></div>
        </div>
    @endif
</div>
@endsection

@if ($mode === 'category')
@push('scripts')
@php
    $categoryJsonData = $categories->map(fn ($category) => [
        'id' => $category->id,
        'code' => $category->code,
        'name' => $category->name,
    ])->values();
    $supplierJsonData = $suppliers->map(fn ($supplier) => [
        'id' => $supplier->id,
        'code' => $supplier->code,
        'name' => $supplier->name,
    ])->values();
    $categoryMappingJsonData = $categoryMappings->map(fn ($mapping) => [
        'id' => $mapping->id,
        'item_category_id' => $mapping->item_category_id,
        'supplier_id' => $mapping->supplier_id,
        'is_primary' => $mapping->is_primary,
        'lead_time_days' => $mapping->lead_time_days,
        'active' => $mapping->active,
    ])->values();
@endphp
<script>
document.addEventListener('DOMContentLoaded', function () {
    const categories = @json($categoryJsonData);
    const suppliers = @json($supplierJsonData);
    let mappings = @json($categoryMappingJsonData);
    const syncUrl = @json(route('purchasing.supplier_category_mappings.sync'));
    const csrf = @json(csrf_token());
    const chipList = document.getElementById('category-chip-list');
    const supplierList = document.getElementById('category-supplier-list');
    const searchInput = document.getElementById('category-supplier-search');
    const selectedOnlyInput = document.getElementById('category-selected-only');
    const saveState = document.getElementById('category-save-state');
    let activeCategoryId = categories[0]?.id ?? null;

    function escapeHtml(value) {
        return String(value ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;');
    }

    function mappingFor(categoryId, supplierId) {
        return mappings.find(mapping => Number(mapping.item_category_id) === Number(categoryId)
            && Number(mapping.supplier_id) === Number(supplierId));
    }

    function selectedCount(categoryId) {
        return mappings.filter(mapping => Number(mapping.item_category_id) === Number(categoryId) && mapping.active).length;
    }

    function renderChips() {
        chipList.innerHTML = categories.map(category => `
            <button type="button" class="category-chip ${Number(category.id) === Number(activeCategoryId) ? 'is-active' : ''}" data-category-id="${category.id}">
                ${escapeHtml(category.name)} <span class="category-chip-count">${selectedCount(category.id)}</span>
            </button>`).join('');

        chipList.querySelectorAll('.category-chip').forEach(button => {
            button.addEventListener('click', function () {
                activeCategoryId = Number(button.dataset.categoryId);
                searchInput.value = '';
                selectedOnlyInput.checked = false;
                render();
            });
        });
    }

    function renderSuppliers() {
        const query = searchInput.value.trim().toLowerCase();
        const selectedOnly = selectedOnlyInput.checked;
        const visible = suppliers.filter(supplier => {
            const mapping = mappingFor(activeCategoryId, supplier.id);
            if (selectedOnly && !mapping?.active) return false;
            return !query || `${supplier.code} ${supplier.name}`.toLowerCase().includes(query);
        });

        if (!visible.length) {
            supplierList.innerHTML = '<div class="text-center text-muted py-4">Tidak ada pemasok yang sesuai.</div>';
            return;
        }

        supplierList.innerHTML = visible.map(supplier => {
            const mapping = mappingFor(activeCategoryId, supplier.id);
            const selected = Boolean(mapping?.active);
            const primary = selected && Boolean(mapping?.is_primary);
            return `
                <div class="supplier-check-row ${selected ? 'is-selected' : ''}" data-supplier-id="${supplier.id}">
                    <label class="supplier-check-main mb-0">
                        <input type="checkbox" class="form-check-input js-category-supplier" ${selected ? 'checked' : ''}>
                        <span class="min-w-0">
                            <span class="supplier-check-name d-block">${escapeHtml(supplier.name)}</span>
                            <span class="supplier-check-code">${escapeHtml(supplier.code)}</span>
                        </span>
                    </label>
                    <label class="d-flex align-items-center gap-2 mb-0 small ${selected ? '' : 'text-muted'}">
                        <input type="radio" class="form-check-input js-category-primary" name="category-primary" ${primary ? 'checked' : ''} ${selected ? '' : 'disabled'}>
                        <span>${primary ? 'Utama' : 'Jadikan utama'}</span>
                    </label>
                    <div class="input-group input-group-sm">
                        <input type="number" class="form-control js-category-lead" min="0" value="${mapping?.lead_time_days ?? ''}" placeholder="Lead time" ${selected ? '' : 'disabled'}>
                        <span class="input-group-text">hari</span>
                    </div>
                </div>`;
        }).join('');

        bindSupplierEvents();
    }

    function render() {
        renderChips();
        renderSuppliers();
    }

    function setSaveState(text, className) {
        saveState.textContent = text;
        saveState.className = `autosave-state ${className || ''}`;
    }

    async function syncSupplier(supplierId, selected, isPrimary, leadTime) {
        setSaveState('Menyimpan...', 'is-saving');
        try {
            const response = await fetch(syncUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({
                    item_category_id: activeCategoryId,
                    supplier_id: supplierId,
                    selected: selected,
                    is_primary: isPrimary,
                    lead_time_days: leadTime === '' ? null : Number(leadTime),
                }),
            });
            if (!response.ok) throw new Error();
            const data = await response.json();
            mappings = mappings.filter(mapping => Number(mapping.item_category_id) !== Number(activeCategoryId))
                .concat(data.mappings || []);
            render();
            setSaveState('Tersimpan', 'is-saved');
        } catch (error) {
            setSaveState('Gagal menyimpan', 'is-error');
            render();
        }
    }

    function bindSupplierEvents() {
        supplierList.querySelectorAll('.supplier-check-row').forEach(row => {
            const supplierId = Number(row.dataset.supplierId);
            const checkbox = row.querySelector('.js-category-supplier');
            const primary = row.querySelector('.js-category-primary');
            const lead = row.querySelector('.js-category-lead');
            const current = mappingFor(activeCategoryId, supplierId);

            checkbox.addEventListener('change', function () {
                if (!checkbox.checked && current?.is_primary && !confirm('Pemasok ini adalah pemasok utama. Hapus dari kategori?')) {
                    checkbox.checked = true;
                    return;
                }
                syncSupplier(supplierId, checkbox.checked, Boolean(current?.is_primary), lead.value);
            });
            primary.addEventListener('change', function () {
                if (primary.checked) syncSupplier(supplierId, true, true, lead.value);
            });
            lead.addEventListener('change', function () {
                syncSupplier(supplierId, true, Boolean(current?.is_primary), lead.value);
            });
        });
    }

    searchInput.addEventListener('input', renderSuppliers);
    selectedOnlyInput.addEventListener('change', renderSuppliers);
    render();
});
</script>
@endpush
@endif
