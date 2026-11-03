<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\PaymentMethod;
use App\Models\PurchaseOrder;
use App\Models\PurchasePayment;
use App\Models\StandaloneAccountingAccount;
use App\Models\StandaloneAccountingJournal;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Accounting\JournalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StandalonePurchasePaymentPostingTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_payment_is_posted_to_legacy_and_standalone_ledgers(): void
    {
        $this->enableDualPosting();
        $this->seedLegacyAccounts();
        [$order, $method, $cash] = $this->paymentContext();

        $payment = PurchasePayment::create([
            'purchase_order_id' => $order->id,
            'date' => '2026-10-27',
            'payment_method_id' => $method->id,
            'cash_account_id' => $cash->id,
            'type' => 'payment',
            'amount' => 1000,
        ]);

        $legacyJournal = app(JournalService::class)->postPurchasePayment($payment->fresh(['purchaseOrder', 'cashAccount', 'paymentMethod']));
        $payment->refresh();
        $standaloneJournal = $payment->standaloneJournal()->with('lines.account')->first();

        $this->assertNotNull($legacyJournal->id);
        $this->assertNotNull($payment->standalone_journal_id);
        $this->assertNotNull($standaloneJournal);
        $this->assertSame('purchase_payment', $standaloneJournal->source_type);
        $this->assertSame($payment->id, (int) $standaloneJournal->source_id);
        $this->assertSame(1000.0, (float) $standaloneJournal->lines->sum('debit'));
        $this->assertSame(1000.0, (float) $standaloneJournal->lines->sum('credit'));
        $this->assertSame(1, $standaloneJournal->lines->where('account.code', '2-110')->count());
        $this->assertSame(1, $standaloneJournal->lines->where('account.code', '1-120')->count());

        app(JournalService::class)->postPurchasePayment($payment->fresh(['purchaseOrder', 'cashAccount', 'paymentMethod']));

        $this->assertSame(1, StandaloneAccountingJournal::where('source_type', 'purchase_payment')->where('source_id', $payment->id)->count());
    }

    public function test_dp_and_dp_apply_use_the_new_advance_account(): void
    {
        $this->enableDualPosting();
        $this->seedLegacyAccounts();
        [$order, $method, $cash] = $this->paymentContext();

        $dp = PurchasePayment::create([
            'purchase_order_id' => $order->id,
            'date' => '2026-10-27',
            'payment_method_id' => $method->id,
            'cash_account_id' => $cash->id,
            'type' => 'dp',
            'amount' => 500,
        ]);
        app(JournalService::class)->postPurchasePayment($dp->fresh(['purchaseOrder', 'cashAccount', 'paymentMethod']));

        $applyMethod = PaymentMethod::create([
            'code' => 'TEST-DP-APPLY',
            'name' => 'Offset DP',
            'mode' => 'credit',
            'is_active' => true,
        ]);
        $apply = PurchasePayment::create([
            'purchase_order_id' => $order->id,
            'date' => '2026-10-27',
            'payment_method_id' => $applyMethod->id,
            'cash_account_id' => null,
            'type' => 'dp_apply',
            'amount' => 500,
        ]);
        app(JournalService::class)->postPurchasePayment($apply->fresh(['purchaseOrder', 'cashAccount', 'paymentMethod']));

        $this->assertSame(2, StandaloneAccountingJournal::where('source_type', 'purchase_payment')->count());
        $this->assertSame(2, StandaloneAccountingJournal::whereHas('lines.account', fn ($query) => $query->where('code', '1-180'))->count());
    }

    public function test_voiding_the_legacy_payment_voids_its_standalone_mirror(): void
    {
        $this->enableDualPosting();
        $this->seedLegacyAccounts();
        [$order, $method, $cash] = $this->paymentContext();

        $payment = PurchasePayment::create([
            'purchase_order_id' => $order->id,
            'date' => '2026-10-27',
            'payment_method_id' => $method->id,
            'cash_account_id' => $cash->id,
            'type' => 'dp',
            'amount' => 100,
        ]);
        $legacyJournal = app(JournalService::class)->postPurchasePayment($payment->fresh(['purchaseOrder', 'cashAccount', 'paymentMethod']));
        $payment->refresh();

        app(JournalService::class)->voidById($legacyJournal->id);

        $original = $payment->standaloneJournal()->first()->fresh();
        $this->assertNotNull($original->voided_at);

        $reversal = StandaloneAccountingJournal::query()
            ->where('source_type', 'purchase_payment_reversal_'.$original->id)
            ->where('source_id', $payment->id)
            ->with('lines')
            ->first();

        $this->assertNotNull($reversal);
        $this->assertNotNull($reversal->voided_at);
        $this->assertSame(
            (float) $original->lines()->sum('debit'),
            (float) $reversal->lines->sum('credit')
        );
        $this->assertSame(
            (float) $original->lines()->sum('credit'),
            (float) $reversal->lines->sum('debit')
        );

        $user = User::factory()->create(['role' => 'owner', 'employee_code' => 'AUDIT-VOID']);
        $cashLine = $reversal->lines->firstWhere('credit', '>', 0);
        $cashAccount = StandaloneAccountingAccount::findOrFail($cashLine->account_id);

        $this->actingAs($user)
            ->get(route('standalone-accounting.accounts.show', $cashAccount))
            ->assertOk()
            ->assertSee('Riwayat void / reversal')
            ->assertSee($reversal->reference);
    }

    private function enableDualPosting(): void
    {
        config(['accounting.standalone_dual_posting_enabled' => true]);
    }

    private function seedLegacyAccounts(): void
    {
        foreach ([
            [JournalService::CODE_AP, 'Hutang Dagang', 'liability', false],
            [JournalService::CODE_ADV_PURCHASE, 'Uang Muka Pembelian', 'asset', false],
            ['1101', 'Kas Tunai', 'asset', true],
        ] as [$code, $name, $type, $isCash]) {
            Account::firstOrCreate(['code' => $code], [
                'name' => $name,
                'type' => $type,
                'is_cash' => $isCash,
                'is_active' => true,
            ]);
        }
    }

    private function paymentContext(): array
    {
        $method = PaymentMethod::create([
            'code' => 'TEST-CASH-'.uniqid(),
            'name' => 'Tunai',
            'mode' => 'cash',
            'is_active' => true,
        ]);
        $supplier = Supplier::create([
            'code' => 'TEST-SUP-'.uniqid(),
            'name' => 'Supplier Test',
        ]);
        $order = PurchaseOrder::create([
            'code' => 'TEST-PO-'.uniqid(),
            'date' => '2026-10-27',
            'supplier_id' => $supplier->id,
            'grand_total' => 1000,
            'status' => 'approved',
        ]);

        return [$order, $method, Account::where('code', '1101')->firstOrFail()];
    }
}
