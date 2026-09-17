@php
    use App\Support\VariablePricingMode;

    $enabled = (bool) $business->variable_pricing_enabled;
@endphp

@if(VariablePricingMode::platformEnabled())
<div class="mb-6 rounded-xl border border-amber-200 bg-amber-50/60 p-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-gray-900">Variable pricing mode</p>
            <p class="mt-1 text-xs text-gray-600">
                Allow cashiers at this business to edit item prices during POS checkout (markets, furniture, hardware, etc.).
            </p>
        </div>
        @if($enabled)
            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-900">Enabled</span>
        @else
            <span class="rounded-full bg-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-700">Disabled</span>
        @endif
    </div>

    @can('platform-full-access')
        <form method="POST" action="{{ route('superadmin.businesses.variable-pricing.update', $business->id) }}" class="mt-4">
            @csrf
            <input type="hidden" name="variable_pricing_enabled" value="0">
            <label class="flex items-start gap-3 rounded-lg border border-amber-200 bg-white p-4">
                <input type="checkbox" name="variable_pricing_enabled" value="1"
                       {{ $enabled ? 'checked' : '' }}
                       class="mt-0.5 rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                <span>
                    <span class="block text-sm font-medium text-gray-900">Enable variable pricing for this business</span>
                    <span class="mt-0.5 block text-xs text-gray-500">POS checkout will show editable unit prices on each cart line. The business owner can also change this from their Business Profile.</span>
                </span>
            </label>
            <button type="submit" class="mt-3 rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">
                Save variable pricing
            </button>
        </form>
    @else
        <p class="mt-4 text-xs text-gray-500">Full superadmin access is required to change variable pricing.</p>
    @endcan
</div>
@endif
