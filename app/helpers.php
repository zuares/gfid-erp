<?php
// Cara Penggunaaan di Blade Template:
// @rupiah($value)
// @decimal($value)

if (!function_exists('rupiah')) {
    function rupiah($value, $decimal = 0)
    {
        return 'Rp ' . number_format($value, $decimal, ',', '.');
    }
}

if (!function_exists('decimal_id')) {
    function decimal_id($value, $decimal = 2)
    {
        return number_format($value, $decimal, ',', '.');
    }
}

if (!function_exists('angka')) {
    function angka($value, $decimal = 0)
    {
        return number_format($value, $decimal, ',', '.');
    }
}

if (!function_exists('toNumber')) {
    /**
     * Parse angka format Indonesia / campuran ke float.
     *
     * Contoh:
     *  - "1.234,56" -> 1234.56
     *  - "24,00"    -> 24.00
     *  - "1.234"    -> 1234
     *  - "1234.56"  -> 1234.56
     *  - null / ''  -> 0.0
     */

    if (!function_exists('num_id')) {
        function num_id($value): float
        {
            if ($value === null || $value === '') {
                return 0.0;
            }

            // Kalau sudah numeric (hasil validasi / cast Laravel), langsung saja
            if (is_int($value) || is_float($value)) {
                return (float) $value;
            }

            // Pastikan string
            $value = trim((string) $value);
            $value = str_replace(' ', '', $value);

            // Kalau ada koma → anggap format Indonesia: "1.234,56" / "24,00"
            if (strpos($value, ',') !== false) {
                // Hilangkan titik ribuan
                $value = str_replace('.', '', $value);
                // Ganti koma jadi titik desimal
                $value = str_replace(',', '.', $value);
                return (float) $value;
            }

            // Kalau tidak ada koma, tapi pola ribuan: "1.234" atau "1.234.567"
            if (preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
                $value = str_replace('.', '', $value);
                return (float) $value;
            }

            // Default: biarkan Laravel terjemahkan (mis. "1234.56")
            return (float) $value;
        }
    }

}
