<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceSkuMappingUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_sku_mapping_page_defines_stock_number_formatter(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'OWNER-SKU-MAPPING-'.uniqid(),
        ]);

        $this->actingAs($owner)
            ->get(route('marketplace.sku-mapping'))
            ->assertOk()
            ->assertSee("const FMT = new Intl.NumberFormat('id-ID');", false);
    }
}
