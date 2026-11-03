@extends('layouts.app')

@section('title', 'Faktur Supplier Baru')

@php
    $user = auth()->user();
    $canSeeMoney = $user?->isOwner() || in_array($user?->role ?? '', ['accounting', 'developer']);
    $selectedOrderId = $order?->id ?? old('purchase_order_id', request('purchase_order_id'));
    $invoiceTotal = max(0, round((float) $grnPostedTotal - (float) $returnTotal, 2));
@endphp

@push('head')
<style>
    .page-wrap { max-width: 760px; margin-inline: auto; padding-bottom: 3rem; }
    .form-label { font-size: .82rem; font-weight: 600; color: var(--muted); }
    .section-title { font-size: .75rem; text-transform: uppercase; letter-spacing: .09em; color: var(--muted); margin-bottom: .75rem; }
    .po-summary-card {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: 10px;
        padding: .85rem 1rem;
        font-size: .84rem;
    }
    .po-summary-row { display: flex; justify-content: space-between; padding: .2rem 0; border-bottom: 1px solid var(--line); }
    .po-summary-row:last-child { border-bottom: none; font-weight: 600; }
    .mono { font-variant-numeric: tabular-nums; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono"; }
    .warn-box {
        background: rgba(234,179,8,.08);
        border: 1px solid rgba(234,179,8,.4);
        border-radius: 8px;
        padding: .6rem .85rem;
        font-size: .82rem;
        color: #a16207;
    }
</style>
@endpush

@section('content')
<div class="page-wrap">
    <div class="d-flex align-items-center gap-2 mb-3">
        <a href="{{ route('purchasing.supplier_invoices.index') }}" class="btn btn-sm btn-outline-secondary">← Kembali</a>
        <h1 class="h5 mb-0 fw-bold">Faktur Supplier Baru</h1>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mb-3">
            <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('purchasing.supplier_invoices.store') }}">
        @csrf

        {{-- SECTION: Referensi PO --}}
        <div class="card mb-3">
            <div class="card-body">
                <div class="section-title">Referensi Purchase Order</div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Purchase Order dengan GRN Posted <span class="text-danger">*</span></label>
                        <select name="purchase_order_id" id="sel-po" class="form-select" required>
                            <option value="">— Pilih PO —</option>
                            @foreach ($receivableOrders as $po)
                                <option value="{{ $po->id }}"
                                    @selected((string) $selectedOrderId === (string) $po->id)>
                                    {{ $po->code }} — {{ optional($po->supplier)->name ?? '?' }}
                                    @if ($canSeeMoney) ({{ rupiah($po->grand_total) }}) @endif
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Hanya PO dengan GRN Posted yang tersedia. Pilih PO untuk memuat nilai penerimaan.</div>
                    </div>
                </div>

                {{-- Ringkasan PO (jika ada) --}}
                @if ($order)
                    <div class="po-summary-card mt-3">
                        <div class="section-title mb-2">Ringkasan PO {{ $order->code }}</div>
                        <div class="po-summary-row">
                            <span class="text-muted">Supplier</span>
                            <span>{{ optional($order->supplier)->name ?? '—' }}</span>
                        </div>
                        @if ($canSeeMoney)
                        <div class="po-summary-row">
                            <span class="text-muted">Total PO</span>
                            <span class="mono">{{ rupiah($order->grand_total) }}</span>
                        </div>
                        <div class="po-summary-row">
                            <span class="text-muted">Total GRN Posted</span>
                            <span class="mono">{{ rupiah($grnPostedTotal) }}</span>
                        </div>
                        @if ($returnTotal > 0)
                        <div class="po-summary-row">
                            <span class="text-muted">Total Return</span>
                            <span class="mono text-danger">- {{ rupiah($returnTotal) }}</span>
                        </div>
                        <div class="po-summary-row">
                            <span class="text-muted">Estimasi Tagihan</span>
                            <span class="mono">{{ rupiah(max(0, $grnPostedTotal - $returnTotal)) }}</span>
                        </div>
                        @endif
                        @endif
                    </div>

                    @if ($grnPostedTotal <= 0)
                        <div class="warn-box mt-2">
                            ⚠️ PO ini belum memiliki GRN Posted dan tidak dapat dibuatkan invoice.
                        </div>
                    @endif
                @endif
            </div>
        </div>

        {{-- SECTION: Data Faktur --}}
        <div class="card mb-3">
            <div class="card-body">
                <div class="section-title">Data Faktur</div>
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label">Supplier <span class="text-danger">*</span></label>
                        <select name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" required>
                            <option value="">— Pilih supplier —</option>
                            @foreach ($suppliers as $sup)
                                <option value="{{ $sup->id }}"
                                    @selected(
                                        old('supplier_id', $order?->supplier_id) == $sup->id
                                    )>
                                    {{ $sup->code }} — {{ $sup->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label">No. Faktur Supplier (Ref)</label>
                        <input type="text" name="supplier_invoice_ref"
                            class="form-control @error('supplier_invoice_ref') is-invalid @enderror"
                            value="{{ old('supplier_invoice_ref') }}"
                            placeholder="Nomor faktur dari supplier (opsional)"
                            maxlength="100">
                        @error('supplier_invoice_ref')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-6 col-md-4">
                        <label class="form-label">Tanggal Faktur <span class="text-danger">*</span></label>
                        <input type="text" name="invoice_date"
                            class="form-control gf-date-input @error('invoice_date') is-invalid @enderror"
                            value="{{ old('invoice_date', now()->toDateString()) }}"
                            data-gf-date autocomplete="off" required>
                        @error('invoice_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-6 col-md-4">
                        <label class="form-label">Jatuh Tempo</label>
                        <input type="text" name="due_date"
                            class="form-control gf-date-input @error('due_date') is-invalid @enderror"
                            value="{{ old('due_date') }}"
                            data-gf-date autocomplete="off"
                            placeholder="Opsional">
                        @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" rows="2"
                            class="form-control @error('notes') is-invalid @enderror"
                            placeholder="Opsional">{{ old('notes') }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- SECTION: Nilai Faktur --}}
        @if ($canSeeMoney)
        <div class="card mb-3">
            <div class="card-body">
                <div class="section-title">Nilai Faktur dari GRN Posted</div>
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label">GRN Posted</label>
                        <div class="po-summary-card mono text-end">{{ rupiah($grnPostedTotal) }}</div>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label">Potongan Retur Posted</label>
                        <div class="po-summary-card mono text-end text-danger">- {{ rupiah($returnTotal) }}</div>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label">Total Invoice</label>
                        <div class="po-summary-card mono text-end fw-semibold">{{ rupiah($invoiceTotal) }}</div>
                    </div>

                    <div class="col-12">
                        <div class="form-text">Nilai dikunci dari GRN Posted dikurangi retur Posted. Nilai PO dan input manual tidak digunakan.</div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="d-flex justify-content-between">
            <a href="{{ route('purchasing.supplier_invoices.index') }}" class="btn btn-outline-secondary">
                ← Batal
            </a>
            <button type="submit" class="btn btn-primary" @disabled(!$order || $invoiceTotal <= 0)>Simpan Faktur (Draft)</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
// Memuat ulang basis invoice setelah PO dipilih.
(function () {
    const select = document.getElementById('sel-po');
    if (!select) return;
    select.addEventListener('change', function () {
        const url = new URL(window.location.href);
        if (this.value) {
            url.searchParams.set('purchase_order_id', this.value);
        } else {
            url.searchParams.delete('purchase_order_id');
        }
        window.location.assign(url.toString());
    });
})();
</script>
@endpush
