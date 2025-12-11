{{-- resources/views/master/items/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Master Item')

@push('head')
    <style>
        .page-wrap {
            max-width: 1000px;
            margin-inline: auto;
            padding: .9rem .8rem 3.2rem;
        }

        body[data-theme="light"] .page-wrap {
            background:
                radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.10) 0,
                    rgba(45, 212, 191, 0.08) 26%,
                    #f9fafb 60%);
        }

        body[data-theme="dark"] .page-wrap {
            background:
                radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.16) 0,
                    rgba(15, 23, 42, 1) 55%);
        }

        .card-main {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.28);
            box-shadow:
                0 10px 24px rgba(15, 23, 42, 0.06),
                0 0 0 1px rgba(15, 23, 42, 0.03);
        }

        .page-header-title {
            font-size: 1.06rem;
            font-weight: 600;
            letter-spacing: -.02em;
        }

        .page-header-subtitle {
            font-size: .8rem;
        }

        .btn-add-item {
            border-radius: 999px;
            padding-inline: 1rem;
            font-size: .8rem;
        }

        .card-filters {
            border-radius: 12px;
        }

        .card-filters .form-label {
            font-size: .74rem;
        }

        @media (max-width: 768px) {
            .page-wrap {
                padding-inline: .6rem;
                padding-bottom: 2.6rem;
            }

            .page-header-title {
                font-size: 1rem;
            }

            .page-header-subtitle {
                font-size: .78rem;
            }
        }

        /* ============ TABLE STYLE (mirip QC index) ============ */

        .table-gfid {
            font-size: .8rem;
            margin-bottom: 0;
        }

        .table-gfid thead th {
            background: color-mix(in srgb, var(--card) 78%, var(--bg) 22%);
            position: sticky;
            top: 0;
            z-index: 1;
            border-bottom: 1px solid rgba(148, 163, 184, 0.45) !important;
            padding-top: .5rem;
            padding-bottom: .5rem;
            font-weight: 600;
            font-size: .75rem;
            letter-spacing: .03em;
            text-transform: uppercase;
        }

        .table-gfid tbody tr {
            border-color: rgba(148, 163, 184, 0.25);
        }

        .table-gfid tbody tr:nth-child(even) {
            background: color-mix(in srgb, var(--card) 96%, rgba(148, 163, 184, 0.22) 4%);
        }

        .table-gfid tbody tr:hover {
            background: color-mix(in srgb, var(--card) 92%, var(--accent-soft) 8%);
        }

        .table-gfid td {
            padding-top: .42rem;
            padding-bottom: .42rem;
            vertical-align: middle;
        }

        .badge-soft {
            border-radius: 999px;
            font-weight: 500;
            font-size: .7rem;
            border: 1px solid rgba(148, 163, 184, 0.5);
            background: color-mix(in srgb, var(--card) 85%, rgba(148, 163, 184, 0.25) 15%);
        }

        .badge-pill-soft {
            border-radius: 999px;
            font-size: .7rem;
        }

        .status-badge {
            min-width: 70px;
        }

        /* FOOTER / PAGINATION ROW */
        .table-footer {
            border-top: 1px solid rgba(148, 163, 184, 0.35);
            background: color-mix(in srgb, var(--card) 94%, var(--bg) 6%);
            border-radius: 0 0 14px 14px;
        }

        .table-footer-text {
            font-size: .74rem;
            color: var(--muted);
        }

        .pagination {
            margin-bottom: 0;
        }

        .pagination .page-link {
            font-size: .76rem;
        }

        @media (max-width: 768px) {
            .table-footer {
                flex-direction: column;
                align-items: stretch !important;
                gap: .35rem;
            }

            .table-footer .pagination-wrapper {
                justify-content: center !important;
            }

            .table-footer-text {
                text-align: center;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="page-header-title mb-1">Master Item</h5>
                <div class="page-header-subtitle text-muted">
                    Daftar item + jumlah barcode terdaftar + status HPP aktif.
                </div>
            </div>
            <div class="ms-3 text-white">
                <a href="{{ route('master.items.create') }}" class="btn btn-primary btn-sm btn-add-item ">
                    <span class="me-1">＋</span>
                    <span>Tambah Item</span>
                </a>
            </div>
        </div>

        {{-- FILTERS --}}
        <div class="card card-main card-filters mb-3">
            <div class="card-body py-2">
                <form method="GET" action="{{ route('master.items.index') }}" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label mb-1">Cari</label>
                        <input type="text" name="q" class="form-control form-control-sm"
                            placeholder="Kode / nama item" value="{{ request('q') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label mb-1">Tipe</label>
                        <select name="type" class="form-select form-select-sm">
                            <option value="">- Semua -</option>
                            @php
                                $types = [
                                    'material' => 'Material',
                                    'wip' => 'WIP',
                                    'finished_good' => 'Finished Good',
                                ];
                            @endphp
                            @foreach ($types as $key => $label)
                                <option value="{{ $key }}" @selected(request('type') === $key)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Kategori (opsional) --}}
                    {{--
                    <div class="col-md-3">
                        <label class="form-label mb-1">Kategori</label>
                        <select name="item_category_id" class="form-select form-select-sm">
                            <option value="">- Semua -</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(request('item_category_id') == $cat->id)>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    --}}

                    <div class="col-md-2 d-flex gap-2">
                        <button class="btn btn-outline-secondary btn-sm w-100">
                            Filter
                        </button>
                        @if (request()->hasAny(['q', 'type', 'item_category_id']))
                            <a href="{{ route('master.items.index') }}"
                                class="btn btn-outline-light border btn-sm d-none d-md-inline-flex">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="card card-main">
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 60vh;">
                    <table class="table table-sm align-middle table-gfid">
                        <thead>
                            <tr class="text-muted">
                                <th style="width: 5%" class="text-center">No.</th>
                                <th style="width: 9%">Kode</th>
                                <th>Nama</th>
                                <th style="width: 8%">Satuan</th>
                                <th style="width: 13%">Tipe</th>
                                <th style="width: 10%" class="text-center">Barcode</th>
                                <th style="width: 14%" class="text-end">HPP Aktif</th>
                                <th style="width: 10%" class="text-center">Status</th>
                                <th style="width: 13%" class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                                @php
                                    $hpp = $item->active_unit_cost ?? 0;
                                    $typeLabels = [
                                        'material' => 'Material',
                                        'wip' => 'WIP',
                                        'finished_good' => 'Finished Good',
                                    ];
                                @endphp
                                <tr>
                                    {{-- PENOMORAN GLOBAL (ikut paginasi) --}}
                                    <td class="text-center text-muted">
                                        {{ $items->firstItem() + $loop->index }}
                                    </td>

                                    <td class="fw-semibold">
                                        {{ $item->code }}
                                    </td>
                                    <td>
                                        <div class="fw-semibold mb-0">
                                            {{ $item->name }}
                                        </div>
                                        @if ($item->sku ?? false)
                                            <div class="text-muted small">
                                                SKU: {{ $item->sku }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-soft px-2">
                                            {{ $item->unit }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-soft">
                                            {{ $typeLabels[$item->type] ?? $item->type }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if ($item->barcodes_count > 0)
                                            <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis px-3">
                                                {{ $item->barcodes_count }}
                                            </span>
                                        @else
                                            <span class="badge rounded-pill bg-secondary-subtle text-muted px-3">
                                                0
                                            </span>
                                        @endif
                                    </td>

                                    {{-- HPP Aktif --}}
                                    <td class="text-end">
                                        @if ($hpp > 0)
                                            <div class="d-flex flex-column align-items-end">
                                                <span class="fw-semibold">
                                                    Rp {{ number_format($hpp, 0, ',', '.') }}
                                                </span>
                                                <span
                                                    class="badge badge-pill-soft bg-success-subtle text-success-emphasis mt-1">
                                                    HPP OK
                                                </span>
                                            </div>
                                        @else
                                            <div class="d-flex flex-column align-items-end">
                                                <span class="text-muted small">
                                                    Belum di-set
                                                </span>
                                                <span
                                                    class="badge badge-pill-soft bg-warning-subtle text-warning-emphasis mt-1">
                                                    Perlu HPP
                                                </span>
                                            </div>
                                        @endif
                                    </td>

                                    <td class="text-center">
                                        @if ($item->active)
                                            <span class="badge badge-pill-soft bg-success-subtle text-success status-badge">
                                                Aktif
                                            </span>
                                        @else
                                            <span class="badge badge-pill-soft bg-danger-subtle text-danger status-badge">
                                                Nonaktif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('master.items.hpp_temp.edit', $item) }}"
                                                class="btn btn-outline-success">
                                                HPP
                                            </a>
                                            <a href="{{ route('master.items.edit', $item) }}"
                                                class="btn btn-outline-primary">
                                                Edit
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        Belum ada item.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- FOOTER + PAGINATION --}}
                <div class="px-3 py-2 d-flex justify-content-between align-items-center table-footer">
                    <div class="table-footer-text">
                        @if ($items->total() > 0)
                            Menampilkan
                            <strong>{{ $items->firstItem() }}–{{ $items->lastItem() }}</strong>
                            dari
                            <strong>{{ $items->total() }}</strong>
                            item
                        @else
                            Tidak ada data item.
                        @endif
                    </div>
                    <div class="pagination-wrapper d-flex justify-content-end">
                        {{ $items->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
