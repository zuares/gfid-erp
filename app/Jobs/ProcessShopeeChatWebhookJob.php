<?php

namespace App\Jobs;

use App\Models\MarketplaceConversation;
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

    public function __construct(
        protected array $payload,
        protected ?string $rawBody = null,
        protected ?int $webhookLogId = null
    ) {}

    public function handle(MarketplaceChatService $chat): void
    {
        $rawPayload = $this->decodedRawPayload();

        $shopId = $rawPayload['shop_id'] ?? $this->payload['shop_id'] ?? null;
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
        $dataType = (string) data_get($rawPayload, 'data.type', data_get($this->payload, 'data.type', ''));
        $content = data_get($rawPayload, 'data.content', data_get($this->payload, 'data.content', []));
        if (is_string($content)) {
            $decoded = json_decode($content, true);
            $content = is_array($decoded) ? $decoded : [];
        }

        if ($dataType === 'notification') {
            // Notification tidak membawa isi pesan, tetapi tetap bisa menjadi
            // sinyal perubahan status percakapan. Refresh list untuk sinkron.
            $this->refreshConversationState($chat, $store, $rawPayload, $content);
            return;
        }

        $conversationId = $this->resolveConversationId($rawPayload, $content);

        if (empty($content)) {
            // Bentuk tak dikenal / bundle_message tanpa payload lengkap:
            // refresh list lalu tarik history pesan percakapan bila bisa.
            $this->refreshConversationState($chat, $store, $rawPayload, $content, $this->webhookLogId);
            return;
        }

        $chat->handleIncomingWebhookMessage($store, $content, $rawPayload, $this->webhookLogId);
    }

    protected function decodedRawPayload(): array
    {
        if (is_string($this->rawBody) && $this->rawBody !== '') {
            $decoded = json_decode($this->rawBody, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return $this->payload;
    }

    protected function resolveConversationId(array $rawPayload, array $content): string
    {
        foreach ([
            data_get($content, 'conversation_id'),
            data_get($rawPayload, 'conversation_id'),
            data_get($rawPayload, 'data.conversation_id'),
            data_get($rawPayload, 'data.content.conversation_id'),
        ] as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return '';
    }

    protected function refreshConversationState(
        MarketplaceChatService $chat,
        Store $store,
        array $rawPayload,
        array $content,
        ?int $webhookLogId = null
    ): void
    {
        $chat->syncConversations($store, pages: 1);

        $conversationId = $this->resolveConversationId($rawPayload, $content);
        if ($conversationId === '') {
            return;
        }

        $conversation = MarketplaceConversation::where('store_id', $store->id)
            ->where('conversation_id', $conversationId)
            ->first();

        if ($conversation) {
            $chat->syncMessages($conversation, triggerWebhookLogId: $webhookLogId);
        }
    }
}
