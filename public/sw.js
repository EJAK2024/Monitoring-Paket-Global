const CACHE_NAME = 'supply-chain-v5';
const CACHE_EXPIRY = 30 * 60 * 1000;

const EXCLUDE_FROM_CACHE = ['/portmap', '/admin', '/login', '/dashboard'];

const PRECACHE_URLS = [
    '/',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(PRECACHE_URLS).catch(() => {});
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys
                    .filter((key) => key !== CACHE_NAME)
                    .map((key) => caches.delete(key))
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('message', (event) => {
    if (event.data === 'skipWaiting') {
        self.skipWaiting();
    }
});

function isHTMLRequest(request) {
    return request.headers.get('accept')?.includes('text/html');
}

function isStaticAsset(request) {
    const url = new URL(request.url);
    return (
        url.pathname.match(/\.(css|js|woff2?|ttf|eot|svg|png|jpe?g|gif|ico|webp)$/) ||
        url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/storage/')
    );
}

function isAPIRequest(request) {
    return new URL(request.url).pathname.startsWith('/api/');
}

function isExcludedFromCache(request) {
    const pathname = new URL(request.url).pathname;
    return EXCLUDE_FROM_CACHE.some((path) => pathname.startsWith(path));
}

async function cachePage(cache, request, response) {
    const clone = response.clone();
    await cache.put(request, clone);
    await cache.put(
        request.url + ':ts',
        new Response(JSON.stringify({ timestamp: Date.now() }))
    );
}

async function getCachedPage(cache, request) {
    const tsResp = await cache.match(request.url + ':ts');
    if (!tsResp) return null;

    const { timestamp } = await tsResp.json();
    if (Date.now() - timestamp > CACHE_EXPIRY) {
        await cache.delete(request);
        await cache.delete(request.url + ':ts');
        return null;
    }

    return cache.match(request);
}

async function pageStrategy(request) {
    if (isExcludedFromCache(request)) {
        try {
            return await fetch(request);
        } catch {
            return new Response('Offline', { status: 503 });
        }
    }

    const cache = await caches.open(CACHE_NAME);
    const isReload = request.cache === 'no-cache' || request.cache === 'reload';

    if (!isReload) {
        const cached = await getCachedPage(cache, request);
        if (cached) return cached;
    }

    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            await cachePage(cache, request, networkResponse);
        }
        return networkResponse;
    } catch {
        const fallback = await cache.match(request);
        if (fallback) return fallback;
        return new Response('Offline', {
            status: 503,
            headers: { 'Content-Type': 'text/html; charset=utf-8' },
        });
    }
}

async function staticStrategy(request) {
    const cached = await caches.match(request);
    if (cached) return cached;

    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cache = await caches.open(CACHE_NAME);
            await cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch {
        return new Response('', { status: 503 });
    }
}

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') return;

    if (isStaticAsset(request)) {
        event.respondWith(staticStrategy(request));
    } else if (isAPIRequest(request)) {
        return;
    } else if (isHTMLRequest(request)) {
        event.respondWith(pageStrategy(request));
    }
});
