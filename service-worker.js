const CACHE_NAME = 'tasty-pizza-pwa-v1';
const APP_SHELL = [
    './offline.html',
    './assets/js/alpine.min.js',
    './assets/pwa/icon.svg',
    './assets/pwa/icon-192.png',
    './assets/pwa/icon-512.png',
];
const MENU_PATHS = new Set(['/menu/index.php', '/menu/menu.php']);
const MAX_MENU_PAGES = 10;

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(APP_SHELL))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(keys => Promise.all(
                keys.filter(key => key.startsWith('tasty-pizza-pwa-') && key !== CACHE_NAME)
                    .map(key => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    if (url.pathname.includes('/admin/') || url.pathname.endsWith('/submit_feedback.php')) {
        return;
    }

    if (request.mode === 'navigate') {
        if (!MENU_PATHS.has(url.pathname)) {
            return;
        }

        event.respondWith((async () => {
            const cache = await caches.open(CACHE_NAME);

            try {
                const forceNetwork = url.searchParams.has('_refresh');
                const response = forceNetwork
                    ? await fetch(request, { cache: 'no-store' })
                    : await fetch(request);
                if (response.ok && response.type === 'basic') {
                    await cache.put(request, response.clone());
                    const menuPages = (await cache.keys()).filter(key => {
                        const cachedUrl = new URL(key.url);
                        return MENU_PATHS.has(cachedUrl.pathname);
                    });
                    while (menuPages.length > MAX_MENU_PAGES) {
                        await cache.delete(menuPages.shift());
                    }
                }
                return response;
            } catch (error) {
                const cachedPage = await cache.match(request);
                if (cachedPage) {
                    return cachedPage;
                }

                const offlinePage = await cache.match('./offline.html');
                if (offlinePage) {
                    return offlinePage;
                }

                throw error;
            }
        })());
        return;
    }

    if (
        url.pathname.endsWith('/assets/js/alpine.min.js')
        || url.pathname.endsWith('/assets/pwa/icon.svg')
        || url.pathname.endsWith('/assets/pwa/icon-192.png')
        || url.pathname.endsWith('/assets/pwa/icon-512.png')
    ) {
        event.respondWith((async () => {
            const cache = await caches.open(CACHE_NAME);
            const cachedAsset = await cache.match(request);
            if (cachedAsset) {
                return cachedAsset;
            }

            const response = await fetch(request);
            if (response.ok && response.type === 'basic') {
                await cache.put(request, response.clone());
            }
            return response;
        })());
    }
});
