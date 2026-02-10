<?php

namespace App\Http\Controllers\Imports;

use App\Http\Controllers\Controller;
use App\Models\MpIncome;
use App\Models\MpShipment;
use App\Models\MpShipmentItem;
use App\Models\Store;
use App\Services\Marketplace\Income\ApplyIncomeService;
use App\Services\Marketplace\Income\MpIncomeImportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MarketplaceIncomeImportController extends Controller
{
    /** Disk untuk simpan file upload income (konsisten, anti nyasar) */
    private string $disk = 'local';

    private const DEFAULT_PER_PAGE = 50;
    private const MAX_PER_PAGE = 200;

    private function draftKey(string $draftId): string
    {
        return "mp_income_preview:{$draftId}";
    }

    /* ============================================================
     * INDEX (ORDERS) — tampilkan mp_incomes (row-level)
     * ============================================================ */
    public function index(Request $request): View
    {
        $stores = $this->storesList();
        [$filters, $perPage] = $this->readFilters($request);

        $baseQ = $this->baseIncomeQuery()
            ->when($filters['channel'] !== '', fn(Builder $q) => $q->where('mp_incomes.channel', $filters['channel']))
            ->when($filters['store_id'] > 0, fn(Builder $q) => $q->where('mp_incomes.store_id', $filters['store_id']))
            ->when($filters['q'] !== '', fn(Builder $q) => $q->where('mp_incomes.platform_order_id', 'like', "%{$filters['q']}%"))
            ->when($filters['batch'] !== '', fn(Builder $q) => $q->where('mp_incomes.import_batch_id', $filters['batch']))
            ->when($filters['date_from'] !== '', fn(Builder $q) => $q->whereDate('mp_incomes.released_date', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn(Builder $q) => $q->whereDate('mp_incomes.released_date', '<=', $filters['date_to']));

        // KPI distinct orders
        $totalOrders = (clone $baseQ)->distinct()->count('mp_incomes.platform_order_id');

        $matchedOrders = (clone $baseQ)
            ->join('mp_shipments as s', function ($j) {
                $j->on('s.store_id', '=', 'mp_incomes.store_id')
                    ->on('s.channel', '=', 'mp_incomes.channel')
                    ->on('s.platform_order_id', '=', 'mp_incomes.platform_order_id');
            })
            ->distinct()
            ->count('mp_incomes.platform_order_id');

        $unmatchedOrders = max(0, $totalOrders - $matchedOrders);

        // Audit sums
        $audit = (clone $baseQ)->selectRaw('
            SUM(mp_incomes.net_payout_actual) as net_sum,
            SUM(mp_incomes.platform_fee_total) as fee_sum,
            SUM(mp_incomes.refund_total) as refund_sum,
            SUM(CASE WHEN mp_incomes.net_payout_actual < 0 THEN 1 ELSE 0 END) as net_negative_count,
            SUM(CASE WHEN mp_incomes.refund_total != 0 THEN 1 ELSE 0 END) as refund_orders_count
        ')->first();

        // Join shipment agg + shipment_items agg (safe, no dup)
        $shipAgg = $this->shipmentAggSubquery();
        $itemsAgg = $this->shipmentItemsAggSubquery();

        $listQ = (clone $baseQ)
            ->leftJoinSub($shipAgg, 'sx', function ($j) {
                $j->on('sx.store_id', '=', 'mp_incomes.store_id')
                    ->on('sx.channel', '=', 'mp_incomes.channel')
                    ->on('sx.platform_order_id', '=', 'mp_incomes.platform_order_id');
            })
            ->leftJoinSub($itemsAgg, 'sia', function ($j) {
                $j->on('sia.mp_shipment_id', '=', 'sx.mp_shipment_id');
            });

        if ($filters['only'] === 'matched') {
            $listQ->whereNotNull('sx.mp_shipment_id');
        } elseif ($filters['only'] === 'unmatched') {
            $listQ->whereNull('sx.mp_shipment_id');
        }

        $items = $listQ
            ->select([
                'mp_incomes.id',
                'mp_incomes.import_batch_id',
                'mp_incomes.channel',
                'mp_incomes.store_id',
                'mp_incomes.platform_order_id',
                'mp_incomes.released_at',
                'mp_incomes.released_date',
                'mp_incomes.platform_fee_total',
                'mp_incomes.refund_total',
                'mp_incomes.net_payout_actual',
                'mp_incomes.source_file',
                'mp_incomes.created_at',

                DB::raw('sx.mp_shipment_id as mp_shipment_id'),
                DB::raw('CASE WHEN sx.mp_shipment_id IS NULL THEN 0 ELSE 1 END as is_matched'),
                DB::raw('COALESCE(sia.items_qty_sum, 0) as ship_items_qty_sum'),
                DB::raw('COALESCE(sia.items_rows_count, 0) as ship_items_rows_count'),
            ])
            ->orderByDesc('mp_incomes.released_at')
            ->orderByDesc('mp_incomes.id')
            ->paginate($perPage)
            ->withQueryString();

        return view('imports.marketplace_income.orders', [
            'stores' => $stores,
            'items' => $items,

            // filters for blade
            'channel' => $filters['channel'],
            'storeId' => $filters['store_id'],
            'q' => $filters['q'],
            'dateFrom' => $filters['date_from'],
            'dateTo' => $filters['date_to'],
            'batch' => $filters['batch'],
            'only' => $filters['only'],

            // kpi
            'totalOrders' => $totalOrders,
            'matchedOrders' => $matchedOrders,
            'unmatchedOrders' => $unmatchedOrders,
            'audit' => $audit,
        ]);
    }

    /* ============================================================
     * CREATE
     * ============================================================ */
    public function create(Request $request): View
    {
        $stores = $this->storesList();

        $draftId = (string) $request->get('draft_id', '');
        $draft = $draftId !== '' ? session($this->draftKey($draftId)) : null;

        return view('imports.marketplace_income.create', compact('stores', 'draft', 'draftId'));
    }

    /* ============================================================
     * PREVIEW
     * ============================================================ */
    public function preview(Request $request, MpIncomeImportService $svc): View | RedirectResponse
    {
        $data = $request->validate([
            'channel' => ['required', 'in:shopee,tiktok'],
            'store_id' => ['required', 'integer', 'min:1', 'exists:stores,id'],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        // cleanup old draft (if re-upload)
        $oldDraftId = (string) $request->get('draft_id', '');
        if ($oldDraftId !== '') {
            $old = session($this->draftKey($oldDraftId));
            if ($old && !empty($old['disk']) && !empty($old['stored_path'])) {
                Storage::disk((string) $old['disk'])->delete((string) $old['stored_path']);
            }
            session()->forget($this->draftKey($oldDraftId));
        }

        $channel = strtolower(trim($data['channel']));
        $storeId = (int) $data['store_id'];

        $file = $request->file('file');
        $sourceFile = $file->getClientOriginalName();

        $path = $file->store("imports/marketplace_income/{$channel}", $this->disk);
        $abs = Storage::disk($this->disk)->path($path);

        if (!is_file($abs) || !is_readable($abs)) {
            abort(500, "Upload tersimpan tapi file tidak ditemukan / tidak bisa dibaca. disk={$this->disk} rel={$path} abs={$abs}");
        }

        $res = $svc->import($channel, $abs, $sourceFile, $storeId, true);

        $stats = $res['stats'] ?? [];
        $sample = $res['sample'] ?? [];

        $draftId = (string) Str::uuid();

        session([
            $this->draftKey($draftId) => [
                'mode' => 'income',
                'disk' => $this->disk,
                'stored_path' => $path,

                'draft_id' => $draftId,
                'created_at' => now()->toDateTimeString(),

                'channel' => $channel,
                'store_id' => $storeId,
                'source_file' => $sourceFile,

                'stats' => $stats,
                'sample' => $sample,
            ],
        ]);

        return view('imports.marketplace_income.preview', [
            'draft_id' => $draftId,
            'channel' => $channel,
            'store_id' => $storeId,
            'source_file' => $sourceFile,
            'stats' => $stats,
            'sample' => $sample,
            'stored_path' => $path,
        ]);
    }

    /* ============================================================
     * DRAFT (RESUME)
     * ============================================================ */
    public function draft(Request $request, MpIncomeImportService $svc): View | RedirectResponse
    {
        $draftId = (string) $request->get('draft_id', '');
        if ($draftId === '') {
            return redirect()->route('imports.marketplace_income.create')->with('error', 'Draft ID tidak ditemukan.');
        }

        $draft = session($this->draftKey($draftId));
        if (!$draft || ($draft['mode'] ?? '') !== 'income') {
            return redirect()->route('imports.marketplace_income.create')->with('error', 'Tidak ada draft income yang bisa dilanjutkan.');
        }

        $fallback = [
            'draft_id' => $draftId,
            'channel' => $draft['channel'] ?? '',
            'store_id' => $draft['store_id'] ?? 0,
            'source_file' => $draft['source_file'] ?? '-',
            'stats' => $draft['stats'] ?? [],
            'sample' => $draft['sample'] ?? [],
            'stored_path' => $draft['stored_path'] ?? '',
        ];

        $disk = (string) ($draft['disk'] ?? $this->disk);
        $storedPath = (string) ($draft['stored_path'] ?? '');

        if ($storedPath === '' || !Storage::disk($disk)->exists($storedPath)) {
            return view('imports.marketplace_income.preview', $fallback)->with('error', 'File draft tidak ditemukan di storage. Silakan upload ulang.');
        }

        try {
            $abs = Storage::disk($disk)->path($storedPath);

            $res = $svc->import(
                (string) ($draft['channel'] ?? ''),
                $abs,
                (string) ($draft['source_file'] ?? 'draft.xlsx'),
                (int) ($draft['store_id'] ?? 0),
                true
            );

            return view('imports.marketplace_income.preview', array_merge($fallback, [
                'stats' => $res['stats'] ?? ($draft['stats'] ?? []),
                'sample' => $res['sample'] ?? ($draft['sample'] ?? []),
            ]));
        } catch (\Throwable $e) {
            report($e);
            return view('imports.marketplace_income.preview', $fallback)->with('error', 'Draft dibuka dari cache (file tidak diproses ulang).');
        }
    }

    /* ============================================================
     * COMMIT
     * ============================================================ */
    public function commit(Request $request, MpIncomeImportService $svc): RedirectResponse
    {
        $draftId = (string) $request->input('draft_id', '');
        if ($draftId === '') {
            return redirect()->route('imports.marketplace_income.create')->with('error', 'Draft ID tidak ditemukan. Preview ulang.');
        }

        $key = $this->draftKey($draftId);
        $draft = session($key);

        if (!$draft || ($draft['mode'] ?? '') !== 'income') {
            return redirect()->route('imports.marketplace_income.create')->with('error', 'Tidak ada draft preview untuk di-commit.');
        }

        $lock = Cache::lock("lock:mp_income_commit:{$draftId}", 120);
        if (!$lock->get()) {
            return redirect()->route('imports.marketplace_income.create', ['draft_id' => $draftId])
                ->with('error', 'Commit sedang diproses. Coba beberapa detik lagi.');
        }

        try {
            $channel = (string) ($draft['channel'] ?? '');
            $storeId = (int) ($draft['store_id'] ?? 0);
            $storedPath = (string) ($draft['stored_path'] ?? '');
            $sourceFile = (string) ($draft['source_file'] ?? '');
            $disk = (string) ($draft['disk'] ?? $this->disk);

            if ($channel === '' || $storeId <= 0 || $storedPath === '') {
                return redirect()->route('imports.marketplace_income.create')->with('error', 'Draft tidak lengkap. Upload ulang.');
            }

            if (!Storage::disk($disk)->exists($storedPath)) {
                return redirect()->route('imports.marketplace_income.create')->with('error', "File draft tidak ditemukan di storage. Upload ulang.");
            }

            $abs = Storage::disk($disk)->path($storedPath);

            $res = $svc->import($channel, $abs, $sourceFile, $storeId, false);
            $stats = $res['stats'] ?? [];

            session()->forget($key);
            Storage::disk($disk)->delete($storedPath);

            return redirect()
                ->route('imports.marketplace_income.index')
                ->with('success',
                    "Import income selesai. Orders=" . (int) ($stats['orders_parsed'] ?? 0)
                    . " incomes=" . (int) ($stats['incomes_upserted'] ?? 0)
                    . " matched=" . (int) ($stats['orders_matched_shipments'] ?? 0)
                    . " updatedShip=" . (int) ($stats['shipments_updated'] ?? 0)
                    . " batch=" . ($stats['batch'] ?? '-')
                );
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('imports.marketplace_income.create', ['draft_id' => $draftId])
                ->with('error', 'Import gagal: ' . $e->getMessage());
        } finally {
            optional($lock)->release();
        }
    }

    /* ============================================================
     * CANCEL
     * ============================================================ */
    public function cancel(Request $request): RedirectResponse
    {
        $draftId = (string) $request->input('draft_id', (string) $request->get('draft_id', ''));
        if ($draftId === '') {
            return redirect()->route('imports.marketplace_income.create')->with('success', 'Draft income dibatalkan.');
        }

        $key = $this->draftKey($draftId);
        $draft = session($key);

        if ($draft) {
            $disk = (string) ($draft['disk'] ?? $this->disk);
            $path = (string) ($draft['stored_path'] ?? '');
            if ($path !== '') {
                Storage::disk($disk)->delete($path);
            }
        }

        session()->forget($key);

        return redirect()->route('imports.marketplace_income.create')->with('success', 'Draft income dibatalkan.');
    }

    /* ============================================================
     * SHOW ORDER (items)
     * - 1 income => 1 shipment primary (MIN(id)) + shipment items
     * ============================================================ */
    public function showOrder(Request $request, MpIncome $income): View
    {
        $shipment = MpShipment::query()
            ->where('store_id', $income->store_id)
            ->where('channel', $income->channel)
            ->where('platform_order_id', $income->platform_order_id)
            ->orderBy('id')
            ->first();

        $shipmentItems = collect();
        if ($shipment) {
            $shipmentItems = MpShipmentItem::query()
                ->where('mp_shipment_id', $shipment->id)
                ->orderBy('id')
                ->get();
        }

        return view('imports.marketplace_income.show_order', [
            'income' => $income,
            'shipment' => $shipment,
            'shipmentItems' => $shipmentItems,
        ]);
    }

    /* ============================================================
     * SHOW BATCH
     * ============================================================ */
    public function showBatch(Request $request, string $batch): View
    {
        $stores = $this->storesList();
        [$filters, $perPage] = $this->readFilters($request);
        // batch param dari URL wajib dipakai (override)
        $filters['batch'] = $batch;

        // Summary per channel/store (for this batch)
        $summaryRows = MpIncome::query()
            ->from('mp_incomes')
            ->selectRaw('
                mp_incomes.import_batch_id,
                mp_incomes.channel,
                mp_incomes.store_id,
                COUNT(*) as orders_count,
                SUM(mp_incomes.net_payout_actual) as net_sum,
                SUM(mp_incomes.platform_fee_total) as fee_sum,
                SUM(mp_incomes.refund_total) as refund_sum,
                MIN(mp_incomes.released_date) as released_date_min,
                MAX(mp_incomes.released_date) as released_date_max,
                MAX(mp_incomes.source_file) as source_file_max,
                MAX(mp_incomes.created_at) as imported_at_max
            ')
            ->where('mp_incomes.import_batch_id', $batch)
            ->when($filters['channel'] !== '', fn(Builder $q) => $q->where('mp_incomes.channel', $filters['channel']))
            ->when($filters['store_id'] > 0, fn(Builder $q) => $q->where('mp_incomes.store_id', $filters['store_id']))
            ->groupBy('mp_incomes.import_batch_id', 'mp_incomes.channel', 'mp_incomes.store_id')
            ->get();

        // base incomes in this batch
        $baseQ = $this->baseIncomeQuery()
            ->where('mp_incomes.import_batch_id', $batch)
            ->when($filters['channel'] !== '', fn(Builder $q) => $q->where('mp_incomes.channel', $filters['channel']))
            ->when($filters['store_id'] > 0, fn(Builder $q) => $q->where('mp_incomes.store_id', $filters['store_id']))
            ->when($filters['q'] !== '', fn(Builder $q) => $q->where('mp_incomes.platform_order_id', 'like', "%{$filters['q']}%"));

        // KPI
        $totalOrders = (clone $baseQ)->distinct()->count('mp_incomes.platform_order_id');

        $matchedOrders = (clone $baseQ)
            ->join('mp_shipments as s', function ($j) {
                $j->on('s.store_id', '=', 'mp_incomes.store_id')
                    ->on('s.channel', '=', 'mp_incomes.channel')
                    ->on('s.platform_order_id', '=', 'mp_incomes.platform_order_id');
            })
            ->distinct()
            ->count('mp_incomes.platform_order_id');

        $unmatchedOrders = max(0, $totalOrders - $matchedOrders);

        $audit = (clone $baseQ)->selectRaw('
            SUM(mp_incomes.net_payout_actual) as net_sum,
            SUM(mp_incomes.platform_fee_total) as fee_sum,
            SUM(mp_incomes.refund_total) as refund_sum,
            SUM(CASE WHEN mp_incomes.net_payout_actual < 0 THEN 1 ELSE 0 END) as net_negative_count,
            SUM(CASE WHEN mp_incomes.refund_total != 0 THEN 1 ELSE 0 END) as refund_orders_count
        ')->first();

        // Join shipment agg + items agg
        $shipAgg = $this->shipmentAggSubquery();
        $itemsAgg = $this->shipmentItemsAggSubquery();

        $items = (clone $baseQ)
            ->leftJoinSub($shipAgg, 'sx', function ($j) {
                $j->on('sx.store_id', '=', 'mp_incomes.store_id')
                    ->on('sx.channel', '=', 'mp_incomes.channel')
                    ->on('sx.platform_order_id', '=', 'mp_incomes.platform_order_id');
            })
            ->leftJoinSub($itemsAgg, 'sia', function ($j) {
                $j->on('sia.mp_shipment_id', '=', 'sx.mp_shipment_id');
            })
            ->select([
                'mp_incomes.*',
                DB::raw('sx.mp_shipment_id as mp_shipment_id'),
                DB::raw('CASE WHEN sx.mp_shipment_id IS NULL THEN 0 ELSE 1 END as is_matched'),
                DB::raw('COALESCE(sia.items_qty_sum, 0) as ship_items_qty_sum'),
                DB::raw('COALESCE(sia.items_rows_count, 0) as ship_items_rows_count'),
            ])
            ->orderByDesc('mp_incomes.released_at')
            ->orderByDesc('mp_incomes.id')
            ->paginate($perPage)
            ->withQueryString();

        // Expand shipment_items for shipments in this page (1 query)
        $shipIds = $items->pluck('mp_shipment_id')->filter()->unique()->values()->all();

        $shipmentItemsByShip = collect();
        if (!empty($shipIds)) {
            $shipmentItemsByShip = MpShipmentItem::query()
                ->whereIn('mp_shipment_id', $shipIds)
                ->orderBy('id')
                ->get([
                    'id',
                    'mp_shipment_id',
                    'sku_code',
                    'sku_parent',
                    'product_name',
                    'variant_name',
                    'qty',
                    'unit_price',
                    'subtotal',
                ])
                ->groupBy('mp_shipment_id');
        }

        return view('imports.marketplace_income.show_batch', [
            'batch' => $batch,
            'stores' => $stores,
            'summaryRows' => $summaryRows,
            'items' => $items,
            'shipmentItemsByShip' => $shipmentItemsByShip,

            'q' => $filters['q'],
            'channel' => $filters['channel'],
            'storeId' => $filters['store_id'],

            'totalOrders' => $totalOrders,
            'matchedOrders' => $matchedOrders,
            'unmatchedOrders' => $unmatchedOrders,
            'audit' => $audit,
        ]);
    }

    /* ============================================================
     * APPLY (re-apply batch)
     * ============================================================ */
    public function apply(Request $request, string $batch, ApplyIncomeService $svc): RedirectResponse
    {
        $data = $request->validate([
            'channel' => ['nullable', 'in:shopee,tiktok'],
            'store_id' => ['nullable', 'integer', 'min:0'],
        ]);

        $channel = strtolower(trim((string) ($data['channel'] ?? '')));
        $storeId = (int) ($data['store_id'] ?? 0);

        $lock = Cache::lock("lock:mp_income_apply:{$batch}:{$channel}:{$storeId}", 120);
        if (!$lock->get()) {
            return back()->with('error', 'Re-apply sedang diproses. Coba beberapa detik lagi.');
        }

        try {
            $stats = $svc->applyBatch(
                batchId: $batch,
                channel: $channel !== '' ? $channel : null,
                storeId: $storeId > 0 ? $storeId : null
            );

            return back()->with(
                'success',
                'Re-apply selesai • updatedShip=' . (int) ($stats['shipments_updated'] ?? 0)
                . ' • matchedOrders=' . (int) ($stats['orders_matched'] ?? 0)
                . ' • multiShipOrders=' . (int) ($stats['orders_with_multi_shipments'] ?? 0)
            );
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Re-apply gagal: ' . $e->getMessage());
        } finally {
            optional($lock)->release();
        }
    }

    /* ============================================================
     * PRIVATE HELPERS
     * ============================================================ */

    private function storesList()
    {
        return Store::query()->select('id', 'name')->orderBy('name')->get();
    }

    private function baseIncomeQuery(): Builder
    {
        return MpIncome::query()->from('mp_incomes');
    }

    private function shipmentAggSubquery()
    {
        // 1 order => 1 shipment primary (MIN id)
        return DB::table('mp_shipments as s')
            ->selectRaw('MIN(s.id) as mp_shipment_id, s.store_id, s.channel, s.platform_order_id')
            ->groupBy('s.store_id', 's.channel', 's.platform_order_id');
    }

    private function shipmentItemsAggSubquery()
    {
        return DB::table('mp_shipment_items as si')
            ->selectRaw('si.mp_shipment_id, SUM(COALESCE(si.qty, 0)) as items_qty_sum, COUNT(*) as items_rows_count')
            ->groupBy('si.mp_shipment_id');
    }

    /**
     * Read + normalize filters in one place.
     * Return: [filters, perPage]
     */
    private function readFilters(Request $request): array
    {
        $channel = strtolower(trim((string) $request->get('channel', '')));
        if (!in_array($channel, ['', 'shopee', 'tiktok'], true)) {
            $channel = '';
        }

        $only = strtolower(trim((string) $request->get('only', 'all')));
        if (!in_array($only, ['all', 'matched', 'unmatched'], true)) {
            $only = 'all';
        }

        $perPage = (int) $request->get('per_page', self::DEFAULT_PER_PAGE);
        if ($perPage <= 0) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        if ($perPage > self::MAX_PER_PAGE) {
            $perPage = self::MAX_PER_PAGE;
        }

        return [[
            'channel' => $channel,
            'store_id' => (int) $request->get('store_id', 0),
            'q' => trim((string) $request->get('q', '')),
            'date_from' => (string) $request->get('date_from', ''),
            'date_to' => (string) $request->get('date_to', ''),
            'batch' => trim((string) $request->get('batch', '')),
            'only' => $only,
        ], $perPage];
    }
}
