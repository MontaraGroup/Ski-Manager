const CACHE_NAME = 'ski-manager-v2';
const PRECACHE = [
  '/css/style.css',
  '/css/leaflet.css',
  '/js/leaflet.js',
  '/favicon.svg',
  '/android-chrome-192x192.png',
  '/android-chrome-512x512.png'
];

self.addEventListener('install', e => {
  e.waitUntil(caches.open(CACHE_NAME).then(c => c.addAll(PRECACHE)));
  self.skipWaiting();
});

self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', e => {
  const url = new URL(e.request.url);

  // Only handle same-origin requests — skip ads, analytics, external scripts
  if (url.origin !== self.location.origin) return;

  // Cache map images aggressively
  if (url.pathname.startsWith('/img/')) {
    e.respondWith(
      caches.open(CACHE_NAME).then(c =>
        c.match(e.request).then(r => {
          if (r) return r;
          return fetch(e.request).then(res => {
            if (res.ok) c.put(e.request, res.clone());
            return res;
          }).catch(() => caches.match(e.request));
        })
      )
    );
    return;
  }

  // Cache static assets (CSS/JS/images)
  if (e.request.destination === 'style' || e.request.destination === 'script' || e.request.destination === 'image') {
    e.respondWith(
      caches.match(e.request).then(r => r || fetch(e.request))
    );
    return;
  }

  // Everything else — network first, no cache
});
