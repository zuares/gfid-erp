<?php

namespace App\Services\Accounting;

use App\Models\Journal;
use App\Models\StandaloneAccountingAccount;
use App\Models\StandaloneAccountingAccountMapping;
use App\Models\StandaloneAccountingJournal;
use App\Models\StandaloneProductionAccountMapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Mirrors the legacy Cutting journals into the isolated accounting ledger.
 *
 * This service deliberately consumes the already-posted legacy journal lines
 * so the pilot can be reconciled line-for-line without changing legacy COA or
 * posting rules. It never creates inventory mutations or legacy journals.
 */
class StandaloneProductionPostingService
{
    public const SOURCE_CUTTING_JOB = 'cutting_job';

    public const SOURCE_CUTTING_WAGE = 'cutting_job_wage';

    public const SOURCE_CUTTING_WIP = 'cutting_wip';

    public const SOURCE_CUTTING_QC_SEWING_SUPPLY = 'cutting_qc_sewing_supply';

    public const SOURCE_CUTTING_QC_ADJUST = 'cutting_qc_adjust';

    public const SOURCE_CUTTING_BUNDLE_MATERIAL_RETURN = 'cutting_bundle_material_return';

    public const SOURCE_CUTTING_BUNDLE_CORRECTION_WIP_VOID = 'cutting_bundle_correction_wip_void';

    public const SOURCE_CUTTING_BUNDLE_CORRECTION_WAGE_VOID = 'cutting_bundle_correction_wage_void';

    public const SOURCE_CUTTING_QC_BUNDLE_VOID = 'cutting_qc_bundle_void';

    public const SOURCE_CUTTING_QC_BUNDLE_REPOST = 'cutting_qc_bundle_repost';

    public const SOURCE_SEWING_PICKUP = 'App\\Models\\SewingPickup';

    public const SOURCE_SEWING_PICKUP_WAGE = 'sewing_pickup_wage';

    public const SOURCE_SEWING_PICKUP_SUPPLY = 'sewing_pickup_supply';

    public const SOURCE_SEWING_PICKUP_LINE_VOID = 'sewing_pickup_line_void';

    public const SOURCE_SEWING_PICKUP_SUPPLY_VOID_LINE = 'sewing_pickup_supply_void_line';

    /** Reject sebelum Ambil Jahit yang ditemukan saat menyiapkan pickup. */
    public const SOURCE_SEWING_PICKUP_REJECT = 'sewing_pickup_pre_sewing_reject';

    /** Source lama yang tetap dibaca agar data historis dapat di-VOID/audit. */
    public const SOURCE_SEWING_PICKUP_REJECT_LEGACY = 'sewing_pickup_reject';

    /** Return only consumed sewing_supply BOM cost for a pre-sewing reject. */
    public const SOURCE_SEWING_PICKUP_REJECT_SUPPLY = 'sewing_pickup_reject_supply';

    public const SOURCE_SEWING_RETURN_OK = 'sewing_return_ok';

    public const SOURCE_SEWING_RETURN_REJECT = 'sewing_return_reject';

    public const SOURCE_SEWING_REWORK_OK = 'sewing_reject_rework_ok';

    public const SOURCE_SEWING_REJECT_CONVERT = 'sewing_reject_convert';

    public const SOURCE_FINISHING_JOB = 'finishing_job';

    public const SOURCE_FINISHING_BOM = 'finishing_bom';

    public const SOURCE_SHIPMENT_COGS = 'shipment_cogs';

    public const SOURCE_SHIPMENT_WAVE_COGS = 'shipment_wave_cogs';

    /** @var array<int, string> */
    private const STAGE_MAPPED_SOURCES = [
        self::SOURCE_CUTTING_JOB,
        self::SOURCE_CUTTING_WAGE,
        self::SOURCE_CUTTING_WIP,
        self::SOURCE_CUTTING_QC_SEWING_SUPPLY,
        self::SOURCE_CUTTING_QC_ADJUST,
        self::SOURCE_CUTTING_BUNDLE_MATERIAL_RETURN,
        self::SOURCE_CUTTING_BUNDLE_CORRECTION_WIP_VOID,
        self::SOURCE_CUTTING_BUNDLE_CORRECTION_WAGE_VOID,
        self::SOURCE_CUTTING_QC_BUNDLE_VOID,
        self::SOURCE_CUTTING_QC_BUNDLE_REPOST,
        self::SOURCE_SEWING_PICKUP,
        self::SOURCE_SEWING_PICKUP_WAGE,
        self::SOURCE_SEWING_PICKUP_SUPPLY,
        self::SOURCE_SEWING_PICKUP_REJECT,
        self::SOURCE_SEWING_PICKUP_REJECT_SUPPLY,
        'sewing_pickup_supply_followup',
        'sewing_pickup_line_void',
        self::SOURCE_SEWING_PICKUP_SUPPLY_VOID_LINE,
        self::SOURCE_SEWING_RETURN_OK,
        self::SOURCE_SEWING_RETURN_REJECT,
        self::SOURCE_SEWING_REWORK_OK,
        self::SOURCE_SEWING_REJECT_CONVERT,
        self::SOURCE_FINISHING_JOB,
        self::SOURCE_FINISHING_BOM,
        self::SOURCE_SHIPMENT_COGS,
        self::SOURCE_SHIPMENT_WAVE_COGS,
    ];

