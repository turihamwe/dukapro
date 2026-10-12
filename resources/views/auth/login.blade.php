@extends('layouts.auth')

@section('title', 'Sign in | ' . platform_brand('name'))

@section('content')
    @include('layouts.partials.auth-brand', [
        'subtitle' => 'Sign in to your account',
    ])

    <x-card>
        @auth
            <div class="mb-4 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-950">
                You are signed in as <span class="font-semibold">{{ auth()->user()->email }}</span>
                @if(auth()->user()->business)
                    ({{ auth()->user()->business->name }})
                @endif
                . Submit the form below to switch accounts, or
                <a href="{{ route('logout.get') }}" class="font-semibold underline">sign out</a>.
            </div>
        @endauth
        @if($errors->has('portal_slug'))
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                {{ $errors->first('portal_slug') }}
            </div>
        @endif

        @include('auth.partials.login-form', [
            'action' => route('login'),
            'forgotPasswordUrl' => route('password.request', ['portal' => 'default']),
        ])
        @include('auth.partials.login-footer-global')
    </x-card>
@endsection
