<?php

namespace App\Events;

use App\Models\ShopeeApiLog;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShopeeApiLogged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $log;

    /**
     * Create a new event instance.
     */
    public function __construct(ShopeeApiLog $log)
    {
        $this->log = $log;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('shopee-api-logs'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'ShopeeApiLogged';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->log->id,
            'method' => $this->log->method,
            'endpoint' => $this->log->endpoint,
            'status_code' => $this->log->status_code,
            'duration' => $this->log->duration,
            'created_at' => $this->log->created_at->format('H:i:s Y-m-d'),
        ];
    }
}
