@extends('layouts.superadmin')

@section('title', 'Affiliate Performance & Management')

@section('content')
@php
    use App\Enums\AffiliateStatus;
    use App\Support\AffiliateStatusPresenter;
@endphp

<div class="mb-8 space-y-4">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Affiliate Performance &amp; Management</h1>
            <p class="mt-1 text-sm text-gray-500">Track referrals, conversions, commissions, and affiliate status</p>
        </div>
        <form method="GET" action="{{ route('superadmin.affiliates.index') }}" class="flex w-full max-w-xl gap-2">
            <input type="hidden" name="period" value="{{ $filters['period'] ?? 'all' }}">
            <input type="hidden" name="status" value="{{ $filters['status'] ?? 'all' }}">
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name, email, username, code…"
                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-violet-500 focus:outline-none">
            <button type="submit" class="shrink-0 rounded-lg bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-violet-500">Search</button>
        </form>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        @foreach(['all' => 'All Time', 'this_month' => 'This Month', 'last_month' => 'Last Month'] as $periodKey => $periodLabel)
            <a href="{{ route('superadmin.affiliates.index', array_merge($filters, ['period' => $periodKey])) }}"
               class="rounded-full px-3 py-1.5 text-xs font-semibold {{ ($filters['period'] ?? 'all') === $periodKey ? 'bg-violet-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                {{ $periodLabel }}
            </a>
        @endforeach
        <span class="mx-1 hidden h-5 w-px bg-gray-300 sm:inline"></span>
        @foreach(['all' => 'All', AffiliateStatus::APPROVED => 'Approved', AffiliateStatus::PENDING => 'Pending', AffiliateStatus::REJECTED => 'Rejected', 'disabled' => 'Disabled'] as $statusKey => $statusLabel)
            <a href="{{ route('superadmin.affiliates.index', array_merge($filters, ['status' => $statusKey])) }}"
               class="rounded-full px-3 py-1.5 text-xs font-semibold {{ ($filters['status'] ?? 'all') === $statusKey ? 'bg-gray-900 text-white' : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50' }}">
                {{ $statusLabel }}
            </a>
        @endforeach
    </div>
</div>

