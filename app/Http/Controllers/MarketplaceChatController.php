<?php

namespace App\Http\Controllers;

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
     * Daftar percakapan lokal. ?sync=1 untuk tarik dari Shopee dulu.
     */
    public function conversations(Request $request)
    {
        if ($request->boolean('sync')) {
            $stores = Store::whereHas('channel', fn ($q) => $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))
                ->where('status', 'active')->get();

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

        return response()->json([
            'conversation' => $conversation->fresh('store:id,name'),
            'messages'     => $messages,
        ]);
    }

    /**
     * Kirim pesan teks ke percakapan yang sudah ada.
     */
    public function send(MarketplaceConversation $conversation, Request $request)
    {
        $data = $request->validate(['text' => 'required|string|max:2000']);

        $res = $this->chat->sendText($conversation->store, $conversation, $data['text']);

        if (! empty($res['error'])) {
            return response()->json(['message' => $res['message'] ?? 'Gagal mengirim pesan.'], 422);
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
     */
    public function unreadCount()
    {
        return response()->json([
            'unread' => (int) MarketplaceConversation::where('unread_count', '>', 0)
                            ->orWhere('is_answered', false)
                            ->count(),
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
}
