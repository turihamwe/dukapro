@props(['title', 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between']) }}>
    <div>
        <h1 class="text-xl font-semibold tracking-tight text-gray-900">{{ $title }}</h1>
        @if($subtitle)
            <p class="mt-1 text-sm text-gray-500">{{ $subtitle }}</p>
        @endif
    </div>
    @if(isset($actions))
        <div class="flex w-full flex-wrap items-stretch gap-2 sm:w-auto sm:justify-end">{{ $actions }}</div>
    @endif
</div>
