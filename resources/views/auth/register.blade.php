<x-layouts.app title="Create account">
    <div class="min-h-[70vh] flex items-center">
        <div class="max-w-xl mx-auto w-full bg-white rounded-2xl shadow-sm border border-slate-200 p-8 md:p-10">
            <h1 class="text-2xl font-bold text-slate-900">Create your account</h1>
            <p class="text-sm text-slate-500 mt-1 mb-6">
                Sign up as a <strong>customer</strong> (to order food) or a <strong>rider</strong> (to deliver).
                Restaurant owners are added by an admin.
            </p>

            <form method="POST" action="{{ route('register') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">I want to</label>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach(['customer' => 'Order food', 'rider' => 'Deliver food'] as $role => $label)
                            <label class="cursor-pointer">
                                <input type="radio" name="role" value="{{ $role }}" {{ old('role', 'customer') === $role ? 'checked' : '' }} class="peer sr-only" required>
                                <div class="border-2 border-slate-200 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 rounded-lg p-4 text-center transition">
                                    <p class="font-medium text-slate-900">{{ $label }}</p>
                                    <p class="text-xs text-slate-500 mt-1">{{ $role === 'customer' ? 'Place orders, browse restaurants' : 'Receive dispatches, complete deliveries' }}</p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Full name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required maxlength="255"
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Phone <span class="text-slate-400 font-normal">(optional)</span></label>
                    <input type="text" name="phone" value="{{ old('phone') }}" maxlength="30"
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
                        <input type="password" name="password" required
                               class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Confirm password</label>
                        <input type="password" name="password_confirmation" required
                               class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30">
                    </div>
                </div>

                <button class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-medium py-2.5 rounded-lg transition shadow-sm">
                    Create account
                </button>

                <p class="text-center text-sm text-slate-500">
                    Already have an account? <a href="{{ route('login') }}" class="text-emerald-700 hover:underline font-medium">Sign in</a>
                </p>
            </form>
        </div>
    </div>
</x-layouts.app>
