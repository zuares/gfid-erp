<?php

namespace Tests\Feature\Accounting;

use App\Models\StandaloneAccountingJournal;
use App\Services\Accounting\StandaloneProductionPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SewingRejectConversionAccountingTest extends TestCase
{
    use RefreshDatabase;

    public function test_conversion_mirror_is_idempotent_balanced_and_voidable(): void
    {
        config(['accounting.standalone_dual_posting_production_sewing_return_enabled' => true]);
        $conversionId = $this->conversionFixture();
        $service = app(StandaloneProductionPostingService::class);

        $first = $service->mirrorSewingRejectConversion($conversionId);
        $second = $service->mirrorSewingRejectConversion($conversionId);

        $this->assertNotNull($first);
        $this->assertSame($first->id, $second?->id);
        $this->assertSame(1, StandaloneAccountingJournal::query()
            ->where('source_type', StandaloneProductionPostingService::SOURCE_SEWING_REJECT_CONVERT)
            ->where('source_id', $conversionId)
            ->whereNull('voided_at')
            ->count());
        $this->assertEqualsWithDelta(59677.95, (float) $first->lines->sum('debit'), 0.01);
        $this->assertEqualsWithDelta(59677.95, (float) $first->lines->sum('credit'), 0.01);
        $this->assertSame(2, $first->lines->where('account_id', DB::table('standalone_accounting_accounts')->where('code', '1-153')->value('id'))->count());

        $service->voidSewingRejectConversion($conversionId);
        $service->voidSewingRejectConversion($conversionId);

        $this->assertSame(0, StandaloneAccountingJournal::query()
            ->where('source_id', $conversionId)
            ->where('source_type', StandaloneProductionPostingService::SOURCE_SEWING_REJECT_CONVERT)
            ->whereNull('voided_at')
            ->count());
        $this->assertSame(1, StandaloneAccountingJournal::query()
            ->where('source_id', $conversionId)
            ->where('source_type', StandaloneProductionPostingService::SOURCE_SEWING_REJECT_CONVERT.'_reversal_'.$first->id)
            ->whereNotNull('voided_at')
            ->count());
    }

    public function test_conversion_blocks_when_location_values_do_not_balance(): void
    {
        config(['accounting.standalone_dual_posting_production_sewing_return_enabled' => true]);
        $conversionId = $this->conversionFixture(59677.948717947, 50000);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('tidak lengkap atau tidak balance');

        app(StandaloneProductionPostingService::class)->mirrorSewingRejectConversion($conversionId);
    }

    private function conversionFixture(float $incomingValue = 59677.948717947, ?float $outgoingValue = null): int
    {
        $outgoingValue ??= $incomingValue;
        $rejSewId = (int) DB::table('warehouses')->insertGetId([
            'code' => 'REJ-SEW-CONVERSION-TEST',
            'name' => 'Reject Sewing Conversion Test',
            'type' => 'internal',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $whRtsId = (int) DB::table('warehouses')->insertGetId([
            'code' => 'WH-RTS-CONVERSION-TEST',
            'name' => 'WH RTS Conversion Test',
            'type' => 'internal',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $itemId = (int) DB::table('items')->insertGetId([
            'code' => 'ITEM-REJECT-CONVERSION-TEST',
            'name' => 'Reject Conversion Source Test',
            'unit' => 'pcs',
            'type' => 'finished',
            'last_purchase_price' => 0,
            'hpp' => 0,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $rejectItemId = (int) DB::table('items')->insertGetId([
            'code' => 'REJ-ITEM-REJECT-CONVERSION-TEST',
            'name' => 'Reject Conversion Destination Test',
            'unit' => 'pcs',
            'type' => 'finished',
            'last_purchase_price' => 0,
            'hpp' => 0,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $conversionId = (int) DB::table('sewing_reject_conversions')->insertGetId([
            'code' => 'SRJC-CONVERSION-TEST-001',
            'date' => '2026-10-29',
            'status' => 'posted',
            'item_id' => $itemId,
            'reject_item_id' => $rejectItemId,
            'qty' => 1,
            'notes' => 'Conversion accounting test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([
            [$rejSewId, $itemId, -1, -$outgoingValue, 'out'],
            [$whRtsId, $rejectItemId, 1, $incomingValue, 'in'],
        ] as [$warehouseId, $mutationItemId, $qty, $totalCost, $direction]) {
            DB::table('inventory_mutations')->insert([
                'date' => '2026-10-29',
                'warehouse_id' => $warehouseId,
                'item_id' => $mutationItemId,
                'qty_change' => $qty,
                'direction' => $direction,
                'source_type' => StandaloneProductionPostingService::SOURCE_SEWING_REJECT_CONVERT,
                'source_id' => $conversionId,
                'unit_cost' => abs($incomingValue),
                'total_cost' => $totalCost,
                'notes' => 'Conversion accounting test mutation',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $conversionId;
    }
}
