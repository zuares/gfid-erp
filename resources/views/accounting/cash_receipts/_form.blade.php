@php
    $isEdit = isset($cashReceipt) && $cashReceipt->exists;
@endphp

<div class="cr-form-grid">
    <label class="cr-field">
        <span>Tanggal</span>
        <input class="form-control cr-form-control" type="text" name="date" data-gf-date
            value="{{ old('date', optional($cashReceipt->date ?? null)->toDateString() ?: now()->toDateString()) }}"
            required autocomplete="off">
    </label>

    <label class="cr-field">
        <span>Nominal</span>
        <input class="form-control cr-form-control" type="number" step="0.01" min="0.01" name="amount"
            value="{{ old('amount', $cashReceipt->amount ?? null) }}" required placeholder="contoh: 150000">
    </label>

    <label class="cr-field">
        <span>Terima ke</span>
        <select class="form-select cr-form-control" name="cash_account_id" required>
            <option value="">Pilih kas / bank</option>
            @foreach ($cashAccounts as $account)
                <option value="{{ $account->id }}" @selected((int) old('cash_account_id', $cashReceipt->cash_account_id ?? 0) === (int) $account->id)>
                    {{ $account->name }}{{ $account->code ? " · {$account->code}" : '' }}
                </option>
            @endforeach
        </select>
    </label>

    <label class="cr-field">
        <span>Sumber Penerimaan</span>
        <select class="form-select cr-form-control" name="source_account_id" required>
            <option value="">Pilih sumber</option>
            @foreach ($sourceAccounts as $account)
                <option value="{{ $account->id }}" @selected((int) old('source_account_id', $cashReceipt->source_account_id ?? 0) === (int) $account->id)>
                    {{ $account->name }}{{ $account->code ? " · {$account->code}" : '' }}
                </option>
            @endforeach
        </select>
    </label>

    <label class="cr-field cr-field-full">
        <span>Keterangan</span>
        <input class="form-control cr-form-control" type="text" name="description"
            value="{{ old('description', $cashReceipt->description ?? null) }}" maxlength="255"
            placeholder="contoh: Penjualan tunai, modal tambahan, refund ongkir">
    </label>

    <label class="cr-field cr-field-full">
        <span>No. Referensi <small>opsional</small></span>
        <input class="form-control cr-form-control" type="text" name="reference"
            value="{{ old('reference', $cashReceipt->reference ?? null) }}" maxlength="100"
            placeholder="contoh: INV/ORDER/NO TRANSFER">
    </label>

    <label class="cr-field cr-field-full">
        <span>Catatan <small>opsional</small></span>
        <textarea class="form-control cr-form-control" name="notes" rows="3" placeholder="Catatan tambahan...">{{ old('notes', $cashReceipt->notes ?? null) }}</textarea>
    </label>

    @if ($errors->any())
        <div class="cr-form-error cr-field-full">
            <b>Periksa lagi:</b>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
