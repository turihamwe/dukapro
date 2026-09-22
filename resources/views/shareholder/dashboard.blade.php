@extends('layouts.shareholder')

@section('title', 'Shareholder Dashboard')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Shareholder Dashboard</h1>
    <p class="mt-1 text-sm text-gray-500">Track your investment and contract earnings progress</p>
</div>

@if(session('payment'))
    <x-alert type="info" class="mb-6">
        {{ session('payment')['message'] ?? 'Payment initiated.' }}
        @if(!empty(session('payment')['simulated_checkout_url']))
            <div class="mt-3">
                <a href="{{ session('payment')['simulated_checkout_url'] }}"
                   class="inline-flex rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500">
                    Complete simulated deposit
                </a>
            </div>
        @endif
    </x-alert>
@endif

@if($shareholder->status === 'pending')
    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
        Your application for <strong>{{ number_format($shareholder->shares_owned, 2) }}</strong> share(s) is pending.
        @if($canPayDeposit ?? false)
            Pay the deposit below via mobile money to activate your shares automatically after YoPayments confirms payment.
        @else
            Your deposit has been received and is being processed, or shares are awaiting activation.
        @endif
    </div>

    @if(($canPayDeposit ?? false) && ($depositReady ?? false))
        <div class="mb-8 overflow-hidden rounded-xl border border-violet-200 bg-white shadow-sm">
            <div class="border-b border-violet-100 bg-violet-50 px-6 py-4">
                <h2 class="font-semibold text-gray-900">Pay share deposit</h2>
                <p class="mt-1 text-xs text-gray-600">Amount due for your selected shares</p>
            </div>
            <div class="px-6 py-5">
                <p class="text-3xl font-bold text-violet-700">UGX {{ number_format($shareholder->capital_invested, 0) }}</p>
                <p class="mt-1 text-sm text-gray-500">{{ number_format($shareholder->shares_owned, 2) }} share(s) × UGX {{ number_format(config('shareholders.price_per_share'), 0) }}</p>

                @if($errors->has('deposit'))
                    <p class="mt-4 text-sm text-red-600">{{ $errors->first('deposit') }}</p>
                @endif

                <form method="POST" action="{{ route('shareholder.deposit.initiate') }}" class="mt-5 space-y-4">
                    @csrf
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Mobile money provider</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex cursor-pointer items-center justify-center rounded-lg border-2 border-gray-200 px-4 py-3 text-sm font-medium has-[:checked]:border-violet-600 has-[:checked]:bg-violet-50">
                                <input type="radio" name="provider" value="mtn" class="sr-only" @checked(old('provider', 'mtn') === 'mtn') required> MTN
                            </label>
                            <label class="flex cursor-pointer items-center justify-center rounded-lg border-2 border-gray-200 px-4 py-3 text-sm font-medium has-[:checked]:border-violet-600 has-[:checked]:bg-violet-50">
                                <input type="radio" name="provider" value="airtel" class="sr-only" @checked(old('provider') === 'airtel')> Airtel
                            </label>
                        </div>
                    </div>
                    <div>
                        <label for="deposit_phone" class="mb-1.5 block text-sm font-medium text-gray-700">Mobile money number</label>
                        <input type="tel" name="phone_number" id="deposit_phone" required
                               value="{{ old('phone_number', $shareholder->phone) }}"
                               placeholder="e.g. 256772123456"
                               class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500">
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-violet-600 px-4 py-3 text-sm font-semibold text-white hover:bg-violet-500">
                        Pay deposit via YoPayments
                    </button>
                </form>
                <p class="mt-3 text-xs text-gray-500">You will receive a PIN prompt on your phone. Shares activate automatically once payment succeeds.</p>
            </div>
        </div>
    @elseif($canPayDeposit ?? false)
        <div class="mb-6 rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700">
            Mobile money deposits are temporarily unavailable. Please contact support to complete your share payment.
        </div>
    @endif
@elseif($shareholder->status === 'rejected')
    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900">
        Your shareholder application was not approved.
    </div>
@elseif($shareholder->isContractComplete())
    <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
        Contract complete — you have reached the 3× earnings cap of <strong>UGX {{ number_format($earningsCap, 0) }}</strong>.
    </div>
@endif

<div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Registered</p>
        <p class="mt-2 text-lg font-bold text-gray-900">{{ optional($shareholder->registered_at ?? $shareholder->created_at)->format('M j, Y') }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Shares owned</p>
        <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($shareholder->shares_owned, 2) }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Capital invested</p>
        <p class="mt-2 text-2xl font-bold text-violet-600">UGX {{ number_format($shareholder->capital_invested, 0) }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Final earnings cap (3×)</p>
        <p class="mt-2 text-2xl font-bold text-emerald-600">UGX {{ number_format($earningsCap, 0) }}</p>
    </div>
</div>

<div class="mb-8 rounded-xl border border-gray-200 bg-white p-6">
    <div class="mb-3 flex items-center justify-between gap-4">
        <div>
            <h2 class="font-semibold text-gray-900">Earnings progress</h2>
            <p class="text-xs text-gray-500">Returns stop once you reach 3× your invested capital</p>
        </div>
        <p class="text-sm font-semibold text-violet-700">{{ number_format($progressPercent, 1) }}%</p>
    </div>
    <div class="h-3 overflow-hidden rounded-full bg-gray-100">
        <div class="h-full rounded-full bg-violet-600 transition-all" style="width: {{ min(100, $progressPercent) }}%"></div>
    </div>
    <div class="mt-4 grid gap-4 sm:grid-cols-3 text-sm">
        <div>
            <p class="text-xs uppercase text-gray-500">Earned to date</p>
            <p class="mt-1 font-semibold text-gray-900">UGX {{ number_format($shareholder->total_earnings, 0) }}</p>
        </div>
        <div>
            <p class="text-xs uppercase text-gray-500">Remaining capacity</p>
            <p class="mt-1 font-semibold text-amber-700">UGX {{ number_format($remainingCapacity, 0) }}</p>
        </div>
        <div>
            <p class="text-xs uppercase text-gray-500">Contract status</p>
            <p class="mt-1 font-semibold capitalize">{{ $shareholder->contract_completed ? 'Completed' : $shareholder->status }}</p>
        </div>
    </div>
</div>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
    <div class="border-b border-gray-200 px-6 py-4">
        <h2 class="font-semibold text-gray-900">Earnings history</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Amount</th>
                    <th class="px-4 py-3">Description</th>
                    <th class="px-4 py-3">Reference</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($shareholder->earnings as $earning)
                    <tr>
                        <td class="px-4 py-3 text-gray-600">{{ optional($earning->paid_at ?? $earning->created_at)->format('M j, Y') }}</td>
                        <td class="px-4 py-3 font-medium text-violet-700">UGX {{ number_format($earning->amount, 0) }}</td>
                        <td class="px-4 py-3">{{ $earning->description ?: '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $earning->reference ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-10 text-center text-gray-500">No earnings recorded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
