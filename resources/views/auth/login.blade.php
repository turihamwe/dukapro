@extends('layouts.app')

@section('title', 'Login - DukaPro')
@section('container_class', 'max-w-md')

@section('content')
<div class="py-8 sm:py-12">
    <div class="mb-8 text-center">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-600 text-lg font-bold text-white shadow-lg shadow-indigo-600/30">D</div>
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">Welcome to DukaPro</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Sign in to manage your store</p>
    </div>

    <x-card>
        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf
            <x-input type="email" name="email" label="Email" value="{{ old('email') }}" required autofocus large />
            <x-input type="password" name="password" label="Password" required large />
            <x-button variant="primary" size="lg" type="submit">Sign In</x-button>
        </form>
        <p class="mt-5 text-center text-xs text-gray-500 dark:text-gray-400">
            Demo: owner@dukapro.test / password · <a href="{{ route('register') }}" class="text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">Create account</a>
        </p>
    </x-card>
</div>
@endsection
