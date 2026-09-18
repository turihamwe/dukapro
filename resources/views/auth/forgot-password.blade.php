@extends('layouts.auth')

@section('title', 'Forgot password | ' . platform_brand('name'))

@section('content')
    @include('layouts.partials.auth-brand', [
        'subtitle' => 'Reset your ' . strtolower($portalLabel) . ' password',
    ])

    <x-card>
        <p class="mb-4 text-sm text-gray-600">
            Enter the email address on your account. We will send you a secure link to choose a new password.
        </p>

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="portal" value="{{ $portal }}">
            @if($business)
                <input type="hidden" name="business" value="{{ $business->portal_slug }}">
            @endif

            <x-input
                type="email"
                name="email"
                label="Email address"
                value="{{ old('email') }}"
                required
                autofocus
                large
                hint="Password reset links are sent by email only."
            />

            <x-button variant="primary" size="lg" type="submit" class="w-full">Send reset link</x-button>
        </form>

        <p class="mt-4 text-center text-xs text-gray-500">
            <a href="{{ $loginUrl }}" class="text-indigo-600 hover:text-indigo-700">Back to sign in</a>
        </p>
    </x-card>
@endsection
