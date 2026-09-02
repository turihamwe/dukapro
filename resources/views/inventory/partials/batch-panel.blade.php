@php
    $activeBatches = $sellable->activeBatches ?? collect();
    $batchStock = $sellable->batchStockQuantity();
    $totalStock = $sellable->totalStockQuantity();
    $hasBatches = $activeBatches->isNotEmpty();
    $modalId = 'add-batch-modal-' . $sellable->id;
@endphp

<x-card>
    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-100 pb-4">
        <div>
            @if($sellable->id !== $parentProduct->id)
                <h3 class="text-base font-semibold text-gray-900">{{ $sellable->displayName() }}</h3>
                <p class="text-xs text-gray-500">{{ $sellable->sku ?? 'No SKU' }}</p>
            @else
                <h3 class="text-base font-semibold text-gray-900">Inventory batches</h3>
                <p class="text-xs text-gray-500">FIFO deduction uses oldest batch first, then legacy stock.</p>
            @endif
        </div>
        <div class="text-right">
            <p class="text-xs text-gray-500">Total stock</p>
            <p class="text-lg font-bold text-indigo-700">{{ format_unit_quantity($totalStock, $sellable->measurement_unit, $sellable->business_id) }}</p>
            @if($hasBatches)
                <span class="mt-1 inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-[11px] font-medium text-indigo-700">
                    {{ $activeBatches->count() }} active {{ Str::plural('batch', $activeBatches->count()) }}
                </span>
            @endif
        </div>
        @can('update', $parentProduct)
            @if($sellable->id !== $parentProduct->id)
                <x-button variant="primary" size="sm" type="button" onclick="openAppModal('{{ $modalId }}')">+ Add Batch</x-button>
            @endif
        @endcan
    </div>

    <div class="mt-4 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead>
                <tr class="text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                    <th class="py-2 pr-4">Source</th>
                    <th class="py-2 pr-4 text-center">Remaining</th>
                    @if($canViewCost)
                        <th class="py-2 pr-4 text-center">Cost</th>
                    @endif
                    <th class="py-2 pr-4 text-center">Sell</th>
                    <th class="py-2 text-center">Received</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @if(! empty($showLegacyRow) && $sellable->stock_quantity > 0)
                    <tr>
                        <td class="py-3 pr-4">
                            <span class="font-medium text-gray-900">Legacy stock</span>
                            <span class="ml-2 rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium uppercase text-gray-600">Original</span>
                        </td>
                        <td class="py-3 pr-4 text-center font-medium text-gray-900">{{ $sellable->stock_quantity }}</td>
                        @if($canViewCost)
                            <td class="py-3 pr-4 text-center text-gray-600">@money($sellable->cost_price ?? 0)</td>
                        @endif
                        <td class="py-3 pr-4 text-center text-gray-900">@money($sellable->price)</td>
                        <td class="py-3 text-center text-gray-500">{{ $sellable->created_at->format('M j, Y') }}</td>
                    </tr>
                @endif
                @forelse($activeBatches as $batch)
                    <tr>
                        <td class="py-3 pr-4">
                            <span class="font-medium text-gray-900">Batch #{{ $batch->id }}</span>
                            @if($batch->notes)
                                <p class="text-xs text-gray-500">{{ Str::limit($batch->notes, 60) }}</p>
                            @endif
                        </td>
                        <td class="py-3 pr-4 text-center font-medium text-gray-900">{{ $batch->remaining_quantity }} / {{ $batch->quantity }}</td>
                        @if($canViewCost)
                            <td class="py-3 pr-4 text-center text-gray-600">@money($batch->cost_price ?? 0)</td>
                        @endif
                        <td class="py-3 pr-4 text-center text-gray-900">@money($batch->selling_price)</td>
                        <td class="py-3 text-center text-gray-500">{{ $batch->received_at->format('M j, Y') }}</td>
                    </tr>
                @empty
                    @if(empty($showLegacyRow) || $sellable->stock_quantity <= 0)
                        <tr>
                            <td colspan="{{ $canViewCost ? 5 : 4 }}" class="py-6 text-center text-gray-500">No active batches yet.</td>
                        </tr>
                    @endif
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>

@can('update', $parentProduct)
    @if($sellable->id !== $parentProduct->id)
        <div id="{{ $modalId }}" class="app-modal-overlay" role="dialog" aria-modal="true">
            <div class="app-modal-panel">
                <div class="app-modal-header">
                    <h3 class="text-lg font-semibold text-gray-900">Add Batch — {{ $sellable->displayName() }}</h3>
                    <button type="button" onclick="closeAppModal('{{ $modalId }}')" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100">&times;</button>
                </div>
                <form method="POST" action="{{ tenant_route('tenant.inventory.batches.store', ['product' => $parentProduct]) }}" class="flex min-h-0 flex-1 flex-col">
                    @csrf
                    <input type="hidden" name="variant_id" value="{{ $sellable->id }}">
                    <div class="app-modal-body space-y-4">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Quantity received</label>
                            <input type="number" step="0.001" min="0.001" name="quantity" required
                                   class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700">Selling price</label>
                                <input type="number" step="0.01" min="0" name="selling_price" required value="{{ $sellable->price }}"
                                       class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            @if($canViewCost)
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Cost price</label>
                                    <input type="number" step="0.01" min="0" name="cost_price" value="{{ $sellable->cost_price }}"
                                           class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            @endif
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Received date</label>
                            <input type="date" name="received_at" value="{{ now()->toDateString() }}"
                                   class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Notes</label>
                            <textarea name="notes" rows="2" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        </div>
                    </div>
                    <div class="app-modal-footer">
                        <button type="button" onclick="closeAppModal('{{ $modalId }}')"
                                class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save Batch</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endcan
