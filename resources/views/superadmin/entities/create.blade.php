@extends('layouts.superadmin')

@section('title', 'Add ' . rtrim($config['label'], 's'))

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold tracking-tight">Add {{ rtrim($config['label'], 's') }}</h1>
</div>

<div class="max-w-xl rounded-xl border border-gray-200 bg-white p-6">
    <form method="POST" action="{{ route('superadmin.entities.store', $entity) }}" class="space-y-4">
        @csrf

        @if(in_array($entity, ['users', 'products', 'customers', 'expenses'], true))
            <div>
                <label class="mb-1 block text-sm font-medium">Business</label>
                <select name="business_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach($businesses as $business)
                        <option value="{{ $business->id }}" @selected(old('business_id') == $business->id)>{{ $business->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        @if($entity === 'businesses')
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Business name" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="text" name="phone" value="{{ old('phone') }}" placeholder="Phone" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        @elseif($entity === 'users')
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Full name" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="text" name="username" value="{{ old('username') }}" required placeholder="Username" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="email" name="email" value="{{ old('email') }}" required placeholder="Email" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="password" name="password" required placeholder="Password" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <select name="role" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @foreach($roles as $role)
                    <option value="{{ $role }}">{{ ucfirst($role) }}</option>
                @endforeach
            </select>
        @elseif($entity === 'products')
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Product name" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="text" name="sku" value="{{ old('sku') }}" placeholder="SKU" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="number" step="0.01" name="price" value="{{ old('price') }}" required placeholder="Price" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="text" name="measurement_unit" value="{{ old('measurement_unit', 'unit') }}" required placeholder="Unit" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="number" step="0.001" name="stock_quantity" value="{{ old('stock_quantity', 0) }}" required placeholder="Stock quantity" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        @elseif($entity === 'customers')
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Customer name" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="text" name="phone" value="{{ old('phone') }}" placeholder="Phone" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        @elseif($entity === 'expenses')
            <input type="text" name="title" value="{{ old('title') }}" required placeholder="Title" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <select name="category" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @foreach($categories as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <input type="number" step="0.01" name="amount" value="{{ old('amount') }}" required placeholder="Amount" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="date" name="expense_date" value="{{ old('expense_date', now()->toDateString()) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <textarea name="description" rows="3" placeholder="Notes" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('description') }}</textarea>
        @endif

        <button type="submit" class="rounded-lg bg-violet-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-violet-500">Create</button>
    </form>
</div>
@endsection
