@if(auth()->check() && auth()->user()->business && auth()->user()->can('manage-billing'))
    @php $business = auth()->user()->business; @endphp
    @if($business->subscription_status === 'trial')
        <div id="trial-banner" class="border-b border-amber-300 bg-gradient-to-r from-amber-100 via-orange-100 to-amber-100 px-4 py-3 text-sm text-amber-950 shadow-sm">
            <div class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
                <div>
                    <p class="font-semibold">
                        Subscription: {{ ucfirst($business->subscription_status) }}
                        @if($business->trial_ends_at)
                            · Trial ends {{ $business->trial_ends_at->format('M j, Y') }}
                        @endif
                    </p>
                    @if($business->trial_ends_at && $business->trial_ends_at->isFuture())
                        <p class="mt-0.5 text-xs text-amber-900/80">
                            Time remaining: <span id="trial-countdown" data-ends="{{ $business->trial_ends_at->toIso8601String() }}">—</span>
                        </p>
                    @endif
                </div>
                <button type="button" id="open-payment-modal" class="shrink-0 rounded-lg bg-amber-600 px-4 py-2 text-xs font-bold uppercase tracking-wide text-white shadow hover:bg-amber-700">
                    Activate
                </button>
            </div>
        </div>
    @endif
@endif
