@props([
    // name wajib
    'name',

    // optional
    'id' => null,
    'value' => null,

    // HTML type: "number" atau "text"
    // - "number"  → enak buat qty sederhana
    // - "text"    → enak buat angka dengan pemisah ribuan (1.000, dst)
    'htmlType' => 'number',

    'step' => '0.01',
    'min' => '0',
    'max' => null,

    // "numeric" → keyboard angka tanpa koma, "decimal" → angka + koma/titik
    'inputmode' => 'decimal',

    // ukuran Bootstrap: sm / md / lg
    'size' => 'sm',

    // alignment: start / center / end
    'align' => 'end',

    // tambahan class custom
    'class' => '',
])

@php
    $inputId = $id ?? str_replace(['[', ']'], '_', $name); // id aman dari [] array
    $sizeClass = $size ? 'form-control-' . $size : '';
    $alignClass = $align ? 'text-' . $align : '';
@endphp

<input id="{{ $inputId }}" name="{{ $name }}" type="{{ $htmlType }}"
    @if ($htmlType === 'number') step="{{ $step }}"
        min="{{ $min }}"
        @if (!is_null($max)) max="{{ $max }}" @endif
    @endif

{{-- Biar di mobile muncul keyboard angka --}}
inputmode="{{ $inputmode }}"
@if ($inputmode === 'numeric') pattern="[0-9]*" @endif

autocomplete="off"
class="form-control {{ $sizeClass }} {{ $alignClass }} gfid-number-input {{ $class }}"
value="{{ old($name, $value) }}"
/>

{{-- JS hanya disuntik sekali untuk semua number input ini --}}
@once
    @push('scripts')
        <script>
            (function() {
                // Auto select saat fokus
                document.addEventListener(
                    'focus',
                    function(e) {
                        const el = e.target;
                        if (!el.classList || !el.classList.contains('gfid-number-input')) return;

                        // delay sedikit biar select() tidak keganggu event lain
                        setTimeout(function() {
                            try {
                                el.select();
                            } catch (_) {}
                        }, 0);
                    },
                    true // pakai capture biar kena event focus
                );

                // Klik kedua dsb tetap select isi
                document.addEventListener('click', function(e) {
                    const el = e.target.closest('.gfid-number-input');
                    if (!el) return;

                    // kalau sudah fokus, tetap paksa select
                    if (document.activeElement === el) {
                        setTimeout(function() {
                            try {
                                el.select();
                            } catch (_) {}
                        }, 0);
                    }
                });
            })
            ();
        </script>
    @endpush
@endonce
