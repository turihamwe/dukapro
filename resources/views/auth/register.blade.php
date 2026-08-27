@extends('layouts.app')

@section('title', 'Register — ' . platform_brand('name'))
@section('container_class', 'max-w-md')
@section('body_class', 'register-page')

@section('content')
<div class="flex min-h-[calc(100vh-4rem)] flex-col justify-center py-4 sm:py-6">
    <div class="mb-4 text-center sm:mb-5">
        @if(platform_brand('logo_url'))
            <img src="{{ platform_brand('logo_url') }}" alt="{{ platform_brand('name') }}" class="mx-auto mb-3 h-12 w-auto max-w-[180px] object-contain sm:h-14">
        @else
            <div class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-600 text-base font-bold text-white shadow-lg shadow-indigo-600/30 sm:h-12 sm:w-12 sm:text-lg">D</div>
        @endif
        <h1 class="text-xl font-semibold tracking-tight text-gray-900 sm:text-2xl">{{ platform_brand('name') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ platform_brand('tagline') }}</p>
    </div>

    <x-card class="shadow-sm">
        <p class="mb-4 text-center text-sm font-medium text-gray-700">Start your free 30-day trial</p>
        <form method="POST" action="{{ route('register') }}" class="space-y-3 sm:space-y-4">
            @csrf
            <x-input type="text" name="business_name" label="Business name" value="{{ old('business_name') }}" required autofocus />
            <div class="grid gap-3 sm:grid-cols-2">
                <x-input type="text" name="name" label="Your name" value="{{ old('name') }}" required />
                <x-input type="email" name="email" label="Email" value="{{ old('email') }}" required />
            </div>
            <x-input type="tel" name="phone" label="Phone (optional)" value="{{ old('phone') }}" />
            <div class="grid gap-3 sm:grid-cols-2">
                <x-select name="currency_symbol" label="Currency">
                    @foreach(['UGX' => 'UGX', 'KES' => 'KES', 'TZS' => 'TZS', '/=' => '/='] as $value => $label)
                        <option value="{{ $value }}" {{ old('currency_symbol', 'UGX') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </x-select>
                <x-select name="currency_position" label="Position">
                    <option value="prefix" {{ old('currency_position', 'prefix') === 'prefix' ? 'selected' : '' }}>Prefix</option>
                    <option value="suffix" {{ old('currency_position') === 'suffix' ? 'selected' : '' }}>Suffix</option>
                </x-select>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <x-input type="password" name="password" label="Password" required />
                <x-input type="password" name="password_confirmation" label="Confirm" required />
            </div>
            <x-button variant="primary" size="lg" type="submit" class="w-full">Create account</x-button>
        </form>
        <p class="mt-4 text-center text-xs text-gray-500">
            Already have an account? <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-700">Sign in</a>
        </p>
    </x-card>
</div>
@endsection
