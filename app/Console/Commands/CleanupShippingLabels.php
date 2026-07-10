<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\MarketplaceOrder;
use Carbon\Carbon;

class CleanupShippingLabels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marketplace:cleanup-labels';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menghapus file resi (.pdf.gz) lokal untuk pesanan yang sudah selesai (COMPLETED) lebih dari 4 hari yang lalu.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai pembersihan cache dokumen resi pengiriman...');
        
        $disk = Storage::disk('local');
        $directory = 'shipping_labels';
        
        if (!$disk->exists($directory)) {
            $this->info('Direktori shipping_labels tidak ditemukan. Selesai.');
            return;
        }

        $files = $disk->allFiles($directory);
        if (empty($files)) {
            $this->info('Tidak ada file resi untuk dihapus.');
            return;
        }

        $deletedCount = 0;
        $fourDaysAgo = Carbon::now()->subDays(4);

        foreach ($files as $file) {
            // Hanya proses file .pdf.gz
            if (!str_ends_with($file, '.pdf.gz')) {
                continue;
            }

            // Ekstrak nama file (format: storeId_orderSn.pdf.gz)
            $filename = basename($file);
            $filenameWithoutExt = str_replace('.pdf.gz', '', $filename);
            
            $parts = explode('_', $filenameWithoutExt, 2);
            if (count($parts) !== 2) {
                continue; // Format tidak sesuai
            }
            
            $storeId = $parts[0];
            $orderSn = $parts[1];

            // Cek status pesanan di database
            $order = MarketplaceOrder::where('store_id', $storeId)
                ->where('channel_order_id', $orderSn)
                ->first();

            // Jika pesanan tidak ada (dihapus), atau statusnya COMPLETED dan sudah lebih dari 4 hari
            if (!$order) {
                $disk->delete($file);
                $deletedCount++;
                $this->line("Dihapus: {$filename} (Pesanan tidak ditemukan di DB)");
            } elseif ($order->order_status === 'COMPLETED' && $order->updated_at < $fourDaysAgo) {
                $disk->delete($file);
                $deletedCount++;
                $this->line("Dihapus: {$filename} (Pesanan COMPLETED lebih dari 4 hari)");
            }
        }

        $this->info("Proses selesai. Total file dihapus: {$deletedCount}");
    }
}
