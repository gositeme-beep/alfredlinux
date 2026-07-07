function toggleModule(header) {
    header.closest('.ek-module').classList.toggle('open');
}

async function cacheAll() {
    if (!('serviceWorker' in navigator)) {
        alert('Service Worker not supported. Offline caching unavailable.');
        return;
    }
    var pages = [
        '/emergency-kit', '/search', '/security', '/post-quantum',
        '/veil/', '/offline.html', '/about-crawler'
    ];
    try {
        var cache = await caches.open('alfred-emergency-v1');
        await cache.addAll(pages);
        alert('Emergency pages cached for offline access (' + pages.length + ' pages).');
        checkCache();
    } catch(e) {
        alert('Some pages could not be cached. Try again when online.');
    }
}

async function checkCache() {
    try {
        var names = await caches.keys();
        var total = 0;
        for (var i = 0; i < names.length; i++) {
            var c = await caches.open(names[i]);
            var keys = await c.keys();
            total += keys.length;
        }
        document.getElementById('ekCachePages').textContent = total;
        if ('storage' in navigator && 'estimate' in navigator.storage) {
            var est = await navigator.storage.estimate();
            var mb = (est.usage / 1024 / 1024).toFixed(1);
            document.getElementById('ekCacheSize').textContent = mb + 'MB';
        }
    } catch(e) {}
    var reg = await navigator.serviceWorker.getRegistration();
    document.getElementById('ekSwStatus').textContent = reg ? 'Active' : 'None';
    document.getElementById('ekLastSync').textContent = new Date().toLocaleDateString();
}

// Online/offline detection
window.addEventListener('online', function() {
    document.getElementById('ekStatus').textContent = 'Online — All systems operational';
});
window.addEventListener('offline', function() {
    document.getElementById('ekStatus').textContent = 'OFFLINE — Running from local cache';
    document.getElementById('ekStatus').style.color = 'var(--ek-amber)';
});
if (!navigator.onLine) {
    document.getElementById('ekStatus').textContent = 'OFFLINE — Running from local cache';
    document.getElementById('ekStatus').style.color = 'var(--ek-amber)';
}

checkCache();
