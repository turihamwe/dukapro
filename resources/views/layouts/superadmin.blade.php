@extends('layouts.base')

@section('body')
<div class="flex min-h-full">
    <aside class="relative hidden w-64 shrink-0 border-r border-gray-200 bg-white lg:block">
        <div class="flex h-16 items-center gap-3 border-b border-gray-200 px-6">
            @include('layouts.partials.dukapro-sidebar-brand', [
                'subtitle' => 'Platform · ' . (auth()->user()->isSubAdmin() ? 'SubAdmin' : 'SuperAdmin'),
            ])
        </div>
        <nav class="space-y-1 p-4">
            <a href="{{ route('superadmin.dashboard') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('superadmin.dashboard') ? 'bg-violet-50 text-violet-700' : 'text-gray-700 hover:bg-gray-100' }}">
                Master Dashboard
            </a>
            <a href="{{ route('superadmin.search') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('superadmin.search') ? 'bg-violet-50 text-violet-700' : 'text-gray-700 hover:bg-gray-100' }}">
                Global Search
            </a>
            @foreach(\App\Support\SuperAdmin\EntityRegistry::all() as $key => $entity)
                <a href="{{ route('superadmin.entities.index', $key) }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->is('superadmin/entities/' . $key . '*') ? 'bg-violet-50 text-violet-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    {{ $entity['label'] }}
                </a>
            @endforeach
            <a href="{{ route('superadmin.activity') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('superadmin.activity') ? 'bg-violet-50 text-violet-700' : 'text-gray-700 hover:bg-gray-100' }}">
                Activity Log
            </a>
            @can('platform-full-access')
                <a href="{{ route('superadmin.settings') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('superadmin.settings*') ? 'bg-violet-50 text-violet-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    Settings &amp; API Keys
                </a>
            @endcan
        </nav>
        <div class="absolute bottom-0 w-64 border-t border-gray-200 bg-white p-4">
            <p class="truncate text-xs text-gray-500">{{ auth()->user()->email }}</p>
            <a href="{{ route('logout.get') }}" class="mt-2 inline-block text-xs text-gray-500 hover:text-gray-700">Sign out</a>
        </div>
    </aside>

    <div class="flex flex-1 flex-col">
        <header class="sticky top-0 z-30 flex min-h-16 items-center justify-between border-b border-gray-200 bg-white px-4 lg:hidden">
            <x-dukapro-logo size="header" />
            <a href="{{ route('logout.get') }}" class="text-sm text-gray-500">Sign out</a>
        </header>

        <main class="flex-1 overflow-auto p-4 sm:p-8">
            @include('layouts.partials.flash')
            @yield('content')
        </main>
    </div>
</div>
@endsection
