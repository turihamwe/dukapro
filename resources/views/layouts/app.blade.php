<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', platform_brand('name'))</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
                    },
                },
            },
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        [x-cloak] { display: none !important; }
    </style>
    @stack('styles')
</head>
<body class="h-full bg-gray-50 text-gray-900 antialiased">

    @auth
    <header class="sticky top-0 z-40 border-b border-gray-200/80 bg-white/80 backdrop-blur-lg">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <div class="flex min-w-0 items-center gap-3">
                <x-dukapro-logo size="header" />
                @auth
                <div class="min-w-0 hidden sm:block">
                    <p class="truncate text-sm font-semibold leading-tight">{{ auth()->user()->business->name ?? platform_brand('name') }}</p>
                    <p class="text-xs text-gray-500">{{ ucfirst(auth()->user()->role) }}</p>
                </div>
                @endauth
            </div>
            <a href="{{ route('logout.get') }}" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                Sign out
            </a>
        </div>
    </header>
    @endauth

    <main class="@auth @unless(request()->routeIs('tenant.*')) pb-24 @endunless @endauth min-h-full">
        <div class="@yield('container_class', 'max-w-7xl') mx-auto px-4 py-8 sm:px-6 lg:px-8">
            @if(session('success'))
                <x-alert type="success" class="mb-6">{{ session('success') }}</x-alert>
            @endif
            @if(session('warning'))
                <x-alert type="warning" class="mb-6">{{ session('warning') }}</x-alert>
            @endif
            @if($errors->any())
                <x-alert type="error" class="mb-6">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-alert>
            @endif

            @yield('content')
        </div>
    </main>

    @auth
    @if(auth()->user()->business && request()->routeIs('tenant.*') && auth()->user()->isCashier())
    <nav class="fixed bottom-0 left-0 right-0 z-50 border-t border-gray-200 bg-white/95 backdrop-blur-lg">
        <div class="mx-auto flex max-w-lg justify-around px-2 py-2">
            @auth
                @if(auth()->user()->business)
                    @php
                        try {
                            $navItems = [];
                            $user = auth()->user();
                            if ($user->can('view-dashboard')) {
                                $navItems[] = ['route' => 'tenant.dashboard', 'match' => 'tenant.dashboard', 'icon' => '🏠', 'label' => 'Home'];
                            }
                            if ($user->can('access-pos')) {
                                $navItems[] = ['route' => 'tenant.pos.index', 'match' => 'tenant.pos.*', 'icon' => '🛒', 'label' => 'POS'];
                            }
                            if ($user->can('manage-inventory')) {
                                $navItems[] = ['route' => 'tenant.inventory.index', 'match' => 'tenant.inventory.*', 'icon' => '📦', 'label' => 'Stock'];
                            }
                            if ($user->can('log-damages')) {
                                $navItems[] = ['route' => 'tenant.damages.index', 'match' => 'tenant.damages.*', 'icon' => '📉', 'label' => 'Damage'];
                            }
                            if ($user->can('submit-reconciliation')) {
                                $navItems[] = ['route' => 'tenant.reconciliation.create', 'match' => 'tenant.reconciliation.*', 'icon' => '💰', 'label' => 'EOD'];
                            }
                            if ($user->can('manage-debts')) {
                                $navItems[] = ['route' => 'tenant.contacts.index', 'match' => 'tenant.contacts.*', 'icon' => '📒', 'label' => 'Contacts'];
                            }
                        } catch (\Throwable $e) {
                            $navItems = [];
                        }
                    @endphp
                @else
                    @php $navItems = []; @endphp
                @endif
            @endauth
            @foreach($navItems ?? [] as $item)
                <a href="{{ tenant_route($item['route']) }}"
                   class="flex flex-col items-center rounded-lg px-3 py-1.5 text-[10px] font-medium transition {{ request()->routeIs($item['match']) ? 'text-indigo-600' : 'text-gray-500 hover:text-gray-700' }}">
                    <span class="mb-0.5 text-base leading-none">{{ $item['icon'] }}</span>
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    </nav>
    @endif
    @endauth

    @stack('scripts')
    <script>
    (function () {
        document.querySelectorAll('.password-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = btn.parentElement.querySelector('input');
                if (!input) return;
                var open = btn.querySelector('.eye-open');
                var closed = btn.querySelector('.eye-closed');
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                open.classList.toggle('hidden', show);
                closed.classList.toggle('hidden', !show);
            });
        });
    })();
    </script>
</body>
</html>
