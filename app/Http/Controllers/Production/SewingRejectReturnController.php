<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Item;
use App\Models\ItemRole;
use App\Models\SewingRejectConversion;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SewingRejectReturnController extends Controller
{
    public function __construct(private InventoryService $inventory)
    {
    }

    public function index(Request $request): View
    {
        $filters = [
            'operator_id' => $request->get('operator_id'),
            'q' => trim((string) $request->get('q')),
        ];

        $rows = $this->rows($filters);

        $perPage = 20;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $items = $rows->forPage($page, $perPage)->values();
        $rejects = new LengthAwarePaginator(
            $items,
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $operators = Employee::query()
            ->whereIn('id', $rows->pluck('operator_id')->filter()->unique()->values()->all())
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return view('production.sewing_reject_returns.index', [
            'rejects' => $rejects,
            'operators' => $operators,
            'filters' => $filters,
            'totalRemaining' => (float) $rows->sum('remaining_qty'),
            'totalReject' => (float) $rows->sum('qty_reject'),
            'totalReworked' => (float) $rows->sum('qty_reworked'),
            'totalConverted' => (float) $rows->sum('qty_converted'),
            'totalFromFinishing' => (int) $rows->where('source_kind', 'finishing')->count(),
            'totalFromSewing' => (int) $rows->where('source_kind', 'sewing_return')->count(),
            'totalRows' => (int) $rows->count(),
        ]);
    }

    public function convert(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'source_reject_return_line_id' => ['nullable', 'integer', 'exists:sewing_return_lines,id'],
            'source_finishing_job_line_id' => ['nullable', 'integer', 'exists:finishing_job_lines,id'],
            'qty' => ['required', 'numeric', 'min:0.001'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $sourceRejectLineId = (int) ($validated['source_reject_return_line_id'] ?? 0);
        $sourceFinishingLineId = (int) ($validated['source_finishing_job_line_id'] ?? 0);

        if (($sourceRejectLineId > 0) === ($sourceFinishingLineId > 0)) {
            throw ValidationException::withMessages([
                'source' => 'Pilih salah satu sumber reject yang valid.',
            ]);
        }

        $date = Carbon::parse($validated['date'])->toDateString();
        $qty = (float) $validated['qty'];

        return DB::transaction(function () use ($sourceRejectLineId, $sourceFinishingLineId, $qty, $date, $validated): RedirectResponse {
            $rejSew = Warehouse::query()->where('code', 'REJ-SEW')->first();
            if (!$rejSew) {
                throw ValidationException::withMessages(['qty' => 'Gudang REJ-SEW belum tersedia.']);
            }
            $whRts = Warehouse::query()->where('code', 'WH-RTS')->first();
            if (!$whRts) {
                throw ValidationException::withMessages(['qty' => 'Gudang WH-RTS belum tersedia.']);
            }

            $source = $this->resolveConversionSource($sourceRejectLineId, $sourceFinishingLineId, (int) $rejSew->id, true);
            if (!$source) {
                throw ValidationException::withMessages(['qty' => 'Baris reject jahit tidak ditemukan atau sudah selesai.']);
            }

            if ($qty > (float) $source->remaining_qty + 0.000001) {
                throw ValidationException::withMessages([
                    'qty' => "Qty {$source->sku} melebihi sisa reject. Sisa: " . number_format((float) $source->remaining_qty, 2, ',', '.') . ' pcs.',
                ]);
            }

            $rejectItem = $this->resolveRejectItem((int) $source->item_id);

            $conversion = SewingRejectConversion::create([
                'code' => SewingRejectConversion::generateCode($date),
                'date' => $date,
                'status' => 'posted',
                'source_reject_return_line_id' => $sourceRejectLineId ?: null,
                'source_finishing_job_line_id' => $sourceFinishingLineId ?: null,
                'item_id' => (int) $source->item_id,
                'reject_item_id' => (int) $rejectItem->id,
                'cutting_job_bundle_id' => (int) $source->bundle_id ?: null,
                'qty' => $qty,
                'created_by_user_id' => auth()->id(),
                'notes' => trim((string) ($validated['notes'] ?? '')) ?: null,
            ]);

            $unitCost = (float) $this->inventory->getItemIncomingUnitCost((int) $rejSew->id, (int) $source->item_id);

            $this->inventory->stockOut(
                warehouseId: (int) $rejSew->id,
                itemId: (int) $source->item_id,
                qty: $qty,
                date: $date,
                sourceType: 'sewing_reject_convert',
                sourceId: (int) $conversion->id,
                notes: "Tidak bisa diperbaiki {$conversion->code} OUT REJ-SEW",
                allowNegative: false,
                lotId: null,
                unitCostOverride: $unitCost > 0 ? $unitCost : null,
                affectLotCost: false,
                cuttingJobBundleId: (int) $source->bundle_id ?: null,
            );

            $this->inventory->stockIn(
                warehouseId: (int) $whRts->id,
                itemId: (int) $rejectItem->id,
                qty: $qty,
                date: $date,
                sourceType: 'sewing_reject_convert',
                sourceId: (int) $conversion->id,
                notes: "Tidak bisa diperbaiki {$conversion->code} IN WH-RTS {$rejectItem->code}",
                lotId: null,
                unitCost: $unitCost > 0 ? $unitCost : null,
                affectLotCost: false,
                cuttingJobBundleId: (int) $source->bundle_id ?: null,
            );

            return redirect()
                ->route('production.sewing.reject_returns.index')
                ->with('status', "{$source->sku} {$qty} pcs dikonversi ke {$rejectItem->code} dan masuk WH-RTS.");
        });
    }

    private function rows(array $filters): \Illuminate\Support\Collection
    {
        $rejSew = Warehouse::query()->where('code', 'REJ-SEW')->first();
        if (!$rejSew) {
            return collect();
        }

        $reworkedSub = DB::table('sewing_return_lines as rw')
            ->join('sewing_returns as srw', 'srw.id', '=', 'rw.sewing_return_id')
            ->whereNull('srw.voided_at')
            ->where('rw.source_type', 'reject_sewing_rework')
            ->whereNotNull('rw.source_reject_return_line_id')
            ->groupBy('rw.source_reject_return_line_id')
            ->selectRaw('rw.source_reject_return_line_id, SUM(COALESCE(rw.qty_ok,0)) as qty_reworked');

        $finishingReworkedSub = DB::table('sewing_return_lines as rw')
            ->join('sewing_returns as srw', 'srw.id', '=', 'rw.sewing_return_id')
            ->whereNull('srw.voided_at')
            ->where('rw.source_type', 'finishing_sewing_rework')
            ->whereNotNull('rw.source_finishing_job_line_id')
            ->groupBy('rw.source_finishing_job_line_id')
            ->selectRaw('rw.source_finishing_job_line_id, SUM(COALESCE(rw.qty_ok,0)) as qty_reworked');

        $convertedSub = DB::table('sewing_reject_conversions')
            ->where('status', 'posted')
            ->whereNotNull('source_reject_return_line_id')
            ->groupBy('source_reject_return_line_id')
            ->selectRaw('source_reject_return_line_id, SUM(qty) as qty_converted');

        $finishingConvertedSub = DB::table('sewing_reject_conversions')
            ->where('status', 'posted')
            ->whereNotNull('source_finishing_job_line_id')
            ->groupBy('source_finishing_job_line_id')
            ->selectRaw('source_finishing_job_line_id, SUM(qty) as qty_converted');

        $rows = DB::table('sewing_return_lines as rl')
            ->join('sewing_returns as r', 'r.id', '=', 'rl.sewing_return_id')
            ->join('sewing_pickup_lines as pl', 'pl.id', '=', 'rl.sewing_pickup_line_id')
            ->join('items as it', 'it.id', '=', 'pl.finished_item_id')
            ->leftJoin('item_categories as cat', 'cat.id', '=', 'it.item_category_id')
            ->leftJoin('employees as e', 'e.id', '=', 'r.operator_id')
            ->leftJoin('inventory_stocks as st', function ($join) use ($rejSew) {
                $join->on('st.item_id', '=', 'pl.finished_item_id')
                    ->where('st.warehouse_id', '=', $rejSew->id);
            })
            ->leftJoinSub($reworkedSub, 'rw_sum', 'rw_sum.source_reject_return_line_id', '=', 'rl.id')
            ->leftJoinSub($convertedSub, 'cv_sum', 'cv_sum.source_reject_return_line_id', '=', 'rl.id')
            ->where('rl.qty_reject', '>', 0)
            ->whereNull('r.voided_at')
            ->when($filters['operator_id'], fn($q, $opId) => $q->where('r.operator_id', $opId))
            ->selectRaw("
                rl.id as line_id,
                'sewing_return' as source_kind,
                rl.qty_reject as qty_reject,
                COALESCE(rw_sum.qty_reworked,0) as qty_reworked,
                COALESCE(cv_sum.qty_converted,0) as qty_converted,
                COALESCE(st.qty,0) as stock_rej_sew,
                DATE(r.date) as reject_date,
                r.code as reject_code,
                r.operator_id as operator_id,
                COALESCE(e.code,'-') as operator_code,
                COALESCE(e.name,'-') as operator_name,
                pl.id as sewing_pickup_line_id,
                pl.cutting_job_bundle_id as bundle_id,
                it.id as item_id,
                it.code as sku,
                it.name as product_name,
                COALESCE(cat.code,'REJECT') as category_code,
                COALESCE(cat.name,'-') as category,
                COALESCE(NULLIF(rl.notes,''),'-') as notes
            ")
            ->orderByDesc('r.date')
            ->orderByDesc('rl.id')
            ->get()
            ->map(function ($r) {
                $reject = (float) $r->qty_reject;
                $reworked = (float) $r->qty_reworked;
                $converted = (float) $r->qty_converted;
                $stock = max((float) $r->stock_rej_sew, 0.0);
                $r->remaining_qty = min(max($reject - $reworked - $converted, 0.0), $stock);
                return $this->decorateRow($r);
            })
            ->filter(fn($r) => (float) $r->remaining_qty > 0.000001)
            ->values();

        $finishingRows = DB::table('finishing_job_lines as fl')
            ->join('finishing_jobs as f', 'f.id', '=', 'fl.finishing_job_id')
            ->join('items as it', 'it.id', '=', 'fl.item_id')
            ->leftJoin('item_categories as cat', 'cat.id', '=', 'it.item_category_id')
            ->leftJoin('employees as e', 'e.id', '=', 'fl.sewing_operator_id')
            ->leftJoin('inventory_stocks as st', function ($join) use ($rejSew) {
                $join->on('st.item_id', '=', 'fl.item_id')
                    ->where('st.warehouse_id', '=', $rejSew->id);
            })
            ->leftJoinSub($finishingReworkedSub, 'frw_sum', 'frw_sum.source_finishing_job_line_id', '=', 'fl.id')
            ->leftJoinSub($finishingConvertedSub, 'fcv_sum', 'fcv_sum.source_finishing_job_line_id', '=', 'fl.id')
            ->where('fl.qty_reject', '>', 0)
            ->where('fl.reject_cause', 'sewing')
            ->where('f.status', 'posted')
            ->when($filters['operator_id'], fn($q, $opId) => $q->where('fl.sewing_operator_id', $opId))
            ->selectRaw("
                fl.id as line_id,
                'finishing' as source_kind,
                fl.qty_reject as qty_reject,
                COALESCE(frw_sum.qty_reworked,0) as qty_reworked,
                COALESCE(fcv_sum.qty_converted,0) as qty_converted,
                COALESCE(st.qty,0) as stock_rej_sew,
                DATE(f.date) as reject_date,
                f.code as reject_code,
                fl.sewing_operator_id as operator_id,
                COALESCE(e.code,'-') as operator_code,
                COALESCE(e.name, NULLIF(fl.sewing_operator_name,''), '-') as operator_name,
                NULL as sewing_pickup_line_id,
                fl.bundle_id as bundle_id,
                it.id as item_id,
                it.code as sku,
                it.name as product_name,
                COALESCE(cat.code,'REJECT') as category_code,
                COALESCE(cat.name,'-') as category,
                COALESCE(NULLIF(fl.reject_notes,''), 'Dari finishing') as notes
            ")
            ->orderByDesc('f.date')
            ->orderByDesc('fl.id')
            ->get()
            ->map(function ($r) {
                $reject = (float) $r->qty_reject;
                $reworked = (float) $r->qty_reworked;
                $converted = (float) $r->qty_converted;
                $stock = max((float) $r->stock_rej_sew, 0.0);
                $r->remaining_qty = min(max($reject - $reworked - $converted, 0.0), $stock);
                return $this->decorateRow($r);
            })
            ->filter(fn($r) => (float) $r->remaining_qty > 0.000001)
            ->values();

        $rows = $rows->concat($finishingRows)->values();

        if ($filters['q']) {
            $needle = mb_strtolower($filters['q']);
            $rows = $rows->filter(function ($r) use ($needle) {
                $hay = mb_strtolower(trim($r->sku . ' ' . $r->product_name . ' ' . $r->category . ' ' . $r->operator_code . ' ' . $r->operator_name . ' ' . $r->notes));
                return str_contains($hay, $needle);
            })->values();
        }

        return $rows;
    }

    private function decorateRow(object $row): object
    {
        $row->source_label = ($row->source_kind ?? '') === 'finishing'
            ? 'Dari Finishing'
            : 'Dari Setor Jahit';

        $row->source_badge = ($row->source_kind ?? '') === 'finishing'
            ? 'FIN'
            : 'SR';

        $categoryCode = Str::upper(Str::slug((string) ($row->category_code ?: 'REJECT'), '-'));
        $row->reject_sku = trim($categoryCode, '-') . '-RJCT';

        $reworked = (float) ($row->qty_reworked ?? 0);
        $converted = (float) ($row->qty_converted ?? 0);
        $remaining = (float) ($row->remaining_qty ?? 0);

        if ($remaining <= 0.000001) {
            $row->status_label = 'Selesai';
            $row->status_class = 'done';
        } elseif ($reworked > 0.000001 || $converted > 0.000001) {
            $row->status_label = 'Sebagian';
            $row->status_class = 'partial';
        } else {
            $row->status_label = 'Belum disetor';
            $row->status_class = 'open';
        }

        try {
            $row->age_days = now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($row->reject_date)->startOfDay(), false) * -1;
        } catch (\Throwable) {
            $row->age_days = null;
        }

        return $row;
    }

    private function resolveConversionSource(int $sourceRejectLineId, int $sourceFinishingLineId, int $rejSewWarehouseId, bool $lock = false): ?object
    {
        if ($sourceRejectLineId > 0) {
            $convertedSub = DB::table('sewing_reject_conversions')
                ->where('status', 'posted')
                ->where('source_reject_return_line_id', $sourceRejectLineId)
                ->selectRaw('source_reject_return_line_id, SUM(qty) as qty_converted')
                ->groupBy('source_reject_return_line_id');

            $reworkedSub = DB::table('sewing_return_lines as rw')
                ->join('sewing_returns as srw', 'srw.id', '=', 'rw.sewing_return_id')
                ->whereNull('srw.voided_at')
                ->where('rw.source_type', 'reject_sewing_rework')
                ->where('rw.source_reject_return_line_id', $sourceRejectLineId)
                ->selectRaw('rw.source_reject_return_line_id, SUM(COALESCE(rw.qty_ok,0)) as qty_reworked')
                ->groupBy('rw.source_reject_return_line_id');

            $query = DB::table('sewing_return_lines as rl')
                ->join('sewing_returns as r', 'r.id', '=', 'rl.sewing_return_id')
                ->join('sewing_pickup_lines as pl', 'pl.id', '=', 'rl.sewing_pickup_line_id')
                ->join('items as it', 'it.id', '=', 'pl.finished_item_id')
                ->leftJoin('inventory_stocks as st', function ($join) use ($rejSewWarehouseId) {
                    $join->on('st.item_id', '=', 'pl.finished_item_id')
                        ->where('st.warehouse_id', '=', $rejSewWarehouseId);
                })
                ->leftJoinSub($reworkedSub, 'rw_sum', 'rw_sum.source_reject_return_line_id', '=', 'rl.id')
                ->leftJoinSub($convertedSub, 'cv_sum', 'cv_sum.source_reject_return_line_id', '=', 'rl.id')
                ->where('rl.id', $sourceRejectLineId)
                ->where('rl.qty_reject', '>', 0)
                ->whereNull('r.voided_at')
                ->selectRaw("
                    rl.id as line_id,
                    'sewing_return' as source_kind,
                    pl.finished_item_id as item_id,
                    pl.cutting_job_bundle_id as bundle_id,
                    it.code as sku,
                    rl.qty_reject as qty_reject,
                    COALESCE(rw_sum.qty_reworked,0) as qty_reworked,
                    COALESCE(cv_sum.qty_converted,0) as qty_converted,
                    COALESCE(st.qty,0) as stock_rej_sew
                ");

            if ($lock) {
                $query->lockForUpdate();
            }

            $row = $query->first();
        } else {
            $convertedSub = DB::table('sewing_reject_conversions')
                ->where('status', 'posted')
                ->where('source_finishing_job_line_id', $sourceFinishingLineId)
                ->selectRaw('source_finishing_job_line_id, SUM(qty) as qty_converted')
                ->groupBy('source_finishing_job_line_id');

            $reworkedSub = DB::table('sewing_return_lines as rw')
                ->join('sewing_returns as srw', 'srw.id', '=', 'rw.sewing_return_id')
                ->whereNull('srw.voided_at')
                ->where('rw.source_type', 'finishing_sewing_rework')
                ->where('rw.source_finishing_job_line_id', $sourceFinishingLineId)
                ->selectRaw('rw.source_finishing_job_line_id, SUM(COALESCE(rw.qty_ok,0)) as qty_reworked')
                ->groupBy('rw.source_finishing_job_line_id');

            $query = DB::table('finishing_job_lines as fl')
                ->join('finishing_jobs as f', 'f.id', '=', 'fl.finishing_job_id')
                ->join('items as it', 'it.id', '=', 'fl.item_id')
                ->leftJoin('inventory_stocks as st', function ($join) use ($rejSewWarehouseId) {
                    $join->on('st.item_id', '=', 'fl.item_id')
                        ->where('st.warehouse_id', '=', $rejSewWarehouseId);
                })
                ->leftJoinSub($reworkedSub, 'rw_sum', 'rw_sum.source_finishing_job_line_id', '=', 'fl.id')
                ->leftJoinSub($convertedSub, 'cv_sum', 'cv_sum.source_finishing_job_line_id', '=', 'fl.id')
                ->where('fl.id', $sourceFinishingLineId)
                ->where('fl.qty_reject', '>', 0)
                ->where('fl.reject_cause', 'sewing')
                ->where('f.status', 'posted')
                ->selectRaw("
                    fl.id as line_id,
                    'finishing' as source_kind,
                    fl.item_id as item_id,
                    fl.bundle_id as bundle_id,
                    it.code as sku,
                    fl.qty_reject as qty_reject,
                    COALESCE(rw_sum.qty_reworked,0) as qty_reworked,
                    COALESCE(cv_sum.qty_converted,0) as qty_converted,
                    COALESCE(st.qty,0) as stock_rej_sew
                ");

            if ($lock) {
                $query->lockForUpdate();
            }

            $row = $query->first();
        }

        if (!$row) {
            return null;
        }

        $row->remaining_qty = min(
            max((float) $row->qty_reject - (float) $row->qty_reworked - (float) $row->qty_converted, 0.0),
            max((float) $row->stock_rej_sew, 0.0)
        );

        return (float) $row->remaining_qty > 0.000001 ? $row : null;
    }

    private function resolveRejectItem(int $sourceItemId): Item
    {
        $source = Item::query()
            ->with('category')
            ->findOrFail($sourceItemId);

        $category = $source->category;
        $categoryCode = $category?->code
            ? Str::upper(Str::slug($category->code, '-'))
            : Str::upper(Str::slug($category?->name ?: 'REJECT', '-'));

        // SKU standar reject: REJ-{KODE_KATEGORI}, misalnya REJ-LJR.
        // Kode lama *-RJCT tetap dipertahankan sebagai histori.
        $rejectCode = 'REJ-' . trim($categoryCode, '-');
        $rejectName = trim(($category?->name ?: $categoryCode) . ' Reject');

        $attributes = [
            'name' => $rejectName,
            'unit' => $source->unit ?: 'pcs',
            'type' => 'finished_good',
            'item_category_id' => $source->item_category_id,
            'item_role' => 'finished_good',
            'item_role_id' => ItemRole::idByCode(ItemRole::FG),
            'production_source' => Item::PRODUCTION_IN_HOUSE,
            'can_buy' => false,
            'can_make' => true,
            'default_supply_source' => Item::SUPPLY_MAKE,
            'last_purchase_price' => 0,
            'hpp' => 0,
            'base_unit_cost' => 0,
            'active' => true,
            'affects_hpp' => false,
            'default_allocation' => 'hpp',
            'is_stocked' => true,
            'hpp_behavior' => 'hpp',
        ];

        if (Schema::hasColumn('items', 'product_category_id')) {
            $attributes['product_category_id'] = $source->product_category_id;
        }

        $columns = Schema::getColumnListing('items');
        $attributes = collect($attributes)
            ->only($columns)
            ->all();

        return Item::query()->firstOrCreate(['code' => $rejectCode], $attributes);
    }
}