    /** @return array<int, StandaloneAccountingJournal> */
    public function mirrorCuttingJob(int $jobId): array
    {
        return $this->mirrorSources([
            self::SOURCE_CUTTING_JOB,
            self::SOURCE_CUTTING_WAGE,
        ], $jobId);
    }

    public function mirrorCuttingWip(int $jobId): ?StandaloneAccountingJournal
    {
        return $this->mirrorSources([self::SOURCE_CUTTING_WIP], $jobId)[0] ?? null;
    }

    public function mirrorCuttingQcSewingSupply(int $jobId): ?StandaloneAccountingJournal
    {
        if (! config('accounting.standalone_dual_posting_production_cutting_enabled', false)) {
            return null;
        }

        return $this->mirrorSources(
            [self::SOURCE_CUTTING_QC_SEWING_SUPPLY],
            $jobId,
            'accounting.standalone_dual_posting_production_cutting_enabled',
        )[0] ?? null;
    }

    public function mirrorCuttingQcAdjustment(int $bundleId): ?StandaloneAccountingJournal
    {
        return $this->mirrorSources([self::SOURCE_CUTTING_QC_ADJUST], $bundleId)[0] ?? null;
    }

    public function mirrorCuttingBundleMaterialReturn(int $correctionId): ?StandaloneAccountingJournal
    {
        return $this->mirrorSources([
            self::SOURCE_CUTTING_BUNDLE_MATERIAL_RETURN,
        ], $correctionId)[0] ?? null;
    }

    public function voidCuttingBundleMaterialReturn(int $correctionId): void
    {
        $this->voidSources([
            self::SOURCE_CUTTING_BUNDLE_MATERIAL_RETURN,
        ], $correctionId);
    }

    public function mirrorCuttingBundleCorrectionWipVoid(int $correctionId): ?StandaloneAccountingJournal
    {
        return $this->mirrorSources([
            self::SOURCE_CUTTING_BUNDLE_CORRECTION_WIP_VOID,
        ], $correctionId)[0] ?? null;
    }

    public function mirrorCuttingBundleCorrectionWageVoid(int $correctionId): ?StandaloneAccountingJournal
    {
        return $this->mirrorSources([
            self::SOURCE_CUTTING_BUNDLE_CORRECTION_WAGE_VOID,
        ], $correctionId)[0] ?? null;
    }

    /** @return array<int, StandaloneAccountingJournal> */
    public function mirrorCuttingQcBundleCancellation(int $cancellationId): array
    {
        return $this->mirrorSources([
            self::SOURCE_CUTTING_QC_BUNDLE_VOID,
            self::SOURCE_CUTTING_QC_BUNDLE_REPOST,
        ], $cancellationId);
    }

    public function voidCuttingQcBundleCancellation(int $cancellationId): void
    {
        $this->voidSources([
            self::SOURCE_CUTTING_QC_BUNDLE_VOID,
            self::SOURCE_CUTTING_QC_BUNDLE_REPOST,
        ], $cancellationId);
    }

    public function voidCuttingJob(int $jobId): void
    {
        $this->voidSources([
            self::SOURCE_CUTTING_JOB,
            self::SOURCE_CUTTING_WAGE,
        ], $jobId);
    }

    public function voidCuttingWip(int $jobId): void
    {
        $this->voidSources([self::SOURCE_CUTTING_WIP], $jobId);
    }

    public function voidCuttingQcSewingSupply(int $jobId): void
    {
        $this->voidSources([self::SOURCE_CUTTING_QC_SEWING_SUPPLY], $jobId);
    }

    public function assertCuttingMappings(): void
    {
        if (! config('accounting.standalone_dual_posting_production_cutting_enabled', false)) {
            return;
        }

        foreach (['1201', '1202', '1204', '2102'] as $legacyCode) {
            $this->standaloneAccountForLegacyCode($legacyCode);
        }
    }

    /** @return array<int, StandaloneAccountingJournal> */
    public function mirrorSewingPickup(int $pickupId): array
    {
        if (! config('accounting.standalone_dual_posting_production_sewing_pickup_enabled', false)) {
            return [];
        }

        return DB::transaction(function () use ($pickupId): array {
            $mirrors = $this->mirrorSources([
                self::SOURCE_SEWING_PICKUP,
                self::SOURCE_SEWING_PICKUP_SUPPLY,
            ], $pickupId, 'accounting.standalone_dual_posting_production_sewing_pickup_enabled');

            $lineIds = \App\Models\SewingPickupLine::query()
                ->where('sewing_pickup_id', $pickupId)
                ->lockForUpdate()
                ->pluck('id');

            foreach ($lineIds as $lineId) {
                $mirrors = array_merge($mirrors, $this->mirrorSources(
                    [self::SOURCE_SEWING_PICKUP_WAGE],
                    (int) $lineId,
                    'accounting.standalone_dual_posting_production_sewing_pickup_enabled',
                ));
            }

            return $mirrors;
        });
    }

    /** Mirror jurnal reversal legacy yang dibuat saat satu line di-VOID. */
    public function mirrorSewingPickupLine(int $lineId): array
    {
        return $this->mirrorSources([
            self::SOURCE_SEWING_PICKUP_LINE_VOID,
            self::SOURCE_SEWING_PICKUP_SUPPLY_VOID_LINE,
        ], $lineId, 'accounting.standalone_dual_posting_production_sewing_pickup_enabled');
    }

