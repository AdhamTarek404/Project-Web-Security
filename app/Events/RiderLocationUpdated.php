<?php

namespace App\Events;

use App\Models\Rider;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// Fired every time the rider app posts a new GPS reading. Broadcast on
// the public 'admin.riders' channel so the live admin map (Phase 10
// Livewire component) moves the rider's pin in real time.
//
// Description: "Real-time order status and rider location updates."
class RiderLocationUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public readonly Rider $rider) {}

    public function broadcastOn(): array
    {
        return [new Channel('admin.riders')];
    }

    public function broadcastWith(): array
    {
        return [
            'rider_id' => $this->rider->id,
            'lat' => (float) $this->rider->current_latitude,
            'lng' => (float) $this->rider->current_longitude,
            'at' => $this->rider->last_location_at?->toIso8601String(),
        ];
    }
}
