<?php

namespace Tests\Feature\Payroll;

use App\Models\PieceworkPayrollPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PieceworkPayrollOverviewFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_overview_defaults_to_draft_periods(): void
    {
        $user = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'PAYROLL-OVERVIEW-FILTER',
        ]);
        $draft = $this->makePeriod('2026-10-01', 'draft');
        $final = $this->makePeriod('2026-10-08', 'final');

        $response = $this->actingAs($user)->get(route('payroll.piecework.overview'));

        $response->assertOk()
            ->assertSee('ID #' . $draft->id)
            ->assertDontSee('ID #' . $final->id)
            ->assertSee('value="draft" selected', false);
    }

    public function test_overview_can_filter_final_and_paid_periods(): void
    {
        $user = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'PAYROLL-OVERVIEW-FILTER-2',
        ]);
        $draft = $this->makePeriod('2026-10-01', 'draft');
        $final = $this->makePeriod('2026-10-08', 'final');
        $paid = $this->makePeriod('2026-10-15', 'final', true);

        $finalResponse = $this->actingAs($user)->get(route('payroll.piecework.overview', [
            'status' => 'final',
        ]));
        $finalResponse->assertOk()
            ->assertDontSee('ID #' . $draft->id)
            ->assertSee('ID #' . $final->id)
            ->assertSee('ID #' . $paid->id);

        $paidResponse = $this->actingAs($user)->get(route('payroll.piecework.overview', [
            'status' => 'paid',
        ]));
        $paidResponse->assertOk()
            ->assertDontSee('ID #' . $draft->id)
            ->assertDontSee('ID #' . $final->id)
            ->assertSee('ID #' . $paid->id);
    }

    private function makePeriod(string $start, string $status, bool $paid = false): PieceworkPayrollPeriod
    {
        return PieceworkPayrollPeriod::create([
            'module' => 'cutting',
            'period_start' => $start,
            'period_end' => $start,
            'status' => $status,
            'total_amount' => 0,
            'paid_at' => $paid ? '2026-10-16 10:00:00' : null,
        ]);
    }
}
