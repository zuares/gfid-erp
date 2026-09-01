<?php

namespace App\Console\Commands;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\SkuMapping;
use App\Models\Store;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SeedMarketplaceOrders extends Command
{
    protected $signature = 'marketplace:seed-orders
                            {--count=5 : Jumlah order yang dibuat}
                            {--status=READY_TO_SHIP : Status order (READY_TO_SHIP / PROCESSED)}
                            {--store= : ID store (default: store pertama)}';

    protected $description = '[DEV ONLY] Buat dummy marketplace orders untuk testing. Tidak jalan di production.';

    /** Nama pembeli dummy */
    private array $buyers = [
        'budi_santoso88', 'siti_rahayu22', 'agus_wijaya99', 'dewi_lestari7',
        'rizky_pratama', 'nurul_hidayah', 'arif_firmansyah', 'maya_kusuma',
        'fandi_ahmadi', 'linda_permata', 'hendra_setiawan', 'rina_marlina',
        'doni_kurniawan', 'yuli_astuti55', 'wahyu_eko123', 'fitri_amalia',
        'bagas_prasetyo', 'nadia_safitri', 'kevin_orlando9', 'ayu_rahmawati',
    ];

    /** Kurir dummy */
    private array $carriers = ['JNE', 'J&T Express', 'SiCepat', 'AnterAja', 'SPX Express', 'GoSend'];

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('Command ini tidak boleh dijalankan di production!');
            return self::FAILURE;
        }

        $count  = (int) $this->option('count');
        $status = strtoupper($this->option('status'));

        if (! in_array($status, ['READY_TO_SHIP', 'PROCESSED'])) {
            $this->error("Status tidak valid: {$status}. Gunakan READY_TO_SHIP atau PROCESSED.");
            return self::FAILURE;
        }

        // ── Pilih store ──────────────────────────────────────────────────────
        $storeId = $this->option('store');
        $store   = $storeId
            ? Store::find($storeId)
            : Store::first();

        if (! $store) {
            $this->error('Tidak ada store di database. Buat store dulu lewat /marketplace/toko.');
            return self::FAILURE;
        }

        $this->line("📦 Store: <info>{$store->name}</info> (ID: {$store->id})");

        // ── Ambil SKU mappings yang ada (pakai ini sebagai item dummy) ───────
        $mappings = SkuMapping::with('item')
            ->whereNotNull('item_id')
            ->get();

        if ($mappings->isEmpty()) {
            $this->warn('⚠  Tidak ada SKU Mapping di database — order akan dibuat tanpa mapped items.');
            $this->warn('   Order tetap dibuat tapi akan muncul di tab Belum Mapping.');
        }

        // ── Generate orders ──────────────────────────────────────────────────
        $this->info("🛍  Membuat {$count} dummy order ({$status})...");
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $created = 0;
        for ($i = 0; $i < $count; $i++) {
            $this->createDummyOrder($store, $status, $mappings);
            $created++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✅ {$created} dummy order berhasil dibuat di store \"{$store->name}\".");
        $this->line('   Cek di /marketplace/orders → tab Perlu Proses.');

        return self::SUCCESS;
    }

    private function createDummyOrder(Store $store, string $status, $mappings): void
    {
        $now       = now();
        $orderedAt = $now->copy()->subMinutes(rand(5, 1440)); // 0–24 jam lalu
        $channelId = $this->generateOrderId();

        $order = MarketplaceOrder::create([
            'store_id'         => $store->id,
            'external_order_id'=> $channelId,
            'channel_order_id' => $channelId,
            // Order dummy ini reguler; booking_sn hanya untuk Pesanan Kilat.
            'booking_sn'       => null,
            'order_date'       => $orderedAt,
            'status'           => 'new',
            'order_status'     => $status,
            'buyer_username'   => $this->buyers[array_rand($this->buyers)],
            'payment_method'   => rand(0, 1) ? 'ShopeePay' : 'COD',
            'shipping_carrier' => $this->carriers[array_rand($this->carriers)],
            'currency'         => 'IDR',
            'ordered_at'       => $orderedAt,
            'synced_at'        => $now,
            'total_amount'     => 0, // di-update setelah items dibuat
            'meta'             => ['is_dummy' => true],
        ]);

        // ── Buat 1–3 items per order ─────────────────────────────────────────
        $itemCount   = rand(1, min(3, max(1, $mappings->count())));
        $totalAmount = 0;

        // Pilih mapping random tanpa duplikat dalam 1 order
        $selected = $mappings->isNotEmpty()
            ? $mappings->random(min($itemCount, $mappings->count()))
            : collect();

        // Jika mapping kurang dari itemCount, isi sisa dengan item tanpa mapping
        $items = collect();
        foreach ($selected as $m) {
            $items->push([
                'mapping' => $m,
                'mapped'  => true,
            ]);
        }
        while ($items->count() < $itemCount) {
            $items->push(['mapping' => null, 'mapped' => false]);
        }

        foreach ($items as $entry) {
            $qty   = rand(1, 3);
            $price = rand(15, 200) * 1000; // 15rb – 200rb

            if ($entry['mapped'] && $entry['mapping']) {
                $m        = $entry['mapping'];
                $itemName = $m->item?->name ?? 'Produk ' . $m->marketplace_sku;
                $sku      = $m->marketplace_sku;
            } else {
                $itemName = 'Produk Sample ' . Str::upper(Str::random(4));
                $sku      = 'SKU-' . Str::upper(Str::random(6));
            }

            MarketplaceOrderItem::create([
                'order_id'             => $order->id,
                'marketplace_order_id' => $order->id,
                'external_item_id'     => rand(100000, 999999),
                'external_model_id'    => rand(1000000, 9999999),
                'item_name'            => $itemName,
                'item_sku'             => $sku,
                'model_sku'            => $sku,
                'qty'                  => $qty,
                'price'                => $price,
            ]);

            $totalAmount += $qty * $price;
        }

        // Update total_amount
        $order->update(['total_amount' => $totalAmount]);
    }

    /**
     * Generate Shopee-style order ID: YYMMDDXXXXXXXX
     */
    private function generateOrderId(): string
    {
        return now()->format('ymd') . strtoupper(Str::random(8));
    }
}
