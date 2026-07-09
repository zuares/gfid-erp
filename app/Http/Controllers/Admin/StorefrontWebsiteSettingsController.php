<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StorefrontSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StorefrontWebsiteSettingsController extends Controller
{
    private function sectionDefinitions(): array
    {
        return [
            'hero'       => ['icon' => '🦸', 'name' => 'Hero',          'desc' => 'Gambar utama, judul, dan CTA'],
            'categories' => ['icon' => '🏷️', 'name' => 'Kategori',      'desc' => 'Grid pilih kategori produk'],
            'channels'   => ['icon' => '🛒', 'name' => 'Channels',      'desc' => 'Shopee, TikTok, Tokopedia, Website'],
            'values'     => ['icon' => '✨', 'name' => 'Values',         'desc' => 'Keunggulan brand'],
            'products'   => ['icon' => '👕', 'name' => 'Produk Pilihan', 'desc' => 'Grid produk ranked / featured'],
            'cta'        => ['icon' => '🎯', 'name' => 'Call to Action', 'desc' => 'Blok ajakan belanja'],
        ];
    }

    // ── Halaman settings ────────────────────────────────────────────────────

    public function index(): View
    {
        $settings = StorefrontSetting::allCached();
        return view('admin.website.settings', compact('settings'));
    }

    public function editSection(string $section): View
    {
        $sections = $this->sectionDefinitions();
        abort_unless(array_key_exists($section, $sections), 404);

        $settings = StorefrontSetting::allCached();
        $meta = $sections[$section];

        return view('admin.website.section-edit', compact('section', 'sections', 'settings', 'meta'));
    }

    public function updateSection(Request $request, string $section): RedirectResponse
    {
        $sections = $this->sectionDefinitions();
        abort_unless(array_key_exists($section, $sections), 404);

        $data = [
            "sections.{$section}_visible" => $request->boolean('visible') ? '1' : '0',
        ];

        if ($section !== 'hero') {
            foreach (['padding_top', 'padding_bottom', 'margin_top', 'margin_bottom'] as $field) {
                $data["sections.{$section}_{$field}"] = (string) max(0, min(120, (int) $request->input($field, 0)));
            }

            $bg = (string) $request->input('bg', '#ffffff');
            $data["sections.{$section}_bg"] = preg_match('/^#[0-9a-fA-F]{6}$/', $bg) ? $bg : '#ffffff';
            $style = (string) $request->input('style', 'default');
            $allowedStyles = ['default', 'soft', 'line', 'compact', 'outline', 'elevated', 'dark', 'editorial'];
            $data["sections.{$section}_style"] = in_array($style, $allowedStyles, true) ? $style : 'default';
        }

        if ($section === 'channels') {
            $data['channels.list'] = (string) $request->input('channels_list', '[]');
            $data['channels.shopee_url'] = (string) $request->input('channels_shopee_url', '');
            $data['channels.tokopedia_url'] = (string) $request->input('channels_tokopedia_url', '');
            $data['channels.tiktok_url'] = (string) $request->input('channels_tiktok_url', '');
        }

        if ($section === 'categories') {
            $data['categories.eyebrow'] = (string) $request->input('categories_eyebrow', '');
            $data['categories.title'] = (string) $request->input('categories_title', '');
            $data['categories.copy'] = (string) $request->input('categories_copy', '');
            $data['categories.all_label'] = (string) $request->input('categories_all_label', '');
            $limit = (int) $request->input('categories_limit', 8);
            $data['categories.limit'] = (string) (in_array($limit, [4, 6, 8, 10, 12], true) ? $limit : 8);
        }

        if ($section === 'values') {
            foreach ([1, 2, 3] as $i) {
                $data["values.{$i}_number"] = (string) $request->input("values_{$i}_number", '');
                $data["values.{$i}_title"] = (string) $request->input("values_{$i}_title", '');
                $data["values.{$i}_desc"] = (string) $request->input("values_{$i}_desc", '');
            }
        }

        StorefrontSetting::setMany($data);

        return redirect()
            ->route('admin.website.settings.sections.edit', $section)
            ->with('success', 'Pengaturan section berhasil disimpan.');
    }

    // ── Simpan semua setting (satu form per group) ───────────────────────────

    public function update(Request $request): RedirectResponse
    {
        $raw = $request->except(['_token', '_method']);

        // Gunakan knownKeys() — tidak perlu query DB, aman meski seeder belum dijalankan.
        // PHP mengkonversi titik (.) di nama field menjadi underscore (_).
        // Contoh: name="branding.brand_name" → $_POST key = "branding_brand_name"
        // Reverse-map: untuk setiap key yang dikenal, cari PHP-mangled version-nya di $raw.
        $filtered = [];
        foreach (StorefrontSetting::knownKeys() as $settingKey) {
            $phpKey = str_replace('.', '_', $settingKey);
            if (array_key_exists($phpKey, $raw)) {
                $filtered[$settingKey] = $raw[$phpKey] ?? '';
            }
        }

        try {
            StorefrontSetting::setMany($filtered);
        } catch (\Throwable $e) {
            report($e);

            $message = str_contains($e->getMessage(), 'no such table')
                ? 'Gagal simpan — tabel belum ada. Jalankan: php artisan migrate'
                : 'Gagal menyimpan pengaturan: ' . $e->getMessage();

            return redirect()
                ->route('admin.website.settings')
                ->with('error', $message);
        }

        return redirect()
            ->route('admin.website.settings')
            ->with('success', 'Pengaturan website berhasil disimpan.');
    }

    // ── Upload gambar (logo / hero images) ─────────────────────────────────────

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'image', 'max:8192'],
        ]);

        $file    = $request->file('file');
        $mime    = $file->getMimeType();
        $tmpPath = $file->getRealPath();

        // Build GD source
        $src = match (true) {
            str_contains($mime, 'jpeg') => imagecreatefromjpeg($tmpPath),
            str_contains($mime, 'png')  => imagecreatefrompng($tmpPath),
            str_contains($mime, 'webp') => imagecreatefromwebp($tmpPath),
            str_contains($mime, 'gif')  => imagecreatefromgif($tmpPath),
            default                     => null,
        };

        if (! $src) {
            // Fallback: simpan as-is (mis. SVG)
            // URL RELATIF agar tetap jalan diakses dari host/IP mana pun (mis. HP via LAN)
            $path = $file->store('website-assets', 'public');
            return response()->json(['url' => '/storage/' . $path]);
        }

        // Resize kalau lebar > 1600px
        $origW = imagesx($src);
        $origH = imagesy($src);
        $maxW  = 1600;

        if ($origW > $maxW) {
            $newW = $maxW;
            $newH = (int) round($origH * $maxW / $origW);
            $dst  = imagecreatetruecolor($newW, $newH);

            if (str_contains($mime, 'png')) {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
            }

            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
            imagedestroy($src);
            $src = $dst;
        }

        // PNG dipertahankan sebagai PNG agar transparansi (alpha) tidak hilang.
        // Format lain disimpan sebagai JPEG quality 82.
        $isPng = str_contains($mime, 'png');

        if ($isPng) {
            imagealphablending($src, false);
            imagesavealpha($src, true);
            $tmpOut = tempnam(sys_get_temp_dir(), 'wsimg_') . '.png';
            imagepng($src, $tmpOut, 8);
            $ext = 'png';
        } else {
            $tmpOut = tempnam(sys_get_temp_dir(), 'wsimg_') . '.jpg';
            imagejpeg($src, $tmpOut, 82);
            $ext = 'jpg';
        }
        imagedestroy($src);

        $filename = 'website-assets/' . uniqid('img_', true) . '.' . $ext;
        \Storage::disk('public')->put($filename, file_get_contents($tmpOut));
        @unlink($tmpOut);

        // URL RELATIF agar tetap jalan diakses dari host/IP mana pun (mis. HP via LAN)
        return response()->json(['url' => '/storage/' . $filename]);
    }
}
