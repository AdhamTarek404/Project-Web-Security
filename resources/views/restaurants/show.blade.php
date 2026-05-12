<x-layouts.app :title="$restaurant->name">

    <a href="{{ route('home') }}" class="text-sm text-slate-500 hover:text-slate-800 inline-flex items-center gap-1">
        <span>←</span> All restaurants
    </a>

    <header class="mt-4 mb-8 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-700 text-white rounded-2xl p-8 shadow">
        <h1 class="text-3xl md:text-4xl font-bold">{{ $restaurant->name }}</h1>
        <p class="text-slate-300 mt-2">{{ $restaurant->address }}</p>
        <div class="mt-4 flex flex-wrap gap-2 text-xs">
            <span class="bg-emerald-500/20 text-emerald-200 px-3 py-1 rounded-full">Open now</span>
            <span class="bg-white/10 text-slate-200 px-3 py-1 rounded-full">{{ number_format($restaurant->commission_rate, 1) }}% platform commission</span>
            <span class="bg-white/10 text-slate-200 px-3 py-1 rounded-full">~5 km delivery radius</span>
        </div>
    </header>

    @auth
        @if(auth()->user()->isCustomer())
            {{-- Customer view: full ordering UI with a live cart sidebar. --}}
            <div x-data="orderForm()" class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <div class="lg:col-span-2 space-y-8">
                    @foreach($restaurant->categories as $cat)
                        <section>
                            <h2 class="text-xl font-semibold mb-3 text-slate-900 flex items-center gap-2">
                                {{ $cat->name }}
                                <span class="text-xs font-normal text-slate-400">{{ $cat->menuItems->count() }} items</span>
                            </h2>

                            <div class="space-y-3">
                                @foreach($cat->menuItems as $item)
                                    <div @class([
                                        'bg-white border border-slate-200 rounded-xl p-5 flex items-center justify-between gap-4 transition',
                                        'opacity-50' => ! $item->is_available,
                                        'hover:border-emerald-300 hover:shadow-sm' => $item->is_available,
                                    ])>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <h3 class="font-medium text-slate-900">{{ $item->name }}</h3>
                                                @if(! $item->is_available)
                                                    <span class="text-xs px-2 py-0.5 rounded bg-slate-200 text-slate-600">Unavailable</span>
                                                @endif
                                            </div>
                                            @if($item->description)
                                                <p class="text-sm text-slate-500 mt-1 line-clamp-2">{{ $item->description }}</p>
                                            @endif
                                            <p class="text-sm font-semibold text-emerald-700 mt-2">
                                                {{ number_format($item->base_price / 100, 2) }} EGP
                                            </p>
                                        </div>

                                        @if($item->is_available)
                                            <div class="flex flex-col items-end gap-2 shrink-0">
                                                @if($item->variants->isNotEmpty())
                                                    <select
                                                        x-model="lines[{{ $item->id }}].variantId"
                                                        @change="lines[{{ $item->id }}].variantPrice = parseInt($event.target.selectedOptions[0].dataset.mod)"
                                                        class="text-sm rounded-lg border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 px-3 py-1.5 w-40">
                                                        @foreach($item->variants as $v)
                                                            <option value="{{ $v->id }}" data-mod="{{ $v->price_modifier }}" {{ $v->is_default ? 'selected' : '' }}>
                                                                {{ $v->name }} @if($v->price_modifier) (+{{ number_format($v->price_modifier / 100, 2) }}) @endif
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                @endif

                                                <div class="flex items-center gap-1 bg-slate-100 rounded-lg p-1">
                                                    <button type="button"
                                                            @click="lines[{{ $item->id }}].qty = Math.max(0, lines[{{ $item->id }}].qty - 1)"
                                                            class="w-8 h-8 rounded-md bg-white text-slate-700 hover:bg-slate-50 shadow-sm flex items-center justify-center font-semibold">−</button>
                                                    <span class="w-8 text-center font-medium tabular-nums" x-text="lines[{{ $item->id }}].qty"></span>
                                                    <button type="button"
                                                            @click="lines[{{ $item->id }}].qty++"
                                                            class="w-8 h-8 rounded-md bg-emerald-500 text-white hover:bg-emerald-600 shadow-sm flex items-center justify-center font-semibold">+</button>
                                                </div>

                                                <input type="hidden" :value="{{ $item->base_price }}" x-init="lines[{{ $item->id }}] = lines[{{ $item->id }}] || { qty: 0, variantId: '{{ optional($item->variants->firstWhere('is_default', true))->id ?? '' }}', variantPrice: {{ optional($item->variants->firstWhere('is_default', true))->price_modifier ?? 0 }}, basePrice: {{ $item->base_price }} }">
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>

                {{-- Live cart sidebar --}}
                <aside class="lg:col-span-1">
                    <div class="lg:sticky lg:top-6 bg-white border border-slate-200 rounded-xl shadow-sm p-6 space-y-4">
                        <h2 class="text-lg font-semibold text-slate-900">Your order</h2>

                        <template x-if="totalQty() === 0">
                            <p class="text-sm text-slate-500">No items yet. Tap <span class="inline-block w-5 h-5 rounded bg-emerald-500 text-white text-xs leading-5 text-center">+</span> on a menu item.</p>
                        </template>

                        <template x-if="totalQty() > 0">
                            <div>
                                <ul class="space-y-2 text-sm border-b pb-3 mb-3 max-h-60 overflow-y-auto">
                                    <template x-for="(line, id) in lines" :key="id">
                                        <li x-show="line.qty > 0" class="flex justify-between gap-3">
                                            <span class="text-slate-700">
                                                <span x-text="line.qty"></span> ×
                                                <span x-text="nameFor(id)"></span>
                                            </span>
                                            <span class="font-mono tabular-nums text-slate-900"
                                                  x-text="((line.basePrice + line.variantPrice) * line.qty / 100).toFixed(2)"></span>
                                        </li>
                                    </template>
                                </ul>
                                <div class="flex justify-between text-sm text-slate-600">
                                    <span>Subtotal</span>
                                    <span class="font-mono tabular-nums" x-text="subtotal().toFixed(2) + ' EGP'"></span>
                                </div>
                                <div class="flex justify-between text-sm text-slate-600">
                                    <span>+ Delivery (computed at checkout)</span>
                                    <span class="font-mono tabular-nums">50.00 EGP*</span>
                                </div>
                                <p class="text-xs text-slate-400 mt-1">*Surge, platform fee &amp; split are computed server-side.</p>
                            </div>
                        </template>

                        <form method="POST" action="{{ route('orders.place', $restaurant) }}" @submit="serializeForm">
                            @csrf
                            <input type="text" name="delivery_address" value="5 Customer St, Cairo"
                                   class="w-full text-sm rounded-lg border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 px-3 py-2 mb-3"
                                   placeholder="Delivery address">

                            <textarea name="notes" rows="2" maxlength="500" placeholder="Special instructions (e.g. ring the bell, no onions)"
                                      class="w-full text-sm rounded-lg border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 px-3 py-2 mb-3"></textarea>

                            <details class="text-xs text-slate-500 mb-3">
                                <summary class="cursor-pointer hover:text-slate-700">Pickup coordinates (advanced)</summary>
                                <div class="grid grid-cols-2 gap-2 mt-2">
                                    <input type="number" step="0.0000001" name="delivery_latitude" value="30.0500"
                                           class="w-full text-xs rounded border-slate-300">
                                    <input type="number" step="0.0000001" name="delivery_longitude" value="31.2400"
                                           class="w-full text-xs rounded border-slate-300">
                                </div>
                            </details>

                            <button type="submit"
                                    :disabled="totalQty() === 0"
                                    :class="totalQty() === 0 ? 'bg-slate-300 cursor-not-allowed' : 'bg-emerald-600 hover:bg-emerald-500'"
                                    class="w-full text-white font-medium py-2.5 rounded-lg transition">
                                <span x-show="totalQty() === 0">Add items to order</span>
                                <span x-show="totalQty() > 0">
                                    Place order — <span x-text="subtotal().toFixed(2)"></span> EGP
                                </span>
                            </button>

                            {{-- Hidden inputs populated on submit. --}}
                            <div id="hidden-items"></div>
                        </form>
                    </div>
                </aside>
            </div>

            @push('scripts')
            <script>
                const ITEM_NAMES = @json($restaurant->categories->flatMap->menuItems->mapWithKeys(fn($i) => [$i->id => $i->name]));

                function orderForm() {
                    return {
                        lines: {},
                        nameFor(id) { return ITEM_NAMES[id] || '?'; },
                        totalQty() { return Object.values(this.lines).reduce((s, l) => s + (l.qty || 0), 0); },
                        subtotal() {
                            return Object.values(this.lines)
                                .reduce((s, l) => s + (l.qty * (l.basePrice + (l.variantPrice || 0))), 0) / 100;
                        },
                        serializeForm(e) {
                            const container = e.target.querySelector('#hidden-items');
                            container.innerHTML = '';
                            for (const [id, line] of Object.entries(this.lines)) {
                                if (line.qty <= 0) continue;
                                const mk = (name, val) => {
                                    const i = document.createElement('input');
                                    i.type = 'hidden';
                                    i.name = name;
                                    i.value = val;
                                    container.appendChild(i);
                                };
                                mk(`items[${id}][menu_item_id]`, id);
                                mk(`items[${id}][quantity]`, line.qty);
                                if (line.variantId) mk(`items[${id}][variant_id]`, line.variantId);
                            }
                        },
                    };
                }
            </script>
            @endpush
        @else
            {{-- Owner/rider/admin viewing the menu — read-only preview. --}}
            <div class="space-y-8">
                @foreach($restaurant->categories as $cat)
                    <section>
                        <h2 class="text-xl font-semibold mb-3">{{ $cat->name }}</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($cat->menuItems as $item)
                                <div class="bg-white border border-slate-200 rounded-xl p-4 {{ $item->is_available ? '' : 'opacity-50' }}">
                                    <div class="flex justify-between">
                                        <h3 class="font-medium">{{ $item->name }}</h3>
                                        <span class="font-semibold text-emerald-700">{{ number_format($item->base_price / 100, 2) }} EGP</span>
                                    </div>
                                    @if($item->description) <p class="text-sm text-slate-500 mt-1">{{ $item->description }}</p> @endif
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        @endif
    @else
        <div class="space-y-8">
            @foreach($restaurant->categories as $cat)
                <section>
                    <h2 class="text-xl font-semibold mb-3">{{ $cat->name }}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach($cat->menuItems as $item)
                            <div class="bg-white border border-slate-200 rounded-xl p-4 {{ $item->is_available ? '' : 'opacity-50' }}">
                                <div class="flex justify-between">
                                    <h3 class="font-medium">{{ $item->name }}</h3>
                                    <span class="font-semibold text-emerald-700">{{ number_format($item->base_price / 100, 2) }} EGP</span>
                                </div>
                                @if($item->description) <p class="text-sm text-slate-500 mt-1">{{ $item->description }}</p> @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        <div class="mt-8 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl p-5 text-sm">
            <a href="{{ route('login') }}" class="font-semibold underline">Sign in as a customer</a> to place an order.
        </div>
    @endauth
</x-layouts.app>
