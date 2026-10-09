@php
    /** @var \App\Models\Product $product */
    use App\Support\ProductInventoryValuation;

    $costProduct = $product;
@endphp
@can('view-cost-prices')
    @php
        $defaultCost = ProductInventoryValuation::purchaseDefaultUnitCost($costProduct);
        $lastCost = ProductInventoryValuation::lastPurchaseUnitCost($costProduct);
        $pct = ProductInventoryValuation::percentChangeFromDefault($lastCost, $defaultCost > 0 ? $defaultCost : null);
        $unitLabel = $costProduct->measurement_unit ?: 'unit';
    @endphp
    <p class="mt-1 text-xs text-gray-500">
        @if($lastCost !== null)
            Previously:
            <span class="font-medium text-gray-700">@money($lastCost)</span>
            / {{ $unitLabel }}
            @if($pct !== null)
                @php
                    $pctLabel = ($pct > 0 ? '+' : '') . rtrim(rtrim(number_format($pct, 1, '.', ''), '0'), '.') . '%';
                    $pctClass = $pct > 0 ? 'text-emerald-600' : ($pct < 0 ? 'text-red-600' : 'text-gray-500');
                @endphp
                <span class="font-medium {{ $pctClass }}">({{ $pctLabel }})</span>
            @endif
        @else
            No previous price
        @endif
    </p>
@endcan
