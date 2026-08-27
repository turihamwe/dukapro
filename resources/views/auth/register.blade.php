@extends('layouts.app')

@section('title', 'Register — ' . platform_brand('name'))
@section('container_class', 'max-w-md')
@section('body_class', 'register-page')

@section('content')
<div class="flex min-h-[calc(100vh-4rem)] flex-col justify-center py-4 sm:py-6">
    <div class="mb-4 text-center sm:mb-5">
        @if(platform_brand('logo_url'))
            <img src="{{ platform_brand('logo_url') }}" alt="{{ platform_brand('name') }}" class="mx-auto mb-3 h-12 w-auto max-w-[180px] object-contain sm:h-14">
        @else
            <div class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-600 text-base font-bold text-white shadow-lg shadow-indigo-600/30 sm:h-12 sm:w-12 sm:text-lg">D</div>
        @endif
        <h1 class="text-xl font-semibold tracking-tight text-gray-900 sm:text-2xl">{{ platform_brand('name') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ platform_brand('tagline') }}</p>
    </div>

    <x-card class="shadow-sm">
        <form method="POST" action="{{ route('register') }}" class="space-y-3 sm:space-y-4" id="register-form">
            @csrf
            <x-input type="text" name="business_name" label="Business name" value="{{ old('business_name') }}" required autofocus />
            <x-input type="text" name="name" label="Your name" value="{{ old('name') }}" required />
            <div>
                <x-input type="text" name="username" id="username" label="Username" value="{{ old('username') }}" required
                         hint="Choose a simple, memorable login name (letters, numbers, dashes)." pattern="[A-Za-z0-9_-]+" />
                <p id="username-status" class="mt-1 hidden text-xs"></p>
            </div>
            <x-input type="email" name="email" label="Email" value="{{ old('email') }}" required />
            <x-input type="tel" name="phone" label="Phone (optional)" value="{{ old('phone') }}" />
            <p class="rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-600">Currency: <strong>UGX (Ugandan Shillings)</strong></p>
            <div class="grid gap-3 sm:grid-cols-2">
                <x-input type="password" name="password" label="Password" required />
                <x-input type="password" name="password_confirmation" label="Confirm" required />
            </div>
            <x-button variant="primary" size="lg" type="submit" class="w-full">Create account</x-button>
        </form>
        <p class="mt-4 text-center text-xs text-gray-500">
            Already have an account? <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-700">Sign in</a>
        </p>
        <p class="mb-4 mt-4 text-center text-sm font-medium text-gray-400">Got issues? Contact 0758-582681</p>
    </x-card>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var input = document.getElementById('username');
    var status = document.getElementById('username-status');
    var timer = null;
    if (!input || !status) return;

    input.addEventListener('input', function () {
        clearTimeout(timer);
        var value = input.value.trim();
        if (value.length < 3) {
            status.classList.add('hidden');
            return;
        }
        timer = setTimeout(function () {
            fetch(@json(route('register.check-username')) + '?username=' + encodeURIComponent(value), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                status.classList.remove('hidden');
                if (data.available) {
                    status.textContent = 'Username is available';
                    status.className = 'mt-1 text-xs text-emerald-600';
                } else {
                    status.textContent = data.message || 'Username is already taken';
                    status.className = 'mt-1 text-xs text-red-600';
                }
            })
            .catch(function () { status.classList.add('hidden'); });
        }, 350);
    });
})();
</script>
@endpush
