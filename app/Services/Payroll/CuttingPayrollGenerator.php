<?php

namespace App\Services\Payroll;

use App\Models\CuttingJobBundle;
use App\Models\Employee;
use App\Models\PieceRate;
use App\Models\PieceworkPayrollLine;
use App\Models\PieceworkPayrollPeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CuttingPayrollGenerator
{
    /**
     * Generate payroll borongan untuk modul Cutting
     *
     * @param  string|\DateTimeInterface  $periodStart
     * @param  string|\DateTimeInterface  $periodEnd
     * @param  int|null $createdByUserId
     * @return \App\Models\PieceworkPayrollPeriod
     */

    public static function generate(
        $periodStart,
        $periodEnd,
        ?int $createdByUserId = null,
        ?PieceworkPayrollPeriod $existingPeriod = null,
    ): PieceworkPayrollPeriod {
        $start = Carbon::parse($periodStart)->startOfDay();
        $end = Carbon::parse($periodEnd)->endOfDay();

        return DB::transaction(function () use ($start, $end, $createdByUserId, $existingPeriod) {
            // 1. Siapkan / pakai period
            if ($existingPeriod) {
                $period = $existingPeriod->fresh();
                $period->lines()->delete();
                $period->total_amount = 0;
                $period->save();
            } else {
                $period = PieceworkPayrollPeriod::create([
                    'module' => 'cutting',
                    'period_start' => $start->toDateString(),
                    'period_end' => $end->toDateString(),
                    'status' => 'draft',
                    'created_by' => $createdByUserId,
                    'total_amount' => 0,
                ]);
            }

            // 2. Agregasi dari Cutting Job + Bundles.
            //
            // Bundle yang belum QC tetap dibayar berdasarkan qty_pcs.
            // Setelah QC, gunakan qty_qc_ok agar reject tidak ikut dibayar.
            $rows = CuttingJobBundle::query()
                ->join('cutting_jobs', 'cutting_job_bundles.cutting_job_id', '=', 'cutting_jobs.id')
                ->join('items', 'cutting_job_bundles.finished_item_id', '=', 'items.id')
                // Filter tanggal; job/bundle void tidak boleh masuk payroll.
                ->whereBetween('cutting_jobs.date', [$start, $end])
                ->where('cutting_jobs.status', '!=', 'voided')
                ->where('cutting_job_bundles.status', '!=', 'voided')
                // Sebelum QC: gross qty_pcs. Sesudah QC: hanya qty_qc_ok.
                ->whereRaw("CASE
                    WHEN cutting_job_bundles.status IN ('cut', 'cutting', 'draft')
                        THEN COALESCE(cutting_job_bundles.qty_pcs, 0)
                    ELSE COALESCE(cutting_job_bundles.qty_qc_ok, 0)
                END > 0")
                ->selectRaw('
                COALESCE(cutting_job_bundles.operator_id, cutting_jobs.operator_id) as employee_id,
                COALESCE(cutting_job_bundles.item_category_id, items.item_category_id) as item_category_id,
                cutting_job_bundles.finished_item_id as item_id,
                cutting_jobs.date as work_date,
                SUM(CASE
                    WHEN cutting_job_bundles.status IN (\'cut\', \'cutting\', \'draft\')
                        THEN COALESCE(cutting_job_bundles.qty_pcs, 0)
                    ELSE COALESCE(cutting_job_bundles.qty_qc_ok, 0)
                END) as total_qty_ok
            ')
                ->groupByRaw('
                COALESCE(cutting_job_bundles.operator_id, cutting_jobs.operator_id),
                COALESCE(cutting_job_bundles.item_category_id, items.item_category_id),
                cutting_job_bundles.finished_item_id,
                cutting_jobs.date
            ')
                ->get();

            $totalAmount = 0;
            $atDate = $end->toDateString();

            foreach ($rows as $row) {
                // pakai helper yang sudah kamu buat
                $rateValue = self::resolveRateForCutting(
                    employeeId: $row->employee_id,
                    itemCategoryId: $row->item_category_id,
                    itemId: $row->item_id,
                    atDate: $atDate,
                );
                $amount = $rateValue * (float) $row->total_qty_ok;
                $totalAmount += $amount;
                PieceworkPayrollLine::create([
                    'payroll_period_id' => $period->id,
                    'employee_id' => $row->employee_id,
                    'work_date' => $row->work_date,
                    'item_category_id' => $row->item_category_id, // ⬅️ sekarang KEISI
                    'item_id' => $row->item_id,
                    'total_qty_ok' => $row->total_qty_ok,
                    'rate_per_pcs' => $rateValue,
                    'amount' => $amount,
                ]);
            }

            // 3. Update total
            $period->update([
                'total_amount' => $totalAmount,
            ]);

            return $period->fresh(['lines']);
        });
    }

    /**
     * Ambil tarif borongan per pcs untuk Cutting.
     *
     * Prioritas:
     *  1) piece_rates dengan module='cutting' + employee + item_id (paling spesifik)
     *  2) piece_rates dengan module='cutting' + employee + item_category_id (fallback)
     *  3) employee.default_piece_rate (kalau master kosong)
     *
     * @param  int         $employeeId
     * @param  int|null    $itemCategoryId
     * @param  int|null    $itemId
     * @param  string      $atDate (YYYY-MM-DD)
     * @return float
     */
    public static function resolveRateForCutting(
        int $employeeId,
        ?int $itemCategoryId,
        ?int $itemId,
        string $atDate
    ): float {
        $q = PieceRate::query()
            ->where('module', 'cutting')
            ->where('employee_id', $employeeId)
            ->whereDate('effective_from', '<=', $atDate)
            ->where(function ($qq) use ($atDate) {
                $qq->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $atDate);
            });

        // 1) paling spesifik: employee + item_id
        if ($itemId) {
            $rate = (clone $q)
                ->where('item_id', $itemId)
                ->first();

            if ($rate) {
                return (float) $rate->rate_per_pcs;
            }

        }

        // 2) fallback kategori: employee + category (item_id NULL atau 0)
        if ($itemCategoryId) {
            $rate = (clone $q)
                ->where(function ($qq) {
                    $qq->whereNull('item_id')->orWhere('item_id', 0);
                })
                ->where('item_category_id', $itemCategoryId)
                ->first();

            if ($rate) {
                return (float) $rate->rate_per_pcs;
            }

        }

        // 3) fallback global employee: (item_id NULL/0, category NULL)
        $rate = (clone $q)
            ->where(function ($qq) {
                $qq->whereNull('item_id')->orWhere('item_id', 0);
            })
            ->whereNull('item_category_id')
            ->first();

        if ($rate) {
            return (float) $rate->rate_per_pcs;
        }

        // 4) Fallback: default_piece_rate dari employees
        $employee = Employee::find($employeeId);
        if ($employee && (float) $employee->default_piece_rate > 0) {
            return (float) $employee->default_piece_rate;
        }

        return 0.0;
    }

}
