<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceSalesDashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_open_sales_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADMIN-SALES-DASHBOARD-'.uniqid(),
        ]);

        $this->actingAs($admin)
            ->get(route('marketplace.dashboard.sales'))
            ->assertForbidden();
    }

    public function test_admin_sidebar_hides_sales_dashboard_link(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADMIN-SALES-SIDEBAR-'.uniqid(),
        ]);

        $this->actingAs($admin)
            ->get(route('marketplace.orders'))
            ->assertOk()
            ->assertDontSee('Dashboard Penjualan')
            ->assertDontSee(route('marketplace.dashboard.sales'), false);
    }
}
