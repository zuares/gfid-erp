<?php

namespace App\Services;

use App\Events\ChatMessageReceived;
use App\Models\MarketplaceChatMessage;
use App\Models\MarketplaceConversation;
use App\Models\MarketplaceOrder;
use App\Models\Store;
use App\Services\Marketplace\MarketplaceApiGateway;
use App\Services\Channels\ChannelManager;
use Illuminate\Support\Facades\Log;

class MarketplaceChatService
{
    public function __construct(protected MarketplaceApiGateway $gateway) {}

    /**
     * Tarik daftar percakapan dari Shopee dan upsert ke DB lokal.
     * Mengembalikan jumlah percakapan yang tersinkron.
     */
    public function syncConversations(Store $store, int $pages = 2, int $pageSize = 25): int
    {
        if (! $store->is_active || blank($store->credential('access_token'))) {
            // Toko nonaktif atau belum terkonfigurasi (record placeholder tanpa token):
            // tidak ada percakapan untuk disinkron, dan memanggil API hanya menghasilkan
            // warning "Invalid access_token". Lewati diam-diam.
            return 0;
        }

        $driver = $this->gateway;
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
                $conversation = $this->upsertConversation($store, $c);
                $synced++;

                // Percakapan yang baru / berubah biasanya punya pesan baru yang
                // perlu dibackfill juga agar audit chat tidak bolong.
                if ($this->shouldBackfillConversationMessages($conversation)) {
                    $this->syncMessages($conversation, pageSize: 50, maxPages: 2);
                }
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
    public function syncMessages(
        MarketplaceConversation $conversation,
        int $pageSize = 50,
        int $maxPages = 10,
        ?int $triggerWebhookLogId = null
    ): int
    {
        $store  = $conversation->store;
        $driver = $this->gateway;
        $new = 0;

        $offset = '';

        for ($page = 0; $page < max(1, $maxPages); $page++) {
            $res = $driver->getChatMessages($store, $conversation->conversation_id, $pageSize, $offset);

            if (! empty($res['error'])) {
                Log::warning("Chat: gagal ambil pesan conversation {$conversation->conversation_id}: " . ($res['message'] ?? $res['error']));
                break;
            }

            $messages = data_get($res, 'response.messages', []);
            if (empty($messages)) {
                break;
            }

            foreach ($messages as $m) {
                if ($this->upsertMessage(
                    $store,
                    $conversation,
                    $m,
                    'sync_api',
                    $m,
                    array_merge($res, $triggerWebhookLogId ? ['trigger_webhook_log_id' => $triggerWebhookLogId] : []),
                    $triggerWebhookLogId
                )) {
                    $new++;
                }
            }

            $nextOffset = $this->resolveChatMessageOffset($res, $offset);
            $hasMore    = $this->chatMessagesHasMore($res, $messages, $nextOffset, $offset, $pageSize);

            if (! $hasMore || $nextOffset === '' || $nextOffset === $offset) {
                break;
            }

            $offset = $nextOffset;
        }

        return $new;
    }

    /**
     * Cek apakah seller masih boleh mengirim chat ke percakapan ini menurut
     * aturan Shopee: (1) pembeli mengirim chat dalam 7 hari terakhir, ATAU
     * (2) ada pesanan dari pembeli itu dalam 30 hari terakhir. Kalau tidak,
     * Shopee akan menolak send_message.
     *
     * @return array{can_reply:bool, reason:?string}
     */
    public function canReply(MarketplaceConversation $conversation): array
    {
        // (1) Jendela chat 7 hari sejak pesan TERAKHIR dari buyer.
        $lastBuyer = $conversation->messages()
            ->where('from_role', 'buyer')
            ->orderByDesc('sent_at')
            ->first();

        if ($lastBuyer && $lastBuyer->sent_at && $lastBuyer->sent_at->gt(now()->subDays(7))) {
            return ['can_reply' => true, 'reason' => null];
        }

        // (2) Jendela pesanan 30 hari — cocokkan buyer_username lintas toko
        // milik shop yang sama (external_shop_id).
        $store    = $conversation->store;
        $username = $conversation->buyer_username;

        if ($store && filled($username)) {
            $storeIds = filled($store->external_shop_id)
                ? Store::where('external_shop_id', $store->external_shop_id)->pluck('id')
                : collect([$store->id]);

            $hasRecentOrder = MarketplaceOrder::whereIn('store_id', $storeIds)
                ->where('buyer_username', $username)
                ->where('order_date', '>=', now()->subDays(30))
                ->exists();

            if ($hasRecentOrder) {
                return ['can_reply' => true, 'reason' => null];
            }
        }

        return [
            'can_reply' => false,
            'reason'    => 'Jendela balasan Shopee sudah lewat. Pembeli terakhir chat lebih dari 7 hari lalu dan tidak ada pesanan dalam 30 hari terakhir, jadi Shopee tidak mengizinkan seller mengirim chat di percakapan ini.',
        ];
    }

    /**
     * Rekonsiliasi status percakapan yang tampak "belum dibalas / belum dibaca"
     * padahal sudah ditangani di aplikasi Shopee. Untuk tiap percakapan yang
     * ditandai (is_answered = false ATAU unread_count > 0), tarik pesan aslinya
     * dari Shopee — ini mengimpor balasan yang dikirim seller LANGSUNG dari
     * aplikasi Shopee (di luar aplikasi ini) — lalu hitung ulang is_answered
     * dari peran pengirim pesan terakhir. Sumber paling otoritatif.
     *
     * @return array{scanned:int, fixed:int}
     */
    public function reconcileAnswered(Store $store, int $limit = 100): array
    {
        if (! $store->is_active || blank($store->credential('access_token'))) {
            return ['scanned' => 0, 'fixed' => 0];
        }

        $convs = MarketplaceConversation::where('store_id', $store->id)
            ->where(fn ($q) => $q->where('is_answered', false)->orWhere('unread_count', '>', 0))
            ->orderByDesc('last_message_at')
            ->limit($limit)
            ->get();

        $fixed = 0;

        foreach ($convs as $conv) {
            $this->syncMessages($conv);

            $last = $conv->messages()->orderByDesc('sent_at')->first();
            if ($last) {
                $answered = $last->from_role === 'seller';
                if ((bool) $conv->is_answered !== $answered) {
                    $conv->update(['is_answered' => $answered]);
                    $fixed++;
                }
            }
        }

        return ['scanned' => $convs->count(), 'fixed' => $fixed];
    }

    /**
     * Kirim pesan teks. Jika $conversation null, kirim cold-start ke $toId
     * (percakapan baru akan dibuat via sync setelahnya).
     */
    public function sendText(Store $store, ?MarketplaceConversation $conversation, string $text, $toId = null): array
    {
        // Kalau toko percakapan tidak siap kirim (tanpa access_token / nonaktif) —
        // mis. record duplikat "Insight" yang mati — alihkan ke toko lain milik
        // SHOP yang sama yang benar-benar terhubung (mis. "Insight Corps").
        if (blank($store->credential('access_token')) || ! $store->is_active) {
            $alt = $this->connectedSiblingStore($store);
            if ($alt) {
                if ($conversation && $conversation->store_id !== $alt->id) {
                    $conversation->update(['store_id' => $alt->id]);
                }
                Log::info("Chat: kirim dialihkan dari toko #{$store->id} ({$store->name}) ke #{$alt->id} ({$alt->name}) — shop sama, toko asal tidak terhubung.");
                $store = $alt;
            } else {
                return [
                    'error'   => 'store_not_connected',
                    'message' => "Toko \"{$store->name}\" belum terhubung ke Shopee dan tidak ada toko pengganti untuk shop ini. Re-authorize toko dulu.",
                ];
            }
        }

        $driver = $this->gateway;
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

            $messagePayload = array_merge($data, [
                'message_type' => 'text',
                'content'      => ['text' => $text],
                'from_id'      => (string) $store->external_shop_id,
                'to_id'        => (string) $toId,
            ]);

            if (empty($messagePayload['message_id'])) {
                $messagePayload['message_id'] = 'local_' . uniqid('', true);
            }

            if (empty($messagePayload['created_timestamp'])) {
                $messagePayload['created_timestamp'] = $sentAt->timestamp;
            }

            $this->upsertMessage(
                $store,
                $conversation,
                $messagePayload,
                'send_api',
                [
                    'to_id'        => (int) $toId,
                    'message_type' => 'text',
                    'content'      => ['text' => $text],
                ],
                $res
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
     * Kembalikan toko yang benar-benar bisa dipakai kirim untuk shop ini:
     * toko itu sendiri kalau sudah terhubung & aktif, atau toko pengganti
     * (shop sama) yang terhubung. Kalau tidak ada, kembalikan toko asal.
     */
    public function usableStore(Store $store): Store
    {
        if (filled($store->credential('access_token')) && $store->is_active) {
            return $store;
        }
        return $this->connectedSiblingStore($store) ?? $store;
    }

    /**
     * Cari toko lain untuk SHOP yang sama (external_shop_id sama) yang aktif &
     * benar-benar terhubung (punya access_token). Dipakai saat toko percakapan
     * adalah record duplikat/mati.
     */
    protected function connectedSiblingStore(Store $store): ?Store
    {
        if (blank($store->external_shop_id)) {
            return null;
        }

        return Store::where('external_shop_id', $store->external_shop_id)
            ->where('id', '!=', $store->id)
            ->where('is_active', true)
            ->whereHas('channel', fn ($q) => $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))
            ->get()
            ->first(fn ($s) => filled($s->credential('access_token')));
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
    public function handleIncomingWebhookMessage(Store $store, array $content, array $rawContext = [], ?int $webhookLogId = null): void
    {
        $extConvId   = $this->resolveConversationId($content, $rawContext);
        $messageId   = (string) (data_get($content, 'message_id') ?? '');
        $messageType = (string) (data_get($content, 'message_type') ?? '');

        if ($extConvId === '') {
            Log::info('Chat webhook: payload tanpa conversation_id, di-skip.', [
                'content' => $content,
                'raw_context' => $rawContext,
            ]);
            return;
        }

        $conversation = MarketplaceConversation::firstOrCreate(
            ['store_id' => $store->id, 'conversation_id' => $extConvId],
            []
        );

        if ($messageType === 'notification') {
            // Notification tidak membawa pesan baru, tetapi bisa mengubah status
            // percakapan. Refresh daftar percakapan agar unread/answered ikut sinkron.
            $this->syncConversations($store, pages: 1);
            return;
        }

        // bundle_message (FAQ/chatbot): hanya berisi daftar message_id →
        // tarik isi lengkapnya via API get_message
        if ($messageType === 'bundle_message' || $messageId === '' || empty($content)) {
            $this->syncConversations($store, pages: 1);
            $new = $this->syncMessages($conversation, triggerWebhookLogId: $webhookLogId);
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

        $isNew = $this->upsertMessage($store, $conversation, $content, 'webhook', $content, $rawContext, $webhookLogId);

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

    private function resolveConversationId(array $content, array $rawContext = []): string
    {
        foreach ([
            data_get($content, 'conversation_id'),
            data_get($rawContext, 'conversation_id'),
            data_get($rawContext, 'data.conversation_id'),
            data_get($rawContext, 'data.content.conversation_id'),
            data_get($rawContext, 'data.content.0.conversation_id'),
        ] as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return '';
    }

    private function resolveChatMessageOffset(array $response, string $currentOffset): string
    {
        foreach ([
            data_get($response, 'response.page_result.next_offset'),
            data_get($response, 'response.next_offset'),
            data_get($response, 'response.page_result.next_cursor.next_offset'),
            data_get($response, 'response.page_result.next_cursor.offset'),
            data_get($response, 'response.offset'),
        ] as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return $currentOffset;
    }

    private function chatMessagesHasMore(array $response, array $messages, string $nextOffset, string $currentOffset, int $pageSize): bool
    {
        $explicit = data_get($response, 'response.page_result.more');
        if ($explicit === null) {
            $explicit = data_get($response, 'response.more');
        }
        if ($explicit === null) {
            $explicit = data_get($response, 'response.has_more');
        }
        if ($explicit === null) {
            $explicit = data_get($response, 'response.page_result.has_more');
        }

        if ($explicit !== null) {
            return (bool) $explicit;
        }

        // Fallback aman: kalau API tidak memberi flag, lanjut hanya jika halaman
        // penuh dan offset berikutnya benar-benar berubah.
        return count($messages) >= $pageSize && $nextOffset !== '' && $nextOffset !== $currentOffset;
    }

    private function shouldBackfillConversationMessages(MarketplaceConversation $conversation): bool
    {
        return $conversation->wasRecentlyCreated
            || $conversation->wasChanged('unread_count')
            || $conversation->wasChanged('last_message_at')
            || $conversation->wasChanged('last_message_text');
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

        // "Sudah dibalas" bila pesan TERAKHIR bukan berasal dari buyer.
        // Shopee mengirim id user pengirim pesan terakhir; to_id adalah buyer.
        // Ini menangkap balasan yang dikirim seller LANGSUNG dari aplikasi Shopee
        // (di luar aplikasi ini) — yang sebelumnya tak pernah menyetel is_answered.
        $buyerId    = data_get($c, 'to_id') !== null ? (string) data_get($c, 'to_id') : null;
        $lastFromId = (string) (data_get($c, 'last_message_from_id')
            ?? data_get($c, 'latest_message_from_id')
            ?? '');
        $isAnswered = ($lastFromId !== '' && $buyerId !== null && $buyerId !== '')
            ? ($lastFromId !== $buyerId)   // pengirim terakhir bukan buyer → sudah dibalas
            : null;                         // tak bisa ditentukan → jangan ubah nilai lama

        $conv = MarketplaceConversation::updateOrCreate(
            ['store_id' => $store->id, 'conversation_id' => $extConvId],
            array_filter([
                'buyer_user_id'     => $buyerId,
                'buyer_username'    => data_get($c, 'to_name'),
                'buyer_avatar'      => data_get($c, 'to_avatar'),
                'last_message_type' => data_get($c, 'latest_message_type'),
                'last_message_text' => $lastText,
                'last_message_at'   => $lastAt,
                'unread_count'      => data_get($c, 'unread_count'),
                'is_answered'       => $isAnswered,
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
    private function upsertMessage(
        Store $store,
        MarketplaceConversation $conversation,
        array $m,
        string $source = 'sync_api',
        ?array $rawPayload = null,
        ?array $rawContext = null,
        ?int $webhookLogId = null
    ): bool
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

        $rawPayload = $rawPayload ?: $m;
        $rawContext = $rawContext ?: $rawPayload;
        $externalConversationId = (string) (data_get($m, 'conversation_id') ?? $conversation->conversation_id);

        $row = MarketplaceChatMessage::firstOrNew(
            ['store_id' => $store->id, 'external_message_id' => $messageId]
        );

        $isNew = ! $row->exists;

        $existingContext = $row->raw_context;
        if (is_string($existingContext)) {
            $decoded = json_decode($existingContext, true);
            $existingContext = is_array($decoded) ? $decoded : [];
        }
        $existingContext = is_array($existingContext) ? $existingContext : [];

        $existingContext[$source] = $rawContext ?: $rawPayload;
        if ($webhookLogId) {
            $existingContext['webhook_log_id'] = $webhookLogId;
        }

        $row->marketplace_conversation_id = $row->marketplace_conversation_id ?: $conversation->id;
        $row->external_conversation_id    = $row->external_conversation_id ?: $externalConversationId;
        $row->source                      = $row->source ?: $source;
        $row->from_role                   = $fromRole;
        $row->from_id                     = $fromId ?: null;
        $row->message_type                = $type;
        $row->text                        = $text;
        $row->content                     = $content;
        $row->raw_payload                 = $row->raw_payload ?: $rawPayload;
        $row->raw_context                 = $existingContext;
        $row->webhook_log_id              = $row->webhook_log_id ?: $webhookLogId;
        $row->sent_at                     = $sentAt;
        $row->is_read                     = $fromRole === 'seller';
        $row->save();

        // Update ringkasan percakapan bila pesan ini yang terbaru
        if ($isNew && (! $conversation->last_message_at || $sentAt->gte($conversation->last_message_at))) {
            $conversation->update([
                'last_message_type' => $type,
                'last_message_text' => $text ?: "[{$type}]",
                'last_message_at'   => $sentAt,
                'is_answered'       => $fromRole === 'seller',
            ]);
        }

        return $isNew;
    }
}
