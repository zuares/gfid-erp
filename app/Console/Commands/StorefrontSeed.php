<?php

namespace App\Console\Commands;

use App\Models\StorefrontEvent;
use App\Models\StorefrontOrder;
use App\Models\StorefrontVisitor;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StorefrontSeed extends Command
{
    protected $signature   = 'storefront:seed
                                {--orders=60    : Jumlah order yang dibuat}
                                {--abandoned=20 : Jumlah abandoned cart (prospects)}
                                {--days=60      : Sebaran hari ke belakang}
                                {--only=        : Hanya jalankan bagian tertentu: orders|abandoned}
                                {--yes          : Skip konfirmasi}';
    protected $description = 'Seed data demo untuk storefront CRM (hanya di APP_DB_MODE=dev)';

    // ── Data dummy ──────────────────────────────────────────────────────────

    private array $names = [
        'Budi Santoso', 'Dewi Rahayu', 'Andi Wijaya', 'Siti Nurhaliza', 'Rudi Hermawan',
        'Rina Susanti', 'Agus Prasetyo', 'Fitri Handayani', 'Hendra Kusuma', 'Yanti Lestari',
        'Doni Firmansyah', 'Maya Indah', 'Fajar Nugroho', 'Lina Marlina', 'Bayu Setiawan',
        'Nita Wulandari', 'Eko Purnomo', 'Sri Wahyuni', 'Rizal Maulana', 'Tuti Andriani',
        'Arif Rahman', 'Dian Permata', 'Wahyu Hidayat', 'Novita Sari', 'Irwan Saputra',
    ];

    private array $phones = [
        '081234', '081356', '082112', '082234', '085712',
        '085813', '087812', '087756', '088112', '089612',
        '081298', '082345', '083456', '085678', '087890',
        '088901', '089012', '081123', '082678', '085901',
        '087123', '088234', '089345', '081456', '082567',
    ];

    private array $cities = [
        ['city' => 'Jakarta Selatan', 'province' => 'DKI Jakarta'],
        ['city' => 'Jakarta Barat',   'province' => 'DKI Jakarta'],
        ['city' => 'Jakarta Timur',   'province' => 'DKI Jakarta'],
        ['city' => 'Bandung',         'province' => 'Jawa Barat'],
        ['city' => 'Bekasi',          'province' => 'Jawa Barat'],
        ['city' => 'Depok',           'province' => 'Jawa Barat'],
        ['city' => 'Bogor',           'province' => 'Jawa Barat'],
        ['city' => 'Tangerang',       'province' => 'Banten'],
        ['city' => 'Surabaya',        'province' => 'Jawa Timur'],
        ['city' => 'Malang',          'province' => 'Jawa Timur'],
        ['city' => 'Yogyakarta',      'province' => 'DI Yogyakarta'],
        ['city' => 'Semarang',        'province' => 'Jawa Tengah'],
        ['city' => 'Medan',           'province' => 'Sumatera Utara'],
        ['city' => 'Makassar',        'province' => 'Sulawesi Selatan'],
        ['city' => 'Denpasar',        'province' => 'Bali'],
        ['city' => 'Palembang',       'province' => 'Sumatera Selatan'],
        ['city' => 'Batam',           'province' => 'Kepulauan Riau'],
        ['city' => 'Pekanbaru',       'province' => 'Riau'],
    ];

    private array $userAgents = [
        'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Mobile/15E148 Safari/604.1',
        'Mozilla/5.0 (Linux; Android 13; Samsung SM-A536B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Mobile Safari/537.36',
        'Mozilla/5.0 (Linux; Android 12; Redmi Note 11) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Mobile Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.6367.82 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Linux; Android 13; OPPO CPH2219) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Mobile Safari/537.36',
        'Mozilla/5.0 (Linux; Android 12; vivo V2207) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Mobile Safari/537.36',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1',
        'Mozilla/5.0 (Linux; Android 11; realme RMX3085) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:125.0) Gecko/20100101 Firefox/125.0',
    ];

    private array $paymentMethods = [
        'Transfer BCA', 'Transfer BCA', 'Transfer BCA',
        'Transfer Mandiri', 'Transfer Mandiri',
        'Transfer BNI', 'Transfer BRI',
        'QRIS', 'QRIS',
        'COD',
    ];

    private array $statuses = [
        'done', 'done', 'done', 'done', 'done',
        'shipped', 'shipped', 'shipped',
        'processing', 'processing',
        'confirmed', 'confirmed',
        'pending',
        'cancelled',
    ];

    // ── Run ─────────────────────────────────────────────────────────────────

    public function handle(): int
    {
        if (env('APP_DB_MODE') !== 'dev') {
            $this->error('Command ini hanya tersedia saat APP_DB_MODE=dev.');
            return 1;
        }

        $targetOrders = (int) $this->option('orders');
        $days         = (int) $this->option('days');
        $existing     = StorefrontOrder::count();

        $this->info("Storefront Seeder");
        $this->line("  Target   : {$targetOrders} order");
        $this->line("  Sebaran  : {$days} hari terakhir");
        $this->line("  Existing : {$existing} order");

        if ($existing > 0 && ! $this->option('yes')) {
            if (! $this->confirm("Sudah ada {$existing} order. Lanjutkan tambah data?", true)) {
                $this->line('Dibatalkan.');
                return 0;
            }
        }

        // Ambil produk dari DB atau pakai fallback
        $products = $this->getProducts();
        if (empty($products)) {
            $this->warn('Tidak ada produk di DB — pakai produk dummy.');
        }

        // Build customer pool
        $customers = $this->buildCustomerPool();

        $bar = $this->output->createProgressBar($targetOrders);
        $bar->start();

        $created = 0;
        for ($i = 0; $i < $targetOrders; $i++) {
            $customer  = $customers[array_rand($customers)];
            $orderDate = $this->randomDate($days);
            $items     = $this->randomItems($products);
            $subtotal  = array_sum(array_column($items, '_line_total'));
            $shipping  = $this->randomShipping();
            $status    = $this->randomStatus($orderDate);

            $dateStr     = $orderDate->format('Ymd');
            $todayCount  = StorefrontOrder::whereDate('created_at', $orderDate->toDateString())->count()
                         + $created + 1;
            $orderNumber = 'GF-' . $dateStr . '-' . str_pad($todayCount, 4, '0', STR_PAD_LEFT);

            $token = $customer['token'];

            $order = StorefrontOrder::create([
                'order_number'    => $orderNumber,
                'visitor_token'   => $token,
                'customer_name'   => $customer['name'],
                'customer_phone'  => $customer['phone'],
                'province'        => $customer['province'],
                'city'            => $customer['city'],
                'address_detail'  => 'Jl. ' . Str::random(6) . ' No.' . rand(1, 99),
                'postal_code'     => (string) rand(10000, 99999),
                'items'           => array_map(fn($it) => array_diff_key($it, ['_line_total' => 0]), $items),
                'subtotal'        => $subtotal,
                'shipping_cost'   => $shipping,
                'total_amount'    => $subtotal + $shipping,
                'shipping_courier'=> $this->randomCourier(),
                'payment_method'  => $this->paymentMethods[array_rand($this->paymentMethods)],
                'status'          => $status,
                'wa_sent_at'      => in_array($status, ['confirmed', 'processing', 'shipped', 'done']) && rand(0, 1)
                    ? $orderDate->copy()->addMinutes(rand(5, 60))
                    : null,
                'created_at'      => $orderDate,
                'updated_at'      => $orderDate,
            ]);

            // Upsert visitor record
            $this->upsertVisitor($token, $customer, $orderDate);

            // Buat beberapa events
            $this->createEvents($token, $items, $orderDate, $status);

            $created++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $total = StorefrontOrder::count();
        $this->info("✓ {$created} order berhasil dibuat. Total sekarang: {$total}");

        // Seed abandoned carts juga (kecuali --only=orders)
        if ($this->option('only') !== 'orders') {
            $this->newLine();
            $this->seedAbandoned($products, $days);
        }

        return 0;
    }

    // ── Seed abandoned carts (prospects) ────────────────────────────────────

    private function seedAbandoned(array $products, int $days): void
    {
        $target    = (int) $this->option('abandoned');
        $customers = $this->buildCustomerPool();

        // Buat pool terpisah — nama + phone berbeda dari customer yang sudah order
        // agar tidak overlap dan appear sebagai prospects (no order)
        $abandonedNames = [
            'Kevin Pratama', 'Sari Oktaviani', 'Iqbal Fauzi', 'Melinda Putri', 'Dimas Satria',
            'Rika Amelia', 'Reza Kurniawan', 'Lusi Handayani', 'Taufik Hidayat', 'Nurul Aini',
            'Hafiz Ramadhan', 'Cindy Maharani', 'Riki Gunawan', 'Vera Susanti', 'Dedy Prayoga',
            'Wulan Dari', 'Ihsan Maulana', 'Putri Rahayu', 'Galih Permana', 'Aisyah Fitri',
        ];
        $abandonedPhones = [
            '0895', '0896', '0897', '0898', '0899',
            '0851', '0852', '0853', '0855', '0856',
            '0831', '0832', '0833', '0838', '0813',
            '0814', '0815', '0816', '0817', '0818',
        ];

        $this->info("Seeding {$target} abandoned cart (prospects)...");
        $bar = $this->output->createProgressBar($target);
        $bar->start();

        for ($i = 0; $i < $target; $i++) {
            $nameIdx  = $i % count($abandonedNames);
            $name     = $abandonedNames[$nameIdx];
            $loc      = $this->cities[array_rand($this->cities)];

            // Sebagian anonymous (30%), sebagian ada HP (70%)
            $isAnon   = rand(0, 9) < 3;
            $phone    = $isAnon ? null : ($abandonedPhones[$nameIdx % count($abandonedPhones)] . str_pad(($i + 1) * 43 % 10000, 4, '0', STR_PAD_LEFT));

            $token    = 'abnd_' . Str::random(43);
            $cartDate = $this->randomDate($days);
            $items    = $this->randomItems($products);

            // Visitor record
            StorefrontVisitor::create([
                'visitor_token'  => $token,
                'ip_address'     => '127.0.0.1',
                'user_agent'     => $this->userAgents[array_rand($this->userAgents)],
                'customer_name'  => $isAnon ? null : $name,
                'customer_phone' => $phone,
                'city'           => $loc['city'],
                'province'       => $loc['province'],
                'first_seen_at'  => $cartDate->copy()->subMinutes(rand(5, 40)),
                'last_seen_at'   => $cartDate,
                'created_at'     => $cartDate,
                'updated_at'     => $cartDate,
            ]);

            // Page views dulu
            foreach (['storefront.home', 'storefront.products'] as $route) {
                $t = $cartDate->copy()->subMinutes(rand(15, 40));
                StorefrontEvent::create(['visitor_token' => $token, 'event_type' => 'page_view',
                    'payload' => ['route' => $route, 'url' => config('app.url') . '/' . ltrim(str_replace('storefront.', '', $route), '.')],
                    'created_at' => $t, 'updated_at' => $t]);
            }

            // Add to cart — tapi TIDAK ada order
            foreach ($items as $item) {
                $t = $cartDate->copy()->subMinutes(rand(2, 14));
                StorefrontEvent::create(['visitor_token' => $token, 'event_type' => 'product_view',
                    'payload' => ['route' => 'storefront.product_detail', 'slug' => $item['slug'], 'url' => config('app.url') . '/products/' . $item['slug']],
                    'created_at' => $t, 'updated_at' => $t]);

                StorefrontEvent::create(['visitor_token' => $token, 'event_type' => 'add_to_cart',
                    'payload' => ['slug' => $item['slug'], 'name' => $item['name'], 'price' => $item['price'], 'qty' => $item['qty'], 'color' => $item['color'], 'size' => $item['size']],
                    'created_at' => $cartDate, 'updated_at' => $cartDate]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✓ {$target} abandoned cart berhasil dibuat.");
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function buildCustomerPool(): array
    {
        $pool = [];
        foreach ($this->names as $i => $name) {
            $phone  = ($this->phones[$i] ?? '08121') . str_pad($i * 37 % 10000, 4, '0', STR_PAD_LEFT);
            $loc    = $this->cities[array_rand($this->cities)];
            $pool[] = [
                'name'     => $name,
                'phone'    => $phone,
                'city'     => $loc['city'],
                'province' => $loc['province'],
                'token'    => 'seed_' . Str::random(44),
                'ua'       => $this->userAgents[array_rand($this->userAgents)],
            ];
        }
        return $pool;
    }

    private function getProducts(): array
    {
        try {
            $products = \App\Models\StorefrontProduct::where('is_published', true)
                ->with(['variants' => fn($q) => $q->where('is_active', true), 'sizes' => fn($q) => $q->where('is_active', true)])
                ->get();

            if ($products->isEmpty()) return $this->fallbackProducts();

            return $products->map(function ($p) {
                $variant = $p->variants->first();
                $size    = $p->sizes->first();
                return [
                    'slug'  => $p->slug,
                    'name'  => $p->name,
                    'price' => $variant?->price_override ?? $p->base_price,
                    'color' => $variant?->color_name ?? 'Hitam',
                    'sizes' => $p->sizes->pluck('size_label')->toArray() ?: ['M', 'L', 'XL'],
                ];
            })->toArray();
        } catch (\Throwable) {
            return $this->fallbackProducts();
        }
    }

    private function fallbackProducts(): array
    {
        return [
            ['slug' => 'kaos-basic',   'name' => 'Kaos Basic Garuda', 'price' => 89000,  'color' => 'Hitam', 'sizes' => ['S','M','L','XL']],
            ['slug' => 'polo-shirt',   'name' => 'Polo Shirt Premium', 'price' => 149000, 'color' => 'Navy',  'sizes' => ['M','L','XL']],
            ['slug' => 'celana-cargo', 'name' => 'Celana Cargo',       'price' => 189000, 'color' => 'Khaki', 'sizes' => ['30','32','34']],
            ['slug' => 'hoodie-gfid',  'name' => 'Hoodie GFID',        'price' => 259000, 'color' => 'Abu',   'sizes' => ['M','L','XL','XXL']],
            ['slug' => 'kemeja-linen', 'name' => 'Kemeja Linen',       'price' => 179000, 'color' => 'Putih', 'sizes' => ['M','L','XL']],
        ];
    }

    private function randomItems(array $products): array
    {
        if (empty($products)) $products = $this->fallbackProducts();

        $count  = rand(1, 3);
        $picked = array_rand($products, min($count, count($products)));
        if (! is_array($picked)) $picked = [$picked];

        return array_map(function ($idx) use ($products) {
            $p   = $products[$idx];
            $qty = rand(1, 3);
            return [
                'slug'        => $p['slug'],
                'name'        => $p['name'],
                'color'       => $p['color'],
                'size'        => $p['sizes'][array_rand($p['sizes'])] ?? 'M',
                'price'       => $p['price'],
                'qty'         => $qty,
                '_line_total' => $p['price'] * $qty,
            ];
        }, $picked);
    }

    private function randomShipping(): int
    {
        return [0, 10000, 15000, 20000, 25000, 35000][array_rand([0, 10000, 15000, 20000, 25000, 35000])];
    }

    private function randomCourier(): string
    {
        return ['JNE REG', 'JNE YES', 'J&T Express', 'SiCepat REG', 'Anteraja', 'COD'][array_rand(['JNE REG', 'JNE YES', 'J&T Express', 'SiCepat REG', 'Anteraja', 'COD'])];
    }

    private function randomDate(int $days): Carbon
    {
        // Lebih banyak order di hari-hari terbaru (growth simulation)
        $weight = rand(0, 100);
        $daysAgo = $weight < 40 ? rand(0, 7)
                 : ($weight < 70 ? rand(7, 20)
                 : rand(20, $days));
        $date = now()->subDays($daysAgo);
        $date->setTime(rand(7, 22), rand(0, 59));
        return $date;
    }

    private function randomStatus(Carbon $date): string
    {
        // Order lama lebih mungkin sudah done/shipped
        $daysAgo = $date->diffInDays(now());
        if ($daysAgo > 14) {
            $pool = ['done','done','done','done','shipped','done','cancelled'];
        } elseif ($daysAgo > 3) {
            $pool = ['done','done','shipped','shipped','processing','confirmed','cancelled'];
        } else {
            $pool = ['pending','pending','confirmed','processing','shipped','done'];
        }
        return $pool[array_rand($pool)];
    }

    private function upsertVisitor(string $token, array $customer, Carbon $date): void
    {
        $existing = StorefrontVisitor::where('visitor_token', $token)->first();
        if ($existing) {
            if ($date->gt($existing->last_seen_at)) {
                $existing->update(['last_seen_at' => $date, 'customer_name' => $customer['name'], 'customer_phone' => $customer['phone']]);
            }
            return;
        }

        StorefrontVisitor::create([
            'visitor_token'   => $token,
            'ip_address'      => '127.0.0.1',
            'user_agent'      => $customer['ua'],
            'customer_name'   => $customer['name'],
            'customer_phone'  => $customer['phone'],
            'city'            => $customer['city'],
            'province'        => $customer['province'],
            'first_seen_at'   => $date->copy()->subMinutes(rand(10, 60)),
            'last_seen_at'    => $date,
            'created_at'      => $date,
            'updated_at'      => $date,
        ]);
    }

    private function createEvents(string $token, array $items, Carbon $orderDate, string $status): void
    {
        $t = $orderDate->copy()->subMinutes(rand(10, 45));

        // page views
        foreach (['storefront.home', 'storefront.products'] as $route) {
            StorefrontEvent::create(['visitor_token' => $token, 'event_type' => 'page_view',
                'payload' => ['route' => $route, 'url' => config('app.url') . '/' . str_replace('storefront.', '', $route)],
                'created_at' => $t, 'updated_at' => $t]);
            $t->addMinutes(rand(1, 5));
        }

        // product views + add to cart
        foreach ($items as $item) {
            StorefrontEvent::create(['visitor_token' => $token, 'event_type' => 'product_view',
                'payload' => ['route' => 'storefront.product_detail', 'slug' => $item['slug'], 'url' => config('app.url') . '/products/' . $item['slug']],
                'created_at' => $t, 'updated_at' => $t]);
            $t->addMinutes(rand(1, 8));

            StorefrontEvent::create(['visitor_token' => $token, 'event_type' => 'add_to_cart',
                'payload' => ['slug' => $item['slug'], 'name' => $item['name'], 'price' => $item['price'], 'qty' => $item['qty'], 'color' => $item['color'], 'size' => $item['size']],
                'created_at' => $t, 'updated_at' => $t]);
            $t->addMinutes(rand(1, 3));

            // duration events
            StorefrontEvent::create(['visitor_token' => $token, 'event_type' => 'page_view_duration',
                'payload' => ['route' => 'storefront.product_detail', 'slug' => $item['slug'], 'seconds' => rand(30, 300)],
                'created_at' => $t, 'updated_at' => $t]);
        }

        // order complete
        if (! in_array($status, ['cancelled'])) {
            StorefrontEvent::create(['visitor_token' => $token, 'event_type' => 'order_complete',
                'payload' => ['total' => array_sum(array_column($items, '_line_total'))],
                'created_at' => $orderDate, 'updated_at' => $orderDate]);
        }
    }
}
