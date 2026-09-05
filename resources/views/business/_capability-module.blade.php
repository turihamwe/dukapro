@php
    $moduleKey = $moduleKey ?? '';
    $capability = $capability ?? [];
    $moduleEnabled = (bool) old("modules.{$moduleKey}.enabled", $capability['enabled'] ?? false);
    $styles = $styles ?? [
        'border' => 'border-gray-200',
        'bg' => 'bg-gray-50',
        'checkbox' => 'text-gray-700 focus:ring-gray-500',
        'badge' => 'bg-gray-100 text-gray-800',
    ];
    $footnote = $footnote ?? null;
    $sourceLabel = $sourceLabel ?? null;
@endphp

<div class="rounded-xl border {{ $styles['border'] }} {{ $styles['bg'] }} p-4">
    <label class="flex items-start gap-3">
        <input type="hidden" name="modules[{{ $moduleKey }}][enabled]" value="0">
        <input type="checkbox" name="modules[{{ $moduleKey }}][enabled]" value="1"
               class="mt-1 rounded border-gray-300 {{ $styles['checkbox'] }}"
               {{ $moduleEnabled ? 'checked' : '' }}>
        <span class="min-w-0 flex-1">
            <span class="flex flex-wrap items-center gap-2">
                <span class="text-sm font-semibold text-gray-900">{{ $capability['label'] ?? $moduleKey }}</span>
                @if($sourceLabel)
                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-600">{{ $sourceLabel }}</span>
                @endif
            </span>
            <span class="mt-1 block text-xs text-gray-600">{{ $capability['description'] ?? '' }}</span>
            @if($capability['suggested'] ?? false)
                <span class="mt-1 inline-block rounded-full {{ $styles['badge'] }} px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide">Suggested for {{ $businessTypeLabel ?? 'your business type' }}</span>
            @endif
            @if($footnote)
                <span class="mt-2 block text-xs text-gray-500">{{ $footnote }}</span>
            @endif
        </span>
    </label>
</div>