    public function voidSewingPickup(int $pickupId): void
    {
        $this->voidSources([
            self::SOURCE_SEWING_PICKUP,
            self::SOURCE_SEWING_PICKUP_SUPPLY,
        ], $pickupId);

        foreach (\App\Models\SewingPickupLine::query()
            ->where('sewing_pickup_id', $pickupId)
            ->pluck('id') as $lineId) {
            $this->voidSources([self::SOURCE_SEWING_PICKUP_WAGE], (int) $lineId);
        }
    }

    public function voidSewingPickupLine(int $lineId): void
    {
        // Jurnal upah asli menjadi VOID dan reversal audit dibuat di ledger baru.
        $this->voidSources([self::SOURCE_SEWING_PICKUP_WAGE], $lineId);
    }

    /**
     * Menutup jurnal material untuk pickup yang seluruh line-nya sudah VOID.
     *
     * Untuk VOID sebagian, jurnal material pickup tetap aktif karena masih
     * memuat line lain yang belum VOID; reversal line tetap menjadi penyeimbang
     * aktif. Setelah seluruh line VOID, original dan reversal material harus
     * sama-sama menjadi jejak audit (voided_at terisi).
     *
     * @param  array<int, int>  $lineIds
     */
    public function voidFullyVoidedSewingPickupMaterial(int $pickupId, array $lineIds): void
    {
        $this->voidSources([self::SOURCE_SEWING_PICKUP], $pickupId);

        foreach ($lineIds as $lineId) {
            $this->voidSources([
                self::SOURCE_SEWING_PICKUP_LINE_VOID,
                self::SOURCE_SEWING_PICKUP_SUPPLY_VOID_LINE,
            ], (int) $lineId);
        }
    }

    public function assertSewingPickupMappings(): void
    {
        if (! config('accounting.standalone_dual_posting_production_sewing_pickup_enabled', false)) {
            return;
        }

        foreach (['1201', '1202', '2102'] as $legacyCode) {
            $this->standaloneAccountForLegacyCode($legacyCode);
        }
    }

    /**
     * Validasi mapping khusus reject saat Ambil Jahit.
     *
     * Event ini memang tidak membuat jurnal legacy. Ia diposting langsung ke
     * ledger akuntansi baru agar struktur/data akuntansi lama tetap utuh.
     */
    public function assertSewingPickupRejectMappings(): void
    {
        if (! config('accounting.standalone_dual_posting_production_sewing_pickup_enabled', false)) {
            return;
        }

        foreach ([['1204', 'debit'], ['1202', 'credit']] as [$legacyCode, $lineSide]) {
            $this->standaloneProductionAccountForSourceLine(
                self::SOURCE_SEWING_PICKUP_REJECT,
                $legacyCode,
                $lineSide,
            );
        }

        foreach ([['1201', 'debit'], ['1204', 'credit']] as [$legacyCode, $lineSide]) {
            $this->standaloneProductionAccountForSourceLine(
                self::SOURCE_SEWING_PICKUP_REJECT_SUPPLY,
                $legacyCode,
                $lineSide,
            );
        }
    }

    /**
     * Post reject susulan langsung ke ledger baru.
     *
     * Sumber stabilnya adalah sewing_pickup_line_id sehingga submit ulang
     * tidak membuat jurnal duplikat. Jika jurnal sebelumnya sudah VOID, repost
     * memakai source_type *_repost_N dan tetap terlacak sebagai dokumen baru.
     */
    public function postSewingPickupReject(
        int $lineId,
        float $amount,
        string|\DateTimeInterface|null $date = null,
        ?string $reason = null,
    ): ?StandaloneAccountingJournal {
        if (! config('accounting.standalone_dual_posting_production_sewing_pickup_enabled', false)) {
            return null;
        }

        $this->assertSewingPickupRejectMappings();
        $amount = round($amount, 2);
        if ($amount <= 0.0001) {
            return null;
        }

        return DB::transaction(function () use ($lineId, $amount, $date, $reason): StandaloneAccountingJournal {
            // Lock line sebagai idempotency boundary untuk retry/concurrent click.
            DB::table('sewing_pickup_lines')
                ->where('id', $lineId)
                ->lockForUpdate()
                ->first();

            $existing = StandaloneAccountingJournal::query()
                ->where('source_id', $lineId)
                ->where(function ($query): void {
                    $query
                        ->where('source_type', self::SOURCE_SEWING_PICKUP_REJECT)
                        ->orWhere('source_type', 'like', self::SOURCE_SEWING_PICKUP_REJECT.'_repost_%');
                })
                ->whereNull('voided_at')
                ->with('lines')
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $debitAccount = $this->standaloneProductionAccountForSourceLine(
                self::SOURCE_SEWING_PICKUP_REJECT,
                '1204',
                'debit',
            );
            $creditAccount = $this->standaloneProductionAccountForSourceLine(
                self::SOURCE_SEWING_PICKUP_REJECT,
                '1202',
                'credit',
            );

            $sourceType = $this->nextSourceType(self::SOURCE_SEWING_PICKUP_REJECT, $lineId);
            $reference = $this->nextReference(self::SOURCE_SEWING_PICKUP_REJECT, $lineId, $sourceType);
            $postingDate = $date instanceof \DateTimeInterface
                ? $date->format('Y-m-d')
                : ($date ?: now()->toDateString());

            $journal = StandaloneAccountingJournal::create([
                'date' => $postingDate,
                'reference' => $reference,
                'source_type' => $sourceType,
                'source_id' => $lineId,
                'description' => trim('Reject susulan Ambil Jahit line #'.$lineId
                    .($reason ? ' — '.$reason : '')),
                'posted_at' => now(),
            ]);

            $journal->lines()->createMany([
                [
                    'account_id' => (int) $debitAccount->id,
                    'debit' => $amount,
                    'credit' => 0,
                ],
                [
                    'account_id' => (int) $creditAccount->id,
                    'debit' => 0,
                    'credit' => $amount,
                ],
            ]);

            return $journal->load('lines');
        });
    }

