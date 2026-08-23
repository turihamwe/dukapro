@extends('layouts.superadmin')

@section('title', 'Activity Log')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold tracking-tight">Unified Activity Log</h1>
    <p class="mt-1 text-sm text-slate-400">Significant actions across all tenants — no private business data exposed</p>
</div>

<form method="GET" class="mb-6 flex flex-wrap gap-3">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search summary or action…"
           class="rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-100 placeholder-slate-500 focus:border-violet-500 focus:outline-none">
    <select name="action" class="rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-100">
        <option value="">All actions</option>
        @foreach($actions as $action)
            <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>{{ $action }}</option>
        @endforeach
    </select>
    <select name="business_id" class="rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-100">
        <option value="">All tenants</option>
        @foreach($businesses as $business)
            <option value="{{ $business->id }}" {{ request('business_id') == $business->id ? 'selected' : '' }}>{{ $business->name }}</option>
        @endforeach
    </select>
    <button type="submit" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white hover:bg-violet-500">Filter</button>
    @if(request()->hasAny(['q', 'action', 'business_id']))
        <a href="{{ route('superadmin.activity') }}" class="rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-400 hover:text-slate-200">Clear</a>
    @endif
</form>

<div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-800 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-6 py-3">When</th>
                    <th class="px-6 py-3">Action</th>
                    <th class="px-6 py-3">Summary</th>
                    <th class="px-6 py-3">Tenant</th>
                    <th class="px-6 py-3">User</th>
                    <th class="px-6 py-3">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse($logs as $log)
                    <tr class="hover:bg-slate-800/50">
                        <td class="whitespace-nowrap px-6 py-3 text-slate-400">{{ $log->created_at->format('M d, H:i') }}</td>
                        <td class="px-6 py-3"><code class="rounded bg-slate-800 px-1.5 py-0.5 text-xs text-violet-300">{{ $log->action }}</code></td>
                        <td class="max-w-md px-6 py-3">{{ $log->summary }}</td>
                        <td class="px-6 py-3 text-slate-400">{{ optional($log->business)->name ?? '—' }}</td>
                        <td class="px-6 py-3 text-slate-400">{{ optional($log->user)->email ?? '—' }}</td>
                        <td class="px-6 py-3 text-xs text-slate-500">{{ $log->ip_address }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-slate-500">No activity recorded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
        <div class="border-t border-slate-800 px-6 py-4">{{ $logs->links() }}</div>
    @endif
</div>
@endsection
