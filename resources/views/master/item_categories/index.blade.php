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
        'other' => 'muted',
    ];
@endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .gf-category-page {
            display: grid;
            gap: 1rem;
        }

        .gf-category-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: .55rem;
            flex-wrap: wrap;
        }

        .gf-category-actions .btn,
        .gf-category-filter .btn {
            min-height: 38px;
            border-radius: 999px !important;
            font-size: .8rem;
            font-weight: 850;
            padding-inline: .95rem;
        }

        .gf-category-kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .7rem;
        }

        .gf-category-kpi-card {
            border: 1px solid rgba(15, 23, 42, .075);
            border-radius: 16px;
            background: linear-gradient(180deg, #fff 0%, #fcfcfd 100%);
            padding: .86rem .95rem;
        }

        .gf-category-kpi-label {
            color: #64748b;
            font-size: .66rem;
            font-weight: 950;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: .32rem;
        }

        .gf-category-kpi-value {
            color: #0f172a;
            font-size: 1.18rem;
            font-weight: 950;
            line-height: 1.1;
        }

        .gf-category-kpi-note {
            color: #94a3b8;
            font-size: .72rem;
            font-weight: 800;
            margin-top: .22rem;
        }

        .gf-category-filter {
            display: grid;
            grid-template-columns: minmax(220px, 1fr) minmax(150px, .45fr) auto;
            gap: .65rem;
            align-items: end;
        }

        .gf-category-filter .form-label {
            color: #64748b;
            font-size: .72rem;
            font-weight: 900;
            letter-spacing: .02em;
            margin-bottom: .3rem;
        }

        .gf-category-filter .form-control,
        .gf-category-filter .form-select {
            min-height: 40px;
            border-radius: 14px;
            border-color: rgba(15, 23, 42, .10);
            font-size: .84rem;
            box-shadow: none;
        }

        .gf-category-tabs {
            margin-top: .75rem;
        }

        .gf-category-kind {
            display: inline-flex;
            align-items: center;
            gap: .36rem;
            border-radius: 999px;
            padding: .18rem .58rem;
            font-size: .72rem;
            font-weight: 850;
            border: 1px solid transparent;
            white-space: nowrap;
        }

        .gf-category-kind-dot {
            width: .44rem;
            height: .44rem;
            border-radius: 999px;
            background: currentColor;
        }

        .gf-category-kind-dark { color: #0f172a; background: #f1f5f9; border-color: #e2e8f0; }
        .gf-category-kind-blue { color: #1d4ed8; background: #dbeafe; border-color: #bfdbfe; }
        .gf-category-kind-amber { color: #92400e; background: #fef3c7; border-color: #fde68a; }
        .gf-category-kind-slate { color: #475569; background: #f1f5f9; border-color: #e2e8f0; }
        .gf-category-kind-green { color: #166534; background: #dcfce7; border-color: #bbf7d0; }
        .gf-category-kind-muted { color: #64748b; background: #f8fafc; border-color: #e2e8f0; }

        .gf-category-code {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 46px;
            border-radius: 10px;
            padding: .22rem .48rem;
            background: #0f172a;
            color: #fff;
            font-size: .74rem;
            font-weight: 900;
            letter-spacing: .03em;
        }

        .gf-category-name {
            color: #0f172a;
            font-weight: 850;
        }

        .gf-category-sub {
            color: #94a3b8;
            font-size: .72rem;
            margin-top: .12rem;
        }

        .gf-category-row-actions {
            display: flex;
            justify-content: flex-end;
            gap: .35rem;
            flex-wrap: nowrap;
        }

        .gf-category-row-actions .btn {
            border-radius: 999px !important;
            font-size: .74rem;
            font-weight: 800;
        }

        .gf-category-count {
            display: inline-flex;
            align-items: baseline;
            justify-content: center;
            gap: .25rem;
            min-width: 58px;
            border-radius: 999px;
            padding: .2rem .65rem;
            background: #eff6ff;
            color: #1d4ed8;
            font-weight: 900;
            font-variant-numeric: tabular-nums;
        }

        .gf-category-count small {
            color: #60a5fa;
            font-size: .66rem;
            font-weight: 850;
        }

        .gf-category-type {
            display: inline-flex;
            border-radius: 999px;
            padding: .18rem .55rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #475569;
            font-size: .72rem;
            font-weight: 850;
            white-space: nowrap;
        }

        .gf-category-foot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .75rem;
            flex-wrap: wrap;
            border-top: 1px solid #eef2f7;
            padding: .85rem 1rem;
            color: #64748b;
            font-size: .76rem;
        }

        .gf-category-empty {
            text-align: center;
            color: #64748b;
            padding: 2.2rem 1rem;
        }

        .gf-category-empty-title {
            color: #0f172a;
            font-weight: 900;
            margin-bottom: .2rem;
        }

        @media (max-width: 768px) {
            .gf-category-kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .gf-category-filter {
                grid-template-columns: 1fr;
            }

            .gf-category-actions {
                justify-content: flex-start;
            }

            .gf-category-row-actions {
                flex-wrap: wrap;
            }
        }

        @media (max-width: 576px) {
            .gf-category-kpi-grid {
                grid-template-columns: 1fr;
            }

            .gf-master-actions {
                flex-basis: 100%;
            }
        }
    
/* GF ITEM CATEGORY UI OVERRIDE - selaras Master Items */
.gf-category-page {
    max-width: 1180px !important;
    margin: 0 auto !important;
    padding: 16px 12px 28px !important;
    color: #0f172a !important;
    font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif !important;
}

.gf-category-head {
    display: flex !important;
    justify-content: space-between !important;
    align-items: stretch !important;
    gap: 14px !important;
    margin-bottom: 14px !important;
    padding: 18px !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 24px !important;
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 58%, #f1f5f9 100%) !important;
    box-shadow: 0 16px 42px rgba(15, 23, 42, .07) !important;
}

.gf-category-head h1,
.gf-category-head .h1,
.gf-category-page h1 {
    color: #0f172a !important;
    font-size: 1.34rem !important;
    font-weight: 950 !important;
    letter-spacing: -.05em !important;
    line-height: 1.1 !important;
    margin: 0 !important;
}

.gf-category-page .text-muted,
.gf-category-sub {
    color: #64748b !important;
}

.gf-category-actions {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 8px !important;
    align-items: center !important;
    justify-content: flex-end !important;
}

.gf-category-actions .btn,
.gf-category-filter .btn {
    border-radius: 999px !important;
    font-weight: 850 !important;
    letter-spacing: -.01em !important;
    min-height: 34px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 6px !important;
}

.gf-category-actions .btn-primary,
.gf-category-filter .btn-primary,
.gf-category-page .btn-primary {
    color: #ffffff !important;
    background: linear-gradient(135deg, #0f172a, #334155) !important;
    border-color: transparent !important;
    box-shadow: 0 12px 24px rgba(15, 23, 42, .12) !important;
}

.gf-category-kpi-grid {
    display: grid !important;
    grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
    gap: 10px !important;
    margin-bottom: 12px !important;
}

.gf-category-kpi-card {
    border: 1px solid #e2e8f0 !important;
    border-radius: 20px !important;
    padding: 14px !important;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%) !important;
    box-shadow: 0 14px 34px rgba(15, 23, 42, .06) !important;
}

.gf-category-kpi-label {
    font-size: .7rem !important;
    font-weight: 900 !important;
    color: #64748b !important;
    text-transform: uppercase !important;
    letter-spacing: .045em !important;
}

.gf-category-kpi-value {
    font-size: 1.28rem !important;
    font-weight: 900 !important;
    color: #0f172a !important;
    letter-spacing: -.04em !important;
    margin-top: 5px !important;
}

.gf-category-kpi-note {
    font-size: .74rem !important;
    color: #94a3b8 !important;
    margin-top: 2px !important;
}

.gf-category-filter {
    display: grid !important;
    grid-template-columns: minmax(220px, 1fr) minmax(170px, .55fr) minmax(170px, .55fr) auto !important;
    gap: 10px !important;
    align-items: end !important;
    margin-bottom: 12px !important;
}

.gf-category-filter .form-label {
    font-size: .72rem !important;
    font-weight: 900 !important;
    color: #334155 !important;
    margin-bottom: 5px !important;
    text-transform: uppercase !important;
    letter-spacing: .045em !important;
}

.gf-category-filter .form-control,
.gf-category-filter .form-select,
.gf-category-page .modal .form-control,
.gf-category-page .modal .form-select {
    border-radius: 14px !important;
    border: 1px solid #e2e8f0 !important;
    min-height: 38px !important;
    color: #0f172a !important;
    font-size: .84rem !important;
    font-weight: 650 !important;
    background: #ffffff !important;
    box-shadow: none !important;
}

.gf-category-filter .form-control:focus,
.gf-category-filter .form-select:focus,
.gf-category-page .modal .form-control:focus,
.gf-category-page .modal .form-select:focus {
    border-color: #94a3b8 !important;
    box-shadow: 0 0 0 .22rem rgba(15, 23, 42, .08) !important;
}

.gf-marketplace-tabs,
.gf-category-tabs {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 8px !important;
    padding: 8px !important;
    border: 1px solid #e2e8f0 !important;
    background: #f8fafc !important;
    border-radius: 18px !important;
    margin-bottom: 12px !important;
}

.gf-marketplace-tabs a,
.gf-marketplace-tabs .btn,
.gf-category-tabs a,
.gf-category-tabs .btn {
    border-radius: 999px !important;
    font-size: .78rem !important;
    font-weight: 850 !important;
    padding: 7px 12px !important;
    border: 1px solid transparent !important;
    color: #475569 !important;
    background: transparent !important;
    text-decoration: none !important;
}

.gf-marketplace-tabs a.active,
.gf-marketplace-tabs .btn.active,
.gf-category-tabs a.active,
.gf-category-tabs .btn.active {
    color: #ffffff !important;
    background: #0f172a !important;
    border-color: #0f172a !important;
    box-shadow: 0 10px 22px rgba(15, 23, 42, .12) !important;
}

.gf-clean-table {
    font-size: .82rem !important;
    color: #0f172a !important;
    margin: 0 !important;
}

.gf-clean-table thead th,
.gf-sticky-table thead th {
    position: sticky !important;
    top: 0 !important;
    z-index: 8 !important;
    background: #f8fafc !important;
    color: #64748b !important;
    font-size: .7rem !important;
    text-transform: uppercase !important;
    letter-spacing: .045em !important;
    font-weight: 900 !important;
    border-bottom: 1px solid #e2e8f0 !important;
    padding: 12px 10px !important;
    white-space: nowrap !important;
}

.gf-clean-table tbody td {
    border-color: #eef2f7 !important;
    padding: 12px 10px !important;
    vertical-align: middle !important;
}

.gf-clean-table tbody tr:hover {
    background: #f8fbff !important;
}

.gf-category-code {
    display: inline-flex !important;
    align-items: center !important;
    border-radius: 999px !important;
    padding: 5px 9px !important;
    background: #f1f5f9 !important;
    color: #334155 !important;
    border: 1px solid #e2e8f0 !important;
    font-size: .73rem !important;
    font-weight: 900 !important;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
}

.gf-category-name {
    font-weight: 850 !important;
    color: #0f172a !important;
    letter-spacing: -.02em !important;
}

.gf-category-kind,
.gf-category-type,
.gf-category-count {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    border-radius: 999px !important;
    padding: 5px 10px !important;
    font-size: .72rem !important;
    font-weight: 850 !important;
    background: #f1f5f9 !important;
    color: #334155 !important;
    border: 1px solid #e2e8f0 !important;
}

.gf-category-row-actions {
    display: inline-flex !important;
    gap: 6px !important;
    justify-content: flex-end !important;
    flex-wrap: wrap !important;
}

.gf-category-foot {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    gap: 12px !important;
    padding-top: 12px !important;
    color: #64748b !important;
    font-size: .78rem !important;
    font-weight: 700 !important;
}

.gf-category-empty {
    text-align: center !important;
    color: #64748b !important;
    padding: 40px 16px !important;
    border: 1px dashed #cbd5e1 !important;
    border-radius: 18px !important;
    background: #f8fafc !important;
}

.gf-category-empty-title {
    color: #0f172a !important;
    font-weight: 900 !important;
    margin-bottom: 4px !important;
}

.gf-category-page .modal-content {
    border-radius: 22px !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 24px 70px rgba(15, 23, 42, .2) !important;
}

.gf-category-page .modal-header,
.gf-category-page .modal-footer {
    border-color: #e2e8f0 !important;
}

.gf-category-page .pagination {
    margin: 0 !important;
    gap: 4px !important;
}

.gf-category-page .pagination .page-link {
    border-radius: 11px !important;
    border-color: #e2e8f0 !important;
    color: #475569 !important;
    font-size: .78rem !important;
    font-weight: 700 !important;
}

.gf-category-page .pagination .active .page-link,
.gf-category-page .pagination .page-item.active .page-link {
    color: #ffffff !important;
    background: #0f172a !important;
    border-color: #0f172a !important;
}

@media (max-width: 992px) {
    .gf-category-head {
        flex-direction: column !important;
    }

    .gf-category-actions {
        justify-content: flex-start !important;
    }

    .gf-category-kpi-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    }

    .gf-category-filter {
        grid-template-columns: 1fr 1fr !important;
    }
}

@media (max-width: 768px) {
    .gf-category-page {
        padding: 12px 10px 24px !important;
    }

    .gf-category-head {
        padding: 15px !important;
        border-radius: 20px !important;
    }

    .gf-category-kpi-grid,
    .gf-category-filter {
        grid-template-columns: 1fr !important;
    }

    .gf-category-actions,
    .gf-category-actions .btn,
    .gf-category-filter .btn {
        width: 100% !important;
    }

    .gf-category-foot {
        flex-direction: column !important;
        align-items: flex-start !important;
    }
}

</style>
@endpush

@section('content')
    <x-gf.page
        class="gf-category-page"
        eyebrow="Master Data"
        title="Kategori Item"
        description="Pisahkan kategori produk, bahan baku, pendukung, accessories, dan packaging agar Master Item tidak bercampur.">

        <x-slot:actions>
            <div class="gf-category-actions">
                <a href="{{ route('master.items.index') }}" class="btn btn-outline-secondary btn-sm">
                    Master Item
                </a>
                <button type="button" class="btn btn-primary btn-sm text-white" data-bs-toggle="modal"
                    data-bs-target="#categoryModal" id="btnAddCategory">
                    + Tambah Kategori
                </button>
            </div>
        </x-slot:actions>

        {{-- FLASH --}}
        @if (session('success'))
            <div class="alert alert-success py-2 px-3 mb-0" style="font-size:.82rem;">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger py-2 px-3 mb-0" style="font-size:.82rem;">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger py-2 px-3 mb-0" style="font-size:.82rem;">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="gf-category-kpi-grid">
            <div class="gf-category-kpi-card">
                <div class="gf-category-kpi-label">Total Kategori</div>
                <div class="gf-category-kpi-value">{{ $fmt($totalCategories) }}</div>
                <div class="gf-category-kpi-note">semua kelompok</div>
            </div>
            <div class="gf-category-kpi-card">
                <div class="gf-category-kpi-label">Aktif</div>
                <div class="gf-category-kpi-value">{{ $fmt($activeCount) }}</div>
                <div class="gf-category-kpi-note">bisa dipilih di item</div>
            </div>
            <div class="gf-category-kpi-card">
                <div class="gf-category-kpi-label">Dipakai Item</div>
                <div class="gf-category-kpi-value">{{ $fmt($usedCount) }}</div>
                <div class="gf-category-kpi-note">kategori punya isi</div>
            </div>
            <div class="gf-category-kpi-card">
                <div class="gf-category-kpi-label">Kosong</div>
                <div class="gf-category-kpi-value">{{ $fmt($emptyCount) }}</div>
                <div class="gf-category-kpi-note">aman ditinjau/hapus</div>
            </div>
        </div>

        <x-gf.panel
            :title="$isItemMode ? 'Daftar Item ' . $activeKindLabel : 'Daftar Kategori'"
            :subtitle="$isItemMode ? 'Tab kelompok menampilkan semua item dalam kelompok tersebut.' : 'Gunakan tab kelompok untuk melihat isi item per kelompok.'"
            :flush="true">
            <div class="gf-panel-body">
                <form method="GET" action="{{ route('master.item_categories.index') }}" class="gf-category-filter">
                    <input type="hidden" name="kind" value="{{ $activeKind }}">

                    <div>
                        <label class="form-label">Cari</label>
                        <input type="search" name="q" class="form-control"
                            placeholder="{{ $isItemMode ? 'Kode / nama item' : 'Kode / nama kategori' }}"
                            value="{{ request('q') }}" autocomplete="off" autofocus>
                    </div>

                    <div>
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="active" @selected(request('status') === 'active')>Aktif</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                        </select>
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary w-100">Filter</button>
                        @if ($hasFilters)
                            <a href="{{ route('master.item_categories.index') }}"
                                class="btn btn-outline-light border">Reset</a>
                        @endif
                    </div>
                </form>

                <div class="gf-marketplace-tabs gf-category-tabs" aria-label="Filter kelompok kategori">
                    <a href="{{ $tabUrl(null) }}"
                        class="gf-marketplace-tab text-decoration-none {{ $activeKind === '' ? 'is-active' : '' }}">
                        Kategori <span class="ms-1">{{ $fmt($totalCategories) }}</span>
                    </a>
                    @foreach ($kindLabels as $key => $label)
                        <a href="{{ $tabUrl($key) }}"
                            class="gf-marketplace-tab text-decoration-none {{ $activeKind === $key ? 'is-active' : '' }}">
                            {{ $label }} <span class="ms-1">{{ $fmt($kindItemCounts[$key] ?? 0) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            @if ($isItemMode)
                @if ($kindItems->isEmpty())
                    <div class="gf-category-empty">
                        <div class="gf-category-empty-title">Tidak ada item {{ strtolower($activeKindLabel) }}.</div>
                        <div>Ubah filter atau cek kategori item di Master Item.</div>
                    </div>
                @else
                    <div class="gf-table-scroll">
                        <table class="table table-hover align-middle mb-0 gf-clean-table gf-sticky-table">
                            <thead>
                                <tr>
                                    <th style="width: 68px;" class="text-center">No.</th>
                                    <th style="width: 110px;">Kode Item</th>
                                    <th>Nama Item</th>
                                    <th style="width: 190px;">Kategori</th>
                                    <th style="width: 110px;">Tipe</th>
                                    <th style="width: 150px;">Produksi</th>
                                    <th style="width: 90px;">Satuan</th>
                                    <th style="width: 92px;" class="text-center">Barcode</th>
                                    <th style="width: 130px;" class="text-end">HPP</th>
                                    <th style="width: 100px;" class="text-center">Status</th>
                                    <th style="width: 90px;" class="text-end">Aksi</th>
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
                                        <td><span class="gf-category-code">{{ $item->code }}</span></td>
                                        <td>
                                            <div class="gf-category-name">{{ $item->name }}</div>
                                            <div class="gf-category-sub">{{ $item->active ? 'Aktif di master item' : 'Nonaktif di master item' }}</div>
                                        </td>
                                        <td>
                                            @if ($item->category)
                                                <div class="gf-category-name">{{ $item->category->code }}</div>
                                                <div class="gf-category-sub">{{ $item->category->name }}</div>
                                            @else
                                                <span class="text-muted small">Tanpa kategori</span>
                                            @endif
                                        </td>
                                        <td><span class="gf-category-type">{{ $typeLabels[$item->type] ?? $item->type }}</span></td>
                                        <td>
                                            @if (in_array($item->type, ['finished_good', 'wip'], true))
                                                <span class="gf-category-type">{{ $item->production_source_label }}</span>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                        <td><span class="gf-category-type">{{ $item->unit }}</span></td>
                                        <td class="text-center">
                                            <span class="gf-category-count">{{ $fmt($item->barcodes_count) }}</span>
                                        </td>
                                        <td class="text-end">
                                            @if ($hpp > 0)
                                                <strong>Rp {{ number_format($hpp, 0, ',', '.') }}</strong>
                                            @else
                                                <span class="text-muted small">Belum di-set</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($item->active)
                                                <span class="gf-badge gf-badge-green">Aktif</span>
                                            @else
                                                <span class="gf-badge gf-badge-red">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('master.items.edit', $item) }}" class="btn btn-outline-primary btn-sm rounded-pill">
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
                <div class="gf-category-empty">
                    <div class="gf-category-empty-title">Tidak ada kategori yang cocok.</div>
                    <div>Ubah filter atau tambah kategori baru untuk kelompok ini.</div>
                </div>
            @else
                <div class="gf-table-scroll">
                    <table class="table table-hover align-middle mb-0 gf-clean-table gf-sticky-table">
                        <thead>
                            <tr>
                                <th style="width: 68px;" class="text-center">No.</th>
                                <th style="width: 90px;">Kode</th>
                                <th>Nama Kategori</th>
                                <th style="width: 210px;">Kelompok</th>
                                <th style="width: 120px;" class="text-center">Item</th>
                                <th style="width: 110px;" class="text-center">Status</th>
                                <th style="width: 160px;" class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($categories as $cat)
                                @php
                                    $tone = $kindTone[$cat->kind] ?? 'muted';
                                @endphp
                                <tr>
                                    <td class="text-center text-muted">{{ $categories->firstItem() + $loop->index }}</td>
                                    <td><span class="gf-category-code">{{ $cat->code }}</span></td>
                                    <td>
                                        <div class="gf-category-name">{{ $cat->name }}</div>
                                        <div class="gf-category-sub">
                                            {{ $cat->items_count > 0 ? 'Dipakai di master item' : 'Belum dipakai item' }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="gf-category-kind gf-category-kind-{{ $tone }}">
                                            <span class="gf-category-kind-dot"></span>
                                            {{ $cat->kind_label }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="gf-category-count">
                                            {{ $fmt($cat->items_count) }} <small>item</small>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if ($cat->active)
                                            <span class="gf-badge gf-badge-green">Aktif</span>
                                        @else
                                            <span class="gf-badge gf-badge-red">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="gf-category-row-actions">
                                            <button type="button" class="btn btn-outline-primary btn-sm btn-edit-category"
                                                data-id="{{ $cat->id }}" data-code="{{ $cat->code }}"
                                                data-name="{{ $cat->name }}" data-kind="{{ $cat->kind }}"
                                                data-active="{{ $cat->active ? 1 : 0 }}"
                                                data-bs-toggle="modal" data-bs-target="#categoryModal">
                                                Edit
                                            </button>
                                            @if ($cat->items_count == 0)
                                                <form method="POST"
                                                    action="{{ route('master.item_categories.destroy', $cat) }}"
                                                    onsubmit="return confirm('Hapus kategori &quot;{{ $cat->name }}&quot;?');"
                                                    class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
                                                </form>
                                            @else
                                                <button type="button" class="btn btn-outline-secondary btn-sm" disabled
                                                    title="Masih dipakai {{ $cat->items_count }} item">
                                                    Hapus
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="gf-category-foot">
                <div>
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
        </x-gf.panel>
    </x-gf.page>

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
