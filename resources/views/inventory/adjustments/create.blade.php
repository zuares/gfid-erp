@extends('layouts.app')

@section('title', 'Inventory Adjustment Baru')

@push('head')
    <style>
        .page-wrap {
            max-width: 900px;
            margin-inline: auto;
            padding: .75rem .75rem 4rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.16) 0,
                    rgba(45, 212, 191, 0.10) 28%,
                    #f9fafc 60%);
        }

        .card-main {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.30);
            box-shadow:
                0 10px 26px rgba(15, 23, 42, 0.06),
                0 0 0 1px rgba(15, 23, 42, 0.03);
        }

        .table thead th {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #64748b;
            border-bottom-color: rgba(148, 163, 184, .5);
        }

        .table tbody td {
            vertical-align: middle;
            font-size: .85rem;
        }

        .btn-row-delete {
            padding: .15rem .35rem;
            border-radius: 999px;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        <div class="mb-3">
            <a href="{{ route('inventory.inventory_adjustments.index') }}" class="btn btn-sm btn-link text-decoration-none">
                ← Kembali ke daftar
            </a>
        </div>

        <form action="{{ route('inventory.inventory_adjustments.store') }}" method="POST">
            @csrf

            <div class="card card-main mb-3">
                <div class="card-header border-0 pb-1">
                    <h1 class="h5 mb-0">Inventory Adjustment Baru</h1>
                </div>
                <div class="card-body pt-2">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label form-label-sm">Tanggal</label>
                            <input type="date" name="date"
                                class="form-control form-control-sm @error('date') is-invalid @enderror"
                                value="{{ old('date', now()->toDateString()) }}">
                            @error('date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-5">
                            <label class="form-label form-label-sm">Gudang</label>
                            <select name="warehouse_id"
                                class="form-select form-select-sm @error('warehouse_id') is-invalid @enderror">
                                <option value="">Pilih Gudang</option>
                                @foreach ($warehouses as $wh)
                                    <option value="{{ $wh->id }}" @selected(old('warehouse_id') == $wh->id)>
                                        {{ $wh->code }} - {{ $wh->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('warehouse_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label form-label-sm">Catatan</label>
                            <input type="text" name="notes" class="form-control form-control-sm"
                                value="{{ old('notes') }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-main">
                <div class="card-header border-0 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Detail Penyesuaian</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-line">
                        + Tambah Baris
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0" id="lines-table">
                            <thead>
                                <tr>
                                    <th style="width: 32%;">Item</th>
                                    <th class="text-end" style="width: 15%;">Qty Selisih</th>
                                    <th style="width: 33%;">Reason</th>
                                    <th class="text-end" style="width: 10%;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $oldLines = old('lines', [
                                        ['item_id' => null, 'qty_diff' => null, 'reason' => null],
                                    ]);
                                @endphp

                                @foreach ($oldLines as $i => $line)
                                    <tr>
                                        <td>
                                            <select name="lines[{{ $i }}][item_id]"
                                                class="form-select form-select-sm">
                                                <option value="">Pilih item…</option>
                                                @foreach ($items as $item)
                                                    <option value="{{ $item->id }}" @selected($line['item_id'] == $item->id)>
                                                        {{ $item->code }} — {{ $item->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="text-end">
                                            <input type="number" step="0.001"
                                                name="lines[{{ $i }}][qty_diff]"
                                                class="form-control form-control-sm text-end"
                                                value="{{ $line['qty_diff'] }}">
                                        </td>
                                        <td>
                                            <input type="text" name="lines[{{ $i }}][reason]"
                                                class="form-control form-control-sm" value="{{ $line['reason'] }}">
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-light border btn-row-delete"
                                                title="Hapus baris">
                                                ×
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @error('lines')
                        <div class="text-danger small px-3 py-1">{{ $message }}</div>
                    @enderror
                    @foreach ($errors->get('lines.*.item_id') as $messages)
                        @foreach ($messages as $message)
                            <div class="text-danger small px-3 py-1">{{ $message }}</div>
                        @endforeach
                    @endforeach
                    @foreach ($errors->get('lines.*.qty_diff') as $messages)
                        @foreach ($messages as $message)
                            <div class="text-danger small px-3 py-1">{{ $message }}</div>
                        @endforeach
                    @endforeach
                </div>

                <div class="card-footer d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Qty Selisih positif = tambah stok, negatif = kurangi stok.
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary">
                        Simpan & Posting
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
                const tableBody = document.querySelector('#lines-table tbody');
                const btnAddLine = document.getElementById('btn-add-line');

                function getNextIndex() {
                    const rows = tableBody.querySelectorAll('tr');
                    return rows.length;
                }

                function addRow() {
                    const index = getNextIndex();

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
