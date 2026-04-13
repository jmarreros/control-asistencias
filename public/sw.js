const CACHE = 'slm-v2';
const PRECACHE = [
    '/images/fondo.jpg',
    '/images/logo-xs.jpg',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/apple-touch-icon.png',
];

self.addEventListener('install', e => {
    e.waitUntil(
        caches.open(CACHE).then(c => c.addAll(PRECACHE))
    );
    self.skipWaiting();
});

self.addEventListener('activate', e => {
    e.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))
        )
    );
    self.clients.claim();
});

// Network-first para páginas, cache-first para imágenes/assets
self.addEventListener('fetch', e => {
    if (e.request.method !== 'GET') return;

    const url = new URL(e.request.url);

    // Assets estáticos: cache-first
    if (url.pathname.startsWith('/images/') || url.pathname.startsWith('/icons/') || url.pathname.startsWith('/build/')) {
        e.respondWith(
            caches.match(e.request).then(cached => cached || fetch(e.request).then(res => {
                const clone = res.clone();
                caches.open(CACHE).then(c => c.put(e.request, clone));
                return res;
            }))
        );
        return;
    }

    // Páginas: network-first
    e.respondWith(
        fetch(e.request).catch(() => caches.match(e.request))
    );
});
