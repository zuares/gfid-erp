<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $storeId;
    public $itemId;

    public function __construct($storeId, $itemId)
    {
        $this->storeId = $storeId;
        $this->itemId = $itemId;
    }

    public function broadcastOn()
    {
        return new Channel('marketplace');
    }

    public function broadcastWith()
    {
        return ['store_id' => $this->storeId, 'item_id' => $this->itemId];
    }
}
