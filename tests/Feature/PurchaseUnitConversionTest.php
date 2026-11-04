<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptLine;
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
        $this->assertEqualsWithDelta(24, $line->stockQty(), 0.000001);
        $this->assertEqualsWithDelta(10000, $line->stockUnitPrice(), 0.000001);
        $this->assertEqualsWithDelta(240000, $line->calculatedLineTotal(), 0.01);
        $this->assertEqualsWithDelta(240000, (float) $po->subtotal, 0.01);
    }

    public function test_po_can_select_stock_unit_and_falls_back_when_conversion_is_missing(): void
    {
        $supplier = Supplier::create(['code' => 'STOCK-SUP', 'name' => 'Stock supplier']);
        $item = Item::create([
            'code' => 'BOX-UNIT',
            'name' => 'Box item',
            'unit' => 'pcs',
            'stock_unit' => 'pcs',
            'purchase_unit' => 'box',
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
                'qty' => 3,
                'purchase_unit' => 'pcs',
                'stock_unit' => 'pcs',
                'conversion_factor' => 12,
                'unit_price' => 1000,
            ]],
        ]);

        $line = $po->lines()->firstOrFail();

        $this->assertSame('pcs', $line->purchase_unit);
        $this->assertSame('pcs', $line->stock_unit);
        $this->assertEqualsWithDelta(1, (float) $line->conversion_factor, 0.000001);
        $this->assertEqualsWithDelta(3000, (float) $po->subtotal, 0.01);
    }

    public function test_latest_grn_price_uses_item_conversion_when_legacy_line_unit_differs_from_master(): void
    {
        $supplier = Supplier::create(['code' => 'LEGACY-GRN-SUP', 'name' => 'Legacy GRN supplier']);
        $item = Item::create([
            'code' => 'OPP-LEGACY-UNIT',
            'name' => 'Plastik OPP legacy',
            'unit' => 'pcs',
            'stock_unit' => 'pcs',
            'purchase_unit' => 'pack',
            'purchase_conversion_factor' => 500,
            'type' => 'material',
        ]);

        $receipt = PurchaseReceipt::create([
            'code' => 'GRN-LEGACY-UNIT',
            'date' => now()->toDateString(),
            'supplier_id' => $supplier->id,
            'subtotal' => 100000,
            'grand_total' => 100000,
            'status' => 'posted',
        ]);

        PurchaseReceiptLine::create([
            'purchase_receipt_id' => $receipt->id,
            'item_id' => $item->id,
            'qty_received' => 1,
            'unit_price' => 100000,
            'line_total' => 100000,
            'purchase_unit' => 'pcs',
            'stock_unit' => 'pcs',
            'conversion_factor' => 1,
        ]);

        $purchase = $item->latestPostedGrnPurchase();

        $this->assertNotNull($purchase);
        $this->assertEqualsWithDelta(200, $purchase->stock_unit_price, 0.000001);
    }
}
