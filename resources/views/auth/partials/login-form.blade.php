@php
    $brandColor = $brandColor ?? '#4f46e5';
    $portalHint = $portalHint ?? null;
@endphp

<form method="POST" action="{{ $action }}" class="space-y-4">
    @csrf
    <x-input
        type="text"
        name="login"
        label="Username or Email"
        value="{{ old('login') }}"
        required
        autofocus
        large
        {{-- hint="Enter your username or email address." --}}
    />
    <x-input type="password" name="password" label="Password" required large />
    <label class="flex items-center gap-2 text-sm text-gray-600">
        <input type="checkbox" name="remember" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" {{ old('remember') ? 'checked' : '' }}>
        Remember me
    </label>
    <x-button variant="primary" size="lg" type="submit" class="w-full" style="background-color: {{ $brandColor }}">Sign In</x-button>
</form>

@if($portalHint)
    <p class="mt-4 text-center text-xs text-gray-500">{{ $portalHint }}</p>
@endif
