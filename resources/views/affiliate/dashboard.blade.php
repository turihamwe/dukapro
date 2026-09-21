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
    @php
        $businessReferralUrl = $affiliate->referralUrl($affiliate->isSubAffiliate() ? $affiliate->code : null);
    @endphp
    <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-emerald-700">Business referral link</p>
        <p class="mt-1 text-xs text-emerald-800/80">Share this so shops register under you{{ $affiliate->isSubAffiliate() ? ' on ' . ($primaryAffiliate->name ?? 'your team') : '' }}.</p>
        <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center">
            <code class="flex-1 break-all rounded-lg bg-white px-3 py-2 text-sm text-emerald-900">{{ $businessReferralUrl }}</code>
            <button type="button" data-copy-text="{{ $businessReferralUrl }}" data-copy-label="Copy link"
                    class="js-copy-to-clipboard shrink-0 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                Copy link
            </button>
        </div>
        <p class="mt-2 text-xs text-emerald-700">
            @if($affiliate->isSubAffiliate())
                Team link for <strong>{{ $primaryAffiliate->name ?? 'parent' }}</strong> · Your agent code: <strong>{{ $affiliate->code }}</strong>
            @else
                Referral code: <strong>{{ $affiliate->code }}</strong>
            @endif
            @php
                $firstCommissionRate = \App\Support\AffiliateCommissionRates::firstRate();
                $subsequentCommissionRate = \App\Support\AffiliateCommissionRates::subsequentRate();
            @endphp
            · First payment per referred business: {{ number_format($firstCommissionRate * 100, 0) }}%
            · Renewals: {{ number_format($subsequentCommissionRate * 100, 0) }}%
        </p>
    </div>

    @if(! $affiliate->isSubAffiliate())
        <div class="mb-6 rounded-xl border border-violet-200 bg-violet-50 p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-violet-700">Team invite link</p>
            <p class="mt-1 text-xs text-violet-800/80">Share this so agents can register under your affiliate team.</p>
            @php $teamInviteUrl = $affiliate->teamInviteUrl(); @endphp
            <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center">
                <code class="flex-1 break-all rounded-lg bg-white px-3 py-2 text-sm text-violet-900">{{ $teamInviteUrl }}</code>
                <button type="button" data-copy-text="{{ $teamInviteUrl }}" data-copy-label="Copy link"
                        class="js-copy-to-clipboard shrink-0 rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500">
                    Copy link
                </button>
            </div>
        </div>
    @endif
@endif

<div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Businesses onboarded</p>
        <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($stats['onboarded_count']) }}</p>
        @if($referralAttribution && ($referralAttribution['direct'] + $referralAttribution['sub']) > 0)
            <p class="mt-1 text-xs text-gray-500">{{ number_format($referralAttribution['direct']) }} direct · {{ number_format($referralAttribution['sub']) }} via sub-affiliates</p>
        @endif
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
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Wallet balance</p>
        <p class="mt-2 text-3xl font-bold text-emerald-600">UGX {{ number_format($stats['wallet_balance'], 0) }}</p>
    </div>
</div>

@if($affiliate->is_active && $affiliate->status === 'approved')
    <div class="mb-8 grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <h2 class="text-sm font-semibold text-gray-900">Request withdrawal</h2>
            <form method="POST" action="{{ route('affiliate.withdrawals.store') }}" class="mt-4 space-y-3">
                @csrf
                <input type="number" min="1" step="1" name="amount" placeholder="Amount (UGX)" required class="w-full rounded-lg border-gray-300 text-sm">
                <input type="text" name="payout_method" placeholder="Payout method (Mobile Money / Bank)" required class="w-full rounded-lg border-gray-300 text-sm">
                <input type="text" name="payout_account" placeholder="Account / phone number" required class="w-full rounded-lg border-gray-300 text-sm">
                <textarea name="notes" rows="2" placeholder="Notes (optional)" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                <button type="submit" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500">Submit withdrawal</button>
            </form>
        </div>

        @if(! $affiliate->isSubAffiliate())
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <h2 class="text-sm font-semibold text-gray-900">Add sub-affiliate</h2>
                <form method="POST" action="{{ route('affiliate.team.store') }}" class="mt-4 space-y-3">
                    @csrf
                    <input type="text" name="name" placeholder="Full name" required class="w-full rounded-lg border-gray-300 text-sm">
                    <input type="email" name="email" placeholder="Email" required class="w-full rounded-lg border-gray-300 text-sm">
                    <input type="text" name="username" placeholder="Username" required class="w-full rounded-lg border-gray-300 text-sm">
                    <input type="password" name="password" placeholder="Password" required class="w-full rounded-lg border-gray-300 text-sm">
                    <input type="password" name="password_confirmation" placeholder="Confirm password" required class="w-full rounded-lg border-gray-300 text-sm">
                    <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Create team member</button>
                </form>
            </div>
        @endif
    </div>

    @if(! $affiliate->isSubAffiliate() && ($teamMembers ?? collect())->isNotEmpty())
        <div class="mb-8 overflow-hidden rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="font-semibold text-gray-900">Your team</h2>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($teamMembers as $member)
                    <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                        <div>
                            <p class="font-medium text-gray-900">{{ $member->name }}</p>
                            <p class="text-xs text-gray-500">{{ $member->code }} · {{ $member->attributed_businesses_count ?? 0 }} shops · Wallet UGX {{ number_format($member->wallet_balance, 0) }}</p>
                        </div>
                        <form method="POST" action="{{ route('affiliate.team.payout') }}" class="flex flex-wrap items-end gap-2">
                            @csrf
                            <input type="hidden" name="sub_affiliate_id" value="{{ $member->id }}">
                            <input type="number" min="1" name="amount" placeholder="Payout UGX" required class="w-32 rounded-lg border-gray-300 text-sm">
                            <button type="submit" class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-500">Pay sub-affiliate</button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endif

