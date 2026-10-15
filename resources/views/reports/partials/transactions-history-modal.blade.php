@push('modals')
<div id="tx-history-modal" class="app-modal-overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="tx-history-title">
    <div class="app-modal-panel flex max-h-[min(90vh,720px)] w-full max-w-5xl flex-col">
        <div class="app-modal-header shrink-0">
            <div>
                <h2 id="tx-history-title" class="text-lg font-semibold text-gray-900">Transaction history</h2>
                <p id="tx-history-subtitle" class="mt-0.5 text-sm text-gray-500">—</p>
            </div>
            <button type="button" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600" onclick="closeAppModal('tx-history-modal')">&times;</button>
        </div>
        <div class="app-modal-body min-h-0 flex-1 overflow-y-auto">
            <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
                <div>
                    <label for="tx-history-range" class="mb-1 block text-xs font-medium text-gray-700">Period</label>
                    <select id="tx-history-range" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm sm:w-40">
                        <option value="day">Today</option>
                        <option value="week">This Week</option>
                        <option value="month" selected>This Month</option>
                        <option value="year">This Year</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>
                <div id="tx-history-custom" class="hidden flex flex-col gap-3 sm:flex-row">
                    <div>
                        <label for="tx-history-start" class="mb-1 block text-xs font-medium text-gray-700">Start date</label>
                        <input type="date" id="tx-history-start" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label for="tx-history-end" class="mb-1 block text-xs font-medium text-gray-700">End date</label>
                        <input type="date" id="tx-history-end" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div class="flex items-end">
                        <button type="button" id="tx-history-apply" class="inline-flex min-h-[40px] items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Apply</button>
                    </div>
                </div>
                <div id="tx-history-branch-wrap" class="hidden">
                    <label for="tx-history-branch" class="mb-1 block text-xs font-medium text-gray-700">Branch</label>
                    <select id="tx-history-branch" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></select>
                </div>
            </div>
            <p id="tx-history-notice" class="mb-3 hidden text-sm text-gray-600"></p>
            <div id="tx-history-loading" class="hidden px-4 py-8 text-center text-sm text-gray-500">Loading transactions…</div>
            <div id="tx-history-empty" class="hidden px-4 py-8 text-center text-sm text-gray-500">No transactions in this date range.</div>
            <div id="tx-history-error" class="hidden px-4 py-8 text-center text-sm text-red-600">Could not load history. Please try again.</div>
            <div id="tx-history-table-wrap" class="hidden overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="sticky top-0 bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-2.5">Date/Time</th>
                            <th class="px-4 py-2.5">Category</th>
                            <th class="px-4 py-2.5">Entity Name</th>
                            <th class="px-4 py-2.5">Activity / Description</th>
                            <th class="px-4 py-2.5">Reference #</th>
                            <th class="px-4 py-2.5 text-right">Amount / Quantity Impact</th>
                            <th class="px-4 py-2.5">Branch</th>
                        </tr>
                    </thead>
                    <tbody id="tx-history-tbody" class="divide-y divide-gray-50 bg-white"></tbody>
                </table>
            </div>
        </div>
        <div class="app-modal-footer shrink-0 flex items-center justify-between gap-3">
            <div id="tx-history-pager" class="hidden flex items-center gap-2 text-sm text-gray-600">
                <button type="button" id="tx-history-prev" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Previous</button>
                <span id="tx-history-page"></span>
                <button type="button" id="tx-history-next" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Next</button>
            </div>
            <x-button variant="secondary" type="button" class="ml-auto" onclick="closeAppModal('tx-history-modal')">Close</x-button>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