    public function voidSewingPickupReject(int $lineId): void
    {
        // Mirror/jurnal baru yang sudah ada tetap dapat di-VOID walau flag
        // kemudian dimatikan, sama seperti lifecycle production lainnya.
        $this->voidSources([
            self::SOURCE_SEWING_PICKUP_REJECT,
            self::SOURCE_SEWING_PICKUP_REJECT_LEGACY,
        ], $lineId);
    }

    /**
     * Post the accounting-new return of sewing_supply cost for a reject line.
     *
     * The physical item is already in REJ-CUT after the reject movement, so
     * the supply component is removed from reject inventory:
     *   Dr 1-150 RM / Cr 1-153 Reject inventory.
     * This intentionally does not touch the main fabric/WIP movement.
     */
    public function postSewingPickupRejectSupply(
        int $lineId,
        string|\DateTimeInterface|null $date = null,
        ?string $reason = null,
    ): ?StandaloneAccountingJournal {
        if (! config('accounting.standalone_dual_posting_production_sewing_pickup_enabled', false)) {
            return null;
        }

        $this->assertSewingPickupRejectMappings();

        return DB::transaction(function () use ($lineId, $date, $reason): ?StandaloneAccountingJournal {
            DB::table('sewing_pickup_lines')
                ->where('id', $lineId)
                ->lockForUpdate()
                ->first();

            $existing = StandaloneAccountingJournal::query()
                ->where('source_id', $lineId)
                ->where(function ($query): void {
                    $query
                        ->where('source_type', self::SOURCE_SEWING_PICKUP_REJECT_SUPPLY)
                        ->orWhere('source_type', 'like', self::SOURCE_SEWING_PICKUP_REJECT_SUPPLY.'_repost_%');
                })
                ->whereNull('voided_at')
                ->with('lines')
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $amount = round((float) DB::table('inventory_mutations')
                ->where('source_type', self::SOURCE_SEWING_PICKUP_REJECT_SUPPLY)
                ->where('source_id', $lineId)
                ->where('qty_change', '>', 0)
                ->sum(DB::raw('COALESCE(total_cost, 0)')), 2);
            if ($amount <= 0.0001) {
                return null;
            }

            $debitAccount = $this->standaloneProductionAccountForSourceLine(
                self::SOURCE_SEWING_PICKUP_REJECT_SUPPLY,
                '1201',
                'debit',
            );
            $creditAccount = $this->standaloneProductionAccountForSourceLine(
                self::SOURCE_SEWING_PICKUP_REJECT_SUPPLY,
                '1204',
                'credit',
            );

            $sourceType = $this->nextSourceType(self::SOURCE_SEWING_PICKUP_REJECT_SUPPLY, $lineId);
            $reference = $this->nextReference(self::SOURCE_SEWING_PICKUP_REJECT_SUPPLY, $lineId, $sourceType);
            $postingDate = $date instanceof \DateTimeInterface
                ? $date->format('Y-m-d')
                : ($date ?: now()->toDateString());

            $journal = StandaloneAccountingJournal::create([
                'date' => $postingDate,
                'reference' => $reference,
                'source_type' => $sourceType,
                'source_id' => $lineId,
                'description' => trim('Return BOM sewing_supply untuk Reject Susulan line #'.$lineId
                    .($reason ? ' — '.$reason : '')),
                'posted_at' => now(),
            ]);

            $journal->lines()->createMany([
                ['account_id' => (int) $debitAccount->id, 'debit' => $amount, 'credit' => 0],
                ['account_id' => (int) $creditAccount->id, 'debit' => 0, 'credit' => $amount],
            ]);

            return $journal->load('lines');
        });
    }

    public function voidSewingPickupRejectSupply(int $lineId): void
    {
        // VOID membuat reversal audit yang juga tetap dikecualikan dari saldo
        // aktif, konsisten dengan lifecycle journal produksi lainnya.
        $this->voidSources([self::SOURCE_SEWING_PICKUP_REJECT_SUPPLY], $lineId);
    }

    /** @return array<int, StandaloneAccountingJournal> */
    public function mirrorSewingReturn(int $returnId): array
    {
        if (! config('accounting.standalone_dual_posting_production_sewing_return_enabled', false)) {
            return [];
        }

        return DB::transaction(fn (): array => $this->mirrorSources([
            self::SOURCE_SEWING_RETURN_OK,
            self::SOURCE_SEWING_RETURN_REJECT,
            self::SOURCE_SEWING_REWORK_OK,
        ], $returnId, 'accounting.standalone_dual_posting_production_sewing_return_enabled'));
    }

