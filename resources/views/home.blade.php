<x-layouts.app title="Restaurants">

    <section class="bg-gradient-to-br from-emerald-600 via-emerald-500 to-teal-500 text-white rounded-2xl p-8 md:p-12 shadow-lg mb-10">
        <div class="max-w-2xl">
            <h1 class="text-3xl md:text-5xl font-bold leading-tight">Hungry?</h1>
            <p class="text-emerald-50 mt-3 text-lg">
                Order from real restaurants. Riders auto-dispatched. Live tracking.
            </p>
            @guest
                <a href="{{ route('login') }}"
                   class="inline-block mt-6 bg-white text-emerald-700 font-semibold px-5 py-2.5 rounded-lg hover:bg-emerald-50 transition">
                    Sign in to order →
                </a>
            @else
                @if(auth()->user()->isCustomer())
                    <p class="text-emerald-50 mt-6 text-sm">Pick a restaurant below to start ordering.</p>
                @endif
            @endguest
        </div>
    </section>

    <div class="flex items-baseline justify-between mb-4">
        <h2 class="text-xl font-semibold text-slate-900">Open restaurants</h2>
        <span class="text-sm text-slate-500">{{ $restaurants->count() }} open</span>
    </div>

    @if($restaurants->isEmpty())
        <div class="bg-white border border-slate-200 rounded-xl p-12 text-center text-slate-500">
            No restaurants are open right now.
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($restaurants as $r)
                @php
                    $colors = ['from-rose-500 to-orange-500', 'from-amber-500 to-yellow-500', 'from-emerald-500 to-teal-500', 'from-sky-500 to-indigo-500', 'from-violet-500 to-fuchsia-500'];
                    $color = $colors[$r->id % count($colors)];
                @endphp
                <a href="{{ route('restaurants.show', $r) }}"
                   class="group block bg-white border border-slate-200 rounded-xl overflow-hidden hover:shadow-lg hover:-translate-y-0.5 hover:border-emerald-300 transition">
                    <div class="h-28 bg-gradient-to-br {{ $color }} flex items-center justify-center">
                        <span class="text-white/90 text-4xl font-bold">{{ mb_substr($r->name, 0, 1) }}</span>
                    </div>
                    <div class="p-5">
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="text-lg font-semibold text-slate-900 group-hover:text-emerald-700">{{ $r->name }}</h3>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 shrink-0">Open</span>
                        </div>
                        <p class="text-sm text-slate-500 mt-1 line-clamp-1">{{ $r->address }}</p>
                        <div class="mt-3 flex items-center gap-3 text-xs text-slate-500">
                            <span>★ {{ $r->ratings_count }} {{ \Illuminate\Support\Str::plural('review', $r->ratings_count) }}</span>
                            <span>·</span>
                            <span>{{ number_format($r->commission_rate, 1) }}% fee</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</x-layouts.app>
