@php
    $tenantBusiness = active_business();
    $manifestHref = $tenantBusiness
        ? route('tenant.pwa.manifest', ['business' => $tenantBusiness->slug])
        : route('pwa.manifest.site');
    $pwaInstallId = $tenantBusiness ? '/app/'.$tenantBusiness->slug.'/' : '/';
    $swUrl = asset('sw.js');
@endphp
<link rel="manifest" href="{{ $manifestHref }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('assets/pwa/icon-192.png') }}">
<link rel="icon" type="image/png" sizes="512x512" href="{{ asset('assets/pwa/icon-512.png') }}">
<meta name="theme-color" content="#0A192F">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="DukaPro POS">
<link rel="apple-touch-icon" href="{{ asset('assets/pwa/icon-192.png') }}">
<meta name="dukapro-pwa-id" content="{{ $pwaInstallId }}">
<style>[x-cloak] { display: none !important; }</style>
<script>
(function () {
    window.__dukaproPwa = window.__dukaproPwa || {
        deferredPrompt: null,
        storageKey: @json($pwaInstallId !== '/' ? 'dukapro-pwa-installed:'.$pwaInstallId : 'dukapro-pwa-installed'),
    };

    function notifyInstallAvailable() {
        window.dispatchEvent(new CustomEvent('dukapro-pwa-install-available'));
    }

    window.addEventListener('beforeinstallprompt', function (event) {
        event.preventDefault();
        window.__dukaproPwa.deferredPrompt = event;
        notifyInstallAvailable();
    });

    window.addEventListener('appinstalled', function () {
        try {
            localStorage.setItem(window.__dukaproPwa.storageKey, '1');
        } catch (e) {}
        window.__dukaproPwa.deferredPrompt = null;
        window.dispatchEvent(new CustomEvent('dukapro-pwa-installed'));
    });

    if (window.__dukaproPwa.deferredPrompt) {
        notifyInstallAvailable();
    }

    if (!('serviceWorker' in navigator)) {
        return;
    }

    window.addEventListener('load', function () {
        navigator.serviceWorker.register(@json($swUrl), { scope: '/' }).then(function (registration) {
            window.__dukaproPwa.serviceWorkerRegistration = registration;
        }).catch(function (err) {
            console.warn('DukaPro service worker registration failed', err);
        });
    });
})();
</script>
