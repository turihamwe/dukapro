<div x-data="pwaInstall()" x-cloak class="space-y-6">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Install on this device</h2>
                <p class="mt-1 text-sm text-gray-600" x-show="showInstalledInBrowser()">
                    This app is already on your computer or phone. Open it from your taskbar, Start menu, or home screen — you do not need to install again.
                </p>
            </div>
            <template x-if="isStandalone || isInstalledOnDevice">
                <span class="inline-flex shrink-0 items-center gap-2 rounded-full bg-emerald-100 px-4 py-2 text-sm font-semibold text-emerald-800">
                    <span aria-hidden="true">✓</span>
                    {{ platform_brand('name') }} is installed on this device
                </span>
            </template>
        </div>

        <template x-if="isStandalone">
            <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50/60 p-4 text-sm text-emerald-950">
                <p class="font-medium">You are using the installed app</p>
                <p class="mt-2 text-emerald-900/90">Close and reopen the app when online to pick up updates.</p>
            </div>
        </template>

        <template x-if="showInstalledInBrowser()">
            <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50/60 p-4 text-sm text-emerald-950">
                <p class="font-medium">Already installed</p>
                <p class="mt-2 text-emerald-900/90">You opened this page in a browser tab. Launch {{ platform_brand('name') }} from the app icon you installed — that is the till experience.</p>
            </div>
        </template>

        <div x-show="!isStandalone && !isInstalledOnDevice" class="mt-6 space-y-3">
            <button type="button"
                    @click="installApp()"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-4 text-base font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto min-h-[52px]">
                <img src="{{ asset('assets/dukapro-logo.png') }}" alt="" width="28" height="28" class="h-7 w-7 shrink-0 rounded-md bg-white object-contain p-0.5" aria-hidden="true">
                Install {{ platform_brand('name') }} App
            </button>
            <p class="text-xs text-gray-500">One click opens your browser’s install dialog (Chrome or Edge on PC; Chrome on Android).</p>
            <p x-show="installMessage" x-text="installMessage" class="text-sm font-medium text-indigo-800" role="status"></p>
        </div>
    </div>

    <p class="text-center text-xs text-gray-500">
        Need help on a shop tablet? Contact support at 0758-582681.
    </p>
</div>
