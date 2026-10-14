@props(['padding' => true])

<div {{ $attributes->merge(['class' => 'bg-white shadow-sm rounded-xl border border-gray-100 dark:border-slate-600 dark:bg-slate-800 ' . ($padding ? 'p-6 sm:p-8' : '')]) }}>
    {{ $slot }}
</div>
