@php
    use App\Support\VariablePricingMode;

    $variablePricingPlatform = VariablePricingMode::platformEnabled();
@endphp

@if($variablePricingPlatform)
    <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-4 sm:p-5">
        <p class="text-sm font-semibold text-gray-900">Variable pricing mode</p>
        <p class="mt-1 text-xs text-gray-600">
            For open-air markets, furniture shops, and hardware stores where prices are negotiated at the point of sale.
            When enabled, cashiers can edit item prices during checkout. Catalog prices remain the default starting point.
        </p>
        <label class="mt-4 flex items-start gap-3 rounded-lg border border-amber-200 bg-white p-4">
            <input type="hidden" name="variable_pricing_enabled" value="0">
            <input type="checkbox" name="variable_pricing_enabled" value="1"
                   {{ old('variable_pricing_enabled', $business->variable_pricing_enabled ?? false) ? 'checked' : '' }}
                   class="mt-0.5 rounded border-gray-300 text-amber-600 focus:ring-amber-500">
            <span>
                <span class="block text-sm font-medium text-gray-900">Variable Pricing Mode</span>
                <span class="mt-0.5 block text-xs text-gray-500">When ON, POS checkout allows editing the selling price per cart line before completing the sale.</span>
            </span>
        </label>
    </div>
@endif