    public function voidSewingReturn(int $returnId): void
    {
        // Mirror yang sudah ada tetap dapat di-VOID walau flag kemudian OFF.
        $this->voidSources([
            self::SOURCE_SEWING_RETURN_OK,
            self::SOURCE_SEWING_RETURN_REJECT,
            self::SOURCE_SEWING_REWORK_OK,
        ], $returnId);
    }

    public function assertSewingReturnMappings(): void
    {
        if (! config('accounting.standalone_dual_posting_production_sewing_return_enabled', false)) {
            return;
        }

        foreach (['1202', '1203', '1204', '2102'] as $legacyCode) {
            $this->standaloneAccountForLegacyCode($legacyCode);
        }
    }

    /**
     * Mirror perpindahan reject dari REJ-SEW ke WH-RTS.
     *
     * Ini bukan penghapusan reject atau spoilage. Sumber dan tujuan tetap
     * berada pada akun 1-153, sehingga saldo bersih akun tidak berubah;
     * jurnal dibuat untuk jejak dokumen dan rekonsiliasi lokasi stok.
     */
    public function mirrorSewingRejectConversion(int $conversionId): ?StandaloneAccountingJournal
    {
        if (! config('accounting.standalone_dual_posting_production_sewing_return_enabled', false)) {
            return null;
        }

        return DB::transaction(function () use ($conversionId): StandaloneAccountingJournal {
            $conversion = DB::table('sewing_reject_conversions')
                ->where('id', $conversionId)
                ->lockForUpdate()
                ->first();

            if (! $conversion) {
                throw ValidationException::withMessages([
                    'standalone_accounting' => "Sewing reject conversion #{$conversionId} tidak ditemukan.",
                ]);
            }

            if ((string) $conversion->status !== 'posted') {
                throw ValidationException::withMessages([
                    'standalone_accounting' => "Sewing reject conversion #{$conversionId} belum berstatus posted.",
                ]);
            }

            $baseSourceType = self::SOURCE_SEWING_REJECT_CONVERT;
            $existing = StandaloneAccountingJournal::query()
                ->where('source_id', $conversionId)
                ->where(function ($query) use ($baseSourceType): void {
                    $query
                        ->where('source_type', $baseSourceType)
                        ->orWhere('source_type', 'like', $baseSourceType.'_repost_%');
                })
                ->whereNull('voided_at')
                ->with('lines')
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $movements = DB::table('inventory_mutations as im')
                ->leftJoin('warehouses as w', 'w.id', '=', 'im.warehouse_id')
                ->where('im.source_type', $baseSourceType)
                ->where('im.source_id', $conversionId)
                ->select('im.qty_change', 'im.total_cost', 'im.warehouse_id', 'w.code as warehouse_code')
                ->lockForUpdate()
                ->get();

            $out = $movements->filter(fn ($row): bool => (float) $row->qty_change < 0);
            $in = $movements->filter(fn ($row): bool => (float) $row->qty_change > 0);
            $outValue = round((float) $out->sum(fn ($row): float => abs((float) ($row->total_cost ?? 0))), 2);
            $inValue = round((float) $in->sum(fn ($row): float => abs((float) ($row->total_cost ?? 0))), 2);

            if ($out->isEmpty() || $in->isEmpty() || $outValue <= 0 || abs($outValue - $inValue) > 0.01) {
                throw ValidationException::withMessages([
                    'standalone_accounting' => sprintf(
                        'Mutasi sewing reject conversion #%d tidak lengkap atau tidak balance: OUT Rp%s, IN Rp%s.',
                        $conversionId,
                        number_format($outValue, 2, ',', '.'),
                        number_format($inValue, 2, ',', '.'),
                    ),
                ]);
            }

            $rejectAccount = $this->standaloneAccountForCode(
                '1-153',
                "Sewing reject conversion #{$conversionId}",
            );
            $sourceType = $this->nextSourceType($baseSourceType, $conversionId);
            $journal = StandaloneAccountingJournal::create([
                'date' => $conversion->date,
                'reference' => $this->nextReference($baseSourceType, $conversionId, $sourceType),
                'source_type' => $sourceType,
                'source_id' => $conversionId,
                'description' => "Mirror: Reject conversion {$conversion->code} — REJ-SEW → WH-RTS",
                'posted_at' => now(),
            ]);

            $journal->lines()->createMany([
                [
                    'account_id' => (int) $rejectAccount->id,
                    'debit' => $outValue,
                    'credit' => 0,
                ],
                [
                    'account_id' => (int) $rejectAccount->id,
                    'debit' => 0,
                    'credit' => $outValue,
                ],
            ]);

            return $journal->load('lines');
        });
    }

    public function voidSewingRejectConversion(int $conversionId): void
    {
        // Mirror yang sudah ada tetap dapat di-VOID walau flag kemudian OFF.
        $this->voidSources([self::SOURCE_SEWING_REJECT_CONVERT], $conversionId);
    }

    /** @return array<int, StandaloneAccountingJournal> */
    public function mirrorFinishingJob(int $jobId): array
    {
        if (! config('accounting.standalone_dual_posting_production_finishing_enabled', false)) {
            return [];
        }

        return DB::transaction(fn (): array => $this->mirrorSources([
            self::SOURCE_FINISHING_JOB,
            self::SOURCE_FINISHING_BOM,
        ], $jobId, 'accounting.standalone_dual_posting_production_finishing_enabled'));
    }

