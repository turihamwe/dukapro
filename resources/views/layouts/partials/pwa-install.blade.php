<div x-data="pwaInstall()" x-cloak class="{{ $wrapperClass ?? '' }}">
    <template x-if="isStandalone || isInstalledOnDevice">
        <p class="text-xs text-emerald-600">App installed on this device</p>
    </template>
    <template x-if="!isStandalone && !isInstalledOnDevice">
        <button type="button"
                @click="installApp()"
                class="{{ $buttonClass ?? 'flex w-full items-center gap-2 rounded-lg px-3 py-2.5 text-sm font-medium transition' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4"/>
            </svg>
            <span>{{ $buttonLabel ?? 'Install App' }}</span>
        </button>
    </template>
    @if(! empty($showDownloadsLink))
        <a href="{{ tenant_route('tenant.downloads.index') }}"
           x-show="!isStandalone && !isInstalledOnDevice"
           class="{{ $linkClass ?? 'mt-2 block text-center text-xs font-medium text-indigo-600 hover:text-indigo-800' }}">
            Full install page →
        </a>
    @endif
</div>
