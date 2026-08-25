<?php

namespace App\Console\Commands;

use App\Models\PurchaseOrder;
use App\Models\PurchasePayment;
use App\Services\Accounting\JournalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class BackfillPurchasePaymentAccounting extends Command
{
    protected $signature = 'accounting:backfill-purchase-payments
        {--dry-run : Tampilkan kandidat tanpa mengubah data}';

    protected $description = 'Backfill jurnal pembayaran PO dan rekonsiliasi status pembayaran PO.';

    public function __construct(
        private readonly JournalService $journalService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $payments = PurchasePayment::query()
            ->with('journal')
            ->whereNull('voided_at')
            ->orderBy('id')
            ->get();

        $candidates = $payments->filter(function (PurchasePayment $payment): bool {
            if (!$payment->journal) {
                return true;
            }

            if (
                $payment->journal->source_type !== JournalService::SRC_PURCHASE_PAYMENT
                || (int) $payment->journal->source_id !== (int) $payment->id
            ) {
                return false;
            }

            return (bool) $payment->journal->voided_at;
        });

        $invalid = $payments->filter(function (PurchasePayment $payment): bool {
            return $payment->journal
                && (
                    $payment->journal->source_type !== JournalService::SRC_PURCHASE_PAYMENT
                    || (int) $payment->journal->source_id !== (int) $payment->id
                );
        });

        $this->info("Payment aktif: {$payments->count()}");
        $this->info("Kandidat backfill jurnal: {$candidates->count()}");

        if ($invalid->isNotEmpty()) {
            $this->error("Payment dengan journal_id/source tidak konsisten: {$invalid->count()}");
            $this->table(
                ['Payment ID', 'Journal ID', 'Source Type', 'Source ID'],
                $invalid->map(fn (PurchasePayment $payment) => [
                    $payment->id,
                    $payment->journal_id,
                    $payment->journal?->source_type,
                    $payment->journal?->source_id,
                ])->all()
            );
        }

        if ($this->option('dry-run')) {
            if ($candidates->isNotEmpty()) {
                $this->table(
                    ['Payment ID', 'PO', 'Type', 'Date', 'Amount', 'Journal ID'],
                    $candidates->map(fn (PurchasePayment $payment) => [
                        $payment->id,
                        $payment->purchaseOrder?->code ?? $payment->purchase_order_id,
                        $payment->type,
                        $payment->date?->toDateString(),
                        $payment->amount,
                        $payment->journal_id ?? '-',
                    ])->all()
                );
            }

            $this->reconcilePaymentStatuses(true);
            return $invalid->isNotEmpty() ? self::FAILURE : self::SUCCESS;
        }

        $backfilled = 0;
        $failed = 0;

        foreach ($candidates as $payment) {
            try {
                DB::transaction(function () use ($payment): void {
                    $payment->forceFill(['journal_id' => null])->save();

                    $this->journalService->postPurchasePayment(
                        $payment->fresh(['purchaseOrder', 'cashAccount', 'paymentMethod'])
                    );
                });

                $backfilled++;
            } catch (Throwable $e) {
                $failed++;
                $this->error("Payment #{$payment->id} gagal: {$e->getMessage()}");
            }
        }

        $this->info("Jurnal berhasil dibackfill: {$backfilled}");
        $changedStatuses = $this->reconcilePaymentStatuses(false);
        $this->info("Status PO direkonsiliasi: {$changedStatuses}");

        if ($invalid->isNotEmpty() || $failed > 0) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function reconcilePaymentStatuses(bool $dryRun): int
    {
        $changed = 0;

        PurchaseOrder::query()
            ->with('activePayments')
            ->orderBy('id')
            ->chunkById(200, function ($orders) use (&$changed, $dryRun): void {
                foreach ($orders as $order) {
                    $paid = round(
                        (float) $order->activePayments
                            ->whereIn('type', ['dp', 'payment'])
                            ->sum('amount'),
                        2
                    );
                    $grandTotal = round((float) $order->grand_total, 2);
                    $epsilon = 0.01;

                    $status = 'unpaid';
                    if ($paid > $epsilon && $paid > $grandTotal + $epsilon) {
                        $status = 'overpaid';
                    } elseif ($paid + $epsilon >= $grandTotal && $grandTotal > 0) {
                        $status = 'paid';
                    } elseif ($paid > $epsilon) {
                        $status = 'partial';
                    }

                    $isChanged = abs((float) $order->paid_amount - $paid) > $epsilon
                        || $order->payment_status !== $status;

                    if (!$isChanged) {
                        continue;
                    }

                    $changed++;

                    if ($dryRun) {
                        $this->line(sprintf(
                            'PO %s: paid_amount %s -> %s, status %s -> %s',
                            $order->code,
                            $order->paid_amount,
                            $paid,
                            $order->payment_status,
                            $status
                        ));
                        continue;
                    }

                    $order->forceFill([
                        'paid_amount' => $paid,
                        'payment_status' => $status,
                    ])->save();
                }
            });

        return $changed;
    }
}
