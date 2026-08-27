@extends('layouts.app')

@section('title', 'Sign in — ' . platform_brand('name'))
@section('container_class', 'max-w-md')

@section('content')
<div class="flex min-h-[calc(100vh-4rem)] flex-col justify-center py-6">
    <div class="mb-6 text-center">
        @if(platform_brand('logo_url'))
            <img src="{{ platform_brand('logo_url') }}" alt="{{ platform_brand('name') }}" class="mx-auto mb-3 h-12 w-auto max-w-[180px] object-contain">
        @else
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-600 text-lg font-bold text-white shadow-lg shadow-indigo-600/30">D</div>
        @endif
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">{{ platform_brand('name') }}</h1>
        <p class="mt-1 text-sm text-gray-500">Sign in with your username or email</p>
    </div>

    <x-card>
        @if($errors->has('portal_slug'))
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                {{ $errors->first('portal_slug') }}
            </div>
        @endif

        @include('auth.partials.login-form', [
            'action' => route('login'),
            'portalHint' => 'Works for all businesses — no portal link required.',
        ])
        @include('auth.partials.login-footer-global')
    </x-card>
</div>
@endsection
