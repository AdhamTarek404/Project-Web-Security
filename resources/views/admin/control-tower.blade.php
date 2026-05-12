<x-layouts.app title="Admin · Control Tower">
    @push('head')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @endpush

    <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900">Control Tower</h1>
        <p class="text-slate-600 mt-1">Live view of active orders and on-duty riders.</p>
    </div>

    <livewire:admin-control-tower />

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const el = document.getElementById('control-tower-map');
                if (!el) return;

                const map = L.map(el).setView([30.0444, 31.2357], 12);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap',
                }).addTo(map);

                const orders = JSON.parse(el.dataset.orders || '[]');
                const riders = JSON.parse(el.dataset.riders || '[]');

                orders.forEach(o => {
                    L.marker([o.rest.lat, o.rest.lng], { title: o.rest.name })
                        .addTo(map)
                        .bindPopup(`<b>${o.rest.name}</b><br>Order #${o.id} · ${o.status}`);
                });

                riders.forEach(r => {
                    L.circleMarker([r.lat, r.lng], { radius: 6, color: '#0ea5e9' })
                        .addTo(map)
                        .bindPopup(`Rider #${r.id}`);
                });
            });
        </script>
    @endpush
</x-layouts.app>
