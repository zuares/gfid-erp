@extends('layouts.app')

@section('title', 'Pilih Supplier - ' . $purchase_request->code)

@push('head')
<style>
    .allocation-wrap { max-width: 1080px; margin-inline: auto; padding: .65rem .75rem 3rem; }
    .allocation-topbar { position: sticky; top: 0; z-index: 250; display: flex; align-items: center; gap: .55rem; flex-wrap: wrap; padding: .55rem .7rem; margin: 0 -.75rem .7rem; background: var(--card, #fff); border-bottom: 1px solid rgba(148,163,184,.18); }
    .allocation-back { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border: 1px solid rgba(148,163,184,.3); border-radius: 7px; color: #64748b; text-decoration: none; }
    .allocation-back:hover { background: rgba(148,163,184,.1); color: #111827; }
    .allocation-heading { min-width: 170px; }
    .allocation-eyebrow { display: block; color: #64748b; font-size: .64rem; font-weight: 850; letter-spacing: .08em; text-transform: uppercase; }
    .allocation-code { color: #111827; font-size: 1.04rem; font-weight: 900; }
    .allocation-spacer { flex: 1; }
    .allocation-btn { display: inline-flex; align-items: center; justify-content: center; gap: .35rem; min-height: 34px; padding: .3rem .65rem; border: 1px solid rgba(148,163,184,.32); border-radius: 7px; background: transparent; color: #475569; text-decoration: none; font-size: .76rem; font-weight: 800; cursor: pointer; }
    .allocation-btn:hover { background: rgba(148,163,184,.1); color: #111827; text-decoration: none; }
    .allocation-primary { color: #fff!important; background: #334155!important; border-color: #334155!important; }
    .allocation-primary:hover { background: #1e293b!important; border-color: #1e293b!important; }
    .allocation-card { background: var(--card); border: 1px solid rgba(148,163,184,.18); border-radius: 9px; overflow: hidden; margin-bottom: .65rem; }
    .allocation-kpis { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .allocation-kpi { padding: .7rem .8rem; border-right: 1px solid rgba(148,163,184,.14); }
    .allocation-kpi:last-child { border-right: 0; }
    .allocation-label { color: #64748b; font-size: .68rem; font-weight: 850; letter-spacing: .04em; text-transform: uppercase; }
    .allocation-value { margin-top: .18rem; color: #111827; font-size: .92rem; font-weight: 850; }
    .allocation-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .68rem .85rem; border-bottom: 1px solid rgba(148,163,184,.14); }
    .allocation-title { color: #334155; font-size: .88rem; font-weight: 900; }
    .allocation-subtitle { color: #64748b; font-size: .76rem; }
    .allocation-table { width: 100%; border-collapse: collapse; }
    .allocation-table th, .allocation-table td { padding: .58rem .7rem; border-bottom: 1px solid rgba(148,163,184,.12); vertical-align: middle; }
    .allocation-table th { color: #64748b; background: rgba(148,163,184,.04); font-size: .68rem; font-weight: 900; letter-spacing: .04em; text-transform: uppercase; white-space: nowrap; }
    .allocation-table td { color: #334155; font-size: .82rem; }
    .allocation-table tbody tr:last-child td { border-bottom: 0; }
    .item-name { color: #111827; font-weight: 850; line-height: 1.2; }
    .item-code { color: #64748b; font-size: .72rem; margin-top: .12rem; }
    .qty-cell { color: #111827!important; font-weight: 800; white-space: nowrap; }
    .qty-unit { color: #64748b; font-size: .72rem; font-weight: 500; }
    .allocation-col-action { width: 52px; }
    .supplier-select { min-width: 240px; }
    .recommendation { display: inline-flex; align-items: center; gap: .25rem; margin-top: .25rem; color: #64748b; font-size: .68rem; font-weight: 750; }
    .recommendation.primary { color: #15803d; }
    .allocation-footer { display: flex; align-items: center; justify-content: flex-end; gap: .45rem; padding: .68rem .85rem; border-top: 1px solid rgba(148,163,184,.14); background: rgba(148,163,184,.04); }
    body[data-theme="dark"] .allocation-code, body[data-theme="dark"] .allocation-value, body[data-theme="dark"] .item-name, body[data-theme="dark"] .qty-cell { color: #e2e8f0!important; }
    body[data-theme="dark"] .allocation-title, body[data-theme="dark"] .allocation-table td { color: #cbd5e1; }
    @media (max-width: 767.98px) {
        .allocation-wrap { padding: .45rem .5rem 3rem; }
        .allocation-topbar { margin-inline: -.5rem; padding: .5rem; }
        .allocation-spacer { display: none; }
        .allocation-heading { flex: 1; min-width: 0; }
        .allocation-topbar .allocation-primary { order: 5; width: 100%; }
        .allocation-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .allocation-kpi { border-bottom: 1px solid rgba(148,163,184,.14); }
        .allocation-kpi:nth-child(2n) { border-right: 0; }
        .allocation-kpi:last-child { grid-column: 1 / -1; border-bottom: 0; }
        .allocation-table thead { display: none; }
        .allocation-table, .allocation-table tbody, .allocation-table tr, .allocation-table td { display: block; width: 100%; }
        .allocation-table tr { padding: .62rem .7rem; border-bottom: 1px solid rgba(148,163,184,.14); }
        .allocation-table td { display: grid; grid-template-columns: 6.5rem minmax(0,1fr); gap: .5rem; border: 0; padding: .16rem 0; }
        .allocation-table td.item-cell { display: block; padding-bottom: .35rem; }
        .allocation-table td[data-label]::before { content: attr(data-label); color: #94a3b8; font-size: .63rem; font-weight: 900; letter-spacing: .04em; text-transform: uppercase; }
        .allocation-table .number-cell { display: none; }
        .allocation-table .qty-cell { text-align: left !important; }
        .supplier-select { min-width: 0; width: 100%; }
        .allocation-col-action { display: block!important; text-align: right; }
        .allocation-col-action::before { display: none; }
        .allocation-footer { position: sticky; bottom: 0; z-index: 20; margin: 0 -.5rem; padding: .55rem .6rem; }
        .allocation-footer .allocation-primary { flex: 1; }
    }
</style>
@endpush

@section('content')
<div class="allocation-wrap">
    <div class="allocation-topbar">
        <a href="{{ route('purchasing.purchase_requests.show', $purchase_request) }}" class="allocation-back" title="Kembali"><i class="bi bi-arrow-left"></i></a>
        <div class="allocation-heading"><span class="allocation-eyebrow">Alokasi Supplier</span><span class="allocation-code mono">{{ $purchase_request->code }}</span></div>
        <div class="allocation-spacer"></div>
        <a href="{{ route('purchasing.purchase_requests.show', $purchase_request) }}" class="allocation-btn"><i class="bi bi-x-lg"></i><span class="d-none d-md-inline">Batal</span></a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('purchasing.purchase_requests.convert', $purchase_request) }}" id="supplier-allocation-form">
        @csrf
        <div class="allocation-card">
            <div class="allocation-kpis">
                <div class="allocation-kpi"><div class="allocation-label">Barang</div><div class="allocation-value"><span id="allocation-item-count">{{ $purchase_request->lines->count() }}</span> item</div></div>
                <div class="allocation-kpi"><div class="allocation-label">Supplier</div><div class="allocation-value" id="supplier-count">0</div></div>
                <div class="allocation-kpi"><div class="allocation-label">PO Draft</div><div class="allocation-value" id="po-count">0</div></div>
            </div>
            <div class="allocation-head">
                <div><div class="allocation-title">Pilih Supplier</div><div class="allocation-subtitle">Satu PO dibuat untuk setiap supplier.</div></div>
            </div>
            <div class="table-responsive">
                <table class="allocation-table">
                        <tr>
                            <th class="text-center" style="width:50px">No</th>
                            <th>Barang</th>
                            <th class="text-end" style="width:120px">Qty</th>
                            <th style="min-width:260px">Supplier</th>
                            <th class="allocation-col-action"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($purchase_request->lines as $index => $line)
                            @php
                                $recommendation = $recommendedSuppliers->get($line->item_id);
                                $defaultSupplierId = old("suppliers.{$line->id}") ?: $recommendation?->supplier_id ?: $purchase_request->supplier_id;
                            @endphp
                            <tr data-line-id="{{ $line->id }}">
                                <td class="text-center text-muted number-cell">{{ $index + 1 }}</td>
                                <td class="item-cell">
                                    <div class="item-name">{{ $line->item?->name ?? 'Item tidak ditemukan' }}</div>
                                    <div class="item-code">{{ $line->item?->code ?? '-' }}</div>
                                </td>
                                <td class="text-end qty-cell" data-label="Qty">
                                    {{ number_format($line->qty, 2, ',', '.') }} <span class="qty-unit">{{ $line->effectivePurchaseUnit() }}</span>
                                </td>
                                <td data-label="Supplier">
                                    <select name="suppliers[{{ $line->id }}]" class="form-select form-select-sm supplier-select" required>
                                        <option value="">Pilih supplier</option>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" @selected((string) $defaultSupplierId === (string) $supplier->id)>
                                                {{ $supplier->name }}{{ $supplier->code ? ' · ' . $supplier->code : '' }}{{ (string) $recommendation?->supplier_id === (string) $supplier->id ? ' · Disarankan' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if ($recommendation)
                                        @php
                                            $recommendationLabel = match ($recommendation->source ?? 'item') {
                                                'history', 'history_category' => 'Riwayat',
                                                'fallback' => 'Default',
                                                'category' => 'Kategori',
                                                default => $recommendation->is_primary ? 'Utama' : 'Alternatif',
                                            };
                                        @endphp
                                        <span class="recommendation {{ $recommendation->is_primary ? 'primary' : '' }}"><i class="bi bi-stars"></i>{{ $recommendationLabel }}</span>
                                    @endif
                                </td>
                                <td class="allocation-col-action text-center">
                                    <button type="button" class="allocation-btn remove-allocation-row"
                                        title="Hapus baris" aria-label="Hapus baris">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="allocation-footer">
                <a href="{{ route('purchasing.purchase_requests.show', $purchase_request) }}" class="allocation-btn">Batal</a>
                <button type="submit" class="allocation-btn allocation-primary" id="submit-allocation"><i class="bi bi-file-earmark-plus"></i>Buat PO Draft</button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const counter = document.getElementById('po-count');
    const form = document.getElementById('supplier-allocation-form');
    const submit = document.getElementById('submit-allocation');
    const itemCount = document.getElementById('allocation-item-count');
    const supplierCountEl = document.getElementById('supplier-count');
    const tbody = form.querySelector('tbody');

    function updateCount() {
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const selects = Array.from(tbody.querySelectorAll('.supplier-select'));
        const uniqueSupplierCount = new Set(selects.map(select => select.value).filter(Boolean)).size;
        rows.forEach((row, index) => {
            const numberCell = row.querySelector('.number-cell');
            if (numberCell) numberCell.textContent = index + 1;
        });
        itemCount.textContent = rows.length;
        supplierCountEl.textContent = uniqueSupplierCount;
        counter.textContent = uniqueSupplierCount;
    }

    tbody.addEventListener('change', function (event) {
        if (event.target.matches('.supplier-select')) updateCount();
    });

    tbody.addEventListener('click', function (event) {
        const button = event.target.closest('.remove-allocation-row');
        if (!button) return;

        const rows = tbody.querySelectorAll('tr');
        if (rows.length <= 1) {
            alert('Minimal satu baris harus dipertahankan untuk membuat PO draft.');
            return;
        }

        const row = button.closest('tr[data-line-id]');
        const itemName = row.querySelector('.item-name')?.textContent.trim() || 'item ini';
        if (!confirm('Hapus ' + itemName + ' dari proses pembuatan PO?')) return;

        row.remove();
        updateCount();
    });

    form.addEventListener('submit', function (event) {
        const rows = tbody.querySelectorAll('tr[data-line-id]');
        const hasEmptySupplier = Array.from(tbody.querySelectorAll('.supplier-select'))
            .some(select => !select.value);

        if (!rows.length || hasEmptySupplier) {
            event.preventDefault();
            alert('Pilih supplier untuk semua baris yang tersisa.');
            return;
        }

        submit.disabled = true;
        submit.textContent = 'Membuat PO...';
    });

    updateCount();
});
</script>
@endpush
