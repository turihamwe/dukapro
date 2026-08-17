@props(['padding' => true])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-900 shadow-sm rounded-xl border border-gray-100 dark:border-gray-800 ' . ($padding ? 'p-6 sm:p-8' : '')]) }}>
    {{ $slot }}
</div>
