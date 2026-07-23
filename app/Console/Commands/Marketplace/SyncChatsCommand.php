<?php

namespace App\Console\Commands\Marketplace;

use App\Models\Store;
use App\Services\MarketplaceChatService;
use Illuminate\Console\Command;

class SyncChatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marketplace:sync-chats
        {--pages=5 : Jumlah halaman percakapan yang ditarik per toko (25/percakapan per halaman)}
        {--reconcile : Tarik pesan asli untuk chat yang masih berstatus "belum dibalas/belum dibaca" dan koreksi is_answered (backfill)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync marketplace chat conversations periodically for fallback mechanism';

    /**
     * Execute the console command.
     */
    public function handle(MarketplaceChatService $chatService)
    {
        $this->info('Starting chat sync...');
        // Tarik beberapa halaman (default 5 = ~125 percakapan) agar percakapan lama
        // ikut ter-refresh, bukan hanya 25 terbaru. syncConversations berhenti sendiri
        // begitu tidak ada halaman berikutnya, jadi toko dengan sedikit chat tetap hemat.
        // Ini yang membuat status "sudah dibalas / sudah dibaca" (di-set di aplikasi
        // Shopee) tersinkron balik ke aplikasi ini secara otomatis.
        $pages = max(1, (int) $this->option('pages'));

        $stores = Store::where('is_active', true) // toko nonaktif dilewati
            ->whereHas('channel', fn ($q) => $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))->get();

        $reconcile = (bool) $this->option('reconcile');

        foreach ($stores as $store) {
            $this->info("Syncing chats for store: {$store->name}");
            try {
                $chatService->syncConversations($store, $pages);

                if ($reconcile) {
                    $r = $chatService->reconcileAnswered($store);
                    $this->info("  reconcile: {$r['scanned']} dipindai, {$r['fixed']} status dikoreksi");
                }
            } catch (\Exception $e) {
                $this->error("Failed syncing chat for {$store->name}: " . $e->getMessage());
            }
        }

        $this->info('Chat sync completed.');
    }
}
