@props(['variant' => 'primary', 'size' => 'md', 'href' => null, 'type' => 'button'])

@php
    $base = 'inline-flex items-center justify-center font-medium rounded-lg shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';
    $variants = [
        'primary'   => 'bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-500 dark:focus:ring-offset-gray-900',
        'secondary' => 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:focus:ring-offset-gray-900',
        'success'   => 'bg-emerald-600 text-white hover:bg-emerald-700 focus:ring-emerald-500 dark:focus:ring-offset-gray-900',
        'danger'    => 'border border-red-300 bg-white text-red-700 hover:bg-red-50 focus:ring-red-500 dark:border-red-800 dark:bg-gray-800 dark:text-red-400',
        'ghost'     => 'bg-transparent text-indigo-600 shadow-none hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-950',
    ];
    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'w-full px-4 py-3 text-sm',
    ];
    $classes = $base . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