    public function voidFinishingJob(int $jobId): void
    {
        // Mirror lama tetap dapat di-VOID walau flag kemudian OFF.
        $this->voidSources([
            self::SOURCE_FINISHING_JOB,
            self::SOURCE_FINISHING_BOM,
        ], $jobId);
    }

    public function assertFinishingMappings(): void
    {
        if (! config('accounting.standalone_dual_posting_production_finishing_enabled', false)) {
            return;
        }

        foreach (['1201', '1202', '1203', '1204'] as $legacyCode) {
            $this->standaloneAccountForLegacyCode($legacyCode);
        }
    }

    /** @return array<int, StandaloneAccountingJournal> */
    public function mirrorShipment(int $shipmentId): array
    {
        if (! config('accounting.standalone_dual_posting_production_shipment_enabled', false)) {
            return [];
        }

        return DB::transaction(function () use ($shipmentId): array {
            $mirrors = $this->mirrorSources([
                self::SOURCE_SHIPMENT_COGS,
            ], $shipmentId, 'accounting.standalone_dual_posting_production_shipment_enabled');

            $waveIds = \App\Models\ShipmentWave::query()
                ->where('shipment_id', $shipmentId)
                ->pluck('id');

            foreach ($waveIds as $waveId) {
                $mirrors = array_merge($mirrors, $this->mirrorSources(
                    [self::SOURCE_SHIPMENT_WAVE_COGS],
                    (int) $waveId,
                    'accounting.standalone_dual_posting_production_shipment_enabled',
                ));
            }

            return $mirrors;
        });
    }

    public function mirrorShipmentWave(int $waveId): ?StandaloneAccountingJournal
    {
        if (! config('accounting.standalone_dual_posting_production_shipment_enabled', false)) {
            return null;
        }

        return $this->mirrorSources([
            self::SOURCE_SHIPMENT_WAVE_COGS,
        ], $waveId, 'accounting.standalone_dual_posting_production_shipment_enabled')[0] ?? null;
    }

    public function voidShipment(int $shipmentId): void
    {
        // Existing mirrors remain voidable even if the feature flag is later
        // turned off. Reversals are audit-only and remain excluded from active
        // balances by voided_at.
        $this->voidSources([self::SOURCE_SHIPMENT_COGS], $shipmentId);

        \App\Models\ShipmentWave::query()
            ->where('shipment_id', $shipmentId)
            ->pluck('id')
            ->each(fn ($waveId) => $this->voidSources(
                [self::SOURCE_SHIPMENT_WAVE_COGS],
                (int) $waveId,
            ));
    }

    public function assertShipmentMappings(): void
    {
        if (! config('accounting.standalone_dual_posting_production_shipment_enabled', false)) {
            return;
        }

        foreach (['1203', '5101'] as $legacyCode) {
            $this->standaloneAccountForLegacyCode($legacyCode);
        }
    }

    /**
     * @param  array<int, string>  $sourceTypes
     * @return array<int, StandaloneAccountingJournal>
     */
    private function mirrorSources(array $sourceTypes, int $sourceId, string $featureFlag = 'accounting.standalone_dual_posting_production_cutting_enabled'): array
    {
        if (! config($featureFlag, false)) {
            return [];
        }

        return DB::transaction(function () use ($sourceTypes, $sourceId): array {
            $legacyJournals = Journal::query()
                ->whereIn('source_type', $sourceTypes)
                ->where('source_id', $sourceId)
                ->whereNull('voided_at')
                ->with(['lines.account'])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $mirrors = [];
            foreach ($legacyJournals as $legacyJournal) {
                $mirrors[] = $this->mirrorLegacyJournal($legacyJournal);
            }

            return $mirrors;
        });
    }

    private function mirrorLegacyJournal(Journal $legacyJournal): StandaloneAccountingJournal
    {
        $baseSourceType = (string) $legacyJournal->source_type;
        $sourceId = (int) $legacyJournal->source_id;

        $existing = StandaloneAccountingJournal::query()
            ->where('source_id', $sourceId)
            ->where(function ($query) use ($baseSourceType): void {
                $query
                    ->where('source_type', $baseSourceType)
                    ->orWhere('source_type', 'like', $baseSourceType.'_repost_%');
            })
            ->whereNull('voided_at')
            ->with('lines')
            ->lockForUpdate()
            ->first();

        if ($existing) {
            return $existing;
        }

        $lines = $this->mapLegacyLines($legacyJournal);
        // Dua akun legacy yang berbeda dapat dipetakan ke satu akun baru
        // (contoh transfer WIP memakai 1202 di debit dan kredit). Setelah
        // agregasi, satu baris dengan debit dan kredit tetap merupakan jurnal
        // balance yang valid dan harus diterima.
        if (count($lines) < 1) {
            throw ValidationException::withMessages([
                'standalone_accounting' => "Mirror {$baseSourceType} #{$sourceId} tidak memiliki baris jurnal bernilai.",
            ]);
        }

        $totalDebit = round((float) collect($lines)->sum('debit'), 2);
        $totalCredit = round((float) collect($lines)->sum('credit'), 2);
        if ($totalDebit <= 0 || abs($totalDebit - $totalCredit) > 0.01) {
            throw ValidationException::withMessages([
                'standalone_accounting' => "Mirror {$baseSourceType} #{$sourceId} tidak balance.",
            ]);
        }

        $sourceType = $this->nextSourceType($baseSourceType, $sourceId);
        $journal = StandaloneAccountingJournal::create([
            'date' => $legacyJournal->date,
            'reference' => $this->nextReference($baseSourceType, $sourceId, $sourceType),
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'description' => 'Mirror: '.($legacyJournal->description ?: $baseSourceType),
            'posted_at' => now(),
        ]);

        $journal->lines()->createMany($lines);

        return $journal->load('lines');
    }

