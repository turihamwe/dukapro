@extends('layouts.admin')

@section('title', 'Wallets & accounts')

@section('content')
<x-page-header title="Wallets & payment accounts" subtitle="Track cash, mobile money, and bank balances in one place">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" type="button" data-open-import-wallets>Import (CSV)</x-button>
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.wallets.export') }}">Export (CSV)</x-button>
        @if($wallets->where('is_active', true)->isNotEmpty())
            <x-button variant="primary" size="sm" type="button" onclick="openAppModal('wallet-deposit-modal')">+ Add funds</x-button>
        @endif
        @can('access-customer-credit')
            <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.customer-credit.payments.index') }}">Receivables</x-button>
        @endcan
        @can('access-supplier-credit')
            <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.supplier-credit.payments.index') }}">Payables</x-button>
        @endcan
    </x-slot>
</x-page-header>

@if(session('import_errors') && count(session('import_errors')))
    <x-alert type="warning" class="mb-4">
        <p class="font-medium">Import notes:</p>
        <ul class="mt-2 list-inside list-disc text-xs">
            @foreach(session('import_errors') as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-alert>
@endif

<p class="mb-4 text-xs text-gray-500">CSV export lists <strong>all payment accounts</strong> for this business. <code>opening_balance</code> is exported as <strong>0</strong> so files are safe to re-import elsewhere without copying live balances.</p>

<x-stat-card class="mb-6" label="Total liquid funds (active wallets)" :value="format_money($totalLiquid)" accent="emerald" />

<div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @forelse($wallets->where('is_active', true) as $wallet)
        <x-card class="p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ $wallet->typeLabel() }}</p>
            <p class="mt-1 text-lg font-semibold text-gray-900">{{ $wallet->name }}</p>
            <p class="mt-2 text-2xl font-bold text-emerald-700">@money($wallet->current_balance)</p>
            @if((float) $wallet->opening_balance !== (float) $wallet->current_balance)
                <p class="mt-1 text-xs text-gray-500">Started at @money($wallet->opening_balance)</p>
            @endif
            <div class="mt-3 flex gap-2">
                <x-button variant="secondary" size="sm" type="button" class="flex-1" onclick="openWalletDepositModal({{ (int) $wallet->id }})">Deposit</x-button>
                <x-button variant="secondary" size="sm" type="button" title="View transaction history" aria-label="View transaction history" onclick="openWalletHistoryModal({{ (int) $wallet->id }})">History</x-button>
            </div>
        </x-card>
    @empty
        <x-card class="sm:col-span-2 lg:col-span-3 text-sm text-gray-600">
            No active payment accounts yet. Balances appear here once accounts exist and receivable or payable payments are linked to wallets.
        </x-card>
    @endforelse
</div>

@if($wallets->isNotEmpty())
    <h2 class="mb-3 text-sm font-semibold text-gray-900">All accounts</h2>
    <div class="mb-2 space-y-3">
        @foreach($wallets as $wallet)
            <x-card :padding="false" class="p-4">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
                    <div>
                        <p class="mb-1 text-xs font-medium text-gray-700">Name</p>
                        <p class="text-sm font-semibold text-gray-900">{{ $wallet->name }}</p>
                    </div>
                    <div>
                        <p class="mb-1 text-xs font-medium text-gray-700">Type</p>
                        <p class="text-sm text-gray-900">{{ $wallet->typeLabel() }}</p>
                    </div>
                    <div>
                        <p class="mb-1 text-xs font-medium text-gray-700">Current balance</p>
                        <p class="text-sm font-semibold text-emerald-700">@money($wallet->current_balance)</p>
                    </div>
                    <div>
                        <p class="mb-1 text-xs font-medium text-gray-700">Status</p>
                        <p class="text-sm text-gray-900">
                            @if($wallet->is_active)
                                <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">Active</span>
                            @else
                                <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">Inactive</span>
                            @endif
                        </p>
                    </div>
                </div>
                <div class="mt-3 flex justify-end border-t border-gray-100 pt-3">
                    <x-button variant="secondary" size="sm" type="button" onclick="openWalletHistoryModal({{ (int) $wallet->id }})">View log</x-button>
                </div>
            </x-card>
        @endforeach
    </div>
@endif

<div class="mt-4">
    <div class="mb-3 h-px w-14 bg-gray-200" aria-hidden="true"></div>
    <x-card class="mb-8">
    <h2 class="mb-4 text-sm font-semibold text-gray-900">Add wallet</h2>
    <form method="POST" action="{{ tenant_route('tenant.wallets.store') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
        @csrf
        <x-input type="text" name="name" label="Name" placeholder="e.g. MTN Mobile Money" required />
        <x-select name="type" label="Type" required>
            @foreach($types as $value => $typeLabel)
                <option value="{{ $value }}" @selected(old('type', 'cash') === $value)>{{ $typeLabel }}</option>
            @endforeach
        </x-select>
        <x-input type="number" step="0.01" min="0" name="opening_balance" label="Opening balance" value="{{ old('opening_balance', '0') }}" />
        <x-button variant="primary" type="submit">Add wallet</x-button>
    </form>
    </x-card>
</div>

@if($recentInflows->isNotEmpty())
    <x-card class="mb-8 !p-0 overflow-hidden">
        <div class="border-b border-gray-100 px-4 py-3">
            <h2 class="text-sm font-semibold text-gray-900">Recent manual deposits</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-2">When</th>
                        <th class="px-4 py-2">Wallet</th>
                        <th class="px-4 py-2">Category</th>
                        <th class="px-4 py-2">Source / note</th>
                        <th class="px-4 py-2 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 bg-white">
                    @foreach($recentInflows as $inflow)
                        <tr>
                            <td class="px-4 py-2 text-gray-600">{{ $inflow->occurred_at->format('M j, Y g:i A') }}</td>
                            <td class="px-4 py-2 text-gray-900">{{ $inflow->wallet->name ?? '—' }}</td>
                            <td class="px-4 py-2 text-gray-700">{{ $inflow->categoryLabel() }}</td>
                            <td class="px-4 py-2 text-gray-600">{{ $inflow->source_name ?: '—' }}</td>
                            <td class="px-4 py-2 text-right font-semibold text-emerald-700">+@money($inflow->amount)</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
@endif

@push('modals')
    <x-csv-import-modal
        modal-id="import-wallets-modal"
        title="Import wallets (CSV)"
        :action="tenant_route('tenant.wallets.import.store')"
        :template-url="tenant_route('tenant.wallets.import.template')"
        columns-hint="Required: <span class=&quot;font-medium text-gray-800&quot;>name, type</span> (<code>cash</code>, <code>mobile_money</code>, <code>bank</code>). Optional: opening_balance, sort_order, identifier (ignored on import). Duplicates match on name + type."
    />
    @if($wallets->where('is_active', true)->isNotEmpty())
        @include('wallets._deposit-modal', ['wallets' => $wallets, 'inflowCategories' => $inflowCategories ?? []])
    @endif
    @if($wallets->isNotEmpty())
        @include('wallets._history-modal')
    @endif
    @can('adjust-wallet-transactions')
        @include('wallets._transaction-edit-modal')
        @include('wallets._transaction-delete-modal')
    @endcan
@endpush

@push('scripts')
<script>
(function () {
    var historyModalId = 'wallet-history-modal';
    var editModalId = 'wallet-transaction-edit-modal';
    var deleteModalId = 'wallet-transaction-delete-modal';
    var historyUrlTemplate = @json(tenant_route('tenant.wallets.transactions', ['wallet' => '__WALLET__']));
    var canManageLedger = @json(auth()->user()->can('adjust-wallet-transactions'));
    var updateUrlTemplate = canManageLedger
        ? @json(tenant_route('tenant.wallets.transactions.update', ['wallet' => '__WALLET__', 'transaction' => '__TX__']))
        : null;
    var deleteUrlTemplate = canManageLedger
        ? @json(tenant_route('tenant.wallets.transactions.destroy', ['wallet' => '__WALLET__', 'transaction' => '__TX__']))
        : null;
    var currencySample = @json(format_money(0));
    var csrfToken = document.querySelector('meta[name="csrf-token"]');
    csrfToken = csrfToken ? csrfToken.content : '';

    var currentWalletId = null;
    var pendingDeleteRow = null;
    var pendingEditRow = null;

    function formatMoney(amount) {
        var n = Number(amount);
        if (!Number.isFinite(n)) {
            return '—';
        }
        var formatted = n.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        return currencySample.replace(/[\d,.]+/, formatted);
    }

    function esc(text) {
        var el = document.createElement('span');
        el.textContent = text == null ? '' : String(text);
        return el.innerHTML;
    }

    function setHistoryState(state) {
        ['loading', 'empty', 'error', 'table-wrap', 'footnote'].forEach(function (suffix) {
            var el = document.getElementById(historyModalId + '-' + suffix);
            if (el) {
                el.classList.toggle('hidden', state !== suffix);
            }
        });
    }

    function walletSubtitle(wallet) {
        return (wallet.name || 'Wallet') + ' · ' + (wallet.type_label || '') + ' · Balance ' + formatMoney(wallet.current_balance);
    }

    function renderHistoryRows(rows, canManage) {
        var tbody = document.getElementById(historyModalId + '-tbody');
        var actionsHead = document.getElementById(historyModalId + '-actions-head');
        if (actionsHead) {
            actionsHead.classList.toggle('hidden', !canManage);
        }
        if (!tbody) {
            return;
        }

        tbody.innerHTML = rows.map(function (row) {
            var inflow = row.direction === 'in';
            var badgeClass = inflow
                ? 'bg-emerald-100 text-emerald-800'
                : 'bg-red-100 text-red-800';
            var amountClass = inflow ? 'text-emerald-700' : 'text-red-700';
            var sign = inflow ? '+' : '−';
            var source = row.source_label || '—';
            if (row.reference) {
                source += ' · ' + row.reference;
            }
            var balance = row.balance_after != null ? formatMoney(row.balance_after) : '—';
            var actionsCell = '';

            if (canManage) {
                actionsCell = '<td class="whitespace-nowrap px-4 py-2 text-right">'
                    + '<button type="button" class="mr-1 rounded p-1 text-gray-500 hover:bg-gray-100 hover:text-indigo-600" title="Edit" data-wallet-tx-edit="' + String(row.id) + '">Edit</button>'
                    + '<button type="button" class="rounded p-1 text-gray-500 hover:bg-gray-100 hover:text-red-600" title="Remove" data-wallet-tx-delete="' + String(row.id) + '">Delete</button>'
                    + '</td>';
            }

            return '<tr data-transaction-id="' + esc(String(row.id)) + '">'
                + '<td class="whitespace-nowrap px-4 py-2 text-gray-600">' + esc(row.occurred_at) + '</td>'
                + '<td class="px-4 py-2"><span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium ' + badgeClass + '">' + esc(row.direction_label) + '</span></td>'
                + '<td class="px-4 py-2 text-gray-800">' + esc(source) + '</td>'
                + '<td class="max-w-xs px-4 py-2 text-gray-600">' + esc(row.notes) + '</td>'
                + '<td class="whitespace-nowrap px-4 py-2 text-right font-semibold ' + amountClass + '">' + sign + formatMoney(row.amount) + '</td>'
                + '<td class="whitespace-nowrap px-4 py-2 text-right text-gray-700">' + esc(balance) + '</td>'
                + actionsCell
                + '</tr>';
        }).join('');
    }

    function loadWalletHistory(walletId, options) {
        options = options || {};
        var subtitle = document.getElementById(historyModalId + '-subtitle');
        var tbody = document.getElementById(historyModalId + '-tbody');

        currentWalletId = walletId;

        if (!options.silent) {
            if (subtitle) {
                subtitle.textContent = 'Loading…';
            }
            if (tbody) {
                tbody.innerHTML = '';
            }
            setHistoryState('loading');
        }

        var url = historyUrlTemplate.replace('__WALLET__', String(walletId));

        return fetch(url, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('fetch failed');
                }
                return response.json();
            })
            .then(function (payload) {
                var wallet = payload.wallet || {};
                var canManage = !!(payload.permissions && payload.permissions.can_manage);

                if (subtitle) {
                    subtitle.textContent = walletSubtitle(wallet);
                }

                var rows = payload.transactions || [];
                if (!rows.length) {
                    setHistoryState('empty');
                    return payload;
                }

                renderHistoryRows(rows, canManage);
                setHistoryState('table-wrap');
                var footnote = document.getElementById(historyModalId + '-footnote');
                if (footnote) {
                    footnote.classList.remove('hidden');
                }

                return payload;
            })
            .catch(function () {
                if (!options.silent) {
                    setHistoryState('error');
                    if (subtitle) {
                        subtitle.textContent = 'Could not load wallet history.';
                    }
                }
                throw new Error('history load failed');
            });
    }

    window.openWalletHistoryModal = function (walletId) {
        if (typeof window.openAppModal === 'function') {
            window.openAppModal(historyModalId);
        }
        loadWalletHistory(walletId);
    };

    function findRowInPayload(payload, transactionId) {
        var rows = (payload && payload.transactions) || [];
        for (var i = 0; i < rows.length; i++) {
            if (String(rows[i].id) === String(transactionId)) {
                return rows[i];
            }
        }
        return null;
    }

    function openEditModal(row) {
        if (!row || !canManageLedger) {
            return;
        }

        pendingEditRow = row;

        var summary = document.getElementById(editModalId + '-summary');
        var err = document.getElementById(editModalId + '-error');
        var amountInput = document.getElementById(editModalId + '-amount');
        var categoryWrap = document.getElementById(editModalId + '-category-wrap');
        var categorySelect = document.getElementById(editModalId + '-category');
        var referenceInput = document.getElementById(editModalId + '-reference');
        var notesInput = document.getElementById(editModalId + '-notes');

        if (err) {
            err.classList.add('hidden');
            err.textContent = '';
        }
        if (summary) {
            summary.textContent = row.direction_label + ' · ' + (row.source_label || 'Wallet movement') + ' · ' + row.occurred_at;
        }
        if (amountInput) {
            amountInput.value = row.amount;
        }
        if (categoryWrap) {
            categoryWrap.classList.toggle('hidden', row.direction !== 'in');
        }
        if (categorySelect && row.category) {
            categorySelect.value = row.category;
        }
        if (referenceInput) {
            referenceInput.value = row.reference_raw || '';
        }
        if (notesInput) {
            notesInput.value = row.notes_raw || '';
        }

        if (typeof window.openAppModal === 'function') {
            window.openAppModal(editModalId);
        }
    }

    function openDeleteModal(row) {
        if (!row || !canManageLedger) {
            return;
        }

        pendingDeleteRow = row;

        var summary = document.getElementById(deleteModalId + '-summary');
        var err = document.getElementById(deleteModalId + '-error');
        if (err) {
            err.classList.add('hidden');
            err.textContent = '';
        }
        if (summary) {
            var sign = row.direction === 'in' ? '+' : '−';
            summary.textContent = row.occurred_at + ' · ' + sign + formatMoney(row.amount) + ' · ' + (row.source_label || 'Movement');
        }

        if (typeof window.openAppModal === 'function') {
            window.openAppModal(deleteModalId);
        }
    }

    document.addEventListener('click', function (event) {
        var editBtn = event.target.closest('[data-wallet-tx-edit]');
        if (editBtn && currentWalletId) {
            var txId = editBtn.getAttribute('data-wallet-tx-edit');
            loadWalletHistory(currentWalletId, { silent: true }).then(function (payload) {
                openEditModal(findRowInPayload(payload, txId));
            });
            return;
        }

        var deleteBtn = event.target.closest('[data-wallet-tx-delete]');
        if (deleteBtn && currentWalletId) {
            var delId = deleteBtn.getAttribute('data-wallet-tx-delete');
            loadWalletHistory(currentWalletId, { silent: true }).then(function (payload) {
                openDeleteModal(findRowInPayload(payload, delId));
            });
        }
    });

    var saveBtn = document.getElementById(editModalId + '-save');
    if (saveBtn) {
        saveBtn.addEventListener('click', function () {
            if (!pendingEditRow || !currentWalletId || !updateUrlTemplate) {
                return;
            }

            var err = document.getElementById(editModalId + '-error');
            var amountInput = document.getElementById(editModalId + '-amount');
            var categorySelect = document.getElementById(editModalId + '-category');
            var referenceInput = document.getElementById(editModalId + '-reference');
            var notesInput = document.getElementById(editModalId + '-notes');

            var body = {
                amount: amountInput ? amountInput.value : '',
                reference: referenceInput ? referenceInput.value : '',
                notes: notesInput ? notesInput.value : '',
            };

            if (pendingEditRow.direction === 'in' && categorySelect) {
                body.category = categorySelect.value;
            }

            var url = updateUrlTemplate
                .replace('__WALLET__', String(currentWalletId))
                .replace('__TX__', String(pendingEditRow.id));

            saveBtn.disabled = true;

            fetch(url, {
                method: 'PUT',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify(body),
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        if (!response.ok) {
                            throw data;
                        }
                        return data;
                    });
                })
                .then(function () {
                    closeAppModal(editModalId);
                    return loadWalletHistory(currentWalletId, { silent: true });
                })
                .catch(function (data) {
                    if (err) {
                        var message = (data && data.errors && data.errors.amount && data.errors.amount[0])
                            || (data && data.message)
                            || 'Could not save changes.';
                        err.textContent = message;
                        err.classList.remove('hidden');
                    }
                })
                .finally(function () {
                    saveBtn.disabled = false;
                });
        });
    }

    var confirmDeleteBtn = document.getElementById(deleteModalId + '-confirm');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function () {
            if (!pendingDeleteRow || !currentWalletId || !deleteUrlTemplate) {
                return;
            }

            var err = document.getElementById(deleteModalId + '-error');
            var url = deleteUrlTemplate
                .replace('__WALLET__', String(currentWalletId))
                .replace('__TX__', String(pendingDeleteRow.id));

            confirmDeleteBtn.disabled = true;

            fetch(url, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        if (!response.ok) {
                            throw data;
                        }
                        return data;
                    });
                })
                .then(function () {
                    closeAppModal(deleteModalId);
                    pendingDeleteRow = null;
                    return loadWalletHistory(currentWalletId, { silent: true });
                })
                .catch(function (data) {
                    if (err) {
                        var message = (data && data.errors && data.errors.transaction && data.errors.transaction[0])
                            || (data && data.message)
                            || 'Could not remove this transaction.';
                        err.textContent = message;
                        err.classList.remove('hidden');
                    }
                })
                .finally(function () {
                    confirmDeleteBtn.disabled = false;
                });
        });
    }
})();

window.openWalletDepositModal = function (walletId) {
    var select = document.querySelector('#wallet-deposit-modal select[name="payment_wallet_id"]');
    if (select && walletId) {
        select.value = String(walletId);
    }
    if (typeof window.openAppModal === 'function') {
        window.openAppModal('wallet-deposit-modal');
    }
};
document.addEventListener('click', function (e) {
    if (e.target.closest('[data-open-import-wallets]')) {
        e.preventDefault();
        window.openAppModal('import-wallets-modal');
    }
});
@if(session('open_import_wallets_modal') || ($errors->has('csv_file') && !old('payment_wallet_id')))
window.openAppModal('import-wallets-modal');
@endif
@if($errors->any() && (old('payment_wallet_id') || old('category')))
(function () {
    var modal = document.getElementById('wallet-deposit-modal');
    if (modal) window.openAppModal(modal);
})();
@endif
</script>
@endpush
@endsection