<div class="mb-8 grid grid-cols-2 gap-4 xl:grid-cols-4">
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Active Affiliates</p>
        <p class="mt-2 text-2xl font-bold text-emerald-600">{{ number_format($summary['active_affiliates']) }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Businesses Onboarded</p>
        <p class="mt-2 text-2xl font-bold">{{ number_format($summary['onboarded_businesses']) }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Paid Subscriber Conversion</p>
        <p class="mt-2 text-2xl font-bold text-violet-600">{{ number_format($summary['conversion_rate'], 1) }}%</p>
        <p class="mt-1 text-xs text-gray-500">{{ number_format($summary['active_subscribers']) }} active subscribers</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Commission Paid vs Pending</p>
        <p class="mt-2 text-lg font-bold text-emerald-700">UGX {{ number_format($summary['paid_commission'], 0) }}</p>
        <p class="text-sm font-semibold text-amber-700">UGX {{ number_format($summary['pending_commission'], 0) }} pending</p>
    </div>
</div>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
    <div class="border-b border-gray-200 px-6 py-4">
        <h2 class="font-semibold text-gray-900">Affiliate performance</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">Affiliate</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Onboarded</th>
                    <th class="px-4 py-3 text-right">Active Subs</th>
                    <th class="px-4 py-3 text-right">Conversion</th>
                    <th class="px-4 py-3">Target Status</th>
                    <th class="px-4 py-3 text-right">Earnings (UGX)</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($rows as $row)
                    @php
                        $affiliate = $row['affiliate'];
                        $badge = AffiliateStatusPresenter::for($affiliate);
                    @endphp
                    <tr class="{{ $row['is_system_default'] ? 'bg-violet-50/60' : '' }}">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">
                                {{ $affiliate->name }}
                                @if($row['is_system_default'])
                                    <span class="ml-1 rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-violet-700">System Default</span>
                                @endif
                            </div>
                            <div class="text-xs text-gray-500">
                                @if(optional($affiliate->user)->username)
                                    {{ '@' . $affiliate->user->username }}
                                    ·
                                @endif
                                {{ $affiliate->code }}
                            </div>
                            <div class="text-xs text-gray-400">{{ $affiliate->email }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $badge['classes'] }}">{{ $badge['label'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-right font-medium">{{ number_format($row['onboarded_count']) }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($row['active_subscribers']) }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($row['conversion_rate'], 1) }}%</td>
                        <td class="px-4 py-3">
                            <span class="text-xs font-medium text-gray-700">{{ $row['target_status'] }}</span>
                            @if(!empty($row['target_tiers_hit']))
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @foreach($row['target_tiers_hit'] as $tier)
                                        <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-800">T{{ $tier }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="font-semibold text-gray-900">{{ number_format($row['total_earnings'], 0) }}</div>
                            <div class="text-xs text-emerald-700">Paid {{ number_format($row['paid_earnings'], 0) }}</div>
                            <div class="text-xs text-amber-700">Pending {{ number_format($row['pending_earnings'], 0) }}</div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex flex-wrap justify-end gap-2">
                                <button type="button"
                                        class="affiliate-breakdown-btn rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                        data-url="{{ route('superadmin.affiliates.show', ['affiliate' => $affiliate, 'period' => $filters['period'] ?? 'all']) }}">
                                    View
                                </button>
                                @can('platform-full-access')
                                    @if($affiliate->status === AffiliateStatus::PENDING)
                                        <form method="POST" action="{{ route('superadmin.affiliates.approve', $affiliate) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-emerald-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-emerald-500">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('superadmin.affiliates.reject', $affiliate) }}" class="inline" onsubmit="return confirm('Reject this affiliate application?')">
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-rose-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-rose-500">Reject</button>
                                        </form>
                                    @elseif($affiliate->status === AffiliateStatus::APPROVED && $affiliate->is_active)
                                        <form method="POST" action="{{ route('superadmin.affiliates.toggle-active', $affiliate) }}" class="inline" onsubmit="return confirm('Suspend this affiliate?')">
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-rose-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-rose-500">Suspend</button>
                                        </form>
                                    @elseif($affiliate->status === AffiliateStatus::REJECTED || $affiliate->status === AffiliateStatus::SUSPENDED || ! $affiliate->is_active)
                                        <form method="POST" action="{{ route('superadmin.affiliates.approve', $affiliate) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-emerald-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-emerald-500">Approve</button>
                                        </form>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-sm text-gray-500">No affiliates match your filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div id="affiliate-breakdown-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/50 p-4" role="dialog" aria-modal="true">
    <div class="max-h-[90vh] w-full max-w-3xl overflow-hidden rounded-xl bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
            <div>
                <h3 id="breakdown-title" class="text-lg font-semibold text-gray-900">Affiliate breakdown</h3>
                <p id="breakdown-subtitle" class="text-xs text-gray-500"></p>
            </div>
            <button type="button" id="breakdown-close" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100" aria-label="Close">&times;</button>
        </div>
        <div id="breakdown-body" class="max-h-[calc(90vh-4rem)] overflow-y-auto px-5 py-4 text-sm text-gray-700">
            <p class="text-gray-500">Loading…</p>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var modal = document.getElementById('affiliate-breakdown-modal');
    var body = document.getElementById('breakdown-body');
    var title = document.getElementById('breakdown-title');
    var subtitle = document.getElementById('breakdown-subtitle');
    var closeBtn = document.getElementById('breakdown-close');

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        body.innerHTML = '<p class="text-gray-500">Loading…</p>';
    }

    function openModal() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function esc(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (char) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char];
        });
    }

    function renderBreakdown(data) {
        var affiliate = data.affiliate || {};
        title.textContent = affiliate.name || 'Affiliate breakdown';
        subtitle.textContent = (affiliate.user && affiliate.user.username ? '@' + affiliate.user.username + ' · ' : '') + (affiliate.code || '');

        var stats = '<div class="mb-4 grid gap-3 sm:grid-cols-4">' +
            '<div class="rounded-lg bg-gray-50 p-3"><p class="text-[11px] uppercase text-gray-500">Onboarded</p><p class="text-lg font-bold">' + esc(data.onboarded_count) + '</p></div>' +
            '<div class="rounded-lg bg-gray-50 p-3"><p class="text-[11px] uppercase text-gray-500">Active subs</p><p class="text-lg font-bold">' + esc(data.active_subscribers) + '</p></div>' +
            '<div class="rounded-lg bg-gray-50 p-3"><p class="text-[11px] uppercase text-gray-500">Conversion</p><p class="text-lg font-bold">' + esc(data.conversion_rate) + '%</p></div>' +
            '<div class="rounded-lg bg-gray-50 p-3"><p class="text-[11px] uppercase text-gray-500">Target</p><p class="text-sm font-semibold">' + esc(data.target_status) + '</p></div>' +
            '</div>' +
            '<div class="mb-4 rounded-lg border border-gray-200 p-3 text-xs">' +
            '<span class="font-semibold text-emerald-700">Paid UGX ' + Number(data.paid_earnings || 0).toLocaleString() + '</span>' +
            ' · <span class="font-semibold text-amber-700">Pending UGX ' + Number(data.pending_earnings || 0).toLocaleString() + '</span>' +
            ' · <span class="font-semibold text-gray-900">Total UGX ' + Number(data.total_earnings || 0).toLocaleString() + '</span>' +
            '</div>';

        var rows = (data.businesses || []).map(function (business) {
            var statusClass = business.subscription_status === 'active'
                ? 'bg-emerald-100 text-emerald-800'
                : (business.subscription_status === 'trial' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-700');

            return '<tr>' +
                '<td class="px-3 py-2 font-medium text-gray-900">' + esc(business.name) + '</td>' +
                '<td class="px-3 py-2 text-gray-600">' + esc(business.email || '—') + '</td>' +
                '<td class="px-3 py-2"><span class="rounded-full px-2 py-0.5 text-[11px] font-semibold ' + statusClass + '">' + esc(business.subscription_status) + '</span></td>' +
                '<td class="px-3 py-2 text-gray-500">' + esc((business.created_at || '').substring(0, 10)) + '</td>' +
                '</tr>';
        }).join('');

        body.innerHTML = stats +
            '<h4 class="mb-2 text-sm font-semibold text-gray-900">Referred businesses</h4>' +
            '<div class="overflow-x-auto rounded-lg border border-gray-200">' +
            '<table class="min-w-full text-left text-xs">' +
            '<thead class="bg-gray-50 text-gray-500"><tr><th class="px-3 py-2">Business</th><th class="px-3 py-2">Email</th><th class="px-3 py-2">Subscription</th><th class="px-3 py-2">Joined</th></tr></thead>' +
            '<tbody class="divide-y divide-gray-100">' +
            (rows || '<tr><td colspan="4" class="px-3 py-6 text-center text-gray-500">No referred businesses in this period.</td></tr>') +
            '</tbody></table></div>';
    }

    document.querySelectorAll('.affiliate-breakdown-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            var url = button.getAttribute('data-url');
            openModal();
            fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (response) { return response.json(); })
                .then(renderBreakdown)
                .catch(function () {
                    body.innerHTML = '<p class="text-rose-600">Could not load affiliate breakdown.</p>';
                });
        });
    });

    closeBtn?.addEventListener('click', closeModal);
    modal?.addEventListener('click', function (event) {
        if (event.target === modal) closeModal();
    });
})();
</script>
@endpush
@endsection
