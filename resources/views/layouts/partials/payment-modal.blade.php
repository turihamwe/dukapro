@can('manage-billing')
@php
    $defaultPlan = \App\Support\SubscriptionPlan::find(\App\Support\SubscriptionPlan::defaultKey());
@endphp
<div id="payment-modal" class="app-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="payment-modal-title">
    <div class="app-modal-panel">
        <div class="app-modal-header">
            <div>
                <h2 id="payment-modal-title" class="text-lg font-semibold text-gray-900">Activate Subscription</h2>
                <p class="mt-1 text-sm text-gray-500">Choose a plan and pay via mobile money.</p>
            </div>
            <button type="button" id="close-payment-modal" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600" aria-label="Close">&times;</button>
        </div>

        <form id="payment-form" method="POST" action="{{ route('subscription.initiate') }}" class="flex min-h-0 flex-1 flex-col">
            <div class="app-modal-body space-y-4">
                @csrf

                @include('subscription.partials.plan-options', ['compact' => true])

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Mobile money provider</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex cursor-pointer items-center justify-center rounded-lg border-2 border-gray-200 px-4 py-3 text-sm font-medium has-[:checked]:border-indigo-600 has-[:checked]:bg-indigo-50">
                            <input type="radio" name="provider" value="mtn" class="sr-only" checked> MTN
                        </label>
                        <label class="flex cursor-pointer items-center justify-center rounded-lg border-2 border-gray-200 px-4 py-3 text-sm font-medium has-[:checked]:border-indigo-600 has-[:checked]:bg-indigo-50">
                            <input type="radio" name="provider" value="airtel" class="sr-only"> Airtel
                        </label>
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Phone number</label>
                    <input type="tel" name="phone_number" required placeholder="e.g. 256772123456"
                           value="{{ auth()->user()->business->phone ?? '' }}"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <p id="payment-plan-summary" class="text-xs text-gray-500">
                    Amount: {{ format_money($defaultPlan['amount']) }} ({{ $defaultPlan['label'] }}) · You will receive a PIN prompt on your phone.
                </p>
                <div id="payment-status" class="hidden rounded-lg border px-3 py-2 text-sm"></div>
            </div>
            <div class="app-modal-footer">
                <button type="submit" id="payment-submit" class="w-full rounded-lg bg-indigo-600 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 sm:w-auto sm:min-w-[12rem]">
                    Send payment request
                </button>
            </div>
        </form>
    </div>
</div>
@endcan
