@extends('layouts.auth')

@section('title', 'Platform Admin Login')

@section('content')
    @include('layouts.partials.auth-brand', [
        'subtitle' => 'Super administrator sign in',
    ])

    <x-card>
        <form method="POST" action="{{ route('superadmin.login') }}" class="space-y-5">
            @csrf
            <x-input type="email" name="email" label="Email" value="{{ old('email') }}" required autofocus large />
            <x-input type="password" name="password" label="Password" required large />
            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="remember" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" {{ old('remember') ? 'checked' : '' }}>
                Remember me
            </label>
            <x-button variant="primary" size="lg" type="submit">Sign In</x-button>
        </form>
        <p class="mt-5 text-center text-xs text-gray-500">
            <a href="{{ route('portal') }}" class="text-indigo-600 hover:text-indigo-700">Business portal sign in</a>
        </p>
    </x-card>
@endsection
