<?php

namespace App\Services\Accounting;

use App\Models\FinishingJob;
use App\Models\StandaloneAccountingAccount;
use App\Models\StandaloneAccountingAccountMapping;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductionAccountingAuditService
{
    private const EPS = 0.01;

    /**
     * Read-only production audit definitions.
     *
     * The movement basis follows the direction used by the legacy posting
     * service. It is deliberately kept separate from the new mirror so the
     * report can show legacy gaps before production mirroring is enabled.
     *
     * @return array<string, array<string, mixed>>
     */
    public function sourceDefinitions(): array
    {
        return [
            'cutting_material' => [
                'label' => 'Cutting — bahan menjadi WIP',
                'movement_types' => [JournalService::SRC_CUTTING_JOB],
                'legacy_journal_types' => [JournalService::SRC_CUTTING_JOB],
                'standalone_journal_types' => [JournalService::SRC_CUTTING_JOB],
                'basis_direction' => 'out',
                'required_mappings' => ['1201', '1202'],
            ],
            'cutting_material_correction' => [
                'label' => 'Cutting — koreksi bundle kembali ke bahan baku',
                'movement_types' => [JournalService::SRC_CUTTING_BUNDLE_MATERIAL_RETURN],
                'legacy_journal_types' => [JournalService::SRC_CUTTING_BUNDLE_MATERIAL_RETURN],
                'standalone_journal_types' => [JournalService::SRC_CUTTING_BUNDLE_MATERIAL_RETURN],
                'basis_direction' => 'in',
                'document_table' => 'cutting_bundle_corrections',
                'document_status' => 'returned',
                'required_mappings' => ['1201', '1202'],
            ],
            'cutting_wage_correction' => [
                'label' => 'Cutting — reversal upah bundle yang dikoreksi',
                'movement_types' => [],
                'legacy_journal_types' => [JournalService::SRC_CUTTING_BUNDLE_CORRECTION_WAGE_VOID],
                'standalone_journal_types' => [JournalService::SRC_CUTTING_BUNDLE_CORRECTION_WAGE_VOID],
                'basis_direction' => null,
                'document_table' => 'cutting_bundle_corrections',
                'document_status' => 'returned',
                'required_mappings' => ['6120', '1202'],
            ],
            'cutting_qc' => [
                'label' => 'QC Cutting — OK dan reject',
                'movement_types' => [
                    JournalService::SRC_CUTTING_WIP,
                    'cutting_reject',
                    'cutting_qc_adjust_in',
                    'cutting_qc_adjust_out',
                ],
                'legacy_journal_types' => [
                    JournalService::SRC_CUTTING_WIP,
                    // BOM sewing supply masuk ke nilai output WIP, tetapi
                    // diposting sebagai jurnal terpisah. Sertakan di
                    // rekonsiliasi QC agar basis mutasi Rp939.200 dibandingkan
                    // dengan jurnal QC Rp839.200 + BOM Rp100.000.
                    JournalService::SRC_CUTTING_QC_SEWING_SUPPLY,
                    JournalService::SRC_CUTTING_QC_ADJUST,
                    'cutting_qc_bundle_void',
                    'cutting_qc_bundle_repost',
                ],
                'standalone_journal_types' => [
                    JournalService::SRC_CUTTING_WIP,
                    JournalService::SRC_CUTTING_QC_SEWING_SUPPLY,
                    JournalService::SRC_CUTTING_QC_ADJUST,
                    'cutting_qc_bundle_void',
                    'cutting_qc_bundle_repost',
                ],
                'legacy_journal_parent_types' => [
                    JournalService::SRC_CUTTING_QC_ADJUST,
                    'cutting_qc_bundle_void',
                    'cutting_qc_bundle_repost',
                ],
                'legacy_journal_parent_table' => 'cutting_qc_sources',
                'standalone_journal_parent_types' => [
                    JournalService::SRC_CUTTING_QC_ADJUST,
                    'cutting_qc_bundle_void',
                    'cutting_qc_bundle_repost',
                ],
                'standalone_journal_parent_table' => 'cutting_qc_sources',
                'basis_direction' => 'in',
                'required_mappings' => ['1201', '1202', '1204', '2102'],
            ],
            'cutting_qc_sewing_supply' => [
                'label' => 'QC Cutting — auto BOM kelengkapan jahit',
                'movement_types' => [JournalService::SRC_CUTTING_QC_SEWING_SUPPLY],
                'legacy_journal_types' => [JournalService::SRC_CUTTING_QC_SEWING_SUPPLY],
                'standalone_journal_types' => [JournalService::SRC_CUTTING_QC_SEWING_SUPPLY],
                'basis_direction' => 'out',
                'required_mappings' => ['1201', '1202'],
            ],
            'sewing_pickup' => [
                'label' => 'Sewing pickup',
                'movement_types' => [JournalService::SRC_SEWING_PICKUP],
                'legacy_journal_types' => [
                    JournalService::SRC_SEWING_PICKUP,
                    JournalService::SRC_SEWING_PICKUP_SUPPLY,
                    JournalService::SRC_SEWING_PICKUP_WAGE,
                    JournalService::SRC_SEWING_PICKUP_LINE_VOID,
                    JournalService::SRC_SEWING_PICKUP_SUPPLY_VOID_LINE,
                ],
                'standalone_journal_types' => [
                    JournalService::SRC_SEWING_PICKUP,
                    JournalService::SRC_SEWING_PICKUP_SUPPLY,
                    JournalService::SRC_SEWING_PICKUP_WAGE,
                    JournalService::SRC_SEWING_PICKUP_LINE_VOID,
                    JournalService::SRC_SEWING_PICKUP_SUPPLY_VOID_LINE,
                ],
                // Upah disimpan dengan source_id sewing_pickup_line_id.
                // Reversal line dan reversal supply juga disimpan dengan
                // source_id sewing_pickup_line_id; agregasikan kembali ke
                // parent sewing_pickup_id agar VOID sebagian ikut teraudit.
                'legacy_journal_parent_types' => [
                    JournalService::SRC_SEWING_PICKUP_WAGE,
                    JournalService::SRC_SEWING_PICKUP_LINE_VOID,
                    JournalService::SRC_SEWING_PICKUP_SUPPLY_VOID_LINE,
                ],
                'standalone_journal_parent_types' => [
                    JournalService::SRC_SEWING_PICKUP_WAGE,
                    JournalService::SRC_SEWING_PICKUP_LINE_VOID,
                    JournalService::SRC_SEWING_PICKUP_SUPPLY_VOID_LINE,
                ],
                'basis_direction' => 'in',
                'required_mappings' => ['1201', '1202', '2102'],
            ],
            'sewing_pickup_reject' => [
                'label' => 'Sewing pickup — reject susulan WIP-CUT → REJ-SEW',
                'movement_types' => [StandaloneProductionPostingService::SOURCE_SEWING_PICKUP_REJECT_LEGACY],
                'legacy_journal_types' => [],
                'standalone_journal_types' => [StandaloneProductionPostingService::SOURCE_SEWING_PICKUP_REJECT_LEGACY],
                'standalone_journal_parent_types' => [StandaloneProductionPostingService::SOURCE_SEWING_PICKUP_REJECT_LEGACY],
                'standalone_journal_parent_table' => 'sewing_pickup_lines',
                'basis_direction' => 'out',
                'expected_legacy_journal' => false,
                'expected_standalone_journal' => true,
                'required_mappings' => ['1202', '1204'],
            ],
            'cutting_pre_sewing_reject' => [
                'label' => 'Cutting — reject sebelum jahit WIP-CUT → REJ-CUT',
                'movement_types' => [StandaloneProductionPostingService::SOURCE_SEWING_PICKUP_REJECT],
                'legacy_journal_types' => [],
                'standalone_journal_types' => [StandaloneProductionPostingService::SOURCE_SEWING_PICKUP_REJECT],
                'standalone_journal_parent_types' => [StandaloneProductionPostingService::SOURCE_SEWING_PICKUP_REJECT],
                'standalone_journal_parent_table' => 'sewing_pickup_lines',
                'basis_direction' => 'out',
                'expected_legacy_journal' => false,
                'expected_standalone_journal' => true,
                'required_mappings' => ['1202', '1204'],
            ],
            'sewing_pickup_reject_supply' => [
                'label' => 'Sewing pickup — return BOM kelengkapan reject ke RM',
                'movement_types' => [\App\Services\Production\SewingPickupRejectSupplyService::SOURCE],
                'legacy_journal_types' => [],
                'standalone_journal_types' => [\App\Services\Production\SewingPickupRejectSupplyService::SOURCE],
                'standalone_journal_parent_types' => [\App\Services\Production\SewingPickupRejectSupplyService::SOURCE],
                'standalone_journal_parent_table' => 'sewing_pickup_lines',
                'basis_direction' => 'in',
                'expected_legacy_journal' => false,
                'expected_standalone_journal' => true,
                'required_mappings' => ['1201', '1204'],
            ],
            'sewing_return_ok' => [
                'label' => 'Sewing return OK',
                'movement_types' => [JournalService::SRC_SEWING_RETURN_OK, 'sewing_qc_in'],
                'legacy_journal_types' => [JournalService::SRC_SEWING_RETURN_OK],
                'standalone_journal_types' => [JournalService::SRC_SEWING_RETURN_OK],
                'basis_direction' => 'in',
                'required_mappings' => ['1202', '1203', '1204', '2102'],
            ],
            'sewing_reject_rework' => [
                'label' => 'Sewing reject / rework',
                'movement_types' => [
                    JournalService::SRC_SEWING_RETURN_REJECT,
                    'sewing_qc_reject',
                    JournalService::SRC_SEWING_REWORK_OK,
                ],
                'legacy_journal_types' => [
                    JournalService::SRC_SEWING_RETURN_REJECT,
                    JournalService::SRC_SEWING_REWORK_OK,
                ],
                'standalone_journal_types' => [
                    JournalService::SRC_SEWING_RETURN_REJECT,
                    JournalService::SRC_SEWING_REWORK_OK,
                ],
                'basis_direction' => 'in',
                'required_mappings' => ['1202', '1204', '1203', '2102', '6120'],
            ],
            'sewing_reject_convert' => [
                'label' => 'Sewing reject convert — REJ-SEW ke WH-RTS',
                'movement_types' => [StandaloneProductionPostingService::SOURCE_SEWING_REJECT_CONVERT],
                'legacy_journal_types' => [],
                'standalone_journal_types' => [StandaloneProductionPostingService::SOURCE_SEWING_REJECT_CONVERT],
                'document_table' => 'sewing_reject_conversions',
                'document_status' => 'posted',
                'expected_legacy_journal' => false,
                'expected_standalone_journal' => true,
                'basis_direction' => 'out',
                'required_mappings' => [],
            ],
            'finishing_output' => [
                'label' => 'Finishing — WIP menjadi barang jadi/reject',
                'movement_types' => [FinishingJob::class],
                'legacy_journal_types' => [JournalService::SRC_FINISHING_JOB],
                'standalone_journal_types' => [JournalService::SRC_FINISHING_JOB],
                // Nilai jurnal finishing_job adalah nilai output yang masuk
                // ke WH-PRD/REJ-FIN. Nilai WIP keluar lebih kecil ketika BOM
                // packing ditambahkan; konsumsi BOM direkonsiliasi terpisah
                // pada stage finishing_bom.
                'basis_direction' => 'in',
                'required_mappings' => ['1202', '1203', '1204'],
            ],
            'finishing_bom' => [
                'label' => 'Finishing — konsumsi BOM',
                'movement_types' => [JournalService::SRC_FINISHING_BOM],
                'legacy_journal_types' => [JournalService::SRC_FINISHING_BOM],
                'standalone_journal_types' => [JournalService::SRC_FINISHING_BOM],
                'basis_direction' => 'out',
                'movement_parent_table' => 'finishing_job_lines',
                'required_mappings' => ['1201', '1202'],
            ],
            'packing' => [
                'label' => 'Packing',
                'movement_types' => [],
                'legacy_journal_types' => [],
                'standalone_journal_types' => [],
                'basis_direction' => null,
                'document_table' => 'packing_jobs',
                'document_status' => 'posted',
                'expected_legacy_journal' => false,
                'required_mappings' => [],
            ],
            'shipment_cogs' => [
                'label' => 'Shipment — HPP saat barang keluar',
                'movement_types' => ['shipment'],
                'legacy_journal_types' => [JournalService::SRC_SHIPMENT_COGS],
                'standalone_journal_types' => [JournalService::SRC_SHIPMENT_COGS],
                'basis_direction' => 'out',
                // Shipment VOID tetap ditampilkan sebagai VOID_ONLY melalui jurnal,
                // tetapi mutasi outbound-nya tidak boleh menjadi basis saldo aktif.
                'movement_document_table' => 'shipments',
                'required_mappings' => ['1203', '5101'],
            ],
        ];
    }

    /**
     * @return array{scope: array<string, mixed>, summary: array<string, mixed>, rows: array<int, array<string, mixed>>, details: array<int, array<string, mixed>>}
     */
    public function report(?string $from = null, ?string $to = null, ?array $onlyStages = null, int $detailLimit = 200): array
    {
        $definitions = $this->filteredDefinitions($onlyStages);
        $rows = collect($definitions)
            ->map(fn (array $definition, string $key): array => $this->auditStage($key, $definition, $from, $to, $detailLimit))
            ->values();

        $details = $rows->flatMap(fn (array $row) => $row['details'])->values();
        $reviewRows = $rows->where('status', 'REVIEW')->count();

        return [
            'scope' => [
                'from' => $from,
                'to' => $to,
                'stages' => array_keys($definitions),
                'standalone_required' => (bool) config('accounting.standalone_production_audit_required', false),
                'standalone_audit_cutover_at' => optional($this->auditCutoverAt())->toDateTimeString(),
            ],
            'summary' => [
                'stages' => $rows->count(),
                'documents' => (int) $rows->sum('document_count'),
                'mutations' => (int) $rows->sum('movement_count'),
                'legacy_missing' => (int) $rows->sum('legacy_missing_count'),
                'standalone_missing' => (int) $rows->sum('standalone_missing_count'),
                'standalone_historical_gaps' => (int) $rows->sum('standalone_historical_gap_count'),
                'mapping_missing' => (int) $rows->sum('mapping_missing_count'),
                'variance' => round((float) $rows->sum('stock_vs_legacy_variance'), 2),
                'review_rows' => $reviewRows,
                'status' => $reviewRows > 0 ? 'REVIEW' : 'PASS',
            ],
            'rows' => $rows->map(fn (array $row): array => collect($row)->except('details')->all())->all(),
            'details' => $details->all(),
        ];
    }

    /** @return array<string, array<string, mixed>> */
    public function definitionsFor(?array $onlyStages = null): array
    {
        return $this->filteredDefinitions($onlyStages);
    }

    /** @return array<string, array<string, mixed>> */
    private function filteredDefinitions(?array $onlyStages): array
    {
        $definitions = $this->sourceDefinitions();
        $onlyStages = collect($onlyStages ?? [])->filter()->values();

        if ($onlyStages->isEmpty()) {
            return $definitions;
        }

        return array_intersect_key($definitions, array_flip($onlyStages->all()));
    }

    /** @param array<string, mixed> $definition */
    private function auditStage(string $key, array $definition, ?string $from, ?string $to, int $detailLimit): array
    {
        $movementRows = $this->movementRows($definition, $from, $to);
        $legacyRows = $this->journalRows(
            'journals',
            'journal_lines',
            $definition['legacy_journal_types'],
            $from,
            $to,
            $definition['legacy_journal_parent_types'] ?? [],
            $definition['legacy_journal_parent_table'] ?? null,
        );
        $standaloneRows = $this->journalRows(
            'standalone_accounting_journals',
            'standalone_accounting_journal_lines',
            $definition['standalone_journal_types'],
            $from,
            $to,
            $definition['standalone_journal_parent_types'] ?? [],
            $definition['standalone_journal_parent_table'] ?? null,
        );
        $documentRows = $this->documentRows($definition, $from, $to);

        $movementById = $movementRows->keyBy('source_id');
        $legacyById = $legacyRows->keyBy('source_id');
        $standaloneById = $standaloneRows->keyBy('source_id');
        $documentIds = $documentRows->pluck('source_id')
            ->merge($movementRows->pluck('source_id'))
            ->merge($legacyRows->pluck('source_id'))
            ->merge($standaloneRows->pluck('source_id'))
            ->filter(fn ($id) => $id !== null)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values();

        // VOID line mengembalikan material ke WIP-CUT, tetapi mutasi awal
        // WIP-SEW masih membawa komponen upah. Upah tersebut sudah dikeluarkan
        // dari jurnal aktif; kurangi basis audit agar rekonsiliasi fisik dan
        // jurnal membandingkan komponen yang sama.
        $movementBasisById = $movementRows
            ->mapWithKeys(fn (array $row): array => [
                (int) $row['source_id'] => (float) ($row['basis_value'] ?? 0),
            ]);
        if ($key === 'sewing_pickup') {
            foreach ($this->voidedSewingPickupWageAdjustments($documentIds) as $sourceId => $adjustment) {
                if ($movementBasisById->has((int) $sourceId)) {
                    $movementBasisById->put(
                        (int) $sourceId,
                        max((float) $movementBasisById->get((int) $sourceId) - (float) $adjustment, 0),
                    );
                }
            }
        }

        $expectedLegacy = $definition['expected_legacy_journal'] ?? true;
        $standaloneRequired = (bool) config('accounting.standalone_production_audit_required', false);
        $standaloneExpected = (bool) ($definition['expected_standalone_journal'] ?? $expectedLegacy);
        $standaloneRequiredForStage = $standaloneExpected
            && ($standaloneRequired || $this->dualPostingEnabledForStage($key));
        $legacyVoidOnly = $legacyRows->isNotEmpty()
            && $legacyRows->every(fn (array $row): bool => (int) $row['active_journal_count'] === 0
                && (int) $row['voided_journal_count'] > 0);
        $standaloneVoidOnly = $standaloneRows->isNotEmpty()
            && $standaloneRows->every(fn (array $row): bool => (int) $row['active_journal_count'] === 0
                && (int) $row['voided_journal_count'] > 0);
        $legacyMissingIds = $expectedLegacy
            ? $documentIds->filter(fn (int $id): bool => ((int) ($legacyById[$id]['active_journal_count'] ?? 0)) === 0
                && ((int) ($legacyById[$id]['voided_journal_count'] ?? 0)) === 0)->values()
            : collect();
        // Gap tetap ditampilkan walau mirror belum diwajibkan, agar audit
        // memberi visibility tanpa memblokir legacy-only stage.
        $standaloneMissingIds = $standaloneExpected
            ? $documentIds->filter(fn (int $id): bool => ((int) ($standaloneById[$id]['active_journal_count'] ?? 0)) === 0
                && ((int) ($standaloneById[$id]['voided_journal_count'] ?? 0)) === 0)->values()
            : collect();
        $auditCutoverAt = $this->auditCutoverAt();
        $standaloneHistoricalGapIds = $standaloneMissingIds
            ->filter(function (int $id) use ($legacyById, $auditCutoverAt): bool {
                $firstLegacyJournalAt = $legacyById[$id]['first_journal_created_at'] ?? null;

                return $auditCutoverAt !== null
                    && $firstLegacyJournalAt !== null
                    && Carbon::parse($firstLegacyJournalAt)->lt($auditCutoverAt);
            })
            ->values();
        $standaloneActiveMissingIds = $standaloneMissingIds
            ->diff($standaloneHistoricalGapIds)
            ->values();

        $movementBasisValue = $this->movementBasisValue($movementRows, $definition['basis_direction']);
        $legacyDebit = round((float) $legacyRows->sum('active_debit'), 2);
        $legacyCredit = round((float) $legacyRows->sum('active_credit'), 2);
        $legacyBalance = round($legacyDebit - $legacyCredit, 2);
        $standaloneDebit = round((float) $standaloneRows->sum('active_debit'), 2);
        $standaloneCredit = round((float) $standaloneRows->sum('active_credit'), 2);
        $standaloneBalance = round($standaloneDebit - $standaloneCredit, 2);
        $legacyValue = $legacyDebit > 0 ? $legacyDebit : $legacyCredit;
        $activeMovementBasisValue = $documentIds->sum(function (int $sourceId) use ($movementBasisById, $legacyById): float {
            return ((int) ($legacyById[$sourceId]['active_journal_count'] ?? 0)) > 0
                ? (float) $movementBasisById->get($sourceId, 0)
                : 0.0;
        });
        $activeLegacyValue = $legacyRows
            ->filter(fn (array $row): bool => (int) $row['active_journal_count'] > 0)
            ->sum(fn (array $row): float => (float) ($row['active_debit'] > 0 ? $row['active_debit'] : $row['active_credit']));
        $variance = $activeMovementBasisValue > 0 && $activeLegacyValue > 0
            ? round($activeLegacyValue - $activeMovementBasisValue, 2)
            : 0.0;
        $mapping = $this->mappingStatus($definition['required_mappings'] ?? []);
        $legacyUnbalanced = abs($legacyBalance) > self::EPS && $legacyRows->isNotEmpty();
        $standaloneUnbalanced = abs($standaloneBalance) > self::EPS && $standaloneRows->isNotEmpty();
        $status = ($mapping['missing_count'] > 0
            || $legacyMissingIds->isNotEmpty()
            || $legacyUnbalanced
            || ($standaloneRequiredForStage && ($standaloneActiveMissingIds->isNotEmpty() || $standaloneUnbalanced)))
            ? 'REVIEW' : 'PASS';

        $details = $documentIds->take(max($detailLimit, 1))->map(function (int $sourceId) use ($key, $definition, $expectedLegacy, $movementById, $movementBasisById, $legacyById, $standaloneById, $legacyMissingIds, $standaloneActiveMissingIds, $standaloneHistoricalGapIds, $standaloneRequiredForStage): array {
            $movement = $movementById->get($sourceId, []);
            $legacy = $legacyById->get($sourceId, []);
            $standalone = $standaloneById->get($sourceId, []);
            $legacyDebit = round((float) ($legacy['active_debit'] ?? 0), 2);
            $legacyCredit = round((float) ($legacy['active_credit'] ?? 0), 2);
            $legacyValue = $legacyDebit > 0 ? $legacyDebit : $legacyCredit;
            $movementValue = round((float) ($movement['basis_value'] ?? 0), 2);
            $movementBasisValue = round((float) $movementBasisById->get($sourceId, $movementValue), 2);
            $legacyStatus = ((int) ($legacy['active_journal_count'] ?? 0)) > 0
                ? 'POSTED'
                : (((int) ($legacy['voided_journal_count'] ?? 0)) > 0 ? 'VOID_ONLY' : 'MISSING');
            $legacyVoidOnlyForDocument = $legacyStatus === 'VOID_ONLY';
            $standaloneStatus = ((int) ($standalone['active_journal_count'] ?? 0)) > 0
                ? 'POSTED'
                : (((int) ($standalone['voided_journal_count'] ?? 0)) > 0 ? 'VOID_ONLY' : 'MISSING');

            return [
                'stage' => $key,
                'source_id' => $sourceId,
                'movement_count' => (int) ($movement['movement_count'] ?? 0),
                'movement_qty_in' => (float) ($movement['qty_in'] ?? 0),
                'movement_qty_out' => (float) ($movement['qty_out'] ?? 0),
                'movement_value' => $movementValue,
                'legacy_status' => $legacyStatus,
                'legacy_journal_count' => (int) ($legacy['journal_count'] ?? 0),
                'legacy_voided_journal_count' => (int) ($legacy['voided_journal_count'] ?? 0),
                'legacy_debit' => $legacyDebit,
                'legacy_credit' => $legacyCredit,
                'movement_basis_value' => $movementBasisValue,
                'legacy_variance' => ! $expectedLegacy || $definition['basis_direction'] === null || $legacyVoidOnlyForDocument
                    ? 0.0
                    : round($legacyValue - $movementBasisValue, 2),
                'standalone_status' => $standaloneStatus,
                'standalone_journal_count' => (int) ($standalone['journal_count'] ?? 0),
                'standalone_voided_journal_count' => (int) ($standalone['voided_journal_count'] ?? 0),
                'standalone_debit' => round((float) ($standalone['active_debit'] ?? 0), 2),
                'standalone_credit' => round((float) ($standalone['active_credit'] ?? 0), 2),
                'legacy_missing' => $legacyMissingIds->contains($sourceId),
                'standalone_missing' => $standaloneRequiredForStage && $standaloneActiveMissingIds->contains($sourceId),
                'standalone_historical_gap' => $standaloneHistoricalGapIds->contains($sourceId),
            ];
        })->all();

        return [
            'key' => $key,
            'label' => $definition['label'],
            'document_count' => $documentIds->count(),
            'movement_count' => (int) $movementRows->sum('movement_count'),
            'movement_qty_in' => round((float) $movementRows->sum('qty_in'), 3),
            'movement_qty_out' => round((float) $movementRows->sum('qty_out'), 3),
            'movement_value_in' => round((float) $movementRows->sum('value_in'), 2),
            'movement_value_out' => round((float) $movementRows->sum('value_out'), 2),
            'movement_basis_value' => round($movementBasisValue, 2),
            'legacy_journal_count' => (int) $legacyRows->sum('journal_count'),
            'legacy_active_journal_count' => (int) $legacyRows->sum('active_journal_count'),
            'legacy_voided_journal_count' => (int) $legacyRows->sum('voided_journal_count'),
            'legacy_missing_count' => $legacyMissingIds->count(),
            'legacy_debit' => $legacyDebit,
            'legacy_credit' => $legacyCredit,
            'legacy_balance' => $legacyBalance,
            'standalone_journal_count' => (int) $standaloneRows->sum('journal_count'),
            'standalone_active_journal_count' => (int) $standaloneRows->sum('active_journal_count'),
            'standalone_voided_journal_count' => (int) $standaloneRows->sum('voided_journal_count'),
            'standalone_missing_count' => $standaloneActiveMissingIds->count(),
            'standalone_historical_gap_count' => $standaloneHistoricalGapIds->count(),
            'standalone_debit' => $standaloneDebit,
            'standalone_credit' => $standaloneCredit,
            'standalone_balance' => $standaloneBalance,
            'mapping_status' => $mapping['status'],
            'mapping_missing_count' => $mapping['missing_count'],
            'mapping_missing' => $mapping['missing'],
            'stock_vs_legacy_variance' => $variance,
            'status' => $status,
            'details' => $details,
        ];
    }

    private function dualPostingEnabledForStage(string $stage): bool
    {
        $configKey = match ($stage) {
            'cutting_material',
            'cutting_material_correction',
            'cutting_wage_correction',
            'cutting_qc',
            'cutting_qc_sewing_supply' => 'accounting.standalone_dual_posting_production_cutting_enabled',
            'sewing_pickup',
            'sewing_pickup_reject',
            'cutting_pre_sewing_reject' => 'accounting.standalone_dual_posting_production_sewing_pickup_enabled',
            'sewing_return_ok',
            'sewing_reject_rework',
            'sewing_reject_convert' => 'accounting.standalone_dual_posting_production_sewing_return_enabled',
            'finishing_output',
            'finishing_bom' => 'accounting.standalone_dual_posting_production_finishing_enabled',
            'shipment_cogs' => 'accounting.standalone_dual_posting_production_shipment_enabled',
            default => null,
        };

        return $configKey !== null && (bool) config($configKey, false);
    }

    /** @param array<string, mixed> $definition */
    private function movementRows(array $definition, ?string $from, ?string $to): Collection
    {
        if (empty($definition['movement_types'])) {
            return collect();
        }

        $query = DB::table('inventory_mutations as im');
        $sourceId = 'im.source_id';

        if (($definition['movement_parent_table'] ?? null) === 'finishing_job_lines') {
            $query->join('finishing_job_lines as fjl', 'fjl.id', '=', 'im.source_id');
            $sourceId = 'fjl.finishing_job_id';
        }

        if (($definition['movement_document_table'] ?? null) === 'shipments') {
            $query->join('shipments as movement_document', 'movement_document.id', '=', 'im.source_id')
                ->whereNull('movement_document.cancelled_at');
        }

        return $query
            ->whereIn('im.source_type', $definition['movement_types'])
            ->whereNotNull($sourceId)
            ->when($from, fn ($q) => $q->whereDate('im.date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('im.date', '<=', $to))
            ->groupBy($sourceId)
            ->selectRaw("{$sourceId} as source_id")
            ->selectRaw('COUNT(im.id) as movement_count')
            ->selectRaw('SUM(CASE WHEN im.qty_change > 0 THEN ABS(im.qty_change) ELSE 0 END) as qty_in')
            ->selectRaw('SUM(CASE WHEN im.qty_change < 0 THEN ABS(im.qty_change) ELSE 0 END) as qty_out')
            ->selectRaw('SUM(CASE WHEN im.qty_change > 0 THEN ABS(COALESCE(im.total_cost, 0)) ELSE 0 END) as value_in')
            ->selectRaw('SUM(CASE WHEN im.qty_change < 0 THEN ABS(COALESCE(im.total_cost, 0)) ELSE 0 END) as value_out')
            ->get()
            ->map(function ($row) use ($definition): array {
                $row = (array) $row;
                $row['source_id'] = (int) $row['source_id'];
                $row['basis_value'] = $definition['basis_direction'] === 'in'
                    ? (float) $row['value_in']
                    : (float) $row['value_out'];

                return $row;
            });
    }

    private function documentRows(array $definition, ?string $from, ?string $to): Collection
    {
        if (empty($definition['document_table'])) {
            return collect();
        }

        return DB::table($definition['document_table'])
            ->when(isset($definition['document_status']), fn ($q) => $q->where('status', $definition['document_status']))
            ->when($from, fn ($q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('date', '<=', $to))
            ->pluck('id')
            ->map(fn ($id): array => ['source_id' => (int) $id])
            ->values();
    }

    private function journalRows(
        string $journalTable,
        string $lineTable,
        array $sourceTypes,
        ?string $from,
        ?string $to,
        array $parentSourceTypes = [],
        ?string $parentTable = null,
    ): Collection {
        if (empty($sourceTypes)) {
            return collect();
        }

        $query = DB::table("{$journalTable} as j")
            ->leftJoin("{$lineTable} as l", 'l.journal_id', '=', 'j.id')
            ->where(function ($query) use ($sourceTypes): void {
                foreach ($sourceTypes as $sourceType) {
                    $query
                        ->orWhere('j.source_type', $sourceType)
                        ->orWhere('j.source_type', 'like', $sourceType.'_repost_%');
                }
            });

        $sourceIdExpression = 'j.source_id';
        if ($parentSourceTypes !== []) {
            if ($parentTable === 'cutting_qc_sources') {
                $bundleSourceTypes = [JournalService::SRC_CUTTING_QC_ADJUST];
                $cancellationSourceTypes = ['cutting_qc_bundle_void', 'cutting_qc_bundle_repost'];
                $query->leftJoin('cutting_job_bundles as parent_bundle', function ($join) use ($bundleSourceTypes): void {
                    $join->on('parent_bundle.id', '=', 'j.source_id')
                        ->where(function ($query) use ($bundleSourceTypes): void {
                            foreach ($bundleSourceTypes as $sourceType) {
                                $query
                                    ->orWhere('j.source_type', $sourceType)
                                    ->orWhere('j.source_type', 'like', $sourceType.'_repost_%');
                            }
                        });
                });
                $query->leftJoin('cutting_qc_cancellations as parent_cancellation', function ($join) use ($cancellationSourceTypes): void {
                    $join->on('parent_cancellation.id', '=', 'j.source_id')
                        ->where(function ($query) use ($cancellationSourceTypes): void {
                            foreach ($cancellationSourceTypes as $sourceType) {
                                $query
                                    ->orWhere('j.source_type', $sourceType)
                                    ->orWhere('j.source_type', 'like', $sourceType.'_repost_%');
                            }
                        });
                });
                $sourceIdExpression = 'COALESCE(parent_bundle.cutting_job_id, parent_cancellation.cutting_job_id, j.source_id)';
            } elseif ($parentTable === 'cutting_job_bundles') {
                $query->leftJoin('cutting_job_bundles as parent_bundle', function ($join) use ($parentSourceTypes): void {
                    $join->on('parent_bundle.id', '=', 'j.source_id')
                        ->where(function ($query) use ($parentSourceTypes): void {
                            foreach ($parentSourceTypes as $sourceType) {
                                $query
                                    ->orWhere('j.source_type', $sourceType)
                                    ->orWhere('j.source_type', 'like', $sourceType.'_repost_%');
                            }
                        });
                });
                $sourceIdExpression = 'COALESCE(parent_bundle.cutting_job_id, j.source_id)';
            } elseif ($parentTable === 'cutting_qc_cancellations') {
                $query->leftJoin('cutting_qc_cancellations as parent_cancellation', function ($join) use ($parentSourceTypes): void {
                    $join->on('parent_cancellation.id', '=', 'j.source_id')
                        ->where(function ($query) use ($parentSourceTypes): void {
                            foreach ($parentSourceTypes as $sourceType) {
                                $query
                                    ->orWhere('j.source_type', $sourceType)
                                    ->orWhere('j.source_type', 'like', $sourceType.'_repost_%');
                            }
                        });
                });
                $sourceIdExpression = 'COALESCE(parent_cancellation.cutting_job_id, j.source_id)';
            } else {
                $query->leftJoin('sewing_pickup_lines as parent_line', function ($join) use ($parentSourceTypes): void {
                    $join->on('parent_line.id', '=', 'j.source_id')
                        ->whereIn('j.source_type', $parentSourceTypes);
                });
                $sourceIdExpression = 'COALESCE(parent_line.sewing_pickup_id, j.source_id)';
            }
            $query->whereRaw($sourceIdExpression.' IS NOT NULL');
        } else {
            $query->whereNotNull('j.source_id');
        }

        return $query
            ->when($from, fn ($q) => $q->whereDate('j.date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('j.date', '<=', $to))
            ->groupByRaw($sourceIdExpression)
            ->selectRaw($sourceIdExpression.' as source_id')
            ->selectRaw('COUNT(DISTINCT j.id) as journal_count')
            ->selectRaw('MIN(j.created_at) as first_journal_created_at')
            ->selectRaw('COUNT(DISTINCT CASE WHEN j.posted_at IS NOT NULL THEN j.id END) as posted_journal_count')
            ->selectRaw('COUNT(DISTINCT CASE WHEN j.voided_at IS NULL AND j.posted_at IS NOT NULL THEN j.id END) as active_journal_count')
            ->selectRaw('COUNT(DISTINCT CASE WHEN j.voided_at IS NOT NULL THEN j.id END) as voided_journal_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN j.voided_at IS NULL AND j.posted_at IS NOT NULL THEN l.debit ELSE 0 END), 0) as active_debit')
            ->selectRaw('COALESCE(SUM(CASE WHEN j.voided_at IS NULL AND j.posted_at IS NOT NULL THEN l.credit ELSE 0 END), 0) as active_credit')
            ->selectRaw('COALESCE(SUM(CASE WHEN j.voided_at IS NOT NULL THEN l.debit ELSE 0 END), 0) as voided_debit')
            ->selectRaw('COALESCE(SUM(CASE WHEN j.voided_at IS NOT NULL THEN l.credit ELSE 0 END), 0) as voided_credit')
            ->get()
            ->map(fn ($row): array => array_merge((array) $row, ['source_id' => (int) $row->source_id]));
    }

    private function auditCutoverAt(): ?Carbon
    {
        $value = config('accounting.standalone_production_audit_cutover_at');

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /** @param Collection<int, array<string, mixed>> $rows */
    private function movementBasisValue(Collection $rows, ?string $direction): float
    {
        if ($direction === null) {
            return 0.0;
        }

        return round((float) $rows->sum($direction === 'in' ? 'value_in' : 'value_out'), 2);
    }

    /**
     * Upah dari line VOID masih ikut berada di nilai mutasi WIP-SEW original,
     * sedangkan jurnal upahnya sudah VOID. Kembalikan adjustment per pickup
     * agar audit tidak menganggap upah VOID sebagai saldo aktif.
     *
     * @param  Collection<int, int>  $sourceIds
     * @return array<int, float>
     */
    private function voidedSewingPickupWageAdjustments(Collection $sourceIds): array
    {
        if ($sourceIds->isEmpty()) {
            return [];
        }

        return DB::table('sewing_pickup_lines')
            ->where('status', 'void')
            ->whereIn('sewing_pickup_id', $sourceIds->all())
            ->select('sewing_pickup_id')
            ->selectRaw('SUM(COALESCE(qty_bundle, 0) * COALESCE(wage_per_pcs, 0)) as wage_value')
            ->groupBy('sewing_pickup_id')
            ->pluck('wage_value', 'sewing_pickup_id')
            ->map(fn ($value): float => (float) $value)
            ->all();
    }

    /** @param array<int, string> $legacyCodes */
    private function mappingStatus(array $legacyCodes): array
    {
        if (empty($legacyCodes)) {
            return ['status' => 'NOT_REQUIRED', 'missing_count' => 0, 'missing' => []];
        }

        $mappings = StandaloneAccountingAccountMapping::query()
            ->whereIn('legacy_account_code', $legacyCodes)
            ->where('is_active', true)
            ->get()
            ->keyBy('legacy_account_code');
        $targetCodes = $mappings->pluck('standalone_account_code')->filter()->values();
        $activeTargets = StandaloneAccountingAccount::query()
            ->whereIn('code', $targetCodes->all())
            ->where('is_active', true)
            ->pluck('code')
            ->all();

        $missing = collect($legacyCodes)
            ->filter(function (string $legacyCode) use ($mappings, $activeTargets): bool {
                $mapping = $mappings->get($legacyCode);

                return ! $mapping || ! in_array((string) $mapping->standalone_account_code, $activeTargets, true);
            })
            ->values()
            ->all();

        return [
            'status' => empty($missing) ? 'PASS' : 'MISSING',
            'missing_count' => count($missing),
            'missing' => $missing,
        ];
    }
}
