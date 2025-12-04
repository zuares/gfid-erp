@extends('layouts.app')

@section('title', 'Master Item')

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .75rem .75rem 4rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.10) 0,
                    rgba(45, 212, 191, 0.08) 26%,
                    #f9fafb 60%);
        }

        .card-main {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.30);
            box-shadow:
                0 10px 26px rgba(15, 23, 42, 0.06),
                0 0 0 1px rgba(15, 23, 42, 0.03);
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-0">Master Item</h5>
                <div class="text-muted small">
                    Daftar item + jumlah barcode terdaftar.
                </div>
            </div>
            <div>
                <a href="{{ route('items.create') }}" class="btn btn-primary btn-sm">
                    + Tambah Item
                </a>
            </div>
        </div>

        {{-- FILTERS --}}
        <div class="card card-main mb-3">
            <div class="card-body py-2">
                <form method="GET" action="{{ route('items.index') }}" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small mb-1">Cari</label>
                        <input type="text" name="q" class="form-control form-control-sm"
                            placeholder="Kode / nama item" value="{{ request('q') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small mb-1">Tipe</label>
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

                    {{-- Kalau kamu punya categories lempar dari controller,
                     aktifkan block ini dan tambahi compact('categories') di controller --}}
                    {{--
                <div class="col-md-3">
                    <label class="form-label small mb-1">Kategori</label>
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

                    <div class="col-md-2">
                        <button class="btn btn-outline-secondary btn-sm w-100">
                            Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="card card-main">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr class="text-muted">
                                <th style="width: 8%">Kode</th>
                                <th>Nama</th>
                                <th style="width: 8%">Satuan</th>
                                <th style="width: 14%">Tipe</th>
                                <th style="width: 10%" class="text-center">Barcode</th>
                                <th style="width: 10%" class="text-center">Status</th>
                                <th style="width: 10%" class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                                <tr>
                                    <td class="fw-semibold">
                                        {{ $item->code }}
                                    </td>
                                    <td>
                                        {{ $item->name }}
                                    </td>
                                    <td>
                                        {{ $item->unit }}
                                    </td>
                                    <td>
                                        @php
                                            $typeLabels = [
                                                'material' => 'Material',
                                                'wip' => 'WIP',
                                                'finished_good' => 'Finished Good',
                                            ];
                                        @endphp
                                        <span class="badge bg-light text-dark border">
                                            {{ $typeLabels[$item->type] ?? $item->type }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if ($item->barcodes_count > 0)
                                            <span class="badge bg-primary-subtle text-primary"
                                                title="Jumlah barcode terdaftar">
                                                {{ $item->barcodes_count }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-muted">
                                                0
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($item->active)
                                            <span class="badge bg-success-subtle text-success">
                                                Aktif
                                            </span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger">
                                                Nonaktif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('items.edit', $item) }}" class="btn btn-sm btn-outline-primary">
                                            Edit
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        Belum ada item.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-3 py-2">
                    {{ $items->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
