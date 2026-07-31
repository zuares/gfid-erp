<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceChatMessage;
use App\Models\MarketplaceConversation;
use App\Models\MarketplaceOrder;
use App\Models\Store;
use App\Services\MarketplaceChatService;
use Illuminate\Http\Request;

class MarketplaceChatController extends Controller
{
    public function __construct(protected MarketplaceChatService $chat) {}

    /**
     * Halaman chat.
     */
    public function page(Request $request)
    {
        return view('marketplace.chat');
    }

    /**
     * Audit raw payload setiap pesan chat.
     */
    public function audit(Request $request)
    {
        $storeId = $request->filled('store_id') ? (int) $request->input('store_id') : null;
        $conversationId = trim((string) $request->query('conversation_id', ''));
        $q = trim((string) $request->query('q', ''));
        $direction = trim((string) $request->query('direction', ''));
        $source = trim((string) $request->query('source', ''));
        $webhookLogId = $request->filled('webhook_log_id') ? (int) $request->input('webhook_log_id') : null;
        $focusMessageId = $request->filled('message_id') ? (int) $request->input('message_id') : null;

        $stores = Store::whereHas('channel', fn ($query) => $query->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))
            ->orderBy('name')
            ->get(['id', 'name']);

        $messages = MarketplaceChatMessage::query()
            ->with([
                'store:id,name,external_shop_id',
                'conversation:id,store_id,conversation_id,buyer_user_id,buyer_username,last_message_at',
                'webhookLog:id,provider,event_type,signature_verified,payload,created_at',
            ])
            ->when($storeId, fn ($query) => $query->where('store_id', $storeId))
            ->when($conversationId !== '', fn ($query) => $query->where('external_conversation_id', $conversationId))
            ->when($direction !== '', fn ($query) => $query->where('from_role', $direction))
            ->when($source !== '', fn ($query) => $query->where('source', $source))
            ->when($webhookLogId, fn ($query) => $query->where('webhook_log_id', $webhookLogId))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('external_message_id', 'like', "%{$q}%")
                        ->orWhere('external_conversation_id', 'like', "%{$q}%")
                        ->orWhere('text', 'like', "%{$q}%")
                        ->orWhere('source', 'like', "%{$q}%")
                        ->orWhere('from_id', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('sent_at')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('marketplace.chat-audit', compact('messages', 'stores', 'storeId', 'conversationId', 'q', 'direction', 'source', 'webhookLogId', 'focusMessageId'));
    }

    /**
     * Daftar percakapan lokal. ?sync=1 untuk tarik dari Shopee dulu.
     */
    public function conversations(Request $request)
    {
        if ($request->boolean('sync')) {
            $stores = Store::whereHas('channel', fn ($q) => $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))
                ->where('status', 'active')
                ->where('is_active', true) // lewati toko yang sengaja dinonaktifkan
                ->get()
                ->filter(fn ($s) => filled($s->credential('access_token'))); // & yang tak punya token

            foreach ($stores as $store) {
                try {
                    $this->chat->syncConversations($store);
                } catch (\Throwable $e) {
                    \Log::warning("Chat: sync conversations store #{$store->id} gagal: " . $e->getMessage());
                }
            }
        }

        $conversations = MarketplaceConversation::with('store:id,name')
            ->orderByDesc('last_message_at')
            ->limit(200)
            ->get();

