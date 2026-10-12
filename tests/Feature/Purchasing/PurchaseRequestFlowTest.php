<?php

namespace Tests\Feature\Purchasing;

use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_is_sent_to_supplier_allocation_after_creating_a_purchase_request(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADMIN-PR-FLOW',
        ]);
        $item = Item::create([
            'code' => 'PR-FLOW-ITEM',
            'name' => 'PR Flow Item',
            'unit' => 'pcs',
            'type' => 'material',
            'active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('purchasing.purchase_requests.store'), [
            'date' => '2026-10-12',
            'lines' => [
                ['item_id' => $item->id, 'qty' => 2],
            ],
        ]);

        $purchaseRequest = PurchaseRequest::query()->latest('id')->firstOrFail();

        $response->assertRedirect(route(
            'purchasing.purchase_requests.allocate_suppliers',
            $purchaseRequest,
        ));
        $this->assertSame('draft', $purchaseRequest->status);
        $this->assertTrue($purchaseRequest->isConvertible());
    }
}
