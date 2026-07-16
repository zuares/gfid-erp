{{-- resources/views/master/items/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Master Item')

@php
    $fmt = fn($n) => number_format((float) $n, 0, ',', '.');

    $typeLabels = $types ?? [
        'raw_material' => 'Raw Material',
        'production_supply' => 'Supply Produksi',
        'shipping_supply' => 'Supply Packing',
        'finished_good' => 'Finished Good',
    ];

    $hasFilters = request()->hasAny(['q', 'type', 'category_kind', 'item_category_id']);

    $totalItems = method_exists($items, 'total') ? $items->total() : $items->count();

    $pageCollection = method_exists($items, 'getCollection') ? $items->getCollection() : collect($items);

    $activeCount = $pageCollection->where('active', 1)->count();
    $withBarcodeCount = $pageCollection->filter(fn($item) => (int) ($item->barcodes_count ?? optional($item->barcodes ?? null)->count() ?? 0) > 0)->count();
    $missingHppCount = $pageCollection->filter(fn($item) => (float) ($item->active_unit_cost ?? $item->effective_unit_cost ?? $item->hpp ?? 0) <= 0)->count();

    $buildUrl = function (array $params = []) {
        return route('master.items.index', array_filter(array_merge(request()->query(), $params), fn($v) => $v !== null && $v !== ''));
    };

    $activeType = (string) request('type', '');
@endphp

@push('head')
<style>
    :root{
        --item-accent:#334155;
        --item-accent-2:#1f2937;
        --item-border:rgba(148,163,184,.18);
        --item-border-strong:rgba(148,163,184,.30);
        --item-muted:#64748b;
    }
    .page-wrap{ max-width:1280px; margin-inline:auto; padding:.75rem .75rem 4rem; background:transparent!important; }

    .card-main{
        background: var(--card);
        border-radius: 8px;
        border: 1px solid var(--item-border);
        box-shadow: none;
        overflow:hidden;
    }
    body[data-theme="dark"] .card-main{
        border-color: rgba(51,65,85,.85);
        box-shadow: none;
    }

    .item-topbar{
        position:sticky;
        top:0;
        z-index:300;
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:.6rem;
        flex-wrap:wrap;
        padding:.45rem .75rem;
        margin-inline:-.75rem;
        margin-bottom:.65rem;
        background:var(--card,#fff);
        border-bottom:1px solid var(--item-border);
    }
    body[data-theme="dark"] .item-topbar{ background:var(--card,#0f172a); }
    .title{ font-weight: 750; font-size:1rem; letter-spacing: 0; margin:0; }
    .sub{ color:var(--item-muted); font-size:.78rem; }
    body[data-theme="dark"] .sub{ color:#9ca3af; }

    .kpis{ display:flex; flex-wrap:wrap; gap:.32rem; margin-top:.35rem; }
    .kpi{
        display:inline-flex; align-items:baseline; gap:.45rem;
        border-radius:7px; padding:.2rem .48rem;
        border:1px solid rgba(148,163,184,.28);
        background: transparent;
        font-size:.72rem;
    }
    body[data-theme="dark"] .kpi{
        background: rgba(15, 23, 42, 0.96);
        border-color: rgba(51, 65, 85, 0.85);
    }
    .kpi .lbl{ text-transform:none; letter-spacing:0; font-size:.66rem; color:#94a3b8; }
    body[data-theme="dark"] .kpi .lbl{ color:#6b7280; }
    .kpi .val{ font-weight:650; color:var(--item-accent); }

    .controls{ display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; }
    .filter-label{ font-size:.8rem; color:#6b7280; }
    body[data-theme="dark"] .filter-label{ color:#9ca3af; }
    .filter-select{ border-radius:7px; padding-left:.75rem; padding-right:2rem; font-size:.82rem; }
    .filter-input{ border-radius:7px; padding-left:.75rem; font-size:.82rem; min-height:30px; }
    .btn-pill{ border-radius:7px; padding-inline:.78rem; box-shadow:none!important; font-weight:600; }
    .btn-item-primary{ background:var(--item-accent)!important; border-color:var(--item-accent)!important; color:#fff!important; }
    .btn-item-primary:hover{ background:var(--item-accent-2)!important; border-color:var(--item-accent-2)!important; color:#fff!important; }
    .btn-item-outline{ color:#475569!important; background:transparent!important; border:1px solid rgba(148,163,184,.35)!important; }
    .btn-item-outline:hover{ background:rgba(148,163,184,.08)!important; color:#111827!important; }
    body[data-theme="dark"] .btn-item-outline { color: #cbd5e1!important; }
    body[data-theme="dark"] .btn-item-outline:hover { color: #f8fafc!important; background: rgba(148,163,184,.15)!important; }
    .btn-fresh{ border-color:#fecaca; color:#b91c1c; background:transparent; }
    .btn-fresh:hover{ background:#fef2f2; color:#991b1b; border-color:#fca5a5; }

    .table-list{ margin-bottom:0; }
    .table-list thead th{
        border-bottom-width:1px;
        font-size:.68rem;
        text-transform:none;
        letter-spacing:0;
        color:#64748b;
        background: var(--card,#fff);
        padding:.52rem .62rem;
        white-space:nowrap;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    body[data-theme="dark"] .table-list thead th{
        background: rgba(15, 23, 42, 0.98);
        color:#9ca3af;
        border-bottom-color: rgba(30, 64, 175, 0.6);
    }
    .table-list tbody td{
        vertical-align:middle;
        border-top-color: rgba(148, 163, 184, 0.16);
        padding:.52rem .62rem;
    }
    body[data-theme="dark"] .table-list tbody td{ border-top-color: rgba(51, 65, 85, 0.85); }

    .code-link{ font-weight:700; text-decoration:none; color:inherit; }
    .code-link:hover{ text-decoration:underline; }
    .muted{ font-size:.82rem; color:#6b7280; }
    body[data-theme="dark"] .muted{ color:#9ca3af; }
    .item-name{ font-weight:600; }

    .badge-status{
        border-radius:7px; padding:.16rem .48rem;
        font-size:.68rem; letter-spacing:0; text-transform:none;
        border:1px solid transparent;
        display:inline-flex; align-items:center; gap:.35rem;
        white-space:nowrap;
    }
    .badge-status::before{ content:''; width:7px; height:7px; border-radius:999px; display:inline-block; }

    .st-active{ background: rgba(34, 197, 94, 0.10); color:#166534; border-color: rgba(34, 197, 94, 0.30); }
    .st-active::before{ background: rgba(34, 197, 94, 0.95); }
    .st-inactive{ background: rgba(239, 68, 68, 0.10); color:#991b1b; border-color: rgba(239, 68, 68, 0.30); }
    .st-inactive::before{ background: rgba(239, 68, 68, 0.95); }
    .st-draft{ background: rgba(148, 163, 184, 0.10); color:#475569; border-color: rgba(148, 163, 184, 0.30); }
    .st-draft::before{ background: rgba(100, 116, 139, 0.95); }
    .st-warning{ background: #fef3c7; color: #92400e; border-color: #fde68a; }

    body[data-theme="dark"] .st-active{ background: rgba(34, 197, 94, 0.20); color:#dcfce7; border-color: rgba(34, 197, 94, 0.55); }
    body[data-theme="dark"] .st-inactive{ background: rgba(239, 68, 68, 0.18); color:#fecaca; border-color: rgba(239, 68, 68, 0.55); }

    .empty{ padding:2.2rem 1.25rem; text-align:center; color:#64748b; }
    body[data-theme="dark"] .empty{ color:#9ca3af; }
    .divider{ height:1px; background: rgba(148, 163, 184, 0.20); }
    body[data-theme="dark"] .divider{ background: rgba(51, 65, 85, 0.85); }
    .flash-clean{ border-radius:8px; padding:.62rem .75rem; font-size:.84rem; border:1px solid rgba(148,163,184,.25); }

    /* Bulk toolbar adjustments */
    #bulkToolbar {
        border-bottom: 1px solid var(--item-border);
        background: var(--card);
        padding: 10px 12px;
        position: sticky;
        top: 0;
        z-index: 100;
    }
    body[data-theme="dark"] #bulkToolbar {
        border-bottom-color: rgba(51, 65, 85, 0.85);
        background: rgba(15, 23, 42, 0.98);
    }
</style>
@endpush

@section('content')
<div class="page-wrap">
    @if(session('success'))
        <div class="flash-clean alert alert-success mb-2">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="flash-clean alert alert-danger mb-2">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="flash-clean alert alert-danger mb-2">{{ $errors->first() }}</div>
    @endif

    <div class="item-topbar">
        <div>
            <div class="title">Master Item</div>
            <div class="sub">Kelola SKU, kategori, barcode, status, dan HPP.</div>

            <div class="kpis">
                <span class="kpi"><span class="lbl">Total</span><span class="val">{{ $fmt($totalItems) }}</span></span>
                <span class="kpi"><span class="lbl">Aktif</span><span class="val">{{ $fmt($activeCount) }}</span></span>
                <span class="kpi"><span class="lbl">Ber-barcode</span><span class="val">{{ $fmt($withBarcodeCount) }}</span></span>
                <span class="kpi"><span class="lbl">HPP Kosong</span><span class="val">{{ $fmt($missingHppCount) }}</span></span>
            </div>
        </div>

        <div class="controls">
            <a href="{{ route('master.item_categories.index') }}" class="btn btn-sm btn-item-outline btn-pill">
                Kategori
            </a>
            <a href="{{ route('master.items.create') }}" class="btn btn-sm btn-item-primary btn-pill">
                Tambah Item
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('master.items.index') }}" class="gf-category-filter d-flex gap-2 flex-wrap mb-3 align-items-center" style="background: var(--card); padding: 10px 14px; border-radius: 8px; border: 1px solid var(--item-border);">
        <div style="flex: 1 1 200px;">
            <input type="search" name="q" class="form-control form-control-sm filter-input w-100"
                value="{{ request('q') }}" placeholder="Cari Kode/SKU/Nama...">
        </div>

        <select name="type" class="form-select form-select-sm filter-select" onchange="this.form.submit()" style="width: auto;">
            <option value="">Semua Tipe</option>
            @foreach ($typeLabels as $key => $label)
                <option value="{{ $key }}" @selected(request('type') == $key)>{{ $label }}</option>
            @endforeach
        </select>

        <select name="category_kind" class="form-select form-select-sm filter-select" onchange="this.form.submit()" style="width: auto;">
            <option value="">Semua Kelompok</option>
            @foreach ($categoryKinds ?? [] as $key => $label)
                <option value="{{ $key }}" @selected(request('category_kind') == $key)>{{ $label }}</option>
            @endforeach
        </select>

        <select name="item_category_id" class="form-select form-select-sm filter-select" onchange="this.form.submit()" style="width: auto;">
            <option value="">Semua Kategori</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" @selected((string) request('item_category_id') === (string) $cat->id)>
                    {{ $cat->code }}
                </option>
            @endforeach
        </select>

        @if ($hasFilters)
            <a href="{{ route('master.items.index') }}" class="btn btn-sm btn-item-outline btn-pill" style="min-height: 30px; display: inline-flex; align-items: center;">Reset</a>
        @endif
    </form>

    <div class="card card-main">
        <div id="bulkToolbar" class="d-none">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="fw-bold" style="font-size:.82rem;">
                    <span id="selectedCount">0</span> terpilih
                </span>
                <span class="text-muted" style="font-size:.78rem;">— aksi:</span>
                <button type="button" class="btn btn-item-outline btn-sm btn-pill" data-bs-toggle="modal" data-bs-target="#bulkCategoryModal">Kategori</button>
                <button type="button" class="btn btn-item-outline btn-sm btn-pill" data-bs-toggle="modal" data-bs-target="#bulkTypeModal">Tipe</button>
                <button type="button" class="btn btn-item-outline btn-sm btn-pill" data-bs-toggle="modal" data-bs-target="#bulkHppModal">Set HPP</button>
                <button type="button" class="btn btn-link btn-sm text-muted ms-auto" id="bulkClear">Batal</button>
            </div>
        </div>

        <div class="card-body p-0">
            @if ($items->count() === 0)
                <div class="empty">
                    Tidak ada item yang cocok.
                    <div class="mt-1">Ubah filter atau klik <b>Tambah Item</b>.</div>
                </div>
            @else
                <div class="table-responsive" style="max-height: 65vh; overflow-y: auto;">
                    <table class="table table-hover align-middle table-list">
                        <thead>
                            <tr>
                                <th style="width: 44px;" class="text-center">
                                    <input type="checkbox" class="form-check-input" id="checkAll">
                                </th>
                                <th style="width: 120px;">Kode / Kategori</th>
                                <th style="min-width: 200px;">Item / Tipe</th>
                                <th class="text-center" style="width: 90px;">Satuan</th>
                                <th class="text-center" style="width: 90px;">Barcode</th>
                                <th class="text-end" style="width: 120px;">HPP</th>
                                <th class="text-center" style="width: 100px;">Status</th>
                                <th class="text-end" style="width: 160px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                @php
                                    $hpp = (float) ($item->active_unit_cost ?? $item->effective_unit_cost ?? $item->hpp ?? 0);
                                    $barcodeCount = (int) ($item->barcodes_count ?? optional($item->barcodes ?? null)->count() ?? 0);
                                @endphp
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input row-check" value="{{ $item->id }}">
                                    </td>
                                    <td>
                                        <div class="item-name">{{ $item->code }}</div>
                                        <div class="muted">
                                            @if($item->category)
                                                {{ $item->category->code }}
                                            @else
                                                -
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="item-name">{{ $item->name }}</div>
                                        <div class="muted">
                                            @if(!empty($item->sku)) SKU: {{ $item->sku }} | @endif
                                            {{ $typeLabels[$item->type] ?? $item->type }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        {{ $item->unit ?: '-' }}
                                    </td>
                                    <td class="text-center">
                                        @if ($barcodeCount > 0)
                                            <span class="badge-status st-active" style="padding:.1rem .35rem;">{{ $fmt($barcodeCount) }}</span>
                                        @else
                                            <span class="badge-status st-warning" style="padding:.1rem .35rem;">0</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if ($hpp > 0)
                                            <span class="fw-semibold">Rp {{ number_format($hpp, 0, ',', '.') }}</span>
                                        @else
                                            <span class="muted">Kosong</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($item->active)
                                            <span class="badge-status st-active">Aktif</span>
                                        @else
                                            <span class="badge-status st-inactive">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1 align-items-center">
                                            <a href="{{ route('master.items.edit', $item) }}" class="btn btn-sm btn-item-outline btn-pill px-3 fw-bold" style="font-size: 0.78rem;">
                                                Edit
                                            </a>
                                            <div class="dropdown">
                                                <button class="btn btn-sm px-2 py-1 shadow-none border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: var(--item-muted); background: transparent;">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="border-radius: 8px; font-size: 0.82rem; border-color: var(--item-border);">
                                                    <li>
                                                        <a class="dropdown-item py-2 fw-medium" href="{{ route('master.items.hpp_temp.edit', $item) }}">
                                                            <i class="bi bi-tag-fill me-2 text-success"></i> Set HPP
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form method="POST" action="{{ route('master.items.destroy', $item) }}" onsubmit="return confirm('Hapus item {{ addslashes($item->code) }}?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item py-2 text-danger fw-medium">
                                                                <i class="bi bi-trash3 me-2"></i> Hapus
                                                            </button>
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

                <div class="divider"></div>

                <div class="p-3 d-flex justify-content-between align-items-center flex-wrap">
                    <div class="muted">
                        @if (method_exists($items, 'firstItem') && $items->total())
                            Menampilkan {{ $items->firstItem() }}–{{ $items->lastItem() }} dari {{ $fmt($items->total()) }} item
                        @else
                            Total {{ $fmt($items->count()) }} item
                        @endif
                    </div>
                    <div>
                        {{ $items->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>


    {{-- MODAL BULK: KATEGORI --}}
    <div class="modal fade" id="bulkCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('master.items.bulk_update') }}" class="modal-content bulk-form">
                @csrf
                <input type="hidden" name="action" value="category">

                <div class="modal-header">
                    <h6 class="modal-title">Ubah Kategori (<span class="sel-count">0</span> item)</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <label class="form-label mb-1" style="font-size:.8rem;">Kategori baru</label>
                    <select name="item_category_id" class="form-select form-select-sm">
                        <option value="">Tanpa kategori</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->code }} — {{ $cat->name }}</option>
                        @endforeach
                    </select>

                    <div class="ids-container"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm text-white">Terapkan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL BULK: TIPE --}}
    <div class="modal fade" id="bulkTypeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('master.items.bulk_update') }}" class="modal-content bulk-form">
                @csrf
                <input type="hidden" name="action" value="type">

                <div class="modal-header">
                    <h6 class="modal-title">Ubah Tipe (<span class="sel-count">0</span> item)</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <label class="form-label mb-1" style="font-size:.8rem;">Tipe baru</label>
                    <select name="type" class="form-select form-select-sm" required>
                        @foreach ($typeLabels as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>

                    <div class="ids-container"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm text-white">Terapkan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL BULK: HPP --}}
    <div class="modal fade" id="bulkHppModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('master.items.bulk_update') }}" class="modal-content bulk-form">
                @csrf
                <input type="hidden" name="action" value="hpp">

                <div class="modal-header">
                    <h6 class="modal-title">Set HPP Sementara (<span class="sel-count">0</span> item)</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-warning py-2 px-3 mb-3" style="font-size:.78rem;">
                        <strong class="sel-count">0</strong> item terpilih. Snapshot lama akan dinonaktifkan.
                    </div>

                    <label class="form-label mb-1" style="font-size:.8rem;">HPP per unit (Rp)</label>
                    <input type="number" step="0.01" min="0" name="unit_cost" class="form-control form-control-sm" required>

                    <label class="form-label mb-1 mt-2" style="font-size:.8rem;">Catatan (opsional)</label>
                    <input type="text" name="notes" class="form-control form-control-sm" maxlength="255"
                        placeholder="Contoh: update manual dari master item">

                    <div class="ids-container"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm">Terapkan HPP</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')

{{-- GF_MASTER_ITEMS_AUTO_FOCUS_SEARCH --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.querySelector('form.gf-category-filter');
    if (!filterForm) return;

    const searchInput =
        filterForm.querySelector('input[name="q"]') ||
        filterForm.querySelector('input[type="search"]') ||
        filterForm.querySelector('input[name="search"]') ||
        filterForm.querySelector('input[type="text"]');

    if (!searchInput) return;

    searchInput.setAttribute('autocomplete', 'off');
    searchInput.setAttribute('autofocus', 'autofocus');

    setTimeout(function () {
        searchInput.focus();

        const value = searchInput.value || '';
        try {
            searchInput.setSelectionRange(value.length, value.length);
        } catch (e) {}
    }, 120);
});
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const checkAll = document.getElementById('checkAll');
        const toolbar = document.getElementById('bulkToolbar');
        const selectedCount = document.getElementById('selectedCount');
        const clearBtn = document.getElementById('bulkClear');

        function rowChecks() {
            return Array.from(document.querySelectorAll('.row-check'));
        }

        function selectedChecks() {
            return rowChecks().filter(c => c.checked);
        }

        function refreshBulkUi() {
            const selected = selectedChecks();
            const count = selected.length;

            if (toolbar) {
                toolbar.classList.toggle('d-none', count === 0);
            }

            if (selectedCount) {
                selectedCount.textContent = count;
            }

            document.querySelectorAll('.sel-count').forEach(el => {
                el.textContent = count;
            });

            if (checkAll) {
                const rows = rowChecks();
                checkAll.checked = rows.length > 0 && rows.every(c => c.checked);
                checkAll.indeterminate = count > 0 && count < rows.length;
            }
        }

        if (checkAll) {
            checkAll.addEventListener('change', function () {
                rowChecks().forEach(c => c.checked = checkAll.checked);
                refreshBulkUi();
            });
        }

        rowChecks().forEach(c => c.addEventListener('change', refreshBulkUi));

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                rowChecks().forEach(c => c.checked = false);
                refreshBulkUi();
            });
        }

        document.querySelectorAll('.bulk-form').forEach(form => {
            form.addEventListener('submit', function (e) {
                const selected = selectedChecks();

                if (!selected.length) {
                    e.preventDefault();
                    alert('Pilih minimal 1 item dulu.');
                    return;
                }

                const container = form.querySelector('.ids-container');
                if (!container) return;

                container.innerHTML = '';

                selected.forEach(c => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'item_ids[]';
                    input.value = c.value;
                    container.appendChild(input);
                });
            });
        });

        const filterForm = document.querySelector('form.gf-category-filter');

        if (filterForm) {
            const searchInput = filterForm.querySelector('input[name="q"]');
            const selects = Array.from(filterForm.querySelectorAll('select'));
            const filterButton = filterForm.querySelector('button[type="submit"], button:not([type])');
            let timer = null;
            let isSubmitting = false;

            function ensureIndicator() {
                if (!searchInput) return null;

                let wrap = searchInput.closest('.gf-filter-live-wrap');

                if (!wrap) {
                    wrap = document.createElement('div');
                    wrap.className = 'gf-filter-live-wrap';
                    searchInput.parentNode.insertBefore(wrap, searchInput);
                    wrap.appendChild(searchInput);
                }

                let indicator = wrap.querySelector('.gf-filter-live-indicator');

                if (!indicator) {
                    indicator = document.createElement('span');
                    indicator.className = 'gf-filter-live-indicator';
                    indicator.innerHTML = '<span class="gf-filter-live-dot"></span><span>filter...</span>';
                    wrap.appendChild(indicator);
                }

                return indicator;
            }

            function showLoading() {
                const indicator = ensureIndicator();
                if (indicator) indicator.classList.add('is-show');

                if (filterButton) {
                    filterButton.disabled = true;
                    filterButton.dataset.originalText = filterButton.dataset.originalText || filterButton.innerHTML;
                    filterButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Filter';
                }
            }

            function submitRealtime(delay = 350) {
                clearTimeout(timer);

                timer = setTimeout(function () {
                    if (isSubmitting) return;
                    isSubmitting = true;
                    showLoading();

                    Array.from(filterForm.elements).forEach(function (el) {
                        if (!el.name || el.type === 'hidden') return;
                        if ((el.tagName === 'INPUT' || el.tagName === 'SELECT') && String(el.value || '').trim() === '') {
                            el.disabled = true;
                        }
                    });

                    filterForm.requestSubmit ? filterForm.requestSubmit() : filterForm.submit();
                }, delay);
            }

            if (searchInput) {
                searchInput.setAttribute('autocomplete', 'off');

                

                searchInput.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        submitRealtime(0);
                    }
                });
            }

            selects.forEach(function (select) {
                select.addEventListener('change', function () {
                    submitRealtime(80);
                });
            });

            if (!filterForm.querySelector('.gf-filter-hint')) {
                const hint = document.createElement('div');
                hint.className = 'gf-filter-hint';
                hint.innerHTML = '<i class="bi bi-lightning-charge-fill"></i><span>Filter otomatis — Tekan Enter pada pencarian atau ubah pilihan untuk memperbarui data.</span>';
                filterForm.insertAdjacentElement('afterend', hint);
            }
        }

        refreshBulkUi();
    });
</script>
@endpush
