<?php

use App\Models\Account;
use App\Models\Journal;
use App\Models\StandaloneAccountingAccount;
use App\Models\StandaloneAccountingAccountMapping;
use App\Models\StandaloneAccountingJournal;
use App\Models\User;
use App\Services\Accounting\StandaloneOpeningBalancePostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function prepareOpeningBalanceMapping(string $legacyCode, string $standaloneCode, string $type = 'asset'): void
{
    Account::updateOrCreate(
        ['code' => $legacyCode],
        ['name' => 'Opening '.$legacyCode, 'type' => $type, 'is_active' => true],
    );
    StandaloneAccountingAccount::updateOrCreate(
        ['code' => $standaloneCode],
        ['name' => 'Opening '.$standaloneCode, 'type' => $type, 'is_active' => true],
    );
    StandaloneAccountingAccountMapping::updateOrCreate(
        ['legacy_account_code' => $legacyCode],
        [
            'standalone_account_code' => $standaloneCode,
            'purpose' => 'Opening balance test',
            'is_active' => true,
        ],
    );
}

it('mirrors an opening balance to standalone accounting idempotently', function () {
    prepareOpeningBalanceMapping('1101', '1-120');
    prepareOpeningBalanceMapping('3101', '3-110', 'equity');

    $legacy = Journal::create([
        'date' => '2026-11-01',
        'description' => 'Saldo awal kas',
        'source_type' => 'opening_balance',
        'source_id' => 1101,
        'posted_at' => now(),
    ]);
    $legacy->lines()->createMany([
        ['account_id' => Account::where('code', '1101')->value('id'), 'debit' => 1000000, 'credit' => 0],
        ['account_id' => Account::where('code', '3101')->value('id'), 'debit' => 0, 'credit' => 1000000],
    ]);

    $service = app(StandaloneOpeningBalancePostingService::class);
    $first = $service->post($legacy);
    $second = $service->post($legacy);

    expect($first?->id)->toBe($second?->id)
        ->and(StandaloneAccountingJournal::where('source_type', 'opening_balance')->where('source_id', $legacy->id)->count())->toBe(1)
        ->and((float) $first->lines()->sum('debit'))->toBe(1000000.0)
        ->and((float) $first->lines()->sum('credit'))->toBe(1000000.0)
        ->and($first->lines()->with('account')->get()->where('account.code', '1-120')->first()->debit)->toBe('1000000.00')
        ->and($first->lines()->with('account')->get()->where('account.code', '3-110')->first()->credit)->toBe('1000000.00');
});

it('voids the standalone opening mirror with an audit reversal', function () {
    prepareOpeningBalanceMapping('1101', '1-120');
    prepareOpeningBalanceMapping('3101', '3-110', 'equity');

    $legacy = Journal::create([
        'date' => '2026-11-01',
        'description' => 'Saldo awal kas',
        'source_type' => 'opening_balance',
        'source_id' => 1101,
        'posted_at' => now(),
    ]);
    $legacy->lines()->createMany([
        ['account_id' => Account::where('code', '1101')->value('id'), 'debit' => 500000, 'credit' => 0],
        ['account_id' => Account::where('code', '3101')->value('id'), 'debit' => 0, 'credit' => 500000],
    ]);

    $service = app(StandaloneOpeningBalancePostingService::class);
    $service->post($legacy);
    $service->voidForSource($legacy->id);

    expect(StandaloneAccountingJournal::where('source_type', 'opening_balance')->where('source_id', $legacy->id)->whereNull('voided_at')->count())->toBe(0)
        ->and(StandaloneAccountingJournal::where('source_type', 'like', 'opening_balance_reversal_%')->where('source_id', $legacy->id)->whereNotNull('voided_at')->count())->toBe(1);
});

it('posts the existing saldo awal page to both ledgers with modal pemilik as default', function () {
    $user = User::factory()->create([
        'employee_code' => 'OPENING-OWNER',
        'role' => 'owner',
    ]);
    $cash = Account::updateOrCreate(
        ['code' => '1101'],
        ['name' => 'Kas Tunai', 'type' => 'asset', 'is_cash' => true, 'is_active' => true],
    );
    Account::updateOrCreate(
        ['code' => '3101'],
        ['name' => 'Modal Pemilik', 'type' => 'equity', 'is_cash' => false, 'is_active' => true],
    );

    $this->actingAs($user)
        ->post(route('accounting.opening-balances.store'), [
            'date' => '2026-11-01',
            'cash_account_id' => $cash->id,
            'equity_account_id' => Account::where('code', '3101')->value('id'),
            'amount' => 2500000,
            'description' => 'Saldo awal kas owner',
        ])
        ->assertRedirect(route('accounting.opening-balances.index'));

    $legacy = Journal::query()->where('source_type', 'opening_balance')->latest('id')->firstOrFail();
    $mirror = StandaloneAccountingJournal::query()
        ->where('source_type', 'opening_balance')
        ->where('source_id', $legacy->id)
        ->with('lines.account')
        ->sole();

    expect($mirror->lines->where('account.code', '1-120')->first()->debit)->toBe('2500000.00')
        ->and($mirror->lines->where('account.code', '3-110')->first()->credit)->toBe('2500000.00');
});
