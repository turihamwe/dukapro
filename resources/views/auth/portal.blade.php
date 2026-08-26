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
        <form method="GET" action="#" id="portal-form" class="space-y-5">
            <x-input type="text" name="portal_slug" id="portal_slug" label="Business portal ID" placeholder="e.g. next-level-academy-a1b2c3d4" required large
                     hint="Ask your store owner for your unique portal link." />
            <x-button variant="primary" size="lg" type="submit">Continue to sign in</x-button>
        </form>
        <p class="mt-5 text-center text-xs text-gray-500">
            New business? <a href="{{ route('register') }}" class="text-indigo-600 hover:text-indigo-700">Create an account</a>
        </p>
    </x-card>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('portal-form')?.addEventListener('submit', function (e) {
    e.preventDefault();
    var slug = document.getElementById('portal_slug').value.trim().replace(/^\/+|\/+$/g, '');
    if (!slug) return;
    var base = @json(url('/business'));
    window.location.href = base + '/' + encodeURIComponent(slug) + '/login';
});
</script>
@endpush
