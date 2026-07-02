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

if (!function_exists('storefront_img')) {
    /**
     * Resolve a storefront product image URL.
     * Accepts full URLs (Unsplash, CDN) or local asset paths.
     */
    function storefront_img(string $img): string
    {
        return str_starts_with($img, 'http') ? $img : asset($img);
    }
}

if (!function_exists('storefrontProducts')) {
    function storefrontProducts(): array
    {
        try {
            return \App\Models\StorefrontProduct::where('is_published', true)
                ->with([
                    'category',
                    'variants' => fn($q) => $q->where('is_active', true)->with(['itemMappings.item.inventoryStocks.warehouse'])->orderBy('sort_order'),
                    'sizes'    => fn($q) => $q->where('is_active', true)->orderBy('sort_order'),
                    'variantItemMappings.variant',
                    'variantItemMappings.size',
                    'variantItemMappings.item.inventoryStocks.warehouse',
                ])
                // Produk dengan rank_position tampil duluan (ranked products first),
                // sisa produk tanpa rank fallback ke sort_order lalu name.
                ->orderByRaw('rank_position IS NULL ASC')
                ->orderBy('rank_position')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(function ($p) {
                    // Variant default = foto utama
                    $default = $p->variants->firstWhere('is_default', true) ?? $p->variants->first();

                    $stockResolver = app(\App\Services\Storefront\StockResolver::class);
                    $availableStock = $stockResolver->forProduct($p);

                    // Badge signals
                    $ageInDays   = (int) now()->diffInDays($p->created_at);
                    $isNewProduct = $ageInDays < 14;
                    $stockStatus = match (true) {
                        $availableStock === 0 => 'out',
                        $availableStock <= 4  => 'low',
                        default               => 'ok',
                    };

                    return [
                        'slug'         => $p->slug,
                        'name'         => $p->name,
                        'price'        => $default?->price_override ?? $p->base_price,
                        'label'        => $p->label ?? '',
                        'product_type' => $p->product_type,
                        'img'          => $default?->getImageSrc() ?: $p->getImageSrc(),
                        'dark'         => false,
                        'sold'         => '',
                        'desc'         => $p->description ?? '',
                        'sizes'  => $p->sizes->pluck('size_label')->toArray(),
                        'colors' => $p->variants
                            ->groupBy(fn($v) => strtolower(trim((string) $v->color_name)))
                            ->map(fn($group) => $group->firstWhere('size_label', null) ?? $group->first())
                            ->map(fn($v) => [
                            'name' => $v->color_name,
                            'hex'  => $v->hex_color ?? '#888888',
                        ])->values()->toArray(),
                        // Kategori
                        'category_slug' => $p->category?->slug ?? '',
                        'category_name' => $p->category?->name ?? '',
                        // Audience
                        'audience'       => $p->audience ?? '',
                        'audience_label' => $p->audience_label,
                        // Ranking
                        'rank_position'   => $p->rank_position,
                        'rank_score'      => $p->rank_score,
                        'available_stock' => $availableStock,
                        'is_pinned'       => (bool) $p->is_pinned,
                        // Badge signals
                        'is_new_product' => $isNewProduct,
                        'stock_status'   => $stockStatus,
                        // Data tambahan untuk image swap, price update & cart
                        '_base_price' => $p->base_price,
                        '_variants'   => $p->variants->map(fn($v) => [
                            'id'             => $v->id,
                            'name'           => $v->color_name,
                            'size_label'     => $v->size_label,
                            'img'            => $v->getImageSrc(),
                            'price_override' => $v->price_override,
                            'stock'          => $stockResolver->forVariant($v),
                            'item_id'        => $v->item_id,
                            'item_code'      => $v->item?->code,
                            'is_default'     => $v->is_default,
                        ])->values()->toArray(),
                        '_variant_items' => $p->variantItemMappings->map(fn($m) => [
                            'variant_id'     => $m->variant_id,
                            'size_id'        => $m->size_id,
                            'color'          => $m->variant?->color_name,
                            'size'           => $m->size?->size_label,
                            'price_override' => $m->price_override,
                            'stock'          => $stockResolver->forMapping($m),
                            'item_id'        => $m->item_id,
                            'item_code'      => $m->item?->code,
                        ])->values()->toArray(),
                        '_sizes' => $p->sizes->map(fn($s) => [
                            'label'          => $s->size_label,
                            'price_override' => $s->price_override,
                        ])->values()->toArray(),
                    ];
                })
                ->toArray();
        } catch (\Throwable $e) {
            // Fallback kalau tabel belum ada (sebelum migrate)
            return [];
        }
    }
}

if (!function_exists('storefrontChannels')) {
    function storefrontChannels(): array
    {
        return [
            ['label' => 'Website',   'url' => '#', 'dark' => true],
            ['label' => 'Shopee',    'url' => '#', 'dark' => false],
            ['label' => 'TikTok',    'url' => '#', 'dark' => false],
            ['label' => 'Tokopedia', 'url' => '#', 'dark' => false],
        ];
    }
}
