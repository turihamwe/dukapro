@extends(auth()->user()->usesCashierExperience() ? 'layouts.cashier' : 'layouts.admin')

@section('title', 'Record Expense')

@section('content')
@include('layouts.partials.cashier-operations-back')
<x-page-header title="Record Expense" subtitle="Log a daily operating cost" />

<x-card class="max-w-2xl">
    <form method="POST" action="{{ tenant_route('tenant.expenses.store') }}" class="space-y-5" id="expense-form">
        @csrf
        <x-input type="text" name="title" label="Title" value="{{ old('title') }}" required autofocus placeholder="e.g. Electricity bill" />
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700" for="expense_category">Category</label>
            <div class="flex flex-col gap-2 sm:flex-row">
                <select name="category" id="expense_category" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm sm:max-w-xs">
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <div class="flex min-w-0 flex-1 gap-2">
                    <input type="text" id="new_expense_category" placeholder="New category name"
                           class="block min-w-0 flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <button type="button" id="add_expense_category_btn"
                            class="shrink-0 rounded-lg bg-gray-900 px-3 py-2 text-xs font-semibold text-white hover:bg-gray-800">Add</button>
                </div>
            </div>
            <p id="expense_category_message" class="mt-2 hidden text-xs text-emerald-600"></p>
            <p id="expense_category_error" class="mt-2 hidden text-xs text-red-600"></p>
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

@push('scripts')
<script>
(function () {
    var select = document.getElementById('expense_category');
    var input = document.getElementById('new_expense_category');
    var btn = document.getElementById('add_expense_category_btn');
    var msg = document.getElementById('expense_category_message');
    var err = document.getElementById('expense_category_error');
    var csrf = document.querySelector('meta[name="csrf-token"]').content;
    var url = @json($quickCategoryUrl ?? tenant_route('tenant.expenses.categories.quick-store'));

    function hideMessages() {
        msg.classList.add('hidden');
        err.classList.add('hidden');
    }

    btn.addEventListener('click', function () {
        hideMessages();
        var name = input.value.trim();
        if (!name) return;

        btn.disabled = true;
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ name: name }),
        })
        .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
        .then(function (res) {
            btn.disabled = false;
            if (!res.ok) {
                err.textContent = res.data.message || (res.data.errors ? Object.values(res.data.errors).flat()[0] : 'Could not add category.');
                err.classList.remove('hidden');
                return;
            }
            var option = document.createElement('option');
            option.value = res.data.slug;
            option.textContent = res.data.name;
            option.selected = true;
            select.appendChild(option);
            input.value = '';
            msg.textContent = 'Category added.';
            msg.classList.remove('hidden');
        })
        .catch(function () {
            btn.disabled = false;
            err.textContent = 'Network error. Try again.';
            err.classList.remove('hidden');
        });
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            btn.click();
        }
    });
})();
</script>
@endpush
