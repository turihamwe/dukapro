@extends('layouts.superadmin')

@section('title', 'Affiliate Field Feedback')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold tracking-tight">Affiliate field feedback</h1>
    <p class="mt-1 text-sm text-gray-500">Reports from partners meeting merchants in the field</p>
</div>

<div class="mb-6 grid grid-cols-2 gap-4 sm:max-w-md">
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <p class="text-xs uppercase text-gray-500">Total reports</p>
        <p class="mt-2 text-2xl font-bold">{{ number_format($summary['total']) }}</p>
    </div>
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
        <p class="text-xs uppercase text-emerald-800">New / unread</p>
        <p class="mt-2 text-2xl font-bold text-emerald-900">{{ number_format($summary['new']) }}</p>
    </div>
</div>

<form method="GET" class="mb-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search reports…"
           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm sm:max-w-xs">
    <select name="category" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <option value="">All categories</option>
        @foreach($categories as $value => $label)
            <option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <option value="">All statuses</option>
        <option value="new" @selected(request('status') === 'new')>New</option>
        <option value="reviewed" @selected(request('status') === 'reviewed')>Reviewed</option>
    </select>
    <button type="submit" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500">Filter</button>
</form>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">When</th>
                    <th class="px-4 py-3">Affiliate</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Headline</th>
                    <th class="px-4 py-3">Merchant / area</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($feedback as $row)
                    <tr class="hover:bg-gray-50 {{ $row->status === 'new' ? 'bg-emerald-50/40' : '' }}">
                        <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $row->created_at->format('M j, Y g:i A') }}</td>
                        <td class="px-4 py-3">
                            @if($row->affiliate)
                                <a href="{{ route('superadmin.entities.show', ['affiliates', $row->affiliate_id]) }}" class="font-medium text-violet-700 hover:text-violet-900">
                                    {{ $row->affiliate->name }}
                                </a>
                                <span class="block text-xs text-gray-500">{{ $row->affiliate->code }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ \App\Models\AffiliateFieldFeedback::categoryLabel($row->category) }}</td>
                        <td class="max-w-xs px-4 py-3 font-medium text-gray-900">{{ $row->summary }}</td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ $row->merchant_name ?: '—' }}
                            @if($row->location)
                                <span class="block text-xs text-gray-500">{{ $row->location }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($row->status === 'new')
                                <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">New</span>
                            @else
                                <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600">Reviewed</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('superadmin.affiliate-feedback.show', $row) }}" class="font-semibold text-violet-600 hover:text-violet-800">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-gray-500">No field feedback yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('superadmin.partials.pagination', ['paginator' => $feedback])
</div>
@endsection
