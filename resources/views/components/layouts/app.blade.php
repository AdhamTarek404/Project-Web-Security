<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'FoodDelivery' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
    @livewireStyles
</head>
<body class="h-full font-sans antialiased text-slate-800">

<nav class="bg-slate-900 text-slate-50">
    <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
        <a href="{{ route('home') }}" class="font-semibold tracking-tight">
            FoodDelivery
        </a>
        <div class="flex items-center gap-4 text-sm">
            <a href="{{ route('home') }}" class="hover:text-white/80">Restaurants</a>

            @auth
                <a href="{{ route('dashboard') }}" class="hover:text-white/80">Dashboard</a>

                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.orders') }}" class="hover:text-white/80">Orders</a>
                    <a href="{{ route('admin.users') }}" class="hover:text-white/80">Users</a>
                    <a href="{{ route('admin.restaurants') }}" class="hover:text-white/80">Restaurants</a>
                    <a href="{{ route('admin.riders') }}" class="hover:text-white/80">Riders</a>
                    <a href="{{ route('admin.control-tower') }}" class="hover:text-white/80">Map</a>
                    <a href="{{ route('admin.surge') }}" class="hover:text-white/80">Surge</a>
                @endif

                <span class="text-slate-300">
                    {{ auth()->user()->name }}
                    <span class="text-xs px-2 py-0.5 rounded bg-slate-700 ml-1">{{ auth()->user()->role }}</span>
                </span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="bg-rose-600 hover:bg-rose-500 text-white px-3 py-1 rounded text-sm">Logout</button>
                </form>
            @else
                <a href="{{ route('register') }}" class="text-slate-200 hover:text-white px-3 py-1.5">Sign up</a>
                <a href="{{ route('login') }}" class="bg-emerald-500 hover:bg-emerald-400 text-white px-3 py-1.5 rounded">Login</a>
            @endauth
        </div>
    </div>
</nav>

@if(session('status'))
    <div class="max-w-6xl mx-auto mt-4 px-4">
        <div class="bg-emerald-100 border border-emerald-300 text-emerald-800 px-4 py-2 rounded text-sm">
            {{ session('status') }}
        </div>
    </div>
@endif

@if($errors->any())
    <div class="max-w-6xl mx-auto mt-4 px-4">
        <div class="bg-rose-100 border border-rose-300 text-rose-800 px-4 py-2 rounded text-sm">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    </div>
@endif

<main class="max-w-6xl mx-auto px-4 py-6">
    {{ $slot ?? '' }}
    @yield('content')
</main>

@livewireScripts
@stack('scripts')
</body>
</html>
