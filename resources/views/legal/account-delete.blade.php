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
    <p class="mt-3 text-sm leading-relaxed text-slate-600">
        This permanently deletes the login and the personal details stored on it: name, username, email, phone, and password.
        You will not be able to sign in again.
    </p>

    <div class="mt-6 space-y-3 text-sm leading-relaxed text-slate-700">
        <p>Sale totals, stock, and other shop books stay with the shop. Your name is removed from those records. If the shop has switched on Uganda EFRIS, the filings already sent for that service stay with it.</p>
        <p>If you are the only owner of a shop, deleting the account also closes the shop, removes the shop’s email and phone, and erases customer and supplier names, phone numbers, emails, and addresses. Amounts owed and sale totals stay.</p>
        <p>If other staff still have logins, remove those accounts first. A shop cannot be closed while someone else can still sign in.</p>
        <p>Staff do not delete their own login from the POS. The owner who created it removes it.</p>
    </div>

    @if($refusal)
        <div class="mt-8 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ $refusal }}</div>
    @else
        <form method="POST" action="{{ route('account.delete.store') }}" class="mt-8 space-y-5">
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

            <label class="flex items-start gap-2 text-sm text-slate-700">
                <input type="checkbox" name="confirm" value="1" class="mt-1" {{ old('confirm') ? 'checked' : '' }} required>
                <span>I understand that my login and personal details will be permanently deleted.</span>
            </label>
            @error('confirm')<p class="text-sm text-red-600">{{ $message }}</p>@enderror

            <label class="flex items-start gap-2 text-sm text-slate-700">
                <input type="checkbox" name="confirm_shop" value="1" class="mt-1" {{ old('confirm_shop') || $closesShop ? 'checked' : '' }} @if($closesShop) required @endif>
                <span>If I am the only owner, close the shop and erase customer and supplier contact details. Sale and balance totals stay with the shop.</span>
            </label>
            @error('confirm_shop')<p class="text-sm text-red-600">{{ $message }}</p>@enderror

            <button type="submit" class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">
                Delete account permanently
            </button>
        </form>
    @endif
</main>
@endsection
