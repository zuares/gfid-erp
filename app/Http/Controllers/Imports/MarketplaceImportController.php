<?php

namespace App\Http\Controllers\Imports;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\MpShipment;
use App\Models\Store;
use App\Services\Marketplace\Export\MarketplaceExportService;
use App\Services\Marketplace\Import\MpImportService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MarketplaceImportController extends Controller
{
    /** Disk penyimpanan upload */
    private string $disk = 'local';

    /* ============================================================
     * INDEX
     * ============================================================ */
    public function index(Request $request): View
    {
        $tz = 'Asia/Jakarta';

        $filters = [
            'q' => trim((string) $request->get('q', '')),
            // NOTE: ini memang string karena MpShipmentController filter pakai mp_shipments.channel (shopee/tiktok)
            'channel' => (string) $request->get('channel', ''),
            'store_id' => (string) $request->get('store_id', ''),
            'status' => (string) $request->get('status', ''),
            'from' => (string) $request->get('from', ''),
            'to' => (string) $request->get('to', ''),
        ];

        $today = Carbon::now($tz)->startOfDay();

        $from = $filters['from']
        ? Carbon::parse($filters['from'], $tz)->startOfDay()
        : (clone $today)->subDays(6);

        $to = $filters['to']
        ? Carbon::parse($filters['to'], $tz)->endOfDay()
        : (clone $today)->endOfDay();

        $filters['from'] = $from->format('Y-m-d');
        $filters['to'] = $to->format('Y-m-d');

        return view('imports.marketplace.index', [
            'stores' => Store::select('id', 'name')->orderBy('name')->get(),
            'draft' => session('mp_import_preview'),
            'filters' => $filters,
        ]);
    }

    /* ============================================================
     * EXPORT (delegated)
     * ============================================================ */
    public function export(Request $request, MarketplaceExportService $service): StreamedResponse
    {
        return $service->export($request);
    }

    /* ============================================================
     * CREATE
     * ============================================================ */
    public function create(): View
    {
        return view('imports.marketplace.create', [
            'channels' => Channel::select('id', 'code', 'name')
                ->where('is_active', 1)
                ->orderBy('name')
                ->get(),
            'stores' => Store::select('id', 'name', 'channel_id')
                ->where('is_active', 1)
                ->orderBy('name')
                ->get(),
            'draft' => session('mp_import_preview'),
        ]);
    }

    /* ============================================================
     * PREVIEW
     * ============================================================ */
    public function preview(Request $request, MpImportService $svc): View | RedirectResponse
    {
        $data = $request->validate([
            'channel_id' => ['required', 'integer', 'exists:channels,id'],
            'store_id' => [
                'required',
                'integer',
                Rule::exists('stores', 'id')->where(function ($q) use ($request) {
                    $q->where('is_active', 1)
                        ->where(function ($w) use ($request) {
                            $w->where('channel_id', $request->input('channel_id'))
                                ->orWhereNull('channel_id'); // kalau TIDAK mau NULL, hapus baris ini
                        });
                }),
            ],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $channelId = (int) $data['channel_id'];
        $storeId = (int) $data['store_id'];

        $channel = Channel::select('id', 'code', 'name')->findOrFail($channelId);
        $store = Store::select('id', 'name', 'channel_id')->findOrFail($storeId);

        $channelKey = $this->resolveChannelKey($channel->name, $channel->code);

        if (!$channelKey) {
            return back()->withInput()->with('error', 'Channel belum dikenali. Pastikan channels.name = Shopee/TikTok atau mapping di resolveChannelKey().');
        }

        $file = $request->file('file');
        $sourceFile = $file->getClientOriginalName();

        $path = $file->store("imports/marketplace/{$channelKey}", $this->disk);
        $abs = Storage::disk($this->disk)->path($path);

        $res = $svc->import($channelKey, $abs, $storeId, $sourceFile, true);

        // ===== persist draft =====
        session([
            'mp_import_preview' => [
                'mode' => 'shipments',
                'disk' => $this->disk,
                'stored_path' => $path,

                'channel_id' => $channelId,
                'channel_name' => (string) $channel->name,
                'channel_key' => $channelKey,

                'store_id' => $storeId,
                'store_name' => (string) $store->name,

                'source_file' => $sourceFile,
                'preview' => array_slice($res['normalized'] ?? [], 0, 50),
                'stats' => $res['stats'] ?? [],
                'import_errors' => $res['import_errors'] ?? [],
            ],
        ]);

        // ===== inject meta for preview blade =====
        $res['channel_id'] = $channelId;
        $res['channel_name'] = (string) $channel->name;
        $res['channel_key'] = $channelKey;

        $res['store_id'] = $storeId;
        $res['store_name'] = (string) $store->name;

        $res['source_file'] = $sourceFile;

        return view('imports.marketplace.preview', $res);
    }

    /* ============================================================
     * COMMIT
     * ============================================================ */
    public function commit(MpImportService $svc): RedirectResponse
    {
        $draft = session('mp_import_preview');
        abort_if(!$draft, 400, 'Tidak ada draft');

        $channelKey = $draft['channel_key'] ?? null;
        abort_if(!$channelKey, 400, 'Draft tidak valid: channel_key kosong');

        $disk = $draft['disk'] ?? $this->disk;
        $storedPath = $draft['stored_path'] ?? null;
        abort_if(!$storedPath, 400, 'Draft tidak valid: stored_path kosong');

        $abs = Storage::disk($disk)->path($storedPath);

        $res = $svc->import(
            $channelKey,
            $abs,
            (int) ($draft['store_id'] ?? 0),
            (string) ($draft['source_file'] ?? 'draft.xlsx'),
            false
        );

        // ✅ bust KPI cache so index updates immediately
        Cache::increment('mp_shipments:kpi:ver');

        session()->forget('mp_import_preview');

        $rows = (int) ($res['stats']['rows'] ?? $draft['stats']['rows'] ?? 0);

        return redirect()
            ->route('imports.marketplace.index')
            ->with('success', 'Import marketplace selesai' . ($rows ? " • Rows: {$rows}" : ''));
    }

    /* ============================================================
     * DRAFT (RESUME)
     * ============================================================ */
    public function draft(MpImportService $svc)
    {
        $draft = session('mp_import_preview');

        if (!$draft) {
            return redirect()
                ->route('imports.marketplace.create')
                ->with('error', 'Tidak ada draft import yang bisa dilanjutkan.');
        }

        // fallback cepat kalau file tidak bisa diproses ulang
        $fallback = [
            'normalized' => $draft['preview'] ?? [],
            'stats' => $draft['stats'] ?? [],
            'import_errors' => $draft['import_errors'] ?? [],
            'channel_id' => $draft['channel_id'] ?? null,
            'channel_name' => $draft['channel_name'] ?? null,
            'channel_key' => $draft['channel_key'] ?? null,
            'store_id' => $draft['store_id'] ?? null,
            'store_name' => $draft['store_name'] ?? null,
            'source_file' => $draft['source_file'] ?? null,
        ];

        $channelKey = $draft['channel_key'] ?? null;
        $disk = $draft['disk'] ?? $this->disk;
        $storedPath = $draft['stored_path'] ?? null;

        // kalau draft tidak lengkap -> fallback + kasih pesan
        if (!$channelKey || !$storedPath) {
            return view('imports.marketplace.preview', $fallback)
                ->with('error', 'Draft tidak lengkap. Silakan upload ulang file import.');
        }

        // kalau file draft sudah hilang (storage terhapus / session lama)
        if (!Storage::disk($disk)->exists($storedPath)) {
            return view('imports.marketplace.preview', $fallback)
                ->with('error', 'File draft tidak ditemukan di storage. Silakan upload ulang.');
        }

        try {
            $abs = Storage::disk($disk)->path($storedPath);

            // rerun dryRun untuk rebuild normalized + stats lengkap
            $res = $svc->import(
                $channelKey,
                $abs,
                (int) ($draft['store_id'] ?? 0),
                (string) ($draft['source_file'] ?? 'draft.xlsx'),
                true
            );

            // normalize key untuk blade preview
            $payload = array_merge($fallback, [
                'normalized' => $res['normalized'] ?? [],
                'stats' => $res['stats'] ?? [],
                'import_errors' => $res['import_errors'] ?? ($res['errors'] ?? []),
            ]);

            return view('imports.marketplace.preview', $payload);
        } catch (\Throwable $e) {
            return view('imports.marketplace.preview', $fallback)
                ->with('error', 'Draft dibuka dari cache (file tidak diproses ulang).');
        }
    }

    /* ============================================================
     * SHOW
     * ============================================================ */
    public function show(MpShipment $import): View
    {
        return view('imports.marketplace.show', [
            's' => $import->load(['store:id,name', 'items']),
        ]);
    }

    /* ============================================================
     * CANCEL
     * ============================================================ */
    public function cancel(): RedirectResponse
    {
        if ($draft = session('mp_import_preview')) {
            if (!empty($draft['disk']) && !empty($draft['stored_path'])) {
                Storage::disk($draft['disk'])->delete($draft['stored_path']);
            }
        }

        session()->forget('mp_import_preview');

        return redirect()
            ->route('imports.marketplace.create')
            ->with('success', 'Draft dibatalkan');
    }

    /* ============================================================
     * Helpers
     * ============================================================ */
    private function resolveChannelKey(?string $name, ?string $code): ?string
    {
        $name = strtolower(trim((string) ($name ?? '')));
        $code = strtolower(trim((string) ($code ?? '')));

        // map by name
        $mapByName = [
            'shopee' => 'shopee',
            'shopee indonesia' => 'shopee',
            'tiktok' => 'tiktok',
            'tiktok shop' => 'tiktok',
        ];
        if (isset($mapByName[$name])) {
            return $mapByName[$name];
        }

        // map by code
        $mapByCode = [
            'shp' => 'shopee',
            'shopee' => 'shopee',
            'ttk' => 'tiktok',
            'tiktok' => 'tiktok',
        ];
        if (isset($mapByCode[$code])) {
            return $mapByCode[$code];
        }

        // allow direct usage if code already matches adapter keys
        if (in_array($code, ['shopee', 'tiktok'], true)) {
            return $code;
        }

        return null;
    }

    public function reconcilePreview(Request $request, MarketplaceReconcileService $svc)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'channel' => ['required', 'in:shopee,tiktok'],
            'store_id' => ['nullable', 'integer'],
            'window' => ['required', 'integer', 'min:0', 'max:7'],
            'threshold' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $res = $svc->reconcileByDate(
            dateYmd: $data['date'],
            channel: $data['channel'],
            storeId: $data['store_id'] ? (int) $data['store_id'] : null,
            windowDays: (int) $data['window'],
            threshold: (int) $data['threshold'],
            dryRun: true
        );

        // simpan preview ke session biar bisa ditampilkan di halaman import
        session()->put('mp_reconcile_preview', [
            'params' => $data,
            'result' => $res,
        ]);

        return back()->with('ok', 'Preview siap.');
    }

    public function reconcileCommit(Request $request, MarketplaceReconcileService $svc)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'channel' => ['required', 'in:shopee,tiktok'],
            'store_id' => ['nullable', 'integer'],
            'window' => ['required', 'integer', 'min:0', 'max:7'],
            'threshold' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $res = $svc->reconcileByDate(
            dateYmd: $data['date'],
            channel: $data['channel'],
            storeId: $data['store_id'] ? (int) $data['store_id'] : null,
            windowDays: (int) $data['window'],
            threshold: (int) $data['threshold'],
            dryRun: false
        );

        // optional: clear preview supaya gak bikin bingung
        session()->forget('mp_reconcile_preview');

        return back()->with('ok', 'Reconcile berhasil disimpan. Auto: ' . ($res['stats']['matched'] ?? 0) . ' • Review: ' . ($res['stats']['needs_review'] ?? 0));
    }
}
