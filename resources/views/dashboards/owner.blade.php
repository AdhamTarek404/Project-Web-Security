<x-layouts.app title="Owner dashboard">
    <div class="mb-8 flex items-start justify-between gap-4 flex-wrap">
        <div>
            <p class="text-sm text-slate-500">Restaurant owner</p>
            <h1 class="text-3xl font-bold text-slate-900">{{ $user->name }}</h1>
        </div>
        <a href="{{ route('owner.restaurants.create') }}"
           class="bg-emerald-600 hover:bg-emerald-500 text-white font-medium px-4 py-2.5 rounded-lg shadow-sm">
            + New restaurant
        </a>
    </div>

    {{-- KPI strip --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <p class="text-xs text-slate-500 uppercase tracking-wide">Restaurants</p>
            <p class="text-3xl font-semibold mt-1">{{ $restaurants->count() }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <p class="text-xs text-slate-500 uppercase tracking-wide">Total orders</p>
            <p class="text-3xl font-semibold mt-1">{{ $orders->count() }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <p class="text-xs text-slate-500 uppercase tracking-wide">Awaiting confirm</p>
            <p class="text-3xl font-semibold mt-1 text-amber-700">{{ $orders->where('status.value', 'placed')->count() }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <p class="text-xs text-slate-500 uppercase tracking-wide">Total payout</p>
            <p class="text-3xl font-semibold mt-1 text-emerald-700">
                {{ number_format($orders->sum('restaurant_payout') / 100, 2) }} <span class="text-base text-slate-500">EGP</span>
            </p>
        </div>
    </div>

    {{-- Restaurants --}}
    <section class="mb-8">
        <h2 class="text-lg font-semibold mb-3 text-slate-900">My restaurants</h2>

        @if($restaurants->isEmpty())
            <div class="bg-white border border-dashed border-slate-300 rounded-xl p-10 text-center">
                <p class="text-slate-500">You don't have any restaurants yet.</p>
                <a href="{{ route('owner.restaurants.create') }}"
                   class="inline-block mt-4 bg-emerald-600 hover:bg-emerald-500 text-white font-medium px-4 py-2 rounded-lg">
                    Create your first restaurant
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($restaurants as $r)
                    <div class="bg-white border border-slate-200 rounded-xl p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h3 class="font-semibold text-slate-900">{{ $r->name }}</h3>
                                    <span class="text-xs px-2 py-0.5 rounded-full {{ $r->is_open ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                        {{ $r->is_open ? 'open' : 'closed' }}
                                    </span>
                                </div>
                                <p class="text-sm text-slate-500 mt-1">{{ $r->address }}</p>
                                <div class="mt-3 flex flex-wrap gap-x-3 gap-y-1 text-xs text-slate-500">
                                    <span>{{ $r->categories_count }} categories</span>
                                    <span>·</span>
                                    <span>{{ $r->orders_count }} orders</span>
                                    <span>·</span>
                                    <span>{{ number_format($r->commission_rate, 1) }}% commission</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t flex items-center justify-between">
                            <a href="{{ route('restaurants.show', $r) }}"
                               class="text-sm text-slate-500 hover:text-slate-800">View public menu →</a>
                            <a href="{{ route('owner.restaurants.manage', $r) }}"
                               class="bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium px-3 py-1.5 rounded-lg">
                                Manage
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Orders --}}
    <section>
        <h2 class="text-lg font-semibold mb-3 text-slate-900">Recent orders</h2>

        @if($orders->isEmpty())
            <div class="bg-white border border-slate-200 rounded-xl p-12 text-center text-slate-500">
                No orders yet. Sign in as <code class="bg-slate-100 px-1.5 rounded">customer1@demo.test</code> and place one.
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
                                    <span class="text-xs text-slate-400">{{ $o->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="text-sm text-slate-600 mt-1">
                                    <span class="font-medium">{{ $o->customer->name }}</span> ·
                                    {{ $o->items->sum('quantity') }} {{ \Illuminate\Support\Str::plural('item', $o->items->sum('quantity')) }}
                                </p>
                                <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                                    <span>subtotal {{ number_format($o->subtotal/100, 2) }}</span>
                                    <span>delivery {{ number_format($o->delivery_fee/100, 2) }}</span>
                                    <span class="font-semibold text-slate-700">total {{ number_format($o->total/100, 2) }} EGP</span>
                                    <span class="text-emerald-700">your payout {{ number_format($o->restaurant_payout/100, 2) }}</span>
                                </div>
                            </div>

                            <div class="flex flex-col gap-2 items-end shrink-0">
                                @if($o->status->value === 'placed')
                                    <form method="POST" action="{{ route('owner.orders.confirm', $o) }}">
                                        @csrf
                                        <button class="bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-medium px-4 py-1.5 rounded-lg">
                                            Confirm
                                        </button>
                                    </form>
                                @endif
                                @if($o->status->value === 'confirmed')
                                    <form method="POST" action="{{ route('owner.orders.start', $o) }}">
                                        @csrf
                                        <button class="bg-sky-600 hover:bg-sky-500 text-white text-sm font-medium px-4 py-1.5 rounded-lg">
                                            Start preparing
                                        </button>
                                    </form>
                                @endif
                                @if(in_array($o->status->value, ['placed', 'confirmed']))
                                    <form method="POST" action="{{ route('owner.orders.cancel', $o) }}">
                                        @csrf
                                        <button class="text-xs text-rose-600 hover:text-rose-700">Cancel</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</x-layouts.app>
