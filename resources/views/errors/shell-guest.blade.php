@extends('layouts.auth')

@section('title')
    @yield('title')
@endsection

@section('content')
    @include('layouts.partials.auth-brand', [
        'subtitle' => trim($__env->yieldContent('guest_subtitle')) ?: 'We could not complete that request',
    ])

    <x-card class="shadow-sm">
        @include('errors.partials.content')
    </x-card>

    @include('auth.partials.login-footer-global')
@endsection
