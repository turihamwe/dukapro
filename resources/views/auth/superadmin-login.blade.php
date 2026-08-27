@extends('layouts.app')

@section('title', 'Platform Admin Login')
@section('container_class', 'max-w-md')

@section('content')
<div class="py-8 sm:py-12">
    <div class="mb-8 text-center">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gray-900 text-lg font-bold text-white">SA</div>
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">DukaPro Platform Admin</h1>
        <p class="mt-1 text-sm text-gray-500">Super administrator sign in</p>
    </div>

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
</div>
@endsection
