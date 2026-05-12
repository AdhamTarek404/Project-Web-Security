<?php

namespace App\Livewire;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Rider;
use Livewire\Attributes\On;
use Livewire\Component;

// Description: "Livewire 3 for admin control tower showing live order map."
//
// This component:
//   - lists active orders (anything not delivered or cancelled)
//   - lists on-duty riders with their current GPS
//   - listens to Reverb broadcasts and refreshes itself when:
//       * any order changes state (echo-private: admin.orders)
//       * any rider posts a new location (echo-private: admin.riders)
//
// On the front-end, the corresponding Blade view renders a Leaflet/Google
// map. The Echo client (set up by `php artisan install:broadcasting --reverb`)
// pipes the broadcast events into Livewire via $wire.dispatch().
class AdminControlTower extends Component
{
    public string $title = 'Live Control Tower';

    /**
     * Reverb broadcasts arrive on these JS channels:
     *   Echo.channel('admin.orders').listen('OrderStateChanged', ...)
     *   Echo.channel('admin.riders').listen('RiderLocationUpdated', ...)
     *
     * Each handler calls $wire.refresh() which triggers a re-render
     * with the latest DB state.
     */
    #[On('order-state-changed')]
    #[On('rider-location-updated')]
    public function refresh(): void
    {
        // The render() method below is called automatically on event.
    }

    public function render()
    {
        $activeOrders = Order::with(['restaurant:id,name,latitude,longitude', 'rider:id,current_latitude,current_longitude'])
            ->whereNotIn('status', [OrderStatus::Delivered->value, OrderStatus::Cancelled->value])
            ->latest()
            ->limit(50)
            ->get();

        $riders = Rider::query()
            ->where('is_on_duty', true)
            ->whereNotNull('current_latitude')
            ->get();

        // Pre-shape the JSON the Blade attribute needs. Doing it here keeps the
        // view free of inline arrow-functions inside HTML attributes (which the
        // Blade compiler is not happy about).
        $orderMarkers = $activeOrders->map(fn ($o) => [
            'id' => $o->id,
            'status' => $o->status?->value,
            'rest' => [
                'lat' => (float) $o->restaurant->latitude,
                'lng' => (float) $o->restaurant->longitude,
                'name' => $o->restaurant->name,
            ],
            'rider' => $o->rider ? [
                'lat' => (float) $o->rider->current_latitude,
                'lng' => (float) $o->rider->current_longitude,
            ] : null,
        ])->values();

        $riderMarkers = $riders->map(fn ($r) => [
            'id' => $r->id,
            'lat' => (float) $r->current_latitude,
            'lng' => (float) $r->current_longitude,
        ])->values();

        return view('livewire.admin-control-tower', [
            'activeOrders' => $activeOrders,
            'orderMarkersJson' => $orderMarkers->toJson(),
            'riderMarkersJson' => $riderMarkers->toJson(),
        ]);
    }
}
