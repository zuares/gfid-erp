<?php

namespace App\Jobs;

use App\Models\Store;
use App\Services\MarketplaceChatService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessShopeeChatWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];

    public function __construct(protected array $payload) {}

    public function handle(MarketplaceChatService $chat): void
    {
        $shopId = $this->payload['shop_id'] ?? null;
        if (! $shopId) return;

        $candidates = Store::where('external_shop_id', (string) $shopId)
            ->whereHas('channel', fn ($q) => $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))
            ->orderByDesc('is_active')
            ->get();

        // Kalau ada beberapa record untuk shop yang sama (mis. "Insight" mati &
        // "Insight Corps" aktif), pilih yang aktif & terhubung supaya bisa dibalas.
        $store = $candidates->first(fn ($s) => $s->is_active && filled($s->credential('access_token')))
            ?? $candidates->first();

        if (! $store) {
            Log::warning("Chat webhook: shop_id {$shopId} tidak dikenal.");
            return;
        }

        // Spec webchat_push: data.type = notification | message
        $dataType = (string) data_get($this->payload, 'data.type', '');

        if ($dataType === 'notification') {
            // mark_as_replied dsb — tidak ada pesan baru, cukup diabaikan
            return;
        }

        $content = data_get($this->payload, 'data.content', []);
        if (is_string($content)) {
            $decoded = json_decode($content, true);
            $content = is_array($decoded) ? $decoded : [];
        }

        if (empty($content)) {
            // Bentuk tak dikenal → fallback: sync ulang daftar percakapan
            $chat->syncConversations($store, pages: 1);
            return;
        }

        $chat->handleIncomingWebhookMessage($store, $content);
    }
}
