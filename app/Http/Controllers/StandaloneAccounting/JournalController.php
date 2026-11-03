<?php

namespace App\Http\Controllers\StandaloneAccounting;

use App\Http\Controllers\Controller;
use App\Models\InventoryAdjustment;
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

        // Ringkasan qty reject untuk jurnal hasil QC Cutting. Nilai diambil
        // dari mutasi stok aktual agar UI tidak menebak jumlah pcs dari nilai
        // jurnal. Source cutting_wip memakai source_id = cutting_job_id.
        $cuttingWipJobIds = $journals->getCollection()
            ->filter(fn ($journal): bool => $journal->source_type === 'cutting_wip')
            ->pluck('source_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $cuttingRejectQtyByJob = $cuttingWipJobIds->isEmpty()
            ? collect()
            : DB::table('inventory_mutations')
                ->where('source_type', 'cutting_reject')
                ->whereIn('source_id', $cuttingWipJobIds->all())
                ->selectRaw('source_id, SUM(qty_change) as qty')
                ->groupBy('source_id')
                ->pluck('qty', 'source_id');

        // Reject susulan memakai source_id = sewing_pickup_line_id.
        $sewingRejectLineIds = $journals->getCollection()
            ->filter(fn ($journal): bool => in_array(
                $journal->source_type,
                ['sewing_pickup_pre_sewing_reject', 'sewing_pickup_reject'],
                true,
            ))
            ->pluck('source_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $sewingRejectQtyByLine = $sewingRejectLineIds->isEmpty()
            ? collect()
            : DB::table('sewing_pickup_lines')
                ->whereIn('id', $sewingRejectLineIds->all())
                ->pluck('qty_rejected', 'id');

        return view('standalone_accounting.journals.index', compact(
            'journals',
            'cuttingRejectQtyByJob',
            'sewingRejectQtyByLine',
        ));
    }

    public function create(Request $request)
    {
        $accounts = StandaloneAccountingAccount::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $mode = $request->string('mode')->toString() === 'opening' ? 'opening' : 'general';
        $openingEquityAccount = $accounts->firstWhere('code', '3-110');
        $openingReference = $mode === 'opening' ? $this->nextOpeningReference() : null;

        return view('standalone_accounting.journals.create', compact(
            'accounts',
            'mode',
            'openingEquityAccount',
            'openingReference',
        ));
    }

    private function nextOpeningReference(): string
    {
        $year = now()->year;
        $prefix = "OB-{$year}-";
        $nextNumber = StandaloneAccountingJournal::query()
            ->where('reference', 'like', "{$prefix}%")
            ->pluck('reference')
            ->map(function (?string $reference) use ($prefix): int {
                $suffix = str_starts_with($reference ?? '', $prefix)
                    ? substr($reference, strlen($prefix))
                    : '';

                return ctype_digit($suffix) ? (int) $suffix : 0;
            })
            ->max() + 1;

        do {
            $reference = $prefix.str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (StandaloneAccountingJournal::query()->where('reference', $reference)->exists());

        return $reference;
    }

    public function show(StandaloneAccountingJournal $journal)
    {
        $journal->load('lines.account');

        $sourceAdjustment = null;
        $sourceStockOpname = null;
        if ($journal->source_type === 'inventory_adjustment' && $journal->source_id) {
            $sourceAdjustment = InventoryAdjustment::query()
                ->with('source')
                ->find($journal->source_id);
            $sourceStockOpname = $sourceAdjustment?->source;
        }

        return view('standalone_accounting.journals.show', compact(
            'journal',
            'sourceAdjustment',
            'sourceStockOpname',
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'mode' => ['nullable', Rule::in(['general', 'opening'])],
            'date' => ['required', 'date'],
            'reference' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('standalone_accounting_journals', 'reference'),
                Rule::requiredIf(fn () => $request->input('mode') === 'opening'),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['nullable', 'integer', 'distinct', 'exists:standalone_accounting_accounts,id'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
        ]);

        $invalidAccountLine = collect($data['lines'])->contains(
            fn (array $line): bool => empty($line['account_id'])
                && ((float) ($line['debit'] ?? 0) > 0 || (float) ($line['credit'] ?? 0) > 0)
        );

        if ($invalidAccountLine) {
            throw ValidationException::withMessages([
                'lines' => 'Setiap baris yang memiliki nominal wajib memilih akun.',
            ]);
        }

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

        $mode = $data['mode'] ?? 'general';
        $sourceType = $mode === 'opening' ? 'opening_balance_manual' : null;

        if ($mode === 'opening') {
            $openingEquity = StandaloneAccountingAccount::query()
                ->where('code', '3-110')
                ->where('is_active', true)
                ->first();

            if (! $openingEquity) {
                throw ValidationException::withMessages([
                    'lines' => 'Akun 3-110 Modal Pemilik belum tersedia atau tidak aktif.',
                ]);
            }

            $hasOwnerCapitalCredit = $lines->contains(
                fn (array $line): bool => (int) $line['account_id'] === (int) $openingEquity->id
                    && $line['credit'] > 0
                    && $line['debit'] <= 0
            );

            if (! $hasOwnerCapitalCredit) {
                throw ValidationException::withMessages([
                    'lines' => 'Jurnal saldo awal harus memiliki kredit ke akun 3-110 Modal Pemilik.',
                ]);
            }

            $inventoryCodes = [
                '1-150', '1-151', '1-151-01', '1-151-02', '1-151-03', '1-152', '1-153',
            ];
            $inventoryAccount = StandaloneAccountingAccount::query()
                ->whereIn('id', $lines->pluck('account_id')->all())
                ->get()
                ->first(fn ($account) => in_array($account->code, $inventoryCodes, true));

            if ($inventoryAccount) {
                throw ValidationException::withMessages([
                    'lines' => 'Persediaan awal harus diinput melalui Opening Stock Opname agar tidak tercatat dua kali.',
                ]);
            }
        }

        DB::transaction(function () use ($data, $lines, $sourceType) {
            $journal = StandaloneAccountingJournal::create([
                'date' => $data['date'],
                'reference' => $data['reference'] ?? null,
                'source_type' => $sourceType,
                'source_id' => null,
                'description' => $data['description'] ?? null,
                'posted_at' => now(),
            ]);

            $journal->lines()->createMany($lines->all());
        });

        return to_route('standalone-accounting.journals.index')
            ->with('message', 'Jurnal akuntansi baru berhasil diposting.');
    }

    public function void(Request $request, StandaloneAccountingJournal $journal)
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        return DB::transaction(function () use ($journal, $data) {
            $locked = StandaloneAccountingJournal::query()
                ->whereKey($journal->id)
                ->lockForUpdate()
                ->firstOrFail();

            $isOpeningJournal = $locked->source_type === 'opening_balance_manual'
                || $locked->reference === 'OB-UMKM-DUMMY-001';

            if (! $isOpeningJournal) {
                throw ValidationException::withMessages([
                    'journal' => 'Hanya jurnal saldo awal manual yang dapat di-VOID dari halaman ini.',
                ]);
            }

            if (! $locked->posted_at) {
                throw ValidationException::withMessages([
                    'journal' => 'Jurnal belum POSTED.',
                ]);
            }

            if ($locked->voided_at) {
                return to_route('standalone-accounting.journals.show', $locked)
                    ->with('message', 'Jurnal saldo awal sudah VOID.');
            }

            $already = StandaloneAccountingJournal::query()
                ->where('source_type', 'opening_balance_manual_reversal')
                ->where('source_id', $locked->id)
                ->exists();

            if ($already) {
                $locked->forceFill(['voided_at' => now()])->save();

                return to_route('standalone-accounting.journals.show', $locked)
                    ->with('message', 'Jurnal saldo awal sudah pernah di-VOID.');
            }

            $locked->load('lines');
            if ($locked->lines->isEmpty()) {
                throw ValidationException::withMessages([
                    'journal' => 'Jurnal saldo awal tidak memiliki baris.',
                ]);
            }

            $locked->forceFill(['voided_at' => now()])->save();

            $description = 'REVERSAL: '.($locked->description ?: 'Saldo Awal');
            if (! empty($data['reason'])) {
                $description .= ' | '.$data['reason'];
            }

            $reversal = StandaloneAccountingJournal::create([
                'date' => $locked->date,
                'reference' => ($locked->reference ?: 'SA-'.$locked->id).'-REV-'.$locked->id,
                'source_type' => 'opening_balance_manual_reversal',
                'source_id' => $locked->id,
                'description' => $description,
                'posted_at' => now(),
                'voided_at' => now(),
            ]);

            $reversal->lines()->createMany(
                $locked->lines->map(fn ($line): array => [
                    'account_id' => (int) $line->account_id,
                    'debit' => (float) $line->credit,
                    'credit' => (float) $line->debit,
                ])->all(),
            );

            return to_route('standalone-accounting.journals.show', $locked)
                ->with('message', 'Saldo awal berhasil di-VOID. Reversal tersimpan untuk audit.');
        });
    }
}
