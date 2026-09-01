<div class="overflow-x-auto rounded-lg border border-indigo-100 bg-white">
    <table class="min-w-full text-xs">
        <thead class="bg-gray-50 text-left uppercase tracking-wide text-gray-500">
            <tr>
                <th class="px-3 py-2">Item</th>
                <th class="px-3 py-2 text-center">Remaining</th>
                @if($canViewCost)
                    <th class="px-3 py-2 text-center">Cost</th>
                @endif
                <th class="px-3 py-2 text-center">Sell</th>
                <th class="px-3 py-2 text-center">Received</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @if($product->variants_count > 0)
                @foreach($product->variants as $variant)
                    @if($variant->stock_quantity > 0)
                        <tr>
                            <td class="px-3 py-2 font-medium text-gray-900">{{ $variant->displayName() }} <span class="text-gray-400">· Legacy</span></td>
                            <td class="px-3 py-2 text-center">{{ $variant->stock_quantity }}</td>
                            @if($canViewCost)
                                <td class="px-3 py-2 text-center">@money($variant->cost_price ?? 0)</td>
                            @endif
                            <td class="px-3 py-2 text-center">@money($variant->price)</td>
                            <td class="px-3 py-2 text-center text-gray-500">{{ $variant->created_at->format('M j, Y') }}</td>
                        </tr>
                    @endif
                    @foreach($variant->activeBatches as $batch)
                        <tr>
                            <td class="px-3 py-2 font-medium text-gray-900">{{ $variant->displayName() }} <span class="text-indigo-600">· Batch #{{ $batch->id }}</span></td>
                            <td class="px-3 py-2 text-center">{{ $batch->remaining_quantity }} / {{ $batch->quantity }}</td>
                            @if($canViewCost)
                                <td class="px-3 py-2 text-center">@money($batch->cost_price ?? 0)</td>
                            @endif
                            <td class="px-3 py-2 text-center">@money($batch->selling_price)</td>
                            <td class="px-3 py-2 text-center text-gray-500">{{ $batch->received_at->format('M j, Y') }}</td>
                        </tr>
                    @endforeach
                @endforeach
            @else
                @if($product->stock_quantity > 0)
                    <tr>
                        <td class="px-3 py-2 font-medium text-gray-900">Legacy stock</td>
                        <td class="px-3 py-2 text-center">{{ $product->stock_quantity }}</td>
                        @if($canViewCost)
                            <td class="px-3 py-2 text-center">@money($product->cost_price ?? 0)</td>
                        @endif
                        <td class="px-3 py-2 text-center">@money($product->price)</td>
                        <td class="px-3 py-2 text-center text-gray-500">{{ $product->created_at->format('M j, Y') }}</td>
                    </tr>
                @endif
                @foreach($product->activeBatches as $batch)
                    <tr>
                        <td class="px-3 py-2 font-medium text-gray-900">Batch #{{ $batch->id }}</td>
                        <td class="px-3 py-2 text-center">{{ $batch->remaining_quantity }} / {{ $batch->quantity }}</td>
                        @if($canViewCost)
                            <td class="px-3 py-2 text-center">@money($batch->cost_price ?? 0)</td>
                        @endif
                        <td class="px-3 py-2 text-center">@money($batch->selling_price)</td>
                        <td class="px-3 py-2 text-center text-gray-500">{{ $batch->received_at->format('M j, Y') }}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>
</div>