<div class="mb-8 overflow-hidden rounded-xl border border-gray-200 bg-white">
    <div class="border-b border-gray-200 px-6 py-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="font-semibold text-gray-900">Referred businesses</h2>
                <p class="mt-1 text-xs text-gray-500">
                    @if($affiliate->isSubAffiliate())
                        Businesses you brought in with your agent link
                    @else
                        Contact details for shops credited to your team
                    @endif
                </p>
            </div>
            @if($referralAttribution && ($referralAttribution['sub'] > 0 || $referralAttribution['direct'] > 0))
                <div class="flex flex-wrap items-center gap-1.5">
                    @foreach(['all' => 'All', 'direct' => 'Direct signups', 'sub' => 'Sub-affiliate signups'] as $key => $label)
                        <a href="{{ route('affiliate.dashboard', ['referrals' => $key]) }}#referred-businesses"
                           class="rounded-full px-3 py-1 text-xs font-semibold {{ ($referralFilter ?? 'all') === $key ? 'bg-violet-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            {{ $label }}
                            @if($key === 'direct')
                                <span class="opacity-80">({{ $referralAttribution['direct'] }})</span>
                            @elseif($key === 'sub')
                                <span class="opacity-80">({{ $referralAttribution['sub'] }})</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    <div class="overflow-x-auto" id="referred-businesses">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">Business</th>
                    @if(! $affiliate->isSubAffiliate())
                        <th class="px-4 py-3">Source</th>
                    @endif
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
                        @if(! $affiliate->isSubAffiliate())
                            <td class="px-4 py-3 text-gray-600">
                                @if($business->referringAffiliate)
                                    <span class="font-medium text-gray-900">{{ $business->referringAffiliate->name }}</span>
                                    <span class="block text-xs text-gray-500">{{ $business->referringAffiliate->code }}</span>
                                @else
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-800">Direct (you)</span>
                                @endif
                            </td>
                        @endif
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
                        <td colspan="{{ $affiliate->isSubAffiliate() ? 5 : 6 }}" class="px-4 py-10 text-center text-gray-500">
                            @if(($referralFilter ?? 'all') !== 'all')
                                No businesses match this filter.
                            @else
                                No businesses referred yet. Share your link to get started.
                            @endif
                        </td>
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

@push('scripts')
<script>
(function () {
    function copyText(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }
        return new Promise(function (resolve, reject) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            try {
                document.execCommand('copy') ? resolve() : reject(new Error('copy failed'));
            } catch (e) {
                reject(e);
            } finally {
                document.body.removeChild(ta);
            }
        });
    }

    document.querySelectorAll('.js-copy-to-clipboard').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var text = btn.getAttribute('data-copy-text');
            if (!text) return;
            var label = btn.getAttribute('data-copy-label') || btn.textContent.trim();
            copyText(text).then(function () {
                btn.textContent = 'Copied!';
                window.setTimeout(function () { btn.textContent = label; }, 2000);
            }).catch(function () {
                btn.textContent = 'Copy failed';
                window.setTimeout(function () { btn.textContent = label; }, 2000);
            });
        });
    });
})();
</script>
@endpush
