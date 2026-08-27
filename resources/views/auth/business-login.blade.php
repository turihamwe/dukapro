@extends('layouts.app')

@section('title', 'Sign in — ' . $business->name)
@section('container_class', 'max-w-md')

@section('content')
@php
    $brandColor = $business->brand_color ?? '#4f46e5';
@endphp
<div class="flex min-h-[calc(100vh-4rem)] flex-col justify-center py-6">
    <div class="mb-6 text-center">
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
        @include('auth.partials.login-form', [
            'action' => route('business.login', ['portal' => $business->portal_slug]),
            'brandColor' => $brandColor,
        ])
        <p class="mt-4 text-center text-xs text-gray-500">
            <a href="{{ route('portal') }}" class="text-indigo-600 hover:text-indigo-700">Use a different business portal</a>
            ·
            <a href="{{ route('login') }}" class="text-indigo-600 hover:text-indigo-700">Universal sign in</a>
        </p>
    </x-card>
</div>
@endsection