(function () {
    var modalId = 'tx-history-modal';
    var feedUrl = @json(tenant_route('tenant.transactions-log.feed'));
    var state = {
        type: 'all',
        supplier_id: '',
        customer_id: '',
        product_id: '',
        title: 'Transactions',
        range: 'month',
        start_date: '',
        end_date: '',
        branch_id: '',
        page: 1
    };

    function esc(text) {
        var el = document.createElement('span');
        el.textContent = text == null ? '' : String(text);
        return el.innerHTML;
    }

    function setState(name) {
        ['loading', 'empty', 'error', 'table-wrap'].forEach(function (suffix) {
            var el = document.getElementById('tx-history-' + suffix);
            if (el) {
                el.classList.toggle('hidden', name !== suffix);
            }
        });
    }

    function syncCustomDates() {
        var range = document.getElementById('tx-history-range');
        var custom = document.getElementById('tx-history-custom');
        if (range && custom) {
            custom.classList.toggle('hidden', range.value !== 'custom');
        }
    }

    function syncBranch(branch) {
        var wrap = document.getElementById('tx-history-branch-wrap');
        var select = document.getElementById('tx-history-branch');
        if (!wrap || !select) {
            return;
        }
        if (!branch || !branch.show_picker) {
            wrap.classList.add('hidden');
            return;
        }
        wrap.classList.remove('hidden');
        if (select.getAttribute('data-built') !== '1') {
            var html = '<option value="all">All branches</option>';
            (branch.options || []).forEach(function (option) {
                html += '<option value="' + esc(option.id) + '">' + esc(option.name) + '</option>';
            });
            select.innerHTML = html;
            select.setAttribute('data-built', '1');
        }
        select.value = branch.selected || 'all';
    }

    function renderRows(rows) {
        var tbody = document.getElementById('tx-history-tbody');
        if (!tbody) {
            return;
        }
        tbody.innerHTML = rows.map(function (row) {
            var impactClass = row.negative ? 'text-red-600' : 'text-emerald-700';
            return '<tr>'
                + '<td class="whitespace-nowrap px-4 py-2 text-gray-700">' + esc(row.occurred_at) + '</td>'
                + '<td class="whitespace-nowrap px-4 py-2 text-gray-900">' + esc(row.category) + '</td>'
                + '<td class="px-4 py-2 text-gray-900">' + esc(row.entity_name) + '</td>'
                + '<td class="px-4 py-2 text-gray-600">' + esc(row.activity) + '</td>'
                + '<td class="whitespace-nowrap px-4 py-2 text-gray-600">' + esc(row.reference_no) + '</td>'
                + '<td class="whitespace-nowrap px-4 py-2 text-right font-medium ' + impactClass + '">' + esc(row.impact) + '</td>'
                + '<td class="whitespace-nowrap px-4 py-2 text-gray-600">' + esc(row.branch) + '</td>'
                + '</tr>';
        }).join('');
    }

    function syncPager(payload) {
        var pager = document.getElementById('tx-history-pager');
        var label = document.getElementById('tx-history-page');
        var prev = document.getElementById('tx-history-prev');
        var next = document.getElementById('tx-history-next');
        var last = payload.last_page || 1;
        if (!pager) {
            return;
        }
        pager.classList.toggle('hidden', last <= 1);
        if (label) {
            label.textContent = 'Page ' + payload.page + ' of ' + last;
        }
        if (prev) {
            prev.disabled = payload.page <= 1;
        }
        if (next) {
            next.disabled = payload.page >= last;
        }
    }

    function loadHistory() {
        var subtitle = document.getElementById('tx-history-subtitle');
        var notice = document.getElementById('tx-history-notice');
        if (subtitle) {
            subtitle.textContent = state.title || 'Loading…';
        }
        if (notice) {
            notice.classList.add('hidden');
            notice.textContent = '';
        }
        setState('loading');

        var params = new URLSearchParams();
        params.set('type', state.type || 'all');
        if (state.supplier_id) params.set('supplier_id', state.supplier_id);
        if (state.customer_id) params.set('customer_id', state.customer_id);
        if (state.product_id) params.set('product_id', state.product_id);
        params.set('range', state.range || 'month');
        if (state.range === 'custom') {
            if (state.start_date) params.set('start_date', state.start_date);
            if (state.end_date) params.set('end_date', state.end_date);
        }
        if (state.branch_id) params.set('branch_id', state.branch_id);
        params.set('page', String(state.page || 1));

        return fetch(feedUrl + '?' + params.toString(), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('fetch failed');
                }
                return response.json();
            })
            .then(function (payload) {
                var title = document.getElementById('tx-history-title');
                if (title) {
                    title.textContent = payload.title || 'Transaction history';
                }
                if (subtitle) {
                    var count = typeof payload.total === 'number' ? payload.total : 0;
                    subtitle.textContent = (payload.range_label || '') + ' · ' + count + (count === 1 ? ' transaction' : ' transactions');
                }
                if (notice && payload.notice) {
                    notice.textContent = payload.notice;
                    notice.classList.remove('hidden');
                }
                syncBranch(payload.branch);
                syncPager(payload);
                var rows = payload.rows || [];
                if (!rows.length) {
                    setState('empty');
                    return;
                }
                renderRows(rows);
                setState('table-wrap');
            })
            .catch(function () {
                setState('error');
                if (subtitle) {
                    subtitle.textContent = 'Could not load history.';
                }
            });
    }

    window.openTransactionsHistory = function (options) {
        options = options || {};
        state.type = options.type || 'all';
        state.supplier_id = options.supplier_id || '';
        state.customer_id = options.customer_id || '';
        state.product_id = options.product_id || '';
        state.title = options.title || 'Transactions';
        state.range = 'month';
        state.start_date = '';
        state.end_date = '';
        state.branch_id = '';
        state.page = 1;

        var range = document.getElementById('tx-history-range');
        var branch = document.getElementById('tx-history-branch');
        if (range) range.value = 'month';
        if (branch) branch.value = 'all';
        syncCustomDates();

        if (typeof window.openAppModal === 'function') {
            window.openAppModal(modalId);
        }
        loadHistory();
    };

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-tx-history]');
        if (!button) {
            return;
        }
        event.preventDefault();
        window.openTransactionsHistory({
            type: button.getAttribute('data-tx-type'),
            supplier_id: button.getAttribute('data-tx-supplier') || '',
            customer_id: button.getAttribute('data-tx-customer') || '',
            product_id: button.getAttribute('data-tx-product') || '',
            title: button.getAttribute('data-tx-title') || 'Transactions'
        });
    });

    var range = document.getElementById('tx-history-range');
    if (range) {
        range.addEventListener('change', function () {
            state.range = range.value;
            state.page = 1;
            syncCustomDates();
            if (range.value !== 'custom') {
                loadHistory();
            }
        });
    }

    var apply = document.getElementById('tx-history-apply');
    if (apply) {
        apply.addEventListener('click', function () {
            state.range = 'custom';
            state.start_date = (document.getElementById('tx-history-start') || {}).value || '';
            state.end_date = (document.getElementById('tx-history-end') || {}).value || '';
            state.page = 1;
            loadHistory();
        });
    }

    var branch = document.getElementById('tx-history-branch');
    if (branch) {
        branch.addEventListener('change', function () {
            state.branch_id = branch.value;
            state.page = 1;
            loadHistory();
        });
    }

    var prev = document.getElementById('tx-history-prev');
    var next = document.getElementById('tx-history-next');
    if (prev) {
        prev.addEventListener('click', function () {
            if (state.page > 1) {
                state.page -= 1;
                loadHistory();
            }
        });
    }
    if (next) {
        next.addEventListener('click', function () {
            state.page += 1;
            loadHistory();
        });
    }
})();
</script>
@endpush
