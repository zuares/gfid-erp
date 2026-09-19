@php
    $selectedSupplier = old('supplier_id', $supplierLoan->supplier_id ?? '');
    $selectedCash = old('cash_account_id', $supplierLoan->cash_account_id ?? $cashAccounts->first()?->id);
    $selectedReceivable = old('receivable_account_id', $supplierLoan->receivable_account_id ?? $receivableAccounts->firstWhere('code', '1306')?->id);
@endphp

<div class="sl-form-grid">
    <label class="sl-field sl-field-full"><span class="sl-field-label">Supplier <b>*</b></span><select class="form-select sl-control" name="supplier_id" required><option value="">Pilih supplier</option>@foreach ($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((string) $selectedSupplier === (string) $supplier->id)>{{ $supplier->code }} · {{ $supplier->name }}</option>@endforeach</select></label>
    <label class="sl-field"><span class="sl-field-label">Tanggal pencairan <b>*</b></span><input class="form-control sl-control" type="date" name="date" value="{{ old('date', optional($supplierLoan->date ?? null)->toDateString() ?: now()->toDateString()) }}" required></label>
    <label class="sl-field"><span class="sl-field-label">Nominal dana <b>*</b></span><span class="sl-money"><span>Rp</span><input class="form-control sl-control" type="number" name="principal_amount" min="0.01" step="0.01" inputmode="decimal" value="{{ old('principal_amount', $supplierLoan->principal_amount ?? '') }}" placeholder="0" required></span></label>
    <label class="sl-field"><span class="sl-field-label">Jatuh tempo <em>opsional</em></span><input class="form-control sl-control" type="date" name="due_date" value="{{ old('due_date', optional($supplierLoan->due_date ?? null)->toDateString()) }}"></label>
    <label class="sl-field"><span class="sl-field-label">Dibayar dari <b>*</b></span><select class="form-select sl-control" name="cash_account_id" required><option value="">Pilih kas / bank</option>@foreach ($cashAccounts as $account)<option value="{{ $account->id }}" @selected((string) $selectedCash === (string) $account->id)>{{ $account->code }} · {{ $account->name }}</option>@endforeach</select></label>
    <label class="sl-field"><span class="sl-field-label">Akun piutang <b>*</b></span><select class="form-select sl-control" name="receivable_account_id" required><option value="">Pilih akun piutang</option>@foreach ($receivableAccounts as $account)<option value="{{ $account->id }}" @selected((string) $selectedReceivable === (string) $account->id)>{{ $account->code }} · {{ $account->name }}</option>@endforeach</select></label>
    <label class="sl-field"><span class="sl-field-label">No. referensi <em>opsional</em></span><input class="form-control sl-control" type="text" name="reference" maxlength="100" value="{{ old('reference', $supplierLoan->reference ?? '') }}" placeholder="No. perjanjian / bukti transfer"></label>
    <label class="sl-field"><span class="sl-field-label">Keterangan <em>opsional</em></span><input class="form-control sl-control" type="text" name="description" maxlength="255" value="{{ old('description', $supplierLoan->description ?? 'Pinjaman supplier') }}" placeholder="Tujuan dana supplier"></label>
    <label class="sl-field sl-field-full"><span class="sl-field-label">Catatan <em>opsional</em></span><textarea class="form-control sl-control" name="notes" rows="3" placeholder="Catatan tambahan transaksi">{{ old('notes', $supplierLoan->notes ?? '') }}</textarea></label>
</div>

@if ($errors->any())
    <div class="alert alert-danger mt-3 mb-0"><strong>Periksa kembali data yang diisi.</strong><ul class="mb-0 mt-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
