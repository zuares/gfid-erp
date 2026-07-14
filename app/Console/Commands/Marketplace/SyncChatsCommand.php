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
    protected $signature = 'marketplace:sync-chats';

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
        $stores = Store::where('is_active', true) // toko nonaktif dilewati
            ->whereHas('channel', fn ($q) => $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))->get();

        foreach ($stores as $store) {
            $this->info("Syncing chats for store: {$store->name}");
            try {
                $chatService->syncConversations($store, 1); // just sync 1 page to check recent chats
            } catch (\Exception $e) {
                $this->error("Failed syncing chat for {$store->name}: " . $e->getMessage());
            }
        }

        $this->info('Chat sync completed.');
    }
}
