// iPart Store — minimal service worker (required for Android install prompt)
// Network-first: always fresh data; no offline caching of API results.
self.addEventListener('install', (e) => self.skipWaiting());
self.addEventListener('activate', (e) => e.waitUntil(clients.claim()));
self.addEventListener('fetch', (e) => {
    // pass-through; exists so the app qualifies as installable
});
