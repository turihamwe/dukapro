@extends('layouts.affiliate')

@section('title', 'Affiliate Dashboard')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Affiliate Dashboard</h1>
    <p class="mt-1 text-sm text-gray-500">Track your referrals and commission earnings</p>
</div>

@if($affiliate->status === 'pending')
    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
        Your application is <strong>pending approval</strong>. You will receive your referral link once a platform administrator approves your account.
    </div>
@elseif($affiliate->status === 'rejected')
    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900">
        Your affiliate application was not approved. Contact support if you believe this is an error.
    </div>
@elseif(! $affiliate->is_active)
    <div class="mb-6 rounded-xl border border-gray-200 bg-gray-100 p-4 text-sm text-gray-700">
        Your affiliate account is currently inactive. Referral links are disabled until reactivated.
    </div>
@else
    <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-emerald-700">Your referral link</p>
        <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center">
            <code class="flex-1 break-all rounded-lg bg-white px-3 py-2 text-sm text-emerald-900">{{ $affiliate->referralUrl() }}</code>
            <button type="button" onclick="navigator.clipboard.writeText(@json($affiliate->referralUrl()))"
                    class="shrink-0 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                Copy link
            </button>
        </div>
        <p class="mt-2 text-xs text-emerald-700">Referral code: <strong>{{ $affiliate->code }}</strong> · Commission rate: {{ number_format($affiliate->commission_rate * 100, 0) }}%</p>
    </div>
@endif

<div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Businesses onboarded</p>
        <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($stats['onboarded_count']) }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Total commission</p>
        <p class="mt-2 text-3xl font-bold text-violet-600">UGX {{ number_format($stats['total_commission'], 0) }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Pending payout</p>
        <p class="mt-2 text-3xl font-bold text-amber-600">UGX {{ number_format($stats['pending_commission'], 0) }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Paid out</p>
        <p class="mt-2 text-3xl font-bold text-emerald-600">UGX {{ number_format($stats['paid_commission'], 0) }}</p>
    </div>
</div>

<div class="mb-8 overflow-hidden rounded-xl border border-gray-200 bg-white">
    <div class="border-b border-gray-200 px-6 py-4">
        <h2 class="font-semibold text-gray-900">Referred businesses</h2>
        <p class="mt-1 text-xs text-gray-500">Contact details for businesses you onboarded</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">Business</th>
                    <th class="px-4 py-3">Phone</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Joined</th>
                    <th class="px-4 py-3">Subscription</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($referredBusinesses as $business)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $business->name }}</td>
                        <td class="px-4 py-3">
                            @if($business->phone)
                                <a href="tel:{{ $business->phone }}" class="text-violet-600 hover:text-violet-800">{{ $business->phone }}</a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $business->email ?: '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $business->created_at->format('M j, Y') }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium capitalize text-gray-700">{{ $business->subscription_status }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-gray-500">No businesses referred yet. Share your link to get started.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
    <div class="border-b border-gray-200 px-6 py-4">
        <h2 class="font-semibold text-gray-900">Commission history</h2>
        <p class="mt-1 text-xs text-gray-500">10% earnings when referred businesses pay for subscriptions</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Business</th>
                    <th class="px-4 py-3">Payment</th>
                    <th class="px-4 py-3">Commission</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($commissions as $commission)
                    <tr>
                        <td class="px-4 py-3 text-gray-600">{{ $commission->created_at->format('M j, Y') }}</td>
                        <td class="px-4 py-3">{{ optional($commission->business)->name ?? '—' }}</td>
                        <td class="px-4 py-3">UGX {{ number_format($commission->payment_amount, 0) }}</td>
                        <td class="px-4 py-3 font-medium text-violet-700">UGX {{ number_format($commission->commission_amount, 0) }}</td>
                        <td class="px-4 py-3 capitalize">{{ $commission->status }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-gray-500">No commission records yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
