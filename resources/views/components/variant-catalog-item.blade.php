@props(['variant'])

@php
    $filterPayload = json_encode($variant->attribute_values ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
@endphp

<div {{ $attributes->merge(['class' => 'variant-catalog-item']) }} data-variant-filters="{{ $filterPayload }}">
    {{ $slot }}
</div>
