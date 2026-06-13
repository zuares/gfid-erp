<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FreshMarketplaceOrders extends Command
{
    protected $signature   = 'marketplace:fresh-orders {--force : Paksa jalan di production (BERBAHAYA)}';
    protected $description = 'Hapus semua marketplace orders + fulfillments + inventory mutations terkait. Hanya untuk testing.';

    public function handle(): int
    {
        if (app()->isProduction() && ! $this->option('force')) {
            $this->error('Command ini tidak boleh dijalankan di production!');
            $this->line('Gunakan --force jika benar-benar yakin.');
            return self::FAILURE;
        }

        if (app()->isProduction()) {
            if (! $this->confirm('⚠️  PRODUCTION environment! Lanjutkan?', false)) {
                $this->info('Dibatalkan.');
                return self::SUCCESS;
            }
        }

        $this->info('🧹 Fresh marketplace orders dimulai...');

        DB::transaction(function () {
            // 1. Hapus inventory mutations yang berasal dari fulfillment
            $mutationCount = DB::table('inventory_mutations')
                ->whereIn('source_type', ['order_fulfillment', 'order_fulfillment_substitution'])
                ->delete();
            $this->line("  ✓ Hapus {$mutationCount} inventory mutations");

            // 2. Hapus fulfillment lines
            $lineCount = DB::table('order_fulfillment_lines')->delete();
            $this->line("  ✓ Hapus {$lineCount} order fulfillment lines");

            // 3. Hapus fulfillments
            $fulfillmentCount = DB::table('order_fulfillments')->delete();
            $this->line("  ✓ Hapus {$fulfillmentCount} order fulfillments");

            // 4. Hapus marketplace order items
            $itemCount = DB::table('marketplace_order_items')->delete();
            $this->line("  ✓ Hapus {$itemCount} marketplace order items");

            // 5. Hapus marketplace orders
            $orderCount = DB::table('marketplace_orders')->delete();
            $this->line("  ✓ Hapus {$orderCount} marketplace orders");
        });

        $this->newLine();
        $this->info('✅ Selesai! Semua marketplace orders dihapus.');
        $this->line('Sekarang bisa sync ulang dari Shopee/marketplace.');

        return self::SUCCESS;
    }
}
