@php
    $customer = $customer ?? null;
    $hasOpeningEntry = $customer && $customer->hasOpeningBalanceEntry();
@endphp
<x-input type="text" name="name" label="Customer name" value="{{ old('name', $customer->name ?? '') }}" required />
<div class="grid gap-4 sm:grid-cols-2">
    <x-input type="text" name="company_name" label="Company" value="{{ old('company_name', $customer->company_name ?? '') }}" />
    <x-input type="text" name="phone" label="Phone" value="{{ old('phone', $customer->phone ?? '') }}" />
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <x-input type="email" name="email" label="Email" value="{{ old('email', $customer->email ?? '') }}" />
    <x-input type="number" step="1" min="1" max="365" name="payment_terms_days" label="Payment terms (days)" value="{{ old('payment_terms_days', $customer->payment_terms_days ?? 30) }}" />
</div>
<x-textarea name="address" label="Address" rows="2">{{ old('address', $customer->address ?? '') }}</x-textarea>
<x-textarea name="notes" label="Notes" rows="2">{{ old('notes', $customer->notes ?? '') }}</x-textarea>
<x-input type="number" step="0.01" min="0" name="credit_limit" label="Credit limit" value="{{ old('credit_limit', $customer->credit_limit ?? 0) }}" />

<div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
    <x-input type="number"
             step="0.01"
             min="0"
             name="opening_balance"
             label="Opening balance (past debt)"
             value="{{ old('opening_balance', $customer->opening_balance ?? '') }}"
             :disabled="$hasOpeningEntry"
             placeholder="0" />
    @if($hasOpeningEntry)
        <p class="mt-2 text-xs text-gray-600">Opening balance entry already recorded on the ledger.</p>
    @else
        <p class="mt-2 text-xs text-gray-500">
            Enter money customers already owe you before using {{ config('app.name', 'DukaPro') }}.
            Creates a ledger entry titled <strong>Opening Balance</strong> with no POS sale and no stock change.
        </p>
    @endif
</div>

@if($customer)
    <label class="flex items-center gap-2 text-sm text-gray-700">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
               @checked(old('is_active', $customer->is_active))>
        Active (available at POS checkout)
    </label>
@endif
