<div {{ $attributes->merge(['class' => 'sortable-table']) }} x-data="sortableTable()">
    {{ $slot }}
</div>
