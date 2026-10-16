@extends('layouts.superadmin')

@section('title', 'Account deletion requests')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold tracking-tight">Account deletion requests</h1>
    <p class="mt-1 text-sm text-gray-500">These are requests only. Opening one does not delete the account.</p>
</div>

<div class="mb-6 max-w-xs rounded-xl border border-amber-200 bg-amber-50 p-4">
    <p class="text-xs uppercase text-amber-800">Waiting for a reply</p>
    <p class="mt-2 text-2xl font-bold text-amber-950">{{ number_format($newCount) }}</p>
</div>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">When</th>
                    <th class="px-4 py-3">Person</th>
                    <th class="px-4 py-3">Shop</th>
                    <th class="px-4 py-3">What happened</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($requests as $row)
                    <tr class="{{ $row->status === 'new' ? 'bg-amber-50/40' : '' }}">
                        <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $row->created_at->format('M j, Y g:i A') }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-900">{{ $row->name ?: '—' }}</p>
                            <p class="text-xs text-gray-500">{{ $row->email ?: $row->username ?: '—' }}</p>
                            @if($row->phone)
                                <p class="text-xs text-gray-500">{{ $row->phone }}</p>
                            @endif
                            <p class="text-xs capitalize text-gray-400">{{ $row->role ?: '—' }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ optional($row->business)->name ?: '—' }}</td>
                        <td class="max-w-md px-4 py-3 text-gray-800">{{ $row->message }}</td>
                        <td class="whitespace-nowrap px-4 py-3">
                            @if($row->status === 'new')
                                <form method="POST" action="{{ route('superadmin.account-deletion-requests.review', $row) }}">
                                    @csrf
                                    <button type="submit" class="text-sm font-semibold text-violet-700 hover:text-violet-900">Mark reviewed</button>
                                </form>
                            @else
                                <span class="text-xs text-gray-500">Reviewed</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500">No deletion requests yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('superadmin.partials.pagination', ['paginator' => $requests])
</div>
@endsection
