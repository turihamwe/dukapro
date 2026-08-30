@extends('layouts.app')

@section('title', 'Renew Subscription')
@section('container_class', 'max-w-4xl')

@section('content')
<div class="mx-auto max-w-lg">
    <x-page-header title="Subscription Required" subtitle="Choose a plan and pay via mobile money to activate or renew" />

    <x-card>
        <p class="mb-6 text-sm text-gray-600">
            @if($business->subscription_status === 'trial' && optional($business->trial_ends_at)->isPast())
                Your free trial ended on {{ $business->trial_ends_at->format('M d, Y') }}.
            @elseif($business->subscription_status === 'inactive')
                Your subscription is inactive.
            @else
                Your subscription has expired.
            @endif
        </p>

        @if(session('payment'))
            <x-alert type="info" class="mb-6">
                {{ session('payment')['message'] }}
                @if(!empty(session('payment')['simulated_checkout_url']))
                    <div class="mt-3">
                        <x-button variant="secondary" size="sm" href="{{ session('payment')['simulated_checkout_url'] }}">Complete Simulated Payment</x-button>
                    </div>
                @endif
            </x-alert>
        @endif

        @can('manage-billing')
        <form method="POST" action="{{ route('subscription.initiate') }}" class="space-y-5">
            @csrf

            @include('subscription.partials.plan-options')

            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Mobile money provider</label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex cursor-pointer items-center justify-center rounded-lg border-2 border-gray-200 px-4 py-3 text-sm font-medium has-[:checked]:border-indigo-600 has-[:checked]:bg-indigo-50">
                        <input type="radio" name="provider" value="mtn" class="sr-only" @checked(old('provider', 'mtn') === 'mtn') required> MTN
                    </label>
                    <label class="flex cursor-pointer items-center justify-center rounded-lg border-2 border-gray-200 px-4 py-3 text-sm font-medium has-[:checked]:border-indigo-600 has-[:checked]:bg-indigo-50">
                        <input type="radio" name="provider" value="airtel" class="sr-only" @checked(old('provider') === 'airtel')> Airtel
                    </label>
                </div>
                @error('provider')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <x-input type="tel" name="phone_number" label="Mobile Money Number" placeholder="e.g. 256772123456" value="{{ old('phone_number', $business->phone) }}" required large />

            <x-button variant="primary" size="lg" type="submit">Pay via Mobile Money</x-button>
        </form>
        @else
        <x-alert type="warning">Only the business owner can manage billing. Contact your owner to renew.</x-alert>
        @endcan

        <p class="mt-5 text-center text-xs text-gray-500">
            You will receive a PIN prompt on your phone. Payment activates instantly after approval.
        </p>
    </x-card>
</div>
@endsection
