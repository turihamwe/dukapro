@php
    use App\Support\PaymentWalletMode;
    use App\Support\WalletLedgerAdjustmentCompliance;

    $adminUnlocked = WalletLedgerAdjustmentCompliance::isAdminUnlocked($business);
    $walletsOn = PaymentWalletMode::active($business);
@endphp

<div class="mb-6 rounded-xl border border-sky-200 bg-sky-50/60 p-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-gray-900">Wallet ledger corrections</p>
            <p class="mt-1 text-xs text-gray-600">
                Makes wallet history corrections available to the business owner. The owner can optionally grant managers the same ability under Staff permissions.
                Other tenants never see this unless you unlock them here.
            </p>
        </div>
        @if($adminUnlocked)
            <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-800">Unlocked</span>
        @else
            <span class="rounded-full bg-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-700">Locked</span>
        @endif
    </div>

    <dl class="mt-4 grid gap-3 sm:grid-cols-2 text-sm">
        <div>
            <dt class="text-xs uppercase text-gray-500">Payment wallets</dt>
            <dd class="mt-1 font-medium text-gray-900">{{ $walletsOn ? 'Enabled' : 'Off — enable in owner settings first' }}</dd>
        </div>
        <div>
            <dt class="text-xs uppercase text-gray-500">Tenant UI</dt>
            <dd class="mt-1 font-medium text-gray-900">{{ $adminUnlocked && $walletsOn ? 'Owner (+ managers if allowed)' : 'Hidden' }}</dd>
        </div>
    </dl>

    @can('platform-full-access')
        <form method="POST" action="{{ route('superadmin.businesses.wallet-ledger.unlock', $business->id) }}" class="mt-4">
            @csrf
            <input type="hidden" name="wallet_ledger_adjustments_admin_unlocked" value="0">
            <label class="flex items-start gap-3 rounded-lg border border-sky-200 bg-white p-4">
                <input type="checkbox" name="wallet_ledger_adjustments_admin_unlocked" value="1"
                       {{ $adminUnlocked ? 'checked' : '' }}
                       class="mt-0.5 rounded border-gray-300 text-sky-600 focus:ring-sky-500">
                <span>
                    <span class="block text-sm font-medium text-gray-900">Enable wallet ledger corrections</span>
                    <span class="mt-0.5 block text-xs text-gray-500">Unchecking hides edit/delete controls entirely for this business.</span>
                </span>
            </label>
            <button type="submit" class="mt-3 rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">
                Save wallet ledger access
            </button>
        </form>
    @endcan
</div>
