@extends('layouts.auth')

@section('title', 'Affiliate Sign In — ' . platform_brand('name'))

@section('content')
    @include('layouts.partials.auth-brand', [
        'subtitle' => 'Affiliate partner sign in',
    ])

    <x-card class="shadow-sm">
        <form method="POST" action="{{ route('affiliate.login.store') }}" class="space-y-4">
            @csrf
            <x-input type="text" name="login" label="Username or email" value="{{ old('login') }}" required autofocus
                     hint="Use your username or email address." />
            <x-input type="password" name="password" label="Password" required />
            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="remember" value="1" class="rounded border-gray-300">
                Remember me
            </label>
            <x-button variant="primary" size="lg" type="submit" class="w-full">Sign in</x-button>
        </form>

        <p class="mt-4 text-center text-xs text-gray-500">
            Want to join the program? <a href="{{ route('affiliate.apply') }}" class="font-medium text-indigo-600 hover:text-indigo-700">Apply as affiliate</a>
        </p>
    </x-card>
@endsection
