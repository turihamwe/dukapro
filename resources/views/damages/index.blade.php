@extends('layouts.admin')

@section('title', 'Damages & Write-offs')
@section('container_class', 'max-w-4xl')

@section('content')
<x-page-header title="Damages & Write-offs" subtitle="Log broken, expired, or spilled stock">
    <x-slot name="actions">
        <x-button variant="primary" size="sm" type="button" id="open-damage-modal">+ Log Damage</x-button>
    </x-slot>
</x-page-header>

<form method="GET" class="mb-6 flex flex-wrap items-end gap-3">
    <x-input type="date" name="date" label="Filter by date" value="{{ $date }}" class="w-auto" />
    <x-button variant="secondary" size="sm" type="submit">Apply</x-button>
    @if(request('date'))
        <a href="{{ tenant_route('tenant.damages.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Today</a>
    @endif
</form>

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
                        {{ ucfirst($damage->reason) }} · {{ $damage->quantity }} {{ $damage->product->measurement_unit }}
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
        <x-card class="text-center text-sm text-gray-500">No damages recorded for this date.</x-card>
    @endforelse
</div>

<div class="mt-6">{{ $damages->links() }}</div>

{{-- Log damage modal --}}
<div id="damage-modal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" id="damage-modal-backdrop"></div>
    <div class="flex min-h-full items-end justify-center p-4 sm:items-center">
        <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Log Damaged Stock</h2>
                <button type="button" id="close-damage-modal" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">&times;</button>
            </div>

            <form method="POST" action="{{ tenant_route('tenant.damages.store') }}" class="space-y-4">
                @csrf

                <x-select name="product_id" label="Product" required>
                    <option value="">Select product…</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                            {{ $product->name }} ({{ $product->stock_quantity }} {{ $product->measurement_unit }} in stock)
                        </option>
                    @endforeach
                </x-select>

                <x-input type="number" step="0.001" min="0.001" name="quantity" label="Quantity Damaged" value="{{ old('quantity') }}" required />

                <x-select name="reason" label="Reason" required>
                    @foreach($reasons as $value => $label)
                        <option value="{{ $value }}" {{ old('reason') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </x-select>

                <x-input type="date" name="damage_date" label="Date" value="{{ old('damage_date', $date) }}" required />

                <p class="text-xs text-gray-500">Stock will be reduced automatically. Loss is calculated from the product cost price.</p>

                <div class="flex gap-3 pt-2">
                    <x-button variant="primary" type="submit" class="flex-1">Record Damage</x-button>
                    <x-button variant="secondary" type="button" id="cancel-damage-modal">Cancel</x-button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var modal = document.getElementById('damage-modal');
    var openBtn = document.getElementById('open-damage-modal');
    var closeBtn = document.getElementById('close-damage-modal');
    var cancelBtn = document.getElementById('cancel-damage-modal');
    var backdrop = document.getElementById('damage-modal-backdrop');

    function openModal() {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    if (openBtn) openBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    if (backdrop) backdrop.addEventListener('click', closeModal);

    @if($errors->any() && old('product_id'))
        openModal();
    @endif
})();
</script>
@endpush
