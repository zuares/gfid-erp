@extends('layouts.app')

@section('title', 'Edit Goods Receipt ' . $receipt->code)

@section('content')
    <div class="container py-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h4 mb-0">Edit Goods Receipt</h1>
                <div class="small text-muted">
                    {{ $receipt->code }} &middot; Tanggal: {{ $receipt->date?->format('Y-m-d') }}
                </div>
            </div>
            <a href="{{ route('purchasing.purchase_receipts.show', $receipt->id) }}" class="btn btn-outline-secondary btn-sm">
                &larr; Kembali
            </a>
        </div>

        @if (session('error'))
            <div class="alert alert-danger py-2">
                {{ session('error') }}
            </div>
        @endif

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

        @if ($receipt->status !== 'draft')
            <div class="alert alert-warning">
                Hanya GRN dengan status <strong>draft</strong> yang boleh diedit.
            </div>
        @endif

        <form action="{{ route('purchasing.purchase_receipts.update', $receipt->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="date" class="form-control"
                                value="{{ old('date', $receipt->date?->toDateString()) }}" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Supplier</label>
                            <select name="supplier_id" class="form-select" required>
                                <option value="">-- Pilih Supplier --</option>
                                @foreach ($suppliers as $sup)
                                    <option value="{{ $sup->id }}" @selected(old('supplier_id', $receipt->supplier_id) == $sup->id)>
                                        {{ $sup->code }} - {{ $sup->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Gudang</label>
                            <select name="warehouse_id" class="form-select" required>
                                <option value="">-- Pilih Gudang --</option>
                                @foreach ($warehouses as $wh)
                                    <option value="{{ $wh->id }}" @selected(old('warehouse_id', $receipt->warehouse_id) == $wh->id)>
                                        {{ $wh->code }} - {{ $wh->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Diskon (nominal)</label>
                            <input type="text" name="discount" class="form-control"
                                value="{{ old('discount', $receipt->discount) }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">PPN (%)</label>
                            <input type="text" name="tax_percent" class="form-control"
                                value="{{ old('tax_percent', $receipt->tax_percent) }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Ongkir</label>
                            <input type="text" name="shipping_cost" class="form-control"
                                value="{{ old('shipping_cost', $receipt->shipping_cost) }}">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" rows="2" class="form-control">{{ old('notes', $receipt->notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- DETAIL BARANG --}}
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Detail Barang Diterima</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-line">
                        + Tambah Baris
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 30%">Item</th>
                                    <th style="width: 12%">Qty Diterima</th>
                                    <th style="width: 12%">Qty Reject</th>
                                    <th style="width: 15%">Harga/Unit</th>
                                    <th style="width: 15%">Unit</th>
                                    <th style="width: 20%">Catatan</th>
                                    <th style="width: 5%"></th>
                                </tr>
                            </thead>
                            <tbody id="grn-lines-body">
                                @php
                                    $oldItemIds = old('item_id', []);
                                    $oldQtyReceived = old('qty_received', []);
                                    $oldQtyReject = old('qty_reject', []);
                                    $oldUnitPrice = old('unit_price', []);
                                    $oldUnits = old('unit', []);
                                    $oldLineNotes = old('line_notes', []);

                                    $useOld = count($oldItemIds) > 0;
                                @endphp

                                @if ($useOld)
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
                                                <input type="text" name="qty_received[]"
                                                    class="form-control form-control-sm text-end"
                                                    value="{{ $oldQtyReceived[$i] ?? 0 }}">
                                            </td>
                                            <td>
                                                <input type="text" name="qty_reject[]"
                                                    class="form-control form-control-sm text-end"
                                                    value="{{ $oldQtyReject[$i] ?? 0 }}">
                                            </td>
                                            <td>
                                                <input type="text" name="unit_price[]"
                                                    class="form-control form-control-sm text-end"
                                                    value="{{ $oldUnitPrice[$i] ?? 0 }}">
                                            </td>
                                            <td>
                                                <input type="text" name="unit[]" class="form-control form-control-sm"
                                                    value="{{ $oldUnits[$i] ?? '' }}">
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
                                    @forelse ($receipt->lines as $line)
                                        <tr>
                                            <td>
                                                <select name="item_id[]" class="form-select form-select-sm">
                                                    <option value="">-- Pilih Item --</option>
                                                    @foreach ($items as $item)
                                                        <option value="{{ $item->id }}" @selected($line->item_id == $item->id)>
                                                            {{ $item->code }} - {{ $item->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="qty_received[]"
                                                    class="form-control form-control-sm text-end"
                                                    value="{{ $line->qty_received }}">
                                            </td>
                                            <td>
                                                <input type="text" name="qty_reject[]"
                                                    class="form-control form-control-sm text-end"
                                                    value="{{ $line->qty_reject }}">
                                            </td>
                                            <td>
                                                <input type="text" name="unit_price[]"
                                                    class="form-control form-control-sm text-end"
                                                    value="{{ $line->unit_price }}">
                                            </td>
                                            <td>
                                                <input type="text" name="unit[]" class="form-control form-control-sm"
                                                    value="{{ $line->unit }}">
                                            </td>
                                            <td>
                                                <input type="text" name="line_notes[]"
                                                    class="form-control form-control-sm" value="{{ $line->notes }}">
                                            </td>
                                            <td class="text-center">
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-danger btn-remove-line">
                                                    &times;
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
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
                                                <input type="text" name="qty_received[]"
                                                    class="form-control form-control-sm text-end" value="0">
                                            </td>
                                            <td>
                                                <input type="text" name="qty_reject[]"
                                                    class="form-control form-control-sm text-end" value="0">
                                            </td>
                                            <td>
                                                <input type="text" name="unit_price[]"
                                                    class="form-control form-control-sm text-end" value="0">
                                            </td>
                                            <td>
                                                <input type="text" name="unit[]" class="form-control form-control-sm"
                                                    placeholder="kg/pcs/m">
                                            </td>
                                            <td>
                                                <input type="text" name="line_notes[]"
                                                    class="form-control form-control-sm">
                                            </td>
                                            <td class="text-center">
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-danger btn-remove-line">
                                                    &times;
                                                </button>
                                            </td>
                                        </tr>
                                    @endforelse
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('purchasing.purchase_receipts.show', $receipt->id) }}"
                    class="btn btn-outline-secondary">
                    Batal
                </a>
                <button type="submit" class="btn btn-primary">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const body = document.getElementById('grn-lines-body');
                const btnAdd = document.getElementById('btn-add-line');

                btnAdd?.addEventListener('click', function() {
                    const row = body.querySelector('tr');
                    const clone = row.cloneNode(true);

                    clone.querySelectorAll('input').forEach(input => {
                        if (input.name === 'qty_received[]' || input.name === 'qty_reject[]' || input
                            .name === 'unit_price[]') {
                            input.value = '0';
                        } else {
                            input.value = '';
                        }
                    });
                    clone.querySelectorAll('select').forEach(select => {
                        select.selectedIndex = 0;
                    });

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
