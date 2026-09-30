<div x-data="pwaInstall()" x-cloak x-show="!isStandalone && (!isInstalledOnDevice || canInstall)" class="mb-6">
    <div class="flex flex-col gap-3 rounded-xl border border-indigo-100 bg-indigo-50/80 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <p class="text-sm font-semibold text-indigo-950">Install {{ platform_brand('name') }} on this device</p>
            <p class="text-xs text-indigo-900/80" x-show="canInstall">One click adds the till app to your home screen or desktop.</p>
            <p class="text-xs text-indigo-900/80" x-show="!canInstall">Download the app for faster POS access (Chrome or Edge).</p>
        </div>
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            <button type="button"
                    @click="installApp()"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 min-h-[44px]">
                <img src="{{ asset('assets/pwa/icon-192.png') }}" alt="" width="22" height="22" class="h-[22px] w-[22px] shrink-0 rounded bg-white object-contain p-0.5" aria-hidden="true">
                <span x-text="canInstall ? 'Install app' : 'Download app'"></span>
            </button>
            <a href="{{ tenant_route('tenant.downloads.index') }}"
               class="inline-flex items-center justify-center rounded-lg border border-indigo-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50 min-h-[44px]">
                Install page
            </a>
        </div>
        <p x-show="installMessage" x-text="installMessage" class="w-full text-xs font-medium text-indigo-800 sm:col-span-2" role="status"></p>
    </div>
</div>
