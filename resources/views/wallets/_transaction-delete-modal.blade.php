@php
    $modalId = 'wallet-transaction-delete-modal';
@endphp
<div id="{{ $modalId }}" class="app-modal-overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title">
    <div class="app-modal-panel max-w-md">
        <div class="app-modal-header">
            <h2 id="{{ $modalId }}-title" class="text-lg font-semibold text-gray-900">Remove transaction?</h2>
            <button type="button" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600" onclick="closeAppModal('{{ $modalId }}')">&times;</button>
        </div>
        <div class="app-modal-body space-y-3">
            <p class="text-sm text-gray-600">This will soft-delete the ledger entry and reverse its effect on the wallet balance. The record stays in the system audit trail.</p>
            <p id="{{ $modalId }}-summary" class="rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-800">—</p>
            <p id="{{ $modalId }}-error" class="hidden text-sm text-red-600"></p>
        </div>
        <div class="app-modal-footer">
            <x-button variant="secondary" type="button" onclick="closeAppModal('{{ $modalId }}')">Cancel</x-button>
            <x-button variant="danger" type="button" id="{{ $modalId }}-confirm">Remove transaction</x-button>
        </div>
    </div>
</div>
