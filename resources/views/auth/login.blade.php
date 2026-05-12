<x-layouts.app title="Sign in">
    <div class="min-h-[70vh] flex items-center">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 w-full">

            {{-- Form --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 md:p-10">
                <h1 class="text-2xl font-bold text-slate-900">Welcome back</h1>
                <p class="text-sm text-slate-500 mt-1 mb-8">Use a demo account to sign in.</p>

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus
                               placeholder="you@example.com"
                               class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
                        <input type="password" name="password" required
                               placeholder="••••••••"
                               class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30" />
                    </div>

                    <button class="w-full bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white font-medium py-2.5 rounded-lg transition shadow-sm">
                        Sign in
                    </button>
                </form>
            </div>

            {{-- Demo accounts --}}
            <div class="bg-gradient-to-br from-slate-900 to-slate-700 text-white rounded-2xl p-8 md:p-10 shadow-sm">
                <h2 class="text-lg font-semibold">Demo accounts</h2>
                <p class="text-sm text-slate-300 mt-1 mb-6">All passwords are <code class="bg-white/10 px-1.5 py-0.5 rounded font-mono">password</code>.</p>

                <ul class="space-y-3 text-sm">
                    @php
                        $accounts = [
                            ['admin@demo.test', 'Admin', 'Live control tower with rider map'],
                            ['owner@demo.test', 'Restaurant owner', 'Confirm orders, start preparing'],
                            ['rider1@demo.test', 'Rider (nearest)', 'Pick up + deliver assigned orders'],
                            ['customer1@demo.test', 'Customer', 'Browse menus and place orders'],
                        ];
                    @endphp
                    @foreach($accounts as [$email, $role, $blurb])
                        <li class="border border-white/10 rounded-lg p-3.5">
                            <div class="flex items-center gap-2">
                                <span class="text-xs uppercase tracking-wider text-emerald-300">{{ $role }}</span>
                            </div>
                            <code class="block text-sm font-mono mt-1 select-all">{{ $email }}</code>
                            <p class="text-xs text-slate-300 mt-1">{{ $blurb }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</x-layouts.app>
