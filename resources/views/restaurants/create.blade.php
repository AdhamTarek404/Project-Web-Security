<x-layouts.app title="New restaurant">
    <a href="{{ route('dashboard') }}" class="text-sm text-slate-500 hover:text-slate-800 inline-flex items-center gap-1">
        <span>←</span> Back to dashboard
    </a>

    <div class="max-w-2xl mt-4">
        <h1 class="text-3xl font-bold text-slate-900">New restaurant</h1>
        <p class="text-slate-500 mt-1">Fill in the basics. You can add categories and menu items right after.</p>

        <form method="POST" action="{{ route('owner.restaurants.store') }}"
              class="mt-6 bg-white border border-slate-200 rounded-xl p-6 space-y-5 shadow-sm">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required maxlength="255"
                       placeholder="e.g. Sara's Kitchen"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Address</label>
                <input type="text" name="address" value="{{ old('address') }}" required maxlength="255"
                       placeholder="12 Tahrir Square, Cairo"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Latitude</label>
                    <input type="number" step="0.0000001" name="latitude" value="{{ old('latitude', '30.0444') }}" required
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Longitude</label>
                    <input type="number" step="0.0000001" name="longitude" value="{{ old('longitude', '31.2357') }}" required
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30">
                </div>
            </div>
            <p class="text-xs text-slate-500 -mt-3">
                Tip: use <a href="https://www.openstreetmap.org/" target="_blank" rel="noopener" class="text-emerald-700 underline">openstreetmap.org</a> to grab coordinates. Defaults point to downtown Cairo.
            </p>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Platform commission (%)</label>
                <input type="number" step="0.01" min="0" max="50" name="commission_rate" value="{{ old('commission_rate', '15.00') }}"
                       class="w-32 rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30">
                <p class="text-xs text-slate-500 mt-1">What the platform takes from every order. Restaurant payout = subtotal − this %.</p>
            </div>

            <div class="pt-4 border-t flex items-center justify-between">
                <a href="{{ route('dashboard') }}" class="text-sm text-slate-500 hover:text-slate-800">Cancel</a>
                <button class="bg-emerald-600 hover:bg-emerald-500 text-white font-medium px-5 py-2.5 rounded-lg shadow-sm">
                    Create restaurant
                </button>
            </div>
        </form>
    </div>
</x-layouts.app>
