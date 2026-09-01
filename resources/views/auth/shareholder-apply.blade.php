@extends('layouts.auth')

@section('title', 'Invest in ' . platform_brand('name'))

@section('content')
    @include('layouts.partials.auth-brand', [
        'subtitle' => 'Shareholder subscription',
    ])

    <x-card class="shadow-sm">
        <div class="mb-4 rounded-lg border border-violet-200 bg-violet-50 px-3 py-3 text-sm text-violet-900">
            <p><strong>{{ number_format($remainingShares, 2) }}</strong> of {{ number_format($totalShares, 0) }} shares available</p>
            <p class="mt-1 text-xs text-violet-700">Price: UGX {{ number_format($pricePerShare, 0) }} per share · Max {{ $maxShareholders }} shareholders · 3× earnings cap</p>
        </div>

        <form method="POST" action="{{ route('shareholder.apply.store') }}" class="space-y-3 sm:space-y-4">
            @csrf
            <x-input type="text" name="name" label="Full name" value="{{ old('name') }}" required autofocus />
            <x-input type="email" name="email" label="Email" value="{{ old('email') }}" required />
            <x-input type="tel" name="phone" label="Phone number" value="{{ old('phone') }}" required />
            <x-input type="text" name="national_id" label="National ID (optional)" value="{{ old('national_id') }}" />
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Number of shares <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" min="0.01" max="{{ $remainingShares }}" name="shares" value="{{ old('shares', 1) }}" required
                       class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <p class="mt-1 text-xs text-gray-500">Investment preview: UGX <span id="investment-preview">{{ number_format(old('shares', 1) * $pricePerShare, 0) }}</span></p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Notes (optional)</label>
                <textarea name="application_message" rows="3" class="w-full rounded-lg border-gray-300 text-sm">{{ old('application_message') }}</textarea>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <x-input type="password" name="password" label="Password" required />
                <x-input type="password" name="password_confirmation" label="Confirm password" required />
            </div>
            <x-button variant="primary" size="lg" type="submit" class="w-full">Submit application</x-button>
        </form>

        <p class="mt-4 text-center text-xs text-gray-500">
            Already a shareholder? <a href="{{ route('shareholder.login') }}" class="font-medium text-indigo-600 hover:text-indigo-700">Sign in</a>
        </p>
    </x-card>
@endsection

@push('scripts')
<script>
(function () {
    var sharesInput = document.querySelector('input[name="shares"]');
    var preview = document.getElementById('investment-preview');
    var price = {{ (int) $pricePerShare }};
    if (!sharesInput || !preview) return;
    sharesInput.addEventListener('input', function () {
        var shares = parseFloat(sharesInput.value) || 0;
        preview.textContent = (shares * price).toLocaleString('en-UG');
    });
})();
</script>
@endpush
