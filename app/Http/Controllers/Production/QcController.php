<?php

namespace App\Http\Controllers\Production;

use App\Exceptions\AutoBomLotShortageException;
use App\Http\Controllers\Controller;
use App\Models\CuttingBundleCorrection;
use App\Models\CuttingJob;
use App\Models\CuttingJobBundle;
use App\Models\CuttingQcCancellation;
use App\Models\InventoryAdjustment;
use App\Models\InventoryAdjustmentLine;
use App\Models\InventoryMutation;
use App\Models\InventoryStock;
use App\Models\ItemBomLine;
use App\Models\PieceRate;
use App\Models\QcResult;
use App\Models\SewingPickupLine;
use App\Models\SewingReturn;
use App\Models\StandaloneAccountingJournal;
use App\Models\StandaloneProductionAccountMapping;
use App\Models\Warehouse;
use App\Services\Accounting\JournalService;
use App\Services\Accounting\StandaloneProductionPostingService;
use App\Services\Production\CuttingService;
use App\Services\Production\QcService;
use App\Services\Purchasing\MaterialShortageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class QcController extends Controller
{
    public function __construct(
        protected QcService $qc,
        protected CuttingService $cutting,
        protected JournalService $journal,
        protected StandaloneProductionPostingService $standaloneProductionPosting,
        protected MaterialShortageService $materialShortages,
    ) {}

    /**
     * List QC per stage.
     */
    public function index(Request $request)
    {
        $stage = $request->get('stage', QcResult::STAGE_CUTTING);

        if (! in_array($stage, [QcResult::STAGE_CUTTING, QcResult::STAGE_SEWING, 'packing'], true)) {
            $stage = QcResult::STAGE_CUTTING;
        }

        $user = Auth::user();
        $userRole = $user->role ?? null;

        // Admin: langsung ke QC Sewing
        if ($userRole === 'admin') {
            if ($request->query('stage') !== QcResult::STAGE_SEWING) {
                return redirect()->route('production.qc.index', ['stage' => QcResult::STAGE_SEWING]);
            }

            $stage = QcResult::STAGE_SEWING;

            $records = SewingReturn::query()
                ->with([
                    'operator',
                    'lines.pickupLine.pickup',
                    'lines.pickupLine.bundle.finishedItem',
                    'lines.pickupLine.bundle.cuttingJob.lot.item',
                    'qcResults.qcUser',
                ])
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();

            return view('production.qc.index', compact('stage', 'records', 'userRole'));
        }

        $records = collect();

        switch ($stage) {
            case QcResult::STAGE_CUTTING:
                $query = CuttingJob::query()
                    ->with([
                        'warehouse',
                        'lot.item',
                        'bundles.finishedItem',
                        'bundles.qcResults' => function ($q) {
                            $q->where('stage', QcResult::STAGE_CUTTING);
                        },
                    ])
                    ->orderByDesc('date')
                    ->orderByDesc('id');

                // Non-owner: hanya tampilkan yang BELUM QC
                if ($userRole !== 'owner') {
                    $query->where('status', 'sent_to_qc');
                }

                $records = $query
                    ->paginate(20)
                    ->withQueryString();
                break;

            case QcResult::STAGE_SEWING:
                $records = SewingReturn::query()
                    ->with([
                        'operator',
                        'lines.pickupLine.pickup',
                        'lines.pickupLine.bundle.finishedItem',
                        'lines.pickupLine.bundle.cuttingJob.lot.item',
                        'qcResults.qcUser',
                    ])
                    ->orderByDesc('date')
                    ->orderByDesc('id')
                    ->paginate(20)
                    ->withQueryString();
                break;

            case 'packing':
                // nanti diisi kalau sudah ada model QC Packing
                $records = collect();
                break;
        }

        return view('production.qc.index', compact('stage', 'records', 'userRole'));
    }

    /**
     * Form QC Cutting.
     */
    public function editCutting(CuttingJob $cuttingJob)
    {
        $cuttingJob->load([
            'warehouse',
            'lot.item',
            'lots.lot.item', // multi-LOT: pivot + lot + item
            'bundles.finishedItem.category',
            'bundles.lot.item', // LOT per bundle — ditampilkan kecil di bawah nama bahan
            'bundles.qcResults' => function ($q) {
                $q->where('stage', QcResult::STAGE_CUTTING);
            },
        ]);

        // ambil QC existing per bundle
        $existingQc = QcResult::query()
            ->where('stage', QcResult::STAGE_CUTTING)
            ->where('cutting_job_id', $cuttingJob->id)
            ->get()
            ->keyBy('cutting_job_bundle_id');

        $activePickedBundleIds = SewingPickupLine::query()
            ->whereIn('cutting_job_bundle_id', $cuttingJob->bundles->pluck('id'))
            ->where('status', '!=', 'void')
            ->where('qty_bundle', '>', 0)
            ->pluck('cutting_job_bundle_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $partialCancelledBundleIds = CuttingQcCancellation::query()
            ->where('cutting_job_id', $cuttingJob->id)
            ->get(['cutting_job_bundle_id', 'metadata'])
            ->filter(fn ($audit) => ! ($audit->metadata['closed_by_full_cancel'] ?? false))
            ->pluck('cutting_job_bundle_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();
        $isOwner = Auth::user()?->isOwner() ?? false;
        $activeBundleCorrections = CuttingBundleCorrection::query()
            ->where('cutting_job_id', $cuttingJob->id)
            ->where('status', 'returned')
            ->latest('id')
            ->get()
            ->keyBy('cutting_job_bundle_id');

        $rows = [];
        foreach ($cuttingJob->bundles as $bundle) {
            $qc = $existingQc->get($bundle->id); // index berdasarkan bundle->id
            $bundleCorrection = $activeBundleCorrections->get($bundle->id);

            $rows[] = [
                'cutting_job_bundle_id' => $bundle->id,
                'bundle_no' => $bundle->bundle_no,
                'bundle_code' => $bundle->bundle_code,
                'item_code' => $bundle->finishedItem?->code,
                'item_name' => $bundle->finishedItem?->name, // ⬅️ dipakai di view (desktop)
                'lot_code' => $bundle->lot?->code,
                'qty_pcs' => $bundle->qty_pcs,
                'qty_used_fabric' => $bundle->qty_used_fabric,
                'status' => $bundle->status,
                'is_partial_cancelled' => in_array((int) $bundle->id, $partialCancelledBundleIds, true) && ! $qc,
                'qty_ok' => $qc?->qty_ok ?? $bundle->qty_pcs,
                'qty_reject' => $qc?->qty_reject ?? 0,
                'reject_reason' => $qc?->reject_reason ?? null,
                'notes' => $qc?->notes ?? null,
                'is_posted' => (bool) ($qc && $bundle->wip_posted_at),
                'is_corrected' => (bool) $bundleCorrection,
                'correction_id' => $bundleCorrection?->id,
                'replacement_item_code' => $bundleCorrection?->replacementItem?->code,
                'can_correct_to_raw' => $isOwner
                    && ! $bundleCorrection
                    && (float) ($bundle->qty_used_fabric ?? 0) > 0.0001
                    && ! in_array((int) $bundle->id, $activePickedBundleIds, true)
                    && (float) ($bundle->sewing_picked_qty ?? 0) <= 0.000001,
                'can_partial_cancel' => $isOwner
                    && $qc
                    && ! $bundleCorrection
                    && ! in_array((int) $bundle->id, $activePickedBundleIds, true)
                    && (float) ($bundle->sewing_picked_qty ?? 0) <= 0.000001,
                'can_update_item' => $isOwner
                    && in_array((int) $bundle->id, $partialCancelledBundleIds, true)
                    && ! $qc
                    && ! in_array((int) $bundle->id, $activePickedBundleIds, true)
                    && (float) ($bundle->sewing_picked_qty ?? 0) <= 0.000001,
            ];
        }

        $loginOperator = Auth::user()->employee ?? null;
        $hasQcCutting = $existingQc->isNotEmpty();

        // Saldo LOT sebelum mutasi Cutting Job. Jika mutasi lama tidak
        // memiliki jejak yang cukup, gunakan saldo sekarang + pemakaian job
        // sebagai fallback agar data legacy tetap dapat ditampilkan.
        $lotStockBeforeById = [];
        $lotInventoryNowById = [];
        $lotQtyOkById = $cuttingJob->bundles
            ->groupBy('lot_id')
            ->map(fn ($bundles): float => round((float) $bundles->sum(
                fn ($bundle): float => (float) ($existingQc->get($bundle->id)?->qty_ok ?? $bundle->qty_pcs)
            ), 2))
            ->all();
        foreach ($cuttingJob->lots as $jobLot) {
            $lotId = (int) $jobLot->lot_id;
            $lotMutationQuery = InventoryMutation::query()
                ->where('warehouse_id', (int) $cuttingJob->warehouse_id)
                ->where('lot_id', $lotId);
            $hasLotMutations = (clone $lotMutationQuery)->exists();
            $lotInventoryNowById[$lotId] = round(
                $hasLotMutations
                    ? (float) $lotMutationQuery->sum('qty_change')
                    : (float) ($jobLot->lot?->qty_onhand ?? 0),
                4,
            );

            $firstCuttingMutation = InventoryMutation::query()
                ->where('warehouse_id', (int) $cuttingJob->warehouse_id)
                ->where('lot_id', $lotId)
                ->where('source_type', 'cutting_job')
                ->where('source_id', (int) $cuttingJob->id)
                ->orderBy('date')
                ->orderBy('id')
                ->first(['date', 'id']);

            if ($firstCuttingMutation) {
                $beforeQty = InventoryMutation::query()
                    ->where('warehouse_id', (int) $cuttingJob->warehouse_id)
                    ->where('lot_id', $lotId)
                    ->where(function ($query) use ($firstCuttingMutation): void {
                        $query
                            ->where('date', '<', $firstCuttingMutation->date)
                            ->orWhere(function ($sameDate) use ($firstCuttingMutation): void {
                                $sameDate
                                    ->where('date', $firstCuttingMutation->date)
                                    ->where('id', '<', (int) $firstCuttingMutation->id);
                            });
                    })
                    ->sum('qty_change');
            } else {
                $beforeQty = (float) ($jobLot->lot?->qty_onhand ?? 0)
                    + (float) ($jobLot->used_fabric_qty ?? 0);
            }

            $lotStockBeforeById[$lotId] = round((float) $beforeQty, 4);
        }

        // Read-only trace untuk menyelaraskan QC dengan halaman detail Cutting.
        $accountingJournalEntries = StandaloneAccountingJournal::query()
            ->where('source_id', (int) $cuttingJob->id)
            ->where(function ($query): void {
                $query
                    ->whereIn('source_type', [
                        'cutting_job',
                        'cutting_job_wage',
                        'cutting_wip',
                        'cutting_qc_sewing_supply',
                    ])
                    ->orWhere('source_type', 'like', 'cutting_job_repost_%')
                    ->orWhere('source_type', 'like', 'cutting_job_wage_repost_%')
                    ->orWhere('source_type', 'like', 'cutting_wip_repost_%')
                    ->orWhere('source_type', 'like', 'cutting_qc_sewing_supply_repost_%')
                    ->orWhere('source_type', 'like', 'cutting_job_reversal_%')
                    ->orWhere('source_type', 'like', 'cutting_job_wage_reversal_%')
                    ->orWhere('source_type', 'like', 'cutting_wip_reversal_%')
                    ->orWhere('source_type', 'like', 'cutting_qc_sewing_supply_reversal_%');
            })
            ->with('lines.account')
            ->orderByDesc('date')
            ->orderByDesc('posted_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $accountingActiveEntries = $accountingJournalEntries->whereNull('voided_at');
        $accountingInternalEntries = $accountingJournalEntries
            ->filter(fn ($journal): bool => str_starts_with((string) $journal->source_type, 'cutting_wip'))
            ->values();
        $accountingFinancialEntries = $accountingJournalEntries
            ->reject(fn ($journal): bool => str_starts_with((string) $journal->source_type, 'cutting_wip'))
            ->values();
        $accountingFinancialActiveEntries = $accountingFinancialEntries->whereNull('voided_at');
        $accountingPostedTotalDebit = (float) $accountingFinancialActiveEntries->sum(fn ($journal) => $journal->lines->sum('debit'));
        $accountingPostedTotalCredit = (float) $accountingFinancialActiveEntries->sum(fn ($journal) => $journal->lines->sum('credit'));

        // Read-only trace lot aktual untuk Auto BOM QC Cutting. Mutasi lama
        // dengan lot_id NULL tetap ditampilkan sebagai REVIEW; tidak ada
        // backfill otomatis dari halaman ini.
        $cuttingQcBomLotRows = InventoryMutation::query()
            ->where('source_id', (int) $cuttingJob->id)
            ->where(function ($query): void {
                $query
                    ->where('source_type', JournalService::SRC_CUTTING_QC_SEWING_SUPPLY)
                    ->orWhere('source_type', 'like', JournalService::SRC_CUTTING_QC_SEWING_SUPPLY.'_repost_%')
                    ->orWhere('source_type', 'like', JournalService::SRC_CUTTING_QC_SEWING_SUPPLY.'_reversal_%');
            })
            ->with(['lot:id,code', 'item:id,code,name,unit'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (InventoryMutation $mutation): array => [
                'id' => (int) $mutation->id,
                'source_type' => (string) $mutation->source_type,
                'direction' => (string) $mutation->direction,
                'item_code' => (string) ($mutation->item?->code ?? '-'),
                'item_name' => (string) ($mutation->item?->name ?? '-'),
                'lot_code' => (string) ($mutation->lot?->code ?? '-'),
                'lot_id' => $mutation->lot_id ? (int) $mutation->lot_id : null,
                'qty' => round(abs((float) $mutation->qty_change), 4),
                'uom' => (string) ($mutation->item?->unit ?? 'pcs'),
                'unit_cost' => round((float) $mutation->unit_cost, 4),
                'total_cost' => round(abs((float) $mutation->total_cost), 2),
                'status' => $mutation->lot_id ? 'TERTELUSUR' : 'REVIEW',
            ])
            ->values();

        // Reject Cutting tetap berada di jurnal cutting_wip yang sama dengan
        // output OK. Ambil mapping dan baris akun aktual supaya UI tidak
        // mengarang akun atau nilai ketika reject belum berhasil dimirror.
        $cuttingRejectMapping = StandaloneProductionAccountMapping::query()
            ->where('source_type', 'cutting_wip')
            ->where('legacy_account_code', '1204')
            ->where('line_side', 'debit')
            ->where('stage', 'cutting')
            ->where('is_active', true)
            ->first();
        $cuttingRejectAccountCode = $cuttingRejectMapping?->standalone_account_code;

        $cuttingRejectJournalRows = collect();
        $cuttingRejectParentEntries = collect();
        if ($cuttingRejectAccountCode) {
            $cuttingRejectParentEntries = $accountingJournalEntries
                ->filter(fn ($journal): bool => str_starts_with((string) $journal->source_type, 'cutting_wip'))
                ->filter(fn ($journal): bool => $journal->lines->contains(
                    fn ($line): bool => (string) ($line->account?->code ?? '') === (string) $cuttingRejectAccountCode
                ));

            $cuttingRejectJournalRows = $cuttingRejectParentEntries
                ->flatMap(function ($journal) use ($cuttingRejectAccountCode) {
                    return $journal->lines
                        ->filter(fn ($line): bool => (string) ($line->account?->code ?? '') === (string) $cuttingRejectAccountCode)
                        ->map(fn ($line): array => [
                            'source_type' => (string) $journal->source_type,
                            'reference' => (string) ($journal->reference ?? '-'),
                            'account_code' => (string) ($line->account?->code ?? '-'),
                            'account_name' => (string) ($line->account?->name ?? '-'),
                            'debit' => (float) $line->debit,
                            'credit' => (float) $line->credit,
                            'voided' => ! empty($journal->voided_at),
                        ]);
                })
                ->values();
        }

        $cuttingRejectQty = (float) collect($rows)->sum('qty_reject');
        $cuttingRejectActiveRows = $cuttingRejectJournalRows->where('voided', false);
        $cuttingRejectActiveParentEntries = $cuttingRejectParentEntries->whereNull('voided_at');
        $cuttingRejectParentBalance = $cuttingRejectActiveParentEntries->isNotEmpty()
            && $cuttingRejectActiveParentEntries->every(fn ($journal): bool => abs(
                (float) $journal->lines->sum('debit') - (float) $journal->lines->sum('credit')
            ) <= 0.01);
        $cuttingRejectAccounting = [
            'qty' => $cuttingRejectQty,
            'mapping_code' => $cuttingRejectAccountCode,
            'mapping_purpose' => $cuttingRejectMapping?->purpose,
            'mapping_status' => $cuttingRejectMapping ? 'PASS' : 'MISSING',
            'active_debit' => round((float) $cuttingRejectActiveRows->sum('debit'), 2),
            'active_credit' => round((float) $cuttingRejectActiveRows->sum('credit'), 2),
            'active_parent_balance' => $cuttingRejectParentBalance,
            'journal_count' => $cuttingRejectJournalRows->count(),
            'active_journal_count' => $cuttingRejectActiveRows->count(),
            'voided_journal_count' => $cuttingRejectJournalRows->where('voided', true)->count(),
            'rows' => $cuttingRejectJournalRows,
        ];

        // Estimasi HPP read-only: biaya bahan dari LOT + tarif upah aktif pada
        // tanggal Cutting Job. Tidak membuat posting baru dari halaman QC.
        $rateDate = $cuttingJob->date ?? now();
        $operatorIds = $cuttingJob->bundles->pluck('operator_id')->filter()->unique()->values();
        $activeCuttingRates = PieceRate::query()
            ->where('module', 'cutting')
            ->whereIn('employee_id', $operatorIds)
            ->activeAt($rateDate)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->get(['employee_id', 'item_id', 'item_category_id', 'rate_per_pcs'])
            ->groupBy('employee_id');

        $cuttingHppRows = $cuttingJob->bundles->map(function ($bundle) use ($activeCuttingRates): array {
            $finishedItem = $bundle->finishedItem;
            $lot = $bundle->lot;
            $lotUnitCost = (float) ($lot?->avg_cost ?: ($lot?->item?->effective_unit_cost ?? 0));
            $operatorRates = $activeCuttingRates->get((int) $bundle->operator_id, collect());

            $wageRate = $operatorRates
                ->first(fn ($rate): bool => (int) $rate->item_id === (int) $bundle->finished_item_id)
                ?->rate_per_pcs;
            if ((float) $wageRate <= 0) {
                $wageRate = $operatorRates
                    ->first(fn ($rate): bool => $rate->item_id === null
                        && (int) $rate->item_category_id === (int) ($finishedItem?->item_category_id ?? $bundle->item_category_id))
                    ?->rate_per_pcs;
            }
            if ((float) $wageRate <= 0) {
                $wageRate = $operatorRates
                    ->first(fn ($rate): bool => $rate->item_id === null && $rate->item_category_id === null)
                    ?->rate_per_pcs;
            }

            $qtyPcs = (float) ($bundle->qty_pcs ?? 0);
            $fabricQty = (float) ($bundle->qty_used_fabric ?? 0);
            $materialCost = round($fabricQty * $lotUnitCost, 2);
            $wageCost = round($qtyPcs * (float) ($wageRate ?? 0), 2);
            $totalCost = round($materialCost + $wageCost, 2);

            return [
                'bundle_code' => (string) ($bundle->bundle_code ?: ('Bundle #'.$bundle->bundle_no)),
                'finished_item_id' => (int) $bundle->finished_item_id,
                'item_code' => (string) ($finishedItem?->code ?? '-'),
                'qty_pcs' => $qtyPcs,
                'fabric_qty' => $fabricQty,
                'material_unit_cost' => $lotUnitCost,
                'material_cost' => $materialCost,
                'material_unit_cost_per_pcs' => $qtyPcs > 0 ? round($materialCost / $qtyPcs, 2) : 0,
                'wage_rate' => (float) ($wageRate ?? 0),
                'wage_cost' => $wageCost,
                'total_cost' => $totalCost,
                'unit_cost' => $qtyPcs > 0 ? round($totalCost / $qtyPcs, 2) : 0,
                'material_cost_missing' => $fabricQty > 0 && $lotUnitCost <= 0,
                'wage_rate_missing' => $qtyPcs > 0 && (float) ($wageRate ?? 0) <= 0,
            ];
        })->values();

        // Komponen BOM read-only untuk transparansi HPP QC. Bahan utama
        // memakai pemakaian kain aktual dari bundle; komponen lain memakai
        // qty BOM dan HPP efektif master item sebagai estimasi.
        $hppBomByItem = ItemBomLine::query()
            ->whereIn('usage_stage', [
                ItemBomLine::STAGE_SEWING_SUPPLY,
                ItemBomLine::STAGE_PACKING_SUPPLY,
            ])
            ->whereHas('bom', fn ($query) => $query
                ->where('active', true)
                ->whereIn('item_id', $cuttingJob->bundles->pluck('finished_item_id')->filter()->unique()))
            ->with(['bom:id,item_id', 'material:id,code,name,unit'])
            ->get()
            ->groupBy(fn ($line): int => (int) $line->bom->item_id);

        $cuttingHppRows = $cuttingHppRows->map(function (array $row) use ($hppBomByItem): array {
            $supportCost = 0.0;
            $otherCost = 0.0;
            $qtyPcs = (float) $row['qty_pcs'];

            foreach ($hppBomByItem->get((int) ($row['finished_item_id'] ?? 0), collect()) as $line) {
                $cost = round(
                    $qtyPcs * $line->qtyWithScrap() * (float) ($line->material?->effective_unit_cost ?? 0),
                    2,
                );
                if ($line->usage_stage === ItemBomLine::STAGE_SEWING_SUPPLY) {
                    $supportCost += $cost;
                } else {
                    $otherCost += $cost;
                }
            }

            $row['support_cost'] = round($supportCost, 2);
            $row['support_unit_cost'] = $qtyPcs > 0 ? round($supportCost / $qtyPcs, 2) : 0;
            $row['other_cost'] = round($otherCost, 2);
            $row['total_cost'] = round(
                (float) $row['material_cost'] + $supportCost + $otherCost + (float) $row['wage_cost'],
                2,
            );
            $row['unit_cost'] = $qtyPcs > 0 ? round($row['total_cost'] / $qtyPcs, 2) : 0;

            return $row;
        })->values();

        $cuttingHppTotals = [
            'material' => round((float) $cuttingHppRows->sum('material_cost'), 2),
            'raw_material' => round((float) $cuttingHppRows->sum('material_cost'), 2),
            'supply' => round((float) $cuttingHppRows->sum('support_cost'), 2),
            'other' => round((float) $cuttingHppRows->sum('other_cost'), 2),
            'wage' => round((float) $cuttingHppRows->sum('wage_cost'), 2),
            'total' => round((float) $cuttingHppRows->sum('total_cost'), 2),
        ];

        // Preview BOM sewing_supply yang benar-benar akan dikonsumsi saat
        // QC disimpan. Perhitungannya mengikuti service posting: qty BOM
        // dikalikan Qty OK, tanpa scrap/packing supply dan tanpa Qty reject.
        $cuttingQcAutoBomRows = collect();
        if ((bool) config('accounting.standalone_production_cutting_qc_auto_bom_enabled', false)) {
            $qtyOkByFinishedItem = $cuttingJob->bundles
                ->groupBy('finished_item_id')
                ->map(fn ($bundles): float => (float) $bundles->sum(
                    fn ($bundle): float => (float) ($existingQc->get($bundle->id)?->qty_ok ?? $bundle->qty_pcs)
                ))
                ->filter(fn (float $qty): bool => $qty > 0.000001);

            if ($qtyOkByFinishedItem->isNotEmpty()) {
                $autoBomLines = ItemBomLine::query()
                    ->where('usage_stage', ItemBomLine::STAGE_SEWING_SUPPLY)
                    ->where('is_optional', false)
                    ->whereHas('bom', fn ($query) => $query
                        ->where('active', true)
                        ->whereIn('item_id', $qtyOkByFinishedItem->keys()->all()))
                    ->with(['bom:id,item_id', 'material:id,code,name,unit'])
                    ->get();
                $rmWarehouseId = Warehouse::query()->where('code', 'RM')->value('id');
                $rmStockByItem = $rmWarehouseId
                    ? InventoryStock::query()
                        ->where('warehouse_id', $rmWarehouseId)
                        ->whereIn('item_id', $autoBomLines->pluck('material_item_id')->unique()->all())
                        ->get()
                        ->keyBy('item_id')
                    : collect();
                $bomSourceType = JournalService::SRC_CUTTING_QC_SEWING_SUPPLY;
                $rmBomNetQtyByItem = $rmWarehouseId
                    ? InventoryMutation::query()
                        ->where('warehouse_id', $rmWarehouseId)
                        ->where('source_id', (int) $cuttingJob->id)
                        ->where(function ($query) use ($bomSourceType): void {
                            $query
                                ->where('source_type', $bomSourceType)
                                ->orWhere('source_type', 'like', $bomSourceType.'_%');
                        })
                        ->get(['item_id', 'qty_change'])
                        ->groupBy('item_id')
                        ->map(fn ($mutations): float => (float) $mutations->sum('qty_change'))
                    : collect();

                $cuttingQcAutoBomRows = $autoBomLines
                    ->groupBy('material_item_id')
                    ->map(function ($lines) use ($qtyOkByFinishedItem, $rmStockByItem, $rmBomNetQtyByItem): array {
                        $requiredQty = 0.0;
                        $finishedItemIds = $lines
                            ->map(fn ($line): int => (int) $line->bom?->item_id)
                            ->filter()
                            ->unique();

                        foreach ($lines as $line) {
                            $finishedItemId = (int) $line->bom?->item_id;
                            $itemQtyOk = (float) $qtyOkByFinishedItem->get($finishedItemId, 0);
                            $lineQty = (float) $line->qty;
                            $requiredQty += $itemQtyOk * $lineQty;
                        }

                        $qtyOk = (float) $finishedItemIds->sum(
                            fn (int $itemId): float => (float) $qtyOkByFinishedItem->get($itemId, 0)
                        );

                        $material = $lines->first()?->material;
                        $materialId = (int) $lines->first()?->material_item_id;
                        $unitCost = (float) ($material?->effective_unit_cost ?? 0);
                        $totalCost = round($requiredQty * $unitCost, 2);
                        $stockNowQty = (float) ($rmStockByItem->get($materialId)?->qty ?? 0);
                        $netJobQtyChange = (float) $rmBomNetQtyByItem->get($materialId, 0);
                        $stockBeforeQcQty = round($stockNowQty - $netJobQtyChange, 4);

                        return [
                            'material_code' => (string) ($material?->code ?? '-'),
                            'material_name' => (string) ($material?->name ?? '-'),
                            'uom' => (string) ($lines->first()?->uom ?: $material?->unit ?: 'pcs'),
                            'available_qty' => $stockBeforeQcQty,
                            'stock_now_qty' => round($stockNowQty, 4),
                            'stock_before_qc_qty' => $stockBeforeQcQty,
                            'net_job_qty_change' => round($netJobQtyChange, 4),
                            'qty_ok' => round($qtyOk, 4),
                            'qty_per_pcs' => $qtyOk > 0 ? round($requiredQty / $qtyOk, 4) : 0,
                            'required_qty' => round($requiredQty, 4),
                            'unit_cost' => $unitCost,
                            'total_cost' => $totalCost,
                            'cost_per_pcs' => $qtyOk > 0 ? round($totalCost / $qtyOk, 2) : 0,
                            'estimated_balance_qty' => round(
                                $stockBeforeQcQty - $requiredQty,
                                4,
                            ),
                        ];
                    })
                    ->filter(fn (array $row): bool => $row['required_qty'] > 0.000001)
                    ->values();
            }
        }

        $pendingAutoBomLotPreview = null;
        if (
            $isOwner
            && $cuttingJob->status === QcService::CUTTING_QC_PENDING_OWNER_REVIEW
        ) {
            $pendingAutoBomLotPreview = $this->qc->previewCuttingQcSewingSupplyLots(
                $cuttingJob,
                $cuttingJob->bundles
                    ->map(fn ($bundle): array => [
                        'cutting_job_bundle_id' => (int) $bundle->id,
                        'qty_ok' => (float) ($existingQc->get($bundle->id)?->qty_ok ?? 0),
                        'qty_reject' => (float) ($existingQc->get($bundle->id)?->qty_reject ?? 0),
                    ])
                    ->all(),
            );
        }

        return view('production.qc.cutting_edit', compact(
            'cuttingJob',
            'rows',
            'loginOperator',
            'hasQcCutting',
            'accountingJournalEntries',
            'accountingActiveEntries',
            'accountingInternalEntries',
            'accountingFinancialEntries',
            'accountingFinancialActiveEntries',
            'accountingPostedTotalDebit',
            'accountingPostedTotalCredit',
            'cuttingRejectAccounting',
            'cuttingHppRows',
            'cuttingHppTotals',
            'cuttingQcAutoBomRows',
            'pendingAutoBomLotPreview',
            'cuttingQcBomLotRows',
            'lotStockBeforeById',
            'lotInventoryNowById',
            'lotQtyOkById'
        ))->with('cuttingQcAutoBomEnabled', (bool) config('accounting.standalone_production_cutting_qc_auto_bom_enabled', false));
    }

    /**
     * Simpan QC Cutting.
     */
    public function updateCutting(Request $request, CuttingJob $cuttingJob)
    {
        $validated = $request->validate([
            'qc_date' => ['required', 'date'],
            'operator_id' => ['nullable', 'exists:employees,id'],

            'results' => ['required', 'array', 'min:1'],
            'results.*.cutting_job_bundle_id' => ['required', 'exists:cutting_job_bundles,id'],
            'results.*.qty_ok' => ['nullable', 'numeric', 'min:0'],
            'results.*.qty_reject' => ['nullable', 'numeric', 'min:0'],
            'results.*.reject_reason' => ['nullable', 'string', 'max:100'],
            'results.*.notes' => ['nullable', 'string'],
        ]);

        // Operator QC selalu mengikuti akun yang sedang login.
        $validated['operator_id'] = Auth::user()->employee?->id;
        $validated['qc_by_user_id'] = Auth::id();

        if (
            $cuttingJob->status === QcService::CUTTING_QC_PENDING_OWNER_REVIEW
            && ! (Auth::user()?->isOwner() ?? false)
        ) {
            return back()->with('warning', 'QC ini sedang menunggu koreksi Owner. Operator tidak dapat memposting atau mengubahnya.');
        }

        try {
            $this->processCuttingQc($cuttingJob, $validated);
        } catch (AutoBomLotShortageException $e) {
            if (! (Auth::user()?->isOwner() ?? false)) {
                $this->qc->saveCuttingQcPendingOwnerReview($cuttingJob, $validated);

                return redirect()
                    ->route('production.qc.cutting.edit', $cuttingJob)
                    ->with('warning', 'QC disimpan sebagai Menunggu Koreksi Owner. Owner perlu memeriksa lot atau Qty OK/BOM.');
            }

            return $this->autoBomLotShortageResponse($e);
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', 'QC gagal: '.$e->getMessage());
        }

        $shortageCount = $this->materialShortages->rows()->where('has_shortage', true)->count();

        return redirect()
            ->route('production.cutting_jobs.show', $cuttingJob)
            ->with('success', 'QC Cutting berhasil disimpan & WIP-CUT sudah dibuat.')
            ->with('material_shortage_count', $shortageCount);
    }

    public function quickOkCutting(CuttingJob $cuttingJob): RedirectResponse
    {
        $cuttingJob->loadMissing([
            'bundles.qcResults' => function ($q) {
                $q->where('stage', QcResult::STAGE_CUTTING);
            },
        ]);

        $hasQcCutting = $cuttingJob->bundles->contains(
            fn ($bundle) => $bundle->qcResults->isNotEmpty()
        );

        if ($hasQcCutting) {
            return redirect()
                ->route('production.cutting_jobs.show', $cuttingJob)
                ->with('error', 'QC Cutting sudah pernah diinput. Gunakan Lihat / Edit QC atau Batalkan QC dulu.');
        }

        if ($cuttingJob->bundles->isEmpty()) {
            return back()->with('error', 'Belum ada bundle cutting untuk diproses.');
        }

        $operatorId = Auth::user()->employee?->id;
        $qcDate = now()->toDateString();

        $payload = [
            'qc_date' => $qcDate,
            'operator_id' => $operatorId,
            'qc_by_user_id' => Auth::id(),
            'results' => $cuttingJob->bundles->map(fn (CuttingJobBundle $bundle) => [
                'cutting_job_bundle_id' => $bundle->id,
                'qty_ok' => (float) $bundle->qty_pcs,
                'qty_reject' => 0,
                'reject_reason' => null,
                'notes' => 'Auto OK dari tombol Selesai Cutting & Siap Jahit',
            ])->values()->all(),
        ];

        try {
            $this->processCuttingQc($cuttingJob, $payload);
        } catch (AutoBomLotShortageException $e) {
            if (! (Auth::user()?->isOwner() ?? false)) {
                return $this->queueCuttingQcForOwnerReview($cuttingJob, $payload, $e);
            }

            return $this->autoBomLotShortageResponse($e);
        } catch (\Throwable $e) {
            return back()
                ->with('error', 'Selesai Cutting gagal: '.$e->getMessage());
        }

        $shortageCount = $this->materialShortages->rows()->where('has_shortage', true)->count();

        return redirect()
            ->route('production.cutting_jobs.show', $cuttingJob)
            ->with('success', 'Cutting selesai. Semua bundle otomatis OK dan sudah masuk WIP-CUT / siap jahit.')
            ->with('material_shortage_count', $shortageCount);
    }

    /**
     * Quick OK per bundle — tandai satu bundle sebagai QC OK dan buat WIP-CUT-nya.
     * Tidak perlu semua bundle selesai dulu.
     */
    public function quickOkBundle(Request $request, CuttingJob $cuttingJob, CuttingJobBundle $bundle): RedirectResponse
    {
        // Pastikan bundle milik cutting job ini
        if ((int) $bundle->cutting_job_id !== $cuttingJob->id) {
            return back()->with('error', 'Bundle tidak ditemukan di Cutting Job ini.');
        }

        // Jika bundle sudah punya QC, skip (sudah diproses)
        $existingQc = QcResult::where('stage', QcResult::STAGE_CUTTING)
            ->where('cutting_job_bundle_id', $bundle->id)
            ->first();

        if ($existingQc) {
            return back()->with('info', 'Bundle '.$bundle->bundle_code.' sudah pernah di-QC.');
        }

        $operatorId = Auth::user()->employee?->id;
        $qcDate = now()->toDateString();

        // Ambil qty dari request jika ada, fallback semua OK
        $qtyPcs = (float) $bundle->qty_pcs;
        $qtyOk = $request->has('qty_ok')
            ? max(0, min($qtyPcs, (float) $request->input('qty_ok', $qtyPcs)))
            : $qtyPcs;
        $qtyReject = $request->has('qty_reject')
            ? max(0, min($qtyPcs - $qtyOk, (float) $request->input('qty_reject', 0)))
            : 0.0;
        $rejectReason = $request->input('reject_reason');
        $autoNote = $request->has('qty_ok') ? null : 'Auto OK dari halaman detail cutting';

        $payload = [
            'qc_date' => $qcDate,
            'operator_id' => $operatorId,
            'qc_by_user_id' => Auth::id(),
            'results' => [[
                'cutting_job_bundle_id' => $bundle->id,
                'qty_ok' => $qtyOk,
                'qty_reject' => $qtyReject,
                'reject_reason' => $rejectReason,
                'notes' => $autoNote,
            ]],
        ];

        try {
            $this->processCuttingQc($cuttingJob, $payload);
        } catch (AutoBomLotShortageException $e) {
            if (! (Auth::user()?->isOwner() ?? false)) {
                return $this->queueCuttingQcForOwnerReview($cuttingJob, $payload, $e);
            }

            return $this->autoBomLotShortageResponse($e);
        } catch (\Throwable $e) {
            return back()->with('error', 'Quick OK bundle gagal: '.$e->getMessage());
        }

        return redirect()
            ->route('production.cutting_jobs.show', $cuttingJob)
            ->with('success', 'Bundle '.$bundle->bundle_code.' berhasil di-QC OK dan masuk WIP-CUT.');
    }

    /**
     * Simpan QC per bundle dari halaman edit (updateOrCreate — bisa create & update).
     * Redirect kembali ke halaman edit QC.
     */
    public function saveBundleEdit(Request $request, CuttingJob $cuttingJob, CuttingJobBundle $bundle): RedirectResponse
    {
        if ((int) $bundle->cutting_job_id !== $cuttingJob->id) {
            return back()->with('error', 'Bundle tidak ditemukan di Cutting Job ini.');
        }

        $qtyPcs = (float) $bundle->qty_pcs;
        $qtyReject = max(0, min($qtyPcs, (float) $request->input('qty_reject', 0)));
        $qtyOk = max(0, $qtyPcs - $qtyReject);
        $qcDate = $request->input('qc_date') ?: now()->toDateString();
        $operatorId = Auth::user()->employee?->id;

        $payload = [
            'qc_date' => $qcDate,
            'operator_id' => $operatorId,
            'qc_by_user_id' => Auth::id(),
            'results' => [[
                'cutting_job_bundle_id' => $bundle->id,
                'qty_ok' => $qtyOk,
                'qty_reject' => $qtyReject,
                'reject_reason' => $request->input('reject_reason'),
                'notes' => $request->input('notes'),
            ]],
        ];

        try {
            $this->processCuttingQc($cuttingJob, $payload);
        } catch (AutoBomLotShortageException $e) {
            if (! (Auth::user()?->isOwner() ?? false)) {
                return $this->queueCuttingQcForOwnerReview($cuttingJob, $payload, $e);
            }

            return $this->autoBomLotShortageResponse($e);
        } catch (\Throwable $e) {
            return back()->with('error', 'Simpan bundle gagal: '.$e->getMessage());
        }

        return redirect()
            ->route('production.qc.cutting.edit', $cuttingJob)
            ->with('success', 'Bundle '.$bundle->bundle_code.' berhasil disimpan.');
    }

    /**
     * Complete the QC Cutting workflow atomically.
     *
     * QC results, WIP/reject inventory, journal, and job status must either
     * all succeed or all roll back together. This prevents an orphan QC
     * result when WIP posting fails (for example because PieceRate is missing).
     */
    private function processCuttingQc(CuttingJob $cuttingJob, array $payload): void
    {
        DB::transaction(function () use ($cuttingJob, $payload) {
            $job = CuttingJob::query()
                ->whereKey($cuttingJob->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->standaloneProductionPosting->assertCuttingMappings();

            // Bundle yang sudah pernah masuk WIP tidak boleh diedit lewat
            // Simpan QC biasa. Koreksi kuantitas harus melalui Adjust atau
            // Partial Cancel supaya stok dan jurnal memiliki jejak reversal.
            $this->assertPostedCuttingQcIsUnchanged($job, $payload['results'] ?? []);

            $this->qc->assertCuttingQcSewingSupplyLotsAvailable(
                $job,
                $payload['results'] ?? [],
            );

            $this->cutting->validatePieceRatesForQc(
                job: $job->fresh('bundles'),
                results: $payload['results'] ?? [],
                qcDate: $payload['qc_date'],
            );

            $this->qc->saveCuttingQc($job, $payload);

            $job = $job->fresh('bundles');
            $this->cutting->createWipFromCuttingQc(
                job: $job,
                qcDate: $payload['qc_date'],
            );

            $journal = $this->journal->postCuttingWip($job->fresh(), $payload['qc_date']);
            if (! $journal) {
                throw new \RuntimeException('Jurnal hasil QC Cutting tidak terbentuk. QC dibatalkan.');
            }

            $this->standaloneProductionPosting->mirrorCuttingWip((int) $job->id);

            // Kelengkapan BOM sewing_supply dikonsumsi di titik QC Cutting,
            // setelah output WIP tersedia. Sewing Pickup hanya memindahkan WIP.
            $this->qc->consumeCuttingQcSewingSupplies($job->fresh('bundles'), $payload['qc_date']);

            // Jika bundle sebelumnya dibuka lewat Partial Cancel QC, jurnal
            // job-level lama tetap aktif karena bundle lain mungkin sudah
            // dipakai sewing. Post jurnal tambahan khusus bundle yang dibuka.
            $this->qc->postReopenedCuttingQcJournals($job->fresh(), $payload['qc_date']);

            $totalBundles = $job->bundles()->count();
            $doneCount = QcResult::query()
                ->where('stage', QcResult::STAGE_CUTTING)
                ->where('cutting_job_id', $job->id)
                ->distinct('cutting_job_bundle_id')
                ->count('cutting_job_bundle_id');

            $job->update([
                'status' => $doneCount >= $totalBundles ? 'qc_done' : $job->status,
                'updated_by' => Auth::id(),
            ]);
        });
    }

    private function queueCuttingQcForOwnerReview(
        CuttingJob $cuttingJob,
        array $payload,
        AutoBomLotShortageException $exception,
    ): RedirectResponse {
        $this->qc->saveCuttingQcPendingOwnerReview($cuttingJob, $payload);

        return redirect()
            ->route('production.qc.cutting.edit', $cuttingJob)
            ->with('warning', 'QC disimpan sebagai Menunggu Koreksi Owner. Owner perlu memeriksa lot atau Qty OK/BOM.');
    }

    private function autoBomLotShortageResponse(AutoBomLotShortageException $exception): RedirectResponse
    {
        return back()
            ->withInput()
            ->with('qc_auto_bom_shortage', $exception->flashPayload());
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function assertPostedCuttingQcIsUnchanged(CuttingJob $job, array $rows): void
    {
        $bundleIds = collect($rows)
            ->map(fn (array $row): int => (int) ($row['cutting_job_bundle_id'] ?? $row['bundle_id'] ?? 0))
            ->filter()
            ->unique()
            ->values();

        if ($bundleIds->isEmpty()) {
            return;
        }

        $postedBundles = $job->bundles()
            ->whereIn('id', $bundleIds->all())
            ->whereNotNull('wip_posted_at')
            ->get()
            ->keyBy('id');

        if ($postedBundles->isEmpty()) {
            return;
        }

        $existing = QcResult::query()
            ->where('stage', QcResult::STAGE_CUTTING)
            ->where('cutting_job_id', $job->id)
            ->whereIn('cutting_job_bundle_id', $postedBundles->keys()->all())
            ->get()
            ->keyBy('cutting_job_bundle_id');

        foreach ($rows as $row) {
            $bundleId = (int) ($row['cutting_job_bundle_id'] ?? $row['bundle_id'] ?? 0);
            $bundle = $postedBundles->get($bundleId);
            $qc = $existing->get($bundleId);
            if (! $bundle || ! $qc) {
                continue;
            }

            $incomingOk = round((float) ($row['qty_ok'] ?? 0), 6);
            $incomingReject = round((float) ($row['qty_reject'] ?? 0), 6);
            $savedOk = round((float) $qc->qty_ok, 6);
            $savedReject = round((float) $qc->qty_reject, 6);

            if (abs($incomingOk - $savedOk) > 0.000001 || abs($incomingReject - $savedReject) > 0.000001) {
                throw new \RuntimeException(
                    "Bundle {$bundle->bundle_code} sudah diposting ke WIP. "
                    .'Gunakan Adjust QC untuk koreksi klasifikasi OK/Reject, atau Partial Cancel lalu QC ulang.'
                );
            }
        }
    }

    public function cancelCutting(CuttingJob $cuttingJob): RedirectResponse
    {
        $role = Auth::user()->role ?? null;
        if ($role !== 'owner') {
            return back()->with('error', 'Hanya OWNER yang boleh membatalkan QC.');
        }

        try {
            $this->qc->cancelCuttingQc($cuttingJob);

            try {
                $this->journal->voidBySource(JournalService::SRC_CUTTING_WIP, (int) $cuttingJob->id, "VOID QC Cutting {$cuttingJob->code}");
            } catch (\Throwable $journalError) {
                Log::warning('Gagal void jurnal cutting_wip', [
                    'cutting_job_id' => $cuttingJob->id,
                    'message' => $journalError->getMessage(),
                ]);
            }
        } catch (\Throwable $e) {
            $payload = $this->buildCancelQcUiPayload($e, $cuttingJob);

            // toast message singkat + data detail untuk modal
            return back()
                ->with('qc_cancel_ui', $payload)
                ->with('error', $payload['toast'] ?? 'Cancel QC gagal.');
        }

        return redirect()
            ->route('production.cutting_jobs.show', $cuttingJob)
            ->with('success', 'QC Cutting dibatalkan. Silakan QC ulang.');
    }

    private function humanizeCancelCuttingQcError(\Throwable $e, CuttingJob $job): string
    {
        $raw = trim((string) $e->getMessage());

        // Pola error inventory service kamu
        $ok = preg_match(
            '/Stok tidak mencukupi untuk item\s+(\d+)\s+di gudang\s+(\d+)\.\s*Stok:\s*([0-9\.,]+),\s*mau keluar:\s*([0-9\.,]+)/i',
            $raw,
            $m
        );

        // Kalau bukan kasus stok, fallback normal
        if (! $ok) {
            return 'Cancel QC gagal: '.$raw;
        }

        [$all, $itemId, $warehouseId, $stok, $need] = $m;

        // Cari "penyebab utama" yang paling membantu user:
        // ambil transaksi Sewing Pickup yang BELUM VOID yang paling baru untuk warehouse+item ini
        $pickup = DB::table('inventory_mutations as m')
            ->join('sewing_pickups as sp', function ($j) {
                $j->on('sp.id', '=', 'm.source_id')
                    ->where('m.source_type', '=', 'App\Models\SewingPickup');
            })
            ->select([
                'm.id',
                'm.qty_change',
                'm.notes',
                'sp.code as pickup_code',
                'sp.voided_at',
            ])
            ->where('m.warehouse_id', (int) $warehouseId)
            ->where('m.item_id', (int) $itemId)
            ->where('m.qty_change', '<', 0)
            ->whereNull('sp.voided_at') // ✅ hanya yang BELUM VOID
            ->orderByDesc('m.date')
            ->orderByDesc('m.id')
            ->first();

        // Kalau tidak ada Sewing Pickup aktif, fallback cari transaksi aktif apa pun yang paling baru
        $other = null;
        if (! $pickup) {
            $other = DB::table('inventory_mutations as m')
                ->select(['m.source_type', 'm.source_id', 'm.qty_change', 'm.notes', 'm.id'])
                ->where('m.warehouse_id', (int) $warehouseId)
                ->where('m.item_id', (int) $itemId)
                ->where('m.qty_change', '<', 0)
                ->whereNotIn('m.source_type', ['cutting_wip', 'cutting_reject', 'cutting_qc_void'])
                ->orderByDesc('m.date')
                ->orderByDesc('m.id')
                ->first();
        }

        // ✅ Pesan SIMPLE (satu layar)
        $msg =
            "Cancel QC gagal — hasil QC sudah dipakai\n\n"
            ."Cutting Job: {$job->code}\n"
            ."Gudang: {$warehouseId}\n"
            ."Item: {$itemId}\n"
            ."Stok tersedia: {$stok}\n"
            ."Dibutuhkan: {$need}\n\n";

        if ($pickup) {
            $code = $pickup->pickup_code ?: '(kode tidak ditemukan)';
            $msg .=
                "Penyebab:\n"
                ."Sewing Pickup {$code} (belum void)\n\n"
                ."Solusi:\n"
                ."1) Void Sewing Pickup {$code}\n"
                .'2) Setelah stok kembali, ulangi Cancel QC';

            return $msg;
        }

        if ($other) {
            $label = "{$other->source_type} #{$other->source_id}";
            $msg .=
                "Penyebab:\n"
                ."{$label}\n\n"
                ."Solusi:\n"
                ."1) Void / batalkan transaksi di atas\n"
                .'2) Setelah stok kembali, ulangi Cancel QC';

            return $msg;
        }

        // fallback paling aman
        return 'Cancel QC gagal: '.$raw;
    }

    private function buildCancelQcUiPayload(\Throwable $e, CuttingJob $job): array
    {
        $raw = trim((string) $e->getMessage());

        $ok = preg_match(
            '/Stok tidak mencukupi untuk item\s+(\d+)\s+di gudang\s+(\d+)\.\s*Stok:\s*([0-9\.,]+),\s*mau keluar:\s*([0-9\.,]+)/i',
            $raw,
            $m
        );

        // fallback kalau bukan kasus stok
        if (! $ok) {
            return [
                'type' => 'error',
                'toast' => 'Cancel QC gagal.',
                'title' => 'Cancel QC gagal',
                'lines' => [$raw],
                'action' => null,
            ];
        }

        [$all, $itemId, $warehouseId, $stok, $need] = $m;

        // cari Sewing Pickup BELUM VOID paling baru yang menghabiskan stok item+warehouse ini
        $pickup = DB::table('inventory_mutations as m')
            ->join('sewing_pickups as sp', function ($j) {
                $j->on('sp.id', '=', 'm.source_id')
                    ->where('m.source_type', '=', 'App\Models\SewingPickup');
            })
            ->select([
                'sp.id as pickup_id',
                'sp.code as pickup_code',
                'm.qty_change',
                'm.notes',
            ])
            ->where('m.warehouse_id', (int) $warehouseId)
            ->where('m.item_id', (int) $itemId)
            ->where('m.qty_change', '<', 0)
            ->whereNull('sp.voided_at')
            ->orderByDesc('m.date')
            ->orderByDesc('m.id')
            ->first();

        $toast = 'Cancel QC gagal — hasil QC sudah dipakai.';
        $title = 'Cancel QC gagal';

        $lines = [
            "Cutting Job: {$job->code}",
            "Gudang: {$warehouseId}",
            "Item: {$itemId}",
            "Stok tersedia: {$stok}",
            "Dibutuhkan: {$need}",
        ];

        $action = null;

        if ($pickup) {
            $code = $pickup->pickup_code ?: "ID {$pickup->pickup_id}";
            $lines[] = "Penyebab: Sewing Pickup {$code} (belum void)";
            $lines[] = 'Solusi: Void Sewing Pickup → lalu ulangi Cancel QC';

            $action = [
                'label' => "Buka {$code}",
                'route' => 'production.sewing.pickups.show',
                'params' => [$pickup->pickup_id],
            ];
        } else {
            $lines[] = 'Solusi: Void transaksi yang memakai stok item ini → lalu ulangi Cancel QC';
        }

        return [
            'type' => 'error',
            'toast' => $toast,
            'title' => $title,
            'lines' => $lines,
            'action' => $action,
        ];
    }

    public function adjustCuttingBundle(Request $request, CuttingJob $cuttingJob, CuttingJobBundle $bundle): RedirectResponse
    {
        // ✅ owner-only
        if ((Auth::user()->role ?? null) !== 'owner') {
            return back()->with('error', 'Hanya OWNER yang boleh melakukan adjustment QC.');
        }

        $validated = $request->validate([
            'qc_date' => ['required', 'date'],
            'qty_ok' => ['required', 'numeric', 'min:0'],
            'qty_reject' => ['nullable', 'numeric', 'min:0'],
            'reject_reason' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $operatorId = Auth::user()->employee?->id;

        try {
            // ✅ ini method baru yang kamu tambahkan di QcService
            $this->qc->adjustCuttingBundleQc(
                bundle: $bundle,
                qcDate: $validated['qc_date'],
                newOk: (float) $validated['qty_ok'],
                newReject: (float) ($validated['qty_reject'] ?? 0),
                operatorId: $operatorId,
                qcByUserId: Auth::id(),
                rejectReason: $validated['reject_reason'] ?? null,
                notes: $validated['notes'] ?? null,
            );
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', 'Adjust QC gagal: '.$e->getMessage());
        }

        return redirect()
            ->route('production.cutting_jobs.show', $bundle->cutting_job_id)
            ->with('success', 'QC bundle berhasil di-adjust.');
    }

    public function cancelCuttingBundle(
        Request $request,
        CuttingJob $cuttingJob,
        CuttingJobBundle $bundle,
    ): RedirectResponse {
        if ((Auth::user()->role ?? null) !== 'owner') {
            return back()->with('error', 'Hanya OWNER yang boleh membatalkan QC per bundle.');
        }

        if ((int) $bundle->cutting_job_id !== (int) $cuttingJob->id) {
            return back()->with('error', 'Bundle tidak ditemukan di Cutting Job ini.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->qc->cancelCuttingBundleQc(
                bundle: $bundle,
                reason: $validated['reason'],
                actorId: Auth::id(),
            );
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', 'Partial Cancel QC gagal: '.$e->getMessage());
        }

        return redirect()
            ->route('production.qc.cutting.edit', $cuttingJob)
            ->with('success', "QC bundle {$bundle->bundle_code} dibatalkan. Bundle lain tetap berjalan.");
    }

    public function correctCuttingBundle(
        Request $request,
        CuttingJob $cuttingJob,
        CuttingJobBundle $bundle,
    ): RedirectResponse {
        if (! (Auth::user()?->isOwner() ?? false)) {
            return back()->with('error', 'Hanya Owner/Dev yang boleh melakukan koreksi bundle.');
        }

        if ((int) $bundle->cutting_job_id !== (int) $cuttingJob->id) {
            return back()->with('error', 'Bundle tidak ditemukan di Cutting Job ini.');
        }

        $validated = $request->validate([
            'return_fabric_qty' => ['required', 'numeric', 'gt:0'],
            'replacement_item_code' => ['required', 'string', 'max:100'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $correction = $this->qc->correctCuttingBundle(
                bundle: $bundle,
                returnFabricQty: (float) $validated['return_fabric_qty'],
                replacementItemCode: $validated['replacement_item_code'],
                reason: $validated['reason'],
                actorId: Auth::id(),
            );
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', 'Koreksi bundle gagal: '.$e->getMessage());
        }

        return redirect()
            ->route('production.qc.cutting.edit', $cuttingJob)
            ->with('success', sprintf(
                'Bundle %s dikoreksi. %s bahan dikembalikan ke LOT; buat Cutting baru dengan item %s.',
                $bundle->bundle_code,
                number_format((float) $correction->return_fabric_qty, 4, ',', '.'),
                strtoupper((string) $validated['replacement_item_code']),
            ));
    }

    public function updateCuttingBundleItem(
        Request $request,
        CuttingJob $cuttingJob,
        CuttingJobBundle $bundle,
    ): RedirectResponse {
        if ((Auth::user()->role ?? null) !== 'owner') {
            return back()->with('error', 'Hanya OWNER yang boleh mengubah item bundle.');
        }

        if ((int) $bundle->cutting_job_id !== (int) $cuttingJob->id) {
            return back()->with('error', 'Bundle tidak ditemukan di Cutting Job ini.');
        }

        $validated = $request->validate([
            'item_code' => ['required', 'string', 'max:100'],
        ]);

        try {
            $this->qc->updateCuttingBundleItem(
                bundle: $bundle,
                itemCode: $validated['item_code'],
                actorId: Auth::id(),
            );
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', 'Update item bundle gagal: '.$e->getMessage());
        }

        return redirect()
            ->route('production.qc.cutting.edit', $cuttingJob)
            ->with('success', "Item bundle {$bundle->bundle_code} berhasil diubah menjadi ".strtoupper(trim($validated['item_code'])).'. Silakan QC ulang.');
    }

    /* ============================================================
     * QC JAHIT (SEWING)
     * ============================================================
     */

    /**
     * Form QC Jahit — satu SewingReturn per form.
     */
    public function editSewing(SewingReturn $sewingReturn)
    {
        $sewingReturn->load([
            'operator',
            'lines.pickupLine.bundle.finishedItem',
            'lines.pickupLine.bundle.cuttingJob',
        ]);

        // QC yang sudah tersimpan untuk return ini
        $existingQc = QcResult::query()
            ->where('stage', QcResult::STAGE_SEWING)
            ->where('sewing_job_id', $sewingReturn->id)
            ->get()
            ->keyBy('cutting_job_bundle_id');

        $rows = [];
        $filterItems = [];
        foreach ($sewingReturn->lines as $line) {
            $bundle = $line->pickupLine?->bundle;
            if (! $bundle) {
                continue;
            }

            $qc = $existingQc->get($bundle->id);

            // Qty maksimal = total yang disetor penjahit. Setelah QC, line dipecah OK + reject.
            $maxQty = (float) ($line->qty_ok ?? 0) + (float) ($line->qty_reject ?? 0);

            $qtyRejectJahit = 0;
            $qtyRejectBahan = 0;
            if ($qc) {
                if ($qc->reject_reason === 'Reject Bahan') {
                    $qtyRejectBahan = $qc->qty_reject;
                } elseif ($qc->reject_reason === 'Reject Jahit & Bahan') {
                    // fallback jika ada mixed reason
                    $qtyRejectJahit = $qc->qty_reject;
                } else {
                    $qtyRejectJahit = $qc->qty_reject;
                }
            }

            $rows[] = [
                'sewing_return_line_id' => $line->id,
                'bundle_id' => $bundle->id,
                'bundle_code' => $bundle->bundle_code,
                'item_id' => $bundle->finished_item_id,
                'item_code' => $bundle->finishedItem?->code,
                'item_name' => $bundle->finishedItem?->name,
                'cutting_job_code' => $bundle->cuttingJob?->code,
                'qty_max' => $maxQty,
                'qty_ok' => $qc?->qty_ok ?? $maxQty,
                'qty_reject_jahit' => $qtyRejectJahit,
                'qty_reject_bahan' => $qtyRejectBahan,
                'notes' => $qc?->notes,
            ];

            if ($bundle->finishedItem) {
                $filterItems[$bundle->finished_item_id] = [
                    'id' => $bundle->finished_item_id,
                    'code' => $bundle->finishedItem->code,
                    'name' => $bundle->finishedItem->name,
                ];
            }
        }

        $loginOperator = Auth::user()->employee ?? null;
        $hasQcSewing = $existingQc->isNotEmpty();
        $filterItems = collect($filterItems)->values();
        $destinationWarehouses = Warehouse::query()
            ->whereIn('code', ['WIP-FIN', 'WH-PRD', 'WH-RTS'])
            ->orderByRaw("CASE code WHEN 'WIP-FIN' THEN 1 WHEN 'WH-PRD' THEN 2 WHEN 'WH-RTS' THEN 3 ELSE 4 END")
            ->get(['id', 'code', 'name']);

        $defaultDestinationWarehouseId = $destinationWarehouses
            ->firstWhere('id', (int) $sewingReturn->destination_warehouse_id)?->id
            ?? $destinationWarehouses->first()?->id;

        return view('production.qc.sewing_edit', compact(
            'sewingReturn',
            'rows',
            'loginOperator',
            'hasQcSewing',
            'filterItems',
            'destinationWarehouses',
            'defaultDestinationWarehouseId'
        ));
    }

    /**
     * Simpan QC Jahit — gerakkan stok WIP-SEW → gudang tujuan (OK) / REJ-SEW (Reject).
     */
    public function updateSewing(Request $request, SewingReturn $sewingReturn)
    {
        $validated = $request->validate([
            'qc_date' => ['required', 'date'],
            'operator_id' => ['nullable', 'exists:employees,id'],
            'destination_warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')->where(
                    fn ($query) => $query->whereIn('code', ['WIP-FIN', 'WH-RTS', 'WH-PRD'])
                ),
            ],
            'results' => ['required', 'array', 'min:1'],
            'results.*.sewing_return_line_id' => ['nullable', 'exists:sewing_return_lines,id'],
            'results.*.bundle_id' => ['required', 'exists:cutting_job_bundles,id'],
            'results.*.qty_ok' => ['nullable', 'numeric', 'min:0'],
            'results.*.qty_reject_jahit' => ['nullable', 'numeric', 'min:0'],
            'results.*.qty_reject_bahan' => ['nullable', 'numeric', 'min:0'],
            'results.*.notes' => ['nullable', 'string'],
        ]);

        // Operator QC selalu mengikuti akun yang sedang login.
        $validated['operator_id'] = Auth::user()->employee?->id;
        $validated['qc_by_user_id'] = Auth::id();

        $alreadyMoved = DB::table('inventory_mutations')
            ->where('source_type', 'sewing_qc_out')
            ->where('source_id', (int) $sewingReturn->id)
            ->exists();

        if ($alreadyMoved) {
            return back()
                ->with('error', 'QC Jahit sudah pernah diposting dan stok sudah bergerak. Void / koreksi dulu sebelum QC ulang.');
        }

        try {
            $this->qc->saveSewingQc($sewingReturn, $validated);

            if ($sewingReturn->isFillable('status')) {
                $sewingReturn->forceFill(['status' => 'posted'])->save();
            }

            foreach (['postSewingReturnOk', 'postSewingReturnReject'] as $method) {
                try {
                    $this->journal->{$method}($sewingReturn->fresh(), $validated['qc_date']);
                } catch (\Throwable $journalError) {
                    Log::warning("Gagal membuat jurnal {$method} dari QC Jahit", [
                        'sewing_return_id' => $sewingReturn->id,
                        'message' => $journalError->getMessage(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', 'QC Jahit gagal: '.$e->getMessage());
        }

        $destinationCode = $sewingReturn->fresh('destinationWarehouse')->destinationWarehouse?->code ?? 'WH-PRD';

        return redirect()
            ->route('production.qc.index', ['stage' => 'sewing'])
            ->with('success', "QC Jahit berhasil disimpan. Stok {$sewingReturn->warehouse?->code} → {$destinationCode} & REJ-SEW sudah diperbarui.");
    }

    public function overproductionCuttingBundle(Request $request, CuttingJob $cuttingJob, CuttingJobBundle $bundle)
    {
        // pastikan bundle memang milik cutting job ini
        abort_unless((int) $bundle->cutting_job_id === (int) $cuttingJob->id, 404);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'qty_add' => ['required', 'numeric', 'gt:0'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        // item wajib ada
        abort_unless((int) $bundle->finished_item_id > 0, 422);

        $module = 'cutting_overproduction';

        return DB::transaction(function () use ($validated, $bundle, $module) {

            /** @var InventoryAdjustment $adj */
            $adj = InventoryAdjustment::create([
                'module' => $module,
                'date' => $validated['date'] ?? now()->toDateString(), // kalau kamu punya kolom date
                'warehouse_id' => (int) $validated['warehouse_id'], // asumsi header punya warehouse_id
                'status' => 'draft',
                'notes' => trim(
                    ($validated['notes'] ?? '').
                    "\n[Auto] Cutting Overproduction | Bundle {$bundle->bundle_code}"
                ),
                'created_by' => auth()->id(),
                'cutting_job_bundle_id' => $bundle->id, // header ref (kalau kamu jadiin)
            ]);

            $qtyAdd = (float) $validated['qty_add'];

            InventoryAdjustmentLine::create([
                'inventory_adjustment_id' => $adj->id,
                'item_id' => $bundle->finished_item_id,
                'lot_id' => null, // WIP/FG non-LOT
                'qty_change' => $qtyAdd,
                'direction' => 'in',
                'notes' => "Overproduction dari Cutting bundle {$bundle->bundle_code}",
                'cutting_job_bundle_id' => $bundle->id,
            ]);

            // opsi: langsung post biar sekali klik (atau biarkan draft dulu)
            // $this->inventoryAdjustmentService->post($adj);

            return redirect()
                ->route('inventory.adjustments.show', ['inventoryAdjustment' => $adj->getKey()]) // sesuaikan route show kamu
                ->with('success', 'Draft Cutting Overproduction dibuat. Silakan POST untuk masuk audit trail stok.');
        });
    }

    public function revertCuttingToCutting(CuttingJob $cuttingJob): RedirectResponse
    {
        // owner-only (samakan pola dengan cancelCutting)
        if ((Auth::user()->role ?? null) !== 'owner') {
            return back()->with('error', 'Hanya OWNER yang boleh mengubah status ini.');
        }

        return DB::transaction(function () use ($cuttingJob) {

            // hanya boleh kalau status-nya memang sent_to_qc
            if ($cuttingJob->status !== 'sent_to_qc') {
                return back()->with('error', 'Job ini bukan status sent_to_qc.');
            }

            // kalau sudah ada QC cutting, jangan boleh revert (suruh cancel QC saja)
            $hasQc = QcResult::query()
                ->where('stage', QcResult::STAGE_CUTTING)
                ->where('cutting_job_id', $cuttingJob->id)
                ->exists();

            if ($hasQc) {
                return back()->with('error', 'Sudah ada QC Cutting. Gunakan menu Cancel QC (VOID) untuk membatalkan QC terlebih dulu.');
            }

            // OPTIONAL: reset field QC di bundle (kalau kamu simpan qty_qc_ok/reject/status di bundle)
            $cuttingJob->loadMissing('bundles');

            foreach ($cuttingJob->bundles as $b) {
                $b->qty_qc_ok = 0;
                $b->qty_qc_reject = 0;
                $b->status = 'cut'; // atau 'cutting' tergantung enum status bundle kamu
                $b->wip_qty = 0;
                $b->wip_warehouse_id = null;
                $b->wip_posted_at = null;
                $b->cut_wip_qty = 0;
                $b->cut_wip_warehouse_id = null;
                $b->save();
            }

            // update header
            $cuttingJob->update([
                'status' => 'cutting',
                'updated_by' => Auth::id(),
            ]);

            return redirect()
                ->route('production.cutting_jobs.show', $cuttingJob)
                ->with('success', 'Status Cutting Job berhasil dikembalikan ke CUTTING.');
        });
    }
}
