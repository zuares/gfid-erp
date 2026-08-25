<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\MarketplacePayout;
use App\Models\Store;
use App\Services\Accounting\MarketplacePayoutService;
use App\Services\Accounting\ShopeeWalletPayoutImportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class MarketplacePayoutController extends Controller
{
    public function index(Request $request)
    {
        $q = MarketplacePayout::query()
            ->with(['bankAccount', 'journal', 'store'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $q->where('status', $request->string('status')->toString());
        }

        if ($request->filled('from')) {
            $q->whereDate('date', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $q->whereDate('date', '<=', $request->date('to'));
        }

        if ($request->filled('marketplace')) {
            $q->where('marketplace_name', $request->string('marketplace')->toString());
        }

        $summaryRows = (clone $q)
            ->withoutEagerLoads()
            ->reorder()
            ->selectRaw('status, COUNT(*) as total_docs, COALESCE(SUM(amount), 0) as total_amount')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $summary = [
            'total_docs'     => (int) $summaryRows->sum('total_docs'),
            'total_amount'   => (float) $summaryRows->sum('total_amount'),
            'posted_amount'  => (float) ($summaryRows->get('posted')->total_amount ?? 0),
            'draft_docs'     => (int) ($summaryRows->get('draft')->total_docs ?? 0),
            'void_docs'      => (int) ($summaryRows->get('void')->total_docs ?? 0),
        ];

        $payouts = $q->paginate(25)->withQueryString();
        $bankAccounts = $this->bankAccountOptions();
        $allDraftCount = MarketplacePayout::query()->where('status', 'draft')->count();
        $shopeeStores = Store::query()
            ->with('channel')
            ->where('is_active', true)
            ->whereHas('channel', fn ($query) => $query->whereIn('code', ['shopee', 'SHP', 'SHOPEE']))
            ->orderBy('name')
            ->get();
        $marketplaceNames = MarketplacePayout::select('marketplace_name')
            ->distinct()->orderBy('marketplace_name')->pluck('marketplace_name');

        return view('accounting.marketplace_payouts.index', compact(
            'payouts', 'summary', 'bankAccounts', 'marketplaceNames', 'shopeeStores', 'allDraftCount'
        ));
    }

    public function importShopee(Request $request, ShopeeWalletPayoutImportService $importer)
    {
        $data = $request->validate([
            'stores'                    => ['nullable', 'array'],
            'stores.*'                  => ['array'],
            'stores.*.enabled'         => ['nullable', 'boolean'],
            'stores.*.bank_account_id'  => ['nullable', 'integer', 'exists:accounts,id'],
            'from'                      => ['required', 'date'],
            'to'                        => ['required', 'date', 'after_or_equal:from'],
        ]);

        $selectedStores = collect($data['stores'] ?? [])
            ->filter(fn (array $store) => (bool) ($store['enabled'] ?? false));

        if ($selectedStores->isEmpty()) {
            return back()->with('status', 'error')
                ->with('message', 'Pilih minimal satu toko Shopee.');
        }

        $fromDate = Carbon::parse($data['from'])->startOfDay();
        $toDate = Carbon::parse($data['to'])->startOfDay();

        $from = $fromDate;
        $to = $toDate->copy()->endOfDay();

        $stores = Store::with('channel')
            ->whereIn('id', array_map('intval', array_keys($selectedStores->all())))
            ->where('is_active', true)
            ->whereHas('channel', fn ($query) => $query->whereIn('code', ['shopee', 'SHP', 'SHOPEE']))
            ->get()
            ->keyBy('id');

        $totals = [
            'stores'                => 0,
            'created'               => 0,
            'skipped'               => 0,
            'skippedExisting'       => 0,
            'bankConflicts'         => 0,
            'skippedInvalid'        => 0,
            'skippedInvalidReasons' => [],
            'errors'                => [],
        ];

        foreach ($selectedStores as $storeId => $storeData) {
            $store = $stores->get((int) $storeId);

            if (! $store) {
                $totals['errors'][] = "Toko #{$storeId} tidak aktif atau bukan toko Shopee.";
                continue;
            }

            $bankAccountId = (int) ($storeData['bank_account_id'] ?? 0);
            if (! $bankAccountId || ! $this->isBankAccount($bankAccountId)) {
                $totals['errors'][] = "{$store->name}: akun tujuan harus akun kas/bank yang aktif.";
                continue;
            }

            try {
                $result = $importer->import($store, $from, $to, $bankAccountId, Auth::id());
                $totals['stores']++;
                $totals['created'] += $result['created'];
                $totals['skipped'] += $result['skipped'];
                $totals['skippedExisting'] += $result['skippedExisting'];
                $totals['bankConflicts'] += $result['bankConflicts'] ?? 0;
                $totals['skippedInvalid'] += $result['skippedInvalid'];

                foreach ($result['skippedInvalidReasons'] ?? [] as $reason => $count) {
                    $totals['skippedInvalidReasons'][$reason] =
                        ($totals['skippedInvalidReasons'][$reason] ?? 0) + $count;
                }
            } catch (Throwable $e) {
                report($e);
                $totals['errors'][] = "{$store->name}: {$e->getMessage()}";
            }
        }

        if ($totals['stores'] === 0 && $totals['errors'] !== []) {
            return back()->with('status', 'error')
                ->with('message', 'Import Shopee gagal: ' . implode(' | ', $totals['errors']));
        }

        $reasonText = collect($totals['skippedInvalidReasons'])
            ->map(fn ($count, $reason) => "{$reason}: {$count}")
            ->implode(', ');
        $errorText = $totals['errors'] !== []
            ? '; gagal: ' . implode(' | ', $totals['errors'])
            : '';

        return back()->with('status', $totals['errors'] === [] ? 'ok' : 'error')
            ->with('message', "Import Shopee selesai untuk {$totals['stores']} toko: {$totals['created']} draft baru, "
                . "{$totals['skipped']} dilewati (sudah ada: {$totals['skippedExisting']}, "
                . "konflik akun bank: {$totals['bankConflicts']}, tidak valid: {$totals['skippedInvalid']}"
                . ($reasonText !== '' ? "; {$reasonText}" : '') . "{$errorText}).");
    }

    public function create()
    {
        $bankAccounts = $this->bankAccountOptions();

        return view('accounting.marketplace_payouts.create', compact('bankAccounts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date'             => ['required', 'date'],
            'marketplace_name' => ['required', 'string', 'max:100'],
            'amount'           => ['required', 'numeric', 'min:0.01'],
            'bank_account_id'  => ['required', 'integer', 'exists:accounts,id'],
            'reference'        => ['nullable', 'string', 'max:100'],
            'description'      => ['nullable', 'string', 'max:255'],
            'notes'            => ['nullable', 'string'],
        ]);

        if (! $this->isBankAccount((int) $data['bank_account_id'])) {
            return back()->withInput()
                ->with('status', 'error')
                ->with('message', 'Akun tujuan harus akun kas/bank yang aktif.');
        }

        $data['status']     = 'draft';
        $data['created_by'] = Auth::id();

        $payout = MarketplacePayout::create($data);

        return redirect()
            ->route('accounting.marketplace-payouts.show', $payout)
            ->with('status', 'ok')
            ->with('message', 'Penerimaan marketplace tersimpan (DRAFT).');
    }

    public function show(MarketplacePayout $marketplacePayout)
    {
        $marketplacePayout->load(['bankAccount', 'store', 'journal.lines.account']);

        return view('accounting.marketplace_payouts.show', compact('marketplacePayout'));
    }

    public function edit(MarketplacePayout $marketplacePayout)
    {
        if ($marketplacePayout->status !== 'draft') {
            return redirect()
                ->route('accounting.marketplace-payouts.show', $marketplacePayout)
                ->with('status', 'error')
                ->with('message', 'Hanya DRAFT yang bisa diedit.');
        }

        $bankAccounts = $this->bankAccountOptions();

        return view('accounting.marketplace_payouts.edit', compact('marketplacePayout', 'bankAccounts'));
    }

    public function update(Request $request, MarketplacePayout $marketplacePayout)
    {
        if ($marketplacePayout->status !== 'draft') {
            return redirect()
                ->route('accounting.marketplace-payouts.show', $marketplacePayout)
                ->with('status', 'error')
                ->with('message', 'Hanya DRAFT yang bisa diupdate.');
        }

        $data = $request->validate([
            'date'             => ['required', 'date'],
            'marketplace_name' => ['required', 'string', 'max:100'],
            'amount'           => ['required', 'numeric', 'min:0.01'],
            'bank_account_id'  => ['required', 'integer', 'exists:accounts,id'],
            'reference'        => ['nullable', 'string', 'max:100'],
            'description'      => ['nullable', 'string', 'max:255'],
            'notes'            => ['nullable', 'string'],
        ]);

        if (! $this->isBankAccount((int) $data['bank_account_id'])) {
            return back()->withInput()
                ->with('status', 'error')
                ->with('message', 'Akun tujuan harus akun kas/bank yang aktif.');
        }

        $marketplacePayout->update($data);

        return redirect()
            ->route('accounting.marketplace-payouts.show', $marketplacePayout)
            ->with('status', 'ok')
            ->with('message', 'Penerimaan marketplace berhasil diupdate.');
    }

    public function destroy(MarketplacePayout $marketplacePayout)
    {
        if ($marketplacePayout->status !== 'draft') {
            return redirect()
                ->route('accounting.marketplace-payouts.show', $marketplacePayout)
                ->with('status', 'error')
                ->with('message', 'Hanya DRAFT yang bisa dihapus.');
        }

        $marketplacePayout->delete();

        return redirect()
            ->route('accounting.marketplace-payouts.index')
            ->with('status', 'ok')
            ->with('message', 'Penerimaan DRAFT berhasil dihapus.');
    }

    public function post(MarketplacePayout $marketplacePayout, MarketplacePayoutService $service)
    {
        $service->post($marketplacePayout);

        return redirect()
            ->route('accounting.marketplace-payouts.show', $marketplacePayout)
            ->with('status', 'ok')
            ->with('message', 'Penerimaan berhasil di-POST (Dr Bank / Cr Saldo Marketplace).');
    }

    public function bulkPost(Request $request, MarketplacePayoutService $service)
    {
        $data = $request->validate([
            'scope'       => ['required', 'in:all,unposted'],
            'marketplace' => ['nullable', 'string', 'max:100'],
            'from'        => ['nullable', 'date'],
            'to'          => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = MarketplacePayout::query()->orderBy('id');
        if ($data['scope'] === 'unposted') {
            $query->where('status', 'draft');

            if (! empty($data['marketplace'])) {
                $query->where('marketplace_name', $data['marketplace']);
            }
            if (! empty($data['from'])) {
                $query->whereDate('date', '>=', $data['from']);
            }
            if (! empty($data['to'])) {
                $query->whereDate('date', '<=', $data['to']);
            }
        }

        $stats = [
            'posted'        => 0,
            'alreadyPosted' => 0,
            'skippedVoid'   => 0,
            'failed'        => 0,
            'errors'        => [],
        ];

        $query->chunkById(100, function ($payouts) use ($service, &$stats) {
            foreach ($payouts as $payout) {
                if ($payout->status === 'posted') {
                    $stats['alreadyPosted']++;
                    continue;
                }

                if ($payout->status !== 'draft') {
                    $stats['skippedVoid']++;
                    continue;
                }

                try {
                    $service->post($payout);
                    $stats['posted']++;
                } catch (Throwable $e) {
                    report($e);
                    $stats['failed']++;
                    $stats['errors'][] = "#{$payout->id}: {$e->getMessage()}";
                }
            }
        });

        $scopeLabel = $data['scope'] === 'all'
            ? 'semua data'
            : 'data yang belum posting sesuai filter';
        $errorText = $stats['errors'] !== []
            ? ' Gagal: ' . implode(' | ', array_slice($stats['errors'], 0, 3))
                . (count($stats['errors']) > 3 ? ' dan lainnya.' : '.')
            : '';

        return back()
            ->with('status', $stats['failed'] > 0 ? 'error' : 'ok')
            ->with('message', "Posting {$scopeLabel} selesai: {$stats['posted']} berhasil, "
                . "{$stats['alreadyPosted']} sudah posted, {$stats['skippedVoid']} dilewati, "
                . "{$stats['failed']} gagal." . $errorText);
    }

    public function void(Request $request, MarketplacePayout $marketplacePayout, MarketplacePayoutService $service)
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $service->void($marketplacePayout, $data['reason'] ?? null);

        return redirect()
            ->route('accounting.marketplace-payouts.show', $marketplacePayout)
            ->with('status', 'ok')
            ->with('message', 'Penerimaan berhasil di-VOID (reversal journal dibuat).');
    }

    private function bankAccountOptions()
    {
        return Account::query()
            ->where('is_cash', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
    }

    private function isBankAccount(int $accountId): bool
    {
        return Account::query()
            ->whereKey($accountId)
            ->where('is_cash', true)
            ->where('is_active', true)
            ->exists();
    }
}
