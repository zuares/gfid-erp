<?php

use App\Models\StandaloneAccountingAccount;
use App\Models\StandaloneAccountingAccountMapping;
use App\Models\StandaloneAccountingJournal;
use App\Models\User;
use Database\Seeders\StandaloneDummyOpeningBalanceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('keeps the new accounting module isolated and posts a balanced journal', function () {
    $user = User::factory()->create(['employee_code' => 'STANDALONE-OWNER-1', 'role' => 'owner']);
    $initialJournalCount = StandaloneAccountingJournal::count();

    $response = $this->actingAs($user)->post(route('standalone-accounting.journals.store'), [
        'date' => '2026-01-01',
        'reference' => 'OPEN-001',
        'description' => 'Saldo awal kas',
        'lines' => [
            ['account_id' => StandaloneAccountingAccount::where('code', '1-120')->value('id'), 'debit' => '1000000', 'credit' => '0'],
            ['account_id' => StandaloneAccountingAccount::where('code', '3-110')->value('id'), 'debit' => '0', 'credit' => '1000000'],
        ],
    ]);

    $response
        ->assertRedirect(route('standalone-accounting.journals.index'))
        ->assertSessionHas('message');

    expect(StandaloneAccountingJournal::count())->toBe($initialJournalCount + 1)
        ->and(StandaloneAccountingJournal::latest('id')->first()->lines()->count())->toBe(2);

    $this->actingAs($user)
        ->get(route('standalone-accounting.trial-balance.index', ['as_of' => '2026-01-01']))
        ->assertOk()
        ->assertSee('Akuntansi Baru')
        ->assertSee('Uang tunai yang disediakan untuk pengeluaran operasional kecil dan rutin.')
        ->assertSee('Neraca saldo seimbang')
        ->assertSee('Kas');
});

it('rejects an unbalanced journal', function () {
    $user = User::factory()->create(['employee_code' => 'STANDALONE-OWNER-2', 'role' => 'owner']);
    $initialJournalCount = StandaloneAccountingJournal::count();

    $response = $this->actingAs($user)->from(route('standalone-accounting.journals.create'))->post(route('standalone-accounting.journals.store'), [
        'date' => '2026-01-01',
        'lines' => [
            ['account_id' => StandaloneAccountingAccount::where('code', '1000')->value('id'), 'debit' => '1000000', 'credit' => '0'],
            ['account_id' => StandaloneAccountingAccount::where('code', '3000')->value('id'), 'debit' => '0', 'credit' => '900000'],
        ],
    ]);

    $response->assertRedirect(route('standalone-accounting.journals.create'))
        ->assertSessionHasErrors('lines');

    expect(StandaloneAccountingJournal::count())->toBe($initialJournalCount);
});

it('seeds a balanced dummy opening balance explicitly without duplicates', function () {
    $this->seed(StandaloneDummyOpeningBalanceSeeder::class);
    $this->seed(StandaloneDummyOpeningBalanceSeeder::class);

    expect(StandaloneAccountingJournal::count())->toBe(1);

    $journal = StandaloneAccountingJournal::query()
        ->where('reference', 'OB-UMKM-DUMMY-001')
        ->with('lines.account')
        ->first();

    expect($journal)->not->toBeNull()
        ->and($journal->date->toDateString())->toBe('2026-01-01')
        ->and($journal->description)->toContain('dummy skala UMKM')
        ->and((float) $journal->lines->sum('debit'))->toBe(98_000_000.0)
        ->and((float) $journal->lines->sum('credit'))->toBe(98_000_000.0)
        ->and($journal->lines->count())->toBe(13)
        ->and($journal->lines->firstWhere('account.code', '1-110')->debit)->toBe('25000000.00');
});

