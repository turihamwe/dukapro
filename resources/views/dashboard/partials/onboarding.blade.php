@if(!$onboarding['is_complete'])
<div class="mb-8 flex min-h-[50vh] flex-col items-center justify-center rounded-2xl border border-dashed border-gray-200 bg-white px-6 py-16 text-center shadow-sm sm:min-h-[55vh] sm:px-12">
    <h2 class="text-2xl font-semibold tracking-tight text-gray-900">Welcome, {{ $business->name }}</h2>
    <p class="mt-2 max-w-md text-sm text-gray-500">Get started by adding your inventory and team. These steps stay visible until both are complete.</p>

    <div class="mt-10 flex w-full max-w-lg flex-col items-stretch gap-4 sm:flex-row sm:justify-center">
        @if($onboarding['needs_products'])
            <a href="{{ tenant_route('tenant.inventory.create') }}"
               class="inline-flex flex-1 items-center justify-center rounded-xl bg-indigo-600 px-8 py-4 text-base font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                Add products
            </a>
        @endif

        @if($onboarding['needs_employees'])
            <a href="{{ tenant_route('tenant.staff.create') }}"
               class="inline-flex flex-1 items-center justify-center rounded-xl border-2 border-indigo-600 bg-white px-8 py-4 text-base font-semibold text-indigo-700 transition hover:bg-indigo-50">
                Add employees
            </a>
        @endif
    </div>
</div>
@endif
