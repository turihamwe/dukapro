@php
    $subtitle = $subtitle ?? null;
    $dark = $dark ?? false;
    $subtitleClass = $dark ? 'text-slate-400' : 'text-gray-500';
@endphp
<div class="flex min-w-0 items-center gap-3">
    <x-dukapro-logo size="{{ $size ?? 'sidebar' }}" class="shrink-0" />
    @if($subtitle)
        <p class="min-w-0 truncate text-xs {{ $subtitleClass }}">{{ $subtitle }}</p>
    @endif
</div>
