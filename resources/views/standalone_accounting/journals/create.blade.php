@extends('layouts.app')

@section('title', 'Akuntansi Baru • Buat Jurnal')

@php
    $isOpening = ($mode ?? 'general') === 'opening';
    $pageTitle = $isOpening ? 'Akuntansi Baru — Jurnal Saldo Awal' : 'Akuntansi Baru — Buat Jurnal';
    $pageDescription = $isOpening
        ? 'Catat kas, bank, dan saldo non-persediaan awal. Persediaan awal diposting dari Opening Stock Opname.'
        : 'Jurnal harus seimbang sebelum bisa diposting.';
    $formatMoneyInput = static function ($value): string {
        if ($value === null || $value === '') {
            return '';
        }

        return rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
    };
@endphp

@push('head')
    <style>
        .new-accounting-btn { display:inline-flex; align-items:center; justify-content:center; min-height:38px; padding:.45rem .85rem; border:1px solid #dbe3ee; border-radius:10px; background:#fff; color:#0f172a; text-decoration:none; font-size:.84rem; font-weight:700; }
        .new-accounting-btn:hover { background:#f8fafc; color:#0f172a; }
        .new-accounting-btn.primary { background:#0f172a; border-color:#0f172a; color:#fff; }
        .new-accounting-btn.primary:hover { background:#1e293b; color:#fff; }
        .journal-line-grid { display:grid; grid-template-columns:minmax(240px, 1fr) 180px 180px; gap:.5rem; align-items:end; }
        @media(max-width:768px) { .journal-line-grid { grid-template-columns:1fr; } }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
        <div>
            <h4 class="mb-1">{{ $pageTitle }}</h4>
            <div class="text-muted small">{{ $pageDescription }}</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if ($isOpening)
                <a class="new-accounting-btn" href="{{ route('standalone-accounting.journals.create', ['mode' => 'general']) }}">Jurnal Umum</a>
            @else
                <a class="new-accounting-btn primary" href="{{ route('standalone-accounting.journals.create', ['mode' => 'opening']) }}">Jurnal Saldo Awal</a>
            @endif
            <a class="new-accounting-btn" href="{{ route('standalone-accounting.journals.index') }}">Kembali ke Jurnal</a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('standalone-accounting.journals.store') }}"
                @if ($isOpening) data-opening-mode="1" @endif>
                @csrf
                <input type="hidden" name="mode" value="{{ $mode ?? 'general' }}">
                @if ($isOpening)
                    <div class="alert alert-info small">
                        <strong>Saldo awal:</strong> kreditkan akun <strong>3-110 Modal Pemilik</strong> untuk sumber dana pemilik.
                        Jangan masukkan akun persediaan di form ini; gunakan Opening Stock Opname.
                    </div>
                @endif
                <div class="row g-3 mb-4">
                    <div class="col-md-3"><label class="form-label" for="date">Tanggal</label><input id="date" name="date" type="date" class="form-control" value="{{ old('date', now()->toDateString()) }}" required></div>
                    <div class="col-md-3"><label class="form-label" for="reference">Referensi{{ $isOpening ? ' *' : '' }}</label><input id="reference" name="reference" class="form-control" value="{{ old('reference', $isOpening ? $openingReference : null) }}" placeholder="{{ $isOpening ? 'OB-2026-001' : 'JV-0001' }}" {{ $isOpening ? 'required' : '' }}></div>
                    <div class="col-md-6"><label class="form-label" for="description">Keterangan</label><input id="description" name="description" class="form-control" value="{{ old('description', $isOpening ? 'Saldo awal kas/bank' : '') }}" placeholder="Saldo awal kas"></div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2"><h6 class="mb-0">Baris Jurnal</h6><span class="text-muted small">Isi debit atau kredit pada setiap baris.</span></div>
                <div id="journal-lines" class="d-grid gap-2">
                    @for ($i = 0; $i < 4; $i++)
                        <div class="journal-line-grid journal-line">
                            <div><label class="form-label small">Akun</label><select name="lines[{{ $i }}][account_id]" class="form-select"><option value="">Pilih akun</option>@foreach ($accounts as $account)<option value="{{ $account->id }}" @selected(old("lines.$i.account_id", $isOpening && $i === 1 ? ($openingEquityAccount?->id ?? null) : null) == $account->id)>{{ $account->code }} — {{ $account->name }}</option>@endforeach</select></div>
                            <div>
                                <label class="form-label small">Debit</label>
                                <input type="text" inputmode="decimal" autocomplete="off" class="form-control js-money-input"
                                    data-money-target="lines[{{ $i }}][debit]" data-money-side="debit"
                                    value="{{ $formatMoneyInput(old("lines.$i.debit")) }}">
                                <input type="hidden" name="lines[{{ $i }}][debit]" class="js-money-value" data-money-side="debit" value="{{ old("lines.$i.debit") }}">
                            </div>
                            <div>
                                <label class="form-label small">Kredit</label>
                                <input type="text" inputmode="decimal" autocomplete="off" class="form-control js-money-input {{ $isOpening && $i === 1 ? 'js-opening-equity-credit-display' : '' }}"
                                    data-money-target="lines[{{ $i }}][credit]" data-money-side="credit"
                                    value="{{ $formatMoneyInput(old("lines.$i.credit")) }}">
                                <input type="hidden" name="lines[{{ $i }}][credit]" class="js-money-value {{ $isOpening && $i === 1 ? 'js-opening-equity-credit-value' : '' }}" data-money-side="credit" value="{{ old("lines.$i.credit") }}">
                            </div>
                        </div>
                    @endfor
                </div>

                <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mt-3">
                    <button type="button" id="add-line" class="new-accounting-btn">+ Tambah Baris</button>
                    <div class="d-flex gap-2"><a href="{{ route('standalone-accounting.journals.index') }}" class="new-accounting-btn">Batal</a><button class="new-accounting-btn primary" type="submit">Posting Jurnal</button></div>
                </div>
            </form>
        </div>
    </div>

    <template id="journal-line-template">
        <div class="journal-line-grid journal-line">
            <div><label class="form-label small">Akun</label><select name="lines[__INDEX__][account_id]" class="form-select"><option value="">Pilih akun</option>@foreach ($accounts as $account)<option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>@endforeach</select></div>
            <div><label class="form-label small">Debit</label><input type="text" inputmode="decimal" autocomplete="off" class="form-control js-money-input" data-money-target="lines[__INDEX__][debit]" data-money-side="debit"><input type="hidden" name="lines[__INDEX__][debit]" class="js-money-value" data-money-side="debit"></div>
            <div><label class="form-label small">Kredit</label><input type="text" inputmode="decimal" autocomplete="off" class="form-control js-money-input" data-money-target="lines[__INDEX__][credit]" data-money-side="credit"><input type="hidden" name="lines[__INDEX__][credit]" class="js-money-value" data-money-side="credit"></div>
        </div>
    </template>
    <script>
        document.getElementById('add-line')?.addEventListener('click', () => {
            const container = document.getElementById('journal-lines');
            const template = document.getElementById('journal-line-template').innerHTML;
            container.insertAdjacentHTML('beforeend', template.replaceAll('__INDEX__', container.children.length));
        });

        const moneyInputs = document.querySelectorAll('.js-money-input');
        const moneyValue = (raw) => {
            let value = String(raw ?? '').trim().replace(/\s/g, '');
            if (!value) return '';

            // Indonesian input: 1.234.567,89. Also accept plain 1000000.00.
            if (value.includes(',')) {
                value = value.replace(/\./g, '').replace(',', '.');
            } else if (/^\d{1,3}(\.\d{3})+$/.test(value)) {
                value = value.replace(/\./g, '');
            }

            value = value.replace(/[^\d.-]/g, '');
            const parsed = Number.parseFloat(value);
            return Number.isFinite(parsed) ? parsed.toFixed(2) : '';
        };

        const formatMoney = (raw) => {
            const normalized = moneyValue(raw);
            if (normalized === '') return '';

            return new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2,
            }).format(Number(normalized));
        };

        const formatMoneyLive = (raw) => {
            let value = String(raw ?? '').trim().replace(/\s/g, '');
            if (!value) return '';

            // Keep a trailing comma while the user is still typing decimals.
            if (value.includes(',')) {
                const [integerPart, fractionPart = ''] = value.split(',', 2);
                const integer = integerPart.replace(/\D/g, '') || '0';
                const fraction = fractionPart.replace(/\D/g, '').slice(0, 2);

                return `${Number(integer).toLocaleString('id-ID')},${fraction}`;
            }

            // Once a value is formatted as 1.000, the next typed digit can
            // produce 1.0005. Keep treating the dot as a thousands separator.
            const dotParts = value.split('.');
            const isFormattedThousands = dotParts.length > 1
                && dotParts[0].length <= 3
                && dotParts.slice(1).every((part) => /^\d+$/.test(part))
                && dotParts.slice(1).some((part) => part.length >= 3);
            if (isFormattedThousands) {
                return Number(dotParts.join('')).toLocaleString('id-ID');
            }

            // Also accept a pasted plain decimal such as 1000.50.
            const plainDecimal = value.match(/^(\d+)\.(\d{0,2})$/);
            if (plainDecimal && plainDecimal[2].length < 2) {
                return `${Number(plainDecimal[1]).toLocaleString('id-ID')},${plainDecimal[2]}`;
            }

            return formatMoney(value);
        };

        const countDigits = (value) => (value.match(/\d/g) || []).length;

        const positionAfterDigits = (value, digitCount, start = 0, end = value.length) => {
            if (digitCount <= 0) return start;

            let seen = 0;
            for (let index = start; index < end; index++) {
                if (/\d/.test(value[index])) {
                    seen++;
                    if (seen >= digitCount) return index + 1;
                }
            }

            return end;
        };

        const setLiveFormattedValue = (input) => {
            const original = input.value;
            const caret = input.selectionStart ?? original.length;
            const originalComma = original.indexOf(',');
            const formatted = formatMoneyLive(original);
            const formattedComma = formatted.indexOf(',');
            const beforeCaret = original.slice(0, caret);
            const isInFraction = originalComma >= 0 && caret > originalComma;

            input.value = formatted;

            let nextCaret;
            if (isInFraction && formattedComma >= 0) {
                const fractionBeforeCaret = countDigits(beforeCaret.slice(originalComma + 1));
                nextCaret = positionAfterDigits(
                    formatted,
                    fractionBeforeCaret,
                    formattedComma + 1,
                    formatted.length,
                );
            } else {
                nextCaret = positionAfterDigits(formatted, countDigits(beforeCaret), 0, formattedComma >= 0 ? formattedComma : formatted.length);
            }

            input.setSelectionRange(nextCaret, nextCaret);
        };

        const syncMoneyInput = (input, formatDisplay = false) => {
            const form = input.closest('form');
            const target = input.dataset.moneyTarget;
            const hidden = Array.from(form.querySelectorAll('.js-money-value'))
                .find((element) => element.name === target);
            if (!hidden) return;

            hidden.value = moneyValue(input.value);
            if (formatDisplay) input.value = formatMoney(hidden.value);
        };

        moneyInputs.forEach((input) => {
            const form = input.closest('form');
            const hidden = Array.from(form.querySelectorAll('.js-money-value'))
                .find((element) => element.name === input.dataset.moneyTarget);
            if (hidden?.value) input.value = formatMoney(hidden.value);
        });

        document.addEventListener('input', (event) => {
            if (event.target.matches?.('.js-money-input')) {
                setLiveFormattedValue(event.target);
                syncMoneyInput(event.target);
            }
        });
        document.addEventListener('focusout', (event) => {
            if (event.target.matches?.('.js-money-input')) syncMoneyInput(event.target, true);
        });

        const openingForm = document.querySelector('form[data-opening-mode]');
        if (openingForm) {
            const equityCreditValue = openingForm.querySelector('.js-opening-equity-credit-value');
            const equityCreditDisplay = openingForm.querySelector('.js-opening-equity-credit-display');
            let autoFillEquity = true;

            const syncOpeningEquity = () => {
                if (!equityCreditValue || !equityCreditDisplay || !autoFillEquity) return;

                const totalDebit = Array.from(openingForm.querySelectorAll('.js-money-value[data-money-side="debit"]'))
                    .reduce((total, input) => total + (Number.parseFloat(input.value) || 0), 0);
                const formatted = totalDebit > 0 ? totalDebit.toFixed(2) : '';

                equityCreditValue.value = formatted;
                equityCreditDisplay.value = formatMoney(formatted);
            };

            openingForm.addEventListener('input', (event) => {
                if (event.target === equityCreditDisplay || event.target.matches('.js-money-input[data-money-side="credit"]')) {
                    autoFillEquity = false;
                }

                if (event.target.matches('.js-money-input[data-money-side="debit"]')) {
                    setLiveFormattedValue(event.target);
                    syncMoneyInput(event.target);
                    syncOpeningEquity();
                }
            });

            syncOpeningEquity();
        }
    </script>
@endsection