    /**
     * @param  array<int, string>  $sourceTypes
     */
    private function voidSources(array $sourceTypes, int $sourceId): void
    {
        DB::transaction(function () use ($sourceTypes, $sourceId): void {
            foreach ($sourceTypes as $sourceType) {
                $mirrors = StandaloneAccountingJournal::query()
                    ->where('source_id', $sourceId)
                    ->where(function ($query) use ($sourceType): void {
                        $query
                            ->where('source_type', $sourceType)
                            ->orWhere('source_type', 'like', $sourceType.'_repost_%');
                    })
                    ->whereNull('voided_at')
                    ->with('lines')
                    ->lockForUpdate()
                    ->get();

                foreach ($mirrors as $journal) {
                    $journal->forceFill(['voided_at' => now()])->save();

                    $reversal = StandaloneAccountingJournal::create([
                        'date' => $journal->date,
                        'reference' => $this->nextReversalReference((string) $journal->reference),
                        'source_type' => $sourceType.'_reversal_'.$journal->id,
                        'source_id' => $sourceId,
                        'description' => 'Reversal: '.($journal->description ?: $sourceType),
                        'posted_at' => now(),
                        // Audit reversal only; VOID rows never affect active balances.
                        'voided_at' => now(),
                    ]);

                    $reversal->lines()->createMany(
                        $journal->lines->map(fn ($line): array => [
                            'account_id' => (int) $line->account_id,
                            'debit' => (float) $line->credit,
                            'credit' => (float) $line->debit,
                        ])->all(),
                    );
                }
            }
        });
    }

    /** @return array<int, array{account_id:int,debit:float,credit:float}> */
    private function mapLegacyLines(Journal $legacyJournal): array
    {
        $merged = [];
        $sewingReturnDestinationCodes = $this->sewingReturnDestinationCodes($legacyJournal);

        foreach ($legacyJournal->lines as $line) {
            $legacyCode = (string) ($line->account?->code ?? '');
            if ($legacyCode === '') {
                throw ValidationException::withMessages([
                    'standalone_accounting' => "Akun legacy pada jurnal {$legacyJournal->source_type} #{$legacyJournal->source_id} tidak ditemukan.",
                ]);
            }

            $debit = round((float) $line->debit, 2);
            $credit = round((float) $line->credit, 2);
            if ($debit <= 0 && $credit <= 0) {
                continue;
            }
            if ($debit > 0 && $credit > 0) {
                throw ValidationException::withMessages([
                    'standalone_accounting' => "Baris akun legacy {$legacyCode} pada {$legacyJournal->source_type} #{$legacyJournal->source_id} tidak valid.",
                ]);
            }

            $account = $this->standaloneAccountForProductionLine(
                legacyJournal: $legacyJournal,
                legacyCode: $legacyCode,
                debit: $debit,
                credit: $credit,
                sewingReturnDestinationCodes: $sewingReturnDestinationCodes,
            );

            $key = (string) $account->id;
            $merged[$key] ??= [
                'account_id' => (int) $account->id,
                'debit' => 0.0,
                'credit' => 0.0,
            ];
            $merged[$key]['debit'] = round($merged[$key]['debit'] + $debit, 2);
            $merged[$key]['credit'] = round($merged[$key]['credit'] + $credit, 2);
        }

        return array_values(array_filter(
            $merged,
            fn (array $line): bool => $line['debit'] > 0.0001 || $line['credit'] > 0.0001,
        ));
    }

    /**
     * Resolve akun detail berdasarkan sumber, sisi jurnal, dan tahap produksi.
     * Jurnal legacy tetap dibaca apa adanya; hanya akun mirror baru yang
     * diarahkan ke WIP Cutting/Sewing/Finishing.
     *
     * @param  array<int, string>  $sewingReturnDestinationCodes
     */
    private function standaloneAccountForProductionLine(
        Journal $legacyJournal,
        string $legacyCode,
        float $debit,
        float $credit,
        array $sewingReturnDestinationCodes,
    ): StandaloneAccountingAccount {
        $lineSide = $debit > 0 ? 'debit' : 'credit';

        // Mapping legacy tetap diwajibkan sebagai guard kompatibilitas dan
        // audit. Target posting baru ditentukan oleh mapping stage di bawah,
        // bukan oleh akun parent 1-151 dari mapping lama.
        $this->standaloneAccountForLegacyCode($legacyCode);

        if ($legacyJournal->source_type === self::SOURCE_SEWING_RETURN_OK
            && $legacyCode === '1203'
            && $lineSide === 'debit'
            && $sewingReturnDestinationCodes === ['WIP-FIN']) {
            return $this->standaloneAccountForCode(
                '1-151-03',
                "Sewing Return OK #{$legacyJournal->source_id} ke WIP-FIN",
            );
        }

        $mapping = StandaloneProductionAccountMapping::query()
            ->where('source_type', (string) $legacyJournal->source_type)
            ->where('legacy_account_code', $legacyCode)
            ->where('line_side', $lineSide)
            ->where('is_active', true)
            ->first();

        if ($mapping) {
            return $this->standaloneAccountForCode(
                (string) $mapping->standalone_account_code,
                "Mapping {$legacyJournal->source_type} {$lineSide} {$legacyCode}",
            );
        }

        if (in_array((string) $legacyJournal->source_type, self::STAGE_MAPPED_SOURCES, true)) {
            throw ValidationException::withMessages([
                'standalone_accounting' => "Mapping WIP tahap {$legacyJournal->source_type} {$lineSide} untuk akun legacy {$legacyCode} belum tersedia.",
            ]);
        }

        return $this->standaloneAccountForLegacyCode($legacyCode);
    }

