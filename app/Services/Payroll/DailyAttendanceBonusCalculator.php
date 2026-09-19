<?php

namespace App\Services\Payroll;

use App\Models\PieceworkPayrollPeriod;
use Illuminate\Support\Collection;

class DailyAttendanceBonusCalculator
{
    public const ELIGIBLE_DAYS = 6;
    public const RATE_PERCENT = 10.0;

    /**
     * Menghitung bonus kehadiran per karyawan untuk payroll harian.
     * Bonus hanya aktif jika tepat 6 baris hadir dalam periode tersebut.
     */
    public static function forPeriod(PieceworkPayrollPeriod $period): Collection
    {
        if ($period->module !== 'daily') {
            return collect();
        }

        return $period->lines()
            ->get(['employee_id', 'attendance_status', 'amount'])
            ->groupBy('employee_id')
            ->map(function (Collection $lines, $employeeId): ?array {
                $presentCount = $lines->where('attendance_status', 'hadir')->count();
                if ($presentCount !== self::ELIGIBLE_DAYS) {
                    return null;
                }

                $baseAmount = round((float) $lines->sum('amount'), 2);
                $bonusAmount = round($baseAmount * (self::RATE_PERCENT / 100), 2);

                return [
                    'employee_id' => (int) $employeeId,
                    'present_count' => $presentCount,
                    'base_amount' => $baseAmount,
                    'rate_percent' => self::RATE_PERCENT,
                    'bonus_amount' => $bonusAmount,
                ];
            })
            ->filter()
            ->values();
    }
}
