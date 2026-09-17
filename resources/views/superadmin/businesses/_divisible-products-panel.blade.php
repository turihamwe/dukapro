@php
    use App\Support\DivisibleProductsMode;

    $enabled = (bool) $business->divisible_products_enabled;
@endphp

@if(DivisibleProductsMode::platformEnabled())
<div class="mb-6 rounded-xl border border-violet-200 bg-violet-50/60 p-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-gray-900">Divisible products</p>
            <p class="mt-1 text-xs text-gray-600">
                Allow cashiers at this business to sell fractional quantities at POS (e.g. 0.5 meters, 1.25 kg).
            </p>
        </div>
        @if($enabled)
            <span class="rounded-full bg-violet-100 px-2.5 py-1 text-xs font-semibold text-violet-900">Enabled</span>
        @else
            <span class="rounded-full bg-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-700">Disabled</span>
        @endif
    </div>

    @can('platform-full-access')
        <form method="POST" action="{{ route('superadmin.businesses.divisible-products.update', $business->id) }}" class="mt-4">
            @csrf
            <input type="hidden" name="divisible_products_enabled" value="0">
            <label class="flex items-start gap-3 rounded-lg border border-violet-200 bg-white p-4">
                <input type="checkbox" name="divisible_products_enabled" value="1"
                       {{ $enabled ? 'checked' : '' }}
                       class="mt-0.5 rounded border-gray-300 text-violet-600 focus:ring-violet-500">
                <span>
                    <span class="block text-sm font-medium text-gray-900">Enable divisible products for this business</span>
                    <span class="mt-0.5 block text-xs text-gray-500">POS checkout will accept decimal quantities. The business owner can also change this from their Business Profile.</span>
                </span>
            </label>
            <button type="submit" class="mt-3 rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700">
                Save divisible products
            </button>
        </form>
    @else
        <p class="mt-4 text-xs text-gray-500">Full superadmin access is required to change divisible products.</p>
    @endcan
</div>
@endif
