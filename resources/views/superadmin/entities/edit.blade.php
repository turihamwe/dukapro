@extends('layouts.superadmin')

@section('title', 'Edit record')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold tracking-tight">Edit {{ rtrim($config['label'], 's') }} #{{ $item->id }}</h1>
</div>

<div class="max-w-xl rounded-xl border border-gray-200 bg-white p-6">
    <form method="POST" action="{{ route('superadmin.entities.update', [$entity, $item->id]) }}" class="space-y-4">
        @csrf
        @method('PUT')

        @if($entity === 'businesses')
            <input type="text" name="name" value="{{ old('name', $item->name) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="email" name="email" value="{{ old('email', $item->email) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="text" name="phone" value="{{ old('phone', $item->phone) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="text" name="subscription_status" value="{{ old('subscription_status', $item->subscription_status) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active))> Active</label>
        @elseif($entity === 'users')
            <input type="text" name="name" value="{{ old('name', $item->name) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="email" name="email" value="{{ old('email', $item->email) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <select name="role" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @foreach($roles as $role)
                    <option value="{{ $role }}" @selected(old('role', $item->role) === $role)>{{ ucfirst($role) }}</option>
                @endforeach
            </select>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active))> Active</label>
        @elseif($entity === 'products')
            <input type="text" name="name" value="{{ old('name', $item->name) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="number" step="0.01" name="price" value="{{ old('price', $item->price) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="number" step="0.001" name="stock_quantity" value="{{ old('stock_quantity', $item->stock_quantity) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active))> Active</label>
        @elseif($entity === 'customers')
            <input type="text" name="name" value="{{ old('name', $item->name) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="text" name="phone" value="{{ old('phone', $item->phone) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="email" name="email" value="{{ old('email', $item->email) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active))> Active</label>
        @elseif($entity === 'expenses')
            <input type="text" name="title" value="{{ old('title', $item->title) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <select name="category" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @foreach($categories as $key => $label)
                    <option value="{{ $key }}" @selected(old('category', $item->category) === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <input type="number" step="0.01" name="amount" value="{{ old('amount', $item->amount) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="date" name="expense_date" value="{{ old('expense_date', optional($item->expense_date)->toDateString()) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <textarea name="description" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('description', $item->description) }}</textarea>
        @elseif($entity === 'affiliates')
            <input type="text" name="name" value="{{ old('name', $item->name) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="email" name="email" value="{{ old('email', $item->email) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="text" name="phone" value="{{ old('phone', $item->phone) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="text" name="code" value="{{ old('code', $item->code) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="number" step="0.0001" min="0" max="1" name="commission_rate" value="{{ old('commission_rate', $item->commission_rate) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <select name="status" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @foreach($affiliateStatuses as $status)
                    <option value="{{ $status }}" @selected(old('status', $item->status) === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active))> Active</label>
        @elseif($entity === 'shareholders')
            <input type="text" name="name" value="{{ old('name', $item->name) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="email" name="email" value="{{ old('email', $item->email) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="text" name="phone" value="{{ old('phone', $item->phone) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="text" name="national_id" value="{{ old('national_id', $item->national_id) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="number" step="0.01" min="0.01" name="shares_owned" value="{{ old('shares_owned', $item->shares_owned) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <p class="text-xs text-gray-500">{{ number_format($remainingShares ?? 0, 2) }} shares remaining · Cap: UGX {{ number_format($item->earningsCap(), 0) }}</p>
            <select name="status" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @foreach($shareholderStatuses as $status)
                    <option value="{{ $status }}" @selected(old('status', $item->status) === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active))> Active</label>
        @elseif($entity === 'affiliate_commissions')
            <select name="status" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value="pending" @selected(old('status', $item->status) === 'pending')>Pending</option>
                <option value="paid" @selected(old('status', $item->status) === 'paid')>Paid</option>
                <option value="cancelled" @selected(old('status', $item->status) === 'cancelled')>Cancelled</option>
            </select>
        @endif

        <button type="submit" class="rounded-lg bg-violet-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-violet-500">Save changes</button>
    </form>
</div>
@endsection
