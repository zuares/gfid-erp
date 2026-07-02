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
    // ── Halaman settings ────────────────────────────────────────────────────

    public function index(): View
    {
        $settings = StorefrontSetting::allCached();
        return view('admin.website.settings', compact('settings'));
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
            $path = $file->store('website-assets', 'public');
            return response()->json(['url' => asset('storage/' . $path)]);
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

        return response()->json(['url' => asset('storage/' . $filename)]);
    }
}
