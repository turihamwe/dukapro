@extends('layouts.shareholder')

@section('title', 'Simulate Share Deposit')

@section('content')
<div class="mx-auto max-w-md">
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white p-6 text-center shadow-sm">
        <h1 class="text-lg font-semibold text-gray-900">Simulate share deposit</h1>
        <p class="mt-2 text-sm text-gray-500">Local sandbox only — simulates a YoPayments callback.</p>

        <dl class="mt-6 space-y-3 rounded-xl border border-gray-100 bg-gray-50 p-4 text-left text-sm">
            <div class="flex justify-between gap-4">
                <dt class="text-gray-500">Reference</dt>
                <dd><code class="rounded bg-gray-200 px-1.5 py-0.5 text-xs">{{ $payment->reference }}</code></dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-gray-500">Shares</dt>
                <dd class="font-medium text-gray-900">{{ number_format($shareholder->shares_owned, 2) }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-gray-500">Amount</dt>
                <dd class="font-medium text-gray-900">UGX {{ number_format($payment->amount, 0) }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-gray-500">Status</dt>
                <dd class="capitalize font-medium text-amber-700">{{ $payment->status }}</dd>
            </div>
        </dl>

        @if($payment->status === 'pending')
            <form method="POST" action="{{ route('shareholder.deposit.simulate.complete', $payment->reference) }}" class="mt-6">
                @csrf
                <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-3 text-sm font-semibold text-white hover:bg-emerald-500">
                    Simulate successful payment
                </button>
            </form>
        @else
            <p class="mt-6 text-sm text-emerald-700">This deposit has already been processed.</p>
        @endif

        <a href="{{ route('shareholder.dashboard') }}" class="mt-4 inline-block text-sm font-medium text-violet-600 hover:text-violet-800">Back to dashboard</a>
    </div>
</div>
@endsection
