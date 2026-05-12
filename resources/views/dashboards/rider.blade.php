<x-layouts.app title="Rider dashboard">
    <div class="mb-8">
        <p class="text-sm text-slate-500">Rider</p>
        <h1 class="text-3xl font-bold text-slate-900">{{ $user->name }}</h1>
    </div>

    @if(! $rider)
        <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-xl p-6">
            Your user has no rider profile yet. Run <code>php artisan migrate:fresh --seed</code> to load the demo riders.
        </div>
    @else
        {{-- Rider status card --}}
        <div class="bg-white border border-slate-200 rounded-xl p-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-500">Duty</p>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="w-2.5 h-2.5 rounded-full {{ $rider->is_on_duty ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                        <span class="font-semibold {{ $rider->is_on_duty ? 'text-emerald-700' : 'text-slate-500' }}">
                            {{ $rider->is_on_duty ? 'ON duty' : 'OFF duty' }}
                        </span>
                    </div>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-500">Available for dispatch</p>
                    <p class="font-semibold mt-2 {{ $rider->is_available ? 'text-emerald-700' : 'text-slate-500' }}">
                        {{ $rider->is_available ? 'Yes' : 'No' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-500">Last location</p>
                    <p class="font-mono text-sm mt-2 text-slate-700">
                        {{ $rider->current_latitude ?? '—' }}, {{ $rider->current_longitude ?? '—' }}
                    </p>
                </div>
            </div>

            <div class="mt-6 pt-6 border-t flex flex-wrap items-end gap-3">
                <form method="POST" action="{{ route('rider.duty') }}">
                    @csrf
                    <button class="bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium px-4 py-2 rounded-lg">
                        Toggle duty
                    </button>
                </form>

                <form method="POST" action="{{ route('rider.location') }}" class="flex flex-wrap items-end gap-2">
                    @csrf
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Latitude</label>
                        <input type="number" step="0.0000001" name="latitude" value="{{ $rider->current_latitude }}" required
                               class="w-32 text-sm rounded-lg border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 px-2 py-1.5">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Longitude</label>
                        <input type="number" step="0.0000001" name="longitude" value="{{ $rider->current_longitude }}" required
                               class="w-32 text-sm rounded-lg border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 px-2 py-1.5">
                    </div>
                    <button class="bg-sky-600 hover:bg-sky-500 text-white text-sm font-medium px-4 py-2 rounded-lg">
                        Ping GPS
                    </button>
                </form>
            </div>
        </div>

        <h2 class="text-lg font-semibold mb-3 text-slate-900">My deliveries</h2>

        @if($orders->isEmpty())
            <div class="bg-white border border-slate-200 rounded-xl p-12 text-center text-slate-500">
                <p>Nothing assigned right now.</p>
                <p class="text-xs mt-2">Once an order enters <code class="bg-slate-100 px-1.5 rounded">preparing</code>, the dispatcher will pick the nearest available rider.</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($orders as $o)
                    <div class="bg-white border border-slate-200 rounded-xl p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="font-semibold text-slate-900">Order #{{ $o->id }}</h3>
                                    <x-status-badge :status="$o->status" />
                                </div>
                                <p class="text-sm text-slate-600 mt-1">
                                    <span class="text-slate-500">Pick up:</span>
                                    <span class="font-medium">{{ $o->restaurant->name }}</span>
                                </p>
                                <p class="text-sm text-slate-600">
                                    <span class="text-slate-500">Deliver to:</span>
                                    <em>{{ $o->delivery_address }}</em>
                                </p>
                                <p class="text-xs text-slate-500 mt-1">
                                    Customer: {{ $o->customer->name }} ·
                                    <span class="text-emerald-700 font-medium">Payout: {{ number_format($o->rider_payout / 100, 2) }} EGP</span>
                                </p>
                            </div>

                            <div class="flex flex-col gap-2 items-end shrink-0">
                                @if($o->status->value === 'preparing')
                                    <form method="POST" action="{{ route('rider.orders.picked-up', $o) }}">
                                        @csrf
                                        <button class="bg-sky-600 hover:bg-sky-500 text-white text-sm font-medium px-4 py-1.5 rounded-lg">
                                            Mark as picked up
                                        </button>
                                    </form>
                                @endif
                                @if($o->status->value === 'on_the_way')
                                    <form method="POST" action="{{ route('rider.orders.delivered', $o) }}">
                                        @csrf
                                        <button class="bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-medium px-4 py-1.5 rounded-lg">
                                            Mark as delivered
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</x-layouts.app>
