<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\Journal;
use App\Models\SalesInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesInvoiceAccountingService
{
    private const RECEIVABLE_ACCOUNT_CODE = '1301';
    private const SALES_ACCOUNT_CODE = '4101';
    private const OUTPUT_TAX_ACCOUNT_CODE = '2201';

    private const MARKETPLACE_CHANNELS = [
        'shopee',
        'tiktok',
        'lazada',
        'tokopedia',
    ];

    public function __construct(private JournalService $journalService)
    {
    }

    /**
     * Post invoice biasa ke GL: Dr Piutang Dagang / Cr Penjualan (+ PPN).
     *
     * Invoice marketplace sengaja tidak diposting dari sini karena omzet,
     * fee, refund, dan saldo marketplace dicatat oleh settlement posting.
     */
    public function post(SalesInvoice $invoice, ?int $userId = null): SalesInvoice
    {
        return DB::transaction(function () use ($invoice, $userId) {
            $locked = SalesInvoice::query()
                ->whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($this->isMarketplaceInvoice($locked)) {
                throw ValidationException::withMessages([
                    'invoice' => 'Invoice marketplace dicatat melalui posting settlement marketplace agar omzet tidak tercatat dua kali.',
                ]);
            }

            if ($locked->journal_id) {
                $journal = Journal::query()->find($locked->journal_id);
                if ($journal && ! $journal->voided_at) {
                    return $locked;
                }
            }

            $locked->load('lines');

            if ($locked->status === 'unpriced') {
                throw ValidationException::withMessages([
                    'invoice' => 'Invoice masih UNPRICED. Lengkapi harga sebelum posting.',
                ]);
            }

            if ($locked->lines->isEmpty()) {
                throw ValidationException::withMessages([
                    'invoice' => 'Invoice tidak memiliki item, tidak bisa diposting.',
                ]);
            }

            $receivable = $this->activeAccount(self::RECEIVABLE_ACCOUNT_CODE, 'Piutang Dagang');
            $sales = $this->activeAccount(self::SALES_ACCOUNT_CODE, 'Penjualan');

            $subtotal = max(0, round((float) $locked->subtotal, 2));
            $discount = max(0, round((float) $locked->discount_total, 2));
            $salesAmount = max(0, round($subtotal - $discount, 2));
            $taxAmount = max(0, round((float) $locked->tax_amount, 2));
            $receivableAmount = max(0, round((float) $locked->grand_total, 2));

            if ($receivableAmount <= 0) {
                throw ValidationException::withMessages([
                    'invoice' => 'Grand total invoice harus lebih besar dari 0.',
                ]);
            }

            if (abs($receivableAmount - ($salesAmount + $taxAmount)) > 0.01) {
                throw ValidationException::withMessages([
                    'invoice' => 'Total invoice tidak konsisten. Hitung ulang subtotal, diskon, dan pajak sebelum posting.',
                ]);
            }

            $lines = [
                [
                    'account_id' => $receivable->id,
                    'debit' => $receivableAmount,
                    'credit' => 0,
                ],
                [
                    'account_id' => $sales->id,
                    'debit' => 0,
                    'credit' => $salesAmount,
                ],
            ];

            if ($taxAmount > 0) {
                $outputTax = $this->activeAccount(self::OUTPUT_TAX_ACCOUNT_CODE, 'PPN Keluaran');
                $lines[] = [
                    'account_id' => $outputTax->id,
                    'debit' => 0,
                    'credit' => $taxAmount,
                ];
            }

            $lines = array_values(array_filter(
                $lines,
                fn (array $line) => $line['debit'] > 0 || $line['credit'] > 0,
            ));

            $journal = $this->journalService->post(
                $locked->date->toDateString(),
                'sales_invoice',
                $locked->id,
                'Penjualan ' . $locked->code,
                $lines,
                [
                    'reference_no' => $locked->code,
                    'notes' => 'Posting sales invoice ke piutang dagang.',
                    'created_by' => $userId ?? auth()->id(),
                ],
            );

            $locked->forceFill([
                'status' => 'posted',
                'journal_id' => $journal->id,
                'posted_at' => now(),
            ])->save();

            return $locked->fresh();
        });
    }

    private function activeAccount(string $code, string $label): Account
    {
        $account = Account::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        if (! $account) {
            throw ValidationException::withMessages([
                'account' => "Akun {$code} {$label} tidak ditemukan atau tidak aktif.",
            ]);
        }

        return $account;
    }

    private function isMarketplaceInvoice(SalesInvoice $invoice): bool
    {
        return in_array(strtolower(trim((string) $invoice->channel)), self::MARKETPLACE_CHANNELS, true);
    }
}
