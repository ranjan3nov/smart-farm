@extends('layouts.guest')
@section('title', 'Sign In')

@section('content')
<div class="w-full max-w-sm">
    {{-- Logo --}}
    <div class="flex flex-col items-center gap-3 mb-8">
        <div class="w-14 h-14 rounded-2xl bg-emerald-500 flex items-center justify-center shadow-lg shadow-emerald-500/20">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3C7 3 3 7.5 3 12c0 4.97 4.03 9 9 9s9-4.03 9-9c0-1.5-.37-2.9-1.02-4.14M12 3v9m0 0 3-3m-3 3-3-3"/>
            </svg>
        </div>
        <div class="text-center">
            <h1 class="text-xl font-semibold text-white">Smart Farm</h1>
            <p class="text-sm text-gray-500 mt-0.5">Sign in to your dashboard</p>
        </div>
    </div>

    {{-- Card --}}
    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 shadow-xl">
        <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-4">
            @csrf

            {{-- Email --}}
            <div class="flex flex-col gap-1.5">
                <label for="email" class="text-sm font-medium text-gray-300">Email</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    autocomplete="email"
                    autofocus
                    class="w-full bg-gray-800 border rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-500 outline-none transition-colors
                           focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20
                           @error('email') border-red-500 @else border-gray-700 @enderror"
                    placeholder="admin@example.com"
                >
                @error('email')
                    <p class="text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div class="flex flex-col gap-1.5">
                <label for="password" class="text-sm font-medium text-gray-300">Password</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    class="w-full bg-gray-800 border rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-500 outline-none transition-colors
                           focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20
                           @error('password') border-red-500 @else border-gray-700 @enderror"
                    placeholder="••••••••"
                >
                @error('password')
                    <p class="text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Remember me --}}
            <label class="flex items-center gap-2.5 cursor-pointer">
                <input type="checkbox" name="remember" class="w-4 h-4 rounded accent-emerald-500">
                <span class="text-sm text-gray-400">Remember me</span>
            </label>

            <button type="submit"
                    class="w-full bg-emerald-500 hover:bg-emerald-400 text-white font-semibold text-sm py-2.5 px-4 rounded-xl transition-colors mt-1 shadow-lg shadow-emerald-500/20">
                Sign In
            </button>
        </form>
    </div>
</div>
@endsection
