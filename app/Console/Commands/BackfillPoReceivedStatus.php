<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Backfill purchase_orders.received_status dari data GRN/purchase_receipts
 * yang sudah ada.
 *
 * Aturan:
 *   - not_received  : tidak ada GRN posted sama sekali
 *   - partial       : total qty received (GRN posted) < qty PO untuk setidaknya satu line
 *   - fully_received: semua line sudah terpenuhi (qty received >= qty PO)
 *
 * Tidak mengubah: status PO, payment_status, atau kolom lain.
 *
 * DEFAULT = DRY RUN. Tambahkan --apply untuk benar-benar menulis.
 *
 * Usage:
 *   php artisan purchasing:backfill-received-status --dry-run
 *   php artisan purchasing:backfill-received-status --apply
 */
class BackfillPoReceivedStatus extends Command
{
    protected $signature = 'purchasing:backfill-received-status
                            {--dry-run : Preview perubahan tanpa menulis (default)}
                            {--apply   : Tulis perubahan ke database}';

    protected $description = 'Backfill received_status di purchase_orders berdasarkan GRN posted yang ada (default dry-run)';

    public function handle(): int
    {
        // Validasi: minimal salah satu flag harus ada, atau default dry-run
        $apply  = (bool) $this->option('apply');
        $dryRun = !$apply; // jika tidak --apply, selalu dry-run

        // Guard: pastikan kolom dan tabel yang dibutuhkan ada
        if (!Schema::hasTable('purchase_orders')) {
            $this->error('Tabel purchase_orders tidak ditemukan.');
            return self::FAILURE;
        }
        if (!Schema::hasColumn('purchase_orders', 'received_status')) {
            $this->error('Kolom received_status belum ada di purchase_orders. Jalankan migration dulu.');
            return self::FAILURE;
        }
        if (!Schema::hasTable('purchase_order_lines')) {
            $this->error('Tabel purchase_order_lines tidak ditemukan.');
            return self::FAILURE;
        }
        if (!Schema::hasTable('purchase_receipts')) {
            $this->error('Tabel purchase_receipts tidak ditemukan.');
            return self::FAILURE;
        }
        if (!Schema::hasTable('purchase_receipt_lines')) {
            $this->error('Tabel purchase_receipt_lines tidak ditemukan.');
            return self::FAILURE;
        }

        $this->newLine();
        $this->line($apply
            ? '<fg=red;options=bold>>>> MODE APPLY — perubahan akan ditulis ke database</>'
            : '<fg=yellow;options=bold>>>> MODE DRY-RUN — hanya preview, tidak ada yang ditulis</>'
        );
        $this->newLine();

        // Ambil semua PO (kecuali cancelled)
        $pos = DB::table('purchase_orders')
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('id')
            ->select('id', 'code', 'status', 'received_status')
            ->get();

        if ($pos->isEmpty()) {
            $this->warn('Tidak ada Purchase Order yang ditemukan.');
            return self::SUCCESS;
        }

        $this->line("Total PO yang akan diproses: <fg=cyan>{$pos->count()}</>");
        $this->newLine();

        // Hitung total qty per PO line
        $poLineQty = DB::table('purchase_order_lines')
            ->select('purchase_order_id', 'id', 'qty')
            ->get()
            ->groupBy('purchase_order_id');

        // Hitung total qty yang sudah diproses per PO line (diterima + reject).
        // Replacement GRN tidak mengonsumsi kuota PO asal.
        $receivedQty = DB::table('purchase_receipt_lines as prl')
            ->join('purchase_receipts as pr', 'pr.id', '=', 'prl.purchase_receipt_id')
            ->where('pr.status', 'posted')
            ->where(function ($q) {
                $q->whereNull('pr.is_replacement')
                    ->orWhere('pr.is_replacement', false);
            })
            ->select('prl.purchase_order_line_id', DB::raw('SUM(COALESCE(prl.qty_received, 0) + COALESCE(prl.qty_reject, 0)) as total_received'))
            ->groupBy('prl.purchase_order_line_id')
            ->pluck('total_received', 'purchase_order_line_id');

        // Counters
        $countNotReceived  = 0;
        $countPartial      = 0;
        $countFullyReceived = 0;
        $countSkipped      = 0; // sudah benar, tidak perlu update
        $countUpdated      = 0;

        $rows = []; // untuk tabel output

        foreach ($pos as $po) {
            $lines = $poLineQty->get($po->id, collect());

            if ($lines->isEmpty()) {
                // PO tanpa lines — anggap not_received
                $newStatus = 'not_received';
            } else {
                $totalLines       = $lines->count();
                $fullyReceivedCnt = 0;
                $anyReceived      = false;

                foreach ($lines as $line) {
                    $received = (float) ($receivedQty->get($line->id, 0));
                    $ordered  = (float) $line->qty;

                    if ($received > 0) {
                        $anyReceived = true;
                    }
                    if ($ordered > 0 && $received >= $ordered) {
                        $fullyReceivedCnt++;
                    }
                }

                if ($fullyReceivedCnt >= $totalLines) {
                    $newStatus = 'fully_received';
                } elseif ($anyReceived) {
                    $newStatus = 'partial';
                } else {
                    $newStatus = 'not_received';
                }
            }

            // Track counter
            match ($newStatus) {
                'fully_received' => $countFullyReceived++,
                'partial'        => $countPartial++,
                default          => $countNotReceived++,
            };

            $oldStatus = $po->received_status ?? null;
            $changed   = $oldStatus !== $newStatus;

            if (!$changed) {
                $countSkipped++;
                continue; // tidak perlu update
            }

            $countUpdated++;
            $rows[] = [
                $po->id,
                $po->code,
                $po->status,
                $oldStatus ?? '(null)',
                $newStatus,
            ];

            if ($apply) {
                DB::table('purchase_orders')
                    ->where('id', $po->id)
                    ->update([
                        'received_status' => $newStatus,
                        'updated_at'      => now(),
                    ]);
            }
        }

        // Output tabel perubahan
        if (!empty($rows)) {
            $this->table(
                ['ID', 'Kode PO', 'Status PO', 'Lama', 'Baru'],
                $rows
            );
            $this->newLine();
        } else {
            $this->info('Tidak ada perubahan yang perlu dilakukan.');
            $this->newLine();
        }

        // Summary
        $this->line('==============================');
        $this->line('<fg=cyan>SUMMARY</> received_status distribusi (setelah backfill):');
        $this->line("  not_received   : <fg=yellow>{$countNotReceived}</>");
        $this->line("  partial        : <fg=yellow>{$countPartial}</>");
        $this->line("  fully_received : <fg=green>{$countFullyReceived}</>");
        $this->line("  skipped (sudah benar): <fg=gray>{$countSkipped}</>");
        $this->newLine();
        $this->line("  Total PO diproses : {$pos->count()}");
        $this->line("  Yang perlu diupdate: <fg=cyan>{$countUpdated}</>");

        if ($dryRun && $countUpdated > 0) {
            $this->newLine();
            $this->warn("DRY-RUN: {$countUpdated} PO akan diupdate. Jalankan --apply untuk menulis.");
        }

        if ($apply) {
            $this->newLine();
            $this->info("✅ Selesai. {$countUpdated} PO berhasil diupdate.");
        }

        $this->line('==============================');
        $this->newLine();

        return self::SUCCESS;
    }
}
