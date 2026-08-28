<?php

namespace Tests\Feature\Payroll;

use App\Models\Employee;
use App\Services\Payroll\DailyPayrollGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyPayrollGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_present_daily_lines_for_configured_active_employees(): void
    {
        Employee::create([
            'code' => 'EMP-DAILY-001',
            'name' => 'Operator Harian',
            'role' => 'operating',
            'payment_type' => 'variable',
            'daily_rate' => 100000,
            'active' => true,
        ]);

        Employee::create([
            'code' => 'EMP-DAILY-002',
            'name' => 'Operator Tanpa Tarif',
            'role' => 'operating',
            'payment_type' => 'variable',
            'daily_rate' => 0,
            'active' => true,
        ]);

        $period = DailyPayrollGenerator::generate('2026-08-10', '2026-08-16');

        $this->assertSame('daily', $period->module);
        $this->assertSame(7, $period->lines()->count());
        $this->assertSame('hadir', $period->lines()->first()->attendance_status);
        $this->assertSame(1.0, (float) $period->lines()->first()->attendance_factor);
        $this->assertSame(100000.0, (float) $period->lines()->first()->rate_per_day);
        $this->assertSame(100000.0, (float) $period->lines()->first()->amount);
        $this->assertSame(600000.0, (float) $period->total_amount);
        $this->assertSame(7, $period->lines()->where('employee_id', Employee::where('code', 'EMP-DAILY-001')->value('id'))->count());
        $this->assertSame(1, $period->lines()->whereDate('work_date', '2026-08-16')->count());
        $this->assertSame('libur', $period->lines()->whereDate('work_date', '2026-08-16')->first()->attendance_status);
        $this->assertSame(0.0, (float) $period->lines()->whereDate('work_date', '2026-08-16')->first()->amount);
    }

    public function test_regenerate_resets_sunday_to_holiday_and_excludes_it_from_payment(): void
    {
        Employee::create([
            'code' => 'EMP-DAILY-REGENERATE',
            'name' => 'Operator Regenerate',
            'role' => 'operating',
            'payment_type' => 'variable',
            'daily_rate' => 100000,
            'active' => true,
        ]);

        $period = DailyPayrollGenerator::generate('2026-08-10', '2026-08-16');
        $sunday = $period->lines()->whereDate('work_date', '2026-08-16')->first();
        $sunday->forceFill([
            'attendance_status' => 'hadir',
            'attendance_factor' => 1,
            'total_qty_ok' => 1,
            'amount' => 100000,
        ])->save();
        $period->forceFill(['total_amount' => 700000])->save();

        $regenerated = DailyPayrollGenerator::generate(
            '2026-08-10',
            '2026-08-16',
            existingPeriod: $period->fresh(),
        );
        $sunday = $regenerated->lines()->whereDate('work_date', '2026-08-16')->first();

        $this->assertSame('libur', $sunday->attendance_status);
        $this->assertSame(0.0, (float) $sunday->attendance_factor);
        $this->assertSame(0.0, (float) $sunday->amount);
        $this->assertSame(600000.0, (float) $regenerated->total_amount);
    }
}
