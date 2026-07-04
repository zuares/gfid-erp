<?php

namespace App\Services\Accounting;

use App\Models\CuttingJob;
use App\Models\FinishingJob;
use App\Models\Journal;
use App\Models\Shipment;
use App\Models\SewingPickup;
use App\Models\SewingReturn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductionJournalAuditService
{
    public function __construct(
        protected JournalService $journal,
    ) {}

    public function sourceDefinitions(): array
    {
        return [
            'grn_inv' => [
                'label' => 'GRN Inventory',
                'movement_source_type' => JournalService::SRC_PURCHASE_RECEIPT,
                'journal_source_type' => JournalService::SRC_GRN_ACCRUAL_INV,
                'model' => null,
                'method' => 'postPurchaseReceiptMutationSource',
                'direction' => 'in',
                'effect' => 'Dr 1201/1202/1203 / Cr 2101',
            ],
            'purchase_return_inv' => [
                'label' => 'Retur Pembelian Inventory',
                'movement_source_type' => 'purchase_return',
                'journal_source_type' => JournalService::SRC_PURCHASE_RETURN_INV,
                'model' => null,
                'method' => 'postPurchaseReturnMutationSource',
                'direction' => 'out',
                'effect' => 'Dr 2101 / Cr 1201/1202/1203',
            ],
            'finishing_job' => [
                'label' => 'Finishing ke Barang Jadi',
                'movement_source_type' => FinishingJob::class,
                'journal_source_type' => JournalService::SRC_FINISHING_JOB,
                'model' => FinishingJob::class,
                'method' => 'postFinishingJob',
                'direction' => 'out',
                'effect' => 'Dr 1203/1204 / Cr 1202',
            ],
            'finishing_bom' => [
                'label' => 'BOM Finishing',
                'movement_source_type' => JournalService::SRC_FINISHING_BOM,
                'journal_source_type' => JournalService::SRC_FINISHING_BOM,
                'model' => FinishingJob::class,
                'method' => 'postFinishingBom',
                'direction' => 'out',
                'effect' => 'Dr 1202 / Cr 1201',
            ],
            'cutting_job' => [
                'label' => 'Cutting Material',
                'movement_source_type' => JournalService::SRC_CUTTING_JOB,
                'journal_source_type' => JournalService::SRC_CUTTING_JOB,
                'model' => CuttingJob::class,
                'method' => 'postCuttingJob',
                'direction' => 'out',
                'effect' => 'Dr 1202 / Cr 1201',
            ],
            'cutting_wip' => [
                'label' => 'QC Cutting ke WIP-CUT',
                'movement_source_type' => JournalService::SRC_CUTTING_WIP,
                'journal_source_type' => JournalService::SRC_CUTTING_WIP,
                'model' => CuttingJob::class,
                'method' => 'postCuttingWip',
                'direction' => 'in',
                'effect' => 'Dr 1202 / Cr 1202',
            ],
            'sewing_pickup' => [
                'label' => 'Ambil Jahit',
                'movement_source_type' => JournalService::SRC_SEWING_PICKUP,
                'journal_source_type' => JournalService::SRC_SEWING_PICKUP,
                'model' => SewingPickup::class,
                'method' => 'postSewingPickup',
                'direction' => 'out',
                'effect' => 'Dr 1202 / Cr 1202',
            ],
            'sewing_pickup_supply' => [
                'label' => 'Kelengkapan Jahit',
                'movement_source_type' => JournalService::SRC_SEWING_PICKUP_SUPPLY,
                'journal_source_type' => JournalService::SRC_SEWING_PICKUP_SUPPLY,
                'model' => SewingPickup::class,
                'method' => 'postSewingPickupSupply',
                'direction' => 'out',
                'effect' => 'Dr 1202 / Cr 1201',
            ],
            'sewing_pickup_supply_followup' => [
                'label' => 'Kelengkapan Jahit Menyusul',
                'movement_source_type' => JournalService::SRC_SEWING_PICKUP_SUPPLY_FOLLOWUP,
                'journal_source_type' => JournalService::SRC_SEWING_PICKUP_SUPPLY_FOLLOWUP,
                'model' => null,
                'method' => 'postSewingPickupSupplyFollowupByAdjustment',
                'direction' => 'out',
                'effect' => 'Dr 1202 / Cr 1201',
            ],
            'sewing_return_ok' => [
                'label' => 'Setoran Jahit OK',
                'movement_source_type' => [JournalService::SRC_SEWING_RETURN_OK, 'sewing_qc_in'],
                'journal_source_type' => JournalService::SRC_SEWING_RETURN_OK,
                'model' => SewingReturn::class,
                'method' => 'postSewingReturnOk',
                'direction' => 'in',
                'effect' => 'Dr 1202 / Cr 1202 + Cr 2102',
            ],
            'sewing_return_reject' => [
                'label' => 'Reject Jahit',
                'movement_source_type' => [JournalService::SRC_SEWING_RETURN_REJECT, 'sewing_qc_reject'],
                'journal_source_type' => JournalService::SRC_SEWING_RETURN_REJECT,
                'model' => SewingReturn::class,
                'method' => 'postSewingReturnReject',
                'direction' => 'in',
                'effect' => 'Dr 1204 / Cr 1202',
            ],
            'sewing_rework_ok' => [
                'label' => 'Rework Jahit OK',
                'movement_source_type' => JournalService::SRC_SEWING_REWORK_OK,
                'journal_source_type' => JournalService::SRC_SEWING_REWORK_OK,
                'model' => SewingReturn::class,
                'method' => 'postSewingReworkOk',
                'direction' => 'in',
                'effect' => 'Dr 1202 / Cr 1204 + Cr 2102',
            ],
            'shipment_cogs' => [
                'label' => 'HPP Shipment',
                'movement_source_type' => 'shipment',
                'journal_source_type' => JournalService::SRC_SHIPMENT_COGS,
                'model' => Shipment::class,
                'method' => 'postShipmentCogsFromMutations',
                'direction' => 'out',
                'effect' => 'Dr 5101 / Cr 1203',
            ],
        ];
    }

    public function auditRows(?array $onlySources = null): Collection
    {
        return collect($this->filteredDefinitions($onlySources))
            ->map(function (array $definition, string $key) {
                $ids = $this->movementSourceIds($key)->values();
                $activeJournalIds = $this->activeJournalSourceIds($definition['journal_source_type'], $ids);
                $missingIds = $ids->diff($activeJournalIds)->values();

                return [
                    'key' => $key,
                    'label' => $definition['label'],
                    'effect' => $definition['effect'],
                    'movement_source_type' => $definition['movement_source_type'],
                    'journal_source_type' => $definition['journal_source_type'],
                    'movement_count' => $this->movementCount($key),
                    'document_count' => $ids->count(),
                    'active_journal_count' => $activeJournalIds->count(),
                    'missing_count' => $missingIds->count(),
                    'voided_journal_count' => $this->voidedJournalCount($definition['journal_source_type'], $ids),
                    'amount' => $this->movementAmount($key, $definition['direction']),
                    'missing_preview' => $missingIds->take(12)->all(),
                ];
            })
            ->values();
    }

    public function missingSourceIds(string $key): Collection
    {
        $definition = $this->definition($key);
        $ids = $this->movementSourceIds($key)->values();

        return $ids->diff($this->activeJournalSourceIds($definition['journal_source_type'], $ids))->values();
    }

    public function postMissing(string $key, int $sourceId): ?Journal
    {
        $definition = $this->definition($key);
        if (empty($definition['model'])) {
            return $this->journal->{$definition['method']}($sourceId);
        }

        $model = $this->findModel($definition['model'], $sourceId);
        if (!$model) {
            return null;
        }

        return $this->journal->{$definition['method']}($model);
    }

    public function definition(string $key): array
    {
        $definitions = $this->sourceDefinitions();

        if (!isset($definitions[$key])) {
            throw new \InvalidArgumentException("Source {$key} tidak dikenal.");
        }

        return $definitions[$key];
    }

    protected function filteredDefinitions(?array $onlySources): array
    {
        $definitions = $this->sourceDefinitions();
        $onlySources = collect($onlySources ?? [])->filter()->values();

        if ($onlySources->isEmpty()) {
            return $definitions;
        }

        return array_intersect_key($definitions, array_flip($onlySources->all()));
    }

    protected function movementSourceIds(string $key): Collection
    {
        if ($key === 'finishing_bom') {
            return DB::table('inventory_mutations as im')
                ->join('finishing_job_lines as fjl', 'fjl.id', '=', 'im.source_id')
                ->where('im.source_type', JournalService::SRC_FINISHING_BOM)
                ->whereNotNull('fjl.finishing_job_id')
                ->distinct()
                ->orderBy('fjl.finishing_job_id')
                ->pluck('fjl.finishing_job_id')
                ->map(fn($id) => (int) $id)
                ->filter()
                ->values();
        }

        $definition = $this->definition($key);

        return DB::table('inventory_mutations')
            ->whereIn('source_type', $this->movementSourceTypes($definition))
            ->whereNotNull('source_id')
            ->distinct()
            ->orderBy('source_id')
            ->pluck('source_id')
            ->map(fn($id) => (int) $id)
            ->filter()
            ->values();
    }

    protected function activeJournalSourceIds(string $sourceType, Collection $ids): Collection
    {
        if ($ids->isEmpty()) {
            return collect();
        }

        return Journal::query()
            ->where('source_type', $sourceType)
            ->whereIn('source_id', $ids->all())
            ->whereNull('voided_at')
            ->distinct()
            ->orderBy('source_id')
            ->pluck('source_id')
            ->map(fn($id) => (int) $id)
            ->values();
    }

    protected function voidedJournalCount(string $sourceType, Collection $ids): int
    {
        if ($ids->isEmpty()) {
            return 0;
        }

        return Journal::query()
            ->where('source_type', $sourceType)
            ->whereIn('source_id', $ids->all())
            ->whereNotNull('voided_at')
            ->count();
    }

    protected function movementCount(string $key): int
    {
        if ($key === 'finishing_bom') {
            return DB::table('inventory_mutations')
                ->where('source_type', JournalService::SRC_FINISHING_BOM)
                ->count();
        }

        $definition = $this->definition($key);

        return DB::table('inventory_mutations')
            ->whereIn('source_type', $this->movementSourceTypes($definition))
            ->count();
    }

    protected function movementAmount(string $key, string $direction): float
    {
        if ($key === 'cutting_wip') {
            return round((float) DB::table('inventory_mutations')
                ->whereIn('source_type', [JournalService::SRC_CUTTING_WIP, 'cutting_reject'])
                ->where('qty_change', '>', 0)
                ->sum(DB::raw('ABS(total_cost)')), 2);
        }

        $query = DB::table('inventory_mutations');

        if ($key === 'finishing_bom') {
            $query->where('source_type', JournalService::SRC_FINISHING_BOM);
        } else {
            $definition = $this->definition($key);
            $query->whereIn('source_type', $this->movementSourceTypes($definition));
        }

        if ($direction === 'in') {
            $query->where('qty_change', '>', 0);
        } else {
            $query->where('qty_change', '<', 0);
        }

        return round((float) $query->sum(DB::raw('ABS(total_cost)')), 2);
    }

    protected function movementSourceTypes(array $definition): array
    {
        return array_values((array) $definition['movement_source_type']);
    }

    protected function findModel(string $modelClass, int $id): ?Model
    {
        /** @var class-string<Model> $modelClass */
        return $modelClass::query()->find($id);
    }
}
