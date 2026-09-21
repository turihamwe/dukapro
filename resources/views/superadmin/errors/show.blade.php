@extends('layouts.superadmin')

@section('title', 'Error detail')

@section('content')
<div class="mb-6">
    <a href="{{ route('superadmin.errors.index') }}" class="text-sm text-violet-600 hover:text-violet-800">← Error telemetry</a>
    <h1 class="mt-2 text-2xl font-bold tracking-tight">Error detail #{{ $log->id }}</h1>
    <p class="mt-1 text-sm text-gray-500">{{ $log->created_at->format('M j, Y H:i:s') }} · {{ ucfirst($log->environment) }}</p>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-4 lg:col-span-1">
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <h2 class="text-sm font-semibold text-gray-900">Context</h2>
            <dl class="mt-3 space-y-2 text-sm">
                <div><dt class="text-gray-500">Business</dt><dd class="font-medium">{{ optional($log->business)->name ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Signed in</dt><dd class="font-medium">{{ $log->isGuest() ? 'Guest (not logged in)' : 'Yes' }}</dd></div>
                <div><dt class="text-gray-500">Username</dt><dd class="font-medium">{{ $log->contactUsername() ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Name</dt><dd>{{ $log->contactName() ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Email</dt><dd>
                    @if($email = $log->contactEmail())
                        <a href="mailto:{{ $email }}" class="text-violet-600 hover:text-violet-800">{{ $email }}</a>
                    @else
                        —
                    @endif
                </dd></div>
                <div><dt class="text-gray-500">Phone (user)</dt><dd>{{ $log->contactPhone() ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Phone (business)</dt><dd>{{ optional($log->business)->phone ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Country</dt><dd>{{ $log->countryLabel() ?? '—' }}@if(!$log->countryLabel() && $log->ipAddress()) <span class="text-xs text-gray-400">(no geo header; see IP)</span>@endif</dd></div>
                <div><dt class="text-gray-500">IP address</dt><dd class="font-mono text-xs">{{ $log->ipAddress() ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Device</dt><dd>{{ $log->deviceTypeLabel() }} · {{ $log->device_info['browser'] ?? 'Unknown' }} on {{ $log->device_info['os'] ?? 'Unknown' }}</dd></div>
                <div><dt class="text-gray-500">HTTP status</dt><dd class="font-medium">{{ $log->httpStatus() ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Class</dt><dd class="break-all text-xs">{{ $log->exception_class ?? '—' }}</dd></div>
            </dl>
        </div>
        @if($log->contactEmail())
            <a href="mailto:{{ $log->contactEmail() }}?subject={{ rawurlencode('DukaPro support — issue on ' . ($log->url ?? 'your account')) }}"
               class="inline-flex w-full items-center justify-center rounded-lg bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-violet-500">
                Email user
            </a>
        @elseif($log->business && $log->business->email)
            <a href="mailto:{{ $log->business->email }}?subject={{ rawurlencode('DukaPro support — issue on ' . ($log->url ?? 'your account')) }}"
               class="inline-flex w-full items-center justify-center rounded-lg bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-violet-500">
                Email business contact
            </a>
        @endif
        @if($log->isGuest())
            <p class="text-xs leading-relaxed text-gray-500">This event has no user account (e.g. incognito or before login). Use business phone/email or the URL to infer who was affected.</p>
        @endif
    </div>

    <div class="space-y-4 lg:col-span-2">
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <h2 class="text-sm font-semibold text-gray-900">Message</h2>
            <p class="mt-2 whitespace-pre-wrap text-sm text-gray-800">{{ $log->error_message }}</p>
            @if($log->url)
                <p class="mt-3 break-all text-xs text-gray-500">{{ $log->url }}</p>
            @endif
        </div>

        @if($log->stack_trace)
            <div class="rounded-xl border border-gray-200 bg-white p-4">
                <h2 class="text-sm font-semibold text-gray-900">Stack trace</h2>
                <pre class="mt-3 max-h-96 overflow-auto rounded-lg bg-gray-950 p-4 text-xs leading-relaxed text-gray-100">{{ $log->stack_trace }}</pre>
            </div>
        @endif

        @if($log->payload)
            <div class="rounded-xl border border-gray-200 bg-white p-4">
                <h2 class="text-sm font-semibold text-gray-900">Sanitized payload</h2>
                <pre class="mt-3 max-h-80 overflow-auto rounded-lg bg-gray-50 p-4 text-xs text-gray-800">{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        @endif
    </div>
</div>
@endsection
