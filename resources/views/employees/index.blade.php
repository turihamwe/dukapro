@extends('layouts.admin')

@section('title', 'Employees')

@section('content')
@php use App\Enums\UserRole; @endphp

<x-page-header title="Employees" subtitle="{{ $employees->count() }} staff members">
    <x-slot name="actions">
        @can('create', App\Models\User::class)
            <x-button variant="primary" size="sm" href="{{ tenant_route('tenant.employees.create') }}">+ Add Employee</x-button>
        @endcan
    </x-slot>
</x-page-header>

<x-card :padding="false" class="overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Branch</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Email</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse($employees as $employee)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $employee->name }}</td>
                        <td class="px-6 py-4 text-sm capitalize text-gray-600">{{ $employee->role }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $employee->branch_name ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $employee->email }}</td>
                        <td class="px-6 py-4 text-right">
                            @can('delete', $employee)
                                <x-delete-confirm-button
                                    :action="tenant_route('tenant.employees.destroy', ['employee' => $employee])"
                                    message="Are you sure you want to delete this item?"
                                    detail="Remove this employee?"
                                    label="Remove"
                                />
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">No employees yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>
@endsection
