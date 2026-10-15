@php
    $modalId = $modalId ?? 'opening-correction-modal';
    $fieldName = $fieldName ?? 'opening_balance';
    $fieldLabel = $fieldLabel ?? 'New value';
    $step = $step ?? '0.01';
    $title = $title ?? 'Correct opening figure';
    $warning = $warning ?? 'This correction applies only to this record.';
    $currentLabel = $currentLabel ?? 'Current value';
    $entityLabel = session('open_opening_correction') === $modalId
        ? session('opening_correction_entity', $entityLabel ?? '')
        : ($entityLabel ?? '');
    $currentValue = session('open_opening_correction') === $modalId
        ? session('opening_correction_current', $currentValue ?? '')
        : ($currentValue ?? '');
@endphp
<div id="{{ $modalId }}" class="app-modal-overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title">
    <div class="app-modal-panel max-w-lg">
        <form method="POST" action="{{ old('_correction_action', $action ?? '#') }}" class="flex min-h-0 flex-1 flex-col">
            @csrf
            <input type="hidden" name="_correction_action" value="{{ old('_correction_action', $action ?? '') }}">
            <div class="app-modal-header">
                <div>
                    <h2 id="{{ $modalId }}-title" class="text-lg font-semibold text-gray-900">{{ $title }}</h2>
                    <p class="mt-0.5 text-sm text-gray-500" data-correction-entity>{{ $entityLabel }}</p>
                </div>
                <button type="button" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600" onclick="closeAppModal('{{ $modalId }}')">&times;</button>
            </div>
            <div class="app-modal-body space-y-4">
                <p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-950">{{ $warning }}</p>
                <p class="text-sm text-gray-600">
                    {{ $currentLabel }}:
                    <span class="font-semibold text-gray-900" data-correction-current>{{ $currentValue }}</span>
                </p>
                <div>
                    <label for="{{ $modalId }}-value" class="mb-1 block text-sm font-medium text-gray-700">{{ $fieldLabel }}</label>
                    <input type="number" step="{{ $step }}" min="0" name="{{ $fieldName }}" id="{{ $modalId }}-value" required
                           data-correction-value
                           value="{{ old($fieldName, $inputValue ?? '') }}"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error($fieldName)
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="{{ $modalId }}-reason" class="mb-1 block text-sm font-medium text-gray-700">Reason / description <span class="text-red-600">*</span></label>
                    <textarea name="reason" id="{{ $modalId }}-reason" rows="3" required minlength="3" maxlength="2000"
                              placeholder="Why this opening figure is being corrected"
                              class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('reason') }}</textarea>
                    @error('reason')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="app-modal-footer">
                <x-button variant="secondary" type="button" onclick="closeAppModal('{{ $modalId }}')">Cancel</x-button>
                <x-button variant="primary" type="submit">Save correction</x-button>
            </div>
        </form>
    </div>
</div>
@once
<script>
(function () {
    if (window.__openingCorrectionBound) return;
    window.__openingCorrectionBound = true;

    window.openOpeningCorrectionModal = function (trigger) {
        var modalId = trigger.getAttribute('data-modal');
        var modal = document.getElementById(modalId);
        if (!modal || typeof window.openAppModal !== 'function') return;
        var form = modal.querySelector('form');
        var action = trigger.getAttribute('data-action') || '';
        if (form && action) {
            form.action = action;
            var hidden = form.querySelector('[name="_correction_action"]');
            if (hidden) hidden.value = action;
        }
        var entity = modal.querySelector('[data-correction-entity]');
        if (entity) entity.textContent = trigger.getAttribute('data-entity') || '';
        var current = modal.querySelector('[data-correction-current]');
        if (current) current.textContent = trigger.getAttribute('data-current') || '';
        var input = modal.querySelector('[data-correction-value]');
        if (input && trigger.hasAttribute('data-value')) {
            input.value = trigger.getAttribute('data-value');
        }
        var reason = modal.querySelector('[name="reason"]');
        if (reason) reason.value = '';
        window.openAppModal(modalId);
    };

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-open-opening-correction]');
        if (!trigger) return;
        event.preventDefault();
        window.openOpeningCorrectionModal(trigger);
    });
})();
</script>
@endonce
@if(session('open_opening_correction') === $modalId)
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.openAppModal === 'function') {
        window.openAppModal(@json($modalId));
    }
});
</script>
@endif
