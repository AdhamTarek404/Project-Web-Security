<x-layouts.app title="My orders">
    <div class="flex items-start justify-between gap-4 mb-8">
        <div>
            <p class="text-sm text-slate-500">Customer</p>
            <h1 class="text-3xl font-bold text-slate-900">Hi, {{ $user->name }}</h1>
        </div>
        <a href="{{ route('home') }}"
           class="bg-emerald-600 hover:bg-emerald-500 text-white font-medium px-4 py-2 rounded-lg shrink-0">
            Browse restaurants
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <p class="text-xs text-slate-500 uppercase tracking-wide">Total orders</p>
            <p class="text-3xl font-semibold mt-1">{{ $orders->count() }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <p class="text-xs text-slate-500 uppercase tracking-wide">Delivered</p>
            <p class="text-3xl font-semibold mt-1 text-emerald-700">{{ $orders->where('status.value', 'delivered')->count() }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <p class="text-xs text-slate-500 uppercase tracking-wide">In progress</p>
            <p class="text-3xl font-semibold mt-1 text-sky-700">{{ $orders->whereIn('status.value', ['placed','confirmed','preparing','on_the_way'])->count() }}</p>
        </div>
    </div>

    <h2 class="text-lg font-semibold text-slate-900 mb-3">Recent orders</h2>

    @if($orders->isEmpty())
        <div class="bg-white border border-slate-200 rounded-xl p-12 text-center">
            <p class="text-slate-500">You have no orders yet.</p>
            <a href="{{ route('home') }}" class="inline-block mt-4 text-emerald-700 hover:text-emerald-800 font-medium">Browse restaurants →</a>
        </div>
    @else
        <div class="space-y-3">
            @foreach($orders as $o)
                <div class="bg-white border border-slate-200 rounded-xl p-5 hover:border-emerald-200 hover:shadow-sm transition">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="font-semibold text-slate-900">Order #{{ $o->id }}</h3>
                                <x-status-badge :status="$o->status" />
                                <span class="text-xs text-slate-400">{{ $o->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-sm text-slate-600 mt-1">
                                <span class="font-medium">{{ $o->restaurant->name }}</span> ·
                                {{ $o->items->sum('quantity') }} {{ \Illuminate\Support\Str::plural('item', $o->items->sum('quantity')) }} ·
                                <span class="font-semibold text-slate-900">{{ number_format($o->total / 100, 2) }} EGP</span>
                            </p>
                            @if($o->rider)
                                <p class="text-xs text-slate-500 mt-1">Rider: <span class="font-medium">{{ $o->rider->user->name }}</span></p>
                            @endif
                        </div>
                        <div class="flex flex-col items-end gap-2 shrink-0">
                            <a href="{{ route('orders.show', $o) }}"
                               class="text-sm bg-slate-900 hover:bg-slate-800 text-white px-3 py-1.5 rounded-lg">View</a>
                            @if(in_array($o->status->value, ['placed', 'confirmed']))
                                <form method="POST" action="{{ route('orders.cancel', $o) }}">
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
</x-layouts.app>
