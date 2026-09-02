@if(auth()->user()->usesCashierExperience())
    <div class="mb-4">
        <a href="{{ tenant_route('tenant.operations.index') }}"
           class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-700 hover:text-emerald-900">
            <span aria-hidden="true">←</span> Back to Operations
        </a>
    </div>
@endif
