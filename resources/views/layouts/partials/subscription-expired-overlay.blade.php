@if(show_subscription_expired_overlay())
@php
    $plans = \App\Support\SubscriptionPlan::all();
    $business = auth()->user()->business;
    $expiredMessage = $business->subscription_status === 'trial' && optional($business->trial_ends_at)->isPast()
        ? 'Your free trial has ended.'
        : ($business->subscription_status === 'active' && optional($business->subscription_ends_at)->isPast()
            ? 'Your paid subscription period has ended.'
            : 'Your subscription has expired.');
@endphp
<div class="absolute inset-0 z-50 flex items-start justify-center overflow-y-auto bg-gray-900/45 p-4 backdrop-blur-[2px] sm:items-center sm:p-6" role="dialog" aria-modal="true" aria-labelledby="subscription-expired-title">
    <div class="my-auto w-full max-w-lg rounded-2xl border border-red-200 bg-white p-6 shadow-2xl sm:p-8">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-red-100 text-2xl" aria-hidden="true">⏱</div>
        <h2 id="subscription-expired-title" class="mt-4 text-center text-xl font-bold text-gray-900">Subscription expired</h2>
        <p class="mt-2 text-center text-sm text-gray-600">{{ $expiredMessage }} Activate a plan to unlock POS, inventory, reports, and all store features again.</p>

        <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2">
            @foreach($plans as $plan)
                <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-center">
                    <p class="text-sm font-semibold text-gray-900">{{ $plan['label'] }}</p>
                    <p class="mt-1 text-lg font-bold text-indigo-700">{{ format_money($plan['amount']) }}</p>
                </div>
            @endforeach
        </div>

        <a href="{{ route('subscription.payment') }}"
           class="mt-6 flex w-full items-center justify-center rounded-xl bg-indigo-600 px-6 py-3.5 text-base font-bold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            Activate
        </a>

        <p class="mt-4 text-center text-xs text-gray-500">You can still view your dashboard. Choose a plan on the next screen to restore full access.</p>
    </div>
</div>
@endif
