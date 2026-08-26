<div class="mb-8 rounded-2xl border border-gray-200 bg-white p-8 sm:p-12 shadow-sm">
    <div class="mx-auto max-w-2xl text-center">
        <h2 class="text-2xl font-semibold tracking-tight text-gray-900">Welcome to DukaPro store, {{ $business->name }}</h2>
        <p class="mt-3 text-sm text-gray-600">Complete these setup steps to unlock your executive dashboard.</p>

        <div class="mt-10 flex flex-col items-stretch justify-center gap-4 sm:flex-row sm:items-center sm:justify-center">
            @if($onboarding['needs_products'])
                <a href="{{ tenant_route('tenant.inventory.create') }}"
                   class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-8 py-4 text-base font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                    Add products
                </a>
            @endif

            @if($onboarding['needs_employees'])
                <a href="{{ tenant_route('tenant.staff.create') }}"
                   class="inline-flex items-center justify-center rounded-xl border-2 border-indigo-600 bg-white px-8 py-4 text-base font-semibold text-indigo-700 transition hover:bg-indigo-50">
                    Add staff
                </a>
            @endif
        </div>

        @if($onboarding['has_products'])
            <div class="mt-8 border-t border-gray-100 pt-6">
                <a href="{{ tenant_route('tenant.inventory.index') }}"
                   class="text-sm font-medium text-indigo-600 hover:text-indigo-700">
                    All products →
                </a>
            </div>
        @endif
    </div>
</div>
