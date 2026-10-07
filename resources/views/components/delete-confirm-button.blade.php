@props([
    'action',
    'method' => 'DELETE',
    'message' => 'Are you sure you want to delete this item?',
    'detail' => null,
    'label' => 'Delete',
    'buttonClass' => 'text-sm font-medium text-red-600 hover:text-red-800',
])

<form method="POST" action="{{ $action }}" {{ $attributes->merge(['class' => 'inline']) }}>
    @csrf
    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif
    <button
        type="button"
        class="{{ $buttonClass }}"
        title="{{ $label }}"
        data-delete-confirm
        data-delete-message="{{ $message }}"
        @if($detail) data-delete-detail="{{ $detail }}" @endif
    >
        {{ $label }}
    </button>
</form>
