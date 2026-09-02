@extends(auth()->user()->usesCashierExperience() ? 'layouts.cashier' : 'layouts.admin')

@section('title', 'Damages & Write-offs')
@section('container_class', 'max-w-4xl')

@section('content')
@include('layouts.partials.cashier-operations-back')
<x-page-header title="Damages & Write-offs" subtitle="Log broken, expired, or spilled stock for today">
    <x-slot name="actions">
        <x-button variant="primary" size="sm" type="button" id="open-damage-modal">+ Log Damage</x-button>
    </x-slot>
</x-page-header>

@if(! auth()->user()->usesCashierExperience())
    <form method="GET" class="mb-6 flex flex-wrap items-end gap-3">
        <x-input type="date" name="date" label="Filter by date" value="{{ $date }}" class="w-auto" />
        <x-button variant="secondary" size="sm" type="submit">Apply</x-button>
        @if(request('date'))
            <a href="{{ tenant_route('tenant.damages.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Today</a>
        @endif
    </form>
@endif

<div class="mb-6 grid grid-cols-2 gap-4">
    <x-stat-card label="Items Written Off" :value="$summary['total_items']" accent="amber" />
    <x-stat-card label="Financial Loss" :value="format_money($summary['total_loss'])" accent="red" />
</div>

<div class="space-y-3">
    @forelse($damages as $damage)
        <x-card :padding="false" class="p-4">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="font-medium text-gray-900">{{ $damage->product->name }}</p>
                    <p class="text-xs text-gray-500">
                        {{ ucfirst($damage->reason) }} · {{ format_unit_quantity($damage->quantity, $damage->product->measurement_unit, $businessId) }}
                        · by {{ $damage->user->name }}
                    </p>
                </div>
                <div class="text-right shrink-0">
                    <p class="font-semibold text-red-600">@money($damage->lossValue())</p>
                    <p class="text-xs text-gray-500">{{ $damage->created_at->format('H:i') }}</p>
                </div>
            </div>
        </x-card>
    @empty
        <x-card class="text-center text-sm text-gray-500">No damages recorded for {{ \Carbon\Carbon::parse($date)->isToday() ? 'today' : \Carbon\Carbon::parse($date)->format('M j, Y') }}.</x-card>
    @endforelse
</div>

@if(! auth()->user()->usesCashierExperience())
    <div class="mt-6">{{ $damages->links() }}</div>
@endif

@push('modals')
<div id="damage-modal" class="app-modal-overlay" aria-hidden="true" role="dialog" aria-modal="true">
    <div class="app-modal-panel">
        <form method="POST" action="{{ tenant_route('tenant.damages.store') }}" class="flex min-h-0 flex-1 flex-col">
            @csrf
            <div class="app-modal-header">
                <h2 class="text-lg font-semibold text-gray-900">Log Damaged Stock</h2>
                <button type="button" id="close-damage-modal" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">&times;</button>
            </div>
            <div class="app-modal-body space-y-4">
                <x-select name="product_id" label="Product" required>
                    <option value="">Select product…</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                            {{ $product->displayName() }} ({{ format_unit_quantity($product->available_stock, $product->measurement_unit, $businessId) }} in stock)
                        </option>
                    @endforeach
                </x-select>

                <x-input type="number" step="1" min="1" name="quantity" label="Quantity Damaged" value="{{ old('quantity', 1) }}" required />

                <x-select name="reason" label="Reason" required>
                    @foreach($reasons as $value => $label)
                        <option value="{{ $value }}" {{ old('reason') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </x-select>

                <input type="hidden" name="damage_date" value="{{ $date }}">

                <p class="text-xs text-gray-500">Stock will be reduced automatically using FIFO costing.</p>
            </div>
            <div class="app-modal-footer">
                <x-button variant="secondary" type="button" id="cancel-damage-modal">Cancel</x-button>
                <x-button variant="primary" type="submit">Record Damage</x-button>
            </div>
        </form>
    </div>
</div>
@endpush
@endsection

@push('scripts')
<script>
(function () {
    var modal = document.getElementById('damage-modal');
    var openBtn = document.getElementById('open-damage-modal');
    var closeBtn = document.getElementById('close-damage-modal');
    var cancelBtn = document.getElementById('cancel-damage-modal');

    if (openBtn) openBtn.addEventListener('click', function () {
        window.openAppModal(modal);
    });
    if (closeBtn) closeBtn.addEventListener('click', function () {
        window.closeAppModal(modal);
    });
    if (cancelBtn) cancelBtn.addEventListener('click', function () {
        window.closeAppModal(modal);
    });

    @if($errors->any() && old('product_id'))
        window.openAppModal(modal);
    @endif
})();
</script>
@endpush
