@props([
    'id',
    'label' => null,
    'options' => [],
    'required' => false,
    'placeholder' => 'Select…',
    'emptyMessage' => null,
    'value' => '',
    'tabThreshold' => 5,
    'variant' => 'indigo',
])

@php
    $optionList = collect($options);
    $useTabs = $optionList->isNotEmpty() && $optionList->count() <= $tabThreshold;
    $activeTab = 'border-indigo-600 bg-indigo-600 text-white';
    $inactiveTab = 'border-gray-200 bg-white text-gray-700 hover:border-gray-300';
    if ($variant === 'emerald') {
        $activeTab = 'border-emerald-600 bg-emerald-600 text-white';
        $inactiveTab = 'border-gray-200 bg-white text-gray-700 hover:border-emerald-200';
    }
@endphp

<div class="choice-picker" data-choice-picker-id="{{ $id }}" data-choice-variant="{{ $variant }}">
    @if($label)
        <label class="mb-1.5 block text-sm font-medium text-gray-700">
            {{ $label }}@if($required)<span class="text-red-500"> *</span>@endif
        </label>
    @endif

    @if($optionList->isEmpty())
        @if($emptyMessage)
            <p class="rounded-lg border border-dashed border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500">{{ $emptyMessage }}</p>
        @endif
        <input type="hidden" id="{{ $id }}" value="">
    @elseif($useTabs)
        <input type="hidden" id="{{ $id }}" value="{{ $value }}" @if($required) data-required="1" @endif>
        <div class="flex flex-wrap gap-1.5" role="tablist" aria-label="{{ $label ?? $id }}">
            @foreach($optionList as $optionValue => $optionLabel)
                @php $isSelected = (string) $value === (string) $optionValue; @endphp
                <button type="button" role="tab" aria-selected="{{ $isSelected ? 'true' : 'false' }}"
                        data-choice-value="{{ $optionValue }}"
                        title="{{ $optionLabel }}"
                        class="choice-tab inline-flex max-w-full items-center rounded-full border px-2.5 py-1 text-xs font-medium leading-tight transition {{ $isSelected ? $activeTab : $inactiveTab }}">
                    <span class="max-w-[7rem] truncate sm:max-w-[8.5rem]">{{ $optionLabel }}</span>
                </button>
            @endforeach
        </div>
    @else
        <select id="{{ $id }}" @if($required) required @endif
                class="block w-full rounded-lg border-gray-300 shadow-sm transition focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2">
            <option value="">{{ $placeholder }}</option>
            @foreach($optionList as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
            @endforeach
        </select>
    @endif
</div>

@once
@push('scripts')
<script>
(function () {
    var activeClasses = {
        indigo: ['border-indigo-600', 'bg-indigo-600', 'text-white'],
        emerald: ['border-emerald-600', 'bg-emerald-600', 'text-white'],
    };
    var inactiveClasses = ['border-gray-200', 'bg-white', 'text-gray-700', 'hover:border-gray-300'];

    function syncChoiceTabStyles(picker, selectedValue) {
        var variant = picker.getAttribute('data-choice-variant') || 'indigo';
        var active = activeClasses[variant] || activeClasses.indigo;
        picker.querySelectorAll('.choice-tab').forEach(function (btn) {
            var selected = btn.getAttribute('data-choice-value') === selectedValue;
            btn.setAttribute('aria-selected', selected ? 'true' : 'false');
            inactiveClasses.concat(active).forEach(function (cls) { btn.classList.remove(cls); });
            (selected ? active : inactiveClasses).forEach(function (cls) { btn.classList.add(cls); });
        });
    }

    window.resetChoicePicker = function (id) {
        var input = document.getElementById(id);
        if (!input) return;
        input.value = '';
        var picker = input.closest('.choice-picker');
        if (picker) syncChoiceTabStyles(picker, '');
    };

    document.addEventListener('click', function (e) {
        var tab = e.target.closest('.choice-tab');
        if (!tab) return;
        var picker = tab.closest('.choice-picker');
        if (!picker) return;
        var input = picker.querySelector('input[type="hidden"]');
        if (!input) return;
        var value = tab.getAttribute('data-choice-value');
        input.value = value;
        syncChoiceTabStyles(picker, value);
    });
})();
</script>
@endpush
@endonce
