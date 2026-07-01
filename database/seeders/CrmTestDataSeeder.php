<?php

namespace Database\Seeders;

use App\Models\StorefrontEvent;
use App\Models\StorefrontOrder;
use App\Models\StorefrontVisitor;
use Illuminate\Database\Seeder;

class CrmTestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Bersih dulu data lama (test only)
        StorefrontEvent::truncate();
        StorefrontOrder::truncate();
        StorefrontVisitor::truncate();

        // ── Visitor 1: Repeat buyer (Siti, 2 order) ─────────────────────────
        StorefrontVisitor::create([
            'visitor_token'  => 'tok-siti-001',
            'ip_address'     => '180.244.1.1',
            'user_agent'     => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0) AppleWebKit Mobile',
            'utm_source'     => 'instagram',
            'utm_campaign'   => 'ramadan-sale',
            'customer_name'  => 'Siti Rahayu',
            'customer_phone' => '08112345678',
            'city'           => 'Bandung',
            'province'       => 'Jawa Barat',
            'first_seen_at'  => now()->subDays(30),
            'last_seen_at'   => now()->subDays(2),
        ]);

        StorefrontEvent::insert([
            ['visitor_token' => 'tok-siti-001', 'event_type' => 'page_view',       'payload' => json_encode(['url' => '/', 'route' => 'storefront.home']),                                                                                        'created_at' => now()->subDays(30)],
            ['visitor_token' => 'tok-siti-001', 'event_type' => 'product_view',    'payload' => json_encode(['slug' => 'kaos-polos', 'url' => '/products/kaos-polos', 'route' => 'storefront.product_detail']),                                   'created_at' => now()->subDays(30)],
            ['visitor_token' => 'tok-siti-001', 'event_type' => 'add_to_cart',     'payload' => json_encode(['slug' => 'kaos-polos', 'name' => 'Kaos Polos Premium', 'color' => 'Putih', 'size' => 'M', 'qty' => 2, 'price' => 85000]),          'created_at' => now()->subDays(30)],
            ['visitor_token' => 'tok-siti-001', 'event_type' => 'checkout_start',  'payload' => json_encode(['items_count' => 1]),                                                                                                                 'created_at' => now()->subDays(30)],
            ['visitor_token' => 'tok-siti-001', 'event_type' => 'order_complete',  'payload' => json_encode(['order_number' => 'GF-TEST-0001', 'total' => 185000]),                                                                               'created_at' => now()->subDays(30)],
            ['visitor_token' => 'tok-siti-001', 'event_type' => 'wa_click',        'payload' => json_encode(['order_number' => 'GF-TEST-0001']),                                                                                                   'created_at' => now()->subDays(30)],
            // Order ke-2 Siti (repeat buyer)
            ['visitor_token' => 'tok-siti-001', 'event_type' => 'product_view',    'payload' => json_encode(['slug' => 'celana-chino', 'url' => '/products/celana-chino', 'route' => 'storefront.product_detail']),                              'created_at' => now()->subDays(2)],
            ['visitor_token' => 'tok-siti-001', 'event_type' => 'add_to_cart',     'payload' => json_encode(['slug' => 'celana-chino', 'name' => 'Celana Chino Slim', 'color' => 'Navy', 'size' => '30', 'qty' => 1, 'price' => 150000]),        'created_at' => now()->subDays(2)],
            ['visitor_token' => 'tok-siti-001', 'event_type' => 'order_complete',  'payload' => json_encode(['order_number' => 'GF-TEST-0002', 'total' => 162000]),                                                                               'created_at' => now()->subDays(2)],
        ]);

        StorefrontOrder::create([
            'order_number'   => 'GF-TEST-0001',
            'visitor_token'  => 'tok-siti-001',
            'customer_name'  => 'Siti Rahayu',
            'customer_phone' => '08112345678',
            'province'       => 'Jawa Barat',
            'city'           => 'Bandung',
            'district'       => 'Coblong',
            'village'        => 'Dago',
            'address_detail' => 'Jl. Dago No.1',
            'items'          => [['slug' => 'kaos-polos', 'name' => 'Kaos Polos Premium', 'color' => 'Putih', 'size' => 'M', 'qty' => 2, 'price' => 85000]],
            'subtotal'       => 170000,
            'shipping_cost'  => 15000,
            'total_amount'   => 185000,
            'payment_method' => 'Transfer BCA',
            'status'         => 'done',
            'wa_sent_at'     => now()->subDays(30),
            'created_at'     => now()->subDays(30),
            'updated_at'     => now()->subDays(30),
        ]);

        StorefrontOrder::create([
            'order_number'   => 'GF-TEST-0002',
            'visitor_token'  => 'tok-siti-001',
            'customer_name'  => 'Siti Rahayu',
            'customer_phone' => '08112345678',
            'province'       => 'Jawa Barat',
            'city'           => 'Bandung',
            'district'       => 'Coblong',
            'village'        => 'Dago',
            'address_detail' => 'Jl. Dago No.1',
            'items'          => [['slug' => 'celana-chino', 'name' => 'Celana Chino Slim', 'color' => 'Navy', 'size' => '30', 'qty' => 1, 'price' => 150000]],
            'subtotal'       => 150000,
            'shipping_cost'  => 12000,
            'total_amount'   => 162000,
            'payment_method' => 'Transfer BCA',
            'status'         => 'pending',
            'created_at'     => now()->subDays(2),
            'updated_at'     => now()->subDays(2),
        ]);

        // ── Visitor 2: Customer biasa (Budi, 1 order, desktop) ────────────────
        StorefrontVisitor::create([
            'visitor_token'  => 'tok-budi-002',
            'ip_address'     => '36.81.1.1',
            'user_agent'     => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120',
            'utm_source'     => 'tiktok',
            'utm_campaign'   => 'flash-sale',
            'customer_name'  => 'Budi Santoso',
            'customer_phone' => '08567891234',
            'city'           => 'Surabaya',
            'province'       => 'Jawa Timur',
            'first_seen_at'  => now()->subDays(7),
            'last_seen_at'   => now()->subDays(1),
        ]);

        StorefrontEvent::insert([
            ['visitor_token' => 'tok-budi-002', 'event_type' => 'page_view',      'payload' => json_encode(['url' => '/']),                                                                                                                         'created_at' => now()->subDays(7)],
            ['visitor_token' => 'tok-budi-002', 'event_type' => 'product_view',   'payload' => json_encode(['slug' => 'kaos-polos']),                                                                                                               'created_at' => now()->subDays(7)],
            ['visitor_token' => 'tok-budi-002', 'event_type' => 'product_view',   'payload' => json_encode(['slug' => 'jaket-bomber']),                                                                                                             'created_at' => now()->subDays(7)],
            ['visitor_token' => 'tok-budi-002', 'event_type' => 'add_to_cart',    'payload' => json_encode(['slug' => 'jaket-bomber', 'name' => 'Jaket Bomber Distro', 'color' => 'Hitam', 'size' => 'L', 'qty' => 1, 'price' => 220000]),        'created_at' => now()->subDays(7)],
            ['visitor_token' => 'tok-budi-002', 'event_type' => 'checkout_start', 'payload' => json_encode(['items_count' => 1]),                                                                                                                   'created_at' => now()->subDays(6)],
            ['visitor_token' => 'tok-budi-002', 'event_type' => 'order_complete', 'payload' => json_encode(['order_number' => 'GF-TEST-0003', 'total' => 237000]),                                                                                 'created_at' => now()->subDays(6)],
        ]);

        StorefrontOrder::create([
            'order_number'   => 'GF-TEST-0003',
            'visitor_token'  => 'tok-budi-002',
            'customer_name'  => 'Budi Santoso',
            'customer_phone' => '08567891234',
            'province'       => 'Jawa Timur',
            'city'           => 'Surabaya',
            'district'       => 'Wonokromo',
            'village'        => 'Jagir',
            'address_detail' => 'Jl. Wonokromo No.5',
            'items'          => [['slug' => 'jaket-bomber', 'name' => 'Jaket Bomber Distro', 'color' => 'Hitam', 'size' => 'L', 'qty' => 1, 'price' => 220000]],
            'subtotal'       => 220000,
            'shipping_cost'  => 17000,
            'total_amount'   => 237000,
            'payment_method' => 'QRIS',
            'status'         => 'confirmed',
            'created_at'     => now()->subDays(6),
            'updated_at'     => now()->subDays(6),
        ]);

        // ── Visitor 3: Aging order (pending > 24 jam) ─────────────────────────
        StorefrontVisitor::create([
            'visitor_token'  => 'tok-rina-003',
            'ip_address'     => '110.137.1.1',
            'user_agent'     => 'Mozilla/5.0 (Linux; Android 13) Mobile Safari',
            'customer_name'  => 'Rina Wati',
            'customer_phone' => '08998887776',
            'city'           => 'Jakarta',
            'province'       => 'DKI Jakarta',
            'first_seen_at'  => now()->subDays(3),
            'last_seen_at'   => now()->subDays(2),
        ]);

        StorefrontEvent::insert([
            ['visitor_token' => 'tok-rina-003', 'event_type' => 'product_view',   'payload' => json_encode(['slug' => 'dress-casual']),                                                                                                                                  'created_at' => now()->subDays(3)],
            ['visitor_token' => 'tok-rina-003', 'event_type' => 'add_to_cart',    'payload' => json_encode(['slug' => 'dress-casual', 'name' => 'Dress Casual Polos', 'color' => 'Pink', 'size' => 'S', 'qty' => 1, 'price' => 195000]),                               'created_at' => now()->subDays(3)],
            ['visitor_token' => 'tok-rina-003', 'event_type' => 'order_complete', 'payload' => json_encode(['order_number' => 'GF-TEST-0004', 'total' => 213000]),                                                                                                       'created_at' => now()->subDays(2)],
        ]);

        StorefrontOrder::create([
            'order_number'   => 'GF-TEST-0004',
            'visitor_token'  => 'tok-rina-003',
            'customer_name'  => 'Rina Wati',
            'customer_phone' => '08998887776',
            'province'       => 'DKI Jakarta',
            'city'           => 'Jakarta',
            'district'       => 'Kebayoran Baru',
            'village'        => 'Melawai',
            'address_detail' => 'Jl. Melawai No.9',
            'items'          => [['slug' => 'dress-casual', 'name' => 'Dress Casual Polos', 'color' => 'Pink', 'size' => 'S', 'qty' => 1, 'price' => 195000]],
            'subtotal'       => 195000,
            'shipping_cost'  => 18000,
            'total_amount'   => 213000,
            'payment_method' => 'Transfer BCA',
            'status'         => 'pending',
            'created_at'     => now()->subDays(2),
            'updated_at'     => now()->subDays(2),
        ]);

        // ── Visitor 4: Prospect (add_to_cart, TIDAK order) ───────────────────
        StorefrontVisitor::create([
            'visitor_token'  => 'tok-dewi-004',
            'ip_address'     => '114.122.1.1',
            'user_agent'     => 'Mozilla/5.0 (Linux; Android 13) Mobile Safari',
            'utm_source'     => 'whatsapp',
            'customer_name'  => 'Dewi Lestari',
            'customer_phone' => '08211112222',
            'city'           => 'Yogyakarta',
            'province'       => 'DIY',
            'first_seen_at'  => now()->subDays(3),
            'last_seen_at'   => now()->subDays(3),
        ]);

        StorefrontEvent::insert([
            ['visitor_token' => 'tok-dewi-004', 'event_type' => 'product_view', 'payload' => json_encode(['slug' => 'kaos-polos']),                                                                                                                        'created_at' => now()->subDays(3)],
            ['visitor_token' => 'tok-dewi-004', 'event_type' => 'add_to_cart',  'payload' => json_encode(['slug' => 'kaos-polos', 'name' => 'Kaos Polos Premium', 'color' => 'Hitam', 'size' => 'L', 'qty' => 3, 'price' => 85000]),                      'created_at' => now()->subDays(3)],
        ]);

        $this->command->info('✅ CRM test data seeded!');
        $this->command->info('   Visitors : ' . StorefrontVisitor::count());
        $this->command->info('   Events   : ' . StorefrontEvent::count());
        $this->command->info('   Orders   : ' . StorefrontOrder::count());
    }
}
