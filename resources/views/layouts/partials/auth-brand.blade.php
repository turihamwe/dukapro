<div class="mb-6 flex w-full flex-col items-center text-center sm:mb-8">
    <x-dukapro-logo size="auth" :centered="true" />
    @if(!empty($subtitle))
        <p class="mt-4 text-sm text-gray-500">{{ $subtitle }}</p>
    @endif
</div>
