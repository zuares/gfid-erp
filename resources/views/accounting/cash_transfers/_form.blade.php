@php
    $isEdit = isset($cashTransfer) && $cashTransfer->exists;
    $defaultFrom = $cashAccounts->firstWhere('code', '1111')?->id ?? $cashAccounts->first()?->id;
    $selectedFrom = old('from_cash_account_id', $cashTransfer->from_cash_account_id ?? ($isEdit ? '' : $defaultFrom));
    $selectedTo = old('to_cash_account_id', $cashTransfer->to_cash_account_id ?? '');
@endphp

<div class="ct-form-grid">
    <label class="ct-field">
        <span>Tanggal</span>
        <input class="form-control ct-form-control" type="text" name="date" data-gf-date
            value="{{ old('date', optional($cashTransfer->date ?? null)->toDateString() ?: now()->toDateString()) }}"
            required autocomplete="off">
    </label>

    <label class="ct-field">
        <span>Nominal</span>
        <div class="input-group">
            <span class="input-group-text bg-white fw-bold">Rp</span>
            <input class="form-control ct-form-control" type="number" name="amount" min="0.01" step="0.01"
                value="{{ old('amount', $cashTransfer->amount ?? '') }}" required placeholder="0">
        </div>
    </label>

    <label class="ct-field">
        <span>Dari Kas/Bank</span>
        <select class="form-select ct-form-control" name="from_cash_account_id" required>
            <option value="">Pilih akun asal</option>
            @foreach ($cashAccounts as $account)
                <option value="{{ $account->id }}" @selected((int) $selectedFrom === (int) $account->id)>
                    {{ $account->name }}{{ $account->code ? " · {$account->code}" : '' }}
                </option>
            @endforeach
        </select>
    </label>

    <label class="ct-field">
        <span>Ke Kas/Bank</span>
        <select class="form-select ct-form-control" name="to_cash_account_id" required>
            <option value="">Pilih akun tujuan</option>
            @foreach ($cashAccounts as $account)
                <option value="{{ $account->id }}" @selected((int) $selectedTo === (int) $account->id)>
                    {{ $account->name }}{{ $account->code ? " · {$account->code}" : '' }}
                </option>
            @endforeach
        </select>
    </label>

    <label class="ct-field ct-field-full">
        <span>Keterangan</span>
        <input class="form-control ct-form-control" type="text" name="description" maxlength="255"
            value="{{ old('description', $cashTransfer->description ?? '') }}"
            placeholder="Contoh: Penarikan tunai dari Bank Jago">
    </label>

    <label class="ct-field ct-field-full">
        <span>No. Referensi <small>opsional</small></span>
        <input class="form-control ct-form-control" type="text" name="reference" maxlength="100"
            value="{{ old('reference', $cashTransfer->reference ?? '') }}" placeholder="Contoh: Bukti penarikan">
    </label>

    <label class="ct-field ct-field-full">
        <span>Catatan <small>opsional</small></span>
        <textarea class="form-control ct-form-control" name="notes" rows="3"
            placeholder="Catatan tambahan...">{{ old('notes', $cashTransfer->notes ?? '') }}</textarea>
    </label>

    @if ($errors->any())
        <div class="ct-form-error ct-field-full">
            <b>Periksa lagi:</b>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
