@can('adjust-wallet-transactions')
    @if(isset($product) && $product->tracksInventory() && ! $product->isVariableParent())
        <x-button variant="secondary" size="sm" type="button"
                  data-open-opening-correction
                  data-modal="product-opening-quantity"
                  data-action="{{ tenant_route('tenant.inventory.opening-quantity', ['product' => $product]) }}"
                  data-entity="{{ $product->displayName() }}"
                  data-current="{{ format_unit_quantity($product->stock_quantity, $product->measurement_unit, $product->business_id) }}"
                  data-value="{{ number_format((float) $product->stock_quantity, 3, '.', '') }}">Correct opening quantity</x-button>
    @endif
@endcan
