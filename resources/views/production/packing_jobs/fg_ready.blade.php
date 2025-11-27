@extends('layouts.app')

@section('title', 'Produksi • FG Ready to Pack')

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding-block: 0.75rem 1.5rem;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
            box-shadow:
                0 14px 30px rgba(15, 23, 42, 0.06),
                0 1px 0 rgba(15, 23, 42, 0.04);
        }

        .help {
            color: var(--muted);
            font-size: .84rem;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .page-title-wrap {
            display: flex;
            align-items: center;
            gap: .9rem;
        }

        .page-icon {
            width: 38px;
            height: 38px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: color-mix(in srgb, var(--primary, #0d6efd) 10%, var(--card) 90%);
            border: 1px solid color-mix(in srgb, var(--primary, #0d6efd) 30%, var(--line) 70%);
            font-size: 1.1rem;
        }

        .page-title {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .page-subtitle {
            font-size: .85rem;
            color: var(--muted);
        }

        .pill-info {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .1rem .55rem;
            border-radius: 999px;
            font-size: .78rem;
            background: color-mix(in srgb, var(--card) 70%, var(--line) 30%);
        }

        .table-wrap {
            overflow-x: auto;
        }

        .table-fg {
            margin-bottom: 0;
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .65rem;
            }

            .card {
                border-radius: 14px;
            }

            .page-title {
                font-size: 1rem;
            }

            .page-subtitle {
                font-size: .8rem;
            }

            .table-wrap {
                font-size: .86rem;
            }

            /* Table → card-like list */
            .table-fg thead {
                display: none;
            }

            .table-fg tbody tr {
                display: block;
                margin-bottom: .75rem;
                border-radius: 14px;
                border: 1px solid var(--line);
                background: var(--card);
                box-shadow:
                    0 10px 24px rgba(15, 23, 42, 0.04),
                    0 0 0 1px rgba(15, 23, 42, 0.02);
            }

            .table-fg tbody tr td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: .28rem .75rem;
                border-top: none !important;
            }

            .table-fg tbody tr td:first-child {
                padding-top: .5rem;
            }

            .table-fg tbody tr td:last-child {
                padding-bottom: .45rem;
            }

            .table-fg tbody tr td[data-label]:before {
                content: attr(data-label);
                font-size: .78rem;
                color: var(--muted);
                margin-right: .75rem;
                flex: 0 0 38%;
                max-width: 42%;
            }

            .td-actions {
                justify-content: flex-end;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="card p-3 mb-3">
            <div class="page-header">
                <div class="page-title-wrap">
                    <div class="page-icon">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div>
                        <h1 class="page-title">Finished Goods Ready to Pack</h1>
                        <div class="page-subtitle">
                            Daftar stok barang jadi (FG) yang siap dibuat Packing Job.
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('production.packing_jobs.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>

        {{-- INFO KALAU GUDANG FG BELUM ADA --}}
        @if (!$fgWarehouse)
            <div class="alert alert-warning border-0 shadow-sm mb-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div>
                        <strong>Gudang FG belum dikonfigurasi.</strong><br>
                        Tambahkan gudang dengan kode <code>FG</code> di modul gudang sebelum membuat Packing Job.
                    </div>
                </div>
            </div>
        @endif

        {{-- PENCARIAN + LIST --}}
        <div class="card p-3 mb-3">
            {{-- FILTER / SEARCH --}}
            <form method="get" class="row g-2 align-items-end mb-3">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Cari barang</label>
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm"
                        placeholder="Kode / nama / warna">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary align-self-end">
                        <i class="bi bi-search me-1"></i> Cari
                    </button>
                    <a href="{{ route('production.packing.fg_ready') }}"
                        class="btn btn-sm btn-outline-secondary align-self-end">
                        Reset
                    </a>
                </div>
                @if ($fgWarehouse)
                    <div class="col-md-4 text-md-end">
                        <div class="pill-info mt-2 mt-md-0">
                            <i class="bi bi-buildings"></i>
                            Gudang FG: <strong>{{ $fgWarehouse->name ?? $fgWarehouse->code }}</strong>
                        </div>
                    </div>
                @endif
            </form>

            {{-- FORM PILIH ITEM → BUAT PACKING JOB --}}
            <form method="get" action="{{ route('production.packing_jobs.create') }}">
                <div class="table-wrap">
                    <table class="table table-sm align-middle table-fg">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 1%;">
                                    <input type="checkbox" id="check-all">
                                </th>
                                <th style="width: 20%;">Item</th>
                                <th>Deskripsi</th>
                                <th class="text-end" style="width: 12%;">Qty FG</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($stocks as $stock)
                                @php
                                    $item = $stock->item;
                                @endphp
                                <tr>
                                    {{-- CHECKBOX --}}
                                    <td data-label="Pilih">
                                        <input type="checkbox" name="item_ids[]" value="{{ $stock->item_id }}"
                                            class="form-check-input select-item">
                                    </td>

                                    {{-- ITEM --}}
                                    <td data-label="Item">
                                        @if ($item)
                                            <div class="small fw-semibold">
                                                {{ $item->code ?? '' }}
                                            </div>
                                            <div class="small text-muted">
                                                {{ $item->name ?? '' }}
                                            </div>
                                        @else
                                            <span class="text-muted small">Item tidak ditemukan</span>
                                        @endif
                                    </td>

                                    {{-- DESKRIPSI (warna, dsb) --}}
                                    <td data-label="Deskripsi">
                                        @if ($item)
                                            <div class="small">
                                                {{ $item->color ?? '' }}
                                            </div>
                                        @endif
                                    </td>

                                    {{-- QTY FG --}}
                                    <td data-label="Qty FG" class="text-end">
                                        <span class="mono">{{ number_format($stock->qty) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        @if ($fgWarehouse)
                                            Belum ada stok FG yang siap packing.
                                        @else
                                            Sistem belum mengenali gudang FG, sehingga stok FG tidak bisa ditampilkan.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- PAGINATION: aman untuk paginator & collection --}}
                @if ($stocks instanceof \Illuminate\Contracts\Pagination\Paginator && $stocks->hasPages())
                    <div class="mt-2">
                        {{ $stocks->links() }}
                    </div>
                @endif

                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                    <div class="help">
                        Pilih satu atau beberapa item, lalu klik
                        <strong>Buat Packing Job</strong>.
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary" @if (!$fgWarehouse) disabled @endif>
                        <i class="bi bi-box-arrow-in-right me-1"></i>
                        Buat Packing Job
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const checkAll = document.getElementById('check-all');
            const checkboxes = document.querySelectorAll('.select-item');

            if (checkAll) {
                checkAll.addEventListener('change', function() {
                    checkboxes.forEach(cb => {
                        cb.checked = checkAll.checked;
                    });
                });
            }
        })();
    </script>
@endpush