    /** @return array<int, string> */
    private function sewingReturnDestinationCodes(Journal $legacyJournal): array
    {
        if ($legacyJournal->source_type !== self::SOURCE_SEWING_RETURN_OK) {
            return [];
        }

        $destinationCodes = DB::table('inventory_mutations as im')
            ->join('warehouses as w', 'w.id', '=', 'im.warehouse_id')
            ->whereIn('im.source_type', ['sewing_qc_in', self::SOURCE_SEWING_RETURN_OK])
            ->where('im.source_id', (int) $legacyJournal->source_id)
            ->where('im.qty_change', '>', 0)
            ->pluck('w.code')
            ->filter()
            ->unique()
            ->values();

        if ($destinationCodes->contains('WIP-FIN') && $destinationCodes->count() > 1) {
            throw ValidationException::withMessages([
                'standalone_accounting' => sprintf(
                    'Sewing Return #%d memiliki tujuan stok campuran (%s); mapping akuntansi baru ambigu. Pisahkan posting per tujuan sebelum mirror.',
                    (int) $legacyJournal->source_id,
                    $destinationCodes->implode(', '),
                ),
            ]);
        }

        return $destinationCodes->all();
    }

    private function standaloneAccountForCode(string $standaloneCode, string $context): StandaloneAccountingAccount
    {
        $account = StandaloneAccountingAccount::query()
            ->where('code', $standaloneCode)
            ->where('is_active', true)
            ->first();

        if (! $account) {
            throw ValidationException::withMessages([
                'standalone_accounting' => "Akun baru {$standaloneCode} untuk {$context} belum aktif.",
            ]);
        }

        return $account;
    }

    private function standaloneAccountForLegacyCode(string $legacyCode): StandaloneAccountingAccount
    {
        $mapping = StandaloneAccountingAccountMapping::query()
            ->where('legacy_account_code', $legacyCode)
            ->where('is_active', true)
            ->first();

        if (! $mapping) {
            throw ValidationException::withMessages([
                'standalone_accounting' => "Mapping akun legacy {$legacyCode} ke akuntansi baru belum tersedia.",
            ]);
        }

        return $this->standaloneAccountForCode(
            (string) $mapping->standalone_account_code,
            "akun legacy {$legacyCode}",
        );
    }

    private function standaloneProductionAccountForSourceLine(
        string $sourceType,
        string $legacyAccountCode,
        string $lineSide,
    ): StandaloneAccountingAccount {
        $mapping = StandaloneProductionAccountMapping::query()
            ->where('source_type', $sourceType)
            ->where('legacy_account_code', $legacyAccountCode)
            ->where('line_side', $lineSide)
            ->where('is_active', true)
            ->first();

        if (! $mapping) {
            throw ValidationException::withMessages([
                'standalone_accounting' => "Mapping {$sourceType} {$lineSide} akun legacy {$legacyAccountCode} belum tersedia.",
            ]);
        }

        return $this->standaloneAccountForCode(
            (string) $mapping->standalone_account_code,
            "mapping {$sourceType} {$lineSide} {$legacyAccountCode}",
        );
    }

    private function nextSourceType(string $baseSourceType, int $sourceId): string
    {
        if (! StandaloneAccountingJournal::query()
            ->where('source_type', $baseSourceType)
            ->where('source_id', $sourceId)
            ->exists()) {
            return $baseSourceType;
        }

        $attempt = 1;
        do {
            $sourceType = $baseSourceType.'_repost_'.$attempt++;
        } while (StandaloneAccountingJournal::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->exists());

        return $sourceType;
    }

    private function nextReference(string $baseSourceType, int $sourceId, string $sourceType): string
    {
        $base = 'SA-'.$baseSourceType.'-'.$sourceId;
        if ($sourceType !== $baseSourceType) {
            $base .= '-'.str_replace($baseSourceType.'_repost_', 'R', $sourceType);
        }

        $reference = $base;
        $attempt = 1;
        while (StandaloneAccountingJournal::query()->where('reference', $reference)->exists()) {
            $reference = $base.'-'.$attempt++;
        }

        return $reference;
    }

    private function nextReversalReference(string $originalReference): string
    {
        $base = $originalReference.'-REV';
        $reference = $base;
        $attempt = 1;
        while (StandaloneAccountingJournal::query()->where('reference', $reference)->exists()) {
            $reference = $base.'-'.$attempt++;
        }

        return $reference;
    }
}
