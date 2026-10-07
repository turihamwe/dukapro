@php
    $confirm = $confirm ?? 'Are you sure you want to delete this item?';
    $label = $label ?? 'Delete';
@endphp

<x-delete-confirm-button
    :action="$action"
    :message="$confirm"
    :label="$label"
/>
