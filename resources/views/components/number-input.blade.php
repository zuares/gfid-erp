@props([
    'name',
    'value' => null,

    // 'integer' atau 'decimal'
    'mode' => 'decimal',

    'placeholder' => '',
    'min' => null,
    'max' => null,

    // jumlah decimal saat blur
    'decimals' => 2,

    // auto focus
    'autofocus' => false,
])

@php
    use Illuminate\Support\Str;

    $id = $attributes->get('id') ?? 'num-' . Str::random(6);
    $inputmode = $mode === 'integer' ? 'numeric' : 'decimal';

    $rawValue = old($name, $value);
@endphp

<input type="text" name="{{ $name }}" id="{{ $id }}" value="{{ $rawValue }}"
    inputmode="{{ $inputmode }}" autocomplete="off" pattern="{{ $mode === 'integer' ? '\d*' : '[0-9]*[.,]?[0-9]*' }}"
    data-number-mode="{{ $mode }}" data-min="{{ $min !== null ? $min : '' }}"
    data-max="{{ $max !== null ? $max : '' }}" data-decimals="{{ $decimals }}"
    data-autofocus="{{ $autofocus ? '1' : '0' }}"
    class="form-control form-control-sm number-input js-number-input {{ $attributes->get('class') }}"
    placeholder="{{ $placeholder }}" />

@once
    @push('head')
        <style>
            /* Desktop: kanan */
            .number-input {
                text-align: right;
            }

            /* Mobile: tengah + anti zoom */
            @media (max-width: 768px) {
                .number-input {
                    text-align: center;
                    font-size: 16px;
                    padding-top: .5rem;
                    padding-bottom: .5rem;
                }
            }
        </style>
    @endpush


    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => initNumberInputs());

            function initNumberInputs(scope = document) {
                scope.querySelectorAll('.js-number-input:not([data-number-inited])')
                    .forEach(input => {
                        setupNumberInput(input);
                        input.dataset.numberInited = "1";
                    });
            }

            function setupNumberInput(input) {
                const mode = input.dataset.numberMode || 'decimal';
                const min = input.dataset.min !== '' ? parseFloat(input.dataset.min) : null;
                const max = input.dataset.max !== '' ? parseFloat(input.dataset.max) : null;
                const decimals = parseInt(input.dataset.decimals || '2');

                /* -----------------------------
                 * INPUT FILTER (realtime)
                 * ----------------------------- */
                input.addEventListener('input', () => {
                    let v = input.value;

                    if (mode === 'integer') {
                        v = v.replace(/[^\d]/g, '');
                    } else {
                        v = v.replace(/,/g, '.'); // koma → titik
                        v = v.replace(/[^0-9.]/g, ''); // angka + titik

                        const parts = v.split('.');
                        if (parts.length > 2) {
                            v = parts[0] + '.' + parts.slice(1).join('');
                        }
                    }

                    input.value = v;
                });

                /* -----------------------------
                 * SELECT ALL + SCROLL
                 * ----------------------------- */
                function scrollToCenter() {
                    setTimeout(() => {
                        try {
                            input.scrollIntoView({
                                block: 'center',
                                behavior: 'smooth'
                            });
                        } catch (e) {
                            const r = input.getBoundingClientRect();
                            window.scrollTo({
                                top: window.pageYOffset + r.top - 150,
                                behavior: 'smooth'
                            });
                        }
                    }, 120);
                }

                function selectAllIfFilled() {
                    if (input.value.length > 0) input.select();
                }

                input.addEventListener('focus', () => {
                    selectAllIfFilled();
                    scrollToCenter();
                });

                input.addEventListener('click', () => selectAllIfFilled());

                /* -----------------------------
                 * FORMAT & VALIDATE ON BLUR
                 * ----------------------------- */
                input.addEventListener('blur', () => {
                    let v = input.value.trim();
                    if (v === '') return;

                    v = v.replace(/,/g, '.');

                    // Pastikan hanya 1 titik
                    const parts = v.split('.');
                    if (parts.length > 2) {
                        v = parts[0] + '.' + parts.slice(1).join('');
                    }

                    let num =
                        mode === 'integer' ?
                        parseInt(v, 10) :
                        parseFloat(v);

                    // Jika parse gagal (misalnya user ketik 25,,87)
                    if (isNaN(num)) {
                        if (parts.length >= 2) {
                            num = Number(parts[0] + "." + parts[1]);
                        }
                    }

                    if (isNaN(num)) {
                        input.value = '';
                        return;
                    }

                    // batas min/max
                    if (min !== null && num < min) num = min;
                    if (max !== null && num > max) num = max;

                    if (mode === 'integer') {
                        input.value = String(num);
                    } else {
                        // JANGAN BULATKAN SALAH — pakai fixed decimals
                        input.value = Number(num).toFixed(decimals);
                    }
                });

                /* -----------------------------
                 * AUTO FOCUS (optional)
                 * ----------------------------- */
                if (input.dataset.autofocus === '1') {
                    setTimeout(() => {
                        input.focus();
                        selectAllIfFilled();
                        scrollToCenter();
                    }, 200);
                }
            }
        </script>
    @endpush
@endonce
