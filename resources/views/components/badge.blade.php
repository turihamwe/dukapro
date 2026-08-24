@props(['color' => 'gray'])

@php
    $colors = [
        'gray'    => 'bg-gray-100 text-gray-700',
        'green'   => 'bg-emerald-100 text-emerald-700',
        'red'     => 'bg-red-100 text-red-700',
        'amber'   => 'bg-amber-100 text-amber-800',
        'indigo'  => 'bg-indigo-100 text-indigo-700',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ' . ($colors[$color] ?? $colors['gray'])]) }}>
    {{ $slot }}
</span>
