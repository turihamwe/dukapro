@extends('layouts.superadmin')

@section('title', 'View ' . $config['label'])

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight">{{ $config['label'] }} #{{ $item->id }}</h1>
        <p class="mt-1 text-sm text-gray-500">Record details</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('superadmin.entities.index', $entity) }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium hover:bg-gray-50">Back</a>
        @can('platform-full-access')
            <a href="{{ route('superadmin.entities.edit', [$entity, $item->id]) }}" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500">Edit</a>
            <form method="POST" action="{{ route('superadmin.entities.destroy', [$entity, $item->id]) }}" onsubmit="return confirm('Delete this record permanently?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">Delete</button>
            </form>
        @endcan
    </div>
</div>

<div class="rounded-xl border border-gray-200 bg-white p-6">
    <dl class="grid gap-4 sm:grid-cols-2">
        @foreach($item->getAttributes() as $key => $value)
            @if(! in_array($key, ['password', 'remember_token'], true))
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ str_replace('_', ' ', $key) }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 break-all">
                        @if($value instanceof \Carbon\Carbon)
                            {{ $value->format('M j, Y g:i A') }}
                        @elseif(is_bool($value))
                            {{ $value ? 'Yes' : 'No' }}
                        @elseif($value === null || $value === '')
                            —
                        @else
                            {{ is_array($value) ? json_encode($value) : $value }}
                        @endif
                    </dd>
                </div>
            @endif
        @endforeach
    </dl>
</div>
@endsection
