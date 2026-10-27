<?php

namespace App\Http\Controllers\StandaloneAccounting;

use App\Http\Controllers\Controller;
use App\Models\StandaloneAccountingAccount;
use App\Models\StandaloneAccountingJournal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class JournalController extends Controller
{
    public function index()
    {
        $journals = StandaloneAccountingJournal::query()
            ->with('lines.account')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(25);

        return view('standalone_accounting.journals.index', compact('journals'));
    }

    public function create()
    {
        $accounts = StandaloneAccountingAccount::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('standalone_accounting.journals.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:80', Rule::unique('standalone_accounting_journals', 'reference')],
            'description' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'integer', 'distinct', 'exists:standalone_accounting_accounts,id'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
        ]);

        $lines = collect($data['lines'])
            ->map(fn (array $line) => [
                'account_id' => (int) $line['account_id'],
                'debit' => round((float) ($line['debit'] ?? 0), 2),
                'credit' => round((float) ($line['credit'] ?? 0), 2),
            ])
            ->filter(fn (array $line) => $line['debit'] > 0 || $line['credit'] > 0)
            ->values();

        if ($lines->count() < 2) {
            throw ValidationException::withMessages([
                'lines' => 'Jurnal minimal memiliki dua baris bernilai.',
            ]);
        }

        if ($lines->contains(fn (array $line) => $line['debit'] > 0 && $line['credit'] > 0)) {
            throw ValidationException::withMessages([
                'lines' => 'Satu baris jurnal tidak boleh memiliki debit dan kredit sekaligus.',
            ]);
        }

        $totalDebit = round($lines->sum('debit'), 2);
        $totalCredit = round($lines->sum('credit'), 2);

        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw ValidationException::withMessages([
                'lines' => 'Jurnal belum seimbang. Total debit harus sama dengan total kredit.',
            ]);
        }

        DB::transaction(function () use ($data, $lines) {
            $journal = StandaloneAccountingJournal::create([
                'date' => $data['date'],
                'reference' => $data['reference'] ?? null,
                'description' => $data['description'] ?? null,
                'posted_at' => now(),
            ]);

            $journal->lines()->createMany($lines->all());
        });

        return to_route('standalone-accounting.journals.index')
            ->with('message', 'Jurnal akuntansi baru berhasil diposting.');
    }
}
