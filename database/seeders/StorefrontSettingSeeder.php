<?php

namespace Database\Seeders;

use App\Models\StorefrontSetting;
use Illuminate\Database\Seeder;

class StorefrontSettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [

            /* ── BRANDING ───────────────────────────────────────────── */
            ['key' => 'branding.brand_name',      'type' => 'text',  'group' => 'branding', 'label' => 'Nama Brand',          'value' => 'Greatfit'],
            ['key' => 'branding.tagline',         'type' => 'text',  'group' => 'branding', 'label' => 'Tagline',             'value' => 'Hal kecil yang bikin hari terasa lebih nyaman.'],
            ['key' => 'branding.logo_url',        'type' => 'image', 'group' => 'branding', 'label' => 'Logo URL',            'value' => '/images/logo-mark.svg'],
            ['key' => 'branding.whatsapp_number', 'type' => 'text',  'group' => 'branding', 'label' => 'No. WhatsApp',        'value' => ''],

            /* ── COLORS ─────────────────────────────────────────────── */
            ['key' => 'colors.ink',    'type' => 'color', 'group' => 'colors', 'label' => 'Ink (teks utama)',       'value' => '#0a0a0a'],
            ['key' => 'colors.accent', 'type' => 'color', 'group' => 'colors', 'label' => 'Accent (highlight)',     'value' => '#E8FF00'],
            ['key' => 'colors.mid',    'type' => 'color', 'group' => 'colors', 'label' => 'Mid (teks sekunder)',    'value' => '#888888'],
            ['key' => 'colors.soft',   'type' => 'color', 'group' => 'colors', 'label' => 'Soft (background card)', 'value' => '#f4f4f4'],
            ['key' => 'colors.line',   'type' => 'color', 'group' => 'colors', 'label' => 'Line (border)',          'value' => '#e8e8e8'],

            /* ── HERO IMAGES ────────────────────────────────────────── */
            ['key' => 'hero.image_1', 'type' => 'image', 'group' => 'hero', 'label' => 'Foto Hero 1', 'value' => 'https://images.unsplash.com/photo-1660167213901-e2f33a1a7486?ixlib=rb-4.1.0&q=85&fm=jpg&crop=entropy&cs=srgb&w=1000&h=1200&fit=crop'],
            ['key' => 'hero.image_2', 'type' => 'image', 'group' => 'hero', 'label' => 'Foto Hero 2', 'value' => 'https://images.unsplash.com/photo-1756786825067-4b153740e7c2?ixlib=rb-4.1.0&q=85&fm=jpg&crop=entropy&cs=srgb&w=1000&h=1200&fit=crop'],
            ['key' => 'hero.image_3', 'type' => 'image', 'group' => 'hero', 'label' => 'Foto Hero 3', 'value' => 'https://images.unsplash.com/photo-1774160928808-afdd9b93363b?ixlib=rb-4.1.0&q=85&fm=jpg&crop=entropy&cs=srgb&w=1000&h=1200&fit=crop'],

            /* ── HERO ───────────────────────────────────────────────── */
            ['key' => 'hero.label',                'type' => 'text',  'group' => 'hero', 'label' => 'Label Kecil',              'value' => 'Koleksi Terbaru'],
            ['key' => 'hero.title_line1',          'type' => 'text',  'group' => 'hero', 'label' => 'Judul Baris 1',            'value' => 'Good Fit,'],
            ['key' => 'hero.title_line2',          'type' => 'text',  'group' => 'hero', 'label' => 'Judul Baris 2',            'value' => 'Good Feel.'],
            ['key' => 'hero.copy',                 'type' => 'textarea', 'group' => 'hero', 'label' => 'Deskripsi Hero',        'value' => 'Hal kecil yang bikin hari terasa lebih nyaman.'],
            ['key' => 'hero.cta_primary_label',    'type' => 'text',  'group' => 'hero', 'label' => 'CTA Utama – Label',        'value' => 'Lihat Koleksi'],
            ['key' => 'hero.cta_primary_url',      'type' => 'url',   'group' => 'hero', 'label' => 'CTA Utama – URL',          'value' => '/products'],
            ['key' => 'hero.cta_secondary_label',  'type' => 'text',  'group' => 'hero', 'label' => 'CTA Sekunder – Label',     'value' => 'Cara Order'],
            ['key' => 'hero.cta_secondary_url',    'type' => 'url',   'group' => 'hero', 'label' => 'CTA Sekunder – URL',       'value' => '#cara-order'],
            ['key' => 'hero.badge_text',           'type' => 'text',  'group' => 'hero', 'label' => 'Badge / Pill Teks',        'value' => '⚡ Pengiriman Hari Ini'],
            ['key' => 'hero.card_title',           'type' => 'text',  'group' => 'hero', 'label' => 'Kartu Hero – Judul',       'value' => 'Greatfit Club'],
            ['key' => 'hero.card_subtitle',        'type' => 'text',  'group' => 'hero', 'label' => 'Kartu Hero – Subjudul',    'value' => '10rb+ pelanggan puas'],

            /* ── VALUES ─────────────────────────────────────────────── */
            ['key' => 'values.1_number', 'type' => 'text', 'group' => 'values', 'label' => 'Value 1 – Angka',  'value' => '01'],
            ['key' => 'values.1_title',  'type' => 'text', 'group' => 'values', 'label' => 'Value 1 – Judul',  'value' => 'Nyaman'],
            ['key' => 'values.1_desc',   'type' => 'textarea', 'group' => 'values', 'label' => 'Value 1 – Deskripsi', 'value' => 'Bahan ringan dan breathable yang bikin kamu betah seharian.'],

            ['key' => 'values.2_number', 'type' => 'text', 'group' => 'values', 'label' => 'Value 2 – Angka',  'value' => '02'],
            ['key' => 'values.2_title',  'type' => 'text', 'group' => 'values', 'label' => 'Value 2 – Judul',  'value' => 'Presisi'],
            ['key' => 'values.2_desc',   'type' => 'textarea', 'group' => 'values', 'label' => 'Value 2 – Deskripsi', 'value' => 'Ukuran konsisten. Cocok di badan, pas di ekspektasi.'],

            ['key' => 'values.3_number', 'type' => 'text', 'group' => 'values', 'label' => 'Value 3 – Angka',  'value' => '03'],
            ['key' => 'values.3_title',  'type' => 'text', 'group' => 'values', 'label' => 'Value 3 – Judul',  'value' => 'Tahan Lama'],
            ['key' => 'values.3_desc',   'type' => 'textarea', 'group' => 'values', 'label' => 'Value 3 – Deskripsi', 'value' => 'Jahitan kuat, warna awet — menemani aktivitas sehari-hari.'],

            /* ── CHANNELS ───────────────────────────────────────────── */
            ['key' => 'channels.shopee_url',    'type' => 'url', 'group' => 'channels', 'label' => 'URL Shopee',    'value' => '#'],
            ['key' => 'channels.tokopedia_url', 'type' => 'url', 'group' => 'channels', 'label' => 'URL Tokopedia', 'value' => '#'],
            ['key' => 'channels.tiktok_url',    'type' => 'url', 'group' => 'channels', 'label' => 'URL TikTok',    'value' => '#'],

            /* ── SECTIONS ───────────────────────────────────────────── */
            ['key' => 'sections.order',              'type' => 'text',    'group' => 'sections', 'label' => 'Urutan Sections',    'value' => 'hero,categories,channels,values,products,cta'],
            ['key' => 'sections.hero_visible',       'type' => 'boolean', 'group' => 'sections', 'label' => 'Hero',               'value' => '1'],
            ['key' => 'sections.categories_visible', 'type' => 'boolean', 'group' => 'sections', 'label' => 'Kategori',           'value' => '1'],
            ['key' => 'sections.channels_visible',   'type' => 'boolean', 'group' => 'sections', 'label' => 'Channels',           'value' => '1'],
            ['key' => 'sections.values_visible',     'type' => 'boolean', 'group' => 'sections', 'label' => 'Values',             'value' => '1'],
            ['key' => 'sections.products_visible',   'type' => 'boolean', 'group' => 'sections', 'label' => 'Produk Pilihan',     'value' => '1'],
            ['key' => 'sections.cta_visible',        'type' => 'boolean', 'group' => 'sections', 'label' => 'Call to Action',     'value' => '1'],

            /* ── FOOTER ─────────────────────────────────────────────── */
            ['key' => 'footer.tagline',       'type' => 'textarea', 'group' => 'footer', 'label' => 'Tagline Footer',     'value' => 'Hal kecil yang bikin hari terasa lebih nyaman, lewat outfit harian Greatfit.'],
            ['key' => 'footer.copyright',     'type' => 'text',     'group' => 'footer', 'label' => 'Copyright',          'value' => '© 2025 Greatfit. All rights reserved.'],
            ['key' => 'footer.made_in',       'type' => 'text',     'group' => 'footer', 'label' => 'Made In',            'value' => 'Dibuat dengan ❤️ di Indonesia'],
            ['key' => 'footer.instagram_url', 'type' => 'url',      'group' => 'footer', 'label' => 'URL Instagram',      'value' => '#'],
            ['key' => 'footer.email',         'type' => 'text',     'group' => 'footer', 'label' => 'Email Kontak',       'value' => ''],
            ['key' => 'footer.address',       'type' => 'textarea', 'group' => 'footer', 'label' => 'Alamat',             'value' => ''],
        ];

        foreach ($defaults as $row) {
            StorefrontSetting::firstOrCreate(
                ['key' => $row['key']],
                [
                    'value' => $row['value'],
                    'type'  => $row['type'],
                    'group' => $row['group'],
                    'label' => $row['label'],
                ]
            );
        }
    }
}
