<x-layouts.app title="Admin · Orders">
    <div class="mb-6 flex items-end justify-between gap-4 flex-wrap">
        <div>
            <p class="text-sm text-slate-500">Admin</p>
            <h1 class="text-3xl font-bold text-slate-900">All orders</h1>
            <p class="text-sm text-slate-500 mt-1">{{ $orders->total() }} total {{ $filter ? "(filtered to '$filter')" : '' }}</p>
        </div>

        <form method="GET" class="flex items-center gap-2">
            <label class="text-xs text-slate-500">Filter by status:</label>
            <select name="status" onchange="this.form.submit()"
                    class="text-sm rounded-lg border-slate-300 px-3 py-1.5">
                <option value="">All</option>
                @foreach($statuses as $s)
                    <option value="{{ $s }}" {{ $filter === $s ? 'selected' : '' }}>{{ $s }}</option>
                @endforeach
            </select>
            @if($filter) <a href="{{ route('admin.orders') }}" class="text-xs text-slate-500 hover:text-slate-800">Clear</a> @endif
        </form>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600 text-xs uppercase tracking-wide">
                <tr>
                    <th class="text-left px-4 py-2.5">#</th>
                    <th class="text-left px-4 py-2.5">Customer</th>
                    <th class="text-left px-4 py-2.5">Restaurant</th>
                    <th class="text-left px-4 py-2.5">Rider</th>
                    <th class="text-left px-4 py-2.5">Status</th>
                    <th class="text-right px-4 py-2.5">Subtotal</th>
                    <th class="text-right px-4 py-2.5">Fee</th>
                    <th class="text-right px-4 py-2.5">Total</th>
                    <th class="text-left px-4 py-2.5">When</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($orders as $o)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-2.5 font-medium">{{ $o->id }}</td>
                        <td class="px-4 py-2.5">{{ $o->customer->name }}</td>
                        <td class="px-4 py-2.5">{{ $o->restaurant->name }}</td>
                        <td class="px-4 py-2.5 text-slate-500">{{ $o->rider?->user?->name ?? '—' }}</td>
                        <td class="px-4 py-2.5"><x-status-badge :status="$o->status" /></td>
                        <td class="px-4 py-2.5 text-right tabular-nums">{{ number_format($o->subtotal / 100, 2) }}</td>
                        <td class="px-4 py-2.5 text-right tabular-nums text-emerald-700">{{ number_format($o->platform_fee / 100, 2) }}</td>
                        <td class="px-4 py-2.5 text-right tabular-nums font-medium">{{ number_format($o->total / 100, 2) }}</td>
                        <td class="px-4 py-2.5 text-slate-500 text-xs">{{ $o->created_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-12 text-center text-slate-500">No orders match this filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $orders->links() }}
    </div>
</x-layouts.app>
