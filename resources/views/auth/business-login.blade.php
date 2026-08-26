@extends('layouts.app')

@section('title', 'Sign in — ' . $business->name)
@section('container_class', 'max-w-md')

@section('content')
@php
    $brandColor = $business->brand_color ?? '#4f46e5';
@endphp
<div class="py-8 sm:py-12">
    <div class="mb-8 text-center">
        @if($business->logoUrl())
            <img src="{{ $business->logoUrl() }}" alt="{{ $business->name }}" class="mx-auto mb-4 h-16 w-auto max-w-[200px] object-contain">
        @else
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-xl text-lg font-bold text-white shadow-lg" style="background-color: {{ $brandColor }}">
                {{ strtoupper(substr($business->name, 0, 1)) }}
            </div>
        @endif
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">{{ $business->name }}</h1>
        <p class="mt-1 text-sm text-gray-500">Secure staff sign in</p>
    </div>

    <x-card>
        <form method="POST" action="{{ route('business.login', ['portal' => $business->portal_slug]) }}" class="space-y-5">
            @csrf
            <x-input type="email" name="email" label="Email" value="{{ old('email') }}" required autofocus large />
            <x-input type="password" name="password" label="Password" required large />
            <x-button variant="primary" size="lg" type="submit" class="w-full" style="background-color: {{ $brandColor }}">Sign In</x-button>
        </form>
        <p class="mt-5 text-center text-xs text-gray-500">
            <a href="{{ route('portal') }}" class="text-indigo-600 hover:text-indigo-700">Use a different business portal</a>
        </p>
    </x-card>
</div>
@endsection
