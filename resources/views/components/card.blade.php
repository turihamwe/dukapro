@props(['padding' => true])

<div {{ $attributes->merge(['class' => 'bg-white shadow-sm rounded-xl border border-gray-100 ' . ($padding ? 'p-6 sm:p-8' : '')]) }}>
    {{ $slot }}
</div>
