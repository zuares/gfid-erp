<?php

namespace App\Console\Commands\Purchasing;

use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnLine;
use App\Models\PurchaseReceipt;
use App\Services\Purchasing\GoodsReceiptService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileReplacementProgress extends Command
{
    protected $signature = 'purchasing:reconcile-replacement-progress {--fix : Terapkan perbaikan pada database} {--dry-run : Hanya tampilkan temuan tanpa memperbaiki database (Default)}';

    protected $description = 'Rekonsiliasi nilai replacement_qty_received dan status replacement pada modul Purchase Return.';

    public function handle(GoodsReceiptService $grnService)
    {
        $isFix = $this->option('fix');
        
        $this->info("Memulai Audit Rekonsiliasi Replacement Progress...");
        if ($isFix) {
            $this->warn("Mode FIX aktif. Database akan di-update berdasarkan perhitungan agregat GRN replacement yang posted.");
        } else {
            $this->info("Mode DRY-RUN (Default). Tidak ada perubahan database.");
        }

        $returns = PurchaseReturn::with('lines')->get();
        $discrepancies = [];

        foreach ($returns as $return) {
            $needsFix = false;
            
            // 1. Get total received from posted GRNs
            $postedReceipts = PurchaseReceipt::with('lines')
                ->where('purchase_return_id', $return->id)
                ->where('is_replacement', true)
                ->where('status', 'posted')
                ->get();

            $calculatedByLine = [];
            foreach ($postedReceipts as $rec) {
                foreach ($rec->lines as $line) {
                    if ($line->purchase_return_line_id && $line->qty_received > 0) {
                        $calculatedByLine[$line->purchase_return_line_id] = 
                            ($calculatedByLine[$line->purchase_return_line_id] ?? 0.0) + (float) $line->qty_received;
                    }
                }
            }

            // 2. Compare against stored
            $isCompleted = true;
            $totalReceived = 0.0;
            
            foreach ($return->lines as $line) {
                $calc = $calculatedByLine[$line->id] ?? 0.0;
                $stored = (float) $line->replacement_qty_received;
                
                if (round($calc, 4) !== round($stored, 4)) {
                    $discrepancies[] = [
                        'return_code' => $return->code,
                        'line_id' => $line->id,
                        'expected' => (float) $line->replacement_qty_expected,
                        'stored' => $stored,
                        'calculated' => $calc,
                        'diff' => $calc - $stored,
                    ];
                    $needsFix = true;
                }
                
                $totalReceived += $calc;
                if (round($calc, 4) < round((float) $line->replacement_qty_expected, 4)) {
                    $isCompleted = false;
                }
            }
            
            $expectedStatus = 'pending';
            if ($isCompleted && $return->lines->count() > 0) {
                $expectedStatus = 'received';
            } elseif ($totalReceived > 0.0001) {
                $expectedStatus = 'partial';
            }
            
            if ($return->replacement_status !== $expectedStatus) {
                $discrepancies[] = [
                    'return_code' => $return->code,
                    'line_id' => 'HEADER',
                    'expected' => 'Status: ' . $expectedStatus,
                    'stored' => 'Status: ' . $return->replacement_status,
                    'calculated' => 'Status: ' . $expectedStatus,
                    'diff' => '-',
                ];
                $needsFix = true;
            }

            // 3. Fix if requested
            if ($needsFix && $isFix) {
                DB::transaction(function () use ($return, $grnService) {
                    $grnService->syncReplacementProgress($return);
                });
                $this->info("✅ Diperbaiki: Return {$return->code}");
            }
        }

        if (empty($discrepancies)) {
            $this->info("Tidak ditemukan anomali/state drift pada sistem.");
        } else {
            $this->warn("Ditemukan " . count($discrepancies) . " perbedaan state:");
            $this->table(
                ['Return Code', 'Line ID', 'Expected Qty', 'Stored Received', 'Calculated', 'Diff/Issue'],
                $discrepancies
            );
        }

        return 0;
    }
}
