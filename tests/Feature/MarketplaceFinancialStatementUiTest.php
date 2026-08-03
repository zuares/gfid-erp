<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceFinancialStatementUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_financial_statement_and_see_sidebar_link(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'OWNER-STATEMENT-' . uniqid(),
        ]);

        $this->actingAs($owner)
            ->get(route('marketplace.reports.financial-statement'))
            ->assertOk()
            ->assertSee('Laporan Keuangan Marketplace')
            ->assertSee('Rekonsiliasi payout')
            ->assertSee(route('marketplace.reports.financial-statement'), false)
            ->assertSee(route('marketplace.reports.financial-statement.export'), false);
    }

    public function test_non_owner_cannot_open_financial_statement(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADMIN-STATEMENT-' . uniqid(),
        ]);

        $this->actingAs($admin)
            ->get(route('marketplace.reports.financial-statement'))
            ->assertForbidden();
    }
}
