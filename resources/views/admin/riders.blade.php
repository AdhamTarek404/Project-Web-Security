<x-layouts.app title="Admin · Riders">
    <div class="mb-6">
        <p class="text-sm text-slate-500">Admin</p>
        <h1 class="text-3xl font-bold text-slate-900">All riders</h1>
        <p class="text-sm text-slate-500 mt-1">{{ $riders->total() }} total</p>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600 text-xs uppercase tracking-wide">
                <tr>
                    <th class="text-left px-4 py-2.5">#</th>
                    <th class="text-left px-4 py-2.5">Name</th>
                    <th class="text-left px-4 py-2.5">Email</th>
                    <th class="text-left px-4 py-2.5">Vehicle</th>
                    <th class="text-left px-4 py-2.5">Plate</th>
                    <th class="text-left px-4 py-2.5">Duty</th>
                    <th class="text-left px-4 py-2.5">Avail.</th>
                    <th class="text-left px-4 py-2.5">Location</th>
                    <th class="text-right px-4 py-2.5">Deliveries</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($riders as $r)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-2.5 font-medium">{{ $r->id }}</td>
                        <td class="px-4 py-2.5">{{ $r->user?->name ?? '—' }}</td>
                        <td class="px-4 py-2.5 font-mono text-xs text-slate-600">{{ $r->user?->email }}</td>
                        <td class="px-4 py-2.5">{{ $r->vehicle_type }}</td>
                        <td class="px-4 py-2.5 font-mono text-xs">{{ $r->license_plate ?? '—' }}</td>
                        <td class="px-4 py-2.5">
                            <span class="inline-flex items-center gap-1.5 text-xs">
                                <span class="w-2 h-2 rounded-full {{ $r->is_on_duty ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                                {{ $r->is_on_duty ? 'on duty' : 'off duty' }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5">
                            <span class="text-xs {{ $r->is_available ? 'text-emerald-700' : 'text-slate-500' }}">
                                {{ $r->is_available ? 'yes' : 'no' }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 font-mono text-xs text-slate-600">
                            @if($r->current_latitude)
                                {{ $r->current_latitude }}, {{ $r->current_longitude }}
                            @else <span class="text-slate-400">—</span> @endif
                        </td>
                        <td class="px-4 py-2.5 text-right tabular-nums">{{ $r->orders_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $riders->links() }}
    </div>
</x-layouts.app>
