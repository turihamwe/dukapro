@php
    use App\Enums\AffiliateStatus;
    use App\Support\AffiliateStatusPresenter;
    $overviewRoute = route('superadmin.platform-overview');
@endphp

<section id="affiliate-performance" class="scroll-mt-8 border-t border-gray-200 pt-10" x-data="affiliateTargetDashboard()" x-cloak>
    <div class="mb-6 space-y-4">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h2 class="text-xl font-bold tracking-tight text-gray-900">Affiliate Performance &amp; Target Tracking</h2>
                <p class="mt-1 text-sm text-gray-500">Track shop registrations, variable daily targets, and multi-horizon projections</p>
            </div>
            <form method="GET" action="{{ $overviewRoute }}" class="flex w-full max-w-xl gap-2">
                <input type="hidden" name="period" value="{{ $filters['period'] ?? 'all' }}">
                <input type="hidden" name="status" value="{{ $filters['status'] ?? 'all' }}">
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name, email, username, code…"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-violet-500 focus:outline-none">
                <button type="submit" class="shrink-0 rounded-lg bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-violet-500">Search</button>
            </form>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @foreach(['all' => 'All Time', 'this_month' => 'This Month', 'last_month' => 'Last Month'] as $periodKey => $periodLabel)
                <a href="{{ route('superadmin.platform-overview', array_merge($filters, ['period' => $periodKey])) }}#affiliate-performance"
                   class="rounded-full px-3 py-1.5 text-xs font-semibold {{ ($filters['period'] ?? 'all') === $periodKey ? 'bg-violet-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    {{ $periodLabel }}
                </a>
            @endforeach
            <span class="mx-1 hidden h-5 w-px bg-gray-300 sm:inline"></span>
            @foreach(['all' => 'All', AffiliateStatus::APPROVED => 'Approved', AffiliateStatus::PENDING => 'Pending', AffiliateStatus::REJECTED => 'Rejected', 'disabled' => 'Disabled'] as $statusKey => $statusLabel)
                <a href="{{ route('superadmin.platform-overview', array_merge($filters, ['status' => $statusKey])) }}#affiliate-performance"
                   class="rounded-full px-3 py-1.5 text-xs font-semibold {{ ($filters['status'] ?? 'all') === $statusKey ? 'bg-gray-900 text-white' : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50' }}">
                    {{ $statusLabel }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="rounded-xl border border-violet-200 bg-gradient-to-r from-violet-50 to-white p-5 sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">Target projections</p>
                <p class="mt-1 text-sm text-gray-700" x-text="projectionCopy">{{ $projectionSummary['copy'] }}</p>
                <p class="mt-1 text-xs text-gray-500">Formula: daily target × 1 · × 7 · × 30 · × 365</p>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:gap-4">
                @foreach(['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'annual' => 'Annual'] as $key => $label)
                    <div class="rounded-lg border border-violet-100 bg-white px-3 py-2 text-center">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-500">{{ $label }}</p>
                        <p class="mt-1 text-lg font-bold text-violet-700" x-text="formatProjection('{{ $key }}')">{{ rtrim(rtrim(number_format($projectionSummary['projections'][$key], 2), '0'), '.') }}</p>
                        <p class="text-[10px] text-gray-400">shops</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
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

    <div class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="border-b border-gray-200 px-6 py-4">
            <h3 class="font-semibold text-gray-900">Affiliate target performance</h3>
            <p class="mt-1 text-xs text-gray-500">Progress compares registered shops against projected targets for each time horizon.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Affiliate</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Shops</th>
                        <th class="px-4 py-3 text-right">Direct</th>
                        <th class="px-4 py-3 text-right">Sub-aff.</th>
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
                        <tr class="{{ $row['is_system_default'] ? 'bg-violet-50/60' : '' }}" id="affiliate-row-{{ $affiliate->id }}">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $affiliate->name }}</div>
                                <div class="text-xs text-gray-500">{{ $affiliate->code }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $badge['classes'] }}">{{ $badge['label'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900">{{ number_format($row['onboarded_count']) }}</td>
                            <td class="px-4 py-3 text-right text-emerald-700">{{ number_format($row['direct_referrals_count'] ?? 0) }}</td>
                            <td class="px-4 py-3 text-right text-violet-700">{{ number_format($row['sub_referrals_count'] ?? 0) }}</td>
                            <td class="px-4 py-3 text-right">
                                <span class="font-semibold text-gray-900 affiliate-daily-target">{{ rtrim(rtrim(number_format($tracking['daily_target'], 2), '0'), '.') }}</span>
                            </td>
                            <td class="px-4 py-3">@include('affiliates.partials.progress-bar', ['horizon' => $tracking['horizons']['daily'], 'label' => 'Today', 'compact' => true])</td>
                            <td class="px-4 py-3">@include('affiliates.partials.progress-bar', ['horizon' => $tracking['horizons']['weekly'], 'label' => 'Week', 'compact' => true])</td>
                            <td class="px-4 py-3">@include('affiliates.partials.progress-bar', ['horizon' => $tracking['horizons']['monthly'], 'label' => 'Month', 'compact' => true])</td>
                            <td class="px-4 py-3">@include('affiliates.partials.progress-bar', ['horizon' => $tracking['horizons']['annual'], 'label' => 'Year', 'compact' => true])</td>
                            <td class="px-4 py-3 text-right">
                                <div class="font-semibold text-gray-900">{{ number_format($row['total_earnings'], 0) }}</div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <button type="button" class="affiliate-breakdown-btn rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                            data-url="{{ route('superadmin.affiliates.show', ['affiliate' => $affiliate, 'period' => $filters['period'] ?? 'all']) }}">View</button>
                                    @can('approve-affiliates')
                                        <button type="button" class="rounded-lg border border-violet-200 bg-violet-50 px-2.5 py-1.5 text-xs font-semibold text-violet-700 hover:bg-violet-100"
                                                @click="openTargetModal({{ $affiliate->id }}, {{ json_encode($affiliate->name) }}, {{ json_encode($tracking) }})">Set Target</button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="px-4 py-10 text-center text-sm text-gray-500">No affiliates match your filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('superadmin.partials.pagination', ['paginator' => $rows])
    </div>

    {{-- Target modal --}}
    <div x-show="targetModalOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4" style="display: none;" @keydown.escape.window="closeTargetModal()">
        <div @click.outside="closeTargetModal()" class="w-full max-w-lg rounded-xl bg-white shadow-xl" x-transition>
            <div class="border-b border-gray-200 px-5 py-4">
                <h3 class="text-lg font-semibold text-gray-900">Configure daily target</h3>
                <p class="text-sm text-gray-500" x-text="targetModalAffiliateName"></p>
            </div>
            <form @submit.prevent="saveTarget()" class="px-5 py-4">
                <label class="block text-sm font-medium text-gray-700">Shops per day</label>
                <input type="number" step="0.1" min="0" max="9999" x-model.number="targetModalDaily" @input="recalculateModalProjections()"
                       class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" required>
                <div class="mt-5 rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <dl class="grid grid-cols-2 gap-3 text-sm">
                        <div><dt class="text-gray-500">Daily</dt><dd class="font-bold" x-text="formatNumber(modalProjections.daily)"></dd></div>
                        <div><dt class="text-gray-500">Weekly</dt><dd class="font-bold" x-text="formatNumber(modalProjections.weekly)"></dd></div>
                        <div><dt class="text-gray-500">Monthly</dt><dd class="font-bold" x-text="formatNumber(modalProjections.monthly)"></dd></div>
                        <div><dt class="text-gray-500">Annual</dt><dd class="font-bold" x-text="formatNumber(modalProjections.annual)"></dd></div>
                    </dl>
                </div>
                <p x-show="targetModalError" x-text="targetModalError" class="mt-3 text-sm text-rose-600"></p>
                <div class="mt-5 flex justify-end gap-2 border-t border-gray-100 pt-4">
                    <button type="button" @click="closeTargetModal()" class="rounded-lg border border-gray-300 px-4 py-2 text-sm">Cancel</button>
                    <button type="submit" :disabled="targetModalSaving" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">Save target</button>
                </div>
            </form>
        </div>
    </div>

    <div id="affiliate-breakdown-modal" class="fixed inset-0 z-40 hidden items-center justify-center bg-gray-900/50 p-4">
        <div class="max-h-[90vh] w-full max-w-3xl overflow-hidden rounded-xl bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                <div>
                    <h3 id="breakdown-title" class="text-lg font-semibold text-gray-900">Affiliate breakdown</h3>
                    <p id="breakdown-subtitle" class="text-xs text-gray-500"></p>
                </div>
                <button type="button" id="breakdown-close" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100">&times;</button>
            </div>
            <div id="breakdown-body" class="max-h-[calc(90vh-4rem)] overflow-y-auto px-5 py-4 text-sm"><p class="text-gray-500">Loading…</p></div>
        </div>
    </div>
