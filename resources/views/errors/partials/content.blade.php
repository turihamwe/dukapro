@php
    $badgeClass = trim($__env->yieldContent('badge_class')) ?: 'inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800';
@endphp

<div class="text-center">
    <p class="{{ $badgeClass }}">@yield('badge')</p>
    <h1 class="mt-4 text-xl font-bold tracking-tight text-gray-900 sm:text-2xl">@yield('heading')</h1>
    <p class="mt-3 text-sm leading-relaxed text-gray-600 sm:text-base">@yield('message')</p>
    @hasSection('hint')
        <p class="mt-2 text-xs text-gray-500">@yield('hint')</p>
    @endif

    <div class="mt-6 flex flex-col gap-2.5 sm:flex-row sm:justify-center">
        @if(error_page_can_go_back())
            <a href="{{ error_page_previous_url() }}"
               class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-500">
                Go back
            </a>
            <a href="{{ error_page_home_url() }}"
               class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                {{ error_page_home_label() }}
            </a>
        @else
            <a href="{{ error_page_home_url() }}"
               class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-500">
                {{ error_page_home_label() }}
            </a>
        @endif
    </div>

    <a href="{{ error_page_support_url() }}" target="_blank" rel="noopener noreferrer"
       class="mt-4 inline-block text-xs font-medium text-gray-500 hover:text-emerald-600 hover:underline">
        Help on WhatsApp
    </a>
</div>
