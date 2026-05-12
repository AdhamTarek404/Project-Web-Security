<x-layouts.app title="Admin · Restaurants">
    <div class="mb-6">
        <p class="text-sm text-slate-500">Admin</p>
        <h1 class="text-3xl font-bold text-slate-900">All restaurants</h1>
        <p class="text-sm text-slate-500 mt-1">{{ $restaurants->total() }} total</p>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600 text-xs uppercase tracking-wide">
                <tr>
                    <th class="text-left px-4 py-2.5">#</th>
                    <th class="text-left px-4 py-2.5">Name</th>
                    <th class="text-left px-4 py-2.5">Owner</th>
                    <th class="text-left px-4 py-2.5">Address</th>
                    <th class="text-left px-4 py-2.5">Status</th>
                    <th class="text-right px-4 py-2.5">Cats</th>
                    <th class="text-right px-4 py-2.5">Orders</th>
                    <th class="text-right px-4 py-2.5">Comm %</th>
                    <th class="text-left px-4 py-2.5">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($restaurants as $r)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-2.5 font-medium">{{ $r->id }}</td>
                        <td class="px-4 py-2.5">
                            <a href="{{ route('restaurants.show', $r) }}" class="text-emerald-700 hover:underline">{{ $r->name }}</a>
                        </td>
                        <td class="px-4 py-2.5 text-slate-500">{{ $r->owner?->name ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-slate-500 text-xs">{{ \Illuminate\Support\Str::limit($r->address, 40) }}</td>
                        <td class="px-4 py-2.5">
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $r->is_open ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                {{ $r->is_open ? 'open' : 'closed' }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-right tabular-nums">{{ $r->categories_count }}</td>
                        <td class="px-4 py-2.5 text-right tabular-nums">{{ $r->orders_count }}</td>
                        <td class="px-4 py-2.5 text-right tabular-nums">{{ number_format($r->commission_rate, 1) }}</td>
                        <td class="px-4 py-2.5">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('owner.restaurants.manage', $r) }}" class="text-xs text-slate-600 hover:text-slate-900">Manage</a>
                                <form method="POST" action="{{ route('admin.restaurants.toggle', $r) }}" class="inline">
                                    @csrf
                                    <button class="text-xs {{ $r->is_open ? 'text-rose-600 hover:text-rose-700' : 'text-emerald-700 hover:text-emerald-800' }}">
                                        Force {{ $r->is_open ? 'close' : 'open' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $restaurants->links() }}
    </div>
</x-layouts.app>
