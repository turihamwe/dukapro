@extends('layouts.base')

@section('body')
<div class="flex min-h-full">
    {{-- Desktop sidebar --}}
    <aside class="hidden w-64 shrink-0 border-r border-gray-200 bg-white lg:flex lg:sticky lg:top-0 lg:max-h-screen lg:flex-col">
        <div class="flex h-16 shrink-0 items-center gap-3 border-b border-gray-200 px-6">
            @include('layouts.partials.dukapro-sidebar-brand', [
                'subtitle' => 'Platform',
            ])
        </div>
        <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto overscroll-contain p-4">
            @include('layouts.partials.superadmin-nav-links')
        </nav>
        <div class="shrink-0 border-t border-gray-200 p-4">
            <p class="truncate text-xs text-gray-500">{{ auth()->user()->email }}</p>
            <a href="{{ route('logout.get') }}" class="mt-2 inline-block text-xs text-gray-500 hover:text-gray-700">Sign out</a>
        </div>
    </aside>

    {{-- Mobile drawer --}}
    <div id="superadmin-nav-backdrop" class="fixed inset-0 z-40 hidden bg-gray-900/50 lg:hidden" aria-hidden="true"></div>
    <aside id="superadmin-nav-drawer" class="fixed inset-y-0 left-0 z-50 flex w-72 max-w-[85vw] -translate-x-full transform flex-col border-r border-gray-200 bg-white transition-transform duration-200 ease-out lg:hidden">
        <div class="flex h-14 shrink-0 items-center justify-between gap-3 border-b border-gray-200 px-4">
            <div class="min-w-0 flex-1">
                <x-dukapro-logo size="header" />
                <p class="truncate text-xs text-gray-500">Platform admin</p>
            </div>
            <button type="button" id="superadmin-nav-close" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100" aria-label="Close menu">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto overscroll-contain p-4">
            @include('layouts.partials.superadmin-nav-links')
        </nav>
        <div class="shrink-0 border-t border-gray-200 p-4">
            <p class="mb-2 truncate text-xs text-gray-500">{{ auth()->user()->email }}</p>
            <a href="{{ route('logout.get') }}" class="flex w-full items-center justify-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700">Sign out</a>
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-30 flex min-h-16 items-center justify-between border-b border-gray-200 bg-white px-4 lg:hidden">
            <div class="flex min-w-0 items-center gap-3">
                <button type="button" id="superadmin-nav-open" class="rounded-lg p-2 text-gray-600 hover:bg-gray-100" aria-label="Open menu">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <x-dukapro-logo size="header" />
            </div>
            <a href="{{ route('logout.get') }}" class="text-sm text-gray-500">Sign out</a>
        </header>

        <main class="flex-1 overflow-auto p-4 sm:p-8">
            @include('layouts.partials.flash')
            @yield('content')
        </main>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var drawer = document.getElementById('superadmin-nav-drawer');
    var backdrop = document.getElementById('superadmin-nav-backdrop');
    var openBtn = document.getElementById('superadmin-nav-open');
    var closeBtn = document.getElementById('superadmin-nav-close');

    function openNav() {
        if (!drawer || !backdrop) return;
        backdrop.classList.remove('hidden');
        drawer.classList.remove('-translate-x-full');
        document.body.classList.add('overflow-hidden');
    }

    function closeNav() {
        if (!drawer || !backdrop) return;
        backdrop.classList.add('hidden');
        drawer.classList.add('-translate-x-full');
        document.body.classList.remove('overflow-hidden');
    }

    openBtn?.addEventListener('click', openNav);
    closeBtn?.addEventListener('click', closeNav);
    backdrop?.addEventListener('click', closeNav);
    drawer?.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', closeNav);
    });
})();
</script>
@endpush
@endsection
