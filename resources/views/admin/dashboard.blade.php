<x-layouts.app title="Admin · Overview">
    <div class="mb-8">
        <p class="text-sm text-slate-500">Admin</p>
        <h1 class="text-3xl font-bold text-slate-900">System overview</h1>
    </div>

    {{-- KPI strip --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <a href="{{ route('admin.orders') }}" class="block bg-white border border-slate-200 rounded-xl p-5 hover:border-emerald-300">
            <p class="text-xs uppercase tracking-wide text-slate-500">Total orders</p>
            <p class="text-3xl font-semibold mt-1">{{ $counts['orders'] }}</p>
            <p class="text-xs text-slate-500 mt-1">{{ $counts['active_orders'] }} active</p>
        </a>
        <a href="{{ route('admin.users') }}" class="block bg-white border border-slate-200 rounded-xl p-5 hover:border-emerald-300">
            <p class="text-xs uppercase tracking-wide text-slate-500">Users</p>
            <p class="text-3xl font-semibold mt-1">{{ $counts['users'] }}</p>
            <p class="text-xs text-slate-500 mt-1">{{ $counts['customers'] }} customers · {{ $counts['owners'] }} owners · {{ $counts['riders'] }} riders</p>
        </a>
        <a href="{{ route('admin.restaurants') }}" class="block bg-white border border-slate-200 rounded-xl p-5 hover:border-emerald-300">
            <p class="text-xs uppercase tracking-wide text-slate-500">Restaurants</p>
            <p class="text-3xl font-semibold mt-1">{{ $counts['restaurants'] }}</p>
            <p class="text-xs text-emerald-700 mt-1">{{ $counts['open'] }} open now</p>
        </a>
        <a href="{{ route('admin.riders') }}" class="block bg-white border border-slate-200 rounded-xl p-5 hover:border-emerald-300">
            <p class="text-xs uppercase tracking-wide text-slate-500">Riders on duty</p>
            <p class="text-3xl font-semibold mt-1 text-emerald-700">{{ $counts['on_duty'] }}</p>
            <p class="text-xs text-slate-500 mt-1">of {{ $counts['riders'] }} total</p>
        </a>
    </div>

    {{-- Revenue --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
        <div class="bg-gradient-to-br from-emerald-600 to-teal-500 text-white rounded-xl p-6">
            <p class="text-emerald-100 text-sm">GMV (delivered orders)</p>
            <p class="text-3xl font-bold mt-1">{{ number_format($counts['gmv_cents'] / 100, 2) }} <span class="text-base font-normal">EGP</span></p>
        </div>
        <div class="bg-gradient-to-br from-slate-900 to-slate-700 text-white rounded-xl p-6">
            <p class="text-slate-300 text-sm">Platform fee earned</p>
            <p class="text-3xl font-bold mt-1">{{ number_format($counts['platform_fee'] / 100, 2) }} <span class="text-base font-normal">EGP</span></p>
        </div>
    </div>

    {{-- Quick links --}}
    <div class="grid grid-cols-2 md:grid-cols-6 gap-3 mb-8">
        @foreach([
            ['admin.orders',      'All orders'],
            ['admin.users',       'All users'],
            ['admin.restaurants', 'All restaurants'],
            ['admin.riders',      'All riders'],
            ['admin.control-tower', 'Live map'],
            ['admin.surge',       '⚡ Surge tester'],
        ] as [$route, $label])
            <a href="{{ route($route) }}" class="bg-white border border-slate-200 rounded-lg p-3 text-center text-sm font-medium hover:border-emerald-300 hover:text-emerald-700">
                {{ $label }} →
            </a>
        @endforeach
    </div>

    {{-- Recent orders preview --}}
    <h2 class="text-lg font-semibold text-slate-900 mb-3">Latest orders</h2>
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600 text-xs uppercase tracking-wide">
                <tr>
                    <th class="text-left px-4 py-2.5">#</th>
                    <th class="text-left px-4 py-2.5">Customer</th>
                    <th class="text-left px-4 py-2.5">Restaurant</th>
                    <th class="text-left px-4 py-2.5">Status</th>
                    <th class="text-right px-4 py-2.5">Total</th>
                    <th class="text-left px-4 py-2.5">When</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($recentOrders as $o)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-2.5 font-medium">{{ $o->id }}</td>
                        <td class="px-4 py-2.5">{{ $o->customer->name }}</td>
                        <td class="px-4 py-2.5">{{ $o->restaurant->name }}</td>
                        <td class="px-4 py-2.5"><x-status-badge :status="$o->status" /></td>
                        <td class="px-4 py-2.5 text-right tabular-nums">{{ number_format($o->total / 100, 2) }} EGP</td>
                        <td class="px-4 py-2.5 text-slate-500 text-xs">{{ $o->created_at->diffForHumans() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.app>