</section>

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
        formatNumber(v) { var n = Number(v || 0); return Number.isInteger(n) ? String(n) : n.toFixed(2).replace(/\.?0+$/, ''); },
        formatProjection(key) { return this.formatNumber(this.platformProjections[key]); },
        projectFromDaily(daily) {
            daily = Math.max(0, Number(daily) || 0);
            return { daily: daily, weekly: Math.round(daily * 7 * 100) / 100, monthly: Math.round(daily * 30 * 100) / 100, annual: Math.round(daily * 365 * 100) / 100 };
        },
        recalculateModalProjections() { this.modalProjections = this.projectFromDaily(this.targetModalDaily); },
        openTargetModal(id, name, tracking) {
            this.targetModalAffiliateId = id; this.targetModalAffiliateName = name;
            this.targetModalDaily = Number(tracking.daily_target || 1);
            this.modalProjections = tracking.projections || this.projectFromDaily(this.targetModalDaily);
            this.targetModalError = ''; this.targetModalOpen = true;
        },
        closeTargetModal() { this.targetModalOpen = false; this.targetModalSaving = false; this.targetModalError = ''; },
        async saveTarget() {
            if (!this.targetModalAffiliateId) return;
            this.targetModalSaving = true; this.targetModalError = '';
            try {
                var response = await fetch('/superadmin/affiliates/' + this.targetModalAffiliateId + '/target', {
                    method: 'PATCH',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ daily_shop_target: this.targetModalDaily }),
                });
                var data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Could not save target.');
                this.closeTargetModal(); window.location.reload();
            } catch (e) { this.targetModalError = e.message || 'Could not save target.'; }
            finally { this.targetModalSaving = false; }
        },
    };
}
(function () {
    var modal = document.getElementById('affiliate-breakdown-modal');
    if (!modal) return;
    var body = document.getElementById('breakdown-body');
    var title = document.getElementById('breakdown-title');
    var subtitle = document.getElementById('breakdown-subtitle');
    function closeModal() { modal.classList.add('hidden'); modal.classList.remove('flex'); body.innerHTML = '<p class="text-gray-500">Loading…</p>'; }
    function openModal() { modal.classList.remove('hidden'); modal.classList.add('flex'); }
    function esc(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (char) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char];
        });
    }

    function renderBreakdown(data) {
        var affiliate = data.affiliate || {};
        title.textContent = affiliate.name || 'Affiliate breakdown';
        subtitle.textContent = (affiliate.code || '');
        function sourceLabel(business) {
            if (business.referring_affiliate) {
                return esc(business.referring_affiliate.name) + ' (' + esc(business.referring_affiliate.code) + ')';
            }
            return '<span class="text-emerald-700">Direct</span>';
        }
        var rows = (data.businesses || []).map(function (business) {
            return '<tr><td class="px-3 py-2">' + esc(business.name) + '</td><td class="px-3 py-2">' + sourceLabel(business) + '</td><td class="px-3 py-2">' + esc(business.email || '—') + '</td><td class="px-3 py-2">' + esc(business.subscription_status) + '</td><td class="px-3 py-2">' + esc((business.created_at || '').substring(0, 10)) + '</td></tr>';
        }).join('');
        var bySub = (data.by_sub_affiliate || []).map(function (group) {
            var sub = group.sub_affiliate || {};
            var name = sub.name ? esc(sub.name) + ' <span class="text-gray-400">(' + esc(sub.code) + ')</span>' : 'Unknown';
            return '<div class="rounded-lg border border-gray-100 bg-gray-50 p-3"><p class="text-xs font-semibold text-gray-900">' + name + '</p><p class="mt-1 text-[11px] text-gray-500">' + esc(group.count) + ' shops · ' + esc(group.active_count) + ' active</p></div>';
        }).join('');
        body.innerHTML = '<div class="mb-4 grid gap-3 sm:grid-cols-5">' +
            '<div class="rounded-lg bg-gray-50 p-3"><p class="text-[11px] uppercase text-gray-500">Onboarded</p><p class="text-lg font-bold">' + esc(data.onboarded_count) + '</p></div>' +
            '<div class="rounded-lg bg-emerald-50 p-3"><p class="text-[11px] uppercase text-emerald-700">Direct</p><p class="text-lg font-bold text-emerald-800">' + esc(data.direct_referrals_count || 0) + '</p></div>' +
            '<div class="rounded-lg bg-violet-50 p-3"><p class="text-[11px] uppercase text-violet-700">Sub-affiliate</p><p class="text-lg font-bold text-violet-800">' + esc(data.sub_referrals_count || 0) + '</p></div>' +
            '<div class="rounded-lg bg-gray-50 p-3"><p class="text-[11px] uppercase text-gray-500">Active subs</p><p class="text-lg font-bold">' + esc(data.active_subscribers) + '</p></div>' +
            '<div class="rounded-lg bg-gray-50 p-3"><p class="text-[11px] uppercase text-gray-500">Conversion</p><p class="text-lg font-bold">' + esc(data.conversion_rate) + '%</p></div></div>' +
            (bySub ? '<div class="mb-4"><p class="mb-2 text-[11px] font-semibold uppercase text-gray-500">Downline attribution</p><div class="grid gap-2 sm:grid-cols-2">' + bySub + '</div></div>' : '') +
            '<table class="min-w-full text-xs"><thead><tr><th class="px-3 py-2 text-left">Business</th><th class="px-3 py-2 text-left">Source</th><th class="px-3 py-2 text-left">Email</th><th class="px-3 py-2 text-left">Status</th><th class="px-3 py-2 text-left">Joined</th></tr></thead><tbody>' +
            (rows || '<tr><td colspan="5" class="px-3 py-6 text-center text-gray-500">No referred businesses.</td></tr>') + '</tbody></table>';
    }

    document.querySelectorAll('.affiliate-breakdown-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openModal();
            fetch(btn.getAttribute('data-url'), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(renderBreakdown)
                .catch(function () { body.innerHTML = '<p class="text-rose-600">Could not load breakdown.</p>'; });
        });
    });
    document.getElementById('breakdown-close')?.addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
})();
</script>
@endpush
