@php
    $paymentWalletsEnabled = (bool) (($business->settings['payment_wallets_mode'] ?? false));
@endphp

<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 sm:p-5">
    <p class="text-sm font-semibold text-emerald-950">Wallets / payment accounts</p>
    <p class="mt-1 text-xs text-emerald-900/80">Track internal liquidity (cash, mobile money, bank) and tie receivable and payable payments to accounts. Read-only balances in the app; not bank reconciliation.</p>
    <label class="mt-4 flex items-start gap-3">
        <input type="hidden" name="payment_wallets_mode" value="0">
        <input type="checkbox" name="payment_wallets_mode" value="1" class="mt-1 rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500"
               @checked(old('payment_wallets_mode', $paymentWalletsEnabled))>
        <span class="text-sm text-emerald-950">Enable wallets / payment accounts for this business</span>
    </label>
</div>
