<?php

use App\Models\Account;
use App\Models\Journal;
use App\Models\StandaloneAccountingAccount;
use App\Models\StandaloneAccountingAccountMapping;
use App\Models\StandaloneAccountingJournal;
use App\Models\StandaloneOpeningMigration;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Accounting\StandaloneOpeningMigrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(\Carbon\Carbon::parse('2026-11-03 10:00:00'));
    $this->owner = User::factory()->create(['employee_code' => 'MIGRATION-OWNER', 'role' => 'owner']);
    $this->actingAs($this->owner);
    SystemSetting::set(SystemSetting::KEY_CUTOFF_DATE, '2026-08-31');
    $this->legacyAccounts = collect([
        ['1202', 'WIP', 'asset'], ['1101', 'Kas', 'asset'], ['3101', 'Modal', 'equity'],
    ])->mapWithKeys(function ($row) {
        $account = Account::updateOrCreate(['code' => $row[0]], ['name' => $row[1], 'type' => $row[2], 'is_active' => true]);

        return [$row[0] => $account];
    });
    $this->mapping = [
        $this->legacyAccounts['1202']->id => StandaloneAccountingAccount::where('code', '1-151')->value('id'),
        $this->legacyAccounts['1101']->id => StandaloneAccountingAccount::where('code', '1-120')->value('id'),
        $this->legacyAccounts['3101']->id => StandaloneAccountingAccount::where('code', '3-110')->value('id'),
    ];
    $this->source = migrationSourceJournal($this->legacyAccounts, '2026-10-31');
});

function migrationSourceJournal($accounts, string $date, array $attributes = []): Journal
{
    $journal = Journal::create(array_merge(['date' => $date, 'description' => 'Migration source', 'source_type' => 'opening_balance_batch', 'posted_at' => now()], $attributes));
    $journal->lines()->createMany([
        ['account_id' => $accounts['1202']->id, 'debit' => '100.25', 'credit' => 0],
        ['account_id' => $accounts['1101']->id, 'debit' => '50.00', 'credit' => 0],
        ['account_id' => $accounts['3101']->id, 'debit' => 0, 'credit' => '150.25'],
    ]);

    return $journal;
}

function migrationPreview($test): string
{
    $response = $test->post(route('standalone-accounting.opening-migration.preview'), ['as_of' => '2026-10-31', 'mapping' => $test->mapping]);
    $response->assertRedirect();
    parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);

    return $query['preview'];
}

function migrationPost($test, string $token)
{
    return $test->post(route('standalone-accounting.opening-migration.store'), ['preview_token' => $token, 'confirmed' => 1]);
}

it('previews the inclusive cutoff period with posted nonvoid journals and includes inactive source accounts', function () {
    migrationSourceJournal($this->legacyAccounts, '2026-08-30');
    $atCutoff = migrationSourceJournal($this->legacyAccounts, '2026-08-31');
    migrationSourceJournal($this->legacyAccounts, '2026-11-01');
    migrationSourceJournal($this->legacyAccounts, '2026-09-01', ['posted_at' => null]);
    migrationSourceJournal($this->legacyAccounts, '2026-09-01', ['voided_at' => now()]);
    migrationSourceJournal($this->legacyAccounts, '2026-09-01', ['source_type' => 'opening_balance_batch_void']);
    $this->legacyAccounts['1202']->update(['is_active' => false]);

    $snapshot = app(StandaloneOpeningMigrationService::class)->preview('2026-10-31', $this->mapping);
    expect($snapshot['source_journal_ids'])->toBe([$this->source->id, $atCutoff->id])
        ->and($snapshot['debit'])->toBe(30050)
        ->and($snapshot['credit'])->toBe(30050)
        ->and($snapshot['mirror_from'])->toBe('2026-11-01')
        ->and($snapshot['warnings'])->toHaveCount(1)
        ->and($snapshot['issues'])->toBeEmpty();

    $this->get(route('standalone-accounting.opening-migration.index', ['as_of' => '2026-10-31']))
        ->assertOk()->assertSee('Opening Migrasi')->assertSee('2026-08-31')->assertSee('WIP');
});

it('provides default opening mappings for the current standalone chart of accounts', function () {
    expect(StandaloneAccountingAccountMapping::query()
        ->whereIn('legacy_account_code', ['6302', '4101', '6303', '1302', '6304', '6305', '1306', '6306', '2104', '1307', '6301', '2301', '1308'])
        ->pluck('standalone_account_code', 'legacy_account_code')
        ->all())
        ->toMatchArray([
            '6302' => '6-180',
            '4101' => '4-110',
            '6303' => '6-210',
            '1302' => '1-130',
            '6304' => '6-210',
            '6305' => '6-210',
            '1306' => '1-130',
            '6306' => '6-210',
            '2104' => '2-120',
            '1307' => '1-130',
            '6301' => '6-150',
            '2301' => '2-120',
            '1308' => '1-130',
        ]);
});

