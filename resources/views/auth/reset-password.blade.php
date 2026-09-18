@extends('layouts.auth')

@section('title', 'Choose new password | ' . platform_brand('name'))

@section('content')
    @include('layouts.partials.auth-brand', [
        'subtitle' => 'Set a new password for ' . strtolower($portalLabel),
    ])

    <x-card>
        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="portal" value="{{ $portal }}">
            @if($business)
                <input type="hidden" name="business" value="{{ $business->portal_slug }}">
            @endif

            <x-input
                type="email"
                name="email"
                label="Email address"
                value="{{ old('email', $email) }}"
                required
                autofocus
                large
            />
            <x-input type="password" name="password" label="New password" required large />
            <x-input type="password" name="password_confirmation" label="Confirm new password" required large />

            <x-button variant="primary" size="lg" type="submit" class="w-full">Update password</x-button>
        </form>

        <p class="mt-4 text-center text-xs text-gray-500">
            <a href="{{ $loginUrl }}" class="text-indigo-600 hover:text-indigo-700">Back to sign in</a>
        </p>
    </x-card>
@endsection
