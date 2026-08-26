@props(['label' => null, 'hint' => null, 'large' => false])

<div {{ $attributes->only('class')->merge(['class' => '']) }}>
    @if($label)
        <label class="mb-1.5 block text-sm font-medium text-gray-700">{{ $label }}</label>
    @endif
    <div class="relative">
        <input
            {{ $attributes->except('class', 'label', 'hint', 'large')->merge([
                'type' => 'password',
                'class' => 'block w-full rounded-lg border-gray-300 pr-10 shadow-sm transition focus:border-indigo-500 focus:ring-indigo-500 text-sm ' . ($large ? 'py-3 text-base' : 'py-2'),
            ]) }}
        />
        <button type="button" class="password-toggle absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-gray-600" tabindex="-1" aria-label="Toggle password visibility">
            <svg class="eye-open h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            <svg class="eye-closed hidden h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
        </button>
    </div>
    @if($hint)
        <p class="mt-1.5 text-xs text-gray-500">{{ $hint }}</p>
    @endif
</div>
