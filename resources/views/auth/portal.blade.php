@extends('layouts.app')

@section('title', 'Business Portal')
@section('container_class', 'max-w-lg')

@section('content')
<div class="py-8 sm:py-12">
    <div class="mb-8 text-center">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-600 text-lg font-bold text-white shadow-lg shadow-indigo-600/30">D</div>
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Sign in to your store</h1>
        <p class="mt-1 text-sm text-gray-500">Each business has a private login URL. Enter yours below.</p>
    </div>

    <x-card>
        @if($errors->has('portal_slug'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ $errors->first('portal_slug') }}
            </div>
        @endif
        <form method="GET" action="#" id="portal-form" class="space-y-5">
            <x-input type="text" name="portal_slug" id="portal_slug" label="Business portal ID" placeholder="e.g. next-level-academy-a1b2c3d4" required large
                     value="{{ old('portal_slug') }}"
                     hint="Paste your full portal link or just the portal ID from your store owner." />
            <x-button variant="primary" size="lg" type="submit">Continue to sign in</x-button>
        </form>
        <p class="mt-5 text-center text-xs text-gray-500">
            <a href="{{ route('login') }}" class="text-indigo-600 hover:text-indigo-700">Sign in with username or email</a>
            ·
            New business? <a href="{{ route('register') }}" class="text-indigo-600 hover:text-indigo-700">Create an account</a>
        </p>
    </x-card>
</div>
@endsection

@push('scripts')
<script>
function extractPortalSlug(input) {
    var value = (input || '').trim().replace(/^\/+|\/+$/g, '');
    if (!value) return '';

    try {
        if (/^https?:\/\//i.test(value)) {
            var url = new URL(value);
            var match = url.pathname.match(/\/business\/([^/]+)\/login\/?$/i);
            if (match) return decodeURIComponent(match[1]);
            var parts = url.pathname.split('/').filter(Boolean);
            return decodeURIComponent(parts[parts.length - 1] === 'login' ? parts[parts.length - 2] : parts[parts.length - 1] || '');
        }
    } catch (e) {}

    var pathMatch = value.match(/\/business\/([^/]+)(?:\/login)?\/?$/i);
    if (pathMatch) return decodeURIComponent(pathMatch[1]);

    return value.replace(/\/login\/?$/i, '');
}

document.getElementById('portal-form')?.addEventListener('submit', function (e) {
    e.preventDefault();
    var slug = extractPortalSlug(document.getElementById('portal_slug').value);
    if (!slug) return;
    var base = @json(url('/business'));
    window.location.href = base + '/' + encodeURIComponent(slug) + '/login';
});
</script>
@endpush
