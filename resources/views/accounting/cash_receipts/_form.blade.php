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
        <input class="form-control cr-form-control cr-amount-input" type="number" step="1" min="0" inputmode="numeric" name="amount" data-cr-amount
            value="{{ old('amount', $cashReceipt->amount ?? null) }}" required placeholder="0">
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
        <textarea class="form-control cr-form-control" name="notes" rows="3" placeholder="">{{ old('notes', $cashReceipt->notes ?? null) }}</textarea>
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

{{-- AUTO CASH RECEIPT FORM BEHAVIOR --}}
<style>
/* Hide Catatan */
input[name="note"],
input[name="notes"],
input[name="catatan"],
textarea[name="note"],
textarea[name="notes"],
textarea[name="catatan"] {
    display: none !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const noteSelectors = [
        'input[name="note"]',
        'input[name="notes"]',
        'input[name="catatan"]',
        'textarea[name="note"]',
        'textarea[name="notes"]',
        'textarea[name="catatan"]'
    ];

    document.querySelectorAll(noteSelectors.join(',')).forEach(function (el) {
        el.required = false;
        el.disabled = true;

        const wrap =
            el.closest('.cr-field') ||
            el.closest('.mb-3') ||
            el.closest('.form-group') ||
            el.closest('.col') ||
            el.closest('label') ||
            el.parentElement;

        if (wrap) {
            wrap.style.setProperty('display', 'none', 'important');
        } else {
            el.style.setProperty('display', 'none', 'important');
        }
    });

    const amountSelectors = [
        'input[name="amount"]',
        'input[name="nominal"]',
        'input[name="jumlah"]',
        'input[name="total"]',
        'input[data-cr-amount]',
        '.cr-amount-input'
    ];

    function cleanAmount(input) {
        if (!input) return;

        let value = String(input.value || '').trim();
        value = value.replace(/\.00$/, '');
        value = value.replace(/[^\d]/g, '');

        input.value = value;
        input.setAttribute('inputmode', 'numeric');
        input.setAttribute('pattern', '[0-9]*');
        input.setAttribute('step', '1');
        input.setAttribute('autocomplete', 'off');
    }

    function focusAmount(scope) {
        const input = (scope || document).querySelector(amountSelectors.join(','));
        if (!input) return;

        cleanAmount(input);

        setTimeout(function () {
            input.focus({ preventScroll: false });
            try {
                input.select();
                input.setSelectionRange(0, input.value.length);
            } catch (e) {}
        }, 120);
    }

    document.querySelectorAll(amountSelectors.join(',')).forEach(function (input) {
        cleanAmount(input);

        input.addEventListener('focus', function () {
            setTimeout(function () {
                try {
                    input.select();
                    input.setSelectionRange(0, input.value.length);
                } catch (e) {}
            }, 40);
        });

        input.addEventListener('input', function () {
            cleanAmount(input);
        });

        input.addEventListener('blur', function () {
            cleanAmount(input);
        });
    });

    document.addEventListener('shown.bs.modal', function (event) {
        focusAmount(event.target);
    });

    if (!document.querySelector('.modal.show')) {
        focusAmount(document);
    }
});
</script>

