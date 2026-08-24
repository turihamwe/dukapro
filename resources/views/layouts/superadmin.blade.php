@extends('layouts.base')

@section('body')
<div class="flex min-h-full">
    <aside class="relative hidden w-64 shrink-0 border-r border-gray-200 bg-white lg:block">
        <div class="flex h-16 items-center gap-3 border-b border-gray-200 px-6">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-violet-600 text-sm font-bold text-white">SA</div>
            <div>
                <p class="text-sm font-semibold">SuperAdmin</p>
                <p class="text-xs text-gray-500">DukaPro Platform</p>
            </div>
        </div>
        <nav class="space-y-1 p-4">
            <a href="{{ route('superadmin.dashboard') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('superadmin.dashboard') ? 'bg-violet-50 text-violet-700' : 'text-gray-700 hover:bg-gray-100' }}">
                <span>📊</span> Tenant Overview
            </a>
            <a href="{{ route('superadmin.activity') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('superadmin.activity') ? 'bg-violet-50 text-violet-700' : 'text-gray-700 hover:bg-gray-100' }}">
                <span>📋</span> Activity Log
            </a>
            <a href="{{ route('superadmin.settings') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('superadmin.settings') ? 'bg-violet-50 text-violet-700' : 'text-gray-700 hover:bg-gray-100' }}">
                <span>⚙️</span> Settings
            </a>
        </nav>
        <div class="absolute bottom-0 w-64 border-t border-gray-200 bg-white p-4">
            <p class="truncate text-xs text-gray-500">{{ auth()->user()->email }}</p>
            <a href="{{ route('logout.get') }}" class="mt-2 inline-block text-xs text-gray-500 hover:text-gray-700">Sign out</a>
        </div>
    </aside>

    <div class="flex flex-1 flex-col">
        <header class="flex h-16 items-center justify-between border-b border-gray-200 bg-white px-4 lg:hidden">
            <p class="font-semibold">SuperAdmin</p>
            <a href="{{ route('logout.get') }}" class="text-sm text-gray-500">Sign out</a>
        </header>

        <main class="flex-1 overflow-auto p-4 sm:p-8">
            @include('layouts.partials.flash')
            @yield('content')
        </main>
    </div>
</div>
@endsection
