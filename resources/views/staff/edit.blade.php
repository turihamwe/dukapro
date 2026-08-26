@extends('layouts.admin')

@section('title', 'Edit Staff')

@section('content')
@php use App\Enums\UserRole; @endphp

<x-page-header :title="'Edit ' . $employee->name" subtitle="Update staff account">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.staff.index') }}">All staff</x-button>
    </x-slot>
</x-page-header>

<x-card class="max-w-2xl">
    <form method="POST" action="{{ tenant_route('tenant.staff.update', ['employee' => $employee]) }}" class="space-y-5">
        @csrf
        @method('PUT')
        <x-input type="text" name="name" label="Full name" value="{{ old('name', $employee->name) }}" required />
        <div class="grid gap-4 sm:grid-cols-2">
            <x-input type="email" name="email" label="Email" value="{{ old('email', $employee->email) }}" required />
            <x-input type="text" name="phone" label="Phone" value="{{ old('phone', $employee->phone) }}" />
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Role</label>
            <select name="role" id="staff-role" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @foreach($roles as $role)
                    <option value="{{ $role }}" @selected(old('role', $employee->role) === $role)>{{ UserRole::label($role) }}</option>
                @endforeach
            </select>
        </div>
        <div id="branch-field" class="{{ old('role', $employee->role) === UserRole::SUPERVISOR ? '' : 'hidden' }}">
            <x-input type="text" name="branch_name" label="Branch name" value="{{ old('branch_name', $employee->branch_name) }}" />
        </div>
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600" {{ old('is_active', $employee->is_active) ? 'checked' : '' }}>
            Active account
        </label>
        <div class="border-t border-gray-100 pt-4">
            <p class="mb-3 text-sm font-medium text-gray-700">Reset password (optional)</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-password-input name="password" label="New password" />
                <x-password-input name="password_confirmation" label="Confirm password" />
            </div>
        </div>
        <x-button variant="primary" type="submit">Save changes</x-button>
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