it('keeps the standalone chart of accounts isolated from legacy accounting', function () {
    $user = User::factory()->create(['employee_code' => 'STANDALONE-OWNER-3', 'role' => 'owner']);

    $this->actingAs($user)
        ->get(route('standalone-accounting.accounts.index'))
        ->assertOk()
        ->assertSee('Kas')
        ->assertSee('Bank');

    // Includes three WIP detail accounts and dedicated employee loan/savings accounts.
    expect(StandaloneAccountingAccount::count())->toBe(54)
        ->and(StandaloneAccountingAccount::whereIn('code', ['1101', '1111', '1112', '1113', '1114'])->count())->toBe(0);
});

it('creates the initial equity accounts without an opening journal', function () {
    expect(StandaloneAccountingAccount::where('code', '3-110')->value('type'))->toBe('equity')
        ->and(StandaloneAccountingAccount::where('code', '3-110')->value('description'))->toContain('Modal yang disetor')
        ->and(StandaloneAccountingAccount::where('code', '3-130')->value('description'))->toContain('Akumulasi laba')
        ->and(StandaloneAccountingAccount::where('code', '3-150')->value('description'))->toContain('Akun sementara')
        ->and(StandaloneAccountingJournal::count())->toBe(0);
});

it('creates the sales accounts with clear contra-revenue descriptions', function () {
    expect(StandaloneAccountingAccount::where('code', '4-110')->value('type'))->toBe('revenue')
        ->and(StandaloneAccountingAccount::where('code', '4-110')->value('description'))->toContain('Pendapatan kotor')
        ->and(StandaloneAccountingAccount::where('code', '4-120')->value('description'))->toContain('Pengurang pendapatan')
        ->and(StandaloneAccountingAccount::where('code', '4-130')->value('description'))->toContain('barang dikembalikan')
        ->and(StandaloneAccountingJournal::count())->toBe(0);
});

it('creates the cost of goods sold account with a production-focused description', function () {
    expect(StandaloneAccountingAccount::where('code', '5-110')->value('type'))->toBe('expense')
        ->and(StandaloneAccountingAccount::where('code', '5-110')->value('name'))->toContain('Harga Pokok Penjualan')
        ->and(StandaloneAccountingAccount::where('code', '5-110')->value('description'))->toContain('barang yang sudah terjual')
        ->and(StandaloneAccountingJournal::count())->toBe(0);
});

it('creates operating expense and interest income accounts with clear classifications', function () {
    expect(StandaloneAccountingAccount::where('code', '6-110')->value('type'))->toBe('expense')
        ->and(StandaloneAccountingAccount::where('code', '6-150')->value('description'))->toContain('penyusutan aset tetap')
        ->and(StandaloneAccountingAccount::where('code', '6-210')->value('description'))->toContain('non-operasional')
        ->and(StandaloneAccountingAccount::where('code', '8-110')->value('type'))->toBe('revenue')
        ->and(StandaloneAccountingAccount::where('code', '8-110')->value('description'))->toContain('Pendapatan bunga')
        ->and(StandaloneAccountingJournal::count())->toBe(0);
});

it('creates initial asset accounts with clear descriptions and contra-asset notes', function () {
    expect(StandaloneAccountingAccount::where('code', '1-110')->value('type'))->toBe('asset')
        ->and(StandaloneAccountingAccount::where('code', '1-140')->value('description'))->toContain('kontra-aset')
        ->and(StandaloneAccountingAccount::where('code', '1-150')->value('description'))->toContain('bahan baku dan material produksi')
        ->and(StandaloneAccountingAccount::where('code', '1-321')->value('description'))->toContain('penyusutan bangunan')
        ->and(StandaloneAccountingAccount::where('code', '1-331')->value('description'))->toContain('penyusutan peralatan')
        ->and(StandaloneAccountingAccount::where('code', '1-180')->value('description'))->toContain('Uang yang sudah dibayarkan kepada supplier')
        ->and(StandaloneAccountingAccount::where('code', '1-191')->value('type'))->toBe('asset')
        ->and(StandaloneAccountingAccount::where('code', '1-191')->value('description'))->toContain('PPN masukan')
        ->and(StandaloneAccountingAccount::whereIn('code', ['1-190', '1-340'])->count())->toBe(0)
        ->and(StandaloneAccountingJournal::count())->toBe(0);
});

