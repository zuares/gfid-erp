<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MarketplaceOrder;
use Illuminate\Support\Facades\DB;

echo "Mulai membersihkan duplikat order (channel_order_id == booking_sn)...\n";

// Cari semua order yang channel_order_id-nya sama dengan booking_sn-nya
$ghostOrders = MarketplaceOrder::whereNotNull('booking_sn')
    ->whereColumn('channel_order_id', 'booking_sn')
    ->get();

$deletedCount = 0;
$mergedCount = 0;

foreach ($ghostOrders as $ghost) {
    // Cari order aslinya, di store yang sama, di mana channel_order_id == external_order_id asli,
    // dan booking_sn-nya sama dengan booking_sn si hantu
    $realOrder = MarketplaceOrder::where('store_id', $ghost->store_id)
        ->where('booking_sn', $ghost->booking_sn)
        ->where('id', '!=', $ghost->id)
        ->whereColumn('channel_order_id', '!=', 'booking_sn')
        ->first();

    if ($realOrder) {
        // Jika order asli ada, pesanan hantu ini aman untuk Dihapus
        echo "Menemukan duplikat hantu: {$ghost->channel_order_id}. Order asli ada (ID: {$realOrder->id}). Menghapus hantu...\n";
        $ghost->delete();
        $deletedCount++;
    } else {
        // Jika order asli TIDAK ada, berarti order hantu ini adalah satu-satunya record.
        // Kita tidak boleh menghapusnya, tapi kita biarkan saja (nanti linkOrder akan memperbaikinya saat ditarik ulang).
        echo "Order hantu {$ghost->channel_order_id} tidak memiliki pasangan asli. Dilewati.\n";
    }
}

echo "Selesai! Berhasil menghapus {$deletedCount} pesanan duplikat.\n";
