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
     * Default kehadiran adalah hadir agar draft langsung menampilkan estimasi
     * payroll. Hari Minggu otomatis menjadi libur dan tidak dibayar. Status
     * hari kerja lainnya tetap bisa disesuaikan sebelum finalisasi.
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

            $totalAmount = 0.0;

            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                foreach ($employees as $employee) {
                    $rate = round((float) $employee->daily_rate, 2);
                    // Regenerate juga harus mengembalikan setiap hari Minggu ke Libur.
                    $isSunday = $date->isSunday();
                    $factor = $isSunday ? 0.0 : 1.0;
                    $amount = round($rate * $factor, 2);
                    $totalAmount += $amount;

                    PieceworkPayrollLine::create([
                        'payroll_period_id' => $period->id,
                        'employee_id' => $employee->id,
                        'work_date' => $date->toDateString(),
                        'attendance_status' => $isSunday ? 'libur' : 'hadir',
                        'attendance_factor' => $factor,
                        'item_category_id' => null,
                        'item_id' => null,
                        'total_qty_ok' => $factor,
                        'rate_per_pcs' => $rate,
                        'rate_per_day' => $rate,
                        'amount' => $amount,
                    ]);
                }
            }

            $period->update(['total_amount' => round($totalAmount, 2)]);

            return $period->fresh(['lines']);
        });
    }
}
