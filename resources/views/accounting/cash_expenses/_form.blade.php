@php
    $isEdit = isset($cashExpense) && $cashExpense->exists;
@endphp

<div style="display:grid;gap:.65rem">
    <div style="display:grid;grid-template-columns:160px 1fr;gap:.6rem;align-items:center">
        <div class="muted" style="font-weight:650">Tanggal</div>
        <input class="in" type="date" name="date"
            value="{{ old('date', $cashExpense->date ?? now()->toDateString()) }}" required>
    </div>

    <div style="display:grid;grid-template-columns:160px 1fr;gap:.6rem;align-items:center">
        <div class="muted" style="font-weight:650">Nominal</div>
        <input class="in" type="number" step="0.01" min="0.01" name="amount"
            value="{{ old('amount', $cashExpense->amount ?? null) }}" required placeholder="contoh: 150000">
    </div>

    <div style="display:grid;grid-template-columns:160px 1fr;gap:.6rem;align-items:center">
        <div class="muted" style="font-weight:650">Kategori Pengeluaran</div>
        <select class="in" name="expense_account_id" required>
            <option value="">— pilih kategori —</option>
            @foreach ($expenseAccounts as $a)
                <option value="{{ $a->id }}" @selected((int) old('expense_account_id', $cashExpense->expense_account_id ?? 0) === (int) $a->id)>
                    {{ $a->name }}{{ $a->code ? " • {$a->code}" : '' }}
                </option>
            @endforeach
        </select>
    </div>

    <div style="display:grid;grid-template-columns:160px 1fr;gap:.6rem;align-items:center">
        <div class="muted" style="font-weight:650">Bayar dari</div>
        <select class="in" name="cash_account_id" required>
            <option value="">— pilih kas / bank —</option>
            @foreach ($cashAccounts as $a)
                <option value="{{ $a->id }}" @selected((int) old('cash_account_id', $cashExpense->cash_account_id ?? 0) === (int) $a->id)>
                    {{ $a->name }}{{ $a->code ? " • {$a->code}" : '' }}
                </option>
            @endforeach
        </select>
    </div>

    <div style="display:grid;grid-template-columns:160px 1fr;gap:.6rem;align-items:center">
        <div class="muted" style="font-weight:650">Keterangan</div>
        <input class="in" type="text" name="description"
            value="{{ old('description', $cashExpense->description ?? null) }}" maxlength="255"
            placeholder="contoh: Beli kardus, bensin, ongkir">
    </div>

    <div style="display:grid;grid-template-columns:160px 1fr;gap:.6rem;align-items:center">
        <div class="muted" style="font-weight:650">No. Referensi (opsional)</div>
        <input class="in" type="text" name="reference"
            value="{{ old('reference', $cashExpense->reference ?? null) }}" maxlength="100"
            placeholder="contoh: INV/STRUK/NO NOTA">
    </div>

    <div style="display:grid;grid-template-columns:160px 1fr;gap:.6rem;align-items:start">
        <div class="muted" style="font-weight:650;padding-top:.5rem">Catatan (opsional)</div>
        <textarea class="in" name="notes" rows="3" placeholder="Catatan tambahan...">{{ old('notes', $cashExpense->notes ?? null) }}</textarea>
    </div>

    @if ($errors->any())
        <div
            style="padding:.65rem .75rem;border-radius:12px;border:1px solid rgba(239,68,68,.25);background:rgba(239,68,68,.08)">
            <div style="font-weight:800;margin-bottom:.25rem">Periksa lagi:</div>
            <ul style="margin:.25rem 0 0;padding-left:1.2rem">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
