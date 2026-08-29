@extends('layouts.app')

@section('title', 'Renew Subscription')
@section('container_class', 'max-w-4xl')

@section('content')
<div class="mx-auto max-w-lg">
    <x-page-header title="Subscription Required" subtitle="Renew to access inventory, POS, and reports" />

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

        <div class="mb-6 rounded-xl border border-indigo-100 bg-indigo-50 p-6 text-center">
            <p class="text-xs font-medium uppercase tracking-wide text-indigo-600">Monthly plan</p>
            <p class="mt-2 text-3xl font-semibold text-gray-900">{{ format_money($business->subscription_amount) }}</p>
        </div>

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
            <x-input type="tel" name="phone_number" label="Mobile Money Number" placeholder="e.g. 254712345678" value="{{ old('phone_number', $business->phone) }}" required large />
            <x-button variant="primary" size="lg" type="submit">Pay via Mobile Money</x-button>
        </form>
        @else
        <x-alert type="warning">Only the business owner can manage billing. Contact your owner to renew.</x-alert>
        @endcan

        <p class="mt-5 text-center text-xs text-gray-500">
            You will receive an STK push on your phone. Payment activates instantly via webhook.
        </p>
    </x-card>
</div>
@endsection
