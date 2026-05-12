<x-layouts.app title="Admin · Users">
    <div class="mb-6 flex items-end justify-between gap-4 flex-wrap">
        <div>
            <p class="text-sm text-slate-500">Admin</p>
            <h1 class="text-3xl font-bold text-slate-900">All users</h1>
            <p class="text-sm text-slate-500 mt-1">{{ $users->total() }} total {{ $filter ? "(filtered to '$filter')" : '' }}</p>
        </div>

        <form method="GET" class="flex items-center gap-2">
            <label class="text-xs text-slate-500">Filter by role:</label>
            <select name="role" onchange="this.form.submit()"
                    class="text-sm rounded-lg border-slate-300 px-3 py-1.5">
                <option value="">All</option>
                @foreach($roles as $r)
                    <option value="{{ $r }}" {{ $filter === $r ? 'selected' : '' }}>{{ $r }}</option>
                @endforeach
            </select>
            @if($filter) <a href="{{ route('admin.users') }}" class="text-xs text-slate-500 hover:text-slate-800">Clear</a> @endif
        </form>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600 text-xs uppercase tracking-wide">
                <tr>
                    <th class="text-left px-4 py-2.5">#</th>
                    <th class="text-left px-4 py-2.5">Name</th>
                    <th class="text-left px-4 py-2.5">Email</th>
                    <th class="text-left px-4 py-2.5">Role</th>
                    <th class="text-left px-4 py-2.5">Phone</th>
                    <th class="text-right px-4 py-2.5">Orders</th>
                    <th class="text-right px-4 py-2.5">Restaurants</th>
                    <th class="text-left px-4 py-2.5">Joined</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($users as $u)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-2.5 font-medium">{{ $u->id }}</td>
                        <td class="px-4 py-2.5">{{ $u->name }}</td>
                        <td class="px-4 py-2.5 font-mono text-xs text-slate-600">{{ $u->email }}</td>
                        <td class="px-4 py-2.5">
                            <span @class([
                                'text-xs px-2 py-0.5 rounded-full',
                                'bg-emerald-100 text-emerald-800' => $u->role === 'admin',
                                'bg-sky-100 text-sky-800'         => $u->role === 'customer',
                                'bg-amber-100 text-amber-800'     => $u->role === 'restaurant_owner',
                                'bg-violet-100 text-violet-800'   => $u->role === 'rider',
                            ])>{{ $u->role }}</span>
                        </td>
                        <td class="px-4 py-2.5 text-slate-500 text-xs">{{ $u->phone ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-right tabular-nums">{{ $u->orders_count }}</td>
                        <td class="px-4 py-2.5 text-right tabular-nums">{{ $u->restaurants_count }}</td>
                        <td class="px-4 py-2.5 text-slate-500 text-xs">{{ $u->created_at->format('Y-m-d') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>
</x-layouts.app>
