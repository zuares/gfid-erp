<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\FinishingRepair;
use App\Models\FinishingRepairLine;
use App\Models\Item;
use App\Models\ItemRole;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FinishingRepairController extends Controller
{
    public function __construct(private InventoryService $inventory)
    {
    }

    public function index(): View
    {
        $repairs = FinishingRepair::query()
            ->with(['createdBy', 'lines.item'])
            ->withCount('lines')
            ->withSum('lines as total_qty_ok', 'qty_ok')
            ->withSum('lines as total_qty_reject', 'qty_reject')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20);

        return view('production.finishing_repairs.index', compact('repairs'));
    }

    public function create(): View
    {
        $lines = $this->availableRejectLines();

        return view('production.finishing_repairs.create', [
            'lines' => $lines,
            'dateValue' => now()->toDateString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'results' => ['required', 'array'],
            'results.*.finishing_job_line_id' => ['required', 'integer', 'exists:finishing_job_lines,id'],
            'results.*.qty_ok' => ['nullable', 'numeric', 'min:0'],
            'results.*.qty_reject' => ['nullable', 'numeric', 'min:0'],
            'results.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $rows = collect($validated['results'])
            ->map(fn($row) => [
                'finishing_job_line_id' => (int) ($row['finishing_job_line_id'] ?? 0),
                'qty_ok' => (float) ($row['qty_ok'] ?? 0),
                'qty_reject' => (float) ($row['qty_reject'] ?? 0),
                'notes' => trim((string) ($row['notes'] ?? '')),
            ])
            ->filter(fn($row) => $row['finishing_job_line_id'] > 0 && ($row['qty_ok'] + $row['qty_reject']) > 0.000001)
            ->values();

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages(['results' => 'Minimal isi 1 baris qty OK atau Tidak Bisa.']);
        }

        $date = Carbon::parse($validated['date'])->toDateString();

        return DB::transaction(function () use ($rows, $date, $validated): RedirectResponse {
            $rejFin = Warehouse::query()->where('code', 'REJ-FIN')->first();
            $whPrd = Warehouse::query()->where('code', 'WH-PRD')->first();

            if (!$rejFin || !$whPrd) {
                throw ValidationException::withMessages(['results' => 'Gudang REJ-FIN / WH-PRD belum lengkap.']);
            }

            $lineIds = $rows->pluck('finishing_job_line_id')->unique()->values()->all();
            $lineMap = $this->availableRejectLines($lineIds, true)->keyBy('finishing_job_line_id');

            foreach ($rows as $row) {
                $available = $lineMap->get($row['finishing_job_line_id']);
                if (!$available) {
                    throw ValidationException::withMessages(['results' => 'Ada baris reject finishing yang tidak valid atau sudah selesai.']);
                }

                $totalQty = (float) $row['qty_ok'] + (float) $row['qty_reject'];

                if ($totalQty > (float) $available->remaining_qty + 0.000001) {
                    throw ValidationException::withMessages([
                        'results' => "Qty {$available->item_code} melebihi sisa REJ-FIN. Sisa: {$available->remaining_qty}.",
                    ]);
                }
            }

            $repair = FinishingRepair::create([
                'code' => FinishingRepair::generateCode($date),
                'date' => $date,
                'status' => 'posted',
                'created_by_user_id' => auth()->id(),
                'notes' => trim((string) ($validated['notes'] ?? '')) ?: null,
            ]);

            foreach ($rows as $row) {
                $available = $lineMap->get($row['finishing_job_line_id']);
                $qtyOk = (float) $row['qty_ok'];
                $qtyReject = (float) $row['qty_reject'];
                $rejectItem = $qtyReject > 0.000001
                    ? $this->resolveRejectItem((int) $available->item_id)
                    : null;

                FinishingRepairLine::create([
                    'finishing_repair_id' => $repair->id,
                    'finishing_job_line_id' => (int) $available->finishing_job_line_id,
                    'item_id' => (int) $available->item_id,
                    'reject_item_id' => $rejectItem?->id,
                    'cutting_job_bundle_id' => (int) $available->bundle_id ?: null,
                    'qty_ok' => $qtyOk,
                    'qty_reject' => $qtyReject,
                    'notes' => $row['notes'] !== '' ? $row['notes'] : null,
                ]);

                $unitCost = (float) $this->inventory->getItemIncomingUnitCost($rejFin->id, (int) $available->item_id);

                if ($qtyOk > 0.000001) {
                    $this->inventory->stockOut(
                        warehouseId: $rejFin->id,
                        itemId: (int) $available->item_id,
                        qty: $qtyOk,
                        date: $date,
                        sourceType: 'finishing_repair',
                        sourceId: (int) $available->finishing_job_line_id,
                        notes: "Perbaikan finishing {$repair->code} OUT REJ-FIN",
                        allowNegative: false,
                        lotId: null,
                        unitCostOverride: $unitCost > 0 ? $unitCost : null,
                        affectLotCost: false,
                        cuttingJobBundleId: (int) $available->bundle_id ?: null,
                    );

                    $this->inventory->stockIn(
                        warehouseId: $whPrd->id,
                        itemId: (int) $available->item_id,
                        qty: $qtyOk,
                        date: $date,
                        sourceType: 'finishing_repair',
                        sourceId: (int) $available->finishing_job_line_id,
                        notes: "Perbaikan finishing {$repair->code} IN WH-PRD",
                        lotId: null,
                        unitCost: $unitCost > 0 ? $unitCost : null,
                        affectLotCost: false,
                        cuttingJobBundleId: (int) $available->bundle_id ?: null,
                    );
                }

                if ($qtyReject > 0.000001 && $rejectItem) {
                    $this->inventory->stockOut(
                        warehouseId: $rejFin->id,
                        itemId: (int) $available->item_id,
                        qty: $qtyReject,
                        date: $date,
                        sourceType: 'finishing_reject_convert',
                        sourceId: (int) $available->finishing_job_line_id,
                        notes: "Tidak bisa diperbaiki {$repair->code} OUT SKU asli",
                        allowNegative: false,
                        lotId: null,
                        unitCostOverride: $unitCost > 0 ? $unitCost : null,
                        affectLotCost: false,
                        cuttingJobBundleId: (int) $available->bundle_id ?: null,
                    );

                    $this->inventory->stockIn(
                        warehouseId: $rejFin->id,
                        itemId: (int) $rejectItem->id,
                        qty: $qtyReject,
                        date: $date,
                        sourceType: 'finishing_reject_convert',
                        sourceId: (int) $available->finishing_job_line_id,
                        notes: "Tidak bisa diperbaiki {$repair->code} IN {$rejectItem->code}",
                        lotId: null,
                        unitCost: $unitCost > 0 ? $unitCost : null,
                        affectLotCost: false,
                        cuttingJobBundleId: (int) $available->bundle_id ?: null,
                    );
                }
            }

            return redirect()
                ->route('production.finishing_repairs.show', $repair)
                ->with('status', "Perbaikan {$repair->code} berhasil disimpan.");
        });
    }

    public function show(FinishingRepair $finishingRepair): View
    {
        $finishingRepair->load([
            'createdBy',
            'lines.item',
            'lines.rejectItem',
            'lines.bundle',
            'lines.finishingJobLine.job',
        ]);

        return view('production.finishing_repairs.show', [
            'repair' => $finishingRepair,
        ]);
    }

    private function availableRejectLines(?array $lineIds = null, bool $lock = false)
    {
        $rejFin = Warehouse::query()->where('code', 'REJ-FIN')->first();
        if (!$rejFin) {
            return collect();
        }

        $consumedSub = DB::table('inventory_mutations')
            ->whereIn('source_type', ['finishing_repair', 'finishing_reject_convert'])
            ->where('warehouse_id', $rejFin->id)
            ->where('direction', 'out')
            ->groupBy('source_id')
            ->selectRaw('source_id as finishing_job_line_id, SUM(ABS(qty_change)) as qty_consumed');

        $query = DB::table('finishing_job_lines as fl')
            ->join('finishing_jobs as f', 'f.id', '=', 'fl.finishing_job_id')
            ->join('items as it', 'it.id', '=', 'fl.item_id')
            ->join('cutting_job_bundles as b', 'b.id', '=', 'fl.bundle_id')
            ->leftJoin('item_categories as cat', 'cat.id', '=', 'it.item_category_id')
            ->leftJoin('inventory_stocks as st', function ($join) use ($rejFin) {
                $join->on('st.item_id', '=', 'fl.item_id')
                    ->where('st.warehouse_id', '=', $rejFin->id);
            })
            ->leftJoinSub($consumedSub, 'rp', 'rp.finishing_job_line_id', '=', 'fl.id')
            ->where('f.status', 'posted')
            ->where('fl.qty_reject', '>', 0)
            ->where(function ($q) {
                $q->whereNull('fl.reject_cause')
                    ->orWhere('fl.reject_cause', 'finishing');
            })
            ->when($lineIds, fn($q) => $q->whereIn('fl.id', $lineIds))
            ->selectRaw("
                fl.id as finishing_job_line_id,
                f.code as finishing_code,
                DATE(f.date) as finishing_date,
                fl.item_id,
                fl.bundle_id,
                b.bundle_code,
                it.code as item_code,
                it.name as item_name,
                COALESCE(cat.code,'REJECT') as category_code,
                COALESCE(cat.name,'-') as category_name,
                fl.qty_reject,
                COALESCE(rp.qty_consumed,0) as qty_consumed,
                COALESCE(st.qty,0) as stock_rej_fin,
                fl.reject_reason,
                fl.reject_notes
            ")
            ->orderByDesc('f.date')
            ->orderByDesc('fl.id');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get()
            ->map(function ($row) {
                $row->remaining_qty = min(
                    max((float) $row->qty_reject - (float) $row->qty_consumed, 0.0),
                    max((float) $row->stock_rej_fin, 0.0)
                );

                return $row;
            })
            ->filter(fn($row) => (float) $row->remaining_qty > 0.000001)
            ->values();
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

        $rejectCode = trim($categoryCode, '-') . '-RJCT';
        $rejectName = trim(($category?->name ?: $categoryCode) . ' Reject');

        $attributes = [
            'name' => $rejectName,
            'unit' => $source->unit ?: 'pcs',
            'type' => 'finished_good',
            'item_category_id' => $source->item_category_id,
            'item_role' => 'finished_good',
            'item_role_id' => ItemRole::idByCode(ItemRole::FG),
            'production_source' => Item::PRODUCTION_IN_HOUSE,
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
