<x-layouts.app title="Admin · Surge pricing playground">
    <div class="mb-6">
        <p class="text-sm text-slate-500">Admin</p>
        <h1 class="text-3xl font-bold text-slate-900">Surge pricing playground</h1>
        <p class="text-sm text-slate-500 mt-1">
            Play with the inputs. The multiplier on the right updates with every change.
            This is the exact engine that runs when a customer places an order.
        </p>
    </div>

    {{-- LIVE COUNTS — shows what the engine sees right now in the real DB. --}}
    <div class="bg-white border border-slate-200 rounded-xl p-4 mb-6 flex items-center gap-6 text-sm">
        <div>
            <span class="text-slate-500">Live active orders right now:</span>
            <strong class="text-slate-900">{{ $liveOrders }}</strong>
        </div>
        <div>
            <span class="text-slate-500">Live available riders right now:</span>
            <strong class="text-slate-900">{{ $liveRiders }}</strong>
        </div>
        <a href="{{ route('admin.surge', ['orders' => $liveOrders, 'riders' => max(1,$liveRiders), 'weather' => 'clear', 'hour' => (int) now()->format('G')]) }}"
           class="ml-auto text-xs text-emerald-700 hover:underline">↻ Use current live values</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- LEFT: form --}}
        <form method="GET" class="lg:col-span-2 bg-white border border-slate-200 rounded-xl p-6 space-y-6">
            {{-- Demand --}}
            <div>
                <div class="flex justify-between items-baseline">
                    <label class="text-sm font-semibold text-slate-900">Demand: active orders</label>
                    <output class="text-2xl font-bold text-emerald-700 tabular-nums" id="ordersOut">{{ $orders }}</output>
                </div>
                <input type="range" name="orders" min="0" max="100" step="1" value="{{ $orders }}"
                       oninput="document.getElementById('ordersOut').textContent = this.value"
                       onchange="this.form.submit()"
                       class="w-full mt-2 accent-emerald-600">
                <p class="text-xs text-slate-500 mt-1">How many orders are currently <code class="bg-slate-100 px-1 rounded">placed/confirmed/preparing/on_the_way</code> in the system.</p>
            </div>

            {{-- Supply --}}
            <div>
                <div class="flex justify-between items-baseline">
                    <label class="text-sm font-semibold text-slate-900">Supply: available riders</label>
                    <output class="text-2xl font-bold text-emerald-700 tabular-nums" id="ridersOut">{{ $riders }}</output>
                </div>
                <input type="range" name="riders" min="1" max="100" step="1" value="{{ $riders }}"
                       oninput="document.getElementById('ridersOut').textContent = this.value"
                       onchange="this.form.submit()"
                       class="w-full mt-2 accent-emerald-600">
                <p class="text-xs text-slate-500 mt-1">On-duty and available right now. The engine computes <code class="bg-slate-100 px-1 rounded">ratio = orders / riders</code>.</p>
            </div>

            {{-- Weather --}}
            <div>
                <label class="block text-sm font-semibold text-slate-900 mb-2">Weather</label>
                <div class="grid grid-cols-3 gap-3">
                    @foreach(['clear' => 'Clear', 'rain' => 'Rain (+0.25)', 'storm' => 'Storm (+0.50)'] as $w => $label)
                        <label class="cursor-pointer">
                            <input type="radio" name="weather" value="{{ $w }}" {{ $weather === $w ? 'checked' : '' }} onchange="this.form.submit()" class="peer sr-only">
                            <div class="border-2 border-slate-200 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 rounded-lg p-3 text-center text-sm transition">
                                {{ $label }}
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Time --}}
            <div>
                <div class="flex justify-between items-baseline">
                    <label class="text-sm font-semibold text-slate-900">Time of day</label>
                    <output class="text-2xl font-bold text-emerald-700 tabular-nums">{{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}:00</output>
                </div>
                <input type="range" name="hour" min="0" max="23" step="1" value="{{ $hour }}"
                       onchange="this.form.submit()"
                       class="w-full mt-2 accent-emerald-600">
                <div class="flex justify-between text-xs text-slate-500 mt-1">
                    <span>00:00</span>
                    <span class="text-amber-600 font-medium">12–14 lunch</span>
                    <span class="text-amber-600 font-medium">19–22 dinner</span>
                    <span>23:00</span>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    @if($inRush)
                        <span class="text-amber-700">⚡ Rush hour — adds 0.25 to the multiplier.</span>
                    @else
                        Off-peak — no time bump.
                    @endif
                </p>
            </div>

            <noscript>
                <button class="bg-emerald-600 text-white px-4 py-2 rounded-lg">Recalculate</button>
            </noscript>
        </form>

        {{-- RIGHT: result --}}
        <div class="space-y-4">
            {{-- Big multiplier card --}}
            <div @class([
                'rounded-xl p-6 text-white text-center shadow-lg bg-gradient-to-br',
                'from-emerald-600 to-teal-500' => $final <= 1.0,
                'from-amber-500 to-orange-500' => $final > 1.0 && $final < 2.0,
                'from-orange-500 to-rose-500'  => $final >= 2.0 && $final < $cap,
                'from-rose-600 to-purple-700'  => $final >= $cap,
            ])>
                <p class="text-sm uppercase tracking-wide opacity-80">Final multiplier</p>
                <p class="text-6xl font-bold tabular-nums mt-2">{{ number_format($final, 2) }}<span class="text-3xl">×</span></p>
                @if($final >= $cap)
                    <p class="text-xs mt-2 opacity-90">⚠ Capped at {{ $cap }}× (engine protection)</p>
                @elseif($final == 1.0)
                    <p class="text-xs mt-2 opacity-90">No surge — riders keep up with demand.</p>
                @else
                    <p class="text-xs mt-2 opacity-90">Active surge in effect.</p>
                @endif
            </div>

            {{-- Breakdown --}}
            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <h2 class="text-sm font-semibold text-slate-900 mb-3">Strategy breakdown</h2>
                <ul class="space-y-2 text-sm">
                    @foreach($breakdown as $label => $value)
                        <li class="flex justify-between items-baseline">
                            <span class="text-slate-700 text-xs">{{ $label }}</span>
                            <span class="font-mono tabular-nums {{ $value > 1.0 ? 'text-amber-700 font-semibold' : 'text-slate-500' }}">
                                {{ number_format($value, 2) }}×
                            </span>
                        </li>
                    @endforeach
                </ul>
                <p class="text-xs text-slate-500 mt-3 pt-3 border-t">
                    Engine sums each strategy's <em>bump above 1.00</em>, adds 1.00 back, caps at {{ $cap }}.
                    Current ratio: <strong>{{ $orders }} / {{ $riders }} = {{ $ratio }}</strong>.
                </p>
            </div>

            {{-- Effect on a sample order --}}
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 text-sm">
                <h2 class="font-semibold text-slate-900 mb-3">What this does to a sample order</h2>
                @php
                    $subtotal = 30000;
                    $baseDelivery = 5000;
                    $surgedDelivery = (int) round($baseDelivery * $final);
                    $total = $subtotal + $surgedDelivery;
                @endphp
                <table class="w-full">
                    <tr class="text-slate-600">
                        <td>Subtotal</td>
                        <td class="text-right tabular-nums">{{ number_format($subtotal / 100, 2) }} EGP</td>
                    </tr>
                    <tr class="text-slate-600">
                        <td>Delivery (base 50.00)</td>
                        <td class="text-right tabular-nums">
                            <span class="text-slate-400 line-through">50.00</span>
                            <span class="font-semibold {{ $final > 1.0 ? 'text-rose-700' : '' }}">{{ number_format($surgedDelivery / 100, 2) }}</span>
                        </td>
                    </tr>
                    <tr class="font-semibold text-slate-900 border-t pt-2">
                        <td class="pt-2">Total</td>
                        <td class="text-right tabular-nums pt-2">{{ number_format($total / 100, 2) }} EGP</td>
                    </tr>
                </table>
            </div>

            {{-- Where it comes from --}}
            <div class="bg-white border border-dashed border-slate-300 rounded-xl p-4 text-xs text-slate-500">
                Source code:
                <ul class="list-disc ml-5 mt-1 space-y-0.5">
                    <li><code>app/Services/Pricing/SurgePricingEngine.php</code></li>
                    <li><code>app/Services/Pricing/MultiplierSurgeStrategy.php</code></li>
                    <li><code>app/Services/Pricing/TimeBasedSurgeStrategy.php</code></li>
                </ul>
                Stored on every order at <code>orders.surge_multiplier</code>.
            </div>
        </div>
    </div>

    {{-- Bottom: real orders showing real surge values --}}
    <section class="mt-10">
        <h2 class="text-lg font-semibold text-slate-900 mb-3">Surge on real orders in the database</h2>
        @php
            $recent = \App\Models\Order::with(['customer', 'restaurant'])->latest()->take(8)->get();
        @endphp
        @if($recent->isEmpty())
            <div class="bg-white border border-slate-200 rounded-xl p-6 text-center text-slate-500 text-sm">
                No orders yet. Place one as a customer to see the surge multiplier applied for real.
            </div>
        @else
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600 text-xs uppercase tracking-wide">
                        <tr>
                            <th class="text-left px-4 py-2.5">Order</th>
                            <th class="text-left px-4 py-2.5">Restaurant</th>
                            <th class="text-right px-4 py-2.5">Subtotal</th>
                            <th class="text-right px-4 py-2.5">Delivery</th>
                            <th class="text-right px-4 py-2.5">Surge</th>
                            <th class="text-right px-4 py-2.5">Total</th>
                            <th class="text-left px-4 py-2.5">Placed at</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($recent as $o)
                            <tr>
                                <td class="px-4 py-2.5 font-medium">#{{ $o->id }}</td>
                                <td class="px-4 py-2.5">{{ $o->restaurant->name }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums">{{ number_format($o->subtotal/100, 2) }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums">{{ number_format($o->delivery_fee/100, 2) }}</td>
                                <td class="px-4 py-2.5 text-right">
                                    <span @class([
                                        'inline-flex items-center text-xs font-medium px-2 py-0.5 rounded-full tabular-nums',
                                        'bg-slate-100 text-slate-700' => $o->surge_multiplier == 1.0,
                                        'bg-amber-100 text-amber-800' => $o->surge_multiplier > 1.0 && $o->surge_multiplier < 2.0,
                                        'bg-rose-100 text-rose-800'   => $o->surge_multiplier >= 2.0,
                                    ])>{{ $o->surge_multiplier }}×</span>
                                </td>
                                <td class="px-4 py-2.5 text-right tabular-nums font-medium">{{ number_format($o->total/100, 2) }}</td>
                                <td class="px-4 py-2.5 text-slate-500 text-xs">{{ optional($o->placed_at)->format('Y-m-d H:i') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-layouts.app>
