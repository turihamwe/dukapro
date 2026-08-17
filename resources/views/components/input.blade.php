@props(['label' => null, 'hint' => null, 'large' => false])

<div {{ $attributes->only('class')->merge(['class' => '']) }}>
    @if($label)
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $label }}</label>
    @endif
    <input {{ $attributes->except('class', 'label', 'hint', 'large')->merge([
        'class' => 'block w-full rounded-lg border-gray-300 shadow-sm transition focus:border-indigo-500 focus:ring-indigo-500 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white ' . ($large ? 'py-3 text-base' : 'py-2'),
    ]) }} />
    @if($hint)
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
    @endif
</div>
