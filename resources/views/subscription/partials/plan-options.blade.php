@php
    $plans = \App\Support\SubscriptionPlan::all();
    $selectedPlan = old('plan', $selectedPlan ?? \App\Support\SubscriptionPlan::defaultKey());
    $inputName = $inputName ?? 'plan';
    $compact = $compact ?? false;
@endphp

<fieldset class="space-y-3">
    <legend class="mb-2 block text-sm font-medium text-gray-700">Choose a plan</legend>
    <div class="{{ $compact ? 'grid grid-cols-1 gap-3 sm:grid-cols-2' : 'grid grid-cols-1 gap-4 sm:grid-cols-2' }}">
        @foreach($plans as $plan)
            <label class="group relative flex cursor-pointer flex-col rounded-xl border-2 bg-white p-4 transition has-[:checked]:border-indigo-600 has-[:checked]:bg-indigo-50/60 {{ $selectedPlan === $plan['key'] ? 'border-indigo-600 bg-indigo-50/60' : 'border-gray-200 hover:border-indigo-200' }}">
                <input type="radio"
                       name="{{ $inputName }}"
                       value="{{ $plan['key'] }}"
                       class="peer sr-only"
                       data-plan-amount="{{ $plan['amount'] }}"
                       data-plan-label="{{ $plan['label'] }}"
                       @checked($selectedPlan === $plan['key'])
                       required>
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ $plan['label'] }}</p>
                        <p class="mt-0.5 text-xs text-gray-500">{{ $plan['description'] }}</p>
                    </div>
                    @if($plan['key'] === 'yearly')
                        <span class="shrink-0 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-700">Save</span>
                    @endif
                </div>
                <p class="mt-3 text-2xl font-bold text-gray-900">{{ format_money($plan['amount']) }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ $plan['days'] }} days of access</p>
            </label>
        @endforeach
    </div>
    @error('plan')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</fieldset>
