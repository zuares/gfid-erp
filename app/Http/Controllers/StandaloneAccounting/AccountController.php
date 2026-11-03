<?php

namespace App\Http\Controllers\StandaloneAccounting;

use App\Http\Controllers\Controller;
use App\Models\StandaloneAccountingAccount;
use App\Models\StandaloneAccountingJournalLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $typeLabel = [
            'asset' => 'Aset',
            'liability' => 'Liabilitas',
            'equity' => 'Ekuitas',
            'revenue' => 'Pendapatan',
            'expense' => 'Beban',
        ];

        $allAccounts = StandaloneAccountingAccount::query()
            ->withCount('lines')
            ->orderBy('code')
            ->get();

        $balances = DB::table('standalone_accounting_journal_lines as line')
            ->join('standalone_accounting_journals as journal', 'journal.id', '=', 'line.journal_id')
            ->whereNotNull('journal.posted_at')
            ->whereNull('journal.voided_at')
            ->whereDate('journal.date', '<=', now()->toDateString())
            ->groupBy('line.account_id')
            ->selectRaw('line.account_id, COALESCE(SUM(line.debit), 0) AS total_debit, COALESCE(SUM(line.credit), 0) AS total_credit')
            ->get()
            ->keyBy('account_id');

        $allAccounts = $allAccounts->map(function ($account) use ($balances) {
            $balance = $balances->get($account->id);
            $net = round((float) ($balance->total_debit ?? 0) - (float) ($balance->total_credit ?? 0), 2);

            $account->balance_amount = abs($net);
            $account->balance_side = $net > 0 ? 'Debit' : ($net < 0 ? 'Kredit' : null);

            return $account;
        });

        $search = trim((string) $request->input('search', ''));
        $type = (string) $request->input('type', '');
        $status = (string) $request->input('status', '');

        $accounts = $allAccounts
            ->when($search !== '', fn ($accounts) => $accounts->filter(function ($account) use ($search) {
                $needle = mb_strtolower($search);

                return str_contains(mb_strtolower($account->code), $needle)
                    || str_contains(mb_strtolower($account->name), $needle)
                    || str_contains(mb_strtolower((string) $account->description), $needle);
            }))
            ->when(array_key_exists($type, $typeLabel), fn ($accounts) => $accounts->where('type', $type))
            ->when(in_array($status, ['active', 'inactive'], true), fn ($accounts) => $accounts->where('is_active', $status === 'active'))
            ->values();

        return view('standalone_accounting.accounts.index', [
            'accounts' => $accounts,
            'totalAccounts' => $allAccounts->count(),
            'activeAccounts' => $allAccounts->where('is_active', true)->count(),
            'inactiveAccounts' => $allAccounts->where('is_active', false)->count(),
            'usedAccounts' => $allAccounts->where('lines_count', '>', 0)->count(),
            'typeCounts' => $allAccounts->countBy('type'),
            'typeLabel' => $typeLabel,
            'filters' => compact('search', 'type', 'status'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('standalone_accounting_accounts', 'code')],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', Rule::in(['asset', 'liability', 'equity', 'revenue', 'expense'])],
        ]);

        StandaloneAccountingAccount::create($data);

        return to_route('standalone-accounting.accounts.index')
            ->with('message', 'Akun akuntansi baru berhasil ditambahkan.');
    }

    public function show(StandaloneAccountingAccount $account)
    {
        $account->loadCount('lines');
        $summary = $this->summaryFor($account->id);
        $recentLines = $this->postedLinesFor($account->id)
            ->orderByDesc('journal.date')
            ->orderByDesc('standalone_accounting_journal_lines.id')
            ->limit(10)
            ->get();
        $auditLines = $this->voidedLinesFor($account->id)
            ->orderByDesc('journal.date')
            ->orderByDesc('standalone_accounting_journal_lines.id')
            ->limit(20)
            ->get();
        $recentLineContext = $this->productionContextForLines($recentLines, (string) $account->code);
        $auditLineContext = $this->productionContextForLines($auditLines, (string) $account->code);

        return view('standalone_accounting.accounts.show', [
            'account' => $account,
            'summary' => $summary,
            'recentLines' => $recentLines,
            'auditLines' => $auditLines,
            'recentLineContext' => $recentLineContext,
            'auditLineContext' => $auditLineContext,
        ]);
    }

    public function ledger(Request $request, StandaloneAccountingAccount $account)
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = $this->postedLinesFor($account->id)
            ->orderBy('journal.date')
            ->orderBy('journal.id')
            ->orderBy('standalone_accounting_journal_lines.id');

        if (! empty($data['from'])) {
            $query->whereDate('journal.date', '>=', $data['from']);
        }

        if (! empty($data['to'])) {
            $query->whereDate('journal.date', '<=', $data['to']);
        }

        $runningBalance = 0;
        $lines = $query->get()->map(function (StandaloneAccountingJournalLine $line) use (&$runningBalance) {
            $runningBalance = round($runningBalance + (float) $line->debit - (float) $line->credit, 2);
            $line->running_balance = abs($runningBalance);
            $line->running_balance_side = $runningBalance > 0 ? 'Debit' : ($runningBalance < 0 ? 'Kredit' : null);

            return $line;
        });
        $lineContext = $this->productionContextForLines($lines, (string) $account->code);

        return view('standalone_accounting.accounts.ledger', [
            'account' => $account,
            'lines' => $lines,
            'lineContext' => $lineContext,
            'filters' => [
                'from' => $data['from'] ?? '',
                'to' => $data['to'] ?? '',
            ],
            'endingBalance' => abs($runningBalance),
            'endingBalanceSide' => $runningBalance > 0 ? 'Debit' : ($runningBalance < 0 ? 'Kredit' : null),
        ]);
    }

    private function summaryFor(int $accountId): array
    {
        $summary = DB::table('standalone_accounting_journal_lines as line')
            ->join('standalone_accounting_journals as journal', 'journal.id', '=', 'line.journal_id')
            ->where('line.account_id', $accountId)
            ->whereNotNull('journal.posted_at')
            ->whereNull('journal.voided_at')
            ->selectRaw('COALESCE(SUM(line.debit), 0) AS total_debit, COALESCE(SUM(line.credit), 0) AS total_credit')
            ->first();

        $totalDebit = round((float) ($summary->total_debit ?? 0), 2);
        $totalCredit = round((float) ($summary->total_credit ?? 0), 2);
        $net = round($totalDebit - $totalCredit, 2);

        return [
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'balanceAmount' => abs($net),
            'balanceSide' => $net > 0 ? 'Debit' : ($net < 0 ? 'Kredit' : null),
        ];
    }

    private function postedLinesFor(int $accountId)
    {
        return StandaloneAccountingJournalLine::query()
            ->select('standalone_accounting_journal_lines.*')
            ->with('journal')
            ->join('standalone_accounting_journals as journal', 'journal.id', '=', 'standalone_accounting_journal_lines.journal_id')
            ->where('standalone_accounting_journal_lines.account_id', $accountId)
            ->whereNotNull('journal.posted_at')
            ->whereNull('journal.voided_at');
    }

    private function voidedLinesFor(int $accountId)
    {
        return StandaloneAccountingJournalLine::query()
            ->select('standalone_accounting_journal_lines.*')
            ->with('journal')
            ->join('standalone_accounting_journals as journal', 'journal.id', '=', 'standalone_accounting_journal_lines.journal_id')
            ->where('standalone_accounting_journal_lines.account_id', $accountId)
            ->whereNotNull('journal.posted_at')
            ->whereNotNull('journal.voided_at');
    }

    /**
     * Resolve production dimensions for an account line without changing the
     * accounting ledger. Inventory mutations are the source of truth for qty
     * and item code; wage-only journals fall back to their production line.
     *
     * @param  iterable<int, StandaloneAccountingJournalLine>  $lines
     * @return array<int, array{item_code: string, qty: float, uom: string, items?: array<int, array{item_code: string, qty: float, uom: string, value: float}>}>
     */
    private function productionContextForLines(iterable $lines, string $accountCode): array
    {
        $contexts = [];

        foreach ($lines as $line) {
            $journal = $line->journal;
            $sourceType = (string) ($journal?->source_type ?? '');
            $sourceId = (int) ($journal?->source_id ?? 0);

            if ($sourceType === '' || $sourceId <= 0) {
                continue;
            }

            $mutationTypes = [$sourceType];
            if ($sourceType === 'cutting_wip' && $accountCode === '1-153') {
                // Reject menjadi komponen terpisah pada jurnal cutting_wip.
                $mutationTypes = ['cutting_reject'];
            } elseif ($sourceType === 'shipment_cogs' || $sourceType === 'shipment_wave_cogs') {
                // Jurnal HPP memakai source_type berbeda dari mutasi shipment.
                $mutationTypes = ['shipment'];
            }

            $mutationRows = DB::table('inventory_mutations as mutation')
                ->leftJoin('items as item', 'item.id', '=', 'mutation.item_id')
                ->whereIn('mutation.source_type', $mutationTypes)
                ->where('mutation.source_id', $sourceId)
                ->get([
                    'item.code as item_code',
                    'item.unit as uom',
                    'mutation.item_id',
                    'mutation.qty_change',
                    'mutation.total_cost',
                ]);

            if ($mutationRows->isNotEmpty()) {
                $groups = $mutationRows
                    ->groupBy(fn ($row): string => (string) ($row->item_code ?: '#'.$row->item_id))
                    ->map(function ($rows): array {
                        $incoming = $rows->filter(fn ($row): bool => (float) $row->qty_change > 0)
                            ->sum(fn ($row): float => (float) $row->qty_change);
                        $outgoing = $rows->filter(fn ($row): bool => (float) $row->qty_change < 0)
                            ->sum(fn ($row): float => abs((float) $row->qty_change));
                        $incomingValue = $rows->filter(fn ($row): bool => (float) $row->qty_change > 0)
                            ->sum(fn ($row): float => abs((float) ($row->total_cost ?? 0)));
                        $outgoingValue = $rows->filter(fn ($row): bool => (float) $row->qty_change < 0)
                            ->sum(fn ($row): float => abs((float) ($row->total_cost ?? 0)));

                        return [
                            'qty' => max($incoming, $outgoing),
                            'uom' => $rows->pluck('uom')->filter()->unique()->implode(', ') ?: 'unit',
                            'value' => round(max($incomingValue, $outgoingValue), 2),
                        ];
                    });

                $contexts[$line->id] = [
                    'item_code' => $groups->keys()->implode(', '),
                    'qty' => (float) $groups->sum('qty'),
                    'uom' => $groups->pluck('uom')->filter()->unique()->implode(', ') ?: 'unit',
                    'items' => $groups->map(fn (array $group, $itemCode): array => [
                        'item_code' => (string) $itemCode,
                        'qty' => (float) $group['qty'],
                        'uom' => (string) $group['uom'],
                        'value' => (float) $group['value'],
                    ])->values()->all(),
                ];

                continue;
            }

            if (in_array($sourceType, ['sewing_pickup_wage', 'sewing_pickup_line_void'], true)) {
                $pickupLine = DB::table('sewing_pickup_lines as pickup_line')
                    ->leftJoin('items as item', 'item.id', '=', 'pickup_line.finished_item_id')
                    ->where('pickup_line.id', $sourceId)
                    ->first([
                        'item.code as item_code',
                        'item.unit as uom',
                        'pickup_line.qty_bundle',
                    ]);

                if ($pickupLine) {
                    $contexts[$line->id] = [
                        'item_code' => (string) ($pickupLine->item_code ?: '-'),
                        'qty' => (float) $pickupLine->qty_bundle,
                        'uom' => (string) ($pickupLine->uom ?: 'pcs'),
                    ];

                    continue;
                }
            }

            if ($sourceType === 'cutting_job_wage') {
                $bundles = DB::table('cutting_job_bundles as bundle')
                    ->leftJoin('items as item', 'item.id', '=', 'bundle.finished_item_id')
                    ->where('bundle.cutting_job_id', $sourceId)
                    ->get([
                        'item.code as item_code',
                        'item.unit as uom',
                        'bundle.finished_item_id',
                        'bundle.qty_pcs',
                    ]);

                if ($bundles->isNotEmpty()) {
                    $groupedBundles = $bundles->groupBy(
                        fn ($row): string => (string) ($row->item_code ?: '#'.$row->finished_item_id)
                    );
                    $contexts[$line->id] = [
                        'item_code' => $groupedBundles->keys()->implode(', '),
                        'qty' => (float) $bundles->sum('qty_pcs'),
                        'uom' => $bundles->pluck('uom')->filter()->unique()->implode(', ') ?: 'pcs',
                    ];
                }
            }
        }

        return $contexts;
    }
}
