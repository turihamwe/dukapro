@extends('layouts.superadmin')

@section('title', 'Global Search')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold tracking-tight">Global Search</h1>
    <p class="mt-1 text-sm text-gray-500">Search across all platform entities</p>
</div>

<form method="GET" class="mb-8 flex gap-2">
    <input type="search" name="q" value="{{ $query }}" autofocus
           class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-violet-500 focus:outline-none"
           placeholder="Search businesses, users, products, customers, sales, expenses…">
    <button type="submit" class="rounded-lg bg-violet-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-violet-500">Search</button>
</form>

@if($query === '')
    <p class="text-sm text-gray-500">Enter a search term to find records across the platform.</p>
@else
    @php $hasResults = collect($results)->flatten()->isNotEmpty(); @endphp
    @unless($hasResults)
        <p class="text-sm text-gray-500">No results found for “{{ $query }}”.</p>
    @else
        @foreach($results as $type => $items)
            @if($items->isNotEmpty())
                <div class="mb-8 overflow-hidden rounded-xl border border-gray-200 bg-white">
                    <div class="border-b border-gray-200 px-6 py-3">
                        <h2 class="font-semibold capitalize">{{ str_replace('_', ' ', $type) }}</h2>
                    </div>
                    <ul class="divide-y divide-gray-100">
                        @foreach($items as $item)
                            <li class="flex items-center justify-between gap-4 px-6 py-3 text-sm">
                                <div>
                                    @if($type === 'businesses')
                                        <p class="font-medium">{{ $item->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $item->email }}</p>
                                    @elseif($type === 'users')
                                        <p class="font-medium">{{ $item->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $item->email }} · {{ optional($item->business)->name }}</p>
                                    @elseif($type === 'products')
                                        <p class="font-medium">{{ $item->name }}</p>
                                        <p class="text-xs text-gray-500">{{ optional($item->business)->name }} · UGX {{ number_format($item->price, 0) }}</p>
                                    @elseif($type === 'customers')
                                        <p class="font-medium">{{ $item->name }}</p>
                                        <p class="text-xs text-gray-500">{{ optional($item->business)->name }}</p>
                                    @elseif($type === 'sales')
                                        <p class="font-medium">{{ $item->sale_number }}</p>
                                        <p class="text-xs text-gray-500">{{ optional($item->business)->name }} · UGX {{ number_format($item->total, 0) }}</p>
                                    @elseif($type === 'expenses')
                                        <p class="font-medium">{{ $item->title }}</p>
                                        <p class="text-xs text-gray-500">{{ optional($item->business)->name }} · UGX {{ number_format($item->amount, 0) }}</p>
                                    @endif
                                </div>
                                <a href="{{ route('superadmin.entities.show', [$type, $item->id]) }}" class="text-violet-600 hover:text-violet-800">View</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endforeach
    @endunless
@endif
@endsection
