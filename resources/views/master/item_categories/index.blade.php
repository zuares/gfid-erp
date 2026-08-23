{{-- resources/views/master/item_categories/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Kategori Item')

@php
    $fmt = fn($n) => number_format((float) $n, 0, ',', '.');
    $activeKind = request('kind', '');
    $activeKindLabel = $activeKind ? ($kindLabels[$activeKind] ?? $activeKind) : null;
    $isItemMode = $activeKind !== '';
    $hasFilters = request()->hasAny(['q', 'kind', 'status']);
    $tabBase = request()->only(['q', 'status']);
    $tabUrl = function (?string $kind = null) use ($tabBase) {
        $params = $tabBase;
        if ($kind !== null && $kind !== '') {
            $params['kind'] = $kind;
        }
        return route('master.item_categories.index', array_filter($params, fn($v) => $v !== null && $v !== ''));
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
    .st-warning{ background: #fef3c7; color: #92400e; border-color: #fde68a; }

    body[data-theme="dark"] .st-active{ background: rgba(34, 197, 94, 0.20); color:#dcfce7; border-color: rgba(34, 197, 94, 0.55); }
    body[data-theme="dark"] .st-inactive{ background: rgba(239, 68, 68, 0.18); color:#fecaca; border-color: rgba(239, 68, 68, 0.55); }

    .empty{ padding:2.2rem 1.25rem; text-align:center; color:#64748b; }
    body[data-theme="dark"] .empty{ color:#9ca3af; }
    .divider{ height:1px; background: rgba(148, 163, 184, 0.20); }
    body[data-theme="dark"] .divider{ background: rgba(51, 65, 85, 0.85); }
    .flash-clean{ border-radius:8px; padding:.62rem .75rem; font-size:.84rem; border:1px solid rgba(148,163,184,.25); }

    .gf-category-tabs {
        display: flex; gap: .4rem; padding: .45rem; background: var(--card, #fff); border-radius: 8px; border: 1px solid var(--item-border); flex-wrap: wrap; margin-bottom: 1rem;
    }
    body[data-theme="dark"] .gf-category-tabs { background: rgba(15, 23, 42, 0.98); border-color: rgba(51, 65, 85, 0.85); }
    .gf-marketplace-tab {
        border-radius: 7px; font-size: .78rem; font-weight: 600; padding: .35rem .75rem; border: 1px solid transparent; color: #475569; text-decoration: none;
    }
    body[data-theme="dark"] .gf-marketplace-tab { color: #9ca3af; }
    .gf-marketplace-tab:hover { background: rgba(148, 163, 184, 0.08); color: #111827; }
    body[data-theme="dark"] .gf-marketplace-tab:hover { background: rgba(148, 163, 184, 0.15); color: #f8fafc; }
    .gf-marketplace-tab.is-active { background: var(--item-accent); color: #fff; border-color: var(--item-accent); }
    body[data-theme="dark"] .gf-marketplace-tab.is-active { background: #334155; border-color: #475569; color: #fff; }

    .gf-category-kind-dot {
        width: .44rem; height: .44rem; border-radius: 999px; background: currentColor; display: inline-block; margin-right: .36rem;
    }
    .kind-pill {
        display: inline-flex; align-items: center; border-radius: 999px; padding: .18rem .58rem; font-size: .72rem; font-weight: 600; border: 1px solid transparent; white-space: nowrap;
    }
    .kind-pill-dark { color: #0f172a; background: #f1f5f9; border-color: #e2e8f0; }
    .kind-pill-blue { color: #1d4ed8; background: #dbeafe; border-color: #bfdbfe; }
    .kind-pill-amber { color: #92400e; background: #fef3c7; border-color: #fde68a; }
    .kind-pill-slate { color: #475569; background: #f1f5f9; border-color: #e2e8f0; }
    .kind-pill-green { color: #166534; background: #dcfce7; border-color: #bbf7d0; }
    .kind-pill-muted { color: #64748b; background: #f8fafc; border-color: #e2e8f0; }
    
    body[data-theme="dark"] .kind-pill-dark { background: #1e293b; color: #f8fafc; border-color: #334155; }
    body[data-theme="dark"] .kind-pill-blue { background: rgba(29, 78, 216, 0.2); color: #93c5fd; border-color: rgba(37, 99, 235, 0.4); }
    body[data-theme="dark"] .kind-pill-amber { background: rgba(146, 64, 14, 0.2); color: #fcd34d; border-color: rgba(217, 119, 6, 0.4); }
    body[data-theme="dark"] .kind-pill-slate { background: rgba(71, 85, 105, 0.2); color: #cbd5e1; border-color: rgba(100, 116, 139, 0.4); }
    body[data-theme="dark"] .kind-pill-green { background: rgba(22, 101, 52, 0.2); color: #86efac; border-color: rgba(34, 197, 94, 0.4); }
    body[data-theme="dark"] .kind-pill-muted { background: rgba(100, 116, 139, 0.1); color: #94a3b8; border-color: rgba(100, 116, 139, 0.3); }
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
            <div class="title">Kategori Item</div>
            <div class="sub">Kelola kategori produk, bahan baku, pendukung, accessories, dan packaging.</div>

            <div class="kpis">
                <span class="kpi"><span class="lbl">Total Kategori</span><span class="val">{{ $fmt($totalCategories) }}</span></span>
                <span class="kpi"><span class="lbl">Aktif</span><span class="val">{{ $fmt($activeCount) }}</span></span>
                <span class="kpi"><span class="lbl">Dipakai Item</span><span class="val">{{ $fmt($usedCount) }}</span></span>
                <span class="kpi"><span class="lbl">Kosong</span><span class="val">{{ $fmt($emptyCount) }}</span></span>
            </div>
        </div>

        <div class="controls">
            <a href="{{ route('master.items.index') }}" class="btn btn-sm btn-item-outline btn-pill">
                Master Item
            </a>
            <button type="button" class="btn btn-sm btn-item-primary btn-pill" data-bs-toggle="modal"
                data-bs-target="#categoryModal" id="btnAddCategory">
                Tambah Kategori
            </button>
        </div>
    </div>

    <form method="GET" action="{{ route('master.item_categories.index') }}" class="gf-category-filter d-flex gap-2 flex-wrap mb-3 align-items-center" style="background: var(--card); padding: 10px 14px; border-radius: 8px; border: 1px solid var(--item-border);">
        <input type="hidden" name="kind" value="{{ $activeKind }}">
        <div style="flex: 1 1 200px;">
            <input type="search" name="q" class="form-control form-control-sm filter-input w-100"
                placeholder="{{ $isItemMode ? 'Kode / nama item' : 'Kode / nama kategori' }}"
                value="{{ request('q') }}" autocomplete="off" autofocus>
        </div>
        
        <select name="status" class="form-select form-select-sm filter-select" onchange="this.form.submit()" style="width: auto;">
            <option value="">Semua Status</option>
            <option value="active" @selected(request('status') === 'active')>Aktif</option>
            <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
        </select>

        @if ($hasFilters)
            <a href="{{ route('master.item_categories.index') }}" class="btn btn-sm btn-item-outline btn-pill" style="min-height: 30px; display: inline-flex; align-items: center;">Reset</a>
        @endif
    </form>

    <div class="gf-category-tabs">
        <a href="{{ $tabUrl(null) }}"
            class="gf-marketplace-tab {{ $activeKind === '' ? 'is-active' : '' }}">
            Kategori <span class="ms-1 fw-bold">{{ $fmt($totalCategories) }}</span>
        </a>
        @foreach ($kindLabels as $key => $label)
            <a href="{{ $tabUrl($key) }}"
                class="gf-marketplace-tab {{ $activeKind === $key ? 'is-active' : '' }}">
                {{ $label }} <span class="ms-1 fw-bold">{{ $fmt($kindItemCounts[$key] ?? 0) }}</span>
            </a>
        @endforeach
    </div>

    <div class="card card-main">
        <div class="card-body p-0">
            @if ($isItemMode)
                @if ($kindItems->isEmpty())
                    <div class="empty">
                        Tidak ada item {{ strtolower($activeKindLabel) }}.
                        <div class="mt-1">Ubah filter atau cek kategori item di Master Item.</div>
                    </div>
                @else
                    <div class="table-responsive" style="max-height: 65vh; overflow-y: auto;">
                        <table class="table table-hover align-middle table-list">
                            <thead>
                                <tr>
                                    <th style="width: 44px;" class="text-center">No.</th>
                                    <th style="width: 120px;">Kode Item</th>
                                    <th style="min-width: 200px;">Nama Item</th>
                                    <th style="width: 160px;">Kategori</th>
                                    <th style="width: 110px;">Tipe</th>
                                    <th style="width: 120px;">Produksi</th>
                                    <th style="width: 80px;" class="text-center">Satuan</th>
                                    <th style="width: 80px;" class="text-center">Barcode</th>
                                    <th style="width: 120px;" class="text-end">HPP</th>
                                    <th style="width: 100px;" class="text-center">Status</th>
                                    <th style="width: 90px;" class="text-end"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($kindItems as $item)
                                    @php
                                        $hpp = $item->active_unit_cost ?? 0;
                                        $typeLabels = [
                                            'material' => 'Material',
                                            'wip' => 'WIP',
                                            'finished_good' => 'Finished Good',
                                        ];
                                    @endphp
                                    <tr>
                                        <td class="text-center text-muted">{{ $kindItems->firstItem() + $loop->index }}</td>
                                        <td><div class="item-name">{{ $item->code }}</div></td>
                                        <td>
                                            <div class="item-name">{{ $item->name }}</div>
                                            <div class="muted">{{ $item->active ? 'Aktif di master item' : 'Nonaktif di master item' }}</div>
                                        </td>
                                        <td>
                                            @if ($item->category)
                                                <div class="item-name">{{ $item->category->code }}</div>
                                                <div class="muted">{{ $item->category->name }}</div>
                                            @else
                                                <span class="muted">-</span>
                                            @endif
                                        </td>
                                        <td><span class="muted">{{ $typeLabels[$item->type] ?? $item->type }}</span></td>
                                        <td>
                                            @if (in_array($item->type, ['finished_good', 'wip'], true))
                                                <div class="item-name">{{ $item->supply_mode_label }}</div>
                                                <div class="muted">Default: {{ $item->default_supply_source_label }}</div>
                                            @else
                                                <span class="muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center"><span class="muted">{{ $item->unit }}</span></td>
                                        <td class="text-center">
                                            @if ($item->barcodes_count > 0)
                                                <span class="badge-status st-active" style="padding:.1rem .35rem;">{{ $fmt($item->barcodes_count) }}</span>
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
                                            <a href="{{ route('master.items.edit', $item) }}" class="btn btn-sm btn-item-outline btn-pill px-3 fw-bold" style="font-size: 0.78rem;">
                                                Edit
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @elseif ($categories->isEmpty())
                <div class="empty">
                    Tidak ada kategori yang cocok.
                    <div class="mt-1">Ubah filter atau tambah kategori baru untuk kelompok ini.</div>
                </div>
            @else
                <div class="table-responsive" style="max-height: 65vh; overflow-y: auto;">
                    <table class="table table-hover align-middle table-list">
                        <thead>
                            <tr>
                                <th style="width: 44px;" class="text-center">No.</th>
                                <th style="width: 120px;">Kode</th>
                                <th style="min-width: 200px;">Nama Kategori</th>
                                <th style="width: 210px;">Kelompok</th>
                                <th style="width: 120px;" class="text-center">Item</th>
                                <th style="width: 110px;" class="text-center">Status</th>
                                <th style="width: 160px;" class="text-end"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($categories as $cat)
                                @php
                                    $tone = $kindTone[$cat->kind] ?? 'muted';
                                @endphp
                                <tr>
                                    <td class="text-center text-muted">{{ $categories->firstItem() + $loop->index }}</td>
                                    <td><div class="item-name">{{ $cat->code }}</div></td>
                                    <td>
                                        <div class="item-name">{{ $cat->name }}</div>
                                        <div class="muted">
                                            {{ $cat->items_count > 0 ? 'Dipakai di master item' : 'Belum dipakai item' }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="kind-pill kind-pill-{{ $tone }}">
                                            <span class="gf-category-kind-dot"></span>
                                            {{ $cat->kind_label }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if ($cat->items_count > 0)
                                            <span class="badge-status st-active" style="padding:.1rem .35rem;">{{ $fmt($cat->items_count) }} item</span>
                                        @else
                                            <span class="badge-status st-warning" style="padding:.1rem .35rem;">0 item</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($cat->active)
                                            <span class="badge-status st-active">Aktif</span>
                                        @else
                                            <span class="badge-status st-inactive">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1 align-items-center">
                                            <button type="button" class="btn btn-sm btn-item-outline btn-pill btn-edit-category" style="font-size: 0.78rem;"
                                                data-id="{{ $cat->id }}" data-code="{{ $cat->code }}"
                                                data-name="{{ $cat->name }}" data-kind="{{ $cat->kind }}"
                                                data-active="{{ $cat->active ? 1 : 0 }}"
                                                data-bs-toggle="modal" data-bs-target="#categoryModal">
                                                Edit
                                            </button>
                                            
                                            <div class="dropdown">
                                                <button class="btn btn-sm px-2 py-1 shadow-none border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: var(--item-muted); background: transparent;">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="border-radius: 8px; font-size: 0.82rem; border-color: var(--item-border);">
                                                    @if ($cat->items_count == 0)
                                                        <li>
                                                            <form method="POST"
                                                                action="{{ route('master.item_categories.destroy', $cat) }}"
                                                                onsubmit="return confirm('Hapus kategori &quot;{{ addslashes($cat->name) }}&quot;?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="dropdown-item py-2 text-danger fw-medium">
                                                                    <i class="bi bi-trash3 me-2"></i> Hapus
                                                                </button>
                                                            </form>
                                                        </li>
                                                    @else
                                                        <li>
                                                            <button type="button" class="dropdown-item py-2 text-muted" disabled title="Masih dipakai {{ $cat->items_count }} item">
                                                                <i class="bi bi-trash3 me-2"></i> Hapus
                                                            </button>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="divider"></div>

            <div class="p-3 d-flex justify-content-between align-items-center flex-wrap">
                <div class="muted">
                    @if ($isItemMode)
                        @if ($kindItems->total() > 0)
                            Menampilkan <strong>{{ $kindItems->firstItem() }}-{{ $kindItems->lastItem() }}</strong>
                            dari <strong>{{ $kindItems->total() }}</strong> item {{ strtolower($activeKindLabel) }}
                        @else
                            Tidak ada item {{ strtolower($activeKindLabel) }}.
                        @endif
                    @elseif ($categories->total() > 0)
                        Menampilkan <strong>{{ $categories->firstItem() }}-{{ $categories->lastItem() }}</strong>
                        dari <strong>{{ $categories->total() }}</strong> kategori
                    @else
                        Tidak ada data kategori.
                    @endif
                </div>
                <div>{{ $isItemMode ? $kindItems->links() : $categories->links() }}</div>
            </div>
        </div>
    </div>
</div>

    {{-- MODAL CREATE/EDIT --}}
    <div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="categoryForm" class="modal-content">
                @csrf
                <input type="hidden" name="_method" id="categoryMethod" value="POST">
                <div class="modal-header">
                    <h6 class="modal-title" id="categoryModalTitle">Tambah Kategori</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label mb-1" style="font-size:.8rem;">Kode</label>
                    <input type="text" name="code" id="categoryCode" class="form-control form-control-sm" maxlength="50"
                        required>

                    <label class="form-label mb-1 mt-2" style="font-size:.8rem;">Nama</label>
                    <input type="text" name="name" id="categoryName" class="form-control form-control-sm" maxlength="190"
                        required>

                    <label class="form-label mb-1 mt-2" style="font-size:.8rem;">Kelompok</label>
                    <select name="kind" id="categoryKind" class="form-select form-select-sm" required>
                        @foreach ($kindLabels as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>

                    <div class="form-check mt-3">
                        <input type="hidden" name="active" value="0">
                        <input type="checkbox" name="active" value="1" class="form-check-input" id="categoryActive"
                            checked>
                        <label class="form-check-label" for="categoryActive" style="font-size:.82rem;">Aktif</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')

{{-- GF_ITEM_CATEGORY_AUTO_FOCUS_SEARCH --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.querySelector('form.gf-category-filter');
    if (!filterForm) return;

    const searchInput =
        filterForm.querySelector('input[type="search"]') ||
        filterForm.querySelector('input[name="q"]') ||
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
        (function () {
            const form = document.getElementById('categoryForm');
            const title = document.getElementById('categoryModalTitle');
            const methodInput = document.getElementById('categoryMethod');
            const codeInput = document.getElementById('categoryCode');
            const nameInput = document.getElementById('categoryName');
            const kindInput = document.getElementById('categoryKind');
            const activeInput = document.getElementById('categoryActive');
            const addButton = document.getElementById('btnAddCategory');

            const storeUrl = "{{ route('master.item_categories.store') }}";
            const updateUrlBase = "{{ url('master/item-categories') }}";

            if (addButton) {
                addButton.addEventListener('click', function () {
                    form.action = storeUrl;
                    methodInput.value = 'POST';
                    title.textContent = 'Tambah Kategori';
                    codeInput.value = '';
                    nameInput.value = '';
                    kindInput.value = '{{ $activeKind ?: 'product' }}';
                    activeInput.checked = true;
                });
            }

            document.querySelectorAll('.btn-edit-category').forEach(btn => {
                btn.addEventListener('click', function () {
                    form.action = updateUrlBase + '/' + btn.dataset.id;
                    methodInput.value = 'PUT';
                    title.textContent = 'Edit Kategori';
                    codeInput.value = btn.dataset.code;
                    nameInput.value = btn.dataset.name;
                    kindInput.value = btn.dataset.kind || 'product';
                    activeInput.checked = btn.dataset.active === '1';
                });
            });
        })();
    </script>
@endpush
