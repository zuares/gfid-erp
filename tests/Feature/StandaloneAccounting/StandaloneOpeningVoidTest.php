<?php

use App\Models\StandaloneAccountingJournal;
use App\Models\User;
use Database\Seeders\StandaloneDummyOpeningBalanceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('voids the dummy opening journal without deleting its audit trail', function () {
    $this->seed(StandaloneDummyOpeningBalanceSeeder::class);

    $user = User::factory()->create([
        'employee_code' => 'VOID-DUMMY-OWNER',
        'role' => 'owner',
    ]);
    $journal = StandaloneAccountingJournal::query()
        ->where('reference', 'OB-UMKM-DUMMY-001')
        ->firstOrFail();

    $this->actingAs($user)
        ->post(route('standalone-accounting.journals.void', $journal), [
            'reason' => 'Penggantian saldo awal dummy',
        ])
        ->assertRedirect(route('standalone-accounting.journals.show', $journal));

    $journal->refresh();
    $reversal = StandaloneAccountingJournal::query()
        ->where('source_type', 'opening_balance_manual_reversal')
        ->where('source_id', $journal->id)
        ->with('lines')
        ->firstOrFail();

    expect($journal->voided_at)->not->toBeNull()
        ->and($reversal->voided_at)->not->toBeNull()
        ->and((float) $reversal->lines->sum('debit'))->toBe(98000000.0)
        ->and((float) $reversal->lines->sum('credit'))->toBe(98000000.0)
        ->and(StandaloneAccountingJournal::whereKey($journal->id)->exists())->toBeTrue();
});
