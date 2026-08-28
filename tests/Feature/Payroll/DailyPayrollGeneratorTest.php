<?php

namespace Tests\Feature\Payroll;

use App\Models\Employee;
use App\Services\Payroll\DailyPayrollGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyPayrollGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_pending_daily_lines_for_configured_active_employees(): void
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

        $period = DailyPayrollGenerator::generate('2026-08-10', '2026-08-11');

        $this->assertSame('daily', $period->module);
        $this->assertSame(2, $period->lines()->count());
        $this->assertSame('pending', $period->lines()->first()->attendance_status);
        $this->assertSame(100000.0, (float) $period->lines()->first()->rate_per_day);
        $this->assertSame(0.0, (float) $period->lines()->first()->amount);
        $this->assertSame(2, $period->lines()->where('employee_id', Employee::where('code', 'EMP-DAILY-001')->value('id'))->count());
    }
}
