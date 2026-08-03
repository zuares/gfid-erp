<?php

namespace App\Http\Controllers\Imports;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\MarketplaceImportBatch;
use App\Models\MpShipment;
use App\Models\Store;
use App\Services\Marketplace\Export\MarketplaceExportService;
use App\Services\Marketplace\Import\MpImportService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
            'recentBatches' => MarketplaceImportBatch::with('store:id,name')
                ->latest()
                ->limit(8)
                ->get(),
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
    public function create(Request $request): View
    {
        $selectedStore = null;

        if ($request->filled('store_id')) {
            $selectedStore = Store::query()
                ->select('id', 'name', 'channel_id')
                ->where('is_active', 1)
                ->find((int) $request->input('store_id'));
        }

        $selectedStoreId = $selectedStore?->id;
        $selectedChannelId = $selectedStore?->channel_id
            ?: ($request->filled('channel_id') ? (int) $request->input('channel_id') : null);

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
            'selectedChannelId' => $selectedChannelId,
            'selectedStoreId' => $selectedStoreId,
        ]);
    }

    /* ============================================================
     * PREVIEW
     * ============================================================ */
    public function previewPage(): RedirectResponse
    {
        if (session('mp_import_preview')) {
            return redirect()->route('imports.marketplace.draft');
        }

        return redirect()
            ->route('imports.marketplace.create')
            ->with('error', 'Tidak ada draft preview yang bisa dibuka. Silakan upload file terlebih dahulu.');
    }

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

        $stats = is_array($res['stats'] ?? null) ? $res['stats'] : [];
        $importErrors = is_array($res['import_errors'] ?? null) ? $res['import_errors'] : [];

        // File yang lolos ekstensi tetapi tidak menghasilkan shipment tidak boleh
        // diteruskan ke commit. Parser tetap mengembalikan preview agar user bisa
        // melihat konteksnya, tetapi tombol commit harus dinonaktifkan.
        if ((int) ($stats['shipments_parsed'] ?? 0) === 0) {
            $importErrors[] = [
                'key' => 'file',
                'message' => 'Tidak ada shipment yang berhasil dibaca. Pastikan template dan header file sesuai channel yang dipilih.',
            ];
        }

        $importErrors = array_values($importErrors);
        $stats['warnings'] = array_values(array_unique(array_merge(
            is_array($stats['warnings'] ?? null) ? $stats['warnings'] : [],
            $importErrors ? ['File belum bisa di-commit sebelum error diperbaiki.'] : []
        )));
        $res['stats'] = $stats;
        $res['import_errors'] = $importErrors;

        $batchId = (string) data_get($res, 'stats.import_batch_id');
        $fileHash = hash_file('sha256', $abs) ?: null;

        MarketplaceImportBatch::updateOrCreate(
            ['id' => $batchId],
            [
                'channel' => $channelKey,
                'store_id' => $storeId,
                'source_type' => 'import',
                'source_file' => $sourceFile,
                'file_hash' => $fileHash,
                'status' => 'preview',
                'total_rows' => (int) ($res['stats']['rows'] ?? $res['stats']['shipments_parsed'] ?? 0),
                'shipments_parsed' => (int) ($res['stats']['shipments_parsed'] ?? 0),
                'items_parsed' => (int) ($res['stats']['items_parsed'] ?? 0),
                'warnings' => $res['stats']['warnings'] ?? [],
                'errors' => $importErrors,
                'error_count' => count($importErrors),
                'created_by' => $request->user()?->id,
            ]
        );

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
                'import_batch_id' => $batchId,
                'file_hash' => $fileHash,
                'preview' => array_slice($res['normalized'] ?? [], 0, 50),
                'stats' => $res['stats'] ?? [],
                'import_errors' => $importErrors,
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

        $batchId = (string) ($draft['import_batch_id'] ?? '');
        $batch = $batchId !== '' ? MarketplaceImportBatch::find($batchId) : null;

        if (!empty($draft['import_errors']) || (int) data_get($draft, 'stats.shipments_parsed', 0) === 0) {
            $commitErrors = !empty($draft['import_errors'])
                ? $draft['import_errors']
                : [[
                    'key' => 'file',
                    'message' => 'Tidak ada shipment yang bisa di-commit.',
                ]];

            $batch?->update([
                'status' => 'failed',
                'errors' => $commitErrors,
                'error_count' => count($commitErrors),
                'completed_at' => now(),
            ]);

            return redirect()
                ->route('imports.marketplace.create')
                ->with('error', 'Import belum bisa di-commit. Perbaiki error pada preview atau upload file yang benar.');
        }

        if ($batch && in_array($batch->status, ['processing', 'completed'], true)) {
            return redirect()
                ->route('imports.marketplace.index')
                ->with('error', 'Batch import ini sudah sedang atau pernah diproses.');
        }

        $commitLock = $batchId !== ''
            ? Cache::lock('mp_import_commit:' . $batchId, 600)
            : null;

        if ($commitLock && !$commitLock->get()) {
            return redirect()
                ->route('imports.marketplace.index')
                ->with('error', 'Import ini sedang diproses. Tunggu sampai selesai sebelum mencoba lagi.');
        }

        $batch?->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        try {
            $res = $svc->import(
                $channelKey,
                $abs,
                (int) ($draft['store_id'] ?? 0),
                (string) ($draft['source_file'] ?? 'draft.xlsx'),
                false,
                $batchId !== '' ? $batchId : null
            );

            $stats = $res['stats'] ?? [];
            $batch?->update([
                'status' => 'completed',
                'shipments_parsed' => (int) ($stats['shipments_parsed'] ?? 0),
                'items_parsed' => (int) ($stats['items_parsed'] ?? 0),
                'inserted_shipments' => (int) ($stats['inserted_shipments'] ?? 0),
                'updated_shipments' => (int) ($stats['updated_shipments'] ?? 0),
                'inserted_items' => (int) ($stats['inserted_items'] ?? 0),
                'warnings' => $stats['warnings'] ?? [],
                'completed_at' => now(),
            ]);

            Storage::disk($disk)->delete($storedPath);
        } catch (\Throwable $e) {
            $batch?->update([
                'status' => 'failed',
                'errors' => [$e->getMessage()],
                'error_count' => 1,
                'completed_at' => now(),
            ]);

            throw $e;
        } finally {
            $commitLock?->release();
        }

        // ✅ bust KPI cache so index updates immediately
        Cache::increment('mp_shipments:kpi:ver');

        session()->forget('mp_import_preview');

        $rows = (int) ($res['stats']['shipments_parsed'] ?? $draft['stats']['rows'] ?? 0);

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
                true,
                ! empty($draft['import_batch_id']) ? (string) $draft['import_batch_id'] : null
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
            if (! empty($draft['import_batch_id'])) {
                MarketplaceImportBatch::whereKey($draft['import_batch_id'])->update([
                    'status' => 'cancelled',
                    'completed_at' => now(),
                ]);
            }

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
     * DELETE IMPORT BATCH
     * ============================================================ */
    public function destroyBatch(MarketplaceImportBatch $batch): RedirectResponse
    {
        if (! in_array($batch->status, ['completed', 'failed'], true)) {
            return redirect()
                ->route('imports.marketplace.index')
                ->with('error', 'Hanya import yang sudah selesai atau gagal yang bisa dihapus.');
        }

        // Batch dengan update tidak aman dihapus karena belum ada snapshot
        // nilai sebelum import untuk melakukan rollback secara akurat.
        if ((int) $batch->updated_shipments > 0) {
            return redirect()
                ->route('imports.marketplace.index')
                ->with('error', 'Batch ini memperbarui shipment lama sehingga tidak bisa dihapus otomatis.');
        }

        $deleted = DB::transaction(function () use ($batch): array {
            $shipmentIds = MpShipment::query()
                ->where('import_batch_id', $batch->id)
                ->pluck('id');

            $packetShipmentIds = $shipmentIds
                ->map(static fn ($id): string => (string) $id)
                ->all();

            $shipmentItems = DB::table('mp_shipment_items')
                ->whereIn('mp_shipment_id', $shipmentIds)
                ->delete();

            $packetItems = DB::table('mp_packet_items')
                ->whereIn('mp_shipment_id', $packetShipmentIds)
                ->delete();

            $reconciliations = DB::table('mp_reconciliations')
                ->whereIn('mp_shipment_id', $shipmentIds)
                ->delete();

            $shipments = MpShipment::query()
                ->whereIn('id', $shipmentIds)
                ->delete();

            $batches = MarketplaceImportBatch::query()
                ->whereKey($batch->id)
                ->delete();

            return compact('shipments', 'shipmentItems', 'packetItems', 'reconciliations', 'batches');
        });

        Cache::increment('mp_shipments:kpi:ver');

        return redirect()
            ->route('imports.marketplace.index')
            ->with('success', 'Import dihapus: ' . (int) $deleted['shipments'] . ' shipment.');
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
