@php
    $navLink = $navLink ?? 'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition';
    $navActive = $navActive ?? 'bg-violet-50 text-violet-700';
    $navIdle = $navIdle ?? 'text-gray-700 hover:bg-gray-100';
@endphp

<a href="{{ route('superadmin.dashboard') }}"
   class="{{ $navLink }} {{ request()->routeIs('superadmin.dashboard') ? $navActive : $navIdle }}">
    Master Dashboard
</a>
@can('platform-full-access')
    <a href="{{ route('superadmin.search') }}"
       class="{{ $navLink }} {{ request()->routeIs('superadmin.search') ? $navActive : $navIdle }}">
        Global Search
    </a>
@endcan
@can('platform-full-access')
    <a href="{{ route('superadmin.affiliates.index') }}"
       class="{{ $navLink }} {{ request()->routeIs('superadmin.affiliates.*') ? $navActive : $navIdle }}">
        Affiliate Performance
    </a>
@endcan
@foreach(\App\Support\SuperAdmin\EntityRegistry::all() as $key => $entity)
    <a href="{{ route('superadmin.entities.index', $key) }}"
       class="{{ $navLink }} {{ request()->is('superadmin/entities/' . $key . '*') ? $navActive : $navIdle }}">
        {{ $entity['label'] }}
    </a>
@endforeach
@can('platform-full-access')
    <a href="{{ route('superadmin.activity') }}"
       class="{{ $navLink }} {{ request()->routeIs('superadmin.activity') ? $navActive : $navIdle }}">
        Activity Log
    </a>
    <a href="{{ route('superadmin.settings') }}"
       class="{{ $navLink }} {{ request()->routeIs('superadmin.settings*') ? $navActive : $navIdle }}">
        Settings &amp; API Keys
    </a>
@endcan
