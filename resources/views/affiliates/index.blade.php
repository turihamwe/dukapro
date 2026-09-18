@extends('layouts.superadmin')

@section('title', 'Affiliate Performance & Target Tracking')

@section('content')
@php
    use App\Enums\AffiliateStatus;
    use App\Support\AffiliateStatusPresenter;
@endphp

<div class="mb-8 space-y-4" x-data="affiliateTargetDashboard()" x-cloak>
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Affiliate Performance &amp; Target Tracking</h1>
            <p class="mt-1 text-sm text-gray-500">Track shop registrations, variable daily targets, and multi-horizon projections</p>
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

    {{-- Projections summary panel --}}
    <div class="rounded-xl border border-violet-200 bg-gradient-to-r from-violet-50 to-white p-5 sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">Target projections</p>
                <p class="mt-1 text-sm text-gray-700" x-text="projectionCopy">{{ $projectionSummary['copy'] }}</p>
                <p class="mt-1 text-xs text-gray-500">Formula: daily target × 1 · × 7 · × 30 · × 365</p>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:gap-4">
                @foreach([
                    'daily' => 'Daily',
                    'weekly' => 'Weekly',
                    'monthly' => 'Monthly',
                    'annual' => 'Annual',
                ] as $key => $label)
                    <div class="rounded-lg border border-violet-100 bg-white px-3 py-2 text-center">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-500">{{ $label }}</p>
                        <p class="mt-1 text-lg font-bold text-violet-700" x-text="formatProjection('{{ $key }}')">{{ rtrim(rtrim(number_format($projectionSummary['projections'][$key], 2), '0'), '.') }}</p>
                        <p class="text-[10px] text-gray-400">shops</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Platform summary cards --}}
    <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Active Affiliates</p>
            <p class="mt-2 text-2xl font-bold text-emerald-600">{{ number_format($summary['active_affiliates']) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Shops Registered</p>
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

    {{-- Affiliate performance table --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="font-semibold text-gray-900">Affiliate target performance</h2>
            <p class="mt-1 text-xs text-gray-500">Progress compares registered shops (via referral tracking) against projected targets for each time horizon.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Affiliate</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Daily Target</th>
                        <th class="px-4 py-3">Daily</th>
                        <th class="px-4 py-3">Weekly</th>
                        <th class="px-4 py-3">Monthly</th>
                        <th class="px-4 py-3">Annual</th>
                        <th class="px-4 py-3 text-right">Earnings (UGX)</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $row)
                        @php
                            $affiliate = $row['affiliate'];
                            $badge = AffiliateStatusPresenter::for($affiliate);
                            $tracking = $row['tracking'];
                        @endphp
                        <tr class="{{ $row['is_system_default'] ? 'bg-violet-50/60' : '' }}"
                            data-affiliate-id="{{ $affiliate->id }}"
                            id="affiliate-row-{{ $affiliate->id }}">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">
                                    {{ $affiliate->name }}
                                    @if($row['is_system_default'])
                                        <span class="ml-1 rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-violet-700">System</span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500">
                                    @if(optional($affiliate->user)->username)
                                        {{ '@' . $affiliate->user->username }} ·
                                    @endif
                                    {{ $affiliate->code }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $badge['classes'] }}">{{ $badge['label'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span class="font-semibold text-gray-900 affiliate-daily-target">{{ rtrim(rtrim(number_format($tracking['daily_target'], 2), '0'), '.') }}</span>
                                <span class="text-xs text-gray-400">/day</span>
                            </td>
                            <td class="px-4 py-3">
                                @include('affiliates.partials.progress-bar', ['horizon' => $tracking['horizons']['daily'], 'label' => 'Today', 'compact' => true, 'class' => 'affiliate-horizon-daily'])
                            </td>
                            <td class="px-4 py-3">
                                @include('affiliates.partials.progress-bar', ['horizon' => $tracking['horizons']['weekly'], 'label' => 'This week', 'compact' => true, 'class' => 'affiliate-horizon-weekly'])
                            </td>
                            <td class="px-4 py-3">
                                @include('affiliates.partials.progress-bar', ['horizon' => $tracking['horizons']['monthly'], 'label' => 'This month', 'compact' => true, 'class' => 'affiliate-horizon-monthly'])
                            </td>
                            <td class="px-4 py-3">
                                @include('affiliates.partials.progress-bar', ['horizon' => $tracking['horizons']['annual'], 'label' => 'This year', 'compact' => true, 'class' => 'affiliate-horizon-annual'])
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="font-semibold text-gray-900">{{ number_format($row['total_earnings'], 0) }}</div>
                                <div class="text-xs text-emerald-700">Paid {{ number_format($row['paid_earnings'], 0) }}</div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <button type="button"
                                            class="affiliate-breakdown-btn rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                            data-url="{{ route('superadmin.affiliates.show', ['affiliate' => $affiliate, 'period' => $filters['period'] ?? 'all']) }}">
                                        View
                                    </button>
                                    @can('platform-full-access')
                                        <button type="button"
                                                class="rounded-lg border border-violet-200 bg-violet-50 px-2.5 py-1.5 text-xs font-semibold text-violet-700 hover:bg-violet-100"
                                                @click="openTargetModal({{ $affiliate->id }}, {{ json_encode($affiliate->name) }}, {{ json_encode($tracking) }})">
                                            Set Target
                                        </button>
                                    @endcan
                                    @can('approve-affiliates')
                                        @if($affiliate->status === AffiliateStatus::PENDING)
                                            <form method="POST" action="{{ route('superadmin.affiliates.approve', $affiliate) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="rounded-lg bg-emerald-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-emerald-500">Approve</button>
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
                            <td colspan="9" class="px-4 py-10 text-center text-sm text-gray-500">No affiliates match your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Target configuration modal --}}
    <div x-show="targetModalOpen"
         x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4"
         style="display: none;"
         @keydown.escape.window="closeTargetModal()">
        <div @click.outside="closeTargetModal()"
             class="w-full max-w-lg rounded-xl bg-white shadow-xl"
             x-transition>
            <div class="border-b border-gray-200 px-5 py-4">
                <h3 class="text-lg font-semibold text-gray-900">Configure daily target</h3>
                <p class="text-sm text-gray-500" x-text="targetModalAffiliateName"></p>
            </div>
            <form @submit.prevent="saveTarget()" class="px-5 py-4">
                <label class="block text-sm font-medium text-gray-700">Shops per day</label>
                <input type="number"
                       step="0.1"
                       min="0"
                       max="9999"
                       x-model.number="targetModalDaily"
                       @input="recalculateModalProjections()"
                       class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-violet-500 focus:outline-none focus:ring-1 focus:ring-violet-500"
                       required>
                <p class="mt-2 text-xs text-gray-500">Default is 1 shop per day. Adjust to set expectations for this affiliate.</p>

                <div class="mt-5 rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Projected targets</p>
                    <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                        <div><dt class="text-gray-500">Daily</dt><dd class="font-bold text-gray-900" x-text="formatNumber(modalProjections.daily)"></dd></div>
                        <div><dt class="text-gray-500">Weekly (×7)</dt><dd class="font-bold text-gray-900" x-text="formatNumber(modalProjections.weekly)"></dd></div>
                        <div><dt class="text-gray-500">Monthly (×30)</dt><dd class="font-bold text-gray-900" x-text="formatNumber(modalProjections.monthly)"></dd></div>
                        <div><dt class="text-gray-500">Annual (×365)</dt><dd class="font-bold text-gray-900" x-text="formatNumber(modalProjections.annual)"></dd></div>
                    </dl>
                </div>

                <p x-show="targetModalError" x-text="targetModalError" class="mt-3 text-sm text-rose-600"></p>

                <div class="mt-5 flex justify-end gap-2 border-t border-gray-100 pt-4">
                    <button type="button" @click="closeTargetModal()" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit"
                            :disabled="targetModalSaving"
                            class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500 disabled:opacity-60">
                        <span x-show="!targetModalSaving">Save target</span>
                        <span x-show="targetModalSaving">Saving…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Breakdown modal (existing) --}}
    <div id="affiliate-breakdown-modal" class="fixed inset-0 z-40 hidden items-center justify-center bg-gray-900/50 p-4" role="dialog" aria-modal="true">
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
</div>

