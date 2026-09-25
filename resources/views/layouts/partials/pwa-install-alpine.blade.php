<script>
document.addEventListener('alpine:init', function () {
    Alpine.data('pwaInstall', function () {
        return {
            isStandalone: false,
            isInstalledOnDevice: false,
            canInstall: false,
            deferredPrompt: null,
            installMessage: '',
            fallbackUrl: '',
            pwaStorageKey: '',
            init: function () {
                var self = this;
                var bridge = window.__dukaproPwa || {};
                self.fallbackUrl = (this.$el && this.$el.dataset.installFallback) || '';
                self.pwaStorageKey = bridge.storageKey || 'dukapro-pwa-installed';
                self.isStandalone = self.detectStandalone();
                self.syncDeferredPrompt();

                if (self.isStandalone) {
                    self.isInstalledOnDevice = true;
                    return;
                }

                self.refreshInstalledState();

                window.addEventListener('dukapro-pwa-install-available', function () {
                    self.syncDeferredPrompt();
                });

                window.addEventListener('dukapro-pwa-installed', function () {
                    self.deferredPrompt = null;
                    self.canInstall = false;
                    self.isInstalledOnDevice = true;
                    self.isStandalone = self.detectStandalone();
                    self.installMessage = '';
                });

                window.addEventListener('appinstalled', function () {
                    self.deferredPrompt = null;
                    self.canInstall = false;
                    self.isInstalledOnDevice = true;
                    self.installMessage = '';
                });
            },
            detectStandalone: function () {
                return window.matchMedia('(display-mode: standalone)').matches
                    || window.matchMedia('(display-mode: window-controls-overlay)').matches
                    || window.navigator.standalone === true;
            },
            syncDeferredPrompt: function () {
                var bridge = window.__dukaproPwa || {};
                if (bridge.deferredPrompt) {
                    this.deferredPrompt = bridge.deferredPrompt;
                    this.canInstall = true;
                }
            },
            refreshInstalledState: function () {
                var self = this;
                if (self.isStandalone) {
                    self.isInstalledOnDevice = true;
                    return;
                }
                try {
                    if (localStorage.getItem(self.pwaStorageKey) === '1'
                        || localStorage.getItem('dukapro-pwa-installed') === '1') {
                        self.isInstalledOnDevice = true;
                    }
                } catch (e) {}

                if (! ('getInstalledRelatedApps' in navigator)) {
                    return;
                }

                navigator.getInstalledRelatedApps().then(function (apps) {
                    if (apps && apps.length > 0) {
                        self.isInstalledOnDevice = true;
                        self.canInstall = false;
                        self.deferredPrompt = null;
                    }
                }).catch(function () {});
            },
            showInstalledInBrowser: function () {
                return this.isInstalledOnDevice && ! this.isStandalone;
            },
            install: function () {
                var self = this;
                self.syncDeferredPrompt();
                if (! this.deferredPrompt) {
                    return Promise.resolve(false);
                }
                this.deferredPrompt.prompt();
                return this.deferredPrompt.userChoice.then(function (choice) {
                    self.deferredPrompt = null;
                    self.canInstall = false;
                    if (window.__dukaproPwa) {
                        window.__dukaproPwa.deferredPrompt = null;
                    }
                    if (choice.outcome === 'accepted') {
                        self.isInstalledOnDevice = true;
                    }
                    self.isStandalone = self.detectStandalone();
                    return choice.outcome === 'accepted';
                }).catch(function () {
                    self.deferredPrompt = null;
                    self.canInstall = false;
                    return false;
                });
            },
            installApp: function () {
                var self = this;
                if (this.isStandalone || this.isInstalledOnDevice) {
                    return;
                }
                this.syncDeferredPrompt();
                if (this.deferredPrompt) {
                    this.installMessage = 'Confirm the install prompt from your browser…';
                    this.install().then(function (accepted) {
                        if (accepted) {
                            self.installMessage = '';
                            self.isInstalledOnDevice = true;
                        } else {
                            self.installMessage = 'Install cancelled. Click Install again to retry.';
                        }
                    });
                    return;
                }
                this.installMessage = 'Install is not ready yet — wait a moment and click Install again, or use Chrome/Edge on this site.';
            },
        };
    });
});
</script>
