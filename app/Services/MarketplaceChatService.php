<?php

namespace App\Services;

use App\Events\ChatMessageReceived;
use App\Models\MarketplaceChatMessage;
use App\Models\MarketplaceConversation;
use App\Models\Store;
use App\Services\Channels\ChannelManager;
use Illuminate\Support\Facades\Log;

class MarketplaceChatService
{
    public function __construct(protected ChannelManager $manager) {}

    /**
     * Tarik daftar percakapan dari Shopee dan upsert ke DB lokal.
     * Mengembalikan jumlah percakapan yang tersinkron.
     */
    public function syncConversations(Store $store, int $pages = 2, int $pageSize = 25): int
    {
        $driver = $this->manager->driver($store);
        $synced = 0;
        $cursor = '';

        for ($i = 0; $i < $pages; $i++) {
            $res = $driver->getConversationList($store, $pageSize, $cursor);

            if (! empty($res['error'])) {
                Log::warning("Chat: gagal ambil conversation list store #{$store->id}: " . ($res['message'] ?? $res['error']));
                break;
            }

            $conversations = data_get($res, 'response.conversations', []);
            if (empty($conversations)) break;

            foreach ($conversations as $c) {
                $this->upsertConversation($store, $c);
                $synced++;
            }

            $cursor = (string) (data_get($res, 'response.page_result.next_cursor.next_timestamp_nano') ?? '');
            $hasMore = data_get($res, 'response.page_result.more', false);
            if (! $hasMore || $cursor === '') break;
        }

        return $synced;
    }

    /**
     * Tarik pesan sebuah percakapan dan upsert. Mengembalikan jumlah pesan baru.
     */
    public function syncMessages(MarketplaceConversation $conversation, int $pageSize = 50): int
    {
        $store  = $conversation->store;
        $driver = $this->manager->driver($store);

        $res = $driver->getChatMessages($store, $conversation->conversation_id, $pageSize);

        if (! empty($res['error'])) {
            Log::warning("Chat: gagal ambil pesan conversation {$conversation->conversation_id}: " . ($res['message'] ?? $res['error']));
            return 0;
        }

        $messages = data_get($res, 'response.messages', []);
        $new = 0;

        foreach ($messages as $m) {
            if ($this->upsertMessage($store, $conversation, $m)) {
                $new++;
            }
        }

        return $new;
    }

    /**
     * Kirim pesan teks. Jika $conversation null, kirim cold-start ke $toId
     * (percakapan baru akan dibuat via sync setelahnya).
     */
    public function sendText(Store $store, ?MarketplaceConversation $conversation, string $text, $toId = null): array
    {
        $driver = $this->manager->driver($store);
        $toId   = $toId ?: $conversation?->buyer_user_id;

        if (! $toId) {
            return ['error' => 'no_recipient', 'message' => 'buyer_user_id tidak ditemukan untuk percakapan ini.'];
        }

        $res = $driver->sendChatMessage($store, $toId, $text);

        if (! empty($res['error'])) {
            return $res;
        }

        $data = data_get($res, 'response', []);

        // Kalau belum ada conversation lokal, coba temukan/buat dari response
        if (! $conversation) {
            $extConvId = (string) (data_get($data, 'conversation_id') ?? '');
            if ($extConvId !== '') {
                $conversation = MarketplaceConversation::firstOrCreate(
                    ['store_id' => $store->id, 'conversation_id' => $extConvId],
                    ['buyer_user_id' => (string) $toId]
                );
            }
        }

        if ($conversation) {
            $sentAt = ! empty($data['created_timestamp'])
                ? now()->setTimestamp((int) $data['created_timestamp'])
                : now();

            MarketplaceChatMessage::updateOrCreate(
                [
                    'store_id'            => $store->id,
                    'external_message_id' => (string) (data_get($data, 'message_id') ?? ('local_' . uniqid())),
                ],
                [
                    'marketplace_conversation_id' => $conversation->id,
                    'from_role'    => 'seller',
                    'from_id'      => (string) $store->external_shop_id,
                    'message_type' => 'text',
                    'text'         => $text,
                    'content'      => ['text' => $text],
                    'sent_at'      => $sentAt,
                    'is_read'      => true,
                ]
            );

            $conversation->update([
                'last_message_type' => 'text',
                'last_message_text' => $text,
                'last_message_at'   => $sentAt,
                'is_answered'       => true,
            ]);

            event(new ChatMessageReceived(
                $store->id, $conversation->id, $conversation->conversation_id, 'seller', mb_substr($text, 0, 80)
            ));
        }

        return ['success' => true, 'conversation' => $conversation?->fresh()];
    }

