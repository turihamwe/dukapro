@can('approve-affiliates')
    <form id="affiliate-bulk-approve-form" method="POST" action="{{ route('superadmin.affiliates.bulk-approve') }}" class="hidden" aria-hidden="true">
        @csrf
    </form>

    <div id="affiliate-bulk-approve-bar"
         class="mb-4 hidden flex-col gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
         hidden>
        <p class="text-sm font-medium text-emerald-950" id="affiliate-bulk-approve-summary">
            <span id="affiliate-bulk-approve-count">0</span> affiliate(s) selected
        </p>
        <div class="flex flex-wrap gap-2">
            <button type="button"
                    id="affiliate-bulk-approve-clear"
                    class="rounded-lg border border-emerald-300 bg-white px-4 py-2 text-sm font-semibold text-emerald-900 hover:bg-emerald-100/50">
                Clear selection
            </button>
            <button type="submit"
                    form="affiliate-bulk-approve-form"
                    id="affiliate-bulk-approve-submit"
                    class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-50"
                    onclick="return window.confirmAffiliateBulkApprove && window.confirmAffiliateBulkApprove();">
                Approve pending in selection
            </button>
        </div>
    </div>
@endcan

@push('scripts')
<script>
(function () {
    var bar = document.getElementById('affiliate-bulk-approve-bar');
    var form = document.getElementById('affiliate-bulk-approve-form');
    if (!bar || !form) return;

    var countEl = document.getElementById('affiliate-bulk-approve-count');
    var summaryEl = document.getElementById('affiliate-bulk-approve-summary');
    var selectAll = document.getElementById('affiliate-select-all-pending');
    var clearBtn = document.getElementById('affiliate-bulk-approve-clear');
    var submitBtn = document.getElementById('affiliate-bulk-approve-submit');

    function rowBoxes() {
        return Array.prototype.slice.call(document.querySelectorAll('.affiliate-row-checkbox'));
    }

    function syncBar() {
        var boxes = rowBoxes();
        var checked = boxes.filter(function (box) { return box.checked; });
        var pendingChecked = checked.filter(function (box) { return box.getAttribute('data-pending') === '1'; });

        countEl.textContent = String(checked.length);
        if (checked.length === 0) {
            summaryEl.textContent = '0 affiliate(s) selected';
        } else if (pendingChecked.length === checked.length) {
            summaryEl.innerHTML = '<span id="affiliate-bulk-approve-count">' + checked.length + '</span> affiliate(s) selected';
        } else {
            summaryEl.innerHTML = '<span id="affiliate-bulk-approve-count">' + checked.length + '</span> affiliate(s) selected'
                + ' <span class="font-normal text-emerald-800">(' + pendingChecked.length + ' pending)</span>';
        }

        bar.hidden = checked.length === 0;
        bar.classList.toggle('hidden', checked.length === 0);
        bar.classList.toggle('flex', checked.length > 0);

        if (submitBtn) {
            submitBtn.disabled = pendingChecked.length === 0;
        }

        form.querySelectorAll('input[name="affiliate_ids[]"]').forEach(function (input) { input.remove(); });
        checked.forEach(function (box) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'affiliate_ids[]';
            input.value = box.value;
            form.appendChild(input);
        });

        if (selectAll) {
            selectAll.indeterminate = checked.length > 0 && checked.length < boxes.length;
            selectAll.checked = boxes.length > 0 && checked.length === boxes.length;
        }
    }

    window.confirmAffiliateBulkApprove = function () {
        var checked = rowBoxes().filter(function (box) { return box.checked; });
        var pending = checked.filter(function (box) { return box.getAttribute('data-pending') === '1'; });
        if (pending.length === 0) {
            return false;
        }
        var msg = pending.length === 1
            ? 'Approve the 1 pending affiliate in your selection?'
            : 'Approve ' + pending.length + ' pending affiliate(s) in your selection?';
        if (pending.length < checked.length) {
            msg += ' Other selected rows will be skipped.';
        }
        return confirm(msg);
    };

    rowBoxes().forEach(function (box) {
        box.addEventListener('change', syncBar);
    });

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            rowBoxes().forEach(function (box) { box.checked = selectAll.checked; });
            syncBar();
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            rowBoxes().forEach(function (box) { box.checked = false; });
            if (selectAll) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            }
            syncBar();
        });
    }

    syncBar();
})();
</script>
@endpush
