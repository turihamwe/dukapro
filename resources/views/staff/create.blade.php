@extends('layouts.admin')

@section('title', 'Add Staff')

@section('content')
@php use App\Enums\UserRole; @endphp

<x-page-header title="Add Staff" subtitle="Create a team member account">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.staff.index') }}">All staff</x-button>
    </x-slot>
</x-page-header>

<x-card class="max-w-2xl">
    <form method="POST" action="{{ tenant_route('tenant.staff.store') }}" class="space-y-5">
        @csrf
            <x-input type="text" name="name" label="Full name" required />
            <x-input type="text" name="username" label="Username" value="{{ old('username') }}" required hint="Simple login name for this staff member." />
            <div class="grid gap-4 sm:grid-cols-2">
            <x-input type="email" name="email" label="Email" required />
            <x-input type="text" name="phone" label="Phone" />
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Role</label>
            <select name="role" id="staff-role" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @foreach($roles as $role)
                    <option value="{{ $role }}" @selected(old('role') === $role)>{{ UserRole::label($role) }}</option>
                @endforeach
            </select>
        </div>
        @if(auth()->user()->isBranchScoped())
            <p class="rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-600">This staff member will be assigned to <strong>{{ auth()->user()->branch->name ?? 'your branch' }}</strong>.</p>
        @else
            <div>
                <label for="branch_id" class="mb-1 block text-sm font-medium text-gray-700">Branch</label>
                <select name="branch_id" id="branch_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">Select branch…</option>
                    @foreach($branches as $branchId => $branchName)
                        <option value="{{ $branchId }}" @selected(old('branch_id') == $branchId)>{{ $branchName }}</option>
                    @endforeach
                </select>
                @error('branch_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @endif
        <div class="grid gap-4 sm:grid-cols-2">
            <x-password-input name="password" label="Password" required />
            <x-password-input name="password_confirmation" label="Confirm password" required />
        </div>
        <x-button variant="primary" type="submit">Save staff member</x-button>
    </form>
</x-card>
@endsection
