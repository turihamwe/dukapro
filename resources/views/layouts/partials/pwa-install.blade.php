@once
    @push('scripts')
        <script>
        document.addEventListener('alpine:init', function () {
            Alpine.data('pwaInstall', function () {
                return {
                    canInstall: false,
                    deferredPrompt: null,
                    init: function () {
                        var self = this;
                        if (window.matchMedia('(display-mode: standalone)').matches) {
                            return;
                        }
                        window.addEventListener('beforeinstallprompt', function (event) {
                            event.preventDefault();
                            self.deferredPrompt = event;
                            self.canInstall = true;
                        });
                        window.addEventListener('appinstalled', function () {
                            self.deferredPrompt = null;
                            self.canInstall = false;
                        });
                    },
                    install: function () {
                        var self = this;
                        if (!this.deferredPrompt) {
                            return;
                        }
                        this.deferredPrompt.prompt();
                        this.deferredPrompt.userChoice.finally(function () {
                            self.deferredPrompt = null;
                            self.canInstall = false;
                        });
                    },
                };
            });
        });
        </script>
    @endpush
@endonce

<div x-data="pwaInstall()" x-cloak class="{{ $wrapperClass ?? '' }}">
    <button type="button"
            x-show="canInstall"
            x-transition
            @click="install()"
            class="{{ $buttonClass ?? 'flex w-full items-center gap-2 rounded-lg px-3 py-2.5 text-sm font-medium transition' }}">
        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4"/>
        </svg>
        <span>Install App</span>
    </button>
</div>
