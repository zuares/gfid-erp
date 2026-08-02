<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StorefrontSetting;
use App\Models\SystemSetting;
use App\Models\ShipmentScanRingtone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class StorefrontWebsiteSettingsController extends Controller
{
    private const SCAN_SOUND_BUILTINS = [
        'ok' => 'OK standar',
        'item' => 'Item berhasil',
        'order' => 'Order baru',
        'orderRepeat' => 'Order duplikat',
        'next' => 'Pindah alur',
        'undo' => 'Undo',
        'reset' => 'Reset',
        'errorGuard' => 'Peringatan / duplikat',
        'errorNoOrder' => 'Order tidak ditemukan',
        'errorNetwork' => 'Network error',
        'error' => 'Error umum',
        'orderReady' => 'Order siap',
        'orderPartial' => 'Order stok kurang',
        'orderNoMatch' => 'Order tidak match',
    ];

    private function scanSoundEvents(): array
    {
        return [
            ['key' => 'order_success', 'group' => 'Scan Order', 'label' => 'Order berhasil', 'help' => 'Nomor order baru berhasil dicatat.', 'default' => 'order'],
            ['key' => 'order_duplicate', 'group' => 'Scan Order', 'label' => 'Order duplikat', 'help' => 'Order sudah pernah dicatat.', 'default' => 'orderRepeat'],
            ['key' => 'order_not_found', 'group' => 'Scan Order', 'label' => 'Order tidak ditemukan', 'help' => 'Order tidak tersedia atau gagal dibaca.', 'default' => 'errorNoOrder'],
            ['key' => 'item_success', 'group' => 'Scan Item', 'label' => 'Item berhasil', 'help' => 'SKU berhasil discan atau ditambahkan.', 'default' => 'item'],
            ['key' => 'item_duplicate', 'group' => 'Scan Item', 'label' => 'Item duplikat / guard', 'help' => 'Scan item ditolak karena aturan workflow.', 'default' => 'errorGuard'],
            ['key' => 'navigation', 'group' => 'Alur & Koreksi', 'label' => 'Pindah alur / order baru', 'help' => 'Berpindah mode atau order berikutnya.', 'default' => 'next'],
            ['key' => 'undo', 'group' => 'Alur & Koreksi', 'label' => 'Undo', 'help' => 'Scan terakhir dibatalkan.', 'default' => 'undo'],
            ['key' => 'reset', 'group' => 'Alur & Koreksi', 'label' => 'Reset order', 'help' => 'Pencatatan order direset.', 'default' => 'reset'],
            ['key' => 'error_general', 'group' => 'Error', 'label' => 'Error umum', 'help' => 'Kesalahan validasi atau proses.', 'default' => 'error'],
            ['key' => 'error_network', 'group' => 'Error', 'label' => 'Network / server error', 'help' => 'Koneksi atau server gagal merespons.', 'default' => 'errorNetwork'],
            ['key' => 'order_ready', 'group' => 'Rekonsiliasi', 'label' => 'Order siap', 'help' => 'Order match dan stok mencukupi.', 'default' => 'orderReady'],
            ['key' => 'order_partial', 'group' => 'Rekonsiliasi', 'label' => 'Order stok kurang', 'help' => 'Order match tetapi stok tidak cukup.', 'default' => 'orderPartial'],
            ['key' => 'order_no_match', 'group' => 'Rekonsiliasi', 'label' => 'Order tidak match', 'help' => 'Order tidak cocok dengan batch.', 'default' => 'orderNoMatch'],
        ];
    }

    private function defaultScanSoundMap(): array
    {
        return collect($this->scanSoundEvents())
            ->mapWithKeys(fn (array $event) => [$event['key'] => 'builtin:' . $event['default']])
            ->all();
    }

    private function mediaBinary(string $name): string
    {
        $configured = trim((string) config('services.ffmpeg.' . ($name === 'ffprobe' ? 'ffprobe_binary' : 'binary'), ''));
        $finder = new ExecutableFinder();
        $candidates = array_values(array_unique(array_filter([
            $configured,
            $finder->find($name),
            '/opt/homebrew/bin/' . $name,
            '/usr/local/bin/' . $name,
            '/usr/bin/' . $name,
            '/bin/' . $name,
        ])));

        foreach ($candidates as $candidate) {
            $candidate = (string) $candidate;
            if (str_starts_with($candidate, '/') && is_executable($candidate)) {
                return $candidate;
            }

            $resolved = $finder->find($candidate);
            if ($resolved && is_executable($resolved)) {
                return $resolved;
            }
        }

        throw new \RuntimeException(strtoupper($name) . ' tidak ditemukan pada server.');
    }

    private function normalizedScanSoundMap(?iterable $ringtones = null): array
    {
        $allowedRingtones = collect($ringtones ?? ShipmentScanRingtone::query()->get())->keyBy('id');
        $saved = json_decode((string) SystemSetting::get(SystemSetting::KEY_SHIPMENT_SCAN_SOUND_MAP, '{}'), true);
        $saved = is_array($saved) ? $saved : [];
        $defaults = $this->defaultScanSoundMap();

        foreach ($defaults as $key => $fallback) {
            $value = (string) ($saved[$key] ?? $fallback);
            if (str_starts_with($value, 'builtin:')) {
                $builtin = substr($value, 7);
                $defaults[$key] = array_key_exists($builtin, self::SCAN_SOUND_BUILTINS)
                    ? $value
                    : $fallback;
                continue;
            }

            if (str_starts_with($value, 'ringtone:')) {
                $id = (int) substr($value, 9);
                $defaults[$key] = $allowedRingtones->has($id) ? $value : $fallback;
                continue;
            }

            $defaults[$key] = $fallback;
        }

        return $defaults;
    }

    private function persistShipmentScanSettings(Request $request): void
    {
        if ($request->has('shipment_scan_sound_enabled')) {
            SystemSetting::set(
                SystemSetting::KEY_SHIPMENT_SCAN_SOUND,
                $request->boolean('shipment_scan_sound_enabled') ? '1' : '0',
                'Default suara scan pengiriman',
                auth()->id()
            );
        }

        if (! $request->has('shipment_scan_sound_map')) {
            return;
        }

        $map = $request->input('shipment_scan_sound_map', []);
        $allowedRingtones = ShipmentScanRingtone::query()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $map = is_array($map) ? $map : [];
        $normalized = $this->defaultScanSoundMap();

        foreach (array_keys($normalized) as $key) {
            $value = (string) ($map[$key] ?? $normalized[$key]);
            if (str_starts_with($value, 'builtin:') && array_key_exists(substr($value, 7), self::SCAN_SOUND_BUILTINS)) {
                $normalized[$key] = $value;
            } elseif (str_starts_with($value, 'ringtone:') && in_array((int) substr($value, 9), $allowedRingtones, true)) {
                $normalized[$key] = $value;
            }
        }

        SystemSetting::set(
            SystemSetting::KEY_SHIPMENT_SCAN_SOUND_MAP,
            json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'Mapping suara event scan pengiriman',
            auth()->id()
        );
    }

    private function resetShipmentScanSettings(): void
    {
        SystemSetting::set(
            SystemSetting::KEY_SHIPMENT_SCAN_SOUND,
            '1',
            'Default suara scan pengiriman',
            auth()->id()
        );
        SystemSetting::set(
            SystemSetting::KEY_SHIPMENT_SCAN_SOUND_MAP,
            json_encode($this->defaultScanSoundMap(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'Mapping suara event scan pengiriman',
            auth()->id()
        );
    }

    private function defaultSalesOperationalSettings(): array
    {
        return [
            'lookup_mode' => 'record_only',
            'lookup_sources' => ['marketplace_order', 'sales_invoice'],
            'lookup_identifiers' => [
                'shipping_awb_no',
                'channel_order_id',
                'booking_sn',
                'external_order_id',
                'invoice_code',
                'channel_invoice_no',
            ],
            'same_store' => true,
            'block_duplicate' => true,
            'allow_unlinked_submit' => true,
            'allow_mixed_linkage' => false,
            'status_timing' => 'on_post',
            'record_only_daily_sales' => false,
        ];
    }

    private function operationalSettingArray(string $key, array $fallback): array
    {
        $value = json_decode((string) SystemSetting::get($key, '[]'), true);

        return is_array($value) ? array_values($value) : $fallback;
    }

    private function salesOperationalSettings(): array
    {
        $defaults = $this->defaultSalesOperationalSettings();
        $allowedModes = ['record_only', 'suggest_on_confirm', 'auto_link_on_scan'];
        $allowedSources = ['marketplace_order', 'sales_invoice'];
        $allowedIdentifiers = [
            'shipping_awb_no',
            'channel_order_id',
            'booking_sn',
            'external_order_id',
            'invoice_code',
            'channel_invoice_no',
        ];

        $mode = (string) SystemSetting::get(
            SystemSetting::KEY_SALES_LOOKUP_MODE,
            $defaults['lookup_mode']
        );
        $sources = array_values(array_intersect(
            $allowedSources,
            $this->operationalSettingArray(SystemSetting::KEY_SALES_LOOKUP_SOURCES, $defaults['lookup_sources'])
        ));
        $identifiers = array_values(array_intersect(
            $allowedIdentifiers,
            $this->operationalSettingArray(SystemSetting::KEY_SALES_LOOKUP_IDENTIFIERS, $defaults['lookup_identifiers'])
        ));
        $statusTiming = (string) SystemSetting::get(
            SystemSetting::KEY_SALES_STATUS_TIMING,
            $defaults['status_timing']
        );

        return [
            'lookup_mode' => in_array($mode, $allowedModes, true) ? $mode : $defaults['lookup_mode'],
            'lookup_sources' => $sources ?: $defaults['lookup_sources'],
            'lookup_identifiers' => $identifiers ?: $defaults['lookup_identifiers'],
            'same_store' => SystemSetting::get(SystemSetting::KEY_SALES_LOOKUP_SAME_STORE, '1') !== '0',
            'block_duplicate' => SystemSetting::get(SystemSetting::KEY_SALES_LOOKUP_BLOCK_DUPLICATE, '1') !== '0',
            'allow_unlinked_submit' => SystemSetting::get(SystemSetting::KEY_SALES_ALLOW_UNLINKED_SUBMIT, '1') !== '0',
            'allow_mixed_linkage' => SystemSetting::get(SystemSetting::KEY_SALES_ALLOW_MIXED_LINKAGE, '0') === '1',
            'status_timing' => in_array($statusTiming, ['never', 'on_link', 'on_post'], true)
                ? $statusTiming
                : $defaults['status_timing'],
            'record_only_daily_sales' => SystemSetting::get(SystemSetting::KEY_SALES_RECORD_ONLY_DAILY_SALES, '0') === '1',
        ];
    }

    private function persistSalesOperationalSettings(Request $request): void
    {
        if (! $request->hasAny([
            'sales_lookup_mode',
            'sales_lookup_sources',
            'sales_lookup_identifiers',
            'sales_lookup_same_store',
            'sales_lookup_block_duplicate',
            'sales_allow_unlinked_submit',
            'sales_allow_mixed_linkage',
            'sales_marketplace_status_timing',
            'sales_record_only_daily_sales',
        ])) {
            return;
        }

        $data = $request->validate([
            'sales_lookup_mode' => ['required', 'in:record_only,suggest_on_confirm,auto_link_on_scan'],
            'sales_lookup_sources' => ['nullable', 'array'],
            'sales_lookup_sources.*' => ['string', 'in:marketplace_order,sales_invoice'],
            'sales_lookup_identifiers' => ['nullable', 'array'],
            'sales_lookup_identifiers.*' => ['string', 'in:shipping_awb_no,channel_order_id,booking_sn,external_order_id,invoice_code,channel_invoice_no'],
            'sales_marketplace_status_timing' => ['required', 'in:never,on_link,on_post'],
        ]);

        $defaults = $this->defaultSalesOperationalSettings();
        $sources = array_values(array_unique($data['sales_lookup_sources'] ?? []));
        $identifiers = array_values(array_unique($data['sales_lookup_identifiers'] ?? []));

        SystemSetting::set(
            SystemSetting::KEY_SALES_LOOKUP_MODE,
            $data['sales_lookup_mode'],
            'Mode lookup order operasional penjualan',
            auth()->id()
        );
        SystemSetting::set(
            SystemSetting::KEY_SALES_LOOKUP_SOURCES,
            json_encode($sources ?: $defaults['lookup_sources'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'Sumber lookup order operasional penjualan',
            auth()->id()
        );
        SystemSetting::set(
            SystemSetting::KEY_SALES_LOOKUP_IDENTIFIERS,
            json_encode($identifiers ?: $defaults['lookup_identifiers'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'Prioritas identitas lookup order operasional penjualan',
            auth()->id()
        );
        SystemSetting::set(SystemSetting::KEY_SALES_LOOKUP_SAME_STORE, $request->boolean('sales_lookup_same_store') ? '1' : '0', 'Batasi lookup ke store shipment', auth()->id());
        SystemSetting::set(SystemSetting::KEY_SALES_LOOKUP_BLOCK_DUPLICATE, $request->boolean('sales_lookup_block_duplicate') ? '1' : '0', 'Blokir order aktif ganda di shipment', auth()->id());
        SystemSetting::set(SystemSetting::KEY_SALES_ALLOW_UNLINKED_SUBMIT, $request->boolean('sales_allow_unlinked_submit') ? '1' : '0', 'Boleh submit order tanpa tautan', auth()->id());
        SystemSetting::set(SystemSetting::KEY_SALES_ALLOW_MIXED_LINKAGE, $request->boolean('sales_allow_mixed_linkage') ? '1' : '0', 'Boleh campur order tertaut dan record-only', auth()->id());
        SystemSetting::set(SystemSetting::KEY_SALES_STATUS_TIMING, $data['sales_marketplace_status_timing'], 'Waktu update status marketplace', auth()->id());
        SystemSetting::set(SystemSetting::KEY_SALES_RECORD_ONLY_DAILY_SALES, $request->boolean('sales_record_only_daily_sales') ? '1' : '0', 'Catat Daily Sales untuk record-only', auth()->id());
    }

    private function resetSalesOperationalSettings(): void
    {
        $defaults = $this->defaultSalesOperationalSettings();
        SystemSetting::set(SystemSetting::KEY_SALES_LOOKUP_MODE, $defaults['lookup_mode'], 'Mode lookup order operasional penjualan', auth()->id());
        SystemSetting::set(SystemSetting::KEY_SALES_LOOKUP_SOURCES, json_encode($defaults['lookup_sources'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'Sumber lookup order operasional penjualan', auth()->id());
        SystemSetting::set(SystemSetting::KEY_SALES_LOOKUP_IDENTIFIERS, json_encode($defaults['lookup_identifiers'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'Prioritas identitas lookup order operasional penjualan', auth()->id());
        SystemSetting::set(SystemSetting::KEY_SALES_LOOKUP_SAME_STORE, '1', 'Batasi lookup ke store shipment', auth()->id());
        SystemSetting::set(SystemSetting::KEY_SALES_LOOKUP_BLOCK_DUPLICATE, '1', 'Blokir order aktif ganda di shipment', auth()->id());
        SystemSetting::set(SystemSetting::KEY_SALES_ALLOW_UNLINKED_SUBMIT, '1', 'Boleh submit order tanpa tautan', auth()->id());
        SystemSetting::set(SystemSetting::KEY_SALES_ALLOW_MIXED_LINKAGE, '0', 'Boleh campur order tertaut dan record-only', auth()->id());
        SystemSetting::set(SystemSetting::KEY_SALES_STATUS_TIMING, 'on_post', 'Waktu update status marketplace', auth()->id());
        SystemSetting::set(SystemSetting::KEY_SALES_RECORD_ONLY_DAILY_SALES, '0', 'Catat Daily Sales untuk record-only', auth()->id());
    }

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

    public function operationalIndex(): View
    {
        $soundEnabled = SystemSetting::get(
            SystemSetting::KEY_SHIPMENT_SCAN_SOUND,
            '1'
        ) !== '0';
        $ringtones = ShipmentScanRingtone::query()->latest()->get();
        $soundEvents = $this->scanSoundEvents();
        $soundMap = $this->normalizedScanSoundMap($ringtones);
        $builtinSounds = self::SCAN_SOUND_BUILTINS;
        $lookupSettings = $this->salesOperationalSettings();
        $scanDocuments = [
            [
                'key' => 'scanner_manual',
                'title' => 'Buku Manual Scanner BP-TC100',
                'description' => 'Panduan konfigurasi scanner Bluetooth CCD infrared 1D.',
                'pages' => 16,
                'path' => 'buku-manual-scanner-bp-tc100.pdf',
            ],
            [
                'key' => 'scan_control',
                'title' => 'GreatFit Scan Control',
                'description' => 'Lembar barcode kontrol untuk NEXT, RESET, dan UNDO.',
                'pages' => 1,
                'path' => 'greatfit-scan-control.pdf',
            ],
        ];

        return view('sales.settings.operational', compact(
            'soundEnabled',
            'ringtones',
            'soundEvents',
            'soundMap',
            'builtinSounds',
            'lookupSettings',
            'scanDocuments'
        ));
    }

    public function operationalDocument(string $document)
    {
        abort_unless(auth()->user()?->isOwner(), 403);

        $documents = [
            'scanner_manual' => 'buku-manual-scanner-bp-tc100.pdf',
            'scan_control' => 'greatfit-scan-control.pdf',
        ];
        abort_unless(isset($documents[$document]), 404);

        $path = public_path('scan-guides/' . $documents[$document]);
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $documents[$document] . '"',
        ]);
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

        if ($request->hasAny([
            'shipment_scan_sound_enabled',
            'shipment_scan_sound_map',
            'shipment_scan_ringtone_upload',
            'reset_shipment_scan_defaults',
        ])) {
            abort_unless(auth()->user()?->isOwner(), 403);
        }

        if ($request->boolean('reset_shipment_scan_defaults')) {
            $this->resetShipmentScanSettings();

            return redirect()
                ->route('admin.website.settings')
                ->with('success', 'Pengaturan suara scan berhasil dikembalikan ke default GFID.');
        }

        if ($request->boolean('shipment_scan_ringtone_upload')) {
            return $this->uploadShipmentRingtone($request);
        }

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
            $this->persistShipmentScanSettings($request);
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

    public function updateOperational(Request $request): RedirectResponse
    {
        if ($request->hasAny([
            'shipment_scan_sound_enabled',
            'shipment_scan_sound_map',
            'shipment_scan_ringtone_upload',
            'reset_shipment_scan_defaults',
            'sales_lookup_mode',
            'sales_lookup_sources',
            'sales_lookup_identifiers',
            'sales_lookup_same_store',
            'sales_lookup_block_duplicate',
            'sales_allow_unlinked_submit',
            'sales_allow_mixed_linkage',
            'sales_marketplace_status_timing',
            'sales_record_only_daily_sales',
            'reset_sales_operational_defaults',
        ])) {
            abort_unless(auth()->user()?->isOwner(), 403);
        }

        if ($request->boolean('reset_shipment_scan_defaults')) {
            $this->resetShipmentScanSettings();

            return redirect()
                ->route('sales.settings.operational')
                ->with('success', 'Pengaturan suara scan berhasil dikembalikan ke default GFID.');
        }

        if ($request->boolean('reset_sales_operational_defaults')) {
            $this->resetSalesOperationalSettings();

            return redirect()
                ->route('sales.settings.operational')
                ->with('success', 'Pengaturan lookup dan tautan order dikembalikan ke default aman.');
        }

        if ($request->boolean('shipment_scan_ringtone_upload')) {
            return $this->uploadShipmentRingtone($request, 'sales.settings.operational');
        }

        try {
            $this->persistShipmentScanSettings($request);
            $this->persistSalesOperationalSettings($request);
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('sales.settings.operational')
                ->with('error', 'Pengaturan suara scan gagal disimpan.');
        }

        return redirect()
            ->route('sales.settings.operational')
            ->with('success', 'Pengaturan operasional penjualan berhasil disimpan.');
    }

    public function deleteShipmentRingtone(ShipmentScanRingtone $ringtone): RedirectResponse
    {
        abort_unless(auth()->user()?->isOwner(), 403);

        return $this->removeShipmentRingtone($ringtone, 'admin.website.settings');
    }

    public function deleteOperationalRingtone(Request $request, ShipmentScanRingtone $ringtone): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        abort_unless(auth()->user()?->isOwner(), 403);

        return $this->removeShipmentRingtone($ringtone, 'sales.settings.operational', $request->expectsJson());
    }

    public function trimOperationalRingtone(Request $request, ShipmentScanRingtone $ringtone): RedirectResponse|JsonResponse
    {
        abort_unless(auth()->user()?->isOwner(), 403);

        $data = $request->validate([
            'trim_start' => ['required', 'numeric', 'min:0', 'max:300'],
            'trim_duration' => ['required', 'numeric', 'min:0.1', 'max:30'],
        ]);

        $temporaryOutput = null;

        try {
            $disk = Storage::disk('public');
            if (!$disk->exists($ringtone->path)) {
                throw new \RuntimeException('File ringtone asli tidak ditemukan di storage.');
            }

            $source = $disk->path($ringtone->path);
            $start = (float) $data['trim_start'];
            $duration = (float) $data['trim_duration'];

            $sourceDuration = null;
            $probeSource = new Process([
                $this->mediaBinary('ffprobe'), '-v', 'error', '-show_entries', 'format=duration',
                '-of', 'default=noprint_wrappers=1:nokey=1', $source,
            ]);
            $probeSource->setTimeout(10);
            $probeSource->run();
            if ($probeSource->isSuccessful() && is_numeric(trim($probeSource->getOutput()))) {
                $sourceDuration = (float) trim($probeSource->getOutput());
            }

            if ($sourceDuration !== null) {
                if ($start >= $sourceDuration) {
                    throw new \RuntimeException('Waktu mulai berada di luar durasi ringtone.');
                }
                $duration = min($duration, max(0.1, $sourceDuration - $start));
            }

            $temporaryOutput = tempnam(storage_path('app'), 'gfid-ringtone-trim-');
            @unlink($temporaryOutput);
            $temporaryOutput .= '.mp3';

            $process = new Process([
                $this->mediaBinary('ffmpeg'), '-y', '-hide_banner', '-loglevel', 'error',
                '-ss', number_format($start, 3, '.', ''),
                '-i', $source,
                '-t', number_format($duration, 3, '.', ''),
                '-vn', '-ac', '1', '-ar', '22050',
                '-c:a', 'libmp3lame', '-b:a', '64k',
                $temporaryOutput,
            ]);
            $process->setTimeout(30);
            $process->run();

            if (!$process->isSuccessful() || !is_file($temporaryOutput)) {
                throw new \RuntimeException('FFmpeg gagal memotong file ringtone. ' . trim($process->getErrorOutput()));
            }

            $durationMs = null;
            $probeOutput = new Process([
                $this->mediaBinary('ffprobe'), '-v', 'error', '-show_entries', 'format=duration',
                '-of', 'default=noprint_wrappers=1:nokey=1', $temporaryOutput,
            ]);
            $probeOutput->setTimeout(10);
            $probeOutput->run();
            if ($probeOutput->isSuccessful() && is_numeric(trim($probeOutput->getOutput()))) {
                $durationMs = (int) round((float) trim($probeOutput->getOutput()) * 1000);
            }

            $newPath = 'shipment-ringtones/' . Str::uuid() . '.mp3';
            $stream = fopen($temporaryOutput, 'rb');
            $disk->put($newPath, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }

            $oldPath = $ringtone->path;
            $ringtone->update([
                'path' => $newPath,
                'mime_type' => 'audio/mpeg',
                'extension' => 'mp3',
                'compressed_size_bytes' => (int) $disk->size($newPath),
                'duration_ms' => $durationMs,
            ]);
            $disk->delete($oldPath);

            $message = 'Durasi ringtone berhasil dipotong dan file library diperbarui.';
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'ok',
                    'message' => $message,
                    'duration_ms' => $durationMs,
                ]);
            }

            return redirect()->route('sales.settings.operational')->with('success', $message);
        } catch (\Throwable $e) {
            report($e);
            $errorMessage = strtolower($e->getMessage());
            $message = str_contains($errorMessage, 'ffmpeg') || str_contains($errorMessage, 'ffprobe')
                ? 'Ringtone gagal dipotong. Pastikan FFmpeg/FFprobe tersedia dan file audionya valid.'
                : $e->getMessage();

            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => $message], 422);
            }

            return redirect()->route('sales.settings.operational')->with('error', $message);
        } finally {
            if ($temporaryOutput && is_file($temporaryOutput)) {
                @unlink($temporaryOutput);
            }
        }
    }

    private function removeShipmentRingtone(ShipmentScanRingtone $ringtone, string $redirectRoute, bool $asJson = false): RedirectResponse|\Illuminate\Http\JsonResponse
    {

        Storage::disk('public')->delete($ringtone->path);
        $deletedId = (int) $ringtone->id;
        $ringtone->delete();

        $map = $this->normalizedScanSoundMap();
        $defaults = $this->defaultScanSoundMap();
        foreach ($map as $key => $value) {
            if ($value === 'ringtone:' . $deletedId) {
                $map[$key] = $defaults[$key];
            }
        }

        SystemSetting::set(
            SystemSetting::KEY_SHIPMENT_SCAN_SOUND_MAP,
            json_encode($map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'Mapping suara event scan pengiriman',
            auth()->id()
        );

        $message = 'Ringtone berhasil dihapus. Mapping yang menggunakannya dikembalikan ke suara bawaan.';

        if ($asJson) {
            return response()->json([
                'status' => 'ok',
                'message' => $message,
                'deleted_id' => $deletedId,
            ]);
        }

        return redirect()
            ->route($redirectRoute)
            ->with('success', $message);
    }

    private function storeUncompressedShipmentRingtone($input, array $data): void
    {
        if (! $input || ! $input->isValid()) {
            throw new \RuntimeException('File upload audio tidak dapat dibaca oleh server.');
        }

        $extension = strtolower((string) ($input->getClientOriginalExtension() ?: $input->extension() ?: 'audio'));
        $mimeTypes = [
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg',
            'm4a' => 'audio/mp4',
            'aac' => 'audio/aac',
            'flac' => 'audio/flac',
            'webm' => 'audio/webm',
        ];
        $mimeType = $mimeTypes[$extension] ?? ((string) $input->getClientMimeType() ?: 'application/octet-stream');
        $path = 'shipment-ringtones/' . Str::uuid() . '.' . $extension;
        $stream = fopen($input->getRealPath(), 'rb');

        if (! $stream || ! Storage::disk('public')->put($path, $stream)) {
            if (is_resource($stream)) {
                fclose($stream);
            }
            throw new \RuntimeException('Disk public tidak dapat menyimpan file ringtone.');
        }
        if (is_resource($stream)) {
            fclose($stream);
        }

        $size = (int) Storage::disk('public')->size($path);
        if ($size <= 0) {
            Storage::disk('public')->delete($path);
            throw new \RuntimeException('File ringtone tersimpan dengan ukuran 0 byte.');
        }

        ShipmentScanRingtone::create([
            'name' => trim($data['shipment_scan_ringtone_name'] ?? '')
                ?: pathinfo($input->getClientOriginalName(), PATHINFO_FILENAME),
            'original_name' => $input->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $mimeType,
            'extension' => $extension,
            'size_bytes' => (int) $input->getSize(),
            'compressed_size_bytes' => $size,
            'duration_ms' => null,
            'uploaded_by' => auth()->id(),
        ]);
    }

    private function uploadShipmentRingtone(Request $request, string $redirectRoute = 'admin.website.settings'): RedirectResponse
    {
        $data = $request->validate([
            'shipment_scan_ringtone_name' => ['nullable', 'string', 'max:120'],
            'shipment_scan_ringtone_audio' => ['required', 'file', 'mimes:mp3,wav,ogg,m4a,aac,flac,webm', 'max:20480'],
        ], [
            'shipment_scan_ringtone_audio.mimes' => 'Format audio harus MP3, WAV, OGG, M4A, AAC, FLAC, atau WEBM.',
            'shipment_scan_ringtone_audio.max' => 'Ukuran audio maksimal 20 MB.',
        ]);

        $input = $request->file('shipment_scan_ringtone_audio');
        $temporaryOutput = null;

        try {
            $source = $input?->getRealPath();
            if (! $input || ! $input->isValid() || ! $source || ! is_readable($source)) {
                throw new \RuntimeException('File upload audio tidak dapat dibaca oleh server.');
            }

            $temporaryOutput = tempnam(sys_get_temp_dir(), 'gfid-ringtone-');
            if ($temporaryOutput === false) {
                throw new \RuntimeException('Folder temporary server tidak dapat ditulis.');
            }
            @unlink($temporaryOutput);
            $temporaryOutput .= '.mp3';

            $process = new Process([
                $this->mediaBinary('ffmpeg'), '-y', '-hide_banner', '-loglevel', 'error',
                '-i', $source,
                '-t', '5', '-vn', '-ac', '1', '-ar', '22050',
                '-c:a', 'libmp3lame', '-b:a', '64k',
                $temporaryOutput,
            ]);
            $process->setTimeout(30);
            $process->run();

            if (! $process->isSuccessful() || ! is_file($temporaryOutput) || (int) filesize($temporaryOutput) <= 0) {
                $errorOutput = trim($process->getErrorOutput());
                $message = str_contains(strtolower($errorOutput), 'not found')
                    ? 'FFmpeg tidak ditemukan di server.'
                    : 'FFmpeg gagal membaca atau mengompres file audio.';
                throw new \RuntimeException($message . ' ' . $errorOutput);
            }

            $durationMs = null;
            try {
                $probe = new Process([
                    $this->mediaBinary('ffprobe'), '-v', 'error', '-show_entries', 'format=duration',
                    '-of', 'default=noprint_wrappers=1:nokey=1', $temporaryOutput,
                ]);
                $probe->setTimeout(10);
                $probe->run();
                if ($probe->isSuccessful() && is_numeric(trim($probe->getOutput()))) {
                    $durationMs = min(5000, (int) round((float) trim($probe->getOutput()) * 1000));
                }
            } catch (\Throwable) {
                // ffprobe hanya untuk metadata; upload tetap valid tanpa durasi.
            }

            $path = 'shipment-ringtones/' . Str::uuid() . '.mp3';
            $stream = fopen($temporaryOutput, 'rb');
            if (! $stream || ! Storage::disk('public')->put($path, $stream)) {
                if (is_resource($stream)) {
                    fclose($stream);
                }
                throw new \RuntimeException('Disk public tidak dapat menyimpan file ringtone.');
            }
            if (is_resource($stream)) {
                fclose($stream);
            }

            $compressedSize = (int) Storage::disk('public')->size($path);
            if ($compressedSize <= 0) {
                Storage::disk('public')->delete($path);
                throw new \RuntimeException('File ringtone tersimpan dengan ukuran 0 byte.');
            }

            ShipmentScanRingtone::create([
                'name' => trim($data['shipment_scan_ringtone_name'] ?? '')
                    ?: pathinfo($input->getClientOriginalName(), PATHINFO_FILENAME),
                'original_name' => $input->getClientOriginalName(),
                'path' => $path,
                'mime_type' => 'audio/mpeg',
                'extension' => 'mp3',
                'size_bytes' => (int) $input->getSize(),
                'compressed_size_bytes' => $compressedSize,
                'duration_ms' => $durationMs,
                'uploaded_by' => auth()->id(),
            ]);

            return redirect()
                ->route($redirectRoute)
                ->with('success', 'Ringtone berhasil dikompres dan ditambahkan ke library.');
        } catch (\Throwable $e) {
            report($e);

            $errorMessage = strtolower($e->getMessage());
            if (str_contains($errorMessage, 'ffmpeg tidak ditemukan') || str_contains($errorMessage, 'ffmpeg tidak ditemukan pada server')) {
                try {
                    $this->storeUncompressedShipmentRingtone($input, $data);

                    return redirect()
                        ->route($redirectRoute)
                        ->with('success', 'Ringtone berhasil ditambahkan tanpa kompresi. FFmpeg belum tersedia di server.');
                } catch (\Throwable $fallbackError) {
                    report($fallbackError);
                    $errorMessage = strtolower($fallbackError->getMessage());
                }
            }

            $userMessage = str_contains($errorMessage, 'disk public') || str_contains($errorMessage, 'temporary server')
                ? 'Server tidak dapat menyimpan file ringtone. Pastikan folder storage dan temporary dapat ditulis oleh PHP-FPM.'
                : 'File audio tidak dapat diproses. Pastikan formatnya valid dan durasinya tidak bermasalah.';

            return redirect()
                ->route($redirectRoute)
                ->with('error', $userMessage);
        } finally {
            if ($temporaryOutput && is_file($temporaryOutput)) {
                @unlink($temporaryOutput);
            }
        }
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
