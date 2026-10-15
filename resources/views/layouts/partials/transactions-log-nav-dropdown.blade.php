@php
    $transactionsOpen = request()->routeIs('tenant.transactions-log.*');
    $transactionsType = request('type', 'all');
    $isModern = ($theme ?? 'plain') === 'modern';
    $navLink = $navLink ?? 'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition';
    $subActive = $isModern ? 'font-medium text-emerald-400 bg-white/10' : 'font-medium text-indigo-700 bg-indigo-50';
    $subIdle = $isModern ? 'text-slate-300 hover:bg-white/5 hover:text-white' : 'text-gray-600 hover:bg-gray-100';
    $containerClass = $isModern ? 'modern-transactions-nav' : 'transactions-nav';
    $btnClass = $isModern
        ? 'modern-nav-link flex w-full items-center justify-between gap-3 rounded-lg px-3 py-3 text-sm font-medium transition border-l-[3px]'
        : $navLink . ' w-full justify-between';
    $btnState = $transactionsOpen ? $navActive : $navIdle;
    $logLink = function (string $type) {
        return tenant_route('tenant.transactions-log.index', ['type' => $type]);
    };
    $logActive = function (string $type) use ($transactionsOpen, $transactionsType, $subActive, $subIdle) {
        $on = $transactionsOpen && $transactionsType === $type;

        return 'block rounded-lg px-3 py-2 text-sm '.($on ? $subActive : $subIdle);
    };
@endphp
<div class="{{ $containerClass }}">
    <button type="button"
            data-transactions-toggle
            aria-expanded="{{ $transactionsOpen ? 'true' : 'false' }}"
            class="{{ $btnClass }} {{ $btnState }}">
        <span class="flex items-center gap-3">
            @if($isModern)
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            @else
                <span>📒</span>
            @endif
            Transactions
        </span>
        <svg data-transactions-chevron class="h-4 w-4 shrink-0 transition {{ $transactionsOpen ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div data-transactions-menu class="ml-4 mt-1 space-y-1 {{ $transactionsOpen ? '' : 'hidden' }}">
        @can('access-supplier-credit')
            <a href="{{ $logLink('supplier') }}" class="{{ $logActive('supplier') }}">Suppliers</a>
        @endcan
        <a href="{{ $logLink('customer') }}" class="{{ $logActive('customer') }}">Customers</a>
        <a href="{{ $logLink('product') }}" class="{{ $logActive('product') }}">Products</a>
        @can('manage-wallets')
            <a href="{{ $logLink('wallet') }}" class="{{ $logActive('wallet') }}">Wallets</a>
        @endcan
        <a href="{{ $logLink('all') }}" class="{{ $logActive('all') }}">All</a>
    </div>
</div>