it('posts only the new ledger once with immutable evidence and no operational mutations', function () {
    $tables = ['journals', 'journal_lines', 'inventory_mutations', 'supplier_ap_opening_balances', 'purchase_payments', 'standalone_accounting_account_mappings'];
    $before = collect($tables)->mapWithKeys(fn ($table) => [$table => DB::table($table)->get()->toJson()]);
    $token = migrationPreview($this);
    $this->get(route('standalone-accounting.opening-migration.index', ['preview' => $token]))
        ->assertOk()->assertSee('Posting Opening Migrasi')->assertSee('Rp 150,25');

    migrationPost($this, $token)->assertRedirect(route('standalone-accounting.opening-migration.index'));
    migrationPost($this, $token)->assertRedirect();
    $migration = StandaloneOpeningMigration::findOrFail(1);
    expect(StandaloneAccountingJournal::count())->toBe(1)
        ->and($migration->posted_by)->toBe($this->owner->id)
        ->and($migration->snapshot['source_journal_ids'])->toBe([$this->source->id])
        ->and($migration->journal->lines->sum('debit'))->toEqual(150.25)
        ->and($migration->journal->lines->sum('credit'))->toEqual(150.25);
    foreach ($tables as $table) {
        expect(DB::table($table)->get()->toJson())->toBe($before[$table]);
    }
    $this->get(route('standalone-accounting.opening-migration.index'))->assertOk()->assertSee('Opening sudah diposting')->assertSee('Koreksi opening');
    $this->get(route('standalone-accounting.journals.show', $migration->journal_id))->assertOk()->assertSee('Snapshot dan koreksi opening');
});

it('rejects a second opening even from a separate preview or a different date', function () {
    $first = migrationPreview($this);
    $second = migrationPreview($this);
    migrationPost($this, $first)->assertRedirect();
    migrationPost($this, $second)->assertSessionHasErrors('migration');
    $snapshot = app(StandaloneOpeningMigrationService::class)->preview('2026-11-02', $this->mapping);
    expect(fn () => app(StandaloneOpeningMigrationService::class)->post($snapshot, (string) Str::uuid(), $this->owner->id))->toThrow(ValidationException::class);
    expect(StandaloneAccountingJournal::count())->toBe(1);
});

it('rejects posting if the source cutoff account or destination changes after preview', function (string $change) {
    $token = migrationPreview($this);
    match ($change) {
        'source' => $this->source->lines()->first()->update(['debit' => 101]),
        'cutoff' => SystemSetting::set(SystemSetting::KEY_CUTOFF_DATE, '2026-09-01'),
        'account' => StandaloneAccountingAccount::findOrFail($this->mapping[$this->legacyAccounts['1202']->id])->update(['is_active' => false]),
        'destination' => StandaloneAccountingJournal::create(['date' => '2026-10-30', 'reference' => 'CONCURRENT', 'posted_at' => now()]),
    };
    migrationPost($this, $token)->assertSessionHasErrors('migration');
    expect(StandaloneOpeningMigration::find(1)->posted_at)->toBeNull()
        ->and(StandaloneAccountingJournal::where('source_type', 'opening_migration')->exists())->toBeFalse();
})->with(['source', 'cutoff', 'account', 'destination']);

it('requires a reviewed unexpired server preview and confirmation', function () {
    migrationPost($this, (string) Str::uuid())->assertSessionHasErrors('migration');
    $token = migrationPreview($this);
    $this->post(route('standalone-accounting.opening-migration.store'), ['preview_token' => $token])->assertSessionHasErrors('confirmed');
    $this->travel(31)->minutes();
    migrationPost($this, $token)->assertSessionHasErrors('migration');
    expect(StandaloneAccountingJournal::count())->toBe(0);
});

it('shows missing mappings and imbalance and refuses a forged posting of that preview', function () {
    $this->source->lines()->first()->update(['debit' => 101]);
    $this->mapping[$this->legacyAccounts['1202']->id] = null;
    $token = migrationPreview($this);
    $this->get(route('standalone-accounting.opening-migration.index', ['preview' => $token]))
        ->assertOk()->assertSee('Saldo sumber tidak seimbang')->assertSee('Pilih akun baru aktif')->assertDontSee('Posting Opening Migrasi');
    migrationPost($this, $token)->assertSessionHasErrors('migration');
    expect(StandaloneAccountingJournal::count())->toBe(0);
});

