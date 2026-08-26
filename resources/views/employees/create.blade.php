@extends('layouts.admin')

@section('title', 'Add Employee')

@section('content')
@php
    use App\Enums\UserRole;
@endphp

<x-page-header title="Add Employee" subtitle="Create a staff account">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.employees.index') }}">All employees</x-button>
    </x-slot>
</x-page-header>

<x-card class="max-w-2xl">
    <form method="POST" action="{{ tenant_route('tenant.employees.store') }}" class="space-y-5">
        @csrf

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Full name</label>
            <input type="text" name="name" value="{{ old('name') }}" required
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Phone</label>
                <input type="text" name="phone" value="{{ old('phone') }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Role</label>
            <select name="role" id="employee-role" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                @foreach($roles as $role)
                    <option value="{{ $role }}" @selected(old('role') === $role)>{{ UserRole::label($role) }}</option>
                @endforeach
            </select>
        </div>

        <div id="branch-field" class="{{ old('role', $roles[0] ?? '') === UserRole::SUPERVISOR ? '' : 'hidden' }}">
            <label class="mb-1 block text-sm font-medium text-gray-700">Branch name</label>
            <input type="text" name="branch_name" value="{{ old('branch_name') }}"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                   placeholder="e.g. Westlands Branch">
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Password</label>
                <input type="password" name="password" required
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Confirm password</label>
                <input type="password" name="password_confirmation" required
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <x-button type="submit" variant="primary">Save employee</x-button>
            <x-button variant="secondary" href="{{ tenant_route('tenant.dashboard') }}">Cancel</x-button>
        </div>
    </form>
</x-card>
@endsection

@push('scripts')
<script>
(function () {
    const roleSelect = document.getElementById('employee-role');
    const branchField = document.getElementById('branch-field');
    if (!roleSelect || !branchField) return;
    roleSelect.addEventListener('change', function () {
        branchField.classList.toggle('hidden', roleSelect.value !== 'supervisor');
    });
})();
</script>
@endpush
