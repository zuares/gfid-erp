<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\UrlGenerationException;

class JournalController extends Controller
{
    /**
     * Map source_type -> label manusia (awam-friendly).
     */
    private function sourceTypeLabel(?string $type): string
    {
        return match ($type) {
            'cash_expense' => 'Pengeluaran Kas/Bank',
            'cash_expense_void' => 'Pembatalan Pengeluaran',

            'opening_balance' => 'Saldo Awal',
            'opening_balance_void' => 'Pembatalan Saldo Awal',

            // Sales / shipment (kalau ada)
            'shipment' => 'Pengiriman',
            'shipment_void' => 'Pembatalan Pengiriman',

            // ✅ Payroll borongan (masuk HPP)
            'piecework_payroll' => 'Payroll Borongan (HPP)',
            'piecework_payroll_void' => 'Pembatalan Payroll Borongan',

            default => $type ?: 'Lainnya',
        };
    }

    /**
     * Build options untuk dropdown source_type.
     */
    private function buildSourceTypeOptions($sourceTypes)
    {
        return $sourceTypes->mapWithKeys(function ($st) {
            return [$st => $this->sourceTypeLabel($st)];
        });
    }

    /**
     * Helper aman untuk bikin route URL tanpa Route::has().
     * Kalau route belum ada / param gak cocok -> return null (biar view aman).
     */
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
        $q = Journal::query()
            ->with(['lines.account'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        // ===== Filters =====
        if ($request->filled('source_type')) {
            $q->where('source_type', $request->string('source_type')->toString());
        }

        if ($request->filled('from')) {
            $q->whereDate('date', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $q->whereDate('date', '<=', $request->date('to'));
        }

        // status = final | draft | voided
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

        // Search
        if ($request->filled('q')) {
            $term = trim($request->string('q')->toString());

            $q->where(function ($w) use ($term) {
                $w->where('description', 'like', "%{$term}%")
                    ->orWhere('source_type', 'like', "%{$term}%")
                    ->orWhere('source_id', 'like', "%{$term}%");

                // cari berdasarkan nama/kode akun di lines
                $w->orWhereHas('lines.account', function ($wa) use ($term) {
                    $wa->where('name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%");
                });
            });
        }

        // dropdown options (source type)
        $rawSourceTypes = Journal::query()
            ->select('source_type')
            ->whereNotNull('source_type')
            ->distinct()
            ->orderBy('source_type')
            ->pluck('source_type');

        $sourceTypeOptions = $this->buildSourceTypeOptions($rawSourceTypes);

        $journals = $q->paginate(30)->withQueryString();

        return view('accounting.journals.index', [
            'journals' => $journals,
            'sourceTypes' => $rawSourceTypes,
            'sourceTypeOptions' => $sourceTypeOptions,
        ]);
    }

    public function show(Journal $journal)
    {
        $journal->load('lines.account');

        $sourceUrl = null;
        $sourceLabel = null;

        // label manusia untuk show page
        $sourceTypeLabel = $this->sourceTypeLabel($journal->source_type);

        // ===== Link ke sumber kalau tersedia (tanpa Route::has) =====

        // Cash Expense
        if (in_array($journal->source_type, ['cash_expense', 'cash_expense_void'], true) && $journal->source_id) {
            $sourceUrl = $this->safeRoute('accounting.cash-expenses.show', $journal->source_id);
            $sourceLabel = $sourceUrl ? 'Buka Pengeluaran' : null;
        }

        // Opening Balance
        if (in_array($journal->source_type, ['opening_balance', 'opening_balance_void'], true)) {
            $sourceUrl = $this->safeRoute('accounting.opening-balances.index');
            $sourceLabel = $sourceUrl ? 'Buka Saldo Awal' : null;
        }

        // ✅ Piecework Payroll (Cutting/Sewing) — sesuaikan route kamu
        // Misal kamu punya route show period payroll: payroll.sewing.show / payroll.cutting.show
        // dan period id disimpan di source_id
        if ($journal->source_type === 'piecework_payroll' && $journal->source_id) {
            // opsi 1: kalau kamu simpan module di description (tidak ideal)
            // opsi 2: route tunggal payroll.piecework.show
            // aku buat aman: coba 2 route, yang berhasil dipakai.
            $sourceUrl = $this->safeRoute('payroll.sewing.show', $journal->source_id) ?? $this->safeRoute('payroll.cutting.show', $journal->source_id);

            $sourceLabel = $sourceUrl ? 'Buka Payroll Borongan' : null;
        }

        return view('accounting.journals.show', compact(
            'journal',
            'sourceUrl',
            'sourceLabel',
            'sourceTypeLabel'
        ));
    }
}
