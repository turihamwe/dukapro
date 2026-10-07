@php
    $customerCreditBusinessEnabled = (bool) (($business->settings['customer_credit_mode'] ?? false));
@endphp

<div class="rounded-xl border border-amber-200 bg-amber-50 p-4 sm:p-5">
    <p class="text-sm font-semibold text-amber-950">Customer credit &amp; accounts receivable</p>
    <p class="mt-1 text-xs text-amber-900/80">Track credit sales from POS, customer balances, partial payments, and opening debts. Does not change how cash sales work.</p>
    <label class="mt-4 flex items-start gap-3">
        <input type="hidden" name="customer_credit_mode" value="0">
        <input type="checkbox" name="customer_credit_mode" value="1" class="mt-1 rounded border-amber-300 text-amber-600 focus:ring-amber-500"
               @checked(old('customer_credit_mode', $customerCreditBusinessEnabled))>
        <span class="text-sm text-amber-950">Enable customer credit for this business</span>
    </label>
</div>
