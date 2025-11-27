/**
 * WXXXC Service Worker
 * Blazing fast caching for instant page loads
 */

const CACHE_NAME = 'wxxxc-v1';
const RUNTIME_CACHE = 'wxxxc-runtime';

// Assets to cache immediately on install
const PRECACHE_ASSETS = [
    '/',
    '/index.html',
    '/studios.html',
    '/portfolio.html',
    '/schedule.html',
    '/community.html',
    '/shop.html',
    '/contact.html',
    '/links.html',
    '/epk.html',
    '/404.html',
    '/css/styles.css',
    '/css/effects.css',
    '/js/main.js',
    '/js/effects.js',
    '/js/turbo.js',
    'https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap'
];

// Install event - precache assets
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('⚡ WXXXC: Precaching assets');
                return cache.addAll(PRECACHE_ASSETS);
            })
            .then(() => self.skipWaiting())
    );
});

// Activate event - clean old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames
                    .filter(name => name !== CACHE_NAME && name !== RUNTIME_CACHE)
                    .map(name => caches.delete(name))
            );
        }).then(() => {
            console.log('⚡ WXXXC: Service Worker activated');
            return self.clients.claim();
        })
    );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET requests
    if (request.method !== 'GET') return;

    // Skip chrome-extension and other non-http(s) requests
    if (!url.protocol.startsWith('http')) return;

    event.respondWith(
        caches.match(request).then(cachedResponse => {
            if (cachedResponse) {
                // Return cached version immediately
                // Also fetch fresh version in background (stale-while-revalidate)
                event.waitUntil(
                    fetch(request).then(response => {
                        if (response.ok) {
                            caches.open(RUNTIME_CACHE).then(cache => {
                                cache.put(request, response);
                            });
                        }
                    }).catch(() => {})
                );
                return cachedResponse;
            }

            // Not in cache, fetch from network
            return fetch(request).then(response => {
                // Cache successful responses
                if (response.ok) {
                    const responseClone = response.clone();
                    caches.open(RUNTIME_CACHE).then(cache => {
                        cache.put(request, responseClone);
                    });
                }
                return response;
            }).catch(() => {
                // Offline fallback for HTML pages
                if (request.headers.get('accept').includes('text/html')) {
                    return caches.match('/404.html');
                }
            });
        })
    );
});

// Background sync for form submissions
self.addEventListener('sync', event => {
    if (event.tag === 'form-sync') {
        event.waitUntil(syncForms());
    }
});

async function syncForms() {
    // Handle queued form submissions when back online
    const cache = await caches.open('form-queue');
    const requests = await cache.keys();

    for (const request of requests) {
        try {
            await fetch(request);
            await cache.delete(request);
        } catch (e) {
            console.log('Form sync failed, will retry');
        }
    }
}

console.log('⚡ WXXXC Service Worker loaded');
