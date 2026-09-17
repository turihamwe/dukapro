@php
    use App\Support\DivisibleProductsMode;

    $divisibleProductsPlatform = DivisibleProductsMode::platformEnabled();
@endphp

@if($divisibleProductsPlatform)
    <div class="rounded-xl border border-violet-200 bg-violet-50/60 p-4 sm:p-5">
        <p class="text-sm font-semibold text-gray-900">Divisible products</p>
        <p class="mt-1 text-xs text-gray-600">
            For businesses that sell by weight, length, or volume — e.g. 0.5 meters of fabric or 1.25 kg of rice.
            When enabled, POS checkout accepts fractional quantities. When disabled, cashiers must use whole numbers only.
        </p>
        <label class="mt-4 flex items-start gap-3 rounded-lg border border-violet-200 bg-white p-4">
            <input type="hidden" name="divisible_products_enabled" value="0">
            <input type="checkbox" name="divisible_products_enabled" value="1"
                   {{ old('divisible_products_enabled', $business->divisible_products_enabled ?? false) ? 'checked' : '' }}
                   class="mt-0.5 rounded border-gray-300 text-violet-600 focus:ring-violet-500">
            <span>
                <span class="block text-sm font-medium text-gray-900">Enable divisible products</span>
                <span class="mt-0.5 block text-xs text-gray-500">When ON, POS quantity fields accept decimals such as 0.5 or 1.25. Stock is still tracked to three decimal places.</span>
            </span>
        </label>
    </div>
@endif
