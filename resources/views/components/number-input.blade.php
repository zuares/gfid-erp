@props([
    // name wajib
    'name',

    // optional
    'id' => null,
    'value' => null,

    // HTML type: "number" atau "text"
    'htmlType' => null, // kita tentukan default di php block agar bisa override saat name mengandung 'qty'

    'step' => null, // default ditentukan di php block
    'min' => '0',
    'max' => null,

    // "numeric" → keyboard angka tanpa koma, "decimal" → angka + koma/titik
    'inputmode' => null, // default ditentukan di php block

    // ukuran Bootstrap: sm / md / lg
    'size' => 'sm',

    // alignment: start / center / end
    'align' => 'end',

    // tambahan class custom
    'class' => '',
])

@php
    // ====== defaults based on name (auto-detect qty) ======
    $lowerName = strtolower($name ?? '');
    $isQty = str_contains($lowerName, 'qty'); // deteksi 'qty' di nama

    // jika user tidak eksplisit set htmlType/inputmode/step, kita set default cerdas
    if (is_null($htmlType)) {
        $htmlType = $isQty ? 'number' : 'number';
        // note: default tetap 'number' to keep numeric behavior; if you need text (formatted) pass htmlType='text'
    }

    if (is_null($inputmode)) {
        $inputmode = $isQty ? 'numeric' : 'decimal';
    }

    if (is_null($step)) {
        $step = $isQty ? '1' : '0.01';
    }

    $inputId = $id ?? str_replace(['[', ']'], '_', $name); // id aman dari [] array
    $sizeClass = $size ? 'form-control-' . $size : '';
    $alignClass = $align ? 'text-' . $align : '';

    // Ensure attributes for numeric fields
    $stepAttr = e($step);
    $minAttr = is_null($min) ? null : e($min);
    $maxAttr = is_null($max) ? null : e($max);

    // value handling: prefer raw (not pre-formatted) if passed as numeric via :value
    $valueAttr = old($name, $value);
@endphp

<input
    id="{{ $inputId }}"
    name="{{ $name }}"
    type="{{ $htmlType }}"
    @if ($htmlType === 'number')
        step="{{ $stepAttr }}"
        min="{{ $minAttr }}"
        @if (!is_null($max)) max="{{ $maxAttr }}" @endif
    @endif

    inputmode="{{ $inputmode }}"
    @if ($inputmode === 'numeric') pattern="[0-9]*" @endif

    autocomplete="off"
    class="form-control {{ $sizeClass }} {{ $alignClass }} gfid-number-input {{ $class }}"
    value="{{ $valueAttr }}"
/>

@once
    @push('scripts')
        <script>
            (function () {
                // ---------- UX helpers: auto-select on focus / click ----------
                document.addEventListener('focus', function (e) {
                    const el = e.target;
                    if (!el.classList || !el.classList.contains('gfid-number-input')) return;
                    setTimeout(function () {
                        try { el.select(); } catch (_) {}
                    }, 0);
                }, true);

                document.addEventListener('click', function (e) {
                    const el = e.target.closest ? e.target.closest('.gfid-number-input') : null;
                    if (!el) return;
                    if (document.activeElement === el) {
                        setTimeout(function () {
                            try { el.select(); } catch (_) {}
                        }, 0);
                    }
                });

                // ---------- Sanitization before submit ----------
                // Applies to all forms on page; if you want to restrict, add an attribute to form (eg. data-sanitize-number="true")
                document.querySelectorAll('form').forEach(function (form) {
                    // guard: don't attach multiple listeners if script re-run
                    if (form.__gfid_number_sanitizer_attached) return;
                    form.__gfid_number_sanitizer_attached = true;

                    form.addEventListener('submit', function () {
                        form.querySelectorAll('.gfid-number-input').forEach(function (inp) {
                            if (!inp) return;
                            let v = inp.value ?? '';
                            v = String(v).trim();

                            // If empty, set to '0' (so DB get numeric)
                            if (v === '') {
                                inp.value = '0';
                                return;
                            }

                            // Remove spaces
                            v = v.replace(/\s+/g, '');

                            // Remove thousand separators commonly '.' (e.g. "1.234.567" -> "1234567")
                            // and also remove any non-digit except comma and dot
                            // First remove dots (common thousand separator)
                            v = v.replace(/[.]/g, '');

                            // Replace comma with dot for decimal normalization
                            v = v.replace(/,/g, '.');

                            // If field name contains 'qty' => treat as integer: strip decimal part
                            const name = (inp.getAttribute('name') || '').toLowerCase();
                            const step = inp.getAttribute('step') || '';
                            const inputmode = (inp.getAttribute('inputmode') || '').toLowerCase();

                            const looksLikeQty = name.includes('qty') || step === '1' || inputmode === 'numeric';

                            if (looksLikeQty) {
                                // if there is decimal dot, take left part only
                                if (v.indexOf('.') !== -1) {
                                    v = v.split('.')[0];
                                }
                                // keep only digits (remove any non-digit leftover)
                                v = v.replace(/\D+/g, '');
                                if (v === '') v = '0';
                                inp.value = v;
                            } else {
                                // For decimal-capable fields: keep numeric form with dot as decimal separator
                                // Remove all characters except digits and dot and minus
                                // Allow at most one dot
                                let cleaned = v.replace(/[^0-9.\-]/g, '');
                                const parts = cleaned.split('.');
                                if (parts.length > 2) {
                                    // join extras as decimal tail
                                    cleaned = parts.shift() + '.' + parts.join('');
                                }
                                if (cleaned === '' || cleaned === '.' || cleaned === '-' ) cleaned = '0';
                                inp.value = cleaned;
                            }
                        });
                    });
                });
            })();
        </script>
    @endpush
@endonce
