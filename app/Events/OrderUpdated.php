<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $storeId;
    public $orderSn;
    public $status;

    public function __construct($storeId, $orderSn, $status = null)
    {
        $this->storeId = $storeId;
        $this->orderSn = $orderSn;
        $this->status = $status;
    }

    public function broadcastOn()
    {
        return new Channel('marketplace');
    }

    public function broadcastWith()
    {
        return [
            'store_id' => $this->storeId,
            'order_sn' => $this->orderSn,
            'status' => $this->status,
        ];
    }
}