it('can return from review to edit mapping without making the edit token postable', function () {
    $token = migrationPreview($this);
    $response = $this->post(route('standalone-accounting.opening-migration.preview'), ['as_of' => '2026-10-31', 'mapping' => $this->mapping, 'edit_mapping' => 1]);
    parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
    $this->get($response->headers->get('Location'))->assertOk()->assertSee('Tinjau mapping dan selisih')->assertDontSee('Posting Opening Migrasi');
    migrationPost($this, $query['preview'])->assertSessionHasErrors('migration');
});

it('blocks existing pre-cutover balances even if their net is zero but allows later daily journals', function () {
    $existing = StandaloneAccountingJournal::create(['date' => '2026-10-31', 'reference' => 'EXISTING', 'posted_at' => now()]);
    $service = app(StandaloneOpeningMigrationService::class);
    expect($service->preview('2026-10-31', $this->mapping)['existing_ids'])->toBe([$existing->id]);
    $existing->update(['date' => '2026-11-01']);
    expect($service->preview('2026-10-31', $this->mapping)['issues'])->toBeEmpty();
    $existing->update(['source_type' => 'opening_balance_manual']);
    expect($service->preview('2026-10-31', $this->mapping)['issues'])->not->toBeEmpty();
});

it('supports all history when there is no cutoff and rejects dates before the configured cutoff', function () {
    $this->get(route('standalone-accounting.opening-migration.index', ['as_of' => '2026-08-30']))->assertSessionHasErrors('as_of');
    SystemSetting::remove(SystemSetting::KEY_CUTOFF_DATE);
    $older = migrationSourceJournal($this->legacyAccounts, '2026-08-30');
    $snapshot = app(StandaloneOpeningMigrationService::class)->preview('2026-10-31', $this->mapping);
    expect($snapshot['source_from'])->toBeNull()->and($snapshot['source_journal_ids'])->toContain($older->id);
});

it('records balanced explained corrections separately and preserves the opening snapshot on retries', function () {
    migrationPost($this, migrationPreview($this))->assertRedirect();
    $before = StandaloneOpeningMigration::with('journal.lines')->findOrFail(1)->toArray();
    $page = $this->get(route('standalone-accounting.opening-migration.index'))->assertOk();
    $payload = [
        'request_token' => $page->viewData('correctionToken'), 'confirmed' => 1, 'date' => '2026-11-01',
        'reason' => 'Koreksi nilai WIP berdasarkan rekonsiliasi produksi REF-001.',
        'lines' => [
            ['account_id' => $this->mapping[$this->legacyAccounts['1202']->id], 'debit' => '10.25', 'credit' => '0'],
            ['account_id' => $this->mapping[$this->legacyAccounts['3101']->id], 'debit' => '0', 'credit' => '10.25'],
            ['account_id' => '', 'debit' => '', 'credit' => ''],
        ],
    ];
    $this->post(route('standalone-accounting.opening-migration.correct'), [...$payload, 'reason' => ' '])->assertSessionHasErrors('reason');
    $bad = $payload;
    $bad['lines'][0]['debit'] = '10.24';
    $this->post(route('standalone-accounting.opening-migration.correct'), $bad)->assertSessionHasErrors('lines');
    $this->post(route('standalone-accounting.opening-migration.correct'), [...$payload, 'date' => '2026-10-30'])->assertSessionHasErrors('date');
    $this->post(route('standalone-accounting.opening-migration.correct'), $payload)->assertSessionHasNoErrors()->assertRedirect();
    $this->post(route('standalone-accounting.opening-migration.correct'), $payload)->assertSessionHasNoErrors()->assertRedirect();
    expect(StandaloneOpeningMigration::with('journal.lines')->findOrFail(1)->toArray())->toBe($before)
        ->and(StandaloneAccountingJournal::count())->toBe(2)
        ->and(DB::table('standalone_opening_migration_corrections')->count())->toBe(1)
        ->and(DB::table('inventory_mutations')->count())->toBe(0);
    $this->get(route('standalone-accounting.opening-migration.index'))->assertOk()->assertSee('REF-001');
});

it('restricts opening migration actions to accounting module users', function () {
    $this->actingAs(User::factory()->create(['employee_code' => 'MIGRATION-OTHER', 'role' => 'other']));
    $this->get(route('standalone-accounting.opening-migration.index'))->assertForbidden();
    foreach (['preview', 'store', 'correct'] as $action) {
        $this->post(route('standalone-accounting.opening-migration.'.$action))->assertForbidden();
    }
});
