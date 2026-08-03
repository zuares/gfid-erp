<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceProfitReportUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_profit_report_and_sidebar_link_is_present(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'OWNER-PROFIT-' . uniqid(),
        ]);

        $response = $this->actingAs($owner)
            ->get(route('marketplace.reports.profit'));

        $response->assertOk()
            ->assertSee('Laporan Keuntungan Marketplace')
            ->assertSee('Payout aktual')
            ->assertSee(route('marketplace.reports.profit'), false)
            ->assertSee(route('marketplace.reports.profit.export'), false);
    }

    public function test_non_owner_cannot_open_profit_report(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADMIN-PROFIT-' . uniqid(),
        ]);

        $this->actingAs($admin)
            ->get(route('marketplace.reports.profit'))
            ->assertForbidden();
    }
}
