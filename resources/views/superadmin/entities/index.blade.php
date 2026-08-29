@extends('layouts.superadmin')

@section('title', $config['label'])

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight">{{ $config['label'] }}</h1>
        <p class="mt-1 text-sm text-gray-500">Platform-wide {{ strtolower($config['label']) }} management</p>
    </div>
    @if(($config['creatable'] ?? false))
        <a href="{{ route('superadmin.entities.create', $entity) }}"
           class="inline-flex items-center justify-center rounded-lg bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-violet-500">
            + Add {{ rtrim($config['label'], 's') }}
        </a>
    @endif
</div>

<form method="GET" class="mb-4 flex gap-2">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search {{ strtolower($config['label']) }}…"
           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
    <button type="submit" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium hover:bg-gray-50">Filter</button>
</form>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    @foreach($config['list'] as $column)
                        <th class="px-4 py-3">{{ str_replace('_', ' ', $column) }}</th>
                    @endforeach
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($records as $record)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-500">#{{ $record->id }}</td>
                        @foreach($config['list'] as $column)
                            <td class="px-4 py-3">
                                @php $value = data_get($record, $column); @endphp
                                @if($column === 'business_id' && $record->relationLoaded('business') && $record->business)
                                    {{ $record->business->name }}
                                @elseif($value instanceof \Carbon\Carbon)
                                    {{ $value->format('M j, Y') }}
                                @elseif(is_bool($value))
                                    {{ $value ? 'Yes' : 'No' }}
                                @elseif(in_array($column, ['price', 'amount', 'total', 'stock_quantity'], true))
                                    {{ is_numeric($value) ? number_format((float) $value, $column === 'stock_quantity' ? 0 : 2) : $value }}
                                @else
                                    {{ $value }}
                                @endif
                            </td>
                        @endforeach
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('superadmin.entities.show', [$entity, $record->id]) }}" class="text-violet-600 hover:text-violet-800">View</a>
                            @can('platform-full-access')
                                <a href="{{ route('superadmin.entities.edit', [$entity, $record->id]) }}" class="ml-3 text-gray-600 hover:text-gray-900">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($config['list']) + 2 }}" class="px-4 py-10 text-center text-gray-500">No records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($records->hasPages())
        <div class="border-t border-gray-200 px-4 py-3">{{ $records->links() }}</div>
    @endif
</div>
@endsection
