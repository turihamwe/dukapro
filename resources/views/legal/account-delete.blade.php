@extends('layouts.landing')

@section('title', 'Delete your account — ' . platform_brand('name'))

@section('content')
@php
    $brand = platform_brand('name');
@endphp

<header class="border-b border-slate-200 bg-white">
    <div class="mx-auto flex max-w-3xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
        <x-dukapro-logo size="sm" />
        <a href="{{ route('privacy') }}" class="text-sm font-medium text-slate-600 hover:text-ink-900">Privacy policy</a>
    </div>
</header>

<main class="mx-auto max-w-3xl px-4 py-10 sm:px-6 sm:py-14">
    <p class="text-sm font-medium text-brand-700">{{ $brand }}</p>
    <h1 class="mt-2 text-3xl font-bold tracking-tight text-ink-900">Delete your account</h1>

    @if($refusal)
        <div class="mt-8 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ $refusal }}</div>
    @else
        <form method="POST" action="{{ route('account.delete.store') }}" class="mt-8 space-y-5"
              x-data="{ reason: {{ json_encode(old('reason', '')) }} }">
            @csrf

            @if(! $user)
                <div>
                    <label for="login" class="mb-1 block text-sm font-medium text-ink-900">Email or username</label>
                    <input id="login" name="login" type="text" value="{{ old('login') }}" required autocomplete="username"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                    @error('login')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            @else
                <p class="text-sm text-slate-600">Signed in as <span class="font-medium text-ink-900">{{ $user->name }}</span> ({{ $user->email }}).</p>
                @error('login')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            @endif

            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-ink-900">Password</label>
                <input id="password" name="password" type="password" required autocomplete="current-password"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="reason" class="mb-1 block text-sm font-medium text-ink-900">Why are you leaving?</label>
                <select id="reason" name="reason" x-model="reason" required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                    <option value="">Select a reason</option>
                    @foreach($reasons as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('reason')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div x-show="reason === 'other'" x-cloak>
                <label for="details" class="mb-1 block text-sm font-medium text-ink-900">Please tell us more</label>
                <textarea id="details" name="details" rows="4" maxlength="2000"
                          class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">{{ old('details') }}</textarea>
                @error('details')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                Submit
            </button>
        </form>
    @endif
</main>
<style>[x-cloak]{display:none !important}</style>
@endsection
