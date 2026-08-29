@extends(auth()->user()->isCashier() ? 'layouts.cashier' : 'layouts.admin')

@section('title', 'Record Expense')

@section('content')
<x-page-header title="Record Expense" subtitle="Log a daily operating cost" />

<x-card class="max-w-2xl">
    <form method="POST" action="{{ tenant_route('tenant.expenses.store') }}" class="space-y-5">
        @csrf
        <x-input type="text" name="title" label="Title" value="{{ old('title') }}" required autofocus placeholder="e.g. Electricity bill" />
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Category</label>
            <select name="category" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @foreach($categories as $key => $label)
                    <option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-input type="number" step="0.01" name="amount" label="Amount (UGX)" value="{{ old('amount') }}" required />
            <x-input type="date" name="expense_date" label="Expense date" value="{{ old('expense_date', now()->toDateString()) }}" required />
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Payment method</label>
                <select name="payment_method" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="cash" @selected(old('payment_method', 'cash') === 'cash')>Cash</option>
                    <option value="mobile_money" @selected(old('payment_method') === 'mobile_money')>Mobile Money</option>
                    <option value="bank" @selected(old('payment_method') === 'bank')>Bank</option>
                </select>
            </div>
            <x-input type="text" name="receipt_reference" label="Receipt / reference" value="{{ old('receipt_reference') }}" />
        </div>
        <x-textarea name="description" label="Notes" rows="3" placeholder="Optional details about this expense">{{ old('description') }}</x-textarea>
        <x-button type="submit" variant="primary">Save expense</x-button>
    </form>
</x-card>
@endsection
