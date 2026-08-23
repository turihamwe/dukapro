<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SuperAdmin') — DukaPro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: Inter, system-ui, sans-serif; }</style>
    @stack('styles')
</head>
<body class="h-full bg-slate-950 text-slate-100 antialiased">
<div class="flex min-h-full">
    <aside class="relative hidden w-64 shrink-0 border-r border-slate-800 bg-slate-900 lg:block">
        <div class="flex h-16 items-center gap-3 border-b border-slate-800 px-6">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-violet-600 text-sm font-bold text-white">SA</div>
            <div>
                <p class="text-sm font-semibold">SuperAdmin</p>
                <p class="text-xs text-slate-400">DukaPro Platform</p>
            </div>
        </div>
        <nav class="space-y-1 p-4">
            <a href="{{ route('superadmin.dashboard') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('superadmin.dashboard') ? 'bg-violet-600/20 text-violet-300' : 'text-slate-300 hover:bg-slate-800' }}">
                <span>📊</span> Tenant Overview
            </a>
            <a href="{{ route('superadmin.activity') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('superadmin.activity') ? 'bg-violet-600/20 text-violet-300' : 'text-slate-300 hover:bg-slate-800' }}">
                <span>📋</span> Activity Log
            </a>
            <a href="{{ route('superadmin.settings') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('superadmin.settings') ? 'bg-violet-600/20 text-violet-300' : 'text-slate-300 hover:bg-slate-800' }}">
                <span>⚙️</span> Settings
            </a>
        </nav>
        <div class="absolute bottom-0 w-64 border-t border-slate-800 p-4">
            <p class="truncate text-xs text-slate-400">{{ auth()->user()->email }}</p>
            <a href="{{ route('logout.get') }}" class="mt-2 inline-block text-xs text-slate-500 hover:text-slate-300">Sign out</a>
        </div>
    </aside>

    <div class="flex flex-1 flex-col">
        <header class="flex h-16 items-center justify-between border-b border-slate-800 bg-slate-900/80 px-4 backdrop-blur lg:hidden">
            <p class="font-semibold">SuperAdmin</p>
            <a href="{{ route('logout.get') }}" class="text-sm text-slate-400">Sign out</a>
        </header>

        <main class="flex-1 overflow-auto p-4 sm:p-8">
            @if(session('success'))
                <div class="mb-6 rounded-lg border border-emerald-800 bg-emerald-950 px-4 py-3 text-sm text-emerald-300">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="mb-6 rounded-lg border border-red-800 bg-red-950 px-4 py-3 text-sm text-red-300">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
