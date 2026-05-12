<x-layouts.app :title="'Order #'.$order->id">
    <a href="{{ route('dashboard') }}" class="text-sm text-slate-500 hover:text-slate-800 inline-flex items-center gap-1">
        <span>←</span> Back to dashboard
    </a>

    <div class="mt-4 mb-8 flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Order #{{ $order->id }}</h1>
            <p class="text-sm text-slate-500 mt-1">Placed {{ $order->created_at->diffForHumans() }}</p>
        </div>
        <x-status-badge :status="$order->status" class="text-sm px-3 py-1" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Items --}}
        <section class="lg:col-span-2 bg-white border border-slate-200 rounded-xl p-6">
            <h2 class="font-semibold text-slate-900 mb-4">Items</h2>
            <ul class="divide-y">
                @foreach($order->items as $i)
                    <li class="py-3 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium text-slate-900">{{ $i->menuItem->name }}</p>
                            @if($i->special_instructions)
                                <p class="text-xs text-slate-500 mt-0.5">{{ $i->special_instructions }}</p>
                            @endif
                            <p class="text-xs text-slate-500 mt-0.5">Qty {{ $i->quantity }} · {{ number_format($i->unit_price/100, 2) }} EGP each</p>
                        </div>
                        <span class="font-medium tabular-nums text-slate-900">{{ number_format($i->line_total / 100, 2) }} EGP</span>
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- Money --}}
        <section class="bg-white border border-slate-200 rounded-xl p-6 space-y-2 text-sm h-fit">
            <h2 class="font-semibold text-slate-900 mb-3">Receipt</h2>
            <div class="flex justify-between text-slate-600"><span>Subtotal</span><span class="tabular-nums">{{ number_format($order->subtotal/100, 2) }}</span></div>
            <div class="flex justify-between text-slate-600"><span>Delivery fee</span><span class="tabular-nums">{{ number_format($order->delivery_fee/100, 2) }}</span></div>
            <div class="flex justify-between text-slate-600"><span>Surge</span><span>{{ $order->surge_multiplier }}×</span></div>
            <div class="flex justify-between text-slate-600"><span>Platform fee</span><span class="tabular-nums">{{ number_format($order->platform_fee/100, 2) }}</span></div>
            <div class="flex justify-between text-slate-600 text-xs"><span class="pl-3">→ Restaurant payout</span><span class="tabular-nums">{{ number_format($order->restaurant_payout/100, 2) }}</span></div>
            <div class="flex justify-between text-slate-600 text-xs"><span class="pl-3">→ Rider payout</span><span class="tabular-nums">{{ number_format($order->rider_payout/100, 2) }}</span></div>
            <div class="flex justify-between font-semibold pt-3 mt-3 border-t text-slate-900">
                <span>Total</span><span class="tabular-nums">{{ number_format($order->total/100, 2) }} EGP</span>
            </div>
            <p class="pt-3 text-xs text-slate-400">
                Payment ref: <code class="font-mono">{{ $order->payment_intent_id ?? '—' }}</code>
            </p>
        </section>

        {{-- Rating (customer, after delivered) --}}
        @if($order->status->value === 'delivered' && $order->customer_id === auth()->id())
            @php
                $restaurantRating = $order->load('ratings')->ratings->where('rateable_type', \App\Models\Restaurant::class)->first();
                $riderRating      = $order->ratings->where('rateable_type', \App\Models\Rider::class)->first();
            @endphp
            <section class="lg:col-span-3 bg-white border border-emerald-200 rounded-xl p-6">
                <h2 class="font-semibold text-slate-900 mb-1">Rate your experience</h2>
                <p class="text-xs text-slate-500 mb-4">Tell us how it went — your feedback updates the restaurant's and rider's public reputation.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Restaurant rating --}}
                    <form method="POST" action="{{ route('orders.rate', $order) }}" class="space-y-3">
                        @csrf
                        <input type="hidden" name="target" value="restaurant">
                        <p class="font-medium text-slate-700">Restaurant: {{ $order->restaurant->name }}</p>

                        @if($restaurantRating)
                            <p class="text-sm text-emerald-700">
                                You gave {{ $restaurantRating->stars }}★
                                @if($restaurantRating->comment) — "{{ $restaurantRating->comment }}" @endif
                            </p>
                            <p class="text-xs text-slate-500">You can update it by submitting again.</p>
                        @endif

                        <div class="flex items-center gap-1">
                            @for($i = 1; $i <= 5; $i++)
                                <label class="cursor-pointer">
                                    <input type="radio" name="stars" value="{{ $i }}" {{ $restaurantRating && $restaurantRating->stars === $i ? 'checked' : ($i === 5 ? 'checked' : '') }} class="peer sr-only">
                                    <span class="text-2xl text-slate-300 peer-checked:text-amber-400 hover:text-amber-400">★</span>
                                </label>
                            @endfor
                        </div>
                        <textarea name="comment" rows="2" maxlength="1000" placeholder="Optional comment"
                                  class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500/30 px-3 py-2">{{ $restaurantRating?->comment }}</textarea>
                        <button class="bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-medium px-4 py-2 rounded-lg">
                            {{ $restaurantRating ? 'Update' : 'Submit' }} restaurant rating
                        </button>
                    </form>

                    {{-- Rider rating --}}
                    @if($order->rider)
                        <form method="POST" action="{{ route('orders.rate', $order) }}" class="space-y-3">
                            @csrf
                            <input type="hidden" name="target" value="rider">
                            <p class="font-medium text-slate-700">Rider: {{ $order->rider->user->name }}</p>

                            @if($riderRating)
                                <p class="text-sm text-emerald-700">
                                    You gave {{ $riderRating->stars }}★
                                    @if($riderRating->comment) — "{{ $riderRating->comment }}" @endif
                                </p>
                            @endif

                            <div class="flex items-center gap-1">
                                @for($i = 1; $i <= 5; $i++)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="stars" value="{{ $i }}" {{ $riderRating && $riderRating->stars === $i ? 'checked' : ($i === 5 ? 'checked' : '') }} class="peer sr-only">
                                        <span class="text-2xl text-slate-300 peer-checked:text-amber-400 hover:text-amber-400">★</span>
                                    </label>
                                @endfor
                            </div>
                            <textarea name="comment" rows="2" maxlength="1000" placeholder="Optional comment"
                                      class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500/30 px-3 py-2">{{ $riderRating?->comment }}</textarea>
                            <button class="bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-medium px-4 py-2 rounded-lg">
                                {{ $riderRating ? 'Update' : 'Submit' }} rider rating
                            </button>
                        </form>
                    @endif
                </div>
            </section>
        @endif

        {{-- Audit log --}}
        <section class="lg:col-span-3 bg-white border border-slate-200 rounded-xl p-6">
            <h2 class="font-semibold text-slate-900 mb-1">Audit trail</h2>
            <p class="text-xs text-slate-500 mb-4">Append-only event log. Every status change is recorded with timestamp, actor, and reason.</p>

            <ol class="relative border-l-2 border-slate-100 ml-2 space-y-4">
                @foreach($order->statusHistory as $h)
                    <li class="ml-4">
                        <span class="absolute -left-[7px] w-3 h-3 rounded-full bg-emerald-500 ring-4 ring-emerald-50"></span>
                        <div class="flex items-baseline gap-3 flex-wrap">
                            <span class="font-medium text-slate-900">{{ $h->from_status ?? '(new)' }} → {{ $h->to_status }}</span>
                            <span class="text-xs text-slate-500">by {{ $h->actor_type }}{{ $h->actor_id ? " #{$h->actor_id}" : '' }}</span>
                            <span class="text-xs text-slate-400 font-mono">{{ $h->occurred_at }}</span>
                        </div>
                        @if($h->reason)
                            <p class="text-sm text-slate-600 mt-1">{{ $h->reason }}</p>
                        @endif
                    </li>
                @endforeach
            </ol>
        </section>
    </div>
</x-layouts.app>
