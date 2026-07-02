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
            // Hero images
            'hero.image_1', 'hero.image_2', 'hero.image_3',
            // Hero content
            'hero.label', 'hero.title_line1', 'hero.title_line2', 'hero.copy',
            'hero.cta_primary_label', 'hero.cta_primary_url',
            'hero.cta_secondary_label', 'hero.cta_secondary_url',
            'hero.badge_text', 'hero.card_title', 'hero.card_subtitle',
            // Values
            'values.1_number', 'values.1_title', 'values.1_desc',
            'values.2_number', 'values.2_title', 'values.2_desc',
            'values.3_number', 'values.3_title', 'values.3_desc',
            // Channels
            'channels.shopee_url', 'channels.tokopedia_url', 'channels.tiktok_url',
            // Sections
            'sections.order',
            'sections.hero_visible', 'sections.categories_visible', 'sections.channels_visible',
            'sections.values_visible', 'sections.products_visible', 'sections.cta_visible',
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
