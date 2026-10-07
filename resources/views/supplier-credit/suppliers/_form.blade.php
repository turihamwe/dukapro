@php
    $supplier = $supplier ?? null;
@endphp
<x-input type="text" name="name" label="Supplier name" value="{{ old('name', $supplier->name ?? '') }}" required />
<div class="grid gap-4 sm:grid-cols-2">
    <x-input type="text" name="phone" label="Phone" value="{{ old('phone', $supplier->phone ?? '') }}" />
    <x-input type="email" name="email" label="Email" value="{{ old('email', $supplier->email ?? '') }}" />
</div>
<x-textarea name="notes" label="Notes" rows="2">{{ old('notes', $supplier->notes ?? '') }}</x-textarea>

@php
    $hasOpeningBill = $supplier && $supplier->hasOpeningBalanceBill();
@endphp
<div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
    <x-input type="number"
             step="0.01"
             min="0"
             name="opening_balance"
             label="Opening balance (initial amount owed)"
             value="{{ old('opening_balance', $supplier->opening_balance ?? '') }}"
             :disabled="$hasOpeningBill"
             placeholder="0" />
    @if($hasOpeningBill)
        <p class="mt-2 text-xs text-gray-600">
            Opening balance bill already created.
            @if($supplier->openingBalancePurchase)
                <a href="{{ tenant_route('tenant.supplier-credit.bills.show', ['purchase' => $supplier->openingBalancePurchase]) }}"
                   class="font-medium text-indigo-600 hover:text-indigo-800">View bill</a>
            @endif
        </p>
    @else
        <p class="mt-2 text-xs text-gray-500">
            If you already owe this vendor money, enter it here. We will create a bill titled
            <strong>Opening Balance</strong> so you can pay it down over time. This does <strong>not</strong> change inventory stock.
        </p>
    @endif
</div>

@if($supplier)
    <label class="flex items-center gap-2 text-sm text-gray-700">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
               @checked(old('is_active', $supplier->is_active))>
        Active (show in restock supplier lists)
    </label>
@endif
