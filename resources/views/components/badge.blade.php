@props(['color' => 'gray'])

@php
    $colors = [
        'gray'    => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
        'green'   => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300',
        'red'     => 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
        'amber'   => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300',
        'indigo'  => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ' . ($colors[$color] ?? $colors['gray'])]) }}>
    {{ $slot }}
</span>
