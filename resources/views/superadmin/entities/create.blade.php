@extends('layouts.superadmin')

@section('title', 'Add ' . rtrim($config['label'], 's'))

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold tracking-tight">Add {{ rtrim($config['label'], 's') }}</h1>
</div>

<div class="max-w-xl rounded-xl border border-gray-200 bg-white p-6">
    <form method="POST" action="{{ route('superadmin.entities.store', $entity) }}" class="space-y-4">
        @csrf

        @if(in_array($entity, ['staff', 'products', 'customers', 'expenses', 'branches'], true))
            <div>
                <label class="mb-1 block text-sm font-medium">Business</label>
                <select name="business_id" id="entity-business-id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
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
        @elseif($entity === 'branches')
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Branch name" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="text" name="address" value="{{ old('address') }}" placeholder="Address" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="text" name="phone" value="{{ old('phone') }}" placeholder="Phone" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))> Active</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_default" value="1" @checked(old('is_default'))> Default branch</label>
        @elseif($entity === 'staff')
            <div>
                <label class="mb-1 block text-sm font-medium">Branch</label>
                <select name="branch_id" id="staff-branch-id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">Select branch…</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" data-business-id="{{ $branch->business_id }}" @selected(old('branch_id') == $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
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
        @elseif($entity === 'affiliates')
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Full name" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="email" name="email" value="{{ old('email') }}" required placeholder="Email" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="text" name="phone" value="{{ old('phone') }}" placeholder="Phone" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="text" name="code" value="{{ old('code') }}" placeholder="Referral code (auto-generated if empty)" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="number" step="0.0001" min="0" max="1" name="commission_rate" value="{{ old('commission_rate', config('affiliates.default_commission_rate')) }}" placeholder="Commission rate (0.10 = 10%)" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <select name="status" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @foreach($affiliateStatuses as $status)
                    <option value="{{ $status }}" @selected(old('status', 'pending') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active'))> Active</label>
        @elseif($entity === 'shareholders')
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Full name" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="email" name="email" value="{{ old('email') }}" required placeholder="Email" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="text" name="phone" value="{{ old('phone') }}" placeholder="Phone" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="text" name="national_id" value="{{ old('national_id') }}" placeholder="National ID" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="number" step="0.01" min="0.01" max="{{ $remainingShares ?? 100 }}" name="shares_owned" value="{{ old('shares_owned', 1) }}" required placeholder="Shares owned" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <p class="text-xs text-gray-500">{{ number_format($remainingShares ?? 0, 2) }} shares remaining · UGX {{ number_format($pricePerShare ?? 1000000, 0) }} per share</p>
            <select name="status" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @foreach($shareholderStatuses as $status)
                    <option value="{{ $status }}" @selected(old('status', 'pending') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active'))> Active</label>
        @elseif($entity === 'shareholder_earnings')
            <select name="shareholder_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @foreach($shareholders as $shareholder)
                    <option value="{{ $shareholder->id }}" @selected(old('shareholder_id') == $shareholder->id)>{{ $shareholder->name }} ({{ $shareholder->email }})</option>
                @endforeach
            </select>
            <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required placeholder="Amount (UGX)" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="text" name="description" value="{{ old('description') }}" placeholder="Description" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <input type="text" name="reference" value="{{ old('reference') }}" placeholder="Reference" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        @endif

        <button type="submit" class="rounded-lg bg-violet-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-violet-500">Create</button>
    </form>
</div>

@if($entity === 'staff')
@push('scripts')
<script>
(function () {
    var businessSelect = document.getElementById('entity-business-id');
    var branchSelect = document.getElementById('staff-branch-id');
    if (!businessSelect || !branchSelect) return;

    function filterBranches() {
        var businessId = businessSelect.value;
        Array.from(branchSelect.options).forEach(function (option, index) {
            if (index === 0) {
                option.hidden = false;
                return;
            }
            option.hidden = option.dataset.businessId !== businessId;
        });
        branchSelect.value = '';
    }

    businessSelect.addEventListener('change', filterBranches);
    filterBranches();
})();
</script>
@endpush
@endif
@endsection
