<link rel="manifest" href="{{ asset('manifest.json') }}">
<meta name="theme-color" content="#0A192F">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="DukaPro POS">
<link rel="apple-touch-icon" href="{{ asset('assets/dukapro-logo.png') }}">
<style>[x-cloak] { display: none !important; }</style>
<script>
(function () {
    if (!('serviceWorker' in navigator)) {
        return;
    }
    window.addEventListener('load', function () {
        navigator.serviceWorker.register(@json(asset('sw.js'))).catch(function () {});
    });
})();
</script>
