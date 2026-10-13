@extends('layouts.admin')

@section('title', 'Edit Expense')

@section('content')
<x-page-header title="Edit Expense" subtitle="{{ $expense->title }}" />

<x-card class="max-w-2xl">
    <form method="POST" action="{{ tenant_route('tenant.expenses.update', ['expense' => $expense]) }}" class="space-y-5">
        @csrf
        @method('PUT')
        <x-input type="text" name="title" label="Title" value="{{ old('title', $expense->title) }}" required />
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Category</label>
            <select name="category" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @foreach($categories as $key => $label)
                    <option value="{{ $key }}" @selected(old('category', $expense->category) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-input type="number" step="0.01" name="amount" label="Amount (UGX)" value="{{ old('amount', $expense->amount) }}" required />
            <x-input type="date" name="expense_date" label="Expense date" value="{{ old('expense_date', $expense->expense_date->toDateString()) }}" required />
        </div>
        @include('expenses._payment-fields', [
            'usesPaymentWallets' => $usesPaymentWallets ?? false,
            'wallets' => $wallets ?? collect(),
            'selectedWalletId' => $expense->payment_wallet_id,
            'paymentMethod' => $expense->payment_method,
            'receiptReference' => $expense->receipt_reference,
        ])
        <x-textarea name="description" label="Notes" rows="3">{{ old('description', $expense->description) }}</x-textarea>
        <x-button type="submit" variant="primary">Update expense</x-button>
    </form>
</x-card>
@endsection
