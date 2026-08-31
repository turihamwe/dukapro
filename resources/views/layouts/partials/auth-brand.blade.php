<div class="auth-brand mb-5 flex w-full flex-col items-center text-center sm:mb-8">
    <div class="fixed inset-x-0 top-0 z-20 flex justify-center border-b border-gray-200/80 bg-gray-50/95 px-4 py-3 backdrop-blur-sm pt-[max(0.75rem,env(safe-area-inset-top))] sm:static sm:z-auto sm:border-0 sm:bg-transparent sm:px-0 sm:py-0 sm:backdrop-blur-none">
        <x-dukapro-logo size="auth" :centered="true" />
    </div>
    {{-- Reserve space for the fixed mobile logo bar --}}
    <div class="h-[calc(10.5rem+env(safe-area-inset-top))] w-full shrink-0 sm:hidden" aria-hidden="true"></div>
    @if(!empty($subtitle))
        <p class="mt-1 text-sm text-gray-500 sm:mt-4">{{ $subtitle }}</p>
    @endif
</div>
