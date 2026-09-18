<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountRenameTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_list_links_to_a_working_name_edit_form(): void
    {
        $account = $this->account();
        $this->actingAs(User::factory()->create(['role' => 'owner', 'employee_code' => 'OWNER-RENAME']));

        $this->get(route('accounting.accounts.index'))
            ->assertOk()
            ->assertDontSee('Edit nama');

        $this->get(route('accounting.accounts.show', $account))
            ->assertOk()
            ->assertSee('Edit nama');

        $this->get(route('accounting.accounts.edit', $account))
            ->assertOk()
            ->assertSee('Edit Akun')
            ->assertSee('value="Bank Lama"', false)
            ->assertSee('name="code"', false)
            ->assertSee('Simpan perubahan');
    }

    public function test_rename_preserves_account_identity_flags_and_posted_journal(): void
    {
        $account = $this->account();
        $originalAccount = $account->fresh()->getAttributes();
        $journal = Journal::create([
            'date' => '2026-09-10',
            'description' => 'Saldo bank',
            'source_type' => 'opening_balance',
            'posted_at' => now(),
        ]);
        $line = JournalLine::create([
            'journal_id' => $journal->id,
            'account_id' => $account->id,
            'debit' => 150000,
            'credit' => 0,
        ]);
        $originalJournal = $journal->fresh()->getAttributes();
        $originalLine = $line->fresh()->getAttributes();

        $this->actingAs(User::factory()->create(['role' => 'owner', 'employee_code' => 'OWNER-RENAME']))
            ->put(route('accounting.accounts.update', $account), [
                'name' => '  Bank Operasional  ',
                'code' => 'TEST-BANK',
                'type' => 'asset',
                'is_cash' => true,
                'is_active' => false,
            ])
            ->assertRedirect(route('accounting.accounts.index'))
            ->assertSessionHas('success', 'Detail akun berhasil diperbarui.');

        $account->refresh();
        $this->assertSame('Bank Operasional', $account->name);
        foreach (['id', 'code', 'type', 'is_cash', 'is_active'] as $field) {
            $this->assertSame($originalAccount[$field], $account->getAttributes()[$field]);
        }
        $this->assertSame($originalJournal, $journal->fresh()->getAttributes());
        $this->assertSame($originalLine, $line->fresh()->getAttributes());
        $this->get(route('accounting.accounts.index'))->assertOk()->assertSee('Bank Operasional');
        $this->get(route('accounting.accounts.ledger', $account))->assertOk()->assertSee('Bank Operasional');
    }

    public function test_invalid_names_are_rejected_and_input_is_kept(): void
    {
        $account = $this->account();
        $this->actingAs(User::factory()->create(['role' => 'owner', 'employee_code' => 'OWNER-RENAME']));

        foreach (['', '   ', str_repeat('a', 256)] as $name) {
            $this->from(route('accounting.accounts.edit', $account))
                ->put(route('accounting.accounts.update', $account), [
                    'code' => 'TEST-BANK',
                    'name' => $name,
                    'type' => 'asset',
                    'is_cash' => true,
                    'is_active' => false,
                ])
                ->assertRedirect(route('accounting.accounts.edit', $account))
                ->assertSessionHasErrors('name');
            $this->assertSame('Bank Lama', $account->fresh()->name);
        }

        $this->get(route('accounting.accounts.edit', $account))
            ->assertOk()
            ->assertSee('Nama akun maksimal 255 karakter.')
            ->assertSee('value="'.str_repeat('a', 256).'"', false);
    }

    public function test_code_and_account_details_can_be_changed_before_first_transaction(): void
    {
        $account = $this->account();

        $this->actingAs(User::factory()->create(['role' => 'owner', 'employee_code' => 'OWNER-RENAME']))
            ->put(route('accounting.accounts.update', $account), [
                'code' => '1113',
                'name' => 'SEABANK Operasional',
                'type' => 'asset',
                'is_cash' => true,
                'is_active' => true,
            ])
            ->assertRedirect(route('accounting.accounts.index'));

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'code' => '1113',
            'name' => 'SEABANK Operasional',
            'type' => 'asset',
            'is_cash' => 1,
            'is_active' => 1,
        ]);
    }

    public function test_code_and_type_cannot_be_changed_after_first_transaction(): void
    {
        $account = $this->account();
        $journal = Journal::create([
            'date' => '2026-09-10',
            'description' => 'Saldo bank',
            'source_type' => 'opening_balance',
            'posted_at' => now(),
        ]);
        JournalLine::create([
            'journal_id' => $journal->id,
            'account_id' => $account->id,
            'debit' => 1,
            'credit' => 0,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'owner', 'employee_code' => 'OWNER-RENAME']))
            ->from(route('accounting.accounts.edit', $account))
            ->put(route('accounting.accounts.update', $account), [
                'code' => '1113',
                'name' => 'Bank Baru',
                'type' => 'expense',
                'is_cash' => false,
                'is_active' => true,
            ])
            ->assertRedirect(route('accounting.accounts.edit', $account))
            ->assertSessionHasErrors('code');

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'code' => 'TEST-BANK',
            'name' => 'Bank Lama',
            'type' => 'asset',
            'is_cash' => 1,
        ]);
    }

    public function test_user_without_accounting_access_cannot_rename_accounts(): void
    {
        $account = $this->account();

        $this->actingAs(User::factory()->create(['role' => 'admin', 'employee_code' => 'ADMIN-RENAME']))
            ->put(route('accounting.accounts.update', $account), ['name' => 'Tidak diizinkan'])
            ->assertForbidden();

        $this->assertSame('Bank Lama', $account->fresh()->name);
    }

    private function account(): Account
    {
        return Account::create([
            'code' => 'TEST-BANK',
            'name' => 'Bank Lama',
            'type' => 'asset',
            'is_cash' => true,
            'is_active' => false,
        ]);
    }
}
