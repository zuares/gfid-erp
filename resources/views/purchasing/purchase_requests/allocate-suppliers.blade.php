@extends('layouts.app')

@section('title', 'Pilih Supplier - ' . $purchase_request->code)

@push('head')
<style>
    .allocation-wrap { max-width: 980px; margin-inline: auto; padding-bottom: 3rem; }
    .allocation-card { background: var(--card); border: 1px solid var(--line); border-radius: 8px; overflow: hidden; }
    .allocation-head { padding: 1rem; border-bottom: 1px solid var(--line); }
    .allocation-table th { color: var(--muted); font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; white-space: nowrap; }
    .item-name { font-weight: 700; }
    .item-code { color: var(--muted); font-size: .8rem; }
    .allocation-footer { padding: .9rem 1rem; border-top: 1px solid var(--line); background: rgba(148, 163, 184, .05); }
    @media (max-width: 767.98px) {
        .allocation-table thead { display: none; }
        .allocation-table, .allocation-table tbody, .allocation-table tr, .allocation-table td { display: block; width: 100%; }
        .allocation-table tr { padding: .8rem 1rem; border-bottom: 1px solid var(--line); }
        .allocation-table td { border: 0; padding: .15rem 0; }
        .allocation-table .number-cell { display: none; }
        .allocation-table .qty-cell { text-align: left !important; font-size: .82rem; color: var(--muted); }
    }
</style>
@endpush

@section('content')
<div class="allocation-wrap">
    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h5 fw-bold mb-1">Pilih Supplier per Item</h1>
            <div class="text-muted small">{{ $purchase_request->code }} · Sistem membuat satu PO draft untuk setiap supplier.</div>
        </div>
        <a href="{{ route('purchasing.purchase_requests.show', $purchase_request) }}" class="btn btn-sm btn-outline-secondary">Batal</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('purchasing.purchase_requests.convert', $purchase_request) }}" id="supplier-allocation-form">
        @csrf
        <div class="allocation-card">
            <div class="allocation-head d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <div>
                    <div class="fw-semibold">{{ $purchase_request->lines->count() }} item akan diproses</div>
                    <div class="small text-muted">Harga belum diisi pada tahap ini dan tetap hanya dapat dikelola owner.</div>
                </div>
                <span class="badge text-bg-light border" id="po-count">0 PO draft</span>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0 allocation-table">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width:50px">No</th>
                            <th>Barang</th>
                            <th class="text-end" style="width:120px">Qty</th>
                            <th style="min-width:260px">Supplier</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($purchase_request->lines as $index => $line)
                            @php
                                $recommendation = $recommendedSuppliers->get($line->item_id);
                                $defaultSupplierId = old(
                                    "suppliers.{$line->id}",
                                    $recommendation?->supplier_id ?? $purchase_request->supplier_id
                                );
                            @endphp
                            <tr>
                                <td class="text-center text-muted number-cell">{{ $index + 1 }}</td>
                                <td>
                                    <div class="item-name">{{ $line->item?->name ?? 'Item tidak ditemukan' }}</div>
                                    <div class="item-code">{{ $line->item?->code ?? '-' }}</div>
                                </td>
                                <td class="text-end qty-cell">
                                    {{ number_format($line->qty, 2, ',', '.') }} {{ $line->item?->unit }}
                                </td>
                                <td>
                                    <select name="suppliers[{{ $line->id }}]" class="form-select form-select-sm supplier-select" required>
                                        <option value="">Pilih supplier</option>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" @selected((string) $defaultSupplierId === (string) $supplier->id)>
                                                {{ $supplier->name }}{{ $supplier->code ? ' · ' . $supplier->code : '' }}{{ (string) $recommendation?->supplier_id === (string) $supplier->id ? ' · Disarankan' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if ($recommendation)
                                        <div class="small mt-1 {{ $recommendation->is_primary ? 'text-success' : 'text-muted' }}">
                                            @if (($recommendation->source ?? 'item') === 'category')
                                                {{ $recommendation->is_primary ? 'Pemasok utama' : 'Pemasok alternatif' }} kategori {{ $recommendation->category_name }} terpilih otomatis.
                                            @else
                                                {{ $recommendation->is_primary ? 'Pemasok utama barang terpilih otomatis.' : 'Pemasok alternatif barang terpilih otomatis.' }}
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="allocation-footer d-flex justify-content-end gap-2">
                <a href="{{ route('purchasing.purchase_requests.show', $purchase_request) }}" class="btn btn-sm btn-outline-secondary">Batal</a>
                <button type="submit" class="btn btn-sm btn-primary" id="submit-allocation">Buat PO Draft</button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selects = Array.from(document.querySelectorAll('.supplier-select'));
    const counter = document.getElementById('po-count');
    const form = document.getElementById('supplier-allocation-form');
    const submit = document.getElementById('submit-allocation');

    function updateCount() {
        const supplierCount = new Set(selects.map(select => select.value).filter(Boolean)).size;
        counter.textContent = supplierCount + ' PO draft';
    }

    selects.forEach(select => select.addEventListener('change', updateCount));
    updateCount();

    form.addEventListener('submit', function () {
        submit.disabled = true;
        submit.textContent = 'Membuat PO...';
    });
});
</script>
@endpush