    /**
     * Tandai percakapan terbaca (lokal + sisi Shopee).
     */
    public function markRead(MarketplaceConversation $conversation): void
    {
        $lastMsg = $conversation->messages()->orderByDesc('sent_at')->first();

        $conversation->update(['unread_count' => 0]);
        $conversation->messages()->where('is_read', false)->update(['is_read' => true]);

        if ($lastMsg && $lastMsg->external_message_id && ! str_starts_with($lastMsg->external_message_id, 'local_')) {
            try {
                $this->manager->driver($conversation->store)
                    ->readConversation($conversation->store, $conversation->conversation_id, $lastMsg->external_message_id);
            } catch (\Throwable $e) {
                Log::warning('Chat: gagal read_conversation di Shopee: ' . $e->getMessage());
            }
        }
    }

    /**
     * Handler pesan masuk dari webhook webchat push.
     * $content = payload['data']['content'] dari Shopee.
     */
    public function handleIncomingWebhookMessage(Store $store, array $content): void
    {
        $extConvId   = (string) (data_get($content, 'conversation_id') ?? '');
        $messageId   = (string) (data_get($content, 'message_id') ?? '');
        $messageType = (string) (data_get($content, 'message_type') ?? '');

        if ($extConvId === '') {
            Log::info('Chat webhook: payload tanpa conversation_id, di-skip.', $content);
            return;
        }

        $conversation = MarketplaceConversation::firstOrCreate(
            ['store_id' => $store->id, 'conversation_id' => $extConvId],
            []
        );

        // bundle_message (FAQ/chatbot): hanya berisi daftar message_id →
        // tarik isi lengkapnya via API get_message
        if ($messageType === 'bundle_message' || $messageId === '') {
            $new = $this->syncMessages($conversation);
            if ($new > 0) {
                $conversation->refresh();
                $conversation->increment('unread_count');
                event(new ChatMessageReceived(
                    $store->id, $conversation->id, $extConvId, 'buyer',
                    mb_substr((string) $conversation->last_message_text, 0, 80)
                ));
            }
            return;
        }

        // Lengkapi data buyer langsung dari payload webhook (tanpa panggil API):
        // from_user_name/from_id milik buyer bila pengirimnya buyer, sebaliknya to_*
        $fromRole = $this->resolveFromRole($store, $conversation, $content);
        $buyerId   = $fromRole === 'buyer' ? data_get($content, 'from_id') : data_get($content, 'to_id');
        $buyerName = $fromRole === 'buyer' ? data_get($content, 'from_user_name') : data_get($content, 'to_user_name');

        $updates = [];
        if (! $conversation->buyer_user_id && $buyerId)    $updates['buyer_user_id']  = (string) $buyerId;
        if (! $conversation->buyer_username && $buyerName) $updates['buyer_username'] = $buyerName;
        if ($updates) $conversation->update($updates);

        $isNew = $this->upsertMessage($store, $conversation, $content, fromWebhook: true);

        if ($isNew) {
            $conversation->refresh();

            if ($fromRole === 'buyer') {
                $conversation->increment('unread_count');
            }

            event(new ChatMessageReceived(
                $store->id,
                $conversation->id,
                $extConvId,
                $fromRole,
                mb_substr((string) $conversation->last_message_text, 0, 80)
            ));
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Tentukan pengirim pesan: seller atau buyer.
     * PENTING: from_id adalah USER id pengirim, bukan shop id — jadi tidak bisa
     * langsung dibandingkan dengan external_shop_id. Urutan deteksi:
     * 1. from_shop_id / to_shop_id (field spec 2025) vs shop id toko
     * 2. from_id / to_id vs buyer_user_id percakapan
     * 3. fallback: anggap buyer
     */
    private function resolveFromRole(Store $store, MarketplaceConversation $conversation, array $m): string
    {
        $shopId = (string) $store->external_shop_id;

        $fromShopId = (string) (data_get($m, 'from_shop_id') ?? '');
        if ($fromShopId !== '' && $fromShopId !== '0') {
            return $fromShopId === $shopId ? 'seller' : 'buyer';
        }

        $toShopId = (string) (data_get($m, 'to_shop_id') ?? '');
        if ($toShopId !== '' && $toShopId !== '0' && $toShopId === $shopId) {
            return 'buyer'; // pesan DITERIMA toko → pengirimnya buyer
        }

        $buyerId = (string) ($conversation->buyer_user_id ?? '');
        $fromId  = (string) (data_get($m, 'from_id') ?? '');
        $toId    = (string) (data_get($m, 'to_id') ?? '');

        if ($buyerId !== '' && $fromId !== '') {
            return $fromId === $buyerId ? 'buyer' : 'seller';
        }
        if ($buyerId !== '' && $toId !== '') {
            return $toId === $buyerId ? 'seller' : 'buyer';
        }

        return 'buyer';
    }

    private function upsertConversation(Store $store, array $c): MarketplaceConversation
    {
        $extConvId = (string) (data_get($c, 'conversation_id') ?? '');

        $lastTsNano = data_get($c, 'last_message_timestamp'); // nano
        $lastAt = $lastTsNano
            ? now()->setTimestamp((int) floor(((float) $lastTsNano) / 1_000_000_000))
            : null;

        $lastText = data_get($c, 'latest_message_content.text')
            ?? (is_string(data_get($c, 'latest_message_content')) ? data_get($c, 'latest_message_content') : null);

        $conv = MarketplaceConversation::updateOrCreate(
            ['store_id' => $store->id, 'conversation_id' => $extConvId],
            array_filter([
                'buyer_user_id'     => data_get($c, 'to_id') !== null ? (string) data_get($c, 'to_id') : null,
                'buyer_username'    => data_get($c, 'to_name'),
                'buyer_avatar'      => data_get($c, 'to_avatar'),
                'last_message_type' => data_get($c, 'latest_message_type'),
                'last_message_text' => $lastText,
                'last_message_at'   => $lastAt,
                'unread_count'      => data_get($c, 'unread_count'),
                'meta'              => $c,
            ], fn ($v) => $v !== null)
        );

        // Jika data baru, atau jika unread_count baru saja bertambah (misal tarikan Cron Job)
        if (
            ($conv->wasRecentlyCreated && $conv->unread_count > 0) ||
            ($conv->wasChanged('unread_count') && $conv->unread_count > $conv->getOriginal('unread_count'))
        ) {
            event(new ChatMessageReceived(
                $store->id,
                $conv->id,
                $extConvId,
                'buyer', // Karena unread_count naik, pasti pesan dari pembeli
                mb_substr((string) $conv->last_message_text, 0, 80)
            ));
        }

        return $conv;
    }

    /**
     * Upsert satu pesan (dari API get_message maupun webhook).
     * Return true jika pesan baru dibuat.
     */
    private function upsertMessage(Store $store, MarketplaceConversation $conversation, array $m, bool $fromWebhook = false): bool
    {
        $messageId = (string) (data_get($m, 'message_id') ?? '');
        if ($messageId === '') return false;

        // content bisa berupa array atau JSON string (khas webhook)
        $content = data_get($m, 'content');
        if (is_string($content)) {
            $decoded = json_decode($content, true);
            $content = is_array($decoded) ? $decoded : ['text' => $content];
        }
        $content = $content ?: [];

        $fromId   = (string) (data_get($m, 'from_id') ?? '');
        $fromRole = $this->resolveFromRole($store, $conversation, $m);

        $ts = data_get($m, 'created_timestamp') ?? data_get($m, 'create_time');
        $sentAt = $ts ? now()->setTimestamp((int) $ts) : now();

        $text = data_get($content, 'text');
        $type = (string) (data_get($m, 'message_type') ?? 'text');

        $row = MarketplaceChatMessage::firstOrCreate(
            ['store_id' => $store->id, 'external_message_id' => $messageId],
            [
                'marketplace_conversation_id' => $conversation->id,
                'from_role'    => $fromRole,
                'from_id'      => $fromId ?: null,
                'message_type' => $type,
                'text'         => $text,
                'content'      => $content,
                'sent_at'      => $sentAt,
                'is_read'      => $fromRole === 'seller',
            ]
        );

        if ($row->wasRecentlyCreated) {
            // Update ringkasan percakapan bila pesan ini yang terbaru
            if (! $conversation->last_message_at || $sentAt->gte($conversation->last_message_at)) {
                $conversation->update([
                    'last_message_type' => $type,
                    'last_message_text' => $text ?: "[{$type}]",
                    'last_message_at'   => $sentAt,
                    'is_answered'       => $fromRole === 'seller',
                ]);
            }
            return true;
        }

        return false;
    }
}