        return response()->json($conversations);
    }

    /**
     * Pesan sebuah percakapan. ?sync=1 untuk tarik dari Shopee dulu.
     */
    public function messages(MarketplaceConversation $conversation, Request $request)
    {
        if ($request->boolean('sync')) {
            try {
                $this->chat->syncMessages($conversation);
            } catch (\Throwable $e) {
                \Log::warning("Chat: sync messages conv #{$conversation->id} gagal: " . $e->getMessage());
            }
        }

        $messages = $conversation->messages()
            ->orderBy('sent_at')
            ->limit(200)
            ->get();

        $reply = $this->chat->canReply($conversation);

        return response()->json([
            'conversation' => $conversation->fresh('store:id,name'),
            'messages'     => $messages,
            'can_reply'    => $reply['can_reply'],
            'reply_reason' => $reply['reason'],
        ]);
    }

    /**
     * Detail raw payload untuk satu message.
     */
    public function messageRaw(MarketplaceChatMessage $message)
    {
        $message->load([
            'store:id,name,external_shop_id',
            'conversation:id,store_id,conversation_id,buyer_user_id,buyer_username,last_message_at',
            'webhookLog:id,provider,event_type,signature_verified,payload,ip_address,created_at',
        ]);

        [$rawPayload, $rawContext, $auditState] = $this->resolveAuditPayloads($message);

        return response()->json([
            'message' => [
                'id' => $message->id,
                'store_id' => $message->store_id,
                'marketplace_conversation_id' => $message->marketplace_conversation_id,
                'external_conversation_id' => $message->external_conversation_id,
                'external_message_id' => $message->external_message_id,
                'source' => $message->source,
                'from_role' => $message->from_role,
                'from_id' => $message->from_id,
                'message_type' => $message->message_type,
                'text' => $message->text,
                'content' => $message->content,
                'raw_payload' => $rawPayload,
                'raw_context' => $rawContext,
                'webhook_log_id' => $message->webhook_log_id,
                'sent_at' => optional($message->sent_at)?->toIso8601String(),
                'is_read' => $message->is_read,
                'audit_state' => $auditState,
            ],
            'store' => $message->store,
            'conversation' => $message->conversation,
            'webhook_log' => $message->webhookLog,
        ]);
    }

    /**
     * Fallback audit payload untuk message legacy yang belum punya raw data lengkap.
     *
     * @return array{0:array,1:array,2:string}
     */
    protected function resolveAuditPayloads(MarketplaceChatMessage $message): array
    {
        $rawPayload = $message->raw_payload;
        $rawContext = $message->raw_context;
        $auditState = 'stored';

        if (is_string($rawPayload)) {
            $decoded = json_decode($rawPayload, true);
            $rawPayload = is_array($decoded) ? $decoded : [];
        }
        $rawPayload = is_array($rawPayload) ? $rawPayload : [];

        if (is_string($rawContext)) {
            $decoded = json_decode($rawContext, true);
            $rawContext = is_array($decoded) ? $decoded : [];
        }
        $rawContext = is_array($rawContext) ? $rawContext : [];

        if (empty($rawPayload) && $message->webhookLog?->payload) {
            $rawPayload = $message->webhookLog->payload;
            $rawContext['audit'] = array_merge($rawContext['audit'] ?? [], [
                'mode' => 'webhook_log_payload',
                'webhook_log_id' => $message->webhook_log_id,
            ]);
            $auditState = 'synthesized_from_webhook_log';
        }

        if (empty($rawPayload)) {
            $rawPayload = array_filter([
                'message_id' => $message->external_message_id,
                'conversation_id' => $message->external_conversation_id,
                'message_type' => $message->message_type,
                'source' => $message->source,
                'from_role' => $message->from_role,
                'from_id' => $message->from_id,
                'text' => $message->text,
                'content' => $message->content ?: (filled($message->text) ? ['text' => $message->text] : null),
                'created_timestamp' => optional($message->sent_at)?->timestamp,
            ], static fn ($value) => $value !== null && $value !== '');

            $rawContext['audit'] = array_merge($rawContext['audit'] ?? [], [
                'mode' => 'synthesized_from_row',
                'message_id' => $message->external_message_id,
                'conversation_id' => $message->external_conversation_id,
                'message_type' => $message->message_type,
            ]);

            $auditState = 'synthesized';
        }

        if (empty($rawContext)) {
            $rawContext = [
                'audit' => [
                    'mode' => 'empty_fallback',
                    'message_id' => $message->external_message_id,
                ],
            ];
        }

        return [$rawPayload, $rawContext, $auditState];
    }

    /**
     * Kirim pesan teks ke percakapan yang sudah ada.
     */
    public function send(MarketplaceConversation $conversation, Request $request)
    {
        $data = $request->validate(['text' => 'required|string|max:2000']);

        $store = $conversation->store;
        if (! $store) {
            return response()->json(['message' => 'Toko percakapan tidak ditemukan.'], 422);
        }

        // Catatan: kalau toko ini duplikat/mati (mis. "Insight"), service otomatis
        // mengalihkan ke toko lain milik shop yang sama yang terhubung ("Insight Corps").
        $res = $this->chat->sendText($store, $conversation, $data['text']);

        if (! empty($res['error'])) {
            $msg = $res['message'] ?? 'Gagal mengirim pesan.';
            if (stripos($msg, 'access_token') !== false || ($res['error'] ?? '') === 'error_auth') {
                $msg = 'Token Shopee bermasalah. Coba Re-authorize toko ini di menu Toko, lalu kirim ulang.';
            }
            return response()->json(['message' => $msg], 422);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Tandai percakapan terbaca.
     */
    public function markRead(MarketplaceConversation $conversation)
    {
        $this->chat->markRead($conversation);

        return response()->json(['success' => true]);
    }

    /**
     * Total pesan belum dibaca (untuk badge sidebar).
     * Hanya menghitung pesan yang BELUM DIBACA (unread_count), tidak lagi
     * mencampur status "belum dibalas" (is_answered).
     */
    public function unreadCount()
    {
        return response()->json([
            'unread' => (int) MarketplaceConversation::where('unread_count', '>', 0)
                            ->sum('unread_count'),
        ]);
    }

    /**
     * Deep-link dari halaman orders: cari/buat percakapan untuk sebuah order.
     * Body: store_id, order_sn, text (optional — kalau diisi langsung kirim).
     */
    public function startFromOrder(Request $request)
    {
        $data = $request->validate([
            'store_id' => 'required|integer|exists:stores,id',
            'order_sn' => 'required|string',
            'text'     => 'nullable|string|max:2000',
        ]);

        $store = Store::findOrFail($data['store_id']);
        $order = MarketplaceOrder::where('store_id', $store->id)
            ->where('channel_order_id', $data['order_sn'])
            ->firstOrFail();

        // Order tetap dicari di toko aslinya, tapi percakapan & pengiriman pakai
        // toko yang benar-benar terhubung untuk shop ini (mis. record "Insight"
        // yang mati → dialihkan ke "Insight Corps").
        $store = $this->chat->usableStore($store);

        $buyerUserId   = data_get($order->raw_json, 'buyer_user_id');
        $buyerUsername = $order->buyer_username;

        // 1. Cari percakapan lokal by buyer_user_id / username
        $conversation = null;
        if ($buyerUserId) {
            $conversation = MarketplaceConversation::where('store_id', $store->id)
                ->where('buyer_user_id', (string) $buyerUserId)->first();
        }
        if (! $conversation && $buyerUsername) {
            $conversation = MarketplaceConversation::where('store_id', $store->id)
                ->where('buyer_username', $buyerUsername)->first();
        }

        // 2. Belum ada? Sync daftar percakapan lalu cari lagi
        if (! $conversation) {
            try { $this->chat->syncConversations($store); } catch (\Throwable $e) {}

            if ($buyerUserId) {
                $conversation = MarketplaceConversation::where('store_id', $store->id)
                    ->where('buyer_user_id', (string) $buyerUserId)->first();
            }
            if (! $conversation && $buyerUsername) {
                $conversation = MarketplaceConversation::where('store_id', $store->id)
                    ->where('buyer_username', $buyerUsername)->first();
            }
        }

        // 3. Kalau diminta langsung kirim pesan
        if (! empty($data['text'])) {
            $res = $this->chat->sendText($store, $conversation, $data['text'], $buyerUserId);
            if (! empty($res['error'])) {
                return response()->json(['message' => $res['message'] ?? 'Gagal mengirim pesan.'], 422);
            }
            $conversation = $res['conversation'] ?? $conversation;
        }

        return response()->json([
            'conversation'   => $conversation,
            'buyer_user_id'  => $buyerUserId,
            'buyer_username' => $buyerUsername,
            'order_sn'       => $data['order_sn'],
        ]);
    }

    /**
     * Diagnose Shopee Token & Chat API
     */
    public function diagnoseChat()
    {
        $store = Store::where('is_active', true)
            ->whereHas('channel', fn($q) => $q->whereIn('code', ['shopee', 'SHP', 'SHOPEE']))
            ->first();

        if (!$store) {
            return response()->json(['error' => 'Tidak ada toko Shopee aktif.']);
        }

        /** @var \App\Services\Channels\Shopee\ShopeeChannel $shopee */
        $shopee = app(\App\Services\Channels\Shopee\ShopeeChannel::class);

        return response()->json([
            'store_id' => $store->id,
            'external_shop_id' => $store->external_shop_id,
            'partner_id_used' => $store->credential('partner_id'),
            'getShopInfo' => $shopee->getShopInfo($store),
            'getUnread' => $shopee->getUnreadConversationCount($store),
            'sendChatMessage_dummy_123' => $shopee->sendChatMessage($store, 123, 'test diagnostic'),
        ]);
    }
}
