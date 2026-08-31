@props([
    'size' => 'md',
    'centered' => false,
    'href' => null,
])

@php
    $sizeClasses = [
        'sm' => 'max-h-8 w-auto max-w-[120px]',
        'md' => 'max-h-10 w-auto max-w-[140px]',
        'sidebar' => 'max-h-12 w-auto max-w-[180px] sm:max-w-[196px]',
        'header' => 'max-h-9 w-auto max-w-[132px] sm:max-w-[148px]',
        'auth' => 'h-auto w-full max-w-[min(100%,300px)] sm:max-h-[4.5rem] sm:w-auto sm:max-w-[320px]',
        'cashier' => 'max-h-10 w-auto max-w-[150px] sm:max-w-[168px]',
    ];
    $objectPosition = $centered ? 'object-center' : 'object-left';
    $imgClass = ($sizeClasses[$size] ?? $sizeClasses['md']) . ' shrink-0 object-contain ' . $objectPosition . ($centered ? ' mx-auto' : '');
    $wrapperClass = trim(
        ($centered ? 'flex w-full justify-center ' : 'inline-flex items-center ')
        . 'min-w-0 max-w-full '
        . ($attributes->get('class') ?? '')
    );
    $logoUrl = dukapro_logo_url();
    $brandName = platform_brand('name');
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->except('class')->merge(['class' => $wrapperClass]) }}>
@else
    <span {{ $attributes->except('class')->merge(['class' => $wrapperClass]) }}>
@endif
    @if($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ $brandName }}" class="{{ $imgClass }}" width="320" height="80" decoding="async">
    @else
        <span class="inline-flex {{ $size === 'auth' ? 'h-12 w-12 sm:h-14 sm:w-14' : 'h-9 w-9' }} shrink-0 items-center justify-center rounded-xl bg-emerald-500 text-sm font-bold text-white shadow-lg shadow-emerald-500/30">
            D
        </span>
    @endif
@if($href)
    </a>
@else
    </span>
@endif
