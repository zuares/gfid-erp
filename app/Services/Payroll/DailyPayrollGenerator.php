<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\PieceworkPayrollLine;
use App\Models\PieceworkPayrollPeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DailyPayrollGenerator
{
    /**
     * Siapkan draft payroll harian per operator dan per tanggal.
     * Kehadiran sengaja dimulai sebagai pending agar payroll tidak otomatis
     * menghitung semua operator sebagai hadir.
     */
    public static function generate(
        $periodStart,
        $periodEnd,
        ?int $createdByUserId = null,
        ?PieceworkPayrollPeriod $existingPeriod = null,
    ): PieceworkPayrollPeriod {
        $start = Carbon::parse($periodStart)->startOfDay();
        $end = Carbon::parse($periodEnd)->startOfDay();

        return DB::transaction(function () use ($start, $end, $createdByUserId, $existingPeriod) {
            $employees = Employee::query()
                ->where('active', true)
                ->where('daily_rate', '>', 0)
                ->orderBy('id')
                ->get(['id', 'daily_rate']);

            if ($employees->isEmpty()) {
                throw new \RuntimeException(
                    'Belum ada karyawan aktif dengan tarif harian. Isi Tarif Harian di Master Karyawan terlebih dahulu.'
                );
            }

            if ($existingPeriod) {
                $period = $existingPeriod->fresh();
                $period->lines()->delete();
                $period->total_amount = 0;
                $period->save();
            } else {
                $period = PieceworkPayrollPeriod::create([
                    'module' => 'daily',
                    'period_start' => $start->toDateString(),
                    'period_end' => $end->toDateString(),
                    'status' => 'draft',
                    'created_by' => $createdByUserId,
                    'total_amount' => 0,
                ]);
            }

            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                foreach ($employees as $employee) {
                    $rate = round((float) $employee->daily_rate, 2);

                    PieceworkPayrollLine::create([
                        'payroll_period_id' => $period->id,
                        'employee_id' => $employee->id,
                        'work_date' => $date->toDateString(),
                        'attendance_status' => 'pending',
                        'attendance_factor' => 0,
                        'item_category_id' => null,
                        'item_id' => null,
                        'total_qty_ok' => 0,
                        'rate_per_pcs' => $rate,
                        'rate_per_day' => $rate,
                        'amount' => 0,
                    ]);
                }
            }

            return $period->fresh(['lines']);
        });
    }
}
