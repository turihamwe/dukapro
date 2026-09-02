@extends('layouts.admin')

@section('title', 'Branches')

@section('content')
<x-page-header title="Branches" subtitle="{{ $branches->count() }} locations">
    <x-slot name="actions">
        <x-button variant="primary" size="sm" href="{{ tenant_route('tenant.branches.create') }}">+ Add Branch</x-button>
    </x-slot>
</x-page-header>

<x-card :padding="false" class="overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Branch</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Staff</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse($branches as $branch)
                    <tr>
                        <td class="px-6 py-4">
                            <p class="text-sm font-medium text-gray-900">{{ $branch->name }}</p>
                            @if($branch->is_default)
                                <p class="text-xs text-indigo-600">Default branch</p>
                            @endif
                            @if($branch->address)
                                <p class="text-xs text-gray-500">{{ $branch->address }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $branch->phone ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $branch->users_count }}</td>
                        <td class="px-6 py-4 text-sm">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $branch->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $branch->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right text-sm">
                            <a href="{{ tenant_route('tenant.branches.edit', ['branch' => $branch]) }}" class="font-medium text-indigo-600 hover:text-indigo-700">Edit</a>
                            @if(! $branch->is_default && $branch->users_count === 0)
                                <form method="POST" action="{{ tenant_route('tenant.branches.destroy', ['branch' => $branch]) }}" class="ml-3 inline" onsubmit="return confirm('Remove this branch?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-medium text-red-600 hover:text-red-700">Remove</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">No branches yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>
@endsection
