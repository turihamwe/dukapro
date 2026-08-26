@extends('layouts.admin')

@section('title', 'My Profile')

@section('content')
<x-page-header title="Profile" subtitle="View and update your account information" />

<x-card class="max-w-2xl">
    <form method="POST" action="{{ tenant_route('tenant.profile.update') }}" class="space-y-5">
        @csrf
        @method('PUT')

        <x-input type="text" name="name" label="Full name" value="{{ old('name', $user->name) }}" required />

        <div class="grid gap-4 sm:grid-cols-2">
            <x-input type="email" name="email" label="Email" value="{{ old('email', $user->email) }}" required />
            <x-input type="text" name="phone" label="Phone" value="{{ old('phone', $user->phone) }}" />
        </div>

        <div class="rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-600">
            Role: <span class="font-medium capitalize text-gray-900">{{ $user->role }}</span>
            @if($user->branch_name)
                · Branch: <span class="font-medium text-gray-900">{{ $user->branch_name }}</span>
            @endif
        </div>

        <div class="border-t border-gray-100 pt-5">
            <p class="mb-3 text-sm font-medium text-gray-700">Change password (optional)</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-password-input name="password" label="New password" />
                <x-password-input name="password_confirmation" label="Confirm password" />
            </div>
        </div>

        <x-button type="submit" variant="primary">Save profile</x-button>
    </form>
</x-card>
@endsection
