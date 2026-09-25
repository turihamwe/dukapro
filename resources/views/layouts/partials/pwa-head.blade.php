@php
    $tenantBusiness = active_business();
    $manifestHref = $tenantBusiness
        ? route('tenant.pwa.manifest', ['business' => $tenantBusiness->slug])
        : asset('manifest.json');
    $pwaInstallId = $tenantBusiness ? '/app/'.$tenantBusiness->slug.'/' : '';
@endphp
<link rel="manifest" href="{{ $manifestHref }}">
<meta name="theme-color" content="#0A192F">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="DukaPro POS">
<link rel="apple-touch-icon" href="{{ asset('assets/dukapro-logo.png') }}">
@if($pwaInstallId !== '')
<meta name="dukapro-pwa-id" content="{{ $pwaInstallId }}">
@endif
<style>[x-cloak] { display: none !important; }</style>
<script>
(function () {
    window.__dukaproPwa = window.__dukaproPwa || {
        deferredPrompt: null,
        storageKey: @json($pwaInstallId !== '' ? 'dukapro-pwa-installed:'. $pwaInstallId : 'dukapro-pwa-installed'),
    };

    window.addEventListener('beforeinstallprompt', function (event) {
        event.preventDefault();
        window.__dukaproPwa.deferredPrompt = event;
        window.dispatchEvent(new CustomEvent('dukapro-pwa-install-available'));
    });

    window.addEventListener('appinstalled', function () {
        try {
            localStorage.setItem(window.__dukaproPwa.storageKey, '1');
        } catch (e) {}
        window.__dukaproPwa.deferredPrompt = null;
        window.dispatchEvent(new CustomEvent('dukapro-pwa-installed'));
    });

    if (!('serviceWorker' in navigator)) {
        return;
    }
    window.addEventListener('load', function () {
        navigator.serviceWorker.register(@json(asset('sw.js'))).catch(function () {});
    });
})();
</script>