<style>[x-cloak] { display: none !important; }</style>

@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
function affiliateTargetDashboard() {
    return {
        targetModalOpen: false,
        targetModalAffiliateId: null,
        targetModalAffiliateName: '',
        targetModalDaily: {{ $projectionSummary['daily_target'] }},
        targetModalSaving: false,
        targetModalError: '',
        modalProjections: @json($projectionSummary['projections']),
        projectionCopy: @json($projectionSummary['copy']),
        platformProjections: @json($projectionSummary['projections']),

        formatNumber(value) {
            var n = Number(value || 0);
            return Number.isInteger(n) ? String(n) : n.toFixed(2).replace(/\.?0+$/, '');
        },

        formatProjection(key) {
            return this.formatNumber(this.platformProjections[key]);
        },

        projectFromDaily(daily) {
            daily = Math.max(0, Number(daily) || 0);
            return {
                daily: daily,
                weekly: Math.round(daily * 7 * 100) / 100,
                monthly: Math.round(daily * 30 * 100) / 100,
                annual: Math.round(daily * 365 * 100) / 100,
            };
        },

        recalculateModalProjections() {
            this.modalProjections = this.projectFromDaily(this.targetModalDaily);
        },

        openTargetModal(id, name, tracking) {
            this.targetModalAffiliateId = id;
            this.targetModalAffiliateName = name;
            this.targetModalDaily = Number(tracking.daily_target || 1);
            this.modalProjections = tracking.projections || this.projectFromDaily(this.targetModalDaily);
            this.targetModalError = '';
            this.targetModalOpen = true;
        },

        closeTargetModal() {
            this.targetModalOpen = false;
            this.targetModalSaving = false;
            this.targetModalError = '';
        },

        async saveTarget() {
            if (!this.targetModalAffiliateId) return;

            this.targetModalSaving = true;
            this.targetModalError = '';

            try {
                var response = await fetch('/superadmin/affiliates/' + this.targetModalAffiliateId + '/target', {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ daily_shop_target: this.targetModalDaily }),
                });

                var data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Could not save target.');
                }

                this.updateRowTracking(this.targetModalAffiliateId, data.tracking);
                this.closeTargetModal();
                window.location.reload();
            } catch (error) {
                this.targetModalError = error.message || 'Could not save target.';
            } finally {
                this.targetModalSaving = false;
            }
        },

        updateRowTracking(affiliateId, tracking) {
            var row = document.getElementById('affiliate-row-' + affiliateId);
            if (!row || !tracking) return;

            var targetEl = row.querySelector('.affiliate-daily-target');
            if (targetEl) {
                targetEl.textContent = this.formatNumber(tracking.daily_target);
            }
        },
    };
}

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

    function horizonBar(label, horizon) {
        var colors = { green: 'bg-emerald-500', yellow: 'bg-amber-400', red: 'bg-rose-500' };
        var badges = { green: 'bg-emerald-100 text-emerald-800', yellow: 'bg-amber-100 text-amber-800', red: 'bg-rose-100 text-rose-800' };
        var bar = colors[horizon.status] || 'bg-gray-400';
        var badge = badges[horizon.status] || 'bg-gray-100 text-gray-700';

        return '<div class="rounded-lg border border-gray-100 bg-white p-2">' +
            '<div class="flex items-center justify-between text-[11px]"><span class="font-medium text-gray-600">' + esc(label) + '</span>' +
            '<span class="rounded-full px-1.5 py-0.5 font-semibold ' + badge + '">' + esc(horizon.label) + '</span></div>' +
            '<div class="mt-1.5 flex items-center gap-2"><div class="h-2 flex-1 rounded-full bg-gray-100"><div class="' + bar + ' h-2 rounded-full" style="width:' + esc(horizon.percent) + '%"></div></div>' +
            '<span class="text-[10px] tabular-nums">' + esc(horizon.actual) + '/' + esc(horizon.target) + '</span></div></div>';
    }

    function renderBreakdown(data) {
        var affiliate = data.affiliate || {};
        var tracking = data.tracking || {};

        title.textContent = affiliate.name || 'Affiliate breakdown';
        subtitle.textContent = (affiliate.user && affiliate.user.username ? '@' + affiliate.user.username + ' · ' : '') + (affiliate.code || '');

        var stats = '<div class="mb-4 grid gap-3 sm:grid-cols-4">' +
            '<div class="rounded-lg bg-gray-50 p-3"><p class="text-[11px] uppercase text-gray-500">Onboarded</p><p class="text-lg font-bold">' + esc(data.onboarded_count) + '</p></div>' +
            '<div class="rounded-lg bg-gray-50 p-3"><p class="text-[11px] uppercase text-gray-500">Active subs</p><p class="text-lg font-bold">' + esc(data.active_subscribers) + '</p></div>' +
            '<div class="rounded-lg bg-gray-50 p-3"><p class="text-[11px] uppercase text-gray-500">Daily target</p><p class="text-lg font-bold">' + esc(tracking.daily_target) + '</p></div>' +
            '<div class="rounded-lg bg-gray-50 p-3"><p class="text-[11px] uppercase text-gray-500">Conversion</p><p class="text-lg font-bold">' + esc(data.conversion_rate) + '%</p></div>' +
            '</div>';

        var horizons = tracking.horizons || {};
        stats += '<div class="mb-4 grid gap-2 sm:grid-cols-2">' +
            horizonBar('Today', horizons.daily || {}) +
            horizonBar('This week', horizons.weekly || {}) +
            horizonBar('This month', horizons.monthly || {}) +
            horizonBar('This year', horizons.annual || {}) +
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
