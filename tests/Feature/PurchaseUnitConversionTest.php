<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Supplier;
use App\Services\Purchasing\PurchaseOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseUnitConversionTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_item_falls_back_to_one_to_one_units(): void
    {
        $item = Item::create([
            'code' => 'LEGACY-UNIT',
            'name' => 'Legacy item',
            'unit' => 'pcs',
            'type' => 'material',
        ]);

        $this->assertSame('pcs', $item->stockUnit());
        $this->assertSame('pcs', $item->purchaseUnit());
        $this->assertSame(1.0, $item->purchaseConversionFactor());
        $this->assertSame(5.0, $item->stockQtyFromPurchase(5));
    }

    public function test_decimal_factor_converts_purchase_qty_to_stock_qty(): void
    {
        $item = Item::create([
            'code' => 'ROLL-UNIT',
            'name' => 'Roll item',
            'unit' => 'meter',
            'stock_unit' => 'meter',
            'purchase_unit' => 'roll',
            'purchase_conversion_factor' => 2.5,
            'type' => 'material',
        ]);

        $this->assertSame(2.5, $item->purchaseConversionFactor());
        $this->assertSame(7.5, $item->stockQtyFromPurchase(3));
    }

    public function test_po_snapshots_purchase_unit_and_keeps_subtotal_in_purchase_currency(): void
    {
        $supplier = Supplier::create(['code' => 'UNIT-SUP', 'name' => 'Unit supplier']);
        $item = Item::create([
            'code' => 'DOZEN-UNIT',
            'name' => 'Dozen item',
            'unit' => 'pcs',
            'stock_unit' => 'pcs',
            'purchase_unit' => 'lusin',
            'purchase_conversion_factor' => 12,
            'type' => 'material',
        ]);

        /** @var PurchaseOrderService $service */
        $service = app(PurchaseOrderService::class);
        $po = $service->create([
            'date' => now()->toDateString(),
            'supplier_id' => $supplier->id,
            'lines' => [[
                'item_id' => $item->id,
                'qty' => 2,
                'unit_price' => 120000,
            ]],
        ]);

        $line = $po->lines()->firstOrFail();

        $this->assertSame('lusin', $line->purchase_unit);
        $this->assertSame('pcs', $line->stock_unit);
        $this->assertEqualsWithDelta(12, (float) $line->conversion_factor, 0.000001);
        $this->assertEqualsWithDelta(24, 2 * (float) $line->conversion_factor, 0.000001);
        $this->assertEqualsWithDelta(240000, (float) $po->subtotal, 0.01);
    }
}
