@props([
    /** @var array<string, string> column key => label */
    'columns' => [],
])

<div {{ $attributes->merge(['class' => 'mb-3 flex flex-wrap items-center gap-x-4 gap-y-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs']) }}>
    <span class="font-medium uppercase tracking-wide text-gray-500">Sort</span>
    @foreach($columns as $key => $label)
        <button type="button"
                @click="sort('{{ $key }}')"
                class="inline-flex items-center gap-1 font-medium text-gray-600 transition hover:text-gray-900">
            <span>{{ $label }}</span>
            <span class="min-w-[1ch] text-[10px] font-normal text-gray-400" x-text="indicator('{{ $key }}')"></span>
        </button>
    @endforeach
</div>
