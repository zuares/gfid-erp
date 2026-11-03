<?php

use App\Models\StandaloneAccountingAccount;
use App\Models\StandaloneAccountingJournal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('prefills the next opening balance reference', function () {
    $user = User::factory()->create([
        'employee_code' => 'NEW-OPENING-REFERENCE',
        'role' => 'owner',
    ]);

    $year = now()->year;
    StandaloneAccountingJournal::create([
        'date' => now()->startOfYear()->toDateString(),
        'reference' => "OB-{$year}-001",
        'source_type' => 'opening_balance_manual',
        'description' => 'Referensi sebelumnya',
        'posted_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('standalone-accounting.journals.create', ['mode' => 'opening']))
        ->assertOk()
        ->assertSee("value=\"OB-{$year}-002\"", false);
});

it('posts saldo awal directly from the new accounting journal form with modal pemilik', function () {
    $user = User::factory()->create([
        'employee_code' => 'NEW-OPENING-OWNER',
        'role' => 'owner',
    ]);
    $cash = StandaloneAccountingAccount::where('code', '1-120')->firstOrFail();
    $equity = StandaloneAccountingAccount::where('code', '3-110')->firstOrFail();

    $this->actingAs($user)
        ->post(route('standalone-accounting.journals.store'), [
            'mode' => 'opening',
            'date' => '2026-11-01',
            'reference' => 'OB-NEW-001',
            'description' => 'Saldo awal kas owner',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 2500000, 'credit' => 0],
                ['account_id' => $equity->id, 'debit' => 0, 'credit' => 2500000],
                ['account_id' => '', 'debit' => '', 'credit' => ''],
                ['account_id' => '', 'debit' => '', 'credit' => ''],
            ],
        ])
        ->assertRedirect(route('standalone-accounting.journals.index'));

    $journal = StandaloneAccountingJournal::where('reference', 'OB-NEW-001')
        ->with('lines.account')
        ->sole();

    expect($journal->source_type)->toBe('opening_balance_manual')
        ->and((float) $journal->lines->sum('debit'))->toBe(2500000.0)
        ->and((float) $journal->lines->sum('credit'))->toBe(2500000.0)
        ->and($journal->lines->where('account.code', '1-120')->first()->debit)->toBe('2500000.00')
        ->and($journal->lines->where('account.code', '3-110')->first()->credit)->toBe('2500000.00');
});

it('rejects inventory lines in the new saldo awal form to prevent double posting', function () {
    $user = User::factory()->create([
        'employee_code' => 'NEW-OPENING-OWNER-2',
        'role' => 'owner',
    ]);
    $inventory = StandaloneAccountingAccount::where('code', '1-150')->firstOrFail();
    $equity = StandaloneAccountingAccount::where('code', '3-110')->firstOrFail();

    $this->actingAs($user)
        ->from(route('standalone-accounting.journals.create', ['mode' => 'opening']))
        ->post(route('standalone-accounting.journals.store'), [
            'mode' => 'opening',
            'date' => '2026-11-01',
            'reference' => 'OB-NEW-INVENTORY-BLOCKED',
            'description' => 'Tidak boleh double inventory',
            'lines' => [
                ['account_id' => $inventory->id, 'debit' => 100000, 'credit' => 0],
                ['account_id' => $equity->id, 'debit' => 0, 'credit' => 100000],
            ],
        ])
        ->assertSessionHasErrors('lines');

    expect(StandaloneAccountingJournal::where('reference', 'OB-NEW-INVENTORY-BLOCKED')->exists())->toBeFalse();
});
