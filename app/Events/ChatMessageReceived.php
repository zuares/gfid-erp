<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $storeId;
    public $conversationId;      // ID lokal (marketplace_conversations.id)
    public $externalConversationId;
    public $fromRole;            // buyer | seller
    public $preview;

    public function __construct($storeId, $conversationId, $externalConversationId, $fromRole, $preview = null)
    {
        $this->storeId = $storeId;
        $this->conversationId = $conversationId;
        $this->externalConversationId = $externalConversationId;
        $this->fromRole = $fromRole;
        $this->preview = $preview;
    }

    public function broadcastOn()
    {
        return new Channel('marketplace');
    }

    public function broadcastWith()
    {
        return [
            'store_id'                 => $this->storeId,
            'conversation_id'          => $this->conversationId,
            'external_conversation_id' => $this->externalConversationId,
            'from_role'                => $this->fromRole,
            'preview'                  => $this->preview,
        ];
    }
}
