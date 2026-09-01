@extends('layouts.auth')

@section('title', 'Become an Affiliate — ' . platform_brand('name'))

@section('content')
    @include('layouts.partials.auth-brand', [
        'subtitle' => 'Join our sales partner program',
    ])

    <x-card class="shadow-sm">
        <p class="mb-4 text-sm text-gray-600">
            Apply to become a {{ platform_brand('name') }} affiliate. Earn <strong>10% commission</strong> on every subscription payment from businesses you refer.
        </p>

        <form method="POST" action="{{ route('affiliate.apply.store') }}" class="space-y-3 sm:space-y-4" id="affiliate-apply-form">
            @csrf
            <x-input type="text" name="name" label="Full name" value="{{ old('name') }}" required autofocus />
            <div>
                <x-input type="text" name="username" id="username" label="Username" value="{{ old('username') }}" required
                         hint="Choose a simple, memorable login name (letters, numbers, dashes)." pattern="[A-Za-z0-9_-]+" />
                <p id="username-status" class="mt-1 hidden text-xs"></p>
            </div>
            <x-input type="email" name="email" label="Email" value="{{ old('email') }}" required />
            <x-input type="tel" name="phone" label="Phone number" value="{{ old('phone') }}" required />
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Why do you want to join? (optional)</label>
                <textarea name="application_message" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('application_message') }}</textarea>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <x-input type="password" name="password" label="Password" required />
                <x-input type="password" name="password_confirmation" label="Confirm password" required />
            </div>
            <x-button variant="primary" size="lg" type="submit" class="w-full">Submit application</x-button>
        </form>

        <p class="mt-4 text-center text-xs text-gray-500">
            Already an affiliate? <a href="{{ route('affiliate.login') }}" class="font-medium text-indigo-600 hover:text-indigo-700">Sign in</a>
        </p>
        <p class="mt-2 text-center text-xs text-gray-500">
            Registering a business instead? <a href="{{ route('register') }}" class="font-medium text-indigo-600 hover:text-indigo-700">Business sign up</a>
        </p>
    </x-card>
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
