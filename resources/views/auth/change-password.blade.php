@extends('layouts.app')
@section('title', 'Change Password')

@section('content')
<div class="p-8 max-w-lg">
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-white">Change Password</h1>
        <p class="text-sm text-gray-500 mt-1">Update your account password.</p>
    </div>

    @if(session('success'))
        <div class="mb-4 flex items-center gap-2.5 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm rounded-xl px-4 py-3">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-4">
            @csrf
            @method('PUT')

            <div class="flex flex-col gap-1.5">
                <label for="current_password" class="text-sm font-medium text-gray-300">Current Password</label>
                <input id="current_password" name="current_password" type="password"
                       class="w-full bg-gray-800 border rounded-xl px-4 py-2.5 text-sm text-white outline-none transition-colors
                              focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20
                              @error('current_password') border-red-500 @else border-gray-700 @enderror">
                @error('current_password')
                    <p class="text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="new_password" class="text-sm font-medium text-gray-300">New Password</label>
                <input id="new_password" name="new_password" type="password"
                       class="w-full bg-gray-800 border rounded-xl px-4 py-2.5 text-sm text-white outline-none transition-colors
                              focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20
                              @error('new_password') border-gray-700 @else border-gray-700 @enderror">
                @error('new_password')
                    <p class="text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="new_password_confirmation" class="text-sm font-medium text-gray-300">Confirm New Password</label>
                <input id="new_password_confirmation" name="new_password_confirmation" type="password"
                       class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white outline-none transition-colors
                              focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
            </div>

            <button type="submit"
                    class="w-full bg-emerald-500 hover:bg-emerald-400 text-white font-semibold text-sm py-2.5 px-4 rounded-xl transition-colors mt-1">
                Update Password
            </button>
        </form>
    </div>
</div>
@endsection
