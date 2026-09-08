@php
    $isEdit = isset($supplierLoan);
    $selectedSupplier = old('supplier_id', $supplierLoan->supplier_id ?? '');
    $selectedCash = old('cash_account_id', $supplierLoan->cash_account_id ?? $cashAccounts->first()?->id);
    $selectedReceivable = old('receivable_account_id', $supplierLoan->receivable_account_id ?? $receivableAccounts->firstWhere('code', '1306')?->id);
@endphp

<div class="sl-form-grid">
    <label class="sl-field"><span>Supplier</span><select class="form-select sl-control" name="supplier_id" required><option value="">Pilih supplier</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((string)$selectedSupplier === (string)$supplier->id)>{{ $supplier->code }} · {{ $supplier->name }}</option>@endforeach</select></label>
    <label class="sl-field"><span>Tanggal Pencairan</span><input class="form-control sl-control" type="date" name="date" value="{{ old('date', optional($supplierLoan->date ?? null)->toDateString() ?: now()->toDateString()) }}" required></label>
    <label class="sl-field"><span>Jatuh Tempo <small>(opsional)</small></span><input class="form-control sl-control" type="date" name="due_date" value="{{ old('due_date', optional($supplierLoan->due_date ?? null)->toDateString()) }}"></label>
    <label class="sl-field"><span>Nominal Dana</span><input class="form-control sl-control" type="number" name="principal_amount" min="0.01" step="0.01" value="{{ old('principal_amount', $supplierLoan->principal_amount ?? '') }}" required placeholder="0"></label>
    <label class="sl-field"><span>Dibayar dari</span><select class="form-select sl-control" name="cash_account_id" required><option value="">Pilih kas/bank</option>@foreach($cashAccounts as $account)<option value="{{ $account->id }}" @selected((string)$selectedCash === (string)$account->id)>{{ $account->code }} · {{ $account->name }}</option>@endforeach</select></label>
    <label class="sl-field"><span>Akun Piutang</span><select class="form-select sl-control" name="receivable_account_id" required><option value="">Pilih akun piutang</option>@foreach($receivableAccounts as $account)<option value="{{ $account->id }}" @selected((string)$selectedReceivable === (string)$account->id)>{{ $account->code }} · {{ $account->name }}</option>@endforeach</select></label>
    <label class="sl-field sl-field-full"><span>Keterangan</span><input class="form-control sl-control" type="text" name="description" maxlength="255" value="{{ old('description', $supplierLoan->description ?? '') }}" placeholder="Contoh: Pinjaman modal bahan baku supplier"></label>
    <label class="sl-field"><span>No. Referensi <small>(opsional)</small></span><input class="form-control sl-control" type="text" name="reference" maxlength="100" value="{{ old('reference', $supplierLoan->reference ?? '') }}" placeholder="Nomor perjanjian / bukti transfer"></label>
    <label class="sl-field sl-field-full"><span>Catatan <small>(opsional)</small></span><textarea class="form-control sl-control" name="notes" rows="3">{{ old('notes', $supplierLoan->notes ?? '') }}</textarea></label>
</div>
@if($errors->any())<div class="alert alert-danger mt-3 mb-0"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
