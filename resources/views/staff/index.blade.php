@extends('layouts.admin')

@section('title', 'Staff')

@section('content')
@php use App\Enums\UserRole; @endphp

<x-page-header title="Staff" subtitle="{{ $staff->count() }} team members">
    <x-slot name="actions">
        @can('create', App\Models\User::class)
            <x-button variant="primary" size="sm" href="{{ tenant_route('tenant.staff.create') }}">+ Add Staff</x-button>
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
                @forelse($staff as $member)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $member->name }}</td>
                        <td class="px-6 py-4 text-sm capitalize text-gray-600">{{ $member->role }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $member->branch_name ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $member->email }}</td>
                        <td class="px-6 py-4 text-right text-sm">
                            @can('update', $member)
                                <a href="{{ tenant_route('tenant.staff.edit', ['employee' => $member]) }}" class="font-medium text-indigo-600 hover:text-indigo-700">Edit</a>
                            @endcan
                            @can('delete', $member)
                                <form method="POST" action="{{ tenant_route('tenant.staff.destroy', ['employee' => $member]) }}" class="ml-3 inline" onsubmit="return confirm('Remove this staff member?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-medium text-red-600 hover:text-red-700">Remove</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">No staff yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>
@endsection
