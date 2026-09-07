<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Journal;
use App\Models\Supplier;
use App\Models\SupplierApOpeningBalance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierApOpeningBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_ap_opening_balance_posts_journal_and_appears_in_ap_report(): void
    {
        $user = User::factory()->create(['role' => 'owner', 'employee_code' => 'OB-TEST-1']);
        $supplier = Supplier::create([
            'code' => 'SUP-OB-1',
            'name' => 'Supplier Opening Test',
            'type' => 'supplier',
            'active' => true,
        ]);
        [$ap, $offset] = $this->accounts();

        $response = $this->actingAs($user)->post(route('accounting.supplier-ap-openings.store'), [
            'supplier_id' => $supplier->id,
            'date' => '2026-08-31',
            'invoice_date' => '2026-08-15',
            'due_date' => '2026-09-15',
            'reference_no' => 'INV-LAMA-001',
            'amount' => '10.000.000',
            'ap_account_id' => $ap->id,
            'offset_account_id' => $offset->id,
            'notes' => 'Saldo awal migrasi',
        ]);

        $response->assertRedirect(route('accounting.supplier-ap-openings.index'));

        $opening = SupplierApOpeningBalance::firstOrFail();
        $journal = Journal::where('source_type', 'supplier_ap_opening_balance')->firstOrFail();

        $this->assertSame('posted', $opening->status);
        $this->assertSame($journal->id, $opening->journal_id);
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $journal->id,
            'account_id' => $offset->id,
            'debit' => 10000000,
            'credit' => 0,
        ]);
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $journal->id,
            'account_id' => $ap->id,
            'debit' => 0,
            'credit' => 10000000,
        ]);

        $this->actingAs($user)
            ->get(route('accounting.ap-report.index', [
                'as_of' => '2026-09-05',
                'supplier_id' => $supplier->id,
            ]))
            ->assertOk()
            ->assertSee('Saldo Awal Hutang')
            ->assertSee('INV-LAMA-001')
            ->assertSee('Rp 10.000.000');
    }

    public function test_voiding_supplier_ap_opening_balance_creates_reversal_and_hides_it_from_report(): void
    {
        $user = User::factory()->create(['role' => 'owner', 'employee_code' => 'OB-TEST-2']);
        $supplier = Supplier::create([
            'code' => 'SUP-OB-2',
            'name' => 'Supplier Void Test',
            'type' => 'supplier',
            'active' => true,
        ]);
        [$ap, $offset] = $this->accounts('2101-VOID', '3101-VOID');

        $this->actingAs($user)->post(route('accounting.supplier-ap-openings.store'), [
            'supplier_id' => $supplier->id,
            'date' => '2026-08-31',
            'amount' => '2500000',
            'ap_account_id' => $ap->id,
            'offset_account_id' => $offset->id,
        ]);

        $opening = SupplierApOpeningBalance::firstOrFail();
        $this->actingAs($user)
            ->post(route('accounting.supplier-ap-openings.void', $opening), [
                'reason' => 'Salah input',
            ])
            ->assertRedirect();

        $this->assertSame('void', $opening->fresh()->status);
        $this->assertNotNull($opening->fresh()->voided_at);
        $this->assertDatabaseHas('journals', [
            'source_type' => 'supplier_ap_opening_balance_void',
            'source_id' => $opening->id,
        ]);

        $this->actingAs($user)
            ->get(route('accounting.ap-report.index', [
                'as_of' => '2026-09-05',
                'supplier_id' => $supplier->id,
            ]))
            ->assertOk()
            ->assertSee('Tidak ada hutang outstanding.');
    }

    public function test_bulk_mode_posts_only_suppliers_with_a_positive_amount(): void
    {
        $user = User::factory()->create(['role' => 'owner', 'employee_code' => 'OB-TEST-3']);
        $supplierA = Supplier::create([
            'code' => 'SUP-OB-3',
            'name' => 'Supplier Bulk A',
            'type' => 'supplier',
            'active' => true,
        ]);
        $supplierB = Supplier::create([
            'code' => 'SUP-OB-4',
            'name' => 'Supplier Bulk B',
            'type' => 'supplier',
            'active' => true,
        ]);
        [$ap, $offset] = $this->accounts('2101-BULK', '3101-BULK');

        $this->actingAs($user)
            ->post(route('accounting.supplier-ap-openings.store'), [
                'bulk' => '1',
                'date' => '2026-08-31',
                'invoice_date' => '2026-08-31',
                'amounts' => [
                    $supplierA->id => '1.500.000',
                    $supplierB->id => '0',
                ],
                'ap_account_id' => $ap->id,
                'offset_account_id' => $offset->id,
            ])
            ->assertRedirect(route('accounting.supplier-ap-openings.index'));

        $this->assertDatabaseCount('supplier_ap_opening_balances', 1);
        $this->assertDatabaseHas('supplier_ap_opening_balances', [
            'supplier_id' => $supplierA->id,
            'amount' => 1500000,
            'status' => 'posted',
        ]);
        $this->assertDatabaseMissing('supplier_ap_opening_balances', [
            'supplier_id' => $supplierB->id,
        ]);
    }

    private function accounts(string $apCode = '2101-OB', string $offsetCode = '3101-OB'): array
    {
        return [
            Account::create([
                'code' => $apCode,
                'name' => 'Hutang Dagang Test',
                'type' => 'liability',
                'is_active' => true,
            ]),
            Account::create([
                'code' => $offsetCode,
                'name' => 'Modal Test',
                'type' => 'equity',
                'is_active' => true,
            ]),
        ];
    }
}
