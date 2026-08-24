@extends('layouts.app')

@section('title', 'Simulate Payment')
@section('container_class', 'max-w-4xl')

@section('content')
<div class="mx-auto max-w-md">
    <x-card class="text-center">
        <h1 class="text-lg font-semibold text-gray-900">Simulate Mobile Money Payment</h1>
        <p class="mt-2 text-sm text-gray-500">Local development only — simulates an STK push callback.</p>

        <dl class="mt-6 space-y-3 rounded-xl border border-gray-100 bg-gray-50 p-4 text-left text-sm">
            <div class="flex justify-between">
                <dt class="text-gray-500">Reference</dt>
                <dd><code class="rounded bg-gray-200 px-1.5 py-0.5 text-xs">{{ $payment->reference }}</code></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">Amount</dt>
                <dd class="font-medium text-gray-900">{{ format_money($payment->amount, auth()->user()->business) }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">Status</dt>
                <dd><x-badge color="amber">{{ ucfirst($payment->status) }}</x-badge></dd>
            </div>
        </dl>

        @if($payment->status === 'pending')
            <form method="POST" action="{{ route('subscription.simulate.complete', $payment->reference) }}" class="mt-6">
                @csrf
                <x-button variant="success" size="lg" type="submit">Simulate Successful Payment</x-button>
            </form>
        @else
            <x-alert type="success" class="mt-6">Payment already processed.</x-alert>
        @endif
    </x-card>
</div>
@endsection