it('creates detailed short-term liability accounts with clear descriptions', function () {
    expect(StandaloneAccountingAccount::where('code', '2-110')->value('type'))->toBe('liability')
        ->and(StandaloneAccountingAccount::where('code', '2-110')->value('description'))->toContain('supplier')
        ->and(StandaloneAccountingAccount::where('code', '2-120')->value('description'))->toContain('listrik')
        ->and(StandaloneAccountingAccount::where('code', '2-130')->value('description'))->toContain('pekerjaan produksi')
        ->and(StandaloneAccountingJournal::count())->toBe(0);
});

it('creates the production chart of accounts and explicit legacy mappings', function () {
    expect(StandaloneAccountingAccount::whereIn('code', ['1-150', '1-151', '1-152', '1-153', '2-130', '5-110', '6-140'])->count())->toBe(7)
        ->and(StandaloneAccountingAccount::where('code', '1-151')->value('type'))->toBe('asset')
        ->and(StandaloneAccountingAccount::where('code', '1-152')->value('type'))->toBe('asset')
        ->and(StandaloneAccountingAccount::where('code', '1-153')->value('type'))->toBe('asset');

    foreach ([
        '1201' => '1-150',
        '1202' => '1-151',
        '1203' => '1-152',
        '1204' => '1-153',
        '2102' => '2-130',
        '5101' => '5-110',
        '6120' => '6-140',
    ] as $legacyCode => $standaloneCode) {
        expect(StandaloneAccountingAccountMapping::where('legacy_account_code', $legacyCode)->value('standalone_account_code'))
            ->toBe($standaloneCode);
    }
});

it('keeps other expenses in the 6-2xx account group', function () {
    expect(StandaloneAccountingAccount::where('code', '6-210')->value('name'))->toContain('Beban Lain-lain')
        ->and(StandaloneAccountingAccount::where('code', '6-210')->value('description'))->toContain('non-operasional')
        ->and(StandaloneAccountingAccount::where('code', '6-200')->exists())->toBeFalse();
});

it('shows the standalone journal menu and its transaction modules', function () {
    $user = User::factory()->create(['employee_code' => 'STANDALONE-OWNER-4', 'role' => 'owner']);

    $this->actingAs($user)
        ->get(route('standalone-accounting.journal-menu.index'))
        ->assertOk()
        ->assertSee('Menu Jurnal')
        ->assertSee('Jurnal Pembelian')
        ->assertSee('Jurnal Penjualan')
        ->assertSee('Penerimaan Kas')
        ->assertSee('Pengeluaran Kas')
        ->assertSee('Kas Kecil')
        ->assertSee('Inventory Card / Kartu Persediaan')
        ->assertSee('Buku Besar Pembantu')
        ->assertSee('Neraca Saldo Periode Akhir');

    $this->actingAs($user)
        ->get(route('standalone-accounting.journal-menu.show', 'pembelian'))
        ->assertOk()
        ->assertSee('Jurnal Pembelian')
        ->assertSee('Buat Jurnal Umum');
});

it('shows the new accounting sidebar for admin, owner, and developer users', function () {
    $admin = User::factory()->create(['employee_code' => 'STANDALONE-ADMIN-1', 'role' => 'admin']);
    $owner = User::factory()->create(['employee_code' => 'STANDALONE-OWNER-5', 'role' => 'owner']);
    $developer = User::factory()->create([
        'employee_code' => 'STANDALONE-DEV-6',
        'role' => 'admin',
        'is_developer' => true,
    ]);
    $menuUrl = route('standalone-accounting.journal-menu.index');

    $this->actingAs($admin)
        ->get($menuUrl)
        ->assertOk()
        ->assertSee('href="'.$menuUrl.'"', false);

    $this->actingAs($owner)
        ->get($menuUrl)
        ->assertOk()
        ->assertSee('href="'.$menuUrl.'"', false);

    $this->actingAs($developer)
        ->get($menuUrl)
        ->assertOk()
        ->assertSee('href="'.$menuUrl.'"', false);
});
