@php
    use App\Support\BillingMode;

    $checkoutByPlan = $checkoutByPlan ?? [];
    $selectedPlan = old('plan', \App\Support\SubscriptionPlan::defaultKey());
    $selectedCheckout = $checkoutByPlan[$selectedPlan] ?? null;
@endphp

@if(BillingMode::isAddons() && $selectedCheckout && ($selectedCheckout['module_amount'] ?? 0) > 0)
    <div class="mb-5 rounded-lg border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-900">
        <p class="font-semibold">Subscription breakdown</p>
        <ul class="mt-2 space-y-1 text-indigo-800">
            @foreach($selectedCheckout['line_items'] as $item)
                <li class="flex justify-between gap-4">
                    <span>{{ $item['label'] }}</span>
                    <span class="font-medium">{{ format_money($item['amount']) }}</span>
                </li>
            @endforeach
        </ul>
        <p class="mt-3 flex justify-between border-t border-indigo-200 pt-2 font-semibold">
            <span>Total due</span>
            <span>{{ format_money($selectedCheckout['total']) }}</span>
        </p>
        <p class="mt-2 text-xs text-indigo-700">Includes add-ons for enabled modules. Pay to unlock module features after your trial.</p>
    </div>
@elseif(BillingMode::isAddons() && ! empty($checkoutByPlan))
    <p class="mb-5 text-xs text-gray-500">Base platform subscription only — no billable modules are enabled, or this business is on grandfathered flat pricing.</p>
@endif

@include('subscription.partials.plan-options', [
    'selectedPlan' => $selectedPlan,
    'checkoutByPlan' => $checkoutByPlan,
])
