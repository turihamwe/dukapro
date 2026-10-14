@extends('layouts.admin')

@section('title', 'Branches')

@section('content')
@php
    $pageTitle = $canManageBranches ? 'Branches' : 'Branch context';
    $pageSubtitle = $canManageBranches
        ? $branches->count() . ' locations'
        : 'Choose which branch to operate as';
@endphp

<x-page-header :title="$pageTitle" :subtitle="$pageSubtitle">
    <x-slot name="actions">
        @if($canManageBranches)
            <x-button variant="primary" size="sm" href="{{ tenant_route('tenant.branches.create') }}">+ Add Branch</x-button>
        @endif
    </x-slot>
</x-page-header>

@if($canImpersonateBranches)
    <x-card class="mb-6 border-indigo-200 bg-indigo-50/80">
        @if($activeBranch)
            <p class="text-sm text-indigo-950">
                You are operating as <strong>{{ $activeBranch->name }}</strong>. POS, inventory, expenses, and reports are scoped to this branch.
            </p>
            <form method="POST" action="{{ tenant_route('tenant.branch-context.clear') }}" class="mt-3">
                @csrf
                <x-button variant="secondary" size="sm" type="submit">Exit branch view (all branches)</x-button>
            </form>
        @else
            <p class="text-sm text-indigo-950">
                You are in the <strong>all-branch</strong> overview. Pick a branch below when you need to work as that location — there is no separate “main branch” mode unless you select your default branch explicitly.
            </p>
        @endif
    </x-card>
@endif

<x-card :padding="false" class="overflow-hidden">
    <x-sortable-table class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <x-sortable-th column="name" class="px-6 py-3">Branch</x-sortable-th>
                    <x-sortable-th column="contact" class="px-6 py-3">Contact</x-sortable-th>
                    <x-sortable-th column="staff" class="px-6 py-3">Staff</x-sortable-th>
                    <x-sortable-th column="status" class="px-6 py-3">Status</x-sortable-th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody x-ref="tbody" class="divide-y divide-gray-100 bg-white">
                @forelse($branches as $branch)
                    <tr data-sortable-row
                        data-sort-name="{{ strtolower($branch->name) }}"
                        data-sort-contact="{{ strtolower($branch->phone ?? '') }}"
                        data-sort-staff="{{ (int) $branch->users_count }}"
                        data-sort-status="{{ $branch->is_active ? 1 : 0 }}">
                        <td class="px-6 py-4">
                            <p class="text-sm font-medium text-gray-900">{{ $branch->name }}</p>
                            @if($branch->is_default)
                                <p class="text-xs text-indigo-600">Default branch</p>
                            @endif
                            @if($activeBranchId && (int) $activeBranchId === (int) $branch->id)
                                <p class="text-xs font-medium text-indigo-700">Current operating context</p>
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
                            @if($canImpersonateBranches && $branch->is_active)
                                @if((int) $activeBranchId === (int) $branch->id)
                                    <span class="text-xs font-medium text-indigo-700">Operating here</span>
                                @else
                                    <form method="POST" action="{{ tenant_route('tenant.branch-context.switch') }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                                        <button type="submit" class="font-medium text-indigo-600 hover:text-indigo-800">Operate as this branch</button>
                                    </form>
                                @endif
                            @endif
                            @if($canManageBranches)
                                <a href="{{ tenant_route('tenant.branches.edit', ['branch' => $branch]) }}" class="ml-3 font-medium text-indigo-600 hover:text-indigo-700">Edit</a>
                                @if(! $branch->is_default && $branch->users_count === 0)
                                    <x-delete-confirm-button
                                        :action="tenant_route('tenant.branches.destroy', ['branch' => $branch])"
                                        message="Are you sure you want to delete this item?"
                                        detail="Remove this branch?"
                                        label="Remove"
                                        class="ml-3"
                                    />
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr data-sort-empty="1">
                        <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">No branches yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-sortable-table>
</x-card>
@endsection
