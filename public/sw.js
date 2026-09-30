/* DukaPro PWA service worker */
var CACHE_VERSION = 'dukapro-pwa-v2';
var PRECACHE_URLS = [
    '/assets/pwa/icon-192.png',
    '/assets/pwa/icon-512.png',
];

self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(CACHE_VERSION).then(function (cache) {
            return cache.addAll(PRECACHE_URLS).catch(function () {});
        }).then(function () {
            return self.skipWaiting();
        })
    );
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(
                keys.filter(function (key) {
                    return key.indexOf('dukapro-pwa-') === 0 && key !== CACHE_VERSION;
                }).map(function (key) {
                    return caches.delete(key);
                })
            );
        }).then(function () {
            return self.clients.claim();
        })
    );
});

self.addEventListener('fetch', function (event) {
    if (event.request.method !== 'GET') {
        return;
    }

    event.respondWith(
        fetch(event.request).catch(function () {
            return caches.match(event.request);
        })
    );
});
