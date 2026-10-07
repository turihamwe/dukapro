@php
    $wallets = $wallets ?? collect();
    $name = $name ?? 'payment_wallet_id';
    $label = $label ?? 'Account / wallet';
    $required = $required ?? $wallets->isNotEmpty();
    $selected = old($name);
@endphp

@if($wallets->isNotEmpty())
    <x-select :name="$name" :label="$label" :required="$required">
        @unless($required)
            <option value="">— None —</option>
        @endunless
        @foreach($wallets as $wallet)
            <option value="{{ $wallet->id }}" @selected((string) $selected === (string) $wallet->id)>
                {{ $wallet->name }} ({{ $wallet->typeLabel() }}) · @money($wallet->current_balance)
            </option>
        @endforeach
    </x-select>
    <p class="mt-1 text-xs text-gray-500">Internal tracking only — not linked to your mobile money or bank provider.</p>
@endif
