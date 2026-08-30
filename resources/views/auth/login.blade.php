@extends('layouts.auth')

@section('title', 'Sign in | ' . platform_brand('name'))

@section('content')
    @include('layouts.partials.auth-brand', [
        'subtitle' => 'Sign in to your account',
    ])

    <x-card>
        @if($errors->has('portal_slug'))
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                {{ $errors->first('portal_slug') }}
            </div>
        @endif

        @include('auth.partials.login-form', [
            'action' => route('login'),
        ])
        @include('auth.partials.login-footer-global')
    </x-card>
@endsection
