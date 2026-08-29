@props([
    'label',
    'value',
    'footer' => null,
    'footerClass' => 'text-gray-500',
    'modalId' => null,
    'modalTitle' => null,
])

<div {{ $attributes->merge(['class' => 'relative rounded-2xl border border-gray-100 bg-white p-5 shadow-sm']) }}>
    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ $label }}</p>
    <p class="mt-2 text-2xl font-bold text-gray-900">{{ $value }}</p>
    @if($footer)
        <p class="mt-2 text-xs font-medium {{ $footerClass }}">{!! $footer !!}</p>
    @endif

    @if($modalId && $modalTitle)
        <button type="button"
                class="dashboard-drilldown-btn absolute bottom-3 right-3 rounded-md p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                data-modal="{{ $modalId }}"
                aria-label="View {{ $label }} details">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
            </svg>
        </button>
    @endif
</div>
