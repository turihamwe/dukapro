@extends('layouts.auth')

@section('title', 'Become an Affiliate — ' . platform_brand('name'))

@section('content')
    @include('layouts.partials.auth-brand', [
        'subtitle' => 'Join our sales partner program',
    ])

    <x-card class="shadow-sm">
        <p class="mb-4 text-sm text-gray-600">
            Apply to become a {{ platform_brand('name') }} affiliate. Earn <strong>10% commission</strong> on every subscription payment from businesses you refer.
        </p>

        <form method="POST" action="{{ route('affiliate.apply.store') }}" class="space-y-3 sm:space-y-4">
            @csrf
            <x-input type="text" name="name" label="Full name" value="{{ old('name') }}" required autofocus />
            <x-input type="email" name="email" label="Email" value="{{ old('email') }}" required />
            <x-input type="tel" name="phone" label="Phone number" value="{{ old('phone') }}" required />
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Why do you want to join? (optional)</label>
                <textarea name="application_message" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('application_message') }}</textarea>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <x-input type="password" name="password" label="Password" required />
                <x-input type="password" name="password_confirmation" label="Confirm password" required />
            </div>
            <x-button variant="primary" size="lg" type="submit" class="w-full">Submit application</x-button>
        </form>

        <p class="mt-4 text-center text-xs text-gray-500">
            Already an affiliate? <a href="{{ route('affiliate.login') }}" class="font-medium text-indigo-600 hover:text-indigo-700">Sign in</a>
        </p>
        <p class="mt-2 text-center text-xs text-gray-500">
            Registering a business instead? <a href="{{ route('register') }}" class="font-medium text-indigo-600 hover:text-indigo-700">Business sign up</a>
        </p>
    </x-card>
@endsection
