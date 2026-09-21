@extends('layouts.superadmin')

@section('title', 'Error Telemetry')

@section('content')
<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight">Error Telemetry</h1>
        <p class="mt-1 text-sm text-gray-500">Client and server failures with business, device, and request context for proactive support</p>
    </div>
    <div class="shrink-0 rounded-xl border border-gray-200 bg-white p-4 lg:max-w-md">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-gray-900">Recording</p>
                <p class="mt-0.5 text-xs text-gray-500">
                    @if($trackingEnvLocked)
                        Disabled in .env (admin cannot enable)
                    @elseif($trackingEnabled)
                        Active — new errors are saved
                    @else
                        Paused — no new errors saved
                    @endif
                </p>
            </div>
            @if(!$trackingEnvLocked)
                <form method="POST" action="{{ route('superadmin.errors.settings') }}" class="flex items-center">
                    @csrf
                    <input type="hidden" name="enabled" id="error-tracking-enabled-input" value="{{ $trackingEnabled ? '1' : '0' }}">
                    <label class="relative inline-flex cursor-pointer items-center">
                        <input type="checkbox" class="peer sr-only" @checked($trackingEnabled)
                               onchange="var f=this.form; f.querySelector('#error-tracking-enabled-input').value=this.checked?'1':'0'; f.submit();">
                        <span class="h-7 w-12 rounded-full bg-gray-300 transition peer-checked:bg-violet-600 peer-focus:ring-2 peer-focus:ring-violet-300"></span>
                        <span class="pointer-events-none absolute left-0.5 top-0.5 h-6 w-6 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                    </label>
                </form>
            @else
                <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">Env off</span>
            @endif
        </div>
    </div>
</div>

<div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <p class="text-xs uppercase text-gray-500">Total logged</p>
        <p class="mt-2 text-2xl font-bold">{{ number_format($summary['total']) }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <p class="text-xs uppercase text-gray-500">Last 24 hours</p>
        <p class="mt-2 text-2xl font-bold text-rose-600">{{ number_format($summary['last_24h']) }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <p class="text-xs uppercase text-gray-500">Backend</p>
        <p class="mt-2 text-2xl font-bold">{{ number_format($summary['backend']) }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <p class="text-xs uppercase text-gray-500">Frontend</p>
        <p class="mt-2 text-2xl font-bold text-violet-600">{{ number_format($summary['frontend']) }}</p>
    </div>
</div>

<form method="GET" class="mb-6 grid gap-3 md:grid-cols-2 xl:grid-cols-6">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search message, URL, class…"
           class="rounded-lg border border-gray-300 px-3 py-2 text-sm xl:col-span-2">
    <select name="environment" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <option value="">All environments</option>
        <option value="backend" @selected(request('environment') === 'backend')>Backend</option>
        <option value="frontend" @selected(request('environment') === 'frontend')>Frontend</option>
    </select>
    <select name="http_status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <option value="">All HTTP statuses</option>
        @foreach([403 => '403 Forbidden', 404 => '404 Not found', 413 => '413 Too large', 419 => '419 Page expired', 429 => '429 Rate limit', '5xx' => '5xx Server error'] as $value => $label)
            <option value="{{ $value }}" @selected(request('http_status') === (string) $value)>{{ $label }}</option>
        @endforeach
    </select>
    <select name="device_type" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <option value="">All devices</option>
        @foreach(['mobile', 'tablet', 'desktop', 'unknown'] as $deviceType)
            <option value="{{ $deviceType }}" @selected(request('device_type') === $deviceType)>{{ ucfirst($deviceType) }}</option>
        @endforeach
    </select>
    <select name="business_id" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <option value="">All businesses</option>
        @foreach($businesses as $business)
            <option value="{{ $business->id }}" @selected((string) request('business_id') === (string) $business->id)>{{ $business->name }}</option>
        @endforeach
    </select>
    <div class="flex flex-wrap gap-2 xl:col-span-2">
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <button type="submit" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500">Filter</button>
        @if(request()->hasAny(['q', 'environment', 'http_status', 'device_type', 'business_id', 'date_from', 'date_to']))
            <a href="{{ route('superadmin.errors.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Clear</a>
        @endif
    </div>
</form>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">When</th>
                    <th class="px-4 py-3">Env</th>
                    <th class="px-4 py-3">HTTP</th>
                    <th class="px-4 py-3">Message</th>
                    <th class="px-4 py-3">Business</th>
                    <th class="px-4 py-3">User</th>
                    <th class="px-4 py-3">Device</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($logs as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-500">{{ $log->created_at->format('M j, H:i') }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase {{ $log->environment === 'backend' ? 'bg-rose-100 text-rose-800' : 'bg-violet-100 text-violet-800' }}">
                                {{ $log->environment }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-xs font-medium text-gray-700">
                            @if($status = $log->httpStatus())
                                <span class="{{ $status >= 500 ? 'text-rose-700' : ($status === 404 ? 'text-amber-700' : 'text-gray-700') }}">{{ $status }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="max-w-md px-4 py-3">
                            <p class="truncate font-medium text-gray-900">{{ $log->error_message }}</p>
                            <p class="truncate text-xs text-gray-500">{{ $log->url }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ optional($log->business)->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-700">
                            @if($log->contactUsername())
                                <span class="block font-medium">{{ $log->contactUsername() }}</span>
                            @endif
                            <span class="block text-xs text-gray-500">{{ $log->contactEmail() ?? ($log->isGuest() ? 'Guest' : '—') }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-600">
                            {{ $log->deviceTypeLabel() }}
                            <span class="block text-gray-400">{{ $log->device_info['browser'] ?? '' }} · {{ $log->device_info['os'] ?? '' }}</span>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('superadmin.errors.show', $log) }}" class="text-violet-600 hover:text-violet-800">Inspect</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-10 text-center text-gray-500">No errors logged yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('superadmin.partials.pagination', ['paginator' => $logs])
</div>
@endsection
