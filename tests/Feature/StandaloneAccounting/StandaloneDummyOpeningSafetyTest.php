<?php

use App\Models\StandaloneAccountingJournal;
use Database\Seeders\StandaloneDummyOpeningBalanceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('leaves standalone journals empty after the normal migration sequence', function () {
    expect(StandaloneAccountingJournal::count())->toBe(0)
        ->and(DB::table('standalone_accounting_journal_lines')->count())->toBe(0);
});

it('rejects explicit demo seeding outside local and testing environments', function (string $environment) {
    $originalEnvironment = app()->environment();
    app()->instance('env', $environment);

    try {
        expect(fn () => app(StandaloneDummyOpeningBalanceSeeder::class)->run())
            ->toThrow(RuntimeException::class, 'Saldo awal dummy hanya boleh dibuat di environment local atau testing.');

        expect(StandaloneAccountingJournal::count())->toBe(0)
            ->and(DB::table('standalone_accounting_journal_lines')->count())->toBe(0);
    } finally {
        app()->instance('env', $originalEnvironment);
    }
})->with(['production', 'staging']);

it('preserves an existing demo when the retired migration is applied or rolled back', function () {
    $this->seed(StandaloneDummyOpeningBalanceSeeder::class);
    $journal = StandaloneAccountingJournal::where('reference', 'OB-UMKM-DUMMY-001')->firstOrFail();
    $before = $journal->load('lines')->toArray();

    $migration = require database_path('migrations/2026_10_27_000001_seed_standalone_dummy_opening_balance.php');
    $migration->up();
    $migration->down();

    expect($journal->fresh('lines')->toArray())->toBe($before)
        ->and(StandaloneAccountingJournal::count())->toBe(1);
});
