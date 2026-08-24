@props(['variant' => 'primary', 'size' => 'md', 'href' => null, 'type' => 'button'])

@php
    $base = 'inline-flex items-center justify-center font-medium rounded-lg shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';
    $variants = [
        'primary'   => 'bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-500',
        'secondary' => 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:ring-indigo-500',
        'success'   => 'bg-emerald-600 text-white hover:bg-emerald-700 focus:ring-emerald-500',
        'danger'    => 'border border-red-300 bg-white text-red-700 hover:bg-red-50 focus:ring-red-500',
        'ghost'     => 'bg-transparent text-indigo-600 shadow-none hover:bg-indigo-50',
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
