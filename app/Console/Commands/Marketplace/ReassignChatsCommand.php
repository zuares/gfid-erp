<?php

namespace App\Console\Commands\Marketplace;

use App\Models\MarketplaceChatMessage;
use App\Models\MarketplaceConversation;
use App\Models\Store;
use Illuminate\Console\Command;

class ReassignChatsCommand extends Command
{
    protected $signature = 'marketplace:reassign-chats {from : ID toko asal} {to : ID toko tujuan}
        {--deactivate : Nonaktifkan toko asal setelah dipindah}
        {--force : Lewati pengecekan shop_id harus sama}';

    protected $description = 'Pindahkan semua percakapan & pesan chat dari satu toko ke toko lain (mis. Insight → Insight Corps)';

    public function handle(): int
    {
        $from = Store::find((int) $this->argument('from'));
        $to   = Store::find((int) $this->argument('to'));

        if (! $from || ! $to) {
            $this->error('Toko asal atau tujuan tidak ditemukan.');
            return self::FAILURE;
        }
        if ($from->id === $to->id) {
            $this->error('Toko asal dan tujuan sama.');
            return self::FAILURE;
        }

        // Pengaman: hanya izinkan kalau shop-nya sama (kecuali --force).
        if (! $this->option('force')
            && filled($from->external_shop_id) && filled($to->external_shop_id)
            && $from->external_shop_id !== $to->external_shop_id) {
            $this->error("shop_id berbeda ({$from->external_shop_id} vs {$to->external_shop_id}). "
                . "Kalau memang sengaja, tambahkan --force.");
            return self::FAILURE;
        }

        $convCount = MarketplaceConversation::where('store_id', $from->id)->count();
        $msgCount  = MarketplaceChatMessage::where('store_id', $from->id)->count();

        if ($convCount === 0 && $msgCount === 0) {
            $this->info("Tidak ada chat di toko \"{$from->name}\" untuk dipindah.");
            return self::SUCCESS;
        }

        if (! $this->confirm("Pindahkan {$convCount} percakapan & {$msgCount} pesan dari \"{$from->name}\" (#{$from->id}) ke \"{$to->name}\" (#{$to->id})?", true)) {
            return self::SUCCESS;
        }

        MarketplaceConversation::where('store_id', $from->id)->update(['store_id' => $to->id]);
        MarketplaceChatMessage::where('store_id', $from->id)->update(['store_id' => $to->id]);

        $this->info("Dipindah: {$convCount} percakapan, {$msgCount} pesan → \"{$to->name}\".");

        if ($this->option('deactivate')) {
            $from->update(['is_active' => false]);
            $this->info("Toko \"{$from->name}\" dinonaktifkan.");
        }

        return self::SUCCESS;
    }
}
