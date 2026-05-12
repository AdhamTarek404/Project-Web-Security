<x-layouts.app :title="'Manage · '.$restaurant->name">
    <a href="{{ route('dashboard') }}" class="text-sm text-slate-500 hover:text-slate-800 inline-flex items-center gap-1">
        <span>←</span> Back to dashboard
    </a>

    {{-- Header --}}
    <header class="mt-4 mb-8 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-700 text-white rounded-2xl p-6 md:p-8 shadow">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">Managing</p>
                <h1 class="text-3xl md:text-4xl font-bold">{{ $restaurant->name }}</h1>
                <p class="text-slate-300 mt-1">{{ $restaurant->address }}</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="px-3 py-1 text-xs rounded-full
                    {{ $restaurant->is_open ? 'bg-emerald-500/20 text-emerald-200' : 'bg-rose-500/20 text-rose-200' }}">
                    {{ $restaurant->is_open ? 'OPEN' : 'CLOSED' }}
                </span>
                <form method="POST" action="{{ route('owner.restaurants.toggle-open', $restaurant) }}">
                    @csrf
                    <button class="bg-white/10 hover:bg-white/20 text-white text-sm px-3 py-1.5 rounded-lg">
                        {{ $restaurant->is_open ? 'Close' : 'Open' }} restaurant
                    </button>
                </form>
            </div>
        </div>
    </header>

    {{-- Basic info --}}
    <section class="mb-8 bg-white border border-slate-200 rounded-xl p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Basic info</h2>
        <form method="POST" action="{{ route('owner.restaurants.update', $restaurant) }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            @method('PATCH')
            <div>
                <label class="block text-xs text-slate-500 mb-1">Name</label>
                <input type="text" name="name" value="{{ old('name', $restaurant->name) }}"
                       class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500/30 px-3 py-2">
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Address</label>
                <input type="text" name="address" value="{{ old('address', $restaurant->address) }}"
                       class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500/30 px-3 py-2">
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Latitude</label>
                <input type="number" step="0.0000001" name="latitude" value="{{ old('latitude', $restaurant->latitude) }}"
                       class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500/30 px-3 py-2">
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Longitude</label>
                <input type="number" step="0.0000001" name="longitude" value="{{ old('longitude', $restaurant->longitude) }}"
                       class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500/30 px-3 py-2">
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Commission rate (%)</label>
                <input type="number" step="0.01" min="0" max="50" name="commission_rate" value="{{ old('commission_rate', $restaurant->commission_rate) }}"
                       class="w-32 rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500/30 px-3 py-2">
            </div>
            <div class="md:col-span-2 pt-2">
                <button class="bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium px-4 py-2 rounded-lg">Save changes</button>
            </div>
        </form>
    </section>

    {{-- Menu (categories + items + variants) --}}
    <section class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Menu</h2>
                <p class="text-sm text-slate-500">Click any item to expand and edit name, price, description, or variants.</p>
            </div>
        </div>

        {{-- Add category --}}
        <form method="POST" action="{{ route('owner.categories.store', $restaurant) }}"
              class="bg-white border border-slate-200 rounded-xl p-4 mb-6 flex flex-wrap items-end gap-3">
            @csrf
            <div class="flex-1 min-w-48">
                <label class="block text-xs text-slate-500 mb-1">New category</label>
                <input type="text" name="name" required maxlength="100" placeholder="e.g. Desserts"
                       class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500/30 px-3 py-2">
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Sort order</label>
                <input type="number" name="sort_order" min="0" value="{{ ($restaurant->categories->max('sort_order') ?? 0) + 1 }}"
                       class="w-24 rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500/30 px-3 py-2">
            </div>
            <button class="bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-medium px-4 py-2 rounded-lg shadow-sm">
                Add category
            </button>
        </form>

        @forelse($restaurant->categories as $cat)
            <div class="bg-white border border-slate-200 rounded-xl p-5 mb-5">

                {{-- Category header with inline edit --}}
                <details class="mb-4 pb-4 border-b">
                    <summary class="flex items-start justify-between gap-4 cursor-pointer">
                        <div>
                            <h3 class="font-semibold text-slate-900">{{ $cat->name }}</h3>
                            <p class="text-xs text-slate-500 mt-0.5">sort order {{ $cat->sort_order }} · {{ $cat->menuItems->count() }} items · click to rename</p>
                        </div>
                        <form method="POST" action="{{ route('owner.categories.destroy', $cat) }}"
                              onsubmit="event.stopPropagation(); return confirm('Delete category {{ $cat->name }} and all its items?')">
                            @csrf
                            @method('DELETE')
                            <button class="text-xs text-rose-600 hover:text-rose-700">Delete category</button>
                        </form>
                    </summary>
                    <form method="POST" action="{{ route('owner.categories.update', $cat) }}" class="mt-3 flex flex-wrap items-end gap-2">
                        @csrf
                        @method('PATCH')
                        <div class="flex-1 min-w-48">
                            <label class="block text-xs text-slate-500 mb-1">Name</label>
                            <input type="text" name="name" value="{{ $cat->name }}" required maxlength="100"
                                   class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500/30 px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-500 mb-1">Sort order</label>
                            <input type="number" name="sort_order" value="{{ $cat->sort_order }}" min="0"
                                   class="w-24 rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500/30 px-3 py-2">
                        </div>
                        <button class="bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium px-4 py-2 rounded-lg">Rename</button>
                    </form>
                </details>

                {{-- Items list --}}
                @forelse($cat->menuItems as $item)
                    <details class="py-3 border-b last:border-b-0 {{ $item->is_available ? '' : 'opacity-60' }}">
                        <summary class="flex items-start justify-between gap-3 cursor-pointer">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="font-medium text-slate-900">{{ $item->name }}</p>
                                    @if(! $item->is_available)
                                        <span class="text-xs px-2 py-0.5 rounded bg-slate-200 text-slate-600">Unavailable</span>
                                    @endif
                                </div>
                                @if($item->description)
                                    <p class="text-xs text-slate-500 mt-0.5">{{ $item->description }}</p>
                                @endif
                                <p class="text-sm font-semibold text-emerald-700 mt-1">
                                    {{ number_format($item->base_price / 100, 2) }} EGP
                                    @if($item->variants->isNotEmpty())
                                        <span class="text-xs text-slate-400 ml-2">{{ $item->variants->count() }} variants</span>
                                    @endif
                                </p>
                            </div>
                            <div class="flex items-center gap-3 shrink-0">
                                <form method="POST" action="{{ route('owner.menu-items.toggle', $item) }}" onclick="event.stopPropagation()">
                                    @csrf
                                    <button class="text-xs {{ $item->is_available ? 'text-amber-600 hover:text-amber-700' : 'text-emerald-700 hover:text-emerald-800' }}">
                                        {{ $item->is_available ? 'Hide' : 'Make available' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('owner.menu-items.destroy', $item) }}"
                                      onclick="event.stopPropagation()" onsubmit="return confirm('Delete {{ $item->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-xs text-rose-600 hover:text-rose-700">Delete</button>
                                </form>
                            </div>
                        </summary>

                        {{-- Edit form --}}
                        <div class="mt-4 ml-2 pl-4 border-l-2 border-slate-100 space-y-4">
                            <form method="POST" action="{{ route('owner.menu-items.update', $item) }}" class="grid grid-cols-1 md:grid-cols-6 gap-3">
                                @csrf
                                @method('PATCH')
                                <div class="md:col-span-2">
                                    <label class="block text-xs text-slate-500 mb-1">Name</label>
                                    <input type="text" name="name" value="{{ $item->name }}" required maxlength="255"
                                           class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500/30 px-3 py-2">
                                </div>
                                <div class="md:col-span-3">
                                    <label class="block text-xs text-slate-500 mb-1">Description</label>
                                    <input type="text" name="description" value="{{ $item->description }}" maxlength="1000"
                                           class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500/30 px-3 py-2">
                                </div>
                                <div>
                                    <label class="block text-xs text-slate-500 mb-1">Price (cents)</label>
                                    <input type="number" name="base_price" min="1" max="1000000" value="{{ $item->base_price }}" required
                                           class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500/30 px-3 py-2">
                                </div>
                                <div class="md:col-span-6">
                                    <button class="bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium px-4 py-2 rounded-lg">Save item</button>
                                </div>
                            </form>

                            {{-- Variants --}}
                            <div>
                                <p class="text-xs uppercase tracking-wide text-slate-500 mb-2">Variants ({{ $item->variants->count() }})</p>

                                @forelse($item->variants as $v)
                                    <div class="flex items-center justify-between py-1.5 text-sm border-t first:border-t-0">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium">{{ $v->name }}</span>
                                            <span class="text-xs text-slate-500">+{{ number_format($v->price_modifier / 100, 2) }} EGP</span>
                                            @if($v->is_default)
                                                <span class="text-xs px-2 py-0.5 rounded bg-emerald-100 text-emerald-700">default</span>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-3">
                                            @if(! $v->is_default)
                                                <form method="POST" action="{{ route('owner.variants.default', $v) }}">
                                                    @csrf
                                                    <button class="text-xs text-slate-600 hover:text-slate-900">Make default</button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('owner.variants.destroy', $v) }}"
                                                  onsubmit="return confirm('Delete variant {{ $v->name }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="text-xs text-rose-600 hover:text-rose-700">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-400">No variants. Add sizes/options below.</p>
                                @endforelse

                                <form method="POST" action="{{ route('owner.variants.store', $item) }}" class="mt-3 flex flex-wrap items-end gap-2">
                                    @csrf
                                    <div>
                                        <label class="block text-xs text-slate-500 mb-1">Variant name</label>
                                        <input type="text" name="name" required maxlength="100" placeholder="e.g. Large"
                                               class="rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500/30 px-3 py-2">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-slate-500 mb-1">+ Price (cents)</label>
                                        <input type="number" name="price_modifier" min="0" max="1000000" value="0" required
                                               class="w-32 rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500/30 px-3 py-2">
                                    </div>
                                    <label class="flex items-center gap-1.5 text-xs text-slate-600 pb-2">
                                        <input type="checkbox" name="is_default" value="1" class="rounded">
                                        Make default
                                    </label>
                                    <button class="bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-medium px-3 py-2 rounded-lg shadow-sm">Add variant</button>
                                </form>
                            </div>
                        </div>
                    </details>
                @empty
                    <p class="text-sm text-slate-500 py-2">No items yet — add one below.</p>
                @endforelse

                {{-- Inline add-item form --}}
                <form method="POST" action="{{ route('owner.menu-items.store', $cat) }}" class="mt-4 pt-4 border-t">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
                        <div class="md:col-span-2">
                            <label class="block text-xs text-slate-500 mb-1">Item name</label>
                            <input type="text" name="name" required maxlength="255" placeholder="e.g. Tiramisu"
                                   class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500/30 px-3 py-2">
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-xs text-slate-500 mb-1">Description (optional)</label>
                            <input type="text" name="description" maxlength="1000" placeholder="Italian dessert with mascarpone"
                                   class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500/30 px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-500 mb-1">Price (cents)</label>
                            <input type="number" name="base_price" min="1" max="1000000" required value="5000"
                                   class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500/30 px-3 py-2">
                        </div>
                        <div class="md:col-span-6 flex items-center justify-between">
                            <p class="text-xs text-slate-500">Prices are integer cents: <code>5000</code> = 50.00 EGP. Variants are added per-item after the item exists.</p>
                            <button class="bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-medium px-4 py-2 rounded-lg shadow-sm">
                                Add item
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        @empty
            <div class="bg-white border border-slate-200 rounded-xl p-8 text-center text-slate-500">
                No categories yet. Add one using the form above.
            </div>
        @endforelse
    </section>
</x-layouts.app>
