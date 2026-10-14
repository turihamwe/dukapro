@php
    $filterLines = $filterLines ?? [];
@endphp
<div class="border-b border-gray-300 pb-4">
    <p class="text-xs uppercase tracking-wide text-gray-500">{{ platform_brand('name') }}</p>
    <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ $title }}</h1>
    <p class="mt-1 text-sm font-medium text-gray-800">{{ $business->name }}</p>
    @foreach($filterLines as $line)
        <p class="text-sm text-gray-600">{{ $line }}</p>
    @endforeach
    <p class="mt-1 text-xs text-gray-500">Generated {{ $generatedAt->format('M j, Y g:i A') }}</p>
</div>
