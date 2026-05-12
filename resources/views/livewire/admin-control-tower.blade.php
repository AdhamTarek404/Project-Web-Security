<div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-6">
    <div class="col-span-2 bg-white border rounded-xl shadow-sm overflow-hidden">
        <h2 class="font-semibold text-lg p-4 border-b">{{ $title }}</h2>

        {{-- The map data is rendered as JSON strings in data-* attributes;
             the page-level <script> in admin/control-tower.blade.php reads
             them with JSON.parse() and places Leaflet markers. --}}
        <div id="control-tower-map" class="h-96 w-full"
             data-orders="{{ $orderMarkersJson }}"
             data-riders="{{ $riderMarkersJson }}"></div>
    </div>

    <div class="bg-white border rounded-xl shadow-sm overflow-hidden">
        <h2 class="font-semibold text-lg p-4 border-b">Active orders ({{ $activeOrders->count() }})</h2>
        <ul class="divide-y max-h-96 overflow-y-auto">
            @forelse($activeOrders as $o)
                <li class="p-3 text-sm flex justify-between">
                    <span>#{{ $o->id }} · {{ $o->restaurant->name }}</span>
                    <span class="font-mono text-xs px-2 py-1 rounded bg-slate-100">{{ $o->status?->value }}</span>
                </li>
            @empty
                <li class="p-4 text-sm text-slate-500">No active orders.</li>
            @endforelse
        </ul>
    </div>

    {{-- Echo wire-up. When the Reverb broadcast arrives we tell Livewire
         to refresh which re-renders this whole component with fresh DB data. --}}
    @script
    <script>
        if (window.Echo) {
            window.Echo.channel('admin.orders').listen('.OrderStateChanged', () => {
                $wire.dispatch('order-state-changed');
            });
            window.Echo.channel('admin.riders').listen('.RiderLocationUpdated', () => {
                $wire.dispatch('rider-location-updated');
            });
        }
    </script>
    @endscript
</div>
