@php
    $modalId = 'wallet-history-modal';
@endphp
<div id="{{ $modalId }}" class="app-modal-overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title">
    <div class="app-modal-panel flex max-h-[min(90vh,720px)] w-full max-w-5xl flex-col">
        <div class="app-modal-header shrink-0">
            <div>
                <h2 id="{{ $modalId }}-title" class="text-lg font-semibold text-gray-900">Transaction history</h2>
                <p id="{{ $modalId }}-subtitle" class="mt-0.5 text-sm text-gray-500">—</p>
            </div>
            <button type="button" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600" onclick="closeAppModal('{{ $modalId }}')">&times;</button>
        </div>
        <div class="app-modal-body min-h-0 flex-1 overflow-y-auto !py-0">
            <div id="{{ $modalId }}-loading" class="hidden px-4 py-8 text-center text-sm text-gray-500">Loading transactions…</div>
            <div id="{{ $modalId }}-empty" class="hidden px-4 py-8 text-center text-sm text-gray-500">No transactions recorded for this wallet yet.</div>
            <div id="{{ $modalId }}-error" class="hidden px-4 py-8 text-center text-sm text-red-600">Could not load history. Please try again.</div>
            <div id="{{ $modalId }}-table-wrap" class="hidden overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="sticky top-0 bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-2.5">Date &amp; time</th>
                            <th class="px-4 py-2.5">Type</th>
                            <th class="px-4 py-2.5">Source / reference</th>
                            <th class="px-4 py-2.5">Notes</th>
                            <th class="px-4 py-2.5 text-right">Amount</th>
                            <th class="px-4 py-2.5 text-right">Balance after</th>
                            <th id="{{ $modalId }}-actions-head" class="hidden px-4 py-2.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="{{ $modalId }}-tbody" class="divide-y divide-gray-50 bg-white"></tbody>
                </table>
            </div>
            <p id="{{ $modalId }}-footnote" class="hidden border-t border-gray-100 px-4 py-2 text-xs text-gray-500">Showing the latest 50 transactions.</p>
        </div>
        <div class="app-modal-footer shrink-0">
            <x-button variant="secondary" type="button" onclick="closeAppModal('{{ $modalId }}')">Close</x-button>
        </div>
    </div>
</div>
