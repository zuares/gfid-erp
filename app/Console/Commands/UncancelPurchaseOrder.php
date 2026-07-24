<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PurchaseOrder;

class UncancelPurchaseOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'po:uncancel {code} {--status=approved : Status baru (approved/draft)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Membatalkan status cancel pada Purchase Order';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $code = $this->argument('code');
        $status = $this->option('status');

        if (!in_array($status, ['approved', 'draft'])) {
            $this->error('Status hanya bisa diubah menjadi approved atau draft.');
            return 1;
        }

        $po = PurchaseOrder::where('code', $code)->first();

        if (!$po) {
            $this->error("Purchase Order dengan kode {$code} tidak ditemukan.");
            return 1;
        }

        if ($po->status !== 'cancelled') {
            $this->warn("Purchase Order {$code} tidak dalam status cancelled. Status saat ini: {$po->status}");
            if (!$this->confirm('Apakah Anda yakin ingin tetap mengubah statusnya?')) {
                return 0;
            }
        }

        $po->update([
            'status' => $status,
            'cancelled_at' => null,
            'cancelled_by' => null,
        ]);

        $this->info("Berhasil! Status Purchase Order {$code} telah diubah menjadi {$status}.");
        return 0;
    }
}
