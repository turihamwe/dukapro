@php
    use App\Support\LowStockAlertMode;
    $currentMode = old('low_stock_alert_mode', LowStockAlertMode::forBusiness($business));
@endphp
<div class="rounded-xl border border-gray-200 bg-white p-4 sm:p-5">
    <h2 class="text-sm font-semibold text-gray-900">Low stock alerts</h2>
    <p class="mt-1 text-xs text-gray-500">Control how inventory alerts appear for products with variants. Branch staff only see alerts for their branch.</p>

    <fieldset class="mt-4 space-y-3">
        @foreach(LowStockAlertMode::labels() as $value => $label)
            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 px-3 py-3 text-sm hover:border-emerald-200 hover:bg-emerald-50/30">
                <input type="radio" name="low_stock_alert_mode" value="{{ $value }}"
                       class="mt-0.5 border-gray-300 text-emerald-600 focus:ring-emerald-500"
                       @checked($currentMode === $value)>
                <span>
                    <span class="font-medium text-gray-900">{{ $label }}</span>
                    @if($value === LowStockAlertMode::COMBINED)
                        <span class="mt-0.5 block text-xs text-gray-500">Recommended. One alert per product using total stock across all variants — fewer notifications for large catalogs.</span>
                    @else
                        <span class="mt-0.5 block text-xs text-gray-500">Alert when any single variant is low, even if other variants still have stock.</span>
                    @endif
                </span>
            </label>
        @endforeach
    </fieldset>
    @error('low_stock_alert_mode')
        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
