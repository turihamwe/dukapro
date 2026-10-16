@extends('layouts.landing')

@section('title', 'Account deleted — ' . platform_brand('name'))

@section('content')
<main class="mx-auto max-w-3xl px-4 py-16 sm:px-6">
    <h1 class="text-3xl font-bold tracking-tight text-ink-900">Your account has been deleted</h1>
    <p class="mt-4 text-sm leading-relaxed text-slate-600">
        The login and the personal details on it have been removed. You cannot sign in with that email or username again.
    </p>
    <p class="mt-6">
        <a href="{{ route('home') }}" class="text-sm font-medium text-brand-700 hover:text-brand-600">Back to home</a>
    </p>
</main>
@endsection
