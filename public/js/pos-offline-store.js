/**
 * DukaPro POS — native IndexedDB storage (no external deps).
 */
(function (global) {
    'use strict';

    var DB_NAME = 'dukapro_pos';
    var DB_VERSION = 1;

    var dbPromise = null;

    function openDb() {
        if (dbPromise) {
            return dbPromise;
        }

        dbPromise = new Promise(function (resolve, reject) {
            if (!global.indexedDB) {
                reject(new Error('IndexedDB is not available'));
                return;
            }

            var request = indexedDB.open(DB_NAME, DB_VERSION);

            request.onupgradeneeded = function (event) {
                var db = event.target.result;

                if (!db.objectStoreNames.contains('catalog')) {
                    db.createObjectStore('catalog', { keyPath: 'businessId' });
                }

                if (!db.objectStoreNames.contains('pending_sales')) {
                    var sales = db.createObjectStore('pending_sales', { keyPath: 'local_id' });
                    sales.createIndex('businessId', 'businessId', { unique: false });
                    sales.createIndex('synced', 'synced', { unique: false });
                }
            };

            request.onsuccess = function () {
                resolve(request.result);
            };

            request.onerror = function () {
                reject(request.error || new Error('IndexedDB open failed'));
            };
        });

        return dbPromise;
    }

    function runTx(storeName, mode, fn) {
        return openDb().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction(storeName, mode);
                var store = tx.objectStore(storeName);

                try {
                    fn(store, tx);
                } catch (error) {
                    reject(error);
                    return;
                }

                tx.oncomplete = function () {
                    resolve();
                };
                tx.onerror = function () {
                    reject(tx.error || new Error('IndexedDB transaction failed'));
                };
                tx.onabort = function () {
                    reject(tx.error || new Error('IndexedDB transaction aborted'));
                };
            });
        });
    }

    function saveCatalog(businessId, products) {
        return runTx('catalog', 'readwrite', function (store) {
            store.put({
                businessId: Number(businessId),
                products: products || [],
                updatedAt: Date.now(),
            });
        });
    }

    function getCatalog(businessId) {
        return openDb().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction('catalog', 'readonly');
                var req = tx.objectStore('catalog').get(Number(businessId));
                req.onsuccess = function () {
                    resolve(req.result ? req.result.products : null);
                };
                req.onerror = function () {
                    reject(req.error);
                };
            });
        });
    }

    function queuePendingSale(record) {
        return runTx('pending_sales', 'readwrite', function (store) {
            store.put(record);
        });
    }

    function getUnsyncedSales(businessId) {
        return openDb().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction('pending_sales', 'readonly');
                var store = tx.objectStore('pending_sales');
                var index = store.index('businessId');
                var req = index.getAll(Number(businessId));

                req.onsuccess = function () {
                    var rows = (req.result || []).filter(function (row) {
                        return row.synced === false;
                    });
                    resolve(rows);
                };
                req.onerror = function () {
                    reject(req.error);
                };
            });
        });
    }

    function markSaleSynced(localId) {
        return openDb().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction('pending_sales', 'readwrite');
                var store = tx.objectStore('pending_sales');
                var getReq = store.get(localId);

                getReq.onsuccess = function () {
                    var row = getReq.result;
                    if (!row) {
                        resolve(false);
                        return;
                    }
                    row.synced = true;
                    row.syncedAt = Date.now();
                    store.put(row);
                };
                getReq.onerror = function () {
                    reject(getReq.error);
                };

                tx.oncomplete = function () {
                    resolve(true);
                };
                tx.onerror = function () {
                    reject(tx.error);
                };
            });
        });
    }

    function generateLocalId() {
        return 'offline_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 10);
    }

    global.DukaProOfflineStore = {
        saveCatalog: saveCatalog,
        getCatalog: getCatalog,
        queuePendingSale: queuePendingSale,
        getUnsyncedSales: getUnsyncedSales,
        markSaleSynced: markSaleSynced,
        generateLocalId: generateLocalId,
    };
})(window);
