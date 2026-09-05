@if(!$onboarding['is_complete'])
<div class="mb-8 flex min-h-[50vh] flex-col items-center justify-center rounded-2xl border border-dashed border-gray-200 bg-white px-6 py-16 text-center shadow-sm sm:min-h-[55vh] sm:px-12">
    <h2 class="text-2xl font-semibold tracking-tight text-gray-900">Welcome, {{ $business->name }}</h2>
    <p class="mt-2 max-w-md text-sm text-gray-500">{{ $onboarding['welcome_subtitle'] }}</p>

    @if($onboarding['is_hospitality'])
        <div class="mt-6 flex flex-wrap justify-center gap-2">
            <span class="rounded-full bg-orange-50 px-3 py-1 text-xs font-semibold text-orange-800">Restaurant Mode</span>
            <span class="rounded-full bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-800">Kitchen workflow enabled</span>
        </div>
    @endif

    <div class="mt-10 flex w-full max-w-lg flex-col items-stretch gap-4 sm:flex-row sm:justify-center">
        @if($onboarding['needs_products'])
            <a href="{{ tenant_route($onboarding['catalog_route']) }}"
               class="inline-flex flex-1 items-center justify-center rounded-xl bg-indigo-600 px-8 py-4 text-base font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                {{ $onboarding['catalog_cta'] }}
            </a>
        @endif

        @if($onboarding['needs_employees'])
            <a href="{{ tenant_route('tenant.staff.create') }}"
               class="inline-flex flex-1 items-center justify-center rounded-xl border-2 border-indigo-600 bg-white px-8 py-4 text-base font-semibold text-indigo-700 transition hover:bg-indigo-50">
                {{ $onboarding['staff_cta'] }}
            </a>
        @endif
    </div>

    @if($onboarding['needs_employees'])
        <p class="mt-4 max-w-md text-xs text-gray-500">{{ $onboarding['staff_hint'] }}</p>
        <form method="POST" action="{{ tenant_route('tenant.onboarding.sole-proprietor') }}" class="mt-3">
            @csrf
            <button type="submit" class="text-xs font-medium text-gray-500 underline hover:text-gray-700">I'm running this alone — I'll act as cashier</button>
        </form>
    @endif
</div>
@endif
