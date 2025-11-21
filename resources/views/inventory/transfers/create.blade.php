{{-- resources/views/inventory/transfers/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Transfer Stok Baru')

@section('content')
    <div class="container py-3">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Transfer Stok Baru</h1>
            <a href="{{ route('inventory.transfers.index') }}" class="btn btn-outline-secondary btn-sm">
                &larr; Kembali
            </a>
        </div>

        {{-- ERROR --}}
        @if ($errors->any())
            <div class="alert alert-danger">
                <div class="fw-semibold mb-1">Terjadi kesalahan:</div>
                <ul class="mb-0">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('inventory.transfers.store') }}" method="POST">
            @csrf

            {{-- HEADER TRANSFER --}}
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="date" class="form-control"
                                value="{{ old('date', now()->toDateString()) }}" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Gudang Asal</label>
                            <select name="from_warehouse_id" class="form-select" required>
                                <option value="">-- Pilih Gudang Asal --</option>
                                @foreach ($warehouses as $wh)
                                    <option value="{{ $wh->id }}" @selected(old('from_warehouse_id') == $wh->id)>
                                        {{ $wh->code }} - {{ $wh->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Gudang Tujuan</label>
                            <select name="to_warehouse_id" class="form-select" required>
                                <option value="">-- Pilih Gudang Tujuan --</option>
                                @foreach ($warehouses as $wh)
                                    <option value="{{ $wh->id }}" @selected(old('to_warehouse_id') == $wh->id)>
                                        {{ $wh->code }} - {{ $wh->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Catatan Header</label>
                            <textarea name="notes" rows="2" class="form-control">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- DETAIL ITEM --}}
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Detail Item yang Dipindahkan</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-line">
                        + Tambah Baris
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40%">Item</th>
                                    <th style="width: 20%">Qty</th>
                                    <th style="width: 35%">Catatan</th>
                                    <th style="width: 5%"></th>
                                </tr>
                            </thead>
                            <tbody id="transfer-lines-body">
                                @php
                                    $oldItemIds = old('item_id', []);
                                    $oldQtys = old('qty', []);
                                    $oldLineNotes = old('line_notes', []);
                                @endphp

                                @if (count($oldItemIds))
                                    @foreach ($oldItemIds as $i => $itemId)
                                        <tr>
                                            <td>
                                                <select name="item_id[]" class="form-select form-select-sm">
                                                    <option value="">-- Pilih Item --</option>
                                                    @foreach ($items as $item)
                                                        <option value="{{ $item->id }}" @selected($itemId == $item->id)>
                                                            {{ $item->code }} - {{ $item->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="qty[]"
                                                    class="form-control form-control-sm text-end"
                                                    value="{{ $oldQtys[$i] ?? 0 }}">
                                            </td>
                                            <td>
                                                <input type="text" name="line_notes[]"
                                                    class="form-control form-control-sm"
                                                    value="{{ $oldLineNotes[$i] ?? '' }}">
                                            </td>
                                            <td class="text-center">
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-danger btn-remove-line">
                                                    &times;
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    {{-- default 1 baris --}}
                                    <tr>
                                        <td>
                                            <select name="item_id[]" class="form-select form-select-sm">
                                                <option value="">-- Pilih Item --</option>
                                                @foreach ($items as $item)
                                                    <option value="{{ $item->id }}">
                                                        {{ $item->code }} - {{ $item->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="qty[]"
                                                class="form-control form-control-sm text-end" value="0">
                                        </td>
                                        <td>
                                            <input type="text" name="line_notes[]" class="form-control form-control-sm">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-line">
                                                &times;
                                            </button>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ACTIONS --}}
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('inventory.transfers.index') }}" class="btn btn-outline-secondary">
                    Batal
                </a>
                <button type="submit" class="btn btn-primary">
                    Simpan & Jalankan Transfer
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const body = document.getElementById('transfer-lines-body');
                const btnAdd = document.getElementById('btn-add-line');

                btnAdd?.addEventListener('click', function() {
                    const row = body.querySelector('tr');
                    const clone = row.cloneNode(true);

                    clone.querySelectorAll('input').forEach(i => i.value = '');
                    clone.querySelectorAll('select').forEach(s => s.selectedIndex = 0);

                    body.appendChild(clone);
                });

                body.addEventListener('click', function(e) {
                    if (e.target.classList.contains('btn-remove-line')) {
                        const rows = body.querySelectorAll('tr');
                        if (rows.length > 1) {
                            e.target.closest('tr').remove();
                        } else {
                            e.target.closest('tr').querySelectorAll('input').forEach(i => i.value = '');
                            e.target.closest('tr').querySelectorAll('select').forEach(s => s.selectedIndex = 0);
                        }
                    }
                });
            });
        </script>
    @endpush

@endsection
