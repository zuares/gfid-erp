<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OpeningBalanceController extends Controller
{
    /**
     * List opening balances (aktif & void) + filter tanggal.
     * Optional filter:
     * - status=active | void
     */
    public function index(Request $request)
    {
        $q = Journal::query()
            ->where('source_type', 'opening_balance')
            ->with(['lines.account'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('from')) {
            $q->whereDate('date', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $q->whereDate('date', '<=', $request->date('to'));
        }

        if ($request->filled('status')) {
            $status = $request->string('status')->toString();
            if ($status === 'active') {
                $q->whereNull('voided_at');
            } elseif ($status === 'void') {
                $q->whereNotNull('voided_at');
            }
        }

        $openingJournals = $q->paginate(30)->withQueryString();

        return view('accounting.opening_balances.index', compact('openingJournals'));
    }

    public function create()
    {
        $cashAccounts = Account::query()
            ->where('is_cash', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $equityAccounts = Account::query()
            ->where('type', 'equity')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $defaultEquity = $equityAccounts->firstWhere('code', '3001')?->id ?? $equityAccounts->first()?->id;

        return view('accounting.opening_balances.create', compact(
            'cashAccounts',
            'equityAccounts',
            'defaultEquity'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'cash_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'equity_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        return DB::transaction(function () use ($data) {

            $cash = Account::query()->whereKey($data['cash_account_id'])->lockForUpdate()->firstOrFail();
            $equity = Account::query()->whereKey($data['equity_account_id'])->lockForUpdate()->firstOrFail();

            if (!$cash->is_cash) {
                throw ValidationException::withMessages([
                    'cash_account_id' => 'Akun yang dipilih bukan kas/bank (is_cash=1).',
                ]);
            }
            if ($equity->type !== 'equity') {
                throw ValidationException::withMessages([
                    'equity_account_id' => 'Akun lawan harus type=equity.',
                ]);
            }

            // ✅ FIX: yang dianggap "sudah ada" hanya opening balance yang masih aktif (belum di-VOID)
            $exists = Journal::query()
                ->where('source_type', 'opening_balance')
                ->where('source_id', $cash->id) // 1 akun kas/bank per tanggal
                ->whereDate('date', $data['date'])
                ->whereNull('voided_at')
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'date' => 'Opening balance aktif untuk akun kas/bank ini pada tanggal tersebut sudah ada.',
                ]);
            }

            $desc = $data['description'] ?: "Opening Balance - {$cash->name}";

            $j = Journal::create([
                'date' => $data['date'],
                'description' => $desc,
                'source_type' => 'opening_balance',
                'source_id' => $cash->id,
                'posted_at' => now(),
                'voided_at' => null,
            ]);

            JournalLine::create([
                'journal_id' => $j->id,
                'account_id' => $cash->id,
                'debit' => $data['amount'],
                'credit' => 0,
            ]);

            JournalLine::create([
                'journal_id' => $j->id,
                'account_id' => $equity->id,
                'debit' => 0,
                'credit' => $data['amount'],
            ]);

            return redirect()
                ->route('accounting.opening-balances.index')
                ->with('status', 'ok')
                ->with('message', 'Opening Balance berhasil diposting.');
        });
    }

    public function void(Request $request, Journal $journal)
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        return DB::transaction(function () use ($journal, $data) {

            /** @var Journal $locked */
            $locked = Journal::query()->whereKey($journal->id)->lockForUpdate()->firstOrFail();

            if ($locked->source_type !== 'opening_balance') {
                throw ValidationException::withMessages([
                    'journal' => 'Journal ini bukan opening balance.',
                ]);
            }

            if (!$locked->posted_at) {
                throw ValidationException::withMessages([
                    'journal' => 'Journal belum POSTED.',
                ]);
            }

            if ($locked->voided_at) {
                return redirect()
                    ->route('accounting.opening-balances.index')
                    ->with('status', 'ok')
                    ->with('message', 'Opening Balance sudah VOID.');
            }

            // anti dobel void: cek apakah sudah ada reversal yang menunjuk journal ini
            $already = Journal::query()
                ->where('source_type', 'opening_balance_void')
                ->where('source_id', $locked->id)
                ->exists();

            if ($already) {
                // kalau reversal sudah ada, pastikan opening juga ditandai void (biar konsisten)
                $locked->update(['voided_at' => now()]);

                return redirect()
                    ->route('accounting.opening-balances.index')
                    ->with('status', 'ok')
                    ->with('message', 'Opening Balance sudah pernah di-VOID.');
            }

            $locked->load('lines');

            $cashLine = $locked->lines->first(fn($l) => (float) $l->debit > 0);
            $equityLine = $locked->lines->first(fn($l) => (float) $l->credit > 0);

            if (!$cashLine || !$equityLine) {
                throw ValidationException::withMessages([
                    'journal' => 'Format opening balance tidak valid (harus 2 baris: cash debit, equity credit).',
                ]);
            }

            $amount = (float) $cashLine->debit;
            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'journal' => 'Nominal opening balance invalid.',
                ]);
            }

            $desc = 'REVERSAL: ' . ($locked->description ?: 'Opening Balance');
            if (!empty($data['reason'])) {
                $desc .= ' | ' . $data['reason'];
            }

            // ✅ tandai void di journal opening original
            $locked->update([
                'voided_at' => now(),
            ]);

            // ✅ buat reversal journal (audit trail tetap ada)
            $rev = Journal::create([
                'date' => $locked->date,
                'description' => $desc,
                'source_type' => 'opening_balance_void',
                'source_id' => $locked->id, // refer ke opening original
                'posted_at' => now(),
                'voided_at' => null,
            ]);

            // Reversal: Debit Equity, Credit Cash
            JournalLine::create([
                'journal_id' => $rev->id,
                'account_id' => $equityLine->account_id,
                'debit' => $amount,
                'credit' => 0,
            ]);

            JournalLine::create([
                'journal_id' => $rev->id,
                'account_id' => $cashLine->account_id,
                'debit' => 0,
                'credit' => $amount,
            ]);

            return redirect()
                ->route('accounting.opening-balances.index')
                ->with('status', 'ok')
                ->with('message', 'Opening Balance berhasil di-VOID (reversal journal dibuat).');
        });
    }
}
