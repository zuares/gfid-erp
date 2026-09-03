<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\UrlGenerationException;

class JournalController extends Controller
{
    private function sourceTypeLabel(?string $type): string
    {
        return match ($type) {
            'cash_expense' => 'Pengeluaran Kas/Bank',
            'cash_expense_void' => 'Pembatalan Pengeluaran',
            'cash_receipt' => 'Penerimaan Kas/Bank',
            'cash_receipt_void' => 'Pembatalan Penerimaan',
            'cash_transfer' => 'Transfer Kas/Bank',
            'cash_transfer_void' => 'Pembatalan Transfer Kas/Bank',

            'account_reclass_inventory_adjustment' => 'Reclass Adjustment Stok (6101 → 6115)',

            'opening_balance' => 'Saldo Awal',
            'opening_balance_void' => 'Pembatalan Saldo Awal',

            'shipment' => 'Pengiriman',
            'shipment_void' => 'Pembatalan Pengiriman',

            'piecework_payroll' => 'Payroll Borongan (HPP)',
            'piecework_payroll_void' => 'Pembatalan Payroll Borongan',

            // ✅ PURCHASING
            'purchase_payment' => 'Pembayaran Pembelian (DP / Pelunasan)',
            'purchase_receipt_post' => 'GRN Posted (Persediaan vs Hutang)',
            'purchase_dp_apply' => 'Apply DP ke Hutang',

            default => $type ?: 'Lainnya',
        };
    }

    private function buildSourceTypeOptions($sourceTypes)
    {
        return $sourceTypes->mapWithKeys(function ($st) {
            return [$st => $this->sourceTypeLabel($st)];
        });
    }

    private function safeRoute(string $name, mixed $param = null): ?string
    {
        try {
            return $param === null ? route($name) : route($name, $param);
        } catch (UrlGenerationException $e) {
            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function index(Request $request)
    {
        // Cut-off filter: default from = cut-off date jika user belum mengisi
        // Tambahkan ?show_legacy=1 untuk tampilkan semua data termasuk sebelum cut-off
        $cutoffDate   = SystemSetting::cutoffDateString();
        $showLegacy   = $request->boolean('show_legacy');
        $cutoffActive = $cutoffDate && !$request->has('from') && !$showLegacy;

        $q = Journal::query()
            ->with(['lines.account'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('source_type')) {
            $q->where('source_type', $request->string('source_type')->toString());
        }

        $fromFilter = $request->input('from', $cutoffActive ? $cutoffDate : null);
        if ($fromFilter) {
            $q->whereDate('date', '>=', $fromFilter);
        }

        if ($request->filled('to')) {
            $q->whereDate('date', '<=', $request->date('to'));
        }

        if ($request->filled('status')) {
            $status = $request->string('status')->toString();

            if ($status === 'final') {
                $q->whereNotNull('posted_at')->whereNull('voided_at');
            } elseif ($status === 'draft') {
                $q->whereNull('posted_at')->whereNull('voided_at');
            } elseif ($status === 'voided') {
                $q->whereNotNull('voided_at');
            }
        }

        if ($request->filled('q')) {
            $term = trim($request->string('q')->toString());

            $q->where(function ($w) use ($term) {
                $w->where('description', 'like', "%{$term}%")
                    ->orWhere('source_type', 'like', "%{$term}%")
                    ->orWhere('source_id', 'like', "%{$term}%");

                $w->orWhereHas('lines.account', function ($wa) use ($term) {
                    $wa->where('name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%");
                });
            });
        }

        $rawSourceTypes = Journal::query()
            ->select('source_type')
            ->whereNotNull('source_type')
            ->distinct()
            ->orderBy('source_type')
            ->pluck('source_type');

        $sourceTypeOptions = $this->buildSourceTypeOptions($rawSourceTypes);

        $journals = $q->paginate(30)->withQueryString();

        return view('accounting.journals.index', [
            'journals'          => $journals,
            'sourceTypes'       => $rawSourceTypes,
            'sourceTypeOptions' => $sourceTypeOptions,
            'cutoff'            => [
                'date'   => $cutoffDate,
                'active' => $cutoffActive,
                'legacy' => $showLegacy,
                'from'   => $fromFilter,
            ],
        ]);
    }

    public function show(Journal $journal)
    {
        $journal->load('lines.account');

        $sourceUrl = null;
        $sourceLabel = null;

        $sourceTypeLabel = $this->sourceTypeLabel($journal->source_type);

        if (in_array($journal->source_type, ['cash_expense', 'cash_expense_void'], true) && $journal->source_id) {
            $sourceUrl = $this->safeRoute('accounting.cash-expenses.show', $journal->source_id);
            $sourceLabel = $sourceUrl ? 'Buka Pengeluaran' : null;
        }

        if (in_array($journal->source_type, ['cash_receipt', 'cash_receipt_void'], true) && $journal->source_id) {
            $sourceUrl = $this->safeRoute('accounting.cash-receipts.show', $journal->source_id);
            $sourceLabel = $sourceUrl ? 'Buka Penerimaan' : null;
        }

        if (in_array($journal->source_type, ['cash_transfer', 'cash_transfer_void'], true) && $journal->source_id) {
            $sourceUrl = $this->safeRoute('accounting.cash-transfers.show', $journal->source_id);
            $sourceLabel = $sourceUrl ? 'Buka Transfer' : null;
        }

        if (in_array($journal->source_type, ['opening_balance', 'opening_balance_void'], true)) {
            $sourceUrl = $this->safeRoute('accounting.opening-balances.index');
            $sourceLabel = $sourceUrl ? 'Buka Saldo Awal' : null;
        }

        if ($journal->source_type === 'piecework_payroll' && $journal->source_id) {
            $sourceUrl = $this->safeRoute('payroll.sewing.show', $journal->source_id) ?? $this->safeRoute('payroll.cutting.show', $journal->source_id);

            $sourceLabel = $sourceUrl ? 'Buka Payroll Borongan' : null;
        }

        // ✅ Purchasing: (opsional) link ke PO atau GRN kalau route kamu ada
        // - purchase_receipt_post / purchase_dp_apply: source_id = GRN id
        if (in_array($journal->source_type, ['purchase_receipt_post', 'purchase_dp_apply'], true) && $journal->source_id) {
            $sourceUrl = $this->safeRoute('purchasing.purchase_receipts.show', $journal->source_id);
            $sourceLabel = $sourceUrl ? 'Buka GRN' : null;
        }

        // - purchase_payment: source_id = payment id (route khusus payment tidak ada)
        // bisa link ke PO lewat pencarian manual nanti, tapi tidak wajib.

        return view('accounting.journals.show', compact(
            'journal',
            'sourceUrl',
            'sourceLabel',
            'sourceTypeLabel'
        ));
    }
}
