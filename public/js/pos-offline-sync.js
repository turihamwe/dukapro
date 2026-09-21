/**
 * DukaPro POS — network status + background offline sale sync.
 */
(function (global) {
    'use strict';

    var config = {
        businessId: null,
        syncUrl: '',
        csrf: '',
    };

    var online = typeof navigator !== 'undefined' ? navigator.onLine : true;
    var syncing = false;
    var statusEl = null;

    function setOnlineState(next) {
        online = !!next;
        if (!statusEl) {
            return;
        }
        statusEl.textContent = online ? 'Online' : 'Offline';
        statusEl.classList.toggle('bg-emerald-100', online);
        statusEl.classList.toggle('text-emerald-800', online);
        statusEl.classList.toggle('bg-amber-100', !online);
        statusEl.classList.toggle('text-amber-900', !online);
        statusEl.setAttribute('aria-label', online ? 'POS online' : 'POS offline — sales saved locally');
    }

    function bindStatusIndicator(elementId) {
        statusEl = document.getElementById(elementId);
        setOnlineState(online);
    }

    function isOnline() {
        return online;
    }

    function syncPendingSales() {
        if (!online || syncing || !config.syncUrl || !global.DukaProOfflineStore) {
            return Promise.resolve({ synced: 0 });
        }

        syncing = true;

        return global.DukaProOfflineStore.getUnsyncedSales(config.businessId)
            .then(function (pending) {
                if (!pending.length) {
                    return { synced: 0 };
                }

                return fetch(config.syncUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        sales: pending.map(function (row) {
                            return {
                                local_id: row.local_id,
                                payload: row.payload,
                            };
                        }),
                    }),
                }).then(function (res) {
                    return res.json().then(function (data) {
                        if (!res.ok) {
                            throw new Error((data && data.message) ? data.message : 'Sync failed');
                        }
                        return data;
                    });
                }).then(function (data) {
                    var ack = (data && data.acknowledged) ? data.acknowledged : [];
                    var chain = Promise.resolve();

                    ack.forEach(function (entry) {
                        chain = chain.then(function () {
                            return global.DukaProOfflineStore.markSaleSynced(entry.local_id);
                        });
                    });

                    return chain.then(function () {
                        return { synced: ack.length, failed: (data && data.failed) ? data.failed : [] };
                    });
                });
            })
            .catch(function () {
                return { synced: 0 };
            })
            .finally(function () {
                syncing = false;
            });
    }

    function init(options) {
        config.businessId = options.businessId;
        config.syncUrl = options.syncUrl || '';
        config.csrf = options.csrf || '';

        if (options.statusElementId) {
            bindStatusIndicator(options.statusElementId);
        }

        global.addEventListener('online', function () {
            setOnlineState(true);
            syncPendingSales();
        });

        global.addEventListener('offline', function () {
            setOnlineState(false);
        });

        if (online) {
            syncPendingSales();
        }

        global.setInterval(function () {
            if (online) {
                syncPendingSales();
            }
        }, 60000);
    }

    global.DukaProOfflinePOS = {
        init: init,
        isOnline: isOnline,
        syncPendingSales: syncPendingSales,
        setOnlineState: setOnlineState,
    };
})(window);
