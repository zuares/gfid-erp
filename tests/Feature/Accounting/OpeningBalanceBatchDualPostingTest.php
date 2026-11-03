<?php

use App\Models\Account;
use App\Models\Journal;
use App\Models\StandaloneAccountingAccount;
use App\Models\StandaloneAccountingAccountMapping;
use App\Models\StandaloneAccountingJournal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function prepareOpeningBatchAccounts(): void
{
    foreach ([
        ['1101', 'Kas Tunai', 'asset', true],
        ['1201', 'Persediaan Bahan Baku', 'asset', false],
        ['1202', 'Persediaan WIP', 'asset', false],
        ['1301', 'Piutang Dagang', 'asset', false],
        ['1501', 'Peralatan Produksi', 'asset', false],
        ['1591', 'Akumulasi Penyusutan Peralatan', 'asset', false],
        ['3101', 'Modal Pemilik', 'equity', false],
    ] as [$code, $name, $type, $isCash]) {
        Account::updateOrCreate(
            ['code' => $code],
            ['name' => $name, 'type' => $type, 'is_cash' => $isCash, 'is_active' => true],
        );
    }

    foreach ([
        ['1-120', 'Kas Tunai', 'asset'],
        ['1-130', 'Piutang Dagang', 'asset'],
        ['1-330', 'Peralatan Produksi', 'asset'],
        ['1-331', 'Akumulasi Penyusutan Peralatan', 'asset'],
        ['3-110', 'Modal Pemilik', 'equity'],
    ] as [$code, $name, $type]) {
        StandaloneAccountingAccount::updateOrCreate(
            ['code' => $code],
            ['name' => $name, 'type' => $type, 'is_active' => true],
        );
    }

    foreach ([
        ['1101', '1-120'],
        ['1301', '1-130'],
        ['1501', '1-330'],
        ['1591', '1-331'],
        ['3101', '3-110'],
    ] as [$legacyCode, $standaloneCode]) {
        StandaloneAccountingAccountMapping::updateOrCreate(
            ['legacy_account_code' => $legacyCode],
            [
                'standalone_account_code' => $standaloneCode,
                'purpose' => 'Opening batch test',
                'is_active' => true,
            ],
        );
    }
}

function openingBatchUser(): User
{
    return User::factory()->create([
        'employee_code' => 'OPENING-BATCH-OWNER',
        'role' => 'owner',
    ]);
}

it('only shows mapped non-inventory accounts on the batch opening page', function () {
    prepareOpeningBatchAccounts();
    $user = openingBatchUser();

    $response = $this->actingAs($user)
        ->get(route('accounting.opening-balances-batch.create'));

    $response->assertOk()
        ->assertSee('Saldo Awal Non-Persediaan')
        ->assertSee('Opening Stock Opname')
        ->assertDontSee('name="account_id[]" value="'.Account::where('code', '1201')->value('id').'"', false)
        ->assertDontSee('name="account_id[]" value="'.Account::where('code', '1202')->value('id').'"', false)
        ->assertSee('name="account_id[]" value="'.Account::where('code', '1301')->value('id').'"', false)
        ->assertSee('name="account_id[]" value="'.Account::where('code', '1501')->value('id').'"', false);
});

it('posts the batch opening balance to legacy and standalone accounting', function () {
    prepareOpeningBatchAccounts();
    $user = openingBatchUser();
    $today = now()->toDateString();
    $cash = Account::where('code', '1101')->firstOrFail();
    $capital = Account::where('code', '3101')->firstOrFail();

    $response = $this->actingAs($user)->post(
        route('accounting.opening-balances-batch.store'),
        [
            'date' => $today,
            'description' => 'Opening batch dual posting test',
            'account_id' => [$cash->id, $capital->id],
            'debit' => [2500000, 0],
            'credit' => [0, 2500000],
        ],
    );

    $response->assertRedirect(route('accounting.opening-balances-batch.index'));

    $legacy = Journal::query()
        ->where('source_type', 'opening_balance_batch')
        ->where('description', 'Opening batch dual posting test')
        ->latest('id')
        ->firstOrFail();

    $mirror = StandaloneAccountingJournal::query()
        ->where('source_type', 'opening_balance')
        ->where('source_id', $legacy->id)
        ->with('lines.account')
        ->firstOrFail();

    expect((float) $mirror->lines->sum('debit'))->toBe(2500000.0)
        ->and((float) $mirror->lines->sum('credit'))->toBe(2500000.0)
        ->and($mirror->lines->where('account.code', '1-120')->first()->debit)->toBe('2500000.00')
        ->and($mirror->lines->where('account.code', '3-110')->first()->credit)->toBe('2500000.00');
});

it('prefills non-inventory asset balances and keeps accumulated depreciation as credit', function () {
    prepareOpeningBatchAccounts();

    $equipment = Account::where('code', '1501')->firstOrFail();
    $accumulatedDepreciation = Account::where('code', '1591')->firstOrFail();
    $journal = Journal::create([
        'date' => now()->toDateString(),
        'description' => 'Prefill saldo non-persediaan test',
        'source_type' => 'prefill_test',
        'posted_at' => now(),
    ]);
    $journal->lines()->createMany([
        ['account_id' => $equipment->id, 'debit' => 4000000, 'credit' => 0],
        ['account_id' => $accumulatedDepreciation->id, 'debit' => 0, 'credit' => 500000],
    ]);

    $view = app(\App\Http\Controllers\Accounting\OpeningBalanceBatchController::class)->create();
    $data = $view->getData();

    expect($data['prefill'][$equipment->id])->toBe(4000000.0)
        ->and($data['prefill'][$accumulatedDepreciation->id])->toBe(500000.0)
        ->and($data['creditAssetCodes'])->toContain('1591');
});

it('voids both the legacy batch and its standalone mirror', function () {
    prepareOpeningBatchAccounts();
    $user = openingBatchUser();
    $today = now()->toDateString();
    $cash = Account::where('code', '1101')->firstOrFail();
    $capital = Account::where('code', '3101')->firstOrFail();

    $this->actingAs($user)->post(route('accounting.opening-balances-batch.store'), [
        'date' => $today,
        'description' => 'Opening batch void test',
        'account_id' => [$cash->id, $capital->id],
        'debit' => [1000000, 0],
        'credit' => [0, 1000000],
    ])->assertRedirect();

    $legacy = Journal::query()
        ->where('source_type', 'opening_balance_batch')
        ->where('description', 'Opening batch void test')
        ->latest('id')
        ->firstOrFail();

    $this->actingAs($user)
        ->post(route('accounting.opening-balances-batch.void', $legacy), [
            'reason' => 'Koreksi test',
        ])
        ->assertRedirect(route('accounting.opening-balances-batch.index'));

    expect(StandaloneAccountingJournal::query()
        ->where('source_type', 'opening_balance')
        ->where('source_id', $legacy->id)
        ->whereNull('voided_at')
        ->exists())->toBeFalse()
        ->and(StandaloneAccountingJournal::query()
            ->where('source_id', $legacy->id)
            ->where('source_type', 'like', 'opening_balance_reversal_%')
            ->whereNotNull('voided_at')
            ->exists())->toBeTrue();
});
