@extends('layouts.app')

@section('title', 'Register - DukaPro')
@section('container_class', 'max-w-md')

@section('content')
<div class="py-8 sm:py-12">
    <div class="mb-8 text-center">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-600 text-lg font-bold text-white shadow-lg shadow-indigo-600/30">D</div>
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">Start your free trial</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">30 days free — no credit card required</p>
    </div>

    <x-card>
        <form method="POST" action="{{ route('register') }}" class="space-y-5">
            @csrf
            <x-input type="text" name="business_name" label="Business Name" value="{{ old('business_name') }}" required autofocus />
            <x-input type="text" name="name" label="Your Name" value="{{ old('name') }}" required />
            <x-input type="email" name="email" label="Email" value="{{ old('email') }}" required />
            <x-input type="tel" name="phone" label="Phone (optional)" value="{{ old('phone') }}" />

            <div class="grid gap-5 sm:grid-cols-2">
                <x-select name="currency_symbol" label="Currency Symbol">
                    @foreach(['UGX' => 'UGX (Uganda)', 'KES' => 'KES (Kenya)', 'TZS' => 'TZS (Tanzania)', '/=' => '/= (Shilling suffix)'] as $value => $label)
                        <option value="{{ $value }}" {{ old('currency_symbol', 'UGX') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </x-select>
                <x-select name="currency_position" label="Symbol Position">
                    <option value="prefix" {{ old('currency_position', 'prefix') === 'prefix' ? 'selected' : '' }}>Prefix (UGX 100,000)</option>
                    <option value="suffix" {{ old('currency_position') === 'suffix' ? 'selected' : '' }}>Suffix (100,000/=)</option>
                </x-select>
            </div>

            <x-input type="password" name="password" label="Password" required />
            <x-input type="password" name="password_confirmation" label="Confirm Password" required />
            <x-button variant="primary" size="lg" type="submit">Create Account</x-button>
        </form>
        <p class="mt-5 text-center text-sm text-gray-500 dark:text-gray-400">
            Already have an account? <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">Sign in</a>
        </p>
    </x-card>
</div>
@endsection
