<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class StorefrontSetting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group', 'label'];

    // ── Cache key ──────────────────────────────────────────────────────────
    public const CACHE_KEY = 'storefront_settings_all';
    public const CACHE_TTL = 600; // 10 menit

    // ── Static helpers ────────────────────────────────────────────────────

    /**
     * Ambil satu setting berdasarkan key.
     * Menggunakan in-request static cache agar tidak query berulang.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return static::allCached()[$key] ?? $default;
    }

    /**
     * Simpan atau update satu setting.
     * Otomatis bust cache setelah disimpan.
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        static::bustCache();
    }

    /**
     * Simpan banyak setting sekaligus (mass update dari form).
     * $data = ['key' => 'value', ...]
     */
    public static function setMany(array $data): void
    {
        foreach ($data as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        static::bustCache();
    }

    /**
     * Semua setting sebagai key → value array, di-cache.
     */
    public static function allCached(): array
    {
        return Cache::remember(static::CACHE_KEY, static::CACHE_TTL, function () {
            try {
                return static::all()->pluck('value', 'key')->toArray();
            } catch (\Throwable) {
                return [];
            }
        });
    }

    /**
     * Hapus cache settings agar data segar terbaca.
     */
    public static function bustCache(): void
    {
        Cache::forget(static::CACHE_KEY);
    }

    /**
     * Daftar semua setting key yang dikenal (sync dengan seeder).
     * Digunakan controller update() agar tidak perlu DB query untuk whitelist.
     */
    public static function knownKeys(): array
    {
        return [
            // Branding
            'branding.brand_name', 'branding.tagline', 'branding.logo_url', 'branding.whatsapp_number',
            // Colors
            'colors.ink', 'colors.accent', 'colors.mid', 'colors.soft', 'colors.line',
            // Hero images — daftar dinamis JSON [{url, focus}], bisa tambah/hapus
            'hero.images',
            // Legacy (fallback)
            'hero.image_1', 'hero.image_2', 'hero.image_3',
            'hero.mobile_image_1', 'hero.mobile_image_2', 'hero.mobile_image_3',
            // Titik fokus per foto (format "x% y%", di-set dengan klik pada foto)
            'hero.image_1_focus', 'hero.image_2_focus', 'hero.image_3_focus',
            'hero.mobile_image_1_focus', 'hero.mobile_image_2_focus', 'hero.mobile_image_3_focus',
            // Hero style
            'hero.style', 'hero.overlay_color', 'hero.overlay_strength',
            'hero.photo_fit', 'hero.photo_focus', 'hero.height',
            // Hero — gaya teks & tombol (warna, ukuran, bentuk)
            'hero.label_color', 'hero.title_color', 'hero.title_size', 'hero.title_style', 'hero.copy_color',
            'hero.badge_bg', 'hero.badge_color',
            'hero.cta_bg', 'hero.cta_color', 'hero.cta2_color', 'hero.cta_radius',
            // Hero content
            'hero.label', 'hero.title_line1', 'hero.title_line2', 'hero.copy',
            'hero.cta_primary_label', 'hero.cta_primary_url',
            'hero.cta_secondary_label', 'hero.cta_secondary_url',
            'hero.badge_text', 'hero.card_title', 'hero.card_subtitle',
            // Values
            'values.1_number', 'values.1_title', 'values.1_desc',
            'values.2_number', 'values.2_title', 'values.2_desc',
            'values.3_number', 'values.3_title', 'values.3_desc',
            // Categories
            'categories.eyebrow', 'categories.title', 'categories.copy',
            'categories.all_label', 'categories.limit',
            // Channels
            'channels.shopee_url', 'channels.tokopedia_url', 'channels.tiktok_url',
            // Sections
            'sections.order',
            'sections.hero_visible', 'sections.categories_visible', 'sections.channels_visible',
            'sections.values_visible', 'sections.products_visible', 'sections.cta_visible',
            'sections.categories_padding_top', 'sections.categories_padding_bottom',
            'sections.categories_margin_top', 'sections.categories_margin_bottom',
            'sections.categories_bg', 'sections.categories_style',
            'sections.channels_padding_top', 'sections.channels_padding_bottom',
            'sections.channels_margin_top', 'sections.channels_margin_bottom',
            'sections.channels_bg', 'sections.channels_style',
            'sections.values_padding_top', 'sections.values_padding_bottom',
            'sections.values_margin_top', 'sections.values_margin_bottom',
            'sections.values_bg', 'sections.values_style',
            'sections.products_padding_top', 'sections.products_padding_bottom',
            'sections.products_margin_top', 'sections.products_margin_bottom',
            'sections.products_bg', 'sections.products_style',
            'sections.cta_padding_top', 'sections.cta_padding_bottom',
            'sections.cta_margin_top', 'sections.cta_margin_bottom',
            'sections.cta_bg', 'sections.cta_style',
            // Footer
            'footer.tagline', 'footer.copyright', 'footer.made_in',
            'footer.instagram_url', 'footer.email', 'footer.address',
            // Checkout — pembayaran
            'checkout.account_name',
            'checkout.bca_no', 'checkout.bri_no', 'checkout.mandiri_no',
            'checkout.qris_image',
            'checkout.pay_qris', 'checkout.pay_gopay', 'checkout.pay_dana', 'checkout.pay_ovo',
            'checkout.pay_shopeepay', 'checkout.pay_bca', 'checkout.pay_bri', 'checkout.pay_mandiri',
            // Checkout — lainnya
            'checkout.weight_per_item', 'checkout.secure_notice',
        ];
    }
}
