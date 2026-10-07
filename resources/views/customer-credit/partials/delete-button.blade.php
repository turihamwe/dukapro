@php
    $confirm = $confirm ?? 'Are you sure you want to delete this record?';
    $label = $label ?? 'Delete';
@endphp

<form method="POST"
      action="{{ $action }}"
      class="inline"
      onsubmit="return confirm(@json($confirm));">
    @csrf
    @method('DELETE')
    <button type="submit"
            class="text-sm font-medium text-red-600 hover:text-red-800"
            title="{{ $label }}">
        {{ $label }}
    </button>
</form>
