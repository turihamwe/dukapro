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
        <div id="branch-field" class="{{ old('role', $roles[0] ?? '') === UserRole::SUPERVISOR ? '' : 'hidden' }}">
            <x-input type="text" name="branch_name" label="Branch name" value="{{ old('branch_name') }}" placeholder="e.g. Westlands Branch" />
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-password-input name="password" label="Password" required />
            <x-password-input name="password_confirmation" label="Confirm password" required />
        </div>
        <x-button variant="primary" type="submit">Save staff member</x-button>
    </form>
</x-card>
@endsection

@push('scripts')
<script>
document.getElementById('staff-role')?.addEventListener('change', function () {
    document.getElementById('branch-field')?.classList.toggle('hidden', this.value !== 'supervisor');
});
</script>
@endpush
