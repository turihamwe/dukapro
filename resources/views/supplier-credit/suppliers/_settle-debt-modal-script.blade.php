@php
    $settleDebtPayloads = $settleDebtPayloads ?? [];
@endphp
<script>
window.vendorSettleDebtPayloads = @json($settleDebtPayloads);
(function () {
    var modalId = 'vendor-settle-debt-modal';
    var form = document.getElementById('vendor-settle-debt-form');
    var amountInput = document.getElementById('vendor-settle-debt-amount');
    var previewEl = document.getElementById('vendor-settle-debt-preview');
    var previewEmpty = document.getElementById('vendor-settle-debt-preview-empty');
    var nameEl = document.getElementById('vendor-settle-debt-name');
    var balanceEl = document.getElementById('vendor-settle-debt-balance');
    var bills = [];
    var maxBalance = 0;

    function money(n) {
        var v = parseFloat(n);
        if (isNaN(v)) return '0';
        return v.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function planFifo(amount) {
        var remaining = Math.round(Math.max(0, amount) * 100) / 100;
        var rows = [];
        for (var i = 0; i < bills.length; i++) {
            if (remaining <= 0) break;
            var due = parseFloat(bills[i].balance_due) || 0;
            if (due <= 0) continue;
            var apply = Math.min(remaining, due);
            apply = Math.round(apply * 100) / 100;
            if (apply <= 0) continue;
            rows.push({
                reference: bills[i].reference,
                purchase_date: bills[i].purchase_date,
                amount: apply,
                balance_after: Math.round((due - apply) * 100) / 100
            });
            remaining = Math.round((remaining - apply) * 100) / 100;
        }
        return rows;
    }

    function renderPreview() {
        if (!previewEl || !previewEmpty) return;
        var amount = parseFloat(amountInput && amountInput.value ? amountInput.value : '0');
        if (!amount || amount <= 0 || !bills.length) {
            previewEl.innerHTML = '';
            previewEmpty.classList.remove('hidden');
            return;
        }
        previewEmpty.classList.add('hidden');
        var rows = planFifo(amount);
        if (!rows.length) {
            previewEl.innerHTML = '<p class="p-3 text-xs text-gray-500">No open bills to apply this payment to.</p>';
            return;
        }
        var html = '<table class="min-w-full divide-y divide-gray-200 text-xs"><thead class="bg-gray-100 text-left text-gray-600"><tr>' +
            '<th class="px-3 py-2 font-medium">Bill</th><th class="px-3 py-2 font-medium">Date</th>' +
            '<th class="px-3 py-2 font-medium text-right">Apply</th><th class="px-3 py-2 font-medium text-right">Remaining</th></tr></thead><tbody class="divide-y divide-gray-100 bg-white">';
        rows.forEach(function (row) {
            html += '<tr><td class="px-3 py-2 text-gray-900">' + escapeHtml(row.reference) + '</td>' +
                '<td class="px-3 py-2 text-gray-600">' + escapeHtml(row.purchase_date) + '</td>' +
                '<td class="px-3 py-2 text-right font-medium text-emerald-800">' + money(row.amount) + '</td>' +
                '<td class="px-3 py-2 text-right text-gray-600">' + money(row.balance_after) + '</td></tr>';
        });
        html += '</tbody></table>';
        var applied = rows.reduce(function (s, r) { return s + r.amount; }, 0);
        applied = Math.round(applied * 100) / 100;
        if (amount > applied + 0.009) {
            html += '<p class="border-t border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">Only ' + money(applied) + ' can be applied; amount exceeds open balance.</p>';
        }
        previewEl.innerHTML = html;
    }

    function escapeHtml(str) {
        return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
    }

    window.openVendorSettleDebtModal = function (supplierId) {
        var payloads = window.vendorSettleDebtPayloads || {};
        var payload = typeof supplierId === 'object' && supplierId !== null
            ? supplierId
            : payloads[String(supplierId)] || payloads[supplierId];

        if (!payload) {
            console.error('Settle debt: no payload for vendor', supplierId);
            return;
        }

        bills = payload.bills || [];
        maxBalance = parseFloat(payload.open_balance) || 0;
        if (form) {
            form.action = payload.action_url || '#';
        }
        if (nameEl) nameEl.textContent = payload.name || 'Vendor';
        if (balanceEl) balanceEl.textContent = payload.open_balance_formatted || money(maxBalance);
        if (amountInput) {
            amountInput.value = maxBalance > 0 ? maxBalance.toFixed(2) : '';
            amountInput.max = maxBalance > 0 ? maxBalance.toFixed(2) : '';
        }
        renderPreview();

        var modalEl = document.getElementById(modalId);
        if (!modalEl) {
            console.error('Settle debt: modal element missing');
            return;
        }

        if (typeof window.openAppModal === 'function') {
            window.openAppModal(modalId);
        } else {
            modalEl.classList.add('is-open');
            document.body.classList.add('app-modal-open');
        }
    };

    if (amountInput) {
        amountInput.addEventListener('input', renderPreview);
        amountInput.addEventListener('change', renderPreview);
    }
})();
</script>
