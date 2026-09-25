@props([
    'column',
    'align' => 'left',
])

@php
    $alignClasses = [
        'left' => 'justify-start text-left',
        'center' => 'justify-center text-center',
        'right' => 'justify-end text-right',
    ];
    $btnAlign = $alignClasses[$align] ?? $alignClasses['left'];
@endphp

<th {{ $attributes->class(['select-none']) }}>
    <button type="button"
            @click="sort('{{ $column }}')"
            class="group inline-flex w-full items-center gap-1 text-xs font-medium uppercase tracking-wider text-gray-500 transition hover:text-gray-800 {{ $btnAlign }}">
        <span>{{ $slot }}</span>
        <span class="min-w-[1ch] text-[10px] font-normal text-gray-400 group-hover:text-gray-600" x-text="indicator('{{ $column }}')"></span>
    </button>
</th>
