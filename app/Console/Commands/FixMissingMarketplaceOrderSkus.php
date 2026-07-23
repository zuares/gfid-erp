<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixMissingMarketplaceOrderSkus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marketplace:fix-missing-skus';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menyalin model_sku dan item_sku dari tabel produk marketplace ke order_items yang SKU-nya masih kosong.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Mencari order items yang kehilangan SKU (model_sku)...');

        $missingModels = DB::table('marketplace_order_items as moi')
            ->join('marketplace_product_models as mpm', 'moi.external_model_id', '=', 'mpm.model_id')
            ->where(function($q) {
                $q->whereNull('moi.model_sku')->orWhere('moi.model_sku', '');
            })
            ->whereNotNull('mpm.model_sku')
            ->where('mpm.model_sku', '!=', '')
            ->select('moi.id', 'mpm.model_sku')
            ->get();

        $countModels = 0;
        foreach ($missingModels as $item) {
            DB::table('marketplace_order_items')->where('id', $item->id)->update(['model_sku' => $item->model_sku]);
            $countModels++;
        }

        $this->info("Berhasil mengupdate {$countModels} baris model_sku.");

        $this->info('Mencari order items yang kehilangan SKU (item_sku)...');

        $missingItems = DB::table('marketplace_order_items as moi')
            ->join('marketplace_products as mp', 'moi.external_item_id', '=', 'mp.item_id')
            ->where(function($q) {
                $q->whereNull('moi.item_sku')->orWhere('moi.item_sku', '');
            })
            ->whereNotNull('mp.item_sku')
            ->where('mp.item_sku', '!=', '')
            ->select('moi.id', 'mp.item_sku')
            ->get();

        $countItems = 0;
        foreach ($missingItems as $item) {
            DB::table('marketplace_order_items')->where('id', $item->id)->update(['item_sku' => $item->item_sku]);
            $countItems++;
        }

        $this->info("Berhasil mengupdate {$countItems} baris item_sku.");
        $this->info('Selesai!');
    }
}
