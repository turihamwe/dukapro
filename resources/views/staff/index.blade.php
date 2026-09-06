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

{{-- Mobile cards --}}
<div class="space-y-3 md:hidden">
    @forelse($staff as $member)
        <x-card :padding="false" class="p-4">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="font-medium text-gray-900">{{ $member->name }}</p>
                    <p class="mt-1 text-xs capitalize text-gray-500">{{ $member->role }} · {{ $member->branch->name ?? '—' }}</p>
                    <p class="mt-1 truncate text-xs text-gray-500">{{ $member->email }}</p>
                </div>
                <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
                    @can('update', $member)
                        <a href="{{ tenant_route('tenant.staff.edit', ['employee' => $member]) }}"
                           class="inline-flex items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">Edit</a>
                    @endcan
                    @can('delete', $member)
                        <form method="POST" action="{{ tenant_route('tenant.staff.destroy', ['employee' => $member]) }}" onsubmit="return confirm('Remove this staff member?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="inline-flex w-full items-center justify-center rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100">Remove</button>
                        </form>
                    @endcan
                </div>
            </div>
        </x-card>
    @empty
        <x-card class="text-center text-sm text-gray-500">No staff yet.</x-card>
    @endforelse
</div>

<x-card :padding="false" class="hidden overflow-hidden md:block">
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
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $member->branch->name ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $member->email }}</td>
                        <td class="px-6 py-4 text-right text-sm">
                            <div class="inline-flex items-center gap-3">
                                @can('update', $member)
                                    <a href="{{ tenant_route('tenant.staff.edit', ['employee' => $member]) }}" class="font-medium text-indigo-600 hover:text-indigo-700">Edit</a>
                                @endcan
                                @can('delete', $member)
                                    <form method="POST" action="{{ tenant_route('tenant.staff.destroy', ['employee' => $member]) }}" class="inline" onsubmit="return confirm('Remove this staff member?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="font-medium text-red-600 hover:text-red-700">Remove</button>
                                    </form>
                                @endcan
                            </div>
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
