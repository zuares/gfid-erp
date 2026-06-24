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

if (!function_exists('po_order_type_label')) {
    /**
     * Kembalikan label user-friendly untuk order_type Purchase Order.
     * @param string|null $type
     * @param bool $withIcon  Sertakan emoji di depan label
     */
    function po_order_type_label(?string $type, bool $withIcon = false): string
    {
        $map = [
            'material'     => ['label' => 'Bahan Produksi',  'icon' => '🧵'],
            'finished_good'=> ['label' => 'Barang Jadi', 'icon' => '👕'],
            'packing'      => ['label' => 'Packaging',     'icon' => '📦'],
            'asset'        => ['label' => 'Aset',        'icon' => '🏭'],
            'service'      => ['label' => 'Operasional',     'icon' => '🔧'],
            'jasa'         => ['label' => 'Jasa',        'icon' => '🤝'],
            'lainnya'      => ['label' => 'Lainnya',     'icon' => '📋'],
        ];
        $entry = $map[$type ?? ''] ?? ['label' => ucfirst((string) $type ?: '—'), 'icon' => '📄'];
        return $withIcon ? $entry['icon'] . ' ' . $entry['label'] : $entry['label'];
    }
}

if (!function_exists('received_status_label')) {
    /**
     * Label user-friendly untuk received_status di Purchase Order.
     */
    function received_status_label(?string $status): string
    {
        return match ($status) {
            'not_received'  => 'Belum Diterima',
            'partial'       => 'Sebagian',
            'fully_received'=> 'Lengkap',
            default         => '—',
        };
    }
}

if (!function_exists('to_num')) {
    /**
     * Convert string angka format ID/EN -> float.
     * Support:
     * - "1.234,56" => 1234.56
     * - "1.234"    => 1234
     * - "1234,56"  => 1234.56
     * - "1234.56"  => 1234.56
     * - null / ""  => 0
     */
    function to_num($value): float
    {
        if ($value === null) {
            return 0.0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $v = trim((string) $value);
        if ($v === '') {
            return 0.0;
        }

        // buang spasi
        $v = preg_replace('/\s+/', '', $v);

        // kalau ada koma, anggap koma = desimal (ID style)
        if (strpos($v, ',') !== false) {
            $v = str_replace('.', '', $v); // ribuan
            $v = str_replace(',', '.', $v); // desimal
            return (float) $v;
        }

        // kalau pola ribuan pakai titik (1.234.567)
        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $v)) {
            $v = str_replace('.', '', $v);
            return (float) $v;
        }

        // default (1234.56 atau 1234)
        return (float) $v;
    }
}

if (!function_exists('pr_status_label')) {
    /**
     * Label user-friendly untuk status Purchase Request.
     */
    function pr_status_label(?string $status): string
    {
        return match ($status) {
            'draft'     => 'Draft',
            'approved'  => 'Approved',
            'rejected'  => 'Ditolak',
            'converted' => 'Converted',
            'cancelled' => 'Dibatalkan',
            default     => $status ?? '—',
        };
    }
}
