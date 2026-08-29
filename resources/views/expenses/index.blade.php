@extends('layouts.admin')

@section('title', 'Expense Reports')

@section('content')
<x-page-header title="Expense Reports" subtitle="{{ $label }}">
    <x-slot name="actions">
        @can('create', App\Models\Expense::class)
            <x-button variant="primary" size="sm" href="{{ tenant_route('tenant.expenses.create') }}">+ Record Expense</x-button>
        @endcan
    </x-slot>
</x-page-header>

<x-report-period-tabs :period="$period" route-name="tenant.expenses.index" />

<div class="mb-4 grid gap-4 sm:grid-cols-3">
    <x-stat-card label="Period total" :value="format_money($periodTotal, $business)" accent="amber" />
    <x-stat-card label="Entries shown" :value="number_format($expenses->total())" accent="indigo" />
</div>

<form method="GET" class="mb-4 flex flex-col gap-3 sm:flex-row">
    <input type="hidden" name="period" value="{{ $period }}">
    <x-input type="search" name="search" value="{{ $search ?? '' }}" placeholder="Search title, category, notes..." class="flex-1" />
    <x-button type="submit" variant="secondary">Search</x-button>
</form>

<div class="space-y-3 md:hidden">
    @forelse($expenses as $expense)
        <x-card :padding="false" class="p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="font-medium text-gray-900">{{ $expense->title }}</p>
                    <p class="text-xs text-gray-500">{{ $categories[$expense->category] ?? ucfirst($expense->category) }} · {{ $expense->expense_date->format('M j, Y') }}</p>
                    @if($expense->description)
                        <p class="mt-1 text-xs text-gray-600">{{ $expense->description }}</p>
                    @endif
                </div>
                <div class="text-right">
                    <p class="font-semibold text-red-600">@money($expense->amount)</p>
                    @can('update', $expense)
                        <a href="{{ tenant_route('tenant.expenses.edit', ['expense' => $expense]) }}" class="mt-1 inline-block text-xs font-medium text-indigo-600">Edit</a>
                    @endcan
                </div>
            </div>
        </x-card>
    @empty
        <x-card class="text-center text-sm text-gray-500">No expenses recorded for this period.</x-card>
    @endforelse
</div>

<x-card :padding="false" class="hidden md:block overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Title</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Category</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Amount</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse($expenses as $expense)
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $expense->expense_date->format('M j, Y') }}</td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $expense->title }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $categories[$expense->category] ?? ucfirst($expense->category) }}</td>
                        <td class="px-6 py-4 text-right text-sm font-semibold text-red-600">@money($expense->amount)</td>
                        <td class="px-6 py-4 text-right text-sm">
                            @can('update', $expense)
                                <a href="{{ tenant_route('tenant.expenses.edit', ['expense' => $expense]) }}" class="text-indigo-600 hover:text-indigo-700">Edit</a>
                            @endcan
                            @can('delete', $expense)
                                <form method="POST" action="{{ tenant_route('tenant.expenses.destroy', ['expense' => $expense]) }}" class="ml-3 inline" onsubmit="return confirm('Delete this expense?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-700">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">No expenses recorded for this period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>

<div class="mt-6">{{ $expenses->links() }}</div>
@endsection
