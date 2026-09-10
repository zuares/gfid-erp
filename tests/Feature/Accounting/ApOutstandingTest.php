<?php

namespace Tests\Feature\Accounting;

use App\Http\Controllers\Accounting\OpeningBalanceBatchController;
use App\Models\Account;
use App\Models\PaymentMethod;
use App\Models\PurchaseOrder;
use App\Models\PurchasePayment;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use App\Models\SupplierApOpeningBalance;
use App\Services\Accounting\ApOutstandingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class ApOutstandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_uses_receipt_date_even_when_receipt_was_edited_later(): void
    {
        $order = $this->order();
        $this->receipt($order, '2026-08-20', 1000)->update(['updated_at' => '2026-09-10']);
        $this->receipt($order, '2026-09-10', 2000)->update(['updated_at' => '2026-08-20']);
        $this->receipt($order, '2026-08-21', 3000)->update(['is_replacement' => true]);
        $this->receipt($order, '2026-08-22', 4000)->update(['status' => 'draft']);

        $rows = app(ApOutstandingService::class)->rowsAsOf('2026-08-31');

        $this->assertCount(1, $rows);
        $this->assertEquals(1000, $rows->sum('outstanding'));
        $this->assertSame(11, $rows->first()->days_outstanding);
    }

    public function test_posted_non_replacement_return_reduces_grn_outstanding(): void
    {
        $order = $this->order();
        $receipt = $this->receipt($order, '2026-09-01', 1000);
        PurchaseReturn::create([
            'code' => uniqid('RET-'), 'purchase_receipt_id' => $receipt->id,
            'purchase_order_id' => $order->id, 'supplier_id' => $order->supplier_id,
            'date' => '2026-09-03', 'status' => 'posted', 'total' => 250,
            'resolution_type' => 'refund',
        ]);

        $row = app(ApOutstandingService::class)->rowsAsOf('2026-09-05')->sole();
        $this->assertEquals(750, $row->grn_total);
        $this->assertEquals(750, $row->outstanding);
    }

    public function test_replacement_return_does_not_reduce_ap(): void
    {
        $order = $this->order();
        $receipt = $this->receipt($order, '2026-09-01', 1000);
        PurchaseReturn::create([
            'code' => uniqid('RET-'), 'purchase_receipt_id' => $receipt->id,
            'purchase_order_id' => $order->id, 'supplier_id' => $order->supplier_id,
            'date' => '2026-09-03', 'status' => 'posted', 'total' => 250,
            'resolution_type' => 'replacement',
        ]);

        $this->assertEquals(1000, app(ApOutstandingService::class)->rowsAsOf('2026-09-05')->sum('outstanding'));
    }

    public function test_return_after_opening_reduces_opening_balance(): void
    {
        $order = $this->order();
        $receipt = $this->receipt($order, '2026-08-20', 1000);
        $this->opening($order, 1000);
        PurchaseReturn::create([
            'code' => uniqid('RET-'), 'purchase_receipt_id' => $receipt->id,
            'purchase_order_id' => $order->id, 'supplier_id' => $order->supplier_id,
            'date' => '2026-09-02', 'status' => 'posted', 'total' => 300,
            'resolution_type' => 'refund',
        ]);

        $row = app(ApOutstandingService::class)->rowsAsOf('2026-09-05')->sole();
        $this->assertTrue($row->is_opening);
        $this->assertEquals(700, $row->outstanding);
    }

    public function test_payments_after_opening_reduce_opening_and_preopening_payments_are_not_counted_twice(): void
    {
        $order = $this->order();
        $this->receipt($order, '2026-08-20', 1000);
        $this->payment($order, '2026-08-31', 200);
        $this->opening($order, 800);
        $this->payment($order, '2026-09-02', 300);
        $this->payment($order, '2026-09-03', 100, 'dp_apply');
        $this->payment($order, '2026-09-03', 999, 'dp');
        $this->payment($order, '2026-09-03', 999, 'loan_apply');
        $this->payment($order, '2026-09-03', 999)->update(['voided_at' => now()]);
        $this->payment($order, '2026-09-10', 400);

        $service = app(ApOutstandingService::class);
        $this->assertEquals(1000, $service->rowsAsOf('2026-08-30')->sum('outstanding'));
        $this->assertEquals(800, $service->rowsAsOf('2026-08-31')->sum('outstanding'));
        $row = $service->rowsAsOf('2026-09-05')->sole();
        $this->assertTrue($row->is_opening);
        $this->assertEquals(400, $row->paid_total);
        $this->assertEquals(400, $row->outstanding);
        $this->assertCount(0, $service->rowsAsOf('2026-09-10'));
    }

    public function test_same_day_receipts_are_included_in_opening_regardless_of_timestamp_format(): void
    {
        $order = $this->order();
        $receipt = $this->receipt($order, '2026-08-31', 1000);
        \Illuminate\Support\Facades\DB::table('purchase_receipts')->where('id', $receipt->id)
            ->update(['date' => '2026-08-31 15:00:00']);
        $this->opening($order, 600);
        $this->assertEquals(600, app(ApOutstandingService::class)->rowsAsOf('2026-09-05')->sum('outstanding'));
    }

    public function test_mixed_po_splits_legacy_payments_between_old_debt_and_new_receipts(): void
    {
        $order = $this->order();
        $this->receipt($order, '2026-08-20', 1000);
        $this->payment($order, '2026-08-21', 200);
        $this->opening($order, 800);
        $this->receipt($order, '2026-09-02', 500);
        $this->payment($order, '2026-09-03', 900);

        $row = app(ApOutstandingService::class)->rowsAsOf('2026-09-05')->sole();
        $this->assertFalse($row->is_opening);
        $this->assertEquals(500, $row->grn_total);
        $this->assertEquals(100, $row->paid_total);
        $this->assertEquals(400, $row->outstanding);
    }

    public function test_receipt_linked_payment_on_mixed_po_does_not_reduce_opening(): void
    {
        $order = $this->order();
        $old = $this->receipt($order, '2026-08-20', 1000);
        $this->opening($order, 1000);
        $new = $this->receipt($order, '2026-09-02', 500);
        $this->payment($order, '2026-09-03', 300)->update(['purchase_receipt_id' => $new->id]);
        $this->payment($order, '2026-09-04', 200)->update(['purchase_receipt_id' => $old->id]);

        $rows = app(ApOutstandingService::class)->rowsAsOf('2026-09-05');
        $this->assertEquals(800, $rows->firstWhere('is_opening', true)->outstanding);
        $this->assertEquals(200, $rows->firstWhere('is_opening', false)->outstanding);
    }

    public function test_opening_payments_do_not_leak_to_other_suppliers_or_future_openings(): void
    {
        $order = $this->order();
        $this->receipt($order, '2026-08-20', 1000);
        $this->opening($order, 100);
        $this->opening($order, 400)->update(['date' => '2026-09-04']);
        $this->payment($order, '2026-09-02', 300);
        $other = $this->order();
        $this->receipt($other, '2026-08-20', 2000);
        $this->opening($other, 700);

        $service = app(ApOutstandingService::class);
        $this->assertEquals(1100, $service->rowsAsOf('2026-09-05')->sum('outstanding'));
        $this->assertEquals(400, $service->rowsAsOf('2026-09-05', $order->supplier_id)->sum('outstanding'));
    }

    public function test_new_po_payment_does_not_reduce_opening_and_old_payments_use_openings_in_order(): void
    {
        $order = $this->order();
        $this->receipt($order, '2026-08-20', 1000);
        $this->opening($order, 400)->update(['reference_no' => 'FIRST']);
        $this->opening($order, 600)->update(['reference_no' => 'SECOND']);
        $newOrder = PurchaseOrder::create([
            'code' => 'PO-NEW', 'supplier_id' => $order->supplier_id,
            'date' => '2026-09-02', 'status' => 'approved',
        ]);
        $this->receipt($newOrder, '2026-09-02', 500);
        $this->payment($newOrder, '2026-09-03', 300);
        $this->payment($order, '2026-09-03', 500);

        $rows = app(ApOutstandingService::class)->rowsAsOf('2026-09-05');
        $this->assertCount(2, $rows);
        $this->assertEquals(200, $rows->firstWhere('is_opening', false)->outstanding);
        $openingRow = $rows->firstWhere('is_opening', true);
        $this->assertSame('SECOND', $openingRow->reference_no);
        $this->assertEquals(500, $openingRow->outstanding);
    }

    public function test_void_opening_restores_operational_debt_and_batch_prefill_matches_report(): void
    {
        $order = $this->order();
        $this->receipt($order, '2026-08-20', 1000);
        $opening = $this->opening($order, 800);
        $this->payment($order, '2026-09-02', 300);
        $prefill = new ReflectionMethod(OpeningBalanceBatchController::class, 'tradePayableAsOf');
        $this->assertEquals(500, $prefill->invoke(app(OpeningBalanceBatchController::class), '2026-09-05'));

        $opening->update(['status' => 'void', 'voided_at' => now()]);
        $this->assertEquals(700, app(ApOutstandingService::class)->rowsAsOf('2026-09-05')->sum('outstanding'));
    }

    private function order(): PurchaseOrder
    {
        $supplier = Supplier::create(['code' => uniqid('SUP-'), 'name' => 'AP Supplier']);

        return PurchaseOrder::create([
            'code' => uniqid('PO-'), 'supplier_id' => $supplier->id,
            'date' => '2026-08-01', 'status' => 'approved',
        ]);
    }

    private function receipt(PurchaseOrder $order, string $date, float $amount): PurchaseReceipt
    {
        return PurchaseReceipt::create([
            'code' => uniqid('GRN-'), 'purchase_order_id' => $order->id,
            'supplier_id' => $order->supplier_id, 'date' => $date,
            'status' => 'posted', 'grand_total' => $amount, 'is_replacement' => false,
        ]);
    }

    private function payment(PurchaseOrder $order, string $date, float $amount, string $type = 'payment'): PurchasePayment
    {
        $method = PaymentMethod::firstOrCreate(['code' => 'AP-CASH'], ['name' => 'Cash', 'mode' => 'cash', 'is_active' => true]);

        return PurchasePayment::create([
            'purchase_order_id' => $order->id, 'date' => $date,
            'payment_method_id' => $method->id, 'amount' => $amount, 'type' => $type,
        ]);
    }

    private function opening(PurchaseOrder $order, float $amount): SupplierApOpeningBalance
    {
        $ap = Account::firstOrCreate(['code' => '2101'], ['name' => 'AP', 'type' => 'liability']);
        $offset = Account::firstOrCreate(['code' => '3101'], ['name' => 'Capital', 'type' => 'equity']);

        return SupplierApOpeningBalance::create([
            'supplier_id' => $order->supplier_id, 'date' => '2026-08-31',
            'amount' => $amount, 'status' => 'posted',
            'ap_account_id' => $ap->id, 'offset_account_id' => $offset->id,
        ]);
    }
}
