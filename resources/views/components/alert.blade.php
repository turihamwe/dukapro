@props(['type' => 'info', 'dismissible' => true])

@php
    $styles = [
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
        'error'   => 'border-red-200 bg-red-50 text-red-800',
        'info'    => 'border-indigo-200 bg-indigo-50 text-indigo-800',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'alert-banner relative rounded-xl border px-4 py-3 pr-10 text-sm ' . ($styles[$type] ?? $styles['info'])]) }} role="alert">
    {{ $slot }}
    @if($dismissible)
        <button type="button" class="alert-dismiss absolute right-3 top-3 rounded p-0.5 opacity-60 transition hover:opacity-100" aria-label="Dismiss">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    @endif
</div>
